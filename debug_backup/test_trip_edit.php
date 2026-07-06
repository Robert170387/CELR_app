<?php
require_once __DIR__ . '/includes/security_utils.php';
requireInternalToolAccess();

/**
 * Diagnóstico de Edición de Viajes
 * Ejecutar: http://localhost/CELR_app/test_trip_edit.php?trip_id=1
 */

require_once 'includes/db.php';

$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 1;

echo "<h2>🔍 Diagnóstico del Viaje #$tripId</h2>";

// 1. Verificar que el viaje existe
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
$stmt->execute([$tripId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("<p style='color:red'>❌ Viaje #$tripId no encontrado</p>");
}

echo "<h3>📊 Datos en Base de Datos:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Valor</th></tr>";
$fields = ['id', 'origin', 'origin_state_id', 'origin_city_id', 'origin_point_id', 
           'destination', 'destination_state_id', 'destination_city_id', 'destination_point_id',
           'vehicle_id', 'driver_id', 'client_id', 'date_load', 'status'];
foreach ($fields as $field) {
    $value = $trip[$field] ?? 'NULL';
    echo "<tr><td>$field</td><td>$value</td></tr>";
}
echo "</table>";

// 2. Verificar locations
$originPointId = $trip['origin_point_id'] ?? null;
$destPointId = $trip['destination_point_id'] ?? null;

echo "<h3>📍 Datos de Ubicaciones:</h3>";

if ($originPointId) {
    $loc = $pdo->query("SELECT l.*, s.name as state_name, c.name as city_name, co.name as country_name 
                       FROM locations l 
                       LEFT JOIN loc_states s ON l.state_id = s.id 
                       LEFT JOIN loc_cities c ON l.city_id = c.id 
                       LEFT JOIN loc_countries co ON l.country_id = co.id 
                       WHERE l.id = $originPointId")->fetch();
    if ($loc) {
        echo "<p><strong>Origen (Punto $originPointId):</strong><br>";
        echo "Nombre: {$loc['name']}<br>";
        echo "País: {$loc['country_name']}<br>";
        echo "Departamento: {$loc['state_name']}<br>";
        echo "Ciudad: {$loc['city_name']}</p>";
    }
}

if ($destPointId) {
    $loc = $pdo->query("SELECT l.*, s.name as state_name, c.name as city_name, co.name as country_name 
                       FROM locations l 
                       LEFT JOIN loc_states s ON l.state_id = s.id 
                       LEFT JOIN loc_cities c ON l.city_id = c.id 
                       LEFT JOIN loc_countries co ON l.country_id = co.id 
                       WHERE l.id = $destPointId")->fetch();
    if ($loc) {
        echo "<p><strong>Destino (Punto $destPointId):</strong><br>";
        echo "Nombre: {$loc['name']}<br>";
        echo "País: {$loc['country_name']}<br>";
        echo "Departamento: {$loc['state_name']}<br>";
        echo "Ciudad: {$loc['city_name']}</p>";
    }
}

// 3. Enlaces de prueba
echo "<h3>🔗 Enlaces de Prueba:</h3>";
echo "<p><a href='trip_create.php?edit=$tripId' target='_blank'>📝 Editar Viaje #$tripId</a></p>";
echo "<p><a href='trip_details.php?id=$tripId' target='_blank'>📄 Ver Detalles del Viaje #$tripId</a></p>";

// 4. Verificar que los archivos tienen los cambios
echo "<h3>✅ Verificación de Archivos:</h3>";

// Verificar location_selector.js
$jsContent = file_get_contents('js/location_selector.js');
if (strpos($jsContent, '$nextTick') !== false) {
    echo "<p style='color:green'>✓ js/location_selector.js tiene $nextTick()</p>";
} else {
    echo "<p style='color:red'>✗ js/location_selector.js NO tiene $nextTick()</p>";
}

// Verificar trip_create.php
$phpContent = file_get_contents('trip_create.php');
if (strpos($phpContent, 'tripDataConfig') !== false) {
    echo "<p style='color:green'>✓ trip_create.php tiene tripDataConfig</p>";
} else {
    echo "<p style='color:red'>✗ trip_create.php NO tiene tripDataConfig correctamente</p>";
}

echo "<hr><p><strong>Instrucciones:</strong></p>";
echo "<ol>";
echo "<li>Abre el enlace 'Editar Viaje' en una pestaña nueva</li>";
echo "<li>Presiona F12 para abrir DevTools</li>";
echo "<li>Ve a la pestaña Console</li>";
echo "<li>Busca el mensaje: 'Edit Trip Data:'</li>";
echo "<li>Verifica que los datos coincidan con los mostrados arriba</li>";
echo "</ol>";

// 5. Lista de archivos modificados recientemente
echo "<h3>🕐 Archivos Modificados Recientemente:</h3>";
$files = [
    'trip_create.php',
    'js/location_selector.js',
    'js/form_validation.js'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Archivo</th><th>Última Modificación</th></tr>";
foreach ($files as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        echo "<tr><td>$file</td><td>" . date('Y-m-d H:i:s', $mtime) . "</td></tr>";
    }
}
echo "</table>";
