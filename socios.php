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
