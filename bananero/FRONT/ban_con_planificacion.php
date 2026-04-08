<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_exporta_plan.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_ExportaPlanif;

if(isset($searchAjax)){
    $page=$obBD_con1->getPageGrid('exporta_planif.selectWhere', $_GET, $obBD_conexion);
    foreach ($page['rows'] as &$v) {
        $v['Registrado']=$obBD_con1->getRowConsulta('productor_tarja.selectWhere',array('unsetCols'=>true, 'addCols'=>array(''=>array('Conteo'=>$obBD_con1->expr('SUM(Prt_Car)'))), 'where'=>array('exporta_planif_det.Pln_Cod'=>$v['Pln_Cod']), 'setWhere'=>array('setByPlanificacion'), 'group'=>'exporta_planif_det.Pln_Cod'), $obBD_conexion);
    } unset($v);
    $obBD_con1->echoJson($page);
}
if(isset($pedidosDetAjax)){
    $_GET['setWhere']=array('isActive');
    $page=$obBD_con1->getPageGrid('exporta_planif_det.selectWhere', $_GET, $obBD_conexion);
    foreach ($page['rows'] as &$v) {
        $conteo=$obBD_con1->getRowConsulta('exporta_planif_det.sql.countContenedores',$v['Pde_Cod'], $obBD_conexion);
        $v['Contenedores']=isset($conteo['total'])&&!empty($conteo['total'])?$conteo['total']*1:0;
        $v['Total']=isset($conteo['suma'])&&!empty($conteo['suma'])?$conteo['suma']*1:0;
        $v['Registrado']=$obBD_con1->getRowConsulta('productor_tarja.selectWhere',array('unsetCols'=>true, 'addCols'=>array(''=>array('Conteo'=>$obBD_con1->expr('SUM(Prt_Car)'))), 'where'=>array('exporta_planif_container.Pde_Cod'=>$v['Pde_Cod']), 'setWhere'=>array('setByPlanificacion'), 'group'=>'exporta_planif_det.Pde_Cod'), $obBD_conexion);
    } unset($v);
    $obBD_con1->echoJson($page);
}
if(isset($loadContainers)){
    $page=$obBD_con1->getPageGrid('naviera_container.selectWhere', $_GET, $obBD_conexion);
    foreach ($page['rows'] as &$v) {
        $v['Registrado']=$obBD_con1->getRowConsulta('productor_tarja.selectWhere',array('unsetCols'=>true, 'addCols'=>array(''=>array('Conteo'=>$obBD_con1->expr('SUM(Prt_Car)'))), 'Nco_Cod'=>$v['Nco_Cod'], 'where'=>array(), 'setWhere'=>array('setByNcoCod')), $obBD_conexion);
    }
    $obBD_con1->echoJson($page);
}
if(isset($getTarjas)){
    $obBD_con1->getPageGridJson('productor_tarja.selectWhere', array_merge(array('setWhere'=>array('setProductor','isActive')),$_GET), $obBD_conexion);
}
$hoy = date("Y-m-d");
$marcas=$obBD_con1->getArrayConsulta('banano_marca.selectWhere',  array('setWhere'=>array('setEmpCod','isActive')), $obBD_conexion);
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$destinos=$obBD_con1->getArrayConsulta('exporta_dest.selectWhere',  array('setWhere'=>array('isActive')), $obBD_conexion);
$cur_periodo=current($periodos);
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion Pedidos del Exterior</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="divSearch" class="row">
                <div class="col-sm-12">
                    <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid2').Search('#searchForm','searchAjax');">
                        <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Consulta de Información</legend>
                        <div class="col-sm-4">

                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                <div class="col-xs-7" >
                                    <select id="Lib_Ano" name="Pln_Ano" class="form-control input-xs" >
                                        <option value="">Periodo..</option>
                                        <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Semana:</label>
                                <div class="col-xs-9" ><select id="Prt_Sem" name="Pln_Sem" class="form-control input-xs" ></select></div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Marca:</label>
                                <div class="col-xs-9" >
                                    <select id="Bam_Cod" name="Bam_Cod" class="form-control input-xs getData ins"s>
                                        <?php if(count($marcas)!=1){ ?><option value="">Selecione Marca...</option><?php } ?>
                                        <?php foreach ($marcas as $m) {
                                            echo "<option value='$m[Bam_Cod]' data--bam_-cod='$m[Bam_Cod]' data--bam_-tam='$m[Bam_Tam]'>$m[Bam_Nom] $m[Bam_Tam]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="center">
                                <button type="button" onclick="$('#searchForm').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                            </div>
                        </div>
                        </fieldset>
                    </form>
                </div>
                <div class="col-xs-12">
                    <div >
                        <table id="searchGrid2"></table>
                        <div id="searchGrid2Pager"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <script type="text/javascript">
    var searchGrid2;
    $(function(){
        searchGrid2=$('#searchGrid2');
        if(searchGrid2.length>0){
        searchGrid2.createGrid({
            postData:$('#searchForm').getData('searchAjax'),
            datatype:'local', height: 295,selectGridRows:false,
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Pln_Cod', width: 25,key:true},
                {label: 'C&oacute;d. Int.', name: 'Cli_Cod', width: 15, hidden:true},
                {label: 'C&oacute;d. Int.', name: 'Bam_Cod', width: 15, hidden:true},
                {label: 'C&oacute;d. Int.', name: 'Exd_Cod', width: 15, hidden:true},
                {label: 'Fecha', name: 'Pln_Fec', width: 75, align: "center"},
                {label: 'Marca', name: 'Marca', width: 80, align: "left", formatter:'union', formatoptions:{sep:' - ', cols:['Bam_Nom','Bam_Tam']} },
                {label: 'Ruc', name: 'Ruc', width: 100, align: "left"},
                {label: 'Cliente', name: 'Cliente', width: 150, align: "left"},
                {label: 'Destino', name: 'Exd_Nom', width: 100, align: "left"},
                {label: 'Pais', name: 'Pas_Nom', width: 150, align: "left"},
                {label: 'Año', name: 'Pln_Ano', width: 50, align: "center" },
                {label: 'Semana', name: 'Pln_Sem', width: 50, align: "center" },
                {label: 'Cant.Planif.', name: 'Pln_Can', width: 70, align: "right", classes:'columnHighlight3' },
                {label: 'Real', name: 'Registrado.Conteo', width: 70, align:"right", classes:'columnHighlight1'}
            ],
            subGrid:true, multiselect:false,
            subGridRowExpanded: function(subgrid_id, row_id) {
                $("#"+subgrid_id).html("<div class='condensed-header jqFirst'><table id='"+subgrid_id+"_t' class='scroll'></table></div>");
                $("#"+subgrid_id+"_t").createGrid({
                    url:"?"+$.param({pedidosDetAjax:true,where:{'exporta_planif_det.Pln_Cod':row_id}}), datatype:"json", height:'auto',selectGridRows:false,
                    colModel: [
                      {label: 'C&oacute;d. Int.', name: 'Pde_Cod', width: 10,key:true, hidden:true},
                      {label: 'C&oacute;d. Int.', name: 'Pln_Cod', width: 10, hidden:true},
                      {label: 'AUCP', name: 'Pln_Auc', width: 30},
                      {label: 'DAE', name: 'Pln_Dae', width: 30},
                      {label: 'Booking', name: 'Pln_Boo', width: 20},
                      {label: 'Tipo', name: 'Pln_Tip', width: 20},
                      {label: 'Cont.', name: 'Contenedores', width: 10, align:"center", formatter:'title', formatoptions:{title:function(o){ return (o.Contenedores||'0')+' Contenedores Asignados'; }}, title:false, classes:'columnHighlight3'},
                      {label: 'Capacidad', name: 'Total', width: 10, align:"right", formatter:'title', formatoptions:{title:function(o){ return (o.Total||'0')+' Cajas'; }}, title:false, classes:'columnHighlight3'},
                      {label: 'Real', name: 'Registrado.Conteo', width: 10, align:"right", classes:'columnHighlight1'},
                      {label: 'Estado', name: 'Pln_Est', width: 10, align:"center", formatter:'estado', title:false},
                      {label: 'Obs.', name: 'Pln_Obs', width: 10, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Pln_Obs; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false }
                      //$.originalRow()
                    ],rowNum:1000000, pager: "", rownumbers:true,
                    loadComplete:function(data){
                        if(data['rows'].length===0) searchGrid2.collapseSubGridRow(row_id);
                    },
                    subGrid:true, multiselect:false,
                    subGridRowExpanded: function(subgrid_id, row_id) {
                        $("#"+subgrid_id).html("<div class='condensed-header jqSecond'><table id='"+subgrid_id+"_t' class='scroll'></table></div>");
                        $("#"+subgrid_id+"_t").createGrid({
                          url:"?"+$.param({loadContainers:true,where:{'exporta_planif_container.Pde_Cod':row_id},setWhere:['setEmpCod','setVapor']}), datatype:"json", height:'auto',selectGridRows:false,
                          colModel: [
                            {label: 'C&oacute;d. Int.', name: 'Nco_Cod', width: 25,key:true, hidden:true },
                            {label: 'Nave/Vapor', name: 'Vap_Nom', width: 40, align: "left", classes:'bgNoRight'},
                            {label: 'Cod.Viaje.', name: 'Vap_Via', width: 40, align: "left", classes:'bgNoRight'},
                            {label: 'Salida', name: 'Edi_Nom', width: 50, align: "left"},
                            {label: 'Descr.', name: 'Nco_Nom', width: 40},
                            {label: 'Termog.', name: 'Nco_Ter', width: 30},
                            {label: 'Sellos', name: 'Nco_Sel', width: 75, formatter:'tags',formatoptions:{type:'warning'}},
                            {label: 'Dia', name: 'Mco_Dia', width: 40, align: "center"},
                            {label: 'Cut Off', name: 'Vap_Cof', width: 40, align: "center"},
                            {label: 'Capacidad', name: 'Nco_Can', width: 30, align:"right", classes:'columnHighlight3'},
                            {label: 'Real', name: 'Registrado.Conteo', width: 30, align:"right", classes:'columnHighlight1'},
                            {label: 'Obs.', name: 'Nco_Obs', width: 10, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Nco_Obs; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false }
                          ], rowNum:1000000, pager: "", rownumbers:false,
                            subGrid:true, multiselect:false,
                            subGridRowExpanded: function(subgrid_id, row_id) {
                                $("#"+subgrid_id).html("<div class='condensed condensed-header jqThird'><table id='"+subgrid_id+"_t' class='scroll'></table></div>");
                                $("#"+subgrid_id+"_t").createGrid({
                                  url:"?"+$.param({where:{Nco_Cod:row_id}, getTarjas:true}), datatype:"json", height:'auto',selectGridRows:false,
                                  colModel: [
                                    { label: 'ID', name: 'Prt_Cod', key: true, width: 75, hidden:true },
                                    { label: 'Productor', name: 'Productor', width: 100, classes:'bgNoRight bgNoColor' },
                                    { label: 'Entrega', name: 'Entrega', width: 50, align:'right', formatter:'union', formatoptions:{conditional:function(o){ return (o.Prt_Car)*1+(o.Prt_Cah)*1; }}, classes:'bgNoRight bgNoColor' },
                                    { label: 'Merma', name: 'Prt_Cah', width: 50, align:'right', classes:'bgNoColor'},
                                    { label: 'Ingreso', name: 'Prt_Car', width: 50, align:'right', classes:'columnHighlight3'},
                                    { label: 'Corte', name: 'Prt_Tip', width: 50, align:'center', classes:'bgNoRight bgNoColor', formatter:'title', formatoptions:{title:function(o){ return getTipoCaja(o.Prt_Tip); } } },
                                    { label: 'Cod.', name: 'Prd_Cau', width: 40, classes:'bgNoColor' },
                                    { label: 'Eval.', name: 'Prt_Eva', width: 25, align: "center", classes:'bgNoRight bgNoColor', formatter:'truefalse', formatoptions:{ noText:false, yesMsg:function(o){ return $.createIcon("user green")+" <b class=\"blue\">"+o.Prt_Eva+"</b>"; }, noMsg:' ', yesIcon:'user green', noIcon:' ', yesColor:'blue' }, title:false },
                                    { label: 'Obs.', name: 'Prt_Obs', width: 25, align: "center", classes:'bgNoColor', formatter:'truefalse', formatoptions:{ noText:false, yesMsg:function(o){ return o.Prt_Obs; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue' }, title:false }
                                  ],
                                  footerrow:true, totalCols:['Prt_Car'],totalDefault:{Prt_Cah:$.fieldSummary()}, rowNum:1000000, pager: "", rownumbers:false
                                });
                              }
                        });
                      }
                });
              },
              loadComplete: function(){
                    var rowIds = searchGrid2.getDataIDs();
                    $.each(rowIds, function (i, v) { searchGrid2.expandSubGridRow(v); });
              }
        }, false, "#searchGrid2Pager").gridButtonsAdd([
            null,{caption:'Imprimir', buttonicon:'print', onClickButton: function(){ printR('#searchGrid2'); } }
        ]);
        }
        setSemanas('#Prt_Sem');
    });
    function setSemanas(id){
        var sem= $(id), html='<option value="">Seleccione Semana...</option>';
        if(!sem.length) return;
        for(var i=1;i<=52;i++) html+=('<option value="'+i+'">'+i.ordinal(true)+' Semana </option>');
        sem.html(html);
    }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR-->
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>
    <script>
        function printR(grid) {
            //$('#titleReporte').html($(grid).getCaption());
            $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false, removeHiddens:true, removeCols:[2]}));
            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
        }
    </script>
    <div id="formatoReporte" style="display: none;">
      <div style="width: 1030px;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CONTENEDORES', '<span id="titleReporte"></span>',$obBD_conexion); ?>
        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
      </div>
    </div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>



