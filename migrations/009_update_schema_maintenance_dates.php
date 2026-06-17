<?php
// migrations/009_update_schema_maintenance_dates.php
require_once 'includes/db.php';

try {
    echo "Adding date-based columns to maintenance_schedules...\n";
    $pdo->exec("ALTER TABLE maintenance_schedules ADD COLUMN interval_days INT DEFAULT NULL AFTER interval_kms");
    $pdo->exec("ALTER TABLE maintenance_schedules ADD COLUMN last_service_date DATE DEFAULT NULL AFTER last_service_kms");
    $pdo->exec("ALTER TABLE maintenance_schedules ADD COLUMN next_service_date DATE DEFAULT NULL AFTER next_service_kms");
    $pdo->exec("ALTER TABLE maintenance_schedules ADD COLUMN warning_margin_days INT DEFAULT 7 AFTER warning_margin_kms");

    // Index
    $pdo->exec("CREATE INDEX idx_maint_next_date ON maintenance_schedules(next_service_date)");

    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>