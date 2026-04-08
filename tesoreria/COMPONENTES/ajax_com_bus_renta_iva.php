<?Php require_once('../LOGICA/logica.php');

/* Alias: [--]
   Descripción: Componente Ajax que permite buscar por descripción los procentajes de retención, en base a parametros Adquisición & Tipo de retención.
   Fecha de actualización: 07-08-2009.
   Desarrollador: Freddy Jumbo.
*/
if(isset($ajax_renta_iva_buscar)){ /* inicio if(isset($ajax_renta_iva_buscar))  */
if(isset($Adq)){ /*inicio if(isset($Adq)){ */
	if(isset($Tipo_Rta)){ /*inicio if(isset($Tipo_Rta)){ */
		
			   
				if($ajax_op=='d')/* inicio if($Tipo_Rta=='R')  */
				  {		//echo $ajax_renta_iva_buscar;   
				  		/* Consulto el I.V.A de bienes */
						$rs_iva_bienes=$obBD_con1->consulta(sentencias_tes(338, $obBD_con1->parametros($Adq.'*'.$Tipo_Rta.'*'.$Pla_Cod.'*'.$ajax_renta_iva_buscar)), $obBD_conexion->conexion); 
				  } /* */
				  else /* else ($Tipo_Rta=='R') */
				  {	    //echo $ajax_renta_iva_buscar;   
				  		/* Consulto el I.V.A de bienes */
						$rs_iva_bienes=$obBD_con1->consulta(sentencias_tes(361, $obBD_con1->parametros($Adq.'*'.$Tipo_Rta.'*'.$Pla_Cod.'*'.$ajax_renta_iva_buscar)), $obBD_conexion->conexion); 		
				  } /* fin if($Tipo_Rta=='R') */
				  $row_rs_iva_bienes=$obBD_con1->registros();
				  $num_row_rs_iva_bienes=$obBD_con1->numregistros();
?>
<table id="Tbl_Rencon" width="100%" >
<tr>
<td>
<table width="100%" height="20" border="1" cellpadding="0" cellspacing="0" class="Fondo" >
  <tr class="Cabecera_ajax">
    <td width="4%"><strong>C&oacute;d. Int </strong></td>
    <td><strong>Renta-I.V.A.</strong> </td>
    <td><strong>Descripci&oacute;n</strong></td>
    <td><strong>Porcentaje(%)</strong></td>
    <td width="2%" >&nbsp;</td>
  </tr>
  <?Php 
   if($num_row_rs_iva_bienes>0){ /* inicio if($num_row_rs_iva_bienes>0){  */
     do {  /* inicio do{ */  ?>
  <tr class="Cuerpo_ajax">
    <td><div align="center"><? echo $row_rs_iva_bienes['Ren_Cod']; ?></div></td>
    <td><div align="center"><? echo $row_rs_iva_bienes['Ren_Sri']; ?></div></td>
    <td><div align="left"><? echo $row_rs_iva_bienes['Ren_Con']; ?></div></td>
    <td width="16%"><div align="center"><? echo $row_rs_iva_bienes['Ren_Por']; ?>
      
    </div></td>
    <td align="center">
<img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" 
onclick="document.getElementById(document.getElementById('Hdd_Ren_Con').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Sri']; ?>'; document.getElementById(document.getElementById('Hdd_Ren_Ide').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Cod']; ?>';
document.getElementById(document.getElementById('Hdd_Ren_Por').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Por']; ?>'; 
document.getElementById(document.getElementById('Hdd_Ren_Con').value).focus(); 
todo_check_renta(<? echo $row_rs_iva_bienes['Ren_Cod']; ?>,<? echo $row_rs_iva_bienes['Ren_Sri']; ?>, <? echo $row_rs_iva_bienes['Ren_Por']; ?>);
" style="cursor:pointer"  ></td>
  </tr>
  <?  } while ($row_rs_iva_bienes = mysqli_fetch_assoc($rs_iva_bienes)); /* fin  } while ($row_rs_iva_bienes = mysqli_fetch_assoc($rs_iva_bienes)); */
	  } else { ?>
  <tr>
    <td colspan="6" class="Alertas"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
  </tr>
  <? } /* fin if($num_row_rs_iva_bienes>0){  */ ?>
</table>
</td>
</tr>
</table>
<?Php   /* liberar el recordset */
		@$obBD_con1->free_result($rs_iva_bienes);
		
	}else{ /* else if(isset($Tipo_Rta)){ */
		echo error_alerta("<< Error de componente: ajax_com_bus_renta_iva.php >> <br>Descripción: No se ha definido la Propiedad: Tipo_Rta<br>
        Tipo_Rta: Variable que contiene el nombre del texto que posse el valor del tipo de retención R o I", 2);
		} /* fin if(isset($Adq)){ */
	}else{  /* else if(isset($Adq)){ */ 
		echo error_alerta("<< Error de componente: ajax_com_bus_renta_iva.php >> <br>Descripción: No se ha definido la Propiedad: Adq<br>
        Adq: Variable que contiene el nombre del texto que posse el código de la adquisición", 2);	
	    }  /* fin if(isset($Tipo_Rta)){  */
	exit();
} /* fin if(isset($ajax_renta_iva_buscar)) */
?>