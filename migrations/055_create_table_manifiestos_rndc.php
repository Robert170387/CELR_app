<?php
/**
 * Migration 055: Tabla manifiestos_rndc — Manifiesto Electrónico de Carga (Colombia)
 */
require_once __DIR__ . '/../includes/db.php';
echo "Creating manifiestos_rndc table...\n";

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS manifiestos_rndc (
        id                      INT AUTO_INCREMENT PRIMARY KEY,
        trip_id                 INT NULL,
        vehicle_id              INT NOT NULL,
        driver_id               INT NOT NULL,

        nro_manifiesto          VARCHAR(50) NOT NULL,
        autorizacion_rndc       VARCHAR(50) NULL,
        nro_remesa              VARCHAR(50) NULL,
        fecha_expedicion        DATE NOT NULL,
        fecha_vencimiento       DATE NULL,

        empresa_transporte      VARCHAR(255) NOT NULL,
        nit_empresa             VARCHAR(50) NULL,

        remitente_nombre        VARCHAR(255) NOT NULL,
        remitente_nit           VARCHAR(50) NULL,
        remitente_codigo        VARCHAR(50) NULL,
        remitente_direccion     VARCHAR(255) NULL,
        remitente_ciudad        VARCHAR(100) NULL,

        destinatario_nombre     VARCHAR(255) NOT NULL,
        destinatario_nit        VARCHAR(50) NULL,
        destinatario_codigo     VARCHAR(50) NULL,
        destinatario_direccion  VARCHAR(255) NULL,
        destinatario_ciudad     VARCHAR(100) NULL,

        origen                  VARCHAR(255) NOT NULL,
        destino                 VARCHAR(255) NOT NULL,

        descripcion_mercancia   TEXT NULL,
        peso_kg                 DECIMAL(10,2) NULL,
        unidades                INT NULL,
        tipo_vehiculo           VARCHAR(100) NULL,

        flete_pactado           DECIMAL(15,2) DEFAULT 0.00,
        anticipo                DECIMAL(15,2) DEFAULT 0.00,
        saldo                   DECIMAL(15,2) DEFAULT 0.00,
        cargue_pagado_por       ENUM('Remitente','Destinatario','Propietario') NULL,
        descargue_pagado_por    ENUM('Remitente','Destinatario','Propietario') NULL,
        lugar_pago              VARCHAR(100) NULL,
        fecha_pago_saldo        DATE NULL,

        estado                  ENUM('Borrador','Activo','Finalizado','Anulado') DEFAULT 'Activo',
        notas                   TEXT NULL,

        created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (trip_id)   REFERENCES trips(id) ON DELETE SET NULL,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
        FOREIGN KEY (driver_id)  REFERENCES personnel(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "✓ Tabla manifiestos_rndc creada correctamente.\n";
} catch (PDOException $e) {
    echo "x Error: " . $e->getMessage() . "\n";
}
