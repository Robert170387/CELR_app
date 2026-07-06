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
                    ['name' => 'Empresa Manifiesto',     'url' => 'clients.php'],
                    ['name' => 'Clientes',              'url' => 'manifest_companies.php'],
                    ['name' => 'Socios / Propietarios', 'url' => 'socios.php'],
                ]
            ],
            ['name' => 'Analítica', 'url' => 'financial_report.php', 'icon' => 'chart-bar'],
        ];

        $configChildren = [
            ['name' => 'General', 'url' => 'config.php', 'icon' => 'adjustments'],
            ['name' => 'Empresa Manifiesto', 'url' => 'clients.php', 'icon' => 'user-circle'],
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
                        (str_contains($child['url'], 'manifest_companies') && str_contains($current_page, 'manifest_companies')) ||
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