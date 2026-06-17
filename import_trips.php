<?php
include 'includes/db.php';
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-xl font-medium leading-6 text-gray-900">Importar Histórico de Viajes</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Carga un archivo CSV o Excel para importar viajes masivamente.
                </p>
                <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <h4 class="text-sm font-bold text-yellow-800">Instrucciones:</h4>
                    <ul class="mt-2 list-disc list-inside text-xs text-yellow-700 space-y-1">
                        <li>El archivo debe ser <strong>.csv</strong> (delimitado por comas o punto y coma).</li>
                        <li>Las fechas deben tener formato <strong>DD/MM/AAAA</strong>.</li>
                        <li>El sistema creará automáticamente Vehículos y Conductores si no existen (basado en Placa y
                            Nombre).</li>
                        <li>Los valores monetarios se limpiarán automáticamente (se ignoran '$', ',' y '.').</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form action="import_trips_handler.php" method="POST" enctype="multipart/form-data">
                <div class="shadow sm:rounded-md sm:overflow-hidden">
                    <div class="px-4 py-5 bg-white space-y-6 sm:p-6">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Archivo CSV</label>
                            <div
                                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48" aria-hidden="true">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file-upload"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-brand-600 hover:text-brand-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-brand-500">
                                            <span>Subir un archivo</span>
                                            <input id="file-upload" name="csv_file" type="file" class="sr-only"
                                                accept=".csv" required>
                                        </label>
                                        <p class="pl-1">o arrastrar y soltar</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        CSV hasta 10MB
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            Iniciar Importación
                        </button>
                    </div>
                </div>
            </form>

            <?php if (isset($_GET['status'])): ?>
                <div
                    class="mt-4 p-4 rounded-md <?php echo $_GET['status'] == 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
                    <?php if ($_GET['status'] == 'success'): ?>
                        <strong>¡Éxito!</strong> Se importaron
                        <?php echo $_GET['count'] ?? 0; ?> viajes correctamente.
                    <?php else: ?>
                        <strong>Error:</strong>
                        <?php echo htmlspecialchars($_GET['msg'] ?? 'Error desconocido'); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>