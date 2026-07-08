<?php 
/*Alias:	codigo barros activos
Descripci�n: Reporte de codigo de barras para los Activos por departamento
Fecha de actualizaci�n:	2011-08-2
Desarrollador:	Mauricio Fierro
MULTIEMPRESA : 
*/	

//require_once '../../administrador/LOGICA/seguridad.php';
require_once('../LOGICA/act_log_campos_det.php');
require_once '../../Librerias/procedimientos/almacenados_standar.php';	

/* Creacion del Objeto de conexion */  
$obBD_conexion = new Class_Log_Conexion_Con;

/* Creacion del Objeto de datos */  
$obBD_con1 =  new Class_Log_Datos_Con; 
?>
	  
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
<table width="100%"  height="232" border="0" align="center">
  <tr>
    <td height="78" align="center" valign="top"><br>
	<table width="516" border="0" align="center">
      <tr>
        <td valign="top" align="center">
	<?php 	/* consulto todos los registros*/
	$rs_ite_pro1= $obBD_con1->consulta(sentencias_con(637, $obBD_con1->parametros($cadena)), $obBD_conexion->conexion);
    $row_rs_ite_pro1 = $obBD_con1->registros();
	$total_rs_ite_pro1 =  $obBD_con1->numregistros();
	
	/* consulto todos los registros paginados*/
	$rs_ite_pro= $obBD_con1->consulta(sentencias_con(638, $obBD_con1->parametros($cadena.'*'.$inicio.'*'.$registros)), $obBD_conexion->conexion);
    $row_rs_ite_pro = $obBD_con1->registros();
	$total_rs_ite_pro =  $obBD_con1->numregistros();
		$resultados = $rs_ite_pro;
		$total_registros =$total_rs_ite_pro1; 
		$indicadorfila=0;
			?>
	<form name="form2" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
	  <table width="200" border="1"  bordercolor="#000000">
        <tr>
          <?php  do{ 
		   ?>
          <td><table width="200" border="0" cellpadding="0" cellspacing="0">
              	<tr>
                	<td colspan="2" align="center"><?php  $varcode=123456789000; include("../../Librerias/barcode/generadorbarras.php"); ?></td>
              	</tr>
              	<tr >
              	  <td width="91" class="Etiqueta1"><div align="left">Departamento:</div></td>
              	  <td width="109" class="LetraNegra"><?php echo $row_rs_ite_pro["Dep_Des"]; ?></td>
            	  </tr>
              	<tr >
              	  <td class="Etiqueta1"><div align="left">Secci&oacute;n:</div></td>
              	  <td class="LetraNegra"><?php echo $row_rs_ite_pro["Sec_Des"]; ?></td>
            	  </tr>
              	<tr>
              	  <td class="Etiqueta1"><div align="left">Nombre Activo:</div></td>
              	  <td class="LetraNegra"><?php echo $row_rs_ite_pro['Act_Des']; ?></td>
            	  </tr>
              	<tr>
                	<td class="Etiqueta1"><div align="left">Secuencial:</div></td>
                	<td class="LetraNegra"><?php echo $row_rs_ite_pro['Act_Sec']; ?></td>
              	</tr>
          </table></td>
          <?php 
		   if($indicadorfila==4)
		   {
		   echo "</tr>";
		   echo "<tr>";
		   $indicadorfila=0;
		   }
		   else
		   {
		   $indicadorfila=$indicadorfila+1;	
		   }
		   }while ($row_rs_ite_pro = $obBD_con1->fetch_assoc($rs_ite_pro)) ; ?>
        </tr>
      </table>
	</form>
	</td>
</tr>
</table></td>
  </tr>
</table>
	</table></td>
      </tr>
      </table>
</body>
</html>
<?Php
@$obBD_con1->free_result($rs_ite_pro);
@$obBD_con1->free_result($rs_ite_pro1);	
@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>
