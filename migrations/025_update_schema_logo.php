<?php
include 'includes/db.php';

try {
    echo "Checking for 'logo_path' column in 'config' table...<br>";

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM config LIKE 'logo_path'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Column not found. Adding column...<br>";
        $pdo->exec("ALTER TABLE config ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL AFTER ganancia_urbano_percent");
        echo "Column 'logo_path' added successfully.<br>";
    } else {
        echo "Column 'logo_path' already exists.<br>";
    }

} catch (PDOException $e) {
    die("Error updating schema: " . $e->getMessage());
}
?>