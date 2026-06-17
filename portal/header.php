<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAuthenticated()) {
    header("Location: ../login.php");
    exit;
}

$role = getCurrentRole();
if ($role !== 'cliente' && $role !== 'conductor' && $role !== 'admin') {
    header("Location: ../index.php?error=no_portal_access");
    exit;
}

$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'] ?? $username;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Aliados - CELR App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus+Jakarta+Sans', sans-serif;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f4ff', 100: '#e0e9fe', 200: '#c1d3fe', 300: '#92b2fd', 400: '#5c89fa',
                            500: '#355df5', 600: '#223de9', 700: '#1b2ecf', 800: '#1b28a7', 900: '#1c2885',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-brand-700 tracking-tight">CELR <span
                            class="text-gray-400 font-medium">Portal</span></span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right mr-3 hidden sm:block">
                        <p class="text-sm font-bold text-gray-900">
                            <?php echo htmlspecialchars($full_name); ?>
                        </p>
                        <p class="text-[10px] font-bold text-brand-600 uppercase tracking-widest">
                            <?php echo ucfirst($role); ?>
                        </p>
                    </div>
                    <a href="../logout.php" class="text-sm font-medium text-red-600 hover:text-red-800">Cerrar
                        Sesión</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">