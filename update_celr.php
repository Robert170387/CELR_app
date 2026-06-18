<?php
if (($_GET["key"] ?? "") !== "celr2026") {
    die("<h2 style='color:red'>Acceso denegado</h2>");
}
$results = [];
$errors  = 0;

// --- includes/config_security.php ---
$dir = __DIR__ . '/includes';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Application Configuration
 * Define environment and security settings
 */

// Define application environment: 'development' or 'production'
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Security settings based on environment
if (APP_ENV === 'production') {
    // Production: Hide all errors
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    // Development: Show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Security headers
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com cdn.jsdelivr.net unpkg.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com unpkg.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self' cdn.jsdelivr.net unpkg.com;");
}

// Call security headers on every request
setSecurityHeaders();

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour

/**
 * Custom error handler for production
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (APP_ENV === 'production') {
        // Log error but don't display
        error_log("Error [$errno] $errstr in $errfile on line $errline");
        return true; // Don't execute PHP internal error handler
    }
    return false; // Let PHP handle it in development
}

set_error_handler('customErrorHandler');

/**
 * Exception handler for production
 */
function customExceptionHandler($exception) {
    if (APP_ENV === 'production') {
        error_log("Uncaught Exception: " . $exception->getMessage());
        http_response_code(500);
        echo "Ha ocurrido un error interno. Por favor contacte al administrador.";
    } else {
        // In development, show full details
        echo "<h1>Exception: " . get_class($exception) . "</h1>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    }
}

set_exception_handler('customExceptionHandler');

/**
 * Helper function to check if in production
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Helper function to check if in development
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/includes/config_security.php', $content) !== false;
$results[] = [$ok, 'includes/config_security.php'];
if (!$ok) $errors++;

// --- includes/export_buttons.php ---
$dir = __DIR__ . '/includes';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Botones de exportación reutilizables
 * Usar: include_once 'includes/export_buttons.php'; exportButtons('viajes', $_GET);
 */
function exportButtons(string $modulo, array $filtros = []): void {
    $q = array_merge($filtros, ['modulo' => $modulo]);
    $qs_pdf = http_build_query($q);
    $qs_xl  = http_build_query(array_merge($q, ['formato' => 'xlsx']));
    $qs_csv = http_build_query(array_merge($q, ['formato' => 'csv']));
    echo <<<HTML
    <div class="export-buttons flex items-center gap-2 flex-wrap">
      <span class="text-xs text-gray-400 mr-1">Exportar:</span>
      <a href="export_pdf.php?{$qs_pdf}"
         target="_blank"
         class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                bg-gradient-to-r from-red-500 to-rose-600 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
        🖨 PDF / Imprimir
      </a>
      <a href="export.php?{$qs_xl}"
         class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
        ⬇ Excel
      </a>
      <a href="export.php?{$qs_csv}"
         class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
        ⬇ CSV
      </a>
    </div>
    HTML;
}

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/includes/export_buttons.php', $content) !== false;
$results[] = [$ok, 'includes/export_buttons.php'];
if (!$ok) $errors++;

// --- includes/header.php ---
$dir = __DIR__ . '/includes';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
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
CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/includes/header.php', $content) !== false;
$results[] = [$ok, 'includes/header.php'];
if (!$ok) $errors++;

// --- includes/sidebar.php ---
$dir = __DIR__ . '/includes';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
// Fetch Logo from Config
$stmtLogo = $pdo->query("SELECT logo_path FROM config LIMIT 1");
$appConfig = $stmtLogo->fetch();
$logoPath = $appConfig['logo_path'] ?? null;
?>
<div class="flex flex-col w-64 bg-slate-950 border-r border-slate-800">
    <!-- Sidebar Header / Logo -->
    <div class="flex items-center justify-center px-8 py-10 border-b border-slate-800/50">
        <?php if ($logoPath && file_exists($logoPath)): ?>
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" class="w-full h-auto max-h-24 object-contain">
        <?php else: ?>
            <div class="flex items-center">
                <div
                    class="w-10 h-10 bg-brand-600 rounded-lg flex items-center justify-center mr-4 shadow-lg shadow-brand-500/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-white tracking-tighter uppercase">CELR <span
                        class="text-brand-500">App</span></h1>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navigation -->
    <nav class="flex-grow py-6 overflow-y-auto px-4 custom-scrollbar">
        <p
            class="px-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-[0.25em] mb-4 opacity-90 border-l-2 border-brand-500 ml-1">
            Menú Principal</p>

        <?php
        $navItems = [
            ['name' => 'Dashboard', 'url' => 'index.php', 'icon' => 'home'],
            [
                'name' => 'Gestión Operativa',
                'id' => 'trips_menu',
                'icon' => 'truck',
                'children' => [
                    ['name' => 'Registro de Viajes', 'url' => 'trips.php'],
                    ['name' => 'Manifiestos RNDC', 'url' => 'rndc.php'],
                    ['name' => 'Anticipos', 'url' => 'trip_advances.php'],
                    ['name' => 'Combustible', 'url' => 'fuel_vouchers.php'],
                    ['name' => 'Gastos de Viaje', 'url' => 'expenses.php'],
                    ['name' => 'Liquidaciones', 'url' => 'settlements.php'],
                    ['name' => 'Importar CSV', 'url' => 'import_trips.php'],
                    ['name' => 'Importación Interactiva', 'url' => 'trips_interactive_import.php'],
                ]
            ],
            ['name' => 'Mantenimiento', 'url' => 'maintenance_list.php', 'icon' => 'cog-maint'],
            [
                'name' => 'Finanzas',
                'id'   => 'finanzas_menu',
                'icon' => 'cash',
                'children' => [
                    ['name' => 'Flujo de Caja',       'url' => 'flujo_caja.php'],
                    ['name' => 'KPI / Indicadores',   'url' => 'kpi_reportes.php'],
                    ['name' => 'Compensado RC',        'url' => 'compensado_rc.php'],
                    ['name' => 'Flypass TAG',          'url' => 'flypass.php'],
                    ['name' => 'Tarjeta Débito',       'url' => 'tarjeta.php'],
                    ['name' => 'Retenciones',          'url' => 'reporte_retenciones.php'],
                ]
            ],
            [
                'name' => 'Directorio',
                'id'   => 'directorio_menu',
                'icon' => 'book',
                'children' => [
                    ['name' => 'Talleres',              'url' => 'talleres.php'],
                    ['name' => 'Proveedores',           'url' => 'suppliers.php'],
                    ['name' => 'Clientes',              'url' => 'clients.php'],
                    ['name' => 'Socios / Propietarios', 'url' => 'socios.php'],
                ]
            ],
            ['name' => 'Analítica', 'url' => 'financial_report.php', 'icon' => 'chart-bar'],
        ];

        $configChildren = [
            ['name' => 'General', 'url' => 'config.php', 'icon' => 'adjustments'],
            ['name' => 'Clientes', 'url' => 'clients.php', 'icon' => 'user-circle'],
            ['name' => 'Proveedores', 'url' => 'suppliers.php', 'icon' => 'briefcase'],
            ['name' => 'Vehículos', 'url' => 'vehicles.php', 'icon' => 'cog'],
            ['name' => 'Personal', 'url' => 'personnel.php', 'icon' => 'users'],
            ['name' => 'Categorías', 'url' => 'categories.php', 'icon' => 'tag'],
        ];

        if ($_SESSION['role'] === 'admin') {
            $configChildren[] = ['name' => 'Usuarios', 'url' => 'users.php', 'icon' => 'user-group'];
            $configChildren[] = ['name' => 'Auditoría', 'url' => 'audit_report.php', 'icon' => 'clipboard-list'];
        }

        $navItems[] = [
            'name' => 'Configuración',
            'id' => 'settings',
            'icon' => 'adjustments',
            'children' => $configChildren
        ];

        $current_page = basename($_SERVER['PHP_SELF']);

        foreach ($navItems as $item):
            if (isset($item['children'])):
                $isChildActive = false;
                foreach ($item['children'] as $child) {
                    if (
                        $current_page == $child['url'] ||
                        (str_contains($child['url'], 'trips.php') && (str_contains($current_page, 'trip'))) ||
                        (str_contains($child['url'], 'expenses.php') && (str_contains($current_page, 'expense'))) ||
                        (str_contains($child['url'], 'compensado') && str_contains($current_page, 'compensado')) ||
                        (str_contains($child['url'], 'flypass') && str_contains($current_page, 'flypass')) ||
                        (str_contains($child['url'], 'tarjeta') && str_contains($current_page, 'tarjeta')) ||
                        (str_contains($child['url'], 'taller') && str_contains($current_page, 'taller')) ||
                        (str_contains($child['url'], 'rndc') && str_contains($current_page, 'rndc')) ||
                        (str_contains($child['url'], 'socio') && str_contains($current_page, 'socio')) ||
                        (str_contains($child['url'], 'retenciones') && str_contains($current_page, 'retenciones'))
                    ) {
                        $isChildActive = true;
                        break;
                    }
                }
                ?>
                <div x-data="{ open: <?php echo $isChildActive ? 'true' : 'false'; ?> }" class="mb-1">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-xl <?php echo $isChildActive ? 'text-white bg-slate-900 border border-slate-800' : 'text-slate-400 hover:text-white hover:bg-slate-900'; ?>">
                        <div class="flex items-center">
                            <?php if ($item['icon'] == 'truck'): ?>
                                <svg class="w-5 h-5 mr-3 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0">
                                    </path>
                                </svg>
                            <?php elseif ($item['icon'] == 'adjustments'): ?>
                                <svg class="w-5 h-5 mr-3 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                    </path>
                                </svg>
                            <?php elseif ($item['icon'] == 'cash'): ?>
                                <svg class="w-5 h-5 mr-3 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                            <?php elseif ($item['icon'] == 'book'): ?>
                                <svg class="w-5 h-5 mr-3 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                    </path>
                                </svg>
                            <?php endif; ?>
                            <span><?php echo $item['name']; ?></span>
                        </div>
                        <svg class="w-4 h-4 transform transition-transform duration-200" :class="{'rotate-90': open}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-2"
                        class="mt-1 space-y-1 bg-slate-900/50 rounded-xl p-1 border border-slate-800/30">
                        <?php foreach ($item['children'] as $child):
                            $isActive = ($current_page == $child['url']);
                            ?>
                            <a href="<?php echo $child['url']; ?>"
                                class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg transition-all <?php echo $isActive ? 'text-white bg-brand-600 shadow-sm shadow-brand-500/20' : 'text-slate-500 hover:text-slate-200 hover:bg-slate-800'; ?>">
                                <span
                                    class="w-1.5 h-1.5 rounded-full mr-3 <?php echo $isActive ? 'bg-white' : 'bg-slate-700'; ?>"></span>
                                <?php echo $child['name']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else:
                $isActive = ($current_page == $item['url']);
                ?>
                <a href="<?php echo $item['url']; ?>"
                    class="sidebar-link mb-1 <?php echo $isActive ? 'sidebar-link-active' : 'sidebar-link-inactive'; ?>">
                    <?php if ($item['icon'] == 'home'): ?>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                    <?php elseif ($item['icon'] == 'cog-maint'): ?>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    <?php elseif ($item['icon'] == 'chart-bar'): ?>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    <?php endif; ?>
                    <?php echo $item['name']; ?>
                </a>
            <?php endif;
        endforeach; ?>
    </nav>


    <!-- Bottom Actions Removed -->
</div>
CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/includes/sidebar.php', $content) !== false;
$results[] = [$ok, 'includes/sidebar.php'];
if (!$ok) $errors++;

// --- index.css ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
/* Core Design System for CELR_app v2.3 - Refined Balance & Global Table Styles */

:root {
    --brand-50: #eff6ff;
    --brand-100: #dbeafe;
    --brand-200: #bfdbfe;
    --brand-500: #3b82f6;
    --brand-600: #2563eb;
    --brand-700: #1d4ed8;
    --brand-800: #1e40af;

    --slate-50: #f8fafc;
    --slate-100: #f1f5f9;
    --slate-200: #e2e8f0;
    --slate-300: #cbd5e1;
    --slate-400: #94a3b8;
    --slate-500: #64748b;
    --slate-600: #475569;
    --slate-700: #334155;
    --slate-800: #1e293b;
    --slate-900: #0f172a;
    --slate-950: #020617;
}

body {
    background-color: var(--slate-50);
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    color: var(--slate-800);
    -webkit-font-smoothing: antialiased;
    letter-spacing: -0.01em;
}

.font-mono {
    font-family: 'JetBrains Mono', monospace;
}

h1,
h2,
h3,
.font-bold {
    letter-spacing: -0.025em;
}

/* Glass Components */
.glass-card {
    background: white;
    border: 1px solid var(--slate-200);
    border-radius: 1.25rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.glass-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* 
   Table Styles - Reverting to the "Original Style" feel
   Reference: Standard Tailwind/Alpine UI tables (Divide-y, gray-50 header)
*/

.saas-table {
    width: 100%;
    border-collapse: collapse;
    /* Reverting to collapse for the standard look */
}

.saas-table th {
    background-color: var(--slate-50);
    padding: 0.625rem 1rem;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--slate-900);
    border-bottom: 1px solid var(--slate-300);
}

.saas-table td {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--slate-200);
    color: var(--slate-600);
    background-color: white;
    vertical-align: middle;
}

.saas-table tr:hover td {
    background-color: var(--slate-50);
}

/* For nested spans in cells that need to be darker */
.saas-table td .text-slate-900,
.saas-table td .font-bold {
    color: var(--slate-900);
}

/* Sidebar Links */
.sidebar-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.sidebar-link-active {
    background: white;
    color: var(--slate-950);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.sidebar-link-inactive {
    color: var(--slate-400);
}

.sidebar-link-inactive:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.08);
}

/* Buttons */
.btn-saas {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: 0.75rem;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-saas-primary {
    background-color: var(--brand-600);
    color: white !important;
}

.btn-saas-primary:hover {
    background-color: var(--brand-700);
    color: white !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-saas-secondary {
    background-color: var(--slate-100);
    color: var(--slate-700) !important;
}

.btn-saas-secondary:hover {
    background-color: var(--slate-200);
    color: var(--slate-900) !important;
}

/* Badges */
.badge-saas {
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
}

/* Form Controls */
.saas-input {
    width: 100%;
    padding: 0.625rem 1rem;
    background-color: white;
    border: 1px solid var(--slate-200);
    border-radius: 0.75rem;
    font-size: 0.875rem;
    color: var(--slate-900);
}

.saas-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--slate-700);
    margin-bottom: 0.5rem;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--slate-800);
    border-radius: 10px;
}

/* User Profile Dropdown Styles */
.user-dropdown-menu {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Ensure dropdown appears above all other elements */
.user-dropdown-container {
    position: relative;
    z-index: 9999;
}

/* Smooth hover transitions for dropdown items */
.dropdown-item {
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-item:hover {
    transform: translateX(2px);
}

/* Focus ring for accessibility */
.user-profile-button:focus {
    outline: 2px solid var(--brand-500);
    outline-offset: 2px;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .user-dropdown-menu {
        width: 240px;
        right: -1rem;
    }
}

/* ========================================
   MODERN FORM DESIGN SYSTEM (SaaS Style)
   ======================================== */

/* Form Container & Cards */

/* Form Container & Cards */
.form-card,
.celr-card {
    background: white;
    border: 1px solid var(--slate-200);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.form-card:hover,
.celr-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-color: var(--slate-300);
}

.form-section {
    background: white;
    border: 1px solid var(--slate-200);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.form-section-header {
    background: linear-gradient(to bottom, var(--slate-50), white);
    border-bottom: 1px solid var(--slate-200);
    padding: 1.25rem 2rem;
}

.form-section-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--slate-900);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0;
    line-height: 1.6;
}

.form-section-subtitle {
    font-size: 0.8125rem;
    color: var(--slate-500);
    margin-top: 0.375rem;
    line-height: 1.5;
}

.form-section-body {
    padding: 2rem;
}

/* Enhanced Input Styles */
.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 1rem 1.25rem;
    font-size: 0.9375rem;
    line-height: 1.6;
    color: var(--slate-900);
    background-color: white;
    border: 1.5px solid var(--slate-300);
    border-radius: 8px;
    transition: all 0.2s ease;
    outline: none;
}

.form-input:hover,
.form-select:hover,
.form-textarea:hover {
    border-color: var(--slate-400);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: var(--brand-500);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    background-color: white;
}

.form-input::placeholder {
    color: var(--slate-400);
}

.form-input:disabled,
.form-select:disabled {
    background-color: var(--slate-50);
    color: var(--slate-500);
    cursor: not-allowed;
    border-color: var(--slate-200);
}

/* Enhanced Labels */
.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--slate-700);
    margin-bottom: 0.625rem;
    letter-spacing: -0.01em;
    line-height: 1.5;
}

.form-label-required::after {
    content: '*';
    color: #ef4444;
    margin-left: 0.25rem;
}

.form-label-optional {
    font-size: 0.8125rem;
    font-weight: 400;
    color: var(--slate-400);
    margin-left: 0.5rem;
}

.form-hint {
    display: block;
    font-size: 0.8125rem;
    color: var(--slate-500);
    margin-top: 0.5rem;
    line-height: 1.5;
}

/* Input Groups & Addons */
.input-group {
    position: relative;
    display: flex;
    align-items: stretch;
}

.input-addon {
    display: flex;
    align-items: center;
    padding: 0 1rem;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--slate-600);
    background-color: var(--slate-50);
    border: 1.5px solid var(--slate-300);
    border-radius: 8px 0 0 8px;
    border-right: none;
}

.input-group .form-input {
    border-radius: 0 8px 8px 0;
}

/* Form Grid Layouts */
.form-grid {
    display: grid;
    gap: 1.5rem;
}

.form-grid-2 {
    grid-template-columns: repeat(1, 1fr);
}

.form-grid-3 {
    grid-template-columns: repeat(1, 1fr);
}

@media (min-width: 640px) {
    .form-grid-2 {
        grid-template-columns: repeat(2, 1fr);
    }

    .form-grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
}

.form-grid-full {
    grid-column: 1 / -1;
}

/* Enhanced Buttons */
.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.875rem 1.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: white;
    background: linear-gradient(to bottom, var(--brand-600), var(--brand-700));
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    line-height: 1.5;
}

.btn-primary:hover {
    background: linear-gradient(to bottom, var(--brand-700), var(--brand-800));
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    transform: translateY(-1px);
}

.btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.875rem 1.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--slate-700);
    background: white;
    border: 1.5px solid var(--slate-300);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    line-height: 1.5;
}

.btn-secondary:hover {
    background: var(--slate-50);
    border-color: var(--slate-400);
    color: var(--slate-900);
}

.btn-group {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-top: 2rem;
}

/* Section Dividers */
.form-divider {
    height: 1px;
    background: linear-gradient(to right, transparent, var(--slate-200), transparent);
    margin: 2rem 0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.875rem;
    border-bottom: 2px solid var(--slate-100);
}

.section-header-icon {
    width: 2.25rem;
    height: 2.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--brand-50);
    border-radius: 6px;
    color: var(--brand-600);
}

.section-header-title {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--slate-900);
    margin: 0;
    line-height: 1.5;
}

/* Chip/Tag Inputs */
.chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--brand-700);
    background: var(--brand-50);
    border: 1px solid var(--brand-200);
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.chip:hover {
    background: var(--brand-100);
    border-color: var(--brand-300);
}

.chip-active {
    background: var(--brand-600);
    color: white;
    border-color: var(--brand-600);
}

/* Toggle Switches */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--slate-300);
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.toggle-switch input:checked+.toggle-slider {
    background-color: var(--brand-600);
}

.toggle-switch input:checked+.toggle-slider:before {
    transform: translateX(20px);
}

/* Location Selector Row */
.location-selector-row {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1rem;
    align-items: end;
}

@media (min-width: 768px) {
    .location-selector-row {
        grid-template-columns: 2fr 2fr 3fr;
    }
}

/* Summary Cards */
.summary-card {
    background: linear-gradient(135deg, var(--brand-50) 0%, white 100%);
    border: 1px solid var(--brand-200);
    border-radius: 12px;
    padding: 1.25rem;
    margin-top: 1rem;
}

.summary-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--slate-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.summary-value {
    font-size: 1.875rem;
    font-weight: 800;
    color: var(--slate-900);
    font-family: 'JetBrains Mono', monospace;
}

/* Alert/Info Boxes */
.info-box {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--brand-50);
    border: 1px solid var(--brand-200);
    border-left: 4px solid var(--brand-600);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.info-box-icon {
    flex-shrink: 0;
    color: var(--brand-600);
}

.info-box-content {
    font-size: 0.8125rem;
    color: var(--slate-700);
    line-height: 1.5;
}

/* Validation States */
.form-input.is-invalid,
.form-select.is-invalid {
    border-color: #ef4444;
    background-color: #fef2f2;
}

.form-input.is-invalid:focus,
.form-select.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-input.is-valid,
.form-select.is-valid {
    border-color: #10b981;
}

.form-error {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #ef4444;
    margin-top: 0.375rem;
    font-weight: 500;
}

.form-success {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #10b981;
    margin-top: 0.375rem;
    font-weight: 500;
}

/* Loading States */
.btn-loading {
    position: relative;
    color: transparent;
    pointer-events: none;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid white;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive Form Spacing */
@media (max-width: 640px) {

    .form-card,
    .form-section-body {
        padding: 1.25rem;
    }

    .form-section-header {
        padding: 1rem 1.25rem;
    }

    .form-grid {
        gap: 1.25rem;
    }

    .btn-group {
        flex-direction: column;
        width: 100%;
        margin-top: 1.5rem;
    }

    .btn-primary,
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}

/* ============================================================
   SISTEMA DE NOTIFICACIONES — Estilo 3D Cards
   Colores: azul (info), verde (success), amarillo (warning), rojo (error)
   ============================================================ */

/* Contenedor de toasts — esquina inferior derecha */
#toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    pointer-events: none;
}

/* Toast base */
.toast {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    min-width: 300px;
    max-width: 400px;
    padding: 1rem 1.25rem;
    border-radius: 16px;
    box-shadow:
        0 8px 24px rgba(0,0,0,0.12),
        0 2px 8px rgba(0,0,0,0.08),
        inset 0 1px 0 rgba(255,255,255,0.35);
    pointer-events: all;
    cursor: pointer;
    transform: translateX(120%);
    opacity: 0;
    transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
    position: relative;
    overflow: hidden;
}

.toast::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 16px 16px 0 0;
    background: rgba(255,255,255,0.45);
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast.hide {
    transform: translateX(120%);
    opacity: 0;
}

/* Variantes de color */
.toast-success {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: #052e16;
}
.toast-error {
    background: linear-gradient(135deg, #f87171, #ef4444);
    color: #450a0a;
}
.toast-warning {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #431407;
}
.toast-info {
    background: linear-gradient(135deg, #60a5fa, #3b82f6);
    color: #172554;
}

/* Ícono del toast */
.toast-icon {
    font-size: 1.4rem;
    line-height: 1;
    flex-shrink: 0;
    margin-top: 1px;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.15));
}

/* Contenido */
.toast-body {
    flex: 1;
}
.toast-title {
    font-weight: 700;
    font-size: 0.875rem;
    margin-bottom: 2px;
    letter-spacing: -0.01em;
}
.toast-message {
    font-size: 0.8rem;
    opacity: 0.85;
    line-height: 1.4;
}

/* Barra de progreso auto-cierre */
.toast-progress {
    position: absolute;
    bottom: 0; left: 0;
    height: 3px;
    border-radius: 0 0 16px 16px;
    background: rgba(0,0,0,0.2);
    animation: toast-progress linear forwards;
}
@keyframes toast-progress {
    from { width: 100%; }
    to   { width: 0%; }
}

/* ============================================================
   ALERT BANNERS — Estilo inline en páginas (reemplaza los div básicos)
   ============================================================ */
.alert-card {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    margin-bottom: 1.25rem;
    box-shadow:
        0 4px 16px rgba(0,0,0,0.08),
        inset 0 1px 0 rgba(255,255,255,0.4);
    font-size: 0.875rem;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}
.alert-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: rgba(255,255,255,0.5);
    border-radius: 14px 14px 0 0;
}
.alert-card-icon {
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 1px;
}
.alert-card-body { flex: 1; }
.alert-card-title {
    font-weight: 700;
    margin-bottom: 2px;
}
.alert-card-text {
    opacity: 0.85;
    font-weight: 400;
    font-size: 0.82rem;
}
.alert-card-close {
    background: none; border: none; cursor: pointer;
    opacity: 0.6; font-size: 1.1rem; padding: 0;
    line-height: 1; flex-shrink: 0;
    transition: opacity 0.2s;
}
.alert-card-close:hover { opacity: 1; }

.alert-card-success {
    background: linear-gradient(135deg, #bbf7d0, #86efac);
    color: #052e16;
}
.alert-card-error {
    background: linear-gradient(135deg, #fecaca, #fca5a5);
    color: #450a0a;
}
.alert-card-warning {
    background: linear-gradient(135deg, #fef08a, #fde047);
    color: #431407;
}
.alert-card-info {
    background: linear-gradient(135deg, #bfdbfe, #93c5fd);
    color: #172554;
}
.alert-card-neutral {
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    color: #0f172a;
}
CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/index.css', $content) !== false;
$results[] = [$ok, 'index.css'];
if (!$ok) $errors++;

// --- js/notifications.js ---
$dir = __DIR__ . '/js';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
/**
 * CELR Notification System
 * Toast notifications + Alert cards estilo 3D
 */

const Notify = (() => {

    const ICONS = {
        success: '✅',
        error:   '❌',
        warning: '⚠️',
        info:    'ℹ️',
    };

    const TITLES = {
        success: 'Éxito',
        error:   'Error',
        warning: 'Atención',
        info:    'Información',
    };

    let container = null;

    function getContainer() {
        if (!container) {
            container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
        }
        return container;
    }

    /**
     * Muestra un toast
     * @param {string} message  - Texto del mensaje
     * @param {string} type     - 'success' | 'error' | 'warning' | 'info'
     * @param {string} title    - Título opcional (usa default si no se pasa)
     * @param {number} duration - ms antes de auto-cerrar (0 = no cierra solo)
     */
    function show(message, type = 'info', title = null, duration = 4000) {
        const c     = getContainer();
        const toast = document.createElement('div');
        const icon  = ICONS[type]  || ICONS.info;
        const ttl   = title || TITLES[type] || 'Aviso';

        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <div class="toast-body">
                <div class="toast-title">${ttl}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button onclick="this.closest('.toast').remove()" style="
                background:none;border:none;cursor:pointer;
                opacity:0.6;font-size:1rem;padding:0;line-height:1;
                color:inherit;flex-shrink:0;align-self:flex-start;
                margin-top:2px;
            ">✕</button>
            ${duration > 0 ? `<div class="toast-progress" style="animation-duration:${duration}ms"></div>` : ''}
        `;

        toast.addEventListener('click', () => dismiss(toast));
        c.appendChild(toast);

        // Animar entrada
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('show'));
        });

        if (duration > 0) {
            setTimeout(() => dismiss(toast), duration);
        }

        return toast;
    }

    function dismiss(toast) {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 350);
    }

    // Atajos
    const success = (msg, title, dur)  => show(msg, 'success', title, dur);
    const error   = (msg, title, dur)  => show(msg, 'error',   title, dur ?? 6000);
    const warning = (msg, title, dur)  => show(msg, 'warning', title, dur);
    const info    = (msg, title, dur)  => show(msg, 'info',    title, dur);

    /**
     * Convierte un div de alerta PHP inline en una alert-card bonita
     * Uso: Notify.upgradeAlerts() al cargar la página
     */
    function upgradeAlerts() {
        // Mapeo de clases antiguas → nuevas
        const map = [
            { old: ['bg-green-100', 'border-green-'],  type: 'success', icon: '✅', title: 'Operación exitosa' },
            { old: ['bg-red-100',   'border-red-'],    type: 'error',   icon: '❌', title: 'Error' },
            { old: ['bg-yellow-100','border-yellow-'], type: 'warning', icon: '⚠️', title: 'Atención' },
            { old: ['bg-blue-100',  'border-blue-'],   type: 'info',    icon: 'ℹ️', title: 'Información' },
        ];

        // Seleccionar divs de alerta inline típicos del proyecto
        const candidates = document.querySelectorAll(
            '[class*="bg-green-100"], [class*="bg-red-100"], [class*="bg-yellow-100"], [class*="bg-blue-100"]'
        );

        candidates.forEach(el => {
            // Solo convertir si parece un banner de mensaje (tiene border)
            const cls = el.className;
            if (!cls.includes('border')) return;
            // Evitar convertir tarjetas de dashboard, tablas, etc.
            if (el.tagName !== 'DIV') return;
            if (el.closest('table') || el.closest('form')) return;

            let matched = null;
            for (const m of map) {
                if (m.old.some(c => cls.includes(c))) { matched = m; break; }
            }
            if (!matched) return;

            const originalText = el.textContent.trim();
            el.outerHTML = buildAlertCard(matched.type, matched.icon, matched.title, originalText);
        });
    }

    function buildAlertCard(type, icon, title, text) {
        return `
        <div class="alert-card alert-card-${type}">
            <span class="alert-card-icon">${icon}</span>
            <div class="alert-card-body">
                <div class="alert-card-title">${title}</div>
                <div class="alert-card-text">${text}</div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>`;
    }

    /**
     * Crea una alert-card directamente en el DOM
     * @param {string} containerId  - ID del elemento donde insertar
     * @param {string} type         - success | error | warning | info | neutral
     * @param {string} title
     * @param {string} text
     */
    function alertCard(containerId, type, title, text) {
        const icons = { success:'✅', error:'❌', warning:'⚠️', info:'ℹ️', neutral:'📋' };
        const el = document.getElementById(containerId);
        if (!el) return;
        el.insertAdjacentHTML('afterbegin', buildAlertCard(type, icons[type] || '📋', title, text));
    }

    // Auto-upgrade al cargar si existe algún banner antiguo
    document.addEventListener('DOMContentLoaded', () => {
        upgradeAlerts();

        // Leer mensajes flash desde meta tags (para PHP → JS)
        const flashEl = document.getElementById('flash-messages');
        if (flashEl) {
            const msgs = JSON.parse(flashEl.dataset.messages || '[]');
            msgs.forEach(m => show(m.text, m.type, m.title));
        }
    });

    return { show, success, error, warning, info, upgradeAlerts, alertCard, buildAlertCard };
})();

// Global shortcuts
window.Notify = Notify;

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/js/notifications.js', $content) !== false;
$results[] = [$ok, 'js/notifications.js'];
if (!$ok) $errors++;

// --- js/dashboard_health.js ---
$dir = __DIR__ . '/js';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
document.addEventListener('DOMContentLoaded', function () {
    const startMigrationCheck = () => {
        const statusContainer = document.getElementById('migration-status-container');
        if (!statusContainer) return;

        statusContainer.innerHTML = '<span class="text-xs text-slate-400 animate-pulse">Verificando estado...</span>';

        fetch('api_migration_status.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    statusContainer.innerHTML = `<span class="text-xs text-red-500 font-bold">Error: ${data.error}</span>`;
                    return;
                }

                let html = '';
                if (data.status === 'success') {
                    html = `
                        <div class="flex items-center space-x-2">
                             <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                             <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Base de Datos al día</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 pl-6">
                            Migraciones: ${data.applied_migrations} / ${data.total_files}
                        </div>
                    `;
                } else {
                    html = `
                        <div class="flex flex-col space-y-2">
                            <div class="flex items-center space-x-2 animate-pulse">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">${data.pending_count} Migraciones Pendientes</span>
                            </div>
                            <button onclick="window.location.href='migrate.php'" class="ml-6 px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold rounded shadow-sm transition-colors w-fit">
                                Ejecutar Ahora &rarr;
                            </button>
                        </div>
                    `;
                }

                // Append DB connection warning if needed
                if (data.db_connection !== 'ok') {
                    html += `<div class="mt-2 text-[10px] font-bold text-red-600">❌ Error de Conexión a BD</div>`;
                }

                if (data.missing_tables && data.missing_tables.length > 0) {
                    html += `<div class="mt-1 text-[10px] font-bold text-red-600">Faltan tablas: ${data.missing_tables.join(', ')}</div>`;
                }

                statusContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching migration status:', error);
                statusContainer.innerHTML = '<span class="text-xs text-red-400">Error de conexión</span>';
            });
    };

    const startAlertsCheck = () => {
        const alertsContainer = document.getElementById('system-alerts-container');
        const alertsList = document.getElementById('alerts-list');
        if (!alertsContainer || !alertsList) return;

        fetch('api.php?action=getSystemAlerts')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.alerts.length > 0) {
                    alertsContainer.style.display = 'block';
                    alertsList.innerHTML = data.alerts.map(alert => {
                        let icon = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                        let bg = 'bg-red-50 border-red-100';

                        if (alert.type === 'status_change') {
                            icon = '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>';
                            bg = 'bg-blue-50 border-blue-100';
                        }

                        return `
                            <div class="flex items-start space-x-4 p-4 rounded-xl border-2 ${bg} shadow-sm transition-all hover:scale-[1.02]">
                                <div class="p-2 bg-white rounded-lg shadow-sm border">
                                    ${icon}
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-slate-900 leading-tight">${alert.title}</h4>
                                    <p class="text-[11px] text-slate-500 font-medium mt-1">${alert.message}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">${alert.time_ago}</span>
                                        <span class="text-[10px] font-black text-slate-600 uppercase">#${alert.entity_id || ''}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            })
            .catch(error => console.error('Error fetching alerts:', error));
    };

    const confirmDialog = (message, onConfirm) => {
        const existing = document.getElementById('celr-confirm-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'celr-confirm-modal';
        modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);';
        modal.innerHTML = `
            <div style="background:#1e293b;border:1px solid #334155;border-radius:16px;padding:2rem;max-width:380px;width:90%;box-shadow:0 25px 60px rgba(0,0,0,0.5);text-align:center;">
                <div style="font-size:2.5rem;margin-bottom:1rem;">🗄️</div>
                <h3 style="color:#f1f5f9;font-size:1.1rem;font-weight:700;margin-bottom:0.5rem;">Confirmar Acción</h3>
                <p style="color:#94a3b8;font-size:0.9rem;margin-bottom:1.5rem;">${message}</p>
                <div style="display:flex;gap:0.75rem;justify-content:center;">
                    <button id="celr-confirm-yes" style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;padding:0.6rem 1.4rem;border-radius:8px;cursor:pointer;font-weight:700;font-size:0.9rem;">Sí, generar</button>
                    <button id="celr-confirm-no"  style="background:#334155;color:#cbd5e1;border:none;padding:0.6rem 1.4rem;border-radius:8px;cursor:pointer;font-weight:600;font-size:0.9rem;">Cancelar</button>
                </div>
            </div>`;

        document.body.appendChild(modal);
        document.getElementById('celr-confirm-no').onclick  = () => modal.remove();
        document.getElementById('celr-confirm-yes').onclick = () => { modal.remove(); onConfirm(); };
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    };

    const initBackupButton = () => {
        const btn = document.getElementById('btn-generate-backup');
        if (!btn) return;

        btn.addEventListener('click', function () {
            confirmDialog('¿Está seguro de generar un respaldo de la base de datos ahora?', () => {
                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="w-3 h-3 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> GENERANDO...';

                fetch('api.php?action=triggerDatabaseBackup')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Notify.success('Respaldo generado exitosamente: ' + data.filename);
                            setTimeout(() => window.location.reload(), 2500);
                        } else {
                            Notify.error('Error al generar respaldo: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error generating backup:', error);
                        Notify.error('Error técnico al generar respaldo.');
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
            });
        });
    };

    startMigrationCheck();
    startAlertsCheck();
    initBackupButton();
});

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/js/dashboard_health.js', $content) !== false;
$results[] = [$ok, 'js/dashboard_health.js'];
if (!$ok) $errors++;

// --- login.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
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
            $stmtUpdate = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmtUpdate->execute([$user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['avatar'] = $user['avatar'];

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
    <title>CELR App — Iniciar Sesión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        /* Fondo con imagen y efecto Ken Burns */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: url('login-bg.jpg') center center / cover no-repeat;
            animation: kenburns 20s ease-in-out infinite alternate;
            transform-origin: center center;
        }

        @keyframes kenburns {
            0%   { transform: scale(1)    translateX(0)     translateY(0); }
            25%  { transform: scale(1.08) translateX(-1%)   translateY(-1%); }
            50%  { transform: scale(1.05) translateX(1%)    translateY(0.5%); }
            75%  { transform: scale(1.1)  translateX(-0.5%) translateY(1%); }
            100% { transform: scale(1.06) translateX(0.5%)  translateY(-0.5%); }
        }

        /* Partículas flotantes */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255,255,255,0.6);
            border-radius: 50%;
            animation: float linear infinite;
        }

        @keyframes float {
            0%   { transform: translateY(100vh) translateX(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translateY(-10vh) translateX(30px); opacity: 0; }
        }

        /* Overlay oscuro */
        .overlay {
            position: fixed;
            inset: 0;
            z-index: 2;
            background: linear-gradient(135deg, rgba(0,20,60,0.65) 0%, rgba(0,0,0,0.45) 100%);
        }

        /* Card del login */
        .login-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* Logo / ícono */
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 2rem;
            box-shadow: 0 8px 25px rgba(59,130,246,0.5);
            animation: pulse-glow 3s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 8px 25px rgba(59,130,246,0.5); }
            50%       { box-shadow: 0 8px 40px rgba(59,130,246,0.85); }
        }

        .login-card h1 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .login-card p.subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 1.8rem;
        }

        label {
            display: block;
            color: rgba(255,255,255,0.85);
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
            transition: all 0.3s;
            outline: none;
        }

        input::placeholder { color: rgba(255,255,255,0.4); }

        input:focus {
            border-color: #3b82f6;
            background: rgba(59,130,246,0.15);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.25);
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59,130,246,0.6);
        }

        .btn-login:active { transform: translateY(0); }

        .error-msg {
            background: rgba(239,68,68,0.2);
            border: 1px solid rgba(239,68,68,0.5);
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .footer-text {
            color: rgba(255,255,255,0.4);
            font-size: 0.75rem;
            text-align: center;
            margin-top: 1.5rem;
        }

    </style>
</head>
<body>

    <!-- Fondo animado -->
    <div class="bg-scene"></div>

    <!-- Partículas -->
    <div class="particles">
        <?php for ($i = 0; $i < 18; $i++):
            $left  = rand(0, 100);
            $delay = rand(0, 15);
            $dur   = rand(8, 20);
            $size  = rand(2, 5);
        ?>
        <div class="particle" style="left:<?= $left ?>%;animation-duration:<?= $dur ?>s;animation-delay:<?= $delay ?>s;width:<?= $size ?>px;height:<?= $size ?>px;"></div>
        <?php endfor; ?>
    </div>

    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Card -->
    <div class="login-card">
        <div class="logo-icon">🚛</div>
        <h1>CELR App</h1>
        <p class="subtitle">Sistema de Gestión de Flota</p>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required autofocus>

            <label for="password">Contraseña</label>
            <div style="position:relative;">
                <input type="password" id="password" name="password" placeholder="••••••••••••" required style="padding-right:3rem;">
                <button type="button" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁️':'🙈';" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-60%);background:none;border:none;cursor:pointer;font-size:1.1rem;color:rgba(255,255,255,0.6);">👁️</button>
            </div>

            <button type="submit" class="btn-login">Entrar al Sistema</button>
        </form>

        <p class="footer-text">&copy; <?= date('Y') ?> CELR App &mdash; Todos los derechos reservados</p>
    </div>


</body>
</html>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/login.php', $content) !== false;
$results[] = [$ok, 'login.php'];
if (!$ok) $errors++;

// --- export.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Exportación centralizada — XLSX / CSV
 * Parámetros GET: modulo, formato, + filtros del módulo
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/SimpleXLSXGen.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

$modulo  = $_GET['modulo']  ?? '';
$formato = $_GET['formato'] ?? 'xlsx'; // xlsx | csv

// ─── Datos por módulo ────────────────────────────────────────────────────────

function buildData(PDO $pdo, string $modulo): array {
    switch ($modulo) {

        // ── VIAJES ──────────────────────────────────────────────────────────
        case 'viajes':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['status']))     { $where .= " AND t.status = ?";       $params[] = $_GET['status']; }
            if (!empty($_GET['vehicle_id'])) { $where .= " AND t.vehicle_id = ?";   $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['fecha_desde'])){ $where .= " AND t.date_load >= ?";   $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])){ $where .= " AND t.date_load <= ?";   $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT t.id, t.date_load, t.trip_type, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           CASE WHEN c.person_type='Jurídica' THEN c.business_name
                                ELSE CONCAT(c.firstname,' ',c.lastname1) END AS cliente,
                           m.name AS material,
                           t.origin, t.destination,
                           t.manifest_number, t.empresa_transporte,
                           t.flete_bruto, t.flete_liquidado, t.comision_manifiesto,
                           t.total_deductibles, t.flete_neto,
                           t.advance_manifest, t.advance_owner,
                           t.gastos_totales, t.commission_value,
                           t.status
                    FROM trips t
                    LEFT JOIN vehicles  v ON v.id = t.vehicle_id
                    LEFT JOIN personnel p ON p.id = t.driver_id
                    LEFT JOIN materials m ON m.id = t.material_id
                    LEFT JOIN clients   c ON c.id = t.client_id
                    $where ORDER BY t.date_load DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha Carga','Tipo','Placa','Conductor','Cliente','Material',
                        'Origen','Destino','Manifiesto','Empresa Transporte',
                        'Flete Bruto','Flete Liquidado','Comisión Manifiesto',
                        'Retenciones','Flete Neto','Anticipo Manif.','Anticipo Trans.',
                        'Gastos Totales','Comisión','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['date_load'], $r['trip_type'], $r['placa'],
                    $r['conductor'], $r['cliente'], $r['material'],
                    $r['origin'], $r['destination'],
                    $r['manifest_number'], $r['empresa_transporte'],
                    (float)$r['flete_bruto'], (float)$r['flete_liquidado'],
                    (float)$r['comision_manifiesto'], (float)$r['total_deductibles'],
                    (float)$r['flete_neto'], (float)$r['advance_manifest'],
                    (float)$r['advance_owner'], (float)$r['gastos_totales'],
                    (float)$r['commission_value'], $r['status'],
                ];
            }
            return ['data' => $data, 'nombre' => 'viajes'];

        // ── GASTOS ──────────────────────────────────────────────────────────
        case 'gastos':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND e.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['category']))   { $where .= " AND e.category = ?";   $params[] = $_GET['category']; }
            if (!empty($_GET['fecha_desde'])){ $where .= " AND e.expense_date >= ?"; $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])) { $where .= " AND e.expense_date <= ?"; $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT e.id, e.expense_date, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           e.category, e.description,
                           e.amount, e.payment_method, e.supplier_name,
                           e.trip_id, e.notes
                    FROM expenses e
                    LEFT JOIN vehicles  v ON v.id = e.vehicle_id
                    LEFT JOIN personnel p ON p.id = e.driver_id
                    $where ORDER BY e.expense_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa','Conductor','Categoría','Descripción',
                        'Valor','Método Pago','Proveedor','Viaje ID','Notas'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['expense_date'], $r['placa'], $r['conductor'],
                    $r['category'], $r['description'], (float)$r['amount'],
                    $r['payment_method'], $r['supplier_name'], $r['trip_id'], $r['notes'],
                ];
            }
            return ['data' => $data, 'nombre' => 'gastos'];

        // ── COMPENSADO RC ────────────────────────────────────────────────────
        case 'compensado':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['mes']))    { $where .= " AND cr.mes = ?";    $params[] = (int)$_GET['mes']; }
            if (!empty($_GET['anio']))   { $where .= " AND cr.anio = ?";   $params[] = (int)$_GET['anio']; }
            if (!empty($_GET['estado'])) { $where .= " AND cr.estado = ?"; $params[] = $_GET['estado']; }

            $sql = "SELECT cr.codigo, cr.anio, cr.mes,
                           CONCAT(p.firstname,' ',p.lastname) AS conductor,
                           v.placa,
                           cr.flete_neto_periodo, cr.gastos_periodo,
                           cr.comision_porcentaje, cr.comision_bruta,
                           cr.descuentos, cr.anticipos_periodo,
                           cr.neto_pagar, cr.estado,
                           cr.fecha_pago, cr.notas
                    FROM compensado_rc cr
                    JOIN personnel p ON p.id = cr.personnel_id
                    JOIN vehicles  v ON v.id = cr.vehicle_id
                    $where ORDER BY cr.anio DESC, cr.mes DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Código','Año','Mes','Conductor','Placa',
                        'Flete Neto Período','Gastos Período','% Comisión',
                        'Comisión Bruta','Descuentos','Anticipos',
                        'Neto a Pagar','Estado','Fecha Pago','Notas'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['codigo'], $r['anio'], $r['mes'], $r['conductor'], $r['placa'],
                    (float)$r['flete_neto_periodo'], (float)$r['gastos_periodo'],
                    (float)$r['comision_porcentaje'], (float)$r['comision_bruta'],
                    (float)$r['descuentos'], (float)$r['anticipos_periodo'],
                    (float)$r['neto_pagar'], $r['estado'], $r['fecha_pago'], $r['notas'],
                ];
            }
            return ['data' => $data, 'nombre' => 'compensado_rc'];

        // ── FLYPASS ──────────────────────────────────────────────────────────
        case 'flypass':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id']))    { $where .= " AND fm.vehicle_id = ?";    $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['legalizado']))    { $where .= " AND fm.legalizado = ?";    $params[] = (int)$_GET['legalizado']; }
            if (!empty($_GET['tipo_movimiento'])){ $where .= " AND fm.tipo_movimiento = ?"; $params[] = $_GET['tipo_movimiento']; }
            if (!empty($_GET['fecha_desde']))   { $where .= " AND DATE(fm.fecha) >= ?";  $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta']))   { $where .= " AND DATE(fm.fecha) <= ?";  $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT fm.id, fm.fecha, v.placa AS vehicle_placa, fm.placa,
                           fm.tipo_movimiento, fm.valor, fm.peaje_nombre,
                           fm.referencia_1, fm.referencia_2, fm.descripcion,
                           fm.legalizado, fm.fecha_legalizacion
                    FROM flypass_movimientos fm
                    LEFT JOIN vehicles v ON v.id = fm.vehicle_id
                    $where ORDER BY fm.fecha DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa Vehículo','Placa TAG','Tipo','Valor',
                        'Peaje','Ref. 1','Ref. 2','Descripción','Legalizado','Fecha Legalización'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['fecha'], $r['vehicle_placa'], $r['placa'],
                    $r['tipo_movimiento'], (float)$r['valor'], $r['peaje_nombre'],
                    $r['referencia_1'], $r['referencia_2'], $r['descripcion'],
                    $r['legalizado'] ? 'Sí' : 'No', $r['fecha_legalizacion'],
                ];
            }
            return ['data' => $data, 'nombre' => 'flypass'];

        // ── TARJETA DÉBITO ───────────────────────────────────────────────────
        case 'tarjeta':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND tm.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['cruzado']))    { $where .= " AND tm.cruzado = ?";    $params[] = $_GET['cruzado']; }
            if (!empty($_GET['tipo']))       { $where .= " AND tm.tipo = ?";       $params[] = $_GET['tipo']; }
            if (!empty($_GET['fecha_desde'])) { $where .= " AND tm.fecha >= ?";    $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])) { $where .= " AND tm.fecha <= ?";    $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT tm.id, tm.fecha, v.placa, tm.tipo,
                           tm.establecimiento, tm.descripcion, tm.referencia,
                           tm.valor, tm.cruzado, tm.fecha_cruce, tm.detalle_cruce
                    FROM tarjeta_movimientos tm
                    LEFT JOIN vehicles v ON v.id = tm.vehicle_id
                    $where ORDER BY tm.fecha DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa','Tipo','Establecimiento',
                        'Descripción','Referencia','Valor','Cruzado','Fecha Cruce','Detalle'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['fecha'], $r['placa'], $r['tipo'],
                    $r['establecimiento'], $r['descripcion'], $r['referencia'],
                    (float)$r['valor'], $r['cruzado'], $r['fecha_cruce'], $r['detalle_cruce'],
                ];
            }
            return ['data' => $data, 'nombre' => 'tarjeta_debito'];

        // ── TALLERES ─────────────────────────────────────────────────────────
        case 'talleres':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['activos']) && $_GET['activos'] === '1') {
                $where .= " AND active = 1";
            }
            if (!empty($_GET['ciudad']))       { $where .= " AND ciudad = ?";      $params[] = $_GET['ciudad']; }
            if (!empty($_GET['especialidad'])) { $where .= " AND especialidad = ?"; $params[] = $_GET['especialidad']; }

            $sql = "SELECT nombre, especialidad, ciudad, departamento, direccion,
                           telefono, celular, email, contacto_principal, nit, notas,
                           IF(active,'Activo','Inactivo') AS estado
                    FROM talleres $where ORDER BY nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Nombre','Especialidad','Ciudad','Departamento','Dirección',
                        'Teléfono','Celular','Email','Contacto','NIT','Notas','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = array_values($r);
            }
            return ['data' => $data, 'nombre' => 'talleres'];

        // ── FLUJO DE CAJA ─────────────────────────────────────────────────────
        case 'flujo_caja':
            $anio = !empty($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
            $sql = "SELECT mes, nombre_mes,
                           total_fletes, total_gastos_viaje, total_gastos_extras,
                           total_anticipos, utilidad_bruta, num_viajes
                    FROM view_flujo_caja_mensual
                    WHERE anio = ?
                    ORDER BY mes";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$anio]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Mes','Nombre','Fletes Netos','Gastos Viaje','Gastos Extras',
                        'Anticipos','Utilidad Bruta','# Viajes'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['mes'], $r['nombre_mes'],
                    (float)$r['total_fletes'], (float)$r['total_gastos_viaje'],
                    (float)$r['total_gastos_extras'], (float)$r['total_anticipos'],
                    (float)$r['utilidad_bruta'], (int)$r['num_viajes'],
                ];
            }
            return ['data' => $data, 'nombre' => "flujo_caja_$anio"];

        // ── LIQUIDACIONES ────────────────────────────────────────────────────
        case 'liquidaciones':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND s.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['status']))     { $where .= " AND s.status = ?";     $params[] = $_GET['status']; }
            if (!empty($_GET['fecha_desde'])) { $where .= " AND s.date >= ?";     $params[] = $_GET['fecha_desde']; }

            $sql = "SELECT s.id, s.date, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           s.total_income, s.total_expenses,
                           s.net_amount, s.status, s.notes
                    FROM settlements s
                    LEFT JOIN vehicles  v ON v.id = s.vehicle_id
                    LEFT JOIN personnel p ON p.id = s.driver_id
                    $where ORDER BY s.date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa','Conductor','Total Ingresos',
                        'Total Gastos','Neto','Estado','Notas'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['date'], $r['placa'], $r['conductor'],
                    (float)$r['total_income'], (float)$r['total_expenses'],
                    (float)$r['net_amount'], $r['status'], $r['notes'],
                ];
            }
            return ['data' => $data, 'nombre' => 'liquidaciones'];

        // ── RNDC ─────────────────────────────────────────────────────────────
        case 'rndc':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND m.vehicle_id = ?";       $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['estado']))     { $where .= " AND m.estado = ?";            $params[] = $_GET['estado']; }
            if (!empty($_GET['fecha_desde'])){ $where .= " AND m.fecha_expedicion >= ?"; $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])){ $where .= " AND m.fecha_expedicion <= ?"; $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT m.nro_manifiesto, m.autorizacion_rndc, m.nro_remesa,
                           m.fecha_expedicion, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           m.empresa_transporte, m.nit_empresa,
                           m.remitente_nombre, m.destinatario_nombre,
                           m.origen, m.destino,
                           m.descripcion_mercancia, m.peso_kg, m.unidades,
                           m.flete_pactado, m.anticipo, m.saldo,
                           m.cargue_pagado_por, m.descargue_pagado_por,
                           m.lugar_pago, m.fecha_pago_saldo, m.estado
                    FROM manifiestos_rndc m
                    LEFT JOIN vehicles  v ON v.id = m.vehicle_id
                    LEFT JOIN personnel p ON p.id = m.driver_id
                    $where ORDER BY m.fecha_expedicion DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Nro. Manifiesto','Autorización RNDC','Nro. Remesa','Fecha',
                        'Placa','Conductor','Empresa Trans.','NIT Empresa',
                        'Remitente','Destinatario','Origen','Destino',
                        'Mercancía','Peso(kg)','Unidades',
                        'Flete Pactado','Anticipo','Saldo',
                        'Cargue x','Descargue x','Lugar Pago','Fecha Pago Saldo','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['nro_manifiesto'], $r['autorizacion_rndc'], $r['nro_remesa'],
                    $r['fecha_expedicion'], $r['placa'], $r['conductor'],
                    $r['empresa_transporte'], $r['nit_empresa'],
                    $r['remitente_nombre'], $r['destinatario_nombre'],
                    $r['origen'], $r['destino'],
                    $r['descripcion_mercancia'], (float)$r['peso_kg'], (int)$r['unidades'],
                    (float)$r['flete_pactado'], (float)$r['anticipo'], (float)$r['saldo'],
                    $r['cargue_pagado_por'], $r['descargue_pagado_por'],
                    $r['lugar_pago'], $r['fecha_pago_saldo'], $r['estado'],
                ];
            }
            return ['data' => $data, 'nombre' => 'manifiestos_rndc'];

        // ── SOCIOS ───────────────────────────────────────────────────────────
        case 'socios':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['activos']) && $_GET['activos'] === '1') {
                $where .= " AND s.active = 1";
            }
            if (!empty($_GET['buscar'])) {
                $where .= " AND (s.nombre LIKE ? OR s.documento LIKE ?)";
                $params[] = '%' . $_GET['buscar'] . '%';
                $params[] = '%' . $_GET['buscar'] . '%';
            }
            if (!empty($_GET['tipo'])) { $where .= " AND s.tipo = ?"; $params[] = $_GET['tipo']; }

            $sql = "SELECT s.nombre, s.tipo,
                           CONCAT(s.tipo_documento,' ',IFNULL(s.documento,'')) AS documento,
                           s.ciudad, s.telefono, s.celular, s.email,
                           s.banco, s.cuenta_bancaria, s.porcentaje_utilidad,
                           IF(s.active,'Activo','Inactivo') AS estado
                    FROM socios s $where ORDER BY s.nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Nombre','Tipo','Documento','Ciudad','Teléfono','Celular',
                        'Email','Banco','Cuenta Bancaria','% Utilidad','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = array_values($r);
            }
            return ['data' => $data, 'nombre' => 'socios'];

        // ── RETENCIONES TRIBUTARIAS ───────────────────────────────────────────
        case 'retenciones':
            $anio = !empty($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
            $params = [$anio];
            $where  = "WHERE YEAR(t.date_load) = ?";
            if (!empty($_GET['mes']))        { $where .= " AND MONTH(t.date_load) = ?"; $params[] = (int)$_GET['mes']; }
            if (!empty($_GET['vehicle_id'])) { $where .= " AND t.vehicle_id = ?";       $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['client_id']))  { $where .= " AND t.client_id = ?";        $params[] = (int)$_GET['client_id']; }

            $sql = "SELECT
                        t.date_load,
                        v.placa,
                        CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                        CASE WHEN c.person_type='Jurídica' THEN c.business_name
                             ELSE CONCAT(c.firstname,' ',c.lastname1) END AS cliente,
                        t.manifest_number,
                        t.flete_bruto,
                        t.total_deductibles,
                        t.flete_neto,
                        t.status
                    FROM trips t
                    LEFT JOIN vehicles  v ON v.id = t.vehicle_id
                    LEFT JOIN personnel p ON p.id = t.driver_id
                    LEFT JOIN clients   c ON c.id = t.client_id
                    $where
                    ORDER BY t.date_load DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Fecha','Placa','Conductor','Cliente','Manifiesto',
                        'Flete Bruto','Total Deductibles','Flete Neto','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['date_load'], $r['placa'], $r['conductor'], $r['cliente'],
                    $r['manifest_number'],
                    (float)$r['flete_bruto'], (float)$r['total_deductibles'],
                    (float)$r['flete_neto'], $r['status'],
                ];
            }
            return ['data' => $data, 'nombre' => "retenciones_$anio"];

        default:
            return ['data' => [['Módulo no válido']], 'nombre' => 'export'];
    }
}

// ─── Construir y descargar ────────────────────────────────────────────────────

$result   = buildData($pdo, $modulo);
$data     = $result['data'];
$nombre   = $result['nombre'];
$filename = $nombre . '_' . date('Ymd_His');

if ($formato === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel
    foreach ($data as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

// Default: XLSX
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename . '.xlsx');
exit;

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/export.php', $content) !== false;
$results[] = [$ok, 'export.php'];
if (!$ok) $errors++;

// --- export_pdf.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Vista de impresión / PDF — todos los módulos
 * El navegador imprime con Ctrl+P o el botón de imprimir de la página
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

$modulo = $_GET['modulo'] ?? '';

$meses_nombres = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

// ─── Cargar datos ─────────────────────────────────────────────────────────────
$titulo = 'Reporte';
$subtitulo = '';
$headers = [];
$rows = [];

switch ($modulo) {

    case 'viajes':
        $titulo = 'Registro de Viajes';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['status']))     { $where .= " AND t.status = ?";     $params[] = $_GET['status']; }
        if (!empty($_GET['vehicle_id'])) { $where .= " AND t.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND t.date_load >= ?"; $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])){ $where .= " AND t.date_load <= ?"; $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT t.id, t.date_load, t.trip_type, v.placa,
                       CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                       t.origin, t.destination, t.manifest_number,
                       t.flete_bruto, t.flete_neto, t.commission_value, t.status
                FROM trips t
                LEFT JOIN vehicles  v ON v.id = t.vehicle_id
                LEFT JOIN personnel p ON p.id = t.driver_id
                $where ORDER BY t.date_load DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['ID','Fecha','Tipo','Placa','Conductor','Origen','Destino','Manif.','Flete Bruto','Flete Neto','Comisión','Estado'];
        foreach ($data as $r) {
            $rows[] = [
                $r['id'], date('d/m/Y', strtotime($r['date_load'])),
                $r['trip_type'], $r['placa'], $r['conductor'],
                $r['origin'], $r['destination'], $r['manifest_number'],
                '$'.number_format($r['flete_bruto'],0,',','.'),
                '$'.number_format($r['flete_neto'],0,',','.'),
                '$'.number_format($r['commission_value'],0,',','.'),
                $r['status'],
            ];
        }
        if (!empty($_GET['fecha_desde']) || !empty($_GET['fecha_hasta'])) {
            $subtitulo = 'Del ' . ($_GET['fecha_desde'] ?? '...') . ' al ' . ($_GET['fecha_hasta'] ?? '...');
        }
        break;

    case 'gastos':
        $titulo = 'Registro de Gastos';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['vehicle_id'])) { $where .= " AND e.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['category']))   { $where .= " AND e.category = ?";   $params[] = $_GET['category']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND e.expense_date >= ?"; $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])) { $where .= " AND e.expense_date <= ?"; $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT e.expense_date, v.placa, e.category, e.description,
                       e.amount, e.payment_method, e.supplier_name
                FROM expenses e
                LEFT JOIN vehicles v ON v.id = e.vehicle_id
                $where ORDER BY e.expense_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Fecha','Placa','Categoría','Descripción','Valor','Método Pago','Proveedor'];
        $total = 0;
        foreach ($data as $r) {
            $total += $r['amount'];
            $rows[] = [
                date('d/m/Y', strtotime($r['expense_date'])),
                $r['placa'], $r['category'], $r['description'],
                '$'.number_format($r['amount'],0,',','.'),
                $r['payment_method'], $r['supplier_name'],
            ];
        }
        $rows[] = ['','','','<strong>TOTAL</strong>','<strong>$'.number_format($total,0,',','.').'</strong>','',''];
        break;

    case 'compensado':
        $titulo = 'Compensado RC — Liquidaciones Conductor';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['mes']))    { $where .= " AND cr.mes = ?";    $params[] = (int)$_GET['mes']; }
        if (!empty($_GET['anio']))   { $where .= " AND cr.anio = ?";   $params[] = (int)$_GET['anio']; }
        if (!empty($_GET['estado'])) { $where .= " AND cr.estado = ?"; $params[] = $_GET['estado']; }
        if (!empty($_GET['anio']))   { $subtitulo = 'Año ' . $_GET['anio']; }

        $sql = "SELECT cr.codigo, cr.anio, cr.mes,
                       CONCAT(p.firstname,' ',p.lastname) AS conductor, v.placa,
                       cr.flete_neto_periodo, cr.neto_pagar, cr.estado, cr.fecha_pago
                FROM compensado_rc cr
                JOIN personnel p ON p.id = cr.personnel_id
                JOIN vehicles  v ON v.id = cr.vehicle_id
                $where ORDER BY cr.anio DESC, cr.mes DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Código','Año','Mes','Conductor','Placa','Flete Neto Período','Neto a Pagar','Estado','Fecha Pago'];
        foreach ($data as $r) {
            $rows[] = [
                $r['codigo'], $r['anio'],
                $meses_nombres[(int)$r['mes']] ?? $r['mes'],
                $r['conductor'], $r['placa'],
                '$'.number_format($r['flete_neto_periodo'],0,',','.'),
                '$'.number_format($r['neto_pagar'],0,',','.'),
                $r['estado'],
                $r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : '—',
            ];
        }
        break;

    case 'flypass':
        $titulo = 'Flypass TAG — Movimientos';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['vehicle_id'])){ $where .= " AND fm.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND DATE(fm.fecha) >= ?"; $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])){ $where .= " AND DATE(fm.fecha) <= ?"; $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT fm.fecha, v.placa AS vehicle_placa, fm.placa,
                       fm.tipo_movimiento, fm.valor, fm.peaje_nombre,
                       fm.descripcion, IF(fm.legalizado,'Sí','No') AS legalizado
                FROM flypass_movimientos fm
                LEFT JOIN vehicles v ON v.id = fm.vehicle_id
                $where ORDER BY fm.fecha DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Fecha','Placa Veh.','Placa TAG','Tipo','Valor','Peaje','Descripción','Legalizado'];
        $total = 0;
        foreach ($data as $r) {
            $total += $r['valor'];
            $rows[] = [
                date('d/m/Y H:i', strtotime($r['fecha'])),
                $r['vehicle_placa'], $r['placa'], $r['tipo_movimiento'],
                '$'.number_format($r['valor'],0,',','.'),
                $r['peaje_nombre'], $r['descripcion'], $r['legalizado'],
            ];
        }
        $rows[] = ['','','','<strong>TOTAL</strong>','<strong>$'.number_format($total,0,',','.').'</strong>','','',''];
        break;

    case 'tarjeta':
        $titulo = 'Tarjeta Débito — Movimientos';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['vehicle_id'])) { $where .= " AND tm.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['tipo']))       { $where .= " AND tm.tipo = ?";        $params[] = $_GET['tipo']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND tm.fecha >= ?";     $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])){ $where .= " AND tm.fecha <= ?";     $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT tm.fecha, v.placa, tm.tipo,
                       tm.establecimiento, tm.descripcion, tm.valor, tm.cruzado
                FROM tarjeta_movimientos tm
                LEFT JOIN vehicles v ON v.id = tm.vehicle_id
                $where ORDER BY tm.fecha DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Fecha','Placa','Tipo','Establecimiento','Descripción','Valor','Estado'];
        $total = 0;
        foreach ($data as $r) {
            $total += abs($r['valor']);
            $rows[] = [
                date('d/m/Y', strtotime($r['fecha'])),
                $r['placa'], $r['tipo'], $r['establecimiento'], $r['descripcion'],
                '-$'.number_format(abs($r['valor']),0,',','.'),
                $r['cruzado'],
            ];
        }
        $rows[] = ['','','','','<strong>TOTAL</strong>','<strong>-$'.number_format($total,0,',','.').'</strong>',''];
        break;

    case 'talleres':
        $titulo = 'Directorio de Talleres';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['activos']) && $_GET['activos']==='1') { $where .= " AND active=1"; }
        if (!empty($_GET['ciudad']))       { $where .= " AND ciudad=?";       $params[] = $_GET['ciudad']; }
        if (!empty($_GET['especialidad'])) { $where .= " AND especialidad=?"; $params[] = $_GET['especialidad']; }

        $sql = "SELECT nombre, especialidad, ciudad, departamento,
                       telefono, celular, email, contacto_principal, nit,
                       IF(active,'Activo','Inactivo') AS estado
                FROM talleres $where ORDER BY nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Nombre','Especialidad','Ciudad','Dpto.','Teléfono','Celular','Email','Contacto','NIT','Estado'];
        foreach ($data as $r) {
            $rows[] = [
                $r['nombre'], $r['especialidad'], $r['ciudad'], $r['departamento'],
                $r['telefono'], $r['celular'], $r['email'],
                $r['contacto_principal'], $r['nit'], $r['estado'],
            ];
        }
        break;

    case 'flujo_caja':
        $anio = !empty($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
        $titulo = "Flujo de Caja — Año $anio";
        $sql = "SELECT mes, nombre_mes, total_fletes, total_gastos_viaje,
                       total_gastos_extras, total_anticipos, utilidad_bruta, num_viajes
                FROM view_flujo_caja_mensual WHERE anio = ? ORDER BY mes";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$anio]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Mes','Fletes Netos','Gastos Viaje','Gastos Extras','Anticipos','Utilidad Bruta','# Viajes'];
        $tf=0; $tg=0; $tge=0; $ta=0; $tu=0; $tv=0;
        foreach ($data as $r) {
            $tf  += $r['total_fletes'];
            $tg  += $r['total_gastos_viaje'];
            $tge += $r['total_gastos_extras'];
            $ta  += $r['total_anticipos'];
            $tu  += $r['utilidad_bruta'];
            $tv  += $r['num_viajes'];
            $rows[] = [
                $r['nombre_mes'],
                '$'.number_format($r['total_fletes'],0,',','.'),
                '$'.number_format($r['total_gastos_viaje'],0,',','.'),
                '$'.number_format($r['total_gastos_extras'],0,',','.'),
                '$'.number_format($r['total_anticipos'],0,',','.'),
                '$'.number_format($r['utilidad_bruta'],0,',','.'),
                $r['num_viajes'],
            ];
        }
        $rows[] = [
            '<strong>TOTAL</strong>',
            '<strong>$'.number_format($tf,0,',','.').'</strong>',
            '<strong>$'.number_format($tg,0,',','.').'</strong>',
            '<strong>$'.number_format($tge,0,',','.').'</strong>',
            '<strong>$'.number_format($ta,0,',','.').'</strong>',
            '<strong>$'.number_format($tu,0,',','.').'</strong>',
            "<strong>$tv</strong>",
        ];
        break;

    default:
        $titulo = 'Módulo no válido';
}

$generado = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($titulo); ?> — CELR</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a2e; background: #f0f4ff; }

  /* ── Pantalla ─────────────────────────── */
  .page-wrapper { max-width: 1100px; margin: 20px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.12); }

  .pdf-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #7c3aed 100%);
    color: #fff;
    padding: 24px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .pdf-header .logo-area h1 { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
  .pdf-header .logo-area p  { font-size: 11px; opacity: .8; margin-top: 2px; }
  .pdf-header .doc-info { text-align: right; font-size: 10px; opacity: .9; }
  .pdf-header .doc-info .doc-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }

  .pdf-body { padding: 24px 32px; }

  .meta-bar { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
  .meta-chip {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #93c5fd;
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 11px;
    color: #1d4ed8;
    font-weight: 600;
  }

  table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
  thead tr { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; }
  thead th { padding: 9px 8px; text-align: left; font-weight: 700; white-space: nowrap; }
  tbody tr:nth-child(even) { background: #f0f7ff; }
  tbody tr:last-child { background: #dbeafe; font-weight: 700; }
  tbody td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  tbody tr:hover { background: #dbeafe; }

  .pdf-footer {
    margin-top: 20px;
    padding: 12px 32px;
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: #64748b;
  }

  /* ── Barra acciones (solo pantalla) ──── */
  .action-bar {
    display: flex;
    gap: 10px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #0f172a, #1e3a8a);
    align-items: center;
    justify-content: flex-end;
  }
  .btn-action {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px; font-size: 12px; font-weight: 700;
    cursor: pointer; border: none; text-decoration: none;
    transition: transform .15s, box-shadow .15s;
  }
  .btn-action:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.3); }
  .btn-print { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
  .btn-back  { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
  .btn-xl    { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
  .btn-csv   { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; }

  /* ── Impresión ────────────────────────── */
  @media print {
    body { background: #fff; }
    .action-bar { display: none !important; }
    .page-wrapper { box-shadow: none; border-radius: 0; max-width: 100%; margin: 0; }
    .pdf-header { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    thead tr { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    table { font-size: 9px; }
    thead th, tbody td { padding: 5px 6px; }
    @page { margin: 10mm; size: A4 landscape; }
  }
</style>
</head>
<body>

<div class="page-wrapper">

  <!-- Barra de acciones (solo pantalla) -->
  <div class="action-bar">
    <?php
    // Construir query string preservando filtros
    $qs = http_build_query(array_merge($_GET, ['modulo' => $modulo]));
    $qs_xl  = http_build_query(array_merge($_GET, ['modulo' => $modulo, 'formato' => 'xlsx']));
    $qs_csv = http_build_query(array_merge($_GET, ['modulo' => $modulo, 'formato' => 'csv']));
    $back = match($modulo) {
        'viajes'       => 'trips.php',
        'gastos'       => 'expenses.php',
        'compensado'   => 'compensado_rc.php',
        'flypass'      => 'flypass.php',
        'tarjeta'      => 'tarjeta.php',
        'talleres'     => 'talleres.php',
        'flujo_caja'   => 'flujo_caja.php',
        'liquidaciones'=> 'settlements.php',
        default        => 'index.php',
    };
    ?>
    <a href="<?php echo $back; ?>" class="btn-action btn-back">← Volver</a>
    <a href="export.php?<?php echo htmlspecialchars($qs_xl); ?>" class="btn-action btn-xl">⬇ Excel (.xlsx)</a>
    <a href="export.php?<?php echo htmlspecialchars($qs_csv); ?>" class="btn-action btn-csv">⬇ CSV</a>
    <button onclick="window.print()" class="btn-action btn-print">🖨 Imprimir / PDF</button>
  </div>

  <!-- Encabezado del documento -->
  <div class="pdf-header">
    <div class="logo-area">
      <h1>CELR</h1>
      <p>Sistema de Gestión de Flota — Robert Serrano</p>
    </div>
    <div class="doc-info">
      <div class="doc-title"><?php echo htmlspecialchars($titulo); ?></div>
      <?php if ($subtitulo): ?>
        <div><?php echo htmlspecialchars($subtitulo); ?></div>
      <?php endif; ?>
      <div>Generado: <?php echo $generado; ?></div>
      <div><?php echo count($rows); ?> registro(s)</div>
    </div>
  </div>

  <!-- Cuerpo -->
  <div class="pdf-body">
    <?php if (!empty($_GET['fecha_desde']) || !empty($_GET['fecha_hasta']) || !empty($_GET['vehicle_id'])): ?>
    <div class="meta-bar">
      <?php if (!empty($_GET['fecha_desde'])): ?><span class="meta-chip">Desde: <?php echo $_GET['fecha_desde']; ?></span><?php endif; ?>
      <?php if (!empty($_GET['fecha_hasta'])): ?><span class="meta-chip">Hasta: <?php echo $_GET['fecha_hasta']; ?></span><?php endif; ?>
      <?php if (!empty($_GET['status'])): ?><span class="meta-chip">Estado: <?php echo htmlspecialchars($_GET['status']); ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
      <p style="text-align:center;padding:40px;color:#94a3b8;">No hay datos para mostrar con los filtros aplicados.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <?php foreach ($headers as $h): ?>
            <th><?php echo htmlspecialchars($h); ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach ($row as $cell): ?>
            <td><?php echo $cell; ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pie de página -->
  <div class="pdf-footer">
    <span>CELR — Sistema de Gestión de Flota</span>
    <span>Documento generado el <?php echo $generado; ?></span>
    <span>Confidencial — Uso interno</span>
  </div>

</div>

</body>
</html>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/export_pdf.php', $content) !== false;
$results[] = [$ok, 'export_pdf.php'];
if (!$ok) $errors++;

// --- reporte_retenciones.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Reporte de Retenciones Tributarias
 * Muestra Rete Fuente, Rete ICA, IVA retenido por viaje con resumen mensual
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/export_buttons.php';

// ─── Filtros ─────────────────────────────────────────────────────────────────
$anio       = (int)($_GET['anio']       ?? date('Y'));
$mes        = isset($_GET['mes']) && $_GET['mes'] !== '' ? (int)$_GET['mes'] : null;
$vehicle_id = $_GET['vehicle_id'] ?? '';
$client_id  = $_GET['client_id']  ?? '';

$params = [];
$where  = "WHERE YEAR(t.date_load) = ?";
$params[] = $anio;

if ($mes !== null) {
    $where .= " AND MONTH(t.date_load) = ?";
    $params[] = $mes;
}
if ($vehicle_id !== '') {
    $where .= " AND t.vehicle_id = ?";
    $params[] = (int)$vehicle_id;
}
if ($client_id !== '') {
    $where .= " AND t.client_id = ?";
    $params[] = (int)$client_id;
}

// ─── Query principal ──────────────────────────────────────────────────────────
$sql = "SELECT
    t.id,
    t.date_load,
    v.placa,
    CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
    CASE WHEN c.person_type='Jurídica' THEN c.business_name
         ELSE CONCAT(c.firstname,' ',c.lastname1) END AS cliente,
    t.manifest_number,
    t.flete_bruto,
    t.flete_liquidado,
    t.total_deductibles,
    t.flete_neto,
    t.status,
    YEAR(t.date_load)  AS anio,
    MONTH(t.date_load) AS mes
FROM trips t
LEFT JOIN vehicles  v ON v.id = t.vehicle_id
LEFT JOIN personnel p ON p.id = t.driver_id
LEFT JOIN clients   c ON c.id = t.client_id
$where
ORDER BY t.date_load DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Totales globales ─────────────────────────────────────────────────────────
$tot_bruto       = 0;
$tot_deductibles = 0;
$tot_neto        = 0;

foreach ($filas as $f) {
    $tot_bruto       += (float)$f['flete_bruto'];
    $tot_deductibles += (float)$f['total_deductibles'];
    $tot_neto        += (float)$f['flete_neto'];
}
$pct_promedio = $tot_bruto > 0 ? ($tot_deductibles / $tot_bruto * 100) : 0;

// ─── Resumen por mes ──────────────────────────────────────────────────────────
$por_mes = [];
foreach ($filas as $f) {
    $k = (int)$f['mes'];
    if (!isset($por_mes[$k])) {
        $por_mes[$k] = ['viajes' => 0, 'bruto' => 0, 'deductibles' => 0, 'neto' => 0];
    }
    $por_mes[$k]['viajes']++;
    $por_mes[$k]['bruto']       += (float)$f['flete_bruto'];
    $por_mes[$k]['deductibles'] += (float)$f['total_deductibles'];
    $por_mes[$k]['neto']        += (float)$f['flete_neto'];
}
ksort($por_mes);

// ─── Listas para filtros ──────────────────────────────────────────────────────
$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
$clients  = $pdo->query(
    "SELECT id,
            CASE WHEN person_type='Jurídica' THEN business_name
                 ELSE CONCAT(firstname,' ',lastname1) END AS nombre
     FROM clients WHERE active=1 ORDER BY nombre"
)->fetchAll();

$meses_nombres = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

function fmt($v) {
    return '$' . number_format(abs((float)$v), 0, ',', '.');
}

$status_labels = [
    'pendiente'   => ['label' => 'Pendiente',   'cls' => 'bg-yellow-100 text-yellow-800'],
    'liquidado'   => ['label' => 'Liquidado',    'cls' => 'bg-green-100 text-green-800'],
    'cancelado'   => ['label' => 'Cancelado',    'cls' => 'bg-red-100 text-red-800'],
    'en_transito' => ['label' => 'En Tránsito',  'cls' => 'bg-blue-100 text-blue-800'],
];
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Encabezado -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Reporte de Retenciones Tributarias</h2>
            <p class="mt-1 text-sm text-gray-500">
                Rete Fuente · Rete ICA · IVA Retenido — resumen por viaje y por mes.
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3 flex-wrap no-print">
            <?php exportButtons('retenciones', $_GET); ?>
            <button onclick="window.print()"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                       bg-gradient-to-r from-gray-600 to-gray-700 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
                🖨 Imprimir
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end no-print">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Año</label>
            <select name="anio" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $anio == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Mes</label>
            <select name="mes" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($meses_nombres as $num => $nom): ?>
                    <option value="<?php echo $num; ?>" <?php echo $mes === $num ? 'selected' : ''; ?>><?php echo $nom; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Vehículo</label>
            <select name="vehicle_id" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($vehicles as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo $vehicle_id == $v['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($v['placa']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Cliente</label>
            <select name="client_id" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($clients as $cl): ?>
                    <option value="<?php echo $cl['id']; ?>" <?php echo $client_id == $cl['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cl['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-700 text-white text-sm rounded hover:bg-gray-800">Filtrar</button>
            <a href="reporte_retenciones.php" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300">Limpiar</a>
        </div>
    </form>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-xs text-blue-500 uppercase font-semibold mb-1">Total Flete Bruto</p>
            <p class="text-xl font-bold text-blue-700"><?php echo fmt($tot_bruto); ?></p>
            <p class="text-xs text-blue-400 mt-1"><?php echo count($filas); ?> viajes</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-xs text-red-500 uppercase font-semibold mb-1">Total Retenciones</p>
            <p class="text-xl font-bold text-red-700"><?php echo fmt($tot_deductibles); ?></p>
            <p class="text-xs text-red-400 mt-1">Rete Fuente + ICA + IVA</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-xs text-green-500 uppercase font-semibold mb-1">Total Flete Neto</p>
            <p class="text-xl font-bold text-green-700"><?php echo fmt($tot_neto); ?></p>
            <p class="text-xs text-green-400 mt-1">Después de retenciones</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <p class="text-xs text-amber-500 uppercase font-semibold mb-1">% Retención Promedio</p>
            <p class="text-xl font-bold text-amber-700"><?php echo number_format($pct_promedio, 2, ',', '.'); ?>%</p>
            <p class="text-xs text-amber-400 mt-1">Ret. / Bruto × 100</p>
        </div>
    </div>

    <!-- Tabla detallada -->
    <?php if (empty($filas)): ?>
        <div class="bg-white shadow rounded-lg p-12 text-center text-gray-400">
            <p class="text-lg">No hay viajes con retenciones para los filtros seleccionados.</p>
            <p class="text-sm mt-2">Ajusta los filtros e intenta de nuevo.</p>
        </div>
    <?php else: ?>
    <div class="bg-white shadow rounded-lg overflow-x-auto mb-6">
        <table class="min-w-full text-xs">
            <thead>
                <tr class="bg-gray-700 text-white">
                    <th class="px-3 py-3 text-left">Fecha</th>
                    <th class="px-3 py-3 text-left">Placa</th>
                    <th class="px-3 py-3 text-left">Conductor</th>
                    <th class="px-3 py-3 text-left">Cliente</th>
                    <th class="px-3 py-3 text-left">Manifiesto</th>
                    <th class="px-3 py-3 text-right">Flete Bruto</th>
                    <th class="px-3 py-3 text-right">Retenciones</th>
                    <th class="px-3 py-3 text-right font-bold">Flete Neto</th>
                    <th class="px-3 py-3 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($filas as $i => $f):
                    $bruto  = (float)$f['flete_bruto'];
                    $deduct = (float)$f['total_deductibles'];
                    $neto   = (float)$f['flete_neto'];
                    $row_bg = $i % 2 === 0 ? '' : 'bg-gray-50';
                    $st     = $f['status'] ?? '';
                    $st_cls = $status_labels[$st]['cls']   ?? 'bg-gray-100 text-gray-700';
                    $st_lbl = $status_labels[$st]['label'] ?? ucfirst($st);
                ?>
                <tr class="hover:bg-blue-50 <?php echo $row_bg; ?>">
                    <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                        <?php echo htmlspecialchars($f['date_load']); ?>
                    </td>
                    <td class="px-3 py-2 font-mono font-semibold text-gray-800">
                        <?php echo htmlspecialchars($f['placa'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-700">
                        <?php echo htmlspecialchars($f['conductor'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-700">
                        <?php echo htmlspecialchars($f['cliente'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-600">
                        <?php echo htmlspecialchars($f['manifest_number'] ?? '—'); ?>
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700"><?php echo fmt($bruto); ?></td>
                    <td class="px-3 py-2 text-right text-red-600 font-medium"><?php echo fmt($deduct); ?></td>
                    <td class="px-3 py-2 text-right font-bold text-green-700"><?php echo fmt($neto); ?></td>
                    <td class="px-3 py-2 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold <?php echo $st_cls; ?>">
                            <?php echo htmlspecialchars($st_lbl); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Fila de totales -->
                <tr class="bg-gray-800 text-white font-bold border-t-2 border-gray-600">
                    <td class="px-3 py-3" colspan="5">TOTAL (<?php echo count($filas); ?> viajes)</td>
                    <td class="px-3 py-3 text-right"><?php echo fmt($tot_bruto); ?></td>
                    <td class="px-3 py-3 text-right text-red-300"><?php echo fmt($tot_deductibles); ?></td>
                    <td class="px-3 py-3 text-right text-green-300 text-sm"><?php echo fmt($tot_neto); ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Resumen por mes -->
    <?php if (!empty($por_mes)): ?>
    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700 text-sm">Resumen por Mes — <?php echo $anio; ?></h3>
        </div>
        <table class="min-w-full text-xs">
            <thead>
                <tr class="bg-gray-100 text-gray-600">
                    <th class="px-4 py-3 text-left">Mes</th>
                    <th class="px-4 py-3 text-right"># Viajes</th>
                    <th class="px-4 py-3 text-right">Flete Bruto</th>
                    <th class="px-4 py-3 text-right">Retenciones</th>
                    <th class="px-4 py-3 text-right">Flete Neto</th>
                    <th class="px-4 py-3 text-right">% Retención</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($por_mes as $num_mes => $d):
                    $pct = $d['bruto'] > 0 ? ($d['deductibles'] / $d['bruto'] * 100) : 0;
                ?>
                <tr class="hover:bg-blue-50">
                    <td class="px-4 py-2 font-medium text-gray-700">
                        <?php echo ($meses_nombres[$num_mes] ?? $num_mes) . ' ' . $anio; ?>
                    </td>
                    <td class="px-4 py-2 text-right text-gray-600"><?php echo $d['viajes']; ?></td>
                    <td class="px-4 py-2 text-right text-gray-700"><?php echo fmt($d['bruto']); ?></td>
                    <td class="px-4 py-2 text-right text-red-600 font-medium"><?php echo fmt($d['deductibles']); ?></td>
                    <td class="px-4 py-2 text-right text-green-700 font-semibold"><?php echo fmt($d['neto']); ?></td>
                    <td class="px-4 py-2 text-right text-amber-700 font-semibold">
                        <?php echo number_format($pct, 2, ',', '.'); ?>%
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Total mensual -->
                <tr class="bg-gray-700 text-white font-bold border-t-2 border-gray-500">
                    <td class="px-4 py-3">TOTAL <?php echo $anio; ?></td>
                    <td class="px-4 py-3 text-right"><?php echo count($filas); ?></td>
                    <td class="px-4 py-3 text-right"><?php echo fmt($tot_bruto); ?></td>
                    <td class="px-4 py-3 text-right text-red-300"><?php echo fmt($tot_deductibles); ?></td>
                    <td class="px-4 py-3 text-right text-green-300"><?php echo fmt($tot_neto); ?></td>
                    <td class="px-4 py-3 text-right text-amber-300">
                        <?php echo number_format($pct_promedio, 2, ',', '.'); ?>%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<style>
@media print {
    .no-print, form, button, nav { display: none !important; }
    .shadow { box-shadow: none !important; }
    body { font-size: 11px; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
    thead { display: table-header-group; }
}
</style>

<?php include 'includes/footer.php'; ?>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/reporte_retenciones.php', $content) !== false;
$results[] = [$ok, 'reporte_retenciones.php'];
if (!$ok) $errors++;

// --- rndc.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Módulo RNDC — Manifiestos Electrónicos de Carga (Colombia)
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/export_buttons.php';

// Filtros
$buscar      = trim($_GET['buscar']      ?? '');
$vehicle_id  = $_GET['vehicle_id']  ?? '';
$estado      = $_GET['estado']      ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

$params = [];
$where  = "WHERE 1=1";

if ($buscar) {
    $where .= " AND (m.nro_manifiesto LIKE ? OR m.autorizacion_rndc LIKE ? OR m.remitente_nombre LIKE ? OR m.destinatario_nombre LIKE ?)";
    $params[] = "%$buscar%"; $params[] = "%$buscar%";
    $params[] = "%$buscar%"; $params[] = "%$buscar%";
}
if ($vehicle_id) { $where .= " AND m.vehicle_id = ?";  $params[] = (int)$vehicle_id; }
if ($estado)     { $where .= " AND m.estado = ?";       $params[] = $estado; }
if ($fecha_desde){ $where .= " AND m.fecha_expedicion >= ?"; $params[] = $fecha_desde; }
if ($fecha_hasta){ $where .= " AND m.fecha_expedicion <= ?"; $params[] = $fecha_hasta; }

$sql = "SELECT m.*,
               v.placa,
               CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor
        FROM manifiestos_rndc m
        LEFT JOIN vehicles  v ON v.id = m.vehicle_id
        LEFT JOIN personnel p ON p.id = m.driver_id
        $where
        ORDER BY m.fecha_expedicion DESC, m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$manifiestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resumen
$total_activos = 0;
$total_flete   = 0;
foreach ($manifiestos as $m) {
    if ($m['estado'] === 'Activo') $total_activos++;
    $total_flete += (float)$m['flete_pactado'];
}

$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
$msg = $_GET['msg'] ?? '';
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Encabezado -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Manifiestos RNDC</h2>
            <p class="mt-1 text-sm text-gray-500">Manifiestos Electrónicos de Carga — Decreto 1079/2015 Colombia</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3 flex-wrap">
            <?php exportButtons('rndc', $_GET); ?>
            <a href="rndc_form.php"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                + Nuevo Manifiesto
            </a>
        </div>
    </div>

    <?php if ($msg === 'saved'): ?>
        <div class="alert-card alert-card-success">
            <span class="alert-card-icon">✅</span>
            <div class="alert-card-body"><div class="alert-card-title">Éxito</div><div class="alert-card-text">Manifiesto guardado correctamente.</div></div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php elseif ($msg === 'anulado'): ?>
        <div class="alert-card alert-card-warning">
            <span class="alert-card-icon">⚠️</span>
            <div class="alert-card-body"><div class="alert-card-title">Atención</div><div class="alert-card-text">Manifiesto anulado.</div></div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php endif; ?>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-xs text-blue-600 uppercase font-semibold mb-1">Total Manifiestos</p>
            <p class="text-2xl font-bold text-blue-800"><?php echo count($manifiestos); ?></p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-xs text-green-600 uppercase font-semibold mb-1">Activos</p>
            <p class="text-2xl font-bold text-green-700"><?php echo $total_activos; ?></p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <p class="text-xs text-purple-600 uppercase font-semibold mb-1">Flete Total</p>
            <p class="text-2xl font-bold text-purple-700">$<?php echo number_format($total_flete, 0, ',', '.'); ?></p>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
            <input type="text" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>"
                   placeholder="Nro. manifiesto, autorización, remitente..."
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Vehículo</label>
            <select name="vehicle_id" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($vehicles as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo $vehicle_id == $v['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($v['placa']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Estado</label>
            <select name="estado" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <option value="Borrador"   <?php echo $estado==='Borrador'   ? 'selected':''; ?>>Borrador</option>
                <option value="Activo"     <?php echo $estado==='Activo'     ? 'selected':''; ?>>Activo</option>
                <option value="Finalizado" <?php echo $estado==='Finalizado' ? 'selected':''; ?>>Finalizado</option>
                <option value="Anulado"    <?php echo $estado==='Anulado'    ? 'selected':''; ?>>Anulado</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
            <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>"
                   class="border border-gray-300 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Hasta</label>
            <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>"
                   class="border border-gray-300 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-gray-700 text-white text-sm rounded hover:bg-gray-800">Buscar</button>
            <a href="rndc.php" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300">Limpiar</a>
        </div>
    </form>

    <!-- Tabla -->
    <?php if (empty($manifiestos)): ?>
        <div class="bg-white shadow rounded-lg p-12 text-center text-gray-400">
            <p class="text-4xl mb-4">📋</p>
            <p class="text-lg">No hay manifiestos registrados.</p>
            <a href="rndc_form.php" class="mt-4 inline-block text-blue-600 hover:underline">Crear el primero →</a>
        </div>
    <?php else: ?>
    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Nro. Manifiesto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Autorización</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Placa</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Conductor</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Remitente</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Destinatario</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Ruta</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Flete</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($manifiestos as $m): ?>
                <?php
                    $estado_cfg = match($m['estado']) {
                        'Activo'     => ['bg-green-100 text-green-800',  'Activo'],
                        'Finalizado' => ['bg-blue-100 text-blue-800',    'Finalizado'],
                        'Anulado'    => ['bg-red-100 text-red-700',      'Anulado'],
                        default      => ['bg-gray-100 text-gray-600',    'Borrador'],
                    };
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono font-semibold text-blue-700">
                        <a href="rndc_details.php?id=<?php echo $m['id']; ?>" class="hover:underline">
                            <?php echo htmlspecialchars($m['nro_manifiesto']); ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600"><?php echo htmlspecialchars($m['autorizacion_rndc'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-xs"><?php echo date('d/m/Y', strtotime($m['fecha_expedicion'])); ?></td>
                    <td class="px-4 py-3 font-mono font-semibold"><?php echo htmlspecialchars($m['placa'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-xs"><?php echo htmlspecialchars($m['conductor'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-xs max-w-xs truncate"><?php echo htmlspecialchars($m['remitente_nombre']); ?></td>
                    <td class="px-4 py-3 text-xs max-w-xs truncate"><?php echo htmlspecialchars($m['destinatario_nombre']); ?></td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        <?php echo htmlspecialchars($m['origen']); ?> →<br>
                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($m['destino']); ?></span>
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-semibold text-gray-800">
                        $<?php echo number_format($m['flete_pactado'], 0, ',', '.'); ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $estado_cfg[0]; ?>">
                            <?php echo $estado_cfg[1]; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center space-x-2 whitespace-nowrap">
                        <a href="rndc_details.php?id=<?php echo $m['id']; ?>"
                           class="text-blue-600 hover:underline text-xs">Ver</a>
                        <?php if ($m['estado'] !== 'Anulado'): ?>
                        <a href="rndc_form.php?id=<?php echo $m['id']; ?>"
                           class="text-yellow-600 hover:underline text-xs">Editar</a>
                        <a href="rndc_delete.php?id=<?php echo $m['id']; ?>"
                           onclick="return confirm('¿Anular este manifiesto?')"
                           class="text-red-500 hover:underline text-xs">Anular</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/rndc.php', $content) !== false;
$results[] = [$ok, 'rndc.php'];
if (!$ok) $errors++;

// --- rndc_delete.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Anular manifiesto RNDC (soft delete — nunca elimina)
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare("UPDATE manifiestos_rndc SET estado='Anulado' WHERE id=?")->execute([$id]);
}
header('Location: rndc.php?msg=anulado');
exit;

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/rndc_delete.php', $content) !== false;
$results[] = [$ok, 'rndc_delete.php'];
if (!$ok) $errors++;

// --- rndc_details.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Detalle / Impresión — Manifiesto Electrónico de Carga RNDC
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: rndc.php'); exit; }

$stmt = $pdo->prepare("SELECT m.*,
    v.placa, v.brand, v.model, v.year,
    CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
    p.license_number
    FROM manifiestos_rndc m
    LEFT JOIN vehicles  v ON v.id = m.vehicle_id
    LEFT JOIN personnel p ON p.id = m.driver_id
    WHERE m.id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) die("Manifiesto #$id no encontrado.");

$generado = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Manifiesto <?php echo htmlspecialchars($m['nro_manifiesto']); ?> — CELR</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #f0f4ff; }

.page-wrapper { max-width: 900px; margin: 20px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.12); }

/* Header oficial */
.doc-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 60%, #7c3aed 100%);
    color: #fff;
    padding: 20px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.doc-header .org h1 { font-size: 20px; font-weight: 900; letter-spacing: 2px; }
.doc-header .org p  { font-size: 10px; opacity: .8; margin-top: 2px; }
.doc-header .doc-id { text-align: right; }
.doc-header .doc-id .nro { font-size: 18px; font-weight: 800; }
.doc-header .doc-id .tipo { font-size: 11px; opacity: .85; }

.doc-body { padding: 24px 28px; }

/* Secciones */
.section { margin-bottom: 16px; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
.section-title {
    background: #1e3a8a;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    padding: 6px 12px;
}
.section-body { padding: 12px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px 16px; }
.field label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .3px; display: block; margin-bottom: 2px; }
.field span  { font-size: 11px; color: #111; font-weight: 600; display: block; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; min-height: 18px; }

/* Badges */
.badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 700; }
.badge-green  { background: #d1fae5; color: #065f46; }
.badge-blue   { background: #dbeafe; color: #1e3a8a; }
.badge-gray   { background: #f3f4f6; color: #374151; }
.badge-red    { background: #fee2e2; color: #991b1b; }

/* Tabla mercancía */
table.merch { width: 100%; border-collapse: collapse; }
table.merch th { background: #e8edf5; font-size: 9px; text-transform: uppercase; padding: 6px 8px; text-align: left; border: 1px solid #d1d5db; }
table.merch td { padding: 7px 8px; border: 1px solid #e5e7eb; font-size: 11px; }

/* Financiero */
.fin-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f0f0f0; }
.fin-row:last-child { border-bottom: none; font-weight: 700; font-size: 13px; color: #1e3a8a; }
.fin-label { color: #6b7280; font-size: 10px; }

/* Firmas */
.firma-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 8px; }
.firma-box { border-top: 2px solid #374151; padding-top: 8px; text-align: center; }
.firma-box .firma-name { font-size: 11px; font-weight: 700; margin-bottom: 2px; }
.firma-box .firma-label { font-size: 9px; color: #6b7280; text-transform: uppercase; }

/* Footer */
.doc-footer {
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    padding: 10px 28px;
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: #94a3b8;
}

/* Barra de acciones */
.action-bar {
    background: linear-gradient(135deg, #0f172a, #1e3a8a);
    padding: 12px 28px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; }
.btn-print { background: linear-gradient(135deg,#10b981,#059669); color:#fff; }
.btn-back  { background: rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); }
.btn-edit  { background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }

@media print {
    body { background: #fff; }
    .action-bar { display: none !important; }
    .page-wrapper { box-shadow: none; border-radius: 0; max-width: 100%; margin: 0; }
    .doc-header, .section-title { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    @page { margin: 10mm; size: A4 portrait; }
}
</style>
</head>
<body>
<div class="page-wrapper">

    <!-- Acciones -->
    <div class="action-bar">
        <a href="rndc.php" class="btn btn-back">← Volver</a>
        <?php if ($m['estado'] !== 'Anulado'): ?>
        <a href="rndc_form.php?id=<?php echo $m['id']; ?>" class="btn btn-edit">✏ Editar</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-print">🖨 Imprimir / PDF</button>
    </div>

    <!-- Encabezado oficial -->
    <div class="doc-header">
        <div class="org">
            <h1>CELR</h1>
            <p>Sistema de Gestión de Flota — Robert Serrano</p>
            <p>Manifiesto Electrónico de Carga — Decreto 1079/2015</p>
        </div>
        <div class="doc-id">
            <div class="tipo">MANIFIESTO DE CARGA</div>
            <div class="nro"><?php echo htmlspecialchars($m['nro_manifiesto']); ?></div>
            <?php
            $badge_class = match($m['estado']) {
                'Activo'     => 'badge-green',
                'Finalizado' => 'badge-blue',
                'Anulado'    => 'badge-red',
                default      => 'badge-gray',
            };
            ?>
            <span class="badge <?php echo $badge_class; ?>"><?php echo $m['estado']; ?></span>
        </div>
    </div>

    <div class="doc-body">

        <!-- Identificación -->
        <div class="section">
            <div class="section-title">Identificación</div>
            <div class="section-body">
                <div class="grid-3">
                    <div class="field"><label>Autorización RNDC</label><span><?php echo htmlspecialchars($m['autorizacion_rndc'] ?? '—'); ?></span></div>
                    <div class="field"><label>Nro. Remesa</label><span><?php echo htmlspecialchars($m['nro_remesa'] ?? '—'); ?></span></div>
                    <div class="field"><label>Fecha Expedición</label><span><?php echo date('d/m/Y', strtotime($m['fecha_expedicion'])); ?></span></div>
                    <div class="field"><label>Fecha Vencimiento</label><span><?php echo $m['fecha_vencimiento'] ? date('d/m/Y', strtotime($m['fecha_vencimiento'])) : '—'; ?></span></div>
                    <div class="field"><label>Empresa Transportadora</label><span><?php echo htmlspecialchars($m['empresa_transporte']); ?></span></div>
                    <div class="field"><label>NIT Empresa</label><span><?php echo htmlspecialchars($m['nit_empresa'] ?? '—'); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Vehículo y Conductor -->
        <div class="section">
            <div class="section-title">Vehículo y Conductor</div>
            <div class="section-body">
                <div class="grid-3">
                    <div class="field"><label>Placa</label><span><?php echo htmlspecialchars($m['placa'] ?? '—'); ?></span></div>
                    <div class="field"><label>Vehículo</label><span><?php echo htmlspecialchars(trim(($m['brand']??'').' '.($m['model']??'').' '.($m['year']??'')) ?: '—'); ?></span></div>
                    <div class="field"><label>Tipo</label><span><?php echo htmlspecialchars($m['tipo_vehiculo'] ?? '—'); ?></span></div>
                    <div class="field"><label>Conductor</label><span><?php echo htmlspecialchars($m['conductor'] ?? '—'); ?></span></div>
                    <div class="field"><label>Licencia</label><span><?php echo htmlspecialchars($m['license_number'] ?? '—'); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Remitente y Destinatario -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div class="section" style="margin-bottom:0">
                <div class="section-title">Remitente (Origen)</div>
                <div class="section-body">
                    <div class="field" style="margin-bottom:6px"><label>Nombre</label><span><?php echo htmlspecialchars($m['remitente_nombre']); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>NIT/Cédula</label><span><?php echo htmlspecialchars($m['remitente_nit'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Código RNDC</label><span><?php echo htmlspecialchars($m['remitente_codigo'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Ciudad</label><span><?php echo htmlspecialchars($m['remitente_ciudad'] ?? '—'); ?></span></div>
                    <div class="field"><label>Dirección</label><span><?php echo htmlspecialchars($m['remitente_direccion'] ?? '—'); ?></span></div>
                </div>
            </div>
            <div class="section" style="margin-bottom:0">
                <div class="section-title">Destinatario (Destino)</div>
                <div class="section-body">
                    <div class="field" style="margin-bottom:6px"><label>Nombre</label><span><?php echo htmlspecialchars($m['destinatario_nombre']); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>NIT/Cédula</label><span><?php echo htmlspecialchars($m['destinatario_nit'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Código RNDC</label><span><?php echo htmlspecialchars($m['destinatario_codigo'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Ciudad</label><span><?php echo htmlspecialchars($m['destinatario_ciudad'] ?? '—'); ?></span></div>
                    <div class="field"><label>Dirección</label><span><?php echo htmlspecialchars($m['destinatario_direccion'] ?? '—'); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Ruta y Mercancía -->
        <div class="section">
            <div class="section-title">Ruta y Mercancía</div>
            <div class="section-body">
                <div class="grid-2" style="margin-bottom:12px">
                    <div class="field"><label>Origen</label><span><?php echo htmlspecialchars($m['origen']); ?></span></div>
                    <div class="field"><label>Destino</label><span><?php echo htmlspecialchars($m['destino']); ?></span></div>
                </div>
                <table class="merch">
                    <thead>
                        <tr>
                            <th>Descripción Mercancía</th>
                            <th style="width:120px">Peso (kg)</th>
                            <th style="width:100px">Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($m['descripcion_mercancia'] ?? '—'); ?></td>
                            <td><?php echo $m['peso_kg'] ? number_format($m['peso_kg'],2,',','.') : '—'; ?></td>
                            <td><?php echo $m['unidades'] ?? '—'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financiero -->
        <div class="section">
            <div class="section-title">Información Financiera</div>
            <div class="section-body">
                <div class="grid-2">
                    <div>
                        <div class="fin-row"><span class="fin-label">Flete Pactado</span><span>$<?php echo number_format($m['flete_pactado'],0,',','.'); ?></span></div>
                        <div class="fin-row"><span class="fin-label">Anticipo</span><span>$<?php echo number_format($m['anticipo'],0,',','.'); ?></span></div>
                        <div class="fin-row"><span class="fin-label">Saldo</span><span>$<?php echo number_format($m['saldo'],0,',','.'); ?></span></div>
                    </div>
                    <div>
                        <div class="field" style="margin-bottom:8px"><label>Cargue pagado por</label><span><?php echo htmlspecialchars($m['cargue_pagado_por'] ?? '—'); ?></span></div>
                        <div class="field" style="margin-bottom:8px"><label>Descargue pagado por</label><span><?php echo htmlspecialchars($m['descargue_pagado_por'] ?? '—'); ?></span></div>
                        <div class="field" style="margin-bottom:8px"><label>Lugar de pago</label><span><?php echo htmlspecialchars($m['lugar_pago'] ?? '—'); ?></span></div>
                        <div class="field"><label>Fecha pago saldo</label><span><?php echo $m['fecha_pago_saldo'] ? date('d/m/Y', strtotime($m['fecha_pago_saldo'])) : '—'; ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($m['notas']): ?>
        <!-- Notas -->
        <div class="section">
            <div class="section-title">Observaciones</div>
            <div class="section-body" style="font-style:italic;color:#555"><?php echo htmlspecialchars($m['notas']); ?></div>
        </div>
        <?php endif; ?>

        <!-- Firmas -->
        <div class="section">
            <div class="section-title">Firmas y Declaraciones</div>
            <div class="section-body">
                <p style="font-size:9px;color:#6b7280;margin-bottom:16px">
                    Declaro que la información contenida en este manifiesto es verídica y que la mercancía descrita
                    se entrega en las condiciones pactadas. Este documento se rige por el Decreto 1079 de 2015
                    y la Resolución 5380 de 2014 del Ministerio de Transporte de Colombia.
                </p>
                <div class="firma-grid">
                    <div class="firma-box">
                        <div style="height:40px"></div>
                        <div class="firma-name"><?php echo htmlspecialchars($m['conductor'] ?? '___________________________'); ?></div>
                        <div class="firma-label">Conductor — Licencia: <?php echo htmlspecialchars($m['license_number'] ?? '___________'); ?></div>
                    </div>
                    <div class="firma-box">
                        <div style="height:40px"></div>
                        <div class="firma-name"><?php echo htmlspecialchars($m['empresa_transporte']); ?></div>
                        <div class="firma-label">Empresa Transportadora — NIT: <?php echo htmlspecialchars($m['nit_empresa'] ?? '___________'); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="doc-footer">
        <span>Manifiesto #<?php echo htmlspecialchars($m['nro_manifiesto']); ?></span>
        <span>Generado: <?php echo $generado; ?></span>
        <span>CELR — Sistema de Gestión de Flota</span>
    </div>
</div>
</body>
</html>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/rndc_details.php', $content) !== false;
$results[] = [$ok, 'rndc_details.php'];
if (!$ok) $errors++;

// --- rndc_form.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Formulario Manifiesto RNDC — Crear / Editar
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/functions.php';

$csrf_token = getCsrfToken();
$editMode   = isset($_GET['id']);
$rndcId     = $editMode ? (int)$_GET['id'] : 0;

if ($editMode) {
    $stmt = $pdo->prepare("SELECT * FROM manifiestos_rndc WHERE id = ?");
    $stmt->execute([$rndcId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) die("Manifiesto #$rndcId no encontrado.");
} else {
    $r = [
        'id' => null, 'trip_id' => '', 'vehicle_id' => '', 'driver_id' => '',
        'nro_manifiesto' => '', 'autorizacion_rndc' => '', 'nro_remesa' => '',
        'fecha_expedicion' => date('Y-m-d'), 'fecha_vencimiento' => '',
        'empresa_transporte' => '', 'nit_empresa' => '',
        'remitente_nombre' => '', 'remitente_nit' => '', 'remitente_codigo' => '',
        'remitente_direccion' => '', 'remitente_ciudad' => '',
        'destinatario_nombre' => '', 'destinatario_nit' => '', 'destinatario_codigo' => '',
        'destinatario_direccion' => '', 'destinatario_ciudad' => '',
        'origen' => '', 'destino' => '',
        'descripcion_mercancia' => '', 'peso_kg' => '', 'unidades' => '', 'tipo_vehiculo' => '',
        'flete_pactado' => '', 'anticipo' => '', 'saldo' => '',
        'cargue_pagado_por' => '', 'descargue_pagado_por' => '',
        'lugar_pago' => '', 'fecha_pago_saldo' => '',
        'estado' => 'Activo', 'notas' => '',
    ];
}

$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
$drivers  = $pdo->query("SELECT id, CONCAT(firstname,' ',IFNULL(lastname,'')) AS nombre FROM personnel WHERE active=1 AND role='conductor' ORDER BY firstname")->fetchAll();
$trips_list = $pdo->query("SELECT t.id, v.placa, t.date_load, t.origin, t.destination FROM trips t LEFT JOIN vehicles v ON v.id=t.vehicle_id ORDER BY t.date_load DESC LIMIT 200")->fetchAll();

$msg = $_GET['msg'] ?? '';
?>

<div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="rndc.php" class="text-gray-400 hover:text-gray-600">← Manifiestos</a>
        <span class="text-gray-300">/</span>
        <h2 class="text-xl font-bold text-gray-900">
            <?php echo $editMode ? 'Editar Manifiesto #'.$rndcId : 'Nuevo Manifiesto RNDC'; ?>
        </h2>
    </div>

    <?php if ($msg === 'saved'): ?>
        <div class="alert-card alert-card-success">
            <span class="alert-card-icon">✅</span>
            <div class="alert-card-body"><div class="alert-card-title">Éxito</div><div class="alert-card-text">Manifiesto guardado. <a href="rndc.php" class="underline ml-2">Ver todos</a></div></div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php endif; ?>

    <form action="save_rndc.php" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="rndc_id"   value="<?php echo $rndcId; ?>">

        <!-- ① Identificación -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">① Identificación del Manifiesto</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Manifiesto *</label>
                    <input type="text" name="nro_manifiesto" required
                           value="<?php echo htmlspecialchars($r['nro_manifiesto']); ?>"
                           placeholder="MFC-2024-000123"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Autorización RNDC</label>
                    <input type="text" name="autorizacion_rndc"
                           value="<?php echo htmlspecialchars($r['autorizacion_rndc'] ?? ''); ?>"
                           placeholder="Código RNDC"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Remesa</label>
                    <input type="text" name="nro_remesa"
                           value="<?php echo htmlspecialchars($r['nro_remesa'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="estado" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <?php foreach (['Borrador','Activo','Finalizado','Anulado'] as $e): ?>
                            <option value="<?php echo $e; ?>" <?php echo $r['estado']===$e?'selected':''; ?>><?php echo $e; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Expedición *</label>
                    <input type="date" name="fecha_expedicion" required value="<?php echo $r['fecha_expedicion']; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" value="<?php echo $r['fecha_vencimiento'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ② Vehículo y Conductor -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">② Vehículo y Conductor</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehículo *</label>
                    <select name="vehicle_id" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $r['vehicle_id']==$v['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($v['placa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conductor *</label>
                    <select name="driver_id" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $r['driver_id']==$d['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($d['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Viaje Asociado <span class="text-gray-400">(opcional)</span></label>
                    <select name="trip_id" id="trip_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                            onchange="autoFillTrip(this)">
                        <option value="">-- Sin viaje asociado --</option>
                        <?php foreach ($trips_list as $t): ?>
                            <option value="<?php echo $t['id']; ?>"
                                    data-origen="<?php echo htmlspecialchars($t['origin']); ?>"
                                    data-destino="<?php echo htmlspecialchars($t['destination']); ?>"
                                    <?php echo $r['trip_id']==$t['id']?'selected':''; ?>>
                                #<?php echo $t['id']; ?> — <?php echo htmlspecialchars($t['placa']); ?>
                                — <?php echo date('d/m/Y', strtotime($t['date_load'])); ?>
                                — <?php echo htmlspecialchars($t['origin']); ?> → <?php echo htmlspecialchars($t['destination']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ③ Empresa de Transporte -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">③ Empresa de Transporte</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                    <input type="text" name="empresa_transporte" required
                           value="<?php echo htmlspecialchars($r['empresa_transporte']); ?>"
                           placeholder="Transportes del Oriente S.A.S."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
                    <input type="text" name="nit_empresa"
                           value="<?php echo htmlspecialchars($r['nit_empresa'] ?? ''); ?>"
                           placeholder="900.123.456-7"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
            </div>
        </div>

        <!-- ④ Remitente -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">④ Remitente (Quien Envía)</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Razón Social *</label>
                    <input type="text" name="remitente_nombre" required
                           value="<?php echo htmlspecialchars($r['remitente_nombre']); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIT / Cédula</label>
                    <input type="text" name="remitente_nit"
                           value="<?php echo htmlspecialchars($r['remitente_nit'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código RNDC</label>
                    <input type="text" name="remitente_codigo"
                           value="<?php echo htmlspecialchars($r['remitente_codigo'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" name="remitente_ciudad"
                           value="<?php echo htmlspecialchars($r['remitente_ciudad'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" name="remitente_direccion"
                           value="<?php echo htmlspecialchars($r['remitente_direccion'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑤ Destinatario -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑤ Destinatario (Quien Recibe)</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Razón Social *</label>
                    <input type="text" name="destinatario_nombre" required
                           value="<?php echo htmlspecialchars($r['destinatario_nombre']); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIT / Cédula</label>
                    <input type="text" name="destinatario_nit"
                           value="<?php echo htmlspecialchars($r['destinatario_nit'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código RNDC</label>
                    <input type="text" name="destinatario_codigo"
                           value="<?php echo htmlspecialchars($r['destinatario_codigo'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" name="destinatario_ciudad"
                           value="<?php echo htmlspecialchars($r['destinatario_ciudad'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" name="destinatario_direccion"
                           value="<?php echo htmlspecialchars($r['destinatario_direccion'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑥ Ruta y Mercancía -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑥ Ruta y Mercancía</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Origen *</label>
                    <input type="text" name="origen" required id="campo_origen"
                           value="<?php echo htmlspecialchars($r['origen']); ?>"
                           placeholder="Medellín, Antioquia"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destino *</label>
                    <input type="text" name="destino" required id="campo_destino"
                           value="<?php echo htmlspecialchars($r['destino']); ?>"
                           placeholder="Bogotá, Cundinamarca"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción Mercancía</label>
                    <input type="text" name="descripcion_mercancia"
                           value="<?php echo htmlspecialchars($r['descripcion_mercancia'] ?? ''); ?>"
                           placeholder="Arena de río, cemento, mercancía general..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                    <input type="number" step="0.01" name="peso_kg"
                           value="<?php echo $r['peso_kg'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidades</label>
                    <input type="number" name="unidades"
                           value="<?php echo $r['unidades'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Vehículo</label>
                    <input type="text" name="tipo_vehiculo"
                           value="<?php echo htmlspecialchars($r['tipo_vehiculo'] ?? ''); ?>"
                           placeholder="Tractocamión, Camión rígido, Minimula..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑦ Financiero -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑦ Información Financiera</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Flete Pactado</label>
                    <input type="number" step="0.01" name="flete_pactado" id="flete_pactado"
                           value="<?php echo $r['flete_pactado'] ?? '0'; ?>"
                           oninput="calcSaldo()"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Anticipo</label>
                    <input type="number" step="0.01" name="anticipo" id="anticipo"
                           value="<?php echo $r['anticipo'] ?? '0'; ?>"
                           oninput="calcSaldo()"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Saldo <span class="text-gray-400 font-normal">(auto)</span></label>
                    <input type="number" step="0.01" name="saldo" id="saldo"
                           value="<?php echo $r['saldo'] ?? '0'; ?>"
                           readonly
                           class="w-full border border-gray-200 bg-gray-50 rounded px-3 py-2 text-sm font-semibold text-blue-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cargue Pagado Por</label>
                    <select name="cargue_pagado_por" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">--</option>
                        <?php foreach (['Remitente','Destinatario','Propietario'] as $op): ?>
                            <option value="<?php echo $op; ?>" <?php echo ($r['cargue_pagado_por']??'')===$op?'selected':''; ?>><?php echo $op; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descargue Pagado Por</label>
                    <select name="descargue_pagado_por" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">--</option>
                        <?php foreach (['Remitente','Destinatario','Propietario'] as $op): ?>
                            <option value="<?php echo $op; ?>" <?php echo ($r['descargue_pagado_por']??'')===$op?'selected':''; ?>><?php echo $op; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Pago</label>
                    <input type="text" name="lugar_pago"
                           value="<?php echo htmlspecialchars($r['lugar_pago'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Pago Saldo</label>
                    <input type="date" name="fecha_pago_saldo" value="<?php echo $r['fecha_pago_saldo'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑧ Notas -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑧ Notas</h3>
            <textarea name="notas" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                      placeholder="Observaciones, instrucciones especiales..."><?php echo htmlspecialchars($r['notas'] ?? ''); ?></textarea>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-3">
            <a href="rndc.php" class="px-5 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700">
                💾 Guardar Manifiesto
            </button>
        </div>
    </form>
</div>

<script>
function calcSaldo() {
    const flete   = parseFloat(document.getElementById('flete_pactado').value) || 0;
    const anticipo = parseFloat(document.getElementById('anticipo').value) || 0;
    document.getElementById('saldo').value = (flete - anticipo).toFixed(2);
}

function autoFillTrip(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        const o = opt.dataset.origen  || '';
        const d = opt.dataset.destino || '';
        if (o) document.getElementById('campo_origen').value  = o;
        if (d) document.getElementById('campo_destino').value = d;
    }
}

calcSaldo();
</script>

<?php include 'includes/footer.php'; ?>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/rndc_form.php', $content) !== false;
$results[] = [$ok, 'rndc_form.php'];
if (!$ok) $errors++;

// --- save_rndc.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Handler: guardar / actualizar manifiesto RNDC
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rndc.php');
    exit;
}

validateCsrfToken();

$s  = fn($k) => trim($_POST[$k] ?? '') ?: null;
$f  = fn($k) => (float)($_POST[$k] ?? 0);
$i  = fn($k) => ($_POST[$k] ?? '') !== '' ? (int)$_POST[$k] : null;

$rndcId = (int)($_POST['rndc_id'] ?? 0);

// Validaciones básicas
$nro  = trim($_POST['nro_manifiesto'] ?? '');
$fecha = trim($_POST['fecha_expedicion'] ?? '');
if (!$nro || !$fecha) {
    header('Location: rndc_form.php?error=campos_requeridos' . ($rndcId ? "&id=$rndcId" : ''));
    exit;
}

$fields = [
    $s('nro_manifiesto'),
    $s('autorizacion_rndc'),
    $s('nro_remesa'),
    $fecha,
    $s('fecha_vencimiento'),
    (int)($_POST['vehicle_id'] ?? 0),
    (int)($_POST['driver_id'] ?? 0),
    $i('trip_id'),
    $s('empresa_transporte'),
    $s('nit_empresa'),
    $s('remitente_nombre'),
    $s('remitente_nit'),
    $s('remitente_codigo'),
    $s('remitente_direccion'),
    $s('remitente_ciudad'),
    $s('destinatario_nombre'),
    $s('destinatario_nit'),
    $s('destinatario_codigo'),
    $s('destinatario_direccion'),
    $s('destinatario_ciudad'),
    $s('origen'),
    $s('destino'),
    $s('descripcion_mercancia'),
    ($_POST['peso_kg'] ?? '') !== '' ? (float)$_POST['peso_kg'] : null,
    ($_POST['unidades'] ?? '') !== '' ? (int)$_POST['unidades'] : null,
    $s('tipo_vehiculo'),
    $f('flete_pactado'),
    $f('anticipo'),
    $f('saldo'),
    $s('cargue_pagado_por'),
    $s('descargue_pagado_por'),
    $s('lugar_pago'),
    $s('fecha_pago_saldo'),
    $s('estado') ?? 'Activo',
    $s('notas'),
];

if ($rndcId > 0) {
    $stmt = $pdo->prepare("UPDATE manifiestos_rndc SET
        nro_manifiesto=?, autorizacion_rndc=?, nro_remesa=?,
        fecha_expedicion=?, fecha_vencimiento=?,
        vehicle_id=?, driver_id=?, trip_id=?,
        empresa_transporte=?, nit_empresa=?,
        remitente_nombre=?, remitente_nit=?, remitente_codigo=?,
        remitente_direccion=?, remitente_ciudad=?,
        destinatario_nombre=?, destinatario_nit=?, destinatario_codigo=?,
        destinatario_direccion=?, destinatario_ciudad=?,
        origen=?, destino=?,
        descripcion_mercancia=?, peso_kg=?, unidades=?, tipo_vehiculo=?,
        flete_pactado=?, anticipo=?, saldo=?,
        cargue_pagado_por=?, descargue_pagado_por=?,
        lugar_pago=?, fecha_pago_saldo=?,
        estado=?, notas=?
        WHERE id=?");
    $stmt->execute(array_merge($fields, [$rndcId]));
} else {
    $stmt = $pdo->prepare("INSERT INTO manifiestos_rndc (
        nro_manifiesto, autorizacion_rndc, nro_remesa,
        fecha_expedicion, fecha_vencimiento,
        vehicle_id, driver_id, trip_id,
        empresa_transporte, nit_empresa,
        remitente_nombre, remitente_nit, remitente_codigo,
        remitente_direccion, remitente_ciudad,
        destinatario_nombre, destinatario_nit, destinatario_codigo,
        destinatario_direccion, destinatario_ciudad,
        origen, destino,
        descripcion_mercancia, peso_kg, unidades, tipo_vehiculo,
        flete_pactado, anticipo, saldo,
        cargue_pagado_por, descargue_pagado_por,
        lugar_pago, fecha_pago_saldo,
        estado, notas
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute($fields);
    $rndcId = (int)$pdo->lastInsertId();
}

// Sincronizar campos RNDC en la tabla trips si hay trip_id
$tripId = $i('trip_id');
if ($tripId) {
    try {
        $sync = $pdo->prepare("UPDATE trips SET
            manifest_number        = ?,
            autorizacion_rndc      = ?,
            nro_remesa             = ?,
            empresa_transporte     = ?,
            nit_empresa_transporte = ?,
            remitente_nombre       = ?,
            remitente_codigo       = ?,
            destinatario_nombre    = ?,
            destinatario_codigo    = ?,
            cargue_pagado_por      = ?,
            descargue_pagado_por   = ?,
            lugar_pago             = ?,
            fecha_pago_saldo       = ?
            WHERE id = ?");
        $sync->execute([
            $s('nro_manifiesto'),
            $s('autorizacion_rndc'),
            $s('nro_remesa'),
            $s('empresa_transporte'),
            $s('nit_empresa'),
            $s('remitente_nombre'),
            $s('remitente_codigo'),
            $s('destinatario_nombre'),
            $s('destinatario_codigo'),
            $s('cargue_pagado_por'),
            $s('descargue_pagado_por'),
            $s('lugar_pago'),
            $s('fecha_pago_saldo'),
            $tripId,
        ]);
    } catch (PDOException $e) {
        // Columnas RNDC en trips pueden no existir aún — ignorar
    }
}

header("Location: rndc.php?msg=saved");
exit;

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/save_rndc.php', $content) !== false;
$results[] = [$ok, 'save_rndc.php'];
if (!$ok) $errors++;

// --- save_socio.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Handler: guardar / actualizar socio
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: socios.php');
    exit;
}

validateCsrfToken();

$s = fn($k) => trim($_POST[$k] ?? '') ?: null;

$socioId = (int)($_POST['socio_id'] ?? 0);
$nombre  = trim($_POST['nombre'] ?? '');

if (!$nombre) {
    $redirect = $socioId > 0 ? "socio_form.php?id=$socioId&msg=error" : "socio_form.php?msg=error";
    header("Location: $redirect");
    exit;
}

$porcentaje = (float)($_POST['porcentaje_utilidad'] ?? 0);

if ($socioId > 0) {
    $active = isset($_POST['active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE socios SET
        tipo                = ?,
        nombre              = ?,
        documento           = ?,
        tipo_documento      = ?,
        telefono            = ?,
        celular             = ?,
        email               = ?,
        direccion           = ?,
        ciudad              = ?,
        departamento        = ?,
        banco               = ?,
        cuenta_bancaria     = ?,
        tipo_cuenta         = ?,
        porcentaje_utilidad = ?,
        notas               = ?,
        active              = ?
        WHERE id = ?");

    $stmt->execute([
        $s('tipo') ?? 'Persona Natural',
        $nombre,
        $s('documento'),
        $s('tipo_documento') ?? 'CC',
        $s('telefono'),
        $s('celular'),
        $s('email'),
        $s('direccion'),
        $s('ciudad'),
        $s('departamento'),
        $s('banco'),
        $s('cuenta_bancaria'),
        $s('tipo_cuenta'),
        $porcentaje,
        $s('notas'),
        $active,
        $socioId,
    ]);

    header("Location: socio_form.php?id=$socioId&msg=saved");
    exit;
} else {
    $stmt = $pdo->prepare("INSERT INTO socios (
        tipo, nombre, documento, tipo_documento,
        telefono, celular, email, direccion,
        ciudad, departamento, banco, cuenta_bancaria,
        tipo_cuenta, porcentaje_utilidad, notas, active
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)");

    $stmt->execute([
        $s('tipo') ?? 'Persona Natural',
        $nombre,
        $s('documento'),
        $s('tipo_documento') ?? 'CC',
        $s('telefono'),
        $s('celular'),
        $s('email'),
        $s('direccion'),
        $s('ciudad'),
        $s('departamento'),
        $s('banco'),
        $s('cuenta_bancaria'),
        $s('tipo_cuenta'),
        $porcentaje,
        $s('notas'),
    ]);

    header("Location: socios.php?msg=saved");
    exit;
}

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/save_socio.php', $content) !== false;
$results[] = [$ok, 'save_socio.php'];
if (!$ok) $errors++;

// --- socio_delete.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Soft delete / reactivar socio
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id         = (int)($_GET['id'] ?? 0);
$reactivar  = isset($_GET['reactivar']) && $_GET['reactivar'] == '1';

if (!$id) {
    header('Location: socios.php');
    exit;
}

$nuevoEstado = $reactivar ? 1 : 0;

$stmt = $pdo->prepare("UPDATE socios SET active = ? WHERE id = ?");
$stmt->execute([$nuevoEstado, $id]);

header('Location: socios.php?msg=deleted');
exit;

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/socio_delete.php', $content) !== false;
$results[] = [$ok, 'socio_delete.php'];
if (!$ok) $errors++;

// --- socio_details.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Detalle de Socio / Propietario
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: socios.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) die("Socio #$id no encontrado.");

// Vehículos asociados
$vehiculos = [];
try {
    $stmtV = $pdo->prepare("SELECT id, placa, modelo, year FROM vehicles WHERE partner_id = ? ORDER BY placa");
    $stmtV->execute([$id]);
    $vehiculos = $stmtV->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $vehiculos = [];
}

// Resumen financiero: viajes y flete_neto de los vehículos de este socio
$resumenFinanciero = ['total_viajes' => 0, 'total_flete_neto' => 0, 'utilidad_calculada' => 0];
try {
    $stmtF = $pdo->prepare("
        SELECT COUNT(t.id) AS total_viajes, IFNULL(SUM(t.flete_neto),0) AS total_flete_neto
        FROM trips t
        JOIN vehicles v ON v.id = t.vehicle_id
        WHERE v.partner_id = ?
    ");
    $stmtF->execute([$id]);
    $row = $stmtF->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $resumenFinanciero['total_viajes']     = (int)$row['total_viajes'];
        $resumenFinanciero['total_flete_neto'] = (float)$row['total_flete_neto'];
        $resumenFinanciero['utilidad_calculada'] = $resumenFinanciero['total_flete_neto'] * ((float)$s['porcentaje_utilidad'] / 100);
    }
} catch (PDOException $e) {
    // tabla trips o columna partner_id puede no existir aún
}

$tipo_color = $s['tipo'] === 'Empresa'
    ? 'bg-purple-100 text-purple-700'
    : 'bg-blue-100 text-blue-700';
?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Encabezado -->
    <div class="flex items-center gap-3 mb-6">
        <a href="socios.php" class="text-gray-400 hover:text-gray-600">← Socios</a>
        <span class="text-gray-300">/</span>
        <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($s['nombre']); ?></h2>
        <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $tipo_color; ?>">
            <?php echo htmlspecialchars($s['tipo']); ?>
        </span>
        <?php if ($s['active']): ?>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Activo</span>
        <?php else: ?>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactivo</span>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

        <!-- Datos de contacto -->
        <div class="bg-white shadow rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Datos de Contacto</h3>
            <dl class="space-y-3 text-sm">
                <?php if ($s['documento']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Documento</dt>
                    <dd class="font-mono font-medium"><?php echo htmlspecialchars($s['tipo_documento']); ?> <?php echo htmlspecialchars($s['documento']); ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['telefono']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Teléfono</dt>
                    <dd><?php echo htmlspecialchars($s['telefono']); ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['celular']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Celular</dt>
                    <dd><a href="tel:<?php echo $s['celular']; ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($s['celular']); ?></a></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['email']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Email</dt>
                    <dd><a href="mailto:<?php echo htmlspecialchars($s['email']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($s['email']); ?></a></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['direccion']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Dirección</dt>
                    <dd><?php echo htmlspecialchars($s['direccion']); ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['ciudad']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Ciudad</dt>
                    <dd><?php echo htmlspecialchars($s['ciudad']); ?><?php if ($s['departamento']): ?>, <?php echo htmlspecialchars($s['departamento']); ?><?php endif; ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['notas']): ?>
                <div class="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-500 italic">
                    <?php echo htmlspecialchars($s['notas']); ?>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Datos bancarios -->
        <div class="bg-white shadow rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Datos Bancarios</h3>
            <dl class="space-y-3 text-sm">
                <?php if ($s['banco']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Banco</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($s['banco']); ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['cuenta_bancaria']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Cuenta</dt>
                    <dd class="font-mono"><?php echo htmlspecialchars($s['cuenta_bancaria']); ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($s['tipo_cuenta']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">Tipo Cuenta</dt>
                    <dd><?php echo htmlspecialchars($s['tipo_cuenta']); ?></dd>
                </div>
                <?php endif; ?>
                <div class="flex gap-3">
                    <dt class="text-gray-500 w-28 shrink-0">% Utilidad</dt>
                    <dd class="font-bold text-green-700"><?php echo number_format((float)$s['porcentaje_utilidad'], 2); ?>%</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Vehículos asociados -->
    <div class="bg-white shadow rounded-lg p-5 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Vehículos Asociados</h3>
        <?php if (empty($vehiculos)): ?>
            <p class="text-sm text-gray-400">No hay vehículos asociados a este socio.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-4 py-2 text-left">Placa</th>
                            <th class="px-4 py-2 text-left">Modelo</th>
                            <th class="px-4 py-2 text-left">Año</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($vehiculos as $v): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono font-bold text-gray-800"><?php echo htmlspecialchars($v['placa']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($v['modelo'] ?? '—'); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($v['year'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Resumen financiero -->
    <div class="bg-white shadow rounded-lg p-5 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Resumen Financiero</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Viajes</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($resumenFinanciero['total_viajes']); ?></p>
            </div>
            <div class="bg-blue-50 rounded p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Flete Neto Acumulado</p>
                <p class="text-2xl font-bold text-blue-700">$<?php echo number_format($resumenFinanciero['total_flete_neto'], 0, ',', '.'); ?></p>
            </div>
            <div class="bg-green-50 rounded p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Utilidad Estimada (<?php echo number_format((float)$s['porcentaje_utilidad'], 2); ?>%)</p>
                <p class="text-2xl font-bold text-green-700">$<?php echo number_format($resumenFinanciero['utilidad_calculada'], 0, ',', '.'); ?></p>
            </div>
        </div>
    </div>

    <!-- Botones -->
    <div class="flex gap-3">
        <a href="socio_form.php?id=<?php echo $s['id']; ?>"
           class="px-4 py-2 bg-yellow-500 text-white rounded text-sm font-semibold hover:bg-yellow-600">
            ✏️ Editar
        </a>
        <button onclick="window.print()"
                class="px-4 py-2 bg-gray-600 text-white rounded text-sm font-semibold hover:bg-gray-700">
            🖨 Imprimir
        </button>
        <a href="socios.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
            ← Volver
        </a>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/socio_details.php', $content) !== false;
$results[] = [$ok, 'socio_details.php'];
if (!$ok) $errors++;

// --- socio_form.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Formulario Socio — Crear / Editar
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/functions.php';

$csrf_token = getCsrfToken();
$editMode   = isset($_GET['id']);
$socioId    = $editMode ? (int)$_GET['id'] : 0;

if ($editMode) {
    $stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
    $stmt->execute([$socioId]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$s) die("Socio #$socioId no encontrado.");
} else {
    $s = [
        'id'                  => null,
        'tipo'                => 'Persona Natural',
        'nombre'              => '',
        'documento'           => '',
        'tipo_documento'      => 'CC',
        'telefono'            => '',
        'celular'             => '',
        'email'               => '',
        'direccion'           => '',
        'ciudad'              => '',
        'departamento'        => '',
        'banco'               => '',
        'cuenta_bancaria'     => '',
        'tipo_cuenta'         => '',
        'porcentaje_utilidad' => '0.00',
        'notas'               => '',
        'active'              => 1,
    ];
}

$msg = $_GET['msg'] ?? '';
?>

<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="socios.php" class="text-gray-400 hover:text-gray-600">← Socios</a>
        <span class="text-gray-300">/</span>
        <h2 class="text-xl font-bold text-gray-900">
            <?php echo $editMode ? 'Editar Socio' : 'Nuevo Socio'; ?>
        </h2>
    </div>

    <?php if ($msg === 'saved'): ?>
        <div class="alert-card alert-card-success">
            <span class="alert-card-icon">✅</span>
            <div class="alert-card-body">
                <div class="alert-card-title">Éxito</div>
                <div class="alert-card-text">Socio guardado. <a href="socios.php" class="ml-2 underline">Ver directorio</a></div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert-card alert-card-danger">
            <span class="alert-card-icon">❌</span>
            <div class="alert-card-body">
                <div class="alert-card-title">Error</div>
                <div class="alert-card-text">Por favor revise los datos e intente nuevamente.</div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php endif; ?>

    <form action="save_socio.php" method="POST" class="bg-white shadow rounded-lg p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="socio_id" value="<?php echo $socioId; ?>">

        <!-- Datos principales -->
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Datos principales</p>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select name="tipo" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="Persona Natural" <?php echo $s['tipo'] === 'Persona Natural' ? 'selected' : ''; ?>>Persona Natural</option>
                        <option value="Empresa" <?php echo $s['tipo'] === 'Empresa' ? 'selected' : ''; ?>>Empresa</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">% Utilidad</label>
                    <input type="number" name="porcentaje_utilidad" step="0.01" min="0" max="100"
                           value="<?php echo htmlspecialchars($s['porcentaje_utilidad']); ?>"
                           placeholder="0.00"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" required
                       value="<?php echo htmlspecialchars($s['nombre']); ?>"
                       placeholder="Nombre completo o razón social"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento</label>
                    <select name="tipo_documento" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <?php foreach (['CC','NIT','CE','Pasaporte'] as $td): ?>
                            <option value="<?php echo $td; ?>" <?php echo $s['tipo_documento'] === $td ? 'selected' : ''; ?>><?php echo $td; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Documento</label>
                    <input type="text" name="documento"
                           value="<?php echo htmlspecialchars($s['documento'] ?? ''); ?>"
                           placeholder="1.234.567.890"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
            </div>
        </div>

        <!-- Ubicación y contacto -->
        <div class="border-t pt-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Ubicación y contacto</p>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
                    <input type="text" name="celular"
                           value="<?php echo htmlspecialchars($s['celular'] ?? ''); ?>"
                           placeholder="310 123 4567"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono fijo</label>
                    <input type="text" name="telefono"
                           value="<?php echo htmlspecialchars($s['telefono'] ?? ''); ?>"
                           placeholder="(4) 123 4567"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email"
                       value="<?php echo htmlspecialchars($s['email'] ?? ''); ?>"
                       placeholder="socio@ejemplo.com"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <input type="text" name="direccion"
                       value="<?php echo htmlspecialchars($s['direccion'] ?? ''); ?>"
                       placeholder="Cra 50 # 30-45, Barrio..."
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" name="ciudad"
                           value="<?php echo htmlspecialchars($s['ciudad'] ?? ''); ?>"
                           placeholder="Medellín, Bogotá..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                    <input type="text" name="departamento"
                           value="<?php echo htmlspecialchars($s['departamento'] ?? ''); ?>"
                           placeholder="Antioquia, Cundinamarca..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- Datos bancarios -->
        <div class="border-t pt-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Datos bancarios</p>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                <input type="text" name="banco"
                       value="<?php echo htmlspecialchars($s['banco'] ?? ''); ?>"
                       placeholder="Bancolombia, Davivienda, Nequi..."
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Cuenta</label>
                    <input type="text" name="cuenta_bancaria"
                           value="<?php echo htmlspecialchars($s['cuenta_bancaria'] ?? ''); ?>"
                           placeholder="123456789"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cuenta</label>
                    <select name="tipo_cuenta" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">-- Seleccione --</option>
                        <option value="Ahorros"   <?php echo ($s['tipo_cuenta'] ?? '') === 'Ahorros'   ? 'selected' : ''; ?>>Ahorros</option>
                        <option value="Corriente" <?php echo ($s['tipo_cuenta'] ?? '') === 'Corriente' ? 'selected' : ''; ?>>Corriente</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Notas -->
        <div class="border-t pt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
            <textarea name="notas" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                      placeholder="Observaciones, acuerdos especiales..."><?php echo htmlspecialchars($s['notas'] ?? ''); ?></textarea>
        </div>

        <?php if ($editMode): ?>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="active" value="1" id="active"
                   <?php echo $s['active'] ? 'checked' : ''; ?>>
            <label for="active" class="text-sm text-gray-700">Socio activo</label>
        </div>
        <?php endif; ?>

        <!-- Botones -->
        <div class="flex justify-end gap-3 pt-2 border-t">
            <a href="socios.php" class="px-5 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700">
                💾 Guardar Socio
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/socio_form.php', $content) !== false;
$results[] = [$ok, 'socio_form.php'];
if (!$ok) $errors++;

// --- socios.php ---
$dir = __DIR__ . '/';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Socios / Propietarios de Vehículos — Listado y búsqueda
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/functions.php';
require_once 'includes/export_buttons.php';

// Filtros
$buscar      = trim($_GET['buscar'] ?? '');
$tipo        = trim($_GET['tipo']   ?? '');
$solo_activos = $_GET['activos'] ?? '1';

$params = [];
$where  = "WHERE 1=1";

if ($solo_activos === '1') {
    $where .= " AND s.active = 1";
}
if ($buscar) {
    $where .= " AND (s.nombre LIKE ? OR s.documento LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}
if ($tipo) {
    $where .= " AND s.tipo = ?";
    $params[] = $tipo;
}

$sql  = "SELECT s.* FROM socios s $where ORDER BY s.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totales para summary cards
$totalSocios  = $pdo->query("SELECT COUNT(*) FROM socios")->fetchColumn();
$totalActivos = $pdo->query("SELECT COUNT(*) FROM socios WHERE active = 1")->fetchColumn();

// Vehículos asociados total
$totalVehiculos = 0;
try {
    $totalVehiculos = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE partner_id IS NOT NULL")->fetchColumn();
} catch (PDOException $e) {
    $totalVehiculos = 0;
}

$msg = $_GET['msg'] ?? '';
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Encabezado -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Socios / Propietarios de Vehículos</h2>
            <p class="mt-1 text-sm text-gray-500">
                <?php echo count($socios); ?> socio<?php echo count($socios) != 1 ? 's' : ''; ?> encontrado<?php echo count($socios) != 1 ? 's' : ''; ?>.
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3 flex-wrap">
            <?php exportButtons('socios', $_GET); ?>
            <a href="socio_form.php"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                + Nuevo Socio
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">👥</div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Socios</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $totalSocios; ?></p>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl">✅</div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Activos</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $totalActivos; ?></p>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-xl">🚛</div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Vehículos Asociados</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $totalVehiculos; ?></p>
            </div>
        </div>
    </div>

    <?php if ($msg === 'saved'): ?>
        <div class="alert-card alert-card-success">
            <span class="alert-card-icon">✅</span>
            <div class="alert-card-body">
                <div class="alert-card-title">Éxito</div>
                <div class="alert-card-text">Socio guardado correctamente.</div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert-card alert-card-warning">
            <span class="alert-card-icon">⚠️</span>
            <div class="alert-card-body">
                <div class="alert-card-title">Atención</div>
                <div class="alert-card-text">Estado del socio actualizado.</div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
            <input type="text" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>"
                   placeholder="Nombre, documento..."
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Tipo</label>
            <select name="tipo" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Todos</option>
                <option value="Persona Natural" <?php echo $tipo === 'Persona Natural' ? 'selected' : ''; ?>>Persona Natural</option>
                <option value="Empresa" <?php echo $tipo === 'Empresa' ? 'selected' : ''; ?>>Empresa</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Estado</label>
            <select name="activos" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="1" <?php echo $solo_activos === '1' ? 'selected' : ''; ?>>Solo activos</option>
                <option value=""  <?php echo $solo_activos === ''  ? 'selected' : ''; ?>>Todos</option>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-gray-700 text-white text-sm rounded hover:bg-gray-800">Buscar</button>
            <a href="socios.php" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300">Limpiar</a>
        </div>
    </form>

    <!-- Grid de tarjetas -->
    <?php if (empty($socios)): ?>
        <div class="bg-white shadow rounded-lg p-12 text-center text-gray-400">
            <p class="text-4xl mb-4">👥</p>
            <p class="text-lg">No hay socios registrados aún.</p>
            <a href="socio_form.php" class="mt-4 inline-block text-blue-600 hover:underline">Registrar el primero →</a>
        </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($socios as $s): ?>
        <?php
            $tipo_color = $s['tipo'] === 'Empresa'
                ? 'bg-purple-100 text-purple-700'
                : 'bg-blue-100 text-blue-700';

            // Vehículos asociados
            $placas = [];
            try {
                $stmtV = $pdo->prepare("SELECT placa FROM vehicles WHERE partner_id = ? ORDER BY placa");
                $stmtV->execute([$s['id']]);
                $placas = $stmtV->fetchAll(PDO::FETCH_COLUMN);
            } catch (PDOException $e) {
                $placas = [];
            }
        ?>
        <div class="bg-white shadow rounded-lg overflow-hidden flex flex-col hover:shadow-md transition-shadow">

            <!-- Header tarjeta -->
            <div class="px-5 py-4 border-b flex justify-between items-start">
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-gray-800 truncate"><?php echo htmlspecialchars($s['nombre']); ?></h3>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $tipo_color; ?>">
                        <?php echo htmlspecialchars($s['tipo']); ?>
                    </span>
                </div>
                <?php if (!$s['active']): ?>
                <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-400 text-xs rounded">Inactivo</span>
                <?php endif; ?>
            </div>

            <!-- Cuerpo tarjeta -->
            <div class="px-5 py-4 flex-1 space-y-2 text-sm text-gray-600">
                <?php if ($s['documento']): ?>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">🪪</span>
                    <span class="font-mono text-xs"><?php echo htmlspecialchars($s['tipo_documento']); ?> <?php echo htmlspecialchars($s['documento']); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($s['telefono'] || $s['celular']): ?>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">📞</span>
                    <span>
                        <?php if ($s['celular']): ?>
                            <a href="tel:<?php echo $s['celular']; ?>" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($s['celular']); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($s['telefono'] && $s['celular']): ?> · <?php endif; ?>
                        <?php if ($s['telefono']): ?>
                            <?php echo htmlspecialchars($s['telefono']); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($s['ciudad']): ?>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">📍</span>
                    <span><?php echo htmlspecialchars($s['ciudad']); ?><?php if ($s['departamento']): ?>, <?php echo htmlspecialchars($s['departamento']); ?><?php endif; ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($placas)): ?>
                <div class="flex items-start gap-2">
                    <span class="text-gray-400 mt-0.5">🚛</span>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach ($placas as $placa): ?>
                            <span class="px-1.5 py-0.5 bg-gray-100 text-gray-700 text-xs font-mono rounded"><?php echo htmlspecialchars($placa); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($s['porcentaje_utilidad'] > 0): ?>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">💰</span>
                    <span class="font-semibold text-green-700"><?php echo number_format((float)$s['porcentaje_utilidad'], 2); ?>% utilidad</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Acciones -->
            <div class="px-5 py-3 bg-gray-50 border-t flex justify-between items-center">
                <a href="socio_details.php?id=<?php echo $s['id']; ?>"
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver detalle</a>
                <div class="flex gap-3">
                    <a href="socio_form.php?id=<?php echo $s['id']; ?>"
                       class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">Editar</a>
                    <?php if ($s['active']): ?>
                    <a href="socio_delete.php?id=<?php echo $s['id']; ?>"
                       onclick="return confirm('¿Desactivar este socio?')"
                       class="text-red-500 hover:text-red-700 text-sm font-medium">Desactivar</a>
                    <?php else: ?>
                    <a href="socio_delete.php?id=<?php echo $s['id']; ?>&reactivar=1"
                       class="text-green-600 hover:text-green-800 text-sm font-medium">Reactivar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/socios.php', $content) !== false;
$results[] = [$ok, 'socios.php'];
if (!$ok) $errors++;

// --- migrations/055_create_table_manifiestos_rndc.php ---
$dir = __DIR__ . '/migrations';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Migration 055: Tabla manifiestos_rndc — Manifiesto Electrónico de Carga (Colombia)
 */
require_once __DIR__ . '/../includes/db.php';
echo "Creating manifiestos_rndc table...\n";

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS manifiestos_rndc (
        id                      INT AUTO_INCREMENT PRIMARY KEY,
        trip_id                 INT NULL,
        vehicle_id              INT NOT NULL,
        driver_id               INT NOT NULL,

        nro_manifiesto          VARCHAR(50) NOT NULL,
        autorizacion_rndc       VARCHAR(50) NULL,
        nro_remesa              VARCHAR(50) NULL,
        fecha_expedicion        DATE NOT NULL,
        fecha_vencimiento       DATE NULL,

        empresa_transporte      VARCHAR(255) NOT NULL,
        nit_empresa             VARCHAR(50) NULL,

        remitente_nombre        VARCHAR(255) NOT NULL,
        remitente_nit           VARCHAR(50) NULL,
        remitente_codigo        VARCHAR(50) NULL,
        remitente_direccion     VARCHAR(255) NULL,
        remitente_ciudad        VARCHAR(100) NULL,

        destinatario_nombre     VARCHAR(255) NOT NULL,
        destinatario_nit        VARCHAR(50) NULL,
        destinatario_codigo     VARCHAR(50) NULL,
        destinatario_direccion  VARCHAR(255) NULL,
        destinatario_ciudad     VARCHAR(100) NULL,

        origen                  VARCHAR(255) NOT NULL,
        destino                 VARCHAR(255) NOT NULL,

        descripcion_mercancia   TEXT NULL,
        peso_kg                 DECIMAL(10,2) NULL,
        unidades                INT NULL,
        tipo_vehiculo           VARCHAR(100) NULL,

        flete_pactado           DECIMAL(15,2) DEFAULT 0.00,
        anticipo                DECIMAL(15,2) DEFAULT 0.00,
        saldo                   DECIMAL(15,2) DEFAULT 0.00,
        cargue_pagado_por       ENUM('Remitente','Destinatario','Propietario') NULL,
        descargue_pagado_por    ENUM('Remitente','Destinatario','Propietario') NULL,
        lugar_pago              VARCHAR(100) NULL,
        fecha_pago_saldo        DATE NULL,

        estado                  ENUM('Borrador','Activo','Finalizado','Anulado') DEFAULT 'Activo',
        notas                   TEXT NULL,

        created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (trip_id)   REFERENCES trips(id) ON DELETE SET NULL,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
        FOREIGN KEY (driver_id)  REFERENCES personnel(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "✓ Tabla manifiestos_rndc creada correctamente.\n";
} catch (PDOException $e) {
    echo "x Error: " . $e->getMessage() . "\n";
}

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/migrations/055_create_table_manifiestos_rndc.php', $content) !== false;
$results[] = [$ok, 'migrations/055_create_table_manifiestos_rndc.php'];
if (!$ok) $errors++;

// --- migrations/056_create_table_socios.php ---
$dir = __DIR__ . '/migrations';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
/**
 * Migration 056: Create socios table - Socios / Propietarios de Vehículos
 */
require_once __DIR__ . '/../includes/db.php';
echo "Creating socios table...\n";

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS socios (
        id                  INT AUTO_INCREMENT PRIMARY KEY,
        tipo                ENUM('Persona Natural','Empresa') DEFAULT 'Persona Natural',
        nombre              VARCHAR(255) NOT NULL,
        documento           VARCHAR(50) NULL,
        tipo_documento      ENUM('CC','NIT','CE','Pasaporte') DEFAULT 'CC',
        telefono            VARCHAR(30) NULL,
        celular             VARCHAR(30) NULL,
        email               VARCHAR(150) NULL,
        direccion           VARCHAR(255) NULL,
        ciudad              VARCHAR(100) NULL,
        departamento        VARCHAR(100) NULL,
        banco               VARCHAR(100) NULL,
        cuenta_bancaria     VARCHAR(50) NULL,
        tipo_cuenta         ENUM('Ahorros','Corriente') NULL,
        porcentaje_utilidad DECIMAL(5,2) DEFAULT 0.00,
        notas               TEXT NULL,
        active              TINYINT(1) DEFAULT 1,
        created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        KEY idx_socios_nombre (nombre),
        KEY idx_socios_active (active),
        KEY idx_socios_ciudad (ciudad)

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo " - Table socios created\n";
    echo "✓ socios table ready.\n";
} catch (PDOException $e) {
    echo "x Error: " . $e->getMessage() . "\n";
}
?>

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/migrations/056_create_table_socios.php', $content) !== false;
$results[] = [$ok, 'migrations/056_create_table_socios.php'];
if (!$ok) $errors++;

// --- migrations/027_update_schema_optimization_v2.php ---
$dir = __DIR__ . '/migrations';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
require_once 'includes/db.php';

try {
    echo "Starting advanced index optimization...<br>";

    $indexes = [
        'trips'     => ['idx_trips_client' => 'client_id', 'idx_trips_settlement' => 'settlement_status', 'idx_trips_report_monthly' => 'date_load, status'],
        'expenses'  => ['idx_expenses_paid_by' => 'paid_by'],
        'personnel' => ['idx_personnel_active' => 'active, type'],
        'clients'   => ['idx_clients_active' => 'active'],
    ];
    foreach ($indexes as $table => $idxList) {
        $existing = $pdo->query("SHOW INDEX FROM $table")->fetchAll(PDO::FETCH_COLUMN, 2);
        foreach ($idxList as $name => $cols) {
            if (!in_array($name, $existing)) {
                $pdo->exec("CREATE INDEX $name ON $table($cols)");
            }
        }
    }
    echo "Advanced indexes checked/added.<br>";

    // Create View for Vehicle Availability
    $pdo->exec("DROP VIEW IF EXISTS view_vehicle_availability");
    $pdo->exec("
        CREATE VIEW view_vehicle_availability AS
        SELECT v.id, v.placa, 
               (SELECT t.kms_end FROM trips t WHERE t.vehicle_id = v.id ORDER BY t.date_load DESC, t.id DESC LIMIT 1) as last_kms,
               EXISTS(SELECT 1 FROM trips t WHERE t.vehicle_id = v.id AND t.status = 'En Progreso') as is_busy
        FROM vehicles v
    ");
    echo "Vehicle availability view created.<br>";

    echo "<b style='color:green;'>Success: Advanced optimization complete.</b>";

} catch (PDOException $e) {
    echo "<b style='color:red;'>Error applying optimizations:</b> " . $e->getMessage();
}
?>
CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/migrations/027_update_schema_optimization_v2.php', $content) !== false;
$results[] = [$ok, 'migrations/027_update_schema_optimization_v2.php'];
if (!$ok) $errors++;

// --- migrations/026_update_schema_money_module.php ---
$dir = __DIR__ . '/migrations';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
require_once 'includes/db.php';

try {
    // 1. Update expenses table for photo uploads
    $cols = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'receipt_photo'")->fetchAll();
    if (!$cols) $pdo->exec("ALTER TABLE expenses ADD COLUMN receipt_photo VARCHAR(255) NULL AFTER description");
    echo "Added receipt_photo to expenses.<br>";

    // 2. Update trips table for settlement status
    $cols = $pdo->query("SHOW COLUMNS FROM trips LIKE 'settlement_status'")->fetchAll();
    if (!$cols) $pdo->exec("ALTER TABLE trips ADD COLUMN settlement_status ENUM('Pending', 'Settled') DEFAULT 'Pending' AFTER final_pay_received");
    $cols = $pdo->query("SHOW COLUMNS FROM trips LIKE 'settlement_notes'")->fetchAll();
    if (!$cols) $pdo->exec("ALTER TABLE trips ADD COLUMN settlement_notes TEXT NULL AFTER settlement_status");
    echo "Added settlement fields to trips.<br>";

    // 3. Create upload directory
    if (!file_exists('uploads/receipts')) {
        mkdir('uploads/receipts', 0777, true);
        echo "Created uploads/receipts directory.<br>";
    }

    echo "Schema update completed successfully!";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/migrations/026_update_schema_money_module.php', $content) !== false;
$results[] = [$ok, 'migrations/026_update_schema_money_module.php'];
if (!$ok) $errors++;

// --- migrations/039_update_schema_system_alerts.php ---
$dir = __DIR__ . '/migrations';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
require_once 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL, -- 'status_change', 'unusual_expense', 'system'
        entity_id INT DEFAULT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        priority VARCHAR(20) DEFAULT 'normal', -- 'low', 'normal', 'high', 'critical'
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Table 'system_alerts' created successfully.<br>";

    // Add config for unusual expense threshold if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM config LIKE 'unusual_expense_threshold'")->fetchAll(); if (!$cols) $pdo->exec("ALTER TABLE config ADD COLUMN unusual_expense_threshold DECIMAL(15,2) DEFAULT 1000000.00");
    echo "Config column 'unusual_expense_threshold' added.<br>";

} catch (PDOException $e) {
    die("Error creating alerts table: " . $e->getMessage());
}

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/migrations/039_update_schema_system_alerts.php', $content) !== false;
$results[] = [$ok, 'migrations/039_update_schema_system_alerts.php'];
if (!$ok) $errors++;

// --- migrations/040_update_schema_digital_trip_sheet.php ---
$dir = __DIR__ . '/migrations';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
require_once 'includes/db.php';

try {
    // Add tank_mileage to expenses for fuel vouchers
    $cols = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'tank_mileage'")->fetchAll(); if (!$cols) $pdo->exec("ALTER TABLE expenses ADD COLUMN tank_mileage DECIMAL(10,2) DEFAULT NULL");
    echo "Column 'tank_mileage' added to 'expenses' table.<br>";

} catch (PDOException $e) {
    echo "Info: 'tank_mileage' already exists or error: " . $e->getMessage() . "<br>";
}

try {
    // Ensure trips has manifest_date
    $cols = $pdo->query("SHOW COLUMNS FROM trips LIKE 'manifest_date'")->fetchAll(); if (!$cols) $pdo->exec("ALTER TABLE trips ADD COLUMN manifest_date DATE DEFAULT NULL");
    echo "Column 'manifest_date' added to 'trips' table.<br>";
} catch (PDOException $e) {
    echo "Info: 'manifest_date' already exists or error: " . $e->getMessage() . "<br>";
}

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/migrations/040_update_schema_digital_trip_sheet.php', $content) !== false;
$results[] = [$ok, 'migrations/040_update_schema_digital_trip_sheet.php'];
if (!$ok) $errors++;

// --- migrations/041_update_schema_fuel_performance.php ---
$dir = __DIR__ . '/migrations';
if ($dir !== __DIR__ . '/' && !is_dir($dir)) mkdir($dir, 0777, true);
$content = <<<'CELR_FILE_END'
<?php
require_once 'includes/db.php';

try {
    // Add kms_driven_on_fuel to help KPL/G calculation if needed, 
    // but we can calculate it from tank_mileage vs kms_start.

    // Check if we need more fields in expenses for the "FORMATO 2025"
    $cols = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'invoice_status'")->fetchAll(); if (!$cols) $pdo->exec("ALTER TABLE expenses ADD COLUMN invoice_status VARCHAR(50) DEFAULT 'Pendiente'");

    // Satrack integration already added satrack_id to vehicles, 
    // but let's ensure health metrics has what it needs.

    echo "Migration 041 completed: Schema optimized for Performance KPIs.<br>";

} catch (PDOException $e) {
    echo "Migration 041 Info: " . $e->getMessage() . "<br>";
}

CELR_FILE_END;
$ok = file_put_contents(__DIR__ . '/migrations/041_update_schema_fuel_performance.php', $content) !== false;
$results[] = [$ok, 'migrations/041_update_schema_fuel_performance.php'];
if (!$ok) $errors++;

// Output
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>CELR Update</title>";
echo "<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;}
.ok{color:#4ade80;} .err{color:#f87171;} h1{color:#60a5fa;margin-bottom:1rem;}
li{margin:4px 0;font-size:0.9rem;}</style></head><body>";
echo "<h1>🚛 CELR App — Actualización de Archivos</h1><ul>";
foreach ($results as [$ok, $file]) {
    $icon = $ok ? "✅" : "❌";
    $cls  = $ok ? "ok" : "err";
    echo "<li class='$cls'>$icon $file</li>";
}
echo "</ul>";
if ($errors === 0) {
    echo "<h2 class='ok'>✅ Actualización completada — " . count($results) . " archivos actualizados</h2>";
    echo "<p style='color:#94a3b8'>Ahora haz git add -A && git commit -m 'Update' && git push origin main</p>";
} else {
    echo "<h2 class='err'>⚠️ $errors archivo(s) con error</h2>";
}
echo "</body></html>";
