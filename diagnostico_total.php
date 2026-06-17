<?php
/**
 * DIAGNÓSTICO EXTREMO DEL SISTEMA
 * Verifica todo lo que pueda estar causando el problema
 */

// Mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>DIAGNÓSTICO EXTREMO - CELR-App</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .error { color: red; font-weight: bold; }
        .ok { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .critical { background: #ffebee; border: 2px solid #f44336; padding: 15px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .code { font-family: monospace; background: #fff3cd; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>🚨 DIAGNÓSTICO EXTREMO DEL SISTEMA</h1>
";

$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 1;

// 1. INFORMACIÓN DEL SERVIDOR
echo "<div class='box'>";
echo "<h2>1. Información del Servidor</h2>";
echo "<table>";
echo "<tr><th>Parámetro</th><th>Valor</th></tr>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td>Current File</td><td>" . __FILE__ . "</td></tr>";
echo "<tr><td>Current Dir</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>Request URI</td><td>" . $_SERVER['REQUEST_URI'] . "</td></tr>";
echo "</table>";
echo "</div>";

// 2. VERIFICAR ARCHIVOS CRÍTICOS
echo "<div class='box'>";
echo "<h2>2. Verificación de Archivos Críticos</h2>";

$filesToCheck = [
    'trip_create.php' => 'Editor Original',
    'trip_edit_simple.php' => 'Editor Simple',
    'edit_trip_basic.php' => 'Editor Ultra Básico',
    'trip_details.php' => 'Detalles del Viaje',
    'save_trip.php' => 'Guardar Viaje',
    'includes/db.php' => 'Conexión BD',
    'includes/functions.php' => 'Funciones',
    'includes/header.php' => 'Header',
    'js/location_selector.js' => 'Location Selector JS',
];

echo "<table>";
echo "<tr><th>Archivo</th><th>Existe</th><th>Tamaño</th><th>Modificado</th><th>Permisos</th><th>Estado</th></tr>";

foreach ($filesToCheck as $file => $description) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    $mtime = $exists ? date('Y-m-d H:i:s', filemtime($file)) : '-';
    $perms = $exists ? substr(sprintf('%o', fileperms($file)), -4) : '-';
    $readable = $exists ? (is_readable($file) ? '✓ Legible' : '✗ No legible') : '-';
    
    $statusClass = $exists ? 'ok' : 'error';
    $status = $exists ? '✅ EXISTE' : '❌ NO EXISTE';
    
    echo "<tr>";
    echo "<td><strong>$file</strong><br><small>$description</small></td>";
    echo "<td class='$statusClass'>$status</td>";
    echo "<td>$size bytes</td>";
    echo "<td>$mtime</td>";
    echo "<td>$perms</td>";
    echo "<td>$readable</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 3. VERIFICAR BASE DE DATOS
echo "<div class='box'>";
echo "<h2>3. Verificación de Base de Datos</h2>";

try {
    require_once 'includes/db.php';
    echo "<p class='ok'>✅ Conexión a BD establecida</p>";
    
    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = ['trips', 'vehicles', 'personnel', 'clients', 'config', 'loc_states', 'loc_cities'];
    
    echo "<table>";
    echo "<tr><th>Tabla Requerida</th><th>Existe</th><th>Registros</th></tr>";
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $tables);
        $count = $exists ? $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn() : 0;
        $status = $exists ? "<span class='ok'>✅ SÍ ($count registros)</span>" : "<span class='error'>❌ NO</span>";
        echo "<tr><td>$table</td><td>$status</td><td>$count</td></tr>";
    }
    echo "</table>";
    
    // Verificar viaje específico
    echo "<h3>Datos del Viaje #$tripId:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trip) {
        echo "<p class='ok'>✅ Viaje encontrado</p>";
        echo "<pre>";
        print_r($trip);
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ Viaje #$tripId NO EXISTE</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR DE BD: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. PROBLEMAS COMUNES
echo "<div class='box'>";
echo "<h2>4. Problemas Comunes y Soluciones</h2>";

echo "<div class='critical'>";
echo "<h3>🔴 SI LOS ARCHIVOS PHP NO MUESTRAN LOS DATOS:</h3>";
echo "<ol>";
echo "<li><strong>Verificar que el archivo existe:</strong><br>";
echo "Abre: <code>http://localhost/CELR_app/edit_trip_basic.php</code><br>";
echo "Si da error 404, el archivo no existe o está en otra carpeta.</li>";
echo "<br>";
echo "<li><strong>Limpiar caché del navegador:</strong><br>";
echo "Presiona: <code>Ctrl + Shift + Delete</code> y borra TODO.<br>";
echo "O usa modo incógnito: <code>Ctrl + Shift + N</code></li>";
echo "<br>";
echo "<li><strong>Verificar error_log de PHP:</strong><br>";
echo "Busca el archivo: <code>C:\xampp\php\logs\php_error_log</code><br>";
echo "O revisa: <code>C:\xampp\apache\logs\error.log</code></li>";
echo "<br>";
echo "<li><strong>Reiniciar Apache:</strong><br>";
echo "Abre XAMPP Control Panel y reinicia Apache.</li>";
echo "<br>";
echo "<li><strong>Verificar que la ruta es correcta:</strong><br>";
echo "Tu archivo debería estar en: <code>C:\xampp\htdocs\CELR_app\</code></li>";
echo "</ol>";
echo "</div>";

echo "<h3>🟡 Solución Temporal - Bypass Total:</h3>";
echo "<p>Si nada funciona, usemos este enfoque:</p>";
echo "<ol>";
echo "<li>Crear un archivo HTML estático con los datos embebidos</li>";
echo "<li>Usar JavaScript puro (sin frameworks) para el comportamiento dinámico</li>";
echo "<li>Postear a save_trip.php mediante form tradicional</li>";
echo "</ol>";
echo "</div>";

// 5. ACCIONES INMEDIATAS
echo "<div class='box'>";
echo "<h2>5. Acciones Inmediatas</h2>";

echo "<p><strong>Prueba A - Verificar que el archivo se ejecuta:</strong></p>";
echo "<ol>";
echo "<li>Abre: <a href='edit_trip_basic.php?id=1' target='_blank'>edit_trip_basic.php?id=1</a></li>";
echo "<li>Presiona F12 → Console</li>";
echo "<li>¿Hay errores rojos?</li>";
echo "<li>¿La página carga en blanco o con contenido?</li>";
echo "</ol>";

echo "<p><strong>Prueba B - Ver caché:</strong></p>";
echo "<ol>";
echo "<li>Abre en modo incógnito: <code>Ctrl + Shift + N</code></li>";
echo "<li>Navega a: <a href='edit_trip_basic.php?id=1' target='_blank'>edit_trip_basic.php?id=1</a></li>";
echo "<li>¿Ahora sí muestra los datos?</li>";
echo "</ol>";

echo "<p><strong>Prueba C - Verificar contenido del archivo:</strong></p>";
echo "<ol>";
echo "<li>Abre: <a href='edit_trip_basic.php' target='_blank'>Ver código fuente del archivo</a></li>";
echo "<li>¿Ves el código PHP o solo HTML?</li>";
echo "<li>Si ves PHP sin ejecutar, hay problema con el servidor</li>";
echo "</ol>";

echo "</div>";

// 6. LINKS DIRECTOS
echo "<div class='box'>";
echo "<h2>6. Links Directos de Prueba</h2>";
echo "<ul>";
echo "<li><a href='trip_details.php?id=$tripId' target='_blank'>📄 Ver Detalles (debería funcionar)</a></li>";
echo "<li><a href='trip_create.php?edit=$tripId' target='_blank'>📝 Editar Original (probablemente falla)</a></li>";
echo "<li><a href='edit_trip_basic.php?id=$tripId' target='_blank'>📝 Editar Básico (prueba crítica)</a></li>";
echo "<li><a href='debug_form_load.php?edit=$tripId' target='_blank'>🔍 Debug Form Load</a></li>";
echo "<li><a href='test_trip_edit.php?trip_id=$tripId' target='_blank'>📊 Test BD</a></li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
