                        <?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

// Fetch options for the filters console
$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
$drivers = $pdo->query("SELECT id, firstname, lastname FROM personnel WHERE active=1 ORDER BY firstname")->fetchAll();
$clients = $pdo->query("SELECT id, business_name, firstname, lastname1, person_type FROM clients WHERE active=1 ORDER BY business_name, firstname")->fetchAll();

$where = [];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "t.status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $where[] = "t.date_load >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = "t.date_load <= ?";
    $params[] = $_GET['date_to'];
}
if (!empty($_GET['vehicle_id'])) {
    $where[] = "t.vehicle_id = ?";
    $params[] = $_GET['vehicle_id'];
}
if (!empty($_GET['driver_id'])) {
    $where[] = "t.driver_id = ?";
    $params[] = $_GET['driver_id'];
}
if (!empty($_GET['client_id'])) {
    $where[] = "t.client_id = ?";
    $params[] = $_GET['client_id'];
}

$whereSql = "";
if (!empty($where)) {
    $whereSql = " WHERE " . implode(" AND ", $where);
}

// Generate query string for pagination links
$filterParams = $_GET;
unset($filterParams['page']);
$queryString = http_build_query($filterParams);
if (!empty($queryString)) {
    $queryString = '&' . $queryString;
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM trips t" . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

// Pagination Configuration
$limit = 15;
$totalPages = ceil($totalRows / $limit);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
$offset = ($page - 1) * $limit;

// Main query with pagination
$sql = "SELECT t.*, v.placa, CONCAT(d.firstname, ' ', IFNULL(d.lastname, '')) as driver_name,
               m.name as material_name,
               CASE 
                   WHEN c.person_type = 'Jurídica' THEN c.business_name
                   ELSE CONCAT(c.firstname, ' ', c.lastname1)
               END as client_name,
               IFNULL(ex.total_trip_expenses, 0) as total_trip_expenses,
               IFNULL(fuel.total_fuel_gallons, 0) as total_fuel_gallons
        FROM trips t 
        LEFT JOIN vehicles v ON t.vehicle_id = v.id 
        LEFT JOIN personnel d ON d.id = t.driver_id 
        LEFT JOIN materials m ON m.id = t.material_id 
        LEFT JOIN clients c ON c.id = t.client_id
        LEFT JOIN (SELECT trip_id, SUM(amount) as total_trip_expenses FROM expenses GROUP BY trip_id) ex ON t.id = ex.trip_id
        LEFT JOIN (SELECT e.trip_id, SUM(e.gallons) as total_fuel_gallons FROM expenses e INNER JOIN expense_categories ec ON e.category_id = ec.id WHERE ec.slug = 'combustible' OR ec.parent_id = (SELECT id FROM expense_categories WHERE slug = 'combustible') GROUP BY e.trip_id) fuel ON t.id = fuel.trip_id"
        . $whereSql . " ORDER BY t.date_load DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

$hasFilters = !empty($_GET['status']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) ||
    !empty($_GET['vehicle_id']) || !empty($_GET['driver_id']) || !empty($_GET['client_id']);

// Check for active trips in progress
$activeTripsCount = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'En Progreso'")->fetchColumn();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php if ($activeTripsCount > 0): ?>
    <div class="mb-4 bg-amber-50 border-l-4 border-amber-400 p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <div class="flex-1">
            <p class="text-sm font-medium text-amber-800">
                ⚠️ Hay <strong><?php echo $activeTripsCount; ?> viaje(s) en progreso</strong> sin finalizar.
                Asegúrese de que el vehículo y conductor no tengan un viaje activo antes de crear uno nuevo.
            </p>
        </div>
    </div>
    <?php endif; ?>
    <?php displayAlerts(); ?>
    
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Registro de Viajes</h1>
            <p class="mt-2 text-sm text-gray-700">Historial completo de operaciones logísticas.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex space-x-3">
            <a href="trips_export.php"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Exportar
            </a>
            <a href="trips_import.php"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Importar
            </a>
            <a href="trip_create.php" id="btn-nuevo-viaje"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                Registrar Nuevo Viaje
            </a>
        </div>
    </div>

    <!-- Filter Console Compact -->
    <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm"
        x-data="{ expanded: <?php echo $hasFilters ? 'true' : 'false'; ?> }">
        <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between cursor-pointer animate-fade-in"
            @click="expanded = !expanded">
            <h3 class="text-sm font-bold text-gray-700 flex items-center">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filtros de Búsqueda
            </h3>
            <span class="text-xs text-brand-600 font-semibold hover:underline" x-text="expanded ? 'Ocultar filtros' : 'Mostrar filtros'"></span>
        </div>
        <div x-show="expanded" x-collapse>
            <form method="GET" class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Fecha Desde/Hasta</label>
                    <div class="flex items-center space-x-1">
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <span class="text-gray-400 text-xs">a</span>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Estado</label>
                    <select name="status"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <option value="">Todos</option>
                        <option value="En Progreso" <?php echo ($_GET['status'] ?? '') === 'En Progreso' ? 'selected' : ''; ?>>En Progreso</option>
                        <option value="Entregado" <?php echo ($_GET['status'] ?? '') === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                        <option value="Finalizado" <?php echo ($_GET['status'] ?? '') === 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                        <option value="Cancelado" <?php echo ($_GET['status'] ?? '') === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Vehículo</label>
                    <select name="vehicle_id"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <option value="">Todos</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo ($_GET['vehicle_id'] ?? '') == $v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['placa']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Conductor</label>
                    <select name="driver_id"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <option value="">Todos</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo ($_GET['driver_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['firstname'] . ' ' . $d['lastname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cliente</label>
                    <select name="client_id"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-xs">
                        <option value="">Todos</option>
                        <?php foreach ($clients as $c): ?>
                            <?php
                                $cName = $c['person_type'] === 'Jurídica' ? $c['business_name'] : ($c['firstname'] . ' ' . $c['lastname1']);
                            ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($_GET['client_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-5 flex justify-end items-center space-x-3 border-t border-gray-100 pt-4 mt-2">
                    <a href="trips.php"
                        class="text-xs text-gray-500 hover:text-gray-700 font-medium px-4 py-2 rounded border border-gray-200 hover:bg-gray-50 transition-colors">Limpiar Filtros</a>
                    <button type="submit"
                        class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2 rounded text-xs font-bold shadow-sm transition-colors">Filtrar Resultados</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">ID / ODT</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Carga y Cliente</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ruta Logística</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vehículo / Conductor</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Análisis / KPI</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Valores</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Gestión</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php if (empty($trips)): ?>
                                <tr><td colspan="7" class="py-10 text-center text-sm text-gray-500 italic">No hay registros de viajes activos.</td></tr>
                            <?php else: ?>
                                <?php foreach ($trips as $trip): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-400 sm:pl-6">
                                            #<?php echo str_pad($trip['id'], 5, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($trip['material_name'] ?? 'Carga No Def.'); ?></span>
                                                <span class="text-xs font-semibold text-brand-600 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($trip['client_name'] ?? 'N/A'); ?></span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <div class="flex items-center text-xs font-bold text-gray-700">
                                                <span><?php echo htmlspecialchars($trip['origin']); ?></span>
                                                <svg class="w-3 h-3 mx-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                <span><?php echo htmlspecialchars($trip['destination']); ?></span>
                                            </div>
                                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block"><?php echo date('d M, Y', strtotime($trip['date_load'])); ?></span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <p class="text-sm font-bold text-gray-900 leading-none"><?php echo htmlspecialchars($trip['placa']); ?></p>
                                            <p class="text-xs font-semibold text-gray-400 mt-1"><?php echo htmlspecialchars($trip['driver_name']); ?></p>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php 
                                                $statusClass = match($trip['status']) {
                                                    'En Progreso' => 'bg-amber-100 text-amber-800',
                                                    'Entregado' => 'bg-blue-100 text-blue-800',
                                                    'Finalizado' => 'bg-green-100 text-green-800',
                                                    default => 'bg-gray-100 text-gray-700'
                                                };
                                            ?>
                                            <div class="inline-status" data-trip-id="<?php echo $trip['id']; ?>">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 cursor-pointer hover:ring-2 hover:ring-brand-400 status-badge <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($trip['status']); ?>
                                                </span>
                                                <select class="hidden status-select text-xs rounded border-gray-300 py-0.5 px-1" data-trip-id="<?php echo $trip['id']; ?>">
                                                    <option value="En Progreso" <?php echo $trip['status'] === 'En Progreso' ? 'selected' : ''; ?>>En Progreso</option>
                                                    <option value="Entregado" <?php echo $trip['status'] === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                                                    <option value="Finalizado" <?php echo $trip['status'] === 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                                                    <option value="Cancelado" <?php echo $trip['status'] === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php 
                                            // Calc Days
                                            $days = 0;
                                            if ($trip['date_load'] && $trip['date_unload']) {
                                                $d1 = new DateTime($trip['date_load']);
                                                $d2 = new DateTime($trip['date_unload']);
                                                $days = $d1->diff($d2)->days;
                                                if ($days == 0) $days = 1;
                                            }
                                            // Calc KPLG
                                            $kplg = ($trip['total_fuel_gallons'] > 0) ? ($trip['kms_total'] / $trip['total_fuel_gallons']) : 0;
                                            // Calc Margin
                                            $margin = ($trip['flete_neto'] > 0) ? (($trip['flete_neto'] - $trip['total_trip_expenses']) / $trip['flete_neto']) * 100 : 0;
                                            ?>
                                            <div class="flex flex-col space-y-1">
                                                <div class="flex items-center text-[10px] uppercase font-bold text-slate-400">
                                                    <span class="w-16">Tiempo:</span>
                                                    <span class="text-slate-700"><?php echo $days ?: '-'; ?> días</span>
                                                </div>
                                                <div class="flex items-center text-[10px] uppercase font-bold text-slate-400">
                                                    <span class="w-16">KPL/G:</span>
                                                    <span class="text-emerald-600"><?php echo number_format($kplg, 1); ?></span>
                                                </div>
                                                <div class="flex items-center text-[10px] uppercase font-bold text-slate-400">
                                                    <span class="w-16">Margen:</span>
                                                    <span class="<?php echo $margin > 0 ? 'text-blue-600' : 'text-red-600'; ?>"><?php echo number_format($margin, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-right">
                                            <div class="flex flex-col items-end">
                                                <span class="text-sm font-bold text-gray-900 tabular-nums"><?php echo formatCurrency($trip['flete_neto']); ?></span>
                                                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-tighter">Com: <?php echo formatCurrency($trip['commission_value']); ?></span>
                                            </div>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <div class="flex items-center justify-end space-x-3">
                                                <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Ver Detalles">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                </a>
                                                <a href="trip_create.php?edit=<?php echo $trip['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </a>
                                                <a href="trip_delete.php?id=<?php echo $trip['id']; ?>" onclick="return confirm('¿Confirmar eliminación permanente?');" class="text-red-600 hover:text-red-900" title="Eliminar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Controls -->
                    <?php if ($totalPages > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Anterior</a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Siguiente</a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando <span class="font-medium"><?php echo $offset + 1; ?></span> a <span class="font-medium"><?php echo min($offset + $limit, $totalRows); ?></span> de <span class="font-medium"><?php echo $totalRows; ?></span> registros
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Anterior</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </a>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $queryString; ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-brand-50 border-brand-500 text-brand-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <a href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Siguiente</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';

document.addEventListener('click', function (e) {
    const badge = e.target.closest('.status-badge');
    if (!badge) return;

    const container = badge.closest('.inline-status');
    const select = container.querySelector('.status-select');
    badge.classList.add('hidden');
    select.classList.remove('hidden');
    select.focus();
});

document.addEventListener('change', function (e) {
    const select = e.target.closest('.status-select');
    if (!select) return;

    const newStatus = select.value;
    const tripId = select.dataset.tripId;
    const container = select.closest('.inline-status');
    const badge = container.querySelector('.status-badge');

    fetch('api.php?action=updateStatus', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
        body: JSON.stringify({ trip_id: tripId, status: newStatus, csrf_token: CSRF_TOKEN })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const colors = {
                'En Progreso': 'bg-amber-100 text-amber-800',
                'Entregado': 'bg-blue-100 text-blue-800',
                'Finalizado': 'bg-green-100 text-green-800',
                'Cancelado': 'bg-red-100 text-red-800'
            };
            badge.textContent = newStatus;
            badge.className = 'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 cursor-pointer hover:ring-2 hover:ring-brand-400 status-badge ' + (colors[newStatus] || 'bg-gray-100 text-gray-700');
        } else {
            alert('Error: ' + (data.error || 'No se pudo actualizar el estado'));
            select.value = badge.textContent;
        }
    })
    .catch(() => {
        alert('Error de conexión al actualizar el estado');
        select.value = badge.textContent;
    })
    .finally(() => {
        select.classList.add('hidden');
        badge.classList.remove('hidden');
    });
});

// Blur on select = cancel (revert)
document.addEventListener('blur', function (e) {
    const select = e.target.closest('.status-select');
    if (!select) return;
    // Only cancel if no change was made (change event fires before blur)
    setTimeout(() => {
        if (!select.classList.contains('hidden')) {
            const badge = select.closest('.inline-status').querySelector('.status-badge');
            select.value = badge.textContent;
            select.classList.add('hidden');
            badge.classList.remove('hidden');
        }
    }, 150);
}, true);

// Confirm before creating a new trip if there are active trips
<?php if ($activeTripsCount > 0): ?>
document.getElementById('btn-nuevo-viaje')?.addEventListener('click', function (e) {
    if (!confirm('⚠️ Hay <?php echo $activeTripsCount; ?> viaje(s) en progreso sin finalizar.\n\n¿Desea continuar creando un nuevo viaje?')) {
        e.preventDefault();
    }
});
<?php endif; ?>
</script>
<?php include 'includes/footer.php'; ?>
