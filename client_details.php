<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: clients.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    $_SESSION['error'] = "Cliente no encontrado.";
    header("Location: clients.php");
    exit;
}

// Navigation: Find Previous and Next Client IDs
$stmtPrev = $pdo->prepare("SELECT MAX(id) FROM clients WHERE id < ?");
$stmtPrev->execute([$id]);
$prevId = $stmtPrev->fetchColumn();

$stmtNext = $pdo->prepare("SELECT MIN(id) FROM clients WHERE id > ?");
$stmtNext->execute([$id]);
$nextId = $stmtNext->fetchColumn();

// Get trips count for this client
$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE client_id = ?");
$stmt->execute([$id]);
$tripsCount = $stmt->fetchColumn();

// Get recent trips
$stmt = $pdo->prepare("
    SELECT t.*, v.placa, CONCAT(p.firstname, ' ', p.lastname) as driver_name
    FROM trips t
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    LEFT JOIN personnel p ON t.driver_id = p.id
    WHERE t.client_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt->execute([$id]);
$recentTrips = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php displayAlerts(); ?>

    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0 flex items-center">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Detalles del Cliente
            </h2>

            <!-- Navigation Buttons -->
            <span class="inline-flex rounded-md shadow-sm ml-6">
                <?php if ($prevId): ?>
                    <a href="client_details.php?id=<?php echo $prevId; ?>"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-700 bg-white hover:bg-gray-50"
                        title="Anterior">
                        &larr; Ant
                    </a>
                <?php else: ?>
                    <span
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-400 bg-gray-100 cursor-not-allowed">
                        &larr; Ant
                    </span>
                <?php endif; ?>

                <?php if ($nextId): ?>
                    <a href="client_details.php?id=<?php echo $nextId; ?>"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-white hover:bg-gray-50"
                        title="Siguiente">
                        Sig &rarr;
                    </a>
                <?php else: ?>
                    <span
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-400 bg-gray-100 cursor-not-allowed">
                        Sig &rarr;
                    </span>
                <?php endif; ?>
            </span>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="clients.php"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Volver
            </a>
            <a href="client_form.php?id=<?php echo $client['id']; ?>"
                class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700">
                Editar
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <?php
                    if ($client['person_type'] == 'Jurídica') {
                        echo htmlspecialchars($client['business_name']);
                    } else {
                        echo htmlspecialchars($client['firstname'] . ' ' . $client['lastname1'] . ' ' . ($client['lastname2'] ?? ''));
                    }
                    ?>
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    <span
                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold 
                        <?php echo $client['person_type'] == 'Jurídica' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                        <?php echo htmlspecialchars($client['person_type']); ?>
                    </span>
                </p>
            </div>
            <div>
                <?php if ($client['active']): ?>
                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                        Activo
                    </span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-800">
                        Inactivo
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">

                <?php if ($client['legal_id']): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Cédula / RUC</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($client['legal_id']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if ($client['email']): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"
                                class="text-brand-600 hover:text-brand-900">
                                <?php echo htmlspecialchars($client['email']); ?>
                            </a>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if ($client['phone']): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($client['phone']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if ($client['mobile']): ?>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Móvil</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($client['mobile']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if ($client['address']): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($client['address']); ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Ubicación</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php
                        $location = [];
                        if ($client['city'])
                            $location[] = $client['city'];
                        if ($client['department'])
                            $location[] = $client['department'];
                        if ($client['country'])
                            $location[] = $client['country'];
                        echo htmlspecialchars(implode(', ', $location) ?: 'No especificada');
                        ?>
                    </dd>
                </div>

                <?php if ($client['notes']): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notas</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo nl2br(htmlspecialchars($client['notes'])); ?>
                        </dd>
                    </div>
                <?php endif; ?>

            </dl>
        </div>
    </div>

    <!-- Trips Section -->
    <div class="mt-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h3 class="text-lg font-medium text-gray-900">Viajes Realizados</h3>
                <p class="mt-1 text-sm text-gray-500">Total de viajes:
                    <?php echo $tripsCount; ?>
                </p>
            </div>
        </div>

        <?php if (!empty($recentTrips)): ?>
            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                    <?php foreach ($recentTrips as $trip): ?>
                        <li>
                            <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="block hover:bg-gray-50">
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-brand-600 truncate">
                                                <?php echo htmlspecialchars($trip['origin'] . ' → ' . $trip['destination']); ?>
                                            </p>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Vehículo:
                                                <?php echo htmlspecialchars($trip['placa']); ?> |
                                                Conductor:
                                                <?php echo htmlspecialchars($trip['driver_name']); ?>
                                            </p>
                                        </div>
                                        <div class="ml-2 flex-shrink-0 flex">
                                            <p
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $trip['status'] === 'Finalizado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo htmlspecialchars($trip['status']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php if ($tripsCount > 5): ?>
                <div class="mt-4 text-center">
                    <a href="trips.php?client_id=<?php echo $client['id']; ?>"
                        class="text-sm text-brand-600 hover:text-brand-900">
                        Ver todos los viajes (
                        <?php echo $tripsCount; ?>) →
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                <p class="text-sm text-gray-500">No hay viajes registrados para este cliente.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>