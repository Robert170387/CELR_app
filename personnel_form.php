<?php
include 'includes/db.php';
include 'includes/header.php';

$p = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $p = $stmt->fetch();
}
?>

<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                <?php echo $p ? 'Editar Personal' : 'Nuevo Personal'; ?>
            </h2>
        </div>
    </div>

    <form action="save_personnel.php" method="POST"
        class="space-y-8 divide-y divide-gray-200 bg-white p-8 shadow rounded-lg">
        <?php if ($p): ?><input type="hidden" name="id" value="<?php echo $p['id']; ?>"><?php endif; ?>

        <!-- Section 1: Basic Info -->
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Información Básica</h3>
            <p class="mt-1 text-sm text-gray-500">Tipo de personal y datos de identificación.</p>

            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <!-- Tipo Personal -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Tipo de Personal</label>
                    <select name="type"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                        <option value="Conductor" <?php echo ($p && $p['type'] == 'Conductor') ? 'selected' : ''; ?>>
                            Conductor</option>
                        <option value="Administrativo" <?php echo ($p && $p['type'] == 'Administrativo') ? 'selected' : ''; ?>>Administrativo</option>
                        <option value="Socio / Propietario" <?php echo ($p && $p['type'] == 'Socio / Propietario') ? 'selected' : ''; ?>>Socio / Propietario</option>
                    </select>
                </div>

                <!-- Document Type -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Tipo Documento</label>
                    <select name="document_type"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                        <option value="CC" <?php echo ($p && $p['document_type'] == 'CC') ? 'selected' : ''; ?>>Cédula
                            Ciudadanía</option>
                        <option value="CE" <?php echo ($p && $p['document_type'] == 'CE') ? 'selected' : ''; ?>>Cédula
                            Extranjería</option>
                        <option value="NIT" <?php echo ($p && $p['document_type'] == 'NIT') ? 'selected' : ''; ?>>NIT
                        </option>
                        <option value="Pasaporte" <?php echo ($p && $p['document_type'] == 'Pasaporte') ? 'selected' : ''; ?>>Pasaporte</option>
                    </select>
                </div>

                <!-- Document Number -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Número Documento</label>
                    <input type="text" name="document_number" value="<?php echo $p['document_number'] ?? ''; ?>"
                        required
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <!-- Names -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Nombres</label>
                    <input type="text" name="firstname" value="<?php echo $p['firstname'] ?? ''; ?>" required
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <!-- Lastnames -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Apellidos</label>
                    <input type="text" name="lastname" value="<?php echo $p['lastname'] ?? ''; ?>" required
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <!-- Gender -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Género</label>
                    <select name="gender"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                        <option value="Masculino" <?php echo ($p && $p['gender'] == 'Masculino') ? 'selected' : ''; ?>>
                            Masculino</option>
                        <option value="Femenino" <?php echo ($p && $p['gender'] == 'Femenino') ? 'selected' : ''; ?>>
                            Femenino</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Contact Info -->
        <div class="pt-8"
            x-data="locationSelector('<?php echo $p['country'] ?? 'Colombia'; ?>', '<?php echo $p['department'] ?? ''; ?>', '<?php echo $p['city'] ?? ''; ?>')"
            x-init="init()">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Datos de Contacto</h3>
            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-6">
                    <label class="block text-sm font-medium text-gray-700">Dirección</label>
                    <input type="text" name="address" value="<?php echo $p['address'] ?? ''; ?>"
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Teléfono / Celular</label>
                    <input type="text" name="phone" value="<?php echo $p['phone'] ?? ''; ?>"
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                </div>

                <!-- Country -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">País</label>
                    <select name="country" x-model="selectedCountry" @change="onCountryChange()"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">-- Seleccione --</option>
                        <template x-for="c in countries" :key="c.iso2">
                            <option :value="c.name" x-text="c.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Department -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Departamento</label>
                    <select name="department" x-model="selectedState" @change="onStateChange()"
                        :disabled="!selectedCountry"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">-- Seleccione --</option>
                        <template x-for="st in states" :key="st.name">
                            <option :value="st.name" x-text="st.name"></option>
                        </template>
                    </select>
                </div>

                <!-- City -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Ciudad / Municipio</label>
                    <select name="city" x-model="selectedCity" :disabled="!selectedState"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">-- Seleccione --</option>
                        <template x-for="ci in cities" :key="ci.id">
                            <option :value="ci.name" x-text="ci.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 3: Professional Info (Driving) -->
        <div class="pt-8">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Información Profesional (Conductores)</h3>
            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">N° Licencia Conducción</label>
                    <input type="text" name="license_number" value="<?php echo $p['license_number'] ?? ''; ?>"
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Categoría Licencia</label>
                    <select name="license_category"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                        <option value="">Seleccione...</option>
                        <option value="A1" <?php echo ($p && $p['license_category'] == 'A1') ? 'selected' : ''; ?>>A1
                        </option>
                        <option value="A2" <?php echo ($p && $p['license_category'] == 'A2') ? 'selected' : ''; ?>>A2
                        </option>
                        <option value="B1" <?php echo ($p && $p['license_category'] == 'B1') ? 'selected' : ''; ?>>B1
                        </option>
                        <option value="B2" <?php echo ($p && $p['license_category'] == 'B2') ? 'selected' : ''; ?>>B2
                        </option>
                        <option value="B3" <?php echo ($p && $p['license_category'] == 'B3') ? 'selected' : ''; ?>>B3
                        </option>
                        <option value="C1" <?php echo ($p && $p['license_category'] == 'C1') ? 'selected' : ''; ?>>C1
                        </option>
                        <option value="C2" <?php echo ($p && $p['license_category'] == 'C2') ? 'selected' : ''; ?>>C2
                        </option>
                        <option value="C3" <?php echo ($p && $p['license_category'] == 'C3') ? 'selected' : ''; ?>>C3
                        </option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-red-700 font-bold">Vencimiento Licencia</label>
                    <input type="date" name="license_expiry" value="<?php echo $p['license_expiry'] ?? ''; ?>"
                        class="mt-1 focus:ring-red-500 focus:border-red-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-red-50">
                </div>
            </div>
        </div>

        <!-- Section 4: Contract & Financial -->
        <div class="pt-8">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Datos Contratación y Financieros</h3>
            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Fecha Ingreso</label>
                    <input type="date" name="date_entry" value="<?php echo $p['date_entry'] ?? ''; ?>"
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Fecha Salida</label>
                    <input type="date" name="date_exit" value="<?php echo $p['date_exit'] ?? ''; ?>"
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-sm font-medium text-gray-700">N° Cuenta Bancaria</label>
                    <input type="text" name="bank_account" value="<?php echo $p['bank_account'] ?? ''; ?>"
                        class="mt-1 focus:ring-brand-500 focus:border-brand-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Sueldo Básico</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" name="salary_basic" value="<?php echo $p['salary_basic'] ?? 0; ?>"
                            class="focus:ring-brand-500 focus:border-brand-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
                            placeholder="0.00">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Auxilio Transporte</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" name="transport_assistance"
                            value="<?php echo $p['transport_assistance'] ?? 0; ?>"
                            class="focus:ring-brand-500 focus:border-brand-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
                            placeholder="0.00">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Sueldo Variable</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" name="salary_variable" value="<?php echo $p['salary_variable'] ?? 0; ?>"
                            class="focus:ring-brand-500 focus:border-brand-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
                            placeholder="0.00">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Sueldo Interno</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" name="salary_internal" value="<?php echo $p['salary_internal'] ?? 0; ?>"
                            class="focus:ring-brand-500 focus:border-brand-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
                            placeholder="0.00">
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-5">
            <div class="flex justify-end">
                <a href="personnel.php"
                    class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Cancelar
                </a>
                <button type="submit"
                    class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Guardar Personal
                </button>
            </div>
        </div>
    </form>
</div>

<script src="js/location_selector.js"></script>
<?php include 'includes/footer.php'; ?>