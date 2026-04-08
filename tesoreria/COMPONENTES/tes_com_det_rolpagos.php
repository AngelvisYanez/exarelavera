<? 
/*Componente que pemite visualizar las personas segun el tipo de Rol con sus respectivos Ingresos y Egresos-*/
if(isset($Are_Cod)){?>

<table width="100%" border="1" cellpadding="0" cellspacing="0" id="tbl_personal">
  <tr class="Cabecera1">
    <td width="3%">N&ordm;</td>
    <td width="34%" align="center" >Personal</td>
	<?php          
	/* Consulta campos ingresos del rol de pagos*/
	$rs_cam_ing= $obBD_con1->consulta(sentencias_tes(980, $obBD_con1->parametros($Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_cam_ing  = $obBD_con1->registros();
	$total_rs_cam_ing = $obBD_con1->numregistros();		
	
	$i=1;
	$j=0;
	$aux="E";
	do{	
		  //echo $j; 
		   $tip_cam[$i]=$row_rs_cam_ing['Cam_Tip'];
		   $hdd_cam_ing_egr[$i]=$row_rs_cam_ing['Cam_Cod'];
		   $hdd_cam_porc[$i]=$row_rs_cam_ing['Cam_Por']; 
		   $hdd_cam_ing_egr_cal[$i]=$row_rs_cam_ing['Cam_Cal']; 
		   $hdd_cam_ing_egr_req[$i]=$row_rs_cam_ing['Cam_Req']; 
		   $vis[$i]=$row_rs_cam_ing['Cam_Vis']; 
		   if ($vis[$i] != "N"){
		   		$cadena=$cadena."*".$row_rs_cam_ing['Cam_Cod'];		
		   }
	 if ($tip_cam[$i] != $aux){$j++;?>
    	<td  align="center" valign="middle" id="td[<? echo $i;?>]" title="<?php echo $row_rs_cam_ing['Cam_Des']; ?>" width="1%">
		<font STYLE="writing-mode: tb-rl; filter: flipv() fliph()"><?php echo $row_rs_cam_ing['Cam_Des'];?></font>			
		</td>		
			<? if ($vis[$i] == "N"){ //Control para ocultar los rubros q poseen "Cam_Vis=N" de la tabla Campo_rol?> 
					<script language="javascript">
						ShowHide('td[<? echo $i?>]');
					</script>
			<?
		}//FIn del if ($row_rs_cam_ing['Cam_vis']; == "S")
	   }else{ //$j++; ?>
	<td><font STYLE="writing-mode: tb-rl; filter: flipv() fliph()">T. I N G R E S O </font></td>	  
	<td align="center" valign="middle" title="<?php echo $row_rs_cam_ing['Cam_Des'];?>" width="1%">
	<font STYLE="writing-mode: tb-rl; filter: flipv() fliph()"><?php echo $row_rs_cam_ing['Cam_Des'];?></font>		</td>
	<? $aux=" ";}?>	
	<? $i++; ?>
	<? }while($row_rs_cam_ing = $obBD_con1->fetch_assoc($rs_cam_ing)); ?>		
    <input type="hidden" size="30" id="hdd_cod_camp" name="hdd_cod_camp" value="<? echo $cadena; ?>"/>
	<td ><font STYLE="writing-mode: tb-rl; filter: flipv() fliph()">T. E G R E S O </font></td>
    <td ><font STYLE="writing-mode: tb-rl; filter: flipv() fliph()">LIQ. RECIBIR</font> </td>
 </tr>
  <?php 	
  /* Consulta del personal de la empresa*/ 
  $rs_per_rol= $obBD_con1->consulta(sentencias_tes(915, $obBD_con1->parametros($Are_Cod)), $obBD_conexion->conexion);			
  $row_rs_per_rol = $obBD_con1->registros();
  $total_rs_per_rol = $obBD_con1->numregistros();	
  $td_cont=1; 
  $cont=0;
  $var_c=0;
  /*Consulta de campo de mapeo par el sueldo del empleado*/
  $rs_mapeo= $obBD_con1->consulta(sentencias_tes(858, $obBD_con1->parametros('')), $obBD_conexion->conexion);			
  $row_rs_mapeo = $obBD_con1->registros();
  $total_rs_mapeo = $obBD_con1->numregistros();	
  if ($total_rs_per_rol > 0){ //inicio IF $rs_per_rol
  do{  
  $var_c++; 
   $cont++; 
   $sueldo=$row_rs_per_rol['Sue_Val'];
   $ing_java = "";
   $ing_java2 = "";
   $hdd_cam_ingre_dis[$cont]=$row_rs_per_rol['Dis_Cod'];   
  ?>
 <tr class="Fondo" <? echo focus_row("resaltar_text","resaltar_back","undo_resaltar_text","Fondo");?>>
   	<td align="center"><? echo $cont; ?></td>
    <td align="left" title="<? echo $row_rs_per_rol['Tic_Des'];?>"><? echo $row_rs_per_rol['Prs_Ape'].' '.$row_rs_per_rol['Prs_Nom']; ?></td>
	<? 
	/* CALCULO CAMPOS INGRESOS */
	$cont_caja_ingre=0;
	$aux="E";
	do{ 
	
	$cont_caja_ingre++;	
	$cod=$hdd_cam_ing_egr[$cont_caja_ingre];
	$porc=$hdd_cam_porc[$cont_caja_ingre];
	 if($tip_cam[$cont_caja_ingre] != $aux){?>
	<td <? if ($vis[$cont_caja_ingre] == "N"){?> id="<? echo "det[".$td_cont."]"; ?>"<? }?> align="right">
	
	<input type="text" name="hdd_ing_egr[<? echo $cont;?>,<? echo $cod; ?>]" style="border:none;text-align:right" id="hdd_ing_egr[<? echo $cont;?>,<? echo $cod; ?>]" size="4" value="<?  if( $row_rs_mapeo['Map_Ide']==$cod){echo $sueldo;}else{ /*echo $porc/100;*/ }?>" onKeyPress="return validar_decimal(event)" onkeyUp="<? echo formula_rol($cont, $cod, $obBD_con1, $obBD_conexion);?> ;toNextField(this.value,<? echo $cont;?>,'hdd_t_ingre','txt_t_ingre','hdd_t_egre','txt_t_egre'); cal_li(<? echo $cont;?>,'txt_t_ingre','txt_t_egre','txt_t_liq');SumaCamposRol(this.form,  <?php echo $total_rs_per_rol;?>);  SumaColumnasRol(this.form, <?php echo $total_rs_per_rol;?>, <?php echo $cod;?>)" tabindex="<? if ($vis[$cont_caja_ingre] != "N"){ echo $cont;}?>" title="hdd_ing_egr[<? echo $cont;?>,<? echo $cod; ?>]" class="Fondo"/>	</td>
	<? if ($vis[$cont_caja_ingre] == "N"){ //Control para ocultar los rubros q poseen "Cam_Vis=N" de la tabla Campo_rol ?> 		
		<script language="javascript">
			ShowHide('det[<? echo $td_cont;?>]');			
		</script>
	<? }//Fin if ($vis[$cont_caja_ingre] == "N")?>
	
	<? }//FIn del if($tip_cam[$cont_caja_ingre] != $aux)
		else{?>
	<td align="center" id="Det[<? echo $cont?>]" bgcolor="#CCCCCC">
	<input type="text" name="txt_t_ingre[<?php echo $cont;?>]" id="txt_t_ingre[<?php echo $cont;?>]" value="" size="4" readonly="readonly" style=" border:none; background:#CCCCCC;text-align:right;"/>        
	<input type="hidden" name="hdd_t_ingre[<?php echo $cont;?>]" id="hdd_t_ingre[<?php echo $cont;?>]" value="<?Php echo $ing_java;?>" title="hdd_ing_egr[<? echo $cont;?>,<? echo $cod;?>]"/>	</td>
	<td align="right">
	<input type="text" name="hdd_ing_egr[<? echo $cont;?>,<? echo $cod; ?>]" style="border:none;text-align:right" id="hdd_ing_egr[<? echo $cont;?>,<? echo $cod; ?>]" size="4" value="<? if($porc>0){echo $porc/100;}else{ /*echo "0";*/ }?>" onKeyPress="return validar_decimal(event)" onkeyUp="<? echo formula_rol($cont, $cod, $obBD_con1, $obBD_conexion);?> toNextField(this.value,<? echo $cont;?>,'hdd_t_ingre','txt_t_ingre','hdd_t_egre','txt_t_egre'); cal_li(<? echo $cont;?>,'txt_t_ingre','txt_t_egre','txt_t_liq');SumaCamposRol(this.form,  <?php echo $total_rs_per_rol;?>);  SumaColumnasRol(this.form, <?php echo $total_rs_per_rol;?>, <?php echo $cod;?>)" tabindex="<? echo $cont;?>" title="hdd_ing_egr[<? echo $cont;?>,<? echo $cod; ?>]" class="Fondo"/>	</td>	
	<? $aux=" "; 
	} // Fin  del else	
	
	if ($vis[$cont_caja_ingre] != "N"){
		$ing_java= $ing_java."hdd_ing_egr[".$cont.",".$cod."]*";
	}	
	?>					
    <input type="hidden" name="hdd_di_Cod[<? echo $cont;?>]" title="hdd_di_Cod[<? echo $cont;?>]"  id="hdd_di_Cod[<? echo $cont;?>]" value="<? echo $hdd_cam_ingre_dis[$cont];?>"/>
    <input type="hidden" name="hdd_ingreso_egreso[<? echo $cont;?>,<? echo $cod; ?>]" id="hdd_ingreso_egreso[<? echo $cont;?>,<? echo $cod; ?>]" value="<? echo $cod;?>"/>					
	
	<? if($tip_cam[$cont_caja_ingre]=="E")
	   {
		 if ($vis[$cont_caja_ingre] != "N")
		 {
			$ing_java2= $ing_java2."hdd_ing_egr[".$cont.",".$cod."]*"; 
		 }
	   }
	$td_cont++; $sueldo="0";	
	}while ($cont_caja_ingre<$total_rs_cam_ing);?>
	<td bgcolor="#CCCCCC">
	<input type="text" name="txt_t_egre[<? echo $cont;?>]"  id="txt_t_egre[<? echo $cont;?>]" value=""  size="5" readonly="true" style="background:#CCCCCC; text-align:right; border:none"/>
	<input type="hidden" name="hdd_t_egre[<? echo $cont;?>]" id="hdd_t_egre[<? echo $cont;?>]" value="<? echo $ing_java2;?>" />	</td>
	<td bgcolor="#CCCCCC">
	<input type="text" name="txt_t_liq[<? echo $cont;?>]" id="txt_t_liq[<? echo $cont;?>]" value=""  size="5" readonly="true" 
	style=" text-align:right; border:none; background:#CCCCCC" />
    <input type="hidden" name="hdd_total_liq" id="hdd_total_liq" value="hi[1,1]*hi[1,2]*hi[1,3]*hi[1,4]" />	
	 <?php if($var_c==$total_rs_per_rol) {?>
	
	 <?php } ?>	</td>
  </tr>
<? } while($row_rs_per_rol = $obBD_con1->fetch_assoc($rs_per_rol));  //Fin del IF $rs_per_rol ?>
 
 <tr class="Cabecera1">
   <td colspan="2" align="rigth">TOTAL:</td>
   <?php
   /* Consulta campos ingresos del rol de pagos*/
	$rs_campos= $obBD_con1->consulta(sentencias_tes(867, $obBD_con1->parametros($Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_campos  = $obBD_con1->registros();
	$total_rs_campos = $obBD_con1->numregistros();	
    $k=0;
	do{
	$k++;?>
   <td align="right"><input type="text" name="hdd_ingre[<? echo $row_rs_campos['Cam_Cod'];?>]" id="hdd_ingre[<? echo $row_rs_campos['Cam_Cod'];?>]" value="" size="4" style="border:none;  text-align:right" class="Cabecera1"></td>
   <?php 
   }while($row_rs_campos = $obBD_con1->fetch_assoc($rs_campos));
   ?>
   <td align="center" id="Det[<? echo $cont?>]">
     <input type="text" name="txt_total_ingres" id="txt_total_ingres" value="" size="4" readonly="readonly" style="border:none;  text-align:right" class="Cabecera1">
    </td>
    <?php
   /* Consulta campos ingresos del rol de pagos*/
	$rs_campose= $obBD_con1->consulta(sentencias_tes(868, $obBD_con1->parametros($Are_Cod)), $obBD_conexion->conexion);			
	$row_rs_campose  = $obBD_con1->registros();
	$total_rs_campose = $obBD_con1->numregistros();	
    $ke=0;
	do{
	$ke++;?>
   <td> 
   <input type="text" name="hdd_ingre[<? echo $row_rs_campose['Cam_Cod'];?>]" id="hdd_ingre[<? echo $row_rs_campose['Cam_Cod'];?>]" value="" size="4" style="border:none;  text-align:right;" class="Cabecera1"></td>
    <?php 
   }while($row_rs_campose = $obBD_con1->fetch_assoc($rs_campose));
   ?>
   <td align="right">
     <input type="text" name="txt_total_egres" id="txt_total_egres" value="" size="4" readonly="readonly" style="border:none;  text-align:right;" class="Cabecera1">
   </td>
   <td  align="right">
     <input type="text" name="txt_total_rol" id="txt_total_rol" value="" size="4" readonly="readonly" style="border:none;  text-align:right;" class="Cabecera1">
   </td>
 </tr>
 

<?php   } //fin de if ($total_rs_per_rol > 0){ 
?>

</table>

<? }else{
	echo error_alerta("<< Error de componente: tes_com_det_rolpagos.php >> <br>Descripción: No se ha definido la Propiedad: 	
		Are_Cod<br> Hoy: Variable que contiene el Tipo de Rol de Pagos", 2);
 }?>
 
<?Php
/* libero los cursores de la base de datos */
@$obBD_con1->free_result($rs_cam_ing);
@$obBD_con1->free_result($rs_per_rol);
@$obBD_con1->free_result($rs_campose);
@$obBD_con1->free_result($rs_campos);
@$obBD_con1->free_result($rs_mapeo);
?> 