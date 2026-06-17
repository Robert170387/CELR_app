<?php
// vehicle_form.php
include 'includes/db.php';
include 'includes/header.php';

$vehicle = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $vehicle = $stmt->fetch();
}

$partners = $pdo->query("SELECT id, firstname, lastname FROM personnel WHERE type='Socio / Propietario' AND active=1 ORDER BY firstname ASC")->fetchAll();
?>

<div class="max-w-xl mx-auto py-10 px-4">
    <h3 class="text-lg font-medium text-gray-900"><?php echo $vehicle ? 'Editar' : 'Nuevo'; ?> Vehículo</h3>
    <form action="save_vehicle.php" method="POST" class="mt-5 space-y-6">
        <?php if ($vehicle): ?><input type="hidden" name="id" value="<?php echo $vehicle['id']; ?>"><?php endif; ?>

        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <!-- Habitual Driver -->
            <div class="sm:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Conductor Habitual (Sugerencia
                    automática)</label>
                <?php $all_drivers = $pdo->query("SELECT id, firstname, lastname FROM personnel WHERE type='Conductor' AND active=1 ORDER BY firstname ASC")->fetchAll(); ?>
                <select name="default_driver_id"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    <option value="">-- Sin conductor asignado --</option>
                    <?php foreach ($all_drivers as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($vehicle && $vehicle['default_driver_id'] == $d['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['firstname'] . ' ' . $d['lastname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Column 1 -->
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Placa</label>
                <input type="text" name="placa" value="<?php echo $vehicle['placa'] ?? ''; ?>" required
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">ID SATRACK</label>
                <input type="text" name="satrack_id" value="<?php echo $vehicle['satrack_id'] ?? ''; ?>"
                    placeholder="ID Dispositivo"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-white font-bold text-brand-700">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Marca</label>
                <input type="text" name="brand" value="<?php echo $vehicle['brand'] ?? ''; ?>" required
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-sm font-medium text-gray-700">Modelo</label>
                <input type="text" name="model" value="<?php echo $vehicle['model'] ?? ''; ?>" required
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <input type="text" name="category" value="<?php echo $vehicle['category'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="active"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    <option value="1" <?php echo ($vehicle['active'] ?? 1) == 1 ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo ($vehicle['active'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Año Modelo</label>
                <input type="text" name="model_year" value="<?php echo $vehicle['model_year'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Color</label>
                <input type="text" name="color" value="<?php echo $vehicle['color'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Cilindraje</label>
                <input type="text" name="displacement" value="<?php echo $vehicle['displacement'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>

            <div class="sm:col-span-6 border-t border-gray-200 mt-2 pt-2">
                <h4 class="text-sm font-bold text-gray-500">Información Técnica</h4>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">No. Serie</label>
                <input type="text" name="serial_number" value="<?php echo $vehicle['serial_number'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">No. Motor</label>
                <input type="text" name="motor_number" value="<?php echo $vehicle['motor_number'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">No. Chasis</label>
                <input type="text" name="chasis_number" value="<?php echo $vehicle['chasis_number'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-sm font-medium text-gray-700">Carga Máxima (Ton)</label>
                <input type="text" name="max_load" value="<?php echo $vehicle['max_load'] ?? ''; ?>"
                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
            </div>

            <!-- Expiration Dates (Alerts) -->
            <div class="sm:col-span-6 border-t border-gray-200 mt-4 pt-4">
                <h4 class="text-sm font-bold text-red-600 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Vencimientos y Documentación (Alertas)
                </h4>
                <div
                    class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-red-50 p-4 rounded-lg border border-red-100">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Vencimiento SOAT</label>
                        <input type="date" name="expiry_soat" value="<?php echo $vehicle['expiry_soat'] ?? ''; ?>"
                            class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Vencimiento Tecnomecánica</label>
                        <input type="date" name="expiry_tecno" value="<?php echo $vehicle['expiry_tecno'] ?? ''; ?>"
                            class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Vencimiento Póliza/Seguro</label>
                        <input type="date" name="expiry_policy" value="<?php echo $vehicle['expiry_policy'] ?? ''; ?>"
                            class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
            </div>
            <!-- Ownership Section -->
            <div class="sm:col-span-6 border-t border-gray-200 mt-6 pt-4"
                x-data="{ ownership: '<?php echo $vehicle['ownership_type'] ?? 'Propio'; ?>' }">
                <h4 class="text-sm font-bold text-gray-500 mb-4 uppercase">Propiedad y Participación</h4>

                <div
                    class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Tipo de Propiedad</label>
                        <select name="ownership_type" x-model="ownership"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                            <option value="Propio">Propio (100% Mío)</option>
                            <option value="Tercero">Tercero (Administrado)</option>
                            <option value="Socio">Sociedad / Socio</option>
                        </select>
                    </div>

                    <!-- Partner Fields (Shown if Socio or Tercero) -->
                    <div class="sm:col-span-3" x-show="ownership !== 'Propio'">
                        <label class="block text-sm font-medium text-gray-700"
                            x-text="ownership === 'Socio' ? 'Socio' : 'Propietario / Tercero'"></label>
                        <select name="partner_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                            <option value="">-- Seleccione Dueño/Socio --</option>
                            <?php foreach ($partners as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($vehicle && $vehicle['partner_id'] == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['firstname'] . ' ' . $p['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sm:col-span-3" x-show="ownership === 'Socio'">
                        <label class="block text-sm font-medium text-gray-700">% Participación del Socio</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" step="0.01" name="partner_percentage"
                                value="<?php echo $vehicle['partner_percentage'] ?? '0'; ?>"
                                class="focus:ring-brand-500 focus:border-brand-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Ej: 50.00 para mitad y mitad.</p>
                    </div>
                </div>
            </div>

            <div class="sm:col-span-6 border-t border-gray-200 mt-2 pt-2"
                x-data="locationSelector('<?php echo $vehicle['register_country'] ?? 'Colombia'; ?>', '<?php echo $vehicle['register_department'] ?? ''; ?>', '<?php echo $vehicle['register_city'] ?? ''; ?>')"
                x-init="init()">
                <h4 class="text-sm font-bold text-gray-500 mb-3">Ubicación de Registro</h4>

                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <!-- Country -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">País Registro</label>
                        <select name="register_country" x-model="selectedCountry" @change="onCountryChange()"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">-- Seleccione --</option>
                            <template x-for="c in countries" :key="c.iso2">
                                <option :value="c.name" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Department -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Depto. Registro</label>
                        <select name="register_department" x-model="selectedState" @change="onStateChange()"
                            :disabled="!selectedCountry"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">-- Seleccione --</option>
                            <template x-for="st in states" :key="st.name">
                                <option :value="st.name" x-text="st.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- City -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Ciudad / Municipio Registro</label>
                        <select name="register_city" x-model="selectedCity" :disabled="!selectedState"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">-- Seleccione --</option>
                            <template x-for="ci in cities" :key="ci">
                                <option :value="ci" x-text="ci"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex items-center space-x-4">
            <a href="vehicles.php"
                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                Cancelar
            </a>
            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700">Guardar</button>
        </div>
    </form>
</div>
<script src="js/location_selector.js"></script>
<?php include 'includes/footer.php'; ?>