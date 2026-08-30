<?Php   require_once('../../tesoreria/LOGICA/logica.php');
/* componente que muestra las deudas d elos estudiantes */
 $Fec_Act=date('Y-m-d'); 
if ((isset($hdd_save2)) && !(isset($proye)) || isset($proye))
{
	
	/*****************************************************/
	/*    FUNCION QUE CARGA AUTOMATICAMENTE LOS RUBROS   */
	/*****************************************************/
	generar_deudas($obBD_con1, $obBD_conexion, $Cli_Cod);
	/****************************************************/
	
	// Cargado de los resultados de la busqueda de producto
	$rs_deuda = $obBD_con1->consulta(sentencias_tes(55, $obBD_con1->parametros($Cli_Cod)), $obBD_conexion->conexion);
	$row_rs_deuda = $obBD_con1->registros();
	$total_rs_deuda = $obBD_con1->numregistros();
	/* Configuraci�n del modulo de tesoreria */
	$rs_confi_teso = $obBD_con1->consulta(sentencias_tes(46, ''), $obBD_conexion->conexion);
	$row_rs_confi_teso = $obBD_con1->registros();
	?>
		
	<FIELDSET class="Busqueda_ajax">
	<label class="Titulos2">Cuentas por cobrar</label>
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
      <?php
		if ($total_rs_deuda > 0) {
	  		  $leyenda = "no";
			  $puntero_actual = $row_rs_deuda['Car_Int'];
			  /* Contador para saber cuantas veces muestra una descipcion */
			  $cont = 1;	
			  $cont2 = 1;	
			  $cont_car = 1;//--//
			  /*Variables para el control de suma deudas */
			  $suma_deuda=0;
			  $suma_interes=0;			  			  	
			do { 
				/* Control para reiniciar la presentacion del diario */ 
				if ($puntero_actual != $row_rs_deuda['Car_Int'])
				{
					$puntero_actual=$row_rs_deuda['Car_Int'];
					$cont=1;		
				    $cont2 = 1;	
					$cont_car=1;//--//													
				}			
				$Pro_Cod = $row_rs_deuda['Pro_Cod'];
				$Nge_Cod = $row_rs_deuda['Nge_Cod'];
				$Sem_Cod = $row_rs_deuda['Sem_Cod'];
				$Asi_Int = $row_rs_deuda['Asi_Int'];
				/* COnsulta los pagos realizados segun el Nge_Cod */
				$rs_pagos = $obBD_con1->consulta(sentencias_tes(68, $obBD_con1->parametros($Cli_Cod.'*'.$Pro_Cod.'*'.$Nge_Cod.'*'.$Asi_Int)), 
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
        <td colspan="8"><strong>
          <?Php 
	/****Consultar la descriopcion de la carrera en base al codigo de la carrera seleccionada*/
	$rs_carrera = $obBD_con1->consulta(sentencias_tes(742, $obBD_con1->parametros($Car_Int)), $obBD_conexion->conexion);
	$row_rs_carrera = $obBD_con1->registros();
	$total_rs_carrera = $obBD_con1->numregistros();
	
	echo $row_rs_carrera['Car_Nom']; 
	?>
        </strong></td>
      </tr>
      <?Php
				}//Fin del if ($cont==1)
			?>
      <?Php
			$saldo = (round($row_rs_deuda['Deu_Val'],2) - round($valor_beca,2)) - round($row_rs_pagos['Vet_Imp'],2); 
			//$saldo = number_format($row_rs_deuda['Deu_Val'] - $valor_beca,2) - number_format($row_rs_pagos['Vet_Imp'],2);// 
			 if ($cont==1)
			  { 
			  if ($saldo > 0)//--//
			  { 
			  	$cont++;	//--//			  
			  ?>
      <tr class="Cabecera_ajax">
        <td colspan="8"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
            <tr>
              <td width="6%"><strong>Periodo:</strong></td>
              <td width="44%"><?Php   echo $row_rs_deuda['Mes_Ini']."-".$row_rs_deuda['Ann_Ini']." / ".
												  $row_rs_deuda['Mes_Fin']."-".$row_rs_deuda['Ann_Fin']; 
										?>              </td>
              <td width="7%"><strong>Curso:</strong></td>
              <td width="43%"><?Php echo $row_rs_deuda['Sem_Nom']."-".$row_rs_deuda['Mod_Des']; ?> </td>
            </tr>
        </table></td>
      </tr>
      <?Php
				}//Fin del if ($saldo > 0)
			  }//Fin del if ($cont==1) ?>
      <?Php 
			/* Solo entra y muestra las deudas sean mayores a CERO */
			if ($saldo > 0)
			{
				$cont_deu++;		
				
				/************ CONTROL PARA EL CALCULO DEL INTERES ***************/
				/****************************************************************/
				/* Calculo del interes */
				interes($obBD_con1, $obBD_conexion, $Cli_Cod, $row_rs_deuda['Pro_Cod'], $row_rs_deuda['Nge_Cod'], $row_rs_deuda['Asi_Int'], $saldo);
				// $dis_res=encontrarcant_dias($row_rs_deuda['Deu_Fec'], $fecha,1);
				/* Consulta los rubro del intereses*/
				$rs_interes = $obBD_con1->consulta(sentencias_tes(58, $obBD_con1->parametros($Cli_Cod.'*'.
				$Nge_Cod.'*'.$row_rs_deuda['Asi_Int'].'*'.$Pro_Cod)), $obBD_conexion->conexion);
				$row_rs_interes = $obBD_con1->registros();
				$total_rs_interes = $obBD_con1->numregistros();
				/****************************************************************/
					
				/****************************************************************/				
				
			if ($cont2==1)
			{
			  ?>
      <tr class="Cabecera_ajax">
        <td width="10%"><strong>C&oacute;d Int </strong></td>
        <td width="20%"><strong>C&oacute;digo</strong></td>
        <td width="20%"><strong>Descripci&oacute;n</strong></td>
        <td width="10%"><strong>Fecha Vencimiento</strong></td>
        <td width="10%"><strong>Valor</strong></td>
        <td width="20%"><strong>% <?php echo $row_rs_becas['Tib_Ini']; ?></strong></td>
        <td width="20%"><strong>Valor Pagar</strong></td>
        <td width="10%">&nbsp;</td>
      </tr>
      <?Php
			}//Fin del if ($cont2==1)
			?>
      <tr width="10%" <?Php if ($row_rs_deuda['Deu_Fec'] < $Fec_Act){  $leyenda = "si"; echo "class='LetraNegra' bgcolor='".$row_rs_confi_teso['Col_Cad']."'"; } else { ?> class="Cuerpo_ajax" <?php } ?>>
        <td align="center" width="10%"><?php echo $row_rs_deuda['Pro_Cod']; ?></td>
        <td align="center" width="20%"><?php echo $row_rs_deuda['Pro_Ide']; ?></td>
        <td width="20%"><?php echo $row_rs_deuda['Ite_Lar']; ?></td>
        <td width="10%" align="center"><?php echo $row_rs_deuda['Deu_Fec']; ?></td>
        <td width="10%" align="right"><?php echo formato_numero($row_rs_deuda['Deu_Val'] -  $row_rs_pagos['Vet_Imp'],2,4); ?></td>
        <td width="20%" align="center"><?Php echo $mensaje; ?></td>
        <td width="20%" align="right"><?php echo formato_numero($saldo,2,4); ?> </td>
        <?php 	$rs_prod_inter = $obBD_con1->consulta(sentencias_tes(655, $obBD_con1->parametros('')), $obBD_conexion->conexion);
				$row_rs_prod_inter = $obBD_con1->registros();
				$total_rs_prod_inter = $obBD_con1->numregistros();
				/* Calculo del total suma deuda */ 
				$suma_deuda= $suma_deuda + $saldo;				
				?>
        <td align="center" class="Cuerpo_ajax">&nbsp;</td>
      </tr>
      <!-- Cargado del Interes -->
      <?Php					
					if ($total_rs_interes > 0)
					{ 
						do{
						/* COnsulta los pagos realizados segun el Nge_Cod */
						$rs_pagos_int = $obBD_con1->consulta(sentencias_tes(69, $obBD_con1->parametros($Cli_Cod.'*'.
										$row_rs_interes['Pro_Cod'].'*'.$Nge_Cod.'*'.$Pro_Cod.'*'.$Asi_Int)), $obBD_conexion->conexion);	
						$row_rs_pagos_int = $obBD_con1->registros();

						/* Saldo del interes */
						$saldo_int = round($row_rs_interes['Deu_Val'],2) - round($row_rs_pagos_int['Vet_Imp'],2);
						
						//$saldo_int = number_format($row_rs_interes['Deu_Val'],2) - number_format($row_rs_pagos_int['Vet_Imp'],2);

						/* Solo entra y muestra las deudas sean mayores a CERO */
		if ($saldo_int > 0)
		{ 
		
		?>
      <tr <?Php if ($row_rs_interes['Deu_Fec'] < $hoy){  $leyenda = "si"; echo "class='LetraNegra' bgcolor='".$row_rs_confi_teso['Col_Cad']."'"; } else { ?> class="Cuerpo_ajax" <?php } ?>>
        <td align="center"><strong><?php echo $row_rs_interes['Pro_Cod']; ?></strong></td>
        <td align="center"><strong><?php echo $row_rs_interes['Pro_Ide']; ?></strong></td>
        <td><strong><?php echo $row_rs_interes['Ite_Lar']; ?></strong></td>
        <td align="center"><strong><?php echo $row_rs_interes['Deu_Fec']; ?></strong></td>
        <td align="right"><strong><?php echo formato_numero($row_rs_interes['Deu_Val'] -  $row_rs_pagos_int['Vet_Imp'],2,4); ?></strong></td>
        <td align="center">&nbsp;</td>
        <td align="right"><strong><?php echo formato_numero($saldo_int,2,4);
		/* Calculo del total suma interes */ 
	     $suma_interes= $suma_interes + $saldo_int; ?></strong></td>
        <td align="center" class="Cuerpo_ajax"><?Php //if ($row_rs_deuda['Pro_Cod'] == $row_rs_prod_inter['Pro_Cod']) { ?>
          <input type="image" width="42" height="40" name="imageField" src="../../mascaras/model1/imagenes/32x32/finanzas.jpg" />
          <a href="<?Php echo $_SERVER['PHP_SELF'] ;?>?proye=1&amp;Pro_Ide=<?Php echo $row_rs_deuda['Pro_Cod'];?>&amp;Cli_Cod=<?php echo $row_rs_existe['Cli_Cod']; ?>&amp;interes=<?php echo $saldo_int; ?>" 
		title="Presiona aqui para ver la proyeccion de interes en los dias posteriores">Inter&eacute;s diario </a>
            <?Php //} else { echo "&nbsp;"; } ?></td>
      </tr>
	
      <?Php 
						}//Fin del if ($saldo_int > 0)
						}while($row_rs_interes=mysqli_fetch_assoc($rs_interes));
					}//Fin del if ($total_rs_interes > 0)
				
			     $cont2++;	
				}//Fin del if (($row_rs_deuda['Deu_Val'] - $row_rs_pagos['Vet_Imp']) > 0 )
				/* Contador para poder mostrar la descripcion una sola vez en la tabla */
				$cont_car++;//--//	   
				}while ($row_rs_deuda = mysqli_fetch_assoc($rs_deuda)); ?>
			 <tr class="Cuerpo_ajax">
        <td colspan="6" align="center"><div align="right"><strong>Total a pagar</strong>:</div></td>
        <td align="right"><strong><?Php echo $suma_deuda+$suma_interes; ?>&nbsp;</strong></td>
        <td align="center">&nbsp; </td>
      </tr>
	<?php							
	  		} else { ?>
      <tr>
        <td colspan="8"><div align="center"><?php echo error_alerta("No hay resultados que mostrar", 1)?></div></td>
      </tr>
      <?php }?>
	  </table>
	  
	  	<br>
			<fieldset>
			<LEGEND>
		<label class="Titulos2">Se&ntilde;ores estudiantes: </label>
	</LEGEND>	
<table width="100%" border="0">
  <tr>
    <td class="LetraNegra Estilo1"><div align="justify">Se les comunica, que la papeleta de dep&oacute;sito debe ser canjeada por la factura en el Dpto. de Tesoreria el mismo d&iacute;a  que se cancela al banco, caso contrario  se incrementara el 0.07% de interes diario. </div></td>
  </tr>
  <tr>
    <td class="LetraNegra Estilo1">
  Para mayor seguridad verificar la proyecci�n de intereses  en la opci�n... <span class="Estilo2">&iexcl;Click aqui! proyecci&oacute;n de inter&eacute;s diario</span></td>
  </tr>
</table>
</fieldset>
		<?php if ($leyenda == "si")
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
		  
		  <?Php
		  }//Fin del if ($leyenda == "si") ?>		
	</FIELDSET>
	<?php } ?>
        
		   
<?php if(isset($proye) && !isset($hdd_save2)) 
 {
 	//echo $Cli_Cod;
	$rs_existe = $obBD_con1->consulta(sentencias_tes(654, $obBD_con1->parametros($Ses_Prs_Cod)), $obBD_conexion->conexion);
$row_rs_existe = $obBD_con1->registros();
$total_rs_existe = $obBD_con1->numregistros();

	
 ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Proyecciones de interes:</label>
</LEGEND>
<?php
// Cargado de los resultados de la busqueda de producto
    $rs_deuda_pro = $obBD_con1->consulta(sentencias_tes(656, $obBD_con1->parametros($Cli_Cod.'*'.$Pro_Ide)), $obBD_conexion->conexion);
	$row_rs_deuda_pro= $obBD_con1->registros();
	//echo $row_rs_deuda_pro['Ite_Lar'];
	$total_rs_deuda = $obBD_con1->numregistros();
	/*** Consultar valor del porcentaje**********************************************************************************/
	$rs_por=$obBD_con1->consulta(sentencias_tes(657, $obBD_con1->parametros('')), $obBD_conexion->conexion);
	$row_rs_por=$obBD_con1->registros();
		
	/*************************************************************************************************************/
	$Pro_Cod = $row_rs_deuda_pro['Pro_Cod'];
	$Nge_Cod = $row_rs_deuda_pro['Nge_Cod'];
	$Sem_Cod = $row_rs_deuda_pro['Sem_Cod'];
	$Asi_Int = $row_rs_deuda_pro['Asi_Int'];
	/* COnsulta los pagos realizados segun el Nge_Cod */
$rs_pagos = $obBD_con1->consulta(sentencias_tes(68, $obBD_con1->parametros($Cli_Cod.'*'.$Pro_Cod.'*'.$Nge_Cod.'*'.$Asi_Int)), 
	$obBD_conexion->conexion);	
	$row_rs_pagos = $obBD_con1->registros();
	/********************************************/
	/***** CONTROL DE BECAS ********/
	/* Consulta el pocentaje de la beca asignado*/
				$rs_becas = $obBD_con1->consulta(sentencias_tes(76, $obBD_con1->parametros($row_rs_deuda_pro['Bec_Cod'].'*'.
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
					
				$valor_beca = ($row_rs_deuda_pro['Deu_Val'] * $porc_beca)/100; 
				//echo $valor_beca;					

				/********************************************/
				/********************************************/
				if ($saldo>0)
				{
				$saldo = $row_rs_deuda_pro['Deu_Val'] - $valor_beca - formato_numero($row_rs_pagos['Vet_Imp'],2,2);
				//echo $saldo; 
				}
				

		if ($saldo >=0)
		{
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr class="Cuerpo_ajax">
    <td width="8%"><strong>Rubro:</strong></td>
	<td width="68%"><?php echo $row_rs_deuda_pro['Ite_Lar']; ?></td>
    <td width="4%"><strong>Valor</strong></td>
	<td width="20%"><?php echo formato_numero($interes,2,2); ?></td>
  </tr>
</table>
<?php  } ?>



<table width="100%" border="0" class="Cabecera_ajax">
  <tr>
    <td width="20%"><strong>Fecha</strong></td>
    <td width="20%"><strong>Dias Mora</strong></td>
	<td width="20%"><strong>% Diario Int.</strong></td>
	<td width="20%"><strong>% Total Int.</strong></td>
	<td width="10%"><strong>$ Total Int.</strong></td>
	<td width="10%"><strong>$ Total a pagar</strong></td>
  </tr>
<?php 
$j=0;
for($j=0;$j<=10;$j++)
{ 
    $fecha=fechas_futuras($Fec_Act, $j);
	//echo $fecha;
?>

 <tr class="Cuerpo_ajax" align="center">
   <td width="20%"><?php  echo $fecha; ?>  </td>
    <td width="20%">
	<?Php  
	  /*** Calculo de diferencia de d�as ***************************************************************/
	   $Fecha=explode('-',$row_rs_deuda_pro['Deu_Fec']);
			$Fecha2=explode('-',$fecha);
	  		$timestamp1 = mktime(0,0,0,$Fecha[1],$Fecha[2],$Fecha[0]);
			$timestamp2 = mktime(0,0,0,$Fecha2[1],$Fecha2[2],$Fecha2[0]);
 		    $segundos_diferencia = $timestamp1 - $timestamp2;  		
			$dias_diferencia = $segundos_diferencia / (60 * 60 * 24); 
			//echo $dis_res.'<br>';
			$dias_difer= abs($dias_diferencia)-$row_rs_por['Int_Dia'];
			echo $dias_difer; //-$dis_res;			
		/*************************************************************************************************/
	             ?> </td>
	<td width="20%"><?php echo $row_rs_por['Int_Por']."%"; ?></td>
	<?php 
	 
	//for($i=0; $i=inter; $i++){  ?>
	<td width="20%"><?php $inter= ($dias_difer* $row_rs_por['Int_Por']);
						echo number_format($inter,2)."%";
	 ?></td>
	<td width="10%"><?php $tot_int=($saldo * $inter)/100;
						echo number_format($tot_int,2);
	 ?></td>
	 <?php //for ($i=0; $i=$interes; $i++){ ?>
	 
	<td width="10%"><?php echo number_format($saldo + $tot_int,2); ?></td>
	<?php //} ?>
 </tr>
 
 <?php } //fin del for?>
</table>
        
      </fieldset>
      <?php //} ?>
    </form>
	<?php
@$obBD_con1->free_result($rs_buscta);
exit();
}//****Cierre de las deudas de los estudiantes****?>