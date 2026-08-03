<?php
/**
 * Extraccion de texto PDF y parseo de presupuesto tipo RCET (partidas + rubros driver).
 * Compatible PHP 5.3+ (sin dependencias externas obligatorias).
 */

require_once(__DIR__ . '/ppto_partidas_logica.php');
require_once(__DIR__ . '/ppto_format_helpers.php');

/**
 * Toneladas/dia tipicas del PDF RCET.
 *
 * @return float
 */
function ppto_pdf_tn_dia_default() {
    return 3500.0;
}

/**
 * Dias del mes para calcular toneladas base mensuales (tn/dia x dias).
 * La columna "Dias Laborables" del PDF (22) es prorrata; la base presupuestaria usa 30.
 *
 * @return int
 */
function ppto_pdf_dias_mes_ton_default() {
    return 30;
}

/**
 * Dias laborables fijos del presupuesto RCET (columna PDF / Ton mens).
 *
 * @return int
 */
function ppto_pdf_dias_laborables_default() {
    return 22;
}

/**
 * Toneladas base PDF por mes por defecto (3500 x 22 = 77000) — egreso/costo Excel.
 *
 * @return float
 */
function ppto_pdf_ton_base_mes_default() {
    return ppto_rubro_ton_mes_operativa();
}

/**
 * Ton ingresos mensual del proyecto RCET (3500 x 30 = 105000).
 *
 * @return float
 */
function ppto_pdf_ton_ingreso_mes_default() {
    return 105000.0;
}

/**
 * Separa ton detectada en archivo: costo (77k Excel) vs ingresos (105k proyecto).
 *
 * @param float $ton_detectada
 * @return array{ingreso:float,costo:float}
 */
function ppto_pdf_ton_dual_base($ton_detectada) {
    $ton_detectada = (float)$ton_detectada;
    $ingreso = ppto_pdf_ton_ingreso_mes_default();
    $costo = ppto_pdf_ton_base_mes_default();

    if ($ton_detectada <= 0.0001) {
        return array('ingreso' => $ingreso, 'costo' => $costo);
    }
    if ($ton_detectada >= 70000 && $ton_detectada < 95000) {
        return array('ingreso' => $ingreso, 'costo' => round($ton_detectada, 4));
    }
    if ($ton_detectada >= 95000) {
        return array('ingreso' => round($ton_detectada, 4), 'costo' => $costo);
    }
    return array('ingreso' => $ingreso, 'costo' => round($ton_detectada, 4));
}

/**
 * Convierte texto Latin-1/Windows-1252 del PDF a UTF-8 para regex con /u.
 *
 * @param string $s
 * @return string
 */
function ppto_pdf_a_utf8($s) {
    if ($s === '' || $s === null) {
        return '';
    }
    if (function_exists('mb_check_encoding') && mb_check_encoding($s, 'UTF-8')) {
        return $s;
    }
    if (function_exists('iconv')) {
        $conv = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
        if ($conv !== false) {
            return $conv;
        }
    }
    return $s;
}

/**
 * Indica si el PDF solo tiene graficos (sin operadores de texto Tj/TJ).
 *
 * @param string $filepath
 * @return bool
 */
function ppto_pdf_es_solo_grafico($filepath) {
    $data = @file_get_contents($filepath);
    if ($data === false || $data === '') {
        return false;
    }
    if (ppto_pdf_tiene_operadores_texto($data)) {
        return false;
    }
    return (strpos($data, 'stream') !== false && strpos($data, 'FlateDecode') !== false);
}

/**
 * Detecta operadores de texto en PDF (incluye streams comprimidos).
 *
 * @param string $data
 * @return bool
 */
function ppto_pdf_tiene_operadores_texto($data) {
    if (preg_match('/\)\s*Tj/', $data) || preg_match('/\]\s*TJ/', $data)) {
        return true;
    }
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $data, $matches)) {
        $n = 0;
        foreach ($matches[1] as $stream) {
            if ($n++ > 30) {
                break;
            }
            $decoded = ppto_pdf_decode_stream($stream);
            if ($decoded !== false && (preg_match('/\)\s*Tj/', $decoded) || preg_match('/\]\s*TJ/', $decoded))) {
                return true;
            }
        }
    }
    return false;
}

/**
 * @param string $stream
 * @return string|false
 */
function ppto_pdf_decode_stream($stream) {
    $decoded = @gzuncompress($stream);
    if ($decoded === false) {
        $decoded = @gzinflate(substr($stream, 2));
    }
    if ($decoded === false) {
        $decoded = @gzinflate($stream);
    }
    return ($decoded !== false) ? $decoded : false;
}

/**
 * Convierte codigo Unicode hex (UTF-16BE) a UTF-8.
 *
 * @param string $hex
 * @return string
 */
function ppto_pdf_hex_to_utf8($hex) {
    $hex = strtoupper(preg_replace('/[^0-9A-F]/', '', $hex));
    if ($hex === '') {
        return '';
    }
    if (strlen($hex) % 4 !== 0) {
        $hex = str_pad($hex, (int)(ceil(strlen($hex) / 4) * 4), '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i < strlen($hex); $i += 4) {
        $code = hexdec(substr($hex, $i, 4));
        if ($code === 0) {
            continue;
        }
        if ($code < 0x80) {
            $out .= chr($code);
        } elseif ($code < 0x800) {
            $out .= chr(0xC0 | ($code >> 6)) . chr(0x80 | ($code & 0x3F));
        } else {
            $out .= chr(0xE0 | ($code >> 12))
                . chr(0x80 | (($code >> 6) & 0x3F))
                . chr(0x80 | ($code & 0x3F));
        }
    }
    return $out;
}

/**
 * Parsea bloques beginbfchar de mapas ToUnicode.
 *
 * @param string $cmap_text
 * @return array
 */
function ppto_pdf_parse_cmap($cmap_text) {
    $map = array();
    if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $cmap_text, $m)) {
        $total = count($m[1]);
        for ($i = 0; $i < $total; $i++) {
            $gid = strtoupper($m[1][$i]);
            $map[$gid] = ppto_pdf_hex_to_utf8($m[2][$i]);
        }
    }
    return $map;
}

/**
 * Une todos los mapas ToUnicode del PDF.
 *
 * @param string $pdf_data
 * @return array
 */
function ppto_pdf_collect_cmaps($pdf_data) {
    $merged = array();
    if (!preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf_data, $streams)) {
        return $merged;
    }
    foreach ($streams[1] as $s) {
        if (strpos($s, 'begincmap') === false && strpos($s, 'beginbfchar') === false) {
            continue;
        }
        $chunk = ppto_pdf_parse_cmap($s);
        foreach ($chunk as $k => $v) {
            if (!isset($merged[$k]) && $v !== '') {
                $merged[$k] = $v;
            }
        }
    }
    return $merged;
}

/**
 * Decodifica un array TJ con glifos hex y kernings.
 *
 * @param string $tj_content
 * @param array $cmap
 * @return string
 */
function ppto_pdf_decode_tj_array($tj_content, $cmap) {
    $out = '';
    if (!preg_match_all('/<([0-9A-Fa-f]+)>|(-?\d+(?:\.\d+)?)/', $tj_content, $parts, PREG_SET_ORDER)) {
        return '';
    }
    foreach ($parts as $p) {
        if ($p[0][0] !== '<') {
            continue;
        }
        $gid = strtoupper($p[1]);
        $gid = str_pad($gid, 4, '0', STR_PAD_LEFT);
        if (isset($cmap[$gid])) {
            $out .= $cmap[$gid];
        } else {
            $decoded = ppto_pdf_hex_to_utf8($gid);
            if ($decoded !== '') {
                $out .= $decoded;
            }
        }
    }
    return $out;
}

/**
 * Extrae texto de un stream con fuentes CID (operador TJ).
 *
 * @param string $stream
 * @param array $cmap
 * @return string
 */
function ppto_pdf_texto_cid_desde_stream($stream, $cmap) {
    $out = '';
    $blocks = preg_split('/\bET\b/', $stream);
    foreach ($blocks as $block) {
        if (strpos($block, 'BT') === false) {
            continue;
        }
        $line = '';
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tj)) {
            foreach ($tj[1] as $arr) {
                $line .= ppto_pdf_decode_tj_array($arr, $cmap);
            }
        }
        if (preg_match_all('/\(([^\\\\\)]*(?:\\\\.|[^\\\\\)])*)\)\s*Tj/', $block, $tj2)) {
            foreach ($tj2[1] as $s) {
                $line .= ppto_pdf_unescape($s);
            }
        }
        $line = trim(preg_replace('/\s+/', ' ', $line));
        if ($line !== '' && preg_match('/[A-Za-z0-9]/', $line)) {
            $out .= $line . "\n";
        }
    }
    return $out;
}

/**
 * Extrae texto CID/TJ de todo el PDF.
 *
 * @param string $pdf_data
 * @return string
 */
function ppto_pdf_extraer_texto_cid($pdf_data) {
    $cmap = ppto_pdf_collect_cmaps($pdf_data);
    if (empty($cmap)) {
        return '';
    }
    $text = '';
    if (!preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf_data, $matches)) {
        return '';
    }
    $n = 0;
    foreach ($matches[1] as $stream) {
        if ($n++ > 80 || strlen($text) > 400000) {
            break;
        }
        $decoded = ppto_pdf_decode_stream($stream);
        if ($decoded === false || strlen($decoded) > 500000) {
            continue;
        }
        if (strpos($decoded, 'TJ') === false && strpos($decoded, 'Tj') === false) {
            continue;
        }
        $text .= ppto_pdf_texto_cid_desde_stream($decoded, $cmap);
    }
    return $text;
}

/**
 * Extrae texto de PDF, Excel o CSV segun extension.
 *
 * @param string $filepath
 * @param string $ext
 * @return string
 */
function ppto_presupuesto_extraer_texto($filepath, $ext, $originalName = '', $mimeType = '') {
    $ext = strtolower(trim((string)$ext));
    if (in_array($ext, array('csv', 'xls', 'xlsx', 'xlsm', 'xltx'))) {
        require_once(__DIR__ . '/ppto_spreadsheet_logica.php');
        return ppto_spreadsheet_extraer_texto($filepath, $ext, $originalName, $mimeType);
    }
    return ppto_pdf_extraer_texto($filepath);
}

/**
 * Extrae texto legible de un archivo PDF.
 *
 * @param string $filepath
 * @return string
 */
function ppto_pdf_extraer_texto($filepath) {
    if (!is_readable($filepath)) {
        return '';
    }

    $escaped = escapeshellarg($filepath);
    $cmds = array('pdftotext', 'C:\\xampp\\poppler\\pdftotext.exe', 'C:\\Program Files\\xpdf\\pdftotext.exe');
    foreach ($cmds as $cmd) {
        $out = @shell_exec($cmd . ' ' . $escaped . ' - 2>NUL');
        if (is_string($out) && strlen(trim($out)) > 30) {
            return $out;
        }
    }

    $data = @file_get_contents($filepath);
    if ($data === false || $data === '') {
        return '';
    }

    $cmap = ppto_pdf_collect_cmaps($data);
    if (!empty($cmap)) {
        $cid_text = ppto_pdf_extraer_texto_cid($data);
        if (strlen(trim($cid_text)) > 30) {
            return strlen($cid_text) > 500000 ? substr($cid_text, 0, 500000) : $cid_text;
        }
    }

    $text = '';

    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $data, $matches)) {
        $max_streams = 80;
        $n = 0;
        foreach ($matches[1] as $stream) {
            if ($n >= $max_streams || strlen($text) > 400000) {
                break;
            }
            $n++;
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate(substr($stream, 2));
            }
            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }
            if ($decoded !== false && strlen($decoded) < 500000) {
                $text .= ppto_pdf_texto_desde_stream($decoded);
            }
        }
    }

    if (strlen(trim($text)) < 30) {
        $cid_text = ppto_pdf_extraer_texto_cid($data);
        if (strlen(trim($cid_text)) > strlen(trim($text))) {
            $text = $cid_text;
        }
    }

    if (strlen(trim($text)) < 30 && strlen($data) < 8000000) {
        if (preg_match_all('/\(([^\\\\\)]{2,120})\)\s*Tj/', $data, $lit)) {
            $n = 0;
            foreach ($lit[1] as $s) {
                if ($n++ > 3000 || strlen($text) > 300000) {
                    break;
                }
                $chunk = trim(ppto_pdf_unescape($s));
                if ($chunk !== '' && preg_match('/[A-Za-z0-9]{2,}/', $chunk)) {
                    $text .= $chunk . "\n";
                }
            }
        }
    }

    if (strlen($text) > 500000) {
        $text = substr($text, 0, 500000);
    }

    return $text;
}

/**
 * @param string $stream
 * @return string
 */
function ppto_pdf_texto_desde_stream($stream) {
    $out = '';
    if (preg_match_all('/\(([^\\\\\)]*(?:\\\\.|[^\\\\\)])*)\)\s*Tj/', $stream, $m)) {
        foreach ($m[1] as $s) {
            $out .= ppto_pdf_unescape($s) . ' ';
        }
    }
    if (preg_match_all('/\[([^\]]+)\]\s*TJ/', $stream, $m2)) {
        foreach ($m2[1] as $chunk) {
            if (preg_match_all('/\(([^\\\\\)]*(?:\\\\.|[^\\\\\)])*)\)/', $chunk, $parts)) {
                foreach ($parts[1] as $p) {
                    $out .= ppto_pdf_unescape($p);
                }
            }
        }
    }
    return $out . "\n";
}

/**
 * @param string $s
 * @return string
 */
function ppto_pdf_unescape($s) {
    $s = str_replace(array('\\n', '\\r', '\\t'), array("\n", "\r", "\t"), $s);
    return stripcslashes($s);
}

/**
 * Normaliza numero con coma o punto decimal.
 *
 * @param string $raw
 * @return float
 */
function ppto_pdf_parse_numero($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') {
        return 0.0;
    }
    $raw = rtrim($raw, '_');
    $raw = str_replace(' ', '', $raw);
    if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    } elseif (strpos($raw, ',') !== false) {
        $raw = str_replace(',', '.', $raw);
    }
    return (float)$raw;
}

/**
 * Detecta toneladas base mensual en el texto del PDF.
 *
 * @param string $text
 * @return float
 */
function ppto_pdf_detectar_ton_base($text) {
    $text_l = strtolower($text);
    $dias_mes = ppto_pdf_dias_mes_ton_default();

    if (preg_match('/(\d{1,4}(?:[.,]\d+)?)\s*(?:tn|ton)\s*\/\s*d[i]/i', $text, $m)) {
        return round(ppto_pdf_parse_numero($m[1]) * $dias_mes, 4);
    }
    if (preg_match('/(\d{1,4}(?:[.,]\d+)?)\s*(?:tn|ton).*?x\s*(\d{1,2})\s*d[i]/i', $text_l, $m)) {
        $dias = (int)$m[2];
        if ($dias >= 28 && $dias <= 31) {
            return round(ppto_pdf_parse_numero($m[1]) * $dias, 4);
        }
        return round(ppto_pdf_parse_numero($m[1]) * $dias_mes, 4);
    }
    if (preg_match('/\b(?:tn\s*\/\s*base|tn\/base)\b[^\d]{0,50}(\d{3,5})\b/i', $text_l, $m)) {
        $tn_dia = ppto_pdf_parse_numero($m[1]);
        return ppto_rubro_ton_mes_operativa($tn_dia);
    }
    if (preg_match('/\b3500\b/s', $text)) {
        return ppto_pdf_ton_base_mes_default();
    }
    if (preg_match('/(\d{2,3}[.,]?\d{3})\s*(?:ton|tn)\s*(?:\/|\s*por\s*)?\s*mes/i', $text_l, $m)) {
        return round(ppto_pdf_parse_numero($m[1]), 4);
    }
    if (preg_match('/base\s*(?:de\s*)?toneladas[^\d]*(\d{2,3}[.,]?\d{3})/i', $text, $m)) {
        return round(ppto_pdf_parse_numero($m[1]), 4);
    }
    if (preg_match('/\b(105[.,]?000|77[.,]?000|100[.,]?000)\b/', $text, $m)) {
        return round(ppto_pdf_parse_numero($m[1]), 4);
    }
    return 0.0;
}

/**
 * Agrega rubro y partida detalle al resultado parseado.
 *
 * @param array $result
 * @param string $grupo_cod
 * @param array $rubro_seq
 * @param string $cod
 * @param string $desc
 * @param float $factor
 */
function ppto_pdf_push_rubro(&$result, $grupo_cod, &$rubro_seq, $cod, $desc, $factor, $extra = array()) {
    $rub = array(
        'codigo' => $cod,
        'descripcion' => $desc,
        'factor_anual' => $factor,
        'nivel' => 3,
        'presup_anual_pdf' => 0,
        'tn_dia' => 0,
        'dias_laborables' => 0,
        'meses' => 12,
        'usd_ton' => 0,
        'monto_recalc' => 0,
    );
    if (is_array($extra)) {
        foreach (array('presup_anual_pdf', 'tn_dia', 'meses', 'usd_ton', 'monto_recalc') as $k) {
            if (isset($extra[$k]) && (float)$extra[$k] != 0) {
                $rub[$k] = (float)$extra[$k];
            }
        }
        if (isset($extra['meses']) && (int)$extra['meses'] > 0) {
            $rub['meses'] = (int)$extra['meses'];
        }
        if (isset($extra['dias_laborables']) && (float)$extra['dias_laborables'] > 0) {
            $rub['dias_laborables'] = (float)$extra['dias_laborables'];
        } else {
            $rub['dias_laborables'] = ppto_pdf_dias_laborables_default();
        }
    }
    $result['rubros'][] = $rub;
    $result['partidas'][] = array(
        'codigo' => $cod,
        'descripcion' => $desc,
        'nivel' => 3,
        'clase' => 'D',
        'padre_codigo' => $grupo_cod,
    );
}

/**
 * Crea subgrupo automatico cuando llega un rubro principal y el grupo actual ya tiene rubros.
 *
 * @param array $result
 * @param string $capitulo_cod
 * @param string|null $grupo_cod
 * @param int $grupo_idx
 * @param array $rubro_seq
 * @param string $desc_rubro
 * @param string|null $titulo_seccion
 * @return string|null nuevo grupo_cod
 */
function ppto_pdf_auto_grupo_rubro(&$result, $capitulo_cod, $grupo_cod, &$grupo_idx, &$rubro_seq, $desc_rubro, $titulo_seccion = null) {
    if ($capitulo_cod === null || $grupo_cod === null) {
        return $grupo_cod;
    }
    if ($titulo_seccion === null || trim($titulo_seccion) === '') {
        return $grupo_cod;
    }
    if (!isset($rubro_seq[$grupo_cod]) || (int)$rubro_seq[$grupo_cod] <= 0) {
        return $grupo_cod;
    }
    $titulo = trim($titulo_seccion);
    if (!ppto_pdf_es_titulo_seccion($titulo)) {
        return $grupo_cod;
    }
    $grupo_idx++;
    $grupo_cod = $capitulo_cod . '.' . str_pad((string)$grupo_idx, 2, '0', STR_PAD_LEFT);
    $rubro_seq[$grupo_cod] = 0;
    if (strlen($titulo) > 100) {
        $titulo = substr($titulo, 0, 97) . '...';
    }
    $existe = false;
    foreach ($result['partidas'] as $p) {
        if ($p['codigo'] === $grupo_cod) {
            $existe = true;
            break;
        }
    }
    if (!$existe) {
        $result['partidas'][] = array(
            'codigo' => $grupo_cod,
            'descripcion' => $titulo,
            'nivel' => 2,
            'clase' => 'G',
            'padre_codigo' => $capitulo_cod,
        );
    }
    return $grupo_cod;
}

/**
 * Celda con monto estilo Excel/PDF (600,00 | 17.400,00 | 646,8000 | 30.030,0000).
 *
 * @param string $cell
 * @return bool
 */
function ppto_pdf_es_celda_monto($cell) {
    $cell = trim((string)$cell);
    if ($cell === '') {
        return false;
    }
    if (preg_match('/^0[,\.]/', $cell)) {
        return false;
    }
    if (preg_match('/^\d{1,3}(?:\.\d{3})+,\d{2,4}$/', $cell)) {
        return true;
    }
    if (preg_match('/^\d+,\d{2,4}$/', $cell)) {
        $val = ppto_pdf_parse_numero($cell);
        return $val >= 100;
    }
    return false;
}

/**
 * Clasifica celdas numericas de una fila de tabla PDF (Excel exportado).
 *
 * @param array $cells
 * @return array
 */
function ppto_pdf_meta_desde_celdas($cells) {
    $meta = array(
        'presup_anual_pdf' => 0,
        'tn_dia' => 0,
        'dias_laborables' => 0,
        'factor_anual' => 0,
        'meses' => 12,
        'usd_ton' => 0,
        'monto_recalc' => 0,
    );
    $decimales = array();
    $montos = array();
    $enteros = array();
    foreach ((array)$cells as $cell) {
        $cell = trim((string)$cell);
        if ($cell === '') {
            continue;
        }
        $val = ppto_pdf_parse_numero($cell);
        if (preg_match('/^0[,\.][0-9]{3,5}$/', $cell) || preg_match('/^\d{1,2}[,\.]\d{3,5}$/', $cell)) {
            $decimales[] = $val;
            continue;
        }
        if (ppto_pdf_es_celda_monto($cell)) {
            $montos[] = $val;
            continue;
        }
        if (preg_match('/^\d+$/', $cell)) {
            $enteros[] = (int)$cell;
        }
    }
    $meta['dias_laborables'] = ppto_pdf_dias_laborables_default();

    if (count($montos) >= 2) {
        $meta['presup_anual_pdf'] = (float)$montos[0];
        $meta['monto_recalc'] = (float)$montos[count($montos) - 1];
    } elseif (count($montos) === 1) {
        if (ppto_pdf_meta_enteros_anual_tn($enteros, $anualBare, $tnBare)) {
            $meta['presup_anual_pdf'] = $anualBare;
            $meta['tn_dia'] = $tnBare;
            $meta['monto_recalc'] = (float)$montos[0];
        } else {
            $meta['presup_anual_pdf'] = (float)$montos[0];
        }
    } elseif (!empty($enteros)) {
        ppto_pdf_meta_asignar_enteros_bare($meta, $enteros);
    }

    if ((float)$meta['tn_dia'] <= 0) {
        foreach ($enteros as $n) {
            if ($n >= 3000 && $n <= 9999) {
                $meta['tn_dia'] = (float)$n;
                break;
            }
        }
    }
    foreach ($enteros as $n) {
        if ($n === 12) {
            $meta['meses'] = 12;
        }
    }

    if (count($decimales) > 0) {
        if (count($decimales) > 1) {
            $meta['factor_anual'] = (float)$decimales[0];
            $meta['usd_ton'] = (float)$decimales[count($decimales) - 1];
        } else {
            $unico = (float)$decimales[0];
            if ($unico >= 1) {
                $meta['factor_anual'] = $unico;
                $meta['usd_ton'] = round($unico / 12, 6);
            } else {
                $meta['usd_ton'] = $unico;
                $meta['factor_anual'] = round($unico * 12, 6);
            }
        }
    }
    return $meta;
}

/**
 * Caso Gaviones: ANUAL y tn/dia como enteros (3500 + 3500) y monto recalc. formateado al final.
 *
 * @param array $enteros
 * @param float $anual
 * @param float $tn
 * @return bool
 */
function ppto_pdf_meta_enteros_anual_tn($enteros, &$anual, &$tn) {
    $anual = 0;
    $tn = 0;
    $candidates = array();
    foreach ((array)$enteros as $n) {
        $n = (int)$n;
        if ($n === 12 || ($n >= 28 && $n <= 31)) {
            continue;
        }
        if ($n >= 100) {
            $candidates[] = $n;
        }
    }
    if (count($candidates) < 2) {
        return false;
    }
    if ($candidates[1] < 3000 || $candidates[1] > 9999) {
        return false;
    }
    $anual = (float)$candidates[0];
    $tn = (float)$candidates[1];
    return true;
}

/**
 * PDF exporta ANUAL y tn/dia como enteros sueltos (ej. 3500 + 3500 en Gaviones).
 *
 * @param array $meta
 * @param array $enteros
 */
function ppto_pdf_meta_asignar_enteros_bare(&$meta, $enteros) {
    $candidates = array();
    foreach ($enteros as $n) {
        $n = (int)$n;
        if ($n === 12) {
            $meta['meses'] = 12;
            continue;
        }
        if ($n >= 28 && $n <= 31) {
            continue;
        }
        if ($n >= 100) {
            $candidates[] = $n;
        }
    }
    if (empty($candidates)) {
        return;
    }
    $meta['presup_anual_pdf'] = (float)$candidates[0];
    if (count($candidates) >= 2 && $candidates[1] >= 3000 && $candidates[1] <= 9999) {
        $meta['tn_dia'] = (float)$candidates[1];
        return;
    }
    if ($candidates[0] >= 3000 && $candidates[0] <= 9999) {
        $meta['tn_dia'] = (float)$candidates[0];
    }
}

/**
 * Indica si la meta ya tiene todos los campos de un rubro detalle (no fila total).
 *
 * @param array $meta
 * @return bool
 */
function ppto_pdf_meta_rubro_completo($meta) {
    if ((float)$meta['presup_anual_pdf'] <= 0) {
        return false;
    }
    if ((float)$meta['tn_dia'] <= 0 || (float)$meta['dias_laborables'] <= 0) {
        return false;
    }
    if ((float)$meta['factor_anual'] <= 0.00001 && (float)$meta['usd_ton'] <= 0.00001) {
        return false;
    }
    if ((float)$meta['monto_recalc'] > 0) {
        return true;
    }
    return false;
}

function ppto_pdf_es_celda_total_grupo($cell, $meta_parcial) {
    if (!ppto_pdf_es_celda_monto($cell)) {
        return false;
    }
    $val = ppto_pdf_parse_numero($cell);
    $anual = (float)$meta_parcial['presup_anual_pdf'];
    $tn = (float)$meta_parcial['tn_dia'];
    if ($tn <= 0 || $anual <= 0) {
        return false;
    }
    if ((float)$meta_parcial['monto_recalc'] > 0) {
        return false;
    }
    if ($anual < 15000 && $val >= 15000) {
        return true;
    }
    if ($val >= $anual * 8) {
        return true;
    }
    return false;
}

function ppto_pdf_es_linea_rubro_principal($line) {
    $line = trim($line);
    if ($line === '') {
        return false;
    }
    if (strlen($line) >= 48) {
        return true;
    }
    if (preg_match('/pol.*meros/i', $line) || preg_match('/(software|planning|enterprise|vehicular|sismicos|estructuras|torniquete|bascula|consumibles|geomembrana|bombas|repuestos|gaviones|mac\s*drain|geo\s*tubos|mac drain)/i', $line)) {
        return true;
    }
    return false;
}

/**
 * Indica si las siguientes celdas del PDF forman un rubro driver con montos.
 *
 * @param array $raw
 * @param int $idx
 * @param int $total
 * @return bool
 */
function ppto_pdf_siguiente_tiene_datos_rubro($raw, $idx, $total) {
    $cells = array();
    $k = $idx + 1;
    while ($k < $total) {
        $cell = trim(preg_replace('/\s+/', ' ', $raw[$k]));
        if ($cell === '') {
            $k++;
            continue;
        }
        if (ppto_pdf_parse_linea_seccion($cell)) {
            $k++;
            continue;
        }
        if (ppto_pdf_es_titulo_seccion($cell)) {
            $k++;
            continue;
        }
        if (ppto_pdf_es_linea_componente_sin_datos($cell)) {
            return false;
        }
        if (preg_match('/^[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $cell) && !ppto_pdf_es_celda_monto($cell) && !preg_match('/^\d+$/', $cell)) {
            if (count($cells) === 0) {
                if (ppto_pdf_es_linea_componente_sin_datos($cell)) {
                    return false;
                }
                if (!ppto_pdf_es_titulo_seccion($cell)) {
                    $k++;
                    continue;
                }
                return false;
            }
            return false;
        }
        $cells[] = $cell;
        if (ppto_pdf_meta_rubro_completo(ppto_pdf_meta_desde_celdas($cells))) {
            return true;
        }
        $k++;
        if ($k - $idx > 16) {
            break;
        }
    }
    return false;
}

/**
 * Sub-lineas descriptivas bajo un rubro principal (sin fila de montos propia).
 *
 * @param string $line
 * @return bool
 */
function ppto_pdf_es_linea_componente_sin_datos($line) {
    $line = trim($line);
    if ($line === '') {
        return true;
    }
    if (ppto_pdf_es_linea_rubro_principal($line) || ppto_pdf_parse_linea_seccion($line)) {
        return false;
    }
    if (preg_match('/pol.*meros/i', $line) || preg_match('/(repuestos|consumibles|geomembrana|bombas|mantenimiento|plomer|tratamiento|campamento|gaviones|mac\s*drain|geo\s*tubos)/i', $line)) {
        return false;
    }
    if (preg_match('/^(Automatizaci|Adecuaci|Electricidad|Pintura|Señal|Senal|senal|chasis|herrajes|acoples|Trazabilidad|Guias|Volquetas|Control de)/i', $line)) {
        return true;
    }
    return false;
}

/**
 * Avanza el indice saltando sub-lineas sin datos y montos huerfanos asociados.
 *
 * @param array $raw
 * @param int $start
 * @param int $total
 * @return int
 */
function ppto_pdf_saltar_lineas_sin_datos($raw, $start, $total) {
    $j = $start + 1;
    $cells = array();
    while ($j < $total) {
        $cell = trim(preg_replace('/\s+/', ' ', $raw[$j]));
        if ($cell === '') {
            $j++;
            continue;
        }
        if (ppto_pdf_parse_linea_seccion($cell)) {
            return $j;
        }
        if (ppto_pdf_es_titulo_seccion($cell)) {
            return $j;
        }
        if (preg_match('/^[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $cell) && !ppto_pdf_es_celda_monto($cell) && !preg_match('/^\d+$/', $cell)) {
            if (ppto_pdf_es_linea_componente_sin_datos($cell) || ppto_pdf_es_linea_lista_catalogo($cell, $raw, $j, $total)) {
                $j++;
                $cells = array();
                continue;
            }
            if (count($cells) === 0 && ppto_pdf_es_linea_rubro_principal($cell)) {
                return $j;
            }
            return $j;
        }
        $cells[] = $cell;
        $j++;
        if (ppto_pdf_meta_rubro_completo(ppto_pdf_meta_desde_celdas($cells))) {
            return $j;
        }
        if (count($cells) > 14) {
            return $j;
        }
    }
    return $j;
}

/**
 * Meta con montos validos para rubro driver (no fila vacia ni sub-detalle).
 *
 * @param array $meta
 * @param string $desc
 * @return bool
 */
function ppto_pdf_meta_es_rubro_driver($meta, $desc = '') {
    if (ppto_pdf_es_meta_fila_totales($meta)) {
        return false;
    }
    $desc = trim($desc !== '' ? $desc : (isset($meta['descripcion']) ? $meta['descripcion'] : ''));
    $anual = (float)$meta['presup_anual_pdf'];
    $monto = (float)$meta['monto_recalc'];
    $factor = (float)$meta['factor_anual'];
    if ($desc !== '' && ppto_pdf_es_linea_componente_sin_datos($desc)) {
        if ($anual < 50 && $monto < 50 && $factor <= 0.00001) {
            return false;
        }
    }
    if (!ppto_pdf_meta_rubro_completo($meta)) {
        return false;
    }
    return ($anual >= 50 || $monto >= 50 || ($factor > 0.00001 && $monto > 0));
}

function ppto_pdf_es_linea_lista_catalogo($line, $raw = null, $idx = null, $total = 0) {
    $line = trim($line);
    if ($line === '' || preg_match('/^\d/', $line)) {
        return false;
    }
    if (preg_match('/^[A-D]$/i', $line)) {
        return false;
    }
    if (ppto_pdf_parse_linea_seccion($line)) {
        return false;
    }
    if (ppto_pdf_es_linea_solo_totales($line) || ppto_pdf_es_celda_monto($line)) {
        return false;
    }
    if (ppto_pdf_es_linea_rubro_principal($line)) {
        return false;
    }
    if (ppto_pdf_es_titulo_seccion($line)) {
        return false;
    }
    if (ppto_pdf_es_linea_componente_sin_datos($line)) {
        return true;
    }
    if (preg_match('/^(Total|TOTAL|PRESUPUESTO|ANUAL)/i', $line)) {
        return false;
    }
    if ($raw !== null && $idx !== null && (int)$total > 0 && ppto_pdf_siguiente_tiene_datos_rubro($raw, (int)$idx, (int)$total)) {
        return false;
    }
    if (preg_match('/^[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $line) && strlen($line) <= 120) {
        return true;
    }
    return false;
}

/**
 * Linea del PDF que solo contiene totales del grupo (sin descripcion de rubro).
 *
 * @param string $line
 * @return bool
 */
function ppto_pdf_es_linea_solo_totales($line) {
    $line = trim($line);
    if ($line === '') {
        return false;
    }
    if (preg_match('/^(total|subtotal|suma)\b/i', $line)) {
        return true;
    }
    if (!preg_match('/[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $line) && preg_match('/\d{1,3}\.\d{3},\d{2}/', $line)) {
        return true;
    }
    return false;
}

/**
 * Meta parseada que corresponde a fila de totales (sin tn/dia ni dias laborables).
 *
 * @param array $meta
 * @return bool
 */
function ppto_pdf_es_meta_fila_totales($meta) {
    $anual = (float)$meta['presup_anual_pdf'];
    $monto = (float)$meta['monto_recalc'];
    $tn = (float)$meta['tn_dia'];
    $dias = (float)$meta['dias_laborables'];
    if ($tn > 0 || $dias > 0) {
        return false;
    }
    if ($anual >= 5000 || $monto >= 5000) {
        return true;
    }
    return false;
}

/**
 * Construye linea normalizada con metadatos de columnas PDF.
 *
 * @param string $desc
 * @param array $meta
 * @return string
 */
function ppto_pdf_linea_rubro_meta($desc, $meta) {
    return $desc . '||'
        . number_format((float)$meta['factor_anual'], 6, '.', '') . '||'
        . number_format((float)$meta['presup_anual_pdf'], 2, '.', '') . '||'
        . number_format((float)$meta['tn_dia'], 2, '.', '') . '||'
        . number_format((float)$meta['dias_laborables'], 0, '.', '') . '||'
        . number_format((float)$meta['meses'], 0, '.', '') . '||'
        . number_format((float)$meta['usd_ton'], 6, '.', '') . '||'
        . number_format((float)$meta['monto_recalc'], 2, '.', '');
}

/**
 * Ajusta factor anual en meta parseada y la agrega al arreglo normalizado.
 *
 * @param array $out
 * @param string $desc
 * @param array $meta
 * @return bool
 */
function ppto_pdf_intentar_emitir_rubro_meta(&$out, $desc, $meta) {
    if (!ppto_pdf_meta_es_rubro_driver($meta, $desc)) {
        return false;
    }
    $factor = (float)$meta['factor_anual'];
    if ($factor <= 0.0001 && (float)$meta['usd_ton'] > 0) {
        $factor = round((float)$meta['usd_ton'] * 12, 6);
        $meta['factor_anual'] = $factor;
    }
    if ($factor <= 0.0001 && (float)$meta['presup_anual_pdf'] > 0) {
        $ton_est = ppto_pdf_ton_base_mes_default();
        $factor = round((float)$meta['presup_anual_pdf'] / $ton_est, 6);
        $meta['factor_anual'] = $factor;
    }
    if ($factor > 0.00001 && $factor < 50) {
        $out[] = ppto_pdf_linea_rubro_meta($desc, $meta);
        return true;
    }
    if ((float)$meta['presup_anual_pdf'] > 0 || (float)$meta['monto_recalc'] > 0) {
        $out[] = ppto_pdf_linea_rubro_meta($desc, $meta);
        return true;
    }
    return false;
}

/**
 * Lee bloque numerico completo (sin descripcion al inicio).
 *
 * @param array $raw
 * @param int $start
 * @param int $total
 * @return array|null
 */
function ppto_pdf_leer_bloque_meta_numerico($raw, $start, $total) {
    $cells = array();
    $j = (int)$start;
    while ($j < $total) {
        $cell = trim(preg_replace('/\s+/', ' ', $raw[$j]));
        if ($cell === '') {
            $j++;
            continue;
        }
        if (ppto_pdf_parse_linea_seccion($cell) || ppto_pdf_es_linea_solo_totales($cell)) {
            break;
        }
        if (preg_match('/^(Total|TOTAL|PRESUPUESTO\s+GRUPO)/i', $cell)) {
            break;
        }
        if (preg_match('/^[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $cell) && !ppto_pdf_es_celda_monto($cell) && !preg_match('/^\d+$/', $cell)) {
            break;
        }
        $cells[] = $cell;
        $j++;
        if (ppto_pdf_meta_rubro_completo(ppto_pdf_meta_desde_celdas($cells))) {
            return array(
                'cells' => $cells,
                'meta' => ppto_pdf_meta_desde_celdas($cells),
                'end' => $j,
            );
        }
        if (count($cells) > 14) {
            break;
        }
    }
    return null;
}

/**
 * Asigna bloques numericos huerfanos a descripciones en cola (PDF con filas desordenadas).
 *
 * @param array $out
 * @param array $raw
 * @param int $j
 * @param int $total
 * @param array $pending_rubros
 * @return int
 */
function ppto_pdf_procesar_metas_huerfanas(&$out, $raw, $j, $total, &$pending_rubros) {
    while (!empty($pending_rubros) && $j < $total) {
        $bloque = ppto_pdf_leer_bloque_meta_numerico($raw, $j, $total);
        if ($bloque === null) {
            break;
        }
        $desc = array_shift($pending_rubros);
        ppto_pdf_intentar_emitir_rubro_meta($out, $desc, $bloque['meta']);
        $j = (int)$bloque['end'];
    }
    return $j;
}

/**
 * @param string $desc
 * @return bool
 */
function ppto_pdf_es_descripcion_rubro_pendiente($desc) {
    $desc = trim($desc);
    if ($desc === '' || ppto_pdf_parse_linea_seccion($desc) || ppto_pdf_es_titulo_seccion($desc)) {
        return false;
    }
    if (ppto_pdf_es_linea_componente_sin_datos($desc)) {
        return false;
    }
    return true;
}

/**
 * @param string $line
 * @return array|null
 */
function ppto_pdf_parse_linea_meta($line) {
    if (substr_count($line, '||') < 6) {
        return null;
    }
    $parts = explode('||', $line);
    $desc = array_shift($parts);
    return array(
        'descripcion' => trim($desc),
        'factor_anual' => isset($parts[0]) ? ppto_pdf_parse_numero($parts[0]) : 0,
        'presup_anual_pdf' => isset($parts[1]) ? ppto_pdf_parse_numero($parts[1]) : 0,
        'tn_dia' => isset($parts[2]) ? ppto_pdf_parse_numero($parts[2]) : 0,
        'dias_laborables' => isset($parts[3]) ? ppto_pdf_parse_numero($parts[3]) : 0,
        'meses' => isset($parts[4]) ? ppto_pdf_parse_numero($parts[4]) : 12,
        'usd_ton' => isset($parts[5]) ? ppto_pdf_parse_numero($parts[5]) : 0,
        'monto_recalc' => isset($parts[6]) ? ppto_pdf_parse_numero($parts[6]) : 0,
    );
}

/**
 * Indica si un texto es titulo de seccion A/B/C del PDF (no un rubro detalle).
 *
 * @param string $titulo
 * @return bool
 */
function ppto_pdf_es_titulo_seccion($titulo) {
    $titulo = trim($titulo);
    if ($titulo === '') {
        return false;
    }
    if (preg_match('/#\s*\d+/i', $titulo)) {
        return true;
    }
    if (preg_match('/^(SEGURIDAD|CONSTRUCCION Y|CONSTRUCCION|ADECUACION|ARRIENDO|OFICINAS|ALQUILER|RELAVERAS|CAMPAMENTOS|EQUIPOS|HARDWARE|SOFTWARE|ERP|SERVICIOS|SUMINISTROS|MONITOREO|VIDEO|GASTOS|MANTENIMIENTO|TRIBUTARIOS|LOCALES|REGALIAS|SOCIALES|DISPOSICION|DIOSPOSICION|INFRAESTRUCTURA|PERSONAL|ADMINISTRATIVO|PROCESOS|CUMPLIMIENTOS|SEGUROS|EXPANSION)/i', $titulo)) {
        return true;
    }
    if (strpos($titulo, ',') !== false && strlen($titulo) >= 18) {
        if (preg_match('/^(SEGURIDAD|CONSTRUCCION|ADECUACION|ARRIENDO|OFICINAS|ALQUILER|RELAVERAS|CAMPAMENTOS|EQUIPOS|HARDWARE|SOFTWARE|ERP|SERVICIOS|SUMINISTROS|MONITOREO|VIDEO|GASTOS|MANTENIMIENTO|TRIBUTARIOS|LOCALES|REGALIAS|SOCIALES|DISPOSICION|DIOSPOSICION|INFRAESTRUCTURA|PERSONAL|ADMINISTRATIVO|PROCESOS|CUMPLIMIENTOS|SEGUROS|EXPANSION)/i', $titulo)) {
            return true;
        }
    }
    return false;
}

/**
 * Letra A-H siguiente para seccion sin prefijo en el PDF (ej. solo "MANTENIMIENTO").
 *
 * @param string|null $ultima
 * @return string
 */
function ppto_pdf_inferir_letra_seccion($ultima) {
    if ($ultima === null || $ultima === '') {
        return 'A';
    }
    $letra = strtoupper(substr($ultima, 0, 1));
    $ord = ord($letra);
    if ($ord >= ord('A') && $ord < ord('H')) {
        return chr($ord + 1);
    }
    return $letra;
}

/**
 * Titulo de seccion A/B/C sin letra al inicio (celda PDF sin columna A/B/C).
 *
 * @param string $line
 * @param array|null $raw
 * @param int|null $idx
 * @param int $total
 * @return bool
 */
function ppto_pdf_es_seccion_titulo_suelto($line, $raw = null, $idx = null, $total = 0) {
    $line = trim($line);
    if ($line === '' || ppto_pdf_parse_linea_seccion($line) !== null) {
        return false;
    }
    if (preg_match('/^[A-D][\.\)\-\s]/i', $line) || ppto_pdf_es_linea_rubro_principal($line)) {
        return false;
    }
    if (ppto_pdf_es_linea_componente_sin_datos($line)) {
        return false;
    }
    if (!ppto_pdf_es_titulo_seccion($line)) {
        return false;
    }
    if ($raw !== null && $idx !== null && (int)$total > 0) {
        return ppto_pdf_siguiente_tiene_datos_rubro($raw, (int)$idx, (int)$total)
            || ppto_pdf_siguiente_es_rubro_principal($raw, (int)$idx, (int)$total);
    }
    return true;
}

/**
 * @param array $raw
 * @param int $idx
 * @param int $total
 * @return bool
 */
function ppto_pdf_siguiente_es_rubro_principal($raw, $idx, $total) {
    $k = $idx + 1;
    while ($k < $total) {
        $cell = trim(preg_replace('/\s+/', ' ', $raw[$k]));
        if ($cell === '') {
            $k++;
            continue;
        }
        if (ppto_pdf_parse_linea_seccion($cell) || ppto_pdf_es_linea_componente_sin_datos($cell)) {
            return false;
        }
        return ppto_pdf_es_linea_rubro_principal($cell);
    }
    return false;
}

/**
 * Rubro con prefijo A./B. dentro de un grupo (no es seccion principal).
 *
 * @param string $titulo
 * @return bool
 */
function ppto_pdf_es_subseccion_rubro($titulo) {
    $titulo = trim($titulo);
    if ($titulo === '') {
        return false;
    }
    if (preg_match('/^(CONSTRUCCION DE|READECUACION|AMPLIACION DE|ESTUDIOS TECNICOS|ESTUDIOS)/i', $titulo)) {
        return true;
    }
    return false;
}

/**
 * Evita tratar rubros cuya descripcion empieza con A-D como seccion pegada (A+UDITORIAS).
 *
 * @param string $letra
 * @param string $resto
 * @return bool
 */
function ppto_pdf_es_falso_seccion_glued($letra, $resto) {
    $full = strtoupper(trim($letra . $resto));
    $falsos = array(
        'AUDITORIAS', 'ACTUALIZACION', 'ADMINISTRACION', 'ADQUISICION', 'ADECUACION',
        'DIRECCION', 'DISTRIBUCION', 'DONACIONES', 'DESARROLLO', 'DOCUMENTACION',
        'CONTRIBUCION', 'CAPACITACION', 'COMPUTADORES', 'CONTROL', 'CAMPAMENTOS',
        'BODEGA', 'BOMBA', 'BATERIAS',
    );
    foreach ($falsos as $f) {
        if (strpos($full, $f) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Quita prefijo A./B. de sub-rubros dentro de un grupo.
 *
 * @param string $desc
 * @return string
 */
function ppto_pdf_limpiar_prefijo_subrubro($desc) {
    $desc = trim($desc);
    if (preg_match('/^[A-D][\.\)\-]\s+(.+)$/i', $desc, $m) && ppto_pdf_es_subseccion_rubro(trim($m[1]))) {
        return trim($m[1]);
    }
    return $desc;
}

/**
 * Indica si una linea continua la descripcion del rubro anterior.
 *
 * @param string $line
 * @return bool
 */
function ppto_pdf_es_continuacion_descripcion($line) {
    $line = trim($line);
    if ($line === '' || preg_match('/^\d/', $line)) {
        return false;
    }
    if (ppto_pdf_parse_linea_seccion($line)) {
        return false;
    }
    if (preg_match('/^(Total|TOTAL|PRESUPUESTO|ANUAL)/i', $line)) {
        return false;
    }
    if (preg_match('/^(y|de|del|la|el|en|para|con|sin|los|las)\s+/i', $line)) {
        return true;
    }
    if (preg_match('/^[a-záéíóúñ]/u', $line)) {
        return true;
    }
    return false;
}

/**
 * Detecta linea de seccion A/B/C (A. TITULO, A TITULO, ATITULO sin espacio).
 *
 * @param string $line
 * @return array|null
 */
function ppto_pdf_parse_linea_seccion($line) {
    $line = trim($line);
    if ($line === '') {
        return null;
    }
    if (preg_match('/^([A-D])[\.\)\-]\s+(.+)$/i', $line, $m)) {
        $titulo = trim($m[2]);
        if (ppto_pdf_es_subseccion_rubro($titulo)) {
            return null;
        }
        if (strlen($titulo) >= 3) {
            return array('letra' => strtoupper($m[1]), 'titulo' => $titulo);
        }
    }
    if (preg_match('/^([A-D])([A-Z0-9#].+)$/', $line, $m)) {
        $titulo = trim($m[2]);
        if (!ppto_pdf_es_falso_seccion_glued($m[1], $titulo) && ppto_pdf_es_titulo_seccion($titulo)) {
            return array('letra' => strtoupper($m[1]), 'titulo' => $titulo);
        }
    }
    if (preg_match('/^([A-D])\s+(.+)$/i', $line, $m)) {
        $titulo = trim($m[2]);
        if (ppto_pdf_es_titulo_seccion($titulo) && !preg_match('/\d{1,3}\.\d{3},\d{2}\s*$/', $line)) {
            return array('letra' => strtoupper($m[1]), 'titulo' => $titulo);
        }
    }
    return null;
}

/**
 * Normaliza texto extraido de PDF tipo Excel (una celda por linea).
 *
 * @param string $text
 * @return array
 */
function ppto_pdf_es_linea_cabecera_tabla($line) {
    $line_l = strtolower($line);
    $line_l = str_replace(array('�', '�', '�', '�', '�', '�'), array('a', 'e', 'i', 'o', 'u', 'n'), $line_l);
    $keys = array('presupuesto', 'periodo', 'prorrata', 'anualsi', 'anual', 'dias', 'monto', 'laborables', 'meses', 'usd', 'recalculado', 'tn /');
    foreach ($keys as $k) {
        if (strpos($line_l, $k) === 0) {
            return true;
        }
    }
    return false;
}

function ppto_pdf_normalizar_texto_presupuesto($text) {
    $text = ppto_pdf_a_utf8($text);
    $raw = explode("\n", str_replace(array("\r\n", "\r"), "\n", $text));
    $total = count($raw);
    for ($ri = 0; $ri < $total; $ri++) {
        $raw[$ri] = ppto_pdf_a_utf8($raw[$ri]);
    }
    $out = array();
    $i = 0;
    $ultima_seccion_letra = null;
    $pending_rubros = array();

    while ($i < $total) {
        $line = trim(preg_replace('/\s+/', ' ', $raw[$i]));
        if ($line === '') {
            $i++;
            continue;
        }

        if (substr_count($line, '||') >= 6) {
            $out[] = $line;
            $i++;
            continue;
        }

        if (preg_match('/^MESES_GRUPO\|\|/i', $line)) {
            $out[] = $line;
            $i++;
            continue;
        }

        if (preg_match('/PRESUPUESTO\s+GRUPO/i', $line)) {
            $out[] = preg_replace('/\s+:/', ':', $line);
            $i++;
            continue;
        }

        if (ppto_pdf_es_linea_cabecera_tabla($line)) {
            $i++;
            continue;
        }

        $sec = ppto_pdf_parse_linea_seccion($line);
        if ($sec) {
            $titulo = $sec['titulo'];
            if (($i + 1) < $total) {
                $next = trim(preg_replace('/\s+/', ' ', $raw[$i + 1]));
                if ($next !== '' && preg_match('/^(REMOTA|LOCAL|PRESUPUESTO)/i', $next)
                    && !preg_match('/^\d/', $next) && strlen($next) < 48) {
                    $titulo .= ' ' . $next;
                    $i++;
                }
            }
            $out[] = $sec['letra'] . '. ' . $titulo;
            $ultima_seccion_letra = strtoupper($sec['letra']);
            $i++;
            continue;
        }

        if (preg_match('/^[A-D]$/i', $line) && ($i + 1) < $total) {
            $next = trim(preg_replace('/\s+/', ' ', $raw[$i + 1]));
            if ($next !== '' && !ppto_pdf_es_linea_componente_sin_datos($next)
                && (preg_match('/^[A-ZÁÉÍÓÚÑ]/u', $next) || ppto_pdf_es_titulo_seccion($next))
                && !preg_match('/^\d/', $next)) {
                $letra = strtoupper($line);
                $out[] = $letra . '. ' . $next;
                $ultima_seccion_letra = $letra;
                $i += 2;
                continue;
            }
        }

        if (preg_match('/^([A-D])[\.\)\-]\s+(.+)$/i', $line, $m)) {
            $tituloSec = trim($m[2]);
            if (!ppto_pdf_es_subseccion_rubro($tituloSec) && strlen($tituloSec) >= 3) {
                $letra = strtoupper($m[1]);
                $out[] = $letra . '. ' . $tituloSec;
                $ultima_seccion_letra = $letra;
                $i++;
                continue;
            }
        }

        if (ppto_pdf_es_seccion_titulo_suelto($line, $raw, $i, $total)) {
            $letra = ppto_pdf_inferir_letra_seccion($ultima_seccion_letra);
            $out[] = $letra . '. ' . $line;
            $ultima_seccion_letra = $letra;
            $i++;
            continue;
        }

        if (ppto_pdf_es_linea_solo_totales($line)) {
            $i++;
            continue;
        }

        if (!preg_match('/^[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $line) && ppto_pdf_es_celda_monto($line)) {
            $i++;
            continue;
        }

        if (preg_match('/^[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $line)) {
            $desc = ppto_pdf_limpiar_prefijo_subrubro($line);
            $inline_anual = '';
            if (preg_match('/^(.+?)(\d{1,3}\.\d{3},\d{2})$/', $desc, $mm)) {
                $before = trim($mm[1]);
                if (!preg_match('/USD\s*$/i', $before)) {
                    $desc = $before;
                    $inline_anual = $mm[2];
                }
            } elseif (preg_match('/^(.+?)(\d+,\d{2})$/', $desc, $mm) && ppto_pdf_parse_numero($mm[2]) >= 500) {
                $before = trim($mm[1]);
                if (!preg_match('/USD\s*$/i', $before)) {
                    $desc = $before;
                    $inline_anual = $mm[2];
                }
            }

            $last_desc_idx = $i;
            while (($last_desc_idx + 1) < $total) {
                $peek = trim(preg_replace('/\s+/', ' ', $raw[$last_desc_idx + 1]));
                if (!ppto_pdf_es_continuacion_descripcion($peek)) {
                    break;
                }
                $desc .= ' ' . $peek;
                $last_desc_idx++;
            }

            if (ppto_pdf_es_linea_componente_sin_datos($desc)) {
                $i = ppto_pdf_saltar_lineas_sin_datos($raw, $last_desc_idx, $total);
                continue;
            }
            if (ppto_pdf_es_linea_lista_catalogo($desc, $raw, $last_desc_idx, $total)
                && !ppto_pdf_siguiente_tiene_datos_rubro($raw, $last_desc_idx, $total)) {
                $i = ppto_pdf_saltar_lineas_sin_datos($raw, $last_desc_idx, $total);
                continue;
            }

            $cells = array();
            if ($inline_anual !== '') {
                $cells[] = $inline_anual;
            }
            $j = $last_desc_idx + 1;
            while ($j < $total) {
                $cell = trim(preg_replace('/\s+/', ' ', $raw[$j]));
                if ($cell === '') {
                    $j++;
                    continue;
                }
                if (ppto_pdf_es_continuacion_descripcion($cell)) {
                    $desc .= ' ' . $cell;
                    $j++;
                    continue;
                }
                if (ppto_pdf_parse_linea_seccion($cell)) {
                    $j++;
                    continue;
                }
                if (preg_match('/^[A-Za-záéíóúñÁÉÍÓÚÑ]/u', $cell) && !preg_match('/^\d/', $cell)) {
                    if (count($cells) === 0 && ppto_pdf_es_linea_lista_catalogo($cell, $raw, $j, $total)) {
                        $j++;
                        continue;
                    }
                    if (count($cells) === 0 && ppto_pdf_es_linea_componente_sin_datos($cell)) {
                        $j++;
                        continue;
                    }
                    break;
                }
                if (preg_match('/^(Total|TOTAL)/i', $cell)) {
                    break;
                }
                $meta_parcial = ppto_pdf_meta_desde_celdas($cells);
                if (ppto_pdf_es_celda_total_grupo($cell, $meta_parcial)) {
                    break;
                }
                $cells[] = $cell;
                $j++;
                if (ppto_pdf_meta_rubro_completo(ppto_pdf_meta_desde_celdas($cells))) {
                    break;
                }
            }

            $meta = ppto_pdf_meta_desde_celdas($cells);
            if (!ppto_pdf_meta_es_rubro_driver($meta, $desc)) {
                if (count($cells) === 0 && ppto_pdf_es_descripcion_rubro_pendiente($desc)) {
                    $pending_rubros[] = $desc;
                }
                $i = $j;
                continue;
            }
            if (ppto_pdf_intentar_emitir_rubro_meta($out, $desc, $meta)) {
                $j = ppto_pdf_procesar_metas_huerfanas($out, $raw, $j, $total, $pending_rubros);
            }
            $i = $j;
            continue;
        }

        $i++;
    }

    return $out;
}

function ppto_pdf_aplicar_meses_grupo(&$result) {
    $meses = isset($result['meses_prorrateo_global']) ? (int)$result['meses_prorrateo_global'] : 0;
    if ($meses < 1) {
        return;
    }
    foreach ($result['partidas'] as $idx => $p) {
        if (isset($p['clase']) && $p['clase'] === 'G') {
            $result['partidas'][$idx]['meses_prorrateo'] = $meses;
        }
    }
    foreach ($result['rubros'] as $idx => $r) {
        $result['rubros'][$idx]['meses'] = $meses;
    }
}

/**
 * Parsea texto PDF a arbol de partidas y rubros driver.
 *
 * @param string $text
 * @return array
 */
function ppto_pdf_parsear_presupuesto($text) {
    $result = array(
        'ton_base' => ppto_pdf_detectar_ton_base($text),
        'partidas' => array(),
        'rubros' => array(),
        'lineas' => array(),
        'warnings' => array(),
    );

    $norm = ppto_pdf_normalizar_texto_presupuesto($text);
    $result['lineas'] = array_slice($norm, 0, 80);

    if (preg_match('/^MESES_GRUPO\|\|(\d+)/im', $text, $mm)) {
        $result['meses_prorrateo_global'] = (int)$mm[1];
    }

    $capitulo_cod = null;
    $grupo_cod = null;
    $grupo_idx = 0;
    $rubro_seq = array();

    foreach ($norm as $line) {
        if ($capitulo_cod === null && preg_match('/PRESUPUESTO\s+GRUPO\s*:?\s*(\d{1,2})\.\s+(.+)/i', $line, $gm)) {
            $capitulo_cod = str_pad((int)$gm[1], 2, '0', STR_PAD_LEFT);
            $grupo_cod = null;
            $grupo_idx = 0;
            $result['partidas'][] = array(
                'codigo' => $capitulo_cod,
                'descripcion' => trim($gm[2]),
                'nivel' => 1,
                'clase' => 'G',
                'padre_codigo' => null,
            );
        }
    }

    foreach ($norm as $line) {
        $line_u = $line;

        if (preg_match('/^(\d{2}(?:\.\d{2}){0,2})\s+(.+?)(?:\s+([0-9]+[,\.][0-9]{2,6}))?\s*$/', $line_u, $m)) {
            $cod = $m[1];
            $desc = trim($m[2]);
            $factor = isset($m[3]) ? ppto_pdf_parse_numero($m[3]) : 0;
            $nivel = substr_count($cod, '.') + 1;
            if ($factor > 0.00001 && $factor < 500) {
                ppto_pdf_push_rubro($result, ppto_partida_prefijo_padre_codigo($cod), $rubro_seq, $cod, $desc, $factor);
            } else {
                $result['partidas'][] = array(
                    'codigo' => $cod,
                    'descripcion' => $desc,
                    'nivel' => $nivel,
                    'clase' => ($nivel >= 3) ? 'D' : 'G',
                    'padre_codigo' => ppto_partida_prefijo_padre_codigo($cod),
                );
            }
            continue;
        }

        if (preg_match('/^(?:PRESUPUESTO\s+)?(?:GRUPO\s*:\s*)?(\d{1,2})\.\s+(.+)$/i', $line_u, $m)
            || preg_match('/PRESUPUESTO\s+GRUPO\s*:?\s*(\d{1,2})\.\s+(.+)/i', $line_u, $m)) {
            $capitulo_cod = str_pad((int)$m[1], 2, '0', STR_PAD_LEFT);
            $grupo_cod = null;
            $grupo_idx = 0;
            $result['partidas'][] = array(
                'codigo' => $capitulo_cod,
                'descripcion' => trim($m[2]),
                'nivel' => 1,
                'clase' => 'G',
                'padre_codigo' => null,
            );
            continue;
        }

        if (preg_match('/^(\d{1,2})[\.\-\)]\s+([A-Z0-9][A-Z0-9\s,\.\-\/\(\)]+)$/i', $line_u, $m)) {
            $capitulo_cod = str_pad((int)$m[1], 2, '0', STR_PAD_LEFT);
            $grupo_cod = null;
            $grupo_idx = 0;
            $result['partidas'][] = array(
                'codigo' => $capitulo_cod,
                'descripcion' => trim($m[2]),
                'nivel' => 1,
                'clase' => 'G',
                'padre_codigo' => null,
            );
            continue;
        }

        if (preg_match('/^(\d{1,2})\s+([A-Z0-9][A-Z0-9\s,\.\-\/\(\)]+)$/i', $line_u, $m)) {
            $capitulo_cod = str_pad((int)$m[1], 2, '0', STR_PAD_LEFT);
            $grupo_cod = null;
            $grupo_idx = 0;
            $result['partidas'][] = array(
                'codigo' => $capitulo_cod,
                'descripcion' => trim($m[2]),
                'nivel' => 1,
                'clase' => 'G',
                'padre_codigo' => null,
            );
            continue;
        }

        $sec = ppto_pdf_parse_linea_seccion($line_u);
        if ($sec !== null && $capitulo_cod !== null) {
            if (preg_match('/#\s*(\d+)/i', $sec['titulo'], $nm)) {
                $grupo_idx = (int)$nm[1];
            } else {
                $letra = strtoupper($sec['letra']);
                if ($letra >= 'A' && $letra <= 'H') {
                    $grupo_idx = ord($letra) - ord('A') + 1;
                } else {
                    $grupo_idx++;
                }
            }
            $grupo_cod = $capitulo_cod . '.' . str_pad((string)$grupo_idx, 2, '0', STR_PAD_LEFT);
            $rubro_seq[$grupo_cod] = 0;
            $existe_sec = false;
            foreach ($result['partidas'] as $p) {
                if ($p['codigo'] === $grupo_cod) {
                    $existe_sec = true;
                    break;
                }
            }
            if (!$existe_sec) {
                $result['partidas'][] = array(
                    'codigo' => $grupo_cod,
                    'descripcion' => $sec['titulo'],
                    'nivel' => 2,
                    'clase' => 'G',
                    'padre_codigo' => $capitulo_cod,
                );
            }
            continue;
        }

        if ($capitulo_cod !== null && $grupo_cod === null && substr_count($line_u, '||') >= 6) {
            $metaPeek = ppto_pdf_parse_linea_meta($line_u);
            if ($metaPeek && ppto_pdf_meta_es_rubro_driver($metaPeek, $metaPeek['descripcion'])) {
                $grupo_idx = 1;
                $grupo_cod = $capitulo_cod . '.01';
                $rubro_seq[$grupo_cod] = 0;
                $secPeek = ppto_pdf_parse_linea_seccion($metaPeek['descripcion']);
                $tituloGrupo = $secPeek ? $secPeek['titulo'] : $metaPeek['descripcion'];
                if (strlen($tituloGrupo) > 100) {
                    $tituloGrupo = substr($tituloGrupo, 0, 97) . '...';
                }
                $result['partidas'][] = array(
                    'codigo' => $grupo_cod,
                    'descripcion' => $tituloGrupo,
                    'nivel' => 2,
                    'clase' => 'G',
                    'padre_codigo' => $capitulo_cod,
                );
            }
        }

        if ($grupo_cod !== null && substr_count($line_u, '||') >= 6) {
            $metaLine = ppto_pdf_parse_linea_meta($line_u);
            if ($metaLine && ppto_pdf_meta_es_rubro_driver($metaLine, $metaLine['descripcion'])) {
                $factor = (float)$metaLine['factor_anual'];
                if ($factor <= 0.0001 && (float)$metaLine['usd_ton'] > 0) {
                    $factor = round((float)$metaLine['usd_ton'] * 12, 6);
                }
                if ($factor <= 0.0001 && (float)$metaLine['presup_anual_pdf'] > 0) {
                    $ton_est = $result['ton_base'] > 0 ? $result['ton_base'] : ppto_pdf_ton_base_mes_default();
                    $factor = round((float)$metaLine['presup_anual_pdf'] / $ton_est, 6);
                }
                if ($factor > 0.00001 && $factor < 50) {
                    $rubro_seq[$grupo_cod]++;
                    $seg = $rubro_seq[$grupo_cod];
                    $cod = $grupo_cod . '.' . str_pad((string)$seg, 2, '0', STR_PAD_LEFT);
                    ppto_pdf_push_rubro($result, $grupo_cod, $rubro_seq, $cod, $metaLine['descripcion'], $factor, $metaLine);
                    continue;
                }
            }
        }

        if ($grupo_cod !== null && preg_match('/^(.+?)\s+([0-9]{1,3}(?:\.[0-9]{3})+,[0-9]{2})\b/', $line_u, $m)) {
            $desc = trim($m[1]);
            $anual = ppto_pdf_parse_numero($m[2]);
            if ($anual >= 500 && !preg_match('/^(total|anual|presupuesto|usd|monto)/i', $desc)) {
                $ton = $result['ton_base'] > 0 ? $result['ton_base'] : ppto_pdf_ton_base_mes_default();
                $factor = round($anual / $ton, 6);
                if ($factor > 0.00001 && $factor < 500) {
                    $rubro_seq[$grupo_cod]++;
                    $seg = $rubro_seq[$grupo_cod];
                    $cod = $grupo_cod . '.' . str_pad((string)$seg, 2, '0', STR_PAD_LEFT);
                    ppto_pdf_push_rubro($result, $grupo_cod, $rubro_seq, $cod, $desc, $factor);
                    continue;
                }
            }
        }

        if (preg_match('/^(.+?)\s+(0[,\.][0-9]{3,5})\s*$/', $line_u, $m) && $grupo_cod !== null) {
            $desc = trim($m[1]);
            $factor = ppto_pdf_parse_numero($m[2]);
            if ($factor > 0.0001 && $factor < 50 && !preg_match('/^(total|subtotal|presupuesto|tonelada)/i', $desc)) {
                $rubro_seq[$grupo_cod]++;
                $seg = $rubro_seq[$grupo_cod];
                $cod = $grupo_cod . '.' . str_pad((string)$seg, 2, '0', STR_PAD_LEFT);
                ppto_pdf_push_rubro($result, $grupo_cod, $rubro_seq, $cod, $desc, $factor);
                continue;
            }
        }

        if (preg_match('/^(.+?)\s+([0-9]+[,\.][0-9]{3,6})\s*$/', $line_u, $m) && $grupo_cod !== null) {
            $desc = trim($m[1]);
            $factor = ppto_pdf_parse_numero($m[2]);
            if ($factor <= 0.00001 || $factor >= 500) {
                continue;
            }
            if (preg_match('/^(total|subtotal|presupuesto|tonelada)/i', $desc)) {
                continue;
            }
            $rubro_seq[$grupo_cod]++;
            $seg = $rubro_seq[$grupo_cod];
            $cod = $grupo_cod . '.' . str_pad((string)$seg, 2, '0', STR_PAD_LEFT);
            ppto_pdf_push_rubro($result, $grupo_cod, $rubro_seq, $cod, $desc, $factor);
        }
    }

    $result['partidas'] = ppto_pdf_dedup_partidas($result['partidas']);
    $result['rubros'] = ppto_pdf_dedup_rubros($result['rubros']);

    if (empty($result['partidas']) && empty($result['rubros'])) {
        $result['warnings'][] = 'No se detectaron partidas ni rubros. Verifique que el PDF tenga texto seleccionable (no solo imagen escaneada).';
    }

    if (!isset($result['meses_prorrateo_global']) || (int)$result['meses_prorrateo_global'] < 1) {
        foreach ($norm as $line) {
            if (preg_match('/^MESES_GRUPO\|\|(\d+)/i', $line, $mm)) {
                $result['meses_prorrateo_global'] = (int)$mm[1];
                break;
            }
        }
    }
    ppto_pdf_aplicar_meses_grupo($result);

    return $result;
}

/**
 * @param array $partidas
 * @return array
 */
function ppto_pdf_dedup_partidas($partidas) {
    $map = array();
    foreach ($partidas as $p) {
        $map[$p['codigo']] = $p;
    }
    $out = array_values($map);
    usort($out, 'ppto_pdf_cmp_codigo');
    return $out;
}

/**
 * @param array $rubros
 * @return array
 */
function ppto_pdf_dedup_rubros($rubros) {
    $map = array();
    foreach ($rubros as $r) {
        $map[$r['codigo']] = $r;
    }
    $out = array_values($map);
    usort($out, 'ppto_pdf_cmp_codigo');
    return $out;
}

/**
 * @param array $a
 * @param array $b
 * @return int
 */
function ppto_pdf_cmp_codigo($a, $b) {
    return strcmp($a['codigo'], $b['codigo']);
}

/**
 * Normaliza clase de partida a G o D.
 *
 * @param string $clase
 * @return string
 */
function ppto_pdf_normalizar_clase($clase) {
    return ($clase === 'G') ? 'G' : 'D';
}

/**
 * Etiqueta legible de clase.
 *
 * @param string $clase
 * @return string
 */
function ppto_pdf_clase_etiqueta($clase) {
    return (ppto_pdf_normalizar_clase($clase) === 'G') ? 'Grupo' : 'Detalle';
}

/**
 * Arma mapa codigo => item esperado desde partidas y rubros parseados.
 *
 * @param array $parsed
 * @return array
 */
function ppto_pdf_items_esperados($parsed) {
    $items = array();
    $partidas = isset($parsed['partidas']) ? $parsed['partidas'] : array();
    $rubros = isset($parsed['rubros']) ? $parsed['rubros'] : array();

    foreach ($partidas as $p) {
        if (empty($p['codigo'])) {
            continue;
        }
        $cod = $p['codigo'];
        $items[$cod] = array(
            'codigo' => $cod,
            'descripcion' => isset($p['descripcion']) ? $p['descripcion'] : '',
            'clase' => ppto_pdf_normalizar_clase(isset($p['clase']) ? $p['clase'] : 'G'),
            'padre_codigo' => !empty($p['padre_codigo']) ? $p['padre_codigo'] : null,
        );
    }

    foreach ($rubros as $r) {
        if (empty($r['codigo'])) {
            continue;
        }
        $cod = $r['codigo'];
        if (!isset($items[$cod])) {
            $padre = ppto_partida_prefijo_padre_codigo($cod);
            $items[$cod] = array(
                'codigo' => $cod,
                'descripcion' => isset($r['descripcion']) ? $r['descripcion'] : '',
                'clase' => 'D',
                'padre_codigo' => $padre ? $padre : null,
            );
        }
    }

    return $items;
}

/**
 * Carga partidas del catalogo por codigos.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param array $codigos
 * @return array
 */
function ppto_pdf_catalogo_por_codigos($mysqli, $emp_id, $codigos) {
    $map = array();
    $emp_id = (int)$emp_id;
    if (!$mysqli || $emp_id <= 0 || empty($codigos)) {
        return $map;
    }

    $in_list = array();
    foreach ($codigos as $c) {
        $c = trim((string)$c);
        if ($c === '') {
            continue;
        }
        $in_list[$c] = "'" . $mysqli->real_escape_string($c) . "'";
    }
    if (empty($in_list)) {
        return $map;
    }

    $sql = "SELECT ppa_codigo_clasificacion, COALESCE(NULLIF(ppa_clase, ''), 'D') AS ppa_clase, ppa_descripcion
            FROM exa_ppto_partidas
            WHERE emp_id = $emp_id
              AND ppa_codigo_clasificacion IN (" . implode(',', array_values($in_list)) . ")";
    $res = $mysqli->query($sql);
    while ($res && ($row = $res->fetch_assoc())) {
        $map[$row['ppa_codigo_clasificacion']] = array(
            'clase' => ppto_pdf_normalizar_clase($row['ppa_clase']),
            'descripcion' => $row['ppa_descripcion'],
        );
    }

    return $map;
}

/**
 * Valida partidas parseadas contra el catalogo (clase Grupo/Detalle y padres).
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param array $parsed
 * @return array
 */
function ppto_pdf_validar_contra_catalogo($mysqli, $emp_id, $parsed) {
    $items = ppto_pdf_items_esperados($parsed);
    $conflictos = array();
    $catalogo = array();

    if (empty($items)) {
        return array('conflictos' => array(), 'catalogo' => array());
    }

    $codigos = array_keys($items);
    $padre_cods = array();
    foreach ($items as $it) {
        if (!empty($it['padre_codigo'])) {
            $padre_cods[$it['padre_codigo']] = true;
        }
    }

    $buscar = array_unique(array_merge($codigos, array_keys($padre_cods)));
    $db_map = ppto_pdf_catalogo_por_codigos($mysqli, $emp_id, $buscar);

    foreach ($items as $cod => $it) {
        $esperada = $it['clase'];
        $existe = isset($db_map[$cod]);
        $actual = $existe ? $db_map[$cod]['clase'] : null;
        $ok = (!$existe || $actual === $esperada);

        $catalogo[$cod] = array(
            'existe' => $existe,
            'clase_actual' => $actual,
            'clase_esperada' => $esperada,
            'estado' => $ok ? ($existe ? 'existente' : 'nuevo') : 'conflicto',
        );

        if ($existe && $actual !== $esperada) {
            $conflictos[] = array(
                'codigo' => $cod,
                'descripcion' => $it['descripcion'],
                'clase_esperada' => $esperada,
                'clase_actual' => $actual,
                'tipo' => 'clase',
                'mensaje' => 'La partida ' . $cod . ' existe como ' . ppto_pdf_clase_etiqueta($actual)
                    . ' pero el archivo requiere ' . ppto_pdf_clase_etiqueta($esperada)
                    . '. Corrijala en Admin (Catalogo de partidas).',
            );
        }
    }

    $conflictos_cod = array();
    foreach ($conflictos as $c) {
        $conflictos_cod[$c['codigo'] . '|' . $c['tipo']] = true;
    }

    foreach ($items as $cod => $it) {
        if (empty($it['padre_codigo'])) {
            continue;
        }
        $pc = $it['padre_codigo'];
        $padre_clase = null;
        if (isset($items[$pc])) {
            $padre_clase = $items[$pc]['clase'];
        } elseif (isset($db_map[$pc])) {
            $padre_clase = $db_map[$pc]['clase'];
        }
        if ($padre_clase === null || $padre_clase === 'G') {
            continue;
        }

        $key = $pc . '|padre';
        if (isset($conflictos_cod[$key])) {
            continue;
        }

        $desc_padre = isset($db_map[$pc]) ? $db_map[$pc]['descripcion'] : (isset($items[$pc]) ? $items[$pc]['descripcion'] : '');
        $conflictos[] = array(
            'codigo' => $pc,
            'descripcion' => $desc_padre,
            'clase_esperada' => 'G',
            'clase_actual' => $padre_clase,
            'tipo' => 'padre',
            'mensaje' => 'La partida padre ' . $pc . ' debe ser Grupo para colgar ' . $cod
                . '. Actualmente es Detalle. Cambiela a Grupo en Admin.',
        );
        $conflictos_cod[$key] = true;

        if (isset($catalogo[$pc])) {
            $catalogo[$pc]['estado'] = 'conflicto';
        }
    }

    return array('conflictos' => $conflictos, 'catalogo' => $catalogo);
}

/**
 * Payload liviano para respuesta JSON (sin miles de lineas).
 *
 * @param array $parsed
 * @return array
 */
function ppto_pdf_payload_slim($parsed) {
    $detectada = isset($parsed['ton_base']) ? (float)$parsed['ton_base'] : 0;
    $dual = ppto_pdf_ton_dual_base($detectada);
    return array(
        'ton_base' => $dual['ingreso'],
        'ton_costo_mes' => $dual['costo'],
        'ton_ingreso_mes' => $dual['ingreso'],
        'ton_detectada' => round($detectada, 4),
        'meses_prorrateo_global' => isset($parsed['meses_prorrateo_global']) ? (int)$parsed['meses_prorrateo_global'] : 0,
        'partidas' => isset($parsed['partidas']) ? $parsed['partidas'] : array(),
        'rubros' => isset($parsed['rubros']) ? $parsed['rubros'] : array(),
        'warnings' => isset($parsed['warnings']) ? $parsed['warnings'] : array(),
    );
}

/**
 * Crea o obtiene una partida del catalogo.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param string $codigo
 * @param string $descripcion
 * @param array $opts
 * @return int
 */
function ppto_pdf_partida_upsert($mysqli, $emp_id, $codigo, $descripcion, $opts) {
    $emp_id = (int)$emp_id;
    $codigo_esc = $mysqli->real_escape_string(trim($codigo));
    $des_esc = $mysqli->real_escape_string(trim($descripcion));
    $tipo = $mysqli->real_escape_string(isset($opts['tipo']) ? $opts['tipo'] : 'G');
    $nat = $mysqli->real_escape_string(isset($opts['naturaleza']) ? $opts['naturaleza'] : 'OPE');
    $clase = $mysqli->real_escape_string(isset($opts['clase']) ? $opts['clase'] : 'G');
    $padre_id = (isset($opts['padre_id']) && (int)$opts['padre_id'] > 0) ? (int)$opts['padre_id'] : 'NULL';
    $nivel = (int)ppto_partida_nivel_desde_codigo($codigo);
    $usu_id = (int)$opts['usu_id'];

    $res = $mysqli->query("SELECT ppa_id FROM exa_ppto_partidas WHERE emp_id=$emp_id AND ppa_codigo_clasificacion='$codigo_esc' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $ppa_id = (int)$row['ppa_id'];
        if ($des_esc !== '') {
            $mysqli->query("UPDATE exa_ppto_partidas SET ppa_descripcion='$des_esc' WHERE ppa_id=$ppa_id AND emp_id=$emp_id LIMIT 1");
        }
        return $ppa_id;
    }

    $sql = "INSERT INTO exa_ppto_partidas
            (emp_id, ppa_codigo_clasificacion, ppa_descripcion, ppa_tipo, ppa_naturaleza, ppa_padre_id, ppa_nivel, ppa_clase, ppa_estado, ppa_fecha_registro, usu_id)
            VALUES ($emp_id, '$codigo_esc', '$des_esc', '$tipo', '$nat', $padre_id, $nivel, '$clase', 'A', NOW(), $usu_id)";
    if ($mysqli->query($sql)) {
        return (int)$mysqli->insert_id;
    }
    return 0;
}

/**
 * Importa partidas y rubros parseados al catalogo y al proyecto.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $usu_id
 * @param string $proy_id
 * @param int $ppe_id
 * @param array $parsed
 * @param float $ton_override
 * @return array
 */
function ppto_pdf_importar_presupuesto($mysqli, $emp_id, $usu_id, $proy_id, $ppe_id, $parsed, $ton_override = 0) {
    ppto_schema_ensure_partida_clase($mysqli);

    $validacion = ppto_pdf_validar_contra_catalogo($mysqli, $emp_id, $parsed);
    if (!empty($validacion['conflictos'])) {
        $msgs = array();
        foreach ($validacion['conflictos'] as $c) {
            if (!empty($c['mensaje'])) {
                $msgs[] = $c['mensaje'];
            }
        }
        return array(
            'ok' => false,
            'message' => 'No se puede importar: hay conflictos de clase Grupo/Detalle en el catalogo. '
                . implode(' ', array_slice($msgs, 0, 3)),
            'conflictos' => $validacion['conflictos'],
        );
    }

    $stats = array(
        'partidas_nuevas' => 0,
        'partidas_existentes' => 0,
        'rubros_nuevos' => 0,
        'rubros_actualizados' => 0,
        'rubros_duplicados_eliminados' => 0,
        'ton_base' => 0,
        'ton_costo_mes' => 0,
    );

    $ton_ingreso = ppto_version_ton_base_sanitize($ton_override);
    $ton_costo = isset($parsed['ton_costo_mes']) ? (float)$parsed['ton_costo_mes'] : 0;
    if ($ton_costo <= 0) {
        $dual = ppto_pdf_ton_dual_base(isset($parsed['ton_base']) ? (float)$parsed['ton_base'] : 0);
        $ton_costo = $dual['costo'];
    }
    if ($ton_ingreso <= 0) {
        $dual = ppto_pdf_ton_dual_base(isset($parsed['ton_base']) ? (float)$parsed['ton_base'] : 0);
        $ton_ingreso = $dual['ingreso'];
    }
    if ($ton_ingreso <= 0) {
        return array('ok' => false, 'message' => 'No se detecto tonelada de ingresos. Ingrese Ton ingresos (mes) manualmente (ej. 105000).');
    }
    $stats['ton_base'] = $ton_ingreso;
    $stats['ton_costo_mes'] = round($ton_costo, 4);

    $cod_to_id = array();
    $partidas = isset($parsed['partidas']) ? $parsed['partidas'] : array();
    usort($partidas, 'ppto_pdf_cmp_codigo');

    foreach ($partidas as $p) {
        $cod = $p['codigo'];
        $res_exist = $mysqli->query("SELECT ppa_id FROM exa_ppto_partidas WHERE emp_id=" . (int)$emp_id . " AND ppa_codigo_clasificacion='" . $mysqli->real_escape_string($cod) . "' LIMIT 1");
        $exists = ($res_exist && $res_exist->num_rows > 0);

        $padre_id = null;
        if (!empty($p['padre_codigo']) && isset($cod_to_id[$p['padre_codigo']])) {
            $padre_id = $cod_to_id[$p['padre_codigo']];
        }

        $ppa_id = ppto_pdf_partida_upsert($mysqli, $emp_id, $cod, $p['descripcion'], array(
            'tipo' => 'G',
            'naturaleza' => 'OPE',
            'clase' => $p['clase'],
            'padre_id' => $padre_id,
            'usu_id' => $usu_id,
        ));

        if ($ppa_id > 0) {
            $cod_to_id[$cod] = $ppa_id;
            if ($exists) {
                $stats['partidas_existentes']++;
            } else {
                $stats['partidas_nuevas']++;
            }
            if (ppto_pdf_normalizar_clase($p['clase']) === 'G' && isset($p['meses_prorrateo'])) {
                $meses_g = (int)$p['meses_prorrateo'];
                if ($meses_g > 0) {
                    ppto_schema_ensure_partida_meses_prorrateo($mysqli);
                    $mysqli->query("UPDATE exa_ppto_partidas SET ppa_meses_prorrateo = $meses_g WHERE ppa_id = $ppa_id AND emp_id = " . (int)$emp_id);
                }
            }
        }
    }

    $proy_esc = $mysqli->real_escape_string(trim($proy_id));
    $ppe_id = (int)$ppe_id;

    $sql_ton = "INSERT INTO exa_ppto_proyecto_version
            (proy_id, emp_id, ppe_id, pv_toneladas_base_mes, pv_toneladas_costo_mes, pv_fecha_registro, usu_id)
            VALUES ('$proy_esc', " . (int)$emp_id . ", $ppe_id, $ton_ingreso, $ton_costo, NOW(), " . (int)$usu_id . ")
            ON DUPLICATE KEY UPDATE pv_toneladas_base_mes=$ton_ingreso, pv_toneladas_costo_mes=$ton_costo, pv_fecha_registro=NOW(), usu_id=" . (int)$usu_id;
    $mysqli->query($sql_ton);

    $rubros = isset($parsed['rubros']) ? $parsed['rubros'] : array();
    foreach ($rubros as $r) {
        $cod = $r['codigo'];
        if (!isset($cod_to_id[$cod])) {
            $padre_cod = ppto_partida_prefijo_padre_codigo($cod);
            $padre_id = ($padre_cod && isset($cod_to_id[$padre_cod])) ? $cod_to_id[$padre_cod] : null;
            $ppa_id = ppto_pdf_partida_upsert($mysqli, $emp_id, $cod, $r['descripcion'], array(
                'tipo' => 'G',
                'naturaleza' => 'OPE',
                'clase' => 'D',
                'padre_id' => $padre_id,
                'usu_id' => $usu_id,
            ));
            if ($ppa_id > 0) {
                $cod_to_id[$cod] = $ppa_id;
                $stats['partidas_nuevas']++;
            }
        }

        $ppa_id = (int)$cod_to_id[$cod];
        if ($ppa_id <= 0) {
            continue;
        }

        $factor = (float)$r['factor_anual'];
        $rubro = $mysqli->real_escape_string($r['descripcion']);
        $presup_pdf = isset($r['presup_anual_pdf']) ? (float)$r['presup_anual_pdf'] : 0;
        $monto_recalc = isset($r['monto_recalc']) ? (float)$r['monto_recalc'] : 0;
        $tn_dia = isset($r['tn_dia']) ? (float)$r['tn_dia'] : 0;
        $dias = ppto_pdf_dias_laborables_default();
        $ton_rubro = ppto_normalizar_ton_mes_rubro($ton_costo, $tn_dia);
        $meses_prorr = isset($r['meses']) ? (int)$r['meses'] : 12;
        if ($meses_prorr < 1) {
            $meses_prorr = 12;
        }
        if (isset($r['presupuesto_anual']) && (float)$r['presupuesto_anual'] > 0) {
            $anual = round((float)$r['presupuesto_anual'], 2);
        } elseif ($tn_dia > 0 && $dias > 0 && $factor > 0) {
            $monto_recalc = round($tn_dia * $dias * $factor, 2);
            $anual = round($monto_recalc / ($meses_prorr / 12), 2);
        } elseif ($monto_recalc > 0) {
            $anual = round($monto_recalc / ($meses_prorr / 12), 2);
        } elseif ($presup_pdf > 0) {
            $anual = round($presup_pdf / ($meses_prorr / 12), 2);
            $factor = round($presup_pdf / $ton_rubro, 6);
        } else {
            $anual = round($ton_rubro * $factor, 2);
        }

        $pdp_id = ppto_proy_rubro_id_por_partida($mysqli, $ppe_id, $ppa_id, $proy_id, $emp_id);
        if ($pdp_id > 0) {
            $sql = "UPDATE exa_ppto_proyecto_detalles
                SET pdp_rubro='$rubro', pdp_toneladas_base=$ton_rubro, pdp_factor_anual_tonelada=$factor, pdp_presupuesto_anual=$anual
                WHERE pdp_id=$pdp_id AND proy_id='$proy_esc' AND emp_id=" . (int)$emp_id . " AND ppe_id=$ppe_id";
            if ($mysqli->query($sql)) {
                $stats['rubros_actualizados']++;
                $stats['rubros_duplicados_eliminados'] += ppto_proy_rubro_purgar_duplicados_partida(
                    $mysqli, $ppe_id, $ppa_id, $proy_id, $emp_id, $pdp_id
                );
                ppto_pdf_distribuir_meses($mysqli, $pdp_id, $anual);
            }
        } else {
            $sql = "INSERT INTO exa_ppto_proyecto_detalles
                (ppe_id, ppa_id, proy_id, emp_id, pdp_rubro, pdp_toneladas_base, pdp_factor_anual_tonelada, pdp_presupuesto_anual, pdp_fecha_registro, usu_id)
                VALUES ($ppe_id, $ppa_id, '$proy_esc', " . (int)$emp_id . ", '$rubro', $ton_rubro, $factor, $anual, NOW(), " . (int)$usu_id . ")";
            if ($mysqli->query($sql)) {
                $pdp_id = (int)$mysqli->insert_id;
                if ($pdp_id <= 0) {
                    $pdp_id = ppto_proy_rubro_id_por_partida($mysqli, $ppe_id, $ppa_id, $proy_id, $emp_id);
                }
                if ($pdp_id > 0) {
                    $stats['rubros_nuevos']++;
                    ppto_pdf_distribuir_meses($mysqli, $pdp_id, $anual);
                }
            }
        }
    }

    $msg = 'Importacion completada: ' . $stats['partidas_nuevas'] . ' partida(s) nueva(s), '
        . $stats['rubros_nuevos'] . ' rubro(s) nuevo(s), ' . $stats['rubros_actualizados'] . ' rubro(s) actualizado(s).';
    if ($stats['rubros_duplicados_eliminados'] > 0) {
        $msg .= ' Duplicados eliminados: ' . $stats['rubros_duplicados_eliminados'] . '.';
    }

    return array('ok' => true, 'message' => $msg, 'stats' => $stats);
}

/**
 * Distribuye presupuesto anual en 12 meses para un rubro de proyecto.
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @param float $anual
 */
function ppto_pdf_distribuir_meses($mysqli, $pdp_id, $anual) {
    $pdp_id = (int)$pdp_id;
    $mensual = round((float)$anual / 12, 2);
    for ($mes = 1; $mes <= 12; $mes++) {
        $mysqli->query("INSERT INTO exa_ppto_proyecto_detalles_mes
            (pdp_id, pdm_mes, pdm_dias_laborables, pdm_factor_mensual, pdm_presupuesto_mensual, pdm_ejecutado, pdm_comprometido, pdm_disponible)
            VALUES ($pdp_id, $mes, 22, 0.0833, $mensual, 0, 0, $mensual)
            ON DUPLICATE KEY UPDATE pdm_presupuesto_mensual=$mensual, pdm_disponible=GREATEST(0, $mensual - pdm_ejecutado - pdm_comprometido)");
    }
}
