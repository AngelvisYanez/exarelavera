<?php
/**
 * Certificado de asistencia ECOPARK / Relavera.
 * Una sola generacion de PDF para visualizar y para enviar (WhatsApp/Email).
 */

if (!function_exists('man_cert_asistencia_limpiar_texto')) {
    function man_cert_asistencia_limpiar_texto($texto)
    {
        $texto = (string) $texto;
        if ($texto === '') {
            return '';
        }
        // 1. Decodificar entidades HTML (&quot;, &ntilde;, &#39;, &aacute;, etc.)
        $texto = html_entity_decode($texto, ENT_QUOTES, 'UTF-8');

        // 2. Corregir posibles secuencias Mojibake / doble UTF-8 comunes en MySQL
        $reemplazos = array(
            'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
            'Ã ' => 'Á', 'Ã‰' => 'É', 'Ã ' => 'Í', 'Ã“' => 'Ó', 'Ãš' => 'Ú',
            'Ã±' => 'ñ', 'Ã‘' => 'Ñ', 'Ã¼' => 'ü', 'Ãœ' => 'Ü',
            'â€“' => '–', 'â€”' => '—', 'â€œ' => '“', 'â€' => '”', 'â€˜' => '‘', 'â€™' => '’',
            'Â°' => '°', 'Â«' => '«', 'Â»' => '»'
        );
        $texto = strtr($texto, $reemplazos);

        // 3. Si no es UTF-8 valido, intentar convertir desde ISO-8859-1 o Windows-1252
        if (function_exists('mb_check_encoding') && !mb_check_encoding($texto, 'UTF-8')) {
            if (function_exists('mb_convert_encoding')) {
                $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
            } elseif (function_exists('utf8_encode')) {
                $texto = utf8_encode($texto);
            }
        }

        // 4. Limpiar espacios no separables
        $texto = str_replace(array("\xC2\xA0", "\xA0"), ' ', $texto);

        return $texto;
    }
}

if (!function_exists('man_cert_asistencia_txt')) {
    function man_cert_asistencia_txt($texto)
    {
        return man_cert_asistencia_limpiar_texto($texto);
    }
}

if (!function_exists('man_cert_asistencia_formatear_fecha_evento')) {
    function man_cert_asistencia_formatear_fecha_evento($fechaInicio, $fechaFin = null)
    {
        if (empty($fechaInicio)) {
            return '';
        }
        $tsIni = strtotime($fechaInicio);
        if (!$tsIni) {
            return (string) $fechaInicio;
        }

        $diasSemana = array('domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado');
        $mesesNom = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');

        // Si no hay fecha fin o ambas fechas son iguales (evento de 1 solo día)
        if (empty($fechaFin) || $fechaInicio === $fechaFin) {
            return $diasSemana[(int) date('w', $tsIni)]
                . ' ' . date('d', $tsIni)
                . ' de ' . $mesesNom[(int) date('m', $tsIni) - 1]
                . ' de ' . date('Y', $tsIni);
        }

        $tsFin = strtotime($fechaFin);
        if (!$tsFin) {
            return $diasSemana[(int) date('w', $tsIni)]
                . ' ' . date('d', $tsIni)
                . ' de ' . $mesesNom[(int) date('m', $tsIni) - 1]
                . ' de ' . date('Y', $tsIni);
        }

        // Rango dentro del mismo mes y año (ej: del 15 al 31 de agosto de 2026)
        if (date('Y-m', $tsIni) === date('Y-m', $tsFin)) {
            return 'del ' . date('d', $tsIni) . ' al ' . date('d', $tsFin) . ' de ' . $mesesNom[(int) date('m', $tsFin) - 1] . ' de ' . date('Y', $tsFin);
        }

        // Rango entre meses distintos del mismo año (ej: del 28 de julio al 15 de agosto de 2026)
        if (date('Y', $tsIni) === date('Y', $tsFin)) {
            return 'del ' . date('d', $tsIni) . ' de ' . $mesesNom[(int) date('m', $tsIni) - 1] . ' al ' . date('d', $tsFin) . ' de ' . $mesesNom[(int) date('m', $tsFin) - 1] . ' de ' . date('Y', $tsFin);
        }

        // Rango entre años distintos
        return 'del ' . date('d', $tsIni) . ' de ' . $mesesNom[(int) date('m', $tsIni) - 1] . ' de ' . date('Y', $tsIni) . ' al ' . date('d', $tsFin) . ' de ' . $mesesNom[(int) date('m', $tsFin) - 1] . ' de ' . date('Y', $tsFin);
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

if (!function_exists('man_cert_asistencia_buscar_archivo')) {
    function man_cert_asistencia_buscar_archivo($nombreArchivo, $empCod = '620')
    {
        return man_cert_resolver_imagen($nombreArchivo, $empCod);
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
            'tipo_certificado' => 'DE ASISTENCIA',
            'texto_certificado' => '',
            'mensaje_whatsapp' => '',
            'mensaje_masivo' => '',
            'intervalo_cola' => 5,
            'fecha_emision_texto' => 'Portovelo, El Oro, 08 de agosto de 2026.',
            'area_firma2' => 'Área de Capacitación',
        );
        try {
            $manEve = $manEve !== null ? trim((string) $manEve) : '';
            if ($manEve !== '') {
                $sql = "SELECT Man_ENom, IFNULL(Man_Ehor, '06:00:00') AS Man_EHor, Man_EFei, IFNULL(Man_EFef, Man_EFei) AS Man_EFef,
                               IFNULL(Man_Teve, 'DE ASISTENCIA') AS Man_Teve,
                               IFNULL(Man_Afir, 'Área de Capacitación') AS Man_Afir,
                               IFNULL(Man_Tcrf, '') AS Man_Tcrf,
                               IFNULL(Man_Wms, '') AS Man_Wms,
                               IFNULL(Man_Mmsg, '') AS Man_Mmsg,
                               IFNULL(Man_Mdel, 5) AS Man_Mdel
                        FROM manifiesto_evento
                        WHERE Man_Eve = '" . addslashes($manEve) . "'
                        LIMIT 1";
            } else {
                $sql = "SELECT Man_ENom, IFNULL(Man_Ehor, '06:00:00') AS Man_EHor, Man_EFei, IFNULL(Man_EFef, Man_EFei) AS Man_EFef,
                               IFNULL(Man_Teve, 'DE ASISTENCIA') AS Man_Teve,
                               IFNULL(Man_Afir, 'Área de Capacitación') AS Man_Afir,
                               IFNULL(Man_Tcrf, '') AS Man_Tcrf,
                               IFNULL(Man_Wms, '') AS Man_Wms,
                               IFNULL(Man_Mmsg, '') AS Man_Mmsg,
                               IFNULL(Man_Mdel, 5) AS Man_Mdel
                        FROM manifiesto_evento
                        WHERE UPPER(IFNULL(Man_Vig, 'N')) = 'S' AND UPPER(IFNULL(Man_EEst, 'A')) = 'A'
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
                $fechaInicio = !empty($rowEv['Man_EFei']) ? trim((string)$rowEv['Man_EFei']) : '';
                $fechaFin = !empty($rowEv['Man_EFef']) ? trim((string)$rowEv['Man_EFef']) : $fechaInicio;

                if ($fechaInicio !== '') {
                    $out['fecha_raw'] = $fechaInicio;
                    $out['fecha_texto'] = man_cert_asistencia_formatear_fecha_evento($fechaInicio, $fechaFin);
                }

                $fechaRefEmision = ($fechaFin !== '') ? $fechaFin : ($fechaInicio !== '' ? $fechaInicio : date('Y-m-d'));
                $tsEm = strtotime($fechaRefEmision);
                $mesesNom = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
                if ($tsEm) {
                    $out['fecha_emision_texto'] = 'Portovelo, El Oro, ' . date('d', $tsEm) . ' de ' . $mesesNom[(int)date('m', $tsEm) - 1] . ' de ' . date('Y', $tsEm) . '.';
                }

                if (!empty($rowEv['Man_Teve'])) {
                    $out['tipo_certificado'] = trim((string) $rowEv['Man_Teve']);
                }
                if (!empty($rowEv['Man_Afir'])) {
                    $out['area_firma2'] = trim((string) $rowEv['Man_Afir']);
                }
                if (!empty($rowEv['Man_Tcrf'])) {
                    $out['texto_certificado'] = trim((string) $rowEv['Man_Tcrf']);
                }
                if (!empty($rowEv['Man_Wms'])) {
                    $out['mensaje_whatsapp'] = trim((string) $rowEv['Man_Wms']);
                }
                if (!empty($rowEv['Man_Mmsg'])) {
                    $out['mensaje_masivo'] = trim((string) $rowEv['Man_Mmsg']);
                }
                if (!empty($rowEv['Man_Mdel'])) {
                    $out['intervalo_cola'] = (int) $rowEv['Man_Mdel'];
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
            'fecha_emision_texto' => isset($evData['fecha_emision_texto']) ? $evData['fecha_emision_texto'] : '',
            'tipo_certificado' => isset($evData['tipo_certificado']) ? $evData['tipo_certificado'] : 'DE ASISTENCIA',
            'area_firma2' => isset($evData['area_firma2']) ? $evData['area_firma2'] : 'Área de Capacitación',
            'texto_certificado' => isset($evData['texto_certificado']) ? $evData['texto_certificado'] : '',
            'mensaje_whatsapp' => isset($evData['mensaje_whatsapp']) ? $evData['mensaje_whatsapp'] : '',
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

        $empCod = isset($params['Emp_Cod']) ? (string)$params['Emp_Cod'] : (isset($_SESSION['Emp_Cod']) ? (string)$_SESSION['Emp_Cod'] : '620');
        $tipoCertificado = !empty($params['tipo_certificado'])
            ? trim((string) $params['tipo_certificado'])
            : 'DE ASISTENCIA';
        $tipoActividad = !empty($params['tipo_actividad'])
            ? trim((string) $params['tipo_actividad'])
            : "la capacitaci\xC3\xB3n";

        $mesesAct = array(
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
        );
        $fechaEmisionStr = !empty($params['fecha_emision_texto'])
            ? $params['fecha_emision_texto']
            : ('Portovelo, El Oro, ' . date('d') . ' de ' . $mesesAct[(int) date('m') - 1] . ' de ' . date('Y') . '.');

        $nombreMayus = function_exists('mb_strtoupper')
            ? mb_strtoupper($nombrePersona, 'UTF-8')
            : strtoupper($nombrePersona);

        $pdf = new CertAsistenciaPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Relavera');
        $pdf->SetAuthor('ECOPARKMINING S.A.');
        $pdf->SetTitle('Certificado ' . $tipoCertificado . ' - ' . $nombrePersona);
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
        $ornSize = 12;
        $pdf->SetDrawColor(197, 160, 89);
        $pdf->SetLineWidth(0.7);
        $pdf->Line(13, 13, 13 + $ornSize, 13);
        $pdf->Line(13, 13, 13, 13 + $ornSize);
        $pdf->Line($pageW - 13, 13, $pageW - 13 - $ornSize, 13);
        $pdf->Line($pageW - 13, 13, $pageW - 13, 13 + $ornSize);
        $pdf->Line(13, $pageH - 13, 13 + $ornSize, $pageH - 13);
        $pdf->Line(13, $pageH - 13, 13, $pageH - 13 - $ornSize);
        $pdf->Line($pageW - 13, $pageH - 13, $pageW - 13 - $ornSize, $pageH - 13);
        $pdf->Line($pageW - 13, $pageH - 13, $pageW - 13, $pageH - 13 - $ornSize);

        // Marca de agua (template de fondo con marco y logos)
        $pathWM = man_cert_resolver_imagen('marca_agua.png', $empCod);
        if ($pathWM) {
            $pdf->SetAlpha(0.85);
            $pdf->Image($pathWM, 7, 7, $pageW - 14, $pageH - 14, '', '', '', false, 300, '', false, false, 0);
            $pdf->SetAlpha(1.0);
        }

        // Encabezado
        $pdf->SetY(20);
        $pdf->SetTextColor(11, 37, 69); // #0b2545
        $pdf->SetFont('helvetica', 'B', 17);
        $pdf->Cell(0, 7, 'ECOPARKMINING S.A.', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 5, 'PROYECTO AMBIENTAL ASOCIATIVO RELAVERA COMUNITARIA "EL TABLÓN"', 0, 1, 'C');

        // Linea verde + hojas
        $pdf->SetDrawColor(134, 239, 172);
        $pdf->SetLineWidth(0.4);
        $midY = $pdf->GetY() + 2;
        $pdf->Line(55, $midY, ($pageW / 2) - 8, $midY);
        $pdf->Line(($pageW / 2) + 8, $midY, $pageW - 55, $midY);
        $pdf->SetY($midY + 3);

        $pdf->SetTextColor(30, 58, 138);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'OTORGA EL PRESENTE', 0, 1, 'C');

        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('times', 'B', 32);
        $pdf->Cell(0, 12, 'CERTIFICADO', 0, 1, 'C');

        $pdf->SetTextColor(22, 163, 74);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 7, $tipoCertificado, 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, '. . .', 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 6, 'A:', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 9, $nombreMayus, 0, 1, 'C');

        $lineW = 130;
        $pdf->SetDrawColor(11, 37, 69);
        $pdf->SetLineWidth(0.6);
        $yLine = $pdf->GetY();
        $pdf->Line(($pageW - $lineW) / 2, $yLine, ($pageW + $lineW) / 2, $yLine);

        $pdf->Ln(2);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'C.I.: ' . $prsCed, 0, 1, 'C');

        $pdf->Ln(4);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetFont('helvetica', '', 11);

        $textoCertificado = !empty($params['texto_certificado'])
            ? trim((string) $params['texto_certificado'])
            : '';

        if (!empty($textoCertificado) && (strpos($textoCertificado, '{') !== false || strlen($textoCertificado) > 30)) {
            $parrafoHtml = str_replace(
                array('{nombre}', '{cedula}', '{fecha}', '{horas}', '{evento}', '{proyecto}'),
                array(
                    '<b>' . htmlspecialchars($nombrePersona, ENT_QUOTES, 'UTF-8') . '</b>',
                    '<b>' . htmlspecialchars($prsCed, ENT_QUOTES, 'UTF-8') . '</b>',
                    '<b>' . htmlspecialchars($fechaEventoTexto, ENT_QUOTES, 'UTF-8') . '</b>',
                    '<b>' . htmlspecialchars($horasEvento, ENT_QUOTES, 'UTF-8') . ' horas</b>',
                    '<b>"' . htmlspecialchars($nombreEvento, ENT_QUOTES, 'UTF-8') . '"</b>',
                    '<b>Proyecto Ambiental Asociativo Relavera Comunitaria "El Tablón"</b>'
                ),
                htmlspecialchars($textoCertificado, ENT_QUOTES, 'UTF-8')
            );
            $parrafoHtml = htmlspecialchars_decode($parrafoHtml);
        } else {
            $actividad = !empty($textoCertificado) ? htmlspecialchars($textoCertificado, ENT_QUOTES, 'UTF-8') : 'la capacitación';
            $conectorFecha = (strpos($fechaEventoTexto, 'del ') === 0) ? ' ' : ' el día ';
            $parrafoHtml = 'Que el Sr(a). <b>' . htmlspecialchars($nombrePersona, ENT_QUOTES, 'UTF-8') . '</b> asistió a ' . $actividad . ' de <b>Proyecto Ambiental Asociativo Relavera Comunitaria "El Tablón"</b>' . $conectorFecha . '<b>' . htmlspecialchars($fechaEventoTexto, ENT_QUOTES, 'UTF-8') . '</b> con una duración de <b>' . htmlspecialchars($horasEvento, ENT_QUOTES, 'UTF-8') . ' horas</b> con el tema <b>"' . htmlspecialchars($nombreEvento, ENT_QUOTES, 'UTF-8') . '"</b>.';
        }

        // Párrafo con ancho proporcional a la vista previa HTML y formato HTML para negritas
        $wParrafo = 220;
        $xParrafo = ($pageW - $wParrafo) / 2;
        $yParrafo = $pdf->GetY();
        $pdf->writeHTMLCell($wParrafo, 0, $xParrafo, $yParrafo, '<div style="text-align: center; color: #334155; font-size: 11pt; line-height: 1.6;">' . $parrafoHtml . '</div>', 0, 1, false, true, 'C', true);

        // Fecha de emisión ubicada en posición intermedia equilibrada
        $pdf->SetY($pdf->GetY() + 4);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 5, $fechaEmisionStr, 0, 1, 'C');

        // Firmas (Firma 1 centrada, Firma 2 comentada)
        $yFirmas = $pageH - 32;
        $pdf->SetDrawColor(11, 37, 69);
        $pdf->SetLineWidth(0.6);

        $wFirma = 80;
        $xCenter = ($pageW - $wFirma) / 2;

        // Línea de firma centrada
        $pdf->Line($xCenter, $yFirmas, $xCenter + $wFirma, $yFirmas);

        // Texto justo debajo de la línea sin separación excesiva
        $pdf->SetY($yFirmas + 1.5);
        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('helvetica', 'B', 9.5);
        $pdf->SetX($xCenter);
        $pdf->Cell($wFirma, 4.5, 'Gerencia General', 0, 1, 'C');

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetX($xCenter);
        $pdf->Cell($wFirma, 4, 'ECOPARKMINING S.A.', 0, 1, 'C');

        /* Segunda firma comentada
        $x1 = 55;
        $x2 = $pageW - 125;
        $pdf->Line($x1, $yFirmas, $x1 + 70, $yFirmas);
        $pdf->Line($x2, $yFirmas, $x2 + 70, $yFirmas);
        $pdf->SetY($yFirmas + 2);
        $pdf->SetTextColor(11, 37, 69);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetX($x1);
        $pdf->Cell(70, 5, 'Gerencia General', 0, 0, 'C');
        $pdf->SetX($x2);
        $areaFirma2 = !empty($params['area_firma2']) ? $params['area_firma2'] : 'Área de Capacitación';
        $pdf->Cell(70, 5, $areaFirma2, 0, 1, 'C');
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetX($x1);
        $pdf->Cell(70, 4, 'ECOPARKMINING S.A.', 0, 0, 'C');
        $pdf->SetX($x2);
        $pdf->Cell(70, 4, 'ECOPARKMINING S.A.', 0, 1, 'C');
        */

        // --- Imagen del Sello y Firmas (igual al HTML) ---
        $rutaSello = realpath(__DIR__ . '/../../imagenes/620/sello.png');
        if (!$rutaSello || !is_file($rutaSello)) {
            $rutaSello = realpath(__DIR__ . '/../../imagenes/620/sello.jpg');
        }

        // 1. Sello en la esquina inferior izquierda
        if ($rutaSello && is_file($rutaSello)) {
            $pdf->Image($rutaSello, 15, $pageH - 48, 30, 30, '', '', '', false, 300, '', false, false, 0, false, false, false);
        }

        // 2. Firma 1 al ras sobre la línea centrada de Gerencia General
        $rutaFirma1 = realpath(__DIR__ . '/../../imagenes/620/firma1.png');
        if (!$rutaFirma1 || !is_file($rutaFirma1)) {
            $rutaFirma1 = $rutaSello; // Fallback al sello si no hay firma
        }
        if ($rutaFirma1 && is_file($rutaFirma1)) {
            $wFirmaImg = 40;
            $hFirmaImg = 20;
            $xFirmaImg = ($pageW - $wFirmaImg) / 2;
            $yFirmaImg = $yFirmas - $hFirmaImg + 2;
            $pdf->Image($rutaFirma1, $xFirmaImg, $yFirmaImg, $wFirmaImg, $hFirmaImg, '', '', '', false, 300, '', false, false, 0, false, false, false);
        }

        /* Segunda firma comentada
        // 3. Firma 2 sobre la línea de Área de Capacitación
        $rutaFirma2 = realpath(__DIR__ . '/../../imagenes/620/firma2.png');
        if (!$rutaFirma2 || !is_file($rutaFirma2)) {
            $rutaFirma2 = $rutaSello;
        }
        if ($rutaFirma2 && is_file($rutaFirma2)) {
            $pdf->Image($rutaFirma2, 189.5, $yFirmas - 23, 35, 25, '', '', '', false, 300, '', false, false, 0, false, false, false);
        }
        */

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
