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
