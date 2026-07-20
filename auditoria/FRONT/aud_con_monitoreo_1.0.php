<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
/**
 * Permite visualizar las actividades de los usuarios en el sistema
 *
 * @author
 * @version 1.0
 * @Fecha de actualizaci�n:	05-03-2013
 *
 * @package auditoria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_monitoreo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 */
$obBD_conexion = new Class_Log_Conexion($Ses_Dat_Dis);

/**
 * objeto para consultas
 */
$obBD_con1 =  new Class_Log_Datos;

if(isset($_GET['ajax_cmb'])){
	$params = $obBD_conexion->conexion->real_escape_string($_GET['params']);
	$ajax_cmb = intval($_GET['ajax_cmb']);
	if($ajax_cmb == 0){
		$Arr_Resultado = $obBD_con1->getArrayConsulta(6, $params, $obBD_conexion);
	}else{
		$Arr_Resultado = $obBD_con1->getArrayConsulta(10, $params.'*'.$ajax_cmb, $obBD_conexion);
	}
	
	$str = '<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
	<thead><tr><th width="8%" >Fecha</th><th width="8%" >Hora</th><th>Actividad</th><th width="15%" >Opci&oacute;n</th>
	<th>Detalle de Opci&oacute;n</th><th width="3%" >&nbsp;</th></tr></thead><tbody>';
	$i = 0;
	foreach($Arr_Resultado as $row){
		$arr = explode(' ', $row['Log_Fec']);
		$i++;
		$str .= '<tr><td align="center">'.$arr[0].'</td>
				 <td align="center">'.$arr[1].'</td>
				 <td align="left">'.str_replace("{0}", strlen($row['Tab_Ali'])>0?mb_convert_encoding($row['Tab_Ali'], 'UTF-8', 'ISO-8859-1'):$row['Tab_Nom'], mb_convert_encoding($row['Eve_Des'], 'UTF-8', 'ISO-8859-1')).'</td>
				 <td align="left">'.mb_convert_encoding($row['Pcs_Lin'], 'UTF-8', 'ISO-8859-1').'</td>
				 <td align="left">'.mb_convert_encoding($row['Pcs_Det'], 'UTF-8', 'ISO-8859-1').'</td>
				 <td align="center"><form action="'.$_SERVER['PHP_SELF'].'" method="post" name="frm'.$i.'" id="frm'.$i.'">
										<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="Muestra_Aparecer();visualizarCampos(this.form,\'mostrar\');"><i class="icon-arrow-right icon-white"></i></button>
										<input type="hidden" name="tabCodigo" value="'.$row['Tab_Cod'].'">
										<input type="hidden" name="logCampos" value="'.$row['Log_Cam'].'">
										<input type="hidden" name="logValores" value="'.mb_convert_encoding($row['Log_Val'], 'UTF-8', 'ISO-8859-1').'">
										<input type="hidden" name="ajax" value="1">
									</form></td></tr>';
	}
	if(count($Arr_Resultado) == 0){
		$str .= '<tr><td>&nbsp;</td><td>&nbsp;</td><td>'.error_alerta(mb_convert_encoding("�No hay resultados que mostrar!", 'UTF-8', 'ISO-8859-1'), 1).'</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
	}
	$str .= '</tbody></table>';
	
	echo $str;
	
$obBD_con1->liberar();
$obBD_conexion->cerrar();
exit();				    
}

if(isset($_POST['ajax'])){
	
$row_tabla = $obBD_con1->getRowConsulta(7, $_POST['tabCodigo'], $obBD_conexion);
$str = '<FIELDSET><LEGEND><label class="Titulos2">Datos De la Tabla:</label></LEGEND>';
$str .= '<table width="100%" cellpadding="0" cellspacing="0"><tr>
		<td width="10%" class="Etiqueta1">Tabla:</td>
		<td class="LetraNegra">&nbsp;'.(strlen($row_tabla['Tab_Nom'])>0?$row_tabla['Tab_Nom']:$row_tabla['Tab_Ali']).'</td>
		</tr><tr><td class="Etiqueta1">Descripci&oacute;n:</td>
				<td class="LetraNegra">&nbsp;'.$row_tabla['Tab_Des'].'</td></tr></table></FIELDSET>';

$Arr = explode(',', str_replace('(', '', str_replace(')', '', $_POST['logCampos'])));

if(substr_count($_POST['logCampos'], ',') != substr_count($_POST['logValores'], ',')){
	$count = strlen($_POST['logValores']);
	$str_ = '';
	$contador = 0;
	for($i = 0; $i < $count; $i++){
		$char = $_POST['logValores'][$i].'';
		if($char == '~'){
			if($contador == 0)
				$contador = 1;
			else
				$contador = 0;
			$char = '';
		}

		if($char == ',' && $contador == 1){
			$str_ .= '~';
		}else{
			$str_ .= $char;
		}
	}
	$Arr_val = explode(',', $str_);
}else{
	$Arr_val = explode(',', $_POST['logValores']);
}

$str .= '<FIELDSET><LEGEND><label class="Titulos2">Campos Afectados:</label></LEGEND>
		 <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
		 <thead><tr><th width="10%" >C&oacute;d. Int.</th>
		 <th >Campo</th><th>Valor</th></tr></thead><tbody>';

for($i = 0; $i < count($Arr); $i++){
	$row = $obBD_con1->getRowConsulta(8, $_POST['tabCodigo'].'*'.str_replace('`', '', $Arr[$i]), $obBD_conexion);
	if(strlen($row['Cam_Cod'])>0){
		$str .= '<tr><td align="center">'.$row['Cam_Cod'].'</td><td>'.(strlen($row['Cam_Ali'])>0?$row['Cam_Ali']:$Arr[$i]).'</td><td>'.str_replace('~',',',$Arr_val[$i]).'</td></tr>';
	}else{
		$str .= '<tr><td>'.$i.'</td><td>'.$Arr[$i].'</td><td>'.str_replace('~',',',$Arr_val[$i]).'</td></tr>';
	}
}
$str .= '<tbody></table></FIELDSET>';
echo $str.barra_estado(count($Arr));
$obBD_con1->liberar();
$obBD_conexion->cerrar();
exit();
}

?>
<html>
<head>
 <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
 <?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
 <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
 <script type="text/javascript">$(function() { $('#set1 *').tooltip({showURL: false}); });</script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
 <script>$(function() { var dates = $( "#from, #to" ).datepicker({
		defaultDate: "+1w",
		changeMonth: true,
		changeYear:true,
		numberOfMonths: 1,
		dateFormat: "yy-mm-dd",
		onSelect: function( selectedDate ) {
			var option = this.id == "from" ? "minDate" : "maxDate",
				instance = $( this ).data( "datepicker" ),
				date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
			dates.not( this ).datepicker( "option", option, date );
		}
	});
 });
function visualizarCampos(form, id){
	$.ajax({
		url : form.action,
		data : $(form).serialize(),
		type : 'POST',
		success:function(data){
			$('#' + id).html(data);
			$('.fixedHeader01').fixedHeaderTable({ height: '250', footer: false, cloneHeadToFoot: true, altClass: 'odd', themeClass: 'fancyTable', autoShow: false });    
			$('.fixedHeader01').fixedHeaderTable('show', 10);
		},
		error:function(jqXHR, status, error){
			alert("Error al obtener datos");
		}
	});
}
 </script>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<div id='set1'>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
  		<tr class="BarraTitulo">
    		<td height="10">&raquo; Monitorear actividades</td>
  		</tr>
  	</table>
  	<form name="Buscador" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
		<?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
		<input type="hidden" name="pag" value="1">
	</form>
	<?php 
	switch($pag){
		case 1:
			/**
			 * Resultado de la Busqueda
			 */
			$Arr_Busqueda = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == 'd'? 1: 2, $_SESSION['Ses_Suc_Cod'].'*'.$_POST['txt_busqueda'], $obBD_conexion);
			?>
			<FIELDSET>
			    <LEGEND>
			    	<label class="Titulos2">Resultados de la busqueda</label>
			    </LEGEND>
			    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
				    <thead>
					      <tr>
					        <th width="5%" >Cod. Int. </th>
					        <th width="10%" >C�dula</th>
					        <th >Apellidos y Nombres </th>
							<th width="3%" >&nbsp;</th>
					      </tr>
				     </thead> 
				     <tbody>
				     <?php foreach($Arr_Busqueda as $row){
				     	?>
				     	<tr>
				     		<td align="center"><?php echo $row['Usu_Cod'];?></td>
					        <td align="left"><?php echo $row['Prs_Ced']?></td>
					        <td align="left"><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'],'#FFFF00', 1);?></td>
							<td align="center">
								<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
									<button type='button' class='btn btn-success btn-mini' title="Elegir" onclick="this.form.submit();"><i class='icon-arrow-right icon-white'></i></button>
									<input type="hidden" name="pag" value="2">
									<input type="hidden" name="usuCodigo" value="<?php echo $row['Usu_Cod'];?>" >
								<input type="hidden" name="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8');?>">
									<input type="hidden" name="txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>">
							</form>
							</td>
						</tr>
				     	<?php
				     }
				     if(count($Arr_Busqueda) == 0){
							?>
							<tr>
						        <td>&nbsp;</td>
						        <td>&nbsp;</td>
						        <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1);?></td>
								<td>&nbsp;</td>
						    </tr>
							<?php
					 }?>
				     </tbody>
			     </table>
		     </FIELDSET>
			<?php
			echo barra_estado(count($Arr_Busqueda));
			break;
		case 2:
			$row_usuario = $obBD_con1->getRowConsulta(3, $_POST['usuCodigo'], $obBD_conexion);
			?>
			<FIELDSET>
			    <LEGEND>
			    	<label class="Titulos2">Datos del Usuario:</label>
			    </LEGEND>
			    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
			    <table width="100%" cellpadding="0" cellspacing="0">
			    	<tr>
			    		<td width="10%" class="Etiqueta1">Cedula:</td>
			    		<td class="LetraNegra">&nbsp;<?php echo $row_usuario['Prs_Ced'];?></td>
			    	</tr>
			    	<tr>
			    		<td class="Etiqueta1">Nombres:</td>
			    		<td class="LetraNegra">&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Ape'];?></td>
			    	</tr>
			    	<tr>
			    		<td width="10%" class="Etiqueta1"></td>
			    		<td class="LetraNegra">&nbsp;
				    		<table cellpadding="0" cellspacing="0">
				    			<tr>
				    				<td class="Etiqueta1">&nbsp;<span class="Asterisco">*</span>Desde:</td>
				    				<td class="LetraNegra">&nbsp;<input name="from" type="text" id="from" value="<?php echo strlen($_POST['from'])>0?$_POST['from']:date('Y-m-d');?>" size="10" onKeyUp="mascara(this,'-',patron, true)" ></td>
				    				<td class="Etiqueta1">&nbsp;&nbsp;<span class="Asterisco">*</span>Hasta:</td>
				    				<td class="LetraNegra">&nbsp;<input name="to" type="text" id="to" value="<?php echo strlen($_POST['to'])>0?$_POST['to']:date('Y-m-d');?>" size="10" onKeyUp="mascara(this,'-',patron, true)" ></td>
				    				<td class="LetraNegra"><button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form,'from*to',2)" ><i class="icon-search icon-white"></i><span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span></button>
				    					<input type="hidden" name="pag" value="2">
				    			<input type="hidden" name="usuCodigo" value="<?php echo htmlspecialchars($_POST['usuCodigo'], ENT_QUOTES, 'UTF-8');?>" >
				    			<input type="hidden" name="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8');?>">
									<input type="hidden" name="txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>">
				    				</td>
				    			</tr>
				    		</table>
			    		</td>
			    	</tr>
			    </table>
			    </form>
			</FIELDSET>
			<?php
			if(isset($_POST['from'])){
				$Arr_Resultado = $obBD_con1->getArrayConsulta(4, $_POST['usuCodigo'].'*'.$_POST['from'].'*'.$_POST['to'], $obBD_conexion);
			}else{
				$Arr_Resultado = $obBD_con1->getArrayConsulta(4, $_POST['usuCodigo'].'*'.date('Y-m-d').'*'.date('Y-m-d'), $obBD_conexion);
			}
				?>
				<FIELDSET>
				    <LEGEND>
				    	<label class="Titulos2">Resultados de la busqueda</label>
				    </LEGEND>
				    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
					    <thead>
						      <tr>
						        <th width="5%" >Cod. Int. </th>
						        <th >Inicio Sesi�n</th>
						        <th >Cierre de Sesi�n </th>
								<th width="3%" >&nbsp;</th>
						      </tr>
					     </thead> 
					     <tbody>
					     <?php foreach($Arr_Resultado as $row){
					     	?>
					     	<tr>
					     		<td align="center"><?php echo $row['Ses_Cod'];?></td>
						        <td align="left"><?php echo $row['Ses_Int']?></td>
						        <td align="left"><?Php echo $row['Ses_Out'];?></td>
								<td align="center">
								<?php 
								
								if(strlen($row['Ses_Out']) > 0){
									$params = $_POST['usuCodigo'].'*'.$row['Ses_Int'].'*'.$row['Ses_Out'];
									$count = $obBD_con1->getRowConsulta(11, $params, $obBD_conexion);
								}else{
									$params = $_POST['usuCodigo'].'*'.$row['Ses_Int'].'*'.date('Y-m-d H:i:s');
									$count = $obBD_con1->getRowConsulta(11, $params, $obBD_conexion);
								}
								
								if($count['count'] > 0){
								?>
									<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
										<button type='button' class='btn btn-success btn-mini' title="Elegir" onclick="this.form.submit();"><i class='icon-arrow-right icon-white'></i></button>
										<input type="hidden" name="pag" value="3">
										<input type="hidden" name="from" value="<?php echo strlen($_POST['from'])>0?$_POST['from']:date('Y-m-d');?>" >
										<input type="hidden" name="to" value="<?php echo strlen($_POST['to'])>0?$_POST['to']:date('Y-m-d');?>" >
										<input type="hidden" name="sesCod" value="<?php echo $row['Ses_Cod'];?>" >
										<input type="hidden" name="usuCodigo" value="<?php echo htmlspecialchars($_POST['usuCodigo'], ENT_QUOTES, 'UTF-8');?>" >
										<input type="hidden" name="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8');?>">
										<input type="hidden" name="txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>">
									</form>
								<?php }else{
									?>
									<img src="../../mascaras/model1/imagenes/32x32/ayuda.png" width="25" height="25" title="No hay actividades registradas en esta sesi�n">
									<?php
								}?>
								</td>
							</tr>
					     	<?php
					     }
					     if(count($Arr_Resultado) == 0){
							?>
							<tr>
						        <td>&nbsp;</td>
						        <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1);?></td>
						        <td>&nbsp;</td>
								<td>&nbsp;</td>
						    </tr>
							<?php
					     }?>
					     </tbody>
				     </table>
			     </FIELDSET>
				<?php
				echo barra_estado(count($Arr_Resultado));
			?>
				<br>
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td width="110">
							<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
							<button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onclick="this.form.submit();"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
							<input type="hidden" name="pag" value="1">
							<input type="hidden" name="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8');?>">
							<input type="hidden" name="txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>">
							</form>
						</td>
					</tr>
				</table>
				
			<?php
			break;
			case 3:
				/**
				 * obtener datos de usuario
				 */
				$row_usuario = $obBD_con1->getRowConsulta(5, $_POST['usuCodigo'].'*'.$_POST['sesCod'], $obBD_conexion);
				?>
				<FIELDSET>
				    <LEGEND>
				    	<label class="Titulos2">Datos del Usuario:</label>
				    </LEGEND>
				    
				    <table width="100%" cellpadding="0" cellspacing="0">
				    	<tr>
				    		<td width="10%" class="Etiqueta1">Cedula:</td>
				    		<td class="LetraNegra">&nbsp;<?php echo $row_usuario['Prs_Ced'];?></td>
				    	</tr>
				    	<tr>
				    		<td class="Etiqueta1">Nombres:</td>
				    		<td class="LetraNegra">&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Ape'];?></td>
				    	</tr>
				    	<tr>
				    		<td width="10%" class="Etiqueta1">Inicio Sesi�n:</td>
				    		<td class="LetraNegra">&nbsp;<?php echo $row_usuario['Ses_Int'];?></td>
				    	</tr>
				    	<tr>
				    		<td width="10%" class="Etiqueta1">Cierre Sesi�n:</td>
				    		<td class="LetraNegra">&nbsp;<?php echo $row_usuario['Ses_Out'];?></td>
				    	</tr>
				    </table>
				    
				</FIELDSET>
				<?php
				if(strlen($row_usuario['Ses_Out']) > 0){
					$params = $_POST['usuCodigo'].'*'.$row_usuario['Ses_Int'].'*'.$row_usuario['Ses_Out'];
					$Arr_Resultado = $obBD_con1->getArrayConsulta(6, $params, $obBD_conexion);
					$Arr_Tablas = $obBD_con1->getArrayConsulta(9, $params, $obBD_conexion);
				}else{
					$params = $_POST['usuCodigo'].'*'.$row_usuario['Ses_Int'].'*'.date('Y-m-d H:i:s');
					$Arr_Resultado = $obBD_con1->getArrayConsulta(6, $params, $obBD_conexion);
					$Arr_Tablas = $obBD_con1->getArrayConsulta(9, $params, $obBD_conexion);
				}
				?>
				<FIELDSET>
					<LEGEND>
				    	<label class="Titulos2">Mostar por:</label>
				    </LEGEND>
				    <table width="100%" cellpadding="0" cellspacing="0">
				    	<tr>
				    		<td width="10%" class="Etiqueta1">Tabla:</td>
				    		<td class="LetraNegra">&nbsp;
				    			<select name="ajax_cmb" id="ajax_cmb" onchange="ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?ajax_cmb='+this.value+'&params=<?php echo $params; ?>','ajax_result');">
				    				<option value="0">Todos</option>
				    				<?php foreach($Arr_Tablas as $row){
				    				?>
				    					<option value="<?php echo $row['Tab_Cod'];?>"><?php echo strlen($row['Tab_Ali']) > 0? $row['Tab_Ali'] : $row['Tab_Nom'];?></option>
				    				<?php
				    				}?>
				    			</select>
				    		</td>
				    	</tr>
				    </table>
				</FIELDSET>
				<FIELDSET>
				    <LEGEND>
				    	<label class="Titulos2">Resultados de la busqueda</label>
				    </LEGEND>
				    <div id="ajax_result">
				    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
					    <thead>
						      <tr>
						        <th width="8%" >Fecha</th>
						        <th width="8%" >Hora</th>
						        <th>Actividad</th>
						        <th width="15%" >Opci�n</th>
						        <th>Detalle de Opci�n</th>
								<th width="3%" >&nbsp;</th>
						      </tr>
					     </thead> 
					     <tbody>
					     <?php 
					     	/**
						  	 * controlar la secuencia de formularios
					      	 */
					     	$i = 0;
					     	foreach($Arr_Resultado as $row){
					     	$arr = explode(' ', $row['Log_Fec']);
					     	$i++;
					     	?>
					     	<tr>
					     		<td align="center"><?php echo $arr[0];?></td>
					     		<td align="center"><?php echo $arr[1];?></td>
						        <td align="left"><?php echo str_replace("{0}", strlen($row['Tab_Ali'])>0?$row['Tab_Ali']:$row['Tab_Nom'], $row['Eve_Des']);?></td>
						        <td align="left"><?php echo $row['Pcs_Lin'];?></td>
						        <td align="left"><?Php echo $row['Pcs_Det'];?></td>
								<td align="center">
									<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name="frm<?php echo $i;?>" id="frm<?php echo $i;?>">
										<button type='button' class='btn btn-success btn-mini' title="Elegir" onclick="Muestra_Aparecer();visualizarCampos(this.form,'mostrar');"><i class='icon-arrow-right icon-white'></i></button>
										<input type="hidden" name="tabCodigo" value="<?php echo $row['Tab_Cod'];?>">
										<input type="hidden" name="logCampos" value="<?php echo $row['Log_Cam'];?>">
										<input type="hidden" name="logValores" value="<?php echo $row['Log_Val'];?>">
										<input type="hidden" name="ajax" value="1">
									</form>
								</td>
							</tr>
					     	<?php
					     }
					     if(count($Arr_Resultado) == 0){
					     	?>
					     	<tr>
						        <td>&nbsp;</td>
						        <td>&nbsp;</td>
						        <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
						        <td>&nbsp;</td>
						        <td>&nbsp;</td>
								<td>&nbsp;</td>
						    </tr>
					     	<?php
					     }?>
					     </tbody>
				     </table>
				     </div>
			     </FIELDSET>
				<?php
				echo barra_estado(count($Arr_Resultado));
				?>
				<br>
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td width="110">
							<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
								<button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onclick="this.form.submit();"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
								<input type="hidden" name="pag" value="2">
							<input type="hidden" name="from" value="<?php echo htmlspecialchars($_POST['from'], ENT_QUOTES, 'UTF-8');?>" >
							<input type="hidden" name="to" value="<?php echo htmlspecialchars($_POST['to'], ENT_QUOTES, 'UTF-8');?>" >
							<input type="hidden" name="usuCodigo" value="<?php echo htmlspecialchars($_POST['usuCodigo'], ENT_QUOTES, 'UTF-8');?>">
							<input type="hidden" name="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8');?>">
							<input type="hidden" name="txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>">
							</form>
						</td>
					</tr>
				</table>
				<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
				  <div id="bgmodal"  class="bgmodal" style="display:none" >
				 <div id="ajax_modal">
				    <div id="mostrar"></div>
				    </div>
				</div>
				<?php
			break;
			
	}
	?>
	<script type="text/javascript" src="../VALIDACIONES/aud_par_monitoreo.js"></script>
	<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</div>
</body>
</html>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>