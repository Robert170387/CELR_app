# 🔧 SOLUCIONES TÉCNICAS PARA BUGS CRÍTICOS

## Archivo: `database_CORRECTED.sql`
### Para corregir todos los problemas de schema

```sql
-- ========== DATABASE CELR_APP CORRECTED SCHEMA ==========

DROP DATABASE IF EXISTS celr_app;
CREATE DATABASE celr_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE celr_app;

-- ========== 1. CONFIGURATION TABLE ==========
CREATE TABLE IF NOT EXISTS config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    currency VARCHAR(10) DEFAULT 'COP',
    currency_symbol VARCHAR(5) DEFAULT '$',
    decimal_separator VARCHAR(1) DEFAULT '.',
    thousands_separator VARCHAR(1) DEFAULT ',',
    decimal_count INT DEFAULT 2,
    
    -- Financial Defaults
    default_rete_fuente DECIMAL(5,2) DEFAULT 0.00,
    default_rete_ica DECIMAL(5,2) DEFAULT 0.00,
    ganancia_nacional_percent DECIMAL(5,2) DEFAULT 0.00,
    ganancia_urbano_percent DECIMAL(5,2) DEFAULT 0.00,
    
    -- Maintenance & Document Warnings
    maint_warning_kms INT DEFAULT 500,
    doc_warning_days INT DEFAULT 30,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========== 2. USERS TABLE ==========
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operador', 'contador', 'supervisor') DEFAULT 'operador',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- ========== 3. LOCATIONS HIERARCHY ==========
CREATE TABLE IF NOT EXISTS countries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(2) DEFAULT NULL,
    active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS states (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(5) DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    INDEX idx_country (country_id),
    UNIQUE KEY unique_state (country_id, name)
);

CREATE TABLE IF NOT EXISTS cities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    state_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE,
    INDEX idx_state (state_id),
    UNIQUE KEY unique_city (state_id, name)
);

CREATE TABLE IF NOT EXISTS locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT,
    state_id INT,
    city_id INT,
    name VARCHAR(255) NOT NULL,
    alias VARCHAR(255),
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
    INDEX idx_active (active)
);

-- ========== 4. CATEGORIES TABLE ==========
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('expense', 'income', 'other') DEFAULT 'expense',
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (active)
);

-- ========== 5. MATERIALS TABLE ==========
CREATE TABLE IF NOT EXISTS materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    active TINYINT(1) DEFAULT 1
);

-- ========== 6. MANIFEST COMPANIES TABLE ==========
CREATE TABLE IF NOT EXISTS manifest_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    nit VARCHAR(50),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========== 7. CLIENTS TABLE ==========
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    nit VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_active (active)
);

-- ========== 8. SUPPLIERS TABLE ==========
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    nit VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    category VARCHAR(50), -- fuel, maintenance, etc
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (active)
);

-- ========== 9. VEHICLES TABLE ==========
CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    placa VARCHAR(20) UNIQUE NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    year INT,
    vin VARCHAR(100),
    
    -- Documents
    expiry_soat DATE,
    expiry_tecno DATE,
    expiry_rip DATE,
    
    -- Last Maintenance
    last_kms DECIMAL(10,2) DEFAULT 0,
    last_maintenance_date DATE,
    next_service_kms DECIMAL(10,2),
    next_service_date DATE,
    
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_placa (placa),
    INDEX idx_active (active)
);

-- ========== 10. PERSONNEL TABLE ==========
CREATE TABLE IF NOT EXISTS personnel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('Conductor', 'Propietario', 'Supervisor', 'Administrativo') DEFAULT 'Conductor',
    document_id VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(100),
    license VARCHAR(50),
    license_expiry DATE,
    
    bank_account VARCHAR(50),
    bank_name VARCHAR(100),
    
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_document (document_id),
    INDEX idx_active (active)
);

-- ========== 11. SOCIOS TABLE (Partners/Co-owners) ==========
CREATE TABLE IF NOT EXISTS socios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    document_id VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(100),
    participation_percent DECIMAL(5,2),
    bank_account VARCHAR(50),
    bank_name VARCHAR(100),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========== 12. TRIPS TABLE (Core) ==========
CREATE TABLE IF NOT EXISTS trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Status & Tracking
    status ENUM('En Progreso', 'En Espera', 'Finalizado', 'Cancelado') DEFAULT 'En Progreso',
    trip_type ENUM('urbano', 'nacional', 'internacional') NOT NULL,
    
    -- References
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    material_id INT,
    client_id INT,
    manifest_company_id INT,
    
    -- Route & Dates
    origin VARCHAR(255),
    origin_city_id INT,
    origin_state_id INT,
    
    destination VARCHAR(255),
    destination_city_id INT,
    destination_state_id INT,
    
    date_load DATE,
    date_unload DATE,
    
    -- Odometer (Tacómetro)
    kms_start DECIMAL(10,2) DEFAULT 0,
    kms_end DECIMAL(10,2) DEFAULT 0,
    kms_total DECIMAL(10,2) GENERATED ALWAYS AS (kms_end - kms_start) STORED,
    
    -- Manifest Data
    manifest_number VARCHAR(100),
    manifest_date DATE,
    weight_declared DECIMAL(10,2) DEFAULT 0,
    weight_origin DECIMAL(10,2) DEFAULT 0,
    weight_dest DECIMAL(10,2) DEFAULT 0,
    
    -- Financials: Income (Flete)
    flete_bruto DECIMAL(15,2) DEFAULT 0,
    
    -- Deductibles (Retentions)
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
    flete_neto DECIMAL(15,2) DEFAULT 0,
    
    -- Advances
    advance_owner DECIMAL(15,2) DEFAULT 0,
    advance_owner_responsible VARCHAR(255),
    advance_manifest DECIMAL(15,2) DEFAULT 0,
    advance_manifest_to_driver TINYINT(1) DEFAULT 0,
    
    -- Commission & Final Calculations
    commission_percent DECIMAL(5,2) DEFAULT 0,
    commission_value DECIMAL(15,2) DEFAULT 0,
    final_pay_expected DECIMAL(15,2) DEFAULT 0,
    
    -- Settlement Status
    settlement_status ENUM('Pending', 'Partial', 'Complete', 'Cancelled') DEFAULT 'Pending',
    settlement_id INT,
    
    -- POD & Documents
    pod_file VARCHAR(255),
    manifest_file VARCHAR(255),
    
    -- Audit
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (driver_id) REFERENCES personnel(id) ON DELETE RESTRICT,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (manifest_company_id) REFERENCES manifest_companies(id) ON DELETE SET NULL,
    FOREIGN KEY (origin_city_id) REFERENCES cities(id) ON DELETE SET NULL,
    FOREIGN KEY (origin_state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (destination_city_id) REFERENCES cities(id) ON DELETE SET NULL,
    FOREIGN KEY (destination_state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_driver (driver_id),
    INDEX idx_date (date_load),
    INDEX idx_settlement (settlement_status)
);

-- ========== 13. EXPENSES TABLE ==========
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT,
    vehicle_id INT NOT NULL,
    supplier_id INT,
    
    -- Categorization
    category VARCHAR(50) NOT NULL,
    paid_by ENUM('Conductor', 'Propietario') DEFAULT 'Propietario',
    
    -- Amount & Details
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    
    -- Payment Info
    payment_method VARCHAR(50),
    invoice_number VARCHAR(50),
    invoice_status ENUM('Pendiente', 'Pagado', 'Rechazado') DEFAULT 'Pendiente',
    
    -- Fuel Specific
    gallons DECIMAL(10,2),
    price_per_gallon DECIMAL(10,2),
    
    -- Receipt & Location
    receipt_photo VARCHAR(255),
    department VARCHAR(100),
    city VARCHAR(100),
    
    -- Location Details
    eds_name VARCHAR(255),
    eds_location VARCHAR(255),
    eds_state_id INT,
    eds_city_id INT,
    
    -- Audit
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (eds_state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (eds_city_id) REFERENCES cities(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_trip (trip_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_category (category),
    INDEX idx_date (date),
    INDEX idx_paid_by (paid_by)
);

-- ========== 14. SETTLEMENTS TABLE ==========
CREATE TABLE IF NOT EXISTS settlements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Period
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- References
    driver_id INT,
    vehicle_owner_id INT,
    created_by INT,
    
    -- Financial Summary
    total_flete DECIMAL(15,2) DEFAULT 0,
    total_deductibles DECIMAL(15,2) DEFAULT 0,
    total_advances DECIMAL(15,2) DEFAULT 0,
    total_expenses DECIMAL(15,2) DEFAULT 0,
    total_commissions DECIMAL(15,2) DEFAULT 0,
    net_payment DECIMAL(15,2) DEFAULT 0,
    
    -- Status
    status ENUM('Draft', 'Approved', 'Paid', 'Cancelled') DEFAULT 'Draft',
    payment_method VARCHAR(50),
    payment_date DATE,
    payment_reference VARCHAR(100),
    
    -- Notes
    notes TEXT,
    
    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (driver_id) REFERENCES personnel(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_owner_id) REFERENCES personnel(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_period (period_start, period_end),
    INDEX idx_status (status)
);

-- ========== 15. SETTLEMENT_TRIPS JUNCTION ==========
CREATE TABLE IF NOT EXISTS settlement_trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    settlement_id INT NOT NULL,
    trip_id INT NOT NULL,
    FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    UNIQUE KEY unique_settlement_trip (settlement_id, trip_id)
);

-- ========== 16. AUDIT LOGS TABLE ==========
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL, -- CREATE, READ, UPDATE, DELETE
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT,
    old_values JSON,
    new_values JSON,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_timestamp (created_at)
);

-- ========== 17. MAINTENANCE LOGS TABLE ==========
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    
    maintenance_type VARCHAR(50),
    description TEXT,
    cost DECIMAL(15,2),
    
    service_date DATE,
    kms_at_service DECIMAL(10,2),
    
    next_service_kms DECIMAL(10,2),
    next_service_date DATE,
    
    supplier_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_date (service_date)
);

-- ========== 18. SYSTEM ALERTS TABLE ==========
CREATE TABLE IF NOT EXISTS system_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    alert_type VARCHAR(50),
    entity_type VARCHAR(100),
    entity_id INT,
    title VARCHAR(255),
    message TEXT,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read)
);

-- ========== SEED DATA ==========
INSERT INTO config (id, currency, currency_symbol, decimal_separator, thousands_separator, decimal_count, 
                   default_rete_fuente, default_rete_ica, ganancia_nacional_percent, ganancia_urbano_percent,
                   maint_warning_kms, doc_warning_days)
VALUES (1, 'COP', '$', '.', ',', 2, 1.0, 1.0, 10.0, 15.0, 500, 30)
ON DUPLICATE KEY UPDATE id=1;

-- Create initial admin user (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO users (id, username, email, password_hash, role, active)
VALUES (1, 'admin', 'admin@celrapp.com', 
        '$2y$10$YIjlrHxjKpYvDOE0d0H.nuKD.6C2TdFxqL0T4d.zWvW0W3U.Y3pZK', 
        'admin', 1)
ON DUPLICATE KEY UPDATE id=1;

-- ========== INDEXES FOR PERFORMANCE ==========
-- These are critical for query performance
CREATE INDEX idx_trips_vehicle_status ON trips(vehicle_id, status);
CREATE INDEX idx_trips_driver_status ON trips(driver_id, status);
CREATE INDEX idx_expenses_vehicle_date ON expenses(vehicle_id, date);
CREATE INDEX idx_expenses_trip_date ON expenses(trip_id, date);

-- Commit
COMMIT;
```

---

## Instrucciones de Uso

### 1. Respaldar base de datos actual
```bash
mysqldump -u root celr_app > backup_celr_app_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Ejecutar nuevo schema (opción A - Limpio)
```bash
mysql -u root < database_CORRECTED.sql
```

### 3. Ejecutar migraciones (opción B - Preservar datos)
Ver archivo `MIGRATIONS_REQUIRED.sql`

### 4. Verificar integridad
```sql
SELECT 'Tablas' as 'Check', COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'celr_app';
SELECT 'Usuarios' as 'Check', COUNT(*) as count FROM users;
SELECT 'Config' as 'Check', COUNT(*) as count FROM config;
```

---

## Cambios Realizados (Resumen)

✅ Eliminadas líneas duplicadas en `manifest_number`
✅ Eliminadas líneas incompletas
✅ Agregada columna `status` a trips
✅ Agregada columna `paid_by` a expenses
✅ Agregadas todas las columnas faltantes
✅ Creadas tablas faltantes
✅ Mejorada integridad referencial con FK
✅ Agregados índices para performance
✅ Documentado seed data inicial

---
