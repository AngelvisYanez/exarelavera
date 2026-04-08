<?php
/**
 * @abstract Permite realizar el registro de un viaje
 * @author José Ambuludí
 * @version 2.0
 * Fecha de creación  2017-01-26
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_viaje.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Viaje($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_Viaje;

//Sección para cargar datos en el Jqgrid referente a los productos registrado
if (isset($productoAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(6, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(6, $data, $obBD_conexion);
    }
    echo json_encode($responce);
    exit();
}

//Sección para cargar datos en el Jqgrid referente al personal registrado
if (isset($personaAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(9, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(9, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
	echo json_encode($responce);
    exit();
}

//Sección para cargar datos en el Jqgrid referente a los clientes registrados
if (isset($viajeAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(19, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(19, $data, $obBD_conexion);
    }
	utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

/*Sección para verificar si una persona ya se encuentra registrada como chofer*/
if(isset($verificarCho)){
    $rs_chofer=$obBD_con1->getRowConsulta(18,$Prs_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
    if(!empty($rs_chofer['Prs_Cod'])){$response['existe']=true;}
    else{$response['existe']=false;}
    echo json_encode($response);
    exit();
}

/*Sección para buscar una persona según el número de cédula*/
if(isset($buscarCliente)){
    $longitud=  strlen($Prs_Ced);
    if($longitud*1===13){$Prs_Ced = substr($Prs_Ced, 0, -3);}
    $response=$obBD_con1->getRowConsulta(21,$Prs_Ced,$obBD_conexion);
    (!empty($response['Prs_Cod']))?$response['existe']=true:$response['existe']=false;
    utf8_encode_deep($response);
	echo json_encode($response);
    exit();
}

/*Sección para listar los viajes de un cliente*/
if(isset($cargarViajes)){
    $response=$obBD_con1->getArrayConsulta(22,$Cli_Cod.'*'.$Fecha.'*'.$Fec_Ini.'*'.$Fec_Fin,$obBD_conexion);
    foreach ($response as &$row){
        $Via_Tot=$row['Via_Can']*$row['Via_Pru'];
        $row['Via_Tot']=  number_format($Via_Tot,2,".","");
    }unset($row);
	utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

/*Sección para guardar datos de los agregar*/
if(isset($save)){
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    
    if(isset($saveCargamento)){$case=4;$consultar=2;$datos=$Car_Des.'*'.$Pro_Cod;}              //Sección para registrar un cargamento
    if(isset($saveModo)){$case=5;$consultar=3;$datos=$Mot_Des.'*'.$Ses_Emp_Cod;}                                 //Sección para registrar un modo_trabajo
    if(isset($saveAutomotor)){$case=7;$consultar=8;$datos=$Ses_Emp_Cod.'*'.$Veh_Mar.'*'.$Veh_Pla.'*'.$Veh_Col;}  //Sección para registrar un vehiculo
    if(isset($saveChofer)){
        $longitud=strlen($Prs_Ced);
        $rs_Ide_Cod = $obBD_con1->getRowConsulta(13,$longitud, $obBD_conexion);
        $Ide_Cod=$rs_Ide_Cod['Ide_Cod'];
        
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(14,$Ciu_Cod.'*'.$Ide_Cod.'*'.$Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Dir, $obBD_conexion);
            $Prs_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
        }
        $case=10;$consultar=15;$datos=$Prs_Cod.'*'.$Ses_Emp_Cod.'*'.$Cho_Tli;
    }                                                                                           //Sección para registrar un chofer
    
    $obBD_con1->operacionobBD($case,$datos, $obBD_conexion);
    $codigo=$obBD_con1->insercionid($obBD_conexion->conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    
    if ($obBD_con1->Error == 0) {$response['registro']=$obBD_con1->getRowConsulta($consultar,$codigo.'*'.$Ses_Emp_Cod,$obBD_conexion);$response['success'] = true;}
    echo json_encode($response);
    exit();
}

/*Sección para registrar un viaje*/
if(isset($saveViaje)){
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    foreach ($campos as $valor){
        if($valor['Via_Aux']=='N'){
            $obBD_con1->operacionobBD(17,$valor['Cho_Cod'].'*'.$valor['Car_Cod'].'*'.$valor['Mot_Cod'].'*'.$Cli_Cod.'*'.$valor['Veh_Cod'].'*'.$valor['Via_Ded'].'*'.$valor['Via_Has'].'*'.$valor['Via_Fec'].'*'.$valor['Via_Can'].'*'.$valor['Via_Pru'].'*'.$Via_Des, $obBD_conexion);
        }else{
            $obBD_con1->operacionobBD(23,$valor['Via_Cod'].'*'.$valor['Cho_Cod'].'*'.$valor['Car_Cod'].'*'.$valor['Mot_Cod'].'*'.$Cli_Cod.'*'.$valor['Veh_Cod'].'*'.$valor['Via_Ded'].'*'.$valor['Via_Has'].'*'.$valor['Via_Fec'].'*'.$valor['Via_Can'].'*'.$valor['Via_Pru'].'*'.$valor['Via_Est'], $obBD_conexion);
        }
    }   
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {$response['success'] = true;}
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script language="javascript" src="../VALIDACIONES/tca_viaje_1.js?a=3"></script>
        <style>
        .chosen-drop .chosen-results {
            max-height: 70px;
        }
        .ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}
        .ret .input-group-btn button{padding: 1px 2px !important;}
        </style>
        <script>
            <?php   $car=$obBD_con1->getArrayConsulta(2,"".'*'.$Ses_Emp_Cod,$obBD_conexion); 
                    $mod=$obBD_con1->getArrayConsulta(3,"".'*'.$Ses_Emp_Cod,$obBD_conexion); 
                    $arr_con=$obBD_con1->getArrayConsulta(15,"".'*'.$Ses_Emp_Cod,$obBD_conexion);    
                    $arr_aut=$obBD_con1->getArrayConsulta(8,"".'*'.$Ses_Emp_Cod,$obBD_conexion);    ?>
            
                    var arr_cho=<?php echo json_encode($arr_con);?>;
                    var arr_aut=<?php echo json_encode($arr_aut);?>;
        </script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Viaje</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <form id="frm_via" name="frm_via" class="form-horizontal normal" action="javascript:saveViaje();">
                    <input type="hidden" id="Cli_Cod" name="Cli_Cod">
                    <select id="car_cod" name="car_cod" class="form-control input-xs select_carga" style="display: none;">
                        <?php foreach ($car as $row){?>
                        <option value="<?php echo $row['Car_Cod'];?>"><?php echo utf8_decode($row['Car_Des']);?></option>
                        <?php }?>
                    </select>
                    <select id="mot_cod" name="mot_cod" class="form-control input-xs select_modo" style="display: none;">
                        <?php foreach ($mod as $row){?>
                        <option value="<?php echo $row['Mot_Cod'];?>"><?php echo utf8_decode($row['Mot_Des']);?></option>
                        <?php }?>
                    </select>
                        
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Formulario de Registro</legend>
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="col-md-6 col-sm-8">
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs">Por Fecha:</label>
                                        <div class="col-xs-7"><div class="input-group input-group-xs por_fecha"><span class="input-group-addon "><span class=""><input onchange="setRango()" id="por_fecha" name="por_fecha" type="checkbox" value="S" offval="N"  style="vertical-align: middle;" /></span></span><span class="input-group-addon alert-info">Desde</span><input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" disabled="" /><span class="input-group-addon alert-info">Hasta</span><input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" disabled="" /></div></div>                                        
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-xs required">Cliente:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <div class="input-group">
                                                <input type="text" id="cliente" name="cliente" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#viajeDialog').dialog('open');" style="z-index: 1;"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-3 label-xs">Direcci&oacute;n:</label>
                                        <div class="col-sm-7">
                                            <input type="text" id="Prs_Dir" name="Prs_Dir" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                
                                </div>
                                <div class="col-md-6" style="display: none;">
                                    <div class="form-group">
                                        <label class="control-label col-sm-3 label-sm">Descripci&oacute;n:</label>
                                        <div class="col-sm-7">
                                            <textarea id="Via_Des" name="Via_Des" class="form-control input-sm" style="resize: none;"></textarea>
                                        </div>
                                    </div>
                                </div>
								
                            </fieldset>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="Via_Grid"></table>
                            <div id="Via_Page"></div>
                        </div>
                    </div>
                    <div style="text-align: left;padding-top: 5px;">
                        <button type="button" id="btn_gua" name="btn_gua" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                    </div>
                </form>
            </div>
        </div>
        <!-- Inicio del diálogo para agregar un cargamento --> 
        <div id="cargamentoDialog" title="Registrar Cargamento">  
            <div class="row">
                <div class="col-md-12">
                    <form id="frm_car" name="frm_car" class="form-horizontal normal" action="javascript:saveDatos('frm_car','saveCargamento','cargamento');">
                        <input type="hidden" id="Pro_Cod" name="Pro_Cod">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm required">Producto:</label>
                                <div class="col-md-9 col-sm-4">
                                    <div class="input-group">
                                        <input type="text" id="Ite_Lar" name="Ite_Lar" class="form-control input-xs" placeholder="Seleccione un producto" readonly="">
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-success btn-xs" onclick="$('#productoDialog').dialog('open');" title="Buscar Producto"><span class="glyphicon glyphicon-search"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm required">Descripci&oacute;n:</label>
                                <div class="col-md-9 col-sm-4">
                                    <input type="text" id="Car_Des" name="Car_Des" class="form-control input-xs" required="">
                                </div>
                            </div>
                        </fieldset>
                        <div style="text-align: center;">
                            <button type="submit" id="btn_gua" name="btn_gua" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        </div>
                    </form>
                </div>             
            </div>
        </div>
        <!-- Inicio del diálogo para agregar un modo trabajo --> 
        <div id="modoDialog" title="Registrar Modo de Trabajo">  
            <div class="row">
                <div class="col-md-12">
                    <form id="frm_mot" name="frm_mot" class="form-horizontal normal" action="javascript:saveDatos('frm_mot','saveModo','modo');">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm required">Descripci&oacute;n:</label>
                                <div class="col-md-9 col-sm-4">
                                    <input type="text" id="Mot_Des" name="Mot_Des" class="form-control input-xs" required="">
                                </div>
                            </div>
                        </fieldset>
                        <div style="text-align: center;">
                            <button type="submit" id="btn_gu1" name="btn_gu1" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        </div>
                    </form>
                </div>             
            </div>
        </div>
        <!-- Inicio del diálogo para agregar un automotor --> 
        <div id="automotorDialog" title="Registrar Automotor">  
            <div class="row">
                <div class="col-md-12">
                    <form id="frm_aut" name="frm_aut" class="form-horizontal normal" action="javascript:saveDatos('frm_aut','saveAutomotor','automotor');">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm required">Placa:</label>
                                <div class="col-md-9 col-sm-4">
                                    <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Marca:</label>
                                <div class="col-md-9 col-sm-4">
                                    <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Color:</label>
                                <div class="col-md-9 col-sm-4">
                                    <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs">
                                </div>
                            </div>
                        </fieldset>
                        <div style="text-align: center;">
                            <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        </div>
                    </form>
                </div>             
            </div>
        </div>
        <!-- Inicio del diálogo para agregar un chofer --> 
        <div id="choferDialog" title="Registrar Conductor">  
            <div class="row">
                <div class="col-md-12">
                    <form id="frm_cho" name="frm_cho" class="form-horizontal normal" action="javascript:saveDatos('frm_cho','saveChofer','chofer');">
                        <input type="hidden" id="Prs_Cod" name="Prs_Cod" value="">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-sm required">C&eacute;dula/R.U.C.:</label>
                                <div class="col-sm-7">
                                    <div class="input-group">
                                        <input type="text" id="Prs_Ced" name="Prs_Ced" class="form-control input-xs" placeholder="Ingrese C&eacute;dula/R.U.C." required="">
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-success btn-xs" onclick="$('#personaDialog').dialog('open');" title="Buscar Persona"><span class="glyphicon glyphicon-search"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-sm required">Nombres:</label>
                                <div class="col-sm-7">
                                    <input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-sm required">Apellidos:</label>
                                <div class="col-sm-7">
                                    <input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required" for="Ciu_Cod">Ciudad:</label>  
                                <div class="col-sm-7">
                                    <?php $row_rs_ciudad = $obBD_con1->getArrayConsulta(12,"", $obBD_conexion); 		?>
                                    <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-xs">
                                        <?Php foreach($row_rs_ciudad as $row) { ?>
                                            <option value="<?Php echo $row['Ciu_Cod'];?>" ><?Php echo $row['Ciu_Des'];?></option>
                                        <?Php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-sm required">Direcci&oacute;n:</label>
                                <div class="col-sm-7">
                                    <input type="text" id="Prs_Dir" name="Prs_Dir" class="form-control input-xs" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4 label-sm">Licencia Tipo:</label>
                                <div class="col-sm-4">
                                    <input type="text" id="Cho_Tli" name="Cho_Tli" class="form-control input-xs">
                                </div>
                            </div>
                        </fieldset>
                        <div style="text-align: center;">
                            <button type="button" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Inicio del diálogo para buscar un producto -->
        <div id="productoDialog" title="B&uacute;squeda de Productos">
            <form class="form-horizontal normal"></form>
        </div>
        <!-- Inicio del diálogo para buscar una persona -->
        <div id="personaDialog" title="B&uacute;squeda de Persona">
            <form class="form-horizontal normal"></form>
        </div>
        <!-- Inicio del diálogo para buscar un cliente -->
        <div id="clienteDialog" title="B&uacute;squeda de Clientes">
            <form class="form-horizontal normal"></form>
        </div>
        <!-- Inicio del diálogo para buscar un cliente -->
        <div id="viajeDialog" title="B&uacute;squeda de Clientes">
            <form class="form-horizontal normal"></form>
        </div>
        <script>
        $(function(){
            $('#btn_agr').addClass('ui-state-disabled');			
            $.createDateRange('#Fec_Ini','#Fec_Fin');
        });
        function setRango(){
                $('.por_fecha').find('input[type=text]')[$('#por_fecha').is(':checked')?'removeAttr':'attr']('disabled','disabled');
            }
        //Función para guardar un viaje
        function saveViaje(){
            var index;
            var data=$('#frm_via').getData('saveViaje');
            data['campos'] = $("#Via_Grid").getGridBatch();
            
            $.each(data['campos'],function(i,v){
                if(v['Con_Duc']==='' || v['Aut_Mot']==='' || v['Via_Fec']==='' || v['Via_Ded']==='' || v['Via_Has']==='' || v['Via_Can']==='' || v['Via_Pru']===''){
                    index = $("#Via_Grid").jqGrid('getInd',v['Via_Cod']);
                    $.alert('Debe completar información en la fila: '+index);
                    $('#Via_Grid').startGridEdit();
                    return false;
                }
            });

            if(index*1>0)return false;
                
            if($('#cliente').val()===''){$.alert('Debe seleccionar un cliente..!!');return;}
            
            if((data['campos'].length)<1){$.alert('Debe existir al menos un registro de viaje..!!');return false;}
            
            $.post("",data,function(response){
                if(response.success===true){
                    $.alert("Transaccion Realizada con &Eacute;xito!");
                    $('#frm_via')[0].reset();
                    $('#Via_Grid').jqGrid('clearGridData',true).trigger('reloadGrid');
                    $("#viajeDialog").getDialogGrid().trigger('reloadGrid', [{page: 1}]);
                }
            },'json').fail(function(){$.alert();});
        }
        </script>
    </BODY>
</HTML>
