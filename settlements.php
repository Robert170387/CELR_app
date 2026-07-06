<?php
include 'includes/db.php';
include 'includes/header.php';

// Filters
$driver_id = $_GET['driver_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$params = [];
$where = "WHERE 1=1";

if ($driver_id) {
    $where .= " AND s.personnel_id = ?";
    $params[] = $driver_id;
}
if ($date_from) {
    $where .= " AND s.date_start >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where .= " AND s.date_end <= ?";
    $params[] = $date_to;
}

$sql = "SELECT s.*, p.firstname, p.lastname 
        FROM settlements s
        LEFT JOIN personnel p ON s.driver_id = p.id
        $where
        ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$settlements = $stmt->fetchAll();

$drivers = $pdo->query("SELECT id, firstname, lastname FROM personnel WHERE type='Conductor' ORDER BY firstname ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Historial de Liquidaciones
            </h2>
            <p class="mt-1 text-sm text-gray-500">Consulta y gestiona los desprendibles de pago generados.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="settlement_form.php"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                Nueva Liquidación
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 shadow rounded-lg mb-8 border border-gray-100">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Conductor</label>
                <select name="driver_id"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-md">
                    <option value="">Todos</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $driver_id == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['firstname'] . ' ' . $d['lastname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
            </div>
            <div class="flex space-x-2">
                <button type="submit"
                    class="flex-1 bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition font-medium text-sm">Filtrar</button>
                <a href="settlements.php"
                    class="bg-gray-100 text-gray-600 py-2 px-4 rounded-md hover:bg-gray-200 transition font-medium text-sm">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md border border-gray-200">
        <ul class="divide-y divide-gray-200">
            <?php if (empty($settlements)): ?>
                <li class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay liquidaciones</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva liquidación de pago.</p>
                </li>
            <?php else: ?>
                <?php foreach ($settlements as $s): ?>
                    <li class="hover:bg-gray-50 transition">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span
                                            class="h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                                            <?php echo strtoupper(substr($s['firstname'], 0, 1) . substr($s['lastname'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-bold text-brand-600 truncate">
                                            <?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Periodo:
                                            <?php echo date('d/m/Y', strtotime($s['date_start'])); ?> -
                                            <?php echo date('d/m/Y', strtotime($s['date_end'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-black text-gray-900">
                                        <?php echo formatCurrency($s['net_to_pay']); ?>
                                    </p>
                                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">
                                        Neto Pagado
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-xs text-gray-400">
                                    Creado el:
                                    <?php echo date('d/m/Y H:i', strtotime($s['created_at'])); ?>
                                </div>
                                <div class="flex space-x-3">
                                    <a href="settlement_details.php?id=<?php echo $s['id']; ?>"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                        Ver Desprendible
                                    </a>
                                    <a href="settlement_delete.php?id=<?php echo $s['id']; ?>"
                                        onclick="return confirm('¿Está seguro de eliminar esta liquidación? Esta acción no se puede deshacer.')"
                                        class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>