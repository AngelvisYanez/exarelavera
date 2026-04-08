<?php 
/**
* @abstract Reporte de comprobante contable (ingreso, egreso, diario)
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización: 2010-09-06
* Fecha de actualización  2012-04-29
* Fecha de actualización  2015-05-07
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_compr.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
	  	
/*
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("d-m-Y");
$fecha = explode('-', $hoy);
	  
if (isset($codigo))
{	
	/* 
	* Cargado de la cabecera 
	*/
	//$row_rs_cabcomp = $obBD_con1->getRowConsulta(333, $tabla.'*'.$codigo.'*'.$tipo.'*'.$Pec_Cod.'*'.$campo, $obBD_conexion);	
    $row_rs_cabcomp = $obBD_con1->getRowConsulta(389, $codigo, $obBD_conexion);	
	$row_rs_usuComp = $obBD_con1->getRowConsulta(365, $row_rs_cabcomp['Usu_Cod'], $obBD_conexion);	
	
	list($ann, $mes, $dia) = explode('-', $row_rs_cabcomp['Com_Fec']);	
}//FIn del if (isset($codigo))

if(count($row_rs_cabcomp) > 0){	
    switch($row_rs_cabcomp['Tia_Ini'])	{
        case 'I': $etiqueta=isset($array_asien[0])?$array_asien[0]:'';
                  $etiqueta2="RECIBIDO DE:";
            break;
        case 'E': $etiqueta=isset($array_asien[1])?$array_asien[1]:'';
                  $etiqueta2="PAGUESE A LA ORDEN DE:";
            break;
        case 'D': $etiqueta=isset($array_asien[2])?$array_asien[2]:'';
                  $etiqueta2="DEPOSITARIO:";
             break;
    }
} ?>
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
.Letra_punto_venta_2 {				
	/*font-family: Verdana;*/
	font-family: Verdana;
	font-size: 12px;	
}
.TablaRepComprLeft {
    font-weight: normal; 
}
    
</style>
</head>
<body>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td colspan="4" align="center" valign="top">
	<table width="80%" border="0" cellpadding="0" cellspacing="0">
	  <tr align="center">
	    <td colspan="5" valign="top" class="Texto_Reporte">&nbsp;<?php
		$tip = $row_rs_cabcomp['Tia_Abr'];
		$num = $row_rs_cabcomp['Com_Num'];
		$etiqueta = $row_rs_cabcomp['Tia_Des'];
		$titulo = "<span class='Texto_Reporte'><strong>Comprobante de $etiqueta N</strong></span><span class='Texto_normal_10'>o</span><span class='Texto_Reporte'><strong> $tip-$mes-$num</strong></span>";
		 $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, " ", $obBD_conexion); ?></td>
	    </tr>
      </table>
	<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="Texto_Reporte">      
      <tr align="center">
        <td colspan="2">&nbsp;</td>
        <td align="left" class="Texto_normal_10">&nbsp;</td>
        <td align="right" class="Texto_normal_10">&nbsp;</td>
        <td align="left">&nbsp;</td>
      </tr>
      <tr align="center">
        <td colspan="2"><div align="left"><b><? echo $etiqueta2; ?></b></div></td>
        <td width="59%" align="left" class="Texto_normal_10"><? 
					echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom'];  
					?></td>
        <td width="7%" align="right" class="Texto_normal_10"><b>POR:</b></td>
        <td width="18%" align="left">&nbsp;<span class="Texto_Reporte"><span class="Texto_normal_10">&nbsp;$<? echo number_format($row_rs_cabcomp['Com_Val'],2); ?></span></span></td>
      </tr>
	  
      <tr align="center">
        <td colspan="2"><div align="left" class="Texto_normal_10"><b>LA CANTIDAD DE: </b></div></td>
        <td align="left" class="Texto_normal_10" style="text-transform:uppercase"><? echo num2letras($row_rs_cabcomp['Com_Val'],false).' DOLARES AMERICANOS'; ?></td>
        <td align="right" class="Texto_normal_10" style="text-transform:uppercase"><b>FECHA:</b></td>
        <td align="left" class="Texto_normal_10" style="text-transform:uppercase">&nbsp;          <?php 
		echo mes($mes,2).'/'.$dia.'/'.$ann; ?></td>
        </tr>      
      <tr align="center">
        <td colspan="2"><div align="left" class="Texto_normal_10"><b>POR CONCEPTO :</b> </div></td>
        <td colspan="3" align="left" class="Texto_normal_10"><? echo $row_rs_cabcomp['Com_Con']; ?></td>
      </tr>
    </table>
	</td>
  </tr>
  <tr valign="top">
    <td valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="10%" align="center" class="TablaRepCompr"><font class="Letra_punto_venta_2">C&oacute;digo</font></td>
        <td align="center" class="TablaRepCompr"><font class="Letra_punto_venta_2">Descripci&oacute;n</font></td>
        <td align="center" class="TablaRepCompr"><font class="Letra_punto_venta_2">Glosa</font></td>
        <td width="10%" align="right" class="TablaRepCompr"><font class="Letra_punto_venta_2">Debe</font></td>
        <td width="10%" align="right" class="TablaRepCompr"><font class="Letra_punto_venta_2">Haber</font></td>
      </tr>
      <?php
	  	/* Consulta las cuentas de grupo */
		$row_rs_grupos = $obBD_con1->getArrayConsulta(339, $codigo.'*'.'D'.'*'.'', $obBD_conexion);		
		$total=0;
		$total_h=0;
	foreach ($row_rs_grupos as $row){
	  	/*  Etiqueta para cuenta de GRUPO DEBE */
		$row_rs_etiquetas_g = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
	
		$Pld_Cod = $row['Pld_Cod'];
		/*  Cargado del detalle DEBE */
		$row_rs_cuentas = $obBD_con1->getArrayConsulta(336, $codigo.'*'.'D'.'*'.'ORDER BY Pld_Cdc'.'*'."AND det_plan.Pld_Rec ='$Pld_Cod'", $obBD_conexion);
	  ?>
      <!--<tr>
        <td align="left" class="TablaRepComprLeft "><? echo $row['Pld_Cdc']; ?></td>
        <td align="left" style="text-transform:uppercase" class="TablaRepComprLeft Texto_normal_10"><? echo $row['Pld_Des']." (".$row_rs_etiquetas_g['Pld_Des'].")"; ?></td>
        <td align="left" style="text-transform:uppercase" class="TablaRepComprLeft Texto_normal_10">&nbsp;</td>
        <td align="right" class="TablaRepComprLeft Texto_normal_10">&nbsp;</td>
        <td align="right" class="TablaRepComprLeft TablaRepComprRight">&nbsp;</td>
      </tr>-->
      <tr>
        <?	  
	  $row_rs_resumen=array();      
          foreach ($row_rs_cuentas as $row){
            $shouldaAdd=true;  
            for($i=0;$i<count($row_rs_resumen);$i++){
                    if($row_rs_resumen[$i]['Pld_Cod']==$row['Pld_Cod']){
                        $shouldaAdd=false;                        
                        $row_rs_resumen[$i]['Asi_Glo']='Valor Agrupado';
                        $row_rs_resumen[$i]['Asi_Val']=$row_rs_resumen[$i]['Asi_Val']+$row['Asi_Val'];
                        break;
                    }
            }   
            if($shouldaAdd)
                array_push ($row_rs_resumen, $row);
          }       
	  foreach ($row_rs_resumen as $row) // Nos permite presentar las cuentas resumindas
	  //foreach ($row_rs_cuentas as $row)   // Nos permite presentar las cuentas sin Resumir
	  {
	  ?>
        <td align="left" class="TablaRepComprLeft"><font class="Letra_punto_venta_2"><? echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Cdc']; ?></font></td>
        <td align="left" class="TablaRepComprLeft"><font class="Letra_punto_venta_2"><? 
		if ($row['Asi_Deh']=='D') { 
			 echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Des']; 
			 }else
			 {  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Des']; }
			 ?></font></td>
        <td class="TablaRepComprLeft" align="left"><font class="Letra_punto_venta_2"><?Php echo $row['Asi_Glo']; ?></font></td>
        <td class="TablaRepComprLeft" align="right"><font class="Letra_punto_venta_2"><? if ($row['Asi_Deh']=='D') { echo number_format($row['Asi_Val'],2); 
					/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
					$total=$total + round($row['Asi_Val'],2); } else { echo '&nbsp'; }?></font></td>
        <td class="TablaRepComprLeft TablaRepComprRight" align="right"><font class="Letra_punto_venta_2"><? if ($row['Asi_Deh']=='H') { echo number_format($row['Asi_Val'],2);
				   /* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
				    $total_h=$total_h + round($row['Asi_Val'],2); } else{ echo '&nbsp'; } ?></font></td>
      </tr>
      <?
	  } //Fin del foreach ($row_rs_cuentas as $row)
	 }//Fin del foreach ($row_rs_grupos as $row)
	 
	/* 
	* Consulta las cuentas de grupo 
	*/
	$row_rs_grupos = $obBD_con1->getArrayConsulta(339, $codigo.'*'.'H'.'*'.'', $obBD_conexion);
	
	foreach ($row_rs_grupos as $row){ 
	  	/* Etiqueta para cuenta de GRUPO HABER */
		$row_rs_etiquetas_g = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
	?>
      <!--<tr>
        <td align="left" class="TablaRepComprLeft"><? echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Cdc']; ?></td>
        <td align="left" style="text-transform:uppercase" class="TablaRepComprLeft"><? echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Des']." (".$row_rs_etiquetas_g['Pld_Des'].")"; ?></td>
        <td align="left" style="text-transform:uppercase" class="TablaRepComprLeft">&nbsp;</td>
        <td align="right" class="TablaRepComprLeft">&nbsp;</td>
        <td align="right" class="TablaRepComprLeft TablaRepComprRight">&nbsp;</td>
      </tr>-->
      <?Php		
		$Pld_Cod = $row['Pld_Cod'];
		/* Cargado del detalle HABER*/
		$row_rs_cuentas = $obBD_con1->getArrayConsulta(336, $codigo.'*'.'H'.'*'.''.'*'."AND det_plan.Pld_Rec ='$Pld_Cod'", $obBD_conexion);
	  
	  $row_rs_resumen=array();      
          foreach ($row_rs_cuentas as $row){
            $shouldaAdd=true;  
            for($i=0;$i<count($row_rs_resumen);$i++){
                    if($row_rs_resumen[$i]['Pld_Cod']==$row['Pld_Cod']){
                        $shouldaAdd=false;                        
                        $row_rs_resumen[$i]['Asi_Glo']='Valor Agrupado';
                        $row_rs_resumen[$i]['Asi_Val']=$row_rs_resumen[$i]['Asi_Val']+$row['Asi_Val'];
                        break;
                    }
            }   
            if($shouldaAdd)
                array_push ($row_rs_resumen, $row);
          }       
	  foreach ($row_rs_resumen as $row){
	  ?>
      <tr>
        <td align="left" class="TablaRepComprLeft"><font class="Letra_punto_venta_2"><? echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Cdc']; ?></font></td>
        <td align="left" class="TablaRepComprLeft"><font class="Letra_punto_venta_2"><? 
		if ($row['Asi_Deh']=='D') { 
			 echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Des']; 
			 }else
			 {  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Des']; 
                         
                         }
			 ?></font></td>
        <td align="left" class="TablaRepComprLeft"><font class="Letra_punto_venta_2"><?Php echo $row['Asi_Glo']; ?></font></td>
        <td align="right" class="TablaRepComprLeft"><font class="Letra_punto_venta_2"><? if ($row['Asi_Deh']=='D') { echo number_format($row['Asi_Val'],2);
					/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */ 
					$total=$total + round($row['Asi_Val'],2); } else { echo '&nbsp'; }?></font></td>
        <td class="TablaRepComprLeft TablaRepComprRight" align="right"><font class="Letra_punto_venta_2"><? if ($row['Asi_Deh']=='H') { echo number_format($row['Asi_Val'],2); 
				   /* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
		   			$total_h=$total_h + round($row['Asi_Val'],2); } else{ echo '&nbsp'; } ?></font></td>
      </tr>
      <?
	  } //Fin del foreach ($row_rs_cuentas as $row)
	 }//Fin del foreach ($row_rs_grupos as $row)
	  ?>
      <tr>
        <td colspan="3" class="TITULO_REPORTE TablaRepCompr"><div align="right"><strong>SUMAN:</strong></div></td>
        <td class="TITULO_REPORTE TablaRepCompr"><font class="Letra_punto_venta_2"><div align="right"><? echo number_format($total,2); ?></div></font></td>
        <td class="TITULO_REPORTE TablaRepCompr"><font class="Letra_punto_venta_2"><div align="right"><? echo number_format($total_h,2); ?></div></font></td>
      </tr>
    </table>
      
	<?
	/* Cargado de los cheques del comprobante */
	$row_rs_carcheq = $obBD_con1->getArrayConsulta(334, $row_rs_cabcomp['Com_Cod'], $obBD_conexion);
	
	if (count($row_rs_carcheq) > 0) 
	{
	?>
	<span class="Texto_normal_10"><strong>CHEQUES DEL COMPROBANTE</strong></span> <br>
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tbody id="contenido">
          
          <tr>
            <td width="20%" align="center" class="TablaRepCompr">Banco</td>
            <td width="60%" align="center" class="TablaRepCompr">Proveedor</td>
            <td width="10%" align="center" class="TablaRepCompr">N&ordm; Ch.</td>
            <td width="10%" align="center" class="TablaRepCompr">Valor</td>
          </tr>
          <? if(!isset($fila)) $fila=0;
	 foreach ($row_rs_carcheq as $row){
	 $fila++;
	 $nombre=explode(" ",$row['Prs_Nom']);
	 ?>
          <tr>
            <td class="TablaRepComprLeft TablaRepComprBottom"><? echo $row['Pld_Des']; ?></td>
            <td class="TablaRepComprLeft TablaRepComprBottom"><? echo $row['Prs_Ape'].' '.$nombre[0]; ?></td>
            <td align="right" class="TablaRepComprLeft TablaRepComprBottom"><? echo $row['Che_Num']; ?></td>
            <td align="right" class="TablaRepComprLeft TablaRepComprBottom TablaRepComprRight"><? echo number_format($row['Che_Val'],2); ?></td>
          </tr>
     <? }//Fin del foreach ($row_rs_carcheq as $row)  ?>
        </tbody>
      </table>
	  <? 
	 }//Fin del if (count($row_rs_carcheq) > 0)
	?>
	<? 	
	$tip=1;
	switch ($tip){
		case 1: ?>
	<br>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="Texto_normal_10" >
	  <tr>
	    <td width="16%" align="center" valign="top" class="TablaRepCompr Letra_punto_venta_2">EMITIDO POR</td>
	    <td width="18%" align="center" valign="top" class="TablaRepCompr Letra_punto_venta_2">DPTO. CONTABILIDAD</td>
	    <td width="16%" align="center" valign="top" class="TablaRepCompr Letra_punto_venta_2">APROBADO POR</td>
	    <td width="19%" valign="top" align="center" class="TablaRepCompr Letra_punto_venta_2">RECIBI CONFORME</td>
	    </tr>
	  <tr>
	    <td height="34" align="right" valign="bottom" class="TablaRepComprLeft TablaRepComprBottom">&nbsp;<div class="Letra_punto_venta_2"><?Php echo $row_rs_usuComp['Prs_Ape']." ".$row_rs_usuComp['Prs_Nom'];?></div></td>
	    <?php 
		  /*if ($fecha[1] == 12)
		  {
			$recibido =  "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;- 20&nbsp;&nbsp;&nbsp;";
		  }
		  else
		  {
			$recibido =  "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;- ".$fecha[2];	 		  
		  }*/
            $nombre=  explode(' ', $row_rs_cabcomp['Prs_Nom']);
            $recibi=$row_rs_cabcomp['Prs_Ape'].' '.$nombre[0];
		  ?>
	    <td align="center" valign="middle" class="TablaRepComprLeft TablaRepComprBottom">&nbsp;	      <?php //echo $recibido; ?></td>
	    <td align="center" valign="middle" class="TablaRepComprLeft TablaRepComprBottom">&nbsp;	      <?php //echo $recibido; ?></td>
	    <td valign="bottom" align="right" class="TablaRepComprLeft TablaRepComprBottom TablaRepComprRight">&nbsp;<div class="Letra_punto_venta_2"><? echo isset($recibi)?$recibi:''; ?></div></td>
	    </tr>
	  </table>
	<?Php	 
		break;	
		case 2: ?>
	<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" class="">
		
		<tr>
		  <td width="16%" align="center" valign="top" class="TablaRepCompr">EMITIDO POR</td>
		  <td width="18%" align="center" valign="top" class="TablaRepCompr">DPTO. CONTABILIDAD</td>
		  <td width="16%" align="center" valign="top" class="TablaRepCompr">APROBADO POR</td>
		  <td width="17%" align="center" valign="top" class="TablaRepCompr">AUTORIZADO POR</td>
		  <td width="19%" valign="top" align="center" class="TablaRepCompr">RECIBI CONFORME</td>
	    </tr>
		<tr>
		  <td height="40" align="right" valign="bottom" class="TablaRepCompr">&nbsp;&nbsp;<div class="Letra_punto_venta_2"><?Php echo $row_rs_usuComp['Prs_Ape']." ".$row_rs_usuComp['Prs_Nom'];?></div></td>
          <?php 
		  /*if ($fecha[1] == 12)
		  {
			$recibido =  "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;- 20&nbsp;&nbsp;&nbsp;";
		  }
		  else
		  {
			$recibido =  "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;- ".$fecha[2];			  
		  }*/
		  ?>
		  <td align="center" valign="middle" class="TablaRepCompr">&nbsp;		    <?php //echo $recibido; ?></td>
		  <td align="center" valign="middle" class="TablaRepCompr">&nbsp;		    <?php //echo $recibido; ?></td>
		  <td align="center" valign="middle" class="TablaRepCompr">&nbsp;		    <?php //echo $recibido; ?></td>
		  <td valign="middle" align="center" class="TablaRepCompr">&nbsp;		    <?php //echo $recibido; ?></td>
	    </tr>
	</table>
	<? break;
	   case 3: ?>
	<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" class="">
	  <tr>
	    <td width="16%" align="center" valign="top" class="TablaRepCompr">EMITIDO POR</td>
	    <td width="18%" align="center" valign="top" class="TablaRepCompr">DPTO. CONTABILIDAD</td>
	    <td width="16%" align="center" valign="top" class="TablaRepCompr">APROBADO POR</td>
	    <td width="19%" valign="top" align="center" class="TablaRepCompr">RECIBI CONFORME</td>
	    </tr>
	  <tr>
	    <td height="40" align="right" valign="bottom" class="TablaRepCompr">&nbsp;<div class="Letra_punto_venta_2"><?Php echo $row_rs_usuComp['Prs_Ape']." ".$row_rs_usuComp['Prs_Nom'];?></div></td>
	    <?php 
		 /* if ($fecha[1] == 12)
		  {
			$recibido =  "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;- 20&nbsp;&nbsp;&nbsp;";
		  }
		  else
		  {
			$recibido =  "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;- ".$fecha[2];			  
		  }*/
		  ?>
	    <td align="center" valign="middle" class="TablaRepCompr">&nbsp;	      <?php //echo $recibido; ?></td>
	    <td align="center" valign="middle" class="TablaRepCompr">&nbsp;	      <?php //echo $recibido; ?></td>
	    <td valign="middle" align="center" class="TablaRepCompr">&nbsp;	      <?php //echo $recibido; ?></td>
	    </tr>
	  </table>
	<? break;	
	} //Fin del switch ($tipo){
	?>  
  </tr>
</table>
</body>
</html>
<?Php 
/** 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();
?>