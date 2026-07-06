<?php
/**
 * Dashboard de KPIs e Indicadores
 * Muestra métricas clave de la flota, rendimiento, clientes y vehículos.
 */
include 'includes/db.php';
include 'includes/header.php';

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// 1. General Metrics
$q_general = $pdo->prepare("
    SELECT 
        COUNT(id) AS total_viajes,
        COALESCE(SUM(flete_bruto), 0) AS total_bruto,
        COALESCE(SUM(flete_neto), 0) AS total_neto,
        COALESCE(SUM(weight), 0) AS total_peso
    FROM trips
    WHERE YEAR(date_load) = ? AND status != 'Cancelado'
");
$q_general->execute([$anio]);
$gen = $q_general->fetch(PDO::FETCH_ASSOC);

$total_viajes = (int)$gen['total_viajes'];
$total_bruto = (float)$gen['total_bruto'];
$total_neto = (float)$gen['total_neto'];
$total_peso = (float)$gen['total_peso'];

$avg_flete_bruto = $total_viajes > 0 ? ($total_bruto / $total_viajes) : 0;

// 2. Fuel Expenses
$q_fuel = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_fuel
    FROM expenses
    WHERE YEAR(date) = ? AND (category LIKE '%Combustible%' OR category LIKE '%ACPM%' OR category = 'Combustible')
");
$q_fuel->execute([$anio]);
$total_fuel = (float)$q_fuel->fetchColumn();

$fuel_ratio = $total_neto > 0 ? ($total_fuel / $total_neto * 100) : 0;

// 3. Top 5 Clients by Revenue
$q_clients = $pdo->prepare("
    SELECT 
        c.id,
        CASE WHEN c.person_type='Jurídica' THEN c.business_name
             ELSE CONCAT(c.firstname,' ',c.lastname1) END AS cliente_nombre,
        COUNT(t.id) AS viajes_count,
        COALESCE(SUM(t.flete_bruto), 0) AS flete_bruto_total
    FROM trips t
    JOIN clients c ON c.id = t.client_id
    WHERE YEAR(t.date_load) = ? AND t.status != 'Cancelado'
    GROUP BY t.client_id
    ORDER BY flete_bruto_total DESC
    LIMIT 5
");
$q_clients->execute([$anio]);
$top_clients = $q_clients->fetchAll(PDO::FETCH_ASSOC);

// 4. Top 5 Vehicles by Trips
$q_vehicles = $pdo->prepare("
    SELECT 
        v.placa,
        COUNT(t.id) AS viajes_count,
        COALESCE(SUM(t.flete_bruto), 0) AS flete_bruto_total
    FROM trips t
    JOIN vehicles v ON v.id = t.vehicle_id
    WHERE YEAR(t.date_load) = ? AND t.status != 'Cancelado'
    GROUP BY t.vehicle_id
    ORDER BY viajes_count DESC
    LIMIT 5
");
$q_vehicles->execute([$anio]);
$top_vehicles = $q_vehicles->fetchAll(PDO::FETCH_ASSOC);

// 5. Top 5 Drivers by Trips
$q_drivers = $pdo->prepare("
    SELECT 
        CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor_nombre,
        COUNT(t.id) AS viajes_count
    FROM trips t
    JOIN personnel p ON p.id = t.driver_id
    WHERE YEAR(t.date_load) = ? AND t.status != 'Cancelado'
    GROUP BY t.driver_id
    ORDER BY viajes_count DESC
    LIMIT 5
");
$q_drivers->execute([$anio]);
$top_drivers = $q_drivers->fetchAll(PDO::FETCH_ASSOC);

// Helper function to format money
function fmt($v) {
    return '$' . number_format((float)$v, 0, ',', '.');
}
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Encabezado -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Dashboard de KPIs e Indicadores</h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">
                Métricas de productividad de la flota, rendimiento financiero, eficiencia de combustible y top rankings.
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3 no-print">
            <button onclick="window.print()"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                       bg-gradient-to-r from-gray-600 to-gray-700 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
                🖨 Imprimir Indicadores
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end no-print">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1 font-semibold">Año de Análisis</label>
            <select name="anio" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $anio === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-brand-600 text-white text-sm rounded hover:bg-brand-700 font-medium">Filtrar</button>
            <a href="kpi_reportes.php" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 font-medium">Limpiar</a>
        </div>
    </form>

    <!-- Tarjetas de Métricas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 uppercase font-semibold">Productividad</p>
                <span class="text-blue-500 text-lg">🚚</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo $total_viajes; ?></p>
            <p class="text-xs text-gray-400 mt-1">Viajes realizados en total</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 uppercase font-semibold">Ingresos Brutos</p>
                <span class="text-emerald-500 text-lg">💵</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo fmt($total_bruto); ?></p>
            <p class="text-xs text-emerald-600 mt-1">Flete neto: <?php echo fmt($total_neto); ?></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 uppercase font-semibold">Flete Promedio / Viaje</p>
                <span class="text-indigo-500 text-lg">📈</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo fmt($avg_flete_bruto); ?></p>
            <p class="text-xs text-gray-400 mt-1">Flete bruto promedio por viaje</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 uppercase font-semibold">Carga Transportada</p>
                <span class="text-amber-500 text-lg">⚖️</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($total_peso, 1, ',', '.'); ?> TN</p>
            <p class="text-xs text-gray-400 mt-1">Toneladas totales cargadas</p>
        </div>
    </div>

    <!-- Indicador de Combustible -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 shadow-sm">
        <h3 class="font-bold text-gray-800 text-sm mb-4">Eficiencia de Combustible (Ratio Combustible / Flete Neto)</h3>
        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
            <span>Total Gasto Combustible: <?php echo fmt($total_fuel); ?></span>
            <span class="font-semibold text-gray-800"><?php echo number_format($fuel_ratio, 1, ',', '.'); ?>% de los ingresos</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden">
            <div class="bg-brand-600 h-4 rounded-full transition-all" style="width: <?php echo min(100, $fuel_ratio); ?>%"></div>
        </div>
        <p class="text-[10px] text-gray-400 mt-2">
            * Ratio ideal: por debajo de 35% del flete neto. Si el ratio supera el 40%, se recomienda auditar consumo y rutas.
        </p>
    </div>

    <!-- Listas de Rankings -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Top Clientes -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800 text-sm">Top 5 Clientes por Facturación</h3>
            </div>
            <div class="p-4">
                <?php if (empty($top_clients)): ?>
                    <p class="text-xs text-gray-400 text-center py-4">No hay datos de clientes registrados.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($top_clients as $index => $c): ?>
                            <div>
                                <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                                    <span><?php echo ($index+1) . '. ' . htmlspecialchars($c['cliente_nombre']); ?></span>
                                    <span><?php echo fmt($c['flete_bruto_total']); ?></span>
                                </div>
                                <div class="text-[10px] text-gray-400 mb-1"><?php echo $c['viajes_count']; ?> viajes</div>
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?php echo $total_bruto > 0 ? ($c['flete_bruto_total'] / $total_bruto * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Vehículos -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800 text-sm">Top 5 Vehículos por Viajes</h3>
            </div>
            <div class="p-4">
                <?php if (empty($top_vehicles)): ?>
                    <p class="text-xs text-gray-400 text-center py-4">No hay datos de vehículos.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($top_vehicles as $index => $v): ?>
                            <div>
                                <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                                    <span class="font-mono"><?php echo ($index+1) . '. ' . htmlspecialchars($v['placa']); ?></span>
                                    <span><?php echo $v['viajes_count']; ?> viajes</span>
                                </div>
                                <div class="text-[10px] text-gray-400 mb-1">Flete Bruto: <?php echo fmt($v['flete_bruto_total']); ?></div>
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?php echo $total_viajes > 0 ? ($v['viajes_count'] / $total_viajes * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Conductores -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800 text-sm">Top 5 Conductores Productivos</h3>
            </div>
            <div class="p-4">
                <?php if (empty($top_drivers)): ?>
                    <p class="text-xs text-gray-400 text-center py-4">No hay datos de conductores.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($top_drivers as $index => $d): ?>
                            <div>
                                <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                                    <span><?php echo ($index+1) . '. ' . htmlspecialchars($d['conductor_nombre']); ?></span>
                                    <span><?php echo $d['viajes_count']; ?> viajes</span>
                                </div>
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-purple-50 h-1.5 rounded-full" style="width: <?php echo $total_viajes > 0 ? ($d['viajes_count'] / $total_viajes * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
