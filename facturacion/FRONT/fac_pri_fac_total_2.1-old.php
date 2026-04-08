<?	
/*
* Descripci�n: Reporte de la opci�n Totales, Detalle y Puntos de Impresi�n
* Fecha de actualizaci�n: 2012-05-26
* Desarrollador: Lewis Chimarro
* Fecha de actualizaci�n: 2013-03-22
* Desarrollador: Lewis Chimarro
* Descripcion: Se agrego 2 columnas, donde se muestra el descuento y el valor neto pagado
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 

/* 
* Desetea las variables cuando no tienen valor por no se las ha selecionado, utilizado por opcion 2 y 4 
*/		
if ($rubros == "")
{
	unset($rubros);
}
if ($rubros_cli == "")
{
	unset($rubros_cli);
}
if ($escu == "")
{
	unset($escu);
}
/*
* Consulta del vendedor en base al codigo de la persona
*/
$rs_vendedor = $obBD_con1->consulta(sentencias_tes(24, $obBD_con1->parametros($Ses_Prs_Cod.'*'.$Ses_Suc_Cod)), $obBD_conexion->conexion);
$row_rs_vendedor = $obBD_con1->registros();
$total_rs_vendedor = $obBD_con1->numregistros();	
$Pun_Cod = $row_rs_vendedor['Pun_Cod'];	

switch ($op){
	/* 
	* Impresi�n de reporte individual del reporte de la factura 
	*/
	case 2:	
		/* 
		* En la opcion 4 se busca las facturas de los puntos de impresion seleccionados
		*/
		//$puntos = "AND caja_aper.Pun_Cod=".$Pun_Cod;		
		$puntos = "AND puntos_imp.Suc_Cod = ".$Ses_Suc_Cod; // Trabaja con la sql 210
		$parametro=" Caj_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
		if($Tci!="")
		{
			$parametro=$parametro." AND persona.Prs_Ced='".$Tci."' ";	
		}
		/* 
		* Entra mientras no se seleccione BUSQUEDA AVANZADA 
		*/
		if (!(isset($escu)))
		{
			/* 
			* Solo FECHAS y AGRUPADAS 
			* Si esta seteado el check rubro entonces agrupa los valores por rubro OK 
			*/
			if (isset($rubros))
			{
				//$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(210, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),$obBD_conexion->conexion);
				$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(1237, $obBD_con1->parametros($parametro.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),$obBD_conexion->conexion);
				$row_rs_buscarcarrera = $obBD_con1->registros();
				$total_rs_buscarcarrera = $obBD_con1->numregistros();
			}//Fin del if (isset($rubros))
			else
			{
				if(isset($rubros_cli))
				{
					
					 /*Consultamos las ventas totales por cliente*/
					 $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(1257, $obBD_con1->parametros($parametro.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),$obBD_conexion->conexion);
					 $row_rs_buscarcarrera = $obBD_con1->registros();
					 $total_rs_buscarcarrera = $obBD_con1->numregistros();
				}else{					   
					/* 
					* solo FECHAS
					* Consulta de los totales de las facturas con detalle 
					*/
				   //$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(106, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),$obBD_conexion->conexion);
					$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(1238, $obBD_con1->parametros($parametro.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),$obBD_conexion->conexion);
				   $row_rs_buscarcarrera = $obBD_con1->registros();
				   $total_rs_buscarcarrera = $obBD_con1->numregistros();
				}
			}//Fin del else if (isset($rubros))		
		}//Fin del if (!(isset($escu)))
	break;
	
	case 3:	
		/* 
		* En la opcion 4 se busca las facturas de los puntos de impresion seleccionados
		*/
		//$puntos = "AND caja_aper.Pun_Cod=".$Pun_Cod;	
		$puntos = "AND puntos_imp.Suc_Cod=".$Ses_Suc_Cod;
		$parametro=" Caj_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
		if($Tci!="")
		{
			$parametro=$parametro." AND persona.Prs_Ced='".$Tci."' ";	
		}	
		if (isset($hdd))
		{	
			/* 
			* Consulta de los totales de las facturas
			*/
		   //$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(212, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), $obBD_conexion->conexion);
		    $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(1240, $obBD_con1->parametros($parametro.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), $obBD_conexion->conexion);
		   $row_rs_buscarcarrera = $obBD_con1->registros();
	  	   $total_rs_buscarcarrera = $obBD_con1->numregistros();	
		   
		 }//Fin del if (isset($hdd))
	break;

	case 4:
		/* 
		* En la opcion 4 se busca las facturas de los puntos de impresion seleccionados
		*/
		$puntos = "AND caja_aper.Pun_Cod=".$Pun_Cod4;	
		$parametro=" Caj_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
		
		if (!(isset($escu)))
		{
			/* 
			* Solo FECHAS y AGRUPADAS 
			* Si esta seteado el check rubro entonces agrupa los valores por rubro
			*/
			if (isset($rubros))
			{  
				$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(210, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),
											$obBD_conexion->conexion);
				$row_rs_buscarcarrera = $obBD_con1->registros();
				$total_rs_buscarcarrera = $obBD_con1->numregistros();
			}//Fin del if (isset($rubros))
			else
			{
				/* 
				* solo FECHAS
				* Consulta de los totales de las facturas con detalle
				*/
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(106, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
			   								$obBD_conexion->conexion);
			   $row_rs_buscarcarrera = $obBD_con1->registros();
	  	       $total_rs_buscarcarrera = $obBD_con1->numregistros();
			}//Fin del else if (isset($rubros))		
		}//Fin del if (!(isset($escu)))
	break;
}//FIn del case $op
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
     <table width="100%" border="0" align="center">
	 <tr align="center">
	  <td width="100%" valign="top" align="center">
      <?php
		   if (($optest) == "A")
		   {
				$estado = 'Activas'; 
		   } else 
		   {
				$estado = 'Anuladas';
		   }//Fin del if (($optest) == "A")
		$tip = $row_rs_cabcomp['Tia_Ini'];
		$num = $row_rs_cabcomp['Com_Num'];

		
		$titulo = "<strong><span class='TITULO_REPORTE_2'>Reporte de Ventas $estado</span></strong>";


		$subtitulo = "<strong><span class='TITULO_REPORTE'>Desde el ".$txt_fec_ini." Hasta el ".$txt_fec_fin." </span></strong>";
		 $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion); ?></td>
    </tr>	
    <tr>
        <td valign="top">
        <table width="290" border="0" cellpadding="0" cellspacing="0" align="center">
		<tr>
		  <td width="86" class="Texto_Reporte"><div align="center"><strong>Desde Nro.:</strong></div></td>
		  <td width="57" class="Texto_Reporte">&nbsp;<?Php echo $num_ini; ?></td>
		  <td width="83" class="Texto_Reporte"><div align="center"><strong>Hasta Nro.:</strong></div></td>
		  <td width="64" class="Texto_Reporte">&nbsp;<?Php echo $num_fin;  ?></td>
	    </tr>
		</table><br>
		<?Php
		if (isset($escu)){ 
		?>	
        <table width="412" border="0" align="center" cellpadding="0" cellspacing="0">
		  <tr>
			<td width="70" class="Texto_Reporte"><div align="right"><strong>Modalidad:</strong></div></td>
			<td width="167" class="Texto_Reporte">&nbsp;<?Php echo $Mod_Des; ?></td>
			<td width="46" class="Texto_Reporte"><div align="right"><strong>Etapa:</strong></div></td>
			<td width="129" class="Texto_Reporte">&nbsp;<?Php echo $Eta_Des; ?></td>
		  </tr>
		</table>
		<?php
		}//fin del if (isset($escu)) ?>	
<?Php
switch ($op){
	case 2:
if (isset ($hdd))
{ 
$boton_imp=false;
for ($x=0; $x<=count($carrera_cod)-1; $x++)
{
		/* 
		* Evalua si se encuentra seteada la opci�n de carrera 
		*/
		if (isset($escu))
		{ 
			if (isset($rubros))
			{
			   /* 
			   * Por FECHAS - AGRUPADO - BUSQUEDA AVANZADA
			   * Consulta de las facturas totales agrupados por rubros en base a la carrera de todos los puntos de impresion 
			   */
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(211, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$carrera_cod[$x].'*'.$puntos)),$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();					
			}//Fin del if (isset($rubros))
			else
			{
			   /* 
			   * Por FECHAS - BUSQUEDA AVANZADA
			   * Consulta de las facturas totales en base a la carrera y el periodo actual
			   */			   
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(110, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$carrera_cod[$x].'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();	
		   	}//Fin del else if (isset($rubros))

			/* 
			* Calcula el total de las facturas por carrera 
			*/
														/* Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion */	
			$resultados_total = explode('*',$obBD_con1->calculosVentasCarreras($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $carrera_cod[$x], $Pun_Cod, $obBD_conexion));										
			
 		   /* 
		   * Control para mostrar el mensaje de error cuando se selecciona todas las carreras 
		   */
		   if ($total_rs_buscarcarrera > 0)/* Control para mostrar el mensaje de error cuando se selecciona todas */
		   {
		   		$cont_todas++;
		   }
  			?>     			
			<table width="100%" border="0" align="center">
				 <tr>	 
				   <td class="Texto_Reporte" align="center"><strong><?php echo  $row_rs_buscarcarrera['Car_Nom']; ?></strong></td>
				</tr>
			</table>
<?php
		}//Fin del if (isset($escu))
		else
		{
		   /* 
		   * Calcula el total de las facturas 
		   */
			/* Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion */		
			//$resultados_total = explode('*',$obBD_con1->calculosVentas($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $Pun_Cod, $obBD_conexion));		
			$resultados_total = explode('*',$obBD_con1->calculosConsultaVentas($parametro, $optest, $Tic_Cod, $Ses_Suc_Cod, $obBD_conexion));
		}

		if ($total_rs_buscarcarrera != 0)
		{
			if (isset($rubros) || isset($rubros_cli))
			{
			?>
				<table width="100%" border="1" cellpadding="0" cellspacing="0">
				  <tr class="TablaRepCompr">
					<td width="10%" class="TablaRepCompr" align="center"><? if(isset($rubros)){ echo "Fecha";} if(isset($rubros_cli)){ echo "Cod. Int.";}?></td>
                                        <?php if(isset($rubros_cli)){ ?>
                                            <td width="10%" class="TablaRepCompr" align="center">C.I. / R.U.C.:</td>
                                        <?php }?>

					<td class="TablaRepCompr" align="center"><? if(isset($rubros)){ echo "Rubros";} if(isset($rubros_cli)){ echo "Clientes";}?></td>
					<td width="8%" class="TablaRepCompr" align="center">Total</td>
				  </tr>
				  <?php
				  do{
				  ?>
				  <tr class="Texto_Reporte">
					<td align="center"><?Php 
					if(isset($rubros))
					{
						echo $row_rs_buscarcarrera['Caj_Fec']; 
					}else{
						echo $row_rs_buscarcarrera['Cli_Cod']; 	
					}
					?>
                                        </td>
                                         <?php if(isset($rubros_cli)){ ?>
                                            <td align="center"><?php echo $row_rs_buscarcarrera['Prs_Ced'];  ?></td>
                                        <?php }?>
					<td><?Php 
					if(isset($rubros))
					{
						echo $row_rs_buscarcarrera['Ite_Lar']; 
					}else{
						echo $row_rs_buscarcarrera['Prs_Ape'].' '.$row_rs_buscarcarrera['Prs_Nom'];
					}					
					?></td>
					<td align="right"><?Php echo formato_numero($row_rs_buscarcarrera['Vet_Imp'] + $row_rs_buscarcarrera['Iva'],2,2); ?></td>
				  </tr>
				  <?Php
				  	//$total_total = $total_total + $row_rs_buscarcarrera['Vet_Imp'];					
				  }while($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>
			    </table>
			    <table width="100%"  border="0" cellpadding="0" cellspacing="0">
                  <tr class="Texto_Reporte">
                    <td width="81%">                
                    <td width="8%"><strong>Subtotal:
                    </strong>
                    <td width="11%" align="right"><strong><?php echo formato_numero($resultados_total[0],2,2); ?></strong></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Tarifa 0%:</strong></td>
                    <td align="right"><strong><?Php echo formato_numero($resultados_total[1],2,2); ?></strong></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Tarifa IVA %: </strong></td>
                    <td align="right"><strong><?Php echo formato_numero($resultados_total[2],2,2); ?></strong></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>% IVA:</strong></td>
                    <td align="right"><strong><?Php echo formato_numero($resultados_total[3],2,2); ?></strong></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Descuento:</strong></td>
                    <td align="right"><strong><?Php echo formato_numero($resultados_total[4],2,2); ?></strong></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Total:</strong></td>
                    <td align="right"><strong><?php echo formato_numero($resultados_total[5],2,2); ?></strong></td>
                  </tr>
                </table>
			    <br>
		    <?Php
			}//Fin del if (isset($rubros))
			else
			{			
			?>
				<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
				  <tr class="Texto_normal_10" >
				    <td  width="4%" align="center"><strong>C&oacute;d. Int.</strong></td>
			        <td  width="5%" align="center"><strong>No. Dcto.</strong></td>
				  	<td  width="6%" align="center"><strong>Fecha 
		  		    </strong>
				  	<td width="9%" align="center" ><strong>C&eacute;dula/R.U.C.</strong></td>
		  		    <td width="25%" align="center" ><strong>Cliente</strong></td>
		  		    <td width="10%"  align="center"><strong>Ret. Num.</strong></td>
		  		    <td width="6%"  align="center"><strong>Ret. Renta</strong></td>
		  		    <td width="6%"  align="center"><strong>Ret. Iva</strong></td>
		  			<td width="6%"  align="center"><strong>SubTotal</strong></td>
		  			<td width="6%"  align="center"><strong>Dscto.</strong></td>
		  			<td width="6%"  align="center"><strong>Iva </strong></td>
		  			<td width="6%"  align="center"><strong>Total</strong></td> 
		  		  </tr>
				    <?Php 
					/* 
					* Consulta el total de todas las facturas 
					*/	
					$total_imp = 0;
					$total_des = 0;
					$total_iva = 0;					
					$total_tot = 0;	
					$total_r_renta=0;
					$total_r_iva=0;								
					do { 
							$i++;
							
							/*Buscamos Renta/Iva de la venta*/
						    $row_retencion = $obBD_con1->getRowConsulta(1318, $row_rs_buscarcarrera['Vet_Cod'], $obBD_conexion);	
							
							/*  
							* Retorno los calculos de las facturas 
							*/
							$resultados = explode('*',$obBD_con1->calculos($row_rs_buscarcarrera['Vet_Cod'], $obBD_conexion));		
						?> 
					    	<tr class="Texto_normal_10">
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Num']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
							  <td><?PHP echo $row_rs_buscarcarrera['Prs_Ced']; ?></td>
							  <td style="white-space: nowrap; overflow: hidden;"><?PHP echo $row_rs_buscarcarrera['Prs_Ape'].' '.$row_rs_buscarcarrera['Prs_Nom']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Ret_Num']; ?></td>
							  <td align="right"><? echo formato_numero($row_retencion['r_renta'],2,3);?></td>
							  <td align="right"><? echo formato_numero($row_retencion['r_iva'],2,3);?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Tot'],2,2); ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Descuento'],2,2); ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Iva'],2,2); ?></td>
							  
							 
							  <!--td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Pag'],2,2); ?></td-->
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Pag']  ,2,2); ?></td>
		  					</tr>						
				   	<?Php 		
						/**
						* Calculo de totales
						*/
						$total_imp = $total_imp + round($row_rs_buscarcarrera['Vet_Tot'],2);
						$total_des = $total_des + ($row_rs_buscarcarrera['Descuento']);
						$total_iva = $total_iva + round($row_rs_buscarcarrera['Iva'],2);
						$total_tot = $total_tot + round($row_rs_buscarcarrera['Vet_Pag'],2);
						$total_r_renta=$total_r_renta + $row_retencion['r_renta'];
						$total_r_iva=$total_r_iva + $row_retencion['r_iva'];	
							
	   					} while ($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>   
				  <tr class="Texto_Reporte">
                      <td colspan="6" align="right"><strong>TOTALES:</strong></td>
                      <td align="right" class="Texto_Listados"><div align="right"><b><? echo formato_numero($total_r_renta,2,2);?></b></div></td>
                      <td align="right" class="Texto_Listados"><div align="right"><b><? echo formato_numero($total_r_iva,2,2);?></b></div></td>
                      <td align="right" class="Texto_Listados"><div align="right"><b><?Php echo formato_numero($total_imp,2,2);?></b></div></td>
                      <td align="right" class="Texto_Listados"><div align="right"><b><?Php echo formato_numero($total_des,2,2);?></b></div></td>
                      <td align="right" class="Texto_Listados"><div align="right"><b><?Php echo formato_numero($total_iva,2,2);?></b></div></td>
                      <td align="right" class="Texto_Listados"><div align="right"><b><?Php echo formato_numero($total_tot,2,2);?></b></div></td>
	    	      </tr>	                        
		  		</table>
				<table width="100%"  border="0" cellpadding="0" cellspacing="0">
                  <tr class="Texto_Reporte">
                    <td width="81%">                
                    <td width="8%" class="Texto_Listados"><strong>Subtotal:
                    </strong>
                    <td width="11%" align="right" class="Texto_normal_10"><div align="right"><strong><?php echo formato_numero($resultados_total[0],2,2); ?></strong></div></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td class="Texto_Listados"><strong>Tarifa 0%:</strong></td>
                    <td align="right" class="Texto_normal_10"><div align="right"><strong><?Php echo formato_numero($resultados_total[1],2,2); ?></strong></div></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td class="Texto_Listados"><strong>Tarifa IVA%: </strong></td>
                    <td align="right" class="Texto_normal_10"><div align="right"><strong><?Php echo formato_numero($resultados_total[2],2,2); ?></strong></div></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td class="Texto_Listados"><strong>% IVA:</strong></td>
                    <td align="right" class="Texto_normal_10"><div align="right"><strong><?Php echo formato_numero($resultados_total[3],2,2); ?></strong></div></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td class="Texto_Listados"><strong>Descuento:</strong></td>
                    <td align="right" class="Texto_normal_10"><div align="right"><strong><?Php echo formato_numero($resultados_total[4],2,2); ?></strong></div></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td class="Texto_Listados"><strong>Total:</strong></td>
                    <td align="right" class="Texto_normal_10"><div align="right"><strong><?php echo formato_numero($resultados_total[5],2,2); ?></strong></div></td>
                  </tr>
</table>
				<?php
		 	}//Fin del else if (isset($rubros))				
		 	/* 
			* Control para saber si se muestra o no el boton 
			*/
			$boton_imp = true;
  		}//Fin del if ($total_rs_buscarcarrera != 0)
  		else
  		{
			/* 
			* Muestra este mensaje en todos los casos excepto cuando selecciona la carrera 
			*/
			if (!(isset($escu)))
			{
				echo error_alerta("No hay resultados que mostrar", 2);
			}//Fin del if (!(isset($escu)))
  		}		
} //Fin del for ($x=0; $x<=count($carreras_cod)-1; $x++)

	/* 
	* Control para mostrar el mensaje de error cuando se selecciona todas las carreras 
	*/
	if (isset($escu) && $boton_imp == false)
	{
			echo error_alerta("No hay resultados que mostrar", 2);
	}//Fin del if (isset($escu) && $boton_imp == false)		
}//Fin del if (isset ($hdd))
break; //Fin del case 2	
	
case 3:
if (isset($hdd))
{
	if ($total_rs_buscarcarrera != 0)
	{		
		?>
    <style>.hide{display:none;} .cortarString{}</style>
			<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
			  <tr class="TablaRepCompr">
				<td class="TablaRepCompr hide" align="center" width="8%">C&oacute;d. Int.</td>
				<td class="TablaRepCompr" align="center" width="8%">No. Doc.</td>
				<td width="12%" class="TablaRepCompr" align="center">Fecha</td> 
				<td width="15%" class="TablaRepCompr" align="center">C.I./R.U.C.</td> 
				<td width="26%"class="TablaRepCompr" align="center">Cliente</td>
				<td class="TablaRepCompr" align="center">Detalle</td>
				<td width="12%" class="TablaRepCompr" align="center">Total</td> 
			  </tr>
				<?Php 
				/* 
				* Consulta el total de todas las facturas 
				*/
				//$resultados_total = explode('*',$obBD_con1->calculosVentas($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $Pun_Cod, $obBD_conexion));			
				$resultados_total = explode('*',$obBD_con1->calculosConsultaVentas($parametro, $optest, $Tic_Cod, $Ses_Suc_Cod, $obBD_conexion));
				do { 																
							$i++; 			
							/*  
							* Retorno los calculos de las facturas 
							*/
							$resultados = explode('*',$obBD_con1->calculos($row_rs_buscarcarrera['Vet_Cod'], $obBD_conexion));							
							/* 
							* Consulta del semestre y la carrera del estudiante 
							*/
							$rs_semestre =  $obBD_con1->consulta(sentencias_tes(174, $obBD_con1->parametros($row_rs_buscarcarrera['Nge_Cod'])), 
							$obBD_conexion->conexion);
							$row_rs_semestre = $obBD_con1->registros();
							$total_rs_semestre = $obBD_con1->numregistros();										
							/*
							* Consulta del detalle de la factura 
							*/
					?> 
						<tr class="Texto_Reporte">
						  <td align="center" valign="top" class="hide"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
						  <td align="center" valign="top"><?php echo $row_rs_buscarcarrera['Vet_Num']; ?></td>
						  <td valign="top" align="center"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
						  <td valign="top" align="center"><?php echo $row_rs_buscarcarrera['Prs_Ced']; ?></td>
						  <td valign="top" class="cortarString"><?PHP echo $row_rs_buscarcarrera['Prs_Ape']." ".$row_rs_buscarcarrera['Prs_Nom']; ?> &nbsp;</td>
						  <td valign="top" align="left">							 
				<?Php 
							/* 
							* Consulta del detalle de la factura 
							*/
							$rs_detalle = $obBD_con1->consulta(sentencias_tes(37, $obBD_con1->parametros($row_rs_buscarcarrera['Vet_Cod'])), $obBD_conexion->conexion);
							$row_rs_detalle =  $obBD_con1->registros();
							$total_rs_detalle =  $obBD_con1->numregistros();
							
							do{
							/* 
							* Consulta los rubro del intereses
							*/
						  $rs_interes = $obBD_con1->consulta(sentencias_tes(74, $obBD_con1->parametros(
						  $row_rs_detalle['Vet_Cod'].'*'.
								$row_rs_detalle['Nge_Cod'].'*'.$row_rs_detalle['Asi_Int'].'*'.
								$row_rs_detalle['Pro_Cod'])), 
						  $obBD_conexion->conexion);
						  $row_rs_interes = $obBD_con1->registros();
						  $total_rs_interes = $obBD_con1->numregistros();								
							
								echo "&#8226; ".$row_rs_detalle['Ite_Cor'].
													"[".formato_numero($row_rs_detalle['Vet_Imp'],2,2)."]<br>"; 
													
								if ($total_rs_interes > 0)
								{
									do{ //Inicio del }while($row_rs_interes = mysqli_fetch_assoc($rs_interes));
									echo "&#8226; ".$row_rs_interes['Ite_Cor'].
												"[".formato_numero($row_rs_interes['Vet_Imp'],2,2)."]<br>"; 
									}while($row_rs_interes = $obBD_con1->fetch_assoc($rs_interes));
								 }//Fin del if ($total_rs_interes > 0)									
							} while($row_rs_detalle = $obBD_con1->fetch_assoc($rs_detalle));
							?>
														  </td>
				 <td align="right" valign="top"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Imp'],2,2); ?></td>
						  <?php
					} while ($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera ));
				?>						
				</tr>	 
			</table>
		
			<table width="100%"  border="0" cellpadding="0" cellspacing="0">
				<tr class="Texto_Reporte">
				  <td width="65%">   </td>   
				  <td><strong>Subtotal:</strong></td>
				  
				  <td width="12%"><div align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Tarifa 0%:</strong></td>	  
				  <td><div align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Tarifa diferente a 0%: </strong></td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>IVA:</strong></td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></div></td>
				</tr>	
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Descuento:</strong></td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></div></td>
				</tr>
				<tr class="Texto_Reporte">
				  <td>&nbsp;</td>
				  <td><strong>Total:</strong></td>
				  <td><div align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></div></td>
				</tr>
	  </table>
	 <?php			
	}//Fin del if ($total_rs_buscarcarrera != 0)
	else
	{
			echo error_alerta("No hay resultados que mostrar", 2);
	}//Fin del else if ($total_rs_buscarcarrera != 0)
}//Fin del if (isset($hdd))
break; //Fin del case 3
	
case 4:		
if (isset ($hdd))
{
	/*
	* Consulta del vendedor en base al codigo del punto de impresion
	*/
	$rs_punto = $obBD_con1->consulta(sentencias_tes(631, $obBD_con1->parametros($Pun_Cod)),$obBD_conexion->conexion);
	$row_rs_punto = $obBD_con1->registros();
	$total_rs_punto = $obBD_con1->numregistros();
?>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="19%" class="Texto_Reporte"><div align="right"><strong>Punto de impresi&oacute;n:</strong> </div></td>
		  <td colspan="3" class="Texto_Reporte">&nbsp;<span class="Texto_Reporte"><?Php echo $row_rs_punto['Pun_Des']; ?></span></td>
	    </tr>
		<tr>
		  <td class="Texto_Reporte"><div align="right"><strong>Estado de documentos:</strong></div></td>
		  <td width="14%" align="left" class="Texto_Reporte">
		    &nbsp;
		    <?php  if ($optest == "A"){ echo 'Activas'; } 
							else { echo 'Anuladas'; } ?>			</td>					  
		  <td width="5%" class="Texto_Reporte">&nbsp;</td>
		  <td width="62%" class="Texto_Reporte">&nbsp;</td>
	    </tr>
	</table>
		<?Php
		if (isset($escu)){ 
	  		/* 
			* Consulta la descripci�n de la etapa 
			*/
			$rs_etapa = $obBD_con1->consulta(sentencias_tes(176, $obBD_con1->parametros($Eta_Cod)), 
										$obBD_conexion->conexion);	
			$row_rs_etapa = $obBD_con1->registros();	
			/* 
			* Datos de la modaldidad 
			*/
			$rs_modalidad = $obBD_con1->consulta(sentencias_tes(172, $obBD_con1->parametros($Mod_Cod)), 
					$obBD_conexion->conexion);
			$row_rs_modalidad = $obBD_con1->registros();							
		?>	
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
		  <tr>
			<td width="19%" class="Texto_Reporte"><div align="right"><strong>Modalidad:</strong></div></td>
			<td width="14%" class="Texto_Reporte">&nbsp;<?Php echo $row_rs_modalidad['Mod_Des'] ?></td>
			<td width="5%" class="Texto_Reporte"><div align="right"><strong>Etapa:</strong></div></td>
			<td width="62%" class="Texto_Reporte">&nbsp;<?Php echo $row_rs_etapa['Eta_Des'] ?></td>
		  </tr>
		</table>
		<?php
		}//fin del if (isset($escu)) ?>

	<br> 	
<?Php
$boton_imp=false;
for ($x=0; $x<=count($carrera_cod)-1; $x++)
{
		/* Evalua si se encuentra seteada la opci�n de carrera */
		if (isset($escu))
		{ 
			if (isset($rubros))
			{
			   /* Por FECHAS - AGRUPADO - BUSQUEDA AVANZADA
			   Consulta de las facturas totales agrupados por rubros en base a la carrera de todos los puntos de impresion */
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(211, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$carrera_cod[$x].'*'.$puntos)), 
			   								$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();					
			}//Fin del if (isset($rubros))
			else
			{
			   /* Por FECHAS - BUSQUEDA AVANZADA
			   Consulta de las facturas totales en base a la carrera y el periodo actual*/			   
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(110, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$carrera_cod[$x].'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
			   							$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();	
		   	}//Fin del else if (isset($rubros))

			/* Calcula el total de las facturas por carrera */
														/* Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion */	
			$resultados_total = explode('*',$obBD_con1->calculosVentasCarreras($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $carrera_cod[$x], $Pun_Cod4, $obBD_conexion));										
			?>     			
			<table width="100%" border="0" align="center">
				 <tr>	 
				   <td class="Texto_Reporte" align="center"><strong><?php echo  $row_rs_buscarcarrera['Car_Nom']; ?></strong></td>
				</tr>
			</table>
<?php
		}//Fin del if (isset($escu))
		else
		{
		   /* Calcula el total de las facturas */
							/* Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion */		
			//$resultados_total = explode('*',$obBD_con1->calculosVentas($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $Pun_Cod4, $obBD_conexion));		
			$resultados_total = explode('*',$obBD_con1->calculosConsultaVentas($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $Ses_Suc_Cod, $obBD_conexion));
		}

		if ($total_rs_buscarcarrera != 0)
		{
			if (isset($rubros))
			{
			?>
				<table width="100%" border="1" cellpadding="0" cellspacing="0">
				  <tr class="TablaRepCompr">
					<td width="10%" class="TablaRepCompr" align="center">Fecha</td>
					<td class="TablaRepCompr" align="center">Rubros</td>
					<td width="8%" class="TablaRepCompr" align="center">Total</td>
				  </tr>
				  <?php
				  do{
				  ?>
				  <tr class="Texto_Reporte">
					<td align="center"><?Php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
					<td><?Php echo $row_rs_buscarcarrera['Ite_Lar']; ?></td>
					<td align="right"><?Php echo formato_numero($row_rs_buscarcarrera['Vet_Imp'] + $row_rs_buscarcarrera['Iva'],2,2); ?></td>
				  </tr>
				  <?Php
				  }while($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>
			    </table>
			    <table width="100%"  border="0" cellpadding="0" cellspacing="0">
                  <tr class="Texto_Reporte">
                    <td width="81%">                
                    <td width="8%"><strong>Subtotal:
                    </strong>
                    <td width="11%" align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Tarifa 0%:</strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td><strong>Tarifa 12%: </strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>12% IVA:</strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Descuento:</strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Total:</strong></td>
                    <td align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></td>
                  </tr>
                </table>
			    <br>
		    <?Php
			}//Fin del if (isset($rubros))
			else
			{			
			?>
				<table width="100%" border="1" cellpadding="0" cellspacing="0">
				  <tr class="TablaRepCompr">
				    <td class="TablaRepCompr" width="4%" align="center">C&oacute;d. Int.</td>
			        <td class="TablaRepCompr" width="6%" align="center">No. Documento</td>
				  	<td class="TablaRepCompr" width="10%" align="center">Fecha 
		  		    <td class="TablaRepCompr" align="center">Cliente</td>
		  			<td width="8%" class="TablaRepCompr" align="center">Total</td> 
		  		  </tr>
				    <?Php 
					do { 
								$i++;
								/*  
								* Retorno los calculos de las facturas 
								*/
								$resultados = explode('*',$obBD_con1->calculos($row_rs_buscarcarrera['Vet_Cod'], $obBD_conexion));								
						?> 
					    	<tr class="Texto_Reporte">
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Num']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
							  <td><?PHP echo $row_rs_buscarcarrera['Prs_Ape'].' '.$row_rs_buscarcarrera['Prs_Nom']; ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Tot'],2,2); ?></td>
		  					</tr>								
				   	<?Php 				
	   					} while ($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>   
		  		</table>
				<table width="100%"  border="0" cellpadding="0" cellspacing="0">
                  <tr class="Texto_Reporte">
                    <td width="81%">                
                    <td width="8%"><strong>Subtotal:
                    </strong>
                    <td width="11%" align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Tarifa 0%:</strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Tarifa 12%: </strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>12% IVA:</strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Descuento:</strong></td>
                    <td align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></td>
                  </tr>
                  <tr class="Texto_Reporte">
                    <td>&nbsp;</td>
                    <td><strong>Total:</strong></td>
                    <td align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></td>
                  </tr>
                </table>
				<?php
		 	}//Fin del else if (isset($rubros))	
		 	/* Control para saber si se muestra o no el boton */
			$boton_imp = true;					
  		}//Fin del if ($total_rs_buscarcarrera != 0)
  		else
  		{
			/* Muestra este mensaje en todos los casos excepto cuando selecciona la carrera */
			if (!(isset($escu)))
			{
				echo error_alerta("No hay resultados que mostrar", 2);
			}//Fin del if (!(isset($escu)))
  		}		
} //Fin del for ($x=0; $x<=count($carreras_cod)-1; $x++)

	/* Control para mostrar el mensaje de error cuando se selecciona todas las carreras */
	if (isset($escu) && $boton_imp == false)
	{
			echo error_alerta("No hay resultados que mostrar", 2);
	}//Fin del if (isset($escu) && $boton_imp == false)		
}//Fin del if (isset ($hdd))
break; //Fin del case 4

}//Fin del case $op
	?>
  </td>
  </tr>
    <tr>
      <td align="center"><div align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></div></td>
    </tr>
</table>	  
</BODY></HTML>
<?php 
@$obBD_con1->free_result($rs_buscar);
@$obBD_con1->free_result($rs_cliente);
@$obBD_con1->free_result($rs_ciudad);
@$obBD_con1->free_result($rs_buscarcarrera);
@$obBD_con1->free_result($rs_carrera);
@$obBD_con1->free_result($rs_detalle);
@$obBD_con1->free_result($rs_semestre);
@$obBD_con1->free_result($rs_vendedor);
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>