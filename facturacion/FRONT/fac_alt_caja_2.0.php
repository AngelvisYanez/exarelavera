<?php

/**
 * Permite modificar un Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 *
 *
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_caja_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* objeto para la conexion
*/
$obBD_conexion = new Class_Log_Conexion_Caj($Ses_Dat_Dis);

/**
* objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Caj;
$obBD_con1->debug(true);

/* GUARDA CAJA*/
if(isset($guardaCaja))
  {
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(1,$_POST,$obBD_conexion); 
    
    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)){ $responce['success'] = true; }
    else{ $responce['success'] = false; $responce['message'] = "No se ha logrado realizar la Transaccion"; }
    $obBD_con1->echoJson($responce);    
  }
  
/* Validar si existe un registro en la fecha y punto */
if(isset($searchValidacion))
{
    $cntCaj = $obBD_con1->getRowConsulta(3, $_GET, $obBD_conexion);    
    if ($cntCaj['contador'] > 0)
    {
        $response['message'] = "Ya se ha asignado en esta fecha";
        $response['success'] = false;
    }
    else
    {
        $response['success'] = true;// Si asigna
    }
    $obBD_con1->echoJson($response);
}

/*
 * Busqueda por filtros
 */
if(isset($searchFiltro))
{
    $data = $obBD_con1->getArrayConsulta(2, $_GET, $obBD_conexion); 
    // Grid necesita este array
    $obBD_con1->echoJson(array(
        'rows'=>$data,
        'total'=>1,
        'records'=>count($data),
        'success'=>true
    ));
}

/*
 * Cerrar Caja
 */
if(isset($closeCaja))
{
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(4,$_GET,$obBD_conexion); //update caja
    
    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
    { 
        $responce['success'] = true;
        $responce['fila'] = $obBD_con1->getRowConsulta(5, $Caj_Cod, $obBD_conexion);
        $responce['fila']['act1']=''; //add a fila
    }
    else
    { 
        $responce['success'] = false; 
        $responce['message'] = "No se ha logrado realizar la Transaccion";
    }
    $obBD_con1->echoJson($responce);
}

 $users = $obBD_con1->getArrayConsulta(0, $Ses_Suc_Cod, $obBD_conexion);// get users
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>       
        <script type="text/javascript" src="../VALIDACIONES/fac_val_caja_2.0.js?a=a1"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; CAJA</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                              <li><a href="#tabs-1">Crear Caja</a></li>
                              <li><a href="#tabs-2">Consulta de Caja</a></li>                               
                            </ul>
                            <div id="tabs-1" style="min-height: 450px;">
                                <form id="frm_alt_caja" name="frm_alt_caja" class="form-vertical" autocomplete="off">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Asignar Caja</legend>
                                            
                                        <!-- Usuario -->
                                        <div class="col-sm-3">
                                            <label class="col-sm-2 control-label col-sm">Usuario</label>
                                            <select id="cmbUser" name="Vnd_Cod" class="form-control col-sm">
                                                <?php 
                                                foreach ($users as $u) {
                                                    echo "<option value='{$u['Vnd_Cod']}' data--pun_-cod= '{$u['Pun_Cod']}'>{$u['Prs_Ape']} {$u['Prs_Nom']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Fecha de Apertura -->
                                        <div class="col-sm-2">
                                            <label class="control-label col-sm">Fecha de Apertura:</label>
                                            <input id="Caj_Fec" name="Caj_Fec" type="text" class="form-control input-sm" >
                                        </div>
                                        
                                            
                                        <!-- Monto -->
                                        <div class="col-sm-2">
                                            <div class="input-group">
                                                <label class="control-label col-sm">Monto</label>                                                
                                                <input type="text" id="Caj_Exi" name="Caj_Exi" class="form-control col-sm" placeholder="Ejem: 100" value="0"  onkeypress="return validar_decimal(event)">
                                            </div>                                                
                                        </div>

                                        <!-- Observacion -->
                                        <div class="col-sm-3">
                                            <div class="input-group">
                                                <label class="control-label col-sm">Observacion</label>
                                                <input type="text" id="Caj_Obs" name="Caj_Obs" class="form-control col-sm" placeholder="Observacion..">
                                            </div>                                                
                                        </div>

                                        <!-- Button -->
                                        <div class="col-sm-1">
                                          <label class="control-label" for="">Acci�n</label>                                              
                                          <button id="btnGuardar" name="btnGuardar" class="btn btn-primary">Guardar</button>                                              
                                        </div>
                                        
                                        <div class="col-sm-3"></div>
                                       
                                    </fieldset>                                    
                                </form>
                            </div>
                            
                            <div id="tabs-2">
                                
                                <form id="frm_bus" name="frm_bus" class="form-horizontal normal">
                                    
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">B&uacute;squeda de Caja</legend>
                                        <div class="form-group">                                            
                                            
                                            <div class="col-sm-4 col-sm-offset-1">
                                                <label class="control-label label-xs">Usuario:</label>
                                                <select id="cmbUserB" name="cmbUserB" class="form-control col-sm">
                                                <?php 
                                                foreach ($users as $u) {
                                                    echo "<option value='{$u['Vnd_Cod']}' data--pun_-cod= '{$u['Pun_Cod']}'>{$u['Prs_Ape']} {$u['Prs_Nom']} - {$u['Pun_Des']}</option>";
                                                }
                                                ?>
                                                </select>
                                            </div>                                            
                                            
                                            <div class="col-sm-2">
                                                <label class="control-label label-xs">Desde:</label>
                                                <input id="Caj_Fec_d" name="Caj_Fec_d" type="text" class="form-control input-sm" >
                                            </div>                                            
                                            
                                            <div class="col-sm-2">
                                                <label class="control-label label-xs">Hasta:</label>
                                                <input id="Caj_Fec_h" name="Caj_Fec_h" type="text" class="form-control input-sm" >
                                            </div>
                                            
                                                <!-- Button -->
                                            <div class="col-sm-1">
                                              <label class="control-label" for="">Acci�n</label>                                              
                                              <button id="btnBuscar" name="btnBuscar" class="btn btn-success">Buscar</button>                                              
                                            </div>
                                            
                                        </div>
                                        
                                    </fieldset>
                                </form> 
                                <div >
                                    <TABLE id="tableResult">
                                        
                                    </TABLE>
                                    <div id="tableResultPager"> 
                                        
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                                  
                    </div>
                </div>                
            </div>
        </div>     
    </BODY>
</HTML>