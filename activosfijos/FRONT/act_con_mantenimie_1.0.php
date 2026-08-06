<?php 
/** 
 * Alias:	Insertar
 * Descripción: Permite la consulta de mantenimiento del activo.
 * por Descripción, Códiog de Barra, Código Secuencial, y por Campos e impresión de la consulta.
 * Desarrollador:	Didimo Zamora
 *
 ***************************************
 * Fecha de actualización:	2011-04-21 *
 * Desarrollador: Dídimo Zamora M.     *
 * Fecha de actualización:	2013-05-28 *
 ***************************************/
  
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_mantenimie.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	 	


/**
 *Configuración de inicio de pestaña posicion 1
 */
if(!isset($op))
{	
	$op =1;	
}

/** 
 *Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Mantenimiento($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Mantenimiento;
/**
 * Creación del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;  
	
	if($txt_busqueda != "")
	 { 	 
	 	if ($op_opciones == "d")
		{			
			/**
			 * Busqueda del activo x medio de la descripcion
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(482,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}
		if ($op_opciones == "cs")
		{			
			/**
			 * Busqueda del activo x medio del codigo secuencial
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(485,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);					
		}
		if ($op_opciones == "cb")
		{			
			/**
			 * Busqueda del activo x medio del codigo de barra
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(486,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}	
		if ($op_opciones == "ns")
		{			
			if (isset($Cam_Cod)){
			 /**
			  * Busqueda del activo x medio del codigo de barra
			  */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(487,$Cam_Cod.'*'.$txt_busqueda, $obBD_conexion);		
			}
		}	
		$total_rs_buscar = count($rs_buscar);	
	 }else
		{
			/** 
			 * Consulta realizada en base al código seleccionado 
			 */
			if (isset($codigo))
			{
				$rs_consultar = $obBD_con1->getRowConsulta(431, $codigo, $obBD_conexion);
				$row_rs_consultar = $rs_consultar;
				$total_rs_consultar =count($codigo);
			}
		}	

?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
			<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
			<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
			<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
			<script language="javascript" src="../VALIDACIONES/act_val_mantenimie.js"></script>        
			<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
			<script type="text/javascript">$(function() { $('#set1 *').tooltip({showURL: false}); });</script>
			<script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
			<script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>        
			<script>
				$(function() { 
				$( "#Fec1" ).datepicker({changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});
				 });		 
				 $(function() { 
				$( "#Fec2" ).datepicker({
					changeMonth:true,
					changeYear:true,
					dateFormat: "yy-mm-dd"});
				 });
			</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>


<div id='set1'>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
		<td height="10">&raquo; Consulta de Mantenimientos de Activos Fijos </td>
	</tr>
	<tr>
		<td valign="top"> 
  	<?php
		$descripcion = "Individual*Por fecha";
  		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		tabs(2,$descripcion, $pag1.'*'.$pag2, $op);
	?>





<?php
	if(!isset($op)){$op = 1;}
		if ($op==1 || $op==2 || $op==3 ){
		switch($op) {			
			case 1: 
?>         
		<fieldset>
		<LEGEND>
			<label class="Titulos2">Buscar por:&nbsp;</label>
		</LEGEND>
		<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
		<table width="762" height="72" border="0" cellpadding="0" cellspacing="0">
		<tr>    
		<td colspan="3">
			<table width="633" border="0">
				<tr>
					<td width="105"><input name="op_opciones" type="radio" value="d"   checked  onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">              
                    <span class="LetraNegra">Descripción</span> <input name="op_cam" id="op_cam" type="hidden" value="d"></td>
                    	
					<td width="125"><input type="radio" name="op_opciones" value="cb" <?Php if($op_opciones== 'cb'){?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">Código de Barra</span></td>
					<td width="122"><input type="radio" name="op_opciones" value="cs" <?Php if($op_opciones== 'cs'){?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);" >
                    <span class="LetraNegra">Código Secuencial</span></td>
                    <td width="263"><input type="radio" name="op_opciones" value="ns" <?Php if($op_opciones== 'ns'){  ?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.Cam_Cod);">
                    <span class="LetraNegra">Por Campo</span>
<?Php
					/**
					 * consulto los campos que esten definidos como busqueda 
					 */
					$rs_campos=$obBD_con1->getArrayConsulta(470, '', $obBD_conexion);
?>
                    <select name="Cam_Cod" id="Cam_Cod" onChange="setfocus(this.form.txt_busqueda);">
<?Php 
						foreach($rs_campos as $row_rs_campos){
?>  
							<option  value="<?php echo $row_rs_campos['Cam_Cod'];?>"><?PHP  echo $row_rs_campos['Cam_Cor'];?></option>
<?Php 
						} //fin foreach($rs_campos as $row_rs_campos){
?> 
                    </select>
					</td>
				</tr>             
			</table>
            </td>
            </tr>
            <tr>
                <td width="119" height="44"class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Activo: </div></td>
                <td width="368"  class="BarraBusqueda">&nbsp;<input size="50" name="txt_busqueda" type="text" id="txt_busqueda">
                </td>
                <td align="left" width="144" class="BarraBusqueda">
				<div align="left">
				<button name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Listar Activos">
				<i class="icon-search icon-white"></i>
				<span>&nbsp;Buscar&nbsp;</span>
				</button>
				</div>
			</td>
			<td width="131"></td>
		</tr>
	</table>
	<script> 
        ShowHide('Cam_Cod');
    </script>
		</form>
     </fieldset>   
        
<?php 
		if (isset($txt_busqueda)){?>
			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
			</LEGEND>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
				<thead>
				<tr>
					<th width="5%">Cód. Int.</th>
					<th>SubGrupo</th>
					<th>Descripci&oacute;n </th>
					<th>Secuencial</th>
					<th>&nbsp;</th>
				</tr>
				</thead>
				<tbody>
<?Php 
				if ($total_rs_buscar > 0){  
					foreach($rs_buscar as $row_rs_buscar){   	
						if($row_rs_buscar['Act_Est']=='I'){ 
							$rojo='#FF0000'; $anulada++; 
						}else{
							$rojo='';
							 }
?>
                <tr>
                    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Act_Cod'];?></FONT></td>
                    <td><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Tia_Des'],'#FFFF00', 1);?></FONT></td>
                    <td><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Act_Des'],'#FFFF00', 1);?></FONT></td>
                    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo  $row_rs_buscar['Act_Cdc'];?></FONT></td>
                    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
                    <td align="center" width="5%">
<?Php 
					if($row_rs_buscar['Act_Est']=='A'){ 
?>
                    <button type="button" name="imageField"  class="btn btn-success btn-mini"  width="22" height="22" title="Seleccionar" onClick="this.form.submit()">	
                        <i class="icon-arrow-right icon-white"></i>
                    </button>  				
                    <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Act_Cod'];?>"/>
                    <input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
                    <input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
                    <input type="hidden" name="volver_opciones" id="volver_opciones" value="<?php echo $op_opciones;?>">
                    <input type="hidden" name="volver_Cam_Cod" id="volver_Cam_Cod" value="<?php echo $Cam_Cod;?>">
<?Php
				}
				else{ echo "&nbsp;";}
?>		     
				</td>
				</form>
				</tr>
<?Php 
				} //Fin foreach($row_rs_buscar as $row_rs_buscar){      
			}else{
?>
            <tr>
                <td> </td>
                <td> </td>
                <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
                <td> </td>
                <td> </td>
            </tr>
<?php
		} // fin del if ($total_rs_buscar > 0)
?>
		</tbody>
		</table>
<?Php
	/**
	* Muestra la barra de estados con la cantidad de registros encontrados 
	*/
	echo barra_estado($total_rs_buscar+0);
?>
	</FIELDSET>
<?php
}
if ($hdd_aux==1){ 
	$rs_mant=$obBD_con1->getArrayConsulta(484,$codigo,$obBD_conexion);
	$rs_mant_Tot=count($rs_mant);
?>
	<form method="post" name= "form2" action="<?php echo $_SERVER['PHP_SELF'];?>">
<?php //Creacion del campo REPOST
	$thisPost->startPost();

?>
    <br>
     
    <FIELDSET>
            <LEGEND>
                <label class="Titulos2">Mantenimientos por ejecutarse</label>
            </LEGEND>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td class="Etiqueta1"> Código Activo:</td>
                <td width="23%" class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Cdc"]?></td>
                <td width="7%" class="Etiqueta1">Descripci&oacute;n:</td>
                <td width="61%" class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Des"]?> </td>
            </tr>		
            <tr>
                <td width="9%" class="Etiqueta1">Tipo de Activo:</td>
                <td class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Tia_Des"];?></td>
                <td width="7%" align="left" class="Etiqueta1"></td>
                <td width="61%">&nbsp;</td>
            </tr>
            <tr>
                <td width="9%" align="left" class="Etiqueta1">Proveedor:</td>
                <td class="LetraNegra">&nbsp;<?php
               /**
                * Consulta los Proveedores por codigo del proveedor
                */
                $rs_prv_act = $obBD_con1->getRowConsulta(467,$Ses_Emp_Cod.'*'.$rs_consultar["Prv_Cod"], $obBD_conexion);
                echo $rs_prv_act['Nombre'];
?>
                </td>
                <td class="Etiqueta1">Custodio :</td>
                <td>&nbsp;<span class="LetraNegra">
                <?php 
                /** 
                 * Consulta el  Custodio 
                 */
                $rs_cus_act = $obBD_con1->getRowConsulta(432,$rs_consultar["Act_Cod"], $obBD_conexion);
                echo $rs_cus_act["Nombre"];  ?>
                </span></td>
            </tr>       
		</table>
        <br>
            
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
            <thead>
              <th width="5%">Cod.Int.</th>
                <th width="8%">Fecha Inicio</th>
                <th width="10%">Estado del Activo</th>
                <th width="8%">Fecha Fin</th>
                <th width="20%">Encargado/Empresa</th> 
                <th width="20%">Detalles</th> 
                <th width="24%">Observaciones</th>               
                
            </thead>
            <tbody>
<?Php
		if($rs_mant_Tot>0){
			foreach($rs_mant as $row_rs_mant){	
				 $cantLetras= strlen($row_rs_mant['Man_Des']);
				  if ($cantLetras>30){
					  /**
					   * cortar cadena 
					   */
					  $Man_Des_Aux= cortar_cadena(0,30,$row_rs_mant['Man_Des'])."...";
				  }else
				  {
					  $Man_Des_Aux= $row_rs_mant['Man_Des'];
				  }
				  
				  /**
				   * Para las observaciones del mantenimiento
				   */
				   $cantLetras= strlen($row_rs_mant['Man_Obs']);
				  if ($cantLetras>30){
					  /**
					   * cortar cadena 
					   */
					  $Man_Obs_Aux= cortar_cadena(0,30,$row_rs_mant['Man_Obs'])."...";
				  }else
				  {
					  $Man_Obs_Aux= $row_rs_mant['Man_Obs'];
				  }			
?>
            <tr>
                <td align="center"><?php echo $row_rs_mant['Man_Cod'];?></td>
                <td align="center"><?php echo $row_rs_mant['Man_Fec'];?></td>
                 <td align="center"><?php echo $row_rs_mant['Est_Des'];?></td>
                <td align="center"><?php echo $row_rs_mant['Man_Fet'];?></td>
                <td align="left"><?php echo $row_rs_mant['Encargado'];?></td>
                 <td align="left" title="<?Php echo $row_rs_mant['Man_Des'];?>"><?php echo $Man_Des_Aux;?></td>
                  <td align="left" title="<?php echo $row_rs_mant['Man_Obs'];?>"><?php echo $Man_Obs_Aux;?></td>
               
                
            </tr>
<?Php
			}
		}else{
?>			 
			<tr>
            	<td align="center" class="LetraNegra">&nbsp;</td>
            	<td align="center" class="LetraNegra">&nbsp;</td>
            	<td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
			</tr>
<?Php
		}
?>
            </tbody>
          	</table>            
<?Php
			if($rs_mant_Tot>0){
				echo barra_estado($rs_mant_Tot);	
			}
?>
	</FIELDSET>  
</form>	
<br> 
                <table width="227" height="36" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="42%">
                        <form name="form10" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
                            <button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(form2, '<?Php echo "txt_busqueda*op_opciones*hdd_volver*Cam_Cod"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'.'*'.$volver_Cam_Cod; ?>')">
                            <i class=" icon-arrow-left icon-white"></i>
                            <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                            </button>
                        </form>
                        </td>
                        <td width="58%" align="left" height="23">  
                            <form name="form11" method="post" action="<?php echo 'act_pri_mantenimien_1.0.php';?>" target="_blank">          
                                <button title="Imprimir Mantenimiento" name="btn_imprimir" id="btn_imprimir" type="submit" class="btn btn-primary start" value="Selec">
                                <i class="icon-print icon-white"></i>
                                    <span>&nbsp;Imprimir&nbsp;</span>
                                </button>
                                <input type="hidden" id="Man_Cod_Aux" name="Man_Cod_Aux" value="<?Php echo $codigo;?>">
                            </form>           
                        </td>
                    </tr>
                </table>

<?Php 
			if ($anulada > 0)
			{		
				$com_leyenda[1]=$anulada;
			}//Fin del if ($anulada > 0)
			?>

<?php
require_once('../../componentes/FRONT/com_con_leyenda.php');  


?>
        
<?Php
}
			break;
			//Consultar por fechas los mantenimientos
			case 2:
				if(!isset($Fec1)){
					$Fec1= date("Y")."-".date("m")."-01";
					$Fec2= date("Y")."-".date("m")."-".date("d");
				}							
				if(isset($ok)){						
					/**
					 * Consultar los manteniminetos por ejecutarse  segun fecha
					 */
					$rs_mantFec=$obBD_con1->getArrayConsulta(504,$Fec1.'*'.$Fec2,$obBD_conexion);				
					}
			
?>	
		<fieldset>
				<LEGEND>
					<label class="Titulos2">Buscar por fecha:&nbsp;</label>
				</LEGEND>
					<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
						<table width="100%" border="0">
								<tr>
									<td width="155"><span class="LetraNegra">Fecha Inicio:</span> <input name="Fec1" type="text" id="Fec1" value="<?Php echo $Fec1;?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
                                </td>
                                <td width="142"><span class="LetraNegra">Fecha Fin:</span><input name="Fec2" type="text" id="Fec2" value="<?Php echo $Fec2;?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
                                </td>
								<td width="547">
                                    <button name="btn_aceptar" class="btn btn-success fileinput-button" id="btn_aceptar" title="Mostrar Controles de Tenencia de Activos Fijos" onClick="validar_requeridos(this.form,'$txt_busqueda',0)">
                                    <i class="icon-search icon-white"></i>
                                    <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>   
                                    </button>
									<input name="ok" id="ok" type="hidden" value="0">
                                    <input name="op" id="op" type="hidden" value="<?Php echo $op;?>">
								</td>
							</tr>
						</table>                
					</form>
				</FIELDSET>	
          <FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND> 
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
            <thead>
              <th width="3%">Cod.Int</th>
                <th width="7%">Fecha Ini</th>
                <th width="11%">Secuencial</th>
                <th width="17%">Activo Fijo</th>
                <th width="7%">Fecha Fin</th>
                <th width="17%">Encargado/Empresa</th> 
                <th width="15%">Detalles</th> 
                <th width="15%">Observaciones</th>               
                <th width="8%">Estado</th>
            </thead>
            <tbody>
<?Php
		if(count($rs_mantFec)>0){
			foreach($rs_mantFec as $row_rs_mantFec){	
			 if($row_rs_mantFec['Man_Pro']==1){
				  $rojo='#FF0000';}else{$rojo='#0C0';}
				  $cantLetras= strlen($row_rs_mantFec['Man_Des']);
				  if ($cantLetras>30){
					  /**
					   * cortar cadena 
					   */
					  $Man_Des_Aux= cortar_cadena(0,30,$row_rs_mantFec['Man_Des'])."...";
				  }else
				  {
					  $Man_Des_Aux= $row_rs_mantFec['Man_Des'];
				  }
				  
				  /**
				   * Para las observaciones del mantenimiento
				   */
				   $cantLetras= strlen($row_rs_mantFec['Man_Obs']);
				  if ($cantLetras>30){
					  /**
					   * cortar cadena 
					   */
					  $Man_Obs_Aux= cortar_cadena(0,30,$row_rs_mantFec['Man_Obs'])."...";
				  }else
				  {
					  $Man_Obs_Aux= $row_rs_mantFec['Man_Obs'];
				  }
?>
            <tr>
                <td align="center"><?php echo $row_rs_mantFec['Act_Cod'];?></td>
                <td align="center"><?php echo $row_rs_mantFec['Man_Fec'];?></td>
                <td align="center"><?php echo $row_rs_mantFec['Act_Cdc'];?></td>
                <td align="left"><?php echo $row_rs_mantFec['Act_Des'];?></td>
                <td align="center"><?php echo $row_rs_mantFec['Man_Fet'];?></td>
                <td align="left"><?php echo $row_rs_mantFec['Encargado'];?></td>
                <td align="left" title="<?Php echo $row_rs_mantFec['Man_Des'];?>"><?php echo $Man_Des_Aux;?></td>
                <td align="left" title="<?php echo $row_rs_mantFec['Man_Obs'];?>"><?php echo $Man_Obs_Aux;?></td>               
                <td style="color:     <?php echo $rojo;?>"   ><?php if($row_rs_mantFec['Man_Pro']==1){ echo "FINALIZADO";}else{ echo "EN PROCESO";} ?>
                	<input id="Man_Cod" name="Man_Cod" value="<?Php echo $row_rs_mantFec['Man_Cod'];?>" type="hidden">
                </td>
            </tr>
<?Php
			}
		}else{
?>			 
			<tr>
            	<td align="center" class="LetraNegra">&nbsp;</td>
            	<td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
            	<td align="center" class="LetraNegra"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
			</tr>
<?Php
		}
?>          </tbody>
          	</table>                     
          </FIELDSET>
<?Php
			/**
			* Muestra la barra de estados con la cantidad de registros encontrados 
			*/
	echo barra_estado(count($rs_mantFec)+0);
	?>   
                              
	<?Php		
		break;	
	}// Fin de 
}//Fin de 
?>
	</td>
    </tr>
   </table>	
</div>	  	          
	<script type="text/javascript" src="../VALIDACIONES/act_par_mantenimie.js"></script>
    <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>

<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>