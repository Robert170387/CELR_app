<?php
/**
 * Migration: Add location IDs to expenses for EDS (Fuel Stations)
 */
require_once __DIR__ . '/../includes/db.php';
echo "Adding EDS location columns to expenses...\n";

try {
    // Add eds_state_id if not exists
    if (!$pdo->query("SHOW COLUMNS FROM expenses LIKE 'eds_state_id'")->fetchColumn()) {
        $pdo->exec("ALTER TABLE expenses ADD COLUMN eds_state_id INT NULL AFTER eds_location");
        echo " - Added column: eds_state_id\n";
    }

    // Add eds_city_id if not exists
    if (!$pdo->query("SHOW COLUMNS FROM expenses LIKE 'eds_city_id'")->fetchColumn()) {
        $pdo->exec("ALTER TABLE expenses ADD COLUMN eds_city_id INT NULL AFTER eds_state_id");
        echo " - Added column: eds_city_id\n";
    }

    // Add indexes if not exist
    try {
        $pdo->exec("ALTER TABLE expenses ADD INDEX idx_expense_eds_city (eds_city_id)");
        echo " - Added index: idx_expense_eds_city\n";
    } catch (Exception $e) {
        // Index might already exist
    }

    // Log migration removed (handled by MigrationManager)

    echo "✓ Expenses location schema updated.\n";
} catch (PDOException $e) {
    echo "x Error updating expenses schema: " . $e->getMessage() . "\n";
}
?>