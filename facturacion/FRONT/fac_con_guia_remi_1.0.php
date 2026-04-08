<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripcion: Permite consultar facturas de compra
* Fecha de actualizacion:	2012-09-13 
* Desarrollador: Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_guia_remi.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
//$Ses_Emp_Cod=1;

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Tes; 	  
/* Asingo el valor a la variable mes */
$mes = date("m"); 

/* Consultar los años para la consulta de las facturas de compras */
$rs_anios = $obBD_con1->getArrayConsulta(247, $Ses_Emp_Cod, $obBD_conexion); 	
		
/* Cargado de los datos de la cabecera */
if ($txt_busqueda != "") {  	
	if ($op_opciones == "d") {	
		/* Consulta de las facturas de compra por apellido */
		$rs_buscar = $obBD_con1->getArrayConsulta(483,trim($txt_busqueda).'*'.$cmb_anio.'*'.$cmb_mes.'*'.$Ses_Emp_Cod.'*'.$cmb_sucursal,$obBD_conexion); 
	} elseif($op_opciones == "ru") {
		/* Consulta de las facturas de compra por RUC */
		$rs_buscar = $obBD_con1->getArrayConsulta(485,trim($txt_busqueda).'*'.$cmb_anio.'*'.$cmb_mes.'*'.$Ses_Emp_Cod.'*'.$cmb_sucursal,$obBD_conexion); 
	} else {
		/* Consulta de las facturas de compra por numero de factura */
		$rs_buscar = $obBD_con1->getArrayConsulta(484, trim($txt_busqueda).'*'.$Ses_Emp_Cod.'*'.$cmb_sucursal, $obBD_conexion);
	}
}//Fin del if ($txt_busqueda != "")


if(isset($ajax_info)){
	include('../COMPONENTES/tesComDetalleGuia.php');
exit();
}

if(isset($ajax_detalle)){
	include("../COMPONENTES/tesComDetalleCom.php");  
exit();	
}

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>		
		<script language="javascript" src="../VALIDACIONES/fac_val_guia_remi.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>         
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>  
        <script language="javascript" src="../VALIDACIONES/XML.js"></script>		
        <script language="javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>
		<script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
       <script>
		$(function() { 
			/* Campo 1 */
			$( "#Cop_Fec" ).datepicker();			
			$( "#Cop_Fec" ).change(function() {
			$( "#Cop_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
			/* Campo 2 */
			$( "#Cop_Cad" ).datepicker();			
			$( "#Cop_Cad" ).change(function() {
			$( "#Cop_Cad" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
			/* Campo 3 */
			$( "#Com_Fec" ).datepicker();			
			$( "#Com_Fec" ).change(function() {
			$( "#Com_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});	
			/* Campo 4 */
			$( "#Cop_Imp" ).datepicker();			
			$( "#Cop_Imp" ).change(function() {
			$( "#Cop_Imp" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});
			/* Campo 5 */
			$( "#Cpp_Ven" ).datepicker();			
			$( "#Cpp_Ven" ).change(function() {
			$( "#Cpp_Ven" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});		
		}); 		
        </script>            
		</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
 <tr class="BarraTitulo">	
	  <td height="10" colspan="3">&raquo; Consultar Documentos de Gu&iacute;as de Remisi&oacute;n</td>    
  <tr>
  <tr>     
       <td  valign="top" height="400">
        <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
        <FIELDSET>
          <legend>
            <label class="Titulos2">Buscar por:</label>
          </legend>  
          <?Php echo mensaje_requerido(); ?>	
        <table width="668" border="0">
              <tr>
                <td width="127"><input name="op_opciones" type="radio" value="d" onClick="document.getElementById('cmb_mes').disabled=false; document.getElementById('cmb_anio').disabled=false; setfocus(this.form.txt_busqueda)" checked>
                    <span class="Etiqueta1">Apellidos</span></td>
                <td width="97"><input name="op_opciones" type="radio" value="ru" onClick="document.getElementById('cmb_mes').disabled=false; document.getElementById('cmb_anio').disabled=false; setfocus(this.form.txt_busqueda)" >
                    <span class="Etiqueta1">RUC</span></td>
                <td width="140"><input type="radio" name="op_opciones" value="r" onClick="document.getElementById('cmb_mes').disabled=true; document.getElementById('cmb_anio').disabled=true; setfocus(this.form.txt_busqueda)">
                    <span class="Etiqueta1">No. Documento </span></td>
                <td width="89" class="Etiqueta1">A&ntilde;o:
                 <select name="cmb_anio" id="cmb_anio">
                <?php 
                foreach($rs_anios as $row_rs_anios)
                {
                ?>
                  <option <?Php if ($cmb_anio == $row_rs_anios['Anio']){ echo "selected";} ?> value="<?php echo $row_rs_anios['Anio']; ?>"><?php echo $row_rs_anios['Anio']; ?></option>
                      <?Php
                }
                ?>
                 </select>        
                </td>
                <?Php
                if (isset($cmb_mes))
                {
                    /**
                    *  Desglosa la cadena de texto AND MONTH(Cop_Fec)=$i 
                    */
                    $array_mes = explode ('=',$cmb_mes);
                    $mes = $array_mes[1];		
                }//Fin del if (isset($cmb_mes))
                ?>			
                <td width="193" align="right"><span class="Etiqueta1">Mes:&nbsp;
                  <select name="cmb_mes" id="cmb_mes">
                    <option value=""><< TODOS >></option>
                    <?Php
                          for ($i=1;$i<=12;$i++)
                          { 
                          ?>
                    <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?Php echo "AND MONTH(Gui_Fec)=$i"; ?>"><?php echo mes($i, 1) ?></option>
                    <?Php
                          } ?>
                  </select>
                </span></td>
                <td width="160" align="right">
                    <span class="Etiqueta1">Sucursal:&nbsp;</span>
                    <select name="cmb_sucursal" id="cmb_sucursal" onchange="validar_requeridos(this.form, 'txt_busqueda', 0)">
                        <option value=""><< TODOS >></option>
                        <?php foreach($rs_sucursal as $row_suc) { ?>
                            <option <?php if ($cmb_sucursal == $row_suc['Suc_Cod']){ echo "selected";} ?> value="<?php echo $row_suc['Suc_Cod']; ?>"><?php echo $row_suc['Suc_Des']; ?></option>
                        <?php } ?>
                    </select>
                </td>
              </tr>
            </table>
            <table width="509" height="36" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="509" height="28" class="BarraBusqueda">&nbsp;&nbsp;<span class="Asterisco">* </span>Busqueda:
                  <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50" />
                  &nbsp; <button type="button" name="btn-buscar" id="btn-buscar2" class="btn btn-success fileinput-button" title="Deudas" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button></td>
              </tr>
            </table>
        </FIELDSET>
		</form>

<? if(isset($txt_busqueda)){?>
<FIELDSET> 
<LEGEND>
<label class='Titulos2'>Resultados de la busqueda</label>
</LEGEND>
<table width="100%"  border="0" cellpadding="0" cellspacing="0" class="fixedHeader02">
<thead>
  <tr>
    <th width="9%">C&oacute;d. Int.</th>
    <th width="15%">No. Documento</th>
    <th width="11%">Fecha  </th>	  		  
    <th width="61%" align="center">Destinatario</th>
    <th width="1%">Pdf</th>
    <th width="1%">&nbsp;</th>
    <th width="1%">&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?Php
  if(count($rs_buscar) > 0)
  {
    $i=0;
	
   	foreach($rs_buscar as $row_rs_buscar)
	{ 
      if($row_rs_buscar['Gui_Est']=='I')
	  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  $i++; 	  				 
	  ?>      	 
      <tr>
	    <td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php $Gui_Cod_Int=$row_rs_buscar['Gui_Cod']; echo $Gui_Cod_Int; ?></FONT></td>
	    <td align="center"><FONT COLOR="<? echo $rojo;?>">
	      <?Php $Gui_Num=$row_rs_buscar['Gui_Num']; echo $Gui_Num; ?>
	      </FONT>
	      </td>
	    <td align="center">
	      <FONT COLOR="<? echo $rojo;?>">
	        <?Php $Fecha_Fac=$row_rs_buscar['Gui_Fec'];  echo $Fecha_Fac; ?>
	        </FONT>
	      </td>
	    <td align="left">
	      <FONT COLOR="<? echo $rojo;?>">
	        <?Php echo $row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom']; ?>
	        </FONT>
	      </td>
	    <td align="center">
        <? if($row_rs_buscar['Gui_Aut']=='S'){ ?>
        <form name="frm_pdf" id="frm_pdf" action="../COMPONENTES/tesPdfGuiaRemisionElectronica_1.0.php" method="post" target="_blank">
            <button type="button" class="btn btn-primary btn-mini" title="Pdf(Retenci&oacute;n electr&oacute;nica SRI)" onclick="this.form.submit()">
            <i class=" icon-download-alt icon-white"></i> <span></span> </button>
            <input name="urlXml" id="urlXml" type="hidden" value="<?Php echo '../FRONT/'.$Ses_Emp_Cod."/".$row_rs_buscar['Gui_Xml']."_A.xml";?>">
            <input name="op" id="op" type="hidden" value="I">
            <input name="logoUrl" id="logoUrl" type="hidden" value="<? echo $Ses_Emp_Log;?>">            
        </form>        
        <? }?>
        </td>
	    <td align="center"><button type="button" class="btn btn-info btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&amp;com_codigo=<? echo $row_rs_buscar['Gui_Cod'];?>&amp;Ses_Emp_Cod=<? echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>	
	    <td align="center">
	      <?Php if ($row_rs_buscar['Gui_Est'] == 'A') { ?>
	       <form method="post" name="pasar" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
	        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
	          <i class=" icon-arrow-right icon-white"></i>
	          </button>       
	        <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Gui_Cod'];?>">
	        <input name="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda; ?>" >
	        <input name="volver_op" type="hidden" value="<?Php echo $op_opciones; ?>">	       
	        <input name="cmb_anio" type="hidden" value="<?Php echo $cmb_anio; ?>">
	        <input name="cmb_mes" type="hidden" value="<?Php echo $cmb_mes; ?>">
	        <input name="Tic_Cod" type="hidden" value="<?Php echo $Tic_Cod; ?>">
	        <input name="Des_Cod" id="Des_Cod" type="hidden" value="<?Php echo $row_rs_buscar['Des_Cod'];?>">
	       </form>       
	      <?Php } 
		else { echo "&nbsp;"; } ?>        
	      </td>
	    </tr>   
        
      <?Php } 
	 }
	else
	{ 
	?>
	    <tr>
         <td align="center">&nbsp;</td>
         <td align="center">&nbsp;</td>
         <td align="center">&nbsp;</td>
         <td align="center">
		 <?php 
			$msg_mes = explode('=', $cmb_mes);
	  		echo error_alerta(" No hay resultados que mostrar para ".strtoupper($txt_busqueda)." en ".mes($msg_mes[1],1)." del ".$cmb_anio, 2);
		 ?></td>
         <td align="center">&nbsp;</td>
         <td align="center">&nbsp;</td>
         <td align="center">&nbsp;</td>
        </tr>
	   <?Php
	} ?>
    </tbody>
  </table>    
</FIELDSET>
<? echo barra_estado(count($rs_buscar));?>
<? }//if(isset($txt_busqueda))?>	                                           
<br />
<?Php 
	if (isset($volver_busqueda) && !isset($op_busqueda)) { 
	/**
	* Consulta de las datos de guias remision
	*/
	$rs_guiasDestinatario = $obBD_con1->getRowConsulta(1274,$codigo,$obBD_conexion);
	
	/**
	* Consulta de las datos de guias remision
	*/
	$rs_guiasTransporte = $obBD_con1->getRowConsulta(1275,$codigo,$obBD_conexion);
	
	/**
	* Consulta el detalle de la guia de remision
	*/
	$rs_guiasDetalle = $obBD_con1->getArrayConsulta(1273,$codigo,$obBD_conexion);

?>
    
    
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2">
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Datos del Destinatario </label>
        </LEGEND>			
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="30%" class="Etiqueta1">&nbsp;C&eacute;dula/R.U.C.:</td>
            <td width="20%" align="left" class="LetraNegra">&nbsp;<? echo $rs_guiasDestinatario['Prs_Ced']?></td>            
            </tr>
          <tr>
            <td class="Etiqueta1">Nombre/Raz&oacute;n Social:</td>
            <td colspan="3" class="LetraNegra">&nbsp;<? echo $rs_guiasDestinatario['Prs_Ape'].' '.$rs_guiasDestinatario['Prs_Nom']?></td>
            </tr>
          <tr>
            <td class="Etiqueta1">C&oacute;d. Establecimiento:</td>
            <td align="left" class="LetraNegra">&nbsp;<? echo $rs_guiasDestinatario['Des_Sri']?></td>
            <td width="16%" class="Etiqueta1">C&oacute;digo Aduana:</td>
            <td width="34%" align="left" class="LetraNegra">&nbsp;<? echo $rs_guiasDestinatario['Des_Adu']?></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Direcci&oacute;n de Llegada:</td>
            <td colspan="3" align="left" class="LetraNegra">&nbsp;<? echo $rs_guiasDestinatario['Gui_Dar']?></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Motivo:</td>
            <td colspan="3" align="left" class="LetraNegra">&nbsp;<? echo $rs_guiasDestinatario['Gui_Mot']?></td>
            </tr>
        </table>       
        </FIELDSET>
        </td>
        <td width="49%">
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Datos del Transporte </label>
        </LEGEND>			
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="31%" class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
            <td width="11%" class="LetraNegra">&nbsp;<? echo $rs_guiasTransporte['Prs_Ced'];?></td>            
            </tr>
          <tr>
            <td class="Etiqueta1">Nombre/Raz&oacute;n Social:</td>
            <td colspan="2" class="LetraNegra">&nbsp;<? echo $rs_guiasTransporte['Prs_Ape'].' '.$rs_guiasTransporte['Prs_Nom'];?></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Direcci&oacute;n de Salida:</td>
            <td colspan="2" class="LetraNegra">&nbsp;<? echo $rs_guiasTransporte['Gui_Dsa'];?></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Placa:</td>
            <td class="LetraNegra">&nbsp;<? echo $rs_guiasTransporte['Gui_Pla'];?></td>
            <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td width="16%" class="Etiqueta1">Salida:</td>
                <td width="26%" class="LetraNegra">&nbsp;<? echo $rs_guiasTransporte['Gui_Fsa'];?></td>
                <td width="19%" class="Etiqueta1">Llegada:</td>
                <td width="39%" class="LetraNegra">&nbsp;<? echo $rs_guiasTransporte['Gui_Far'];?></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td class="Etiqueta1">Ruta de Traslado:</td>
            <td colspan="2" class="LetraNegra">&nbsp;<? echo $rs_guiasTransporte['Gui_Rut'];?></td>
          </tr>
        </table>       
        </FIELDSET>
        </td>
      </tr>
    </table>
    
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Detalle de la Gu&iacute;a de Remisi&oacute;n</label>
    </LEGEND>    
      <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader02"> 
      <thead>   
        <tr>
            <th width="9%">C&oacute;digo</th>	  
            <th width="14%">Cantidad</th>
            <th width="77%">Descripci&oacute;n</th>
            </tr>
        </thead>
        <tbody>	
        <? foreach($rs_guiasDetalle as $datos){?>
        <tr>
            <td align="center"><? echo $datos['Pro_Cod'];?></td>
            <td align="center"><? echo $datos['Gui_Can'];?></td>
            <td>&nbsp;<? echo $datos['Ite_Lar'].' '.$datos['Pro_Obs'];?></td>
        </tr>
        <? }?>
        </tbody>          
       </table>
       <? echo barra_estado(count($rs_guiasDetalle));?>                
    </FIELDSET>
    <br />
    <table border="0">
      <tr>
        <td width="107" align="left">
        <form  method="post" name="form3" action="<?Php $_SERVER['PHP_SELF']?>">
        <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_buscar*cmb_anio*cmb_mes*Tic_Cod*Tri_Cod*txt_fec_ini*txt_fec_fin*hdd*optest*Chk_Ret*codigo*op_busqueda"; ?>', 
'<?Php echo $volver_busqueda.'*'.$volver_op.'*'.'1'.'*'.$cmb_anio.'*'.$cmb_mes.'*'.$Tic_Cod.'*'.$Tri_Cod.'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*'.'1'.'*'.$optest.'*'.$Chk_Ret.'*0'.'*'.$op_busqueda; ?>')">
           <i class=" icon-arrow-left icon-white"></i> 
           <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
         </button>
        </form>
        </td>
        <td width="136" align="left">
		<?
          	/* 
			* Consulta del reporte para impresion 
			*/
			$pagina = $_SERVER['PHP_SELF'];
			$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);			
		  ?>
          <form  method="post" name="form3" action="<?Php echo $reportes[1].'?a=2'; ?>" target="_blank">
          
               <button type="button" class="btn btn-primary start" title="Imprimir Documento" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
              <input name="txt_fec_fin" id="txt_fec_fin" type="hidden" value="<?php echo $txt_fec_fin; ?>">
              <input name="txt_fec_ini"  id="txt_fec_ini" type="hidden" value="<?php echo $txt_fec_ini; ?>">                                          
              <input name="Tri_Cod" id="Tri_Cod" type="hidden" value="<?Php echo $Tri_Cod; ?>" >
              <input name="Tic_Cod" type="hidden" value="<?php echo $Tic_Cod; ?>">
              <input name="Gui_Cod" type="hidden" value="<?php echo $codigo; ?>">
              <input name="optest" type="hidden" value="<?php echo $optest; ?>">
              <input type="hidden" name="Chk_Ret" value="<?Php echo $Chk_Ret; ?>">
          </form>
      </td>
      </tr>
    </table>
<? }?>
<br />
</td>
</tr>
</table>

<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar"></div>
       </div>
</div>
</div> 
<script type="text/javascript" src="../VALIDACIONES/fac_par_guia_remi.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	   
</BODY>
</HTML>
<?Php	
/**
* Cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>