<?php
// vehicle_expenses_dashboard.php

require_once 'app/Controllers/ReportController.php';
include 'includes/functions.php';
include 'includes/header.php';

use App\Controllers\ReportController;

$controller = new ReportController();
$selected_vehicle_id = $_GET['vehicle_id'] ?? null;

$metrics = $controller->getVehicleExpenseMetrics($selected_vehicle_id);

if ($selected_vehicle_id) {
    if (isset($metrics['error'])) {
        die("Error loading metrics: " . $metrics['error']);
    }
    // Extract variables for view compatibility
    $vehicle_detail = $metrics['detail'];
    $monthly_trend = $metrics['monthly_trend'];
    $category_breakdown = $metrics['category_breakdown'];
    $recent_expenses = $metrics['recent_expenses'];
    $vehicle_totals = $metrics['totals'];

    // For list button
    // We need list to pass back to 'Volver a lista' if needed, but actually the clear link just removes arg
} else {
    $vehicles = $metrics; // Is list of all vehicles
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📊 Dashboard de Gastos por Vehículo</h1>
            <p class="mt-2 text-sm text-gray-700">Análisis detallado de costos operativos por vehículo</p>
        </div>
        <?php if ($selected_vehicle_id): ?>
            <a href="vehicle_expenses_dashboard.php"
                class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Volver a lista
            </a>
        <?php endif; ?>
    </div>

    <?php if (!$selected_vehicle_id): ?>
        <!-- Vehicle List View -->
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($vehicles as $vehicle): ?>
                <?php
                $cost_per_km = $vehicle['total_kms'] > 0 ? $vehicle['total_expenses'] / $vehicle['total_kms'] : 0;
                ?>
                <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow cursor-pointer"
                    onclick="window.location.href='vehicle_expenses_dashboard.php?vehicle_id=<?php echo $vehicle['id']; ?>'">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">
                                <?php echo htmlspecialchars($vehicle['placa']); ?>
                            </h3>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Activo
                            </span>
                        </div>

                        <dl class="mt-5 grid grid-cols-1 gap-3">
                            <div class="bg-gradient-to-r from-red-50 to-red-100 px-4 py-3 rounded-lg">
                                <dt class="text-xs font-medium text-red-600 uppercase">Total Gastos</dt>
                                <dd class="mt-1 text-2xl font-bold text-red-700">
                                    <?php echo formatCurrency($vehicle['total_expenses']); ?>
                                </dd>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-blue-50 px-3 py-2 rounded">
                                    <dt class="text-xs font-medium text-blue-600">Conductor</dt>
                                    <dd class="mt-1 text-sm font-bold text-blue-700">
                                        <?php echo formatCurrency($vehicle['conductor_expenses']); ?>
                                    </dd>
                                </div>
                                <div class="bg-purple-50 px-3 py-2 rounded">
                                    <dt class="text-xs font-medium text-purple-600">Propietario</dt>
                                    <dd class="mt-1 text-sm font-bold text-purple-700">
                                        <?php echo formatCurrency($vehicle['owner_expenses']); ?>
                                    </dd>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-4 py-3 rounded-lg">
                                <dt class="text-xs font-medium text-gray-600">Costo por Km</dt>
                                <dd class="mt-1 text-lg font-bold text-gray-900">
                                    <?php echo $cost_per_km > 0 ? formatCurrency($cost_per_km) : 'N/A'; ?>
                                </dd>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo number_format($vehicle['total_kms'], 0); ?> km recorridos
                                </p>
                            </div>

                            <div class="text-center pt-2">
                                <span class="text-xs text-gray-500">
                                    <?php echo $vehicle['expense_count']; ?> gastos registrados
                                </span>
                            </div>
                        </dl>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Vehicle Detail View -->
        <?php if ($vehicle_detail): ?>
            <div class="mt-8">
                <!-- Vehicle Header -->
                <div class="bg-gradient-to-r from-brand-600 to-brand-700 shadow-lg rounded-lg px-6 py-8 text-white">
                    <h2 class="text-3xl font-bold">
                        <?php echo htmlspecialchars($vehicle_detail['placa']); ?>
                    </h2>
                    <?php if (!empty($vehicle_detail['brand']) || !empty($vehicle_detail['model'])): ?>
                        <p class="mt-2 text-brand-100">
                            <?php
                            $details = [];
                            if (!empty($vehicle_detail['brand']))
                                $details[] = $vehicle_detail['brand'];
                            if (!empty($vehicle_detail['model']))
                                $details[] = $vehicle_detail['model'];
                            echo htmlspecialchars(implode(' ', $details));
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Summary Cards -->
                <?php
                // Logic moved to controller, $vehicle_totals available from $metrics['totals']
                $cost_per_km = $vehicle_totals && $vehicle_totals['total_kms'] > 0
                    ? $vehicle_totals['total_expenses'] / $vehicle_totals['total_kms']
                    : 0;
                ?>
                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Gastos</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900">
                                <?php echo formatCurrency($vehicle_totals['total_expenses'] ?? 0); ?>
                            </dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Gastos Conductor</dt>
                            <dd class="mt-1 text-3xl font-semibold text-blue-600">
                                <?php echo formatCurrency($vehicle_totals['conductor_expenses'] ?? 0); ?>
                            </dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Gastos Propietario</dt>
                            <dd class="mt-1 text-3xl font-semibold text-purple-600">
                                <?php echo formatCurrency($vehicle_totals['owner_expenses'] ?? 0); ?>
                            </dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Costo por Km</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900">
                                <?php echo $cost_per_km > 0 ? formatCurrency($cost_per_km) : 'N/A'; ?>
                            </dd>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo number_format($vehicle_totals['total_kms'] ?? 0, 0); ?> km
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Monthly Trend Chart -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">📈 Tendencia Mensual (Últimos 6 meses)</h3>
                        <div style="position: relative; height: 300px;">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Category Breakdown Chart -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">🎯 Distribución por Categoría</h3>
                        <div style="position: relative; height: 300px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Category Table -->
                <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Desglose por Categoría</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">% del Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($category_breakdown as $cat): ?>
                                <?php $percentage = $vehicle_totals['total_expenses'] > 0 ? ($cat['total'] / $vehicle_totals['total_expenses']) * 100 : 0; ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($cat['category_name'] ?? ucfirst(str_replace('_', ' ', $cat['category']))); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $cat['count']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        <?php echo formatCurrency($cat['total']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <span class="mr-2">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                                <div class="bg-brand-600 h-2 rounded-full"
                                                    style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Expenses -->
                <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Gastos Recientes</h3>
                        <a href="expenses.php?vehicle_id=<?php echo $selected_vehicle_id; ?>"
                            class="text-sm text-brand-600 hover:text-brand-900">Ver todos →</a>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pagado por</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_expenses as $exp): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $exp['date']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($exp['category_name'] ?? ucfirst(str_replace('_', ' ', $exp['category']))); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">
                                        <?php echo htmlspecialchars($exp['description'] ?? '-'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2 py-1 text-xs font-semibold 
                                                     <?php echo ($exp['paid_by'] ?? 'Conductor') === 'Conductor' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo htmlspecialchars($exp['paid_by'] ?? 'Conductor'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">
                                        <?php echo formatCurrency($exp['amount']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chart.js Scripts -->
            <script>
                // Wait for DOM and Chart.js to be ready
                window.addEventListener('load', function () {
                    console.log('Initializing charts...');

                    // Check if Chart is available
                    if (typeof Chart === 'undefined') {
                        console.error('Chart.js is not loaded!');
                        return;
                    }

                    // Monthly Trend Chart
                    try {
                        const monthlyCanvas = document.getElementById('monthlyTrendChart');
                        if (!monthlyCanvas) {
                            console.error('Monthly chart canvas not found');
                        } else {
                            const monthlyCtx = monthlyCanvas.getContext('2d');
                            const monthlyLabels = <?php echo json_encode(array_column($monthly_trend, 'month_label')); ?>;
                            const monthlyData = <?php echo json_encode(array_column($monthly_trend, 'total')); ?>;

                            console.log('Monthly data:', monthlyLabels, monthlyData);

                            new Chart(monthlyCtx, {
                                type: 'line',
                                data: {
                                    labels: monthlyLabels.length > 0 ? monthlyLabels : ['Sin datos'],
                                    datasets: [{
                                        label: 'Gastos Mensuales',
                                        data: monthlyData.length > 0 ? monthlyData : [0],
                                        borderColor: 'rgb(37, 99, 235)',
                                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                        tension: 0.4,
                                        fill: true,
                                        borderWidth: 2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function (context) {
                                                    return '$' + context.parsed.y.toLocaleString();
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function (value) {
                                                    return '$' + value.toLocaleString();
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                            console.log('Monthly chart created successfully');
                        }
                    } catch (error) {
                        console.error('Error creating monthly chart:', error);
                    }

                    // Category Chart
                    try {
                        const categoryCanvas = document.getElementById('categoryChart');
                        if (!categoryCanvas) {
                            console.error('Category chart canvas not found');
                        } else {
                            const categoryCtx = categoryCanvas.getContext('2d');
                            const categoryLabels = <?php echo json_encode(array_map(fn($c) => $c['category_name'] ?? ucfirst(str_replace('_', ' ', $c['category'])), $category_breakdown)); ?>;
                            const categoryData = <?php echo json_encode(array_column($category_breakdown, 'total')); ?>;

                            console.log('Category data:', categoryLabels, categoryData);

                            new Chart(categoryCtx, {
                                type: 'doughnut',
                                data: {
                                    labels: categoryLabels.length > 0 ? categoryLabels : ['Sin datos'],
                                    datasets: [{
                                        data: categoryData.length > 0 ? categoryData : [1],
                                        backgroundColor: [
                                            'rgb(239, 68, 68)',
                                            'rgb(59, 130, 246)',
                                            'rgb(168, 85, 247)',
                                            'rgb(34, 197, 94)',
                                            'rgb(251, 146, 60)',
                                            'rgb(236, 72, 153)',
                                            'rgb(14, 165, 233)',
                                            'rgb(132, 204, 22)'
                                        ],
                                        borderWidth: 2,
                                        borderColor: '#fff'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            position: 'right',
                                            labels: {
                                                boxWidth: 15,
                                                padding: 10
                                            }
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function (context) {
                                                    const label = context.label || '';
                                                    const value = '$' + context.parsed.toLocaleString();
                                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                                    return label + ': ' + value + ' (' + percentage + '%)';
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                            console.log('Category chart created successfully');
                        }
                    } catch (error) {
                        console.error('Error creating category chart:', error);
                    }
                });
            </script>

        <?php else: ?>
            <div class="mt-8 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-700">Vehículo no encontrado.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>