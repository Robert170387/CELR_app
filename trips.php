<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

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
        LEFT JOIN (SELECT trip_id, SUM(gallons) as total_fuel_gallons FROM expenses WHERE category='combustible' OR category='Combustible' GROUP BY trip_id) fuel ON t.id = fuel.trip_id";

$where = [];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "t.status = ?";
    $params[] = $_GET['status'];
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY t.date_load DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
            <a href="trip_create.php"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                Registrar Nuevo Viaje
            </a>
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
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 <?php echo $statusClass; ?>">
                                                <?php echo $trip['status']; ?>
                                            </span>
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
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
