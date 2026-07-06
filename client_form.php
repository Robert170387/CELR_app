<?php
include 'includes/db.php';
include 'includes/header.php';
$csrf_token = getCsrfToken();

$c = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $c = $stmt->fetch();
}

// Check initial type for JS toggle
$initialType = $c ? $c['person_type'] : 'Física';
?>

<div class="max-w-3xl mx-auto py-10 px-4">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            <?php echo $c ? 'Editar Cliente' : 'Nuevo Cliente'; ?>
        </h2>
    </div>

    <!-- Alpine.js Data Scope -->
    <div x-data="{ type: '<?php echo $initialType; ?>' }">

        <form action="save_client.php" method="POST"
            class="space-y-8 divide-y divide-gray-200 bg-white p-8 shadow rounded-lg">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <?php if ($c): ?><input type="hidden" name="id" value="<?php echo $c['id']; ?>">
            <?php endif; ?>

            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Información General</h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                    <!-- Person Type -->
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Tipo de Persona</label>
                        <select name="person_type" x-model="type"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                            <option value="Física">Física</option>
                            <option value="Jurídica">Jurídica</option>
                        </select>
                    </div>

                    <!-- Active Status -->
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <select name="active"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                            <option value="1" <?php echo ($c && $c['active'] == 1) ? 'selected' : ''; ?>>Activo</option>
                            <option value="0" <?php echo ($c && $c['active'] == 0) ? 'selected' : ''; ?>>Inactivo
                            </option>
                        </select>
                    </div>

                    <!-- Legal ID -->
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Cédula / RUC</label>
                        <input type="text" name="legal_id" value="<?php echo $c['legal_id'] ?? ''; ?>"
                            class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        <p class="mt-1 text-xs text-gray-500">Identificación fiscal del cliente</p>
                    </div>

                    <!-- Fields for Natural Person -->
                    <div class="sm:col-span-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6"
                        x-show="type === 'Física'">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nombres *</label>
                            <input type="text" name="firstname" value="<?php echo $c['firstname'] ?? ''; ?>"
                                :required="type === 'Física'"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Primer Apellido *</label>
                            <input type="text" name="lastname1" value="<?php echo $c['lastname1'] ?? ''; ?>"
                                :required="type === 'Física'"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Segundo Apellido</label>
                            <input type="text" name="lastname2" value="<?php echo $c['lastname2'] ?? ''; ?>"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>
                    </div>

                    <!-- Fields for Juridical Person -->
                    <div class="sm:col-span-6" x-show="type === 'Jurídica'" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700">Razón Social *</label>
                        <input type="text" name="business_name" value="<?php echo $c['business_name'] ?? ''; ?>"
                            :required="type === 'Jurídica'"
                            class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                    </div>

                </div>
            </div>

            <!-- Contact Information -->
            <div class="pt-8">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Información de Contacto</h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="text" inputmode="email" name="email" value="<?php echo $c['email'] ?? ''; ?>"
                            class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input type="text" name="phone" value="<?php echo $c['phone'] ?? ''; ?>"
                            class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Móvil</label>
                        <input type="text" name="mobile" value="<?php echo $c['mobile'] ?? ''; ?>"
                            class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                    </div>

                </div>
            </div>

            <!-- Location -->
            <div class="pt-8">
                <div x-data="locationSelector('<?php echo $c['country'] ?? 'Costa Rica'; ?>', '<?php echo $c['department'] ?? ''; ?>', '<?php echo $c['city'] ?? ''; ?>')"
                    x-init="init()">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Ubicación</h3>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Dirección</label>
                            <input type="text" name="address" value="<?php echo $c['address'] ?? ''; ?>"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>

                        <!-- Country -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">País</label>
                            <select name="country" x-model="selectedCountry" @change="onCountryChange()"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="">-- Seleccione --</option>
                                <template x-for="c in countries" :key="c.iso2">
                                    <option :value="c.name" x-text="c.name" :selected="c.name == selectedCountry">
                                    </option>
                                </template>
                            </select>
                            <p x-show="isLoadingCountries" class="text-xs text-brand-500 mt-1">Cargando...</p>
                        </div>

                        <!-- Department -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Provincia/Departamento</label>
                            <select name="department" x-model="selectedState" @change="onStateChange()"
                                :disabled="!selectedCountry"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="">-- Seleccione --</option>
                                <template x-for="st in states" :key="st.name">
                                    <option :value="st.name" x-text="st.name" :selected="st.name == selectedState">
                                    </option>
                                </template>
                            </select>
                            <p x-show="isLoadingStates" class="text-xs text-brand-500 mt-1">Cargando...</p>
                        </div>

                        <!-- City -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Ciudad / Municipio</label>
                            <select name="city" x-model="selectedCity" :disabled="!selectedState"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="">-- Seleccione --</option>
                                <template x-for="ci in cities" :key="ci.id">
                                    <option :value="ci.name" x-text="ci.name" :selected="ci.name == selectedCity"></option>
                                </template>
                            </select>
                            <p x-show="isLoadingCities" class="text-xs text-brand-500 mt-1">Cargando...</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Código Postal</label>
                            <input type="text" name="postal_code" value="<?php echo $c['postal_code'] ?? ''; ?>"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="pt-8">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Notas Adicionales</h3>
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                    <textarea name="notes" rows="3"
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50"><?php echo $c['notes'] ?? ''; ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">Información adicional sobre el cliente</p>
                </div>
            </div>

            <div class="pt-5">
                <div class="flex justify-end">
                    <a href="clients.php"
                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                        Guardar Cliente
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="js/location_selector.js"></script>
<?php include 'includes/footer.php'; ?>