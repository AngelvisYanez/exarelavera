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

if(isset($validar)){
	$response = $obBD_datos->validarParametros($Ses_Suc_Cod, $obBD_conexion);
	utf8_encode_deep($response); 
	echo json_encode($response);
	exit();
}

if(isset($updateKardex)){
	$response = $obBD_datos->updateKardex($Ses_Suc_Cod, $obBD_conexion);
	utf8_encode_deep($response); 
	echo json_encode($response);
	exit();
}

if(isset($saveComprobantes)){
	$data=$_POST;
  $data['Emp_Cod']=$Ses_Emp_Cod; 
  $data['Suc_Cod']=$Ses_Suc_Cod;
  $data['rows'] = $obBD_datos->getArrayConsulta('ventas.0', $data, $obBD_conexion);

	$response = $obBD_datos->saveComCosto($data,$obBD_conexion);
	utf8_encode_deep($response); 
	echo json_encode($response);
	exit();
}

if(isset($saveComprobantesPeriodo)){
	$datos=$_POST;
  $datos['Emp_Cod']=$Ses_Emp_Cod; 
  $datos['Suc_Cod']=$Ses_Suc_Cod;
  $datos['codigosArray'] = $obBD_datos->getArrayConsulta('ventas.0', $datos, $obBD_conexion);

  $arrayCodigos = array();
  foreach($datos['codigosArray'] as $k=>$v) {
      $arrayCodigos[$k] = $v['Vet_Cod'];
  }
  $List = implode(', ', $arrayCodigos);
  $datos['codigos'] = '(' . $List . ')';

	$response = $obBD_datos->saveComCostoPeriodo($datos,$obBD_conexion);
	utf8_encode_deep($response); 
	echo json_encode($response);
	exit();
}

if(isset($searchDocument)){
	$data=$_GET; 
	$data['Emp_Cod']=$Ses_Emp_Cod; 
	$data['Suc_Cod']=$Ses_Suc_Cod;
	$response['rows'] = $obBD_datos->getArrayConsulta('ventas.0', $data, $obBD_conexion);
	$obBD_datos->echoJson($response);
	exit();
}

if(isset($getDetalleFactura)){
	$response['rows'] = $obBD_datos->getArrayConsulta('ventas.4', $Vet_Cod, $obBD_conexion);
	$obBD_datos->echoJson($response);
	exit();
}


?>

<!DOCTYPE html>
<HTML>
    <head>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Costo Venta Registrar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>  
        <style>#tabsInsert.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}.ui-tabs .ui-tabs-panel{padding: 5px;}</style>
        <script type="text/ecmascript" src="../VALIDACIONES/con_val_costoventa.js"></script>
    </head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Costo de Venta</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch">
                <form id="formFiltros" class="form-horizontal normal" action="javascript:$('#tableResult').Search('#formFiltros','searchDocument');" >
	                <div class="row">  
	                    <div class="col-sm-12 form-horizontal normal">
	                    	<fieldset class="exa-fieldset">
	                        <legend class="Titulos2">Filtrar</legend>

		                    	<!-- Fecha -->
		                        <div class="col-sm-4"> 
		                          <div class="input-group input-group-xs">
                                <label class="form-control form-control-sm">Periodo</label>
		                            <span class="input-group-addon bold alert-info">Desde:</span>
		                            <input name="Fec_Ini" type="text" id="txt_fec_ini" class="form-control input-md datepicker databind" style="text-align: center;"/>
		                            <span class="input-group-addon bold alert-info">Hasta:</span>
		                            <input name="Fec_Fin" type="text" id="txt_fec_fin" class="form-control input-md datepicker databind" style="text-align: center;"/>
		                          </div>
		                        </div>
		                        
		                        <!-- Button -->
	                            <div class="col-sm-1">                                          
	                              <button id="btnSearch" name="btnSearch" type="submit" class="btn btn-sm btn-success">Buscar</button>                                     
	                            </div>

	                            <!-- Button Actualizar -->
	                            <div class="col-sm-offset-10">                                          
	                              <button id="btnUpdate" name="btnUpdate" type="button" class="btn btn-sm btn-warning">Actualizar Costos</button>                         
	                            </div>

		                    </fieldset>
	                    </div>
	                </div>
	            </form>

                <div class="row">  
                    <div class="col-sm-12 form-horizontal normal">
                    	<table id="tableResult"></table>
                        <div id="tableResultPager"></div>
                    </div>
                </div>

                <!--INICIO DEL DIALOGO MOSTRAR EL DETALLE DE LA VENTA--> 
                <div id="verDetalleNota" title="Detalle de Documento">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabs_abo_det" class="ui-tab-fix">
                            <ul style="font-size: 12px;" role="tablist">
                                <li id="ant_detasi"><a href="#ant_det_asi">Items</a></li>
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
                      <button id="btnSave" name="btnSave" class="btn btn-sm btn-primary">Contabilizar</button>                                     
                    </div>

               		<div class="col-sm-2"> 
                      <br>                                        
                      <select id='tipoCom' name='tipoCom' class="form-control form-control-sm">
                        <option value = 'D'>Por Documento</option>
                      	<option value = 'P'>Por Periodo</option>
                      </select>                                     
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
          $.post( "", {getDetalleFactura:true,Vet_Cod:row['Vet_Cod']}, function(responce) 
          {
                  for(let i=0;i<responce['rows'].length;i++){
                    let ids_pg= $('#showProductosNota').jqGrid('getDataIDs').length+1;
                    $('#showProductosNota').jqGrid('addRowData', ids_pg,
                        {
                            index:ids_pg,
                            Ite_Lar:responce['rows'][i].Ite_Lar,
                            Vet_Can:responce['rows'][i].Vet_Can,
                            Vet_Pru:responce['rows'][i].Vet_Pru,
                            Promedio:responce['rows'][i].Promedio,
                            Vet_Imp:responce['rows'][i].Vet_Imp,
                            Costo:responce['rows'][i].Costo,
                            Utilidad:responce['rows'][i].Utilidad
                        },"last");
                  }

                  $('#showProductosNota').jqGrid('footerData', 'set', {
                    Ite_Lar:"<div style='text-align:right;'>TOTALES:</div>",
                    Vet_Imp: $('#showProductosNota').jqGrid('getCol', 'Vet_Imp', true, 'sum'),
                    Costo: $('#showProductosNota').jqGrid('getCol', 'Costo', true, 'sum'),
                    Utilidad: $('#showProductosNota').jqGrid('getCol', 'Utilidad', true, 'sum')
                  },true);

                  $("#showProductosNota").updateGridsSizes();

            },'json').fail(function(error) {
                console.log("El Servidor ha fallado en responder!");
            });
        }

	</script>
</body>
</HTML>
