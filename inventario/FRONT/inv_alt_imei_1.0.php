<?php

/**
 * @abstract Permite realizar el registro y control de IMEI de teléfonos
 *
 * @author Exa Contable
 * @version 1.0
 * Fecha de creación: 2026-01-19
 */

// Verificar si existe vendor local, si no usar el de facturacion
$vendor_path = '';
if (file_exists(__DIR__ . '/vendor/php-excel-reader/excel_reader2.php')) {
    $vendor_path = __DIR__ . '/vendor/';
} elseif (file_exists(__DIR__ . '/../facturacion/FRONT/vendor/php-excel-reader/excel_reader2.php')) {
    $vendor_path = __DIR__ . '/../facturacion/FRONT/vendor/';
}

if (!empty($vendor_path)) {
    include $vendor_path . 'php-excel-reader/excel_reader2.php';
    include $vendor_path . 'SpreadsheetReader.php';
} else {
    // Intentar rutas alternativas
    if (file_exists('../../facturacion/FRONT/vendor/php-excel-reader/excel_reader2.php')) {
        include '../../facturacion/FRONT/vendor/php-excel-reader/excel_reader2.php';
        include '../../facturacion/FRONT/vendor/SpreadsheetReader.php';
    } else {
        die('Error: No se encontró la librería SpreadsheetReader. Por favor, verifique que el vendor esté instalado.');
    }
}

/**
 * @abstract Permite realizar el registro y control de IMEI de teléfonos
 * @author Sistema
 * @version 1.0
 * Fecha de creación: 2025-01-XX
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_imei.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Imei($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Imei;

$hoy = date("Y-m-d");
$hora = date("H:i:s");

// Obtener sucursal por defecto
if (!isset($Ses_Suc_Cod) || empty($Ses_Suc_Cod)) {
    require_once('../../facturacion/LOGICA/fac_log_factura.php');
    $obBD_conexion_suc = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_con_suc = new Class_Log_Datos_facturaVenta;
    $sucursales = $obBD_con_suc->getArrayConsulta(17, $Ses_Emp_Cod, $obBD_conexion_suc);
    $Ses_Suc_Cod = !empty($sucursales) ? $sucursales[0]['Suc_Cod'] : 1;
}

// Búsqueda de productos para el modal
if (isset($searchPro)) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;
    $op_opciones = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'd';
    $search = '';

    // 🔑 Detectar correctamente el valor buscado
    switch ($op_opciones) {
        case 'm': // Marca
            if (isset($_GET['search']) && $_GET['search'] !== 'false') {
                $search = trim($_GET['search']);
            }
            break;

        case 'd': // Descripción
            if (isset($_GET['search']) && $_GET['search'] !== 'false') {
                $search = trim($_GET['search']);
            }
            break;
    }
    
    // Parámetros: Suc_Cod * Emp_Cod * search * op_opciones
    $params = $Ses_Suc_Cod . '*' . $Ses_Emp_Cod . '*' . $search . '*' . $op_opciones;    
    
    // Contar usando el caso 13
    $contar = $obBD_con1->getRowConsulta(13, $params, $obBD_conexion);
    $total = isset($contar['total']) ? intval($contar['total']) : 0;
    
    // Calcular paginación
    $pagination = pages($total, $page, $rows);
    $responce = $pagination['data'];
    
    // Obtener datos usando el caso 12 con límites
    if ($total > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(12, $params . '*' . $pagination['limits'], $obBD_conexion);
    } else {
        $responce['rows'] = array();
    }
    
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

// Listar productos para el grid principal
if (isset($listProductosGrid)) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 100;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Parámetros: Suc_Cod * Emp_Cod * search
    $params = $Ses_Suc_Cod . '*' . $Ses_Emp_Cod . '*' . $search;
    
    // Contar usando el caso 14
    $contar = $obBD_con1->getRowConsulta(14, $params, $obBD_conexion);
    $total = isset($contar['total']) ? intval($contar['total']) : 0;
    
    // Calcular paginación
    $pagination = pages($total, $page, $rows);
    $responce = $pagination['data'];
    
    // Obtener datos usando el caso 10 con límites
    if ($total > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(10, $params . '*' . $pagination['limits'], $obBD_conexion);
    } else {
        $responce['rows'] = array();
    }
    
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

// Listar IMEI de un producto para vista previa
if (isset($listImeiProducto)) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 100;
    $Pro_Cod = isset($_GET['Pro_Cod']) ? intval($_GET['Pro_Cod']) : (isset($_POST['Pro_Cod']) ? intval($_POST['Pro_Cod']) : 0);
    
    if ($Pro_Cod > 0) {
        // Obtener todos los IMEI del producto
        $imeis = $obBD_con1->getArrayConsulta(11, $Pro_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
        
        // Formatear respuesta para jqGrid
        $total = count($imeis);
        $total_pages = ($total > 0 && $rows > 0) ? ceil($total / $rows) : 0;
        
        // Aplicar paginación
        $start = ($page - 1) * $rows;
        $imeis_paginados = array_slice($imeis, $start, $rows);
        
        $response = array(
            'page' => $page,
            'total' => $total_pages,
            'records' => $total,
            'rows' => $imeis_paginados ? $imeis_paginados : array()
        );
        
        utf8_encode_deep($response);
        echo json_encode($response);
    } else {
        // Si no hay Pro_Cod, retornar array vacío
        echo json_encode(array('page' => 1, 'total' => 0, 'records' => 0, 'rows' => array()));
    }
    exit();
}

// Listar IMEI
if (isset($listImei)) {
    $Pro_Cod = isset($Pro_Cod) ? $Pro_Cod : '';
    $Ime_Num = isset($Ime_Num) ? $Ime_Num : '';
    $response = $obBD_con1->getArrayConsulta(4, $Ses_Suc_Cod . '*' . $Ses_Emp_Cod . '*' . $Pro_Cod . '*' . $Ime_Num, $obBD_conexion, $page, $rows);
    $obBD_con1->echoJson($response);
    exit();
}

// Verificar si un IMEI ya existe
if (isset($verificarImei)) {
    $Ime_Num = isset($_GET['Ime_Num']) ? trim($_GET['Ime_Num']) : (isset($_POST['Ime_Num']) ? trim($_POST['Ime_Num']) : '');
    if (!empty($Ime_Num)) {
        $existe = $obBD_con1->getRowConsulta(6, $Ime_Num . '*' . $Ses_Suc_Cod, $obBD_conexion);
        $resp = array('existe' => ($existe['total'] > 0));
    } else {
        $resp = array('existe' => false, 'error' => 'IMEI no proporcionado');
    }
    echo json_encode($resp);
    exit();
}

// Obtener información de producto para el modal
if (isset($ajaxProd)) {
    $prod = $obBD_con1->getRowConsulta(9, $Ses_Suc_Cod . '*' . $Pro_Cod, $obBD_conexion);
    if ($prod) {
        $resp = array('success' => true, 'prod' => $prod);
    } else {
        $resp = array('success' => false, 'message' => 'Producto no encontrado');
    }
    echo json_encode($resp);
    exit();
}

// Guardar IMEI
if (isset($saveImei)) {
    $data = filter_input_array(INPUT_POST);
    // Estado siempre será 'A' por defecto
    $data['Ime_Est'] = 'A';
    // Tipo por defecto 'P' (Pendiente)
    if (empty($data['Ime_Tip'])) {
        $data['Ime_Tip'] = 'P';
    }
    // Usuario y sucursal
    $data['Usu_Cod'] = $Ses_Usu_Cod;
    $data['Suc_Cod'] = $Ses_Suc_Cod;
    
    // Validar que el IMEI no exista (solo para nuevos IMEI, no para actualizaciones)
    if (empty($data['Ime_Cod'])) {
        $existe = $obBD_con1->getRowConsulta(6, $data['Ime_Num'] . '*' . $Ses_Suc_Cod, $obBD_conexion);
        if ($existe['total'] > 0) {
            // Si ya existe, retornar como omitido (no es un error, simplemente se omite)
            $resp = array('success' => false, 'message' => 'El IMEI ya existe en el sistema', 'omitido' => true);
            echo json_encode($resp);
            exit();
        }
    }
    
    if (!empty($data['Ime_Cod'])) {
        // Actualizar
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(3, $data, $obBD_conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
        
        if ($obBD_con1->Error == 0) {
            $resp = array('success' => true, 'message' => 'IMEI actualizado correctamente', 'Ime_Cod' => $data['Ime_Cod']);
        } else {
            $resp = array('success' => false, 'message' => 'Error al actualizar el IMEI: ' . $obBD_con1->MsgError);
        }
    } else {
        // Insertar
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(2, $data, $obBD_conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
        
        if ($obBD_con1->Error == 0) {
            // Obtener el ID del IMEI insertado
            $Ime_Cod = 0;
            if (is_resource($obBD_conexion->conexion) || is_object($obBD_conexion->conexion)) {
                $Ime_Cod = mysqli_insert_id($obBD_conexion->conexion);
            }
            $resp = array('success' => true, 'message' => 'IMEI guardado correctamente', 'Ime_Cod' => $Ime_Cod);
        } else {
            $resp = array('success' => false, 'message' => 'Error al guardar el IMEI: ' . $obBD_con1->MsgError);
        }
    }
    echo json_encode($resp);
    exit();
}

// Obtener IMEI para editar
if (isset($getImei)) {
    $imei = $obBD_con1->getRowConsulta(5, $Ime_Cod, $obBD_conexion);
    echo json_encode($imei);
    exit();
}

// Eliminar IMEI
if (isset($deleteImei)) {
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(7, $Ime_Cod, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    
    if ($obBD_con1->Error == 0) {
        $resp = array('success' => true, 'message' => 'IMEI eliminado correctamente');
    } else {
        $resp = array('success' => false, 'message' => 'Error al eliminar el IMEI');
    }
    echo json_encode($resp);
    exit();
}

// Importar desde Excel
if (isset($_POST["import"])) {
    // $targetPath = 'uploads/' . $_FILES['file']['name'];
    // if (!file_exists('uploads')) {
    //     mkdir('uploads', 0777, true);
    // }
    // move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);

    // Usar ruta absoluta basada en el directorio del archivo
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Limpiar el nombre del archivo para evitar problemas de seguridad
    $fileName = basename($_FILES['file']['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Verificar que el archivo se haya subido correctamente
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $resp = array('success' => false, 'message' => 'Error al subir el archivo. Verifique los permisos del directorio uploads.');
        echo json_encode($resp);
        exit();
    }
    
    // Verificar que el archivo existe y es legible
    if (!file_exists($targetPath) || !is_readable($targetPath)) {
        $resp = array('success' => false, 'message' => 'El archivo no existe o no es legible: ' . $targetPath);
        echo json_encode($resp);
        exit();
    }
    
    try {
        $Reader = new SpreadsheetReader($targetPath);
        $sheetCount = count($Reader->sheets());
        $Reader->ChangeSheet(0);
        
        $contador = 0;
        $insertados = 0;
        $errores = array();
        
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        
        // Obtener Pro_Cod del formulario si está disponible
        $Pro_Cod_Form = isset($_POST['Pro_Cod']) ? trim($_POST['Pro_Cod']) : '';
        
        foreach ($Reader as $Row) {
            $contador++;
            
            // Saltar encabezado (primera fila)
            if ($contador == 1) {
                continue;
            }
            
            // Buscar IMEIs en cualquier columna (normalmente columna B, índice 1)
            $Imei_List = '';
            foreach ($Row as $cell) {
                if (!empty($cell)) {
                    $cell = trim($cell);
                    // Si la celda contiene comas, probablemente es la lista de IMEIs
                    if (strpos($cell, ',') !== false) {
                        $Imei_List = $cell;
                        break;
                    }
                }
            }
            
            // Si no se encontró en ninguna celda, intentar columna B (índice 1) o A (índice 0)
            if (empty($Imei_List)) {
                $Imei_List = isset($Row[1]) ? trim($Row[1]) : (isset($Row[0]) ? trim($Row[0]) : '');
            }
            
            // Si hay Pro_Cod en el formulario, usarlo; si no, intentar tomar del Excel (columna A)
            $Pro_Cod = !empty($Pro_Cod_Form) ? $Pro_Cod_Form : (isset($Row[0]) ? trim($Row[0]) : '');
            
            if (empty($Pro_Cod)) {
                $errores[] = "Fila $contador: Falta el código de producto. Asegúrese de seleccionar un producto antes de importar.";
                continue;
            }
            
            if (empty($Imei_List)) {
                // Si no hay IMEIs en esta fila, continuar con la siguiente
                continue;
            }
            
            // Separar IMEI por coma
            $imeis = explode(',', $Imei_List);
            $imeis = array_map('trim', $imeis);
            $imeis = array_filter($imeis); // Eliminar vacíos
            
            foreach ($imeis as $ime_num) {
                if (empty($ime_num)) continue;
                
                // Validar que el IMEI no exista
                $existe = $obBD_con1->getRowConsulta(6, $ime_num . '*' . $Ses_Suc_Cod, $obBD_conexion);
                if ($existe['total'] > 0) {
                    $errores[] = "Fila $contador: IMEI $ime_num ya existe";
                    continue;
                }
                
                $data = array(
                    'Pro_Cod' => $Pro_Cod,
                    'Ime_Num' => $ime_num,
                    'Usu_Cod' => $Ses_Usu_Cod,
                    'Suc_Cod' => $Ses_Suc_Cod,
                    'Ime_Tip' => 'P'
                );
                
                $obBD_con1->operacionobBD(2, $data, $obBD_conexion);
                if ($obBD_con1->Error == 0) {
                    $insertados++;
                } else {
                    $errores[] = "Fila $contador: Error al insertar IMEI $ime_num";
                }
            }
        }
        
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
        
        // Eliminar archivo temporal
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        
        $resp = array(
            'success' => true,
            'message' => "Se insertaron $insertados IMEI correctamente",
            'errores' => $errores
        );
        
    } catch (Exception $e) {
        $resp = array('success' => false, 'message' => 'Error al leer el archivo: ' . $e->getMessage());
    }
    
    echo json_encode($resp);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?php echo "Control de IMEI [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../VALIDACIONES/inv_val_imei.js?x=3"></script>
    <style>
        .imei-grid {
            margin-top: 20px;
        }
        .import-section {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        /* Ajustar tamaño del grid principal */
        #gridImei {
            width: 100% !important;
            height: 500px !important;
        }
        /* Ajustar tamaño del grid dentro del modal */
        #proDialog table[id^="grid"] {
            width: 100% !important;
            height: 450px !important;
        }
        /* Aumentar tamaño de fuente en los grids */
        .ui-jqgrid .ui-jqgrid-btable {
            font-size: 13px;
        }
        .ui-jqgrid .ui-jqgrid-htable th {
            font-size: 13px;
            padding: 8px;
        }
        .ui-jqgrid tr.jqgrow td {
            padding: 8px;
        }
        /* Ocultar botón de colapsar del grid */
        #gbox_gridProductos .ui-jqgrid-titlebar-close,
        #gbox_gridProductos .ui-jqgrid-titlebar .ui-jqgrid-titlebar-close {
            display: none !important;
        }
        /* Centrar el pager */
        #pagerProductos {
            text-align: center !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            flex-wrap: wrap !important;
        }
        #pagerProductos > * {
            margin: 0 5px !important;
        }
        #pagerProductos .ui-paging-info {
            text-align: center !important;
            display: inline-block !important;
            margin: 0 10px !important;
        }
        #pagerProductos .ui-pg-table {
            margin: 0 auto !important;
            display: inline-block !important;
        }
        #pagerProductos .ui-pg-table td {
            text-align: center !important;
        }
        #pagerProductos .ui-pg-selbox {
            margin: 0 5px !important;
        }
        /* Estilos para el input de búsqueda en el título del grid */
        #gbox_gridProductos .ui-jqgrid-titlebar {
            height: auto !important;
            width: 100% !important;
        }
        #gbox_gridProductos .ui-jqgrid-title {
            width: 100% !important;
            padding: 2px 2px !important;
            height: auto !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        #gbox_gridProductos .ui-jqgrid-title > span:first-child {
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            font-size: 15px !important;
        }
        #gbox_gridProductos .ui-jqgrid-title > div {
            flex-shrink: 0 !important;
            margin-left: auto !important;
        }
        #searchProductos {
            border: 1px solid #ccc;
            border-radius: 4px;
            transition: border-color 0.3s ease;
        }
        #searchProductos:focus {
            border-color: #66afe9;
            outline: 0;
            box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6);
        }
    </style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Control de IMEI de Teléfonos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <!-- Formulario de IMEI -->
            <form id="formImei" class="form-horizontal normal" action="javascript:saveImeiData()">
                <input type="hidden" id="Ime_Cod" name="Ime_Cod" value="" />
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del IMEI:</legend>
                            <!-- Fila 1: Solo Producto -->
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm required" for="producto">Producto:</label>
                                <div class="col-sm-8">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="Pro_Cod" id="Pro_Cod" value="" style="display: none" />
                                        <input id="producto" type="text" class="form-control input-sm" placeholder="Seleccione un Producto ..." required readonly />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success" onclick="$('#proDialog').dialog('open');" type="button">
                                                <span class="glyphicon glyphicon-search" title="Buscar Producto"></span>
                                            </button>
                                            <button class="btn btn-danger" onclick="limpiarProducto();" type="button">
                                                <span class="glyphicon glyphicon-trash" title="Limpiar"></span>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Fila 2: Nombre, Marca y Stock -->
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm">Nombre:</label>
                                <div class="col-sm-3">
                                    <input type="text" id="Pro_Nom_Info" class="form-control input-sm" readonly />
                                </div>
                                <label class="col-sm-1 control-label label-sm" style="margin-left: -60px;" >Marca:</label>
                                <div class="col-sm-3">
                                    <input type="text" id="Mar_Des_Info" class="form-control input-sm" readonly />
                                </div>
                                <label class="col-sm-1 control-label label-sm" style="margin-left: -60px;">Stock:</label>
                                <div class="col-sm-2">
                                    <input type="text" id="Stk_Can_Info" class="form-control input-sm" style="width: 90px;" readonly />
                                    <small class="help-block text-left" style="margin-top: 5px;">Total de IMEI: <span id="totalImeiCount">0</span></small>
                                </div>
                            </div>
                            <!-- Fila 3: Botón Agregar IMEI centrado -->
                            <div class="form-group">
                                <div class="col-sm-12 text-center" style="margin-top: 10px;">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="$('#imeiModal').dialog('open');" title="Agregar IMEI">
                                        <span class="glyphicon glyphicon-plus"></span> Agregar IMEI
                                    </button>
                                    <input type="hidden" id="Ime_Num" name="Ime_Num" value="" />
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

            </form>


            <!-- Grid de Productos -->
            <div class="imei-grid" style="width: 100%;">
                <h4 class="Titulos2">Lista de Productos con IMEI</h4>
                <!-- <div style="width: 100%; height: 500px;"> -->
                    <table id="gridProductos"></table>
                    <div id="pagerProductos"></div>
                <!-- </div> -->
            </div>
        </div>
    </div>

    <!-- Modal para buscar productos -->
    <div id="proDialog" title="Búsqueda de Productos">
        <form class="form-horizontal normal">
        </form>
    </div>

    <!-- Modal de vista previa de IMEI -->
    <div id="imeiPreviewDialog" title="Vista Previa de IMEI">
        <h4 style="margin-top: 0;"></h4>
        <div style="width: 100%;">
            <table id="gridImeiPreview"></table>
            <div id="pagerImeiPreview"></div>
        </div>
    </div>
    
    <!-- Modal para agregar IMEI -->
    <div id="imeiModal" title="Agregar IMEI">
        <div id="imeiTabs">
            <ul>
                <li><a href="#tabManual"><span class="glyphicon glyphicon-pencil"></span> Ingreso Manual</a></li>
                <li><a href="#tabExcel"><span class="glyphicon glyphicon-file"></span> Importar Excel</a></li>
            </ul>
            
            <!-- Tab Ingreso Manual -->
            <div id="tabManual">
                <div class="form-group">
                    <label>Ingrese los IMEI (uno por línea o separados por coma):</label>
                    <textarea id="imeiManualInput" class="form-control" rows="10" 
                            placeholder="Ejemplo:&#10;123456789012345&#10;987654321098765&#10;&#10;O separados por coma:&#10;123456789012345,987654321098765"></textarea>
                    <small class="help-block">Cada IMEI debe tener 15 dígitos. Puede ingresar múltiples IMEI separados por coma o uno por línea.</small>
                </div>
                <div class="form-group">
                    <button type="button" id="btnAgregarImei" class="btn btn-primary" onclick="procesarImeiManual()">
                        <span class="glyphicon glyphicon-ok"></span> Agregar IMEI
                    </button>
                    <button type="button" class="btn btn-danger" onclick="limpiarImeiManual()">
                        <span class="glyphicon glyphicon-refresh"></span> Limpiar
                    </button>
                </div>
            </div>
            
            <!-- Tab Importar Excel -->
            <div id="tabExcel">
                <div class="form-group">
                    <label>Formato del archivo Excel:</label>
                    <ul>
                        <li>Columna A: Código del Producto (Pro_Cod) - <strong>Opcional si ya seleccionó el producto</strong></li>
                        <li>Columna B: Lista de IMEI separados por coma (ejemplo: 123456789012345,987654321098765)</li>
                    </ul>
                </div>
                <form id="formImport" enctype="multipart/form-data" method="post" action="">
                    <input type="hidden" name="import" value="1" />
                    <div class="form-group">
                        <label class="control-label">Seleccionar archivo Excel:</label>
                        <input type="file" name="file" id="fileExcel" accept=".xls,.xlsx" class="form-control" />
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-info" onclick="importarExcelDesdeModal()">
                            <span class="glyphicon glyphicon-upload"></span> Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
            <label>IMEI agregados en esta sesión:</label>
            <div id="imeiListContainer" class="well" style="max-height: 150px; overflow-y: auto;">
                <ul id="imeiListItems" class="list-unstyled" style="margin: 0;"></ul>
            </div>
            <div style="margin-top: 10px;">
                <button type="button" id="btnGuardarImei" class="btn btn-success" onclick="guardarImeiDelModal()">
                    <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Todos los IMEI
                </button>
                <button type="button" class="btn btn-danger" onclick="limpiarListaImei()">
                    <span class="glyphicon glyphicon-trash"></span> Limpiar Lista
                </button>
            </div>
        </div>
    </div>

    <?php
        // Cerrado y liberacion de las conexiones
        $obBD_con1->liberar();
        $obBD_conexion->cerrar();
    ?>
</BODY>
</HTML>
