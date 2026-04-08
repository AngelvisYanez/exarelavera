<?php

/* DIRECTORIOS REQUERIDOS */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_tec_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Manifiesto_Tecnico($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Manifiesto_Tecnico;

/* formato para fechas */
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* para pruebas */
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 9600);

/* DECLARACION DE AJAX */

// Cargar niveles de humedad para el select
if(isset($loadHumedadAjax)){
    $resp = $obBD_con1->getArrayConsulta(1, "", $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar manifiestos para el select
if(isset($loadManifiestoAjax)){
    $resp = $obBD_con1->getArrayConsulta(8, "", $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Buscar manifiestos para el modal de búsqueda
if(isset($manifiestosAjax)){
    // Obtener parámetros de búsqueda, verificando múltiples fuentes
    $filtro = '';
    $search = '';
    $estado = 'A';
    
    // Intentar obtener desde $_REQUEST primero, luego desde variables globales
    if (isset($_REQUEST['op_opciones']) && $_REQUEST['op_opciones'] !== '') {
        $filtro = $_REQUEST['op_opciones'];
    } elseif (isset($op_opciones) && $op_opciones !== '') {
        $filtro = $op_opciones;
    }
    
    // El campo de búsqueda puede llamarse 'search' o 'searchMan' dependiendo de la configuración
    if (isset($_REQUEST['search']) && trim($_REQUEST['search']) !== '') {
        $search = trim($_REQUEST['search']);
    } elseif (isset($_REQUEST['searchMan']) && trim($_REQUEST['searchMan']) !== '') {
        $search = trim($_REQUEST['searchMan']);
    } elseif (isset($search) && trim($search) !== '') {
        $search = trim($search);
    } elseif (isset($searchMan) && trim($searchMan) !== '') {
        $search = trim($searchMan);
    }
    
    if (isset($_REQUEST['op_opciones2']) && $_REQUEST['op_opciones2'] !== '') {
        $estado = $_REQUEST['op_opciones2'];
    } elseif (isset($op_opciones2) && $op_opciones2 !== '') {
        $estado = $op_opciones2;
    }
    
    // Obtener Man_Tip del formulario
    $man_tip = 'P'; // Valor por defecto
    if (isset($_REQUEST['Man_Tip']) && trim($_REQUEST['Man_Tip']) !== '') {
        $man_tip = trim($_REQUEST['Man_Tip']);
    } elseif (isset($Man_Tip) && trim($Man_Tip) !== '') {
        $man_tip = trim($Man_Tip);
    }
    
    $parms = array( 
        'filtro' => $filtro, 
        'search' => $search,
        'Man_Tip' => $man_tip
        //'estado' => $estado
    );
    $resp = $obBD_con1->getArrayConsulta(10, $parms, $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Obtener el siguiente código de manifiesto técnico
if (isset($getLastMatAjax)) {
	$resp['success'] = false;
	$last_Mat = $obBD_con1->getRowConsulta(2, "", $obBD_conexion);
	if ($last_Mat['sig'] == 0) {
		$resp['data'] = "1";
	} else {
		$resp['data'] = $last_Mat['sig'];
	}
	if ($obBD_con1->Error == 0) {
		$resp['success'] = true;
		$resp['message'] = "Transaccion exitosa!";
	}
	$obBD_con1->echoJson($resp);
	exit();
}

// Obtener el siguiente código de manifiesto
if (isset($getLastManAjax)) {
	$resp['success'] = false;
	$last_Man = $obBD_con1->getRowConsulta(9, "", $obBD_conexion);
	if ($last_Man['sig'] == 0) {
		$resp['data'] = "1";
	} else {
		$resp['data'] = $last_Man['sig'];
	}
	if ($obBD_con1->Error == 0) {
		$resp['success'] = true;
		$resp['message'] = "Transaccion exitosa!";
	}
	$obBD_con1->echoJson($resp);
	exit();
}

/* Inicia la insercion o actualizacion de un manifiesto técnico */
if(isset($saveManiTecAjax)){
    try{
    // Verificar si es una edición (UPDATE) o un nuevo registro (INSERT)
    $esEdicion = isset($_POST['Mat_Cod']) && !empty($_POST['Mat_Cod']);
    
    // Recoger datos POST necesarios
    $valores = array(
        'Man_Cod' => isset($_POST['Man_Cod']) ? $_POST['Man_Cod'] : '',
        'Usu_Cod' => $Ses_Usu_Cod,
        'Hum_Cod' => isset($_POST['Hum_Cod']) ? $_POST['Hum_Cod'] : '',
        // 'Mat_Rso' => isset($_POST['Mat_Rso']) ? $_POST['Mat_Rso'] : '',
        'Mat_Dna' => isset($_POST['Mat_Dna']) ? $_POST['Mat_Dna'] : '',
        'Mat_Fde' => isset($_POST['Mat_Fde']) ? $_POST['Mat_Fde'] : '',
        'Mat_Eae' => isset($_POST['Mat_Eae']) ? $_POST['Mat_Eae'] : '',
        'Mat_Ear' => isset($_POST['Mat_Ear']) ? $_POST['Mat_Ear'] : '',
        'Mat_Oce' => isset($_POST['Mat_Oce']) ? $_POST['Mat_Oce'] : '',
        'Mat_Tra' => isset($_POST['Mat_Tra']) ? $_POST['Mat_Tra'] : ''
    );
    
    $resp['success'] = false;
    
    if ($esEdicion) {
        // Es una actualización (UPDATE) - mantener el usuario original
        $valores['Mat_Cod'] = $_POST['Mat_Cod'];
        // Obtener el registro actual para mantener el Usu_Cod original
        $valores_consulta = array('Mat_Cod' => $_POST['Mat_Cod']);
        $registro_actual = $obBD_con1->getRowConsulta(5, $valores_consulta, $obBD_conexion);
        if ($registro_actual && isset($registro_actual['Usu_Cod'])) {
            $valores['Usu_Cod'] = $registro_actual['Usu_Cod'];
        }
        $operacion = $obBD_con1->operacionobBD(6, $valores, $obBD_conexion);        

        if ($obBD_con1->Error == 0) {
            // Actualizar el estado del manifiesto: Man_Tip a 'GS' o 'R'
            $manifiesto_actual = $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('Man_Cod' => $_POST['Man_Cod'])), $obBD_conexion, true);
            $man_tes_actual = isset($manifiesto_actual['Man_Tes']) ? $manifiesto_actual['Man_Tes'] : '';
            
            // Determinar estado objetivo
            $target_man_tip = (isset($_POST['Man_Tip']) && $_POST['Man_Tip'] === 'R') ? 'R' : 'GS';
            $man_tes_nuevo = '';

            if ($target_man_tip === 'R') {
                // Lógica para RECHAZADO
                $partes = explode('-', $man_tes_actual);
                $tiene_R = false;
                foreach($partes as $parte){
                    if(trim($parte) === 'R') $tiene_R = true;
                }
                if(!$tiene_R) {
                    $man_tes_nuevo = $man_tes_actual . '-R';
                } else {
                    $man_tes_nuevo = $man_tes_actual;
                }
            } else {
                // Lógica existente para GS
                $partes = explode('-', $man_tes_actual);
                $partes_filtradas = array();
                $tiene_A = false;
                $tiene_GS = false;
                foreach($partes as $parte){
                    $parte = trim($parte);
                    if(!empty($parte)){
                        $partes_filtradas[] = $parte;
                        if($parte === 'A') $tiene_A = true;
                        if($parte === 'GS') $tiene_GS = true;
                    }
                }
                if(!$tiene_A) $partes_filtradas[] = 'A';
                if(!$tiene_GS) $partes_filtradas[] = 'GS';
                $man_tes_nuevo = implode('-', $partes_filtradas);
            }

            // Limpiar guiones
            $man_tes_nuevo = trim($man_tes_nuevo, '-');
            $man_tes_nuevo = preg_replace('/-+/', '-', $man_tes_nuevo);

            // Lógica para actualizar Man_Usu (Append JSON)
            $man_usu_actual = isset($manifiesto_actual['Man_Usu']) ? $manifiesto_actual['Man_Usu'] : '';
            $man_usu_arr = array();
            if(!empty($man_usu_actual)){
                $decoded = json_decode($man_usu_actual, true);
                if(is_array($decoded)){
                   if(isset($decoded[0])) $man_usu_arr = $decoded;
                   else $man_usu_arr[] = $decoded;
                }
            }
            // Agregar nuevo evento
            $man_usu_arr[] = array(
                'Usu_Cod' => $Ses_Usu_Cod,
                'Man_Tip' => $target_man_tip,
                'Fecha' => date('Y-m-d H:i:s')
            );
            $man_usu_json = addslashes(json_encode($man_usu_arr));

            $update_params = array('Man_Tip'=>$target_man_tip, 'Man_Tes'=>$man_tes_nuevo, 'Man_Usu'=>$man_usu_json, 'where'=>array('Man_Cod'=>$_POST['Man_Cod']));
            
            if ($target_man_tip === 'R') {
                $update_params['Man_Est'] = 'I';
            }

            $obBD_con1->operacionobBD('manifiesto.update', $update_params, $obBD_conexion);
            $resp['success'] = true;
            $resp['message'] = 'Manifiesto técnico actualizado correctamente';
        } else {
            $resp['message'] = 'Error al actualizar: ' . $obBD_con1->MsgError;
        }
    } else {
        // Obtener el siguiente código
        $last_Mat = $obBD_con1->getRowConsulta(2, "", $obBD_conexion);
        $valores['Mat_Cod'] = $last_Mat['sig'];
        /* CAMBIO DE TIPO DE MANIFIESTO A APROBADO */
        // $obBD_con1->operacionobBD('manifiesto.update', array('Man_Tip'=>'A', 'where'=>array('Man_Cod'=>$_POST['Man_Cod'])), $obBD_conexion);
        $operacion = $obBD_con1->operacionobBD(3, $valores, $obBD_conexion);
        
        if ($obBD_con1->Error == 0) {
            // Actualizar el estado del manifiesto: Man_Tip a 'GS' o 'R'
            $man_cod = isset($_POST['Man_Cod']) ? addslashes($_POST['Man_Cod']) : '';
            if(!empty($man_cod)){
                $manifiesto_actual = $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('Man_Cod' => $_POST['Man_Cod'])), $obBD_conexion, true);
                $man_tes_actual = isset($manifiesto_actual['Man_Tes']) ? $manifiesto_actual['Man_Tes'] : '';
                
                // Determinar estado objetivo
                $mat_eae = isset($_POST['Mat_Eae']) ? $_POST['Mat_Eae'] : '';
                $target_man_tip = ((isset($_POST['Man_Tip']) && $_POST['Man_Tip'] === 'R') || $mat_eae === 'R') ? 'R' : 'GS';
                $man_tes_nuevo = '';

                if ($target_man_tip === 'R') {
                    // Lógica para RECHAZADO
                    $partes = explode('-', $man_tes_actual);
                    $tiene_R = false;
                    foreach($partes as $parte){
                        if(trim($parte) === 'R') $tiene_R = true;
                    }
                    if(!$tiene_R) {
                        $man_tes_nuevo = $man_tes_actual . '-R';
                    } else {
                        $man_tes_nuevo = $man_tes_actual;
                    }
                } else {
                    // Lógica existente para GS
                    $partes = explode('-', $man_tes_actual);
                    $partes_filtradas = array();
                    $tiene_A = false;
                    $tiene_GS = false;
                    foreach($partes as $parte){
                        $parte = trim($parte);
                        if(!empty($parte)){
                            $partes_filtradas[] = $parte;
                            if($parte === 'A') $tiene_A = true;
                            if($parte === 'GS') $tiene_GS = true;
                        }
                    }
                    if(!$tiene_A) $partes_filtradas[] = 'A';
                    if(!$tiene_GS) $partes_filtradas[] = 'GS';
                    $man_tes_nuevo = implode('-', $partes_filtradas);
                }

                // Limpiar guiones
                $man_tes_nuevo = trim($man_tes_nuevo, '-');
                $man_tes_nuevo = preg_replace('/-+/', '-', $man_tes_nuevo);

                // Lógica para actualizar Man_Usu (Append JSON)
                $man_usu_actual = isset($manifiesto_actual['Man_Usu']) ? $manifiesto_actual['Man_Usu'] : '';
                $man_usu_arr = array();
                if(!empty($man_usu_actual)){
                    $decoded = json_decode($man_usu_actual, true);
                    if(is_array($decoded)){
                        if(isset($decoded[0])) $man_usu_arr = $decoded;
                        else $man_usu_arr[] = $decoded;
                    }
                }
                // Agregar nuevo evento
                $man_usu_arr[] = array(
                    'Usu_Cod' => $Ses_Usu_Cod,
                    'Man_Tip' => $target_man_tip,
                    'Fecha' => date('Y-m-d H:i:s')
                );
                $man_usu_json = addslashes(json_encode($man_usu_arr));

                $update_params = array('Man_Tip'=>$target_man_tip, 'Man_Tes'=>$man_tes_nuevo, 'Man_Usu'=>$man_usu_json, 'where'=>array('Man_Cod'=>$_POST['Man_Cod']));
                
                if ($target_man_tip === 'R') {
                    $update_params['Man_Est'] = 'I';
                }

                $obBD_con1->operacionobBD('manifiesto.update', $update_params, $obBD_conexion);
            }
            $resp['success'] = true;
            $resp['message'] = 'Manifiesto técnico registrado correctamente';
            $resp['Mat_Cod'] = $valores['Mat_Cod'];
        } else {
            $resp['message'] = 'Error al registrar: ' . $obBD_con1->MsgError;
        }
    }

    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al registrar: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

// Búsqueda por código QR en el modal
if(isset($_GET['buscarPorQRAjax']) || isset($buscarPorQRAjax)){
    $resp = array('success' => false, 'message' => '', 'data' => null);
    
    if (!isset($_GET['codigo_qr'])) {
        $resp['message'] = 'Debe ingresar un código QR';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $codigo_qr = trim($_GET['codigo_qr']);
    if (empty($codigo_qr)) {
        $resp['message'] = 'Debe ingresar un código QR';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    // El código QR puede venir en formato JSON: {"cliente":"...","Man_Cod":"...","Man_Num":"..."}
    // O directamente como Man_Cod
    $man_cod = '';
    
    // Intentar decodificar como JSON primero
    $qr_data = json_decode($codigo_qr, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($qr_data) && isset($qr_data['Man_Cod'])) {
        // Es JSON, extraer Man_Cod
        $man_cod = trim($qr_data['Man_Cod']);
    } else {
        // No es JSON, intentar extraer de texto plano con formato "CODIGO: X"
        // Buscar el patrón "CODIGO:" seguido de un número
        if (preg_match('/CODIGO:\s*(\d+)/i', $codigo_qr, $matches)) {
            $man_cod = trim($matches[1]);
        } else {
            // Si no encuentra el patrón, asumir que es directamente el Man_Cod
            $man_cod = trim($codigo_qr);
        }
    }
    
    // Validar que se haya obtenido Man_Cod
    if(empty($man_cod)){
        $resp['success'] = false;
        $resp['message'] = 'El código QR no contiene un Man_Cod válido.';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $valores = array(
        'man_cod' => $man_cod
    );
    $data = $obBD_con1->getRowConsulta(11, $valores, $obBD_conexion);
    
    if ($obBD_con1->Error == 0) {
        if ($data && !empty($data)) {
            $resp['success'] = true;
            $resp['data'] = $data;
            $resp['message'] = 'Manifiesto encontrado';
        } else {
            $resp['success'] = false;
            $resp['message'] = 'No existe el manifiesto buscado';
        }
    } else {
        $resp['message'] = 'Error en la consulta: ' . $obBD_con1->MsgError;
    }
    
    $obBD_con1->echoJson($resp);
    exit();
}

/* Carga de datos para el grid principal */
if(isset($LoadManifTecAjax)){
    $parms = array( 
        'Fec_IniM' => isset($_GET['Fec_IniM']) ? $_GET['Fec_IniM'] : '', 
        'Fec_FinM' => isset($_GET['Fec_FinM']) ? $_GET['Fec_FinM'] : '', 
        'filtro' => isset($_GET['op_opciones']) ? $_GET['op_opciones'] : '', 
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'ordenar' => isset($_GET['ordenar']) ? $_GET['ordenar'] : 'codigo_desc'
    );
    $resp = $obBD_con1->getArrayConsulta(4, $parms, $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

/* Eliminar el registro del manifiesto técnico */
if(isset($deleteManiTecAjax)){
    $resp = array();
    $resp['success'] = false;
    try {
        $valores = array( 'Mat_Cod' => $_POST['Mat_Cod']);
        $obBD_con1->operacionobBD(7, $valores, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Manifiesto técnico eliminado correctamente';
        } else {
            $resp['success'] = false;
            $resp['message'] = 'Error al eliminar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al eliminar: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/* Obtener datos completos de un manifiesto técnico para editar */
if(isset($getManifTecAjax)){
    $resp = array();
    $resp['success'] = false;
    
    if (!isset($_GET['Mat_Cod']) || empty($_GET['Mat_Cod'])) {
        $resp['message'] = 'No se proporcionó el código del manifiesto técnico';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $valores = array('Mat_Cod' => $_GET['Mat_Cod']);
    $data = $obBD_con1->getRowConsulta(5, $valores, $obBD_conexion);
    
    if ($obBD_con1->Error == 0 && $data) {
        $resp['success'] = true;
        $resp['data'] = $data;
        $resp['message'] = 'Datos cargados correctamente';
    } else {
        $resp['message'] = 'No se encontraron datos del manifiesto técnico: ' . $obBD_con1->MsgError;
    }
    
    $obBD_con1->echoJson($resp);
    exit();
}

/* Obtener nombres de usuarios para listado (Historial GE/GS) */
if(isset($_POST['getUsersNamesAjax'])){
    $codes_in = isset($_POST['codes']) ? $_POST['codes'] : '';
    $codes_arr = is_array($codes_in) ? $codes_in : explode(',', $codes_in);
    $codes_arr = array_filter(array_map('intval', $codes_arr));
    $codes_str = implode(',', $codes_arr);
    
    if(empty($codes_str)){
        $obBD_con1->echoJson(array());
        exit();
    }
    $resp = $obBD_con1->getArrayConsulta(12, array('codes' => $codes_str), $obBD_conexion);
    if(function_exists('utf8_encode_deep')) utf8_encode_deep($resp);
    $obBD_con1->echoJson($resp);
    exit();
}

/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?php echo "Manifiesto Técnico"; ?></TITLE>
        <meta charset="UTF-8">
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <style>
            #qr-reader-container-modal {
                text-align: center;
                position: relative;
            }
            
            #qr-reader-modal {
                min-height: 250px;
            }
            
            #qr-reader-modal video {
                width: 100%;
                max-width: 400px;
                border: 2px solid #333;
                border-radius: 5px;
            }
            
            .btn-rescan-qr-modal {
                margin-top: 10px;
                display: none;
            }
            .badge-activo {
                background-color: #28a745;
                color: white;
                padding: 3px 10px;
                border-radius: 10px;
                font-size: 11px;
            }
            
            .badge-inactivo {
                background-color:rgb(247, 57, 57);
                color: white;
                padding: 3px 10px;
                border-radius: 10px;
                font-size: 11px;
            }
            .badge-facturado {
                background-color:rgb(171, 111, 228);
                color: white;
                padding: 3px 10px;
                border-radius: 10px;
                font-size: 11px;
            }
            .badge-garita-in {
                background-color:rgb(255, 193, 7);
                color: #333;
                padding: 3px 10px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: bold;
            }
            .badge-garita-out {
                background-color:rgb(23, 162, 184);
                color: white;
                padding: 3px 10px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: bold;
            }
            .turno-estado.libre {
                background-color: #28a745;
                color: white;
            }
            
            /* Estilos para el modal de búsqueda de manifiestos - Habilitar scroll */
            #manifiestosDialog {
                overflow: hidden !important;
            }
            
            #manifiestosDialog .ui-dialog-content {
                overflow-y: auto !important;
                overflow-x: auto !important;
                max-height: 80vh !important;
                padding: 10px !important;
            }
            
            #manifiestosDialog .condensed {
                overflow-x: auto !important;
                overflow-y: visible !important;
            }
            
            #manifiestosDialog form {
                overflow: visible !important;
            }
            
            /* Asegurar que el grid tenga scroll si es necesario */
            #manifiestosGrid {
                overflow: visible !important;
            }

            /* ESTILOS CARD INFO (VISTA PREVIA ESTILO IMAGEN 2) */
            .info-view-container {
                padding: 10px;
                background: #fff;
            }
            .info-card-item {
                display: flex;
                background-color: #f7f9fc;
                border: 1px solid #eef2f8;
                border-radius: 4px;
                margin-bottom: 6px;
                overflow: hidden;
                align-items: center;
                min-height: 45px;
            }
            .info-card-bar {
                width: 5px;
                align-self: stretch;
            }
            .bar-blue { background-color: #007bff; }
            .bar-green { background-color: #28a745; }
            .bar-yellow { background-color: #ffc107; }
            .bar-purple { background-color: #6f42c1; }
            .bar-orange { background-color: #fd7e14; }
            
            .info-card-icon {
                padding: 8px 12px;
                font-size: 16px;
                color: #555;
                width: 40px;
                text-align: center;
            }
            .info-card-content {
                display: flex;
                padding: 8px 10px;
                flex-grow: 1;
                font-size: 13px;
                align-items: center;
            }
            .info-card-label {
                font-weight: 700;
                color: #444;
                width: 120px;
                flex-shrink: 0;
            }
            .info-card-value {
                color: #222;
                font-weight: 500;
                word-break: break-word;
            }
            
            #infoRegistroDialog fieldset {
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                padding: 10px 15px;
                border-radius: 8px;
            }
            #infoRegistroDialog legend {
                width: auto;
                padding: 0 10px;
                font-size: 14px;
                font-weight: bold;
                border-bottom: none;
                margin-bottom: 0;
                color: #007bff;
            }
            .dialog-footer-buttons {
                text-align: center;
                padding: 15px 0 5px 0;
            }
        </style>
    </HEAD>

    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo;Manifiesto Técnico</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <!-- AMBIENTE PRINCIPAL -->
                <div id="documentoSearch">
                    <div class="row">
                        <form name="searchManifestoTec" id="searchManifestoTec" class="form-horizontal normal" action="javascript:$('#man_tecGrid').Search('#searchManifestoTec','LoadManifTecAjax');">
                            <div class="col-xs-5">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">B&uacute;squeda</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-xs-10 radioset opt_search">
                                            <input id="radsf1" name="op_opciones" type="radio" value="u" checked="" onclick="setfocus(this.form.search)" alt="" />
                                            <label for="radsf1">Usuario</label>
                                            <input id="radsf2" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search)" alt="" />
                                            <label for="radsf2">No. Manifiesto</label>
                                            <input id="radsf3" name="op_opciones" type="radio" value="p" onclick="setfocus(this.form.search)" alt="" />
                                            <label for="radsf3">Placa</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                        <div class="col-xs-8">
                                            <div class="input-group">
                                                <input name="search" onkeydown="if (event.keyCode === 13) man_tec.trigger('reloadGrid')" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                                <span class="input-group-btn">
                                                    <button type="button" id="btnSearch" onclick="man_tec.trigger('reloadGrid')" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1">
                                                        <span class="glyphicon glyphicon-search"></span>
                                                        <span>Buscar</span>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-sm-7">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros</legend>
                                    <div class="form-group" style="margin-top: 10px; margin-left: 10px;">
                                        <label class="col-sm-1 control-label label-xs">Periodo:</label>
                                        <div class="col-sm-3">
                                            <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" style="text-align: center;" onchange="desbloquear();">
                                                <?php
                                                foreach ($periodos as $p) {
                                                    echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-6" style="display: flex; align-items: center; gap: 5px;">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon alert-info">Desde</span>
                                                <input type="text" id="Fec_IniM" name="Fec_IniM" class="form-control datepicker" style="text-align: center; width: 90px; padding: 0;"/>
                                            </div>
                                            <i class="glyphicon glyphicon-transfer" style="color: #666; font-size: 14px;"></i>
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon alert-info">Hasta</span>
                                                <input type="text" id="Fec_FinM" name="Fec_FinM" class="form-control" style="text-align: center; width: 90px; padding: 0;"/>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <!-- <div class="form-group">
                                    <div class="col-sm-12">
                                        <button type="button" id="btnNuevoManifTec" class="btn btn-success btn-xs pull-right" title="Nuevo Manifiesto Técnico" style="margin-top:10px;" onclick="abrirModalManifTec();">
                                            <span class="glyphicon glyphicon-plus"></span>
                                            Nuevo Manifiesto Técnico
                                        </button>
                                    </div>
                                </div> -->
                            </div>
                            <!-- Grid Principal de Manifiesto Técnico -->
                            <div class="col-sm-12" style="min-height: 350px; padding-bottom: 1px;">
                                <table id="man_tecGrid"></table>
                                <div id="man_tecGridPager"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Inicio del diálogo para buscar Manifiestos -->
            <div id="manifiestosDialog" title="B&uacute;squeda de Manifiestos">
                <form id="manifiestosForm" class="form-horizontal normal"></form>
            </div>

            <!-- dialogo de registro de manifiesto técnico -->
			<div id="manifTecDialog" title="Manifiesto Técnico" style="display: none;">
				<form id="manifTecForm" class="form-horizontal normal">
                    <input name="Mat_Cod" id="Mat_Cod" type="hidden" value="" />
                    <input name="Usu_Cod" id="Usu_Cod" type="hidden" value="<?php echo $Ses_Usu_Cod; ?>" />
                    
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos Generales</legend>
                        
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">C&oacute;digo Manifiesto:</label>
                            <div class="col-xs-8">
                                <div class="input-group input-group-xs">
                                    <input name="Man_Cod" id="Man_Cod" type="text" class="form-control input-xs" readonly />
                                    <span class="input-group-btn">
                                        <button type="button" id="btnBusMan" onclick="$('#manifiestosDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Manifiesto" tabindex="2">
                                            <span class="glyphicon glyphicon-search"></span>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Usuario:</label>
                            <div class="col-xs-8">
                                <input name="Usu_Nom" id="Usu_Nom" type="text" class="form-control input-xs" value="<?php echo isset($_SESSION['Ses_Prs_Nom']) && isset($_SESSION['Ses_Prs_Ape']) ? $_SESSION['Ses_Prs_Nom'] . ' ' . $_SESSION['Ses_Prs_Ape'] : ''; ?>" readonly />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Nivel de Humedad:</label>
                            <div class="col-xs-8">
                                <select id="Hum_Cod" name="Hum_Cod" class="form-control input-xs" required="">
                                    <option value="">-- Seleccione --</option>
                                </select>
                            </div>
                        </div>

                        <!-- <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Riesgo de Sobrecarga:</label>
                            <div class="col-xs-8">
                                <input name="Mat_Rso" id="Mat_Rso" type="text" maxlength="20" class="form-control input-xs" />
                            </div>
                        </div> -->

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Tipo de Desecho No Aprobado:</label>
                            <div class="col-xs-8">
                                <input name="Mat_Dna" id="Mat_Dna" type="text" maxlength="20" class="form-control input-xs" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Fecha:</label>
                            <div class="col-xs-3">
                                <input id="Mat_Fde" name="Mat_Fde" type="text" size="10" class="form-control input-xs datepicker" style="text-align: center" disabled/>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de la Celda de Residuos</legend>
                        
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">No. Celda:</label>
                            <div class="col-xs-8">
                                <input name="Mat_Nce" id="Mat_Nce" type="text" maxlength="20" class="form-control input-xs" readonly />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Código Celda:</label>
                            <div class="col-xs-8">
                                <input name="Mat_Cce" id="Mat_Cce" type="text" maxlength="20" class="form-control input-xs" readonly />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Nombre de Plataforma:</label>
                            <div class="col-xs-8">
                                <input name="Mat_Dce" id="Mat_Dce" type="text" maxlength="20" class="form-control input-xs" readonly />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Estado y Acción</legend>
                        
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Estado Ambiental:</label>
                            <div class="col-xs-8">
                                <select id="Mat_Eae" name="Mat_Eae" class="form-control input-xs" required="">
                                    <option value="">-- Seleccione --</option>
                                    <option value="A">A - Aceptado</option>
                                    <option value="R">R - Rechazado</option>
                                    <option value="AC">AC - Aceptado con Condición</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Estado de Acción:</label>
                            <div class="col-xs-8">
                                <select id="Mat_Ear" name="Mat_Ear" class="form-control input-xs" required="">
                                    <option value="">-- Seleccione --</option>
                                    <option value="TR">TR - Transporte</option>
                                    <option value="AT" selected="selected">AT - Almacenamiento Temporal</option>
                                    <option value="EL">EL - Eliminación</option>
                                    <option value="DF">DF - Disposición Final</option>
                                    <option value="CT">CT - Cierre Técnico</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Observación:</label>
                            <div class="col-xs-8">
                                <textarea name="Mat_Oce" id="Mat_Oce" class="form-control input-xs" maxlength="50"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Tratamiento:</label>
                            <div class="col-xs-8">
                                <select id="Mat_Tra" name="Mat_Tra" class="form-control input-xs">
                                    <option value="">-- Seleccione --</option>
                                    <option value="AT">AT - Almacenamiento Temporal</option>
                                    <option value="DF" selected="selected">DF - Disposición Final</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <div class="form-group center">
                        </br>
                        <a class="btn btn-sm btn-primary" onclick="GuardarManifTec()">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                        </a>
                        <a class="btn btn-sm btn-default" onclick="$('#manifTecDialog').dialog('close');">
                            <i class="glyphicon glyphicon-remove"></i> Cancelar
                        </a>
                    </div>
				</form>
			</div>

        </div>
        <style>
            .card-view { display: flex; flex-direction: column; gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
            .card-item { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 5px 0; }
            .card-label { font-weight: bold; }
        </style>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script src="../VALIDACIONES/man_tec_1.0.js?x=14"></script>
        <?php
        // Cerrado y liberacion de las conexiones
            $obBD_con1->liberar();
            $obBD_conexion->cerrar();
        ?>
        
        <!-- Diálogo para "Información del Registro" (Diseño Imagen 2) -->
        <div id="infoRegistroDialog" title="Información del Registro" style="display: none;">
            <div id="infoRegistroContent">
                <!-- Se llena vía AJAX en JS -->
            </div>
            <div class="dialog-footer-buttons">
                <button type="button" class="btn btn-primary btn-sm" onclick="$('#infoRegistroDialog').dialog('close');">
                    <i class="glyphicon glyphicon-ok"></i> <span>Cerrar</span>
                </button>
            </div>
        </div>
    </BODY>
</HTML>