<?php
/**
 * @abstract Permite realizar la modificación de un proceso de facturación de viajes
 * @author Erik Niebla
 * @version 2.0
 * Fecha de creación  2017-02-13
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_viaje.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_con1 = new MysqlDatos(true);
//viajes
if(isset($viajesAjax)){
    $resp=$obBD_con1->getPageGrid('viaje', array_merge($_GET,array('setWhere'=>array('isActive')/*, 'order'=>'Cliente DESC'*/)) );
    foreach($resp['rows'] as &$v){
        if($v['Via_Fac']=='F'){
            $vet=$obBD_con1->getRow(null,$obBD_con1->select()->from('ventas',array('Vet_Num'))->where('Vet_Cod=?',$v['Vet_Cod']));
            $v['Vet_Num']=" No. $vet[Vet_Num]";
        }
    } unset($v);
    $obBD_con1->echoJson($resp);
}
if(isset($clienteAjax)){ $obBD_con1->getPageGridJson('cliente', array_merge($_GET,array('setWhere'=>array('isActive')))); }
if(isset($proveeAjax)){ $obBD_con1->getPageGridJson('proveedore', array_merge($_GET,array('setWhere'=>array('isActive')))); }
if(isset($choferAjax)){ $obBD_con1->getPageGridJson('chofer', array_merge($_GET,array('setWhere'=>array('isActive','setLastVehiculo'))) ); }

?>

<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/ecmascript" src="../VALIDACIONES/tca_val_viaje_3.0.js"></script>
    <style>.ui-tab-fix.ui-tabs.noPaddingV .ui-tabs-panel{padding-top:5px !important;}</style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consulta de Viajes</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div id="bus_fac" class="row">
            <form id="reportForm" name="reportForm" class="form-horizontal normal" action="javascript:$('#viajesReport').Search('#reportForm','viajesAjax');">
                <div class="col-md-12">
                    <div id='tabsMain' class="ui-tabs ui-tab-fix noPaddingH noPaddingV noBorder">
                        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                            <li><a href="#tabs-1">Por Cliente</a></li>
                            <!-- <li><a href="#tabs-2">Por Proveedor</a></li> -->
                        </ul>
                        <div id="tabs-1" class="ui-tabs-panel" >
                            <div class="row">
                            <div class="col-md-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Seleccionar Cliente</legend>
                                <div class="form-group">
                                    <input type="hidden" name="Cli_Cod" data-cliente="Cli_Cod">
                                    <label class="control-label col-md-3 col-sm-4 label-sm required">C&eacute;dula/R.U.C.:</label>
                                    <div class="col-md-7 col-sm-7">
                                        <div class="input-group">
                                            <input type="text" data-cliente="Ruc" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="" />
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#clienteDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                <button class="btn btn-success btn-xs" type="button" title="Limpiar Par&aacute;metros" onclick="$('#reportForm').setData({},'cliente');"><span class="glyphicon glyphicon-eject"></span></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-3 label-sm">Cliente:</label>
                                    <div class="col-sm-7">
                                        <input type="text" data-cliente="Cliente" class="form-control input-xs" readonly="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-3 label-sm">Direcci&oacute;n:</label>
                                    <div class="col-sm-7">
                                        <input type="text" data-cliente="Prs_Dir" class="form-control input-xs" readonly="">
                                    </div>
                                </div>
                            </fieldset>
                            </div>
                            <div class="col-md-6" id="reportDateRange">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtrar Por:</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Estado:</label>
                                    <div class="col-sm-10 radioset">
                                        <input id="r1" name="op_opciones" type="radio" value="T" checked="" onclick="setfocus(this.form.search)"/><label for="r1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                        <input id="r2" name="op_opciones" type="radio" value="F" onclick="setfocus(this.form.search)"/><label for="r2">&nbsp;&nbsp;Facturado&nbsp;&nbsp;</label>
                                        <input id="r3" name="op_opciones" type="radio" value="NF" onclick="setfocus(this.form.search)"/><label for="r3">&nbsp;&nbsp;Sin Facturar&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Sem:&nbsp;&nbsp;<input type="checkbox" class="check-big" value="S" offval="N" onchange="$('.rangeDiv')[$(this).is(':checked')?'hide':'show'](); $('.semDiv')[!$(this).is(':checked')?'hide':'show'](); /*$('.dateRangeInputs').find('input[type=text]').prop('disabled',$(this).is(':checked')).end().find('.alert-info')[$(this).is(':checked')?'addClass':'removeClass']('alert-disabled'); $(this).parent().parent().find('select').prop('disabled',!$(this).is(':checked')).val('');*/" /></label>
                                    <div class="col-sm-7 semDiv" style="display: none;">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon alert-info" >Año</span>
                                            <select name="Via_Year" class="form-control"><?php echo $obBD_con1->htmlOptions($obBD_con1->getArrayConsulta('perio_cont.getForSelect', null), 'Year', 'Year' ); ?></select>
                                            <span class="input-group-addon alert-info" >Semana</span>
                                            <select name="Via_Sem" class="form-control semanaSearch"><option value=""></option></select>
                                        </div>
                                    </div>
                                    <label class="col-sm-1 control-label label-xs rangeDiv">Fecha:</label>
                                    <div class="col-sm-7 rangeDiv">
                                        <div class="input-group input-group-xs dateRangeInputs">
                                            <span class="input-group-addon alert-info" >Desde</span>
                                            <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control">
                                            <span class="input-group-addon alert-info" >Hasta</span>
                                            <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="hidden" name="Cho_Cod" data-chofer="Cho_Cod">
                                    <label class="col-sm-2 control-label label-xs">Chofer:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" name="Chofer" data-chofer="Chofer" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" />
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-xs" type="button" title="Buscar Chofer" onclick="$('#choferDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                <button class="btn btn-success btn-xs" type="button" title="Limpiar Par&aacute;metros" onclick="$('#reportForm').setData({},'chofer');"><span class="glyphicon glyphicon-eject"></span></button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="center">
                                        <button class="btn btn-success btn-xs" type="button" title="Buscar Viajes" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                    </div>
                                </div>
                            </fieldset>
                            </div>
                            </div>
                        </div>
                        
			<!-- <div id ="tabs-2" class="ui-tabs-panel" data-type="provee" data-report="Reporte por Provedor(es)">
                            <div class="row">
                            <div class="col-md-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Seleccionar Proveedor</legend>
                                <div class="form-group">
                                    <input type="hidden" data-provee="Prv_Cod" name="Prv_Cod">
                                    <label class="control-label col-md-3 col-sm-4 label-sm required">C&eacute;dula/R.U.C.:</label>
                                    <div class="col-md-7 col-sm-7">
                                        <div class="input-group">
                                            <input type="text" data-provee="Ruc" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="" />
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-xs" type="button" title="Buscar Proveedor" onclick="$('#proveeDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                <button class="btn btn-success btn-xs" type="button" title="Limpiar Par&aacute;metros" onclick="$('#reportForm').setData({},'provee')"><span class="glyphicon glyphicon-eject"></span></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-3 label-sm">Proveedor:</label>
                                    <div class="col-sm-7">
                                        <input type="text" data-provee="Proveedor" class="form-control input-xs" readonly="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-3 label-sm">Direcci&oacute;n:</label>
                                    <div class="col-sm-7">
                                        <input type="text" data-provee="Prs_Dir" class="form-control input-xs" readonly="">
                                    </div>
                                </div>
                            </fieldset>
                            </div>
                            </div>
                        </div> 
 			-->

                    </div>
                </div>
            </form>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div>
                    <table id="viajesReport"></table>
                    <div id="viajesReportPager"></div>
                </div>
                <div style="padding-top: 10px; padding-bottom: 0px;">
                    <button class="btn btn-sm btn-primary" onclick="imprimirReporte();/*$('#listClie').jqGrid('printGrid',{nombre:'Ventas',hoja:'Salidas',caption:true});*/" type="button" title="Imprimir    "><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                    <button type="button" onclick="exportarReporte();" class="btn btn-primary btn-sm start" title="Descargar archivo de Excel"><i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Inicio del diálogo para buscar un cliente -->
<div id="clienteDialog" title="B&uacute;squeda de Clientes"></div>
<div id="proveeDialog" title="B&uacute;squeda de Proveedores"></div>
<div id="choferDialog" title="B&uacute;squeda de Choferes"></div>
<script type="text/javascript">
$(function () {

});
</script>
<?php //$obBD_con1->setReports('REPORTE DE VIAJES'); ?>
<script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>



