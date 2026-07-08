<?php
/**
 * @abstract Permite realizar el registro de personal
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci�n  2016-11-15
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_contrato.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion
 */

$obBD_conexion = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_rrhh;
$hoy = date("Y-m-d");
//Secci�n para cargar datos en el Jqgrid referente al personal registrado
if(isset($personalAjax)){ 
    $data=filter_input_array(INPUT_GET);
    $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(1, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0){ $responce['rows'] =  $obBD_con1->getArrayConsulta(1, $data, $obBD_conexion); }
    utf8_encode_deep($responce['rows']);
	echo json_encode($responce);exit();
}
//Secci�n para buscar los datos de un tipo de cargo espec�fico
if(isset($buscarTipocargo)){
    $response = $obBD_con1->getRowConsulta(11, $Tic_Cod, $obBD_conexion);
    echo json_encode($response);exit();
}
//Secci�n para saber los contratos en los cuales esta involucrado un empleado
if(isset($contratosEmpleado)){
    $response = $obBD_con1->getArrayConsulta(2,$Per_Cod, $obBD_conexion);	
    echo json_encode($response);exit();
}
//Secci�n para saber los procesos de afiliaci�n en los cuales esta involucrado un empleado
if(isset($afiliacionEmpleado)){
    $response = $obBD_con1->getArrayConsulta(3,$Per_Cod, $obBD_conexion);	
    echo json_encode($response);exit();
}

//Secci�n ajax para guardar un nuevo contrato
if (isset($saveContrato)) {
    if(isset($Indefinido1)&& $Indefinido1=='S') $Con_Fin='9999-12-31';
    if(isset($Indefinido2)&& $Indefinido2=='S') $Afi_Fef='9999-12-31';
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

    //VALIDAR INGRESO DE CONTRATO DUPLICADO 
    $contratos = $obBD_con1->operacionobBD(17, $Per_Cod . '*' . $Con_Ini . '*' . $Con_Fin, $obBD_conexion);

    if($contratos->num_rows == 0){
        //Secci�n para insertar un registro en la tabla contratos_lab
        $obBD_con1->operacionobBD(4, $Tic_Cod . '*' . $Per_Cod . '*' . $Ded_Cod . '*' . $Reb_Cod . '*' . $Con_Ini . '*' . $Con_Fin . '*' . $Con_Mot.'*'.$Con_Exc, $obBD_conexion);
        //Secci�n para obtener el c�digo de la �ltima inserci�n en la tabla contratos_lab
        $Con_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
        //Secci�n para insertar un registro en la tabla sueldos
        $obBD_con1->operacionobBD(5, $Con_Cod . '*' . $Sue_Fec . '*' . $Sue_Val. '*' . $Sue_Va1.'*'.$salarioBasico.'*'.$Sut_Cod, $obBD_conexion);

        //insertar en la tabla pago_contrato
        $obBD_con1->operacionobBD(20, $Con_Cod . '*' . $Bak_Cod. '*' . $Pag_Con_Tip . '*' .$Pag_Con_For  . '*' . $Pag_Con_Cue, $obBD_conexion);

        //Secci�n para insertar un registro en la tabla afiliacion
        if($Afi_Con=="S"){
            $obBD_con1->operacionobBD(6, $Con_Cod . '*' . $Afi_Fei . '*' . $Afi_Fnd. '*' . $Afi_Dte. '*' . $Afi_Dcu . '*' . $Afi_Fef . '*' . $Afi_Mot. '*' . $Afi_Due, $obBD_conexion);
        }
        
	else{
             $obBD_con1->operacionobBD(18, $Con_Cod, $obBD_conexion);
        } 
	 
    }
   
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        if($contratos->num_rows == 0){  
            $response['success'] = true;
        }
        else{
           $response['message'] =  "Ya existe un contrato para las fechas de inicio y fin!";
           $response['error'] = $obBD_con1->MsgError;
        }
    }
    else{
        $response['error'] = $obBD_con1->MsgError;
    }

    echo json_encode($response);
    exit();
}

$sectorial = $obBD_con1->getArrayConsulta(16,"", $obBD_conexion);	
$bancos = $obBD_con1->getArrayConsulta(19,"", $obBD_conexion);   

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Contrato Registrar [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <link rel="stylesheet" href="../../framework/jquery/summernote/summernote.css">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/summernote/summernote.min.js"></script>
        <script src="../../framework/jquery/summernote/lang/summernote-es-ES.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <style>
            th.ui-th-column div{
                white-space:normal !important;
                height:auto !important;
                padding:2px;
            }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Contrato</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix">
                            <ul style="font-size: 12px;">
                                <li><a href="#con_tra">Contrato</a></li>
                                <li><a href="#afi_lia">Afiliaci&oacute;n</a></li>
                            </ul>
                            <div id="con_tra">
                                <form id="formContrato" name="formContrato" class="form-horizontal normal" action="javascript:">
                                    <input type="hidden" id="tic_cod" name="tic_cod">
                                    <input type="hidden" id="Per_Cod" name="Per_Cod">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">Datos del Contrato</legend>
                                                <div class="form-group Titulos2">
                                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                                </div>
                                                <div class="col-md-7 col-sm-8">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Prs_Ced">C&eacute;dula/R.U.C.:</label>  
                                                        <div class="col-sm-6">
                                                            <div class="input-group input-group-xs">                                                  
                                                                <input id="empleado" name="empleado" type="text" class="form-control input-sm" placeholder="Seleccionar personal" required="" onkeypress="return validar_numeric(event);" readonly="" />
                                                                <span class="input-group-btn">
                                                                    <button class="btn btn-success" type="button" onclick="$('#personalDialog').dialog('open');"><span class="glyphicon glyphicon-search" title="Buscar personal"></span></button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <button type="button" id="ver_con" name="ver_con" onclick="if($('#empleado').val()===''){$.alert('Debe seleccionar un empleado..!!');}else{$('#contratosDialog').dialog('open');}" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-folder-open" title="Ver Historial de Contratos"></span> Ver Historial</button>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Tic_Cod">Cargo:</label>  
                                                        <div class="col-sm-6">
                                                            <div class="input-group input-group-xs">
                                                                <?php $row_rs_sub_dep = $obBD_con1->getArrayConsulta(7, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                                <select name="Tic_Cod" id="Tic_Cod" data-placeholder="Seleccione un cargo" class="chzn-select-template-example">
                                                                    <option value=""></option>
                                                                    <?Php
                                                                    foreach ($row_rs_sub_dep as $row) {
                                                                        ?>
                                                                        <optgroup label="<?Php echo mb_convert_encoding($row['Dep_Des'], 'ISO-8859-1', 'UTF-8'); ?>">
                                                                            <?php $row_rs_cargo = $obBD_con1->getArrayConsulta(8, $row['Dep_Cod'], $obBD_conexion); ?>
                                                                            <?Php
                                                                            foreach ($row_rs_cargo as $row) {
                                                                                ?>
                                                                                <option value="<?php echo $row['Tic_Cod']; ?>"><?Php echo mb_convert_encoding($row['Tic_Des'], 'ISO-8859-1', 'UTF-8'); ?></option>
                                                                            <?Php } ?>
                                                                        </optgroup>
                                                                    <?Php } ?>
                                                                </select>
                                                                <span class="input-group-btn">
                                                                    <button id="pop" class="btn btn-success trigger" type="button" data-container="body" data-toggle="popover" data-placement="right" data-content=""><span class="glyphicon glyphicon-info-sign"></span></button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Con_Ini">Inicio Contrato:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Con_Ini" name="Con_Ini" class="form-control input-xs" value="<?php echo $hoy; ?>" type="text" readonly=""/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Con_Fin">Fin Contrato:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Con_Fin" name="Con_Fin" class="form-control input-xs" type="text" readonly="" value="9999-12-31" disabled="disabled"/>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <input type="checkbox" name="Indefinido1" class="fechaFin" value="S" checked="checked" onchange="if($(this).is(':checked')){ $('#Con_Fin').val('9999-12-31').prop('disabled', true); }else{ $('#Con_Fin').val('').prop('disabled', false); } " /> Indefinido
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Relaci&oacute;n Laboral:</label>
                                                        <div class="col-sm-4">
                                                            <?php $rs_relacion = $obBD_con1->getArrayConsulta(9, $Ses_Suc_Cod, $obBD_conexion); ?>
                                                            <select id="Reb_Cod" name="Reb_Cod" class="form-control input-xs">
                                                                <?php foreach ($rs_relacion as $row) { ?>
                                                                    <option value="<?php echo $row['Reb_Cod']; ?>"><?php echo $row['Reb_Des']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Dedicaci&oacute;n Laboral:</label>
                                                        <div class="col-sm-4">
                                                            <?php $rs_dedicacion = $obBD_con1->getArrayConsulta(10, $Ses_Suc_Cod, $obBD_conexion); ?>
                                                            <select id="Ded_Cod" name="Ded_Cod" class="form-control input-xs">
                                                                <?php foreach ($rs_dedicacion as $row) { ?>
                                                                    <option value="<?php echo $row['Ded_Cod']; ?>"><?php echo $row['Ded_Des']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Fecha Inicio Salario:</label>
                                                        <div class="col-sm-4">
                                                            <input type="text" id="Sue_Fec" name="Sue_Fec" class="form-control input-xs" value="<?php echo $hoy; ?>" readonly="">
                                                        </div>
                                                    </div>
													<div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Aplica extenci&oacute;n conyugal:</label>
                                                        <div class="col-sm-2">
                                                            <select id="Con_Exc" name="Con_Exc" class="form-control input-xs">
                                                                    <option value="N">NO</option>
																	<option value="S">SI</option>																	
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs">Salario Sectorial:</label>
                                                        <div class="col-sm-8">
                                                            <select id="Sut_Cod" name="Sut_Cod" class="form-control input-xs" onchange="changeSueldo($(this).val());">
                                                                <option value="">Ninguno</option>
                                                                <?php 
                                                                foreach ($sectorial AS $sec){
                                                                    echo "<option value='$sec[Sut_Cod]'>$sec[Sut_Nom] - $ $sec[Suu_Val]</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Salario:</label>
                                                        <div class="col-sm-4">
                                                            <div class="input-group input-group-xs">
                                                                <input type="text" id="Sue_Val" name="Sue_Val" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);">
                                                                <span class="input-group-addon" title="Salario de Afiliacion al IESS"> <span class="glyphicon glyphicon-info-sign"></span> </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <input type="checkbox" id="salarioBasico" name="salarioBasico" class="salarioBasico" value="S" offval="N" onchange="if($(this).is(':checked')){ $('#Sue_Val').val('0.00').prop('disabled', true); }else{ $('#Sue_Val').val('').prop('disabled', false); } " /> Basico
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Salario Neto:</label>
                                                        <div class="col-sm-4">
                                                            <div class="input-group input-group-xs">
                                                                <input type="text" id="Sue_Va1" name="Sue_Va1" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);" disabled="disabled">
                                                                <span class="input-group-addon" title="Salario Neto a Recibir(Excedente se Registrara como Bonificacion/Anticipo a Utilidad)"> <span class="glyphicon glyphicon-info-sign"></span> </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <input type="checkbox" id="salarioNeto" name="salarioNeto" class="salarioNeto" value="S" offval="N" onchange="if($(this).is(':checked')){ $('#Sue_Va1').val('').prop('disabled', false); }else{ $('#Sue_Va1').val('').prop('disabled', true); } " /> Neto a Recibir
                                                        </div>
                                                    </div>


                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required" >Forma de pago:</label>
                                                        <div class="col-sm-2">
                                                            <select id="Pag_Con_For" name="Pag_Con_For" class="form-control input-xs" onchange="formaPago();">
                                                                    <option value="T">Transferencia</option>
                                                                    <option value="C">Cheque</option>    
                                                                    <option value="E">Efectivo</option>                                                                
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Banco:</label>
                                                        <div class="col-sm-2">
                                                            <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs">
                                                                    <?php 
                                                                        foreach ($bancos AS $banco){
                                                                            echo "<option value='$banco[Bak_Cod]'>$banco[Bak_Des]</option>";
                                                                        }
                                                                    ?>                                                                
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Tipo de cuenta:</label>
                                                        <div class="col-sm-2">
                                                            <select id="Pag_Con_Tip" name="Pag_Con_Tip" class="form-control input-xs">
                                                                    <option value="N">Ninguno</option>
                                                                    <option value="A">Ahorros</option>
                                                                    <option value="C">Corriente</option>                                                                
                                                            </select>
                                                        </div>
                                                    </div>


                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">N&uacute;mero de cuenta:</label>
                                                        <div class="col-sm-4">
                                                            <input type="text" id="Pag_Con_Cue" name="Pag_Con_Cue" class="form-control input-xs txtRight"onkeypress="return validar_decimal(event);"  class="form-control input-xs txtRight" required="" /> 
                                                        </div>
                                                    </div>


                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs">Motivo de Inicio/Fin:</label>
                                                        <div class="col-sm-8">
                                                            <textarea id="Con_Mot" name="Con_Mot" class="form-control"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="afi_lia">
                                <form id="formAfiliacion" name="formAfiliacion" class="form-horizontal normal" action="javascript:">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">Datos de Afiliaci&oacute;n</legend>
                                                <div class="form-group Titulos2">
                                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                                </div>
                                                <div class="col-md-7 col-sm-8">
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Afiliar IEES:</label>
                                                        <div class="col-sm-3">
                                                            <select id="Afi_Con" name="Afi_Con" class="form-control input-xs">
                                                                <option value="N">NO</option>
                                                                <option value="S">SI</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <button type="button" id="ver_afi" name="ver_afi" onclick="if($('#empleado').val()===''){$.alert('Debe seleccionar un empleado..!!');}else{$('#afiliacionDialog').dialog('open');}" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-folder-open"></span> Ver Historial</button>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs">Es Dueño:</label>
                                                        <div class="col-sm-8">
                                                            
                                                            <label class="control-label label-xs"><input type="checkbox" name="Afi_Due" value="S" offval="N" style="margin: -3px 0 0;vertical-align: middle;" /> NOTA: se aplicara un porcentaje distinto de aportación.</label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Fecha Ingreso IESS:</label>
                                                        <div class="col-sm-4">
                                                            <input type="text" id="Afi_Fei" name="Afi_Fei" class="form-control input-xs" value="<?php echo $hoy; ?>" readonly="" placeholder="Seleccionar fecha">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Acumular Fondos Reserva:</label>
                                                        <div class="col-sm-4">
                                                            <select id="Afi_Fnd" name="Afi_Fnd" class="form-control input-xs">
                                                                <option value="S">SI</option>
                                                                <option value="N">NO</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Acumular D&eacute;cimo Tercero:</label>
                                                        <div class="col-sm-4">
                                                            <select id="Afi_Dte" name="Afi_Dte" class="form-control input-xs">
                                                                <option value="S">SI</option>
                                                                <option value="N">NO</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Acumular D&eacute;cimo Cuarto:</label>
                                                        <div class="col-sm-4">
                                                            <select id="Afi_Dcu" name="Afi_Dcu" class="form-control input-xs">
                                                                <option value="S">SI</option>
                                                                <option value="N">NO</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs required">Fecha Salida IESS:</label>
                                                        <div class="col-sm-4">
                                                            <input type="text" id="Afi_Fef" name="Afi_Fef" class="form-control input-xs" readonly="" value='9999-12-31' placeholder="Seleccionar fecha" disabled="disabled" >
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <input type="checkbox" name="Indefinido2" class="fechaFin" value="S" checked="checked" onchange="if($(this).is(':checked')){ $('#Afi_Fef').val('9999-12-31').prop('disabled', true); }else{ $('#Afi_Fef').val('').prop('disabled', false); } " /> Indefinido
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label col-sm-4 label-xs">Motivo Ingreso/Salida IESS:</label>
                                                        <div class="col-sm-8">
                                                            <textarea id="Afi_Mot" name="Afi_Mot" class="form-control"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12"><button type="button" id="btn_gua" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div> 
            </div>
        </div>
        <!-- Inicio del di�logo para buscar personal --> 
        <div id="personalDialog" title="B&uacute;squeda de Personal">  
            <form class="form-horizontal normal"></form>    
        </div>
        <!-- Inicio del di�logo para mostrar historial de contratos --> 
        <div id="contratosDialog" title="Historial de Contratos">  
            <div class="row">
                <div class="col-md-12">
                    <form class="form-horizontal normal">
                        <h5 id="tit_con"></h5>                 
                    </form>
                </div>
                <div class="col-md-12">
                    <table id="list"></table>
                    <div id="listPager"></div>
                </div>                
            </div>
        </div>
        <!-- Inicio del di�logo para mostrar historial de contratos --> 
        <div id="afiliacionDialog" title="Historial de Afiliaciones">  
            <div class="row">
                <div class="col-md-12">
                    <form class="form-horizontal normal">
                        <h5 id="tit_afi"></h5>                
                    </form>
                </div>
                <div class="col-md-12">
                    <table id="list_afi"></table>
                    <div id="listPager_afi"></div>
                </div>                
            </div>
        </div>
        <script type="text/javascript">
            //Secci�n para inicializar componentes
            $(function () {
                //Inicio del di�logo personal
                $.createSearchDialog('#personalDialog',[
                    { label: 'Cód.Int.', name: 'Per_Cod', key: true,hidden:true,viewable: true },                                
                    { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Personal', name: 'personal', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                            
                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                        formatter:function (cellvalue, options, rowObject) { 
                            return $.getGridButton(cargarDatos,rowObject);
                        }
                    }
                ],null,null,null,null,{title:'Personal',options:[{label:'&nbsp;&nbsp;Apellido&nbsp;&nbsp;',value:'d'},
                  {label:'&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;',value:'c'}]}); 
                //Se declara datepicker
                $.createDatePickers("#Con_Ini");
                $.createDatePickers("#Con_Fin");
                $.createDatePickers("#Sue_Fec");
                $.createDatePickers("#Afi_Fei");
                $.createDatePickers("#Afi_Fef");
                //Secci�n para crear y validar tabs
                $("#tabs").createTabs({});
                //Asignamos la funci�n de Chosen
                $("#Tic_Cod").createChosen('input-xs');
                //Secci�n para asignar popup
                $('#Tic_Cod').on('change', function () {
                    $.post("<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>",{'Tic_Cod':$('#Tic_Cod').val(),'buscarTipocargo':true},function(response){
                        cargarPop(response.Tic_Des,response.Tic_Per);
                    },'json');
                });
                //Secci�n inicializar el textarea como editor de texto
                $('#Con_Mot').createWYSIWYG({height: 150});
                $('#Afi_Mot').createWYSIWYG({height: 150});
                //Captura el evento y valida el formulario
                $('#btn_gua').click(function () {
                    saveForm();
                });
                //Se declara el jqgrid para presentar informaci�n de los contratos de un empleado
                $("#list").createGrid({
                    caption:'Contratos Empleado: ',height: 200,width:660,responsive:false,
                    colModel: [
                        {label: 'Cargo', name: 'Tic_Des', width: 150, align: "center"},
                        {label: 'Fecha Inicio', name: 'Con_Ini', width: 70, align: "center"},
                        {label: 'Fecha Fin', name: 'Con_Fin', width: 70, align: "center"},
                        {label: 'Salario', name: 'Sue_Val', width: 70, align: "center"},
                        {label: 'Salario Neto', name: 'Sue_Va1', width: 70, align: "center"}
                    ]
                },true);
                //Se declara el jqgrid para presentar informaci�n de la afiliaci�n de un empleado
                $("#list_afi").createGrid({
                    caption:'Afiliaci&oacute;n Empleado: ', height: 200,width:660,responsive:false,                 
                    colModel: [
                        {label: 'Fecha Ingreso', name: 'Afi_Fei', width: 120, align: "center"},
                        {label: 'Fecha Salida', name: 'Afi_Fef', width: 120, align: "center"},
                        {label: 'Acumula Fondos Reserva', name: 'Afi_Fnd', width: 100, align: "center"},
                        {label: 'Acumula D&eacute;cimo Tercero', name: 'Afi_Dte', width: 100, align: "center"},
                        {label: 'Acumula D&eacute;cimo Cuarto', name: 'Afi_Dcu', width: 100, align: "center"}
                    ]                   
                },true);
                //Inicio del di�logo para presentar el historial de contratos
                $('#contratosDialog').createDialog({icon:'glyphicon glyphicon-folder-open',height:400,width:700});
                //Inicio del di�logo para presentar el historial de afiliaciones
                $('#afiliacionDialog').createDialog({icon:'glyphicon glyphicon-folder-open',height:400,width:700});
            });
            function changeSueldo(val){
                var select=val!=='';
               
                $("#salarioBasico").prop('checked',false).prop('disabled',select);
                $('#Sue_Val').val('').prop('disabled',select);
            }
            /*INICIO DE FUNCIONES NECESARIAS PARA EL MANEJO DE DATOS*/
            //Funci�n para cargar los datos en el popup
            function cargarPop(cargo,perfil){
                $('#pop').flyout('hide');
                $('#pop').flyout({
                    title: 'Perfil del cargo: ' + cargo,
                    content: perfil,
                    html: true,
                    placement: 'right',
                    dismissible: true
                });
            }
            //Funci�n para cargar los antecedentes de contratos y afiliaciones
            function cargarDatos(empleado){
                $('#personalDialog').dialog('close');
                $('#Per_Cod').val(empleado['Per_Cod']);
                $('#empleado').val(empleado['empleado']);
                $('#list').jqGrid('setCaption','Listado de Contratos Efectuados');
                $('#list_afi').jqGrid('setCaption','Listado de Afiliaciones Efectuadas');
                $.post('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',{contratosEmpleado:true,Per_Cod:empleado['Per_Cod']},function(response){
                    $('#list').setRows(response);
                    if(response.length>0){$('#tit_con').html('Historico de Contratos: '+empleado.empleado);}else{$('#tit_con').html('No Registra Antecedentes de Contratos');}
                },'json').fail(function(){alert();});
                $.post('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',{afiliacionEmpleado:true,Per_Cod:empleado['Per_Cod']},function(response){
                    $('#list_afi').setRows(response);
                    if(response.length>0){$('#tit_afi').html('Historico de Afiliaciones: '+empleado.empleado);}else{$('#tit_afi').html('No Registra Antecedentes de Afiliaci&oacute;n');}
                },'json').fail(function(){alert();});
            }
            //Funci�n para registrar personal
            function saveForm() {
                if($('#empleado').val()===''||$('#Tic_Cod').val()===''||($('#Sut_Cod').val()===""&&!$('#salarioBasico').is(':checked')&&$('#Sue_Val').val()==='')||($('#salarioNeto').is(':checked')&&$('#Sue_Va1').val()==='')){
                    $.alert('Debe completar los campos obligatorios dentro de la pestaña Contrato..!!');
                    return;
                }
                var data=$('#formContrato').getData('saveContrato');
                $.extend(data,$('#formAfiliacion').getData());
                $.post('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',data,function(response){
                    if(response['success']===true){
                        $.alert("Transaccion Realizada con &Eacute;xito!");
                        limpiar();                        
                    }else{$.alert(response['message']);}
                },'json').fail(function(){alert();});
            }
            function limpiar() {
                $("#formAfiliacion")[0].reset();
                $("#formContrato")[0].reset();
                $('#Tic_Cod').val('').trigger('chosen:updated');
                $('#Con_Mot').summernote('reset');
                $('#Afi_Mot').summernote('reset');
                $('#list').jqGrid('clearGridData',true).trigger('reloadGrid');
                $('#list_afi').jqGrid('clearGridData',true).trigger('reloadGrid');
                $('#tabs').tabs({active:0});
                $('.fechaFin').prop('checked',true).trigger('change');
                $('#salarioBasico').prop('checked',false).trigger('change');
                $('#salarioNeto').prop('checked',false).trigger('change');
                $('#Sut_Cod').val("").trigger('change');
            }
		
	    function desactivar(){
                if($("#Afi_Con").val() == 'N'){
                    $('#Afi_Fnd').prop('disabled',true)
                    $('#Afi_Dte').prop('disabled',true)
                    $('#Afi_Dcu').prop('disabled',true)
                }
                else{
                    $('#Afi_Fnd').prop('disabled',false)
                    $('#Afi_Dte').prop('disabled',false)
                    $('#Afi_Dcu').prop('disabled',false)
                }
            }
              
        desactivar();

        $( "#Afi_Con" ).change(function(){
            desactivar();
        });

        function formaPago(){
            if($('#Pag_Con_For').val() == 'T'){
                $('#Bak_Cod').prop('disabled',false)
                $('#Pag_Con_Cue').prop('disabled',false)
                $('#Pag_Con_Tip').prop('disabled',false)  
            }
            else{
                $('#Bak_Cod').val('1');
                $('#Pag_Con_Cue').val('');
                $('#Pag_Con_Tip').val('N');
                $('#Bak_Cod').prop('disabled',true)
                $('#Pag_Con_Cue').prop('disabled',true)
                $('#Pag_Con_Tip').prop('disabled',true)  
            }
        }

        </script>
    </BODY>
</HTML>



