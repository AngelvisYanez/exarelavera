<?Php   require_once('../../tesoreria/LOGICA/logica.php');
/* componente que muestra las deudas d elos estudiantes */
if (isset($deudas))
{
	// Cargado de los resultados de la busqueda de producto
	$rs_deuda = $obBD_con1->consulta(sentencias_tes(55, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
	$row_rs_deuda = $obBD_con1->registros();
	$total_rs_deuda = $obBD_con1->numregistros();
	/* Configuración del modulo de tesoreria */
	$rs_confi_teso = $obBD_con1->consulta(sentencias_tes(46, ''), $obBD_conexion->conexion);
	$row_rs_confi_teso = $obBD_con1->registros();
		
	?>
<FIELDSET class="Busqueda_ajax">
   <label class="Titulos2">Cuentas por cobrar</label>
<table width="100%" border="1" cellpadding="0" cellspacing="0" class="Busqueda_ajax">
		<?
		if ($total_rs_deuda > 0) {
	  		  $leyenda = "no";
			  $puntero_actual = $row_rs_deuda['Car_Int'];
			  /* Contador para saber cuantas veces muestra una descipcion */
			  $cont = 1;	
			  $cont2 = 1;	
			  $cont_car = 1;			  	
			do { 
				/* Control para reiniciar la presentacion del diario */ 
				if ($puntero_actual != $row_rs_deuda['Car_Int'])
				{
					$puntero_actual=$row_rs_deuda['Car_Int'];
					$cont=1;		
				    $cont2 = 1;	
					$cont_car=1;								
				}			
				$Pro_Cod = $row_rs_deuda['Pro_Cod'];
				$Nge_Cod = $row_rs_deuda['Nge_Cod'];
				$Sem_Cod = $row_rs_deuda['Sem_Cod'];
				$Asi_Int = $row_rs_deuda['Asi_Int'];
				/* Consulta los pagos realizados segun el Nge_Cod */
				$rs_pagos = $obBD_con1->consulta(sentencias_tes(68, $obBD_con1->parametros($codigo.'*'.$Pro_Cod.'*'.$Nge_Cod.'*'.$Asi_Int)), 
										$obBD_conexion->conexion);	
				$row_rs_pagos = $obBD_con1->registros();
				
				
				/********************************************/
				/*****        CONTROL DE BECAS       ********/
				/* Consulta el pocentaje de la beca asignado*/
				$rs_becas = $obBD_con1->consulta(sentencias_tes(76, $obBD_con1->parametros($row_rs_deuda['Bec_Cod'].'*'.
										$Pro_Cod)), $obBD_conexion->conexion);	
				$row_rs_becas = $obBD_con1->registros();
				$total_rs_becas = $obBD_con1->numregistros();

				if ($row_rs_becas['Bec_Pot'] > 0)
				{ 
					$mensaje = $row_rs_becas['Bec_Pot']; 
					$porc_beca = $row_rs_becas['Bec_Pot']; 
				}//Fin del if ($row_rs_becas['Bec_Pot'] >= 0)			
				else
				{
					if ($row_rs_becas['Bec_Por'] > 0)
					{ 
						$mensaje = $row_rs_becas['Bec_Por']; 
						$porc_beca = $row_rs_becas['Bec_Por']; 										
					}			
					else
					{
						$mensaje = "&nbsp;";
						$porc_beca = 0;
					}								
				}//Fin del else if ($row_rs_becas['Bec_Pot'] >= 0)		
					
				$valor_beca = ($row_rs_deuda['Deu_Val'] * $porc_beca)/100; 					

				/********************************************/
				/********************************************/
																			
				if ($cont_car==1)//Antes $cont = 1
				{
				?>							
			<tr class="LetraNegra">
				<td width="11%"><strong>Carrera:</strong></td>
				<td colspan="8"><strong><?Php echo $row_rs_deuda['Car_Nom']; ?></strong></td>
			</tr>
			<?Php
				}//Fin del if ($cont==1)

			$saldo = (round($row_rs_deuda['Deu_Val'],2) - round($valor_beca,2)) - round($row_rs_pagos['Vet_Imp'],2); 
//echo $saldo."<br>";
			 if ($cont==1)
			  { 
			  if ($saldo > 0)
			  { 
			  	  $cont++;	
			  ?>
			 <tr class="Cabecera_ajax">
      		   <td colspan="9">
				  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
					<tr>
					  <td width="11%"><strong>Periodo:</strong></td>
					  <td width="39%"><?Php echo $row_rs_deuda['Mes_Ini']."-".$row_rs_deuda['Ann_Ini']." / ".
												  $row_rs_deuda['Mes_Fin']."-".$row_rs_deuda['Ann_Fin']; 
										?> </td>
					  <td width="11%"><strong>Semestre: </strong></td>
					  <td width="39%">&nbsp;<?Php echo $row_rs_deuda['Sem_Nom']; ?> </td>
					</tr>
				  </table>
 			   </td>
	   		</tr>
			<?Php } //Fin del if ($saldo > 0)
			  }//Fin del if ($cont==1) ?>
			<?Php 
			/* Solo entra y muestra las deudas sean mayores a CERO */
			if ($saldo > 0)
			{
				$cont_deu++;		
				
				/************ CONTROL PARA EL CALCULO DEL INTERES ***************/
				/****************************************************************/
				/* Calculo del interes */
				interes($obBD_con1, $obBD_conexion, $codigo, $row_rs_deuda['Pro_Cod'], $row_rs_deuda['Nge_Cod'], 
						$row_rs_deuda['Asi_Int'], $saldo);
				/* Consulta los rubro del intereses*/
				$rs_interes = $obBD_con1->consulta(sentencias_tes(58, $obBD_con1->parametros($codigo.'*'.
							$Nge_Cod.'*'.$row_rs_deuda['Asi_Int'].'*'.$Pro_Cod)), $obBD_conexion->conexion);
				$row_rs_interes = $obBD_con1->registros();
				$total_rs_interes = $obBD_con1->numregistros();
				/****************************************************************/
				/****************************************************************/				
				
			if ($cont2==1)
			{
			  ?>		  
      		<tr class="Cabecera_ajax">
      		  <td width="8%"><strong>C&oacute;d Int </strong></td>
        		<td width="8%"><strong>C&oacute;digo</strong></td>
        		<td width="40%"><strong>Descripci&oacute;n</strong></td>
        		<td width="12%"><strong>M&oacute;dulo</strong></td>				
        		<td width="8%"><strong>Fecha Vencimiento</strong></td>
				<td width="8%"><strong>Valor</strong></td>		
				<td width="4%"><strong>% <?php echo $row_rs_becas['Tib_Ini']; ?></strong></td>								
				<td width="8%"><strong>Valor Pagar</strong></td>												
				<td width="4%">&nbsp;</td>
				
        	</tr>
			<?Php
			}//Fin del if ($cont2==1)
			
			if ($row_rs_deuda['Asi_Int'] != 0)
			{
				/* Consulta las descripción del modulo */
				$rs_modulos = $obBD_con1->consulta(sentencias_tes(186, $obBD_con1->parametros($row_rs_deuda['Asi_Int'])), 
											$obBD_conexion->conexion);	
				$row_rs_modulos = $obBD_con1->registros();			
			}//Fin del if ($row_rs_deuda['Asi_Int'] != 0) ?>
      		<tr <?Php if ($row_rs_deuda['Deu_Fec'] < $hoy){  $leyenda = "si"; echo "class='LetraNegra' bgcolor='".$row_rs_confi_teso['Col_Cad']."'"; }else { ?> class="Cuerpo_ajax" <?php } ?>>
      			<td align="center"><? echo $row_rs_deuda['Pro_Cod']; ?></td>
        		<td align="center" width="8%"><? echo $row_rs_deuda['Pro_Ide']; ?></td>
        		<td><? echo $row_rs_deuda['Ite_Lar']; ?></td>
        		<td><? echo $row_rs_modulos['Asi_Des']."&nbsp;"; ?></td>						
				<td align="center" width="8%"><? echo $row_rs_deuda['Deu_Fec']; ?></td>
				<td align="right"><? echo formato_numero($row_rs_deuda['Deu_Val'] -  $row_rs_pagos['Vet_Imp'],2,1); ?></td>						
				<td align="center"><?Php echo $mensaje; ?></td>
				<td align="right"><? echo formato_numero($saldo,2,1); 
				/* Calculo del total suma interes */ 
	     			$suma_deuda= $suma_deuda + $saldo; ?></td>																		
        		<td align="center" class="Cuerpo_ajax" width="4%"><img src="../../imagenes/insertar.jpg" style="cursor:pointer" width="22" height="22" 
				onClick="nueva_fila('c_contenido','<?php echo $row_rs_deuda['Pro_Ide'];?>','<?php echo $row_rs_deuda['Pro_Cod'];?>','<?php echo $row_rs_deuda['Ite_Lar'];?>','<?php echo formato_numero(($row_rs_deuda['Deu_Val'] - $valor_beca) -  $row_rs_pagos['Vet_Imp'],2,1); ?>','<?php echo $row_rs_deuda['Iva_Por'];?>','<?php echo $row_rs_deuda['Iva_Cod'];?>','<?php echo $row_rs_deuda['Nge_Cod'];?>','<?php echo $row_rs_deuda['Deu_Rec'];?>','<?Php echo $_SERVER['PHP_SELF']; ?>','<?php echo $row_rs_deuda['Asi_Int'];?>','si','si',<?php echo $total_rs_interes; ?>);
						<?Php 
					if ($total_rs_interes > 0)
					{ 						
						/* Agrega automaticamente los rubros recursivos */
						do{ //Fin del }while($row_rs_interes=mysqli_fetch_assoc($rs_interes));
							/* COnsulta los pagos realizados segun el Nge_Cod */
							$rs_pagos_int = $obBD_con1->consulta(sentencias_tes(69, $obBD_con1->parametros($codigo.'*'.
											$row_rs_interes['Pro_Cod'].'*'.$Nge_Cod.'*'.$Pro_Cod.'*'.$Asi_Int)), 
											$obBD_conexion->conexion);	
							$row_rs_pagos_int = $obBD_con1->registros();
							
							/* Saldo del interes */
							$saldo_int = round($row_rs_interes['Deu_Val'],2) - round($row_rs_pagos_int['Vet_Imp'],2);

						if ($saldo_int > 0)
						{											
							?>
							nueva_fila('c_contenido','<?php echo $row_rs_interes['Pro_Ide'];?>','<?php echo $row_rs_interes['Pro_Cod'];?>','<?php echo $row_rs_interes['Ite_Lar'];?>','<?php echo formato_numero(($row_rs_interes['Deu_Val']) - $row_rs_pagos_int['Vet_Imp'],2,1); ?>','<?php echo $row_rs_interes['Iva_Por'];?>','<?php echo $row_rs_interes['Iva_Cod'];?>','<?php echo $row_rs_interes['Nge_Cod'];?>','<?php echo $row_rs_interes['Deu_Rec'];?>','<?Php echo $_SERVER['PHP_SELF']; ?>','<?php echo $row_rs_interes['Asi_Int'];?>','si','no','-1'); 												
						<?Php	//Se agrega -1 para saber q se debe bloquear el campo de precio unitario						
						}//FIn del if ($saldo_int > 0)
						}while($row_rs_interes=$obBD_con1->fetch_assoc($rs_interes));
						/* Mueve el puntero a la posicion inicial */
						$row_rs_interes = first_last($rs_interes, $row_rs_interes, 0);
					}//FIn del if ($total_rs_interes > 0)
						?>; 
					asignar_total_fac();">
				</td>
  </tr>
					<!-- Cargado del Interes -->
					<?Php					
					if ($total_rs_interes > 0)
					{ 
						do{
						/* COnsulta los pagos realizados segun el Nge_Cod */
						$rs_pagos_int = $obBD_con1->consulta(sentencias_tes(69, $obBD_con1->parametros($codigo.'*'.
										$row_rs_interes['Pro_Cod'].'*'.$Nge_Cod.'*'.$Pro_Cod.'*'.$Asi_Int)), $obBD_conexion->conexion);	
						$row_rs_pagos_int = $obBD_con1->registros();

						/* Saldo del interes */
						$saldo_int = round($row_rs_interes['Deu_Val'],2) - round($row_rs_pagos_int['Vet_Imp'],2);

						/* Solo entra y muestra las deudas sean mayores a CERO */
						if ($saldo_int > 0)
						{					
					?>
				
				<tr <?Php if ($row_rs_interes['Deu_Fec'] < $hoy){  $leyenda = "si"; echo "class='LetraNegra' bgcolor='".$row_rs_confi_teso['Col_Cad']."'"; } else { ?> class="Cuerpo_ajax" <?php } ?>>
      				  <td align="center"><strong><? echo $row_rs_interes['Pro_Cod']; ?></strong></td>
        			  <td align="center"><strong><? echo $row_rs_interes['Pro_Ide']; ?></strong></td>
        			  <td><strong><a class="href_4" title="Proyecci&oacute;n de interes en los proximos d&iacute;as" style="cursor:pointer" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?codigo=<?Php echo $codigo; ?>&Pro_Cod=<?Php echo $Pro_Cod; ?>&interes=<?php echo $saldo_int; ?>&proye', 'proyecciones')"><? echo $row_rs_interes['Ite_Lar']; ?></a> </strong></td>
					  <td>&nbsp;</td>							
					  <td align="center"><strong><? echo $row_rs_interes['Deu_Fec']; ?></strong></td>
					  <td align="right"><strong><? echo round($row_rs_interes['Deu_Val'] -  $row_rs_pagos_int['Vet_Imp'],2); ?></strong></td>						
					  <td align="center">&nbsp;</td>
					  <td align="right"><strong><? echo number_format($saldo_int,2); ?></strong>
					  <?php /* Calculo del total suma interes */ 
						     $suma_interes= $suma_interes + $saldo_int; ?>
					  
					  </td>
					  <td align="center" class="Cuerpo_ajax">&nbsp;</td>																											
        				
					</tr>
      			<?Php 
						}//Fin del if ($saldo_int > 0)
						}while($row_rs_interes=$obBD_con1->fetch_assoc($rs_interes));
					}//Fin del if ($total_rs_interes > 0)
				
			     $cont2++;	
				}//Fin del if (($row_rs_deuda['Deu_Val'] - $row_rs_pagos['Vet_Imp']) > 0 )
				/* Contador para poder mostrar la descripcion una sola vez en la tabla */
				//$cont++;	
				$cont_car++;///***************ojo nuevo 
				}while($row_rs_deuda = $obBD_con1->fetch_assoc($rs_deuda));	?>			
				 <tr class="Cuerpo_ajax">
        <td colspan="7" align="center"><div align="right"><strong>Total a pagar</strong></div></td>
        <td align="right"><strong><?Php echo number_format($suma_deuda+$suma_interes, 2); ?>&nbsp;</strong></td>
        <td align="center">&nbsp; </td>
      </tr> <?php
	  		} 
			
			else { ?>
	  				<tr>
						<td colspan="9"><div align="center"><?php echo error_alerta("No hay resultados que mostrar", 1)?></div></td>
	  				</tr>
	  		<? }?>
			<tr>
				<td colspan="9" align="center"><img src="../../imagenes/ocultar2.jpg" height="12" style="cursor:pointer" alt="Ocultar" onClick="ShowHide('deudas_table')">
				</td>
			</tr>
</table>
		<?php  echo barra_estado($cont_deu); 

		  if ($leyenda == "si")
		  {
		  ?>
		  <br>
	 <table width="226" border="1" cellpadding="0" cellspacing="0" class="Titulos2">
			<tr>
			  <td colspan="2">Leyenda:</td>
		    </tr>
			<tr>
			  <td width="45" bgcolor="<?Php echo $row_rs_confi_teso['Col_Cad']; ?>">&nbsp;</td>
			  <td width="175"> &nbsp;Cuentas por cobrar vencidas </td>			    
			</tr>
	  </table>
	<table width="100%" border="0">
  <tr>
    <td><div id="proyecciones"></div></td>
  </tr>
</table>
  
		  <?Php
		  }//Fin del if ($leyenda == "si") 	
		  ?>
	</FIELDSET>
	<?php

@$obBD_con1->free_result($rs_buscta);
exit();
}//****Cierre de las deudas de los estudiantes****?>	
