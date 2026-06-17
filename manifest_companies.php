<?php
include 'includes/db.php';
include 'includes/header.php';

// Handle Delete (Soft Delete)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("UPDATE manifest_companies SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manifest_companies.php");
    exit;
}

// Handle Form Submit (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE manifest_companies SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    } else {
        // Create
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO manifest_companies (name, active) VALUES (?, 1)");
            $stmt->execute([$name]);
        }
    }
    header("Location: manifest_companies.php");
    exit;
}

// Fetch Item to Edit if requested
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM manifest_companies WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}

// Fetch list of active items
$companies = $pdo->query("SELECT * FROM manifest_companies WHERE active = 1 ORDER BY name ASC")->fetchAll();
?>

<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Empresas de Manifiesto</h3>
            <p class="mt-1 text-sm text-gray-600">
                Gestiona las empresas que emiten los manifiestos de carga.
            </p>

            <form action="manifest_companies.php" method="POST" class="mt-6 bg-white p-4 shadow rounded-lg">
                <h4 class="text-sm font-bold text-gray-700 mb-2">
                    <?php echo $editItem ? 'Editar Empresa' : 'Nueva Empresa'; ?>
                </h4>

                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                <?php endif; ?>

                <div class="flex flex-col gap-3">
                    <input type="text" name="name"
                        value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                        placeholder="Ej. Logistica SAS">

                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 inline-flex justify-center rounded-md border border-transparent bg-brand-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                            <?php echo $editItem ? 'Actualizar' : 'Guardar'; ?>
                        </button>

                        <?php if ($editItem): ?>
                            <a href="manifest_companies.php"
                                class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nombre</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($companies as $c): ?>
                            <tr>
                                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <a href="manifest_companies.php?edit=<?php echo $c['id']; ?>"
                                        class="text-brand-600 hover:text-brand-900 mr-4">Editar</a>
                                    <a href="manifest_companies.php?delete=<?php echo $c['id']; ?>"
                                        onclick="return confirm('¿Seguro que deseas eliminar esta empresa?');"
                                        class="text-red-600 hover:text-red-900">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>