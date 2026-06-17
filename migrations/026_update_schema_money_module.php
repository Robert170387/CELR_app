<?php
require_once 'includes/db.php';

try {
    // 1. Update expenses table for photo uploads
    $pdo->exec("ALTER TABLE expenses ADD COLUMN IF NOT EXISTS receipt_photo VARCHAR(255) NULL AFTER description;");
    echo "Added receipt_photo to expenses.<br>";

    // 2. Update trips table for settlement status
    $pdo->exec("ALTER TABLE trips ADD COLUMN IF NOT EXISTS settlement_status ENUM('Pending', 'Settled') DEFAULT 'Pending' AFTER final_pay_received;");
    $pdo->exec("ALTER TABLE trips ADD COLUMN IF NOT EXISTS settlement_notes TEXT NULL AFTER settlement_status;");
    echo "Added settlement fields to trips.<br>";

    // 3. Create upload directory
    if (!file_exists('uploads/receipts')) {
        mkdir('uploads/receipts', 0777, true);
        echo "Created uploads/receipts directory.<br>";
    }

    echo "Schema update completed successfully!";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>