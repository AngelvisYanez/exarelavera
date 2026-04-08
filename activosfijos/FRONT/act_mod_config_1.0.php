<?php
/**
 * @abstract Permite realizar la edición de la configuración de activos.
 * @author José Ambuludí
 * @version 1.0
 * Fecha de creación  2016-12-08
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_config.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexión
*/
$obBD_conexion = new Class_Log_Conexion_Config($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Config;

//Sección para verificar si ya se registro un ingreso en la tabla config_activo
if(isset($verificarConfig)){
    $rs_verificar=$obBD_con1->getRowConsulta(8, $Ses_Suc_Cod, $obBD_conexion);
    if(count($rs_verificar)>0){
        $response['existe']=true;
    }else{$response['existe']=false;}
    echo json_encode($response);
    exit();
}
//Sección para comprobar de que no exista una configuración ya registrada
if(isset($comprobar)){
    $rs_comprobar=$obBD_con1->getArrayConsulta(9, $Ses_Suc_Cod, $obBD_conexion);
    if(count($rs_comprobar)>0){
        $response['existe']=true;
    }else{$response['existe']=false;}
    echo json_encode($response);
    exit();
}
//Sección para cargar datos registrados
if(isset($cargar)){
    $response['config']=$obBD_con1->getRowConsulta(8, $Ses_Suc_Cod, $obBD_conexion);
    $response['porcent']=$obBD_con1->getArrayConsulta(5, $Ses_Suc_Cod, $obBD_conexion);
    echo json_encode($response);
    exit();
}
//Sección para insertar un registro 
if(isset($updateConfiguracion)){
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(7,$Cfg_Cod.'*'.$Cfg_Ddp.'*'.$Cfg_Por,$obBD_conexion);
    if($Insert>0){
        if(count($porcentaje)>0&&($Cfg_Por!='N')){
            foreach ($porcentaje as $value){
                $obBD_con1->operacionobBD(1,$Ses_Suc_Cod.'*'.$value['Apr_Des'].'*'.$value['Apr_Por'],$obBD_conexion);
            }
        }
    }
    else{
        if(count($porcentaje)>0&&($Cfg_Por!='N')){
            foreach ($porcentaje as $value){
                $obBD_con1->operacionobBD(4,$value['Apr_Cod'].'*'.$value['Apr_Des'].'*'.$value['Apr_Por'],$obBD_conexion);
            }
        }
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
    exit();
}
//Sección para eliminar un registro de porcentaje de la tabla activo_poorcent
if(isset($deletePorcentaje)){
    $response['success']=false;$response['message']="No se ha logrado realizar la transacci&oacute;n";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(6,$Apr_Cod,$obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){$response['success']=true;}
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <style>
            input[type="radio"] {margin: 1px 0 0;}
            #pag_tpo_center{ display: none; }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar configuraci&oacute;n para el registro de activos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-10">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Modificar Configuraci&oacute;n de Activos</legend>
                            <form id="frm_Con" name="frm_Con" class="form-horizontal normal" action="javascript:saveForm();">
                                <div id="modificar">
                                    <!--Clave primaria de la tabla config_activo-->
                                    <input type="hidden" id="Cfg_Cod" name="Cfg_Cod">
                                    <!--Campo que especifica que se hará un insert en la tabla activo_porcent-->
                                    <input type="hidden" id="Insert" name="Insert" value="0">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">D&iacute;as de depreciar&oacute;n:</label>
                                        <div class="col-sm-5 radioset">
                                            <input id="rad_ba1" name="Cfg_Ddp" type="radio" value="DT" /><label for="rad_ba1">&nbsp;&nbsp;D&Iacute;AS TRIBUTARIOS&nbsp;&nbsp;</label>
                                            <input id="rad_ba2" name="Cfg_Ddp" type="radio" value="DM" /><label for="rad_ba2">&nbsp;&nbsp;D&Iacute;AS MENSUALES&nbsp;&nbsp;</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2"></label>
                                        <div class="col-sm-9">
                                            <div class="alert alert-info" role="alert">
                                                <p id="dt"><span class="glyphicon glyphicon-ok-circle"></span> D&iacute;as tributarios considera <code>30 d&iacute;as mensuales</code> y <code>360 d&iacute;as anuales.</code></p>
                                                <p id="dm" style="display: none;"><span class="glyphicon glyphicon-ok-circle"></span> D&iacute;as mensuales considera <code>d&iacute;as completos de cada mes</code> y <code>365 d&iacute;as anuales.</code></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2"></label>
                                        <div id="porcentaje" class="col-sm-9">
                                            <fieldset class="exa-fieldset">
                                                <legend class="Titulos2">Registro de porcentajes de depreciaci&oacute;n</legend>
                                                <table id="tbl_por"></table><!--tbl_por=>tabla_porcentajes-->
                                                <div id="pag_tpo"></div><!--pag_tpo=>paginación de la tabla porcentaje-->
                                            </fieldset>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2"></label>
                                        <div id="porcentaje" class="col-sm-6">
                                            <button type="submit" id="btn_gua" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                        </div>    
                                    </div>
                                </div>
                                <div id="alerta" class="col-sm-12">
                                    <div class="alert alert-info" role="alert">
                                        <h4 class="alert-heading"><span class="glyphicon glyphicon-info-sign"></span> Atenci&oacute;n</h4>
                                        <p style="text-align: justify;">Al momento no se ha detectado el registro de la configuraci&oacute;n inicial de activos fijos, por lo tanto las opciones
                                            para su modificaci&oacute;n no estan disponibles.</p>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            var arrayPorcentaje=[{Apr_Des:'Inmuebles (excepto terrenos), naves, aeronaves, barcazas y similares 5% anual.',Apr_Por:'5'},
                                {Apr_Des:'Instalaciones, maquinarias, equipos y muebles 10% anual.',Apr_Por:'10'},
                                {Apr_Des:'Vehiculos, equipos de transporte y equipo caminero movil 20% anual.',Apr_Por:'20'},
                                {Apr_Des:'Equipos de computo y software 33.33% anual.',Apr_Por:'33.33'}];
            $(function(){
                comprobar();
                verificar();
                cargar();
                //JGRID DE PORCENTAJES
                $("#tbl_por").jqGrid({
                datatype: "local",regional : 'es',responsive: true,autowidth : true, shrinkToFit: true, height: 150,cmTemplate: {sortable:false},
                colModel: [  
                    { label: 'Apr_Cod', name: 'Apr_Cod',key:true,hidden:true, width: 150 },
                    { label: 'Descripci&oacute;n', name: 'Apr_Des', width: 340,editable:true}, 
                    { label: 'Porcentaje', name: 'Apr_Por', width: 60,align:'center',editable:true}, 
                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                        formatter:function (cellvalue, options, rowObject) {   
                            return  '<span id="eli'+options.rowId+'" class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="eliminarFila(\''+options.rowId+'\',\''+rowObject.Apr_Cod+'\')";><i class="glyphicon glyphicon-trash"></i></span>';
                        }
                    }
                ],
                gridComplete: function() {
                    var ids = jQuery("#tbl_por").jqGrid('getDataIDs');
                    for (var i = 0; i < ids.length; i++) {
                        var cl = ids[i];
                        $("#tbl_por").jqGrid('editRow',cl);
                    }
                },
                rowNum: 40, pager: "#pag_tpo", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
                });
                $("#tbl_por").navGrid('#pag_tpo',{edit:false,add:false,del:false,search:false,refresh:false})
                .navButtonAdd('#pag_tpo',{
                    caption:"Agregar campo",
                    id:'btn_agr',
                    buttonicon:"glyphicon glyphicon-plus", 
                    title:'Agregar',
                    onClickButton: function(){ 
                        var $this=$(this),id=($this.jqGrid('getCol','Apr_Cod',false,'max')+1)||0; 
                        $this.jqGrid('addRowData',id,{'Apr_Cod':id});     
                        $this.jqGrid('editRow',id);
                    }, 
                    position:"last"
                });
                $("#tbl_por").jqGrid('resizeGrid');
                //SECCIÓN PARA MANEJO DE RADIO BUTTONS
                $('#rad_ba1').change(function(){
                    $('#dm').hide();
                    $('#dt').show();
                });
                $('#rad_ba2').change(function(){
                    $('#dm').show();
                    $('#dt').hide();
                });
            });
            //INICIO DE LA SECCIÓN DE FUNCIONES NECESARIAS PARA EL MANEJO DE DATOS
            //Función para verificar si existe un registro la tabla config_activo
            function verificar(){
                $.post("<?Php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",{verificarConfig:true},function(response){
                    if(response['existe']===true){
                        $('#modificar').show();
                        $('#alerta').hide();
                    }
                },'json').fail(function(){$.alert();});
            }
            //Función para comprobar de que no exista al menos un registro en la tabla activo_deprecia
            function comprobar(){
                $.post("<?Php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",{comprobar:true},function(response){
                    if(response['existe']===true){
                        $('#btn_gua').attr('disabled',true);
                        $("input[type=radio]").attr('disabled', true);
                        $("#tbl_por").stopGridEdit();
                        $('#btn_agr').addClass('ui-state-disabled');
                        $.alert('El proceso de depreciaci&oacute;n ya se encuentra iniciado, por tal motivo no se puede modificar la configuración..!!');
                    }
                },'json').fail(function(){$.alert();});
            }
            function cargar(){
                $.post("<?Php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",{cargar:true},function(response){
                    if(response['porcent'].length>0){
                        $("#tbl_por").setRows(response['porcent']);
                    }else{$("#tbl_por").setRowsByIndex(arrayPorcentaje,'Apr_Cod');$('#Insert').val(1);}
                    if(response['config']['Cfg_Ddp']==='DM'){$("#rad_ba2").prop('checked','checked').trigger('change');$('#dm').show();$('#dt').hide();}
                    else{$("#rad_ba1").prop('checked','checked').trigger('change');$('#dm').hide();$('#dt').show();}
                    $('#Cfg_Cod').val(response['config']['Cfg_Cod']);
                },'json').fail(function(){$.alert();});
            }
            //Función para eliminar una fila del jqgrid
            function eliminarFila(index,Apr_Cod){
                $.createDialogConfirm('Desea ELIMINAR el registro seleccionado',null,function(){
                    $('#tbl_por').jqGrid('delRowData',index);
                    $.post("<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING);?>",{'Apr_Cod':Apr_Cod,'deletePorcentaje':true},function(){},'json').fail(function(){$.alert();});
                });
            }
            //Función para guardar un registro
            function saveForm(){
                var data=$('#frm_Con').serializeObject();
                data['porcentaje']=$("#tbl_por").getGridBatch();
                data['updateConfiguracion']=true;
                $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data,function(response){
                    if(response['success']===true){
                        $.alert("Transaccion Realizada con &Eacute;xito!");
                        cargar();
                    }
                },'json').fail(function() { $.alert();});
            }
        </script>
    </BODY>
</HTML>

