<?php
include 'includes/db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
$task = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_schedules WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch();
}

$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active = 1 ORDER BY placa")->fetchAll();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <h3 class="text-xl font-medium leading-6 text-gray-900">
                <?php echo $id ? 'Editar Tarea' : 'Programar Tarea'; ?>
            </h3>
            <p class="mt-2 text-sm text-gray-600">
                Define una alerta de mantenimiento basada en el kilometraje recorrido por el vehículo.
            </p>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form action="save_maintenance.php" method="POST" class="shadow sm:rounded-md overflow-hidden" x-data="{
                next_kms: <?php echo $task['next_service_kms'] ?? 0; ?>,
                last_kms: <?php echo $task['last_service_kms'] ?? 0; ?>,
                interval: <?php echo $task['interval_kms'] ?? 5000; ?>,
                updateNext() {
                    this.next_kms = parseFloat(this.last_kms) + parseFloat(this.interval);
                }
            }">
                <?php if ($id): ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php endif; ?>

                <div class="px-4 py-5 bg-white sm:p-6 space-y-4">
                    <div class="grid grid-cols-6 gap-6">
                        <!-- Vehicle -->
                        <div class="col-span-6 sm:col-span-4">
                            <label class="block text-sm font-medium text-gray-700">Vehículo</label>
                            <select name="vehicle_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="">Seleccione vehículo...</option>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php echo ($task && $task['vehicle_id'] == $v['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['placa']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Task Name -->
                        <div class="col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Nombre de la Tarea</label>
                            <input type="text" name="task_name" required value="<?php echo $task['task_name'] ?? ''; ?>"
                                placeholder="Ej: Cambio de Aceite, Rotación de Llantas"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <!-- Interval -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Frecuencia (Cada cuántos Km)</label>
                            <input type="number" name="interval_kms" required x-model="interval" @input="updateNext()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <!-- Last Service KMS -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Kilometraje Último Servicio</label>
                            <input type="number" name="last_service_kms" required x-model="last_kms"
                                @input="updateNext()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <p class="mt-1 text-[10px] text-gray-400 italic">Si nunca se ha registrado, use el
                                kilometraje actual del camión.</p>
                        </div>

                        <!-- Next Service KMS (Calculated) -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 text-brand-700 font-bold">Próximo
                                Servicio Sugerido (Km)</label>
                            <input type="number" name="next_service_kms" required x-model="next_kms"
                                class="mt-1 block w-full rounded-md border-brand-200 bg-brand-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm font-black">
                        </div>

                        <!-- Warning Margin -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Avisar antes de (Km)</label>
                            <input type="number" name="warning_margin_kms" required
                                value="<?php echo $task['warning_margin_kms'] ?? 500; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        </div>

                        <!-- Priority -->
                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Prioridad</label>
                            <select name="priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="Baja" <?php echo ($task && $task['priority'] == 'Baja') ? 'selected' : ''; ?>>Baja</option>
                                <option value="Media" <?php echo (!$task || $task['priority'] == 'Media') ? 'selected' : ''; ?>>Media</option>
                                <option value="Alta" <?php echo ($task && $task['priority'] == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                                <option value="Crítica" <?php echo ($task && $task['priority'] == 'Crítica') ? 'selected' : ''; ?>>Crítica</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <a href="maintenance_list.php"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 mr-3">Cancelar</a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                        <?php echo $id ? 'Actualizar' : 'Guardar Programa'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>