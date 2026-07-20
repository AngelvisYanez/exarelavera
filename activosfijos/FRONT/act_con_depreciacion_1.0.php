<?php
/**
 * @abstract Permite realizar la consulta de la depreciaci�n por un activo o por todos los registrados
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci?n  2016-09-09
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_depreciacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Depreciacion($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_Depreciacion;

/* Secci�n para presentar los activos que constan en la secci�n de depreciaci�n (tabla activo_deprecia) */
if (isset($actAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Suc_Cod"] = $Ses_Suc_Cod;
    $contar = $obBD_con1->getRowConsulta(13, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(13, $data, $obBD_conexion);
    }
    echo json_encode($responce);
    exit();
}
/* Secci�n para listar el plan de cuentas */
if (isset($cuentaAjax)) {
    $contar = $obBD_con1->getRowConsulta(14, $search . '*' . $Ses_Emp_Cod . '*' . $pec_cod . '*' . $op_opciones . '*', $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(14, $search . '*' . $Ses_Emp_Cod . '*' . $pec_cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion);
    echo json_encode($responce);
    exit();
}
/* Secci�n para presentar los periodos contables */
if (isset($periodoContable)) {
    $response = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
    echo json_encode($response);
    exit();
}
/* Secci�n para extraer todos los activos de la tabla del mismo nombre */
if (isset($allActivos)) {
    $data = $_POST;
    $data['Suc_Cod'] = $Ses_Suc_Cod;
    $response = $obBD_con1->getArrayConsulta(2, $data, $obBD_conexion);
    $actii = array();
    foreach ($response as &$row) {
        //Secci�n para cargar la configuracion de activos
        $rs_configuracion = $obBD_con1->getRowConsulta(11, $Ses_Suc_Cod, $obBD_conexion);
        $row['Cfg_Ddp'] = $rs_configuracion['Cfg_Ddp'];
        $row['Cfg_Por'] = $rs_configuracion['Cfg_Por'];
        $row['Act_Cos'] = $row['Act_Val'] - $row['Act_Res'];
        $row['Meses'] = array();
        //Secci�n para extraer los registros de un activo dentro de la tabla activo_deprecia
        $depreciacion = $obBD_con1->getArrayConsulta(12, $row['Act_Cod'], $obBD_conexion);
        $row['Meses'] = $depreciacion;
    } unset($row);
    foreach ($response as $row) {
        foreach ($row['Meses'] as $row0) {
            $ban = true;
            $descomponer = explode("-", $row0["Acd_Fpd"]);
            $row1['Pec_Cod']=$row0["Pec_Cod"];
            foreach ($actii as &$row1) {
                if ($row1['Act_Cod'] == $row['Act_Cod'] && $row1['anio'] == $descomponer[0]) {
                    $ban = false;
                    array_push($row1['depre'], $row0["Acd_Fpd"]);
                }
            } unset($row1);
            if ($ban) {
                array_push($actii, array_merge($row, array(anio => $descomponer[0], Pec_Cod=>$row0["Pec_Cod"],depre => array(0 => $row0["Acd_Fpd"]))));
            }
        }
    }
    foreach ($actii as &$row1) {
        unset($row1['Meses']);
    } unset($row1);
    echo json_encode(array(activos => $actii));
    exit();
}
/* Secci�n para obtener el Com_Cod de la tabla activo_deprecia, seg�n el a�o y mes requerido */
if (isset($buscarCom_Cod)) {
    //Secci�n para listar todos los registros de la tabla activo_deprecia
    $rs_activo_deprecia = $obBD_con1->getArrayConsulta(15, $Ses_Suc_Cod, $obBD_conexion);
    foreach ($rs_activo_deprecia as $value) {
        $descomponer = explode("-", $value["Acd_Fpd"]);
        $anio1 = $descomponer[0];
        $mes1 = $descomponer[1];
        if (($anio1 == $anio) && ($mes1 == $mes)) {
            $Com_Cod = $value["Com_Cod"];
        }
    }
    echo $Com_Cod;
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../VALIDACIONES/act_calcular_depreciacion.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Depreciaci&oacute;n de Activos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtros</legend>
                            <div id="tabs" class="ui-tab-fix">
                                <ul style="font-size: 12px;">
                                    <li><a href="#tab_dep">Depreciaci&oacute;n</a></li>
                                    <li><a href="#tab_asi">Asiento Contable</a></li>
                                </ul>
                                <div id="tab_dep">
                                    <form id="formDepreciacion" name="formDepreciacion" class="form-horizontal normal" action="javascript:calcular();"> 
                                        <input type="hidden" id="Act_Cod" name="Act_Cod" value="">
                                        <input type="hidden" id="Pld_Cod" name="Pld_Cod" value="">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="col-md-3 control-label label-xs">Activo:</label>  
                                                        <div class="col-md-9">
                                                            <div class="input-group input-group-xs">
                                                                <input type="text" name="activo" id="activo" class="form-control input-xs" placeholder="Seleccione un activo" readonly="">
                                                                <span class="input-group-btn">
                                                                    <button type="button" class="btn btn-success" onclick="$('#actDialog').dialog('open');"><span class="glyphicon glyphicon-check" title="Buscar activo"></span></button>
                                                                    <button type="button" class="btn btn-success" onclick="$('#activo').val(''); $('#Act_Cod').val('');"><span class="glyphicon glyphicon-eject" title="Limpiar campo"></span></button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="col-md-3 control-label label-xs">Per&iacute;odo Inicio:</label>  
                                                        <div class="col-md-4">
                                                            <select name="Pec_Cod" id="Pec_Cod" class="form-control input-xs"></select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-3 control-label label-xs">Per&iacute;odo Fin:</label>  
                                                        <div class="col-md-4">
                                                            <select name="Pec_Cod1" id="Pec_Cod1" class="form-control input-xs"></select>
                                                        </div>
                                                        <div class="col-md-3" align="left">
                                                            <button type="submit" name="btn_buscar" id="btn_buscar" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div id="tab_asi">
                                    <form class="form-horizontal normal">
                                        <div class="row">
                                            <div class="col-sm-7">
                                                <div class="form-group">
                                                    <label class="col-md-3 control-label label-xs">Per&iacute;odo Contable:</label>  
                                                    <div class="col-md-4">
                                                        <select name="Pec_Cod2" id="Pec_Cod2" class="form-control input-xs"></select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-3 control-label label-xs">Mes:</label>  
                                                    <div class="col-md-4">
                                                        <select name="mes" id="mes" class="form-control input-xs">
                                                            <option value="0">Seleccione</option>
                                                            <option value="01">Enero</option>
                                                            <option value="02">Febrero</option>
                                                            <option value="03">Marzo</option>
                                                            <option value="04">Abril</option>
                                                            <option value="05">Mayo</option>
                                                            <option value="06">Junio</option>
                                                            <option value="07">Julio</option>
                                                            <option value="08">Agosto</option>
                                                            <option value="09">Septiembre</option>
                                                            <option value="10">Octubre</option>
                                                            <option value="11">Noviembre</option>
                                                            <option value="12">Diciembre</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3" align="left">
                                                        <button type="button" name="btn_bus" id="btn_bus" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultados</legend>
                            <div id="horizontal">
                                <table id="dep_mensual"></table>
                                <div id="list_dm_Pager"></div>
                            </div>
                            <div id="imprimir" style="display: none;">
                                <?php echo $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, 'REPORTE DE ACTIVOS FIJOS', '', 21, $obBD_conexion); ?>
                            </div>
                            <div style="padding-top: 10px; padding-bottom: 0px;">
                                <button type="button" onclick="$('#imprimir').append($('#dep_mensual').jqGrid('exportGridElement', {nombre:'Depreciaci&oacute;n Mensual', hoja:'Dep. Mensual', caption:true, footer:true, removeHiddens:true})); $.downloadFile($.exportarExcelBlob($('#imprimir').html(), 'Activos Fijos'), 'activos_fijos_' + $.getDate() + '.xls');" class="btn btn-primary btn-sm start" title="Descargar archivo de Excel"><i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
        <!-- Inicio de di�logo para buscar un activo depreciado -->
        <div id="actDialog" title="B&uacute;squeda de activos">
            <form class="form-horizontal normal">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros</legend>
                    <div class="form-group">
                        <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
                        <div class="col-md-8 radioset">
                            <input type="radio" name="op_opciones" id="rad1" value="d" checked="" onclick="setfocus(this.form.search)"/><label for="rad1">&nbsp;&nbsp;Activo&nbsp;&nbsp;</label> 
                            <input type="radio" name="op_opciones" id="rad2" value="c" onclick="setfocus(this.form.search)"/><label for="rad2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                            <input type="radio" name="op_opciones" id="rad3" value="f" onclick="setfocus(this.form.search)"/><label for="rad3">&nbsp;&nbsp;Fecha&nbsp;&nbsp;</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label label-xs">B&uacute;squeda:</label>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" id="search_activo" name="search" class="form-control input-sm" onkeydown="if (event.keyCode === 13)this.form.submit()" autofocus="" placeholder="Ingrese activo a buscar..." />
                                <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar activo"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>

        <script type="text/javascript">
            $(function(){
                //Secci�n para crear tabs 
                $("#tabs").createTabs({});
                //Inicio de di�logo para activos
                $.createSearchDialog('#actDialog', [
                    {label:'Cod.Int.', name:'Act_Cod', key:true, width:20, align:'center'},
                    {label:'Inicio Depreciaci&oacute;n', name:'Act_Fec', width: 35, align:'center'},
                    {label:'Activo', name:'Act_Des', width: 90},
                    {label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name:'act1', width:18, align:'center', viewable: false,
                        formatter:function (cellvalue, options, rowObject){
                            return '<span class="btn btn-success btn-xs" title="Seleccionar" onclick="cargarActivo(\'' + rowObject.Act_Cod + '\',\'' + rowObject.Act_Des + '\');"><i class="glyphicon glyphicon-arrow-right"></span>';
                        }
                    }
                ]);
                //Secci�n para buscar Com_Cod
                $('#btn_bus').click(function(){
                    var descomponer=$("#Pec_Cod2").val().split('*');
                    $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{buscarCom_Cod:true,anio:descomponer[1],mes:$("#mes").val()},function(response){
                        var descomponer1=response.split('*');
                        if(descomponer1[0]!=''){
                            window.open('../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo='+descomponer1[0]+'&tabla=proveedore&campo=Prv_Cod&tipo='+descomponer1[1]+'&Pec_Cod='+descomponer[0],'_blank');    
                        }else{
                            $.alert('Comprobante no existe..!!');
                        }
                    });
                });
                //Secci�n para cargar el comobobox del periodo contable v�a ajax
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", {periodoContable:true}, function(response) {
                    if (response.length > 0){
                    var options = "";
                        options += "<option value='0'>Todos</option>";
                        for (var i = 0, z = response.length; i < z; i++){
                            options += "<option value='" + response[i].Pec_Cod +"*"+response[i].Periodo+ "'>" + response[i].Periodo + "</option>";
                        }
                        $("[id^='Pec_Cod']").html(options);
                    } else{$.alert(response['message']);}
                },'json').fail(function() { $.alert(); });
                //Llamo a la funci�n cargarGrid para inicializar el jqgrid
                cargarGrid();    
            });
            
            /*** INICIO DE FUNCIONES NECESARIAS PARA EL MANEJO DE DATOS ***/
            //Funci�n para cargar el activo seleccionado
            function cargarActivo(act_cod, activo){
                $('#actDialog').dialog('close');
                $('#Act_Cod').val(act_cod);
                $('#activo').val(activo);
            }
            //Secci�n para calcular el proceso de depreciaci�n
            var activos_dep;
            function calcular(){
                var dep_men = 0, dep_acm = 0, act_cod = 0, val_lib = 0,arreglo=[];
                //Se descompone el periodo de inicio
                var descomponer_pini=$('#Pec_Cod').val().split('*');var pecCod_pini=descomponer_pini[0];var anio_pini=descomponer_pini[1];
                //Se descompone el periodo de fin
                var descomponer_pfin=$('#Pec_Cod1').val().split('*');var pecCod_pfin=descomponer_pfin[0];var anio_pfin=descomponer_pfin[1];
                $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", $('#formDepreciacion').getData('allActivos'), function(response){
                    if (response['activos'].length > 0){
                        activos_dep = new Array();
                        activos_dep = response['activos'];
                        for (var i = 0; i < activos_dep.length; i++){
                            var dias_anio = 0, fin_dep = 0,indice=0;
                            if (act_cod === 0){act_cod = activos_dep[i]['Act_Cod']; }
                            if (act_cod !== activos_dep[i]['Act_Cod']){dep_acm = 0; val_lib = 0; act_cod = activos_dep[i]['Act_Cod']; }
                            (activos_dep[i]['Cfg_Ddp'] === 'DT')?dias_anio = 360:dias_anio = 365;
                            var dep_anu = activos_dep[i]['Act_Cos'] / activos_dep[i]['Act_Ann'];
                            var dep_diaria = dep_anu / dias_anio;
                            var meses = activos_dep[i]['depre'];
                            var descomponer_ff = activos_dep[i]['Act_Ffd'].split('-');
                            var anioff = descomponer_ff[0], mesff = descomponer_ff[1], diaff = descomponer_ff[2];
                            var anio=activos_dep[i]['anio'];
                            if(pecCod_pini===pecCod_pfin){ 
                                if((activos_dep[i]['Pec_Cod']>=pecCod_pini)&&(activos_dep[i]['Pec_Cod']<=pecCod_pfin))
                                {
                                    arreglo.push({'anio':activos_dep[i]['anio'],'Pld_Des':activos_dep[i]['Pld_Des'],'Act_Des':activos_dep[i]['Act_Des'],'Act_Fec':activos_dep[i]['Act_Fec'],
                                        'Act_Can':activos_dep[i]['Act_Can'],'Act_Val':activos_dep[i]['Act_Val'],'Act_Res':activos_dep[i]['Act_Res']});
                                }
                            }else{
                                if((anio>=anio_pini)&&(anio<=anio_pfin))
                                {
                                    arreglo.push({'anio':activos_dep[i]['anio'],'Pld_Des':activos_dep[i]['Pld_Des'],'Act_Des':activos_dep[i]['Act_Des'],'Act_Fec':activos_dep[i]['Act_Fec'],
                                        'Act_Can':activos_dep[i]['Act_Can'],'Act_Val':activos_dep[i]['Act_Val'],'Act_Res':activos_dep[i]['Act_Res']});
                                }
                            }
                            for (var j = 0; j < meses.length; j++){
                                var descomponer = meses[j].split('-');
                                var anio = descomponer[0], mes = descomponer[1];
                                if ((anio === anioff) && (mes === mesff)){fin_dep = diaff; }
                                dep_men = dep_mensual(meses[j], dep_diaria, activos_dep[i]['Cfg_Ddp'], fin_dep);
                                dep_acm = parseFloat(dep_acm) + parseFloat(dep_men);
                                if((anio>=anio_pini)&&(anio<=anio_pfin)){
                                    indice=arreglo.length-1;
                                    $.extend(arreglo[indice],{['t' + mes]:dep_men[0].toFixed(2)});
                                }
                                $.extend(activos_dep[i],{['t' + mes]:dep_men[0].toFixed(2)});
                            }
                            val_lib = activos_dep[i]['Act_Val'] - dep_acm;
                            $.extend(activos_dep[i], {'dep_acm':dep_acm.toFixed(2)});
                            $.extend(activos_dep[i], {'val_lib':val_lib.toFixed(2)});
                            if((anio>=anio_pini)&&(anio<=anio_pfin)){
                                $.extend(arreglo[indice], {'dep_acm':dep_acm.toFixed(2)});
                                $.extend(arreglo[indice], {'val_lib':val_lib.toFixed(2)});
                            }
                        }
                        if(($('#Pec_Cod').val()==='0')&&($('#Pec_Cod1').val()==='0')){$('#dep_mensual').setRows(activos_dep);}
                        else{if(arreglo.length===0){$.alert('No existen registros en ese periodo..!!');}$('#dep_mensual').setRows(arreglo);}
                    }
                }, 'json');
            }
            //Funci�n para inicializar el jqgrid que presenta la depreciaci�n mensual
            function cargarGrid(){
                $('#dep_mensual').jqGrid('clearGridData', true);
                $('#dep_mensual').jqGrid({
                    url:'<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                        mtype:'GET',datatype:'local',regional:'es',autowidth:true,shrinkToFit:false,hidegrid:false,
                        responsive:true,height:250,cmTemplate:{sortable:false},caption:'DEPRECIACI&Oacute;N MENSUAL',
                        colModel:[
                        {label:'A&ntilde;o', name:'anio', width:80, align:'center'},
                        {label:'Cuenta Contable', name:'Pld_Des', align:'center'},
                        {label:'Activo', name:'Act_Des', align:'center', width:250},
                        {label:'Fecha Adquisici&oacute;n', name:'Act_Fec', align:'center', width:110},
                        {label:'Cant.', name:'Act_Can', align:'center', width:50},
                        {label:'Costo', name:'Act_Val', align:'center', width:100},
                        {label:'Valor Residual', name:'Act_Res', align:'center', width:100, summaryType:'count', summaryTpl : 'Total:'},
                        {label:'Enero', name:'t01', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Febrero', name:'t02', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Marzo', name:'t03', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Abril', name:'t04', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Mayo', name:'t05', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Junio', name:'t06', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Julio', name:'t07', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Agosto', name:'t08', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Septiembre', name:'t09', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Octubre', name:'t10', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Noviembre', name:'t11', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Diciembre', name:'t12', align:'center', width:70, sorttype:"float", formatter:formatVacio, summaryType:'sum', classes:"columnHighlight3"},
                        {label:'Dep<br>Acum.', name:'dep_acm', align:'center', width:100, sorttype:"float", formatter:formatVacio, classes:"columnHighlight4"},
                        {label:'Saldo<br>Libros', name:'val_lib', align:'center', width:100, sorttype:"float", formatter:formatVacio, classes:"columnHighlight4"},
                        {label:'Cta. Contable', name:'Pld_Des'}
                        ], rowNum:10000, width:900, pager:'list_dm_Pager', viewrecords:true, pgbuttons:false, pgtext:null, sortorder:'asc', grouping:true, sortname: 'anio',
                        groupingView:{
                        groupField:['Pld_Des', 'anio'],
                                groupColumnShow:[false, true],
                                groupText:['<div><span style="float:left;"><b>{0}</b></span></div>', '<div><span style="float:left;"><b>Periodo: {0}</b></span></div>'],
                                groupCollapse:true,
                                groupSummary : [false, true],
                                groupOrder:['asc']
                        },
                        footerrow: true, userDataOnFooter: false,
                        loadComplete: function () {
                            var Sum_dep_acum = $('#dep_mensual').jqGrid('getCol', 'dep_acm', false, 'sum');
                            var Sum_val_libros = $('#dep_mensual').jqGrid('getCol', 'val_lib', false, 'sum');
                            var Sum_01 = $('#dep_mensual').jqGrid('getCol', 't01', false, 'sum');
                            var Sum_02 = $('#dep_mensual').jqGrid('getCol', 't02', false, 'sum');
                            var Sum_03 = $('#dep_mensual').jqGrid('getCol', 't03', false, 'sum');
                            var Sum_04 = $('#dep_mensual').jqGrid('getCol', 't04', false, 'sum');
                            var Sum_05 = $('#dep_mensual').jqGrid('getCol', 't05', false, 'sum');
                            var Sum_06 = $('#dep_mensual').jqGrid('getCol', 't06', false, 'sum');
                            var Sum_07 = $('#dep_mensual').jqGrid('getCol', 't07', false, 'sum');
                            var Sum_08 = $('#dep_mensual').jqGrid('getCol', 't08', false, 'sum');
                            var Sum_09 = $('#dep_mensual').jqGrid('getCol', 't09', false, 'sum');
                            var Sum_10 = $('#dep_mensual').jqGrid('getCol', 't10', false, 'sum');
                            var Sum_11 = $('#dep_mensual').jqGrid('getCol', 't11', false, 'sum');
                            var Sum_12 = $('#dep_mensual').jqGrid('getCol', 't12', false, 'sum');
                            $('#dep_mensual').jqGrid('footerData','set',{Act_Res: 'TOTALES:',t01:Sum_01, t02:Sum_02, t03:Sum_03, t04:Sum_04, t05:Sum_05, t06:Sum_06, t07:Sum_07, t08:Sum_08, t09:Sum_09, t10:Sum_10, t11:Sum_11, t12:Sum_12,dep_acm: Sum_dep_acum, val_lib:Sum_val_libros});
                        }
                    });
                $('#dep_mensual').jqGrid('setGroupHeaders', {
                    useColSpanStyle: true,
                    groupHeaders:[{startColumnName: 't01', numberOfColumns: 12, titleText: 'Meses'}]
                });
                $('#dep_mensual').jqGrid('setFrozenColumns');
                function formatVacio(cellValue, options, rowdata, action) {
                    if (cellValue === "" || cellValue * 1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "-";
                    var number = parseFloat(cellValue).toFixed(2);
                    return number;
                }
                $('#dep_mensual').jqGrid('resizeGrid');
            }
        </script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    </BODY>
</HTML>



