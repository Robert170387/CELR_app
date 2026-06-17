<?php
require_once 'includes/db.php';

try {
    echo "Adding 'notes' column to settlements table...\n";
    $pdo->exec("ALTER TABLE settlements ADD COLUMN notes TEXT AFTER net_to_pay");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Note: Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>