<?php
// parsers/parser_iess.php
function parse_iess($text) {
    file_put_contents(__DIR__ . '/../iess_text.log', $text);
    
    // Validar que sea realmente un documento del IESS
    $textUpper = mb_strtoupper($text, 'UTF-8');
    if (strpos($textUpper, 'INSTITUTO ECUATORIANO DE SEGURIDAD SOCIAL') === false && 
        strpos($textUpper, 'PLANILLAS') === false &&
        strpos($textUpper, 'IESS') === false) {
        return array('error' => 'El documento subido no parece ser una planilla del IESS válida. Por favor verifica el archivo.');
    }

    $res = array('status' => 'ok', 'meses' => array());
    
    // Extraer todos los periodos, cédulas, la palabra TOTALES y montos
    $pattern = '/(20[2-3][0-9]-(?:1[0-2]|0?[1-9])\b)|(\b\d{10}(?:001)?\b)|(TOTALES?)|((?:\d{1,3},)*\d{1,6}\.\d{2})/i';
    
    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        $meses = array();
        
        $sbu = 460;
        if (isset($_SESSION['parametros']['sbu'])) {
            $sbu = floatval($_SESSION['parametros']['sbu']);
        }

        $current_mes = null;
        $current_cedula = null;
        $float_buffer = array();
        $processed_rows = array(); // Para deduplicar filas exactas
        
        $process_buffer = function($mes, $cedula, $floats) use (&$meses, &$processed_rows, $sbu) {
            // El IESS tiene 8 columnas de dinero por empleado
            if (count($floats) >= 8) {
                $row_key = $cedula !== null ? "$mes-$cedula" : "$mes-" . implode('-', array_slice($floats, -8));
                if (isset($processed_rows[$row_key])) {
                    return; // Ignorar fila duplicada
                }
                $processed_rows[$row_key] = true;

                $f = array_slice($floats, -8);
                // El orden extraído de la estructura en crudo del PDF es:
                // 0: Sueldo
                // 1: Total Aporte
                // 2: Cesantia
                // 3: Aporte Adic
                // 4: Patronal
                // 5: Individual (9.45)
                // 6: % CCC
                // 7: Valor CCC
                $sueldo = floatval(str_replace(',', '', $f[0]));
                $patronal = floatval(str_replace(',', '', $f[4]));
                $individual = floatval(str_replace(',', '', $f[5]));
                $valor_ccc = floatval(str_replace(',', '', $f[7]));
                
                if (!isset($meses[$mes])) {
                    $meses[$mes] = array('empleados' => 0, 'n_bruta' => 0, 'n_pat' => 0, 'n_ind' => 0, 'n_ccc' => 0, 'n_prov1314' => 0, 'n_vac' => 0);
                }
                
                $meses[$mes]['empleados']++;
                $meses[$mes]['n_bruta'] += $sueldo;
                $meses[$mes]['n_pat'] += $patronal;
                $meses[$mes]['n_ind'] += $individual;
                $meses[$mes]['n_ccc'] += $valor_ccc;
                
                $meses[$mes]['n_prov1314'] += ($sueldo / 12) + (1 * $sbu / 12); 
                $meses[$mes]['n_vac'] += $sueldo / 24;
            }
        };

        foreach ($matches as $m) {
            if (!empty($m[1])) {
                // Es un periodo
                if ($current_mes !== null) {
                    $process_buffer($current_mes, $current_cedula, $float_buffer);
                }
                $parts = explode('-', $m[1]);
                $current_mes = intval($parts[1]);
                $current_cedula = null;
                $float_buffer = array();
            } elseif (!empty($m[2])) {
                // Es una cédula
                $current_cedula = $m[2];
            } elseif (!empty($m[3])) {
                // Fila de totales
                if ($current_mes !== null) {
                    $process_buffer($current_mes, $current_cedula, $float_buffer);
                    $float_buffer = array(); // Limpiar para que los montos de totales no sean asignados al empleado
                }
            } elseif (!empty($m[4])) {
                // Es un float
                if ($current_mes !== null) {
                    $float_buffer[] = $m[4];
                }
            }
        }
        if ($current_mes !== null) {
            $process_buffer($current_mes, $current_cedula, $float_buffer);
        }
        
        if (!empty($meses)) {
            $res['meses'] = $meses;
            return $res;
        }
    }
    
    // Fallback genérico si no se encuentra el patrón
    $res['datos'] = array(
        'empleados' => 0,
        'n_bruta' => 0,
        'n_pat' => 0,
        'n_ind' => 0,
        'n_ccc' => 0,
        'n_prov1314' => 0,
        'n_vac' => 0
    );
    
    return $res;
}
