<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* Descripción: Permite dar de baja un retencion
* Fecha de actualización:	2013-05-13 
* Moificado: José Cumbicos
*/
	require_once('../../administrador/LOGICA/seguridad.php');
	require_once('../LOGICA/fac_log_retencion.php');
	require_once('../../componentes/LOGICA/logica.php');
        require_once('../../Librerias/procedimientos/almacenados_standar.php');	    	
	require_once('../../Librerias/postclass.php');	
	
 	/**
	* creacion del Objeto de conexion 
	*/
	$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
	
	/**
	* creacion del objeto mysql para las consultas 
	*/
	$obBD_con1 =  new Class_Log_Datos_Ret; 	
	
	/*
	* Creación del objeto para evitar el reenvio 
	*/
	$thisPost = new Post_Block;
	
	/**
	* evitar el reenvio de formularios 
	*/
	$hoy = date("Y-m-d");
	
	/**
	* asingo el valor a la variable mes 
	*/
	$mes = date("m");
	
if (isset($_POST['postID'])&&$thisPost->postBlock($_POST['postID']))
{	
	if(isset($Ret_Cod))
	{
		/**
		* creacion del objeto mysql para las inserciones 
		*/
		$obBD_ins1 =  new Class_Log_Datos_Ret;
		
		/**
		* inicio de la transaccion 
		*/
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);	
		
		/*
		* Dara de baja a la retención 
		*/
		$obBD_ins1->operacionobBD(508,$Ret_Cod.'*'.'I',$obBD_conexion);			
		
		/**
		* grabar auditoria
		*/
		//$obBD_ins1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
		
		/*
		*  Fin de la transaccion
		*/
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
	}	
}


if(isset($ajax_detalle))
{
	include('../COMPONENTES/tesComDetalleReten.php'); 
	exit();	
}

/**
*  Busqueda del codigo de las facturas 
*/
if (isset($hdd_Pec_Cod)){
	/**
	*  Carga el periodos contable actual 
	*/
	$row_rs_periodo = $obBD_con1->getRowConsulta(189,$Pec_Cod,$obBD_conexion);			
	$total_rs_periodo=$row_rs_periodo['Pec_Fei'] > 0? 1 : 0;
	
	/**
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);	
	
}

/**
* Inicializo la variable en 1 
*/
if (isset($txt_busqueda))
{				
	    if ($op_opciones == "d")
		{
			$rs_buscar = $obBD_con1->getArrayConsulta(505,trim($txt_busqueda).'*'.'6'.'*'.$cmb_mes.'*'.$Ses_Emp_Cod,$obBD_conexion);
			$total_rs_buscar=count($rs_buscar);
			
		}else{
			if ($op_opciones == "t")
		    {
				$rs_buscar = $obBD_con1->getArrayConsulta(504,trim($txt_busqueda).'*'.'6'.'*'.$cmb_mes.'*'.$Ses_Emp_Cod,$obBD_conexion);
				$total_rs_buscar=count($rs_buscar);
			}else{
		       $rs_buscar = $obBD_con1->getArrayConsulta(506,trim($txt_busqueda).'*'.'6'.'*'.$cmb_mes.'*'.$Ses_Emp_Cod,$obBD_conexion);
			   $total_rs_buscar=count($rs_buscar);
		    }  
		}

}//FIn del case $op
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>
		<script language="javascript" src="../VALIDACIONES/fac_val_compras.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>         
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>  
        
        <script language="javascript" src="../VALIDACIONES/XML.js"></script>		
                       
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
       <script>
		$(function() { 
			//var imagen = "../../mascaras/model1/imagenes/32x32/calendar.gif";
			/* Campo 1 */
			$( "#Cop_Fec" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});			
			
			/* Campo 2 */
			$( "#Cop_Cad" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			

			/* Campo 3 */
			$( "#Com_Fec" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			

			/* Campo 4 */
			$( "#Cop_Imp" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen*/
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			

			/* Campo 5 */
			$( "#Cpp_Ven" ).datepicker({
				changeMonth: true, changeYear: true, 
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			
		}); 		
        </script>            
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td height="10" colspan="3">&raquo;anulaci&oacute;n de  RETENCI&Oacute;n <?Php echo isset($periodo)?$periodo:''; ?></td>
  </tr>
	     
  <tr>
        <td height="400" valign="top">
		<form name="form2" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
		<?Php
		
		/* Condicion para evaluar cuando mostrar los periodos contables */
		if (!isset($hdd_Pec_Cod) && !isset($hdd_volver))
		{ 	?>
				
			<?Php include("../../componentes/FRONT/comConPeriodoCont.php"); ?>
			<input name="hdd_Pec_Cod" id="hdd_Pec_Cod" value="1" type="hidden" >
		 </form>
		 <?Php 
		 }
		 else
		 {
		

	
?>

<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
<FIELDSET>
<legend>
<label class="Titulos2">Buscar por:</label>
</legend>
<br />
<table width="695" border="0">
    <tr>
      <td width="114">
        <input name="op_opciones" type="radio" onClick="document.getElementById('cmb_mes').disabled=false;  document.getElementById('txt_busqueda').focus()"  style="cursor:pointer" value="r" checked>
        <span class="Etiqueta1">Apellido</span>
        </td>
      <td width="150"><input name="op_opciones" type="radio" onclick="document.getElementById('cmb_mes').disabled=false;  document.getElementById('txt_busqueda').focus()"  style="cursor:pointer" value="t" checked="checked" />
        <span class="Etiqueta1">C&eacute;dula/R.U.C.</span></td>
      <td width="156">
      <input name="op_opciones" type="radio" value="d" onClick="document.getElementById('cmb_mes').disabled=true;  document.getElementById('txt_busqueda').focus()"  style="cursor:pointer"  >
        <span class="Etiqueta1">No. Com. Reten.
        <input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="">
        <input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
        </span></td>
      <td width="9">&nbsp;</td>
      <td width="244">
	  <?php 
		/* Parametro de la busqueda por fecha en compras */
		$Com_Fecha="AND MONTH(Cop_Fec)"; 
		?>		
		<?Php include('../../componentes/FRONT/com_con_meses.php');?></td>
      </tr>
  </table>

  <table width="591" height="36" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="91" height="28" class="BarraBusqueda"><div align="right"><span class="Asterisco">* </span>Búsqueda:</div></td>
      <td width="500" class="BarraBusqueda"><div align="center"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase ">&nbsp;&nbsp;&nbsp;<button name="btn_buscar" type="button" class="btn btn-success fileinput-button" title="Buscar" id="btn_buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)"> <i class="icon-search icon-white"></i><span>Buscar</span></button>     </div></td>      
    </tr>
  </table>
</FIELDSET>
</form>
<?Php if(isset($txt_busqueda)){?>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
	 <thead> 
      <tr>
	    <th width="5%" align="center">Nro. Int</th>
	    <th width="6%">Tipo</th>
	    <th width="8%">No. Com. Reten. </th>
		<th width="8%">No. Fact.</th>	
        <th width="6%" align="center">Fecha</th>
        <th width="30%" align="center">Proveedor</th>
        <th width="6%" align="center">Base</th>
        <th width="6%" align="center">Valor retenido  </th>
        <th width="3%">&nbsp;</th>
		<th width="3%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	  <?Php 
	  
	  if($total_rs_buscar != 0)
	  {
		$i=0;  
		/*
		* inicializo un contador para saber si en la búsqueda se encuentran facturas con pagos
		*/
		$existe_pagos=0;
	    
		/**
		*   color Rojo para registros inactivos
		*/
		
		
		foreach($rs_buscar as $row_rs_buscar) {  		
		if($row_rs_buscar['Ret_Est']=='I')
	    { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
		
		$i++;
		
	  	/*
		*  Consultar si la factura se registro de forma automática 
		*/
		$row_rs_compra_manual_automatica=$obBD_con1->getRowConsulta(380,$row_rs_buscar['Cop_Cod'],$obBD_conexion);		
		$num_row_rs_compra_manual_automatica=$row_rs_compra_manual_automatica['Com_Cod'] > 0? 1 : 0;
						
		/**
		*  consulto si la factura de compra ya tiene pagos realizados 
		*/
		$row_rs_existe_pagos=$obBD_con1->getRowConsulta(357,$row_rs_compra_manual_automatica['Com_Cod'],$obBD_conexion);				
		$num_row_rs_existe_pagos=$row_rs_existe_pagos['Cpp_Cod'] > 0? 1 : 0;
	   ?>
	  <form name="form3" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">      
      <tr>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php  $Ret_Int_Com=$row_rs_buscar['Ret_Cod']; echo $Ret_Int_Com; ?></FONT></td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>">
	      <?Php
		
		if($num_row_rs_compra_manual_automatica>0)
		{ echo "Automático";
		}else{ echo "Manual";}?>
        </FONT>	
        </td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>">
	      <?Php  $Ret_Com=$row_rs_buscar['Ret_Num']; echo $Ret_Com; ?>
        </FONT>
        </td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>">
	      <?Php $Cop_Num=$row_rs_buscar['Cop_Num']; echo $Cop_Num; ?>
        </FONT>
        </td>	
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>">
	      <?Php $Fec_Ret_Con=$row_rs_buscar['Ret_Fec'];  echo $Fec_Ret_Con;  ?>
        </FONT>
        </td>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><div align="left">
	      <?Php echo marcar_cadena($txt_busqueda,$row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom'], '#FFFF00', 1); ?>	      
	      </div>
          </FONT>
	      </td>
	    <td><FONT COLOR="<?php echo $rojo;?>">
	      <div align="right">
	      <?php
		  $row_base_impu=$obBD_con1->getRowConsulta(567,$row_rs_buscar['Ret_Cod'],$obBD_conexion);		 
		  echo formato_numero(isset($row_base_impu['suma_re'])?$row_base_impu['suma_re']:0, 2, 1);
		  //$row_base_impu['suma_re'];
		  
		   ?>
	        </div>		   
            </FONT>
            </td>
	    
	    <td width="8%" align="right"><FONT COLOR="<?php echo $rojo;?>">
	      <?Php	
			$rs_detalle = $obBD_con1->getArrayConsulta(381,$row_rs_buscar['Ret_Cod'],$obBD_conexion);			
			$Val_Ret=0;
			foreach($rs_detalle as $row_rs_detalle)
			{
				$Val_Ret=$Val_Ret+($row_rs_detalle['Ret_Bas']*$row_rs_detalle['Ren_Por']/100);
			}
		    echo formato_numero($Val_Ret, 2, 1);
		?>		
        </FONT></td>
	    <td align="center"><button type="button" class="btn btn-success btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&amp;ret_codigo=<?php echo $row_rs_buscar['Ret_Cod'];?>','mostrar')"><i class="icon-info-sign icon-white"></i></button>
        </td>
	    <td align="center">
		<?Php //if($num_row_rs_existe_pagos==0){ 	
		      if(1==1){ 	
				if ($row_rs_buscar['Ret_Est'] == 'A') { ?>
         
          <?php $thisPost->startPost();	?>
          <input type="hidden" id="Ret_Cod" name="Ret_Cod" value="<?php echo $row_rs_buscar['Ret_Cod']; ?>"/>
          <input type="hidden" id="Pec_Cod" name="Pec_Cod" value="<?php echo $Pec_Cod; ?>"/>
          <input type="hidden" id="hdd_Pec_Cod" name="hdd_Pec_Cod" value="1"/>
          <input type="hidden" id="elim" name="elim" value="<?Php echo $row_rs_buscar['Ret_Est']; ?>"/>
          <input type="hidden" id="op" name="op" value="1"/>
          
          <button type="button" class="btn btn-danger btn-mini" title="Anular" onClick= "alert('<< Ud. se dispone a dar de Baja la retencion No <?Php echo $Ret_Com; ?>, para lo cual una vez realizada la transacci&oacute;n NO se podran reversar los cambios >>'); confirmacion(this.form)"><i class="icon-ban-circle icon-white"></i></button>
          
		  <?Php } else { echo "&nbsp;"; } ?>		
	      <?Php }else{ /* Contador */ $existe_pagos++;  ?><img src="../../mascaras/model1/imagenes/32x32/dinero.png" width="22" height="22"  >
	      <?php } ?>
          </td>
	    </tr>	
        </form>  
		<?Php   
		 }
		  }else { 
		?>
	  <tr>
	    <td align="center">&nbsp;</td>
	    <td align="center">&nbsp;</td>
	    <td align="center">&nbsp;</td>
	    <td align="center">&nbsp;</td>
	    <td align="center">&nbsp;</td>
	    <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1);?></td>
	    <td align="center">&nbsp;</td>
	    <td align="center">&nbsp;</td>
	    <td align="center">&nbsp;</td>
	    <td align="center">&nbsp;</td>
	    </tr>
	  <?Php 
	      }
	  ?>
      </tbody>
  </table>
</FIELDSET>
 <?Php echo barra_estado($total_rs_buscar);  ?>
<br>
<?php 
		/* Parametro de la busqueda por fecha en compras */
		/* Control para setear el arreglo solo cuando tenga valores*/
		if (isset($existe_pagos) && $existe_pagos > 0)
		{
			$com_leyenda[0]=$existe_pagos; 	
		}//Fin del if ($existe_pagos > 0)
		if (isset($anulada) && $anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}//Fin del if ($anulada > 0)
		?>		
		<?Php include('../../componentes/FRONT/com_con_leyenda.php');?>
  <?Php 
  
	}
  ?>
  

 <?Php } /* Cierro el IF del Else if (!isset($hdd_Pec_Cod) && !isset($hdd_volver)) */ ?>
</td>
</tr>
</table>
</p>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar"></div>
       </div>
</div>
</div> 	  
</BODY></HTML>
<script type="text/javascript" src="../VALIDACIONES/fac_par_retencion.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
<?Php

/**
*  Fin cierro las conexion 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();


?>
