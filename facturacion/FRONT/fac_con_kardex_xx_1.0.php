<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?Php 
/***
* Descripción: Consulta del kardex 
* Fecha de actualización:	2011-04-28
* Desarrollador: Lewis Chimarro
* <<<  Ite_Cod es equivalente a Pro_Cod  >>>
* Fecha de actualización:	2013-01-08
* Desarrollador: Lewis Chimarro
*/	

require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/fac_log_kardex.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creacion del Objeto de conexion 
*/  
$obBD_conexion = new Class_Log_Conexion_Kar($Ses_Dat_Dis);
/**
* Objeto para la obtención de datos
*/
$obBD_con1 =  new Class_Log_Datos_Kar; 

/**
* Valida la marca al momento de guardar el producto
*/
if(isset($ajax_mar_val))
{  
	/**
	* CREO ESTAS VARIABLES OCULTAS PARA AL AJAX PARA GENERAR UNA CONSULTA 
	*/
	echo "<input  border='0' name='Pro_Cod' type='hidden' id='Pro_Cod'  size='15'  value='$Pro_Cod' />";
	echo "<input  border='0' name='Mar_Des1' type='text' id='Mar_Des1'  size='15'  value='$Mar_Des1' />";
	exit();
}

/**
* ESTE AJAX ES PARA CONSULTAR LOS ITEMS 
*/ 
if($ajax_1==1)
{	
	?>
   	<FIELDSET>
  	<LEGEND>
  	<label class="Titulos2">Resultados de la busqueda</label>
  	</LEGEND>
	<table border="1" width="100%" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
          <th width="4%">Cód. Int.</th>
		  <th width="20%">Categoria</th>
          <th width="20%">Descripción Corta </th>
		  <th width="56%">Descripción Larga</th>
		  <th width="2%">&nbsp;</th>
      </tr>
     </thead>
     <tbody>
	  <?Php 	 
	 if($opciones=='r')
	 {	
	 	/**
		 * Consulta por codigo de barra 
		 */
	 	
		$rs_consulta = $obBD_con1->getArrayConsulta(1040, trim($txt_b).'*'.$Ses_Emp_Cod, $obBD_conexion);
		$total_rs_consulta =  count($rs_consulta);
	 }
	 else
	 {
		/**
		 * Consulta por item 
		 */
		$rs_consulta = $obBD_con1->getArrayConsulta(1041, trim($txt_b).'*'.$Ses_Emp_Cod, $obBD_conexion);
		$total_rs_consulta =  count($rs_consulta);
	 }	 
	 if ($total_rs_consulta>0)
	    { 
			foreach($rs_consulta as $row_rs_consulta){?>
		    <tr>
			   <td align="center"><?Php echo  $row_rs_consulta['Pro_Cod']; ?></td>
			   <td><?Php echo $row_rs_consulta['Cat_Des']; ?></td>
			   <td><?Php echo $row_rs_consulta['Ite_Cor']; ?></td>
			   <td><?Php echo  marcar_cadena($txt_b, $row_rs_consulta['Ite_Lar'],'#FFFF00',1);?></td>
			   <td align="center">
                <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="ponPrefijo('<?php echo $row_rs_consulta["Pro_Cod"]; ?>' , '<?php echo $row_rs_consulta["Ite_Cor"]; ?>','<?php echo  $row_rs_consulta["Ite_Lar"]; ?>','<?php echo  $row_rs_consulta["Cat_Des"]; ?>','<?php echo  $row_rs_consulta["Cat_Cdc"]; ?>','<?php echo  $row_rs_consulta["Cat_Cod"]; ?>');	   
			   ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar_val=1&Ite_Cod='+ document.getElementById('Ite_Cod').value+'&Cat_Cod1='+ document.getElementById('Cat_Cod1').value+ '&Pro_Cod=<?php echo  $row_rs_consulta["Pro_Cod"]; ?>'+ '&Mar_Des1=<?php echo  $row_rs_consulta["Mar_Des"]; ?>','contenedormarca');">
           			<i class=" icon-arrow-right icon-white"></i>
           	    </button>
                </td>		
		  </tr>
		  <?Php } //FIN foreach($rs_consulta as $row_rs_consulta) ?>
		   </table>
		  <?php echo barra_estado($total_rs_consulta); 
		  exit();
		}
		else
		{
		 ?>
		 <tr>
		 	<td>&nbsp;</td>
		 	<td>&nbsp;</td>
		 	<td>&nbsp;</td>
		 	<td><?php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
		 	<td>&nbsp;</td>                                                
		 </tr>	
    </tbody>     
  </table>
  <?php echo barra_estado($total_rs_consulta); ?>
<?Php 
	exit();	
	}//FIn del if ($total_rs_consulta>0)		
}/* FIN DEL AJAX 1*/
?>	 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>		  	
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_kardex.js"></script>        
    <script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            /* LLamado a la class del boton exportar */
            $("#Boton_Excel").click(function(event) {
                $("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
                $("#FormularioExportacion").submit();
        });
        });
    </script>        
    <!--Librerias para interfaz -->       
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>                 
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modal.js"></script>
    <script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
    <script type="text/javascript">
    $(function() { 
        /* Campo 1 */
        $( "#ini" ).datepicker();
        $( "#ini" ).change(function() {
        $( "#ini" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
     });
        /* Campo 2 */
        $( "#fin" ).datepicker();
        $( "#fin" ).change(function() {
        $( "#fin" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
     });		 
    });
    </script>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">	
</head>
<body>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td>&raquo; Consultar Kardex </td>
  	</tr>
	<tr>
    <td valign="top"><form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>" >
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Datos a registrar </label>
		</LEGEND>
<?Php 
mensaje_requerido(); 
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="15%" class="Etiqueta1">C&oacute;d. Int.:  </td>
    <td width="85%" class="LetraNegra"><table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td><input  disabled="disabled" border="0" name="Ite_Cod2" type="text" id="Ite_Cod2"  size="15" maxlength="30" />
            <input  border="0" name="Ite_Cod" type="hidden" id="Ite_Cod"  size="15" maxlength="30" /></td>
          <td><button type="button" class="btn btn-success fileinput-button" title="Elegir Producto" id="button">
           			<i class="icon-tag icon-white"></i>
           			<span>Producto</span>
       	</button>
          </td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td class="Etiqueta1">C&oacute;d. Categoria: </td>
    <td width="85%" class="LetraNegra"><input disabled="disabled" border="0" name="Cat_Cod12" type="text" id="Cat_Cod12" value="" size="15" maxlength="30" />
      <input border="0" name="Cat_Cod1" type="hidden" id="Cat_Cod1" value="" size="15" maxlength="30" />
      <input border="0" name="Cat_Cdc" type="hidden" id="Cat_Cdc" value="" size="15" maxlength="30" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Descripción  Corta: </td>
    <td class="LetraNegra"><input disabled="disabled" border="0" name="Ite_Cor" type="text" id="Ite_Cor" value="<?Php echo $row_rs_consulta['Ite_Cor']; ?>" size="15" maxlength="30" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Descripción Larga: </td>
    <td class="LetraNegra"><input  disabled="disabled" name="Ite_Lar" type="text" id="Ite_Lar" value="<?Php echo $row_rs_consulta['Ite_Lar']; ?>" size="25" maxlength="30" /></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Marca: </td>
    <td class="LetraNegra"><label></label>
	<div id="contenedormarca">
      <table width="49%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13%"><input name='Mar_Des1' type='text' id='Mar_Des1'  value=''  size='15' readonly="readonly"  border='0' /></td>
          <td width="87%"> <input  border='0' name='Pro_Cod' type='hidden' id='Ite_Cod2'  size='15'  value='' /></td>
        </tr>
      </table></div>     </td>
  </tr>
  <tr>
    <td colspan="2"><fieldset>
      <LEGEND>
        <label class="Titulos2">Buscar por Fecha:</label>
        </LEGEND>
      <table border="0" cellpadding="0" cellspacing="0">
        <tr><td width="56" height="20" class="BarraBusqueda"><div align="right">Desde: </div></td>
          <td width="152" class="BarraBusqueda"><input name="ini" type="text" id="ini" value="<?php if (isset($ini)){ echo $ini; }else{ echo date("Y-m-d"); } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)">
          <td width="56" class="BarraBusqueda"><div align="right">Hasta: </div></td>
          <td width="152" class="BarraBusqueda"><input name="fin" type="text" id="fin" value="<?php if (isset($fin)){ echo $fin; }else{ echo date("Y-m-d"); } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)">
          <td width="160" class="BarraBusqueda"><div align="center">
          <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form,'Ite_Cod2', 0)">
           			<i class="icon-search icon-white"></i>
           			<span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;&nbsp;</span>
       	</button>                      
            <input name="hdd" type="hidden" id="hdd">
            </div>          </td>
          </tr>
        </table>
    </FIELDSET></td>
    </tr>
</table>
<input name="hdd_save" type="hidden" id="hdd_save" value="insertar" />
</FIELDSET>
<br />
      </form>
      </td>
	</tr>
</table>
  
<?php if(isset($hdd))
{
	/**
	* Consulta los registros del kardex Ite_Cod = Pro_Cod
	*/
	//$rs_consulta = $obBD_con1->consulta(sentencias_kar(1010, $obBD_con1->parametros($Ite_Cod)), $obBD_conexion->conexion);
	$rs_consulta = $obBD_con1->getRowConsulta(1010,$Ite_Cod, $obBD_conexion);
    $row_rs_consulta = $rs_consulta;
	$total_rs_consulta =  count($rs_consulta);
 ?>
<form name="form1" method="post" action="fac_pri_kardex_1.0.php" target="_blank">
  <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td>
        <tr>
          <td><table width="100%" border="0" cellpadding="0" cellspacing="0" >
            <tr>
              <td width="12%"  class="Texto_Reporte">&nbsp;<strong>Categoria: </strong></td>
              <td width="24%" class="Texto_Reporte" >&nbsp;<?php echo  $row_rs_consulta['Cat_Des'] ?></td>
              <td width="13%"  class="Texto_Reporte">&nbsp;<strong>Descripci&oacute;n Larga:</strong></td>
              <td width="23%" class="Texto_Reporte" >&nbsp;<?php echo  $row_rs_consulta['Ite_Lar'] ?></td>
              <td width="8%" class="Texto_Reporte" >&nbsp;<strong>Iva: </strong></td>
              <td width="20%" class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Iva_Por'] ?></td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>C&oacute;d. Categoria:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Pro_Cdc'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Marca:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Mar_Des'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Cod.Barra: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Pro_Bar'] ?></td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Observaci&oacute;n: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Pro_Obs'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Adquisici&oacute;n: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Adq_Des'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Ubicacion: </strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Ubi_Des'] ?></td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Descripci&oacute;n  Corta:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Ite_Cor'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Unidad:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $row_rs_consulta['Uni_Des'] ?></td>
              <td class="Texto_Reporte"><div align="right"></div></td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Desde:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $fech_ini; ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Hasta:</strong></td>
              <td class="Texto_Reporte">&nbsp;<?php echo  $fech_fin; ?></td>
              <td class="Texto_Reporte">&nbsp;</td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
          </table></td>
        </tr>
        <tr>
          <td>
		  <?php
	/**
	 * Consulta de fecha a fecha el movimiento del producto 
	 */	  
	$rs_consulta = $obBD_con1->getArrayConsulta(1042,$ini.'*'.$fin.'*'.$Ite_Cod, $obBD_conexion);
    $row_rs_consulta = $rs_consulta;
	$total_rs_consulta = count($rs_consulta);
	/**
	 * Consulta el movimiento del saldo actual al saldo anterior del producto 
	 */
	$rs_stock = $obBD_con1->getArrayConsulta(1043,$ini.'*'.$Ite_Cod, $obBD_conexion);
    $row_rs_stock = $rs_stock;
	$total_rs_stock =  count($rs_stock); 
	$Stock_actual=$row_rs_stock['Stock'];
	/**
     * Consulta el saldo anterior del producto 
	 */
	$rs_saldo1 = $obBD_con1->getArrayConsulta(1047,$ini.'*'.$Ite_Cod, $obBD_conexion);
    $row_rs_saldo1 = $rs_saldo1;
	$total_rs_saldo =  count($row_rs_saldo1);
	
	$Saldo_actual=$row_rs_saldo1['Saldo'];
	$res_saldo=$row_rs_saldo1['Saldo'] ;
	
	$rs_saldo = $obBD_con1->getArrayConsulta(1047,$ini.'*'.$Ite_Cod, $obBD_conexion);
	
	foreach($rs_saldo as $row_rs_saldo){
		$Saldo_actual=$Saldo_actual+$row_rs_saldo['Saldo'];
	 } ?>
		  <br>
		  <table width="100%" border="1" cellpadding="0" bordercolor="#000000" cellspacing="0">
            <tr class="Cabecera1">
              <td   height="35" ><div align="center"><strong>Fecha</strong></div></td>
              <td  colspan="2" ><div align="center"><strong>Documento</strong></div></td>
              <td colspan="2"><div align="center"><strong>Parametro del movimiento </strong></div></td>
              <td><div align="center"><strong>Entrada</strong></div></td>
              <td ><div align="center"><strong>Salida</strong></div></td>
              <td><div align="center"><strong>Ingreso</strong></div></td>
              <td ><div align="center"><strong>Egreso</strong></div></td>
              <td><div align="center"><strong>Saldos</strong></div></td>
              <td><div align="center"><strong>Stock</strong></div></td>
            </tr>
            <tr  class="Fondo">
              <td height="19" ><strong>dd/mm/aaaa</strong></td>
              <td  ><strong>Comp.</stroncg></td>
              <td ><div align="center"><strong>Numero</strong></div></td>
              <td  ><div align="center"><strong>Actividad</strong></div></td>
              <td  ><div align="center"><strong>Concepto</strong></div></td>
              <td>&nbsp;</td>
              <td >&nbsp;</td>
              <td>&nbsp;</td>
              <td >&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
             <tr class="Fondo">
              <td width="10%">&nbsp;</td>
              <td width="10%">&nbsp;</td>
              <td width="10%">&nbsp;</td>
              <td colspan="2" align="center">Saldo al <span class="LetraNegra"><?php list($ann, $mes, $dia) = preg_split('![/.-]!',$ini); echo $dia.', de '.mes($mes, 1).', '.$ann; ?></span></td>
              <td width="5%"><div align="right">
                <div align="right"><span class="Encabezado_reporte">
                  <?php echo ' '; ?>
                </span></div>
              </div></td>
              <td width="5%"><div align="right">
                <div align="right"><span class="Encabezado_reporte">
                  <?php  echo ' '; ?>
                </span></div>
              </div></td>
              <td width="5%"><div align="right"><span class="Encabezado_reporte">
                <?php  echo ' '; ?>
              </span></div></td>
			  <td width="5%"><div align="right"><span class="Encabezado_reporte">
			    <?php  echo ' '; ?>
			  </span></div></td>
              <td width="5%"><div align="right"><span class="Encabezado_reporte"><?php echo "$ ".formato_numero($Saldo_actual,2,1);?></span></div>              </td>
              <td width="5%"><div align="right"><span class="Encabezado_reporte"><?php if($row_rs_stock['Stock']==''){ echo 0;}else{echo $row_rs_stock['Stock'];}?></span></div>              </td>
	        </tr>
    		<?php 
			
			$Precio_Compra=0;
			$Tota_Salida=0;
			
			 
			foreach($rs_consulta as $row_rs_consulta){					
					$numero='';
					$documento='';
					$observacion='';
				if($row_rs_consulta['Vet_Cod']<>0)
					{	$Tipo_Comprobante='Venta';
						/**
						* Seleccion el movimiento del producto en ventas 
						*/
						
						$rs_docum = $obBD_con1->getRowConsulta(1044,$row_rs_consulta['Vet_Cod'], $obBD_conexion);
    					$row_rs_docum =  $rs_docum;
						$total_rs_docum =  count($rs_docum);
						
						$numero=$row_rs_docum['Vet_Num'];
						$documento=$row_rs_docum['Tic_Des'];
						$observacion=$row_rs_docum['Vet_Obs'];
					}
				if($row_rs_consulta['Cop_Cod']<>0)
					{	$Tipo_Comprobante='Compra';
						/**
						* Seleccion el movimiento del producto en compra 
						*/
											
						$rs_docum = $obBD_con1->getRowConsulta(1045,$row_rs_consulta['Cop_Cod'], $obBD_conexion);
						
    					$row_rs_docum = $rs_docum;
						$total_rs_docum =  count($rs_docum);
						
						$numero=$row_rs_docum['Cop_Num'];
						$documento=$row_rs_docum['Cop_Des'];
						$observacion=$row_rs_docum['Cop_Obs'];
					}
					if($row_rs_consulta['Aju_Cod']<>0)
					{
						$Tipo_Comprobante='Ajuste';
						/**
						* Seleccion el movimiento del producto en ajustes 
						*/						
						
						$rs_docum = $obBD_con1->getRowConsulta(1046,$row_rs_consulta['Aju_Cod'], $obBD_conexion);
    					$row_rs_docum = $rs_docum;
						$total_rs_docum =  count($rs_docum);
						
						$numero=$row_rs_docum['Aju_Cod'];
						$documento='Ajuste';
						$observacion=$row_rs_docum['Aju_Obs'];
					}?>
	        <tr>
              <td width="10%"><?php echo $row_rs_consulta['Kar_Fec']; ?></td>
              <td width="10%"><?php echo $documento; ?></td>
              <td width="10%"><?php echo $numero; ?></td>
              <td width="10%"><?php echo $Tipo_Comprobante; ?></td>
              <td width="30%"><?php echo $observacion; ?> </td>
              <td width="5%"><div align="right"><?php echo $row_rs_consulta['Kar_Can']; ?></div></td>
              <td width="5%"><div align="right"><?php echo $row_rs_consulta['Kar_Sal']; 
			  										$Tota_Salida=$Tota_Salida+$row_rs_consulta['Kar_Sal'];
			  ?></div></td>
              <td width="5%"><div align="right"><?php echo  "$ ".formato_numero($row_rs_consulta['Precio_ent'],2,1); ?></div></td>
			  <td width="5%"><div align="right"><?php echo "$ ".formato_numero($row_rs_consulta['Precio_sal'],2,1); 
			  		$Precio_Compra=$Precio_Compra+$row_rs_consulta['Precio_sal'];
			  ?> </div></td>
              <td width="5%"><div align="right"><?php echo "$ ".formato_numero($Saldo_actual=$Saldo_actual+$row_rs_consulta['Saldo'],2,1); ?></div>              </td>
              <td width="5%"><div align="right"><?php echo $Stock_actual=$Stock_actual+$row_rs_consulta['Stock']; ?></div>              </td>
	        </tr>
    	<?php  } //FIN foreach($rs_consulta as $row_rs_consulta){?>
	      </table></td>
        </tr>
        <tr>
          <td><table width="80%" border="1" cellpadding="0" cellspacing="0" bordercolor="#000000">
              <tr>
                <td width="20%"><span class="Encabezado_reporte">Stock actual:
                    <?php	if($Stock_actual<>0){ echo  $Stock_actual;}else{echo '0';}	?>
                </span></td>
                <td width="20%" class="Encabezado_reporte">Saldo actual:                  <?php if($Saldo_actual<>0){echo "$ ".formato_numero($Saldo_actual,2,1) ;	}else{echo '0';} ?></td>
                <td width="60%" class="Encabezado_reporte"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="234">Promedio Ponderado: <?php echo $Precio_Compra.' / '.$Tota_Salida.' = ';		
					if( $Tota_Salida==0){echo 0;}else{echo formato_numero(($Precio_Compra/$Tota_Salida),2,1); }	?></td>
                    <td width="272">Articulo  final : 
                      <?php if($Stock_actual<>0){echo  $Stock_actual.'   ' ;	}else {	echo '0'.'   ';}?>x <?php if ($Tota_Salida==0){echo 0; }else{echo '  '.formato_numero(($Precio_Compra/$Tota_Salida),2,1).' ' ;}
?>
= <?php if ($Tota_Salida==0){echo 0;}else{echo formato_numero(($Precio_Compra/$Tota_Salida)* $Stock_actual,2,1).' '; }
		?>		</td>
                  </tr>
                </table>
                </td>
              </tr>
            </table>
            <br>           
             <button  name="bnt_print" id="bnt_print" type="submit" class="btn btn-primary start" value="Imprimir" title="Imprimir" >
                  <i class='icon-print icon-white'></i> <span>Imprimir</span>
             </button>  
            <input border="0" name="fech_ini" type="hidden" id="fech_ini" value="<?php echo $ini; ?>" size="15" maxlength="30" />
            <input border="0" name="fech_fin" type="hidden" id="fech_fin" value="<?php echo $fin; ?>" size="15" maxlength="30" />
            <input border="0" name="codigo" type="hidden" id="codigo" value="<?php echo $Ite_Cod; ?>" size="15" maxlength="30" />
		  </td>
        </tr>
		</table>
</form>
<?php } ?>

<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()"> 
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td width="100%">  
  <FIELDSET>
  <LEGEND>
  <label class="Titulos2">Buscar por :</label>
  </LEGEND>
  <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td ><?PHP mensaje_requerido(); ?></td>
    </tr>
  </table>
  <table width="76%" border="0" cellspacing="0" >
    <tr>
      <td colspan="3" >
      <table width="100%" border="0">
        <tr>
		<form name="form_bus" action="" method="post">
          <td ><input type="radio" id="op_opciones" name="op_opciones" value="r" onClick="setfocus(document.getElementById('txt_busqueda')); document.getElementById('op').value = this.value;"  />
              <span class="LetraNegra">Código de barra</span></td>
          <td > <input id="op_opciones" name="op_opciones" type="radio" value="d" onClick="setfocus(document.getElementById('txt_busqueda')); document.getElementById('op').value = this.value;" checked="checked" />
          <input type="hidden" name="op" id="op" value="d" />
              <span class="LetraNegra">Descripción</span></td>
	   </form>
        </tr>
      </table></td>
    </tr>
    <tr>
      <td width="15%" class="BarraBusqueda"><span class="Asterisco">* </span>Item:</td>
      <td width="52%" class="BarraBusqueda">
		<input name="txt_busqueda" onKeyPress="
		 tecla = window.event.keyCode; 		
		 vectoropc=document.form_bus.op.value ;      

			if (tecla == 13) 
				{	cadenacar=document.getElementById('txt_busqueda').value;
					if(vectoropc=='r')
					{	
						if(cadenacar.length==13)
						{
						cadenacar=cadenacar.substr( 0, 12 );
						}
						else
						{
						cadenacar=document.getElementById('txt_busqueda').value;
						}
					}
					if(vectoropc=='d')
					{
						cadenacar=document.getElementById('txt_busqueda').value;
					}
  			ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_1=1&txt_b='+cadenacar+'&ajax_2=1&opciones='+vectoropc ,'busqueda_item') 
   			}  " type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase " />
            </td>
           <td class="BarraBusqueda" width="45%" align="center">
      <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_1=1&txt_b='+ document.getElementById('txt_busqueda').value+'&ajax_2='+1 ,'busqueda_item')">
           			<i class="icon-search icon-white"></i>
           			<span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;&nbsp;</span>
       	</button>
      </td>
    </tr>
  </table>
  </fieldset>
  <br>
   <div id="busqueda_item"></div>
</td>
  </tr>
</table>
</div>
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_kardex.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  
</body>
</html>
<?Php 
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>