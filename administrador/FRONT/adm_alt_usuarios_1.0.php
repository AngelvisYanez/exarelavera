<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?Php 
/**
 * Permite registrar un nuevo Usuario
 *
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualizaci�n:	2012-04-18
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

if(isset($_POST['hdd_volver']))
{
	unset($_POST['hdd_save']);
	unset($_POST['Prs_Cod']);
}
	 
	 
if (isset($_POST['hdd_save']))
{
	if ($thisPost->postBlock($_POST['postID']))
	{
	/**
	 * Inicio de Transacci�n
	 */
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	
	/**
	 * Parametros de usuario a guardar
	 * @var string
	 */
	$param_usuario = $_POST['Prs_Cod'].'*'.$_POST['Suc_Cod'].'*'.$_POST['Prs_Ced'].'*'.$_POST['Usu_Pal'].'**A*'.$_POST['Usu_Cad'];
	
	/**
	 * Grabar los registros
	 */
	$obBD_con1->operacionobBD(12, $param_usuario, $obBD_conexion);

	/**
	 * Codigo de autoincremento de inserccion del usuario
	 * @var number
	 */
	$Usu_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);	
	
	$Per_Cod = $_POST['perfil'];
	$n = count($Per_Cod);
	$i = 0;
	 
	while ($i < $n)
	{
		/**
		 * Guardado de perfiles
		 */
		$obBD_con1->operacionobBD(13, $Usu_Cod.'*'.$Per_Cod[$i], $obBD_conexion);
		$i++;
	}
	
	/**
	 * grabar auditoria
	 */
	//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
	
	/* Cierre de la transacci�n */
	$obBD_con1->fin_transaccion($obBD_conexion->conexion);

		/**
		* Conexion a la base de datos maestra
		*/
		$obBD_conexion_master = new Class_Log_Conexion_Admu;

		/**
		 * objeto para consultas
		 * @var Class_Log_Datos_Adm
		 */
		$obBD_con_master =  new Class_Log_Datos_Admu;

		/**
		* Transacci�n Anidada
		*/
		$obBD_con_master->inicio_transaccion($obBD_conexion_master->conexion);		
		
		/**
		* Selecciona la base de datos asignada a una empresa
		*/
		$row_data = $obBD_con_master->getRowConsulta(35, $Ses_Emp_Cod, $obBD_conexion_master);
	
		/**
		* Inserta el usuario en la base de datos master
		*/
		$obBD_con_master->operacionobBD(36, $Ses_Suc_Cod.'*'.$row_data['Dat_Cod'].'*'.$_POST['Prs_Ced'], $obBD_conexion_master);

		/**
		* Cierre de la transacci�n 
		*/
		$obBD_con_master->fin_transaccion($obBD_conexion_master->conexion);

	
	unset($_POST['Prs_Cod']);
	unset($_POST['hdd_save']);
 }			
}// fin del if ($thisPost->postBlock($_POST['postID'])) */
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script type="text/javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
  <td height="10">&raquo; Insertar de usuarios</td>
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

if(isset($_POST['txt_busqueda']) && !isset($_POST['Prs_Cod']))
{
	/**
	 * numero de sucursales
	 * si hay mas de uno entrara y en el combo de sucursales solo
	 * mostrara en cual sucursal le falta crearle un usuario
	 */
	$count_suc = $obBD_con1->getRowConsulta(25, $Ses_Emp_Cod, $obBD_conexion);
?>		    
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
      <tr>
        <th width="5%" >Cod. Int. </th>
        <th width="8%" >Usuarios</th>
        <th width="81%" >Apellidos y Nombres </th>
		<th width="3%" >&nbsp;</th>
      </tr>
     </thead> 
     <tbody>
<?php
	/**
	 * personas encontradas
	 * @var array
	 */
	$Arr_Persona = $obBD_con1->searchPersona($Ses_Emp_Cod, $_POST['txt_busqueda'], $_POST['op_opciones'], $obBD_conexion);
	
	foreach($Arr_Persona as $row)
	{
		/**
		 * Color de la fuente a mostrar en el resultado
		 * @var string
		 */
		$rojo='';
		
		if($row['Estado']=='I')
		{
			$rojo='#FF0000'; 
			$anulada++;
		}
?>
	<tr>
		<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row['Prs_Cod']; ?></FONT></td>
		<td><FONT COLOR="<?php echo $rojo;?>">&nbsp;<?Php echo $row['Prs_Ced']; ?></FONT></td>
		<td>&nbsp;<FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'],'#FFFF00', 1);?></FONT></td>
		<td align="center">
<?php 
		if($row['Estado']=='I')
		{
?>
		<img src="../../mascaras/model1/imagenes/32x32/encrypted.png">
<?php
		}
		else
		{
			$count_usu = $obBD_con1->getRowConsulta(24, $row['Prs_Ced'].'*'.$Ses_Emp_Cod, $obBD_conexion);
			if($count_suc['count'] == $count_usu['count']){
				?>
				<img src="../../mascaras/model1/imagenes/32x32/ayuda.png" width="25" height="25" title="Ya tiene usuarios registrados">
				<?php
			}else{
			?>
				<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
					<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
		           		<i class=" icon-arrow-right icon-white"></i>
		           	</button>					
		            <input type="hidden" name="Prs_Cod" id="Prs_Cod" value="<?Php echo $row['Prs_Cod'];?>">
					<input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?Php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>"/>
					<input type="hidden" name="op_opciones" id="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8')?>">
					<input type="hidden" name="id_tabla" id="id_tabla" value="<?php echo $obBD_con1->id_tabla?>">
				</form>
			<?php
			}
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
<?php
echo barra_estado(count($Arr_Persona));
}

if (isset($_POST['Prs_Cod'])) 
{ 
	$Row_Persona = $obBD_con1->getRowConsulta(10, $_POST['Prs_Cod'], $obBD_conexion);
?> 	  
<form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
<?php 
		$thisPost->startPost();
?>		  
<FIELDSET>
<LEGEND>
	<label class="Titulos2">Del Usuario</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="16%" align="justify" class="Etiqueta1"><span class="Asterisco">*</span>Sucursal:</td>
	  <td colspan="2" class="LetraNegra">
		  <select name="Suc_Cod" id="Suc_Cod" style="text-transform:uppercase" >
			<?Php 
			/**
			 * Arreglo de sucursales resultado de busqueda
			 * @var array
			 */
			$Arr_Sucursales = $obBD_con1->getArrayConsulta(26, $Ses_Emp_Cod.'*'.$Row_Persona['Prs_Ced'], $obBD_conexion);
			
			foreach($Arr_Sucursales as $row)
			{ 
			?>
			<option value="<?Php echo $row['Suc_Cod']; ?>"><?Php echo $row['Suc_Des']; ?> </option>
			<?Php 
			}
			?>
		  </select>
		</td>
	</tr>
	<tr>
        <td class="Etiqueta1">Usuario: </td>
        	<td colspan="2" class="LetraNegra">&nbsp;
        	<input name="Prs_Cod" id="Prs_Cod" type="hidden" value="<?Php echo $Row_Persona['Prs_Cod'];?>">
			<input name="Prs_Ced" id="Prs_Ced" type="hidden" value="<?Php echo $Row_Persona['Prs_Ced'];?>">
			<?Php echo $Row_Persona['Prs_Ced']; ?>
		</td>
    </tr>
    <tr>
       <td class="Etiqueta1">Apellidos y Nombres: </td>
       <td colspan="2" class="LetraNegra">&nbsp;<?Php echo $Row_Persona['Prs_Ape']." ".$Row_Persona['Prs_Nom']; ?></td>
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
      <td colspan="2">&nbsp;<select name="Usu_Cad" id="Usu_Cad">
	  	<option value="S">Si</option>
	  	<option value="N">No</option>		
      </select>
      </td>
    </tr>	  
</table>
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Del Perfil</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">      
      <tr>
        <td width="14%" class="Etiqueta1" valign="top">Perfiles Activos: </td>
        <td width="86%" align="left">
		  <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <?php
          $rs_consulta2 = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
          
          for($ch = 0; $ch < count($rs_consulta2); ) {
			  ?>
            <tr align="left">
              <?php for($i = 0; $i < 3; $i++){
		         	if(isset($rs_consulta2[$ch + $i]))
		            {
			        	$row_rs_consulta2 = $rs_consulta2[$ch + $i];
				   		if($row_rs_consulta2['Per_Est']=='I')
		  	  			{ 
		  	  				$rojo='#FF0000'; $anulada++; 
		  	  			}
		  	  			else
		  	  			{
		  	  				$rojo='';
		  	  			}	
					      ?>
			              <td width="30%" colspan="3" <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "LetraNegra");?> class="LetraNegra">
			              	<span id="span_foco" >
			              		<input name="perfil[]" id="perfil[]" type="checkbox"  value="<?php echo $row_rs_consulta2['Per_Cod']; ?>">&nbsp;
			              		<FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_consulta2['Per_Des']; ?>
			              		</FONT>
			              	</span>
						  </td>
		              <?php 
		          }
		      }?>
            </tr>
          <?php 
          	$ch = $ch + $i;
		 }
		 ?>
         </table>	
		 </td>
      </tr>
  </table>
</FIELDSET>
<br>
   <table width="300" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td width="34%">
  <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*hdd_volver', '<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8').'*'.htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8').'*'.'1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atras&nbsp;&nbsp;</span>
       		 </button>
  </td>
    <td width="66%" height="23">
     <button type="button" class="btn btn-primary start" title="Guardar" onClick= "if(claveigual(document.getElementById('Usu_Pal'),document.getElementById('Usu_Pal2'))!=false){ IsChk2(this.form) }; ">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
     </button>
	</td>
   </tr>
   </table>
   <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
</form>
<?Php 
}

if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}
?>
<br>
 <?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>        
	</td>
  </tr>
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/adm_par_usuarios.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	 
</BODY>
</HTML>
<?Php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>