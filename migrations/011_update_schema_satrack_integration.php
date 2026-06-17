<?php
// migrations/011_update_schema_satrack_integration.php
require_once 'includes/db.php';

try {
    echo "Updating vehicles table for SATRACK Integration...\n";

    // Add satrack_id column
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN satrack_id VARCHAR(100) DEFAULT NULL AFTER placa");

    echo "Column satrack_id added successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>