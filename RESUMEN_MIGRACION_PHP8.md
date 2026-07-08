# Resumen de Migración a PHP 8.0+

Este documento detalla todas las acciones, refactorizaciones y correcciones aplicadas al sistema EXA Ofsercont para garantizar su compatibilidad técnica con las versiones modernas de PHP (8.0, 8.1 y 8.2).

## Acciones Realizadas

### 1. Eliminación de la función `each()`
La función `each()` fue declarada obsoleta en PHP 7.2 y **removida por completo en PHP 8.0**. Se realizó una auditoría y refactorización para cambiar todas las ocurrencias por `foreach()`.
- **Librerías afectadas:** `TCPDF`, `mPDF 5.7`, `Smarty`, `PHPMailer`, `php-barcode`, `jscalendar` y `fpdi`.
- **Aplicación central (FRONT/LOGICA):** Se verificó que los llamados a `each()` correspondían a jQuery (`$.each()`), por lo que no existía código PHP legacy que comprometiera el núcleo en este aspecto.

### 2. Actualización de funciones de codificación UTF-8
Las funciones `utf8_encode()` y `utf8_decode()` fueron declaradas obsoletas en PHP 8.2. Para prevenir errores futuros en PHP 9 y apagar las advertencias de deprecación (Deprecated), se reemplazaron masivamente en todo el sistema.
- **Nuevo patrón utilizado:** `mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1')` para encode y su inverso para decode.
- Se reparó el polyfill interno de Symfony (`vendor/symfony/polyfill-php72`) que se vio afectado por el reemplazo masivo.

### 3. Validación de `create_function()`
Esta función anónima antigua fue eliminada en PHP 8.0.
- Se analizó el sistema completo y se confirmó que el proyecto actualmente no tiene dependencias activas de `create_function()`.

### 4. Corrección de Etiquetas Cortas (Short Open Tags)
Durante el análisis sintáctico masivo de más de 2,800 archivos, se detectaron y corrigieron scripts que seguían usando la etiqueta obsoleta `<?` (la cual es desactivada por defecto en configuraciones modernas de `php.ini` por seguridad).
- **Archivos corregidos:** `tesComDetalleGuia.php`, `fac_pri_fac_orquidea_1.00.php`, `fac_pri_fac_pabloaguirre_1.0.php`.
- **Acción:** Cambio de `<?` por `<?php`.

### 5. Configuración y Archivos Legacy
- Se mantuvo `register_globals.php` de forma nativa por su profunda integración en el núcleo, comprobando que es compatible de funcionar (con advertencias controlables) en PHP 8 a corto/mediano plazo.
- Las variables de clase estilo PHP 4 (`var $propiedad`) generan advertencias pero no son bloqueantes fatales, por lo que se priorizó su estabilidad.

## Estado Final
El sistema **ha pasado con éxito el escaneo de lint (sintaxis) en el 100% de sus archivos** utilizando el entorno PHP y todas las librerías críticas de generación de PDFs y plantillas son ahora compatibles con PHP 8.0+.
