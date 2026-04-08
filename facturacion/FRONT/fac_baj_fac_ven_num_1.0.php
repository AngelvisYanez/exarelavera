<?php
/**
 * @abstract Permite anular documentos de ventas para no ser utilizados
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creaci�n  2017-07-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_facturaVenta;
//borrar debug completo
//$obBD_con1->debug(true);
$configs = $obBD_con1->getRowConsulta(12, $Ses_Emp_Cod,$obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(85,$Ses_Suc_Cod.'*'.$Ses_Prs_Cod,$obBD_conexion);
//Secci�n para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
$rs_Punto = $obBD_con1->getRowConsulta(7,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
$Ciu_Des=$obBD_con1->getRowConsulta(6,$Ses_Usu_Cod, $obBD_conexion);

//$obBD_con1->echoLog($obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion));
/* Configuraciones de la Empresa */



if(isset($getDocuments)){
    $resp['documents']=$obBD_con1->getArrayConsulta(8,$rs_Punto['Pun_Cod'],$obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}

if(isset($autorizaAjax)){
    $resp['autorizaciones']=$obBD_con1->getArrayConsulta(100, $rs_Punto['Pun_Cod'].'*'.$Tic_Cod.'*'.'LIMIT 10', $obBD_conexion,$page, $rows);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}


if(isset($getDateServ)){
    $resp['hoy']=date("Y-m-d");
    $obBD_con1->echoJson($resp);
}

//Secci�n para listar los clientes registrados en la empresa
if(isset($clieAjax)){
    $contar = $obBD_con1->getRowConsulta(1,  $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    if($contar['total']>0)
        $responce['rows'] = $obBD_con1->getArrayConsulta(1, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}


//Secci�n para obtener el n�mero de secuencia
if(isset($numeroSec)){
    
    $siguiente=$obBD_con1->getRowConsulta(10,$Aut_ini.'*'.$Aut_Fin.'*'.$Aut_Sri.'*'.$Tic_Cod.'*'.$Ses_Suc_Cod,$obBD_conexion);
    $response['Vet_Num']=$siguiente['siguiente'];
    $response['contador']=$siguiente['contador'];
    echo json_encode($response);
    exit();
}
//Secci�n para comprobar si el n�mero de secuencia ya se encuentra registado
if(isset($existeNumdoc)){
    $rs_numdocumento=$obBD_con1->getRowConsulta(11,$Ses_Suc_Cod.'*'.$Aut_Sri.'*'.$Vet_Num.'*'.''.'*'.$Pun_Sri,$obBD_conexion);
    if($rs_numdocumento['total']*1>0){$response['existe']=true;}else{$response['existe']=false;}
    $response['success']=true;
    $obBD_con1->echoJson($response);
}



/* Consulta del codigo retencion */
if(isset($codiAjax)){
    $data=$_GET;
    $contar = $obBD_con1->getRowConsulta(21, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce=$pagination['data']; $data['limits']=$pagination['limits'];
    if($contar['total']>0){
        $responce['rows'] = $obBD_con1->getArrayConsulta(21, $data, $obBD_conexion);
        if($configs['Cof_Con']=='S'&&!empty($Pla_Cod)){
            foreach ($responce['rows'] AS &$r){
                $cuenta = $obBD_con1->getRowConsulta(22,$Pla_Cod.'*'.$r['Ren_Cod'].'*V', $obBD_conexion);
                if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
            }unset($r);
        }
    }
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}



if(isset($getDataPunto)){
    $resp = $obBD_con1->getRowConsulta(7,$Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    $obBD_con1->echoJson($resp); 
}


if(isset($getCliente)){
    $resp['cliente'] = $obBD_con1->getRowConsulta(106,$Ses_Emp_Cod,$obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}


/* Secci�n para realizar el guardado */
if(isset($saveDocument)){
    $response=array();
    /* Creacion de Objetos de Conexiones para Proceso de Guardado de Venta*/
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
/*Habilita Debuger de SQLs en Proceso de Guardado de Venta*/
    //$obBD_con1->debug(true);
    $obBD_conIns->debug(true);
/*Inicio de Transaccion*/    
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
/*Verifica usuario tenga Permisos de Vendedor*/
    if(empty($vendedor['Vnd_Cod'])){
      $response['message']="No tiene permisos de Vendedor!";
    }
    $Vnd_Cod=$vendedor['Vnd_Cod'];
    $Pro_Cod = $obBD_con1->getRowConsulta(104,$Ses_Emp_Cod,$obBD_conexion);
    $Cli_Cod = $obBD_con1->getRowConsulta(106,$Ses_Emp_Cod,$obBD_conexion);
    $Iva_Cod = $obBD_con1->getRowConsulta(110,$Ses_Emp_Cod,$obBD_conexion);
    
    if(empty($Cli_Cod)){
        $response['message']="No tiene Consumidor Final asociado!";
    }
    if(empty($Pro_Cod)){
        $response['message']="No tiene Productos asociados!";
    }
    if(empty($Iva_Cod)){
        $response['message']="No tiene asociados codigos de ivas!";
    }
    try{
        //Seccion para verificar si la caja ya fue aperturada
        $rs_Caja = $obBD_con1->getRowConsulta(76,$rs_Punto['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexion);
        if(empty($rs_Caja['Caj_Cod'])){
            //Secci�n para aperturar la caja a trav�s de insert a la tabla caja_aper
            $obBD_conIns->operacionobBD(77,$rs_Punto['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexionIns);
            //Secci�n para obtener el id ingresado en la tabla caja_aper
            $Caj_Cod=$obBD_conIns->insercionid($obBD_conexionIns->conexion);
        }else{
            $Caj_Cod=$rs_Caja['Caj_Cod'];
        }

        /* valida que no exista el documento */
        
        $response['no_registrados']=array();
        $response['anulados']=array();
        foreach ($Vet_Num_Array as $value) {
            $num_existe_gencod=$obBD_con1->getRowConsulta(50, $Ses_Suc_Cod.'*'.$Aut_Sri.'*'.$value.'*'.$Vet_Cod.'*'.$Pun_Sri, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
            if($num_existe_gencod['total']*1>0) {
                array_push($response['no_registrados'],$value);
            }else{
                array_push($response['anulados'],$value);
            }
        }
        //valores por defectos de item a insertar
        $item=array();
        $item['Vet_Ite'] = 1;
        $item['Vet_Can']= 0;
        $item['Iva_Cod']=$Iva_Cod['Iva_Cod'];
        $item['Vet_Pru']= 0;
        $item['Vet_Imp']= 0;
        $item['Pro_Cod']= $Pro_Cod['Pro_Cod'];
        

        //$obBD_con1->echoLog($Cli_Cod);
        foreach ($response['anulados'] as $Vet_Num) {
            
             /* Cabecera de la factura de venta */
            $obBD_conIns->operacionobBD(111, $Tic_Cod.'*'.$Cli_Cod['Cli_Cod'].'*'.$Ciu_Des['Ciu_Cod'].'*'.$Caj_Cod.'*'.$rs_Punto['Vnd_Cod'].'*'.
                        $Vet_Num.'*La Venta fue creada con estado Analuda*'.$Aut_Cod.'*'.'0'.'*'.$hora.'*'.NULL.'*'.$Vet_Aut.'*'.NULL.'*'.NULL.'*'.NULL.'*'.NULL.'*I', $obBD_conexionIns);
            $Vet_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
            //$obBD_con1->echoLog($Vet_Cod);
            $item['Vet_Cod'] = $Vet_Cod;

            /* Item Documento */
            $obBD_conIns->operacionobBD(86,$item, $obBD_conexionIns);
        }

    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $response['message']=$ex->getMessage();
        echo json_encode($response); exit();
    }

    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    if ($obBD_conIns->Error == 0) {
        $response['success']=true;
        $response['message']='Transaccion realizada con Exito';
    }
    else{
        $response=array(success=>false,message=>"No se ha logrado realizar la Transaccion",error=>$obBD_ins1->MsgError);
    }
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Ventas Anular Secuencia [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script language="javascript" src="../VALIDACIONES/fac_baj_ven_num.js"></script>
        <style>
            .msg_fly 
            {
                font-size: 12px !important;
            }
            .activo {
                color:green !important;
            }
            .inactivo{
                color:red !important;
            }
           
            
            #panelAnulVentas{
                height: 350px ;
            }
            .row{
                height: 25px ;
            }
            #form_anular{
                width: 80%;
                margin: 0 auto;
                position: absolute;
                top: 30px; left: 0; bottom: 0; right: 0;
            }
            textarea {
                overflow-y: auto;
                -ms-overflow-style:auto;
            }
            
            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
        </style>
    </HEAD>
    <BODY>
        
        
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Anular Documentos de Ventas </h3><p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;"></p></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="factura">
                    <div class="row">
                        <div class="col-xs-9" id="panelAnulVentas" >
                            <form class='form-horizontal normal' id='form_anular'>
                                <div class='row form-group col-xs-12'>
                                    <label class="col-xs-5 control-label label-xs required">Cliente:</label>
                                    <div class="col-xs-5">
                                      <div class="input-group">
                                          <input id="Cliente_Nombre" name="Cliente" type="text" class="form-control input-xs readOnly" readOnly  required=""/>
                                          <span class="input-group-addon input-xs" title="Cliente Asignado"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                      </div>
                                    </div>
                                </div>
                                <div class='row form-group col-xs-12'>
                                    <label class="col-xs-5 control-label label-xs required">Fecha:</label>
                                    <div class="col-xs-5">
                                      <div class="input-group">
                                          <input id="Caj_Fec" name="Caj_Fec" type="text" class="form-control input-xs readOnly ret_field datepickers"  required=""  pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                                          <span class="input-group-addon input-xs" title="Fecha de Anulacion"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                      </div>
                                    </div>
                                </div>
                                <div class='row form-group col-xs-12'>
                                    
                                    <label class='col-xs-5 control-label label-xs required'>Documento :</label>
                                    <div class="col-xs-5">
                                        <select name='Tic_Cod' id='Tic_Cod' class='form-control input-xs'>
                                        </select>
                                    </div>
                                </div>
                                <div class='row form-group col-xs-12'>
                                    <label class='col-xs-5 control-label label-xs required'>Autorización :</label>
                                    <div class="col-xs-5 ">
                                        <div class='input-group'>
                                            <select name='Aut_Cod' id='Aut_Cod' class='form-control input-xs'>
                                                <option value=0>Seleccione...</option>
                                            </select>
                                            <span class="input-group-addon input-xs" id='span_aut' title="Información de Autorización"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                        </div>  
                                    </div>
                                </div>
                                <div class='row form-group col-xs-12'>
                                    <label class='col-xs-5 control-label label-xs required'>Tipo de Eliminación:</label>
                                    <div class="col-xs-5">
                                        <select name='Tipo_Eliminacion' id='Tipo_Eliminacion' class='form-control input-xs'>
                                            <option value=1>Secuencial</option>
                                            <option value=2>Uno a Uno</option>
                                        </select>
                                    </div>
                                </div>
                                <div  id='panel_secuencia'>
                                    <div class='row form-group col-xs-12'>
                                        <label class='col-xs-5 control-label label-xs required'>Inicio:</label>
                                        <div class="col-xs-3">
                                            <div class="input-group input-group-xs">
                                                <input name="Secuencia_Ini" type="text" class="form-control input-xs" onkeypress="return validar_numeric(event);" required="" />
                                                <span class="input-group-addon validate" ><i></i></span>
                                            </div>
                                        </div>
                                    </div >
                                    <div class='row form-group col-xs-12'>                                 
                                        <label class='col-xs-5 control-label label-xs required'>Fin:</label>
                                        <div class="col-xs-3">
                                            <div class=" input-group input-group-xs">
                                                <input name="Secuencia_Fin" type="text" onkeypress="return validar_numeric(event);" class="form-control input-xs" required="" />
                                                <span class="input-group-addon validate" ><i></i></span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div  id='panel_uno_uno' >
                                    <div class='row form-group col-xs-12'>
                                        <label class='col-xs-5 control-label label-xs required'>Numeros:</label>
                                        <div class="col-xs-5">
                                            <div class="input-group">
                                                <input class="form-control input-xs" type="text" id="numero_nuevo" onkeypress="return validar_numeric(event);"/>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-xs btn-success" type="button" id="add_num"><span class="glyphicon glyphicon-plus-sign"></span></button>
                                                </span> 
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-striped col-md-5 col-md-offset-5">
                                        <fieldset class="exa-fieldset">
                                            <legend class="Titulos2">Números para Anular</legend>
                                            <div class="list_numeros" id="list_numeros">
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                                <div class="form-group center col-xs-12">
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                </div>
                            </form>
                        </div>
                        
                        
                        
                        <!--panel resultado de eliminacion-->
                        <div class='col-xs-3 hidden' id="tabs">
                            <div id="resultado" class="panel panel-primary"> 
                                <div style="height: 30px;" class="panel-heading">
                                    <h5 class="panel-title">Anulados</h5>
                                </div>
                                <div id="tabs-1" class="panel-body" name='tab-anulados' class="form-group"></div>
                                <div class="panel-heading" style="height: 30px;">
                                    <h5 class="panel-title">Imposible Anular</h5>
                                </div>
                                <div id="tabs-2" class="panel-body" name='tab-imposible' class="form-group"></div>
                                <div class="center"><a class=" btn btn-danger" id="close_button">Cerrrar</a></div>
                            </div>
                            
                        </div>
                        
                        <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    </BODY>
</HTML>
