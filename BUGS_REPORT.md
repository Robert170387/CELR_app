# 🔴 REPORTE EXHAUSTIVO DE BUGS Y PROBLEMAS - CELR_APP

## Fecha de Análisis: 2026-06-19
## Estado: **CRÍTICO** - Se encontraron 27 problemas significativos

---

## 📋 ÍNDICE RÁPIDO
1. [CRÍTICOS (🔴)](#críticos) - 8 problemas
2. [ALTOS (🟠)](#altos) - 10 problemas  
3. [MEDIOS (🟡)](#medios) - 6 problemas
4. [BAJOS (🟢)](#bajos) - 3 problemas

---

## 🔴 CRÍTICOS {#críticos}

### 1. **DUPLICACIÓN DE COLUMNAS EN DATABASE.SQL**
**Archivo:** `database.sql` (línea 85-87)
**Problema:** El campo `manifest_number` aparece duplicado
```sql
-- LÍNEA 85-87:
manifest_number VARCHAR(100),
manifest_number VARCHAR(100),
```
**Impacto:** Esto causa error al ejecutar el SQL. La segunda columna será rechazada por MySQL.
**Solución:** Eliminar la línea duplicada.

---

### 2. **LÍNEA INCOMPLETA EN DATABASE.SQL**
**Archivo:** `database.sql` (línea 23-24)
**Problema:** Hay una línea incompleta
```sql
CREATE TABLE IF NOT EXISTS locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1
);

    active TINYINT(1) DEFAULT 1    <-- LÍNEA INCOMPLETA SIN ESTRUCTURA
);
```
**Impacto:** Error de sintaxis SQL al crear la base de datos.
**Solución:** Eliminar la línea incompleta (línea 24).

---

### 3. **FALTA LA COLUMNA `paid_by` EN TABLA EXPENSES**
**Archivo:** `database.sql` (líneas 137-153)
**Problema:** La tabla `expenses` NO tiene la columna `paid_by` pero el código la usa:
```php
// En ExpenseController.php y functions.php
$stmtExpenses->execute([$tripId, 'Conductor']); // ← paid_by se usa como filtro
```
**Columnas Faltantes en `expenses`:**
- `paid_by` (VARCHAR) - Conductor o Propietario
- `payment_method` (VARCHAR)
- `eds_name`, `eds_location`, `eds_state_id`, `eds_city_id`
- `invoice_number`, `invoice_status`
- `gallons`, `price_per_gallon`
- `receipt_photo`
- `department`, `city`
- `created_by`
- `supplier_id`

**Impacto:** Errores 500 al guardar gastos. Las columnas no existen en BD.
**Solución:** Actualizar schema de `expenses`.

---

### 4. **TABLA `categories` NO EXISTE**
**Archivo:** `database.sql`
**Problema:** No hay definición de tabla `categories` pero se referencia en:
- `category_form.php`
- `category_details.php`
- `categories.php`

**Impacto:** Error al acceder a módulo de categorías.
**Solución:** Crear tabla `categories`.

---

### 5. **FALTA COLUMNA `status` EN TABLA TRIPS**
**Archivo:** `database.sql` (línea 68)
**Problema:** La tabla `trips` NO tiene columna `status` pero el código la usa constantemente:
```php
WHERE status = 'En Progreso'
WHERE status NOT IN ('Finalizado', 'Cancelado')
```
**Impacto:** Consultas SQL fallan frecuentemente.
**Solución:** Agregar columna `status` a tabla `trips`.

---

### 6. **FALTA TABLA `users` Y COLUMNA `created_by` EN AUDITRÍA**
**Archivo:** `database.sql`
**Problema:** No existe tabla `users` pero se usa en:
- Autenticación (`includes/auth.php`)
- Auditría (`ExpenseController.php` - `created_by`)
- Reportes

**Impacto:** Login no funciona, auditría incompleta.
**Solución:** Crear tabla `users` con campos: `id, username, email, password_hash, role, active, created_at`.

---

### 7. **LÓGICA INCOMPLETA EN `calculateTripFinancials`**
**Archivo:** `includes/functions.php` (línea 156-183)
**Problema:** 
- Intenta actualizar columnas que NO existen: `value_iva`, `value_rete_iva`
- El schema NO tiene estas columnas
- El SQL UPDATE fallará

```php
$updateSql = "UPDATE trips SET 
    ...
    value_iva = ?,           // ← No existe
    value_rete_iva = ?,      // ← No existe
    ...
```
**Impacto:** Función de recálculo de financieros falla.
**Solución:** Alinear columnas o eliminar referencias innecesarias.

---

### 8. **TABLA `settlements` NO EXISTE**
**Archivo:** `database.sql`
**Problema:** No hay definición de tabla `settlements` pero se referencia:
- `settlements.php`
- `SettlementController.php`
- Lógica de liquidaciones completa

**Impacto:** Módulo de liquidaciones no funciona.
**Solución:** Crear tabla `settlements`.

---

## 🟠 ALTOS {#altos}

### 9. **FALTA TABLA `audit_logs`**
**Archivo:** Varias referencias
**Problema:** No existe tabla para almacenar logs de auditoría
- `api_audit_logs.php` intenta consultarla
- `Audit::log()` intenta insertar

**Impacto:** Sistema de auditoría no funciona.
**Solución:** Crear tabla `audit_logs`.

---

### 10. **VALIDACIÓN INCOMPLETA DE FINANCIEROS**
**Archivo:** `includes/functions.php` (línea 82)
**Problema:** `validateFinancials()` solo valida negativos, no valida:
- Valores muy altos (máximos razonables)
- Decimales más de 2 lugares
- División por cero en cálculos

**Impacto:** Datos corruptos pueden ser guardados.

---

### 11. **FALTA SEGURIDAD EN SQL DINÁMICO**
**Archivo:** `app/Controllers/ApiController.php` (línea 54, 68, 113)
**Problema:** Algunas consultas usan valores directamente en SQL:
```php
$stmt->execute([$country, $state]); // OK (preparada)
// Pero en algunos lugares:
$stmtCfg = $pdo->query("SELECT doc_warning_days FROM config LIMIT 1");
// Buena práctica: usar prepare()
```

**Impacto:** Potencial SQL injection si no se valida entrada.

---

### 12. **FALTA TABLA `personnel` Y RELACIONES**
**Archivo:** `database.sql`
**Problema:** 
- Tabla `personnel` no está definida
- Se referencia en `personnel.php`, `PersonnelController.php`
- Schema tiene `drivers` pero código usa `personnel`

**Impacto:** CRUD de personal no funciona.

---

### 13. **FALTA TABLAS DE SOPORTE**
**Archivo:** `database.sql`
**Problema:** Faltan tablas:
- `clients` (para `clients.php`)
- `suppliers` (para `suppliers.php`)
- `socios` (para `socios.php`)
- `vehicles_maintenance` (para `maintenance_list.php`)
- `locations_hierarchy` (para jerarquía país/estado/ciudad)

**Impacto:** Múltiples módulos no funcionan.

---

### 14. **CONFIGURACIÓN INCOMPLETA DE LA TABLA `config`**
**Archivo:** `database.sql` (línea 9)
**Problema:** Faltan columnas en tabla `config`:
- `currency_symbol`
- `decimal_separator`
- `thousands_separator`
- `decimal_count`
- `maint_warning_kms`
- `doc_warning_days`

El código intenta acceder a estas en `includes/functions.php`:
```php
$stmt = $pdo->query("SELECT currency_symbol, decimal_separator, ... FROM config");
```

**Impacto:** Errores al formatear moneda y configuraciones.

---

### 15. **MISSING FOREIGN KEYS EN `trips`**
**Archivo:** `database.sql` (línea 68)
**Problema:** Faltan relaciones:
- No hay FK a `clients`
- No hay FK a `manifest_companies` (aunque se guarda ID)

**Impacto:** Integridad referencial comprometida.

---

### 16. **FUNCIÓN `setFlashMessage` INVOCADA SIN VALIDACIÓN**
**Archivo:** `app/Controllers/TripController.php` (línea 67, 114, etc.)
**Problema:** Se invoca `setFlashMessage()` pero está en `includes/functions.php`
- Si `functions.php` no está cargado primero, falla
- No hay try-catch para errores

**Impacto:** Errores silenciosos o logs inútiles.

---

### 17. **REDIRECT SIN HEADER CHECK**
**Archivo:** `app/Core/Controller.php`
**Problema:** Redirecciones sin verificar si headers ya fueron enviados
```php
header("Location: $url"); // Sin isset headers_sent()
```

**Impacto:** Warnings si hay output buffer anterior.

---

### 18. **TABLA `expenses` - FOREIGN KEY INCOMPLETO**
**Archivo:** `database.sql` (línea 151)
**Problema:**
```sql
FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
-- Falta ON DELETE behavior
```

**Impacto:** Integridad referencial débil.

---

## 🟡 MEDIOS {#medios}

### 19. **FALTA MANEJO DE TIMEZONE**
**Archivo:** Toda la aplicación
**Problema:** No hay configuración de timezone
- `date('Y-m-d')` usa timezone por defecto del servidor
- En BD pueden haber timestamps en diferente zona

**Solución:** Agregar `date_default_timezone_set('America/Bogota');` al inicio.

---

### 20. **VARIABLES GLOBALES `$pdo` NO SIEMPRE DISPONIBLES**
**Archivo:** `includes/functions.php` (línea 100)
**Problema:**
```php
function calculateTripFinancials($tripId) {
    global $pdo; // Asume que $pdo está definido globalmente
```

**Impacto:** Errores si la función se llama desde contexto donde `$pdo` no existe.

---

### 21. **CACHE IMPLEMENTATION INCOMPLETO**
**Archivo:** `config.php` (línea 7)
**Problema:** Intenta usar `Cache::remember()` pero `Cache.php` está vacío o incompleto
```php
require_once 'app/Core/Cache.php';
use App\Core\Cache;
$config = Cache::remember('system_config', ...);
```

**Impacto:** Cache no funciona, carga innecesaria de BD.

---

### 22. **FALTA VALIDACIÓN DE FECHAS**
**Archivo:** `includes/functions.php` (línea 45)
**Problema:** `validatePOST()` solo valida formato, no lógica de fechas:
- Fecha carga > fecha descarga?
- Fecha en el futuro?
- Fecha válida para operaciones?

**Solución:** Agregar validación de lógica temporal.

---

### 23. **AUDITRÍA SIN USUARIO**
**Archivo:** `app/Helpers/Audit.php`
**Problema:** `Audit::log()` intenta guardar `$userId` pero:
- Si usuario no está logueado, ¿qué valor usa?
- Si `$_SESSION['user_id']` no existe, falla

**Impacto:** Logs incompletos o errores.

---

### 24. **INCONSISTENCIA: `drivers` vs `personnel`**
**Archivo:** `database.sql` + código
**Problema:** 
- Schema define tabla `drivers`
- Código usa tabla `personnel`
- No hay sincronización

**Impacto:** Confusión en desarrollo, bugs de mismatch.

---

## 🟢 BAJOS {#bajos}

### 25. **COMENTARIOS OBSOLETOS EN SQL**
**Archivo:** `database.sql` (línea 80-81)
**Problema:**
```sql
manifest_company VARCHAR(255), -- Legacy (kept for historical data)
manifest_company_id INT, -- New relation
```
Guardando datos en dos lugares, genera duplicación.

---

### 26. **FUNCIÓN `isProduction()` MULTIPLE**
**Archivo:** Definida en 3 archivos:
- `includes/config_security.php`
- `update_celr.php`
- `CELR_App_Codebase_Context.md`

**Impacto:** Posible conflicto de definiciones.

---

### 27. **LOGS DIRECTORY NO EXISTE**
**Archivo:** `includes/config_security.php` (línea 18)
**Problema:**
```php
ini_set('error_log', __DIR__ . '/../logs/error.log');
// Directorio /logs puede no existir
```

**Impacto:** Warnings si directorio no existe (depende del SO).

---

## 🚨 IMPACTO GLOBAL

| Módulo | Estado | Problemas |
|--------|--------|-----------|
| Trips | ❌ NO FUNCIONA | Falta `status`, columnas financieras |
| Expenses | ❌ NO FUNCIONA | Falta `paid_by` y 8+ columnas |
| Settlements | ❌ NO FUNCIONA | Tabla inexistente |
| Categories | ❌ NO FUNCIONA | Tabla inexistente |
| Personnel | ❌ NO FUNCIONA | Tabla inexistente |
| Audit Logs | ⚠️ PARCIAL | Tabla inexistente |
| Config | ⚠️ PARCIAL | Columnas faltantes |
| Auth | ⚠️ PARCIAL | Tabla `users` faltante |

---

## 📝 PLAN DE CORRECCIÓN (PRIORIDAD)

### FASE 1: CRÍTICO (Hoy)
1. Eliminar duplicación en `database.sql`
2. Corregir líneas incompletas
3. Agregar columnas faltantes a `expenses`
4. Crear tabla `users`
5. Agregar `status` a tabla `trips`

### FASE 2: BLOQUEADOR (Este mes)
6. Crear tablas faltantes: `categories`, `settlements`, `personnel`, `clients`, `suppliers`
7. Crear tabla `audit_logs`
8. Actualizar tabla `config` con columnas completas

### FASE 3: OPTIMIZACIÓN
9. Implementar `Cache.php` correctamente
10. Sincronizar `drivers` ↔ `personnel`
11. Normalizar redirecciones y manejo de errores

---

## ✅ CHECKLIST DE VERIFICACIÓN

- [ ] SQL ejecuta sin errores
- [ ] Todas las tablas existen
- [ ] Todos los CRUD funcionan
- [ ] Login funciona
- [ ] Guardado de viajes funciona
- [ ] Guardado de gastos funciona
- [ ] Liquidaciones funciona
- [ ] Auditoría registra eventos
- [ ] No hay errores 500

---

**Generado por:** Sistema de Análisis Automático
**Urgencia:** 🔴 **CRÍTICA** - La aplicación no es funcional sin estas correcciones
