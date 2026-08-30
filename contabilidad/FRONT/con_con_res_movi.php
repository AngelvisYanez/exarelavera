<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* Descripci�n: Permite consultar el balance de comprobaci�n
* Fecha de actualizaci�n:	2012-10-24
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
        <meta charset="UTF-8">
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/con_val_balances.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>        
		<meta http-equiv="Content-Type" content="text/html; ">
	</HEAD>
<BODY>
<div id="set1">
     <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td height="10"><span class="">&nbsp;&raquo;</span> Resumen de Movimientos <?Php echo $periodo; ?></td>
    </tr>
	<tr>
        <td height="389" valign="top">
		<form name="form1" method="post" action="<?Php  echo $_SERVER['PHP_SELF']; ?>">
<?Php
/* Control para la elecci�n del periodo contable */
if (!isset($hdd_save) && !isset($hdd_save2))
{
?>		
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selección Periodo Contable</label>
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
	    <td width="110" height="40" align="center"><button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>
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
    <td width="50%">
        <fieldset>
            <legend>
                <label class="Etiqueta1">Seleccionar Rango:</label>
            </legend>
        <table>
            <tr>
                <td>
                    <select id="cta_ran" name="rangos" onchange="$('.rangos').hide(); if(this.value==='G') {$('#grupo').show();$('#cta_grupo').val('');} if(this.value==='R') {$('#rango').show();$('.cta_rango').val(''); } ">
                        <option value="T" <?php if($rangos=='T') echo 'selected'; ?> >Todos</option>
                        <option value="G" <?php if($rangos=='G') echo 'selected'; ?>>Grupo</option>
                        <option value="R" <?php if($rangos=='R') echo 'selected'; ?>>Rango</option>
                    </select>
                </td>
                <td>&nbsp;&nbsp;&nbsp;</td>
                <td>
                    <div id="grupo" class="rangos">
                        <input id="cta_grupo" name="cta_grupo" type="text" value="<?php echo $cta_grupo; ?>" size="7" required />
                    </div>
                    <div id="rango" class="rangos">
                        Desde: <input class="cta_rango" id="cta_desde" name="cta_desde" type="text" value="<?php echo $cta_desde; ?>" maxlength="1"  size="3" required onkeypress="return validar_numeric(event);" />&nbsp;&nbsp;&nbsp;
                        Hasta: <input class="cta_rango" id="cta_hasta" name="cta_hasta" type="text" value="<?php echo $cta_hasta; ?>" maxlength="1"  size="3" required onkeypress="return validar_numeric(event);" />
                    </div>
                </td>
            </tr>
        </table>
            <script>
                $('.rangos').hide();
                <?php if($rangos=='G') echo "$('#grupo').show();";  if($rangos=='R') echo "$('#rango').show();"; ?>
                function validaRango(){
                    if($("#cta_ran option:selected").val()==='G'){
                        if($("#cta_grupo").val()===''){
                            $("#cta_grupo").focus();
                            alert('Debe ingresar la cuenta a consultar!');
                            return false;
                        }
                    }
                    if($("#cta_ran option:selected").val()==='R'){
                        if($("#cta_desde").val()===''){
                            $("#cta_desde").focus();
                            alert('Debe ingresar el grupo de inicio de la consulta!');
                            return false;
                        }
                        if($("#cta_hasta").val()===''){
                            $("#cta_hasta").focus();
                            alert('Debe ingresar el grupo de terminacion de la consulta!');
                            return false;
                        }
                    }
                    return true;    
                }
                
            </script>
        </fieldset>    
    </td>
  </tr>
</table>
<br>
<table width="229" border="0">
  <tr>
	<td width="223">
     <button type="button" class="btn btn-success fileinput-button" title="Mostrar Balance de Comprobaci&oacute;n" name="button" id="button" onClick="if(validaRango()){validar_balance(this.form, this.form.cmb_mes);}">
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
    <div id="Exportar">
<table width="100%" border="0" cellspacing="0" cellpadding="0" id="Exportar_a_Excel">
  <tr class="LetraNegra">
    <td colspan="4"><span class="Etiqueta1">Desde: </span><?php echo $txt_fec_ini; ?> &nbsp;&nbsp;&nbsp;<span class="Etiqueta1">Hasta:</span> <?Php echo $txt_fec_fin; ?><br>
      <br></td>
  </tr>
  <tr class="LetraNegra">
    <td colspan="4"><?php 
	/**
	* Consulta de la cuenta de utilidades 
	*/
	$row_utilidades = $obBD_con1->getRowConsulta(220, $Pec_Cod, $obBD_conexion);
        //var_dump($Pla_Cod);
	$utilidad = $row_utilidades['Pld_Cod'];
        $sql='';
        if($rangos=='G'){
            $sql=" AND Pld_Cdc LIKE '".trim($cta_grupo)."%' ";
        }
        if($rangos=='R'){
            $sql=" AND CAST((SUBSTRING_INDEX(Pld_Cdc, '.', 1))AS DECIMAL) BETWEEN $cta_desde AND $cta_hasta ";
        }
	/**
	* Carga los nodos del plan de cuentas 
	*/
	$obBD_con1->cargarNodosResumen($Pla_Cod,0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 3, $arreglo[0], 0, $utilidad, 0, $Max_Niv, 2,$sql); 
	?></td>
    </tr>
</table>
    </div>
</FIELDSET>
<br>
<table border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="110"><form action="con_pri_res_movi.php" method="post" name= "form2" id="form2" target="_blank">
    <button type="button" class="btn btn-primary start" title="Imprimir Balance de Comprobaci&oacute;n" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>    
      <input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?Php echo $arreglo[0]; ?>" />
      <input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?Php echo $txt_fec_ini; ?>">
      <input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?Php echo $txt_fec_fin; ?>">
      <input name="Max_Niv" type="hidden" id="Max_Niv" value="<?Php echo $Max_Niv; ?>">
          <input name="rangos" type="hidden" id="Max_Niv" value="<?Php echo $rangos; ?>"/>
          <input name="cta_grupo" type="hidden" id="Max_Niv" value="<?Php echo $cta_grupo; ?>"/>
          <input name="cta_desde" type="hidden" id="Max_Niv" value="<?Php echo $cta_desde; ?>"/>
          <input name="cta_hasta" type="hidden" id="Max_Niv" value="<?Php echo $cta_hasta; ?>"/>
<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?Php echo $Pla_Cod; ?>"> 
<input name="utilidad" type="hidden" id="utilidad" value="<?Php echo $utilidad; ?>" />                 
    </form></td>
    <td width="170"><button name="Boton_Excel" onclick="downloadFile(exportarExcelBlob('Exportar','Resumen Movimientos'),'ResumMovimi-'+getDate()+'.xls')" type="button" class="btn btn-primary start" title="Exportar Excel">
           <i class=" icon-share icon-white"></i><span>Excel</span>
	</button></td>
  </tr>
</table>
<?Php
}//if (isset($hdd_save))
?>
	</td>
  </tr>
</table>	
</div>  
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/ReportPrint.js"></script> 
</BODY></HTML>
<?Php 
/**
* Cierre de la conexion
*/
$obBD_conexion->cerrar();
?>