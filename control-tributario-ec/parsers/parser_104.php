<?php
// parsers/parser_104.php
function parse_104($text) {
    // Validar que sea realmente un Formulario 104 del SRI
    $textUpper = mb_strtoupper($text, 'UTF-8');
    if (strpos($textUpper, 'IMPUESTO AL VALOR AGREGADO') === false && 
        strpos($textUpper, 'DECLARACIÓN DE IVA') === false &&
        strpos($textUpper, 'FORMULARIO 104') === false) {
        return array('error' => 'El documento subido no parece ser un Formulario 104 válido del SRI. Por favor verifica el archivo.');
    }

    // Basic extraction logic
    $res = array('status' => 'ok', 'datos' => array());
    
    // Detect month
    $mes = 1; // Default
    $meses = array('ENERO'=>1, 'FEBRERO'=>2, 'MARZO'=>3, 'ABRIL'=>4, 'MAYO'=>5, 'JUNIO'=>6, 'JULIO'=>7, 'AGOSTO'=>8, 'SEPTIEMBRE'=>9, 'OCTUBRE'=>10, 'NOVIEMBRE'=>11, 'DICIEMBRE'=>12);
    
    // Buscar la primera aparición de un mes en el texto (generalmente el periodo fiscal en la cabecera)
    $first_pos = 999999;
    foreach($meses as $m => $num) {
        $pos = stripos($text, $m);
        if ($pos !== false && $pos < $first_pos) {
            $first_pos = $pos;
            $mes = $num;
        }
    }
    $res['mes'] = $mes;
    $res['nombre_mes'] = array_search($mes, $meses);
    
    $codigos = array('401', '403', '405', '409', '411', '413', '415', '419', '421', '422', '423', '424', '425', '426', '427', '428', '429', '431', '434', '441', '442', '443', '444', '454', '601', '606', '609', '617', '902', '903', '904', '999', '483', '485');
    for ($i = 500; $i <= 564; $i++) {
        $codigos[] = (string)$i;
    }
    for ($i = 721; $i <= 802; $i++) {
        $codigos[] = (string)$i;
    }
    $campos = array();
    foreach($codigos as $c) {
        $campos[$c] = 0;
    }
    
    // Extraer solo valores con 2 decimales para evitar tragar códigos de casilleros vacíos
    $pattern = '/(?:^|(?<=\.\d{2})|\D)(\d{3})\s*(0\.\d{2}|[1-9][\d,]*\.\d{2})/';
    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        $vistos = array();
        foreach ($matches as $m) {
            $code = $m[1];
            $raw_val = $m[2];
            $val = (float) str_replace(',', '', $raw_val);
            
            // Fix for merged casilleros without commas (e.g. 731 empty, next is 799 with 965.08 -> 799965.08)
            // Only apply this fix for codes in the 700 series to prevent splitting valid monetary amounts like 7318.50 for code 401.
            if ($code >= 700 && preg_match('/^(\d{3})(\d{1,5}\.\d{2})$/', $raw_val, $parts)) {
                $possible_code = $parts[1];
                $real_raw_val = $parts[2];
                if (in_array($possible_code, $codigos) && $possible_code > $code && $possible_code >= 700) {
                    if (!isset($vistos[$possible_code])) {
                        $vistos[$possible_code] = true;
                        $campos[$possible_code] = (float) $real_raw_val;
                    }
                    $vistos[$code] = true; // Mark current empty casillero as seen
                    continue; // Skip assigning the merged value to the current empty code
                }
            }
            
            if (!isset($vistos[$code])) {
                $vistos[$code] = true;
                $campos[$code] = $val;
            }
        }
    }
    
    // Extract fecha de presentación / recaudación, serial, código
    $res['fecha_presentacion'] = null;
    $res['numero_serial'] = null;
    $res['codigo_verificador'] = null;

    if (preg_match('/([A-Z0-9]{12,25})\s+(\d{10,20})\s+([0-9]{2}[-\/][0-9]{2}[-\/][0-9]{4})/', $text, $mHeader)) {
        $res['codigo_verificador'] = str_replace('SRI', '', $mHeader[1]); // Remove SRI if merged
        $res['numero_serial'] = $mHeader[2];
        $res['fecha_presentacion'] = $mHeader[3];
    } else {
        // Fallback individuales
        if (preg_match('/([0-9]{2}[-\/][0-9]{2}[-\/][0-9]{4})/', $text, $mDate)) {
            $res['fecha_presentacion'] = $mDate[1];
        }
        if (preg_match('/N[UÚ]MERO\s+SERIAL[\s\:]*(\d+)/iu', $text, $mSer)) {
            $res['numero_serial'] = $mSer[1];
        }
        if (preg_match('/C[OÓ]DIGO\s+VERIFICADOR[\s\:]*([A-Z0-9]+)/iu', $text, $mCod)) {
            $res['codigo_verificador'] = $mCod[1];
        }
    }
    
    // Detectar si es Original o Sustitutiva
    $res['tipo_declaracion'] = 'ORIGINAL';
    if (preg_match('/Tipo\s+Declaraci[oó]n:\s*(SUSTITUTIVA|ORIGINAL)/iu', $text, $mType)) {
        $res['tipo_declaracion'] = strtoupper($mType[1]);
    } else if (preg_match('/104\s*No\.\s*Identificaci[oó]n\s*de\s*la\s*declaraci[oó]n\s*que\s*la\s*sustituye\s*(\d{10,20})/iu', $text)) {
        $res['tipo_declaracion'] = 'SUSTITUTIVA';
    } else if (preg_match('/FORMULARIO\s+SUSTITUYE\s*:\s*\d+/iu', $text)) {
        $res['tipo_declaracion'] = 'SUSTITUTIVA';
    }
    
    $res['datos'] = $campos;
    return $res;
}

function extract_val($text, $codigo) {
    $pattern = '/(?:^|\s|\.\d{2})' . $codigo . '\s*([\d,]+\.\d{2}|[\d,]+)/';
    if (preg_match($pattern, $text, $m)) {
        return (float) str_replace(',', '', $m[1]);
    }
    return 0.0;
}
