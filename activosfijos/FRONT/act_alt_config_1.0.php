<?php
/**
 * @abstract Permite realizar la configuraci�n para el registro de activos
 * Es decir si desea registrar bajo los d�as tributarios (30 d�as todos los meses) o
 * bajo los d�as completos de cada mes y 365 d�as anuales. Adem�s se indicar� si se aplicar�n
 * los porcentajes estipulados por el SRI.
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci�n  2016-12-07
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_config.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexi�n
*/
$obBD_conexion = new Class_Log_Conexion_Config($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Config;

//Secci�n para comprobar de que no exista una configuraci�n ya registrada
if(isset($comprobar)){
    $rs_comprobar=$obBD_con1->getRowConsulta(3, $Ses_Suc_Cod, $obBD_conexion);
    if(isset($rs_comprobar['Suc_Cod'])){
        $response['existe']=true;
    }else{$response['existe']=false;}
    echo json_encode($response);
    exit();
}

//Secci�n para insertar un registro 
if(isset($saveConfiguracion)){
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    if(count($porcentaje)>0&&($Cfg_Por!='N')){
        foreach ($porcentaje as $value){
            $obBD_con1->operacionobBD(1,$Ses_Suc_Cod.'*'.$value['Apr_Des'].'*'.$value['Apr_Por'],$obBD_conexion);
        }
    }
    $obBD_con1->operacionobBD(2,$Ses_Suc_Cod.'*'.$Cfg_Ddp.'*'.$Cfg_Por,$obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Configuraci&oacute;n para el registro de activos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-10">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Registrar Configuraci&oacute;n de Activos</legend>
                            <form id="frm_Con" name="frm_Con" class="form-horizontal normal" action="javascript:saveForm();">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">D&iacute;as de depreciar&oacute;n:</label>
                                    <div class="col-sm-5 radioset">
                                        <input id="rad_ba1" name="Cfg_Ddp" type="radio" value="DT" checked="" /><label for="rad_ba1">&nbsp;&nbsp;D&Iacute;AS TRIBUTARIOS&nbsp;&nbsp;</label>
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
                                            <div id="pag_tpo"></div><!--pag_tpo=>paginaci�n de la tabla porcentaje-->
                                        </fieldset>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2"></label>
                                    <div id="porcentaje" class="col-sm-6">
                                        <button type="submit" id="btn_gua" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
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
                //JGRID DE PORCENTAJES
                $("#tbl_por").jqGrid({
                datatype: "local",regional : 'es',responsive: true,autowidth : true, shrinkToFit: true, height: 150,cmTemplate: {sortable:false},
                colModel: [  
                    { label: 'Index', key:true,hidden:true, name: 'Index', width: 150 },
                    { label: 'Descripci&oacute;n', name: 'Apr_Des', width: 340,editable:true}, 
                    { label: 'Porcentaje', name: 'Apr_Por', width: 60,align:'center',editable:true}, 
                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                        formatter:function (cellvalue, options, rowObject) {   
                            return  '<span id="eli'+options.rowId+'" class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="eliminarFila(\''+options.rowId+'\')";><i class="glyphicon glyphicon-trash"></i></span>';
                        }
                    }
                ],                                                     
                rowNum: 40, pager: "#pag_tpo", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
                });
                $("#tbl_por").navGrid('#pag_tpo',{edit:false,add:false,del:false,search:false,refresh:false})
                .navButtonAdd('#pag_tpo',{
                    caption:"Agregar campo",
                    id:'btn_agr',
                    buttonicon:"glyphicon glyphicon-plus", 
                    title:'Agregar',
                    onClickButton: function(){ 
                        public $this=$(this),id=($this.jqGrid('getCol','Index',false,'max')+1)||0; 
                        $this.jqGrid('addRowData',id,{'Index':id});     
                        $this.jqGrid('editRow',id);
                    }, 
                    position:"last"
                });
                $("#tbl_por").setRowsByIndex(arrayPorcentaje,'Index');
                //SECCI�N PARA MANEJO DE RADIO BUTTONS
                $('#rad_ba1').change(function(){
                    $('#dm').hide();
                    $('#dt').show();
                });
                $('#rad_ba2').change(function(){
                    $('#dm').show();
                    $('#dt').hide();
                });
            });
            //INICIO DE LA SECCI�N DE FUNCIONES NECESARIAS PARA EL MANEJO DE DATOS
            //Funci�n para comprobar de que no exista el registro en la tabla config_activo
            function comprobar(){
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>",{comprobar:true},function(response){
                    if(response['existe']===true){
                        $('#tbl_por').jqGrid('clearGridData');
                        $("input[type=radio]").attr('disabled', true);
                        $('#btn_agr').addClass('ui-state-disabled');
                        $('#btn_gua').attr('disabled',true);
                        $.alert('El registro de configuraci&oacute;n ya fue realizado, para modificar el mismo ir a la opci&oacute;n Modificar.');
                    }
                },'json').fail(function(){$.alert();});
            }
            //Funci�n para eliminar una fila del jqgrid
            function eliminarFila(index){
                $('#tbl_por').jqGrid('delRowData',index);
            }
            //Funci�n para guardar un registro
            function saveForm(){
                var data=$('#frm_Con').serializeObject();
                data['porcentaje']=$("#tbl_por").getGridBatch();
                data['saveConfiguracion']=true;
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data,function(response){
                    if(response['success']===true){
                        $('#tbl_por').jqGrid('clearGridData');
                        $("input[type=radio]").attr('disabled', true);
                        $('#btn_agr').addClass('ui-state-disabled');
                        $('#btn_gua').attr('disabled',true);
                        $.alert("Transaccion Realizada con &Eacute;xito!");
                    }
                },'json').fail(function() { $.alert();});
            }
        </script>
    </BODY>
</HTML>

