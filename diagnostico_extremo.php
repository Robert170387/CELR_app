<?php
/**
 * Diagnóstico Extremo - Verificar qué está pasando realmente
 */

require_once 'includes/db.php';

$tripId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Extremo - Viaje #$tripId</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .error { color: red; font-weight: bold; }
        .ok { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Extremo - Viaje #$tripId</h1>
";

// 1. Obtener datos del viaje
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
$stmt->execute([$tripId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<div class='box'>";
if (!$trip) {
    echo "<p class='error'>❌ VIAJE #$tripId NO EXISTE EN LA BASE DE DATOS</p>";
} else {
    echo "<p class='ok'>✅ VIAJE #$tripId ENCONTRADO</p>";
    echo "<h3>Datos del Viaje:</h3>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th><th>Estado</th></tr>";
    
    $criticalFields = [
        'id', 'origin', 'destination', 
        'date_load', 'date_unload',
        'kms_start', 'kms_end',
        'flete_bruto', 'weight_declared', 'weight_origin', 'weight_dest',
        'manifest_number',
        'vehicle_id', 'driver_id', 'client_id',
        'origin_state_id', 'origin_city_id',
        'destination_state_id', 'destination_city_id'
    ];
    
    foreach ($criticalFields as $field) {
        $value = $trip[$field] ?? 'NULL';
        $status = ($value !== 'NULL' && $value !== '' && $value !== 0) ? 
                  '<span class="ok">✓ OK</span>' : 
                  '<span class="warning">⚠ Vacío</span>';
        echo "<tr><td><strong>$field</strong></td><td>$value</td><td>$status</td></tr>";
    }
    echo "</table>";
}
echo "</div>";

// 2. Verificar relaciones
if ($trip) {
    echo "<div class='box'>";
    echo "<h3>Verificación de Relaciones:</h3>";
    
    // Vehículo
    if ($trip['vehicle_id']) {
        $v = $pdo->query("SELECT placa, brand FROM vehicles WHERE id = {$trip['vehicle_id']}")->fetch();
        if ($v) {
            echo "<p class='ok'>✅ Vehículo: {$v['placa']} - {$v['brand']}</p>";
        } else {
            echo "<p class='error'>❌ Vehículo ID {$trip['vehicle_id']} NO ENCONTRADO</p>";
        }
    }
    
    // Conductor
    if ($trip['driver_id']) {
        $d = $pdo->query("SELECT firstname, lastname FROM personnel WHERE id = {$trip['driver_id']}")->fetch();
        if ($d) {
            echo "<p class='ok'>✅ Conductor: {$d['firstname']} {$d['lastname']}</p>";
        } else {
            echo "<p class='error'>❌ Conductor ID {$trip['driver_id']} NO ENCONTRADO</p>";
        }
    }
    
    // Cliente
    if ($trip['client_id']) {
        $c = $pdo->query("SELECT business_name, firstname, lastname1, person_type FROM clients WHERE id = {$trip['client_id']}")->fetch();
        if ($c) {
            $name = ($c['person_type'] == 'Juridica') ? $c['business_name'] : $c['firstname'] . ' ' . $c['lastname1'];
            echo "<p class='ok'>✅ Cliente: $name</p>";
        } else {
            echo "<p class='error'>❌ Cliente ID {$trip['client_id']} NO ENCONTRADO</p>";
        }
    }
    
    // Ubicaciones
    if ($trip['origin_state_id']) {
        $s = $pdo->query("SELECT name FROM loc_states WHERE id = {$trip['origin_state_id']}")->fetch();
        echo "<p class='ok'>✅ Origen Depto: " . ($s['name'] ?? 'NO ENCONTRADO') . "</p>";
    }
    if ($trip['origin_city_id']) {
        $c = $pdo->query("SELECT name FROM loc_cities WHERE id = {$trip['origin_city_id']}")->fetch();
        echo "<p class='ok'>✅ Origen Ciudad: " . ($c['name'] ?? 'NO ENCONTRADO') . "</p>";
    }
    
    echo "</div>";
}

// 3. Verificar archivos
echo "<div class='box'>";
echo "<h3>Verificación de Archivos:</h3>";

$files = [
    'trip_create.php',
    'trip_edit_simple.php',
    'trip_details.php',
    'js/location_selector.js',
    'includes/functions.php',
    'save_trip.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $mtime = date('Y-m-d H:i:s', filemtime($file));
        echo "<p class='ok'>✅ $file existe ($size bytes) - Modificado: $mtime</p>";
    } else {
        echo "<p class='error'>❌ $file NO EXISTE</p>";
    }
}
echo "</div>";

// 4. Instrucciones
echo "<div class='box' style='background: #fff3cd;'>";
echo "<h3>🧪 Prueba de Funcionamiento:</h3>";
echo "<p><strong>Pasos para verificar:</strong></p>";
echo "<ol>";
echo "<li>Abre este enlace en una pestaña nueva: <a href='trip_edit_simple.php?id=$tripId' target='_blank'>Abrir Editor Simplificado</a></li>";
echo "<li>Presiona <strong>F12</strong> para abrir DevTools</li>";
echo "<li>Ve a la pestaña <strong>Console</strong></li>";
echo "<li>Recarga la página con <strong>Ctrl+F5</strong></li>";
echo "<li>Mira si hay errores rojos en la consola</li>";
echo "<li>Ve a la pestaña <strong>Network</strong> y verifica que no haya errores 404</li>";
echo "</ol>";
echo "<p><strong>Si los datos arriba muestran valores vacíos (NULL), el problema es que el viaje realmente no tiene esos datos guardados.</strong></p>";
echo "</div>";

// 5. Enlaces de acción
echo "<div class='box'>";
echo "<h3>🔗 Enlaces de Acción:</h3>";
echo "<p><a href='trip_edit_simple.php?id=$tripId' style='display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 5px;'>📝 Editar Viaje (Versión Simple)</a></p>";
echo "<p><a href='trip_create.php?edit=$tripId' style='display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin: 5px;'>📝 Editar Viaje (Versión Original)</a></p>";
echo "<p><a href='trip_details.php?id=$tripId' style='display: inline-block; padding: 10px 20px; background: #FF9800; color: white; text-decoration: none; border-radius: 4px; margin: 5px;'>👁️ Ver Detalles del Viaje</a></p>";
echo "</div>";

echo "</body></html>";
