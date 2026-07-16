<?php
error_reporting(E_ALL);
ini_set('display_errors, '1);
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$base = 'C:/Users/ismaa/OneDrive/Documentos/GitHub/exa-contable-relavera;

$files = [
    'administrador/LOGICA/seguridad.php,
    'contabilidad/LOGICA/con_log_planc_2.php,
    'tesoreria/LOGICA/tes_log_ccpp.php,
    'tesoreria/LOGICA/tes_log_banco.php,
    'facturacion/LOGICA/fac_log_codigos_sri.php,
    'contabilidad/LOGICA/con_sql_estado.php,
];

foreach ($files as $f) {
    $path = $base . '/ . $f;
    if (!file_exists($path)) {
        echo "MISSING: $f . PHP_EOL;
        continue;
    }
    // Reset include path for each file test
    get_included_files(); // just to have it
    try {
        // Use a temp scope to test each include
        $result = @include_once $path;
        echo "$f:  . ($result ? 'OK : 'FAILED (returned false)) . PHP_EOL;
    } catch (Throwable $e) {
        echo "$f ERROR:  . get_class($e) . ":  . $e->getMessage() . " in  . $e->getFile() . ": . $e->getLine() . PHP_EOL;
    }
}
