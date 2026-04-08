<?php
/*
* Descripción: Reporte de la opción Totales, Detalle y Puntos de Impresión - Versión 3.0
* Fecha de actualización: 2026-03-02
* Adaptación: Trae Assistant
*/	
extract($_REQUEST);
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		

/*  Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 

/* Adaptacion para filtros del Tab 4 */
$puntos = "AND puntos_imp.Suc_Cod = " . (!empty($Suc_Cod) && $Suc_Cod != 'T' ? $Suc_Cod : $Ses_Suc_Cod);
$parametro = " Caj_Fec BETWEEN '" . $txt_fec_ini . " 00:00:00' AND '" . $txt_fec_fin . " 23:59:59' ";

if (!empty($Cli_Cod) && $Cli_Cod != 'T') {
    $parametro .= " AND ventas.Cli_Cod = " . $Cli_Cod;
}

// Limpiar filtros 'T' para la consulta
$optest_sql = ($optest == 'T') ? '' : $optest;
$tic_cod_sql = ($Tic_Cod == 'T') ? '' : $Tic_Cod;

/* Ejecución de la consulta principal para obtener registros y calcular mínimos/máximos */
$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(1240, $obBD_con1->parametros($parametro.'*'.$optest_sql.'*'.$tic_cod_sql.'*'.$puntos)), $obBD_conexion->conexion);
$total_rs_buscarcarrera = $obBD_con1->numregistros();

// Calcular números mínimos y máximos mostrados
$num_ini = "";
$num_fin = "";

if ($total_rs_buscarcarrera > 0) {
    // Necesitamos recorrer los resultados o hacer una subconsulta para obtener min/max reales de los números mostrados
    $sql_min_max = "SELECT MIN(ventas.Vet_Num) as min_num, MAX(ventas.Vet_Num) as max_num FROM ventas 
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
                    WHERE $parametro " . 
                    ($optest_sql != '' ? " AND ventas.Vet_Est = '$optest_sql'" : "") .
                    ($tic_cod_sql != '' ? " AND ventas.Tic_Cod = '$tic_cod_sql'" : "") .
                    " $puntos";
    $rs_min_max = $obBD_con1->consulta($sql_min_max, $obBD_conexion->conexion);
    $row_min_max = $obBD_con1->fetch_assoc($rs_min_max);
    $num_ini = $row_min_max['min_num'];
    $num_fin = $row_min_max['max_num'];
}

$resultados_total = explode('*', $obBD_con1->calculosConsultaVentas($parametro, $optest_sql, $tic_cod_sql, (!empty($Suc_Cod) && $Suc_Cod != 'T' ? $Suc_Cod : $Ses_Suc_Cod), $obBD_conexion));

?>
<HTML>
	<HEAD>
		<TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
<?php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
     <table width="100%" border="0" align="center">
	 <tr align="center">
	  <td width="100%" valign="top" align="center">
      <?php
		   if ($optest == "A") { $estado = 'Activas'; } 
           else if ($optest == "I") { $estado = 'Anuladas'; }
           else { $estado = 'Todas'; }

           $titulo = "<strong><span class='TITULO_REPORTE_2'>Reporte de Ventas $estado (Detalle)</span></strong>";
           if ($Tic_Cod == "4") {
               $titulo = "<strong><span class='TITULO_REPORTE_2'>Reporte de Notas de Crédito $estado (Detalle)</span></strong>";
           }

		   $subtitulo = "<strong><span class='TITULO_REPORTE'>Desde el ".$txt_fec_ini." Hasta el ".$txt_fec_fin." </span></strong>";
		   $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion); 
      ?>
      </td>
    </tr>	
    <tr>
        <td valign="top">
        <table width="290" border="0" cellpadding="0" cellspacing="0" align="center">
		<tr>
		  <td width="86" class="Texto_Reporte"><div align="center"><strong>Desde Nro.:</strong></div></td>
		  <td width="57" class="Texto_Reporte">&nbsp;<?Php echo $num_ini; ?></td>
		  <td width="83" class="Texto_Reporte"><div align="center"><strong>Hasta Nro.:</strong></div></td>
		  <td width="64" class="Texto_Reporte">&nbsp;<?Php echo $num_fin;  ?></td>
	    </tr>
		</table><br>

	<?if ($total_rs_buscarcarrera != 0) { ?>
    <style>.hide{display:none;} .cortarString{}</style>
			<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse: collapse;">
			  <tr class="TablaRepCompr">
				<td class="TablaRepCompr hide" align="center" width="8%">C&oacute;d. Int.</td>
				<td class="TablaRepCompr" align="center" width="8%">No. Doc.</td>
				<td width="12%" class="TablaRepCompr" align="center">Fecha</td> 
				<td width="15%" class="TablaRepCompr" align="center">C.I./R.U.C.</td> 
				<td width="26%"class="TablaRepCompr" align="center">Cliente</td>
				<td class="TablaRepCompr" align="center">Detalle</td>
				<td width="12%" class="TablaRepCompr" align="center">Total</td> 
			  </tr>
				<?Php 
				while ($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)) { 																
					?> 
						<tr class="Texto_Reporte">
						  <td align="center" valign="top" class="hide"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
						  <td align="center" valign="top"><?php echo $row_rs_buscarcarrera['Vet_Num']; ?></td>
						  <td valign="top" align="center"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
						  <td valign="top" align="center"><?php echo $row_rs_buscarcarrera['Prs_Ced']; ?></td>
						  <td valign="top" class="cortarString"><?PHP echo $row_rs_buscarcarrera['Prs_Ape']." ".$row_rs_buscarcarrera['Prs_Nom']; ?> &nbsp;</td>
						  <td valign="top" align="left">							 
				<?Php 
							/* Consulta del detalle de la factura */
							$rs_detalle = $obBD_con1->consulta(sentencias_tes(37, $obBD_con1->parametros($row_rs_buscarcarrera['Vet_Cod'])), $obBD_conexion->conexion);
							while($row_rs_detalle = $obBD_con1->fetch_assoc($rs_detalle)){
								echo "&#8226; ".$row_rs_detalle['Ite_Cor']. " [" . formato_numero($row_rs_detalle['Vet_Imp'], 2, 2) . "]<br>"; 
							}
							?>
						  </td>
				          <td align="right" valign="top"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Imp'],2,2); ?></td>
                        </tr>
				<?php } ?>						
			</table>
		
			<table width="100%"  border="0" cellpadding="0" cellspacing="0">
				<tr class="Texto_Reporte">
				  <td width="65%">   </td>   
				  <td><strong>Subtotal:</strong></td>
				  <td width="12%"><div align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Tarifa 0%:</strong></td>	  
				  <td><div align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Tarifa diferente a 0%: </strong></td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>IVA:</strong></td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></div></td>
				</tr>	
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Descuento:</strong></td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Total:</strong></td>
				  <td><div align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></div></td>
				</tr>
	  </table>
	 <?php } else { echo error_alerta("No hay resultados que mostrar", 2); } ?>
  </td>
  </tr>
    <tr>
      <td align="center"><div align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></div></td>
    </tr>
</table>	  
</BODY></HTML>
<?php 
@$obBD_con1->free_result($rs_buscarcarrera);
@$obBD_con1->free_result($rs_detalle);
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>