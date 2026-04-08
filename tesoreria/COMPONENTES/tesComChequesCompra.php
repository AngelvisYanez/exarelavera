<?php
/** 
* Descripción: Componente que permite elegir la fecha en meses y tambien en un determinado 
				rango de fechas 
* Fecha de actualización:	2009-09-09
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2012-08-20
* Desarrollador:	Lewis Chimarro
*/
/**
* Variable de la forma de pago 
*/
if (isset($For_Cod))
{
	/**
	* Variable que almacena el nombre del campo que contiene el valor del cheque 
	*/
	if (isset($Hdd_Valor))
	{
		/**
		* Variable que almacena el nombre del campo que contiene la fecha del cheque 
		*/
		if (isset($Hdd_Fecha))
		{	
			/**
			* Consulta los tipos de pagos en relacion con compr_plan 
			*/
			$rs_tipo_pagos = $obBD_con1->consulta(sentencias_comf(258, $obBD_con1->parametros($For_Cod)), $obBD_conexion->conexion);
			$row_rs_tipo_pagos = $obBD_con1->registros();
			$total_rs_tipo_pagos = $obBD_con1->numregistros();
			/**
			* Consulta del saldo de los anticipos a proveedor 
			*/
			$rs_saldo_anticipos = $obBD_con1->consulta(sentencias_comf(192, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
			$row_rs_saldo_anticipos = $obBD_con1->registros();
			$total_rs_saldo_anticipos = $obBD_con1->numregistros();			
		?>
			<FIELDSET id="Fie_Cheques">
			<LEGEND>
			<label class="Titulos2">Tipos de Pago </label>
			</LEGEND>	
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
            <thead class="Cabecera1">
			  <tr height="35">
				<th width="20%">Descripci&oacute;n</th>
				<th width="15%" align="center">N&ordm; Documt.</th>
				<th width="15%" align="center">Valor</th>
				<th width="15%">Fec. Elab</th>
				<th width="30%">Observaci&oacute;n</th>
				<th width="5%">&nbsp;</th>
			  </tr>
            </thead>
			<tbody id="Tbl_Cheques">              
	  <?Php 
	  		/**
			* Consulto la informacion del cheke Cop_Bus=> es el código interno de la fatura de compra
			*/ 
	  		$rs_informacion_cheque=$obBD_con1->consulta(sentencias_comf(346, $obBD_con1->parametros($Cop_Bus)), $obBD_conexion->conexion); 
			$row_rs_informacion_cheque=$obBD_con1->registros();
			$num_row_rs_informacion_cheque=$obBD_con1->numregistros();	
			$valor=0; 
		
		/**
		* Si existe cheques ingresa
		*/
		if ($num_row_rs_informacion_cheque>0)
		{	
			do { $fila_che++;  	 ?> 
 		<tr class="Fondo">
		<td>
		<?Php
			/**
			* Consultar la información del cheque 
			*/
			$rs_cheque_compras=$obBD_con1->consulta(sentencias_comf(372, $obBD_con1->parametros($row_rs_informacion_cheque['Pld_Cod'])), $obBD_conexion->conexion); 
			$row_rs_cheque_compra_mod=$obBD_con1->registros();
			$num_row_rs_cheque_compra_mod=$obBD_con1->numregistros();	
			if($num_row_rs_cheque_compra_mod==0)
			{
			  $Ban_Tipo='O';
			}else
			{
			   $Ban_Tipo=$row_rs_cheque_compra_mod['Ban_Tip'];
			}
			/**
			* Fin consultar la información del cheque 
			*/	
			$rs_cuentas_cheque=$obBD_con1->consulta(sentencias_comf(347, $obBD_con1->parametros($row_rs_cheque_compra_mod['Ban_Tip'])), $obBD_conexion->conexion);
			$row_rs_cuenta_cheque=$obBD_con1->registros();		?>
            <select name="datos_ch[<?Php echo $fila_che; ?>,3]" id="datos_ch[<?Php echo $fila_che; ?>,3]"  >
            <option value="<?php echo $row_rs_cheque_compra_mod['Ban_Cod'].'*'.$row_rs_informacion_cheque['Pld_Cod'].'*'.$Ban_Tip; ?>">
            <?php echo $row_rs_informacion_cheque['Pld_Des']; ?></option>
            </select>
			<input name="datos_ch[<?Php echo $fila_che; ?>,3]" id="datos_ch[<?Php echo $fila_che; ?>,3]"  value="<?Php echo $row_rs_cheque_compra_mod['Ban_Cod'].'*'.$row_rs_informacion_cheque['Pld_Cod'].'*'.$Ban_Tipo; ?>" type="hidden" />
		</td>
    	<td align="center" >
		<?php  		
		/**
		* Consulto si hay cheque 
		*/
		$rs_cheque_consulta=$obBD_con1->consulta(sentencias_comf(367, $obBD_con1->parametros($row_rs_informacion_cheque['Asi_Cod'])), $obBD_conexion->conexion);
		$row_rs_cheque_consulta=$obBD_con1->registros();	
	  	?>
					<input name="datos_ch[<?Php echo $fila_che; ?>,4]" type="text" id="datos_ch[<?Php echo $fila_che; ?>,4]" size="10" maxlength="10" value="<?Php echo $row_rs_cheque_consulta['Che_Num']; ?>" readonly="" />
		</td>
    <td align="center">
		<?Php
		/**
		* Acumulo el valor del cheque 
		*/
		$valor=$valor+$row_rs_informacion_cheque['Asi_Val'];
		?>	
		<input  name="datos_ch[<?Php echo $fila_che; ?>,5]" type="text" id="datos_ch[<?Php echo $fila_che; ?>,5]" size="10" maxlength="10"  value="<?Php echo formato_numero($row_rs_informacion_cheque['Asi_Val'],2,1); ?>" style="text-align:right" onblur="numerico(this);" onkeyup="cal_total_cheques(5, 'nfilas_ch', 'datos_ch')"  />
	</td>
    <td ><input type="text" name="datos_ch[<?Php echo $fila_che; ?>,6]" id="datos_ch[<?Php echo $fila_che; ?>,6]" size="10" maxlength="10" value="<?Php echo $row_rs_cheque_consulta['Che_Fec']; ?>"  onKeyUp="mascara(this,'-',patron, true)"  />
	</td>
    	<td >
<input type="text" name="datos_ch[<?Php echo $fila_che; ?>,8]" id="datos_ch[<?Php echo $fila_che; ?>,8]" size="30" value="<?Php echo $row_rs_informacion_cheque['Asi_Con']; ?>" readonly="" />
<input type="hidden" name="datos_ch[<?Php echo $fila_che; ?>,9]" id="datos_ch[<?Php echo $fila_che; ?>,9]" value="<?Php echo $fila_che; ?>" />	   
		</td>
    <td align="center">
<?Php  //if(isset($ocul)){ /* inicio if(isset($ocul)){  antes estaba activo este codigo */ ?>
<input name="quitar_fila" id="quitar_fila" type="button" class="BotonEliminar" onclick="quitar_fila_mod_pre(this)" value="X"  />
	<?Php //} ?>
	</td> 
		
	  	</tr>	 
  <?Php }while($row_rs_informacion_cheque=$obBD_con1->fetch_assoc($rs_informacion_cheque)); 
			} //FIn del if ($num_row_rs_informacion_cheque>0)
  ?>              
			</tbody>
			  <tr>
				<td>&nbsp;</td>
				<td><strong>TOTAL: </strong></td>
				<td><input name="txt_total" type="text" id="txt_total" size="10" readonly="true" style="text-align:right" value="0"></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			  </tr>
              
			</table>
			<br/>
			<table width="607" border="0" cellpadding="0" cellspacing="0">
			  <tr>
				<td width="111" align="left"><input id="nfilas_ch" name="nfilas_ch" type="hidden" value="0" />
                 <button type="button" name="button1" id="button1" class="btn btn-success fileinput-button" title="Agregar tipo de pago" 
        onclick="botones_opcion_compra(1, 'Tbl_Pagos*Tbl_BusCtas*Tbl_Anticipos')">
           <i class="icon-plus icon-white"></i>
           <span>Pagos</span>
           </button>
				</td>
				<td width="110" align="left">
                <button type="button" name="button2" id="button2" class="btn btn-success fileinput-button" title="Buscar cuenta contable" onclick="botones_opcion_compra(2, 'Tbl_Pagos*Tbl_BusCtas*Tbl_Anticipos')">
           <i class="icon-list-alt icon-white"></i>
           <span>Cuentas</span>
    		</button>  
            <input name="cantmodal" id="cantmodal" type="hidden" value="2" />
              </td>
			    <td width="165" align="left">
                <!--<input name="Btn_Anticipo" type="button" class="Boton_Dinero2" id="Btn_Anticipo" title="Cancelar anticipo" onclick="botones_opcion_compra(3, 'Tbl_Pagos*Tbl_BusCtas*Tbl_Anticipos')" value="Anticipos" />--></td>
			    <td width="221" align="left"><?php if ($total_rs_saldo_anticipos > 0){  blink("El proveedor posee Anticipos", "txt_blink", "#FFFF00", "#FF0000"); } ?>
				<input name="total_anticipos" id="total_anticipos" type="hidden" value="<?php echo $total_rs_saldo_anticipos ?>" />
				</td>
			  </tr>
			</table>
			<br />
         <div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
		 </div>
		 <div id="bgmodal"  class="bgmodal"   style="display:none">
         <!--
         Pagos en cheque
         -->
         <FIELDSET id="Tbl_Pagos">
          <LEGEND>
            <label class="Titulos2">Selecci&oacute;n del tipo de pago: </label>
          </LEGEND>	
            <table width="100%" border="0" class="">
            <thead>
			  <tr class="Cabecera1">
				<th width="35%">C&oacute;d. Int. </th>
				<th width="58%">Descripci&oacute;n</th>
				<th width="7%">&nbsp;</th>
			  </tr>
             </thead>
             <tbody>
			  <?Php
			  if ($total_rs_tipo_pagos > 0)
			  {
			  do{
					/**
					* Consulta los bancos con su respectivo asiento contable 
					*/	
					$rs_combo = $obBD_con1->consulta(sentencias_comf(257, $obBD_con1->parametros($Pla_Cod.'*'.$row_rs_tipo_pagos['Pag_Cod'])), $obBD_conexion->conexion);
					$row_rs_combo = $obBD_con1->registros();
					$total_rs_combo = $obBD_con1->numregistros(); 
			
					/**
					* Creacion del Array para luego ser procesado	
					*/
					do{ 
						$ban_cod[]=$row_rs_combo['Ban_Cod'].'*'.$row_rs_combo['Pld_Cod'].'*'.$row_rs_combo['Ban_Tip'];
						$ban_des[]=$row_rs_combo['Pld_Des'];
					} while ($row_rs_combo = $obBD_con1->fetch_assoc($rs_combo));		 
					
					/**
					* Procesamiento del Array a un formato entendible por Javascript
					*/
					$ban_cod='Array(\'' . implode('\', \'', $ban_cod) . '\')';
					$ban_des='Array(\'' . implode('\', \'', $ban_des) . '\')';  		
			  ?>
			  <tr class="Fondo">
				<td align="center"><?Php echo $row_rs_tipo_pagos['Pag_Cod']; ?></td>
				<td><?Php echo $row_rs_tipo_pagos['Pag_Des']; ?></td>
				<td align="center">
                <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="nueva_fila_cheque_com('Tbl_Cheques', <? echo $ban_cod; ?>,<? echo $ban_des; ?>, '<?Php echo $Hdd_Fecha; ?>', '<?Php echo $Hdd_Valor; ?>'); cal_total_cheques(5, 'nfilas_ch', 'datos_ch')">
	        	<i class=" icon-arrow-right icon-white"></i>
    		    </button>
				</td>
			  </tr>
			  <?php
				unset($ban_cod);
				unset($ban_des);
			  }while($row_rs_tipo_pagos=$obBD_con1->fetch_assoc($rs_tipo_pagos));
			  }//Fin del if ($total_rs_tipo_pagos > 0)
			  else
			  { ?>
			  <tr class="Fondo">
				<td align="center">&nbsp;</td>
				<td align="center"><?Php echo error_alerta("No existe una configuraci&oacute;n de los Tipos de Pagos en las facturas de compra", 2); ?></td>
				<td align="center">&nbsp;</td>
			  </tr>
			  <?Php
			  }//Fin del else if ($total_rs_tipo_pagos > 0)
			  ?>
              </tbody>
			</table>
            </FIELDSET>
             <!--
             Buscador de plan de cuentas
             -->            
			<table width="100%"  border="0" cellspacing="0" cellpadding="0" id="Tbl_BusCtas">
			<tr>
				<td><?Php 
					/**
					* C = buscador con cargado en combos 
					*/
					$tipo_busc = 'C'; 
					$Capa = 'busqueda';
					$Nombre_Buscador = 'buscta_combo';//Cuadro de texto
					$Nombre_Opciones = 'op_opciones_combo';//Option
					?>
					<?Php include('../../componentes/FRONT/comConBuscarcta.php'); ?>
				</td>
			</tr>
			</table>
			<!-- 
            Control de anticipos 
            -->
			<table  border="1" cellspacing="0" cellpadding="0" id="Tbl_Anticipos">
              <tr class="Cabecera1">
                <td  width="40">C&oacute;d. Int. </td>
                <td width="280" align="center">Descripci&oacute;n</td>
                <td width="100" align="center">Fecha</td>
                <td width="80">Saldo</td>
                <td width="25">&nbsp;</td>
              </tr>
			  <?php
			if ($total_rs_saldo_anticipos > 0)
			{
			/**
			* Contador para el arreglo del valor total de los anticipos 
			*/
			$i=0;
			  do{
			  		$i++;
					unset($ban_cod);
					unset($ban_des);
					$ban_cod[]= $row_rs_saldo_anticipos['Ant_Cod'].'*'.$row_rs_saldo_anticipos['Pld_Cod'].'*A';
					$ban_des[]= $row_rs_saldo_anticipos['Pld_Des'];			  
			  		/**
					* Procesamiento del Array a un formato entendible por Javascript
					*/
					$ban_cod='Array(\'' . implode('\', \'', $ban_cod) . '\')';
					$ban_des='Array(\'' . implode('\', \'', $ban_des) . '\')';  
					/**
					* Consulta el total pagado del anticipo 
					*/
					$rs_cruzado_anticipos = $obBD_con1->consulta(sentencias_comf(194, $obBD_con1->parametros($row_rs_saldo_anticipos['Ant_Cod'])), $obBD_conexion->conexion);
					$row_rs_cruzado_anticipos = $obBD_con1->registros();
					$saldo = $row_rs_saldo_anticipos['Asi_Val'] - $row_rs_cruzado_anticipos['Ant_Val'];					
				/**
				* Entra en caso de un anticipo tenga algo pendiente por pagar 
				*/	
				if ($saldo >0)
				{	
			  ?>
              <tr class="Fondo">
                <td align="center"><?php echo $row_rs_saldo_anticipos['Pld_Cod']; ?></td>
                <td><?php echo $row_rs_saldo_anticipos['Pld_Des']; ?></td>
                <td align="center"><?php echo $row_rs_saldo_anticipos['Ant_Fec']; ?></td>
                <td align="right"><?php echo formato_numero($saldo,2,3); ?>
				<input name="sal_anticipos[<?php echo $i; ?>]" id="sal_anticipos[<?php echo $i; ?>]" type="hidden" value="<?php echo round($saldo,2); ?>" />				
				<input name="name_cheque[<?php echo $i; ?>]" id="name_cheque[<?php echo $i; ?>]" type="hidden" value="" />
				</td>
                <td align="center"><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Agregar cuenta" style="	
					cursor:pointer" onclick="if (existe_cheque('nfilas_ch', 'datos_ch', 3, <? echo $ban_cod; ?>)== true){ nueva_fila_cheque_com('Tbl_Cheques', <? echo $ban_cod; ?>,<? echo $ban_des; ?>, '<?Php echo $Hdd_Fecha; ?>', '<?Php echo "sal_anticipos[".$i."]"; ?>'); cal_total_cheques(5, 'nfilas_ch', 'datos_ch'); validar_anticipos_cruce(<?php echo $i; ?>); }" /></td>
              </tr>
			  <?Php
			  	}//Fin del if ($saldo >0)
			  }while($row_rs_saldo_anticipos = $obBD_con1->fetch_assoc($rs_saldo_anticipos));
			}//Fin del if ($total_rs_saldo_anticipos > 0)
			else
			{ ?>
			 <tr>
			 	<td colspan="5"><?php echo error_alerta("No existen anticipos que mostrar", 2); ?></td>
			</tr>
		 	   <?php									
			}//Fin del else if ($total_rs_saldo_anticipos > 0)
			  ?>
            </table>
            </div>
            </FIELDSET>
			<?php
			@$obBD_con1->free_result($rs_tipo_pagos);
			@$obBD_con1->free_result($rs_combo);?>			
<?Php
		}//Fin del if (isset($Hdd_Fecha))
		else
		{
			 echo error_alerta("<< Error de componente: tes_com_cheques.php >> <br>Descripción: No se ha definido la Propiedad: Hdd_Fecha<br>
								Hdd_Fecha: Variable que contiene el nombre del texto que posse la fecha del documento", 2); 				
		}//Fin del else if (isset($Hdd_Fecha))
	}//Fin del if (isset($Hdd_Valor))
	else
	{
		 echo error_alerta("<< Error de componente: tes_com_cheques.php >> <br>Descripción: No se ha definido la Propiedad: Hdd_Valor<br>
								Hdd_Valor: Variable que contiene el nombre del texto que posse el valor del documento", 2); 
	}
}//Fin del if (isset($For_Cod))
else
{
	 echo error_alerta("<< Error de componente: tes_com_cheques.php >> <br>Descripción: No se ha definido la Propiedad: For_Cod<br>
							For_Cod: Variable que contiene la forma de pago ", 2); 
}//Fin del else if (isset($For_Cod))
?>