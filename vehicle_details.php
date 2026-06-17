<?php
include 'includes/db.php';
include 'includes/header.php';
// include 'includes/functions.php'; // Removed redundant include

if (!isset($_GET['id'])) {
    header("Location: vehicles.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT v.*, CONCAT(p.firstname, ' ', p.lastname) as partner_name 
                       FROM vehicles v 
                       LEFT JOIN personnel p ON v.partner_id = p.id 
                       WHERE v.id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die("Vehículo no encontrado.");
}

// Navigation: Find Previous and Next Vehicle IDs
$stmtPrev = $pdo->prepare("SELECT MAX(id) FROM vehicles WHERE id < ?");
$stmtPrev->execute([$id]);
$prevId = $stmtPrev->fetchColumn();

$stmtNext = $pdo->prepare("SELECT MIN(id) FROM vehicles WHERE id > ?");
$stmtNext->execute([$id]);
$nextId = $stmtNext->fetchColumn();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center">
            <h1 class="text-2xl font-bold text-gray-900">Detalles del Vehículo</h1>

            <!-- Navigation Buttons -->
            <span class="inline-flex rounded-md shadow-sm ml-6">
                <?php if ($prevId): ?>
                    <a href="vehicle_details.php?id=<?php echo $prevId; ?>"
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
                    <a href="vehicle_details.php?id=<?php echo $nextId; ?>"
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
        <div>
            <a href="vehicle_form.php?id=<?php echo $vehicle['id']; ?>"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none mr-2">
                Editar
            </a>
            <a href="vehicles.php"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Información General</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Detalles técnicos y administrativos.</p>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Placa</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold">
                        <?php echo htmlspecialchars($vehicle['placa']); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Categoría</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['category'] ?? '-'); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <span
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $vehicle['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $vehicle['active'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Marca / Línea</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Modelo</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['model_year'] ?? '-'); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Color</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['color'] ?? '-'); ?>
                    </dd>
                </div>

                <!-- Identification -->
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">No. Motor</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['motor_number'] ?? '-'); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">No. Chasis</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['chasis_number'] ?? '-'); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">No. Serie</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['serial_number'] ?? '-'); ?>
                    </dd>
                </div>

                <!-- Specs -->
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Cilindraje</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['displacement'] ?? '-'); ?> cc
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Cap. Carga</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['max_load'] ?? '-'); ?> kg
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Ciudad Registro</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($vehicle['register_city'] ?? '-'); ?>
                    </dd>
                </div>

                <!-- Ownership Display -->
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-t border-gray-100">
                    <dt class="text-sm font-bold text-brand-600 uppercase tracking-wide">Propiedad</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 bg-brand-100 text-brand-800 rounded-md font-bold">
                                <?php echo htmlspecialchars($vehicle['ownership_type'] ?? 'Propio'); ?>
                            </span>
                            <?php if ($vehicle['ownership_type'] !== 'Propio'): ?>
                                <span class="text-gray-600">
                                    <?php echo ($vehicle['ownership_type'] === 'Socio' ? 'con' : 'de') . ': '; ?>
                                    <strong
                                        class="text-gray-900"><?php echo htmlspecialchars($vehicle['partner_name'] ?? 'N/A'); ?></strong>
                                </span>
                                <?php if ($vehicle['ownership_type'] === 'Socio'): ?>
                                    <span
                                        class="ml-2 px-2 py-1 bg-blue-50 text-blue-700 border border-blue-100 rounded-md text-xs font-bold">
                                        Distribución: <?php echo (100 - (float) $vehicle['partner_percentage']); ?>% Mío /
                                        <?php echo (float) $vehicle['partner_percentage']; ?>% Socio
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-500 font-medium italic">Vehículo bajo control total.</span>
                            <?php endif; ?>
                        </div>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>