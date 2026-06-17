<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/header.php';

requireRole('admin');

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("Usuario no encontrado.");
}

// Navigation: Find Previous and Next User IDs
$stmtPrev = $pdo->prepare("SELECT MAX(id) FROM users WHERE id < ?");
$stmtPrev->execute([$id]);
$prevId = $stmtPrev->fetchColumn();

$stmtNext = $pdo->prepare("SELECT MIN(id) FROM users WHERE id > ?");
$stmtNext->execute([$id]);
$nextId = $stmtNext->fetchColumn();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center">
            <h1 class="text-2xl font-bold text-gray-900">Detalles de Usuario</h1>

            <!-- Navigation Buttons -->
            <span class="inline-flex rounded-md shadow-sm ml-6">
                <?php if ($prevId): ?>
                    <a href="user_details.php?id=<?php echo $prevId; ?>"
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
                    <a href="user_details.php?id=<?php echo $nextId; ?>"
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
        <div>
            <a href="user_form.php?id=<?php echo $user['id']; ?>"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none mr-2">
                Editar
            </a>
            <a href="users.php"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Perfil de Sistema</h3>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">ID Usuario</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($user['id']); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Nombre Completo</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($user['full_name'] ?? '-'); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Nombre de Usuario</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Rol</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <span
                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo $user['created_at']; ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>