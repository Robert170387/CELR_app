<?php
include 'includes/db.php';
require_once 'includes/auth.php';

// Protect page
if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

// Handle Delete (Soft Delete)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("UPDATE materials SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: materials.php");
    exit;
}

// Handle Form Submit (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $id = $_POST['id'] ?? null;

    if ($name !== '') {
        if ($id) {
            // Update
            $checkStmt = $pdo->prepare("SELECT * FROM materials WHERE name = ? AND id != ?");
            $checkStmt->execute([$name, $id]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                header("Location: materials.php?edit=$id&error=duplicate");
                exit;
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE materials SET name = ? WHERE id = ?");
                    $stmt->execute([$name, $id]);
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        header("Location: materials.php?edit=$id&error=duplicate");
                        exit;
                    } else {
                        throw $e;
                    }
                }
            }
        } else {
            // Create
            $checkStmt = $pdo->prepare("SELECT * FROM materials WHERE name = ?");
            $checkStmt->execute([$name]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                if ($existing['active'] == 0) {
                    $reactivateStmt = $pdo->prepare("UPDATE materials SET active = 1 WHERE id = ?");
                    $reactivateStmt->execute([$existing['id']]);
                } else {
                    header("Location: materials.php?error=duplicate");
                    exit;
                }
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO materials (name, active) VALUES (?, 1)");
                    $stmt->execute([$name]);
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        header("Location: materials.php?error=duplicate");
                        exit;
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }
    header("Location: materials.php");
    exit;
}

include 'includes/header.php';

// Fetch Material to Edit if requested
$editMaterial = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editMaterial = $stmt->fetch();
}

// Fetch list of active materials
$materials = $pdo->query("SELECT * FROM materials WHERE active = 1 ORDER BY id DESC")->fetchAll();
?>

<div class="max-w-4xl mx-auto py-6 px-4">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">Ya existe un material con ese nombre.</span>
        </div>
    <?php endif; ?>

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Gestión de Materiales</h3>
            <p class="mt-1 text-sm text-gray-600">
                Agrega, edita o elimina tipos de carga.
            </p>

            <form action="materials.php" method="POST" class="mt-6 bg-white p-4 shadow rounded-lg">
                <h4 class="text-sm font-bold text-gray-700 mb-2">
                    <?php echo $editMaterial ? 'Editar Material' : 'Nuevo Material'; ?>
                </h4>

                <?php if ($editMaterial): ?>
                    <input type="hidden" name="id" value="<?php echo $editMaterial['id']; ?>">
                <?php endif; ?>

                <div class="flex flex-col gap-3">
                    <input type="text" name="name"
                        value="<?php echo $editMaterial ? htmlspecialchars($editMaterial['name']) : ''; ?>" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                        placeholder="Ej. Cemento">

                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 inline-flex justify-center rounded-md border border-transparent bg-brand-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                            <?php echo $editMaterial ? 'Actualizar' : 'Guardar'; ?>
                        </button>

                        <?php if ($editMaterial): ?>
                            <a href="materials.php"
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
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">ID</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nombre</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($materials as $m): ?>
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500"><?php echo $m['id']; ?>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($m['name']); ?></td>
                                <td
                                    class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <a href="materials.php?edit=<?php echo $m['id']; ?>"
                                        class="text-brand-600 hover:text-brand-900 mr-4">Editar</a>
                                    <a href="materials.php?delete=<?php echo $m['id']; ?>"
                                        onclick="return confirm('¿Seguro que deseas eliminar este material?');"
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