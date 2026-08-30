<?php	
/**
* Descripci�n: Permite consultar facturas de compra
* Fecha de actualizaci�n:	2012-09-13 
* Desarrollador: Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');
require_once('../../componentes/LOGICA/logica.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');




/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Comt; 	  
/**
* Asingo el valor a la variable mes 
*/
$mes = date("m"); 

if($Ses_Prs_Cod=='1')
{				
	//$Cop_Cod='1679'; //74
	//require_once("../COMPONENTES/tesXmlRetencionElectronica_1.0.php");		
	//echo $claveAcceso; 
}

if(isset($ajaxGrid)){
        if($Tic_Cod!='T'){
			$paramTipDoc=" AND compras.Tic_Cod='".$Tic_Cod."'";
		}else{
			$paramTipDoc="";
		}
		if($op_busqueda=='A')
		{
			/*Por Fechas*/	
			$paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
			$paramFecCi=$paramFecCi." AND Prs_Ced='".$txtCi."' ";
		}
		if($op_busqueda=='F')
		{
			$paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
		}
		if($op_busqueda=='C')
		{    
			/*Por Ruc*/
			$paramFecCi=" AND Prs_Ced='".$txtCi."'";
		}
		
		/*Busqueda por rango de fechas*/
		$contar = $obBD_con1->getArrayConsulta(1093, $Ses_Emp_Cod.'*'.$paramTipDoc.'*'.$paramFecCi.'*'.$optest, $obBD_conexion);
                $pagination= pages(count($contar), $page, $rows);
                $responce=$pagination['data'];
                $responce['rows'] =  $obBD_con1->getArrayConsulta(1093, $Ses_Emp_Cod.'*'.$paramTipDoc.'*'.$paramFecCi.'*'.$optest.'*'.$pagination['limits'], $obBD_conexion);
                $responce['userdata'] = $obBD_con1->getRowConsulta(1092, $Ses_Emp_Cod.'*'.$paramTipDoc.'*'.$paramFecCi.'*'.$optest, $obBD_conexion);
                $responce['userdata']['TotRen']=0;
                $responce['userdata']['TotIva']=0; 
		        
                for($i=0;$i<count($responce['rows']);$i++){
                    /*Consultamos si la compra tiene retencion*/
                    $row_ret_compra = $obBD_con1->getRowConsulta(1088, $responce['rows'][$i]['Cop_Cod'], $obBD_conexion);
 		    $row_cajas = $obBD_con1->getRowConsulta(4850, $responce['rows'][$i]['Cop_Cod'], $obBD_conexion);
		    $responce['rows'][$i]['Cant']= $row_cajas['Cop_Can'];
                    $responce['rows'][$i]['Ret_Num']=($row_ret_compra['Ret_Num']!=''&&$row_ret_compra['Ret_Num']!=null)?str_pad($row_ret_compra['Ret_Num'], 9, "0", STR_PAD_LEFT):'';
                    $responce['rows'][$i]['Aut_Sri']=($row_ret_compra['Aut_Sri']!=null?$row_ret_compra['Aut_Sri']:'');
                    $responce['rows'][$i]['TotRen']=($row_ret_compra['TotRen']!=null?$row_ret_compra['TotRen']:0);
                    $responce['rows'][$i]['TotIva']=($row_ret_compra['TotIva']!=null?$row_ret_compra['TotIva']:0);
		    $responce['userdata']['Cant']=$responce['userdata']['Cant']+$responce['rows'][$i]['Cant'];                    
                    $responce['userdata']['TotRen']=$responce['userdata']['TotRen']+$responce['rows'][$i]['TotRen'];
                    $responce['userdata']['TotIva']=$responce['userdata']['TotIva']+$responce['rows'][$i]['TotIva'];                    
                }
                utf8_encode_deep($responce['rows']); 
                echo json_encode($responce);
                exit();
}
/**
* Inicializa la variable op cuando no esta seteada la misma
*/
if (!(isset($op)))
	$op = 1; 	   
	
/**
* Consulta el tipo de comprobante 
*/
$rs_tip_compr = $obBD_con1->getArrayConsulta(729, '', $obBD_conexion);	
		
/**
* Consultar los a�os para la consulta de las facturas de compras 
*/
$rs_anios = $obBD_con1->getArrayConsulta(247, $Ses_Emp_Cod, $obBD_conexion); 	
		
/**
* opciones del switch 
*/
switch ($op){
	case 1: 
	/**
	* Cargado de los datos de la cabecera 
	*/
	if (isset($txt_busqueda) && $txt_busqueda != "")
	{  	
		if ($op_opciones == "d")
		{	
			/**
			* Consulta de las facturas de compra por apellido 
			*/
			$rs_buscar = $obBD_con1->getArrayConsulta(483,trim($txt_busqueda).'*'.$Tic_Cod.'*'.$cmb_anio.'*'.$cmb_mes.'*'.$Ses_Emp_Cod,$obBD_conexion); 
		}
		elseif($op_opciones == "ru")
		{		
			/**
			* Consulta de las facturas de compra por RUC 
			*/
			$rs_buscar = $obBD_con1->getArrayConsulta(485,trim($txt_busqueda).'*'.$Tic_Cod.'*'.$cmb_anio.'*'.$cmb_mes.'*'.$Ses_Emp_Cod,$obBD_conexion); 
		}
		else
		{
			/**
			* Consulta de las facturas de compra por n�mero de factura 
			*/
			$rs_buscar = $obBD_con1->getArrayConsulta(484, trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
	}//Fin del if ($txt_busqueda != "")
	break;
}//FIn del case $op

if(isset($ajax_info)){

	include('../COMPONENTES/tesComDetalleCom.php');
exit();
}

if(isset($ajax_detalle)){
	include("../COMPONENTES/tesComDetalleCom.php");  
exit();	
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Compras Consultar [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
        <?php require_once("../../mascaras/model1/estilos/estilos.php");?>

		<script type="text/javascript" src="../VALIDACIONES/fac_val_compras.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
         <?php if($op!=3){ ?>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>  
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <?php }else{ ?>
            <script type="text/ecmascript" src="../../framework/jquery/jquery.min/jquery-2.1.4.min.js"></script> 
            <script type="text/ecmascript" src="../../framework/jquery/jquery.ui/jquery-ui-1.11.4/jquery-ui.min.js"></script> 
            <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/jqgrid/jqgrid-5.0.0/css/ui.jqgrid.css" />
            <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/jqgrid/jqgrid-5.0.0/css/ui.fix.custom.css" />
            <script type="text/ecmascript" src="../../framework/jquery/jqgrid/jqgrid-5.0.0/js/i18n/grid.locale-es.js"></script>
            <script type="text/ecmascript" src="../../framework/jquery/jqgrid/jqgrid-5.0.0/js/jquery.jqGrid.min.js"></script>
            <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.basics-1.0.js"></script>
            <script type="text/ecmascript" src="../../Librerias/scripts/generales/jqgrid.ExcelExport.js"></script>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
            <script type="text/ecmascript" src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            
        <?php }; ?>
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
	    <!--Librerias para calendario -->       
        
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
       
        <!--<script type="text/javascript" src="../VALIDACIONES/XML.js"></script>		-->
        <script type="text/javascript">
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
			<?php if($op!=3){ ?>$('#set1 *').tooltip({showURL: false}); <?php }; ?>
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
	  <td height="10" colspan="3">&raquo; Consultar Documentos de Compras </td>    
  <tr>
  <tr>     
       <td  valign="top" height="400">
<form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
	<?php
	/*** opciones de las pesta�as del men� ***/
	$pag1= $_SERVER['PHP_SELF']."?op=1";
	$pag2= $_SERVER['PHP_SELF']."?op=2";
	$pag3= $_SERVER['PHP_SELF']."?op=3";
	$pag4= $_SERVER['PHP_SELF']."?op=4";
	//tabs(3,'Individual*Totales*Resumen*+Retenci&oacute;n', $pag1.'*'.$pag2.'*'.$pag3.'*'.$pag4, $op);
	tabs(2,'Individual*Resumen*Totales*+Retenci&oacute;n', $pag1.'*'.$pag3.'*'.$pag2.'*'.$pag4, $op);
	?>
    </form>
	<div id="ContTabul">	
            
<?Php
switch ($op)
{
	case 1: 
?>  
           <form name="formver" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
<FIELDSET>
<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="10%" class="Etiqueta1"  ><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
    <td width="90%">     
      <select name="Tic_Cod" id="Tic_Cod">      
      <?Php 
	  foreach($rs_tip_compr as $row_rs_tip_compr)
	  { ?>
      <option <?Php if (isset($Tic_Cod)&&$Tic_Cod == $row_rs_tip_compr['Tic_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option>
      <?php
	  } ?>
    </select></td>
  </tr>
</table>  
</FIELDSET>
<FIELDSET>
<legend>
	<label class="Titulos2">Buscar por:</label></legend>  
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
          <option <?Php if (isset($cmb_anio)&&$cmb_anio == $row_rs_anios['Anio']){ echo "selected";} ?> value="<?php echo $row_rs_anios['Anio']; ?>"><?php echo $row_rs_anios['Anio']; ?></option>
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
			$mes = isset($array_mes[1])?$array_mes[1]:'';		
		}//Fin del if (isset($cmb_mes))
		?>			
        <td width="193" align="right"><span class="Etiqueta1">Mes:&nbsp;
          <select name="cmb_mes" id="cmb_mes">
            <option value=""><< TODOS >></option>
            <?Php
				  for ($i=1;$i<=12;$i++)
				  { 
				  ?>
            <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?Php echo "AND MONTH(Cop_Fec)=$i"; ?>"><?php echo mes($i, 1) ?></option>
            <?Php
				  } ?>
          </select>
        </span></td>
      </tr>
    </table>
<table width="610" height="36" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="405" height="28" class="BarraBusqueda"><span class="Asterisco">* </span>Busqueda:
      <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50" />
      &nbsp; </td>
    <td width="205" align="center"><button type="button" name="btn-buscar" id="btn-buscar2" class="btn btn-success fileinput-button" title="Deudas" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button></td>
  </tr>
</table>
</FIELDSET>
</form>
<?Php
if(isset($txt_busqueda))
{ 
?> 
<FIELDSET> 
<LEGEND>
<label class='Titulos2'>Resultados de la busqueda</label>
</LEGEND>
<table width="100%"  border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
<thead>
  <tr>
    <th width="6%">C&oacute;d. Int.</th>
    <th width="6%">Compr.</th>
    <th width="8%">Forma  Pago</th>
    <th width="17%">No. Documento</th>
    <th width="8%">Fecha  </th>	  		  
    <th width="46%" align="center">Proveedor</th>
    <th width="2%">Pdf</th>
    <th width="2%">&nbsp;</th>
    <th width="5%">&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?Php
  if(count($rs_buscar) > 0)
  {
    $i=0;
	
   	foreach($rs_buscar as $row_rs_buscar)
	{ 
      if($row_rs_buscar['Cop_Est']=='I')
	  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  $i++; 
	  /*Consultamos si la compra tiene retencion*/
	  $row_ret_compra = $obBD_con1->getRowConsulta(718, $row_rs_buscar['Cop_Cod'], $obBD_conexion);	
	  
	  /**
	  * Consultar las fecha de comprobante 
	  */ 
	  $row_rs_comprobante_compra=$obBD_con1->getRowConsulta(345, $row_rs_buscar['Cop_Cod'], $obBD_conexion);			 
	  ?>      	 
      <tr>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php $Cop_Cod_Int=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod_Int; ?></FONT></td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>">
	         <?Php 
		/**
		* Consultar si la factura se registro de forma autom�tica y tiene un comprobante contable
		*/
		$rs_compra_manual_automatica=$obBD_con1->getRowConsulta(369, $row_rs_comprobante_compra['Com_Cod'], $obBD_conexion);

		if(count($rs_compra_manual_automatica)>0)
			echo "Si";
		else
			echo "No";																
		?>
	         </FONT></td>
	    <td align="center"><font color="<?php echo $rojo;?>"><?php
           $rs_formaPago=$obBD_con1->getRowConsulta(362, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
		   if (count($rs_formaPago)>0)
		   		echo "Cr&eacute;dito";
		   else
		   		echo "Contado";
		   ?></FONT></td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>">
	      <?Php $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?>
	      </FONT>
	      </td>
	    <td align="center">
	      <FONT COLOR="<?php echo $rojo;?>">
	        <?Php $Fecha_Fac=$row_rs_buscar['Cop_Fec'];  echo $Fecha_Fac; ?>
	        </FONT>
	      </td>
	    <td align="left">
	      <FONT COLOR="<?php echo $rojo;?>">
	        <?Php echo $row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom']; ?>
	        </FONT>
	      </td>
	    <td align="center">
        <?php if($row_ret_compra['Ret_Aut']=='S'){ ?>
        <form name="frm_pdf" id="frm_pdf" action="../COMPONENTES/tesPdfElectronicos.php?type=RETENC&Doc_Cod=<?php echo $row_ret_compra['Ret_Cod'];?>" method="post" target="_blank">
            <button type="button" class="btn btn-primary btn-mini" title="Pdf(Retenci&oacute;n electr&oacute;nica SRI)" onclick="this.form.submit()">
            <i class=" icon-download-alt icon-white"></i> <span></span> </button>
            <input name="urlXml" id="urlXml" type="hidden" value="<?Php echo '../FRONT/'.$Ses_Emp_Cod.'/'.$row_ret_compra['Ret_Xml'].'_A.xml';?>">
            <input name="op" id="op" type="hidden" value="I">
            <input name="logoUrl" id="logoUrl" type="hidden" value="<?php echo $Ses_Emp_Log;?>">            
        </form>        
        <?php }?>
        </td>
	    <td align="center"><button type="button" class="btn btn-info btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&amp;com_codigo=<?php echo $row_rs_buscar['Cop_Cod'];?>&Ses_Suc_Cod=<?php echo $Ses_Suc_Cod;?>&Ses_Emp_Cod=<?php echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>	
	    <td align="center">
	      <?Php if ($row_rs_buscar['Cop_Est'] == 'A') { ?>
	       <form method="post" name="pasar" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
	        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
	          <i class=" icon-arrow-right icon-white"></i>
	          </button>       
	        <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Cop_Cod'];?>">
	        <input name="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda; ?>" >
	        <input name="volver_op" type="hidden" value="<?Php echo $op_opciones; ?>">
	        <input name="op" type="hidden" value="<?Php echo $op; ?>">
	        <input name="cmb_anio" type="hidden" value="<?Php echo $cmb_anio; ?>">
	        <input name="cmb_mes" type="hidden" value="<?Php echo $cmb_mes; ?>">
	        <input name="Tic_Cod" type="hidden" value="<?Php echo $Tic_Cod; ?>">
	        <input name="Prv_Cod" id="Prv_Cod" type="hidden" value="<?Php echo isset($row_rs_buscar['Prv_Cod'])?$row_rs_buscar['Prv_Cod']:'';?>">
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
  <?Php  
	/**
	* Control para ocultar el detalle de las filas 
	*/
	if(count($rs_buscar) != 0)
	{
		ocultarDetalle(count($rs_buscar));
	}
?>      
</FIELDSET>
	<?php
	echo barra_estado(count($rs_buscar)); 
	}	
	break; //fin del case 1
	case 2: ?>
<form name="form2" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
<FIELDSET>
<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="10%" class="Etiqueta1"><span class="Asterisco">*</span> Tipo documento:</td>
    <td width="90%">
      <select name="Tic_Cod" id="Tic_Cod">
      <option value="T"><< TODOS >></option>
      <?Php 
	  foreach($rs_tip_compr as $row_rs_tip_compr)
	  { ?>
      <option <?Php if (isset($Tic_Cod)&&$Tic_Cod == $row_rs_tip_compr['Tic_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option>
      <?php
	  } ?>
    </select>
    <span class="Etiqueta1">
    <input name="Chk_Ret" type="checkbox" id="Chk_Ret" value="1">
Compras no sujetas a retenci&oacute;n</span></td>
  </tr>
</table>
</FIELDSET>
   <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Tipo de sustento tributario:</label>
	</LEGEND>
    <table width="79%" height="27" border="0" cellpadding="0" cellspacing="0" >
    <tr>
      <td width="15%" height="23" class="Etiqueta1"><span class="Asterisco">*</span> Sustento tributario:</td>
      <td width="85%">
	  <?php
	  /**
	  * Consulta el sustento 
	  */
	  //$rs_sustento = $obBD_con1->getArrayConsulta(711, '', $obBD_conexion);
          $rs_sustento = $obBD_con1->getArrayConsulta(1057, '', $obBD_conexion);  
 ?>
	   <select name="Tri_Cod" id="Tri_Cod">
         <option value="T"><< TODOS >></option>
         <?php 
		 foreach ($rs_sustento as $row_rs_sustento)
		 { ?>
         <option <?Php if (isset($Tri_Cod) && $Tri_Cod == $row_rs_sustento['Tri_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_sustento['Tri_Cod'];?>">
                 <?Php  echo $row_rs_sustento['Tri_Sri'].' - '.$row_rs_sustento['Tri_Des'];?> 
		 </option>
         <?php					
	 	 } 
		?>
	    </select>		
        </td>
    </tr>
    </table>
</FIELDSET>
<table width="99%" border="0">
     <tr>
     	<td colspan="2">
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Buscar Por:</label>
        </LEGEND>
        <table width="64%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="23%"><label>
              <input name="op_busqueda" type="radio" id="op_busqueda" value="F" onclick="document.getElementById('Bus_fechas').className = 'muestra';document.getElementById('Bus_ci').className = 'oculta'" checked="checked" />
            </label>
              <span class="Titulos2">Rango de Fechas</span></td>
            <td width="32%"><label>
              <input name="op_busqueda" type="radio" id="op_busqueda" value="C" onclick="document.getElementById('Bus_ci').className = 'muestra';document.getElementById('Bus_fechas').className = 'oculta'" />
            </label>
              <span class="Titulos2">C.I./R.U.C.</span></td>
            <td width="38%"><label>
              <input name="op_busqueda" type="radio" id="op_busqueda" value="A" onclick="document.getElementById('Bus_ci').className = 'muestra';document.getElementById('Bus_fechas').className = 'muestra';" />
            </label>
              <span class="Titulos2">C.I./R.U.C. + Fechas</span></td>  
            <td width="7%">&nbsp;</td>
            
          </tr>
        </table>
        </FIELDSET>
        </td>
     </tr>
     <tr>
       <td width="54%" valign="top">
           <table width="100%" border="0" cellpadding="0" cellspacing="0">           
           <tr id="Bus_ci">
               <td>
               <FIELDSET>
               <LEGEND>
               <label class="Titulos2">CI/RUC:</label>
               </LEGEND>
               	<span class="Titulos2">C.I./R.U.C:</span><input name="txtCi" type="text" id="txtCi" maxlength="13"  />
               </FIELDSET>
               </td>
		   </tr>
            <tr id="Bus_fechas">
               <td><?php include("../../componentes/FRONT/com_con_fecha.php"); ?></td>
		   </tr>   
           </table>               
           <script type="text/javascript">ShowHide('Bus_ci');</script>
       </td>
       <td width="46%" valign="top">
	   <?php include("../../componentes/FRONT/com_con_estado.php"); ?>
       </td>
     </tr>
</table>
<table width="212" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="212">
      <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_fec_ini*txt_fec_fin', 0)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button> 
        <input name="hdd" type="hidden" id="hdd" value="1">
        <input name="op" type="hidden" id="op" value="<?Php echo $op; ?>">
        </td>
    </tr>
  </table>
  </form>
  <?Php
if(isset($hdd))
{ 
?>
    <br />
      <FIELDSET>
      <LEGEND>
    <label class="Titulos2">Resultados de la b&uacute;squeda:</label>
    </LEGEND>
     <div id="Exportar_a_Excel">
    <?php if($op_busqueda=='F'){?>
    <table width="59%" border="0">
      <tr>
        <td width="13%" class="Etiqueta1"><div align="right">Desde:</div></td>
        <td width="15%" class="LetraNegra">&nbsp;<?Php echo $txt_fec_ini?></td>
        <td width="7%" class="Etiqueta1">Hasta:</td>
        <td width="16%" class="LetraNegra">&nbsp;<?Php echo $txt_fec_fin?></td>
        <td width="15%" class="Etiqueta1">Estado:</td>
        <td width="34%" class="LetraNegra">&nbsp;<?Php if($optest=='A'){ echo "Activas";  }else { echo "Anuladas"; }  ?></td>
      </tr>
    </table>
	<?php }else{?>
    <table width="60%" border="0">
      <tr>
        <td width="13%" class="Etiqueta1"><div align="right">C.I/R.U.C:</div></td>
        <td width="14%" class="LetraNegra">&nbsp;<?Php echo $txtCi?></td>
        <td width="12%" class="Etiqueta1">Proveedor:</td>
        <td width="34%" class="LetraNegra">&nbsp;
		<?Php 
		$rs_buscarProvee = $obBD_con1->getRowConsulta(1070, $txtCi.'*'.$Ses_Emp_Cod, $obBD_conexion);
		echo $rs_buscarProvee['Prs_Ape'].' '.$rs_buscarProvee['Prs_Nom'];
		?>
        </td>
        <td width="7%" class="Etiqueta1">Estado:</td>
        <td width="20%" class="LetraNegra">&nbsp;<?Php if($optest=='A'){ echo "Activas";  }else { echo "Anuladas"; }  ?></td>
      </tr>
    </table>
    <?php }?>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
    <thead>                           
    <tr>
      <th width="4%">C&oacute;d. Int. </th>
      <th width="11%" align="center">Tip. Doc.</th>
      <th width="11%" align="center">Nro. Doc.</th>
      <th width="8%">Autorizaci&oacute;n</th>
      <th width="8%" >Fecha </th>
      <th width="7%" align="center">C&eacute;dula/Ruc</th>
      <th width="16%" colspan="3">Proveedor</th>
	<?php 
    if(!isset($sustento))
    { 
		if($op_busqueda=='F')
		{
			/** 
			* Busqueda de facturas en un rango de fechas  
			*/   
			$rs_buscar = $obBD_con1->getArrayConsulta(537, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$Tic_Cod.'*'.$optest.'*'.$Ses_Emp_Cod, $obBD_conexion);     
		}else{
			/** 
			* Busqueda de facturas por cedula de proveedor  
			*/
			$rs_buscar = $obBD_con1->getArrayConsulta(1067, $txtCi.'*'.$Tic_Cod.'*'.$optest.'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
	}//Fin del if(!isset($sustento))
    
    /**
    * Prepara la cadena concatenando los tipos de documentos para enviarlos como parametro de la sql
    */
    if($Tic_Cod=='T')
    {	  
        $par_sql='';
		 foreach($rs_tip_compr as $row_rs_tip_compr)
		 {  
			$par_sql=$par_sql.'compras.Tic_Cod='.$row_rs_tip_compr['Tic_Cod'].'  OR ';
		 }  
		 $par_sql=substr($par_sql,0,strlen($par_sql)-4);
		 $par_sql='('.$par_sql.')';
    }
    else
    {
        $par_sql='compras.Tic_Cod='.$Tic_Cod;
    }
    
    /**
    * Prepara la cadena concatenando los sustentos tributarios para enviarlos como parametro de la sql
    */	
    if ($Tri_Cod !='T')
    {		
        $sustento_cod[]=$Tri_Cod;
    }//fin del if $Tri_Cod; 
    else
    { 
        foreach($rs_sustento as $row_rs_sustento)
        { 						
            $sustento_cod[]= $row_rs_sustento['Tri_Cod']; 			
        }
    }//fin del else
   
    /**
    * Buscar las compras cuando NO se ha seleccionado "Compras no sujetas a retenci�n"
    */	
    if(empty($Chk_Ret))
    { 
        /**
        * Contador del boton mas + menos -
        */
        $i=0; 
        $fila = 0;
        $max_adq=array();
        $max_com=array();
		if($op_busqueda=='F')
		{	 
			 /**
			 * Consulto las adquisiciones del rango de fechas 
			 */
			$rs_adquisio = $obBD_con1->getArrayConsulta(324, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$par_sql.'*'.$optest.'*'.$Ses_Emp_Cod, $obBD_conexion);
		}else{
			$rs_adquisio = $obBD_con1->getArrayConsulta(1068, $par_sql.'*'.$txtCi.'*'.$optest.'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
        $maximo=count($rs_adquisio);
         
          /**
          * Despliega el n�mero de columnas de tipos de adquisiciones  
          */
          for($j=0; $j<count($rs_adquisio); $j++)
          { ?>
            <th width="2%">Base 0%<br><font title="<?php echo $rs_adquisio[$j]['Adq_Des'];?>">[<?php echo $rs_adquisio[$j]['Adq_Cor'];?>]</font></th>
            <th width="2%">Base IVA<br><font title="<?php echo $rs_adquisio[$j]['Adq_Des'];?>">[<?php echo $rs_adquisio[$j]['Adq_Cor'];?>]</font></th>
          <?php }?>
              <th width="3%">Desc.</th>
              <th width="3%">IVA</th>
              <th width="3%">ICE</th>
              <th width="3%">TOTAL</th>
              <th width="2%">Pdf</th>
              <th width="2%">&nbsp;</th>
              <th width="2%">&nbsp;</th>
          </tr>
          </thead>
          <tbody>
		  <?php
        /**
        * Recorrido de los tipos de sustentos tributarios 
        */
        for ($x=0; $x<=count($sustento_cod)-1; $x++)
        {	 		
		    			
			//if($op_busqueda=='F')
			//{
				/**
				* Consultar las facturas de compras en base a la fecha de inicio, fin, tipo de comprobante, estado y sustento tributario 
				*/
			//	$rs_buscar = $obBD_con1->getArrayConsulta(725, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$par_sql.'*'.$optest.'*'.$sustento_cod[$x].'*'.$Ses_Emp_Cod, $obBD_conexion);
			//}else{
				/**
				* Consultar las facturas de compras en base a la cedula, tipo de comprobante, estado y sustento tributario 
				*/
			//	$rs_buscar = $obBD_con1->getArrayConsulta(1069, $txtCi.'*'.$par_sql.'*'.$optest.'*'.$sustento_cod[$x].'*'.$Ses_Emp_Cod, $obBD_conexion);
			//}
			if($op_busqueda=='A')
            {
                    /*Por Fechas*/	
                    $paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
                    $paramFecCi=$paramFecCi." AND Prs_Ced='".$txtCi."' ";
            }
            if($op_busqueda=='F')
            {
                    $paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
            }
            if($op_busqueda=='C')
            {    
                    /*Por Ruc*/
                    $paramFecCi=" AND Prs_Ced='".$txtRuc."'";
            }

            /**
            * Consultar las facturas de compras en base a la fecha de inicio, fin, tipo de comprobante, estado y sustento tributario 
            */
            $rs_buscar = $obBD_con1->getArrayConsulta(1099, $paramFecCi.'*'.$par_sql.'*'.$optest.'*'.$sustento_cod[$x].'*'.$Ses_Emp_Cod, $obBD_conexion); 
			$total_rs_buscar = count($rs_buscar); 
            $row = current($rs_buscar);
    		
            if (count($rs_buscar) > 0)	
            { 
                $contar_resultado=$contar_resultado+count($rs_buscar);
                /**
                * Variable columnas el maximo numero de tipos de aquisiciones 
                * por compra multiplicado por la frecuencia 2 
                */
                $columnas=$maximo*2;
                /**
                * Sumo en la variable columnas el numero de colspan=13 
                */
                $columnas=$columnas+14;
             ?> 
            
            
		 
                <?Php
                /***
                * consultar el iva activo 
                * "Esto deber� cambiarse a cargar el Iva en base a la compra"
                */		
                $rs_iva_com = $obBD_con1->getArrayConsulta(727, $row_rs_buscar['Cop_Cod'], $obBD_conexion); 
                $xiv=0;
                foreach($rs_iva_com as $row_rs_iva_com)
                {
                    $iva_codigo[$xiv]=$row_rs_iva_com['Iva_Cod'];
                    $iva_porcentaje[$xiv]=$row_rs_iva_com['Iva_Por'];
                    $xiv++;
                }
            
                /**
                * Inicializo el contador en 0 
                */
                $acumtotal=0;
                foreach($rs_buscar as $row_rs_buscar)
                { 
                    /*Consultamos si la compra tiene retencion*/
		    		$row_ret_compra = $obBD_con1->getRowConsulta(718, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
					
					if (strlen($row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'])>20)
					{
						$proveedor= substr($row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'],0,20).'...';	
					}else{
						$proveedor= $row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'];	
					}
					
					if(strlen($row_rs_buscar['Cop_Aut'])>10)
					{
						$Cop_Aut=substr($row_rs_buscar['Cop_Aut'],0,10);   
						$AutNum=$Cop_Aut.'...';
					}else{
						$Cop_Aut=$row_rs_buscar['Cop_Aut'];
						$AutNum=$Cop_Aut;
					}
					
					$acumtotal=$acumtotal+count($rs_buscar);
                    $i++;
                    $fila++;
                    if($row_rs_buscar['Cop_Est']=='I')
                      { $rojo='#FF0000'; $anulada++; }else{$rojo='';}		
                    ?>                     
                  <tr >
                    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Cop_Cod=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod; ?></FONT></td>
                    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?PHP echo $row_rs_buscar['Tic_Des']; ?></FONT></td>
                    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?></FONT></td>
                    <td align="center" style="mso-number-format:'@';" title="<?php echo $row_rs_buscar['Cop_Aut'];?>"><FONT COLOR="<?php echo $rojo;?>"><?php echo $AutNum; ?></FONT></td>
                    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Fec_Com=$row_rs_buscar['Cop_Fec']; echo $Fec_Com; ?></FONT></td>
                    <td align="center" style="mso-number-format:'@';"><FONT COLOR="<?php echo $rojo;?>"><?PHP $Prs_Ced= $row_rs_buscar['Prs_Ced']; echo $Prs_Ced; ?></FONT></td>
                    <td colspan="3" title="<?php echo $row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'];?>"><FONT COLOR="<?php echo $rojo;?>"><?PHP echo $proveedor; ?></FONT>
                    </td>
                    <?php 			
                    if(count($rs_adquisio) > 0)
                    { 
                        /** 
                        * Inicio un contador en cero xc 
                        */
                        $xc=0;
                        /** 
                        * inicializo el contador del iva en 0 
                        */
                        $xiv=0;		
                        
                        foreach($rs_adquisio as $row_rs_adquisio)
                        {
                            /**
                            * $iva_codigo[0] representa el %0
                            */
                        ?><td  align="right"><FONT COLOR="<?php echo $rojo;?>"><?php  
                        $row_importe_comp = $obBD_con1->getRowConsulta(323, $row_rs_buscar['Cop_Cod'].'*'.$row_rs_adquisio['Adq_Cod'].'*'.$iva_codigo[0], $obBD_conexion); 		
						   if ($row_importe_comp['Iva_Por']==0)
						   {  
								if($row_importe_comp['Importe']>0)
								{
									echo formato_numero($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2,1);
									/**
									* Acumulador para las compras con base cero (0) 
									*/
									 $sum_cero[$xc]=$sum_cero[$xc]+round($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2);
								}
								else
								{
									$sum_cero[$xc]=$sum_cero[$xc]+0;
								}			  
						   }
						   else  
						   {  
							  /**
							  * Acumulador para las compras con base cero (0) 
							  */
							  $sum_cero[$xc]=$sum_cero[$xc]+0;
                       	   } 	
                        /**
                        * $iva_codigo[1] representa el %12
                        */
                        ?>
                         </FONT></td>
                        <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php	
                        $row_importe_comp = $obBD_con1->getRowConsulta(323, $row_rs_buscar['Cop_Cod'].'*'.$row_rs_adquisio['Adq_Cod'].'*'.$iva_codigo[1], $obBD_conexion); 
                         if ($row_importe_comp['Iva_Por']!=0)
                          { 		   		 	  
                              if($row_importe_comp['Importe']>0)
                              {			  
                                  echo formato_numero($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2,1);
                                  /**
                                  * Acumulador para las compras con base cero (0) 
                                  */
                                  $sum_base[$xc]=$sum_base[$xc]+round($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2);
                              }
                              else
                              {
                                  $sum_base[$xc]=$sum_base[$xc]+0;
                              }
                           }
                           else  
                           {  
                              /**
                              * Acumulador para las compras con base cero (0) 
                              */
                              $sum_base[$xc]=$sum_base[$xc]+0;
                           }
                          ?>
                          </FONT></td>
                        <?Php
                            $xiv++;
                            $xc++;		
						}//Fin del if(count($rs_adquisio) > 0)
					 // }
                    }//Fin del foreach($rs_buscar as $row_rs_buscar)
                
    
                $iva_factura=0;
                ?>
                <?php $resultados = $obBD_con1->getRowConsulta(1092, $Ses_Emp_Cod.'*'."AND compras.Cop_Cod='$Cop_Cod'".'*'.''.'*'.'', $obBD_conexion);//explode('*', $obBD_con1->calculosCompraIce($Cop_Cod, $obBD_conexion)); ?>
                <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php echo empty($resultados['Descu'])||$resultados['Descu']*1==0?'&nbsp':formato_numero($resultados['Descu'],2,1); $tot_descu+=formato_numero($resultados['Descu'],2,1)*1; ?></FONT></td>
                <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php	
                /**
                * Retorno los calculos de las facturas 
                */
                if($resultados['IvaTot']*1>0)
                { 
                    $iva_factura=formato_numero($resultados['IvaTot'],2,1);
                    echo $iva_factura; 
                    /**
                    * Acumulo en iva_tot el total de las facturas de compras 
                    */		
                    $iva_tot=$iva_tot+$iva_factura*1;  	
                }
                else 
                { 
                    echo "&nbsp"; 
                } ?>
                </FONT>        
                </td>
                <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php echo empty($resultados['IceTot'])||$resultados['IceTot']*1==0?'&nbsp':formato_numero($resultados['IceTot'],2,1); $tot_ice+=formato_numero($resultados['IceTot'],2,1)*1; ?></FONT></td>
                <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php echo formato_numero($resultados['total'],2,1);
                        /**
                        * Acumulo en tot_fac el total de las facturas de compras 
                        */		
                        $tot_fac=$tot_fac+formato_numero($resultados['total'],2,1)*1;			   
                        ?></FONT> 
                </td>
                <td align="center">
                <?php if($row_ret_compra['Ret_Aut']=='S'){ ?>
                <form name="frm_pdf" id="frm_pdf" action="../COMPONENTES/tesPdfRetencionElectronica_1.0.php" method="post" target="_blank">
                    <button type="button" class="btn btn-primary btn-mini" title="Pdf(Retenci&oacute;n electr&oacute;nica SRI)" onclick="this.form.submit()">
                    <i class=" icon-download-alt icon-white"></i> <span></span> </button>
                    <input name="urlXml" id="urlXml" type="hidden" value="<?Php echo '../FRONT/'.$Ses_Emp_Cod."/".$row_ret_compra['Ret_Xml']."_A.xml";?>">
                    <input name="op" id="op" type="hidden" value="I">
                    <input name="logoUrl" id="logoUrl" type="hidden" value="<?php echo $Ses_Emp_Log;?>">            
                </form>
                <?php }?>
                </td>
                <td align="center"><button type="button" class="btn btn-info btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&amp;com_codigo=<?php echo $row_rs_buscar['Cop_Cod'];?>&amp;Ses_Emp_Cod=<?php echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
                <td align="center"><?Php 
                if ($row_rs_buscar['Cop_Est'] == 'A') 
                { ?>
                 <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" name="form2" id="form2">
                    <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
                        <i class=" icon-arrow-right icon-white"></i>
                    </button>        
                    <input name="txt_fec_fin" type="hidden" value="<?Php echo $txt_fec_fin; ?>" >
                    <input name="volver_op" type="hidden" value="<?Php echo isset($op_opciones)?$op_opciones:''; ?>">
                    <input name="op_busqueda" id="op_busqueda" type="hidden" value="<?Php echo $op_busqueda;?>">                    
                    <input name="op" type="hidden" value="<?Php echo $op; ?>">
                    <input name="txt_fec_ini" type="hidden" value="<?Php echo $txt_fec_ini; ?>">
                    <input name="Tri_Cod" type="hidden" value="<?Php echo $Tri_Cod; ?>">
                    <input name="txtCi" id="txtCi" type="hidden" value="<?Php echo $txtCi; ?>">                    
                    <input name="optest" type="hidden" value="<?Php echo $optest; ?>">
                    <input name="Tic_Cod" type="hidden" value="<?Php echo $Tic_Cod; ?>">
                    <input name="Chk_Ret" type="hidden" id="Chk_Ret" value="">
                    <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Cop_Cod'];?>">
                    <input name="Prv_Cod" id="Prv_Cod" type="hidden" value="<?Php echo isset($row_rs_buscar['Prv_Cod'])?$row_rs_buscar['Prv_Cod']:'';?>">
                  </form>
                <?Php 
                } 
                else 
                { 
                    echo "&nbsp;"; 
                } ?> 
                </td>
                </tr> 
                
             <?php } //Fin del count($rs_buscar) ?>   
				
        <?Php 					 
         }//fin for ($x=0; $x<=count($sustento_cod)-1; $x++)
         
         if (isset($sustento) && count($rs_buscar)==0)
         { 
              $colsp3=$colsp1+15; ?>
        <?php 
         }	 
	}
		if($contar_resultado>0)
		{ 
		/**
		* Inicio el if($contar_resultado>0)  
		*/ ?>
        <tfoot>
		<tr class="Cabecera1">
          <td colspan="9"><div align="right"><strong>SUBTOTAL POR TIPO DE COMPROBANTE :</strong></div></td>
          <?Php 
          for($j=0; $j< count($rs_adquisio); $j++)
          { ?>
          <td align="right"><?Php 
                if($sum_cero[$j]>0)
                { 
                    echo formato_numero($sum_cero[$j],2,1);  
                    $sum_cero_total[$j]=$sum_cero_total[$j]+$sum_cero[$j];
                    $sum_cero[$j]=0; 
                }
                else
                { 
                    $sum_cero_total[$j]=$sum_cero_total[$j]+0;
                    echo "&nbsp"; 
                } 
          ?></td>
          <td align="right"><?Php 
                if($sum_base[$j]>0)
                { 	  
                  echo formato_numero($sum_base[$j],2,1);  
                  $sum_base_total[$j]=$sum_base_total[$j]+$sum_base[$j];
                  $sum_base[$j]=0;  	  
                }
                else
                { 
                  $sum_base_total[$j]=$sum_base_total[$j]+0;
                  echo "&nbsp"; 	  	  
                }  ?>
          </td>
       <?Php } //fin del for($j=0; $j< count($rs_adquisio); $j++)?>
          <td width="3%" align="right"><?Php echo $tot_descu==0?'&nbsp;':formato_numero($tot_descu,2,1); $descu_total_factura+=$tot_descu; $tot_descu=0; ?></td>
          <td align="right"><?Php 
				if($iva_tot>0)
				{
					echo formato_numero($iva_tot,2,1);  
					$iva_total_factura=$iva_total_factura+$iva_tot; $iva_tot=0; 
				} 
				else 
				{ 
					echo "&nbsp";  
				} ?>
		  </td>
          <td width="3%" align="right"><?Php echo $tot_ice==0?'&nbsp;':formato_numero($tot_ice,2,1); $ice_total_factura+=$tot_ice; $tot_ice=0;  ?></td>
          <td width="3%" align="right"><?Php 
                if($tot_fac>0)
                { 
                    echo formato_numero($tot_fac,2,1); 
                    $total_facturas=$total_facturas+$tot_fac;  
                    $tot_fac=0; 
                }
                else
                { 
                    echo "&nbsp"; 
                } ?></td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr> 
        <tr class="Cabecera1">
		  <td colspan="9" align="right"><strong>TOTAL GENERAL:</strong></td>
		  <?Php 
		  for($j=0; $j<count($rs_adquisio); $j++)
		  { ?>
		  <td align="right"><?Php echo formato_numero($sum_cero_total[$j],2,1); ?></td>
		  <td  align="right"><?Php echo formato_numero($sum_base_total[$j],2,1); ?></td>
	  <?Php 
	  	  }//for($j=0; $j<count($rs_adquisio); $j++)  ?>
                  <td align="right"><?Php echo formato_numero($descu_total_factura,2,1); ?></td>
		  <td align="right"><?Php echo formato_numero($iva_total_factura,2,1); ?></td>
                  <td align="right"><?Php echo formato_numero($ice_total_factura,2,1); ?></td>
		  <td align="right"><?Php echo formato_numero($total_facturas,2,1); ?></td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
        </tfoot>
		<?Php
		}/* fin inicio el if(contar_resultado>0) */ 
 echo barra_estado($contar_resultado);
}//fin del if(empty($Chk_Ret))	
else
{ 
/**
* C o m p r a s    n o     s u j e t a s    a    r e t e n c i � n
*/
	/**
	* Defino un contador 
	*/
	$contador_acumulado_2=0;
	/**
	* Recorrido de los tipos de sustentos tributarios 
	*/
	for ($x=0; $x<=count($sustento_cod)-1; $x++)
	{				
	/**
	* Consultar las facturas de compras en base a la fecha de inicio, fin, tipo de comprobante, estado y sustento tributario 
	*/
	$rs_buscar = $obBD_con1->getArrayConsulta(326, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$par_sql.'*'.$optest.'*'.$sustento_cod[$x].'*'.$Ses_Emp_Cod, $obBD_conexion);
	$total_rs_buscar = count($rs_buscar);
	$row = current($rs_buscar);
	$contador_acumulado_2=$contador_acumulado_2+count($rs_buscar);
	//echo "-->".$contador_acumulado_2;
		if(count($rs_buscar)>0)
		{
	 ?>
		  <tr class="Cabecera1">
			<td colspan="16"><br><h3 align="center"> <?Php echo $row['Tri_Des']; ?></h3></td>
		  </tr>
		  <tr class="Cabecera1">
			<td width="4%">C&oacute;d. Int.</td>
			<td width="10%" align="center">Tip. Doc.</td>
			<td width="8%" align="center">Nro. Doc.</td>
			<td width="8%">Autorizaci&oacute;n</td>
			<td >Fecha </td>
			<td width="7%" align="center">C&eacute;dula/R.U.C</td>
			<td width="20%">Proveedor</td>
			<td width="16%">Importe</td>
			<td width="16%">IVA</td>
			<td width="4%">TOTAL</td>
			<td>&nbsp;</td>
			<td colspan="5">&nbsp;</td>
		  </tr>
		  <?Php
			$total_fac=0;
			$total_iva=0;
			$total_importe=0;
			
			foreach($rs_buscar as $row_rs_buscar)
			{
			$i++;
			$fila++;
			if($row_rs_buscar['Cop_Est']=='I')
			  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}		
			?>				
		  <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
			<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Cop_Cod=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod; ?></FONT></td>
			<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?PHP echo $row_rs_buscar['Tic_Des']; ?></FONT></td>
			<td width="8%" align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?>
			  </FONT></td>
			<td align="center" style="mso-number-format:'@';"><FONT COLOR="<?php echo $rojo;?>"><?php $Cop_Aut=$row_rs_buscar['Cop_Aut'];  echo $Cop_Aut; ?>
			  </FONT></td>
			<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Fec_Com=$row_rs_buscar['Cop_Fec'];  echo $Fec_Com; ?>
			  </FONT></td>
			<td align="center" style="mso-number-format:'@';"><FONT COLOR="<?php echo $rojo;?>"><?PHP $Prs_Ced= $row_rs_buscar['Prs_Ced']; echo $Prs_Ced; ?>
			  </FONT></td>
			<td  align="center"><FONT COLOR="<?php echo $rojo;?>"><?PHP echo $row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; ?></FONT></td>
			<td align="right" width="16%"><FONT COLOR="<?php echo $rojo;?>">
			<?php  $resultados = explode('*', $obBD_con1->calculosCompraIce($Cop_Cod, $obBD_conexion));
				   $total_importe = $total_importe +  round($row_rs_buscar['Importe']-($row_rs_buscar['Importe']*$row_rs_buscar['Cop_Des']/100),2);
				   echo formato_numero($row_rs_buscar['Importe']-($row_rs_buscar['Importe']*$row_rs_buscar['Cop_Des']/100),2,1);			 	  
			  ?></FONT></td>
			<td align="right"><FONT COLOR="<?php echo $rojo;?>"><?Php
				   $iva_factura=formato_numero($resultados[3],2,1);
				   //Total del iva
				   $total_iva = $total_iva + round($resultados[3],2);
				echo $iva_factura; ?> 
				</FONT></td>
			<td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php
					echo $resultados[5];
					/**
					* Acumulo en tot_fac el total de las facturas de compras 
					*/		
					$total_fac=$total_fac+$iva_factura+($row_rs_buscar['Importe']-($row_rs_buscar['Importe']*$row_rs_buscar['Cop_Des']/100));				   
					?></FONT></td>
			<td align="center"><button type="button" class="btn btn-info btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&amp;com_codigo=<?php echo $row_rs_buscar['Cop_Cod'];?>&amp;Ses_Emp_Cod=<?php echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
			<td colspan="5" align="center"><?Php 
				if ($row_rs_buscar['Cop_Est'] == 'A') 
				{ ?>
				   <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" name="form2" id="form2">
               <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
                    <i class=" icon-arrow-right icon-white"></i>
                </button>  
				<input name="txt_fec_fin" type="hidden" value="<?Php echo $txt_fec_fin; ?>" >
				<input name="volver_op" type="hidden" value="<?Php echo $op_opciones; ?>">
				<input name="op" type="hidden" value="<?Php echo $op; ?>">
				<input name="txt_fec_ini" type="hidden" value="<?Php echo $txt_fec_ini; ?>">
				<input name="Tri_Cod" type="hidden" value="<?Php echo $Tri_Cod; ?>">
				<input name="optest" type="hidden" value="<?Php echo $optest; ?>">
				<input name="Tic_Cod" type="hidden" value="<?Php echo $Tic_Cod; ?>">
				<input name="Chk_Ret" type="hidden" id="Chk_Ret" value="1">
				<input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Cop_Cod'];?>">
				<input name="Prv_Cod" id="Prv_Cod" type="hidden" value="<?Php echo $row_rs_buscar['Prv_Cod'];?>">
				</form>
				<?Php 
				} 
				else 
				{ 
					echo "&nbsp;"; 
				} ?>        
				</td>
		  </tr>		  
		  
		<?Php }//Fin del foreach $row_rs_buscar ?>
		<tr class="Cabecera1">
		  <td colspan="7" ><div align="right">SUBTOTAL POR TIPO DE COMPROBANTE :</div></td>
		  <td align="right"><?php echo formato_numero($total_importe,2,1); ?></td>
		  <td align="right"><?php echo  formato_numero($total_iva,2,1); ?></td>
		  <td align="right" ><strong>
		    <?Php 
			/**
			* Total acumulado 
			*/
			$total_acumulado=$total_acumulado+$total_fac;
			$acumulado_iva = $acumulado_iva + $total_iva;
			$acumulado_importe = $acumulado_importe + $total_importe;
			echo formato_numero($total_fac,2,1); ?>
		    </strong></td>
		  <td align="right" >&nbsp;</td>
		  <td colspan="5"align="right" >&nbsp;</td>
		  </tr>
        <?Php  
		} //fin del if(count($rs_buscar)>0)
		  /**
		  * Almaceno el total acumulado 
		  */
		  $tot_acumulado=$tot_acumulado+$tot_fac;
    } /* Fin si !existe Chk_Ret*/ 
	//if($contador_acumulado_2!=0 ){
   
	if (round($total_acumulado)>0)
	{
	?>
    <tr class="Cabecera1">
      <td colspan="7" align="right" ><strong>TOTAL GENERAL:</strong></td>
      <td align="right" ><?Php echo formato_numero($acumulado_importe,2,1); ?></td>
      <td align="right" ><?Php echo formato_numero($acumulado_iva,2,1); ?></td>
      <td align="right" ><strong><?Php echo formato_numero($total_acumulado,2,1); ?></strong></td>
      <td align="right" >&nbsp;</td>
      <td colspan="5" align="right" >&nbsp;</td>
    </tr>
    <?Php
	} 
//} ?>
    
  </table> 
</div>  
	<br>    
</FIELDSET>
	
   <?php } 
   		
	/**
	* Control para mostrar el boton imprimir
	*/
	//$contador_acumulado_2!=0 ||
	if( $contar_resultado!=0)
	{ 	
	?>
<br />
		<table border="0">
		  <tr>
			<td align="left">
			  <form  method="post" name="form3" action="fac_pri_fac_compra_1.0.php" target="_blank">
                   <button type="button" class="btn btn-primary start" title="Imprimir Documento" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
                  <input name="txt_fec_fin" id="txt_fec_fin" type="hidden" value="<?php echo $txt_fec_fin; ?>">
                  <input name="txt_fec_ini" id="txt_fec_ini" type="hidden" value="<?php echo $txt_fec_ini; ?>">                                          
                  <input name="Tri_Cod" id="Tri_Cod" type="hidden" value="<?Php echo $Tri_Cod; ?>" >
                  <input name="txtRuc" id="txtRuc" type="hidden" value="<?Php echo $txtCi; ?>" >                  
                  <input name="Tic_Cod" id="Tic_Cod" type="hidden" value="<?php echo $Tic_Cod; ?>">                                   
                  <input name="optest" type="hidden" value="<?php echo $optest; ?>">
                  <input name="op_busqueda" type="hidden" value="<?php echo $op_busqueda; ?>">
                  
                  <input type="hidden" name="Chk_Ret" value="<?Php echo $Chk_Ret; ?>">
			  </form>
          </td>
              <td>
              <form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
                <input type="hidden" id="datos_a_enviar" name="datos_a_enviar">
                <button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
                       <i class=" icon-share icon-white"></i>
                       <span>Excel</span>
                </button>
              </form>
              </td>
		  </tr>
		</table>
	<?Php
	} // fin del if($contador_acumulado_2!=0 || $contar_resultado!=0)
   
 } // fin del if ($hdd)  
 break;
 
 case 3:
 		
?> 		<form action="javascript:$('#comp').Search('#frm_buscar','ajaxGrid');" name="frm_buscar" id="frm_buscar">
 		<FIELDSET>
        <legend>
            <label class="Titulos2">Tiposs de documento:</label>
        </legend>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="10%" class="Etiqueta1"><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
            <td width="90%">     
              <select style="width: 200px" name="Tic_Cod" id="Tic_Cod">      
              <option value="T"><< TODOS >></option>
			  <?Php 
              foreach($rs_tip_compr as $row_rs_tip_compr)
              { ?>
              <option <?Php if (isset($Tic_Cod)&&$Tic_Cod == $row_rs_tip_compr['Tic_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option>
              <?php
              } ?>
            </select></td>
          </tr>
        </table>  
        </FIELDSET>
        
 	 
     <table width="99%" border="0">
     <tr>
     	<td colspan="2">
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Buscar Por:</label>
        </LEGEND>
        <table width="64%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="23%"><label>
              <input name="op_busqueda" type="radio" id="op_busqueda" value="F" onclick="document.getElementById('Bus_fechas').className = 'muestra';document.getElementById('Bus_ci').className = 'oculta'" checked="checked" />
            </label>
              <span class="Titulos2">Rango de Fechas</span></td>
            <td width="32%"><label>
              <input name="op_busqueda" type="radio" id="op_busqueda" value="C" onclick="document.getElementById('Bus_ci').className = 'muestra';document.getElementById('Bus_fechas').className = 'oculta'" />
            </label>
              <span class="Titulos2">C.I./R.U.C.</span></td>
            <td width="38%"><label>
              <input name="op_busqueda" type="radio" id="op_busqueda" value="A" onclick="document.getElementById('Bus_ci').className = 'muestra';document.getElementById('Bus_fechas').className = 'muestra'" />
            </label>
              <span class="Titulos2">C.I./R.U.C. + Fechas</span></td>              
            <td width="38%">&nbsp;</td>
          </tr>
        </table>
        </FIELDSET>
        </td>
     </tr>
     <tr>
       <td width="54%" valign="top">
           <table width="100%" border="0" cellpadding="0" cellspacing="0">
           
           <tr id="Bus_ci">
               <td>
               <FIELDSET>
               <LEGEND>
               <label class="Titulos2">CI/RUC:</label>
               </LEGEND>
               	<span class="Titulos2">C.I./R.U.C:</span><input name="txtCi" type="text" id="txtCi" maxlength="13"  />
               </FIELDSET>
               </td>
		   </tr>
             <tr id="Bus_fechas">
               <td><?php include("../../componentes/FRONT/com_con_fecha.php"); ?></td>
		   </tr>  
           </table>               
           <script type="text/javascript">ShowHide('Bus_ci');</script>
       </td>
       <td width="46%" valign="top">
	   <?php include("../../componentes/FRONT/com_con_estado.php"); ?>
       </td>
     </tr>
	 </table>
 	 <table width="212" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="212">
      <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_fec_ini*txt_fec_fin', 0)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button> 
        <input name="hdd" type="hidden" id="hdd" value="1">
        <input name="op" type="hidden" id="op" value="<?Php echo $op; ?>">
        </td>
    </tr>
  	</table>
    </form> 
    <FIELDSET> 
    <LEGEND>
    <label class='Titulos2'>Resultados de la busqueda</label>
    </LEGEND>
        <table id="comp"></table><div id="listPager"></div>
    </FIELDSET>     
    <?php if(isset($hdd)){
		
		if($Tic_Cod!='T'){
			$paramTipDoc=" AND Tic_Cod='".$Tic_Cod."'";
		}else{
			$paramTipDoc="";
		}
		if($op_busqueda=='A')
		{
                    /*Por Fechas*/	
                    $paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
                    $paramFecCi=$paramFecCi." AND Prs_Ced='".$txtCi."' ";
		}
                if($op_busqueda=='F')
                {
                    $paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
                }
                if($op_busqueda=='C')
                {    
			/*Por Ruc*/
                    $paramFecCi=" AND Prs_Ced='".$txtCi."'";
		}
		
		/*Busqueda por rango de fechas*/
		$rs_buscar = $obBD_con1->getArrayConsulta(1084, $Ses_Emp_Cod.'*'.$paramTipDoc.'*'.$paramFecCi.'*'.$optest, $obBD_conexion);
			
	?>  
    <FIELDSET> 
    <LEGEND>
    <label class='Titulos2'>Resultados de la busqueda</label>
    </LEGEND>    
    <table width="100%"  border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
    <thead>
      <tr>
        <th width="5%">C&oacute;d. Int.</th>
        <th width="4%">Secc.</th>
        <th width="7%">Fecha</th>	  		  
        <th width="10%">Tip. Doc.</th>	  		  
        <th width="10%">No. Documento</th>
        <th width="9%" align="center">C.I./R.U.C</th>        
        <th width="11%" align="center">Proveedor</th>
        <th width="7%">Sub 0%</th>
        <th width="7%">Sub 12%</th>
        <th width="7%">Iva</th>
        <th width="7%">Total</th>
      </tr>
      </thead>
      <tbody>
      <?Php
      if(count($rs_buscar) > 0)
      {
        $i=0;
        $sub0=0;
		$sub12=0;
		$ivaTot=0;
		$total=0;
        foreach($rs_buscar as $row_rs_buscar)
        { 
          if($row_rs_buscar['Cop_Est']=='I')
          { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
          $i++; 
		  if(strlen($row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom'])>14)
		  {
			$NomPrvCop=$row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; 
			$AuxNomPrvCop=substr($row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom'],0,14).'...';
		  }else{
			$NomPrvCop=$row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; 
			$AuxNomPrvCop=$NomPrvCop;
		  }
		  if(strlen($row_rs_buscar['Tic_Des'])>14)
		  {
			$NomTicDes=$row_rs_buscar['Tic_Des']; 
			$AuxNomTicDes=substr($row_rs_buscar['Tic_Des'],0,14).'...';
		  }else{
			$NomTicDes=$row_rs_buscar['Tic_Des']; 
			$AuxNomTicDes=$NomTicDes;
		  }
		  
          /*Consultamos si la compra tiene retencion*/
          $row_ret_compra = $obBD_con1->getRowConsulta(718, $row_rs_buscar['Cop_Cod'], $obBD_conexion);				 
          ?>      	 
          <tr>
            <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php $Cop_Cod_Int=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod_Int; ?></FONT></td>
            <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Cop_Sec'];?></FONT></td>
            <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php $Fecha_Fac=$row_rs_buscar['Cop_Fec'];  echo $Fecha_Fac; ?></FONT></td>
            <td align="left" title="<?php echo $NomTicDes;?>"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $AuxNomTicDes;?>&nbsp;</FONT></td>
            <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?></FONT></td>
            <td align="center"><font color="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced'];?></font></td>
            <td align="left" title="<?php echo $NomPrvCop;?>"><FONT COLOR="<?php echo $rojo;?>">&nbsp;<?Php echo $AuxNomPrvCop; ?></FONT></td>
            <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?Php $sub0=$sub0+$row_rs_buscar['Sub0']; echo formato_numero($row_rs_buscar['Sub0'],2,2);?>&nbsp;</FONT></td>	
            <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?Php $sub12=$sub12+$row_rs_buscar['Sub12']; echo formato_numero($row_rs_buscar['Sub12'],2,2);?>&nbsp;</FONT></td>
            <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?Php $ivaTot=$ivaTot+$row_rs_buscar['IvaTot']; echo formato_numero($row_rs_buscar['IvaTot'],2,2);?>&nbsp;</FONT></td>
            <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?Php $total=$total+($row_rs_buscar['IvaTot']+$row_rs_buscar['Sub12']+$row_rs_buscar['Sub0']); echo formato_numero($row_rs_buscar['IvaTot']+$row_rs_buscar['Sub12']+$row_rs_buscar['Sub0'],2,2);?>&nbsp;</FONT></td>            
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
             <td align="center">&nbsp;</td>
             <td align="center">&nbsp;</td>
             <td align="center">&nbsp;</td>
             <td align="center"><?php 
                $msg_mes = explode('=', $cmb_mes);
                echo error_alerta(" No hay resultados que mostrar para ".strtoupper($txt_busqueda)." en ".mes($msg_mes[1],1)." del ".$cmb_anio, 2);
             ?></td>
             <td align="center">&nbsp;</td>
             <td align="center">&nbsp;</td>
             <td align="center">&nbsp;</td>
             <td align="center">&nbsp;</td>
            </tr>
           <?Php
        } ?>        
        </tbody>
        <tfoot>
        <tr class="Cabecera1">
              <td colspan="7" align="right">Totales:&nbsp;</td>
              <td align="right"><?php echo formato_numero($sub0,2,2);?>&nbsp;</td>
              <td align="right"><?php echo formato_numero($sub12,2,2);?>&nbsp;</td>
              <td align="right"><?php echo formato_numero($ivaTot,2,2);?>&nbsp;</td>
              <td align="right"><?php echo formato_numero($total,2,2);?>&nbsp;</td>
        </tr>
        </tfoot>
      </table>
     
      <?Php  
        /**
        * Control para ocultar el detalle de las filas 
        */
        if(count($rs_buscar) != 0)
        {
            ocultarDetalle(count($rs_buscar));
        }
    ?>      
    </FIELDSET>
    <?php echo barra_estado(count($rs_buscar)); ?>
    <table width="117" border="0">
      <td width="111">
          
            <input type="hidden" id="datos_a_enviar" name="datos_a_enviar">
<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" onclick="downloadFile(exportarExcelBlob('excel','Reporte Resumen Compras'),'Reporte Resumen Compras-'+getDate()+'.xls')" title="Exportar Excel">
                   <i class=" icon-share icon-white"></i>
                   <span>Excel</span>
            </button>
          
          </td>
      </tr>
    </table>
    <div id="excel" style="display:none">
    <table width="2000"  border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse; table-layout:fixed;">
  <thead>
    <tr class="Cabecera1">
      <th colspan="17">RESUMEN DE COMPRAS</th>
      </tr>
    <tr class="Cabecera1">
      <th width="5%"><strong>C&oacute;d. Int.</strong></th>
      <th width="4%"><strong>Secc.</strong></th>
      <th width="7%"><strong>Fecha</strong></th>
      <th width="10%"><strong>Tip. Doc.</strong></th>
      <th width="5%"><strong>No. Documento</strong></th>
      <th width="5%">Autorizaci&oacute;n</th>
      <th width="9%" align="center">Num. Retenci&oacute;n</th>
      <th width="9%" align="center">Aut. Retenci&oacute;n</th>
      <th width="9%" align="center"><strong>C.I./R.U.C</strong></th>
      <th width="11%" align="center"><strong>Proveedor</strong></th>
      <th width="7%"><strong>Sub 0%</strong></th>
      <th width="7%"><strong>Sub 12%</strong></th>
      <th width="7%"><strong>Iva</strong></th>
      <th width="3%"><strong>Total</strong></th>
      <th width="15%"> Retenci&oacute;n (Renta)</th>
      <th width="15%">Retenci&oacute;n (Iva)</th>
      <th width="15%">Concepto</th>
    </tr>
  </thead>
  <tbody>
    <?Php
      if(count($rs_buscar) > 0)
      {
        $i=0;
        $sub0=0;
		$sub12=0;
		$ivaTot=0;
		$total=0;
        foreach($rs_buscar as $row_rs_buscar)
        { 
          if($row_rs_buscar['Cop_Est']=='I')
          { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
          $i++; 
		 
          /*Consultamos si la compra tiene retencion*/
          $row_ret_compra = $obBD_con1->getRowConsulta(1088, $row_rs_buscar['Cop_Cod'], $obBD_conexion);				 
          ?>
    <tr>
      <td align="center"><font color="<?php echo $rojo;?>"><?Php $Cop_Cod_Int=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod_Int; ?></font></td>
      <td align="center"><font color="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Cop_Sec'];?></font></td>
      <td align="center"><font color="<?php echo $rojo;?>"><?Php $Fecha_Fac=$row_rs_buscar['Cop_Fec']; echo $Fecha_Fac; ?></font></td>
      <td align="left"><font color="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Tic_Des'];?></font></td>
      <td align="center"><font color="<?php echo $rojo;?>"><?Php $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?></font></td>
      <td align="center" style="mso-number-format:'@'"><font color="<?php echo $rojo;?>"><?Php  echo $row_rs_buscar['Cop_Aut']; ?></font></td>
      <td align="center" style="white-space: nowrap; overflow: hidden;"><?php if($row_ret_compra['Ret_Num']!=''){echo str_pad($row_ret_compra['Ret_Num'], 9, "0", STR_PAD_LEFT);}?></td>
      <td align="center" style="mso-number-format:'@'"><?php echo $row_ret_compra['Aut_Sri'];?></td>
      <td align="center" style="mso-number-format:'@'"><font color="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced'];?></font></td>
      <td align="left"><font color="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; ?></font></td>
      <td align="right" style="mso-number-format:'0.00';"><?Php $sub0=$sub0+$row_rs_buscar['Sub0']; echo formato_numero($row_rs_buscar['Sub0'],2,2);?></td>
      <td align="right"><font color="<?php echo $rojo;?>"><?Php $sub12=$sub12+$row_rs_buscar['Sub12']; echo formato_numero($row_rs_buscar['Sub12'],2,2);?></font></td>
      <td align="right"><font color="<?php echo $rojo;?>"><?Php $ivaTot=$ivaTot+$row_rs_buscar['IvaTot']; echo formato_numero($row_rs_buscar['IvaTot'],2,2);?></font></td>
      <td align="right"><font color="<?php echo $rojo;?>"><?Php $total=$total+($row_rs_buscar['IvaTot']+$row_rs_buscar['Sub12']+$row_rs_buscar['Sub0']); echo formato_numero($row_rs_buscar['IvaTot']+$row_rs_buscar['Sub12']+$row_rs_buscar['Sub0'],2,2);?></font></td>
      <td align="right"><?Php echo $row_ret_compra['TotRen'];?></td>
      <td align="right"><?Php echo $row_ret_compra['TotIva'];?></td>
      <td align="right"><?php
      	$row_detcompra = $obBD_con1->getArrayConsulta(1085, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
		$cadCon="";
		foreach($row_detcompra as $datos)
		{
			if (count($row_detcompra)==1)
			{
				$cadCon=$cadCon.$datos['Cop_Pro'];
			}else{
				$cadCon=$cadCon.$datos['Cop_Pro'].",";		
			}						
		}
		echo $cadCon;
	  ?></td>
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
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center"><?php 
                $msg_mes = explode('=', $cmb_mes);
                echo error_alerta(" No hay resultados que mostrar para ".strtoupper($txt_busqueda)." en ".mes($msg_mes[1],1)." del ".$cmb_anio, 2);
             ?></td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
    </tr>
    <?Php
        } ?>
  </tbody>
  <tfoot>
    <tr class="Cabecera1">
      <td colspan="10" align="right"><strong>Totales:</strong>&nbsp;</td>
      <td align="right"><strong><?php echo formato_numero($sub0,2,2);?></strong></td>
      <td align="right"><strong><?php echo formato_numero($sub12,2,2);?></strong></td>
      <td align="right"><strong><?php echo formato_numero($ivaTot,2,2);?></strong></td>
      <td align="right"><strong><?php echo formato_numero($total,2,2);?></strong></td>
      <td align="right">&nbsp;</td>
      <td align="right">&nbsp;</td>
      <td align="right">&nbsp;</td>
    </tr>
  </tfoot>
</table>
    
     </div>
 	<?php }
break;
	?>
 
<?php case 4: 
   if($Ses_Prs_Cod=='1'){
?> 
  <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" name="frm_buscar" id="frm_buscar">
 		<FIELDSET>
        <legend>
            <label class="Titulos2">Tipo de documento:</label>
        </legend>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="10%" class="Etiqueta1"><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
            <td width="90%">     
              <select style="width: 200px" name="Tic_Cod" id="Tic_Cod">      
              <option value="T"><< TODOS >></option>
			  <?Php 
              foreach($rs_tip_compr as $row_rs_tip_compr)
              { ?>
              <option <?Php if ($Tic_Cod == $row_rs_tip_compr['Tic_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option>
              <?php
              } ?>
            </select></td>
          </tr>
        </table>  
        </FIELDSET>         	 
        <table width="99%" border="0">
         <tr>
            <td colspan="2">
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Buscar Por:</label>
            </LEGEND>
            <table width="64%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td width="23%"><label>
                  <input name="op_busqueda" type="radio" id="op_busqueda" value="F" onclick="document.getElementById('Bus_fechas').className = 'muestra';document.getElementById('Bus_ci').className = 'oculta'" checked="checked" />
                </label>
                  <span class="Titulos2">Rango de Fechas</span></td>
                <td width="32%"><label>
                  <input name="op_busqueda" type="radio" id="op_busqueda" value="C" onclick="document.getElementById('Bus_ci').className = 'muestra';document.getElementById('Bus_fechas').className = 'oculta'" />
                </label>
                  <span class="Titulos2">C.I./R.U.C.</span></td>
                <td width="7%">&nbsp;</td>
                <td width="38%">&nbsp;</td>
              </tr>
            </table>
            </FIELDSET>
            </td>
         </tr>
         <tr>
           <td width="54%" valign="top">
               <table width="100%" border="0" cellpadding="0" cellspacing="0">
               <tr id="Bus_fechas">
                   <td><?php include("../../componentes/FRONT/com_con_fecha.php"); ?></td>
               </tr>
               <tr id="Bus_ci">
                   <td>
                   <FIELDSET>
                   <LEGEND>
                   <label class="Titulos2">CI/RUC:</label>
                   </LEGEND>
                    <span class="Titulos2">C.I./R.U.C:</span><input name="txtCi" type="text" id="txtCi" maxlength="13"  />
                   </FIELDSET>
                   </td>
               </tr>
               </table>               
               <script type="text/javascript">ShowHide('Bus_ci');</script>
           </td>
           <td width="46%" valign="top">
           <?php include("../../componentes/FRONT/com_con_estado.php"); ?>
           </td>
         </tr>
         </table>
         <table width="212" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="212">
          <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_fec_ini*txt_fec_fin', 0)">
               <i class="icon-search icon-white"></i>
               <span>Buscar</span>
        </button> 
            <input name="hdd" type="hidden" id="hdd" value="1">
            <input name="op" type="hidden" id="op" value="<?Php echo $op; ?>">
            </td>
        </tr>
        </table>
    </form> 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
<?php 		 
   }else{
		echo "En construcci&oacute;n...!";   
   }
 break;
 
 }//fin del switch

if (isset($codigo) && $codigo>0)
{ 
	/**
	* Consulta datos de los proveedores
	*/
	$rs_proveed = $obBD_con1->getArrayConsulta(472, $codigo, $obBD_conexion); 
	$row = current($rs_proveed);
	$cliente = $row['Cop_Cod'];
?>
<br />
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Proveedor</label>
</LEGEND>
<table width="38%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="173" class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
    <td width="248" colspan="3" class="LetraNegra">&nbsp;<?Php echo $row['Prs_Ced'] ?></td>
 </tr>
 <tr>
<td width="173" class="Etiqueta1">Proveedor:</td>
<td colspan="3" class="LetraNegra">&nbsp;<?Php echo $row['Prs_Nom']."&nbsp".$row_rs_proveed['Prs_Ape']."&nbsp" ?></td>
  </tr>		  
  <tr>
    <td width="173" class="Etiqueta1">Direcci&oacute;n:</td>
    <td colspan="3" class="LetraNegra">&nbsp;<?php echo $row['Prs_Dir']?></td>
  </tr>
</table>
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos de la Factura </label>
</LEGEND>
 <FIELDSET>
<LEGEND>
<label class="Titulos2"> Generales </label>
</LEGEND>
  <table width="100%" border="0" cellspacing="0">
    <tr>
      <td width="16%"  class="Etiqueta1">No. Documento:</td>
      <td width="13%"  class="LetraNegra">&nbsp;<?Php $Cop_Numero=$row['Cop_Num']; echo $Cop_Numero;   ?></td>
      <td width="9%"  class="Etiqueta1">Autorizaci&oacute;n:</td>
      <td width="11%"  class="LetraNegra">&nbsp;<?Php $Cop_Aut=$row['Cop_Aut']; echo $Cop_Aut;  ?></td>
      <td width="13%"  class="Etiqueta1">Fecha de emisi&oacute;n:</span></td>
      <td width="38%"  class="LetraNegra">&nbsp;<?Php echo $row['Cop_Fec']   ?></td>
    </tr>
    <tr>
      <td width="16%"   class="Etiqueta1">Fecha de impresi&oacute;n: </td>
      <td width="13%"  class="LetraNegra" >&nbsp;<?Php  $Cop_Imf=$row['Cop_Imf']; echo $Cop_Imf;   ?> </td>
      <td width="9%"  class="Etiqueta1">Caducidad: </td>
      <td width="11%"  class="LetraNegra">&nbsp;<?Php $Cop_cad=$row['Cop_Cad']; echo $Cop_cad;   ?></td>
      <td width="13%"  class="Etiqueta1">Ciudad:</td>
      <td width="38%"  class="LetraNegra">&nbsp;<?Php  $Ciu_Des=$row['Ciu_Des'];  echo $Ciu_Des; ?></td>
    </tr>
    <tr>
      <td   class="Etiqueta1">Tipo de Sustento tributario  :</td>
      <td colspan="5" class="LetraNegra" >&nbsp;<?Php  $Tri_Des=$row['Tri_Des']; echo $Tri_Des;   ?> </td>
    </tr>            
    <tr>
      <td width="16%"  class="Etiqueta1">Observaci&oacute;n:</td>
      <td  colspan="5" class="LetraNegra">&nbsp;<?Php echo $row['Cop_Obs']   ?></td>
    </tr>
 </table>
 </FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la Factura</label>
</LEGEND>
<table width="90%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
<thead>
	<tr >
		<th>Cant.</th>
		<th>Descripci&oacute;n</th>
		<th>P. Unitario</th>
		<th>Importe</th>
		<th>Desc.</th>
		<th>I.V.A.</th>
	    <th>I.C.E.</th>
        <th>Adq.</th>
	</tr>
</thead>
<tbody>    
	<?Php 
	/**
	* % de Descuento total 
	*/
	$Cop_Des = $row['Cop_Des'];
	/**
	* Estado de la factura 
	*/
	$Cop_Est = $row['Cop_Est'];
	$Cod_Fac= $row['Cop_Cod'];

	foreach($rs_proveed as $row_rs_proveed)
	{
		$Des_Gen=$row_rs_proveed['Cop_Des'];
		$Cop_Est=$row_rs_proveed['Cop_Est'];
	?>
	<tr>
	  <td align="center"><?Php $can_com=$row_rs_proveed['Cop_Can'];  echo $can_com;  ?></td>
	  <td align="left"><?Php $prodto=$row_rs_proveed['Cop_Pro']; echo $prodto;  ?></td>
	  <td align="right"><?Php  $pre_uni=formato_numero($row_rs_proveed['Cop_Pru'],2,1); echo $pre_uni; ?></td>   
	  <td align="right"><?Php $Imp= formato_numero($row_rs_proveed['Cop_Imp'],2,1);  echo $Imp; ?></td>
	  <td align="right"><?Php  $Desc=$row_rs_proveed['Cop_Dec']; echo $Desc;?></td>
	  <td align="right"><?Php $Por_Iva=$row_rs_proveed['Iva_Por']; echo $Por_Iva; ?></td>
	  <td align="right"><?Php $Por_Ice=$row_rs_proveed['Cop_Ice']; echo $Por_Ice; ?>
	 <?Php  
	 	/**
		* Consulta los datos del ice
		*/
	 	//$row_porciento = $obBD_con1->getRowConsulta(527, $row_rs_proveed['Cop_Int'], $obBD_conexion);
	    //echo $row_porciento['Ice_Por']; ?></td>
      <td align="center"><?Php 
	  /**
	  * consulto el tipo de adquisici�n 
	  */
	  $row_rs_adquisicion_compra= $obBD_con1->getRowConsulta(325, $row_rs_proveed['Cop_Int'].'*'.$row_rs_proveed['Cop_Cod'], $obBD_conexion);
	  echo $row_rs_adquisicion_compra['Adq_Des'];  ?></td>
	</tr>
	<?Php 
	}//Fin del foreach $row_rs_proveed 
	/**
	*  Retorno los calculos de las facturas 
	*/
	//$resultados = explode('*',$obBD_con1->calculosCompraIce($codigo, $obBD_conexion));
        $resultados= $obBD_con1->getRowConsulta(1092, $Ses_Emp_Cod.'*'."AND compras.Cop_Cod='$row[Cop_Cod]'".'*'.''.'*'.'', $obBD_conexion);
                
	?>
    </tbody>
    
    <tr>
     	<td colspan="2">&nbsp;</td>
         <td class="Etiqueta1" align="right"><strong>SUBTOTAL:</strong></td>
         <td class="LetraNegra" align="right"><?Php echo formato_numero($resultados['Sub0']*1+$resultados['Sub12']*1,2,1); ?></td>
         <td colspan="4" class="LetraNegra">&nbsp;</td>
         </tr>
       <tr>
         <td colspan="2">&nbsp;</td>
         <td class="Etiqueta1" align="right"><strong>TARIFA 0% :</strong></td>
         <td class="LetraNegra"align="right"><?Php echo formato_numero($resultados['Sub0'],2,1); ?></td>
         <td colspan="4" class="LetraNegra">&nbsp;</td>
         </tr>
       <tr>
         <td colspan="2">&nbsp;</td>
         <td class="Etiqueta1" align="right"><strong>TARIFA 12% : </strong></td>
         <td class="LetraNegra" align="right"><?Php echo formato_numero($resultados['Sub12'],2,1); ?></td>
         <td colspan="4" class="LetraNegra">&nbsp;</td>
         </tr>
       <tr>
         <td colspan="2">&nbsp;</td>
         <td class="Etiqueta1" align="right"><strong>12% I.V.A. :</strong></td>
         <td class="LetraNegra" align="right"><?Php echo formato_numero($resultados['IvaTot'],2,1); ?></td>
         <td colspan="4" class="LetraNegra">&nbsp;</td>
         </tr>
       <tr>
         <td colspan="2">&nbsp;</td>
         <td class="Etiqueta1" align="right"><strong>I.C.E. :</strong></td>
         <td class="LetraNegra" align="right"><?php echo formato_numero($resultados['IceTot'],2,1); ?></td>
         <td colspan="4" class="LetraNegra">&nbsp;</td>
         </tr>
       <tr>
         <td colspan="2">&nbsp;</td>
         <td class="Etiqueta1" width="148" align="right"><strong>% DESCUENTO : <?Php echo $Des_Gen; ?></strong></td>
         <td class="LetraNegra"width="70" align="right"><?Php echo formato_numero($resultados['Descu'],2,1); ?></td>
         <td colspan="4" class="LetraNegra">&nbsp;</td>
         </tr>
       <tfoot>  
       <tr>
         <td colspan="2">&nbsp;</td>
         <td class="Etiqueta1"align="right"><strong>TOTAL : </strong></td>
         <td class="LetraNegra"align="right"><?php echo formato_numero($resultados['total'],2,1); ?></td>
         <td colspan="4" class="LetraNegra">&nbsp;</td>
         </tr>
       </tfoot>
	</table>
</FIELDSET>
</FIELDSET>        
<br>

	
     <table width="292" border="0" cellspacing="0" cellpadding="0">
       <tr>
         <td width="91">
         <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name= "Form3" >
         <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*op*hdd_buscar*cmb_anio*cmb_mes*Tic_Cod*Tri_Cod*txt_fec_ini*txt_fec_fin*hdd*optest*Chk_Ret*codigo*op_busqueda"; ?>', 
  '<?Php echo $volver_busqueda.'*'.$volver_op.'*'.$op.'*'.'1'.'*'.$cmb_anio.'*'.$cmb_mes.'*'.$Tic_Cod.'*'.$Tri_Cod.'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*'.'1'.'*'.$optest.'*'.$Chk_Ret.'*0'.'*'.$op_busqueda; ?>')">
               <i class=" icon-arrow-left icon-white"></i> 
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
         </form>
         </td>
         <td width="90">
         <form action="../FRONT/fac_pri_fac_detallecompras_1.0.php" method="post" target="_new" name= "Form5" >
         <button type="button" class="btn btn-primary start" title="Imprime Detalle" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Detalle</span></button> 
         <input type="hidden" id="com_codigo" name="com_codigo" value="<?php echo $codigo;?>" />
         </form>         
         </td>
         <td width="110">
         <?php
			$rs_comprob = $obBD_con1->getRowConsulta(1076, $Tic_Cod,$obBD_conexion);
			if($rs_comprob['Tic_Sri']=='3')
			{
			/**
			* Consulta del reporte para impresion 
			*/
			$pagina = $_SERVER['PHP_SELF'];
			$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);
			
				$hdd_liquidacion = $reportes[1];
			
		 ?>
         <form action="<?Php echo $hdd_liquidacion;?>" method="post" target="_new" name= "Form4" >
         <button type="button" class="btn btn-primary start" title="Imprime Liquidaci&oacute;n" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Liquidaci&oacute;n</span></button> 
         <input type="hidden" id="Cop_Cod" name="Cop_Cod" value="<?php echo $codigo;?>" />
         </form>
         <?php }?>
         </td>         
       </tr>
     </table>
	 
<?Php
}//FIn del if (isset($codigo) && !(isset($txt_busqueda)))
echo "</div>";	  
 ?> 
 <?php
if (isset($anulada) && $anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
<br/>
<?php
require_once('../../componentes/FRONT/com_con_leyenda.php');
?>
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
<script type="text/javascript" src="../VALIDACIONES/fac_par_compras.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
<?php if($op==3){ ?>
<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/jquery.ui/jquery-ui-1.11.4/jquery-ui.min.css" />
<script type="text/javascript" >
    var gridComp=$("#comp");  
    gridComp.jqGrid({
            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
            mtype: "GET", datatype: "local", regional : 'es',hidegrid:false,//ajaxRowOptions: { async: true }, 
            //postData: $("#searchForm").getData("consAjax"),
            autowidth : true, shrinkToFit: false, height: 250,responsive:true,
            cmTemplate: {sortable:false,title: true},
            colModel: [
                { label: 'C�d.Int.', name: 'Cop_Cod', key: true, width:60,align:"center", frozen:true },
                { label: 'Secc.', name: 'Cop_Sec',width: 45,align:"center", frozen:true },
                { label: 'Fecha.', name: 'Cop_Fec',  width: 85,align:"center", frozen:true },
                { label: 'Sust.', name: 'Tri_Sri',width: 45,align:"center", frozen:true },
                { label: 'Tip. Doc.', name: 'Tic_Des', width: 125, frozen:true },
                { label: 'No. Documento', name: 'Cop_Num', width: 125, frozen:true }, 
				{ label: 'Aut.', name: 'Cop_Aut', width: 100,cellattr: function (rowId, tv, rawObject, cm, rdata){return 'style="'+excelFormats.text+'"';}}, 
                { label: 'C.I./R.U.C.', name: 'Prs_Ced', width: 100,cellattr: function (rowId, tv, rawObject, cm, rdata){return 'style="'+excelFormats.text+'"';}},
                { label: 'Proveedor', name: 'proveedor', width: 220 },
		{ label: 'Cantidad', name: 'Cant', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'},
                { label: 'Sub 0%', name: 'Sub0', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'},
                { label: 'Sub IVA', name: 'Sub12', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'} ,
                { label: 'Descue.', name: 'Descu', width: 70 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'} ,
                { label: 'ICE', name: 'IceTot', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'} ,
                { label: 'IVA', name: 'IvaTot', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'} ,
                { label: 'IRBPNR', name: 'Cop_Irb', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'} ,                
                { label: 'Total', name: 'total', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'}, 
                { label: 'No. Retenci�n', name: 'Ret_Num', width: 125 },
                { label: 'Aut. Retenci�n', name: 'Aut_Sri', width: 125 },
                { label: 'Ret.(Renta)', name: 'TotRen', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'} ,
                { label: 'Ret.(Iva)', name: 'TotIva', width: 90 ,align:"right", formatter:'currency',formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'}
            ],                                     
            rowNum: 500,rownumbers: true, pager: "listPager", gridview: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",footerrow: true, userDataOnFooter: true
//            loadComplete: function () {                       
//                    gridComp.jqGrid('footerData', 'set', { Prs_Ape: '<div style="text-align:right;">Totalesss:</div>',Cant:gridComp.jqGrid('getCol','Cant',false,'sum'),Sub0:gridComp.jqGrid('getCol','Sub0',false,'sum'),Sub12:gridComp.jqGrid('getCol','Sub12',false,'sum'),IvaTot:gridComp.jqGrid('getCol','IvaTot',false,'sum'),total:gridComp.jqGrid('getCol','total',false,'sum') });                     
//             }            
        }).jqGrid('setFrozenColumns');
        gridComp.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false })
        .navSeparatorAdd("#listPager")
        .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar Excel&nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",
                                onClickButton: function() {
                                    gridComp.jqGrid('exportGridExcel',{nombre:"Compras",hoja:"HOJA 1",footer:true});	
                                },position: "last"
                            });
        gridComp.jqGrid('bindKeys');
        //$.createDateRange('#txt_fec_ini','#txt_fec_fin');
</script>  
<?php } ?>
</BODY>
</HTML>
<?Php	
/**
* Cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>