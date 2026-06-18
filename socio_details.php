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
