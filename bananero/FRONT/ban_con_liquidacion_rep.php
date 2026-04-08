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
$obBD_con1->setConnection($obBD_conexion);

$hoy = date("Y-m-d");

if(isset($provAjax)){
    $page=$obBD_con1->getPageGridJson('productor_bana', $_GET);
}
//if(isset($searchLiquid)){
//    $obBD_con1->getPageGridJson('liquidacion_bana.selectWhere', array_merge(array('where'=>array('Lib_Est'=>'A'),'order'=>'Prs_Ced','setWhere'=>array(/*'setProductor'*/,'setTotales'),'group'=>'persona.Prs_Ced','order'=>'Lib_Ano DESC, Lib_Sem DESC, Productor'),$_GET),true);
//}
if(isset($searchLiquid)){
    $obBD_con1->getPageGridJson('liquidacion_bana', array_merge(array('where'=>array('Lib_Est'=>'A'),'setWhere'=>array(/*'setProductor',*/'setTotales'),'group'=>'liquidacion_bana.Lib_Cod','order'=>'Lib_Ano DESC, Lib_Sem DESC, Productor'),$_GET) );
}
if(isset($searchLiquidDet)){
    $r=$obBD_con1->getPageGrid('liquidacion_bana', array_merge(array('where'=>array('Lib_Est'=>'A'),'setWhere'=>array(/*'setProductor',*/'setTotales'),'group'=>'liquidacion_bana.Lib_Cod','order'=>'Lib_Ano DESC, Lib_Sem DESC, Productor'),$_GET) );
    foreach($r['rows'] as &$v){
        $v['Detalle']=$obBD_con1->getArrayConsulta('liquidacion_bana_det', array('where'=>array('liquidacion_bana_det.Lib_Cod'=>$v['Lib_Cod'],'Lid_Can>0')) );
    } unset($v);
    $obBD_con1->echoJson($r);
}

$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'));
$cur_periodo=current($periodos);
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript"></script>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Reporte Liquidaciones</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="tabsSearch" class="ui-tab-fix ui-tabs noPaddingH">
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                  <li><a href="#byProductor">Por Proveedor</a></li>
                  <li><a href="#byDetalle">Por Detalle</a></li>
                </ul>
                <div id="byDetalle" class="ui-tabs-panel ui-widget-content" data-table="liquidacionesDet">
                    <div class="row">
                        <div class="col-xs-12">
                            <form id="formDocumentoDet" class="form-horizontal normal formDatos" action="javascript:searchDet();" >
                                
                            </form>
                        </div>
                        <div class="col-xs-12">
                            <div style="min-height: 280px">
                                <table id="liquidacionesDet"></table>
                                <div id="liquidacionesDetPager"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="byProductor" class="ui-tabs-panel ui-widget-content" data-table="liquidaciones">
                    <div class="row">
                        <div class="col-xs-12">
                            <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:search();">
                                <!--<input name="order" type="hidden" value="" />-->
                                <fieldset class="exa-fieldset" id="provFormTemp">
                                    <legend class="Titulos2">Consulta de Información</legend>
                                <div class="col-sm-4">

                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                        <div class="col-xs-7" >
                                            <select id="Lib_Ano" name="Lib_Ano" class="form-control input-xs" >
                                                <option value="">Periodo..</option>
                                                <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                            </select>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Semana:</label>
                                        <div class="col-xs-9" ><select name="Lib_Sem" class="form-control input-xs semanaSelect" ></select></div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>
                                        <div class="col-xs-9" >
                                          <input name="Prd_Cod" data-name="Prd_Cod" type="text" style="display:none;" />
                                          <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                          <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                          <div class="input-group input-group-xs">
                                            <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Productor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                            <span class="input-group-btn">
                                                 <button id="Prv_Btn" type="button" onclick="selectProvee({})" class="btn btn-success btn-xs" title="Buscar Productor" ><span class="glyphicon glyphicon-eject"></span></button>
                                                <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor" ><span class="glyphicon glyphicon-search"></span></button>
                                                <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->
                                            </span>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Productor:</label>
                                        <div class="col-xs-9" >
                                            <span name="Productor" data-name="Productor" class="form-control input-xs databind datatitle"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="center">
                                        <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                                    </div>
                                </div>
                                </fieldset>

                            </form>
                        </div>
                        <div class="col-xs-12">
                            <div style="min-height: 280px">
                                <table id="liquidaciones"></table>
                                <div id="liquidacionesPager"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script type="text/javascript">
    var liquidaciones,liquidacionesDet;
    $(document).ready(function(){
        $('#tabsSearch').find('>.ui-tabs-panel').initDivs({
            byProductor:function(){
                liquidaciones=$('#liquidaciones');
                liquidaciones.createGrid({
                    caption:'Liquidaciones',
                    height: 250, datatype: "local", selectGridRows:true, bindKeys:false,
                    footerrow:true,totalCols:['Lib_Caj','Ingresos','Descuentos','Total'],totalDefault:{Bam_Nom:'<div class="txtRight">TOTALES:<div>'},
                    colModel: [
                        { label: 'Cód. Int.', name: 'Lib_Cod', width: 15 ,align:"center", key:true, hidden:true},
                        { label: 'Cód. Int.', name: 'Com_Cod', width: 15 ,align:"center", hidden:true},
                        { label: 'Periodo', name: 'Lib_Ano', width: 30, align:"center", summaryType:$.fieldHeader, classes:'bgNoRight bgNoColor', hidden:true},
                        { label: 'Semana', name: 'Lib_Sem', width: 30, align:"center", classes:'bgNoRight bgNoColor', hidden:true},
                        { label: 'Num.', name: 'Lib_Num', width: 30,align:"center", classes:'bgNoRight bgNoColor', formatter:function(cv,opt,obj){ return (obj['Lib_Mag']==='N'?'F':'')+cv; } },
                        { label: 'Fecha', name: 'Lib_Fec', width: 50, align:"center", classes:'bgNoRight bgNoColor'},
                        { label: 'Marca', name: 'Bam_Nom', width: 50, classes:'bgNoColor', summaryType:$.fieldSummarys},
                        { label: 'Ruc', name: 'Prs_Ced', width: 75, classes:'bgNoRight bgNoColor', hidden:true},
                        { label: 'Productor', name: 'Productor', width: 125, classes:'bgNoColor',summaryType:$.fieldHeader, hidden:true},
                        { label: 'Liquidadas', name: 'Lib_Caj', width: 35, classes:'columnHighlight10', align:"right", summaryType:'sum' },
                        { label: 'Precio', name: 'Lib_Pru', width: 25, classes:'', align:"right", formatter:'number' },
                        { label: 'Ingresos', name: 'Ingresos', width: 50, classes:'columnHighlight2', align:"right", formatter:'currency', summaryType:'sum' },
                        { label: 'Descuentos', name: 'Descuentos', width: 50, classes:'columnHighlight2', align:"right", formatter:'currency', summaryType:'sum' },
                        { label: 'Total', name: 'Total', width: 50, classes:'columnHighlight2', align:"right", formatter:'currency', summaryType:'sum' }
                    ], grouping:true, groupingView : {
                        groupField:['Lib_Sem','Prs_Ced'],
                        groupColumnShow:[false,false],
                        groupText:["<div class='txtLeft'><b>Año</b> {Lib_Ano} - <b>Semana</b> {0}</div>","<div class='txtLeft'><i>{0}</i> - <b><u>{Productor}</u></b> </div>"],
                        groupSummary: [false,true]
                    }
                },false,'#liquidacionesPager').gridButtonsAdd([null,
                    {buttonicon:'print', caption:' Imprimir',onClickButton:function(){ printR('#liquidaciones'); }},
                    {buttonicon:'download-alt',caption:' Descargar',onClickButton:function(){ exportR('#liquidaciones'); }}
                ]);
            }, byDetalle:function(){
                liquidacionesDet=$('#liquidacionesDet');
                liquidacionesDet.createGrid({
                    caption:'Liquidaciones',
                    height: 250, datatype: "local", selectGridRows:true, bindKeys:false,
                    footerrow:true,totalCols:['Lib_Caj','Ingresos','Descuentos','Total'],totalDefault:{Bam_Nom:'<div class="txtRight">TOTALES:<div>'},
                    colModel: [
                        { label: 'Cód. Int.', name: 'Lib_Cod', width: 15 ,align:"center", key:true, hidden:true},
                        { label: 'Cód. Int.', name: 'Com_Cod', width: 15 ,align:"center", hidden:true},
                        { label: 'Periodo', name: 'Lib_Ano', width: 20, align:"center", summaryType:$.fieldHeader, classes:'bgNoRight bgNoColor', hidden:true},
                        { label: 'Semana', name: 'Lib_Sem', width: 20, align:"center", classes:'bgNoRight bgNoColor', hidden:true},
                        { label: 'Num.', name: 'Lib_Num', width: 25,align:"center", classes:'bgNoRight bgNoColor', formatter:function(cv,opt,obj){ return (obj['Lib_Mag']==='N'?'F':'')+cv; } },
                        { label: 'Fecha', name: 'Lib_Fec', width: 30, align:"center", classes:'bgNoRight bgNoColor'},
                        { label: 'Marca', name: 'Bam_Nom', width: 50, classes:'bgNoColor', summaryType:$.fieldSummarys},
                        { label: 'Ruc', name: 'Prs_Ced', width: 50, classes:'bgNoRight bgNoColor', hidden:true},
                        { label: 'Productor', name: 'Productor', width: 125, classes:'bgNoColor',summaryType:$.fieldHeader, hidden:true},
                        { label: 'Liquidadas', name: 'Lib_Caj', width: 25, classes:'columnHighlight10', align:"right", summaryType:'sum' },
                        { label: 'Precio', name: 'Lib_Pru', width: 20, classes:'', align:"right", formatter:'number' },
                        { label: 'Ingresos', name: 'IngDetalle', width: 55, formatter:'detalleLiquidacion', formatoptions:{type:'I'}, cellattr:function(){ return 'style="white-space:normal;vertical-align:top;"'; }, title:'false' },
                        { label: 'Descuentos', name: 'EgreDetalle', width: 55, formatter:'detalleLiquidacion', formatoptions:{type:'D'}, cellattr:function(){ return 'style="white-space:normal;vertical-align:top;"'; }, title:'false' },
                        { label: 'Tot. Ingresos', name: 'Ingresos', width: 35, classes:'columnHighlight2', align:"right", formatter:'currency', summaryType:'sum' },
                        { label: 'Tot. Descuentos', name: 'Descuentos', width: 35, classes:'columnHighlight2', align:"right", formatter:'currency', summaryType:'sum' },
                        { label: 'Total', name: 'Total', width: 40, classes:'columnHighlight2', align:"right", formatter:'currency', summaryType:'sum' }
                    ], grouping:true, groupingView : {
                        groupField:['Lib_Sem','Prs_Ced'],
                        groupColumnShow:[false,false],
                        groupText:["<div class='txtLeft'><b>Año</b> {Lib_Ano} - <b>Semana</b> {0}</div>","<div class='txtLeft'><i>{0}</i> - <b><u>{Productor}</u></b> </div>"],
                        groupSummary: [false,true]
                    }
                },false,'#liquidacionesDetPager').gridButtonsAdd([null,
                    {buttonicon:'print', caption:' Imprimir',onClickButton:function(){ printR('#liquidacionesDet'); }},
                    {buttonicon:'download-alt',caption:' Descargar',onClickButton:function(){ exportR('#liquidacionesDet'); }}
                ]);
            }
        });
        $("#tabsSearch").createTabs({beforeActivate:function(e,u){            
            $("#provFormTemp").detach().appendTo(u.newPanel.find('>div:first-child>div:first-child>form:first-child'));
            u.newPanel.find('#'+u.newPanel.data('table')).clearGrid();            
        }});
        $('.semanaSelect').fillSelect(function(){
            var s=[]; for(var i=1;i<=52;i++) s.push({value:i,label:i.ordinal(true,'sup')+" Semana "}); return s;
        }());
        if($('#provDialog').length>0)
        $('#provDialog').createSearchDialog({colModel:[
            { label: 'C&oacute;d.Int.', name: 'Prd_Cod', key: true, width: 15,align:"center",hidden:true },
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', width: 15,align:"center",hidden:true },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Productor', name: 'Productor', width: 100},
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'selectProvee'} }
        ]},{ title:'Productor' });
    });
    $.fn.fmatter.detalleLiquidacion=function(cv,opts,o){ var html="",f=$.ocf(opts),t=f['type']; if($.isArray(o.Detalle)) $.each(o.Detalle, function(){ if(this.Lid_Tip===t) html+="<span style='display:block;width:100%'>"+this.Lid_Des+" <b class='pull-right'>["+$.numFormat(t==='D'&&this.Lid_Grp.toNum()===-1?this.Lid_Can*this.Lib_Pru*this.Lid_Pru/100:this.Lid_Can*this.Lid_Pru,'number')+"]</b></span><span class='hidden'>\n</span>"; }); return html; };
    function search(){
        liquidaciones.Search('#formDocumento','searchLiquid');
    }
    function searchDet(){
        liquidacionesDet.Search('#formDocumentoDet','searchLiquidDet');
    }
    function selectProvee(provee){
        $('#provFormTemp').setData($.extend(provee,{op_opciones:'c'}),'name').find('.dialogSearch').addClass('x');
        $('#provDialog').dialog('close');
    }
    </script>
    <script type="text/javascript"></script>
    <script>
	function printR(grid) {
            $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));
            $('#titleReporte').html($(grid).getCaption());
            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
        }
        function exportR(grid) {
            var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
            temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true,removeHiddens:true}));
            $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'contenedores_'+$.getDate()+'.xls');
        }
    </script>
    <div id="formatoReporte" style="display: none;">
      <div style="width: 1030px;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE LIQUIDACIONES', '<span id="titleReporte"></span>',$obBD_conexion); ?>
        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
      </div>
    </div>
    <div id="formatoExportar" style="width: 700px;display: none;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE LIQUIDACIONES', '<span class="title_grid"></span>',$obBD_conexion,false,9); ?>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR-->
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>
    <div id='detalleDialog'></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>



