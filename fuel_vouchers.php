<?php
include 'includes/db.php';
include 'includes/header.php';

// Filter inputs
$dateStart = $_GET['start_date'] ?? date('Y-m-01');
$dateEnd = $_GET['end_date'] ?? date('Y-m-t');
$vehicleId = $_GET['vehicle_id'] ?? '';
$supplierId = $_GET['supplier_id'] ?? '';

// Build Query
$sql = "
    SELECT e.*, v.placa, 
           CASE 
               WHEN s.person_type = 'Jurídica' THEN s.business_name 
               ELSE CONCAT(s.firstname, ' ', s.lastname1) 
           END as supplier_name,
           t.origin, t.destination, e.trip_id
    FROM expenses e
    LEFT JOIN vehicles v ON e.vehicle_id = v.id
    LEFT JOIN suppliers s ON e.supplier_id = s.id
    LEFT JOIN trips t ON e.trip_id = t.id
    WHERE e.category_id IN (SELECT id FROM expense_categories WHERE slug = 'combustible' OR parent_id = (SELECT id FROM expense_categories WHERE slug = 'combustible'))
";

$params = [];

if ($dateStart) {
    $sql .= " AND e.date >= ?";
    $params[] = $dateStart;
}
if ($dateEnd) {
    $sql .= " AND e.date <= ?";
    $params[] = $dateEnd;
}
if ($vehicleId) {
    $sql .= " AND e.vehicle_id = ?";
    $params[] = $vehicleId;
}
if ($supplierId) {
    $sql .= " AND e.supplier_id = ?";
    $params[] = $supplierId;
}

$sql .= " ORDER BY e.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vouchers = $stmt->fetchAll();

// Fetch filter options
$vehicles = $pdo->query("SELECT * FROM vehicles WHERE active=1")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY created_at DESC")->fetchAll();

// Calculate Totals
$totalGallons = 0;
$totalAmount = 0;
foreach ($vouchers as $v) {
    $totalGallons += $v['gallons'];
    $totalAmount += $v['amount'];
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Vales de Combustible</h1>
            <p class="mt-2 text-sm text-gray-700">Consulta y filtrado de gastos de combustible.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="expense_form.php?category=combustible"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                Registrar Combustible
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" name="start_date" value="<?php echo $dateStart; ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
            </div>
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" name="end_date" value="<?php echo $dateEnd; ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Vehículo</label>
                <select name="vehicle_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo $vehicleId == $v['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['placa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                <select name="supplier_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $supplierId == $s['id'] ? 'selected' : ''; ?>>
                            <?php 
                            if ($s['person_type'] == 'Jurídica') {
                                echo htmlspecialchars($s['business_name']);
                            } else {
                                echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname1']);
                            }
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-6 flex justify-end">
                <button type="submit"
                    class="inline-flex justify-center rounded-md border border-transparent bg-gray-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Filtrar
                </button>
                <a href="fuel_vouchers.php" class="ml-3 inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Totals Card -->
     <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Compra</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo '$' . number_format($totalAmount, 0); ?></dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Galones</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($totalGallons, 2); ?></dd>
            </div>
        </div>
     </div>

    <!-- Table -->
    <div class="flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Fecha</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vehículo</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Proveedor</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Galones</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Precio/Gal</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Viaje</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Ver</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php if (empty($vouchers)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No se encontraron registros de combustible en este periodo.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vouchers as $v): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                            <?php echo $v['date']; ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($v['placa']); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 truncate max-w-xs">
                                            <?php echo htmlspecialchars($v['supplier_name'] ?? 'No registrado'); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-bold">
                                            <?php echo number_format((float)($v['gallons'] ?? 0), 2); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php echo number_format((float)($v['price_per_gallon'] ?? 0), 0); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-bold text-green-600">
                                            <?php echo '$' . number_format($v['amount'], 0); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            <?php if (($v['invoice_status'] ?? 'Pendiente') == 'Cancelada'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Pagado
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Pendiente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs text-gray-500">
                                            <?php if ($v['trip_id']): ?>
                                                <a href="trip_details.php?id=<?php echo $v['trip_id']; ?>" class="text-brand-600 hover:text-brand-900 hover:underline">
                                                    Viaje #<?php echo $v['trip_id']; ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <?php if ($v['receipt_photo']): ?>
                                                <a href="<?php echo htmlspecialchars($v['receipt_photo']); ?>" target="_blank" class="text-brand-600 hover:text-brand-900 mr-2">
                                                    Foto
                                                </a>
                                            <?php endif; ?>
                                            <a href="expense_form.php?id=<?php echo $v['id']; ?>" class="text-gray-600 hover:text-gray-900">
                                                Editar
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>
