<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

// Fetch all maintenance schedules with current mileage from the view
$schedules = $pdo->query("
    SELECT ms.*, v.placa, va.last_kms
    FROM maintenance_schedules ms
    JOIN vehicles v ON ms.vehicle_id = v.id
    JOIN view_vehicle_availability va ON v.id = va.id
    ORDER BY ms.priority DESC, (ms.next_service_kms - va.last_kms) ASC
")->fetchAll();
?>

<div class="space-y-10">
    <?php displayAlerts(); ?>

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between py-4 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Mantenimiento Preventivo</h1>
            <p class="text-base font-medium text-slate-500 mt-1">Control de flota y ciclos de vida mecánica.</p>
        </div>
        <div class="mt-6 lg:mt-0">
            <a href="maintenance_form.php" class="btn-saas btn-saas-primary shadow-xl">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Programar Nueva Tarea
            </a>
        </div>
    </div>

    <!-- Maintenance Board -->
    <div class="bg-white rounded-lg border border-slate-200 shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-64">Vehículo y Tarea</th>
                        <th>Configuración</th>
                        <th class="w-80">Estado de Vida Útil</th>
                        <th>Alerta de Sistema</th>
                        <th class="text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="5" class="py-32 text-center text-slate-400 font-bold italic text-lg">No hay tareas
                                de mantenimiento programadas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $task): ?>
                            <?php
                            $kms_left = $task['next_service_kms'] - $task['last_kms'];
                            $is_overdue = $kms_left <= 0;
                            $is_warning = !$is_overdue && ($kms_left <= $task['warning_margin_kms']);

                            // Progress percentage (current usage within the interval)
                            $usage = $task['last_kms'] - $task['last_service_kms'];
                            $percent = ($usage / $task['interval_kms']) * 100;
                            $percent = max(0, min(100, $percent));

                            $barColor = $is_overdue ? 'bg-red-500' : ($is_warning ? 'bg-amber-500' : 'bg-brand-500');
                            $textColor = $is_overdue ? 'text-red-700' : ($is_warning ? 'text-amber-700' : 'text-emerald-700');
                            ?>
                            <tr class="group hover:bg-slate-50/50 transition-all">
                                <td>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 leading-tight">
                                            <?php echo htmlspecialchars($task['placa']); ?>
                                        </p>
                                        <p class="text-[11px] font-bold text-slate-500 mt-1 uppercase tracking-widest">
                                            <?php echo htmlspecialchars($task['task_name']); ?>
                                        </p>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter mb-2">Ciclo:
                                            <?php echo number_format($task['interval_kms']); ?> Km</span>
                                        <?php
                                        $priorityClass = match ($task['priority']) {
                                            'Crítica' => 'bg-red-100 text-red-800 border-red-200',
                                            'Alta' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                                        };
                                        ?>
                                        <span
                                            class="badge-saas border <?php echo $priorityClass; ?> w-fit font-semibold"><?php echo $task['priority']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-1 h-3 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                                            <div class="h-full <?php echo $barColor; ?> transition-all duration-1000 ease-out"
                                                style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <span
                                            class="text-xs font-bold text-slate-700 font-mono"><?php echo floor($percent); ?>%</span>
                                    </div>
                                    <div class="flex justify-between mt-2 px-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Inicio:
                                            <?php echo number_format($task['last_service_kms']); ?></span>
                                        <span class="text-[10px] font-bold text-brand-600 uppercase">Meta:
                                            <?php echo number_format($task['next_service_kms']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col <?php echo $textColor; ?>">
                                        <div class="flex items-center">
                                            <span class="relative flex h-3 w-3 mr-3 mt-0.5">
                                                <span
                                                    class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo $is_overdue ? 'bg-red-400' : ($is_warning ? 'bg-amber-400' : 'bg-emerald-400'); ?> opacity-75"></span>
                                                <span
                                                    class="relative inline-flex rounded-full h-3 w-3 <?php echo $is_overdue ? 'bg-red-600' : ($is_warning ? 'bg-amber-600' : 'bg-emerald-600'); ?>"></span>
                                            </span>
                                            <span class="text-sm font-bold uppercase tracking-tighter">
                                                <?php
                                                if ($is_overdue)
                                                    echo "Crítico: Vencido por " . number_format(abs($kms_left)) . " Km";
                                                elseif ($is_warning)
                                                    echo "Atención: Restan " . number_format($kms_left) . " Km";
                                                else
                                                    echo "Óptimo: Restan " . number_format($kms_left) . " Km";
                                                ?>
                                            </span>
                                        </div>
                                        <span
                                            class="text-[11px] font-semibold text-slate-400 mt-2 ml-6 uppercase tabular-nums">KM
                                            Actual: <?php echo number_format($task['last_kms']); ?></span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end space-x-3">
                                        <a href="maintenance_log_form.php?schedule_id=<?php echo $task['id']; ?>"
                                            class="btn-saas px-4 py-2 bg-slate-900 !text-white hover:bg-brand-600 text-xs font-bold shadow-lg shadow-slate-200">
                                            REGISTRAR SERVICIO
                                        </a>
                                        <a href="maintenance_form.php?id=<?php echo $task['id']; ?>"
                                            class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-indigo-600 transition-all border border-slate-100 shadow-sm"
                                            title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>