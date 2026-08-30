<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php
/**
 * Permite registrar un nuevo Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 * 
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualizaci�n:	2012-04-16
 * 
 * @package tesoreria.FRONT
 */
	  
	  require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../LOGICA/adq_log_proveedor.php');	  
   	  require_once('../../Librerias/procedimientos/almacenados_standar.php');
	  require_once('../../Librerias/postclass.php');
	  	  
	  /**
	   * objeto para la conexion
	   * @var Class_Log_Conexion_Tes
	   */
	  $obBD_conexion = new Class_Log_Conexion_Prv($Ses_Dat_Dis);
	  
	  /**
	   * objeto para consultas
	   * @var Class_Log_Datos_Tes
	   */
      $obBD_con1 =  new Class_Log_Datos_Prv;
	  
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
	  		 * valores de proveedor a guardar
	  		 * @var string
	  		 */
	  		$Param_Proveedor = strtoupper($_POST['Prv_Com']).'*'.$_POST['Prv_Tic'].'*'.$_POST['Prv_Con'].'*'.strtoupper($_POST['Prv_Nge']).'*'.strtoupper($_POST['Prv_Apg']).'*'.$_POST['Prv_Tlg'].'*'.$_POST['Prv_Ceg'].'*'.$_POST['Prv_Cog'].'*'.strtoupper($_POST['Prv_Ace']).'*'.$_POST['Prv_Fin'].'*'.$_POST['Prv_Fce'].'*'.$_POST['Prv_Fre'].'*'.$_POST['Prv_Fac'].'*'.strtoupper($_POST['Prv_Act']).'*'.strtoupper($_POST['Prv_Nct']).'*'.$_POST['Prv_Ect'].'*'.$_POST['Prv_Fax'].'*'.$_POST['Prv_Esp'].'*'.$Ses_Emp_Cod.'*'.$_POST['Prv_Rep'];
	  
	  		if($_POST['event'] == 1)
	  		{
	  			/**
	  			 * Valores de la persona a guardar
	  			 * @var string
	  			 */
	  			$Param_Persona = $_POST['Prs_Ced'].'*'.$_POST['Ide_Cod'].'*'.strtoupper($_POST['Prs_Nom']).'*'.strtoupper($_POST['Prs_Ape']).'*'.$_POST['Ciu_Cod'].'*'.strtoupper($_POST['Prs_Dir']).'*'.$_POST['Prs_Tel'].'*'.$_POST['Prs_Te2'].'*'.$_POST['Prs_Cel'].'*'.$_POST['Prs_Cor'];
	  				
	  			/**
	  			 * guardado en persona cliente
	  			 */
	  			$obBD_con1->InsetPersonaProveedor(5, $Param_Persona, 4, $Param_Proveedor, $_SERVER['PHP_SELF']);
	  		}
	  		else
	  		{
				/**
				* Inicio de la transaccion
				*/
				$obBD_con1->inicio_transaccion($obBD_conexion->conexion);					
	  			/**
	  			 * guardado de cliente
	  			 */
	  			$obBD_con1->operacionobBD(4, $_POST['Prs_Cod'].'*'.$Param_Proveedor, $obBD_conexion);
				//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
				/**
				* Finaliza la transacci�n
				*/
				$obBD_con1->fin_transaccion($obBD_conexion->conexion);
				
	  		}
	  
	  		unset($_POST['event']);
	  		unset($_POST['Prs_Ced']);
	  		unset($_POST['hdd_save']);
	  
	  		$event = -1;
	  	}
	  }
	  
	  
	/**
	 * Comprobar la existencia de persona - proveedor
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
		   $row_rs_persona = $obBD_con1->getRowConsulta(1, $str,$obBD_conexion);
		   
		   unset($str);
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
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/adq_val_proveedor.js"></script>
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
			/* Campo 1 */
			$( "#Prv_Fin" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});

			/* Campo 2 */
			$( "#Prv_Fce" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});

			/* Campo 3 */
			$( "#Prv_Fre" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});

			/* Campo 4 */
			$( "#Prv_Fac" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});
		}); 		
        </script> 
        
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
   <tr class="BarraTitulo">
    <td height="10">&raquo; registrar proveedor 
    </td>
   </tr> 
 <?php 
	if(!($event > 0) or isset($_POST['hdd_volver']))
	{
	?>
    <tr>
       <td><?Php echo mensaje_requerido(); ?></td>
    </tr>
   <tr>
    <td height="400" valign="top">
     <form method="post" name= "form" action="<?php echo $_SERVER['PHP_SELF'];?>">        
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
         <tr>
          <td width="11%"  align="right" class="LetraNegra">
                    &nbsp;&nbsp;&nbsp;Nacional&nbsp;&nbsp;
                    <input name="opn" type="radio" value="0" 
                    onClick="document.getElementById('opiden').value='N'; setfocus(this.form.Prs_Ced)" checked="checked">
                  </td> 
          <td width="89%" class="LetraNegra">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Extranjero&nbsp;&nbsp;
                    <input name="opn" type="radio" value="0" 
                    onClick="document.getElementById('opiden').value='J'; setfocus(this.form.Prs_Ced)"/>
                  </td>
         </tr>
        </table>
      <input name="opiden" type="hidden" id="opiden" value="N"/>
      <fieldset>
        <legend>
         <label class="Titulos2">Comprobar registro</label>
        </legend>
        <table width="100%"  border="0" cellpadding="0" cellspacing="0">
         <tr height="20">
          <td width="11%" align="right"  class="Etiqueta1">
           <span class="Asterisco">*</span> 
            R.U.C:
           </td>
          <td width="15%"  class="LetraNegra">
                 	<input name="Prs_Ced" type="text" id="Prs_Ced" 
                    onBlur="if(document.getElementById('opiden').value == 'N'){ validarDocumento(this.form.Prs_Ced)}" 
                    value="<?Php if(isset($_POST['Prs_Ced']))echo htmlspecialchars($_POST['Prs_Ced'], ENT_QUOTES, 'UTF-8'); ?>" size="17" maxlength="13">
                 </td>
          <td width="74%" align="left">
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
		  			echo "El proveedor con c&eacute;dula/R.U.C.: ".$_POST['Prs_Ced'].", ya se encuentra registrado";
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
    </td>
   </tr> 
   <?php } ?>
<tr>
<td>
<?php 
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
  <table width="100%" border="0">
  <tr>
       <td><?Php echo mensaje_requerido(); ?></td>
  </tr>
  </table>
  
  <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Datos Generales</label>
	 </LEGEND>
	 <table width="100%"  border="0" cellpadding="2" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
         <span class="Asterisco">*</span> 
         R.U.C.:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <?Php echo htmlspecialchars($_POST['Prs_Ced'], ENT_QUOTES, 'UTF-8'); ?>
			<input name="Prs_Ced" type="hidden" id="Prs_Ced" value="<?php echo $_POST['Prs_Ced']; ?>">
       </td>       
      </tr>
      
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo de documento:</td>
	    <td class="LetraNegra">&nbsp;
		<?php
			/**
			 * Total de caracteres
			 * @var int
			 */
			$Ide_Max = strlen($_POST['Prs_Ced']);
			$Ide_Max = ($Ide_Max != 10 && $Ide_Max != 13)?-1:$Ide_Max;
			/**
			 * valores de identificacion del documento ingresado
			 * @var array
			 */
			$Identifica = $obBD_con1->getRowConsulta(6,$Ide_Max,$obBD_conexion);
		?>
		<input type="hidden" id="Ide_Cod" name="Ide_Cod" value="<?php echo $Identifica['Ide_Cod'];?>">
			<?php echo $Identifica['Ide_Des'];?>			
		</td>
	    </tr>
	    
	    <tr>
	   <td class="Etiqueta1">
        
        <span class="Asterisco">*</span>
        Tipo de Contribuyente:
        
       </td>
       <td class="LetraNegra">&nbsp;
         <Select name="Prv_Tic" id="Prv_Tic" onChange="MostrarNJ(this)">
          <option value = "N" >NATURAL</option>
          <option value = "J" >JURIDICO</option>
         </Select>
       </td>
      </tr>
         <tr id="Natural">
       <td class="Etiqueta1">
        
         <span class="Asterisco">*</span> 
         Nombre (Raz&oacute;n Social): </td>
       <td  class="LetraNegra">&nbsp;
        <?Php 
	       			if(isset($row_rs_persona))
	       			{
	       				echo $row_rs_persona['Prs_Nom'];
	       			} 
	       			else
	       			{
       			?> 
       					<input name="Prs_Nom" id="Prs_Nom" type="text" size="66" maxlength="50" style="text-transform:uppercase" value="">
       			<?php 
       				}
       			?>
       </td>
      </tr>
      <tr>
       <td class="Etiqueta1">
        
         <span class="Asterisco">*</span> 
         <label id="Natural_a">Apellido (Raz&oacute;n Social):</label>
         <label id="Juridico">Raz&oacute;n Social:</label>
        
       </td>
       <td  class="LetraNegra">&nbsp;
		<?Php 
        			if(isset($row_rs_persona))
        			{
        				echo $row_rs_persona['Prs_Ape'];
        			} 
        			else
        			{
        		?>  
        			<input name="Prs_Ape" id="Prs_Ape" type="text" style="text-transform:uppercase" value="" size="66" maxlength="50" >
        		<?php 
        			}
        		?>
       </td>
      </tr>
        
      <tr id="sexo">
        <td class="Etiqueta1"><span class="Asterisco">*</span> Genero: </td>
        <td class="LetraNegra">&nbsp;
		<?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['sexo'];
			}
			 else 
			{
		?>
				<select name="Prs_Sex" id="Prs_Sex">
            		<option value="M">MASCULINO</option>
            		<option value="F">FEMENINO</option>
        		</select>
        <?php 
			} 
		?>
        </td>
        
      </tr>
	  
	  <tr>
	   <td class="Etiqueta1">
        
         <span class="Asterisco">*</span> 
         Nombre Comercial:
        
       </td>
       <td class="LetraNegra">&nbsp;
        
	     <input name="Prv_Com" type="text" id="Prv_Com" 
         style="text-transform:uppercase" value="" size="66" maxlength="100"/>
        
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
        
        <span class="Asterisco">*</span>
        Contribuyente Especial:
       
       </td>
       <td class="LetraNegra">&nbsp;
        
	     <Select name="Prv_Esp" id="Prv_Esp">
          <option value = "">Seleccionar...</option>
          <option value = "S">SI</option>
          <option value = "N">NO</option>
         </Select>
        
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
       
        <span class="Asterisco">*</span>
        Obligado a llevar Contabilidad:
        
       </td>
       <td class="LetraNegra">&nbsp;
        
	     <Select name="Prv_Con" id="Prv_Con">
         <option value = "">Seleccionar...</option>
          <option value = "S">SI</option>
          <option value = "N">NO</option>
         </Select>
       
       </td>
      </tr>
      <tr>
        <td class="Etiqueta1">Representante Legal: </td>
        <td class="LetraNegra">&nbsp;<input name="Prv_Rep" type="text" id="Prv_Rep" 
         style="text-transform:uppercase" value="" size="66" maxlength="100"/></td>
      </tr>
	  
     </table>
     <script type="text/javascript">
    MostrarNJ(this);
    </script>
  </FIELDSET>
  
  <fieldset>
     <legend>
       <label class="Titulos2">Datos del Gerente</label>
     </legend>
     <table width="100%"  border="0" cellpadding="2" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
         Nombre Gerente:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
       <input name="Prv_Nge" type="text" id="Prv_Nge" style="text-transform:uppercase" value="" size="30" maxlength="80"/>
       </td>       
      </tr>
      <tr>
       <td class="Etiqueta1">
         Apellido Gerente:
        
       </td>
       <td class="LetraNegra">&nbsp;
       <input name="Prv_Apg" type="text" id="Prv_Apg" style="text-transform:uppercase" value="" 
         size="30" maxlength="80"/>
       </td>       
      </tr>
      <tr>
       <td class="Etiqueta1">
         Tel&eacute;fono Gerente:
       </td>
       <td class="LetraNegra">&nbsp;
       <input name="Prv_Tlg" type="text" id="Prv_Tlg" style="text-transform:uppercase" value="" 
         size="15" maxlength="15" onBlur="numerico(this)"/>
       </td>       
      </tr>
      <tr>
       <td class="Etiqueta1">
         Celular Gerente:
        
       </td>
       <td class="LetraNegra">&nbsp;
       <input name="Prv_Ceg" type="text" id="Prv_Ceg" style="text-transform:uppercase" value="" 
         size="15" maxlength="15" onBlur="numerico(this)"/>
       </td>       
      </tr>
      <tr>
       <td class="Etiqueta1">
         E-mail Gerente:
       </td>
       <td class="LetraNegra">&nbsp;
       <input name="Prv_Cog" type="text" id="Prv_Cog" value="" 
         size="30" maxlength="80" onblur="correo(this);">
       </td>       
      </tr>
     </table>
    </fieldset>	 
    
    <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">SRI: Actividad Econ&oacute;mica</label>
	 </LEGEND>     
     <table width="100%"  border="0" cellpadding="2" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1" >
        Actividad Econ&oacute;mica:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
       <textarea id="Prv_Ace" name="Prv_Ace" cols="40" style="text-transform:uppercase"></textarea>
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
         <span class="Asterisco">*</span> 
         Fecha Inicio de Actividades:
       </td>
       <td class="LetraNegra">&nbsp;
	     <input name="Prv_Fin" type="text" id="Prv_Fin" value="<?php echo date("Y-m-d"); ?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
        Fecha Cese de Actividades:
       </td>
       <td class="LetraNegra">&nbsp;
	     <input name="Prv_Fce" type="text" id="Prv_Fce" value="" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
       </td>
      </tr>
      <tr>
       <td class="Etiqueta1">
        Fecha de Reinicio Actividades:
       </td>
       <td class="LetraNegra">&nbsp;
	    <input name="Prv_Fre" type="text" id="Prv_Fre" value="" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
        Fecha de Actualizaci�n Actividades:
       </td>
       <td class="LetraNegra">&nbsp;
       <input name="Prv_Fac" type="text" id="Prv_Fac" value="" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
       </td>
      </tr>
     </table>
    </FIELDSET>
    
    <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Datos de Ubicaci&oacute;n</label>
	 </LEGEND>
     <table width="100%"  border="0" cellpadding="2" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
        <span class="Asterisco">*</span> Ciudad:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
       <?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Ciu_Des'];
			}
			else 
			{
		?>
				<select name="Ciu_Cod" id="Ciu_Cod">
				<option value="">Seleccione...</option>
        		<?php 
					$arr_Ciudad = $obBD_con1->getArrayConsulta(3, '', $obBD_conexion);
					foreach($arr_Ciudad as $row)
					{
				?>
         				<option value="<?php echo $row['Ciu_Cod']; ?>">
         				<?php echo $row['Ciu_Des']; ?>
         				</option>
         		<?php
					}
				?>
        		</select>
        <?php 
			} 
		?>
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
        <span class="Asterisco">*</span>
        Direcci&oacute;n:
       </td>
       <td class="LetraNegra">&nbsp;
       <?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Dir'];
			}
			else 
			{
		?>
				<input name="Prs_Dir" type="text" id="Prs_Dir" style="text-transform:uppercase" value="" size="66" maxlength="80" />
		<?php
			}
		?>
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
         <span class="Asterisco">*</span> 
         Tel&eacute;fono:
       </td>
       <td class="LetraNegra">&nbsp;
        <?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Tel'];
			}
			else 
			{
		?>
				<input name="Prs_Tel" type="text" id="Prs_Tel" style="text-transform:uppercase" value="" size="15" maxlength="15" onBlur="numerico(this)"/>
		<?php
			}
		?>
       </td>    
      </tr>
      <tr>
	   <td class="Etiqueta1">
        
         Tel&eacute;fono 2:
       
       </td>
       <td class="LetraNegra">&nbsp;
       <?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Te2'];
			}
			else 
			{
		?>
				<input name="Prs_Te2" type="text" id="Prs_Te2" style="text-transform:uppercase" value="" size="15" maxlength="15" onBlur="numerico(this)"/>
		<?php
			}
		?>
       </td>    
      </tr>
      <tr>
	   <td class="Etiqueta1">
         Celular:
       </td>
       <td class="LetraNegra">&nbsp;
       <?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Cel'];
			}
			else 
			{
		?>
				<input name="Prs_Cel" type="text" id="Prs_Cel" style="text-transform:uppercase" value="" size="15" maxlength="15" onBlur="numerico(this)"/>
		<?php
			}
		?>
       </td>    
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
       Fax:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        <input name="Prv_Fax" type="text" id="Prv_Fax" style="text-transform:uppercase" value="" size="15" maxlength="15" onBlur="numerico(this)"/>
       </td>
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
       E-mail:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
       <?Php 
			if(isset($row_rs_persona))
			{
				echo $row_rs_persona['Prs_Cor'];
			}
			else 
			{
		?>
				<input name="Prs_Cor" type="text" id="Prs_Cor" value="" size="30" onblur="correo(this);"/>
		<?php
			}
		?>
       </td>
      </tr>
     </table>
    </FIELDSET>
    
    <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Datos de Contacto</label>
	 </LEGEND>
	 <table width="100%"  border="0" cellpadding="2" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
        Apellidos:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
	    <input name="Prv_Act" type="text" id="Prv_Act" style="text-transform:uppercase" value="" size="30" maxlength="80"/>
       </td>
      </tr>
      <tr>
	   <td class="Etiqueta1">
       Nombres:
       </td>
       <td class="LetraNegra">&nbsp;
       <input name="Prv_Nct" type="text" id="Prv_Nct" style="text-transform:uppercase" value="" size="30" maxlength="80"/>
       </td>
      </tr>
      <tr>
            <td class="Etiqueta1">
             E-mail:
            </td>
            <td class="LetraNegra">&nbsp;
            <input name="Prv_Ect" type="text" id="Prv_Ect" value="" size="30" onblur="correo(this);"/>
            </td>  
          </tr>     
     </table>
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
    <table border="0" cellpadding="0" cellspacing="0">
      <tr> 
      	<td width="113">
      		 <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'hdd_volver', '<?Php echo '1'; ?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>&nbsp;
      	</td>
        <td width="187">
          <button type="button" class="btn btn-primary start" title="Guardar" onclick="<?php 
          if(!isset($row_rs_persona))
          {
          	echo "validar_persona(this.form);";
          }
          else
          {
          	echo "validar_proveedor(this.form);";
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
  }
  ?>
  </td>
  </tr>
  </table>
</div>
<script type="text/javascript" src="../VALIDACIONES/adq_par_proveedor.js"></script>
  <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>