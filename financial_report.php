<?php
// financial_report.php

require_once 'app/Controllers/ReportController.php';
include 'includes/functions.php';
include 'includes/header.php';

use App\Controllers\ReportController;

$controller = new ReportController();

// --- Filters ---
$year = $_GET['year'] ?? date('Y');
$vehicle_id = $_GET['vehicle_id'] ?? '';
$driver_id = $_GET['driver_id'] ?? '';

// Fetch Data via Controller
$reportData = $controller->getGeneralFinancialReport($year, $vehicle_id, $driver_id);

// Extract variables for view compatibility
$months = $reportData['months'];
$income = $reportData['income'];
$expenses_by_cat = $reportData['expenses_by_cat'];
$commissions = $reportData['commissions'];
$total_income = $reportData['total_income'];
$monthly_expenses_total = $reportData['monthly_expenses_total'];
$monthly_utility = $reportData['monthly_utility'];
$total_utility = $reportData['total_utility'];
$margin_total = $reportData['margin_total'];
$total_expenses_all = array_sum($monthly_expenses_total); // Calcuated here or could be passed

// Filter Options
$vehicles = $reportData['vehicles'];
$drivers = $reportData['drivers'];

function getMonthName($m)
{
    $names = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
    return $names[$m];
}
?>

<div class="max-w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte Financiero Mensual</h1>
            <div class="flex space-x-4 mt-2">
                <a href="financial_report.php"
                    class="text-sm font-bold text-brand-600 border-b-2 border-brand-600">General</a>
                <a href="route_profitability.php"
                    class="text-sm font-medium text-gray-500 hover:text-brand-600 transition-colors">Rentabilidad por
                    Ruta</a>
                <a href="vehicle_expenses_dashboard.php"
                    class="text-sm font-medium text-gray-500 hover:text-brand-600 transition-colors">Gastos por
                    Vehículo</a>
            </div>
        </div>

        <!-- Filters -->
        <form class="mt-4 md:mt-0 flex flex-wrap gap-2 items-end" method="GET">
            <div>
                <label class="block text-xs font-medium text-gray-700">Año</label>
                <select name="year"
                    class="mt-1 block w-24 py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                        echo "<option value='$y' " . ($year == $y ? 'selected' : '') . ">$y</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Vehículo</label>
                <select name="vehicle_id"
                    class="mt-1 block w-40 py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    <option value="">Todos</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo $vehicle_id == $v['id'] ? 'selected' : ''; ?>>
                            <?php echo $v['placa']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Conductor</label>
                <select name="driver_id"
                    class="mt-1 block w-40 py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    <option value="">Todos</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $driver_id == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo $d['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 shadow-sm">
                Filtrar
            </button>
        </form>
    </div>

    <!-- Report Table -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-3 py-3 text-left font-bold text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                                    Concepto
                                </th>
                                <?php foreach ($months as $m): ?>
                                    <th scope="col"
                                        class="px-2 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">
                                        <?php echo getMonthName($m); ?>
                                    </th>
                                <?php endforeach; ?>
                                <th scope="col"
                                    class="px-3 py-3 text-right font-bold text-gray-900 uppercase tracking-wider bg-gray-100">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Incomes -->
                            <tr class="bg-green-50">
                                <td
                                    class="px-3 py-4 whitespace-nowrap font-bold text-gray-900 sticky left-0 bg-green-50">
                                    INGRESOS (Fletes)
                                </td>
                                <?php foreach ($months as $m): ?>
                                    <td class="px-2 py-4 whitespace-nowrap text-right text-green-700 font-medium">
                                        <?php echo $income[$m] > 0 ? formatCurrency($income[$m]) : '-'; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td
                                    class="px-3 py-4 whitespace-nowrap text-right font-bold text-green-900 bg-green-100">
                                    <?php echo formatCurrency($total_income); ?>
                                </td>
                            </tr>

                            <!-- Expenses Header -->
                            <tr class="bg-gray-100">
                                <td colspan="14" class="px-3 py-2 text-left font-bold text-gray-700 uppercase">
                                    EGRESOS
                                </td>
                            </tr>

                            <!-- Expenses Rows -->
                            <?php foreach ($expenses_by_cat as $cat => $monthly_amounts):
                                $row_total = array_sum($monthly_amounts);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-600 sticky left-0 bg-white">
                                        <?php
                                        // Format Category Name
                                        $catName = ucfirst($cat);
                                        $catName = str_replace('_', ' ', $catName);
                                        $map = [
                                            'r_repuestos' => 'Repuestos',
                                            'mantenimiento_general' => 'Mantenimiento General',
                                            'combustible' => 'Combustible',
                                            'peajes' => 'Peajes',
                                            'cargue' => 'Cargue',
                                            'descargue' => 'Descargue',
                                            'lavado' => 'Lavado',
                                            'montaje_llantas' => 'Montaje Llantas',
                                            'bascula' => 'Báscula',
                                            'engrace' => 'Engrace',
                                            'comision' => 'Comisión',
                                            'parqueadero' => 'Parqueadero',
                                            'papeleria' => 'Papelería',
                                            'otros_viaje' => 'Otros Viaje',
                                            'otros' => 'Otros'
                                        ];
                                        if (isset($map[strtolower($cat)])) {
                                            $catName = $map[strtolower($cat)];
                                        }
                                        echo htmlspecialchars($catName);
                                        ?>
                                    </td>
                                    <?php foreach ($months as $m): ?>
                                        <td class="px-2 py-2 whitespace-nowrap text-right text-gray-500">
                                            <?php echo $monthly_amounts[$m] > 0 ? formatCurrency($monthly_amounts[$m]) : '-'; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-right font-medium text-gray-700 bg-gray-50">
                                        <?php echo formatCurrency($row_total); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Commissions -->
                            <tr class="bg-orange-50 hover:bg-orange-100">
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-orange-800 font-medium sticky left-0 bg-orange-50">
                                    Comisiones Conductores
                                </td>
                                <?php foreach ($months as $m): ?>
                                    <td class="px-2 py-2 whitespace-nowrap text-right text-orange-600">
                                        <?php echo $commissions[$m] > 0 ? formatCurrency($commissions[$m]) : '-'; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-right font-medium text-orange-800 bg-orange-100">
                                    <?php echo formatCurrency(array_sum($commissions)); ?>
                                </td>
                            </tr>

                            <!-- Total Expenses Row -->
                            <tr class="bg-red-50 font-bold">
                                <td class="px-3 py-4 whitespace-nowrap text-red-900 sticky left-0 bg-red-50">
                                    TOTAL EGRESOS
                                </td>
                                <?php foreach ($months as $m): ?>
                                    <td class="px-2 py-4 whitespace-nowrap text-right text-red-700">
                                        <?php echo $monthly_expenses_total[$m] > 0 ? formatCurrency($monthly_expenses_total[$m]) : '-'; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="px-3 py-4 whitespace-nowrap text-right text-red-900 bg-red-100">
                                    <?php echo formatCurrency($total_expenses_all); ?>
                                </td>
                            </tr>

                            <!-- Utility -->
                            <tr class="bg-blue-50 font-bold border-t-2 border-brand-200">
                                <td class="px-3 py-4 whitespace-nowrap text-brand-900 sticky left-0 bg-blue-50">
                                    UTILIDAD
                                </td>
                                <?php foreach ($months as $m): ?>
                                    <td
                                        class="px-2 py-4 whitespace-nowrap text-right <?php echo $monthly_utility[$m] >= 0 ? 'text-brand-700' : 'text-red-600'; ?>">
                                        <?php echo formatCurrency($monthly_utility[$m]); ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="px-3 py-4 whitespace-nowrap text-right text-brand-900 bg-blue-100">
                                    <?php echo formatCurrency($total_utility); ?>
                                </td>
                            </tr>

                            <!-- Margin -->
                            <tr class="bg-gray-50 text-xs text-gray-500">
                                <td class="px-3 py-2 whitespace-nowrap sticky left-0 bg-gray-50">
                                    MARGEN %
                                </td>
                                <?php foreach ($months as $m):
                                    $marg = ($income[$m] > 0) ? ($monthly_utility[$m] / $income[$m]) * 100 : 0;
                                    ?>
                                    <td class="px-2 py-2 whitespace-nowrap text-right">
                                        <?php echo number_format($marg, 1); ?>%
                                    </td>
                                <?php endforeach; ?>
                                <td class="px-3 py-2 whitespace-nowrap text-right bg-gray-100">
                                    <?php echo number_format($margin_total, 1); ?>%
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>