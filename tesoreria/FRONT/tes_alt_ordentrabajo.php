<?php
/**
 * @abstract Permite realizar el registro de un proceso de facturaciï¿½n de viajes
 * @author Erick Cordova
 * @version 2.0
 * Fecha de creación  2017-07-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ordentrabajo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


$obBD_conexion = new Class_Log_Conexion_OrdenTrabajo($Ses_Dat_Dis);
$obBD_con1 =  new Class_Logica_OrdenTrabajo;

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

if(isset($nameSave)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;

    $tipo='';
    $descripcion='';
    $ventana='';

    switch($select) {
        case 'Par': $tipo='P'; $descripcion=$data['partes']; $ventana= 'partes'; break;
        case 'Des': $tipo='D'; $descripcion=$data['descripcion']; $ventana= 'descripcion';break;
        case 'Rep': $tipo='R'; $descripcion=$data['repuestos']; $ventana= 'repuestos';break;
        case 'Ser': $tipo='S'; $descripcion=$data['servicios']; $ventana= 'servicios';break;   
    }

    $data['Tipo']=$tipo;
    $data['Descripcion']=$descripcion;

    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);    
        $obBD_con1->operacionobBD(11,$data,$obBD_conexion);
        $resp['select'] = $select;
        $resp[$select.'_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $resp[$select.'_Des'] = $descripcion;
        $resp['nameSave'] = $ventana;
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   

    if($obBD_con1->Error==0){ $resp['success']=true; }else{$resp=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_con1->MsgError);}  
    utf8_encode_deep($resp); echo json_encode($resp); exit();
}

if(isset($imprimir)){
    $responce['success']=false;
    $table['{empresa}']=$Ses_Emp_Nom;
    $_POST['Suc_Cod']=$Ses_Suc_Cod;
    //ChromePhp::log($table['{empresa}']);
    $_POST['Emp_Cod']=$Ses_Emp_Cod; 

    //ENCABEZADO
    $responce['cabecera'] = $obBD_con1->getArrayConsulta(9, $_POST, $obBD_conexion);
    $cabecera = $responce['cabecera'];

    $table['{empresa}']=$cabecera[0]['Emp_Nom'];
    $table['{logo}']=$cabecera[0]['Emp_Log'];
    $table['{ruc}']=$cabecera[0]['Emp_Ruc'];
    $table['{sucursal}']=$cabecera[0]['Suc_Des'];
    $table['{tel_suc}']=$cabecera[0]['Suc_Te1'];
    $table['{dir_suc}']=$cabecera[0]['Suc_Dir'];
    $table['{cor_suc}']=$cabecera[0]['Suc_Cor'];

    $table['{cliente}'] = $cabecera[0]['cliente'];
    $table['{cedula}'] = $cabecera[0]['Prs_Ced'];
    $table['{direccion}'] = $cabecera[0]['Prs_Dir'];
    $table['{correo}'] = $cabecera[0]['Prs_Cor'];

    $table['{ordNum}'] = $cabecera[0]['Ord_Num'];
    $table['{fechaRecepcion}'] = $cabecera[0]['Ord_Fecha_Rec'];
    $table['{fechaEntrega}'] = $cabecera[0]['Ord_Fecha_Ent'];
    $table['{servicio}'] = $cabecera[0]['Ord_Servicio'];
    $table['{motor}'] = $cabecera[0]['Ord_Motor'];

    $table['{partes}'] = $cabecera[0]['Ord_Partes'];
    $table['{descripcion}'] = $cabecera[0]['Ord_Descripcion'];
    $table['{repuestos}'] = $cabecera[0]['Ord_Repuestos'];
    $table['{observaciones}'] = $cabecera[0]['Ord_Observaciones'];

    $table['{total}'] = $cabecera[0]['Ord_Total'];
    $table['{saldo}'] = $cabecera[0]['Ord_Saldo'];
    $table['{abono}'] = $cabecera[0]['Ord_Abono'];

    $responce['html']=reporteHtml($table,'tes_alt_ordentrabajo.html');
    $responce['success']=true;
        
    utf8_encode_deep($responce);        
    echo json_encode($responce);
    exit();
}

if(isset($clieAjax)){
    $response=$obBD_con1->getPageGrid(1, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);
    $obBD_con1->echoJson($response);
}

if(isset($searchCliente)){
    $responce = $obBD_con1->getRowConsulta(2, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(3, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
}

if(isset($numSec)){
    $data=$_GET;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $data['Emp_Cod']=$Ses_Emp_Cod; 
    $maximo = $obBD_con1->getRowConsulta(4, $data, $obBD_conexion);

    if($maximo['numero'] == null){
    	$maximo['numero'] = 0;
    }
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'Ord_Num'=>$maximo['numero']);
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($saveDocument)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod; 
    $data['Suc_Cod']=$Ses_Suc_Cod;

    $data['Ord_Num'] = (int) $data['Ord_Num'];
    $data['Ord_Total'] = (float) $data['Ord_Total'];
    $data['Ord_Abono'] = (float) $data['Ord_Abono'];
    $data['Ord_Saldo'] = (float) $data['Ord_Saldo'];

    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $responce = $obBD_con1->operacionobBD(5, $data, $obBD_conexion);
    $Ord_Cod=$obBD_con1->insercionid($obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

    if($obBD_con1->Error==0) {
        $responce=array('success'=>true, 'Ord_Cod'=>$Ord_Cod, 'message'=>'Transacci&oacute;n realizada con exito!');
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacci&oacute;n!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}


?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>

        <script>
        $('.panel-main').hide();
        $('.panel-main').show();
        var docs, pagos, data=[],vet_num_ant=0,tic_cod_ant=0, Vet_Index=1, Vet_Selected, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>';
            <?php $array_documentos=$obBD_con1->getArrayConsulta(8,$rs_Punto['Pun_Cod'],$obBD_conexion);?>
        var array_documentos=<?php echo json_encode($array_documentos);?>, ivas_venta=<?php echo json_encode($ivas)?>;
        </script>

        <style>
            .ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}
            .footrow td[aria-describedby="documento_Vet_Imp"],.footrow td[aria-describedby="documento_Vet_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
            #jqGridButtonDiv{float:right; padding-right:10px; position:relative; top:-1px;}
            #Ret_Asu{ vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); }
            #resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px; }
            #resultContent .resp span:first-child{ color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px; }
            .msg_fly { font-size: 12px !important; }
            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
            .footrow td[aria-describedby="items_Vet_Pru"], .footrow td[aria-describedby="items_Vet_Imp"]{padding: 0 !important;}
        </style>

    </HEAD>
    <BODY>


        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  ORDEN DE TRABAJO</h3><p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;"></p></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="fichaMedica">
                    <div class="row">
                        <div class="col-xs-12" id="panelFicha" >
                            <div class="row">
                                <form id="fichaForm" name="fichaForm" method="post" class="form-horizontal normal" action="javascript:" >
                                    <div class="col-md-12 col-xs-12">

                                        <fieldset class="exa-fieldset" id="clieFormTemp">
                                            <legend class="Titulos2"></legend>
                                            <div class="form-group">
                                                <label class="col-xs-1 control-label label-xs required">C&eacute;dula/RUC:</label>
                                                <div class="col-xs-2" >
                                                  <input name="Prs_Cod" type="text" style="display:none;" />
                                                  <input id="Cli_Cod" name="Cli_Cod" type="text" style="display:none;" />
                                                  
                                                  <input name="op_opciones" type="text" value="c" style="display: none;">
                                                  <div class="input-group input-group-xs">
                                                      <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese cliente..."  class="form-control input-xs datatrigger clearable dialogSearch" tabindex="1" required="true" />
                                                    <span class="input-group-btn">
                                                        <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar cliente"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        <button id="Via_Btn" type="button" onclick="$('#viajesGrid').clearGridData(); $('#viajesDialog').dialog('open');" class="btn btn-success btn-xs viajes" title="Seleccionar Viajes"  tabindex="2" style="display:none;"><span class="fa fa-truck"></span></button>
                                                    </span>
                                                  </div>
                                                </div>
                                                <label class="col-xs-1 control-label label-xs">Cliente:</label>
                                                <div class="col-xs-2" ><span id="cliente" name="cliente" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Correo:</label>
                                                <div class="col-xs-2" ><span id="Prs_Cor" name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-1 control-label label-xs">Direcci&oacute;n:</label>
                                                <div class="col-xs-2" ><span id="Prs_Dir" name="Prs_Dir" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Tel&eacute;fono:</label>
                                                <div class="col-xs-2" ><span id="Prs_Tel" name="Prs_Tel" type="text" class="form-control input-xs databind datatitle"></span></div>
                                             </div>
                                            </fieldset>

                                        <div class="form-group">
                                            <label class="col-xs-1 control-label label-xs">Ord. Num:</label>
                                             <div class="col-xs-1" ><div class="input-group input-group-xs"><span class="input-group-addon">OT</span><input id="Ord_Num" name="Ord_Num" type="text" class="form-control input-xs databind datatitle" readonly></div></div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-xs-1 control-label label-xs required">Fecha Recepcion:</label>
                                            <div class="col-xs-2"><input id="Ord_Fecha_Rec" name="Ord_Fecha_Rec" type="text" placeholder="" class="form-control input-xs datepicker databind" required></div>
                                            <label class="col-xs-1 control-label label-xs required">Fecha Entrega:</label>
                                            <div class="col-xs-2"><input id="Ord_Fecha_Ent" name="Ord_Fecha_Ent" type="text" placeholder="" class="form-control input-xs datepicker databind" required></div>
                                            <label class="col-xs-1 control-label label-xs required">Servicio:</label>
                                                <div class="col-xs-2">
                                                    <div class="input-group input-group-xs col-xs-12">
                                                        <?php $opciones = $obBD_con1->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod, 'Tipo' => 'S') , $obBD_conexion);?>
                                                        <select name="Ord_Servicio" id="Ser_Cod" class="form-control" required>
                                                          <?Php foreach($opciones as $row){ echo"<option value='$row[Ord_Opc_Descripcion]'>$row[Ord_Opc_Descripcion]</option>"; } ?>
                                                        </select>
                                                        <span class="input-group-btn"><button onclick="$('#serviciosForm').setData({}); $('#serviciosDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>
                                                    </div>
                                                </div> 

                                            <label class="col-xs-1 control-label label-xs required">Motor/Marca:</label>
                                            <div class="col-xs-2" ><input name="Ord_Motor" type="text" class="form-control input-xs databind datatitle" style="text-transform:uppercase;"></div>
                                        </div>    

                                        <div style="margin-top: 30px;" class="form-group">
                                            <div class="col-sm-6">
                                               <label class="col-sm-1 control-label label-xs required" for="addpartes">Partes:</label>
                                               <div class="col-sm-3">
                                                   <div class="input-group input-group-sm">
                                                        <?php $opciones = $obBD_con1->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod, 'Tipo' => 'P') , $obBD_conexion);?>
                                                        <select name="Par_Cod" id="Par_Cod" class="form-control" required>
                                                          <?Php foreach($opciones as $row){ echo"<option value='$row[Ord_Opc_Cod]'>$row[Ord_Opc_Descripcion]</option>"; } ?>
                                                        </select>
                                                        <span class="input-group-btn"><button onclick="$('#partesForm').setData({}); $('#partesDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>
                                                      </div>  
                                                    </div>
                                                <div class="col-sm-1">
                                                    <button id="btnaddpartes" name="btnaddpartes" type="button" onclick="agregarPartes();" class="btn btn-primary btn-xs "><i class="glyphicon glyphicon-plus"></i> Agregar</button>
                                                </div> 
                                             </div>

                                             <div class="col-sm-6">
                                               <label class="col-sm-1 control-label label-xs required" for="adddescripcion">Descripcion:</label>
                                               <div class="col-sm-3">
                                                    <div class="input-group input-group-sm">
                                                        <?php $opciones = $obBD_con1->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod, 'Tipo' => 'D') , $obBD_conexion);?>
                                                        <select name="Des_Cod" id="Des_Cod" class="form-control" required>
                                                          <?Php foreach($opciones as $row){ echo"<option value='$row[Ord_Opc_Cod]'>$row[Ord_Opc_Descripcion]</option>"; } ?>
                                                        </select>
                                                        <span class="input-group-btn"><button onclick="$('#descripcionForm').setData({}); $('#descripcionDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>  
                                                    </div>
                                               </div>
                                                <div class="col-sm-1">
                                                    <button id="btnaddpartes" name="btnaddpartes" type="button" onclick="agregarDescripcion();" class="btn btn-primary btn-xs "><i class="glyphicon glyphicon-plus"></i> Agregar</button>
                                                </div> 
                                             </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="col-sm-6" >
                                                <textarea id="Ord_Partes" name="Ord_Partes" class="form-control" rows="12" placeholder="Partes"></textarea>
                                            </div>
                                            <div class="col-sm-6" >
                                                <textarea id="Ord_Descripcion" name="Ord_Descripcion" class="form-control" rows="12" placeholder="Descripcion"></textarea>
                                            </div>
                                        </div>

                                         <div style="margin-top: 10px;" class="form-group">
                                            <div class="col-sm-6">
                                               <label class="col-sm-1 control-label label-xs required" for="addrepuestos">Repuestos:</label>
                                               <div class="col-sm-3">
                                                   <div class="input-group input-group-sm">
                                                        <?php $opciones = $obBD_con1->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod, 'Tipo' => 'R') , $obBD_conexion);?>
                                                        <select name="Rep_Cod" id="Rep_Cod" class="form-control" required>
                                                          <?Php foreach($opciones as $row){ echo"<option value='$row[Ord_Opc_Cod]'>$row[Ord_Opc_Descripcion]</option>"; } ?>
                                                        </select>
                                                        <span class="input-group-btn"><button onclick="$('#repuestosForm').setData({}); $('#repuestosDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>
                                                      </div>  
                                               </div>
                                                <div class="col-sm-1">
                                                    <button id="btnaddrepuestos" name="btnaddrepuestos" type="button" onclick="agregarRepuestos();" class="btn btn-primary btn-xs "><i class="glyphicon glyphicon-plus"></i> Agregar</button>
                                                </div> 
                                             </div>
                                         </div>
                                        <div class="form-group">
                                            <div class="col-sm-12" >
                                                <textarea id="Ord_Repuestos" name="Ord_Repuestos" class="form-control" rows="4" placeholder="Repuestos"></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="col-sm-12" >
                                                <textarea id="Ord_Observaciones" name="Ord_Observaciones" class="form-control" rows="4" placeholder="Observaciones"></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                        	<div class="col-sm-12" >
                                        		<div class="col-xs-3"> </div>
                                                <label class="col-xs-1 control-label label-xs">Total:</label>
                                                <div class="col-xs-1" ><input  id="Ord_Total" name="Ord_Total" onkeyup="calcularSaldo();" type="text" class="form-control input-xs databind datatitle"></div>

                                                <label class="col-xs-1 control-label label-xs">Abono:</label>
                                                <div class="col-xs-1" ><input  id="Ord_Abono" name="Ord_Abono" onkeyup="calcularSaldo();" type="text" class="form-control input-xs databind datatitle"></div>

                                                <label class="col-xs-1 control-label label-xs">Saldo:</label>
                                                <div class="col-xs-1" ><input id="Ord_Saldo" name="Ord_Saldo" type="text"  readonly="true" sclass="form-control input-xs databind datatitle"></div>

                                            </div>
                                         </div>

                                        <div class="form-group">
                                            <label class="col-xs-1 control-label label-xs"></label>
                                        </div>
                                        <div class="form-group">
                                            <div class="form-group">
                                                <div class="col-xs-12 text-center">
                                                    <button id="btnSave" name="btnSave" type="button" onclick="guardar();" class="btn btn-primary "><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                                </div> 
                                            </div>
                                        </div>
                                </form>
                            </div>
                            <div class="col-sm-12 Titulos2" style="text-align: center;"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="serviciosDialog" title="Agregar servicios"> 
                <div class="row">
                    <div class="col-md-12" >                
                        <form id="serviciosForm" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,{nameSave:'servicios',select:'Ser'},saveForm)"  >                   
                        <div class="form-group">
                           <label class="col-sm-4 control-label label-sm required">Nombre servicios:</label>  
                           <div class="col-sm-8" ><input type="text" class="form-control input-sm" name="servicios"  value="" required /></div>
                        </div> 
                        </form> 
                    </div>
                </div>
            </div> 

             <div id="partesDialog" title="Agregar partes"> 
                <div class="row">
                    <div class="col-md-12" >                
                        <form id="partesForm" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,{nameSave:'partes',select:'Par'},saveForm)"  >                   
                        <div class="form-group">
                           <label class="col-sm-4 control-label label-sm required">Nombre partes:</label>  
                           <div class="col-sm-8" ><input type="text" class="form-control input-sm" name="partes"  value="" required /></div>
                        </div> 
                        </form> 
                    </div>
                </div>
            </div> 

            <div id="descripcionDialog" title="Agregar descripcion"> 
                <div class="row">
                    <div class="col-md-12" >                
                        <form id="descripcionForm" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,{nameSave:'descripcion',select:'Des'},saveForm)"  >                   
                        <div class="form-group">
                           <label class="col-sm-4 control-label label-sm required">Descripcion:</label>  
                           <div class="col-sm-8" ><input type="text" class="form-control input-sm" name="descripcion"  value="" required /></div>
                        </div> 
                        </form> 
                    </div>
                </div>
            </div> 

            <div id="repuestosDialog" title="Agregar repuestos"> 
                <div class="row">
                    <div class="col-md-12" >                
                        <form id="repuestosForm" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,{nameSave:'repuestos',select:'Rep'},saveForm)"  >                   
                        <div class="form-group">
                           <label class="col-sm-4 control-label label-sm required">Nombre repuestos:</label>  
                           <div class="col-sm-8" ><input type="text" class="form-control input-sm" name="repuestos"  value="" required /></div>
                        </div> 
                        </form> 
                    </div>
                </div>
            </div> 
        
        <!-- Inicio del diálogo para buscar pacientes -->
        <div id="clieDialog" title="B&uacute;squeda de clientes"><form class="form-horizontal normal"> </form></div>
        <script>

            $(document).ready(function(){
                $('#partesDialog').createDialog({height:150, width:530, icon:'plus'});
                $('#descripcionDialog').createDialog({height:150, width:530, icon:'plus'});
                $('#repuestosDialog').createDialog({height:150, width:530, icon:'plus'});
                $('#serviciosDialog').createDialog({height:150, width:530, icon:'plus'});
                addActions('partes');
                addActions('descripcion');
                addActions('repuestos');
                addActions('servicios');
            });  

            function addActions(name){
                $('#'+name+'Form').append('<div class="form-group" style="padding-top:10px;"><label class="col-sm-4 control-label"></label><div class="col-sm-8">'+
                        '<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>'+
                        '<button type="button" onclick="$(\'#'+name+'Dialog\').dialog(\'close\');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>'+
                    '</div></div><div class="form-group Titulos2"><div class="col-md-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div></div>');
            }

            function saveForm(o){ $.saveDataJson("",$.extend(o,$('#'+o['nameSave']+'Form').getData('save'+o['nameSave'].charAt(0).toUpperCase()+o['nameSave'].slice(1))),
                function(resp){
                $('#'+resp['select']+'_Cod').append('<option value="'+resp[resp['select']+'_Des']+'">'+resp[resp['select']+'_Des']+'</option>').val(resp[resp['select']+'_Cod']);
                $('#'+resp['nameSave']+'Dialog').dialog('close');
                return false;
             }); 
            }

            $.createSearchDialog('clieDialog',[
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'cliente', width: 100},
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label: 'Ciudad', name: 'Ciu_Des', hidden:false, width: 50 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectCliente} }
            ],null,null,null,{headertitles:true},{ title:'Cliente', text:'Prs_Ced' });           


        function selectCliente(cliente){
            $('#clieFormTemp').setData($.extend(cliente,{op_opciones:'c'}));
            $('#clieDialog').dialog('close');

            $.post("", { enableDisableCampos: true, Pac_Cod: cliente['Cli_Cod'] }, function(responce) {
                if (responce['success'] === true) {
                    if(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0)){
                        $("#anticipo").css("display", "none");
                    }
                    else{
                        $("#anticipo").css("display", "block");
                    }
                    $("#ant_msg").html(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? "$ 0.00" : $.numFormat(responce['data_ant']));
                    $("#ant_msg")[responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? 'removeClass' : 'addClass']('alert alert-danger bold');
                } else {
                    $("#anticipo").css("display", "none");
                    $("#ant_msg").html("$ 0.00");
                    $("#ant_msg").removeClass('alert alert-danger bold');
                    $.alert(responce['message']);
                }
            }, 'json');
            numFicha();
        }
        </script>

        <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>

        <script type="text/javascript">
        $.clearValidate();
        function searchCliente(ced,tipo){
            (tipo==='ec')?ced=ced.substring(0,10):ced;
            $.post("",{searchCliente:true,Prs_Ced:ced}, function(response){
                if(response['existe']===true){
                    $.alert('El cliente '+ced+' ya se encuentra registrado..!!');
                    clear();
                }
            },'json').fail(function (){$.alert();});
        }

        function clear(){
            $('#clieCreateDialog').setData({Cli_Tic:'N',Prs_Ciu:'Ec',Prs_Sex:'M'});
            $('#Prs_Ced').val('').focus();
            $('.juridico').hide();$('.natural').show();
        }


        function numFicha(){
            $.post("",{numSec:true}, function( response ) {
                var num = parseInt(response['Ord_Num']) + 1;
                $('#Ord_Num').val('000'+num);
            },'json').fail(function (){
                $.alert();
            });
        }

        function limpiar(){
        	$("#fichaForm")[0].reset();
        	$("#Prs_Cor").text("");
			$("#cliente").text("");
			$("#Prs_Dir").text("");
			$("#Prs_Tel").text("");
            $("#Ord_Partes").text("");
            $("#Ord_Descripcion").text("");
            $("#Ord_Repuestos").text("");
        }

        function guardar(){
            var cod = $('#Cli_Cod').val();

            var num = $('#Ord_Num').val();
            var fec = $('#Ord_Fecha_Rec').val();
            var fecEn = $('#Ord_Fecha_Ent').val();
            var motor = $('#Ord_Motor').val();
            
            var part = $('#Ord_Partes').val();
            var desc= $('#Ord_Descripcion').val();
            var repu = $('#Ord_Repuestos').val();

            if(cod!='' && fec!='' && num!='' && part!='' && desc!='' && repu!='' && fecEn!='' && motor!=''){
                $.saveDataJson('', $('#fichaForm').getData('saveDocument'), 
                function( resp ){
                if(resp['success']==true){
                   //$.alert("Orden de trabajo guardada con exito");
                   limpiar();
                   numFicha();
                   imprimirOrden(resp['Ord_Cod']);
                }else{
                    $.alert("No se pudo realizar la transaccion");
                }
                return false;
                });
            }else{
                $('#cliente').focus();
                $.alert('Ingrese todos los campos');

            }
        }

        function calcularSaldo(){
          var saldo = 0;
          var total = 0;
          var abono = 0;
          if($('#Ord_Total').val() != ""){
            total = parseFloat($('#Ord_Total').val());
          }
          if($('#Ord_Abono').val() != ""){
             abono = parseFloat($('#Ord_Abono').val());
          }
          saldo = total - abono;
          $('#Ord_Saldo').val(saldo);
        }


        function imprimirOrden(orden){
            $.post( "",{imprimir:true, Ord_Cod:orden}, function( response ) {
                if(response['success']===true){
                    $(response['html']).printElement({pageTitle:'Exa Software Contable'});
                }else{$.alert(response['message']);}                                   
             },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
        }

        function agregarPartes(){
            var texto = $( "#Par_Cod option:selected" ).text();
            $('#Ord_Partes').append(texto + ', ');
        }

        function agregarDescripcion(){
            var texto = $( "#Des_Cod option:selected" ).text();
            $('#Ord_Descripcion').append(texto + ', ');
        }

        function agregarRepuestos(){
            var texto = $( "#Rep_Cod option:selected" ).text();
            $('#Ord_Repuestos').append(texto + ', ');
        }

        $("#Ord_Fecha_Ent").createDatePickers();
  		$("#Ord_Fecha_Rec").createDatePickers();

        </script>
          
    </BODY>
</HTML>