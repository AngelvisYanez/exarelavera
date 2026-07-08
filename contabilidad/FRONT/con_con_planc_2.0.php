<?php	
/** 
 * Alias:	Consultar
 * Descripción: Permite modificar las cuentas del plan de cuentas
 * Fecha de actualización:	2012-04-20
 * Desarrollador:	Lewis Chimarro
 * Fecha de actualización:	2015-03-07
 * Desarrollador:	Lewis Chimarro
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
	
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas
 */
$obBD_con1 =  new Class_Log_Datos_Con;

if(isset($excel)){ ?>
    <table  width="100%" border="1" style="border-collapse:collapse;table-layout:fixed;" cellpadding="0" cellspacing="0">
        <tr>
            <td colspan="2" align="center" bgcolor="#CCCCCC" ><strong><?php echo $descrip; ?></strong></td>             
        </tr>
        <tr>
              <td width="12%" align="center" bgcolor="#CCCCCC" ><strong>C&oacute;digo</strong></td>
              <td width="88%" align="center" bgcolor="#CCCCCC" ><strong>Cuentas</strong></td>
        </tr>
            <?php
            echo $obBD_con1->obtenerPlanCuentas($codigo, 0, $obBD_conexion);
            ?>
    </table>

<?php exit(); } ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/con_val_planc.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
    <td height="10">&raquo; Consultar Plan de Cuentas</td>
</tr>
<tr>
<td height="400" valign="top">
<table width="100%">
<tr>
<td>
<?php
if(!isset($op)) $op = 1;

$descripcion = "Cuentas*Impresión";
$pag1= $_SERVER['PHP_SELF']."?op=1";
$pag2= $_SERVER['PHP_SELF']."?op=2";
tabs(2,$descripcion, $pag1.'*'.$pag2, $op);
?>
<div id="ContTabul">
<?php 
switch($op){
	case 1:
		?>
		<FIELDSET>
			<LEGEND>
				<label class="Titulos2">Buscar por:</label>
			</LEGEND>
			<?Php
			mensaje_requerido(); 
			?>
			<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name="form" id="form">
				<table width="515" border="0">
				  <tr>
					<td width="164"><input name="op_opciones" type="radio" value="d"   
					onClick="setfocus(this.form.txt_busqueda)" style="cursor:pointer"  checked>
					<span class="Etiqueta1">Descripción</span></td>
					<td width="341">
					<input type="radio" name="op_opciones"  
					onClick="setfocus(this.form.txt_busqueda)" style="cursor:pointer" value="r">
					<span class="Etiqueta1">Código</span></td>
				  </tr>
				</table>
				<table width="572" border="0" cellpadding="0" cellspacing="0" class="BarraBusqueda">
				  <tr>
					<td height="37"><span class="Asterisco">*</span> B&uacute;squeda:
					  <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="45">&nbsp;&nbsp;
					  <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)">
					    <i class="icon-search icon-white"></i>
					    <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
					    </button>
					  <input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar">	  </td>
					</tr>
				</table>
			</form>
		</FIELDSET>
		<?php
		if(isset($_POST['hdd_buscar'])){
				/**
				 * buscar cuenta
				 */
				$row_rs_nodos = $obBD_con1->getArrayConsulta(($op_opciones=='d')? 313 : 314,trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
		?>
			<fieldset>
				<LEGEND>
					<label class="Titulos2">Detalle de Cuentas:</label>
				</LEGEND>
				<table width="100%" cellpadding="0" cellspacing="0" class="fixedHeader01">
				    <thead>
					  <tr>
						<th width="8%"><strong>C&oacute;d. Int.</strong></th>
						<th width="9%">Plan de Cuenta</th>
						<th width="9%">C&oacute;digo</th>
						<th ><strong>Cuenta</strong></th>
						<th width="10%"><strong>Tipo</strong></th>
						<th >A. P. D&eacute;bito</th>
						<th >A. P. Cr&eacute;dito</th>
						<th width="10%" class="Cabecera"><strong>Estado</strong></th>
						</tr>
				   </thead>
				   <tbody>
				   <?php 
				   
				   foreach($row_rs_nodos as $row){
						$num_cuenta = $row['Pld_Cdc'];
						if ($row['Pld_Est'] == 'Inactivo'){ 
							$color_d = 'style="color: red;"'; 
							if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
						}else{
							$color_d = '';	
						}?>
					  <tr>
							<td align="center" <?php echo $color_d; ?>><?php echo $row['Pld_Cod']; ?></td>
							<td <?php echo $color_d; ?>><?php echo $row['Pla_Obs']; ?></td>
							<td <?php echo $color_d; ?>><?php echo $row['Pld_Cdc']; ?></td>
							<td <?php echo $color_d; ?>><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Pld_Des'], '#FFFF00', 1); ?></td>
							<td <?php echo $color_d; ?> align="center"><?php echo $row['Pld_Tip']; ?></td>
							<td <?php echo $color_d; ?> align="center"><?php echo $row['Pld_Deb']; ?></td>
							<td <?php echo $color_d; ?> align="center"><?php echo $row['Pld_Cre']; ?></td>
							<td <?php echo $color_d; ?> align="center"><?php echo $row['Pld_Est']; ?></td>
					</tr>
	  			<?php }
	  				if(count($row_rs_nodos)==0){
	  					?>
	  					<tr><td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td><?Php echo error_alerta("No hay ninguna cuenta creada", 1) ?></td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	</tr>
	  					<?php
	  				}?>
				   </tbody>
				</table>
			</fieldset>
			<?php
			echo barra_estado(count($row_rs_nodos)).'<br>';
			?>
		<?php	
		}
	break;
	case 2:
			/**
  			 * Cargado de los planes de cuenta de una empresa en especifico.
  			 */
  			$row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod,$obBD_conexion);
  			?>
  			<FIELDSET>
				<LEGEND>
					<label class="Titulos2">Resultados de la Busqueda</label>
				</LEGEND>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
					<thead>
      					<tr>
        					<th width="8%"><strong>C&oacute;d. Int.</strong></th>
        					<th ><strong>Descripci&oacute;n</strong></th>
        					<th width="18%">&nbsp;</th>
      					</tr>
					</thead>
    				<tbody>
      				<?foreach($row_rs_planes as $row){
	 		 			if($row['Pla_Est']=='Inactivo'){ 
							$rojo='#FF0000';
							if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
						}else{
							$rojo='';
						}			
						?>
					      <tr>
					        	<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Cod']; ?></FONT></td>
					        	<td><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Obs']; ?></FONT></td>
					        	<td align="center">
						        	<?php 
						        		if($row['Pla_Est'] == 'Inactivo'){
						        			?>
								        	<img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25" title="La cuenta esta inactiva">
								        	<?php
						        		}else{
						        			?>
						        			<form action="con_pri_planc_2.0.php" method="post" name= "form1" target="_blank" style="display: inline;">
										        <button type="button" class="btn btn-primary start" title="Imprimir Plan de Cuentas" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
										        <input type="hidden" name="codigo" id="codigo" value="<?php echo $row['Pla_Cod']; ?>" />
									        </form> 
                                                                                <button type="button" class="btn btn-primary start" title="Descargar Plan de Cuentas" onclick="exportExcel('<?php echo $row['Pla_Cod']; ?>','<?php echo $row['Pla_Obs']; ?>');"> <i class="icon-share icon-white"></i> <span>Excel</span> </button>    
						        			<?php
						        		}
						        	?>
								</td>
					      </tr>
				      <?php 
      				}
					  /**
					   * Mostrar un mensaje si no existen planes creados 
					   */
					  if (count($row_rs_planes) == 0) 
					  {
						?>
							<tr>
								<td>&nbsp;</td>
					  	  		<td><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
					  	  		<td>&nbsp;</td>
					  		</tr>
						<?php
					  }
					  ?>
			 		</tbody>        
			    </table>   
			</FIELDSET>
  			<?php
  			echo barra_estado(count($row_rs_planes)).'<br>';
	break;
}
?>
</div>
</td>
</tr>
</table>	  
</td>
</tr>
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/con_par_planc.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
<script type="text/javascript">
function exportExcel(Pla_Cod,Pla_Obs){
    $.get('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{'excel':true,'codigo':Pla_Cod,'descrip':Pla_Obs}, function(response){
        $.downloadFile($.exportarExcelBlob(response,'Plan de Cuentas'),'PlanCuentas.xls');
    }).fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
    
}
</script>
</BODY></HTML>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>