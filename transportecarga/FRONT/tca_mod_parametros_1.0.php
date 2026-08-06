	<?php
/**
 * @abstract Permite realizar la modificaci�n de un proceso de facturaci�n de viajes
 * @author Jos� Ambulud�
 * @version 2.0
 * Fecha de creaci�n  2017-02-13
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

$hoy = date("Y-m-d");

//Secci�n para cargar datos en el Jqgrid referente a las facturas registradas
if (isset($cargarDatos)) {
    $response['chofer'] = $obBD_con1->getArrayConsulta(27,$Ses_Emp_Cod,$obBD_conexion);
    $response['vehiculo'] = $obBD_con1->getArrayConsulta(30,$Ses_Emp_Cod,$obBD_conexion);
    $response['cargamento'] = $obBD_con1->getArrayConsulta(32,$Ses_Emp_Cod,$obBD_conexion);
    $response['modo'] = $obBD_con1->getArrayConsulta(34,$Ses_Emp_Cod,$obBD_conexion);
    $response['origen'] = $obBD_con1->getArrayConsulta('viaje_lugar',array('setWhere'=>array('setEmpCod','isOrigen','isActive')),$obBD_conexion);
    $response['destino'] = $obBD_con1->getArrayConsulta('viaje_lugar',array('setWhere'=>array('setEmpCod','isDestino','isActive')),$obBD_conexion);
    $obBD_con1->echoJson($response);
}

/*Secci�n para buscar una persona seg�n el n�mero de c�dula*/
if(isset($buscarCliente)){
    $longitud=  strlen($Prs_Ced);
    if($longitud*1===13){$Prs_Ced = substr($Prs_Ced, 0, -3);}
    $response=$obBD_con1->getRowConsulta(21,$Prs_Ced,$obBD_conexion);
    if(!empty($response['Prs_Cod'])){
        $response['existe']=true;
        $rs_chofer=$obBD_con1->getRowConsulta(18,$response['Prs_Cod'].'*'.$Ses_Emp_Cod,$obBD_conexion);
        if(!empty($rs_chofer['Prs_Cod'])){$response['existeChofer']=true;}else{$response['existeChofer']=false;}
    }else{$response['existe']=false;}
    $obBD_con1->echoJson($response);
}

//Secci�n para cargar datos en el Jqgrid referente a los productos registrado
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
    $obBD_con1->echoJson($responce);
}

if(isset($save)){
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

    //Secci�n para guardar datos de un chofer
    if($clave=='Cho_Cod'){
        foreach ($campos as $valor){
            if($valor['Cho_Aux']=='N'){
                $longitud=strlen($valor['Prs_Ced']);
                $rs_Ide_Cod = $obBD_con1->getRowConsulta(13,$longitud, $obBD_conexion);
                $Ide_Cod=$rs_Ide_Cod['Ide_Cod'];
                $Prs_Cod=$valor['Prs_Cod'];
                if(empty($Prs_Cod)){
                    $obBD_con1->operacionobBD(14,$valor['Ciu_Cod'].'*'.$Ide_Cod.'*'.$valor['Prs_Ced'].'*'.$valor['Prs_Nom'].'*'.$valor['Prs_Ape'].'*'.$valor['Prs_Dir'], $obBD_conexion);
                    $Prs_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
                }
                $obBD_con1->operacionobBD(10,array($Prs_Cod,$Ses_Emp_Cod,$valor['Cho_Tli'],$valor['Cho_Tel']), $obBD_conexion);
            }else{
                $obBD_con1->operacionobBD(28,array($valor['Cho_Cod'],$valor['Cho_Tli'],$valor['Cho_Est'],$valor['Cho_Tel']), $obBD_conexion);
                $obBD_con1->operacionobBD(29,$valor['Prs_Cod'].'*'.$valor['Prs_Ape'].'*'.$valor['Prs_Nom'].'*'.$valor['Ciu_Cod'].'*'.$valor['Prs_Dir'], $obBD_conexion);
            }
        }
    }

    //Secci�n para guardar datos de un Vehiculo
    if($clave=='Veh_Cod'){
        foreach ($campos as $v){
            if($v['Veh_Aux']=='N'){
                $obBD_con1->operacionobBD(7,$Ses_Emp_Cod.'*'.$v['Veh_Mar'].'*'.$v['Veh_Pla'].'*'.$v['Veh_Col'], $obBD_conexion);
            }else{
                $obBD_con1->operacionobBD(31,$v['Veh_Cod'].'*'.$v['Veh_Mar'].'*'.$v['Veh_Pla'].'*'.$v['Veh_Col'].'*'.$v['Veh_Est'], $obBD_conexion);
            }
        }
    }

    //Secci�n para guardar datos de un cargamento
    if($clave=='Car_Cod'){
        foreach ($campos as $v){
            if($v['Car_Aux']=='N'){
                $obBD_con1->operacionobBD(4,$v['Car_Des'].'*'.$v['Pro_Cod'], $obBD_conexion);
            }else{
                $obBD_con1->operacionobBD(33,$v['Car_Cod'].'*'.$v['Car_Des'].'*'.$v['Pro_Cod'].'*'.$v['Car_Est'], $obBD_conexion);
            }
        }
    }

    //Secci�n para guardar datos de un modo_trabajo
    if($clave=='Mot_Cod'){
        foreach ($campos as $v){
            if($v['Mot_Aux']=='N'){
                $obBD_con1->operacionobBD(5,$v['Mot_Des'].'*'.$Ses_Emp_Cod, $obBD_conexion);
            }else{
                $obBD_con1->operacionobBD(35,$v['Mot_Cod'].'*'.$v['Mot_Des'].'*'.$v['Mot_Est'], $obBD_conexion);
            }
        }
    }
    if($clave=='Vlu_Cod'){
        foreach ($campos as $v){
            if($v['Vlu_Aux']=='N'){
                $obBD_con1->operacionobBD('viaje_lugar.insert',array('Emp_Cod'=>$Ses_Emp_Cod, 'Vlu_Aco'=>$v['Vlu_Aco'], 'Vlu_Zon'=>$v['Vlu_Zon'], 'Vlu_Tip'=>$v['Vlu_Tip'] ), $obBD_conexion);
            }else{
                $obBD_con1->operacionobBD('viaje_lugar.update',array('Vlu_Aco'=>$v['Vlu_Aco'], 'Vlu_Zon'=>$v['Vlu_Zon'], 'Vlu_Est'=>$v['Vlu_Est'], 'where'=>array('Vlu_Cod'=>$v['Vlu_Cod']) ), $obBD_conexion);
            }
        }
    }

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {$response['success'] = true;}
    $obBD_con1->echoJson($response);
}
if(isset($elimina)){
    $resp=array();
    $tables=array('Mot_Grid'=>'modo_trabajo','Car_Grid'=>'cargamento','Cho_Grid'=>'chofer','Aut_Grid'=>'vehiculo','Ori_Grid'=>'viaje_lugar','Des_Grid'=>'viaje_lugar');
    $oBdSet = new MysqlDatos(true);
    $oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{
        $oBdSet->operation($tables[$grid].'.update', array(substr($est,1,strlen($est))=>'I','where'=>array(substr($est,1,strlen($est)-4)."Cod"=>$id)));
        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script>
            <?php $ciudades=$obBD_con1->getArrayConsulta(12,"",$obBD_conexion); ?>
        </script>
        <style>
            .ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}
            .ret .input-group-btn button{padding: 1px 2px !important;}
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Factura</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix">
                            <ul style="font-size: 12px;">
                                <li><a href="#tab_con">Conductores</a></li>
                                <li><a href="#tab_aut">Automotores</a></li>
                                <li><a href="#tab_car">Cargamentos</a></li>
                                <li><a href="#tab_mod">Modos de Trabajo</a></li>
                                <li><a href="#tab_ori">Origenes</a></li>
                                <li><a href="#tab_des">Destinos</a></li>
                            </ul>
                            <div id="tab_con">
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <select id="ciu_cod" name="ciu_cod" class="form-control input-xs select_carga" style="display: none;">
                                            <?php foreach ($ciudades as $row){?>
                                            <option value="<?php echo $row['Ciu_Cod'];?>"><?php echo mb_convert_encoding($row['Ciu_Des'], 'ISO-8859-1', 'UTF-8');?></option>
                                            <?php }?>
                                        </select>
                                        <div>
                                            <table id="Cho_Grid" class="grid"></table>
                                            <div id="Pag_Cho"></div>
                                        </div>
                                        <div style="text-align: left;padding-top: 5px;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="save('Cho_Grid',['Prs_Ced','Prs_Ape','Prs_Nom'],'Cho_Cod');"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="tab_aut">
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-6">
                                        <div>
                                            <table id="Aut_Grid" class="grid"></table>
                                            <div id="Pag_Aut"></div>
                                        </div>
                                        <div style="text-align: left;padding-top: 5px;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="save('Aut_Grid',['Veh_Pla'],'Veh_Cod');"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="tab_car">
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-6">
                                        <div>
                                            <table id="Car_Grid" class="grid"></table>
                                            <div id="Pag_Car"></div>
                                        </div>
                                        <div style="text-align: left;padding-top: 5px;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="save('Car_Grid',['Ite_Lar','Car_Des'],'Car_Cod');"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="tab_mod">
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-6">
                                        <div>
                                            <table id="Mot_Grid" class="grid"></table>
                                            <div id="Pag_Mot"></div>
                                        </div>
                                        <div style="text-align: left;padding-top: 5px;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="save('Mot_Grid',['Mot_Des'],'Mot_Cod');"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="tab_ori">
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-6">
                                        <div>
                                            <table id="Ori_Grid" class="grid"></table>
                                            <div id="Pag_Ori"></div>
                                        </div>
                                        <div style="text-align: left;padding-top: 5px;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="save('Ori_Grid',['Vlu_Aco'],'Vlu_Cod');"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="tab_des">
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-6">
                                        <div>
                                            <table id="Des_Grid" class="grid"></table>
                                            <div id="Pag_Des"></div>
                                        </div>
                                        <div style="text-align: left;padding-top: 5px;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="save('Des_Grid',['Vlu_Aco'],'Vlu_Cod');"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Inicio del di�logo para buscar un cliente -->
        <div id="clienteDialog" title="B&uacute;squeda de Clientes">
            <form class="form-horizontal normal"></form>
        </div>
        <!-- Inicio del di�logo para buscar un chofer -->
        <div id="choferDialog" title="B&uacute;squeda de Choferes">
            <form class="form-horizontal normal"></form>
        </div>
        <!-- Inicio del di�logo para buscar un producto -->
        <div id="productoDialog" title="B&uacute;squeda de Productos">
            <form class="form-horizontal normal"><input type="hidden" name="row" id="row" value=""/></form>
        </div>
        <script type="text/javascript">
            $(function () {
                cargarDatos();
                //Inicializaci�n
                $("#tabs").createTabs({});
                $.createDatePickers('.datepicker');
                $('#Fec_Ini').val('');

                //Inicio Grid para presentar el detalle de factura
                $("#Cho_Grid").createGrid({
                    caption:'Listado de Conductores',height: 180,
                    colModel: [
                        {label: 'Cho_Cod', name: 'Cho_Cod',key:true,hidden:true},
                        {label: 'Prs_Cod', name: 'Prs_Cod',hidden:true,formatter:'input2',formatoptions:{class:'datos'}},
                        {label: '<span class="required"></span> C&eacute;dula/R.U.C.', name: 'Prs_Ced',width: 40, align: "center",editable:true, editoptions:{dataInit:cargarPersona}},
                        {label: '<span class="required"></span> Apellidos', name: 'Prs_Ape', width: 55, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: '<span class="required"></span> Nombres', name: 'Prs_Nom', width: 55, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: '<span class="required"></span> Ciudad', name: 'Ciu_Cod', width: 50, align: "center",title:false,formatter:'select1', formatoptions:{id:'ciu_cod'}},
                        {label: 'Direcci&oacute;n', name: 'Prs_Dir', width: 70, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: 'Telefono', name: 'Cho_Tel', width: 35, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: 'Tipo Licencia', name: 'Cho_Tli', width: 30, align: "center",formatter:'input2',formatoptions:{class:''}},
                        {label: 'Cho_Est', name: 'Cho_Est',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: 'Cho_Aux', name: 'Cho_Aux',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(eliminarFila,{id:rowObject.Cho_Cod,grid:'Cho_Grid',aux:'_Cho_Aux',est:'_Cho_Est'},'Eliminar','glyphicon glyphicon-remove',null,'danger');
                            }
                        }
                    ],pgbuttons:false,pgtext:null,footerrow:false,beforeSelectRow: function(rowid, e) {return false;}
                },true,'Pag_Cho',{view:false,refresh:false}).gridButtonsAdd([{
                            caption:"Agregar campo",
                            id:'btn_agr',
                            buttonicon:"glyphicon glyphicon-plus",
                            title:'Agregar',
                            onClickButton: function (){agregarFila(0,'Cho_Grid','Cho_Cod','_Cho_Aux');},
                            position:"last"
                        },{
                            buttonicon:"glyphicon glyphicon-refresh",
                            title:'Resetear',
                            onClickButton: function (){actualizarFila('Cho_Grid','Cho_Cod','Cho_Est');}
                        }]);

                $("#Aut_Grid").createGrid({
                    caption:'Listado de Automotores',height: 180,
                    colModel: [
                        {label: 'Veh_Cod', name: 'Veh_Cod',key:true,hidden:true},
                        {label: '<span class="required"></span> Placa', name: 'Veh_Pla',width: 40,formatter:'input2',formatoptions:{class:'datos'}},
                        {label: 'Marca', name: 'Veh_Mar',width: 40, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: 'Color', name: 'Veh_Col',width: 40, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: 'Veh_Est', name: 'Veh_Est',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: 'Veh_Aux', name: 'Veh_Aux',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(eliminarFila,{id:rowObject.Veh_Cod,grid:'Aut_Grid',aux:'_Veh_Aux',est:'_Veh_Est'},'Eliminar','glyphicon glyphicon-remove',null,'danger');
                            }
                        }
                    ],pgbuttons:false,pgtext:null,footerrow:false,beforeSelectRow: function(rowid, e) {return false;}
                },true,'Pag_Aut',{view:false,refresh:false}).gridButtonsAdd([{
                            caption:"Agregar campo",
                            id:'btn_agr',
                            buttonicon:"glyphicon glyphicon-plus",
                            title:'Agregar',
                            onClickButton: function (){agregarFila(0,'Aut_Grid','Veh_Cod','_Veh_Aux');},
                            position:"last"
                        },{
                            buttonicon:"glyphicon glyphicon-refresh",
                            title:'Resetear',
                            onClickButton: function (){actualizarFila('Aut_Grid','Veh_Cod','Veh_Est');}
                        }]);

                $("#Car_Grid").createGrid({
                    caption:'Listado de Cargamentos',height: 180,
                    colModel: [
                        {label: 'Car_Cod', name: 'Car_Cod',key:true,hidden:true},
                        {label: 'Pro_Cod', name: 'Pro_Cod',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: '<span class="required"></span> Producto', name: 'Ite_Lar',width: 40,formatter:'input3',formatoptions:{class:'datos'}},
                        {label: '<span class="required"></span> Descripci&oacute;n', name: 'Car_Des',width: 40, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: 'Car_Est', name: 'Car_Est',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: 'Car_Aux', name: 'Car_Aux',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 10, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(eliminarFila,{id:rowObject.Car_Cod,grid:'Car_Grid',aux:'_Car_Aux',est:'_Car_Est'},'Eliminar','glyphicon glyphicon-remove',null,'danger');
                            }
                        }
                    ],pgbuttons:false,pgtext:null,footerrow:false,beforeSelectRow: function(rowid, e) {return false;}
                },true,'Pag_Car',{view:false,refresh:false}).gridButtonsAdd([{
                            caption:"Agregar campo",
                            id:'btn_agr',
                            buttonicon:"glyphicon glyphicon-plus",
                            title:'Agregar',
                            onClickButton: function (){agregarFila(0,'Car_Grid','Car_Cod','_Car_Aux');},
                            position:"last"
                        },{
                            buttonicon:"glyphicon glyphicon-refresh",
                            title:'Resetear',
                            onClickButton: function (){actualizarFila('Car_Grid','Car_Cod','Car_Est');}
                        }]);

                $("#Mot_Grid").createGrid({
                    caption:'Listado de Modos de Trabajo',height: 180,
                    colModel: [
                        {label: 'Mot_Cod', name: 'Mot_Cod',key:true,hidden:true},
                        {label: '<span class="required"></span> Descripci&oacute;n', name: 'Mot_Des',width: 40, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                        {label: 'Mot_Est', name: 'Mot_Est',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: 'Mot_Aux', name: 'Mot_Aux',hidden:true,formatter:'input2',formatoptions:{class:''}},
                        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 5, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(eliminarFila,{id:rowObject.Mot_Cod,grid:'Mot_Grid',aux:'_Mot_Aux',est:'_Mot_Est'},'Eliminar','glyphicon glyphicon-remove',null,'danger');
                            }
                        }
                    ],pgbuttons:false,pgtext:null,footerrow:false,beforeSelectRow: function(rowid, e) {return false;}
                },true,'Pag_Mot',{view:false,refresh:false}).gridButtonsAdd([{
                            caption:"Agregar campo",
                            buttonicon:"glyphicon glyphicon-plus",
                            title:'Agregar',
                            onClickButton: function (){agregarFila(0,'Mot_Grid','Mot_Cod','_Mot_Aux');}

                        },{
                            buttonicon:"glyphicon glyphicon-refresh",
                            title:'Resetear',
                            onClickButton: function (){actualizarFila('Mot_Grid','Mot_Cod','Mot_Est');}
                        }]);
                var op_lugares=[
                    {label: 'Vlu_Cod', name: 'Vlu_Cod',key:true,hidden:true},
                    {label: 'Zona', name: 'Vlu_Zon',width: 40, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                    {label: '<span class="required"></span> Acopio', name: 'Vlu_Aco',width: 40, align: "center",formatter:'input2',formatoptions:{class:'datos'}},
                    {label: 'Vlu_Est', name: 'Vlu_Est',hidden:true,formatter:'input2',formatoptions:{class:''}},
                    {label: 'Vlu_Aux', name: 'Vlu_Aux',hidden:true,formatter:'input2',formatoptions:{class:''}}
                ];
                $("#Ori_Grid").createGrid({
                    caption:'Lista de Origenes', height: 180,
                    colModel: op_lugares.concat([
                        { label: 'Vlu_Tip', name: 'Vlu_Tip', width:20 , hidden:true, formatter:function(){ return 'O'; } },
                        { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 5, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(eliminarFila,{id:rowObject.Vlu_Cod,grid:'Ori_Grid',aux:'_Vlu_Aux',est:'_Vlu_Est'},'Eliminar','glyphicon glyphicon-remove',null,'danger');
                            }
                        }
                    ]) ,pgbuttons:false,pgtext:null,footerrow:false,beforeSelectRow: function(rowid, e) {return false;}
                },true,'Pag_Ori',{view:false,refresh:false}).gridButtonsAdd([{
                    caption:"Agregar campo", buttonicon:"glyphicon glyphicon-plus", title:'Agregar', onClickButton: function(){ agregarFila(0,'Ori_Grid','Vlu_Cod','_Vlu_Aux'); }
                },{ buttonicon:"glyphicon glyphicon-refresh", title:'Resetear', onClickButton: function (){actualizarFila('Ori_Grid','Vlu_Cod','Vlu_Est');} }]);

                $("#Des_Grid").createGrid({
                    caption:'Lista de Destinos', height: 180,
                    colModel: op_lugares.concat([
                        { label: 'Vlu_Tip', name: 'Vlu_Tip', width:20 , hidden:true, formatter:function(){ return 'D'; } },
                        { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 5, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(eliminarFila,{id:rowObject.Vlu_Cod,grid:'Des_Grid',aux:'_Vlu_Aux',est:'_Vlu_Est'},'Eliminar','glyphicon glyphicon-remove',null,'danger');
                            }
                        }
                    ]) ,pgbuttons:false,pgtext:null,footerrow:false,beforeSelectRow: function(rowid, e) {return false;}
                },true,'Pag_Des',{view:false,refresh:false}).gridButtonsAdd([{
                    caption:"Agregar campo", buttonicon:"glyphicon glyphicon-plus", title:'Agregar', onClickButton: function(){ agregarFila(0,'Ori_Grid','Vlu_Cod','_Vlu_Aux'); }
                },{ buttonicon:"glyphicon glyphicon-refresh", title:'Resetear', onClickButton: function (){actualizarFila('Des_Grid','Vlu_Cod','Vlu_Est');} }]);


                //Inicio del di�logo para presentar productos
                $.createSearchDialog('#productoDialog', [
                    {label: 'C�d.Int.', name: 'Pro_Cod', key: true, hidden: false, viewable: true, width: 15, align: 'center'},
                    {label: 'Producto', name: 'Ite_Lar', width: 70},
                    {label: 'Pld_Cod', name: 'Pld_Cod', hidden: true},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,formatter: boton}
                ], null, null, null, null, {title: 'Producto', options: [{label: '&nbsp;&nbsp;Producto&nbsp;&nbsp;', value: 'd'},
                {label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c'}]});
                function boton(cellvalue, options, rowObject){
                    if($.varValid(rowObject.Pld_Cod)){
                        return $.getGridButton(cargarProducto, {Pro_Cod:rowObject.Pro_Cod,Ite_Lar:rowObject.Ite_Lar,row:$('#row').val()});
                    }else{
                        return $.getGridButton('','','Producto NO esta parametrizado','glyphicon glyphicon-lock','','btn btn-warning');
                    }
                }

                $.fn.fmatter.select1=function(cv,opts,cObjt){
                    var set=opts['colModel']['formatoptions'],op=$('#'+set['id']).html(),el=$('<select id="'+opts['rowId']+'_'+opts['colModel']['name']+'" name="'+opts['colModel']['name']+'" class="form-control input-xs">'+op+'</select>');
                    return el.prop('outerHTML');
                };
                $.fn.fmatter.select1.unformat=function(cv,opts,cObjt){ return $(cObjt).find(':input').val(); };

                $.fn.fmatter.input2=function(cv,opts,cObjt){
                    var set=opts['colModel']['formatoptions'],el=$('<input type="text" id="'+opts['rowId']+'_'+opts['colModel']['name']+'" name="'+opts['colModel']['name']+'" class="form-control input-xs '+set['class']+'"/>');
                    return el.prop('outerHTML');
                };
                $.fn.fmatter.input2.unformat=function(cv,opts,cObjt){ return $(cObjt).find(':input').val(); };

                $.fn.fmatter.input3=function(cv,opts,cObjt){
                    var set=opts['colModel']['formatoptions'],el=$('<div class="input-group input-group-xs ret"><input type="text" id="'+opts['rowId']+'_'+opts['colModel']['name']+'" name="'+opts['colModel']['name']+'" class="form-control input-xs '+set['class']+'"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="" onclick="$(\'#productoDialog\').dialog(\'open\');$(\'#row\').val('+opts['rowId']+');"><span class="glyphicon glyphicon-plus"></span></button></span></div>');
                    return el.prop('outerHTML');
                };
                $.fn.fmatter.input3.unformat=function(cv,opts,cObjt){ return $(cObjt).find(':input').val(); };

            });

            /*** FUNCIONES PARA EL MANEJO DE DATOS ***/

            //Funci�n que agrega una fila al grid
            function agregarFila(indice,grid,cod,aux){
                $('#'+grid).jqGrid('resizeGrid');
                public $this=$('#'+grid),id,nuevo;
                if(indice<1){
                    id=($this.jqGrid('getCol',cod,false,'max')+1)||0;
                    nuevo='N';
                }else{
                    id=indice;
                    nuevo='A';
                }
                var data={};data[cod]=id;
                $this.jqGrid('addRowData',id,data);
                $this.jqGrid('editRow',id);
                $('#'+id+aux).val(nuevo);
            }

            //Funci�n para eliminar un registro del grid
            function eliminarFila(objeto){
                var aux=$('#'+objeto.id+objeto.aux).val();
                if(aux==='N'){
                    $('#'+objeto.grid).jqGrid('delRowData',objeto.id);
                }else{
                    $.post("",$.extend(objeto,{elimina:true}),function(response){
                        if(response.success===true){
                            $('#'+objeto.id+objeto.est).val('I');
                            $('#'+objeto.id,'#'+objeto.grid).hide();
                        }
                    },'json').fail(function(){$.alert();});
                }
            }

            //Funci�n para actualizar grid
            function actualizarFila(grid,codigo,estado){
                var data=$('#'+grid).getGridBatch();
                $.each(data,function(i,v){
                    if(v[estado]==='I'){
                        $('#'+v[codigo]+'_'+estado).val('A');
                        $('#'+v[codigo],'#'+grid).show();
                    }
                });
                $('#'+grid).startGridEdit();
            }

            //Estilo cargar persona seg�n su n�mero de c�dula
            function cargarPersona(e,obj,opt){
                $(e).on('change',function (){
                    buscarCliente(this.value,obj);
                });
            }

            //Funci�n para cargar los datos de un producto
            function cargarProducto(obj){
                $('#productoDialog').dialog('close');
                var producto=$('#productoForm').getData();
                $('#'+producto.row+'_Pro_Cod').val(obj.Pro_Cod);
                $('#'+producto.row+'_Ite_Lar').val(obj.Ite_Lar);
            }

            //Funci�n que recibe el n�mero de c�dula
            function buscarCliente(cliente,obj){
                var respuesta=validaNoIdentif(cliente);
                if(respuesta['success']===true){
                    $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>",{buscarCliente:true,Prs_Ced:cliente},function(response){
                        if(response['existe']===true && response['existeChofer']===false){
                            $('#Cho_Grid').find('tr#'+obj['rowId']).setData(response,false);
                        }
                        if(response['existe']===false){
                            $.alert('La persona no se encuentra registrada..!!');
                            $('#Cho_Grid').find('tr#'+obj['rowId']).find('.datos').val('');
                        }
                        if(response['existeChofer']===true){
                            $.alert('La persona ya se encuentra registrada como chofer..!!');
                            $('#Cho_Grid').find('tr#'+obj['rowId']).find('.datos').val('');
                            $('#'+obj['rowId']+'_Prs_Ced').val('');
                        }
                    },'json').fail(function(){$.alert();});
                }else{
                    $.alert(respuesta['message']);
                    $('#Cho_Grid').find('tr#'+obj['rowId']).find('.datos').val('');
                    $('#'+obj['rowId']+'_Prs_Ced').val('');
                }
            }

            //valida cedula
            function validaNoIdentif(number){
                var digitos = number.split(""), dto=digitos.length, acu=0, resp={success:false,message:''},
                coef={'NA':[2,1,2,1,2,1,2,1,2],'PU':[3,2,7,6,5,4,3,2,0],'PR':[4,3,2,7,6,5,4,3,2]}, modulo, acum=0;
                if(dto===0) resp['message']='No has ingresado ning\u00fan dato!';
                else{
                 for(var i=0; i<dto; i++) if(!isNaN(digitos[i])){ digitos[i]=digitos[i]*1; acu = acu+1; }
                 if(acu===dto){
                  var tipo = digitos[2];
                  if (tipo===7||tipo===8) resp['message']='"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else{ tipo=(tipo<6?'NA':(tipo===6?'PU':(tipo===9?'PR':''))); modulo=(tipo==='NA'?10:11); resp['tipo_abrev']=tipo; resp['tipo']=(tipo==='NA'?'Natural':(tipo==='PR'?'Privada':(tipo==='PU'?'P\u00fablica':''))); }
                      if(dto!==10&&dto!==13){ resp['message']='La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; }else{ resp['doc_abr']=(dto===10?'C':(dto===13?'R':'')); resp['doc']=(dto===10?'C\u00E9dula':(dto===13?'R.U.C.':'')); }
                      if(number.substring(0,2)*1>24) resp['message']='Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';
                      if(dto===13){
                              if(number.substring(10,13)!=='001') resp['message']='Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
                              if(tipo==='PU'&&number.substring(9,13)!=='0001') resp['message']='El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
                      }else if((tipo==='PU'||tipo==='PR')) resp['message']='El R.U.C. de las empresas '+resp['tipo']+'s deben tener 13 digitos!';
                      if(resp['message'].length>0) return resp;

                      for(var a=0;a<9;a++){
                              var resul=digitos[a]*coef[tipo][a];
                              acum+=(resul-(tipo==='NA'&&resul>=10?9:0));
                      }
                      var residuo=acum%modulo, digitoVerificador = residuo===0 ? 0: modulo - residuo;
                      if(digitos[(tipo==='PU'?8:9)]!==digitoVerificador) resp['message'] = 'El n\u00famero de '+resp['doc']+' de la '+(tipo==='NA'?'Persona Natural':'Empresa '+resp['tipo'])+' ingresado es inv\u00E1lido!';

                      if(resp['message'].length===0) resp['success']=true;
                 }else resp['message']="ERROR: Solo debe contener d\u00EDgitos!";
                }
                return resp;
            }

            //Funci�n para cargar los datos a cada una de los grids
            function cargarDatos(){
                $('.grid').jqGrid('clearGridData',true).trigger('reloadGrid');
                $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>",{cargarDatos:true},function(response){
                    $.each(response['chofer'],function(i,v){
                        agregarFila(v['Cho_Cod'],'Cho_Grid','Cho_Cod','_Cho_Aux');$('#Cho_Grid').find('tr#'+v['Cho_Cod']).setData(v,false);
                    });
                    $.each(response['vehiculo'],function(i,v){
                        agregarFila(v['Veh_Cod'],'Aut_Grid','Veh_Cod','_Veh_Aux');$('#Aut_Grid').find('tr#'+v['Veh_Cod']).setData(v,false);
                    });
                    $.each(response['cargamento'],function(i,v){
                        agregarFila(v['Car_Cod'],'Car_Grid','Car_Cod','_Car_Aux');$('#Car_Grid').find('tr#'+v['Car_Cod']).setData(v,false);
                    });
                    $.each(response['modo'],function(i,v){
                        agregarFila(v['Mot_Cod'],'Mot_Grid','Mot_Cod','_Mot_Aux');$('#Mot_Grid').find('tr#'+v['Mot_Cod']).setData(v,false);
                    });
                    $.each(response['origen'],function(i,v){
                        agregarFila(v['Vlu_Cod'],'Ori_Grid','Vlu_Cod','_Vlu_Aux');$('#Ori_Grid').find('tr#'+v['Vlu_Cod']).setData(v,false);
                    });
                    $.each(response['destino'],function(i,v){
                        agregarFila(v['Vlu_Cod'],'Des_Grid','Vlu_Cod','_Vlu_Aux');$('#Des_Grid').find('tr#'+v['Vlu_Cod']).setData(v,false);
                    });
                },'json').fail(function(){$.alert();});
            }

            //Funci�n para guardar un registro
            function save(grid,variables,clave){
                $.createDialogConfirm('Desea Guardar los cambios realizados..!!',null,function(){
                    var index=0, data=$('#'+grid).getGridBatch();
                    $.arraySpliceFields(data, ['act1']);
                    $.each(data,function(i,v){
                        for (var ind = 0; ind < variables.length; ind++) {
                            var campo=variables[ind];
                            if(v[campo]===''){
                                index = $('#'+grid).jqGrid('getInd',v[clave]);
                                $.alert('Debe completar informaci�n en la fila: '+index);
                                $('#'+grid).startGridEdit();
                                return false;
                            }
                        }
                    });

                    if(index*1>0){return false;}
                    $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>",{campos:data,save:true,clave:clave},function(response){
                        if(response.success===true){
                            $.alert("Transaccion Realizada con &Eacute;xito!");
                            cargarDatos();
                        }
                    },'json').fail(function(){$.alert();});
                },function(){
                    cargarDatos();
                });
            }
        </script>
    </BODY>
</HTML>





