# CELR-App: Batería de Pruebas Integrales

## 📋 Resumen Ejecutivo

Se ha implementado una **Suite de Pruebas Automatizadas** completa para validar la salud del sistema CELR-App antes del despliegue masivo a conductores.

## 🎯 Componentes Implementados

### 1. **Test Runner** (`tests/test_runner.php`)
Script principal para ejecución CLI de todas las pruebas:
- ✓ 24 pruebas distribuidas en 4 categorías
- ✓ Reporte detallado con porcentaje de éxito
- ✓ Archivos de log guardados automáticamente
- ✓ Compatible con ejecución por cron

**Uso:**
```bash
php tests/test_runner.php
```

### 2. **Dashboard Visual** (`tests/index.php`)
Interfaz web para monitoreo en tiempo real:
- ✓ Gráficos de progreso animados
- ✓ Historial de ejecuciones
- ✓ Detalle por categoría de prueba
- ✓ Indicadores de rendimiento (ms)

**Acceso:** `http://localhost/CELR_app/tests/`

### 3. **Test API** (`tests/test_api.php`)
API REST para integración con CI/CD:
- ✓ Respuesta en JSON
- ✓ Ejecutable vía AJAX
- ✓ Métricas de tiempo incluidas

**Endpoints:**
```
GET /tests/test_api.php?action=run    # Ejecutar pruebas
GET /tests/test_api.php?action=status # Verificar estado
```

## 🧪 Categorías de Prueba

### **1. Salud de Base de Datos** [DB]
Valida estructura del esquema:
- ✓ Tablas: trips, expenses, fuel_vouchers, locations, audit_logs
- ✓ Columnas migradas: origin_state_id, origin_city_id, destination_state_id, destination_city_id
- ✓ Índices de ubicaciones (migración 042)
- ✓ Integridad referencial (sin registros huérfanos)

**7 pruebas incluidas**

### **2. Flujo MVC** [MVC]
Valida arquitectura y flujo de datos:
- ✓ Existencia de shims (save_trip.php, save_expense.php)
- ✓ Controladores operativos
- ✓ Simulación de inserción de gasto
- ✓ Vinculación correcta con tabla trips
- ✓ Accesibilidad de route_profitability.php

**6 pruebas incluidas**

### **3. Trazabilidad y Auditoría** [AUDIT]
Valida sistema de logs:
- ✓ Clase Audit operativa
- ✓ API api_audit_logs.php responde
- ✓ Logs estructurados y legibles
- ✓ Inserción automática de auditoría

**5 pruebas incluidas**

### **4. Rendimiento** [PERF]
Valida tiempos de respuesta:
- ✓ api_locations.php < 500ms
- ✓ Carga de ciudades por departamento < 300ms
- ✓ Queries usando índices (EXPLAIN)
- ✓ Migraciones aplicadas

**4 pruebas incluidas**

## 📊 Criterios de Aprobación

| Porcentaje | Estado | Recomendación |
|------------|--------|---------------|
| 100% | ✅ **PASS** | Listo para despliegue |
| 80-99% | ⚠️ **WARNING** | Revisar módulos fallidos |
| <80% | ❌ **FAIL** | NO desplegar - Corregir errores |

## 🚀 Guía de Uso

### **Ejecución Rápida (CLI)**
```bash
# Navegar al directorio
cd C:\xampp\htdocs\CELR_app

# Ejecutar pruebas
php tests/test_runner.php
```

### **Dashboard Web**
1. Acceder a `http://localhost/CELR_app/tests/`
2. Click en "Ejecutar Pruebas"
3. Revisar resultados por categoría
4. Descargar reporte si es necesario

### **Integración CI/CD**
```bash
# Ejecutar y verificar éxito
php tests/test_runner.php | grep "TODAS LAS PRUEBAS PASARON"
if [ $? -eq 0 ]; then
    echo "✅ Despliegue aprobado"
else
    echo "❌ Despliegue cancelado"
    exit 1
fi
```

## 📁 Estructura de Archivos

```
tests/
├── index.php              # Dashboard web
├── test_runner.php        # Script CLI principal
├── test_api.php           # API REST para pruebas
├── reports/               # Reportes generados
│   └── 2026-02-01_14-30-00_report.txt
└── README.md              # Esta documentación
```

## 🔍 Reportes Generados

Los reportes se guardan automáticamente en `tests/reports/` con formato:
```
CELR-APP BATERÍA DE PRUEBAS - REPORTE
Fecha: 2026-02-01 14:30:00
====================================

Resultado General: PASS
Porcentaje: 100%
Tiempo: 2.34s

Detalles:
[✓ PASS] [DB] Verificar tabla trips
[✓ PASS] [DB] Verificar tabla expenses
...
```

## 🛠️ Mantenimiento

### **Agregar Nuevas Pruebas**

1. Editar `test_runner.php` o `test_api.php`
2. Agregar función de prueba en la categoría correspondiente:
```php
['name' => 'Mi Nueva Prueba', 'fn' => function() {
    // Lógica de prueba
    return ['pass' => true, 'details' => 'Éxito'];
}]
```

### **Personalizar Límites de Tiempo**

Editar constantes en `test_api.php`:
```php
// Cambiar 500ms a valor deseado
'time' => $time < 500  // Para api_locations.php
```

## ⚠️ Notas Importantes

1. **Datos de Prueba**: Las pruebas insertan registros temporales que se eliminan automáticamente (usando transacciones rollback)

2. **Permisos**: Asegurar que el usuario de BD tenga permisos para:
   - Leer estructura de tablas (SHOW COLUMNS, SHOW INDEX)
   - Insertar/eliminar registros de prueba
   - Crear archivos en `tests/reports/`

3. **Entorno**: Las pruebas están diseñadas para ejecutarse en el entorno de desarrollo antes del despliegue

## 📞 Soporte

Si alguna prueba falla:
1. Revisar mensaje de error detallado
2. Verificar conectividad a base de datos
3. Confirmar que todas las migraciones están aplicadas
4. Revisar logs en `tests/reports/`

---

**Versión**: 1.0  
**Última actualización**: Febrero 2026  
**Compatible con**: CELR-App v2.0
