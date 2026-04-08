<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php 
   /**
	* pagina para generar Resumen Anexo Transaccional
	*
	* @author Jose Cumbicos
	* Ultima Actualización: 28-05-2016
	*
	* @package tesoreria
	*/
	
require_once('../../Librerias/config.php/register_globals.php'); 
require_once($APP_REAL_PATH.'/administrador/LOGICA/logica.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/** 
 * objeto conexion
 */
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);

/**
 * objeto para extraer datos
 */
$obBD_con1 =  new Class_Log_Datos_Anx;

/**
*   Variables para Encabezado
*/
$Titulo="TALON DE RESUMEN";
$fechaAnexo=explode('-',$ini);
$Subtitulo="ANEXO TRANSACCIONAL ".mes($fechaAnexo[1],1).' '.$fechaAnexo[0];
$row_rs_PorIva = $obBD_con1->getRowConsulta(876, $ini, $obBD_conexion);

?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<style type="text/css">
            .totales{font-size: 10px;font-weight: normal;font: 8pt verdana;}
</style>
<body class="Cuerpo">
<? /* Consulta de la cabecera del reporte */
	$row_institucion= $obBD_con1->getRowConsulta(5, $Ses_Suc_Cod, $obBD_conexion);//GetRowConsulta(5,$Ses_Cod_Suc);
	/* Consulta de la cabecera del reporte */
	$row_institucion = $obBD_con1->getRowConsulta(22, $Ses_Suc_Cod, $obBD_conexion);
	/* Consulta la provicia y pais de la sucursal */
	$row_provincia = $obBD_con1->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD_conexion);
?>
<table width="100%" height="907" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td height="58" valign="top">
    
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
			  <tr align="center">
			    <td width="10%" rowspan="5" valign="top"><img src="../../mascaras/model2/imagenes/32x32/sri.png" width="94" height="61" /></td>
			    <td width="80%" height="24" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
			    <td width="10%" rowspan="5" valign="top" class="TITULO_REPORTE_2"><img src="<? echo $row_institucion['Emp_Log']?>" width="94" height="61" /></td>
			  </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php 
				if (count($row_provincia) > 0)
				{
					$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
				}
				else
				{
					$provincia = "";					
				}
				echo $row_institucion['Ciu_Des'].$provincia;?></div></td>
		      </tr>
			  <tr align="center">
			    <td colspan="3" valign="top"><hr /></td>
	  		  </tr>
			  <tr align="center">
			    <td valign="top" class="TITULO_REPORTE">&nbsp;</td>
			    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $Titulo; ?></td>
	  		  </tr>
			  <tr align="center">
			    <td valign="top" class="TITULO_REPORTE">&nbsp;</td>
			    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $Subtitulo; ?></td>
		      </tr>
		    </table>
    
    </td>
  </tr>
  <tr valign="top">
    <td valign="top">
	<br>
    <table width="100%" height="501"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td height="29"><div align="center">
		<label class="TITULO_REPORTE"><?Php echo $nivel[$i]; ?></label>
        <?
    	    $rsComprasNum = $obBD_con1->getArrayConsulta(865, $Ses_Emp_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);			
			$totalCompras= count($rsComprasNum);
	    if ($totalCompras>0 &&  $aCom==1)
		{
		?>
        <table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
        <thead>
          <tr class="Texto_Listados">
            <th colspan="7" style="color: #FFF;" bgcolor="#025ECC"><div align="center">COMPRAS</div></th>
            </tr>
          <tr class="Texto_Listados">
            <th width="6%" align="center" bgcolor="#99CCCC">C&oacute;d.</th>
            <th width="34%" align="center" bgcolor="#99CCCC">Transacci&oacute;n</th>
            <th width="12%" align="center" bgcolor="#99CCCC">No. Registros</th>
            <th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa 0%</th>
            <th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa <? echo $row_rs_PorIva['Iva_Por']."%";?></th>
            <th width="12%" align="center" bgcolor="#99CCCC">BI No Objeto de IVA</th>
            <th width="12%" align="center" bgcolor="#99CCCC">Valor IVA</th>
          </tr>
          </thead>
          <tbody>
          <? 
		     $filas = 0;
			 $suma1=0;
			 $suma2=0;
			 $suma3=0;
			 $suma4=0;		     					 							 
		  foreach($rsComprasNum as $row)
		  {		
			  //$rsComprasBI0 = $obBD_con1->getRowConsulta(878, $ini.'*'.$fin.'*'.$row['Tic_Cod'].'*'.$Ses_Emp_Cod.'*'.'0', $obBD_conexion);
			  //$rsComprasBI12 = $obBD_con1->getRowConsulta(878, $ini.'*'.$fin.'*'.$row['Tic_Cod'].'*'.$Ses_Emp_Cod.'*'.$row_rs_PorIva['Iva_Por'], $obBD_conexion);
              // 
              $rsComprasBI = $obBD_con1->getRowConsulta(882, $ini.'*'.$fin.'*'.$row['Tic_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
		      $filas++;
              $ncred=($row['Tic_Sri']*1==4)?-1:1;
              $suma1+=($ncred*$rsComprasBI['Sub0']); 
              $suma2+=($ncred*$rsComprasBI['Sub12']); 
              $suma4+=($ncred*$rsComprasBI['IvaTot']);
			  /*if ($row['Tic_Sri']*1==4) //notas de credito
			  {
			  	$suma1-=$rsComprasBI0['Importe'];
				$suma2-=$rsComprasBI12['Importe'];
				$suma4-=$rsComprasBI12['Iva_Val'];
			  }else{
				$suma1+=$rsComprasBI0['Importe']; 
				$suma2+=$rsComprasBI12['Importe']; 
				$suma4+=$rsComprasBI12['Iva_Val'];
			  }	*/		  			  
			  $suma3='0.00';			  
		  ?>
          <tr class="Texto_Listados">
            <td align="center" class="LetraNegra" width="6%"><?Php echo $row['Tic_Sri'];?></td>
            <td align="center" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><?Php echo $row['Tic_Des'];?></td>
            <td align="center" class="LetraNegra"><?Php echo $row['total'];?></td>
            <td align="center" class="LetraNegra"><?Php echo number_format($rsComprasBI['Sub0'],2);?></td>
            <td align="center" class="LetraNegra"><?Php echo number_format($rsComprasBI['Sub12'],2);?></td>
            <td align="center" class="LetraNegra"><?Php echo "0.00";?>&nbsp;</td>
            <td align="center" class="LetraNegra"><?Php echo number_format($rsComprasBI['IvaTot'],2);?></td>
          </tr>
		  <? }?>
          <tr class="totales">
            <td colspan="3" align="right" class=""><strong>Total:</strong></td>
            <td align="center" class=""><strong><? echo number_format($suma1,2);?></strong></td>
            <td align="center" class=""><strong><? echo number_format($suma2,2);?></strong></td>
            <td align="center" class=""><strong><? echo $suma3;?></strong></td>
            <td align="center" class=""><strong><? echo number_format($suma4,2);?></strong></td>
          </tr>
          
          </tbody>
          </table>
		 
          
         <? }?>
        </div>
        </td>
      </tr>
      <tr>
        <td height="14">&nbsp;</td>
      </tr>
      <tr>
        <td height="14">
        <?        
		$rsVentasNum = $obBD_con1->getArrayConsulta(883, $Ses_Emp_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);	    
		$totalVentas=count($rsVentasNum);
		if($totalVentas>0 && $bVen==1)
		{
		?>
        <table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
          <thead>
            <tr class="Texto_Listados">
              <th colspan="7" style="color: #FFF;" bgcolor="#025ECC"><div align="center">VENTAS</div></th>
            </tr>
            <tr class="Texto_Listados">
              <th width="6%" align="center" bgcolor="#99CCCC">C&oacute;d.</th>
              <th width="34%" align="center" bgcolor="#99CCCC">Transacci&oacute;n</th>
              <th width="12%" align="center" bgcolor="#99CCCC">No. Registros</th>
              <th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa 0%</th>
              <th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa <? echo $row_rs_PorIva['Iva_Por']."%";?></th>
              <th width="12%" align="center" bgcolor="#99CCCC">BI No Objeto de IVA</th>
              <th width="12%" align="center" bgcolor="#99CCCC">Valor IVA</th>
            </tr>
          </thead>
          <tbody>
            <? 
		     $filas = 0;
			 $suma1='0.00';
			 $suma2='0.00';
			 $suma3='0.00';
			 $suma4='0.00';
		  foreach($rsVentasNum as $row)
		  {		      
		      $filas++;
			  if ($row['Tic_Sri']=='04') //notas de credito
			  {
			  	$suma1-=$row['Importe0'];			 
			    $suma2-=$row['Importe12'];
				$suma4-=$row['Tot_Iva'];
			  }else{								
				$suma1+=$row['Importe0'];			 
			    $suma2+=$row['Importe12'];
				$suma4+=$row['Tot_Iva'];
			  }			  			  
			  $suma3='0.00';
			  			  
		  ?>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra"><?Php echo $row['Tic_Sri'];?></td>
              <td align="center" class="LetraNegra"><?Php echo $row['Tic_Des'];?></td>
              <td align="center" class="LetraNegra"><?Php echo $row['total'];?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format($row['Importe0'],2);?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format($row['Importe12'],2);?></td>
              <td align="center" class="LetraNegra"><?Php echo '0.00';?>&nbsp;</td>
              <td align="center" class="LetraNegra"><?Php echo number_format($row['Tot_Iva'],2);?></td>
            </tr>            
			<? }?>
            <tr class="totales">
              <td colspan="3" align="right" class=""><strong>Total:</strong></td>
              <td align="center" class=""><strong><? echo number_format($suma1,2);?></strong></td>
              <td align="center" class=""><strong><? echo number_format($suma2,2);?></strong></td>
              <td align="center" class=""><strong><? echo number_format($suma3,2);?></strong></td>
              <td align="center" class=""><strong><? echo number_format($suma4,2);?></strong></td>
            </tr>
           <? $rsVentasNum = $obBD_con1->getArrayConsulta(888, $Ses_Emp_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);	
			  $totalVentas=count($rsVentasNum);
		     $filas = 0;
			 $suma1='0.00';
			 $suma2='0.00';
			 $suma3='0.00';
			 $suma4='0.00';
			 $rsVentasReem = $obBD_con1->getRowConsulta(889, $Ses_Emp_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);	
			 
			 if($totalVentas>0 && $bVen==1)
		    {		      
			  foreach($rsVentasNum as $row)
		      {		      
		      $filas++;
			  if ($row['Tic_Sri']=='04') //notas de credito
			  {
			  	$suma1-=$row['Importe0'];			 
			    $suma2-=$row['Importe12'];
				$suma4-=$row['Tot_Iva'];
			  }else{								
				$suma1+=$row['Importe0'];			 
			    $suma2+=$row['Importe12'];
				$suma4+=$row['Tot_Iva'];
			  }			  			  
			  $suma3='0.00';
			
		  			  
		  ?>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra"><?Php echo $row['Tic_Sri'];?></td>
              <td align="center" class="LetraNegra"><?Php echo $row['Tic_Des'];?></td>
              <td align="center" class="LetraNegra"><?Php echo $rsVentasReem['total'];?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format($row['Importe0'],2);?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format($row['Importe12'],2);?></td>
              <td align="center" class="LetraNegra"><?Php echo '0.00';?>&nbsp;</td>
              <td align="center" class="LetraNegra"><?Php echo number_format($row['Tot_Iva'],2);?></td>
            </tr>            
			<? } }	 
			?>
          </tbody>
        </table>
        <? }?>
        </td>
      </tr>
      
     
      <tr>
        <td height="7">&nbsp;</td>
      </tr>
      <tr>
        <td height="7">       
        <table width="100%" border="1" style="border-collapse:collapse;" cellpadding="2" cellspacing="0">
          <thead>
            <tr class="Texto_Listados">
              <th colspan="4" style="color: #FFF;" bgcolor="#025ECC"><div align="center">ANULADOS</div></th>
            </tr>
            <tr class="Texto_Listados">
              <th width="18%" align="center" bgcolor="#99CCCC">Tipo</th>
              <th width="24%" align="center" bgcolor="#99CCCC">Secuencia</th>
              <th colspan="2" align="center" bgcolor="#99CCCC">Autorizacion</th>
              </tr>
          </thead>
          <tbody>
            <? 
		  
        	$rs_ventas = $obBD_con1->getArrayConsulta(390, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);
			$rs_retencion = $obBD_con1->getArrayConsulta(237, $ini.'*'.$fin.'*'.'I'.'*'.$Ses_Emp_Cod, $obBD_conexion);
				
		  $totalAnulados = 0;		  
		  foreach($rs_ventas as $row){
		     $totalAnulados+=1;  
		  ?>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra"><?Php echo $row['Tic_Des'];?></td>
              <td align="center" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><span class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><?Php echo str_pad($row['Vet_Num'], 9, "0", STR_PAD_LEFT);?></span></td>
              <td colspan="2" align="left" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><?Php echo str_pad($row['Aut_Sri'], 9, "0", STR_PAD_LEFT);?></td>
              </tr>
            <? }
          foreach($rs_retencion as $row){
		     $totalAnulados+=1;  
		  ?>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra"><?Php echo $row['Tic_Des'];?></td>
              <td align="center" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><span class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><?Php echo str_pad($row['Ret_Num'], 9, "0", STR_PAD_LEFT);?></span></td>
              <td colspan="2" align="left" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><?Php echo str_pad($row['Aut_Sri'], 9, "0", STR_PAD_LEFT);?></td>
              </tr>
            <? }?>  
            <tr class="Texto_Listados">
              <td colspan="3" align="right" class="LetraNegra"><strong>Total:</strong></td>
              <td width="25%" align="center" class="LetraNegra"><strong><?Php echo $totalAnulados;?></strong></td>
            </tr>
          </tbody>
        </table></td>
      </tr>
      <tr>
        <td height="7">&nbsp;</td>
      </tr> 
       <? $rsExport = $obBD_con1->getArrayConsulta(884, $Ses_Emp_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);
	  $totalExpo=count($rsExport);	  
	  if($totalExpo>0 && $Exp==1)
	  {
	  ?>     
      <tr>
        <td height="8">        
        <table width="100%" border="1" style="border-collapse:collapse;" cellpadding="2" cellspacing="0">
          <thead>
            <tr class="Texto_Listados">
              <th colspan="4" style="color: #FFF;" bgcolor="#025ECC"><div align="center">EXPORTACIONES (VENTAS)</div></th>
            </tr>
            <tr class="Texto_Listados">
              <th width="6%" align="center" bgcolor="#99CCCC">Cod.</th>
              <th width="34%" align="center" bgcolor="#99CCCC">Transacci&oacute;n</th>
              <th width="36%" align="center" bgcolor="#99CCCC">No. Registros</th>
              <th width="24%" align="center" bgcolor="#99CCCC">Valor FOB</th>
            </tr>
          </thead>
          <tbody>
            <? 
		  $totalExp = 0;		  
		  foreach($rsExport as $row)
		  {
		     $totalExp+=$row['Importe']+$row['Tot_Iva'];  
		  ?>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra"><?Php echo $row['Tic_Sri'];?></td>
              <td align="center" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><span class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><?Php echo $row['Tic_Des'];?></span></td>
              <td align="center" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><? echo $row['total'];?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format($row['Importe']+$row['Tot_Iva'],2);?></td>
            </tr>
            <? }?>
            <tr class="totales">
              <td colspan="3" align="right" class=""><strong>Total:</strong></td>
              <td align="center" class=""><strong><?Php echo number_format($totalExp,2);?></strong></td>
            </tr>
          </tbody>
        </table></td>
      </tr>
      <? }?>
      <tr>
        <td height="15">&nbsp;</td>
      </tr>
      <tr>
        <td height="29" class="Texto_Listados"><strong>RESUMEN DE RETENCIONES </strong></td>
      </tr>
      <tr>
        <td height="30">
        <?        
    	$rsRentaCompra = $obBD_con1->getArrayConsulta(869, $Ses_Emp_Cod.'*R*'.$ini.'*'.$fin, $obBD_conexion);	    
		$totalRenta=count($rsRentaCompra);
		if($totalRenta>0 && $aCom==1)
		{
		?>
        <table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
          <thead>
            <tr class="Texto_Listados">
              <th colspan="5" style="color: #FFF;" bgcolor="#025ECC"><div align="center"><strong>RETENCION EN LA FUENTE DE IMPUESTO A LA RENTA</strong></div></th>
              </tr>
            <tr class="Texto_Listados">
              <th width="7%" align="center" bgcolor="#99CCCC">C&oacute;d.</th>
              <th width="53%" align="center" bgcolor="#99CCCC">Concepto de Retenci&oacute;n</th>
              <th width="16%" align="center" bgcolor="#99CCCC">No. Registros</th>
              <th width="12%" align="center" bgcolor="#99CCCC">Base Imponible</th>
              <th width="12%" align="center" bgcolor="#99CCCC">Valor Retenido</th>
            </tr>
          </thead>
          <tbody>
            <? 
		   $filas = 0;	
		   $suma1=0;	   
		   $suma2=0;
		   $auxAcceso=1;	   
		  foreach($rsRentaCompra as $row)
		  {		  
		     $rsValorRentaCompra = $obBD_con1->getRowConsulta(873, $Ses_Emp_Cod.'*'.$row['Ren_Cod'].'*'.$ini.'*'.$fin, $obBD_conexion);
			 $filas++;
			 $suma1+=$rsValorRentaCompra['base'];	   
			 $suma2+=round($rsValorRentaCompra['valor'],2);	   
		    if ($row['Ren_Sri']=='332' && $auxAcceso==1)
			{   
			    $auxAcceso=0;
				$rs_compras = $obBD_con1->getArrayConsulta(874, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);
				$base332=0;
				$tot332=0;
				foreach($rs_compras as $row_rs_compras)
				{
					/**
					* Consulta de facturas que no tienen retencion codigo 332
					*/
					$row_rs_compras_sin_retenc = $obBD_con1->getRowConsulta(855, $row_rs_compras['Cop_Cod'], $obBD_conexion);
					$total_rs_compras_sin_retenc = count($row_rs_compras_sin_retenc);
					$cont=0;
					
					if($row_rs_compras_sin_retenc['Iva_Por']!="")
					{						
						$base332+= ($row_rs_compras_sin_retenc['Sub0']+$row_rs_compras_sin_retenc['Sub12']);												
						$tot332++;
					}
				}
			?>
                <tr class="Texto_Listados">
                  <td align="center" class="LetraNegra">332</td>
                  <td align="center" class="LetraNegra" style="white-space: nowrap; overflow: hidden;">OTRAS COMPRAS DE BIENES Y SERVICIOS NO SUJETAS A RETENCIÓN</td>
                  <td align="center" class="LetraNegra"><? echo $tot332;?></td>
                  <td align="center" class="LetraNegra"><? echo number_format($base332,2);?></td>
                  <td align="center" class="LetraNegra">0.00</td>             
                </tr>
            <? }else{?>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra"><?Php echo $row['Ren_Sri'];?></td>
              <td align="center" class="LetraNegra"><?Php echo $row['Ren_Con'];?></td>
              <td align="center" class="LetraNegra"><?Php echo $row['total'];?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format(round($rsValorRentaCompra['base'],2),2);//number_format($rsValorRentaCompra['base'],2);?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format(round($rsValorRentaCompra['valor'],2),2);//number_format($rsValorRentaCompra['valor'],2);?></td>
            </tr>
			<? }
		  }?>
            <tr class="totales">
              <td colspan="3" align="right" class=""><strong>Total:</strong></td>
              <td align="center" class=""><strong><?Php echo number_format($base332+$suma1,2);?></strong></td>
              <td align="center" class=""><strong><?Php echo number_format($suma2,2);?></strong></td>
            </tr>            
          </tbody>
        </table>
        <? }?>
        
        </td>
      </tr>
      <tr>
        <td height="19">&nbsp;</td>
      </tr>
      <tr>
        <td height="65">
		<?        
    	   	 $rsIvaCompra = $obBD_con1->getArrayConsulta(870, '', $obBD_conexion);
			 $totalIva=count($rsIvaCompra);	
		if($totalIva>0 && $aCom==1)
		{		  
		?>
          <table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
          <thead>
            <tr class="Texto_Listados">
              <th colspan="3" style="color: #FFF;" bgcolor="#025ECC"><div align="center">RETENCION EN LA FUENTE DE IVA</div></th>
              </tr>
            <tr class="Texto_Listados">
              <th width="17%" align="center" bgcolor="#99CCCC">Operaci&oacute;n</th>
              <th width="59%" align="center" bgcolor="#99CCCC">Concepto de Retenci&oacute;n</th>
              <th width="24%" align="center" bgcolor="#99CCCC">Valor Retenido</th>
            </tr>
          </thead>
          <tbody>
          <? 
		  $filas = 0;
		  $suma1=0;
		  foreach($rsIvaCompra as $row)
		  {
		     $rsDetIvaCompra = $obBD_con1->getRowConsulta(871, $Ses_Emp_Cod.'*'.$ini.'*'.$fin.'*'.$row['Ren_Cod'], $obBD_conexion);	
			 $filas++;
			 $suma1+=$rsDetIvaCompra['valor'];  
		  ?>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra"><?Php echo $row['DetRen'];?></td>
              <td align="center" class="LetraNegra" style="white-space: nowrap; overflow: hidden;"><?Php echo $row['Ren_Con'];?></td>
              <td align="center" class="LetraNegra"><?Php echo number_format($rsDetIvaCompra['valor'],2);?></td>
            </tr>
			<? }?>
            <tr class="totales">
              <td colspan="2" align="right" class=""><strong>Total:</strong></td>
              <td align="center" class=""><strong><?Php echo number_format($suma1,2);?></strong></td>
            </tr>
            
          </tbody>
        </table>
        <? }?>
        </td>
      </tr>
      <tr>
        <td height="19">&nbsp;</td>
      </tr>
      <tr>
        <td height="59">
        <?
         /**
		 * Consultando todas las ventas segun la cedula y fecha y empresa del cliente
		 */
		 $rs_TodasVentas = $obBD_con1->getArrayConsulta(872, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);	 
		 $totalVentas=count($rs_TodasVentas);
		 
		 $rs_RetBancarias = $obBD_con1->getRowConsulta(891, $Ses_Emp_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);	 
		 
		 
		 //echo count($row_datos['Vet_Cod']);
		 foreach($rs_TodasVentas as $row_datos)
		 {
		  	 /*Consultamos los Items detalles de ventas*/
			 $rs_DetalleVentas = $obBD_con1->getArrayConsulta(858, $row_datos['Vet_Cod'], $obBD_conexion);	 			
			 foreach($rs_DetalleVentas as $row_Detdatos)
		 	 {
			 	  if ($row_Detdatos['Ren_Cod']!='')
				  {
					  /*Consultamos el porcentaje de la renta*/
			 		  $rs_rentaPor = $obBD_con1->getRowConsulta(864, $row_Detdatos['Ren_Cod'], $obBD_conexion);	 
					  $valorRenta=$valorRenta + (($row_Detdatos['Tot_Imp']*$rs_rentaPor['Ren_Por'])/100);					 
				  }
				  if ($row_Detdatos['Ren_Iva']!='')
				  {
					  /*Consultamos el porcentaje de la iva*/
			 		  $rs_ivaPor = $obBD_con1->getRowConsulta(864, $row_Detdatos['Ren_Iva'], $obBD_conexion);
					  $valorIva=$valorIva + (($row_Detdatos['Tot_Iva']*$rs_ivaPor['Ren_Por'])/100);
				  }				  
			 }
		 }
		 if($totalVentas>0 && $bVen==1)
		 {
		?>
        <table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
          <thead>
            <tr class="Texto_Listados">
              <th colspan="3" style="color: #FFF;" bgcolor="#025ECC"><div align="center">RESUMEN DE RETENCIONES QUE LE EFECTUARON EN EL PERIODO</div></th>
            </tr>
            <tr class="Texto_Listados">
              <th width="17%" align="center" bgcolor="#99CCCC">Operaci&oacute;n</th>
              <th width="59%" align="center" bgcolor="#99CCCC">Concepto de Retenci&oacute;n</th>
              <th width="24%" align="center" bgcolor="#99CCCC">Valor Retenido</th>
            </tr>
          </thead>
          <tbody>           
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra">VENTA</td>
              <td align="center" class="LetraNegra">Valor de IVA que le han retenido</td>
              <td align="center" class="LetraNegra"><?Php echo number_format($valorIva+$rs_RetBancarias['ivaTot'],2);?></td>
            </tr>
            <tr class="Texto_Listados">
              <td align="center" class="LetraNegra">VENTA</td>
              <td align="center" class="LetraNegra">Valor de Renta que le han retenido</td>
              <td align="center" class="LetraNegra"><?Php echo number_format($valorRenta+$rs_RetBancarias['renTot'],2);?></td>
            </tr>
            <tr class="totales">
              <td colspan="2" align="right" class=""><strong>Total:</strong></td>
              <td align="center" class=""><strong><?Php echo number_format($valorRenta+$valorIva+$rs_RetBancarias['ivaTot']+$rs_RetBancarias['renTot'],2);?></strong></td>
            </tr>
           
          </tbody>
        </table>
        <? }?>
        </td>
      </tr>
      <tr>
        <td height="59">&nbsp;</td>
      </tr>
    </table>
	</td>
  </tr>
</table>
</body>
</html>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>