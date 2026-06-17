<?php
include 'includes/db.php';

try {
    // Add default_driver_id to vehicles
    $cols = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'default_driver_id'");
    if (!$cols->fetch()) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN default_driver_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE vehicles ADD CONSTRAINT fk_vehicle_driver FOREIGN KEY (default_driver_id) REFERENCES personnel(id) ON DELETE SET NULL");
        echo "Added default_driver_id to vehicles table.<br>";
    }

    // Add manifest_file to trips
    $colsTrips = $pdo->query("SHOW COLUMNS FROM trips LIKE 'manifest_file'");
    if (!$colsTrips->fetch()) {
        $pdo->exec("ALTER TABLE trips ADD COLUMN manifest_file VARCHAR(255) DEFAULT NULL");
        echo "Added manifest_file to trips table.<br>";
    }

    echo "Schema update completed.";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>