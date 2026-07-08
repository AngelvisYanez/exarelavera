<?php   
/**
* @abstract Reporte de comprobante contable (ingreso, egreso, diario)
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaci�n: 2010-09-06
* Fecha de actualizaci�n  2012-04-29
* Fecha de actualizaci�n  2015-05-07
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
	$row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);
	list($ann, $mes, $dia) = explode('-', $row_rs_cabcomp['Com_Fec']);	
}//FIn del if (isset($codigo))

if(count($row_rs_cabcomp) > 0){	
    switch($row_rs_cabcomp['Tia_Ini'])	{
        case 'I': $etiqueta=isset($array_asien[0])?$array_asien[0]:'';
                  $etiqueta2="RECIBIDO DE:";
				  $pos=120;
            break;
        case 'E': $etiqueta=isset($array_asien[1])?$array_asien[1]:'';
                  $etiqueta2="PAGO REALIZADO A:";
				  $pos=150;
            break;
        case 'D': $etiqueta=isset($array_asien[2])?$array_asien[2]:'';
                  $etiqueta2="DEPOSITARIO:";
				  $pos=120;
             break;
    }
} ?>
<?php //header('Content-Type: text/html; charset=ISO-8859-1'); ?>
<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!doctype html>
<html>
<head>
<title><?php echo $Ses_Sys_Nom; ?></title>
<!--meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"-->
<meta charset="UTF-8">

</head>
<body>
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
      <tr >
        <?php if($Ses_Suc_Cod != 334) { ?>
        <td id="imag" style="line-height:0;" width="10%" rowspan="4"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="100" height="100" /></td>
        <?php } ?>
        <td width="57%" valign="top"><strong><?php echo $row_institucion['Emp_Nom']; ?></strong></td>
        <td width="33%" align="right" valign="top"><strong>COMPROBANTE</strong></td>
      </tr>
      <tr>
         <?php if($Ses_Suc_Cod != 334) { ?>
        <td valign="top"><strong>RUC:</strong> <?php echo $row_institucion['Emp_Ruc']; ?></td>
        <?php } else{?>
          <td></td>
        <?php }?>
        <td align="right" valign="top"><?php echo $etiqueta; ?></td>
      </tr>
      <tr>
         <?php if($Ses_Suc_Cod != 334) { ?>
        <td valign="top"><strong>TELEFONO:</strong> <?php echo $row_institucion['Suc_Te1']; ?></td>
        <?php } else{?>
          <td></td>
        <?php }?>
        <td align="right" valign="top"><strong>No. <?php echo $tip.'-'.$mes.'-'.$num; ?></strong></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="40" valign="top">
    <table class="contenido" width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="20%" valign="top"><strong><?php echo $etiqueta2; ?></strong></td>
        <td>&nbsp;<?php echo substr($row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom'],0,56); ?></td>
        <td align="right" valign="top"><?php echo "<strong>POR:</strong>&nbsp;$".number_format($row_rs_cabcomp['Com_Val'],2); ?></td>
        </tr>
      <tr>
        <td valign="top"><strong>LA CANTIDAD DE:</strong></td>
        <td width="48%"><?php echo strtoupper (num2letras($row_rs_cabcomp['Com_Val'],false)).' USD'; ?></td>
        <td width="32%" align="right" valign="top"><?php echo "<strong>FECHA:&nbsp;</strong>".mes($mes,2).'/'.$dia.'/'.$ann; ?></td>
      </tr>
      <tr>       
        <td colspan="3" valign="top">
        <table class="contenido" width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td><?php echo "<strong>POR CONCEPTO:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_cabcomp['Com_Con']; ?></td>
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
      <?php
  $row_rs_grupos = $obBD_con1->getArrayConsulta(339, $codigo.'*'.'D'.'*'.'', $obBD_conexion);		
foreach ($row_rs_grupos as $row){
/*  Etiqueta para cuenta de GRUPO DEBE */
$row_rs_etiquetas_g = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
$Pld_Cod = $row['Pld_Cod'];
/*  Cargado del detalle DEBE */
$row_rs_cuentas = $obBD_con1->getArrayConsulta(336, $codigo.'*'.'D'.'*'.'ORDER BY Pld_Cdc'.'*'."AND det_plan.Pld_Rec ='$Pld_Cod'", $obBD_conexion);

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
        <td><?php echo $row['Pld_Cdc']; ?></td>
        <td><?php 
		if ($row['Asi_Deh']=='D') { 
			echo substr($row['Pld_Des'],0,38); 
		}else{  
			echo substr($row['Pld_Des'],0,38); 
		}
		 ?></td>
        <td style=""><?php echo $row['Asi_Glo']; ?></td>
        <td align="right"><?php if ($row['Asi_Deh']=='D') { 
			echo number_format($row['Asi_Val'],2); 
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total=$total + round($row['Asi_Val'],2); 
		}else { 
			echo '&nbsp'; 
		} 
		?></td>
        <td align="right"><?php if ($row['Asi_Deh']=='H'){ 
			echo number_format($row['Asi_Val'],2);
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total_h=$total_h + round($row['Asi_Val'],2); 
		}else{ 
			echo '&nbsp'; 
		} 		
		?></td>
      </tr>
<?php }
}

$row_rs_grupos = $obBD_con1->getArrayConsulta(339, $codigo.'*'.'H'.'*'.'', $obBD_conexion);		
foreach ($row_rs_grupos as $row){
	/*  Etiqueta para cuenta de GRUPO DEBE */
	$row_rs_etiquetas_g = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
	$Pld_Cod = $row['Pld_Cod'];
	/*  Cargado del detalle DEBE */
	$row_rs_cuentas = $obBD_con1->getArrayConsulta(336, $codigo.'*'.'H'.'*'.'ORDER BY Pld_Cdc'.'*'."AND det_plan.Pld_Rec ='$Pld_Cod'", $obBD_conexion);

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
        <td><?php echo $row['Pld_Cdc']; ?></td>
        <td><?php 
		if ($row['Asi_Deh']=='D') { 
			echo substr($row['Pld_Des'],0,38);
		}else{  
			echo substr($row['Pld_Des'],0,38);
		}
		 ?></td>
        <td ><?php echo $row['Asi_Glo']; ?></td>
        <td align="right"><?php if ($row['Asi_Deh']=='D') { 
			echo number_format($row['Asi_Val'],2); 
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total=$total + round($row['Asi_Val'],2); 
		}else { 
			echo '&nbsp'; 
		} 
		?></td>
        <td align="right"><?php if ($row['Asi_Deh']=='H'){ 
			echo number_format($row['Asi_Val'],2);
			/* Se uiliza round a 3 decimales para el detalle de cada calculo de las retenciones de renta e iva */
			$total_h=$total_h + round($row['Asi_Val'],2); 
		}else{ 
			echo '&nbsp'; 
		} 
		
		?></td>
     </tr>
<?php
	}
}
?>
	 <tr >
	   <td class="linea2">&nbsp;</td>
	   <td class="linea2">&nbsp;</td>
	   <td class="linea2" align="right">TOTAL:</td>
	   <td class="linea2" align="right"><strong><?php echo number_format($total,2); ?></strong></td>
	   <td class="linea2" align="right"><strong><?php echo number_format($total_h,2); ?></strong></td>
	   </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top">
    <?php
$row_rs_carcheq = $obBD_con1->getArrayConsulta(334, $row_rs_cabcomp['Com_Cod'], $obBD_conexion);
$fila+=20;
if (count($row_rs_carcheq) > 0) { ?>
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
      <?php 
	$fila+=45;
	if(!isset($fila)) $fila=0;
	foreach ($row_rs_carcheq as $row){
		$fila++;
		$nombre=explode(" ",$row['Prs_Nom']);
	 ?>         
      <tr>
        <td><?php echo substr($row['Pld_Des'],0,38); ?></td>
        <td><?php echo substr($row['Prs_Ape'].' '.$nombre[0],0,44); ?></td>
        <td><?php echo $row['Che_Num']; ?></td>
        <td align="right"><?php echo number_format($row['Che_Val'],2); ?></td>
      </tr>
     
	<?php } ?>
      <tr>
        <td colspan="4">&nbsp;</td>
      </tr>
      <tr>
        <td class="linea2" colspan="4">&nbsp;</td>
      </tr>
      </table>

	
<?php }
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
            <?php if($Ses_Suc_Cod != 334){
                    if($Ses_Suc_Cod == 613){
                      echo $row_institucion['Emp_Nom'];
                    } else {
                      echo $row_rs_usuComp['Prs_Ape']." ".$arr_nom[0];
                    }
                  } else{
                      echo $arr_nom[0];
                  }
              ?>
          </div>
        </td>
        <td valign="top">DPTO. CONTABILIDAD</td>
         <td valign="top">REVISADO POR<br> <?php   if($Ses_Emp_Cod == 591  && $row_rs_cabcomp['Tia_Abr']=="DC"   ){ echo "Monica Fernández"; } ?></td>
      <?php if($Ses_Emp_Cod == 591  && $row_rs_cabcomp['Tia_Abr']=="DC"  ){ ?>  <td valign="top">APROBADO POR<br><?php  echo "Sebastian Baquerizo";?></td><?php } ?>
        <!--td valign="top">APROBADO POR<br></td-->
        <td valign="top">RECIBI CONFORME
          <div><?php echo isset($recibi)?$recibi:''; ?></div></td>
      </tr>
	
      <tr>
          <td><strong>Fecha impresion:</strong>  <?php echo date("Y-m-d H:i:s"); ?></td>
      </tr>

    </table></td>
  </tr>
</table>
</body>
</html>
<?php
/** 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();
?>