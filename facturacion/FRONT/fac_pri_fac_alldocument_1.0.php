<?
// BLOQUE DE ARCHIVOS REQUERIDOS
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');
require_once('../../contabilidad/LOGICA/con_log_compr.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion  */
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Comt;


/* Creacion del Objeto de conexion */
$obBD_conexion1 = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con2 =  new Class_Log_Datos_Con;


$hoy = date("d-m-Y");
$mes = date("m");
$fecha = explode('-', $hoy);

/* Componente que muestra el detalle de las compras */
if (isset($codigo)) {
  /* Cargado de la cabecera  */
  //$row_rs_cabcomp = $obBD_con2->getRowConsulta(333, $tabla.'*'.$codigo.'*'.$tipo.'*'.$Pec_Cod.'*'.$campo, $obBD_conexion1);	
  $row_rs_cabcomp = $obBD_con2->getRowConsulta(389, $codigo, $obBD_conexion1);
  $row_rs_usuComp = $obBD_con2->getRowConsulta(365, $row_rs_cabcomp['Usu_Cod'], $obBD_conexion1);
  $row_institucion = $obBD_con2->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion1);
  list($ann, $mes, $dia) = explode('-', $row_rs_cabcomp['Com_Fec']);
} //FIn del if (isset($codigo))

if (count($row_rs_cabcomp) > 0) {
  switch ($row_rs_cabcomp['Tia_Ini']) {
    case 'I':
      $etiqueta = isset($array_asien[0]) ? $array_asien[0] : '';
      $etiqueta2 = "RECIBIDO DE:";
      $pos = 120;
      break;
    case 'E':
      $etiqueta = isset($array_asien[1]) ? $array_asien[1] : '';
      $etiqueta2 = "PAGO REALIZADO A:";
      $pos = 150;
      break;
    case 'D':
      $etiqueta = isset($array_asien[2]) ? $array_asien[2] : '';
      $etiqueta2 = "DEPOSITARIO:";
      $pos = 120;
      break;
  }
}
?>

<HTML>

<HEAD>
  <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
  <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
</HEAD>

<BODY>

  <p>
    <?Php
    /* Componente que muestra el detalle de las compras  */
    if (isset($com_codigo)) {
      $rs_infoEmpresa = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);
      $resultados = explode('*', $obBD_con1->calculosCompraIce($com_codigo, $obBD_conexion));
      $rs_usuVendedor = $obBD_con1->getRowConsulta(1073, $com_codigo, $obBD_conexion);
      $rs_info = $obBD_con1->getRowConsulta(1071, $com_codigo, $obBD_conexion);
      $rs_tipoCompr = $obBD_con1->getRowConsulta(1076, $rs_info['Tic_Cod'], $obBD_conexion);
      $rs_detalle = $obBD_con1->getArrayConsulta(723, $com_codigo, $obBD_conexion);
      $row = current($rs_detalle);
      $tot_rs_detalle = count($rs_detalle);
      $Obs = $row['Cop_Obs'];
    ?>
  </p>
  <table width="95%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td width="50%" valign="bottom" class="TEXTO_NORMAL_UPPER_18" style="font-size:14px">
        <div align="left"><strong><? echo $rs_infoEmpresa['Emp_Nom']; ?></strong></div>
      </td>
      <td width="50%">&nbsp;</td>
    </tr>
    <tr>
      <div align="center"> DETALLE DE LA COMPRA </div>
      <td width="50%" valign="bottom" class="TITULO_REPORTE">
        <div align="left"> detalle de compras</div>
      </td>
      <td align="right" class="Texto_Listados" style="font-size:11px">
        <div align="right"><? echo $hoy; ?></div>
      </td>
    </tr>
    <tr>
      <td height="19" colspan="2">
        <hr />
      </td>
    </tr>
  </table>

  <table width="95%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td class="Texto_Listados" style="font-size:11px"><strong>Proveedor</strong>:</td>
        <td colspan="5" class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><? echo $rs_info['Prs_Ape'].' '.$rs_info['Prs_Nom'];?></span></td>
  </tr>
      <tr>
        <td class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><strong>C&eacute;dula/R.U.C.:&nbsp;</strong></span></td>
        <td class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><? echo $rs_info['Prs_Ced'];?>&nbsp;</span></td>
        <td class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Tipo:&nbsp;</strong></div></td>
        <td class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><? echo $rs_tipoCompr['Tic_Des'];?></span></td>
        <td class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Creado el:&nbsp;</strong></div></td>
        <td class="Texto_Listados" style="font-size:11px"><? echo $rs_info['Cop_Sys']; ?>&nbsp;</td>
      </tr>
      <tr>
        <td width="10%" class="Texto_Listados" style="font-size:11px"><strong>No. Docto:</strong></td>
        <td width="18%" class="Texto_Listados" style="font-size:11px"><? echo $rs_info['Cop_Num'];?>&nbsp;</td>
        <td width="6%" class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Autorizaci&oacute;n:&nbsp;</strong></div></td>
        <td width="17%" class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><? echo $rs_info['Cop_Aut'];?></span></td>
        <td width="14%" class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Emisi&oacute;n:&nbsp;</strong></div></td>
        <td width="35%" class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><? echo $rs_info['Cop_Fec'];?></span>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        <td width="14%" class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Usuario:</strong></div></td>
        <td width="35%" class="Texto_Listados" style="font-size:11px">&nbsp;<? echo $rs_usuVendedor['Prs_Ape'].' '.$rs_usuVendedor['Prs_Nom']; ?></td>
      </tr>
</table>


  <table width="95%" border="0" cellpadding="0" cellspacing="0">
    <thead>
      <tr height="25" class="TablaRepCompr">
        <th colspan="4" align="left" class="TablaRepComprBottom">&nbsp;</th>
      </tr>
      <tr height="25" class="TablaRepCompr">
        <th width="11%" align="left" valign="middle" class="TablaRepComprBottom">Cantidad</th>
        <th width="49%" align="left" valign="middle" class="TablaRepComprBottom">Descripci&oacute;n</th>
        <th width="19%" align="right" valign="middle" class="TablaRepComprBottom">P. Unitario </th>
        <th width="21%" align="right" valign="middle" class="TablaRepComprBottom">Importe</th>
      </tr>
    </thead>
    <tbody class="Fondo">
      <?Php
      foreach ($rs_detalle as $row_rs_detalle) {
        if (strlen($row_rs_detalle['Cop_Pro']) > 30) {
          $CopPro = substr($row_rs_detalle['Cop_Pro'], 0, 30);
          $CopPro = $CopPro . '...';
        } else {
          $CopPro = $row_rs_detalle['Cop_Pro'];
        }
      ?>

        <tr class="Texto_Listados" height="20" style="font-size:12px">
          <td height="20" align="left"><?Php echo $row_rs_detalle['Cop_Can']; ?></td>
          <td title="<?Php echo $row_rs_detalle['Cop_Pro']; ?>"><?Php echo $CopPro; ?></td>
          <td height="20" align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Pru'], 2, 4); ?></td>
          <td height="20" align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Imp'], 2, 4); ?></td>
        </tr>
      <?Php } ?>
      <tr>
        <td height="64" align="left">&nbsp;</td>
        <td height="64" align="left">&nbsp;</td>
        <td height="64" align="left">&nbsp;</td>
        <td height="64" align="left">&nbsp;</td>
      </tr>
      <tr>
        <td colspan="4" align="left" class="TablaRepComprBottom">&nbsp;</td>
      </tr>

      <tr>
        <td height="60" colspan="2" align="left" valign="top" class="Texto_Listados" style="font-size:12px"><strong>Observaci&oacute;n</strong>:<?Php echo "&nbsp;" . $Obs; ?></td>
        <td colspan="2" align="left" class="Texto_Listados">
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td width="64%" align="left" class="Texto_Listados" style="font-size:12px">
                <div align="right">Subtotal:</div>
              </td>
              <td width="36%" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong><?Php echo formato_numero($resultados[0], 2, 1); ?></strong></div>
              </td>
            </tr>
            <tr>
              <td align="left" class="Texto_Listados" style="font-size:12px">
                <div align="right">Tarifa 0%:</div>
              </td>
              <td align="right" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong><?Php echo formato_numero($resultados[1], 2, 1); ?></strong></div>
              </td>
            </tr>
            <tr>
              <td align="left" class="Texto_Listados" style="font-size:12px">
                <div align="right">Tarifa 12%:</div>
              </td>
              <td align="right" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong><?Php echo formato_numero($resultados[2], 2, 1); ?></strong></div>
              </td>
            </tr>
            <tr>
              <td align="left" class="Texto_Listados" style="font-size:12px">
                <div align="right">Iva:</div>
              </td>
              <td align="right" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong><?Php echo formato_numero($resultados[3], 2, 1); ?></strong></div>
              </td>
            </tr>
            <tr>
              <td align="left" class="Texto_Listados" style="font-size:12px">
                <div align="right">Ice:</div>
              </td>
              <td align="right" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong><?php echo formato_numero($resultados[6], 2, 1); ?></strong></div>
              </td>
            </tr>
            <tr>
              <td align="left" class="Texto_Listados" style="font-size:12px">
                <div align="right">Descuento:<strong> <?Php echo isset($Des_Gen) ? $Des_Gen : 0; ?></strong></div>
              </td>
              <td align="right" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong><?Php echo formato_numero($resultados[4], 2, 1); ?></strong></div>
              </td>
            </tr>
            <tr>
              <td align="left" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong>Total</strong>:</div>
              </td>
              <td align="right" class="Texto_Listados" style="font-size:12px">
                <div align="right"><strong><?php echo formato_numero($resultados[5], 2, 1); ?></strong></div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </tbody>
  </table>
  <br>
  <?

      $rs_retCod = $obBD_con1->getRowConsulta(718, $com_codigo, $obBD_conexion);
      $rs_confiEmp = $obBD_con1->getRowConsulta(1049, $Ses_Suc_Cod, $obBD_conexion);
      $rs_detalle = $obBD_con1->getArrayConsulta(381, $rs_retCod['Ret_Cod'], $obBD_conexion);
      $total_rs_detalle = count($rs_detalle);
      $impTotal = 0;
      if ($total_rs_detalle != 0 && 1 == 2) {
  ?>
    <strong>Retenci&oacute;n</strong>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="8%" class="Etiqueta1">No. Doc:</td>
        <td width="16%" class="LetraNegra">&nbsp;<?Php echo str_pad($rs_retCod['Ret_Num'], 9, "0", STR_PAD_LEFT); ?></td>
        <td width="8%" class="Etiqueta1">Emitido:</td>
        <td width="16%" class="LetraNegra">&nbsp;<?Php echo $rs_retCod['Ret_Fec']; ?></td>
        <td width="9%" class="Etiqueta1">Autorizaci&oacute;n:</td>
        <td width="67%" class="LetraNegra">&nbsp;
          <?Php
          if ($rs_confiEmp['Cof_Gce'] == 'N') {
            echo $rs_retCod['Aut_Sri'];
          } else {
            echo $rs_retCod['Ret_Sri'];
          }
          ?>
        </td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <thead>
        <tr height="25" class="Cabecera1">
          <th width="11%" height="20" align="center">C&oacute;d. Imp. </th>
          <th width="41%" height="20" align="center">Descripci&oacute;n</th>
          <th width="10%" height="20" align="center">Impuesto</th>
          <th width="12%" height="20" align="center">Base</th>
          <th width="13%" align="center">% Retenci&oacute;n </th>
          <th width="13%" height="20" align="center">Valor Retenido </th>
        </tr>
      </thead>
      <tbody class="Fondo">
        <? foreach ($rs_detalle as $row_rs_detalle) { ?>
          <tr height="20">
            <td align="center"><?Php echo $row_rs_detalle['Ren_Sri']; ?></td>
            <td><?Php echo $row_rs_detalle['Ren_Con']; ?></td>
            <td align="center"><?Php echo $row_rs_detalle['Ren_Ret']; ?></td>
            <td align="center"><?Php echo formato_numero($row_rs_detalle['Ret_Bas'], 2, 4); ?></td>
            <td align="center"><?Php echo $row_rs_detalle['Ren_Por']; ?></td>
            <td align="right"><?Php $impTotal = $impTotal + $row_rs_detalle['Val_Ret'];
                              echo formato_numero($row_rs_detalle['Val_Ret'], 2, 4); ?></td>
          </tr>
        <? } ?>
      </tbody>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="25%">&nbsp;</td>
        <td width="25%">&nbsp;</td>
        <td width="37%">&nbsp;</td>
        <td width="13%" align="right"><strong>Total:&nbsp;&nbsp;<? echo formato_numero($impTotal, 2, 4); ?></strong></td>
      </tr>
    </table>
    <br />
  <? } ?>

  <? //echo barra_estado($tot_rs_detalle);
  ?>
<?Php
    } //FIn del if (isset($com_codigo))
    else {
      echo error_alerta("<< Error de componente >> <br>Descripci&oacute;n: No se ha definido la Propiedad: com_codigo<br>
                                        com_codigo: Variable que contiene el codigo interno de la factura de compra", 2);
    } //FIn del else if (isset($com_codigo))
?>
<!-- VISUALIZACION DEL ASIENTO CONTABLE  Y LA RENTENCION -->
<style type="text/css">
.linea {
    border-bottom: 1px solid black;
    border-top: 1px solid black;
    border-collapse: collapse;
}
.linea2 {
     border-top: 1px solid black;   
    border-collapse: collapse;
}
.titulo{
	font-family: Verdana, Geneva, sans-serif;
	font-size:12px;

}
.contenido{
	font-family:Verdana, Geneva, sans-serif;
	font-size:12px;
}

table { border-collapse: collapse; }

#imag{
   padding: 0; 
   margin: 0;
}


</style>

<?php

$tip = $row_rs_cabcomp['Tia_Abr'];
$num = $row_rs_cabcomp['Com_Num'];
$etiqueta = $row_rs_cabcomp['Tia_Des'];
$total=0;
$total_h=0;
$fila=0;
//$titulo = "<span class='titulo'><strong>Comprobante de $etiqueta N</strong></span><span class='titulo'>o</span><span class='titulo'><strong> $tip-$mes-$num</strong></span>";

//echo floor($totcad/30);
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td height="75" valign="top">
    <table class="titulo" width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <?php if($Ses_Suc_Cod != 334) { ?>
        <td id="imag" style="line-height:0;" width="10%" rowspan="4"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="100" height="100" /></td>
        <?php } ?>
        <div align="center"> ASIENTO CONTABLE </div>
        <td width="57%" valign="top"><strong><? echo $row_institucion['Emp_Nom']; ?></strong></td>
        <td width="33%" align="right" valign="top"><strong>COMPROBANTE</strong></td>
      </tr>
      <tr>
         <?php if($Ses_Suc_Cod != 334) { ?>
        <td valign="top"><strong>RUC:</strong> <? echo $row_institucion['Emp_Ruc']; ?></td>
        <?php } else{?>
          <td></td>
        <?php }?>
        <td align="right" valign="top"><? echo $etiqueta; ?></td>
      </tr>
      <tr>
         <?php if($Ses_Suc_Cod != 334) { ?>
        <td valign="top"><strong>TELEFONO:</strong> <? echo $row_institucion['Suc_Te1']; ?></td>
        <?php } else{?>
          <td></td>
        <?php }?>
        <td align="right" valign="top"><strong>No. <? echo $tip.'-'.$mes.'-'.$num; ?></strong></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="40" valign="top">
    <table class="contenido" width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="20%" valign="top"><strong><? echo $etiqueta2; ?></strong></td>
        <td>&nbsp;<? echo substr($row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom'],0,56); ?></td>
        <td align="right" valign="top"><? echo "<strong>POR:</strong>&nbsp;$".number_format($row_rs_cabcomp['Com_Val'],2); ?></td>
        </tr>
      <tr>
        <td valign="top"><strong>LA CANTIDAD DE:</strong></td>
        <td width="48%"><? echo strtoupper (num2letras($row_rs_cabcomp['Com_Val'],false)).' USD'; ?></td>
        <td width="32%" align="right" valign="top"><?php echo "<strong>FECHA:&nbsp;</strong>".mes($mes,2).'/'.$dia.'/'.$ann; ?></td>
      </tr>
      <tr>       
        <td colspan="3" valign="top">
        <table class="contenido" width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td><? echo "<strong>POR CONCEPTO:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cabcomp['Com_Con']; ?></td>
            </tr>
          </table></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="90">
    <table class="contenido" width="100%" border="0" cellpadding="0" cellspacing="0" >
      <tr>
        <td class="linea" width="14%" align="left">C&Oacute;DIGO</td>
        <td class="linea" width="37%">DESCRIPCI&Oacute;N</td>
        <td class="linea" width="25%">GLOSA</td>
        <td class="linea" width="13%" align="right">DEBE</td>
        <td class="linea" width="11%" align="right">HABER</td>
      </tr>
      <?
  $row_rs_grupos = $obBD_con2->getArrayConsulta(339, $codigo.'*'.'D'.'*'.'', $obBD_conexion1);		
foreach ($row_rs_grupos as $row){
/*  Etiqueta para cuenta de GRUPO DEBE */
$row_rs_etiquetas_g = $obBD_con2->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion1);
$Pld_Cod = $row['Pld_Cod'];
/*  Cargado del detalle DEBE */
$row_rs_cuentas = $obBD_con2->getArrayConsulta(336, $codigo.'*'.'D'.'*'.'ORDER BY Pld_Cdc'.'*'."AND det_plan.Pld_Rec ='$Pld_Cod'", $obBD_conexion1);

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
      <tr>
        <td><? echo $row['Pld_Cdc']; ?></td>
        <td><? 
		if ($row['Asi_Deh']=='D') { 
			echo substr($row['Pld_Des'],0,38); 
		}else{  
			echo substr($row['Pld_Des'],0,38); 
		}
		 ?></td>
        <td style=""><?Php echo $row['Asi_Glo']; ?></td>
        <td align="right"><? if ($row['Asi_Deh']=='D') { 
			echo number_format($row['Asi_Val'],2); 
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total=$total + round($row['Asi_Val'],2); 
		}else { 
			echo '&nbsp'; 
		} 
		?></td>
        <td align="right"><? if ($row['Asi_Deh']=='H'){ 
			echo number_format($row['Asi_Val'],2);
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total_h=$total_h + round($row['Asi_Val'],2); 
		}else{ 
			echo '&nbsp'; 
		} 		
		?></td>
      </tr>
<? }
}

$row_rs_grupos = $obBD_con2->getArrayConsulta(339, $codigo.'*'.'H'.'*'.'', $obBD_conexion1);		
foreach ($row_rs_grupos as $row){
	/*  Etiqueta para cuenta de GRUPO DEBE */
	$row_rs_etiquetas_g = $obBD_con2->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion1);
	$Pld_Cod = $row['Pld_Cod'];
	/*  Cargado del detalle DEBE */
	$row_rs_cuentas = $obBD_con2->getArrayConsulta(336, $codigo.'*'.'H'.'*'.'ORDER BY Pld_Cdc'.'*'."AND det_plan.Pld_Rec ='$Pld_Cod'", $obBD_conexion1);

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
	<tr>
        <td><? echo $row['Pld_Cdc']; ?></td>
        <td><? 
		if ($row['Asi_Deh']=='D') { 
			echo substr($row['Pld_Des'],0,38);
		}else{  
			echo substr($row['Pld_Des'],0,38);
		}
		 ?></td>
        <td ><?Php echo $row['Asi_Glo']; ?></td>
        <td align="right"><? if ($row['Asi_Deh']=='D') { 
			echo number_format($row['Asi_Val'],2); 
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total=$total + round($row['Asi_Val'],2); 
		}else { 
			echo '&nbsp'; 
		} 
		?></td>
        <td align="right"><? if ($row['Asi_Deh']=='H'){ 
			echo number_format($row['Asi_Val'],2);
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total_h=$total_h + round($row['Asi_Val'],2); 
		}else{ 
			echo '&nbsp'; 
		} 
		
		?></td>
     </tr>
<?
	}
}
?>
	 <tr >
	   <td class="linea2">&nbsp;</td>
	   <td class="linea2">&nbsp;</td>
	   <td class="linea2" align="right">TOTAL:</td>
	   <td class="linea2" align="right"><strong><? echo number_format($total,2); ?></strong></td>
	   <td class="linea2" align="right"><strong><? echo number_format($total_h,2); ?></strong></td>
	   </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top">
    <?
/* Cargado de los cheques del comprobante */
$row_rs_carcheq = $obBD_con2->getArrayConsulta(334, $row_rs_cabcomp['Com_Cod'], $obBD_conexion1);
$fila+=20;
if (count($row_rs_carcheq) > 0) 
{
?>
    <table class="contenido" width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td height="27" colspan="4" align="left" valign="middle">CHEQUES DEL COMPROBANTE</td>
      </tr>
      <tr>
        <td class="linea" width="36%">BANCO</td>
        <td class="linea" width="41%">PPROVEEDOR</td>
        <td class="linea" width="14%">CHEQUE</td>
        <td class="linea" width="9%" align="right">VALOR</td>
      </tr>
      <? 
	$fila+=45;
	if(!isset($fila)) $fila=0;
	foreach ($row_rs_carcheq as $row){
		$fila++;
		$nombre=explode(" ",$row['Prs_Nom']);
	 ?>         
      <tr>
        <td><? echo substr($row['Pld_Des'],0,38); ?></td>
        <td><? echo substr($row['Prs_Ape'].' '.$nombre[0],0,44); ?></td>
        <td><? echo $row['Che_Num']; ?></td>
        <td align="right"><? echo number_format($row['Che_Val'],2); ?></td>
      </tr>
     
	<? }?>
      <tr>
        <td colspan="4">&nbsp;</td>
      </tr>
      <tr>
        <td class="linea2" colspan="4">&nbsp;</td>
      </tr>
      </table>

	
<? }
$nombre=  explode(' ', $row_rs_cabcomp['Prs_Nom']);
$recibi=$row_rs_cabcomp['Prs_Ape'].' '.$nombre[0];
$tip=1;
$fila=$fila+50;
$arr_nom=explode(' ',$row_rs_usuComp['Prs_Nom']);
?>    
    </td>
  </tr>
  <tr>
    <td height="147"><table class="contenido" width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>_____________________</td>
        <td>_____________________</td>
        <td>_____________________</td>
        <td>_____________________</td>
      </tr>
      <tr>
        <td valign="top">EMITIDO POR
          <div>
            <?Php  if($Ses_Suc_Cod != 334){
                    echo $row_rs_usuComp['Prs_Ape']." ".$arr_nom[0];
                    }
                    else{
                       echo $arr_nom[0];
                    }
              ?>
          </div>
        </td>
        <td valign="top">DPTO. CONTABILIDAD</td>
        <td valign="top">APROBADO POR<br></td>
        <td valign="top">RECIBI CONFORME
          <div><? echo isset($recibi)?$recibi:''; ?></div></td>
      </tr>
	
      <tr>
          <td><strong>Fecha impresión:</strong>  <?php echo date("Y-m-d H:i:s"); ?></td>
      </tr>

    </table></td>
  </tr>
</table>


</BODY>

</HTML>
<?Php
/* liberar conexiones en la BD */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
// $obBD_conexion1->cerrar();
?>