<?php
/**
 * Gestión de Flypass TAG (Peajes Electrónicos)
 */
include 'includes/db.php';
include 'includes/header.php';

$alert = '';
$alert_cls = '';

// ─────────────────────────────────────────────────
// PROCESAR CARGA CSV
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle !== false) {
            $headers = fgetcsv($handle, 1000, ';'); // Intenta con punto y coma primero
            if (count($headers) < 3) {
                rewind($handle);
                $headers = fgetcsv($handle, 1000, ','); // Intenta con coma
            }

            // Normalizar headers
            $header_map = [];
            foreach ($headers as $idx => $h) {
                $clean = strtolower(trim(str_replace(['"', "'", ' '], '', $h)));
                $header_map[$clean] = $idx;
            }

            // Buscar índices clave
            $idx_fecha = $header_map['fecha'] ?? $header_map['fecha/hora'] ?? $header_map['transaccion'] ?? 0;
            $idx_placa = $header_map['placa'] ?? $header_map['vehiculo'] ?? 1;
            $idx_valor = $header_map['valor'] ?? $header_map['monto'] ?? $header_map['debito'] ?? 2;
            $idx_peaje = $header_map['peaje'] ?? $header_map['nombre'] ?? $header_map['descripcion'] ?? $header_map['peajenombre'] ?? 3;

            $imported = 0;
            $errors = 0;

            while (($row = fgetcsv($handle, 1000, ';')) !== false || ($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($row) < 3) continue;

                $fecha_raw = $row[$idx_fecha] ?? '';
                $placa     = strtoupper(trim(str_replace([' ', '-'], '', $row[$idx_placa] ?? '')));
                $valor_raw = $row[$idx_valor] ?? '0';
                $peaje     = $row[$idx_peaje] ?? 'Peaje TAG';

                // Limpiar valor
                $valor = (float)str_replace(['$', '.', ','], '', $valor_raw);

                // Parsear fecha
                $fecha = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $fecha_raw)));

                if (!$placa || $valor <= 0) {
                    $errors++;
                    continue;
                }

                // Buscar vehículo por placa
                $stmt_veh = $pdo->prepare("SELECT id FROM vehicles WHERE REPLACE(REPLACE(placa, ' ', ''), '-', '') = ? LIMIT 1");
                $stmt_veh->execute([$placa]);
                $vehicle_id = $stmt_veh->fetchColumn() ?: null;

                // Evitar duplicados
                $stmt_dup = $pdo->prepare("SELECT COUNT(*) FROM flypass_movimientos WHERE fecha = ? AND placa = ? AND valor = ?");
                $stmt_dup->execute([$fecha, $placa, $valor]);
                if ($stmt_dup->fetchColumn() == 0) {
                    $stmt_ins = $pdo->prepare("
                        INSERT INTO flypass_movimientos (fecha, vehicle_id, placa, tipo_movimiento, valor, peaje_nombre, descripcion, legalizado)
                        VALUES (?, ?, ?, 'Peaje', ?, ?, 'Cargado vía CSV', 0)
                    ");
                    $stmt_ins->execute([$fecha, $vehicle_id, $row[$idx_placa] ?? $placa, $valor, $peaje]);
                    $imported++;
                }
            }
            fclose($handle);
            $alert = "Proceso completo: $imported movimientos importados, $errors con formato inválido.";
            $alert_cls = "bg-green-100 text-green-800";
        }
    } else {
        $alert = "Error al subir el archivo CSV.";
        $alert_cls = "bg-red-100 text-red-800";
    }
}

// ─────────────────────────────────────────────────
// PROCESAR LEGALIZACIÓN
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['legalize_movement'])) {
    $mov_id  = (int)$_POST['movement_id'];
    $trip_id = (int)$_POST['trip_id'];

    if ($mov_id > 0 && $trip_id > 0) {
        // Fetch movement details
        $stmt_mov = $pdo->prepare("SELECT * FROM flypass_movimientos WHERE id = ?");
        $stmt_mov->execute([$mov_id]);
        $mov = $stmt_mov->fetch(PDO::FETCH_ASSOC);

        if ($mov) {
            $pdo->beginTransaction();
            try {
                // 1. Update movement as legalized
                $stmt_up = $pdo->prepare("UPDATE flypass_movimientos SET legalizado = 1, trip_id = ?, fecha_legalizacion = CURDATE() WHERE id = ?");
                $stmt_up->execute([$trip_id, $mov_id]);

                // 2. Insert expense matching the trip
                $stmt_exp = $pdo->prepare("
                    INSERT INTO expenses (trip_id, vehicle_id, category, paid_by, amount, description, date, payment_method)
                    VALUES (?, ?, 'Peajes', 'Propietario', ?, ?, ?, 'Flypass')
                ");
                $desc_peaje = "Legalizado Flypass: " . $mov['peaje_nombre'];
                $fecha_solo = date('Y-m-d', strtotime($mov['fecha']));
                $stmt_exp->execute([$trip_id, $mov['vehicle_id'], $mov['valor'], $desc_peaje, $fecha_solo]);

                $pdo->commit();
                $alert = "Movimiento legalizado con éxito. Gasto creado para el viaje #$trip_id.";
                $alert_cls = "bg-green-100 text-green-800";
            } catch (Exception $e) {
                $pdo->rollBack();
                $alert = "Error al legalizar: " . $e->getMessage();
                $alert_cls = "bg-red-100 text-red-800";
            }
        }
    }
}

// ─────────────────────────────────────────────────
// ACCIÓN ELIMINAR / RESETEAR
// ─────────────────────────────────────────────────
if (isset($_GET['op'])) {
    $op = $_GET['op'];
    $id = (int)($_GET['id'] ?? 0);

    if ($op === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM flypass_movimientos WHERE id = ?");
        $stmt->execute([$id]);
        $alert = "Movimiento Flypass eliminado.";
        $alert_cls = "bg-red-100 text-red-800";
    }

    if ($op === 'reset' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE flypass_movimientos SET legalizado = 0, trip_id = NULL, fecha_legalizacion = NULL WHERE id = ?");
        $stmt->execute([$id]);
        $alert = "Estado de legalización reiniciado.";
        $alert_cls = "bg-orange-100 text-orange-800";
    }
}

// ─────────────────────────────────────────────────
// FILTROS Y QUERY PRINCIPAL
// ─────────────────────────────────────────────────
$f_vehicle_id = $_GET['vehicle_id'] ?? '';
$f_legalizado = $_GET['legalizado'] ?? '';
$f_fecha_desde = $_GET['fecha_desde'] ?? '';
$f_fecha_hasta = $_GET['fecha_hasta'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($f_vehicle_id !== '') {
    $where .= " AND vehicle_id = ?";
    $params[] = (int)$f_vehicle_id;
}
if ($f_legalizado !== '') {
    $where .= " AND legalizado = ?";
    $params[] = (int)$f_legalizado;
}
if ($f_fecha_desde !== '') {
    $where .= " AND fecha >= ?";
    $params[] = $f_fecha_desde . ' 00:00:00';
}
if ($f_fecha_hasta !== '') {
    $where .= " AND fecha <= ?";
    $params[] = $f_fecha_hasta . ' 23:59:59';
}

// Cards summary metrics
$q_summary = $pdo->query("
    SELECT 
        COALESCE(SUM(valor), 0) AS total_consumo,
        COALESCE(SUM(CASE WHEN legalizado=1 THEN valor ELSE 0 END), 0) AS total_legalizado,
        COALESCE(SUM(CASE WHEN legalizado=0 THEN valor ELSE 0 END), 0) AS total_pendiente
    FROM flypass_movimientos
")->fetch(PDO::FETCH_ASSOC);

// Fetch movements list
$sql = "
    SELECT m.*, v.placa AS vehiculo_placa
    FROM flypass_movimientos m
    LEFT JOIN vehicles v ON v.id = m.vehicle_id
    $where
    ORDER BY m.fecha DESC
    LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vehicles dropdown list
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
            <h2 class="text-2xl font-bold text-gray-900">Flypass TAG</h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">
                Conciliación y legalización de movimientos de peajes electrónicos Flypass.
            </p>
        </div>
    </div>

    <!-- Tarjetas Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-blue-500 uppercase font-semibold mb-1">Total Consumido TAG</p>
            <p class="text-xl font-bold text-blue-700"><?php echo fmt($q_summary['total_consumo']); ?></p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-green-500 uppercase font-semibold mb-1">Total Legalizado</p>
            <p class="text-xl font-bold text-green-700"><?php echo fmt($q_summary['total_legalizado']); ?></p>
        </div>
        <div class="bg-rose-50 border border-rose-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-rose-500 uppercase font-semibold mb-1">Pendiente por Legalizar</p>
            <p class="text-xl font-bold text-rose-700"><?php echo fmt($q_summary['total_pendiente']); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Importador CSV y Filtros -->
        <div class="space-y-6">
            <!-- Importer -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 text-sm mb-4">Importar Movimientos CSV</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Archivo CSV (Delimitador ; o ,)</label>
                        <input type="file" name="csv_file" class="w-full border border-gray-300 rounded p-1 text-xs" accept=".csv" required>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded text-xs font-bold transition-all">
                        Cargar Archivo
                    </button>
                </form>
            </div>

            <!-- Filtros -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 text-sm mb-4">Filtros de Búsqueda</h3>
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
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
                        <select name="legalizado" class="w-full border border-gray-300 rounded p-2 text-xs">
                            <option value="">Todos</option>
                            <option value="0" <?php echo $f_legalizado === '0' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="1" <?php echo $f_legalizado === '1' ? 'selected' : ''; ?>>Legalizado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha Desde</label>
                        <input type="date" name="fecha_desde" class="w-full border border-gray-300 rounded p-2 text-xs" value="<?php echo htmlspecialchars($f_fecha_desde); ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha Hasta</label>
                        <input type="date" name="fecha_hasta" class="w-full border border-gray-300 rounded p-2 text-xs" value="<?php echo htmlspecialchars($f_fecha_hasta); ?>">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="w-full px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded text-xs font-semibold transition-all">
                            Filtrar
                        </button>
                        <a href="flypass.php" class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded text-center transition-all">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado de Movimientos -->
        <div class="lg:col-span-3 bg-white border border-gray-200 rounded-lg p-5 shadow-sm overflow-x-auto">
            <h3 class="font-bold text-gray-800 text-sm mb-4">Listado de Consumos (Flypass)</h3>
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-3 py-2 text-left">Fecha Transacción</th>
                        <th class="px-3 py-2 text-left">Placa (CSV)</th>
                        <th class="px-3 py-2 text-left">Asociado A</th>
                        <th class="px-3 py-2 text-left">Peaje / Nombre</th>
                        <th class="px-3 py-2 text-right">Valor</th>
                        <th class="px-3 py-2 text-center">Estado</th>
                        <th class="px-3 py-2 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($movimientos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-400">No hay movimientos encontrados con los filtros seleccionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $m): 
                            $leg = (int)$m['legalizado'];
                            $st_cls = $leg === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            $st_lbl = $leg === 1 ? 'Legalizado' : 'Pendiente';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($m['fecha']); ?></td>
                            <td class="px-3 py-3 font-mono font-semibold text-gray-600"><?php echo htmlspecialchars($m['placa']); ?></td>
                            <td class="px-3 py-3 text-gray-800 font-semibold whitespace-nowrap">
                                <?php if ($m['vehicle_id']): ?>
                                    🚙 <?php echo htmlspecialchars($m['vehiculo_placa'] ?? '—'); ?>
                                <?php else: ?>
                                    <span class="text-red-500 font-semibold">⚠️ Placa no registrada</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-gray-600"><?php echo htmlspecialchars($m['peaje_nombre']); ?></td>
                            <td class="px-3 py-3 text-right font-bold text-gray-700"><?php echo fmt($m['valor']); ?></td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?php echo $st_cls; ?>">
                                    <?php echo $st_lbl; ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center space-x-1 whitespace-nowrap">
                                <?php if ($leg === 0 && $m['vehicle_id']): 
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
                                            Legalizar
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
                                                <button type="submit" name="legalize_movement" class="w-full px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-[10px] font-bold">
                                                    Vincular
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php elseif ($leg === 1): ?>
                                    <a href="flypass.php?op=reset&id=<?php echo $m['id']; ?>" 
                                       class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-[10px] hover:bg-orange-200 font-semibold">
                                        Desvincular
                                    </a>
                                <?php endif; ?>
                                <a href="flypass.php?op=delete&id=<?php echo $m['id']; ?>" 
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
/* Mostrar grupo en hover */
.group:hover .group-hover\:block {
    display: block !important;
}
</style>

<?php include 'includes/footer.php'; ?>
