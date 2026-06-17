<?php
include 'includes/db.php';
include 'includes/functions.php'; // For currency formatting
include 'includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: personnel.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM personnel WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    die("Personal no encontrado.");
}

// Navigation: Find Previous and Next Personnel IDs
$stmtPrev = $pdo->prepare("SELECT MAX(id) FROM personnel WHERE id < ?");
$stmtPrev->execute([$id]);
$prevId = $stmtPrev->fetchColumn();

$stmtNext = $pdo->prepare("SELECT MIN(id) FROM personnel WHERE id > ?");
$stmtNext->execute([$id]);
$nextId = $stmtNext->fetchColumn();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center">
            <h1 class="text-2xl font-bold text-gray-900">Detalles del Personal</h1>

            <!-- Navigation Buttons -->
            <span class="inline-flex rounded-md shadow-sm ml-6">
                <?php if ($prevId): ?>
                    <a href="personnel_details.php?id=<?php echo $prevId; ?>"
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
                    <a href="personnel_details.php?id=<?php echo $nextId; ?>"
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
            <a href="personnel_form.php?id=<?php echo $p['id']; ?>"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none mr-2">
                Editar
            </a>
            <a href="personnel.php"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <?php echo htmlspecialchars($p['firstname'] . ' ' . $p['lastname']); ?>
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $p['type'] === 'Administrativo' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                        <?php echo ucfirst($p['type']); ?>
                    </span>
                    <span
                        class="ml-2 text-gray-600"><?php echo $p['document_type'] . ' ' . $p['document_number']; ?></span>
                </p>
            </div>
            <?php if ($p['active']): ?>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Activo</span>
            <?php else: ?>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Inactivo</span>
            <?php endif; ?>
        </div>

        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">

                <!-- Section: Personal Info -->
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Género</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo $p['gender']; ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo $p['address'] . ' - ' . $p['city'] . ', ' . $p['department']; ?>
                    </dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo $p['phone']; ?></dd>
                </div>

                <!-- Section: Professional (Only if Conductor or has data) -->
                <?php if (!empty($p['license_number'])): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-blue-50">
                        <dt class="text-sm font-medium text-blue-900">Licencia Conducción</dt>
                        <dd class="mt-1 text-sm text-blue-900 sm:mt-0 sm:col-span-2 font-bold">
                            <?php echo $p['license_number'] . ' (Categoría ' . $p['license_category'] . ')'; ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <!-- Section: Contractual -->
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Fechas Contrato</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        Ingreso:
                        <strong><?php echo $p['date_entry'] ? date('d/m/Y', strtotime($p['date_entry'])) : '-'; ?></strong>
                        <?php if ($p['date_exit']): ?>
                            <br>Salida: <strong><?php echo date('d/m/Y', strtotime($p['date_exit'])); ?></strong>
                        <?php endif; ?>
                    </dd>
                </div>

                <!-- Section: Financial -->
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-500">Salarios</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <ul class="list-disc pl-5">
                            <li>Básico: <?php echo formatCurrency($p['salary_basic']); ?></li>
                            <li>Variable: <?php echo formatCurrency($p['salary_variable']); ?></li>
                            <li>Interno: <?php echo formatCurrency($p['salary_internal']); ?></li>
                        </ul>
                    </dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Cuenta Bancaria</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-mono">
                        <?php echo $p['bank_account']; ?>
                    </dd>
                </div>

            </dl>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>