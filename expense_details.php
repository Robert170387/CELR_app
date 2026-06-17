<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: expenses.php");
    exit;
}

$stmt = $pdo->prepare("SELECT e.*, t.origin, t.destination, v.placa, u.full_name as creator_name, u.username as creator_username
                       FROM expenses e 
                       LEFT JOIN trips t ON e.trip_id = t.id 
                       LEFT JOIN vehicles v ON e.vehicle_id = v.id 
                       LEFT JOIN users u ON e.created_by = u.id
                       WHERE e.id = ?");
$stmt->execute([$_GET['id']]);
$expense = $stmt->fetch();

if (!$expense) {
    die("Gasto no encontrado.");
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Detalles del Gasto</h1>
        <div>
            <a href="expense_form.php?id=<?php echo $expense['id']; ?>"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none mr-2">
                Editar
            </a>
            <a href="expenses.php"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Gasto #<?php echo $expense['id']; ?> -
                <?php echo ucfirst(str_replace('_', ' ', $expense['category'])); ?>
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Fecha: <?php echo $expense['date']; ?></p>
            <?php if (!empty($expense['creator_name']) || !empty($expense['creator_username'])): ?>
                <p class="text-xs text-gray-400 mt-1">
                    Registrado por:
                    <span class="font-medium text-gray-600">
                        <?php echo htmlspecialchars($expense['creator_name'] ?: $expense['creator_username']); ?>
                    </span>
                </p>
            <?php endif; ?>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Monto</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold text-lg">
                        <?php echo formatCurrency($expense['amount']); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo nl2br(htmlspecialchars($expense['description'] ?? '-')); ?>
                    </dd>
                </div>

                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Pagado por</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <span
                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                     <?php echo ($expense['paid_by'] ?? 'Conductor') === 'Conductor' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                            <?php echo htmlspecialchars($expense['paid_by'] ?? 'Conductor'); ?>
                        </span>
                        <span class="ml-2 text-xs text-gray-500">
                            <?php echo ($expense['paid_by'] ?? 'Conductor') === 'Conductor' ? '(Afecta liquidación)' : '(No afecta liquidación)'; ?>
                        </span>
                    </dd>
                </div>

                <!-- Associations -->
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Vinculado a Viaje</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php if ($expense['trip_id']): ?>
                            <a href="trip_details.php?id=<?php echo $expense['trip_id']; ?>"
                                class="text-brand-600 hover:underline">
                                Viaje #<?php echo $expense['trip_id']; ?> (<?php echo $expense['origin']; ?> ->
                                <?php echo $expense['destination']; ?>)
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400">No vinculado</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Vinculado a Vehículo</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo $expense['placa'] ? $expense['placa'] : '<span class="text-gray-400">--</span>'; ?>
                    </dd>
                </div>

                <!-- Fuel Specifics -->
                <?php if ($expense['category'] === 'combustible'): ?>
                    <div class="bg-yellow-50 px-4 py-5 sm:px-6 col-span-3">
                        <h4 class="text-sm font-bold text-gray-800">Detalles de Combustible</h4>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Estación (EDS)</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo htmlspecialchars($expense['eds_name'] . ' - ' . $expense['eds_location']); ?>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Factura</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            No. <?php echo htmlspecialchars($expense['invoice_number']); ?>
                            <span
                                class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($expense['invoice_status'] ?? 'Pendiente') === 'Cancelada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo htmlspecialchars($expense['invoice_status'] ?? 'Pendiente'); ?>
                            </span>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Galones</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo number_format($expense['gallons'] ?? 0, 2); ?> gal
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Precio / Galón</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo formatCurrency($expense['price_per_gallon'] ?? 0); ?>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Método de Pago</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo htmlspecialchars($expense['payment_method'] ?? '-'); ?>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>