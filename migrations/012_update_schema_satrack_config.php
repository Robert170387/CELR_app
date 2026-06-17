<?php
// migrations/012_update_schema_satrack_config.php
require_once 'includes/db.php';

try {
    echo "Updating config table for SATRACK Global Credentials...\n";

    // Add SATRACK configuration columns
    $pdo->exec("ALTER TABLE config ADD COLUMN satrack_api_key VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE config ADD COLUMN satrack_token VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE config ADD COLUMN satrack_api_url VARCHAR(255) DEFAULT 'https://api.satrack.com/v1/locations'");

    echo "SATRACK configuration columns added successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>