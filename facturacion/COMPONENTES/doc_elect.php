<?php	
/**
* @abstract Permite
* @author Erik Niebla
* @version 1.0
* Fecha de creación  2016-11-24
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/fac_log_electronica.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Elect($Ses_Cli_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Elect;

$hoy = date("Y-m-d");
$mes = date("m");

$ruta="/FRONT/$Ses_Cli_Emp_Cod/";
$ruta_xmls=$APP_REAL_PATH."/facturacion".$ruta;

$documento=array();
if($type=='VENTAS'||$type=='NOTASC') $documento=$obBD_con1->getRowConsulta(3, $Ses_Cli_Emp_Cod.'*'.$Ses_Cli_Cod.'*Vet_Cod='.$Doc_Cod, $obBD_conexion); 
if($type=='RETENC') $documento=$obBD_con1->getRowConsulta(4, $Ses_Cli_Emp_Cod.'*'.$Ses_Prv_Cod.'* AND Ret_Cod='.$Doc_Cod, $obBD_conexion,true); 

$xml=$ruta_xmls.$documento['Doc_Xml'];

if(is_readable($xml."_A.xml")){    
    $documento['file_xml']='..'.$ruta.$documento['Doc_Xml']."_A.xml";
} 

?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
    <style>  
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar Documentos Electronicos  <div class="pull-right"><button type="button" onclick="window.open('<?php echo $documento['file_xml']; ?>')" class="btn btn-success btn-xs" data-originaldata="" title="Descargar"><i class="fa fa-download"></i></button> </div> </h3> </div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
           
                <div class="row">  
                    <div class="col-xs-12">  
                        <iframe src="http://exa.ofsercont.com/facturacion/COMPONENTES/<?php echo $ruta_pdf[$documento['type']]; ?>?urlXml=<?php echo $documento['file_xml']; ?>&op=I&logoUrl=<?php echo $Ses_Cli_Emp_Log; ?>" style="width:100%; height:450px;" frameborder="0"></iframe>
                    </div> 
                </div>
            
        </div>
    </div>
    <script type="text/javascript">
        
    </script>
</BODY>
</HTML>