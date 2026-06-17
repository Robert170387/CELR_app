<?php
require_once 'includes/db.php';

try {
    echo "Updating settlements table for partial payments and breakdowns...\n";

    $pdo->exec("ALTER TABLE settlements 
                ADD COLUMN partial_payment DECIMAL(15,2) DEFAULT 0 AFTER balance_to_discount,
                ADD COLUMN total_advances_manifest DECIMAL(15,2) DEFAULT 0 AFTER total_commissions,
                ADD COLUMN total_advances_owner DECIMAL(15,2) DEFAULT 0 AFTER total_advances_manifest");

    echo "- Columns added successfully.\n";
    echo "Migration completed.";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Note: Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>