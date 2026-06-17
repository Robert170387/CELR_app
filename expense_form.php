<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$expense = null;
$trip_id_preselected = $_GET['trip_id'] ?? null;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch lists for the form
$vehicles = $pdo->query("SELECT * FROM vehicles WHERE active=1 ORDER BY placa ASC")->fetchAll();
$categories_variable = $pdo->query("SELECT * FROM expense_categories WHERE type='variable' ORDER BY name ASC")->fetchAll();
$categories_fixed = $pdo->query("SELECT * FROM expense_categories WHERE type='fixed' ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY created_at DESC")->fetchAll();
$all_personnel = $pdo->query("SELECT id, firstname, lastname FROM personnel WHERE active=1 ORDER BY firstname ASC")->fetchAll();

// Location Pre-population (Structural Repair)
$all_states = $pdo->query("SELECT s.id, s.name FROM loc_states s JOIN loc_countries c ON s.country_id = c.id WHERE c.name = 'Colombia' ORDER BY s.name ASC")->fetchAll();
$all_cities = [];

$current_state = $expense['department'] ?? '';
$current_city = $expense['city'] ?? '';
$current_driver_id = $expense['driver_id'] ?? '';

// If driver_id is empty but we have a trip, try to recover from trip
if (empty($current_driver_id) && !empty($expense['trip_id'])) {
    $stmt = $pdo->prepare("SELECT driver_id FROM trips WHERE id = ?");
    $stmt->execute([$expense['trip_id']]);
    $current_driver_id = $stmt->fetchColumn() ?: '';
}

// If state is empty but we have an ID, try to recover the name
if (empty($current_state) && !empty($expense['eds_state_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM loc_states WHERE id = ?");
    $stmt->execute([$expense['eds_state_id']]);
    $current_state = $stmt->fetchColumn() ?: '';
}

if (!empty($current_state)) {
    $stmt = $pdo->prepare("SELECT ci.id, ci.name FROM loc_cities ci JOIN loc_states s ON ci.state_id = s.id WHERE s.name = ? ORDER BY ci.name ASC");
    $stmt->execute([$current_state]);
    $all_cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If city is empty but we have an ID, try to recover the name
if (empty($current_city) && !empty($expense['eds_city_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM loc_cities WHERE id = ?");
    $stmt->execute([$expense['eds_city_id']]);
    $current_city = $stmt->fetchColumn() ?: '';
}

// Trip context
$extra_trip_where = "";
if ($expense && !empty($expense['trip_id'])) {
    $extra_trip_where = " OR id = " . intval($expense['trip_id']);
}
$trips = $pdo->query("SELECT id, origin, destination, date_load, vehicle_id FROM trips WHERE 1=1 $extra_trip_where ORDER BY id DESC LIMIT 100")->fetchAll();

// Prepare JSON for Alpine
$trips_json = json_encode($trips);
$states_json = json_encode($all_states);
$cities_json = json_encode($all_cities);
$operational_slugs = array_values(array_map(fn($c) => $c['slug'], array_filter($categories_variable, fn($c) => $c['slug'] !== 'combustible')));
$operational_slugs_json = json_encode($operational_slugs);
?>
<?php ?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="expenseForm()">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900"><?php echo $expense ? 'Editar Gasto' : 'Registrar Nuevo Gasto'; ?>
        </h1>
        <p class="mt-1 text-sm text-slate-500">Complete los detalles del gasto operativo o administrativo.</p>
    </div>

    <!-- Workflow Header if applicable -->
    <?php
    if ($trip_id_preselected) {
        $step = (($_GET['category'] ?? '') === 'combustible' || ($expense['category'] ?? '') === 'combustible') ? 3 : 4;
        renderWorkflowHeader($step, $trip_id_preselected);
    }
    ?>



    <form action="save_expense.php" method="POST" enctype="multipart/form-data" class="space-y-6 persist-form"
        id="expenseForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <?php if ($expense): ?><input type="hidden" name="id" value="<?php echo $expense['id']; ?>"><?php endif; ?>

        <!-- SECCIÓN 1: Información General -->
        <div class="form-section">
            <div class="form-section-header">
                <h3 class="form-section-title">📋 Información General</h3>
                <p class="form-section-subtitle">Datos básicos y fecha del registro</p>
            </div>
            <div class="form-section-body">
                <div class="form-grid form-grid-2">
                    <!-- Fecha -->
                    <div>
                        <label class="form-label form-label-required">Fecha del Gasto</label>
                        <input type="date" name="date" x-model="formData.date" 
                            value="<?php echo $expense['date'] ?? date('Y-m-d'); ?>" class="form-input">
                    </div>

                    <!-- Vehículo -->
                    <div>
                        <label class="form-label form-label-required">Vehículo Asociado</label>
                        <select name="vehicle_id" x-model="formData.vehicle_id" @change="onVehicleChange()" required
                            class="form-select">
                            <option value="">-- Seleccione Vehículo --</option>
                            <?php foreach ($vehicles as $v): ?>
                                 <option value="<?php echo $v['id']; ?>" <?php echo (($expense['vehicle_id'] ?? '') == $v['id']) ? 'selected' : ''; ?>>
                                     <?php echo htmlspecialchars($v['placa']); ?> -
                                     <?php echo htmlspecialchars($v['brand']); ?>
                                 </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Conductor -->
                    <div>
                        <label class="form-label">Conductor Asociado</label>
                        <select name="driver_id" x-model="formData.driver_id" class="form-select">
                            <option value="">-- Seleccione Conductor --</option>
                            <?php foreach ($all_personnel as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($current_driver_id == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['firstname'] . ' ' . $p['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Categoría -->
                    <div class="col-span-1 sm:col-span-2">
                        <label class="form-label form-label-required">Categoría del Gasto</label>
                        <select name="category" x-model="formData.category" @change="onCategoryChange()"
                            class="form-select">
                            <option value="">-- Seleccione Categoría --</option>
                            <optgroup label="Operativos (Viaje)">
                                <?php foreach ($categories_variable as $cat): ?>
                                    <option value="<?php echo $cat['slug']; ?>" <?php echo (($expense['category'] ?? '') == $cat['slug']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Mantenimiento/Fijos">
                                <?php foreach ($categories_fixed as $cat): ?>
                                    <option value="<?php echo $cat['slug']; ?>" <?php echo (($expense['category'] ?? '') == $cat['slug']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- Alerta de Viaje Activo -->
                <div x-show="activeTripAlert" x-transition
                    class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-3">
                    <span class="text-xl">🚌</span>
                    <div>
                        <h4 class="text-sm font-bold text-amber-900 uppercase">Vínculo Automático Detectado</h4>
                        <p class="text-sm text-amber-800" x-html="activeTripMessage"></p>
                    </div>
                </div>

                <!-- Selección Manual de Viaje (si no es auto-detectado o si se requiere cambiar) -->
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <label class="form-label">Vincular a Viaje (Opcional)</label>
                    <select name="trip_id" x-model="formData.trip_id" class="form-select">
                        <option value="">-- Ninguno (Gasto General / Administrativo) --</option>
                        <?php foreach ($trips as $t): ?>
                             <option value="<?php echo $t['id']; ?>" <?php echo (($expense['trip_id'] ?? $trip_id_preselected ?? '') == $t['id']) ? 'selected' : ''; ?>>
                                 #<?php echo $t['id']; ?> - <?php echo $t['origin']; ?> → <?php echo $t['destination']; ?>
                                 (<?php echo $t['date_load']; ?>)
                             </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-hint" x-show="isOperationalRequired">⚠️ Esta categoría requiere estar vinculada a un
                        viaje.</p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: Detalles Financieros -->
        <div class="form-section">
            <div class="form-section-header">
                <h3 class="form-section-title">💰 Detalles Financieros</h3>
                <p class="form-section-subtitle">Montos, proveedores y origen de fondos</p>
            </div>
            <div class="form-section-body">

                <!-- Origen de Fondos -->
                <div class="mb-6 bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <label class="form-label text-blue-900 mb-2">¿Quién paga este gasto?</label>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <label
                            class="flex items-center p-3 bg-white rounded-lg border border-blue-200 cursor-pointer hover:border-blue-400 transition-colors flex-1 shadow-sm">
                                 <input type="radio" name="paid_by" value="Conductor" x-model="formData.paid_by" <?php echo (($expense['paid_by'] ?? 'Conductor') == 'Conductor') ? 'checked' : ''; ?>
                                     class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Conductor</span>
                                <span class="block text-xs text-gray-500">Se descuenta de sus viáticos/anticipos</span>
                            </div>
                        </label>
                        <label
                            class="flex items-center p-3 bg-white rounded-lg border-blue-200 border cursor-pointer hover:border-blue-400 transition-colors flex-1 shadow-sm">
                                 <input type="radio" name="paid_by" value="Propietario" x-model="formData.paid_by" <?php echo (($expense['paid_by'] ?? '') == 'Propietario') ? 'checked' : ''; ?>
                                     class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Propietario / Caja</span>
                                <span class="block text-xs text-gray-500">Pago directo de la empresa</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-grid form-grid-2">
                    <!-- Proveedor -->
                    <div>
                        <label class="form-label">Proveedor</label>
                        <select name="supplier_id" x-model="formData.supplier_id"
                            @change="onSupplierChange()" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($expense && $expense['supplier_id'] == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($s['person_type'] == 'Jurídica') ? $s['business_name'] : $s['firstname'] . ' ' . $s['lastname1']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Estado de Factura -->
                    <div>
                        <label class="form-label">Estado de Factura</label>
                        <select name="invoice_status" x-model="formData.invoice_status" class="form-select">
                            <option value="Pendiente" <?php echo (($expense['invoice_status'] ?? 'Pendiente') == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Cancelada" <?php echo (($expense['invoice_status'] ?? '') == 'Cancelada') ? 'selected' : ''; ?>>Cancelada / Pagada</option>
                        </select>
                    </div>

                    <!-- Ubicación General — Data Island: PHP pre-renderiza valores, Alpine solo gestiona la lista -->
                    <div class="col-span-1 sm:col-span-2">
                        <label class="form-label">Ubicación Geográfica (Departamento / Ciudad)
                            <span x-show="loc.supplierSuggestion" class="ml-2 text-xs text-blue-600 font-normal"
                                style="display:none">
                                📍 Sugerido por proveedor — <button type="button" @click="applyLocSuggestion()"
                                    class="underline">Aplicar</button>
                            </span>
                        </label>

                        <!-- Valores siempre enviados al servidor (estáticos desde PHP, actualizados por Alpine) -->
                        <input type="hidden" name="department" id="fld_department"
                            :value="loc.selectedState">
                        <input type="hidden" name="city" id="fld_city"
                            :value="loc.selectedCity">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-1">
                            <select id="sel_department" x-model="loc.selectedState" @change="locOnStateChange(true)"
                                class="form-select" :disabled="loc.isLoadingStates">
                                <option value="">-- Departamento --</option>
                                <?php if ($current_state): ?>
                                    <option value="<?php echo $current_state; ?>" selected><?php echo $current_state; ?></option>
                                <?php endif; ?>
                                <template x-for="s in loc.states" :key="s.id || s.name">
                                    <option :value="s.name" x-text="s.name" x-show="s.name !== '<?php echo $current_state; ?>'"></option>
                                </template>
                            </select>
                            <select id="sel_city" x-model="loc.selectedCity" class="form-select"
                                :disabled="loc.isLoadingCities || !loc.selectedState">
                                <option value="">-- Ciudad/Municipio --</option>
                                <?php if ($current_city): ?>
                                    <option value="<?php echo $current_city; ?>" selected><?php echo $current_city; ?></option>
                                <?php endif; ?>
                                <template x-for="c in loc.cities" :key="c.id || c.name">
                                    <option :value="c.name" x-text="c.name" x-show="c.name !== '<?php echo $current_city; ?>'"></option>
                                </template>
                            </select>
                        </div>
                        <p class="form-hint text-slate-400 text-xs mt-1"
                            x-show="!loc.isLoadingStates && loc.states.length === 0" style="display:none">
                            ⚠️ No se pudieron cargar los departamentos. Los datos guardados se conservan igualmente.
                        </p>
                        <div class="flex gap-4">
                            <p class="form-hint text-amber-600" style="display: none;" x-show="loc.isLoadingStates">⏳ Cargando departamentos...</p>
                            <p class="form-hint text-amber-600" style="display: none;" x-show="loc.isLoadingCities">⏳ Cargando ciudades...</p>
                        </div>
                    </div>

                    <!-- Monto -->
                    <div>
                        <label class="form-label form-label-required">Monto Total</label>
                        <div class="input-group">
                            <span class="input-addon">$</span>
                            <input type="number" step="0.01" name="amount" x-model="formData.amount"
                                value="<?php echo $expense['amount'] ?? ''; ?>"
                                @input="recalcImpact()"
                                :readonly="isFuelCategory" :class="{'bg-gray-100 cursor-not-allowed': isFuelCategory}"
                                class="form-input text-lg font-bold" placeholder="0.00" required>
                        </div>
                        <p class="form-hint" x-show="isFuelCategory">* Calculado automáticamente (Galones × Precio)</p>
                    </div>
                </div>

                <!-- Panel Impacto Financiero (se muestra cuando hay viaje vinculado) -->
                <div x-show="impact.visible" x-transition
                    class="mt-4 p-4 rounded-xl border border-indigo-200 bg-indigo-50 grid grid-cols-3 gap-3 text-center"
                    style="display:none">
                    <div>
                        <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Flete Neto Viaje</p>
                        <p class="text-base font-black text-indigo-800 mt-1" x-text="'$ ' + impact.fleteNeto.toLocaleString('es-CO')"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider"
                            :class="impact.utilidadViaje >= 0 ? 'text-emerald-600' : 'text-red-500'">Utilidad c/Gasto</p>
                        <p class="text-base font-black mt-1"
                            :class="impact.utilidadViaje >= 0 ? 'text-emerald-700' : 'text-red-600'"
                            x-text="'$ ' + impact.utilidadViaje.toLocaleString('es-CO')"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider"
                            :class="impact.margen >= 0 ? 'text-slate-600' : 'text-red-500'">Margen %</p>
                        <p class="text-base font-black mt-1"
                            :class="impact.margen >= 0 ? 'text-slate-700' : 'text-red-600'"
                            x-text="impact.margen.toFixed(1) + '%'"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: Combustible (Condicional) -->
        <div class="form-section border-amber-200" x-show="isFuelCategory" x-transition>
            <div class="form-section-header bg-amber-50 border-amber-200">
                <h3 class="form-section-title text-amber-800">⛽ Detalles de Combustible</h3>
                <p class="text-xs text-amber-700 mt-1">Información específica de la estación de servicio y tanqueo</p>
            </div>
            <div class="form-section-body">
                <div class="form-grid form-grid-2">
                    <!-- Nombre EDS -->
                    <div>
                        <label class="form-label">Nombre Estación (EDS)</label>
                        <input type="text" name="eds_name" x-model="formData.eds" 
                            value="<?php echo $expense['eds_name'] ?? ''; ?>" class="form-input"
                            placeholder="Ej: Texaco, Terpel...">
                    </div>

                    <!-- Galones -->
                    <div>
                        <label class="form-label">Volumen (Galones)</label>
                        <input type="number" step="0.001" id="gallons" name="gallons" x-model="formData.gls"
                            value="<?php echo $expense['gallons'] ?? ''; ?>"
                            @input="calculateFuelTotal()" class="form-input">
                    </div>
                    <!-- Precio -->
                    <div>
                        <label class="form-label">Precio x Galón</label>
                        <div class="input-group">
                            <span class="input-addon">$</span>
                            <input type="number" step="0.01" id="price_per_gallon" name="price_per_gallon"
                                value="<?php echo $expense['price_per_gallon'] ?? ''; ?>"
                                x-model="formData.ppg" @input="calculateFuelTotal()" class="form-input">
                        </div>
                    </div>
                    <!-- Método Pago -->
                    <div>
                        <label class="form-label">Método de Pago</label>
                        <select name="payment_method" x-model="formData.payment_method" class="form-select">
                            <option value="Efectivo" <?php echo (($expense['payment_method'] ?? 'Efectivo') == 'Efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                            <option value="Tarjeta" <?php echo (($expense['payment_method'] ?? '') == 'Tarjeta') ? 'selected' : ''; ?>>Tarjeta</option>
                            <option value="Transferencia" <?php echo (($expense['payment_method'] ?? '') == 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                            <option value="Credito" <?php echo (($expense['payment_method'] ?? '') == 'Credito') ? 'selected' : ''; ?>>Crédito</option>
                        </select>
                    </div>
                    <!-- Factura Combustible -->
                    <div>
                        <label class="form-label">No. Factura / Recibo</label>
                        <input type="text" name="invoice_number" x-model="formData.invoice_number"
                            value="<?php echo $expense['invoice_number'] ?? ''; ?>"
                            class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 4: Evidencia y Descripción -->
        <div class="form-section">
            <div class="form-section-header">
                <h3 class="form-section-title">📄 Evidencia</h3>
                <p class="form-section-subtitle">Soportes y detalles adicionales</p>
            </div>
            <div class="form-section-body">
                <div class="mt-4">
                    <label class="form-label">Descripción Adicional</label>
                    <textarea name="description" x-model="formData.description"
                        class="form-textarea" rows="3"
                        placeholder="Detalles del gasto..."><?php echo $expense['description'] ?? ''; ?></textarea>
                </div>

                <div class="mt-6 border-t border-dashed border-slate-200 pt-6">
                    <label class="form-label mb-2">Foto del Recibo / Factura</label>
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                        <?php if (!empty($expense['receipt_photo'])): ?>
                        <div class="mt-2 text-xs text-slate-500 flex items-center">
                            <svg class="h-4 w-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Comprobante cargado. <a href="<?php echo htmlspecialchars($expense['receipt_photo']); ?>"
                                target="_blank" class="text-brand-600 font-bold ml-1 hover:underline">Ver actual</a>
                            <input type="hidden" name="existing_photo" value="<?php echo htmlspecialchars($expense['receipt_photo']); ?>">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="receipt_photo" accept="image/*" class="block w-full text-sm text-slate-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-brand-50 file:text-brand-700
                            hover:file:bg-brand-100
                            transition-colors cursor-pointer">
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div
            class="sticky bottom-0 bg-white/90 backdrop-blur-md border-t border-slate-200 p-4 -mx-4 -mb-4 sm:mx-0 sm:mb-0 sm:rounded-b-lg flex flex-col-reverse sm:flex-row justify-end gap-3 z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <a href="expenses.php" class="btn-secondary w-full sm:w-auto">Cancelar</a>
            <button type="submit" class="btn-primary w-full sm:w-auto shadow-lg shadow-brand-500/30">Guardar
                Gasto</button>
        </div>

    </form>
</div>

<script>
    function expenseForm() {
        return {
            formData: {
                date: <?php echo json_encode($expense['date'] ?? date('Y-m-d')); ?>,
                vehicle_id: <?php echo json_encode($expense['vehicle_id'] ?? ''); ?>,
                category: <?php echo json_encode($expense['category'] ?? ''); ?>,
                trip_id: <?php echo json_encode($expense['trip_id'] ?? $trip_id_preselected ?? ''); ?>,
                paid_by: <?php echo json_encode($expense['paid_by'] ?? 'Conductor'); ?>,
                amount: <?php echo json_encode($expense['amount'] ?? ''); ?>,
                supplier_id: <?php echo json_encode($expense['supplier_id'] ?? ''); ?>,
                description: <?php echo json_encode($expense['description'] ?? ''); ?>,
                invoice_status: <?php echo json_encode($expense['invoice_status'] ?? 'Pendiente'); ?>,
                driver_id: <?php echo json_encode($current_driver_id); ?>,
                gls: <?php echo json_encode($expense['gallons'] ?? ''); ?>,
                ppg: <?php echo json_encode($expense['price_per_gallon'] ?? ''); ?>,
                eds: <?php echo json_encode($expense['eds_name'] ?? ''); ?>,
                payment_method: <?php echo json_encode($expense['payment_method'] ?? 'Efectivo'); ?>,
                invoice_number: <?php echo json_encode($expense['invoice_number'] ?? ''); ?>
            },
            fuelData: {}, // Moved to formData as requested
            isFuelCategory: false,
            isEditing: <?php echo isset($_GET['id']) ? 'true' : 'false'; ?>,
            activeTripAlert: false,
            activeTripMessage: '',
            isOperationalRequired: false,
            operationalSlugs: <?php echo $operational_slugs_json; ?>,
            trips: <?php echo $trips_json; ?>,

            loc: {
                countries: [],
                states: <?php echo $states_json; ?>,
                cities: <?php echo $cities_json; ?>,
                selectedCountry: 'Colombia',
                selectedState: <?php echo json_encode($current_state); ?>,
                selectedCity: <?php echo json_encode($current_city); ?>,
                isLoadingStates: false,
                isLoadingCities: false,
                supplierSuggestion: null
            },
            impact: { visible: false, fleteNeto: 0, currentExpensesTotal: 0, utilidadViaje: 0, margen: 0 },

            init() {
                this.initForm();
            },

            async initForm() {
                console.log("Expense Form Initializing...");
                this.onCategoryChange(); // Check initial category state
                if (this.formData.vehicle_id && !this.isEditing) {
                    this.checkActiveTrip();
                }
                // Always load states for Colombia so location selector is ready
                await this.locFetchStates();
                
                // If editing and a state is pre-selected, load its cities
                if (this.loc.selectedState) {
                    const savedCity = this.loc.selectedCity;
                    console.log("Pre-selected state detected:", this.loc.selectedState, "Restoring city:", savedCity);
                    
                    // Force city list loading
                    await this.locOnStateChange(true);
                    
                    // Small delay to allow Alpine to render the city options before selecting
                    setTimeout(() => {
                        this.loc.selectedCity = savedCity;
                        console.log("City restored to:", this.loc.selectedCity);
                    }, 250);
                }
            },

            onCategoryChange() {
                this.isFuelCategory = (this.formData.category === 'combustible');
                this.isOperationalRequired = this.operationalSlugs.includes(this.formData.category);

                if (this.isFuelCategory) {
                    this.calculateFuelTotal();
                }
            },

            async locFetchStates() {
                if (this.loc.states.length > 0) return; // Already loaded
                this.loc.isLoadingStates = true;
                try {
                    const res = await fetch(`api_locations.php?action=states&country=${encodeURIComponent(this.loc.selectedCountry)}`);
                    if (!res.ok) throw new Error('Network response was not ok');
                    const data = await res.json();
                    if (!data.error && data.data && data.data.states) {
                        this.loc.states = data.data.states;
                    }
                } finally {
                    this.loc.isLoadingStates = false;
                }
            },

            async locOnStateChange(force = false) {
                if (!this.loc.selectedState) {
                    this.loc.cities = [];
                    return;
                }

                this.loc.isLoadingCities = true;
                this.loc.cities = []; // Clear current cities before fetching new ones
                try {
                    const res = await fetch(`api_locations.php?action=cities&country=${encodeURIComponent(this.loc.selectedCountry)}&state=${encodeURIComponent(this.loc.selectedState)}`);
                    if (!res.ok) throw new Error('Network response was not ok');
                    const data = await res.json();
                    if (!data.error && data.data) {
                        this.loc.cities = data.data;
                    }
                } catch (e) {
                    console.error('Error loading cities:', e);
                } finally {
                    this.loc.isLoadingCities = false;
                }
            },

            calculateFuelTotal() {
                if (this.isFuelCategory) {
                    const g = parseFloat(this.formData.gls) || 0;
                    const p = parseFloat(this.formData.ppg) || 0;
                    if (g > 0 && p > 0) {
                        this.formData.amount = (g * p).toFixed(0);
                    }
                }
            },

            async checkActiveTrip() {
                if (!this.formData.vehicle_id || !this.formData.date) {
                    this.activeTripAlert = false;
                    return;
                }

                // Only check if no trip manually selected or if we want to confirm
                try {
                    const response = await fetch(`api.php?action=findActiveTrip&vehicle_id=${this.formData.vehicle_id}&date=${this.formData.date}`);
                    const result = await response.json();

                    if (!result.error && result.trip) {
                        this.activeTripAlert = true;
                        this.activeTripMessage = `Viaje Activo: <strong>#${result.trip.id} (${result.trip.origin} → ${result.trip.destination})</strong>. Se vinculará automáticamente.`;

                        // Auto-select if not set
                        if (!this.formData.trip_id) {
                            this.formData.trip_id = result.trip.id;
                        }
                    } else {
                        this.activeTripAlert = false;
                    }
                } catch (e) {
                    console.error("Error checking trip:", e);
                }
            },

            async applyLocSuggestion() {
                if (this.loc.supplierSuggestion) {
                    this.loc.selectedState = this.loc.supplierSuggestion.department;
                    await this.locOnStateChange(true);
                    // Wait for cities to be rendered before selecting the city
                    setTimeout(() => {
                        this.loc.selectedCity = this.loc.supplierSuggestion.city;
                        this.loc.supplierSuggestion = null;
                    }, 250);
                }
            },

            // --- NUEVO: Sugerencia geográfica desde proveedor ---
            async onSupplierChange() {
                const sid = this.formData.supplier_id;
                if (!sid) { this.loc.supplierSuggestion = null; return; }
                try {
                    const res = await fetch(`api_supplier_location.php?supplier_id=${sid}`);
                    const data = await res.json();
                    if (!data.error && data.department) {
                        this.loc.supplierSuggestion = { department: data.department, city: data.city };
                    }
                } catch(e) { console.warn('Supplier location fetch failed', e); }
            },

            // --- NUEVO: Panel de impacto financiero en tiempo real ---
            async recalcImpact() {
                const tripId = this.formData.trip_id;
                if (!tripId) { this.impact.visible = false; return; }
                try {
                    const res = await fetch(`api.php?action=getTripFinancials&trip_id=${tripId}`);
                    const data = await res.json();
                    if (!data.error && data.trip) {
                        const fleteNeto = parseFloat(data.trip.flete_neto) || 0;
                        const prevExpenses = parseFloat(data.trip.total_conductor_expenses) || 0;
                        const newAmount = parseFloat(this.formData.amount) || 0;
                        const utilidad = fleteNeto - prevExpenses - newAmount;
                        this.impact.fleteNeto = Math.round(fleteNeto);
                        this.impact.utilidadViaje = Math.round(utilidad);
                        this.impact.margen = fleteNeto > 0 ? (utilidad / fleteNeto) * 100 : 0;
                        this.impact.visible = true;
                    }
                } catch(e) { console.warn('Impact calc failed', e); }
            }
        }
    }
</script>

<?php include 'includes/footer.php'; ?>