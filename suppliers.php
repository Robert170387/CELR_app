<?php
include 'includes/db.php';
include 'includes/header.php';

// Fetch suppliers
$sql = "SELECT * FROM suppliers ORDER BY created_at DESC";
$suppliers = $pdo->query($sql)->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php displayAlerts(); ?>
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Proveedores</h1>
            <p class="mt-2 text-sm text-gray-700">Gestión de proveedores, personas naturales y jurídicas.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="supplier_form.php"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                Nuevo Proveedor
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
                                    Identificación</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nombre / Razón
                                    Social</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tipo</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ubicación</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php $counter = 1;
                            foreach ($suppliers as $s): ?>
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-400 sm:pl-6">
                                        <?php echo $counter++; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                        <div class="font-bold text-gray-900">
                                            <?php echo htmlspecialchars($s['nit']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo $s['tax_regime'] ?? ''; ?>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                        <?php
                                        if ($s['person_type'] === 'Jurídica') {
                                            echo '<span class="font-bold">' . htmlspecialchars($s['business_name']) . '</span>';
                                        } else {
                                            echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname1'] . ' ' . $s['lastname2']);
                                        }
                                        ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $s['person_type'] === 'Jurídica' ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo $s['person_type']; ?>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($s['city'] ?? ''); ?>,
                                        <?php echo htmlspecialchars($s['department'] ?? ''); ?>
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <a href="supplier_details.php?id=<?php echo $s['id']; ?>"
                                            class="inline-block text-blue-600 hover:text-blue-900 mr-3" title="Ver">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="supplier_form.php?id=<?php echo $s['id']; ?>"
                                            class="inline-block text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <a href="supplier_delete.php?id=<?php echo $s['id']; ?>"
                                            onclick="return confirm('¿Está seguro de que desea eliminar este proveedor?');"
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