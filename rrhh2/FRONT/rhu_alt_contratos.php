<?php  
/*
ALIAS: 
DESCRIPCIÓN: PERMITE REGISTRAR DICTRIBUTIVOS(CONTRATOS) PARA EL PERSONAL ADMINISTRATIVO
FECHA DE ACTUALIZACION: 2016-04-22
DESARROLLADOR: JOSE CUMBICOS*/


require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_contratos.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');
//require_once('../../componentes/LOGICA/logica.php');	
	   
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;
/* Creación del objeto para evitar el reenvio */
$thisPost = new Post_Block;

/* Cargar datos con AJAX */
if(isset($dep_ajx)){

	/* Cargado de departamentos */
	$rs_departamentos = $obBD_con1->consulta(sentencias_rhu(649, $obBD_con1->parametros($Are_Cod)), $obBD_conexion->conexion);
	$row_rs_departamentos = $obBD_con1->registros();
	$total_rs_departamentos = $obBD_con1->numregistros();
	?>
	<select name="Dep_Cod" id="Dep_Cod" style="text-transform:uppercase"  onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?car_ajx=1&person=<?Php echo $personal;?>&Are_Cod=<?Php echo $Are_Cod; ?>&Dep_Cod=' + this.value,'cargos')"  >
	<option value="">Seleccione...</option>
	<?Php do { //if ($row_rs_etapas['Eta_Cod']==1){ ?>
			<option value="<?Php echo $row_rs_departamentos['Dep_Cod']?>" ><?Php echo $row_rs_departamentos['Dep_Des'];  ?></option>
			<?Php //}
	}while($row_rs_departamentos=$obBD_con1->fetch_assoc($rs_departamentos));  ?>
	</select>
	<?Php		
exit();
}	  

if(isset($car_ajx)){

 /* Consulta de cargos */
$rs_cargos=$obBD_con1->consulta(sentencias_rhu(650, $obBD_con1->parametros($Dep_Cod)), $obBD_conexion->conexion);
$row_rs_cargos= $obBD_con1->registros();
$total_rs_cargos = $obBD_con1->numregistros(); 
 
/* Consulta si el personal ya tiene ese cargo */
$rs_verificardep = $obBD_con1->consulta(sentencias_rhu(657, $obBD_con1->parametros($person.'*'.$Dep_Cod)), $obBD_conexion->conexion);
$row_rs_verificardep = $obBD_con1->registros();
$total_rs_verificardep = $obBD_con1->numregistros();
?>
<input name="total_depart" id="total_depart" type="hidden" value="<?php echo $total_rs_verificardep; ?>" />
<?php if($total_rs_verificardep>0)
  {
		blink("El empleado ya tiene cargo en este departamento", "txt_blink", "#FFFF00", "#FF0000"); 
  }else{
?>
    <select name="Tic_Cod" style="text-transform:uppercase" id="Tic_Cod" >
        <option value="">Selecione...</option>
        <?Php do { //if ($row_rs_etapas['Eta_Cod']==1){ ?>
        <option value="<?Php echo $row_rs_cargos['Tic_Cod']?>" ><?Php echo $row_rs_cargos['Tic_Des'];  ?></option>
        <?Php //}
         }while($row_rs_cargos=$obBD_con1->fetch_assoc($rs_cargos));?>
    </select>
	<?Php }
	exit();
}	  

// Busqueda General de Datos
if ($txt_busqueda != "" )
{
	if ($op_opciones == "d")
	{
		/* BUSCA EL PERSONAL QUE EMPIEZA CON EL APELLIDO */			
		$rs_buscar = $obBD_con1->getArrayConsulta(646, trim($txt_busqueda).'*'.$Ses_Emp_Cod,$obBD_conexion);
	}
	if ($op_opciones == "r")
	{
		/* BUSCA EL PERSONAL QUE EMPIEZA CON EL CODIGO */
		$rs_buscar = $obBD_con1->getArrayConsulta(647, trim($txt_busqueda).'*'.$Ses_Emp_Cod,$obBD_conexion);
	}
    $total_rs_buscar = count($rs_buscar);
}	//if ($txt_busqueda != "")

if ($codigo>0)
{
  
  /****Cargo los datos del Personal******/
	$row_rs_consulta = $obBD_con1->getRowConsulta(645,$codigo.'*'.$Ses_Emp_Cod,$obBD_conexion);				
	$total_rs_consulta  = $row_rs_consulta['Prs_Cod']>0?1:0;
	/******Muestro los cargos que tiene un empleado de la UTSAM*******/
	$rs_consul_cargo = $obBD_con1->consulta(sentencias_rhu(644, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);			
	$row_rs_consul_cargo  = $obBD_con1->registros();
	$total_rs_consul_cargo  = $obBD_con1->numregistros();
	
	/* Consulta de las areas de departamento */
	$rs_areas=$obBD_con1->consulta(sentencias_rhu(648, ''), $obBD_conexion->conexion);
	$row_rs_areas = $obBD_con1->registros();
	$total_rs_areas = $obBD_con1->numregistros();  
	/* Consulta de  de relacion laboral */
	$rs_rela=$obBD_con1->consulta(sentencias_rhu(609, ''), $obBD_conexion->conexion);
	$row_rs_rela = $obBD_con1->registros();
	$total_rs_rela = $obBD_con1->numregistros();  
	/* Consulta de  de dedicion laboral */
	$rs_dedi=$obBD_con1->consulta(sentencias_rhu(610, $obBD_con1->parametros('')), $obBD_conexion->conexion);
	$row_rs_dedi = $obBD_con1->registros();
	$total_rs_dedi = $obBD_con1->numregistros();  
   
}// Fin del if (isset($codigo))
		
if ($thisPost->postBlock($_POST['postID'])) 
{	
	if(isset($hdd_save) && !isset($volver_op) )
	{

		/* Cracion del objeto mysql para las inserciones */
		$obBD_ins1 =  new Class_Log_Datos_Con; 
		
		/*Inicio de la transaccion*/
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		
		/* Inserción de distributivo */
		$obBD_ins1->grabarv_registros(sentencias_rhu(652, $obBD_ins1->parametros($Tic_Cod.'*'.$Ded_Cod.'*'.$Reb_Cod.'*'.$Con_Ini.'*'.$Con_Fin.'*'.$Cod_Per)),$obBD_conexion->conexion);	
		/*Obtener ultimo registro*/
		$Con_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);
		/* Inserción en tabla sueldo */
		$obBD_ins1->grabarv_registros(sentencias_rhu(653, $obBD_ins1->parametros($Sue_Val.'*'.'A'.'*'.date('Y-m-d').'*'.$Con_Cod)),$obBD_conexion->conexion);	
		
		/*Fin de la transaccion*/
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
		
	}
}
   
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>          
         <script type="text/javascript" src="../../Librerias/masked/jquery.maskedinput-1.2.2.js"></script> 					
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <script>
		$(function() { 
			//var imagen = "../../mascaras/model1/imagenes/32x32/calendar.gif";
			/* Campo 1 */
			$( "#Con_Ini" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});			
			
			/* Campo 2 */
			$( "#Con_Fin" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });						
		}); 		
        </script>   
        
         <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		}); 
		</script>
	</HEAD>
<body <?Php if (isset($car_ajx)){ ?> onLoad="if (document.getElementById('total_depart').value == 0){ setInterval('parpadeo(\'txt_blink\')',500) }" <?php } ?>>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">Registro de contratos del personal </td>
	</tr>
	<tr>	  	
        <td height="389" valign="top">
         <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" id="form1">
            
    <?php include('../../componentes/FRONT/com_con_persona.php');?>
	    
	</form>
  <input type="hidden" name="cod_bus" id="cod_bus" value="<?php echo $txt_busqueda;?>"/>
  <input type="hidden" name="cod_op" id="cod_op" value="<?php echo $op_opciones;?>"/>
    <?Php
  	if(isset($txt_busqueda))
	{
	
  ?>
	
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
      <thead>
      <tr>
        <th width="12%">Cód. Int.</th>
		<th width="16%">C&eacute;dula/RUC</th>
        <th width="69%">Personal</th>
        <th width="3%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	 <? if ($total_rs_buscar > 0)
		{
		 foreach($rs_buscar as $row_rs_buscar){
	 ?>       
      <tr> 
	    <td width="12%" align="center" ><?Php echo $row_rs_buscar['Per_Cod']; ?></td>
	    <td width="16%" align="center" ><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
        <td  width="69%" align="left"><? echo marcar_cadena($txt_busqueda,$row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom'],'#FFFF00',1);?></td        
        ><td width="3%" align="center">
        <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" id="form1">		
		<input type="hidden" name="cod_bus" id="cod_bus" value="<?php echo $txt_busqueda;?>"/>
		<input type="hidden" name="cod_op" id="cod_op" value="<?php echo $op_opciones;?>"/>	
		<input type="hidden" name="codigo" id="codigo" value=<?php echo $row_rs_buscar['Per_Cod']; ?> /> 
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()"><i class=" icon-arrow-right icon-white"></i>
        	</button>        		
        </form>
        </td>        
      </tr>	 	  
      <? }
	 }else{?>
     <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td align="center"><?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($txt_busqueda)." ".$codigo, 1);?></td>
        <td>&nbsp;</td>
     </tr>
     <? }?>
     <tbody>
    </table>
    </FIELDSET>
    <?Php 
  	
 }
  ?>  
  	  		   		
		
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name="form2" id="form2">
<?php  //Creacion del campo REPOST
$thisPost->startPost(); ?>
<?Php if ($total_rs_consulta > 0) { ?>		  
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Personal</label>
</LEGEND>
<table border="0" width="100%">
     <tr bordercolor="#FFFFFF">
        <td width="11%" class="Etiqueta1">C&eacute;dula/ RUC: </td>
        <td width="89%" class="LetraNegra"> <?Php echo $row_rs_consulta['Prs_Ced'];?>
          <input type="hidden" name="codigo2" value="<?php echo $codigo; ?>" /></td>
     </tr>
      <tr bordercolor="#FFFFFF">
        <td class="Etiqueta1">Personal: </td>
        <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Ape']." ".$row_rs_consulta['Prs_Nom']; ?></td>
      </tr>       
  </table> 
	
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Laborales</label>
</LEGEND>
<table border="0" width="100%">
     <tr bordercolor="#FFFFFF">
        <td width="12%" class="Etiqueta1"><span class="Asterisco">* </span>Area: </td>
        <td width="88%" class="LetraNegra">
		<select name="Are_Cod" id="Are_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?dep_ajx=1&personal=<?php echo $codigo; ?>&Are_Cod=' + this.value,'departamento')">
          <option value="">Seleccione...</option>
          <?php
			do {  
		?>
          <option value="<?php echo $row_rs_areas['Are_Cod']?>"><?php echo $row_rs_areas['Are_Des']?></option>
          <?php
			} while ($row_rs_areas = $obBD_con1->fetch_assoc($rs_areas)); ?>
        </select></td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="Etiqueta1"><span class="Asterisco">* </span>Departamento:</td>
        <td class="LetraNegra">
		
		<div id="departamento">
		  <select name="Dep_Cod"  >
			<option></option>
		  </select>
        </div>		</td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="Etiqueta1"><span class="Asterisco">* </span>Cargo: </td>
        <td class="LetraNegra">
		<div id="cargos">
          <select name="Tic_Cod"  >
            <option></option>
          </select>
        </div>		</td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="Etiqueta1"><span class="Asterisco">* </span>Dedicaci&oacute;n laboral </td>
        <td class="LetraNegra"><select name="Ded_Cod" id="Ded_Cod">
          <option value="">Seleccione...</option>
          <?php
			do {  
		?>
          <option value="<?php echo $row_rs_dedi['Ded_Cod']?>"><?php echo $row_rs_dedi['Ded_Des'].' ('.$row_rs_dedi['Ded_Hrs'].' Hrs)'?></option>
          <?php
			} while ($row_rs_dedi = $obBD_con1->fetch_assoc($rs_dedi));	?>
        </select></td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="Etiqueta1"><span class="Asterisco">* </span>Relaci&oacute;n Laboral </td>
        <td class="LetraNegra"><select name="Reb_Cod" id="Reb_Cod">
          <option value="">Seleccione...</option>
          <?php
			do {  
		  ?>
          <option value="<?php echo $row_rs_rela['Reb_Cod']?>"><?php echo $row_rs_rela['Reb_Des']?></option>
          <?php
			} while ($row_rs_rela = $obBD_con1->fetch_assoc($rs_rela)); ?>
        </select></td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="Etiqueta1"><span class="Asterisco">* </span>Fecha de inicio laboral </td>
        <td class="LetraNegra">
		<input name="Con_Ini" type="text" id="Con_Ini" value="<?php //echo date("Y-m-d"); ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"></td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td height="25" class="Etiqueta1">Fecha de fin Laboral: </td>
        <td class="LetraNegra">
		<input name="Con_Fin" type="text" id="Con_Fin" value="<?php //echo date("Y-m-d"); ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"></td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="Etiqueta1"><span class="Asterisco">* </span>Sueldo:</td>
        <td class="LetraNegra">
          <input name="Sue_Val" type="text" id="Sue_Val" size="8" maxlength="8" onKeyPress="return validar_decimal(event)"  style="text-align:right">
          <input type="hidden" name="Cod_Per" id="Cod_Per"  value="<?php echo $codigo; ?>">		  
		  </td>
      </tr>       
  </table> 
  <br />
</FIELDSET>
<table>
   <tr>
     <td>
	   <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_volver(this.form, '<?Php echo "txt_busqueda*op_opciones*volver_op"; ?>', '<?Php echo $cod_bus.'*'.$cod_op.'*'.'1'; ?>');">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
       
       <input type="hidden" name="hdd_volver" id="hdd_volver" value="2">	 
	   </td>	
	 <td>	  
       <button type="button" class="btn btn-primary start" title="Guardar" name="btn_guardar" onClick="validar_contratos(this.form);">
   <i class="icon-book icon-white"></i>
   <span>Guardar</span>
       
	   <input type="hidden" name="hdd_save" id="hdd_save" value="1">
       
	   </td>
   </tr>
 </table>
<?Php } ?>		
<br />
</form></td>
  </tr>

</table>
</body>
</html>
<?Php
/* libero los cursores de la base de datos */
@$obBD_con1->free_result($rs_buscar);
@$obBD_con1->free_result($rs_consulta);
@$obBD_con1->free_result($rs_exist);

/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>
