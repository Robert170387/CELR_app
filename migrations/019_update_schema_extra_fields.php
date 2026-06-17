<?php
include 'includes/db.php';

try {
    // 1. Add fields to Vehicles
    $cols = [
        "ADD COLUMN category VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN model_year VARCHAR(10) DEFAULT NULL",
        "ADD COLUMN color VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN serial_number VARCHAR(100) DEFAULT NULL",
        "ADD COLUMN motor_number VARCHAR(100) DEFAULT NULL",
        "ADD COLUMN chasis_number VARCHAR(100) DEFAULT NULL",
        "ADD COLUMN displacement VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN max_load VARCHAR(50) DEFAULT NULL",
        "ADD COLUMN register_city VARCHAR(100) DEFAULT NULL"
    ];

    foreach ($cols as $col) {
        try {
            $pdo->exec("ALTER TABLE vehicles $col");
            echo "Added column to vehicles: $col <br>";
        } catch (PDOException $e) {
            // Ignore if column exists
            echo "Column likely exists or error: " . $e->getMessage() . "<br>";
        }
    }

    // 2. Add fields to Drivers
    $colsDriver = [
        "ADD COLUMN address VARCHAR(255) DEFAULT NULL",
        "ADD COLUMN city_residence VARCHAR(100) DEFAULT NULL",
        "ADD COLUMN dob DATE DEFAULT NULL",
        "ADD COLUMN bank_account VARCHAR(100) DEFAULT NULL"
    ];

    foreach ($colsDriver as $col) {
        try {
            $pdo->exec("ALTER TABLE drivers $col");
            echo "Added column to drivers: $col <br>";
        } catch (PDOException $e) {
            echo "Column likely exists or error: " . $e->getMessage() . "<br>";
        }
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>