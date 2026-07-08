<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
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

if (isset($ajax_op))
{ 
	/* 
	* Consulta los perfiles de la empresa 
	*/
	$row_rs_perfil = $obBD_con1->getRowConsulta(4, $ajax_op, $obBD_conexion); 
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
        <tr>
          <td width="13%" class="Etiqueta1">Observación:&nbsp;</td>
          <td width="87%" class="LetraNegra"><?Php 
		  	if($row_rs_perfil['Per_Obs'] == ""){ 
				echo "(ninguna)";
			}else{
				echo $row_rs_perfil['Per_Obs'];
			}
			?></td>
        </tr>
         <tr>
          <td width="13%" class="Etiqueta1">Estado:&nbsp;</td>
          <td width="87%" class="LetraNegra"><select id="estado" name="estado">
				<?php 
                    if($row_rs_perfil['Per_Est']=='A')
                    {
                        echo "<option selected=\"selected\" value=\"A\"> Activo </option>";
                        echo "<option value=\"I\"> Inactivo </option>";
                    }
                    else
                    {
                        echo "<option value=\"A\"> Activo </option>";
                        echo "<option selected=\"selected\" value=\"I\"> Inactivo </option>";
                    }
                ?>
            </select></td>
        </tr>
      </table>
		
	</FIELDSET>		  
	<br>
	
       <button type="button" class="btn btn-primary start" title="Guardar" onClick= "confirmacion(this.form);">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
       </button>
		
	<input name="codigo" type="hidden" value="<?php echo $ajax_op; ?>">
	<input name="hdd_save" type="hidden" id="hdd_save" value="<?php echo "1"; ?>">
	</form>
<?php	
	exit();
}

/*
* Dar de baja al perfil seleccionado
*/
if ($thisPost->postBlock($_POST['postID']) && isset($hdd_save)) 
{			
	/*
	* Inicio de la transaccion
	*/
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);

	$parametros = $estado.'*'.$codigo;
	/*
	* Elimina los procesos asignados al perfil 
	*/
	$obBD_con1->operacionobBD(6, $parametros, $obBD_conexion);	

	/*
	* Fin del la transaccion
	*/
	$obBD_con1->fin_transaccion($obBD_conexion->conexion);
}//Fin del if ($thisPost->postBlock($_POST['postID'])) 

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script src="../LOGICA/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
		<script language="javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">	
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr class="BarraTitulo">
	  <td height="10">&raquo; Anular/Activar Perfil </td>
    </tr>
    <tr>
      <td height="296" valign="top">
<?php
/* 
* Ingresa cuando se seleeciona el perfil 
*/	

	
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
    	<tr <?Php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
        	<td align="center"><?php  echo '<font color="'.$color.'">'.$row_rs_perfiles['Per_Cod'].'</font>'; ?></td>
        	<td><?Php echo '<font color="'.$color.'">'.$row_rs_perfiles['Per_Des'].'</font>'; ?></td>
        	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
                <td align="center">
                <?Php 
                   if ($row_rs_perfiles['Per_Est'] == 'A') 
                   { ?>
                    <button type="button" class="btn btn-success btn-mini" title="Anular" onClick= "ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_op=<?php echo $row_rs_perfiles['Per_Cod']; ?>','ajax_modal');Muestra_Aparecer();" ><i class='icon-arrow-right icon-white'></i></button>
                  
                <?Php
                   }
                   else
                   { ?>
                        <button type="button" class="btn btn-danger btn-mini" title="Activar" onClick= "ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?ajax_op=<?php echo $row_rs_perfiles['Per_Cod']; ?>','ajax_modal');Muestra_Aparecer();" ><i class="icon-ban-circle icon-white"></i></button>
                        <?php
                   }//Fin del if ($row_rs_perfiles['Per_Est'] == 'A')  
				?>
				    <input type="hidden" name="codigo" id="codigo" value="<?php echo $row_rs_perfiles['Per_Cod'];?>"/>
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

 
/*
* Control para setear el arreglo solo cuando tenga valores
*/
if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)?><br>
 <?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>   
 
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