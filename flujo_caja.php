<?php
/**
 * Reporte de Flujo de Caja
 * Muestra el flujo de caja mensual con ingresos, gastos y utilidad estimada.
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/export_buttons.php';

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// Fetch cash flow data
$sql = "SELECT mes, nombre_mes, total_fletes, total_gastos_viaje, total_gastos_extras, total_anticipos, utilidad_bruta, num_viajes 
        FROM view_flujo_caja_mensual 
        WHERE anio = ? 
        ORDER BY mes";
$stmt = $pdo->prepare($sql);
$stmt->execute([$anio]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totales anuales
$tot_fletes = 0;
$tot_gastos_viaje = 0;
$tot_gastos_extras = 0;
$tot_anticipos = 0;
$tot_utilidad = 0;
$tot_viajes = 0;

foreach ($filas as $f) {
    $tot_fletes += (float)$f['total_fletes'];
    $tot_gastos_viaje += (float)$f['total_gastos_viaje'];
    $tot_gastos_extras += (float)$f['total_gastos_extras'];
    $tot_anticipos += (float)$f['total_anticipos'];
    $tot_utilidad += (float)$f['utilidad_bruta'];
    $tot_viajes += (int)$f['num_viajes'];
}

$tot_gastos_global = $tot_gastos_viaje + $tot_gastos_extras;

function fmt($v) {
    return '$' . number_format((float)$v, 0, ',', '.');
}
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Encabezado -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Flujo de Caja Mensual</h2>
            <p class="mt-1 text-sm text-gray-500">
                Resumen de ingresos de fletes netos, gastos operativos de viaje, gastos administrativos extras y utilidad neta estimada.
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3 flex-wrap no-print">
            <?php exportButtons('flujo_caja', $_GET); ?>
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
                    <option value="<?php echo $y; ?>" <?php echo $anio === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-brand-600 text-white text-sm rounded hover:bg-brand-700 font-medium">Filtrar</button>
            <a href="flujo_caja.php" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 font-medium">Limpiar</a>
        </div>
    </form>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-blue-500 uppercase font-semibold mb-1">Ingresos (Flete Neto)</p>
            <p class="text-xl font-bold text-blue-700"><?php echo fmt($tot_fletes); ?></p>
            <p class="text-xs text-blue-400 mt-1"><?php echo $tot_viajes; ?> viajes en total</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-red-500 uppercase font-semibold mb-1">Gastos Operativos (Viaje)</p>
            <p class="text-xl font-bold text-red-700"><?php echo fmt($tot_gastos_viaje); ?></p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-orange-500 uppercase font-semibold mb-1">Gastos Administrativos / Extras</p>
            <p class="text-xl font-bold text-orange-700"><?php echo fmt($tot_gastos_extras); ?></p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-purple-500 uppercase font-semibold mb-1">Anticipos Entregados</p>
            <p class="text-xl font-bold text-purple-700"><?php echo fmt($tot_anticipos); ?></p>
        </div>
        <div class="rounded-lg p-4 shadow-sm border <?php echo $tot_utilidad >= 0 ? 'bg-green-50 border-green-200 text-green-800' : 'bg-rose-50 border-rose-200 text-rose-800'; ?>">
            <p class="text-xs uppercase font-semibold mb-1">Utilidad Neta</p>
            <p class="text-xl font-bold <?php echo $tot_utilidad >= 0 ? 'text-green-700' : 'text-rose-700'; ?>"><?php echo fmt($tot_utilidad); ?></p>
            <p class="text-xs mt-1 <?php echo $tot_utilidad >= 0 ? 'text-green-500' : 'text-rose-500'; ?>">
                Rentabilidad: <?php echo $tot_fletes > 0 ? number_format(($tot_utilidad / $tot_fletes) * 100, 1, ',', '.') : '0'; ?>%
            </p>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="bg-white shadow rounded-lg overflow-x-auto mb-6">
        <table class="min-w-full text-xs">
            <thead>
                <tr class="bg-gray-700 text-white">
                    <th class="px-4 py-3 text-left">Mes</th>
                    <th class="px-4 py-3 text-center">Viajes</th>
                    <th class="px-4 py-3 text-right">Fletes Netos</th>
                    <th class="px-4 py-3 text-right">Gastos Viaje</th>
                    <th class="px-4 py-3 text-right">Gastos Extras</th>
                    <th class="px-4 py-3 text-right">Anticipos</th>
                    <th class="px-4 py-3 text-right font-bold">Utilidad Bruta</th>
                    <th class="px-4 py-3 text-center">Margen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($filas)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">No hay registros financieros para este año.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filas as $i => $f): 
                        $fletes = (float)$f['total_fletes'];
                        $g_viaje = (float)$f['total_gastos_viaje'];
                        $g_extras = (float)$f['total_gastos_extras'];
                        $anticipos = (float)$f['total_anticipos'];
                        $utilidad = (float)$f['utilidad_bruta'];
                        $viajes = (int)$f['num_viajes'];
                        $margen = $fletes > 0 ? ($utilidad / $fletes) * 100 : 0;
                        $row_bg = $i % 2 === 0 ? '' : 'bg-gray-50';
                    ?>
                    <tr class="hover:bg-blue-50 <?php echo $row_bg; ?>">
                        <td class="px-4 py-3 font-semibold text-gray-800"><?php echo htmlspecialchars($f['nombre_mes']); ?></td>
                        <td class="px-4 py-3 text-center text-gray-600 font-medium"><?php echo $viajes; ?></td>
                        <td class="px-4 py-3 text-right text-gray-700"><?php echo fmt($fletes); ?></td>
                        <td class="px-4 py-3 text-right text-gray-600"><?php echo fmt($g_viaje); ?></td>
                        <td class="px-4 py-3 text-right text-gray-600"><?php echo fmt($g_extras); ?></td>
                        <td class="px-4 py-3 text-right text-purple-600"><?php echo fmt($anticipos); ?></td>
                        <td class="px-4 py-3 text-right font-bold <?php echo $utilidad >= 0 ? 'text-green-700' : 'text-rose-700'; ?>"><?php echo fmt($utilidad); ?></td>
                        <td class="px-4 py-3 text-center font-medium">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] <?php echo $utilidad >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo number_format($margen, 1, ',', '.'); ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Fila Totales -->
                    <tr class="bg-gray-800 text-white font-bold border-t-2 border-gray-600">
                        <td class="px-4 py-3">TOTAL ANUAL</td>
                        <td class="px-4 py-3 text-center"><?php echo $tot_viajes; ?></td>
                        <td class="px-4 py-3 text-right"><?php echo fmt($tot_fletes); ?></td>
                        <td class="px-4 py-3 text-right"><?php echo fmt($tot_gastos_viaje); ?></td>
                        <td class="px-4 py-3 text-right"><?php echo fmt($tot_gastos_extras); ?></td>
                        <td class="px-4 py-3 text-right text-purple-300"><?php echo fmt($tot_anticipos); ?></td>
                        <td class="px-4 py-3 text-right <?php echo $tot_utilidad >= 0 ? 'text-green-300' : 'text-rose-300'; ?> text-sm"><?php echo fmt($tot_utilidad); ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] <?php echo $tot_utilidad >= 0 ? 'bg-green-600 text-white' : 'bg-red-600 text-white'; ?>">
                                <?php echo $tot_fletes > 0 ? number_format(($tot_utilidad / $tot_fletes) * 100, 1, ',', '.') : '0'; ?>%
                            </span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .no-print, form, button, nav { display: none !important; }
    .shadow { box-shadow: none !important; }
    body { font-size: 11px; }
}
</style>

<?php include 'includes/footer.php'; ?>
