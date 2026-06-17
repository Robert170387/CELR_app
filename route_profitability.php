<?php
// route_profitability.php

require_once 'app/Controllers/ReportController.php';
include 'includes/functions.php';
include 'includes/db.php';
include 'includes/header.php';

use App\Controllers\ReportController;

$controller = new ReportController();
$year = $_GET['year'] ?? date('Y');
$state_id = $_GET['state_id'] ?? null;

$states = $pdo->query("SELECT id, name FROM loc_states ORDER BY name ASC")->fetchAll();
$routes = $controller->getRouteProfitability($year, $state_id);
?>

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Rentabilidad por Ruta</h1>
            <div class="flex space-x-4 mt-2">
                <a href="financial_report.php" class="text-sm font-medium text-gray-500 hover:text-brand-600 transition-colors">General</a>
                <a href="route_profitability.php" class="text-sm font-bold text-brand-600 border-b-2 border-brand-600">Rentabilidad por Ruta</a>
                <a href="vehicle_expenses_dashboard.php" class="text-sm font-medium text-gray-500 hover:text-brand-600 transition-colors">Gastos por Vehículo</a>
            </div>
        </div>

        <form class="mt-4 md:mt-0 flex items-end gap-3" method="GET">
            <div class="flex gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Año</label>
                    <select name="year" class="block w-24 py-2 px-3 border border-gray-200 bg-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm font-bold">
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 3; $y--) {
                            echo "<option value='$y' " . ($year == $y ? 'selected' : '') . ">$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Departamento</label>
                    <select name="state_id" class="block w-48 py-2 px-3 border border-gray-200 bg-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm font-bold">
                        <option value="">Todos</option>
                        <?php foreach($states as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $state_id == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg shadow-brand-200 hover:bg-brand-700 transition-all">
                Filtrar
            </button>
        </form>
    </div>

    <!-- Stats Row -->
    <?php if (!isset($routes['error'])): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <?php 
            $bestRoute = $routes[0] ?? null; 
            $totalIncome = array_sum(array_column($routes, 'total_income'));
            $totalUtility = array_sum(array_column($routes, 'net_utility'));
            $avgMargin = $totalIncome > 0 ? ($totalUtility / $totalIncome) * 100 : 0;
        ?>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Ingresos Totales</p>
            <h3 class="text-2xl font-black text-gray-900"><?php echo formatCurrency($totalIncome); ?></h3>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[10px] font-bold text-brand-600 uppercase tracking-widest mb-1">Utilidad Neta</p>
            <h3 class="text-2xl font-black text-brand-700"><?php echo formatCurrency($totalUtility); ?></h3>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1">Margen Promedio</p>
            <h3 class="text-2xl font-black text-emerald-700"><?php echo number_format($avgMargin, 1); ?>%</h3>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest mb-1">Ruta Top (Utilidad)</p>
            <h3 class="text-lg font-black text-gray-900 truncate"><?php echo $bestRoute ? $bestRoute['destination'] : 'N/A'; ?></h3>
        </div>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Ruta (Origen &rarr; Destino)</th>
                        <th class="px-6 py-4 text-center text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Viajes</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Ingresos</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Costos + Comis.</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Utilidad</th>
                        <th class="px-6 py-4 text-center text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Margen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (isset($routes['error'])): ?>
                        <tr><td colspan="6" class="p-10 text-center text-red-500 font-bold"><?php echo $routes['error']; ?></td></tr>
                    <?php elseif (empty($routes)): ?>
                        <tr><td colspan="6" class="p-20 text-center text-gray-400 italic">No hay datos de fletes para el periodo seleccionado.</td></tr>
                    <?php else: ?>
                        <?php foreach($routes as $r): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-5">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-xl bg-brand-50 text-brand-600 flex flex-col items-center justify-center mr-3 font-black text-[10px] uppercase">
                                        <span><?php echo substr($r['origin'], 0, 3); ?></span>
                                        <hr class="w-full border-brand-100">
                                        <span><?php echo substr($r['destination'], 0, 3); ?></span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-gray-900"><?php echo htmlspecialchars($r['origin']); ?> &rarr; <?php echo htmlspecialchars($r['destination']); ?></div>
                                        <div class="text-[9px] text-brand-600 font-bold uppercase tracking-widest">
                                            <?php echo htmlspecialchars($r['origin_state'] ?: '-'); ?> &rarr; <?php echo htmlspecialchars($r['dest_state'] ?: '-'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="px-3 py-1 bg-gray-100 rounded-full text-xs font-black text-gray-600"><?php echo $r['trip_count']; ?></span>
                            </td>
                            <td class="px-6 py-5 text-right font-bold text-gray-900">
                                <?php echo formatCurrency($r['total_income']); ?>
                            </td>
                            <td class="px-6 py-5 text-right text-gray-500 text-sm font-medium">
                                <?php echo formatCurrency($r['total_expenses'] + $r['total_commissions']); ?>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="text-sm font-black <?php echo $r['net_utility'] >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                    <?php echo formatCurrency($r['net_utility']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center justify-center space-x-2">
                                    <div class="w-16 bg-gray-100 rounded-full h-1.5 overflow-hidden hidden sm:block">
                                        <?php 
                                            $width = max(0, min(100, $r['margin']));
                                            $color = ($r['margin'] > 25) ? 'bg-emerald-500' : (($r['margin'] > 10) ? 'bg-brand-500' : 'bg-rose-500');
                                        ?>
                                        <div class="<?php echo $color; ?> h-full" style="width: <?php echo $width; ?>%"></div>
                                    </div>
                                    <span class="text-xs font-black <?php echo ($r['margin'] > 10) ? 'text-gray-900' : 'text-rose-600'; ?>">
                                        <?php echo number_format($r['margin'], 1); ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
