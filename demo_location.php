<?php
include 'includes/db.php';
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="bg-white shadow sm:rounded-md overflow-hidden p-6">
        <h3 class="text-xl font-bold text-gray-900 border-b pb-4 mb-6">Demo: Ubicación Local</h3>

        <div x-data="locationSelector('Colombia', '', '')" x-init="init()">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Country -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">País</label>
                    <select x-model="selectedCountry" @change="onCountryChange()"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">-- Seleccione --</option>
                        <template x-for="c in countries" :key="c.id">
                            <option :value="c.name" x-text="c.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Departamento</label>
                    <select x-model="selectedState" @change="onStateChange()" :disabled="!selectedCountry"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">-- Seleccione --</option>
                        <template x-for="st in states" :key="st.id">
                            <option :value="st.name" x-text="st.name"></option>
                        </template>
                    </select>
                    <p x-show="isLoadingStates" class="text-xs text-brand-500 mt-1">Cargando...</p>
                </div>

                <!-- City -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ciudad</label>
                    <select x-model="selectedCity" :disabled="!selectedState"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">-- Seleccione --</option>
                        <template x-for="ci in cities" :key="ci">
                            <option :value="ci" x-text="ci"></option>
                        </template>
                    </select>
                    <p x-show="isLoadingCities" class="text-xs text-brand-500 mt-1">Cargando...</p>
                </div>
            </div>

            <!-- Debug Info -->
            <div class="mt-8 p-4 bg-gray-100 rounded text-sm font-mono text-gray-600">
                Seleccionado: <span x-text="selectedCountry"></span> /
                <span x-text="selectedState"></span> /
                <span x-text="selectedCity"></span>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>