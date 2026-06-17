<?php
// migrations/008_update_schema_epod.php
require_once 'includes/db.php';

try {
    echo "Adding 'delivery_proof_url' column to trips table...\n";
    $pdo->exec("ALTER TABLE trips ADD COLUMN delivery_proof_url VARCHAR(255) DEFAULT NULL AFTER status");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Note: Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>