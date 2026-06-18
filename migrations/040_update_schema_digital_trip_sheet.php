<?php
require_once 'includes/db.php';

try {
    // Add tank_mileage to expenses for fuel vouchers
    $cols = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'tank_mileage'")->fetchAll(); if (!$cols) $pdo->exec("ALTER TABLE expenses ADD COLUMN tank_mileage DECIMAL(10,2) DEFAULT NULL");
    echo "Column 'tank_mileage' added to 'expenses' table.<br>";

} catch (PDOException $e) {
    echo "Info: 'tank_mileage' already exists or error: " . $e->getMessage() . "<br>";
}

try {
    // Ensure trips has manifest_date
    $cols = $pdo->query("SHOW COLUMNS FROM trips LIKE 'manifest_date'")->fetchAll(); if (!$cols) $pdo->exec("ALTER TABLE trips ADD COLUMN manifest_date DATE DEFAULT NULL");
    echo "Column 'manifest_date' added to 'trips' table.<br>";
} catch (PDOException $e) {
    echo "Info: 'manifest_date' already exists or error: " . $e->getMessage() . "<br>";
}
