<?php

/* DIRECTORIOS REQUERIDOS */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_busq_doc.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_BusqDoc($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con = new Class_Log_Datos_BusqDoc;

/* DECLARACION DE AJAX */

// Búsqueda por código QR
if(isset($_GET['buscarPorQRAjax']) || isset($buscarPorQRAjax)){
    $resp = array('success' => false, 'message' => '', 'data' => null);
    
    if (!isset($_GET['codigo_qr'])) {
        $resp['message'] = 'Debe ingresar un código QR';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $codigo_qr = trim($_GET['codigo_qr']);
    if (empty($codigo_qr)) {
        $resp['message'] = 'Debe ingresar un código QR';
        $obBD_con->echoJson($resp);
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
        // $man_cod = $codigo_qr;
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
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $valores = array(
        'man_cod' => $man_cod,
        'codigo_qr' => $codigo_qr  // Mantener el código completo por compatibilidad
    );
    $data = $obBD_con->getRowConsulta(1, $valores, $obBD_conexion);
    
    if ($obBD_con->Error == 0) {
        if ($data && !empty($data)) {
            $man_tip = isset($data['Man_Tip']) ? $data['Man_Tip'] : '';
            
            // Determinar el mensaje según el estado
            if ($man_tip == 'F') {
                $resp['success'] = false;
                $resp['data'] = $data;
                $resp['message'] = 'Este documento ya fue facturado';
                $resp['tipo_mensaje'] = 'info';
            } else if ($man_tip == 'R') {
                $resp['success'] = false;
                $resp['data'] = $data;
                $resp['message'] = 'Lo sentimos este documento fue rechazado';
                $resp['tipo_mensaje'] = 'error';
            } else {
                $resp['success'] = true;
                $resp['data'] = $data;
                $resp['message'] = 'Documento Encontrado';
                $resp['tipo_mensaje'] = 'success';
            }
        } else {
            $resp['success'] = false;
            $resp['message'] = 'No existe el documento buscado';
            $resp['tipo_mensaje'] = 'warning';
        }
    } else {
        $resp['message'] = 'Error en la consulta: ' . $obBD_con->MsgError;
        $resp['tipo_mensaje'] = 'error';
    }
    
    $obBD_con->echoJson($resp);
    exit();
}

// Búsqueda por número de manifiesto
if(isset($_GET['buscarPorManifiestoAjax']) || isset($buscarPorManifiestoAjax)){
    $resp = array('success' => false, 'message' => '', 'data' => null);
    
    if (!isset($_GET['numero_manifiesto'])) {
        $resp['message'] = 'Debe ingresar un número de manifiesto';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    // El año es opcional ahora
    if (!isset($_GET['pla_cod'])) {
        $resp['message'] = 'Debe ingresar el código de planta';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $numero_manifiesto = trim($_GET['numero_manifiesto']);
    // $anio = isset($_GET['anio']) ? trim($_GET['anio']) : '';
    $pla_cod = trim($_GET['pla_cod']);
    
    if (empty($numero_manifiesto)) {
        $resp['message'] = 'Debe ingresar un número de manifiesto';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    // El año es opcional ahora, si no se proporciona se busca sin restricción de año
    if (empty($pla_cod)) {
        $resp['message'] = 'Debe ingresar el código de planta';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $valores = array(
        'numero_manifiesto' => $numero_manifiesto,
        'pla_cod' => $pla_cod
    );
    $data = $obBD_con->getRowConsulta(2, $valores, $obBD_conexion);
    
    if ($obBD_con->Error == 0) {
        if ($data && !empty($data)) {
            $man_tip = isset($data['Man_Tip']) ? $data['Man_Tip'] : '';
            
            // Determinar el mensaje según el estado
            if ($man_tip == 'F') {
                $resp['success'] = false;
                $resp['data'] = $data;
                $resp['message'] = 'Este documento ya fue facturado';
                $resp['tipo_mensaje'] = 'info';
            } else if ($man_tip == 'R') {
                $resp['success'] = false;
                $resp['data'] = $data;
                $resp['message'] = 'Lo sentimos este documento fue rechazado';
                $resp['tipo_mensaje'] = 'error';
            } else {
                $resp['success'] = true;
                $resp['data'] = $data;
                $resp['message'] = 'Documento Encontrado';
                $resp['tipo_mensaje'] = 'success';
            }
        } else {
            $resp['success'] = false;
            $resp['message'] = 'No existe el documento buscado';
            $resp['tipo_mensaje'] = 'warning';
        }
    } else {
        $resp['message'] = 'Error en la consulta: ' . $obBD_con->MsgError;
        $resp['tipo_mensaje'] = 'error';
    }
    
    $obBD_con->echoJson($resp);
    exit();
}

// Búsqueda por placa
if(isset($_GET['buscarPorPlacaAjax']) || isset($buscarPorPlacaAjax)){
    $resp = array('success' => false, 'message' => '', 'data' => null);
    
    if (!isset($_GET['veh_pla'])) {
        $resp['message'] = 'Debe ingresar un número de placa';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $veh_pla = trim($_GET['veh_pla']);
    
    if (empty($veh_pla)) {
        $resp['message'] = 'Debe ingresar un número de placa';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $valores = array(
        'veh_pla' => $veh_pla
    );
    $data = $obBD_con->getRowConsulta(4, $valores, $obBD_conexion);
    
    if ($obBD_con->Error == 0) {
        if ($data && !empty($data)) {
            $man_tip = isset($data['Man_Tip']) ? $data['Man_Tip'] : '';
            
            // Determinar el mensaje según el estado
            if ($man_tip == 'F') {
                $resp['success'] = false;
                $resp['data'] = $data;
                $resp['message'] = 'Este documento ya fue facturado';
                $resp['tipo_mensaje'] = 'info';
            } else if ($man_tip == 'R') {
                $resp['success'] = false;
                $resp['data'] = $data;
                $resp['message'] = 'Lo sentimos este documento fue rechazado';
                $resp['tipo_mensaje'] = 'error';
            } else {
                $resp['success'] = true;
                $resp['data'] = $data;
                $resp['message'] = 'Documento Encontrado';
                $resp['tipo_mensaje'] = 'success';
            }
        } else {
            $resp['success'] = false;
            $resp['message'] = 'No existe el documento buscado';
            $resp['tipo_mensaje'] = 'warning';
        }
    } else {
        $resp['message'] = 'Error en la consulta: ' . $obBD_con->MsgError;
        $resp['tipo_mensaje'] = 'error';
    }
    
    $obBD_con->echoJson($resp);
    exit();
}

// Aprobar entrada o salida del manifiesto
if(isset($_GET['aprobarManifiestoAjax']) || isset($aprobarManifiestoAjax)){
    $resp = array('success' => false, 'message' => '', 'nuevo_estado' => '');
    
    if (!isset($_GET['man_cod'])) {
        $resp['message'] = 'No se proporcionó el código del manifiesto';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $man_cod = trim($_GET['man_cod']);
    if (empty($man_cod)) {
        $resp['message'] = 'El código del manifiesto está vacío';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    // Primero consultar el estado actual del manifiesto
    $valores_consulta = array(
        'man_cod' => $man_cod,
        'codigo_qr' => ''
    );
    $data = $obBD_con->getRowConsulta(1, $valores_consulta, $obBD_conexion);
    
    if ($obBD_con->Error != 0 || !$data || empty($data)) {
        $resp['message'] = 'No se encontró el manifiesto';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    $estado_actual = isset($data['Man_Tip']) ? $data['Man_Tip'] : '';
    $nuevo_estado = '';
    
    // Determinar el nuevo estado según el estado actual
    if ($estado_actual == 'P') {
        $nuevo_estado = 'GE';
    } else if ($estado_actual == 'A') {
        $nuevo_estado = 'GS';
    } else {
        $resp['message'] = 'El manifiesto no está en un estado válido para aprobar (debe ser P o A)';
        $obBD_con->echoJson($resp);
        exit();
    }
    
    // Actualizar el estado
    $valores_update = array(
        'man_cod' => $man_cod,
        'nuevo_estado' => $nuevo_estado,
        'estado_actual' => $estado_actual
    );
    $obBD_con->operacionobBD(3, $valores_update, $obBD_conexion);
    
    if ($obBD_con->Error == 0) {
        $resp['success'] = true;
        $resp['message'] = 'Estado actualizado correctamente';
        $resp['nuevo_estado'] = $nuevo_estado;
        $resp['estado_anterior'] = $estado_actual;
    } else {
        $resp['message'] = 'Error al actualizar el estado: ' . $obBD_con->MsgError;
    }
    
    $obBD_con->echoJson($resp);
    exit();
}

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?php echo "Búsqueda de Documentos"; ?></TITLE>
        <meta charset="UTF-8">
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <style>
            .search-container { width: 98%; max-width: 1600px; margin: 20px auto; padding: 20px; }
            #searchForm { max-width: 800px; margin: 0 auto; }
            .search-options { margin-bottom: 20px; }
            .search-options label { margin-right: 20px; cursor: pointer; }
            .search-input-group { margin-bottom: 20px; }
            .result-container { max-width: 800px; margin: 30px auto; padding: 20px; border: 2px solid #ddd; border-radius: 5px; display: none; position: relative; z-index: 100; }
            .result-container.show { display: block; }
            .result-container.success { border-color: #28a745; background-color: #d4edda; }
            .result-container.error { border-color: #dc3545; background-color: #f8d7da; }
            .result-container.info { border-color: #17a2b8; background-color: #d1ecf1; }
            .result-container.warning { border-color: #ffc107; background-color: #fff3cd; }
            .result-header { text-align: center; margin-bottom: 20px; }
            .result-icon { font-size: 48px; margin-bottom: 10px; }
            .result-icon.success { color: #28a745; }
            .result-icon.error { color: #dc3545; }
            .result-icon.info { color: #17a2b8; }
            .result-icon.warning { color: #ffc107; }
            /* Documento Encontrado siempre negro */
            .result-message-found { color: black !important; font-weight: bold; }
            .result-message { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
            .result-data { background-color: #fff; padding: 15px; border-radius: 5px; margin-top: 15px; }
            .result-data .row { margin-bottom: 10px; padding: 8px; border-bottom: 1px solid #eee; }
            .result-data .row:last-child { border-bottom: none; }
            .result-data .label { font-weight: bold; display: inline-block; width: 150px; }
            .result-data .value { display: inline-block; }
            
            /* Estilo destacado para el valor del Manifiesto */
            #manifiestoValue {
                background-color: #337ab7;
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                font-weight: bold;
                font-size: 18px !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                letter-spacing: 1px;
            }
            @keyframes checkmark { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
            @keyframes cross { 0%, 50%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-10deg); } 75% { transform: rotate(10deg); } }
            .result-icon.animate { animation: checkmark 0.6s ease-in-out; }
            .result-icon.error.animate { animation: cross 0.6s ease-in-out; }
            #qr-reader-container { text-align: center; position: relative; }
            #qr-reader { min-height: 250px; }
            #qr-reader video { width: 100%; max-width: 400px; border: 2px solid #333; border-radius: 5px; }
            .btn-rescan-qr { margin-top: 10px; display: none; }
            .qr-scanner-container { display: none; }
            /* Estilos generales para ajustar el tamaño de fuente a 16px en desktop */
            .search-container label,
            .search-container .form-control,
            .search-container .btn,
            .search-container .input-group-addon {
                font-size: 16px !important;
            }
            
            /* Asegurar que los botones de radio jQuery UI también tengan fuente 16px */
            .search-container .ui-button .ui-button-text {
                font-size: 16px !important;
            }

            /* Ajustar altura de inputs y botones para que se adapten al texto de 16px */
            .search-container .form-control {
                height: 40px !important;
            }

            .search-container .btn {
                padding: 8px 16px;
            }

            .search-container .input-group-addon {
                padding: 8px 12px;
            }
            
            /* Ajustar anchos específicos para el texto más grande */
            .manifiesto-fields #searchPlaCod {
                width: 120px !important;
            }
            
            .manifiesto-fields #searchInputManifiesto {
                width: 250px !important;
            }

            /* Estilos para desktop - mostrar estructura desktop y ocultar móvil */
            .manifiesto-fields-mobile {
                display: none !important;
            }
            
            /* Asegurar que input-group funcione correctamente */
            .manifiesto-fields.input-group {
                display: table;
                border-collapse: separate;
            }
            
            .manifiesto-fields.input-group .input-group-addon,
            .manifiesto-fields.input-group .form-control,
            .manifiesto-fields.input-group .input-group-btn {
                display: table-cell;
                vertical-align: middle;
            }
            
            .manifiesto-fields.input-group .input-group-addon:first-child,
            .manifiesto-fields.input-group .form-control:first-child,
            .manifiesto-fields.input-group .input-group-btn:first-child > .btn {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }
            
            .manifiesto-fields.input-group .input-group-addon:last-child,
            .manifiesto-fields.input-group .form-control:last-child,
            .manifiesto-fields.input-group .input-group-btn:last-child > .btn {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }
            
            /* Anchos específicos */
            .manifiesto-fields select {
                width: auto;
                min-width: 130px;
            }
            
            .manifiesto-fields #searchPlaCod {
                width: 80px;
                min-width: 80px;
                border-right: none;
                margin-right: 0;
                padding-right: 0;
            }
            
            /* Eliminar espacio entre código de planta y el guion */
            .manifiesto-fields #searchPlaCod + .input-group-addon {
                border-left: none;
                margin-left: 0;
                padding-left: 0;
            }
            
            .manifiesto-fields #searchInputManifiesto {
                width: 100px;
                min-width: 100px;
                border-right: none;
            }
            
            /* Estilos para unir el input con el display del número */
            .manifiesto-fields .manifiesto-number-display {
                background-color: #d9edf7;
                color: #31708f;
                font-weight: bold;
                border-left: none;
                padding-left: 0 !important;
                margin-left: 0 !important;
                vertical-align: middle;
            }
            
            .manifiesto-fields #searchInputManifiesto {
                margin-right: 0 !important;
                padding-right: 0 !important;
            }
            
            .manifiesto-fields-mobile .manifiesto-number-display-mobile {
                background-color: #d9edf7;
                color: #31708f;
                font-weight: bold;
                border-left: none;
                padding-left: 0 !important;
                margin-left: 0 !important;
                vertical-align: middle;
            }
            
            .manifiesto-fields-mobile #searchInputManifiestoMobile {
                margin-right: 0 !important;
                padding-right: 0 !important;
            }
            
            /* Eliminar espacio entre código de planta y el guion en móvil */
            .manifiesto-fields-mobile #searchPlaCodMobile {
                border-right: none;
                margin-right: 0;
                padding-right: 0;
            }
            
            .manifiesto-fields-mobile #searchPlaCodMobile + .input-group-addon {
                border-left: none;
                margin-left: 0;
                padding-left: 0;
            }
            
            /* Estilos para móvil y tablet */
            @media (max-width: 992px) {
            /* Estilos específicos para la estructura de la página */
            html, body {
                height: 100%;
                width: 100%;
                margin: 0;
                padding: 0;
                overflow: hidden !important; /* Bloquear scroll en body */
                position: fixed; /* Fijar body para evitar rebote en iOS */
            }
            
            .panel-main {
                position: fixed !important; /* Fijar contenedor principal */
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                margin: 0 !important;
                border: none !important;
                border-radius: 0;
                z-index: 1000;
                background-color: #fff;
            }
            
            .panel-heading {
                flex: 0 0 auto; /* No encoger, altura automática */
                z-index: 2000 !important; /* Z-index superior al contenido */
                position: relative;
                display: block !important;
                color: white !important;
                padding: 15px !important;
                min-height: 50px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2); /* Sombra para separar visualmente */
                width: 100%;
            }
            
            .panel-title {
                font-size: 20px !important;
                margin: 0 !important;
                display: block !important;
                font-weight: bold;
                line-height: 1.2;
            }
            
            .panel-body {
                overflow-y: auto !important; /* Scroll solo aquí */
                flex: 1;
                padding-bottom: 50px;
                -webkit-overflow-scrolling: touch;
                position: relative;
                z-index: 1;
                width: 100%;
            }
            
            .search-container {
                padding: 10px;
                max-width: 100%;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label.control-label {
                text-align: left !important;
                padding-left: 0;
                margin-bottom: 10px;
                font-size: 16px; /* Fuente más grande */
                width: 100%;
                float: none;
            }
            
            /* Forzar que las columnas ocupen todo el ancho para apilarse */
            .form-group .col-xs-2,
            .form-group .col-xs-10,
            .form-group .col-xs-6,
            .form-group .col-xs-4 {
                width: 100%;
                float: none;
                padding-left: 0;
                padding-right: 0;
            }
            
            .radioset {
                display: flex;
                flex-direction: row;
                gap: 10px;
                flex-wrap: nowrap;
                width: 100%;
                justify-content: center;
            }
            
            .radioset label {
                padding: 15px 10px;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                flex: 1;
                text-align: center;
                white-space: normal; /* Permitir saltos de línea si es necesario */
                font-size: 16px !important; /* Fuente más grande */
                font-weight: 500;
                line-height: 1.2;
                height: auto;
                min-height: 50px;
            }
            
            /* Ocultar estructura desktop y mostrar móvil */
            .manifiesto-fields {
                display: none !important;
            }
            
            .manifiesto-fields-mobile {
                display: block !important;
            }
            
            /* Contenedor principal de inputs en móvil */
            .manifiesto-fields-mobile {
                width: 100%;
                max-width: 600px; /* Limitar ancho en tablets grandes */
                margin: 0 auto;
            }
            
            /* Centrar el apartado de Manifiesto en móvil */
            #searchManifiestoGroup {
                text-align: left;
            }
            
            #searchManifiestoGroup label.control-label {
                text-align: left !important;
                width: 100%;
                margin-bottom: 5px;
            }
            
            #searchManifiestoGroup .col-xs-6 {
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
                padding: 0;
            }

            /* Fila de inputs unificada y horizontal con botón incluido */
            .row-mobile-inputs {
                display: flex;
                flex-direction: row;
                align-items: stretch;
                justify-content: center;
                width: 100%;
                margin-bottom: 0;
                border: 1px solid #ccc;
                border-radius: 4px;
                overflow: hidden; /* Para que los bordes redondeados funcionen */
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            /* Estilos para los elementos dentro de la fila horizontal */
            .row-mobile-inputs .input-addon-mobile {
                /* background-color: #d9edf7; */
                background-image: -webkit-linear-gradient(top, #d9edf7 0, #b9def0 100%);
                color: #31708f;
                border: none;
                border-right: 1px solid #bce8f1;
                padding: 5px 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 16px;
            }
            
            .row-mobile-inputs input.form-control {
                border: none;
                border-right: 1px solid #eee;
                border-radius: 0;
                box-shadow: none;
                height: 40px;
                padding: 6px 10px;
                font-size: 16px;
                text-align: center;
            }
            
            .row-mobile-inputs input.form-control:focus {
                background-color: #f8f8f8;
                z-index: 2;
            }

            /* Ajuste de anchos relativos */
            .row-mobile-inputs .input-planta {
                width: 20%;
                min-width: 50px;
            }
            
            .row-mobile-inputs .input-numero {
                flex: 1; /* Toma el espacio restante */
                min-width: 80px;
                border-right: none;
            }
            
            /* Botón de búsqueda integrado */
            .row-mobile-inputs .btn-mobile-search {
                border-radius: 0;
                border: none;
                padding: 0 15px;
                font-size: 16px;
                font-weight: bold;
                height: auto;
            }
            
            /* Botón Buscar y Limpiar en Móvil - eliminado ya que se integró */
            .btn-group-mobile {
                display: none;
            }
            
            /* Resultados */
            .result-container {
                margin-top: 25px;
                padding: 20px;
                font-size: 16px;
            }
            
            .result-data .label {
                font-size: 14px;
                width: 140px;
            }
            
            .result-data .value {
                font-size: 16px;
            }
            
            /* Ajustes para Placa y QR en móvil */
            #searchPlaca {
                height: 50px;
                font-size: 18px;
                text-align: center;
            }
            
            #btnSearchPlaca {
                height: 50px;
                font-size: 16px;
            }
            
            /* Ajustar ancho del select de Estado en móvil para que sea similar al botón de Manifiesto */
            #Man_Tip {
                width: auto !important;
                min-width: 150px;
                max-width: 180px;
                display: inline-block;
            }
            
            /* Transformar input-group de placa en stack vertical para móvil */
            #searchPlacaGroup .input-group {
                display: block !important;
                width: 100% !important;
            }

            #searchPlacaGroup .input-group .form-control {
                display: block !important;
                width: 100% !important;
                border-radius: 4px !important;
                margin-bottom: 10px !important;
                height: 50px !important;
            }

            #searchPlacaGroup .input-group-btn {
                display: flex !important;
                width: 100% !important;
                justify-content: space-between !important;
                gap: 10px !important;
            }

            #searchPlacaGroup .input-group-btn .btn {
                flex: 1 !important;
                border-radius: 4px !important;
                margin-left: 0 !important; /* Override inline style */
                height: 50px !important;
                font-size: 16px !important;
                width: auto !important;
            }
            }
        </style>
        <!-- Librería para escanear QR -->
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    </HEAD>

    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo;Búsqueda de Documentos</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="search-container">
                    <form name="searchForm" id="searchForm" class="form-horizontal normal">
                        <!-- Input oculto para almacenar el Man_Cod del registro consultado -->
                        <input type="hidden" id="hiddenManCod" name="hiddenManCod" value="">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Tipo de Búsqueda</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Filtrado Por:</label>
                                <div class="col-xs-10">
                                    <span class="radioset">
                                        <input id="radio_manifiesto" name="tipo_busqueda" type="radio" value="manifiesto" checked="checked" style="cursor:pointer"><label for="radio_manifiesto">Manifiesto Nº</label>
                                        <input id="radio_placa" name="tipo_busqueda" type="radio" value="placa" style="cursor:pointer"><label for="radio_placa">Placa</label>
                                        <input id="radio_qr" name="tipo_busqueda" type="radio" value="qr" style="cursor:pointer"><label for="radio_qr">Código QR</label>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Campos para búsqueda por número de manifiesto -->
                            <div class="form-group search-input-group" id="searchManifiestoGroup">
                                <label class="col-xs-2 control-label">Manifiesto:</label>
                                <div class="col-xs-6">
                                    <!-- Estructura para Desktop -->
                                    <div class="input-group manifiesto-fields">
                                        <span class="input-group-addon alert-info" style="min-width: 30px; text-align: center;">M</span>
                                        <input type="text" id="searchPlaCod" name="searchPlaCod" class="form-control" placeholder="Planta" maxlength="2" style="text-align: center; margin: 0; border-right: none;" pattern="[0-9]*" inputmode="numeric">
                                        <span class="input-group-addon alert-info" style="min-width: 20px; text-align: center; padding-left: 0; padding-right: 0; border-left: none; border-right: none;">-</span>
                                        <input type="text" id="searchInputManifiesto" name="searchInputManifiesto" class="form-control" placeholder="Manif #" style="text-align: center; margin: 0; border-left: none;" pattern="[0-9]*" inputmode="numeric">
                                        <span class="input-group-btn">
                                            <button type="button" id="btnSearchManifiesto" class="btn btn-success" title="Buscar Documento">
                                                <span class="glyphicon glyphicon-search"></span>
                                                Buscar
                                            </button>
                                        </span>
                                    </div>
                                    
                                    <!-- Estructura para Móvil -->
                                    <div class="manifiesto-fields-mobile">
                                        <!-- Fila única de inputs horizontal con botón integrado -->
                                        <div class="row-mobile-inputs">
                                            <span class="input-addon-mobile">M</span>
                                            <input type="text" id="searchPlaCodMobile" name="searchPlaCodMobile" class="form-control input-planta" placeholder="Planta" maxlength="2" pattern="[0-9]*" inputmode="numeric">
                                            <span class="input-addon-mobile">-</span>
                                            <input type="text" id="searchInputManifiestoMobile" name="searchInputManifiestoMobile" class="form-control input-numero" placeholder="Manif #" maxlength="4" pattern="[0-9]*" inputmode="numeric">
                                            <button type="button" id="btnSearchManifiestoMobile" class="btn btn-success btn-mobile-search" title="Buscar Documento">
                                                <span class="glyphicon glyphicon-search"></span>
                                                Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos para búsqueda por placa -->
                            <div class="form-group search-input-group" id="searchPlacaGroup" style="display: none;">
                                <label class="col-xs-2 control-label">Placa:</label>
                                <div class="col-xs-10">
                                    <div class="input-group">
                                        <input type="text" id="searchPlaca" name="searchPlaca" class="form-control" placeholder="Ingrese número de placa" maxlength="10" style="text-align: left;">
                                        <span class="input-group-btn">
                                            <button type="button" id="btnSearchPlaca" class="btn btn-success" title="Buscar Documento">
                                                <span class="glyphicon glyphicon-search"></span>
                                                Buscar
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campos para búsqueda por QR - Escáner integrado -->
                            <div class="form-group search-input-group" id="searchQRGroup" style="display: none;">
                                <label class="col-xs-2 control-label">Escáner QR:</label>
                                <div class="col-xs-10">
                                    <div id="qr-reader-container" style="width: 100%; max-width: 500px; margin: 0 auto; padding: 10px; background-color: #f9f9f9; border: 2px solid #ddd; border-radius: 5px;">
                                        <div id="qr-reader" style="width: 100%;"></div>
                                        <button type="button" id="btnRescanQR" class="btn btn-info btn-rescan-qr">
                                            <span class="glyphicon glyphicon-qrcode"></span>
                                            Escanear Nuevamente
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    
                    <!-- Contenedor de resultados -->
                    <div id="resultContainer" class="result-container">
                        <div class="result-header">
                            <div id="resultIcon" class="result-icon"></div>
                            <div id="resultMessage" class="result-message"></div>
                        </div>
                        <div id="resultData" class="result-data" style="display: none;">
                            <div class="row">
                                <span class="label" style="color: black; font-size: 14px;">CHOFER:</span>
                                <span class="value" id="choferValue"></span>
                            </div>
                            <div class="row">
                                <span class="label" style="color: black; font-size: 14px;">PLACA:</span>
                                <span class="value" id="placaValue"></span>
                            </div>
                            <div class="row">
                                <span class="label" style="color: black; font-size: 14px;">No. MANIFIESTO:</span>
                                <span class="value" id="manifiestoValue"></span>
                            </div>
                            <div class="row">
                                <span class="label" style="color: black; font-size: 14px;">FECHA:</span>
                                <span class="value" id="fechaValue"></span>
                            </div>
                            <div class="row" id="notaProceso" style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd; display: none;">
                                <div style="text-align: center; width: 100%; padding: 10px;">
                                    <span style="color: #31708f; font-size: 14px; font-weight: bold; font-style: italic;" id="notaTexto"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 20px; text-align: center; padding-top: 15px;">
                            <button type="button" id="btnAprobarManifiesto" class="btn btn-primary btn-lg" style="display: none;">
                                <span class="glyphicon glyphicon-ok"></span>
                                <span id="btnAprobarTexto">Aprobar Entrada</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Botón para volver a escanear después de ver resultados (solo visible cuando se escaneó) -->
                    <div class="form-group" id="rescanButtonGroup" style="display: none; margin-top: 15px;">
                        <div class="col-xs-12 text-center">
                            <button type="button" id="btnRescanFromResult" class="btn btn-info btn-sm">
                                <span class="glyphicon glyphicon-qrcode"></span>
                                Escanear QR Nuevamente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="../VALIDACIONES/man_val_busq_doc.js?e=1"></script>
        <?php
        // Cerrado y liberacion de las conexiones
        $obBD_con->liberar();
        $obBD_conexion->cerrar();
        ?>
    </BODY>
</HTML>