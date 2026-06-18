<?php
require_once 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL, -- 'status_change', 'unusual_expense', 'system'
        entity_id INT DEFAULT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        priority VARCHAR(20) DEFAULT 'normal', -- 'low', 'normal', 'high', 'critical'
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Table 'system_alerts' created successfully.<br>";

    // Add config for unusual expense threshold if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM config LIKE 'unusual_expense_threshold'")->fetchAll(); if (!$cols) $pdo->exec("ALTER TABLE config ADD COLUMN unusual_expense_threshold DECIMAL(15,2) DEFAULT 1000000.00");
    echo "Config column 'unusual_expense_threshold' added.<br>";

} catch (PDOException $e) {
    die("Error creating alerts table: " . $e->getMessage());
}
