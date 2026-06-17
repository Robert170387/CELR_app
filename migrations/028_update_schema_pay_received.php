<?php
include 'includes/db.php';

try {
    echo "Checking for 'final_pay_received' column in 'trips' table...<br>";

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM trips LIKE 'final_pay_received'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Column not found. Adding column...<br>";
        $pdo->exec("ALTER TABLE trips ADD COLUMN final_pay_received DECIMAL(15,2) DEFAULT 0 AFTER final_pay_expected");
        echo "Column 'final_pay_received' added successfully.<br>";
    } else {
        echo "Column 'final_pay_received' already exists.<br>";
    }

} catch (PDOException $e) {
    die("Error updating schema: " . $e->getMessage());
}
?>