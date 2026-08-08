<?php
/**
 * Certificado de asistencia ECOPARK / Relavera.
 * Una sola generacion de PDF para visualizar y para enviar (WhatsApp/Email).
 */

if (!function_exists('man_cert_asistencia_txt')) {
    function man_cert_asistencia_txt($texto)
    {
        $texto = (string) $texto;
        if ($texto === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
            if ($conv !== false && $conv !== '') {
                return $conv;
            }
        }
        if (function_exists('mb_convert_encoding')) {
            $conv = @mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
            if ($conv !== false && $conv !== '') {
                return $conv;
            }
        }
        $decoded = @utf8_decode($texto);
        return ($decoded !== false && $decoded !== '') ? $decoded : $texto;
    }
}

if (!function_exists('man_cert_asistencia_formatear_fecha_evento')) {
    function man_cert_asistencia_formatear_fecha_evento($fechaYmd)
    {
        if (empty($fechaYmd)) {
            return '';
        }
        $tsEv = strtotime($fechaYmd);
        if (!$tsEv) {
            return (string) $fechaYmd;
        }
        $diasSemana = array(
            'domingo', 'lunes', 'martes', "mi\xC3\xA9rcoles", 'jueves', 'viernes', "s\xC3\xA1bado",
        );
        $mesesNom = array(
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
        );
        return $diasSemana[(int) date('w', $tsEv)]
            . ' ' . date('d', $tsEv)
            . ' de ' . $mesesNom[(int) date('m', $tsEv) - 1]
            . ' de ' . date('Y', $tsEv);
    }
}

if (!function_exists('man_cert_asistencia_normalizar_horas')) {
    function man_cert_asistencia_normalizar_horas($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '6';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(:\d{2})?$/', $valor, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($min > 0) {
                return sprintf('%d:%02d', $h, $min);
            }
            return $h > 0 ? (string) $h : '6';
        }
        if (is_numeric(str_replace(',', '.', $valor))) {
            $v = floatval(str_replace(',', '.', $valor));
            $h = floor($v);
            $min = round(($v - $h) * 60);
            if ($min > 0) {
                return sprintf('%d:%02d', (int)$h, (int)$min);
            }
            return (string) (int)$h;
        }
        return $valor;
    }
}

if (!function_exists('man_cert_resolver_imagen')) {
    function man_cert_resolver_imagen($nombreArchivo, $empCod = '620')
    {
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
        $candidatos = array(
            __DIR__ . '/../../imagenes/' . $empCod . '/' . $nombreArchivo,
            __DIR__ . '/../imagenes/' . $empCod . '/' . $nombreArchivo,
            $docRoot ? $docRoot . '/imagenes/' . $empCod . '/' . $nombreArchivo : '',
        );
        foreach ($candidatos as $c) {
            if (!empty($c) && file_exists($c) && is_file($c)) {
                return $c;
            }
        }
        return false;
    }
}

if (!function_exists('man_cert_asistencia_resolver_evento')) {
    function man_cert_asistencia_resolver_evento($obBD, $obConexion, $manEve = null)
    {
        $out = array(
            'nombre' => "Capacitaci\xC3\xB3n en Seguridad Industrial y Ambiental",
            'horas' => '6',
            'fecha_texto' => "s\xC3\xA1bado 08 de agosto de 2026",
            'fecha_raw' => '',
        );
        try {
            $manEve = $manEve !== null ? trim((string) $manEve) : '';
            if ($manEve !== '') {
                $sql = "SELECT Man_ENom, COALESCE(Man_EHor, Man_Ehor, 6) AS Man_EHor, Man_EFei
                        FROM manifiesto_evento
                        WHERE Man_Eve = '" . addslashes($manEve) . "'
                        LIMIT 1";
            } else {
                $sql = "SELECT Man_ENom, COALESCE(Man_EHor, Man_Ehor, 6) AS Man_EHor, Man_EFei
                        FROM manifiesto_evento
                        WHERE Man_Vig = 'S' AND IFNULL(Man_EEst, 'A') = 'A'
                        LIMIT 1";
            }
            $resEv = $obBD->consulta($sql, $obConexion->conexion);
            if ($resEv && ($rowEv = $obBD->fetch_assoc($resEv))) {
                if (method_exists($obBD, 'utf8_change_param')) {
                    $obBD->utf8_change_param($rowEv);
                }
                if (!empty($rowEv['Man_ENom'])) {
                    $out['nombre'] = trim((string) $rowEv['Man_ENom']);
                }
                if (isset($rowEv['Man_EHor']) && $rowEv['Man_EHor'] !== '' && $rowEv['Man_EHor'] !== null) {
                    $out['horas'] = man_cert_asistencia_normalizar_horas($rowEv['Man_EHor']);
                }
                if (!empty($rowEv['Man_EFei'])) {
                    $out['fecha_raw'] = trim((string) $rowEv['Man_EFei']);
                    $out['fecha_texto'] = man_cert_asistencia_formatear_fecha_evento($out['fecha_raw']);
                }
            }
        } catch (Exception $e) {
            // defaults
        }
        return $out;
    }
}

if (!function_exists('man_cert_asistencia_armar_params')) {
    /**
     * Arma los parametros del certificado (misma fuente para ver y enviar).
     */
    function man_cert_asistencia_armar_params($prsNom, $prsApe, $prsCed, $evData)
    {
        return array(
            'Prs_Nom' => trim((string) $prsNom),
            'Prs_Ape' => trim((string) $prsApe),
            'Prs_Ced' => trim((string) $prsCed),
            'nombre_evento' => isset($evData['nombre']) ? $evData['nombre'] : '',
            'horas_evento' => isset($evData['horas']) ? $evData['horas'] : '6',
            'fecha_evento_texto' => isset($evData['fecha_texto']) ? $evData['fecha_texto'] : '',
        );
    }
}

if (!function_exists('man_cert_asistencia_generar_pdf')) {
    /**
     * Genera el PDF unico del certificado (vista previa = envio).
     * Diseno alineado a visualizarCertificadoPDF (HTML ECOPARK).
     *
     * @param array $params
     * @return string|false Ruta absoluta del PDF
     */
    function man_cert_asistencia_generar_pdf(array $params)
    {
        if (!class_exists('CertAsistenciaPDF', false)) {
            if (!class_exists('TCPDF', false)) {
                $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
                $tcpdfPaths = array(
                    __DIR__ . '/../../Librerias/TCPDF/tcpdf.php',
                    __DIR__ . '/../Librerias/TCPDF/tcpdf.php',
                    $docRoot ? $docRoot . '/Librerias/TCPDF/tcpdf.php' : '',
                    $docRoot ? $docRoot . '/relavera/Librerias/TCPDF/tcpdf.php' : '',
                );
                foreach ($tcpdfPaths as $tPath) {
                    if (!empty($tPath) && file_exists($tPath) && is_file($tPath)) {
                        require_once $tPath;
                        break;
                    }
                }
            }
            if (class_exists('TCPDF', false)) {
                class CertAsistenciaPDF extends TCPDF
                {
                    public function Header() {}
                    public function Footer() {}
                }
            } else {
                return false;
            }
        }

        $prsNom = isset($params['Prs_Nom']) ? trim((string) $params['Prs_Nom']) : '';
        $prsApe = isset($params['Prs_Ape']) ? trim((string) $params['Prs_Ape']) : '';
        $prsCed = isset($params['Prs_Ced']) ? trim((string) $params['Prs_Ced']) : '';
        $nombrePersona = trim($prsNom . ' ' . $prsApe);
        if ($nombrePersona === '' || $nombrePersona === 'Nombres Apellidos') {
            $nombrePersona = "Juan Carlos P\xC3\xA9rez";
        }

        $nombreEvento = !empty($params['nombre_evento'])
            ? trim((string) $params['nombre_evento'])
            : "Capacitaci\xC3\xB3n en Seguridad Industrial y Ambiental";
        $horasEvento = !empty($params['horas_evento'])
            ? man_cert_asistencia_normalizar_horas($params['horas_evento'])
            : '6';
        $fechaEventoTexto = !empty($params['fecha_evento_texto'])
            ? trim((string) $params['fecha_evento_texto'])
            : "s\xC3\xA1bado 08 de agosto de 2026";

        $mesesAct = array(
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
        );
        $fechaEmisionStr = 'Portovelo, El Oro, ' . date('d') . ' de '
            . $mesesAct[(int) date('m') - 1] . ' de ' . date('Y') . '.';

        $nombreMayus = function_exists('mb_strtoupper')
            ? mb_strtoupper($nombrePersona, 'UTF-8')
            : strtoupper($nombrePersona);

        $pdf = new CertAsistenciaPDF('L', 'mm', 'A4', false, 'ISO-8859-1', false);
        $pdf->SetCreator('Relavera');
        $pdf->SetAuthor('ECOPARKMINING S.A.');
        $pdf->SetTitle(man_cert_asistencia_txt('Certificado de Asistencia - ' . $nombrePersona));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(22, 18, 22);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();

        $pageW = $pdf->getPageWidth();
        $pageH = $pdf->getPageHeight();

        // === MARCOS (igual idea del HTML: borde navy + outline oro) ===
        $pdf->SetDrawColor(11, 37, 69); // #0b2545
        $pdf->SetLineWidth(3.0);
        $pdf->Rect(7, 7, $pageW - 14, $pageH - 14);
        $pdf->SetDrawColor(197, 160, 89); // #c5a059
        $pdf->SetLineWidth(0.9);
        $pdf->Rect(10, 10, $pageW - 20, $pageH - 20);

        // Ornamentos esquinas (como .corner-ornament)
        $pdf->SetDrawColor(197, 160, 89);
        $pdf->SetLineWidth(0.6);
        $c = 14;
        $L = 12;
        // top-left
        $pdf->Line($c, $c, $c + $L, $c);
        $pdf->Line($c, $c, $c, $c + $L);
        // top-right
        $pdf->Line($pageW - $c, $c, $pageW - $c - $L, $c);
        $pdf->Line($pageW - $c, $c, $pageW - $c, $c + $L);
        // bottom-left
        $pdf->Line($c, $pageH - $c, $c + $L, $pageH - $c);
        $pdf->Line($c, $pageH - $c, $c, $pageH - $c - $L);
        // bottom-right
        $pdf->Line($pageW - $c, $pageH - $c, $pageW - $c - $L, $pageH - $c);
        $pdf->Line($pageW - $c, $pageH - $c, $pageW - $c, $pageH - $c - $L);

        // Marca de agua tenue en el centro con SetAlpha (no tapa texto)
        $empCod = isset($params['Emp_Cod']) ? (string)$params['Emp_Cod'] : '620';
        $rutaMarca = man_cert_resolver_imagen('marca_agua.png', $empCod);
        if (!$rutaMarca) {
            $rutaMarca = man_cert_resolver_imagen('relavera.png', $empCod);
        }
        if (!$rutaMarca) {
            $rutaMarca = man_cert_resolver_imagen('logo-completo.png', $empCod);
        }
        if ($rutaMarca) {
            $pdf->SetAlpha(0.12);
            $pdf->Image($rutaMarca, ($pageW / 2) - 45, ($pageH / 2) - 45, 90, 90, '', '', '', false, 300, '', false, false, 0);
            $pdf->SetAlpha(1.0);
        }

        // === CONTENIDO (mismo orden/texto que el HTML) ===
        $pdf->SetY(26);
        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('times', 'B', 22);
        $pdf->Cell(0, 9, man_cert_asistencia_txt('ECOPARKMINING S.A.'), 0, 1, 'C');

        $pdf->SetTextColor(30, 58, 138);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->MultiCell(
            0,
            5,
            man_cert_asistencia_txt('PROYECTO AMBIENTAL ASOCIATIVO RELAVERA COMUNITARIA "EL TABLON"'),
            0,
            'C',
            false,
            1
        );

        // Divisor verde
        $pdf->Ln(2);
        $pdf->SetDrawColor(134, 239, 172);
        $pdf->SetLineWidth(0.4);
        $midY = $pdf->GetY() + 2;
        $pdf->Line(55, $midY, ($pageW / 2) - 8, $midY);
        $pdf->Line(($pageW / 2) + 8, $midY, $pageW - 55, $midY);
        $pdf->SetY($midY + 3);

        $pdf->SetTextColor(30, 58, 138);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, man_cert_asistencia_txt('OTORGA EL PRESENTE'), 0, 1, 'C');

        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('times', 'B', 32);
        $pdf->Cell(0, 12, man_cert_asistencia_txt('CERTIFICADO'), 0, 1, 'C');

        $pdf->SetTextColor(22, 163, 74);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 7, man_cert_asistencia_txt('DE ASISTENCIA'), 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, man_cert_asistencia_txt('. . .'), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 6, man_cert_asistencia_txt('A:'), 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 9, man_cert_asistencia_txt($nombreMayus), 0, 1, 'C');

        $lineW = 130;
        $pdf->SetDrawColor(11, 37, 69);
        $pdf->SetLineWidth(0.6);
        $yLine = $pdf->GetY();
        $pdf->Line(($pageW - $lineW) / 2, $yLine, ($pageW + $lineW) / 2, $yLine);

        $pdf->Ln(2);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, man_cert_asistencia_txt('C.I.: ' . $prsCed), 0, 1, 'C');

        $pdf->Ln(5);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetFont('helvetica', '', 11);

        // Parrafo EXACTO del HTML visualizarCertificadoPDF
        $parrafo =
            'Que el Sr. ' . $nombrePersona
            . " asisti\xC3\xB3 a la capacitaci\xC3\xB3n de Proyecto Ambiental Asociativo Relavera Comunitaria \"El Tabl\xC3\xB3n\""
            . " el d\xC3\xADa " . $fechaEventoTexto
            . " con una duraci\xC3\xB3n de " . $horasEvento . ' horas'
            . ' con el tema "' . $nombreEvento . '".';

        $pdf->MultiCell(0, 6, man_cert_asistencia_txt($parrafo), 0, 'C', false, 1);

        $pdf->Ln(8);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 5, man_cert_asistencia_txt($fechaEmisionStr), 0, 1, 'C');

        // Firmas
        $yFirmas = $pageH - 40;
        $pdf->SetDrawColor(11, 37, 69);
        $pdf->SetLineWidth(0.6);
        $x1 = 55;
        $x2 = $pageW - 125;

        // Firma 1: Gerencia General (firma1.png / firma1.jpg)
        $rutaFirma1 = man_cert_resolver_imagen('firma1.png', $empCod);
        if (!$rutaFirma1) {
            $rutaFirma1 = man_cert_resolver_imagen('firma1.jpg', $empCod);
        }
        if ($rutaFirma1) {
            $pdf->Image($rutaFirma1, $x1 + 12, $yFirmas - 22, 45, 22, '', '', '', false, 300, '', false, false, 0);
        }

        // Firma 2: Área de Capacitación (firma2.png / firma2.jpg)
        $rutaFirma2 = man_cert_resolver_imagen('firma2.png', $empCod);
        if (!$rutaFirma2) {
            $rutaFirma2 = man_cert_resolver_imagen('firma2.jpg', $empCod);
        }
        if ($rutaFirma2) {
            $pdf->Image($rutaFirma2, $x2 + 12, $yFirmas - 22, 45, 22, '', '', '', false, 300, '', false, false, 0);
        }

        // Líneas de firma
        $pdf->Line($x1, $yFirmas, $x1 + 70, $yFirmas);
        $pdf->Line($x2, $yFirmas, $x2 + 70, $yFirmas);

        $pdf->SetY($yFirmas + 2);
        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetX($x1);
        $pdf->Cell(70, 5, man_cert_asistencia_txt('Gerencia General'), 0, 0, 'C');
        $pdf->SetX($x2);
        $pdf->Cell(70, 5, man_cert_asistencia_txt("Area de Capacitaci\xC3\xB3n"), 0, 1, 'C');

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetX($x1);
        $pdf->Cell(70, 4, 'ECOPARKMINING S.A.', 0, 0, 'C');
        $pdf->SetX($x2);
        $pdf->Cell(70, 4, 'ECOPARKMINING S.A.', 0, 1, 'C');

        // Sello (sello.png / sello.jpg) en esquina inferior izquierda
        $rutaSello = man_cert_resolver_imagen('sello.png', $empCod);
        if (!$rutaSello) {
            $rutaSello = man_cert_resolver_imagen('sello.jpg', $empCod);
        }
        if ($rutaSello) {
            $pdf->Image($rutaSello, 45, $pageH - 54, 28, 28, '', '', '', false, 300, '', false, false, 0);
        }

        $safeCed = preg_replace('/[^a-zA-Z0-9_-]/', '', $prsCed);
        if ($safeCed === '') {
            $safeCed = 'cert';
        }
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'cert_asistencia_' . $safeCed . '_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.pdf';

        $pdf->Output($filePath, 'F');

        if (!is_file($filePath) || filesize($filePath) < 800) {
            return false;
        }
        return $filePath;
    }
}
