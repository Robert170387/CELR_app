<?php
require_once 'includes/db.php';

try {
    echo "Updating settlements table for date ranges...\n";

    // Add date_start and date_end
    $pdo->exec("ALTER TABLE settlements 
                ADD COLUMN date_start DATE AFTER year,
                ADD COLUMN date_end DATE AFTER date_start");

    echo "- Columns 'date_start' and 'date_end' added.\n";
    echo "Migration completed successfully.";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Note: Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>