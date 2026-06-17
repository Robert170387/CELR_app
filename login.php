<?php
require_once 'includes/db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'inactive') {
            $error = "Su cuenta está desactivada. Contacte al administrador.";
        } else {
            // Update last_login
            $stmtUpdate = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmtUpdate->execute([$user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['avatar'] = $user['avatar']; // Save avatar to session

            if ($user['role'] === 'cliente' || $user['role'] === 'conductor') {
                header("Location: portal/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    } else {
        $error = "Usuario o contraseña inválidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CELR App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa',
                            500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-lg shadow-lg max-w-sm w-full">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Iniciar Sesión</h1>
            <p class="text-gray-500 text-sm">Ingrese sus credenciales</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Usuario</label>
                <input
                    class="shadow appearance-none border rounded w-full bg-gray-50 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-brand-500"
                    id="username" name="username" type="text" placeholder="Usuario" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Contraseña</label>
                <input
                    class="shadow appearance-none border rounded w-full bg-gray-50 py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:border-brand-500"
                    id="password" name="password" type="password" placeholder="******************" required>
            </div>
            <div class="flex items-center justify-between">
                <button
                    class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transform transition hover:scale-105 duration-300 ease-in-out"
                    type="submit">
                    Entrar
                </button>
            </div>
        </form>
        <p class="text-center text-gray-500 text-xs mt-4">
            &copy; <?php echo date('Y'); ?> CELR App. Todos los derechos reservados.
        </p>
    </div>

</body>

</html>