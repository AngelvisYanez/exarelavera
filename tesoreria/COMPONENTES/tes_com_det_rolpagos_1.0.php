<? 
/*
Leyenda
Alias: 					
Descripcion:			Componente que pemite visualizar las personas segun el tipo de Rol con sus respectivos Ingresos y Egresos
						del registrar y modificar
Fecha de Actualizacion:	20/07/2010
Desarrollador:			Angelica Gálvez
Fecha de Actualizacion:	24/01/2011
Desarrollador:			Lewis Chimarro
*/
if(isset($Com_Are_Cod))
{ 
	/* Consulta campos ingresos del rol de pagos*/
	$rs_cam_ing= $obBD_con1->consulta(sentencias_tes($Com_Sql_Campos_Cab, $obBD_con1->parametros($Com_Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_cam_ing  = $obBD_con1->registros();
	$total_rs_cam_ing = $obBD_con1->numregistros();		
?>
<div class="fullScreenTable">
<table width="100%" border="1" cellpadding="0" cellspacing="0" id="tbl_personal">
<thead>       
<th width="3%">N&ordm;</th>
<th width="40%" align="center" >Personal.</th>
	<?php          
	$i=1;
	$j=0;
	$aux="E";
	do{	
		   $tip_cam[$i]=$row_rs_cam_ing['Cam_Tip'];
		   $hdd_cam_ing_egr[$i]=$row_rs_cam_ing['Cam_Cod'];
		   $hdd_cam_porc[$i]=$row_rs_cam_ing['Cam_Por']; 
		   $hdd_cam_ing_egr_cal[$i]=$row_rs_cam_ing['Cam_Cal']; 
		   $hdd_cam_ing_egr_req[$i]=$row_rs_cam_ing['Cam_Req']; 
		   $vis[$i]=$row_rs_cam_ing['Cam_Vis']; 
		   if ($vis[$i] != "N"){
		   		$cadena=$cadena."*".$row_rs_cam_ing['Cam_Cod'];		
		   }

	if ($tip_cam[$i] == $aux){$j++; ?>
	<th align="center">T. INGRESOS</th>	  
	<? $aux=" ";
		} ?>
																							

    	<th align="center" id="td[<? echo $i;?>]" title="<?php echo $row_rs_cam_ing['Cam_Des']; ?>" width="1">
		<?php echo $row_rs_cam_ing['Cam_Dec'];?>		</th>		
		<?Php		
		if ($vis[$i] == "N")
		{ //Control para ocultar los rubros q poseen "Cam_Vis=N" de la tabla Campo_rol?> 
			<script language="javascript">
  			fila = document.getElementById("td[<? echo $i;?>]");
    		fila.style.display = "none"; //ocultar fila 
            </script>
			<?
		}//FIn del if ($row_rs_cam_ing['Cam_vis']; == "S") 
		$i++; ?>
	<? }while($row_rs_cam_ing = $obBD_con1->fetch_assoc($rs_cam_ing)); ?>		
    <input type="hidden" size="30" id="hdd_cod_camp" name="hdd_cod_camp" value="<? echo $cadena; ?>"/>
	<th align="center">T. EGRESOS.</th>
    <th>LIQ. RECIBIR</th>
 </tr>
  </thead>
   <tbody>
  <?php 	
  /* Consulta del personal de la empresa*/ 
  $rs_per_rol = $obBD_con1->consulta(sentencias_tes($Com_Sql_Personal, $obBD_con1->parametros($Com_Personal)), $obBD_conexion->conexion);			
  $row_rs_per_rol = $obBD_con1->registros();
  $total_rs_per_rol = $obBD_con1->numregistros();	
  $td_cont=1; 
  $cont=0;
  $var_c=0;

  if ($total_rs_per_rol > 0){ //inicio IF $rs_per_rol
  do{  
  $var_c++; 
   $cont++; 
   $ing_java = "";
   $ing_java2 = "";
   $hdd_cam_ingre_dis[$cont]=$row_rs_per_rol['Dis_Cod'];   
  ?>
 <tr>
   	<td align="center"><? echo $cont; ?></td>
    <td align="left" title="<? echo $row_rs_per_rol['Tic_Des'];?>" width="40%">
	<input name="hdd_nombre" type="text" value="<? echo "Cód.: ".$row_rs_per_rol['Dis_Cod']." ".$row_rs_per_rol['Prs_Ape'].' '.$row_rs_per_rol['Prs_Nom']; ?>"size="50"  readonly="readonly" style=" border:none;text-align:left;background:none">
	
	<? 
	/* CALCULO CAMPOS INGRESOS */
	$nombre=$row_rs_per_rol['Prs_Ape'].' '.$row_rs_per_rol['Prs_Nom'];
	$cont_caja_ingre=0;
	$aux="E";
	unset($Fnd_Res);
	do{ 
		/* Define el codigo del empleado */
		$Cod_Emp=$row_rs_per_rol['Dis_Cod'];
		/* Contador para las cajas de texto */
		$cont_caja_ingre++;	
		$cod=$hdd_cam_ing_egr[$cont_caja_ingre];
		$porc=$hdd_cam_porc[$cont_caja_ingre];
		
		/* Condicion para saber cuando hay campos de egreso */
		if($tip_cam[$cont_caja_ingre] == $aux)
		{
		?>
		<td id="Det[<? echo $cont?>]" width="1" align="right">
			<input type="text" name="txt_t_ingre[<?php echo $cont;?>]" id="txt_t_ingre[<?php echo $cont;?>]" value="<?Php echo round($suma_fila_i[$cont],2); ?>" size="4" 
            readonly="readonly" style=" border:none;text-align:right;background:none" />
			<input type="hidden" name="hdd_t_ingre[<?php echo $cont;?>]" id="hdd_t_ingre[<?php echo $cont;?>]" 
            value="<?Php echo $ing_java;?>" />		</td>
		<? $aux=" "; 
		} // Fin  del else	if($tip_cam[$cont_caja_ingre] != $aux) ?>
		<td align="right" <? if ($vis[$cont_caja_ingre] == "N"){?> id="<? echo "det[".$td_cont."]"; ?>"<? }?>>
        <?  
		/* Control para los modulos que se agregan a cada campo */
		$param[0] = $cod;//Codigo del campo
		$param[1] = $Cod_Emp;//Codigo del empleado
		$modulo_rol = configura_campo($param, $obBD_con1, $obBD_conexion);  
		
		/* C O N T R O L    P A R A    M O D I F I C A C I O N    D E    R O L E S    D E    P A G O */
		if ($Com_Tipo == 'M')//Modificacion
		{ 
			/* Consulta del valor de un campos del rol de pago */
			$rs_campo_valor = $obBD_con1->consulta(sentencias_tes(890, $obBD_con1->parametros($Com_Are_Cod.'*'.$Cod_Emp.'*'.$cod)), $obBD_conexion->conexion);	
			$row_rs_campo_valor = $obBD_con1->registros();

			/* Evalua si son campos de ingreso o egreso y visible si */
			if($tip_cam[$cont_caja_ingre] == 'E' and $vis[$cont_caja_ingre] == 'S')//Egreso
			{						
				$suma_fila_e[$cont] = $suma_fila_e[$cont] + round($row_rs_campo_valor['Rol_Val'],2);
				$suma_colum_e[$cod] = $suma_colum_e[$cod] + round($row_rs_campo_valor['Rol_Val'],2);
			}
			elseif($vis[$cont_caja_ingre] == 'S')//Ingreso
			{
				$suma_fila_i[$cont] = $suma_fila_i[$cont] + round($row_rs_campo_valor['Rol_Val'],2);
				$suma_colum_i[$cod] = $suma_colum_i[$cod] + round($row_rs_campo_valor['Rol_Val'],2);				
			}
		}//Fin del if ($Com_Tipo == 'M')
		elseif ($Com_Tipo == 'A')//Alta
		{ 
			/* Evalua si son campos de ingreso o egreso y visible si */			
			if($tip_cam[$cont_caja_ingre] == 'E' and $vis[$cont_caja_ingre] == 'S')//Egreso
			{
				$suma_fila_e[$cont] = $suma_fila_e[$cont] + round($modulo_rol['Value'][0],2);		
				$suma_colum_e[$cod] = $suma_colum_e[$cod] + round($modulo_rol['Value'][0],2);				
			}
			elseif($vis[$cont_caja_ingre] == 'S')//Ingreso
			{
				$suma_fila_i[$cont] = $suma_fila_i[$cont] + round($modulo_rol['Value'][0],2);			
				$suma_colum_i[$cod] = $suma_colum_i[$cod] + round($modulo_rol['Value'][0],2);								
			}			
		}//Fin del elseif ($Com_Tipo == 'M')

		/* C O N T R O L    P A R A    M O D I F I C A C I O N    D E    R O L E S    D E    P A G O */		
		?>
		<?php
		/*Consulta los campos afectado en la formula*/
	  	
		$rs_campoformula = $obBD_con1->consulta(sentencias_tes(898, $obBD_con1->parametros($cod)), $obBD_conexion->conexion);	
		$row_rs_campoformula = $obBD_con1->registros();	
		$total_campoformula = $obBD_con1->numregistros();		
		 ?>
		<input type="text" name="hdd_ing_egr[<? echo $cont;?>,<? echo $cod;?>]" style="border:none;text-align:right;background:none" id="hdd_ing_egr[<? echo $cont;?>,<? echo $cod; ?>]" size="4" <?php if($hdd_cam_ing_egr_cal[$cont_caja_ingre] == "S"){ echo "readonly='true'";}?> 
            value="<?php if ($Com_Tipo == 'A'){ if (count($modulo_rol['Value']) == 1){ echo $modulo_rol['Value'][0]; }else{ echo round($porc,4); } 
				}elseif ($Com_Tipo = 'M')
				{  
					/* Control para asignar 2 decimales a los valores y 3 a los porcentajes internos */
					if ($vis[$cont_caja_ingre] == 'S')
					{
						echo round($row_rs_campo_valor['Rol_Val'],2); 
					}
					else
					{
						echo round($row_rs_campo_valor['Rol_Val'],3); 
					}
				}?>" 
           
            onkeypress="return validar_decimal(event)"  
            onkeyup="<? echo formula_rol($cont, $cod, $modulo_rol['Event'], $obBD_con1, $obBD_conexion);  ?> <?php  if($total_campoformula>0){ do{?>SumaCamposRol(this.form,  <?php echo $total_rs_per_rol;?>, <?php echo $row_rs_campoformula['Cam_Cod'];?>,<?php echo $cont;?>);<?php }while($row_rs_campoformula = $obBD_con1->fetch_assoc($rs_campoformula));} ?> SumaCamposRol(this.form,  <?php echo $total_rs_per_rol;?>, <?php echo $cod;?>,<?php echo $cont;?>);SumaColumnas(this.form, <?php echo $total_rs_per_rol;?>)" tabindex="<? if ($vis[$cont_caja_ingre] != "N"){ 
			echo $cont;}?>"/>
		<input type="hidden" name="hdd_ingreso_egreso[<? echo $cont;?>,<? echo $cod; ?>]" id="hdd_ingreso_egreso[<? echo $cont;?>,<? echo $cod; ?>]" value="<? echo $cod;?>"/>		</td>
		<? if ($vis[$cont_caja_ingre] == "N"){ //Control para ocultar los rubros q poseen "Cam_Vis=N" de la tabla Campo_rol ?> 		
			<script language="javascript">
			fila = document.getElementById("det[<? echo $td_cont;?>]");
    		fila.style.display = "none"; //ocultar col
			</script>
		<? }//Fin if ($vis[$cont_caja_ingre] == "N")
		/* Concatena todos los campos que nos sera visibles */
		if ($vis[$cont_caja_ingre] != "N")
		{
			$ing_java= $ing_java."hdd_ing_egr[".$cont.",".$cod."]*";
		}//Fin del if ($vis[$cont_caja_ingre] != "N")
	/* Concatena los nombres de los campos que calculan el total */
	if($tip_cam[$cont_caja_ingre]=="E")
    {
	 if ($vis[$cont_caja_ingre] != "N")
	 {
		$ing_java2= $ing_java2."hdd_ing_egr[".$cont.",".$cod."]*"; 
	 }
	}//Fin del if($tip_cam[$cont_caja_ingre]=="E")
		/* Contador para el id de las columnas */
		$td_cont++; 
	}while ($cont_caja_ingre<$total_rs_cam_ing);	
	?>
	<td width="1">
	<input type="text" name="txt_t_egre[<? echo $cont;?>]"  id="txt_t_egre[<? echo $cont;?>]" value="<?Php echo round($suma_fila_e[$cont],2); ?>"  size="5" readonly="true" style="text-align:right;border:none;background:none"/>
	<input type="hidden" name="hdd_t_egre[<? echo $cont;?>]" id="hdd_t_egre[<? echo $cont;?>]" value="<? echo $ing_java2;?>" />	</td>
	<td>
	<input type="text" name="txt_t_liq[<? echo $cont;?>]" id="txt_t_liq[<? echo $cont;?>]" value="<?Php echo round($suma_fila_i[$cont] - $suma_fila_e[$cont],2); ?>"  size="5" readonly="true" 
	style=" text-align:right;border:none;;background:none <?php if (round($suma_fila_i[$cont] + $suma_fila_e[$cont],2)!=0){ ?> background:#CCCCCC <?Php }else{ ?>background:#F00 <?Php } ?>" />   </td>
  </tr>
<? } while($row_rs_per_rol = $obBD_con1->fetch_assoc($rs_per_rol));  //Fin del IF $rs_per_rol ?>
 
 <tr>
   <td colspan="2" align="rigth">TOTAL:</td>
   <?php
   /* Consulta campos ingresos del rol de pagos*/
	$rs_campos= $obBD_con1->consulta(sentencias_tes($Com_Sql_Campos_Ing, $obBD_con1->parametros($Com_Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_campos  = $obBD_con1->registros();
	$total_rs_campos = $obBD_con1->numregistros();	
    $k=0;
	do{
	$k++;?>
   <td width="1"><input type="text" name="hdd_ingre[<? echo $row_rs_campos['Cam_Cod'];?>]" id="hdd_ingre[<? echo $row_rs_campos['Cam_Cod'];?>]" value="<?Php echo round($suma_colum_i[$row_rs_campos['Cam_Cod']],2); ?>" size="5" style=" border:none;text-align:right;background:none" /></td>
   <?php 
     /* Almacena el total de ingresos */
   	 $total_colum_i = $total_colum_i + $suma_colum_i[$row_rs_campos['Cam_Cod']];
   }while($row_rs_campos = $obBD_con1->fetch_assoc($rs_campos));
   ?>
   <td id="Det[<? echo $cont?>]">
     <input type="text" name="txt_total_ingres" id="txt_total_ingres" value="<?Php echo round($total_colum_i,2); ?>" size="5" readonly="readonly" style=" border:none;text-align:right;background:none">    </td>
    <?php
   /* Consulta campos ingresos del rol de pagos*/
	$rs_campose= $obBD_con1->consulta(sentencias_tes($Com_Sql_Campos_Egr, $obBD_con1->parametros($Com_Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_campose  = $obBD_con1->registros();
	$total_rs_campose = $obBD_con1->numregistros();	
    $ke=0;
	do{
	$ke++;?>
   <td  width="1">
   <input type="text" name="hdd_ingre[<? echo $row_rs_campose['Cam_Cod'];?>]" id="hdd_ingre[<? echo $row_rs_campose['Cam_Cod'];?>]" value="<?Php echo round($suma_colum_e[$row_rs_campose['Cam_Cod']],2); ?>" size="5" style=" border:none;text-align:right;background:none"></td>
    <?php
     /* Almacena el total de egresos */
   	 $total_colum_e = $total_colum_e + $suma_colum_e[$row_rs_campose['Cam_Cod']];	
   }while($row_rs_campose = $obBD_con1->fetch_assoc($rs_campose));
   
   ?>
   <td>
     <input type="text" name="txt_total_egres" id="txt_total_egres" value="<?Php echo round($total_colum_e,2); ?>" size="5" readonly="readonly" style=" border:none;text-align:right;background:none">	 </td>
   <td>
   <input type="text" name="txt_total_rol" id="txt_total_rol" value="<?Php echo round($total_colum_i - $total_colum_e,2); ?>" size="5" readonly="readonly" style=" border:none;text-align:right;background:none">   </td>
 </tr>
<?php   
} //fin de if ($total_rs_per_rol > 0){ 
else
{ ?>
	<tr>
    	<td colspan="5"><?Php echo error_alerta(" << No existen campos configurados para este Tipo de Rol de Pagos >>", 1);?>  </td>
    </tr>
<?Php	
}
?>
</tbody>
</table>
</div>
<? 
}
else
{
	echo error_alerta("<< Error de componente: tes_com_det_rolpagos.php >> <br>Descripción: No se ha definido la Propiedad: 	
		Com_Are_Cod<br> Hoy: Variable que contiene el Tipo de Rol de Pagos", 2); 
}?>