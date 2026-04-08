<?php	
/**
* @abstract Permite realizar la consulta de activos
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci?n  2016-09-09
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Activo($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Activo;

if(isset($actAjax)){
    $data=filter_input_array(INPUT_GET);
    $data["Suc_Cod"]=$Ses_Suc_Cod;
    $contar=$obBD_con1->getRowConsulta(613,$data,$obBD_conexion);
    $pagination=pages($contar['total'], $page, $rows);

    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0){$responce['rows']=$obBD_con1->getArrayConsulta(613,$data,$obBD_conexion);}
    echo json_encode($responce);
    exit();
}

/*Secci�n para extraer todos los activos de la tabla del mismo nombre*/
if(isset($allActivos)){
    $data=$_POST;
    $data['Suc_Cod']=$Ses_Suc_Cod;
    $response=$obBD_con1->getArrayConsulta(713,$data,$obBD_conexion);
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script type="text/javascript" src="../VALIDACIONES/act_calcular_depreciacion.js"></script>
        <style>
            th.ui-th-column div{
                white-space:normal !important;
                height:auto !important;
                padding:2px;
            }
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consulta de Asignaciones</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Filtros</legend>
                        <form id="formActivo" name="formActivo" class="form-horizontal normal" action="javascript:listarActivos();"> 
                            <input type="hidden" id="Act_Cod" name="Act_Cod" value="">
                            <input type="hidden" id="Pld_Cod" name="Pld_Cod" value="">
                            <div class="col-md-5 col-sm-6">
                                <div class="form-group">
                                    <label class="col-md-3 control-label label-xs">Activo:</label>  
                                    <div class="col-md-9">
                                        <div class="input-group input-group-xs">
                                            <input type="text" name="activo" id="activo" class="form-control input-xs" placeholder="Seleccione un activo" readonly="">
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-success" onclick="$('#actDialog').dialog('open');"><span class="glyphicon glyphicon-check" title="Buscar activo"></span></button>
                                                <button type="button" class="btn btn-success" onclick="$('#activo').val('');$('#Act_Cod').val('');"><span class="glyphicon glyphicon-eject" title="Limpiar campo"></span></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-3">
                                <div class="form-group">
                                    <div class="col-md-7" align="left">
                                        <button type="submit" name="btn_buscar" id="btn_buscar" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </fieldset>
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Resultados</legend>
                        <div id="horizontal">
                            <table id="dep_mensual"></table>
                            <div id="list_dm_Pager"></div>
                        </div>
                        <div id="imprimir" style="display: none;">
                            <?php // echo $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, 'REPORTE DE ACTIVOS FIJOS - PERIODO <span id="periodo_excel"></span>', '',21,$obBD_conexion); ?>
                        </div>
                        <div style="padding-top: 10px; padding-bottom: 0px;">
                            <button type="button" onclick="$('#imprimir').append($('#dep_mensual').jqGrid('exportGridElement',{nombre:'Depreciaci&oacute;n Mensual',hoja:'Dep. Mensual',caption:true,footer:true,removeHiddens:true,removeCols:[10]}));$.downloadFile($.exportarExcelBlob($('#imprimir').html(),'Activos Fijos'),'activos_fijos_'+$.getDate()+'.xls');" class="btn btn-primary btn-sm start" title="Descargar archivo de Excel"><i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                         </div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
    
    <!--Inicio de di�logo para buscar un activo--> 
    <div id="actDialog" title="B&uacute;squeda de Activos">  
        <form class="form-horizontal normal"></form>
    </div>
    
    <script type="text/javascript">
        $(function(){
            $.createSearchDialog('#actDialog', [
                {label: 'Cod.Int.',name:'Act_Cod',align:'center',key:true,width:50},
                {label: 'Activo',name:'Act_Des', width: 190},
                {label: 'Fecha Cmpra',name:'Act_Fec',align:'center',width: 80},
                {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                    formatter: function (cellvalue, options, rowObject) {
                        return $.getGridButton(cargarActivo, rowObject);
                    }
                }
            ],null, null, null, null, {title: 'Activos', options: [{label: '&nbsp;&nbsp;Nombre&nbsp;&nbsp;', value: 'd'},{label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c'}]});

              $("#dep_mensual").createGrid({
                autowidth:true,
                hidegrid:false,
                responsive:true,
                height:300,
                colModel: [
                    {label:'C&oacute;digo',name:'Act_Cod', width:30,align:'center'},
                    {label:'Activo',name:'Act_Des',align:'center',width:150},
                    {label:'Act. Nro',name:'Aca_Num',align:'center',width:50},
                    {label:'Fec. Emi.',name:'Asg_Fec',align:'center',width:60},
                    {label:'Tipo',name:'Tipo',align:'center',width:50},
                    {label:'Departamento / Custodio',name:'DepaCustodio',align:'center',width:150},
                    {label:'Justificaci&oacute;n',name:'Asg_Raz',align:'center',width:150},
                    {label:'Fec. Ent.',name:'Asg_Fas',align:'center',width:60},
                    {label:'Estado',name:'Estado',align:'center',width:50},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 50, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            return $.getGridButton(imprimir, rowObject,'Imprimir','glyphicon glyphicon-print');
                        }
                    }
                ],
            }, true, "#list_dm_Pager",{}); 
        });

        function listarActivos()
        {
            var dep_men=0,dep_acm=0,act_cod=0,val_lib=0;    
            $.post("<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",$('#formActivo').getData('allActivos'),function(response){
                if(response.length>0){
                    $('#dep_mensual').setRows(response);
                }
            },'json').fail(function(error){console.log(error)});
        }

        function cargarActivo(activo){
            $('#actDialog').dialog('close');
            $('#Act_Cod').val(activo.Act_Cod);
            $('#activo').val(activo.Act_Des);
        }

        function imprimir(activo){
            window.open('./act_pri_activo_1.0.php?Act_Cod='+activo.Act_Cod+'&Dep_Acm='+activo.dep_acm+'&Val_Lib='+activo.val_lib+'&Estado='+activo.estado,'_blank');    
        }

    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>



