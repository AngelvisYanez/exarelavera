<?php
/**
 * Extracción básica de texto desde PDF (sin dependencias externas)
 */

function cte_pdf_extraer_texto($ruta) {
    if (!is_readable($ruta)) {
        return '';
    }

    // Intentar Python con PyPDF2 si está disponible
    $pythonScript = __DIR__ . '/extract_pdf.py';
    if (!file_exists($pythonScript)) {
        $pyCode = "import sys\ntry:\n    from PyPDF2 import PdfReader\n    reader = PdfReader(sys.argv[1])\n    text = ''\n    for page in reader.pages:\n        t = page.extract_text()\n        if t: text += t + ' '\n    print(text)\nexcept Exception as e:\n    pass\n";
        @file_put_contents($pythonScript, $pyCode);
    }
    $outPy = @shell_exec('python "' . $pythonScript . '" "' . $ruta . '" 2>nul');
    if ($outPy && strlen(trim($outPy)) > 30) {
        $texto = preg_replace('/\s+/', ' ', $outPy);
        return trim($texto);
    }

    // Intentar pdftotext si está en PATH (Poppler en algunos XAMPP)
    $pdftotext = null;
    foreach (array('pdftotext', 'C:\\xampp\\poppler\\bin\\pdftotext.exe') as $bin) {
        $out = @shell_exec('"' . $bin . '" -layout "' . str_replace('"', '', $ruta) . '" - 2>nul');
        if ($out && strlen(trim($out)) > 50) {
            $texto = preg_replace('/\s+/', ' ', $out);
            return trim($texto);
        }
    }

    $raw = @file_get_contents($ruta);
    if ($raw === false) {
        return '';
    }

    // Limit size to avoid stack overflow in older PHP versions when running regex on binary data
    if (strlen($raw) > 2000000) {
        $raw = substr($raw, 0, 2000000);
    }

    $texto = '';

    // Decompress FlateDecode streams safely
    $offset = 0;
    while (($start = strpos($raw, "stream\n", $offset)) !== false || ($start = strpos($raw, "stream\r\n", $offset)) !== false) {
        $start += ($raw[$start+6] == "\r" ? 8 : 7);
        $end = strpos($raw, "endstream", $start);
        if ($end !== false) {
            $stream = substr($raw, $start, $end - $start);
            $uncompressed = @gzuncompress(trim($stream));
            if ($uncompressed !== false) {
                if (preg_match_all('/\(([^\(\)]{1,500})\)\s*(?:Tj|TJ)/', $uncompressed, $m)) {
                    foreach ($m[1] as $inner) {
                        $texto .= cte_pdf_unescape($inner) . ' ';
                    }
                }
                if (preg_match_all('/BT([\s\S]{1,2000}?)ET/', $uncompressed, $blocks)) {
                    foreach ($blocks[1] as $block) {
                        if (preg_match_all('/\(([^\(\)]{1,500})\)/', $block, $parts)) {
                            foreach ($parts[1] as $p) {
                                $texto .= cte_pdf_unescape(trim($p)) . ' ';
                            }
                        }
                    }
                }
            }
            $offset = $end + 9;
        } else {
            break;
        }
    }

    // FlateDecode streams — texto entre paréntesis en operadores Tj/TJ
    // Safe version without catastrophic backtracking: match anything except parentesis
    if (preg_match_all('/\(([^\(\)]{1,500})\)\s*(?:Tj|TJ)/', $raw, $m)) {
        foreach ($m[1] as $inner) {
            $texto .= cte_pdf_unescape($inner) . ' ';
        }
    }

    // BT ... ET blocks
    // Safe version: limit the length of the block to 2000 chars to prevent stack overflow
    if (strlen($texto) < 80 && preg_match_all('/BT([\s\S]{1,2000}?)ET/', $raw, $blocks)) {
        foreach ($blocks[1] as $block) {
            if (preg_match_all('/\(([^\(\)]{1,500})\)/', $block, $parts)) {
                foreach ($parts[1] as $p) {
                    $texto .= cte_pdf_unescape(trim($p)) . ' ';
                }
            }
        }
    }

    // Texto legible suelto
    if (strlen($texto) < 80) {
        if (preg_match_all('/[\x20-\x7E\xC0-\xFF]{4,200}/', $raw, $ascii)) {
            $texto = implode(' ', $ascii[0]);
        }
    }

    $texto = preg_replace('/\s+/', ' ', $texto);
    return trim($texto);
}

function cte_pdf_unescape($s) {
    $map = array('\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\(' => '(', '\\)' => ')', '\\\\' => '\\');
    foreach ($map as $k => $v) {
        $s = str_replace($k, $v, $s);
    }
    return $s;
}
