# Plan de Migración y Correcciones a PHP 7.1.33


Tras la revisión del código, se identificó que la migración a PHP 7.1.33 **no está completa**. Existen varias incompatibilidades fatales y errores de sintaxis específicos de PHP 7.x que bloquean el funcionamiento correcto del sistema. A continuación se detallan los hallazgos y las soluciones propuestas.

## Estado Actual
* El sistema cuenta con directivas e incompatibilidades eliminadas a partir de PHP 5.4 y PHP 7.0 que aún se encuentran en el código.
* Gran parte del código usa "short open tags" (`<?`), lo que en PHP 7.1 con la configuración por defecto (`short_open_tag = Off`) genera múltiples errores de "unexpected end of file" y "unexpected 'else'".
* Se siguen incluyendo librerías legacy con modificadores y funciones que lanzan Fatal Errors en PHP 7.

## Cambios Propuestos

### 1. Reemplazar `session_register()`
La función `session_register()` fue eliminada en PHP 5.4.
* **Archivos afectados:** `administrador/FRONT/adm_con_control_1.0.php`, `administrador/FRONT/adm_con_control_1.1.php` y `auditoria/LOGICA/adm_con_control_1.1.php`.
* **Acción:** Reemplazar llamadas como `session_register("Ses_Usu_Cod");` por la asignación directa al arreglo superglobal: `$_SESSION["Ses_Usu_Cod"] = $Ses_Usu_Cod;`.

### 2. Corregir Asignación por Referencia en Instanciación (`&new`)
PHP 7 eliminó la sintaxis de asignación por referencia al instanciar (`&new`), lo que actualmente arroja `Parse error: syntax error, unexpected 'new'`.
* **Archivos afectados:** `administrador/FRONT/adm_con_treemenu.php` (y sus variantes `_p.php` y `_adm_1.0_p.php`), así como copias en `Librerias/config.php` y `mascaras`.
* **Acción:** Cambiar `$var = &new Clase();` a `$var = new Class();`.

### 3. Eliminar Superglobales como Parámetros de Funciones
Desde PHP 7, las variables superglobales (`$_POST`, `$_GET`, etc.) no pueden ser usadas como parámetros en declaraciones de funciones, causando un `Fatal error`.
* **Archivos afectados:** 
    * `administrador/LOGICA/adm_log_soporte.php` (`function tomarTicket($_POST, ...)`)
    * `compras/LOGICA/requisiciones/index.php` (`function getRequisitores($_POST)`)
* **Acción:** Renombrar el parámetro `$_POST` en las firmas de dichas funciones (ej. `$postData`) y actualizar su uso interno.

### 4. Reemplazar mPDF Legacy (Modificador `/e` en `preg_replace`)
La librería antigua en `Librerias/MPDF57/mpdf.php` utiliza el modificador `/e` en `preg_replace()`, eliminado en PHP 7.0. Aunque el `composer.json` incluye mPDF 7, varios archivos aún incluyen y llaman al código antiguo directamente.
* **Archivos afectados:** `activosfijos/FRONT/act_pri_activo_1.0.php`, `activosfijos/FRONT/act_pri_custodio_2.0.php`, `rrhh/FRONT/rhu_pri_personal_1.0.php`, `auditoria/FRONT/aud_mod_dashboard_tareas_1.0.php`.
* **Acción:** Actualizar los archivos para que utilicen la nueva librería provista por Composer (`use Mpdf\Mpdf; $mpdf = new Mpdf();`) y eliminar el `include("../../Librerias/MPDF57/mpdf.php");`. Alternativamente, si requiere esfuerzo mayor de refactor, corregir manualmente el modificador `/e` en el mPDF legacy utilizando `preg_replace_callback()`.

### 5. Actualizar `eregi_replace()` a `preg_replace()`
La función `eregi_replace()` fue eliminada en PHP 7.0.
* **Archivos afectados:** `Librerias/FactElect/XmlSecurity/Key/P12.php`
* **Acción:** Cambiar `eregi_replace( "[\n]",'',$cert)` por `preg_replace('/[\n]/i', '', $cert)`.

### 6. Configuración de PHP (Short Open Tags)
* **Acción:** Dado que cientos de archivos (aprox. 60 usos por archivo) emplean `<?` en lugar de `<?php`, se sugiere fuertemente habilitar `short_open_tag = On` en el archivo `php.ini` del servidor en lugar de modificar masivamente el código.

*(Nota: Directivas como `each()` aún funcionan en PHP 7.1.33 ya que su deprecación arroja E_DEPRECATED en 7.2 y fue removida recién en 8.0, por lo que no impiden la migración a la versión 7.1 solicitada).*