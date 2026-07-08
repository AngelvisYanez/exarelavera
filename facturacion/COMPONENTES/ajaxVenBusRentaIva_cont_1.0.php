<?Php 
/**
* Descripci�n: Componente Ajax que permite buscar por descripci�n los procentajes de retenci�n, en base a parametros Adquisici�n & Tipo de retenci�n.
* Fecha de actualizaci�n: 07-08-2009.
* Desarrollador: Lewis Chimarro
* Fecha de actualizaci�n: 07-08-2009.
* Desarrollador: Lewis Chimarro
*/

if(isset($ajax_renta_iva_buscar)){ /* inicio if(isset($ajax_renta_iva_buscar))  */
if(isset($Adq)){ /*inicio if(isset($Adq)){ */
	if(isset($Tipo_Rta)){ /*inicio if(isset($Tipo_Rta)){ */
		if($ajax_op=='d')/* inicio if($Tipo_Rta=='R')  */
		  {		
			/**
			* Consulto por descripci�n de Retenci�n o Iva
			* Falta revisar para que filtre por adquisici�n 
			*/
			$rs_iva_bienes=$obBD_con1->getArrayConsulta(339, $Adq.'*'.$Tipo_Rta.'*'.$Pla_Cod.'*'.trim($ajax_renta_iva_buscar).'*'.$Ses_Emp_Cod, $obBD_conexion); 
                        //echo '<br/>vervevrevr';
		  } 
		  else /* else ($Tipo_Rta=='R') */
		  {	    
			/*
			* Consulto por porcentaje de Retenci�n o Iva
			*/
			$rs_iva_bienes=$obBD_con1->getArrayConsulta(363, $Adq.'*'.$Tipo_Rta.'*'.$Pla_Cod.'*'.trim($ajax_renta_iva_buscar).'*'.$Ses_Emp_Cod, $obBD_conexion); 		
		  } /* fin if($Tipo_Rta=='R') */
?>

<div id="Tbl_Rencon">
<table width="100%" border="0" class="fixedHeader01">
<thead>
  <tr>
    <th width="10%"><strong>C&oacute;d. Int </strong></th>
    <th width="10%"><strong>Renta-I.V.A.</strong> </th>
    <th width="37%"><strong>Descripci&oacute;n</strong></th>
    <th width="10%"><strong>Porcentaje(%)</strong></th>
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
	 {   
	 	if (strlen($row_rs_iva_bienes['Ren_Con'])>40)
		{
			$renta= substr($row_rs_iva_bienes['Ren_Con'],0,40).'...';	
		}else{
			$renta= $row_rs_iva_bienes['Ren_Con'];	
		}
	 ?>
  <tr>
    <td align="center" title="<?php if (strlen($row_rs_iva_bienes['Ren_Con'])>40){echo $row_rs_iva_bienes['Ren_Con'];}?>"><?php echo $row_rs_iva_bienes['Ren_Cod']; ?></td>
    <td alin="center" title="<?php if (strlen($row_rs_iva_bienes['Ren_Con'])>40){echo $row_rs_iva_bienes['Ren_Con'];}?>"><?php echo $row_rs_iva_bienes['Ren_Sri']; ?></td>
    <td align="left" id="set1" title="<?php if (strlen($row_rs_iva_bienes['Ren_Con'])>40){echo $row_rs_iva_bienes['Ren_Con'];}?>"><?Php echo marcar_cadena($ajax_renta_iva_buscar, $renta, '#FFFF00', 1);?></td>
    <td align="center" title="<?php if (strlen($row_rs_iva_bienes['Ren_Con'])>40){echo $row_rs_iva_bienes['Ren_Con'];}?>"><?php echo $row_rs_iva_bienes['Ren_Por']; ?>
    </td>
    <td align="center">
    <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="document.getElementById(document.getElementById('Hdd_Ren_Con').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Sri']; ?>'; document.getElementById(document.getElementById('Hdd_Ren_Ide').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Cod']; ?>';
document.getElementById(document.getElementById('Hdd_Ren_Por').value).value = '<?Php echo $row_rs_iva_bienes['Ren_Por']; ?>'; 
document.getElementById(document.getElementById('Hdd_Ren_Con').value).focus(); 
todo_check_renta(<?php echo $row_rs_iva_bienes['Ren_Cod']; ?>,'<?php echo $row_rs_iva_bienes['Ren_Sri']; ?>', <?php echo $row_rs_iva_bienes['Ren_Por']; ?>);<?php if($Tipo_Rta=='R'){ ?>document.getElementById('Btn_RentaEdit['+document.getElementById('Hdd_Txt_Ide').value+']').style.display='<?php if($row_rs_iva_bienes['Ren_Sri']==$Cod_Banano) echo 'block'; else echo 'none'; ?>'<?php } ?>; 
">
        	<i class=" icon-arrow-right icon-white"></i>
        	</button>
		</td>
  </tr>
  <?php  } /* fin  foreach ($rs_iva_bienes); */
	  } 
	  else 
	  { ?>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td><?Php echo error_alerta(" No hay resultados que mostrar, o debe configurar los C�digo de Retenci�n e Iva con el Plan de cuentas respectivo", 1); ?></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <?php } /* fin if($num_row_rs_iva_bienes>0){  */ ?>
  </tbody>
</table>
<?Php   
echo barra_estado(count($rs_iva_bienes));?>
</div>
<?Php
	}else{ /* else if(isset($Tipo_Rta)){ */
		echo error_alerta("<< Error de componente: ajax_com_bus_renta_iva.php >> <br>Descripci�n: No se ha definido la Propiedad: Tipo_Rta<br>
        Tipo_Rta: Variable que contiene el nombre del texto que posse el valor del tipo de retenci�n R o I", 2);
		} /* fin if(isset($Adq)){ */
	}else{  /* else if(isset($Adq)){ */ 
		echo error_alerta("<< Error de componente: ajax_com_bus_renta_iva.php >> <br>Descripci�n: No se ha definido la Propiedad: Adq<br>
        Adq: Variable que contiene el nombre del texto que posse el c�digo de la adquisici�n", 2);	
	    }  /* fin if(isset($Tipo_Rta)){  */
	exit();
} /* fin if(isset($ajax_renta_iva_buscar)) */
?>