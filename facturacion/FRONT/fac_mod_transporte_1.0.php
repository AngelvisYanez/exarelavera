<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
/**
 * Permite actualizar datos de Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 *
 * @author Jose Cumbicos
 * @version 1.0
 * Fecha de actualización:	2015-06-30
 * @package tesoreria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_transporte.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Tra($Ses_Dat_Dis);
 
/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 =  new Class_Log_Datos_Tra;

/**
 * Llamado de la libreria para evitar el reenvio de datos
 * @var Post_Block
 */
$thisPost = new Post_Block;

if(isset($_POST['hdd_volver']))
{
  	unset($_POST['hdd_save']);
  	unset($_POST['Tra_Cod']);
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
  		$Param_cliente = '*'.$_POST['Prs_Cod'].'*'.$Ses_Emp_Cod.'*'.$_POST['Tra_Cod'];
		//echo "<br>".$Param_cliente;
  		
  		/**
  		 * Valores de la persona a guardar
  		 * @var string
  		 */
  		$Param_persona = $_POST['Prs_Ced'].'*'.$_POST['Prs_Nom'].'*'.$_POST['Prs_Ape'].'*'.$_POST['Prs_Dir'].'*'.$_POST['Prs_Cod'];
		//echo "<br>".$Param_persona."<br>";
		$obBD_con1->updatePersonaCliente(12,$Param_persona,13,$Param_cliente);

		unset($_POST['hdd_save']);
  		unset($_POST['Tra_Cod']);
	}
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
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
   <tr class="BarraTitulo">
    <td height="10">&raquo; modificar Transporte
    </td>
   </tr> 
   <tr>
     <td valign="top" height="400">
    <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
    <?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
    </form>   
   <?Php
  if(isset($_POST['txt_busqueda']) && !isset($_POST['Tra_Cod']))
  {
  ?>
   <FIELDSET>
   <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
   </LEGEND>
    <table class="fixedHeader01" cellpadding="0" cellspacing="0" width="100%" >
    <thead>
    <tr>
	 <th width="6%">C&oacute;d. Int. </th>
     <th width="28%">C&eacute;dula/RUC</th>
     <th width="63%">Transporte</th>
	 <th width="3%">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <?php	
	$Arr_Transporte = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == "d"? 9 : 10,$Ses_Emp_Cod.'*'.$_POST['txt_busqueda'], $obBD_conexion);
	$total_row=count($Arr_Transporte);
	if($total_row!=0)
	{	
		foreach($Arr_Transporte as $row)
		{
		?>
		<tr>
		 <td align="center" width="6%">
		  <?Php echo '&nbsp;'.$row['Tra_Cod']; ?>
		 </td>
		 <td>
		  <?Php echo '&nbsp;'.$row['Prs_Ced']; ?>
		 </td>
		 <td>
		  <?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'], '#FFFF00', 1); ?>
		 </td>
		 <td width="3%" align="center">
		 <form name='form6' method='post'  action='<?php echo $_SERVER['PHP_SELF'];?>'>
			<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
				<i class=" icon-arrow-right icon-white"></i>
			</button>
			<input name="Tra_Cod" id="Tra_Cod" type="hidden" value="<?Php echo $row['Tra_Cod']; ?>">
			<input name="txt_busqueda" value="<?php echo $_POST['txt_busqueda'];?>" type="hidden">
			<input name="op_opciones" value="<?php echo $_POST['op_opciones'];?>" type="hidden">
		</form>
		</td>
	   </tr>   
		<?php
		}
	}else{
		?>
     <tr>
      <td align="center">&nbsp;</td>
      <td>&nbsp;</td>
      <td align="center"><?php 
			$msg_mes = explode('=', $cmb_mes);
	  		echo error_alerta(" No hay resultados que mostrar...!", 2);
		 ?></td>
      <td width="3%" align="center">&nbsp;</td>
    </tr> 
    <?php }?>
    </tbody>
  </table>
  <?php
   echo barra_estado(count($Arr_Transporte));
  ?>
   </FIELDSET>   
   <?php
  }   
  if(isset($_POST['Tra_Cod']) && !isset($_POST['hdd_volver']))
  {
  ?>
  <FIELDSET>
   <LEGEND>
	<label class="Titulos2">Datos a Modificar</label>
   </LEGEND> 
    <form method="post" name= "form" action="<?php echo $_SERVER['PHP_SELF'];?>">
   <?php 
   	 $thisPost->startPost();
	 $row_rs_persona = $obBD_con1->getRowConsulta(11,$_POST['Tra_Cod'],$obBD_conexion);
   ?>
    <input name="Prs_Cod" type="hidden" id="Prs_Cod" value="<?php echo $row_rs_persona['Prs_Cod'];?>" />
    <input name="Tra_Cod" type="hidden" id="Tra_Cod" value="<?php echo $_POST['Tra_Cod'];?>" />
    
    <table width="100%" border="0" cellpadding="2" cellspacing="0">
  	<tr>
       <td><?Php echo mensaje_requerido(); ?></td>
  	</tr>
  	</table>

	<FIELDSET>
	<LEGEND>
	<label class='Titulos2'>Datos del Transporte</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="2" cellspacing="0">
	  <tr>
	    <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula/R.U.C.:</td>
	    <td width="770" class="LetraNegra">&nbsp;
	      <input name="Prs_Ced" type="text" id="Prs_Ced" value="<?php echo $row_rs_persona['Prs_Ced']; ?>" size="13" maxlength="13" readonly="readonly">
	      <?php
			/**
			 * Total de caracteres
			 * @var int
			 */
			$Ide_Max = strlen($row_rs_persona['Prs_Ced']);
			$Ide_Max = ($Ide_Max != 10 && $Ide_Max != 13)?-1:$Ide_Max;
			/**
			 * valores de identificacion del documento ingresado
			 * @var array
			 */
			$Identifica = $obBD_con1->getRowConsulta(4,$Ide_Max,$obBD_conexion);
		?>
	      <input type="hidden" id="Ide_Cod" name="Ide_Cod" value="<?php echo $Identifica['Ide_Cod'];?>">
	      </td>
	    </tr>
	  <tr id="Natural">
	    <td class="Etiqueta1">        
	      <span class="Asterisco">*</span> 
	      Nombre:        
	      </td>
	    <td  class="LetraNegra">&nbsp;
	      <input name="Prs_Nom" type="text" id="Prs_Nom" style="text-transform:uppercase" value="<?php echo $row_rs_persona['Prs_Nom'];?>" size="30" maxlength="80" />
	      </td>
	    </tr>
      <tr>
       <td class="Etiqueta1">        
         <span class="Asterisco">*</span> 
         <label id="Natural_a">Apellido /</label>
         <label id="Juridico">Razon Social:</label>        
       </td>
       <td  class="LetraNegra">&nbsp;
		<input name="Prs_Ape" type="text" id="Prs_Ape" style="text-transform:uppercase" value="<?php echo $row_rs_persona['Prs_Ape'];?>" size="30" maxlength="80" />
       </td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n domiciliaria:</td>
        <td class="LetraNegra">&nbsp;
          <input name="Prs_Dir" type="text" style="text-transform:uppercase" id="Prs_Dir" value="<?php echo $row_rs_persona['Prs_Dir'];?>" size="66" maxlength="60" >
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
      		 <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*hdd_volver', '<?php echo $_POST['txt_busqueda'].'*'.$_POST['op_opciones'].'*'.'1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
      	</td>
        <td width="190">
          <button type="button" class="btn btn-primary start" title="Guardar" onclick="<?php 
          if(!isset($row_rs_persona))
          {
          	echo "validar_persona_ced(this.form);";
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
  }
  ?>
<script type="text/javascript" src="../VALIDACIONES/fac_par_transporte.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
	</td>
   </tr>
</table>
</div>
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>