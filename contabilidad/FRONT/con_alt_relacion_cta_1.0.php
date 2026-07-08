<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* Descripción: Permite registrar la relacion entre los productos y el plan de cuentas
* Fecha de actualización:	2009-12-11
* Desarrollador:	Lewis Chimarro 
* Fecha de actualización:	2012-06-15
* Desarrollador:	Lewis Chimarro 
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_relacion_cta.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

/** 
* Creación del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;
/** 
* Creacion del Objeto de conexion 
*/  
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creación del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Con; 

$hoy = date("Y-m-d");
$mes = date("m");

/** 
* Carga el periodos contable actual 
*/
$rs_periodos = $obBD_con1->consulta(sentencias_con(214, $obBD_con1->parametros($Ses_Emp_Cod)), $obBD_conexion->conexion);
$row_rs_periodos = $obBD_con1->registros();
$total_rs_periodos = $obBD_con1->numregistros();

if(isset($ajax_plan_cta))
{		
	$parametro = "AND det_plan.Pld_Tip = 'D'";
	
	if ($tipo == 'd')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$rs_buscar = $obBD_con1->consulta(sentencias_con(312,$obBD_con1->parametros(trim($txt_b).'*'.$Ses_Emp_Cod.'*'.$parametro.'*'.$Pla_Cod)), $obBD_conexion->conexion);
		$row_rs_buscar = $obBD_con1->registros();
		$total_rs_buscar = $obBD_con1->numregistros();
	}
	else
	{
		/**
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$rs_buscar = $obBD_con1->consulta(sentencias_con(313,$obBD_con1->parametros(trim($txt_b).'*'.$Ses_Emp_Cod.'*'.$parametro.'*'.$Pla_Cod)), $obBD_conexion->conexion);
		$row_rs_buscar = $obBD_con1->registros();
		$total_rs_buscar = $obBD_con1->numregistros();		
	}
?>
	<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
			<table width="100%" border="1" cellpadding="0" cellspacing="0">
			  <tr class="Cabecera1">
				<td width="10%">C&oacute;digo</td>
				  <td>Descripci&oacute;n</td>
				  <td>Grupo</td>				  
				  <td>Tipo</td>				  
				  <td width="7%">Estado</td>
				  <td width="7%">&nbsp;</td>
			  </tr>
			  <?Php 
			  if($total_rs_buscar != 0)		
				{			  
				  do { 
					/**
					* Consulta del detallete de la CUENTA 
					*/
					$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_buscar['Pld_Rec'])), $obBD_conexion->conexion);
					$row_rs_recur = $obBD_con1->registros();					  				  
				  ?>
				  <tr <?php echo focus_row("resaltar_text","resaltar_back","undo_resaltar_text","Fondo");?> class="Fondo">
					<td><?php echo $row_rs_buscar['Pld_Cdc']; ?></td>
				  <td><?php echo $row_rs_buscar['Pld_Des']; ?></td>
				  <td align="center"><?php if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']; }else{ echo "&nbsp;"; } ?></td>
				  <td align="center"><?php echo $row_rs_buscar['Pld_Tip']; ?></td>				  
				  <td align="center"><?php echo $row_rs_buscar['Pld_Est']; ?></td>
				  <td align="center">
				  <?Php if ($row_rs_buscar['Pld_Est'] == 'Activa'){?>
                  <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="document.getElementById('Cta_Des').value='<?php echo $row_rs_buscar['Pld_Des'];?>'; document.getElementById('Pld_Cod').value='<?php echo $row_rs_buscar['Pld_Cod'];?>';closeModal();">
        	<i class=" icon-arrow-right icon-white"></i>
		        </button>
				  <?php }else{ echo "&nbsp;"; } ?>				
				  </td>
				  </tr>
				  <?Php } while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar));
				}//Fin del if($total_rs_buscar != 0)	
				else
				{
				  ?>	
				  <tr>
					<td colspan="6"><?php echo error_alerta("¡No hay resultados que mostrar!", 2)?><td>
				  </tr>
	  			<?Php
				} //Fin del else if($total_rs_buscar != 0)	?>				
		  </table>
          	<?Php echo barra_estado($total_rs_buscar); ?>
		</FIELDSET>		
<?php
	@$obBD_con1->free_result($rs_recur);
	@$obBD_con1->free_result($rs_buscar);
	exit();
}

if(isset($ajax_verRelaciones))
{			
		if($Car_Int==""){ $Car_Int=0;}
		if($Mod_Cod==""){ $Mod_Cod=0;}
		
		/**
		* Elimina las relaciones entre producto - plan de cuentas
		*/
		$obBD_ins1 =  new Class_Log_Datos_Con;
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		$obBD_con1->grabarv_registros(sentencias_con(518,$obBD_con1->parametros($Pro_Cod.'*'.$Pld_Cod.'*'.$Car_Int.'*'.$Mod_Cod)),$obBD_conexion->conexion);
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);		
		/**
		* Carga el periodos contable actual 
		*/
		$rs_relacion = $obBD_con1->consulta(sentencias_con(515,$obBD_con1->parametros($Pro_Cod)), $obBD_conexion->conexion);
		$row_rs_relacion = $obBD_con1->registros();
		$total_rs_relacion = $obBD_con1->numregistros();
		?>
		<div id="div_relacion">
<table width="100%" height="0%" border="1" cellpadding="0" cellspacing="0">
          <tr class="Cabecera1">
            <td width="12%" height="30%">C&oacute;d. Int </td>
            <td width="12%">C&oacute;digo</td>
            <td width="38%">Cuenta contable</td>
            <?Php
			if ($tot_car >0)
			{
			?>
            <th id="id_mod" width="10%"><strong>Modalidad</strong></th>
            <th id="id_car" width="15%"><strong>Carrera</strong></th>
            <?Php
			}
			?>
            <td width="14%">&nbsp;</td>
          </tr>
          <?php
		    if($total_rs_relacion!=0){
			do
			{
				/**
				* Busca la carrera 
				*/
				$rs_carrera = $obBD_con1->consulta(sentencias_con(517,$obBD_con1->parametros($row_rs_relacion['Car_Int'])), $obBD_conexion->conexion);
				$row_rs_carrera = $obBD_con1->registros();
				$tot_rs_carrera = $obBD_con1->numregistros();				
				/** 
				* Busca la modalidad 
				*/
				$rs_modalidad = $obBD_con1->consulta(sentencias_con(516,$obBD_con1->parametros($row_rs_relacion['Mod_Cod'])), $obBD_conexion->conexion);
				$row_rs_modalidad = $obBD_con1->registros();
				$tot_rs_modalidad = $obBD_con1->numregistros();
		  ?>
			  <tr class="Fondo" <?php echo focus_row("resaltar_text","resaltar_back","undo_resaltar_text","Fondo");?>>
				<td height="34%" align="center"><?php echo $row_rs_relacion['Pro_Cod']?></td>
				<td align="center"><?php echo $row_rs_relacion['Pld_Cod']?></td>
				<td align="left">&nbsp;<?php echo $row_rs_relacion['Pld_Des']?></td>
	            <?Php
				if ($tot_car >0)
				{
				?>
				<td align="center"><?php  echo $row_rs_modalidad['Mod_Des'];?></td>
				<td align="center">&nbsp;<?php echo $row_rs_carrera['Car_Nom'];?></td>
                <?php
				} ?>
			    <td align="center">
                                <button type="button" class="btn btn-danger delete" title="Eliminar Relación" onClick="if(confirmacion3(this.form)){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_verRelaciones=1&Pro_Cod=<?php echo $Pro_Cod?>&Pld_Cod=<?php echo $row_rs_relacion['Pld_Cod'];?>&tot_car=<?php echo $tot_car; ?>&Car_Int=<?php echo $row_rs_carrera['Car_Int'];?>&Mod_Cod=<?php echo $row_rs_modalidad['Mod_Cod'];?>','div_relacion')}">
                    <i class="icon-trash icon-white"></i>
                    <span>Eliminar</span>
                </button>
				</td>
			  </tr>
			  <?Php } while ($row_rs_relacion = $obBD_con1->fetch_assoc($rs_relacion));
			  }else{
			  ?>
          <tr>
            <td height="36%">&nbsp;</td>
            <td height="36%">&nbsp;</td>
            <td height="36%"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1); ?></td>            
         <?Php
			if ($tot_car >0)
			{
			?>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <?Php
			}
			?>
            <td height="36%">&nbsp;</td>
          </tr>
		  <?php }?>
        </table>
		</div>
<?php
$obBD_con1->free_result($rs_relacion);
$obBD_con1->free_result($rs_carrera);
$obBD_con1->free_result($rs_modalidad);
exit();
}

/**
* Grabado de las relacion entre producto - plan de cuentas
*/
if($thisPost->postBlock($_POST['postID'])) 
{				
	if(isset($hdd_save) && !isset($hdd_volver))
	{
			$obBD_ins1 =  new Class_Log_Datos_Con;
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			$obBD_con1->grabarv_registros(sentencias_con(514,$obBD_con1->parametros($Pro_Cod.'*'.$Pld_Cod.'*'.$Car_Int.'*'.$Mod_Cod)),$obBD_conexion->conexion);
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}

/**
* Inicializa la variable op cuando no esta seteada la misma
*/
if (!(isset($op)))
	$op = 1;

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once "../../mascaras/model1/estilos/estilos.php"; ?>								
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/con_val_relacion_cta.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript"> 
              $(function() {
                    $('#set1 *').tooltip({showURL: false});
              });              			
        </script>                    
		<link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">			
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td width="100%" height="8%">&raquo; Registrar Relaci&oacute;n producto - Plan de Cuentas <?Php echo $periodo;?></td>
  	</tr>
	<tr>
      <td height="400" align="left" valign="top">
	   <?php
		/*** opciones de las pesta?as del menu ***/
		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		tabs(2,'Individual*Ver', $pag1.'*'.$pag2, $op);
	
switch ($op)
{
	case 1: 
?>       
	  <?php 
	  if(!isset($hdd_aux))
	  {?>
	  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">	  
	 
      <FIELDSET>
	  <LEGEND>
			<label class="Titulos2">Selección Periodo Contable</label>
	  </LEGEND>				
	  <table width="30%" border="0" cellspacing="0" cellpadding="0">
	  	<tr>
			<td width="18%" class="Etiqueta1">Periodo:&nbsp; </td>
			<td width="42%">			  
			  <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodos['Pec_Fei']; ?>">
			  <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodos['Pec_Fef']; ?>">		
			  <select name="Pec_Cod" id="Pec_Cod" onChange="javascript: asignar_fechas(this.value)">
			  <?Php 
			  	$fecha=explode("-",$row_rs_periodos['Pec_Fei']); 
				$periodo="En el periodo ".$fecha[0];
				
				if ($total_rs_periodos > 0)
				{
				do{
				?>
				<option value="<?Php echo $row_rs_periodos['Pec_Cod'].'*'.$row_rs_periodos['Pec_Fei'].'*'.$row_rs_periodos['Pec_Fef'].'*'.$row_rs_periodos['Pla_Cod']; ?>"><?Php echo $row_rs_periodos['Periodo']; ?>
				</option>
				<?php
				}while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
				}//Fin del if ($total_rs_periodo > 0)
				else
				{ ?>
					<option value=""></option>
				<?Php
				}//Fin del else if ($total_rs_periodos > 0)
				?>	
			  </select>
		    </td>
			<td width="40%" align="center">
            <button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        </button>   			
			<input name="hdd_aux" type="hidden" id="hdd_aux" value="1">
			<input name="periodo" type="hidden" id="periodo" value="<?php echo $periodo;?>">			
			</td>
		  </tr>
		</table>
		</FIELDSET>			 
		</form>	
   <?php } ?>		
		<br>			
		<?php 
		if(isset($hdd_aux))
		{
			/*
			* Divide la cadena del periodo contable 
			*/
			$arreglo = explode("*",$Pec_Cod); 		
			?>
            
            		
            
            
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Buscar producto:</label>
		</LEGEND>				
	    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2">
	    <table width="624" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="112" height="44" class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Busqueda:</div></td>
		  <td width="512" class="BarraBusqueda">
		  <input type="text" name="txt_busqueda" id="txt_busqueda" size="50" maxlength="50">
		  
		  
			<input name="aux" type="hidden" value="1">
			<input name="periodo" type="hidden" id="periodo" value="<?php echo $periodo;?>">
			<input name="hdd_aux" type="hidden" id="hdd_aux" value="1">
			<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>"> 
			<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $arreglo[1]; ?>">
			<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $arreglo[2]; ?>">								
   			<input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $arreglo[3]; ?>">								
            <button type="button" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        	</button>   
		 
		  </td>
		</tr>
	    </table>	
	    </form>			  
		</FIELDSET>
		<?php 
		}
				
		if(isset($aux))
		{
			/**
			* Carga el periodos contable actual 
			*/
			$rs_rubros = $obBD_con1->consulta(sentencias_con(511,$obBD_con1->parametros(trim($txt_busqueda).'*'.$Ses_Emp_Cod)), $obBD_conexion->conexion);
			$row_rs_rubros = $obBD_con1->registros();
			$total_rs_rubros = $obBD_con1->numregistros();
		?>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Resultados de la Busqueda</label>
		</LEGEND>
		<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>
		  <tr>
			<th width="10%"><strong>C&oacute;d. Int.</strong></th>
			<th width="15%"><strong>Categoria</strong></th>
			<th width="15%"><strong>Descripci&oacute;n Corta</strong></th>
			<th width="46%"><strong>Descripci&oacute;n Larga</strong></th>
			<th width="11%"><strong>Marca</strong><strong></strong></th>
			<th width="3%">&nbsp;</th>
		 </tr>		 		 
         </thead>
         <tbody>
		 <?php 
		 if($total_rs_rubros!=0)
		 {
			 do{ ?>
			 <tr>
				<td align="center"><?php echo $row_rs_rubros['Pro_Cod']; ?></td>
				<td><?php echo $row_rs_rubros['Cat_Des']; ?>&nbsp;</td>				
				<td>&nbsp;<?php echo $row_rs_rubros['Ite_Cor']?></td>
				<td>&nbsp;<?Php  echo marcar_cadena($txt_busqueda, $row_rs_rubros['Ite_Lar'], '#FFFF00', 1); ?></td>
				<td><?php echo $row_rs_rubros['Mar_Des']; ?>&nbsp;</td>
				<form method="post" name="form2" action="<?php echo $_SERVER['PHP_SELF']?>">		  
				<td align="center">
				<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>				
				<input name="Ite_Cod" id="Ite_Cod" type="hidden" value="<?php echo $row_rs_rubros['Ite_Cod'];?>">
				<input name="Pro_Cod" id="Pro_Cod" type="hidden" value="<?php echo $row_rs_rubros['Pro_Cod'];?>">
				<input name="Cat_Des" id="Cat_Des" type="hidden" value="<?php echo $row_rs_rubros['Cat_Des'];?>">						
				<input name="Ite_Cor" id="Ite_Cor" type="hidden" value="<?php echo $row_rs_rubros['Ite_Cor'];?>">
				<input name="Ite_Lar" id="Ite_Lar" type="hidden" value="<?php echo $row_rs_rubros['Ite_Lar'];?>">
				<input name="Mar_Des" id="Mar_Des" type="hidden" value="<?php echo $row_rs_rubros['Mar_Des'];?>">
				<input name="txt_busqueda" id="txt_busqueda" type="hidden" value="<?php echo $txt_busqueda;?>">
				<input type="hidden" name="codigo" id="codigo" value="<?php echo $arreglo[0];?>">
				<input type="hidden" name="hdd_aux1" id="hdd_aux1" value="1">
				<input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
				<input name="periodo" type="hidden" id="periodo" value="<?php echo $periodo;?>">
  				<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod;?>">
				</td>
				</form>		 
			 </tr>
			 <?Php } while ($row_rs_rubros = $obBD_con1->fetch_assoc($rs_rubros));
			}else{?>
			<tr >
		 		<td>&nbsp;</td>
		 		<td>&nbsp;</td>
		 		<td>&nbsp;</td>
		 		<td><?php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
		 		<td>&nbsp;</td>
		 	</tr>
			<?php }?>
            </tbody>
		</table>            
			<?php echo barra_estado($total_rs_rubros); 
		 ?>
        </FIELDSET>
<?php
	}//Fin del if(isset($aux))
	
	if($hdd_aux1==1 && !isset($hdd_volver))
	{?>	
	<form method="post" name="form4" action="<?php echo $_SERVER['PHP_SELF']?>">    	
	<table width="882">
	<tr>
       <td ><?Php echo mensaje_requerido(); ?></td>
      </tr>
	</table>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos a registrar </label>
	</LEGEND>
	<table width="100%" height="0%" border="0" cellpadding="0" cellspacing="0">
      
      <tr>
        <td width="17%" class="Etiqueta1">C&oacute;d. Int.:</td>
        <td width="83%" class="LetraNegra">&nbsp;<?php echo $Ite_Cod;?>
          <input type="hidden" name="Pro_Cod" id="Pro_Cod" value="<?php echo $Pro_Cod;?>"></td>
      </tr>
      <tr>
        <td class="Etiqueta1">C&oacute;d. categoria:</td>
        <td class="LetraNegra">&nbsp;<?php echo $Cat_Des;?></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Descripcion corta:</td>
        <td class="LetraNegra">&nbsp;<?php echo $Ite_Cor;?></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Descripcion larga:</td>
        <td class="LetraNegra">&nbsp;<?php echo $Ite_Lar;?></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Marca:</td>
        <td class="LetraNegra">&nbsp;<?php echo $Mar_Des;?></td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Cuenta contable:</td>
        <td valign="top">
        <table width="49%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="58%"><?Php	/* Creacion del campo repost */
				$thisPost->startPost();	?>
              <input readonly="readonly" border="0" name="Cta_Des" type="text" id="Cta_Des"  size="45"/>
              <input type="hidden" border="0" name="Pld_Cod"  id="Pld_Cod"  size="15" maxlength="30" /></td>
            <td width="42%"><button type="button" class="btn btn-success btn-mini" title="Buscar" id="button"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="20" class="Etiqueta1">&nbsp;</td>
        <td valign="top">&nbsp;</td>
      </tr>
      <tr>
        <td height="80" class="Etiqueta1">&nbsp;</td>
        <td valign="top">
          <?php
			/**
			* Carga el periodos contable actual 
			*/
			$rs_relacion = $obBD_con1->consulta(sentencias_con(515,$obBD_con1->parametros($Pro_Cod)), $obBD_conexion->conexion);
			$row_rs_relacion = $obBD_con1->registros();
			$total_rs_relacion = $obBD_con1->numregistros();
			/**
			* Carga el periodos contable actual 
			*/
			$rs_modalidad = $obBD_con1->consulta(sentencias_con(512,''), $obBD_conexion->conexion);
			$row_rs_modalidad = $obBD_con1->registros();
			$total_rs_modalidad = $obBD_con1->numregistros();
			/**
			* Carga el periodos contable actual 
			*/
			$rs_carrera = $obBD_con1->consulta(sentencias_con(513,$obBD_con1->parametros($Ses_Emp_Cod)), $obBD_conexion->conexion);
			$row_rs_carrera = $obBD_con1->registros();
			$total_rs_carrera = $obBD_con1->numregistros();
		?>        
          <div id="div_relacion">
            <table width="100%" height="0%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
              <thead>
                <tr>
                  <th width="11%"><strong>C&oacute;d. Int </strong></th>
                  <th width="12%"><strong>C&oacute;digo</strong></th>
                  <th width="38%"><strong>Cuenta contable</strong></th>
                  <?Php
			if ($total_rs_carrera >0)
			{
			?>
                  <th id="id_mod" width="10%"><strong>Modalidad</strong></th>
                  <th id="id_car" width="15%"><strong>Carrera</strong></th>
                  <?Php
			}
			?>
                  <th width="14%"><strong>&nbsp;</strong></th>
                  </tr>
                </thead>
              <tbody>
                <?php
		    if($total_rs_relacion!=0){
			do
			{
				/**
				* Busca la carrera 
				*/
				$rs_carrera_pro = $obBD_con1->consulta(sentencias_con(517,$obBD_con1->parametros($row_rs_relacion['Car_Int'])), $obBD_conexion->conexion);
				$row_rs_carrera_pro = $obBD_con1->registros();
				$tot_rs_carrera_pro = $obBD_con1->numregistros();				
				/** 
				* Busca la modalidad 
				*/
				$rs_modalidad_pro = $obBD_con1->consulta(sentencias_con(516,$obBD_con1->parametros($row_rs_relacion['Mod_Cod'])), $obBD_conexion->conexion);
				$row_rs_modalidad_pro = $obBD_con1->registros();
				$tot_rs_modalidad_pro = $obBD_con1->numregistros();
		  ?>
                <tr >
                  <td height="34%" align="center"><?php echo $row_rs_relacion['Pro_Cod']?></td>
                  <td align="center"><?php echo $row_rs_relacion['Pld_Cod']?></td>
                  <td align="left">&nbsp;<?php echo $row_rs_relacion['Pld_Des']?></td>
                  <?Php
				if ($total_rs_carrera >0)
				{
				?>
                  <td align="center"><?php  echo $row_rs_modalidad_pro['Mod_Des'];?></td>
                  <td align="center"><?php echo $row_rs_carrera_pro['Car_Nom'];?></td>
                  <?php
				} ?>
                  <td align="center">
                    <button type="button" class="btn btn-danger btn-mini" title="Eliminar Relación" onClick="if(confirmacion3(this.form)){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_verRelaciones=1&Pro_Cod=<?php echo $Pro_Cod?>&Pld_Cod=<?php echo $row_rs_relacion['Pld_Cod'];?>&Car_Int=<?php echo $row_rs_carrera_pro['Car_Int'];?>&tot_car=<?php echo $total_rs_carrera; ?>&Mod_Cod=<?php echo $row_rs_modalidad_pro['Mod_Cod'];?>','div_relacion')}">
                      <i class="icon-trash icon-white"></i>
                      <span>Eliminar</span>
                      </button>              
                    </td>
                  </tr>
                <?Php } while ($row_rs_relacion = $obBD_con1->fetch_assoc($rs_relacion));
			  }else{
			  ?>
                <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td><?Php echo error_alerta("¡No hay resultados que mostrar!", 1); ?></td>
                  <?Php
			if ($total_rs_carrera >0)
			{
			?>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <?Php
			}
			?>
                  <td>&nbsp;</td>
                  <?php }?>
                  </tr>		  
                </tbody>
              </table>
            </div>
          </td>
      </tr>
      <tr>
        <td class="Etiqueta1">
          <?php if($total_rs_carrera!=0)
		{?> 
          <span class="Asterisco">*</span> Modalidad:
          <?php
		}
		?>
          </td>
        <td>
          <?php if($total_rs_carrera!=0)
		{?> 
          <select name="Mod_Cod" id="Mod_Cod">         
            <option value="">Seleccione...</option>
            <option value="Null">[Ninguno]</option>
            <?php  
				do {  			
			  ?>
            <option value="<?Php echo $row_rs_modalidad['Mod_Cod']; ?>"> <?php echo $row_rs_modalidad['Mod_Des']; ?> </option>
            <?php } while ($row_rs_modalidad = $obBD_con1->fetch_assoc($rs_modalidad)); ?>
            </select>		
          <?php }
	  	else
		{?>
          <input type="text" id="Mod_Cod" name="Mod_Cod" value="0" style="visibility:hidden" />        
          <?Php
		}
		?>        
          </td>
      </tr>
	  <tr>
        <td class="Etiqueta1">
        <?php if($total_rs_carrera!=0)
		{?> 
        <span class="Asterisco">*</span> Carrera:
        <?php
		}
		?>
        </td>        
        <td>		
        <?php if($total_rs_carrera!=0)
		{?> 
		<select name="Car_Int" id="Car_Int">
		 		 	
			  <option value="">Seleccione...</option>
			  <option value="Null">&raquo; [Ninguno]</option>
			  <?php  
				do {  			
			  ?>
			  <option value="<?Php echo $row_rs_carrera['Car_Int']; ?>"> <?php echo "&raquo; ".$row_rs_carrera['Car_Nom']."[ ".$row_rs_carrera['Eta_Des']." ]"; ?> </option>
			  <?php } while ($row_rs_carrera = $obBD_con1->fetch_assoc($rs_carrera)); ?>	
        </select>
      <?php }
	  	else
		{?>
        <input type="text" id="Car_Int" name="Car_Int" value="0" style="visibility:hidden" />        
        <?Php
		}
		?>		
        	</td>
      </tr>
    </table>
	</FIELDSET>
	
	<table width="267" height="73" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="111">   		    
        <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_volver(this.form,'<?Php echo "txt_busqueda*aux*hdd_aux*hdd_volver"; ?>','<?Php echo $txt_busqueda.'*'.'1'.'*'.'1'.'1'; ?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button> 
  		</td>
        <td width="156" height="73">
        <button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_requeridos(this.form, 'Cta_Des*Mod_Cod*Car_Int',1)">
	          <i class="icon-book icon-white"></i>
	          <span>Guardar</span>
	          </button>
            <input name="hdd_save" id="hdd_save" type="hidden" value="1"/>
			<input name="periodo" type="hidden" id="periodo" value="<?php echo $periodo;?>">
			<input type="hidden" name="Pec_Cod" id="Pec_Cod" value="<?php echo $Pec_Cod;?>">
			<input type="hidden" name="hdd_aux" id="hdd_aux" value="1">			
            
			<input name="Ite_Cod" id="Ite_Cod" type="hidden" value="<?php echo $Ite_Cod; ?>">
				<input name="Pro_Cod" id="Pro_Cod" type="hidden" value="<?php echo $Pro_Cod; ?>">
				<input name="Cat_Des" id="Cat_Des" type="hidden" value="<?php echo $Cat_Des; ?>">						
				<input name="Ite_Cor" id="Ite_Cor" type="hidden" value="<?php echo $Ite_Cor; ?>">
				<input name="Ite_Lar" id="Ite_Lar" type="hidden" value="<?php echo $Ite_Lar; ?>">
				<input name="Mar_Des" id="Mar_Des" type="hidden" value="<?php echo $Mar_Des; ?>">
				<input name="txt_busqueda" id="txt_busqueda" type="hidden" value="<?php echo $txt_busqueda;?>">
				<input type="hidden" name="Pec_Cod" id="Pec_Cod" value="<?php echo $Pec_Cod;?>">
                <input type="hidden" name="hdd_aux1" id="hdd_aux1" value="1">
        </td>
      </tr>
    </table>
	</form>
	<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
<div id="bgmodal"  class="bgmodal"   style="display:none">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
  <td>  
  <FIELDSET>
  <LEGEND>
  <label class="Titulos2">Buscar por :</label>
  </LEGEND>
  <table  border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td><?PHP mensaje_requerido(); ?>&nbsp;</td>
    </tr>
  </table>
  <table width="481" border="0">
    <tr>
      <td width="205">
      	<input name="op_opciones" type="radio" value="d" onClick="setfocus(document.getElementById('txt_busqueda2')); document.getElementById('opcion').value=this.value" checked>
        <span class="LetraNegra">Descripci&oacute;n</span>
      </td>
      <td width="266">
      <input type="radio" name="op_opciones" id="op_opciones" value="c" onClick="setfocus(document.getElementById('txt_busqueda2')); document.getElementById('opcion').value=this.value">
      <input type="hidden" id="opcion" name="opcion" value="d">
        <span class="LetraNegra">Cuenta</span>
        </td>
      </tr>
  </table>
  <table width="540" height="36" border="0" cellspacing="0" lass="">
    <tr>
      <td width="78" height="28" class="BarraBusqueda"><span class="Asterisco">* </span>Cuenta:</td>
      <td width="343" class="BarraBusqueda">
	  <input name="txt_busqueda2" type="text" id="txt_busqueda2" value="" size="50" maxlength="50" style="text-transform:uppercase "></td>
      <td width="113"><div align="center">      
      <button type="button" class="btn btn-success btn-mini" title="Buscar" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_1=1&txt_b='+ document.getElementById('txt_busqueda2').value+'&Pec_Cod=<?php echo $Pec_Cod;?>&Pla_Cod=<?Php echo $arreglo[3]; ?>&ajax_plan_cta=1&tipo='+document.getElementById('opcion').value ,'busqueda_item')">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        </button> 
      </div></td>
    </tr>
</table>
  </fieldset>
  <br>
   <div id="busqueda_item"></div><br>
</td>
  </tr>
</table>
</div>
	<?php }?>
<?php break; //fin del case 1
 
   case 2: 
   ?>
   <form method="post" name="form5" action="<?php echo $_SERVER['PHP_SELF']?>">
   <FIELDSET>
		<LEGEND>
		<label class="Titulos2">Productos Relacionados</label>
		</LEGEND>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Opciones</label>
		</LEGEND>
        <table width="48%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="9%" class="Etiqueta1" ><input type="radio" name="opt_1" id="opt_1" onclick="this.form.submit();" value="S" <?php if($opt_1=='S'){ echo 'checked';}?>></td>
            <td width="17%" class="Etiqueta1"><div  align="left">&nbsp;Relacionados</div></td>
            <td width="4%" class="Etiqueta1"><input type="radio" name="opt_1" id="opt_1" onclick="this.form.submit();" value="N" <?php if($opt_1=='N'){ echo 'checked';}?>></td>
            <td width="70%" class="Etiqueta1"><div  align="left">&nbsp;No Relacionados</div></td>
          </tr>
        </table>
		<input type="hidden" id="op" name="op" value="<?php echo $op;?>" />
        </FIELDSET>
        <br />
        <?php if (isset($opt_1)){?> 
        <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader02">
        <thead>
		  <tr>
			<th width="7%"><strong>C&oacute;d. Int.</strong></th>
			<th width="13%"><strong>Sustento</strong></th>
			<th width="37%"><strong>Producto</strong></th>
			<th width="10%"><strong>Cod. Cta.</strong></th>
			<th width="33%"><strong>Cuenta Contable</strong></th>						
		 </tr>		 		 
         </thead>
         <tbody>
		 <?php 
		 if($opt_1=='N')
		 {
			$rs_relacionProducto = $obBD_con1->getArrayConsulta(520,$Ses_Emp_Cod,$obBD_conexion); 
		 }else{
		 	$rs_relacionProducto = $obBD_con1->getArrayConsulta(519,$Ses_Emp_Cod,$obBD_conexion);
		 }
		 $total_relacionProducto=count($rs_relacionProducto);		 		 
		 if( $total_relacionProducto!=0)
		 {
			 foreach($rs_relacionProducto as $datos){ ?>
			 <tr>
				<td align="center"><?php echo $datos['Pro_Cod']; ?></td>
				<td>&nbsp;<?php echo  $datos['Adq_Des'];?></td>
				<td><?php echo $datos['Ite_Lar']." ".$datos['Pro_Obs']; ?>&nbsp;</td>				
				<td>&nbsp;<?php echo $datos['Pld_Cdc']?></td>
				<td>&nbsp;<?Php  echo $datos['Pld_Des']; ?></td>							 
			 </tr>
			 <?Php };
		}else{?>
			<tr >
		 		<td>&nbsp;</td>
		 		<td>&nbsp;</td>
		 		<td><?php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
		 		<td>&nbsp;</td>
		 		<td>&nbsp;</td>		 		
		 	</tr>
			<?php }?>
            </tbody>
		</table>            		
        	<?php echo barra_estado($total_relacionProducto); 
		}
		 ?>
        </FIELDSET>
        </form>
   <?php
   break; //fin del case 1 	
}
?>    
	</td>
  </tr>
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/con_par_relacion_cta.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>    
</BODY>
</HTML>
<?php
@$obBD_con1->free_result($rs_periodos);
@$obBD_con1->free_result($rs_rubros);
@$obBD_con1->free_result($rs_relacion);
@$obBD_con1->free_result($rs_modalidad);
@$obBD_con1->free_result($rs_carrera);
@$obBD_con1->free_result($rs_carrera_pro);
@$obBD_con1->free_result($rs_modalidad_pro);
/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>