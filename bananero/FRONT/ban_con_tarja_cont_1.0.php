<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_tarja.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Tarja();

$hoy = date("Y-m-d");

if(isset($provAjax)){
    $page=$obBD_con1->getPageGridJson('productor_bana.selectWhere', $_GET, $obBD_conexion);
}
if(isset($searchNaves)){
    $pages=$obBD_con1->getPageGrid('naviera_container.selectWhere', array_merge(array('setWhere'=>array('setVapor','setCliente','isActive')),$_GET), $obBD_conexion);
    foreach($pages['rows'] as &$v) {
        $v['Tarjas']=$obBD_con1->getArrayConsulta('productor_tarja.selectWhere', array('where'=>array('Nco_Cod'=>$v['Nco_Cod']),'setWhere'=>array('setProductor','isActive')), $obBD_conexion);
    } unset($v);
    $obBD_con1->echoJson($pages);
}
if(isset($searchNavesDetalle)){
    $pages=$obBD_con1->getPageGrid('naviera_container.selectWhere', array_merge(array('setWhere'=>array('setVapor','setPlanif','isActive')),$_GET), $obBD_conexion);
    if(is_array($pages['rows']))
    foreach($pages['rows'] as &$v) {
        $tarjas=$obBD_con1->getArrayConsulta('productor_tarja.sql.totalesByContainer', array('Nco_Cod'=>$v['Nco_Cod'],'Ptr_Est'=>'A'), $obBD_conexion);
        $v['Total']=0;
        if(is_array($tarjas))
        foreach($tarjas as $w) {
            $v[$w['Prt_Tip']]=$w['total'];
            $v['Total']+=($w['total']*1);
        }
    } unset($v);
    $obBD_con1->echoJson($pages);
}
if(isset($getTarjas)){
    $obBD_con1->getPageGridJson('productor_tarja.selectWhere', array_merge(array('setWhere'=>array('setProductor','isActive')),$_GET), $obBD_conexion);
}
$marcas=$obBD_con1->getArrayConsulta('banano_marca.selectWhere',  array('setWhere'=>array('setEmpCod','isActive')), $obBD_conexion);
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$cur_periodo=current($periodos);
$tipos=$obBD_con1->getTiposCaja();
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var tiposCaja=<?php echo json_encode($tipos); ?>;
    </script>
    <script type="text/javascript" src="../VALIDACIONES/ban_val_tarja.js"></script>
    <style>

    </style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Tarjas por Contenedor</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="tabsSearch" class="ui-tab-fix ui-tabs noPaddingH">
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                  <li><a href="#tabs-1">Por Tipo Tarja</a></li>
                  <li><a href="#tabs-2">Por Contenedores</a></li>
                </ul>
                <div id="tabs-1" class="ui-tabs-panel ui-widget-content">
                    <div class="row">
                        <div class="col-xs-12">
                        <fieldset class="exa-fieldset" id="provFormTemp">
                        <legend class="Titulos2">Consulta de Información</legend>
                        <div class="col-xs-4">
                            <script>
                                function searchMain(){
                                    navesContDetalle.Search('#formDocumentoSearch','searchNavesDetalle');
                                    var semana=$.semanas($('#Vap_Ano').val(),$('#Prt_Sem2').val());
                                    navesContDetalle.setCaption($('#Prt_Sem2 option:selected').text()+' - Desde '+semana['Fei']+" Hasta "+semana['Fef']);
                                }
                            </script>
                            <form id="formDocumentoSearch" class="form-horizontal normal formDatos" action="javascript:if($('#Vap_Ano').val()===''||$('#Prt_Sem2').val()==='')  $.alert('Debe seleccionar <u class=\'green\'>PERIODO</u> y <u class=\'green\'>SEMANA</u>'); else searchMain()">
                                <input name="order" type="hidden" value="Nav_Nom,Vap_Nom,Vap_Via,Pln_Bld,Nco_Nom" />


                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                        <div class="col-xs-7" >
                                            <select  id="Vap_Ano" name="where[Vap_Ano]" class="form-control input-xs" >
                                                <option value="">Periodo..</option>
                                                <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Semana:</label>
                                        <div class="col-xs-9" ><select id="Prt_Sem2" name="where[Vap_Sem]" class="form-control input-xs" ></select></div>
                                    </div>


                            </form>
                        </div>
                        <div class="col-xs-2">
                            <div class="form-group">
                                <div class="center"><button type="button" onclick="$('#formDocumentoSearch').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button></div>
                            </div>
                        </div>
                        </fieldset>
                        </div>
                        <div class="col-xs-12">
                            <div>
                                <table id="navesContDetalle"></table>
                                <div id="navesContDetallePager"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tabs-2" class="ui-tabs-panel ui-widget-content">
                    <div class="row">
                        <div class="col-xs-5">
                            <script>
                                function searchDeatalle(){
                                    navesCont.Search('#formDocumento','searchNaves');
                                    var semana=$.semanas($('#Lib_Ano').val(),$('#Prt_Sem').val());
                                    navesCont.setCaption($('#Prt_Sem option:selected').text()+' - Desde '+semana['Fei']+" Hasta "+semana['Fef']);
                                }
                            </script>
                            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:if($('#Lib_Ano').val()===''||$('#Prt_Sem').val()==='')  $.alert('Debe seleccionar <u class=\'green\'>PERIODO</u> y <u class=\'green\'>SEMANA</u>'); else searchDeatalle(); ">
                                <input name="order" type="hidden" value="Nav_Nom,Vap_Nom,Vap_Via,Nco_Nom" />
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Consulta de Información</legend>


                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                        <div class="col-xs-7" >
                                            <select  id="Lib_Ano" name="where[Vap_Ano]" class="form-control input-xs" >
                                                <option value="">Periodo..</option>
                                                <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                            </select>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Semana:</label>
                                        <div class="col-xs-9" ><select id="Prt_Sem" name="where[Vap_Sem]" class="form-control input-xs" ></select></div>
                                    </div>
                                    <div class="form-group">
                                        <div class="center">
                                        <button type="button" onclick="$('#formDocumento').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                                        </div>
                                    </div>



                                </fieldset>

                            </form>
                            <div>
                                <table id="navesCont"></table>
                                <div id="navesContPager"></div>
                            </div>
                            <div class="help-block"></div>
                            <div class="center">
                                <button type="button" onclick="printGrupal();" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-print"></i> Imprimir Contenedores Semana</i></button>
                            </div>
                        </div>
                        <div class="col-xs-7">
                            <fieldset class="exa-fieldset">
                            <legend class="exa-fieldset Titulos2">Datos del Contenedor</legend>
                            <form id='formNave' class="form-horizontal normal">
                                <span name="Nco_Cod" class="form-control input-xs databind hidden"></span>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Fecha:</label>
                                    <div class="col-xs-3" ><span name="Vap_Cof" class="form-control input-xs datatitle"></span></div>
                                    <label class="col-xs-2 control-label label-xs">Naviera/Age.:</label>
                                    <div class="col-xs-5" ><span name="Nav_Nom" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Nave:</label>
                                    <div class="col-xs-3" ><span name="Vap_Nom" class="form-control input-xs datatitle"></span></div>
                                    <label class="col-xs-2 control-label label-xs">Viaje:</label>
                                    <div class="col-xs-2" ><span name="Vap_Via" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Ruc:</label>
                                    <div class="col-xs-3" ><span name="Ruc" class="form-control input-xs datatitle"></span></div>
                                    <label class="col-xs-2 control-label label-xs">Cliente:</label>
                                    <div class="col-xs-5" ><span name="Cliente" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Semana:</label>
                                    <div class="col-xs-2" ><span name="Vap_Sem" class="form-control input-xs datatitle"></span></div>
                                    <label class="col-xs-2 control-label label-xs">Sellos:</label>
                                    <div class="col-xs-6" ><span name="Nco_Sel" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Termog.:</label>
                                    <div class="col-xs-3" ><span name="Nco_Ter" class="form-control input-xs datatitle"></span></div>
                                    <label class="col-xs-2 control-label label-xs">Marca:</label>
                                    <div class="col-xs-5" ><span name="Bam_Nom" class="form-control input-xs datatitle"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Chofer:</label>
                                    <div class="col-xs-10" >
                                        <div class="input-group input-group-xs">
                                            <span name="Nco_Cho" class="form-control input-xs datatitle"></span>
                                            <span class="input-group-addon labelBg bold">CI:</span>
                                            <span name="Nco_Cch" class="form-control input-xs datatitle"></span>
                                            <span class="input-group-addon bold labelBg">Placa:</span>
                                            <span name="Nco_Pla" class="form-control input-xs datatitle"></span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            </fieldset>
                            <div class="jqFirst jqHeaderFirst">
                                <table id="tarjaProd"></table>
                                <div id="tarjaProdPager"></div>
                            </div>
                            <div class="help-block"></div>
                            <div class="center">
                                <button type="button" id="btnIndv" onclick="printIndiv($(this).data('id'));" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-print"></i> Imprimir Contenedor</i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <script type="text/javascript">

    function imprimirLiquida(legal, Lib_Cod){
        //$.imprimirUrl("<?php //echo $linkLiqui; ?>"+Lib_Cod+(legal?"":"&detallado="));
    }

    </script>
    <script type="text/javascript">
        var navesCont, tarjaProd, navesContDetalle;
        var prevCellVal = { cellId: undefined, value: undefined };
        $(document).ready(function () {
            navesCont=$("#navesCont");
            navesContDetalle=$("#navesContDetalle");
            if(navesCont.length>0){
                $( "#tabsSearch" ).createTabs();
                tarjaProd=$("#tarjaProd");
                tarjaProd.createGrid({
                    height: '125', caption: 'Contenedor:',
                    colModel: [
                        { label: 'ID', name: 'Prt_Cod', key: true, width: 75, hidden:true },
                        { label: 'Productor', name: 'Productor', width: 100, classes:'bgNoRight bgNoColor' },
                        { label: 'Entrega', name: 'Entrega', width: 50, align:'right', formatter:'union', formatoptions:{conditional:function(o){ return (o.Prt_Car)*1+(o.Prt_Cah)*1; }}, classes:'bgNoRight bgNoColor' },
                        { label: 'Merma', name: 'Prt_Cah', width: 50, align:'right', classes:'bgNoColor'},
                        { label: 'Ingreso', name: 'Prt_Car', width: 50, align:'right', classes:'columnHighlight3'},
                        { label: 'Corte', name: 'Prt_Tip', width: 50, align:'center', classes:'bgNoRight bgNoColor', formatter:'title', formatoptions:{title:function(o){ return getTipoCaja(o.Prt_Tip); } } },
                        { label: 'Cod.', name: 'Prt_Nqc', width: 25, align: "center", classes:'bgNoColor', formatter:'truefalse', formatoptions:{ noText:false, yesMsg:function(o){ return o.Prd_Cau; }, noMsg:' ', yesIcon:'barcode', noIcon:' ', yesColor:' ' }, title:false },
                        { label: 'Eval.', name: 'Prt_Eva', width: 25, align: "center", classes:'bgNoRight bgNoColor', formatter:'truefalse', formatoptions:{ noText:false, yesMsg:function(o){ return $.createIcon("user green")+" <b class=\"blue\">"+o.Prt_Eva+"</b>"; }, noMsg:' ', yesIcon:'user green', noIcon:' ', yesColor:'blue' }, title:false },
                        { label: 'Obs.', name: 'Prt_Obs', width: 25, align: "center", classes:'bgNoColor', formatter:'truefalse', formatoptions:{ noText:false, yesMsg:function(o){ return o.Prt_Obs; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue' }, title:false }
                    ],
                    footerrow:true, totalCols:['Prt_Car'],totalDefault:{Prt_Cah:$.fieldSummary()}, loadComplete: setDetalle
                },true/*,"#tarjaProdPager"*/);

                navesCont.createGrid({
                    height: 250, datatype:'local', caption: 'Nave/Contenedor',
                    colModel: [
                        { label: 'ID', name: 'Nco_Cod', key: true, width: 75, hidden:true },
                        { label: 'Naviera', name: 'Nav_Nom', width: 150, summaryType:$.fieldHeader, hidden:true },
                        { label: 'Nave', name: 'Vap_Nom', width: 150, summaryType:$.fieldHeader, hidden:true},
                        { label: 'Viaje', name: 'Vap_Via', width: 150, hidden:true },
                        { label: 'Descrip.', name: 'Nco_Nom', width: 150 },
                        { label: 'Sellos', name: 'Nco_Sel', width: 150, formatter:'tags',formatoptions:{type:'warning'}},
                        { label: 'Termografo', name: 'Nco_Ter', width: 150 },
//                        { label: '&nbsp;', name: 'info', width: 35, formatter:'gridButton', formatoptions:{ data:'Nco_Cod', action:'setContainer', icon:'info-sign', type:'info' } },
                        { label: '&nbsp;', name: 'show', width: 35, formatter:'gridButton', formatoptions:{ data:'Nco_Cod', action:'setContainer' } },
                        $.originalRow()
                    ],
                    onSelectRow: function(rowid, selected) {
                        if(rowid !== null && selected) {
                            setContainer(rowid);
                        }
                    }, // use the onSelectRow that is triggered on row click to show a details grid
                    loadComplete: clearSelection,
                    onSortCol : clearSelection,
                    onPaging : clearSelection,
                    groupingView : {
                        groupField : ['Vap_Via'], groupColumnShow : [false], groupText:["<div class='txtLeft'>{Nav_Nom} - <b><u>{Vap_Nom}</u></b> <i>[{0}]</i></div>"]
                    }, grouping:true
                },false,"#navesContPager");

                var cols=[],colsName=['Total'];
                $.each(tiposCaja, function(i,v){
                    cols.push({ label: v['value'], name: v['value'], width: 52, align:'right', summaryType:'sum' });
                    colsName.push(v['value']);
                });
                cols.push({ label: 'Total', name: 'Total', width: 52, align:'right', summaryType:'sum' });
                navesContDetalle.createGrid({
                    height: 250, datatype:'local', caption: 'Nave/Contenedor',
                    footerrow:true, totalCols:colsName,totalDefault:{Nco_Ter:$.fieldSummary({msg:'TOTAL GENERAL'})} ,
                    colModel: [
                        { label: 'ID', name: 'Nco_Cod', key: true, width: 75, hidden:true },
                        { label: 'Naviera', name: 'Nav_Nom', width: 50, summaryType:$.fieldHeader, hidden:true },
                        { label: 'Nave', name: 'Vap_Nom', width: 50, summaryType:$.fieldHeader, hidden:true},
                        { label: 'Viaje', name: 'Vap_Via', width: 50, hidden:true },
                        { label: 'Día.', name: 'Nco_Dia', width: 60, align:'center' },
                        { label: 'Descrip.', name: 'Nco_Nom', width: 75, classes:'bgNoRight bgNoColor bgSumNoRight bgSumNoColor'},
                        { label: 'Sellos', name: 'Nco_Sel', width: 150, formatter:'tags',formatoptions:{type:'warning'}, classes:'bgNoRight bgNoColor bgSumNoRight bgSumNoColor'},
                        { label: 'Termografo', name: 'Nco_Ter', width: 75, summaryType:$.fieldSummary, classes:'bgNoColor bgSumNoColor' }
                    ].concat(cols).concat([
                        { label: 'Bl', name: 'Pln_Bld', width: 75, classes:'bgNoColor bgSumNoColor', rowspanId:'Pde_Cod', cellattr: $.jqRowspan, align:'center' }
                    ]),
                    groupingView : {
                        groupField : ['Vap_Via'], groupColumnShow : [false], groupText:["<div class='txtLeft'>{Nav_Nom} - <b><u>{Vap_Nom}</u></b> <i>[{0}]</i></div>"],groupSummary: [true]
                    }, grouping:true
                },false,"#navesContDetallePager").gridButtonsAdd([null,
                    {buttonicon:'print', caption:' Imprimir',onClickButton:function(){ printR('#navesContDetalle'); }},
                    {buttonicon:'download-alt',caption:' Descargar',onClickButton:function(){ exportR('#navesContDetalle'); }}
                ]);;

                $('#detalleDialog').createDialogDetail({
                    caption: 'Totales', width:300, height: '175',
                    footerrow:true, totalCols:['Prt_Car'], totalDefault:{Prt_Tip:$.fieldSummary()},
                    colModel: [
                        { label: 'ID', name: 'index', key: true, width: 75, hidden:true },
                        { label: 'Corte', name: 'Prt_Tip', width: 150, classes:'bgNoColor' },
                        { label: 'Cantidad', name: 'Prt_Car', width: 75, align:'right' }
                    ]
                },{icon:'eye-open'});
                setSemanas2();
            }


        });
        function setContainer(rowid) {
            $("#btnIndv").data('id',rowid);
            var dato=navesCont.getCell(rowid,'OriginalData');
            $('#formNave').setData(dato);
            tarjaProd.jqGrid('setCaption', 'Contenedor: '+dato.Nco_Nom+"<div class='pull-right' style='margin-top: -3px;'>"+$.getGridButton('showDetalle',rowid,'Ver Resumen','eye-open',null,'info')+'</div>');
            tarjaProd.setRows(dato.Tarjas);
            //tarjaProd.Search({where:{Nco_Cod:dato.Nco_Cod}, getTarjas:true});
        }
        function clearSelection() {
            $('#formNave').setData({});
            if(tarjaProd!==undefined && tarjaProd.length>0){
                tarjaProd.jqGrid('setCaption', 'Contenedor: ');
                tarjaProd.clearGrid();
                $('#detalleDialog').getDialogGrid().clearGrid();
            }
        }
        function setDetalle(){
            var lista=[];
            var datos=tarjaProd.getGridBatch();
            $.each(datos,function(i,v){
                var add=true;
                $.each(lista, function(j,w){
                    if(v['Prt_Tip']===w['Prt_Tipo']){
                        lista[j]['Prt_Car']+=(v['Prt_Car']*1);
                        add=false;
                        return add;
                    }
                });
                if(add){
                    v['Prt_Car']=v['Prt_Car'].toNum();
                    v['Prt_Tipo']=v['Prt_Tip'];
                    v['Prt_Tip']=getTipoCaja(v['Prt_Tip']);
                    lista.push(v);
                }
            }); // console.log(lista);
            $('#detalleDialog').getDialogGrid().setRowsByIndex(lista);
        }
        function showDetalle(){ $('#detalleDialog').dialog('open'); }
        function getTipoCaja(Tip){
            var tipo='';
            $.each(tiposCaja,function(j,w){
                if(w['value']===Tip) tipo=w['label'];
            });
            return tipo===""?Tip:tipo;
        }


    </script>
    <script>
        function printGrupal(){
            var rows=navesCont.getGridBatch(), html='';
            if(rows.length===0) return $.alert('No hay contenedores para imprimir!');
            console.log(rows);
            $('#loader').show();
            $('#titleReporte').html('');
            $.each(rows,function(i,v){
                tarjaProd.setRows(v.OriginalData.Tarjas);
                $('#tablaDatos').setData(v.OriginalData);
                $('#tablaReporteExtra').html($('#tablaDatos').html());
                $('#tablaReporte').html(tarjaProd.jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}))
                    .append('<tbody><tr><td>&nbsp;</td></tr></tbody>')
                    .append($('#detalleDialog').getDialogGrid().jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));
                html+=($('#tablaReporteExtra').prop('outerHTML')+$('#tablaReporte').prop('outerHTML')+"<br/><br/>");
            });
            $('#reporteInner').html(html);
            $('#loader').hide();
            $('#formatoReporteEmpty').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
        }
        function printIndiv(rowid) {
            if(!$.vv(rowid)) return $.alert('Seleccione un Contenedor!');
            var dato=navesCont.getCell(rowid,'OriginalData');
            //console.log(dato);
            $('#tablaDatos').setData(dato);
            $('#titleReporte').html('');
            $('#tablaReporteExtra').html($('#tablaDatos').html());
            $('#tablaReporte').html(tarjaProd.jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}))
                .append('<tbody><tr><td>&nbsp;</td></tr></tbody>')
                .append($('#detalleDialog').getDialogGrid().jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));

            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
        }
        function printR(grid) {
            $('#tablaReporteExtra').html('');
            $('#titleReporte').html($(grid).getCaption());
            $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false, removeHiddens:true, removeCols:[2]}));
            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
        }
        function exportR(grid) {
            var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
            temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true,removeHiddens:true}));
            $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'contenedores_'+$.getDate()+'.xls');
        }
    </script>
    <table id="tablaDatos" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed; display:none;" >
        <tbody>
            <tr>
                <td width="8%"></td><td width="25.32%"></td>
                <td width="8%"></td><td width="25.32%"></td>
                <td width="8%"></td><td width="25.32%"></td>
            </tr>
            <tr><td colspan="6" align="center"><b>CONTENEDOR:</b> <span name="Nco_Nom" class="form-control" style="font-size:13px;"></span></td></tr>
            <tr>
                <td><b>FECHA:</b></td><td><span name="Nco_Dia" class="form-control"></span></td>
                <td colspan="4" align="right"><b>SEMANA <span name="Vap_Sem" class="form-control"></span></b>  </td>
            </tr>
            <!--<tr><td><b>CLIENTE:</b></td><td colspan="5"><span name="Ruc" class="form-control"></span> - <span name="Cliente" class="form-control"></span></td></tr>-->
            <tr><td colspan="6">&nbsp;</td></tr>
            <tr>
                <td><b>NAVIERA:</b></td><td><span name="Nav_Nom" class="form-control"></span></td>
                <td><b>BOOKING:</b></td><td><span name="Nco_Bkg" class="form-control"></span></td>
                <td><b>CHOFER:</b></td><td><span name="Nco_Cho" class="form-control"></span></td>
            </tr>
            <tr>
                <td><b>NAVE:</b></td><td><span name="Vap_Nom" class="form-control"></span></td>
                <td><b>SELLOS:</b></td><td><span name="Nco_Sel" class="form-control"></span></td>
                <td><b>C.I.:</b></td><td><span name="Nco_Cch" class="form-control"></span></td>
            </tr>
            <tr>
                <td><b>VIAJE:</b></td><td><span name="Vap_Via" class="form-control"></span></td>
                <td><b>TERMOGR.:</b></td><td><span name="Nco_Ter" class="form-control"></span></td>
                <td><b>Placa:</b></td><td><span name="Nco_Pla" class="form-control"></span></td>
            </tr>
            <tr>
                <td><b>ACOPIO:</b></td><td><span name="Nco_Bod" class="form-control"></span>/<span name="Nco_Con" class="form-control"></span></td>
                <td><b>MARCA.:</b></td><td colspan="3"><span name="Bam_Nom" class="form-control"></span></td>
            </tr>
        </tbody>
    </table>
    <div id="formatoReporteEmpty" style="display: none;">
      <div style="width: 1030px;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CONTENEDORES', '<span id="titleReporte2"></span>',$obBD_conexion); ?>
        <div id="reporteInner"></div>
        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
      </div>
    </div>
    <div id="formatoReporte" style="display: none;">
      <div style="width: 1030px;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CONTENEDORES', '<span id="titleReporte"></span>',$obBD_conexion); ?>
        <table id="tablaReporteExtra" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed; font-size: 11px;" width="100%"></table>
        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
      </div>
    </div>
    <div id="formatoExportar" style="width: 700px; display: none;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span class="title_grid"></span>',$obBD_conexion,false,9); ?>
    </div>

    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <div id='detalleDialog'></div>
</BODY>
</HTML>



