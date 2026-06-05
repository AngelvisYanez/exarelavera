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
require_once('../LOGICA/tes_log_kardex.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

//$Ses_Usu_Cod=1;
//$Ses_Emp_Cod=1;
//$Ses_Prs_Cod=1;
//$Ses_Suc_Cod=1;
//$Ses_Dat_Dis="ecu911";
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
	<table width="100%" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
          <th width="10%">C&oacute;d. Int.</th>
		  <th width="47%">Descripci&oacute;n</th>
          <th width="20%">Marca</th>
		  <th width="20%">Tipo</th>
		  <th width="3%">&nbsp;</th>
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
			   <td><?Php echo  marcar_cadena($txt_b, $row_rs_consulta['Ite_Lar'],'#FFFF00',1);?></td>
			   <td><?Php echo $row_rs_consulta['Mar_Des']; ?></td>
			   <td><?Php echo $row_rs_consulta['Cat_Des'];?></td>
			   <td align="center">
                <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="ponPrefijo('<? echo $row_rs_consulta['Pro_Cod']; ?>','<? echo $row_rs_consulta['Pro_Obs']; ?>','<? echo  $row_rs_consulta['Ite_Lar']; ?>','<? echo  $row_rs_consulta['Cat_Des']; ?>','<? echo  $row_rs_consulta['Cat_Cdc']; ?>','<? echo  $row_rs_consulta['Cat_Cod']; ?>','<? echo  $row_rs_consulta['Mar_Des']; ?>'); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar_val=1&Ite_Cod='+ document.getElementById('Ite_Cod').value+'&Cat_Cod1='+ document.getElementById('Cat_Cod1').value+ '&Pro_Cod=<? echo  $row_rs_consulta["Pro_Cod"]; ?>'+ '&Mar_Des1=<? echo  $row_rs_consulta["Mar_Des"]; ?>','contenedormarca');">
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
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../VALIDACIONES/fac_val_kardex.js"></script>        
    <script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
    <script language="javascript">
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
        $( "#ini" ).datepicker( {changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});
     }); 
        /* Campo 2 */
       
    $(function() {   
        $( "#fin" ).datepicker({changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});  		 
    });
    </script>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">	
</head>
<body>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
  <td>&raquo; Consultar Kardex </td>
</tr>
<tr>
<td valign="top">
<form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>" >
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Datos a registrar </label>
    </LEGEND>
<?Php 
mensaje_requerido(); 
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="9%" class="Etiqueta1">C&oacute;d. Int.:</td>
    <td width="91%" class="LetraNegra">
      <table width="272" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="152">
            
            <input name="Ite_Cod2" type="text" id="Ite_Cod2" style="border:none"  size="15" maxlength="30" readonly="readonly"  />
            <input name="Ite_Cod" type="hidden" id="Ite_Cod" value="<? echo $Ite_Cod;?>" />
            </td>         
          <td width="120"><button style="font-size:9px" type="button" class="btn btn-success btn-mini" title="Elegir Producto" id="button"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button></td>
        </tr>
        </table>
      </td>
  </tr>
  <tr>
    <td class="Etiqueta1">Categoria: </td>
    <td width="91%"><input name="Cat_Cod" type="text" id="Cat_Cod" style="border:none" size="15" readonly="readonly" />
      <input name="CatCod1" type="hidden" id="CatCod1" value="" />
      <input name="Cat_Cdc" type="hidden" id="Cat_Cdc" value=""/></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1">Descripción: </td>
    <td class="LetraNegra"><input style="border:none"  name="Ite_Lar" type="text" id="Ite_Lar" value="<?Php echo $row_rs_consulta['Ite_Lar']; ?>" size="25"/>
      <input name="Ite_Cor" type="hidden" id="Ite_Cor" value=""/></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observación:</td>
    <td class="LetraNegra"><input style="border:none" name="Pro_Obs" type="text" id="Pro_Obs" value="<?Php echo $row_rs_consulta['Pro_Obs'];?>" size="25" maxlength="30" /></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Marca: </td>
    <td class="LetraNegra"><label></label>
	<div id="contenedormarca">
      <table width="49%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13%"><input style="border:none"  name='Mar_Des1' type='text' id='Mar_Des1'  value=''  size='15' readonly="readonly"  /></td>
          <td width="87%"> <input  border='0' name='Pro_Cod' type='hidden' id='Ite_Cod2'  size='15'  value='' /></td>
        </tr>
      </table></div>     </td>
  </tr>
  <tr>
    <td colspan="2">
    
    <fieldset>
      <LEGEND>
        <label class="Titulos2">Buscar por Fecha:</label>
        </LEGEND>
      <table width="450" border="0" cellpadding="0" cellspacing="0">
        <tr><td width="56" height="40" class="BarraBusqueda"><div align="right">Desde: </div></td>
          <td width="85" class="BarraBusqueda"><input name="ini" type="text" id="ini" value="<?php if (isset($ini)){ echo $ini; }else{ echo date("Y-m-d"); } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)">
          <td width="58" class="BarraBusqueda"><div align="right">Hasta: </div></td>
          <td width="114" class="BarraBusqueda"><input name="fin" type="text" id="fin" value="<?php if (isset($fin)){ echo $fin; }else{ echo date("Y-m-d"); } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)">
          <td width="137" class="BarraBusqueda">&nbsp;
          <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="this.form.submit();">
           			<i class="icon-search icon-white"></i>
           			<span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;&nbsp;</span>
       	</button>                      
            <input name="hdd" type="hidden" id="hdd">
                   </td>
          </tr>
        </table>
    </FIELDSET></td>
  </tr>
</table>
<input name="hdd_save" type="hidden" id="hdd_save" value="insertar"/>
</FIELDSET>
<br />
      </form>
      </td>
	</tr>
  <tr>
    <td colspan="2"> 

  
<? if(isset($hdd))
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
              <td width="10%"  class="Texto_Reporte">&nbsp;<strong>Categoria: </strong></td>
              <td width="23%" class="Texto_Reporte" >&nbsp;<? echo  $row_rs_consulta['Cat_Des'] ?></td>
              <td width="9%"  class="Texto_Reporte">&nbsp;<strong>Descripci&oacute;n:</strong></td>
              <td width="22%" class="Texto_Reporte" >&nbsp;<? echo  $row_rs_consulta['Ite_Lar'] ?></td>
              <td width="7%" class="Texto_Reporte" >&nbsp;<strong>Iva: </strong></td>
              <td width="29%" class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Iva_Por'] ?></td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>C&oacute;d. Categoria:</strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Pro_Cdc'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Marca:</strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Mar_Des'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Cod.Barra: </strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Pro_Bar'] ?></td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Observaci&oacute;n: </strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Pro_Obs'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Adquisici&oacute;n: </strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Adq_Des'] ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Ubicacion: </strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Ubi_Des'] ?></td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Unidad:</strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $row_rs_consulta['Uni_Des'] ?></td>
              <td class="Texto_Reporte">&nbsp;</td>
              <td class="Texto_Reporte">&nbsp;</td>
              <td class="Texto_Reporte"><div align="right"></div></td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
            <tr>
              <td class="Texto_Reporte">&nbsp;<strong>Desde:</strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $ini; ?></td>
              <td class="Texto_Reporte">&nbsp;<strong>Hasta:</strong></td>
              <td class="Texto_Reporte">&nbsp;<? echo  $fin; ?></td>
              <td class="Texto_Reporte">&nbsp;</td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
          </table></td>
        </tr>
        <tr>
          <td>
		  <?
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
	 } 
	 if($Saldo_actual<0){$rojo ="style='color:red;'";}else{$rojo="";}
	 ?>
		  <br>
		  <table width="98%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse; table-layout:fixed;">
            <tr class="Cabecera1">
              <td width="80" height="35" ><div align="center"><strong>Fecha</strong></div></td>
              <td  colspan="2" ><div align="center"><strong>Documento</strong></div></td>
              <td colspan="2"><div align="center"><strong>Parametro del movimiento </strong></div></td>
              <td width="6%"><div align="center"><strong>Entrada</strong></div></td>
              <td width="6%" ><div align="center"><strong>Salida</strong></div></td>
              <td width="6%"><div align="center"><strong>Ingreso</strong></div></td>
              <td width="6%" ><div align="center"><strong>Egreso</strong></div></td>
              <td width="6%"><div align="center"><strong>Saldos</strong></div></td>
              <td width="6%"><div align="center"><strong>Stock</strong></div></td>
            </tr>
            <tr  class="Fondo">
              <td height="10%" align="center" ><strong>dd/mm/aaaa</strong></td>
              <td width="60"  align="center"><strong>Ajuste</strong></td>
              <td width="49" ><div align="center"><strong>Numero</strong></div></td>
              <td width="60" align="center"  ><strong>Actividad</strong></td>
              <td width="229"  ><div align="center"><strong>Concepto</strong></div></td>
              <td>&nbsp;</td>
              <td >&nbsp;</td>
              <td>&nbsp;</td>
              <td >&nbsp;</td>
              <td><p>&nbsp;</p></td>
              <td>&nbsp;</td>
            </tr>
             <tr class="Fondo">
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td colspan="2" align="center">Saldo al <span class="LetraNegra"><?php list($ann, $mes, $dia) = preg_split('![/.-]!',$ini); echo $dia.', de '.mes($mes, 1).', '.$ann; ?></span></td>
              <td><div align="right">
                <div align="right">
                <span class="Encabezado_reporte">
                  <? echo ' '; ?>
                </span></div>
              </div></td>
              <td><div align="right">
                <div align="right"><span class="Encabezado_reporte">
                  <?  echo ' '; ?>
                </span></div>
              </div></td>
              <td><div align="right"><span class="Encabezado_reporte">
                <?  echo ' '; ?>
              </span></div></td>
			  <td><div align="right"><span class="Encabezado_reporte">
			    <?  echo ' '; ?>
			  </span></div></td>
              <td <?Php echo $rojo; ?>><div align="right"><span class="Encabezado_reporte"><? echo "$ ".formato_numero($Saldo_actual,2,1);?></span></div>              </td>
              <td><div align="right"><span class="Encabezado_reporte"><? if($row_rs_stock['Stock']==''){ echo 0;}else{echo $row_rs_stock['Stock'];}?></span></div>              </td>
	        </tr>
    		<? 
			
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
              <td align="center"><? echo $row_rs_consulta['Kar_Fec']; ?></td>
              <td align="center">&nbsp;<? echo $documento; ?></td>
              <td align="center"><? echo $numero; ?></td>
              <td align="center"><? echo $Tipo_Comprobante; ?></td>
              <td title="<? echo $observacion; ?>" style="white-space:nowrap;overflow:hidden;"><? echo $observacion; ?> </td>
              <td><div align="right"><? echo $row_rs_consulta['Kar_Can']; ?></div></td>
              <td><div align="right"><? echo $row_rs_consulta['Kar_Sal']; 
			  										$Tota_Salida=$Tota_Salida+$row_rs_consulta['Kar_Sal'];
			  ?></div></td>
              <td><div align="right"><? echo  "$ ".formato_numero($row_rs_consulta['Precio_ent'],2,1); ?></div></td>
			  <td><div align="right"><? echo "$ ".formato_numero($row_rs_consulta['Precio_sal'],2,1); 
			  		$Precio_Compra=$Precio_Compra+$row_rs_consulta['Precio_sal'];
			  ?> </div></td>
              <? if($Saldo_actual+$row_rs_consulta['Saldo']<0){$rojo1 ="style='color:red;'";}else{$rojo1="";}?>
              <td <?Php echo $rojo1; ?> ><div align="right"><? echo "$ ".formato_numero($Saldo_actual=$Saldo_actual+$row_rs_consulta['Saldo'],2,1); ?></div>              </td>
              <td><div align="right"><? echo $Stock_actual=$Stock_actual+$row_rs_consulta['Stock']; ?></div>              </td>
	        </tr>
    	<?  } //FIN foreach($rs_consulta as $row_rs_consulta){?>
	      </table></td>
        </tr>
        <tr>
          <td><table width="88%" height="22" border="1" cellpadding="0" cellspacing="0" bordercolor="#000000">
              <tr>
                <td width="20%"><span class="Encabezado_reporte">Stock actual:
                    <?	if($Stock_actual<>0){ echo  $Stock_actual;}else{echo '0';}	?>
                </span></td>
                <td width="20%" style="color:red" class="Encabezado_reporte">Saldo actual:                  <? if($Saldo_actual<>0){echo "$ ".formato_numero($Saldo_actual,2,1) ;	}else{echo '0';} ?></td>
                <td width="60%" class="Encabezado_reporte"><table width="117%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="234">Promedio Ponderado: <? echo $Precio_Compra.' / '.$Tota_Salida.' = ';		
					if( $Tota_Salida==0){echo 0;}else{echo formato_numero(($Precio_Compra/$Tota_Salida),2,1); }	?></td>
                    <td width="272">Articulo  final : 
                      <? if($Stock_actual<>0){echo  $Stock_actual.'   ' ;	}else {	echo '0'.'   ';}?>x <? if ($Tota_Salida==0){echo 0; }else{echo '  '.formato_numero(($Precio_Compra/$Tota_Salida),2,1).' ' ;}
?>
= <? if ($Tota_Salida==0){echo 0;}else{echo formato_numero(($Precio_Compra/$Tota_Salida)* $Stock_actual,2,1).' '; }
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
            <input border="0" name="fech_ini" type="hidden" id="fech_ini" value="<? echo $ini; ?>" size="15" maxlength="30" />
            <input border="0" name="fech_fin" type="hidden" id="fech_fin" value="<? echo $fin; ?>" size="15" maxlength="30" />
            <input border="0" name="codigo" type="hidden" id="codigo" value="<? echo $Ite_Cod; ?>" size="15" maxlength="30" />
		  </td>
        </tr>
		</table>
        <br />
</form>
<? } ?>
	</td>
    </tr>
</table>

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
  <table width="75%" border="0" cellspacing="0" >
    <tr>
      <td colspan="3" >
      <table width="100%" border="0">
        <tr>
		<form name="form_bus" action="" method="post">
          <td width="26%" ><input type="radio" id="op_opciones" name="op_opciones" value="r" onClick="setfocus(document.getElementById('txt_busqueda')); document.getElementById('op').value = this.value;"  />
              <span class="LetraNegra">Código de barra</span></td>
          <td width="74%" > <input id="op_opciones" name="op_opciones" type="radio" value="d" onClick="setfocus(document.getElementById('txt_busqueda')); document.getElementById('op').value = this.value;" checked="checked" />
          <input type="hidden" name="op" id="op" value="d" />
              <span class="LetraNegra">Descripción</span></td>
	   </form>
        </tr>
      </table></td>
    </tr>
    <tr>
      <td width="16%" height="5" align="right" class="BarraBusqueda"><span class="Asterisco">* </span>Item:</td>
      <td width="84%" class="BarraBusqueda">
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
   			}  " type="text" id="txt_busqueda" value="" size="40" maxlength="50" />
            
      <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_1=1&txt_b='+ document.getElementById('txt_busqueda').value+'&ajax_2='+1 ,'busqueda_item')">
           			<i class="icon-search icon-white"></i>
           			<span>&nbsp;Buscar&nbsp;</span>
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
<script type="text/javascript" src="../VALIDACIONES/tes_par_kardex.js"></script>
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