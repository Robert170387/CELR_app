<?php
require_once __DIR__ . '/includes/security_utils.php';
requireInternalToolAccess();

/**
 * DB_DIAG - Sistema de Diagnóstico de Salud de Base de Datos CELR-App
 * Verifica la existencia de tablas, columnas críticas y tipos de datos post-migración.
 */
include 'includes/db.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=UTF-8');
}

echo "==================================================\n";
echo "   CELR-APP: DIAGNÓSTICO DE SALUD DE BD\n";
echo "==================================================\n\n";

$tables_to_check = [
    'trips' => [
        'origin_state_id' => 'int',
        'destination_state_id' => 'int',
        'origin_city_id' => 'int',
        'destination_city_id' => 'int',
        'origin_point_id' => 'int',
        'destination_point_id' => 'int',
        'fuel_vouchers' => 'decimal', // Verificaremos si es columna o tabla
        'percent_iva' => 'decimal',
        'percent_rete_iva' => 'decimal'
    ],
    'expenses' => [
        'trip_id' => 'int',
        'vehicle_id' => 'int',
        'created_by' => 'int',
        'receipt_photo' => 'varchar'
    ],
    'audit_logs' => [
        'user_id' => 'int',
        'action' => 'varchar',
        'entity_type' => 'varchar'
    ]
];

$all_passed = true;

foreach ($tables_to_check as $table => $columns) {
    echo "TABLA: [$table]\n";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "  [FAIL] Tabla '$table' no existe.\n";
            $all_passed = false;
            continue;
        }

        $stmtCols = $pdo->query("DESCRIBE $table");
        $existing = [];
        while ($row = $stmtCols->fetch(PDO::FETCH_ASSOC)) {
            $existing[$row['Field']] = $row['Type'];
        }

        foreach ($columns as $col => $type) {
            if (isset($existing[$col])) {
                $actual = strtolower($existing[$col]);
                echo "  [OK] $col found ($actual)\n";
            } else {
                // Caso especial: fuel_vouchers puede ser una tabla independiente o columna
                if ($col === 'fuel_vouchers') {
                    $checkTable = $pdo->query("SHOW TABLES LIKE 'fuel_vouchers'")->rowCount();
                    if ($checkTable > 0) {
                        echo "  [OK] fuel_vouchers existe como TABLA independiente.\n";
                        continue;
                    }
                }
                echo "  [FAIL] Columna '$col' MISSING en '$table'.\n";
                $all_passed = false;
            }
        }
    } catch (PDOException $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
        $all_passed = false;
    }
    echo "\n";
}

// Verificar Tablas de Ubicaciones Jerárquicas
echo "SISTEMA DE UBICACIONES:\n";
$loc_tables = ['loc_countries', 'loc_states', 'loc_cities'];
foreach ($loc_tables as $lt) {
    $exists = $pdo->query("SHOW TABLES LIKE '$lt'")->rowCount();
    if ($exists) {
        $count = $pdo->query("SELECT COUNT(*) FROM $lt")->fetchColumn();
        echo "  [OK] $lt existe con $count registros.\n";
    } else {
        echo "  [FAIL] $lt no existe.\n";
        $all_passed = false;
    }
}

echo "\n==================================================\n";
echo "RESULTADO: " . ($all_passed ? "SALUDABLE ✅" : "PROBLEMAS DETECTADOS ❌") . "\n";
echo "==================================================\n";
?>
