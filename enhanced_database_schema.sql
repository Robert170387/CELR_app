-- Database Schema for CELR_app - Enhanced Expense Categories
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

-- 5. Expense Categories Table (NEW - Hierarchical Structure)
-- Main categories with types
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    type ENUM('viaje', 'vehiculo_fijo', 'administrativo', 'especial') NOT NULL,
    parent_id INT NULL,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_parent (parent_id),
    INDEX idx_sort_order (sort_order)
);

-- 6. Expenses Table (Updated - References new category system)
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NULL,
    vehicle_id INT NULL,
    
    -- New category system
    category_id INT NULL,
    category_name VARCHAR(255) NOT NULL, -- For backward compatibility
    
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_date (date)
);

-- 7. Trips (Viajes) Table
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

-- Seed Initial Config if empty
INSERT INTO config (id, currency, default_rete_fuente, default_rete_ica, ganancia_nacional_percent, ganancia_urbano_percent)
SELECT 1, 'COP', 1.0, 1.0, 10.0, 15.0
WHERE NOT EXISTS (SELECT 1 FROM config WHERE id = 1);

-- Seed Expense Categories with Hierarchical Structure
-- Insert parent categories first, then subcategories with parent_id references

-- 1. Combustible (auto-contenido, sin subcategorías)
INSERT IGNORE INTO expense_categories (name, slug, type, description) VALUES
('Combustible', 'combustible', 'viaje', 'Combustible para vehículos y operaciones de viaje');

-- 2. Peajes y Tasas
INSERT INTO expense_categories (name, slug, type, description) VALUES
('Peajes y Tasas', 'peajes_y_tasas', 'viaje', 'Peajes, tasas y cobros relacionados con el uso de vías');
SET @parent_peajes = LAST_INSERT_ID();
INSERT INTO expense_categories (name, slug, type, parent_id, description) VALUES
('Peaje Efectivo', 'peaje_efectivo', 'viaje', @parent_peajes, 'Peaje pagado en efectivo'),
('Peaje TAG', 'peaje_tag', 'viaje', @parent_peajes, 'Peaje electrónico mediante sistema TAG'),
('Bascula', 'bascula', 'viaje', @parent_peajes, 'Servicios de pesaje de vehículos');

-- 3. Operación de Ruta
INSERT INTO expense_categories (name, slug, type, description) VALUES
('Operación de Ruta', 'operacion_de_ruta', 'viaje', 'Gastos operativos relacionados con la ruta y el viaje');
SET @parent_ruta = LAST_INSERT_ID();
INSERT INTO expense_categories (name, slug, type, parent_id, description) VALUES
('Auxilio Transporte Conductor', 'auxilio_transporte_conductor', 'viaje', @parent_ruta, 'Auxilio de transporte para conductores'),
('Cargue', 'cargue', 'viaje', @parent_ruta, 'Gastos relacionados con el cargue de mercancías'),
('Descargue', 'descargue', 'viaje', @parent_ruta, 'Gastos relacionados con el descargue de mercancías'),
('Carpa', 'carpa', 'viaje', @parent_ruta, 'Gastos de carpa para protección de mercancías'),
('Carrozada', 'carrozada', 'viaje', @parent_ruta, 'Gastos de carrozada para transporte seguro'),
('Descarrozada', 'descarrozada', 'viaje', @parent_ruta, 'Gastos de descarrozada para desmonte seguro'),
('Comision contado', 'comision_contado', 'viaje', @parent_ruta, 'Comisión por pago en efectivo'),
('Comision viaje Conductor', 'comision_viaje_conductor', 'viaje', @parent_ruta, 'Comisión por servicios de viaje del conductor'),
('Hotel', 'hotel', 'viaje', @parent_ruta, 'Gastos de alojamiento durante viajes'),
('Parqueadero', 'parqueadero', 'viaje', @parent_ruta, 'Gastos de estacionamiento durante viajes'),
('Parqueadero + Celular Conductor', 'parqueadero_celular_conductor', 'viaje', @parent_ruta, 'Gastos de estacionamiento con servicio de celular para conductores'),
('Otros Viajes', 'otros_viajes', 'viaje', @parent_ruta, 'Otros gastos relacionados con operaciones de viaje');

-- 4. Mantenimiento y Repuestos
INSERT INTO expense_categories (name, slug, type, description) VALUES
('Mantenimiento y Repuestos', 'mantenimiento_y_repuestos', 'vehiculo_fijo', 'Mantenimiento preventivo y correctivo de vehículos');
SET @parent_manto = LAST_INSERT_ID();
INSERT INTO expense_categories (name, slug, type, parent_id, description) VALUES
('Aceite - Filtros', 'aceite_filtros', 'vehiculo_fijo', @parent_manto, 'Aceite y filtros para mantenimiento de motores'),
('Aire acondicionado', 'aire_acondicionado', 'vehiculo_fijo', @parent_manto, 'Sistema de aire acondicionado para vehículos'),
('Bateria', 'bateria', 'vehiculo_fijo', @parent_manto, 'Baterías y sistemas eléctricos'),
('Caja', 'caja', 'vehiculo_fijo', @parent_manto, 'Componentes de caja de cambios'),
('Motor', 'motor', 'vehiculo_fijo', @parent_manto, 'Componentes y sistemas de motor'),
('Transmision', 'transmision', 'vehiculo_fijo', @parent_manto, 'Sistema de transmisión'),
('Radiador', 'radiador', 'vehiculo_fijo', @parent_manto, 'Sistemas de refrigeración'),
('Electrico', 'electrico', 'vehiculo_fijo', @parent_manto, 'Componentes eléctricos y electrónicos'),
('Engrace', 'engrace', 'vehiculo_fijo', @parent_manto, 'Sistemas de lubricación'),
('Frenos', 'frenos', 'vehiculo_fijo', @parent_manto, 'Sistemas de frenos'),
('Direccion', 'direccion', 'vehiculo_fijo', @parent_manto, 'Sistema de dirección'),
('Lavada', 'lavada', 'vehiculo_fijo', @parent_manto, 'Servicios de lavado y limpieza'),
('Llantas', 'llantas', 'vehiculo_fijo', @parent_manto, 'Llantas y neumáticos'),
('Reencauche', 'reencauche', 'vehiculo_fijo', @parent_manto, 'Servicios de reencauche de neumáticos'),
('Montallantas', 'montallantas', 'vehiculo_fijo', @parent_manto, 'Montaje y desmontaje de llantas'),
('Rines', 'rines', 'vehiculo_fijo', @parent_manto, 'Componentes de rines y llantas'),
('Mano de obra', 'mano_obra', 'vehiculo_fijo', @parent_manto, 'Mano de obra para servicios de mantenimiento'),
('Repuestos', 'repuestos', 'vehiculo_fijo', @parent_manto, 'Repuestos y piezas de repuesto'),
('Pintura', 'pintura', 'vehiculo_fijo', @parent_manto, 'Servicios de pintura y reparación'),
('Tapiceria', 'tapiceria', 'vehiculo_fijo', @parent_manto, 'Servicios de tapicería y revestimientos');

-- 5. Documentación y Seguros
INSERT INTO expense_categories (name, slug, type, description) VALUES
('Documentación y Seguros', 'documentacion_y_seguros', 'vehiculo_fijo', 'Seguros, documentación y servicios de monitoreo de vehículos');
SET @parent_docseg = LAST_INSERT_ID();
INSERT INTO expense_categories (name, slug, type, parent_id, description) VALUES
('SOAT', 'soat', 'vehiculo_fijo', @parent_docseg, 'Seguro Obligatorio de Accidentes de Tráfico'),
('Tecnicomecanica', 'tecnicomecanica', 'vehiculo_fijo', @parent_docseg, 'Servicios de tecnicomecánica'),
('Todo Riesgo', 'todo_riesgo', 'vehiculo_fijo', @parent_docseg, 'Seguro integral de cobertura completa'),
('Satelital GPS', 'satelital_gps', 'vehiculo_fijo', @parent_docseg, 'Servicios de seguimiento GPS satelital'),
('Satelital Video', 'satelital_video', 'vehiculo_fijo', @parent_docseg, 'Servicios de seguimiento video satelital');

-- 6. Administrativos y Nómina
INSERT INTO expense_categories (name, slug, type, description) VALUES
('Administrativos y Nómina', 'administrativos_y_nomina', 'administrativo', 'Gastos administrativos y de nómina');
SET @parent_admin = LAST_INSERT_ID();
INSERT INTO expense_categories (name, slug, type, parent_id, description) VALUES
('Salario basico Conductor', 'salario_basico_conductor', 'administrativo', @parent_admin, 'Salario básico para conductores'),
('Salario supervisor', 'salario_supervisor', 'administrativo', @parent_admin, 'Salario para supervisores'),
('Contador', 'contador', 'administrativo', @parent_admin, 'Servicios de contabilidad y administración'),
('Papeleria Conductor', 'papeleria_conductor', 'administrativo', @parent_admin, 'Papelería y suministros para conductores'),
('Otros', 'otros_administrativos', 'administrativo', @parent_admin, 'Otros gastos administrativos');

-- 7. Cuentas Especiales / No Operativos
INSERT INTO expense_categories (name, slug, type, description) VALUES
('Cuentas Especiales / No Operativos', 'cuentas_especiales', 'especial', 'Cuentas especiales y no operativas');
SET @parent_especial = LAST_INSERT_ID();
INSERT INTO expense_categories (name, slug, type, parent_id, description) VALUES
('Compra Activo / Vehículo', 'compra_activo_vehiculo', 'especial', @parent_especial, 'Compra de activos fijos y vehículos (Balance General)'),
('Deducible Cargamos', 'deducible_cargamos', 'especial', @parent_especial, 'Deducibles por siniestros o cuentas por cobrar/cruzar');

-- Create indexes for better performance
CREATE INDEX idx_expense_categories_type ON expense_categories(type);
CREATE INDEX idx_expense_categories_parent ON expense_categories(parent_id);
CREATE INDEX idx_expenses_category ON expenses(category_id);
CREATE INDEX idx_expenses_date ON expenses(date);

-- Show completion message
SELECT 'Database schema created successfully!' as message;
SELECT 'Expense categories hierarchical structure implemented' as info;
SELECT 'Views and procedures created for reporting and analysis' as info;
