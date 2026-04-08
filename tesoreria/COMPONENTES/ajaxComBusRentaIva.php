<?Php 
/**
* Descripción: Componente Ajax que permite buscar por descripción los procentajes de retención, en base a parametros Adquisición & Tipo de retención.
* Fecha de actualización: 07-08-2009.
* Desarrollador: Lewis Chimarro
* Fecha de actualización: 07-08-2009.
* Desarrollador: Lewis Chimarro
*/
if(isset($ajax_renta_iva_buscar)){ /* inicio if(isset($ajax_renta_iva_buscar))  */
if(isset($Adq)){ /*inicio if(isset($Adq)){ */
	if(isset($Tipo_Rta)){ /*inicio if(isset($Tipo_Rta)){ */
				if($ajax_op=='d')/* inicio if($Tipo_Rta=='R')  */
				  {		
					/**
					* Consulto por descripción de Retención o Iva
					* Falta revisar para que filtre por adquisición 
					*/
					$rs_iva_bienes=$obBD_con1->getArrayConsulta(338, $Adq.'*'.$Tipo_Rta.'*'.$Pla_Cod.'*'.trim($ajax_renta_iva_buscar), $obBD_conexion); 
				  } 
				  else /* else ($Tipo_Rta=='R') */
				  {	    
					/**
					* Consulto por porcentaje de Retención o Iva
					*/
					$rs_iva_bienes=$obBD_con1->getArrayConsulta(361, $Adq.'*'.$Tipo_Rta.'*'.$Pla_Cod.'*'.trim($ajax_renta_iva_buscar), $obBD_conexion); 		
				  } /* fin if($Tipo_Rta=='R') */
?>
<div id="Tbl_Rencon">
<table width="100%" border="0" class="fixedHeader01">
<thead>
  <tr>
    <th width="15%"><strong>C&oacute;d. Int </strong></th>
    <th width="22%"><strong>Renta-I.V.A.</strong> </th>
    <th width="37%"><strong>Descripci&oacute;n</strong></th>
    <th width="24%"><strong>Porcentaje(%)</strong></th>
    <th width="2%">&nbsp;</th>
  </tr>
 </thead>
 <tbody>
  <?Php 
   if(count($rs_iva_bienes)>0){ /* inicio if($num_row_rs_iva_bienes>0){  */
	 /**
	 * inicio do{ 
	 */ 
     foreach($rs_iva_bienes as $row_rs_iva_bienes)
	 {   ?>
  <tr>
    <td align="center"><? echo $row_rs_iva_bienes['Ren_Cod']; ?></td>
    <td alin="center"><? echo $row_rs_iva_bienes['Ren_Sri']; ?></td>
    <td align="left"><?Php echo marcar_cadena($ajax_renta_iva_buscar, $row_rs_iva_bienes['Ren_Con'], '#FFFF00', 1);  ?></td>
    <td align="center"><? echo $row_rs_iva_bienes['Ren_Por']; ?>
    </td>
    <td align="center">
    <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="document.getElementById(document.getElementById('Hdd_Ren_Con').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Sri']; ?>'; document.getElementById(document.getElementById('Hdd_Ren_Ide').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Cod']; ?>';
document.getElementById(document.getElementById('Hdd_Ren_Por').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Por']; ?>'; 
document.getElementById(document.getElementById('Hdd_Ren_Con').value).focus(); 
todo_check_renta(<? echo $row_rs_iva_bienes['Ren_Cod']; ?>,<? echo $row_rs_iva_bienes['Ren_Sri']; ?>, <? echo $row_rs_iva_bienes['Ren_Por']; ?>);
">
        	<i class=" icon-arrow-right icon-white"></i>
        	</button>
		</td>
  </tr>
  <?  } /* fin  foreach ($rs_iva_bienes); */
	  } 
	  else 
	  { ?>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td><?Php echo error_alerta(" No hay resultados que mostrar, o debe configurar los Código de Retención e Iva con el Plan de cuentas respectivo", 1); ?></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <? } /* fin if($num_row_rs_iva_bienes>0){  */ ?>
  </tbody>
</table>
<?Php   
echo barra_estado(count($rs_iva_bienes));?>
</div>
<?Php
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