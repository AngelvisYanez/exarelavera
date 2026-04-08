<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?Php 
/**
 * Permite actualizar un usuario Usuario
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
require_once('../../Librerias/postclass.php');

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

/**
 * Llamado de la libreria para evitar el reenvio de datos
 * @var Post_Block
 */
$thisPost = new Post_Block;
/* // prueba nuevo mail gmail
require_once('../../facturacion/LOGICA/fac_log_electronica.php');
$obBD_elect =  new Class_Log_Datos_Factura_Elect();
var_dump($obBD_elect->sendMailDoc2(57729,"ep_niebla@hotmail.com",NULL,$obBD_conexion));
exit();*/
/**
 * Llamado de la libreria para evitar el reenvio de datos
 * @var Post_Block
 */
$thisPost = new Post_Block;

if(isset($_POST['hdd_volver']))
{
	unset($_POST['hdd_save']);
	unset($_POST['Usu_Cod']);
}
	 
	 
if (isset($_POST['hdd_save']))
{
	if ($thisPost->postBlock($_POST['postID']))
	{
	
		/**
		 * Inicio de la transaccion
		 */
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
				
		/**
		 * Inserción de datos de la inscripción 
		 */
		$obBD_con1->operacionobBD(18, $_POST['Usu_Cod'], $obBD_conexion);
		
		$Per_Cod = $_POST['perfil'];
		$n = count($Per_Cod);
		$i = 0;
		 
		while ($i < $n)
		{
			/**
			 * Guardado de perfiles
			 */
			$obBD_con1->operacionobBD(13, $_POST['Usu_Cod'].'*'.$Per_Cod[$i], $obBD_conexion);
			$i++;
		}
	
		if (trim($_POST['Usu_Pal'])==""){
			
			/**
			 * Sql para modificar sin el tipo de usuario si lo requiere
			 */
			$obBD_con1->operacionobBD(19, $_POST['Usu_Ced2'].'*'.$_POST['Usu_Cod'].'*'.$_POST['Usu_Cad'], $obBD_conexion);
		}
		else{		
			/**
			 * Sql para modificar sin el tipo de usuario si lo requiere
			 */
			$obBD_con1->operacionobBD(20, trim($_POST['Usu_Pal']).'*'.$_POST['Usu_Ced2'].'*'.$_POST['Usu_Cod'].'*'.$_POST['Usu_Cad'], $obBD_conexion);
		}
		
		//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
		
		/**
		 * Cierre de la transacción 
		 */
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		
		unset($_POST['Usu_Cod']);
		unset($_POST['hdd_save']);
 	}			
} 
?>
<html>
	<head>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script language="javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	</head>
<body>
<div id="set1">
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
		<tr class="BarraTitulo">
  			<td height="10">&raquo; Modificar de usuarios</td>
		</tr>
		<tr height="400">	  	
			<td valign="top">
				<form name="Buscador" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
					<?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
				</form>
<?Php 
/**
 * Numero de personas anuladas
 * @var int
 */
$anulada = 0;
if(isset($_POST['txt_busqueda']) && !isset($_POST['Usu_Cod']))
{
?>		    
    <fieldset>
	    <legend>
	    	<label class="Titulos2">Resultados de la busqueda</label>
	    </legend>
	    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
	    <thead>
	      <tr>
	        <th width="5%" >Cod. Int. </th>
	        <th width="8%" >Cédula</th>
	        <th>Apellidos y Nombres </th>
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
		<td><FONT COLOR="<? echo $rojo;?>"><?php echo $row['Suc_Des'];?></FONT></td>
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
<?
echo barra_estado(count($Arr_Persona));
}

if (isset($_POST['Usu_Cod']))
{ 
	$row_rs_consulta = $obBD_con1->getRowConsulta(16, $_POST['Usu_Cod'], $obBD_conexion);
?> 	  
<form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
<? //Creacion del campo REPOST
$thisPost->startPost();?>		  
<fieldset>
<LEGEND>
<label class="Titulos2">Del Usuario</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="16%" align="justify" class="Etiqueta1">Sucursal:</td>
	  <td colspan="2" class="LetraNegra">&nbsp;<?php echo $row_rs_consulta['Suc_Des'];?>
		  <input type="hidden" name="Suc_Des" id="Suc_Des" value="<?php echo $row_rs_consulta['Suc_Cod'];?>">
	  </td>
	</tr>
	<tr>
        <td class="Etiqueta1">Usuario: </td>
        <td colspan="2" class="LetraNegra">&nbsp;<input name="Usu_Cod2" type="hidden" value="<?Php echo $row_rs_consulta['Usu_Cod'];?>">
		  <input name="Usu_Ced2" type="hidden" value="<?Php echo $row_rs_consulta['Usu_Ced'];?>"><?Php echo $row_rs_consulta['Usu_Ced']; ?>	    </td>
    </tr>
    <tr>
       <td class="Etiqueta1">Apellidos y Nombres: </td>
       <td colspan="2" class="LetraNegra">&nbsp;<?Php echo $row_rs_consulta['Prs_Ape']." ".$row_rs_consulta['Prs_Nom']; ?></td>
    </tr>
    <tr>
		<td class="Etiqueta1">Clave: </td>
		<td width="23%"><input type="password" name="Usu_Pal" id="Usu_Pal" size="25" maxlength="32" onKeyUp="seguridad_clave(this.value)" onBlur="if (this.value.length != 0) { minimo(this, 6); }"></td>
		<td width="61%" valign="middle">
			<table border="1" cellpadding="0" cellspacing="0" bordercolor="#333333">
			<tr>
				<td>
				<table width="143" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td id="niv1" width="16" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
					<td id="niv2" width="25" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
					<td id="niv3" width="42" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
					<td id="niv4" width="60" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
				  </tr>
				</table>		  		</td>
			</tr>
			</table>		</td>
    </tr>
    <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Confirmar Clave: </td>
        <td colspan="2"><input name="Usu_Pal2" id="Usu_Pal2" type="password" size="25" maxlength="32" onBlur="if (this.value.length != 0) { minimo(this, 6); }"></td>
    </tr>
    <tr>
      <td class="Etiqueta1"><span class="Asterisco">*</span> La cuenta expira: </td>
      <td colspan="2">&nbsp;<select name="Usu_Cad">
	  	<option <?Php if ($row_rs_consulta['Usu_Cad'] == 'S'){ echo "selected"; } ?> value="S">Si</option>
	  	<option <?Php if ($row_rs_consulta['Usu_Cad'] == 'N'){ echo "selected"; } ?> value="N">No</option>		
      </select>
      </td>
    </tr>	  
</table>
</fieldset>
	<fieldset>
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
			              	
						  if($row_rs_consulta2['Per_Est']=='I'){ $rojo='#FF0000'; $anulada++; }else{$rojo='';}	
					      ?>
				              <td width="30%" colspan="3" <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "LetraNegra");?> class="LetraNegra">
					              	<span id="span_foco"  <? if (in_array($row_rs_consulta2['Per_Cod'],$perfiles)) {?> style='background:#0F0' <?Php } ?>>
					              		<input name="perfil[]" id="perfil[]" type="checkbox"  value="<? echo $row_rs_consulta2['Per_Cod']; ?>" <? if (in_array($row_rs_consulta2['Per_Cod'],$perfiles)) { echo "checked"; } ?>>&nbsp;
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
	</fieldset>
<br>
   <table width="300" border="0" cellpadding="0" cellspacing="0">
	   <tr>
		     <td width="34%">
		  		<button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*hdd_volver', '<? echo $_POST['txt_busqueda'].'*'.$_POST['op_opciones'].'*'.'1';?>')">
		               <i class=" icon-arrow-left icon-white"></i>
		               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
		       		 </button>
		  	</td>
		    <td width="66%" height="23">
		     <button type="button" class="btn btn-primary start" title="Guardar" onClick= "/*if(claveigual(document.getElementById('Usu_Pal'),document.getElementById('Usu_Pal2'))!=false){*/ IsChk(this.form) /*}*/; ">
		           <i class="icon-book icon-white"></i>
		           <span>Guardar</span>
		           </button>
		          
			</td>
	   </tr>
   </table>
   <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
   <input type="hidden" name="Usu_Cod" id="Usu_Cod" value="<?Php echo $_POST['Usu_Cod'];?>"/>  
</form>
<?Php } //fin del if(isset($hdd_aux))

		/* Parametro de la busqueda por fecha en compras */
		/* Control para setear el arreglo solo cuando tenga valores*/
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}//Fin del if ($anulada > 0)
		?><br>
 <?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>        

			</td>
		</tr>
	</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/adm_par_usuarios.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	 
</body>
</html>
<?Php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>