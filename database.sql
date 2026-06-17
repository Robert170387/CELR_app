-- Database Schema for CELR_app
-- Created for MySQL/MariaDB

CREATE DATABASE IF NOT EXISTS celr_app;
USE celr_app;

-- 1. Configuration Table
-- Holds global default values.
CREATE TABLE IF NOT EXISTS config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    currency VARCHAR(10) DEFAULT 'COP',
    default_rete_fuente DECIMAL(5,2) DEFAULT 0.00,
    default_rete_ica DECIMAL(5,2) DEFAULT 0.00,
    ganancia_nacional_percent DECIMAL(5,2) DEFAULT 0.00,
    ganancia_urbano_percent DECIMAL(5,2) DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 1.5 Locations Table (Added to fix missing table error)
CREATE TABLE IF NOT EXISTS locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1
);


    active TINYINT(1) DEFAULT 1
);

-- 2. Materials Table
CREATE TABLE IF NOT EXISTS materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1
);

-- 2.5 Manifest Companies Table
CREATE TABLE IF NOT EXISTS manifest_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1
);


-- 3. Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    placa VARCHAR(20) UNIQUE NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    active TINYINT(1) DEFAULT 1
);

-- 4. Drivers Table
CREATE TABLE IF NOT EXISTS drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    document_id VARCHAR(50),
    phone VARCHAR(50),
    license VARCHAR(50),
    active TINYINT(1) DEFAULT 1
);

-- 5. Trips (Viajes) Table
-- The core table linking everything.
CREATE TABLE IF NOT EXISTS trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Info
    trip_type ENUM('urbano', 'nacional', 'internacional') NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    material_id INT,
    
    -- Route & Dates
    origin VARCHAR(255),
    destination VARCHAR(255),
    date_load DATE,
    date_unload DATE,
    
    -- Odometer (Tacómetro)
    kms_start DECIMAL(10,2) DEFAULT 0,
    kms_end DECIMAL(10,2) DEFAULT 0,
    kms_total DECIMAL(10,2) DEFAULT 0, -- Calculated
    
    -- Manifest Data
    manifest_company VARCHAR(255), -- Legacy (kept for historical data)
    manifest_company_id INT, -- New relation
    manifest_number VARCHAR(100),

    manifest_number VARCHAR(100),
    manifest_date DATE,
    weight_declared DECIMAL(10,2) DEFAULT 0,
    weight_origin DECIMAL(10,2) DEFAULT 0,
    weight_dest DECIMAL(10,2) DEFAULT 0,
    
    -- Financials: Income (Flete)
    flete_bruto DECIMAL(15,2) DEFAULT 0, -- Valor flete manifiesto
    
    -- Deductibles (Retentions based on percentages)
    percent_rete_fuente DECIMAL(5,2) DEFAULT 0,
    value_rete_fuente DECIMAL(15,2) DEFAULT 0,
    
    percent_rete_ica DECIMAL(5,2) DEFAULT 0,
    value_rete_ica DECIMAL(15,2) DEFAULT 0,
    
    percent_deductible_3 DECIMAL(5,2) DEFAULT 0,
    value_deductible_3 DECIMAL(15,2) DEFAULT 0,
    
    value_deductible_4 DECIMAL(15,2) DEFAULT 0,
    value_deductible_5 DECIMAL(15,2) DEFAULT 0,
    value_deductible_6 DECIMAL(15,2) DEFAULT 0,
    
    total_deductibles DECIMAL(15,2) DEFAULT 0,
    flete_neto DECIMAL(15,2) DEFAULT 0, -- flete_bruto - total_deductibles
    
    -- Advances
    advance_owner DECIMAL(15,2) DEFAULT 0,
    advance_owner_responsible VARCHAR(255),
    advance_manifest DECIMAL(15,2) DEFAULT 0,
    
    -- Commission & Final Calculations
    commission_percent DECIMAL(5,2) DEFAULT 0,
    commission_value DECIMAL(15,2) DEFAULT 0, 
    
    final_pay_expected DECIMAL(15,2) DEFAULT 0, -- flete_neto - advance_manifest
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- 6. Expenses Table
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NULL,
    vehicle_id INT NULL,
    
    -- Categories:
    -- Operational (Viaje): 'combustible', 'peajes', 'cargue', 'descargue', 'viaticos', 'parqueadero_viaje', 'otros_viaje'
    -- Fixed/Maintenance: 'mantenimiento', 'soat', 'seguros', 'impuestos', 'llantas', 'aceite', 'parqueadero_fijo'
    category VARCHAR(50) NOT NULL, 
    
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Seed Initial Config if empty
INSERT INTO config (id, currency, default_rete_fuente, default_rete_ica, ganancia_nacional_percent, ganancia_urbano_percent)
SELECT 1, 'COP', 1.0, 1.0, 10.0, 15.0
WHERE NOT EXISTS (SELECT 1 FROM config WHERE id = 1);
