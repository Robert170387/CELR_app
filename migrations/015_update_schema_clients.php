<?php
require_once 'includes/db.php';

echo "Iniciando migración de base de datos para módulo de Clientes...\n\n";

try {
    // 1. Crear tabla clients
    echo "1. Creando tabla 'clients'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_type ENUM('Física', 'Jurídica') NOT NULL,
            
            -- Persona Física
            firstname VARCHAR(100),
            lastname1 VARCHAR(100),
            lastname2 VARCHAR(100),
            
            -- Persona Jurídica
            business_name VARCHAR(200),
            legal_id VARCHAR(50),
            
            -- Común
            email VARCHAR(150),
            phone VARCHAR(20),
            mobile VARCHAR(20),
            
            -- Dirección
            address TEXT,
            country VARCHAR(100) DEFAULT 'Costa Rica',
            department VARCHAR(100),
            city VARCHAR(100),
            postal_code VARCHAR(20),
            
            -- Metadata
            notes TEXT,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_person_type (person_type),
            INDEX idx_active (active),
            INDEX idx_business_name (business_name),
            INDEX idx_lastname (lastname1)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "   ✓ Tabla 'clients' creada exitosamente.\n\n";

    // 2. Verificar si la columna client_id ya existe en trips
    echo "2. Verificando columna 'client_id' en tabla 'trips'...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM trips LIKE 'client_id'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        echo "   Agregando columna 'client_id' a tabla 'trips'...\n";
        $pdo->exec("
            ALTER TABLE trips 
            ADD COLUMN client_id INT NULL AFTER vehicle_id,
            ADD INDEX idx_client_id (client_id)
        ");
        echo "   ✓ Columna 'client_id' agregada exitosamente.\n\n";

        // Agregar foreign key
        echo "3. Agregando foreign key constraint...\n";
        $pdo->exec("
            ALTER TABLE trips 
            ADD CONSTRAINT fk_trips_client 
                FOREIGN KEY (client_id) 
                REFERENCES clients(id) 
                ON DELETE RESTRICT
        ");
        echo "   ✓ Foreign key agregada exitosamente.\n\n";
    } else {
        echo "   ℹ Columna 'client_id' ya existe.\n\n";
    }

    echo "✅ Migración completada exitosamente!\n";
    echo "\nPuedes eliminar este archivo después de ejecutarlo.\n";

} catch (PDOException $e) {
    echo "❌ Error en la migración: " . $e->getMessage() . "\n";
    throw $e;
}
?>