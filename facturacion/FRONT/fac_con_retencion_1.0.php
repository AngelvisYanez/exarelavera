<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripción: Permite consultar las retenciones
* Fecha de actualización:	2012-10-02
* Desarrollador: Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');    
require_once('../../Librerias/procedimientos/almacenados_standar.php');
//require_once('../../administrador/LOGICA/logica.php');	
  
/**
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);

/**
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	 	 	 

/**
* Tipo de documento/comprobante 
*/
define('tipo_compr', 6); 

/**
* Defino el mes actual 
*/
$mes = date("m"); 


if(isset($ajax_detalle))
{
	$rs_detalle = $obBD_con1->getArrayConsulta(182,$Ret_Cod,$obBD_conexion);								
	$total_rs_detalle=count($rs_detalle);
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="fixedHeader01">
          <thead> 
          <tr>
            <th width="10%" align="center">C&oacute;d. Imp. </th>
            <th width="37%" align="center">Descripci&oacute;n</th>
            <th width="15%" align="center">Base</th>
            <th width="14%" align="center">% Retenci&oacute;n </th>
            <th width="14%" align="center">Valor Retenido </th>
            <th width="10%" align="center">% Iva </th>
          </tr>
          </thead>
      	  <tbody>
          <?php foreach($rs_detalle as $row_rs_detalle){?>
          <tr>
            <td align="center"><?php echo $row_rs_detalle['Ren_Sri']; ?></td>
            <td><?php echo $row_rs_detalle['Ren_Con']; ?></td>
            <td align="right"><?php echo formato_numero($row_rs_detalle['Ret_Bas'],2,4); ?></td>
            <td align="center"><?php echo $row_rs_detalle['Ren_Por']; ?></td>
            <td align="right"><?php echo formato_numero($row_rs_detalle['Val_Ret'],2,4); ?></td>
            <td align="right"><?php echo $row_rs_detalle['Iva_Por']." ".$row_rs_detalle['Adq_Cod']; ?></td>
          </tr>
          <?php }?>
          </tbody>
        </table>
</FIELDSET>
<?php echo barra_estado($total_rs_detalle);  ?>
<?php	

exit();
}

/**
* Control para iniciar por primera vez la variable 
*/
if (!(isset($op_cbm)) )
{ $op_cbm=1; }

/**
* Cargado de los porcentajes d retención 
*/
if(!isset($op))
{ $op=1; }


/** 
*  cargar ajax 
*/ 
if(isset($ajax_suc_anio)){ 
   /**
   * obtengo el año actual
   */	
   $ani_act=date('Y');
   if($esta_chk=='true')
   {    
		/**
		*  Cargar porcentajes y codigos de renta_iva 
		*/
		if($Cbm_Ani!=$ani_act)
		{
			/**
			* consulto los códigos de las retenciones de años anteriores 
			*/
			$rs_renta_iva=$obBD_con1->getArrayConsulta(330,$Cbm_Ani,$obBD_conexion);
		}else
		{
			/**
			* consulto los códigos de las retenciones del año actual 
			*/
			$rs_renta_iva=$obBD_con1->getArrayConsulta(513,'',$obBD_conexion);	  	 		
		}?>
		<span class="Asterisco">* </span><span class="Etiqueta1">Porcentajes:</span>&nbsp;
        <select  name="Ren_Por"  id="Ren_Por"  >
		<option value="T" ><< TODOS >></option>
		<?php foreach($rs_renta_iva as $row_renta_iva) { ?>
			<option value="<?php echo $row_renta_iva['Ren_Cod'];?>"><?php echo $row_renta_iva['Ren_Por'].'%'; ?></option>
		<?php }?>
		</select> 
<?php  }else
	{ 
		/**
		* Consulta de codigos de formularios SRI 
		*/  
		$rs_renta_iva_form=$obBD_con1->getArrayConsulta(476,$Cbm_Ani,$obBD_conexion);
?>
		<span class="Asterisco">* </span><span class="Etiqueta1">Codigos:</span>&nbsp;
		<select name="Ren_Cod" id="Ren_Cod"  >
		<option value="R"><< RESUMEN >></option>
		  <?php foreach($rs_renta_iva_form as $row_renta_iva_form){  ?>
            <option value="<?php echo $row_renta_iva_form['Ren_Cod'];?>"><?php echo $row_renta_iva_form['Ren_Sri'].' - '.$row_renta_iva_form['Ren_Por'].'%';  ?></option>
          <?php }?>
		</select> 	
<?php  }

exit();
}




switch ($op){
	case 1: 
	
	/** 
	* Cargado de los datos de la Cabecera1 
	*/
	if (isset($txt_busqueda)&& $txt_busqueda != "" && (!isset($Cop_Cod))){  
			/**
			* defino rangos de fechas 
			*/
			if($cmb_mes2=='T')
			{	
				/**
				* rango de fechas año a año 
				*/
				$rango_fecha_busin1=$cmb_anio2.'-'.'01'.'-'.'01';
				$rango_fecha_busin2=$cmb_anio2.'-'.'12'.'-'.'31';
			}
			else
			{	
				/**
				* rango de fechas mes a mes 
				*/
				$rango_fecha_busin1=$cmb_anio2.'-'.$cmb_mes2.'-'.'01';
				$rango_fecha_busin2=$cmb_anio2.'-'.$cmb_mes2.'-'.'31';
			}
					
			if ($op_opciones == "d")
			{	
				/**
				* Consulta por apellido  las retenciones
				*/			
				$rs_buscar = $obBD_con1->getArrayConsulta(332, trim($txt_busqueda).'*'.tipo_compr.'*'.$rango_fecha_busin1.'*'.$rango_fecha_busin2.'*'.$Ses_Emp_Cod, $obBD_conexion);
				unset($Ret_Cod); 
				
			}
			else 
			{	/**
				* Consulta por número de comprobante de retención 
				*/
				$rs_buscar = $obBD_con1->getArrayConsulta(333, trim($txt_busqueda).'*'.tipo_compr.'*'.$rango_fecha_busin1.'*'.$rango_fecha_busin2.'*'.$Ses_Emp_Cod, $obBD_conexion);
				unset($Ret_Cod); 
				
			}  
			$total_rs_buscar = count($rs_buscar);
	}//Fin del if ($txt_busqueda != "" && (!isset($Cop_Cod)))
	else		
	{
		if(isset($Ret_Cod))
		{
			/**
			* Consulta los datos de la retención a modificar 
			*/ 
			$rs_inf_retencion=$obBD_con1->getArrayConsulta(501, $Ret_Cod, $obBD_conexion);
		
		}
	}//Fin del else if ($txt_busqueda != "" && (!isset($Cop_Cod)))	
	break;
	
	case 2:
		if(isset($Ret_Cod))
		{
			/**
			* Consulta los datos de la retención a modificar 
			*/ 
			$rs_inf_retencion=$obBD_con1->getArrayConsulta(501, $Ret_Cod, $obBD_conexion);			
		}
	break;
}//FIn del case $op


?>
<HTML>
	<HEAD>
		<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
		<TITLE><?php echo "Retención Consultar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8"> 
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>		
		<script type="text/javascript" src="../VALIDACIONES/fac_val_compras.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/fac_val_retencion.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>         
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>         
        <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>		
         <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
       <script>
		$(function() { 
			//var imagen = "../../mascaras/model1/imagenes/32x32/calendar.gif";
			/* Campo 1 */
			$( "#ini" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});			
			
			/* Campo 2 */
			$( "#fin" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			

		}); 		
        </script> 
        <style>
        .letra10 {
				font-family: Arial, Helvetica, sans-serif;
				font-size:11px;
		}
        </style>    
		
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0"  cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td height="10" colspan="3">&raquo;&nbsp;&nbsp;Consultar Retenci&oacute;n</td>
  </tr>
	<tr>
      <td height="400" valign="top">
		<form name="form1" method="post" action="<?php $_SERVER['PHP_SELF']?>">
		<?php	/**
				* menu opciones 
				*/
		 		$pag1= $_SERVER['PHP_SELF']."?op=1";
			  	$pag2= $_SERVER['PHP_SELF']."?op=2";
				$pag3= $_SERVER['PHP_SELF']."?op=3";
	 		  	tabs(3,'Individual*Totales*Grupales', $pag1.'*'.$pag2.'*'.$pag3, $op); ?>				
   	<div id="ContTabul">	
		<?php
	switch ($op){
		default : 
?>
<br>
<FIELDSET>
<legend><label class="Etiqueta1">Buscar por:</label></legend>
<table width="696" border="0">
    <tr>
    <td width="16%"><input name="op_opciones" type="radio" value="r" checked onClick="setfocus(this.form.txt_busqueda)">
        <span class="Etiqueta1">Apellidos </span></td>
      <td width="28%" ><input name="op_opciones" type="radio" value="d" onClick="setfocus(this.form.txt_busqueda)">
        <span class="Etiqueta1">No. Comprob. de retenci&oacute;n </span></td>
      <td width="14%" class="Etiqueta1" >A&ntilde;o:</td>
      <td width="11%" >
	  <?php 
	  /**
	  * consulta de años 
	  */
	  //$rs_anios = $obBD_con1->getArrayConsulta(329,'', $obBD_conexion);
	  $rs_anios = $obBD_con1->getArrayConsulta(554,$Ses_Emp_Cod, $obBD_conexion);
	   ?>
	  <select name="cmb_anio2" id="cmb_anio2">
        <?php 
		foreach($rs_anios as $row_rs_anios)
		{
		?><option value="<?php echo $row_rs_anios['Anio']; ?>" ><?php echo $row_rs_anios['Anio']; ?></option>
            <?php
		}
		?>
       </select>
       </td>
      <td width="6%" class="Etiqueta1" >Mes:</td>
      <td width="25%" >
      <select name="cmb_mes2" id="cmb_mes2">
        <option value="T"><< TODOS >></option>
        <?php
				  for ($i=1;$i<=12;$i++)
				  { 
				  	echo $i;
				  ?>
        <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?php echo $i; ?>"><?php echo mes($i, 1) ?></option>
        <?php
				  } ?>
      </select>
      </td>
    </tr>
  </table>
  <table width="639" height="36" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="108" height="28" class="BarraBusqueda"><div align="right"><span class="Asterisco">* </span>B&uacute;squeda:</div></td>
      <td width="531" class="BarraBusqueda"><div align="center"> <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase ">
        <input type="hidden" name="opcion_bchk" value="1">&nbsp;&nbsp;<button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda',0);">
          <i class="icon-search icon-white"></i>
          <span>Buscar</span>
          </button> 
      </div></td>
     
    </tr>
  </table>
  
</FIELDSET>
<?php	
	break;
	case 2: 
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Buscar por fecha:</label>
</LEGEND>
<?php 
	  /**
	  *  consulta de años 
	  */
	  //$rs_anios = $obBD_con1->getArrayConsulta(329,'',$obBD_conexion); 		  
	  $rs_anios = $obBD_con1->getArrayConsulta(554,$Ses_Emp_Cod, $obBD_conexion);
?>
<table width="674" border="0">
<tr><td width="6%" class="Etiqueta1" ><span class="Asterisco">*</span> A&ntilde;o:</td>
    <td width="12%"  > 
		<select name="cmb_anio" id="cmb_anio" onChange="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_anio=1&Cbm_Ani=' + this.value+'&esta_chk='+ document.getElementById('bcheck').checked,'div_porcentajes'); setearfecha();" >
        <?php 
		foreach($rs_anios as $row_rs_anios){
		?><option <?php if ($cmb_anio == $row_rs_anios['Anio']){ echo "selected"; } ?> value="<?php echo $row_rs_anios['Anio']; ?>" ><?php echo $row_rs_anios['Anio']; ?></option>
            <?php
		}
		?>
        </select>   
    </td>
    <td width="82%"  class="Etiqueta1">	
	<table width="" border="0" align="left" cellpadding="0" cellspacing="0" id="capa_fecha">
  <tr class="Etiqueta1">
    <td> <span class="Asterisco">*</span> Mes: 
	<?php
	/**
	* Control para mantener seteada la información seleccionada 
	*/
	if (isset($cmb_mes))
	{
		$mes = $cmb_mes;
	}//FIn del if (isset($cmb_mes))
	?>
      <select name="cmb_mes" id="cmb_mes">
        <option value="T"><< TODOS >></option>
        <?php
          for ($i=1;$i<=12;$i++)
          {            
          ?>
        <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?php echo $i; ?>"><?php echo mes($i, 1) ?></option>
        <?php }?>
      </select>			  
     </td>
  </tr>
</table>

<!-- -->	
<table width="" border="0" align="left" cellpadding="0" cellspacing="0" id="capa_rango_fec">
  <tr >
    <td width="68" class="Etiqueta1"> 
<span class="Asterisco">*</span>
	Desde:
    &nbsp;&nbsp;</span>    </td>
    <td class="Etiqueta1"><input name="ini" type="text" id="ini" onkeyup="mascara(this,'-',patron,true)"; size="10" value="<?php echo date("Y-m-d"); ?>"  /></td>
    <td width="57" class="Etiqueta1">&nbsp;<span class="Asterisco">*</span> Hasta:</td>
    <td class="Etiqueta1"><input name="fin" type="text" id="fin" onkeyup="mascara(this,'-',patron,true)"; size="10" value="<?php echo date("Y-m-d"); ?>"  /></td>
  </tr>
</table>

	<!-- onClick="ocultar_bloque(this); setearfecha();"  -->
	<input type="checkbox" name="Chk_Fec" value="1" onClick="ocultas_rango_fecha(this); setearfecha();" style="cursor:pointer" >
	Elegir fecha </td>
    </tr>
  </table>
  
   <script type="text/javascript" type="text/javascript">
      document.getElementById('capa_rango_fec').className = "oculta";
      document.getElementById('capa_fecha').className = "muestra";
   </script>
   </FIELDSET>
   </div>
  <table width="99%"  border="0">
  <tr>
    <td  valign="top">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Porcentaje o c&oacute;digo del formulario:</label>
    </LEGEND>
	<table width="74%" border="0" cellpadding="0" cellspacing="0">
    <tr>
    <td width="41%" class="Etiqueta1" >	
	<div align="left"> 
	<?php 
		/* asigno a la variable opcion_bchk el valor bcheck */
		$opcion_bchk=$bcheck;
		/* asigno a la variable $bcheck el valor de 1*/
		$bcheck = 1;
	?>
	<input name="bcheck" type="radio" id="bcheck"  style="cursor:pointer"  value="1" 
	onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_anio=1&Cbm_Ani=' + document.getElementById('cmb_anio').value +'&esta_chk='+ document.getElementById('bcheck').checked ,'div_porcentajes');" <?php if ($bcheck == 1){ ?> checked="CHECKED" <?php } ?>  >
	Porcentajes </div></td>
     <td width="59%" class="Etiqueta1"  ><div align="left"> 
	 <input name="bcheck" id="bcheck" type="radio" value="0" <?php //if ($bcheck == 2){ ?>  <?php //} ?> style="cursor:pointer" 
	 onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_anio=1&Cbm_Ani=' + document.getElementById('cmb_anio').value +'&esta_chk='+ document.getElementById('bcheck').checked ,'div_porcentajes');"  >
     C&oacute;digos  </div></td>
  </tr>
</table><div align="left" id="div_porcentajes">
<?php


	if($op_cbm=='1')
	{ 
 		/**
		* Cargar porcentajes y codigos de renta_iva 
		*/
		$rs_renta_iva=$obBD_con1->getArrayConsulta(513,'',$obBD_conexion);
	?>
	<span class="Asterisco">*</span><span class="Etiqueta1">Porcentajes:</span>&nbsp;
	<select  name="Ren_Por"    >
	<option value="T" ><< TODOS >></option>
		<?php foreach($rs_renta_iva as $row_renta_iva) { ?>
	   <option <?php if($Ren_Por==$row_renta_iva['Ren_Por']){ ?>  selected="selected" <?php  }  ?>   value="<?php echo   $row_renta_iva['Ren_Por'];  ?>"><?php echo $row_renta_iva['Ren_Por'].'%'; ?></option>
	   <?php }?>
	</select>
   <?php
	}
	else{ 
	    /**
		* consulta de codigos de formularios SRI 
		*/  
		$rs_renta_iva_form=$obBD_con1->getArrayConsulta(476,'',$obBD_conexion);
		$row_renta_iva_form=$obBD_con1->registros();
	?><span class="Asterisco">* </span><span class="Etiqueta1">C&oacute;digo:</span>&nbsp;
	<select name="Ren_Cod" >
	<option value="R"><< RESUMEN.. >></option>
	<?php foreach($rs_renta_iva_form as $row_renta_iva_form){  ?>
	<option value="<?php echo $row_renta_iva_form['Ren_Cod'];?>"><?php echo $row_renta_iva_form['Ren_Sri'].' - '.$row_renta_iva_form['Ren_Por'].'%';  ?></option>
	<?php }?>
	</select>
    <?php }?>
	 </div>
</LEGEND>
</FIELDSET></td><td width="52%" valign="top" height="80"><?php include("../../componentes/FRONT/com_con_estado.php"); ?></td>
  </tr>
</table>
  <br>
   <table border="0" cellpadding="0" cellspacing="0">
  <tr><script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "ini",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		</script>
	  <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "fin",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendariof",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		</script><td ><div align="center">		  
	<button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="this.form.submit()">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button>
          </div>          </td>
        </tr>
  </table>
  <input name="hdd" type="hidden" id="hdd" value="1">
 </form> 
	<?php
	break;








case 3:
?>
	<form name="form1" id="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">	
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Buscar por fecha:</label>
        </LEGEND>
        <?php 
              /**
              *  consulta de años 
              */
              //$rs_anios = $obBD_con1->getArrayConsulta(329,'',$obBD_conexion); 		  
              $rs_anios = $obBD_con1->getArrayConsulta(554,$Ses_Emp_Cod, $obBD_conexion);
        ?>
        <table width="674" border="0">
        <tr><td width="6%" class="Etiqueta1" ><span class="Asterisco">*</span> A&ntilde;o:</td>
            <td width="12%"  > 
                <select name="cmb_anio" id="cmb_anio" onChange="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_anio=1&Cbm_Ani=' + this.value+'&esta_chk='+ document.getElementById('bcheck').checked,'div_porcentajes'); setearfecha();" >
                <?php 
                foreach($rs_anios as $row_rs_anios){
                ?><option <?php if (isset($cmb_anio)&&$cmb_anio == $row_rs_anios['Anio']){ echo "selected"; } ?> value="<?php echo $row_rs_anios['Anio']; ?>" ><?php echo $row_rs_anios['Anio']; ?></option>
                    <?php
                }
                ?>
                </select>   
            </td>
            <td width="82%" align="left"  class="Etiqueta1">	
            <table width="" border="0" align="left" cellpadding="0" cellspacing="0" id="capa_fecha">
          <tr class="Etiqueta1">
            <td> <span class="Asterisco">*</span> Mes: 
            <?php
            /**
            * Control para mantener seteada la información seleccionada 
            */
            if (isset($cmb_mes))
            {
                $mes = $cmb_mes;
            }//FIn del if (isset($cmb_mes))
            ?>
              <select name="cmb_mes" id="cmb_mes">
                <option value="T"><< TODOS >></option>
                <?php
                  for ($i=1;$i<=12;$i++)
                  {            
                  ?>
                <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?php echo $i; ?>"><?php echo mes($i, 1) ?></option>
                <?php }?>
              </select>			  
             </td>
          </tr>
        </table>
        
        <!-- -->	
        <table width="" border="0" align="left" cellpadding="0" cellspacing="0" id="capa_rango_fec">
          <tr >
            <td width="68" class="Etiqueta1"> 
        <span class="Asterisco">*</span>
            Desde:
            &nbsp;&nbsp;</span>    </td>
            <td class="Etiqueta1"><input name="ini" type="text" id="ini" onkeyup="mascara(this,'-',patron,true)"; size="10" value="<?php echo date("Y-m-d"); ?>"  /></td>
            <td width="57" class="Etiqueta1">&nbsp;<span class="Asterisco">*</span> Hasta:</td>
            <td class="Etiqueta1"><input name="fin" type="text" id="fin" onkeyup="mascara(this,'-',patron,true)"; size="10" value="<?php echo date("Y-m-d"); ?>"  /></td>
          </tr>
        </table>
        
            <!-- onClick="ocultar_bloque(this); setearfecha();"  -->
            <input type="checkbox" name="Chk_Fec" value="1" onClick="ocultas_rango_fecha(this); setearfecha();" style="cursor:pointer" >
            Elegir fecha </td>
            </tr>
          </table>
          
           <script type="text/javascript" type="text/javascript">
              document.getElementById('capa_rango_fec').className = "oculta";
              document.getElementById('capa_fecha').className = "muestra";
           </script>
        </FIELDSET>
        <table width="99%"  border="0">
  	<tr>
    <td  valign="top">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">C&oacute;digos utilizados del formulario :</label>
    </LEGEND><div align="left" id="div_porcentajes">
<?php
 
	$rs_renta_iva=$obBD_con1->getArrayConsulta(555,$Ses_Emp_Cod,$obBD_conexion);
	$rs_codigos=$obBD_con1->getRowConsulta(556,'332',$obBD_conexion);		
	?><span class="Asterisco">* </span><span class="Etiqueta1">C&oacute;digo:</span>&nbsp;
	<select name="Ren_Cod" id="Ren_Cod">
	<option value="T"><< TODOS >></option>      
    <?php if($rs_codigos['Ren_Cod']!=""){?>
    <option <?php if(isset($Ren_Cod)&&$Ren_Cod==$rs_codigos['Ren_Cod']){echo 'selected';}?> value="<?php echo $rs_codigos['Ren_Cod']?>"><?php echo $rs_codigos['Ren_Sri'].' &nbsp;&nbsp;- '.$rs_codigos['Ren_Ret'].' '.$rs_codigos['Ren_Por'].'%';?></option>
    <?php }?>
	<?php foreach($rs_renta_iva as $datos){  ?>
	<option <?php if(isset($Ren_Cod)&&$Ren_Cod==$datos['Ren_Cod']){echo 'selected';}?> value="<?php echo $datos['Ren_Cod'];?>"><?php echo $datos['Ren_Sri'].' &nbsp;&nbsp;- '.$datos['Ren_Ret'].' '.$datos['Ren_Por'].'%';  ?></option>
	<?php }?>
	</select>
  
	 </div>	
</FIELDSET></td>
	<td width="52%" valign="top"><?php include("../../componentes/FRONT/com_con_estado.php"); ?></td>
  </tr>
</table>
<table width="18%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="53%"><button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="this.form.submit()">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button></td>
    <td width="47%"><input type="hidden" name="hdd_aux" id="hdd_aux" value="1"  /> </td>
  </tr>
</table>
</form>

<?php if(isset($hdd_aux)){
	if(!isset($Chk_Fec)||$Chk_Fec!=1)
	{		
		if($cmb_mes!='T')
		{
			$ini=$cmb_anio.'-'.str_pad($cmb_mes, 2, "0", STR_PAD_LEFT).'-'.'01'; 
			$fin=$cmb_anio.'-'.str_pad($cmb_mes, 2, "0", STR_PAD_LEFT).'-'.'31';
		}else{
			$ini=$cmb_anio.'-01-01'; 
			$fin=$cmb_anio.'-12-31';			
		}
	}
	
	if($Ren_Cod=='T')
	{
		$rs_DatosCodigo=$obBD_con1->getArrayConsulta(560,$Ses_Emp_Cod,$obBD_conexion);			
	}else{
		$rs_DatosCodigo=$obBD_con1->getArrayConsulta(558,$Ren_Cod,$obBD_conexion);
	}
	$totRegistro=0;
	$totBase=0;
	$totRet=0;
?>
<br />
<FIELDSET>  
<LEGEND>
<label class="Titulos2">Resultado de la busqueda</label>
</LEGEND>
<?php //Verificar si existe el comprobante de la retencion 									
	$existe_comprobante = $obBD_con1->getArrayConsulta(569, $Ses_Emp_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
	$existe_asiento = !empty($existe_comprobante);
?>
<table id="Tbl_Codigos" width="100%" border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed;">
  <tr class="Cabecera1">
    <td width="9%" height="24"><strong>&nbsp;# Retenci&oacute;n</strong></td>
    <td width="8%"><strong>Fecha</strong></td>
    <td width="31%"><strong>Proveedor</strong></td>
    <td width="27%"><strong>Comprob. de Compra</strong></td>
    <td width="8%"><strong>Emisi&oacute;n</strong></td>
    <td width="7%"><strong>Base</strong></td>
    <td width="7%"><strong>Monto</strong></td>
  </tr>      
  <?php foreach($rs_DatosCodigo as $codigo){  	     
	 if($codigo['Ren_Sri']!='332'){
        $rs_DatosCompra=$obBD_con1->getArrayConsulta(557,$Ses_Emp_Cod.'*'.$codigo['Ren_Cod'].'*'.$ini.'*'.$fin.'*'.$optest,$obBD_conexion);
     }else{
		$rs_DatosCompra=$obBD_con1->getArrayConsulta(559,$Ses_Emp_Cod.'*'.$ini.'*'.$fin.'*'.$optest,$obBD_conexion);			
	 }//fin if($codigo['Ren_Sri']=='332')
	 $rs_DatosCompra_total=count($rs_DatosCompra);	
	 ?>
  <tr>
    <td colspan="7" bgcolor="#FFFF99">&nbsp; <strong><?php echo $codigo['Ren_Sri']." &nbsp;&nbsp;&nbsp;".$codigo['Ren_Con'];?></strong></td>
  </tr>
   <?php 
    if($optest=='I')
	{ $rojo='#FF0000';}else{$rojo='';}
	
    if($rs_DatosCompra_total!=0){
	   $totRegistro=$totRegistro+$rs_DatosCompra_total;
       $totBase=0;	   
	   $totRet=0;
	   
	   foreach($rs_DatosCompra as $compra){
   		$nomDocComp=isset($compra['Tic_Des'])?substr($compra['Tic_Des'],0,19):'';
		$totBase=$totBase+$compra['Ret_Bas'];	
		$totRet=$totRet+$compra['Ren_Ret'];
   ?>   
      <tr class="letra10">
        <td align="center" style="mso-number-format:'@'"><FONT COLOR="<?php echo $rojo;?>"><?php if($compra['Ret_Num']!='-'){echo $compra['Suc_Sri'] . '-' .$compra['Pun_Rete'] . '-' . str_pad($compra['Ret_Num'], 9, "0", STR_PAD_LEFT);}else{echo "-";}?></FONT></td>
        <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $compra['Ret_Fec'];?></FONT></td>
        <td style="white-space:nowrap;overflow:hidden;"><FONT COLOR="<?php echo $rojo;?>">&nbsp;<?php echo $compra['Prs_Ape'].' '.$compra['Prs_Nom'];?></FONT></td>
        <td style="white-space:nowrap;overflow:hidden;"><FONT COLOR="<?php echo $rojo;?>">&nbsp;<?php echo $nomDocComp.' &nbsp;&nbsp;'.$compra['Cop_Num'];?></FONT></td>
        <td align="center"><FONT COLOR="<?php echo $rojo;?>">&nbsp;<?php echo $compra['Cop_Fec'];?></FONT></td>
        <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php echo $compra['Ret_Bas'];?></FONT></td>
        <td align="right"><FONT COLOR="<?php echo $rojo;?>"><?php echo $compra['Ren_Ret'];?></FONT></td>
      </tr>
           
  <?php }?>  
      <tr class="letra10">
        <td colspan="5" align="right" style="mso-number-format:'@'">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="68%">&nbsp;(Se encontraron <strong><?php echo $rs_DatosCompra_total; ?></strong> registros en la base de datos)</td>
            <td width="32%" align="right"><font color="<?php echo $rojo;?>"><strong>Totales</strong></font></td>
          </tr>
        </table>
        </td>
        <td align="right"><FONT COLOR="<?php echo $rojo;?>"><strong><?php echo formato_numero($totBase,2,2);?></strong></FONT></td>
        <td align="right"><FONT COLOR="<?php echo $rojo;?>"><strong><?php echo formato_numero($totRet,2,2);?></strong></FONT></td>
      </tr> 
  <?php	}else{
  ?>    
  	<tr class="letra10">
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center" title="<?php echo isset($nomPro)? $nomPro:'';?>"><?php 
			$msg_mes = explode('-', $cmb_mes);
	  		echo " No hay resultados que mostrar para el codigo ".strtoupper($codigo['Ren_Sri']);
		 ?></td>
        <td>&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="right">&nbsp;</td>
        <td align="right">&nbsp;</td>
      </tr>
  <?php }
  } ?>
</table>

</FIELDSET>
<table width="30%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="33.3%"><button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-primary start" title="Buscar" onClick="$('#Tbl_Codigos').clone().prepend($('#exportarReporte').find('table:first-child').clone().html()).exportarExcelPhp('Retenciones-'+$.getDate()+'.xls',urlPhpExcel,'Datos 1');">
            <i class=" icon-share icon-white"></i>&nbsp;&nbsp;<span>Excel</span>
    </button></td>
    <td width="33.3%">
    <form name="frmReporte" id="frmReporte" method="post" action="fac_pri_ret_grupales_2.0.php" target="_blank">
        <input name="Ret_Cod" type="hidden" value="<?php echo $Ret_Cod; ?>">                   
        <button name="bnt_print" type="submit" class="btn btn-primary start" id="bnt_print" value="Imprimir"><i class=" icon-print icon-white"></i><span>&nbsp;&nbsp;Imprimir&nbsp;&nbsp;</span></button>
        <input type="hidden" id="cmb_mes" name="cmb_mes" value="<?php echo $cmb_mes;?>" />
        <input type="hidden" id="cmb_anio" name="cmb_anio" value="<?php echo $cmb_anio;?>" />  
        <input type="hidden" id="Chk_Fec" name="Chk_Fec" value="<?php echo $Chk_Fec;?>" />          
        <input type="hidden" id="Ren_Cod" name="Ren_Cod" value="<?php echo $Ren_Cod;?>" />          
        <input type="hidden" id="optest" name="optest" value="<?php echo $optest;?>" />                       
    </form>
    </td>

	<td width="33.3%">
		<form action="../../contabilidad/FRONT/con_alt_compr_2.0.php" onsubmit="return confirmarEnvio();">
			<input type="hidden" id="cmb_mes" name="cmb_mes" value="<?php echo $cmb_mes; ?>" />
			<input type="hidden" id="cmb_anio" name="cmb_anio" value="<?php echo $cmb_anio; ?>" />
			<input id="ini" name="ini" value="<?php echo $ini; ?>" type="hidden">
			<input id="fin" name="fin" value="<?php echo $fin; ?>" type="hidden">
			<input type="hidden" id="Ren_Cod" name="Ren_Cod" value="<?php echo $Ren_Cod; ?>" />
			<input type="hidden" id="optest" name="optest" value="<?php echo $optest; ?>" />
			<button class="btn btn-primary start" type="submit">Generar Asiento </button>
		</form>
	</td>
    <script>
		const existeComprobante = <?php echo json_encode($existe_asiento); ?>;
			if (existeComprobante) {
				function confirmarEnvio() {
					return confirm("⚠️ Atención:\n\nEste asiento contable parece haber sido registrado previamente.\nVerifique el comprobante antes de continuar para evitar duplicaciones o inconsistencias contables. Si no existe, proceda con el registro del asiento.");
				}
			}
	</script>
  </tr>
</table>
<?php }?>
<?php 

break;
















}

if(isset($hdd))
{		/**
		* consulta por meses 
		*/ 
		if(!isset($Chk_Fec)) /* inicio  */
		{  
			   /**
			   * formar el año 
			   */
			   if($cmb_mes!='T'){ /* inicio if($cmb_mes!='T'){  */				 
				  $anio_rango_1=$cmb_anio.'-'.str_pad($cmb_mes, 2, "0", STR_PAD_LEFT).'-'.'01';
				  $anio_rango_2=$cmb_anio.'-'.str_pad($cmb_mes, 2, "0", STR_PAD_LEFT).'-'.'31';				  
				}
				else				
				{					    
					$anio_rango_1=$cmb_anio.'-'.'01'.'-'.'01';
					$anio_rango_2=$cmb_anio.'-'.'12'.'-'.'31';
				}/* fin if($cmb_mes!='T'){  */
		}else
		{ 
			$anio_rango_1=$ini; $anio_rango_2=$fin; 	
		}
		if($opcion_bchk=='1')/* inicio if($bcheck=='1')  */
		{ 	
				if($Ren_Por=='T')
				{ /* inicio if($Ren_Por!='T'){  */
					 /**
					 *  Consulto por fecha de comprobante de retención 
					 */		 
					 $rs_buscar = $obBD_con1->getArrayConsulta(545,$anio_rango_1.'*'.$anio_rango_2.'*'.tipo_compr.'*'.$optest.'*'.$Ses_Emp_Cod,$obBD_conexion);
				 	
				 }else{
					  
					 /**
					 *  Consulto por fecha de comprobante de retención 
					 */		 
					 $rs_buscar = $obBD_con1->getArrayConsulta(546,$anio_rango_1.'*'.$anio_rango_2.'*'.tipo_compr.'*'.$optest.'*'.$Ren_Por.'*'.$Ses_Emp_Cod, $obBD_conexion);  	 					
					
				 }/* fin if($Ren_Por!='T'){  */
			
		}else
		{  
			if($Ren_Cod=='R')
			{	
			  /**
			  *  Consulto por fecha de comprobante de retención 
			  */	
			  $rs_buscar=$obBD_con1->getArrayConsulta(549,$anio_rango_1.'*'.$anio_rango_2.'*'.tipo_compr.'*'.$optest.'*'.$Ses_Emp_Cod,$obBD_conexion);	
						
			}else
			{	
			  /**
			  *  Consulto por fecha de comprobante de retención 
			  */	
			  $rs_buscar = $obBD_con1->getArrayConsulta(547,$anio_rango_1.'*'.$anio_rango_2.'*'.tipo_compr.'*'.$optest.'*'.$Ren_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);				
			 
			}	
		}/* fin if($bcheck=='1')  */
 		$total_rs_buscar = count($rs_buscar);		
		$txt_busqueda=$total_rs_buscar;
}
	
if(isset($txt_busqueda))
{ 
		if($total_rs_buscar != 0)
		{ 
			if($op==2) 
		 	{ 
?>  
  <br>
			  <?php 
              /* muestro las fechas por rangos */
              if($Chk_Fec==1){ 
              ?>
              <table width="100%" border="0">
                <tr>
                  <td width="185" class="Etiqueta1"><div align="right">Desde:</div></td>
                  <td width="222" class="LetraNegra"><?php echo $anio_rango_1?></td>
                  <td width="330" class="Etiqueta1">Hasta:</td>
                  <td width="619" class="LetraNegra">&nbsp;<?php echo $anio_rango_2?></td>
                </tr>
              </table>
              <?php
              }else
              {
                ?>
                  <table width="100%" border="0">
                <tr>
                  <td width="185" class="Etiqueta1"><div align="right">Mes:</div></td>
                  <td width="222" class="LetraNegra"><?php echo mes($cmb_mes, 1); ?></td>
                  <td width="330" class="Etiqueta1">&nbsp;</td>
                  <td width="619" class="LetraNegra">&nbsp;</td>
                </tr>
              </table>
              <?php } ?>
              <table width="100%" border="0">
              <tr>
                 <td width="184" class="Etiqueta1"><div align="right">Comprobantes de retenci&oacute;n: </div></td>
                 <td width="223" align="left" class="LetraNegra">
				 <?php if ($optest == "A")
					   { 
						 echo 'Activas'; 
					   } else {
						 echo 'Anuladas';
					   } 
				 ?>      
                 </td>
                 <td width="319" class="Etiqueta1"><?php if($Ren_Por!="") { ?>Porcentaje de retención: <?php }  if(!empty($Ren_Cod)) { ?>C&oacute;digo de formulario: <?php }  ?></td>
                 <td width="630" class="LetraNegra">&nbsp;&nbsp;
                   <?php if(($Ren_Por!="")) { if ($Ren_Por == "T"){ echo "Todos los %"; }
                        else{ echo $Ren_Por.'%'; }}  if(!empty($Ren_Cod)) { echo $rs_buscar[0]['Ren_Sri']; }  ?>  </td>
                </tr>
              </table>  
              <?php
              }


if($opcion_bchk==1 || $Ren_Cod!='R'  ) 
{ ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busquedas</label>
</LEGEND>
	<table id="Tbl_Codigos" width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
	  <thead> 
      <tr>
	      <th width="5%" align="center" style="border:0.1pt solid #778899;">Nro. Int. </th>
	      <th width="7%" align="center" style="border:0.1pt solid #778899;">No. Ret. </th>
		  <th width="10%" align="center" style="border:0.1pt solid #778899;">No. Fac. </th>	
          <th width="7%" align="center" style="border:0.1pt solid #778899;">Fecha</th>
          <th width="30%" align="center" style="border:0.1pt solid #778899;">Proveedor</th>
          <th width="7%" align="center" style="border:0.1pt solid #778899;">Base imp </th>
		  <th width="7%" align="center" style="border:0.1pt solid #778899;">Valor</th>
		  <th width="3%">&nbsp;</th>
		  <th width="3%">&nbsp;</th>
      </tr>
      <tbody>
	  <?php
	   $itemr=1;
       $total_base=0;
	   $Suma_Val_Ret=0; 
	   $i=0;
   	   foreach($rs_buscar as $row_rs_buscar) { 
	   if($row_rs_buscar['Ret_Est']=='I')
	    { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	   $i++;
	   ?>
	   <?php /* Base de retención de las facturas */ 
       if(isset($hdd)){
	    /* Control para la opcion 2 */
        if($opcion_bchk==1)
		{
			if(!($Ren_Por=="T"))
			{
				/*  */
                $rs_base_impuesto=$obBD_con1->getArrayConsulta(515,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Por,$obBD_conexion);
				$rs_base_rentas= $obBD_con1->getArrayConsulta(515,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Por,$obBD_conexion);
		   }//Fin del if(!empty($Ren_Por)){
		   else 
		   { 			
			   $rs_base_impuesto=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);
			   $rs_base_rentas=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);        
			}//Fin del Else del if(!empty($Ren_Por)){
		}//Fin del if(!isset($bcheck)){
		else
		{
		     	$rs_base_impuesto=$obBD_con1->getArrayConsulta(544,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Cod,$obBD_conexion);
				$rs_base_rentas=$obBD_con1->getArrayConsulta(544,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Cod,$obBD_conexion);
		}//Fin del else if(!isset($bcheck)){
		}//Fin del if(!isset($Chk_For))    
		
		if($op==1)
		{ 
			 $rs_base_impuesto=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);
			 $rs_base_rentas=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);
					 
		}
		
		if (strlen($row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'])>35)
		{
			$proveedor= substr($row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'],0,35).'...';	
			$auxProvee=$row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'];
		}else{
			$proveedor= $row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'];
			$auxProvee=$row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom'];	
		}
		?>
	  <tr>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Num_Int_Com= $row_rs_buscar['Ret_Cod']; echo $Num_Int_Com;  ?></FONT></td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php  $Ret_Com=$row_rs_buscar['Ret_Num']; echo str_pad($Ret_Com, 6, "0", STR_PAD_LEFT); ?></FONT></td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Cop_Num=$row_rs_buscar['Cop_Num']; echo $Cop_Num; ?></FONT></td>	
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php $Ret_Fec_Sav=$row_rs_buscar['Ret_Fec']; echo $Ret_Fec_Sav; ?></FONT></td>
	    <td align="left" title="<?php echo $auxProvee;?>"><FONT COLOR="<?php echo $rojo;?>"><?php echo marcar_cadena($txt_busqueda, $proveedor, '#FFFF00', 1);if(empty($row_rs_buscar['Prs_Nom'])){ echo "&nbsp"; }?></FONT></td>	    	    	    
        <td align="right"><FONT COLOR="<?php echo $rojo;?>">		
	    <?php 			
		 foreach($rs_base_rentas as $row_rs_base_rentas){     
			 $base_impo=$base_impo+$row_rs_base_rentas['Ret_Bas'];
		 }
		 echo formato_numero($base_impo,2,4);
		 unset($base_impo);
		 ?>		
        </FONT>
        </td>
	    <td align="right"><FONT COLOR="<?php echo $rojo;?>">
	      <?php
		
		if(!($Ren_Por=="T"))
		{  
           	$total=0;
			foreach($rs_base_impuesto as $row_rs_base_impuesto){
		    	$total_base = $total_base + $row_rs_base_impuesto['Ret_Bas'];
				$total=$total+round((($row_rs_base_impuesto['Ret_Bas']*$row_rs_base_impuesto['Ren_Por'])/100),2);
		 	}
		}//Fin del if(!empty($Ren_Por))
		else
		{ 
			$total=0;
		  	foreach($rs_base_impuesto as $row_rs_base_impuesto){				
				 $total_base = $total_base + $row_rs_base_impuesto['Ret_Bas'];
		    	 $total=$total+round((($row_rs_base_impuesto['Ret_Bas']*$row_rs_base_impuesto['Ren_Por'])/100),2);
		 	}
		}//Fin del else if(!empty($Ren_Por))
		$Imp_Com= formato_numero($total, 2,4);
		echo $Imp_Com;
		$Suma_Val_Ret=$Suma_Val_Ret+$total;
		unset($Imp_Com);
		   ?></FONT></td>
	    <td align="center"><button type="button" class="btn btn-success btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&amp;Ret_Cod=<?php echo $row_rs_buscar['Ret_Cod'];?>','mostrar')"><i class="icon-info-sign icon-white"></i></button>        
	      </td>
	    <td align="center">
	    <form name="form3" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >  
	      <input name="Ret_Cod" type="hidden" value="<?php echo $row_rs_buscar['Ret_Cod'];?>"> 
          <input name="Ren_Cod" type="hidden" value="<?php echo $Ren_Cod;?>"> 
          <input name="Ren_Por" type="hidden" value="<?php echo $Ren_Por;?>"> 
          <input name="op" id="op" type="hidden" value="<?php echo $op;?>"> 
          <input name="optest" id="optest" type="hidden" value="<?php echo $optest;?>"> 
          <input name="ini" id="ini" type="hidden" value="<?php echo $ini;?>"> 
          
          <input name="fin" id="fin" type="hidden" value="<?php echo $fin;?>"> 
	      <?php if($row_rs_buscar['Ret_Est']!="I"){?>
	      <button type="button" name="imageField" id="imageField" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()" ><i class=" icon-arrow-right icon-white"></i>
	        </button> 
          <?php }else{ echo '&nbsp;';}?> 
          </form>	    	    
        </td>
	    </tr>
        
	    <?php $itemr++; } ?>
	  <?php if(($op!=1 && $opcion_bchk==1) || ($op!=1 && $opcion_bchk==0)){?>
      </tbody>
      <tfoot>
      <tr class="Cabecera1">
	    <td height="12" colspan="5" align="right"><strong>Totales:</strong></td>
	    <td align="right"><span class="LetraNegra"><strong><?php echo formato_numero($total_base,2,4);  ?></strong></span></td>
	    <td align="right"><span class="LetraNegra"><strong><?php echo formato_numero($Suma_Val_Ret,2,4); ?></strong></span></td>
	    <td colspan="2" align="center">&nbsp;</td>
	 </tr>	
     </tfoot> 
		<?php }?>
             
  </table>
  <table width="20%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="44%"><button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-primary start" title="Buscar" onClick="$('#exportarReporte').find('table:first-child').clone().append($('#Tbl_Codigos').html()).exportarExcelPhp('Retenciones-'+$.getDate()+'.xls',urlPhpExcel,'Datos 1');">
                <i class=" icon-share icon-white"></i>&nbsp;&nbsp;<span>Excel</span>
        </button></td>
        <td width="56%">
        <form name="frmReporte" id="frmReporte" method="post" action="fac_pri_ret_grupales_2.0.php" target="_blank">
            <input name="Ret_Cod" type="hidden" value="<?php echo $Ret_Cod; ?>">                   
            <button name="bnt_print" type="submit" class="btn btn-primary start" id="bnt_print" value="Imprimir"><i class=" icon-print icon-white"></i><span>&nbsp;&nbsp;Imprimir&nbsp;&nbsp;</span></button>
            <input type="hidden" id="cmb_mes" name="cmb_mes" value="<?php echo $cmb_mes;?>" />
            <input type="hidden" id="cmb_anio" name="cmb_anio" value="<?php echo $cmb_anio;?>" />  
            <input type="hidden" id="Chk_Fec" name="Chk_Fec" value="<?php echo $Chk_Fec;?>" />          
            <input type="hidden" id="Ren_Cod" name="Ren_Cod" value="<?php echo $Ren_Cod;?>" />          
            <input type="hidden" id="optest" name="optest" value="<?php echo $optest;?>" />                       
        </form>
        </td>
      </tr>
    </table>
</FIELDSET>
<?php 
	echo barra_estado($total_rs_buscar);
	echo "<br>";
} else {  
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
 <thead> 
  <tr>   
    <th width="6%" align="center">Item</th>
    <th width="15%" align="center" >C&oacute;d. Imp. </th>
    <th width="15%" align="center">% de Reten. </th>
    <th width="23%" align="center">Can. compr. compra </th>
    <th width="18%" align="center">Base imp. </th>
    <th width="23%" align="center">Valor retenido </th>
    </tr>
  </thead>
  <tbody>  
  <?php  $itemr=1; $Suma_Val_Ret=0; $total_base=0; 
  foreach($rs_buscar as $row_rs_buscar)  { 
  		/**
		*  Consulta el detalle de las retenciones para sumarlas una a una, debido a descuadres de decimales 
		*/	
		$rs_det_renta = $obBD_con1->getArrayConsulta(183,tipo_compr.'*'.$anio_rango_1.'*'.$anio_rango_2.'*'.$row_rs_buscar['Ren_Cod'].'*'.$optest.'*'.$Ses_Emp_Cod,$obBD_conexion);
		
		if($Ren_Cod=='R'){
			$Tot_Ret=0;		
			$Tot_Bas = 0;
			foreach($rs_det_renta as $row_rs_det_renta){
				$Tot_Bas = $Tot_Bas + $row_rs_det_renta['Total']; 
				$Tot_Ret=$Tot_Ret + round($row_rs_det_renta['Renta'],2); 
			}
		}
  
?>
  <tr>
    <td align="center" <?php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?php echo $itemr; ?></td>
    <td align="center" <?php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?php $Ren_Sri=$row_rs_buscar['Ren_Sri']; echo $Ren_Sri; unset($Ren_Sri);  ?></td>
    <td align="center" <?php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?php $Por_Sri=$row_rs_buscar['Ren_Por']; echo $Por_Sri;   unset($Por_Sri);  ?>	</td>
    <td align="center" <?php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?php $Num_Cop=$row_rs_buscar['Num_Cop']; echo $Num_Cop;   unset($Num_Cop);  ?>	</td>
    <td align="right" <?php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>  >
		<?php 	
		if($Ren_Cod=='R'){
			$total_base= $total_base + $Tot_Bas;
			$Base_Tot=formato_numero($Tot_Bas,2,4); 
			echo $Base_Tot; 
		}else
		{
		   echo formato_numero($row_rs_buscar['Ret_Bas'],2,4);
		   $total_base=$total_base + $row_rs_buscar['Ret_Bas'];
		}
		     ?>	</td>
    <td align="right" <?php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>  >
	<?php 
		if($Ren_Cod=='R'){
			echo formato_numero($Tot_Ret,2,4); 
			$Suma_Val_Ret=$Suma_Val_Ret+$Tot_Ret;   
			unset($Base_Tot); unset($Tot_Bas); unset($Tot_Ret);    
		}
		else
		{
			echo formato_numero($row_rs_buscar['Renta'],2,4);
		 	$Suma_Val_Ret=$Suma_Val_Ret + $row_rs_buscar['Renta'];
		}
		?></td>
    </tr>
    
  <?php $itemr++;  }?>	
  <tfoot>
  <tr class="Cabecera1">
    <td height="25" colspan="4" ><div align="right"><strong>TOTALES:</strong></div></td>
    <td align="right"><?php echo formato_numero($total_base,2,4);  ?></td>
    <td align="right"><?php echo formato_numero($Suma_Val_Ret,2,4); ?></td>
  </tr>
  </tfoot>
  </tbody>
</table>
<br>
<?php
/**
*  Consulta del total de retenciones emitidas RENTA 
*/
$row_rs_num_renta = $obBD_con1->getRowConsulta(184,tipo_compr.'*'.$optest.'*'.$anio_rango_1.'*'.$anio_rango_2.'*'.'R*'.$Ses_Emp_Cod,$obBD_conexion);
 
/**
*  Consulta del total de retenciones emitidas IVA 
*/
$row_rs_num_iva = $obBD_con1->getRowConsulta(184,tipo_compr.'*'.$optest.'*'.$anio_rango_1.'*'.$anio_rango_2.'*'.'I*'.$Ses_Emp_Cod,$obBD_conexion);
?>
<table width="100%" border="0" class="LetraNegra">
  <tr>
    <td width="3%"><strong><?php echo $row_rs_num_renta['Renta_Iva']; ?></strong></td>
    <td width="97%"><?php echo "Comprobantes de Impuesto a la Renta"; ?></td>
  </tr>
  <tr>
    <td width="3%"><strong><?php echo $row_rs_num_iva['Renta_Iva'];?></strong></td>
    <td width="97%"><?php echo "Comprobantes de Iva"; ?></td>
  </tr>
</table>
</FIELDSET>
<?php } 
//if(($op!=1 && $opcion_bchk==1) || ($op!=1 && $opcion_bchk==0)){
?>
     
     
  <?php 
  	//}//Fin del if($op!=1 && !isset($Chk_For))
  }
  else
  {
  	  if(!isset($Ret_Cod)){
  		echo "<br>";
		?>
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Resultados de la busqueda</label>
        </LEGEND>
	  <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
	  <thead> 
      <tr>
        <th align="center">Nro. Int. </th>
        <th align="center">No. Ret. </th>
        <th align="center">No. Fac. </th>
        <th align="center">Fecha</th>
        <th align="center">Proveedor</th>
        <th align="center">Base imp </th>
        <th align="center">Valor</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
      </tr>
       </thead>
      <tr>
	      <th width="5%" align="center">&nbsp;</th>
	      <th width="7%" align="center">&nbsp;</th>
		  <th width="10%" align="center">&nbsp;</th>	
          <th width="7%" align="center">&nbsp;</th>
          <th width="30%" align="center"><?php echo error_alerta("No hay resultados que mostrar", 1);?></th>
          <th width="7%" align="center">&nbsp;</th>
		  <th width="7%" align="center">&nbsp;</th>
		  <th width="3%">&nbsp;</th>
		  <th width="3%">&nbsp;</th>
      </tr>     
      </table>
      </FIELDSET>
		<?php
		}
  }
}
	
if(isset($Ret_Cod)){?>
<br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Proveedor </label>
</LEGEND>
<table width="100%" border="0">
  <tr>
    <td width="11%" class="Etiqueta1">Proveedor:</td>    
    <td width="89%" class="LetraNegra"><?php echo $rs_inf_retencion[0]['Prs_Ape'].' '.$rs_inf_retencion[0]['Prs_Nom'] ?></td>
  </tr>
  <tr>
  	<td width="11%" class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
    <td width="89%" class="LetraNegra"><?php echo $rs_inf_retencion[0]['Prs_Ced'] ?></td>	
  </tr>
  <tr>	<td class="Etiqueta1">Direcci&oacute;n:</td>
    	<td colspan="5" class="LetraNegra"><?php echo $rs_inf_retencion[0]['Prs_Dir']?></td>
  </tr>
</table>
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos de la Retención </label>
</LEGEND>
 <?php //echo mensaje_requerido(); ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Generales </label>
</LEGEND>
  <table width="100%"  border="0">
  <tr>
  <td width="11%" class="Etiqueta1">No. Com. Retenci&oacute;n: </td>
  <td width="12%" class="LetraNegra"><?php $Ret_Num=$rs_inf_retencion[0]['Ret_Num']; echo $Ret_Num;   ?></td>
  <td width="9%" class="Etiqueta1">Fecha del Comp: </td>
   <td width="12%" class="LetraNegra"><?php $Ret_Fec=$rs_inf_retencion[0]['Ret_Fec']; echo $Ret_Fec;  ?></td>
   <td width="7%" class="Etiqueta1">No. Factura :</td>
   <td width="49%" class="LetraNegra"><?php  $Num_Fac=$rs_inf_retencion[0]['Cop_Num']; echo $Num_Fac; ?>
     <input name="Ret_Cod" type="hidden" id="Ret_Cod" value="<?php echo $rs_inf_retencion[0]['Ret_Cod']; ?> "></td>
  </tr>
</table>
    <table width="100%"  border="0">
      <tr>
        <td width="11%"  class="Etiqueta1"> Fecha Emi. Comp:</td>
        <td width="12%"><span class="LetraNegra">
          <?php  $Fec=$rs_inf_retencion[0]['Cop_Fec']; echo $Fec; ?>
        </span></td>
        <td width="9%" class="Etiqueta1">Fecha de Cad. Comp:</td>
        <td width="68%" class="LetraNegra"><?php  $Fcad=$rs_inf_retencion[0]['Cop_Cad'];
		 if($Fcad==NULL || empty($Fcad))
		 {
			 /** 
			 *  Consultar la fecha de caducidad para la autorizacion del bloque de liquidaciones de compra 
			 */
			 $row_fec_aut_liq=$obBD_con1->getRowConsulta(523,$rs_inf_retencion[0]['Aut_Cod'],$obBD_conexion);		 
			 echo $row_fec_aut_liq['Aut_Cad'];			
		 }else{ echo $Fcad; } 
		 ?></td>
        </tr>
      <tr>
        <td class="Etiqueta1">Tipo Compr:</td>
        <td colspan="3"><span class="LetraNegra">
          <?php  $Tic=$rs_inf_retencion[0]['Tic_Des']; echo $Tic; ?>
        </span></td>
        </tr>
    </table>
    <table width="100%" border="0">    
    <tr>
      <td width="11%" class="Etiqueta1">Por concepto de: </td>
      <td width="89%"  colspan="6" class="LetraNegra"><?php echo $rs_inf_retencion[0]['Ret_Con']; ?></td>
    </tr>
  </table>
</FIELDSET>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la retención </label>
</LEGEND>
  <table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader02">
	<thead> 
		<tr>
          <th width="15%" >Eje/Fiscal</th>
          <th width="15%"  >C&oacute;d. imp. </th>
          <th width="15%" >Impuesto</th>
          <th width="15%" >Base Renta</th>
          <th width="15%" >% de reten </th>
          <th width="10%" >Valor </th>
	  </tr>
    </thead>  
    <tbody id="c_contenido">  
	<?php 
	//$rs_detalle = $obBD_con1->getArrayConsulta(182,$Ret_Cod,$obBD_conexion);								
	//$total_rs_detalle=count($rs_detalle);
	
	/**
	*  A�o fiscal actual 
	*/		
	$total_retenido=0;
	$Ret_Est=$rs_inf_retencion[0]['Ret_Est'];
	
	foreach($rs_inf_retencion as $row_car_detalle)//Inicio del } while($row_car_detalle=mysqli_fetch_assoc($rs_inf_retencion));
	{	
		$fila++;
		$Fecha=explode('-',$row_car_detalle['Ret_Fec']);
	?>
	<tr >
	 
	  <td align="center" class="LetraNegra"><?php echo $Fecha[0];?></td>
      <td align="center" class="LetraNegra"><?php $Cod_Imp=$row_car_detalle['Ren_Sri']; echo $Cod_Imp;  ?></td>
	  <td align="center" class="LetraNegra"><?php echo $row_car_detalle['Ret_Imp']; ?></td>    
	  <td align="right" class="LetraNegra"><?php echo formato_numero($row_car_detalle['Ret_Bas'],2,4); ?></td>	
	  <td align="center" class="LetraNegra"><?php $Ren_Por=$row_car_detalle['Ren_Por']; echo $Ren_Por;  ?></td>
	  <td align="right" class="LetraNegra">
	  <?php  $Ret_Val_Por=round(($row_car_detalle['Ret_Bas']*$row_car_detalle['Ren_Por'])/100,2); 
	       echo formato_numero($Ret_Val_Por,2,4);
		   $total_retenido=$total_retenido+round((($row_car_detalle['Ret_Bas']*$row_car_detalle['Ren_Por'])/100),2); ?>
	  </td>
	  </tr>
	<?php } ?>
	</tbody>
	<tfoot>
    <tr>
	  <td colspan="5" class="Etiqueta1">TOTAL RETENIDO :&nbsp;&nbsp;&nbsp;
	    <input id="nfilas" name="nfilas" type="hidden" value="<?php echo $fila; ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
	  <td class="LetraNegra" align="right"><strong>
	   	<?php echo formato_numero($total_retenido,2,4);  ?></strong></td>
		</tr>
     </tfoot>
	</table>
	</fieldset>
	</FIELDSET>

		<br>
<?php
/**
* Consulta del reporte para impresion 
*/
$pagina = $_SERVER['PHP_SELF'];
$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);		
switch ($op){
case 1: 
?>		
<table width="157">		
		<tr>
			<td align="left">
			<?php if ($Ret_Est == 'A'){ echo "</form>"; ?> 
				<form name="form2" id="form2" method="post" action="<?php echo $reportes[1]."?Ret_Cod=".$Ret_Cod;?>" target="_blank">
					<input name="Ret_Cod" type="hidden" value="<?php echo $Ret_Cod; ?>">                   
					<button name="bnt_print" type="submit" class="btn btn-primary start" id="bnt_print" value="Imprimir"><i class=" icon-print icon-white"></i><span>&nbsp;&nbsp;Imprimir&nbsp;&nbsp;</span></button>
				</form>
			<?php } else { echo error_alerta("Comprobante de retenci&oacute;n  ANULADO", 1); } ?> </td>
		</tr>
      </table>
 <?php
 	break;
	case 2:	
	/**
	* Si existen datos encontrados 
	*/
	//if($total_rs_buscar != 0)
	//{
  ?>  
  <table width="527">    
    <tr>
      <td colspan="3" align="left" >
	  <form name="form" action="<?php echo $reportes[1];?>" method="post" target="_blank">
        <input type="hidden" name="op" value="<?php echo $op;?>" >		  			
        <input id="ini" name="ini" value="<?php echo $ini;?>" type="hidden" >
        <input id="fin" name="fin" value="<?php echo $fin;?>" type="hidden" >
        <input id="Opes" name="Opes" value="<?php echo $optest;?>" type="hidden" >
        <input id="Ret_Cod" name="Ret_Cod" type="hidden" value="<?php echo $Ret_Cod; ?>">
        <input name="Renp" value="<?php echo $Ren_Por;?>" type="hidden" >
        <input name="optest" value="<?php echo $optest;?>" type="hidden" >
        <input id="Ren_Cod" name="Ren_Cod" type="hidden" value="<?php echo $Ren_Cod;?>"> 
        <input id="Ren_Por" name="Ren_Por" type="hidden" value="<?php echo $Ren_Por;?>"> 
        <input name="hdd_save2" type="hidden" value="print">
        <button name="bnt_print" type="submit" class="btn btn-primary start" id="bnt_print" value="Imprimir"><i class=" icon-print icon-white"></i>
<span>&nbsp;&nbsp;Imprimir&nbsp;&nbsp;</span></button>
  	  </form>         
      </td>
    </tr>
  </table>
  <?php 
  //}//Fin del if($total_rs_buscar != 0)
  	break;
} //Fin del switch ($op){

echo "</div>";
 
}//Fin del if(isset($Ret_Cod))
?>
 
</td>
</tr>
</table>
<?php 

/**
*  Parametro de la busqueda por fecha en compras 
*  Control para setear el arreglo solo cuando tenga valores
*/
if (isset($existe_pagos) && $existe_pagos > 0)
{
	$com_leyenda[0]=$existe_pagos; 	
}//Fin del if ($existe_pagos > 0)
if (isset($anulada) && $anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>		
<?php include('../../componentes/FRONT/com_con_leyenda.php');?>	  
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar"></div>
       </div>
</div>
</div> 
<div>
<?php 
$obBDr = new MysqlDatosContab(true);
echo '<div id="exportarReporte" style="display:none;">'.$obBDr->getReportHeader($_SESSION['Ses_Suc_Cod'], "REPORTE RETENCIONES", $subtittle, null, false,7).'<table class="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table></div>';

?>
</div>
</BODY></HTML>
<?php	
/* liberar conexiones en la BD */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>