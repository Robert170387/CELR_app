<?php
include 'header.php';
require_once '../app/Controllers/TripController.php';

use App\Controllers\TripController;

$userId = $_SESSION['user_id'];
$role = getCurrentRole();

// Fetch link info
$stmtUser = $pdo->prepare("SELECT related_client_id, related_personnel_id FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$userData = $stmtUser->fetch();

$clientId = $userData['related_client_id'] ?? null;
$personnelId = $userData['related_personnel_id'] ?? null;

$controller = new TripController();
$portalData = [];
$linkedId = ($role === 'cliente') ? $clientId : $personnelId;

if ($linkedId) {
    $portalData = $controller->getPortalData($role, $linkedId);
}

$trips = $portalData['trips'] ?? [];
$summary = $portalData['summary'] ?? ['total' => 0, 'active' => 0, 'completed' => 0];
?>

<div class="mb-8">
    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center gap-3">
            <div class="bg-emerald-500 rounded-full p-1">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span class="text-emerald-400 text-xs font-black uppercase tracking-widest">Planilla reportada con éxito</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl flex items-center gap-3">
            <div class="bg-rose-500 rounded-full p-1">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <span class="text-rose-400 text-xs font-black uppercase tracking-widest">Error:
                <?php echo htmlspecialchars($_GET['error']); ?></span>
        </div>
    <?php endif; ?>

    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Bienvenido a su Panel de Control</h1>
    <p class="text-gray-500">Resumen de operaciones y seguimiento de servicios.</p>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Servicios</p>
        <h3 class="text-3xl font-black text-gray-900">
            <?php echo $summary['total']; ?>
        </h3>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <p class="text-xs font-bold text-brand-600 uppercase tracking-widest mb-1">En Tránsito</p>
        <h3 class="text-3xl font-black text-brand-700">
            <?php echo $summary['active']; ?>
        </h3>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Completados</p>
        <h3 class="text-3xl font-black text-emerald-700">
            <?php echo $summary['completed']; ?>
        </h3>
    </div>
</div>

<!-- Planilla Digital Form (Conductors Only) -->
<?php
$activeTrip = null;
if ($role === 'conductor' && $personnelId) {
    foreach ($trips as $trip) {
        if ($trip['status'] === 'En Progreso') {
            $activeTrip = $trip;
            break;
        }
    }
}
?>

<?php if ($activeTrip): ?>

    <!-- Planilla Digital Unificada (Light SaaS Theme) -->
    <div class="mb-12" id="digital-sheet-section">
        <div class="celr-card border-l-4 border-l-brand-600 relative overflow-hidden">
            <!-- Header Planilla -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b border-slate-100 pb-6 gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-brand-50 rounded-lg text-brand-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 tracking-tight">PLANILLA DIGITAL 2025</h2>
                            <p class="text-sm font-medium text-slate-500">
                                ODT-<?php echo $activeTrip['id']; ?> •
                                <span
                                    class="text-slate-900 font-bold"><?php echo htmlspecialchars($activeTrip['placa']); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div>
                    <span
                        class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-2 w-fit">
                        <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                        Viaje en Progreso
                    </span>
                </div>
            </div>

            <form action="save_digital_sheet.php" method="POST" class="space-y-8 persist-form" id="digitalSheetForm">
                <input type="hidden" name="trip_id" value="<?php echo $activeTrip['id']; ?>">

                <!-- Seccion 1: Kilometraje y Ruta -->
                <div class="form-section">
                    <div class="form-section-header bg-slate-50/50">
                        <h3 class="form-section-title flex items-center gap-2">
                            <span>📍</span> Ruta y Kilometraje
                        </h3>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid form-grid-2">
                            <div>
                                <label class="form-label">Origen</label>
                                <input type="text" name="origin"
                                    value="<?php echo htmlspecialchars($activeTrip['origin']); ?>"
                                    class="form-input bg-slate-50" readonly>
                            </div>
                            <div>
                                <label class="form-label">Destino</label>
                                <input type="text" name="destination"
                                    value="<?php echo htmlspecialchars($activeTrip['destination']); ?>"
                                    class="form-input bg-slate-50" readonly>
                            </div>
                            <div>
                                <label class="form-label">Km Inicial</label>
                                <input type="number" name="kms_start" value="<?php echo (int) $activeTrip['kms_start']; ?>"
                                    class="form-input font-mono" readonly>
                            </div>
                            <div>
                                <label class="form-label form-label-required">Km Final (Llegada)</label>
                                <input type="number" name="kms_end" value="<?php echo (int) $activeTrip['kms_end']; ?>"
                                    placeholder="Ingrese Km al llegar" class="form-input font-bold text-brand-600 text-lg">
                            </div>
                            <div>
                                <label class="form-label">Fecha Cargue</label>
                                <input type="date" name="date_load" value="<?php echo $activeTrip['date_load']; ?>"
                                    class="form-input bg-slate-50" readonly>
                            </div>
                            <div>
                                <label class="form-label">Fecha Descargue</label>
                                <input type="date" name="date_unload"
                                    value="<?php echo $activeTrip['date_unload'] ?: date('Y-m-d'); ?>" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seccion 2: Combustible -->
                <div class="form-section border-amber-100">
                    <div class="form-section-header bg-amber-50/50">
                        <h3 class="form-section-title text-amber-900 flex items-center gap-2">
                            <span>⛽</span> Registro de Combustible
                        </h3>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid form-grid-2">
                            <div>
                                <label class="form-label">Valor Total ($)</label>
                                <div class="input-group">
                                    <span class="input-addon">$</span>
                                    <input type="number" name="expenses[combustible][amount]" placeholder="0"
                                        class="form-input text-lg font-bold">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Galones</label>
                                <input type="number" step="0.01" name="expenses[combustible][gallons]" placeholder="0.00"
                                    class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Km al Tanquear</label>
                                <input type="number" name="expenses[combustible][tank_mileage]" placeholder="Odómetro foto"
                                    class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Precio $/Galón</label>
                                <input type="number" step="0.01" name="expenses[combustible][price_per_gallon]"
                                    placeholder="0.00" class="form-input">
                            </div>
                            <div class="form-grid-full">
                                <label class="form-label">Método de Pago</label>
                                <select name="expenses[combustible][payment_method]" class="form-select">
                                    <option value="Efectivo">Efectivo (Contado)</option>
                                    <option value="Crédito">Crédito / Vale</option>
                                    <option value="Tarjeta">Tarjeta / Electrónico</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seccion 3: Gastos Rápidos -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title flex items-center gap-2">
                            <span>💸</span> Otros Gastos Operativos
                        </h3>
                    </div>
                    <div class="form-section-body">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php
                            $quickExpenses = [
                                'peajes' => 'Peajes',
                                'lavado' => 'Lavado',
                                'engrace' => 'Engrace',
                                'bascula' => 'Báscula'
                            ];
                            foreach ($quickExpenses as $slug => $label): ?>
                                <div>
                                    <label class="form-label text-xs uppercase"><?php echo $label; ?></label>
                                    <div class="input-group">
                                        <span class="input-addon px-2 text-xs">$</span>
                                        <input type="number" name="expenses[<?php echo $slug; ?>][amount]" placeholder="0"
                                            class="form-input">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Seccion 4: Anticipos (Info) -->
                <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
                    <h4 class="text-sm font-bold text-blue-900 uppercase mb-4">💰 Anticipos Recibidos</h4>
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <span class="block text-xs text-blue-600 mb-1">Por Manifiesto</span>
                            <span
                                class="text-xl font-bold text-blue-900">$<?php echo number_format($activeTrip['advance_manifest']); ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-blue-600 mb-1">Del Propietario</span>
                            <span
                                class="text-xl font-bold text-blue-900">$<?php echo number_format($activeTrip['advance_owner']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="border-t border-slate-100 pt-6 mt-8">
                    <label
                        class="flex items-start gap-3 p-4 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer mb-6 hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="status" value="Entregado"
                            class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                        <div>
                            <span class="block text-sm font-bold text-slate-900">NOTIFICAR LLEGADA A DESTINO</span>
                            <span class="block text-xs text-slate-500">Marque esta casilla solo si ya entregó la mercancía y
                                finalizó el viaje.</span>
                        </div>
                    </label>

                    <button type="submit" class="w-full btn-primary py-4 text-lg shadow-xl shadow-brand-500/20">
                        💾 GUARDAR PLANILLA
                    </button>

                    <p class="text-center text-xs text-slate-400 mt-4">
                        Al guardar, confirma que la información es veraz.
                    </p>
                </div>

            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Trips Table -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-50 flex justify-between items-center">
        <h3 class="font-bold text-gray-800">Cargas Recientes</h3>
        <span class="text-xs font-bold text-gray-400">Últimos 20 registros</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Fecha
                        / ODT</th>
                    <?php if ($role === 'cliente'): ?>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            Vehículo</th>
                    <?php else: ?>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            Cliente</th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Ruta
                    </th>
                    <th class="px-6 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Estado</th>
                    <th class="px-6 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Cumplido</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($trips)): ?>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400 italic">No se encontraron servicios
                            registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trips as $t): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">ODT-
                                    <?php echo $t['id']; ?>
                                </div>
                                <div class="text-[10px] text-gray-400 font-bold">
                                    <?php echo date('d/m/Y', strtotime($t['date_load'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-800">
                                    <?php
                                    if ($role === 'cliente') {
                                        echo htmlspecialchars($t['placa']);
                                    } else {
                                        echo htmlspecialchars($t['client_name'] ?: 'N/A');
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-gray-600">
                                    <?php echo htmlspecialchars($t['origin']); ?> &rarr;
                                    <?php echo htmlspecialchars($t['destination']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php
                                $statusColor = ($t['status'] === 'En Progreso' ? 'bg-amber-100 text-amber-700' : ($t['status'] === 'Finalizado' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'));
                                ?>
                                <span
                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold border border-current <?php echo $statusColor; ?>">
                                    <?php echo strtoupper($t['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if (!empty($t['delivery_proof_url'])): ?>
                                    <a href="../<?php echo htmlspecialchars($t['delivery_proof_url']); ?>" target="_blank"
                                        class="text-brand-600 hover:text-brand-800" title="Ver Cumplido">
                                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-300">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>