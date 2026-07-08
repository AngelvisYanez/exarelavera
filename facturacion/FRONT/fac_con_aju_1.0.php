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

$rs_ann = $obBD_con1->getArrayConsulta(1208, '', $obBD_conexion);

if(!isset($op))
{
	$op =1;	
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
				<label class="Titulos2">Detalle : <?php echo $aju_det; ?></label>
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
				  <td align="right"><?php echo formato_numero($row_detalle['Aju_Imp'],2,1); $Total=$Total+(isset($row_detalle['Rcb_Imp'])?$row_detalle['Rcb_Imp']:0);?>&nbsp;</td>
				</tr>
			<?php } ?>
				</tbody>
			</table>	
		</fieldset>
	<?php
			exit();	
	}
	
switch($op){
	case 2:

	/**
	* Llamado a componente ajax 
	*/
	require_once("../COMPONENTES/ajax_con_ctaAjuste.php");
	
	/**
	* Consulta los tipos de ajuste 
	*/
	$rs_tpaj= $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
	
	if (isset($txt_busqueda))
	{		
		/**
		* Consulto la busqueda de la cabecera Ajustes 
		*/
		$rs_buspro = $obBD_con1->getArrayConsulta(1056, $txt_busqueda.'*'.$ini.'*'.$fin, $obBD_conexion);	
	}
	
	break;

}
		
?>
<HTML><HEAD>
		<!--TITLE><?Php echo $Ses_Sys_Nom;?></TITLE-->
    <TITLE><?Php echo "Ajustes Consultar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>		
		<script language="javascript" src="../VALIDACIONES/fac_val_aju.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>         
        <script>
    $(function() { 
        /* Campo 1 */					
        $( "#ini" ).datepicker( "option", "showAnim", "show" ); $.datepicker.setDefaults( $.datepicker.regional[ "es" ] ); 
        $( "#ini" ).datepicker({ altField: "#ini", altFormat: "yy-mm-dd" });	
        /* Campo 2 */			
        $( "#fin" ).datepicker( "option", "showAnim", "show" ); $.datepicker.setDefaults( $.datepicker.regional[ "es" ] ); 
        $( "#fin" ).datepicker({ altField: "#fin", altFormat: "yy-mm-dd" });						
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
	  <td height="10">&raquo; Consultar Ajustes de Productos</td>
</tr>
<?php if(count($rs_ann) > 0) {?>
<tr>
 <td align="left" valign="top" height="400">
  <?php
		$descripcion = "Individual*Grupal";
  		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		tabs(2,$descripcion, $pag1.'*'.$pag2, $op);
	?>

<?php
	if(!isset($op)){$op = 1;}
	
		if ( $op==1 || $op==2 ) {
		switch($op) {
			case 1:
?>	
   <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Buscar por:</label>
    </LEGEND>
     <?Php 
    /**
    * Muestra el mensaje de requerido
    */
    mensaje_requerido(); 
    ?>
    <table width="495" height="27" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="114" height="23" class="Etiqueta1"><div align="left">
              <input name="op_opciones" type="radio" value="d" checked onClick="document.getElementById('cmb_mes').disabled=false; setfocus(this.form.txt_busqueda)">
          Apellidos</div></td>
          <td width="140" class="Etiqueta1"> <div align="left">Año:&nbsp;
          <select name="cmb_ann" id="cmb_ann">
		  <?Php
          foreach($rs_ann as $row_ann)
          {
            ?>
            <option <?php if ($row_ann['ann'] == date('Y')){ echo "selected"; } ?> value="<?Php echo $row_ann['ann']; ?>"><?php echo $row_ann['ann']; ?></option>
            <?Php
          }
          ?>
          </select>
        </div></td>
        <td width="241" class="Etiqueta1"> <div align="left">Mes:&nbsp;
          <select name="cmb_mes" id="cmb_mes">
             <option value=""><< TODOS >></option>
		  <?Php
          for ($i=1;$i<=12;$i++)
          {
            ?>
            <option <?php if (isset($mes) && $i == $mes){ echo "selected"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1) ?></option>
            <?Php
          }
          ?>
          </select>
        </div></td>
      </tr>
    </table>
    <table width="647" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="85" height="40" class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span>Busqueda:</div></td>
          <td width="339" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" onKeyUp="parametro_injection(this)"></td>
          <td width="123" class="BarraBusqueda"><div align="center">
           
             <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)">
               <i class="icon-search icon-white"></i>
               <span>Buscar</span>
        </button> 
         <input name="op" type="hidden" value="<?php echo $op; ?>" >
            
          </div></td>
        </tr>
      </table>
    </FIELDSET>
    </form>  
    
    <?php 
	if(isset($txt_busqueda))
	{ 
		if($cmb_mes != ''){
			$vr = 'AND MONTH(Aju_Fec) ='.$cmb_mes;
		}else{
			$vr = '';
		}
		$row_rs_cabcomp = $obBD_con1->getArrayConsulta(1207, $txt_busqueda.'*'.$Ses_Emp_Cod.'*'.$vr.'*'.$cmb_ann, $obBD_conexion);
	?>	
		<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
	<table width="100%" border="1" cellpadding="1" cellspacing="0" class="fixedHeader01">
    <thead>
        <tr>
          <th width="5%">No. Int </th>
          <th width="10%">C&eacute;dula/R.U.C.</th>
          <th width="10%">Proveedor/Cliente</th>
          <th width="10%" align="center">No. Ajuste</th>
          <th width="10%">Fecha</th>
    		  <th width="10%">Tipo</th>
          <th width="10%">Detalle</th>
    		  <th width="5%">&nbsp;</th> 
          <th width="8%">&nbsp;</th> 
        </tr>
     </thead>
     <tbody>	    
		<?php 			
	if (count($row_rs_cabcomp) > 0) 
	{	  		
		$i=0;
		foreach ($row_rs_cabcomp as $row)
		{ 
		$i++;
			 if($row['Aju_Est']=='I')
	  		 { $rojo='#FF0000'; $anulada++; }else{$rojo='';}					
		?>
		<tr>
		  <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row['Aju_Cod']; ?></font></td>		  
   		  <td><font color="<?php echo $rojo; ?>"><?php echo $row['Prs_Ced']; ?>&nbsp;</font></td>
		  <td><font color="<?php echo $rojo; ?>">
		  <?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'], '#FFFF00', 1); ?></font></td>
           <td align="center" width="9%"><font color="<?php echo $rojo; ?>"><?php echo $row['Aju_Sec']; ?></font></td>
		  <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row['Aju_Fec']; ?></font></td>			
		  <td align="center"><font color="<?php echo $rojo; ?>">&nbsp;<?php echo $row['Tia_Des']; ?></font></td>
      <td align="center"><font color="<?php echo $rojo; ?>">&nbsp;<?php echo $row['Aju_Det']; ?></font></td>
		  <td align="center" >          
                <button type="button" class="btn btn-info btn-mini" title="Ver detalle" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?ajx_det=<?php echo $row['Aju_Cod'];?>&aju_det=<?php echo $row['Aju_Det'];?>&op=<?php echo $op;?>','ajax_modal'); Muestra_Aparecer();" style="height:22px">
                        <i class="icon-info-sign icon-white"></i>
                    </button>	
                </td>
         <td align="center">
                  <?Php if ($row['Aju_Est'] == 'A') { ?>
                  <form name='frm_personal' method='post' action="fac_pri_aju_1.0.php" target="_blank">
                    <?php $thisPost->startPost();?>
                    <input type="hidden" name="Prv_Cod" id="Prv_Cod" value="<?Php echo $row['Prv_Cod'];?>">
                    <input type="hidden" name="Aju_Cod" id="Aju_Cod" value="<?Php echo $row['Aju_Cod'];?>">
                    <input type="hidden" name="hdd_save" id="hdd_save" value="">
                    <button type="button" class="btn btn-primary btn-mini" title="Imprimir Ajuste" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>                
                    </form>
                  <?Php } else { echo "&nbsp;"; }?>
                  </td>
        <?php
	  	} // Fin del foreach
  		}//Fin del //Fin del if ($total_rs_cabcomp > 0)
		else
		{ ?>
		  <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><?Php echo error_alerta(" No hay resultados que mostrar", 1); ?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
		<?Php		
		}//Fin del else //Fin del if ($total_rs_cabcomp > 0)	  
	 	?>     
     	</tbody>     
    	</table>
 		<?php echo barra_estado(count($row_rs_cabcomp)); ?>
		</FIELDSET>
    	<?Php
    	if (isset($anulada) && $anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    	<?php require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
	<?php  
	}//Fin del if(isset($txt_busqueda))  
	
	break;
	
	case 2: 
?>                
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
        <td width="73" class="BarraBusqueda"><input name="ini" type="text" id="ini" value="<?php if (isset($ini)){ echo $ini; }else{ echo date('Y-m').'-01'; } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)"/></td>
        <td width="49" class="BarraBusqueda"><div align="right">Hasta: </div></td>
        <td width="107" class="BarraBusqueda"><input name="fin" type="text" id="fin" value="<?php if (isset($fin)){ echo $fin; }else{ echo date("Y-m-d"); } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" /></td>
        <td width="145" align="center" class="BarraBusqueda">
        <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda*ini*fin', 0)">
               <i class="icon-search icon-white"></i>
               <span>Buscar</span>
        </button> 
          <input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar" />
          <input name="op" type="hidden" id="op" value="<?php echo $op;?>" />
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
                <th width="5%" align="center">C&oacute;d. Int.</th>
                <th width="10%" align="center">C&eacute;dula/R.U.C.</th>
                <th width="20%" align="center">Proveedor</th>
                 <th width="10%" align="center">No. Ajuste</th>
                <th width="10%" align="center">Fecha</th>
                 <th width="5%" align="center">&nbsp;</th>
                <th width="5%" align="center">&nbsp;</th>
              </tr>
             </thead>
             <tbody>
              <?php  
              if(count($rs_buspro)!=0)
              {
              foreach($rs_buspro as $row_rs_buspro){ 
              $i++;
                 if($row_rs_buspro['Aju_Est']=='I')
                 { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
              ?>
              <tr>
                <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Aju_Cod']; ?></font></td>
                
                <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Prs_Ced']; ?></font></td>
                <td align="left"><font color="<?php echo $rojo; ?>">&nbsp;<?php echo marcar_cadena($txt_busqueda,$row_rs_buspro['Prs_Ape'].' '.$row_rs_buspro['Prs_Nom'],'#FFFF00', 1) ?></font></td>
                <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Aju_Sec']; ?></font></td>
                <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_buspro['Aju_Fec']; ?></font></td>
                 <td align="center">                 
                <button type="button" class="btn btn-info btn-mini" title="Ver detalle" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?ajx_det=<?php echo $row_rs_buspro['Aju_Cod'];?>&aju_det=<?php echo $row_rs_buspro['Aju_Det'];?>&op=<?php echo $op;?>','ajax_modal'); Muestra_Aparecer();" style="height:22px">
                        <i class="icon-info-sign icon-white"></i>
                    </button>
                </td>
                <td align="center">
                  <?Php if ($row_rs_buspro['Aju_Est'] == 'A') { ?>
                  <form name='frm_personal' method='post' action="fac_pri_aju_1.0.php" target="_blank">
                    <?php $thisPost->startPost();?>
                    <input type="hidden" name="Prv_Cod" id="Prv_Cod" value="<?Php echo $row_rs_buspro['Prv_Cod'];?>">
                    <input type="hidden" name="Aju_Cod" id="Aju_Cod" value="<?Php echo $row_rs_buspro['Aju_Cod'];?>">
                    <input type="hidden" name="hdd_save" id="hdd_save" value="">
                    <button type="button" class="btn btn-primary btn-mini" title="Imprimir Ajuste" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>                
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
 <?php break;
 	}
}?>
<?php } //fin if(count($rs_ann) > 0)?>
</td>
</tr>
</table>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
        <div id="ajax_modal">
        </div>
    </div>
</div>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_aju.js?z=10"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
<?Php
	/** 
	* Control para ocultar el detalle de las filas 
	*/
	if(isset($rs_buspro) && count($rs_buspro) != 0)
	{
		ocultarDetalle(count($rs_buspro));
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