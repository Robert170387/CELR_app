<?php
require_once 'includes/db.php';

try {
    echo "Updating database schema for Settlements...\n";

    // 1. Add transport_assistance to personnel
    $pdo->exec("ALTER TABLE personnel ADD COLUMN transport_assistance DECIMAL(15,2) DEFAULT 0 AFTER salary_internal");
    echo "- Column 'transport_assistance' added to 'personnel'.\n";

    // 2. Create settlements table
    $sql = "CREATE TABLE IF NOT EXISTS settlements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        personnel_id INT NOT NULL,
        month INT NOT NULL,
        year INT NOT NULL,
        salary_basic DECIMAL(15,2) NOT NULL,
        transport_assistance DECIMAL(15,2) NOT NULL,
        total_commissions DECIMAL(15,2) NOT NULL,
        total_advances DECIMAL(15,2) NOT NULL,
        total_expenses DECIMAL(15,2) NOT NULL,
        balance_to_discount DECIMAL(15,2) NOT NULL, -- (Advances - Expenses)
        net_to_pay DECIMAL(15,2) NOT NULL,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (personnel_id) REFERENCES personnel(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    echo "- Table 'settlements' created successfully.\n";

    echo "\nMigration completed successfully.";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Note: Column 'transport_assistance' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>