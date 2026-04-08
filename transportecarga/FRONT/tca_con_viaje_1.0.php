<?php
/**
 * @abstract Permite realizar la modificación de un proceso de facturación de viajes
 * @author José Ambuludí
 * @version 2.0
 * Fecha de creación  2017-02-13
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_viaje.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Viaje($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_Viaje;

$hoy = date("Y-m-d");

//Sección para cargar datos en el Jqgrid referente a las facturas registradas
if (isset($facturasAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(24, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(24, $data, $obBD_conexion);
    }
	utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

//Sección para cargar datos en el Jqgrid referente a los clientes registrados
if (isset($clienteAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(25, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(25, $data, $obBD_conexion);
    }
	utf8_encode_deep($responce);	
    echo json_encode($responce);
    exit();
}

//Sección para cargar datos en el Jqgrid referente a los clientes registrados
if (isset($choferAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(26, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(26, $data, $obBD_conexion);
    }
	utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consulta de Viajes</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="bus_fac" class="row">
                    <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Fac').Search('#frm_bus','facturasAjax');">
                        <input type="hidden" id="Cli_Cod" name="Cli_Cod" class="cliente">
                        <input type="hidden" id="Cho_Cod" name="Cho_Cod" class="chofer">
                        <div class="col-md-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Seleccionar Cliente</legend>
                                <div class="form-group">
                                    <label class="control-label col-md-3 col-sm-4 label-sm required">C&eacute;dula/R.U.C.:</label>
                                    <div class="col-md-7 col-sm-7">
                                        <div class="input-group">
                                            <input type="text" id="Prs_Ced" name="Prs_Ced" class="form-control input-xs cliente" placeholder="Seleccione un cliente" readonly="">
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-xs cliente" type="button" title="Buscar Cliente" onclick="$('#clienteDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                <button class="btn btn-success btn-xs cliente" type="button" title="Limpiar Par&aacute;metros" onclick="$('.cliente').val('');"><span class="glyphicon glyphicon-eject"></span></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-3 label-sm">Cliente:</label>
                                    <div class="col-sm-7">
                                        <input type="text" id="cliente" name="cliente" class="form-control input-xs cliente" readonly="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-3 label-sm">Direcci&oacute;n:</label>
                                    <div class="col-sm-7">
                                        <input type="text" id="Prs_Dir" name="Prs_Dir" class="form-control input-xs cliente" readonly="">
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-md-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtrar Por:</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Estado:</label>
                                    <div class="col-sm-10 radioset">
                                        <input id="r1" name="op_opciones" type="radio" value="r1" checked="" onclick="setfocus(this.form.search)"/><label for="r1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                        <input id="r2" name="op_opciones" type="radio" value="r2" onclick="setfocus(this.form.search)"/><label for="r2">&nbsp;&nbsp;Facturado&nbsp;&nbsp;</label>
                                        <input id="r3" name="op_opciones" type="radio" value="r3" onclick="setfocus(this.form.search)"/><label for="r3">&nbsp;&nbsp;Sin Facturar&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Fecha:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon alert-info" >Desde</span>
                                            <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control">
                                            <span class="input-group-addon alert-info" >Hasta</span>
                                            <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Chofer:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" id="chofer" name="chofer" class="form-control input-xs chofer" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-xs" type="button" title="Buscar Chofer" onclick="$('#choferDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                <button class="btn btn-success btn-xs" type="button" title="Limpiar Par&aacute;metros" onclick="$('.chofer').val('');"><span class="glyphicon glyphicon-eject"></span></button>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </form>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div>
                            <table id="Lis_Fac"></table>
                            <div id="Pag_Lis"></div>
                        </div>                        
                        <div style="padding-top: 10px; padding-bottom: 0px;">
                            <button class="btn btn-sm btn-primary" onclick="impriRepClie();/*$('#listClie').jqGrid('printGrid',{nombre:'Ventas',hoja:'Salidas',caption:true});*/" type="button" title="Imprimir    "><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                            <button type="button" onclick="$('#imprimir').append($('#Lis_Fac').jqGrid('exportGridElement', {nombre:'Viajes', hoja:'Viajes', caption:true, footer:true, removeHiddens:true})); $.downloadFile($.exportarExcelBlob($('#imprimir').html(), 'Viajes'), 'viajes_' + $.getDate() + '.xls');" class="btn btn-primary btn-sm start" title="Descargar archivo de Excel"><i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Inicio del diálogo para buscar un cliente -->
        <div id="clienteDialog" title="B&uacute;squeda de Clientes">
            <form class="form-horizontal normal"></form>
        </div>
        <!-- Inicio del diálogo para buscar un chofer -->
        <div id="choferDialog" title="B&uacute;squeda de Choferes">
            <form class="form-horizontal normal"></form>
        </div>
        <script type="text/javascript">
            $(function () {
                //Inicialización
                $.createDatePickers('.datepicker');
                $.createDateRange('#Fec_Ini','#Fec_Fin');
                //$('#Fec_Ini').val('');
                
                //Inicio Grid para presentar el detalle de factura
                $("#Lis_Fac").createGrid({
                    postData: $("#frm_bus").getData("facturasAjax"), height: 295,
                    colModel: [
                        {label: 'Via_Cod', name: 'Via_Cod',key:true,hidden:true},
                        {label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced',width: 50, align: "center",hidden:true},
                        {label: 'Fecha', name: 'Via_Fec', width: 30, align: "center",classes:'bgNoRight'},
                        {label: 'Veh&iacute;culo', name: 'Veh_Pla', width: 30, align: "center",sorttype:'int',summaryType:'count',summaryTpl : 'Total:'},
                        {label: 'Chofer', name: 'chofer', width: 70, align: "center",classes:'bgNoRight'},
                        {label: 'Cargamento', name: 'Car_Des', width: 50, align: "center",classes:'bgNoRight'},
						{label: 'Origen', name: 'Via_Ded', width: 50, align: "center",classes:'bgNoRight'},
						{label: 'Destino', name: 'Via_Has', width: 50, align: "center",classes:'bgNoRight'},
                        {label: 'Cant.', name: 'Via_Can', width: 20, align: "center",sorttype:'number',formatter:'number', summaryType:'sum'},
                        {label: 'P.U', name: 'Via_Pru', width: 20, align: "center",sorttype:'number',formatter:'number', summaryType:'sum'},
                        {label: 'Total', name: 'total', width: 20, align: "center",sorttype:'number',formatter:'number', summaryType:'sum',classes:'bgNoColor'},
                        {label: 'Estado', name: 'estado', width: 40, align: "center"}
                    ],
                    grouping:true,
                    groupingView : {
                        groupField : ['Prs_Ced'],
                        groupColumnShow : [false],
                        groupText : ['<div><span style="float:left;"><b>{0}</b></span></div>','{0}'],
                        groupCollapse : true,
                        groupSummary : [true],
                        groupOrder: ['desc']   		
                    },footerrow: true,userDataOnFooter: false,
                    loadComplete: function(data){ 
                        if($.varValid(data.rows)){
                            var total=0,Via_Can=0,Via_Pru=0;
                            for(var i=0,z=data.rows.length;i<z;i++){
                                if(data.rows[i]['estado'] ==='Sin Facturar') $("#"+data.rows[i].Via_Cod+' td:not(.jqgrid-rownum)').addClass('cellOrange1');
                                Via_Can=Via_Can*1+data.rows[i]['Via_Can']*1;
                                Via_Pru=Via_Pru*1+data.rows[i]['Via_Pru']*1;
                                total=total*1+data.rows[i]['total']*1;
                            }
                        }
                        $('#Lis_Fac').jqGrid('footerData','set',{Veh_Pla: 'TOTALES:',Via_Can:Via_Can,Via_Pru:Via_Pru,total:total});
                    }
                }, false, "#Pag_Lis");
                
                //Inicio del diálogo para presentar clientes
                $.createSearchDialog('#clienteDialog', [
                    {label: 'Cód.Int.', name: 'Cli_Cod', key: true, hidden: true},
                    {label: 'C&eacute;dula', name: 'Prs_Ced', width: 30},
                    {label: 'Cliente(s)', name: 'cliente', width: 70},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            $.extend(rowObject,{dialog:'cliente',frm:'frm_bus'});return $.getGridButton(pasarDatos, rowObject);
                        }
                    }
                ], null, null, null, null, {title: 'Clientes', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},
                {label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});
                
                //Inicio del diálogo para presentar choferes
                $.createSearchDialog('#choferDialog', [
                    {label: 'Cód.Int.', name: 'Cho_Cod', key: true, hidden: true},
                    {label: 'C&eacute;dula', name: 'Prs_Ced1', width: 30},
                    {label: 'Cliente(s)', name: 'chofer', width: 70},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            $.extend(rowObject,{dialog:'chofer',frm:'frm_bus'});return $.getGridButton(pasarDatos, rowObject);
                        }
                    }
                ], null, null, null, null, {title: 'Choferes', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},
                {label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});
                
                
            });
            
            /*** FUNCIONES PARA EL MANEJO DE DATOS ***/
            //Función para pasar datos seleccionados
            function pasarDatos(objeto){
                $('#'+objeto['dialog']+'Dialog').dialog('close');
                $('#'+objeto['frm']).setData(objeto,false);
            }
            
            function impriRepClie(){
                $('#tablaReporte').html($("#Lis_Fac").jqGrid('exportGridInnerHTML',{generated:false,caption:false,bodyBorder:false,footer:true,removeHiddens:true,removeCols:['9']}));
                imprimirReporte($("#Lis_Fac").getCaption());
            }  
            
            function imprimirReporte(tittle){
                $('#titleReporte').html(tittle+' Desde '+ $("#Fec_Ini").val() +' Hasta '+$("#Fec_Fin").val());				
                $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'iframe',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
            };
        </script>
        <div id="imprimir" style="display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE VIAJES', NULL, $obBD_conexion, false, 10); ?>
        </div>
        <div id="formatoReporte" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE VIAJES', '<span id="titleReporte">&nbsp;</span>',$obBD_conexion,true,NULL,NULL,NULL,'19px'); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>
        
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script> 
        <script>$.clearValidate();</script>
    </BODY>
</HTML>



