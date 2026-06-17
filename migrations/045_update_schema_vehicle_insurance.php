<?php
/**
 * Migration: Add todo_riesgo insurance fields to vehicles
 */
require_once __DIR__ . '/../includes/db.php';
echo "Adding Todo Riesgo insurance columns to vehicles...\n";

try {
    // Add expiry_todo_riesgo if not exists
    if (!$pdo->query("SHOW COLUMNS FROM vehicles LIKE 'expiry_todo_riesgo'")->fetchColumn()) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN expiry_todo_riesgo DATE NULL AFTER expiry_policy");
        echo " - Added column: expiry_todo_riesgo\n";
    }

    // Add policy_number_todo_riesgo if not exists
    if (!$pdo->query("SHOW COLUMNS FROM vehicles LIKE 'policy_number_todo_riesgo'")->fetchColumn()) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN policy_number_todo_riesgo VARCHAR(50) NULL AFTER expiry_todo_riesgo");
        echo " - Added column: policy_number_todo_riesgo\n";
    }

    echo "✓ Vehicles insurance schema updated.\n";
} catch (PDOException $e) {
    echo "x Error updating vehicles schema: " . $e->getMessage() . "\n";
}
?>
