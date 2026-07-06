<?php
/**
 * Gestión de Tarjeta Débito (Movimientos y Conciliación)
 */
include 'includes/db.php';
include 'includes/header.php';

$alert = '';
$alert_cls = '';

// ─────────────────────────────────────────────────
// REGISTRAR MOVIMIENTO MANUAL
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_manual'])) {
    $fecha           = $_POST['fecha'];
    $vehicle_id      = (int)$_POST['vehicle_id'];
    $tipo            = $_POST['tipo'];
    $establecimiento = $_POST['establecimiento'];
    $valor           = (float)$_POST['valor'];
    $referencia      = $_POST['referencia'] ?? '';
    $descripcion     = $_POST['descripcion'] ?? '';

    if ($fecha && $vehicle_id > 0 && $valor > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tarjeta_movimientos (fecha, vehicle_id, tipo, establecimiento, valor, referencia, descripcion, cruzado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente')
            ");
            $stmt->execute([$fecha, $vehicle_id, $tipo, $establecimiento, $valor, $referencia, $descripcion]);
            $alert = "Movimiento registrado manualmente de forma exitosa.";
            $alert_cls = "bg-green-100 text-green-800";
        } catch (Exception $e) {
            $alert = "Error al registrar movimiento: " . $e->getMessage();
            $alert_cls = "bg-red-100 text-red-800";
        }
    } else {
        $alert = "Por favor complete los campos obligatorios.";
        $alert_cls = "bg-red-100 text-red-800";
    }
}

// ─────────────────────────────────────────────────
// PROCESAR CARGA CSV (Extracto Bancario)
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle !== false) {
            $headers = fgetcsv($handle, 1000, ';');
            if (count($headers) < 3) {
                rewind($handle);
                $headers = fgetcsv($handle, 1000, ',');
            }

            // Normalizar headers
            $header_map = [];
            foreach ($headers as $idx => $h) {
                $clean = strtolower(trim(str_replace(['"', "'", ' '], '', $h)));
                $header_map[$clean] = $idx;
            }

            $idx_fecha = $header_map['fecha'] ?? 0;
            $idx_desc  = $header_map['descripcion'] ?? $header_map['detalle'] ?? 1;
            $idx_ref   = $header_map['referencia'] ?? $header_map['documento'] ?? 2;
            $idx_valor = $header_map['valor'] ?? $header_map['monto'] ?? $header_map['debito'] ?? 3;

            $imported = 0;
            $errors = 0;

            while (($row = fgetcsv($handle, 1000, ';')) !== false || ($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($row) < 3) continue;

                $fecha_raw = $row[$idx_fecha] ?? '';
                $desc      = $row[$idx_desc] ?? '';
                $ref       = $row[$idx_ref] ?? '';
                $valor_raw = $row[$idx_valor] ?? '0';

                // Limpiar valor
                $valor = (float)str_replace(['$', '.', ','], '', $valor_raw);
                $fecha = date('Y-m-d', strtotime(str_replace('/', '-', $fecha_raw)));

                if ($valor <= 0 || !$fecha_raw) {
                    $errors++;
                    continue;
                }

                // Intentar adivinar la placa en la descripción o referencia
                $vehicle_id = null;
                $stmt_v = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1");
                while ($v = $stmt_v->fetch(PDO::FETCH_ASSOC)) {
                    $clean_placa = str_replace([' ', '-'], '', $v['placa']);
                    if (stripos(str_replace([' ', '-'], '', $desc), $clean_placa) !== false || stripos(str_replace([' ', '-'], '', $ref), $clean_placa) !== false) {
                        $vehicle_id = $v['id'];
                        break;
                    }
                }

                // Evitar duplicados
                $stmt_dup = $pdo->prepare("SELECT COUNT(*) FROM tarjeta_movimientos WHERE fecha = ? AND valor = ? AND referencia = ?");
                $stmt_dup->execute([$fecha, $valor, $ref]);
                if ($stmt_dup->fetchColumn() == 0) {
                    $stmt_ins = $pdo->prepare("
                        INSERT INTO tarjeta_movimientos (fecha, vehicle_id, tipo, establecimiento, referencia, descripcion, valor, cruzado)
                        VALUES (?, ?, 'Compra', ?, ?, ?, ?, 'Pendiente')
                    ");
                    $stmt_ins->execute([$fecha, $vehicle_id, 'Establecimiento Bancario', $ref, $desc, $valor]);
                    $imported++;
                }
            }
            fclose($handle);
            $alert = "Proceso completo: $imported movimientos del extracto importados, $errors registros inválidos.";
            $alert_cls = "bg-green-100 text-green-800";
        }
    } else {
        $alert = "Error al subir el archivo CSV.";
        $alert_cls = "bg-red-100 text-red-800";
    }
}

// ─────────────────────────────────────────────────
// PROCESAR CRUCE / CONCILIACIÓN
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_movement'])) {
    $mov_id   = (int)$_POST['movement_id'];
    $trip_id  = (int)$_POST['trip_id'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    // Resolve category info
    $category_name = null;
    $category_slug = null;
    if ($category_id) {
        $stmtCat = $pdo->prepare("SELECT name, slug FROM expense_categories WHERE id = ? AND active = 1");
        $stmtCat->execute([$category_id]);
        $catInfo = $stmtCat->fetch();
        if ($catInfo) {
            $category_name = $catInfo['name'];
            $category_slug = $catInfo['slug'];
        }
    }

    if ($mov_id > 0 && $trip_id > 0 && $category_id) {
        $stmt_mov = $pdo->prepare("SELECT * FROM tarjeta_movimientos WHERE id = ?");
        $stmt_mov->execute([$mov_id]);
        $mov = $stmt_mov->fetch(PDO::FETCH_ASSOC);

        if ($mov) {
            $pdo->beginTransaction();
            try {
                // 1. Update movement status as Cruzado
                $detail = "Cruzado con viaje #$trip_id - Categoría: $category_name";
                $stmt_up = $pdo->prepare("UPDATE tarjeta_movimientos SET cruzado = 'Cruzado', fecha_cruce = CURDATE(), detalle_cruce = ? WHERE id = ?");
                $stmt_up->execute([$detail, $mov_id]);

                // 2. Insert expense into matching trip
                $stmt_exp = $pdo->prepare("
                    INSERT INTO expenses (trip_id, vehicle_id, category_id, category_name, paid_by, amount, description, date, payment_method)
                    VALUES (?, ?, ?, ?, 'Propietario', ?, ?, ?, 'Tarjeta Débito')
                ");
                $desc_exp = "Pago Tarjeta: " . $mov['descripcion'] . ' (Establecimiento: ' . $mov['establecimiento'] . ')';
                $stmt_exp->execute([$trip_id, $mov['vehicle_id'], $category_id, $category_name, $mov['valor'], $desc_exp, $mov['fecha']]);

                $pdo->commit();
                $alert = "Movimiento conciliado correctamente. Gasto de '$category_name' creado para el viaje #$trip_id.";
                $alert_cls = "bg-green-100 text-green-800";
            } catch (Exception $e) {
                $pdo->rollBack();
                $alert = "Error al conciliar movimiento: " . $e->getMessage();
                $alert_cls = "bg-red-100 text-red-800";
            }
        }
    }
}

// ─────────────────────────────────────────────────
// ELIMINAR / RESETEAR ACCIÓN
// ─────────────────────────────────────────────────
if (isset($_GET['op'])) {
    $op = $_GET['op'];
    $id = (int)($_GET['id'] ?? 0);

    if ($op === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM tarjeta_movimientos WHERE id = ?");
        $stmt->execute([$id]);
        $alert = "Movimiento de tarjeta eliminado.";
        $alert_cls = "bg-red-100 text-red-800";
    }

    if ($op === 'reset' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE tarjeta_movimientos SET cruzado = 'Pendiente', fecha_cruce = NULL, detalle_cruce = NULL WHERE id = ?");
        $stmt->execute([$id]);
        $alert = "Estado de conciliación de movimiento reiniciado.";
        $alert_cls = "bg-orange-100 text-orange-800";
    }
}

// ─────────────────────────────────────────────────
// FILTROS Y QUERIES
// ─────────────────────────────────────────────────
$f_vehicle_id = $_GET['vehicle_id'] ?? '';
$f_cruzado    = $_GET['cruzado'] ?? '';
$f_fecha_desde = $_GET['fecha_desde'] ?? '';
$f_fecha_hasta = $_GET['fecha_hasta'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($f_vehicle_id !== '') {
    $where .= " AND vehicle_id = ?";
    $params[] = (int)$f_vehicle_id;
}
if ($f_cruzado !== '') {
    $where .= " AND cruzado = ?";
    $params[] = $f_cruzado;
}
if ($f_fecha_desde !== '') {
    $where .= " AND fecha >= ?";
    $params[] = $f_fecha_desde;
}
if ($f_fecha_hasta !== '') {
    $where .= " AND fecha <= ?";
    $params[] = $f_fecha_hasta;
}

// Metrics Cards
$q_summary = $pdo->query("
    SELECT 
        COALESCE(SUM(valor), 0) AS total_consumo,
        COALESCE(SUM(CASE WHEN cruzado='Cruzado' THEN valor ELSE 0 END), 0) AS total_cruzado,
        COALESCE(SUM(CASE WHEN cruzado='Pendiente' THEN valor ELSE 0 END), 0) AS total_pendiente
    FROM tarjeta_movimientos
")->fetch(PDO::FETCH_ASSOC);

// Fetch transactions list
$sql = "
    SELECT tm.*, v.placa AS vehiculo_placa
    FROM tarjeta_movimientos tm
    LEFT JOIN vehicles v ON v.id = tm.vehicle_id
    $where
    ORDER BY tm.fecha DESC
    LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll(PDO::FETCH_ASSOC);

function fmt($v) {
    return '$' . number_format((float)$v, 0, ',', '.');
}
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <?php if ($alert): ?>
        <div class="p-4 mb-4 rounded <?php echo $alert_cls; ?> font-medium">
            <?php echo htmlspecialchars($alert); ?>
        </div>
    <?php endif; ?>

    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Tarjeta Débito corporativa</h2>
            <p class="mt-1 text-sm text-gray-500 font-medium font-semibold">
                Gestión, conciliación y control de compras y gastos realizados con tarjeta de débito empresarial.
            </p>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-blue-500 uppercase font-semibold mb-1">Total Gastos Tarjeta</p>
            <p class="text-xl font-bold text-blue-700"><?php echo fmt($q_summary['total_consumo']); ?></p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-green-500 uppercase font-semibold mb-1">Total Conciliado / Cruzado</p>
            <p class="text-xl font-bold text-green-700"><?php echo fmt($q_summary['total_cruzado']); ?></p>
        </div>
        <div class="bg-rose-50 border border-rose-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-rose-500 uppercase font-semibold mb-1">Total Pendiente por Cruzar</p>
            <p class="text-xl font-bold text-rose-700"><?php echo fmt($q_summary['total_pendiente']); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar controls (Forms & Filters) -->
        <div class="space-y-6">
            <!-- Manual Add Form -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 text-sm mb-4">Registrar Movimiento Manual</h3>
                <form method="POST" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha *</label>
                        <input type="date" name="fecha" class="w-full border border-gray-300 rounded p-2 text-xs" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Vehículo *</label>
                        <select name="vehicle_id" class="w-full border border-gray-300 rounded p-2 text-xs" required>
                            <option value="">Seleccione Vehículo</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['placa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo Movimiento</label>
                        <select name="tipo" class="w-full border border-gray-300 rounded p-2 text-xs">
                            <option value="Compra">Compra / Establecimiento</option>
                            <option value="Retiro">Retiro Cajero</option>
                            <option value="Comisión">Comisión Bancaria</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Establecimiento / Destino</label>
                        <input type="text" name="establecimiento" class="w-full border border-gray-300 rounded p-2 text-xs" placeholder="E.g. EDS Terpel, Taller X" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Valor *</label>
                        <input type="number" name="valor" class="w-full border border-gray-300 rounded p-2 text-xs" placeholder="E.g. 150000" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Referencia</label>
                        <input type="text" name="referencia" class="w-full border border-gray-300 rounded p-2 text-xs" placeholder="E.g. Nro de voucher">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción</label>
                        <textarea name="descripcion" class="w-full border border-gray-300 rounded p-2 text-xs" rows="2" placeholder="Detalles del consumo..."></textarea>
                    </div>
                    <button type="submit" name="save_manual" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold transition-all">
                        Guardar Consumo
                    </button>
                </form>
            </div>

            <!-- Import Extract CSV -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 text-sm mb-4">Importar Extracto (CSV)</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Archivo CSV</label>
                        <input type="file" name="csv_file" class="w-full border border-gray-300 rounded p-1 text-xs" accept=".csv" required>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded text-xs font-bold transition-all">
                        Cargar Extracto
                    </button>
                </form>
            </div>

            <!-- Filtros -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 text-sm mb-4">Filtros</h3>
                <form method="GET" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Vehículo</label>
                        <select name="vehicle_id" class="w-full border border-gray-300 rounded p-2 text-xs">
                            <option value="">Todos</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo $f_vehicle_id == $v['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v['placa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Conciliación</label>
                        <select name="cruzado" class="w-full border border-gray-300 rounded p-2 text-xs">
                            <option value="">Todos</option>
                            <option value="Pendiente" <?php echo $f_cruzado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Cruzado" <?php echo $f_cruzado === 'Cruzado' ? 'selected' : ''; ?>>Cruzado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
                        <input type="date" name="fecha_desde" class="w-full border border-gray-300 rounded p-2 text-xs" value="<?php echo htmlspecialchars($f_fecha_desde); ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
                        <input type="date" name="fecha_hasta" class="w-full border border-gray-300 rounded p-2 text-xs" value="<?php echo htmlspecialchars($f_fecha_hasta); ?>">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="w-full px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded text-xs font-semibold transition-all">
                            Filtrar
                        </button>
                        <a href="tarjeta.php" class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded text-center transition-all">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- List Table -->
        <div class="lg:col-span-3 bg-white border border-gray-200 rounded-lg p-5 shadow-sm overflow-x-auto">
            <h3 class="font-bold text-gray-800 text-sm mb-4">Listado de Consumos de Tarjeta</h3>
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Vehículo</th>
                        <th class="px-3 py-2 text-left">Tipo / Ref</th>
                        <th class="px-3 py-2 text-left">Establecimiento / Detalle</th>
                        <th class="px-3 py-2 text-right">Valor</th>
                        <th class="px-3 py-2 text-center">Estado</th>
                        <th class="px-3 py-2 text-center font-bold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($movimientos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-400">No se encontraron movimientos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $m): 
                            $cr = $m['cruzado'];
                            $st_cls = $cr === 'Cruzado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($m['fecha']); ?></td>
                            <td class="px-3 py-3 font-mono font-semibold text-gray-800">
                                <?php if ($m['vehicle_id']): ?>
                                    🚗 <?php echo htmlspecialchars($m['vehiculo_placa'] ?? '—'); ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Sin vehículo</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-gray-600">
                                <div class="font-bold"><?php echo htmlspecialchars($m['tipo']); ?></div>
                                <div class="text-[10px] text-gray-400"><?php echo htmlspecialchars($m['referencia'] ?: '—'); ?></div>
                            </td>
                            <td class="px-3 py-3 text-gray-600">
                                <div class="font-bold text-gray-800"><?php echo htmlspecialchars($m['establecimiento']); ?></div>
                                <div class="text-[10px] text-gray-500 font-medium"><?php echo htmlspecialchars($m['descripcion']); ?></div>
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-gray-700"><?php echo fmt($m['valor']); ?></td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?php echo $st_cls; ?>">
                                    <?php echo $cr; ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center space-x-1 whitespace-nowrap">
                                <?php if ($cr === 'Pendiente' && $m['vehicle_id']): 
                                    // Fetch recent trips for this vehicle to suggest
                                    $stmt_trips = $pdo->prepare("
                                        SELECT id, manifest_number, date_load 
                                        FROM trips 
                                        WHERE vehicle_id = ? AND status != 'Cancelado' 
                                        ORDER BY date_load DESC LIMIT 10
                                    ");
                                    $stmt_trips->execute([$m['vehicle_id']]);
                                    $veh_trips = $stmt_trips->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                    <div class="inline-block relative group">
                                        <button class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-semibold hover:bg-blue-200">
                                            Cruzar
                                        </button>
                                        <!-- Mini Dropdown Form -->
                                        <div class="hidden group-hover:block absolute right-0 mt-1 w-64 bg-white border border-gray-200 rounded shadow-lg p-3 z-50 text-left">
                                            <form method="POST" class="space-y-2">
                                                <input type="hidden" name="movement_id" value="<?php echo $m['id']; ?>">
                                                
                                                <label class="block text-[10px] font-bold text-gray-700">Seleccione Viaje / Manifiesto</label>
                                                <select name="trip_id" class="w-full border border-gray-300 rounded p-1 text-[11px]" required>
                                                    <option value="">Seleccione Viaje</option>
                                                    <?php foreach ($veh_trips as $vt): ?>
                                                        <option value="<?php echo $vt['id']; ?>">
                                                            #<?php echo htmlspecialchars($vt['manifest_number'] ?? $vt['id']); ?> (<?php echo $vt['date_load']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <label class="block text-[10px] font-bold text-gray-700 mt-2">Categoría del Gasto</label>
                                                <?php $tarjeta_cats = $pdo->query("SELECT id, name FROM expense_categories WHERE active = 1 ORDER BY FIELD(type,'viaje','vehiculo_fijo','administrativo','especial'), sort_order ASC, name ASC")->fetchAll(); ?>
                                                <select name="category_id" class="w-full border border-gray-300 rounded p-1 text-[11px]" required>
                                                    <option value="">-- Seleccione --</option>
                                                    <?php foreach ($tarjeta_cats as $tc): ?>
                                                        <option value="<?php echo $tc['id']; ?>"><?php echo htmlspecialchars($tc['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="submit" name="match_movement" class="w-full px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-[10px] font-bold">
                                                    Conciliar y Crear Gasto
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php elseif ($cr === 'Cruzado'): ?>
                                    <a href="tarjeta.php?op=reset&id=<?php echo $m['id']; ?>" 
                                       class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-[10px] hover:bg-orange-200 font-semibold">
                                        Desvincular
                                    </a>
                                <?php endif; ?>
                                <a href="tarjeta.php?op=delete&id=<?php echo $m['id']; ?>" 
                                   onclick="return confirm('¿Eliminar este registro?');" 
                                   class="px-2 py-1 bg-red-100 text-red-700 rounded text-[10px] hover:bg-red-200 font-semibold">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.group:hover .group-hover\:block {
    display: block !important;
}
</style>

<?php include 'includes/footer.php'; ?>
