<?php
include 'includes/db.php';

try {
    // Vehicles
    $colsV = $pdo->query("SHOW COLUMNS FROM vehicles");
    $existingV = $colsV->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('expiry_soat', $existingV)) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN expiry_soat DATE DEFAULT NULL");
        echo "Added expiry_soat to vehicles.<br>";
    }
    if (!in_array('expiry_tecno', $existingV)) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN expiry_tecno DATE DEFAULT NULL");
        echo "Added expiry_tecno to vehicles.<br>";
    }
    if (!in_array('expiry_policy', $existingV)) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN expiry_policy DATE DEFAULT NULL");
        echo "Added expiry_policy to vehicles.<br>";
    }

    // Personnel
    $colsP = $pdo->query("SHOW COLUMNS FROM personnel");
    $existingP = $colsP->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('license_expiry', $existingP)) {
        $pdo->exec("ALTER TABLE personnel ADD COLUMN license_expiry DATE DEFAULT NULL");
        echo "Added license_expiry to personnel.<br>";
    }

    echo "Schema update for Alerts completed.";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>