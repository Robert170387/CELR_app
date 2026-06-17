<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

$sql = "SELECT t.id, t.date_load, t.manifest_number, t.origin, t.destination, t.advance_manifest, t.advance_owner, 
               v.placa, CONCAT(d.firstname, ' ', IFNULL(d.lastname, '')) as driver_name 
        FROM trips t 
        LEFT JOIN vehicles v ON t.vehicle_id = v.id 
        LEFT JOIN personnel d ON t.driver_id = d.id 
        WHERE t.advance_manifest > 0 OR t.advance_owner > 0
        ORDER BY t.date_load DESC";
$advances = $pdo->query($sql)->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Anticipos (Viáticos)</h1>
            <p class="mt-2 text-sm text-gray-700">Control de anticipos entregados por Manifiesto y por Propietario.</p>
        </div>
    </div>

    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Viaje
                                    #</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Fecha / Manifiesto
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vehículo /
                                    Conductor</th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Anticipo
                                    Manifiesto</th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Anticipo
                                    Propietario</th>
                                <th
                                    class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 font-bold bg-gray-100">
                                    Total Anticipos</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php if (empty($advances)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No se han registrado anticipos en los viajes aún.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($advances as $adv):
                                    $total = $adv['advance_manifest'] + $adv['advance_owner'];
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                            #
                                            <?php echo $adv['id']; ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <div class="font-medium text-gray-900">
                                                <?php echo $adv['date_load']; ?>
                                            </div>
                                            <div class="text-xs text-brand-600">MF:
                                                <?php echo htmlspecialchars($adv['manifest_number'] ?: 'S/N'); ?>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <div class="font-bold text-gray-900">
                                                <?php echo htmlspecialchars($adv['placa']); ?>
                                            </div>
                                            <div class="text-xs">
                                                <?php echo htmlspecialchars($adv['driver_name']); ?>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-right text-blue-600">
                                            <?php echo formatCurrency($adv['advance_manifest']); ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-right text-purple-600">
                                            <?php echo formatCurrency($adv['advance_owner']); ?>
                                        </td>
                                        <td
                                            class="whitespace-nowrap px-3 py-4 text-sm text-right font-black text-gray-900 bg-gray-50">
                                            <?php echo formatCurrency($total); ?>
                                        </td>
                                        <td
                                            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <a href="trip_details.php?id=<?php echo $adv['id']; ?>"
                                                class="text-brand-600 hover:text-brand-900">
                                                Ver Detalle
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <?php
                            $sumMan = array_sum(array_column($advances, 'advance_manifest'));
                            $sumOwn = array_sum(array_column($advances, 'advance_owner'));
                            $sumTotal = $sumMan + $sumOwn;
                            ?>
                            <tr>
                                <td colspan="3"
                                    class="px-6 py-4 text-sm text-gray-900 text-right uppercase tracking-wider">Totales:
                                </td>
                                <td class="px-3 py-4 text-sm text-right text-blue-700">
                                    <?php echo formatCurrency($sumMan); ?>
                                </td>
                                <td class="px-3 py-4 text-sm text-right text-purple-700">
                                    <?php echo formatCurrency($sumOwn); ?>
                                </td>
                                <td class="px-3 py-4 text-sm text-right text-gray-900 bg-gray-100">
                                    <?php echo formatCurrency($sumTotal); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>