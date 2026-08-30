<?php
/**
 * @abstract Permite revisar los numeros no ocupados en una autorizacion
 * @author Alejandro Camacho
 * @version 1.0
 * Fecha de creaci�n  2021-06-25
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_valautorizacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


$fecha_actual = date("Y-m-d");
$fechaAnterior = date("Y-m-d",strtotime($fecha_actual."- 2 month"));

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;

$sucursales = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);

if(isset($datos)){
    $resp['autorizaciones']=$obBD_con1->getArrayConsulta(1,$sucursal,$obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}

if(isset($autorizaci)){
    $datos = array('Sucursal' => $sucursal, 'Tipo' => $tipo);
    $resp['autorizaciones']=$obBD_con1->getArrayConsulta(3,$datos,$obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}

if(isset($puntoEmision)){
    $datos = array('Sucursal' => $sucursal, 'Tipo' => $tipo, 'Autorizacion'=>$autSri);
    $resp['puntosSRI']=$obBD_con1->getArrayConsulta(8,$datos,$obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}

if(isset($buscar)){
    $datos = array('Sucursal' => $sucursal, 'Tipo' => $tipo, 'Aut_Sri' => $autSri, 'Pun_Sri' => $punSri);
    $resp['autorizaciones']=$obBD_con1->getArrayConsulta(4,$datos,$obBD_conexion);
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}

if(isset($consultarVetNums)){
    $filtros = array('Codigos'=>$autorizaciones, 'Fecha'=>$fechaAnterior);
    if($tipo == "F"){
         $resp['numeros']=$obBD_con1->getArrayConsulta(5,$filtros,$obBD_conexion);
    }
    if($tipo == "R"){
         $resp['numeros']=$obBD_con1->getArrayConsulta(6,$filtros,$obBD_conexion);
    }
    if($tipo == "G"){
         $resp['numeros']=$obBD_con1->getArrayConsulta(7,$filtros,$obBD_conexion);
    }

    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/con_val_valautorizacion.js"></script>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar N�meros Faltantes por Autorizaci�n </h3><p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;"></p></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="factura">
                    <div class="row">
                        <div class="col-xs-9" id="panelAnulVentas" >
                            <form class='form-horizontal normal' id='form_anular'>

                            	 <div class='row form-group col-xs-12'>
                                    <label class='col-xs-5 control-label label-xs required'>Sucursal :</label>
                                    <div class="col-xs-5">
                                        <select name='Suc_Cod' id='Suc_Cod' class='form-control input-xs'>
                                        	<option>Seleccione...</option>
                                        	<?php 
                                        		foreach ($sucursales as $sucursal) 
                                        		{
                                        			echo "<option value='" . $sucursal['Suc_Cod'] . "'>" . $sucursal['Suc_Des'] . "</option>";
                                        		}
                                        	 ?>
                                        </select>
                                    </div>
                                </div>

                                <div class='row form-group col-xs-12'>
                                    <label class='col-xs-5 control-label label-xs required'>Documento :</label>
                                    <div class="col-xs-5">
                                        <select name='Tic_Cod' id='Tic_Cod' class='form-control input-xs'>
                                        	<option>Seleccione...</option>
                                        </select>
                                    </div>
                                </div>

                                <div class='row form-group col-xs-12'>
                                    <label class='col-xs-5 control-label label-xs required'>Autorizaci�n :</label>
                                    <div class="col-xs-5 ">
                                            <select name='Aut_Cod' id='Aut_Cod' class='form-control input-xs'>
                                                <option>Seleccione...</option>
                                            </select>
                                    </div>  
                                </div>

                                <div class='row form-group col-xs-12'>
                                    <label class='col-xs-5 control-label label-xs required'>Punto de Emisi�n :</label>
                                    <div class="col-xs-5 ">
                                            <select name='Pun_Sri' id='Pun_Sri' class='form-control input-xs'>
                                                <option>Seleccione...</option>
                                            </select>
                                    </div>  
                                </div>

                                <div class="row form-group col-xs-12 margin-top">
									<label  class='col-xs-5 control-label label-xs' for="valNumeros"></label>
									<div class="col-xs-5 ">
									    <textarea class="form-control" id="valNumeros" rows="10" placeholder="Numeros no ocupados en la autorizacion" readonly="true"></textarea>
									</div>
								 </div>

                                 <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b>La b�squeda de documentos empezar� desde el <b><?php echo $fechaAnterior?></b></div>
                                
                            </form>
                        </div>
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
