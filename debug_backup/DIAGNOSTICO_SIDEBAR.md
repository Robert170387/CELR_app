# 🔧 Diagnóstico del Sidebar - CELR App

## Problema Reportado
El sidebar no despliega los menús "Gestión Operativa" y "Configuración".

## Correcciones Aplicadas

### 1. ✅ Alpine.js Collapse Plugin
- **Archivo**: `includes/header.php`
- **Cambio**: Agregado el plugin `@alpinejs/collapse` antes de Alpine.js
- **Código**:
```html
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

### 2. ✅ Script de Debug
- **Archivo**: `js/alpine_init.js` (NUEVO)
- **Propósito**: Verificar que Alpine.js y el plugin Collapse se carguen correctamente
- **Función de Debug**: `window.debugSidebar()` disponible en consola

### 3. ✅ Página de Prueba
- **Archivo**: `test_alpine.html` (NUEVO)
- **URL**: http://localhost/CELR_app/test_alpine.html
- **Propósito**: Verificar que Alpine.js funciona independientemente del resto de la app

## 📋 Pasos para Verificar

### Paso 1: Probar la Página de Test
1. Abrir en el navegador: `http://localhost/CELR_app/test_alpine.html`
2. Verificar que aparezca el mensaje: "✅ Alpine.js inicializado correctamente"
3. Hacer clic en los menús "Gestión Operativa" y "Configuración"
4. **Resultado Esperado**: Los menús deben expandirse y contraerse con animación suave

### Paso 2: Verificar el Dashboard
1. Abrir: `http://localhost/CELR_app/index.php`
2. Abrir la Consola del Navegador (F12)
3. Buscar estos mensajes:
   - `✓ Alpine.js loaded successfully`
   - `✓ Alpine.js initialized`
4. Ejecutar en consola: `debugSidebar()`
5. Verificar que no haya errores en rojo

### Paso 3: Probar el Sidebar
1. En el sidebar izquierdo, hacer clic en "Gestión Operativa"
2. Hacer clic en "Configuración"
3. **Resultado Esperado**: Los menús deben expandirse mostrando sus sub-items

## 🐛 Si el Problema Persiste

### Verificar en Consola del Navegador (F12)

#### Errores Comunes:

**Error 1**: `Alpine is not defined`
- **Causa**: Alpine.js no se cargó desde el CDN
- **Solución**: Verificar conexión a internet o usar versión local

**Error 2**: `x-collapse is not a valid directive`
- **Causa**: El plugin Collapse no se registró
- **Solución**: Verificar que el script del plugin se carga ANTES de Alpine.js

**Error 3**: `Cannot read property 'open' of undefined`
- **Causa**: El componente Alpine no se inicializó en el elemento
- **Solución**: Verificar que el atributo `x-data` esté presente

### Comandos de Debug en Consola

```javascript
// Verificar versión de Alpine
Alpine.version

// Ver estado de los menús
debugSidebar()

// Verificar si el plugin Collapse está cargado
typeof AlpineCollapse !== 'undefined'

// Forzar apertura de un menú (ejemplo)
Alpine.$data(document.querySelector('[x-data*="open"]')).open = true
```

## 📁 Archivos Modificados

1. `includes/header.php` - Agregado plugin Collapse y script de debug
2. `js/alpine_init.js` - NUEVO - Helper de inicialización
3. `test_alpine.html` - NUEVO - Página de prueba independiente

## 🔍 Información Técnica

### Orden de Carga de Scripts
```
1. Chart.js
2. Alpine Collapse Plugin (defer)
3. Alpine.js Core (defer)
4. Alpine Init Helper
5. Form Validation
6. Leaflet (mapa)
7. Form Persistence
```

### Estructura del Sidebar
```html
<div x-data="{ open: false }">
    <button @click="open = !open">Menu</button>
    <div x-show="open" x-collapse>
        <!-- Submenu items -->
    </div>
</div>
```

## ✅ Checklist de Verificación

- [ ] La página `test_alpine.html` funciona correctamente
- [ ] No hay errores en la consola del navegador
- [ ] El comando `debugSidebar()` muestra información de los menús
- [ ] Los menús del sidebar se expanden al hacer clic
- [ ] La animación de colapso es suave (no instantánea)

## 📞 Próximos Pasos

Si después de seguir estos pasos el problema persiste, por favor reportar:

1. **Navegador y versión** (Chrome, Firefox, Edge, etc.)
2. **Errores en consola** (captura de pantalla)
3. **Resultado del comando** `debugSidebar()`
4. **¿Funciona test_alpine.html?** (Sí/No)

---

**Última actualización**: 2026-02-01 19:05
**Versión Alpine.js**: 3.x.x
**Plugin Collapse**: 3.x.x
