<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php 
   /**
	* pagina de listado de precios para imprimir (tes_pri_producto_1.0.php) :)
	*
	* @author Jose Cumbicos
	* Ultima Actualización: 28-05-2014
	*
	* Permite visualizar los datos de productos y su visualizacion de imprecion
	*
	* @package tesoreria
	*/
	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_precios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto conexion
 */
$obBD_conexion = new Class_Log_Conexion_pre($Ses_Dat_Dis);

/**
 * objeto para extraer datos
 */
$obBD_con1 =  new Class_Log_Datos_pre;

/**
*   Variables para Encabezado
*/
$Titulo="LISTA DE PRODUCTOS";
$Subtitulo="";
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body class="Cuerpo">
<?php /* Consulta de la cabecera del reporte */
	$row_institucion= $obBD_con1->getRowConsulta(5, $Ses_Suc_Cod, $obBD_conexion);//GetRowConsulta(5,$Ses_Cod_Suc);
?>
<table width="100%" height="907" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td height="58" valign="top"><table width="100%" height="18" border="0" cellpadding="0" cellspacing="0">
      <tr align="center">&nbsp;
        <td height="18" align="center" ><?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod,$Titulo,$Subtitulo,$obBD_conexion)?></td>
      </tr>
    </table></td>
  </tr>
  <tr valign="top">
    <td valign="top">
	<br>
    <table width="100%" height="121"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td height="117"><div align="center">
		  <label class="TITULO_REPORTE"><?Php echo $nivel[$i]; ?></label>
          <?php
    	if($page == 'TODOS')
		{
			$Arr_Producto = $obBD_con1->getArrayConsulta(2, $Tpv_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);//EjecutarConsulta(2,$Tpv_Cod);
		}
		else
		{
			$Arr_Producto = $obBD_con1->getArrayConsulta(3, $Tpv_Cod.'*'.$page.'*'.$Ses_Suc_Cod, $obBD_conexion);//EjecutarConsulta(3,$Tpv_Cod.'*'.$page);
		}
	?>
          <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
        <thead>
          <tr class="TITULO_REPORTE">
            <th width="4%" bgcolor="#CCCCCC">C&oacute;d. Int.</th>
            <th width="10%" bgcolor="#CCCCCC">Categor&iacute;a</th>
            <th width="29%" bgcolor="#CCCCCC">Descripci&oacute;n Larga</th>
            <th width="20%" bgcolor="#CCCCCC">Descripci&oacute;n Corta </th>
            <th width="10%" bgcolor="#CCCCCC">Marca</th>
            <th width="9%" bgcolor="#CCCCCC">Stock</th>
            <th width="9%" bgcolor="#CCCCCC">PRECIO</th>
            <th width="9%" bgcolor="#CCCCCC">pvp</th>
          </tr>
          </thead>
          <tbody>
          <?php 
		     $filas = 0;
		  foreach($Arr_Producto as $row)
		  {
		     $filas++;
		  ?>
          <tr class="Texto_Reporte">
            <td align="center" class="LetraNegra" width="4%"><?Php echo $row['Pro_Cod'];?></td>
            <td class="LetraNegra"><?Php echo $row['Cat_Des'];?></td>
            <td class="LetraNegra"><?Php echo $row['Ite_Lar']." ".$row['Pro_Obs'];?></td>
            <td class="LetraNegra"><?Php echo $row['Ite_Cor']." ".$row['Pro_Obs'];?></td>
            <td class="LetraNegra"><?Php echo $row['Mar_Des'];?></td>
            <td class="LetraNegra" align="right"><?Php echo $row['Stk_Can'];?>&nbsp;</td>
            <td class="LetraNegra" align="right"><?Php echo number_format($row['Pre_Pvp'],2);?>&nbsp;</td>
            <td class="LetraNegra" align="right"><?Php echo number_format($row['Pre_Pvp'] + ($row['Pre_Pvp'] * $row['Iva_Por'])/100,2);?></td>
          </tr>
          <?php }?>
          </tbody>
    </table>
		  <br>
        </div></td>
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