<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Alejandro Camacho
* @version 1.0
* Fecha de creacion  2023/07/31
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_reporte.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');



/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_rrhh_reporte($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_rrhh_reporte;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($rolesAjax)){ 
    $data=$_POST; 
    $responce['rows'] = $obBD_con1->getArrayConsulta(4, $data, $obBD_conexion);
    $responce['records'] = count($responce['rows']);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);    
}

if (isset($pagosAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $responce['rows'] = $obBD_con1->getArrayConsulta(6, $data, $obBD_conexion);
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}


?>

<!DOCTYPE html>
<html>
    <HEAD>		
    	<meta charset="UTF-8">
        <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?php echo "Reporte Banco/Transferencia [EXA]"; ?></TITLE>
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>  
         <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.js"></script>
         <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>         
        <style></style>
    </HEAD>
	<body>

		<div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Reporte para banco</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-search">
              <div class="row">  
                  <form id="formSearchRol" class="form-horizontal normal" action="javascript:$('#Lis_Cli').Search('#formSearchRol','pagosAjax');">

                  	<input type="text" id='Pld_Cod' name="Pld_Cod" style='display: none' value="">
                  	<input type="text" id='Ban_Cue' name="Ban_Cue" style='display: none' value="">
                  	<input type="text" id='Roles_Where' name="Roles_Where" style='display: none' value="">

                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Plantilla Rol</legend>
                            <div class="form-horizontal normal">                                 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Area:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Are_Cod" name="Are_Cod" class="form-control input-xs" onchange="getRoles()">
                                            <option value="">TODAS</option>
                                            <?php $rs_area = $obBD_con1->getArrayConsulta(1,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_area as $row){  
                                                     ?><option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Plantilla:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Map_Cod" name="Map_Cod" class="form-control input-xs" onchange="getRoles()">
                                            <option value="">TODAS</option>
                                            <?php $rs_maps = $obBD_con1->getArrayConsulta(2,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_maps as $row){  
                                                     ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div>
                            </div>
                        </fieldset>
                    </div>   
                    <div class="col-xs-4">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos Rol</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-1 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-3"> 
                                        <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="getRoles()" required="">                                            
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(3,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_perio as $row){  
                                                     ?><option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>

                                    <label class="col-xs-1 control-label label-xs">Tipo:</label>  
                                    <div class="col-xs-3"> 
                                        <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs" onchange="getRoles()" required="">                                            
                                                <option value="M">Mensual</option>
                                                <option value="S">Semanal</option>
                                        </select>
                                    </div> 

                                    <label id='labelMes' class="col-xs-1 control-label label-xs">Mes:</label>
                                    <div class="col-xs-3" id='divMes'>
                                        <div class="input-group input-group-xs">
                                            <input id="Month" name="Month" type="hidden">
                                            <span id="Mes" class="form-control"></span>
                                            <span class="input-group-btn">
                                                <button id="MonthButton" onclick="$('#Month').monthpicker('show','#Mes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
                                            </span>
                                        </div>  
                                    </div> 

                                    <label id='labelSemana' class="col-xs-1 control-label label-xs">Semana:</label> 
                                     <div class="col-xs-3" id='divSemana'>
                                            <select id="Rol_S" class="form-control input-xs datatrigger" onchange="getRoles();"></select>                               
                                    </div> 

                                </div>
                                <div class="form-group">
                                    <label class="col-xs-1 control-label label-xs">Roles:</label>  
                                    <div class="col-xs-11"> 
                                        <select id="Rol_Cod" name="Rol_Cod" class="form-control input-xs" onchange="" required="">                                        
                                        </select>
                                    </div>    
                                </div>
                            </div>
                        </fieldset>
                    </div> 

                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos Pago</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                	<label class="col-xs-3 control-label label-xs">Banco:</label>  
                                    <div class="col-xs-9"> 
                                    	<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs ed_element ed_CHE ed_TRF ed_TDC" required="" onchange="">
											 <?php
						                          $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(5, array('Ban_Tip'=>'B'), $obBD_conexion);
						                          foreach ($row_rs_tipo_asien2 as $row)
						                          { ?>
												<option value="<?php echo $row['Ban_Cod']; ?>" data-des="<?php echo $row['Pld_Des']; ?>"
												 data-cue="<?php echo $row['Ban_Cue']; ?>" data-cdc="<?php echo $row['Pld_Cdc']; ?>"
												 data-pla="<?php echo $row['Pld_Cod']; ?>">
													<?php echo $row['Pld_Des'] ?>-
													<?php echo $row['Ban_Cue'] ?>
												</option>
												<?php } ?>
										</select>
                                    </div> 
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Filtrar Por:</label>
                                    <div class="col-xs-9 radioset opt_search">
                                          <input id="radsc1" name="op_opciones" type="radio" value="t" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Transferencia&nbsp;&nbsp;&nbsp;</label>
                                          <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;Cheque&nbsp;&nbsp;&nbsp;</label>
                                          <input id="radsc3" name="op_opciones" type="radio" value="e" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;Efectivo&nbsp;&nbsp;</label>
                                    </div>
                                </div>

                            </div>
                        </fieldset>
                    </div>


                    <div class="col-xs-1 center vcenter" style="height: 70px;"><button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>                    
                </form>

	            </div>
	        </div>
	        <div style="min-height:460px;">
                    <table id="Lis_Cli"></table>
                    <div id="Pag_Cli"></div>

                     <div style="padding-top: 10px; padding-bottom: 0px;">
                        <button type="button" onclick="exportar()" class="btn btn-primary btn-sm start" title="Exportar registros"><i class="glyphicon glyphicon-download-alt"></i> <span>Exportar</s-printpan></button>
                    </div>
            </div>
	    </div>
	</div>

	<div id="exportar" style="display: none;">
        <table id="tablaExporta" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
    </div>

    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_reportes.js?x=500"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>	
		
	<script type="text/javascript">
        $(function() {
            $('#divSemana').hide();
            $('#labelSemana').hide();
            $('#Month').attr('data-monthplacer','#Mes').createMonthPicker({showYear:false, prepend:'Seleccione Mes',openOnFocus:false}).monthpicker('setMonthActive',0);
            fillWeeks();

            $('#Month').on('change', function () {
                getRoles();
            });

            $('#Rol_Tip').on('change', function () {
                var tipo = $(this).val();
                if(tipo == 'M'){
                    $('#divSemana').hide();
                    $('#labelSemana').hide();
                    $('#divMes').show();
                    $('#labelMes').show();
                }
                if(tipo == 'S'){
                    $('#divMes').hide();
                    $('#labelMes').hide();
                    $('#divSemana').show();
                    $('#labelSemana').show();
                }
            });

        });

        

    </script>


	</body>
</html>
