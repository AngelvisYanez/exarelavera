<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* @abstract Permite registrar los cheques 
* @author Lewis Chimarro
* @version 1.0
* Fecha de creaci�n  2012-07-19
* Fecha de actualizaci�n  2012-04-25
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
* Cracion del objeto mysql para las consultas 
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
* Cargado AJAX de los resultados de la b�squeda
*/
if (isset($buscod))
{
		if ($op_opciones=='d')
		{
			/**
			* Cargado de los resultados de la busqueda por descripcion de la cuenta
			*/
			$rs_buspro = $obBD_con1->getArrayConsulta(301,trim($buscod).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
		elseif ($op_opciones=='c')
		{
			/**
			* Cargado de los resultados de la busqueda por codigo de la cuenta
			*/
			$rs_buspro = $obBD_con1->getArrayConsulta(302,trim($buscod).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
                //cambio
//		$row_rs_buspro = $obBD_con1->registros();
//		$total_rs_buspro = $obBD_con1->numregistros();
                $row_rs_buspro = $rs_buspro;
		$total_rs_buspro = count($rs_buspro);
	/**
	* Consulta los bancos con su respectivo asiento contable 
	*/	
	$rs_combo = $obBD_con1->getArrayConsulta(304, $cod_num, $obBD_conexion);
	//$row_rs_combo = $obBD_con1->registros();
	//$total_rs_combo = $obBD_con1->numregistros();
	
	/**
	* Creacion del Array para luego ser procesado	
	*/
        
        
	//do {
        foreach ($rs_combo as $row){
		$ban_cod[]=$row['Banasi'];
		$ban_des[]=$row['Pld_Des']." ($".number_format($row['Asi_Val'],2,'.','').")";
		$asi_val[]=number_format($row['Asi_Val'],2,'.','');
        }
	//} while ($row_rs_combo = mysqli_fetch_assoc($rs_combo));
//        foreach ($row_rs_carrera as $row){
//            
//        }
        
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
              //do { 
               foreach ($row_rs_buspro as $row){   
           ?>
      <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
        <td align="center"><?php echo $row['Prv_Cod']; ?></td>
        <td align="left"><?php echo $row['Prs_Ced']; ?></td>
        <td align="left"><?php echo $row['Prs_Ape'].' '. $row['Prs_Nom']; ?></td>
        <td align="center">
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="nueva_fila_cheque('contenido',<?php echo $row['Prv_Cod']; ?>,'<?php echo $row['Prs_Ape'].' '.
		$row['Prs_Nom']; ?>',<?php echo $ban_cod; ?>,<?php echo $ban_des; ?>,<?php echo $cod_prv; ?>, '<?Php echo $com_fec; ?>',<?Php echo $asi_val; ?>,'<?Php echo $varios_prov; ?>')">
		        <i class="icon-arrow-right icon-white"></i>
            </button>           
        </td>
        </tr>
      <?php 
               }
            //} while ($row_rs_buspro = $obBD_con1->fetch_assoc($rs_buspro));
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
		
		foreach ($datos as $puntero => $item)
		{
			$cant++;
			$param[]=$item;
			if ($cant==10)
				{
					$cant=0;
					$obBD_con1->grabarv_registros(sentencias_che(190,$obBD_con1->parametros($param[3].'*'.$param[0].'*'.$param[1].'*'.$param[4].'*'.$param[7].
									'*'.$param[5].'*'.$param[8].'*'.$param[6].'*'.$param[9])), $obBD_conexion->conexion);
					unset($param);
				}
		}
		
		$mostrar=true;
		/** 
		* Fin del la transacci�n
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}// Fin de Guardado
	} //fin del POSTH

	/**
	* Cargado de los datos de la cabecera 
	*/
	if (trim($txt_busqueda) !="") 
	{	
//		if ($op_opciones == "d")
//		{	
			//$rs_cabcompr = $obBD_con1->getArrayConsulta(311,'proveedore'.'*'.trim($txt_busqueda).'*'.'D'.'*'.
									//$Pec_Cod.'*'.'Prv_Cod'.'*'.$cmb_mes.'*'.$bancos, $obBD_conexion);
                    $rs_cabcompr = $obBD_con1->getArrayConsulta(343,trim($txt_busqueda).'*'.$Pec_Cod.'*'.$bancos.'*'.$op_opciones.'*H', $obBD_conexion);
//		}//Fin del if ($op_opciones == "d")
//		else 
//		{		
//			/**
//			*  Control para busqueda mensual  
//			*/
//			$mes_array = explode('-', trim($txt_busqueda));
//			if (count($mes_array)==2)
//			{
//				$Par_Fec = "AND MONTH(Com_Fec)=$mes_array[0]";
//				$txt_busqueda = $mes_array[1];//$mes[1] es el numero del comprobante
//			}
//			$rs_cabcompr = $obBD_con1->getArrayConsulta(305,'proveedore'.'*'.trim($txt_busqueda).'*'.'D'.'*'.
//									$Pec_Cod.'*'.'Prv_Cod'.'*'.$Par_Fec, $obBD_conexion);
//		}//Fin del else if ($op_opciones == "d")			
		$row_rs_cabcompr = $rs_cabcompr;
		$total_rs_cabcompr = count($rs_cabcompr);
		
	}//Fin del if (trim($txt_busqueda) !="")
	else
	{
		/**
		* Control para mostrar la descripci�n del comprobante cuando se graba el cheque
		*/	
		if (isset($codigo))
		{
			/**
			* Consulta los datos del comprobante 
			*/				
			$rs_cabcomp = $obBD_con1->consulta(sentencias_che(149,$obBD_con1->parametros('proveedore'.'*'.$codigo.'*'.'D'.'*'.
									$Pec_Cod.'*'.'Prv_Cod')), $obBD_conexion->conexion);
			$row_rs_cabcomp = $obBD_con1->registros();
			$total_rs_cabcomp = $obBD_con1->numregistros();
			
			if (isset($mostrar))
			{
			/**
			 * Cargado de los cheques seg�n el n�mero de comprobante de egreso
			 */
			$rs_concomp = $obBD_con1->getArrayConsulta(143,$codigo,$obBD_conexion);
			//$row_rs_concomp = $obBD_con1->registros();
			//$total_rs_concomp = $obBD_con1->numregistros();		
			}
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
		<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
		<TITLE><?Php echo "Cheques Registrar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../VALIDACIONES/tes_val_cheque.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
  	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>		
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; registrar cheques <?Php echo $periodo; ?></td>
  </tr>
	<tr>
      <td height="389" align="left" valign="top">
<form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name= "form1">
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
	    <td width="84" align="center"><button type="button" class="btn btn-success" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
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
		<table width="501" height="27" border="0">
          <tr>
            <td width="178" height="23" class="LetraNegra"><input name="op_opciones" type="radio" value="d"  <?php if(!isset($op_opciones)) {echo "checked";} if($op_opciones=="d") {echo "checked";} ?>  onClick="document.getElementById('cmb_mes').disabled=false; setfocus(form1.txt_busqueda)">
              Apellidos</td>
            <td width="225" class="LetraNegra"><input type="radio" name="op_opciones" value="r"  <?php if($op_opciones=="r") {echo "checked";} ?>  onClick="document.getElementById('cmb_mes').disabled=true; setfocus(form1.txt_busqueda)">
              No. de Comprobante </td>
            <td width="249" class="LetraNegra"> Seleccione Banco:&nbsp;
                <select style="display:none;" name="cmb_mes" id="cmb_mes" <?php if($op_opciones=="r") {echo "disabled";} ?>>
                  <option value="" ><< TODOS >></option>
                  <?Php
				  for ($i=1;$i<=12;$i++)
				  { ?>
                  <option <?php if ($cmb_mes == ("AND MONTH(Com_Fec)=".$i)){ echo "selected"; } ?><?php //if ($i == $mes){ echo "selected"; } ?>  value="<?Php echo "AND MONTH(Com_Fec)=$i"; ?>"><?php echo mes($i, 1) ?></option>
                  <?Php
				  } ?>
              </select>
              <select name="bancos" id="bancos" >
<?php
    $rs_bancos = $obBD_con1->getArrayConsulta(339,$Ses_Emp_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
                                  <option <?php if($bancos==$row['Pld_Cod']) {echo "selected";} ?> value="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
<?php
        }
    }
?>
                              </select>
            </td>
          </tr>
        </table>
		<table width="545" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="77" class="BarraBusqueda"><div align="right" >Busqueda:</div></td>
			  <td width="350" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>" size="50" maxlength="50"></td>
			  <td width="118"><div align="center">
				<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
				<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
				<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">
				<input name="hdd_save2" type="hidden" id="hdd_save2">
                <button type="button" class="btn btn-success" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
			  </div></td>
			</tr>
	    </table>
		</FIELDSET>
</form>
<?php 
if(isset($txt_busqueda))
{ ?>	
	<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
	<table width="100%" border="0" cellpadding="1" cellspacing="0" class="fixedHeader01">
    <thead>
        <tr>
          <th width="5%">Cod.Int.</th>
          <th width="9%">Generaci&oacute;n</th>
          <th width="11%">No. Compr </th>
          <th width="10%">C&eacute;dula/R.U.C.</th>
          <th width="29%">Proveedor</th>
          <th width="10%">Fecha</th>
		  <th width="10%">Valor</th>
		  <th width="5%">&nbsp;</th> 
                  <th width="5%">&nbsp;</th> 
        </tr>
     </thead>
     <tbody>
		<?php
	if ($total_rs_cabcompr > 0) 
	{		 
		$i=0; 				
		//do{
                foreach ($row_rs_cabcompr as $row)
                {
                    
                	$i++;
		  	if($row['Com_Est']=='I')
	  		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';} 
			  
			  $row_verifica = $obBD_con1->getArrayConsulta(143, $row['Com_Cod'], $obBD_conexion);			  
			  ?>
		<tr>
		  <td align="center"><?php echo $row['Com_Cod']; ?></td>
		  <td align="center"><font color="<?php echo $rojo; ?>">
		    <?Php  if ($row['Com_Gen'] == 'M') echo "Manual"; else echo "Autom&aacute;tico";
		  ?></font></td>		  
		  <td align="center"><font color="<?php echo $rojo; ?>">
		    <?php 
	  	list($ann, $mes, $dia) = split('[/.-]', $row['Com_Fec']);
		  echo $row['Tia_Abr'].'-'.$mes.'-'.$row['Com_Num']; ?>
		    </font></td>
		  <td><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Prs_Ced']; ?></FONT></td>
		  <td><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Prs_Ape']." ".$row['Prs_Nom']; ?></FONT></td>
		  <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Com_Fec']; ?></FONT></td>			
		  <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Com_Val']; ?></FONT></td>
		  <td align="center">
                      <button type="button" class="btn btn-info btn-mini" onclick="Muestra_Aparecer();ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax=true&ComCod=<?php echo $row['Com_Cod']; ?>','ajax_modal')"><i class="icon-info-sign icon-white"></i></button>
                  </td><td>
 <?php if ($row['Com_Est']=='A') { 
		  if (count($row_verifica) == 0)
		  {
		  ?>
		    <form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
		      <input name="codigo" id="codigo" type="hidden"  value="<?php echo $row['Com_Cod']; ?>">
		      <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
		      <input name="volver_busqueda" id="volver_busqueda" type="hidden"  value="<?php echo $txt_busqueda; ?>">
		      <input name="volver_opciones" id="volver_opciones" type="hidden"  value="<?php echo $op_opciones; ?>">
                      <input name="volver_bancos" id="volver_opciones" type="hidden"  value="<?php echo $bancos; ?>">
		      <input name="volver_mes" id="volver_mes" type="hidden"  value="<?php echo $cmb_mes; ?>">               
		      <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
		        <i class="icon-arrow-right icon-white"></i>
		        </button>
		      </form>    					
		    <?php
		  		}//FIn del if (count($row_verifica) == 0)
				else
				{ ?>
		    <img src="../../mascaras/model1/imagenes/32x32/dinero.png" title="Comprobante contiene cheque(s) relacionados" />
		    <?Php	
					}
		  }else { echo "&nbsp;"; } ?>		  		  
		    </td>					
		  </tr>
		<?php	  		
                }
	  	//}while ($row_rs_cabcompr = $obBD_con1->fetch_assoc($rs_cabcompr));  ?>       
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
	<?php echo barra_estado($total_rs_cabcompr); 
	
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>   	  
	</FIELDSET>
<?php 
}//Fin del if(isset($txt_busqueda))
 
/**
* Se ejecuta cuando se selecciona el comprobante 
*/
if ($total_rs_cabcomp >0)
{
	/**
	* Cargado de las cuentas a modificar 
	*/
	$rs_cuentas = $obBD_con1->getArrayConsulta(306,$codigo, $obBD_conexion);
	
	/**
	* Consulta los proveedores a los cuales se les hace varios cheques 
	*/
	$rs_prov_cheques = $obBD_con1->getArrayConsulta(314,$Ses_Emp_Cod,$obBD_conexion);	
	if (count($rs_prov_cheques) > 0)
	{
		//do{
                foreach ($rs_prov_cheques as $row)
				  {
			$varios_prov = $varios_prov.'*'.$row['Prv_Cod'];
                                  }
		//}while($row_rs_prov_cheques = $obBD_con1->fetch_assoc($rs_prov_cheques));	
	}//FIn del if ($total_rs_prov_cheques > 0)		
	?>
	<form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name="form2"> 
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
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="13%" class="Etiqueta1"><input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>"> 
No. Compr: </td>
		<td width="43%" class="LetraNegra">&nbsp;<? list($ann, $mes, $dia) = split('[/.-]', $row_rs_cabcomp['Com_Fec']);
		  echo $row_rs_cabcomp['Tia_Abr'].'-'.$mes.'-'.$row_rs_cabcomp['Com_Num']; ?>
		  
		<td width="9%" class="Etiqueta1">Fecha:</td>
		<td width="35%" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Fec']; ?></td>
	  </tr>
	  <tr>
		<td class="Etiqueta1">Proveedor:</td>
		<td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom']; ?></td>
		<td class="Etiqueta1">Valor:</td>
		<td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Val']; ?></td>
	  </tr>
	  <tr>
		<td height="24" class="Etiqueta1">Concepto:</td>
		<td height="24" colspan="3" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Con']; ?></td>
		</tr>
	  <tr>
		<td height="20" valign="top" class="Etiqueta1">Observaci&oacute;n:</td>
		<td height="20" colspan="3" valign="top" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Obs']; ?>
		 <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo;  ?>"></td>
		</tr>
	</table>
	</FIELDSET>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Cuentas</label>
		</LEGEND>	
		<table width="100%" border="0" class="fixedHeader01">
        <thead>
          <tr>
            <th width="8%" align="center">C&oacute;digo</th>
            <th width="31%" align="center">Cuenta</th>
            <th width="41%" align="center">Glosa</th>
            <th width="10%" align="center">Debe</th>
            <th width="10%" align="center">Haber</th>
          </tr>
         </thead> 
         <tbody>
          <?php if (count($rs_cuentas) > 0)
			 {			 
			  //do{
                          foreach ($rs_cuentas as $row){    
                           ?>
          <tr>
            <td><?php echo $row['Pld_Cdc']; ?></td>
            <td><?php echo $row['Pld_Des']; ?></td>
            <td><?php echo $row['Asi_Glo']; ?></td>
            <td align="right">
                <?php if ($row['Asi_Deh']=='D') { echo $row['Asi_Val']; $total=$total + $row['Asi_Val']; } ?>
            </td>
            <td align="right">
                <?php if ($row['Asi_Deh']=='H') { echo $row['Asi_Val']; $total2=$total2 + $row['Asi_Val'];  } ?>
            </td>
          </tr>
          <?php 
                          }
                         }
               // } while($row_rs_cuentas=$obBD_con1->fetch_assoc($rs_cuentas)); } 
             ?>
         </tbody>
         <tfoot> 
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td align="right"><strong>TOTALES</strong></td>
            <td align="right"><strong><?php echo number_format($total,2); ?></strong></td>
            <td align="right"><strong><?php echo number_format($total2,2); ?></strong></td>
          </tr>
		</tfoot>	
        </table>
         <?php  echo barra_estado($total_rs_cuentas);  ?>
		</FIELDSET>		
		<?php if (!($mostrar))
			{ ?>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Cheques</label>
		</LEGEND>		
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
		<thead>		  
		  <tr>
			<th width="20%">Banco</th>
		<th width="20%">Proveedor</th>
		<th width="12%">N&ordm; Ch.</th>
		<th width="15%">Valor</th>
		<th width="13%">Fec. Elab</th>
		<th style="display: none;" width="0px">Fec. Cobro</th>
		<th width="10%">Observaci&oacute;n</th>
		<th width="10%">&nbsp;</th>  
		  </tr>
        </thead>  
        <tbody id="contenido" style="height:30"> 
           
		</tbody>
        <tfoot>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td align="right"><strong>TOTAL</strong></td>
            <td align="right"><input name="txt_total" type="text" id="txt_total" size="6" readonly="true" style="text-align:right" /></td>
            <td>&nbsp;</td>
            <td style="display: none;" width="0%">&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
          </tfoot>
        </table>
   		<br>
		<table width="207" border="0" cellpadding="0" cellspacing="0">
		  <tr>
		    <td width="103"><button type="button" class="btn btn-primary start" title="Beneficiario" id="button1" name="button1"> <i class="icon-user icon-white"></i> <span>Beneficiario</span> </button></td>
		    </tr>
		  </table>
		<?php
		/**
		* Cargado del array que contiene lo valores maximos de los cheques x asiento. 
		*/
		$rs_arrmax = $obBD_con1->getArrayConsulta(304,$codigo, $obBD_conexion);
		//$row_rs_arrmax = $obBD_con1->registros();
		//$total_rs_arrmax = $obBD_con1->numregistros();
		/**
		* Creacion del Array para luego ser procesado 
		*/
                foreach ($rs_arrmax as $row)
                {
		//do { 
			$codigo_array=explode("*",$row['Banasi']);
			$asi_cod[]=$codigo_array[1];
			$asi_val[]=$row['Asi_Val'];
                }
		//} while ($row_rs_arrmax = $obBD_con1->fetch_assoc($rs_arrmax));
		
		$asi_cod='Array(\'' . implode('\', \'', $asi_cod) . '\')';
		$asi_val='Array(\'' . implode('\', \'', $asi_val) . '\')';
		?>		
		</FIELDSET>
		<br>
		  <table width="312" border="0" cellpadding="0" cellspacing="0">
			  <tr>
			    <td width="110"><button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onclick="campos_hide(this.form, 'txt_busqueda*op_opciones*cmb_mes*Pec_Cod*hdd_volver*bancos', '<?php echo $volver_busqueda.'*'.$volver_opciones.'*'.$volver_mes.'*'.$Pec_Cod.'*1*'.$volver_bancos;?>')"> <i class=" icon-arrow-left icon-white"></i> <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span> </button>
                                <input id="nfilas" name="nfilas" type="hidden" value="0">
				<input id="bt_save" name="bt_save" type="hidden" value="Grabar">
                                <input id="hdd_save" name="hdd_save" type="hidden" value="oculto">
                            <input name="cantmodal" id="cantmodal" type="hidden" value="2">
                </td>
			    <td width="202"><button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_cheques(this.form,<?php echo $asi_cod; ?>,<?php echo $asi_val; ?>)"> <i class="icon-book icon-white"></i> <span>Guardar</span> </button></td>
		      </tr>
		  </table>
		<?php 
		}//Fin del if (!isset($hdd_save))
	}
} ?>
	</form>
<?php 
if (isset($bt_save) && !isset($hdd_volver)) 
{ ?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Cheques</label>
	</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>
          <tr>
            <th width="29%" align="center">Proveedor</th>
            <th width="18%" align="center">Banco</th>
            <th width="11%" align="center">N&ordm; Ch.</th>
            <th width="10%" align="center">Valor</th>
            <th width="12%" align="center">Fecha</th>
            <th width="4%" align="center">&nbsp;</th>
            <th width="4%" align="center">&nbsp;</th>
            <th width="4%" align="center">&nbsp;</th>
            <th width="4%" align="center">&nbsp;</th>
            <th width="4%" align="center">&nbsp;</th>
          </tr>
        </thead>
        <tbody>  
      <?php if(count($rs_concomp) != 0)
		  {
	 //do {
          foreach ($rs_concomp as $row)
	  {
	 	$cod = $row['Che_Cod'];
		$asi = $row['Asi_Cod'];
		$ban = $row['Ban_Cod'];
		$pro = $row['Prv_Cod'];				
	 ?>
          <tr>
            <td><?php echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?></td>
            <td>&nbsp;<?php echo $row['Pld_Des'];?>
              </option></td>
            <td align="right"><?php echo $row['Che_Num']; ?></td>
            <td align="right">&nbsp;<?php echo "$".''.number_format($row['Che_Val'],2,'.',''); ?></td>
            <td align="center"><?php echo $row['Che_Fec']; ?></td>
           <form action="tes_pri_cheque_mac_1.0.php" method="post" name= "form3" target="_blank">
		<td align="center">		
		<input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>">
		<input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
		<input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
		<input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">	
		<input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" title="Ver cheque Banco Machala" width="22" height="35">		
		</td>	
		</form>
            <form action="tes_pri_cheque_pac_1.0.php" method="post" name= "form3" target="_blank">
              <td align="center"><input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>">
                  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
                  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
                  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
                  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" title="Ver cheque Banco Pacífico" width="24" height="23">
              </td>
            </form>
            <form action="tes_pri_cheque_rum_1.0.php" method="post" name= "form3" target="_blank">
              <td align="center"><input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>">
                  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
                  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
                  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
                  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" title="Ver cheque Banco Rumiñahui" width="30" height="15">
              </td>
            </form>
            <form action="tes_pri_cheque_gua_1.0.php" method="post" name= "form3" target="_blank">
              <td align="center"><input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>">
                  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
                  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
                  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
                  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" title="Ver cheque Banco Guayaquil" width="36" height="18">
              </td>
            </form>
             <form action="tes_pri_cheque_pch_1.0.php" method="post" name= "form3" target="_blank">              
              <td align="center">
<input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>">
                  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
                  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
                  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
                  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" title="Ver cheque Banco del Pichincha" width="36" height="30">               
              </td>
             </form> 
          </tr>
          <?php } 
          //while ($row_rs_concomp = $obBD_con1->fetch_assoc($rs_concomp)); 
   } //FIn del if($total_rs_concomp != 0) 
   else 
   { ?>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><?Php echo error_alerta("No hay resultados que mostrar", 2); ?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
 <?php } //Fin del else if($total_rs_concomp != 0) ?>
		 </tbody>
        </table>
		</FIELDSET>
<?php } //if (isset($bt_save)) 
?> 	
</td>
</tr>
</table>
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
			  <td width="205"><input id="op_opciones" name="op_opciones" type="radio" value="d" checked onClick="setfocus(this.form.buscta)">
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input id="op_opciones" name="op_opciones" type="radio" value="c" onClick="setfocus(this.form.buscta)">
				<span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span></td>
			</tr>
		</table>
		<table width="554" height="36" border="0" cellpadding="0" cellspacing="0" >
		<tbody id="tbusqueda">
		  <tr>
			<td width="85" height="28" class="BarraBusqueda"><div align="right"><strong>B&uacute;squeda</strong>:</div></td>
			<td width="359" class="BarraBusqueda"><input name="buscta" type="text" id="buscta" size="50" maxlength="50" onKeyPress="if (trim(document.getElementById('buscta').value) != ''){ var form = document.getElementById('formSearch');enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod='+document.getElementById('buscta').value+'&op_opciones='+form.elements['op_opciones'].value+'&cod_num='+<? echo $row_rs_cabcomp['Com_Cod']; ?>+'&cod_prv='+<? echo $row_rs_cabcomp['Prv_Cod']; ?>+'&com_fec=<? echo $row_rs_cabcomp['Com_Fec']; ?>&varios_prov=<?Php echo $varios_prov; ?>','busqueda') }"></td>
			<td width="110" align="center">
            <button type="button" class="btn btn-success" title="Buscar" onClick="var form = document.getElementById('formSearch');ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod='+document.getElementById('buscta').value+'&op_opciones='+form.elements['op_opciones'].value+'&cod_num='+<? echo $row_rs_cabcomp['Com_Cod']; ?>+'&cod_prv='+<? echo $row_rs_cabcomp['Prv_Cod']; ?>+'&com_fec=<? echo $row_rs_cabcomp['Com_Fec']; ?>&varios_prov=<?Php echo $varios_prov; ?>','busqueda')"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
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
@$obBD_con1->free_result($rs_periodos);
@$obBD_con1->free_result($rs_concomp);
@$obBD_con1->free_result($rs_cabcomp);
@$obBD_con1->free_result($rs_cabcompr);
@$obBD_con1->free_result($rs_periodo);
@$obBD_con1->free_result($rs_detalle);
@$obBD_con1->free_result($rs_cuentas);
@$obBD_con1->free_result($rs_prov_cheques);
@$obBD_con1->free_result($rs_arrmax);
@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>