<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripci�n:Anulacion de Ajustes.
* Fecha de actualizaci�n:	02-06-11 
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	13-07-2012
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	03-09-2013
* Desarrollador:	Fabian Gallardo
*/
	  
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_aju.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 
/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");

/**
* Llamado a componente ajax 
*/
require_once("../COMPONENTES/ajax_con_ctaAjuste.php");

/**
* Consulta los tipos de ajuste 
*/
$rs_tpaj = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);

/**
* Evitar el reenvio de formularios 
*/
if ($thisPost->postBlock($_POST['postID'])) 
{    
	if (isset($hdd_save))
	{   
		/**
		* Inicio de la transaccion
		*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);	
				
		$row_detalle = $obBD_con1->getArrayConsulta(1057, $Aju_Cod, $obBD_conexion);
		$obBD_con1->operacionobBD(1058,$Aju_Cod, $obBD_conexion);
		$obBD_con1->operacionobBD(1059, $Aju_Cod, $obBD_conexion);		
		foreach ($row_detalle as $row)
		{
			/**
			* Consulta el Stock 
			*/
			$row_rs_conpro = $obBD_con1->getRowConsulta(1206,$row['Pro_Cod'],$obBD_conexion);						
			$tstock= $row_rs_conpro['stock']; /*No se utiliza la suma del ajuste porque ya se anula y se vuelve al calculo anterior + $row['Aju_Can'] */			
			
			/**
			* Actualizo el Stock 
			*/
			$obBD_con1->operacionobBD(1204, $tstock.'*'.$row['Pro_Cod'].'*'.$Ses_Suc_Cod, $obBD_conexion);

			/**
			* Actualizo el Stock en tabla producto
			*/

			$obBD_con1->operacionobBD(12044, $tstock.'*'.$row['Pro_Cod'], $obBD_conexion);					
		}
		/**
		* Dando de baja un Recibo de Caja chica 
		*/
		
		/**
		* FInaliza la transaccion
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);	
	}
}

if (isset($txt_busqueda))
{		/**
		* Consulto la busqueda de la cabecera Ajustes 
		*/
		$rs_buspro = $obBD_con1->getArrayConsulta(1056, $txt_busqueda.'*'.$ini.'*'.$fin, $obBD_conexion);	
}
else
{
	$rs_buspro = array();
}

if(isset($ajx_det)){
	
	/**
	* Resultado de la tabla detalle de ajustes
	*/
	$rs_detalle = $obBD_con1->getArrayConsulta(1057, $ajx_det, $obBD_conexion);
	$Total=0;
	
	?>
    <fieldset>
        <LEGEND>
            <label class="Titulos2">Detalle : <?php echo urldecode($aju_det); ?></label>
        </LEGEND>
     <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>    
              <th width="10%" align="center"><strong>Cant.</strong></th>
              <th width="30%"><strong>Descripci&oacute;n</strong></th>
              <th width="33%" align="right"><strong>P. Unitario</strong></th>
              <th width="27%" align="right"><strong>Importe</strong></th>
            </tr>
           <tbody>
            <?php foreach($rs_detalle as $row_detalle){?>
            <tr >     
              <td align="center" ><?php echo $row_detalle['Aju_Can']; ?></td>
              <td>&nbsp;<?php echo $row_detalle['Ite_Lar']; ?></td>
              <td align="right"><?php echo formato_numero($row_detalle['Aju_Pru'],2,1); ?>&nbsp;</td>
              <td align="right"><?php echo formato_numero($row_detalle['Aju_Imp'],2,1); $Total=$Total+$row_detalle['Rcb_Imp'];?>&nbsp;</td>
            </tr>
        <?php } ?>
        	</tbody>
        </table>	
    </fieldset>
<?php
		exit();	
}
?>
<HTML><HEAD>
		<!--TITLE><?Php echo $Ses_Sys_Nom;?></TITLE-->
		<TITLE><?Php echo "Ajustes Anular [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>		
		<script type="text/javascript" src="../VALIDACIONES/fac_val_aju.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>         
        <script>
    $(function() { 
        //var imagen = "../../mascaras/model1/imagenes/32x32/calendar.gif";
		/* Campo 1 */
		$( "#ini" ).datepicker({
			changeMonth: true, changeYear: true,
			/* Permite asignar una imagen */
			/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});
		/* Campo 1 */
		$( "#fin" ).datepicker({
			changeMonth: true, changeYear: true,
			/* Permite asignar una imagen */
			/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});			
        }); 		
        </script>    
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>		

		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Anular Ajustes de Productos</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"><FIELDSET>
<LEGEND>
	<label class="Titulos2">Buscar por:</label>
</LEGEND>
<?Php 
/**
* Muestra el mensaje de requerido
*/
mensaje_requerido(); 
?>
<table width="744" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="99" height="40" class="BarraBusqueda"><div align="right"><span class=
		  "Asterisco">*</span> Movimiento:</div></td>
    <td width="216" class="BarraBusqueda"><span class="LetraNegra">
      <select name="txt_busqueda" id="txt_busqueda">
        <option value="">Seleccione...</option>
        <?php  foreach($rs_tpaj as $row_rs_tpaj){ ?>
        <option <?php if ($txt_busqueda == $row_rs_tpaj['Tia_Cod']){ echo "selected"; } ?> value="<?Php echo $row_rs_tpaj['Tia_Cod']; ?>">
        <?php  echo "[".$row_rs_tpaj['Tia_Tra']."] ".$row_rs_tpaj['Tia_Des'];		?>
        </option>
        <?php	}?>
      </select>
    </span></td>
    <td width="55" class="BarraBusqueda"><div align="right">Desde: </div></td>
    <td width="73" class="BarraBusqueda"><input name="ini" type="text" id="ini" value="<?php if (isset($ini)){ echo $ini; }else{ echo date("Y-m-d"); } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)"/></td>
    <td width="49" class="BarraBusqueda"><div align="right">Hasta: </div></td>
    <td width="107" class="BarraBusqueda"><input name="fin" type="text" id="fin" value="<?php if (isset($fin)){ echo $fin; }else{ echo date("Y-m-d"); } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" /></td>
    <td width="145" align="center" class="BarraBusqueda">
    <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda*ini*fin', 0)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
    </button> 
      <input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar" />
    </td>
  </tr>
</table>
</FIELDSET>
    </form>	
<?Php  	
  	if(isset($txt_busqueda))
	{ ?>
	<br>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la Busqueda</label>
	</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>
		  <tr>
		    <th width="7%" align="center">&nbsp;</th>
		    <th width="10%" align="center">C&oacute;d. Int.</th>
			<th width="9%" align="center">No. Ajuste</th>
			<th width="9%" align="center">Fecha</th>
			<th width="9%" align="center">C&eacute;dula/R.U.C.</th>
			<th width="37%" align="center">Proveedor</th>
			<th width="10%" align="center">&nbsp;</th>
		  </tr>
         </thead>
         <tbody>
		  <?php  
		  if(count($rs_buspro)!=0)
		  {
		  foreach($rs_buspro as $row_rs_buspro){ $i++;
  			 if($row_rs_buspro['Aju_Est']=='I')
	  		 { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
		  ?>
		  <tr>
		    <td align="center" width="7%">
                <button type="button" class="btn btn-success btn-mini" title="Ver detalle" onClick="Muestra_Aparecer(); ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?ajx_det=<?php echo $row_rs_buspro['Aju_Cod'];?>&aju_det=<?php echo urlencode($row_rs_buspro['Aju_Det']);?>','ajax_modal'); " style="height:22px">
                    <i class=" icon-search icon-white"></i>
                </button>                
            </td>
		    <td align="center" width="10%"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Aju_Cod']; ?></font></td>
			<td align="center" width="9%"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Aju_Sec']; ?></font></td>
			<td align="center" width="9%"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Aju_Fec']; ?></font></td>
			<td align="center" width="9%"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Prs_Ced']; ?></font></td>
			<td align="left" width="37%">
                	<div align="left" style="float:left">
                <font color="<?php echo $rojo; ?>">&nbsp;<?php echo marcar_cadena($txt_busqueda,$row_rs_buspro['Prs_Ape'].' '.$row_rs_buspro['Prs_Nom'],'#FFFF00', 1) ?></font></div>
            </td>
			<td align="center" width="10%">
			  <?Php if ($row_rs_buspro['Aju_Est'] == 'A') { ?>
			  <form name='frm_personal' method='post' action="<?php echo $_SERVER['PHP_SELF']; ?>">
			    <?php $thisPost->startPost();?>
			    <input type="hidden" name="Prv_Cod" id="Prv_Cod" value="<?Php echo $row_rs_buspro['Prv_Cod'];?>">
			    <input type="hidden" name="Aju_Cod" id="Aju_Cod" value="<?Php echo $row_rs_buspro['Aju_Cod'];?>">
			    <input type="hidden" name="hdd_save" id="hdd_save" value="">
			    <button type="button" class="btn btn-danger delete" title="Anular Ajuste" onClick="confirmacion2(this.form)">
                    <i class="icon-trash icon-white"></i>
                    <span>Anular</span>
    			</button>                
			    </form>
			  <?Php } else { echo "&nbsp;"; }?>
			  </td>
		  </tr>
		  <?Php } 
		  }else{?>
              <tr>
              	<td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td><?Php echo error_alerta("!No hay resultados que mostrar!", 1) ?></td>
                <td>&nbsp;</td>
              </tr>
     	  <?php }?>
          </tbody>
	  </table>	  
	  <?php echo barra_estado(count($rs_buspro));?>
	</FIELDSET>  
   <?Php
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?> 
<?php } ?>
</td>
</tr>
</table>

<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
        <div id="ajax_modal">
        </div>
    </div>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_aju.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
<?Php
	/** 
	* Control para ocultar el detalle de las filas 
	*/
	if(count($rs_buspro) != 0)
	{
		//ocultarDetalle(count($rs_buspro));
	}
?>
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>