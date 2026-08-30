<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* @abstract Permite modificartrar los cheques 
* @author Lewis Chimarro
* @version 1.0
* Fecha de creaciï¿½n  2012-07-19
* Fecha de actualizaciï¿½n  2012-07-23
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");	

if(isset($ajax)){
        $com_codigo = $ComCod;
	include('../../contabilidad/COMPONENTES/con_con_detalleCompr.php'); 
        exit();
}
/**
* Cargado AJAX de los resultados de la bï¿½squeda
*/
if (isset($buscod))
{
		if ($op_opciones2=='d')
		{
			/**
			* Cargado de los resultados de la busqueda por descripcion de la cuenta
			*/
			$rs_buspro = $obBD_con1->consulta(sentencias_che(301,$obBD_con1->parametros(trim($buscod).'*'.$Ses_Emp_Cod)), $obBD_conexion->conexion);
		}
		elseif ($op_opciones2=='c')
		{
			/**
			* Cargado de los resultados de la busqueda por codigo de la cuenta
			*/
			$rs_buspro = $obBD_con1->consulta(sentencias_che(302,$obBD_con1->parametros(trim($buscod).'*'.$Ses_Emp_Cod)), $obBD_conexion->conexion);
		}
		$row_rs_buspro = $obBD_con1->registros();
		$total_rs_buspro = $obBD_con1->numregistros();
	
	/**
	* Consulta los bancos con su respectivo asiento contable 
	*/	
	$rs_combo = $obBD_con1->consulta(sentencias_che(304, $obBD_con1->parametros($cod_num)), $obBD_conexion->conexion);
	$row_rs_combo = $obBD_con1->registros();
	$total_rs_combo = $obBD_con1->numregistros();
	
	/**
	* Creacion del Array para luego ser procesado	
	*/
	do { 
		$ban_cod[]=$row_rs_combo['Banasi'];
		$ban_des[]=$row_rs_combo['Pld_Des']." ($".number_format($row_rs_combo['Asi_Val'],2,'.','').")";
		$asi_val[]=number_format($row_rs_combo['Asi_Val'],2,'.','');
	} while ($row_rs_combo = mysqli_fetch_assoc($rs_combo));

	/**
	* Procesamiento del Array a un formato entendible por Javascript
	*/
	$ban_cod='Array(\'' . implode('\', \'', $ban_cod) . '\')';
	$ban_des='Array(\'' . implode('\', \'', $ban_des) . '\')';
	$asi_val='Array(\'' . implode('\', \'', $asi_val) . '\')';
	?>
<br>
<table width="100%" border="1" cellpadding="0" cellspacing="0">
      <tr class="Cabecera1">
        <td width="40"><strong>C&oacute;d. Int. </strong></td>
        <td width="80"><strong>C&eacute;dula/R.U.C.</strong></td>
        <td><strong>Proveedor</strong></td>
        <td width="35">&nbsp;</td>
        </tr>
      <?php
	  if ($total_rs_buspro > 0) 
	  {
	  do { ?>
      <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
        <td align="center"><?php echo $row_rs_buspro['Prv_Cod']; ?></td>
        <td align="left"><?php echo $row_rs_buspro['Prs_Ced']; ?></td>
        <td align="left"><?php echo $row_rs_buspro['Prs_Ape'].' '. $row_rs_buspro['Prs_Nom']; ?></td>
        <td align="center">
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="nueva_fila_cheque('contenido',<?php echo $row_rs_buspro['Prv_Cod']; ?>,'<?php echo $row_rs_buspro['Prs_Ape'].' '.
		$row_rs_buspro['Prs_Nom']; ?>',<?php echo $ban_cod; ?>,<?php echo $ban_des; ?>,<?php echo $cod_prv; ?>, '<?Php echo $com_fec; ?>',<?Php echo $asi_val; ?>,'<?Php echo $varios_prov; ?>')">
		        <i class="icon-arrow-right icon-white"></i>
            </button> </td>
        </tr>
      <?php } while ($row_rs_buspro = $obBD_con1->fetch_assoc($rs_buspro));
	  } else { ?>
	  	<tr><td colspan="4" align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
	  	</tr>
	  <?php }?>
    </table>
    <?php echo barra_estado($total_rs_buspro); ?>	 
<?php 
@$obBD_con1->free_result($rs_buspro);
@$obBD_con1->free_result($rs_combo);
exit();
}//Fin del if (isset($buscod))

/**
* Consulta para la eleccion del periodo contable
*/
if (!isset($hdd_save) && !isset($hdd_save2) && !isset($codigo))
{
	/**
	* Carga el periodos contable actual 
	*/
	$row_rs_periodos = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod, $obBD_conexion);
}//Fin del if ($Pec_Cod)
else
{
	/**
	* Guardado del cheque 
	*/
	//if ($thisPost->postBlock($_POST['postID']))
	{
		if (isset($bt_save) && !isset($hdd_volver))
		{
			/**
			* Inicio de la transaccion
			*/
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			/**
			* Control para eliminar las cuentas que han sido eliminadas 
			*/
			$asientos=explode('*',$asientos);
			for ($d=1;$d<=count($asientos)-1;$d++)
			{
				$obBD_con1->grabarv_registros(sentencias_che(310,$obBD_con1->parametros($asientos[$d])), $obBD_conexion->conexion);
			}
                        //print_r($datos);
			foreach ($datos as $puntero => $item)
			{
				$cant++;
				$param[]=$item;
                                
				if ($cant==10)
				{
                                        //var_dump($param);
					$cant=0;
					$obBD_con1->grabarv_registros(sentencias_che(190,$obBD_con1->parametros($param[3].'*'.$param[0].'*'.$param[1].'*'.$param[4].'*'.$param[7].
									'*'.$param[5].'*'.$param[8].'*'.$param[6].'*'.$param[9])), $obBD_conexion->conexion);
                                        unset($param);
				}
			}
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}//Fin del if (isset($bt_save))
	} //fin del POSTH

	/**
	* Cargado de los datos de la cabecera 
	*/
	if (trim($txt_busqueda) !="") 
	{
//		if ($op_opciones == "d")
//		{	
			$rs_cabcompr = $obBD_con1->getArrayConsulta(345,trim($txt_busqueda).'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$bancos, $obBD_conexion);
//		}//Fin del if ($op_opciones == "d")
//		else 
//		{			
//			/**
//			*  Control para busqueda mensual  
//			*/
//			$mes_array = explode('-', $txt_busqueda);
//			if (count($mes_array)==2)
//			{
//				$Par_Fec = "AND MONTH(Com_Fec)=$mes_array[0]";
//				$txt_busqueda = $mes_array[1];//$mes[1] es el numero del comprobante
//			}
//
//			$rs_cabcompr = $obBD_con1->getArrayConsulta(313,'proveedore'.'*'.trim($txt_busqueda).'*'.'D'.'*'.
//									$Pec_Cod.'*'.'Prv_Cod'.'*'.$Par_Fec, $obBD_conexion);
//		}//Fin del else if ($op_opciones == "d")					
		$row_rs_cabcompr = $rs_cabcompr;
		$total_rs_cabcompr = count($rs_cabcompr);
	}
	else
	{
		if (isset($codigo))
		{
			/**
			* Consulta los datos del comprobante 
			*/				
			$rs_cabcomp = $obBD_con1->consulta(sentencias_che(149,$obBD_con1->parametros('proveedore'.'*'.$codigo.'*'.'D'.'*'.
									$Pec_Cod.'*'.'Prv_Cod')), $obBD_conexion->conexion);
			$row_rs_cabcomp = $obBD_con1->registros();
			$total_rs_cabcomp = $obBD_con1->numregistros();						
		}
	}//FIn del else if (trim($txt_busqueda) !="") 
		
	/**
	* Divide la cadena del periodo contable 
	*/
	$arreglo = explode("*",$Pec_Cod); 		
	$Pec_Cod = $arreglo[0];
	/**
	* Consulta del periodo contable 
	*/
	$rs_periodo = $obBD_con1->consulta(sentencias_che(113, $obBD_con1->parametros($Pec_Cod)), $obBD_conexion->conexion);
	$row_rs_periodo = $obBD_con1->registros();
	$total_rs_periodo = $obBD_con1->numregistros();

	/**
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);				
}//Fin del if (!isset($hdd_save))
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../VALIDACIONES/tes_val_cheque.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
  	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>		
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</script>
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0"  class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Modificar  cheques <?Php echo $periodo; ?></td>
  </tr>
	<tr>
      <td align="left" valign="top" height="400">
    <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name= "form1">
<?php 
if (!isset($hdd_save) && !isset($hdd_save2) && !isset($codigo)) 
{ ?>
	<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selecci&#243;n Periodo Contable</label>
	</LEGEND>
	<table width="280" border="0" cellspacing="0" cellpadding="0">
	  <tr>
	    <td width="53" class="Etiqueta1">Periodos:&nbsp; </td>
	    <td width="88"><?php
		$periodo = current($row_rs_periodos); 
		?>
	      <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $periodo['Pec_Fei']; ?>" />
	      <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $periodo['Pec_Fef']; ?>" />
	      <select name="Pec_Cod" id="Pec_Cod" onchange="javascript: asignar_fechas(this.value)">
	        <?Php 
	if (count($row_rs_periodos) > 0)
	{
		foreach ($row_rs_periodos as $row)
		{
		?>
	        <option value="<?Php echo $row['Pec_Cod'].'*'.$row['Pec_Fei'].'*'.$row['Pec_Fef']; ?>"><?Php echo $row['Periodo']; ?></option>
	        <?php		
		}//Fin del foreach ($row_rs_periodos as $row)
	}//Fin del if ($row_rs_periodos > 0)
	else
	{ ?>
	        <option value=""></option>
	        <?Php
	}//Fin del else if ($row_rs_periodos > 0)
	?>
	        </select></td>
	    <td width="84" align="center"><button type="button" class="btn btn-success" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>
	      <input name="hdd_save" type="hidden" id="hdd_save" /></td>
	    </tr>
	  </table>
	</FIELDSET>			 	  	  
<?php 
}//Fin del if (!isset($hdd_save))      
else
{
 ?>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Buscar  Comprobante de Egreso</label>
		</LEGEND>
		<table width="605" height="27" border="0">
		  <tr>
		    <td width="124" height="23" class="LetraNegra"><input name="op_opciones" type="radio" value="d"  <?php if(!isset($op_opciones)) {echo "checked";} if($op_opciones=="d") {echo "checked";} ?>  onclick="document.getElementById('cmb_mes').disabled=false; setfocus(this.form.txt_busqueda)" />
		      Apellidos</td>
		    <td width="167" class="LetraNegra"><input type="radio" name="op_opciones" value="n"  <?php if($op_opciones=="n") {echo "checked";} ?>  onclick=                  "document.getElementById('cmb_mes').disabled=true; setfocus(this.form.txt_busqueda)" />
		      No. de Cheque </td>
		    <td width="300" class="LetraNegra"><input type="radio" name="op_opciones" value="r"  <?php if($op_opciones=="r") {echo "checked";} ?>  onclick=                  "document.getElementById('cmb_mes').disabled=true; setfocus(this.form.txt_busqueda)" />
		      No. de Comprobante</td>
		    </tr>
		  <tr>
		    <td>Seleccione Banco:&nbsp;</td>
		    <td colspan="2" class="LetraNegra"><select style="display: none;" name="cmb_mes2" id="cmb_mes2" <?php if($op_opciones=="r") {echo "disabled";} ?>>
		      <option value="">&lt;&lt; TODOS &gt;&gt;</option>
		      <?Php
                          for ($i=1;$i<=12;$i++)
                          { ?>
		      <option <?php if ($cmb_mes == ("AND MONTH(Com_Fec)=".$i)){ echo "selected"; } ?><?php //if ($i == $mes){ echo "selected"; } ?>  value="<?Php echo "AND MONTH(Com_Fec)=$i"; ?>">
		        <?php 
                                    echo mes($i, 1) ?>
		        </option>
		      <?Php
                          } ?>
		      </select>
		      <select name="bancos" id="bancos" style=" width:300px">
		        <option value="">&lt;&lt; TODOS &gt;&gt;</option>
		        <?php
    $rs_bancos = $obBD_con1->getArrayConsulta(339,$Ses_Emp_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
		        <option  <?php if($bancos==$row['Pld_Cod']) {echo "selected";} ?> value="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
		        <?php
        }
    }
?>
		        </select></td>
		    </tr>
		  </table>
		<table width="545" height="36" border="0" cellpadding="0" cellspacing="0" >
		  <tr>
		    <td width="77" class="BarraBusqueda"><div align="right" >Busqueda:</div></td>
		    <td width="350" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>" size="50" maxlength="50" /></td>
		    <td width="118"><div align="center">
		      <input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>" />
		      <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>" />
		      <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>" />
		      <input name="hdd_save2" type="hidden" id="hdd_save2" />
		      <button type="button" class="btn btn-success" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>
		      </div></td>
		    </tr>
		  </table>
		</FIELDSET>
	</form>
<?php 
if(isset($txt_busqueda))
	{
	  ?>	
	<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="1" cellspacing="0" class="fixedHeader01">
		  <thead>
		    <tr>		     
		      <th width="6%">Cod. Int.</th>
                      <th width="5%">Num.</th>
		      <th width="9%">Generaci&oacute;n</th>
		      <th width="11%">No. Compr </th>
		      <th width="2%">C&eacute;dula/R.U.C.</th>
		      <th width="25%">Proveedor</th>
		      <th width="8%">Fecha</th>
		      <th width="8%">Valor</th>
                      <th width="5%">&nbsp;</th>
		      <th width="5%">&nbsp;</th>
	        </tr>
	      </thead>
		  <tbody>
	<?php
	if ($total_rs_cabcompr > 0) 
	{		 
		$i=0; 				
		foreach ($row_rs_cabcompr as $row) //do 
		{
			$i++;
		  	if($row['Com_Est']=='I')
	  		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}  ?>
		    <tr>
		      <!--<td align="center"><img src="../../imagenes/edit_add.png" id="mas[<?php echo $i; ?>]" width="25" height="25" title="Ver detalle" style="cursor:pointer" onclick="mas_menos(1,'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)" /><img src="../../imagenes/edit_remove.png" id="menos[<?php echo $i; ?>]" width="25" title="Ocultar detalle" style="cursor:pointer" height="25" onclick="mas_menos(2, 'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)" /></td>-->
		      <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row['Com_Cod']; ?></font></td>
                      <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row['Che_Num']; ?></font></td>
		      <td align="center"><font color="<?php echo $rojo; ?>">
		        <?Php  if ($row['Com_Gen'] == 'M') echo "Manual"; else echo "Autom&aacute;tico";
		  ?>
		        </font></td>
		      <td align="center"><font color="<?php echo $rojo; ?>">
		        <?php 
	  	list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
		  echo $row['Tia_Abr'].'-'.$mes.'-'.$row['Com_Num']; ?>
		        </font></td>
		      <td><font color="<?php echo $rojo;?>"><?php echo $row['Prs_Ced']; ?></font></td>
		      <td><font color="<?php echo $rojo;?>"><?php echo $row['Prs_Ape']." ".$row['Prs_Nom']; ?></font></td>
		      <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row['Com_Fec']; ?></font></td>
		      <td align="right"><font color="<?php echo $rojo;?>"><?php echo $row['Com_Val']; ?></font></td>
                      <td align="center">
                            <!--<button type="button" class="btn btn-info btn-mini" onclick="Muestra_Aparecer();ajax_datos('<?Php //echo $_SERVER['PHP_SELF']; ?>?ajax=true&ComCod=<?php echo $row['Com_Cod']; ?>','ajax_modal')"><i class="icon-info-sign icon-white"></i></button>-->
                        </td>
		      <td align="center"><?php if ($row['Com_Est']=='A') { ?>
		        <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2" id="form2">
		          <input name="codigo" id="codigo" type="hidden"  value="<?php echo $row['Com_Cod']; ?>" />
		          <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>" />
		          <input name="volver_busqueda" id="volver_busqueda" type="hidden"  value="<?php echo $txt_busqueda; ?>" />
                          <input name="volver_bancos" id="volver_bancos" type="hidden"  value="<?php echo $bancos; ?>" />
		          <input name="volver_opciones" id="volver_opciones" type="hidden"  value="<?php echo $op_opciones; ?>" />
		          <input name="volver_mes" id="volver_mes" type="hidden"  value="<?php echo $cmb_mes; ?>" />
		          <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()"> <i class="icon-arrow-right icon-white"></i> </button>
	            </form>
		        <?php
		  }else { echo "&nbsp;"; } ?></td>
	        </tr>
		    <tr id="detalle[<?Php echo $i; ?>]">
		      <?php
			/**
			* Propiedad Codigo del comprobante 
			*/
			$com_codigo = $row['Com_Cod']; ?>
		      <td align="center">&nbsp;</td>
		      <td colspan="7"><?Php //include('../../contabilidad/COMPONENTES/con_con_detalleCompr.php'); ?></td>
		      <td align="center">&nbsp;</td>
	        </tr>
		    <?php	  		
	  	}//while ($row_rs_cabcompr = $obBD_con1->fetch_assoc($rs_cabcompr));  ?>
		    <?Php 
	}//Fin del if ($total_rs_cabcompr > 0)
	else
	{ ?>
		    <tr>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      <td><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
		      <td>&nbsp;</td>
	        </tr>
		    <?php
	}//Fin del else if(isset($txt_busqueda))
	  ?>
	      </tbody>
	    </table>
		<?php 
		echo barra_estado($total_rs_cabcompr); ?>	  	  
  	<?Php
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>         
	</FIELDSET>
<?php }
if ($total_rs_cabcomp >0)
{
	/**
	* Consulta las cuentas de un comprobante de egreso 
	*/
	$rs_cuentas = $obBD_con1->consulta(sentencias_che(306,$obBD_con1->parametros($codigo)),$obBD_conexion->conexion);
	$row_rs_cuentas = $obBD_con1->registros();
	$total_rs_cuentas = $obBD_con1->numregistros();
	/**
	* Consulta los bancos 
	*/
	$rs_combo = $obBD_con1->consulta(sentencias_che(304,$obBD_con1->parametros($codigo)),$obBD_conexion->conexion);
	$row_rs_combo = $obBD_con1->registros();
	$total_rs_combo = $obBD_con1->numregistros();
	/**
	* Consulta los proveedores a los cuales se les hace varios cheques 
	*/
	$rs_prov_cheques = $obBD_con1->consulta(sentencias_che(314,$obBD_con1->parametros($Ses_Emp_Cod)),$obBD_conexion->conexion);
	$row_rs_prov_cheques = $obBD_con1->registros();
	$total_rs_prov_cheques = $obBD_con1->numregistros();
	
	if ($total_rs_prov_cheques > 0)
	{
		do{
			$varios_prov = $varios_prov.'*'.$row_rs_prov_cheques['Prv_Cod'];
		}while($row_rs_prov_cheques = $obBD_con1->fetch_assoc($rs_prov_cheques));	
	}//FIn del if ($total_rs_prov_cheques > 0)		

	/**
	* Control para emitir varios cheques a un proveedor 
	*/
	$variosprv =  explode('*', $varios_prov);
	$cheques = false;
	for ($i=1; $i<=count($variosprv)-1;$i++)
	{
		if ($row_rs_cabcomp['Prv_Cod']==$variosprv[$i])
		{
			$cheques = true;
			break;
		}
	}//Fin de for (i=1; i<=variosprv.length-1;i++)
	?>
	<form action="<?Php  echo $_SERVER['PHP_SELF']?>" method="post" name= "form2"> 
        <?Php
    	/**
	* Creacion del campo REPOST
	*/
	$thisPost->startPost();
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos del Comprobante</label>
	</LEGEND>
	<table width="100%" border="0">  
  <tr>
    <td width="15%" class="Etiqueta1"><input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">C&oacute;d. Compr: </td>
    <td width="38%" class="LetraNegra">&nbsp;<?php list($ann, $mes, $dia) = preg_split('![/.-]!', $row_rs_cabcomp['Com_Fec']);
		  echo $row_rs_cabcomp['Tia_Abr'].'-'.$mes.'-'.$row_rs_cabcomp['Com_Num']; ?></td>
    <td width="11%" class="Etiqueta1">Fecha:</td>
    <td width="36%" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Fec']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Nombre:</td>
    <td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom']; ?></td>
    <td class="Etiqueta1">Valor:</td>
    <td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Val']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Concepto:</td>
    <td colspan="3" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Con']; ?></td>
    </tr>
  <tr>
    <td valign="top" class="Etiqueta1">Observaci&oacute;n:</td>
    <td colspan="3" valign="top" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Obs']; ?><input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo;  ?>"></td>
    </tr>
	</table>
	</FIELDSET>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Cuentas</label>
	</LEGEND>		
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	<tr>
	  <th width="10%">C&oacute;digo</th>
		<th>Descripci&oacute;n</th>
		<th>Glosa</th>
		<th width="10%">Debe</th>
		<th width="10%">Haber</th>
		</tr>
    </thead>
    <tbody>    
	<?php 
	if ($total_rs_cuentas > 0)
	{ 
		do {   
	?>
	<tr>
	  <td><?php echo $row_rs_cuentas['Pld_Cdc']; ?></td>
		<td><?php echo $row_rs_cuentas['Pld_Des']; ?></td>
		<td><?php echo $row_rs_cuentas['Asi_Glo']; ?></td>
		<td align="right">
	  <?php if ($row_rs_cuentas['Asi_Deh']=='D') { echo $row_rs_cuentas['Asi_Val']; $total=$total + $row_rs_cuentas['Asi_Val']; } ?>
	  	</td>
		<td align="right">
		  <?php if ($row_rs_cuentas['Asi_Deh']=='H') { echo $row_rs_cuentas['Asi_Val']; } ?>
		  </td>
		</tr>
	<?php } while($row_rs_cuentas=$obBD_con1->fetch_assoc($rs_cuentas)); } ?>
	<tr>
	  <td class="LetraNegra">&nbsp;</td>
	  <td class="LetraNegra">&nbsp;</td>
	  <td class="LetraNegra"><strong>Totales</strong></td>
	  <td class="LetraNegra" align="right"><strong><?php echo number_format($total,2); ?></strong></td>
	  <td class="LetraNegra" align="right"><strong><?php echo number_format($total,2); ?></strong></td>
	  </tr>	
	</table>
    <?php  echo barra_estado($total_rs_cuentas);  ?>
	</FIELDSET>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Cheques emitidos</label>
	</LEGEND>		
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
	<thead>	  
	  <tr>
	    <th width="22%">Banco</th>
		<th width="20%">Proveedor</th>
		<th width="8%">N&ordm; Ch.</th>
		<th width="8%">Valor</th>
		<th width="8%">Fec. Elab</th>
		<th style="display: none;" width="0px">Fec. Cobro</th>
		<th width="10%">Observaci&oacute;n</th>
		<th width="9%">&nbsp;</th>                
	  </tr>      
	 </thead>	
     <tbody id="contenido">
	 <?php
	/**
	* Cargado de los cheques del comprobante 
	*/
	$rs_carcheq = $obBD_con1->consulta(sentencias_che(309,$obBD_con1->parametros($row_rs_cabcomp['Com_Cod'])), 
					$obBD_conexion->conexion);
	$row_rs_carcheq = $obBD_con1->registros();
	$total_rs_carcheq = $obBD_con1->numregistros();
	/**
	* Cargado del array que contiene lo valores maximos de los cheques x asiento.
	*/
	$rs_arrmax = $obBD_con1->consulta(sentencias_che(304,$obBD_con1->parametros($row_rs_cabcomp['Com_Cod'])), $obBD_conexion->conexion);
	$row_rs_arrmax = $obBD_con1->registros();
	$total_rs_arrmax = $obBD_con1->numregistros();
	/**
	* Creacion del Array para luego ser procesado
	*/
	do { 
		$codigo_array=explode("*",$row_rs_arrmax['Banasi']);
		$asi_cod[]=$codigo_array[1];
		$asi_val[]=$row_rs_arrmax['Asi_Val'];
		$asientos=$asientos.'*'.$codigo_array[1];					
	} while ($row_rs_arrmax = $obBD_con1->fetch_assoc($rs_arrmax));

	$asi_cod='Array(\'' . implode('\', \'', $asi_cod) . '\')';
	$asi_val='Array(\'' . implode('\', \'', $asi_val) . '\')';
	
	$total = 0;
	 do 
	 {
		if($row_rs_carcheq['Che_Est']=='I'||$row_rs_carcheq['Che_Est']=='P')
		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
		$fila++;
	 if ($row_rs_carcheq['Che_Est'] == 'A'||$row_rs_carcheq['Che_Est']=='C')
	 { 
		 $total = $total + $row_rs_carcheq['Che_Val'];
	 ?>
	  <tr>
	    <td>
        <input name="datos[<?php echo $fila; ?>,1]" id="datos[<?php echo $fila; ?>,1]" type="hidden" value="<?php echo $row_rs_carcheq['Ban_Cod']; ?>"><input name="datos[<?php echo $fila; ?>,2]" id="datos[<?php echo $fila; ?>,2]" type="hidden" value="<?php echo $row_rs_carcheq['Asi_Cod']; ?>">		
              <select <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo '';} ?>  name="datos[<?php echo $fila; ?>,10]" id="datos[<?php echo $fila; ?>,10]" onChange="cargar_codban(this,this.parentNode.firstChild,this.parentNode.childNodes[1]); 
		<?Php if ($cheques == false){ ?>
				seleccionar_valor(this,<?Php echo $asi_val; ?>,this.parentNode.parentNode.childNodes(3).firstChild);
		<?Php } ?>">
	        <option value="0"></option>
	        <?php
		  /**
		  * Mueve el puntero al inicio 
		  */
		  $row_rs_combo = first_last($rs_combo, $row_rs_combo, 0);
				
		  do {  ?>
	        <option value="<?php echo $row_rs_combo['Banasi']; ?>" <?php
		  $bancos=explode("*",$row_rs_combo['Banasi']);
		  if ($bancos[0]==$row_rs_carcheq['Ban_Cod']) { echo "selected"; } ?>><?php echo $row_rs_combo['Pld_Des']." ($".$row_rs_carcheq['Che_Val'].")"; ?></option>
	        <?php
			} while ($row_rs_combo = $obBD_con1->fetch_array($rs_combo));
			?>
	        </select></td>
	    <td><input <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'readonly';} ?> name="datos[<?php echo $fila; ?>,3]" id="datos[<?php echo $fila; ?>,3]" type="hidden" value="<?php echo $row_rs_carcheq['Prv_Cod']; ?>"><?php echo $row_rs_carcheq['Prs_Ape'].' '.$row_rs_carcheq['Prs_Nom']; ?></td>
	    <td align="right"><input <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'readonly';} ?> name="datos[<?php echo $fila; ?>,4]" type="text" id="datos[<?php echo $fila; ?>,4]" value="<?php echo $row_rs_carcheq['Che_Num']; ?>" size="10" maxlength="10"></td>
	    <td align="right"><input <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'readonly';} ?> name="datos[<?php echo $fila; ?>,5]" type="text" id="datos[<?php echo $fila; ?>,5]" value="<?php echo number_format($row_rs_carcheq['Che_Val'],2,'.',''); ?>" size="6" maxlength="10" onKeyUp="cal_total_cheque(5)" style="text-align:right"></td>
		<td><input <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'readonly';} ?> name="datos[<?php echo $fila; ?>,8]" type="text" id="datos[<?php echo $fila; ?>,8]" onBlur="validar_fecha2(this)" onKeyUp="mascara(this,'-',patron,true);" value="<?php if ($row_rs_carcheq['Che_Fec'] != 0){ echo $row_rs_carcheq['Che_Fec']; }?>" size="7" maxlength="10"></td>
	    <td style="display: none;"><input <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'readonly';} ?> name="datos[<?php echo $fila; ?>,6]" type="text" id="datos[<?php echo $fila; ?>,6]" onBlur="validar_fecha2(this)" onKeyUp="mascara(this,'-',patron,true);" value="<?php if ($row_rs_carcheq['Che_Cob'] != 0){ echo $row_rs_carcheq['Che_Cob']; }?>" size="7" maxlength="10"></td>
	    <td><input <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'readonly';} ?> name="datos[<?php echo $fila; ?>,7]" type="text" id="datos[<?php echo $fila; ?>,7]" value="<?php echo $row_rs_carcheq['Che_Obs']; ?>" size="7" maxlength="20">
	      <input <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'readonly';} ?> name="datos[<?php echo $fila; ?>,9]" type="hidden" id="datos[<?php echo $fila; ?>,9]" value="<?php echo $row_rs_carcheq['Che_Cod']; ?>"></td>		
	    <td align="center">
                <?php if ($row_rs_carcheq['Che_Est'] == 'C'){echo 'Cobrado el '.$row_rs_carcheq['Che_Cob'];}else{ ?>
        <button type="button" class="btn btn-danger delete" title="Eliminar definitivamente el Cheque" onclick="quitar_fila_st(this)">
                    <i class="icon-trash icon-white"></i>
                    <span>Eliminar</span>
        </button>
                <?php } ?>
        </td>
	    </tr>
      <?Php
	 }//FIn del if ($row_rs_carcheq['Che_Est'] == 'A')
	 elseif($row_rs_carcheq['Che_Est'] == 'A')//Esto borrar
	 {
	  ?>  
	  <tr>
	    <td><font color="<?php echo $rojo;?>"><?Php echo $row_rs_carcheq['Pld_Des']; ?></font></td>
	    <td><font color="<?php echo $rojo;?>"><?php echo $row_rs_carcheq['Prs_Ape'].' '.$row_rs_carcheq['Prs_Nom']; ?></font></td>
	    <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row_rs_carcheq['Che_Num']; ?></font></td>
	    <td align="right"><font color="<?php echo $rojo;?>"><?php echo round($row_rs_carcheq['Che_Val'],2); ?></font></td>
	    <td><font color="<?php echo $rojo;?>"><?php if ($row_rs_carcheq['Che_Fec'] != 0){ echo $row_rs_carcheq['Che_Fec']; }?></font></td>
	    <td><font color="<?php echo $rojo;?>"><?php if ($row_rs_carcheq['Che_Cob'] != 0){ echo $row_rs_carcheq['Che_Cob']; }?></font></td>
	    <td><font color="<?php echo $rojo;?>"><?php echo $row_rs_carcheq['Che_Obs']; ?></font></td>
	    <td align="center">&nbsp;</td>
	    </tr>
	<?php 
	 }//FIn del else if ($row_rs_carcheq['Che_Est'] == 'A')
	} while ($row_rs_carcheq = $obBD_con1->fetch_assoc($rs_carcheq)); ?>
	</tbody>
    <tfoot>
      <tr>
        <td>&nbsp;</td>
        <td class="LetraNegra">&nbsp;</td>
        <td class="LetraNegra"><strong>TOTAL</strong></td>
        <td class="LetraNegra" align="right"><input name="txt_total" type="text" id="txt_total" size="6" readonly="true" style="text-align:right" value="<?Php echo number_format($total,2,'.','');?>" /></td>
        <td class="LetraNegra">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </tfoot>  
    </table>
    <?php  echo barra_estado($total_rs_carcheq);  ?>
    <br />
    <table width="207" border="0" cellpadding="0" cellspacing="0">
	  <tr>
	    <td width="103"><button type="button" class="btn btn-primary start" title="Beneficiario" id="button1" name="button1"> <i class="icon-user icon-white"></i> <span>Beneficiario</span></button></td>
	    </tr>
	  </table>
      <?Php	   
    if ($anulada > 0)
        {		
            $com_leyenda[4]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?> 
	</FIELDSET>
	<br>	
	<table width="312" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		    <td width="110"><button type="button" class="btn btn-inverse fileinput-button" title="Atrï¿½s" onclick="campos_hide(this.form, 'txt_busqueda*op_opciones*cmb_mes*Pec_Cod*hdd_volver*bancos', '<?php echo $volver_busqueda.'*'.$volver_opciones.'*'.$volver_mes.'*'.$Pec_Cod.'*1*'.$volver_bancos;?>')"> <i class=" icon-arrow-left icon-white"></i> <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
            <input id="nfilas" name="nfilas" type="hidden" value="<?php echo $fila; ?>">
			  <input id="asientos" name="asientos" type="hidden" value="<?php echo $asientos; ?>">
			  <input id="bt_save" name="bt_save" type="hidden" value="Grabar">
            <!-- <input id="hdd_save" name="hdd_save" type="hidden" value="oculto">-->
            <input name="cantmodal" id="cantmodal" type="hidden" value="2">
            </td>
		    <td width="202"><button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_cheques(this.form,<?php echo $asi_cod; ?>,<?php echo $asi_val; ?>)"> <i class="icon-book icon-white"></i> <span>Guardar</span></button>
             <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>" />
		          <input name="txt_busqueda" id="txt_busqueda" type="hidden"  value="<?php echo $volver_busqueda; ?>" />
		          <input name="op_opciones" id="op_opciones" type="hidden"  value="<?php echo $volver_opciones; ?>" />
		          <input name="cmb_mes" id="cmb_mes" type="hidden"  value="<?php echo $volver_mes; ?>" />
            </td>
		    </tr>
		  </table>
<br>	                       
	</form>
    
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">	
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name="form2" id="formSearch">
<?Php noEnterSubmit(); ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>			
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">B&uacute;squeda de Proveedor</label>
    </LEGEND>
    <table width="450" border="0">
        <tr>
          <td width="205"><input name="op_opciones2" type="radio" value="d" checked onClick="setfocus(this.form.buscta)">
          <span class="LetraNegra"><strong>Apellido</strong></span></td>
          <td width="266"><input name="op_opciones2" type="radio" value="c" onClick="setfocus(this.form.buscta)">
            <span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span></td>
        </tr>
</table>
<table width="554" height="36" border="0" cellpadding="0" cellspacing="0" >
    <tbody id="tbusqueda">
      <tr>
        <td width="85" height="28" class="BarraBusqueda"><div align="right"><strong>B&uacute;squeda</strong>:</div></td>
        <td width="359" class="BarraBusqueda"><input name="buscta" type="text" id="buscta" size="50" maxlength="50" onKeyPress="if (trim(document.getElementById('buscta').value) != ''){ var form = document.getElementById('formSearch');enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod='+document.getElementById('buscta').value+'&op_opciones2=ver'+form.elements['op_opciones2'].value+'&cod_num='+<?php echo $row_rs_cabcomp['Com_Cod']; ?>+'&cod_prv='+<?php echo $row_rs_cabcomp['Prv_Cod']; ?>+'&com_fec=<?php echo $row_rs_cabcomp['Com_Fec']; ?>&varios_prov=<?Php echo $varios_prov; ?>','busqueda') }"></td>
        <td width="110" align="center">
        <button type="button" class="btn btn-success" title="Buscar" onClick="var form = document.getElementById('formSearch');ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod='+document.getElementById('buscta').value+'&op_opciones2='+form.elements['op_opciones2'].value+'&cod_num='+<?php echo $row_rs_cabcomp['Com_Cod']; ?>+'&cod_prv='+<?php echo $row_rs_cabcomp['Prv_Cod']; ?>+'&com_fec=<?php echo $row_rs_cabcomp['Com_Fec']; ?>&varios_prov=<?Php echo $varios_prov; ?>','busqueda')"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
        </td>
      </tr>
    </tbody>
</table>
    <div id="busqueda">
    </div>
	</FIELDSET>
    </td>
</tr>
</table>
</form>    	
</div>    
    
<?php }  
}//FIn else
	?></td>
  </tr>
</table>
</div>
    <div id="bgtransparent" class="bgtransparent" style="display:none" onclick="closeModal()">
    </div>
    <div id="bgmodal"  class="bgmodal"  style="display:none">  
     <div id="ajax_modal"></div>
    </div>
<script type="text/javascript" src="../VALIDACIONES/tes_par_cheque.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
<?Php
	/* 
	* Control para ocultar el detalle de las filas 
	*/
	if($total_rs_cabcompr != 0)
	{
		ocultarDetalle($total_rs_cabcompr);
	}
?>
</BODY></HTML>
<?Php
/**
* Cierra conexiones y libera consultas
*/
@$obBD_con1->free_result($rs_periodos);
@$obBD_con1->free_result($rs_cabcompr);
@$obBD_con1->free_result($rs_cabcomp);
@$obBD_con1->free_result($rs_periodo);
@$obBD_con1->free_result($rs_cuentas);
@$obBD_con1->free_result($rs_combo);
@$obBD_con1->free_result($rs_prov_cheques);
@$obBD_con1->free_result($rs_carcheq);
@$obBD_con1->free_result($rs_arrmax);
@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>