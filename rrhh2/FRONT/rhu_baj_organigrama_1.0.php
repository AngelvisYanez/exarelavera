<?	
/* 
Alias:	Eliminar
Descripción: Permite dar de baja a los departamentos y cargos de la insititucion
Fecha de actualización:	2011-02-24
Desarrollador:	Lewis Chimarro
*MULTIEMPRESAS = SI *
*/

$Ses_Emp_Cod = 1;
//require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/logica.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/*Creacion del Objeto de conexion*/
$obBD_conexion = new Class_Log_Conexion_Rhu;
/* Cracion del objeto mysql para las consultas*/
$obBD_con1 =  new Class_Log_Datos_Rhu;
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
		$obBD_con1->grabarv_registros(sentencias_rhu(674, $obBD_con1->parametros($Dep_Est.'*'.$Hdd_Dep_Cod)),$obBD_conexion->conexion);	
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
		$obBD_con1->grabarv_registros(sentencias_rhu(710, $obBD_con1->parametros($Sec_Est.'*'.$Hdd_Sec_Cod)),$obBD_conexion->conexion);	
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
		$obBD_con1->grabarv_registros(sentencias_rhu(675, $obBD_con1->parametros($Tic_Est.'*'.$Hdd_Tic_Cod)),$obBD_conexion->conexion);	
		/*Fin del la transaccion*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">		
		<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">								
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>		
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
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
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Activaci&oacute;n / Inactivaci&oacute;n de Departamentos &amp; cargos</td>
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
	<br>
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

	<table width="100%" border="1" cellpadding="0" cellspacing="0">      
	  <tr class="Cabecera1">
	    <td width="4%"><strong>C&oacute;d. Int.</strong></td>
	    <td width="92%">
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
       </td>
	    <td width="2%">&nbsp;</td>
	    <td width="2%">&nbsp;</td>
	    </tr>    
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
	    <td align="center">
        <input name="img2"  id="img2" type="image" src="../../mascaras/model1/imagenes/32x32/eliminar.jpg" width="18" height="18" title="Editar" style="cursor:pointer">
                <input type="hidden" name="Sec_Cod" id="Tic_Cod" value="<?php echo $row_rs_secciones['Sec_Cod'];?>"/>
				<input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/> 
                <input type="hidden" name="np" id="np" value="<?php echo $row_rs_direc['Dep_Cod']+0;?>"/>
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
               echo '<font color="'.$color.'"><img src="../../mascaras/model1/imagenes/32x32/persona.gif" width="22" height="25" title="Cargo">'.
                    "Cód. Int.:".$row_rs_cargos['Tic_Cod'].' - '.$row_rs_cargos['Tic_Des']."</font><br>"; ?>

        </td>
        <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
	    <td align="center">
        <input name="img2"  id="img2" type="image" src="../../mascaras/model1/imagenes/32x32/eliminar.jpg" width="18" height="18" title="Editar" style="cursor:pointer">
                <input type="hidden" name="Tic_Cod" id="Tic_Cod" value="<?php echo $row_rs_cargos['Tic_Cod'];?>"/>
				<input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/> 
                <input type="hidden" name="np" id="np" value="<?php echo $row_rs_direc['Dep_Cod']+0;?>"/>
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
				   echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="../../mascaras/model1/imagenes/32x32/persona.gif" width="22" height="25" title="Cargo">'.
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
            	<input name="img2"  id="img2" type="image" src="../../mascaras/model1/imagenes/32x32/eliminar.jpg" width="18" height="18" title="Editar" style="cursor:pointer">
                <input type="hidden" name="Dep_Cod" id="Dep_Cod" value="<?php echo $row_rs_nodos['Dep_Cod'];?>"/>
				<input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/> 
                <input type="hidden" name="np" id="np" value="<?php echo $row_rs_direc['Dep_Cod']+0;?>"/>	               
            </td>
           </form> 
            <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
            <td align="center">
			<?Php 
			if ($row_rs_nodos['Dep_Est'] == 'A') 
			   { ?>            
            <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
            <input type="hidden" name="np" id="np" value="<?php echo $row_rs_nodos['Dep_Cod'];?>"/>	
            <input name="img1"  id="img1" type="image" src="../../imagenes/forward.png" width="18" height="18" title="Elegir" style="cursor:pointer">
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
	
	  <tr class="Fondo"><td colspan="4"><?Php echo error_alerta("¡No hay <strong>DEPARTAMENTOS</strong> que mostrar!", 1); ?></td>
	    </tr>
<? }?>
	  </table>
     
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
				<?Php
                if ($total_rs_direca >0)
                { ?>                
			        <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
			        <input type="hidden" name="np" id="np" value="<?php echo $row_rs_direca['Dep_Rec'];?>"/>
				<?Php
                }//Fin del if ($total_rs_direca >0)
                ?>
			        <input name="atras" type="submit" class="Boton_Atras" title="Atr&aacute;s" id="atras" value="Atras">
		        </td>
              </form>			    
		      </tr>
	    </table>
<?Php
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
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
	      <tr>
	        <td width="13%" class="Etiqueta1">Departamento:&nbsp;</td>
	        <td width="87%" class="LetraNegra"><?Php echo $row_rs_departamento['Dep_Des']; ?></td>
          </tr>
	      <tr>
	        <td class="Etiqueta1">Estado:&nbsp;</td>
	        <td>
	            <select name="Dep_Est" id="Dep_Est">
                	<option <?Php if ($row_rs_departamento['Dep_Est'] == 'A'){ echo "selected"; } ?> value="A">Activo</option>
                	<option <?Php if ($row_rs_departamento['Dep_Est'] == 'I'){ echo "selected"; } ?> value="I">Inactivo</option>                    
                </select>
	          <input name="np" type="hidden" id="np" value="<?php echo $np;?>">
              <input name="Hdd_Dep_Cod" type="hidden" id="Hdd_Dep_Cod" value="<?php echo $Dep_Cod;?>">
			<input name="ndepartamento" type="hidden" id="ndepartamento" value="ndepartamento">	
            <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
            </td>
          </tr>
    	</table>
	</FIELDSET>
	<br>
    <input name="btn_save" type="button" class="Boton_Guardar" title="Guardar" id="btn_save" onClick="validar_requeridos(this.form, 'Dep_Est', 1)" value="Guardar">
	  </form> 
	  </div>
<?Php
}//Fin del if (isset($Dep_Cod))

if (isset($Sec_Cod))
{
	/* Consulta el detalle de las secciones */
	$rs_seccion = $obBD_con1->consulta(sentencias_rhu(708, $obBD_con1->parametros($Sec_Cod)), $obBD_conexion->conexion);
	$row_rs_seccion = $obBD_con1->registros();
	$total_rs_seccion = $obBD_con1->numregistros();

?>
<div id="id_seccion">
	  <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form4" id="form4">        
	<FIELDSET>    
	<LEGEND>
	<label class="Titulos2">Datos a registrar </label>
	</LEGEND>
    <?php  //Creacion del campo REPOST
	$thisPost->startPost(); ?>    
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
	      <tr>
	        <td width="13%" class="Etiqueta1">Seccion:&nbsp;</td>
	        <td width="87%" class="LetraNegra"><?Php echo $row_rs_seccion['Sec_Des']; ?></td>
          </tr>
	      <tr>
	        <td class="Etiqueta1">Estado:&nbsp;</td>
	        <td>
	            <select name="Sec_Est" id="Sec_Est">
                	<option <?Php if ($row_rs_seccion['Sec_Est'] == 'A'){ echo "selected"; } ?> value="A">Activo</option>
                	<option <?Php if ($row_rs_seccion['Sec_Est'] == 'I'){ echo "selected"; } ?> value="I">Inactivo</option>                    
                </select>
	          <input name="np" type="hidden" id="np" value="<?php echo $np;?>">
              <input name="Hdd_Sec_Cod" type="hidden" id="Hdd_Sec_Cod" value="<?php echo $Sec_Cod;?>">
			<input name="nseccion" type="hidden" id="nseccion" value="nseccion">	
            <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
            </td>
          </tr>
    	</table>
	</FIELDSET>
	<br>
    <input name="btn_save" type="button" class="Boton_Guardar" title="Guardar" id="btn_save" onClick="validar_requeridos(this.form, 'Sec_Est', 1)" value="Guardar">
	  </form> 
	  </div>
<?
	}//Fin if (isset($Sec_Cod))
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
              <td width="13%" class="Etiqueta1">Cargo:&nbsp;</td>
              <td width="87%" class="LetraNegra"><?Php echo $row_rs_cargo['Tic_Des']; ?></td>
            </tr>
            <tr>
              <td class="Etiqueta1">Estado:&nbsp;</td>
              <td class="LetraNegra">
	            <select name="Tic_Est" id="Tic_Est">
                	<option <?Php if ($row_rs_cargo['Tic_Est'] == 'A'){ echo "selected"; } ?> value="A">Activo</option>
                	<option <?Php if ($row_rs_cargo['Tic_Est'] == 'I'){ echo "selected"; } ?> value="I">Inactivo</option>                    
                </select>              
                <input name="np" type="hidden" id="np" value="<?php echo $np;?>">
	            <input name="Hdd_Tic_Cod" type="hidden" id="Hdd_Tic_Cod" value="<?php echo $Tic_Cod;?>">                
                <input name="ncargo" type="hidden" id="ncargo" value="ncargo">
                <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"/>
                </td>
            </tr>
          </table>
        </FIELDSET>
        <br>
        <input name="btn_save" type="button" class="Boton_Guardar" title="Guardar" id="btn_save" onClick="validar_requeridos(this.form, 'Tic_Est', 1)" value="Guardar">
      </form>
    </div>
    	<?  
	}//Fin del if (isset($Tic_Cod))
}//Fin del if ($total_rs_nodos > 0)  ?>    
   </td>
  </tr>
</table>	  
</BODY></HTML>
<?php
@$obBD_con1->free_result($rs_areas);
@$obBD_con1->free_result($rs_nodos);
@$obBD_con1->result($rs_direc);
@$obBD_con1->result($rs_cargos);
@$obBD_con1->result($rs_direca);
@$obBD_con1->result($rs_cargo);
@$obBD_con1->result($rs_departamento);
/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>