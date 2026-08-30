<?php

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


$obBD_conexion = new Class_Log_Conexion_Activo($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Activo;


$rs_cabecera = $obBD_con1->getRowConsulta(710,$Ses_Suc_Cod,$obBD_conexion);
$data['Suc_Cod']=$Ses_Suc_Cod;
$data['Act_Cod']=$Act_Cod;
$rs_activo=$obBD_con1->getRowConsulta(6133,$data,$obBD_conexion);
if(!empty($rs_activo['Act_Fot'])){
    $imagenes=explode(',',$rs_activo['Act_Fot']);
}
$rs_detactivo=$obBD_con1->getArrayConsulta(618,$rs_activo['Act_Cod'], $obBD_conexion);
$html='
<table width="100%">
    <tr>
        <td>
            <table width="100%">
                <tr><td rowspan="5"><img src="'.$rs_cabecera['Emp_Log'].'" height="80" width="80"></td></tr>
                <tr><td width="100%"><b>'.$rs_cabecera['Emp_Nom'].'</b></td></tr>
                <tr><td><b>R.U.C.: </b>'.$rs_cabecera['Emp_Ruc'].'</td></tr>
                <tr><td><b>DIRECCI&Oacute;N: </b>'.$rs_cabecera['Suc_Dir'].'</td></tr>
                <tr><td><b>'.$rs_cabecera['provincia'].'</b></td></tr>
            </table>
        </td>
    </tr>
    <hr width="100%" style="color: #000000;" />
    <tr>
        <td>
            <table>
                <tr><td bgcolor="#BDBDBD"><b>INFORMACI&Oacute;N DE ACTIVO</b></td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <tr><td><b>C&oacute;digo: </b>'.$rs_activo['Act_Cod'].'</td><td><b>Categor&iacute;a: </b>'.$rs_activo['Tia_Des'].'</td></tr>
                <tr><td><b>Activo: </b>'.$rs_activo['Act_Des'].'</td><td><b>C&oacute;digo de Barras: </b>'.$rs_activo['Act_Bar'].'</td></tr>
                <tr><td><b>Vida &Uacute;til: </b>'.$rs_activo['Act_Ann'].' a&ntilde;os</td><td><b>Garant&iacute;a: </b>'.$rs_activo['Act_Gar'].' meses</td></tr>
                <tr><td><b>Costo: </b>'.$rs_activo['Act_Val'].'</td><td><b>Valor Residual: </b>'.$rs_activo['Act_Res'].'</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <tr><td bgcolor="#BDBDBD"><b>DETALLE DE ACTIVO</b></td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>';
            foreach ($rs_detactivo as $valor){
                $html.='<tr><td><b>'.$valor['Cam_Lar'].': </b></td><td>'.$valor['Act_Val'].'</td></tr>';
            }
$html.='    </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <tr><td bgcolor="#BDBDBD"><b>INFORMACI&Oacute;N DE DEPRECIACI&Oacute;N</b></td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <tr><td><b>Depreciaci&oacute;n Anual: </b>'.$rs_activo['Act_Pde'].' %</td></tr>
                <tr><td><b>Fecha Inicio Depreciaci&oacute;n: </b>'.$rs_activo['Act_Fec'].'</td><td><b>Fecha Fin Depreciaci&oacute;n: </b>'.$rs_activo['Act_Ffd'].'</td></tr>
                <tr><td><b>Depreciaci&oacute;n Acumulada: </b>'.$Dep_Acm.'</td><td><b>Valor en Libros: </b>'.$Val_Lib.'</td></tr>
                <tr><td><b>Estado: </b>'.$Estado.'&oacute;n</td><td><b>C.C. Depreciaci&oacute;n: </b>'.$rs_activo['Pld_Des'].'</td></tr>
            </table>
        </td>
    </tr>    
    <tr>
        <td>
            <table>
                <tr><td bgcolor="#BDBDBD"><b>IM&Aacute;GENES DEL ACTIVO</b></td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <tr>';
                if(count((array)$imagenes)>0){
                    foreach ($imagenes as $img){
                        $html.='<td><img src="../../imagenes/'.$Ses_Emp_Cod.'/Activos/'.$img.'" height="220" width="280" style="border:1px solid black;"></td>';
                    }
                }else{
                    $html.='<td>No hay im&aacute;genes para mostrar.</td>';
                }
$html.='        </tr>
            </table>
        <td>
    <tr>';
    if($rs_activo['Act_Obs']==""){$observacion="No existe observaci&oacute;n alguna.";}else{$observacion=$rs_activo['Act_Obs'];}
$html.='
    <tr>
        <td>
            <table>
                <tr><td><b>Observaci&oacute;n: </b>'.$observacion.'</td></tr>
            </table>
        </td>
    </tr>
</table>';

ini_set('memory_limit', '32M');
set_time_limit(0);
require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf(['mode' => 'c', 'format' => 'A4', 'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 16, 'margin_bottom' => 16, 'margin_header' => 9, 'margin_footer' => 9]); 

$mpdf->SetDisplayMode('fullpage');

$mpdf->list_indent_first_level = 0;// 1 or 0 - whether to indent the first level of a list
$mpdf->SetHTMLFooter('<hr width="100%" style="color: #000000;" /><table width="100%" style="vertical-align: bottom; font-family: arial; font-size: 8pt; color: #000000;"><tr>
<td width="50%" align="left">Generado el {DATE j-m-Y} por EXA [Sofware Contable]</td>
<td width="50%" align="right">{PAGENO}/{nbpg}</td>
</tr></table>');

//LOAD a stylesheet
$stylesheet = file_get_contents('../../Librerias/MPDF57/css/mpdfstyletables.css');
$mpdf->WriteHTML($stylesheet,1);// The parameter 1 tells that this is css/style only and no body/html/text
$mpdf->WriteHTML($html,2);
$mpdf->Output('mpdf.pdf','I');
exit;




