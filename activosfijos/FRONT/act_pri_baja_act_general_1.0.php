<?php 
/** 
 @Alias:		 Imprimir :) drzm_12@
 @Descripción:   Permite la mimprimir las bajas de los activos fijos
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
		 if($Op_aux == 3){
			$Titulo="Lista de Activos dados de Baja Desde el ".$Fec1_aux." Hasta el ".$Fec2_aux;
		 }
		 else
			
			$rs_Est_Mot = $obBD_con1->getRowConsulta(9,$Baj_Mot_aux, $obBD_conexion);
			
			$Titulo="Lista de Activos dados de baja por [".$rs_Est_Mot['Est_Des']."]";
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
<p>
	<?Php  
	if(isset($Op_aux)){ 
		/**
		 * Consultar los activos dados de baja por motivo y por fecha segun corresponda
		 */
		if($Op_aux == 3){
			$rs_bus_Motivo = $obBD_con1->getArrayConsulta(8,$Fec1_aux.'*'.$Fec2_aux.'*'.$Ses_Emp_Cod, $obBD_conexion);	
		}else
			$rs_bus_Motivo = $obBD_con1->getArrayConsulta(7,$Baj_Mot_aux.'*'.$Ses_Emp_Cod, $obBD_conexion);		 
		?>
		<table align="center" width="80%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
			<thead>
				<tr class="Cabecera1">
					<th width="7%">Cód. Int.</th>
					<th width="15%">Secuencial</th>
					<th width="30%">Descripci&oacute;n</th>
					<th width="10%">Fecha</th>
					<th width="15%">Destino</th>
					<th width="20%">A quien?</th>
					<th width="8%">Valor</th>			
				</tr>
			</thead>
			<tbody>
		<?Php 
		$anulada=0;
		if (count($rs_bus_Motivo) > 0){  
			foreach($rs_bus_Motivo as $row_rs_Mot){   					
		?>
			<tr>
				<td align="center" class="Texto_Reporte"><?php echo $row_rs_Mot['Baj_Cod'];?></td>
				<td align="left" class="Texto_Reporte"><?Php echo $row_rs_Mot['Act_Cdc'];?></td>
				<td align="left" class="Texto_Reporte">&nbsp;<?php echo  $row_rs_Mot['Act_Des'];?></td>						
				<td align="center" class="Texto_Reporte"><?php echo $row_rs_Mot['Baj_Fba'];?></td>
				<td align="left" class="Texto_Reporte"><?Php echo $row_rs_Mot['Baj_Des'];?> </td>
				<td align="center" class="Texto_Reporte" ><?Php echo $row_rs_Mot['Baj_Qui'];?> </td>
				<td align="left" class="Texto_Reporte" ><?Php echo $row_rs_Mot['Baj_Val'];?> </td>			
			  </tr>
		<?Php 
			} //Fin foreach($row_rs_buscar as $row_rs_buscar){      
		}
		?>	
			</tbody>
		</table>                    
		<?php
	} //fin if(isset($Op_aux))     
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
