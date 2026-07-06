<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/header.php';

requireRole('admin');

// Display fk_constraint error
if (isset($_GET['error']) && $_GET['error'] === 'fk_constraint') {
    $_SESSION['error'] = "No se puede eliminar la categoría porque tiene gastos asociados.";
}

$type_labels = [
    'viaje' => ['label' => 'Viaje', 'color' => 'bg-blue-100 text-blue-800'],
    'vehiculo_fijo' => ['label' => 'Vehículo Fijo', 'color' => 'bg-emerald-100 text-emerald-800'],
    'administrativo' => ['label' => 'Administrativo', 'color' => 'bg-purple-100 text-purple-800'],
    'especial' => ['label' => 'Especial', 'color' => 'bg-amber-100 text-amber-800'],
];

$type_order = ['viaje', 'vehiculo_fijo', 'administrativo', 'especial'];

// Get all categories grouped by type
$all_cats = [];
foreach ($type_order as $t) {
    $stmt = $pdo->prepare("SELECT * FROM expense_categories WHERE type = ? AND parent_id IS NULL ORDER BY sort_order ASC, name ASC");
    $stmt->execute([$t]);
    $all_cats[$t] = $stmt->fetchAll();
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php displayAlerts(); ?>
    <?php include 'includes/admin_nav.php'; ?>
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Categorías de Gastos</h1>
            <p class="mt-2 text-sm text-gray-700">Administra los tipos de gastos disponibles en el sistema.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="category_form.php"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                Nueva Categoría
            </a>
        </div>
    </div>

    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">

                    <?php foreach ($type_order as $type): 
                        $info = $type_labels[$type];
                        $cats = $all_cats[$type] ?? [];
                        if (empty($cats)) continue;
                    ?>
                    <div class="border-b border-gray-200 last:border-b-0">
                        <div class="px-6 py-3 <?php echo str_replace('text-', 'bg-', $info['color']) . '/20'; ?> border-b border-gray-100">
                            <span class="inline-flex items-center rounded-full <?php echo $info['color']; ?> px-3 py-1 text-xs font-bold">
                                <?php echo $info['label']; ?>
                            </span>
                            <span class="text-xs text-gray-500 ml-2"><?php echo count($cats); ?> categoría(s)</span>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 pl-6 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Nombre</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subcategorías</th>
                                    <th class="relative py-3 pl-3 pr-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <?php foreach ($cats as $cat): 
                                    $stmt = $pdo->prepare("SELECT * FROM expense_categories WHERE parent_id = ? ORDER BY sort_order ASC, name ASC");
                                    $stmt->execute([$cat['id']]);
                                    $children = $stmt->fetchAll();
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-bold text-gray-900">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">
                                            <?php echo htmlspecialchars($cat['slug']); ?>
                                        </td>
                                        <td class="px-3 py-4 text-sm">
                                            <?php if (!empty($children)): ?>
                                                <div class="flex flex-wrap gap-1">
                                                <?php foreach ($children as $child): ?>
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 border border-gray-200">
                                                        <a href="category_form.php?id=<?php echo $child['id']; ?>" class="hover:text-indigo-600" title="Editar: <?php echo htmlspecialchars($child['name']); ?>">
                                                            <?php echo htmlspecialchars($child['name']); ?>
                                                        </a>
                                                    </span>
                                                <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">Sin subcategorías</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                                            <a href="category_reorder.php?id=<?php echo $cat['id']; ?>&dir=up" class="inline-block text-gray-500 hover:text-gray-700 mr-1" title="Subir orden">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                            </a>
                                            <a href="category_reorder.php?id=<?php echo $cat['id']; ?>&dir=down" class="inline-block text-gray-500 hover:text-gray-700 mr-2" title="Bajar orden">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </a>
                                            <a href="category_form.php?id=<?php echo $cat['id']; ?>" class="inline-block text-indigo-600 hover:text-indigo-900 mr-2" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>
                                            <a href="category_delete.php?id=<?php echo $cat['id']; ?>" onclick="return confirm('¿Está seguro de que desea eliminar esta categoría?');" class="inline-block text-red-600 hover:text-red-900" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>