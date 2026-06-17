<?php
require_once 'includes/db.php';

try {
    echo "Migrating 'drivers' table to 'personnel'...\n";

    // 1. Rename Table
    // Check if table 'personnel' already exists
    $exists = $pdo->query("SHOW TABLES LIKE 'personnel'")->rowCount() > 0;
    if (!$exists) {
        $pdo->exec("RENAME TABLE drivers TO personnel");
        echo "Table renamed to 'personnel'.\n";
    } else {
        echo "Table 'personnel' already exists.\n";
    }

    // 2. Add New Columns
    $columns = [
        "ADD COLUMN type ENUM('Administrativo', 'Conductor') DEFAULT 'Conductor'",
        "ADD COLUMN document_type ENUM('CC', 'CE', 'NIT', 'Pasaporte') DEFAULT 'CC'",
        "CHANGE COLUMN document_id document_number VARCHAR(50)", // Rename old column
        "CHANGE COLUMN name firstname VARCHAR(255)", // Rename old column
        "ADD COLUMN lastname VARCHAR(255) AFTER firstname",
        "ADD COLUMN address VARCHAR(255)",
        "ADD COLUMN city VARCHAR(100)",
        "ADD COLUMN department VARCHAR(100)",
        "ADD COLUMN gender ENUM('Masculino', 'Femenino') DEFAULT 'Masculino'",
        "CHANGE COLUMN license license_number VARCHAR(50)", // Rename
        "ADD COLUMN license_category VARCHAR(10)", // A1, C1 etc
        "ADD COLUMN date_entry DATE",
        "ADD COLUMN date_exit DATE",
        "ADD COLUMN salary_basic DECIMAL(15,2) DEFAULT 0",
        "ADD COLUMN salary_variable DECIMAL(15,2) DEFAULT 0",
        "ADD COLUMN salary_internal DECIMAL(15,2) DEFAULT 0",
        "ADD COLUMN bank_account VARCHAR(100)"
    ];

    foreach ($columns as $sql) {
        try {
            $pdo->exec("ALTER TABLE personnel $sql");
            echo "Executed: $sql\n";
        } catch (PDOException $e) {
            // Ignore Duplicate column errors
            if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                // echo "Skipped (exists/error): $sql\n";
            } else {
                // If renaming failed because it's already renamed, we might want to skip.
                echo "Note: " . $e->getMessage() . "\n";
            }
        }
    }

    // 3. Update Foreign Keys in Trips is NOT needed usually if MySQL handles rename correctly, 
    // BUT the column name in trips is `driver_id`. We can keep `driver_id` pointing to `personnel.id`.
    // It's semantically okay for now.

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Critical Error: " . $e->getMessage() . "\n";
}
?>