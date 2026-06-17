<?php
include 'includes/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM trip_payments LIKE 'payment_concept'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE trip_payments ADD COLUMN payment_concept VARCHAR(50) DEFAULT 'Abono' AFTER amount");
        echo "Column 'payment_concept' added to 'trip_payments'.<br>";
    } else {
        echo "Column 'payment_concept' already exists.<br>";
    }

    echo "Schema update completed.";
} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>