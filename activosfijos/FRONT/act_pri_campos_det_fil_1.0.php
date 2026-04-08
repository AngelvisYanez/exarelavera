<?php 
/**  
  * Descripción: Permite la impresión en pantalla de activo fijo
  * Desarrollador:			Didimo Zamora
  * Fecha de actualización:	2011-05-21  
  */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../LOGICA/act_log_campos_det.php');  	  
	 
/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");

/**
 * Consulta las Sucursales 
 */
$rs_suc_act = $obBD_con1->getRowConsulta(422, $Ses_Emp_Cod, $obBD_conexion);
$total_rs_suc_act = count($rs_suc_act);

if (isset($txt_bus)){
	/**
	 * Consulta de la cabecera del reporte 
	 */
	$rs_institucion = $obBD_con1->getRowConsulta(134,$Ses_Suc_Cod, $obBD_conexion);
	$total_rs_institucion = count($rs_institucion);
	$row_rs_institucion = $rs_institucion;		
  }
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
<style type="text/css">
<!--
.Estilo1 {font-size: 16px}
.Estilo2 {font-size: 18px}
-->
</style>
</head>
<body class="Cuerpo">

<?php 	  
/**
 *   Variables para Encabezado
 */
 
 $Titulo="LISTA DE ACTIVOS FIJOS FILTRADA POR: [".$_POST['txt_busqueda_pri']."]";
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
  if(isset($_POST['txt_busqueda_pri']))
  { 
	if ($Tip_Bus == 'L')//Busqueda por coincidencia
	{
		/**
		 * Busqueda del activo x medio de la descripcion (like)
		 */
		$rs_buscar = $obBD_con1->getArrayConsulta(429,strtoupper(trim($txt_busqueda_pri)).'*'.$tipo_a, $obBD_conexion);		
		$total_rs_buscar = count($rs_buscar);					
	}
	else //Busqueda exacta
	{
		/**
		 * Busqueda del activo x medio de la descripcion (igual)
		 */
		$rs_buscar = $obBD_con1->getArrayConsulta(664,strtoupper(trim($txt_busqueda_pri)).'*'.$tipo_a, $obBD_conexion);						
		$total_rs_buscar = count($rs_buscar);					
	}	
	?>
    <p>		
	<table align="center" width="80%" border="1" cellpadding="0" cellspacing="0">
      <tr class="Cabecera1">
		  <td class="Texto_Reporte" width="4%">Cód. Int.</td>
          <td class="Texto_Reporte" width="19%">SubGrupo</td>
		  <td class="Texto_Reporte" width="32%">Descripci&oacute;n </td>
		  <td class="Texto_Reporte" width="15%">Secuencial</td>
          <?Php 
		  	 /**
			 * seleccionar toodos los campos de busqueda
			 */
			$td=0;
			$rs_camp = $obBD_con1->getArrayConsulta(660,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp); 
		  	if($total_rs_camp > 0){									
				foreach($rs_camp as $row_rs_camp){
				?>
					<td class="Texto_Reporte" width="9%">
					<? echo $row_rs_camp['Cam_Cor']; $td +=1; ?>
                    </td>
			   <? }//if($total_rs_camp > 0){
			}?>         
          	<td class="Texto_Reporte" width="8%">Estado</td>
          </tr>
	  <?Php 
	 if ($total_rs_buscar > 0){  		
	  foreach($rs_buscar as $row_rs_buscar){  
	  ?>     
	  <tr>
          <td class="LetraNegra" align="center"><?php echo $row_rs_buscar['Act_Cod'];?></td>
          <td class="LetraNegra" align="center"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Tia_Des'],'#FFFF00', 1);?></td>
          <td class="LetraNegra" align="center"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Act_Des'],'#FFFF00', 1);?></td>
          <td class="LetraNegra"><?php echo  $row_rs_buscar['Act_Cdc'];?></td>	      
<?php 		
			$rs_camp = $obBD_con1->getArrayConsulta(660,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp);
			if ($total_rs_camp> 0){
				foreach($rs_camp as $row_rs_camp){
						$rs_val_Camp =  $obBD_con1->getRowConsulta(661, $row_rs_buscar['Act_Cod'].'*'. $row_rs_camp['Cam_Cod'],$obBD_conexion);
					?>
					<td  class="LetraNegra" align="center" >
						<?Php echo $rs_val_Camp['Act_Val'] ?>                
					</td>
					<?
				}
		 	 }
		  ?>         
          <td  class="LetraNegra" align="center">
           <?php echo $row_rs_buscar['Act_Est'];?>
          </td>		
	  </tr>
	  <?Php } //fin foreach($rs_buscar as $row_rs_buscar){     
  	  }else{
  	  ?>
      	<tr>
        	<td  class="LetraNegra">&nbsp;</td>
            <td  class="LetraNegra">&nbsp;</td>
            <td  class="LetraNegra">&nbsp;</td>
            <td  class="LetraNegra">&nbsp;</td>
            <?php
            if($td > 0){
				?>
				 <td class="LetraNegra">&nbsp;</td>
                <?Php
			}
			?>          
           	<td  class="LetraNegra">&nbsp;</td>
        </tr>
      <? } // fin del if ($total_rs_buscar > 0)?>
	</table>                       	     
<?	
	}// fin de if(isset($txt_busqueda_pri))
?>	
</body>
</html>
<?Php 
$obBD_conexion->cerrar();
?>