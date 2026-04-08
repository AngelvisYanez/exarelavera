<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?     
/* 
* Alias:					Eliminar
* Descripción: 				Permite dar de baja a los tipos de asiento
* Fecha de actualización:	2014-05-30
* Desarrollador:			juanpuxito
* Fecha de actualización:	2014-06-30
* Desarrollador:			Juan Morales R.
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_tip_asiento.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/*
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tip($Ses_Dat_Dis);

/*
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tip; 	  

/*
* Creación del objeto para evitar el reenvio
*/
$thisPost = new Post_Block;

if (isset($ajax_op))
{ 
	/* 
	* Consulta los perfiles de la empresa 
	*/
	$row_rs_tip = $obBD_con1->getRowConsulta(4, $ajax_op, $obBD_conexion); 
?>
	<form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2">
	 <?php  //Creacion del campo REPOST
	$thisPost->startPost(); ?>  	
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos a modificar</label>
	</LEGEND>		
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
       <tr>
          <td width="13%" class="Etiqueta1">Abrev.:&nbsp;</td>
          <td width="87%" class="LetraNegra"><?Php echo $row_rs_tip['Tia_Abr']; ?></td>
        </tr>
        <tr>
          <td width="13%" class="Etiqueta1">Descripción:</td>
          <td width="87%" class="LetraNegra"><?Php echo $row_rs_tip['Tia_Des']; ?></td>
        </tr>
        <tr>
          <td width="13%" class="Etiqueta1">Tipo:&nbsp;</td>
		 <td>
         <select name="Tia_Ini" id="Tia_Ini" disabled>                       	  						
              <option <?Php if($row_rs_tip['Tia_Ini']=="I"){echo 'selected' ;}?> value='I'>Ingreso</option>              				              <option <?Php if($row_rs_tip['Tia_Ini']=="E"){echo 'selected' ;}?> value='E'>Egreso </option>              																					 			  <option <?Php if($row_rs_tip['Tia_Ini']=="D"){echo 'selected' ;}?> value='D' >Diario</option>	       
          </select>	
          		 </td>
        </tr>
         <tr>
          <td width="13%" class="Etiqueta1">Estado:&nbsp;</td>
          <td width="87%" class="LetraNegra"><select id="estado" name="estado">
				<?php 
                    if($row_rs_tip['Tia_Est']=='I')
                    {
						echo "<option value=\"A\"> Activo </option>";
                        echo "<option selected=\"selected\" value=\"I\"> Inactivo </option>";
                    }
                    else
                    {
						echo "<option selected=\"selected\" value=\"A\"> Activo </option>";
                        echo "<option value=\"I\"> Inactivo </option>";						                       
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
	<input name="codigo" type="hidden" value="<? echo $ajax_op; ?>">
	<input name="hdd_save" type="hidden" id="hdd_save" value="<? echo "1"; ?>">
	</form>
<?	
	exit();
}

/*
* Dar de baja al topo de asiento seleccionado
*/
if ($thisPost->postBlock($_POST['postID']) && isset($hdd_save)) 
{			
	/*
	* Inicio de la transaccion
	*/
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);

	$parametros = $estado.'*'.$codigo;
	/*
	* Elimina los procesos asignados al tipo de asiento 
	*/
	$obBD_con1->operacionobBD(8, $parametros, $obBD_conexion);	

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
		<!--<script src="../LOGICA/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
		<script language="javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	-->
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
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
	  <td height="10">&raquo; Anular/Activar Tipo de Asiento </td>
    </tr>
    <tr>
      <td height="340" valign="top">
<?
/* 
* Ingresa cuando se selecciona el tipo de asiento
*/		
	/* 
	* Consulta los perfiles de la empresa 
	*/
	$rs_tipos = $obBD_con1->getArrayConsulta(7, $Ses_Emp_Cod, $obBD_conexion); 
?>

<fieldset>
<legend>
<label class="Titulos2">Resultados de la busqueda</label>
</legend>
<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
	<thead>
	 <tr>	
        <th width="4%">Cód. Int. </th>        
  		<th width="4%">Abrev. </th>               
		<th>Descripci&oacute;n</th>       
   		<th width="6%">Tipo </th>    
        <th width="6%">Estado </th>   
        <th width="2%">&nbsp;</th>    
    </tr>   
	</thead>
	<tbody>    
		<?Php 
        if (count($rs_tipos)!=0)
		{
		foreach($rs_tipos as $row_rs_tipos){
            
           if ($row_rs_tipos['Tia_Est'] == 'I') 
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
        	<td align="center"><?php  echo '<font color="'.$color.'">'.$row_rs_tipos['Tia_Cod'].'</font>'; ?></td>
        	<td><?Php echo '<font color="'.$color.'">'.$row_rs_tipos['Tia_Abr'].'</font>'; ?></td>
            <td><?Php echo '<font color="'.$color.'">'.$row_rs_tipos['Tia_Des'].'</font>'; ?></td>            
            <td><?Php if($row_rs_tipos['Tia_Ini']=='I'){echo '<font color="'.$color.'">'.INGRESO.'</font>';}
			if($row_rs_tipos['Tia_Ini']=='E'){ echo '<font color="'.$color.'">'.EGRESO.'</font>';} 
			if($row_rs_tipos['Tia_Ini']=='D'){ echo '<font color="'.$color.'">'.DIARIO.'</font>';} ?></td>
            <td><?Php if($row_rs_tipos['Tia_Est']=='A'){echo '<font color="'.$color.'">'.ACTIVO.'</font>';} 
			if($row_rs_tipos['Tia_Est']=='I'){echo '<font color="'.$color.'">'.INACTIVO.'</font>';} ?></td>
        	<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
                <td align="center">
                <?Php 
                   if ($row_rs_tipos['Tia_Est'] == 'I') 
                   { ?>
                        <button type="button" class="btn btn-danger btn-mini" title="Activar" onClick= "ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?ajax_op=<?php echo $row_rs_tipos['Tia_Cod']; ?>','ajax_modal');Muestra_Aparecer();" ><i class="icon-ban-circle icon-white"></i></button>          
                <?Php
                   }
                   else
                   { ?>
                   <button type="button" class="btn btn-success btn-mini" title="Anular" onClick= "ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_op=<?php echo $row_rs_tipos['Tia_Cod']; ?>','ajax_modal');Muestra_Aparecer();" ><i class='icon-arrow-right icon-white'></i></button>                                              
                        <?
                   }//Fin del if ($row_rs_perfiles['Per_Est'] == 'A')  
				?>
				    <input type="hidden" name="codigo" id="codigo" value="<?php echo $row_rs_tipos['Tia_Cod'];?>"/>
                </td>
    		</form>
    	</tr>
    	<? }}else{?>
        <tr>
    	  <td align="center">&nbsp;</td>
    	  <td>&nbsp;</td>
    	  <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
    	  <td>&nbsp;</td>
    	  <td>&nbsp;</td>
    	  <td align="center">&nbsp;</td>
  	    </tr>
		<? }?>
    </tbody>
</table>
</fieldset>
<? 
	echo barra_estado(count($rs_tipos));
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
        <div id="ajax_modal"></div>
    </div>
		</td>
    </tr> 
</table> 
</div>
</BODY></HTML>
<?php
/*
* Cierre las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>