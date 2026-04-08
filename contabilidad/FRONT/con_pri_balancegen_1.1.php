<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripci�n: Permite imprimir el balance general
* Fecha de actualizaci�n:	2012-10-06
* Desarrollador:	Lewis Chimarro 
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_balances.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Configuración para aumentar el tiempo de ejecución del script
 * Útil cuando se procesan grandes volúmenes de datos contables
 */
set_time_limit(300); // 5 minutos (300 segundos)
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M'); // Aumentar también el límite de memoria si es necesario

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
?>
<HTML>
	<HEAD>
		<TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>

    <meta charset="UTF-8">
		<?php require_once("../../mascaras/model1/estilos/print.php"); ?>
		<meta http-equiv="Content-Type" content="text/html;">
        <style type="text/css">
			.LetraNegra {
	font: normal 11px "Trebuchet MS", Arial, Helvetica, sans-serif;	
	color: #000000;
			}
			
			.LetraPie {
				font:Verdana, Geneva, sans-serif;
				font-size: 8px;			    		
				color:#333;
			}
		</style>
	</HEAD>
<BODY>
     <table width="590" border="0" align="center" cellpadding="0" cellspacing="0">
	 <tr class="Titulos3">
	   <td width="100%" colspan="2" align="center"><?php 	   
	   $titulo = "<strong><span class='TITULO_REPORTE_2'>Estado de Situacion Financiera</span></strong>";
		$subtitulo = "<strong><span class='TITULO_REPORTE'>Desde el ".$txt_fec_ini." Hasta el ".$txt_fec_fin." </span></strong>";
	   $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion);  ?></td>
	   </tr>
	 </table>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0">  
	 <tr class="Titulos3">
	   <td height="10" align="center">&nbsp;</td>
	   </tr>
	<tr>
        <td valign="top">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr class="LetraNegra">
    <td colspan="4"><?php 	
	/**
	* Carga los nodos del plan de cuentas 
	*/
	$obBD_con1->cargarNodosBalance($Pla_Cod,0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 1, $Pec_Cod, 0, $utilidad, 0, $Max_Niv, 2); 		
	?></td>
    </tr>
  
  <tr class="LetraNegra">
    <td colspan="4"><table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" >
      <tr>
        <td valign="top" align="left"><?php 
		$obBD_con1->fechaImpresion($Ses_Suc_Cod, $obBD_conexion); 
		$infoFirmas=$obBD_con1->getRowConsulta(5,$Ses_Emp_Cod,$obBD_conexion);
		?></td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        </tr>
      <tr>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        </tr>
      <tr>
        <td height="66" align="center" valign="top">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="center">&nbsp;</td>
      </tr>
      <tr>
        <td valign="top" align="left">__________________<br>        </td>
        <td valign="top" align="center">&nbsp;</td>
        <td valign="top" align="left">__________________<br>        </td>
        </tr>
      <tr>
        <td height="9" align="left" valign="top">GERENTE<p></p><p style="margin:-1.5% 0;"><?php echo $infoFirmas['Emp_Ren'];?></p><p></p><p style="margin:-1.5% 0;">CI:&nbsp;<?php echo $infoFirmas['Emp_Rre'];?></p></td>
        <td align="center" valign="top">&nbsp;</td>
        <td align="left" valign="top">CONTADOR
          <p></p>
          <p style="margin:-1.5% 0;"><?php echo $infoFirmas['Emp_Con'];?></p>
          <p></p>
          <p style="margin:-1.5% 0;">RUC:&nbsp;<?php echo $infoFirmas['Emp_Rco'];?></p></td>
        </tr>
      <tr>
        <td height="10" colspan="3" align="left" valign="top"><hr /></td>
        </tr>
      <tr>
        <td height="10" align="left" valign="top" class="LetraPie">CORPROINFO - OFSERCONT - EXA SISTEMA CONTABLE</td>
        <td align="center" valign="top">&nbsp;</td>
        <td align="left" valign="top">&nbsp;</td>
      </tr>

    </table></td>
  </tr>
</table>	</td>
  </tr>
</table>	  
</BODY></HTML>
<?php 
/**
* Cierra la conexion
*/
$obBD_conexion->cerrar();
?>