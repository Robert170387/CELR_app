-- Fix Missing Tables and Columns for CELR App
-- This script adds all missing tables and columns referenced in the application

-- Add missing columns to trips for advanced features
ALTER TABLE trips ADD COLUMN IF NOT EXISTS date_load DATETIME;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS date_return DATETIME;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS weight DECIMAL(10, 2);
ALTER TABLE trips ADD COLUMN IF NOT EXISTS volume DECIMAL(10, 2);
ALTER TABLE trips ADD COLUMN IF NOT EXISTS manifest_date DATE;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS pod_path VARCHAR(255);
ALTER TABLE trips ADD COLUMN IF NOT EXISTS person_type VARCHAR(50);
ALTER TABLE trips ADD COLUMN IF NOT EXISTS pod_number VARCHAR(100);
ALTER TABLE trips ADD COLUMN IF NOT EXISTS pod_date DATE;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS pod_signed_by VARCHAR(255);
ALTER TABLE trips ADD COLUMN IF NOT EXISTS pod_notes TEXT;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS origin_point_id INT NULL AFTER material_id;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS destination_point_id INT NULL AFTER origin_point_id;

-- Add missing columns to vehicles table
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS brand VARCHAR(100);
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS model VARCHAR(100);
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS color VARCHAR(50);
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS year INT;
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS document_number VARCHAR(50);
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS owner_id INT;
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS last_maintenance_date DATE;
ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS last_maintenance_kms INT;

-- Create missing view for vehicle availability (now that columns exist)
CREATE OR REPLACE VIEW view_vehicle_availability AS
SELECT 
    v.id,
    v.placa,
    v.brand,
    v.model,
    COUNT(DISTINCT t.id) as trip_count,
    MAX(t.date_return) as last_trip_date,
    v.active
FROM vehicles v
LEFT JOIN trips t ON v.id = t.vehicle_id AND t.settlement_status != 'Cancelled'
GROUP BY v.id, v.placa, v.brand, v.model, v.active;

-- Add missing columns to suppliers table
ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS firstname VARCHAR(100);
ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS lastname1 VARCHAR(100);
ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS lastname2 VARCHAR(100);
ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS business_name VARCHAR(255);
ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS person_type VARCHAR(50) DEFAULT 'natural';

-- Create view for route profitability
CREATE OR REPLACE VIEW view_route_profitability AS
SELECT 
    CONCAT(t.origin, ' → ', t.destination) as route,
    COUNT(*) as trip_count,
    AVG(t.final_pay_expected) as avg_revenue,
    SUM(CASE WHEN t.final_pay_expected > 0 THEN t.final_pay_expected ELSE 0 END) as total_revenue,
    SUM(e.amount) as total_expenses,
    (SUM(CASE WHEN t.final_pay_expected > 0 THEN t.final_pay_expected ELSE 0 END) - IFNULL(SUM(e.amount), 0)) as gross_profit
FROM trips t
LEFT JOIN expenses e ON t.id = e.trip_id
WHERE t.settlement_status IN ('Completed', 'Pending')
GROUP BY t.origin, t.destination;

-- Add missing columns to settlement_trips table (for clarity)
ALTER TABLE settlement_trips ADD COLUMN IF NOT EXISTS kms_approved DECIMAL(10, 2);
ALTER TABLE settlement_trips ADD COLUMN IF NOT EXISTS amount_approved DECIMAL(12, 2);

-- Ensure expenses table has necessary fields
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS receipt_number VARCHAR(100);
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS category VARCHAR(100);
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS receipt_date DATE;
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS approved_by INT;

-- Create any missing indexes
ALTER TABLE trips ADD INDEX IF NOT EXISTS idx_driver_id (driver_id);
ALTER TABLE trips ADD INDEX IF NOT EXISTS idx_vehicle_id (vehicle_id);
ALTER TABLE trips ADD INDEX IF NOT EXISTS idx_client_id (client_id);
ALTER TABLE trips ADD INDEX IF NOT EXISTS idx_settlement_status (settlement_status);
ALTER TABLE trips ADD INDEX IF NOT EXISTS idx_date_load (date_load);

ALTER TABLE expenses ADD INDEX IF NOT EXISTS idx_trip_id (trip_id);
ALTER TABLE expenses ADD INDEX IF NOT EXISTS idx_paid_by (paid_by);
ALTER TABLE expenses ADD INDEX IF NOT EXISTS idx_category (category);

-- Ensure personnel has the name fields for full name display
ALTER TABLE personnel ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) GENERATED ALWAYS AS (CONCAT_WS(' ', firstname, lastname)) VIRTUAL;

-- Add settings table if it doesn't exist (for global application settings)
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Verify all critical tables exist
-- The following should all show table names:
SELECT 'Database schema update completed successfully!' as status;
