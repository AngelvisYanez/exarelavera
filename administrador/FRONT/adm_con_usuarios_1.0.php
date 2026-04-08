<?php
/**
 * Permite visualizar los usuarios
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualización:	2012-04-18
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualización:	2014-05-21 
 *
 * @package administrador.FRONT
 */

require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_usuarios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Admu($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Admu;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script language="javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
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
		
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
        							
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
  <td>&raquo; Consultar  usuarios</td>
</tr>
<tr>
 <td valign="top">
<?php	
   
   
   /**
    * $op obtiene el numero de la pestaÃ±a activa
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
    * $descripcion cadena que contiene los nombres de las pestaÃ±as separadas por ( * )
	* $pag1 and $pag2 URL de la pagina
    */
	
	$descripcion = "Individual*Todos";
	$pag1 = $_SERVER['PHP_SELF']."?op=1";
	$pag2 = $_SERVER['PHP_SELF']."?op=2";
	tabs(2,$descripcion, $pag1.'*'.$pag2, $op);
?>
</td>
</tr>
<tr height="400">
<td valign="top">
   <?
  switch($op)
  {
	  case 1:
?>
	<div id="set1">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr >	  	
			<td valign="top">
				<form name="Buscador" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
					<?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
				</form>
			</td>
		</tr>
		
<?Php 
/**
 * Numero de personas anuladas
 * @var int
 */
$anulada = 0;

if(isset($_POST['txt_busqueda']) && !isset($_POST['Usu_Cod']))
{
?>	
<tr><td valign="top">	    
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
    <table width="100%" border="1" align="center" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
      <tr>
        <th width="6%" >Cod. Int. </th>
        <th width="8%" >Cédula</th>
        <th >Apellidos y Nombres </th>
        <th>Sucursal</th>
		<th width="3%" >&nbsp;</th>
      </tr>
     </thead> 
     <tbody>
<?php 

	/**
	 * personas encontradas
	 * @var array
	 */
	$Arr_Persona = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == "d"? 14 : 15,$Ses_Emp_Cod.'*'.$_POST['txt_busqueda'], $obBD_conexion);
	
	foreach($Arr_Persona as $row)
	{
		/**
		 * Color de la fuente a mostrar en el resultado
		 * @var string
		 */
		$rojo='';
		
		if($row['Usu_Est']=='I')
		{
			$rojo='#FF0000'; 
			$anulada++;
		}
?>
	<tr>
		<td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row['Prs_Cod']; ?></FONT></td>
		<td><FONT COLOR="<? echo $rojo;?>">&nbsp;<?Php echo $row['Usu_Ced']; ?></FONT></td>
		<td>&nbsp;<FONT COLOR="<? echo $rojo;?>"><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'],'#FFFF00', 1);?></FONT></td>
		<td><FONT COLOR="<? echo $rojo;?>">&nbsp;<?Php echo $row['Suc_Des']; ?></FONT></td>
		<td align="center">
<?php 
		if($row['Usu_Est']=='I')
		{
?>
		<img src="../../mascaras/model1/imagenes/32x32/encrypted.png">
<?php
		}
		else
		{
?>
			<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
			<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
           		<i class=" icon-arrow-right icon-white"></i>
           	</button>					
            <input type="hidden" name="Usu_Cod" id="Usu_Cod" value="<?Php echo $row['Usu_Cod'];?>">
			<input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?Php echo $_POST['txt_busqueda'];?>"/>
			<input type="hidden" name="op_opciones" id="op_opciones" value="<? echo $_POST['op_opciones']?>">
			</form>
<?php
		}
?>
		</td>
	</tr>
<?php
	}
?>
  </tbody>
</table>
</FIELDSET>
<?php echo barra_estado(count($Arr_Persona));?>
</td>
</tr>
<?
}

if (isset($_POST['Usu_Cod']))
{
	$row_rs_consulta = $obBD_con1->getRowConsulta(16, $_POST['Usu_Cod'], $obBD_conexion);
?>
<tr><td>		  
<FIELDSET>
<LEGEND>
<label class="Titulos2">Del Usuario</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="16%" align="justify" class="Etiqueta1">Sucursal:</td>
	  <td colspan="2" class="LetraNegra">&nbsp;<?php echo $row_rs_consulta['Suc_Des'];?>
	  </td>
	</tr>
	<tr>
        <td class="Etiqueta1">Usuario: </td>
        <td colspan="2" class="LetraNegra">&nbsp;
		  <?Php echo $row_rs_consulta['Usu_Ced']; ?>	   
		</td>
    </tr>
    <tr>
       <td class="Etiqueta1">Apellidos y Nombres: </td>
       <td colspan="2" class="LetraNegra">&nbsp;<?Php echo $row_rs_consulta['Prs_Ape']." ".$row_rs_consulta['Prs_Nom']; ?></td>
    </tr>
    <tr>
      <td class="Etiqueta1">La cuenta expira: </td>
      <td colspan="2">&nbsp;
      <?Php if ($row_rs_consulta['Usu_Cad'] == 'S'){ echo "SI"; }else { echo "NO";} ?>
      </td>
    </tr>	  
</table>
</FIELDSET>
</td>
</tr>
<tr><td>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Del Perfil</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">      
      <tr>
        <td width="14%" class="Etiqueta1" valign="top">Perfiles Activos: </td>
        <td width="86%" align="left">
		  <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <?
          $rs_consulta2 = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
		  $rs_consulta3 = $obBD_con1->getArrayConsulta(17, $_POST['Usu_Cod'], $obBD_conexion);
          
 		  $i=0;
		          
		  foreach($rs_consulta3 as $row_rs_consulta3) {
			$i++;
			$perfiles[$i]=$row_rs_consulta3['Per_Cod'];
		  }
          
		  for($ch = 0; $ch < count($rs_consulta2); ) {
			  ?>
            <tr align="left">
              <? for($i = 0; $i < 3; $i++){
		              	if(isset($rs_consulta2[$ch + $i]))
		              	{
			              	$row_rs_consulta2 = $rs_consulta2[$ch + $i];
			              	
						  if($row_rs_consulta2['Per_Est']=='I'){ $rojo='#FF0000'; $anulada++; }else{$rojo='';}?>
				              <td width="30%" colspan="3" <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "LetraNegra");?> class="LetraNegra">
				              	<span id="span_foco"  <? if (in_array($row_rs_consulta2['Per_Cod'],$perfiles)) {?> style='background:#0F0' <?Php } ?>>
				              		&nbsp;
				              		<FONT COLOR="<? echo $rojo;?>"><? echo $row_rs_consulta2['Per_Des']; ?></FONT>
				              	</span>
							  </td>
				               <? 
		              	}
				      } 
				      $ch = $ch + $i;
				      ?>
		            </tr>
		          <? 
				 }
				 ?>
         </table>	
		 </td>
      </tr>
  </table>
</FIELDSET>
</td></tr>
<tr><td>
<br />
   <table width="300" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td width="34%">
     <form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
  <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*hdd_volver', '<? echo $_POST['txt_busqueda'].'*'.$_POST['op_opciones'].'*'.'1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
       		 </form>
  </td>
   </tr>
   </table>
   </td>
   </tr> 
<?Php } //fin del if(isset($hdd_aux))

		/* Parametro de la busqueda por fecha en compras */
		/* Control para setear el arreglo solo cuando tenga valores*/
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}//Fin del if ($anulada > 0)
		?>
	</table>
	</div>
	<br>
	<?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>
<?php
	  	break;
	  case 2:
?>
<fieldset>
<legend>
<label class="Titulos2">Listado de Usuarios</label>
</legend>
<br>

<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01" id="Exportar_a_Excel">
    <thead>
      <tr>
        <th width="6%" >Cod. Int. </th>
        <th width="8%" >Cédula</th>
        <th >Apellidos y Nombres </th>
        <th>Sucursal</th>
      </tr>
     </thead> 
     <tbody>
<?php 

	/**
	 * personas encontradas
	 * @var array
	 */
	$Arr_Persona = $obBD_con1->getArrayConsulta(14,$Ses_Emp_Cod.'*'.'', $obBD_conexion);
	
	foreach($Arr_Persona as $row)
	{
		/**
		 * Color de la fuente a mostrar en el resultado
		 * @var string
		 */
		$rojo='';
		
		if($row['Usu_Est']=='I')
		{
			$rojo='#FF0000'; 
			$anulada++;
		}
?>
	<tr>
		<td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row['Prs_Cod']; ?></FONT></td>
		<td><FONT COLOR="<? echo $rojo;?>">&nbsp;<?Php echo $row['Usu_Ced']; ?></FONT></td>
		<td>&nbsp;<FONT COLOR="<? echo $rojo;?>"><?Php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?></FONT></td>
		<td><FONT COLOR="<? echo $rojo;?>">&nbsp;<?Php echo $row['Suc_Des']; ?></FONT></td>
	</tr>
<?php
	}
?>
  </tbody>
</table>
</fieldset>
<?php echo barra_estado(count($Arr_Persona));?>
<br>
<div id="set1">
<table width="300" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td width="34%">
     <form action="adm_pri_usuarios_1.0.php" method="post" target="_blank" name="form2" id="form2">
  		<button type="button" class="btn btn-primary start" title="Imprmir" onclick="this.form.submit()">
           <i class=" icon-print icon-white"></i>
           <span>Imprimir</span>
		</button>
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
   </table><br />
</div>
<?php
	  	break;
  }
?>
</td>
</tr>
</table>

<script type="text/javascript" src="../VALIDACIONES/adm_par_usuarios.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>