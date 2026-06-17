<?php
include 'includes/db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: settlements.php");
    exit;
}

// Fetch Settlement
$stmt = $pdo->prepare("SELECT s.*, p.firstname, p.lastname, p.document_number, p.bank_account 
                       FROM settlements s 
                       JOIN personnel p ON s.personnel_id = p.id 
                       WHERE s.id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    die("Liquidación no encontrada.");
}

// Fetch Trips in that range (same logic as API)
$stmtTrips = $pdo->prepare("
    SELECT t.id, t.trip_type, t.commission_value, t.advance_manifest, t.advance_owner, t.advance_manifest_to_driver, 
           t.origin, t.destination, t.manifest_number, t.manifest_date, t.date_load, t.flete_neto,
           v.placa as vehicle_placa, mc.name as manifest_company_name, t.manifest_company as legacy_company,
           CASE 
               WHEN c.person_type = 'Jurídica' THEN c.business_name 
               ELSE CONCAT(c.firstname, ' ', c.lastname1) 
           END as client_name
    FROM trips t
    JOIN vehicles v ON t.vehicle_id = v.id
    LEFT JOIN clients c ON t.client_id = c.id
    LEFT JOIN manifest_companies mc ON t.manifest_company_id = mc.id
    WHERE t.driver_id = ? AND t.date_load BETWEEN ? AND ?
    ORDER BY t.date_load ASC
");
$stmtTrips->execute([$s['personnel_id'], $s['date_start'], $s['date_end']]);
$trips = $stmtTrips->fetchAll();

$countNational = 0;
$countUrban = 0;
foreach ($trips as $t) {
    if ($t['trip_type'] === 'nacional')
        $countNational++;
    if ($t['trip_type'] === 'urbano')
        $countUrban++;
}

// Fetch company config for logo
$stmtC = $pdo->query("SELECT logo_path FROM config LIMIT 1");
$globalConfig = $stmtC->fetch();
?>

<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="mb-6 flex justify-between items-center print:hidden">
        <a href="settlements.php" class="text-sm text-gray-500 hover:text-brand-600 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Volver al Historial
        </a>
        <div class="flex space-x-3">
            <button onclick="window.print()"
                class="bg-gray-900 text-white px-4 py-2 rounded text-sm font-bold shadow hover:bg-black transition">Imprimir
                Desprendible</button>
            <a href="settlement_delete.php?id=<?php echo $s['id']; ?>"
                onclick="return confirm('¿Eliminar esta liquidación?')"
                class="bg-white border border-red-300 text-red-600 px-4 py-2 rounded text-sm font-bold hover:bg-red-50 transition">Eliminar</a>
        </div>
    </div>

    <!-- The Pay Stub -->
    <div class="bg-white shadow-2xl rounded-lg overflow-hidden border border-gray-200 print:shadow-none print:border-0"
        id="paymentStub">
        <!-- Receipt Header -->
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-start">
                <div class="flex items-center space-x-4">
                    <?php if (!empty($globalConfig['logo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($globalConfig['logo_path']); ?>" alt="Logo"
                            class="h-16 w-auto">
                    <?php else: ?>
                        <div
                            class="h-16 w-16 bg-brand-100 flex items-center justify-center rounded text-brand-700 font-bold">
                            LOGO</div>
                    <?php endif; ?>
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight">CELR APP</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase">CARGA EN LA RUTA - SOLUCIONES LOGÍSTICAS
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <h1 class="text-xl font-black text-gray-900 uppercase">Desprendible de Pago</h1>
                    <p class="text-sm font-bold text-gray-500">COMISIONES POR VIAJE</p>
                    <div
                        class="mt-2 text-xs font-mono border border-brand-500 inline-block px-3 py-1 text-brand-600 rounded">
                        RECIBO NO: <span class="font-bold"><?php echo str_pad($s['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-3 gap-8 text-xs">
                <div class="space-y-1">
                    <p><span class="font-bold text-gray-400 uppercase">Conductor:</span> <span
                            class="text-gray-900 font-bold ml-1"><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?></span>
                    </p>
                    <p><span class="font-bold text-gray-400 uppercase">Cédula:</span> <span
                            class="text-gray-900 ml-1"><?php echo $s['document_number']; ?></span></p>
                    <p><span class="font-bold text-gray-400 uppercase">Cuenta:</span> <span
                            class="text-gray-900 ml-1"><?php echo $s['bank_account'] ?: 'N/A'; ?></span></p>
                </div>
                <div class="space-y-1">
                    <p><span class="font-bold text-gray-400 uppercase">Fecha Doc:</span> <span
                            class="text-gray-900 ml-1"><?php echo date('d/m/Y', strtotime($s['created_at'])); ?></span>
                    </p>
                    <p><span class="font-bold text-gray-400 uppercase">Periodo:</span> <span
                            class="text-gray-900 ml-1"><?php echo date('d/m/Y', strtotime($s['date_start'])); ?> -
                            <?php echo date('d/m/Y', strtotime($s['date_end'])); ?></span></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-400 italic">Este documento es un soporte interno de liquidación.</p>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-[9px]">
                <thead class="bg-gray-900 text-white uppercase font-bold">
                    <tr>
                        <th class="px-2 py-2 text-left">ODT</th>
                        <th class="px-2 py-2 text-left">F. Manif.</th>
                        <th class="px-2 py-2 text-left">Vehículo</th>
                        <th class="px-2 py-2 text-left">Origen - Destino</th>
                        <th class="px-2 py-2 text-left">Empresa / N° Manifiesto</th>
                        <th class="px-2 py-2 text-left">Tipo</th>
                        <th class="px-2 py-2 text-right">Flete Neto</th>
                        <th class="px-2 py-2 text-right">Ant. Manif.</th>
                        <th class="px-2 py-2 text-right">Ant. Prop.</th>
                        <th class="px-2 py-2 text-right">Gastos</th>
                        <th class="px-2 py-2 text-right">Comisión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($trips as $t): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 font-bold text-gray-900">ODT-<?php echo $t['id']; ?></td>
                            <td class="px-2 py-2 whitespace-nowrap text-slate-950 font-medium">
                                <?php echo date('d/m/Y', strtotime($t['manifest_date'] ?: $t['date_load'])); ?>
                            </td>
                            <td class="px-2 py-2 font-bold text-gray-700"><?php echo $t['vehicle_placa']; ?></td>
                            <td class="px-2 py-2 text-slate-950 font-medium">
                                <?php echo $t['origin'] . ' - ' . $t['destination']; ?>
                            </td>
                            <td class="px-2 py-2 text-slate-950">
                                <div class="font-bold">
                                    <?php echo htmlspecialchars($t['client_name'] ?: ($t['manifest_company_name'] ?: ($t['legacy_company'] ?: 'N/A'))); ?>
                                </div>
                                <div><?php echo $t['manifest_number']; ?></div>
                            </td>
                            <td class="px-2 py-2 capitalize font-bold text-gray-600"><?php echo $t['trip_type']; ?></td>
                            <td class="px-2 py-2 text-right font-medium text-gray-900">
                                <?php echo formatCurrency($t['flete_neto']); ?>
                            </td>
                            <td class="px-2 py-2 text-right text-slate-950 font-medium">
                                <?php echo formatCurrency($t['advance_manifest']); ?>
                            </td>
                            <td class="px-2 py-2 text-right text-slate-950 font-medium">
                                <?php echo formatCurrency($t['advance_owner']); ?>
                            </td>
                            <?php
                            $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE trip_id = ? AND paid_by = 'Conductor'");
                            $stmtExp->execute([$t['id']]);
                            $tripExpenses = $stmtExp->fetchColumn() ?: 0;
                            ?>
                            <td class="px-2 py-2 text-right text-red-600"><?php echo formatCurrency($tripExpenses); ?></td>
                            <td class="px-2 py-2 text-right font-bold text-brand-700">
                                <?php echo formatCurrency($t['commission_value']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Bottom Summary -->
        <div class="p-8 bg-white grid grid-cols-2 gap-8 border-t border-gray-100">
            <div class="space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <h4 class="text-[10px] font-black text-slate-950 uppercase mb-2 border-b border-slate-200 pb-1">
                        Conteo
                        de Operación</h4>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-950 font-medium">Viajes Nacionales:</span>
                        <span class="font-black text-gray-900"><?php echo $countNational; ?></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-950 font-medium">Viajes Urbanos:</span>
                        <span class="font-black text-gray-900"><?php echo $countUrban; ?></span>
                    </div>
                </div>

                <div class="pt-2 border-t border-dashed border-gray-200 mt-4">
                    <div class="flex justify-between items-center bg-brand-50 p-3 rounded-lg border border-brand-200">
                        <span class="text-xs font-bold text-brand-700 uppercase">Saldo Final Pagado:</span>
                        <span
                            class="text-2xl font-black text-brand-800"><?php echo formatCurrency($s['net_to_pay']); ?></span>
                    </div>
                </div>

                <?php if ($s['partial_payment'] > 0): ?>
                    <div class="mt-4 border-t border-gray-100 pt-4 flex flex-col space-y-1">
                        <div
                            class="flex justify-between text-xs text-brand-600 font-black italic bg-brand-50 px-2 py-1 rounded">
                            <span>(-) ABONO PROPIETARIO RECIBIDO:</span>
                            <span><?php echo formatCurrency($s['partial_payment']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-gray-50">
                    <span class="font-bold text-gray-500 uppercase">Comisiones Conductor:</span>
                    <span class="font-black text-gray-900"><?php echo formatCurrency($s['total_commissions']); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-50">
                    <span class="font-bold text-gray-500 uppercase">Salario Básico (Periodo):</span>
                    <span class="font-black text-gray-900"><?php echo formatCurrency($s['salary_basic']); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-50">
                    <span class="font-bold text-gray-500 uppercase">Auxilio Transporte:</span>
                    <span
                        class="font-black text-gray-900"><?php echo formatCurrency($s['transport_assistance']); ?></span>
                </div>
                <?php
                $grossTotal = $s['salary_basic'] + $s['transport_assistance'] + $s['total_commissions'];
                ?>
                <div class="flex justify-between py-1 pt-4 text-brand-700 font-black text-sm uppercase">
                    <span>Devengado Subtotal:</span>
                    <span class="text-base"><?php echo formatCurrency($grossTotal); ?></span>
                </div>

                <div class="pt-4 mt-2 border-t-2 border-brand-900 space-y-1">
                    <h4 class="text-[10px] font-black text-brand-900 uppercase mb-2">Resumen Anticipos y Gastos</h4>
                    <div class="flex justify-between text-[10px]">
                        <span class="text-gray-500 uppercase">Total Anticipos Manifiesto:</span>
                        <span
                            class="font-bold text-gray-900"><?php echo formatCurrency($s['total_advances_manifest']); ?></span>
                    </div>
                    <div class="flex justify-between text-[10px]">
                        <span class="text-gray-500 uppercase">Total Anticipos Propietario:</span>
                        <span
                            class="font-bold text-gray-900"><?php echo formatCurrency($s['total_advances_owner']); ?></span>
                    </div>
                    <div class="flex justify-between text-[10px] font-bold text-gray-900 pt-1 border-t border-gray-100">
                        <span class="uppercase">(=) Total Anticipos Entregados:</span>
                        <span><?php echo formatCurrency($s['total_advances']); ?></span>
                    </div>
                    <div class="flex justify-between text-[10px] text-red-600 font-bold border-b border-gray-100 pb-1">
                        <span class="uppercase">(-) Gastos Reportados (Soportes):</span>
                        <span><?php echo formatCurrency($s['total_expenses']); ?></span>
                    </div>
                    <div class="flex justify-between font-black text-red-700 uppercase bg-red-50 px-2 py-1 rounded">
                        <span>SALDO GESTIÓN (A Descontar):</span>
                        <span><?php echo formatCurrency($s['balance_to_discount']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Signature -->
        <div class="p-8 pt-0">
            <?php if (!empty($s['notes'])): ?>
                <div class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-xs italic text-gray-700">
                    <span class="font-black uppercase not-italic block mb-1 text-[10px]">Notas / Observaciones:</span>
                    <?php echo nl2br(htmlspecialchars($s['notes'])); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-8">
                <div
                    class="mt-20 border-t border-gray-400 pt-2 text-center text-[10px] text-gray-500 uppercase font-black">
                    Firma del Conductor
                    <div class="mt-1 font-normal lowercase italic text-[8px]">
                        <?php echo 'C.C: ' . $s['document_number']; ?>
                    </div>
                </div>
                <div class="mt-20 text-right">
                    <p class="text-[9px] text-gray-400">Generado digitalmente por CELR APP</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #paymentStub,
        #paymentStub * {
            visibility: visible;
        }

        #paymentStub {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>