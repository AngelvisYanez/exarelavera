<?php 
/**
* @abstract Reporte total de comprobantes contables (ingreso, egreso, diario)
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización: 2010-09-06
* Fecha de actualización  2012-05-05
* @author Lewis Chimarro
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compr.php');
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

/* 
* CRITERIO = tipo - tabla - campo 
*/
$criterio=explode("*",$Com_Tip);
/* 
* SQL = tabla - tipo - campo 
*/
switch ($Com_Aut){
	case 'T':
		$generacion = "";
	break;
	case 'M':
		$generacion = " AND comprobantes.Com_Gen = 'M'";
	break;
	case 'A':
		$generacion = " AND comprobantes.Com_Gen = 'A'";
	break;
}
$row_rs_comfec = $obBD_con1->getArrayConsulta(335, $criterio[1].'*'.$criterio[0].'*'.$criterio[2].'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*'.$option.'*'.$generacion.'*'.$Ses_Emp_Cod, $obBD_conexion);
$comfec = current($row_rs_comfec);
?>
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
</head>
<body>
<table width="100%"  border="0" align="center">
  <tr>
    <td align="center" valign="top">
    &nbsp;<?php
		$titulo = "<span class='TITULO_REPORTE_2'>Listado de Comprobantes de ".$comfec['Tia_Des']."</span>";
		 $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, " ", $obBD_conexion); ?>&nbsp;
    </td>
  </tr>
  <tr valign="top">
    <td valign="top">
    <table width="298" border="0" align="center" cellpadding="0" cellspacing="0" class="Texto_Reporte">
      <tr>
        <td width="86" align="right" class="Texto_Reporte"><div align="right">Desde:</div></td>
        <td width="79" class="Texto_Reporte">&nbsp;<?Php echo $txt_fec_ini?></td>
        <td width="38" align="right" class="Texto_Reporte"><div align="right">Hasta:</div></td>
        <td width="95" class="Texto_Reporte">&nbsp;<?Php echo $txt_fec_fin?></td>
      </tr>
    </table>
    <table width="175" border="0" align="center" cellpadding="0" cellspacing="0" class="Texto_Reporte">
      <tr>
        <td width="97" align="right" class="Texto_Reporte"><div align="right">Comprobantes:</div></td>
        <td width="78" class="Texto_Reporte">&nbsp;
          <?php  if ($option == "A"){ echo "Activos"; } else { echo "Anulados"; } ?></td>
        </tr>
    </table>
    <br>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
      <thead>
        <tr>
          <?Php if(isset($Niv_Cod[1])){ ?>
          <th width="8%" class="TablaRepCompr" align="center">No. Int </th>
          <?Php } ?>
          <th width="8%" class="TablaRepCompr" align="center">Generaci&oacute;n</th>
          <th width="8%" class="TablaRepCompr" align="center">No. Compr </th>
          <th width="12%" class="TablaRepCompr" align="center">Fecha</th>
          <th class="TablaRepCompr" align="center">Proveedor/Cliente</th>
          <?Php if(isset($Niv_Cod[2])){ ?>
          <th class="TablaRepCompr" align="center">Concepto</th>
          <?Php } ?>
          <th width="10%" class="TablaRepCompr" align="center">Valor</th>
        </tr>
      </thead>
      <tbody>
        <? 
	if (count($row_rs_comfec) > 0)
	{ 
		$i=0;
		$total_fin = 0;	
		//$cont_compr = 0;
		foreach ($row_rs_comfec as $row)
		{ 	
			$i++;	
	?>
        <tr>
          <?Php if(isset($Niv_Cod[1])){ ?>
          <td align="center"><? echo $row['Com_Cod']; ?></td>
          <?Php } ?>
          <td align="center"><?Php 
		  /* 
		  * Control para mostrar si el comprobante es automatico o manual 
		  */	
		  if ($row['Com_Gen'] == 'A')
		  {
		  		echo "Autom&aacute;tico";
		  }
		  else
		  {
		  		echo "Manual";
		  }
		  ?></td>
          <td align="center"><? 
		  list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
		  echo $mes.'-'.$row['Com_Num']; ?></td>
          <td align="center"><? echo $row['Com_Fec']; ?></td>
          <td><? echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?></td>
          <?Php if(isset($Niv_Cod[2])){ ?>
          <td><? echo $row['Com_Con']; ?></td>
          <?Php } ?>
          <td align="right"><? echo $row['Com_Val']; ?></td>
        </tr>
        <?  $total_fin = $total_fin + $row['Com_Val'];
		}//Fin del if ($mostrar == $Com_Aut)
	 }//Fin del if ($total_rs_comfec >0)
	else
	{ ?>
        <tr>
          <td>&nbsp;</td>
          <?Php if(isset($Niv_Cod[1])){ ?>
          <td>&nbsp;</td>
          <?php } ?>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td><?php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
          <?Php if(isset($Niv_Cod[2])){ ?>
          <td>&nbsp;</td>
          <?php } ?>
          <td>&nbsp;</td>
        </tr>
        <?php	
	}//Fin del if ($total_rs_comfec >0)
	?>
      </tbody>
    </table>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="86%" align="right" class="TablaRepCompr">TOTAL: </td>
        <td width="10%" align="right" class="TablaRepCompr"><?PHP echo formato_numero($total_fin,2,4); ?></td>
      </tr>
    </table></td>
  </tr>
  <tr valign="top">
    <td valign="top" align="center"><?php echo $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
  </tr>
</table>
</body>
</html>
<?Php	
$obBD_conexion->cerrar();
?>	