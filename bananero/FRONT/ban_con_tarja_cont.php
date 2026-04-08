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
    $obBD_con1->getPageGridJson('naviera_container.selectWhere', array_merge(array('setWhere'=>array('setVapor','setCliente','isActive')),$_GET), $obBD_conexion,true);
}
if(isset($getTarjas)){
    $obBD_con1->getPageGridJson('productor_tarja.selectWhere', array_merge(array('setWhere'=>array('setProductor','isActive')),$_GET), $obBD_conexion,true);
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
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Tarjas por Contenedor</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-5">
                    
                </div>
            </div>    
            <div class="row">    
                <div class="col-xs-5">
                    <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:if($('#Lib_Ano').val()===''||$('#Prt_Sem').val()==='')  $.alert('Debe seleccionar <u class=\'green\'>PERIODO</u> y <u class=\'green\'>SEMANA</u>'); else navesCont.Search('#formDocumento','searchNaves');"> 
                        <input name="order" type="hidden" value="Nav_Nom,Vap_Nom,Vap_Via,Nco_Nom" />
                        <fieldset class="exa-fieldset" id="provFormTemp">
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
                        <button type="button" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-print"></i> Imprimir Contenedores Semana</i></button>
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
                        <button type="button" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-print"></i> Imprimir Contenedor</i></button>
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
        var navesCont, tarjaProd;
        $(document).ready(function () {	           
            navesCont=$("#navesCont");
            if(navesCont.length>0){
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
                        {label: 'Eval.', name: 'Prt_Eva', width: 25, align: "center", classes:'bgNoRight bgNoColor', formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return $.createIcon("user green")+" <b class=\"blue\">"+o.Prt_Eva+"</b>"; }, noMsg:' ', yesIcon:'user green', noIcon:' ', yesColor:'blue', noText:true }, title:false },
                        {label: 'Obs.', name: 'Prt_Obs', width: 25, align: "center", classes:'bgNoColor', formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Prt_Obs; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false }
                    ],
                    footerrow:true, totalCols:['Prt_Car'],totalDefault:{Prt_Cah:$.fieldSummary()}               
                },true/*,"#tarjaProdPager"*/);
                
                navesCont.createGrid({               
                    height: 250, datatype:'local', caption: 'Nave/Contenedor',
                    colModel: [
                        { label: 'ID', name: 'Nco_Cod', key: true, width: 75, hidden:true },
                        { label: 'Naviera', name: 'Nav_Nom', width: 150, summaryType:$.fieldHeader, hidden:true },
                        { label: 'Nave', name: 'Vap_Nom', width: 150, hidden:true},
                        { label: 'Viaje', name: 'Vap_Via', width: 150, summaryType:$.fieldHeader, hidden:true },
                        { label: 'Descrip.', name: 'Nco_Nom', width: 150 },
                        { label: 'Sellos', name: 'Nco_Sel', width: 150, formatter:'tags',formatoptions:{type:'warning'}}, 
                        { label: 'Termografo', name: 'Nco_Ter', width: 150 },
                        { label: '&nbsp;', name: 'info', width: 35, formatter:'gridButton', formatoptions:{ data:'Nco_Cod', action:'setContainer', icon:'info-sign', type:'info' } },
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
                    grouping:true,
                    groupingView : {
                        groupField : ['Vap_Nom'], groupColumnShow : [false], groupText:["<div class='txtLeft'>{Nav_Nom} - <b><u>{0}</u></b> <i>[{Vap_Via}]</i></div>"]
                    }
                },false,"#navesContPager");
                
                $('#detalleDialog').createDialogDetail({                    
                    caption: 'Totales', width:300, height: '175',
                    footerrow:true, totalCols:['Prt_Car'], totalDefault:{Prt_Tip:$.fieldSummary()},
                    colModel: [
                        { label: 'ID', name: 'index', key: true, width: 75, hidden:true },
                        { label: 'Corte', name: 'Prt_Tip', width: 150, classes:'bgNoColor' },
                        { label: 'Cantidad', name: 'Prt_Car', width: 75, align:'right' }                
                    ]
                },{icon:'eye-open'});
            }
	
            
        });
        function setContainer(rowid) {
            var dato=navesCont.getCell(rowid,'OriginalData');  
            $('#formNave').setData(dato);
            tarjaProd.jqGrid('setCaption', 'Contenedor: '+dato.Nco_Nom+"<div class='pull-right' style='margin-top: -3px;'>"+$.getGridButton('showDetalle',rowid,'Ver Resumen','eye-open',null,'info')+'</div>');
            tarjaProd.Search({where:{Nco_Cod:dato.Nco_Cod}, getTarjas:true});
        }
        function clearSelection() {
            $('#formNave').setData({});
            if(tarjaProd!==undefined && tarjaProd.length>0){
                tarjaProd.jqGrid('setCaption', 'Contenedor: ');
                tarjaProd.clearGrid();
            }
        }
        function showDetalle() {
            var lista=[];
            var datos=tarjaProd.getGridBatch();
            $.each(datos,function(i,v){
                var add=true;               
                $.each(lista, function(j,w){
                    if(v['Prt_Tip']===w['Prt_Tip']){
                        w['Prt_Car']+=(w['Prt_Car']*1+v['Prt_Car']*1);
                        add=false;
                        return add;
                    }
                });
                if(add){                  
                    v['Prt_Tip']=getTipoCaja(v['Prt_Tip']);
                    lista.push(v);
                }
            });
            console.log(lista);
            $('#detalleDialog').getDialogGrid().setRowsByIndex(lista);
            $('#detalleDialog').dialog('open');
        }
        function getTipoCaja(Tip){
            var tipo='';
            $.each(tiposCaja,function(j,w){
                if(w['value']===Tip) tipo=w['label'];
            });
            return tipo===""?Tip:tipo;
        }
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id='detalleDialog'></div>
</BODY>
</HTML>



