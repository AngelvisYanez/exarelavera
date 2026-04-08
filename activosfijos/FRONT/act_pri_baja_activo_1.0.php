<?php 
/** 
 @Alias:		 Imprimir :) drzm_12@
 @Descripción:   Permite la mimprimir los controles de auditoria por Custodio
 @Desarrollador: Didimo Zamora.
***********************************
 @Fecha de actualización:	2013-07-25
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo_baj.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php'); 


/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;
/** 
 * Creación del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;
/**
 * Muestra modal para dar de alta el mantenimiento de activo.
 */
 
/**
 * Consulta de la institución del usuario. 
 */
$rs_institucion = $obBD_con1->getRowConsulta(12, $Ses_Suc_Cod,$obBD_conexion);
$row_rs_institucion = $rs_institucion;

/**
 * Consulta de las auditorias por id_auditoria  para obtener los datos del custodio
 */
$rs_ControlAud = $obBD_con1->getRowConsulta(13, $Aud_Int,$obBD_conexion);

/**
 * Consulta del  auditor o responsable del control
 */
$rs_auditore = $obBD_con1->getRowConsulta(11,$rs_ControlAud['Aud_Cod'],$obBD_conexion);

$hoy = date("Y-m-d");
?> 

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?Php echo $Ses_Sys_Nom;?></title>
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
	<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
    <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<?php 	  
	/**
	 * Variables para Encabezado
	 */
	 $Titulo="Baja de Activo Fijo";
	 $Subtitulo="";	
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
  	<tr align="center">
    	<td>
           <table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr align="center">
                  <td colspan="4" ><? $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod,$Titulo,$Subtitulo,$obBD_conexion)?></td>
                </tr>
           </table>  
      </td>
  </tr>
</table>

<?Php  

 if (isset($Man_Cod_Aux)){ 
	 /**
	  * Consultar los activos dados de baja.
	  */
		
	$rs_bajas = $obBD_con1->getRowConsulta(5,$Man_Cod_Aux.'*'.$Ses_Emp_Cod,$obBD_conexion);

?>
<table align="center" cellspacing="0" cellpadding="0" width="80%">
<tr>
	<td>
	<fieldset>
		<LEGEND>
		<label class="Titulos2">Datos del Activo</label>
		</LEGEND>
		<table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="3"> </td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"> Código Activo:</td>
			<td width="72%" class="Texto_Reporte">&nbsp;<?php echo $rs_bajas["Act_Cdc"]?></td>
			<td width="8%"></td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"> Descripción:</td>
			<td class="Texto_Reporte">&nbsp;<?php echo $rs_bajas["Act_Des"]?></td>
			<td></td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"> Tipo de Activo:</td>
			<td> <label class="Texto_Reporte">&nbsp;<?php echo $rs_bajas["Tia_Des"];?></label></td>
			<td></td>
		</tr>
		</table> 
 </fieldset> 
 
 <FIELDSET>
		<LEGEND>
		<label class="Titulos2">Datos de la Baja del Activo</label>
		</LEGEND>
		<table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="3"> </td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Motivo:</td>
			<td width="80%" class="Texto_Reporte">&nbsp;<? echo $rs_bajas['Est_Des'];?>      
            </td>
			<td width="0%"></td>
		</tr>
        <tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Fecha del Informe:</td>
			<td class="Texto_Reporte">&nbsp;<? echo $rs_bajas['Baj_Fba'];?> </td>
			<td></td>
		</tr>       
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Informe Técnico:</td>
			<td class="Texto_Reporte" valign="middle">&nbsp;<div align="justify"> <? echo $rs_bajas['Baj_Inf'];?> </div></td>
			<td></td>
		</tr>       
		</table>
 </FIELDSET>
 <FIELDSET>
		<LEGEND>
		<label class="Titulos2">Destino del activo dado de baja</label>
		</LEGEND>
		<table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Destino:</td>
			<td width="80%" class="Texto_Reporte">&nbsp;<? echo $rs_bajas['Baj_Des'];?>      	    	
            </td>			
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Receptor:</td>
			<td class="Texto_Reporte">&nbsp;<? echo $rs_bajas['Baj_Qui'];?></td>			
		</tr>
        <tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Valor:</td>
			<td class="Texto_Reporte">&nbsp;<? echo $rs_bajas['Baj_Val'];?></td>			
		</tr>
	</table>
 </FIELDSET>
 </td>
 </tr>
</table>

<?
 }
?>

<?Php
         
   /**
    * Consulta los datos del usuario 
    */
   $row_usuario = $obBD_con1->getRowConsulta(5003, $Ses_Usu_Cod, $obBD_conexion);
   $fecha=explode("-",date("Y-m-d")); 
         $fechaHoy = $rs_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ; 
    
?>
   <table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
   		<tr>
	        <td>&nbsp;</td>            
        </tr>
   		
       
        <tr>
        	<td>&nbsp;</td>        
        </tr>
		<tr align="center">
       		<td valign="top"><hr /></td>
       	</tr>
     	<tr align="center" >
       		<td colspan="2" width="75%" valign="top" class="LetraNegra"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;<strong>RESPONSABLE:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
        </tr>
        
      </table>
</body>
</html>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_conexion->cerrar();
?>