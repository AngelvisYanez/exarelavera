<?php
/**
 * @abstract Permite actualizar comprobantes automaticos con cuentas de banco. 
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creaci�n  10/11/2017
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_ban.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Che;


//$obBD_con1->debug(true);
if (isset($cargarPeriodos)) {
    try {
        $resp['periodos'] = $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
        } else {
            $resp = array('messsage' => $obBD_con1->MsgError);
        }
    } catch (Exception $ex) {
        $resp = array('messsage' => $ex->getMessage());
    }
    $obBD_con1->echoJson($resp);
}



if (isset($getBancos)) {

    $resp = array('success' => true, 'options' => "<option value=''></option>");
    $bancos = $obBD_con1->getArrayConsulta(4, $Pec_Cod, $obBD_conexion);
    //var_dump($bancos);
    foreach ($bancos as $v) {
        $resp['options'] = $resp['options'] . "<option value='$v[Pld_Cod]' data--pld_-cod='$v[Pld_Cod]' data--ban_-cod='$v[Ban_Cod]' data--ban_-cue='$v[Ban_Cue]' data--pld_-cdc='$v[Pld_Cdc]' data--pld_-des='" . str_replace("'", '', $v['Pld_Des']) . "'>$v[Pld_Des] (Cta.#: $v[Ban_Cue])</option>";
    }
    $obBD_con1->echoJson($resp);
}


if(isset($save_cheques)){
    //$obBD_con1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_Che($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_Che;
    $tipo_asiento = $obBD_con1->getRowConsulta(5,'', $obBD_conexion);
    $Prv_Cod = $obBD_con1->getProveeClie($Ses_Emp_Cod, 'Prv_Cod', $obBD_conexion);
    $Com_Num = $obBD_con1->getComNumPecAuto($tipo_asiento['Tia_Cod'], $Pec_Cod, $Com_Fec, $obBD_conexion);
    //$obBD_conIns->debug(true);
    $obBD_conIns->inicio_transaccion($obBD_conexionIns);
    try {
        $response['anulados']=array();
        $response['no_anulados']=array();
        foreach ($cheques as $row) {
            $rs_buscar = $obBD_con1->getArrayConsulta(7,array('Ban_Cod'=>$Ban_Cod,'Che_Num'=>$row), $obBD_conexion);
            if (count($rs_buscar) >= 1) {
                //throw new Exception('El cheque N.' . row . ' se encuentra registrado');
                array_push($response['no_anulados'],$row);
            }else{
                
                array_push($response['anulados'],$row);
            }
        }
        $response['message']="Se anularon ". count($response['anulados'])." cheque(s) con �xito";
        
        if (count($response['anulados'])>0 && count($response['anulados'])<=20){
            //creando comprobante
            $data=$_POST; 
            $data['Prv_Cod']=$Prv_Cod;
            $data['Com_Num']=$Com_Num;
            $data['Tia_Cod']=$tipo_asiento['Tia_Cod'];
            $data['Com_Con']="Comprobante generado automaticamente por anulacion de grupo de cheques";
            $data['Com_Obs']="Comprobane inactivo generado por anulacion de cheques";
            $obBD_conIns->operacionobBD(6,$data,$obBD_conexionIns);
            $Com_Cod= $obBD_conIns->insercionid($obBD_conexionIns);
            foreach ($response['anulados'] as $row) {
               //insertando asientos del comprobante
                $obBD_conIns->operacionobBD(8,array('Com_Cod'=>$Com_Cod,'Pld_Cod'=>$Pld_Cod,'Glosa'=>$row.'Cheque N�'),$obBD_conexionIns);
               // creando cheque relacionado al asiento contable
                $obBD_conIns->operacionobBD(9,array('Che_Cod'=>1,'Prv_Cod'=>$Prv_Cod,'Ban_Cod'=>$Ban_Cod,'Asi_Cod'=>$obBD_conIns->insercionid($obBD_conexionIns),'Che_Num'=>$row,'Che_Fec'=>$Com_Fec,'Che_Obs'=>$data['Com_Obs']),$obBD_conexionIns);
            }  
        }else{
            if(count($response['anulados'])>20)
                throw new Exception('Solo se pueden eliminar hasta 20 cheques');
            throw new Exception('Los cheques no pudieron ser anulados');
        }
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
        if ($obBD_conIns->Error == 0) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = $obBD_conIns->MsgError;
        }
    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $response['message'] = $ex->getMessage();
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
        $obBD_con1->echoJson($response);
    }
    $obBD_con1->echoJson($response);
}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Cheques Anular Secuencia [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>  
        <style>#tabs.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}
        </style>
        <script type="text/ecmascript" src="../VALIDACIONES/tes_val_cheque_ban.js?a=1"></script>
        <script>
            var baja_che=1;
        </script>
        <style>
            .swlFlyout{
                height: 60px !important;
                min-width: 150px !important;
                z-index: 9999 !important;
            }
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Anular Cheques  </h3><p id="cabeceraPuntoImp" class="text-right col-sm-12  " style="margin-top:-15px;"></p></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="factura">
                    <div class="row">
                        <div id="panelAnulVentas" >
                            <form class='form-horizontal normal' id='form_anular' action="javascript:validaCampos(this)">
                                <div class="col-sm-6 col-sm-offset-3">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Datos de Cheques</legend>
                                        <div class='row form-group col-sm-12'>
                                            <label class="col-sm-4 control-label label-sm required">Periodo:</label>
                                            <div class="col-sm-6">
                                                <select class="form-control input-sm" id="Pec_Cod" name="Pec_Cod"  required="">
                                                    <option value=0>----</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class='row form-group col-sm-12'>
                                            <label class="col-sm-4 control-label label-sm required">Banco:</label>
                                            <div class="col-sm-6">
                                                <select id="Pld_Cod" name="Pld_Cod" class="form-control input-sm"></select>
                                            </div>
                                        </div>
                                        <div class='row form-group col-sm-12'>
                                            <label class="col-sm-4 control-label label-sm required">Fecha:</label>
                                            <div class="col-sm-6">
                                              <div class="input-group">
                                                  <input id="Caj_Fec" name="Caj_Fec" type="text" class="form-control input-sm readOnly ret_field datepickers"  required=""  pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                                                  <span class="input-group-addon input-sm" title="Fecha de Anulacion"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                              </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Datos de Eliminaci�n</legend>
                                        <div class='row form-group col-sm-12'>
                                            <label class='col-sm-4 control-label label-sm required'>Tipo de Eliminaci�n:</label>
                                            <div class="col-sm-6">
                                                <select name='Tipo_Eliminacion' id='Tipo_Eliminacion' class='form-control input-sm'>
                                                    <option value=1>Secuencial</option>
                                                    <option value=2>Uno a Uno</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div  id='panel_secuencia'>
                                            <div class='row form-group col-sm-12'>
                                                <label class='col-sm-4 control-label label-sm required'>Inicio:</label>
                                                <div class="col-sm-4">
                                                    <div class="input-group input-group-sm">
                                                        <input name="Secuencia_Ini" type="text" class="form-control input-sm" onkeypress="return validar_numeric(event);" required="" />
                                                        <span class="input-group-addon validate" ><i></i></span>
                                                    </div>
                                                </div>
                                            </div >
                                            <div class='row form-group col-sm-12'>                                 
                                                <label class='col-sm-4 control-label label-sm required'>Fin:</label>
                                                <div class="col-sm-4">
                                                    <div class=" input-group input-group-sm">
                                                        <input name="Secuencia_Fin" type="text" onkeypress="return validar_numeric(event);" class="form-control input-sm" required="" />
                                                        <span class="input-group-addon validate" ><i></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div  id='panel_uno_uno' >
                                            <div class='row form-group col-sm-12'>
                                                <label class='col-sm-4 control-label label-sm required'>N�meros:</label>
                                                <div class="col-sm-6">
                                                    <div class="input-group">
                                                        <input class="form-control input-sm" type="text" id="numero_nuevo" onkeypress="return validar_numeric(event);"/>
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-sm btn-success" type="button" id="add_num"><span class="glyphicon glyphicon-plus-sign"></span></button>
                                                        </span> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="list-striped col-sm-6 col-sm-offset-4">
                                                <fieldset class="exa-fieldset">
                                                    <legend class="Titulos2">N�meros para Anular</legend>
                                                    <div class="list_numeros" id="list_numeros">
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                    </fieldset> 
                                    <div class="form-group center col-sm-12">
                                        <button class="btn btn-sm btn-primary" type="submit"><i class="glyphicon glyphicon-trash"></i> Anular</button>
                                    </div>
                                </div>
                            </form>
                        </div>              
                        <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                    </div>
                </div>
            </div>
        </div>
   

        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script>$.clearValidate();</script>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script> 
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
        <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />    
    </BODY>
</HTML>