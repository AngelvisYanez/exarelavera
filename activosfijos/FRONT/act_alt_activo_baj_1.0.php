<?php 
/** 
 * Alias:	Insertarr
 * Descripci�n: Permite el ingreso de mantenimiento del activo.
 * Desarrollador:	Didimo Zamora
 * **********************************
 * Fecha de actualizaci�n:	2011-04-21
 * Desarrollador: D�dimo Zamora M.
 * Fecha de actualizaci�n:	2013-05-28
 * Fecha de actualizaci�n:	2013-08-07
 */
 
//Variables de Sesion estaticas 
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo_baj.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	 	

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;
/** 
 * Creaci�n del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;
/**
 * Muestra modal para dar de alta el mantenimiento de activo.
 */
?>

<?Php
$hoy = date("Y-m-d");

if ($thisPost->postBlock($_POST['postID'])){ 
	
	if (isset($hdd_save) && !isset($hdd_volver)){ 
		
				$obBD_con1->inicio_transaccion($obBD_conexion->conexion);				
				/** 
				 * Insercion de la baja del activo.
				 */   
				 
				 switch($destino){
					 case 0:
					 	$Baj_Des="Donaci�n";
					 break;
					 case 1:
					 	$Baj_Des="Destrucci�n";
					 break;
					 case 2:
					 	$Baj_Des="Venta";
					 break;
					 case 3:
					 	$Baj_Des="Descuento";
					 break;
				 }
				 
				 
				 
				 if($destino==0){
					 $Baj_Val=0;					 
					 $obBD_con1->operacionobBD(1, $Act_Cod.'*'.$hoy.'*'.$Baj_Fba.'*'.$Baj_Inf.'*'.$Baj_Des.'*'.$Baj_Qui.'*'.$Baj_Val, $obBD_conexion);
				 }
				 
				  if($destino==1){
					 $Baj_Val=0;
					 $obBD_con1->operacionobBD(1, $Act_Cod.'*'.$hoy.'*'.$Baj_Fba.'*'.$Baj_Inf.'*'.$Baj_Des.'*'.$Baj_Qui.'*'.$Baj_Val, $obBD_conexion);
					 
				 } if($destino==2 || $destino==3){
					 $obBD_con1->operacionobBD(1, $Act_Cod.'*'.$hoy.'*'.$Baj_Fba.'*'.$Baj_Inf.'*'.$Baj_Des.'*'.$Baj_Qui.'*'.$Baj_Val, $obBD_conexion);
				 }	
				/**
				 * Dar de baja logicamente del activo. el estado del Activo.
				 */
				 $obBD_con1->operacionobBD(2, $Act_Cod, $obBD_conexion);
				 
				  /**
				  * Actualizar el estado del activo segun el motivo de la baja
				  */
				  $obBD_con1->operacionobBD(6, $Baj_Mot.'*'.$Act_Cod, $obBD_conexion);
				 		
				$obBD_con1->fin_transaccion($obBD_conexion->conexion);	
				unset($txt_busqueda);	
	 }
}
	
	/**
	 * Si se ha ingresado valor  la busqueda
	 */ 
	 if($txt_busqueda != "")
	 { 	 
	 	if ($op_opciones == "d")
		{			
			/**
			 * Busqueda del activo x medio de la descripcion
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(3,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}
		if ($op_opciones == "cs")
		{			
			/**
			 * Busqueda del activo x medio del codigo secuencial
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(435,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);					
		}
		if ($op_opciones == "cb")
		{			
			/**
			 * Busqueda del activo x medio del codigo de barra
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(436,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}	
		if ($op_opciones == "ns")
		{			
			if (isset($Cam_Cod)){
			 /**
			  * Busqueda del activo x medio del  c�digo del campo
			  */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(471,$Cam_Cod.'*'.$txt_busqueda, $obBD_conexion);		
			}
		}			
		$total_rs_buscar = count($rs_buscar);	
	 }else
		{
		/** 
		 * Consulta realizada en base al c�digo seleccionado 
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
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
			<?Php require_once("../../mascaras/model1/estilos/estilos.php");?>
            <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
            <script type="text/javascript" src="../VALIDACIONES/act_val_activo_baj.js"></script>
            <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>            
            <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>             
            <script type="text/javascript"> 
			  $(function() {
					$('#set1 *').tooltip({showURL: false});
			  });              			
			</script>   
            <script>
				$(function() { 
					 $( "#Baj_Fba" ).datepicker({
					  changeMonth:true, 
					  changeYear:true, 
					  dateFormat: "yy-mm-dd",
					  minDate: new Date(),
					 });
					 });
			</script>                                                                           
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
	<tr class="BarraTitulo">
	  <td colspan="3" height="10">&raquo; Dar Baja de Activo Fijo</td>
    </tr>
    <tr>
		<td valign="top">  
		<fieldset>
		<LEGEND>
			<label class="Titulos2">Buscar activo por:&nbsp;</label>
		</LEGEND>
        
		<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
        <?Php
         //Creacion del campo REPOST
		$thisPost->startPost();
		?>
		<table width="762" height="72" border="0" cellpadding="0" cellspacing="0">
		<tr>    
		<td colspan="3">
			<table width="633" border="0">
				<tr>
					<td width="105"><input name="op_opciones" type="radio" value="d"  checked  onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">              
                    <span class="LetraNegra">Descripci�n</span> <input name="op_cam" id="op_cam" type="hidden" value="d"></td>
					<td width="125"><input type="radio" name="op_opciones" value="cb" <?Php if($op_opciones== 'cb'){?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">C�digo de Barra</span></td>
					<td width="122"><input type="radio" name="op_opciones" value="cs" <?Php if($op_opciones== 'cs'){?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);" >
                    <span class="LetraNegra">C�digo Secuencial</span></td>
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
			<th width="5%">C�d. Int.</th>
			<th width="35">SubGrupo</th>
			<th width="40">Descripci&oacute;n </th>
			<th width="20">Secuencial</th>
			<th>&nbsp;</th>
		</tr>
		</thead>
		<tbody>
<?Php 
		$anulada=0;
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
			<td title="<?php echo $row_rs_buscar['Tia_Des'];?>"><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Tia_Des'],'#FFFF00', 1);?></FONT></td>
			<td  title="<?php echo $row_rs_buscar['Act_Des'];?>"><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Act_Des'],'#FFFF00', 1);?></FONT></td>
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
			<td align="center"><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
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
?>

<?Php 
if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
<br/>
<?php
require_once('../../componentes/FRONT/com_con_leyenda.php');?> 



 
<?Php  
 if ($hdd_aux==1){ 
?>
	<form method="post" name= "form2" action="<?php echo $_SERVER['PHP_SELF'];?>">
<?php //Creacion del campo REPOST
	$thisPost->startPost();
?>
	<fieldset>
		<LEGEND>
		<label class="Titulos2">Datos del Activo</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="3"> </td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"> C�digo Activo:</td>
			<td class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Cdc"]?>
            <input id="Act_Cod" name="Act_Cod" type="hidden" value="<?Php echo $codigo;?>" ></td>
			<td></td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"> Descripci�n:</td>
			<td class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Des"]?></td>
			<td></td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"> Tipo de Activo:</td>
			<td> <label class="Titulos2">&nbsp;<?php echo $row_rs_consultar["Tia_Des"];?></label></td>
			<td></td>
		</tr>
		</table>
 
 </fieldset> 
 
 <fieldset>
		<LEGEND>
		<label class="Titulos2">Datos de la Baja del Activo</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="3"> </td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Motivo:</td>
			<td width="80%" class="LetraNegra">&nbsp;<?Php
                	$rs_motivoBaj=$obBD_con1->getArrayConsulta(423,"",$obBD_conexion);				
				?><select name="Baj_Mot" id="Baj_Mot" >
                   <option value="">Seleccione..</option>
                <?php
				foreach($rs_motivoBaj as $row_rs_motivoBaj){
				?>
            		<option value="<?Php echo $row_rs_motivoBaj['Est_Cod'] ?>"><?Php echo $row_rs_motivoBaj['Est_Des'];?></option>
                <?php
                }
                ?>
                </select>
            </td>
			<td width="0%"></td>
		</tr>
        <tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Fecha del Informe:</td>
			<td>&nbsp;<input name="Baj_Fba" type="text" id="Baj_Fba" value="<?php echo date("Y-m-d");?>"></td>
			<td></td>
		</tr>       
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Informe T�cnico:</td>
			<td class="LetraNegra">&nbsp;<textarea id="Baj_Inf" name="Baj_Inf" cols="100" rows="10"></textarea></td>
			<td></td>
		</tr>       
		</table>
 </fieldset>
 <fieldset>
		<LEGEND>
		<label class="Titulos2">Destino del activo dado de baja</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Destino:</td>
			<td width="80%" class="LetraNegra">
       	    	<select name="Baj_Des" id="Baj_Des"  onChange="document.getElementById('destino').value= this.value;habilitar_text(this.value)">
            		<option value="" >Seleccone...</option>  
                    <option value="0" >Donaci�n</option>     
                    <option value="1" >Destrucci�n</option> 
                    <option value="2" >Venta</option> 
                    <option value="3" >Descuento</option>                                  
                </select> 
            </td>			
		</tr>
		<tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Receptor:</td>
			<td class="LetraNegra"><input name="Baj_Qui" id="Baj_Qui" type="text" size="50"></td>			
		</tr>
        <tr>
			<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Valor:</td>
			<td class="LetraNegra"><input name="Baj_Val" id="Baj_Val" value="" type="text" size="10" onBlur="numerico(this)"></td>			
		</tr>
	</table>
 </fieldset>
 <input id="hdd_save" name="hdd_save" type="hidden" value="1">
  <input id="destino" name="destino" type="hidden" value="">
 </form>
	<table  width="220">
        <tr>
            <td width="49%" align="left">   
                <form method="post" name= "form3" action="<?php echo $_SERVER['PHP_SELF'];?>">	
                        <input id="hdd_volver" name="hdd_volver" type="hidden" value="0">                         
                    <button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
                    onClick="campos_hide(form2, '<?Php echo "op_opciones*txt_busqueda*hdd_volver"; ?>','<?Php echo $volver_opciones.'*'.$volver_busqueda.'*'.$hdd_volver;?>')">
                     <i class="icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;&nbsp;</span>
                    </button>
                 </form>                            
            </td>            
            <td width="51%" >  
             <form method="post" name= "form4" action="<?php echo $_SERVER['PHP_SELF'];?>">	                               
                <button name="boton_guardar" id="boton_guardar" type="button"  class="btn btn-primary fileinput-button" title="Guardar" value="Guardar" onClick="validar_requeridos_baja(form2,validar_opciones(document.getElementById('destino').value),1)"> 
                <i class="icon-book icon-white"></i>
                <span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span>
                </button>                          
              </form>                           
            </td>                        
        </tr> 
	</table> 
 <?php
 }
 ?>
 
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/act_par_activo_baj.js"></script>
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