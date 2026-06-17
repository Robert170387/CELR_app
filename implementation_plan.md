# Plan de Refactorización de Arquitectura CELR-App

Este documento detalla el plan para profesionalizar la arquitectura de la aplicación, pasando de un estructura plana a un modelo MVC (Modelo-Vista-Controlador), centralizando migraciones y mejorando la auditoría.

## 1. Nueva Estructura de Directorios
El objetivo es separar la lógica de negocio (PHP) de la vista (HTML) y la configuración.

```text
/CELR_app
├── app/                  <-- [NUEVO] Núcleo de la aplicación
│   ├── Core/             <-- Clases base (Database, Controller, Router)
│   ├── Controllers/      <-- Lógica de negocio (reemplaza save_*.php)
│   ├── Models/           <-- Acceso a datos (Consultas SQL encapsuladas)
│   ├── Services/         <-- Lógica compleja (Cálculos financieros, etc.)
│   └── Helpers/          <-- Funciones auxiliares (Auditoría, Formatos)
├── migrations/           <-- [NUEVO] Scripts de base de datos controlados
├── public/               <-- (Opcional futuro) Punto de entrada web
├── includes/             <-- Mantener por compatibilidad (legacy)
└── ... archivos raíz
```

## 2. Refactorización a MVC (Controladores)
Centralizaremos la lógica dispersa en `save_*.php` en Controladores organizados por módulo.

**Estrategia de Migración Suave (Shim Strategy):**
Para no romper los formularios existentes que apuntan a `save_trip.php`:
1.  Crear `app/Controllers/TripController.php`.
2.  Mover la lógica de `save_trip.php` al método `save()` del controlador.
3.  Modificar `save_trip.php` para que solo sea un "puente" que llame al controlador.

## 3. Sistema de Migraciones Unificado
Crearemos un gestor que:
1.  Verifique la tabla `migrations_log`.
2.  Escanee la carpeta `migrations/` buscando archivos `.sql` o `.php` nuevos.
3.  Ejecute las migraciones en orden numérico/fecha.
4.  Registre el éxito en la base de datos.
5.  **Bonus:** Un script `migrate.php` que se puede ejecutar desde navegador o consola.

## 4. Auditoría y Salud del Sistema
1.  **AuditService:** Una clase estática o Singleton en `app/Helpers/Audit.php` que estandarice `logAction`.
2.  **HealthCheck:** Integración de `db_diag.php` en un dashboard visual.
