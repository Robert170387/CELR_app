<?php
include 'includes/db.php';
include 'includes/header.php';

$clients = $pdo->query("SELECT * FROM clients ORDER BY 
    CASE 
        WHEN person_type = 'Jurídica' THEN business_name
        ELSE CONCAT(firstname, ' ', lastname1)
    END ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php displayAlerts(); ?>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Clientes</h1>
            <p class="mt-2 text-sm text-gray-700">Lista de todos los clientes registrados en el sistema.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="client_form.php"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                Nuevo Cliente
            </a>
        </div>
    </div>

    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">#
                                </th>
                                <th class="py-3.5 px-3 text-left text-sm font-semibold text-gray-900">
                                    Nombre/Razón Social
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tipo</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Contacto</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ciudad</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php if (empty($clients)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No hay clientes registrados aún.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $counter = 1;
                                foreach ($clients as $client): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-400 sm:pl-6">
                                            <?php echo $counter++; ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-gray-900">
                                            <?php
                                            if ($client['person_type'] == 'Jurídica') {
                                                echo htmlspecialchars($client['business_name']);
                                            } else {
                                                echo htmlspecialchars($client['firstname'] . ' ' . $client['lastname1']);
                                            }
                                            ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <span
                                                class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                                <?php echo $client['person_type'] == 'Jurídica' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                <?php echo htmlspecialchars($client['person_type']); ?>
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php if ($client['email']): ?>
                                                <div>
                                                    <?php echo htmlspecialchars($client['email']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($client['phone'] || $client['mobile']): ?>
                                                <div class="text-xs text-gray-400">
                                                    <?php echo htmlspecialchars($client['phone'] ?: $client['mobile']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($client['city'] ?: '-'); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <?php if ($client['active']): ?>
                                                <span
                                                    class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                                    Activo
                                                </span>
                                            <?php else: ?>
                                                <span
                                                    class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                                    Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td
                                            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <a href="client_details.php?id=<?php echo $client['id']; ?>"
                                                class="inline-block text-blue-600 hover:text-blue-900 mr-3" title="Ver">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="client_form.php?id=<?php echo $client['id']; ?>"
                                                class="inline-block text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <a href="client_delete.php?id=<?php echo $client['id']; ?>"
                                                onclick="return confirm('¿Está seguro de que desea eliminar este cliente?');"
                                                class="inline-block text-red-600 hover:text-red-900" title="Eliminar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>