<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php'; // Ensure DB is available for logo fetch
require_once __DIR__ . '/functions.php';

// Protect all pages that include header.php (most of them)
if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

// Generate CSRF token for all authenticated pages
$csrf_token = generateCsrfToken();

$current_page = basename($_SERVER['PHP_SELF']);
$role = getCurrentRole();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CELR App - Gestión de Flota</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="index.css?v=<?php echo time(); ?>">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- CELR Notification System -->
    <script src="js/notifications.js?v=<?php echo time(); ?>"></script>

    <!-- Flash messages desde PHP -->
    <?php
    $flash = [];
    if (!empty($_GET['msg'])) {
        $msgMap = [
            'success'      => ['type'=>'success', 'title'=>'Guardado',    'text'=>'Operación completada correctamente.'],
            'saved'        => ['type'=>'success', 'title'=>'Guardado',    'text'=>'Registro guardado correctamente.'],
            'deleted'      => ['type'=>'warning', 'title'=>'Eliminado',   'text'=>'El registro fue desactivado.'],
            'legalizado'   => ['type'=>'success', 'title'=>'Legalizado',  'text'=>'Movimiento legalizado correctamente.'],
            'cruzado'      => ['type'=>'success', 'title'=>'Cruzado',     'text'=>'Movimiento cruzado correctamente.'],
            'ya_legalizado'=> ['type'=>'info',    'title'=>'Sin cambios', 'text'=>'Este movimiento ya estaba legalizado.'],
            'error'        => ['type'=>'error',   'title'=>'Error',       'text'=>'Ocurrió un error. Intente nuevamente.'],
        ];
        $key = $_GET['msg'];
        if (isset($msgMap[$key])) $flash[] = $msgMap[$key];
    }
    if (!empty($_SESSION['flash_message'])) {
        $flash[] = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
    }
    ?>
    <div id="flash-messages" data-messages="<?php echo htmlspecialchars(json_encode($flash)); ?>" style="display:none"></div>

    <!-- Leaflet & OSM -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f4ff', 100: '#e0e9fe', 200: '#c1d3fe', 300: '#92b2fd', 400: '#5c89fa',
                            500: '#355df5', 600: '#223de9', 700: '#1b2ecf', 800: '#1b28a7', 900: '#1c2885',
                        }
                    },
                    borderRadius: {
                        'xl': '0.75rem',
                        '2xl': '1rem',
                        '3xl': '1.5rem',
                    }
                }
            }
        }
    </script>



    <!-- Form Validation -->
    <script src="js/form_validation.js"></script>
</head>

<body class="bg-slate-50 h-screen overflow-hidden text-slate-900">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header Refined -->
            <header
                class="flex justify-between items-center py-4 px-8 bg-white/80 backdrop-blur-md border-b border-slate-200 z-10">
                <div class="flex items-center">
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight flex items-center">
                        <?php
                        if ($current_page == 'index.php')
                            echo 'Dashboard <span class="text-xs font-semibold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full ml-3">v2.0</span>';
                        elseif ($current_page == 'trips.php' || $current_page == 'trip_create.php')
                            echo 'Operaciones / Viajes';
                        elseif ($current_page == 'expenses.php')
                            echo 'Control de Gastos';
                        elseif ($current_page == 'vehicles.php' || $current_page == 'vehicle_details.php')
                            echo 'Flota / Vehículos';
                        elseif ($current_page == 'financial_report.php')
                            echo 'Analítica Financiera';
                        elseif ($current_page == 'personnel.php' || $current_page == 'personnel_form.php' || $current_page == 'personnel_details.php')
                            echo 'Recursos Humanos';
                        elseif ($current_page == 'users.php')
                            echo 'Administración de Usuarios';
                        elseif ($current_page == 'config.php')
                            echo 'Configuración del Sistema';
                        elseif ($current_page == 'maintenance_list.php')
                            echo 'Mantenimiento Preventivo';
                        else
                            echo 'CELR Management';
                        ?>
                    </h2>
                </div>

                <div class="flex items-center space-x-6">
                    <!-- User Profile Dropdown -->
                    <div x-data="{ open: false }" @click.away="open = false" class="relative">
                        <!-- Trigger Button -->
                        <button @click="open = !open"
                            class="flex items-center group focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 rounded-xl transition-all">
                            <div class="mr-3 text-right hidden sm:block">
                                <p class="text-sm font-bold text-slate-900 leading-none">
                                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                                    <?php echo htmlspecialchars($_SESSION['role'] ?? 'Operador'); ?>
                                </p>
                            </div>
                            <?php if (!empty($_SESSION['avatar'])): ?>
                                <img class="h-9 w-9 rounded-xl object-cover ring-2 ring-slate-100 group-hover:ring-brand-300 transition-all"
                                    src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="Profile">
                            <?php else: ?>
                                <div
                                    class="h-9 w-9 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-bold shadow-lg shadow-slate-200 group-hover:bg-brand-600 group-hover:shadow-brand-200 transition-all">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                            <!-- Dropdown Arrow -->
                            <svg class="ml-2 h-4 w-4 text-slate-400 group-hover:text-brand-600 transition-transform duration-200"
                                :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                            class="absolute right-0 mt-3 w-64 origin-top-right z-50" style="display: none;">
                            <div class="rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/5 overflow-hidden">
                                <!-- User Info Header -->
                                <div
                                    class="px-4 py-3 bg-gradient-to-br from-brand-50 to-slate-50 border-b border-slate-100">
                                    <p class="text-sm font-bold text-slate-900 truncate">
                                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        <?php echo htmlspecialchars($_SESSION['email'] ?? 'usuario@celr.com'); ?>
                                    </p>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-2">
                                    <!-- Mi Perfil -->
                                    <a href="profile.php"
                                        class="group flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <svg class="mr-3 h-5 w-5 text-slate-400 group-hover:text-brand-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                        <span class="font-medium">Mi Perfil</span>
                                    </a>

                                    <!-- Cambiar Contraseña -->
                                    <a href="reset_password.php"
                                        class="group flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <svg class="mr-3 h-5 w-5 text-slate-400 group-hover:text-brand-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                                            </path>
                                        </svg>
                                        <span class="font-medium">Cambiar Contraseña</span>
                                    </a>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-slate-100"></div>

                                <!-- Cerrar Sesión -->
                                <div class="py-2">
                                    <a href="logout.php"
                                        class="group flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="mr-3 h-5 w-5 text-red-500 group-hover:text-red-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                            </path>
                                        </svg>
                                        <span class="font-bold">Cerrar Sesión</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Scroll Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-8">