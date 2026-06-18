<?php
/**
 * Reporte de Retenciones Tributarias
 * Muestra Rete Fuente, Rete ICA, IVA retenido por viaje con resumen mensual
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/export_buttons.php';

// ─── Filtros ─────────────────────────────────────────────────────────────────
$anio       = (int)($_GET['anio']       ?? date('Y'));
$mes        = isset($_GET['mes']) && $_GET['mes'] !== '' ? (int)$_GET['mes'] : null;
$vehicle_id = $_GET['vehicle_id'] ?? '';
$client_id  = $_GET['client_id']  ?? '';

$params = [];
$where  = "WHERE YEAR(t.date_load) = ?";
$params[] = $anio;

if ($mes !== null) {
    $where .= " AND MONTH(t.date_load) = ?";
    $params[] = $mes;
}
if ($vehicle_id !== '') {
    $where .= " AND t.vehicle_id = ?";
    $params[] = (int)$vehicle_id;
}
if ($client_id !== '') {
    $where .= " AND t.client_id = ?";
    $params[] = (int)$client_id;
}

// ─── Query principal ──────────────────────────────────────────────────────────
$sql = "SELECT
    t.id,
    t.date_load,
    v.placa,
    CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
    CASE WHEN c.person_type='Jurídica' THEN c.business_name
         ELSE CONCAT(c.firstname,' ',c.lastname1) END AS cliente,
    t.manifest_number,
    t.flete_bruto,
    t.flete_liquidado,
    t.total_deductibles,
    t.flete_neto,
    t.status,
    YEAR(t.date_load)  AS anio,
    MONTH(t.date_load) AS mes
FROM trips t
LEFT JOIN vehicles  v ON v.id = t.vehicle_id
LEFT JOIN personnel p ON p.id = t.driver_id
LEFT JOIN clients   c ON c.id = t.client_id
$where
ORDER BY t.date_load DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Totales globales ─────────────────────────────────────────────────────────
$tot_bruto       = 0;
$tot_deductibles = 0;
$tot_neto        = 0;

foreach ($filas as $f) {
    $tot_bruto       += (float)$f['flete_bruto'];
    $tot_deductibles += (float)$f['total_deductibles'];
    $tot_neto        += (float)$f['flete_neto'];
}
$pct_promedio = $tot_bruto > 0 ? ($tot_deductibles / $tot_bruto * 100) : 0;

// ─── Resumen por mes ──────────────────────────────────────────────────────────
$por_mes = [];
foreach ($filas as $f) {
    $k = (int)$f['mes'];
    if (!isset($por_mes[$k])) {
        $por_mes[$k] = ['viajes' => 0, 'bruto' => 0, 'deductibles' => 0, 'neto' => 0];
    }
    $por_mes[$k]['viajes']++;
    $por_mes[$k]['bruto']       += (float)$f['flete_bruto'];
    $por_mes[$k]['deductibles'] += (float)$f['total_deductibles'];
    $por_mes[$k]['neto']        += (float)$f['flete_neto'];
}
ksort($por_mes);

// ─── Listas para filtros ──────────────────────────────────────────────────────
$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
$clients  = $pdo->query(
    "SELECT id,
            CASE WHEN person_type='Jurídica' THEN business_name
                 ELSE CONCAT(firstname,' ',lastname1) END AS nombre
     FROM clients WHERE active=1 ORDER BY nombre"
)->fetchAll();

$meses_nombres = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

function fmt($v) {
    return '$' . number_format(abs((float)$v), 0, ',', '.');
}

$status_labels = [
    'pendiente'   => ['label' => 'Pendiente',   'cls' => 'bg-yellow-100 text-yellow-800'],
    'liquidado'   => ['label' => 'Liquidado',    'cls' => 'bg-green-100 text-green-800'],
    'cancelado'   => ['label' => 'Cancelado',    'cls' => 'bg-red-100 text-red-800'],
    'en_transito' => ['label' => 'En Tránsito',  'cls' => 'bg-blue-100 text-blue-800'],
];
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Encabezado -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Reporte de Retenciones Tributarias</h2>
            <p class="mt-1 text-sm text-gray-500">
                Rete Fuente · Rete ICA · IVA Retenido — resumen por viaje y por mes.
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3 flex-wrap no-print">
            <?php exportButtons('retenciones', $_GET); ?>
            <button onclick="window.print()"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                       bg-gradient-to-r from-gray-600 to-gray-700 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
                🖨 Imprimir
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end no-print">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Año</label>
            <select name="anio" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $anio == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Mes</label>
            <select name="mes" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($meses_nombres as $num => $nom): ?>
                    <option value="<?php echo $num; ?>" <?php echo $mes === $num ? 'selected' : ''; ?>><?php echo $nom; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Vehículo</label>
            <select name="vehicle_id" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($vehicles as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo $vehicle_id == $v['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($v['placa']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Cliente</label>
            <select name="client_id" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($clients as $cl): ?>
                    <option value="<?php echo $cl['id']; ?>" <?php echo $client_id == $cl['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cl['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-700 text-white text-sm rounded hover:bg-gray-800">Filtrar</button>
            <a href="reporte_retenciones.php" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300">Limpiar</a>
        </div>
    </form>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-xs text-blue-500 uppercase font-semibold mb-1">Total Flete Bruto</p>
            <p class="text-xl font-bold text-blue-700"><?php echo fmt($tot_bruto); ?></p>
            <p class="text-xs text-blue-400 mt-1"><?php echo count($filas); ?> viajes</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-xs text-red-500 uppercase font-semibold mb-1">Total Retenciones</p>
            <p class="text-xl font-bold text-red-700"><?php echo fmt($tot_deductibles); ?></p>
            <p class="text-xs text-red-400 mt-1">Rete Fuente + ICA + IVA</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-xs text-green-500 uppercase font-semibold mb-1">Total Flete Neto</p>
            <p class="text-xl font-bold text-green-700"><?php echo fmt($tot_neto); ?></p>
            <p class="text-xs text-green-400 mt-1">Después de retenciones</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <p class="text-xs text-amber-500 uppercase font-semibold mb-1">% Retención Promedio</p>
            <p class="text-xl font-bold text-amber-700"><?php echo number_format($pct_promedio, 2, ',', '.'); ?>%</p>
            <p class="text-xs text-amber-400 mt-1">Ret. / Bruto × 100</p>
        </div>
    </div>

    <!-- Tabla detallada -->
    <?php if (empty($filas)): ?>
        <div class="bg-white shadow rounded-lg p-12 text-center text-gray-400">
            <p class="text-lg">No hay viajes con retenciones para los filtros seleccionados.</p>
            <p class="text-sm mt-2">Ajusta los filtros e intenta de nuevo.</p>
        </div>
    <?php else: ?>
    <div class="bg-white shadow rounded-lg overflow-x-auto mb-6">
        <table class="min-w-full text-xs">
            <thead>
                <tr class="bg-gray-700 text-white">
                    <th class="px-3 py-3 text-left">Fecha</th>
                    <th class="px-3 py-3 text-left">Placa</th>
                    <th class="px-3 py-3 text-left">Conductor</th>
                    <th class="px-3 py-3 text-left">Cliente</th>
                    <th class="px-3 py-3 text-left">Manifiesto</th>
                    <th class="px-3 py-3 text-right">Flete Bruto</th>
                    <th class="px-3 py-3 text-right">Retenciones</th>
                    <th class="px-3 py-3 text-right font-bold">Flete Neto</th>
                    <th class="px-3 py-3 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($filas as $i => $f):
                    $bruto  = (float)$f['flete_bruto'];
                    $deduct = (float)$f['total_deductibles'];
                    $neto   = (float)$f['flete_neto'];
                    $row_bg = $i % 2 === 0 ? '' : 'bg-gray-50';
                    $st     = $f['status'] ?? '';
                    $st_cls = $status_labels[$st]['cls']   ?? 'bg-gray-100 text-gray-700';
                    $st_lbl = $status_labels[$st]['label'] ?? ucfirst($st);
                ?>
                <tr class="hover:bg-blue-50 <?php echo $row_bg; ?>">
                    <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                        <?php echo htmlspecialchars($f['date_load']); ?>
                    </td>
                    <td class="px-3 py-2 font-mono font-semibold text-gray-800">
                        <?php echo htmlspecialchars($f['placa'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-700">
                        <?php echo htmlspecialchars($f['conductor'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-700">
                        <?php echo htmlspecialchars($f['cliente'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-600">
                        <?php echo htmlspecialchars($f['manifest_number'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700"><?php echo fmt($bruto); ?></td>
                    <td class="px-3 py-2 text-right text-red-600 font-medium"><?php echo fmt($deduct); ?></td>
                    <td class="px-3 py-2 text-right font-bold text-green-700"><?php echo fmt($neto); ?></td>
                    <td class="px-3 py-2 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold <?php echo $st_cls; ?>">
                            <?php echo htmlspecialchars($st_lbl); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Fila de totales -->
                <tr class="bg-gray-800 text-white font-bold border-t-2 border-gray-600">
                    <td class="px-3 py-3" colspan="5">TOTAL (<?php echo count($filas); ?> viajes)</td>
                    <td class="px-3 py-3 text-right"><?php echo fmt($tot_bruto); ?></td>
                    <td class="px-3 py-3 text-right text-red-300"><?php echo fmt($tot_deductibles); ?></td>
                    <td class="px-3 py-3 text-right text-green-300 text-sm"><?php echo fmt($tot_neto); ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Resumen por mes -->
    <?php if (!empty($por_mes)): ?>
    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700 text-sm">Resumen por Mes — <?php echo $anio; ?></h3>
        </div>
        <table class="min-w-full text-xs">
            <thead>
                <tr class="bg-gray-100 text-gray-600">
                    <th class="px-4 py-3 text-left">Mes</th>
                    <th class="px-4 py-3 text-right"># Viajes</th>
                    <th class="px-4 py-3 text-right">Flete Bruto</th>
                    <th class="px-4 py-3 text-right">Retenciones</th>
                    <th class="px-4 py-3 text-right">Flete Neto</th>
                    <th class="px-4 py-3 text-right">% Retención</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($por_mes as $num_mes => $d):
                    $pct = $d['bruto'] > 0 ? ($d['deductibles'] / $d['bruto'] * 100) : 0;
                ?>
                <tr class="hover:bg-blue-50">
                    <td class="px-4 py-2 font-medium text-gray-700">
                        <?php echo ($meses_nombres[$num_mes] ?? $num_mes) . ' ' . $anio; ?>
                    </td>
                    <td class="px-4 py-2 text-right text-gray-600"><?php echo $d['viajes']; ?></td>
                    <td class="px-4 py-2 text-right text-gray-700"><?php echo fmt($d['bruto']); ?></td>
                    <td class="px-4 py-2 text-right text-red-600 font-medium"><?php echo fmt($d['deductibles']); ?></td>
                    <td class="px-4 py-2 text-right text-green-700 font-semibold"><?php echo fmt($d['neto']); ?></td>
                    <td class="px-4 py-2 text-right text-amber-700 font-semibold">
                        <?php echo number_format($pct, 2, ',', '.'); ?>%
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Total mensual -->
                <tr class="bg-gray-700 text-white font-bold border-t-2 border-gray-500">
                    <td class="px-4 py-3">TOTAL <?php echo $anio; ?></td>
                    <td class="px-4 py-3 text-right"><?php echo count($filas); ?></td>
                    <td class="px-4 py-3 text-right"><?php echo fmt($tot_bruto); ?></td>
                    <td class="px-4 py-3 text-right text-red-300"><?php echo fmt($tot_deductibles); ?></td>
                    <td class="px-4 py-3 text-right text-green-300"><?php echo fmt($tot_neto); ?></td>
                    <td class="px-4 py-3 text-right text-amber-300">
                        <?php echo number_format($pct_promedio, 2, ',', '.'); ?>%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<style>
@media print {
    .no-print, form, button, nav { display: none !important; }
    .shadow { box-shadow: none !important; }
    body { font-size: 11px; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
    thead { display: table-header-group; }
}
</style>

<?php include 'includes/footer.php'; ?>
