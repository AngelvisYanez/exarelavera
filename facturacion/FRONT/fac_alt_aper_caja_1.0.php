<?php 
/**
 * Descripción: Alta de cajas para facturación
 * Fecha de actualización:	2012-03-19 
 * Desarrollador:	Jose Cumbicos
 * Fecha de actualización: 2011-06-09
 * Desarrollador: Nebil Oyola
 * Fecha de actualización: 2012-02-17
 * Desarrollador: lewis.chimarro
 * Fecha de actualización: 2014-06-01
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_aper_caja.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	

/**
* Creación del objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creacin del objeto mysql para las consultas ´
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	  
/**
* Evitar el reenvio de datos 
*/
$thisPost = new Post_Block;
$mes = date("m");
$hoy = date("Y-m-d");

if (isset($ajax_caja))
{
	$rs_consultar = $obBD_con1->getArrayConsulta(15, $Caj_Fec.'*'.$Pun_Cod, $obBD_conexion);
	$total_rs_consultar = count($rs_consultar);
	
	if ($total_rs_consultar > 0)
	{ 
	?><font color="#FF0000">La caja fue abierta</font><?Php	
	}
	exit();
}

/**
* Control para el registro de nuevas cajas
*/
if (isset($ajax_new))
{
if ($op==1)	//Edicion de la Apertura de Caja			
{
	/**
	* Consulta de la caja selecionada 
	*/
	$row_rs_caja_activa = $obBD_con1->getRowConsulta(4, $codigo, $obBD_conexion);	
?>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" id="form1">
<?Php 
	/**
	* Creación del campo repost 
	*/
	$thisPost->startPost();  ?>
  <input type="hidden" name="hiddenField" value="<?PHP echo $row_rs_vendedor['Pun_Cod']; ?>">
  <FIELDSET>
  <br>
    <LEGEND>
          <label class="Titulos2">Datos a registrar</label>
    </LEGEND>
	<?php mensaje_requerido() ?>
		  <table width="500" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">			 
			 <tr>
			   <td width="127" class="Etiqueta1">Fecha de apertura: </td>
			   <td width="364">&nbsp;<?Php echo $row_rs_caja_activa['Caj_Fec']; ?></td>
		      </tr>
			 <tr>
			   <td class="Etiqueta1">Hora inicio:</td>
			   <td>&nbsp;<?Php echo $row_rs_caja_activa['Caj_Hoi']; ?></td>
		      </tr>
			 <tr>
			   <td class="Etiqueta1">Estado:</td>
			   <td>&nbsp;<img src="../../mascaras/model1/imagenes/32x32/decrypted2.png" width="24" height="24" border="0" title="<?php echo $row_rs_caja_activa['Caj_Est']; ?>" />
			     	     
		          <input name="Caj_Fef" type="hidden" id="Caj_Fef" value="<?php echo date("Y-m-d"); ?>" />
	            </td>
		     </tr>			
             <tr>
              <td class="Etiqueta1">Observaciones:</td>
              <td>
               &nbsp;
               <textarea name="Caj_Obs" id="Caj_Obs" cols="40" rows="4"></textarea>                   
			  </td>
              </tr>
        </table>
	      <input name="codigo" type="hidden" id="codigo" value="<?php echo $codigo; ?>">
        </FIELDSET>
		<br>
	  <?Php 
      if ($hoy >= $fecha)
      {
      ?>
	      <table width="118" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="100%" height="23">                 
				<button type="button" class="btn btn-primary fileinput-button" title="Guardar" onClick="confirmacion(this.form)" >
			           			<i class="icon-book icon-white"></i>
			           			<span>Guardar</span>
			           		</button>                  
                  <input name="hdd_save1" type="hidden" id="hdd_save1" value="insertar">
			  </td>
            </tr>
          </table> 
              <?Php
			  }
			  else
			  { echo error_alerta (" ¡No se puede cerrar la Caja del día $fecha, porque la fecha actual es $hoy!", 2); }
			  ?>          
  	</form>   
	<?Php 
    } //Fin del if (isset($codigo))

exit();
}//Fin ajax_new

/**
* Evitar el reenvio de formularios 
*/
if ($thisPost->postBlock($_POST['postID']))
{			
	if (isset($hdd_save1)) //cierre de caja				
	{		
		$Caj_Hof = date("H:i:s"); 
		/*Inicio de la transaccion*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		$obBD_con1->grabarv_registros(sentencias_tes(7, $obBD_con1->parametros($Caj_Exi.'*'.'C'.'*'.$Caj_Obs.'*'.$codigo.'*'.$Caj_Hof.'*'.$Caj_Fef)), $obBD_conexion->conexion);		
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);	
	}//fin del if (isset($hdd_save1))

	if (isset($hdd_save2)) //apertura de caja
		{	
			$rs_consultar = $obBD_con1->consulta(sentencias_tes(15, $obBD_con1->parametros($Caj_Fec.'*'.$Pun_Cod)), 
									$obBD_conexion->conexion);
			$total_rs_consultar = $obBD_con1->numregistros();
		
			if ($total_rs_consultar == 0)
			{ 
				/*Inicio de la transaccion*/
				$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
				$Caj_Hoi = date("H:i:s"); 
				/* Inserción de la apertura de caja */
				$obBD_con1->grabarv_registros(sentencias_tes(6, $obBD_con1->parametros($Caj_Fec.'*'.$Caj_Hoi.'*'.$Pun_Cod.'*'.$Caj_Exi)), $obBD_conexion->conexion);	
				$obBD_con1->fin_transaccion($obBD_conexion->conexion);	
				unset ($op);
			}//Fin del if ($total_rs_consultar == 0)
			else
			{ ?>
				<script LANGUAGE="JavaScript">	
					alert ("¡No se ha podido guardar la Caja porque ya ha sido abierta!");
				</script>
		<?Php				
			}//Fin del else if ($total_rs_consultar == 0)
		}//Fin del if (isset($hdd_save2))
}//Fin del if ($thisPost->postBlock($_POST['postID']))

/**
* Consulta del vendedor en base al codigo de la persona
*/
$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
$total_rs_vendedor = count($row_rs_vendedor);

/**
* Consulta de la caja activa en base al vendedor
*/
$rs_caja = $obBD_con1->getArrayConsulta(5, $row_rs_vendedor['Pun_Cod'].'*'.$mes, $obBD_conexion);
$open = $rs_caja[0]['Caj_Est2'];
$total_rs_caja = count($rs_caja);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>	        
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    	<script language="javascript" src="../VALIDACIONES/fac_val_aper_caja.js"></script>  
		<link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/jquery.js"></script>
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>              
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script> 
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
        <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <script>
		$(function() { 
			/*$( "#Caj_Fec" ).datepicker({ showOn: "button", buttonImage: "../../Librerias/datapicker/css/images/calendar.gif", buttonImageOnly: true, }); */
		$( "#Caj_Fec" ).datepicker({ altField: "#Caj_Fec", altFormat: "yy-mm-dd" });			
			$( "#Caj_Fec" ).datepicker( "option", "showAnim", "show" ); $.datepicker.setDefaults( $.datepicker.regional[ "es" ] ); 
			/*$( "#Caj_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );	*/		
		}); 
        </script>
	    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">    
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10"><table width="100%" border="0" align="left" cellpadding="0" cellspacing="0">
        <tr class="BarraTitulo">
         <td width="45%">&raquo; Abrir / cerrar caja </td>
         <td width="36%"><strong>PUNTO DE IMPRESION:</strong> <?Php echo $row_rs_vendedor['Pun_Des']; ?></td>
        </tr>
      </table></td>
  </tr>
	<tr>
        <td height="389" valign="top">          
            <br>
			 
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Historial de cajas</label>
            </LEGEND>
            <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" id="form1">
              <table border="1" cellpadding="0" cellspacing="0" class="fixedHeader01" width="100%">
              <thead>
                <tr>
                  <th width="4%" align="center">C&oacute;d. Int.</th>
                  <th width="10%" align="center">Fecha de Apertura </th>
                  <th width="10%">Hora Inicio </th>
                  <th width="10%">Fecha de Cierre</th>
                  <th width="4%">Estado</th>
                  <th width="2%">&nbsp;</th>
                </tr>
               </thead>
               <tbody>
                <?Php 
			if ($total_rs_caja >0)	
			{
				foreach($rs_caja as $row_rs_caja) { 
				?>
                <tr>
                  <td align="center"><?Php echo $row_rs_caja['Caj_Cod']; ?></td>
                  <td align="center"><?Php echo $row_rs_caja['Caj_Fec']; ?>&nbsp;</td>
                  <td align="center"><?Php echo $row_rs_caja['Caj_Hoi'];?>&nbsp;</td>
                  <td align="center"><?Php echo $row_rs_caja['Caj_Fef']; ?></td>
                  <td align="center"><?Php if ($row_rs_caja['Caj_Est2']=='A'){ ?>
                  	<img src="../../mascaras/model1/imagenes/32x32/decrypted2.png" width="24" height="24" border="0" title="<?php echo $row_rs_caja['Caj_Est']; ?>">
                    <?Php
				  }else{ ?>
					  <img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="22" height="22" border="0" title="<?php echo $row_rs_caja['Caj_Est']; ?>">
                   <?Php
				  } ?>
                  
                  &nbsp;</td>
                  <td align="center"><?Php if ($row_rs_caja['Caj_Est2']=='A'){ ?>                    
					<button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="Muestra_Aparecer();ajax_datos('<?php echo $_SERVER['PHP_SELF']?>?ajax_new=1&op=1&fecha=<?Php echo $row_rs_caja['Caj_Fec']; ?>&codigo=<?Php echo $row_rs_caja['Caj_Cod']; ?>','ajax_contenido');"><i class=" icon-arrow-right icon-white"></i></button> 
                    <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_caja['Caj_Cod']; ?>" />
                    <input name="op" id="op" type="hidden" value="1" />
         <input name="fecha" id="fecha" type="hidden" value="<?Php echo $row_rs_caja['Caj_Fec']; ?>" />            
                    
				<?Php } ?></td>
                </tr>
               <?Php } //Fin del foreach $row_rs_caja 
			}else{			   
			   ?>
                <tr>
                  <td align="center">&nbsp;</td>
                  <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
                  <td align="center">&nbsp;</td>
                  <td align="center">&nbsp;</td>
                  <td align="center">&nbsp;</td>
                  <td align="center">&nbsp;</td>
                </tr> 
            <?Php
			}//Fin del if ($total_rs_caja >0)
			?>              
               </tbody>
              </table>
              </form>
            </FIELDSET>			
<?Php echo barra_estado($total_rs_caja);?>

<?php
if ($open == 'C' or $total_rs_caja==0)
{
?>
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" id="form1">
	  <br>	
	<table width="155" border="0" cellpadding="0" cellspacing="0">
      <tr>
		 <td width="149" scope="col">
		<button type="button" name="button1" class="btn btn-success fileinput-button" title="Abrir Caja" onClick="Muestra_Aparecer();"><i class="icon-plus-sign icon-white"></i><span>&nbsp;Agregar&nbsp;</span></button>
		<input name="Pun_Cod" type="hidden" id="Pun_Cod" value="<?php echo $row_rs_vendedor['Pun_Cod']; ?>">
        <input name="op" type="hidden" id="op" value="2">
		    </td>
		 </tr>
	</table><br />
	</form>
<?Php
} //Fin del if ($total_rs_caja == 0)			
?>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">
<div id="ajax_contenido">
<?php  /**
	 * Apertura de la nueva Caja
	 */
		 
	?>	
		<form method="post" name= "form2" action="<?php echo $_POST['form1'];?>">
			 <FIELDSET>
			  <LEGEND>
				<label class="Titulos2">Datos a registrar</label>
			 </LEGEND>
			 <?Php echo mensaje_requerido(); ?>
			  <table width="700" border="0" cellpadding="0" cellspacing="0">
				  <tr>
					 <td width="144" class="Etiqueta1"><span class="Asterisco">*</span>Fecha de apertura:
					 </td>
					 <td width="100">&nbsp;
				<input name="Caj_Fec" id="Caj_Fec" type="text" value="" size="10" maxlength="10" onKeyUp="mascara(this,'-',patron,true)" onChange="validar_fecha2(this); ajax_datos('<?php echo $_SERVER['PHP_SELF']?>?ajax_caja&Pun_Cod=<?php echo $row_rs_vendedor['Pun_Cod']; ?>&Caj_Fec='+this.value, 'div_caja');">  
					</td>      
					 <td width="326"><div id="div_caja">&nbsp;</div></td>
				  </tr>
                  <tr>
                    <td class="Etiqueta1">Monto Inicial:</td>
                    <td>&nbsp;&nbsp;<input name="Caj_Exi" size="10" maxlength="10" type="text" id="Caj_Exi" value="0.00" /></td>
                    <td width="326">&nbsp;</td>
                  </tr>
			</table>
			</FIELDSET>
				  <br>
				  <table width="100" border="0" cellpadding="0" cellspacing="0">
					<tr>
					  <td width="100%" height="23">
					<button type="button" class="btn btn-primary fileinput-button" title="Guardar" id="btn_abrir" onClick="validar_requeridos(this.form, 'Caj_Fec', 1)" >
									<i class="icon-book icon-white"></i>
									<span>Guardar</span>
								</button>                                    
					  <input name="hdd_save2" type="hidden" id="hdd_save2" value="insertar">
					  <input name="Pun_Cod" type="hidden" id="Pun_Cod" value="<?php echo $row_rs_vendedor['Pun_Cod']; ?>">
					  </td>
					</tr>
				  </table>
			  </form> 
			

</div>
</div>			 
		</td>
    </tr>
</table>	
</div> 
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_aper_caja.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	    
</BODY>
</HTML>
<?Php
/**
* Cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/**
* fin cierre las conexiones 
*/
?>
