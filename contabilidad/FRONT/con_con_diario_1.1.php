<?php	
/**
* @abstract Consulta del libro diario 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaci�n  2012-05-01
* Fecha de actualizaci�n  2015-05-01
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_diario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creaci�n del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);

/**
* Creaci�n del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

/**
* Descripcion del periodo contable 
*/
$periodo = "del periodo contable ".substr($Pec_Fei, 0,4);		
/**
* En esta consulta el c�digo del plan de cuentas
*/
$row_rs_cuenta_manual = $obBD_con1->getRowConsulta(215,$Pec_Cod, $obBD_conexion);	
$Pla_Cod = $row_rs_cuenta_manual['Pla_Cod'];	

/**
* Cargado ajax de la busqueda de la cuenta 
*/
if (isset($buscod))
{
	
	if ($op_op=='d')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(312,trim($buscod).'*'.$Ses_Emp_Cod.'*'.''.'*'.$Pla_Cod,$obBD_conexion);
	}
	if ($op_op=='c')
	{
		/**
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(313,trim($buscod).'*'.$Ses_Emp_Cod.'*'.''.'*'.$Pla_Cod,$obBD_conexion);
	}	
	$total_rs_buscar = count($rs_buscar);					
?>	
    <FIELDSET>
		<LEGEND>
		<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
			<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
            <thead>
			  <tr>
				  <th width="10%">C&oacute;digo</th>
				  <th>Descripci&oacute;n</th>
				  <th>Grupo</th>				  
				  <th>Tipo</th>				  
				  <th width="7%">Estado</th>
				  <th width="7%">&nbsp;</th>
			  </tr>
              </thead>
              <tbody>
			  <?php 
			  if($total_rs_buscar != 0)		
				{			  
				  foreach($rs_buscar as $row_rs_buscar) { 
					/**
					* Consulta del detallete de la CUENTA 
					*/
					$row_rs_recur = $obBD_con1->getRowConsulta(204,$row_rs_buscar['Pld_Rec'],$obBD_conexion);				  
				  ?>
				  <tr>
					  <td><?php echo $row_rs_buscar['Pld_Cdc']; ?></td>
                      <td><?php echo utf8_encode($row_rs_buscar['Pld_Des']); ?></td>
                      <td align="center"><?php if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']; }else{ echo "&nbsp;"; } ?></td>
                      <td align="center"><?php echo $row_rs_buscar['Pld_Tip']; ?></td>				  
                      <td align="center"><?php echo $row_rs_buscar['Pld_Est']; ?></td>
                      <td align="center">
					  <?php if ($row_rs_buscar['Pld_Est'] == 'Activa'){?>                      
					  <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="document.form1.codRec.value='<?php echo $row_rs_buscar['Pld_Rec']; ?>';document.form1.Pld_Cod.value='<?php echo $row_rs_buscar['Pld_Cod']; ?>'; document.form1.txt_busqueda.value='<?php echo $row_rs_buscar['Pld_Cdc']; ?>';">
		      <i class="icon-arrow-right icon-white"></i>
		      </button>
					  <?php }else{ echo "&nbsp;"; } ?>                         				
                      </td>
				  </tr>
				  <?php } 
				}//Fin del if($total_rs_buscar != 0)	
				else
				{
				  ?>	
				  <tr>					
                      <td width="10%">&nbsp;</td>
                      <td><?php echo error_alerta("No hay resultados que mostrar", 2)?></td>
                      <td>&nbsp;</td>				  
                      <td>&nbsp;</td>				  
                      <td width="7%">&nbsp;</td>
                      <td width="7%">&nbsp;</td>
				  </tr>
	  			<?php
				} //Fin del else if($total_rs_buscar != 0)	?>				
                </tbody>                
		  </table>
		</FIELDSET>		
	  <?php 
		/**
		* Muestra la barra de estados con la cantidad de registros encontrados 
		*/
		echo barra_estado($total_rs_buscar);
exit();
}//Fin del if (isset($cuenta))

if (isset($hdd_save) or isset($hdd_save2))
{		

		$hoy = date("Y-m-d");
		if (isset($detalle))
		{					
			/**
			* Consulta de los comprobantes en base a una cuenta 
			*/
			$rs_compr = $obBD_con1->getArrayConsulta(9,$txt_fec_ini.'*'.$txt_fec_fin.'*'.$txt_busqueda.'*'.$Pec_Cod,$obBD_conexion); 
		}
		else
		{
			if (isset($txt_fec_ini))
			{
				$arreglo = explode("*",$Pec_Cod); 		
				$Pec_Cod = $arreglo[0];
				
				$txt_busqueda = "";
				/**
				* Consulta de los comprobantes TODOS
				*/
				if($TipDoc!='T')
				{ $parame='AND Tia_Cod='.$TipDoc;}	
					
				$rs_compr = $obBD_con1->getArrayConsulta(14,$txt_fec_ini.'*'.$txt_fec_fin.'*'.$Pec_Cod.'*'.$parame,$obBD_conexion); 
			}
		}		
		$total_rs_compr = count($rs_compr);
}//Fin del if (isset($hdd_save))
else
{
	if (isset($hdd_save))
	{
		/**
		* Divide la cadena del periodo contable 
		*/ 
		$arreglo = explode("*",$Pec_Cod); 		
		$Pec_Cod = $arreglo[0];
		$Pec_Fei = $arreglo[1];
		$Pec_Fef = $arreglo[2];
	}//Fin del if (isset($hdd_save))
	else
	{
		/**
		* Carga el periodos contable actual 
		*/
		$rs_periodo = $obBD_con1->getArrayConsulta(214,$Ses_Emp_Cod, $obBD_conexion);
		$total_rs_periodo = count($rs_periodo);
		$row_rs_periodo = current($rs_periodo);
	}//Fin del else if (isset($hdd_save))
}
?>
<HTML>
	<HEAD>
		<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
		<TITLE><?php echo "Libro Diario [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
		<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/con_val_diario.js?a=2"></script>       
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
        <link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
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
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
 <tr class="BarraTitulo">
  <td height="10">&raquo; libro diario <?php echo $periodo; ?> </td>
</tr>
	<tr>
        <td height="389" valign="top">
		<form name="form1" id="form1" method="post" action="<?php  echo $_SERVER['PHP_SELF']; ?>">        
<?php
if (!isset($hdd_save) && !isset($hdd_save2))
{
?>
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selección Periodo Contable</label>
	</LEGEND>

    <table border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="48" class="Etiqueta1">Periodo:&nbsp; </td>
        <td width="90">          
          <input type="hidden" id="hdd_ann" name="hdd_ann" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>" />
		  <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
          <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">		
		<select name="Pec_Cod" id="Pec_Cod" onChange="javascript: asignar_fechas(this.value)">
		<?php 
		foreach($rs_periodo as $row)
		{
		?>
			<option value="<?php echo $row['Pec_Cod'].'*'.$row['Pec_Fei'].'*'.$row['Pec_Fef']; ?>"><?php echo $row['Periodo']; ?></option>	
		<?php
		}
		?>	
        </select>
         </td>
        <td width="117" align="center">        
        <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-ok icon-white"></i>
                    <span>Aceptar</span> </button>
          <input name="hdd_save" type="hidden" id="hdd_save"></td>
      </tr>
    </table>
</FIELDSET>		
	<?php
}//Fin del if (!isset($hdd_save))
?>

<?php
if (isset($hdd_save) or isset($hdd_save2))
{	
	$rs_tipDoc = $obBD_con1->getArrayConsulta(315,'', $obBD_conexion);
	?>
	<table width="100%" border="0">
      <tr>
        <td width="38%"><?php include("../COMPONENTES/con_con_anio_mes_fecha.php"); ?></td>
        <td width="22%"><FIELDSET>
          <LEGEND>
            <label class="Etiqueta1">Tipo de Comprobante</label>
            </LEGEND>
          <table width="69%" border="0">
            <tr>
              <td width="7%" class="Etiqueta1">Documento:</td>
              <td width="25%"><select name="TipDoc" id="TipDoc" style="width: 160px;">
                <option value="T"><< TODOS >></option>
                <?php foreach($rs_tipDoc as $datos){?>
                	<option value="<?php echo $datos['Tia_Cod']; ?>" <?php if($TipDoc==$datos['Tia_Cod']){echo "selected";}?>><?php echo $datos['Tia_Des']; ?></option>
                <?php }?>
              </select></td>
            </tr>
          </table>
        </FIELDSET></td>
        <td width="22%">
        <FIELDSET>  
	<LEGEND>
		<label class="Etiqueta1">Opciones de presentaci&oacute;n</label>
	</LEGEND>	
	<table width="69%" border="0">
	  <tr>
		<td width="7%" class="Etiqueta1">Ordenar:</td>
		<td width="25%">
        <select name="ordenar" id="ordenar">
		<option <?php if ($ordenar == 'ORDER BY Com_Fec ASC'){ echo "selected";} ?> value="ORDER BY Com_Fec ASC">Ascendente A->Z</option>
		<option <?php if ($ordenar == 'ORDER BY Com_Fec DESC'){ echo "selected";} ?> value="ORDER BY Com_Fec DESC">Descendente Z->A</option>
	</select></td>
		</tr>
</table>														
</FIELDSET></td>
        <td width="18%"><FIELDSET>
		<LEGEND>
			<label class="Etiqueta1">Opciones de busqueda</label>
		</LEGEND>
		<table width="90%" border="0">
          <tr>
            <td><label>
          <input name="detalle" type="checkbox" id="detalle" onClick="ShowHide('field_buscador'); ShowHide('tbl_diario');" value="checkbox">
        </label>
          <span class="Etiqueta1">Busqueda avanzada</span></td>
          </tr>
        </table>
		</FIELDSET>
		</td>
      </tr>
    </table>
    
    <input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
    <input type="hidden" id="hdd_ann" name="hdd_ann" value="<?php echo $hdd_ann; ?>" />
	<FIELDSET id="field_buscador">
		<LEGEND>
			<label class="Etiqueta1">Buscar por cuenta:</label>
		</LEGEND>
		<table border="0" cellpadding="0" cellspacing="0">
    	<tr>
	      <td width="93" height="28" class="Etiqueta1"><div align="right"><span class="Asterisco">* </span>Cuenta:&nbsp;</div></td>
	      <td width="136" valign="middle">
          <input name="txt_busqueda" type="text" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>" size="20" 
		  	maxlength="50" onBlur="validar_cuentas(form1, this)" onKeyUp="parametro_injection(this)">
            <input name="codRec" type="hidden" id="codRec">  
            <input name="Pld_Cod" type="hidden" id="Pld_Cod"></td>
	      <td width="131"><div align="center">
	        <button type="button" id="btn_libros" name="btn_libros" class="btn btn-success fileinput-button" title="Cuentas" onClick="Muestra_Aparecer();"> <i class="icon-list-alt icon-white"></i> <span>Cuentas</span> </button>
	      </div>			</td>
    	</tr>
  		</table>
        <br>
        <table width="151" border="0" cellpadding="0" cellspacing="0" id="tbl_libros">
          <tr>
            <td width="100%" height="23">                         
            <button name="btn_buscar" type="button" class="btn btn-success fileinput-button" title="Calcular Diario" id="btn_buscar" onClick=
			"validar_buscar_cuenta(document.form1, 'txt_busqueda')"><i class="icon-search icon-white"></i> <span>Buscar</span> </button>
	        <input name="hdd_save2" type="hidden" id="hdd_save2">                                   
             </td>
          </tr>
        </table>
	    </FIELDSET>		
  		<table width="8%" border="0" cellpadding="0" cellspacing="0" id="tbl_diario">
            <tr>
              <td height="45" align="center">
              <button name="btn_buscar_simple" type="button" class="btn btn-success fileinput-button" title="Calcular Diario" id="btn_buscar_simple" onClick=
			"this.form.submit()"><i class="icon-search icon-white"></i> <span>Buscar</span> </button>
              </td>
            </tr>
        </table>
        
		</form>
	<script language="javascript">
		 ShowHide('field_buscador'); 	
		 ShowHide('buscador'); 
 		 //ShowHide('tbl_libros'); 		 
	 </script>		  		
<?php 
if (isset($hdd_save2))
{
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultado de la B&uacute;squeda</label>
</LEGEND>
<div id="Exportar_a_Excel">
<?php
if (isset($detalle))
{
/**
* Consulta del detallete de la CUENTA buscada
*/
$row_cuenta = $obBD_con1->getRowConsulta(314,trim($txt_busqueda).'*'.$Pla_Cod,$obBD_conexion);							
						
?>	
  
  <table width="778" border="0" class="LetraNegra">
    <tr>
      <td width="47" class="Etiqueta1">Desde:</td>
      <td width="201"> <?php echo $txt_fec_ini; ?></td>
      <td width="125" class="Etiqueta1">Hasta:</td>
      <td width="387"> <?php echo $txt_fec_fin; ?></td>
    </tr>
    <tr>
      <td class="Etiqueta1">C&oacute;digo:</td>
      <td> <?php echo $row_cuenta['Pld_Cdc_Grupo']; ?></td>
      <td class="Etiqueta1">GRUPO:</td>
      <td> <?php echo $row_cuenta['Pld_Des_Grupo']; ?></td>
    </tr>
    <tr>
      <td class="Etiqueta1">C&oacute;digo:</td>
      <td> <?php echo $row_cuenta['Pld_Cdc']; ?></td>
      <td class="Etiqueta1">Cuenta:</td>
      <td> <?php echo $row_cuenta['Pld_Des']; ?></td>
    </tr>
  </table>
<?php
}//Fin del if (isset($detalle))
?>
  <table width="100%" border="1" cellpadding="1" cellspacing="0" class="fixedHeader03">
    <thead>
    <tr>
      <th width="8%" align="center">No. Int.</th>
      <th width="8%" align="center">No. Compr.</th>
      <th align="center">Fecha</th>
      <th>Cuentas</th>
      <th align="center">Debe</th>
      <th align="center">Haber</th>
      </tr>
     </thead>
    <tbody>
<?php
if ($total_rs_compr > 0) {
  $puntero_actual=$row_rs_compr['Com_Cod'];
  /**
  * Contador para saber cuantas veces muestra una descipcion del diario 
  */
  $cont = 1;
  $total_debe = 0;
  $total_haber = 0;
  /**
  * Cantidad de asientos 
  */
  $num_asi = 0;
	   
  foreach($rs_compr as $row_rs_compr) { 
	$num_asi++;  
  ?>
    <tr>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center"><?php echo " - ".$num_asi." - "; ?></td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
    </tr>
	<tr>
      <td colspan="6" align="justify"><?php echo $row_rs_compr['Com_Con']; ?></td>
      </tr>	
    <?php
	 /**
	 * Consulta de los comprobantes 
	 */
 	 $rs_comprobantes = $obBD_con1->getArrayConsulta(10,$row_rs_compr['Com_Cod'],$obBD_conexion); 	 
	 $total_rs_comprobantes = count($rs_comprobantes);
	 
	 foreach($rs_comprobantes as $row_rs_comprobantes){  
    	$mostrar_total = false;
	  	/**
		* Control para reiniciar la presentacion del diario 
		*/ 
	  	if ($puntero_actual != $row_rs_compr['Com_Cod'])
		{
			$puntero_actual=$row_rs_compr['Com_Cod'];
			$cont=1;
		    $total_debe = 0;
		    $total_haber = 0;			
		}
	?>
    <tr>
      <td align="center"><?php if ($cont==1)
							{
	  							echo $row_rs_comprobantes['Com_Cod']; 
							}
							else
							{
								echo "&nbsp;";
							}
							?>	  </td>
      <td align="center"><?php if ($cont==1)
							{	  						
								$fecha = explode("-", $row_rs_comprobantes['Com_Fec']);
								$mes = $fecha[1];
								echo $row_rs_comprobantes['Tia_Abr'] . "-" . $mes . '-' . $row_rs_comprobantes['Com_Num'];
							}
							else
							{
								echo "&nbsp;";
							}							 
							?>	  </td>
							  
      <td width="10%" align="center"><?php if ($cont ==1)
	  						{
	  							echo $row_rs_comprobantes['Com_Fec']; 
	  						}
							else
							{
								echo "&nbsp;";
							}							 
							?>	  </td>	  	  
      <td><?php echo $row_rs_comprobantes['Pld_Cdc'].' &nbsp;&nbsp;'.$row_rs_comprobantes['Pld_Des']; ?></td>	  	  
   	  <td width="8%" align="right">&nbsp;
	  					<?php if ($row_rs_comprobantes['Asi_Deh'] == 'D')
	  					{
							echo formato_numero($row_rs_comprobantes['Asi_Val'], 2, 4); 
							$debe = $row_rs_comprobantes['Asi_Val'];
							$total_debe = $total_debe + $debe;							
						} 
						else 
						{ 
							echo "&nbsp;"; 
							$debe = 0;
						}?></td>
      <td width="8%" align="right">&nbsp;<?php if ($row_rs_comprobantes['Asi_Deh'] == 'H')
	  					{
							echo formato_numero($row_rs_comprobantes['Asi_Val'], 2, 4); 
							$haber = $row_rs_comprobantes['Asi_Val'];
							$total_haber = $total_haber + $haber;							
						} 
						else 
						{ 
							echo "&nbsp;"; 
							$haber = 0;
						}
			?></td>
	  </tr>
	  <?php
		/**
		* Contador para poder mostrar la descripcion una sola vez en la tabla 
		*/
		$cont++;	   
	  }
	  ?>
		<tr>
		  <td class="TITULO_REPORTE">&nbsp;</td>
		  <td class="TITULO_REPORTE">&nbsp;</td>
		  <td class="TITULO_REPORTE">&nbsp;</td>
		  <td class="TITULO_REPORTE"><div align="right">TOTAL</div></td>
		  <td align="right"><?php echo formato_numero($total_debe, 2, 4);?></td>
		  <td align="right"><?php echo formato_numero($total_haber, 2, 4);?></td>
		  </tr>	  
		<tr>
<?php		  
  } 
 } else { ?>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td align="center"><?php echo error_alerta("No hay resultados que mostrar", 2) ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
        </tr>
<?php } //Fin del else	
} //Fin del if ($txt_busqueda)
	?>
    </tbody>
  </table> 
  </div>
</FIELDSET>
<br>

 <?php if ($total_rs_compr > 0) { //Condicion para mostrar o no el boton imprimir ?>
  <br>
    <table width="202" border="0" cellpadding="0" cellspacing="0">
    <tr>
    <td width="21%">
    <form action="con_pri_diario_1.1.php" method="post" name= "form2" id="form2" target="_blank">
    <button type="button" class="btn btn-primary start" title="Imprimir Diario" onClick="confirmacion_print(this.form)"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
    <input name="txt_busqueda" type="hidden" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>">
    <input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>">
    <input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">
    <input name="mes" type="hidden" id="mes" value="<?php echo $mes; ?>">
    <input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
    <input name="TipDoc" type="hidden" id="TipDoc" value="<?php echo $TipDoc; ?>">
    </form>
    </td>
    <td width="21%">  
    <form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
  	<input type="hidden" id="datos_a_enviar" name="datos_a_enviar" />
  	<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
           <i class=" icon-share icon-white"></i>
           <span>Excel</span>
	</button>
</form></td>
    </tr>
    </table> 
  <?php }//Fin del if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) ?>

<?php
}//Fin del if (isset($hdd_save))
?>
</td>
  </tr>
</table>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar">             
             <table width="100%" border="0" cellpadding="0" cellspacing="0" id="buscador">
              <tr>
                <td width="100%"><br>
                  <FIELDSET>
                    <LEGEND>
                    <label class="Titulos2">B&uacute;squeda de Cuentas </label>
                    </LEGEND>
                      <table width="500" border="0" >
                      <tr>
                        <td width="205"><input name="op_opciones" id="op_opciones" type="radio" value="d" checked="checked" onClick="document.getElementById('op_op').value = this.value; setfocus(form1.buscta)">
                            <span class="LetraNegra">Descripci&oacute;n</span></td>
                        <td width="266"><input type="radio" name="op_opciones" id="op_opciones" value="c" onClick="document.getElementById('op_op').value = this.value; setfocus(form1.buscta)">
                            <span class="LetraNegra">C&oacute;digo</span></td>
                      </tr>
                    </table>
                      <input name="op_op" type="hidden" id="op_op" value="d">
                    <input name="name_input" type="hidden" id="name_input">
                    <table width="591" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="452" class="BarraBusqueda">Busqueda: 
                        <input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="enter_ajax('<?php echo $_SERVER['PHP_SELF']; ?>?buscod=' + document.getElementById('buscta').value + '&op_op=' + document.getElementById('op_op').value + '&name_input=' + document.getElementById('name_input').value + '&Pec_Cod=' + document.getElementById('Pec_Cod').value, 'busqueda')">
                        </td>
                      <td width="139">&nbsp;<button name="btn_buscarcta" type="button" class="btn btn-success fileinput-button" title="Buscar" id="btn_buscarcta" onClick=
			"ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?buscod=' + document.getElementById('buscta').value + '&op_op=' + document.getElementById('op_op').value + '&name_input=' + document.getElementById('name_input').value + '&Pec_Cod=' + document.getElementById('Pec_Cod').value, 'busqueda')"><i class="icon-search icon-white"></i> <span>Buscar</span> </button>
           			  </td>
                     </tr>                      
                    </table>                      
                  </FIELDSET>
                  <div id="busqueda"> </div>
                  </td>
                  </tr>
              </table>             
             </div>
       </div>
</div>
<script type="text/javascript" src="../VALIDACIONES/con_par_diario.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
</BODY>
</HTML>
<?php 
/** 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();
?>