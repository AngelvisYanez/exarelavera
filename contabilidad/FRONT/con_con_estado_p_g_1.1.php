<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripci�n: Permite consultar el balance de perdidas y ganacias (resultados)
* Fecha de actualizaci�n:	2012-10-09
* Desarrollador:	Lewis Chimarro 
* Fecha de actualizaci�n:	2015-06-15
* Desarrollador:	Lewis Chimarro 
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_balances.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

/**
* Consulta todos los periodos activos 
*/
if(!isset($Pec_Cod))
{
	/**
	* Carga el periodos contable actual 
	*/
	$rs_periodos = $obBD_con1->getArrayConsulta(219,$Ses_Emp_Cod, $obBD_conexion);
	$perio = current($rs_periodos);
}
else
{
	/* Descripcion del periodo contable */
	$periodo = "en el periodo contable ".substr($Pec_Fei, 0,4);			
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/con_val_balances.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
         <!--Librerias para exportar a excel --> 
	    <script language="javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>   
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
     <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td height="10"><span>&raquo;</span> Estado de Resultado Integral <?Php echo $periodo; ?></td>
    </tr>
	<tr>
        <td height="400" valign="top">
		<form name="form1" method="post" action="<?Php  echo $_SERVER['PHP_SELF']; ?>">
<?Php
/* Control para la elecci�n del periodo contable */
if (!isset($hdd_save) && !isset($hdd_save2))
{
?>		
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selecci�n Periodo Contable</label>
	</LEGEND>
	<table width="294" border="0" cellspacing="0" cellpadding="0">
	  <tr>
	    <td width="69" class="Etiqueta1">Periodo:&nbsp; </td>
	    <td width="115"><input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $perio['Pec_Fei']; ?>" />
	      <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $perio['Pec_Fef']; ?>" />
	      <select name="Pec_Cod" id="Pec_Cod" onchange="javascript: asignar_fechas(this.value)">
	        <?Php 
		if (count($rs_periodos) > 0)
		{
		foreach($rs_periodos as $row_rs_periodo)
		{
		?>
	        <option value="<?Php echo $row_rs_periodo['Pec_Cod'].'*'.$row_rs_periodo['Pec_Fei'].'*'.$row_rs_periodo['Pec_Fef'].'*'.$row_rs_periodo['Pla_Cod']; ?>"><?Php echo $row_rs_periodo['Periodo']; ?></option>
	        <?php		
		}
		}//Fin del if ($total_rs_periodo > 0)
		else
		{ ?>
	        <option value=""></option>
	        <?Php
		}
		?>
	        </select></td>
	    <td width="110" height="40" align="center"><button type="button" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>
	      <input name="hdd_save2" type="hidden" id="hdd_save2" /></td>
	    </tr>
	  </table>
</FIELDSET>			 
<?Php 
}//Fin del if (!isset($hdd_save) && !isset($hdd_save2))
if (isset($Pec_Cod))
{
	/**
	* Divide la cadena del periodo contable 
	*/	
	$arreglo = explode("*",$Pec_Cod); 
	$Pla_Cod = $arreglo[3];		
?>		
<table width="100%" border="0">
  <tr>
    <td width="50%"><?php include("../COMPONENTES/con_con_anio_mes_fecha.php"); ?></td>
    <td width="50%"><?php include("../COMPONENTES/con_con_niveles_plan.php"); ?></td>
  </tr>
</table>
<br>
<table width="229" border="0">
  <tr>
    <td width="223">
    <button type="button" class="btn btn-success fileinput-button" title="Mostrar Estado de Perdidas y Ganancias" name="button" id="button" onClick="validar_balance(this.form, this.form.cmb_mes)">
           <i class="icon-check icon-white"></i>
           <span>Calcular</span>
           </button>      
        <input name="hdd_save" type="hidden" id="hdd_save" value="">
        <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
        <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
        <input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>">
        <input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
    </td>
  </tr>
</table>
<?Php
}//Fin del if (isset($hdd_save2))
?>
</form>
<?Php
if (isset($hdd_save))
{ 
?>
<FIELDSET>  
<LEGEND>
<label class="Etiqueta1">Resultados de la busqueda</label>
</LEGEND>
<table width="100%" border="0" cellspacing="0" cellpadding="0" id="Exportar_a_Excel">
  <tr class="LetraNegra">
    <td colspan="4"><span class="Etiqueta1">Desde: </span><?php echo $txt_fec_ini; ?> &nbsp;&nbsp;&nbsp;<span class="Etiqueta1">Hasta:</span> <?Php echo $txt_fec_fin; ?><br>
      <br></td>
  </tr>
  <tr class="LetraNegra">
    <td colspan="4"><?php 
	/**
	* Carga los nodos del plan de cuentas 
	*/
	$obBD_con1->cargarNodosBalance($Pla_Cod,0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 2, $arreglo[0], 0, 0, 0, $Max_Niv, 2); 	
	?></td>
    </tr>
</table>
</FIELDSET>
<br>
<table border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="110">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form_volver" id="form_volver" style="display: inline-block;">
			<button type="submit" class="btn" title="Volver a selección de período"
				style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: #ffffff; width: auto; padding: 6px 14px; border-radius: 6px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500; margin-left: 10px;">
				<i class="icon-arrow-left icon-white"></i>
				<span> Volver</span>
			</button>
		</form>
	</td>
    <td width="110"><form action="con_pri_estado_p_g_1.1.php" method="post" name= "form2" id="form2" target="_blank">
    <button type="button" class="btn btn-primary start" title="Imprimir Estado de Perdidas y Ganancias" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>             
      <input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?Php echo $arreglo[0]; ?>">
      <input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?Php echo $txt_fec_ini; ?>">
      <input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?Php echo $txt_fec_fin; ?>">
      <input name="Max_Niv" type="hidden" id="Max_Niv" value="<?Php echo $Max_Niv; ?>">
<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?Php echo $Pla_Cod; ?>"> 
<input name="utilidad" type="hidden" id="utilidad" value="<?Php echo $utilidad; ?>">                       
    </form></td>
    <td width="143"><form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
    <button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
           <i class=" icon-share icon-white"></i>
           <span>Excel</span>
	</button>         
      <input type="hidden" id="datos_a_enviar" name="datos_a_enviar" />
    </form></td>
  </tr>
</table>
<?Php
}//if (isset($hdd_save))
?>
	</td>
  </tr>
</table>	  
</div>
</BODY>
</HTML>
<?Php 
/**
* Cierra la Conexion
*/
$obBD_conexion->cerrar();
?>