<?php
// parsers/parser_101.php
// Parser para Formularios 101 (Sociedades) y 102 (Personas Naturales) - Impuesto a la Renta
function parse_101($text) {
    $textUpper = mb_strtoupper($text, 'UTF-8');

    // Detectar tipo de formulario: 101 o 102
    $tipo_formulario = '102'; // default
    if (strpos($textUpper, 'FORMULARIO 101') !== false || strpos($textUpper, 'SOCIEDADES Y ESTABLECIMIENTOS') !== false) {
        $tipo_formulario = '101';
    }

    // Validar que sea un formulario de Renta
    if (strpos($textUpper, 'IMPUESTO A LA RENTA') === false &&
        strpos($textUpper, 'FORMULARIO 102') === false &&
        strpos($textUpper, 'FORMULARIO 101') === false &&
        strpos($textUpper, 'DECLARACI') === false) {
        return array('error' => 'El documento subido no parece ser un Formulario 101 o 102 válido del SRI.');
    }

    $res = array(
        'status' => 'ok',
        'datos' => array(),
        'tipo_formulario' => $tipo_formulario
    );

    // --- Extraer metadatos ---

    // Año / período fiscal
    $anio = 0;
    if (preg_match('/(?:PER[IÍ]ODO\s*FISCAL|EJERCICIO\s*FISCAL|A[ÑN]O)\s*[:\s]*\s*(20[0-9]{2})/i', $textUpper, $m)) {
        $anio = intval($m[1]);
    } elseif (preg_match('/(20[2-3][0-9])/', $textUpper, $m)) {
        $anio = intval($m[1]);
    }
    $res['anio'] = $anio;

    // RUC
    $ruc = '';
    if (preg_match('/\b(\d{10}001)\b/', $text, $m)) {
        $ruc = $m[1];
    }
    $res['ruc'] = $ruc;

    // Razón social
    $razon_social = '';
    $patrones_nombre = array(
        '/(?:RAZ[OÓ]N\s+SOCIAL|APELLIDOS\s+Y\s+NOMBRES)[:\s]+([A-Z0-9\s\.,&\-Ñ]{5,80}?)(?:RUC|PER[IÍ]ODO|N[UÚ]MERO|TIPO|FORMULARIO|DECLARACI|\d{13}|\n|$)/i',
        '/(\d{10}001)[:\s]+([A-Z0-9\s\.,&\-Ñ]{5,80}?)(?:RUC|PER|NUM|TIPO|DEC|\n|$)/i'
    );
    foreach ($patrones_nombre as $p) {
        if (preg_match($p, $textUpper, $m)) {
            $n = trim(preg_replace('/\s+/', ' ', $m[count($m)-1]));
            if (strlen($n) > 3 && !is_numeric(str_replace(' ', '', $n))) {
                $razon_social = $n;
                break;
            }
        }
    }
    $res['razon_social'] = $razon_social;

    // Número de serie
    $serie = '';
    if (preg_match('/(?:N[UÚ]MERO\s+DE\s+SERIE|No\.?\s*SERIE)[:\s]*(\d{10,20})/i', $textUpper, $m)) {
        $serie = $m[1];
    }
    $res['numero_serie'] = $serie;

    // Original o sustitutiva y tipo de declaración
    $res['es_sustitutiva'] = false;
    $res['tipo_declaracion'] = 'ORIGINAL';
    if (preg_match('/Tipo\s+Declaraci[oó]n:\s*(SUSTITUTIVA|ORIGINAL)/iu', $text, $mType)) {
        $res['tipo_declaracion'] = strtoupper($mType[1]);
        if ($res['tipo_declaracion'] == 'SUSTITUTIVA') $res['es_sustitutiva'] = true;
    } else if (preg_match('/104\s*No\.\s*Identificaci[oó]n\s*de\s*la\s*declaraci[oó]n\s*que\s*la\s*sustituye\s*(\d{10,20})/iu', $text)) {
        $res['tipo_declaracion'] = 'SUSTITUTIVA';
        $res['es_sustitutiva'] = true;
    } else if (preg_match('/FORMULARIO\s+SUSTITUYE\s*:\s*\d+/iu', $text)) {
        $res['tipo_declaracion'] = 'SUSTITUTIVA';
        $res['es_sustitutiva'] = true;
    }

    // Extraer fecha de presentación / recaudación, serial, código (Fallback robusto SRI)
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
    
    // Si ya lo extrajo arriba, lo conservamos (para mantener compatibilidad antigua de 101)
    if (!empty($res['fecha_presentacion'])) {
        $res['fecha_recaudacion'] = $res['fecha_presentacion'];
    }

    // --- Definir casilleros a extraer ---

    // Casilleros de 4 dígitos (sección RESULTADOS: Ingresos y Gastos)
    $codigos_4d = array(
        // Ingresos
        '6001', '6003', '6005', '6007', '6009', '6011', '1005', '6999',
        // Gastos de personal
        '7041', '7044', '7047', '7050', '7053', '7056', '7059',
        // Depreciaciones
        '7065', '7068',
        // Amortizaciones
        '7095',
        // Otros gastos
        '7173', '7179', '7191', '7197', '7209', '7242', '7248', '7269',
        // Totales costos y gastos
        '7991', '7992', '7999'
    );

    // Casilleros de 3 dígitos (BALANCE y CONCILIACIÓN)
    $codigos_3d = array(
        // Activos corrientes
        '311', '315', '337', '336', '360', '361',
        // Activos no corrientes (PPE y otros)
        '362', '363', '365', '369', '371', '373', '375', '384', '386', '449',
        // Total activos
        '499',
        // Pasivos corrientes
        '513', '532', '533', '534', '545', '549', '550',
        // Pasivos no corrientes
        '563', '573', '574', '589',
        // Total pasivos
        '599',
        // Patrimonio
        '601', '602', '604', '611', '615', '616', '698',
        // Conciliación tributaria
        '801', '802', '803', '804', '805', '806', '807', '808', '809', '810', '811',
        '836', '837', '838', '849', '850', '851', '857', '858', '859', '861', '863',
        '865', '866', '869', '870', '871', '902', '903', '904', '999',
        // Base participación trabajadores
        '098'
    );

    $campos = array();

    // Inicializar todos los campos a 0
    foreach ($codigos_4d as $c) {
        $campos[$c] = 0;
    }
    foreach ($codigos_3d as $c) {
        $campos[$c] = 0;
    }

    // --- PASO 1: Extraer casilleros de 4 dígitos ---
    // Regex: un código de 4 dígitos seguido de un valor decimal (con o sin comas como separador de miles)
    $pattern_4d = '/(?:^|\D)(\d{4})\s+(0\.\d{2}|[1-9][\d,]*\.\d{2})/';
    $vistos_4d = array();
    if (preg_match_all($pattern_4d, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $code = $m[1];
            $val = (float) str_replace(',', '', $m[2]);
            if (in_array($code, $codigos_4d) && !isset($vistos_4d[$code])) {
                $vistos_4d[$code] = true;
                $campos[$code] = $val;
            }
        }
    }

    // --- PASO 2: Extraer casilleros de 3 dígitos ---
    // Ahora que ya marcamos los 4-dígitos, extraemos los de 3.
    // Regex: un código de 3 dígitos (no precedido por otro dígito) seguido de un valor decimal.
    // El lookbehind negativo (?<!\d) asegura que no capturemos los últimos 3 dígitos de un código de 4.
    $pattern_3d = '/(?<!\d)(\d{3})\s+(0\.\d{2}|[1-9][\d,]*\.\d{2})/';
    $vistos_3d = array();
    if (preg_match_all($pattern_3d, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $code = $m[1];
            $raw_val = $m[2];
            $val = (float) str_replace(',', '', $raw_val);

            if (!in_array($code, $codigos_3d) || isset($vistos_3d[$code])) {
                continue;
            }

            // Fix para casilleros pegados sin espacio (ej. "499" vacío + "513" con valor -> "513250.00")
            // Detectar si el valor contiene otro código embebido
            if (preg_match('/^(\d{3})(\d{1,5}\.\d{2})$/', $raw_val, $parts)) {
                $possible_code = $parts[1];
                $real_val = $parts[2];
                if (in_array($possible_code, $codigos_3d) && $possible_code > $code && !isset($vistos_3d[$possible_code])) {
                    $vistos_3d[$possible_code] = true;
                    $campos[$possible_code] = (float) $real_val;
                    $vistos_3d[$code] = true;
                    continue;
                }
            }

            $vistos_3d[$code] = true;
            $campos[$code] = $val;
        }
    }

    $res['datos'] = $campos;
    $res['campos'] = $campos;

    // --- Secciones de resumen para facilitar la lectura ---
    $res['resumen'] = array(
        'total_activos' => $campos['499'],
        'total_pasivos' => $campos['599'],
        'total_patrimonio' => $campos['698'],
        'total_ingresos' => $campos['6999'],
        'total_costos_gastos' => $campos['7999'],
        'utilidad_ejercicio' => $campos['615'],
        'perdida_ejercicio' => $campos['616'],
        'ir_causado' => $campos['850'],
        'ir_a_pagar' => $campos['869'],
        'saldo_a_favor' => $campos['870'],
        'total_pagado' => $campos['999']
    );

    return $res;
}
