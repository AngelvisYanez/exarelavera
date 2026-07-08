<?php
require_once('../LOGICA/tes_log_compras.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/* Creacion del Objeto de conexion */

$obBD_conexion2 = new Class_Log_Conexion_Comt;
/* Cracion del objeto mysql para las consultas */
$obBD_con2 =  new Class_Log_Datos_Comt; 	  
// Variables de ingreso del modulo ($busqueda,$codigo,$Sem_Cod)
/* Si el semestre esta creado para el cliente entonces de busca los rubros filtrados desde deudas */
		/* Cargado de los resultados de la busqueda de producto sin precio*/
	
		
		$rs_buscta =  $obBD_con2->consulta(sentencias_comf(52, $obBD_con2->parametros($buscador)),$obBD_conexion2->conexion);	
		$row_rs_buscta = $obBD_con2->registros();
		$total_rs_buscta = $obBD_con2->numregistros();		
	
?>
<br>

<?Php 


$iva_cod=stripslashes($iva_cod);
$iva_por=stripslashes($iva_por);
$ice_cod=stripslashes($ice_cod);
$ice_por=stripslashes($ice_por);

?>
<table width="100%" border="1" cellpadding="0" cellspacing="0" id="tbl_resultados">
<?php
	if ($total_rs_buscta > 0) {
		$cont = 1;
		do { 
			$Pro_Cod = $row_rs_buscta['Pro_Cod'];
			/* Consulta de la posible beca y su porcentaje que tenga el estudiante */
			/*$rs_becas = $obBD_con1->consulta(sentencias_tes(73, $obBD_con1->parametros($codigo.'*'.$Pro_Cod.'*'.$Sem_Cod)), 
								$obBD_conexion->conexion);	
			$row_rs_becas = $obBD_con1->registros();
			$total_rs_becas = $obBD_con1->numregistros();	*/
			if ($cont == 1)
			{			
?>
<tr class="Cabecera_ajax">
				<td width="8%"><strong>Cód. Int.</strong></td>
				<td width="4%"><strong>Marca</strong></td>
				<td width="5%"><strong>Adq.</strong></td>
				<td><strong>Descripci&oacute;n</strong></td>
				<td><strong>Ubicaci&oacute;n</strong></td>
				<td><strong>Pvp.</strong></td>		
				<!-- <td><strong>% <?php //echo $row_rs_becas['Tib_Ini']; ?></strong></td> -->								
				<!-- <td><strong>Valor Pagar</strong></td> -->
				<td width="4%"></td>
			</tr>					
			<?Php }//Fin del if ($cont == 1)?>
			<tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Cuerpo_ajax");?> class="Cuerpo_ajax">
				<td><div align="center"><?php echo $row_rs_buscta['Pro_Cod']; ?></div></td>
				<td><div align="center"><?php echo $row_rs_buscta['Mar_Des']; ?></div></td>
				<td><?php echo $row_rs_buscta['Adq_Cor']; ?></td>
				<td><div align="left">&nbsp;<?php echo $row_rs_buscta['Ite_Lar']; ?></div></td>
				<td align="right"><?php echo $row_rs_buscta['Ubi_Des']; ?></td>
				<td align="right"><?php echo formato_numero($row_rs_buscta['Pre_Pvp'],2,2); ?></td>	
				<!-- <td align="center">
				</td> -->
				<!-- <td align="right"><?php //echo number_format(($row_rs_buscta['Pre_Pvp']) - $valor_beca,2); ?>
				</td> -->																													
				<td>
				<div align="center">
				<img src="../../mascaras/model1/imagenes/forward.png" style="cursor:pointer" width="18" height="18" onClick="
                
                
               
                
                nueva_fila_com_ice('c_contenido',<?Php echo $iva_cod; ?>,<?Php echo $iva_por; ?>,<?Php echo $ice_cod; ?>, <?Php echo $ice_por; ?>,'<?php echo $row_rs_buscta['Adq_Cor']; ?>',<?php echo $row_rs_buscta['Adq_Cod']; ?>,'<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $Pec_Cod; ?>', '','<?php echo $row_rs_buscta['Ite_Lar']; ?>',<?php echo $row_rs_buscta['Pro_Cod']; ?>);
                
                
                "></div>						
				</td>
			</tr>
		<?php $cont++;
			} while ($row_rs_buscta = $obBD_con2->fetch_assoc($rs_buscta));
	} else { ?>
			<tr>
				<td colspan="9"><?php echo error_alerta("No hay resultados que mostrar", 1)?></td>
			</tr>
	<?php }?>
	<tr>
		<td colspan="9" align="center"><img src="../../imagenes/ocultar2.jpg" height="12" style="cursor:pointer" alt="Ocultar" onClick="ShowHide('rubros_table')"></td>
	</tr>
</table>

     
<?php
@$obBD_con1->free_result($rs_buscta);
?>
