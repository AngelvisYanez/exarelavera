# Resumen de Migración a PHP 8.2 - Sistema EXA (ERP)

Este documento resume los cambios realizados recientemente para lograr la compatibilidad completa del sistema EXA con **PHP 8.2**, resolviendo errores críticos (como el HTTP 500) y advertencias de deprecación que impedían el correcto funcionamiento del sistema.

## 1. Correcciones de Constructores (Estilo PHP 4 a PHP 8)
En PHP 8.0, se eliminó el soporte para constructores con el mismo nombre que la clase (estilo PHP 4). Esto causaba que las clases no se inicializaran correctamente.
- **`skins/php/TreeMenu.php`**: 
  - Se reemplazó `function TreeMenu()` por `public function __construct()`.
  - Se reemplazó `function TreeMenuItem()` por `public function __construct()`.
  *Impacto:* Esto resolvió el problema crítico donde el menú lateral de módulos no se renderizaba porque las propiedades de los ítems del menú (`label`, `itemType`, `href`, `icon`) quedaban nulas al no invocarse el constructor.

## 2. Propiedades Dinámicas (Atributo `#[AllowDynamicProperties]`)
En PHP 8.2, la creación de propiedades dinámicas no declaradas está deprecada y emite advertencias (`Deprecated: Creation of dynamic property...`).
- **`skins/php/TreeMenu.php`**: 
  - Se añadió el atributo `#[AllowDynamicProperties]` a las clases `TreeMenu` y `TreeMenuItem` para permitir la asignación dinámica de propiedades sin emitir advertencias que corrompían la salida HTML.

## 3. Manejo de Errores de MySQLi
PHP 8.1 cambió el modo de reporte de errores predeterminado de MySQLi a lanzar excepciones (`MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR`), lo cual rompía el código legacy que dependía de silenciamiento de errores en consultas SQL.
- **`DATA/MysqlConexion.php`** y **`DATA/MysqlDatos.php`**:
  - Se añadió `mysqli_report(MYSQLI_REPORT_OFF);` antes de establecer las conexiones.
  *Impacto:* El sistema vuelve a manejar los errores de base de datos a través de valores de retorno en lugar de excepciones, restaurando el funcionamiento del login y todas las consultas antiguas.

## 4. Reemplazo de Codificación UTF-8
Las funciones `utf8_encode()` y `utf8_decode()` fueron deprecadas en PHP 8.2.
- Se refactorizó masivamente en los archivos lógicos y procedimientos almacenados (como `Librerias/procedimientos/almacenados_standar.php` y `administrador/LOGICA/adm_log_menu_tree.php`).
- Reemplazos realizados:
  - `utf8_encode($string)` $\rightarrow$ `mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1')`
  - `utf8_decode($string)` $\rightarrow$ `mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8')`

## 5. Filtros de Saneamiento Deprecados
El filtro `FILTER_SANITIZE_STRING` fue deprecado en PHP 8.1.
- **`administrador/FRONT/home.php`**: 
  - Se actualizó el uso de `filter_var(..., FILTER_SANITIZE_STRING)` a `filter_var(..., FILTER_SANITIZE_FULL_SPECIAL_CHARS)`.

## 6. Configuración de Codificación (Acentos rotos)
A partir de PHP 5.6, la directiva `default_charset` cambió por defecto a `UTF-8`. En PHP 8, esto causaba que PHP forzara el encabezado HTTP `Content-Type: text/html; charset=UTF-8`, anulando las meta-etiquetas `<meta charset="iso8859-1">` del frontend e invalidando los caracteres con tildes (como "Administración") provenientes de la base de datos (latin1).
- **`DATA/MysqlConexion.php`**:
  - Se añadió `ini_set('default_charset', 'iso-8859-1');` para obligar a PHP a respetar la codificación ISO-8859-1 en las cabeceras HTTP, restaurando la correcta visualización de eñes y acentos.

## 7. Deprecación de `each()` (PHP 7.2 / PHP 8.0)
La función `each()` fue eliminada en PHP 8.0.
- Se ha refactorizado el uso residual de estructuras `while(list($key, $val) = each($array))` reemplazándolas por construcciones modernas `foreach($array as $key => $val)`.

---
**Estado actual:** El sistema es capaz de levantar sesión, consultar la base de datos sin lanzar excepciones no controladas y renderizar el árbol de módulos del menú principal sin emitir advertencias de deprecación en PHP 8.2.
