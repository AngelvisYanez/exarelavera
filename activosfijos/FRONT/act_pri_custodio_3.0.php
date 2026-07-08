<?php 
/** 
 @Alias:		 Imprimir
 @Descripción:   Permite la impresión de activos por Custodio
 @Desarrollador: Didimo Zamora.
***********************************
 @Fecha de actualización:	2013-06-10
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_custodio.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	 	



/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Cch;
/** 
 * Creación del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;  
/**
 * Consulta de la cabecera del reporte 
 */
 
 
 
$rs_institucion = $obBD_con1->getRowConsulta(134, $Ses_Suc_Cod,$obBD_conexion);
$total_rs_institucion =  count($rs_institucion);
$row_rs_institucion = $rs_institucion;

$hoy = date("Y-m-d");


if (isset($_POST['codigo']))
		{
			$rs_consultar = $obBD_con1->getArrayConsulta(135,$_POST['codigo'],$obBD_conexion);
			$total_rs_consultar = count($rs_consultar);		
		}		
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>

<?php 	  
/**
 *   Variables para Encabezado
 */
 $Titulo="Custodia de Activos Fijos";
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
 	 <td colspan="4" valign="top" class="TITULO_REPORTE_2">&nbsp;</td>
  </tr>
  <tr>
    <td width="20%" ><span class="Etiqueta1">Institución :</span></td>
    <td width="31%"><span class="LetraNegra"><?Php echo $row_rs_institucion['Emp_Nom'].' - '.$row_rs_institucion['Suc_Des']; ?></span></td>
    <td width="7%">&nbsp;</td>
    <td width="42%">&nbsp;</td>
  </tr>
  <tr>
    <td><span class="Etiqueta1">Fecha de Emisi&oacute;n :</span></td>
    <td><span class="LetraNegra"><?php echo $hoy ?></span></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <?php $Nombre = $rs_consultar[0]['Nombre']; $Cedula = $rs_consultar[0]['Prs_Ced'];?>
  <tr>
    <td><span class="Etiqueta1">Nombre del Custodio :</span></td>
    <td><span class="LetraNegra"><?php echo $Nombre; ?></span></td>
    <td><span class="Etiqueta1">Cédula :</span></td>
    <td><span class="LetraNegra"><?php echo $Cedula; ?></span></td>
  </tr>
</table>
<br/>
<table width="80%" border="1" align="center" cellspacing="0" cellpadding="0">
<tr class="Cabecera1">
    <td width="5%"> Ord.</td>
    <td width="5%">Cod. Art.</td>
    <td width="15%">Departamento</td>
    <td width="35%">Nombre del Art&iacute;culo</td>
    <td width="15%">Observación</td>
    <?Php 
		 /**
			 * seleccionar toodos los campos de busqueda
			 */
			 $td=0;
			$rs_camp = $obBD_con1->getArrayConsulta(140,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp); 
		  	if($total_rs_camp > 0){									
				foreach($rs_camp as $row_rs_camp){
				?>
					<th width="20%">
					<?php echo $row_rs_camp['Cam_Cor']; $td +=1;?>
                    </th>
			   <?php }//if($total_rs_camp > 0){
			}
	
	?>  
    <td width="10%">Estado</td>
     <td width="5%">Costo</td> 
</tr>
 <?php if( $total_rs_consultar > 0){
	 
	 $Total=0;
    foreach($rs_consultar as $row_rs_consultar){
        $i++;
    ?>
<tr>
    <td class="LetraNegra" align="center"><?php echo $i;?></td>
    <td class="LetraNegra" align="center"><?php echo $row_rs_consultar['Act_Cod'];?></td>
    <td class="LetraNegra" align="left"><?php echo $row_rs_consultar['Dep_Des'];?></td>
    <td class="LetraNegra"><?php echo $row_rs_consultar['Act_Des'];?></td>
    <td class="LetraNegra" align="left"><?php echo $row_rs_consultar['Act_Obs'];?></td>
   <?php 		
			$rs_camp = $obBD_con1->getArrayConsulta(140,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp);
			if ($total_rs_camp> 0){
				foreach($rs_camp as $row_rs_camp){
						$rs_val_Camp =  $obBD_con1->getRowConsulta(141, $row_rs_buscar['Act_Cod'].'*'. $row_rs_camp['Cam_Cod'],$obBD_conexion);
					?>
					<td align="center" width="16%">
						<?Php echo $rs_val_Camp['Act_Val'] ?>                
					</td>
					<?php
				}
		 	 }
		  ?>
   
    <td class="LetraNegra" align="center"><?php echo $row_rs_consultar['Est_Des'];?></td>
    <td class="LetraNegra" align="right"><?php if($row_rs_consultar['Act_Val']==0){ echo "0.00";}else{
				echo formato_numero($row_rs_consultar['Act_Val'], 2, 1); $Total= $Total+ $row_rs_consultar['Act_Val'];}?>
             </td>
    
    
</tr>
<?php 	 
	/**
	 * Almacena en un arreglo las observaciones del los activos.
	 */
	//$Observaciones[$i] = $row_rs_consultar['Act_Obs'];
 }//fin foreach($rs_consultar as $row_rs_consultar){
	 ?>
	 <tr class="Fondo">
         	
            
        	 <?Php
        if ($total_rs_camp> 0){
			$total_rs_camp = $total_rs_camp+5;?>          
				<td <?php echo "colspan='".$total_rs_camp."'" ?>  >&nbsp;</td>
			<?php	
		 	 }
			 else{
			  echo "<td colspan='6'>&nbsp;</td>";}
		  ?>         	
            <td  style="font-size:14px"  class="LetraNegra" align="right"><strong>Total</strong></td>
            <td  style="font-size:14px"  class="LetraNegra" align="right"><?Php echo formato_numero($Total, 2, 4);?></td>
        </tr>  
	 <?php
    }?>
</table>
<br>
<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0" class="Texto_Reporte">
	
    <?Php 
	/*
		foreach($Observaciones as $Obser => $valor) { 
			echo  "<tr class='Etiqueta1' >";			
			echo " <td width='55%' align='left' valign='top'><span class='Etiqueta1'> &nbsp; El Ord: ".$Obser." tiene la Observación: ".$valor." </span></td> "; 
			echo " </tr>";
		}*/
		
	?>   
    
    
<tr >
      <td width="25%" style="font-size:12px" align="justify"  >
      
      <p>&nbsp;</p>
      <p><b>En la ciudad de Machala y en esta fecha, se procede a realizar la legalización del traspaso de responsabilidad y custodia de los bienes a usted entregados por el Ministerio de Cordinación de Seguridad - Centro Zonal de Seguridad ECU-911, para el normal desempeño de sus labores, cumpliendo con lo señalado en el Reglamento General Sustitutivo para el Control de Manejo y Control de los Bienes del Sector Público(Art. 3 - Art. 92) y en el Manual General de Administración y Control de los Activos fijos del Sector Público(Acuerdo No. 12 CG), publicado en el suplemento del Registro Oficial No. 59 Capitulo III, del 7 de Mayo de 1997 y de acuerdo con lo establecido en el manual de funciones. Para constancia de lo antes detallado firmamos por triplicado. </b></p></td>   
    </tr>
</table>


<br><br><br>
<table width="80%" border="0" align="center" cellpadding="2" cellspacing="0" class="Texto_Reporte">
<tr>
  <td valign="top" align="center"><span class="Etiqueta1">RECIBI CONFORME :</span></td>
  <td valign="top" align="center"><span class="Etiqueta1">ENCARGADO A.F. :</span></td>
   <td valign="top" align="center"><span class="Etiqueta1">ENTREGUE CONFORME :</span></td>
  </tr>
<tr>
  <td valign="top" align="center">&nbsp;</td>
  <td valign="top" align="center">&nbsp;</td>
   <td valign="top" align="center">&nbsp;</td>
  </tr>
<tr>
<td valign="top" align="center">___________________________<br>    </td>
<td valign="top" align="center">___________________________<br>    </td>
<td valign="top" align="center">___________________________<br>    </td>
</tr>
<tr>
  <td height="19" align="center" valign="top"><span class="LetraNegra"><?php echo $Nombre;?></span><br/><span class="LetraNegra"><?php echo $Cedula;?></span></td>
  <td align="center" valign="top">Responsable Administrativo-Financiero</td>
   <td align="center" valign="top">Inventariadora de activos Fijos</td>
  </tr>
</table>   

</BODY>
</HTML>
<?php
/**
 *  Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
 
?>