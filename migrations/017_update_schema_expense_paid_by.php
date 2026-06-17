<?php
/**
 * Migration: Add paid_by column to expenses table
 * This tracks whether an expense is paid by the Conductor or Propietario
 */

require_once 'includes/db.php';

try {
    echo "Starting migration: Add paid_by column to expenses table...\n";

    // Check if column already exists
    $checkStmt = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'paid_by'");
    if ($checkStmt->rowCount() > 0) {
        echo "Column 'paid_by' already exists. Skipping migration.\n";
        return;
    }

    // Add the paid_by column
    $sql = "ALTER TABLE expenses 
            ADD COLUMN paid_by ENUM('Conductor', 'Propietario') 
            NOT NULL DEFAULT 'Conductor' 
            AFTER category";

    $pdo->exec($sql);

    echo "✓ Successfully added 'paid_by' column to expenses table.\n";
    echo "✓ Default value set to 'Conductor' for existing records.\n";
    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    throw $e;
}
?>