<?php
require_once 'includes/db.php';

try {
    echo "Updating personnel types...\n";
    $pdo->exec("ALTER TABLE personnel MODIFY COLUMN type ENUM('Administrativo', 'Conductor', 'Socio / Propietario') DEFAULT 'Conductor'");
    echo "- Added 'Socio / Propietario' to personnel types.\n";

    echo "Updating vehicles table for ownership...\n";
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN ownership_type ENUM('Propio', 'Tercero', 'Socio') DEFAULT 'Propio' AFTER placa");
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN partner_id INT NULL AFTER ownership_type");
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN partner_percentage DECIMAL(5,2) DEFAULT 0 AFTER partner_id");

    // Add foreign key for partner
    $pdo->exec("ALTER TABLE vehicles ADD CONSTRAINT fk_vehicle_partner FOREIGN KEY (partner_id) REFERENCES personnel(id)");

    echo "- Ownership columns added to vehicles.\n";
    echo "Migration completed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>