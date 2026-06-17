<?php
include 'includes/db.php';
include 'includes/header.php';

$s = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $s = $stmt->fetch();
}

// Check initial type for JS toggle
$initialType = $s ? $s['person_type'] : 'Natural';
?>

<div class="max-w-3xl mx-auto py-10 px-4">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            <?php echo $s ? 'Editar Proveedor' : 'Nuevo Proveedor'; ?>
        </h2>
    </div>

    <!-- Alpine.js Data Scope -->
    <div x-data="{ type: '<?php echo $initialType; ?>' }">

        <form action="save_supplier.php" method="POST"
            class="space-y-8 divide-y divide-gray-200 bg-white p-8 shadow rounded-lg">
            <?php if ($s): ?><input type="hidden" name="id" value="<?php echo $s['id']; ?>">
            <?php endif; ?>

            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Información General</h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                    <!-- Person Type & Regime -->
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Tipo de Persona</label>
                        <select name="person_type" x-model="type"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                            <option value="Natural">Natural</option>
                            <option value="Jurídica">Jurídica</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Régimen</label>
                        <select name="tax_regime"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                            <option value="Simplificado" <?php echo ($s && $s['tax_regime'] == 'Simplificado') ? 'selected' : ''; ?>>Simplificado</option>
                            <option value="Común" <?php echo ($s && $s['tax_regime'] == 'Común') ? 'selected' : ''; ?>>
                                Común</option>
                        </select>
                    </div>

                    <!-- NIT -->
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">NIT / Cédula</label>
                        <input type="text" name="nit" value="<?php echo $s['nit'] ?? ''; ?>" required
                            class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <!-- Fields for Natural Person -->
                    <div class="sm:col-span-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6"
                        x-show="type === 'Natural'">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nombres</label>
                            <input type="text" name="firstname" value="<?php echo $s['firstname'] ?? ''; ?>"
                                :required="type === 'Natural'"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Primer Apellido</label>
                            <input type="text" name="lastname1" value="<?php echo $s['lastname1'] ?? ''; ?>"
                                :required="type === 'Natural'"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Segundo Apellido</label>
                            <input type="text" name="lastname2" value="<?php echo $s['lastname2'] ?? ''; ?>"
                                class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                        </div>
                    </div>

                    <!-- Fields for Juridical Person -->
                    <div class="sm:col-span-6" x-show="type === 'Jurídica'" style="display: none;">
                        <!-- Hidden by default if PHP says Natural, handled by Alpine -->
                        <label class="block text-sm font-medium text-gray-700">Razón Social</label>
                        <input type="text" name="business_name" value="<?php echo $s['business_name'] ?? ''; ?>"
                            :required="type === 'Jurídica'"
                            class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <!-- Location with Cascading Selectors -->
                    <div class="sm:col-span-6 border-t border-gray-200 mt-4 pt-4"
                        x-data="locationSelector('<?php echo $s['country'] ?? 'Colombia'; ?>', '<?php echo $s['department'] ?? ''; ?>', '<?php echo $s['city'] ?? ''; ?>')"
                        x-init="init()">
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Ubicación</h4>

                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                                <input type="text" name="address" value="<?php echo $s['address'] ?? ''; ?>"
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
                                <label class="block text-sm font-medium text-gray-700">Departamento</label>
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
                                    <template x-for="ci in cities" :key="ci">
                                        <option :value="ci" x-text="ci" :selected="ci == selectedCity"></option>
                                    </template>
                                </select>
                                <p x-show="isLoadingCities" class="text-xs text-brand-500 mt-1">Cargando...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Info -->
                    <div class="sm:col-span-6 border-t border-gray-200 mt-4 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Información Bancaria</h4>
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Banco</label>
                                <input type="text" name="bank_name" value="<?php echo $s['bank_name'] ?? ''; ?>"
                                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Tipo de Cuenta</label>
                                <select name="account_type"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                                    <option value="Ahorros" <?php echo ($s && $s['account_type'] == 'Ahorros') ? 'selected' : ''; ?>>Ahorros</option>
                                    <option value="Corriente" <?php echo ($s && $s['account_type'] == 'Corriente') ? 'selected' : ''; ?>>Corriente</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Número de Cuenta</label>
                                <input type="text" name="bank_account" value="<?php echo $s['bank_account'] ?? ''; ?>"
                                    class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="pt-5">
                <div class="flex justify-end">
                    <a href="suppliers.php"
                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                        Guardar Proveedor
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="js/location_selector.js"></script>
<?php include 'includes/footer.php'; ?>