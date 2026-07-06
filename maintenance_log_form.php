<?php
include 'includes/db.php';
include 'includes/header.php';

$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    header("Location: maintenance_list.php?error=" . urlencode("Seleccione una tarea programada."));
    exit;
}

$stmt = $pdo->prepare("SELECT ms.*, v.placa, va.last_kms as current_kms
                      FROM maintenance_schedules ms
                      JOIN vehicles v ON ms.vehicle_id = v.id
                      JOIN view_vehicle_availability va ON v.id = va.id
                      WHERE ms.id = ?");
$stmt->execute([$schedule_id]);
$task = $stmt->fetch();

if (!$task) {
    header("Location: maintenance_list.php?error=" . urlencode("Tarea no encontrada."));
    exit;
}
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <h3 class="text-xl font-medium leading-6 text-gray-900 border-b-2 border-brand-500 pb-2 inline-block">
                Registrar Servicio Realizado</h3>
            <p class="mt-4 text-sm text-gray-600">
                Al registrar este servicio, el sistema actualizará automáticamente el próximo kilometraje de
                mantenimiento basado en la frecuencia definida.
            </p>
            <div class="mt-6 bg-brand-50 p-4 rounded-lg border border-brand-100">
                <p class="text-xs uppercase font-bold text-brand-700">Resumen de Tarea</p>
                <p class="text-sm font-bold text-gray-900 mt-1">
                    <?php echo htmlspecialchars($task['task_name']); ?>
                </p>
                <p class="text-xs text-gray-500 mt-1">Vehículo:
                    <?php echo $task['placa']; ?>
                </p>
                <p class="text-xs text-gray-500">Kilometraje planificado:
                    <?php echo number_format($task['next_service_kms']); ?> Km
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form action="save_maintenance_log.php" method="POST" enctype="multipart/form-data"
                class="shadow sm:rounded-md overflow-hidden" x-data="{
                performed_kms: <?php echo $task['current_kms']; ?>,
                cost: 0
            }">
                <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="px-4 py-5 bg-white sm:p-6 space-y-5">
                    <div class="grid grid-cols-6 gap-6">
                        <!-- Performed Date -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Fecha del Servicio</label>
                            <input type="date" name="performed_date" required value="<?php echo date('Y-m-d'); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <!-- Performed KMS -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Kilometraje al realizar (Km)</label>
                            <input type="number" name="performed_at_kms" required x-model="performed_kms"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <p class="mt-1 text-[10px] text-gray-400">KM Actual en sistema:
                                <?php echo number_format($task['current_kms']); ?>
                            </p>
                        </div>

                        <!-- Cost -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Costo del Servicio ($)</label>
                            <input type="number" name="cost" required x-model="cost"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <!-- Technician -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Taller / Técnico</label>
                            <input type="text" name="technician" placeholder="Ej: Taller Central, Mecánico Juan"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <!-- Notes -->
                        <div class="col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Observaciones / Detalles</label>
                            <textarea name="notes" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="Detalle qué se cambió o qué se revisó..."></textarea>
                        </div>

                        <!-- Receipt Photo -->
                        <div class="col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Foto de Factura / Soporte</label>
                            <div
                                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-brand-400 transition-colors">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-brand-600 hover:text-brand-500">
                                            <span>Subir archivo</span>
                                            <input name="receipt_photo" type="file" class="sr-only" accept="image/*">
                                        </label>
                                        <p class="pl-1">o arrastrar y soltar</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG hasta 5MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <a href="maintenance_list.php"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 mr-3">Cancelar</a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Confirmar y Finalizar Servicio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
