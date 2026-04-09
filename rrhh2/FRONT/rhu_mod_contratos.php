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

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;
/* Creación del objeto para evitar el reenvio */
$thisPost = new Post_Block;

/*Cargar datos con AJAX */
if(isset($dep_ajx)){
/*Cargado de etapas*/
	$rs_departamentos = $obBD_con1->consulta(sentencias_rhu(649, $obBD_con1->parametros($Are_Cod)), $obBD_conexion->conexion);
	$row_rs_departamentos = $obBD_con1->registros();
	$total_rs_departamentos = $obBD_con1->numregistros();
?>
  <select name="Dep_Cod" id="Dep_Cod" style="text-transform:uppercase"  onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?car_ajx=1&Are_Cod=<?Php echo $Are_Cod; ?>&Dep_Cod=' + this.value,'cargos')" >
            <option></option>
            <?Php do { //if ($row_rs_etapas['Eta_Cod']==1){ ?>
            <option value="<?Php echo $row_rs_departamentos['Dep_Cod']?>" ><?Php echo $row_rs_departamentos['Dep_Des'];  ?></option>
            <?Php //}
			 }while($row_rs_departamentos=$obBD_con1->fetch_assoc($rs_departamentos));  ?>
          </select>
<?Php
 	$obBD_con1->free_result($rs_departamentos);
	exit();
}//fin de if(isset($dep_ajx)){

/*Cargar datos con AJAX */ 
if(isset($car_ajx)){
 /*Consulta de cargos*/
$rs_cargos=$obBD_con1->consulta(sentencias_rhu(650, $obBD_con1->parametros($Dep_Cod)), $obBD_conexion->conexion);
$row_rs_cargos= $obBD_con1->registros();
$total_rs_cargos = $obBD_con1->numregistros();  
?>
        <select name="Tic_Cod" style="text-transform:uppercase" id="Tic_Cod" >
            <option></option>
            <?Php do { //if ($row_rs_etapas['Eta_Cod']==1){ ?>
            <option value="<?Php echo $row_rs_cargos['Tic_Cod']?>" ><?Php echo $row_rs_cargos['Tic_Des'];  ?></option>
            <?Php //}
			 }while($row_rs_cargos=$obBD_con1->fetch_assoc($rs_cargos));?>
          </select>
<?Php
 	$obBD_con1->free_result($rs_cargos);
	exit();
}// fin de if(isset($car_ajx)){

 // Busqueda General de Datos
	if ($txt_busqueda != "" )
	{
		if ($op_opciones == "d")
			{
				/*BUSCA EL PERSONAL QUE EMPIEZA CON EL APELLIDO*/			
				  $rs_buscar = $obBD_con1->consulta(sentencias_rhu(654, $obBD_con1->parametros(trim($txt_busqueda))), $obBD_conexion->conexion);
 			}
		if ($op_opciones == "r")
			{
				/*BUSCA EL PERSONAL QUE EMPIEZA CON EL CODIGO*/
				$rs_buscar = $obBD_con1->consulta(sentencias_rhu(655, $obBD_con1->parametros(trim($txt_busqueda))), $obBD_conexion->conexion);
			}
	  $row_rs_buscar = $obBD_con1->registros();
      $total_rs_buscar = $obBD_con1->numregistros();
	}//fin de if ($txt_busqueda != "")
	
	if ($codigo>0)
		{
		  /*Consulta car gar los ajax con lod datos guardados*/
		    $rs_modificar = $obBD_con1->consulta(sentencias_rhu(658, $obBD_con1->parametros($Tic_Cod)), $obBD_conexion->conexion);			
			$row_rs_modificar  = $obBD_con1->registros();
   			$total_rs_modificar  = $obBD_con1->numregistros();
			/***Consulta de depratmento**/
			$rs_depart = $obBD_con1->consulta(sentencias_rhu(659, ''), $obBD_conexion->conexion);			
			$row_rs_depart = $obBD_con1->registros();
   			$total_rs_depart = $obBD_con1->numregistros();
			/**Consulta  de cargos**/
			$rs_tipcargo = $obBD_con1->consulta(sentencias_rhu(660, ''), $obBD_conexion->conexion);			
			$row_rs_tipcargo = $obBD_con1->registros();
   			$total_rs_tipcargo = $obBD_con1->numregistros();
		   /*Cargo los datos del Personal*/
			$rs_consulta = $obBD_con1->consulta(sentencias_rhu(645, $obBD_con1->parametros($codigo.'*'.$Ses_Emp_Cod)), $obBD_conexion->conexion);			
			$row_rs_consulta  = $obBD_con1->registros();
   			$total_rs_consulta  = $obBD_con1->numregistros();
		   /*Crgo los datos del contrato*/
			$rs_contrato = $obBD_con1->consulta(sentencias_rhu(656, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);			
			$row_rs_contrato  = $obBD_con1->registros();
   			$total_contrato  = $obBD_con1->numregistros();
		    /*Muestro los cargos que tiene un empleado de la UTSAM*/
			$rs_consul_cargo = $obBD_con1->consulta(sentencias_rhu(644, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);			
			$row_rs_consul_cargo  = $obBD_con1->registros();
   			$total_rs_consul_cargo  = $obBD_con1->numregistros();
			/*Consulta de las areas de departamento*/
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
		
if(isset($hdd_save) && !isset($volver_op)){
	if ($thisPost->postBlock($_POST['postID'])) 
	{	
		/* Cracion del objeto mysql para las inserciones */		
		$obBD_ins1 =  new Class_Log_Datos_Con;
		/*Inicio de la transaccion*/
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		/* Actualizar de distributivo */
		$obBD_ins1->grabarv_registros(sentencias_rhu(661, $obBD_ins1->parametros($Tic_Cod.'*'.$Ded_Cod.'*'.$Reb_Cod.'*'.$Con_Ini.'*'.$Con_Fin.'*'.$Con_Cod)),$obBD_conexion->conexion);	
		/* Actualizar en tabla sueldo */		
		$obBD_ins1->grabarv_registros(sentencias_rhu(662, $obBD_ins1->parametros($Sue_Val.'*'.$Con_Cod)),$obBD_conexion->conexion);	
		/*fin de la conexion */
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
	}//fin del if(isset($hdd_save) && !isset($volver_op)){
}// fin del if ($thisPost->postBlock($_POST['postID'])) 
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
<body> 
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">Modificacion de contratos del personal </td>
	</tr>
	<tr>	  	
        <td height="389" valign="top">
         <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" id="form1">
    <?php require('../../componentes/FRONT/com_con_persona.php');?>
   
	</form>
    <?Php
  	if(isset($txt_busqueda)){ ?>
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
      <thead>
      <tr>
        <th width="12%">Cód. Int.</th>
		<th width="14%">C&eacute;dula/R.U.C.</th>
        <th width="35%">Personal</th>
        <th width="17%">Cargo</th>
        <th width="14%">Sueldo</th>
        <th width="8%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	 <?php if ($total_rs_buscar > 0){?>
      <?Php do {?> 
    
      <tr> 
	    <td align="center" ><?Php echo $row_rs_buscar['Con_Cod']; ?></td>
	    <td align="center" ><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
        <td align="left" ><?Php  echo marcar_cadena($txt_busqueda, $row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom'], '#FFFF00', 1); ?>          </td>
        <td align="left" ><?Php echo $row_rs_buscar['Tic_Des']; ?></td>
        <td align="center" ><?Php echo "$ ".number_format($row_rs_buscar['Sue_Va1'],2); ?></td>
		<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
		  <td align="center">
		    <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()"><i class=" icon-arrow-right icon-white"></i>
		      </button> 
		    <input type="hidden" name="cod_bus" id="cod_bus" value="<?php echo $txt_busqueda;?>"/>
		    <input type="hidden" name="cod_op" id="cod_op" value="<?php echo $op_opciones;?>"/>	
		    <input type="hidden" name="codigo" id="codigo" value="<?php echo $row_rs_buscar['Per_Cod']; ?>" /> 
		    <input type="hidden" name="modificar" id="modificar" value="1"/> 
		    <input type="hidden" name="Con_Cod" id="Con_Cod" value="<?php echo $row_rs_buscar['Con_Cod']; ?>" /> 
		    <input type="hidden" name="Tic_Cod" id="Tic_Cod" value="<?php echo $row_rs_buscar['Tic_Cod']; ?>" /> 
		    </td>
		  </form> 
      </tr>	        
      <?Php } while ($row_rs_buscar = $obBD_con1->registros($rs_buscar)); 
	} else
	{?>
     <tr class="Fondo">
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td align="center"><?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($txt_busqueda)." ".$codigo, 1);?></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
     </tr>
    <? }// fin del else?> 
    </tbody>
    </table>
    </FIELDSET>
    <?Php 
	  echo barra_estado($total_rs_buscar);
  }// fin del   	if(isset($txt_busqueda))
  ?>  
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name="form3" id="form3">
<?php  //Creacion del campo REPOST
$thisPost->startPost(); ?>
<?Php if ($total_rs_consulta > 0) { ?>		  
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Personal</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
     <tr bordercolor="#FFFFFF">
        <td width="13%" class="Etiqueta1">C&eacute;dula/R.U.C.: </td>
        <td width="87%" class="LetraNegra"> <?Php echo $row_rs_consulta['Prs_Ced']; ?>
          <input type="hidden" name="codigo2" value="<?php echo $codigo; ?>" /></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Personal: </td>
        <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Ape']." ".$row_rs_consulta['Prs_Nom']; ?></td>
      </tr>       
  </table> 
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Laborales</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
     <tr>
        <td width="13%" class="Etiqueta1"><span class="Asterisco">* </span> Area:&nbsp; </td>
        <td width="87%" class="LetraNegra">
		<select name="Are_Cod" id="Are_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?dep_ajx=1&Are_Cod=' + this.value,'departamento')">
          <option></option>
          <?php
			do {  
		?>
          <option <?php if($row_rs_modificar['Are_Cod']==$row_rs_areas['Are_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_areas['Are_Cod']?>"><?php echo $row_rs_areas['Are_Des']?></option>
          <?php
			} while ($row_rs_areas = $obBD_con1->fetch_assoc($rs_areas)); ?>
        </select></td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span> Departamento:&nbsp;</td>
        <td class="LetraNegra">
		<div id="departamento">		
		  <select name="Dep_Cod" id="Dep_Cod" >
          <option></option>
          <?php
			do {  
		?>
          <option <?php if($row_rs_modificar['Dep_Cod']==$row_rs_depart['Dep_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_depart['Dep_Cod']?>"><?php echo $row_rs_depart['Dep_Des']?></option>
          <?php
			} while ($row_rs_depart= $obBD_con1->fetch_assoc($rs_depart));?>
        </select>
        </div></td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span> Cargo:&nbsp; </td>
        <td class="LetraNegra">
		<div id="cargos">
          <select name="Tic_Cod" id="Tic_Cod" >
          <option></option>
          <?php
			do {  
		?>
          <option <?php if($row_rs_modificar['Tic_Cod']==$row_rs_tipcargo['Tic_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_tipcargo['Tic_Cod']?>"><?php echo $row_rs_tipcargo['Tic_Des']?></option>
          <?php
			} while ($row_rs_tipcargo= $obBD_con1->fetch_assoc($rs_tipcargo));?>
        </select>
        </div>
		</td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span> Dedicaci&oacute;n laboral:&nbsp; </td>
        <td class="LetraNegra">
		<select name="Ded_Cod" id="Ded_Cod">
          <option></option>
          <?php
			do {  
		?>
          <option  <?php if ($row_rs_contrato['Ded_Cod']==$row_rs_dedi['Ded_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_dedi['Ded_Cod']?>"><?php echo $row_rs_dedi['Ded_Des'].' ('.$row_rs_dedi['Ded_Hrs']."Hrs)"; ?></option>
          <?php
			} while ($row_rs_dedi = $obBD_con1->fetch_assoc($rs_dedi));	?>
        </select></td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span> Relaci&oacute;n laboral:&nbsp; </td>
        <td class="LetraNegra"><select name="Reb_Cod" id="Reb_Cod">
          <option></option>
          <?php
			do {  
		?>
          <option <?php if ($row_rs_contrato['Reb_Cod']==$row_rs_rela['Reb_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_rela['Reb_Cod']?>"><?php echo $row_rs_rela['Reb_Des']?></option>
          <?php
			} while ($row_rs_rela = $obBD_con1->fetch_assoc($rs_rela)); ?>
        </select></td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span> Fecha de inicio laboral:&nbsp; </td>
        <td class="LetraNegra">
		<input name="Con_Ini" type="text" id="Con_Ini" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);" value="<?php echo $row_rs_contrato['Con_Ini'] ?>">
		</td>
      </tr>
      <tr>
        <td height="25" class="Etiqueta1">Fecha de fin laboral:&nbsp; </td>
        <td class="LetraNegra">
		<input name="Con_Fin" type="text" id="Con_Fin" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);" value="<?php 
		echo $row_rs_contrato['Con_Fin'];?>">			
		</td>
      </tr>
      <tr>
       <td class="Etiqueta1"><span class="Asterisco">* </span> Sueldo:&nbsp;</td>
       <td class="LetraNegra">
	   <input name="Sue_Val" type="text" id="Sue_Val" size="8" maxlength="8" value="<?php echo round($row_rs_contrato['Sue_Va1'],2)?>" style="text-align:right">
       </td>
      </tr>       
  </table> 
  <br/>
</FIELDSET>
<br/>
<table border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td>
	 <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*volver_op"; ?>', '<?Php echo $cod_bus.'*'.$cod_op.'*1'; ?>');" /><i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
       <input type="hidden" name="hdd_volver" id="hdd_volver" value="2">	  
	   <input type="hidden" name="Con_Cod" id="_Cod" value="<?php echo $Con_Cod; ?>">  </td>	
	 <td>
	 <button type="button" class="btn btn-primary start" title="Guardar" name="btn_guardar" onClick="validar_contratos(this.form)"/> <i class="icon-book icon-white"></i><span>Guardar</span> </button>
	   <input type="hidden" name="hdd_save" id="hdd_save" value="1">	   
	   </td>
   </tr>
 </table>
 <br />
 <?Php } // fin de if ($total_rs_consulta > 0) ?>		
</form></td>
  </tr>
</table>
</body>
</html>
<?Php
/* libero los cursores de la base de datos */
@$obBD_con1->free_result($rs_buscar);
@$obBD_con1->free_result($rs_consulta);
@$obBD_con1->free_result($rs_modificar);
@$obBD_con1->free_result($rs_depart);
@$obBD_con1->free_result($rs_tipcargo);
@$obBD_con1->free_result($rs_consul_cargo);
@$obBD_con1->free_result($rs_consulta);
@$obBD_con1->free_result($rs_rela);
@$obBD_con1->free_result($rs_dedi);
@$obBD_con1->free_result($rs_contrato);
/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>