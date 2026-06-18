<?php
/**
 * Migration 056: Create socios table - Socios / Propietarios de Vehículos
 */
require_once __DIR__ . '/../includes/db.php';
echo "Creating socios table...\n";

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS socios (
        id                  INT AUTO_INCREMENT PRIMARY KEY,
        tipo                ENUM('Persona Natural','Empresa') DEFAULT 'Persona Natural',
        nombre              VARCHAR(255) NOT NULL,
        documento           VARCHAR(50) NULL,
        tipo_documento      ENUM('CC','NIT','CE','Pasaporte') DEFAULT 'CC',
        telefono            VARCHAR(30) NULL,
        celular             VARCHAR(30) NULL,
        email               VARCHAR(150) NULL,
        direccion           VARCHAR(255) NULL,
        ciudad              VARCHAR(100) NULL,
        departamento        VARCHAR(100) NULL,
        banco               VARCHAR(100) NULL,
        cuenta_bancaria     VARCHAR(50) NULL,
        tipo_cuenta         ENUM('Ahorros','Corriente') NULL,
        porcentaje_utilidad DECIMAL(5,2) DEFAULT 0.00,
        notas               TEXT NULL,
        active              TINYINT(1) DEFAULT 1,
        created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        KEY idx_socios_nombre (nombre),
        KEY idx_socios_active (active),
        KEY idx_socios_ciudad (ciudad)

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo " - Table socios created\n";
    echo "✓ socios table ready.\n";
} catch (PDOException $e) {
    echo "x Error: " . $e->getMessage() . "\n";
}
?>
