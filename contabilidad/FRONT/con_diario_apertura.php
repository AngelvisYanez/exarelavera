<?Php
require_once('../../administrador/LOGICA/seguridad.php'); //para permitir que la pagina cargue
require_once('../LOGICA/con_log_balances.php'); //Busqueda de periodos, utitlidad


$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Con;

$rs_periodos = $obBD_con1->getArrayConsulta(219,$Ses_Emp_Cod, $obBD_conexion);
?>


<!DOCTYPE html>
<html>
<head>
	<!--title><?Php echo $Ses_Sys_Nom; ?></title-->
	<TITLE><?Php echo "Diario de Apertura [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.min.js"></script>
</head>
<body>

	<div class="panel panel-main">
        <div class="panel-heading exa-header">
        	<h3 class="panel-title">&raquo;  Generar Diario de Apertura</h3>
        </div>

    	<div class="row">
    		<div class=" col-xs-3" style="margin-left: 10px; margin-top: 10px;">
    			<form action="con_mod_compr_2.0.php" method="post" name= "form1" id="form1" target="_blank">
					
					<!-- <div class="form-group">
						<label for="Pec_Cod2">Periodo a obtener cuentas: </label>
						<select class="form-control" name="Pec_Cod2" id="Pec_Cod2">
						    <?Php 
								foreach($rs_periodos as $row)
								{
							?>
					        	<option value="<?Php echo $row['Pec_Cod'];?>"><?Php echo $row['Periodo']; ?></option>
					        <?php		
								}				
							?>
						</select>
					</div> -->
					
					<div class="form-group">
						<label for="Pec_Cod1">Periodo: </label>
						<select class="form-control" name="Pec_Cod1" id="Pec_Cod1">
						    <?Php 
								foreach($rs_periodos as $row)
								{
							?>
					        	<option value="<?Php echo $row['Pec_Cod'] . '*' . $row['Periodo'];?>"><?Php echo $row['Periodo']; ?></option>
					        <?php		
								}				
							?>
						</select>
					</div>
					

					<input name="Max_Niv2" type="hidden" id="Max_Niv2" value="7">
					<input name="Diario_Generado" type="hidden" id="Diario_Generado" value="True">

					<button style="margin-bottom: 10px;" type="submit" class="btn btn-primary" title="Generar Diario"> <i class="icon-share icon-white"></i> <span>Generar</span> </button>			
				</form>	
    		</div>
    	</div>	

	</div>
	

</body>
</html>