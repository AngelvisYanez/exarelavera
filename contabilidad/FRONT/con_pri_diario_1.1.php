<?	
/**
* @abstract Reporte del libro diario 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-05-01
* Fecha de actualización  2015-05-01
* @author Lewis Chimarro
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_diario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');			
	
/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
/**
* Consulta de la cabecera del reporte 
*/
$row_rs_empresa = $obBD_con1->getRowConsulta(207,$Ses_Suc_Cod,$obBD_conexion);
$total_rs_empresa = $row_rs_empresa['Emp_Nom'] > 0? 1 : 0;

$hoy = date("Y-m-d");

if ($txt_busqueda != "")
{		
	/**
	* Consulta de los comprobantes en base a una cuenta 
	*/
	$rs_compr = $obBD_con1->getArrayConsulta(9,$txt_fec_ini.'*'.$txt_fec_fin.'*'.$txt_busqueda.'*'.$Pec_Cod,$obBD_conexion); 
}
else
{
	/**
	* Consulta de los comprobantes TODOS
	*/
	if($TipDoc!='T')
	{ $parame='AND Tia_Cod='.$TipDoc;}
		
	/**
	* Consulta de los comprobantes TODOS
	*/
	$rs_compr = $obBD_con1->getArrayConsulta(14,$txt_fec_ini.'*'.$txt_fec_fin.'*'.$Pec_Cod.'*'.$parame,$obBD_conexion); 
}
$total_rs_compr = count($rs_compr);
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/print.php"); ?> 
	</HEAD>
<BODY>
     <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr align="center">
        <td colspan="4">&nbsp;<?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "Libro Diario", " ", $obBD_conexion); ?>&nbsp;</td>
        </tr>
	 </table>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">   
	<tr>
        <td valign="top" align="center">
<?php
if (isset($detalle))
{
/**
* Consulta del detallete de la CUENTA 
*/
$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($rs_compr['Pld_Rec'][1])), $obBD_conexion->conexion);
			$row_rs_recur = $obBD_con1->registros();				
?>
  <table width="669" border="0" class="Texto_Reporte">
    <tr>
      <td width="47" align="right">Desde:</td>
      <td width="201"><?Php echo $txt_fec_ini; ?></td>
      <td width="125" align="right">Hasta:</td>
      <td width="387"><?Php echo $txt_fec_fin; ?></td>
    </tr>
    <tr>
      <td align="right">C&oacute;digo:</td>
      <td><?Php echo $row_rs_recur['Pld_Cdc']; ?></td>
      <td align="right">GRUPO:</td>
      <td><?Php echo $row_rs_recur['Pld_Des']; ?></td>
    </tr>
    <tr>
      <td align="right">C&oacute;digo:</td>
      <td><?Php echo $row_rs_compr['Pld_Cdc']; ?></td>
      <td align="right">Cuenta:</td>
      <td><?Php echo $row_rs_compr['Pld_Des']; ?></td>
    </tr>
  </table>
<?php
}//Fin del if (isset($detalle))
?>
<? $row_tipoComp = $obBD_con1->getRowConsulta(316,$TipDoc,$obBD_conexion); ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="12%" class="Texto_Reporte"><strong><? if($TipDoc!='T')
	{ echo "Tipo de Comprobante:";}?></strong></td>
    <td width="88%" class="Texto_Reporte">&nbsp;<? echo $row_tipoComp['Tia_Des'];?></td>
  </tr>
</table>

  <table width="100%"  height="20" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse" class="Texto_Reporte">
    <tr class="Texto_Listados">
      <td width="8%" align="center" bgcolor="#CCCCCC"><strong>No. Int.</strong></td>
      <td width="8%" align="center" bgcolor="#CCCCCC"><strong>No. Com.</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Tip</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Fecha</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Cuentas</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Debe</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Haber</strong></td>
      </tr>
<?Php
if ($total_rs_compr > 0) {
  $puntero_actual=$row_rs_compr['Com_Cod'];
  /**
  * Contador para saber cuantas veces muestra una descipcion del diario 
  */
  $cont = 1;
  $total_debe = 0;
  $total_haber = 0;
  /**
  * Cantidad de asientos 
  */
  $num_asi = 0;
	  
  foreach($rs_compr as $row_rs_compr){ 
	$num_asi++;  
  ?>
    <tr>
      <td colspan="7" align="center"><?Php echo " - ".$num_asi." - "; ?></td>
    </tr>
	<tr>
      <td colspan="7" align="justify"><?Php echo $row_rs_compr['Com_Con']; ?></td>
    </tr>	
    <?php
	 /**
	 * Consulta de los comprobantes 
	 */
 	 $rs_comprobantes = $obBD_con1->getArrayConsulta(10,$row_rs_compr['Com_Cod'],$obBD_conexion); 	 
	 $total_rs_comprobantes = count($rs_comprobantes);
	  
	 foreach($rs_comprobantes as $row_rs_comprobantes){  
    	$mostrar_total = false;
	  	/**
		* Control para reiniciar la presentacion del diario 
		*/ 
	  	if ($puntero_actual != $row_rs_compr['Com_Cod'])
		{
			$puntero_actual=$row_rs_compr['Com_Cod'];
			$cont=1;
		    $total_debe = 0;
		    $total_haber = 0;			
		}
	?>
    <tr class="Texto_Reporte">
      <td align="center"><? if ($cont==1)
							{
	  							echo $row_rs_comprobantes['Com_Cod']; 
							}
							else
							{
								echo "&nbsp;";
							}
							?>	  </td>
      <td align="center"><?Php if ($cont==1)
							{	  						
							  echo $row_rs_comprobantes['Com_Num']; 
							}
							else
							{
								echo "&nbsp;";
							}							 
							?>	  </td>
							  
      <td align="center"><?Php if ($cont ==1)
	  						{
	  							echo $row_rs_comprobantes['Tia_Ini']; 	  
	  						}
							else
							{
								echo "&nbsp;";
							}							 
						  ?>	   </td>	  
      <td width="10%" align="center"><?Php if ($cont ==1)
	  						{
	  							echo $row_rs_comprobantes['Com_Fec']; 
	  						}
							else
							{
								echo "&nbsp;";
							}							 
							?>	  </td>	  	  
      <td><?Php echo $row_rs_comprobantes['Pld_Cdc'].'&nbsp;&nbsp;'.$row_rs_comprobantes['Pld_Des']; ?></td>	  	  
   	  <td width="8%" align="right">&nbsp;
	  					<? if ($row_rs_comprobantes['Asi_Deh'] == 'D')
	  					{
							echo formato_numero($row_rs_comprobantes['Asi_Val'], 2, 4); 
							$debe = $row_rs_comprobantes['Asi_Val'];
							$total_debe = $total_debe + $debe;							
						} 
						else 
						{ 
							echo "&nbsp;"; 
							$debe = 0;
						}?></td>
      <td width="8%" align="right">&nbsp;<? if ($row_rs_comprobantes['Asi_Deh'] == 'H')
	  					{
							echo formato_numero($row_rs_comprobantes['Asi_Val'], 2, 4); 
							$haber = $row_rs_comprobantes['Asi_Val'];
							$total_haber = $total_haber + $haber;							
						} 
						else 
						{ 
							echo "&nbsp;"; 
							$haber = 0;
						}
			?></td>
	  </tr>
	  <?php
		/**
		* Contador para poder mostrar la descripcion una sola vez en la tabla 
		*/
		$cont++;	   
	  } 
	  ?>
		<tr class="Texto_Reporte">
		  <td colspan="5" class="TITULO_REPORTE"><div align="right">TOTAL</div></td>
		  <td align="right"><?Php echo formato_numero($total_debe, 2, 4);?></td>
		  <td align="right"><?Php echo formato_numero($total_haber, 2, 4);?></td>
	    </tr>	  
		<tr>
<?php		  
  } 
 } else { ?>
      <td colspan="7"><?Php echo error_alerta("No hay resultados que mostrar", 2) ?></td>
    </tr>
<? } //Fin del else	?>
  </table>  
  <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr align="center">
      <td colspan="4"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
    </tr>
  </table>
  <?Php 	
//}//Fin del if ($total_rs_comprobantes > 0)
?>
 </td>
  </tr>
</table>	  
</BODY></HTML>
<?Php 
/** 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();
?>