<?php

/**
 * Formulario: Gestión de Eventos y Visitantes
 * Agrupa la gestión de Eventos y Visitantes.
 * Ubicación: relavera/FRONT/man_alt_visitantes.php
 * @author Sistema EXA
 * @version 3.0
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_visitantes.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once(__DIR__ . '/../LOGICA/relavera_whatsapp_utils.php');
require_once(__DIR__ . '/../LOGICA/relavera_notif_mail_utils.php');
require_once(__DIR__ . '/../LOGICA/man_cert_asistencia_helper.php');

$obBD_conexion = new Class_Log_Conexion_Visitantes($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Visitantes;

/* ==========================================================================
   VISUALIZADOR / IMPRESOR DE CERTIFICADO EN PDF (PLANTILLA ECOPARK MINING)
   ========================================================================== */
if (isset($_GET['verCertificadoPdfAjax'])) {
    $prsNom = isset($_GET['Prs_Nom']) ? trim($_GET['Prs_Nom']) : 'Nombres';
    $prsApe = isset($_GET['Prs_Ape']) ? trim($_GET['Prs_Ape']) : 'Apellidos';
    $prsCed = isset($_GET['Prs_Ced']) ? trim($_GET['Prs_Ced']) : '1100000000';
    $esVis = isset($_GET['es_visitante']) && $_GET['es_visitante'] == '1';
    $manEve = isset($_GET['Man_Eve']) && trim($_GET['Man_Eve']) !== '' ? trim($_GET['Man_Eve']) : null;

    $nombrePersona = trim($prsNom . ' ' . $prsApe);
    if (empty($nombrePersona) || $nombrePersona === 'Nombres Apellidos') {
        $nombrePersona = 'Juan Carlos Pérez';
    }

    $evData = man_cert_asistencia_resolver_evento($obBD_con1, $obBD_conexion, $manEve);
    $nombreEvento = $evData['nombre'];
    $horasEvento = $evData['horas'];
    $fechaEventoTexto = $evData['fecha_texto'];
    $tipoCertificado = !empty($evData['tipo_certificado']) ? $evData['tipo_certificado'] : 'DE ASISTENCIA';
    $textoCertificado = !empty($evData['texto_certificado']) ? trim($evData['texto_certificado']) : '';

    if (!empty($textoCertificado) && (strpos($textoCertificado, '{') !== false || strlen($textoCertificado) > 30)) {
        $parrafoHtml = str_replace(
            array('{nombre}', '{cedula}', '{fecha}', '{horas}', '{evento}', '{proyecto}'),
            array(
                '<strong>' . htmlspecialchars($nombrePersona) . '</strong>',
                '<strong>' . htmlspecialchars($prsCed) . '</strong>',
                '<strong>' . $fechaEventoTexto . '</strong>',
                '<strong>' . htmlspecialchars($horasEvento) . ' horas</strong>',
                '<strong>"' . htmlspecialchars($nombreEvento) . '"</strong>',
                '<strong>Proyecto Ambiental Asociativo Relavera Comunitaria "El Tablón"</strong>'
            ),
            htmlspecialchars($textoCertificado)
        );
        $parrafoHtml = htmlspecialchars_decode($parrafoHtml);
    } else {
        $actividad = !empty($textoCertificado) ? htmlspecialchars($textoCertificado) : 'la capacitación';
        $conectorFecha = (strpos($fechaEventoTexto, 'del ') === 0) ? ' ' : ' el día ';
        $parrafoHtml = 'Que el Sr(a). <strong>' . htmlspecialchars($nombrePersona) . '</strong> asistió a ' . $actividad . ' de <strong>Proyecto Ambiental Asociativo Relavera Comunitaria "El Tablón"</strong>' . $conectorFecha . '<strong>' . $fechaEventoTexto . '</strong> con una duración de <strong>' . htmlspecialchars($horasEvento) . ' horas</strong> con el tema <strong>"' . htmlspecialchars($nombreEvento) . '"</strong>.';
    }

    $mesesAct = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
    $fechaEmisionStr = !empty($evData['fecha_emision_texto'])
        ? $evData['fecha_emision_texto']
        : ('Portovelo, El Oro, ' . date('d') . ' de ' . $mesesAct[(int)date('m') - 1] . ' de ' . date('Y') . '.');

    // Carga robusta de imágenes en Base64 para marca de agua, sello, firma1 y firma2
    $srcWatermark = '../../imagenes/620/marca_agua.png';
    $pathWM1 = __DIR__ . '/../../imagenes/620/marca_agua.png';
    $pathWM2 = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/imagenes/620/marca_agua.png' : '';
    if (file_exists($pathWM1)) {
        $srcWatermark = 'data:image/png;base64,' . base64_encode(file_get_contents($pathWM1));
    } elseif (!empty($pathWM2) && file_exists($pathWM2)) {
        $srcWatermark = 'data:image/png;base64,' . base64_encode(file_get_contents($pathWM2));
    }

    $srcSello = '../../imagenes/620/sello.png';
    $pathSello1 = __DIR__ . '/../../imagenes/620/sello.png';
    $pathSello2 = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/imagenes/620/sello.png' : '';
    if (file_exists($pathSello1)) {
        $srcSello = 'data:image/png;base64,' . base64_encode(file_get_contents($pathSello1));
    } elseif (!empty($pathSello2) && file_exists($pathSello2)) {
        $srcSello = 'data:image/png;base64,' . base64_encode(file_get_contents($pathSello2));
    }

    // Firma 1 (Gerencia General)
    $srcFirma1 = '../../imagenes/620/firma1.png';
    $pathFirma1_1 = __DIR__ . '/../../imagenes/620/firma1.png';
    $pathFirma1_2 = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/imagenes/620/firma1.png' : '';
    if (file_exists($pathFirma1_1)) {
        $srcFirma1 = 'data:image/png;base64,' . base64_encode(file_get_contents($pathFirma1_1));
    } elseif (!empty($pathFirma1_2) && file_exists($pathFirma1_2)) {
        $srcFirma1 = 'data:image/png;base64,' . base64_encode(file_get_contents($pathFirma1_2));
    } else {
        $srcFirma1 = $srcSello;
    }

    // Firma 2 (Área de Capacitación)
    $srcFirma2 = '../../imagenes/620/firma2.png';
    $pathFirma2_1 = __DIR__ . '/../../imagenes/620/firma2.png';
    $pathFirma2_2 = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/imagenes/620/firma2.png' : '';
    if (file_exists($pathFirma2_1)) {
        $srcFirma2 = 'data:image/png;base64,' . base64_encode(file_get_contents($pathFirma2_1));
    } elseif (!empty($pathFirma2_2) && file_exists($pathFirma2_2)) {
        $srcFirma2 = 'data:image/png;base64,' . base64_encode(file_get_contents($pathFirma2_2));
    } else {
        $srcFirma2 = $srcSello;
    }
?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Certificado de Asistencia - <?php echo htmlspecialchars($nombrePersona); ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                background: #e2e8f0;
                font-family: 'Montserrat', sans-serif;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
                color: #0f172a;
            }

            .no-print-bar {
                width: 1020px;
                max-width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #0f172a;
                color: #fff;
                padding: 12px 24px;
                border-radius: 8px;
                margin-bottom: 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .no-print-bar h3 {
                font-size: 15px;
                font-weight: 600;
                color: #38bdf8;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .no-print-bar .btns {
                display: flex;
                gap: 10px;
            }

            .btn-action {
                padding: 8px 18px;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                font-size: 13px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s;
            }

            .btn-print {
                background: #0284c7;
                color: #fff;
            }

            .btn-print:hover {
                background: #0369a1;
            }

            .btn-close {
                background: #475569;
                color: #fff;
            }

            .btn-close:hover {
                background: #334155;
            }

            .cert-canvas {
                width: 1020px;
                height: 720px;
                background: #ffffff;
                position: relative;
                border-radius: 4px;
                box-shadow: 0 14px 40px rgba(0, 0, 0, 0.15);
                overflow: hidden;
                padding: 40px 60px;
                border: 12px solid #0b2545;
                outline: 3px solid #c5a059;
                outline-offset: -8px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                text-align: center;
            }

            .corner-ornament {
                position: absolute;
                width: 45px;
                height: 45px;
                border: 2px solid #c5a059;
                z-index: 4;
            }

            .corner-top-left {
                top: 18px;
                left: 18px;
                border-right: none;
                border-bottom: none;
            }

            .corner-top-right {
                top: 18px;
                right: 18px;
                border-left: none;
                border-bottom: none;
            }

            .corner-bottom-left {
                bottom: 18px;
                left: 18px;
                border-right: none;
                border-top: none;
            }

            .corner-bottom-right {
                bottom: 18px;
                right: 18px;
                border-left: none;
                border-top: none;
            }

            .cert-watermark {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: fill;
                opacity: 0.85;
                mix-blend-mode: multiply;
                pointer-events: none;
                z-index: 1;
            }

            .cert-sello-box-corner {
                position: absolute;
                left: 45px;
                bottom: 30px;
                z-index: 10;
                pointer-events: none;
            }

            .cert-sello-img-corner {
                width: 120px;
                height: 120px;
                object-fit: contain;
                transform: rotate(-15deg);
                opacity: 0.92;
                filter: drop-shadow(0px 2px 5px rgba(0, 0, 0, 0.15));
            }

            .cert-sello-box-left {
                position: absolute;
                left: 185px;
                bottom: 68px;
                z-index: 10;
                pointer-events: none;
            }

            .cert-sello-img-left {
                width: 115px;
                height: 115px;
                object-fit: contain;
                transform: rotate(-12deg);
                opacity: 0.92;
                filter: drop-shadow(0px 2px 5px rgba(0, 0, 0, 0.15));
            }

            .cert-sello-box-right {
                position: absolute;
                right: 185px;
                bottom: 68px;
                z-index: 10;
                pointer-events: none;
            }

            .cert-sello-img-right {
                width: 115px;
                height: 115px;
                object-fit: contain;
                transform: rotate(12deg);
                opacity: 0.92;
                filter: drop-shadow(0px 2px 5px rgba(0, 0, 0, 0.15));
            }

            .cert-content {
                position: relative;
                z-index: 5;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: center;
            }

            .company-title {
                font-family: 'Cinzel', serif;
                font-size: 26px;
                font-weight: 900;
                color: #0b2545;
                letter-spacing: 2px;
                text-transform: uppercase;
                margin-bottom: 2px;
            }

            .company-subtitle {
                font-size: 11px;
                font-weight: 700;
                color: #1e3a8a;
                letter-spacing: 1.5px;
                text-transform: uppercase;
                line-height: 1.4;
                max-width: 650px;
                margin: 0 auto;
            }

            .green-divider {
                margin: 12px auto;
                width: 75%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
            }

            .green-divider .line {
                flex: 1;
                height: 1px;
                background: #86efac;
            }

            .green-divider .leaf-icon {
                color: #16a34a;
                font-size: 15px;
            }

            .grant-header {
                font-size: 12px;
                font-weight: 700;
                color: #1e3a8a;
                letter-spacing: 3px;
                text-transform: uppercase;
                margin-top: 4px;
                margin-bottom: 4px;
            }

            .main-cert-title {
                font-family: 'Cinzel', serif;
                font-size: 38px;
                font-weight: 900;
                color: #0b2545;
                letter-spacing: 5px;
                text-transform: uppercase;
                margin-bottom: 2px;
            }

            .sub-cert-title {
                font-size: 18px;
                font-weight: 800;
                color: #16a34a;
                letter-spacing: 4px;
                text-transform: uppercase;
            }

            .dots-divider {
                color: #16a34a;
                font-size: 14px;
                margin: 8px 0 12px 0;
                letter-spacing: 4px;
            }

            .grant-to {
                font-size: 15px;
                font-weight: 800;
                color: #0b2545;
                margin-bottom: 6px;
            }

            .recipient-name {
                font-size: 26px;
                font-weight: 900;
                color: #0b2545;
                padding-bottom: 4px;
                border-bottom: 2px solid #0b2545;
                display: inline-block;
                min-width: 500px;
                margin-bottom: 6px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .recipient-cedula {
                font-size: 14px;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 16px;
            }

            .cert-paragraph {
                font-size: 14px;
                color: #334155;
                line-height: 1.75;
                max-width: 780px;
                margin: 0 auto 16px auto;
                text-align: center;
            }

            .cert-paragraph strong {
                color: #0f172a;
                font-weight: 800;
            }

            .bottom-section {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .location-tag {
                font-size: 13px;
                font-weight: 600;
                color: #334155;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                margin-bottom: 15px;
            }

            .location-tag span {
                color: #16a34a;
            }

            .cert-footer {
                width: 100%;
                display: flex;
                justify-content: space-around;
                align-items: flex-end;
                padding-bottom: 5px;
            }

            .signature-block {
                width: 260px;
                text-align: center;
            }

            .signature-line {
                width: 100%;
                border-top: 2px solid #0b2545;
                height: 35px;
            }

            .signature-role {
                font-size: 11px;
                font-weight: 800;
                color: #0b2545;
                text-transform: uppercase;
            }

            .signature-company {
                font-size: 10px;
                color: #64748b;
                font-weight: 600;
            }

            @media print {
                body {
                    background: #fff;
                    padding: 0;
                }

                .no-print-bar {
                    display: none !important;
                }

                .cert-canvas {
                    box-shadow: none;
                    border-width: 10px;
                    width: 100vw;
                    height: 100vh;
                    padding: 25px;
                }

                @page {
                    size: landscape;
                    margin: 0;
                }
            }
        </style>
    </head>

    <body>
        <div class="no-print-bar">
            <h3><i class="glyphicon glyphicon-certificate"></i> Vista Previa del Certificado (ECOPARKMINING S.A.)</h3>
            <div class="btns">
                <button class="btn-action btn-print" onclick="window.print();"><i class="glyphicon glyphicon-print"></i> Imprimir / PDF</button>
                <button class="btn-action btn-close" onclick="window.close();"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
            </div>
        </div>

        <div class="cert-canvas">
            <div class="corner-ornament corner-top-left"></div>
            <div class="corner-ornament corner-top-right"></div>
            <div class="corner-ornament corner-bottom-left"></div>
            <div class="corner-ornament corner-bottom-right"></div>

            <img src="<?php echo $srcWatermark; ?>" class="cert-watermark" alt="Marca de Agua" onerror="this.src='/imagenes/620/marca_agua.png';">

            <div class="cert-sello-box-corner">
                <img src="<?php echo $srcSello; ?>" class="cert-sello-img-corner" alt="Sello Esquina" onerror="this.src='/imagenes/620/sello.png';">
            </div>

            <div class="cert-sello-box-left">
                <img src="<?php echo $srcFirma1; ?>" class="cert-sello-img-left" alt="Firma Gerencia General" onerror="this.src='/imagenes/620/firma1.png';">
            </div>

            <div class="cert-sello-box-right">
                <img src="<?php echo $srcFirma2; ?>" class="cert-sello-img-right" alt="Firma Área de Capacitación" onerror="this.src='/imagenes/620/firma2.png';">
            </div>

            <div class="cert-content">
                <div>
                    <div class="company-title">ECOPARKMINING S.A.</div>
                    <div class="company-subtitle">PROYECTO AMBIENTAL ASOCIATIVO RELAVERA COMUNITARIA "EL TABLÓN"</div>

                    <div class="green-divider">
                        <div class="line"></div>
                        <div class="leaf-icon">🍃</div>
                        <div class="line"></div>
                    </div>

                    <div class="grant-header">OTORGA EL PRESENTE</div>
                    <div class="main-cert-title">CERTIFICADO</div>
                    <div class="sub-cert-title"><?php echo htmlspecialchars($tipoCertificado); ?></div>

                    <div class="dots-divider">• • •</div>

                    <div class="grant-to">A:</div>
                    <div class="recipient-name"><?php echo htmlspecialchars($nombrePersona); ?></div>
                    <div class="recipient-cedula">C.I.: <strong><?php echo htmlspecialchars($prsCed); ?></strong></div>

                    <div class="cert-paragraph">
                        <?php echo $parrafoHtml; ?>
                    </div>
                </div>

                <div class="bottom-section">
                    <div class="location-tag">
                        <span>📍</span> <?php echo $fechaEmisionStr; ?>
                    </div>

                    <div class="cert-footer">
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-role">Gerencia General</div>
                            <div class="signature-company">ECOPARKMINING S.A.</div>
                        </div>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-role"><?php echo htmlspecialchars(!empty($evData['area_firma2']) ? $evData['area_firma2'] : 'Área de Capacitación', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="signature-company">ECOPARKMINING S.A.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// Catálogo de eventos para el tab Eventos (lista desplegable)
$eventosCatalogo = array();
try {
    $paramsEvCat = array($Ses_Emp_Cod);
    $paramsEvCat['limits'] = 'LIMIT 500';
    $eventosCatalogo = $obBD_con1->getArrayConsulta(18, $paramsEvCat, $obBD_conexion);
    if (!is_array($eventosCatalogo)) {
        $eventosCatalogo = array();
    }
    $obBD_con1->utf8_change_param($eventosCatalogo);
} catch (Exception $e) {
    $eventosCatalogo = array();
}

// Consultar evento actualmente en vigencia (Man_Vig = 'S')
$nombreEventoVigente = '';
$idEventoVigente = '';
$fefEventoVigente = '';
try {
    $resEv = $obBD_con1->consulta("SELECT Man_Eve, Man_ENom, Man_EFef FROM manifiesto_evento WHERE UPPER(Man_Vig) = 'S' AND UPPER(Man_EEst) = 'A' LIMIT 1", $obBD_conexion->conexion);
    if ($resEv && ($rowEv = $obBD_con1->fetch_assoc($resEv))) {
        $nombreEventoVigente = trim($rowEv['Man_ENom']);
        $idEventoVigente = $rowEv['Man_Eve'];
        $fefEventoVigente = !empty($rowEv['Man_EFef']) ? trim($rowEv['Man_EFef']) : '';
    }
} catch (Exception $e) {
}

/* ==========================================================================
   FUNCIÓN AUXILIAR DE COMPRESIÓN DE IMÁGENES EN SERVIDOR (PHP GD)
   ========================================================================== */
function optimizarYComprimirImagen($sourcePath, $targetPath, $maxDim = 1920, $quality = 85)
{
    list($width, $height, $type) = @getimagesize($sourcePath);
    if (!$width || !$height) return false;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $srcImg = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $srcImg = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return move_uploaded_file($sourcePath, $targetPath);
    }

    if (!$srcImg) return move_uploaded_file($sourcePath, $targetPath);

    $ratio = $width / $height;
    if ($width > $maxDim || $height > $maxDim) {
        if ($ratio > 1) {
            $newWidth = $maxDim;
            $newHeight = round($maxDim / $ratio);
        } else {
            $newHeight = $maxDim;
            $newWidth = round($maxDim * $ratio);
        }
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $dstImg = imagecreatetruecolor($newWidth, $newHeight);

    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $res = imagejpeg($dstImg, $targetPath, $quality);

    imagedestroy($srcImg);
    imagedestroy($dstImg);

    return $res && file_exists($targetPath);
}

function responderJsonLimpio($response)
{
    if (class_exists('DebugBar')) {
        @DebugBar::sendDataInHeaders(true);
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

/* ==========================================================================
   MANEJADORES AJAX (AJAX DISPATCHER)
   ========================================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $maxPost = ini_get('post_max_size');
    $contentMB = number_format($_SERVER['CONTENT_LENGTH'] / (1024 * 1024), 2);
    responderJsonLimpio(array(
        'success' => false,
        'message' => "El tamaño total del envío ($contentMB MB) superó el límite 'post_max_size' ($maxPost) configurado en el servidor PHP. Reduzca la cantidad o tamaño de archivos adjuntos."
    ));
}

// 1. Listar Eventos
if (isset($_REQUEST['listEventosGridAjax'])) {
    $req = array_merge($_GET, $_POST);
    $page = isset($req['page']) ? intval($req['page']) : 1;
    $rows = isset($req['rows']) ? intval($req['rows']) : 50;
    if ($rows <= 0 || $rows >= 99999) {
        $rows = 999999;
    }
    if ($page < 1) {
        $page = 1;
    }
    $params = array($Ses_Emp_Cod);
    if (!empty($req['search'])) {
        $params['search'] = $req['search'];
    }
    if (!empty($req['Man_Eve'])) {
        $params['Man_Eve'] = $req['Man_Eve'];
    }
    $contar = $obBD_con1->getRowConsulta(18, $params, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];
    if ($contar['total'] > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(18, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    responderJsonLimpio($response);
}

// 2. Listar Visitantes / Personas por Evento
if (isset($_REQUEST['listVisitantesEventoGridAjax'])) {
    $req = array_merge($_GET, $_POST);
    $page = isset($req['page']) ? intval($req['page']) : 1;
    $rows = isset($req['rows']) ? intval($req['rows']) : 50;
    if ($rows <= 0 || $rows >= 99999) {
        $rows = 999999;
    }
    if ($page < 1) {
        $page = 1;
    }
    $params = array($Ses_Emp_Cod);
    if (!empty($req['Man_Eve'])) {
        $params['Man_Eve'] = $req['Man_Eve'];
    }
    if (isset($req['op_opciones'])) {
        $params['op_opciones'] = $req['op_opciones'];
    }
    if (!empty($req['search'])) {
        $params['search'] = $req['search'];
    }
    $contar = $obBD_con1->getRowConsulta(19, $params, $obBD_conexion);
    $total = isset($contar['total']) ? intval($contar['total']) : 0;
    $pagination = pages($total, $page, $rows);
    $response = $pagination['data'];
    if ($total > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(19, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    responderJsonLimpio($response);
}

// 3. Buscar persona / visitante por Cédula
if (isset($_GET['buscarPersonaCedulaAjax'])) {
    $resp = array('success' => true, 'existe' => false, 'esVisitante' => false);
    $ced = isset($_GET['Prs_Ced']) ? preg_replace('/[^a-zA-Z0-9]/', '', trim($_GET['Prs_Ced'])) : '';
    if (!empty($ced)) {
        $fotosVisitante = array('MVis_Doc_Ced' => '', 'MVis_Doc_Ced_Rev' => '', 'MVis_Doc_Vot' => '', 'MVis_Doc_Fot' => '');
        try {
            $resFV = @$obBD_con1->consulta("SELECT * FROM manifiesto_visitante mv INNER JOIN persona p ON p.Prs_Cod = mv.Prs_Cod WHERE p.Prs_Ced = '$ced' AND mv.Emp_Cod = '$Ses_Emp_Cod' ORDER BY mv.MVis_Cod DESC", $obBD_conexion->conexion);
            if ($resFV && mysqli_num_rows($resFV) > 0) {
                while ($rowFV = $obBD_con1->fetch_assoc($resFV)) {
                    if (isset($rowFV['MVis_Doc_Ced']) && empty($fotosVisitante['MVis_Doc_Ced']) && !empty($rowFV['MVis_Doc_Ced'])) $fotosVisitante['MVis_Doc_Ced'] = $rowFV['MVis_Doc_Ced'];
                    if (isset($rowFV['MVis_Doc_Ced_Rev']) && empty($fotosVisitante['MVis_Doc_Ced_Rev']) && !empty($rowFV['MVis_Doc_Ced_Rev'])) $fotosVisitante['MVis_Doc_Ced_Rev'] = $rowFV['MVis_Doc_Ced_Rev'];
                    if (isset($rowFV['MVis_Doc_Vot']) && empty($fotosVisitante['MVis_Doc_Vot']) && !empty($rowFV['MVis_Doc_Vot'])) $fotosVisitante['MVis_Doc_Vot'] = $rowFV['MVis_Doc_Vot'];
                    if (isset($rowFV['MVis_Doc_Fot']) && empty($fotosVisitante['MVis_Doc_Fot']) && !empty($rowFV['MVis_Doc_Fot'])) $fotosVisitante['MVis_Doc_Fot'] = $rowFV['MVis_Doc_Fot'];
                }
            }
        } catch (Exception $eFV) {
        }

        // Verificar si ya existe como visitante en la empresa
        $visitante = $obBD_con1->getRowConsulta(16, array($Ses_Emp_Cod, $ced), $obBD_conexion);
        if (!empty($visitante)) {
            $resp['existe'] = true;
            $resp['esVisitante'] = true;
            if (empty($visitante['MVis_Doc_Ced']) && !empty($fotosVisitante['MVis_Doc_Ced'])) $visitante['MVis_Doc_Ced'] = $fotosVisitante['MVis_Doc_Ced'];
            if (empty($visitante['MVis_Doc_Ced_Rev']) && !empty($fotosVisitante['MVis_Doc_Ced_Rev'])) $visitante['MVis_Doc_Ced_Rev'] = $fotosVisitante['MVis_Doc_Ced_Rev'];
            if (empty($visitante['MVis_Doc_Vot']) && !empty($fotosVisitante['MVis_Doc_Vot'])) $visitante['MVis_Doc_Vot'] = $fotosVisitante['MVis_Doc_Vot'];
            if (empty($visitante['MVis_Doc_Fot']) && !empty($fotosVisitante['MVis_Doc_Fot'])) $visitante['MVis_Doc_Fot'] = $fotosVisitante['MVis_Doc_Fot'];
            $resp['visitante'] = $visitante;
            $resp['persona'] = array(
                'Prs_Cod' => $visitante['Prs_Cod'],
                'Prs_Ced' => $visitante['Prs_Ced'],
                'Prs_Nom' => $visitante['Prs_Nom'],
                'Prs_Ape' => $visitante['Prs_Ape'],
                'Prs_Tel' => !empty($visitante['Prs_Tel_Base']) ? $visitante['Prs_Tel_Base'] : (isset($visitante['MVis_Tel']) ? $visitante['MVis_Tel'] : ''),
                'Prs_Cor' => !empty($visitante['Prs_Cor']) ? $visitante['Prs_Cor'] : '',
                'Prs_Dir' => !empty($visitante['Prs_Dir_Base']) ? $visitante['Prs_Dir_Base'] : '',
                'Prs_Fec' => $visitante['Prs_Fec']
            );
            $obBD_con1->utf8_change_param($resp['visitante']);
            $obBD_con1->utf8_change_param($resp['persona']);
        } else {
            // Verificar si existe en tabla persona
            $persona = $obBD_con1->getRowConsulta(6, array($ced), $obBD_conexion);
            if (!empty($persona)) {
                $resp['existe'] = true;
                $resp['esVisitante'] = false;
                $resp['persona'] = $persona;
                $obBD_con1->utf8_change_param($resp['persona']);
            }
        }
    }
    responderJsonLimpio($resp);
}

// 4. Obtener Visitante Completo por ID
if (isset($_GET['getVisitanteByIdAjax'])) {
    $resp = array('success' => false);
    $MVis_Cod = isset($_GET['MVis_Cod']) ? $_GET['MVis_Cod'] : (isset($_GET['Vis_Cod']) ? $_GET['Vis_Cod'] : '');
    if (!empty($MVis_Cod)) {
        $visitante = $obBD_con1->getRowConsulta(17, array($MVis_Cod), $obBD_conexion);
        if (!empty($visitante)) {
            $resp['success'] = true;
            $resp['visitante'] = $visitante;
            $obBD_con1->utf8_change_param($resp['visitante']);
        }
    }
    responderJsonLimpio($resp);
}

// 5. Guardar Visitante Completo para Evento
if (isset($_POST['saveVisitanteAjax']) || isset($_POST['saveChoferAjax'])) {
    $resp = array('success' => false);
    $MVis_Cod = isset($_POST['MVis_Cod']) ? trim($_POST['MVis_Cod']) : (isset($_POST['Vis_Cod']) ? trim($_POST['Vis_Cod']) : '');
    $Man_Eve = isset($_POST['Man_Eve']) && $_POST['Man_Eve'] !== '' ? trim($_POST['Man_Eve']) : null;
    $Prs_Cod = isset($_POST['Prs_Cod']) ? trim($_POST['Prs_Cod']) : '';

    // Leer Vis_Ced; fallback a Cho_Ced (retrocompatibilidad) y a Prs_Ced
    $Vis_Ced = isset($_POST['Vis_Ced']) ? preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['Vis_Ced'])) : '';
    if (empty($Vis_Ced) && isset($_POST['Cho_Ced'])) {
        $Vis_Ced = preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['Cho_Ced']));
    }
    if (empty($Vis_Ced) && isset($_POST['Prs_Ced'])) {
        $Vis_Ced = preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['Prs_Ced']));
    }
    $Prs_Nom = isset($_POST['Prs_Nom']) ? addslashes($_POST['Prs_Nom']) : '';
    $Prs_Ape = isset($_POST['Prs_Ape']) ? addslashes($_POST['Prs_Ape']) : '';
    $Prs_Fec = !empty($_POST['Prs_Fec']) ? $_POST['Prs_Fec'] : null;

    $Vis_Nac = isset($_POST['Vis_Nac']) && $_POST['Vis_Nac'] !== '' ? addslashes($_POST['Vis_Nac']) : (isset($_POST['Cho_Nac']) && $_POST['Cho_Nac'] !== '' ? addslashes($_POST['Cho_Nac']) : 'Ecuatoriana');
    $Vis_Eci = isset($_POST['Vis_Eci']) && $_POST['Vis_Eci'] !== '' ? $_POST['Vis_Eci'] : (isset($_POST['Cho_Eci']) && $_POST['Cho_Eci'] !== '' ? $_POST['Cho_Eci'] : 'Soltero/a');
    $Vis_Tsa = isset($_POST['Vis_Tsa']) ? $_POST['Vis_Tsa'] : (isset($_POST['Cho_Tsa']) ? $_POST['Cho_Tsa'] : '');
    $Vis_Tel = isset($_POST['Vis_Tel']) && $_POST['Vis_Tel'] !== '' ? trim($_POST['Vis_Tel']) : (isset($_POST['Cho_Tel']) ? trim($_POST['Cho_Tel']) : '');
    $Vis_Cor = isset($_POST['Vis_Cor']) && $_POST['Vis_Cor'] !== '' ? trim($_POST['Vis_Cor']) : (isset($_POST['Cho_Cor']) ? trim($_POST['Cho_Cor']) : '');
    $Vis_Dir = isset($_POST['Vis_Dir']) && $_POST['Vis_Dir'] !== '' ? addslashes($_POST['Vis_Dir']) : (isset($_POST['Cho_Dir']) ? addslashes($_POST['Cho_Dir']) : '');
    $Vis_Nem = isset($_POST['Vis_Nem']) && $_POST['Vis_Nem'] !== '' ? addslashes($_POST['Vis_Nem']) : (isset($_POST['Cho_Nem']) ? addslashes($_POST['Cho_Nem']) : '');
    $Vis_Tem = isset($_POST['Vis_Tem']) && $_POST['Vis_Tem'] !== '' ? trim($_POST['Vis_Tem']) : (isset($_POST['Cho_Tem']) ? trim($_POST['Cho_Tem']) : '');
    $Vis_Obs = isset($_POST['MVis_Obs']) ? addslashes($_POST['MVis_Obs']) : (isset($_POST['Vis_Obs']) ? addslashes($_POST['Vis_Obs']) : '');

    $baseDir = dirname(__DIR__) . '/RECURSOS/archivos_adjuntos/visitantes/';
    if (!file_exists($baseDir)) {
        @mkdir($baseDir, 0777, true);
    }
    $uploadDir = $baseDir . $Vis_Ced . '/';
    if (!file_exists($uploadDir)) {
        if (!@mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new Exception("No se pudo crear la carpeta de destino para los archivos ($uploadDir).");
        }
    }

    $debugDetails = array(
        'Vis_Ced' => $Vis_Ced,
        'Prs_Nom' => $Prs_Nom,
        'Prs_Ape' => $Prs_Ape,
        'Vis_Tel' => $Vis_Tel,
        'archivos_recibidos' => array()
    );

    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        // 1. Guardar o Actualizar Persona
        if (empty($Prs_Cod)) {
            $persona = $obBD_con1->getRowConsulta(6, array($Vis_Ced), $obBD_conexion);
            if (empty($persona)) {
                $datosPersona = array(
                    'Prs_Ced' => $Vis_Ced,
                    'Prs_Nom' => $Prs_Nom,
                    'Prs_Ape' => $Prs_Ape,
                    'Prs_Tel' => $Vis_Tel,
                    'Prs_Cor' => $Vis_Cor,
                    'Prs_Dir' => $Vis_Dir
                );
                if (!empty($Prs_Fec)) $datosPersona['Prs_Fec'] = $Prs_Fec;

                $obBD_con1->operacionobBD('persona.insert', $datosPersona, $obBD_conexion);
                if ($obBD_con1->Error != 0) {
                    $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                    throw new Exception("Error al guardar Persona: " . $errMsg);
                }
                $Prs_Cod = $obBD_con1->insercionid($obBD_conexion);
            } else {
                $Prs_Cod = $persona['Prs_Cod'];
            }
        }

        if (!empty($Prs_Cod)) {
            $datosPrs = array(
                'Prs_Ced' => $Vis_Ced,
                'Prs_Nom' => $Prs_Nom,
                'Prs_Ape' => $Prs_Ape,
                'Prs_Tel' => $Vis_Tel,
                'Prs_Cor' => $Vis_Cor,
                'Prs_Dir' => $Vis_Dir,
                'where'   => array('Prs_Cod' => $Prs_Cod)
            );
            if (!empty($Prs_Fec)) $datosPrs['Prs_Fec'] = $Prs_Fec;
            $obBD_con1->operacionobBD('persona.update', $datosPrs, $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al actualizar Persona: " . $errMsg);
            }
        }

        // 2. Procesar Archivos Adjuntos (acepta Vis_Doc_* y Cho_Doc_* por retrocompatibilidad)
        $uploadedFiles = array();
        $fileFields = array(
            'Vis_Doc_Ced'     => 'Cédula Anverso',
            'Vis_Doc_Ced_Rev' => 'Cédula Reverso',
            'Vis_Doc_Vot'     => 'Certificado Votación',
            'Vis_Doc_Fot'     => 'Foto Carnet',
            'Cho_Doc_Ced'     => 'Cédula Anverso',
            'Cho_Doc_Ced_Rev' => 'Cédula Reverso',
            'Cho_Doc_Vot'     => 'Certificado Votación',
            'Cho_Doc_Fot'     => 'Foto Carnet'
        );

        $maxAllowedBytes = 5 * 1024 * 1024; // 5 MB

        foreach ($fileFields as $field => $fieldLabel) {
            if (isset($_FILES[$field])) {
                $errCode = $_FILES[$field]['error'];
                if ($errCode === UPLOAD_ERR_OK) {
                    $tmpPath  = $_FILES[$field]['tmp_name'];
                    $origSize = $_FILES[$field]['size'];
                    $origName = $_FILES[$field]['name'];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    // Normalizar clave: siempre Vis_
                    $keyNorm = str_replace('Cho_', 'Vis_', $field);
                    $filename   = strtolower($keyNorm) . '.' . ($ext === 'pdf' ? 'pdf' : 'jpg');
                    $targetPath = $uploadDir . $filename;

                    if (in_array($ext, array('jpg', 'jpeg', 'png', 'webp'))) {
                        $compSuccess = optimizarYComprimirImagen($tmpPath, $targetPath, 1920, 85);
                        if (!$compSuccess || !file_exists($targetPath)) {
                            throw new Exception("No se pudo procesar la imagen para '$fieldLabel'.");
                        }
                        $finalSize = filesize($targetPath);
                        if ($finalSize > $maxAllowedBytes) {
                            @unlink($targetPath);
                            $finalMB = number_format($finalSize / (1024 * 1024), 2);
                            throw new Exception("La imagen de '$fieldLabel' ($finalMB MB) supera el límite máximo de 5.00 MB.");
                        }
                    } else if ($ext === 'pdf') {
                        if ($origSize > $maxAllowedBytes) {
                            $pdfMB = number_format($origSize / (1024 * 1024), 2);
                            throw new Exception("El archivo PDF para '$fieldLabel' ($pdfMB MB) supera el límite máximo de 5.00 MB.");
                        }
                        if (!move_uploaded_file($tmpPath, $targetPath)) {
                            throw new Exception("No se pudo subir el PDF de '$fieldLabel'.");
                        }
                    } else {
                        throw new Exception("Formato no válido para '$fieldLabel'.");
                    }
                    $uploadedFiles[$keyNorm] = '../RECURSOS/archivos_adjuntos/visitantes/' . $Vis_Ced . '/' . $filename;
                }
            }
        }

        // 3. Guardar o Actualizar Visitante
        $datosVisitante = array(
            'Prs_Cod'  => $Prs_Cod,
            'Emp_Cod'  => $Ses_Emp_Cod,
            'MVis_Nac' => $Vis_Nac,
            'MVis_Eci' => $Vis_Eci,
            'MVis_Tsa' => $Vis_Tsa,
            'MVis_Nem' => $Vis_Nem,
            'MVis_Tem' => $Vis_Tem,
            'MVis_Obs' => $Vis_Obs,
            'MVis_Est' => 'A'
        );

        if (empty($Man_Eve)) {
            try {
                $resEv = $obBD_con1->consulta("SELECT Man_Eve FROM manifiesto_evento WHERE UPPER(Man_Vig) = 'S' AND UPPER(Man_EEst) = 'A' LIMIT 1", $obBD_conexion->conexion);
                if ($resEv && ($rowEv = $obBD_con1->fetch_assoc($resEv))) {
                    $Man_Eve = $rowEv['Man_Eve'];
                }
            } catch (Exception $e) {}
        }
        if (!empty($Man_Eve)) {
            $datosVisitante['Man_Eve'] = $Man_Eve;

            // Validar que la fecha fin del evento no haya expirado (Permitido solo hasta el mismo día Man_EFef)
            $resEF = $obBD_con1->consulta("SELECT Man_ENom, Man_EFef, DATE_FORMAT(NOW(), '%Y-%m-%d') AS Hoy FROM manifiesto_evento WHERE Man_Eve = '$Man_Eve' LIMIT 1", $obBD_conexion->conexion);
            if ($resEF && ($rowEF = $obBD_con1->fetch_assoc($resEF))) {
                if (!empty($rowEF['Man_EFef']) && $rowEF['Hoy'] > $rowEF['Man_EFef']) {
                    $fefFmt = date('d/m/Y', strtotime($rowEF['Man_EFef']));
                    throw new Exception("No se pueden registrar visitantes en el evento '" . $rowEF['Man_ENom'] . "' debido a que su fecha fin (" . $fefFmt . ") ya ha expirado.");
                }
            }
        }

        if (isset($uploadedFiles['Vis_Doc_Ced']))     $datosVisitante['MVis_Doc_Ced']     = $uploadedFiles['Vis_Doc_Ced'];
        if (isset($uploadedFiles['Vis_Doc_Ced_Rev'])) $datosVisitante['MVis_Doc_Ced_Rev'] = $uploadedFiles['Vis_Doc_Ced_Rev'];
        if (isset($uploadedFiles['Vis_Doc_Vot']))     $datosVisitante['MVis_Doc_Vot']     = $uploadedFiles['Vis_Doc_Vot'];
        if (isset($uploadedFiles['Vis_Doc_Fot']))     $datosVisitante['MVis_Doc_Fot']     = $uploadedFiles['Vis_Doc_Fot'];

        // Herencia de fotos existentes si no se subió nueva foto
        $visExistGlobal = $obBD_con1->getRowConsulta(16, array($Ses_Emp_Cod, $Vis_Ced), $obBD_conexion);
        $camposFotos = array(
            'MVis_Doc_Ced'     => 'MVis_Doc_Ced',
            'MVis_Doc_Ced_Rev' => 'MVis_Doc_Ced_Rev',
            'MVis_Doc_Vot'     => 'MVis_Doc_Vot',
            'MVis_Doc_Fot'     => 'MVis_Doc_Fot'
        );
        foreach ($camposFotos as $targetKey => $sourceKey) {
            if (!isset($datosVisitante[$targetKey]) || empty($datosVisitante[$targetKey])) {
                if (!empty($visExistGlobal) && !empty($visExistGlobal[$sourceKey])) {
                    $datosVisitante[$targetKey] = $visExistGlobal[$sourceKey];
                }
            }
        }

        if (!empty($MVis_Cod)) {
            $datosVisitante['where'] = array('MVis_Cod' => $MVis_Cod);
            $obBD_con1->operacionobBD('manifiesto_visitante.update', $datosVisitante, $obBD_conexion);
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al actualizar Visitante: " . $errMsg);
            }
        } else {
            $visExistEnEvento = null;
            if (!empty($Man_Eve)) {
                $visExistEnEvento = $obBD_con1->getRowConsulta(20, array($Ses_Emp_Cod, $Vis_Ced, $Man_Eve), $obBD_conexion);
            }
            if (!empty($visExistEnEvento)) {
                $datosVisitante['where'] = array('MVis_Cod' => $visExistEnEvento['MVis_Cod']);
                $obBD_con1->operacionobBD('manifiesto_visitante.update', $datosVisitante, $obBD_conexion);
            } else {
                $obBD_con1->operacionobBD('manifiesto_visitante.insert', $datosVisitante, $obBD_conexion);
            }
            if ($obBD_con1->Error != 0) {
                $errMsg = !empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : ("Error Cód: " . $obBD_con1->Error);
                throw new Exception("Error al guardar Visitante: " . $errMsg);
            }
        }

        $obBD_con1->Error = 0;
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        $resp['success'] = true;
        $resp['message'] = 'Visitante guardado correctamente';
        $resp['debug_info'] = $debugDetails;

        // Envío automático opcional de WhatsApp de Bienvenida / Confirmación de Registro
        $telNotif = !empty($Vis_Tel) ? trim($Vis_Tel) : (!empty($Vis_Tem) ? trim($Vis_Tem) : '');
        if (!empty($telNotif) && !empty($Man_Eve)) {
            try {
                $evDataNotif = man_cert_asistencia_resolver_evento($obBD_con1, $obBD_conexion, $Man_Eve);
                $nomEv = !empty($evDataNotif['nombre']) ? $evDataNotif['nombre'] : 'Evento';
                $nomCompleto = trim($Prs_Nom . ' ' . $Prs_Ape);
                $telWa = relavera_whatsapp_normalizar_numero_ec($telNotif);

                if (!empty($telWa)) {
                    $msgCustom = !empty($evDataNotif['mensaje_whatsapp']) ? trim($evDataNotif['mensaje_whatsapp']) : '';
                    if ($msgCustom !== '') {
                        $cuerpo = str_replace(
                            array('{nombre}', '{cedula}', '{evento}', '{proyecto}'),
                            array($nomCompleto, $Vis_Ced, $nomEv, 'Relavera Comunitaria "El Tablón"'),
                            $msgCustom
                        );
                        if (strpos($cuerpo, '👋') !== false || strpos($cuerpo, '🏢') !== false || strpos($cuerpo, "\n") !== false) {
                            $msgWa = $cuerpo;
                        } else {
                            $msgWa = "¡Hola *" . $nomCompleto . "*! 👋\n\n"
                                . $cuerpo . "\n\n"
                                . "🏢 Proyecto: *Relavera Comunitaria \"El Tablón\"* - ECOPARKMINING S.A.\n\n"
                                . "¡Gracias por tu participación! ✨";
                        }
                    } else {
                        $msgWa = "¡Hola *" . $nomCompleto . "*! 👋\n\n"
                            . "Te has registrado exitosamente en el evento *\"" . $nomEv . "\"*.\n"
                            . "Esperando que sea de su agrado y que disfrute al máximo.\n\n"
                            . "🏢 Proyecto: *Relavera Comunitaria \"El Tablón\"* - ECOPARKMINING S.A.\n\n"
                            . "¡Gracias por tu participación! ✨";
                    }
                    relavera_enviar_whatsapp_notif($telWa, $msgWa);
                }
            } catch (Exception $eNotif) {
                // Silencioso para no afectar la respuesta del guardado si falla el webhook
            }
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = $e->getMessage();
    }
    responderJsonLimpio($resp);
}

// 6. Anular Visitante
if (isset($_POST['anularVisitanteAjax'])) {
    $resp = array('success' => false);
    $MVis_Cod = isset($_POST['MVis_Cod']) ? $_POST['MVis_Cod'] : (isset($_POST['Vis_Cod']) ? $_POST['Vis_Cod'] : '');
    if (!empty($MVis_Cod)) {
        $obBD_con1->operacionobBD('manifiesto_visitante.update', array('MVis_Est' => 'I', 'where' => array('MVis_Cod' => $MVis_Cod)), $obBD_conexion);
        $resp['success'] = ($obBD_con1->Error == 0);
    }
    responderJsonLimpio($resp);
}

// 7. Enviar certificado PDF a visitante del evento (WhatsApp y/o correo)
if (isset($_POST['enviarCertificadoVisitanteEventoAjax'])) {
    @set_time_limit(120);
    $resp = array(
        'success'           => false,
        'message'           => '',
        'whatsapp'          => false,
        'correo'            => false,
        'omitido_whatsapp'  => false,
        'omitido_correo'    => false,
        'debug_info'        => array()
    );

    try {
        $MVis_Cod = isset($_POST['MVis_Cod']) ? (int)$_POST['MVis_Cod'] : 0;
        $canal = isset($_POST['canal']) ? strtolower(trim((string) $_POST['canal'])) : 'ambos';
        if (!in_array($canal, array('whatsapp', 'correo', 'ambos'), true)) {
            $canal = 'ambos';
        }

        $resp['debug_info']['MVis_Cod_recibido'] = $MVis_Cod;
        $resp['debug_info']['canal'] = $canal;

        if ($MVis_Cod <= 0) {
            throw new Exception('No se recibió el código del visitante.');
        }

        $visitante = $obBD_con1->getRowConsulta(17, array($MVis_Cod), $obBD_conexion);
        if (empty($visitante)) {
            throw new Exception('No se encontró el visitante indicado.');
        }
        $obBD_con1->utf8_change_param($visitante);

        $prsNom = isset($visitante['Prs_Nom']) ? trim((string) $visitante['Prs_Nom']) : '';
        $prsApe = isset($visitante['Prs_Ape']) ? trim((string) $visitante['Prs_Ape']) : '';
        $nombre = isset($visitante['nombre']) ? trim((string) $visitante['nombre']) : '';
        if ($nombre === '') {
            $nombre = trim($prsNom . ' ' . $prsApe);
        }
        $cedula = isset($visitante['Prs_Ced']) ? trim((string) $visitante['Prs_Ced']) : '';

        $telefono = '';
        if (!empty($visitante['Prs_Tel_Base'])) {
            $telefono = trim((string) $visitante['Prs_Tel_Base']);
        } elseif (!empty($visitante['MVis_Tem'])) {
            $telefono = trim((string) $visitante['MVis_Tem']);
        }

        $correo = isset($visitante['Prs_Cor']) ? trim((string) $visitante['Prs_Cor']) : '';
        $correoValido = ($correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL));
        if (!$correoValido) {
            $correo = '';
        }

        $resp['debug_info']['nombre']          = $nombre;
        $resp['debug_info']['cedula']          = $cedula;
        $resp['debug_info']['telefono_raw']    = $telefono;
        $resp['debug_info']['correo_raw']      = isset($visitante['Prs_Cor']) ? trim((string) $visitante['Prs_Cor']) : '';
        $resp['debug_info']['correo_valido']   = $correoValido;

        $manEve = isset($visitante['Man_Eve']) ? trim((string) $visitante['Man_Eve']) : '';
        if ($manEve === '' && !empty($_POST['Man_Eve'])) {
            $manEve = trim((string) $_POST['Man_Eve']);
        }
        $evData = man_cert_asistencia_resolver_evento($obBD_con1, $obBD_conexion, $manEve !== '' ? $manEve : null);
        $nombreEvento = $evData['nombre'];
        $resp['debug_info']['Man_Eve'] = $manEve;
        $resp['debug_info']['nombreEvento'] = $nombreEvento;

        $pdfParams = man_cert_asistencia_armar_params($prsNom, $prsApe, $cedula, $evData);
        $pdfPath = man_cert_asistencia_generar_pdf($pdfParams);
        if ($pdfPath === false || !is_file($pdfPath)) {
            throw new Exception('No se pudo generar el PDF del certificado.');
        }
        $resp['debug_info']['pdf_generado'] = true;
        $resp['debug_info']['pdf_path']     = $pdfPath;

        $pdfBase64 = base64_encode(file_get_contents($pdfPath));
        $pdfNombre = 'Certificado_Asistencia_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $cedula !== '' ? $cedula : 'visitante') . '.pdf';

        $lineas   = array();
        $lineas[] = '¡Hola ' . ($nombre !== '' ? '*' . $nombre . '*' : '') . '! 👋';
        $lineas[] = 'Gracias por su participación. Adjuntamos su certificado en formato PDF.';
        $lineas[] = '';
        $lineas[] = '🏢 Proyecto: *Relavera Comunitaria "El Tablón"* - ECOPARKMINING S.A.';
        if ($nombreEvento !== '') {
            $lineas[] = '📌 Evento: *' . $nombreEvento . '*';
        }
        $mensaje = implode("\n", $lineas);
        $asunto  = 'Certificado de Asistencia' . ($nombreEvento !== '' ? ' - ' . $nombreEvento : ' - ECOPARKMINING');

        $detalle   = array();
        $enviarWa  = ($canal === 'whatsapp' || $canal === 'ambos');
        $enviarMail= ($canal === 'correo'   || $canal === 'ambos');

        if ($enviarWa) {
            $telWa = relavera_whatsapp_normalizar_numero_ec($telefono);
            $resp['debug_info']['telefono_normalizado_wa'] = $telWa;
            if ($telWa === '') {
                $resp['omitido_whatsapp'] = true;
                $detalle[] = 'WhatsApp omitido (sin teléfono válido)';
            } else {
                $okWa = relavera_enviar_whatsapp_documento_notif($telWa, $pdfBase64, $pdfNombre, $mensaje);
                $resp['whatsapp'] = (bool) $okWa;
                $resp['debug_info']['whatsapp_resultado'] = $okWa;
                $detalle[] = $okWa ? 'WhatsApp enviado (PDF)' : 'WhatsApp falló';
            }
        }

        if ($enviarMail) {
            if ($correo === '') {
                $resp['omitido_correo'] = true;
                $detalle[] = 'Correo omitido (sin email válido)';
            } else {
                $okMail = relavera_notif_enviar_correo_notif(
                    $correo,
                    $nombre,
                    $asunto,
                    $mensaje,
                    null,
                    array('ruta' => $pdfPath, 'nombre' => $pdfNombre)
                );
                $resp['correo'] = (bool) $okMail;
                $resp['debug_info']['correo_resultado'] = $okMail;
                $detalle[] = $okMail ? 'Correo enviado (PDF adjunto)' : 'Correo falló';
            }
        }

        @unlink($pdfPath);

        $resp['success'] = ($resp['whatsapp'] || $resp['correo'] || $resp['omitido_whatsapp'] || $resp['omitido_correo']);
        if ($resp['whatsapp'] || $resp['correo']) {
            @$obBD_con1->consulta("UPDATE manifiesto_visitante SET MVis_Cer_Env = 'S' WHERE MVis_Cod = $MVis_Cod", $obBD_conexion->conexion);
            $resp['message'] = 'Certificado PDF notificado: ' . implode('. ', $detalle) . '.';
        } else {
            $resp['message'] = 'No se pudo enviar el PDF. ' . implode('. ', $detalle) . '.';
        }
    } catch (Exception $e) {
        $resp['success'] = false;
        $resp['message'] = $e->getMessage();
    }
    responderJsonLimpio($resp);
}

// 8. Obtener datos del evento para envío masivo (mensaje y delay)
if (isset($_POST['getDatosEventoEnvioMasivoAjax'])) {
    $resp = array('success' => false, 'Man_Mmsg' => '', 'Man_Mdel' => 5, 'Man_ENom' => '');
    try {
        $manEve = isset($_POST['Man_Eve']) ? trim((string)$_POST['Man_Eve']) : '';
        $evData = man_cert_asistencia_resolver_evento($obBD_con1, $obBD_conexion, $manEve);
        $resp['success'] = true;
        $resp['Man_ENom'] = isset($evData['nombre']) ? $evData['nombre'] : '';
        $resp['Man_Mmsg'] = isset($evData['mensaje_masivo']) ? $evData['mensaje_masivo'] : '';
        $resp['Man_Mdel'] = (isset($evData['intervalo_cola']) && (int)$evData['intervalo_cola'] > 0) ? (int)$evData['intervalo_cola'] : 5;
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    responderJsonLimpio($resp);
}

// 9. Enviar mensaje masivo a un visitante individual (llamado en cola secuencial)
if (isset($_POST['enviarMensajeMasivoVisitanteAjax'])) {
    $resp = array('success' => false, 'message' => '');
    try {
        $MVis_Cod = isset($_POST['MVis_Cod']) ? (int)$_POST['MVis_Cod'] : 0;
        $manEve = isset($_POST['Man_Eve']) ? trim((string)$_POST['Man_Eve']) : '';

        if ($MVis_Cod <= 0) {
            throw new Exception('Código de visitante inválido.');
        }

        $visitante = $obBD_con1->getRowConsulta(17, array($MVis_Cod), $obBD_conexion);
        if (empty($visitante)) {
            throw new Exception('No se encontró el registro del visitante.');
        }
        $obBD_con1->utf8_change_param($visitante);

        $prsNom = isset($visitante['Prs_Nom']) ? trim((string)$visitante['Prs_Nom']) : '';
        $prsApe = isset($visitante['Prs_Ape']) ? trim((string)$visitante['Prs_Ape']) : '';
        $nombre = isset($visitante['nombre']) ? trim((string)$visitante['nombre']) : trim($prsNom . ' ' . $prsApe);
        $cedula = isset($visitante['Prs_Ced']) ? trim((string)$visitante['Prs_Ced']) : '';

        $telefono = '';
        if (!empty($visitante['Prs_Tel_Base'])) {
            $telefono = trim((string)$visitante['Prs_Tel_Base']);
        } elseif (!empty($visitante['Prs_Tel'])) {
            $telefono = trim((string)$visitante['Prs_Tel']);
        } elseif (!empty($visitante['MVis_Tem'])) {
            $telefono = trim((string)$visitante['MVis_Tem']);
        }

        if (empty($telefono)) {
            throw new Exception("El visitante '$nombre' no tiene teléfono registrado.");
        }

        $telWa = relavera_whatsapp_normalizar_numero_ec($telefono);
        if (empty($telWa)) {
            throw new Exception("El teléfono '$telefono' no es un número de WhatsApp válido.");
        }

        $evData = man_cert_asistencia_resolver_evento($obBD_con1, $obBD_conexion, !empty($manEve) ? $manEve : (isset($visitante['Man_Eve']) ? $visitante['Man_Eve'] : null));

        $plantilla = !empty($evData['mensaje_masivo']) ? $evData['mensaje_masivo'] : '';
        if (empty($plantilla)) {
            $plantilla = "¡Hola *{nombre}*! 👋\n\nTe recordamos que el evento *\"{evento}\"* se llevará a cabo el día *{fecha}*.\n\n🏢 Proyecto: *{proyecto}* - ECOPARKMINING S.A.\n\n¡Te esperamos puntualmente!";
        }

        $reemplazos = array(
            '{nombre}'   => $nombre,
            '{cedula}'   => $cedula,
            '{evento}'   => isset($evData['nombre']) ? $evData['nombre'] : 'Evento',
            '{fecha}'    => isset($evData['fecha_texto']) ? $evData['fecha_texto'] : date('d/m/Y'),
            '{horas}'    => isset($evData['horas']) ? $evData['horas'] : '6',
            '{proyecto}' => 'Relavera Comunitaria "El Tablón"'
        );

        $msgFinal = str_replace(array_keys($reemplazos), array_values($reemplazos), $plantilla);

        $okWa = relavera_enviar_whatsapp_notif($telWa, $msgFinal);
        if (!$okWa) {
            throw new Exception("Error al enviar mensaje por API de WhatsApp a $nombre ($telWa).");
        }

        $resp['success'] = true;
        $resp['message'] = "Mensaje enviado exitosamente a $nombre ($telWa).";
    } catch (Exception $e) {
        $resp['success'] = false;
        $resp['message'] = $e->getMessage();
    }
    responderJsonLimpio($resp);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Visitantes</title>
    <!-- Framework & CSS Requirements -->
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <?php require_once("../../mascaras/model3/estilos/estilos.php") ?>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/visitantes.css">
</head>

<body>
    <div class="panel panel-default panel-main exa-ui-panel">
        <!-- Encabezado Estilo Model3 -->
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><span class="glyphicon glyphicon-calendar"></span> Datos de Eventos y Visitantes</h3>
        </div>

        <div class="panel-body exa-body">
            <!-- Pestañas (Tabs) -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tabEventos" aria-controls="tabEventos" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-calendar icon-tab"></i>Eventos y Visitantes
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- ==================== TAB EVENTOS ==================== -->
                    <div role="tabpanel" class="tab-pane active" id="tabEventos">
                        <div class="row vis-tab-row">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Personas / Visitantes del evento</legend>
                                    <form id="filtroVisitantesEventoForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridVisitantesEvento();">
                                        <!-- Fila Superior: Filtrar Por a la izquierda (encima de Búsqueda) y Evento a la derecha -->
                                        <div class="vis-filter-row">
                                            <div class="vis-filter-group">
                                                <label class="control-label label-xs vis-filter-label">Filtrar Por:</label>
                                                <div class="radioset opt_search">
                                                    <input id="radVisEve1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" />
                                                    <label for="radVisEve1">Nombre</label>
                                                    <input id="radVisEve2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                    <label for="radVisEve2">Cédula</label>
                                                </div>
                                            </div>
                                            <div class="vis-filter-group">
                                                <label class="control-label label-xs vis-filter-label-inline">Evento:</label>
                                                <div class="vis-evento-select-wrap">
                                                    <select id="selMan_Eve" name="Man_Eve" class="form-control input-xs select-wide chosen-select vis-input-sm" data-placeholder="Todos los eventos..." onchange="actualizarGridVisitantesEvento();">
                                                        <option value="">— Todos los eventos —</option>
                                                        <?php
                                                        $eventoPreseleccionado = !empty($idEventoVigente) ? $idEventoVigente : '';
                                                        foreach ($eventosCatalogo as $evRow) {
                                                            $evId = isset($evRow['Man_Eve']) ? $evRow['Man_Eve'] : '';
                                                            if ($evId === '' || $evId === null) {
                                                                continue;
                                                            }
                                                            $evNom = isset($evRow['Man_ENom']) ? $evRow['Man_ENom'] : ('Evento #' . $evId);
                                                            $evFei = !empty($evRow['Man_EFei']) ? $evRow['Man_EFei'] : '';
                                                            $evFef = !empty($evRow['Man_EFef']) ? $evRow['Man_EFef'] : '';
                                                            $evVig = (isset($evRow['Man_Vig']) && strtoupper($evRow['Man_Vig']) === 'S') ? ' [VIGENTE]' : '';
                                                            $label = $evNom;
                                                            if ($evFei !== '') {
                                                                $label .= ' (' . $evFei . ')';
                                                            }
                                                            $label .= $evVig;
                                                            $sel = ((string)$eventoPreseleccionado !== '' && (string)$eventoPreseleccionado === (string)$evId) ? ' selected' : '';
                                                            echo '<option value="' . htmlspecialchars($evId, ENT_QUOTES, 'UTF-8') . '" data-fef="' . htmlspecialchars($evFef, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fila Inferior: Búsqueda a la izquierda y Registrar Visitante a la derecha -->
                                        <div class="vis-filter-row-bottom">
                                            <div class="vis-filter-group-grow">
                                                <label class="control-label label-xs vis-filter-label">Búsqueda:</label>
                                                <div class="vis-busqueda-wrap">
                                                    <div class="input-group">
                                                        <input name="search" type="text" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control clearable vis-input-sm" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridVisitantesEvento(); }" />
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="actualizarGridVisitantesEvento();" class="btn btn-success vis-btn-sm" title="Buscar personas">
                                                                <span class="glyphicon glyphicon-search"></span> Buscar
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="vis-btn-registrar-wrap" style="display: flex; gap: 5px;">
                                                <!--
                                                <button id="btnEnvioMasivoTop" class="btn btn-primary vis-btn-registrar" type="button" onclick="abrirModalEnvioMasivo();" style="background-color: #2563eb; border-color: #1d4ed8;" title="Enviar mensaje de difusión por WhatsApp a los seleccionados">
                                                    <i class="glyphicon glyphicon-bullhorn"></i> Envío Masivo
                                                </button>
                                                -->
                                                <button id="btnAbrirModalRegistrar" class="btn btn-success vis-btn-registrar" type="button" onclick="abrirModalVisitante();">
                                                    <i class="glyphicon glyphicon-plus"></i> Registrar Visitante
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <div class="exa-ui-grid-host">
                            <table id="gridVisitantesEvento"></table>
                            <div id="gridVisitantesEventoPager"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL REGISTRAR / EDITAR VISITANTE ==================== -->
    <div id="visitanteDialog" title="Registrar Visitante en Evento" style="display: none;">
        <form id="visitanteForm" class="form-horizontal normal" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" id="Vis_Cod" name="Vis_Cod">
            <input type="hidden" id="MVis_Cod" name="MVis_Cod">
            <input type="hidden" id="Prs_Cod" name="Prs_Cod">
            <input type="hidden" id="Man_Eve" name="Man_Eve">
            <input type="hidden" id="chk_es_visitante" name="es_visitante" value="1">

            <!-- BANNER EVENTO EN VIGENCIA -->
            <div class="row vis-modal-header-row">
                <div class="col-xs-12 text-left">
                    <?php if (!empty($nombreEventoVigente)) { ?>
                        <span id="lbl_evento_vigente" class="label label-info vis-label-evento" style="display:inline-block; background-color:#0284c7; color:#fff;" title="Evento en vigencia activo">
                            <i class="glyphicon glyphicon-bullhorn"></i> Evento "<strong><?php echo htmlspecialchars($nombreEventoVigente); ?></strong>" En Vigencia
                        </span>
                        <input type="hidden" id="hdn_man_eve_vigente" value="<?php echo $idEventoVigente; ?>">
                        <input type="hidden" id="hdn_man_eve_vigente_fef" value="<?php echo htmlspecialchars($fefEventoVigente, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php } else { ?>
                        <span id="lbl_evento_vigente" class="label label-default vis-label-evento" style="display:inline-block; background-color:#64748b; color:#fff;">
                            <i class="glyphicon glyphicon-calendar"></i> Sin Evento En Vigencia
                        </span>
                        <input type="hidden" id="hdn_man_eve_vigente" value="">
                        <input type="hidden" id="hdn_man_eve_vigente_fef" value="">
                    <?php } ?>
                </div>
            </div>

            <!-- BLOQUE 1: IDENTIFICACIÓN PERSONAL -->
            <div class="row">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-user"></i> 1. Identificación Personal</legend>
                        <div class="row">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Cédula / RUC / Pasaporte / Documento de Identidad">Cédula / Doc. Id:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Vis_Ced" name="Vis_Ced" class="form-control input-xs" required placeholder="N° Identificación" maxlength="20" onchange="buscarPersonaCedula(this.value);">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Nombres del Visitante">Nombres:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" required placeholder="Nombres completos">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Apellidos del Visitante">Apellidos:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" required placeholder="Apellidos completos">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Fecha Nacimiento">Fec. Nacimiento:</label>
                                    <div class="col-xs-12">
                                        <input type="date" id="Prs_Fec" name="Prs_Fec" class="form-control input-xs input-date-wide" min="1940-01-01" max="2010-12-31" onchange="calcularEdad(this.value);">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row vis-row-mt">
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Edad calculada">Edad:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Vis_Edad" class="form-control input-xs bold text-center" readonly placeholder="-">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Nacionalidad del Visitante">Nacionalidad:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Vis_Nac" name="Vis_Nac" class="form-control input-xs" value="Ecuatoriana">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Estado Civil">Estado Civil:</label>
                                    <div class="col-xs-12">
                                        <select id="Vis_Eci" name="Vis_Eci" class="form-control input-xs select-wide chosen-select">
                                            <option value="Soltero/a">Soltero/a</option>
                                            <option value="Casado/a">Casado/a</option>
                                            <option value="Divorciado/a">Divorciado/a</option>
                                            <option value="Viudo/a">Viudo/a</option>
                                            <option value="Unión de Hecho">Unión de Hecho</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Teléfono Celular Personal">Celular Personal:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Vis_Tel" name="Vis_Tel" class="form-control input-xs" required placeholder="0991234567" maxlength="20">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- BLOQUE 2: CONTACTOS E INFORMACIÓN MÉDICA -->
            <div class="row vis-row-mt-6">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"><i class="glyphicon glyphicon-phone"></i> 2. Contactos e Información Médica</legend>
                        <div class="row">
                            <div class="col-xs-3">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs required" title="Tipo de Sangre del Visitante">Tipo Sangre:</label>
                                    <div class="col-xs-12">
                                        <select id="Vis_Tsa" name="Vis_Tsa" class="form-control input-xs select-wide chosen-select" required>
                                            <option value="">Seleccione...</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Correo Electrónico Personal">Correo Electrónico:</label>
                                    <div class="col-xs-12">
                                        <input type="email" id="Vis_Cor" name="Vis_Cor" class="form-control input-xs" placeholder="correo@ejemplo.com">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-5">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Dirección Domiciliaria de Residencia">Dirección Domiciliaria:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Vis_Dir" name="Vis_Dir" class="form-control input-xs" placeholder="Dirección residencia">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row vis-row-mt">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Nombre del Contacto de Emergencia">Contacto Emergencia:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Vis_Nem" name="Vis_Nem" class="form-control input-xs" placeholder="Nombre contacto emergencia">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Teléfono del Contacto de Emergencia">Teléfono Emergencia:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="Vis_Tem" name="Vis_Tem" class="form-control input-xs" placeholder="Teléfono emergencia" maxlength="20">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row vis-row-mt">
                            <div class="col-xs-12">
                                <div class="form-group">
                                    <label class="col-xs-12 control-label label-xs" title="Observaciones Adicionales">Observaciones:</label>
                                    <div class="col-xs-12">
                                        <input type="text" id="MVis_Obs" name="MVis_Obs" class="form-control input-xs" placeholder="Observaciones generales o motivo de la visita">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

        </form>

        <div class="vis-modal-footer">
            <button id="btnGuardarVisitante" class="btn btn-primary" type="button" onclick="guardarVisitante();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Visitante</button>
            <button class="btn btn-danger" type="button" onclick="$('#visitanteDialog').dialog('close');" style="margin-left:5px;"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Previsualizador de Documentos y Fotos -->
    <div id="previewDocModal" title="Previsualización de Documento" style="display: none;">
        <div id="previewDocContent" class="vis-preview-content">
            <img id="previewDocImg" src="" alt="Vista previa de foto" class="vis-preview-img" />
            <iframe id="previewDocPdf" src="" class="vis-preview-iframe"></iframe>
        </div>
        <div class="vis-preview-footer">
            <button class="btn btn-sm btn-danger" type="button" onclick="$('#previewDocModal').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
        </div>
    </div>

    <!-- Diálogo de Alerta UI Model3 -->
    <div id="alertCustomDialog" title="Notificación" style="display: none;">
        <div class="vis-alert-body">
            <span id="alertCustomIcon" class="glyphicon glyphicon-info-sign vis-alert-icon"></span>
            <div id="alertCustomMessage" class="vis-alert-message"></div>
        </div>
    </div>

    <!-- Diálogo / Barra de Progreso para Envío Masivo -->
    <div id="progresoEnvioMasivoDialog" title="Envío Masivo WhatsApp" style="display: none;">
        <div style="padding: 15px 10px;">
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <span id="txtProgresoMasivoContador" style="font-weight: bold; font-size: 13px; color: #1e3a8a;">
                    <i class="glyphicon glyphicon-bullhorn"></i> Procesando cola de envíos...
                </span>
                <span id="txtProgresoMasivoPorcentaje" class="label label-primary" style="font-size: 12px; padding: 4px 8px; background-color: #2563eb;">
                    0%
                </span>
            </div>

            <!-- Barra de Progreso Bootstrap -->
            <div class="progress" style="height: 22px; margin-bottom: 15px; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); background-color: #e2e8f0;">
                <div id="barProgresoMasivo" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; line-height: 22px; font-weight: bold; font-size: 11px; background-color: #2563eb;">
                    0%
                </div>
            </div>

            <!-- Estado actual del envío -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; margin-bottom: 12px;">
                <div style="font-size: 11px; color: #475569; margin-bottom: 4px;">
                    <b>Destinatario actual:</b> <span id="txtProgresoMasivoDestinatario" style="color: #0f172a; font-weight: bold;">Iniciando...</span>
                </div>
                <div style="font-size: 11px; color: #059669; font-weight: bold;">
                    <span id="txtProgresoMasivoEstado"><i class="glyphicon glyphicon-refresh spin"></i> Preparando primer mensaje...</span>
                </div>
            </div>

            <!-- Contador de Pausa / Countdown -->
            <div id="boxProgresoMasivoTimer" style="text-align: center; margin-bottom: 15px; display: none;">
                <span class="label label-warning" style="font-size: 11px; padding: 4px 12px; background-color: #f59e0b;">
                    <i class="glyphicon glyphicon-time"></i> <span id="txtProgresoMasivoCountdown">Pausa de seguridad: 5s</span>
                </span>
            </div>

            <!-- Resumen de envíos -->
            <div style="display: flex; justify-content: space-around; font-size: 11px; color: #64748b; margin-bottom: 10px; background: #fff; border: 1px dashed #cbd5e1; padding: 6px; border-radius: 4px;">
                <span>Exitosos: <b id="cntMasivoExitos" style="color: #16a34a;">0</b></span>
                <span>Fallidos: <b id="cntMasivoFallos" style="color: #dc2626;">0</b></span>
                <span>Pendientes: <b id="cntMasivoPendientes" style="color: #2563eb;">0</b></span>
            </div>
        </div>

        <div style="text-align: right; border-top: 1px solid #e5e7eb; padding-top: 10px;">
            <button id="btnPausarEnvioMasivo" type="button" class="btn btn-warning btn-sm" onclick="togglePausarColaEnvioMasivo();" style="margin-right: 5px;">
                <i class="glyphicon glyphicon-pause"></i> Pausar
            </button>
            <button id="btnCancelarEnvioMasivo" type="button" class="btn btn-danger btn-sm" onclick="cancelarColaEnvioMasivo();">
                <i class="glyphicon glyphicon-stop"></i> Detener
            </button>
        </div>
    </div>

    <!-- JS Scripts -->
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/man_val_visitantes.js?e=10"></script>
</body>

</html>

<!-- Cierre de conexiones y liberacion de memoria -->
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>