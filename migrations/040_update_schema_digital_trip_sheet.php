<?php
require_once 'includes/db.php';

try {
    // Add tank_mileage to expenses for fuel vouchers
    $pdo->exec("ALTER TABLE expenses ADD COLUMN IF NOT EXISTS tank_mileage DECIMAL(10,2) DEFAULT NULL");
    echo "Column 'tank_mileage' added to 'expenses' table.<br>";

} catch (PDOException $e) {
    echo "Info: 'tank_mileage' already exists or error: " . $e->getMessage() . "<br>";
}

try {
    // Ensure trips has manifest_date
    $pdo->exec("ALTER TABLE trips ADD COLUMN IF NOT EXISTS manifest_date DATE DEFAULT NULL");
    echo "Column 'manifest_date' added to 'trips' table.<br>";
} catch (PDOException $e) {
    echo "Info: 'manifest_date' already exists or error: " . $e->getMessage() . "<br>";
}
