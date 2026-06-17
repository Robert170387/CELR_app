<?php
require_once 'includes/db.php';

try {
    echo "Updating schema for comprehensive configuration...<br>";

    // Localization
    $pdo->exec("ALTER TABLE config ADD COLUMN base_country VARCHAR(100) DEFAULT 'Colombia' AFTER currency");
    $pdo->exec("ALTER TABLE config ADD COLUMN currency_symbol VARCHAR(10) DEFAULT '$' AFTER base_country");
    $pdo->exec("ALTER TABLE config ADD COLUMN decimal_separator VARCHAR(1) DEFAULT ',' AFTER currency_symbol");
    $pdo->exec("ALTER TABLE config ADD COLUMN thousands_separator VARCHAR(1) DEFAULT '.' AFTER decimal_separator");
    $pdo->exec("ALTER TABLE config ADD COLUMN decimal_count INT DEFAULT 0 AFTER thousands_separator");

    // Fiscal
    $pdo->exec("ALTER TABLE config ADD COLUMN default_iva_percent DECIMAL(5,2) DEFAULT 0.00 AFTER default_rete_ica");
    $pdo->exec("ALTER TABLE config ADD COLUMN default_rete_iva_percent DECIMAL(5,2) DEFAULT 0.00 AFTER default_iva_percent");

    // Operational
    $pdo->exec("ALTER TABLE config ADD COLUMN weight_unit VARCHAR(20) DEFAULT 'Toneladas' AFTER ganancia_urbano_percent");
    $pdo->exec("ALTER TABLE config ADD COLUMN distance_unit VARCHAR(20) DEFAULT 'Kilómetros' AFTER weight_unit");
    $pdo->exec("ALTER TABLE config ADD COLUMN maint_warning_kms INT DEFAULT 500 AFTER distance_unit");
    $pdo->exec("ALTER TABLE config ADD COLUMN doc_warning_days INT DEFAULT 30 AFTER maint_warning_kms");

    // Identity
    $pdo->exec("ALTER TABLE config ADD COLUMN business_name VARCHAR(255) NULL AFTER last_backup_at");
    $pdo->exec("ALTER TABLE config ADD COLUMN nit VARCHAR(50) NULL AFTER business_name");
    $pdo->exec("ALTER TABLE config ADD COLUMN billing_resolution TEXT NULL AFTER nit");
    $pdo->exec("ALTER TABLE config ADD COLUMN address VARCHAR(255) NULL AFTER billing_resolution");
    $pdo->exec("ALTER TABLE config ADD COLUMN phone VARCHAR(100) NULL AFTER address");
    $pdo->exec("ALTER TABLE config ADD COLUMN email VARCHAR(100) NULL AFTER phone");

    echo "<b style='color:green;'>Success: Comprehensive configuration schema updated.</b>";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<b style='color:orange;'>Info: Some columns already exist. Proceeding...</b>";
    } else {
        echo "<b style='color:red;'>Error updating schema:</b> " . $e->getMessage();
    }
}
?>