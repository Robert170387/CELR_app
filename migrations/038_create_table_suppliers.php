<?php
require_once 'includes/db.php';

try {
    echo "Creating 'suppliers' table...\n";

    $sql = "CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        person_type ENUM('Natural', 'Jurídica') DEFAULT 'Natural',
        tax_regime ENUM('Común', 'Simplificado') DEFAULT 'Simplificado',
        nit VARCHAR(50),
        firstname VARCHAR(100),
        lastname1 VARCHAR(100),
        lastname2 VARCHAR(100),
        business_name VARCHAR(255),
        address VARCHAR(255),
        department VARCHAR(100),
        city VARCHAR(100),
        bank_name VARCHAR(100),
        bank_account VARCHAR(100),
        account_type ENUM('Ahorros', 'Corriente') DEFAULT 'Ahorros',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "Table 'suppliers' created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>