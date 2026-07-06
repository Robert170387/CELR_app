<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: suppliers.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    $_SESSION['error'] = "Proveedor no encontrado.";
    header("Location: suppliers.php");
    exit;
}

// Navigation: Find Previous and Next Supplier IDs
$stmtPrev = $pdo->prepare("SELECT MAX(id) FROM suppliers WHERE id < ?");
$stmtPrev->execute([$id]);
$prevId = $stmtPrev->fetchColumn();

$stmtNext = $pdo->prepare("SELECT MIN(id) FROM suppliers WHERE id > ?");
$stmtNext->execute([$id]);
$nextId = $stmtNext->fetchColumn();

// Get expenses count for this supplier
$stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE supplier_id = ?");
$stmt->execute([$id]);
$expensesCount = $stmt->fetchColumn();

// Get recent expenses
$stmt = $pdo->prepare("
    SELECT e.*, v.placa
    FROM expenses e
    LEFT JOIN vehicles v ON e.vehicle_id = v.id
    WHERE e.supplier_id = ?
    ORDER BY e.date DESC
    LIMIT 5
");
$stmt->execute([$id]);
$recentExpenses = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php displayAlerts(); ?>

    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0 flex items-center">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Detalles del Proveedor
            </h2>

            <!-- Navigation Buttons -->
            <span class="inline-flex rounded-md shadow-sm ml-6">
                <?php if ($prevId): ?>
                    <a href="supplier_details.php?id=<?php echo $prevId; ?>"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-700 bg-white hover:bg-gray-50"
                        title="Anterior">
                        &larr; Ant
                    </a>
                <?php else: ?>
                    <span
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-400 bg-gray-100 cursor-not-allowed">
                        &larr; Ant
                    </span>
                <?php endif; ?>

                <?php if ($nextId): ?>
                    <a href="supplier_details.php?id=<?php echo $nextId; ?>"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-white hover:bg-gray-50"
                        title="Siguiente">
                        Sig &rarr;
                    </a>
                <?php else: ?>
                    <span
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-400 bg-gray-100 cursor-not-allowed">
                        Sig &rarr;
                    </span>
                <?php endif; ?>
            </span>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="suppliers.php"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Volver
            </a>
            <a href="supplier_form.php?id=<?php echo $supplier['id']; ?>"
                class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700">
                Editar
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <?php
                    if ($supplier['person_type'] == 'Jurídica') {
                        echo htmlspecialchars($supplier['business_name']);
                    } else {
                        echo htmlspecialchars($supplier['firstname'] . ' ' . $supplier['lastname1'] . ' ' . ($supplier['lastname2'] ?? ''));
                    }
                    ?>
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    <span
                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold 
                        <?php echo $supplier['person_type'] == 'Jurídica' ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800'; ?>">
                        <?php echo htmlspecialchars($supplier['person_type']); ?>
                    </span>
                </p>
            </div>
            <div>
                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-800">
                    NIT:
                    <?php echo htmlspecialchars($supplier['nit']); ?>
                </span>
            </div>
        </div>

        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">

                <?php if ($supplier['tax_regime']): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Régimen Tributario</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($supplier['tax_regime']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if (!empty($supplier['email'])): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>"
                                class="text-brand-600 hover:text-brand-900">
                                <?php echo htmlspecialchars($supplier['email']); ?>
                            </a>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if (!empty($supplier['phone'])): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($supplier['phone']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if (!empty($supplier['mobile'])): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Móvil</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($supplier['mobile']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if (!empty($supplier['address'])): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($supplier['address']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Ubicación</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php
                        $location = [];
                        if (!empty($supplier['city']))
                            $location[] = $supplier['city'];
                        if (!empty($supplier['department']))
                            $location[] = $supplier['department'];
                        if (!empty($supplier['country']))
                            $location[] = $supplier['country'];
                        echo htmlspecialchars(implode(', ', $location) ?: 'No especificada');
                        ?>
                    </dd>
                </div>

                <?php if (!empty($supplier['notes'])): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notas</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo nl2br(htmlspecialchars($supplier['notes'])); ?>
                        </dd>
                    </div>
                <?php endif; ?>

            </dl>
        </div>
    </div>

    <!-- Expenses Section -->
    <div class="mt-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h3 class="text-lg font-medium text-gray-900">Historial de Gastos</h3>
                <p class="mt-1 text-sm text-gray-500">Total de gastos asociados:
                    <?php echo $expensesCount; ?>
                </p>
            </div>
        </div>

        <?php if (!empty($recentExpenses)): ?>
            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                    <?php foreach ($recentExpenses as $expense): ?>
                        <li>
                            <a href="expense_details.php?id=<?php echo $expense['id']; ?>" class="block hover:bg-gray-50">
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-brand-600 truncate">
                                                <?php echo htmlspecialchars($expense['description']); ?>
                                            </p>
                                            <p class="mt-1 text-sm text-gray-500">
                                                <?php echo date('d/m/Y', strtotime($expense['date'])); ?> |
                                                <span class="font-medium text-gray-900">
                                                    <?php echo formatCurrency($expense['amount']); ?>
                                                </span>
                                            </p>
                                            <p class="mt-1 text-xs text-gray-400">
                                                Categoría:
                                                <?php echo htmlspecialchars($expense['category_name'] ?? $expense['category'] ?? ''); ?>
                                                <?php if ($expense['placa']): ?>
                                                    | Vehículo:
                                                    <?php echo htmlspecialchars($expense['placa']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php if ($expensesCount > 5): ?>
                <div class="mt-4 text-center">
                    <a href="expenses.php?supplier_id=<?php echo $supplier['id']; ?>"
                        class="text-sm text-brand-600 hover:text-brand-900">
                        Ver todos los gastos (
                        <?php echo $expensesCount; ?>) →
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                <p class="text-sm text-gray-500">No hay gastos registrados con este proveedor.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>