<?php     
/* 
* Alias:					Modificar
* Descripción: 				Permite activar y desactivar los procesos
* Fecha de actualización:	2011-03-12
* Desarrollador:			Lewis Chimarro
* Fecha de actualización:	2013-06-24
* Desarrollador:			Fabian Gallardo G.
*/

require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_perfil.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');
/*
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Admp($Ses_Dat_Dis);

/*
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Admp; 	  

/*
* Creación del objeto para evitar el reenvio
*/
$thisPost = new Post_Block;

if(isset($ajax_op))
{ 
	/* 
	* Consulta los perfiles de la empresa 
	*/
	$rs_perfiles = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion); 
	
	/* 
	* Consulta el proceso de manera individual 
	*/
	$row_rs_proceso = $obBD_con1->getRowConsulta(10, $ajax_op, $obBD_conexion); 
	
	?>
    <fieldset>
		<legend>
		<label class="Titulos2">Datos a modificar</label>
		</legend>
         <fieldset>
		<legend>
		<label class="Titulos2">Datos del Proceso</label>
		</legend>
        	<table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="20%" class="Etiqueta1">Orden:&nbsp;</td>
                <td width="80%" class="LetraNegra"><?php echo $row_rs_proceso['Pcs_Ord'];?>&nbsp;</td>
              </tr>
              <tr>
                <td width="20%" class="Etiqueta1">Nombre:&nbsp;</td>
                <td width="80%" class="LetraNegra"><?php echo $row_rs_proceso['Pcs_Nom']; ?>&nbsp;</td>
              </tr>
              <tr>
                <td width="20%" class="Etiqueta1">Link:&nbsp;</td>
                <td width="80%" class="LetraNegra"><?php echo $row_rs_proceso['Pcs_Lin']; ?>&nbsp;</td>
              </tr>
              <tr>
                <td width="20%" class="Etiqueta1">Ruta:&nbsp;</td>
                <td width="80%" class="LetraNegra"><?php echo $row_rs_proceso['Rut_Des']; ?>&nbsp;</td>
              </tr>
              <tr>
                <td width="20%" class="Etiqueta1">Observación:&nbsp;</td>
                <td width="80%" class="LetraNegra"><?php echo $row_rs_proceso['Pcs_Det']; ?>&nbsp;</td>
              </tr>
           </table>
        
        
        </fieldset>
	  <form action="<?Php echo $_SERVER['PHP_SELF'].'?op=2'; ?>" method="post" name="form_chk">
      <fieldset>
		<legend>
		<label class="Titulos2">Perfiles seleccionados</label>
		</legend>
        
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr><?php 
		  //echo $op;
		  	$i = 0; $nam = 0;
          	foreach($rs_perfiles as $row_rs_perfiles){
				if($row_rs_perfiles['Per_Est'] == 'A'){
					$rs_det = $obBD_con1->getArrayConsulta(7, $row_rs_perfiles['Per_Cod'].'*'.$ajax_op, $obBD_conexion); 
					//if(count($rs_det) > 0){ echo count($rs_det);}
		  ?>
 <td width="5%" class="Etiqueta1"><input name="per[<?php echo $nam;?>]"  id="per[<?php echo $nam;?>]" title="<?php echo $row_rs_perfiles['Per_Cod'];?>" type='checkbox' style='cursor:pointer' value="<?php echo $row_rs_perfiles['Per_Cod'];?>" <?php if(count($rs_det) > 0){ echo " checked ";} ?> ></td>
            <td width="15%" class="Etiqueta1"><div align="left"><?php echo $row_rs_perfiles['Per_Des'];?></div></td>
            <?php
					$i++; $nam++;
					if($i == 3){
						echo "</tr><tr>";
						$i = 0;
					}
				}
			}
		   ?>
          </tr>
          <tr>
          	<td>&nbsp;</td>
          </tr>
          <tr>
          	<td class="Etiqueta1"><input name="All"  id="All" type='checkbox' style='cursor:pointer' value="1" onClick="allCheck('<?php echo count($rs_perfiles); ?>', this)"></td>
             <td width="15%" class="Etiqueta1"><div align="left">Todos/Ninguno</div></td>
          </tr>
		</table>
        
		</fieldset>
       
 
     <br>
		<button type="button" class="btn btn-primary start" title="Guardar" onClick= "confirmacion(this.form)">
		  <i class="icon-book icon-white"></i>
		   <span>Guardar</span>
		</button>
			
		<input name="pcs" id="pcs" type="hidden" value="<?php echo $ajax_op; ?>">
		<input name="hdd_all" type="hidden" id="hdd_all" value="actualizar">
        </form>
     </fieldset>
  <?php
 exit();
}

/* Actualiza los perfiles */
if (isset($hdd_ind) && !isset($hdd_volver))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{			
		/**Inicio de la transaccion**/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);

		/* Elimina los procesos asignados al perfil */
		$obBD_con1->operacionobBD(3, $codigo, $obBD_conexion);	

		/* Evalua si esta creado el arreglo */
		if (isset($nomchk))
		{		
			foreach ($nomchk as $puntero => $item)
			{
				/* Almacena los procesos en un perfil */
				$obBD_con1->operacionobBD(2, $codigo.'*'.$item,$obBD_conexion);	
			}//Fin del foreach ($nomchk as $puntero => $item)
		}//Fin del if (isset($nomchk))

		/*Fin del la transaccion*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}//Fin del if (isset($hdd_ind) && !isset($hdd_volver))


/*
* Actualiza los perfiles 
*/
if (isset($hdd_all))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{			
		/*
		* Inicio de la transaccion
		*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);

		$obBD_con1->operacionobBD(11, $pcs, $obBD_conexion);	
		
		foreach ($per as $perfil)
		{	
			if (isset($perfil))
			{
				$parametros = $perfil.'*'.$pcs;
				
				$obBD_con1->operacionobBD(2, $parametros, $obBD_conexion);	
			}//Fin del if (isset($nomchk))
		}//Fin del foreach ($per as $perfil)
		
		/*
		* Fin del la transaccion
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}//Fin del if (isset($hdd_all) && !isset($hdd_volver))


?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script src="../LOGICA/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
   		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
		<script type="text/javascript" src="../VALIDACIONES/adm_val_perfil.js?y=2"></script>	
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">	
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
    <tr class="BarraTitulo">
	  <td height="10">&raquo; Modificaci&oacute;n de Perfil </td>
    </tr>
    <tr>
      <td height="296" valign="top">
<?php
if(!isset($op)){$op = 1;}

$descripcion = "Individual*General";
$pag1= $_SERVER['PHP_SELF']."?op=1";
$pag2= $_SERVER['PHP_SELF']."?op=2";
tabs(2,$descripcion, $pag1.'*'.$pag2, $op);

if ( $op==1 || $op==2 ) {
switch($op) {
case 1: 

/* 
* Ingresa cuando se seleeciona el perfil 
*/	
if (isset($codigo) && !isset($hdd_volver))
{ 
	/* 
	* Consulta los perfiles de la empresa 
	*/
	$row_rs_perfil = $obBD_con1->getRowConsulta(4, $codigo, $obBD_conexion); 
?>
	<form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2">
	 <?php  //Creacion del campo REPOST
	$thisPost->startPost(); ?>  
	<br>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos a modificar</label>
	</LEGEND>		
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13%" class="Etiqueta1">Perfil:&nbsp;</td>
          <td width="87%" class="LetraNegra"><?Php echo $row_rs_perfil['Per_Des']; ?></td>
        </tr>
      </table>
		  <table width="100%" height="100%" border="0" cellspacing="0" cellpadding="0" class="menu">
            <tr>
              <td align="left" valign="top" bgcolor="#999999">
			  <?php $Com_Tipo = 'M'; ?>
			  <?php require_once("adm_con_treemenu_adm_1.0.php"); ?>
              </td>
              </tr>
          </table>
	</FIELDSET>		  
	<br>
	
      <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form, 'hdd_volver','0')">
		               <i class=" icon-arrow-left icon-white"></i>
		               <span>Atr&aacute;s</span>
		       		 </button>
      &nbsp;
       <button type="button" class="btn btn-primary start" title="Guardar" onClick= "confirmacion(this.form)">
		           <i class="icon-book icon-white"></i>
		           <span>Guardar</span>
		           </button>
		
	<input name="codigo" type="hidden" value="<?php echo $codigo; ?>">
	<input name="hdd_ind" type="hidden" id="hdd_ind" value="actualizar">
	</form>
<?php	
}
else
{	

	/* 
	* Consulta los perfiles de la empresa 
	*/
	$rs_perfiles = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion); 
?>
    <fieldset>
    <legend>
    <label class="Titulos2">Resultados de la busqueda</label>
    </legend>
     <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
            <thead>
    <th width="4%">Cód. Int. </th>
    <th>Perfiles</th>
    <th width="2%">&nbsp;</th>
    </thead>
    <tbody>
    <?Php 
    $anulada=0;
    foreach($rs_perfiles as $row_rs_perfiles){
	
   if ($row_rs_perfiles['Per_Est'] == 'I') 
   { 
		$color = '#FF0000'; 
		$anulada++;
	} 
	else
	{
		$color = '';	
	}
	?>
	<tr>
	<td align="center"><?php  echo '<font color="'.$color.'">'.$row_rs_perfiles['Per_Cod'].'</font>'; ?></td>
	<td><?Php echo '<font color="'.$color.'">'.$row_rs_perfiles['Per_Des'].'</font>'; ?></td>
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
	<td align="center">
	<?Php 
	   if ($row_rs_perfiles['Per_Est'] == 'A') 
	   { ?>
	   <button name="img2"  id="img2" type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
						<i class=" icon-arrow-right icon-white"></i>
		</button>
	   <input type="hidden" name="codigo" id="codigo" value="<?php echo $row_rs_perfiles['Per_Cod'];?>"/>
	<?Php
	   }
	   else
	   {
			echo '&nbsp; ';
	   }//Fin del if ($row_rs_perfiles['Per_Est'] == 'A') 
	?>               	                                             
	</td>
	</form>
	</tr>
	<?Php 
	}?>
	</tbody>
	</table>
	</fieldset>
	<?php 
		echo barra_estado(count($rs_perfiles));
}//Fin del else if (isset($codigo) && !isset($hdd_volver))
	
	break;
	
	case 2: 

	if (!isset($np))
	{
		$np=0;
	}
	/* 
	* Cargado de los nodos - Codigo Empresa, Nodo Padre 
	*/
	if (isset($np))
	{
		$rs_nodos = $obBD_con1->getArrayConsulta(8, $np, $obBD_conexion);
	}
	//$row_rs_perfil = $obBD_con1->getRowConsulta(4, $codigo, $obBD_conexion); 
	?>
		 <?php  //Creacion del campo REPOST
		$thisPost->startPost(); ?>  
		<br>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Seleccione el proceso</label>
		</LEGEND>	
        <div class="LetraNegra">	
		<?php	
		/*
		* Consulta la ruta del departamento 
		*/
		if ($np==0) 
		{
			echo "<strong>INICIO</strong>";
			$separador='';
		}else{
			$rs_direc = $obBD_con1->getArrayConsulta(9, $np, $obBD_conexion);
			
			foreach($rs_direc as $row_rs_direc){
				echo "Ud. está dentro de <strong><img src='../../mascaras/model1/imagenes/32x32/folder-expanded.png' width='22' height='25' title='Directorio'> ".strtoupper($row_rs_direc['Org_Des'])."</strong>";
			}
			$separador='.';
		}//Fin del if ($np==0)  
		?>  
        </div>
        <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">      
            <thead>
              <tr>
                <th width="4%"><strong>C&oacute;d. Int.</strong></th>
                <th width="40%">Directorios/Procesos</th>
                <th width="40%">Ruta</th>
                <th width="4%">&nbsp;</th>
                </tr>    
            </thead>
            <tbody>  
			 <?Php
            /* 
            * Consulta los procesos del Directorio 
            */
            $rs_procesos = $obBD_con1->getArrayConsulta(10, $np, $obBD_conexion);
            
            /*
            * Consulta los cargos del departamento seleccionado, que se detalla el nombre en la cabecera 
            */
            if (count($rs_procesos) > 0)		
            {                
			   foreach($rs_procesos as $row_rs_procesos){
            ?>
            <tr>
                <td align="center" ><?php echo $row_rs_procesos['Pcs_Cod']; ?></td>
                <td width="40%"> 
                     <?Php	
                       if (isset($row_rs_procesos['Pcs_Est']) && $row_rs_procesos['Pcs_Est'] == 'I') 
                       { 
                            $color = '#FF0000'; 
                            $anulada++;
                        }else{
                            $color = '';	
                        }
						echo '<font color="'.$color.'">';
						if ($row_rs_procesos["Pcs_Tip"] == 'P'){
							$img_pag = '<img style="vertical-align:middle" src="../../mascaras/model1/imagenes/32x32/proceso.png" width="20" height="18" title="Página de tipo Proceso">';
						}else{
							$img_pag = '<img style="vertical-align:middle" src="../../mascaras/model1/imagenes/32x32/reporte.png" width="20" height="18" title="Página de tipo Reporte">';						
						}
						echo "Orden: ".$row_rs_procesos['Pcs_Ord'].' - '.$img_pag.$row_rs_procesos['Pcs_Lin']."</font><br>"; 
                  ?>
                </td>
                <td width="35%"><?Php echo $row_rs_procesos['Rut_Des']."<b>".$row_rs_procesos['Pcs_Nom']."</b>"; ?></td>
                <td align="center">
					<form action="<?php echo $_SERVER['PHP_SELF'].'?op=2';?>" method="post" name= "form_mod" id="form_mod">
						<input type="hidden" name="mod" id="mod" value="<?php echo 'M';?>"/>	
						<button type="button" title="Editar" class="btn btn-primary btn-mini"  onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF'].'?op=2'; ?>&ajax_op=<?php echo $row_rs_procesos['Pcs_Cod']; ?>','ajax_modal');Muestra_Aparecer();"><i class=" icon-edit icon-white"></i>
						</button>
					</form>
				</td>
            </tr>
                 <?Php
			   }//Fin foreach($rs_procesos as $row_rs_procesos)
			}//Fin del if (count($rs_procesos) > 0)	
			
		   /*
		   * Valida si existen nodos 
		   */
		   if (count($rs_nodos) > 0) 
		   { 
                       $anulada =0;
			  foreach($rs_nodos as $row_rs_nodos){ 	 ?>         
			  <tr>
                <td align="center"><?php echo $row_rs_nodos['Org_Cod']; ?></td>
                <td width="40%">
                    <?Php
                    if ($row_rs_nodos['Org_Mod'] == 'I') 
                    { 
                        $color_d = '#FF0000'; 
                        $anulada++;
                    } 
                    else
                    {
                        $color_d = '';	
                    }				
                    ?>
                    <img style="vertical-align:middle" src="../LOGICA/images/<?Php echo $row_rs_nodos['Org_Img'] ?>" width="22" height="25" title="Departamento"><?php echo "<font color='".$color_d."'><strong> ".$row_rs_nodos['Org_Des']."</strong></font>"; ?>
              	</td>
			 	<td width="35%">&nbsp;</td>
              	<td align="center">
               <form style="display:inline" action="<?php echo $_SERVER['PHP_SELF'].'?op=2';?>" method="post" name= "form2" id="form2">
                <?Php 
                	if ($row_rs_nodos['Org_Mod'] == 'A') 
               		{ 
				?>			
            	<input type="hidden" name="np" id="np" value="<?php echo $row_rs_nodos['Org_Cod'];?>"/>	
            	<button title="Elegir" type='button' class='btn btn-success btn-mini' onClick="submit()" ><i class='icon-arrow-right icon-white'></i></button>
            	<?Php
              		}else{
						echo "&nbsp;"; 
					}?>
                </form> 
              	</td>
			</tr>
            <?php
             	}//Fin foreach($rs_nodos as $row_rs_nodos)
			}//Fin del if (count($rs_nodos) > 0)	
			?>
          </tbody>
        </table>
				
		</FIELDSET>		  
		<?php 
		if (count($rs_procesos) > 0)		
        {                
			echo barra_estado(count($rs_procesos));
		}else{
			echo barra_estado(count($rs_nodos));
		}
		?>			
         <br>
<table border="0" cellpadding="0" cellspacing="0">
  <tr>
  <form action="<?php echo $_SERVER['PHP_SELF'].'?op=2';?>" method="post" name= "form3" id="form3">
	<td align="right">
	<?Php
	if (isset($rs_direc) && count($rs_direc) >0)
	{ 
		foreach($rs_direc as $row_rs_direc){
		?>
			<input type="hidden" name="np" id="np" value="<?php echo $row_rs_direc['Org_Niv'];?>"/> 
            <button name="atras" type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" id="atras" value="Atras" onClick="submit()" >
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
            <?Php
		}
	}//Fin del if (count($rs_direc) >0)
	?></td>
  </form>
  </tr>
</table>
        <?php
	break;
	}
}
?>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
	<div id="bgmodal"  class="bgmodal" style="display:none" >
        <div id="ajax_modal">
        </div>
    </div>
			</td>
		</tr> 
	</table> 
	</div>
	</BODY></HTML>
<?php
	@$obBD_con1->free_result($rs_perfil);
	@$obBD_con1->free_result($rs_perfiles);
	/*
	* Cierre las conexiones 
	*/
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
?>