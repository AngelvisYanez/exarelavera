<?php	
/**
* @abstract Permite registrar el costo de venta por cada venta 
* @author Alejandro Camacho
* @version 1.0
* Fecha de creaciion 06/07/2021
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once("../../inventario/LOGICA/inv_logica_inventario.php");

$obBD_conexion = new Class_Log_Conexion_Inventario($Ses_Dat_Dis);
$obBD_datos =  new Class_Logica_Inventario;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($deleteComprobantes)){
	$data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod; 
    $data['Suc_Cod']=$Ses_Suc_Cod;
    $data['rows'] = $obBD_datos->getArrayConsulta('comprobantes.4', $data, $obBD_conexion);

	$response = $obBD_datos->deleteComCosto($data,$obBD_conexion);
	utf8_encode_deep($response); 
	echo json_encode($response);
	exit();
}

if(isset($deleteComprobante)){
    $response = $obBD_datos->deleteComprobante($codigo,$obBD_conexion);
    utf8_encode_deep($response); 
    echo json_encode($response);
    exit();
}

if(isset($searchDocument)){
	$data=$_GET; 
	$data['Emp_Cod']=$Ses_Emp_Cod; 
	$data['Suc_Cod']=$Ses_Suc_Cod;
	$response['rows'] = $obBD_datos->getArrayConsulta('comprobantes.4', $data, $obBD_conexion);
	$obBD_datos->echoJson($response);
	exit();
}

if(isset($getDetalleComprobante)){
	$response['rows'] = $obBD_datos->getArrayConsulta('comprobantes.6', $Com_Cod, $obBD_conexion);
	$obBD_con1->echoJson($response);
	exit();
}

?>

<!DOCTYPE html>
<HTML>
    <head>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Costo Venta Elimiar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?php
        $mask_model = 'model1';
        require_once("../../mascaras/unified-loader.php");
        ?>
        <style>#tabsInsert.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}.ui-tabs .ui-tabs-panel{padding: 5px;}</style>
        <script type="text/ecmascript" src="../VALIDACIONES/con_val_baj_costoventa.js"></script>
    </head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Comprobantes Costo de Venta</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch">
                <form id="formFiltros" class="form-horizontal normal" action="javascript:$('#tableResult').Search('#formFiltros','searchDocument');" >
	                <div class="row">  
	                    <div class="col-sm-12 form-horizontal normal">
	                    	<fieldset class="exa-fieldset">
	                        <legend class="Titulos2">Filtrar</legend>

		                    	<!-- Fecha -->
		                        <div class="col-sm-3"> 
		                          <div class="input-group input-group-xs">
		                            <span class="input-group-addon bold alert-info">Desde:</span>
		                            <input name="Fec_Ini" type="text" id="txt_fec_ini" class="form-control input-sm datepicker databind" style="text-align: center;"/>
		                            <span class="input-group-addon bold alert-info">Hasta:</span>
		                            <input name="Fec_Fin" type="text" id="txt_fec_fin" class="form-control input-sm datepicker databind" style="text-align: center;"/>
		                          </div>
		                        </div>
		                        
		                        <!-- Button -->
	                            <div class="col-sm-1">                                          
	                              <button id="btnSearch" name="btnSearch" class="btn btn-sm btn-success">Buscar</button>                                     
	                            </div>

		                    </fieldset>
	                    </div>
	                </div>
	            </form>

                <div class="row">  
                    <div class="col-sm-12 form-horizontal normal">
                    	<table id="tableResult"></table>
                        <div id="tableResultPager"></div>
                        <div class="Titulos2" ><span id="plan-footer"><strong>Leyenda:</strong><span class="glyphicon glyphicon-stop red"></span> Descuadrado</div>
                    </div>
                </div>

                <!--INICIO DEL DIALOGO MOSTRAR EL DETALLE DEl COMPROBANTE--> 
                <div id="verDetalleNota" title="Detalle del Comprobante">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabs_abo_det" class="ui-tab-fix">
                            <ul style="font-size: 12px;" role="tablist">
                                <li id="ant_detasi"><a href="#ant_det_asi">Asientos</a></li>
                            </ul>
                            <div id="ant_det_asi">
                                <div class="row">
                                    <div class="col-sm-12" style="padding-top: 10px;">
                                        <table id="showProductosNota" name="showProductosNota"></table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>


               	<div class="row">  
                    <div class="col-sm-1"> 
                      <br>                                        
                      <button id="btnDelete" name="btnDelete" class="btn btn-sm btn-danger">Eliminar Comprobantes</button>                                     
                    </div>
                </div>

            </div>
    	</div>
	</div>


	<script type="text/javascript">

		function verDetalle(row){
          $("#showProductosNota").updateGridsSizes();
          $("#showProductosNota").jqGrid("clearGridData").trigger("reloadGrid");
          $('#verDetalleNota').dialog('open');
          $.post( "", {getDetalleComprobante:true,Com_Cod:row['Com_Cod']}, function(responce) 
          {
                  for(let i=0;i<responce['rows'].length;i++){
                    let ids_pg= $('#showProductosNota').jqGrid('getDataIDs').length+1;
                    $('#showProductosNota').jqGrid('addRowData', ids_pg,
                        {
                            index:ids_pg,
                            Pld_Cod:responce['rows'][i].Pld_Cod,
                            Pld_Des:responce['rows'][i].Pld_Des,
                            Com_Con:responce['rows'][i].Com_Con,
                            Debe:responce['rows'][i].Debe,
                            Haber:responce['rows'][i].Haber
                        },"last");
                  }

                  $('#showProductosNota').jqGrid('footerData', 'set', {
                    Pld_Des:"<div style='text-align:right;'>TOTALES:</div>",
                    Debe: $('#showProductosNota').jqGrid('getCol', 'Debe', true, 'sum'),
                    Haber: $('#showProductosNota').jqGrid('getCol', 'Haber', true, 'sum')
                  },true);

                  $("#showProductosNota").updateGridsSizes();

            },'json').fail(function(error) {
                console.log("El Servidor ha fallado en responder!");
            });
        }

        function eliminarComp(row){;
          $.post( "", {deleteComprobante:true,codigo:row['Com_Cod']}, function(response) 
          {
                $.alert('La transacción se realizo con Éxito!');
                $('#tableResult').jqGrid('delRowData',row['Com_Cod']);

                var parseTotal=  $('#tableResult').jqGrid('getCol', 'Com_Val', false, 'sum');
                $('#tableResult').jqGrid('footerData', 'set', {Com_Val: parseTotal});

            },'json').fail(function(error) {
                console.log("El Servidor ha fallado en responder!");
            });
        }

	</script>

</body>
</HTML>
