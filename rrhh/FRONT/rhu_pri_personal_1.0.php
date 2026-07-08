<?php

/**
 * @abstract Permite imprimir a trav�s de PDF el registro de un empleado seleccionado
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci�n  2017-01-24
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

/*Secci�n para obtener los datos de la cabecera*/
$rs_cabecera = $obBD_con1->getRowConsulta(14, $Ses_Suc_Cod, $obBD_conexion);
/*Secci�n para obtener la informaci�n de un empleado*/
//$data['Suc_Cod']=$Ses_Suc_Cod;
//$data['Act_Cod']=$Act_Cod;
$rs_empleado = $obBD_con1->getRowConsulta(15, $Per_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);
$rs_contrato = $obBD_con1->getRowConsulta(17, $Per_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);
$estado_afiliacion = null;

if ($rs_contrato['Afi_Est'] == 'A') {
    $estado_afiliacion = "SI";
} else {
    $estado_afiliacion = "NO";
}

$pago_contrato = "Indefinido";

if ($rs_contrato['Pag_Con_Cue'] == 'T') {
    $pago_contrato = 'Transferencia';
} else if ($rs_contrato['Pag_Con_Cue'] == 'C') {
    $pago_contrato = 'Cheque';
} else  if ($rs_contrato['Pag_Con_Cue'] == 'E') {
    $pago_contrato = 'Efectivo';
} 

if (empty($rs_empleado['Prs_Fec'])) {
    $Prs_Eda = "-";
    $Prs_Fec = "-";
} else {
    $descomponer = explode('-', $rs_empleado['Fec_Sys']);
    $descompone1 = explode('-', $rs_empleado['Prs_Fec']);
    $Prs_Eda = $descomponer[0] - $descompone1[0];
    $Prs_Fec = $rs_empleado['Prs_Fec'];
}

$html = '
<table width="100%">
    <tr>
        <td>
            <table width="100%">
                <tr><td rowspan="5"><img src="' . $rs_cabecera['Emp_Log'] . '" height="80" width="80"></td></tr>
                <tr><td width="100%"><b>' . $rs_cabecera['Emp_Nom'] . '</b></td></tr>
                <tr><td><b>R.U.C.: </b>' . $rs_cabecera['Emp_Ruc'] . '</td></tr>
                <tr><td><b>DIRECCI&Oacute;N: </b>' . $rs_cabecera['Suc_Dir'] . '</td></tr>
                <tr><td><b>' . $rs_cabecera['provincia'] . '</b></td></tr>
            </table>
        </td>
    </tr>
    <hr width="100%" style="color: #000000;" />
    <tr>
        <td>
            <table>
                <tr><td bgcolor="#BDBDBD"><b>INFORMACI&Oacute;N PERSONAL</b></td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%">
                <tr><td><b>C&eacute;dula/R.U.C.:</b> ' . $rs_empleado['Prs_Ced'] . '</td><td rowspan="7"><img src="../../imagenes/' . $Ses_Emp_Cod . '/personal/' . $rs_empleado['Per_Fot'] . '" height="130" width="130"></td></tr>
                <tr><td><b>Empleado:</b> ' . $rs_empleado['empleado'] . '</td></tr>
                <tr><td><b>Fecha de Nacimiento:</b> ' . $Prs_Fec . '</td></tr>
                <tr><td width="50%"><b>Edad:</b> ' . $Prs_Eda . '</td></tr>
                <tr><td><b>Ciudad:</b> ' . $rs_empleado['Ciu_Des'] . '</td></tr>
                <tr><td><b>Tel&eacute;fono 1:</b> ' . $rs_empleado['Prs_Tel'] . '</td></tr>
                <tr><td><b>Tel&eacute;fono 2:</b> ' . $rs_empleado['Prs_Te1'] . '</td></tr>
                <tr><td><b>Tel&eacute;fono 3:</b> ' . $rs_empleado['Prs_Te2'] . '</td><td width="50%"><b>Genero:</b> ' . $rs_empleado['Prs_Gen'] . '</td></tr>
                <tr><td><b>Email:</b> ' . $rs_empleado['Prs_Cor'] . '</td><td><b>Estado Civil:</b> ' . $rs_empleado['Prs_Esc'] . '</td></tr>
                <tr><td><b>Direcci&oacute;n:</b> ' . $rs_empleado['Prs_Dir'] . '</td><td><b>Carga Familiar:</b> ' . $rs_empleado['Per_Car'] . '</td></tr>
                <tr><td><b>Observaci&oacute;n:</b> ' . $rs_empleado['Per_Obs'] . '</td><td><b>T&iacute;tulo:</b> ' . $rs_empleado['Per_Tit'] . '</td></tr>
            </table>
        </td>
    </tr>

    <tr>
        <td>
            <table>
                <tr><td bgcolor="#BDBDBD"><b>INFORMACI&Oacute;N M&Eacute;DICA</b></td></tr>
            </table>
        </td>
    </tr>

    <tr>
        <td>
            <table width="100%">
                <tr><td><b>Condici&oacute;n F&iacute;sica:</b> ' . $rs_empleado['Per_Cfi'] . '</td><td><b>Tipo de Sangre:</b> ' . $rs_empleado['Prs_San'] . '</td></tr>
            </table>
        </td>
    </tr>



 <tr>
        <td>
            <table>
                <tr><td bgcolor="#BDBDBD"><b>CONTRATO LABORAL</b></td></tr>
            </table>
        </td>
    </tr>

 <tr>
        <td>
            <table width="100%">
                <tr><td><b>Inicio contrato:</b> ' . $rs_contrato['Con_Ini'] . '</td></tr>
                <tr><td><b>Sueldo:</b> ' . $rs_contrato['Sue_Val'] . '</td></tr>
                <tr><td><b>Dedicaci&oacute;n laboral:</b> ' . $rs_contrato['Ded_Des'] . '</td></tr>
                <tr><td><b>Relaci&oacute;n laboral:</b> ' . $rs_contrato['Reb_Des'] . '</td></tr>
                <tr><td><b>Fecha contrato IESS:</b> ' . $rs_contrato['Afi_Fei'] . '</td></tr>
                <tr><td><b>Afiliado al IESS:</b> ' . $estado_afiliacion . '</td></tr>
                <tr><td><b>Forma de pago:</b> ' . $pago_contrato . '</td></tr>
            </table>
        </td>
    </tr>


</table>';

/*Configuraci�n de impresi�n y hoja*/
ini_set('memory_limit', '32M');
require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf(['mode' => 'c', 'format' => 'A4', 'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 16, 'margin_bottom' => 16, 'margin_header' => 9, 'margin_footer' => 9]);

$mpdf->SetDisplayMode('fullpage');

$mpdf->list_indent_first_level = 0; // 1 or 0 - whether to indent the first level of a list
$mpdf->SetHTMLFooter('<hr width="100%" style="color: #000000;" /><table width="100%" style="vertical-align: bottom; font-family: arial; font-size: 8pt; color: #000000;"><tr>
<td width="50%" align="left">Generado el {DATE j-m-Y} por EXA [Sofware Contable]</td>
<td width="50%" align="right">{PAGENO}/{nbpg}</td>
</tr></table>');
//LOAD a stylesheet
$stylesheet = file_get_contents('../../Librerias/MPDF57/css/mpdfstyletables.css');
$mpdf->WriteHTML($stylesheet, 1); // The parameter 1 tells that this is css/style only and no body/html/text
$html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
$mpdf->WriteHTML($html, 2);

$mpdf->Output('mpdf.pdf', 'I');
exit;
