<?php
require_once 'includes/db.php';

try {
    echo "Updating users table schema...\n";

    // Add 'avatar' column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
        echo "Added 'avatar' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "'avatar' column already exists.\n";
        } else {
            throw $e;
        }
    }

    // Add 'status' column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' NOT NULL");
        echo "Added 'status' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "'status' column already exists.\n";
        } else {
            throw $e;
        }
    }

    // Add 'last_login' column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL");
        echo "Added 'last_login' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "'last_login' column already exists.\n";
        } else {
            throw $e;
        }
    }

    echo "Schema update completed successfully.\n";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>