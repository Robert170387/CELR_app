<?php
require_once 'includes/db.php';
include 'includes/header.php'; // includes auth.php

$message = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Initial Fetch
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Handle Basic Info & Avatar
    $full_name = trim($_POST['full_name']);

    // Avatar Upload
    $avatarPath = $currentUser['avatar']; // Default to existing
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExt, $allowedExts)) {
            $newFileName = uniqid('user_', true) . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                $avatarPath = $destPath;
            }
        }
    }

    // 2. Handle Password (Optional)
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $passwordSQL = "";
    $params = [$full_name, $avatarPath];

    if (!empty($password)) {
        if ($password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } else {
            $passwordSQL = ", password = ?";
            $params[] = password_hash($password, PASSWORD_BCRYPT);
        }
    }

    if (!$error) {
        $params[] = $user_id; // For WHERE clause
        $sql = "UPDATE users SET full_name = ?, avatar = ? $passwordSQL WHERE id = ?";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Update Session
            $_SESSION['full_name'] = $full_name;
            $_SESSION['avatar'] = $avatarPath;

            $message = "Perfil actualizado correctamente.";

            // Re-fetch user data to show updated info
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $currentUser = $stmt->fetch();

        } catch (PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}
?>

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg max-w-2xl mx-auto">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Mi Perfil</h3>
                <p class="mt-1 text-sm text-gray-500">Actualiza tu información personal</p>
            </div>
            <div>
                <span
                    class="inline-flex items-center rounded-full bg-brand-100 px-3 py-0.5 text-sm font-medium text-brand-800">
                    <?php echo ucfirst($_SESSION['role']); ?>
                </span>
            </div>
        </div>

        <div class="p-6">
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">

                <!-- Avatar Section -->
                <div class="flex items-center space-x-6">
                    <div class="flex-shrink-0">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img class="h-24 w-24 rounded-full object-cover border-4 border-gray-200"
                                src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Avatar actual">
                        <?php else: ?>
                            <span
                                class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center text-2xl font-bold text-gray-500 border-4 border-gray-100">
                                <?php echo strtoupper(substr($currentUser['username'], 0, 2)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Foto de Perfil</label>
                        <input type="file" name="avatar" accept="image/*"
                            class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG o GIF. Máx 2MB.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Username (Read Only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Usuario</label>
                        <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm cursor-not-allowed">
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                        <input type="text" name="full_name"
                            value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>

                <hr class="border-gray-200">

                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-4">Cambiar Contraseña (Opcional)</h4>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                            <input type="password" name="password" placeholder="Dejar en blanco para mantener"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                            <input type="password" name="confirm_password" placeholder="Repetir nueva contraseña"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-brand-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>