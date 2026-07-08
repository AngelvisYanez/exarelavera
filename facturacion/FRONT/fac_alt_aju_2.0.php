<?php	  
/**
* Descripci�n: Registro de Ajustes
* Fecha de actualizaci�n:	02-06-11
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	03-07-12
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	03-09-2013
* Desarrollador:	Fabian Gallardo
*/	

require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/fac_log_aju.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  
require_once('../../Librerias/postclass.php');

/**
* Creaci�n del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creaci�n del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;
/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");

/** 
* Llamado a componente ajax 
*/
require_once("../COMPONENTES/ajax_con_ctaAjuste.php");

if (isset($ajax_mov))
{ 
	/**
	* Consulta los tipos de ajustes: compras-inventario-baja etc 
	*/
	$rs_tpaj= $obBD_con1->getArrayConsulta(1050, $Ses_Emp_Cod.'*'.$Tia_IoE, $obBD_conexion);
?>
    <select name="Tia_Cod" id="Tia_Cod" >
      <option value="">Seleccione...</option>
      <?php  foreach($rs_tpaj as $row_rs_tpaj) {  	 ?>
      <option   value="<?Php echo $row_rs_tpaj['Tia_Cod']; ?>">
      <?php  echo $row_rs_tpaj['Tia_Des'];		?>
      </option>
      <?php	}?>
    </select>
<?Php
	unset($rs_tpaj);
	exit();
}

/**
* Evitar el reenvio de formularios 
*/
if ($thisPost->postBlock($_POST['postID'])) 
{
	if (isset($hdd_save) && !isset($hdd_volver))
	{
		/**
		* Inicio de la transaccion
		*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/** 
		* consulto el codigo secuencial del Tac_Cod 
		*/
		$rs_codigo = $obBD_con1->getRowConsulta(1055, $Tia_Cod, $obBD_conexion);
		
		if (count($rs_codigo) <= 0 ){ 
			$codigo_aj = 1;	
		}else{
			$codigo_aj = $rs_codigo['Aju_Sec'] + 1;	
		}
		/**
		* Objeto que contiene los parametros
		*/
		$parametros = $hoy.'*'.date ("H:i:s").'*'.$Aju_Det.'*'.$Aju_Obs.'*'.$Aju_Num.'*'.$codigo_aj.'*'.$Tia_Cod.'*'.$Rec_Cod.'*'.$Vnd_Cod;
		/**
		* Registrando la cabcera de Ajuste 
		*/
		$obBD_con1->operacionobBD(1051, $parametros, $obBD_conexion);
		$Rcb_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
		
		
		
		foreach ($datos as $puntero => $item)
		{
		    $cant++;
		    $param[]=$item;
		 
		    if ($cant==5)
		    {
				$cant=0;
				/**
				* Objeto que contiene los parametros
				*/						
				$parametros = $Rcb_Cod.'*'.$param[2].'*'.$param[0].'*'.$param[3].'*'.$param[4];
				/**
				* Inserta los datos en la tabla detalle de ajuste 
				*/
				$obBD_con1->operacionobBD(1052, $parametros, $obBD_conexion);	
				
				/**
				* Consulta si el producto es un bien  
				*/
				$rs_adquisicio = $obBD_con1->getRowConsulta(1037, $param[2], $obBD_conexion);
				
				$Iva_Cod = $rs_adquisicio['Iva_Cod'];
				
				/**
				* Consulta el Stock 
				*/
				$rs_conpro = $obBD_con1->getRowConsulta(1206, $param[2], $obBD_conexion);
				
				if (count($rs_adquisicio) <> 0)
				{	
				/**
				* Verifica si el movimiento es un ingreso o egreso 
				*/
					if($Tia_IoE == 'I')
					{	
						/**
						* Objeto que contiene los parametros
						*/
						$parametros = '0'.'*'.$Rcb_Cod.'*'.'0'.'*'.'0'.'*'.$param[2].'*'.$hoy.'*'.date ("H:i:s").'*'.$param[0].'*'.'0'.'*'.'0'.'*'.$param[3].'*'.'0'.'*'.$param[4].'*'.'0'.'*'.$Iva_Cod;
						/**
						* REGISTRO EN EL KARDEX  SI ES UN INGRESO
						*/					
						$obBD_con1->operacionobBD(1035, $parametros, $obBD_conexion);
						$tstock= $rs_conpro['stock'] + $param[0];
						//echo $rs_conpro['stock'].' *** '.$param[0];
					}
					elseif($Tia_IoE== 'E')
					{	
						/**
						* Objeto que contiene los parametros
						*/
						$parametros = '0'.'*'.$Rcb_Cod.'*'.'0'.'*'.'0'.'*'.$param[2].'*'.$hoy.'*'.date ("H:i:s").'*'.'0'.'*'.$param[0].'*'.$param[3].'*'.'0'.'*'.$param[4].'*'.'0'.'*'.'0'.'*'.$Iva_Cod;
						/**
						* REGISTRO EN EL KARDEX SI ES UN EGRESO
						*/
						$obBD_con1->operacionobBD(1035, $parametros, $obBD_conexion);
						$tstock= $rs_conpro['stock'] - $param[0];
						//echo $rs_conpro['stock'] & ";"& $param[0];
					}
					/**
					* Objeto que contiene los parametros
					*/
					$parametrosStk = $tstock.'*'.$param[2].'*'.$Ses_Suc_Cod;
					//echo "<br>".$tstock.'*'.$param[2].'*'.$Ses_Suc_Cod;
				}								
				
				/**
				* Actualizo el Stock 
				*/
				$obBD_con1->operacionobBD(1204, $parametrosStk, $obBD_conexion);			
				
				unset($param);							
		    }		  		  
		}		
		/**
		* FInaliza la transaccion
		*/										
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);	
	}
}

if (isset($txt_busqueda))
{
	if ($op_opciones=='d')
	{
		/**
		* Cargado de los datos de la persona-proveedor
		*/
		$rs_buspro = $obBD_con1->getArrayConsulta(487, $txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	}
	else{
		/**
		* Cargado de los resultados de del numero-proveedor
		*/
		$rs_buspro = $obBD_con1->getArrayConsulta(702, $txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/fac_val_aju.js?x=20"></script>
        <!--Librerias para interfaz -->       
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
<?Php 
/** 
* Impresion automatica del comprobante de ajuste
*/
if (isset($hdd_save) && !isset($hdd_volver))
{
?>
<script language="javascript">windows('fac_pri_aju_1.0.php?Aju_Cod=<?Php echo $Rcb_Cod; ?>','', 800,600,'yes', 'yes', 'yes', 'no');
</script> 
<?Php
}//Fin del if (isset($hdd_save) && !isset($hdd_volver))
?>
<table width="98%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Ajustes de Productos </td>
</tr>
<tr>
    <td height="400" valign="top">
    <?Php
	/**
	* Consulta del vendedor en base al codigo de la persona
	*/
	$rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
	
	if (count($rs_vendedor) == 0)
	{
		echo error_alerta (" Ud. no esta autorizado para realizar ajustes", 2);
		echo "</ td></ tr></ table>"; 
		exit();
	}
	?>    
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"> 	
	<?php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
    </form>
<?Php  
if(isset($txt_busqueda))
{ ?>
	<br>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la Busqueda</label>
	</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>
		  <tr>
			<th width="6%" align="center"><strong>C&oacute;d. Int.</strong></th>
			<th width="10%" align="center"><strong>C&eacute;dula/R.U.C.</strong></th>
			<th width="78%" align="center"><strong>Proveedor</strong></th>
			<th width="4%">&nbsp;</th>
		  </tr>
         </thead>
         <tbody> 
		  <?php  
		  if(count($rs_buspro)!=0)
		  {
		  	foreach($rs_buspro as $row_rs_buspro){  ?>
		  <form name='frm_personal' method='post' action="<?php echo $_SERVER['PHP_SELF']; ?>">
		  <tr>
			<td height="73%" align="center"><?php echo $row_rs_buspro['Prv_Cod']; ?></td>
			<td align="center"><?php echo $row_rs_buspro['Prs_Ced']; ?></td>
			<td align="left">&nbsp;<?php echo marcar_cadena($txt_busqueda,$row_rs_buspro['Prs_Ape'].' '.$row_rs_buspro['Prs_Nom'],'#FFFF00', 1) ?></td>
			<td width="4%" align="center">
		
			<input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buspro['Per_Cod'];?>">
			<input type="hidden" name="Rec_Cod" id="Rec_Cod" value="<?Php echo $row_rs_buspro['Prv_Cod'];?>">
			<input type="hidden" name="op_opciones" id="op_opciones" value="<?Php echo $op_opciones;?>">
			<input type="hidden" name="txt_bus" id="txt_bus" value="<?Php echo $txt_busqueda;?>">
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
                <i class=" icon-arrow-right icon-white"></i>
            </button>
			</td>
		  </tr>
		  </form>
		  <?Php } 
		  }else{?>
              <tr><td>&nbsp;</td>
                <td>&nbsp;</td>
                <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
     	  <?php }?>
          </tbody>
	  </table>
	  <?php echo barra_estado(count($rs_buspro));?>
	</FIELDSET>  
<?php 
} //Fin del if(isset($txt_busqueda))

if (isset($codigo) && !isset($hdd_volver))
{
	/**
	* resultado de los datos del proveedor
	*/
	$row_rs_personal = $obBD_con1->getRowConsulta(708, $Rec_Cod, $obBD_conexion);
	
?>
<form action="<?Php $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
<?Php $thisPost->startPost(); ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Proveedor</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td width="12%" class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
	<td width="88%" class="LetraNegra">&nbsp;<?php echo $row_rs_personal['Prs_Ced']?>
	  <input name="Rec_Cod" id="Rec_Cod" type="hidden" value="<?Php echo $Rec_Cod;?>">
      <input type="hidden" id="Vnd_Cod" name="Vnd_Cod" value="<?Php echo $rs_vendedor['Vnd_Cod']; ?>" />
      </td>
	</tr>
	<tr>	
	<td width="12%" class="Etiqueta1">Nombre:</td>
	<td class="LetraNegra">&nbsp;<?php echo $row_rs_personal['Prs_Ape'].' '.$row_rs_personal['Prs_Nom']; ?></td>
	</tr>	
</table>
</FIELDSET>
	<FIELDSET>
	  <LEGEND>
		<label class="Titulos2">Generales</label>
	  </LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		  <tr>
		    <td class="Etiqueta1"><span class="Asterisco">*</span> Movimiento: </td>
		    <td class="LetraNegra">
            <select name="Tia_IoE" id="Tia_IoE" onchange="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_mov&Tia_IoE='+this.value,'div_ajuste')">
		      <option value="">Seleccione...</option>
              <option value="I">Ingreso</option>
              <option value="E">Egreso</option>
		      </select></td>
		    </tr>
		  <tr>
			<td class="Etiqueta1"><span class="Asterisco">* </span>Concepto: </td>
			<td class="LetraNegra">
            <div id="div_ajuste">
            <select name="Tia_Cod" id="Tia_Cod" >
              <option value="">Seleccione...</option>
              <?php  if(count($rs_tpaj)>0) foreach($rs_tpaj as $row_rs_tpaj) {  ?>
              <option   value="<?Php echo $row_rs_tpaj['Tia_Cod']; ?>">
              <?php echo $row_rs_tpaj['Tia_Des']; ?>
              </option>
              <?php	} ?>
            </select>
            </div>
            </td>
		  </tr>
		 <tr style="display:none">	
		   <td width="12%" class="Etiqueta1">No. Documento:</td>
		   <td class="LetraNegra"><label><input name="Aju_Num" type="text" id="Aju_Num" size="15" maxlength="15" onBlur="var formato=/^[0-9]{3}-[0-9]{3}-[0-9]{7}$/;	
	  validar_formato(this,formato,'Los n&uacute;meros de las facturas deben cumplir el siguiente formato: 999-999-9999999\nEjemplo: 001-001-0000586');"></label></td>
		 </tr>
		 <tr>
		  <td class="Etiqueta1"><span class="Asterisco">*</span> Concepto:</td>
		  <td class="LetraNegra"><textarea name="Aju_Det" cols="80" rows="3" id="Aju_Det"></textarea></td>
		 </tr>
		 <tr>
		  <td class="Etiqueta1">Observaci&oacute;n:</td>
		  <td class="LetraNegra"><textarea name="Aju_Obs" cols="80" rows="3" id="Aju_Obs"></textarea></td>
		 </tr>	
	  </table>
	</FIELDSET>
	<FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Detalle</label>
	 </LEGEND>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader02">
     <thead>
		<tr>     
		  <th width="5%" align="center">Cant.</th>
		  <th width="60%" align="center">Descripci&oacute;n</th>
		  <th width="20%" align="center">P. Unitario </th>
		  <th width="11%" align="center">Importe</th>
		  <th width="4%">&nbsp;</th>
		</tr>
     </thead>   
   	  <tbody id="c_contenido">  
	  </tbody>
      <tfoot>
	  <tr height="30">
		<td >&nbsp;</td>
		<td >&nbsp;</td>
		<td align="right"><strong>TOTAL:&nbsp;</strong></td>
		<td align="right"><input type="text" id="txt_total" readonly="" name="txt_total" size="6" style="text-align:right"></td>
		<td id="id_msj" align="right">&nbsp;</td>
	  </tr>
      </tfoot>
	 </table>
     <br />
	 <table width="117" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="117">
		  <input id="nfilas" name="nfilas" type="hidden" value="0">
          <input name="cantmodal" id="cantmodal" type="hidden" value="2" />
          <button type="button" name="button1" id="button1" class="btn btn-success fileinput-button" title="Buscar Producto">
           <i class="icon-plus icon-white"></i>
           <span>Producto</span>
           </button>              
		 </td>
	  </tr>
	 </table>
	</FIELDSET>
    <br />
	<table width="222" height="16%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="110">
        <button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="campos_hide(this.form,'<?Php echo "txt_busqueda*op_opciones*hdd_volver";?>','<?Php echo $txt_bus.'*'.$op_opciones.'*'.$volver_op; ?>')">
                    <i class=" icon-arrow-left icon-white"></i>
                    <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       </button>
        </td>
        <td width="112">
        <button type="button" class="btn btn-primary start" title="Guardar" onClick= "validar_requeridos(this.form, 'Tia_IoE*Tia_Cod*Aju_Det', 1)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button>        
            <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
        </td>
      </tr>
    </table>
	<br>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">
	<table id="tb_busquedaCta" width="100%" border="0" cellpadding="0" cellspacing="0">
	 <tr>
	   <td><?Php 
			 /**
			 * C= buscador con cargado en combos 
			 */
			$tipo_busc = 'F'; 
			$Capa = 'busqueda_f';
			$Nombre_Buscador = 'buscta';//Cuadro de texto
			$Nombre_Opciones = 'op_opciones';//Option	
			//$Pla_Cod=2;	
			?>
			<?Php require_once('../COMPONENTES/com_con_ctaAjuste.php'); ?>
		</td>
	  </tr>
	</table>	
	</div>
	<br>
</form>
		</td>
	  </tr>
	</table>
<?php } 
?>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_aju.js?x=2"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>