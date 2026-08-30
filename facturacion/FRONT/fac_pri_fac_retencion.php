<?php 
   	require_once('../../administrador/LOGICA/seguridad.php');
	require_once('../../Librerias/operacion.php');
	require_once('../LOGICA/logica.php');
    require_once('../../Librerias/procedimientos/almacenados_standar.php');		
    require_once('../../Librerias/procedimientos/almacenados_matricula.php');	
	require_once('../../Librerias/procedimientos/almacenados_academico.php');	
/* Consulta de la cabecera del reporte */
$rs_institucion = consultas_tes(207, $Ses_Suc_Cod);
$row_rs_institucion= mysqli_fetch_assoc($rs_institucion);
$total_rs_institucion = mysqli_num_rows ($rs_institucion);
  /*Consulta de las facturas totales*/
  if ($escu == 'checkbox')
	{
		/* Carga el detalle de un periodo especifico */
		$rs_periodo = detalle_periodo($periodo);
		$row_rs_periodo = mysqli_fetch_assoc($rs_periodo);
		/*********************************************/
		if ($Car_Int != 'T')
		{
			unset($carrera_cod);
			$carrera_cod[]=$Car_Int;
		}
		else
		{
			unset($carrera_cod);
			$rs_carrera = carreras();
			$row_rs_carrera= mysqli_fetch_assoc($rs_carrera);
			do{
				$carrera_cod[] = $row_rs_carrera['Car_Int'] ;
			}while ($row_rs_carrera = mysqli_fetch_assoc($rs_carrera));
		}
	} 
   else  
	{
			unset($carrera_cod);
			$carrera_cod[]=0;
			/* Consultar los rubros */
		    $rs_buscarcarrera = consultas_tes(210, $ini.'*'.$fin.'*'.$option.'*'.'1');
			$row_rs_buscarcarrera = mysqli_fetch_assoc($rs_buscarcarrera);
			$total_rs_buscarcarrera = mysqli_num_rows($rs_buscarcarrera);
	}	
?>
<html>
<head>
<title>Ginus</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">	
<script type="text/javascript" src="../Librerias/validaciones/validacion.js"></script>
<body>
<table width="652"  height="232" border="0" align="center">
  <tr>
    <td width="646" height="78" valign="top">	  
	<table width="592" border="0" align="center" class="Titulos3">
      <tr align="center">
        <td width="106" rowspan="3" valign="top"><img src="../../imagenes/logo.jpg" width="105" height="65"></td>
        <td width="476" height="14"><?php echo $row_rs_institucion['Emp_Nom']; ?></td>
        </tr>
      <tr align="center">
        <td height="20">Reporte de Facturas <span class="Titulos3"><span class="Titulos3">
          <?Php
   if (($option) == "A")
   {
   ?>
        </span><?php echo 'Activas'; 
			} else {
			echo 'Anuladas';
			}
			?></span></td>
        </tr>		
      <tr align="center" valign="top">
        <td height="14" class="TITULO_REPORTE"> Desde el <span class="LetraNegra"><?php echo $ini; ?></span> hasta el <span class="LetraNegra"><?Php echo $fin; ?></span>&nbsp;<br><?Php  if ($escu == 'checkbox'){ echo "Periodo&nbsp;&nbsp;".$row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".$row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin']; } ?></td>
        </tr>
    </table>
   <br>
	<table width="0" border="0" align="center">
      <tr>
        <td><table width="540" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="91" class="Etiquetas"><div align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Desde:</div></td>
              <td width="153" class="LetraNegra"><?Php echo $ini?></td>
              <td width="56" class="Etiquetas"><div align="left">Hasta:</div></td>
              <td width="240" class="LetraNegra"><?Php echo $fin?></td>
            </tr>
          </table>
          <table width="520" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td class="Etiquetas"><div align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Facturas desde Nro.:&nbsp;&nbsp;&nbsp;<span class="LetraNegra"><?Php echo maxi_min_fac($ini, $fin, 'N', $option, 1); ?></span></div></td>
              <td class="LetraNegra">&nbsp;</td>
              <td colspan="2" class="Etiquetas"><div align="left">Hasta Nro:&nbsp;&nbsp;&nbsp;<span class="LetraNegra"><?Php echo maxi_min_fac($ini, $fin, 'M', $option, 1);  ?></span></div></td>
              </tr>
            <tr>
              <td width="255" class="Etiquetas"><div align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Facturas: <span class="LetraNegra">
                  <?php  if ($option == "A")
												   { 
												     echo 'Activas'; 
												   } else {
										 			 echo 'Anuladas';
												   } ?>
              </span></div></td>
              <td width="10" align="left" class="LetraNegra">&nbsp;</td>
              <td width="22" class="Etiquetas"><?Php if ($escu)
					  										 { echo "Periodo:"; }
															 else{
															  echo "&nbsp";
															 }?></td>
              <td width="215" class="LetraNegra">&nbsp;&nbsp;
                  <?Php if ($escu)
					  															{ 
					  															if ($op_opciones == "S")
												   								{ 
																				     echo 'Semestral'; 
																				   } else 
																				   {
																		 			 echo 'Pre-universitario';
																				} }?>              </td>
            </tr>
          </table>
          <?Php
$cont_todas=0;
for ($x=0; $x<=count($carrera_cod)-1; $x++)
{
if ($escu == 'checkbox')
{
/*Consulta de las facturas totales por rubros en base a la carrera y el periodo actual*/			
 $rs_buscarcarrera = consultas_tes(106, $ini.'*'.$fin.'*'.$carrera_cod[$x].'*'.$option.'*'.$op_opciones.'*'.$periodo.'*'.'1');
 $row_rs_buscarcarrera= mysqli_fetch_assoc($rs_buscarcarrera);
 echo $row_rs_buscarcarrera['Vet_Num']."Hola";
 $total_rs_buscarcarrera = mysqli_num_rows($rs_buscarcarrera);
	?>
		  <table width="395" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
              <td width="328" class="TITULO_REPORTE"><?php echo $row_rs_buscarcarrera['Car_Nom']; ?> </td>
            </tr>
          </table>
          <?php
	}//Fin del if ($Car_Int >0)
	if ($total_rs_buscarcarrera > 0)
	 {
	?>
		<br>
          <table  border="1" align="center" bordercolor="#000000">
            <tr>
              <td colspan="8" class="Encabezado_reporte" align="center"><div class="TITULO_REPORTE">detalle de facturas </div></td>
            </tr>
            <tr>
			<td width="63" class="TITULO_REPORTE">No INT.</td>
			  <td class="TITULO_REPORTE">No. Fact</td>
              <td width="63" class="TITULO_REPORTE">FECHA</td>
              <td width="300" class="TITULO_REPORTE">RUBROS</td>
			  <td width="300" class="TITULO_REPORTE">RUBROS</td>
			  <td width="300" class="TITULO_REPORTE">RUBROS</td>
              <td width="72" class="TITULO_REPORTE">TOTAL</td>
            </tr>
            <?php
		  $total_total=0;
		  do{
		  ?>
            <tr class="LetraNegra">
			<td align="center"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
			<td align="center"><?php echo $row_rs_buscarcarrera['Vet_Num']."HOLA"; ?></td>
              <td width="75"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
              <td><?Php echo $row_rs_buscarcarrera['Ite_Lar']; ?>&nbsp;</td>
              <td align="right"><?Php echo number_format($row_rs_buscarcarrera['Vet_Imp'],2); ?></td>
			  <td align="right"><?Php echo number_format($row_rs_buscarcarrera['Prs_Nom'],2); ?></td>
			  <td align="right"><?Php echo number_format($row_rs_buscarcarrera['Vet_Imp'],2); ?></td>
            </tr>
            <?Php
				 $total_total = $total_total + $row_rs_buscarcarrera['Vet_Imp'];
				 }while($row_rs_buscarcarrera = mysqli_fetch_assoc($rs_buscarcarrera))
				  ?>
          </table>
          <table width="463"  border="0" align="center" cellpadding="0" cellspacing="0">
            <tr class="LetraNegra">
            <td width="42">&nbsp;</td>
    	    <td width="42">&nbsp;</td>
		    <td width="25">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td width="25">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
		    <td width="25">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td width="42">&nbsp;</td>
            <td width="182" class="Etiquetas">Total:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td width="80" align="right"><?php echo number_format($total_total,2); ?></td>
            </tr>
          </table>
          <br>
		  <br>
          <?php // Consulta del detalle de la factura 
   		        $rs_detalle = consultas_tes(37, $row_rs_buscarcarrera['Vet_Cod']);
				$row_rs_detalle = mysqli_fetch_assoc($rs_detalle);
				$total_rs_detalle = mysqli_num_rows ($rs_detalle);	
				$tarifa_0 = 0;
	 			$tarifa_12 = 0;
				$subtotal=0;
				$iva=0;
				$des=0;
				$total=0;
				do{
						/* % de Descuento total */
						$Vet_Des = $row_rs_detalle['Vet_Des'];
						/* Calculo del total de la factura */
						$subtotal= $subtotal + $row_rs_detalle['Vet_Imp'];
						/* Calculo de las tarifas */
						if ($row_rs_detalle['Iva_Por'] == 0)
						{
							$tarifa_0 = $tarifa_0 + $row_rs_detalle['Vet_Imp'];
							/*Descuento individual */
							$des_0 = $des_0 + ($row_rs_detalle['Vet_Imp'] * $row_rs_detalle['Vet_Dec'])/100;
						}
						else
						{
							$tarifa_12 = $tarifa_12 + $row_rs_detalle['Vet_Imp'];
							/*Descuento individual */
							$des_12 = $des_12 + ($row_rs_detalle['Vet_Imp'] * $row_rs_detalle['Vet_Dec'])/100;			
							$iva_12 = $row_rs_detalle['Iva_Por'];
						}						
				} while($row_rs_detalle = mysqli_fetch_assoc($rs_detalle));
							/* Suma del descuento */
							$des = $des_0 + $des_12;
							/* calculo del iva con descuento individual */
							$iva = (($tarifa_12 - $des_12) * $iva_12)/100;
							/* Calculo del descuento total */
							if ($Vet_Des != 0)
							{
								$des = ($subtotal * $Vet_Des) / 100;
								$des_12 = ($tarifa_12 * $Vet_Des) / 100;
								$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
							}
								/*Calculo del total */
								$total = ($subtotal - $des) + $iva;
								/*******************************************/
								/*******************************************/
								/*******************************************/
								$total_subtotal= $total_subtotal + $subtotal;
								$total_tarifa_0= $total_tarifa_0 + $tarifa_0;
								$total_tarifa_12= $total_tarifa_12 + $tarifa_12;		
								$total_iva = $total_iva + $iva;
								$total_des = $total_des + $des;
								$total_total= $total_total + $total;
								/*******************************************/
								/*******************************************/
								/*******************************************/
								$des = 0;
								$des_0 = 0;
								$des_12 = 0;																																																																																										
	   } while ($row_rs_buscarcarrera = mysqli_fetch_assoc($rs_buscarcarrera));
	   ?></td>
      </tr>
    </table>
</table>
	  <?Php
	}//Fin del if ($total_rs_buscarcarrera > 0)
//Fin del for ($x=0; $x<=count($carrera_cod)-1; $x++)
?>
      </td>
      </tr>
      </table>
</body>
</html>
<?Php
?>