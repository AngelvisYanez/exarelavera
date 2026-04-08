<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 2.0
 * Fecha de creaci�n  2019-02-15
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Con;

$hoy = date("Y-m-d");

if(isset($parametrosList)){ 
    $plan=  $obBD_con1->getRowConsulta(346, $Pec_Cod, $obBD_conexion);
    $responce=array('success'=>true, 'rows'=>$obBD_con1->getArrayConsulta('tipo_param.selectWhere', array('Tpa_Est'=>'A','order'=>array('Tpa_Grp ASC','Tpa_Des ASC'), 'group'=>array('Tpa_Abr') ), $obBD_conexion));
    foreach ($responce['rows'] as &$val) {
        $conteo=$obBD_con1->getRowConsulta('plan_param.selectCountWhere',array('where'=>array('plan_cuenta.Pla_Cod'=>$plan['Pla_Cod'],'tipo_param.Tpa_Cod'=>$val['Tpa_Cod'])), $obBD_conexion);
        $val['Param']= $conteo['total']*1>0?'S':'N';
    }
    $obBD_con1->echoJson($responce);
}
if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(347, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(347, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($parametros)){ 
    $plan=  $obBD_con1->getRowConsulta(346, $Pec_Cod_Tpa, $obBD_conexion);
    $responce['rows']=  $obBD_con1->getArrayConsulta(345, $plan['Pla_Cod'].'*'.$Tpa_Cod, $obBD_conexion);
    utf8_encode_deep($responce['rows']); echo json_encode($responce);exit();
}
if(isset($saveNewParam)){
    $data = $_POST;
    $responce['success'] = false;
    $existingParam = $obBD_con1->getRowConsulta(343, $data['Tpa_Abr'], $obBD_conexion);
    if ($existingParam != null) {
        $responce['message'] = "El parametro <u>" . $data['Tpa_Des'] . "</u> ya se encuentra registrado.";
        utf8_encode_deep($responce);
        echo json_encode($responce);
        exit();
    }
    
    $Par_Sql = array( $data['Tpa_Grp'], $data['Tpa_Abr'], $data['Tpa_Des'], $data['cbx_tpd'], $data['cbx_tpa'], $data['cbx_Est'] );
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(344, $Par_Sql, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
        $responce['message'] = $obBD_con1->MsgError;
    }

    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
if(isset($saveParam)){ 
    $data=$_POST;
    $responce['success']=false;
    $tipo_param = $obBD_con1->getRowConsulta(341, $data['Tpa_Cod'], $obBD_conexion);
    $plan=  $obBD_con1->getRowConsulta(346, $data['Pec_Cod'], $obBD_conexion);
    $rows=  $obBD_con1->getArrayConsulta(345, $plan['Pla_Cod'].'*'.$data['Tpa_Cod'], $obBD_conexion);
    $max=count($rows);    
    // valida q no existe la cuenta
    foreach ($rows AS $row){
        if($row['Pld_Cod']==$data['Pld_Cod']){
            $responce['message']="La cuenta <u>$row[Pld_Cdc]</u> - <u>$row[Pld_Des]</u> ya se encuenta registrada para el parametro <u>$tipo_param[Tpa_Des]</u>.";
            utf8_encode_deep($responce); echo json_encode($responce); exit();
        }
    }
    // valida los parametros q permiten una sola cuenta
    if($tipo_param['Tpa_Uni']=='S' && count($rows)>0){
        $responce['message']="Solo se permite <b class=\"red\">UNA</b> cuenta para el parametro <u class='green upper'>$tipo_param[Tpa_Des]</u>.";
        utf8_encode_deep($responce); echo json_encode($responce); exit();
    }
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);       
        $obBD_con1->operacionobBD(348, $data, $obBD_conexion);   
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    utf8_encode_deep($responce);echo json_encode($responce);exit();
}
if(isset($deleteParam)){ 
    $data=filter_input_array(INPUT_POST);
    $responce['success']=false;
    $tipo_param = $obBD_con1->getRowConsulta(341, $data['Tpa_Cod'], $obBD_conexion);
    
    $used=null;
    if($tipo_param['Tpa_Abr']=='CD')
        $used = $obBD_con1->getRowConsulta(350, $data, $obBD_conexion);    
    if($tipo_param['Tpa_Abr']=='CA')
        $used = $obBD_con1->getRowConsulta(351, $data, $obBD_conexion); 
    
    if($used!=null&&isset($used['total'])&&$used['total']*1>0){
        $responce['message']="No se puede eliminar la cuenta <u>$data[Pld_Cdc]</u> - <u>$data[Pld_Des]</u> del parametro <u>$tipo_param[Tpa_Des]</u> porque esta siendo utilizada.<br/><br/> <i>Comuniquese con el Administrador.</i>";
        utf8_encode_deep($responce); echo json_encode($responce);exit();
    }
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);        
    $obBD_con1->operacionobBD(349, $data, $obBD_conexion);     
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

    if($obBD_con1->Error==0){ $responce=array('success'=>true,'message'=>'Se ha eliminado la parametrización!');}else{$responce=array('success'=>false,'message'=>"No se logro eliminar el parametro!",'error'=>$obBD_con1->MsgError);}
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Parametrización General [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Parametrización de Cuentas Contables</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-6">                    
                    <div style="padding-bottom: 10px" class="jqHeaderFirst jqFirst">
                        <table id="list_1"></table>
                        <div id="listPager_1"></div>
                    </div>       
                </div>
                <div class="col-sm-6">
                    <fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Parametros</legend> <!-- Form Name -->
                        <form id="formParam" class="form-horizontal normal"  action="javascript:$('#list_2').Search('#formParam','parametros');"  >
                            <input type="text" class="hidden" id="Tpa_Cod" name="Tpa_Cod" value="" />
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm required" for="Ciu_Cod">Plan de Cuentas:</label>  
                                <div class="col-sm-4">
                                    <?php $rs_periodos = $obBD_con1->getArrayConsulta(342, $Ses_Emp_Cod, $obBD_conexion);  ?>
                                    <select name="Pec_Cod_Tpa" id="Pec_Cod_Tpa" onchange="selectPeriodo();$('#list_2').Search('#formParam','parametros');" class="form-control input-sm" required>  
                                        <?Php                                                                        
                                        if (count($rs_periodos) > 0){
                                        $periodo=$rs_periodos[0];
                                        foreach($rs_periodos as $row){ ?>
                                            <option value="<?Php echo $row['Pec_Cod']; ?>"><?Php echo $row['Periodo']; ?></option>	
                                        <?php }
                                        }//Fin del if ($total_rs_periodo > 0)                                
                                        ?>	                               
                                    </select> 
                                    <script>
                                        var periodos=<?php echo json_encode($rs_periodos); ?>;
                                    </script>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-sm required">Parametro:</label>  
                                <div class="col-sm-9">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-addon alert-warning" name="Tpa_Abr">&nbsp;</span><span class="form-control input-sm upper" name="Tpa_Des"></span>
                                    </div>    
                                </div>
                            </div>    
                        </form>
                    </fieldset>
                    <fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Cuentas Parametrizadas</legend> <!-- Form Name -->
                        <div style="padding-bottom: 10px">
                            <table id="list_2"></table>
                            <div id="listPager_2"></div>
                        </div> 
                        <button onclick="if($('#Tpa_Cod').val()!=='') $('#cuenDialog').dialog('open'); else $.alert('Seleccione un tipo de <u>Parametro</u>.');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm"><i class="glyphicon glyphicon-list-alt"></i> <span>Agregar Cuenta</span></button>
                    </fieldset>
                </div>
            </div> 
        </div>

        <div id="newParamDialog" title="Agregar Nuevo Tipo de Parametro" style="display:none;">
            <form class="form-horizontal normal" id="newParamForm" action="javascript:saveNewParam();">
                <div class="form-group" style="margin-top: 20px;">
                    <label class="col-xs-3 control-label label-xs required">Grupo:</label>
                    <div class="col-xs-6">
                        <input type="text" id="Tpa_Grp" name="Tpa_Grp" class="form-control input-xs" required="" maxlength="50" placeholder="Grupo del parametro..." />
                    </div>
                </div>
                <div class="form-group" style="margin-top: 6px;">
                    <label class="col-xs-3 control-label label-xs required">Abreviación:</label>
                    <div class="col-xs-6">
                        <input type="text" id="Tpa_Abr" name="Tpa_Abr" class="form-control input-xs" required="" maxlength="3" placeholder="Abreviacion del Parametro..." />
                    </div>
                </div>
                <div class="form-group" style="margin-top: 6px;">
                    <label class="col-xs-3 control-label label-xs required">Nombre:</label>
                    <div class="col-xs-6">
                        <input type="text" id="Tpa_Des" name="Tpa_Des" class="form-control input-xs" required="" maxlength="50" placeholder="Nombre del Parametro..." />
                    </div>
                </div>

                <div class="form-group" style="margin-top: 6px;">
                    <label class="col-xs-3 control-label label-xs required">Tpd Unica:</label>
                    <div class="col-xs-2">
                        <select id="cbx_tpd" name="cbx_tpd" class="form-control input-xs" readOnly>
                            <option value="S" selected="">Si</option>
                            <option value="N">No</option>
                        </select>
                    </div>
                    <label class="col-xs-3 control-label label-xs required">Tpa Unica:</label>
                    <div class="col-xs-2">
                        <select id="cbx_tpa" name="cbx_tpa" class="form-control input-xs" readOnly>
                            <option value="S" selected="">Si</option>
                            <option value="N">No</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 6px;">
                    <label class="col-xs-3 control-label label-xs required">Estado:</label>
                    <div class="col-xs-3">
                        <select id="cbx_Est" name="cbx_Est" class="form-control input-xs readOnly" readonly="readonly" disabled="disabled">
                            <option value="A" selected="">Activo</option>
                            <option value="I">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="center">
                    <div clas="separator"></div>
                    <button type="submit" class="btn btn-sm btn-success no" style="margin-top: 6px;">
                        <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script type="text/javascript">
    function saveNewParam() {
        var data = $('#newParamForm').getData('saveNewParam');
        //console.log(data);
        $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', data, function (d) {
            $.saveDataJson('', d, function (r) {
                $("#list_1").trigger('reloadGrid', [{ page: 1 }]);
                $('#newParamForm')[0].reset();
                // $('#newParamDialog').dialog('close');
            });
        });
    }
    $(function() {
        $("#list_1").createGrid({                
            postData: {parametrosList:true, Pec_Cod:$('#Pec_Cod_Tpa').val()}, height: 330, rowNum: 10000000, pgbuttons: false, pgtext: null, caption:'Tipos Parametro',
            colModel: [
                { label: 'Cód.Int.', name: 'Tpa_Cod', key: true, width: 15, align:"center", hidden:true },       
                { label: 'Grupo', name: 'Tpa_Grp', width: 20,align:"center", hidden:true },
                { label: 'Abr.', name: 'Tpa_Abr', width: 30,align:"center" },                          
                { label: 'Tipo Parametro', name: 'Tpa_Des', width: 200, classes:'upper'  },
                { label: 'Usado', name: 'Param', width: 30, align:"center", formatter:'truefalse', formatoptions:{noColor:'red', yesMsg:'Parametrizado', noMsg:'Sin Parametrizar'} } ,
                { label: 'Unico', name: 'Tpa_Uni', width: 30, align:"center", formatter:'truefalse', formatoptions:{ yesMsg:'Permite Una Sola Cuenta', noMsg:'Permite Varias Cuentas', noColor:'grey',}  } ,
                { label: '&nbsp;', name: 'act1', width: 30, formatter:'gridButton', formatoptions:{ action:'selectTipo' } }
            ], grouping:true, groupingView : { groupField : ['Tpa_Grp'], groupColumnShow : [false], groupText:["<div class='txtLeft'><b>{0}</b> <span class='green pull-right'>{1} Parametro(s)</span></div>"] }                
        },false,'#listPager_1',{
            refresh: true, view: true
        }).gridButtonsAdd([ {},
            { caption: 'Nuevo Parametro', buttonicon: 'plus',
                onClickButton: function() {
                    if (!$('#newParamDialog').data('ui-dialog')) {
                        $('#newParamDialog').dialog({
                            height: 250,
                            width: 450,
                            modal: true,
                            resizable: false,
                        });
                    }
                    $('#newParamDialog').dialog('open');
                },
                // Solo mostrar el botón si Ses_Prs_Cod es 1 es decir Administrador
				css: (Ses_Prs_Cod == 1 ? {} : { display: 'none' })
            }
        ]); 
        
        //$( "#tabs" ).tabs(); 
        $("#list_2").createGrid({                
            postData: $("#formParam").getData("parametros"), height: 200, rowNum: 10000000, pgbuttons: false, pgtext: null,
            colModel: [
                { label: 'Cód.Int.', name: 'id', key: true, width: 15,align:"center", hidden:true },                    
                { label: 'Cód.Int.', name: 'Pld_Cod', width: 15,align:"center", hidden:true },                    
                { label: 'Cód.Int.', name: 'Tpa_Cod', width: 15,align:"center", hidden:true },  
                { label: 'Abr.', name: 'Tpa_Abr', width: 35,align:"center", classes:'columnHighlight4' },
                { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 75,align:'center', classes:'columnHighlight3' },                      
                { label: 'Cuenta Contable', name: 'Pld_Des', width: 225  },                               
                { label:'&nbsp;', name: 'act1', width: 35, align: 'center',viewable: false,
                    formatter:function (cellvalue, options, rowObject) { return $.getGridButton(deleteParam,rowObject,"Eliminar","trash",null,"danger");  }
                }
            ]
        },false,'#listPager_2',{refresh: true, view: true}); 
    });
    function selectTipo(tipo){
        $('#formParam').setData(tipo,false);
        $('#list_2').Search('#formParam','parametros');
    }
    function selectPeriodo(){
        $('input[name="Pec_Cod"]').val($('#Pec_Cod_Tpa').val());
        $('input[name="periodo"]').val($('#Pec_Cod_Tpa').find('option:selected').text());
        $('#list_1').jqGrid('setGridParam',{postData: {parametrosList:true, Pec_Cod:$('#Pec_Cod_Tpa').val()}}).jqGrid("clearGridData").trigger('reloadGrid', [{ page: 1 }]);
        $.getDialogGrid('#cuenDialog').jqGrid("clearGridData").trigger('reloadGrid', [{ page: 1 }]);;
    }
    function saveParam(Pld_Cod){ 						
        $.saveDataJson( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{saveParam:true,Pld_Cod:Pld_Cod,Tpa_Cod:$('#Tpa_Cod').val(),Pec_Cod:$('#Pec_Cod_Tpa').val()},function(response){ $("#list_1").trigger('reloadGrid', [{ page: 1 }]); $("#list_2").trigger('reloadGrid'); });
        $('#cuenDialog').dialog('close');
    }
    function deleteParam(data){            
        $.extend(data,{deleteParam:true,Pec_Cod:$('#Pec_Cod_Tpa').val()});
        $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data,function(response){  $("#list_1").trigger('reloadGrid', [{ page: 1 }]); $("#list_2").trigger('reloadGrid', [{ page: 1 }]); });            
    }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA parametros --> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none;">  
        <form class="form-horizontal normal"> 
        <fieldset class="exa-fieldset">
		<legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset" >
                        <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                        <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div>                   
                    <div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label>                       
                        <input name="periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" value="<?php if(isset($periodo['Periodo'])) echo $periodo['Periodo']; ?>" /> 
                        <input name="Pec_Cod" type="hidden" value="<?php if(isset($periodo['Pec_Cod'])) echo $periodo['Pec_Cod']; ?>" /> 
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">                        
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus  class="form-control input-sm "/>
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
        </form> 
    </div> 
    <script>
    // DIALOG BUSCAR CUENTAS            
    $.createSearchDialog('cuenDialog',[
        { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
        { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
        { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
        { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
        { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
        { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
            formatter:function (cellvalue, options, rowObject) { return $.getGridButton(saveParam,rowObject.Pld_Cod,"Agregar Cuenta");}
        }
    ]); 
    </script>
</BODY>
</HTML>