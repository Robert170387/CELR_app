<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE trips ADD COLUMN advance_manifest_to_driver TINYINT(1) DEFAULT 0 AFTER advance_manifest");
    echo "Column 'advance_manifest_to_driver' added to 'trips' table successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>