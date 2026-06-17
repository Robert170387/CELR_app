<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

$id = $_GET['id'] ?? 0;
// Fetch Full Trip Data with Hierarchical Location Information
$stmt = $pdo->prepare("
    SELECT t.*, v.placa, CONCAT(d.firstname, ' ', IFNULL(d.lastname, '')) as driver_name, m.name as material_name, mc.name as manifest_company_name,
           u.full_name as creator_name, u.username as creator_username,
           CASE 
               WHEN c.person_type = 'Jurídica' THEN c.business_name
               ELSE CONCAT(c.firstname, ' ', c.lastname1)
           END as client_name,
           os.name as origin_state_name,
           oc.name as origin_city_name,
           ds.name as destination_state_name,
           dc.name as destination_city_name
    FROM trips t
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    LEFT JOIN personnel d ON t.driver_id = d.id
    LEFT JOIN clients c ON t.client_id = c.id
    LEFT JOIN materials m ON t.material_id = m.id
    LEFT JOIN manifest_companies mc ON t.manifest_company_id = mc.id
    LEFT JOIN users u ON t.created_by = u.id
    LEFT JOIN loc_states os ON t.origin_state_id = os.id
    LEFT JOIN loc_cities oc ON t.origin_city_id = oc.id
    LEFT JOIN loc_states ds ON t.destination_state_id = ds.id
    LEFT JOIN loc_cities dc ON t.destination_city_id = dc.id
    WHERE t.id = ?
");

$stmt->execute([$id]);
$trip = $stmt->fetch();

if (!$trip)
    die("Viaje no encontrado");

// Navigation: Find Previous and Next Trip IDs
$stmtPrev = $pdo->prepare("SELECT MAX(id) FROM trips WHERE id < ?");
$stmtPrev->execute([$id]);
$prevId = $stmtPrev->fetchColumn();

$stmtNext = $pdo->prepare("SELECT MIN(id) FROM trips WHERE id > ?");
$stmtNext->execute([$id]);
$nextId = $stmtNext->fetchColumn();

// Fetch Expenses
$stmtExp = $pdo->prepare("SELECT * FROM expenses WHERE trip_id = ? ORDER BY paid_by, date DESC");
$stmtExp->execute([$id]);
$expenses = $stmtExp->fetchAll();

// Separate expenses by payer
$conductor_expenses = array_filter($expenses, fn($e) => ($e['paid_by'] ?? 'Conductor') === 'Conductor');
$owner_expenses = array_filter($expenses, fn($e) => ($e['paid_by'] ?? 'Conductor') === 'Propietario');

$total_conductor_expenses = array_sum(array_column($conductor_expenses, 'amount'));
$total_owner_expenses = array_sum(array_column($owner_expenses, 'amount'));
$total_expenses = $total_conductor_expenses + $total_owner_expenses;

// Fetch Payments (Cartera)
$stmtPay = $pdo->prepare("SELECT * FROM trip_payments WHERE trip_id = ? ORDER BY payment_date DESC");
$stmtPay->execute([$id]);
$payments = $stmtPay->fetchAll();

$total_payments_registered = array_sum(array_column($payments, 'amount'));
$saldo_pendiente_pago = $trip['final_pay_expected'] - $total_payments_registered;

// --- CALCULATED METRICS FOR DECISION MAKING ---
// 1. Días de Viaje
$days_of_trip = 0;
if ($trip['date_load'] && $trip['date_unload']) {
    $d1 = new DateTime($trip['date_load']);
    $d2 = new DateTime($trip['date_unload']);
    $diff = $d1->diff($d2);
    $days_of_trip = $diff->days;
    if ($days_of_trip == 0)
        $days_of_trip = 1; // Minimum 1 day if loaded/unloaded same day
}

// 2. Consumo KPL/G (Kilómetros por Galón)
$total_fuel_gallons = array_sum(array_column(array_filter($expenses, fn($e) => ($e['category'] === 'combustible' || $e['category'] === 'Combustible')), 'gallons'));
$kms_driven = $trip['kms_total'];
$kplg = ($total_fuel_gallons > 0) ? ($kms_driven / $total_fuel_gallons) : 0;

// 3. Punto de Equilibrio (Break-even point)
// In this context, it's the minimum freight needed to cover operational expenses + driver commission
$bep = $total_expenses + ($trip['commission_value'] ?? 0);

// 4. Margen de Contribución %
$net_freight = $trip['flete_neto'];
$utility = $net_freight - $total_expenses;
$contribution_margin_pct = ($net_freight > 0) ? ($utility / $net_freight) * 100 : 0;
?>

<?php renderWorkflowHeader(2, $id); ?>

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detalles del Viaje #<?php echo $trip['id']; ?></h1>
            <div class="mt-2 space-y-1">
                <div class="flex items-center text-sm text-gray-700">
                    <span class="font-bold text-brand-600">Origen:</span>
                    <span class="ml-2"><?php echo htmlspecialchars($trip['origin']); ?></span>
                    <?php if ($trip['origin_city_name'] || $trip['origin_state_name']): ?>
                        <span class="ml-2 text-xs text-gray-500">
                            (<?php echo htmlspecialchars($trip['origin_city_name'] ?: '-'); ?>, <?php echo htmlspecialchars($trip['origin_state_name'] ?: '-'); ?>)
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center text-sm text-gray-700">
                    <span class="font-bold text-brand-600">Destino:</span>
                    <span class="ml-2"><?php echo htmlspecialchars($trip['destination']); ?></span>
                    <?php if ($trip['destination_city_name'] || $trip['destination_state_name']): ?>
                        <span class="ml-2 text-xs text-gray-500">
                            (<?php echo htmlspecialchars($trip['destination_city_name'] ?: '-'); ?>, <?php echo htmlspecialchars($trip['destination_state_name'] ?: '-'); ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">
                Registrado por:
                <span class="font-medium text-gray-600">
                    <?php echo htmlspecialchars($trip['creator_name'] ?: $trip['creator_username'] ?: 'Sistema'); ?>
                </span>
                el <?php echo date('d/m/Y H:i', strtotime($trip['created_at'])); ?>
            </p>
        </div>
        <div>
        </div>
        <div class="flex items-center space-x-2">
            <!-- Navigation Buttons -->
            <span class="inline-flex rounded-md shadow-sm mr-4">
                <?php if ($prevId): ?>
                    <a href="trip_details.php?id=<?php echo $prevId; ?>"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-700 bg-white hover:bg-gray-50"
                        title="Viaje Anterior">
                        &larr; Ant
                    </a>
                <?php else: ?>
                    <span
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-400 bg-gray-100 cursor-not-allowed">
                        &larr; Ant
                    </span>
                <?php endif; ?>

                <?php if ($nextId): ?>
                    <a href="trip_details.php?id=<?php echo $nextId; ?>"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-white hover:bg-gray-50"
                        title="Siguiente Viaje">
                        Sig &rarr;
                    </a>
                <?php else: ?>
                    <span
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-400 bg-gray-100 cursor-not-allowed">
                        Sig &rarr;
                    </span>
                <?php endif; ?>
            </span>

            <span class="inline-flex rounded-md shadow-sm">
                <span class="inline-flex rounded-md shadow-sm">
                    <a href="trip_create.php?edit=<?php echo $trip['id']; ?>"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 mr-2">
                        Editar
                    </a>
                    <a href="trip_delete.php?id=<?php echo $trip['id']; ?>"
                        onclick="return confirm('¿Seguro deseas eliminar este viaje? Esta acción no se puede deshacer.');"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 mr-2">
                        Eliminar
                    </a>
                    <a href="trips.php"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Volver
                    </a>
                </span>

        </div>
    </div>

    <!-- Metric Cards for Decision Making -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Días de Viaje -->
        <div class="bg-white overflow-hidden shadow rounded-2xl border border-slate-100 p-5">
            <dt class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Días de Viaje</dt>
            <dd class="flex items-baseline justify-between">
                <div class="text-2xl font-black text-slate-900">
                    <?php echo $days_of_trip; ?> <span class="text-sm font-medium text-slate-400">días</span>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
            </dd>
        </div>

        <!-- Consumo KPL/G -->
        <div class="bg-white overflow-hidden shadow rounded-2xl border border-slate-100 p-5">
            <dt class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Consumo KPL/G</dt>
            <dd class="flex items-baseline justify-between">
                <div class="text-2xl font-black text-slate-900">
                    <?php echo number_format($kplg, 2); ?> <span
                        class="text-sm font-medium text-slate-400">km/gal</span>
                </div>
                <div class="p-2 bg-emerald-50 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </dd>
            <p class="text-[10px] text-slate-400 mt-1 italic font-medium">Total:
                <?php echo number_format($total_fuel_gallons, 1); ?> galones
            </p>
        </div>

        <!-- Punto de Equilibrio -->
        <div class="bg-white overflow-hidden shadow rounded-2xl border border-slate-100 p-5">
            <dt class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Punto de Equilibrio</dt>
            <dd class="flex items-baseline justify-between">
                <div class="text-xl font-black text-amber-600">
                    <?php echo formatCurrency($bep); ?>
                </div>
                <div class="p-2 bg-amber-50 rounded-lg">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
            </dd>
            <p class="text-[10px] text-slate-400 mt-1 italic font-medium">Gastos + Comisión</p>
        </div>

        <!-- Margen de Contribución % -->
        <div class="bg-white overflow-hidden shadow rounded-2xl border border-slate-100 p-5">
            <dt class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Margen Contribución</dt>
            <dd class="flex items-baseline justify-between">
                <div
                    class="text-2xl font-black <?php echo $contribution_margin_pct > 0 ? 'text-emerald-700' : 'text-red-700'; ?>">
                    <?php echo number_format($contribution_margin_pct, 1); ?>%
                </div>
                <div class="p-2 <?php echo $contribution_margin_pct > 0 ? 'bg-emerald-50' : 'bg-red-50'; ?> rounded-lg">
                    <svg class="w-5 h-5 <?php echo $contribution_margin_pct > 0 ? 'text-emerald-500' : 'text-red-500'; ?>"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
            </dd>
            <p class="text-[10px] text-slate-400 mt-1 italic font-medium">Utilidad:
                <?php echo formatCurrency($utility); ?>
            </p>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- Column 1: Operational Info -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Información Operativa</h3>
            </div>
            <div class="px-4 py-5 sm:p-6 space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Estado</span>
                    <span class="text-sm font-bold text-gray-900 uppercase"><?php echo $trip['trip_type']; ?></span>
                </div>

                <div class="border-t border-gray-100 my-2"></div>

                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Fecha Cargue</span>
                    <span class="text-sm text-gray-900"><?php echo $trip['date_load']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Fecha Descargue (Est)</span>
                    <span class="text-sm text-gray-900"><?php echo $trip['date_unload'] ?: '-'; ?></span>
                </div>

                <div class="border-t border-gray-100 my-2"></div>

                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Vehículo</span>
                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($trip['placa']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Conductor</span>
                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($trip['driver_name']); ?></span>
                </div>
                <!-- Client Info -->
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Cliente</span>
                    <span class="text-sm font-bold text-gray-900">
                        <?php echo htmlspecialchars($trip['client_name'] ?? 'No asignado'); ?>
                    </span>
                </div>

                <div class="border-t border-gray-100 my-2"></div>

                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Km Inicial</span>
                    <span class="text-sm text-gray-900"><?php echo number_format($trip['kms_start'], 1); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Km Final</span>
                    <span
                        class="text-sm text-gray-900"><?php echo $trip['kms_end'] ? number_format($trip['kms_end'], 1) : '-'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Km Total</span>
                    <span class="text-sm font-bold text-gray-900"><?php echo number_format($trip['kms_total'], 1); ?>
                        km</span>
                </div>

                <div class="border-t border-gray-100 my-2"></div>


                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Número Manifiesto</span>
                    <span
                        class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($trip['manifest_number']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Flete Manifiesto</span>
                    <span class="text-sm text-gray-900"><?php echo formatCurrency($trip['flete_bruto']); ?></span>
                </div>

                <div class="border-t border-gray-100 my-2"></div>

                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Carga (Material)</span>
                    <span
                        class="text-sm text-gray-900"><?php echo htmlspecialchars($trip['material_name'] ?? '-'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Peso Declarado</span>
                    <span class="text-sm text-gray-900"><?php echo $trip['weight_declared']; ?> Ton</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Peso Origen</span>
                    <span class="text-sm text-gray-900"><?php echo $trip['weight_origin']; ?> Ton</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Peso Destino</span>
                    <span class="text-sm text-gray-900"><?php echo $trip['weight_dest']; ?> Ton</span>
                </div>

            </div>

        </div>

        <!-- Column 2: Financial Summary -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Resumen Financiero</h3>
            </div>
            <div class="px-4 py-5 sm:p-6 space-y-3">
                <?php
                $flete_liquidado = $trip['flete_bruto'];
                $is_adjusted = false;
                if ($trip['weight_declared'] > 0 && $trip['weight_dest'] > 0 && $trip['weight_declared'] != $trip['weight_dest']) {
                    $flete_liquidado = ($trip['flete_bruto'] / $trip['weight_declared']) * $trip['weight_dest'];
                    $is_adjusted = true;
                }
                ?>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Flete Manifiesto</span>
                    <span
                        class="text-sm font-bold text-gray-900"><?php echo formatCurrency($trip['flete_bruto']); ?></span>
                </div>

                <div class="flex justify-between items-center bg-blue-50 p-2 rounded border border-blue-100">
                    <div>
                        <span class="text-sm font-bold text-blue-900">Flete Liquidado</span>
                        <p class="text-[10px] text-blue-700 leading-none">Ajustado según Peso Destino vs Declarado</p>
                    </div>
                    <span class="text-sm font-bold text-blue-900">
                        <?php echo formatCurrency($flete_liquidado); ?>
                    </span>
                </div>

                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-2 mb-1">Deducibles</h4>
                <div class="pl-2 space-y-1">
                    <div class="flex justify-between items-center text-gray-600 text-xs">
                        <span>Rete Fuente (<?php echo $trip['percent_rete_fuente']; ?>%)</span>
                        <span><?php echo formatCurrency($trip['value_rete_fuente']); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-600 text-xs">
                        <span>Rete ICA (<?php echo $trip['percent_rete_ica']; ?>%)</span>
                        <span><?php echo formatCurrency($trip['value_rete_ica']); ?></span>
                    </div>
                    <?php if ($trip['value_deductible_3'] > 0): ?>
                        <div class="flex justify-between items-center text-gray-600 text-xs">
                            <span>Deducible 3 (Otros)</span>
                            <span><?php echo formatCurrency($trip['value_deductible_3']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($trip['value_deductible_4'] > 0): ?>
                        <div class="flex justify-between items-center text-gray-600 text-xs">
                            <span>Deducible 4</span>
                            <span><?php echo formatCurrency($trip['value_deductible_4']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($trip['value_deductible_5'] > 0): ?>
                        <div class="flex justify-between items-center text-gray-600 text-xs">
                            <span>Deducible 5</span>
                            <span><?php echo formatCurrency($trip['value_deductible_5']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($trip['value_deductible_6'] > 0): ?>
                        <div class="flex justify-between items-center text-gray-600 text-xs">
                            <span>Deducible 6</span>
                            <span><?php echo formatCurrency($trip['value_deductible_6']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div
                        class="flex justify-between items-center text-red-600 font-bold border-t border-gray-100 pt-1 mt-1">
                        <span class="text-xs">(-) Deducibles Totales</span>
                        <span class="text-xs"><?php echo formatCurrency($trip['total_deductibles']); ?></span>
                    </div>
                </div>

                <div class="flex justify-between items-center bg-green-50 p-2 rounded mt-2">
                    <span class="text-sm font-bold text-green-900">Flete Neto</span>
                    <span
                        class="text-sm font-bold text-green-900"><?php echo formatCurrency($trip['flete_neto']); ?></span>
                </div>

                <div class="pt-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Comisión Conductor</h4>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Tipo: <?php echo ucfirst($trip['trip_type']); ?>
                            (<?php echo $trip['commission_percent']; ?>%)</span>
                        <span
                            class="text-sm font-bold text-orange-600"><?php echo formatCurrency($trip['commission_value']); ?></span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php if ($trip['trip_type'] == 'urbano'): ?>
                            * Calculado sobre (Flete Neto - Gastos Operativos)
                        <?php else: ?>
                            * Calculado sobre Flete Neto
                        <?php endif; ?>
                    </p>
                </div>

                <div class="border-t border-gray-200 my-2"></div>

                <div class="space-y-1">
                    <div class="flex justify-between items-center text-gray-500 text-xs">
                        <span>Anticipo Manifiesto</span>
                        <span><?php echo formatCurrency($trip['advance_manifest']); ?></span>
                    </div>
                    <?php if ($trip['advance_owner'] > 0): ?>
                        <div class="flex justify-between items-center text-gray-500 text-xs">
                            <span>Anticipo Propietario</span>
                            <span><?php echo formatCurrency($trip['advance_owner']); ?></span>
                        </div>
                        <?php if ($trip['advance_owner_responsible']): ?>
                            <div class="text-right text-xs text-gray-400 italic">Resp:
                                <?php echo htmlspecialchars($trip['advance_owner_responsible']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="flex justify-between items-center mt-2">
                    <span class="text-sm text-gray-900 font-bold">Pago Final Esperado</span>
                    <span
                        class="text-lg font-bold text-brand-600"><?php echo formatCurrency($trip['final_pay_expected']); ?></span>
                </div>
                <p class="text-xs text-right text-gray-500 mb-2">(Flete Liq. - Deducibles Totales - Anticipo)</p>

                <div class="flex justify-between items-center bg-gray-100 p-2 rounded border border-gray-200">
                    <span class="text-sm text-gray-900 font-bold">Pago Final Recibido</span>
                    <?php if ($trip['final_pay_received'] > 0): ?>
                        <span
                            class="text-lg font-bold text-green-700"><?php echo formatCurrency($trip['final_pay_received']); ?></span>
                    <?php else: ?>
                        <span class="text-sm text-gray-400 italic">Pendiente</span>
                    <?php endif; ?>
                </div>

                <!-- Settlement (Viáticos) Logic -->
                <div class="mt-6 border-t-2 border-red-100 pt-4">
                    <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider mb-2">Liquidación de Viáticos
                    </h4>
                    <div class="bg-red-50 p-3 rounded-lg border border-red-200">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-gray-500 uppercase">Monto Entregado (Viáticos):</span>
                            <?php
                            $montoEntregado = $trip['advance_owner'];
                            if ($trip['advance_manifest_to_driver'] ?? 0) {
                                $montoEntregado += $trip['advance_manifest'];
                            }
                            ?>
                            <span
                                class="text-sm font-medium text-gray-900"><?php echo formatCurrency($montoEntregado); ?></span>
                        </div>
                        <?php if ($trip['advance_manifest_to_driver'] ?? 0): ?>
                            <div class="text-[10px] text-gray-400 text-right mb-1">
                                (Incluye Anticipo Manifiesto: <?php echo formatCurrency($trip['advance_manifest']); ?>)
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-gray-500 uppercase">Gastos Conductor:</span>
                            <span class="text-sm font-medium text-red-600">-
                                <?php echo formatCurrency($total_conductor_expenses); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-red-200 pt-1 mt-1">
                            <span class="text-xs font-bold text-red-800 uppercase">Saldo (Este Viaje):</span>
                            <?php $balance = calculateTripBalance($trip['id']); ?>
                            <span
                                class="text-md font-bold <?php echo $balance < 0 ? 'text-blue-600' : 'text-red-600'; ?>">
                                <?php echo formatCurrency($balance); ?>
                            </span>
                        </div>

                        <?php
                        $totalPending = calculateDriverTotalPendingBalance($trip['driver_id']);
                        if ($totalPending != $balance): // Only show if there's a difference
                            ?>
                            <div class="flex justify-between items-center mt-1 pt-1 border-t border-red-100 border-dashed">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">Saldo Total Acumulado:</span>
                                <span class="text-sm font-black text-red-700">
                                    <?php echo formatCurrency($totalPending); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4 border-t border-red-200 pt-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-gray-600">Estado Actual:</span>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo ($trip['settlement_status'] ?? 'Pending') == 'Settled' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ($trip['settlement_status'] ?? 'Pending') == 'Settled' ? 'LIQUIDADO' : 'PENDIENTE'; ?>
                                </span>
                            </div>

                            <?php if (($trip['settlement_status'] ?? 'Pending') != 'Settled'): ?>
                                <!-- Form to Settle -->
                                <form action="save_settlement_status.php" method="POST" class="mt-3">
                                    <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                    <input type="hidden" name="action" value="settle">

                                    <div class="mb-2">
                                        <label for="notes"
                                            class="block text-[10px] font-medium text-gray-500 uppercase tracking-wider mb-1">Notas
                                            de Liquidación:</label>
                                        <textarea name="notes" id="notes" rows="2"
                                            class="shadow-sm focus:ring-brand-500 focus:border-brand-500 block w-full sm:text-xs border-gray-300 rounded-md"
                                            placeholder="Observaciones sobre devoluciones o descuentos..."><?php echo htmlspecialchars($trip['settlement_notes'] ?? ''); ?></textarea>
                                    </div>

                                    <button type="submit"
                                        onclick="return confirm('¿Confirma que los valores son correctos y desea cerrar la liquidación de viáticos?')"
                                        class="w-full flex justify-center items-center px-3 py-2 border border-transparent text-xs font-medium rounded leading-4 text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Cerrar y Liquidar Viáticos
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Display Notes and Reopen Button -->
                                <?php if ($trip['settlement_notes']): ?>
                                    <div class="bg-white p-2 rounded border border-gray-200 mb-3">
                                        <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Observaciones:</p>
                                        <p class="text-xs text-gray-700 italic">
                                            "<?php echo htmlspecialchars($trip['settlement_notes']); ?>"</p>
                                    </div>
                                <?php endif; ?>

                                <form action="save_settlement_status.php" method="POST">
                                    <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                    <input type="hidden" name="action" value="reopen">
                                    <button type="submit"
                                        onclick="return confirm('¿Desea reabrir la liquidación? Esto cambiará el estado a PENDIENTE.')"
                                        class="w-full flex justify-center items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                        <svg class="mr-1.5 h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Reabrir Liquidación
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 3: Expenses Linked -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Gastos Asociados</h3>
                <a href="expense_form.php?trip_id=<?php echo $trip['id']; ?>"
                    class="text-xs text-brand-600 hover:text-brand-900 font-bold">+ Agregar</a>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <?php if (empty($expenses)): ?>
                    <p class="text-sm text-gray-500 italic">No hay gastos registrados para este viaje.</p>
                <?php else: ?>
                    <!-- Conductor Expenses -->
                    <?php if (!empty($conductor_expenses)): ?>
                        <div class="mb-4">
                            <h4 class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-2 flex items-center">
                                <span class="inline-block w-2 h-2 bg-blue-500 rounded-full mr-1"></span>
                                Gastos del Conductor (Afectan liquidación)
                            </h4>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($conductor_expenses as $e): ?>
                                    <li class="py-2 flex justify-between items-center">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-700">
                                                <?php
                                                $catName = ucfirst(str_replace('_', ' ', $e['category']));
                                                echo htmlspecialchars($catName);
                                                ?>
                                            </span>
                                            <span class="text-xs text-gray-400"><?php echo $e['date']; ?></span>
                                            <?php if ($e['receipt_photo']): ?>
                                                <a href="<?php echo htmlspecialchars($e['receipt_photo']); ?>" target="_blank"
                                                    class="text-[10px] text-brand-600 hover:text-brand-900 flex items-center mt-0.5">
                                                    📎 Ver Comprobante
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <span
                                            class="text-sm font-medium text-red-600"><?php echo formatCurrency($e['amount']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <li class="py-2 flex justify-between border-t border-blue-200 mt-2 font-bold text-blue-700">
                                    <span>Subtotal Conductor</span>
                                    <span><?php echo formatCurrency($total_conductor_expenses); ?></span>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Owner Expenses -->
                    <?php if (!empty($owner_expenses)): ?>
                        <div class="mb-4">
                            <h4 class="text-xs font-bold text-purple-700 uppercase tracking-wider mb-2 flex items-center">
                                <span class="inline-block w-2 h-2 bg-purple-500 rounded-full mr-1"></span>
                                Gastos del Propietario (No afectan liquidación)
                            </h4>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($owner_expenses as $e): ?>
                                    <li class="py-2 flex justify-between items-center">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-700">
                                                <?php
                                                $catName = ucfirst(str_replace('_', ' ', $e['category']));
                                                echo htmlspecialchars($catName);
                                                ?>
                                            </span>
                                            <span class="text-xs text-gray-400"><?php echo $e['date']; ?></span>
                                            <?php if ($e['receipt_photo']): ?>
                                                <a href="<?php echo htmlspecialchars($e['receipt_photo']); ?>" target="_blank"
                                                    class="text-[10px] text-brand-600 hover:text-brand-900 flex items-center mt-0.5">
                                                    📎 Ver Comprobante
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <span
                                            class="text-sm font-medium text-purple-600"><?php echo formatCurrency($e['amount']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <li class="py-2 flex justify-between border-t border-purple-200 mt-2 font-bold text-purple-700">
                                    <span>Subtotal Propietario</span>
                                    <span><?php echo formatCurrency($total_owner_expenses); ?></span>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Total -->
                    <div class="py-2 flex justify-between border-t-2 border-gray-300 mt-2 font-bold text-gray-900">
                        <span>Total Todos los Gastos</span>
                        <span><?php echo formatCurrency($total_expenses); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Column 4 (Row 2): ePOD / Documentation -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Prueba de Entrega (ePOD)</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Image Display -->
                <?php if (!empty($trip['delivery_proof_url'])): ?>
                    <div class="mb-4">
                        <?php
                        $ext = pathinfo($trip['delivery_proof_url'], PATHINFO_EXTENSION);
                        if (strtolower($ext) == 'pdf'):
                            ?>
                            <div class="flex items-center justify-center h-32 bg-gray-100 rounded-lg border border-gray-200">
                                <span class="text-gray-500 font-medium">Documento PDF</span>
                            </div>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($trip['delivery_proof_url']); ?>" alt="ePOD"
                                class="w-full h-auto rounded-lg shadow-sm border border-gray-200 object-cover max-h-64">
                        <?php endif; ?>

                        <a href="<?php echo htmlspecialchars($trip['delivery_proof_url']); ?>" target="_blank"
                            class="block text-center text-sm text-brand-600 mt-2 hover:underline font-bold">
                            👁️ Ver Documento Completo
                        </a>
                    </div>
                    <div class="border-t border-gray-100 my-4"></div>
                <?php else: ?>
                    <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">Pendiente cargar cumplido.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Upload Form -->
                <form action="trip_upload_pod.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 uppercase tracking-widest mb-2">Cargar/Actualizar
                            Imagen</label>
                        <div
                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                    viewBox="0 0 48 48" aria-hidden="true">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="pod_file"
                                        class="relative cursor-pointer bg-white rounded-md font-medium text-brand-600 hover:text-brand-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-brand-500">
                                        <span>Subir archivo</span>
                                        <input id="pod_file" name="pod_file" type="file" class="sr-only" required
                                            accept="image/*,application/pdf">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, PDF hasta 5MB</p>
                            </div>
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Guardar Documento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Payments Section (Cartera) -->
    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <div
            class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center">
                    <span class="mr-2">💰</span> Control de Cartera / Pagos Recibidos
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Gestiona los abonos recibidos por este viaje.
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase font-bold">Saldo Pendiente por Cobrar</p>
                    <p
                        class="text-2xl font-bold <?php echo $saldo_pendiente_pago > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo formatCurrency($saldo_pendiente_pago); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200">

                <!-- Left: Payment Form -->
                <div class="p-6 bg-gray-50 md:col-span-1">
                    <h4 class="text-sm font-bold text-gray-900 mb-4">Registrar Nuevo Abono</h4>
                    <form action="save_trip_payment.php" method="POST" class="space-y-4">
                        <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Fecha de Pago</label>
                            <input type="date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Monto Recibido</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                    class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Concepto</label>
                            <select name="payment_concept"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="Anticipo">Anticipo (Inicial)</option>
                                <option value="Saldo Final">Saldo Final</option>
                                <option value="Abono Parcial">Abono Parcial</option>
                                <option value="Pago Total">Pago Total (Único)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Método de Pago</label>
                            <select name="payment_method"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="Transferencia">Transferencia Bancaria</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Consignación">Consignación</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Referencia / Comprobante
                                (Opcional)</label>
                            <input type="text" name="reference" placeholder="# Comprobante..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Notas Adicionales</label>
                            <textarea name="notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"></textarea>
                        </div>

                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Guardar Pago
                        </button>
                    </form>
                </div>

                <!-- Right: Payment History List -->
                <div class="p-6 md:col-span-2 bg-white">
                    <h4 class="text-sm font-bold text-gray-900 mb-4">Historial de Pagos</h4>

                    <?php if (empty($payments)): ?>
                        <div
                            class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm">No se han registrado pagos para este viaje.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-6">
                                            Fecha</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Detalles</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Monto</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span
                                                class="sr-only">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td
                                                class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                <?php echo $pay['payment_date']; ?>
                                            </td>
                                            <td class="px-3 py-4 text-xs text-gray-500">
                                                <div class="flex items-center space-x-2">
                                                    <span
                                                        class="font-bold text-gray-800"><?php echo $pay['payment_method']; ?></span>
                                                    <?php
                                                    $conceptClass = 'bg-gray-100 text-gray-800';
                                                    if ($pay['payment_concept'] == 'Anticipo')
                                                        $conceptClass = 'bg-yellow-100 text-yellow-800';
                                                    if ($pay['payment_concept'] == 'Saldo Final')
                                                        $conceptClass = 'bg-green-100 text-green-800';
                                                    if ($pay['payment_concept'] == 'Pago Total')
                                                        $conceptClass = 'bg-green-100 text-green-800';
                                                    ?>
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $conceptClass; ?>">
                                                        <?php echo $pay['payment_concept']; ?>
                                                    </span>
                                                </div>
                                                <?php if ($pay['reference']): ?>
                                                    <div class="mt-0.5">Ref: <span
                                                            class="font-mono"><?php echo htmlspecialchars($pay['reference']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($pay['notes']): ?>
                                                    <div class="italic text-gray-400 mt-1">
                                                        "<?php echo htmlspecialchars($pay['notes']); ?>"</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-bold text-green-700">
                                                <?php echo formatCurrency($pay['amount']); ?>
                                            </td>
                                            <td
                                                class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <a href="delete_trip_payment.php?id=<?php echo $pay['id']; ?>&trip_id=<?php echo $trip['id']; ?>"
                                                    onclick="return confirm('¿Seguro que deseas eliminar este pago?')"
                                                    class="text-red-600 hover:text-red-900">Eliminar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Total Row -->
                                    <tr class="bg-gray-50">
                                        <td colspan="2" class="py-3 pl-4 pr-3 text-right text-sm font-bold text-gray-900">
                                            Total Recibido</td>
                                        <td
                                            class="px-3 py-3 text-right text-sm font-bold text-green-800 border-t border-gray-300">
                                            <?php echo formatCurrency($total_payments_registered); ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Next Step Action -->
    <div class="mt-8 flex justify-center pb-12">
        <a href="expense_form.php?category=combustible&trip_id=<?php echo $id; ?>"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-brand-600 hover:bg-brand-700 transition transform hover:scale-105">
            Continuar a Vales de Combustible &rarr;
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>