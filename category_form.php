<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/header.php';

requireRole('admin');

$cat = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM expense_categories WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $cat = $stmt->fetch();
}
?>

<div class="max-w-xl mx-auto py-10 px-4">
    <h3 class="text-lg font-medium text-gray-900"><?php echo $cat ? 'Editar' : 'Nueva'; ?> Categoría</h3>

    <form action="save_category.php" method="POST" class="mt-5 space-y-6 bg-white p-6 shadow sm:rounded-md">
        <?php if ($cat): ?>
            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="name" value="<?php echo $cat['name'] ?? ''; ?>" required
                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
        </div>

        <?php if (!$cat): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700">Código interno (Opcional, se genera auto)</label>
                <input type="text" name="slug" placeholder="ej: mi_gasto_nuevo"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                <p class="text-xs text-gray-500">Usado por el sistema. Use guiones bajos en lugar de espacios.</p>
            </div>
        <?php else: ?>
            <div class="text-sm text-gray-500">Código: <strong><?php echo $cat['slug']; ?></strong> (No editable)</div>
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700">Tipo</label>
            <select name="type"
                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                <option value="variable" <?php if (($cat['type'] ?? '') == 'variable')
                    echo 'selected'; ?>>Operativo /
                    Variable (Se vincula a Viaje)</option>
                <option value="fixed" <?php if (($cat['type'] ?? '') == 'fixed')
                    echo 'selected'; ?>>Fijo / Mantenimiento
                </option>
            </select>
        </div>

        <div class="pt-4 flex justify-end">
            <a href="categories.php"
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 mr-3">Cancelar</a>
            <button type="submit"
                class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                Guardar
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>