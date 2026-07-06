-- Migration 001: Migrate Expense Categories to Hierarchical System
-- 
-- This migration:
-- 1. Adds category_id and category_name columns to expenses table
-- 2. Migrates existing slug-based category references to the new system
-- 3. Maps legacy category values to new hierarchical slugs
-- 4. Adds foreign key constraint
--
-- NOTE: Run this AFTER running enhanced_database_schema.sql (which creates the categories)

-- Step 1: Add new columns to expenses table (MySQL-compatible)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND COLUMN_NAME = 'category_id');
SET @sql := IF(@exists = 0, 'ALTER TABLE expenses ADD COLUMN category_id INT NULL AFTER vehicle_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND COLUMN_NAME = 'category_name');
SET @sql := IF(@exists = 0, 'ALTER TABLE expenses ADD COLUMN category_name VARCHAR(255) NULL AFTER category_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND INDEX_NAME = 'idx_expenses_category_id');
SET @sql := IF(@exists = 0, 'ALTER TABLE expenses ADD INDEX idx_expenses_category_id (category_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Migrate existing data - direct slug match
UPDATE expenses e
  LEFT JOIN expense_categories ec ON e.category = ec.slug
  SET e.category_id = ec.id,
      e.category_name = COALESCE(ec.name, e.category)
  WHERE e.category_id IS NULL;

-- Step 3: Case-insensitive slug match
UPDATE expenses e
  LEFT JOIN expense_categories ec ON LOWER(e.category) = LOWER(ec.slug)
  SET e.category_id = ec.id,
      e.category_name = COALESCE(ec.name, e.category)
  WHERE e.category_id IS NULL;

-- Step 4: Explicit mapping for legacy category values that don't match new slugs
UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'peaje_efectivo' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'peaje_efectivo' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('peajes', 'peaje');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'descarrozada' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'descarrozada' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('desencarrozada', 'descarrozada');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'carrozada' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'carrozada' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('encarrozada', 'carrozada');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'comision_viaje_conductor' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'comision_viaje_conductor' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('comision', 'comision_contado');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'repuestos' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'repuestos' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('r_repuestos', 'repuestos');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'mantenimiento_y_repuestos' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'mantenimiento_y_repuestos' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category = 'mantenimiento';

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'auxilio_transporte_conductor' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'auxilio_transporte_conductor' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('viaticos', 'auxilio_transporte');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'hotel' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'hotel' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('hospedaje', 'hotel');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'parqueadero' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'parqueadero' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('parqueadero_viaje', 'parqueadero_fijo', 'parqueadero');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'otros_viajes' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'otros_viajes' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category = 'otros_viaje';

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'montallantas' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'montallantas' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('montaje_llantas', 'montallantas');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'aceite_filtros' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'aceite_filtros' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category = 'aceite';

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'salario_basico_conductor' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'salario_basico_conductor' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('sueldo', 'salario_basico_conductor');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'engrace' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'engrace' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category IN ('engrase', 'engrace');

UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'soat' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'soat' LIMIT 1)
  WHERE e.category_id IS NULL AND e.category = 'soat';

-- Step 5: Catch-all for any remaining unmigrated rows - map to 'otros_viajes' as fallback
UPDATE expenses e
  SET e.category_id = (SELECT id FROM expense_categories WHERE slug = 'otros_viajes' LIMIT 1),
      e.category_name = (SELECT name FROM expense_categories WHERE slug = 'otros_viajes' LIMIT 1)
  WHERE e.category_id IS NULL;

-- Step 6: Add foreign key constraint
-- Uncomment after verifying all data is migrated
-- ALTER TABLE expenses
--   ADD CONSTRAINT fk_expenses_category
--   FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL;

-- Step 7: Verify migration
SELECT 
  'Migration complete' as status,
  COUNT(*) as total_expenses,
  SUM(CASE WHEN e.category_id IS NOT NULL THEN 1 ELSE 0 END) as migrated,
  SUM(CASE WHEN e.category_id IS NULL THEN 1 ELSE 0 END) as unmigrated
FROM expenses e;
