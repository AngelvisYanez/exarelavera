<?php
/**
 * Descripcion:          Modulo de Deuda Inicial
 * Fecha de creacion:    Septiembre 21, 2017
 * Desarrollador:	Asael Tello
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_deuda_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Deu($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Deu;
$hoy = date("Y-m-d");

    if(isset($clienteAjax)) 
    {
        $contar = $obBD_con1->getRowConsulta(2, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*', $obBD_conexion);
        $pagination = pages($contar['total'], $page, $rows);
        $responce = $pagination['data'];
        if ($contar['total'] > 0)
            $responce['rows'] = $obBD_con1->getArrayConsulta(2, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion);
        utf8_encode_deep($responce['rows']);
        echo json_encode($responce);
        exit();
    }

    /*
     * Save Caja
     */
    if (isset($save)) {
        $data = $_POST;        
        $obBD_con1->inicio_transaccion($obBD_conexion);

        $aut        = $obBD_con1->getArrayConsulta(6,   $Ses_Suc_Cod, $obBD_conexion);        
		if(!isset($Caj_Cod)||empty($Caj_Cod)){
			//save caja_aper
			$obBD_con1->operacionobBD(8, array("Pun_Cod" => $aut[0]['Pun_Cod'], "Caj_Fec" => $data['Caj_Fec']), $obBD_conexion);
			//get Caj_Cod
			$Caj_Cod = $obBD_con1->insercionid($obBD_conexion);
			//save venta
		}
        $obBD_con1->operacionobBD(1, array("Aut_Cod" => $aut[0]['Aut_Cod'], "Cli_Cod" => $data['Cli_Cod'], "Ciu_Cod" => $data['Ciu_Cod'], "Caj_Cod" => $Caj_Cod, "Vnd_Cod" => $aut[0]['Vnd_Cod'], "Vet_Obs" => $data['Vet_Obs']), $obBD_conexion);
        // get Vet_Cod
        $Vet_Cod = $obBD_con1->insercionid($obBD_conexion);
        //save comprobante
        $obBD_con1->operacionobBD(3, array("Pec_Cod" => $data['Pec_Cod'], "Cli_Cod" => $data['Cli_Cod'], "Usu_Cod" => $Ses_Usu_Cod, "Com_Fec" => $data['Caj_Fec'], "Com_Val" => $data['Caj_Exi'], "Tia_Cod" => $data['Tia_Cod'] ), $obBD_conexion);
        //get Com_Cod
        $Com_Cod = $obBD_con1->insercionid($obBD_conexion);
        // save detalle-ventas-comprobante
        $obBD_con1->operacionobBD(5, array("Vet_Cod" => $Vet_Cod, "Com_Cod" => $Com_Cod), $obBD_conexion);
        // save asientos
        $obBD_con1->operacionobBD(4, array("Com_Cod" => $Com_Cod, "Asi_Deh" => 'D', "Asi_Val" => $data['Caj_Exi'], "Pld_Cod" => $data['Pld_Cod']), $obBD_conexion);//debe
        //save ccpp_cobrar
        $obBD_con1->operacionobBD(10, array("Vet_Cod" => $Vet_Cod, "Com_Cod" => $Com_Cod, "Cpc_Ven" => $data['Caj_Fec']), $obBD_conexion);
        
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $responce['success'] = true;
        } else {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transaccion";
        }

        $obBD_con1->echoJson($responce);
    }

    /*
     * Modify Caja
     */
    if (isset($modify)) {
        $data = $_POST;
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        //update caja_aper
        $obBD_con1->operacionobBD(20, array("Caj_Cod" => $data['row']['Caj_Cod'], "Caj_Fec" => $data['data']['Caj_Fec']), $obBD_conexion);
        //update comprobante
        $obBD_con1->operacionobBD(17, array("Cli_Cod" => $data['data']['Cli_Cod'], "Com_Fec" => $data['data']['Caj_Fec'], "Com_Val" => $data['data']['Caj_Exi'], "Com_Cod"=> $data['row']['Com_Cod'], "Tia_Cod" => $data['data']['Tia_Cod']), $obBD_conexion);
        // update venta
        $obBD_con1->operacionobBD(18, array("Cli_Cod" => $data['data']['Cli_Cod'], "Vet_Cod"=> $data['row']['Vet_Cod'], "Vet_Obs" => $data['data']['Vet_Obs']), $obBD_conexion);
        // update asiento
        $obBD_con1->operacionobBD(19, array("Asi_Val" => $data['data']['Caj_Exi'], "Pld_Cod"=> $data['data']['Pld_Cod'], "Com_Cod"=> $data['row']['Com_Cod']), $obBD_conexion);
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) 
        {
            $response['success'] = true;
            $response['fila']['act3'] = ''; //add a fila
        } 
        else 
        {
            $response['success'] = false;
            $response['message'] = "No se ha logrado realizar la Transaccion";
        }
        $obBD_con1->echoJson($response);
    }

    /*
     * Get data by Sucursal
     */
    if (isset($searchAll)) {
        $data = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows' => $data,
            'total' => 1,
            'records' => count($data),
            'success' => true
        ));
    }
    
    /*
     * Valida Caja by Punto y Fecha
     */
    if(isset($validaCaja))
    {
        $aut        = $obBD_con1->getArrayConsulta(6,$Ses_Suc_Cod, $obBD_conexion);        
        $data = $obBD_con1->getArrayConsulta(7, array("fecha" => $_POST[fecha], "Pun_Cod" => $aut[0][Pun_Cod]), $obBD_conexion);
        if ($data[0]['contador'] === "0")
        {
            $data['success'] = true;            
        }
        else
        {
            $data['success'] = false;
			$data['Caj_Cod'] = $data[0]['Caj_Cod'];
            $data['message'] = "Ya existe caja con esta fecha y punto";
        }
        $obBD_con1->echoJson($data);
    }
    
    /*
     * Delete Deuda Inicial
     */
    if(isset($eliminar))
    {   
        $d = $_POST;
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        //elimina detalle ventas-comprobante
        $obBD_con1->operacionobBD(13, array("Vet_Cod" => $d['data']['Vet_Cod'], "Com_Cod" => $d['data']['Com_Cod']), $obBD_conexion);
        // elimina venta
        $obBD_con1->operacionobBD(15, $d['data']['Vet_Cod'], $obBD_conexion);
        // elimina caja_aper
        $obBD_con1->operacionobBD(16, $d['data']['Caj_Cod'], $obBD_conexion);
        // elimina comprobante
        $obBD_con1->operacionobBD(14, $d['data']['Com_Cod'], $obBD_conexion);
        
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) 
        {
            $response['success'] = true;
            $response['fila']['act2'] = ''; //add a fila
        } 
        else 
        {
            $response['success'] = false;
            $response['message'] = "No se ha logrado realizar la Transaccion";
        }
        
        $obBD_con1->echoJson($response);
    }
    
    $ciudad     = $obBD_con1->getSucursal($Ses_Suc_Cod, $obBD_conexion);
    $periodo    = $obBD_con1->getPerioCont($Ses_Emp_Cod, $hoy, $obBD_conexion);    
	$perios     = $obBD_con1->getArrayConsulta(22, '', $obBD_conexion);    
    $pld        = $obBD_con1->getArrayConsulta(9,$Ses_Emp_Cod, $obBD_conexion);
    $tipoComprobante = $obBD_con1->getArrayConsulta(21,'', $obBD_conexion);
	//$obBD_con1->echoLog($perios);
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Ccxcc Deudas Iniciales [EXA]"; ?></TITLE>
	    <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>       
        <script language="javascript" src="../VALIDACIONES/tes_val_deuda_1.0.js?X=2"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Deuda Inicial</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                                <li><a href="#tabs-1">Deudas Iniciales</a></li>
                            </ul>
                            <div id="tabs-1" style="min-height: 450px;">

                                <div>
                                    <table id="tableResult">                                        
                                    </table>
                                    <div id="tableResultPager">                                         
                                    </div>
                                    <BR>
                                    <div class="col-sm-2">
                                        <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i>   Agregar</button>
                                    </div>
                                </div>
                            </div>                          
                        </div>

                    </div>
                </div>                
            </div>
        </div>

        <!-- MODAL DEUDA-->
        <div id="modalDeuda" title="">
            <form id ="frmModDeuda" name="frmModDeuda" class="form-horizontal" autocomplete="off">
                <fieldset>
                    <div class="form-group Titulos2">
                        <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                    </div>

                    <!-- Cod Autorizacion-->
                    <div>
                        <input type="text" id="Aut_Cod" name="Aut_Cod" hidden="true">
                    </div>

                    <!-- PERIODO -->
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs" for="Pec_Cod">Periodo:</label>
                        <div class="col-xs-8">
                            <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" hidden="true" required="">
                                <?php  
									foreach ($perios as $p)
                                    {
                                        echo "<option value='$p[Pec_Cod]'>$p[Periodo]</option> ";
                                    }
                                    //echo "<option value='$periodo[Pec_Cod]'>$periodo[Periodo]</option> ";
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- CUENTA -->
                    <div class="form-group">
                        <label class="col-md-4 control-label label-xs required" for="cuenta">Cuenta:</label>  
                        <div class="col-md-6">
                            <select id="Pld_Cod" name="Pld_Cod" class="form-control input-xs">
                                <option value="0">Seleccionar ...</option>
                                <?php 
                                    foreach ($pld as $d)
                                    {
                                        echo "<option value='$d[Pld_Cod]'>$d[cuenta]</option>";
                                    }
                                ?>
                            </select>                            
                        </div>
                    </div>

                    <!-- FECHA-->
                    <div class="form-group">
                        <label class="col-md-4 control-label label-xs required" for="Caj_Fec">Fecha:</label>  
                        <div class="col-md-6">
                            <input id="Caj_Fec" name="Caj_Fec" type="text" placeholder="" class="form-control input-xs" value="<?PHP echo $hoy; ?>" />
                        </div>
                    </div>
                    
                    <!-- TIPO DE COMPROBANTE -->
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required" for="Ciu_Cod">Tipo de Comprobante:</label>
                        <div class="col-xs-8">
                            <select id="Tia_Cod" name="Tia_Cod" class="form-control input-xs">                                
                                <?php 
                                    foreach ($tipoComprobante as $t)
                                    {
                                        if ($t['Tia_Des'] === 'INGRESO')
                                        {
                                            echo "<option value='$t[Tia_Cod]' selected>$t[Tia_Des]</option>";
                                        }                                        
                                        else
                                        {
                                            echo "<option value='$t[Tia_Cod]'>$t[Tia_Des]</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- CIUDAD -->
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required" for="Ciu_Cod">Ciudad:</label>
                        <div class="col-xs-8">
                            <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs">                                
                                <?php                                
                                echo "<option value='$ciudad[Ciu_Cod]'>$ciudad[Ciu_Des]</option> ";
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- CLIENTE-->
                    <div class="form-group" id="btnBusca">
                        <label class="col-xs-4 control-label label-sm required" for="cod_cuenta">Emitido a:</label>  
                        <div class="col-xs-8">                                    
                            <div class="input-group input-group-sm">                                                
                                <input type="hidden" name="Cli_Cod" id="Cli_Cod" value="" />  
                                <input id="cliente" name="cliente" type="text" class="form-control" placeholder="Seleccione el cliente" required readonly />
                                <span class="input-group-btn" >
                                    <button class="btn btn-success" onclick="$('#clienteDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Cliente"></span></button>
                                </span>
                            </div>
                        </div>                                  
                    </div>

                    <!-- VALOR -->
                    <div class="form-group">
                        <label class="col-md-4 control-label label-xs required" for="Caj_Exi">Valor:</label>  
                        <div class="col-md-6">
                            <input id="Caj_Exi" name="Caj_Exi" type="text" placeholder="" class="form-control input-xs"  onkeypress="return validar_decimal(event)" required="">
                        </div>
                    </div>

                    <!-- OBSERVACION -->
                    <div class="form-group">
                        <label class="col-md-4 control-label label-xs" for="Vet_Obs">Observación:</label>
                        <div class="col-md-8">
                            <textarea id="Vet_Obs" name="Vet_Obs" rows="3" placeholder="Ingrese observaciones..." class="form-control input-xs"></textarea>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="form-group">
                        <label class="col-md-4 control-label" for="btnModificar"></label>
                        <div class="col-md-8">
                            <button id="btnAccion" type="button" name="btnAccion" class="btn btn-sm btn-primary"></button>
                        </div>
                    </div>

                </fieldset>
            </form>
        </div>       

        <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
        <div id="clienteDialog" title="B&uacute;squeda de Proveedores">
            <form class="form-horizontal normal">                   
            </form>
        </div>
        <!-- FIN DEL DIALOGO PROVEEDOR-->

    </BODY>
</HTML>