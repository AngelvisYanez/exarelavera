<?php
// parsers/parser_103.php
function parse_103($text) {
    // Validar que sea realmente un Formulario 103 del SRI
    $textUpper = mb_strtoupper($text, 'UTF-8');
    if (strpos($textUpper, 'RETENCIONES EN LA FUENTE') === false && 
        strpos($textUpper, 'DECLARACIÓN DE RETENCIONES') === false &&
        strpos($textUpper, 'FORMULARIO 103') === false) {
        return array('error' => 'El documento subido no parece ser un Formulario 103 válido del SRI. Por favor verifica el archivo.');
    }

    $res = array('status' => 'ok', 'datos' => array());
    
    $mes = 1;
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
    
    // Regex para buscar casilleros de Base Imponible: Rango 300-349 o 3440
    // Evita extraer casilleros de retención (350-998)
    // Códigos válidos de Base Imponible según Formulario 103
    $bases_validas = array(
        '303','303A','3030','304','307','308','309','310','311','312','3120','3121',
        '314','3140','319','320','322','323','3230','324','3250','332','332A','333',
        '334','335','336','337','3370','343','3430','344','3440','346','3480','3483',
        '3484','3485','350'
    );
    
    // Ordenar por longitud descendente para evaluar primero los códigos de 4 dígitos
    usort($bases_validas, function($a, $b) {
        return strlen($b) - strlen($a);
    });
    
    $campos = array();
    $total = 0;
    
    // Construir expresión regular que evalúe estrictamente los casilleros válidos
    $pattern = '/(?:^|\s|[^0-9A-Z])(' . implode('|', $bases_validas) . ')\s*(0\.\d{2}|[1-9][\d,]*\.\d{2})/';
    
    // Buscar todas las coincidencias
    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        $vistos = array();
        foreach ($matches as $m) {
            $codigo = $m[1];
            $val = (float) str_replace(',', '', $m[2]);
            
            // Excluir 302, 349 y 497 según la regla estricta
            if (in_array($codigo, array('302', '349', '497'))) {
                continue;
            }
            
            if (!isset($vistos[$codigo])) {
                $vistos[$codigo] = true;
                if ($val > 0) {
                    $campos[$codigo] = $val;
                    $total += $val;
                }
            }
        }
    }
    
    // Casillero 999: Total Retenido
    if (preg_match('/(?:^|\D)(999)\s+([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2})/', $text, $m)) {
        $res['total_pagado'] = (float) str_replace(',', '', $m[2]);
    }
    
    $campos['total'] = $total;
    
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
