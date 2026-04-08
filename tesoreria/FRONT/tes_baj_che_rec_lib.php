<?php
/**
* @abstract Permite liberar (dar de baja) los cheques recibidos (CXCC - cheques_ext)
* @author Antigravity
* @version 1.0
* Fecha de creacion 2026-02-19
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Che;

$hoy = date("Y-m-d");

if(isset($cheqAjax)){
    $data='';
    if ($_POST){
        $data=$_POST;
    }else {
        $data=$_GET;
    }
    
    // Preparar parametros para SQL Case 18 (Busqueda cheques_ext)
    // Parametros Case 18: 
    // $Par_Sql['op_opciones'] ('d' para numero, 'p' para persona/cliente)
    // $Par_Sql['search'] (valor a buscar)
    // $Par_Sql['TipBus'] (Filtro estados)
    // $Par_Sql['txt_fec_ini'], $Par_Sql['txt_fec_fin']
    // $Par_Sql['Bak_Cod'] (Banco)
    // $Par_Sql['Emp_Cod']
    // $Par_Sql['limits'] (Paginacion/Formato grid)

    $params = array();
    $params['Emp_Cod'] = $Ses_Emp_Cod;
    $params['Bak_Cod'] = isset($data['Bak_Cod']) ? $data['Bak_Cod'] : "0";
    
    // Manejo de fechas
    if($periodos=='RANGE'){
        $params['txt_fec_ini'] = $txt_fec_ini;
        $params['txt_fec_fin'] = $txt_fec_fin;
    } else if($periodos==='ALL') {
        $params['txt_fec_ini'] = '2000-01-01'; // Fecha muy antigua
        $params['txt_fec_fin'] = '2100-01-01'; // Fecha muy futura
    } else {
        // Asumiendo que Pec_Fei y Pec_Fef vienen del select de periodos
        $params['txt_fec_ini'] = $Pec_Fei;
        $params['txt_fec_fin'] = $Pec_Fef;
    }

    // Busqueda por numero o general
    if(!empty($data['buscarCheNum'])) {
        $params['op_opciones'] = 'd';
        $params['search'] = $data['buscarCheNum'];
    } else {
        $params['op_opciones'] = 'p'; // Por defecto buscar por cliente (aunque no se envie search)
        $params['search'] = '';
    }

    // Tipos de busqueda (Estados)
    // No enviamos TipBus para que traiga todos los estados excepto 'I' (Anulado), segun logica SQL Case 18.
    // Si enviamos "12345", el SQL haria AND de todos los estados, resultando en 0 filas.
    // $params['TipBus'] = "12345"; 

    // Paginacion para JQGrid
    $page = isset($data['page']) ? $data['page'] : 1;
    $limit = isset($data['rows']) ? $data['rows'] : 100;
    $sidx = isset($data['sidx']) ? $data['sidx'] : 'Che_Fec';
    $sord = isset($data['sord']) ? $data['sord'] : 'DESC';
    
    // Construccion string limits para SQL 18
    $params['limits'] = " ORDER BY $sidx $sord"; 

    // Obtener datos
    $rs_buscar = $obBD_con1->getArrayConsulta(18, $params, $obBD_conexion);
    
    // Formatear respuesta JSON para JQGrid (Formato esperado por $.getDataJson)
    $responce=array('success'=>true,'rows'=>$rs_buscar);
    $obBD_con1->echoJson($responce);
    exit;
}

if(isset($liberarCheq)){
    $data=$_POST;
    // Liberar en CXCC significa Dar de Baja (Anular) -> Estado 'I'
    // Usamos SQL Case 27: UPDATE cheques_ext SET Che_Est ='$Par_Sql[Che_Est]' ... where Che_Cod=$Par_Sql[Che_Cod]
    
    $params = array();
    $params['Che_Cod'] = $data['Che_Cod'];
    $params['Che_Est'] = 'I'; // Inactivo / Anulado
    $params['Che_Num'] = '0'; // Liberar el numero de cheque para reutilizarlo
    
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(27, $params, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true, 'message'=>'El cheque ha sido dado de baja correctamente.');
    } else {
        $responce=array('success'=>false, 'message'=>'No se pudo realizar la transacción!', 'error'=>$obBD_con1->MsgError);
    }
    echo json_encode($responce);
    exit;
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>
        <TITLE><?Php echo "Liberar Cheques Recibidos [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
	</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Liberar (Dar de Baja) Cheques Recibidos CXCC<?Php if(isset($periodo)) echo $periodo; ?></h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <form action="javascript:LoadCheque();" method="post" name="form1" id= "form1" class="form-horizontal normal">
                        <div class="col-xs-5">
                             <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros de B&uacute;squeda</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Banco:</label>
                                     <div class="col-sm-10">
                                        <select id="Bak_Cod" name="Bak_Cod" onchange="LoadCheque();" class="form-control input-xs" >
                                            <option value="0"><< TODOS >></option>
                                            <?php
                                                // Cargar Bancos activos (Case 17 - Bancos Emisores)
                                                // Re-uso de query correcta
                                                $rs_bancos = $obBD_con1->getArrayConsulta(17, '', $obBD_conexion);
                                                foreach ($rs_bancos as $row){  ?>
                                                        <option value="<?php echo $row['Bak_Cod']; ?>"><?php echo $row['Bak_Des']; ?></option>
                                            <?php } ?>
                                        </select>
                                     </div>
                                </div>
                                <div class="form-group">
                                  <label for="buscarCheNum" class="col-sm-2 control-label label-xs">Cheque N.:</label>
                                  <div class="col-sm-5">
                                      <input type="number" min="0" step="1" name="buscarCheNum"  id="buscarCheNum" class="form-control input-xs nospin" onchange="LoadCheque();" value=""/>
                                  </div>
                                </div>

                             </fieldset>
                        </div>
                        <div class="col-xs-5">
                             <fieldset class="exa-fieldset">
                                 <legend class="Titulos2">Rango de Fechas</legend>
                                    <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Rango:</label>
                                    <div class="col-sm-5">
                                        <div id="pec_values" style="display: none;"><input type="text" name="Pec_Cod" /><input type="text" name="Pec_Fei" /><input type="text" name="Pec_Fef" /></div>
                                        <select class="form-control input-xs"  onchange="if(this.value!=='ALL'&&this.value!=='RANGE'){setPeriodo();LoadCheque();} if(this.value==='RANGE'){ $('#rangeDates').find('input').removeAttr('disabled'); }else{ $('#rangeDates').find('input').attr('disabled','disabled'); } " id="periodos" name="periodos"  required="">
                                            <?php
                                                $row_rs_periodos = $obBD_con1->getArrayConsulta(384, $Ses_Emp_Cod, $obBD_conexion);
                                                if (count($row_rs_periodos) > 0){
                                                    $periodo = current($row_rs_periodos);
                                                    foreach ($row_rs_periodos as $row){
                                                ?><option value="<?php echo $row['Pec_Cod']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                    }
                                                } ?>
                                            <option value="RANGE"><< POR FECHAS >></option>
                                            <option value="ALL"><< TODOS >></option>
                                        </select>
                                    </div>
                                </div>
                                    <div id="rangeDates" class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Desde:</label>
                                        <div class="col-sm-4">
                                             <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs" style="text-align: center;" disabled />
                                        </div>
                                        <label class="col-sm-1 control-label label-xs">Hasta:</label>
                                        <div class="col-sm-4">
                                            <input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs" style="text-align: center;" disabled />
                                        </div>
                                    </div>
                             </fieldset>
                        </div>
                        <div class="col-xs-2 center" style="padding-top: 10px;">
                            <button type="button" class="btn btn-success" title="Filtrar Cheques" onclick="this.form.submit()"> <i class="glyphicon glyphicon-search"></i> <span>Buscar</span> </button>
                        </div>
                    </form>
                    <div class="col-xs-12" style="min-height: 360px;">
                        <table id="list"></table>
                        <div id="listPager"></div>
                    </div>
                </div>
        </div>
    </div>

<script>
    var periodos=<?php if (isset($row_rs_periodos) && count($row_rs_periodos) > 0) echo json_encode($row_rs_periodos); else echo 'new Array()';?>;
    function setPeriodo(){ if(periodos.length>0){  $('#pec_values').setData(getPeriodo()); } }
    
    function LibCheque(ro){
        $.createDialogConfirm('Est&aacute; seguro que desea DAR DE BAJA este Cheque Recibido?<br>Esta acci&oacute;n anular&aacute; el cheque del sistema.',null,function(){
            $.saveDataJson('',{liberarCheq:true, Che_Cod: ro.Che_Cod},function(res){
                LoadCheque();
            });
        },function(){ });
    }

    function setCaption(){
       var aux=($('#periodos').val()!=='RANGE'&&$('#periodos').val()!=='ALL'?' del Periodo '+getPeriodo()["Periodo"]:'');
       $("#list").jqGrid('setCaption', 'Listado de Cheques Recibidos CXCC'+aux);
    }

    function getPeriodo(){
        if($("#periodos").val()==='ALL'||$("#periodos").val()==='RANGE') return {};
        if(periodos.length===0){return new Array();}
        for(var i=0;i<periodos.length;i++){
            if(periodos[i]['Pec_Cod']+''===$("#periodos").val()) return periodos[i];
        }
    }
    setPeriodo();
</script>

    <script>
        function LoadCheque(){
          $.getDataJson($("#list"),$("#form1").getData('cheqAjax'), function(response){
             setCaption();
             $("#list").setRows(response['rows']);
             return false;
           });
         }
        $(document).ready(function () {
            var gridList=$("#list");
            gridList.createGrid({
                caption:' ',height: 270,cmTemplate: {sortable:true}, sortname:'Che_Fec',sortorder:"asc",pgbuttons: false,pgtext: null,
                colModel: [
                    { label: 'Fecha', name: 'Che_Fec', width: 45 ,align:"center", sorttype:"date"},
                    { label: 'Banco', name: 'Bak_Des', width: 80 },
                    { label: 'No. Cheque', name: 'Che_Num', width: 35, align:"center",sorttype:"int"},
                    { label: 'Cliente / Beneficiario', name: 'Cli_Nom', width: 100 },
                    { label: 'Observaci&oacuten', name: 'Che_Obs', width: 90 },
                    { label: 'Valor', name: 'Che_Val', width: 45, sorttype:"currency", align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                    { label: 'Estado', name: 'Che_Est', width: 30, align:"center", formatter: function(cellvalue){
                        if(cellvalue=='A') return 'Activo/No Cobrado';
                        if(cellvalue=='C') return 'Cobrado';
                        if(cellvalue=='P') return 'Protestado';
                        if(cellvalue=='I') return 'Anulado';
                        return cellvalue;
                    }},
                    { label: 'Cod.', name: 'Che_Cod', key: true, width: 50,align:"center", hidden:true },
                    { label:'Liberar/Baja', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:LibCheque,
                            conditional:function(ro){
                                    return (ro.Che_Est !== 'I' && parseInt(ro.used_in_payment) == 0); // Solo mostrar si no esta anulado y no esta en uso
                            },
                            caseFalse:function(ro){
                                    if(parseInt(ro.used_in_payment) > 0) return $.createIcon('locked',null,'title="En uso en un cobro activo"');
                                    return $.createIcon('cancel red',null,'title="Dado de Baja"');
                            },
                            model: {icon: 'trash', title: 'Dar de Baja'} 
                        }
                    }
                ]
            },true,'#listPager',{refresh: false});

            $.createDateRange('#txt_fec_ini','#txt_fec_fin');
        });
    </script>

</BODY>
</HTML>
