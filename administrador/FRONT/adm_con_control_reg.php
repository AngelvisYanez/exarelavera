<?php
/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaciï¿½n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_digitacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Ven($Ses_Dat_Dis);
/**
 * Creaciï¿½n del Objeto para consultas
 */
$obBD_con1 = new Class_Log_Datos_Ven;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($empAjax)){
    $responce['success']=true;
    $fechas="$ini 00:00:00".'*'."$fin 23:59:59";
        
    $obBD_conexionAux = new Class_Log_Conexion_Ven;
    $base = $obBD_con1->getRowConsulta(2, $Emp_Cod, $obBD_conexionAux);
    $obBD_conexionAux->cerrar();
    $responce['base']=$base;
    $obBD_conexionDis = new Class_Log_Conexion_Ven($base['Dat_Dis']);  
    $estados=array(array('value'=>'A','label'=>'activos'),array('value'=>'I','label'=>'anulados'));
    $filas_gene=array(
        array('name'=>'Compras','sql'=>8),
        array('name'=>'Ventas','sql'=>9),
        array('name'=>'Asientos','sql'=>10,'tipos'=>array('Ingreso'=>" AND Tia_Ini='I' AND Com_Est='A' AND Com_Gen!='A' ",'Egreso'=>" AND Tia_Ini='E' AND Com_Est='A' AND Com_Gen!='A'",'Diario'=>" AND Tia_Ini='D' AND Com_Est='A' AND Com_Gen!='A'",'Automatico'=>" AND Com_Est='A' AND Com_Gen='A'",'Anulado'=>" AND Com_Est='I'")),
        array('name'=>'Cheques','sql'=>11,'tipos'=>array('No Cobrados'=>" AND Che_Est='A' ",'Cobrados'=>" AND Che_Est='C'",'Protestados'=>" AND Che_Est='P' ",'Anulados'=>" AND Che_Est='I' ")),
    );
    $responce['filas_gene']=array();
    $responce['pies']=array();
    foreach($filas_gene as $v){
        $fila=array('tipo'=>$v['name']);
        $pie=array();
        foreach($estados as $s){
            $total=$obBD_con1->getRowConsulta($v['sql'], $Emp_Cod.'*'.$s['value'].'*'.$fechas, $obBD_conexionDis);
            $fila[$s['label']]=$total['total']; 
            if(!isset($fila['ultimo'])||$fila['ultimo']<$total['last']) $fila['ultimo']=$total['last'];
            if(!isset($responce['ultimo'])||$responce['ultimo']<$total['last']) $responce['ultimo']=$total['last'];
            if(!isset($v['tipos'])) array_push($pie, array('tipo'=>ucfirst($s['label']),'total'=>$total['total'],'ultimo'=>$total['last']));
        }
        array_push($responce['filas_gene'], $fila);        
        if(isset($v['tipos'])){
            foreach($v['tipos'] as $k=>$t){                
                $total=$obBD_con1->getRowConsulta($v['sql'], $Emp_Cod.'*'.''.'*'.$fechas.'*'.$t, $obBD_conexionDis);
                array_push($pie, array('tipo'=>ucfirst($k),'total'=>$total['total'],'ultimo'=>$total['last']));
            }
        }
        $responce['pies'][$v['name']]=$pie;
    }
    $obBD_conexionDis->cerrar();
    $obBD_con1->echoJson($responce);    
}

$obBD_conexionAux = new Class_Log_Conexion_Ven;
$empresas=$obBD_con1->getArrayConsulta(7, "", $obBD_conexionAux); 
$obBD_conexionAux->cerrar();
$emp_options="<option value=''></option>";
foreach($empresas as $r) $emp_options.="<option value='".$r['Emp_Cod']."' data--emp_-nom='".($r['Emp_Nom'])."'>".($r['Emp_Cor'])."</option>";
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>                 
        <?Php require_once("../../mascaras/model1/estilos/jqplot.php") ?> 
        <style>                    
           .jqplot-table-legend{border-collapse:inherit !important;border-spacing:3px;}
            @media print { .hidePrint,.ui-datepicker{display: none;} }
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header hidePrint"><h3 class="panel-title">&raquo;  Control de Registros</h3></div>

            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
               
                <div class="row">  
                    <div class="col-sm-12">
                    <fieldset class="exa-fieldset hidePrint">                           
                       <legend class="Titulos2">Filtros</legend> <!-- Form Name -->
                       <form id="formParam" class="form-horizontal normal"  action="javascript:if($('#Emp_Cod').val()!=='')setEmpresa(); else $.alert('Debe seleccionar una <u>Empresa</u>!');"  >
                           <div class="col-sm-6">
                               <div class="form-group">
                                 <label class="col-xs-2 control-label label-sm required ">Empresa:</label>
                                 <div class="col-xs-10">     
                                     <select name="Emp_Cod" id="Emp_Cod" class="form-control input-sm" data-placeholder="Seleccione Empresa..."><?php echo $emp_options; ?></select>
                                 </div>
                               </div>  
                           </div>    
                           <div class="col-sm-6">
                                <input type="hidden" name="empAjax" value="" />
                                <div class="form-group">
                                 <label class="col-xs-2 control-label label-sm required ">Desde:</label>
                                 <div class="col-xs-3">     
                                     <input name="ini" type="text" id="iniProd" class="form-control input-sm" value="<?php echo $hoy; ?>" required/>
                                 </div>
                                 <label class="col-xs-1 control-label label-sm required ">Hasta:</label>
                                 <div class="col-xs-3">                                    
                                     <input name="fin" type="text" id="finProd" class="form-control input-sm" value="<?php echo $hoy; ?>" required/>                              
                                 </div>
                                 <div class="col-xs-2">
                                   <div class=""><button type="button"  onclick="/*if($('#Pro_Cod').val()!==''){*/this.form.submit();$('#listProd').jqGrid('setCaption', 'Salidas de Mercaderia '+' - '+($('#producto').val()!==''?$('#producto').val()+' - ':'')+'Desde '+ $('#iniProd').val()+' Hasta '+$('#finProd').val());/*}else{$.alert('Seleccione el Producto');}*/" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                 </div>
                                </div>
                            </div>    
                       </form>
                    </fieldset> 
                    </div>
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix hidePrint">
                                <li><a href="#tabs-0">General</a></li>
                                <li><a href="#tabs-1">Comprobantes</a></li>
                                <li><a href="#tabs-2">Compras</a></li>
                                <li><a href="#tabs-3">Ventas</a></li>
                                <li><a href="#tabs-4">Cheques</a></li>
                                <li class="pull-right" style="text-shadow: none; color:black;"><div >Último Registro: <input id="ultimo" style="border-radius: 3px;border: 1px solid #ccc; text-align: center; font-weight: normal; padding: 2px 10px;" tabindex="-1" readonly="" /></div></li>
                            </ul>
                            <div class="panels-area">
                                <div id="tabs-0">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div>
                                                <table id="usua"></table>
                                                <div id="usuaPager"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="scrollable-tree" style="max-height: 385px;">
                                            <div style="margin-left:11%;">
                                                <div id="chartGene" style="width:90%; height:200px;"></div>                            
                                            </div>    
                                            </div> 
                                            <div style="height:70px;width: 80px;" class="center-block hidePrint">
                                                <table class="jqplot-table-legend" style="margin:4px;">
                                                    <tbody><tr class="jqplot-table-legend"><td class="jqplot-table-legend jqplot-table-legend-swatch" style="text-align: center;"><div class="jqplot-table-legend-swatch-outline"><div class="jqplot-table-legend-swatch" style="border-color: rgb(234, 162, 40); background-color: rgb(234, 162, 40);"></div></div></td><td class="jqplot-table-legend jqplot-table-legend-label">Anulados</td></tr><tr class="jqplot-table-legend"><td class="jqplot-table-legend jqplot-table-legend-swatch" style="text-align: center;"><div class="jqplot-table-legend-swatch-outline"><div class="jqplot-table-legend-swatch" style="border-color: rgb(75, 178, 197); background-color: rgb(75, 178, 197);"></div></div></td><td class="jqplot-table-legend jqplot-table-legend-label">Activos</td></tr></tbody>
                                                </table>
                                            </div>
                                        </div>    
                                    </div>
                                </div>
                                <div id="tabs-1">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div>
                                                <table id="gridAsientos"></table>
                                                <div id="pagerAsientos" style="hidePrint"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6"><div id="pieAsientos"></div></div>
                                    </div>    
                                </div>
                                <div id="tabs-2">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div>
                                                <table id="gridCompras"></table>
                                                <div id="pagerCompras" style="hidePrint"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6"><div id="pieCompras"></div></div>
                                    </div> 
                                </div>
                                <div id="tabs-3">
                                    <div class="row">
                                        <div class="col-sm-6">
                                             <div>
                                                <table id="gridVentas"></table>
                                                <div id="pagerVentas" style="hidePrint"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6"><div id="pieVentas"></div></div>
                                    </div> 
                                </div>
                                
                                <div id="tabs-4">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div>
                                               <table id="gridCheques"></table>
                                               <div id="pagerCheques" style="hidePrint"></div>
                                           </div>
                                       </div>
                                       <div class="col-sm-6"><div id="pieCheques"></div></div>
                                    </div>   
                                </div>
                            </div>                            
                        </div> 
                        <div style="margin-top: 5px;">
                            <button type="button"  onclick="exportR();" class="btn btn-sm btn-success hidePrint" title="Exportar a Excel"><span class="glyphicon glyphicon-download"></span> &nbsp;Exportar </button>                            
                        </div>
                    </div>
                </div>
              
            </div>
        </div>
      
        <script>
        var gridGeneral,plotGen,
            pieGlobal=[
                {data:null,id:'Asientos',label:'Comprobantes Contables'},
                {data:null,id:'Compras',label:'Compras Registradas'},
                {data:null,id:'Ventas',label:'Ventas Registradas'},
                {data:null,id:'Cheques',label:'Cheques Registrados'}
            ];

        $(function() {
            var span='<span class="title_grid"></span>', totlabel='<div style="text-align:right;">TOTAL:<div>';
            gridGeneral=$("#usua");
            gridGeneral.createGrid({                
                caption:'Registros Globales'+span, height: 260, footerrow:true, 
                colModel: [    
                    { label: 'Tipo Documento',name: 'tipo', width: 75,classes:'columnHighlight3'},   
                    { label: 'Activos',name: 'activos', width: 30,align:'right'},    
                    { label: 'Anulados',name: 'anulados', width: 30,align:'right'},
                    { label: 'Último',name: 'ultimo', width: 40,align:'center'}
                ], loadComplete: function(){ $(this).setGridSummary(['activos','anulados'],{tipo:totlabel}); }
            },true,"#usuaPager").gridButtonAdd({buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR('#usua','#chartGene'); }}); 
            
            var auxData=[[["Todas",10]]], 
                dataPlot={ legend:{ show:true }, seriesDefaults:{ renderer: $.jqplot.PieRenderer }, highlighter: {show: true,useAxesFormatters: false, formatString:'%s, %P'} },
                gridDefault={                    
                    colModel: [    
                        { label: 'Estado/Tipo',name: 'tipo', width: 100,classes:'columnHighlight3'},   
                        { label: 'Total',name: 'total', width: 30,align:'right'},    
                        { label: 'Último',name: 'ultimo', width: 40,align:'center'}
                    ], height: 260, footerrow:true, loadComplete: function(){ $(this).setGridSummary(['total'],{tipo:totlabel}); }
                };
            for(var i=0;i<pieGlobal.length;i++){    
                pieGlobal[i]['data'] = $.jqplot('pie'+pieGlobal[i]['id'],auxData,$.extend(true,dataPlot,{title:pieGlobal[i]['label']}));                
                $("#grid"+pieGlobal[i]['id']).createGrid($.extend(true,gridDefault,{caption:pieGlobal[i]['label']+span}),true,'#pager'+pieGlobal[i]['id']).gridButtonAdd({buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR('#grid'+this.p['gene_id'],'#pie'+this.p['gene_id']); }})[0].p['gene_id']=pieGlobal[i]['id'];
            }
            
            var labels=['Activos','Anulados'], s1, s2;
            s1=s2=[[0,0]];
            plotGen = $.jqplot('chartGene',[s1, s2], {
                title: "Registros Globales",
                animate: !$.jqplot.use_excanvas, animateReplot: true,
                stackSeries: true, //captureRightClick: true,			
                seriesDefaults:{
                    renderer:$.jqplot.BarRenderer,
                    shadowAngle: 135,linePattern: 'dashed',
                    rendererOptions: {
                        barDirection: 'horizontal',                        
                        barWidth: 26,barPadding: 4, barMargin: 5                        
                    },
                    pointLabels: {show: true, hideZeros: true, formatString: '%d'}
                },
                legend: {
                    labels: labels ,
                    show: true,fontSize:'20px',
                    location: 'e'//,placement: 'outside'
                },
                axes: {
                    yaxis: {
                        renderer: $.jqplot.CategoryAxisRenderer,
                        autoscale:true,    
                        tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                        tickOptions: {angle: -65}
                    },
                    autoscale:true    
                },                                                                              
                highlighter:{show:true,useAxesFormatters: false,tooltipContentEditor:tooltipContentEditorBars,tooltipLocation : 'ne'}			
            });
            
            $('#tabs').createTabs(null,false);
            $.createDateRange('#iniProd','#finProd');
            $('#Emp_Cod').createChosen('input-sm',{template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc">'+d['Emp_Nom']+'</div>';}});
            $('#formParam')[0].reset();
            $('.ui-dialog,.jqplot-table-legend,.ui-jqgrid-pager').addClass('hidePrint');
        });
        function replotGraph(batch){
            gridGeneral.setRows(batch);
            var ticks=new Array(),
                ser1 =new Array(),
                ser2 =new Array();
            
            for(var i=0;i<batch.length;i++){
                ticks.push(batch[i]['tipo']);
                ser1.push(new Array(batch[i]['activos']*1,i+1));
                ser2.push(new Array(batch[i]['anulados']*1,i+1));                
            }
            $('#chartGene').height(batch.length*70);
            plotGen.axes.yaxis.ticks=ticks;
            plotGen.series[0].data = ser1;
            plotGen.series[1].data = ser2;
            var tab=$('#tabs-0'), hidden=tab.is(':hidden');
            if(hidden) tab.show(); plotGen.replot({resetAxes: true }); if(hidden) tab.hide();
        }
        function replotPies(d){
            $.each(d,function(k,v){                
                var padre=$('#pie'+k).parent().parent().parent(), hidden=padre.is(':hidden'), fila=$.arrayGetItem(pieGlobal,'id',k), series=[];
                $('#grid'+fila['id']).setRows(v);
                $.each(v,function(i,f){
                    series.push([f['tipo'],f['total']*1]);
                });
                fila['data'].series[0].data=series;
                if(hidden) padre.show(); fila['data'].replot(); if(hidden) padre.hide();
            });
        }
        function setEmpresa() {              
            $.getDataJson('',$('#formParam').serializeObject(),function(response){
                $("#loader").fadeOut("slow");
                if(response['success']===true){                    
                    $('.title_grid').html(' - '+'Desde '+ $('#iniProd').val()+' Hasta '+$('#finProd').val());
                    $('#ultimo').val(response['ultimo']);
                    replotGraph(response['filas_gene']);
                    replotPies(response['pies']);  
                    $('.jqplot-table-legend').addClass('hidePrint');
                }
            });
        }
        </script>   
        <script>
            //$.jqplot.config.enablePlugins = true;             
            function tooltipContentEditorBars(str, seriesIndex, pointIndex, plot) {               
                return '<b>'+plot.axes.yaxis.ticks[pointIndex] + "</b> <br/><b>"+plot.legend.labels[seriesIndex] +'</b>: ' + plot.series[seriesIndex].data[pointIndex][0]+' Registros.';
            }
            function printR(grid,chart) {
                var imgData = $(chart).jqplotToImageStr({}); // given the div id of your plot, get the img data
                $('#imgChart1').attr('src',imgData); // create an img and add the data to it               
                $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));
                $('#titleReporte').html($(grid).getCaption());
                $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
            }
            function exportR() {
                var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
                temp.append($("#usua").jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));
                for(var i=0;i<pieGlobal.length;i++)
                    temp.append($("#grid"+pieGlobal[i]['id']).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));
                $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'digitacion_'+$.getDate()+'.xls');    
            }
        </script>
        <div id="formatoReporte" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>',$obBD_conexion); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
            <div style="margin-left:20%;margin-right:20%;margin-top: 20px;"><img id="imgChart1" style="width: 100%;"/></div>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>  
        <div id="formatoExportar" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', 'Registros <span class="title_grid"></span>',$obBD_conexion,false,5); ?>
        </div>    
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    </BODY>
</HTML>