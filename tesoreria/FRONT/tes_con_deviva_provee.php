<?php	
/**
* @abstract Permite registrar el costo de venta por cada venta 
* @author Alejandro Camacho
* @version 1.0
* Fecha de creaciion 06/07/2021
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once("../LOGICA/tes_log_deviva_provee.php");

$obBD_conexion = new Class_Log_Conexion_Iva($Ses_Dat_Dis);
$obBD_datos =  new Class_Logica_Iva;

$hoy = date("Y-m-d");
$mes = date("m");


if(isset($searcwerw)){
	$data=$_GET; 
	$data['Emp_Cod']=$Ses_Emp_Cod; 
	$data['Suc_Cod']=$Ses_Suc_Cod;
	$response['rows'] = $obBD_datos->getArrayConsulta('ventas.0', $data, $obBD_conexion);
	$obBD_datos->echoJson($response);
	exit();
}

if(isset($searchDocument)){
    $FILTERS=array();
    array_push($FILTERS,'isActive');
    array_push($FILTERS,'byDateRange');

    $response = $obBD_datos->getPageGrid('compras.selectWhere', array_merge($_GET,array('where'=>array(), 'setWhere'=>array_merge($FILTERS,array('setUsuario','setRetencion','setTotales')))), $obBD_conexion);

    for($i = 0, $size = count($response['rows']); $i < $size; $i++) {
        if($response['rows'][$i]['Iva_Tot'] == '0.00'){
            unset($response['rows'][$i]);
        }
    }
    $response['rows'] = array_values($response['rows']);

    foreach ($response['rows'] as &$row){
        $row['proveedor']=$row['Proveedor'];
        $row['vendedor']=$row['Vendedor'];

        if($row['Ret_Data'] == "S"){
            $ret_data = $obBD_datos->getRowConsulta('retencion.selectWhere', array('where'=>array('retencion.Ret_Cod'=>$row['Ret_Cod']), 'group'=>'retencion.Ret_Cod', 'setWhere'=>array('setTotales')), $obBD_conexion);
            $row = array_merge(array('Autorizacion1'=>$ret_data['Autorizacion1']),$row);
        }

        $activo = $obBD_datos->getRowConsulta('compras.2', $row['Cop_Cod'], $obBD_conexion);
        $reembolso = $obBD_datos->getRowConsulta('compras.3', $row['Cop_Cod'], $obBD_conexion);

        $activo['Activo'] > 0 ? $row = array_merge(array('Activo'=>'SI'),$row) : $row = array_merge(array('Activo'=>'NO'),$row);
        $reembolso['Reembolso'] > 0 ? $row = array_merge(array('Reembolso'=>'SI'),$row) : $row = array_merge(array('Reembolso'=>'NO'),$row);
    }

    $obBD_datos->echoJson($response);
}


if(isset($getDetalleFactura)){
	$response['rows'] = $obBD_datos->getArrayConsulta('ventas.4', $Vet_Cod, $obBD_conexion);
	$obBD_con1->echoJson($response);
	exit();
}


?>

<!DOCTYPE html>
<HTML>
    <head>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>  
        <style>#tabsInsert.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}.ui-tabs .ui-tabs-panel{padding: 5px;}</style>
        <script type="text/ecmascript" src="../VALIDACIONES/tes_val_deviva_provee.js"></script>
    </head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Devolución de IVA a proveedores de exportadores</h3></div>        
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

               	<div class="row"> 
                  <div class="col-sm-1"> 
                      <br>                                        
                      <button id="btnExcel" name="btnExcel" class="btn btn-sm btn-primary" onclick="exportar()"> <i class="glyphicon glyphicon-download-alt"></i> Excel</button>                                     
                    </div>
                </div>

            </div>
    	</div>
	</div>

  <div id="exportar" style="display: none;">
        <? php //echo $obBD_datos->getReportHeader($Ses_Suc_Cod, 'Devolucion de IVA', '', $obBD_conexion, false, 12,true) ?>
        <table id="tablaExporta" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
  </div>

  <script type="text/javascript">
     function exportar(){
        $('#tablaExporta').html($('#tableResult').jqGrid('exportGridInnerHTML',{footer:true,bodyBorder:false,removeHiddens:true}));
        $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'Devolucion'), 'DevolucionIVA_' + $.getDate() + '.xls');
    }
  </script>

  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>

</body>
</HTML>
