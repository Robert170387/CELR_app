<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Fetch all statistics in a single query using subqueries
$statsQuery = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM trips WHERE status = 'En Progreso') as trip_count,
        (SELECT COUNT(*) FROM vehicles) as vehicle_count,
        (SELECT COUNT(*) FROM personnel WHERE type='Conductor' AND active=1) as driver_count,
        (SELECT COUNT(*) FROM clients WHERE active=1) as client_count
");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);
$tripCount = $stats['trip_count'];
$vehicleCount = $stats['vehicle_count'];
$driverCount = $stats['driver_count'];
$clientCount = $stats['client_count'];

// Financials (Current Month) - Combined into single query
$currentMonth = date('Y-m');
$financialStmt = $pdo->prepare("
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = ?) as expenses_total,
        (SELECT COALESCE(SUM(flete_neto), 0) FROM trips WHERE DATE_FORMAT(date_load, '%Y-%m') = ?) as income_total,
        (SELECT COALESCE(SUM(commission_value), 0) FROM trips WHERE DATE_FORMAT(date_load, '%Y-%m') = ?) as commissions_total
");
$financialStmt->execute([$currentMonth, $currentMonth, $currentMonth]);
$financials = $financialStmt->fetch(PDO::FETCH_ASSOC);
$expensesTotal = $financials['expenses_total'];
$incomeTotal = $financials['income_total'];
$commissionsTotal = $financials['commissions_total'];
$utilityTotal = $incomeTotal - ($expensesTotal + $commissionsTotal);

// Money on Street - Optimized with JOIN instead of correlated subquery
$moneyOnStreet = $pdo->query("
    SELECT SUM(
        (t.advance_owner + (CASE WHEN t.advance_manifest_to_driver = 1 THEN t.advance_manifest ELSE 0 END)) - 
        IFNULL(driver_expenses.total, 0)
    )
    FROM trips t 
    LEFT JOIN (
        SELECT trip_id, SUM(amount) as total 
        FROM expenses 
        WHERE paid_by = 'Conductor'
        GROUP BY trip_id
    ) driver_expenses ON t.id = driver_expenses.trip_id
    WHERE t.settlement_status = 'Pending'
")->fetchColumn() ?: 0;

// Accounts Receivable (Cartera Pendiente de Clientes) - Optimized with JOIN
$pendingAR = $pdo->query("
    SELECT SUM(t.final_pay_expected - IFNULL(payments.total_paid, 0))
    FROM trips t
    LEFT JOIN (
        SELECT trip_id, SUM(amount) as total_paid 
        FROM trip_payments 
        GROUP BY trip_id
    ) payments ON t.id = payments.trip_id
    WHERE t.final_pay_expected > 0
")->fetchColumn() ?: 0;

// Trips with Balance - Optimized with single LEFT JOIN for payments
$tripsWithBalance = $pdo->query("
    SELECT 
        t.id, 
        t.date_load, 
        t.origin, 
        t.destination, 
        t.final_pay_expected,
        IFNULL(payments.total_paid, 0) as paid_amount,
        (t.final_pay_expected - IFNULL(payments.total_paid, 0)) as balance_pending,
        CASE 
            WHEN c.person_type = 'Jurídica' THEN c.business_name 
            ELSE CONCAT(c.firstname, ' ', c.lastname1) 
        END as client_name
    FROM trips t
    LEFT JOIN clients c ON t.client_id = c.id
    LEFT JOIN (
        SELECT trip_id, SUM(amount) as total_paid 
        FROM trip_payments 
        GROUP BY trip_id
    ) payments ON t.id = payments.trip_id
    HAVING balance_pending > 1000
    ORDER BY balance_pending DESC
    LIMIT 6
")->fetchAll();

// Recent Trips - Already optimized (uses JOINs)
$recentTrips = $pdo->query("
    SELECT t.*, v.placa, CONCAT(p.firstname, ' ', IFNULL(p.lastname, '')) as driver_name 
    FROM trips t
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN personnel p ON t.driver_id = p.id
    ORDER BY t.created_at DESC
    LIMIT 6
")->fetchAll();

// Alerts
require_once 'app/Controllers/VehicleController.php';
$vehicleCtrl = new \App\Controllers\VehicleController();
$maintenanceAlerts = $vehicleCtrl->getSystemMaintenanceAlerts();
$allAlerts = array_merge(getUpcomingAlerts(30), $maintenanceAlerts, getOverdueCollectionAlerts());

// Vehicle Performance (Accumulated History) - Already optimized (uses LEFT JOINs with subqueries)
$vehiclePerformance = $pdo->query("
    SELECT 
        v.id, 
        v.placa,
        IFNULL(income_data.total_income, 0) as income,
        IFNULL(expense_data.total_expenses, 0) + IFNULL(income_data.total_commissions, 0) as expenses,
        IFNULL(income_data.total_income, 0) - (IFNULL(expense_data.total_expenses, 0) + IFNULL(income_data.total_commissions, 0)) as utility
    FROM vehicles v
    LEFT JOIN (
        SELECT vehicle_id, SUM(flete_neto) as total_income, SUM(commission_value) as total_commissions
        FROM trips
        GROUP BY vehicle_id
    ) income_data ON v.id = income_data.vehicle_id
    LEFT JOIN (
        SELECT vehicle_id, SUM(amount) as total_expenses
        FROM expenses
        GROUP BY vehicle_id
    ) expense_data ON v.id = expense_data.vehicle_id
    WHERE income_data.total_income IS NOT NULL OR expense_data.total_expenses IS NOT NULL
    ORDER BY utility DESC
")->fetchAll();

// Driver Performance Query (Yearly Breakdown) - Optimized with JOIN instead of correlated subquery
$driverPerformance = $pdo->query("
    SELECT 
        p.id as driver_id,
        CONCAT(p.firstname, ' ', IFNULL(p.lastname, '')) as driver_name,
        t_years.trip_year,
        IFNULL(trip_stats.total_trips, 0) as total_trips,
        IFNULL(trip_stats.urbanos, 0) as urbanos,
        IFNULL(trip_stats.nacionales, 0) as nacionales,
        IFNULL(trip_stats.internacionales, 0) as internacionales,
        IFNULL(trip_stats.total_income, 0) as total_income,
        IFNULL(trip_stats.total_commissions, 0) as total_commissions,
        IFNULL(driver_expenses.total_expenses, 0) as total_expenses
    FROM personnel p
    CROSS JOIN (
        SELECT DISTINCT DATE_FORMAT(date_load, '%Y') as trip_year
        FROM trips 
        WHERE date_load IS NOT NULL
    ) t_years
    LEFT JOIN (
        SELECT 
            driver_id,
            DATE_FORMAT(date_load, '%Y') as trip_year,
            COUNT(*) as total_trips,
            SUM(CASE WHEN trip_type = 'urbano' THEN 1 ELSE 0 END) as urbanos,
            SUM(CASE WHEN trip_type = 'nacional' THEN 1 ELSE 0 END) as nacionales,
            SUM(CASE WHEN trip_type = 'internacional' THEN 1 ELSE 0 END) as internacionales,
            SUM(flete_neto) as total_income,
            SUM(commission_value) as total_commissions
        FROM trips
        WHERE date_load IS NOT NULL
        GROUP BY driver_id, DATE_FORMAT(date_load, '%Y')
    ) trip_stats ON p.id = trip_stats.driver_id AND t_years.trip_year = trip_stats.trip_year
    LEFT JOIN (
        SELECT 
            t3.driver_id,
            DATE_FORMAT(t3.date_load, '%Y') as trip_year,
            SUM(e.amount) as total_expenses
        FROM expenses e
        JOIN trips t3 ON e.trip_id = t3.id
        GROUP BY t3.driver_id, DATE_FORMAT(t3.date_load, '%Y')
    ) driver_expenses ON p.id = driver_expenses.driver_id AND t_years.trip_year = driver_expenses.trip_year
    WHERE p.type = 'Conductor'
    AND (trip_stats.total_trips IS NOT NULL OR driver_expenses.total_expenses IS NOT NULL)
    ORDER BY t_years.trip_year DESC, IFNULL(trip_stats.total_income, 0) DESC
")->fetchAll();

$systemMetrics = getSystemHealthMetrics();

if (!function_exists('formatMonth')) {
    function formatMonth($monthStr)
    {
        if (!$monthStr)
            return '-';
        $months = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre'
        ];
        $parts = explode('-', $monthStr);
        if (count($parts) < 2)
            return $monthStr;
        return ($months[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
    }
}
?>

<div class="max-w-7xl mx-auto">
    <!-- Statistics Header -->
    <div class="mb-10">
        <h1 class="text-4xl font-bold text-slate-950 tracking-tight mb-2">Resumen Ejecutivo</h1>
        <p class="text-lg font-bold text-slate-700">Panel principal de control y métricas clave.</p>
    </div>

    

    <!-- Recent System Alerts -->
    <div class="mb-12" id="system-alerts-container" style="display: none;">
        <h2 class="text-2xl font-bold text-slate-950 tracking-tight flex items-center mb-6">
            <span class="w-1.5 h-7 bg-red-600 rounded-full mr-4"></span>
            Avisos Recientes y Notificaciones
        </h2>
        <div id="alerts-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Alerts loaded via JS -->
        </div>
    </div>

    <!-- Main Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <!-- Stat: Ingresos Mes -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-emerald-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Ingresos</p>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">
                        <?php echo formatCurrency($incomeTotal); ?>
                    </h3>
                </div>
                <div
                    class="p-3 bg-emerald-500 text-white rounded-xl shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 mr-2"></span>
                    <p class="text-xs font-medium text-slate-400">Proyección del mes actual</p>
                </div>
                <a href="financial_report.php" class="text-[10px] font-bold text-emerald-600 hover:underline">Ver
                    Reporte
                    &rarr;</a>
            </div>
        </div>

        <!-- Stat: Gastos Mes -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-rose-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs font-bold text-rose-500 uppercase tracking-widest mb-1">Egresos</p>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">
                        <?php echo formatCurrency($expensesTotal + $commissionsTotal); ?>
                    </h3>
                </div>
                <div
                    class="p-3 bg-rose-500 text-white rounded-xl shadow-lg shadow-rose-500/20 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <span class="flex h-2 w-2 rounded-full bg-rose-500 mr-2"></span>
                    <p class="text-xs font-medium text-slate-400">Gastos Ops. + Comisiones</p>
                </div>
                <a href="expenses.php" class="text-[10px] font-bold text-rose-600 hover:underline">Gestionar Gastos
                    &rarr;</a>
            </div>
        </div>

        <!-- Stat: Utilidad Mes -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-indigo-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-1">Utilidad Neta</p>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">
                        <?php echo formatCurrency($utilityTotal); ?>
                    </h3>
                </div>
                <div
                    class="p-3 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-600/20 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <span class="flex h-2 w-2 rounded-full bg-indigo-600 mr-2"></span>
                    <p class="text-xs font-medium text-slate-400">Balance final mensual</p>
                </div>
                <a href="financial_report.php" class="text-[10px] font-bold text-indigo-600 hover:underline">Ver
                    Analítica
                    &rarr;</a>
            </div>
        </div>

        <!-- Stat: Dinero Calle -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-amber-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs font-bold text-amber-500 uppercase tracking-widest mb-1">Por Legalizar</p>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">
                        <?php echo formatCurrency($moneyOnStreet); ?>
                    </h3>
                </div>
                <div
                    class="p-3 bg-amber-500 text-white rounded-xl shadow-lg shadow-amber-500/20 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <span class="flex h-2 w-2 rounded-full bg-amber-500 mr-2"></span>
                    <p class="text-xs font-medium text-slate-400">Dinero en manos de terceros</p>
                </div>
                <a href="settlements.php" class="text-[10px] font-bold text-amber-600 hover:underline">Liquidar
                    &rarr;</a>
            </div>
        </div>

        <!-- Stat: Viajes Activos -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-violet-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs font-bold text-violet-600 uppercase tracking-widest mb-1">En Ruta</p>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight"><?php echo $tripCount; ?></h3>
                </div>
                <div
                    class="p-3 bg-violet-600 text-white rounded-xl shadow-lg shadow-violet-600/20 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <span class="flex h-2 w-2 rounded-full bg-violet-600 mr-2"></span>
                    <p class="text-xs font-medium text-slate-400">Viajes activos actualmente</p>
                </div>
                <a href="trips.php?status=En+Progreso" class="text-[10px] font-bold text-violet-600 hover:underline">Ver
                    Mapa &rarr;</a>
            </div>
        </div>

        <!-- Stat: Clientes -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-slate-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Clientes</p>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight"><?php echo $clientCount; ?></h3>
                </div>
                <div
                    class="p-3 bg-slate-800 text-white rounded-xl shadow-lg shadow-slate-800/20 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <span class="flex h-2 w-2 rounded-full bg-slate-800 mr-2"></span>
                    <p class="text-xs font-medium text-slate-400">Total convenios activos</p>
                </div>
                <a href="clients.php" class="text-[10px] font-bold text-slate-600 hover:underline">Ir a Clientes
                    &rarr;</a>
            </div>
        </div>

        <!-- Stat: Cartera -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-blue-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-1">Cartera AR</p>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">
                        <?php echo formatCurrency($pendingAR); ?>
                    </h3>
                </div>
                <div
                    class="p-3 bg-blue-600 text-white rounded-xl shadow-lg shadow-blue-600/20 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <span class="flex h-2 w-2 rounded-full bg-blue-600 mr-2"></span>
                    <p class="text-xs font-medium text-slate-400">Saldo pendiente por cobrar</p>
                </div>
                <a href="trips.php" class="text-[10px] font-bold text-blue-600 hover:underline">Detalle Cartera
                    &rarr;</a>
            </div>
        </div>

        <!-- Stat: Salud del Sistema -->
        <div
            class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div
                class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 <?php echo $systemMetrics['recent_errors'] > 0 ? 'bg-red-50' : 'bg-emerald-50'; ?> rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative flex justify-between items-start mb-4">
                <div>
                    <p
                        class="text-xs font-bold <?php echo $systemMetrics['recent_errors'] > 0 ? 'text-red-600' : 'text-emerald-600'; ?> uppercase tracking-widest mb-1">
                        Salud del Sistema</p>
                    <div class="space-y-1">
                        <h3 class="text-xl font-black text-slate-900 tracking-tight flex items-center">
                            <span
                                class="w-1.5 h-1.5 rounded-full <?php echo $systemMetrics['recent_errors'] > 0 ? 'bg-red-500' : 'bg-emerald-500'; ?> mr-2"></span>
                            Errores (24h): <?php echo $systemMetrics['recent_errors']; ?>
                        </h3>

                        <!-- Async Migration Status Container -->
                        <div id="migration-status-container" class="min-h-[40px]">
                            <!-- Content loaded via JS -->
                        </div>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <div class="mt-2 flex flex-wrap gap-2" id="backup-action-container">
                                <button id="btn-generate-backup"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold rounded-lg shadow-sm transition-all active:scale-95 disabled:opacity-50">
                                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    GENERAR RESPALDO AHORA
                                </button>
                                <a href="api.php?action=downloadLatestBackup"
                                    class="inline-flex items-center px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-[10px] font-bold rounded-lg shadow-sm transition-all active:scale-95">
                                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    DESCARGAR ÚLTIMO
                                </a>
                            </div>
                        <?php endif; ?>

                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Último Respaldo:<br>
                            <span
                                class="text-slate-900"><?php echo $systemMetrics['last_backup'] ? date('d M, Y H:i', strtotime($systemMetrics['last_backup'])) : 'Nunca'; ?></span>
                        </p>
                    </div>
                </div>
                <div
                    class="p-3 <?php echo $systemMetrics['recent_errors'] > 0 ? 'bg-red-500' : 'bg-emerald-600'; ?> text-white rounded-xl shadow-lg transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
            <div class="relative flex items-center mt-2">
                <p class="text-[10px] font-medium text-slate-400">Integridad de base de datos y respaldos</p>
                <?php if ($systemMetrics['recent_errors'] > 0): ?>
                    <a href="audit_report.php?entity_type=SYSTEM&action=ERROR"
                        class="ml-auto text-[10px] font-bold text-red-600 hover:underline">Ver Errores &rarr;</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin-Only Audit Widget -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <div
                class="group relative bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div
                    class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-indigo-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity">
                </div>
                <div class="relative flex justify-between items-start mb-2 p-6 pb-2">
                    <div>
                        <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-1">
                            Actividad de Seguridad</p>
                        <div class="space-y-1">
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">
                                Auditoría Reciente
                            </h3>
                        </div>
                    </div>
                    <div class="p-3 bg-indigo-600 text-white rounded-xl shadow-lg transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                </div>

                <!-- JS Container -->
                <div id="audit-activity-container" class="relative">
                    <!-- Loaded via dashboard_audit.js -->
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vehicle Performance Section -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-950 tracking-tight flex items-center">
                <span class="w-1.5 h-7 bg-indigo-600 rounded-full mr-4"></span>
                Rendimiento Acumulado por Vehículo
            </h2>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Vehículo</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Ingresos</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Egresos Total</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Utilidad Neta</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if (empty($vehiclePerformance)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No hay historial
                                    financiero
                                    registrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehiclePerformance as $v): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="vehicle_details.php?id=<?php echo $v['id']; ?>"
                                            class="font-bold text-slate-900 border-b-2 border-indigo-100 hover:text-indigo-600"><?php echo htmlspecialchars($v['placa']); ?></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-emerald-600 font-bold">
                                        <?php echo formatCurrency($v['income']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-red-600 font-bold">
                                        <?php echo formatCurrency($v['expenses']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo $v['utility'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo formatCurrency($v['utility']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Driver Performance Section -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-950 tracking-tight flex items-center">
                <span class="w-1.5 h-7 bg-emerald-600 rounded-full mr-4"></span>
                Rendimiento por Conductor (Anual)
            </h2>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Conductor</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Año</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Viajes</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Utilidad Neta</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if (empty($driverPerformance)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No hay datos de
                                    rendimiento
                                    disponibles.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($driverPerformance as $dp):
                                $utility = $dp['total_income'] - $dp['total_expenses'] - $dp['total_commissions'];
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="personnel_details.php?id=<?php echo $dp['driver_id']; ?>"
                                            class="font-bold text-slate-900 hover:text-emerald-600"><?php echo htmlspecialchars($dp['driver_name']); ?></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-bold">
                                        <?php echo $dp['trip_year']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center space-x-2">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100"
                                                title="Urbanos">
                                                U: <?php echo $dp['urbanos']; ?>
                                            </span>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100"
                                                title="Nacionales">
                                                N: <?php echo $dp['nacionales']; ?>
                                            </span>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100"
                                                title="Internacionales">
                                                I: <?php echo $dp['internacionales']; ?>
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-bold mt-1 tracking-wider uppercase">Total:
                                            <?php echo $dp['total_trips']; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span
                                            class="text-sm font-bold <?php echo $utility >= 0 ? 'text-emerald-600' : 'text-red-600'; ?>">
                                            <?php echo formatCurrency($utility); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Client Accounts Receivable Section -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-950 tracking-tight flex items-center">
                <span class="w-1.5 h-7 bg-blue-600 rounded-full mr-4"></span>
                Cartera Pendiente de Clientes
            </h2>
            <a href="trips.php" class="text-sm font-bold text-blue-600 hover:text-blue-800">Ver Cartera Completa
                &rarr;</a>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                ODT /
                                Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Cliente / Ruta</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Vlr.
                                Flete</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Saldo
                                Pendiente</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Acción</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if (empty($tripsWithBalance)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400 italic font-medium">No hay
                                    cobros
                                    pendientes registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tripsWithBalance as $t): ?>
                                <tr class="hover:bg-blue-50/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-black text-slate-900">ODT-<?php echo $t['id']; ?></div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase">
                                            <?php echo date('d M, Y', strtotime($t['date_load'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-slate-800 truncate max-w-[200px]">
                                            <?php echo htmlspecialchars($t['client_name']); ?>
                                        </div>
                                        <div class="text-xs text-slate-500 italic"><?php echo htmlspecialchars($t['origin']); ?>
                                            &rarr; <?php echo htmlspecialchars($t['destination']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold text-slate-600">
                                        <?php echo formatCurrency($t['final_pay_expected']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-base font-black text-red-600">
                                            <?php echo formatCurrency($t['balance_pending']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <a href="trip_details.php?id=<?php echo $t['id']; ?>"
                                            class="inline-flex items-center px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-blue-300 transition-all">
                                            Ver / Cobrar
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

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
        <!-- Recent Activity Section -->
        <div class="xl:col-span-2">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-950 tracking-tight flex items-center">
                    <span class="w-1.5 h-7 bg-brand-600 rounded-full mr-4"></span>
                    Actividad Reciente
                </h2>
                <a href="trips.php"
                    class="text-sm font-semibold text-brand-600 hover:text-brand-800 flex items-center group">
                    Ver todo el historial
                    <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3">
                        </path>
                    </svg>
                </a>
            </div>

            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th>Vehículo</th>
                            <th>Operación</th>
                            <th>Estado</th>
                            <th class="text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTrips)): ?>
                            <tr>
                                <td colspan="4" class="py-20 text-center text-slate-400 font-semibold italic">No hay
                                    actividad
                                    reciente.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTrips as $trip): ?>
                                <tr class="group hover:bg-slate-50 transition-colors">
                                    <td>
                                        <div>
                                            <p class="font-bold text-slate-900 text-sm leading-none mb-1">
                                                <?php echo htmlspecialchars($trip['placa']); ?>
                                            </p>
                                            <p class="text-[11px] font-semibold text-slate-400 capitalize">
                                                <?php echo htmlspecialchars($trip['driver_name']); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($trip['destination']); ?></span>
                                            <span class="text-xs text-slate-400 font-medium italic">De:
                                                <?php echo htmlspecialchars($trip['origin']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($trip['status']) {
                                            'En Progreso' => 'bg-amber-100 text-amber-700 border-amber-200',
                                            'Entregado' => 'bg-blue-100 text-blue-700 border-blue-200',
                                            'Finalizado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200'
                                        };
                                        ?>
                                        <span class="badge-saas <?php echo $statusClass; ?> border px-3 py-1 text-[10px]">
                                            <?php echo $trip['status']; ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="trip_details.php?id=<?php echo $trip['id']; ?>"
                                            class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-slate-50 text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-all">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alerts Section -->
        <div class="xl:col-span-1">
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center mb-6">
                <span class="w-2.5 h-8 bg-red-600 rounded-full mr-4"></span>
                Alertas Críticas
            </h2>

            <div class="space-y-6">
                <?php if (empty($allAlerts)): ?>
                    <div class="glass-card p-10 text-center bg-emerald-50/50 border-emerald-100 border-2 border-dashed">
                        <div
                            class="h-16 w-16 bg-white rounded-3xl shadow-sm flex items-center justify-center mx-auto mb-6 text-emerald-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-slate-800 font-bold text-lg">Sistema bajo control</p>
                        <p class="text-sm font-medium text-slate-500 mt-2">No hay vencimientos ni mantenimiento pendiente.
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allAlerts as $alert):
                        $isDanger = ($alert['type'] == 'maintenance' && $alert['is_overdue']) ||
                            (isset($alert['days_left']) && $alert['days_left'] < 0);
                        $alertBg = $isDanger ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200';
                        $iconColor = $isDanger ? 'text-red-500' : 'text-amber-500';
                        $iconBg = $isDanger ? 'bg-red-100' : 'bg-amber-100';
                        ?>
                        <a href="<?php echo $alert['link']; ?>"
                            class="flex items-center p-5 rounded-3xl border-2 <?php echo $alertBg; ?> shadow-sm relative overflow-hidden group hover:scale-[1.02] transition-all">
                            <div
                                class="h-14 w-14 rounded-2xl <?php echo $iconBg; ?> <?php echo $iconColor; ?> flex items-center justify-center mr-5 shadow-inner">
                                <?php if ($alert['type'] == 'maintenance'): ?>
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-slate-800 tracking-tight leading-none mb-1">
                                    <?php echo htmlspecialchars($alert['entity']); ?>
                                </p>
                                <p
                                    class="text-xs font-semibold <?php echo $isDanger ? 'text-red-600' : 'text-amber-600'; ?> uppercase tracking-widest">
                                    <?php echo $alert['concept']; ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-lg font-bold <?php echo $isDanger ? 'text-red-700' : 'text-amber-700'; ?> leading-none">
                                    <?php
                                    if (isset($alert['days_left']))
                                        echo $alert['days_left'] < 0 ? 'VENCIDO' : $alert['days_left'] . 'd';
                                    elseif (isset($alert['kms_left']))
                                        echo $alert['is_overdue'] ? 'VENCIDO' : number_format($alert['kms_left']) . 'km';
                                    ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    </div> <!-- End max-w-7xl -->




    <script src="js/dashboard_health.js"></script>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <script src="js/dashboard_audit.js"></script>
    <?php endif; ?>
    <?php include 'includes/footer.php'; ?>
