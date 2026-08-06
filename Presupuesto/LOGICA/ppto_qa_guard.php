<?php
/**
 * Guardia de entorno QA para scripts de prueba Presupuesto.
 * Requisitos: CLI + (EXA_PPTO_QA=1 o flag --qa-confirm).
 * Evita ejecucion accidental via web o en shell de produccion.
 */
function ppto_qa_guard_bootstrap()
{
    if (php_sapi_name() !== 'cli') {
        if (function_exists('http_response_code')) {
            http_response_code(403);
        } else {
            header('HTTP/1.1 403 Forbidden');
        }
        die("Solo CLI.\n");
    }

    $qa_ok = (getenv('EXA_PPTO_QA') === '1');
    if (!$qa_ok && isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
        $qa_ok = in_array('--qa-confirm', $GLOBALS['argv'], true);
    }
    if (!$qa_ok) {
        fwrite(STDERR, "Bloqueado: entorno QA no confirmado.\n");
        fwrite(STDERR, "  Windows: set EXA_PPTO_QA=1 && php script.php\n");
        fwrite(STDERR, "  Linux:   EXA_PPTO_QA=1 php script.php\n");
        fwrite(STDERR, "  Alternativa: php script.php --qa-confirm\n");
        exit(1);
    }
}
