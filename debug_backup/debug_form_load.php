<?php
require_once __DIR__ . '/includes/security_utils.php';
requireInternalToolAccess();

/**
 * Diagnóstico de Carga de Formulario de Edición
 * Muestra exactamente qué está pasando cuando se carga el formulario
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

$tripId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Edición - Viaje #$tripId</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .error { color: red; }
        .ok { color: green; }
        .debug { background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de Edición de Viaje</h1>
";

// PASO 1: Verificar parámetro
if (!$tripId) {
    echo "<div class='box'>";
    echo "<h3>❌ ERROR: No se recibió ID del viaje</h3>";
    echo "<p>Parámetros recibidos:</p>";
    echo "<pre>";
    print_r($_GET);
    echo "</pre>";
    echo "<p class='error'>Debes acceder con: ?edit=1 o ?id=1</p>";
    echo "</div>";
    exit;
}

echo "<div class='box'>";
echo "<h3>✅ PASO 1: Parámetro recibido</h3>";
echo "<p>ID del viaje a editar: <strong>$tripId</strong></p>";
echo "</div>";

// PASO 2: Intentar cargar el viaje
echo "<div class='box'>";
echo "<h3>🔍 PASO 2: Cargando viaje desde BD...</h3>";

try {
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        echo "<p class='error'>❌ Viaje #$tripId NO ENCONTRADO en la BD</p>";
        echo "<p>Query ejecutada: SELECT * FROM trips WHERE id = $tripId</p>";
        echo "<p>Filas afectadas: " . $stmt->rowCount() . "</p>";
    } else {
        echo "<p class='ok'>✅ Viaje cargado exitosamente</p>";
        echo "<div class='debug'>";
        echo "<p><strong>Datos completos del viaje:</strong></p>";
        echo "<pre>";
        print_r($trip);
        echo "</pre>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR al cargar: " . $e->getMessage() . "</p>";
}
echo "</div>";

if (!$trip) {
    echo "</body></html>";
    exit;
}

// PASO 3: Verificar campos críticos
echo "<div class='box'>";
echo "<h3>📋 PASO 3: Verificación de Campos Críticos</h3>";
echo "<table>";
echo "<tr><th>Campo</th><th>Valor en BD</th><th>Tipo</th><th>¿Vacío?</th></tr>";

$criticalFields = [
    'vehicle_id', 'driver_id', 'client_id',
    'date_load', 'date_unload',
    'kms_start', 'kms_end',
    'flete_bruto', 'weight_declared', 'weight_origin', 'weight_dest',
    'manifest_number',
    'origin', 'destination',
    'origin_state_id', 'origin_city_id',
    'destination_state_id', 'destination_city_id'
];

foreach ($criticalFields as $field) {
    $value = $trip[$field] ?? null;
    $type = gettype($value);
    $isEmpty = ($value === null || $value === '' || $value === 0);
    $status = $isEmpty ? '<span class="error">⚠ VACÍO</span>' : '<span class="ok">✓ OK</span>';
    $displayValue = $value === null ? 'NULL' : ($value === '' ? '(string vacío)' : $value);
    
    echo "<tr>";
    echo "<td><strong>$field</strong></td>";
    echo "<td>$displayValue</td>";
    echo "<td>$type</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// PASO 4: Simular carga en el formulario
echo "<div class='box'>";
echo "<h3>🧪 PASO 4: Simulación de Carga en Formulario</h3>";

echo "<p>Valores que deberían aparecer en los campos del formulario:</p>";
echo "<table>";
echo "<tr><th>Campo del Formulario</th><th>Valor Esperado</th><th>Código PHP</th></tr>";

$formFields = [
    ['name' => 'vehicle_id', 'value' => $trip['vehicle_id'], 'code' => 'echo $trip["vehicle_id"];'],
    ['name' => 'driver_id', 'value' => $trip['driver_id'], 'code' => 'echo $trip["driver_id"];'],
    ['name' => 'client_id', 'value' => $trip['client_id'], 'code' => 'echo $trip["client_id"];'],
    ['name' => 'date_load', 'value' => $trip['date_load'], 'code' => 'echo $trip["date_load"];'],
    ['name' => 'date_unload', 'value' => $trip['date_unload'], 'code' => 'echo $trip["date_unload"];'],
    ['name' => 'kms_start', 'value' => $trip['kms_start'], 'code' => 'echo $trip["kms_start"];'],
    ['name' => 'kms_end', 'value' => $trip['kms_end'], 'code' => 'echo $trip["kms_end"];'],
    ['name' => 'flete_bruto', 'value' => $trip['flete_bruto'], 'code' => 'echo $trip["flete_bruto"];'],
    ['name' => 'manifest_number', 'value' => $trip['manifest_number'], 'code' => 'echo $trip["manifest_number"];'],
];

foreach ($formFields as $field) {
    $val = $field['value'] ?? 'NULL';
    echo "<tr>";
    echo "<td>{$field['name']}</td>";
    echo "<td><strong>$val</strong></td>";
    echo "<td><code>{$field['code']}</code></td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// PASO 5: Probar carga de selects
echo "<div class='box'>";
echo "<h3>🔽 PASO 5: Verificación de Opciones en Selects</h3>";

// Vehículos
$vehicles = $pdo->query("SELECT id, placa, brand FROM vehicles WHERE active=1 OR id = {$trip['vehicle_id']} ORDER BY placa ASC")->fetchAll();
echo "<p><strong>Vehículos disponibles:</strong> " . count($vehicles) . "</p>";
echo "<p>Vehículo seleccionado debería ser ID: {$trip['vehicle_id']}</p>";
$vehicleFound = false;
foreach ($vehicles as $v) {
    if ($v['id'] == $trip['vehicle_id']) {
        $vehicleFound = true;
        echo "<p class='ok'>✅ Vehículo encontrado: {$v['placa']} - {$v['brand']}</p>";
        break;
    }
}
if (!$vehicleFound) {
    echo "<p class='error'>❌ Vehículo ID {$trip['vehicle_id']} NO está en la lista</p>";
}

// Conductores
$drivers = $pdo->query("SELECT id, firstname, lastname FROM personnel WHERE type='Conductor' AND (active=1 OR id = {$trip['driver_id']}) ORDER BY firstname ASC")->fetchAll();
echo "<p><strong>Conductores disponibles:</strong> " . count($drivers) . "</p>";
echo "<p>Conductor seleccionado debería ser ID: {$trip['driver_id']}</p>";
$driverFound = false;
foreach ($drivers as $d) {
    if ($d['id'] == $trip['driver_id']) {
        $driverFound = true;
        echo "<p class='ok'>✅ Conductor encontrado: {$d['firstname']} {$d['lastname']}</p>";
        break;
    }
}
if (!$driverFound) {
    echo "<p class='error'>❌ Conductor ID {$trip['driver_id']} NO está en la lista</p>";
}
echo "</div>";

// PASO 6: HTML de prueba
echo "<div class='box'>";
echo "<h3>📝 PASO 6: HTML de Prueba</h3>";
echo "<p>A continuación se muestra cómo deberían verse los campos (sin Alpine.js):</p>";

echo "<div style='background: #f9f9f9; padding: 20px; border: 1px solid #ddd;'>";
echo "<h4>Información Básica:</h4>";
echo "<p><strong>Vehículo:</strong><br>";
echo "<select style='width: 300px; padding: 8px;'>";
echo "<option value=''>-- Seleccione --</option>";
foreach ($vehicles as $v) {
    $selected = ($v['id'] == $trip['vehicle_id']) ? 'selected' : '';
    echo "<option value='{$v['id']}' $selected>{$v['placa']} - {$v['brand']}</option>";
}
echo "</select></p>";

echo "<p><strong>Conductor:</strong><br>";
echo "<select style='width: 300px; padding: 8px;'>";
echo "<option value=''>-- Seleccione --</option>";
foreach ($drivers as $d) {
    $selected = ($d['id'] == $trip['driver_id']) ? 'selected' : '';
    echo "<option value='{$d['id']}' $selected>{$d['firstname']} {$d['lastname']}</option>";
}
echo "</select></p>";

echo "<p><strong>Fecha de Carga:</strong><br>";
echo "<input type='date' value='{$trip['date_load']}' style='width: 300px; padding: 8px;'></p>";

echo "<p><strong>Km Inicial:</strong><br>";
echo "<input type='number' value='{$trip['kms_start']}' style='width: 300px; padding: 8px;'></p>";

echo "<p><strong>Flete Bruto:</strong><br>";
echo "<input type='number' value='{$trip['flete_bruto']}' style='width: 300px; padding: 8px;'></p>";

echo "</div>";
echo "<p style='margin-top: 10px; color: #666;'>Si los campos arriba aparecen con valores, el problema es Alpine.js o la caché del navegador.</p>";
echo "</div>";

// Enlaces
echo "<div class='box'>";
echo "<h3>🔗 Enlaces de Acción</h3>";
echo "<p><a href='trip_edit_simple.php?id=$tripId' style='display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Abrir Editor Simplificado</a></p>";
echo "<p><a href='trip_create.php?edit=$tripId' style='display: inline-block; padding: 12px 24px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>Abrir Editor Original</a></p>";
echo "<p><a href='diagnostico_extremo.php?id=$tripId' style='display: inline-block; padding: 12px 24px; background: #FF9800; color: white; text-decoration: none; border-radius: 4px;'>Ver Diagnóstico Extremo</a></p>";
echo "</div>";

echo "</body></html>";
