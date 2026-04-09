<?	
/* 
Alias:	Modificar
Descripción: Permite modificar los departamentos y cargos de la insititucion
Fecha de actualización:	2011-02-24
Desarrollador:	Lewis Chimarro
Actualizado por: Jose Cumbicos
Fecha 2016-03-28

*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_organigrama.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/*Creacion del Objeto de conexion*/
$obBD_conexion = new Class_Log_Conexion_rhu($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas*/
$obBD_con1 =  new Class_Log_Datos_rhu;
/* Creación del objeto para evitar el reenvio*/
$thisPost = new Post_Block;

/* Grabado de un departamento nuevo en algun nodo del plan */
if (isset($ndepartamento))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{			
		/**Inicio de la transaccion**/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/* Inserción de distributivo */
		$obBD_con1->grabarv_registros(sentencias_rhu(671, $obBD_con1->parametros($Dep_Des.'*'.$Hdd_Dep_Cod.'*'.$Dep_Cdc)),$obBD_conexion->conexion);	
		/*Fin del la transaccion*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}

/* Grabado de una seccion nuevo en algun nodo del plan */
if (isset($nseccion))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{			
		/**Inicio de la transaccion**/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/* Inserción de distributivo */
		$obBD_con1->grabarv_registros(sentencias_rhu(709, $obBD_con1->parametros($Sec_Des.'*'.$Hdd_Sec_Cod)),$obBD_conexion->conexion);	
		/*Fin del la transaccion*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}

/* Grabado de un departamento nuevo en algun nodo del plan */
if (isset($ncargo))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{			
		/**Inicio de la transaccion**/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/* Inserción de distributivo */
		$obBD_con1->grabarv_registros(sentencias_rhu(673, $obBD_con1->parametros($Tic_Des.'*'.$Hdd_Tic_Cod)),$obBD_conexion->conexion);	
		/*Fin del la transaccion*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}

/* Ingresa cuando se desea editar un departamento */
if (isset($Dep_Cod))
{
	/* Consulta el detalle de los departamentos */
	$rs_departamento = $obBD_con1->consulta(sentencias_rhu(26, $obBD_con1->parametros($Dep_Cod)), $obBD_conexion->conexion);
	$row_rs_departamento = $obBD_con1->registros();
	$total_rs_departamento = $obBD_con1->numregistros();	
?>
	<div id="id_departamento">
	<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form4" id="form4">        
	<FIELDSET>    
	<LEGEND>
	<label class="Titulos2">Datos a registrar </label>
	</LEGEND>
    <?php  //Creacion del campo REPOST
	$thisPost->startPost(); ?>    
    	<input name="np" type="hidden" id="np" value="<?php echo $np;?>">
        <input name="Hdd_Dep_Cod" type="hidden" id="Hdd_Dep_Cod" value="<?php echo $Dep_Cod;?>">
        <input name="ndepartamento" type="hidden" id="ndepartamento" value="ndepartamento">	
        <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
	    <tr>
	        <td width="13%" class="Etiqueta1"><span class="Asterisco">*</span> Departamento:&nbsp;</td>
	        <td width="87%" class="LetraNegra"><input name="Dep_Des" type="text" id="Dep_Des" size="50" maxlength="50" value="<?Php echo $row_rs_departamento['Dep_Des']; ?>">&nbsp;</td>
        </tr>	      
	    <tr>
	        <td class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo</td>
	        <td class="LetraNegra"><input name="Dep_Cdc" type="text" id="Dep_Cdc" size="50" maxlength="50" value="<?Php echo $row_rs_departamento['Dep_Cdc']; ?>"></td>
	    </tr>
    	</table>
	</FIELDSET>
	<br>
    <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Dep_Des', 1)"> <i class="icon-book icon-white"></i> <span>Guardar</span></button>      
	  </form> 
	  </div>
<?Php
exit();
}//Fin del if (isset($Dep_Cod))


if (isset($Tic_Cod))
{
	/* Consulta el detalle de los departamentos */
	$rs_cargo = $obBD_con1->consulta(sentencias_rhu(672, $obBD_con1->parametros($Tic_Cod)), $obBD_conexion->conexion);
	$row_rs_cargo = $obBD_con1->registros();
	$total_rs_cargo = $obBD_con1->numregistros();

?>
    <div id="id_cargo">
      <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form4" id="form">
        <FIELDSET>
          <LEGEND>
            <label class="Titulos2">Datos a registrar </label>
            </LEGEND>
          <?php  //Creacion del campo REPOST
			$thisPost->startPost(); ?>
          <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="13%" class="Etiqueta1"><span class="Asterisco">*</span> Cargo:&nbsp;2</td>
              <td width="87%" class="LetraNegra"><input name="Tic_Des" type="text" id="Tic_Des" size="50" maxlength="50" value="<?Php echo $row_rs_cargo['Tic_Des']; ?>">
                &nbsp;</td>
            </tr>
            <tr>
              <td class="LetraNegra">&nbsp;</td>
              <td class="LetraNegra">
                <input name="np" type="hidden" id="np" value="<?php echo $np;?>">
	            <input name="Hdd_Tic_Cod" type="hidden" id="Hdd_Tic_Cod" value="<?php echo $Tic_Cod;?>">                
                <input name="ncargo" type="hidden" id="ncargo" value="ncargo">
                <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
                </td>
            </tr>
          </table>
        </FIELDSET>
        <br>
        <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Tic_Des', 1)"> <i class="icon-book icon-white"></i> <span>Guardar</span></button>
      </form>
    </div>
    	<?  
exit();
}//Fin del if (isset($Tic_Cod))
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script language="javascript" src="../VALIDACIONES/rhu_val_organigrama.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
        <link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />					
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<?
if (!isset($txt_busqueda))
{
	/* Cargado de las areas*/
	$rs_areas = $obBD_con1->consulta(sentencias_rhu(664, $obBD_con1->parametros($Ses_Emp_Cod)), $obBD_conexion->conexion);
	$row_rs_areas = $obBD_con1->registros();
}
else
{
	/* Consulta un area especifica cuando selecciona el area */
	$rs_areas = $obBD_con1->consulta(sentencias_rhu(663, $obBD_con1->parametros($txt_busqueda)), $obBD_conexion->conexion);
	$row_rs_areas = $obBD_con1->registros();
	
}

/* Cargado de los nodos - Codigo Empresa, Nodo Padre */
if (isset($np))
{
	$rs_nodos = $obBD_con1->consulta(sentencias_rhu(665, $obBD_con1->parametros($txt_busqueda.'*'.$np)), $obBD_conexion->conexion);
	$row_rs_nodos = $obBD_con1->registros();
	$total_rs_nodos = $obBD_con1->numregistros();
}
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Modificaci&oacute;n de Departamentos &amp; cargos</td>
  </tr>
	<tr>
      <td height="389" align="left" valign="top">
	<?
	if (!isset($txt_busqueda) && !isset($np)) 
	{	
	?>
    <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"> 
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Seleccione el &aacute;rea:</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="13%" class="Etiqueta1">Area:&nbsp;</td>
      <td width="87%">          
        <select name="txt_busqueda" id="txt_busqueda">
          <option value="">Seleccione...</option>
          <?php  do {  ?>
	          <option value="<?php echo $row_rs_areas['Are_Cod'];?>"><?php echo $row_rs_areas['Are_Des'];?></option>
          <?php
				} while ($row_rs_areas = $obBD_con1->fetch_assoc($rs_areas)); ?>
          </select>
          <input type="hidden" name="np" id="np" value="0">
      </td>
      </tr>
  	</table>
	</FIELDSET>
    <br>
    <input name="btn_buscar" type="button" class="Boton_Aceptar" title="Aceptar" id="btn_buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)" value="Aceptar">
    </form>
	<? }//Fin del if (!isset($txt_busqueda) && !isset($np)) 

	if (isset($txt_busqueda)) {
	?>
	<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultado de la Busqueda</label>
    </LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" >
	  <tr>
	    <td width="13%" class="Etiqueta1" >Area:&nbsp;</td>
	    <td width="87%" class="LetraNegra" ><?Php echo $row_rs_areas['Are_Des']; ?></td>
	    </tr>
	  <tr>
	    <td class="Etiqueta1" >&nbsp;</td>
	    <td class="LetraNegra" >&nbsp;</td>
	    </tr>
	  </table>

	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader02">      
	<thead>  
      <tr>
	    <th width="8%"><strong>C&oacute;d. Int.</strong></th>
	    <th width="92%">
	      <?	
		/* Consulta la ruta del departamento */
		if ($np==0) 
		{
			echo "<strong>INICIO</strong>";
			$separador='';
		} 
		else 
		{
			$rs_direc = $obBD_con1->consulta(sentencias_rhu(667, $obBD_con1->parametros($txt_busqueda.'*'.$np)), $obBD_conexion->conexion);
			$row_rs_direc = $obBD_con1->registros();
			$total_rs_direc = $obBD_con1->numregistros();
			echo "Ud. está dentro de <strong>".strtoupper($row_rs_direc['Dep_Des'])."</strong>";
			$separador='.';
		}//Fin del if ($np==0)  
		?>
	      - Departamentos 
       </th>
	   <th width="2%">&nbsp;</th>
	   <th width="2%">&nbsp;</th>
	   </tr>
       </thead>
       <tbody>     
	  <?Php
       /* Consulta los cargos del Departamento */
		$rs_cargos = $obBD_con1->consulta(sentencias_rhu(670, $obBD_con1->parametros($np)), $obBD_conexion->conexion);
		$row_rs_cargos = $obBD_con1->registros();
		$total_rs_cargos = $obBD_con1->numregistros(); 
		
		/* Consulta las secciones del Departamento */
		$rs_secciones = $obBD_con1->consulta(sentencias_rhu(707, $obBD_con1->parametros($np)), $obBD_conexion->conexion);
		$row_rs_secciones = $obBD_con1->registros();
		$total_rs_secciones = $obBD_con1->numregistros();
		
	/* Consulta las secciones del departamento seleccionado, que se detalla el nombre en la cabecera */
	if ($total_rs_secciones > 0 )		
	{ 
		do{
	?>
	  <tr <?Php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
	    <td align="center" >&nbsp;</td>
	    <td> 
			<?Php	
               if ($row_rs_secciones['Sec_Est'] == 'I') 
			   { 
			   		$color = '#FF0000'; 
					$anulada++;
				} 
				else
				{
					$color = '';	
				}
               echo '<font color="'.$color.'"><img src="../../mascaras/model1/imagenes/32x32/view_choose.png" width="22" height="25" title="Departamento">'.
                    "Cód. Int.:".$row_rs_secciones['Sec_Cod'].' - '.$row_rs_secciones['Sec_Des']."</font><br>"; ?>
               </td>
        <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
	    <td align="center"><input name="img2"  id="img2" type="image" src="../../mascaras/model1/imagenes/32x32/editar.png" width="18" height="18" title="Editar" style="cursor:pointer">
        <input type="hidden" name="Sec_Cod" id="Tic_Cod" value="<?php echo $row_rs_secciones['Sec_Cod'];?>"/>
        <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>        <input type="hidden" name="np" id="np" value="<?php echo $row_rs_direc['Dep_Cod']+0;?>"/>                    
		</td>
		</form>
	    <td align="center">&nbsp;</td>
	   </tr>
          <?
        } while ($row_rs_secciones = $obBD_con1->fetch_assoc($rs_secciones)); ?>
       <?Php
	}//Fin del if ($total_rs_secciones > 0) 
	
	/* Consulta los cargos del departamento seleccionado, que se detalla el nombre en la cabecera */
	if ($total_rs_cargos > 0)		
	{ 
       do{	
	?>
	  <tr <?Php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
	    <td align="center">&nbsp;</td>
	    <td>	  
			 <?Php	
			   if ($row_rs_cargos['Tic_Est'] == 'I') 
			   { 
			   		$color = '#FF0000'; 
					$anulada++;
				} 
				else
				{
					$color = '';	
				}
               echo '<font color="'.$color.'"><img src="../../mascaras/model1/imagenes/32x32/persona.gif" width="22" height="25" title="Departamento">'.
                    "Cód. Int.:".$row_rs_cargos['Tic_Cod'].' - '.$row_rs_cargos['Tic_Des']."</font><br>"; ?>
        </td>
        <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
	    <td align="center">
        <button type="button" class="btn btn-primary btn-mini" name="button1" id="button1" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?Tic_Cod=<?php echo $row_rs_cargos['Tic_Cod'];?>&txt_busqueda=<?php echo $txt_busqueda;?>&np=<? echo $row_rs_direc['Dep_Cod']+0;?>&txt_busqueda=<? echo $txt_busqueda;?>','ajax_modal')"><i class=" icon-edit icon-white"></i></button>
        
       
</td>
		</form>
	    <td align="center">&nbsp;</td>
	   </tr>
      <?Php
      } while ($row_rs_cargos = $obBD_con1->fetch_assoc($rs_cargos)); 	  
	}//Fin del if ($total_rs_cargos > 0) 
	
   /* Valida si existen nodos */
   if ($total_rs_nodos > 0) 
   { 
	  do { 	 ?>         
	  <tr <?Php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
            <td align="center"><? echo $row_rs_nodos['Dep_Cod']; ?>
            </td>
            <td>
            <?Php
			if ($row_rs_nodos['Dep_Est'] == 'I') 
			   { 
					$color_d = '#FF0000'; 
					$anulada++;
				} 
				else
				{
					$color_d = '';	
				}				
			?>
            <img src="../../mascaras/model1/imagenes/32x32/departamento.png" width="22" height="25" title="Departamento"><? echo "<font color='".$color_d."'><strong>".$row_rs_nodos['Dep_Des']."</strong></font>"; 
			/* Consulta los cargos del Departamento */
			$rs_cargos = $obBD_con1->consulta(sentencias_rhu(670, $obBD_con1->parametros($row_rs_nodos['Dep_Cod'])), $obBD_conexion->conexion);
			$row_rs_cargos = $obBD_con1->registros();
			$total_rs_cargos = $obBD_con1->numregistros();	  

			/* Consulta las secciones del Departamento */
			$rs_secciones = $obBD_con1->consulta(sentencias_rhu(707, $obBD_con1->parametros($row_rs_nodos['Dep_Cod'])), $obBD_conexion->conexion);
			$row_rs_secciones = $obBD_con1->registros();
			$total_rs_secciones = $obBD_con1->numregistros(); 
			
			if ($total_rs_cargos > 0)
			{ 		
				do{
				   echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="../../mascaras/model1/imagenes/32x32/persona.gif" width="22" height="25" title="Departamento">'.
						"Cód. Int.:".$row_rs_cargos['Tic_Cod'].' - '.$row_rs_cargos['Tic_Des']; ?>
				  <?
			   } while ($row_rs_cargos = $obBD_con1->fetch_assoc($rs_cargos));
		  }//Fin del if ($total_rs_cargos > 0)  
		  
		  if ($total_rs_secciones > 0)
			{ 		
				do{
				   echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="../../mascaras/model1/imagenes/32x32/view_choose.png" width="22" height="25" title="Departamento">'.
						"Cód. Int.:".$row_rs_secciones['Sec_Cod'].' - '.$row_rs_secciones['Sec_Des']; ?>
				  <?
			   } while ($row_rs_secciones = $obBD_con1->fetch_assoc($rs_secciones));
		   }//Fin del if ($total_rs_secciones > 0)
		  ?>                 
          </td>
          <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
            <td align="center">
            <?Php 
			if ($row_rs_nodos['Dep_Est'] == 'A') 
			   { ?>			
            	<button type="button" class="btn btn-primary btn-mini" name="button1" id="button1" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?Dep_Cod=<?php echo $row_rs_nodos['Dep_Cod'];?>&txt_busqueda=<?php echo $txt_busqueda;?>&np=<? echo $row_rs_direc['Dep_Cod']+0;?>&txt_busqueda=<? echo $txt_busqueda;?>','ajax_modal')"><i class=" icon-edit icon-white"></i></button>
                
                <!--<input name="img2"  id="img2" type="image" src="../../mascaras/model1/imagenes/32x32/editar.png" width="18" height="18" title="Editar" style="cursor:pointer">
                <input type="hidden" name="Dep_Cod" id="Dep_Cod" value="<?php //echo $row_rs_nodos['Dep_Cod'];?>"/>
				<input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php //echo $txt_busqueda;?>"/> 
                <input type="hidden" name="np" id="np" value="<?php //echo $row_rs_direc['Dep_Cod']+0;?>"/>-->	 
			<?Php
               }
            ?>&nbsp;                	                                             
            </td>
           </form> 
            <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
            <td align="center">
			<?Php 
			if ($row_rs_nodos['Dep_Est'] == 'A') 
			   { ?>            
            <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
            <input type="hidden" name="np" id="np" value="<?php echo $row_rs_nodos['Dep_Cod'];?>"/>	
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
			<?Php
               }
            ?>&nbsp;                	                                                         
            </td>
            </form> 
        </tr>
<?Php		
	  } while ($row_rs_nodos = $obBD_con1->fetch_assoc($rs_nodos));
   } 
   else 
   { ?>            			
	
	  <tr><td>&nbsp;</td>
	    <td align="center"><?Php echo error_alerta("¡No hay <strong>DEPARTAMENTOS</strong> que mostrar!", 1); ?></td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    </tr>
<? }?>
	</tbody>	
	</table>
    </FIELDSET> 
<?		// Link para volver atrás
$rs_direca = $obBD_con1->consulta(sentencias_rhu(666, $obBD_con1->parametros($txt_busqueda.'*'.$np)), $obBD_conexion->conexion);
$row_rs_direca = $obBD_con1->registros(); 
$total_rs_direca = $obBD_con1->numregistros()
?>
        	<br>
<table border="0" cellpadding="0" cellspacing="0">
			  <tr>
		      <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3" id="form3">
			    <td align="right">
				<button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*np', '<? echo $txt_busqueda.'*'.$row_rs_direca['Dep_Rec'];?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
		        </td>
              </form>			    
		      </tr>
	    </table>
 <?php 
		/* Parametro de la busqueda por fecha en compras */
		/* Control para setear el arreglo solo cuando tenga valores*/
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}//Fin del if ($anulada > 0)
		?><br>
 <?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>        
<?Php


if (isset($Sec_Cod))
{
	/* Consulta el detalle de las secciones */
	$rs_seccion = $obBD_con1->consulta(sentencias_rhu(708, $obBD_con1->parametros($Sec_Cod)), $obBD_conexion->conexion);
	$row_rs_seccion = $obBD_con1->registros();
	$total_rs_seccion = $obBD_con1->numregistros();

?>
 <div id="id_seccion">
      <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form4" id="form">
        <FIELDSET>
          <LEGEND>
            <label class="Titulos2">Datos a registrar </label>
            </LEGEND>
          <?php  //Creacion del campo REPOST
			$thisPost->startPost(); ?>
          <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="13%" class="Etiqueta1"><span class="Asterisco">*</span> Sección:&nbsp;1</td>
              <td width="87%" class="LetraNegra"><input name="Sec_Des" type="text" id="Sec_Des" size="50" maxlength="50" value="<?Php echo $row_rs_seccion['Sec_Des']; ?>">
                &nbsp;</td>
            </tr>
            <tr>
              <td class="LetraNegra">&nbsp;</td>
              <td class="LetraNegra">
                <input name="np" type="hidden" id="np" value="<? echo $np; ?>">
                <input name="Hdd_Sec_Cod" type="hidden" id="Hdd_Sec_Cod" value="<?php echo $Sec_Cod;?>">
                <input name="nseccion" type="hidden" id="nseccion" value="nseccion">
                <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
                </td>
            </tr>
          </table>
        </FIELDSET>
        <br>
        <input name="btn_save" type="button" class="Boton_Guardar" title="Guardar" id="btn_save" onClick="validar_requeridos(this.form, 'Sec_Des', 1)" value="Guardar">
      </form>
    </div>

<?
}//Fin del if (isset($Sec_Cod))


}//Fin del if ($total_rs_nodos > 0)  ?>    
   </td>
  </tr>
</table>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">		
	   <div id="ajax_modal">
        	 
       </div>  
</div>	  
</BODY></HTML>
<?php
/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>