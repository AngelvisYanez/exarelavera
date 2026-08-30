<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/** 
* Descripci�n: Permite modificar las cuentas del plan de cuentas
* Fecha de actualizaci�n:	2012-04-19
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2013-02-22
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2014-07-07
* Desarrollador:	Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	  

/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
/**
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;  

if (isset($ajax_codigo))
{
	$row_rs_vercodigo = $obBD_con1->getRowConsulta(1, $codpla.'*'.$cod_cuenta.'*'.$Ses_Emp_Cod, $obBD_conexion);

	if (count($row_rs_vercodigo) > 0 && $row_rs_vercodigo['Pld_Cdc'] != $Pld_Cdc)
	{ 
	?>
     <input name="cod_cuenta" type="text" id="cod_cuenta" value="<?php echo $cod_cuenta; ?>" onBlur="parametro_x(this, '.') 
              ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_codigo&codpla=<?Php echo $codpla; ?>&cod_cuenta='+this.value+'&Pld_Cdc='+document.getElementById('Pld_Cdc').value, 'div_existe')">	  
    <font color="#FF0000">El c�digo de cuenta <?Php echo $cod_cuenta; ?> ya existe</font>
    <?Php	
	}
	else
	{ ?>
     <input name="cod_cuenta" type="text" id="cod_cuenta" value="<?php echo $cod_cuenta; ?>" onBlur="validar_cuentas(this.form, this); 
                  ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_codigo&codpla=<?Php echo $codpla; ?>&cod_cuenta='+this.value+'&Pld_Cdc='+document.getElementById('Pld_Cdc').value, 'div_existe')">	 		
   <?Php
	}
	exit();
}


if (isset($hdd_save1))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{ 
	   /**
	   * inicio de la transaccion 
	   */
	   $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		
		switch ($hdd_iva)
		{
			case 1:
			/**
			* Guardado de la asignaci�n de la cuenta del iva
			*/
			if (/*isset($hdd_iva) &&*/ !isset($hdd_volver))
			{
				/**
				* Elimina el iva cobrado actual
				*/
				$obBD_con1->operacionobBD(317, $iva_cob, $obBD_conexion);
				/**
				* Inserta la cuenta contable en la tabla iva cobrado
				*/
				$obBD_con1->operacionobBD(318, $Pld_Cod, $obBD_conexion);
			}	
			break;
			case 2:
			/**
			* Guardado de la asignaci�n de la cuenta del iva pagado
			*/
			if (!isset($hdd_volver))
			{
				/**
				* Elimina el iva pagado actual
				*/
				$obBD_con1->operacionobBD(320, $iva_pag, $obBD_conexion);
				/**
				* Inserta la cuenta contable en la tabla iva pagado
				*/
				$obBD_con1->operacionobBD(321, $Pld_Cod, $obBD_conexion);
			}	
		}		
		/**
		* fin de la transacci�n 
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);		

	}}

if (!isset($np))
{
	/**
	* Cargado de los planes de cuenta de una empresa en especifico.
	*/
	$row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod,$obBD_conexion);
}
else
{
	/*if ($thisPost->postBlock($_POST['postID'])) 
	{ 
	}*/
	/**
	* Cargado de los nodos - Codigo Empresa, Codigo Plan de Cuenta, Nodo Padre	
	*/
	if (isset($np))
	{	
		/**
		* Cargado de las cuentas del plan
		*/
		$row_rs_nodos = $obBD_con1->getArrayConsulta(303, $Ses_Emp_Cod.'*'.$codpla.'*'.$np, $obBD_conexion);
		/**
		* Cargado del plan de cuenta de una empresa en especifico.
		*/
		$row_rs_plan = $obBD_con1->getRowConsulta(310, $codpla, $obBD_conexion);		
	}	
}	
?>
<HTML>
	<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/con_val_planc.js"></script>
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
<table width="100%" border="0"  cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Configurar cuentas de iva</td>
  </tr>
	<tr>
      <td align="left" valign="top"> 
 <?Php
 if (!isset($np))
 {
 ?>                  	
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la Busqueda</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
      <tr>
        <th width="9%" align="center"><strong>C&oacute;d. Int.</strong></th>
        <th width="7%" align="center"><strong>Fecha</strong></th>
        <th width="56%" align="center"><strong>Descripci&oacute;n</strong></th>
        <th width="12%" align="center"><strong>Estado</strong></th>
        <th width="4%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
      <?php
	  /**
	  * Evalua si existen planes de cuenta creados 
	  */
	  if (count($row_rs_planes) > 0) 
	  {
		foreach($row_rs_planes as $row)
		{
	 		 if($row['Pla_Est']=='Inactivo')
	  		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}			
	  ?>
      <tr>
        <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row['Pla_Cod']; ?></font></td>
        <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row['Pla_Fec']; ?></font></td>
        <td><font color="<?php echo $rojo; ?>"><?php echo $row['Pla_Obs']; ?></font></td>
        <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row['Pla_Est']; ?></font></td>
        <td align="center">
          <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">      
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
              <i class=" icon-arrow-right icon-white"></i>
              </button>
            <input type="hidden" id="codpla" name="codpla" value="<?php echo $row['Pla_Cod']; ?>" />         
            <input type="hidden" id="np" name="np" value="0" /> 
            </form>        
        </td>
      </tr>
      <?php 
	  		}//foreach($row_rs_planes as $row)
	  }//Fin del if ($total_rs_planes > 0)  
	  else
	  {
	   ?>
	  	<tr><td>&nbsp;</td>
	  	  <td>&nbsp;</td>
	  	  <td><?Php echo error_alerta("&iexcl;No hay resultados que mostrar!", 1) ?></td>
	  	  <td>&nbsp;</td>
          <td>&nbsp;</td>
	  	</tr>
	  <?php }?>
      </tbody>
    </table>
    <?php echo barra_estado(count($row_rs_planes)); ?>   
	</FIELDSET>
	<?Php
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
	<?php 
	 }//FIn del if (!isset($np))
	
	/**
	* Modificaci�n de una cuenta 
	*/
	if (isset($np)) 
	{
		/**
		* Consulta el iva cobrado
		*/
		$row_ivacobrado = $obBD_con1->getRowConsulta(316, $codpla,$obBD_conexion);
		/**
		* Consulta el iva pagado
		*/
		$row_ivapagado = $obBD_con1->getRowConsulta(319, $codpla,$obBD_conexion);		
	?>
	<br>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2"><?Php echo $row_rs_plan['Pla_Obs']; ?></label>
	</LEGEND>
	<table width="363" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="123" class="Etiqueta1">Usted esta editando: </td>
		<td width="240" class="LetraNegra"><strong>
		  <?php
		if ($np==0) 
		{
			echo "INICIO del Plan de Cuentas";
			$separador='';
		} 
		else 
		{
			$row_rs_direc = $obBD_con1->getRowConsulta(305, $np, $obBD_conexion);
			echo $row_rs_direc['Pld_Cdc'].".-  ".$row_rs_direc['Pld_Des'];
			$separador='.';
		}//Fin del if ($np==0) 
		?> 
		</strong></td>
	  </tr>
	</table>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	    <th width="7%" align="center"><strong>C&oacute;d. Int.</strong></th>
		<th width="9%" align="center"><strong>C�digo</strong></th>
		<th width="31%" align="center"><strong>Cuenta</strong></th>
		<th width="9%" align="center"><strong>Tipo</strong></th>
		<th width="10%" align="center"><strong>Estado</strong></th>
		<th width="13%" align="center">Iva Ventas&nbsp;<img src="../../mascaras/model1/imagenes/32x32/info.gif" title="La cuenta actual para Iva Ventas actual es: <?Php echo $row_ivacobrado['Pld_Cdc'].' - '.$row_ivacobrado['Pld_Des']; ?>" /></th>
		<th width="13%" align="center">Iva Compras&nbsp;<img src="../../mascaras/model1/imagenes/32x32/info.gif" title="La cuenta actual para Iva Compras actual es: <?Php echo $row_ivapagado['Pld_Cdc'].' - '.$row_ivapagado['Pld_Des']; ?>" /></th>
		<th width="8%" align="center">&nbsp;</th>
		</tr>
    </thead>
    <tbody>    
	  <?php
	  if (count($row_rs_nodos) > 0) 
	  {
		foreach($row_rs_nodos as $row)
		{
			if ($row['Pld_Est'] == 'Inactivo') 
			   { 
					$color_d = '#FF0000'; 
					$anulada++;
				} 
				else
				{
					$color_d = '';	
				} ?>
	  <tr>
	    <td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Cod']; ?></font></td>
		<td><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Cdc']; ?></font></td>
		<td><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Des']; ?></font></td>
		<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Tip']; ?></font></td>
		<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Est']; ?></font></td>
		<td align="center">
        <?Php
		if ($row['Pld_Tip'] == 'Detalle')
		{
			
			if($row_ivacobrado['Pld_Cod']== $row['Pld_Cod'])
			{ ?>
			<button type="button" class="btn btn-primary btn-mini" title="Cuenta contable para &laquo;Iva Ventas&raquo;" onClick= "" >
					<i class="icon-check icon-white"></i>
			</button>            
		<?php		
			}
			else
			{				
		?>
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
        <?php $thisPost->startPost();?>
			<button type="button" class="btn btn-primary btn-mini" title="Marcar cuenta contable para &laquo;Iva Ventas&raquo;" onClick= "confirmacion(this.form)" >
					<i class="icon-white"></i>
			</button>
            <input type="hidden" id="codpla" name="codpla" value="<?php echo $codpla; ?>" />
            <input type="hidden" id="np" name="np" value="<?php echo $row['Pld_Rec']; ?>" />
            <input type="hidden" id="Pld_Cod" name="Pld_Cod" value="<?php echo $row['Pld_Cod']; ?>" />
            <input type="hidden" id="iva_cob" name="iva_cob" value="<?php echo $row_ivacobrado['Pld_Cod']; ?>" />
            <input type="hidden" id="hdd_iva" name="hdd_iva" value="1" />
            <input type="hidden" id="hdd_save1" name="hdd_save1" value=""  />
        </form>
		<?php
			}
		}
		?>        
        </td>
		<td align="center">
        <?Php
		if ($row['Pld_Tip'] == 'Detalle')
		{
			
			if($row_ivapagado['Pld_Cod']== $row['Pld_Cod'])
			{ ?>
			<button type="button" class="btn btn-primary btn-mini" title="Cuenta contable para &laquo;Iva Compras&raquo;" onClick= "" >
					<i class="icon-check icon-white"></i>
			</button>            
		<?php		
			}
			else
			{				
		?>
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
        <?php $thisPost->startPost();?>
			<button type="button" class="btn btn-primary btn-mini" title="Marcar cuenta contable para &laquo;Iva Compras&raquo;" onClick= "confirmacion(this.form)" >
					<i class="icon-white"></i>
			</button>
            <input type="hidden" id="codpla" name="codpla" value="<?php echo $codpla; ?>" />
            <input type="hidden" id="np" name="np" value="<?php echo $row['Pld_Rec']; ?>" />
            <input type="hidden" id="Pld_Cod" name="Pld_Cod" value="<?php echo $row['Pld_Cod']; ?>" />
            <input type="hidden" id="iva_pag" name="iva_pag" value="<?php echo $row_ivapagado['Pld_Cod']; ?>" />
            <input type="hidden" id="hdd_iva" name="hdd_iva" value="2" />
            <input type="hidden" id="hdd_save1" name="hdd_save1" value=""  />
        </form>
		<?php
			}
		}
		?>        
        </td>
		<td align="center"> 
        <?Php
		if ($row['Pld_Tip'] == 'GRUPO')
		{ ?>
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
		  <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
		    <i class=" icon-arrow-right icon-white"></i>
		    </button>
		  <input type="hidden" id="codpla" name="codpla" value="<?php echo $codpla; ?>" />
		  <input type="hidden" id="np" name="np" value="<?php echo $row['Pld_Cod']; ?>" />
		  </form>
        <?Php
		}
		?>  
          </td>
		</tr>
	  <?php } //Fin del foreach($row_rs_nodos as $row)
	  } else { ?>
	  	<tr>
        	<td>&nbsp;</td>
	  	  	<td>&nbsp;</td>
	  	  	<td><?Php echo error_alerta("No hay ninguna cuenta creada", 1) ?></td>
	  	  	<td>&nbsp;</td>
	  	  	<td>&nbsp;</td>
	  	  	<td>&nbsp;</td>
	  	  	<td>&nbsp;</td>
	  	  	<td>&nbsp;</td>
	  	</tr>
	  <?php }?>
      </tbody>
	</table>
		<?php 
		 echo barra_estado(count($row_rs_nodos));			
		?>              
    <table width="214" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="100">
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
        <button type="button" class="btn btn-inverse fileinput-button" title="Volver al Plan de Cuentas" onclick="campos_hide(this.form, 'hdd_volver', '')"> <i class="icon-step-backward icon-white"></i> <span>&nbsp;&nbsp;Inicio&nbsp;&nbsp;</span></button>
        </form>
        </td>        
	<?php	
	if ($np!=0) 
	{
		/**
		* Link para volver atr�s
		*/
		$row_rs_direca = $obBD_con1->getRowConsulta(306, $np, $obBD_conexion); ?>                
			    <td width="114">
                <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
<button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="this.form.submit()">
                    <i class=" icon-arrow-left icon-white"></i>
                    <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       </button>
       <input type="hidden" id="codpla" name="codpla" value="<?php echo $codpla; ?>" />
       <input type="hidden" id="np" name="np" value="<?php echo $row_rs_direca['Pld_Rec']; ?>" />
       </form>
        </td>
        <?Php
		} //Fin del if ($np!=0) ?>        
      </tr>
    </table>           
	</FIELDSET>
		<?Php
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>      
	<?php } ?>	
    </td>
  </tr>
</table>	  
</div>
<script type="text/javascript" src="../VALIDACIONES/con_par_planc.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php
$obBD_conexion->cerrar();
?>