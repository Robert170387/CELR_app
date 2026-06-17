# CELR_app Technical Blueprint
**Documento Técnico de Referencia (Auditoría Legacy)**

Este documento detalla la estructura lógica, esquemas de base de datos y reglas de negocio extraídas del código base de la aplicación legacy `CELR_app`. Su propósito principal es servir de contexto para la inteligencia de NotebookLM durante la migración o paridad técnica hacia la nueva aplicación `TransporteColombia`.

---

## 1. Esquema de Base de Datos Real (Mapping de Hojas de Cálculo a SQL)

El sistema legacy migró la lógica de un archivo de hojas de cálculo (DB_INGRESOS, DB_GASTOS, Mantenimiento, etc.) a tablas relacionales en MySQL (`celr_app`).

### A. DB_INGRESOS → Tabla `trips` (Viajes / ODTs)
Registra la facturación y la información logística de cada flete.
*   **Primary Key:** `id` (INT, AUTO_INCREMENT)
*   **Foreign Keys Principales:** `vehicle_id` (vehicles), `driver_id` (personnel), `client_id` (clients), `manifest_company_id` (manifest_companies).
*   **Datos Clave:**
    *   `trip_type`: ENUM ('urbano', 'nacional', 'internacional')
    *   `flete_bruto`: DECIMAL(15,2) (Flete de manifiesto).
    *   `weight_declared` / `weight_dest`: DECIMAL(10,2) (Pesos para reliquidación).
    *   `percent_rete_fuente`, `percent_rete_ica`: DECIMAL(5,2).
    *   `percent_deductible_3`, `value_deductible_4`, `value_deductible_5`, `value_deductible_6`: DECIMAL(15,2) (Deducibles).
    *   `flete_neto`: DECIMAL(15,2) (Calculado dinámicamente).
    *   `advance_manifest`, `advance_owner`: DECIMAL(15,2) (Anticipos).
    *   `status`: ENUM ('En Progreso', 'Finalizado', 'Cancelado').

### B. DB_GASTOS → Tabla `expenses`
Registra todos los egresos operativos (ligados a un viaje) y administrativos.
*   **Primary Key:** `id` (INT)
*   **Foreign Keys:** `trip_id` (trips), `vehicle_id` (vehicles), `supplier_id` (suppliers).
*   **Datos Clave:**
    *   `category`: VARCHAR(50) (Ej. 'combustible', 'peajes', 'llantas').
    *   `paid_by`: ENUM ('Conductor', 'Propietario') - Determina si afecta la liquidación del conductor.
    *   `amount`: DECIMAL(15,2) (Costo total).
    *   `invoice_status`: ENUM ('Pendiente', 'Cancelada') - Equivale a PENDIENTE/PAGADO en la hoja CONFIG.
    *   `department` / `city`: VARCHAR(100) (Recientemente añadidos para tracking geográfico nativo sin normalizar estrictamente a loc_cities en caso de inputs libres).

### C. Mantenimiento → Tablas `maintenance_schedules` y `maintenance_logs`
Separa la programación teórica de los mantenimientos reales ejecutados.
*   **`maintenance_schedules`:**
    *   `task_name`: VARCHAR(255) (Ej. Cambio de Aceite).
    *   `interval_kms`: INT.
    *   `next_service_kms`: DECIMAL(15,2).
*   **`maintenance_logs`:**
    *   `performed_at_kms`: DECIMAL(15,2).
    *   `cost`: DECIMAL(15,2).

---

## 2. Mapeo de Flujos (ODT) - Cálculo Financiero de Viajes

El motor financiero de `CELR_app` se ejecuta a través de la función `calculateTripFinancials($tripId)` (ubicada en `includes/functions.php`). El cálculo es secuencial y estricto:

**Paso 1: Flete Liquidado (Ajuste por Peso)**
Si existe un peso destino, el flete bruto inicial se reliquida.
*   `flete_liquidado` = `(flete_bruto / weight_declared) * weight_dest`
*   *(Si no hay peso destino registrado, `flete_liquidado` = `flete_bruto`).*

**Paso 2: Cálculo de Deducibles (Retenciones y Descuentos)**
Todos los porcentajes se calculan sobre el `flete_liquidado`, ignorando el IVA.
*   `value_rf` = `flete_liquidado * (percent_rete_fuente / 100)`
*   `value_ica` = `flete_liquidado * (percent_rete_ica / 100)`
*   `value_d3` = `flete_liquidado * (percent_deductible_3 / 100)`
*   `total_deductibles` = `value_rf + value_ica + value_d3 + value_deductible_4 + value_deductible_5 + value_deductible_6`

**Paso 3: Flete Neto**
Es el valor que recibe la empresa antes de pagar la comisión del viaje.
*   `flete_neto` = `flete_liquidado - total_deductibles`

**Paso 4: Comisiones y Pago Final**
*   **Nacional:** La comisión se calcula directamente como `flete_neto * (commission_percent / 100)`.
*   **Urbano:** La comisión deduce los gastos del viaje primero: `(flete_neto - totalTripExpenses) * (commission_percent / 100)`.
*   **Pago Final Esperado (Cartera):** `flete_liquidado - advance_manifest - total_deductibles`.

---

## 3. Lógica de Persistencia de Gastos y UI (`expense_form.php`)

### Diagnóstico de Pérdida de Datos en Edición
Históricamente (antes de la reciente corrección), al intentar editar un gasto (ej. `?id=9`), el formulario aparecía vacío.
*   **Causa Raíz:** El formulario de PHP inyectaba los datos recuperados hacia un objeto de estado Reactivo gestionado por `Alpine.js` (`formData` y `loc`). Cuando el componente JS fallaba al inicializar (debido a problemas asíncronos consultando locaciones, APIs caídas, o variables no definidas como `department`), Alpine.js detenía su ciclo de vida. Al detenerse, los `<input x-model="formData.monto">` destruían el valor del DOM y quedaban en blanco.
*   **Solución Aplicada:** Se inyectaron condicionales y *fallbacks* en la inicialización (ej. `selectedState: <?php echo json_encode($expense['department'] ?? $eds_state_name ?? ''); ?>`). Si no hay departamento, hereda de forma segura el valor viejo (`eds_state_id`) previniendo bloqueos de UI. Además, se añadieron las columnas reales en backend en el controlador `ExpenseController.php`.

### Categorías y Estados (Hoja CONFIG)
*   Las categorías de la antigua "Hoja CONFIG" (Ej. Aceite - Filtros, Combustible) ahora viven en la tabla `expense_categories`. El frontend itera sobre ellas para llenar `<optgroup>`. Combustible tiene lógica UI condicional especial en JS.
*   Los estados de pago (PAGADO vs PENDIENTE) ahora son mapeados al campo ENUM `invoice_status` que acepta los valores de base de datos `'Cancelada'` y `'Pendiente'`.

---

## 4. Vinculación Geográfica y Proveedores

**Captura Geográfica Genérica**
El sistema ya no aísla la ubicación solo a "Combustible". Ahora cualquier ODT o Gasto puede tener un contexto geográfico (`department` y `city`).
1.  **Frontend:** Usa dos `select` anidados, poblados por una llamada Fetch a `api_locations.php` que consulta las tablas estándar `loc_states` y `loc_cities`.
2.  **Backend (`ExpenseController.php`):** Recibe strings simples por POST (`$_POST['department']`, `$_POST['city']`) y los persiste como texto libre en la tabla `expenses`. Esto permite flexibilidad si el catálogo de ciudades cambia o se ingresan datos manuales.
3.  **Proveedores (`suppliers`):** Los proveedores mantienen su propia normalización. Al asociar un proveedor a un gasto (Ej. PROV-0059 en Piedecuesta), el formulario precarga dinámicamente los campos geográficos del gasto si la lógica del front así lo requiere.

---

## 5. Ciclo de Vida de un "Cheque" (Flujo de Caja)

En la concepción original, la "Hoja Cheques" documentaba pagos diferidos o por verificar. En la arquitectura actual de `CELR_app`:

1.  **Ingresos (Cuentas por Cobrar):**
    *   No hay una tabla `cheques`. Los pagos de los clientes se registran en la tabla `trip_payments`.
    *   Un abono a un flete neto se crea con `payment_method = 'Cheque'`, referenciando un número de transacción en el campo `reference`.
2.  **Egresos (Pagos a Proveedores):**
    *   Los gastos que no se pagaron de contado se marcan con `invoice_status = 'Pendiente'`.
    *   Al liquidarse mediante transferencia o cheque bancario, se cambian a `'Cancelada'` y se actualiza su `payment_method` a `'Cheque'`.
3.  **Consolidación Mensual (Flujo de Caja):**
    *   El Dashboard (`index.php`) y reportes consolidan el dinero en tiempo real utilizando funciones de agregación (`SUM(amount) FROM trip_payments` vs `SUM(amount) FROM expenses`).
    *   El "Dinero en la Calle" se calcula restando del flete esperado todos los anticipos dados, permitiendo trazar cuánto efectivo está virtualizado en cheques o en manos de los conductores pendientes por legalizar.
