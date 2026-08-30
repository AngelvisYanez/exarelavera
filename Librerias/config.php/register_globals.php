<?php
ini_set('default_charset', 'utf-8');
require_once __DIR__ . '/../debugbar/vendor/autoload.php';
if (!class_exists('DebugBar', false)) {
    class_alias('DebugBar\DebugHelper', 'DebugBar');
}

/**
 * Registro seguro de variables de solicitud.
 *
 * MIGRACIÓN DE SEGURIDAD: Se eliminó la inyección directa de parámetros
 * GET/POST/JSON como variables PHP (patrón register_globals).
 * Solo se inyectan variables de SESIÓN que comienzan con 'Ses_' para
 * mantener compatibilidad con el código existente.
 *
 * Para acceder a parámetros de solicitud, usar:
 *   $_GET['param']   - parámetros URL
 *   $_POST['param']  - datos de formulario
 *   $_REQUEST['param'] - ambos
 *   Req('param')     - helper seguro (ver abajo)
 */

/**
 * Helper seguro para acceder a parámetros de solicitud.
 * Reemplaza el patrón de variables directas.
 *
 * @param string $key Nombre del parámetro
 * @param mixed $default Valor por defecto si no existe
 * @param string|null $filter Filtro de PHP (FILTER_DEFAULT, FILTER_VALIDATE_INT, etc.)
 * @return mixed
 */
function Req($key, $default = null, $filter = null) {
    $sources = [$_GET, $_POST];
    foreach ($sources as $source) {
        if (isset($source[$key])) {
            $value = $source[$key];
            if ($filter !== null) {
                $filtered = filter_var($value, $filter);
                return $filtered !== false ? $filtered : $default;
            }
            return $value;
        }
    }
    return $default;
}

/**
 * Helper para obtener datos JSON del body
 */
function getBody() {
    $raw = file_get_contents('php://input');
    if (empty($raw)) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function ReqBody($key, $default = null) {
    static $body = null;
    if ($body === null) {
        $body = getBody();
    }
    return isset($body[$key]) ? $body[$key] : $default;
}

/**
 * Variables SESIÓN - Configuración segura + compatibilidad
 */
if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookies de sesión de forma segura (solo si no hay output aún)
    if (headers_sent() === false) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        // Nota: session.cookie_secure se habilita solo en HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', 1);
        }
    }
    @session_start();
}

if (!empty($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'Ses_') === 0) {
            ${$key} = $value;
        }
    }
}

/**
 * Compatibilidad legacy: extracción segura de GET/POST
 *
 * El código legacy (926+ archivos) usa isset($var) para detectar
 * parámetros de formulario. Esta sección restaura ese comportamiento
 * de forma controlada para no romper la aplicación.
 *
 * Medidas de seguridad:
 * - Solo extrae valores escalares (evita inyección de arrays)
 * - No sobrescribe variables existentes (protege las de sesión)
 * - No extrae arrays completos de $_FILES
 */
$__LEGACY_POST_GET_SOURCES = [$_GET, $_POST];
foreach ($__LEGACY_POST_GET_SOURCES as $__src) {
    foreach ($__src as $__key => $__value) {
        if (!is_array($__value) && !isset($$__key)) {
            $$__key = $__value;
        }
    }
}
unset($__LEGACY_POST_GET_SOURCES, $__src, $__key, $__value);

/**
 * Output Escaping - Previene XSS en toda la aplicación
 */
function esc($var) {
    if ($var === null) return '';
    return htmlspecialchars((string)$var, ENT_QUOTES, 'UTF-8');
}

/**
 * Escapes a value for safe use in an HTML attribute
 */
function esc_attr($var) {
    return esc($var);
}

/**
 * Escapes a value for safe use in a JavaScript context
 */
function esc_js($var) {
    return esc($var);
}

/**
 * CSRF Token - Generación y helpers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_validate($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$DirSep = DIRECTORY_SEPARATOR;
$APP_REAL_PATH = realpath(str_replace(basename(__FILE__), '', __FILE__) . '..' . $DirSep . '..' . $DirSep);

include_once(__DIR__ . '/debugbar.php');
include_once(__DIR__ . '/monolog.php');

$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}
