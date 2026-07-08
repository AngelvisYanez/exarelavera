<?php	
/*Alias:	Reporte de kardex
Descripción: Reporte de kardex listo para la impreison
Fecha de actualización:	2013-11-07
Desarrollador:	El Didi
<<<  Ite_Cod es equivalente a Pro_Cod  >>>
MULTIEMPRESA : 
*/	

require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/tes_log_kardex.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');



/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Kar($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Kar;
/***********************************************/
//$rs_consulta = $obBD_con1->consulta(sentencias_tes(1010, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
$rs_consulta = $obBD_con1->getRowConsulta(1010,$codigo, $obBD_conexion);
$row_rs_consulta =$rs_consulta;
$total_rs_consulta =  count($rs_consulta);

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
	
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>

<?php 	  
/**
 *   Variables para Encabezado
 */
 $Titulo="KARDEX";
 $Subtitulo="";	
 ?>
 <br>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
  	<tr align="center">
    	<td width="100%">
           <table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr align="center">
                  <td colspan="4">&nbsp; <?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod,$Titulo,$Subtitulo,$obBD_conexion)?></td>
                </tr>
           </table>  
      </td>
  </tr>
</table>	


<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr align="center" >
	   <td>&nbsp;</td>
 </tr>
 <tr>
        <td valign="top">
   

  <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td ><table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td><table width="100%" border="0" cellpadding="0" cellspacing="0" >
            <tr>
              <td width="12%"  class="Texto_Reporte">&nbsp;<strong>Categoria: </strong></td>
              <td width="24%" class="Texto_Reporte" >&nbsp;<?php echo  $row_rs_consulta['Cat_Des'] ?></td>
              <td width="13%"  class="Texto_Reporte">&nbsp;<strong>Descripci&oacute;n Larga:</strong></td>
              <td width="23%" class="Texto_Reporte" >&nbsp;<?php echo  $row_rs_consulta['Ite_Lar'] ?></td>
              <td width="8%" class="Texto_Reporte" >&nbsp;<strong>Iva: </strong></td>
              <td width="20%" class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Iva_Por'] ?></td>
              </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>C&oacute;d. Categoria:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Pro_Cdc'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Marca:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Mar_Des'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Cod.Barra: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Pro_Bar'] ?></td>
              </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Observaci&oacute;n: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Pro_Obs'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Adquisici&oacute;n: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Adq_Des'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Ubicacion: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Ubi_Des'] ?></td>
              </tr>
            
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Descripci&oacute;n  Corta:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Ite_Cor'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Unidad:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Uni_Des'] ?></td>
              <td class="Texto_Reporte"><div align="right"></div></td>
              <td class="Texto_Reporte">&nbsp;</td>
              </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Desde:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $fech_ini; ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Hasta:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $fech_fin; ?> </td>
              <td class="Texto_Reporte">&nbsp;</td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
          </table></td>
        </tr>
        <tr>
          <td >
		  <?php
			$rs_consulta = $obBD_con1->getArrayConsulta(1042,$fech_ini.'*'.$fech_fin.'*'.$codigo, $obBD_conexion);
			
			//$rs_consulta = $obBD_con1->consulta(sentencias_tes(1042, $obBD_con1->parametros($fech_ini.'*'.$fech_fin.'*'.$codigo)), $obBD_conexion->conexion);
			
			//$row_rs_consulta = $rs_consulta;
			$total_rs_consulta = count($rs_consulta);
	
	
			//$rs_stock = $obBD_con1->consulta(sentencias_tes(1043, $obBD_con1->parametros($fech_ini.'*'.$codigo)), $obBD_conexion->conexion);
			$rs_stock = $obBD_con1->getArrayConsulta(1043,$fech_ini.'*'.$codigo, $obBD_conexion);
			$row_rs_stock = $rs_stock;
			$total_rs_stock =  count($rs_stock);
			$Stock_actual=$row_rs_stock['Stock'];
	
			//$rs_saldo = $obBD_con1->consulta(sentencias_tes(1047, $obBD_con1->parametros($fech_ini.'*'.$codigo)), $obBD_conexion->conexion);
			$rs_saldo1 = $obBD_con1->getArrayConsulta(1047,$fech_ini.'*'.$codigo, $obBD_conexion);
			$row_rs_saldo1 = $rs_saldo1;

			
			$Saldo_actual=$row_rs_saldo1['Saldo'];
			$res_saldo=$row_rs_saldo1['Saldo'] ;
			
			$rs_saldo = $obBD_con1->getArrayConsulta(1047,$fech_ini.'*'.$codigo, $obBD_conexion);
			$total_rs_saldo = count($rs_saldo); 	
	
	//do{
		foreach($rs_saldo as $row_rs_saldo){
			$Saldo_actual=$Saldo_actual+$row_rs_saldo['Saldo'];
	 }//while ($row_rs_saldo = $obBD_con1->fetch_assoc($row_rs_saldo)) ?>
     
		 
		  <table width="100%" border="1" cellpadding="0" style="border-collapse:collapse" cellspacing="0">
		    <tr class="Texto_Listados" bgcolor="#CCCCCC">
		      <td height="21" class="" ><div align="center"><strong>Fecha</strong></div></td>
		      <td colspan="2"><div align="center"><strong>Documento</strong></div></td>
		      <td colspan="2"><div align="center"><strong>Parametro del movimiento</strong></div></td>
		      <td width="6%" ><div align="center"><strong>Entrada</strong></div></td>
		      <td width="6%" ><div align="center"><strong>Salida</strong></div></td>
		      <td width="6%" ><div align="center"><strong>Ingreso</strong></div></td>
		      <td width="6%" ><div align="center"><strong>Egreso</strong></div></td>
		      <td width="6%" ><div align="center"><strong>Saldos</strong></div></td>
		      <td width="6%" ><div align="center"><strong>Stock</strong></div></td>
		      </tr>
		    <tr class="Texto_Listados" >
		      <td align="center" ><strong>dd/mm/aaaa</strong></td>
		      <td  ><strong>Comp.</strong></td>
		      <td ><div align="center"><strong>Numero</strong></div></td>
		      <td  ><div align="center"><strong>Actividad</strong></div></td>
		      <td  ><div align="center"><strong>Concepto</strong></div></td>
		      <td>&nbsp;</td>
		      <td >&nbsp;</td>
		      <td>&nbsp;</td>
		      <td >&nbsp;</td>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      </tr>
		    <tr class="Texto_Listados">
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      <td colspan="2" align="center">Saldo al :<span class="LetraNegra">
		        <?php list($ann, $mes, $dia) = preg_split('![/.-]!',$fech_ini); echo $dia.', de '.mes($mes, 1).', '.$ann; ?>
		        </span></td>
		      <td><div align="right">&nbsp;</div></td>
		      <td><div align="right">&nbsp;</div></td>
		      <td><div align="right">&nbsp;</div></td>
		      <td><div align="right">&nbsp;</div></td>
		      <td><div align="right"><?php echo "$ ".formato_numero($Saldo_actual,2,1); ?></div></td>
		      <td><div align="right">
		        <?php if($row_rs_stock['Stock']==''){ echo 0;}else{echo $row_rs_stock['Stock'];}?>
		       </div></td>
		      </tr>
		    <?php 
			
			$Precio_Compra=0;
			$Tota_Salida=0;
			
			//do{ 
			foreach($rs_consulta as $row_rs_consulta){					
					$numero='';
					$documento='';
					$observacion='';
				if($row_rs_consulta['Vet_Cod']<>0)
					{	$Tipo_Comprobante='Venta';
						/* Seleccion el movimiento del producto en ventas */
						//$rs_docum = $obBD_con1->consulta(sentencias_tes(1044, $obBD_con1->parametros($row_rs_consulta['Vet_Cod'])), $obBD_conexion->conexion);
						
						$rs_docum = $obBD_con1->getRowConsulta(1044,$row_rs_consulta['Vet_Cod'], $obBD_conexion);
    					$row_rs_docum = $rs_docum;
						
						$total_rs_docum =  count($rs_docum);
						
						$numero=$row_rs_docum['Vet_Num'];
						$documento=$row_rs_docum['Tic_Des'];
						$observacion=$row_rs_docum['Vet_Obs'];
					}
				if($row_rs_consulta['Cop_Cod']<>0)
					{	$Tipo_Comprobante='Compra';
						/* Seleccion el movimiento del producto en compra */
						//$rs_docum = $obBD_con1->consulta(sentencias_tes(1045, $obBD_con1->parametros($row_rs_consulta['Cop_Cod'])), $obBD_conexion->conexion);
						
						$rs_docum = $obBD_con1->getRowConsulta(1045, $row_rs_consulta['Cop_Cod'],$obBD_conexion);
    					$row_rs_docum = $rs_docum;
						$total_rs_docum =  count($rs_docum);
						
						$numero=$row_rs_docum['Cop_Num'];
						$documento=$row_rs_docum['Cop_Des'];
						$observacion=$row_rs_docum['Cop_Obs'];
					}
					if($row_rs_consulta['Aju_Cod']<>0)
					{
						$Tipo_Comprobante='Ajuste';
						/* Seleccion el movimiento del producto en ajustes */	
						//$rs_docum = $obBD_con1->consulta(sentencias_tes(1046, $obBD_con1->parametros($row_rs_consulta['Aju_Cod'])), $obBD_conexion->conexion);			
						$rs_docum = $obBD_con1->getRowConsulta(1046,$row_rs_consulta['Aju_Cod'], $obBD_conexion);
    					$row_rs_docum = $rs_docum;
						$total_rs_docum =  count($rs_docum);
						
						$numero=$row_rs_docum['Aju_Cod'];
						$documento='Ajuste';
						$observacion=$row_rs_docum['Aju_Obs'];
					}?>
		    <tr class="Texto_normal_10">
		      <td width="9%" class="LetraNegra" align="center"><?php echo $row_rs_consulta['Kar_Fec']; ?></td>
		      <td width="7%" class="LetraNegra"><?php echo $documento; ?></td>
		      <td width="7%" class="LetraNegra"><?php echo $numero; ?></td>
		      <td width="7%" class="LetraNegra"><?php echo $Tipo_Comprobante; ?></td>
		      <td width="34%" class="LetraNegra"><?php echo $observacion; ?></td>
		      <td class="LetraNegra"><div align="right"><?php echo $row_rs_consulta['Kar_Can']; ?></div></td>
		      <td class="LetraNegra"><div align="right"><?php echo $row_rs_consulta['Kar_Sal']; 
			  										$Tota_Salida=$Tota_Salida+$row_rs_consulta['Kar_Sal'];
			  ?></div></td>
		      <td class="LetraNegra"><div align="right" ><?php echo  "$ ".formato_numero($row_rs_consulta['Precio_ent'],2,1); ?></div></td>
		      <td class="LetraNegra"><div align="right"><?php echo "$ ".formato_numero($row_rs_consulta['Precio_sal'],2,1); 
			  		$Precio_Compra=$Precio_Compra+$row_rs_consulta['Precio_sal'];
			  ?></div></td>
		      <td class="LetraNegra"><div align="right"><?php echo "$ ".formato_numero($Saldo_actual=$Saldo_actual+$row_rs_consulta['Saldo'],2,1); ?></div></td>
		      <td class="LetraNegra"><div align="right"><?php echo $Stock_actual=$Stock_actual+$row_rs_consulta['Stock']; ?></div></td>
		      </tr>
		    <?php  }  //while ($row_rs_consulta = $obBD_con1->fetch_assoc($rs_consulta)) ?>
		    </table></td>
        </tr>
        <tr>
          <td><table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
            <tr>
                <td width="20%" class="Texto_Listados">Stock actual:
                    <strong><?php	if($Stock_actual<>0){ echo  $Stock_actual;}else{echo '0';}	?> </strong>
                 </td>
                <td width="20%" class="Texto_Listados">Saldo actual:
                  <strong><?php if($Saldo_actual<>0){echo "$ ".formato_numero($Saldo_actual,2,1) ;	}else{echo '0';} ?></strong>
                </td>
                <td width="60%" class="Encabezado_reporte"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="30%" class="Texto_Listados">Pro. Ponderado: 
                      <strong><?php echo $Precio_Compra.' / '.$Tota_Salida.' = ';		
					if( $Tota_Salida==0){echo 0;}else{echo formato_numero(($Precio_Compra/$Tota_Salida),2,1); }	?></strong></td>
                    <td width="30%"  class="Texto_Listados">Articulo  final :
                      <strong>
					  <?php if($Stock_actual<>0){echo  $Stock_actual.'   ' ;	}else {	echo '0'.'   ';}?>
                      x
                      <?php if ($Tota_Salida==0){echo 0; }else{echo '  '.formato_numero(($Precio_Compra/$Tota_Salida),2,1).' ' ;}
?>
=
<?php if ($Tota_Salida==0){echo 0;}else{echo formato_numero(($Precio_Compra/$Tota_Salida)* $Stock_actual,2,1).' '; }
		?>
        			</strong>
        			</td>
                  </tr>
                </table>                  </td>
              </tr>
          </table>
            <p></td>
        </tr>
      </table></td>
    </tr>
  </table>
</td>
  </tr>
</table>	  
</BODY></HTML>
<?Php 

/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();

?>