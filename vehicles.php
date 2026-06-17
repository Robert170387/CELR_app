<?php
include 'includes/db.php';
include 'includes/header.php';

$vehicles = $pdo->query("SELECT v.*, CONCAT(p.firstname, ' ', p.lastname) as partner_name 
                        FROM vehicles v 
                        LEFT JOIN personnel p ON v.partner_id = p.id 
                        ORDER BY v.id DESC")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php displayAlerts(); ?>
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Vehículos</h1>
            <p class="mt-2 text-sm text-gray-700">Lista de todos los vehículos registrados.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="vehicle_form.php"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">Nuevo
                Vehículo</a>
        </div>
    </div>
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">#
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Placa
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Marca
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Modelo
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Propietario
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado
                                </th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span
                                        class="sr-only">Editar</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php $counter = 1;
                            foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-400 sm:pl-6">
                                        <?php echo $counter++; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-gray-900">
                                        <?php echo htmlspecialchars($vehicle['placa']); ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($vehicle['brand']); ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($vehicle['model']); ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span
                                                class="font-bold text-gray-900"><?php echo htmlspecialchars($vehicle['ownership_type'] ?? 'Propio'); ?></span>
                                            <?php if ($vehicle['ownership_type'] !== 'Propio' && $vehicle['partner_name']): ?>
                                                <span
                                                    class="text-xs text-brand-600 font-semibold"><?php echo htmlspecialchars($vehicle['partner_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?php if (($vehicle['active'] ?? 1) == 1): ?>
                                            <span
                                                class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Activo</span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <a href="vehicle_details.php?id=<?php echo $vehicle['id']; ?>"
                                            class="inline-block text-blue-600 hover:text-blue-900 mr-3" title="Ver">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="vehicle_form.php?id=<?php echo $vehicle['id']; ?>"
                                            class="inline-block text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <a href="vehicle_delete.php?id=<?php echo $vehicle['id']; ?>"
                                            onclick="return confirm('¿Está seguro de que desea eliminar este vehículo?');"
                                            class="inline-block text-red-600 hover:text-red-900" title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>