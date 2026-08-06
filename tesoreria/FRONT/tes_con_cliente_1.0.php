<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php
/**
 * Permite visualizar datos de Clientes
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-26
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2014-05-29 
 *
 * @package tesoreria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cliente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 =  new Class_Log_Datos_Cli;
?>
<HTML>
<HEAD>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/tes_val_cliente.js"></script>
		
		<script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
	    
	    <script type="text/javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>
		
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
   <tr class="BarraTitulo">
    <td>&raquo; Consultar cliente</td>
   </tr>
   <tr height="400">
    <td valign="top">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
 		<td>
<?php	      
   /**
    * $op obtiene el numero de la pestaña activa
    */
   
	if(!isset($_GET['op']))
	{
	  	$op = 1;
	}
	else
	{
		$op = $_GET['op'];
	}
    
	/**
    * $descripcion cadena que contiene los nombres de las pestañas separadas por ( * )
	* $pag1 and $pag2 URL de la pagina
    */
	
	$descripcion = "Individual*Todos";
	$pag1 = $_SERVER['PHP_SELF']."?op=1";
	$pag2 = $_SERVER['PHP_SELF']."?op=2";
	tabs(2,$descripcion, $pag1.'*'.$pag2, $op);
?>
		</td>
	</tr>
	<tr>
		<td>
<?php 
	switch ($op)
	{
		case 1:
?>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td valign="top">
    <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
    <?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
    </form>
    </td>
   </tr>
   <?Php
  if(isset($_POST['txt_busqueda']) && !isset($_POST['Cli_Cod']))
  {
  ?>
   <tr>
   <td>
   <FIELDSET>
   <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
   </LEGEND>
    <table class="fixedHeader01" cellpadding="0" cellspacing="0" width="100%" >
    <thead>
    <tr>
	 <th width="6%">C&oacute;d. Int. </th>
     <th>C&eacute;dula/RUC</th>
     <th>Cliente</th>
	 <th width="3%">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <?php
	
	$Arr_Cliente = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == "d"? 9 : 10,$Ses_Emp_Cod.'*'.$_POST['txt_busqueda'], $obBD_conexion);
	
	foreach($Arr_Cliente as $row)
	{
	?>
    <tr>
	 <td align="center" width="6%">
	  <?Php echo '&nbsp;'.$row['Cli_Cod']; ?>
     </td>
	 <td>
	  <?Php echo '&nbsp;'.$row['Prs_Ced']; ?>
     </td>
	 <td>
	  <?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'], '#FFFF00', 1); ?>
     </td>
	 <td align="center" width="3%">
	 <form name='form6' method='post'  action='<?php echo $_SERVER['PHP_SELF'];?>'>
	 	<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
		<input name="Cli_Cod" id="Cli_Cod" type="hidden" value="<?Php echo $row['Cli_Cod']; ?>">
    	<input name="txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>" type="hidden">
    	<input name="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8');?>" type="hidden">
    </form>
    </td>
   </tr> 
	<?php
	}
	?>
    </tbody>
  </table>
  <?php
   echo barra_estado(count($Arr_Cliente));
  ?>
 </FIELDSET>
   </td>
   </tr>
   <?php
  }
   ?>
  </table>
  <?php
  if(isset($_POST['Cli_Cod']) && !isset($_POST['hdd_volver']))
  {
  ?>
  <FIELDSET>
   <LEGEND>
	<label class="Titulos2">Datos a Modificar</label>
   </LEGEND>
   <?php 
	 $row_rs_persona = $obBD_con1->getRowConsulta(14,$_POST['Cli_Cod'],$obBD_conexion);
   ?>
    <table width="100%" border="0" cellpadding="2" cellspacing="0">
  	<tr>
       <td><?Php echo mensaje_requerido(); ?></td>
  	</tr>
  	</table>

	<FIELDSET>
	<LEGEND>
	<label class='Titulos2'>Datos del cliente</label>
	</LEGEND>

	<table width="734" border="0" cellpadding="2" cellspacing="0">
	  <tr>
        <td width="170" class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
	    <td class="LetraNegra">&nbsp;
			<?php echo $row_rs_persona['Prs_Ced']; ?>
		</td>
	  </tr>
	  
	  <tr>
	   <td class="Etiqueta1">
        Tipo de Contribuyente:
       </td>
       <td class="LetraNegra">&nbsp;
         <?php 
         if($row_rs_persona['Cli_Tic']=='N')
         {
         	echo "NATURAL";
         }
         else
         {
         	echo "JURIDICO";
         }?>
         <input type="hidden" name="Cli_Tic" id="Cli_Tic" value="<?php echo $row_rs_persona['Cli_Tic'];?>" >
       </td>
      </tr>
      
      <tr id="Natural">
       <td class="Etiqueta1">
         Nombre:
        
       </td>
       <td  class="LetraNegra">&nbsp;
        <?php echo $row_rs_persona['Prs_Nom'];?>
       </td>
      </tr>
      <tr>
       <td class="Etiqueta1">
         <label id="Natural_a">Apellido:</label>
         <label id="Juridico">Empresa:</label>
        
       </td>
       <td  class="LetraNegra">&nbsp;
		<?php echo $row_rs_persona['Prs_Ape'];?>
       </td>
      </tr>
	 
      <tr id="sexo">
        <td  class="Etiqueta1">G&eacute;nero: </td>
        <td  class="LetraNegra">&nbsp;
		<?php 
			if($row_rs_persona['Prs_Sex']=="M")
			{
				echo "MASCULINO";
			}
			else
			{
				echo "FEMENINO";
			}
		?>
        </td>
        
      </tr>
      
      <tr>
        <td class="Etiqueta1"> Obligado a llevar Contabilidad: </td>
        <td class="LetraNegra">&nbsp;<?php 
			if($row_rs_persona['Cli_Con']=="N")
			{ echo "NO";}
			else if($row_rs_persona['Cli_Con']=="S")
			{ echo "SI";}			
		?></td>
      </tr>
      <tr id="tipo_pr">
        <td width="170" class="Etiqueta1"> Tipo: </td>
        <td width="556" class="LetraNegra">&nbsp;
		    <?php 
			if($row_rs_persona['Cli_Tip']=="P")
			{
				echo "PUBLICO";
			}
			else if($row_rs_persona['Cli_Tip']=="R")
			{
				echo "PRIVADO";
			}
			else
			{
				echo " ";
			}
		?>
        </td>
      </tr>
	  
      <tr>
        <td class="Etiqueta1">Correo:</td>
        <td class="LetraNegra">&nbsp;<?php echo $row_rs_persona['Prs_Cor'];?></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Direcci&oacute;n domiciliaria:</td>
        <td class="LetraNegra">&nbsp;
			<?php echo $row_rs_persona['Prs_Dir'];?>
		</td>
      </tr>
      <tr>
        <td class="Etiqueta1">Ciudad: </td>
        <td >&nbsp;
        <?php echo $row_rs_persona['Ciu_Des'];?>
        </td>
      </tr>
      <tr>
        <td  class="Etiqueta1"> Tel&eacute;fono:</td>
        <td class="LetraNegra">&nbsp;
				<?php echo $row_rs_persona['Prs_Tel']?>
        <span class="Etiqueta1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Telefono 2 :
		 <?php echo $row_rs_persona['Prs_Te2']?>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>
		<span class="Etiqueta1">Celular:
		 <?php echo $row_rs_persona['Prs_Cel'];?>
        </span>
        </td>
      </tr>
      
      <tr>
        <td  class="Etiqueta1">Cupo:</td>
        <td>&nbsp;<?php echo $row_rs_persona['Cli_Cup']?></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Zona:</td>
        <td>&nbsp;
        	<?php echo $row_rs_persona['Zon_Des']; ?>
        </td>
      </tr>
    </table>
    <script type="text/javascript">
    MostrarNJ(this);
    </script>
    </FIELDSET>
    
    <FIELDSET>
	<LEGEND>
		<label class="Titulos2">De la Emisi�n de Factura</label>
	</LEGEND>
	<table width="629" border="0" cellpadding="2" cellspacing="0">  
  	<tr>
		<td width="140" class="Etiqueta1">C&eacute;dula/R.U.C.:&nbsp;</td>
		<td width="475" >
		<?php echo $row_rs_persona['Cli_Ruf'];?>
		</td>
	</tr>
  	<tr>
		<td width="140" class="Etiqueta1">Factura a nombre de:&nbsp;</td>
		<td>
		<?php echo $row_rs_persona['Cli_Fac'];?>
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
    <table width="300" border="0" cellpadding="0" cellspacing="0">
      <tr> 
      	<td width="89">
      	<form method="post" name= "form" action="<?php echo $_SERVER['PHP_SELF'];?>">
      		 <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*hdd_volver', '<?php echo $_POST['txt_busqueda'].'*'.$_POST['op_opciones'].'*'.'1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
       	</form>
      	</td>
      </tr>
    </table>
  </FIELDSET>
  <?php 
  }
  ?>
  </div>
<?php
			break;
		case 2:
?>
<fieldset>
<legend>
<label class="Titulos2">Listado de Clientes</label>
</legend>
<br>
<table class="fixedHeader01" cellpadding="0" cellspacing="0" width="100%" id="Exportar_a_Excel">
    <thead>
    <tr>
	 <th width="6%">C&oacute;d. Int. </th>
     <th width="10%">C&eacute;dula/RUC</th>
     <th width="84%">Cliente</th>
    </tr>
    </thead>
    <tbody>
    <?php
	
	$Arr_Cliente = $obBD_con1->getArrayConsulta(9,$Ses_Emp_Cod.'*'.'', $obBD_conexion);
	
	foreach($Arr_Cliente as $row)
	{
	?>
    <tr>
	 <td align="center" width="6%">
	  <?Php echo '&nbsp;'.$row['Cli_Cod']; ?>
     </td>
	 <td>
	  <?Php echo '&nbsp;'.$row['Prs_Ced']; ?>
     </td>
	 <td>
	  <?Php echo $row['Prs_Ape']." ".$row['Prs_Nom']; ?>
     </td>
   </tr> 
	<?php
	}
	?>
    </tbody>
  </table>


</fieldset>
<br>
<div id="set1">
<table width="300" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td width="110">
     <form action="tes_pri_cliente_1.0.php" method="post" name="form2" id="form2" target="_blank">
  		<button type="button" class="btn btn-primary start" title="Imprmir" onclick="this.form.submit()">
           <i class=" icon-print icon-white"></i>
           <span>Imprimir</span>
		</button>
     </form>
  </td>
  	<td width="190">
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
</div>
<?php
			break;
	}
?>
		</td>
	</tr>
</table>
<script type="text/javascript" src="../VALIDACIONES/tes_par_cliente.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
    </td>
   </tr>
 </table>

</BODY>
</HTML>
<?php
/**
* Cierre de las conexiones
*/
$obBD_con1->liberar(); 
$obBD_conexion->cerrar();
?>