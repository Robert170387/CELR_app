<?php
require_once 'includes/db.php';

try {
    // 1. Add full_name to users
    try {
        echo "Updating Users table...<br>";
        // Check if column exists logic is hard in raw SQL without queries. 
        // We will just attempt to add it. If it fails (Duplicate column), we catch it.
        $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100) AFTER username");
        echo "Users table updated.<br>";
    } catch (PDOException $e) {
        echo "Users table update skipped (might already exist): " . $e->getMessage() . "<br>";
    }

    // 2. Add created_by to trips
    try {
        echo "Updating Trips table...<br>";
        // Using INT instead of INT UNSIGNED to match default auto_increment
        $pdo->exec("ALTER TABLE trips ADD COLUMN created_by INT, ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "Trips table updated.<br>";
    } catch (PDOException $e) {
        echo "Trips table update skipped/failed: " . $e->getMessage() . "<br>";
    }

    // 3. Add created_by to expenses
    try {
        echo "Updating Expenses table...<br>";
        $pdo->exec("ALTER TABLE expenses ADD COLUMN created_by INT, ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "Expenses table updated.<br>";
    } catch (PDOException $e) {
        echo "Expenses table update skipped/failed: " . $e->getMessage() . "<br>";
    }

    echo "Schema update process finished.";

} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
?>