<?php
require_once 'includes/db.php';

try {
    echo "Updating 'expenses' table to add 'supplier_id' column...\n";

    // Add column if not exists
    $sql = "ALTER TABLE expenses ADD COLUMN supplier_id INT DEFAULT NULL";
    try {
        $pdo->exec($sql);
        echo "Column 'supplier_id' added.\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') { // Duplicate column
            echo "Column 'supplier_id' already exists.\n";
        } else {
            throw $e;
        }
    }

    // Add FK
    $sqlFK = "ALTER TABLE expenses ADD CONSTRAINT fk_expenses_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL";
    try {
        $pdo->exec($sqlFK);
        echo "Foreign Key added.\n";
    } catch (PDOException $e) {
        // FK might already exist, safe to ignore for now or handle explicitly
        echo "Constraint might already exist: " . $e->getMessage() . "\n";
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>