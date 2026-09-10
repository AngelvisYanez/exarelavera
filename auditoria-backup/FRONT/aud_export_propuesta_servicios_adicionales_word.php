<?php
/**
 * Exportación Word - Propuesta de Servicios Adicionales
 * POST: Con_Cod, servicios_seleccionados (JSON array de { Act_Cod, Act_Nombre, Ser_Nombre, Precio })
 */
ob_start();
require_once(__DIR__ . '/../../administrador/LOGICA/seguridad.php');
require_once(__DIR__ . '/../LOGICA/aud_log_despacho_1.0.php');
require_once(__DIR__ . '/../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;
$nombreDespacho = (isset($Ses_Emp_Nom) && trim($Ses_Emp_Nom) !== '') ? trim($Ses_Emp_Nom) : (isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'DESPACHO CONTABLE');

$Con_Cod = isset($_POST['Con_Cod']) ? intval($_POST['Con_Cod']) : 0;
$jsonSeleccionados = isset($_POST['servicios_seleccionados']) ? trim($_POST['servicios_seleccionados']) : '';
$seleccionados = array();
if ($jsonSeleccionados !== '') {
    $dec = json_decode($jsonSeleccionados, true);
    if (is_array($dec)) $seleccionados = $dec;
}

if ($Con_Cod <= 0) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Contrato no indicado.'));
    exit;
}

$rowCliente = $obBD_con1->getRowConsulta(87, array('Con_Cod' => $Con_Cod), $obBD_conexion);
$clienteNombre = isset($rowCliente['Cliente_Nombre']) ? trim($rowCliente['Cliente_Nombre']) : '';
$clienteRuc = isset($rowCliente['RUC']) ? trim($rowCliente['RUC']) : '';

$repLegalNombre = '';
$repLegalDoc = '';
if ($Ses_Emp_Cod > 0) {
    $obBD_con1->setError(0, '');
    $rowRep = $obBD_con1->getRowConsulta(88, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (!empty($rowRep) && $obBD_con1->Error == 0) {
        $repLegalNombre = isset($rowRep['Representante_Nombre']) ? trim($rowRep['Representante_Nombre']) : '';
        $repLegalDoc = isset($rowRep['Representante_Identificacion']) ? trim($rowRep['Representante_Identificacion']) : '';
    }
}

$actividadesContrato = $obBD_con1->getArrayConsulta(16, array('Con_Cod' => $Con_Cod), $obBD_conexion);
if (!is_array($actividadesContrato)) $actividadesContrato = array();
try {
    $actividadesPrecios = $obBD_con1->getArrayConsulta(67, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
} catch (Exception $e) {
    $actividadesPrecios = array();
}
if (!is_array($actividadesPrecios)) $actividadesPrecios = array();

$actIdsEnContrato = array();
foreach ($actividadesContrato as $a) $actIdsEnContrato[(int)$a['Act_Cod']] = true;

$porServicio = array();
$norm = function ($v) { return trim((string)(isset($v) ? $v : '')); };
foreach ($actividadesContrato as $a) {
    $serNom = $norm($a['Ser_Nombre']) !== '' ? $norm($a['Ser_Nombre']) : 'Otros';
    if (!isset($porServicio[$serNom])) $porServicio[$serNom] = array('incluidas' => array(), 'noIncluidas' => array());
    $nom = $norm($a['Act_Nombre']);
    if ($nom !== '') $porServicio[$serNom]['incluidas'][] = $nom;
}
foreach ($actividadesPrecios as $a) {
    $serNom = $norm($a['Ser_Nombre']) !== '' ? $norm($a['Ser_Nombre']) : 'Otros';
    if (!isset($porServicio[$serNom])) $porServicio[$serNom] = array('incluidas' => array(), 'noIncluidas' => array());
    $enContrato = isset($actIdsEnContrato[(int)$a['Act_Cod']]);
    $nom = $norm($a['Act_Nombre']);
    if ($enContrato) {
        if ($nom !== '' && !in_array($nom, $porServicio[$serNom]['incluidas'])) $porServicio[$serNom]['incluidas'][] = $nom;
    } else {
        if ($nom !== '') $porServicio[$serNom]['noIncluidas'][] = $nom;
    }
}

$fechaStr = date('d/m/Y');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$html = '<!DOCTYPE html><html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="UTF-8"><meta name=ProgId content=Word.Document></head><body style="font-family: Arial, sans-serif; font-size: 11pt;">';

$html .= '<div style="text-align: center; color: #2C5D94; font-weight: bold; font-size: 14pt; margin-bottom: 6px;">' . h($nombreDespacho) . '</div>';
$html .= '<div style="text-align: center; font-weight: bold; font-size: 12pt; margin-bottom: 12px;">PROPUESTA DE SERVICIOS ADICIONALES</div>';
$html .= '<p><strong>Cliente:</strong> ' . h($clienteNombre) . '</p>';
$html .= '<p><strong>RUC:</strong> ' . h($clienteRuc) . '</p>';
$html .= '<p><strong>Fecha:</strong> ' . h($fechaStr) . '</p>';
$html .= '<p style="margin-top: 16px;"><strong style="color: #2C5D94;">1. SERVICIOS CONTRATADOS VS SERVICIOS ADICIONALES</strong></p>';
$html .= '<p>A continuación se detalla la comparación entre los servicios incluidos en su contrato actual y los servicios adicionales disponibles:</p>';

$html .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 9pt;">';
$html .= '<thead><tr style="background: #2C5D94; color: white;"><th style="width: 22%; text-align: left;">CATEGORÍA</th><th style="width: 39%; text-align: left;">INCLUIDAS EN CONTRATO</th><th style="width: 39%; text-align: left;">NO INCLUIDAS</th></tr></thead><tbody>';
ksort($porServicio);
foreach ($porServicio as $serNom => $inv) {
    $inclList = array_filter($inv['incluidas']);
    $noInclList = array_filter($inv['noIncluidas']);
    $incl = $inclList ? implode('<br/>', array_map(function ($n) { return '• ' . h($n); }, $inclList)) : '—';
    $noIncl = $noInclList ? implode('<br/>', array_map('h', $noInclList)) : '—';
    $html .= '<tr><td style="vertical-align: middle;">' . h($serNom) . '</td><td style="vertical-align: middle;">' . $incl . '</td><td style="vertical-align: middle;">' . $noIncl . '</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<p style="margin-top: 20px;"><strong style="color: #2C5D94;">2. CATÁLOGO DE SERVICIOS ADICIONALES (SELECCIONADOS)</strong></p>';
$html .= '<p>Los siguientes servicios adicionales han sido seleccionados en esta propuesta. Los precios están calculados según el régimen de su empresa.</p>';

if (empty($seleccionados)) {
    $html .= '<p>Ningún servicio adicional seleccionado.</p>';
} else {
    $html .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 10pt;">';
    $html .= '<tr style="background: #2C5D94; color: white;"><th style="text-align: left;">SERVICIO ADICIONAL</th><th style="text-align: right; width: 120px;">PRECIO USD</th></tr>';
    $ultimoSer = '';
    foreach ($seleccionados as $item) {
        $serNom = isset($item['Ser_Nombre']) ? $item['Ser_Nombre'] : '';
        if ($serNom !== '' && $serNom !== $ultimoSer) {
            $html .= '<tr style="background: #E2E8F0;"><td colspan="2"><strong>' . h(mb_strtoupper($serNom)) . ' ADICIONALES</strong></td></tr>';
            $ultimoSer = $serNom;
        }
        $nom = isset($item['Act_Nombre']) ? $item['Act_Nombre'] : '';
        $precio = isset($item['Precio']) ? floatval($item['Precio']) : 0;
        $html .= '<tr><td>' . h($nom) . '</td><td style="text-align: right;">$' . number_format($precio, 2) . '</td></tr>';
    }
    $html .= '</table>';
}

$html .= '<p style="margin-top: 20px;"><strong style="color: #2C5D94;">CONDICIONES GENERALES</strong></p><ul>';
$html .= '<li>Los precios mostrados son referenciales y están sujetos a la complejidad específica de cada caso.</li>';
$html .= '<li>Los servicios adicionales se facturarán únicamente cuando sean solicitados y previamente aprobados por el cliente.</li>';
$html .= '<li>Este tarifario tiene vigencia durante el año ' . date('Y') . ' y podrá ser actualizado previa notificación.</li>';
$html .= '<li>Los precios no incluyen IVA.</li>';
$html .= '<li>Para solicitar cualquiera de estos servicios, puede comunicarse con nosotros a través de los canales habituales.</li></ul>';

$html .= '<p style="margin-top: 20px;"><strong style="color: #2C5D94;">CONFIRMACIÓN DE RECEPCIÓN</strong></p>';
$html .= '<p>Por favor, firme este documento como constancia de que ha recibido y conoce el listado de servicios adicionales y sus tarifas vigentes:</p>';
$html .= '<table style="width: 100%; margin-top: 24px;"><tr><td style="width: 50%; vertical-align: top;">';
$html .= '<p style="border-bottom: 1px solid #333; height: 28px;"></p><p style="margin-top: 4px; font-size: 9pt;">Firma del Cliente</p><p style="margin-top: 4px;">' . h($clienteNombre) . '</p><p style="margin-top: 2px; font-size: 9pt;">' . ($clienteRuc !== '' ? 'RUC: ' . h($clienteRuc) : '') . '</p></td>';
$html .= '<td style="width: 50%; vertical-align: top;">';
$html .= '<p style="border-bottom: 1px solid #333; height: 28px;"></p><p style="margin-top: 4px; font-size: 9pt;">Firma del Despacho</p><p style="margin-top: 4px;">' . ($repLegalNombre !== '' ? h($repLegalNombre) : '[NOMBRE DEL REPRESENTANTE]') . '</p><p style="margin-top: 2px; font-size: 9pt;">' . ($repLegalDoc !== '' ? 'C.I./RUC: ' . h($repLegalDoc) : '') . '</p></td></tr></table>';

$html .= '</body></html>';

$nombreArchivo = 'Propuesta_Servicios_Adicionales_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $clienteNombre) . '_' . date('Y-m-d_His') . '.doc';
$nombreArchivo = substr($nombreArchivo, 0, 80) . '.doc';

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.ms-word; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
echo $html;
exit;
