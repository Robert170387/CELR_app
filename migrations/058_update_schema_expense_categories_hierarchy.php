<?php
// migrations/058_update_schema_expense_categories_hierarchy.php

echo "Adding parent_id to expense_categories for hierarchy support.\n";

try {
    // Add parent_id column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM expense_categories LIKE 'parent_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE expense_categories ADD COLUMN parent_id INT(11) NULL AFTER slug");
        echo "Added parent_id column.\n";
    } else {
        echo "Column parent_id already exists.\n";
    }

    // Insert 'Operativos (Viaje)' as a parent category
    $pdo->exec("INSERT IGNORE INTO expense_categories (name, slug, type, parent_id) VALUES ('Operativos (Viaje)', 'operativos_viaje', 'variable', NULL)");
    $stmt = $pdo->query("SELECT id FROM expense_categories WHERE slug = 'operativos_viaje' AND parent_id IS NULL LIMIT 1");
    $opId = $stmt->fetchColumn();
    echo "Parent 'operativos_viaje' ID: " . ($opId ?: 'exists') . "\n";

    // Insert 'Fijos / Mantenimiento' as a parent category
    $pdo->exec("INSERT IGNORE INTO expense_categories (name, slug, type, parent_id) VALUES ('Fijos / Mantenimiento', 'fijos_mantenimiento', 'fixed', NULL)");
    $stmt = $pdo->query("SELECT id FROM expense_categories WHERE slug = 'fijos_mantenimiento' AND parent_id IS NULL LIMIT 1");
    $fixId = $stmt->fetchColumn();
    echo "Parent 'fijos_mantenimiento' ID: " . ($fixId ?: 'exists') . "\n";

    // Link existing subcategories to their new parents
    if ($opId) {
        $pdo->exec("UPDATE expense_categories SET parent_id = $opId WHERE type = 'variable' AND id != $opId AND (parent_id IS NULL OR parent_id = 0)");
    }
    if ($fixId) {
        $pdo->exec("UPDATE expense_categories SET parent_id = $fixId WHERE type = 'fixed' AND id != $fixId AND (parent_id IS NULL OR parent_id = 0)");
    }

    // Add foreign key constraint
    try {
        $pdo->exec("ALTER TABLE expense_categories ADD CONSTRAINT fk_expense_cat_parent FOREIGN KEY (parent_id) REFERENCES expense_categories(id) ON DELETE SET NULL");
        echo "Added foreign key constraint.\n";
    } catch (\PDOException $e) {
        echo "Foreign key constraint may already exist: " . $e->getMessage() . "\n";
    }

    echo "Migration 058 completed successfully.\n";

} catch (Exception $e) {
    echo "Migration 058 failed: " . $e->getMessage() . "\n";
    throw $e;
}
