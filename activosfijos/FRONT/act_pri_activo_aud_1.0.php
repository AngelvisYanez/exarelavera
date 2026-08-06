<?php 
/** 
 @Alias:		 Imprimir :) drzm_12@
 @Descripci�n:   Permite la mimprimir los controles de auditoria por Custodio
 @Desarrollador: Didimo Zamora.
***********************************
 @Fecha de actualizaci�n:	2013-07-25
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo_aud.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	 
	


/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_ActAu;
/** 
 * Creaci�n del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;  
 
/**
 * Consulta de la instituci�n del usuario. 
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
<title><?Php echo $Ses_Sys_Nom; ?></title>
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script type="text/javascript" src="../VALIDACIONES/Validaciones.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<?php 	  
/**
 * Variables para Encabezado
 */
 $Titulo="Control de Tenencia de Activos Fijos";
 $Subtitulo="";	
 ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
  	<tr align="center">
    	<td>
           <table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr align="center">
                  <td colspan="4" ><?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod,$Titulo,$Subtitulo,$obBD_conexion)?></td>
                </tr>
           </table>  
      </td>
  </tr>
</table>
<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
		<td width="12%" align="right" ><span class="LetraNegra">Instituci�n :</span></td>
<td width="32%"><span class="LetraNegra">&nbsp;<?Php echo $row_rs_institucion['Emp_Nom'].' - '.$row_rs_institucion['Suc_Des']; ?></span></td>
		<td width="7%">&nbsp;</td>
		<td width="49%">&nbsp;</td>
  </tr>
  <tr>
		<td align="right"><span class="LetraNegra">Fecha:</span></td>
	<td><span class="LetraNegra">&nbsp;<?php echo $rs_ControlAud['Aud_Fec'];?></span></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
  </tr>
  <tr>
		<td align="right"><span class="LetraNegra">Custodio :</span></td>
		<td><span class="LetraNegra">&nbsp;<?php echo $rs_ControlAud['Custodio']; ?></span></td>
		<td align="right"><span class="LetraNegra">C�dula :</span></td>
		<td><span class="LetraNegra">&nbsp;<?php echo $rs_ControlAud['Prs_Ced'] ?></span></td>
  </tr>
</table>
 <p>
<?php		
if (isset($Aud_Int)){
	/**
	 * Consulto los activos  del control por cod de audioria
	 */	
	$rs_consultar = $obBD_con1->getArrayConsulta(10,$Aud_Int, $obBD_conexion);
	$total_rs_consultar = count($rs_consultar);		  		
?> 
</p>
<table  align="center" width="80%" style="border-collapse:collapse" border="1" cellpadding="0" cellspacing="0" >      
	<tr class="Cabecera1">
	  <td width="5%" align="center">Ord.</td>
	  <td width="8%" align="center"> Cod. Int.</td>
	  <td width="37%" align="center">Descripci&oacute;n del Activo</td>
	  <td width="10%" align="center"> Estado</td> 
	  <td width="15%" align="center"> Auditor Estado</td>                  
	  <td width="25%" align="center">Observaciones</td>           
	</tr> 
<?Php 
/**
* Recorrido del bucle de activos por codigo del custodio
*/
if ($total_rs_consultar>0){
  $i=0;
foreach($rs_consultar as $row_rs_consultar){ 
  $i++;			  
?>
	<tr>
		<td class="LetraNegra" align="center"><?Php echo $i;?> </td>
		<td align="center" class="LetraNegra"><?Php echo $row_rs_consultar['Act_Cod'];?></td>
		<td class="LetraNegra"><?Php echo $row_rs_consultar['Act_Des'];?></td>
		<td align="center" class="LetraNegra">
<?Php
		/**
		 * Consulta el estado  l�gico del activo.
		 */
          $rs_Estado1 = $obBD_con1->getRowConsulta(14,$row_rs_consultar['Est_Act'], $obBD_conexion);
		  echo $rs_Estado1['Est_Des'];
?>
        </td>
		<td class="LetraNegra" align="center"><?Php echo $row_rs_consultar['Est_Des'];?></td>     
		<td class="LetraNegra"><?Php echo $row_rs_consultar['Aud_Obs'];?></td>
	</tr>  
<?Php 
}// fin  de foreach($rs_buscar as $row_rs_buscar){ 
}// fin de if ($total_rs_buscar>0){
?>
</table>  
   <?php	
}
?>
<p>
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
        <tr>
        	 <td class="LetraNegra">Firma del Auditado: __________________________________</td>            
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
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>