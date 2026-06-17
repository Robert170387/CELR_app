<?php
require_once 'includes/db.php';

try {
    echo "Updating schema for system health features...<br>";

    // Add last_backup_at to config table
    $pdo->exec("ALTER TABLE config ADD COLUMN last_backup_at DATETIME NULL AFTER ganancia_urbano_percent");
    echo "Added 'last_backup_at' column to 'config' table.<br>";

    // Initialize last_backup_at with some value if not set
    $pdo->exec("UPDATE config SET last_backup_at = NOW() WHERE last_backup_at IS NULL");

    echo "<b style='color:green;'>Success: System health schema updated.</b>";

} catch (PDOException $e) {
    echo "<b style='color:red;'>Error updating schema:</b> " . $e->getMessage();
}
?>