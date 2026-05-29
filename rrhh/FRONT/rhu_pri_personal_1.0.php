<?php

/**
 * @abstract Permite imprimir a traves de PDF el registro de un empleado seleccionado
 * @author Jose Ambuludi
 * @version 1.0
 * Fecha de creacion  2017-01-24
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_personal.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas
 */
$obBD_con1 = new Class_Log_Datos_rrhh;

/*Seccion para obtener los datos de la cabecera*/
$rs_cabecera = $obBD_con1->getRowConsulta(14, $Ses_Suc_Cod, $obBD_conexion);
/*Seccion para obtener la informacion de un empleado*/
$rs_empleado = $obBD_con1->getRowConsulta(15, $Per_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);
$rs_contrato = $obBD_con1->getRowConsulta(17, $Per_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);

utf8_encode_deep($rs_cabecera);
utf8_encode_deep($rs_empleado);
utf8_encode_deep($rs_contrato);

$estado_afiliacion = (isset($rs_contrato['Afi_Est']) && $rs_contrato['Afi_Est'] == 'A') ? 'SI' : 'NO';

$pago_contrato = 'Indefinido';
if (isset($rs_contrato['Pag_Con_Cue'])) {
    if ($rs_contrato['Pag_Con_Cue'] == 'T') {
        $pago_contrato = 'Transferencia';
    } elseif ($rs_contrato['Pag_Con_Cue'] == 'C') {
        $pago_contrato = 'Cheque';
    } elseif ($rs_contrato['Pag_Con_Cue'] == 'E') {
        $pago_contrato = 'Efectivo';
    }
}

if (empty($rs_empleado['Prs_Fec'])) {
    $Prs_Eda = '-';
    $Prs_Fec = '-';
} else {
    $descomponer = explode('-', $rs_empleado['Fec_Sys']);
    $descompone1 = explode('-', $rs_empleado['Prs_Fec']);
    $Prs_Eda = $descomponer[0] - $descompone1[0];
    $Prs_Fec = $rs_empleado['Prs_Fec'];
}

$C_ACCENT = '#2563eb';
$C_HEAD   = '#334155';
$C_LABEL  = '#475569';
$C_TEXT   = '#1e293b';
$C_MUTED  = '#64748b';
$C_BORDER = '#e2e8f0';
$C_ZEBRA  = '#f8fafc';
$C_OK_BG  = '#dcfce7';
$C_OK_TX  = '#166534';
$C_OFF_BG = '#fee2e2';
$C_OFF_TX = '#991b1b';
$PRI_PAD    = '3px 8px';
$PRI_FS_LBL = '8.5pt';
$PRI_FS_VAL = '9pt';
$PRI_COLS   = array('22%', '28%', '22%', '28%');
$PRI_TBL_CSS = 'width:100%;border-collapse:collapse;table-layout:fixed;';

function priResetZebra() {
    priFilaDoble('', '', '', '', true);
}

function priInicioTabla() {
    global $C_BORDER, $PRI_COLS, $PRI_TBL_CSS;
    return '<table class="pri-full" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid ' . $C_BORDER . ';' . $PRI_TBL_CSS . '">'
        . '<colgroup>'
        . '<col width="' . $PRI_COLS[0] . '" />'
        . '<col width="' . $PRI_COLS[1] . '" />'
        . '<col width="' . $PRI_COLS[2] . '" />'
        . '<col width="' . $PRI_COLS[3] . '" />'
        . '</colgroup>'
        . '<tr style="height:0;line-height:0;font-size:0;">'
        . '<td width="' . $PRI_COLS[0] . '" style="padding:0;border:0;margin:0;"></td>'
        . '<td width="' . $PRI_COLS[1] . '" style="padding:0;border:0;margin:0;"></td>'
        . '<td width="' . $PRI_COLS[2] . '" style="padding:0;border:0;margin:0;"></td>'
        . '<td width="' . $PRI_COLS[3] . '" style="padding:0;border:0;margin:0;"></td>'
        . '</tr>';
}

function priCeldaFull($contenido) {
    return '<table width="100%" cellpadding="0" cellspacing="0" style="' . $GLOBALS['PRI_TBL_CSS'] . '"><tr><td width="100%" style="width:100%;padding:0;">' . $contenido . '</td></tr></table>';
}

function priValor($valor) {
    if ($valor === null || $valor === '' || $valor === false) {
        return '-';
    }
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function priFila($etiqueta, $valor) {
    global $C_LABEL, $C_BORDER, $C_TEXT, $C_ZEBRA, $PRI_PAD, $PRI_FS_LBL, $PRI_FS_VAL;
    static $alt = false;
    $alt = !$alt;
    $bg = $alt ? 'background:' . $C_ZEBRA . ';' : '';
    return '<tr>'
        . '<td style="padding:' . $PRI_PAD . ';font-size:' . $PRI_FS_LBL . ';font-weight:bold;color:' . $C_LABEL . ';border-bottom:1px solid ' . $C_BORDER . ';' . $bg . '">' . $etiqueta . '</td>'
        . '<td style="padding:' . $PRI_PAD . ';font-size:' . $PRI_FS_VAL . ';color:' . $C_TEXT . ';border-bottom:1px solid ' . $C_BORDER . ';' . $bg . '" colspan="3">' . priValor($valor) . '</td>'
        . '</tr>';
}

function priFilaDoble($et1, $val1, $et2, $val2, $reset = false) {
    global $C_LABEL, $C_BORDER, $C_TEXT, $C_ZEBRA, $PRI_PAD, $PRI_FS_LBL, $PRI_FS_VAL;
    static $alt = false;
    if ($reset) {
        $alt = false;
        return '';
    }
    $alt = !$alt;
    $bg = $alt ? 'background:' . $C_ZEBRA . ';' : '';
    $celda2 = ($et2 === '' && ($val2 === '' || $val2 === null))
        ? '<td style="padding:' . $PRI_PAD . ';border-bottom:1px solid ' . $C_BORDER . ';' . $bg . '" colspan="2">&nbsp;</td>'
        : '<td style="padding:' . $PRI_PAD . ';font-size:' . $PRI_FS_LBL . ';font-weight:bold;color:' . $C_LABEL . ';border-bottom:1px solid ' . $C_BORDER . ';' . $bg . '">' . $et2 . '</td>'
        . '<td style="padding:' . $PRI_PAD . ';font-size:' . $PRI_FS_VAL . ';color:' . $C_TEXT . ';border-bottom:1px solid ' . $C_BORDER . ';' . $bg . '">' . priValor($val2) . '</td>';
    return '<tr>'
        . '<td style="padding:' . $PRI_PAD . ';font-size:' . $PRI_FS_LBL . ';font-weight:bold;color:' . $C_LABEL . ';border-bottom:1px solid ' . $C_BORDER . ';' . $bg . '">' . $et1 . '</td>'
        . '<td style="padding:' . $PRI_PAD . ';font-size:' . $PRI_FS_VAL . ';color:' . $C_TEXT . ';border-bottom:1px solid ' . $C_BORDER . ';' . $bg . '">' . priValor($val1) . '</td>'
        . $celda2
        . '</tr>';
}

function priTituloSeccion($texto) {
    global $C_HEAD, $C_ACCENT, $PRI_TBL_CSS;
    return '<table class="pri-full" width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;margin-bottom:0;' . $PRI_TBL_CSS . '">'
        . '<tr><td width="100%" style="width:100%;background-color:' . $C_HEAD . ';color:#ffffff;font-size:9pt;font-weight:bold;padding:5px 10px;letter-spacing:0.5px;">'
        . '<span style="color:' . $C_ACCENT . ';font-size:9pt;margin-right:6px;">|</span>' . $texto
        . '</td></tr></table>';
}

function priTituloDocumento($texto) {
    global $C_ACCENT, $C_TEXT, $C_MUTED, $PRI_TBL_CSS;
    return '<table class="pri-full" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 6px 0;' . $PRI_TBL_CSS . '">'
        . '<tr><td width="100%" style="width:100%;border-left:4px solid ' . $C_ACCENT . ';padding:2px 0 2px 10px;">'
        . '<span style="font-size:11pt;font-weight:bold;color:' . $C_TEXT . ';">' . $texto . '</span>'
        . '<span style="font-size:8pt;color:' . $C_MUTED . ';margin-left:8px;"></span>'
        . '</td></tr></table>';
}

$logoHtml = '';
if (!empty($rs_cabecera['Emp_Log'])) {
    $logoHtml = '<img src="' . $rs_cabecera['Emp_Log'] . '" width="65" height="65" style="width:65px;height:65px;" />';
}

$fotoHtml = '<table width="85" cellpadding="0" cellspacing="0"><tr><td align="center" style="width:85px;height:85px;background:#e5e7eb;border:1px solid #d1d5db;font-size:7.5pt;color:#6b7280;">Sin foto</td></tr></table>';
if (!empty($rs_empleado['Per_Fot']) && $rs_empleado['Per_Fot'] !== 'no') {
    $fotoHtml = '<img src="../../imagenes/' . $Ses_Emp_Cod . '/personal/' . $rs_empleado['Per_Fot'] . '" width="85" height="85" style="width:85px;height:85px;" />';
}

$estadoBadge = (isset($rs_empleado['Per_Est']) && $rs_empleado['Per_Est'] === 'Activo')
    ? '<span style="background:' . $C_OK_BG . ';color:' . $C_OK_TX . ';padding:2px 8px;font-size:7.5pt;font-weight:bold;">Activo</span>'
    : '<span style="background:' . $C_OFF_BG . ';color:' . $C_OFF_TX . ';padding:2px 8px;font-size:7.5pt;font-weight:bold;">Inactivo</span>';

$html = '
<table class="pri-full" width="100%" cellpadding="0" cellspacing="0" style="font-family:helvetica,arial,sans-serif;font-size:9pt;color:' . $C_TEXT . ';' . $PRI_TBL_CSS . '">
    <tr><td width="100%" style="width:100%;padding:0;">
        <table class="pri-full" width="100%" cellpadding="0" cellspacing="0" style="border-bottom:2px solid ' . $C_ACCENT . ';padding-bottom:8px;' . $PRI_TBL_CSS . '">
            <tr>
                <td width="75" valign="top" style="padding-right:10px;">' . $logoHtml . '</td>
                <td valign="top">
                    <div style="font-size:12pt;font-weight:bold;color:' . $C_HEAD . ';margin-bottom:3px;">' . priValor($rs_cabecera['Emp_Nom']) . '</div>
                    <div style="font-size:8.5pt;color:' . $C_MUTED . ';line-height:1.35;">
                        <strong>RUC:</strong> ' . priValor($rs_cabecera['Emp_Ruc']) . ' &nbsp;|&nbsp;
                        <strong>Dir:</strong> ' . priValor($rs_cabecera['Suc_Dir']) . '<br/>
                        ' . priValor($rs_cabecera['provincia']) . '
                    </div>
                </td>
                <td width="95" valign="top" align="right">' . $fotoHtml . '</td>
            </tr>
        </table>
    </td></tr>

    <tr><td width="100%" style="width:100%;padding:0;">' . priTituloDocumento('Ficha del trabajador') . '</td></tr>

    <tr><td width="100%" style="width:100%;padding:0;">
        <table class="pri-full" width="100%" cellpadding="0" cellspacing="0" style="background:' . $C_ZEBRA . ';border:1px solid ' . $C_BORDER . ';margin-bottom:4px;' . $PRI_TBL_CSS . '">
            <tr>
                <td style="padding:8px 10px;">
                    <div style="font-size:12pt;font-weight:bold;color:' . $C_TEXT . ';margin-bottom:3px;">' . priValor($rs_empleado['empleado']) . '</div>
                    <div style="font-size:8.5pt;color:' . $C_MUTED . ';line-height:1.35;">
                        <strong>Identificaci&oacute;n:</strong> ' . priValor($rs_empleado['Ide_Des']) . ' ' . priValor($rs_empleado['Prs_Ced']) . ' &nbsp;|&nbsp;
                        ' . $estadoBadge . '
                    </div>
                </td>
            </tr>
        </table>
    </td></tr>

    <tr><td width="100%" style="width:100%;padding:0;">' . priCeldaFull(
        priTituloSeccion('INFORMACI&Oacute;N PERSONAL')
        . priResetZebra() . priInicioTabla()
            . priFilaDoble('Fecha de nacimiento', $Prs_Fec, 'Edad', $Prs_Eda)
            . priFilaDoble('Ciudad', $rs_empleado['Ciu_Des'], 'G&eacute;nero', $rs_empleado['Prs_Gen'])
            . priFilaDoble('Tel&eacute;fono', $rs_empleado['Prs_Tel'], 'Celular', $rs_empleado['Prs_Cel'])
            . priFilaDoble('Tel. adicional', $rs_empleado['Prs_Te2'], 'Estado civil', $rs_empleado['Prs_Esc'])
            . priFilaDoble('Correo', $rs_empleado['Prs_Cor'], 'Carga familiar', $rs_empleado['Per_Car'])
            . priFila('Direcci&oacute;n', $rs_empleado['Prs_Dir'])
            . priFilaDoble('T&iacute;tulo', $rs_empleado['Per_Tit'], 'Riesgo social', $rs_empleado['Per_Rso'])
            . priFilaDoble('Movilizaci&oacute;n', $rs_empleado['Per_Mov'], '', '')
            . priFila('Observaci&oacute;n', $rs_empleado['Per_Obs']) . '
        </table>'
    ) . '</td></tr>

    <tr><td width="100%" style="width:100%;padding:0;">' . priCeldaFull(
        priTituloSeccion('INFORMACI&Oacute;N M&Eacute;DICA')
        . priResetZebra() . priInicioTabla()
            . priFilaDoble('Condici&oacute;n f&iacute;sica', $rs_empleado['Per_Cfi'], 'Tipo condici&oacute;n f&iacute;sica', $rs_empleado['Per_Tcf'])
            . priFilaDoble('Tipo de sangre', $rs_empleado['Prs_San'], '', '') . '
        </table>'
    ) . '</td></tr>

    <tr><td width="100%" style="width:100%;padding:0;">' . priCeldaFull(
        priTituloSeccion('CONTRATO LABORAL')
        . priResetZebra() . priInicioTabla()
            . priFilaDoble('Inicio contrato', $rs_contrato['Con_Ini'], 'Sueldo', $rs_contrato['Sue_Val'])
            . priFilaDoble('Dedicaci&oacute;n laboral', $rs_contrato['Ded_Des'], 'Relaci&oacute;n laboral', $rs_contrato['Reb_Des'])
            . priFilaDoble('Fecha contrato IESS', $rs_contrato['Afi_Fei'], 'Afiliado al IESS', $estado_afiliacion)
            . priFilaDoble('Forma de pago', $pago_contrato, '', '') . '
        </table>'
    ) . '</td></tr>
</table>';

ini_set('memory_limit', '32M');
include("../../Librerias/MPDF57/mpdf.php");

$mpdf = new mPDF('c', 'A4', '', '', 15, 15, 16, 16, 9, 9);
$mpdf->keep_table_proportions = true;
$mpdf->shrink_tables_to_fit = 1;
$mpdf->SetDisplayMode('fullpage');
$mpdf->list_indent_first_level = 0;
$mpdf->SetHTMLFooter('<hr width="100%" style="color:#e2e8f0;" /><table width="100%" style="vertical-align:bottom;font-family:helvetica,arial;font-size:8pt;color:#94a3b8;"><tr>
<td width="50%" align="left">Generado el {DATE j-m-Y} - EXA Software Contable</td>
<td width="50%" align="right">{PAGENO}/{nbpg}</td>
</tr></table>');
$stylesheet = file_get_contents('../../Librerias/MPDF57/css/mpdfstyletables.css');
$mpdf->WriteHTML($stylesheet, 1);
$mpdf->WriteHTML('table.pri-full { width: 100%; border-collapse: collapse; table-layout: fixed; } table.pri-full td { padding-left: 0; padding-right: 0; }', 1);
$mpdf->WriteHTML($html, 2);
$mpdf->Output('ficha_trabajador.pdf', 'I');
exit;
