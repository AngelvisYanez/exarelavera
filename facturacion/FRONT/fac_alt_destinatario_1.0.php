<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
 * Permite registrar un nuevo Transportista Facturacion electronica
 * 
 * @author Jose Cumbicos
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-16
 * 
 * @package tesoreria.FRONT
 */	  
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_destinatario.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
* objeto para la conexion
* @var Class_Log_Conexion_Tes
*/
$obBD_conexion = new Class_Log_Conexion_Des($Ses_Dat_Dis);

/**
* objeto para consultas
* @var Class_Log_Datos_Tes
*/
$obBD_con1 =  new Class_Log_Datos_Des;

/**
* Llamado de la libreria para evitar el reenvio de datos
* @var Post_Block
*/
$thisPost = new Post_Block;
	  
if(isset($_POST['hdd_volver']))
{
unset($_POST['hdd_save']);
}
	  	  
if (isset($_POST['hdd_save']))
{
	if ($thisPost->postBlock($_POST['postID']))
	{
		/**
		 * valores de cliente a guardar
		 * @var string
		 */
		 //$_POST['Zon_Cod'].
		$Param_transporte = $Ses_Emp_Cod.'*'.$_POST['Des_Sri'].'*'.$_POST['Des_Adu'];
		
		if($_POST['event'] == 1)
		{
			/**
			 * Valores de la persona a guardar
			 * @var string
			 */
			$Param_persona = $_POST['Prs_Ced'].'*'.$_POST['Prs_Nom'].'*'.$_POST['Prs_Ape'].'*'.$_POST['Prs_Dir'].'*'.$_POST['Ide_Cod'].'*'.$_POST['Prs_Cor'];
			   
			/**
			 * guardado en persona transporte
			 */
			$obBD_con1->InsetPersonaDestinatario(7, $Param_persona, 8, $Param_transporte);
		}
		else
		{
			/**
			 * guardado de transporte
			 */
			$obBD_con1->insertUpdateDelete(8, $_POST['Prs_Cod'].'*'.$Ses_Emp_Cod.'*'.$_POST['Des_Sri'].'*'.$_POST['Des_Adu'], $obBD_conexion);
		}
	
		unset($_POST['event']);
		unset($_POST['Prs_Ced']);
		unset($_POST['hdd_save']);
	
		$event = -1;
	}
}
	  	  
/**
 * Comprobar la existencia de persona - cliente
 */
if(isset($_POST['hdd_comprobar']))
{  
	/**
	 * Obtiene cadena concatenada la cedula y ruc
	 * @var string
	 */
	$str = $obBD_con1->convertCedulaRuc($_POST['Prs_Ced']);
	
	/**
	 * 0: proveedor ya registrado
	 * 1: persona ya registrado
	 * 2; persona y proveedor sin registar
	 * @var int
	*/
	$event = $obBD_con1->ComprovarExistencia(1, 2, $str, $Ses_Emp_Cod, $obBD_conexion);	
	
	if($event == 2)
	{
	   /**
		* Fila de datos de la persona encontrada
		* @var array
		*/
	   $row_rs_persona = $obBD_con1->getRowConsulta(3, $str,$obBD_conexion);
	   
	   unset($Prs_Ced1,$Prs_Ruc);
	}
}
else
{
	/**
	 * Valor que toma por defecto al iniciar la p�gina
	 */
	$event = -1;
}	
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/fac_val_transporte.js"></script>
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
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
    <td>&raquo; registrar Destinatario </td>
  </tr> 
  <tr height="400">
    <td valign="top">
 <?php 
	if(!($event > 0) or isset($_POST['hdd_volver']))
	{
	?>   
     <form method="post" name= "form" action="<?php echo $_SERVER['PHP_SELF'];?>">        
      <fieldset>
      <legend>
      <label class="Titulos2">Comprobar registro</label>
      </legend>
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
         <tr>
          <td width="18%"  align="right" class="LetraNegra">
                    &nbsp;&nbsp;&nbsp;Nacional&nbsp;&nbsp;
                    <input name="opn" type="radio" value="0" 
                    onClick="document.getElementById('opiden').value='N'; setfocus(this.form.Prs_Ced)" checked="checked">
                  </td> 
          <td width="82%" class="LetraNegra">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Extranjero&nbsp;&nbsp;
                    <input name="opn" type="radio" value="0" 
                    onClick="document.getElementById('opiden').value='J'; setfocus(this.form.Prs_Ced)"/>
                  </td>
         </tr>
      </table>
      <input name="opiden" type="hidden" id="opiden" value="N"/>
      
        <table width="905"  border="0" cellpadding="0" cellspacing="0">
         <tr>
          <td width="142" align="right"  class="Etiqueta1">
           <span class="Asterisco">*</span> 
            Buscar c&eacute;dula/R.U.C.:
           </td>
          <td width="158"  class="LetraNegra">
                 	<input name="Prs_Ced" type="text" id="Prs_Ced" 
                    onBlur="if(document.getElementById('opiden').value == 'N'){ validarDocumento(this.form.Prs_Ced)}" 
                    value="<?Php if(isset($_POST['Prs_Ced']))echo htmlspecialchars($_POST['Prs_Ced'], ENT_QUOTES, 'UTF-8'); ?>" size="17" maxlength="13">
                 </td>
          <td width="605" align="left"> 
          <button type="button" class="btn btn-success fileinput-button" title="Comprobar" onclick="validar_requeridos(this.form, 'Prs_Ced', 0)">
                    <i class="icon-refresh icon-white"></i>
                    <span>Comprobar</span>
                </button>
           <span class="Texto_Reporte_Rojo">&nbsp;&nbsp;&nbsp;&nbsp;
            <?Php
		   /**
		    * Funcion que evitar en submit con el enter
		    */
		   noEnterSubmit();		
		   if (isset($_POST['hdd_comprobar']))
		   {
		    	if ($event == 0) 
		  		{ 
		  			echo "El Destinatario con c&eacute;dula/R.U.C.: ".$_POST['Prs_Ced'].", ya se encuentra registrado";
				}	   
		   }
		   ?>
           </span> 
          </td>
         </tr>
        </table>
       </fieldset>
      <input name="hdd_comprobar" type="hidden" id="hdd_comprobar" value="insertar"/>
     </form>
   <?php } 
  if($event > 0 && !isset($_POST['hdd_volver']))
  {
  ?>
<FIELDSET>
<LEGEND>
<label class='Titulos2'>Datos a registrar</label>
</LEGEND>
	<form method="post" name="form" action="<?php echo $_SERVER['PHP_SELF'];?>">
	<?php $thisPost->startPost();?>
    <input type="hidden" value="<?php echo $event;?>" name="event" id="event" >
    <?Php echo mensaje_requerido(); ?>
	<FIELDSET>
	<LEGEND>
	<label class='Titulos2'>Datos del Destinatario</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="2" cellspacing="0">
	  <tr>
        <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula/R.U.C.:</td>
	    <td width="83%" class="LetraNegra">&nbsp;
			<?Php echo htmlspecialchars($_POST['Prs_Ced'], ENT_QUOTES, 'UTF-8'); ?>
			<input name="Prs_Ced" type="hidden" id="Prs_Ced" value="<?php echo $_POST['Prs_Ced']; ?>">
		</td>
	    </tr>
	  <tr>
	    <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> Tipo de documento:</td>
	    <td class="LetraNegra">&nbsp;
	      <?php
			/**
			 * Total de caracteres
			 * @var int
			 */
			$Ide_Max = strlen($_POST['Prs_Ced']);
			$Ide_Max = ($Ide_Max != 10 && $Ide_Max != 13)?-2:$Ide_Max;
			/**
			 * valores de identificacion del documento ingresado
			 * @var array
			 */
			$Identifica = $obBD_con1->getRowConsulta(4,$Ide_Max,$obBD_conexion);
		?>
	      <input type="hidden" id="Ide_Cod" name="Ide_Cod" value="<?php echo $Identifica['Ide_Cod'];?>">
	      <?php echo $Identifica['Ide_Des'];?>			
	      </td>
	    </tr>        
       <tr>
         <td class="Etiqueta1">Codigo Aduana:</td>
         <td  class="LetraNegra"><?Php 
			if(isset($row_rs_persona))
			{
				if ($row_rs_persona['Des_Adu']!='')
				{
					echo $row_rs_persona['Des_Adu'];
				}else{
				?><input name="Des_Adu" id="Des_Adu" type="text" size="15" maxlength="20" style="text-transform:uppercase" value="" /><?php
				}
			} 
			else
			{
		?>
              <input name="Des_Adu" id="Des_Adu" type="text" size="15" maxlength="20" style="text-transform:uppercase" value="" />
         <?php }?>
           </td>
       </tr>
       <tr>
         <td class="Etiqueta1">C&oacute;digo Establecimiento(SRI):</td>
         <td  class="LetraNegra"><?Php 
			if(isset($row_rs_persona))
			{				
				if ($row_rs_persona['Des_Sri']!='')
				{
					echo $row_rs_persona['Des_Sri'];
				}else{
				?><input name="Des_Sri" id="Des_Sri" type="text" size="15" maxlength="3" style="text-transform:uppercase" value="" /><?php
				}
			} 
			else 
			{
		?>
           <input name="Des_Sri" id="Des_Sri" type="text" size="15" maxlength="3" style="text-transform:uppercase" value="" />
         <?php }?>
           </td>
       </tr>
       <tr id="Natural">
         <td class="Etiqueta1">        
           <span class="Asterisco">*</span> 
           Nombres:        
           </td>
         <td  class="LetraNegra"><?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Nom'];
			} 
			else
			{?><input name="Prs_Nom" id="Prs_Nom" type="text" size="66" maxlength="50" style="text-transform:uppercase" value=""><?php }?>
           </td>
       </tr>
      <tr>
       <td class="Etiqueta1">        
         <span class="Asterisco">*</span> 
         <label id="Natural_a">Apellidos</label>
         / Razon Social
         <label id="Juridico">:</label>        
       </td>
       <td  class="LetraNegra"><?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Ape'];
			} 
			else
			{
			?><input name="Prs_Ape" id="Prs_Ape" type="text" style="text-transform:uppercase" value="" size="66" maxlength="50" ><?php }?>
       </td>
      </tr>
	 </table>
<table width="100%" border="0" cellpadding="2" cellspacing="0" id="detalle">  
    <tr>
      <td class="Etiqueta1"><span class="Asterisco">*</span> Correo Electr&oacute;nico:</td>
      <td class="LetraNegra"><?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Cor'];
			} 
			else 
			{?><input name="Prs_Cor" type="text" id="Prs_Cor" value="" size="66" maxlength="60" ><?php }?>
        </td>
    </tr>
      <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n:</td>
    <td width="83%" class="LetraNegra"><?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Dir'];
			} 
			else 
			{
			?><input name="Prs_Dir" type="text" style="text-transform:uppercase" id="Prs_Dir" value="" size="66" maxlength="60" ><?php }?>
      </td>
  </tr>
      </table>
    <script type="text/javascript">
    MostrarNJ(this);
    </script>
    </FIELDSET>
	<br>   
	<?php 
		if(isset($row_rs_persona))
		{
	?>
			<input type="hidden" name="Prs_Cod" value="<?php echo $row_rs_persona['Prs_Cod']?>">
	<?php 
		}
	?>
    <table width="300" border="0" cellpadding="0" cellspacing="0">       
       <tr> 
      	<td width="110">
      		 <button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="campos_hide(this.form, 'hdd_volver', '<?Php echo '1'; ?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
      	</td>
        <td width="190">
          <button type="button" class="btn btn-primary start" title="Guardar" onclick="<?php 
          if(!isset($row_rs_persona))
          {
          	echo "validar_persona(this.form);";
          }
          else
          {
          	echo "confirmacion(this.form);";
          }
          ?>">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
           </button>
          </td>
      </tr>
    </table>
	  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
    </form>
</FIELDSET>	
  <?php 
  }?>
  </td>
  </tr>
  </table>
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_transporte.js"></script>
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