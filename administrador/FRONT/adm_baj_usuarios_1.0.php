<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?Php
/**
 * Descripci�n: Permite modificar la clave y los perfiles del usuario
 * Fecha de actualizaci�n:	2011-03-14 
 * Desarrollador:	Jose Cumbicos 
 * Fecha de actualizaci�n:	2011-03-19 
 * Desarrollador:	Lewis Chimarro
 * Fecha de actualizaci�n:	2014-05-21 
 * Desarrollador:	lewis.chimarro
 */	
require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_usuarios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		 
require_once('../../Librerias/postclass.php');
	    
/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Admu($Ses_Dat_Dis);
	  
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Admu;
	  
/**
 * Creaci�n del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;

/**
 * Evitar el reenvio de formularios 
 */
if ($thisPost->postBlock($_POST['postID'])) 
{ 
	if (isset($hdd_save) && !isset($hdd_atras))
	{
		/**
		 * Inicio de la transacci�n 
		 */
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
				
		/**
		 * Inserci�n de datos de la inscripci�n 
		 */
		$obBD_con1->operacionobBD(28, $Usu_Est2.'*'.$codigo, $obBD_conexion);
		
		$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $_SESSION['Ses_Usu_Cod'], $obBD_conexion);
		
		/**
		 * Cierre de la transacci�n 
		 */
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		unset($codigo);
	}			
}
?>
<HTML>
	<head>
		<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
		<TITLE><?Php echo "Usuario Anular [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script language="javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	</head>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td>&raquo; Activar / Inactivar  usuarios</td>
	</tr>
	<tr height="400">	  	
        <td valign="top">                  
		<form name="Buscador" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
			<?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
			<input type="hidden" name="hdd_aux" id="hdd_aux" value="">
			<input type="hidden" name="hdd_atras" id="hdd_atras" value="1">			
		</form> 		
<? if(isset($txt_busqueda)){ ?> 
	<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
      <tr>
        <th width="5%">Cod.Int.</th>
		<th width="9%">Cédula</th>
        <th>Apellidos y Nombres </th>
        <th>Sucursal</th>
        <th width="2%">&nbsp;</th>
      </tr>
     </thead>
     <tbody>
      <?Php 
      /**
       * personas encontradas
       * @var array
       */
      $Arr_Persona = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == "d"? 14 : 15,$Ses_Emp_Cod.'*'.$_POST['txt_busqueda'], $obBD_conexion);
	  
	  foreach($Arr_Persona as $row_rs_buscar) {
	  	  if($row_rs_buscar['Usu_Est']=='I'){ $rojo='#FF0000'; $anulada++; }else{$rojo='';}	
	  ?>
      <tr>
        <td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Usu_Cod']; ?></FONT></td>
		<td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced']; ?></FONT></td>
        <td align="left">&nbsp;<FONT COLOR="<? echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom'],'#FFFF00', 1); ?></FONT></td>
        <td><FONT COLOR="<? echo $rojo;?>"><?php echo $row_rs_buscar['Suc_Des'];?></FONT></td>
		<td align="center">
			<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="frml">
				<button type='button' class='btn btn-success btn-mini' title="Elegir" onclick="this.form.submit();"><i class='icon-arrow-right icon-white'></i></button>
				<input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Usu_Cod'];?>"/>
				<input type="hidden" name="volver_busqueda" id="volver_txt_busqueda" value="<?Php echo $txt_busqueda;?>"/>
				<input type="hidden" name="volver_opciones" id="volver_opciones" value="<? echo $op_opciones?>">
			</form>		
		</td>		
      </tr>
      <?Php }?>
    </tbody>
    </table>
    </FIELDSET> 
<? 
	echo barra_estado(count($Arr_Persona));
} ?>
	       
<?Php if (isset($codigo) && !isset($hdd_atras)) {?>		  
 <form action="<?Php $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
<?
	$thisPost->startPost();
	/**
	 * consulta de usuarios
	 */
	$row_rs_consulta = $obBD_con1->getRowConsulta(27, $codigo, $obBD_conexion);
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a modificar </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">     
      <tr>
        <td class="Etiqueta1">Sucursal:</td>
        <td class="LetraNegra">&nbsp;<?Php echo $row_rs_consulta['Suc_Des']; ?></td>
      </tr>
      <tr>
        <td width="15%" class="Etiqueta1">Usuario: </td>
        <td width="85%" class="LetraNegra">&nbsp;<?Php echo $row_rs_consulta['Usu_Ced']; ?>
          <input name="codigo" type="hidden" value="<?Php echo $row_rs_consulta['Usu_Cod'];?>" readonly="true">		</td>
      </tr>
      <tr>
        <td class="Etiqueta1">Apellidos y Nombres: </td>
        <td class="LetraNegra">&nbsp;<?Php echo $row_rs_consulta['Prs_Ape']." ".$row_rs_consulta['Prs_Nom']; ?></td>
      </tr>
	  
      <tr>
        <td class="Etiqueta1">Estado de la Cuenta: </td>
        <td>
		<select name="Usu_Est2" id="Usu_Est2">
        <?php 
		if (isset($codigo) && $row_rs_consulta >0)
		{
			$row_rs_cod = array ("A","I");
			$row_rs_des = array ("Activo", "Inactivo");
			for ($i=0;$i<count($row_rs_cod);$i++) 
			{  
	  	?>
        <option <?Php if($row_rs_cod[$i] == $row_rs_consulta['Usu_Est'] ){echo "selected";}?> value="<?php echo $row_rs_cod[$i]?>"><?php echo $row_rs_des[$i]?></option>
        <?php
			}
		}
		?>
        </select></td>
      </tr>      
  </table>
</FIELDSET>
<br>
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
        <td width="110">
            <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_atras"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*1'; ?>')"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
        </td>
        <td width="110">
             <button type="button" class="btn btn-primary fileinput-button" title="Actualizar" onClick="validar_requeridos(this.form, 'Usu_Est2', 1)"><i class=" icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span></button>
             <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
        </td>
    </tr>
</table>
</form>
<?Php 
} 
		/**
		 * Parametro de la busqueda por fecha en compras
		 * Control para setear el arreglo solo cuando tenga valores
		 */
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}
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