<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../../componentes/LOGICA/logica.php');
  	  require_once('../../tesoreria/LOGICA/tes_log_ccpp.php');
	  require_once('../../Librerias/procedimientos/almacenados_standar.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Mysql;
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Datos;
/***********************************************/
$hoy = date("Y-m-d");
	  if (isset($codigo))
	  {
		/* Consulta de la cabecera del reporte */
		$rs_institucion = $obBD_con1->consulta(sentencias_tes(207, $obBD_con1->parametros($Ses_Suc_Cod)), $obBD_conexion->conexion);
		$row_rs_institucion= $obBD_con1->registros();
		$total_rs_institucion = $obBD_con1->numregistros();
		/* Cargado de la cabecera */
		$rs_cabcomp = $obBD_con1->consulta(sentencias_con(333, $obBD_con1->parametros($tabla.'*'.$codigo.'*'.$tipo.'*'.$Pec_Cod.'*'.$campo))
									, $obBD_conexion->conexion);//Antes $op
		$row_rs_cabcomp = $obBD_con1->registros();
		$total_rs_cabcomp = $obBD_con1->numregistros();
		list($ann, $mes, $dia) = preg_split('![/.-]!', $row_rs_cabcomp['Com_Fec']);
	  }
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.Estilo1 {font-size: 16px}
.Estilo2 {font-size: 18px}
-->
</style>
</head>
<body class="Cuerpo" marginheight="50%">
<table width="100%" height="100%" border="0" align="center">
  <tr>
    <td height="1" colspan="4" align="center" valign="top">
	<?php if($total_rs_cabcomp > 0) {
	/* Consulta de los tipos de asientos */
	$rs_tipo_asien = $obBD_con1->consulta(sentencias_con(210, ''), $obBD_conexion->conexion);
	$row_rs_tipo_asien = $obBD_con1->registros();
	$total_rs_tipo_asien = $obBD_con1->numregistros();
	do {
		$descripcion = $descripcion.$row_rs_tipo_asien['Tia_Des'].'*';
		$array_asien[] = $row_rs_tipo_asien['Tia_Des'];
	}while($row_rs_tipo_asien = mysqli_fetch_assoc($rs_tipo_asien));
	switch($tipo) {
	case 1: $etiqueta=$array_asien[0];
			  $etiqueta2="RECIBIDO DE:";
			  break;
	case 2: $etiqueta=$array_asien[1];
			  $etiqueta2="PAGUESE A LA ORDEN DE:";
			  break;
	case 3: $etiqueta=$array_asien[2];
			  $etiqueta2="DEPOSITARIO:";
			  break;
	}
	?>
	<table width="80%" border="0" cellpadding="0" cellspacing="0">
      <tr align="center">
        <td width="9%" rowspan="5" valign="top"><img src="../../imagenes/logo.jpg" width="83" height="67"></td>
        <td colspan="4" class="TITULO_REPORTE Estilo1"><?Php echo $row_rs_institucion['Emp_Nom'].' - '.$ann; ?></td>
        </tr>
      <tr align="center">
        <td width="26%"valign="top" class="Texto_Reporte"><div align="right"><strong>R.U.C.:</strong></div></td>
        <td width="24%" align="left" valign="top" class="Texto_Reporte"><div align="left"> &nbsp;<?php echo $row_rs_institucion['Emp_Ruc']; ?></div></td>
        <td width="10%" align="left" valign="top" class="Texto_Reporte"><div align="right"><strong>TELEFONO:</strong></div></td>
        <td width="31%" align="left" valign="top" class="Texto_Reporte"><div align="left">&nbsp;<?php echo $row_rs_institucion['Suc_Te1']; ?></div></td>
      </tr>
      <tr align="center">
        <td align="left" valign="top" class="Texto_Reporte"><div align="right"><strong>DIRECCION:</strong></div></td>
        <td colspan="3" align="left" valign="top" class="Texto_Reporte"><div align="left"> &nbsp;<?php echo $row_rs_institucion['Suc_Dir']; ?></div></td>
        </tr>
      <tr align="center">
        <td align="left" valign="top" class="Texto_Reporte"><div align="right"><strong>E-MAIL:</strong></div></td>
        <td colspan="3" align="left" valign="top" class="Texto_Reporte"><div align="left"> &nbsp;<?php echo $row_rs_institucion['Suc_Cor']; ?></div></td>
      </tr>
      <tr align="center">
        <td colspan="4" align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php echo $row_rs_institucion['Ciu_Des']." - EL ORO - ECUADOR";?></div></td>
        </tr>
      <tr align="center">
        <td colspan="5" valign="top"><HR></td>
      </tr>
      <tr align="center">
        <td colspan="5" valign="top" class="TITULO_REPORTE_2"><span class="Estilo2">Comprobante de <?php echo $etiqueta; ?> N</span><span class="TITULO_REPORTE">o</span> <?php echo "C".$row_rs_cabcomp['Tia_Ini']."-".$mes."-".$row_rs_cabcomp['Com_Num']; ?></td>
        </tr>
      <tr align="center">
        <td colspan="5" valign="top" class="TITULO_REPORTE"><div align="right"><span class="TITULO_REPORTE">POR:</span> <span class="TITULO_REPORTE_2">$<?php echo number_format($row_rs_cabcomp['Com_Val'],2); ?></span>
            </div></td>
      </tr>
    </table>
	<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">      
      <tr align="center">
        <td colspan="2" class="TITULO_REPORTE">&nbsp;</td>
        <td width="34%" class="Texto_Reporte">&nbsp;</td>
        <td width="23%" class="TITULO_REPORTE"><div align="right">FECHA:</div></td>
        <td width="19%" class="Texto_Reporte"><span class="Texto_Reporte" style="text-transform:uppercase">
          &nbsp;
          <?php 
		echo mes($mes,2).'/'.$dia.'/'.$ann; ?>
        </span></td>
      </tr>
      <tr align="center">
        <td colspan="2" class="TITULO_REPORTE"><div align="left"><?php echo $etiqueta2; ?></div></td>
        <td colspan="3" class="Texto_Reporte"><div align="left"><?php 
					echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom'];  
					?></div>          <div align="left"></div></td>
        </tr>
      <tr align="center">
        <td colspan="2" class="TITULO_REPORTE"><div align="left">LA cantidad DE :</div></td>
        <td colspan="3" class="Texto_Reporte" style="text-transform:uppercase"><?php //echo $row_rs_cabcomp['Com_Val']; 
			$v_absoluto=explode(".",$row_rs_cabcomp['Com_Val']);
			echo num2letras($v_absoluto[0],false,true).', '.$v_absoluto[1].' /100 DOLARES AMERICANOS'; ?></td>
        </tr>
      <tr align="center">
        <td colspan="2" class="TITULO_REPORTE"><div align="left">POR CONCEPTO :</div></td>
        <td colspan="3" class="Texto_Reporte"><?php echo $row_rs_cabcomp['Com_Con']; ?></td>
      </tr>
    </table>
	<?php } ?></td>
  </tr>
  <tr valign="top">
    <td valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="20%" align="center" class="TablaRepCompr">C&oacute;digo</td>
        <td align="center" class="TablaRepCompr">Descripci&oacute;n</td>
		<td align="center" class="TablaRepCompr">Glosa</td>
		<td width="10%" align="center" class="TablaRepCompr">Debe</td>
        <td width="10%" align="center" class="TablaRepCompr">Haber</td>
      </tr>
	  <?php
	  	/* Consulta las cuentas de grupo */
		$rs_grupos = $obBD_con1->consulta(sentencias_con(339, $obBD_con1->parametros($codigo.'*'.'D'.'*'.'')), 
		$obBD_conexion->conexion);
		$row_rs_grupos = $obBD_con1->registros();
		$total_rs_grupos = $obBD_con1->numregistros();
	$total=0;
	do{
	  	/* Etiqueta para cuenta de GRUPO DEBE */
		$rs_etiquetas_g = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_grupos['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_etiquetas_g = $obBD_con1->registros();
		$total_rs_etiquetas_g = $obBD_con1->numregistros();
		$Pld_Cod = $row_rs_grupos['Pld_Cod'];
		/* Cargado del detalle DEBE */
		$rs_cuentas = $obBD_con1->consulta(sentencias_con(336,$obBD_con1->parametros($codigo.'*'.'D'.'*'.'ORDER BY Pld_Cdc'.'*'.
										"AND det_plan.Pld_Rec ='$Pld_Cod'")), $obBD_conexion->conexion);
		$row_rs_cuentas = $obBD_con1->registros();
		$total_rs_cuentas = $obBD_con1->numregistros();
	  ?>
	  <tr>
	    <td class="LetraNegra" align="left"><?php echo $row_rs_grupos['Pld_Cdc']; ?></td>
	    <td class="LetraNegra" align="left" style="text-transform:uppercase"><?php echo $row_rs_grupos['Pld_Des']." (".$row_rs_etiquetas_g['Pld_Des'].")"; ?></td>
	    <td class="LetraNegra" align="left" style="text-transform:uppercase">&nbsp;</td>
	    <td class="LetraNegra" align="right">&nbsp;</td>
	    <td class="LetraNegra" align="right">&nbsp;</td>
	    </tr>
	  <tr>	  
      <?php
	  do {
	  ?>
        <td class="LetraNegra" align="left"><?php echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cuentas['Pld_Cdc']; ?></td>
        <td class="LetraNegra" align="left"><?php 
		if ($row_rs_cuentas['Asi_Deh']=='D') { 
			 echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cuentas['Pld_Des']; 
			 }else
			 {  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cuentas['Pld_Des']; }
			 ?></td>
		<td class="LetraNegra" align="left"><?Php echo $row_rs_cuentas['Asi_Glo']; ?></td>
		<td class="LetraNegra" align="right">         
            <?php if ($row_rs_cuentas['Asi_Deh']=='D') { echo number_format($row_rs_cuentas['Asi_Val'],2); 
					$total=$total + $row_rs_cuentas['Asi_Val']; } else { echo '&nbsp'; }?>         </td>
		 <td class="LetraNegra" align="right">
		   <?php if ($row_rs_cuentas['Asi_Deh']=='H') { echo number_format($row_rs_cuentas['Asi_Val'],2); 
		   		} else{ echo '&nbsp'; } ?></td>
      </tr>
	  <?php
	  } while($row_rs_cuentas=mysqli_fetch_assoc($rs_cuentas)); 
	 }while($row_rs_grupos=mysqli_fetch_assoc($rs_grupos));
	/* Consulta las cuentas de grupo */
	$rs_grupos = $obBD_con1->consulta(sentencias_con(339, $obBD_con1->parametros($codigo.'*'.'H'.'*'.'')), $obBD_conexion->conexion);
	$row_rs_grupos = $obBD_con1->registros();
	$total_rs_grupos = $obBD_con1->numregistros();
	do{ 
	  	/* Etiqueta para cuenta de GRUPO HABER */
		$rs_etiquetas_g = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_grupos['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_etiquetas_g = $obBD_con1->registros();
		$total_rs_etiquetas_g = $obBD_con1->numregistros();
	?>
	  <tr>
	    <td class="LetraNegra" align="left"><?php echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_grupos['Pld_Cdc']; ?></td>
	    <td class="LetraNegra" align="left" style="text-transform:uppercase"><?php echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_grupos['Pld_Des']." (".$row_rs_etiquetas_g['Pld_Des'].")"; ?></td>
	    <td class="LetraNegra" align="left" style="text-transform:uppercase">&nbsp;</td>
	    <td class="LetraNegra" align="right">&nbsp;</td>
	    <td class="LetraNegra" align="right">&nbsp;</td>
	    </tr>
	<?Php		
		$Pld_Cod = $row_rs_grupos['Pld_Cod'];
		/* Cargado del detalle HABER*/
		$rs_cuentas = $obBD_con1->consulta(sentencias_con(336,$obBD_con1->parametros($codigo.'*'.'H'.'*'.''.'*'."AND det_plan.Pld_Rec ='$Pld_Cod'")), $obBD_conexion->conexion);
		$row_rs_cuentas = mysqli_fetch_assoc($rs_cuentas);
		$total_rs_cuentas = mysqli_num_rows ($rs_cuentas);
	  do {
	  ?>
	  <tr>
        <td class="LetraNegra" align="left"><?php echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cuentas['Pld_Cdc']; ?></td>
        <td class="LetraNegra" align="left"><?php 
		if ($row_rs_cuentas['Asi_Deh']=='D') { 
			 echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cuentas['Pld_Des']; 
			 }else
			 {  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cuentas['Pld_Des']; }
			 ?></td>
		<td class="LetraNegra" align="left"><?Php echo $row_rs_cuentas['Asi_Glo']; ?></td>
		<td class="LetraNegra" align="right">         
            <?php if ($row_rs_cuentas['Asi_Deh']=='D') { echo number_format($row_rs_cuentas['Asi_Val'],2); 
					$total=$total + $row_rs_cuentas['Asi_Val']; } else { echo '&nbsp'; }?>         </td>
		 <td class="LetraNegra" align="right">
		   <?php if ($row_rs_cuentas['Asi_Deh']=='H') { echo number_format($row_rs_cuentas['Asi_Val'],2); 
		   		} else{ echo '&nbsp'; } ?></td>
      </tr>
	  <?php
	  } while($row_rs_cuentas=mysqli_fetch_assoc($rs_cuentas)); 
	 }while($row_rs_grupos = mysqli_fetch_assoc($rs_grupos));
	  ?>
	  <tr>
        <td colspan="3" class="TITULO_REPORTE"><div align="right"><strong>SUMAN:</strong></div></td>
        <td class="TITULO_REPORTE"><div align="right"><?php echo number_format($total,2); ?></div>          </td>
		<td class="TITULO_REPORTE"><div align="right"><?php echo number_format($total,2); ?></div>		  </td>
      </tr>
    </table>
	<?php
	/* Cargado de los cheques del comprobante */
	$rs_carcheq = $obBD_con1->consulta(sentencias_con(334, $obBD_con1->parametros($row_rs_cabcomp['Com_Cod'])), $obBD_conexion->conexion);
	$row_rs_carcheq = $obBD_con1->registros();
	$total_rs_carcheq = $obBD_con1->numregistros();
	if ($total_rs_carcheq > 0) {
	?>
	<span class="TITULO_REPORTE">CHEQUES DEL COMPROBANTE</span> <br>
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tbody id="contenido">
          <tr>
            <td width="20%" align="center" class="TablaRepCompr">Banco</td>
            <td width="60%" align="center" class="TablaRepCompr">Proveedor</td>
            <td width="10%" align="center" class="TablaRepCompr">N&ordm; Ch.</td>
            <td width="10%" align="center" class="TablaRepCompr">Valor</td>
          </tr>
          <?php
	 do {
	 $fila++;
	 $nombre=explode(" ",$row_rs_carcheq['Prs_Nom']);
	 ?>
          <tr>
            <td class="LetraNegra"><?php echo $row_rs_carcheq['Pld_Des']; ?></td>
            <td class="LetraNegra"><?php echo $row_rs_carcheq['Prs_Ape'].' '.$nombre[0]; ?></td>
            <td align="right" class="LetraNegra"><?php echo $row_rs_carcheq['Che_Num']; ?></td>
            <td align="right" class="LetraNegra"><?php echo number_format($row_rs_carcheq['Che_Val'],2); ?></td>
          </tr>
          <?php } while ($row_rs_carcheq = mysqli_fetch_assoc($rs_carcheq)); ?>
        </tbody>
      </table>
	  <?php }
	?>
	<br>
	<?php switch ($tipo){
		case 1: ?>
	<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" class="Texto_Reporte">
      <tr>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
      </tr>
      <tr>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
      </tr>
      <tr>
        <td valign="top" align="center">__________________<br>        </td>
        <td valign="top" align="center">__________________<br>        </td>
        <td valign="top" align="center">__________________<br>        </td>
        <td valign="top" align="center">__________________</td>
      </tr>
      <tr>
        <td height="19" rowspan="2" align="center" valign="top">EMITIDO POR</td>
        <td rowspan="2" align="center" valign="top">DPTO. CONTABILIDAD</td>
        <td rowspan="2" align="center" valign="top">APROBADO POR</td>
        <td valign="top" align="center"><div align="center">RECIBI CONFORME<br>
        </div></td>
      </tr>
      <tr>
        <td valign="top" align="center"><div align="center"><span >C.I</span>...................................</div></td>
      </tr>
    </table>		 
	<?Php	 
		break;	
		case 2: ?>
	<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" class="Texto_Reporte">
		<tr>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
	    </tr>
		<tr>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
		  <td valign="top" align="center">&nbsp;</td>
	    </tr>
		<tr>
    <td valign="top" align="center">__________________<br>    </td>
    <td valign="top" align="center">__________________<br>    </td>
    <td valign="top" align="center">__________________<br>    </td>
    <td valign="top" align="center">__________________<br>    </td>
    <td valign="top" align="center">__________________</td>
		</tr>
		<tr>
		  <td height="19" rowspan="2" align="center" valign="top">EMITIDO POR</td>
		  <td rowspan="2" align="center" valign="top">DPTO. CONTABILIDAD</td>
		  <td rowspan="2" align="center" valign="top">APROBADO POR</td>
		  <td rowspan="2" align="center" valign="top">AUTORIZADO POR</td>
		  <td valign="top" align="center">
	        <div align="center">RECIBI CONFORME<br>
	        </div></td>
	    </tr>
		<tr>
		  <td valign="top" align="center"><div align="center"><span >C.I</span>...................................</div></td>
	    </tr>
	</table>
	<?php break;
	   case 3: ?>
	<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" class="Texto_Reporte">
      <tr>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
      </tr>
      <tr>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
      </tr>
      <tr>
        <td valign="top" align="center">__________________<br>        </td>
        <td valign="top" align="center">__________________<br>        </td>
        <td valign="top" align="center">__________________<br>        </td>
        <td valign="top" align="center">__________________</td>
      </tr>
      <tr>
        <td height="19" rowspan="2" align="center" valign="top">EMITIDO POR</td>
        <td rowspan="2" align="center" valign="top">DPTO. CONTABILIDAD</td>
        <td rowspan="2" align="center" valign="top">APROBADO POR</td>
        <td valign="top" align="center"><div align="center">RECIBI CONFORME<br>
        </div></td>
      </tr>
      <tr>
        <td valign="top" align="center"><div align="center"><span >C.I</span>...................................</div></td>
      </tr>
    </table>
	<?php break;	
	} //Fin del switch ($tipo){
	?>  
  </tr>
</table>
</body>
</html>
<?Php 
$obBD_conexion->cerrar();
?>