<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

require_once __DIR__ . '/app/Helpers/Audit.php';
use App\Helpers\Audit;

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'La nueva contraseña y la confirmación no coinciden.';
    } elseif (strlen($new_password) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current_password, $user['password'])) {
            $error = 'La contraseña actual es incorrecta.';
        } else {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$new_hash, $_SESSION['user_id']]);

            // Log audit
            Audit::log('UPDATE', 'USER', $_SESSION['user_id'], "Cambio de contraseña");

            $success = true;
        }
    }
}
?>

<div class="max-w-2xl mx-auto py-8">
    <div class="mb-8">
        <a href="profile.php"
            class="inline-flex items-center text-sm text-brand-600 hover:text-brand-700 font-medium mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al Perfil
        </a>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Cambiar Contraseña</h1>
        <p class="text-sm text-slate-500 mt-2">Actualiza tu contraseña para mantener tu cuenta segura.</p>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm font-medium text-green-800">
                    ¡Contraseña actualizada exitosamente! Tu cuenta ahora está más segura.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-8">
            <form method="POST" action="" class="space-y-6">
                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-bold text-slate-700 mb-2">
                        Contraseña Actual
                    </label>
                    <input type="password" name="current_password" id="current_password" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                        placeholder="Ingresa tu contraseña actual">
                </div>

                <!-- New Password -->
                <div>
                    <label for="new_password" class="block text-sm font-bold text-slate-700 mb-2">
                        Nueva Contraseña
                    </label>
                    <input type="password" name="new_password" id="new_password" required minlength="6"
                        class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                        placeholder="Mínimo 6 caracteres">
                    <p class="mt-1 text-xs text-slate-500">Debe tener al menos 6 caracteres.</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-bold text-slate-700 mb-2">
                        Confirmar Nueva Contraseña
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                        class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                        placeholder="Repite la nueva contraseña">
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-brand-600 text-white font-bold py-3 px-6 rounded-xl hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-all shadow-lg shadow-brand-200 hover:shadow-brand-300">
                        Actualizar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Tips -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-2xl p-6">
        <h3 class="text-sm font-bold text-blue-900 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                </path>
            </svg>
            Consejos de Seguridad
        </h3>
        <ul class="space-y-2 text-sm text-blue-800">
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"></path>
                </svg>
                Usa una combinación de letras mayúsculas, minúsculas y números
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"></path>
                </svg>
                Evita usar información personal fácil de adivinar
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"></path>
                </svg>
                No compartas tu contraseña con nadie
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"></path>
                </svg>
                Cambia tu contraseña periódicamente
            </li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>