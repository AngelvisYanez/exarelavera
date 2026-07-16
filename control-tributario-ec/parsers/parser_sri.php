<?php
/**
 * Parser PDF declaraciones SRI — Form 104 / comprobante simple / IR
 */
require_once __DIR__ . '/pdf_text.php';

class ParserSRI
{
    private $texto = '';
    private $archivo = '';

    public function parse($rutaPdf) {
        $this->archivo = basename($rutaPdf);
        $this->texto = mb_strtoupper(cte_pdf_extraer_texto($rutaPdf), 'UTF-8');

        if (strlen($this->texto) < 30) {
            return array('error' => 'No se pudo leer texto del PDF. Verifique el archivo o instale pdftotext.');
        }

        if ($this->esComprobanteSimple()) {
            return $this->parseComprobanteSimple();
        }

        return $this->parseDeclaracionCompleta();
    }

    private function esComprobanteSimple() {
        return strpos($this->texto, 'COMPROBANTE') !== false
            || strpos($this->texto, 'SIN VALOR A PAGAR') !== false
            || (strpos($this->texto, 'CUMPLIDA') !== false && strpos($this->texto, '401') === false);
    }

    private function parseComprobanteSimple() {
        $data = array(
            'tipo_doc' => 'comprobante_simple',
            'archivo' => $this->archivo,
            'numero_serie' => $this->match('/SERIE[:\s]*([A-Z0-9\-]+)/'),
            'ruc' => $this->match('/RUC[:\s]*(\d{13})/'),
            'razon_social' => $this->matchRazon(),
            'periodo_texto' => $this->matchPeriodo(),
            'tipo_impuesto' => $this->match('/(?:TIPO\s+IMPUESTO|IMPUESTO)[:\s]*(\d{4})/') ?: $this->detectarImpuesto(),
            'tipo_declaracion' => $this->match('/(ORIGINAL|SUSTITUTIVA)/'),
            'estado' => $this->detectarEstado(),
            'fecha_recaudacion' => $this->matchFecha(),
            'codigo_verificador' => $this->match('/(?:CODIGO|CÓDIGO)\s*VERIFICADOR[:\s]*([A-Z0-9]+)/i'),
        );

        $data['mes'] = cte_periodo_a_mes((isset($data['periodo_texto']) ? $data['periodo_texto'] : ''), cte_anio_contribuyente());
        $data['formulario'] = strpos((isset($data['tipo_impuesto']) ? $data['tipo_impuesto'] : ''), '1011') !== false ? '103' : '104';
        if (strpos($this->texto, 'FORMULARIO 103') !== false || strpos($this->texto, '103') !== false) {
            $data['formulario'] = '103';
        }

        return $data;
    }

    private function parseDeclaracionCompleta() {
        $campos = array('401', '403', '405', '409', '411', '413', '415', '419', '421', '422', '423', '424', '425', '426', '427', '428', '429', '431', '434', '441', '444', '454', '510', '529', '564', '601', '609', '617', '999', '483', '485', '606');
        $valores = array();
        foreach ($campos as $c) {
            $valores[$c] = $this->extraerCampo($c);
        }

        $data = array_merge($valores, array(
            'tipo_doc' => 'declaracion_completa',
            'archivo' => $this->archivo,
            'formulario' => '104',
            'numero_serie' => $this->match('/SERIE[:\s]*([A-Z0-9\-]+)/'),
            'ruc' => $this->match('/RUC[:\s]*(\d{13})/'),
            'razon_social' => $this->matchRazon(),
            'periodo_texto' => $this->matchPeriodo(),
            'tipo_declaracion' => $this->match('/(ORIGINAL|SUSTITUTIVA)/'),
            'estado' => $this->detectarEstado(),
            'fecha_recaudacion' => $this->matchFecha(),
            'codigo_verificador' => $this->match('/(?:COD|CÓDIGO)\s*VERIF[:\s]*([A-Z0-9]+)/i'),
            'tipo_impuesto' => '2011',
        ));

        if ($valores['413'] > 0 && $valores['403'] == 0) {
            $data['403'] = $valores['413'];
        }

        $data['mes'] = cte_periodo_a_mes((isset($data['periodo_texto']) ? $data['periodo_texto'] : ''), cte_anio_contribuyente());
        $data['999'] = $valores['999'];
        $data['sin_valor_pagar'] = ($valores['999'] == 0);

        return $data;
    }

    private function extraerCampo($numero) {
        $patterns = array(
            '/\b' . $numero . '\b[\s\:\-]*([\d\.,]+)/',
            '/CASILLERO\s*' . $numero . '[\s\:\-]*([\d\.,]+)/',
            '/CAMPO\s*' . $numero . '[\s\:\-]*([\d\.,]+)/',
            '/' . $numero . '\s+([\d]{1,3}(?:[\.,]\d{3})*[\.,]\d{2})/',
        );
        foreach ($patterns as $p) {
            if (preg_match($p, $this->texto, $m)) {
                return cte_num($m[1]);
            }
        }
        return 0.0;
    }

    private function match($regex) {
        if (preg_match($regex, $this->texto, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function matchRazon() {
        if (preg_match('/RAZ[ÓO]N\s+SOCIAL[:\s]+([A-Z0-9\s\.,&\-]+?)(?:\s+RUC|\s+PERIODO)/', $this->texto, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function matchPeriodo() {
        foreach ($GLOBALS['CTE_MESES'] as $nombre) {
            if (preg_match('/' . $nombre . '\s+(\d{4})/', $this->texto, $m)) {
                return $nombre . ' ' . $m[1];
            }
        }
        if (preg_match('/PERIODO\s+FISCAL[:\s]+([A-Z0-9\s]+)/', $this->texto, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function matchFecha() {
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $this->texto, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $this->texto, $m)) {
            return $m[0];
        }
        return null;
    }

    private function detectarImpuesto() {
        if (strpos($this->texto, '1011') !== false || strpos($this->texto, 'RENTA') !== false) {
            return '1011';
        }
        return '2011';
    }

    private function detectarEstado() {
        if (strpos($this->texto, 'SIN VALOR A PAGAR') !== false) {
            return 'SIN VALOR A PAGAR';
        }
        if (strpos($this->texto, 'CUMPLIDA') !== false) {
            return 'CUMPLIDA';
        }
        return 'REGISTRADA';
    }
}

function cte_parsear_pdfs_sri(array $files) {
    $parser = new ParserSRI();
    $resultados = array();

    foreach ((isset($files['tmp_name']) ? $files['tmp_name'] : array()) as $i => $tmp) {
        if (empty($tmp) || ((isset($files['error'][$i]) ? $files['error'][$i] : 1)) !== UPLOAD_ERR_OK) {
            continue;
        }
        $nombre = (isset($files['name'][$i]) ? $files['name'][$i] : 'doc.pdf');
        $dest = CTE_UPLOAD . '/' . uniqid('sri_') . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $nombre);
        if (!move_uploaded_file($tmp, $dest)) {
            continue;
        }
        $parsed = $parser->parse($dest);
        $parsed['ruta_tmp'] = $dest;
        $resultados[] = $parsed;
    }

    return $resultados;
}
