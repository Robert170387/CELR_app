<?php
// migrations/010_update_schema_users_access.php
require_once 'includes/db.php';

try {
    echo "Updating users table for External Access (Clients/Providers)...\n";

    // Check if columns exist (simple way or just try adding)
    // We'll just try adding via exec

    // Add related_client_id
    $pdo->exec("ALTER TABLE users ADD COLUMN related_client_id INT DEFAULT NULL AFTER role");
    $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_user_client FOREIGN KEY (related_client_id) REFERENCES clients(id) ON DELETE SET NULL");

    // Add related_personnel_id (for Drivers/Providers)
    $pdo->exec("ALTER TABLE users ADD COLUMN related_personnel_id INT DEFAULT NULL AFTER related_client_id");
    $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_user_personnel FOREIGN KEY (related_personnel_id) REFERENCES personnel(id) ON DELETE SET NULL");

    echo "Columns related_client_id and related_personnel_id added successfully.\n";

} catch (PDOException $e) {
    echo "Error (might be harmless if columns exist): " . $e->getMessage() . "\n";
}
?>