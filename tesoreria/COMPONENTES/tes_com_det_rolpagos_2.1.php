<?php 
/*
Leyenda
Alias: 					
Descripcion:			Componente que pemite visualizar las personas segun el tipo de Rol con sus respectivos Ingresos y Egresos
						del registrar y modificar
Fecha de Actualizacion:	20/07/2010
Desarrollador:			Angelica Gálvez
Fecha de Actualizacion:	24/01/2011
Desarrollador:			Lewis Chimarro
Fecha de Actualizacion:	02/05/2011
Desarrollador:			Lewis Chimarro
*/

if(isset($Com_Are_Cod))
{ 
	/* Consulta campos ingresos del rol de pagos*/
	$rs_cam_ing= $obBD_con1->consulta(sentencias_rol($Com_Sql_Campos_Cab, $obBD_con1->parametros($Com_Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_cam_ing  = $obBD_con1->registros();
	$total_rs_cam_ing = $obBD_con1->numregistros();		
	
	/* Constante para definir estilos en las cajas de texto */
	define(estilo_head, "border:none;text-align:center;background:none;font-size:10px;color:#FFF");
	define(estilo_head_right, "border:none;text-align:right;background:none;font-size:10px;color:#FFF");
	define(estilo_body_center, "border:none;text-align:center;background:none;font-size:10px");	
	define(estilo_body_right, "border:none;text-align:right;background:none;font-size:10px");	
	define(estilo_body_left, "border:none;text-align:left;background:none;font-size:10px");	
?>
<!--
TAMAÑO DIV 1:
    width:1024px
TAMAÑO DIV 2:
    width:1280px
-->
<!--<div style="width:1280px; overflow-x:scroll; overflow-y:hidden">-->
<table border="1"  cellpadding="0" cellspacing="0" id="tbl_personal" style="width:1280px;"><!--style="width:1280px;"-->  
<thead>
<tr class="Cabecera1"> 
<th style="width: 10px;"><input type="text" name="txt_colum1" id="txt_colum1" value="N&ordm;" size="1" 
            readonly="readonly" style=" <?Php echo estilo_head; ?>"/>  </th>
<th style="width: 200px;" align="left" ><input name="txt_colum2" type="text" value="Personal."size="60"  readonly="readonly" style="  <?Php echo estilo_head; ?>" id="txt_colum2"></th>
	<?php          
	$i=1;
	$j=0;
	$aux="E";
	do{	
		   $tip_cam[$i]=$row_rs_cam_ing['Cam_Tip'];
		   $hdd_cam_ing_egr[$i]=$row_rs_cam_ing['Cam_Cod'];
		   $hdd_cam_ing_egr_dep[$i]=$row_rs_cam_ing['Cam_Cod'];
		   $hdd_cam_porc[$i]=$row_rs_cam_ing['Cam_Por']; 
		   $hdd_cam_ing_egr_cal[$i]=$row_rs_cam_ing['Cam_Cal']; 
		   $hdd_cam_ing_egr_req[$i]=$row_rs_cam_ing['Cam_Req']; 
		   $cam_sum[$i]= $row_rs_cam_ing['Cam_Sum']; 
		   $vis[$i]=$row_rs_cam_ing['Cam_Vis']; 
		   if ($vis[$i] != "N"){
		   		$cadena=$cadena."*".$row_rs_cam_ing['Cam_Cod'];		
		   }
		   if(($vis[$i] != "N") && ($tip_cam[$i]=="I")){
		       $cading=$cading."*".$row_rs_cam_ing['Cam_Cod'];	
		   }
		   if( ($vis[$i] != "N") &&($tip_cam[$i]=="E")){
		       $cadegr=$cadegr."*".$row_rs_cam_ing['Cam_Cod'];	
		   }

	if ($tip_cam[$i] == $aux){$j++; ?>
	<th style="width: 10px;" align="center">
    <input type="text" name="txt_colum3" id="txt_colum3" value="T. INGRESOS" size="10" 
            readonly="readonly" style=" <?Php echo estilo_head; ?>" />    </th>	  
	<?php $aux=" ";
		} ?>
																							

    	<th style="width: 10px;" align="center" id="td[<?php echo $i;?>]" title="<?php echo $row_rs_cam_ing['Cam_Des']; ?>">				
        <input type="text" name="txt_colum4" id="txt_colum4" value="<?php echo $row_rs_cam_ing['Cam_Dec'];?>" size="6" 
            readonly="readonly" style=" <?Php echo estilo_head; ?>" />    </th>		
		<?Php		
		if ($vis[$i] == "N")
		{ //Control para ocultar los rubros q poseen "Cam_Vis=N" de la tabla Campo_rol?> 
			<script language="javascript">
  			fila = document.getElementById("td[<?php echo $i;?>]");
    		fila.style.display = "none"; //ocultar fila 
            </script>
			<?php
		}//FIn del if ($row_rs_cam_ing['Cam_vis']; == "S") 
		$i++; ?>
	<?php }while($row_rs_cam_ing = $obBD_con1->fetch_assoc($rs_cam_ing)); ?>		
    <input type="hidden" size="30" id="hdd_cod_camp" name="hdd_cod_camp" value="<?php echo $cadena; ?>"/>
	  <input type="hidden" size="30" id="hdd_cod_ing" name="hdd_cod_ing" value="<?php echo $cading; ?>"/>
	  <input type="hidden" size="30" id="hdd_cod_egr" name="hdd_cod_egr" value="<?php echo $cadegr; ?>"/>
	<th style="width: 10px;" align="center"><input type="text" name="txt_colum5" id="txt_colum5" value="T. EGRESOS" size="10" 
            readonly="readonly" style=" <?Php echo estilo_head; ?>" /></th>
    <th style="width: 10px;"><input type="text" name="txt_colum6" id="txt_colum6" value="LIQ. RECIBIR" size="10" 
            readonly="readonly" style=" <?Php echo estilo_head; ?>" />    </th>
 </tr>
</thead>
<tbody> 
<!-- </table>-->
<!--
TAMAÑO DIV 1:
    width:1520px
    height:300px
TAMAÑO DIV 2:
    width:1520px
    height:300px    
-->
<!--<div style="overflow-y:scroll; overflow-x:hidden; height:300px; width:1520px">
 <table style="width:1280px" border="1" cellpadding="0" cellspacing="0">-->
  <?php 	
  /* Consulta del personal de la empresa*/ 
  $rs_per_rol = $obBD_con1->consulta(sentencias_rol($Com_Sql_Personal, $obBD_con1->parametros($Com_Personal)), $obBD_conexion->conexion);			
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
  <?Php //echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?>
 <tr class="Fondo" id="tbody_fila[<?php echo $cont?>]">
   	<td style="width: 10px;" align="center"><input type="text" name="txt_campos4" id="txt_campos4" value="<?php echo $cont; ?>" size="1" 
            readonly="readonly" style=" <?Php echo estilo_body_center; ?>" <?Php echo focus_row_select_id('tbody_fila['.$cont.']',"resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> /></td>
    <td style="width: 200px;" align="left" title="<?php echo $row_rs_per_rol['Tic_Des'];?>"><input name="hdd_nombre" type="text" value="<?php echo "Cód.: ".$row_rs_per_rol['Dis_Cod']." ".$row_rs_per_rol['Prs_Ape'].' '.$row_rs_per_rol['Prs_Nom']; ?>"size="60"  readonly="readonly" style=" <?Php echo estilo_body_left; ?>" <?Php echo focus_row_select_id('tbody_fila['.$cont.']',"resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> /></td>
	
	<?php 
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
		$dep=$hdd_cam_ing_egr[$cont_caja_ingre];
		$sum= $cam_sum[$cont_caja_ingre];
		$porc=$hdd_cam_porc[$cont_caja_ingre];
		
		/* Condicion para saber cuando hay campos de egreso */
		if($tip_cam[$cont_caja_ingre] == $aux)
		{
		?>
<td style="width: 10px;" id="Det[<?php echo $cont?>]" align="right" class="Cabecera1">
			<input type="text" name="txt_t_ingre[<?php echo $cont;?>]" id="txt_t_ingre[<?php echo $cont;?>]" value="<?Php echo round($suma_fila_i[$cont],2); ?>" size="10" 
            readonly="readonly" style=" <?Php echo estilo_head_right; ?>"/>
			<input type="hidden" name="hdd_t_ingre[<?php echo $cont;?>]" id="hdd_t_ingre[<?php echo $cont;?>]" 
            value="<?Php echo $ing_java;?>" />		</td>
		<?php $aux=" "; 
		} // Fin  del else	if($tip_cam[$cont_caja_ingre] != $aux) ?>
<td style="width: 10px;" align="right" <?php if ($vis[$cont_caja_ingre] == "N"){?> id="<?php echo "det[".$td_cont."]"; ?>"<?php }?>>
        <?php  
		/* Control para los modulos que se agregan a cada campo */
		$param[0] = $cod;//Codigo del campo
		$param[1] = $Cod_Emp;//Codigo del empleado
		$modulo_rol = configura_campo($param, $obBD_con1, $obBD_conexion);  
		//print_r($modulo_rol);
		/* C O N T R O L    P A R A    M O D I F I C A C I O N    D E    R O L E S    D E    P A G O */
		if ($Com_Tipo == 'M')//Modificacion
		{ 
			/* Consulta del valor de un campos del rol de pago */
			$rs_campo_valor = $obBD_con1->consulta(sentencias_rol(890, $obBD_con1->parametros($Com_Are_Cod.'*'.$Cod_Emp.'*'.$cod)), $obBD_conexion->conexion);	
			$row_rs_campo_valor = $obBD_con1->registros();

			/* Evalua si son campos de ingreso o egreso y visible si */
			if($tip_cam[$cont_caja_ingre] == 'E' and $vis[$cont_caja_ingre] == 'S')//Egreso
			{						
				$suma_fila_e[$cont] = $suma_fila_e[$cont] + round($row_rs_campo_valor['Rol_Val'],4);
				$suma_colum_e[$cod] = $suma_colum_e[$cod] + round($row_rs_campo_valor['Rol_Val'],4);
			}
			elseif($vis[$cont_caja_ingre] == 'S')//Ingreso
			{
			if(($cod!=2) && ($cod!=34) ){
				$suma_fila_i[$cont] = $suma_fila_i[$cont] + round($row_rs_campo_valor['Rol_Val'],4);
				$suma_colum_i[$cod] = $suma_colum_i[$cod] + round($row_rs_campo_valor['Rol_Val'],4);
				}				
			}
		}//Fin del if ($Com_Tipo == 'M')
		elseif ($Com_Tipo == 'A')//Alta
		{ 
			/* Evalua si son campos de ingreso o egreso y visible si */			
			if($tip_cam[$cont_caja_ingre] == 'E' and $vis[$cont_caja_ingre] == 'S')//Egreso
			{
				$suma_fila_e[$cont] = $suma_fila_e[$cont] + round($modulo_rol['Value'][0],4);		
				$suma_colum_e[$cod] = $suma_colum_e[$cod] + round($modulo_rol['Value'][0],4);				
			}
			elseif($vis[$cont_caja_ingre] == 'S')//Ingreso
			{
			if(($cod!=2) && ($cod!=34) ){
				$suma_fila_i[$cont] = $suma_fila_i[$cont] + round($modulo_rol['Value'][0],4);			
				$suma_colum_i[$cod] = $suma_colum_i[$cod] + round($modulo_rol['Value'][0],4);		
				}						
			}			
		}//Fin del elseif ($Com_Tipo == 'A')
		
		if ($Com_Tipo == 'C')//Modificacion
		{ 
			/* Consulta del valor de un campos del rol de pago */
			$rs_campo_valor = $obBD_con1->consulta(sentencias_rol(890, $obBD_con1->parametros($Com_Are_Cod.'*'.$Cod_Emp.'*'.$cod)), $obBD_conexion->conexion);	
			$row_rs_campo_valor = $obBD_con1->registros();

			/* Evalua si son campos de ingreso o egreso y visible si */
			if($tip_cam[$cont_caja_ingre] == 'E' and $vis[$cont_caja_ingre] == 'S')//Egreso
			{						
				$suma_fila_e[$cont] = $suma_fila_e[$cont] + round($row_rs_campo_valor['Rol_Val'],4);
				$suma_colum_e[$cod] = $suma_colum_e[$cod] + round($row_rs_campo_valor['Rol_Val'],4);
			}
			elseif($vis[$cont_caja_ingre] == 'S')//Ingreso
						{
				if(($cod!=2) && ($cod!=34) ){
				$suma_fila_i[$cont] = $suma_fila_i[$cont] + $row_rs_campo_valor['Rol_Val'];
				$suma_colum_i[$cod] = $suma_colum_i[$cod] + $row_rs_campo_valor['Rol_Val'];	
				}			
			}
		}//Fin del if ($Com_Tipo == 'C') 
		
		/* C O N T R O L    P A R A    M O D I F I C A C I O N    D E    R O L E S    D E    P A G O */		
		?>
		<?php
		/*Consulta los campos afectado en la formula*/
	  	
		$rs_campoformula = $obBD_con1->consulta(sentencias_rol(898, $obBD_con1->parametros($cod)), $obBD_conexion->conexion);	
		$row_rs_campoformula = $obBD_con1->registros();	
		$total_campoformula = $obBD_con1->numregistros();
		 ?>
		
		<input  type="text" name="hdd_ing_egr[<?php echo $cont;?>,<?php echo $cod;?>]" style=" <?Php echo estilo_body_right; ?>" id="hdd_ing_egr[<?php echo $cont;?>,<?php echo $cod; ?>]" size="6" <?php if(($hdd_cam_ing_egr_cal[$cont_caja_ingre] == "S") || ($Com_Tipo == 'C')){ echo "readonly='true'";}?> 
            value="<?php if ($Com_Tipo == 'A'){ if (count($modulo_rol['Value']) == 1){ echo $modulo_rol['Value'][0]; }else{ echo round($porc,4); } 
				}elseif (($Com_Tipo == 'M') || ($Com_Tipo == 'C'))
				{  
					/* Control para asignar 2 decimales a los valores y 3 a los porcentajes internos */
					if ($vis[$cont_caja_ingre] == 'S')
					{
						echo round($row_rs_campo_valor['Rol_Val'],5); 
					}
					else
					{
						echo round($row_rs_campo_valor['Rol_Val'],4); 
					}
				}?>" 
           
            onkeypress="return validar_decimal(event)"  
            <?php if(($Com_Tipo == 'M') || ($Com_Tipo == 'A')){  ?> onkeyup="<?php if($sum=='S'){?> 
<?php echo formula_rol($cont, $cod, $modulo_rol['Event'], $req, $obBD_con1, $obBD_conexion); /*}*/ ?> <?php  
if($total_campoformula>0){ ?>  <?php do{?>
SumaCamposRol(this.form,  <?php echo $total_rs_per_rol;?>, <?php echo $row_rs_campoformula['Cam_Cod'];?>,<?php echo $cont;?>);
<?php }while($row_rs_campoformula = $obBD_con1->fetch_assoc($rs_campoformula));
} ?> SumaCamposRol(this.form,  <?php echo $total_rs_per_rol;?>, <?php echo $cod;?>,<?php echo $cont;?>);
SumaColumnas(this.form, <?php echo $total_rs_per_rol;?>) <?php }// fin de  if($sum=='S') ?>" <?php  }// fin de if(($Com_Tipo == 'M') || ($Com_Tipo == 'A')){?> tabindex="<?php if ($vis[$cont_caja_ingre] != "N"){ 
			echo $cont;}?>" <?Php echo focus_row_select_id('tbody_fila['.$cont.']',"resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> />
		<input type="hidden" name="hdd_ingreso_egreso[<?php echo $dep;?>,<?php echo $cod; ?>]" id="hdd_ingreso_egreso[<?php echo $cont;?>,<?php echo $cod; ?>]" value="<?php echo $cod;?>"/>		</td>
		<?php if ($vis[$cont_caja_ingre] == "N"){ //Control para ocultar los rubros q poseen "Cam_Vis=N" de la tabla Campo_rol ?> 		
	<script language="javascript">
			fila = document.getElementById("det[<?php echo $td_cont;?>]");
    		fila.style.display = "none"; //ocultar col
			</script>
		<?php }//Fin if ($vis[$cont_caja_ingre] == "N")
		/* Concatena todos los campos que nos sera visibles */
		if ($vis[$cont_caja_ingre] != "N")
		{   if(($cod!=2) && ($cod!=34)){
			$ing_java= $ing_java."hdd_ing_egr[".$cont.",".$cod."]*";
			}
		}//Fin del if ($vis[$cont_caja_ingre] != "N")
	/* Concatena los nombres de los campos que calculan el total */
	if($tip_cam[$cont_caja_ingre]=="E")
    {
	 if ($vis[$cont_caja_ingre] != "N")
	 {
	 	 if(($cod!=2) && ($cod!=34)){
		$ing_java2= $ing_java2."hdd_ing_egr[".$cont.",".$cod."]*"; 
		}
	 }
	}//Fin del if($tip_cam[$cont_caja_ingre]=="E")
		/* Contador para el id de las columnas */
		$td_cont++; 
	}while ($cont_caja_ingre<$total_rs_cam_ing);	
	?>
	<td style="width: 10px;" class="Cabecera1">
	<input type="text" name="txt_t_egre[<?php echo $cont;?>]"  id="txt_t_egre[<?php echo $cont;?>]" value="<?Php echo round($suma_fila_e[$cont],2); ?>"  size="10" readonly="true" style=" <?Php echo estilo_head_right; ?>" />
	<input type="hidden" name="hdd_t_egre[<?php echo $cont;?>]" id="hdd_t_egre[<?php echo $cont;?>]" value="<?php echo $ing_java2;?>" />	</td>
	<td style="width: 10px;" class="Cabecera1">
    <font style="color:<?php if (round($suma_fila_i[$cont] + $suma_fila_e[$cont],2)!=0){ ?>  <?Php }else{ ?>  background:#F00 <?Php } ?>">
	<input type="text" name="txt_t_liq[<?php echo $cont;?>]" id="txt_t_liq[<?php echo $cont;?>]" value="<?Php echo round($suma_fila_i[$cont] - $suma_fila_e[$cont],2); ?>"  size="10" readonly="true" 
	style=" <?Php echo estilo_head_right; ?>" /></font></td>
  </tr>
<?php } while($row_rs_per_rol = $obBD_con1->fetch_assoc($rs_per_rol));  //Fin del IF $rs_per_rol ?>
 <!--</table>
 </div>
 <table style="width:1280px" border="1" cellpadding="0" cellspacing="0">-->
 <tr class="Cabecera1">
   <td style="width: 10px;" align="rigth">
   <input type="text" name="txt_campos6" id="txt_campos6" value="&nbsp;" size="1" 
            readonly="readonly" style=" <?Php echo estilo_head; ?>" /></td>
   <td style="width: 200px;" align="rigth">
    <input name="txt_nombre2" type="text" value="TOTAL:" size="60"  readonly="readonly" style=" <?Php echo estilo_head; ?>" /></td>
   <?php
   /* Consulta campos ingresos del rol de pagos*/
	$rs_campos= $obBD_con1->consulta(sentencias_rol($Com_Sql_Campos_Ing, $obBD_con1->parametros($Com_Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_campos  = $obBD_con1->registros();
	$total_rs_campos = $obBD_con1->numregistros();	
    $k=0;
	do{
	$k++;?>
   <td style="width: 10px;">
   <?php 
   if( ($row_rs_campos['Cam_Cod']==2) || ($row_rs_campos['Cam_Cod']==34) ){?><input type="text" name="campo_vac" id="campo_vac" value="<?php echo  $val;  ?>" size="6" style=" <?Php echo estilo_head; ?>" /><?php } else{ ?><input type="text" name="hdd_ingre[<?php echo $row_rs_campos['Cam_Cod'];?>]" id="hdd_ingre[<?php echo $row_rs_campos['Cam_Cod'];?>]" value="<?Php echo round($suma_colum_i[$row_rs_campos['Cam_Cod']],2); ?>" size="6" style=" <?Php echo estilo_head; ?>"  title=" <?php echo $row_rs_campos['Cam_Cod']; ?>" />
    <?php } ?>   </td>
   <?php 
     /* Almacena el total de ingresos */
   	 $total_colum_i = $total_colum_i + $suma_colum_i[$row_rs_campos['Cam_Cod']];
   }while($row_rs_campos = $obBD_con1->fetch_assoc($rs_campos));
   ?>
   <td style="width: 10px;" id="Det[<?php echo $cont?>]"><input type="text" name="txt_total_ingres" id="txt_total_ingres" value="<?Php echo round($total_colum_i,2); ?>" size="10" readonly="readonly" style=" <?Php echo estilo_head_right; ?>" /></td>
    <?php
   /* Consulta campos ingresos del rol de pagos*/
	$rs_campose= $obBD_con1->consulta(sentencias_rol($Com_Sql_Campos_Egr, $obBD_con1->parametros($Com_Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_campose  = $obBD_con1->registros();
	$total_rs_campose = $obBD_con1->numregistros();	
    $ke=0;
	do{
	$ke++;?>
   <td style="width: 10px;">
   <input type="text" name="hdd_ingre[<?php echo $row_rs_campose['Cam_Cod'];?>]" id="hdd_ingre[<?php echo $row_rs_campose['Cam_Cod'];?>]" value="<?Php echo $suma_colum_e[$row_rs_campose['Cam_Cod']]; ?>" size="6" style=" <?Php echo estilo_head; ?>" title=" <?php echo $row_rs_campose['Cam_Cod']; ?>"></td>
    <?php
     /* Almacena el total de egresos */
   	 $total_colum_e = $total_colum_e + $suma_colum_e[$row_rs_campose['Cam_Cod']];	
   }while($row_rs_campose = $obBD_con1->fetch_assoc($rs_campose));
   
   ?>
   <td style="width: 10px;">
     <input type="text" name="txt_total_egres" id="txt_total_egres" value="<?Php echo round($total_colum_e,2); ?>" size="10" readonly="readonly" style=" <?Php echo estilo_head_right; ?>">	 </td>
   <td style="width: 10px;">
   <input type="text" name="txt_total_rol" id="txt_total_rol" value="<?Php echo round($total_colum_i - $total_colum_e,2); ?>" size="10" readonly="readonly" style=" <?Php echo estilo_head_right; ?>">   </td>
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
<!--</div>-->
<?php 
}
else
{
	echo error_alerta("<< Error de componente: tes_com_det_rolpagos.php >> <br>Descripción: No se ha definido la Propiedad: 	
		Com_Are_Cod<br> Hoy: Variable que contiene el Tipo de Rol de Pagos", 2); 
}?>