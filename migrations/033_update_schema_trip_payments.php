<?php
include 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS trip_payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        trip_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        payment_date DATE NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'Transferencia',
        reference VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    )");
    echo "Table 'trip_payments' created successfully.<br>";

    // Ensure final_pay_received exists in trips (it should, but just in case)
    // We will use this column as a cache sum.

    echo "Schema update completed successfully.";
} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>