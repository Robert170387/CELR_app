<?php
/**
 * Gestión de Compensado RC (Liquidación de Conductores)
 */
include 'includes/db.php';
include 'includes/header.php';

$alert = '';
$alert_cls = '';

// ─────────────────────────────────────────────────
// ACCIONES: Registrar Pago o Eliminar
// ─────────────────────────────────────────────────
if (isset($_GET['op'])) {
    $op = $_GET['op'];
    $id = (int)($_GET['id'] ?? 0);

    if ($op === 'pay' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE compensado_rc SET estado = 'Pagado', fecha_pago = CURDATE() WHERE id = ?");
        $stmt->execute([$id]);
        $alert = "Pago registrado exitosamente para la compensación.";
        $alert_cls = "bg-green-100 text-green-800";
    }

    if ($op === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM compensado_rc WHERE id = ?");
        $stmt->execute([$id]);
        $alert = "Registro de compensación eliminado.";
        $alert_cls = "bg-red-100 text-red-800";
    }
}

// ─────────────────────────────────────────────────
// GUARDAR NUEVA COMPENSACIÓN
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_compensation'])) {
    $personnel_id = (int)$_POST['personnel_id'];
    $vehicle_id   = (int)$_POST['vehicle_id'];
    $anio         = (int)$_POST['anio'];
    $mes          = (int)$_POST['mes'];
    $com_pct      = (float)$_POST['comision_porcentaje'];
    $descuentos   = (float)$_POST['descuentos'];
    $notas        = $_POST['notas'] ?? '';

    // Re-calcular los valores en el servidor
    $q_trips = $pdo->prepare("
        SELECT 
            COALESCE(SUM(flete_neto), 0) AS flete_neto,
            COALESCE(SUM(advance_owner), 0) AS anticipos
        FROM trips
        WHERE driver_id = ? AND vehicle_id = ? AND YEAR(date_load) = ? AND MONTH(date_load) = ? AND status != 'Cancelado'
    ");
    $q_trips->execute([$personnel_id, $vehicle_id, $anio, $mes]);
    $calc = $q_trips->fetch(PDO::FETCH_ASSOC);

    $flete_neto = (float)$calc['flete_neto'];
    $anticipos  = (float)$calc['anticipos'];
    $com_bruta  = ($flete_neto * $com_pct) / 100;
    $neto_pagar = $com_bruta - $anticipos - $descuentos;

    $codigo = 'RC-' . $anio . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . rand(1000, 9999);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO compensado_rc (
                codigo, personnel_id, vehicle_id, anio, mes, flete_neto_periodo, 
                comision_porcentaje, comision_bruta, descuentos, anticipos_periodo, neto_pagar, estado, notas
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', ?)
        ");
        $stmt->execute([
            $codigo, $personnel_id, $vehicle_id, $anio, $mes, $flete_neto,
            $com_pct, $com_bruta, $descuentos, $anticipos, $neto_pagar, $notas
        ]);
        $alert = "Compensación guardada con éxito con código: $codigo.";
        $alert_cls = "bg-green-100 text-green-800";
    } catch (Exception $e) {
        $alert = "Error al guardar compensación: " . $e->getMessage();
        $alert_cls = "bg-red-100 text-red-800";
    }
}

// ─────────────────────────────────────────────────
// SIMULACIÓN / CÁLCULO PREVIO
// ─────────────────────────────────────────────────
$calc_data = null;
$calc_trips = [];
if (isset($_GET['action']) && $_GET['action'] === 'calculate') {
    $c_driver_id  = (int)($_GET['driver_id'] ?? 0);
    $c_vehicle_id = (int)($_GET['vehicle_id'] ?? 0);
    $c_anio       = (int)($_GET['anio'] ?? date('Y'));
    $c_mes        = (int)($_GET['mes'] ?? date('n'));
    $c_com_pct    = (float)($_GET['comision_porcentaje'] ?? 10);
    $c_desc       = (float)($_GET['descuentos'] ?? 0);

    if ($c_driver_id > 0 && $c_vehicle_id > 0) {
        // Query trips matching
        $q_trips_list = $pdo->prepare("
            SELECT id, manifest_number, date_load, flete_neto, advance_owner
            FROM trips
            WHERE driver_id = ? AND vehicle_id = ? AND YEAR(date_load) = ? AND MONTH(date_load) = ? AND status != 'Cancelado'
            ORDER BY date_load ASC
        ");
        $q_trips_list->execute([$c_driver_id, $c_vehicle_id, $c_anio, $c_mes]);
        $calc_trips = $q_trips_list->fetchAll(PDO::FETCH_ASSOC);

        $tot_flete = 0;
        $tot_anti  = 0;
        foreach ($calc_trips as $t) {
            $tot_flete += (float)$t['flete_neto'];
            $tot_anti  += (float)$t['advance_owner'];
        }

        $com_bruta = ($tot_flete * $c_com_pct) / 100;
        $neto_pagar = $com_bruta - $tot_anti - $c_desc;

        $calc_data = [
            'flete_neto' => $tot_flete,
            'anticipos'  => $tot_anti,
            'com_bruta'  => $com_bruta,
            'neto_pagar' => $neto_pagar,
            'driver_id'  => $c_driver_id,
            'vehicle_id' => $c_vehicle_id,
            'anio'       => $c_anio,
            'mes'        => $c_mes,
            'com_pct'    => $c_com_pct,
            'desc'       => $c_desc
        ];
    }
}

// ─────────────────────────────────────────────────
// FETCH EXISTENTES Y LISTADOS
// ─────────────────────────────────────────────────
$existing_comp = $pdo->query("
    SELECT 
        c.*,
        CONCAT(p.firstname, ' ', IFNULL(p.lastname, '')) AS conductor_nombre,
        v.placa
    FROM compensado_rc c
    JOIN personnel p ON p.id = c.personnel_id
    JOIN vehicles v ON v.id = c.vehicle_id
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$drivers = $pdo->query("SELECT id, CONCAT(firstname, ' ', IFNULL(lastname, '')) AS nombre FROM personnel WHERE type='Conductor' AND active=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll(PDO::FETCH_ASSOC);

$meses_nombres = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];

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
            <h2 class="text-2xl font-bold text-gray-900">Compensado RC (Liquidaciones de Conductores)</h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">
                Cálculo y registro de liquidaciones mensuales para conductores en base a fletes cargados, comisiones y anticipos.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Calculadora de Compensaciones -->
        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm h-fit">
            <h3 class="font-bold text-gray-800 text-sm mb-4">Calcular Nueva Compensación</h3>
            <form method="GET" class="space-y-4">
                <input type="hidden" name="action" value="calculate">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Conductor</label>
                    <select name="driver_id" class="w-full border border-gray-300 rounded p-2 text-xs" required>
                        <option value="">Seleccione Conductor</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo isset($_GET['driver_id']) && (int)$_GET['driver_id'] === $d['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Vehículo</label>
                    <select name="vehicle_id" class="w-full border border-gray-300 rounded p-2 text-xs" required>
                        <option value="">Seleccione Vehículo</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo isset($_GET['vehicle_id']) && (int)$_GET['vehicle_id'] === $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['placa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Año</label>
                        <select name="anio" class="w-full border border-gray-300 rounded p-2 text-xs" required>
                            <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo isset($_GET['anio']) && (int)$_GET['anio'] === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Mes</label>
                        <select name="mes" class="w-full border border-gray-300 rounded p-2 text-xs" required>
                            <?php foreach ($meses_nombres as $num => $nom): ?>
                                <option value="<?php echo $num; ?>" <?php echo isset($_GET['mes']) && (int)$_GET['mes'] === $num ? 'selected' : ''; ?>><?php echo $nom; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">% Comisión</label>
                        <input type="number" step="0.1" name="comision_porcentaje" class="w-full border border-gray-300 rounded p-2 text-xs" 
                               value="<?php echo htmlspecialchars($_GET['comision_porcentaje'] ?? '10'); ?>" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Otros Descuentos</label>
                        <input type="number" name="descuentos" class="w-full border border-gray-300 rounded p-2 text-xs" 
                               value="<?php echo htmlspecialchars($_GET['descuentos'] ?? '0'); ?>" required>
                    </div>
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded text-xs font-bold transition-all">
                    Calcular
                </button>
            </form>

            <?php if ($calc_data !== null): ?>
                <div class="mt-6 border-t border-gray-100 pt-4 space-y-3">
                    <h4 class="font-bold text-xs text-gray-800">Resultados del Cálculo:</h4>
                    <div class="flex justify-between text-xs text-gray-600">
                        <span>Flete Neto Periodo:</span>
                        <span class="font-bold text-gray-800"><?php echo fmt($calc_data['flete_neto']); ?></span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-600">
                        <span>Comisión Bruta (<?php echo $calc_data['com_pct']; ?>%):</span>
                        <span class="font-bold text-gray-800"><?php echo fmt($calc_data['com_bruta']); ?></span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-600 text-red-600">
                        <span>Anticipos:</span>
                        <span class="font-bold">- <?php echo fmt($calc_data['anticipos']); ?></span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-600 text-red-600">
                        <span>Descuentos:</span>
                        <span class="font-bold">- <?php echo fmt($calc_data['desc']); ?></span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t border-gray-100 pt-2 text-gray-800">
                        <span>Neto a Pagar:</span>
                        <span class="<?php echo $calc_data['neto_pagar'] >= 0 ? 'text-green-700' : 'text-rose-700'; ?>">
                            <?php echo fmt($calc_data['neto_pagar']); ?>
                        </span>
                    </div>

                    <form method="POST" class="mt-4 space-y-3">
                        <input type="hidden" name="personnel_id" value="<?php echo $calc_data['driver_id']; ?>">
                        <input type="hidden" name="vehicle_id" value="<?php echo $calc_data['vehicle_id']; ?>">
                        <input type="hidden" name="anio" value="<?php echo $calc_data['anio']; ?>">
                        <input type="hidden" name="mes" value="<?php echo $calc_data['mes']; ?>">
                        <input type="hidden" name="comision_porcentaje" value="<?php echo $calc_data['com_pct']; ?>">
                        <input type="hidden" name="descuentos" value="<?php echo $calc_data['desc']; ?>">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Notas</label>
                            <textarea name="notas" class="w-full border border-gray-300 rounded p-2 text-xs" rows="2" placeholder="Notas sobre descuentos, bonos, etc."></textarea>
                        </div>
                        <button type="submit" name="save_compensation" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold transition-all">
                            Guardar Compensación
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Listado de Compensaciones -->
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg p-5 shadow-sm overflow-x-auto">
            <h3 class="font-bold text-gray-800 text-sm mb-4">Registro de Compensaciones</h3>
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-3 py-2 text-left">Código</th>
                        <th class="px-3 py-2 text-left">Conductor</th>
                        <th class="px-3 py-2 text-left">Vehículo</th>
                        <th class="px-3 py-2 text-center">Período</th>
                        <th class="px-3 py-2 text-right">Comisión</th>
                        <th class="px-3 py-2 text-right">Anticipos</th>
                        <th class="px-3 py-2 text-right font-bold">Neto Pagar</th>
                        <th class="px-3 py-2 text-center">Estado</th>
                        <th class="px-3 py-2 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($existing_comp)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-6 text-gray-400">No hay compensaciones registradas en el sistema.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($existing_comp as $c): 
                            $st = $c['estado'];
                            $st_cls = $st === 'Pagado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 font-semibold text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($c['codigo']); ?></td>
                            <td class="px-3 py-3 text-gray-700"><?php echo htmlspecialchars($c['conductor_nombre']); ?></td>
                            <td class="px-3 py-3 font-mono font-semibold text-gray-600"><?php echo htmlspecialchars($c['placa']); ?></td>
                            <td class="px-3 py-3 text-center text-gray-600"><?php echo str_pad($c['mes'], 2, '0', STR_PAD_LEFT) . '/' . $c['anio']; ?></td>
                            <td class="px-3 py-3 text-right text-gray-700"><?php echo fmt($c['comision_bruta']); ?> (<?php echo (float)$c['comision_porcentaje']; ?>%)</td>
                            <td class="px-3 py-3 text-right text-red-600"><?php echo fmt($c['anticipos_periodo']); ?></td>
                            <td class="px-3 py-3 text-right font-bold text-gray-800"><?php echo fmt($c['neto_pagar']); ?></td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?php echo $st_cls; ?>">
                                    <?php echo $st; ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center space-x-1 whitespace-nowrap">
                                <?php if ($st !== 'Pagado'): ?>
                                    <a href="compensado_rc.php?op=pay&id=<?php echo $c['id']; ?>" 
                                       class="px-2 py-1 bg-green-100 text-green-700 rounded text-[10px] hover:bg-green-200 transition-all font-semibold">
                                        Registrar Pago
                                    </a>
                                <?php endif; ?>
                                <a href="compensado_rc.php?op=delete&id=<?php echo $c['id']; ?>" 
                                   onclick="return confirm('¿Seguro que desea eliminar esta compensación?');" 
                                   class="px-2 py-1 bg-red-100 text-red-700 rounded text-[10px] hover:bg-red-200 transition-all font-semibold">
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

    <!-- Lista de viajes para verificación -->
    <?php if (!empty($calc_trips)): ?>
        <div class="mt-8 bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
            <h3 class="font-bold text-gray-800 text-sm mb-4">Viajes del Período para Validación</h3>
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-600">
                        <th class="px-4 py-2 text-left">Manifiesto</th>
                        <th class="px-4 py-2 text-left">Fecha Carga</th>
                        <th class="px-4 py-2 text-right">Flete Neto</th>
                        <th class="px-4 py-2 text-right">Anticipos Propietario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($calc_trips as $t): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-800 font-semibold"><?php echo htmlspecialchars($t['manifest_number'] ?? '—'); ?></td>
                        <td class="px-4 py-2 text-gray-600"><?php echo htmlspecialchars($t['date_load']); ?></td>
                        <td class="px-4 py-2 text-right text-gray-700"><?php echo fmt($t['flete_neto']); ?></td>
                        <td class="px-4 py-2 text-right text-red-600"><?php echo fmt($t['advance_owner']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
