<?php
/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_digitacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Ven($Ses_Dat_Dis);
/**
 * Creaci�n del Objeto para consultas
 */
$obBD_con1 = new Class_Log_Datos_Ven;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($usuAjax)){
    $responce['success']=true;
    $fechas="$ini 00:00:00".'*'."$fin 23:59:59".'*';
    $usuarios = $obBD_con1->getArrayConsulta(3, '', $obBD_conexion);
    $cant=count($usuarios);  
    
    $obBD_conexionAux = new Class_Log_Conexion_Ven;
    $bases = $obBD_con1->getArrayConsulta(2, '', $obBD_conexionAux);
    $obBD_conexionAux->cerrar();
    foreach($bases as $db){        
        $obBD_conexionAux = new Class_Log_Conexion_Ven($db['Dat_Dis']);          
        if($obBD_conexionAux->conexion!=false){
            for($i=0; $i<$cant; $i++) {                              
                $ventas= $obBD_con1->getRowConsulta(4, $fechas.$usuarios[$i]['Prs_Ced'], $obBD_conexionAux);
                $usuarios[$i]['TotalVet']=($usuarios[$i]['TotalVet']*1)+(empty($ventas)||empty($ventas['Conteo'])?0:$ventas['Conteo']*1);
                $compras= $obBD_con1->getRowConsulta(5, $fechas.$usuarios[$i]['Prs_Ced'], $obBD_conexionAux); 
                $usuarios[$i]['TotalCop']=($usuarios[$i]['TotalCop']*1)+(empty($compras)||empty($compras['Conteo'])?0:$compras['Conteo']*1);
                $comprob= $obBD_con1->getRowConsulta(6, $fechas.$usuarios[$i]['Prs_Ced'], $obBD_conexionAux); 
                $usuarios[$i]['TotalCom']=($usuarios[$i]['TotalCom']*1)+(empty($comprob)||empty($comprob['Conteo'])?0:$comprob['Conteo']*1);
            } 
        }
        $obBD_conexionAux->cerrar();
    }
    $responce['rows']=$usuarios;
    utf8_encode_trim_deep($responce['rows']); echo json_encode($responce); exit();
}
//if(isset($usuAjax)){     
//    $responce['success']=true;
//    $fechas="$ini 00:00:00".'*'."$fin 23:59:59".'*';
//    $responce['rows'] = $obBD_con1->getArrayConsulta(3, '', $obBD_conexion);  
//    foreach ($responce['rows'] as &$v) {
//        $ventas= $obBD_con1->getRowConsulta(4, $fechas.$v['Prs_Ced'], $obBD_conexion);       
//        $v['TotalVet']=empty($ventas)?0:$ventas['Conteo'];
//        $compras= $obBD_con1->getRowConsulta(5, $fechas.$v['Prs_Ced'], $obBD_conexion); 
//        $v['TotalCop']=empty($compras)?0:$compras['Conteo'];
//        $comprob= $obBD_con1->getRowConsulta(6, $fechas.$v['Prs_Ced'], $obBD_conexion); 
//        $v['TotalCom']=empty($comprob)?0:$comprob['Conteo'];
//    } unset($v);
//    utf8_encode_trim_deep($responce['rows']); echo json_encode($responce); exit();
//}
//if(isset($usuAjax)){ 
//    $responce['success']=true;
//    $responce['rows'] = $obBD_con1->getArrayConsulta(1, "$ini 00:00:00".'*'."$fin 23:59:59", $obBD_conexion);
//    utf8_encode_trim_deep($responce['rows']); echo json_encode($responce); exit();
//}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>                   
        <?Php require_once("../../mascaras/model1/estilos/jqplot.php") ?> 
        <style>                    
           .jqplot-table-legend{border-collapse:inherit !important;border-spacing:3px;}
            @media print { .hidePrint,.ui-datepicker{display: none;} }
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Digitaciones</h3></div>

            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
               
                <div class="row">
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset hidePrint">                           
                           <legend class="Titulos2">Filtros</legend> <!-- Form Name -->
                           <form id="formParam" class="form-horizontal normal"  action="javascript:findUsuarios();"  >
                               <input type="hidden" name="usuAjax" value="" />
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
                                  <div class=""><button type="button"  onclick="/*if($('#Pro_Cod').val()!==''){*/this.form.submit();$('#listProd').jqGrid('setCaption', 'Salidas de Mercaderia '+' - '+($('#producto').val()!==''?$('#producto').val()+' - ':'')+'Desde '+ $('#iniProd').val()+' Hasta '+$('#finProd').val());/*}else{$.alert('Seleccione el Producto');}*/" class="btn btn-sm btn-success" title="Ejecutar B�squeda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                </div>
                               </div>
                           </form>
                        </fieldset> 
                        <div style="margin-top:10px;margin-bottom: 5px;">
                            <table id="usua"></table>
                            <div id="usuaPager" style="hidePrint"></div>
                        </div>
                        <div>
                            <button type="button"  onclick="$('#usua').jqGrid('exportGridExcel',{nombre:'Digitacion',hoja:'Usuarios',caption:true});" class="btn btn-sm btn-success" title="Exportar a Excel"><span class="glyphicon glyphicon-download"></span> &nbsp;Excel</button>
                            <button type="button"  onclick="printR();" class="btn btn-sm btn-success" title="Imprimir"><span class="glyphicon glyphicon-print"></span> &nbsp;Imprimir</button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="scrollable-tree" style="max-height: 385px;">
                        <div style="margin-left:11%;">
                            <div id="chart4" style="width:90%; height:200px;"></div>                            
                        </div>    
                        </div> 
                        <div style="height:70px;width: 80px;" class="center-block hidePrint">
                            <table class="jqplot-table-legend" style="margin:4px;"><tbody><tr class="jqplot-table-legend"><td class="jqplot-table-legend jqplot-table-legend-swatch" style="text-align: center; padding-top: 0px;"><div class="jqplot-table-legend-swatch-outline"><div class="jqplot-table-legend-swatch" style="border-color: rgb(197, 180, 127); background-color: rgb(197, 180, 127);"></div></div></td><td class="jqplot-table-legend jqplot-table-legend-label" style="padding-top: 0px;">Asientos</td></tr><tr class="jqplot-table-legend"><td class="jqplot-table-legend jqplot-table-legend-swatch" style="text-align: center;"><div class="jqplot-table-legend-swatch-outline"><div class="jqplot-table-legend-swatch" style="border-color: rgb(234, 162, 40); background-color: rgb(234, 162, 40);"></div></div></td><td class="jqplot-table-legend jqplot-table-legend-label">Compras</td></tr><tr class="jqplot-table-legend"><td class="jqplot-table-legend jqplot-table-legend-swatch" style="text-align: center;"><div class="jqplot-table-legend-swatch-outline"><div class="jqplot-table-legend-swatch" style="border-color: rgb(75, 178, 197); background-color: rgb(75, 178, 197);"></div></div></td><td class="jqplot-table-legend jqplot-table-legend-label">Ventas</td></tr></tbody></table>
                        </div>
                    </div>
                </div>
              
            </div>
        </div>
        <div id="barDialog" title="Grafica Individual">
            <div class="row">
                <div class="col-xs-12" style="margin-bottom: 15px;">
                    <fieldset  class="exa-fieldset">
                        <legend class="Titulos2">Datos Usuario</legend>    
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="control-label label-sm col-xs-2">Usuario:</label>
                                <div class="col-xs-10">
                                    <input id="Prs_Cod" type="hidden" value="" />
                                    <span id="Usuario" class="form-control input-sm"></span>
                                </div>
                            </div>

                        </div>
                    </fieldset>    
                </div>                
                <div class="col-xs-6">
                    <div style="margin-left:8%;">
                    <div id="chart1" style="height:250px;width:250px;"></div>
                    </div> 
                </div>
                <div class="col-xs-6">
                    <div id="pie6" style="height:250px;width:275px;"></div> 
                </div>
            </div>            
        </div>
        <script>
        var plot4,plot1,plot6;
        var usuaGrid;
        $(function() {
            $.createDialog('#barDialog',400,600);
            usuaGrid=$("#usua");
            usuaGrid.createGrid({                
                caption:'Listado de Usuarios', height: 260, responsive:true,
                colModel: [                               
                    { label: 'Cod.Int.', name: 'Prs_Cod', key: true, hidden:true,viewable:true, width: 30,align:'center' }, 
                    { label: 'Usuario',name: 'Usuario', width: 150,classes:'columnHighlight3'},   
                    { label: 'Asientos',name: 'TotalCom', width: 30,align:'right'},    
                    { label: 'Compras',name: 'TotalCop', width: 30,align:'right'},    
                    { label: 'Ventas',name: 'TotalVet', width: 30,align:'right'}, 
                    { label:'&nbsp;', name: 'act1', width: 40, align: 'center',viewable: false,
                        formatter:function (cellvalue, options, rowObject) {                           
                            return $.getGridButton(replotBars,rowObject.Prs_Cod,"Ver Registro","fa-bar-chart",null,'info')+'&nbsp;'+
                                   $.getGridButton(deleteUsuario,rowObject.Prs_Cod,"Quitar de Lista","trash",null,'danger');
                        }
                    }
                ]
                                  
            },true,"#usuaPager",{view:false}); 
            
            var labels=['Ventas','Compras','Asientos'];
            
            var s1 = [[2,1], [6,2], [7,3], [10,4]];
            var s2 = [[7,1], [5,2],[3,3],[2,4]];
            var s3 = [[14,1], [9,2], [9,3], [8,4]];
             
             
            s1=s2=s3=[[0,0]];
            plot4 = $.jqplot('chart4',[s1, s2, s3], {
                title: "Registros por Usuario",
                animate: !$.jqplot.use_excanvas, animateReplot: true,
                stackSeries: true,
                //captureRightClick: true,			
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
            $.createDateRange('#iniProd','#finProd');
            $('#formParam')[0].reset();
            //replotGraph();
        });
        function replotGraph(){
            var batch=usuaGrid.getGridBatch();
            var ticks=new Array();
            var ser1 =new Array();
            var ser2 =new Array();
            var ser3 =new Array();
            
            for(var i=0;i<batch.length;i++){
                ticks.push((batch[i]['Usuario']).substring(0, 20));
                ser1.push(new Array(batch[i]['TotalVet']*1,i+1));
                ser2.push(new Array(batch[i]['TotalCop']*1,i+1));
                ser3.push(new Array(batch[i]['TotalCom']*1,i+1));
            }
            //console.log(ser1);
            $('#chart4').height(batch.length*50);
            plot4.axes.yaxis.ticks=ticks;
            plot4.series[0].data = ser1;
            plot4.series[1].data = ser2;
            plot4.series[2].data = ser3;
              
            plot4.replot({resetAxes: true });
        }
        function findUsuarios() {  
            $("#loader").show();
            $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$('#formParam').serializeObject(),function(response){
                $("#loader").fadeOut("slow");
                if(response['success']===true){
                    usuaGrid.setRows(response['rows']);
                    usuaGrid.jqGrid('setCaption', 'Registros '+' - '+'Desde '+ $('#iniProd').val()+' Hasta '+$('#finProd').val());
                    replotGraph();
                }else{
                    usuaGrid.clearGrid();
                }
            },'json').fail(function(error) { $("#loader").hide();$.alert("El Servidor ha fallado en responder!");});            
        }
        </script>   
        <script>
            //$.jqplot.config.enablePlugins = true;
           $(function() {  
                plot1 = $.jqplot('chart1', [[2, 6, 7]], {   
                    title: "Registros",
                    animate: !$.jqplot.use_excanvas, animateReplot: true,
                    seriesDefaults:{
                        renderer:$.jqplot.BarRenderer,
                        pointLabels: { show: true },
                        rendererOptions: {varyBarColor: true}
                    },
                    axes: { xaxis: {renderer: $.jqplot.CategoryAxisRenderer,ticks: ['Ventas','Compras','Asientos']} },
                    highlighter: {show: true,useAxesFormatters: false,tooltipContentEditor:tooltipContentEditorX}
                });                
                
                $('#chart1').bind('jqplotDataClick', 
                    function (ev, seriesIndex, pointIndex, data) {
                        console.log(pointIndex);
                        //$('#info1').html('series: '+seriesIndex+', point: '+pointIndex+', data: '+data);
                    }
                );
            });  
            plot6 = $.jqplot('pie6', [[["Todas",10]]], {
                title: "Empresas",legend:{ show:true }  ,
                seriesDefaults:{ renderer: $.jqplot.PieRenderer },
                highlighter: {show: true,useAxesFormatters: false,formatString:'%s, %P'}
            });
            function tooltipContentEditorBars(str, seriesIndex, pointIndex, plot) {               
                return '<b>'+plot.axes.yaxis.ticks[pointIndex] + "</b> <br/><b>"+plot.legend.labels[seriesIndex] +'</b>: ' + plot.series[seriesIndex].data[pointIndex][0]+' Registros.';
            }
            function deleteUsuario(Prs_Cod) { usuaGrid.jqGrid('delRowData',Prs_Cod); replotGraph(); }
            function replotBars(Prs_Cod) {  
                var data=usuaGrid.jqGrid('getRowData',Prs_Cod);
                $('#Usuario').html(data['Usuario']);
                $('#Prs_Cod').val(data['Prs_Cod']);
                $('#barDialog').dialog('open');
                var ser1 =[[data['TotalVet']*1,data['TotalCop']*1,data['TotalCom']*1]];                
                plot1.replot({data:ser1 });                
            }
            function printR() {
                var imgData = $('#chart4').jqplotToImageStr({}); // given the div id of your plot, get the img data
                $('#imgChart1').attr('src',imgData); // create an img and add the data to it               
                $('#tablaReporte').html($("#usua").jqGrid('exportGridInnerHTML',{generated:false,caption:false,bodyBorder:false}));
                $('#titleReporte').html(usuaGrid.getCaption());
                $('#tablaReporte').find("tbody").find("tr").find("td:eq(6)").remove();
                $('#tablaReporte').find("thead").find("tr").find("th:eq(6)").remove();
                $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
            }
        </script>
        <div id="formatoReporte" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE DIGITACI�N', '<span id="titleReporte">Digitaciones por Usuarios</span>',$obBD_conexion); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
            <div style="margin-left:20%;margin-right:20%;margin-top: 20px;"><img id="imgChart1" style="width: 100%;"/></div>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>   
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    </BODY>
</HTML>