<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?	
/*
* Descripción: Consulta las ventas individual, total y detallada, de cada vendedor y punto de impresión
* Fecha de actualización: 2012-05-25
* Desarrollador: Lewis Chimarro
* Fecha de actualización: 2013-03-22
* Desarrollador: Lewis Chimarro
* Descripcion: Se agrego 2 columnas, donde se muestra el descuento y el valor neto pagado
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');	 
require_once('../LOGICA/fac_log_deudas.php'); 	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 

/* 
* Declaracion de constante Tipo de comprobante - Factura
*/
if (!isset($Tic_Cod))
{
	$Tic_Cod = 1; 
}

$mes = date("m");
$ann = date("Y");

/* 
* Inializa la variable op 
*/
if (!(isset($op)))
{
	$op = 1;
}//Fin del if (!(isset($op)))

/*
* Consulta del vendedor en base al codigo de la persona
*/
$rs_vendedor = $obBD_con1->consulta(sentencias_tes(24, $obBD_con1->parametros($Ses_Prs_Cod.'*'.$Ses_Suc_Cod)), $obBD_conexion->conexion);
$row_rs_vendedor = $obBD_con1->registros();
$total_rs_vendedor = $obBD_con1->numregistros();	
$Pun_Cod = $row_rs_vendedor['Pun_Cod'];	

/**
* Ajax para cargar el detalle de la venta
*/
if (isset($ajax_detalle))
{
	$com_codigo = $ajax_codigo;
 	include("../COMPONENTES/tesComDetalleVen.php"); 
	exit();
}

/* 
* OPCIONES 
*/
switch ($op){
	case 1: //Individual	
	/* 
	* Cargado de los datos de la cabecera 
	*/
	if (trim($txt_busqueda != ""))
	{	
	    if ($op_opciones == "d")
		{
			/* 
			* Consulta todas las facturas en base al apellido 
			*/
			$rs_buscar = $obBD_con1->consulta(sentencias_tes(320, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pun_Cod.'*'.$cmb_anio.'*'.$cmb_mes)),$obBD_conexion->conexion);

                        //$rs_buscar = $obBD_con1->consulta(sentencias_tes(1231, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pun_Cod.'*'.$cmb_anio.'*'.$cmb_mes)), $obBD_conexion->conexion);									

		}
		elseif ($op_opciones == "r")
		{
			/* 
			* Consulta las facturas en base al codigo de la factura 
			*/
			$rs_buscar = $obBD_con1->consulta(sentencias_tes(321, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pun_Cod)),$obBD_conexion->conexion);

                       //$rs_buscar = $obBD_con1->consulta(sentencias_tes(1232, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pun_Cod)),$obBD_conexion->conexion);
		}  
		else
		{
			/* 
			* Consulta las facturas en base a la papeleta de deposito
			*/
			$rs_buscar = $obBD_con1->consulta(sentencias_tes(323, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pun_Cod)),$obBD_conexion->conexion);
		
                       //$rs_buscar = $obBD_con1->consulta(sentencias_tes(1233, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pun_Cod)),$obBD_conexion->conexion);
		}
		$row_rs_buscar = $obBD_con1->registros();
  	    $total_rs_buscar = $obBD_con1->numregistros();		
	}//Fin del if (trim($txt_busqueda != ""))
	else
	{		
		if (isset($codigo))
		{
			/*
			* Consulta datos de los clientes
			*/
			$rs_cliente = $obBD_con1->consulta(sentencias_tes(37, $obBD_con1->parametros($codigo)),$obBD_conexion->conexion);
			$row_rs_cliente = $obBD_con1->registros();
			$total_rs_cliente = $obBD_con1->numregistros();	
			$cliente = $row_rs_cliente['Vet_Cod'];			
			/*
			* Consulta de las ciudades
			*/
			$rs_ciudad = $obBD_con1->consulta(sentencias_tes(26, $obBD_con1->parametros($Usu_Cod)),$obBD_conexion->conexion);
			$row_rs_ciudad = $obBD_con1->registros();
			$total_rs_ciudad = $obBD_con1->numregistros();

			/* 
			* Consulta del reporte para impresion 
			*/
			$pagina = $_SERVER['PHP_SELF'];
			$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);

			/*****************************************************/
			/*    FUNCION QUE CARGA AUTOMATICAMENTE LOS RUBROS   */
			/*****************************************************/			
			$obBD_con1->generarDeudas($row_rs_cliente['Cli_Cod'], $obBD_conexion);			
		}//Fin del if (isset($codigo))
	}//Fin del if (trim($txt_busqueda != ""))
	break;

	case 2:	
		/* 
		* Llamado a componente ajax para busquedas avanzadas
		*/		
		require_once("../../componentes/FRONT/ajax_suc_mod_eta_car.php"); 
	    
		/* 
		* En la opcion 2 se busca las facturas de todos los puntos de impresion 
		*/
		$puntos = "AND caja_aper.Pun_Cod = ".$Pun_Cod;			
		/* 
		* Entra mientras no se seleccione BUSQUEDA AVANZADA 
		*/
		if (!(isset($escu)))
		{
			/* 
			* Solo FECHAS y AGRUPADAS 
			* Si esta seteado el check rubro entonces agrupa los valores por rubro 
			*/
			if (isset($rubros))
			{
				$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(210, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),
											$obBD_conexion->conexion);
				$row_rs_buscarcarrera = $obBD_con1->registros();
				$total_rs_buscarcarrera = $obBD_con1->numregistros();
			}//Fin del if (isset($rubros))
			else
			{
				/* 
				* solo FECHAS
				* Consulta de los totales de las facturas con detalle 
				*/
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(106, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
			   								$obBD_conexion->conexion);
			   $row_rs_buscarcarrera = $obBD_con1->registros();
	  	       $total_rs_buscarcarrera = $obBD_con1->numregistros();
			}//Fin del else if (isset($rubros))		
		}//Fin del if (!(isset($escu)))
	break;
	
	case 3:			
		if (isset($hdd))
		{	
			/* 
			* En la opcion 4 se busca las facturas de los puntos de impresion seleccionados
			*/
			$puntos = "AND caja_aper.Pun_Cod=".$Pun_Cod;	
		
			/* 
			* Consulta de los totales de las facturas  
			*/
		   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(212, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), $obBD_conexion->conexion);
		   $row_rs_buscarcarrera = $obBD_con1->registros();
	  	   $total_rs_buscarcarrera = $obBD_con1->numregistros();
		 }//Fin del if (isset($hdd))
	break;

	case 4:
		/* 
		* En la opcion 4 se busca las facturas de los puntos de impresion seleccionados
		*/
		$puntos = "AND caja_aper.Pun_Cod = ".$Pun_Cod4;			
		if (!(isset($escu)))
		{
			/* 
			* Solo FECHAS y AGRUPADAS 
			* Si esta seteado el check rubro entonces agrupa los valores por rubro
			*/
			if (isset($rubros))
			{
				$rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(210, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)),
											$obBD_conexion->conexion);
				$row_rs_buscarcarrera = $obBD_con1->registros();
				$total_rs_buscarcarrera = $obBD_con1->numregistros();
			}//Fin del if (isset($rubros))
			else
			{
				/* 
				* solo FECHAS
				* Consulta de los totales de las facturas con detalle 
				*/
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(106, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
			   								$obBD_conexion->conexion);
			   $row_rs_buscarcarrera = $obBD_con1->registros();
	  	       $total_rs_buscarcarrera = $obBD_con1->numregistros();
			}//Fin del else if (isset($rubros))		
		}//Fin del if (!(isset($escu)))
	break;
}//FIn del case $op


?>

<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>	
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    	<script language="javascript" src="../VALIDACIONES/fac_val_fac_ven.js"></script>
   		<script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
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
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>

<div id="set1">
<?Php
if ($total_rs_vendedor >0)
{ ?>
     <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td width="45%" colspan="2">&raquo; Consultar  Ventas</td>
      <td width="39%">&raquo; <strong>PUNTO DE IMPRESION:</strong> <?Php echo $row_rs_vendedor['Pun_Des']; ?></td>
      <td width="16%" align="right">&nbsp;</td>
	 </tr>	
  <tr>  
    <td height="400"  colspan="4" valign="top">
<?php
		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		$pag3= $_SERVER['PHP_SELF']."?op=3";
		$pag4= $_SERVER['PHP_SELF']."?op=4";
		tabs(3,'Individual*Totales*Detalle*Puntos de Impresión', $pag1.'*'.$pag2.'*'.$pag3.'*'.$pag4, $op);
		?>		
<div id="ContTabul">		
<?Php
switch ($op){
	case 1: 
?>	
<form name="form1" id="form1" action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post">
<FIELDSET>
	<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
      <table width="604" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="144" class="Etiqueta1"  ><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
        <td width="460">  
           <?Php
            /**
            * Consulta los tipos de comprobantes
            */
            $row_tipo_compr = $obBD_con1->getArrayConsulta(1036, '', $obBD_conexion);	
        ?>
        <select name="Tic_Cod" id="Tic_Cod" onchange="if(this.value=='1'){document.getElementById('urlComXml').value='../COMPONENTES/tesPdfFacturaElectronica_1.0.php'};if(this.value=='4'){document.getElementById('urlComXml').value='../COMPONENTES/tesPdfNotasCreditoElectronica_1.0.php'} ">
        <option  value="">Seleccione...</option>
        <?Php
        foreach($row_tipo_compr as $row)
        { ?>
          <option  <?Php if ($Tic_Cod == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php echo $row['Tic_Cod']; ?>"><?Php echo $row['Tic_Des']; ?></option>
        <?Php
        }
        ?>
        </select>
        <input type="hidden" id="urlComXml" name="urlComXml" value="../COMPONENTES/tesPdfFacturaElectronica_1.0.php" />
        </td>
      </tr>
    </table>  
</FIELDSET>   
<FIELDSET>
<LEGEND>
    <label class="Titulos2">Buscar cliente por:</label>
</LEGEND>
<table width="696" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="100"><input name="op_opciones" type="radio" value="d" onClick="document.getElementById('cmb_mes').disabled=false; 
                                        document.getElementById('cmb_anio').disabled=false; setfocus(this.form.txt_busqueda)" <?Php if ($op_opciones == "d" or !isset($op_opciones)){  echo "checked"; } ?>>
        <span class="Etiqueta1">Apellidos</span></td>
    <td width="137"><input type="radio" name="op_opciones" value="r" onClick="document.getElementById('cmb_mes').disabled=true; 
                                        document.getElementById('cmb_anio').disabled=true; setfocus(this.form.txt_busqueda)" <?Php if ($op_opciones == "r"){  echo "checked"; } ?>>
        <span class="Etiqueta1">No. Documento </span></td>
    <td width="134"><input type="radio" name="op_opciones" value="p" onclick="document.getElementById('cmb_mes').disabled=true; 
                                        document.getElementById('cmb_anio').disabled=true; setfocus(this.form.txt_busqueda)" <?Php if ($op_opciones == "p"){  echo "checked"; } ?> />
      <span class="Etiqueta1">Dep&oacute;sito </span></td>
    <td width="325" class="LetraNegra"><?Php include("../COMPONENTES/tes_com_ann_mes_ven.php"); ?></td>
    </tr>
</table>
<table width="566" height="36" border="0" cellpadding="0" cellspacing="0">
<tr>
  <td height="28" class="BarraBusqueda"><div align="left"><span class="Asterisco">* </span>Busqueda:
    <input name="txt_busqueda" type="text" id="txt_busqueda" size="50" maxlength="50" style="text-transform:uppercase ">
    
    <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)">
      <i class="icon-search icon-white"></i>
      <span>Buscar</span>
      </button></div>  </td>
  </tr>
</table>
</FIELDSET>
</form>
<?Php
if(isset($txt_busqueda))
{
?>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	    <th width="4%">Cod. Int.</th>
        <th width="4%">No. Fact.</th>
        <th>Cliente</th>
        <th width="10%">Fecha</th>
        <th width="4%">Pdf</th>
        <th width="4%">&nbsp;</th>	  		  
        <th width="4%">&nbsp;</th>
      </tr>
	</thead>
    <tbody>	
	  <?Php 
	 if($total_rs_buscar != 0)
	 {	  
	  $i=0;
	  do { 
	  	$i++;
		  	if($row_rs_buscar['Vet_Est']=='I')
	  		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  ?>	  
	  <tr>
	    <td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Vet_Cod']; ?></FONT></td>
		<td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Vet_Num']; ?></FONT></td>
		<td align="left"><FONT COLOR="<? echo $rojo;?>">&nbsp;<?Php echo marcar_cadena($txt_busqueda,$row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom'],'#FFFF00',1); ?></FONT></td>
		<td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Caj_Fec']; ?></FONT></td>
		<td align="center">
        <? if($row_rs_buscar['Vet_Aut']=='S'){ ?>
        <form name="frm_pdf" id="frm_pdf" action="<? echo $urlComXml;?>" method="post" target="_blank">
            <button type="button" class="btn btn-primary btn-mini" title="Pdf(Factura electr&oacute;nica SRI)" onclick="this.form.submit()">
            <i class=" icon-download-alt icon-white"></i> <span></span> </button>
            <input name="urlXml" id="urlXml" type="hidden" value="<?Php echo '../FRONT/'.$Ses_Emp_Cod."/".$row_rs_buscar['Vet_Xml']."_A.xml";?>">
            <input name="op" id="op" type="hidden" value="I">
            <input name="logoUrl" id="logoUrl" type="hidden" value="<? echo $Ses_Emp_Log;?>">            
        </form>        
        <? }?>
        </td>
		<td align="center">
        <button type="button" name="button" id="button" class="btn btn-info btn-mini" title="Ver detalle" onclick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&ajax_codigo=<?Php echo $row_rs_buscar['Vet_Cod']; ?>','ajax_modal')">
	        <i class="icon-info-sign icon-white"></i>
	        </button>	</td>	
		<td align="center">						
        <form name="form2" id="form1" action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
		<input name="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Cod'];?>">	
        <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
		<input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">
		<input name="volver_anio" id="volver_anio" type="hidden" value="<?Php echo $cmb_anio;?>">				
		<input name="volver_mes" id="volver_mes" type="hidden" value="<?Php echo $cmb_mes;?>">
        <input name="volver_Tic_Cod" id="volver_Tic_Cod" type="hidden" value="<?Php echo $Tic_Cod;?>">	
        <input name="urlComXml" id="urlComXml" type="hidden" value="<?Php echo $urlComXml;?>">	
        		
        </form>
			</td>
	  </tr>	  
	  <tr id="detalle[<?Php echo $i; ?>]">
			<?php
			$com_codigo = $row_rs_buscar['Vet_Cod'];
			?>			  
	  	<td colspan="4"><?Php include("../COMPONENTES/tes_com_detalle_ven.php");  ?></td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>			
	    </tr>	  
	  <?Php } while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar)); 
}//Fin del if($total_rs_buscar != 0)
else
{ ?>
		<tr>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td><?Php
		$msg_mes = explode('=', $cmb_mes);
  		echo error_alerta("No hay resultados que mostrar para ".strtoupper($txt_busqueda)." en ".mes($msg_mes[1],1)." del ".$cmb_anio, 2); ?></td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    </tr>    
<?Php	
}//Fin del else if($total_rs_buscar != 0) ?>
      </tbody>
  </table>
</FIELDSET>
<?Php 
	echo barra_estado($total_rs_buscar);	  	
}//FIn del if(isset($txt_busqueda))

if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
<br/>
<?
require_once('../../componentes/FRONT/com_con_leyenda.php');
?>
<?Php
	break;
	case 2: ?>
	<form name="form3" id="form3" action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Buscar por:</label>
	</LEGEND>	
		<table width="99%" border="0" cellpadding="0" cellspacing="0">
		  <tr>
			<td width="48%" valign="top">
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Rubros</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="21%"><label class="Etiqueta1">
                  <input name="rubros" type="checkbox" id="rubros" value="checkbox">Agrupar</label>
                </td>
                <td width="79%"><span class="Etiqueta1">
                  <input name="escu" type="checkbox" id="escu" disabled="disabled" onClick="ShowHide('carreras')" value="checkbox">Busqueda avanzada</span>
                </td>
              </tr>
            </table>
		    <table width="100%" border="0" cellpadding="0" cellspacing="0" id="carreras">
              <hr>
              <tr>
                <td width="13%" align="left">
				<?Php include("../../componentes/FRONT/com_suc_mod_eta_car.php"); ?></br>
                </td>
                </tr>
            </table>
		</FIELDSET></td>
			<td width="52%" valign="top">
			<?php include("../../componentes/FRONT/com_con_estado.php"); ?>	</td>
		  </tr>
		  <tr>
		    <td colspan="2"><FIELDSET>
	<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
    <table width="565" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120" class="Etiqueta1"  ><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
    <td width="445">  
   
      <select name="Tic_Cod" id="Tic_Cod">
              <option  <?Php if ($Tic_Cod == 1){ echo "selected"; } ?> value="1">FACTURA</option>
              <option  <?Php if ($Tic_Cod == 2){ echo "selected"; } ?> value="2">* NOTA O BOLETA DE VENTA</option>
            </select></td>
  </tr>
</table>  
</FIELDSET>   </td>
		    </tr>
		  <tr>
			<td colspan="2">
        <?php include("../../componentes/FRONT/com_con_fecha.php"); ?></td>
	</tr>
  </table>
  </FIELDSET>
    <br> 
	<button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_con_facturas(this.form)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button> 
    <input name="hdd" type="hidden" id="hdd">
	<input type="hidden" name="op" value="<?Php echo $op; ?>">
	</form>
<?Php
if (isset ($hdd))
{
	/* 
	* Consulta el maximo de numeracion de las facturas emitidas en el rango de fechas 
	*/
	$rs_maximo_fac= $obBD_con1->consulta(sentencias_tes(96, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
													$obBD_conexion->conexion);
	$row_rs_maximo_fac = $obBD_con1->registros();
	/* 
	* Consulta el minimo de numeracion de las facturas emitidas en el rango de fechas 
	*/
	$rs_minimo_fac= $obBD_con1->consulta(sentencias_tes(97, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
													$obBD_conexion->conexion);
	$row_rs_minimo_fac = $obBD_con1->registros();
?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la busqueda</label>
	</LEGEND>
    <div id="Exportar_a_Excel">
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			<tr>
			<td width="10%" class="Etiqueta1">Desde:</td>
			<td width="17%" class="LetraNegra">&nbsp;<?Php echo $txt_fec_ini?></td>
			<td width="7%" class="Etiqueta1">Hasta:</td>
			<td width="66%" class="LetraNegra">&nbsp;<?Php echo $txt_fec_fin?></td>
			</tr>
      	</table>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		  	<tr>
            <td width="11%" class="Etiqueta1">Desde Nro.:</td>
		    <td width="11%" class="LetraNegra">&nbsp;<?Php echo $row_rs_minimo_fac['Num']; ?></td>
			<td width="11%" class="Etiqueta1">Hasta Nro.:</td>
			<td width="67%" class="LetraNegra">&nbsp;<?Php echo $row_rs_maximo_fac['Num'];  ?></td>
		  	</tr>
			<tr>
			<td class="Etiqueta1">Estado de Documentos:</td>
		    <td align="left" class="LetraNegra">&nbsp;
					    <?php  if ($optest == "A"){ echo 'Activas'; } 
								else { echo 'Anulados'; } ?>			</td>					  
			<td class="Etiqueta1">&nbsp;</td>
			<td class="LetraNegra">&nbsp;</td>
			</tr>
		</table>
		<?Php
		if (isset($escu)){ 
	  		/* 
			* Consulta la descripción de la etapa 
			*/
			$rs_etapa = $obBD_con1->consulta(sentencias_tes(176, $obBD_con1->parametros($Eta_Cod)), 
										$obBD_conexion->conexion);	
			$row_rs_etapa = $obBD_con1->registros();	
			/* 
			* Datos de la modaldidad 
			*/
			$rs_modalidad = $obBD_con1->consulta(sentencias_tes(172, $obBD_con1->parametros($Mod_Cod)), 
					$obBD_conexion->conexion);
			$row_rs_modalidad = $obBD_con1->registros();							
		?>	
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
		  <tr>
			<td width="11%" class="Etiqueta1">Modalidad:</td>
			<td width="16%" class="LetraNegra"><?Php echo $row_rs_modalidad['Mod_Des'] ?></td>
			<td width="6%" class="Etiqueta1">Etapa:</td>
			<td width="67%" class="LetraNegra"><?Php echo $row_rs_etapa['Eta_Des'] ?></td>
		  </tr>
		</table>
		<?php
		}//fin del if (isset($escu))

if ($Car_Int != 'T')
{
	unset($carrera_cod);
	$carrera_cod[]=$Car_Int;		
}//Fin del if ($Car_Int != 'T')

if (!(isset($escu)))
{
	unset($carrera_cod);
	$carrera_cod[]=0;
}//Fin del if (!(isset($escu)))

$boton_imp=false;
for ($x=0; $x<=count($carrera_cod)-1; $x++)
{
		/* Evalua si se encuentra seteada la opción de carrera */
		if (isset($escu))
		{ 
			if (isset($rubros))
			{
			   /* Por FECHAS - AGRUPADO - BUSQUEDA AVANZADA
			   Consulta de las facturas totales agrupados por rubros en base a la carrera de todos los puntos de impresion */
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(211, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$carrera_cod[$x].'*'.$puntos)), 
			   								$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();					
			}//Fin del if (isset($rubros))
			else
			{
			   /* 
			   * Por FECHAS - BUSQUEDA AVANZADA
			   * Consulta de las facturas totales en base a la carrera y el periodo actual*/			   
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(110, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$carrera_cod[$x].'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
			   							$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();	
		   	}//Fin del else if (isset($rubros))

			/* 
			* Calcula el total de las facturas por carrera 
			*/
			/* 
			* Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion 
			*/	
			$resultados_total = explode('*',$obBD_con1->calculosVentasCarreras($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $carrera_cod[$x], $Pun_Cod, $obBD_conexion));													
  			?>     			
			<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
				 <tr>	 
				   <td class="LetraNegra" align="center"><strong><?php echo  $row_rs_buscarcarrera['Car_Nom']; ?></strong></td>
				</tr>
		</table>
			<?php
		}//Fin del if (isset($escu))
		else
		{
		   /* 
		   * Calcula el total de las facturas */
		  /* 
		  * Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion 
		  */		
			$resultados_total = explode('*',$obBD_con1->calculosVentas($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $Pun_Cod, $obBD_conexion));		
		}

	if ($total_rs_buscarcarrera != 0)
	{
			if (isset($rubros))
			{
			?>				
				<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
                <thead>
				  <tr>
					<th width="10%">Fecha</th>
					<th>Rubros</th>
					<th width="8%">Total</th>
				  </tr>
                </thead>
                <tbody>
				  <?php
				  do{
				  ?>
				  <tr>
					<td align="center"><?Php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
					<td><?Php echo $row_rs_buscarcarrera['Ite_Lar']; ?></td>
					<td align="right"><?Php echo formato_numero($row_rs_buscarcarrera['Vet_Imp'] + $row_rs_buscarcarrera['Iva'],2,2); ?></td>
				  </tr>
				  <?Php
				  	//$total_total = $total_total + $row_rs_buscarcarrera['Vet_Imp'];					
				  }while($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>
                  </tbody>
			    </table>
			    <table width="100%"  border="0" cellpadding="0" cellspacing="0">
					<tr class="LetraNegra">
					  <td width="73%">      
					  <td width="16%" class="Etiqueta1">Subtotal:
					  <td width="11%" align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 0%:</td>	  
					  <td align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 12%: </td>
					  <td align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">12% IVA:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></td>
					</tr>	
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Descuento:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Total:</td>
					  <td align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></td>
					</tr>
			  	</table>
		  		<br>
		    <?Php
			}//Fin del if (isset($rubros))
			else
			{			
			?>
				<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader02">
                <thead>
				  <tr>
				    <th width="4%">C&oacute;d. Int.</th>
			        <th width="8%">No. Documento</th>
				  	<th width="10%">Fecha</th>
				  	<th>Retenci&oacute;n</th>
				  	<th>C&eacute;dula/R.U.C.</th>
		  		    <th>Cliente</th>
		  		    <th>Observaci&oacute;n</th>
		  			<th width="8%">Valor</th>
		  			<th width="8%">Descuento</th>
		  			<th width="8%">SubTotal</th>
		  			<th width="8%">Iva</th>
		  			<th width="8%">Total</th> 
		  		  </tr>
                  </thead>
                  <tbody>
				    <?Php 
					/* 
					* Consulta el total de todas las facturas 
					*/
					$total_imp = 0;
					$total_des = 0;
					$total_iva = 0;					
					$total_tot = 0;
					do { 
						$i++;
						/*  
						* Retorno los calculos de las facturas 
						*/
						$resultados = explode('*',$obBD_con1->calculos($row_rs_buscarcarrera['Vet_Cod'], $obBD_conexion));				
						$row_curso = $obBD_con1->getRowConsulta(1073, $row_rs_buscarcarrera['Cli_Cod'].'*'.$row_rs_buscarcarrera['Nge_Cod'], $obBD_conexion);				
						?> 
					    	<tr>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Num']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
							  <td><?PHP echo $row_rs_buscarcarrera['Ret_Num']; ?></td>
							  <td><?PHP echo $row_rs_buscarcarrera['Prs_Ced']; ?></td>
							  <td><?PHP echo $row_rs_buscarcarrera['Prs_Ape'].' '.$row_rs_buscarcarrera['Prs_Nom']; ?></td>
							  <td><?php echo $row_curso['Sem_Nom']; ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Tot'],2,3);
							  
							   ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Descuento'],2,3); ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Tot']-$row_rs_buscarcarrera['Descuento'],2,3);
							  
							   ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Iva'],2,3); ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Pag']+$row_rs_buscarcarrera['Iva'],2,3); ?></td>
		  					</tr>
				   	<?Php 
					/**
					* Calculo de totales
					*/
					$total_imp = $total_imp + round($row_rs_buscarcarrera['Vet_Tot'],2);
					$total_des = $total_des + ($row_rs_buscarcarrera['Descuento']);
					$total_iva = $total_iva + round($row_rs_buscarcarrera['Iva'],2);
					$total_tot = $total_tot + round($row_rs_buscarcarrera['Vet_Pag'],2);	
									
	   					} while ($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>   
                        </tbody>
						<tfoot>
					    	<tr>
					    	  <td align="center">&nbsp;</td>
					    	  <td align="center">&nbsp;</td>
					    	  <td align="center">&nbsp;</td>
					    	  <td align="right">&nbsp;</td>
					    	  <td align="right">&nbsp;</td>
					    	  <td align="right"><strong>TOTALES:</strong></td>
					    	  <td align="right">&nbsp;</td>
					    	  <td align="right"><b><?Php echo formato_numero($total_imp,2,3);?></b></td>
					    	  <td align="right"><b><?Php echo formato_numero($total_des,2,3);?></b></td>
					    	  <td align="right"><?Php echo formato_numero($total_imp-$total_des,2,3);?></td>
					    	  <td align="right"><b><?Php echo formato_numero($total_iva,2,3);?></b></td>
					    	  <td align="right"><b><?Php echo formato_numero($total_tot + $total_iva,2,3);?></b></td>
		    	    </tr>								
                   </tfoot>     
		  		</table>
 			<?Php 
			echo barra_estado($total_rs_buscarcarrera); ?>
<br>
				<table width="100%"  border="0" cellpadding="0" cellspacing="0">
					<tr class="LetraNegra">
					  <td width="73%">      
					  <td width="16%" class="Etiqueta1">Subtotal:
					  <td width="11%" align="right"><?php echo formato_numero($resultados_total[0],2,3); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 0%:</td>	  
					  <td align="right"><?Php echo formato_numero($resultados_total[1],2,3); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 12%: </td>
					  <td align="right"><?Php echo formato_numero($resultados_total[2],2,3); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">12% IVA:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[3],2,3); ?></td>
					</tr>	
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Descuento:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[4],2,3); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Total:</td>
					  <td align="right"><?php echo formato_numero($resultados_total[5],2,3); ?></td>
					</tr>
			  	</table>
              </div>  											
		 <?php
		 	}//Fin del else if (isset($rubros)) 

		/* 
		* Control para saber si se muestra o no el boton 
		*/
		$boton_imp = true;
  	}//Fin del if ($total_rs_buscarcarrera != 0)
	else
  	{
		/* 
		* Muestra este mensaje en todos los casos excepto cuando selecciona la carrera 
		*/
		if (!(isset($escu)))
		{
			echo error_alerta(" ¡No hay resultados que mostrar1!", 2);
		}//Fin del if (!(isset($escu)))
	}//Fin del if ($total_rs_buscarcarrera != 0)
} //Fin del for ($x=0; $x<=count($carreras_cod)-1; $x++)

	/* 
	* Control para mostrar el mensaje de error cuando se selecciona todas las carreras 
	*/
	if (isset($escu) && $boton_imp == false)
	{
			echo error_alerta(" ¡No hay resultados que mostrar!", 2);
	}//Fin del if (isset($escu) && $boton_imp == false)	
?>
</FIELDSET>
<br>
<?Php
if ($boton_imp == true)
{ ?>	
   <br>
   <table border="0" cellpadding="0" cellspacing="0">
	  <tr>
	   <td width="106" align="left" class="LetraNegra">
		 <form action="fac_pri_fac_total_2.0.php" method="post" name="form4" target="_blank">
             <button type="button" class="btn btn-primary start" title="Imprimir Documentos" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
			 <input name="hdd" type="hidden" value="<?php echo $hdd; ?>">
			 <input name="Pun_Cod" type="hidden" value="<?php echo $Pun_Cod; ?>">
			 <input name="puntos" type="hidden" value="<?php echo $puntos; ?>">
			 <input name="optest" type="hidden" value="<?php echo $optest; ?>">
			 <input name="op" type="hidden" value="<?php echo $op; ?>">
			 <input name="txt_fec_ini" type="hidden" value="<?php echo $txt_fec_ini; ?>">
			 <input name="txt_fec_fin" type="hidden" value="<?php echo $txt_fec_fin;?>">					 
			 <input name="Tic_Cod" type="hidden" value="<?php echo $Tic_Cod; ?>">						 
			 <input name="rubros" type="hidden" id="rubros" value="<?Php echo $rubros ?>">
			 <input name="escu" type="hidden" id="escu" value="<?Php echo $escu ?>">
			 <input name="Mod_Cod" type="hidden" id="modalidad" value="<?Php echo $Mod_Cod ?>">
			 <input name="Eta_Cod" type="hidden" id="Eta_Cod" value="<?Php echo $Eta_Cod ?>">
             <input name="num_ini" type="hidden" id="num_ini" value="<?Php echo $row_rs_minimo_fac['Num']; ?>">
             <input name="num_fin" type="hidden" id="num_fin" value="<?Php echo $row_rs_maximo_fac['Num'];  ?>">
             <input name="Eta_Des" type="hidden" id="Eta_Des" value="<?Php echo $row_rs_etapa['Eta_Des'] ?>">
             <input name="Mod_Des" type="hidden" id="Mod_Des" value="<?Php echo $row_rs_modalidad['Mod_Des'] ?>">
			<?Php
			if (isset($carrera_cod))
			{
				for ($j=0; $j<=count($carrera_cod)-1; $j++)	
				{
				?>						
					<input name="carrera_cod[<?Php echo $j; ?>]" type="hidden" value="<?php echo $carrera_cod[$j]; ?>">
				<?Php
				}//Fin del for ($j=0; $j<=count($carrera_cod); $j++) 
			}//Fin del if (isset($carrera_cod))	?>
		</form>				 			
		</td>
        <td width="337">
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
<?php
}//Fin del if ($boton_imp == true)
	
/* 
* Entra en esta condicion cuando se selecciona opciones avanzadas para buscar por la carrera 
*/
if (isset($escu))
{
	/* 
	* Consultas la inconsistencia de las facturas que no tienen relación con matriculas 
	*/
   $rs_buscar = $obBD_con1->consulta(sentencias_tes(225, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod)), $obBD_conexion->conexion);
   $row_rs_buscar= $obBD_con1->registros();
   $total_rs_buscar = $obBD_con1->numregistros();	
   
   if ($total_rs_buscar > 0)
   {
   ?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de inconsistencias<img src="../../mascaras/model1/imagenes/32x32/nota.png" border="0" style="cursor:pointer" title="Se considera como inconsistencia a todo Documento de un Cliente que no tiene relaci&oacute;n con una matr&iacute;cula Activa."></label>
	</LEGEND>   
		<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>
		  <tr>
			<th width="4%">&nbsp;</th>
			<th>C&oacute;d. Int.</th>
			  <th>No. Fact.</th>
			  <th>Cliente</th>
			  <th>Fecha</th>	  		  
			  <th>Punto de impresi&oacute;n </th>
			  <th width="22">&nbsp;</th>
		  </tr>
         </thead>
         <tbody>
		  <?Php 
		  $i=0;
		  do { 
			$i++;
		  ?>
		  <tr>
		  <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
			<td align="center"><img src="../../imagenes/edit_add.png" id="mas[<?php echo $i; ?>]" width="25" height="25" title="Ver detalle" style="cursor:pointer" onClick="mas_menos(1,'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)"><img src="../../imagenes/edit_remove.png" id="menos[<?php echo $i; ?>]" width="25" title="Ocultar detalle" style="cursor:pointer" height="25" onClick="mas_menos(2, 'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)"></td>
			<td align="center"><?Php echo $row_rs_buscar['Vet_Cod']; ?></td>
			<td height="25" align="center"><?Php echo $row_rs_buscar['Vet_Num']; ?></td>
			<td align="left"><?Php echo $row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; ?>&nbsp;</td>
			<td align="center"><?Php echo $row_rs_buscar['Caj_Fec']; ?></td>	
			<td align="center"><?Php echo $row_rs_buscar['Pun_Des']; ?></td>
			<td align="center">		
			<input name="imagen" type="image" src="../../mascaras/model1/imagenes/32x32/vista.jpg" title="Ver" width="22" height="22">			</td>
			<input name="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Cod'];?>">	
		  </form>
		  </tr>
		  <tr id="detalle[<?Php echo $i; ?>]">
			<?php
			$com_codigo = $row_rs_buscar['Vet_Cod'];
			?>			  		  
			<td>&nbsp;</td>
			<td colspan="5"><?Php include("../COMPONENTES/tes_com_detalle_ven.php");  ?></td>		
			<td>&nbsp;</td>			
		  </tr>
			<script language="javascript">
			ShowHide('detalle[<?Php echo $i; ?>]');
			ShowHide('menos[<?Php echo $i; ?>]');		 
			</script>				
		  <?Php } while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar)); ?>
          </tbody>
	  </table>
	  </FIELDSET>   	
	<?php 
	echo barra_estado($total_rs_buscar);
   }//Fin del    if ($total_rs_buscar > 0) de las inconsistencias
}//Fin del if (isset($escu))
}//Fin del if (isset ($hdd))
	break; //Fin del case 2		
	case 3:
?>
<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
<FIELDSET>
<LEGEND>
<label class="Titulos2">Buscar por:</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
	 <td colspan="2">
    <FIELDSET>
	<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
    <table width="565" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120" class="Etiqueta1"  ><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
    <td width="445">  
   
      <select name="Tic_Cod" id="Tic_Cod">
              <option  <?Php if ($Tic_Cod == 1){ echo "selected"; } ?> value="1">FACTURA</option>
              <option  <?Php if ($Tic_Cod == 2){ echo "selected"; } ?> value="2">* NOTA O BOLETA DE VENTA</option>
            </select></td>
  </tr>
</table>  
</FIELDSET>   </td>
		    </tr>
  <tr>
    <td width="45%"><?php include("../../componentes/FRONT/com_con_fecha.php"); ?></td>
    <td width="55%"><?php include("../../componentes/FRONT/com_con_estado.php"); ?></td>
  </tr>
</table>
 </FIELDSET> 
    <br> 	
<button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_fec_ini*txt_fec_fin', 0)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button>     
    <input name="hdd" type="hidden" id="hdd">
	<input type="hidden" name="op" value="<?Php echo $op; ?>">
</form>
	<br>
<?Php
if (isset($hdd))
{
	/* 
	* Consulta el maximo de numeracion de las facturas emitidas en el rango de fechas 
	*/
	$rs_maximo_fac= $obBD_con1->consulta(sentencias_tes(96, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
													$obBD_conexion->conexion);
	$row_rs_maximo_fac = $obBD_con1->registros();

	/* 
	* Consulta el minimo de numeracion de las facturas emitidas en el rango de fechas 
	*/
	$rs_minimo_fac= $obBD_con1->consulta(sentencias_tes(97, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
													$obBD_conexion->conexion);
	$row_rs_minimo_fac = $obBD_con1->registros();
?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la busqueda</label>
	</LEGEND>
    <div id="Exportar_a_Excel">
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			<tr>
  			  <td width="11%" class="Etiqueta1">Desde:</td>
  			  <td width="16%" class="LetraNegra">&nbsp;<?Php echo $txt_fec_ini?></td>
  			  <td width="6%" class="Etiqueta1">Hasta:</td>
  			  <td width="67%" class="LetraNegra">&nbsp;<?Php echo $txt_fec_fin?></td>
			  </tr>
      	</table>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		  	<tr>
            <td width="11%" class="Etiqueta1"> Desde Nro.:</td>
		    <td width="11%" class="LetraNegra">&nbsp;<?Php echo $row_rs_minimo_fac['Num']; ?></td>
			<td width="11%" class="Etiqueta1">Hasta Nro.:</td>
			<td width="67%" class="LetraNegra">&nbsp;<?Php echo $row_rs_maximo_fac['Num'];  ?></td>
		  	</tr>
			<tr>
			<td class="Etiqueta1">Estado de documentos:</td>
		    <td align="left" class="LetraNegra">
					    &nbsp;
					    <?php  if ($option == "A"){ echo 'Activas'; } 
								else { echo 'Anulados'; } ?>			</td>					  
			<td class="Etiqueta1">&nbsp;</td>
			<td class="LetraNegra">&nbsp;</td>
			</tr>
		</table>	
<?php
	if ($total_rs_buscarcarrera != 0)
	{		
		?>
			<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
            <thead>
			  <tr>
				<th width="4%">C&oacute;d. Int.</th>
				<th width="8%">No. Documento</th>
				<th width="8%">Fecha</th>
				<th>C&eacute;dula/R.U.C.</th> 
				<th>Cliente</th>
				<th>Detalle</th>
				<th>Total</th> 
			  </tr>
             </thead>
             <tbody>
				<?Php 
				/* 
				* Consulta el total de todas las facturas 
				*/
				$resultados_total = explode('*',$obBD_con1->calculosVentas($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $Pun_Cod, $obBD_conexion));			
				do { 
					$i++; 			
					/*  
					* Retorno los calculos de las facturas 
					*/
					$resultados = explode('*',$obBD_con1->calculos($row_rs_buscarcarrera['Vet_Cod'], $obBD_conexion));
					
					/* Consulta del semestre y la carrera del estudiante */
					/*$rs_semestre =  $obBD_con1->consulta(sentencias_tes(174, $obBD_con1->parametros($row_rs_buscarcarrera['Nge_Cod'])), 
					$obBD_conexion->conexion);
					$row_rs_semestre = $obBD_con1->registros();
					$total_rs_semestre = $obBD_con1->numregistros();	*/		
					
					/*
					* Consulta del detalle de la factura 
					*/
					?> 
						<tr>
						  <td align="center" valign="top"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
						  <td align="center" valign="top"><?php echo $row_rs_buscarcarrera['Vet_Num']; ?></td>
						  <td valign="top" align="center"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
						  <td valign="top"><?PHP echo $row_rs_buscarcarrera['Prs_Ced']; ?></td>
						  <td valign="top"><?PHP echo $row_rs_buscarcarrera['Prs_Ape']." ".$row_rs_buscarcarrera['Prs_Nom']; ?> &nbsp;</td>
						  <td valign="top" align="left">							
						    <?Php 
							/* 
							* Consulta del detalle de la factura 
							*/
							$rs_detalle = $obBD_con1->consulta(sentencias_tes(37, $obBD_con1->parametros($row_rs_buscarcarrera['Vet_Cod'])), $obBD_conexion->conexion);
							$row_rs_detalle =  $obBD_con1->registros();
							$total_rs_detalle =  $obBD_con1->numregistros();
														
							do{
							/* 
							* Consulta los rubro del intereses
							*/
						  $rs_interes = $obBD_con1->consulta(sentencias_tes(74, $obBD_con1->parametros(
						  $row_rs_detalle['Vet_Cod'].'*'.
								$row_rs_detalle['Nge_Cod'].'*'.$row_rs_detalle['Asi_Int'].'*'.
								$row_rs_detalle['Pro_Cod'])), 
						  $obBD_conexion->conexion);
						  $row_rs_interes = $obBD_con1->registros();
						  $total_rs_interes = $obBD_con1->numregistros(); 
						   echo "&#8226; ".$row_rs_detalle['Ite_Cor'].
													"[".formato_numero($row_rs_detalle['Vet_Imp'],2,2)."]<br>"; 
							if ($total_rs_interes > 0)
							{
								do{ //Inicio del }while($row_rs_interes = mysqli_fetch_assoc($rs_interes)); 
									 echo "&#8226; ".$row_rs_interes['Ite_Cor'].
												"[".formato_numero($row_rs_interes['Vet_Imp'],2,2)."]<br>"; 
									}while($row_rs_interes = $obBD_con1->fetch_assoc($rs_interes));
								 }//Fin del if ($total_rs_interes > 0)									
							} while($row_rs_detalle = $obBD_con1->fetch_assoc($rs_detalle));
							?>
				</td>
				 <td align="right" valign="top"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Imp'],2,2); ?></td>
						  <?php
					} while ($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera ));
				?>						
				</tr>
                </tbody>	 
			</table>
			<?php 
			echo barra_estado($total_rs_buscarcarrera);
			?>
			<br>
			<table width="100%"  border="0" cellpadding="0" cellspacing="0">
				<tr class="LetraNegra">
				  <td width="73%">      
				  <td width="15%" class="Etiqueta1">Subtotal:
				  <td width="12%"><div align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></div></td>
				</tr>
				<tr class="LetraNegra">
				  <td>&nbsp;</td>
				  <td class="Etiqueta1">Tarifa 0%:</td>	  
				  <td><div align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></div></td>
				</tr>
				<tr class="LetraNegra">
				  <td>&nbsp;</td>
				  <td class="Etiqueta1">Tarifa 12%: </td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></div></td>
				</tr>
				<tr class="LetraNegra">
				  <td>&nbsp;</td>
				  <td class="Etiqueta1">12% IVA:</td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></div></td>
				</tr>	
				<tr class="LetraNegra">
				  <td>&nbsp;</td>
				  <td class="Etiqueta1">Descuento:</td>
				  <td><div align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></div></td>
				</tr>
				<tr class="LetraNegra">
				  <td>&nbsp;</td>
				  <td class="Etiqueta1">Total:</td>
				  <td><div align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></div></td>
				</tr>
	  </table>
      </div>
</FIELDSET>
<br>	  
	  <table border="0">
		  <tr>
			 <td width="107" align="left" class="LetraNegra">
			 <form action="fac_pri_fac_total_2.0.php" method="post" name="form4" target="_blank"> 
			 <button type="button" class="btn btn-primary start" title="Imprimir Documentos" onclick="confirmacion_print(this.form)"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
			 <input name="hdd" type="hidden" value="<?php echo $hdd; ?>">
			 <input name="Pun_Cod" type="hidden" value="<?php echo $Pun_Cod; ?>">
			 <input name="puntos" type="hidden" value="<?php echo $puntos; ?>">
			 <input name="optest" type="hidden" value="<?php echo $optest; ?>">
			 <input name="op" type="hidden" value="<?php echo $op; ?>">
			 <input name="txt_fec_ini" type="hidden" value="<?php echo $txt_fec_ini; ?>">
			 <input name="txt_fec_fin" type="hidden" value="<?Php echo $txt_fec_fin;  ?>">					 
			 <input name="Tic_Cod" type="hidden" value="<?php echo $Tic_Cod; ?>">	
              <input name="num_ini" type="hidden" id="num_ini" value="<?Php echo $row_rs_minimo_fac['Num']; ?>">
             <input name="num_fin" type="hidden" id="num_fin" value="<?Php echo $row_rs_maximo_fac['Num'];  ?>">				 <input name="Eta_Des" type="hidden" id="Eta_Des" value="<?Php echo $row_rs_etapa['Eta_Des'] ?>">
             <input name="Mod_Des" type="hidden" id="Mod_Des" value="<?Php echo $row_rs_modalidad['Mod_Des'] ?>">
			 </form>				 
			 </td>
<td width="337">
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
	}//Fin del if ($total_rs_buscarcarrera != 0)
	else
	{
		echo error_alerta(" ¡No hay resultados que mostrar!", 2);
	}//Fin del if ($total_rs_buscarcarrera != 0)		
}//Fin del if (isset($hdd))
break; //Fin del case 3	
case 4:	
	
	/*
	* Consulta del punto de impresion
	*/
	$rs_puntos = $obBD_con1->consulta(sentencias_tes(90, $obBD_con1->parametros($Ses_Suc_Cod)), $obBD_conexion->conexion);
	$row_rs_puntos = $obBD_con1->registros();
	$total_rs_puntos = $obBD_con1->numregistros();
	 ?>
<form name="form3" id="form3" action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post">
<FIELDSET>
<LEGEND>
	<label class="Titulos2">Buscar por:</label>
</LEGEND>	
	<table width="99%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td colspan="2" valign="top">		 <FIELDSET>
<LEGEND>
<label class="Titulos2">Puntos de impresi&oacute;n</label>
</LEGEND>
 <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="12%" class="Etiqueta1">Punto de Impresi&oacute;n:</td>
          <td width="88%">
    		<select name="Pun_Cod4" id="Pun_Cod4">
            <?PHP 
   			do{ ?>
            <option <?Php if ($row_rs_puntos['Pun_Cod']==$Pun_Cod4){ echo "selected"; } ?>   value="<?Php echo $row_rs_puntos['Pun_Cod']; ?>"><?PHP echo $row_rs_puntos['Pun_Des']; ?></option>
            <?PHP } while($row_rs_puntos=$obBD_con1->registros()); ?>
          </select></td>          
        </tr>
	</table>
</FIELDSET></td>
	      </tr>
		  <tr>
			<td width="48%" valign="top">
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Rubros</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="24%"><label class="Etiqueta1">
                  <input name="rubros" type="checkbox" id="rubros2" value="checkbox">
                  Agrupar</label></td>
                <td width="76%"><span class="Etiqueta1">
                  <input name="escu" type="checkbox" id="escu" onClick="ShowHide('carreras')" value="checkbox">
                  Busqueda avanzada</span></td>
              </tr>
            </table>
		    <table width="100%" border="0" cellpadding="0" cellspacing="0" id="carreras">
              <hr>
              <tr>
                <td width="13%" align="left">
				<?Php include("../../componentes/FRONT/com_suc_mod_eta_car.php"); ?></br></td>
                </tr>
            </table>
		</FIELDSET></td>
			<td width="52%" valign="top">
			<?php include("../../componentes/FRONT/com_con_estado.php"); ?>		</td>
		  </tr>
		  <tr>
			<td colspan="2">
        <?php include("../../componentes/FRONT/com_con_fecha.php"); ?>  		</td>
	</tr>
  </table>
  </FIELDSET>
    <br> 	
<button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_con_facturas(this.form)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button>    
    <input name="hdd" type="hidden" id="hdd">
	<input type="hidden" name="op" value="<?Php echo $op; ?>">
</form> 

<?Php
if (isset ($hdd))
{
	/* 
	* Consulta el maximo de numeracion de las facturas emitidas en el rango de fechas 
	*/
	$rs_maximo_fac= $obBD_con1->consulta(sentencias_tes(96, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
													$obBD_conexion->conexion);
	$row_rs_maximo_fac = $obBD_con1->registros();

	/* 
	* Consulta el minimo de numeracion de las facturas emitidas en el rango de fechas 
	*/
	$rs_minimo_fac= $obBD_con1->consulta(sentencias_tes(97, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
													$obBD_conexion->conexion);
	$row_rs_minimo_fac = $obBD_con1->registros();

	/*
	* Consulta del punto de impresion
	*/
	$rs_punto = $obBD_con1->consulta(sentencias_tes(631, $obBD_con1->parametros($Pun_Cod4)),$obBD_conexion->conexion);
	$row_rs_punto = $obBD_con1->registros();
?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la busqueda</label>
	</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			<tr>
  			  <td class="Etiqueta1">Punto de impresi&oacute;n: </td>
  			  <td class="LetraNegra"><?Php echo $row_rs_punto['Pun_Des']; ?></td>
  			  <td class="Etiqueta1">&nbsp;</td>
  			  <td class="LetraNegra">&nbsp;</td>
		  </tr>
  			<tr>
			<td width="11%" class="Etiqueta1">Desde:</td>
			<td width="16%" class="LetraNegra"><?Php echo $txt_fec_ini?></td>
			<td width="6%" class="Etiqueta1">Hasta:</td>
			<td width="67%" class="LetraNegra"><?Php echo $txt_fec_fin?></td>
			</tr>
      	</table>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		  	<tr>
            <td width="11%" class="Etiqueta1">Desde Nro.:</td>
		    <td width="12%" class="LetraNegra"><?Php echo $row_rs_minimo_fac['Num']; ?></td>
			<td width="10%" class="Etiqueta1">Hasta Nro.:</td>
			<td width="67%" class="LetraNegra"><?Php echo $row_rs_maximo_fac['Num'];  ?></td>
		  	</tr>
			<tr>
			<td class="Etiqueta1">Estado de documentos:</td>
		    <td align="left" class="LetraNegra">
					    <?php  if ($optest == "A"){ echo 'Activas'; } 
								else { echo 'Anuladas'; } ?>			</td>					  
			<td class="Etiqueta1">&nbsp;</td>
			<td class="LetraNegra">&nbsp;</td>
			</tr>
		</table>
		<?Php
		if (isset($escu)){ 
	  		/* 
			* Consulta la descripción de la etapa 
			*/
			$rs_etapa = $obBD_con1->consulta(sentencias_tes(176, $obBD_con1->parametros($Eta_Cod)), 
										$obBD_conexion->conexion);	
			$row_rs_etapa = $obBD_con1->registros();	
			/* Datos de la modaldidad */
			$rs_modalidad = $obBD_con1->consulta(sentencias_tes(172, $obBD_con1->parametros($Mod_Cod)), 
					$obBD_conexion->conexion);
			$row_rs_modalidad = $obBD_con1->registros();							
		?>	
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
		  <tr>
			<td width="11%" class="Etiqueta1">Modalidad:</td>
			<td width="16%" class="LetraNegra"><?Php echo $row_rs_modalidad['Mod_Des'] ?></td>
			<td width="6%" class="Etiqueta1">Etapa:</td>
			<td width="67%" class="LetraNegra"><?Php echo $row_rs_etapa['Eta_Des'] ?></td>
		  </tr>
		</table>
		<?php
		}//txt_fec_fin del if (isset($escu))

if ($Car_Int != 'T')
{
	unset($carrera_cod);
	$carrera_cod[]=$Car_Int;		
}//Fin del if ($Car_Int != 'T')

if (!(isset($escu)))
{
	unset($carrera_cod);
	$carrera_cod[]=0;
}//Fin del if (!(isset($escu)))

$boton_imp=false;
for ($x=0; $x<=count($carrera_cod)-1; $x++)
{
		/* 
		* Evalua si se encuentra seteada la opción de carrera
		*/
		if (isset($escu))
		{ 
			if (isset($rubros))
			{
			   /* 
			   * Por FECHAS - AGRUPADOS - BUSQUEDA AVANZADA 
			   * Consulta de las facturas totales agrupados por rubros en base a la carrera de todos los puntos de impresion 
			   */
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(211, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod.'*'.$carrera_cod[$x].'*'.$puntos)), 
			   								$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();					
			}//Fin del if (isset($rubros))
			else
			{
			   /* 
			   * Por FECHAS - BUSQUEDA AVANZADA
			   * Consulta de las facturas totales en base a la carrera y el periodo actual
			   */	   
			   $rs_buscarcarrera = $obBD_con1->consulta(sentencias_tes(110, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$carrera_cod[$x].'*'.$optest.'*'.$Tic_Cod.'*'.$puntos)), 
			   							$obBD_conexion->conexion);
			   $row_rs_buscarcarrera= $obBD_con1->registros();
			   $total_rs_buscarcarrera = $obBD_con1->numregistros();	
		   	}//Fin del else if (isset($rubros))

			/* 
			* Calcula el total de las facturas por carrera 
			*/																
			/* 
			* Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion 
			*/	
			$resultados_total = explode('*',$obBD_con1->calculosVentasCarreras($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $carrera_cod[$x], $Pun_Cod4, $obBD_conexion));													
  			?>     			
			<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
				 <tr>	 
				   <td class="LetraNegra" align="center"><strong><?php echo  $row_rs_buscarcarrera['Car_Nom']; ?></strong></td>
				</tr>
		</table>
			<?php
		}//Fin del if (isset($escu))
		else
		{
		   /* 
		   * Calcula el total de las facturas 
		   */
		   /* 
		   * Fecha inicial - final - objeto - conexion - activa/inactiva - carrera - punto de impresion 
		   */		
			$resultados_total = explode('*',$obBD_con1->calculosVentas($txt_fec_ini, $txt_fec_fin, $optest, $Tic_Cod, $Pun_Cod4, $obBD_conexion));		
		}

		if ($total_rs_buscarcarrera != 0)
		{
			if (isset($rubros))
			{
			?>				
				<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
                <thead>
				  <tr>
					<th width="10%">Fecha</th>
					<th>Rubros</th>
					<th width="8%">Total</th>
				  </tr>
                 </thead>
                 <tbody>
				  <?php
				  do{
				  ?>
				  <tr>
					<td align="center"><?Php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
					<td><?Php echo $row_rs_buscarcarrera['Ite_Lar']; ?></td>
					<td align="right"><?Php echo formato_numero($row_rs_buscarcarrera['Vet_Imp'] + $row_rs_buscarcarrera['Iva'],2,2); ?></td>
				  </tr>
				  <?Php
				  }while($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>
                  </tbody>
		  </table>
			    <table width="100%"  border="0" cellpadding="0" cellspacing="0">
					<tr class="LetraNegra">
					  <td width="73%">      
					  <td width="16%" class="Etiqueta1">Subtotal:
					  <td width="11%" align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 0%:</td>	  
					  <td align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 12%: </td>
					  <td align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">12% IVA:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></td>
					</tr>	
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Descuento:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Total:</td>
					  <td align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></td>
					</tr>
                    
			  	</table>
		  		<br>
		    <?Php
			}//Fin del if (isset($rubros))
			else
			{			
			?>
				<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
                <thead>
				  <tr>
				    <th width="4%">C&oacute;d. Int.</th>
			        <th width="6%">No. Documento</th>
				  	<th width="10%">Fecha</th> 
		  		    <th>Cliente</th>
		  			<th width="8%">Total</th> 
		  		  </tr>
                 </thead>
                 <tbody>
				    <?Php 
					/* 
					* Consulta el total de todas las facturas 
					*/					
					do { 
								$i++;
								/*  
								* Retorno los calculos de las facturas 
								*/
								$resultados = explode('*',$obBD_con1->calculos($row_rs_buscarcarrera['Vet_Cod'], $obBD_conexion));								
						?> 
					    	<tr class="Fondo">
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Cod']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Vet_Num']; ?></td>
							  <td align="center"><?php echo $row_rs_buscarcarrera['Caj_Fec']; ?></td>
							  <td><?PHP echo $row_rs_buscarcarrera['Prs_Ape'].' '.$row_rs_buscarcarrera['Prs_Nom']; ?></td>
							  <td align="right"><?php echo formato_numero($row_rs_buscarcarrera['Vet_Tot'],2,2); ?></td>
		  					</tr>								
				   	<?Php 				
	   					} while ($row_rs_buscarcarrera = $obBD_con1->fetch_assoc($rs_buscarcarrera)); ?>   
                        </tbody>
		  		</table>
 			<?Php 
			echo barra_estado($total_rs_buscarcarrera); ?>
				<br>
				<table width="100%"  border="0" cellpadding="0" cellspacing="0">
					<tr class="LetraNegra">
					  <td width="73%">      
					  <td width="16%" class="Etiqueta1">Subtotal:
					  <td width="11%" align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 0%:</td>	  
					  <td align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Tarifa 12%: </td>
					  <td align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">12% IVA:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></td>
					</tr>	
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Descuento:</td>
					  <td align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></td>
					</tr>
					<tr class="LetraNegra">
					  <td>&nbsp;</td>
					  <td class="Etiqueta1">Total:</td>
					  <td align="right"><?php echo formato_numero($resultados_total[5],2,2); ?></td>
					</tr>
			  	</table>											
		 <?php
		 	}//Fin del else if (isset($rubros))			
		 	/* 
			* Control para saber si se muestra o no el boton 
			*/
			$boton_imp = true;
  		}//Fin del if ($total_rs_buscarcarrera != 0)
	else
  	{
		/* 
		* Muestra este mensaje en todos los casos excepto cuando selecciona la carrera 
		*/
		if (!(isset($escu)))
		{
			echo error_alerta(" ¡No hay resultados que mostrar!", 2);
		}//Fin del if (!(isset($escu)))
	}//Fin del if ($total_rs_buscarcarrera != 0)
} //Fin del for ($x=0; $x<=count($carreras_cod)-1; $x++)

	/* 
	* Control para mostrar el mensaje de error cuando se selecciona todas las carreras 
	*/
	if (isset($escu) && $boton_imp == false)
	{
			echo error_alerta(" ¡No hay resultados que mostrar!", 2);
	}//Fin del if (isset($escu) && $boton_imp == false)	
?>
</FIELDSET>
<br>
<?Php
if ($boton_imp == true)
{ ?>	
   <br>
   <table border="0">
	  <tr>
	   <td class="LetraNegra" align="left">
		 <form action="fac_pri_fac_total_2.0.php" method="post" name="form4" target="_blank">
			 <button type="button" class="btn btn-primary start" title="Imprimir Documentos" onclick="confirmacion_print(this.form)"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
			 <input name="hdd" type="hidden" value="<?php echo $hdd; ?>">
			 <input name="Pun_Cod4" type="hidden" value="<?php echo $Pun_Cod4; ?>">
			 <input name="puntos" type="hidden" value="<?php echo $puntos; ?>">
			 <input name="optest" type="hidden" value="<?php echo $optest; ?>">
			 <input name="op" type="hidden" value="<?php echo $op; ?>">
			 <input name="txt_fec_ini" type="hidden" value="<?php echo $txt_fec_ini; ?>">
			 <input name="txt_fec_fin" type="hidden" value="<?php echo $txt_fec_fin;?>">					 
			 <input name="Tic_Cod" type="hidden" value="<?php echo $Tic_Cod; ?>">						 
			 <input name="rubros" type="hidden" id="rubros" value="<?Php echo $rubros ?>">
			 <input name="escu" type="hidden" id="escu" value="<?Php echo $escu ?>">
 			 <input name="Mod_Cod" type="hidden" id="modalidad" value="<?Php echo $Mod_Cod ?>">
			 <input name="Eta_Cod" type="hidden" id="Eta_Cod" value="<?Php echo $Eta_Cod ?>">
             <input name="num_ini" type="hidden" id="num_ini" value="<?Php echo $row_rs_minimo_fac['Num']; ?>">
             <input name="num_fin" type="hidden" id="num_fin" value="<?Php echo $row_rs_maximo_fac['Num'];  ?>">				 <input name="Eta_Des" type="hidden" id="Eta_Des" value="<?Php echo $row_rs_etapa['Eta_Des'] ?>">
             <input name="Mod_Des" type="hidden" id="Mod_Des" value="<?Php echo $row_rs_modalidad['Mod_Des'] ?>">
			<?Php
			if (isset($carrera_cod))
			{
				for ($j=0; $j<=count($carrera_cod)-1; $j++)	
				{
				?>						
					<input name="carrera_cod[<?Php echo $j; ?>]" type="hidden" value="<?php echo $carrera_cod[$j]; ?>">
				<?Php
				}//Fin del for ($j=0; $j<=count($carrera_cod); $j++) 
			}//Fin del if (isset($carrera_cod))	?>
		</form>				 			
		</td>
	</tr>
	</table> 
<?php
}//Fin del if ($boton_imp == true)
/* 
* Entra en esta condicion cuando se selecciona opciones avanzadas para buscar por la carrera 
*/
if (isset($escu))
{
	/* 
	* Consultas la inconsistencia de las facturas que no tienen relación con matriculas 
	*/
   $rs_buscar = $obBD_con1->consulta(sentencias_tes(225, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$optest.'*'.$Tic_Cod)), $obBD_conexion->conexion);
   $row_rs_buscar= $obBD_con1->registros();
   $total_rs_buscar = $obBD_con1->numregistros();	
   
   if ($total_rs_buscar > 0)
   {
   ?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de inconsistencias<img src="../../mascaras/model1/imagenes/32x32/nota.png" border="0" style="cursor:pointer" title="Se considera como inconsistencia a toda Factura de un Cliente que no tiene relaci&oacute;n con una matr&iacute;cula Activa."></label>
	</LEGEND>   
		<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>
		  <tr>
			<th width="4%">&nbsp;</th>
			<th>C&oacute;d. Int.</th>
			  <th>No. Fact.</th>
			  <th>Cliente</th>
			  <th>Fecha</th>	  		  
			  <th>Punto de impresi&oacute;n </th>
			  <th width="22">&nbsp;</th>
		  </tr>
        </thead>
        <tbody>
		  <?Php 
		  $i=0;
		  do { 
			$i++;
		  ?>
		  <tr>
		  <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
			<td align="center"><img src="../../imagenes/edit_add.png" id="mas[<?php echo $i; ?>]" width="25" height="25" title="Ver detalle" style="cursor:pointer" onClick="mas_menos(1,'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)"><img src="../../imagenes/edit_remove.png" id="menos[<?php echo $i; ?>]" width="25" title="Ocultar detalle" style="cursor:pointer" height="25" onClick="mas_menos(2, 'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)"></td>
			<td align="center"><?Php echo $row_rs_buscar['Vet_Cod']; ?></td>
			<td height="25" align="center"><?Php echo $row_rs_buscar['Vet_Num']; ?></td>
			<td align="left"><?Php echo $row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; ?>&nbsp;</td>
			<td align="center"><?Php echo $row_rs_buscar['Caj_Fec']; ?></td>	
			<td align="center"><?Php echo $row_rs_buscar['Pun_Des']; ?></td>
			<td align="center">		
			<input name="imagen" type="image" src="../../mascaras/model1/imagenes/32x32/vista.jpg" title="Ver" width="22" height="22">			</td>
			<input name="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Cod'];?>">	
		  </form>
		  </tr>
		  <tr id="detalle[<?Php echo $i; ?>]">
			<?php
			$com_codigo = $row_rs_buscar['Vet_Cod'];
			?>			  		  
			<td>&nbsp;</td>
			<td colspan="5"><?Php include("../COMPONENTES/tes_com_detalle_ven.php");  ?></td>		
			<td>&nbsp;</td>			
		  </tr>
			<script language="javascript">
			ShowHide('detalle[<?Php echo $i; ?>]');
			ShowHide('menos[<?Php echo $i; ?>]');		 
			</script>				
		  <?Php } while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar)); ?>
          </tbody>
	  </table>
	  </FIELDSET>   	
<?php 
echo barra_estado($total_rs_buscar);
   }//Fin del    if ($total_rs_buscar > 0) de las inconsistencias
}//Fin del if (isset($escu))
}//Fin del if (isset ($hdd))
break; //Fin del case 4

}//Fin del case $op
?>
<!-- Informacion detallada de la factura utilizada por la opcion 1, 2 y 3 -->
<?Php 
if ($codigo > 0 && !(isset($txt_busqueda))) { ?>
<br>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Cliente </label>
</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="14%" class="Etiqueta1">C&eacute;dula:</td>
		<td width="19%" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Prs_Ced'] ?></td>
		<td width="8%" class="Etiqueta1">&nbsp;</td>
		<td colspan="3" class="LetraNegra">&nbsp;</td>
		</tr>
	  <tr>
	    <td class="Etiqueta1">Cliente:</td>
	    <td colspan="3" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']; ?></td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    </tr>
	  <tr>
		<td class="Etiqueta1">Direcci&oacute;n:</td>
		<td colspan="3" class="LetraNegra">&nbsp;<?php echo $row_rs_cliente['Prs_Dir']?></td>
		<td width="6%">&nbsp;</td>
		<td width="27%">&nbsp;</td>
	  </tr>
	</table>
</FIELDSET>
	<?Php
	if ($row_rs_cliente['Est_Ruf'] != "")
	{
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos del Representante</label>
	</LEGEND>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="14%" class="Etiqueta1">Cudela/R.U.C.:</td>
		<td class="LetraNegra">&nbsp;<? echo $row_rs_cliente['Est_Ruf']; ?>		  <div align="right"></div></td>
		</tr>
	  <tr>
	    <td class="Etiqueta1">Representante:</td>
	    <td class="LetraNegra">&nbsp;<? echo $row_rs_cliente['Est_Fac']; ?></td>
	    </tr>
	  <tr>
		<td class="Etiqueta1">Direcci&oacute;n:</td>
		<td class="LetraNegra">&nbsp;<? echo $row_rs_cliente['Est_Dir']; ?></td>
	  </tr>
	 </table>
	 </FIELDSET>
  <?php }//Fin del if ($total_rs_representante > 0) ?>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos de la Factura </label>
</LEGEND>
 <FIELDSET>
<LEGEND>
<label class="Titulos2"> Generales </label>
</LEGEND>
	<table width="100%" height="49" border="0" cellpadding="0" cellspacing="0">
	  <tr>
	    <td class="Etiqueta1"><span class="Asterisco">* </span>Tipo documento:</td>
	    <td colspan="3" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Tic_Des']; ?></td>
	    <td class="Etiqueta1">&nbsp;</td>
	    <td class="LetraNegra">&nbsp;</td>
	    </tr>
	  <tr>
	  <td width="14%" class="Etiqueta1">Fecha:</td>
	  <td width="23%" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Caj_Fec']; ?></td>
	   <td width="9%" class="Etiqueta1">Ciudad:</td>
	   <td width="20%" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Ciu_Des']; ?>		   </td>
	   <td width="7%" class="Etiqueta1">No Fact:</td>
	   <td width="27%" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Vet_Num']; ?></td>
	  </tr>
	  <tr>
	   <td class="Etiqueta1">Observaci&oacute;n:</td>
	  <td colspan="5" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Vet_Obs']; ?></td>
	  </tr>
	</table>
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Formas de Pago </label>
</LEGEND>
      <?Php 	 
	  /*
	  * Consulta las formas de pago de la factura
	  */
	 $rs_pago_fac = $obBD_con1->consulta(sentencias_tes(316, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
	 $row_rs_pago_fac = $obBD_con1->registros();
	 $total_rs_pago_fac = $obBD_con1->numregistros(); 
	 
	 $Pag_Cod = $row_rs_pago_fac['Pag_Cod'];
	 $Bak_Cod = $row_rs_pago_fac['Bak_Cod'];
	 $Ban_Cod = $row_rs_pago_fac['Ban_Cod'];
	 $Vet_Cue = $row_rs_pago_fac['Vet_Cue']; 
	 $Vet_Che = $row_rs_pago_fac['Vet_Che'];
	 $Vet_Tot = $row_rs_pago_fac['Vet_Tot'];
	 $Pag_Des=  $row_rs_pago_fac['Pag_Des'];	 	 
	  /* 
	  * Control para saber si hay mas de un tipo de pago 
	  */
	 if ($total_rs_pago_fac > 1)
	  {
	   	 $row_rs_pago_fac = first_last($rs_pago_fac, $row_rs_pago_fac, 1);
		 $Pag_Cod2 = $row_rs_pago_fac['Pag_Cod'];
		 $Bak_Cod2 = $row_rs_pago_fac['Bak_Cod'];
		 $Ban_Cod2 = $row_rs_pago_fac['Ban_Cod'];
		 $Vet_Cue2 = $row_rs_pago_fac['Vet_Cue']; 
		 $Vet_Che2 = $row_rs_pago_fac['Vet_Che'];
		 $Vet_Tot2 = $row_rs_pago_fac['Vet_Tot'];
		 $Pag_Des2=  $row_rs_pago_fac['Pag_Des'];		
	  }//Fin del if ($total_rs_pago_fac > 1) */	  
	?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
  <tr>
    <td width="14%" class="Etiqueta1">Forma:</td>
    <td width="40%" class="LetraNegra"><?php echo $row_rs_pago_fac['For_Des'];  ?></td>
	<td width="20%" class="Etiqueta1">&nbsp;</td>
	<td width="26%" class="LetraNegra">&nbsp;</td>
  </tr>
</table>
  </FIELDSET>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td width="50%" align="left" valign="top">
		<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Tipo 1</label>
		</LEGEND>	  
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="14%" align="right" class="Etiqueta1">Tipo:</td>
          <td width="18%" align="left" class="LetraNegra"> 
               &nbsp;<?Php echo $Pag_Des; ?>          </td>
          <td width="14%" class="Etiqueta1">Banco:</td>
          <td width="19%" class="LetraNegra">&nbsp;
		  <?Php
  		  /* 
		  * Bancos correspondientes al plan de cuentas 
		  */
		  $rs_bancos = $obBD_con1->consulta(sentencias_tes(187, $obBD_con1->parametros($Ban_Cod)), $obBD_conexion->conexion);
		  $row_rs_bancos = $obBD_con1->registros(); 
		  $total_rs_bancos= $obBD_con1->numregistros();

		 	if($total_rs_bancos > 0){
				 echo $row_rs_bancos['Pld_Des'];
    	     }//Fin del if($total_rs_bancos > 0)
			 else
	         {
				/*
				* cargar el banco de otros bancos 
				*/ 
				$rs_banco = $obBD_con1->consulta(sentencias_tes(188, $obBD_con1->parametros($Bak_Cod)), $obBD_conexion->conexion);
				$row_rs_banco = $obBD_con1->registros(); 
					 echo $row_rs_banco['Bak_Des'];
	         }//Fin del else if($total_rs_bancos > 0)
	      ?>		  </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1" align="">Cuenta No:</td>
          <td class="LetraNegra">&nbsp;<?php echo $Vet_Cue; ?></td>
          <td class="Etiqueta1">Cheque/Papeleta No: </td>
          <td class="LetraNegra">&nbsp;&nbsp;<?php echo $Vet_Che; ?></td>
          <td width="8%" class="Etiqueta1">Valor:</td>
          <td width="27%" class="LetraNegra">
           &nbsp;<?php echo formato_numero($Vet_Tot,2,2); ?>          </td>
        </tr>
		</table>
	    </FIELDSET>		
        </td>
      </tr>
	  <?Php  
	  if ($total_rs_pago_fac > 1)
	  { ?>
    <tr>
      <td align="left" valign="top" id="cheque">
		<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Tipo 2</label>
		</LEGEND>	  	  
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="14%" height="25" class="Etiqueta1">Tipo:</td>
          <td width="18%" class="LetraNegra">&nbsp;<?PHP echo $Pag_Des2; ?></td>
          <td width="14%" class="Etiqueta1">Banco:</td>
          <td width="19%" class="LetraNegra">
		  &nbsp;
		  <?Php
  		  /* 
		  * Bancos correspondientes al plan de cuentas 
		  */
		  $rs_bancos = $obBD_con1->consulta(sentencias_tes(187, $obBD_con1->parametros($Ban_Cod2)), $obBD_conexion->conexion);
		  $row_rs_bancos = $obBD_con1->registros(); 
		  $total_rs_bancos= $obBD_con1->numregistros();

		 	if($total_rs_bancos > 0){
				 echo $row_rs_bancos['Pld_Des'];
    	     }//Fin del if($total_rs_bancos > 0)
			 else
	         {
				/*
				* Cargar el banco de otros bancos 
				*/ 
				$rs_banco = $obBD_con1->consulta(sentencias_tes(188, $obBD_con1->parametros($Bak_Cod2)), $obBD_conexion->conexion);
				$row_rs_banco = $obBD_con1->registros(); 
					 echo $row_rs_banco['Bak_Des'];
	         }//Fin del else if($total_rs_bancos > 0)
	      ?>		   </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
		  
        <tr>
          <td class="Etiqueta1">Cuenta No:</td>
          <td class="LetraNegra">&nbsp;<?php echo $Vet_Cue2; ?></td>
          <td class="Etiqueta1">Cheque/Papeleta No: </td>
          <td class="Titulos2">&nbsp;&nbsp;<?php echo $Vet_Che2; ?></td>
          <td width="8%" class="Etiqueta1">Valor:</td>
          <td width="27%" class="LetraNegra">
            &nbsp;&nbsp;<?php echo formato_numero($Vet_Tot2,2,2); ?></td>
        </tr>
		</table>
		</FIELDSET>		
        </td>
    </tr>
	<?Php 
	}//Fin del if ($total_rs_pago_fac > 1)
	?>
  </table>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la Factura</label>
</LEGEND>
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
  <thead>
	<tr>
		<th width="8%">Cant.</th>
		<th width="34%">Descripción</th>
		<th width="15%">P. Unitario</th>
		<th width="15%">Importe</th>
		<th width="8%">Desc.</th>
		<th width="8%">IVA</th>
	</tr>
   </thead>
   <tbody>
	<?Php 
		/* 
		* % de Descuento total 
		*/
		$Vet_Des = $row_rs_cliente['Vet_Des'];
		/* 
		* Estado de la factura 
		*/
		$Vet_Est = $row_rs_cliente['Vet_Est'];
	do{
	?>
	<tr>
	  <td align="center"><?Php echo $row_rs_cliente['Vet_Can']?></td>
	  <td><?Php echo $row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'];?></td>
	  <td align="right"><?Php echo formato_numero($row_rs_cliente['Vet_Pru'],2,2); ?></td>   
	  <td align="right"><?Php echo formato_numero($row_rs_cliente['Vet_Imp'],2,2); ?></td>
	  <td align="right"><?Php echo $row_rs_cliente['Vet_Dec']?></td>
	  <td align="right"><?Php echo $row_rs_cliente['Iva_Por']?></td>
	  </tr>
	<?Php 	  
	  /* 
	  * Consulta los rubro del intereses
	  */
	  $rs_interes = $obBD_con1->consulta(sentencias_tes(74, $obBD_con1->parametros(
			$codigo.'*'.$row_rs_cliente['Nge_Cod'].'*'.
			$row_rs_cliente['Asi_Int'].'*'.$row_rs_cliente['Pro_Cod'])), 
				$obBD_conexion->conexion);
	  $row_rs_interes = $obBD_con1->registros();
	  $total_rs_interes = $obBD_con1->numregistros();

	if ($total_rs_interes > 0)
	{
		do{ //Inicio del }while($row_rs_interes = mysqli_fetch_assoc($rs_interes)); ?> 
	<tr>
	  <td>&nbsp;</td>
	  <td><?Php echo $row_rs_interes['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'];?></td>
	  <td align="right"><?Php echo formato_numero($row_rs_interes['Vet_Pru'],2,2); ?></td>   
	  <td align="right"><?Php echo formato_numero($row_rs_interes['Vet_Imp'],2,2); ?></td>
	  <td align="right"><?Php echo $row_rs_interes['Vet_Dec']?></td>
	  <td align="right"><?Php echo $row_rs_interes['Iva_Por']?></td>
	  </tr>
<?Php 
		}while($row_rs_interes = $obBD_con1->fetch_assoc($rs_interes));
	 }//Fin del if ($total_rs_interes > 0)										
	  
	}while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));

	/*  
	* Retorno los calculos de las facturas 
	*/
	$resultados = explode('*',$obBD_con1->calculos($codigo, $obBD_conexion));
	?>
	<tr>
	  <td class="LetraNegra">&nbsp;</td>
	  <td class="Etiqueta1" align="right">&nbsp;</td>
	  <td class="Etiqueta1" align="right">SUBTOTAL:</td>
	  <td class="Etiqueta1" align="right"><span class="LetraNegra"><?Php echo formato_numero($resultados[0],2,2); ?></span></td>
	  <td align="right" class="LetraNegra">&nbsp;</td>
	  <td align="right" class="LetraNegra">&nbsp;</td>
	  </tr>
	<tr>
	  <td class="LetraNegra">&nbsp;</td>
	  <td class="Etiqueta1" align="right">&nbsp;</td>
	  <td class="Etiqueta1" align="right">TARIFA 0%: </td>
	  <td class="Etiqueta1" align="right"><span class="LetraNegra"><?Php echo formato_numero($resultados[1],2,2); ?></span></td>
	  <td align="right" class="LetraNegra">&nbsp;</td>
	  <td align="right" class="LetraNegra">&nbsp;</td>
	  </tr>
	<tr>
	  <td class="LetraNegra">&nbsp;</td>
	  <td class="Etiqueta1" align="right">&nbsp;</td>
	  <td class="Etiqueta1" align="right">TARIFA 12%:</td>
	  <td class="Etiqueta1" align="right"><span class="LetraNegra"><?Php echo formato_numero($resultados[2],2,2); ?></span></td>
	  <td align="right" class="LetraNegra">&nbsp;</td>
	  <td align="right" class="LetraNegra">&nbsp;</td>
	  </tr>
	<tr>
	  <td class="LetraNegra">&nbsp;</td>
		<td class="Etiqueta1" align="right">&nbsp;</td>
		<td class="Etiqueta1" align="right">12% I.V.A.:</td>
		<td class="Etiqueta1" align="right"><span class="LetraNegra"><?Php echo formato_numero($resultados[3],2,2); ?></span></td>
		<td  align="right" class="LetraNegra">&nbsp;</td>
		<td  align="right" class="LetraNegra">&nbsp;</td>
		</tr>
	<tr>
	  <td class="LetraNegra">&nbsp;</td>
		<td class="Etiqueta1" align="right">&nbsp;</td>
		<td class="Etiqueta1" align="right">% DESCUENTO:&nbsp;<span class="LetraNegra"><?Php echo $Vet_Des; ?></span></td>
		<td class="Etiqueta1" align="right"><span class="LetraNegra"><?Php echo formato_numero($resultados[4],2,2); ?></span></td>
		<td width="8%" class="LetraNegra" align="right">&nbsp;</td>
		<td class="LetraNegra" align="right">&nbsp;</td>
		</tr>
	<tr>
	  <td class="LetraNegra">&nbsp;</td>
		<td class="Etiqueta1" align="right">&nbsp;</td>
		<td class="Etiqueta1" align="right">TOTAL:</td>
		<td class="Etiqueta1" align="right"><span class="LetraNegra"><?php echo formato_numero($resultados[5],2,2); ?></span></td>
		<td align="right" class="LetraNegra">&nbsp;</td>
		<td align="right" class="LetraNegra">&nbsp;</td>
		</tr>
        </tbody>
	</table>
	</fieldset>
	<br>
	<table width="303" border="0" cellpadding="0" cellspacing="0">
	  <tr>
	    <td width="106">       
        <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name= "form2">
        <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onclick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver*cmb_anio*cmb_mes*Tic_Cod*op*urlComXml"; ?>', '<?Php echo $volver_busqueda.'*'.$volver_op.'*1*'.$volver_anio.'*'.$volver_mes.'*'.$volver_Tic_Cod.'*'.$op.'*'.$urlComXml; ?>')"> <i class=" icon-arrow-left icon-white"></i> <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span> </button>
        </form>       
        </td>
	    <td width="197">
         <?Php if ($Vet_Est == 'A'){ ?> 
        <form action="<?Php echo $reportes[1]; ?>" method="post" name= "form2" target="_blank">
        <button type="button" class="btn btn-primary start" title="Imprimir Documento" onclick="confirmacion_print(this.form)"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
	      <input name="hdd_save" type="hidden" id="hdd_save" value="insertar" />
          <input name="Vet_Cod" type="hidden" value="<?Php echo $codigo; ?>">
          </form>
           <?Php 
		} else { echo error_alerta (" Documento ANULADO", 2); } ?>
          </td>
	    </tr>
	  </table>
</FIELDSET>
<?Php 
}//Fin del if ($codigo > 0 && !(isset($txt_busqueda)))

	if (!(isset($carreras)) && ($op==4 || $op == 2))
	{
	?>
	<script language="javascript">
	 ShowHide('carreras');  		 
	 </script>
	<?Php
	}
	?>	
    </div></td>
  </tr>	
</table>
</DIV>
	
<?Php
	/* 
	* Control para ocultar el detalle de las filas 
	*/
	if($total_rs_buscar != 0)
	{
		ocultarDetalle($total_rs_buscar);
	}
}//FIn del if ($total_rs_vendedor >0)
else
{
	echo error_alerta (" Ud. no es un Vendedor autorizado para consultar Facturas o Notas de Ventas", 2);

}
?>
<div id="bgtransparent" class="bgtransparent" style="display:none" onclick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"  style="display:none">		
	<div id="ajax_modal"></div>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_fac_ven.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php 
@$obBD_con1->free_result($rs_buscar);
@$obBD_con1->free_result($rs_cliente);
@$obBD_con1->free_result($rs_ciudad);
@$obBD_con1->free_result($rs_buscarcarrera);
@$obBD_con1->free_result($rs_carrera);
@$obBD_con1->free_result($rs_detalle);
@$obBD_con1->free_result($rs_semestre);
@$obBD_con1->free_result($rs_vendedor);
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>