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

// Fetch parents for dropdown (exclude self and descendants when editing)
$parents_sql = "SELECT * FROM expense_categories WHERE parent_id IS NULL";
if ($cat) {
    $parents_sql .= " AND id != " . intval($cat['id']);
    // Also exclude descendants of the current category
    $stmtDesc = $pdo->prepare("SELECT id FROM expense_categories WHERE parent_id = ?");
    $stmtDesc->execute([$cat['id']]);
    $descendants = $stmtDesc->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($descendants)) {
        $parents_sql .= " AND id NOT IN (" . implode(',', array_map('intval', $descendants)) . ")";
    }
}
$parents_sql .= " ORDER BY sort_order ASC, name ASC";
$parents = $pdo->query($parents_sql)->fetchAll();
?>

<div class="max-w-6xl mx-auto py-10 px-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Columna Formulario (2 cols) -->
        <div class="md:col-span-2">
            <h3 class="text-lg font-medium text-gray-900"><?php echo $cat ? 'Editar' : 'Nueva'; ?> Categoría</h3>

            <form action="save_category.php" method="POST" class="mt-5 space-y-6 bg-white p-6 shadow sm:rounded-md">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <?php if ($cat): ?>
                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                    <input type="hidden" name="sort_order" value="<?php echo $cat['sort_order'] ?? 0; ?>">
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
                    <label class="block text-sm font-medium text-gray-700">¿Es subcategoría de?</label>
                    <select name="parent_id" id="parent_id_select" onchange="toggleType()"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">-- Ninguna (Será una Categoría Principal) --</option>
                        <?php foreach($parents as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo (($cat['parent_id'] ?? '') == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="type_container">
                    <label class="block text-sm font-medium text-gray-700">Tipo de Gasto</label>
                    <select name="type" id="type_select"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="viaje" <?php if (($cat['type'] ?? '') == 'viaje') echo 'selected'; ?>>
                            Viaje (Combustible, Peajes, Operación de Ruta)
                        </option>
                        <option value="vehiculo_fijo" <?php if (($cat['type'] ?? '') == 'vehiculo_fijo') echo 'selected'; ?>>
                            Vehículo Fijo (Mantenimiento, Repuestos, Seguros)
                        </option>
                        <option value="administrativo" <?php if (($cat['type'] ?? '') == 'administrativo') echo 'selected'; ?>>
                            Administrativo / Nómina (Salarios, Papelería)
                        </option>
                        <option value="especial" <?php if (($cat['type'] ?? '') == 'especial') echo 'selected'; ?>>
                            Especial / No Operativo (Activos, Deducibles)
                        </option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Las subcategorías heredan automáticamente el tipo de su categoría principal.</p>
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

        <!-- Columna Barra Lateral (1 col) -->
        <div class="bg-gray-50 p-6 rounded-md shadow border border-gray-200 h-fit">
            <h4 class="text-sm font-bold text-gray-700 mb-4 border-b pb-2">📋 Jerarquía de Categorías</h4>
            <div class="space-y-4 max-h-[500px] overflow-y-auto pr-1">
                <?php 
                $type_labels_hierarchy = [
                    'viaje' => ['label' => 'Viaje', 'color' => 'text-blue-800 bg-blue-100', 'border' => 'border-blue-200'],
                    'vehiculo_fijo' => ['label' => 'Vehículo Fijo', 'color' => 'text-emerald-800 bg-emerald-100', 'border' => 'border-emerald-200'],
                    'administrativo' => ['label' => 'Administrativo', 'color' => 'text-purple-800 bg-purple-100', 'border' => 'border-purple-200'],
                    'especial' => ['label' => 'Especial', 'color' => 'text-amber-800 bg-amber-100', 'border' => 'border-amber-200'],
                ];
                $type_order = ['viaje', 'vehiculo_fijo', 'administrativo', 'especial'];
                foreach ($type_order as $type):
                    $tinfo = $type_labels_hierarchy[$type];
                    $parents_by_type = $pdo->prepare("SELECT id, name, type FROM expense_categories WHERE parent_id IS NULL AND type = ? ORDER BY sort_order ASC, name ASC");
                    $parents_by_type->execute([$type]);
                    $type_parents = $parents_by_type->fetchAll();
                    foreach ($type_parents as $parent):
                        $children = $pdo->prepare("SELECT name, slug FROM expense_categories WHERE parent_id = ? ORDER BY sort_order ASC, name ASC");
                        $children->execute([$parent['id']]);
                        $children_list = $children->fetchAll();
                ?>
                    <div>
                        <div class="text-xs font-bold <?php echo $tinfo['color']; ?> px-2 py-1.5 rounded mb-2 mt-2 border <?php echo $tinfo['border']; ?>">
                            <?php echo htmlspecialchars($parent['name']); ?>
                            <span class="font-normal text-xs float-right opacity-70"><?php echo $tinfo['label']; ?></span>
                        </div>
                        <ul class="text-sm text-gray-600 space-y-1.5 pl-3 border-l-2 border-gray-200 ml-2">
                            <?php if(empty($children_list)): ?>
                                <li class="text-xs text-gray-400 italic">Sin subcategorías</li>
                            <?php endif; ?>
                            <?php foreach ($children_list as $e): ?>
                                <li class="flex justify-between py-1 border-b border-gray-100 last:border-b-0 text-xs">
                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($e['name']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php 
                    endforeach;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleType() {
    var parentSelect = document.getElementById('parent_id_select');
    var typeContainer = document.getElementById('type_container');
    if (parentSelect.value !== "") {
        typeContainer.style.display = 'none';
    } else {
        typeContainer.style.display = 'block';
    }
}
// Run on load
document.addEventListener('DOMContentLoaded', toggleType);
</script>

<?php include 'includes/footer.php'; ?>