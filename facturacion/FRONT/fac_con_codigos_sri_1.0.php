<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php     
/**
 * Permite registrar Los Codigos renta_iva 
 * 
 * @author Jose Cumbicos
 * @version 1.0
 * Fecha de actualizaci�n:	2014-07-30
 * 
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_codigos_sri.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

/*
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Cod($Ses_Dat_Dis);

/*
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Cod; 	  

/*
* Creaci�n del objeto para evitar el reenvio
*/
$thisPost = new Post_Block;

if (isset($ajax_op))
{ 
	/* 
	* Consulta los perfiles de la empresa 
	*/
	$row_rs_tip = $obBD_con1->getRowConsulta(10, $ajax_op, $obBD_conexion); 
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
          <td width="13%" class="Etiqueta1">C&oacute;d. SRI:&nbsp;</td>
          <td width="87%" class="LetraNegra"><?Php echo $row_rs_tip['Ren_Sri']; ?></td>
        </tr>
        <tr>
          <td width="13%" class="Etiqueta1">Porcentaje:&nbsp;</td>
          <td width="87%" class="LetraNegra"><?Php echo $row_rs_tip['Ren_Por']."%"; ?></td>
        </tr>
        <tr>
          <td width="13%" class="Etiqueta1">Descripci�n:&nbsp;</td>
          <td width="87%" class="LetraNegra"><?Php echo $row_rs_tip['Ren_Con']; ?></td>
        </tr>
        <!--<tr>
          <td width="13%" class="Etiqueta1">Tipo:&nbsp;</td>
		 <td>
         <select name="Tia_Ini" id="Tia_Ini" disabled>                       	  						
              <option <?Php //if($row_rs_tip['Tia_Ini']=="I"){echo 'selected' ;}?> value='I'>Ingreso</option>              				              
              <option <?Php //if($row_rs_tip['Tia_Ini']=="E"){echo 'selected' ;}?> value='E'>Egreso </option>              																					 		 	  <option <?Php //if($row_rs_tip['Tia_Ini']=="D"){echo 'selected' ;}?> value='D' >Diario</option>	       
         </select>	
         </td>-->
        </tr>
         <tr>
          <td width="13%" class="Etiqueta1">Estado:&nbsp;</td>
          <td width="87%" class="LetraNegra">
          <select id="estado" name="estado">
			<option <?php if($row_rs_tip['Ren_Est']=='Activo'){ echo "selected";}?>  value="A">Activo</option>
            <option <?php if($row_rs_tip['Ren_Est']=='Anulado'){ echo "selected";}?> value="I">Anular</option>					
          </select>
          </td>
        </tr>
      </table>
		
	</FIELDSET>		  
	<br>	
       <button type="button" class="btn btn-primary start" title="Guardar" onClick= "confirmacion(this.form);">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
       </button>	
	<input name="codigo" id="codigo" type="hidden" value="<?php echo $ajax_op; ?>">
	<input name="hdd_save" type="hidden" id="hdd_save" value="<?php echo "1"; ?>">
	</form>
<?php	
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
	$obBD_con1->operacionobBD(11, $parametros, $obBD_conexion);	

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
		<script type="text/javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	-->
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
	  <td height="10">&raquo; Consultar c&oacute;digos SRI</td>
    </tr>
    <tr>
      <td height="340" valign="top">
<?php
/* 
* Ingresa cuando se selecciona el tipo de asiento
*/		
	/* 
	* Consulta todos los codigos activos
	*/
	$rs_tipos = $obBD_con1->getArrayConsulta(9,'', $obBD_conexion); 
?>

<fieldset>
<legend>
<label class="Titulos2">Resultados de la busqueda</label>
</legend>
<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
	<thead>
	 <tr>	
        <th width="7%">C�d. Int. </th>        
  		<th width="7%">C&oacute;d. S.R.I.</th>               
		<th width="48%">Descripci&oacute;n</th>
		<th width="10%">Porcentaje(%)</th>
		<th width="10%">Bienes / Servicios</th>       
   		<th width="10%">Renta / Iva</th>    
        </tr>   
	</thead>
	<tbody>    
	<?Php 
    if (count($rs_tipos)!=0)
	{
		foreach($rs_tipos as $row){
            
           if ($row_rs_tipos['Ren_Est'] == 'Inactivo') 
           { $color = '#FF0000';}             
        ?>
    	<tr>
        	<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Ren_Cod']?></FONT></td>
        	<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row['Ren_Sri']?></FONT></td>
            <td title="<?php echo $row['Ren_Con'];?>"><FONT COLOR="<?php echo $rojo;?>">
              <?php if(strlen($row['Ren_Con'])<=70){echo $row['Ren_Con'];}else{ echo substr( $row['Ren_Con'],0,70)."..."; }?>
            </FONT></td>
            <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row['Ren_Por']."%"?></FONT></td>
            <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row['Ren_Tip']?></FONT></td>            
            <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row['Ren_Ret']?></FONT></td>
            <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
            </form>
    	</tr>
    	<?php }}else{?>
        <tr>
    	  <td align="center">&nbsp;</td>
    	  <td>&nbsp;</td>
    	  <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
    	  <td>&nbsp;</td>
    	  <td>&nbsp;</td>
    	  <td>&nbsp;</td>
    	  </tr>
		<?php }?>
    </tbody>
</table>
</fieldset>
<?php 
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