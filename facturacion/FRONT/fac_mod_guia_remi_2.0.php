<?php
/**
 * @abstract Permite realizar guias de remision
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2015-07-22
 */
/**
 * Actualizado por Wilson Belduma
 * Fecha de actualizacion: 2024-05-17
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_guia_remi.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

if (isset($doc_xml)) {
    header('Location: ' . "../FRONT/$Ses_Emp_Cod/{$doc_xml}_A.xml");
}
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_G_Remi($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_G_Remi;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($searchDocument)) {
    $obBD_con1->getPageGridJson(16, $_GET, $obBD_conexion);
}
if (isset($docDetalle)) {
    $rows = $obBD_con1->getArrayConsulta(18, $Gui_Cod, $obBD_conexion);
    foreach ($rows as &$r) {
        $r['items'] = $obBD_con1->getArrayConsulta(19, $Gui_Cod . '*' . $r['Gui_Int'], $obBD_conexion);
    }
    unset($r);
    $obBD_con1->echoJson(array('success' => true, 'rows' => $rows));
}

if (isset($Doc_Cod)) {
    require_once('../LOGICA/fac_log_electronica.php');

    $obBD_elect =  new Class_Log_Datos_Guia_Elect;
    $obBD_elect->createPdf($Doc_Cod, $obBD_conexion);
}
/* busqueda de transportistas / destinatarios */
if (isset($transAjax) || isset($destiAjax)) {
    $obBD_con1->getPageGridJson(3, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . (isset($transAjax) ? 'T' : 'D'), $obBD_conexion, $page, $rows);
}
/* Consulta del tipo de productos */
if (isset($proAjax)) {
    $obBD_con1->getPageGridJson(4, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $Ses_Suc_Cod, $obBD_conexion, $page, $rows);
}
/* Consulta defacturas de venta */
if (isset($vetAjax)) {
    $obBD_con1->getPageGridJson(7, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
/* ver si exite un transportista/destinatario */
if (isset($provAjax2)) {
    $pers = $obBD_con1->getArrayConsulta(14, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $Gpe_Tip, $obBD_conexion);
    $responce = array('rows' => null, 'total' => 0);
    if (count($pers) >= 1) {

        $per = array(0 => $pers[0]);
        foreach ($pers as $p) {
            if ($p['Emp_Cod'] * 1 == $Ses_Emp_Cod * 1) {
                $per[0] = $p;
                break;
            }
        }
        $responce['rows'] = $per;
        $responce['total'] = count($per);
    }
    $obBD_con1->echoJson($responce);
}
/* Guarda un nuevo proveedor */
if (isset($guardaProvAjax)) {
    $data = $_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (empty($Prs_Cod)) {
        $obBD_con1->operacionobBD(15, $data, $obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    }
    $obBD_con1->operacionobBD(8, $data, $obBD_conexion);
    $data['Gpe_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    $data[$Gpe_Tip == 'T' ? 'transportista' : 'destinatario'] = (empty($data['Gpe_Ras']) ? trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']) : $data['Gpe_Ras']);

    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
        $responce = array('success' => true, 'gpe' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}

//Editar un proveedor 
if (isset($editarProvAjax)) {
    $data = $_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (!empty($Prs_Cod)) { //regresa true   caso contrariio false
        //editar persona
        $obBD_con1->operacionobBD(23, $data, $obBD_conexion);
        $data['Prs_Cod'] = $Prs_Cod;
    }
    $obBD_con1->operacionobBD(24, $data, $obBD_conexion);
    $data[$Gpe_Tip == 'T' ? 'transportista' : 'destinatario'] = (empty($data['Gpe_Ras']) ? trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']) : $data['Gpe_Ras']);
    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
        $responce = array('success' => true, 'gpe' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}
//finalizar editar proveedor

// =====================================================
// AJAX: Guardar o modificar derecho minero
// =====================================================
if (isset($guardarDerechoMineroAjax)) {
    $resp = array('success' => false);
    $accion = isset($_POST['accion']) ? $_POST['accion'] : 'registrar';
    
    try {
        $obBD_con1->inicio_transaccion($obBD_conexion);
        
        if ($accion == 'modificar' && !empty($_POST['Der_Min_Id'])) {
            // Actualizar derecho minero existente - case 1279
            $params = array(
                $_POST['Der_Min_Codigo'],
                $_POST['Der_Min_Nombre'],
                $_POST['Der_Min_Titular_Operador'],
                $_POST['Der_Min_Tipo'],
                isset($_POST['Der_Min_Ubicacion']) ? $_POST['Der_Min_Ubicacion'] : '',
                isset($_POST['Der_Min_Observaciones']) ? $_POST['Der_Min_Observaciones'] : '',
                date('Y-m-d H:i:s'), // Fecha modificación
                $_POST['Der_Min_Id'], // WHERE Der_Min_Id
                isset($_POST['Der_Min_Recurso']) ? $_POST['Der_Min_Recurso'] : ''
            );
            $obBD_con1->operacionobBD(1279, $params, $obBD_conexion);
            $Der_Min_Id = $_POST['Der_Min_Id'];
            $resp['message'] = 'Derecho minero actualizado exitosamente';
        } else {
            // Insertar nuevo derecho minero - case 1278
            $params = array(
                $_POST['Der_Min_Codigo'],
                $_POST['Der_Min_Nombre'],
                $_POST['Der_Min_Titular_Operador'],
                $_POST['Der_Min_Tipo'],
                isset($_POST['Der_Min_Ubicacion']) ? $_POST['Der_Min_Ubicacion'] : '',
                isset($_POST['Der_Min_Observaciones']) ? $_POST['Der_Min_Observaciones'] : '',
                'A', // Estado
                date('Y-m-d H:i:s'), // Fecha registro
                isset($_POST['Der_Min_Recurso']) ? $_POST['Der_Min_Recurso'] : ''
            );
            $obBD_con1->operacionobBD(1278, $params, $obBD_conexion);
            $Der_Min_Id = $obBD_con1->insercionid($obBD_conexion);
            $resp['message'] = 'Derecho minero registrado exitosamente';
        }
        
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $resp['success'] = true;
            $resp['Der_Min_Id'] = $Der_Min_Id;
        } else {
            $resp['success'] = false;
            $resp['message'] = 'Error al guardar el derecho minero';
            $resp['error'] = $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}

// =====================================================
// AJAX: Cargar bodegas usadas anteriormente por el destinatario
// =====================================================
if (isset($cargarBodegasDestinatarioAjax)) {
    $Gpe_Cod = isset($_POST['Gpe_Cod']) ? $_POST['Gpe_Cod'] : '';
    $bodegas = array();
    
    if (!empty($Gpe_Cod)) {
        try {
            // Usamos la misma conexión y lógica del archivo principal
            $result = $obBD_con1->operacionobBD(28, array($Gpe_Cod), $obBD_conexion);
            
            if ($result instanceof mysqli_result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $nombre = trim($row['Bod_Nom']);
                    if (!empty($nombre)) {
                        $bodegas[] = $nombre;
                    }
                }
            }
        } catch (Exception $e) {
            // Silenciosamente capturamos errores
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(array_values(array_unique($bodegas)));
    exit();
}

// =====================================================
// AJAX: Obtener lista de derechos mineros
// =====================================================
if (isset($obtenerDerechosMineros)) {
    error_log("DEBUG: obtenerDerechosMineros AJAX called");
    $resp = array('success' => false, 'debug' => array());
    
    try {
        $resp['debug'][] = "Base de datos: " . (isset($Ses_Dat_Dis) ? $Ses_Dat_Dis : 'NO DEFINIDA');
        $resp['debug'][] = "Intentando ejecutar operacionobBD(1277)";
        
        $result = $obBD_con1->operacionobBD(1277, array(), $obBD_conexion);
        $derechos = array();
        
        $resp['debug'][] = "Resultado de operacionobBD: " . ($result ? "SI" : "NO");
        
        if ($result instanceof mysqli_result) {
            $count = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $derechos[] = $row;
                $count++;
            }
            $resp['debug'][] = "Registros encontrados: " . $count;
            $resp['success'] = true;
            $resp['derechos'] = $derechos;
            $resp['message'] = "Se cargaron $count derechos mineros";
        } else {
            $resp['success'] = false;
            $resp['message'] = 'operacionobBD retornó false';
            $resp['derechos'] = array();
            $resp['debug'][] = "Error en operacionobBD";
        }
    } catch (Exception $e) {
        $resp['success'] = false;
        $resp['message'] = 'Error: ' . $e->getMessage();
        $resp['derechos'] = array();
        $resp['debug'][] = "Exception: " . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}

if (isset($validaVetNum)) {
    $Gui_Cod = isset($Gui_Cod) ? $Gui_Cod : '';
    $electronica = ($Aut_Tem == 'E');
    $row_max_codig = $obBD_con1->getRowConsulta(5, $_GET, $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion    
    $Ret_Id_Man = ($row_max_codig['next']);

    $resp = array_merge(array('success' => true, 'Vet_Num' => $Ret_Id_Man, 'Vet_Num_Old' => $Vet_Num, 'Vet_Cod' => $Gui_Cod, 'Aut_Ini' => $Aut_Ini, 'Aut_Fin' => $Aut_Fin));
    if (!empty($Vet_Num)) {
        $num_existe_gencod = $obBD_con1->getRowConsulta(6, $Suc_Sri . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . $Gui_Cod . '*' . $Pun_Sri, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
        if ($num_existe_gencod['total'] * 1 > 0) {
            $resp['success'] = false;
            $resp['message'] = "El documento número $Vet_Num ya Existe en el Sistema!";
        }
    } else $resp['success'] = false;

    $obBD_con1->echoJson($resp);
}
$error = 0;
$vend = $obBD_con1->getRowConsulta(1, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
if (empty($vend['Vnd_Cod'])) $error++;
else {
    $autori = $obBD_con1->getRowConsulta(2, $vend['Vnd_Cod'], $obBD_conexion);
    if (empty($autori['Aut_Cod'])) $error++;
}

if (isset($saveDocument)) {
    $resp = array('success' => false);
    /* Que sea vendedor */
    if (empty($vend['Vnd_Cod'])) {
        $resp['message'] = "No tiene permisos de Vendedor!";
    }
    $Vnd_Cod = $vend['Vnd_Cod'];
    /* valida que no exista el documento */
    $num_existe_gencod = $obBD_con1->getRowConsulta(6, $Suc_Sri . '*' . $Aut_Sri . '*' . $Gui_Num . '*' . $Gui_Cod . '*' . $Pun_Sri, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
    if ($num_existe_gencod['total'] * 1 > 0) {
        $resp['message'] = "La guia de remision No. $Gui_Num ya existe!";
    }

    if (!empty($resp['message'])) {
        echo json_encode($resp);
        exit();
    }

    $obBD_ins1 =  new Class_Log_Datos_G_Remi;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_G_Remi($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {

        if ($Aut_Tem == 'E') { // documento electronico
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect =  new Class_Log_Datos_Guia_Elect;
            $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Gui_Fec, $Gui_Num, $obBD_conexion);
            $_POST['Gui_Xml'] = $claveAcceso;
        }

        $obBD_ins1->operacionobBD(22, $Gui_Cod, $obBD_conexionIns);
        /* Cabecera de la guia */
        $obBD_ins1->operacionobBD(21, $_POST, $obBD_conexionIns);
        $Bod_Nom_Str = '';
        // if (isset($_POST['Bodega_Nom']) && is_array($_POST['Bodega_Nom'])) {
        //     $Bod_Nom_Str = implode(' - ', $_POST['Bodega_Nom']);
        // }
        foreach ($destinos as $i => $dest) {
            $g = array('Gui_Cod' => $Gui_Cod, 'Gui_Int' => ($i + 1));
            /* guarda destinatario */
            $obBD_ins1->operacionobBD(11, array_merge($dest, $g), $obBD_conexionIns);
            foreach ($dest['items'] as $j => $item) {
                $item['Gde_Int'] = ($j + 1);
                /* guarda detalle */
                $obBD_ins1->operacionobBD(12, array_merge($item, $g), $obBD_conexionIns);
            }
        }
    } catch (Exception $ex) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $ex->getMessage();
        echo json_encode($resp);
        exit();
    }
    if ($obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns)) {
        $resp = array('success' => true, 'Gui_Cod' => $Gui_Cod);
        if ($Aut_Tem == 'E') { // documento electronico
            $resp['xml'] = $obBD_elect->createXmlGuiaRemision($Gui_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
            $resp['pdf'] = '../COMPONENTES/tesPdfElectronicos.php?clave=' . $claveAcceso;
        } else {
            $resp['imprimir'] = $obBD_con1->reportesExa($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        }
    } else {
        $resp = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_ins1->MsgError);
    }
    $obBD_con1->echoJson($resp);
}

$rs_periodo = $obBD_con1->getArrayConsulta(17, $Ses_Emp_Cod, $obBD_conexion);
$rs_sucursal = $obBD_con1->getArrayConsulta(29, $Ses_Emp_Cod, $obBD_conexion);
$imprimir = $obBD_con1->reportesExa($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
?>

<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Guias Modificar [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var modificar = true,
            Aut_Tem = 'N';
    </script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_guia_remi_2.0.js?x=2"></script>
    <!-- Librerías para el Select Dinámico (Select2) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        @media (max-width: 768px) {
            .exa-fieldset { padding: 10px 5px !important; margin-bottom: 5px !important; }
            .control-label { text-align: left !important; margin-bottom: 2px !important; padding-top: 5px !important; }
            .form-group { margin-bottom: 5px !important; }
            .col-xs-1, .col-xs-2, .col-xs-3, .col-xs-4, .col-xs-5, .col-xs-6, .col-xs-7, .col-xs-8, .col-xs-9, .col-xs-10, .col-xs-11, .col-xs-12 {
                width: 100% !important;
                margin-bottom: 5px;
            }
            
            /* Permitir Periodo y Mes en la misma fila */
            .search_pec.getData { width: 100% !important; }
            .search_pec.getData.ins, #Cmb_Mes { display: inline-block !important; }
            
            /* Diseño de cuadrícula 2x2 para Periodo y Mes */
            .form-group:has(select[name="Pec_Cod"]) {
                display: flex !important;
                flex-wrap: wrap !important;
                margin-bottom: 10px !important;
            }
            .form-group:has(select[name="Pec_Cod"]) > label,
            .form-group:has(select[name="Pec_Cod"]) > div {
                width: 50% !important;
                margin: 0 !important;
                padding: 0 4px !important;
                float: none !important;
            }
            /* Ubicar etiquetas en la primera fila */
            .form-group:has(select[name="Pec_Cod"]) > label:nth-of-type(1) { order: 1 !important; }
            .form-group:has(select[name="Pec_Cod"]) > label:nth-of-type(2) { order: 2 !important; }
            /* Ubicar selectores en la segunda fila */
            .form-group:has(select[name="Pec_Cod"]) > div:nth-of-type(1) { order: 3 !important; }
            .form-group:has(select[name="Pec_Cod"]) > div:nth-of-type(2) { order: 4 !important; }
            
            .form-group:has(select[name="Pec_Cod"]) label { 
                margin-bottom: 2px !important; 
                height: auto !important; 
                font-weight: bold;
            }

            .input-group { width: 100% !important; }
            
            /* Ajuste para el set de botones (Filtrar Por) */
            .radioset {
                display: flex !important;
                flex-wrap: nowrap !important;
                gap: 2px !important;
                width: 100% !important;
                margin-bottom: 5px !important;
            }
            .radioset label {
                flex: 1 !important;
                text-align: center !important;
                padding: 8px 2px !important;
                font-size: 9.5px !important;
                min-width: 0 !important;
                white-space: nowrap !important;
                letter-spacing: -0.3px;
            }

            /* Grillas responsivas */
            .ui-jqgrid, .ui-jqgrid-view, .ui-jqgrid-hdiv, .ui-jqgrid-bdiv, .ui-jqgrid-pager {
                width: 100% !important;
            }
            .ui-jqgrid-bdiv { overflow-x: auto !important; -webkit-overflow-scrolling: touch; }
            .ui-jqgrid-htable, .ui-jqgrid-btable { min-width: 900px !important; } /* Forzar scroll horizontal */
            .ui-jqgrid tr.ui-row-ltr td { padding: 8px 4px !important; font-size: 11px !important; }
            
            /* Pager de jqGrid responsivo (Compacto y completo) */
            .ui-jqgrid-pager { height: auto !important; padding: 8px 0 !important; background: #f9f9f9 !important; }
            .ui-pg-table { width: auto !important; margin: 0 auto !important; border-spacing: 0 !important; }
            .ui-pg-button { padding: 4px 2px !important; width: auto !important; }
            
            /* Forzar visualización de botones individuales y ocultar agrupamiento de jqGrid */
            .ui-pg-button-responsive { display: none !important; }
            .ui-pg-table td { display: table-cell !important; vertical-align: middle !important; }

            /* Ocultar sección derecha para evitar desbordamiento */
            #searchGridPager_right { display: none !important; } 
            #searchGridPager_left, #searchGridPager_center { width: auto !important; float: none !important; display: inline-block !important; vertical-align: middle !important; }
            #searchGridPager .ui-pg-table { display: inline-table !important; }

            .ui-pg-div { 
                display: inline-flex !important; 
                font-size: 10px !important; 
                align-items: center; 
                justify-content: center;
            }
            
            .ui-pg-input, .ui-pg-selbox { height: 22px !important; font-size: 11px !important; padding: 2px !important; margin: 0 2px !important; }

            /* Botones generales en móvil (Excluyendo los de la grilla para que no se estiren) */
            .btn:not(.ui-jqgrid .btn, .ui-pg-button .btn, .ui-pg-div .btn, .ui-pg-button) { width: 100%; margin-bottom: 5px; padding: 10px; }
            
            /* Asegurar que los botones de acción dentro de la grilla se mantengan compactos */
            .ui-jqgrid .btn { 
                width: auto !important; 
                padding: 4px 8px !important; 
                margin: 0 !important;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
            }
            .ui-jqgrid .ui-pg-button .btn, 
            .ui-jqgrid .ui-pg-div .btn,
            .ui-jqgrid td .btn { 
                padding: 4px 10px !important;
                height: auto !important;
                font-size: 11px !important;
                width: auto !important;
            }
            .btn-container-responsive { display: flex !important; gap: 5px; width: 100%; }
            .btn-container-responsive .btn { flex: 1 !important; margin-bottom: 0 !important; }
            .input-group-btn .btn { width: auto; margin-bottom: 0; padding: 6px 12px; }

            /* Sincronizar altura de búsqueda usando Flexbox en móvil */
            .input-group { 
                display: flex !important; 
                width: 100% !important; 
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .form-control.input-xs { border-radius: 4px 0 0 4px !important; }
            input[name="search"].form-control, 
            input[name="Prs_Ced"].form-control, 
            input[data-trans="Prs_Ced"].form-control {
                height: 38px !important;
                padding: 6px 12px !important;
                box-sizing: border-box !important;
                flex: 1 !important;
                min-width: 0 !important;
            }
            .input-group-btn { display: flex !important; width: auto !important; }
            #search.btn, #Tra_Btn, .input-group-btn .btn {
                height: 38px !important;
                margin: 0 !important;
                padding: 6px 12px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                box-sizing: border-box !important;
            }
            /* Quitar bordes redondeados intermedios en grupos de botones */
            .input-group-btn .btn:not(:last-child) { border-radius: 0 !important; }
            .input-group-btn .btn:last-child { border-radius: 0 4px 4px 0 !important; }

            /* Ajuste específico para el campo Numero en móvil */
            .form-group:has(#Gui_Num) div[class*="col-xs-"] {
                width: 100% !important; /* Aumentar el ancho de la columna contenedora */
            }
            .input-group:has(#Gui_Num) {
                width: 100% !important;
            }
            .input-group-addon {
                flex-shrink: 0 !important; /* Evitar que el prefijo (001-001) se colapse */
                width: auto !important;
                display: flex !important;
                align-items: center !important;
            }

            /* Ajustes para evitar desbordamiento del pager en móvil */
            .ui-jqgrid .ui-jqgrid-pager {
                width: 100% !important;
                height: auto !important;
                table-layout: fixed !important;
            }
            .ui-jqgrid .ui-pg-table {
                table-layout: auto !important;
                width: 100% !important;
            }
            .ui-jqgrid .ui-pg-button {
                padding: 1px 2px !important;
            }
            /* Asegurar que las secciones del pager no se pisen en móvil */
            #searchGridPager_right { width: 33.33% !important; white-space: nowrap !important; overflow: hidden !important; }
            /* Reducir drásticamente el ancho de la columna de numeración en todos los grids en móvil */
            .ui-jqgrid td.jqgrid-rownum, 
            .ui-jqgrid th.jqgrid-rownum,
            .ui-jqgrid .jqgrid-rownum,
            .ui-jqgrid col[id$="_rn"],
            .ui-jqgrid table.ui-jqgrid-btable col:first-child,
            .ui-jqgrid table.ui-jqgrid-htable col:first-child {
                width: 30px !important;
                min-width: 30px !important;
                max-width: 30px !important;
                padding: 0 !important;
                text-align: center !important;
            }
            .ui-jqgrid .jqgrid-rownum div { 
                width: 30px !important; 
                text-align: center !important;
                margin: 0 !important;
            }

            /* Apilar columnas en 'Detalle Guia Destinatario' para evitar desbordamiento */
            #panelGuiaRemi .col-xs-4, 
            #panelGuiaRemi .col-xs-8 {
                width: 100% !important;
                padding: 5px !important;
            }
            #panelGuiaRemi .ui-jqgrid {
                width: 100% !important;
            }
            #panelGuiaRemi .ui-jqgrid-bdiv {
                overflow-x: auto !important; /* Permitir scroll horizontal en la tabla */
            }
            #itemsContainer {
                width: 100% !important;
                overflow-x: auto !important;
            }

            /* --- Ajustes para Modals (Dialogs) en móvil --- */
            .ui-dialog {
                width: 96% !important;
                left: 2% !important;
                max-width: 96vw !important;
                padding: 10px 5px !important;
                box-sizing: border-box !important;
            }
            .ui-dialog .ui-dialog-content { padding: 5px !important; }
            
            /* Ajustar filtros y radio buttons en modals */
            .ui-dialog .radioset label {
                padding: 5px 8px !important;
                font-size: 11px !important;
                flex: 1;
                text-align: center;
            }
            .ui-dialog .radioset {
                display: flex !important;
                flex-wrap: wrap !important;
                width: 100% !important;
            }

            /* Inputs y búsqueda dentro de modals */
            .ui-dialog .input-group { width: 100% !important; }
            .ui-dialog input[type="text"].form-control, 
            .ui-dialog input[type="search"].form-control {
                height: 38px !important;
                font-size: 13px !important;
            }
            
            /* Asegurar que la grilla dentro del modal no se salga */
            .ui-dialog .ui-jqgrid { width: 100% !important; }
            .ui-dialog .ui-jqgrid-bdiv { overflow-x: auto !important; }
        }
        .btn-container-responsive { display: inline-block; }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Guias de Remisión</h3>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch">
                <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');">
                    <div class="row">
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Búsqueda</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                    <div class="col-xs-10 radioset opt_search">
                                        <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Transportista&nbsp;&nbsp;&nbsp;</label>
                                        <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                        <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                    <div class="col-xs-7">
                                        <div class="input-group">
                                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus class="form-control input-sm clearable submit" />
                                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                        </div><!-- /input-group -->
                                    </div><input type="text" tabindex="-1" style="display:none;" />
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtros</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Estado:</label>&nbsp;
                                    <span class="radioset">
                                        <input id="op_est3" name="op_est" type="radio" value="T" style="cursor:pointer"><label for="op_est3"> Todas </label>
                                        <input id="op_est1" name="op_est" type="radio" value="A" checked='checked' style="cursor:pointer"><label for="op_est1"> Activas </label>
                                        <input id="op_est2" name="op_est" type="radio" value="I" style="cursor:pointer"><label for="op_est2">Anuladas</label>
                                    </span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Sucursal:</label>
                                    <div class="col-xs-9" >
                                        <select name="Suc_Cod" class="form-control input-xs" onchange="$(this.form).submit();">
                                            <option value=""><< TODOS >></option>
                                            <?php foreach($rs_sucursal as $row){ echo "<option value='$row[Suc_Cod]'>$row[Suc_Des]</option>"; } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                    <div class="col-xs-3">
                                        <select name="Pec_Cod" class="form-control input-xs search_pec getData ins" style="text-align: center;" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled'); ">
                                            <option value=""><< TODOS >></option>
                                            <?php foreach ($rs_periodo as $row) {
                                                $selected = ($row['Periodo'] == date('Y')) ? 'selected' : '';
                                                echo "<option value='$row[Pec_Cod]' data--Year='$row[Periodo]' $selected>$row[Periodo]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>
                                    <div class="col-xs-3">
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec" style="text-align: center;">
                                            <option value=""><< TODOS >></option>
                                            <?php for ($i = 1; $i <= 12; $i++) { ?>
                                            <option <?php if ($i == $mes) {
                                                        echo "selected=''";
                                                    } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
                <div style="min-height: 270px;">
                    <table id="searchGrid"></table>
                    <table id="searchGridPager"></table>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="glyphicon glyphicon-info-sign orange"></span> Autorización Pendiente | <span class="fa fa-globe green"></span> Guia Remision Autorizada </div>
                </div>
                <script>
                    function verDocument(doc) {
                        Aut_Tem = doc['Aut_Tem'];
                        $('#formDocumento').setData(doc);
                        $('#Tic_Cod_Guia option:selected').data({
                            //Aut_Cod:doc['Aut_Cod'],
                            Aut_Fci: doc['Aut_Fci'],
                            Aut_Cad: doc['Aut_Cad'],
                            Suc_Sri: doc['Suc_Sri'],
                            Pun_Sri: doc['Pun_Sri'],
                            Aut_Sri: doc['Aut_Num'],
                            Aut_Ini: doc['Aut_Ini'],
                            Aut_Fin: doc['Aut_Fin'],
                            Aut_Tem: doc['Aut_Tem']
                        });
                        $('#addDocBtn').css({
                            display: Aut_Tem !== 'E' ? 'none' : ''
                        });
                        $('#Gui_Num').data({
                            Gui_Num: doc['Gui_Num'],
                            Gui_Cod: doc['Gui_Cod']
                        });
                        $.getDataJson('', {
                            docDetalle: true,
                            Gui_Cod: doc['Gui_Cod']
                        }, function(resp) {
                            data_guia = resp['rows'];
                            docs.setRows(data_guia);
                            $('#panelDestinatario').hide();
                            $('#panelGuiaRemi').show().updateGridsSizes();
                            $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                            docs.jqGrid('setSelection', data_guia[0]['Gui_Index']);

                            
                            // Initialize dropdowns and selects based on observation data or main document data
                            
                            if (typeof window.recargarDerechosMineros === 'function') {
                                window.recargarDerechosMineros(doc['Der_Min_Id']);
                            }
                            
                            var obs = doc['Gui_Obs'] || '';
                            
                            // Parse Week (Semana# X)
                            var semanaMatch = obs.match(/(Semana#\s*\d+)/i);
                            if (semanaMatch) {
                                var sVal = semanaMatch[1].toUpperCase().replace("SEMANA", "Semana"); 
                                $('#Semana_Select').val(sVal);
                            } else {
                                $('#Semana_Select').val('');
                            }
                            
                            // Load Bodega_Nom from `data_guia[0]` (guia_destino.Bod_Nom)
                        //     if ($('#Bodega_Nom').length > 0) {
                        //         var $select = $('#Bodega_Nom');
                        //         $select.val(null).trigger('change');
                        //         // clear option dynamically loaded
                        //         $select.find('option.opt-dinamica, option[data-select2-tag="true"]').remove();
                                
                        //         var bodaStr = (data_guia && data_guia.length > 0) ? data_guia[0]['Bodega_Nom'] : '';
                        //         if (!bodaStr && doc['Bodega_Nom']) bodaStr = doc['Bodega_Nom'];
                                
                        //         var bodegasFromRow = bodaStr ? bodaStr.split(' - ') : [];
                                
                        //         bodegasFromRow.forEach(function(bodega) {
                        //             bodega = bodega.trim().toUpperCase();
                        //             if (bodega === "") return;
                                    
                        //             if ($select.find("option[value='" + bodega + "']").length === 0) {
                        //                 var newOption = new Option(bodega, bodega, true, true);
                        //                 $(newOption).addClass('opt-dinamica');
                        //                 $select.append(newOption);
                        //             } else {
                        //                 $select.find("option[value='" + bodega + "']").prop('selected', true);
                        //             }
                        //         });
                        //         $select.trigger('change.select2');
                        //     }
                        // Load Bc_Cod from doc
                            if ($('#Bc_Cod').length > 0) {
                                var bcCod = doc['Bc_Cod'];
                                if (bcCod) {
                                    $('#Bc_Cod').val(bcCod).trigger('change');
                                } else {
                                    $('#Bc_Cod').val('').trigger('change');
                                }
                            }
                        });
                    }
                </script>
            </div>
            <!-- copiado -->
            <div id="documentoMain" style="visibility: hidden;">
                <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
                    <input name="Gui_Cod" type="text" style="display:none;" />
                    <div class="row">
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Transportista</legend>
                                <div class="form-group trasportista">
                                    <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>
                                    <div class="col-xs-6" id="transFormTemp">
                                        <input name="Prs_Cod" type="text" data-trans="Prs_Cod" style="display:none;" />
                                        <input name="Gpe_Cod" type="text" data-trans="Gpe_Cod" style="display:none;" />
                                        <input name="op_opciones" type="text" value="c" style="display: none;">
                                        <div class="input-group input-group-xs">
                                            <input name="Prs_Ced" data-trans="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#transDialog',selectTrans); }" type="text" placeholder="Ingrese Trasportista..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                            <span class="input-group-btn">
                                                <button id="Tra_Btn" type="button" onclick="$('#transDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Transportista" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                <button type="button" onclick="$('#guiaPersonaDialog').setData({Gpe_Tip:'T'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialog').dialog('open'); " class="btn btn-success btn-xs" title="Registrar Transportista" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                <!--Editar-->
                                                <button type="button" onclick="$('#guiaPersonaDialogEdit').setData({Gpe_Tip:'T'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialogEdit').dialog('open'); $('.search_dest').hide(); $('.search_trans').show(); " class="btn btn-success btn-xs" title="Editar Transportista" tabindex="2"><span class="glyphicon glyphicon-pencil"></span></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group trasportista">
                                    <label class="col-xs-3 control-label label-xs required">R.Social:</label>
                                    <div class="col-xs-9">
                                        <span name="transportista" data-trans="transportista" class="form-control input-xs databind datatitle"></span>
                                    </div>
                                </div>
                                <div class="form-group trasportista">
                                    <label class="col-xs-3 control-label label-xs required">Placa:</label>
                                    <div class="col-xs-4">
                                        <input type="text" name="Gui_Pla" data-trans="Gpe_Pla" class="form-control input-xs" maxlength="10" required="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Fechas:</label>
                                    <div class="col-xs-9">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold required">Salida:</span>
                                            <input name="Gui_Fei" id="Gui_Fei" type="text" class="form-control span" style="text-align: center;" tabindex="-1" value="<?php echo $hoy; ?>" required="">
                                            <span class="input-group-addon bold required">Arrivo:</span>
                                            <input name="Gui_Fef" id="Gui_Fef" type="text" class="form-control span" style="text-align: center;" tabindex="-1" value="<?php echo $hoy; ?>" required="">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Dir. Salida:</label>
                                    <div class="col-xs-9">
                                        <textarea name="Gui_Dor" class="form-control input-xs" required=""></textarea>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos de la Guia de Remisión</legend>
                                <input name="Aut_Cod" type="text" data-trans="Prs_Cod" style="display:none;" value="<?php echo $autori['Aut_Cod']; ?>" />
                                <input name="Aut_Tem" type="text" data-trans="Prs_Cod" style="display:none;" value="<?php echo $autori['Aut_Tem']; ?>" />
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                                    <div class="col-xs-6">
                                        <select id="Tic_Cod_Guia" name="Tic_Cod" class="form-control input-xs readOnly getData ins" disabled="">
                                            <?php    echo "<option value='$autori[Tic_Cod]'  data--tic_-cod='$autori[Tic_Cod]' selected='' > ".utf8($autori['Tic_Des'])."  </option>";  ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Numero:</label>
                                    <div class="col-xs-4">
                                        <div class="input-group input-group-xs">
                                            <span id="Pun_Num" name='Pun_Num' class="input-group-addon alert-info"><?php echo $autori['Suc_Sri'] . '-' . $autori['Pun_Sri']; ?></span>
                                            <input type="text" id="Gui_Num" name="Gui_Num" onchange="validaVetNum()" class="form-control input-xs secuencia" tabindex="5" required="" style="text-align: right;">
                                            <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Fec. Emision:</label>
                                    <div class="col-xs-4">
                                        <input type="text" name="Gui_Fec" id="Gui_Fec" class="form-control input-xs" readonly="" value="<?php echo $hoy; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Autorizacion:</label>
                                    <div class="col-xs-6">
                                        <span name='Num_Aut_Sri' type="text" class="form-control input-xs"><?php echo $autori['Aut_Tem'] == 'N' ? $autori['Aut_Sri'] : 'ELECTRONICA'; ?></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Observacion:</label>
                                    <div class="col-xs-9">
                                        <textarea name="Gui_Obs" class="form-control input-xs"></textarea>
                                    </div>
                                </div>

                                <!-- SELECTOR DE DERECHO MINERO -->
                                <div class="form-group">
                                    <label class="col-sm-3 col-xs-12 control-label label-xs">Derecho Minero:</label>
                                    <div class="col-sm-9 col-xs-12">
                                        <select name="Der_Min_Id" id="Der_Min_Id" class="form-control input-xs" onchange="actualizarInfoDerecho(this)">
                                            <option value="">Cargando derechos mineros...</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Botones de gestión -->
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9 col-xs-12">
                                        <div class="btn-container-responsive">
                                            <button type="button" onclick="$('#derechoMineroDialog').dialog('open'); limpiarFormDerechoMinero();" class="btn btn-success btn-xs" title="Registrar Nuevo Derecho Minero">
                                                <span class="glyphicon glyphicon-plus"></span> Nuevo
                                            </button>
                                            <button type="button" onclick="editarDerechoMinero();" class="btn btn-warning btn-xs" title="Editar Derecho Minero Seleccionado" id="btnEditarDerecho">
                                                <span class="glyphicon glyphicon-pencil"></span> Editar
                                            </button>
                                        </div>
                                        <small class="help-block" style="margin-top: 5px; margin-bottom: 0; color: #666;">
                                            <i class="glyphicon glyphicon-info-sign"></i>
                                            Al seleccionar un derecho minero, su información se agregará automáticamente a las observaciones
                                        </small>
                                    </div>
                                </div>
                                <!-- FIN SELECTOR DE DERECHO MINERO -->

                                <?php if ($Ses_Emp_Cod == 608): ?>
                                    <div class="form-group">
                                        <label class="col-sm-3 col-xs-12 control-label label-xs">Semana:</label>
                                        <div class="col-sm-9 col-xs-12">
                                            <select name="Semana_Select" id="Semana_Select" class="form-control input-xs" onchange="actualizarSemana(this)">
                                                <option value="">Seleccione semana...</option>
                                                <?php
                                                $year = date('Y');
                                                $max_weeks = date("W", strtotime("December 28, $year"));
                                                for ($i = 1; $i <= $max_weeks; $i++) {
                                                    echo "<option value=\"Semana# $i\">Semana# $i</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 col-xs-12 control-label label-xs">Bodega:</label>
                                        <div class="col-sm-9 col-xs-12">
                                            <div style="display:flex; gap:4px; align-items:flex-start;">
                                                <div style="flex:1; min-width:0;">
                                                    <select name="Bc_Cod" id="Bc_Cod" class="form-control input-xs" style="width:100%">
                                                        <option value="">Seleccione una bodega...</option>
                                                        <?php
                                                        $queryBodegas = $obBD_con1->operacionobBD(31, array($Ses_Emp_Cod), $obBD_conexion);
                                                        if ($queryBodegas) {
                                                            while ($bod = mysqli_fetch_assoc($queryBodegas)) {
                                                                echo "<option value=\"{$bod['Bc_Cod']}\">{$bod['Bc_Nom']}</option>";
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <button type="button" onclick="$('#bodegaDialog').dialog('open'); $('#modal_nueva_bodega').val('').focus();" class="btn btn-success btn-xs" title="Añadir nueva bodega" style="flex-shrink:0; height:26px; margin-top:1px;">
                                                    <span class="glyphicon glyphicon-plus"></span> Añadir
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DIALOGO MODAL PARA BODEGA -->
                                    <div id="bodegaDialog" title="Nueva Bodega" style="display:none;">
                                        <div class="form-group" style="margin-bottom: 5px;">
                                            <label class="control-label label-xs" for="modal_nueva_bodega">Nombre de la bodega:</label>
                                            <input type="text" id="modal_nueva_bodega" class="form-control input-xs" placeholder="Escriba aquí la bodega...">
                                        </div>
                                        <div style="text-align: right; margin-top: 10px;">
                                            <button type="button" id="btnGuardarModalBodega" class="btn btn-primary btn-xs">
                                                <span class="glyphicon glyphicon-floppy-disk"></span> Guardar
                                            </button>
                                            <button type="button" onclick="$('#bodegaDialog').dialog('close');" class="btn btn-default btn-xs">
                                                <span class="glyphicon glyphicon-remove"></span> Cerrar
                                            </button>
                                        </div>
                                    </div>
                                    <!-- FIN DIALOGO -->
                                <?php endif; ?>

                                    <script type="text/javascript">
                                    <?php if($Ses_Emp_Cod == 608): ?>
                                    $(document).ready(function() {
                                        var $bodegaSelect = $('#Bc_Cod').select2({
                                            placeholder: "Seleccione...",
                                            allowClear: true,
                                            width: '100%'
                                        });

                                        // Inicializar modal dialog de Bodega
                                        $('#bodegaDialog').dialog({
                                            autoOpen: false,
                                            width: 350,
                                            modal: true,
                                            resizable: false
                                        });

                                        $('#btnGuardarModalBodega').on('click', function(e) {
                                            e.preventDefault();
                                            var term = $.trim($('#modal_nueva_bodega').val()).toUpperCase();
                                            var $btn = $(this);
                                            
                                            if (term !== '') {
                                                $btn.prop('disabled', true);
                                                $('#loader').show();
                                                
                                                $.ajax({
                                                    url: 'fac_alt_guia_remi_2.0.php?addBodegaAjax=true',
                                                    type: 'POST',
                                                    data: { Bc_Nom: term },
                                                    dataType: 'json',
                                                    success: function(res) {
                                                        if (res.success) {
                                                            var exists = $('#Bc_Cod').find("option[value='" + res.Bc_Cod + "']").length > 0;
                                                            if (!exists) {
                                                                var newOption = new Option(term, res.Bc_Cod, false, false);
                                                                $('#Bc_Cod').append(newOption).trigger('change');
                                                            }
                                                            $('#modal_nueva_bodega').val(''); 
                                                            $.alert(res.message); // Notificar exito
                                                        } else {
                                                            $.alert('Error: ' + res.message);
                                                        }
                                                    },
                                                    error: function() {
                                                        $.alert('Ocurrió un error en la conexión.');
                                                    },
                                                    complete: function() {
                                                        $btn.prop('disabled', false);
                                                        $('#loader').hide();
                                                    }
                                                });
                                            } else {
                                                $.alert("Escriba el nombre de la nueva bodega.");
                                            }
                                        });

                                        // Agregar a observación la bodega seleccionada
                                        $bodegaSelect.on('select2:select', function (e) {
                                            var selData = e.params.data;
                                            if (selData && selData.text) {
                                                agregarAObservacion(selData.text);
                                            }
                                        });
                                    });

                                    function agregarAObservacion(val) {
                                        var obsField = $('textarea[name="Gui_Obs"]');
                                        var currentObs = (obsField.val() || '').trim();
                                        val = val.trim().toUpperCase();
                                        
                                        if (currentObs === "") {
                                            obsField.val(val);
                                        } else {
                                            // Verificación robusta de duplicados
                                            var partes = currentObs.split(/\s*-\s*/);
                                            var existe = false;
                                            for(var i=0; i<partes.length; i++) {
                                                if(partes[i].trim().toUpperCase() === val) {
                                                    existe = true;
                                                    break;
                                                }
                                            }
                                            
                                            if (!existe) {
                                                obsField.val(currentObs + " - " + val);
                                            }
                                        }
                                    }

                                    function actualizarSemana(select) {
                                        var val = select.value;
                                        if (!val) return;
                                        agregarAObservacion(val);
                                    }
                                    <?php endif; ?>

                                    function actualizarInfoDerecho(select) {
                                        var selectedOption = select.options[select.selectedIndex];
                                        var obsField = $('textarea[name="Gui_Obs"]');
                                        var currentObs = obsField.val() || '';
                                        
                                        // Prefijo para identificar y reemplazar la información
                                        var prefix = "DERECHO MINERO:";
                                        
                                        if (select.value && selectedOption) {
                                            var codigo = selectedOption.getAttribute('data-codigo') || '';
                                            var nombre = selectedOption.getAttribute('data-nombre') || '';
                                            var titular = selectedOption.getAttribute('data-titular') || '';
                                            var tipo = selectedOption.getAttribute('data-tipo');
                                            
                                            // Limpieza de nulos o undefined
                                            if (tipo === 'null' || !tipo || tipo === 'undefined') {
                                                tipo = ''; 
                                            }
                                            
                                            var infoLine = prefix + " " + codigo + " - " + nombre;
                                            if (titular) infoLine += " (" + titular + ")";
                                            if (tipo) infoLine += " - TIPO: " + tipo;
                                            
                                            // Lógica de reemplazo exacta
                                            var lines = currentObs.split("\n");
                                            var found = false;
                                            for (var i = 0; i < lines.length; i++) {
                                                if (lines[i].trim().indexOf(prefix) === 0) {
                                                    lines[i] = infoLine;
                                                    found = true;
                                                    break;
                                                }
                                            }
                                            
                                            if (!found) {
                                                if (currentObs.trim() !== "") {
                                                    lines.push(infoLine);
                                                } else {
                                                    lines = [infoLine];
                                                }
                                            }
                                            
                                            obsField.val(lines.join("\n"));
                                        } else {
                                            // Si se quita la selección, eliminar la línea correspondiente
                                            var lines = currentObs.split("\n");
                                            var newLines = [];
                                            for (var i = 0; i < lines.length; i++) {
                                                if (lines[i].trim().indexOf(prefix) !== 0) {
                                                    newLines.push(lines[i]);
                                                }
                                            }
                                            obsField.val(newLines.join("\n").trim());
                                        }
                                    }
                                    </script>
                            </fieldset>
                        </div>

                    </div>
                </form>
                <div class="row" id="panelDestinatario" style="display: none;">
                    <div class="col-xs-12">
                        <form id="formDesti" class="form-horizontal normal formDatos" action="javascript:validaDestinatario();">
                            <input name="Gui_Index" type="text" style="display:none;" />
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Destinatario</legend>
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group destinatario">
                                            <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>
                                            <div class="col-xs-6" id="destiFormTemp">
                                                <input name="Prs_Cod" type="text" data-desti="Prs_Cod" style="display:none;" />
                                                <input name="Gpe_Cod" type="text" data-desti="Gpe_Cod" style="display:none;" />
                                                <input name="op_opciones" type="text" value="c" style="display: none;">
                                                <div class="input-group input-group-xs">
                                                    <input name="Prs_Ced" data-desti="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#destiDialog',selectDest); }" type="text" placeholder="Ingrese Destinatario..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                                    <span class="input-group-btn">
                                                        <button id="Des_Btn" type="button" onclick="$('#destiDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Destinatario" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        <button type="button" onclick="$('#guiaPersonaDialog').setData({Gpe_Tip:'D'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Destinatario" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                        <button type="button" onclick="$('#guiaPersonaDialogEdit').setData({Gpe_Tip:'D'}).find('.validate').find('i').removeAttr('class'); $('#guiaPersonaDialogEdit').dialog('open'); $('.search_dest').show(); $('.search_trans').hide(); " class="btn btn-success btn-xs" title="Editar Destinatario" tabindex="2"><span class="glyphicon glyphicon-pencil"></span></button>

                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group destinatario">
                                            <label class="col-xs-3 control-label label-xs required">R.Social:</label>
                                            <div class="col-xs-9">
                                                <span name="destinatario" data-desti="destinatario" class="form-control input-xs databind datatitle"></span>
                                            </div>
                                        </div>
                                        <div class="form-group destinatario">
                                            <label class="col-xs-3 control-label label-xs">Codigos:</label>
                                            <div class="col-xs-9">
                                                <div class="input-group input-group-xs">
                                                    <span class="input-group-addon bold" title="Codigo Establecimiento">Establec.:</span>
                                                    <input name="Gui_Ces" data-desti="Gpe_Ces" type="text" class="form-control span" style="text-align: right;" tabindex="-1">
                                                    <span class="input-group-addon bold" title="Documento Aduanero">D. Aduane.:</span>
                                                    <input name="Gui_Dad" data-desti="Gpe_Dad" type="text" class="form-control span" style="text-align: right;" tabindex="-1">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group destinatario">
                                            <label class="col-xs-3 control-label label-xs required">Motivo:</label>
                                            <div class="col-xs-3">
                                                <?php $motiv = $obBD_con1->getMotivos();  ?>
                                                <select name="Mot_Aux" id="Mot_Aux" class="form-control input-xs" required="">
                                                    <option value="">Seleccione..</option>
                                                    <?php foreach ($motiv as $v) {
                                                        echo "<option value=\"$v\" >$v</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                            <div class="col-xs-6" style="display: none;">
                                                <label class="control-label label-xs required">Descripcion Motivo:</label>
                                                <textarea id="Gui_Mot" name="Gui_Mot" class="form-control input-xs"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-xs-6">
                                                <label class="control-label label-xs required">Dir. Destino:</label>
                                                <textarea name="Gui_Dde" class="form-control input-xs" required=""></textarea>
                                            </div>

                                            <div class="col-xs-6">
                                                <label class="control-label label-xs">Ruta:</label>
                                                <textarea name="Gui_Rut" class="form-control input-xs"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <fieldset class="exa-fieldset venta">
                                            <legend class="Titulos2">Documento Respaldo</legend>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Documento:</label>
                                                <div class="col-xs-6">
                                                    <select name="Tic_Cod" data-venta="Tic_Cod" class="form-control input-xs readOnly getData ins" disabled="">
                                                        <option value="" selected="">Seleccione..</option>
                                                        <option value="1">FACTURA DE VENTA</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Numero:</label>
                                                <div class="col-xs-4">
                                                    <input name="Vet_Cod" type="text" data-venta="Vet_Cod" style="display:none;" />
                                                    <div class="input-group input-group-xs">
                                                        <span name="Vet_Prefix" data-venta="Vet_Prefix" class="input-group-addon bold alert-info databind">000-000-</span>
                                                        <input name="Vet_Num" data-venta="Vet_Num" type="text" class="form-control span" style="text-align: center;" readonly="" tabindex="-1" value="">
                                                        <span class="input-group-btn">
                                                            <button id="Vet_Btn" type="button" onclick="$('#vetDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        </span>
                                                    </div>
                                                </div>
                                                <label class="col-xs-2 control-label label-xs">Fec.&nbsp;Emision:</label>
                                                <div class="col-xs-3">
                                                    <span type="text" name="Caj_Fec" data-venta="Caj_Fec" class="form-control input-xs databind"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Autorizacion:</label>
                                                <div class="col-xs-9">
                                                    <span type="text" name="Aut_Sri" data-venta="Aut_Sri" class="form-control input-xs databind"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs">Cliente:</label>
                                                <div class="col-xs-9">
                                                    <span type="text" name="cliente" data-venta="cliente" class="form-control input-xs databind"></span>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="col-xs-12 center">
                                        <div class="btn-container-responsive" style="margin-top: 10px;">
                                            <button id="btnAddDesti" type="button" onclick="$(this.form).formSubmit();" class="btn btn-info btn-sm" title="Registrar Destinatario"><i class="glyphicon glyphicon-plus"></i> Agregar Destino a la Guia</button>
                                            <button type="button" onclick="$('#panelDestinatario').hide(); $('#panelGuiaRemi').show().updateGridsSizes();" class="btn btn-danger btn-sm" title="Cancelar"><i class="glyphicon glyphicon-plus"></i> Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
                <div class="row" id="panelGuiaRemi" style="visibility: hidden;">
                    <div class="col-xs-4">
                        <div style="min-height: 200px; padding: 5px 0;">
                            <table id="documentos"></table>
                            <div id="documentosPager"></div>
                        </div>
                    </div>
                    <div class="col-xs-8">
                        <div class="form-horizontal normal" id="formTempDestinat">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Destinatario</legend>
                                <input type="text" id="Gui_Index" name="Gui_Index" style="display: none;" />
                                <div class="form-group">
                                    <div class="col-xs-6">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold">Cedula:</span>
                                            <span name="Prs_Ced" class="form-control span"></span>
                                            <span class="input-group-addon bold">Destin.</span>
                                            <span name="destinatario" class="form-control span datatitle"></span>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold">Establecimiento:</span>
                                            <span name="Gui_Ces" class="form-control span"></span>
                                            <span class="input-group-addon bold">Cod. Aduanero</span>
                                            <span name="Gui_Dad" class="form-control span"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold">Destino:</span>
                                            <span name="Gui_Dde" class="form-control span datatitle"></span>
                                            <span class="input-group-addon bold">Motivo:</span>
                                            <span name="Gui_Mot" class="form-control span datatitle"></span>
                                            <span class="input-group-addon bold">Ruta:</span>
                                            <span name="Gui_Rut" class="form-control span datatitle"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold">Documen.:</span>
                                            <span name="Tic_Cod_Txt" class="form-control span"></span>
                                            <span class="input-group-addon bold">Numero:</span>
                                            <span name="documento" class="form-control span"></span>
                                            <span class="input-group-addon bold">Fecha:</span>
                                            <span name="Caj_Fec" class="form-control span"></span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div id="itemsContainer">
                            <table id="items"></table>
                            <div id="itemsPager"></div>
                        </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="btn-container-responsive">
                            <button type="button" class="btn btn-inverse btn-sm" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Volver</button>
                            <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 Titulos2">
                    <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                </div>
            </div>
            <!-- fin copiado -->
        </div>
    </div>

    <!-- copiado -->
    <!-- Busqueda de Transportista -->
    <div id="transDialog" title="B&uacute;squeda de Transportista"></div>
    <!-- Busqueda de Destinatarios -->
    <div id="destiDialog" title="B&uacute;squeda de Destinatario"></div>
    <!-- Busqueda de Productos -->
    <div id="proDialog" title="B&uacute;squeda de Productos"></div>
    <!-- Busqueda de Facturas de Venta -->
    <div id="vetDialog" title="B&uacute;squeda de Facturas de Venta"></div>
    <!-- Dialogo guardado impresion -->
    <div id="successDialog" title="Mensaje del Sistema" style="display: none;">
        <center>
            <b style="font-size:14px;">Se ha Guardado la Guia con Exito!</b>
            <button id="btnImpGuia" type="button" class="btn btn-info bntSuccess" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Guia</button>
            <button id="btnImpPdf" type="button" class="btn btn-info bntSuccess" onclick="window.open($(this).data('url'))"><i class="glyphicon glyphicon-file"></i> Visualizar PDF</button>
        </center>
    </div>
    <!-- Crear Trasportista/Destinatario -->
    <div id="guiaPersonaDialog" title="Registrar">
        <form class="form-horizontal normal" id="gpeCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <input name="Gpe_Tip" id="Gpe_Tip" type="text" class="hidden datatrigger" onchange="$('.gpediv').hide(); if(this.value==='T'){ $('.gpeTipo').html('Transportista'); $('.transportista').show(); }else{ $('.gpeTipo').html('Destinatario'); $('.destinatario').show(); }" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del <span class="gpeTipo">Trasnportista</span></legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProvee(this.value); }else{ $('#Ide_Cod').val(''); $('#Prv_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" />
                            <span class="input-group-addon validate"><i></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                    <div class="col-xs-5">
                        <?php $rs_identi = $obBD_con1->getArrayConsulta(9, '', $obBD_conexion); ?>
                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                            <option value=""></option>
                            <?php foreach ($rs_identi as $row) {
                                echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs">Nombres:</label>
                    <div class="col-xs-9"><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs required">Genero:</label>
                    <div class="col-xs-4">
                        <select name="Prs_Sex" class="form-control input-xs">
                            <option value="M">MASCULINO</option>
                            <option value="F">FEMENINO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Razon Social:</label>
                    <div class="col-xs-9"><input name="Gpe_Ras" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group gpediv transportista" style="display:none;">
                    <label class="col-xs-3 control-label label-xs">Placa:</label>
                    <div class="col-xs-4"><input name="Gpe_Pla" type="text" class="form-control input-xs" maxlength="10" /></div>
                </div>
                <div class="form-group gpediv destinatario" style="display:none;">
                    <label class="col-xs-3 control-label label-xs">Establec.:</label>
                    <div class="col-xs-2"><input name="Gpe_Ces" type="text" class="form-control input-xs" maxlength="3" /></div>
                    <label class="col-xs-3 control-label label-xs">C.Aduanero:</label>
                    <div class="col-xs-4"><input name="Gpe_Dad" type="text" class="form-control input-xs" maxlength="20" /></div>
                </div>
            </fieldset>
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos de Ubicación</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                    <div class="col-xs-4">
                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(13, '', $obBD_conexion); ?>
                        <select name="Ciu_Cod" class="form-control input-xs" required="">
                            <option value=""></option>
                            <?php foreach ($rs_ciudad as $row) {
                                echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Dirección:</label>
                    <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Teléfono:</label>
                    <div class="col-xs-4"><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>
                    <div class="col-xs-5"><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                </div>
            </fieldset>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
            <div class="Titulos2">
                <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
            </div>
        </form>
    </div>


    <!--Formulario para Editar  Trasportista/Destinatario-->
    <div id="guiaPersonaDialogEdit" title="Editar">
        <form class="form-horizontal normal" id="gpeEditForm" action="javascript:if(validaNoIdentif($('#gpeEditForm #Prs_Ced').val())['success']){ editarProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" id="Prs_Cod" type="hidden" class="text" />
            <input name="Gpe_Cod" id="Gpe_Cod" type="hidden" data-trans="Gpe_Cod" />
            <input name="Gpe_Tip" id="Gpe_Tip" type="text" class="hidden datatrigger" onchange="$('.gpediv').hide() ; if(this.value==='T'){ $('.gpeTipo').html('Transportista'); $('.transportista').show(); }else{ $('.gpeTipo').html('Destinatario'); $('.destinatario').show(); }" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del <span class="gpeTipo">Trasnportista</span></legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10 ? 2:1); $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProveeEdit(this.value); }else{ $('#Ide_Cod').val(''); $('#Prv_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" readonly />
                            <span class="input-group-addon validate"><i></i></span>
                        </div>
                    </div>
                    <button id="Tra_Btn" type="button" onclick="$('#transDialog').dialog('open');" class="btn btn-success btn-xs search_trans" title="Buscar Transportista" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                    <button id="Des_Btn search_dest" type="button" onclick="$('#destiDialog').dialog('open');" class="btn btn-success btn-xs search_dest" title="Buscar Destinatario" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Documento:</label>
                    <div class="col-xs-5">
                        <?php $rs_identi = $obBD_con1->getArrayConsulta(9, '', $obBD_conexion); ?>
                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                            <option value=""></option>
                            <?php foreach ($rs_identi as $row) {
                                echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz�n Social:</span></label>
                    <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs">Nombres:</label>
                    <div class="col-xs-9"><input name="Prs_Nom" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group natural">
                    <label class="col-xs-3 control-label label-xs required">Genero:</label>
                    <div class="col-xs-4">
                        <select name="Prs_Sex" class="form-control input-xs">
                            <option value="M">MASCULINO</option>
                            <option value="F">FEMENINO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Razon Social:</label>
                    <div class="col-xs-9"><input name="Gpe_Ras" type="text" class="form-control input-xs" /></div>
                </div>
                <div class="form-group gpediv transportista transportista_save" style="display:none;">
                    <label class="col-xs-3 control-label label-xs">Placa:</label>
                    <div class="col-xs-4"><input name="Gpe_Pla" type="text" class="form-control input-xs" maxlength="10" /></div>
                </div>

                <div class="form-group gpediv destinatario  destinatario_edit" style="display:none;">
                    <label class="col-xs-3 control-label label-xs">Establec.:</label>
                    <div class="col-xs-2"><input name="Gpe_Ces" type="text" class="form-control input-xs" maxlength="3" /></div>
                    <label class="col-xs-3 control-label label-xs">C.Aduanero:</label>
                    <div class="col-xs-4"><input name="Gpe_Dad" type="text" class="form-control input-xs" maxlength="20" /></div>
                </div>

            </fieldset>
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos de Ubicación</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                    <div class="col-xs-4">
                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(13, '', $obBD_conexion); ?>
                        <select name="Ciu_Cod" class="form-control input-xs" required="">
                            <option value=""></option>
                            <?php foreach ($rs_ciudad as $row) {
                                echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Dirección:</label>
                    <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Teléfono:</label>
                    <div class="col-xs-4"><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>
                    <div class="col-xs-5"><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                </div>
            </fieldset>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Editar</button>
            </div>
            <div class="Titulos2">
                <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
            </div>
        </form>
    </div>
    <!-- Fin de formulario para Editar  Trasportista/Destinatario-->

        <!-- DIÁLOGO PARA GESTIONAR DERECHOS MINEROS -->
            <div id="derechoMineroDialog" title="Gestionar Derecho Minero" style="display:none;">
                <form class="form-horizontal normal" id="formDerechoMinero" action="javascript:guardarDerechoMinero();">
                    <input name="Der_Min_Id" id="Der_Min_Id_Form" type="hidden" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos del Derecho Minero</legend>
                        <div class="form-group">
                            <label class="col-sm-3 col-xs-12 control-label label-xs required">Código:</label>
                            <div class="col-sm-4 col-xs-12">
                                <input name="Der_Min_Codigo" id="Der_Min_Codigo" type="text" class="form-control input-xs" maxlength="100" required="" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 col-xs-12 control-label label-xs required">Nombre:</label>
                            <div class="col-sm-9 col-xs-12">
                                <input name="Der_Min_Nombre" id="Der_Min_Nombre" type="text" class="form-control input-xs" maxlength="255" required="" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 col-xs-12 control-label label-xs required">Titular/Operador:</label>
                            <div class="col-sm-9 col-xs-12">
                                <select name="Der_Min_Titular_Operador" id="Der_Min_Titular_Operador" class="form-control input-xs" required="">
                                    <option value="TITULAR" selected>TITULAR</option>
                                    <option value="OPERADOR">OPERADOR</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 col-xs-12 control-label label-xs required">Tipo:</label>
                            <div class="col-sm-9 col-xs-12">
                                <?php 
                                 $tipos_derecho = $obBD_con1->getArrayConsulta(1281, '', $obBD_conexion);
                                $total_tipos = !empty($tipos_derecho) ? count($tipos_derecho) : 0;
                                $auto_select = ($total_tipos > 0 && $total_tipos <= 2); // Auto-seleccionar si hay 1 o 2 opciones
                                ?>
                                <select name="Der_Min_Tipo" id="Der_Min_Tipo" class="form-control input-xs" required="">
                                    <?php if (!$auto_select): ?>
                                        <option value="">Seleccione tipo...</option>
                                    <?php endif; ?>
                                    <?php 
                                    if (!empty($tipos_derecho)) {
                                        $first = true;
                                        foreach ($tipos_derecho as $tipo) {
                                            $selected = ($auto_select && $first) ? 'selected' : '';
                                            echo "<option value='{$tipo['Tip_Der_Min_Nombre']}' {$selected}>{$tipo['Tip_Der_Min_Nombre']}</option>";
                                            $first = false;
                                        }
                                    } else {
                                        // Opciones por defecto si no existe la tabla
                                        echo "<option value='Concesión Minera' selected>Concesión Minera</option>";
                                        echo "<option value='Permiso de Exploración'>Permiso de Exploración</option>";
                                        echo "<option value='Licencia de Explotación'>Licencia de Explotación</option>";
                                        echo "<option value='Concesión de Beneficio'>Concesión de Beneficio</option>";
                                        echo "<option value='Permiso de Libre Aprovechamiento'>Permiso de Libre Aprovechamiento</option>";
                                        echo "<option value='Otro'>Otro</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Tipo de Recurso:</label>
                            <div class="col-xs-6">
                                <select name="Der_Min_Recurso" id="Der_Min_Recurso" class="form-control input-xs">
                                    <option value="">Seleccione recurso...</option>
                                    <option value="METÁLICO">METÁLICO</option>
                                    <option value="NO METÁLICO">NO METÁLICO</option>
                                    <option value="MATERIALES DE CONSTRUCCIÓN">MATERIALES DE CONSTRUCCIÓN</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Ubicación:</label>
                            <div class="col-xs-9">
                                <input name="Der_Min_Ubicacion" id="Der_Min_Ubicacion" type="text" class="form-control input-xs" maxlength="255" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Observaciones:</label>
                            <div class="col-xs-9">
                                <textarea name="Der_Min_Observaciones" id="Der_Min_Observaciones" class="form-control input-xs" rows="3"></textarea>
                            </div>
                        </div>
                    </fieldset>
                    <div class="center">
                        <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        <button type="button" onclick="$('#derechoMineroDialog').dialog('close');" class="btn btn-sm btn-default">Cancelar</button>
                    </div>
                    <div class="Titulos2">
                        <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.
                    </div>
                </form>
            </div>
            <!-- FIN DIÁLOGO DERECHOS MINEROS -->
            <script type="text/javascript">
                $(function() {
                    
                    // Inicializar diálogo de derechos mineros
                    $('#derechoMineroDialog').dialog({
                        autoOpen: false,
                        width: 600,
                        modal: true,
                        closeOnEscape: true
                    });
                    
                    // Cargar derechos mineros al iniciar la página
                    recargarDerechosMineros();
                });
                
                // Limpiar formulario de derecho minero
                function limpiarFormDerechoMinero() {
                    $('#formDerechoMinero')[0].reset();
                    $('#Der_Min_Id_Form').val('');
                    $('#Der_Min_Titular_Operador').val('TITULAR'); // Resetear a TITULAR por defecto
                    $('#derechoMineroDialog').dialog('option', 'title', 'Registrar Nuevo Derecho Minero');
                }
                
                // Editar derecho minero seleccionado
                function editarDerechoMinero() {
                    var derMinId = $('#Der_Min_Id').val();
                    if (!derMinId) {
                        alert('Por favor seleccione un derecho minero para editar');
                        return;
                    }
                    
                    var selectedOption = $('#Der_Min_Id option:selected');
                    $('#Der_Min_Id_Form').val(derMinId);
                    $('#Der_Min_Codigo').val(selectedOption.data('codigo'));
                    $('#Der_Min_Nombre').val(selectedOption.data('nombre'));
                    $('#Der_Min_Titular_Operador').val(selectedOption.data('titular'));
                    $('#Der_Min_Tipo').val(selectedOption.data('tipo'));
                    $('#Der_Min_Recurso').val(selectedOption.data('recurso'));
                    
                    $('#derechoMineroDialog').dialog('option', 'title', 'Editar Derecho Minero');
                    $('#derechoMineroDialog').dialog('open');
                }
                
                // Guardar derecho minero (nuevo o editar)
                function guardarDerechoMinero() {
                    var formData = $('#formDerechoMinero').serializeArray();
                    var derMinId = $('#Der_Min_Id_Form').val();
                    var accion = derMinId ? 'modificar' : 'registrar';
                    
                    formData.push({name: 'guardarDerechoMineroAjax', value: '1'});
                    formData.push({name: 'accion', value: accion});
                    
                    $.ajax({
                        url: window.location.pathname,
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.message || 'Derecho minero guardado exitosamente');
                                $('#derechoMineroDialog').dialog('close');
                                // Recargar lista de derechos mineros
                                recargarDerechosMineros(response.Der_Min_Id);
                            } else {
                                alert('Error: ' + (response.message || 'No se pudo guardar el derecho minero'));
                                console.error('Error details:', response);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', xhr.responseText);
                            alert('Error al guardar: ' + error + '\nRevise la consola para más detalles');
                        }
                    });
                }
                
                // Recargar lista de derechos mineros
                function recargarDerechosMineros(selectId) {
                    // console.log('DEBUG: Llamando a recargarDerechosMineros...');
                    var derMinIdIdToSet = selectId || $('#Der_Min_Id').val();
                    var $select = $('#Der_Min_Id');
                    
                    // Si el select no existe, no hacemos nada (ej: empresa != 608)
                    if ($select.length === 0) return;
                    
                    // Mostrar indicador de carga
                    $select.html('<option value="">Cargando...</option>');
                    
                    $.ajax({
                        url: window.location.pathname,
                        type: 'POST',
                        data: { obtenerDerechosMineros: 1 },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $select.empty().append('<option value="">Seleccione Derecho Minero...</option>');
                                
                                if (response.derechos && response.derechos.length > 0) {
                                    response.derechos.forEach(function(derecho) {
                                        var optionHtml = '<option value="' + derecho.Der_Min_Id + '" ' +
                                            'data-codigo="' + (derecho.Der_Min_Codigo || "") + '" ' +
                                            'data-nombre="' + (derecho.Der_Min_Nombre || "") + '" ' +
                                            'data-titular="' + (derecho.Der_Min_Titular_Operador || "") + '" ' +
                                            'data-tipo="' + (derecho.Der_Min_Tipo || "") + '" ' +
                                            'data-recurso="' + (derecho.Der_Min_Recurso || "") + '">' +
                                            (derecho.Der_Min_Codigo ? derecho.Der_Min_Codigo + " - " : "") + derecho.Der_Min_Nombre + 
                                            '</option>';
                                        $select.append(optionHtml);
                                    });
                                    
                                    // Restaurar selección o seleccionar el nuevo
                                    if (derMinIdIdToSet) {
                                        $select.val(derMinIdIdToSet);
                                    } else {
                                        var currentObs = $('textarea[name="Gui_Obs"]').val() || '';
                                        if (currentObs) {
                                            var obsUpper = currentObs.toUpperCase();
                                            var found = false;
                                            $select.find('option').each(function() {
                                                var opt = $(this);
                                                if (opt.val() && !found) {
                                                    var mCodigo = (opt.data('codigo') || '').toString().toUpperCase();
                                                    var mNombre = (opt.data('nombre') || '').toString().toUpperCase();
                                                    var optText = opt.text().toUpperCase();
                                                    
                                                    // Buscamos coincidencia
                                                    if ((mCodigo && obsUpper.indexOf(mCodigo) !== -1) || 
                                                        (mNombre && obsUpper.indexOf(mNombre) !== -1) || 
                                                        (obsUpper.indexOf(optText) !== -1)) {
                                                        opt.prop('selected', true);
                                                        found = true;
                                                    }
                                                }
                                            });
                                        }
                                    }
                                }
                            } else {
                                $select.html('<option value="">Error al cargar derechos</option>');
                                console.error('Error details:', response);
                            }
                        },
                        error: function(xhr, status, error) {
                            $select.html('<option value="">Error de conexión</option>');
                            console.error('AJAX Error:', xhr.responseText);
                        }
                    });
                }
            </script>
            
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />
    <!-- fin copiado -->
    <script type="text/javascript">

    </script>
</BODY>

</HTML>