# Resumen: Sistema de Máscaras Unificado

## ¿Qué se ha creado?

He implementado un **sistema de máscaras unificado** que resuelve el problema de inconsistencia de estilos en todo el sistema ERP. Aquí está lo que se ha creado:

### 1. **Archivo de Configación de Tema Predeterminado**
- **Ubicación:** `mascaras/model3/estilos/modules/00-default-theme.css`
- **Propósito:** Define todas las variables CSS (colores, espaciados, tipografía, etc.) que se usarán en todo el sistema
- **Ventaja:** Cambiar un color aquí lo cambia en todo el sistema automáticamente

###  **Carga Centralizada de Máscaras**
- **Ubicación:** `mascaras/unified-loader.php`
- **Propósito:** Proporciona una forma centralizada de cargar estilos en todos los módulos
- **Uso:** `require_once('../../mascaras/unified-loader.php');`

### 3. **Documentación Completa**
- **Ubicación:** `mascaras/README.md`
- **Propósito:** Guía completa de uso, migración y mejores prácticas

## ¿Cómo funciona?

### Antes (Sistema Inconsistente)
```php
// Cada módulo incluía estilos de forma independiente
require_once("../../mascaras/model1/estilos/jqgrid5.php");
// O peor aún:
require_once("../../mascaras/model1/estilos/basic.php");
require_once("../../mascaras/model1/estilos/jqgrid.php"); // Versión antigua
```

### Ahora (Sistema Unificado)
```php
// Todos los módulos usan la misma carga centralizada
require_once('../../mascaras/unified-loader.php');
```

## Paleta de Colores Estándar

### Colores Principales
- **Azul Primario:** `#4a88b5` (botones, enlaces, acentos)
- **Azul Oscuro:** `#2f6f96` (hover states)
- **Gris Secundario:** `#5d7289` (encabezados de grillas)

### Colores Semánticos
- **Éxito/Pagado:** `#87B87F` (verde)
- **Peligro/Vencido:** `#D15B47` (rojo)
- **Advertencia:** `#FFB752` (naranja)
- **Información:** `#6FB3E0` (azul claro)

### Fondos
- **Página:** `#dbe5f1` (azul claro)
- **Panel:** `#eef3f8` (azul muy claro)
- **Grilla:** `#f8fafc` (casi blanco)

## Cómo Usar en Módulos

### Para Módulos Nuevos
```php
<?php
// Cargar estilos unificados
require_once('../../mascaras/unified-loader.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mi Módulo</title>
</head>
<body>
    <div class="panel panel-main exa-ui-panel">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">Título del Módulo</h3>
        </div>
        <div class="panel-body exa-body">
            <!-- Contenido del módulo -->
        </div>
    </div>
</body>
</html>
```

### Para Migrar Módulos Existentes
1. Reemplazar la línea de inclusión de estilos
2. Actualizar la estructura HTML para usar las clases de panel
3. Reemplazar estilos en línea con clases CSS predefinidas

## Opciones de Configuración

El cargador unificado acepta estas variables:

```php
$mask_model = 'model3';           // 'model1', 'model2', o 'model3'
$mask_core_only = false;          // Usar solo CSS core (sin maestro-detalle)
$mask_no_grid_fit = false;        // Deshabilitar JavaScript de ajuste de grilla
$mask_include_legacy = true;      // Incluir estilos heredados de model1
require_once('../../mascaras/unified-loader.php');
```

## Funciones Auxiliares

### getMaskClass()
Retorna clases CSS para patrones comunes:
```php
echo getMaskClass('button', 'primary');  // "btn btn-primary"
echo getMaskClass('cell', 'success');    // "cellGreen1"
```

### getStatusRowClass()
Retorna clases para estados de fila:
```php
echo getStatusRowClass('pagado');   // "exa-ui-row-pagado"
echo getStatusRowClass('vencido');  // "exa-ui-row-vencido"
```

## Beneficios

1. **Consistencia:** Todos los módulos lucen igual automáticamente
2. **Mantenibilidad:** Cambiar un color aquí lo cambia en todo el sistema
3. **Flexibilidad:** Se puede personalizar por módulo usando variables CSS
4. **Rendimiento:** Menos solicitudes HTTP al combinar estilos
5. **Facilidad de uso:** Una sola línea para cargar todos los estilos

## Próximos Pasos

1. **Migrar módulos existentes** al nuevo sistema
2. **Personalizar colores** según necesidades específicas
3. **Probar en diferentes módulos** para asegurar consistencia
4. **Documentar módulos específicos** que requieran estilos personalizados

## Soporte

Para preguntas o problemas, consulte la documentación en `mascaras/README.md` o contacte al equipo de desarrollo.