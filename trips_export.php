<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/SimpleXLSXGen.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

// Fetch all trips with related names
$sql = "SELECT t.*, v.placa, CONCAT(p.firstname, ' ', IFNULL(p.lastname, '')) as driver_name,
               m.name as material_name, mc.name as manifest_company_name,
               CASE 
                   WHEN c.person_type = 'Jurídica' THEN c.business_name
                   ELSE CONCAT(c.firstname, ' ', c.lastname1)
               END as client_name
        FROM trips t 
        LEFT JOIN vehicles v ON t.vehicle_id = v.id 
        LEFT JOIN personnel p ON t.driver_id = p.id 
        LEFT JOIN materials m ON t.material_id = m.id
        LEFT JOIN clients c ON t.client_id = c.id
        LEFT JOIN manifest_companies mc ON t.manifest_company_id = mc.id
        ORDER BY t.date_load DESC";

$stmt = $pdo->query($sql);

$data = [];
// Column Headers
$data[] = [
    'ID ODT',
    'Fecha Carga',
    'Tipo Viaje',
    'Vehículo (Placa)',
    'Conductor',
    'Cliente',
    'Material',
    'Origen',
    'Destino',
    'Nro Manifiesto',
    'Empresa Manifiesto',
    'Flete Bruto',
    'Retenciones',
    'Flete Neto',
    'Anticipo Manifiesto',
    'Anticipo Trans',
    'Comisión Valor',
    'Estado'
];

while ($row = $stmt->fetch()) {
    $data[] = [
        $row['id'],
        $row['date_load'],
        $row['trip_type'],
        $row['placa'],
        $row['driver_name'],
        $row['client_name'],
        $row['material_name'],
        $row['origin'],
        $row['destination'],
        $row['manifest_number'],
        $row['manifest_company_name'] ?: $row['manifest_company'],
        (float) $row['flete_bruto'],
        (float) $row['total_deductibles'],
        (float) $row['flete_neto'],
        (float) $row['advance_manifest'],
        (float) $row['advance_owner'],
        (float) $row['commission_value'],
        $row['status']
    ];
}

$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
$filename = "viajes_export_" . date('Ymd_His') . ".xlsx";

$xlsx->downloadAs($filename);
exit;
