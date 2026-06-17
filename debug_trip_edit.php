<?php
/**
 * Script de diagnóstico para edición de viajes
 * Muestra exactamente qué datos están llegando al navegador
 */

require_once 'includes/db.php';

$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 1;

// Obtener datos del viaje
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
$stmt->execute([$tripId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Viaje no encontrado");
}

// Mostrar diagnóstico
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Viaje #<?php echo $tripId; ?></title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .error { color: red; }
        .ok { color: green; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Viaje #<?php echo $tripId; ?></h1>
    
    <div class="section">
        <h2>1. Datos PHP (Servidor)</h2>
        <pre><?php print_r($trip); ?></pre>
    </div>
    
    <div class="section">
        <h2>2. Valores que deberían llegar a JavaScript</h2>
        <table border="1" cellpadding="5">
            <tr><th>Campo</th><th>Valor PHP</th><th>Estado</th></tr>
            <?php
            $fields = [
                'vehicle_id', 'driver_id', 'client_id',
                'date_load', 'date_unload',
                'kms_start', 'kms_end',
                'flete_bruto', 'weight_declared', 'weight_origin', 'weight_dest',
                'manifest_number',
                'origin', 'origin_point_id', 'origin_state_id', 'origin_city_id',
                'destination', 'destination_point_id', 'destination_state_id', 'destination_city_id'
            ];
            foreach ($fields as $field) {
                $value = $trip[$field] ?? 'NULL';
                $status = ($value !== 'NULL' && $value !== '' && $value !== '0') ? '<span class="ok">✓ OK</span>' : '<span class="error">✗ Vacío/NULL</span>';
                echo "<tr><td>$field</td><td>$value</td><td>$status</td></tr>";
            }
            ?>
        </table>
    </div>
    
    <div class="section">
        <h2>3. Verificación de Archivos</h2>
        <?php
        $files = [
            'trip_create.php',
            'js/location_selector.js',
            'includes/functions.php',
            'app/Controllers/TripController.php'
        ];
        foreach ($files as $file) {
            if (file_exists($file)) {
                $mtime = filemtime($file);
                echo "<p class='ok'>✓ $file existe (modificado: " . date('Y-m-d H:i:s', $mtime) . ")</p>";
            } else {
                echo "<p class='error'>✗ $file NO EXISTE</p>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Enlaces de prueba</h2>
        <p><a href="trip_create.php?edit=<?php echo $tripId; ?>" target="_blank">Editar Viaje</a></p>
        <p><a href="trip_details.php?id=<?php echo $tripId; ?>" target="_blank">Ver Detalles</a></p>
        <p><a href="test_trip_edit.php?trip_id=<?php echo $tripId; ?>" target="_blank">Ver Datos BD</a></p>
    </div>
    
    <div class="section">
        <h2>5. Instrucciones para depurar en el navegador</h2>
        <ol>
            <li>Abre la página de edición del viaje</li>
            <li>Presiona F12 para abrir DevTools</li>
            <li>Ve a la pestaña "Console"</li>
            <li>Busca el mensaje "Edit Trip Data:"</li>
            <li>Expándelo y verifica que los datos coincidan con los de arriba</li>
            <li>Ve a la pestaña "Network" y verifica que no haya errores 404</li>
            <li>Recarga la página con Ctrl+F5 (para limpiar caché)</li>
        </ol>
    </div>
    
    <div class="section">
        <h2>6. Posibles causas del problema</h2>
        <ul>
            <li><strong>Caché del navegador:</strong> El navegador está mostrando una versión antigua del archivo</li>
            <li><strong>Alpine.js no carga:</strong> Revisa en Console si hay errores de Alpine</li>
            <li><strong>Valores nulos:</strong> Los campos pueden estar NULL en la BD</li>
            <li><strong>Conflicto x-model/value:</strong> Alpine.js puede estar sobreescribiendo los valores</li>
        </ul>
    </div>
</body>
</html>
