<?php 
/**
 * Interfaz para llevar el control de tickets de parte de las empresas
 *
 * @author Alejandro CAmacho
 * @version 1.0
 * Fecha de actualizaci�n:	2021/03/22
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ordentrabajo.php');

require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


$obBD_conexion = new Class_Log_Conexion_OrdenTrabajo($Ses_Dat_Dis);
$obBD_con1 =  new Class_Logica_OrdenTrabajo;

$hoy = date("Y-m-d H:i:s");
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

if(isset($saveDocument)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod; 
    $data['Suc_Cod']=$Ses_Suc_Cod;

    $data['Ord_Num'] = (int) $data['Ord_Num'];
    $data['Ord_Cod'] = (int) $data['Ord_Cod'];
    $data['Ord_Total'] = (float) $data['Ord_Total'];
    $data['Ord_Abono'] = (float) $data['Ord_Abono'];
    $data['Ord_Saldo'] = (float) $data['Ord_Saldo'];

    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $responce = $obBD_con1->operacionobBD(8, $data, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'message'=>'Transacci&oacute;n realizada con exito!');
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacci&oacute;n!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
 
if(isset($searchFiltro))
{
	$data=$_GET;
	$data['Emp_Cod']=$Ses_Emp_Cod; 

    $data = $obBD_con1->getArrayConsulta(6, $data, $obBD_conexion);
    $obBD_con1->echoJson(array(
        'rows'=>$data,
        'total'=>1,
        'records'=>count($data),
        'success'=>true
    ));
    exit(); 
}

if(isset($cargarEditar))
{
    $response = $obBD_con1->getRowConsulta(7, $_POST, $obBD_conexion);
    $obBD_con1->echoJson($response);
    exit(); 
}

if(isset($clieAjax)){
    $response=$obBD_con1->getPageGrid(1, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);
    $obBD_con1->echoJson($response);
    exit();
}

if(isset($searchCliente)){
    $responce = $obBD_con1->getRowConsulta(2, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(3, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
    exit();
}


?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script language="javascript" src="../VALIDACIONES/tes_val_ordentrabajo.js?x=x11"></script>
         <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    </HEAD>
    <BODY>
        <div id="documentoSearch" class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Modificar Orden de Trabajo</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">

                      <form id="frm_alt_auto" name="frm_alt_auto" class="form-vertical" autocomplete="off" action="javascript:$('#tableResult').Search('#frm_alt_auto','searchFiltro');">
                          <fieldset class="exa-fieldset">
                              <legend class="Titulos2">Filtrar Orden de Trabajo</legend>
                               

                               <div class="col-xs-2" >
	                               	<div class="input-group input-group-xs">
	                                  <input id="cliente" name="cliente" size="50" maxlength="50" type="text" class="form-control input-sm clearable" autofocus=""  placeholder="Cliente..." />
	                              </div>
                                </div>

								<!-- Fecha -->
                              <div class="col-sm-2"> 
                                <div class="input-group input-group-xs">
                                	<span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
									    <input type="checkbox"value="S" offval="N"  id="f_recepcion" name="f_recepcion" onchange="checkRecepcion()">
									</span>
                                  <span class="input-group-addon bold alert-info">Fecha Recepcion:</span>
                                  <input name="txt_fec_recepcion" type="text" id="txt_fec_recepcion" class="form-control input-sm datepicker databind" disabled="true" style="text-align: center;"/>
                                </div>
                              </div>      

                              <!-- Fecha -->
                              <div class="col-sm-2"> 
                                <div class="input-group input-group-xs">
                                	<span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
									    <input type="checkbox" value="S" offval="N" id="f_entrega" name="f_entrega" onchange="checkEntrega()">
									</span>
                                  <span class="input-group-addon bold alert-info">Fecha Entrega:</span>
                                  <input name="txt_fec_entrega" type="text" id="txt_fec_entrega" class="form-control input-sm datepicker databind" disabled="true" style="text-align: center;"/>
                                </div>
                              </div>     

                              <!-- Button -->
                              <div class="col-sm-1">                                            
                                <button id="btnSearch" name="btnSearch" class="btn btn-success">Buscar</button>                                              
                              </div>

                              <div class="col-sm-3"></div>
                          </fieldset>                                    
                      </form>

                      <div >
                          <table id="tableResult"></table>
                          <div id="tableResultPager"></div>
                          <br>
                      </div>                               
                    </div>
                </div>                
            </div>
        </div>

        <div id="documentoMain" class="panel panel-main">
        	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Modificar Orden de Trabajo</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="fila" class="row">

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
                                <input id="Ord_Cod" name="Ord_Cod" type="text" style="display:none;" />
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
                                    <div class="col-xs-1" ><input id="Ord_Total" name="Ord_Total" onkeyup="calcularSaldo();" type="text" class="form-control input-xs databind datatitle"></div>

                                    <label class="col-xs-1 control-label label-xs">Abono:</label>
                                    <div class="col-xs-1" ><input id="Ord_Abono" name="Ord_Abono" onkeyup="calcularSaldo();" type="text" class="form-control input-xs databind datatitle"></div>

                                    <label class="col-xs-1 control-label label-xs">Saldo:</label>
                                    <div class="col-xs-1" ><input id="Ord_Saldo" name="Ord_Saldo" type="text" readonly="true" class="form-control input-xs databind datatitle"></div>

                                </div>
                             </div>

                            <div class="form-group">
                                <label class="col-xs-1 control-label label-xs"></label>
                            </div>
                            <div class="form-group">
                                <div class="form-group">
                                    <div class="col-xs-12 text-center">
                                        <button id="btnSave" name="btnSave" type="button" onclick="modificar();" class="btn btn-primary "><i class="glyphicon glyphicon-floppy-disk"></i> Modificar</button>
                                        <a class="black btn btn-md btn-danger" onclick="atras();" ><i class="glyphicon glyphicon-ban-circle"></i> Cancelar</a>
                                    </div> 
                                </div>
                            </div>
                    </form>
                  <!--FIN para guardar la orden de trabajo  -->
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
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript">
        	  $('#documentoMain').hide();
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
        </script>
    </BODY>
</HTML>