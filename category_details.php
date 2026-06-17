<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/header.php';

requireRole('admin');

if (!isset($_GET['id'])) {
    header("Location: categories.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM expense_categories WHERE id = ?");
$stmt->execute([$_GET['id']]);
$cat = $stmt->fetch();

if (!$cat) {
    die("Categoría no encontrada.");
}

// Stats
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE category = ?");
$stmtCount->execute([$cat['slug']]);
$usage_count = $stmtCount->fetchColumn();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Detalles de Categoría</h1>
        <div>
            <a href="category_form.php?id=<?php echo $cat['id']; ?>"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none mr-2">
                Editar
            </a>
            <a href="categories.php"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900"><?php echo htmlspecialchars($cat['name']); ?></h3>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($cat['name']); ?></dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">ID Interno (Slug)</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-mono">
                        <?php echo htmlspecialchars($cat['slug']); ?></dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Tipo</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php if ($cat['type'] === 'variable'): ?>
                            <span
                                class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800">Operativo
                                / Viaje</span>
                        <?php else: ?>
                            <span
                                class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800">Fijo
                                / Mantenimiento</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Uso en Gastos</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        Usada en <?php echo $usage_count; ?> registros.
                        <?php if ($usage_count > 0): ?>
                            <span class="text-xs text-red-500 ml-2">(No se puede eliminar)</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>