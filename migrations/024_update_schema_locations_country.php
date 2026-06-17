<?php
require_once 'includes/db.php';

echo "Updating tables to support location cascading (Country, Department, City)...\n";

try {
    // 1. Suppliers: Add country
    $pdo->exec("ALTER TABLE suppliers ADD COLUMN country VARCHAR(100) DEFAULT 'Colombia' AFTER address");
    echo "Added 'country' to 'suppliers'.\n";
} catch (PDOException $e) {
    echo "Suppliers: " . $e->getMessage() . "\n";
}

try {
    // 2. Personnel: Add country
    $pdo->exec("ALTER TABLE personnel ADD COLUMN country VARCHAR(100) DEFAULT 'Colombia' AFTER address");
    echo "Added 'country' to 'personnel'.\n";
} catch (PDOException $e) {
    echo "Personnel: " . $e->getMessage() . "\n";
}

try {
    // 3. Vehicles: Add country and department (it only had register_city)
    // Checking if register_city exists, it does.
    try {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN register_country VARCHAR(100) DEFAULT 'Colombia' AFTER register_city");
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN register_department VARCHAR(100) DEFAULT NULL AFTER register_country");
        echo "Added 'register_country', 'register_department' to 'vehicles'.\n";
    } catch (PDOException $e) {
        echo "Vehicles columns likely exist or error: " . $e->getMessage() . "\n";
    }
} catch (PDOException $e) {
    echo "Vehicles: " . $e->getMessage() . "\n";
}

echo "Migration attempted.\n";
?>