<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

// Fetch filter options
$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
$categoriesArr = $pdo->query("SELECT DISTINCT slug, name FROM expense_categories ORDER BY name")->fetchAll();
$suppliers = $pdo->query("SELECT id, business_name, firstname, lastname1, person_type FROM suppliers ORDER BY business_name, firstname")->fetchAll();

// Build WHERE clause based on filters
$where = ["1=1"];
$params = [];

if (!empty($_GET['date_from'])) {
    $where[] = "e.date >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = "e.date <= ?";
    $params[] = $_GET['date_to'];
}
if (!empty($_GET['vehicle_id'])) {
    $where[] = "e.vehicle_id = ?";
    $params[] = $_GET['vehicle_id'];
}
if (!empty($_GET['category'])) {
    $where[] = "e.category = ?";
    $params[] = $_GET['category'];
}
if (!empty($_GET['paid_by'])) {
    $where[] = "e.paid_by = ?";
    $params[] = $_GET['paid_by'];
}
if (!empty($_GET['supplier_id'])) {
    $where[] = "e.supplier_id = ?";
    $params[] = $_GET['supplier_id'];
}

$whereClause = implode(" AND ", $where);

// Main query
$sql = "SELECT e.*, v.placa, s.business_name, s.firstname, s.lastname1, s.person_type 
        FROM expenses e 
        LEFT JOIN vehicles v ON e.vehicle_id = v.id 
        LEFT JOIN suppliers s ON e.supplier_id = s.id
        WHERE $whereClause
        ORDER BY e.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Calculate totals
$totalsSql = "SELECT COUNT(*) as count, SUM(amount) as total_amount FROM expenses e WHERE $whereClause";
$totalsStmt = $pdo->prepare($totalsSql);
$totalsStmt->execute($params);
$totals = $totalsStmt->fetch();

$total_count = $totals['count'] ?: 0;
$total_amount = $totals['total_amount'] ?: 0;

$hasFilters = !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['vehicle_id']) ||
    !empty($_GET['category']) || !empty($_GET['paid_by']) || !empty($_GET['supplier_id']);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php displayAlerts(); ?>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Control de Gastos</h1>
            <p class="mt-2 text-sm text-gray-700">Egresos operativos y administrativos registrados.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="expense_form.php"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto">
                Registrar Nuevo Gasto
            </a>
        </div>
    </div>

    <!-- Enhanced Stats Summary Compact -->
    <div class="mt-6">
        <div class="inline-flex items-center bg-red-50 border border-red-100 px-4 py-2 rounded-lg">
            <span class="text-xs font-bold text-red-600 uppercase tracking-widest mr-3">Total Egresado:</span>
            <span class="text-xl font-bold text-slate-900"><?php echo formatCurrency($total_amount); ?></span>
            <span class="ml-4 text-xs font-medium text-slate-500">(<?php echo $total_count; ?> registros)</span>
        </div>
    </div>

    <!-- Filter Console Compact -->
    <div class="mt-8 bg-white border border-gray-200 rounded-lg shadow-sm"
        x-data="{ expanded: <?php echo $hasFilters ? 'true' : 'false'; ?> }">
        <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between cursor-pointer"
            @click="expanded = !expanded">
            <h3 class="text-sm font-bold text-gray-700">Filtros de Búsqueda</h3>
            <span class="text-xs text-gray-400" x-text="expanded ? 'Cerrar' : 'Abrir'"></span>
        </div>
        <div x-show="expanded" x-collapse>
            <form method="GET" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Fecha Desde/Hasta</label>
                    <div class="flex items-center space-x-2">
                        <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <input type="date" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Categoría</label>
                    <select name="category"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <option value="">Todas</option>
                        <?php foreach ($categoriesArr as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo ($_GET['category'] ?? '') == $cat['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Vehículo</label>
                    <select name="vehicle_id"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <option value="">Todos</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo ($_GET['vehicle_id'] ?? '') == $v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['placa']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-3 flex justify-end space-x-3">
                    <a href="expenses.php"
                        class="text-xs text-gray-500 hover:text-gray-700 font-medium py-2">Limpiar</a>
                    <button type="submit"
                        class="bg-gray-800 text-white px-4 py-2 rounded text-xs font-bold">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expenses Table Styled as Clients -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Fecha
                                    / Categoría</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Concepto y Detalle</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Pago
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Monto
                                </th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span
                                        class="sr-only">Gestión</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php foreach ($expenses as $expense): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-400 sm:pl-6">
                                        <div class="flex flex-col text-left">
                                            <span class="text-xs font-bold text-gray-400 font-mono tracking-tighter mb-0.5"><?php echo date('d M, Y', strtotime($expense['date'])); ?></span>
                                            <span class="text-sm font-bold text-gray-900 uppercase tracking-tight"><?php echo ucfirst(str_replace('_', ' ', $expense['category'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <p class="text-sm font-bold text-gray-700 max-w-sm truncate mb-0.5"><?php echo htmlspecialchars($expense['description']); ?></p>
                                        <span class="text-xs font-bold text-brand-600 uppercase tracking-widest"><?php echo $expense['placa'] ?? 'General'; ?></span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-left">
                                        <?php 
                                            $payerClass = ($expense['paid_by'] ?? 'Conductor') === 'Conductor' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800';
                                        ?>
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold leading-5 <?php echo $payerClass; ?>">
                                            <?php echo htmlspecialchars($expense['paid_by'] ?? 'Conductor'); ?>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-right">
                                        <span class="text-sm font-bold text-red-600 tabular-nums"><?php echo formatCurrency($expense['amount']); ?></span>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex items-center justify-end space-x-3">
                                            <a href="expense_details.php?id=<?php echo $expense['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Ver Detalles">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </a>
                                            <a href="expense_form.php?id=<?php echo $expense['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($expenses)): ?>
                                <tr><td colspan="5" class="py-10 text-center text-sm text-gray-500 italic">No se encontraron registros de gastos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>