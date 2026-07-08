<?php

/**
 * Permite copiar proveedores de otras empresas a la empresa actual
 *
 * @author Sistema EXA
 * @version 1.0
 * Fecha de actualización: 2025-01-XX
 *
 * @package tesoreria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../adquisiciones/LOGICA/adq_log_provee.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../DATA/MysqlConexion.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Prv
 */
$obBD_conexion = new Class_Log_Conexion_Prv($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Prv
 */
$obBD_con1 = new Class_Log_Datos_Prv;

// Ajax para cargar bases de datos disponibles
if (isset($loadBasesDatos)) {
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    // Obtener bases de datos únicas desde la tabla data
    $sql = "SELECT DISTINCT Dat_Dis FROM exa_master.data WHERE Dat_Dis IS NOT NULL AND Dat_Dis != '' ORDER BY Dat_Dis";
    $bases_datos_raw = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    $bases_datos = array();
    foreach ($bases_datos_raw as $base) {
        $bases_datos[] = array(
            'Dat_Dis' => $base['Dat_Dis'],
            'Emp_Nom' => strtoupper($base['Dat_Dis'])
        );
    }
    
    echo json_encode(array('success'=>true, 'bases'=>$bases_datos));
    exit();
}

// Ajax para cargar empresas de una base de datos específica
if (isset($loadEmpresasByDB)) {
    if (empty($Dat_Dis)) {
        echo json_encode(array('success'=>false, 'message'=>'No se ha seleccionado una base de datos!'));
        exit();
    }
    
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    // Obtener empresas de la base de datos seleccionada
    $sql = "SELECT DISTINCT E.Emp_Cod, E.Emp_Nom, E.Emp_Cor
            FROM `" . addslashes($Dat_Dis) . "`.empresas E
            INNER JOIN `" . addslashes($Dat_Dis) . "`.sucursal S ON E.Emp_Cod = S.Emp_Cod
            INNER JOIN exa_master.access A ON S.Suc_Cod = A.Suc_Cod
            WHERE E.Emp_Est='A' AND S.Suc_Est='A' AND A.Acc_Est='A' 
            AND A.Acc_Usr='" . mysqli_real_escape_string($obBD_conexion_master->conexion, trim($Ses_Usu_Ced)) . "'
            AND E.Emp_Cod != " . intval($_SESSION['Ses_Emp_Cod']) . "
            ORDER BY E.Emp_Nom";
    
    $empresas = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    echo json_encode(array('success'=>true, 'empresas'=>$empresas));
    exit();
}

// Ajax para obtener proveedores de otra empresa
if (isset($proveedoresOrigenAjax)) {
    // Necesitamos la base de datos para hacer la consulta
    if (!empty($_GET['Dat_Dis'])) {
        // Crear conexión a la base de datos origen
        $obBD_conexion_origen = new Class_Log_Conexion_Prv($_GET['Dat_Dis']);
        $obBD_con1->getPageGridJson(89, $_GET, $obBD_conexion_origen, true);
    } else {
        // Si no se especifica, usar la base de datos actual
        $obBD_con1->getPageGridJson(89, $_GET, $obBD_conexion, true);
    }
}

// Ajax para copiar proveedores
if (isset($copiarProveedoresAjax)) {
    // Configurar headers para JSON y evitar errores
    header('Content-Type: application/json; charset=utf-8');
    
    // Iniciar buffer de salida para capturar cualquier output inesperado
    ob_start();
    
    $response = array('success' => false, 'message' => '', 'copiados' => 0, 'existentes' => 0, 'errores' => 0);
    
    try {
        if (!isset($_POST['empresa_origen']) || empty($_POST['empresa_origen'])) {
            $response['message'] = 'Debe seleccionar una empresa origen';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        if (!isset($_POST['Dat_Dis']) || empty($_POST['Dat_Dis'])) {
            $response['message'] = 'Debe seleccionar una base de datos';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        if (!isset($_POST['proveedores']) || empty($_POST['proveedores'])) {
            $response['message'] = 'Debe seleccionar al menos un proveedor';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        $empresa_origen = intval($_POST['empresa_origen']);
        $empresa_destino = intval($_SESSION['Ses_Emp_Cod']);
        $dat_dis_origen = $_POST['Dat_Dis'];
        
        // Validar y decodificar JSON de proveedores
        if (!isset($_POST['proveedores']) || empty($_POST['proveedores'])) {
            $response['message'] = 'No se recibieron datos de proveedores';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        // Limpiar y validar el JSON antes de decodificar
        $proveedores_json = trim($_POST['proveedores']);
        $proveedores_ids = json_decode($proveedores_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Función compatible con versiones anteriores de PHP
            $error_msg = 'Error desconocido en JSON';
            if (function_exists('json_last_error_msg')) {
                $error_msg = json_last_error_msg();
            } else {
                // Mapear códigos de error para versiones anteriores
                $error_codes = array(
                    JSON_ERROR_NONE => 'No hay error',
                    JSON_ERROR_DEPTH => 'Profundidad máxima de pila excedida',
                    JSON_ERROR_STATE_MISMATCH => 'Desbordamiento de buffer o modos no coinciden',
                    JSON_ERROR_CTRL_CHAR => 'Carácter de control encontrado, posiblemente codificado incorrectamente',
                    JSON_ERROR_SYNTAX => 'Error de sintaxis JSON',
                    JSON_ERROR_UTF8 => 'Caracteres UTF-8 mal formados, posiblemente codificados incorrectamente'
                );
                $error_code = json_last_error();
                $error_msg = isset($error_codes[$error_code]) ? $error_codes[$error_code] : 'Error desconocido (código: ' . $error_code . ')';
            }
            $response['message'] = 'Error al procesar los datos de proveedores: ' . $error_msg . '. JSON recibido: ' . substr($proveedores_json, 0, 100);
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        // Validar que sea un array
        if (!is_array($proveedores_ids)) {
            $response['message'] = 'Los datos de proveedores no son válidos. Se esperaba un array.';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        if ($empresa_origen == $empresa_destino) {
            $response['message'] = 'No puede copiar proveedores de la misma empresa';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
    
        // Crear conexión a la base de datos origen para leer los proveedores
        $obBD_conexion_origen = new Class_Log_Conexion_Prv($dat_dis_origen);
        if (!$obBD_conexion_origen->conexion) {
            throw new Exception("No se pudo conectar a la base de datos de origen: " . $obBD_conexion_origen->Error);
        }
        
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        
        $copiados = 0;
        $existentes = 0;
        $errores = 0;
        $proveedores_existentes = array(); // Para almacenar los nombres de proveedores que ya existen
        
        foreach ($proveedores_ids as $prv_cod_origen) {
        try {
            // Obtener datos del proveedor origen desde la base de datos origen
            $sql_proveedor_origen = "SELECT proveedore.*, persona.* 
                                   FROM proveedore 
                                   INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod 
                                   WHERE proveedore.Prv_Cod = " . intval($prv_cod_origen) . " 
                                   AND proveedore.Emp_Cod = " . $empresa_origen;
            
            $result = mysqli_query($obBD_conexion_origen->conexion, $sql_proveedor_origen);
            if (!$result || mysqli_num_rows($result) == 0) {
                $errores++;
                continue;
            }
            
            $proveedor_data = mysqli_fetch_assoc($result);
            $prs_cod = $proveedor_data['Prs_Cod'];
            $nombre_proveedor = trim($proveedor_data['Prs_Ape'] . ' ' . $proveedor_data['Prs_Nom']);
            $cedula_proveedor = $proveedor_data['Prs_Ced'];
            
            // Verificar si la persona ya existe
            $sql_existe_persona = "SELECT Prs_Cod FROM persona WHERE Prs_Ced = '" . 
                                  mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Ced']) . "'";
            $result_persona = mysqli_query($obBD_conexion->conexion, $sql_existe_persona);
            
            if (mysqli_num_rows($result_persona) > 0) {
                $persona_existente = mysqli_fetch_assoc($result_persona);
                $prs_cod_destino = $persona_existente['Prs_Cod'];
            } else {
                // Insertar persona si no existe
                // Manejar campos opcionales que pueden ser NULL
                $prs_sex = !empty($proveedor_data['Prs_Sex']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Sex']) . "'" : "NULL";
                $prs_dir = !empty($proveedor_data['Prs_Dir']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Dir']) . "'" : "NULL";
                $prs_tel = !empty($proveedor_data['Prs_Tel']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Tel']) . "'" : "NULL";
                $prs_te2 = !empty($proveedor_data['Prs_Te2']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Te2']) . "'" : "NULL";
                $prs_cel = !empty($proveedor_data['Prs_Cel']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Cel']) . "'" : "NULL";
                $prs_cor = !empty($proveedor_data['Prs_Cor']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Cor']) . "'" : "NULL";
                $ciu_cod = !empty($proveedor_data['Ciu_Cod']) ? intval($proveedor_data['Ciu_Cod']) : "NULL";
                $ide_cod = !empty($proveedor_data['Ide_Cod']) ? intval($proveedor_data['Ide_Cod']) : "NULL";
                
                $sql_insert_persona = "INSERT INTO persona (Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, Prs_Dir, Prs_Tel, Prs_Te2, Prs_Cel, Prs_Cor, Ciu_Cod, Ide_Cod, Prs_Est) 
                                       VALUES (
                                           '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Ced']) . "',
                                           '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Nom']) . "',
                                           '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prs_Ape']) . "',
                                           " . $prs_sex . ",
                                           " . $prs_dir . ",
                                           " . $prs_tel . ",
                                           " . $prs_te2 . ",
                                           " . $prs_cel . ",
                                           " . $prs_cor . ",
                                           " . $ciu_cod . ",
                                           " . $ide_cod . ",
                                           'A'
                                       )";
                
                if (!mysqli_query($obBD_conexion->conexion, $sql_insert_persona)) {
                    $error_msg = mysqli_error($obBD_conexion->conexion);
                    error_log("Error al insertar persona: " . $error_msg . " - SQL: " . $sql_insert_persona);
                    $errores++;
                    continue;
                }
                
                $prs_cod_destino = mysqli_insert_id($obBD_conexion->conexion);
            }
            
            // Verificar si el proveedor ya existe en la empresa destino
            $sql_existe_proveedor = "SELECT Prv_Cod FROM proveedore WHERE Prs_Cod = " . $prs_cod_destino . " AND Emp_Cod = " . $empresa_destino;
            $result_proveedor = mysqli_query($obBD_conexion->conexion, $sql_existe_proveedor);
            
            if (mysqli_num_rows($result_proveedor) > 0) {
                $existentes++;
                // Guardar información del proveedor que ya existe
                $proveedores_existentes[] = $nombre_proveedor . ' (Cédula: ' . $cedula_proveedor . ')';
                continue;
            }
            
            // Insertar proveedor en la empresa destino
            // Usar campos mínimos requeridos
            $prv_tic = !empty($proveedor_data['Prv_Tic']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Tic']) . "'" : "NULL";
            $prv_con = !empty($proveedor_data['Prv_Con']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Con']) . "'" : "NULL";
            $prv_com = !empty($proveedor_data['Prv_Com']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Com']) . "'" : "NULL";
            $prv_esp = !empty($proveedor_data['Prv_Esp']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Esp']) . "'" : "NULL";
            
            // Insertar con campos mínimos primero
            $sql_insert_proveedor = "INSERT INTO proveedore (Prs_Cod, Emp_Cod, Prv_Tic, Prv_Con, Prv_Com, Prv_Esp, Prv_Est) 
                                   VALUES (
                                       " . $prs_cod_destino . ",
                                       " . $empresa_destino . ",
                                       " . $prv_tic . ",
                                       " . $prv_con . ",
                                       " . $prv_com . ",
                                       " . $prv_esp . ",
                                       'A'
                                   )";
            
            if (mysqli_query($obBD_conexion->conexion, $sql_insert_proveedor)) {
                $copiados++;
                
                // Si hay campos adicionales y el proveedor se insertó correctamente, actualizarlos
                $prv_cod_nuevo = mysqli_insert_id($obBD_conexion->conexion);
                $campos_update = array();
                
                if (!empty($proveedor_data['Prv_Reg'])) {
                    $campos_update[] = "Prv_Reg = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Reg']) . "'";
                }
                if (!empty($proveedor_data['Prv_Ris'])) {
                    $campos_update[] = "Prv_Ris = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Ris']) . "'";
                }
                if (!empty($proveedor_data['Prv_Gct'])) {
                    $campos_update[] = "Prv_Gct = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Gct']) . "'";
                }
                if (!empty($proveedor_data['Prv_Rim_Emp'])) {
                    $campos_update[] = "Prv_Rim_Emp = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Rim_Emp']) . "'";
                }
                if (!empty($proveedor_data['Prv_Rim_Np'])) {
                    $campos_update[] = "Prv_Rim_Np = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Rim_Np']) . "'";
                }
                if (!empty($proveedor_data['Prv_Ag_Ret'])) {
                    $campos_update[] = "Prv_Ag_Ret = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Ag_Ret']) . "'";
                }
                if (!empty($proveedor_data['Prv_Tel'])) {
                    $campos_update[] = "Prv_Tel = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Tel']) . "'";
                }
                if (!empty($proveedor_data['Prv_Cor'])) {
                    $campos_update[] = "Prv_Cor = '" . mysqli_real_escape_string($obBD_conexion->conexion, $proveedor_data['Prv_Cor']) . "'";
                }
                
                // Actualizar campos adicionales si existen
                if (!empty($campos_update) && $prv_cod_nuevo > 0) {
                    $sql_update_proveedor = "UPDATE proveedore SET " . implode(", ", $campos_update) . " WHERE Prv_Cod = " . $prv_cod_nuevo;
                    mysqli_query($obBD_conexion->conexion, $sql_update_proveedor);
                }
            } else {
                $error_msg = mysqli_error($obBD_conexion->conexion);
                error_log("Error al insertar proveedor: " . $error_msg . " - SQL: " . $sql_insert_proveedor);
                $errores++;
            }
            
        } catch (Exception $e) {
            error_log("Excepción al copiar proveedor: " . $e->getMessage());
            $errores++;
        }
        }
        
        if ($errores == 0) {
            $obBD_con1->fin_transaccion($obBD_conexion->conexion);
            $response['success'] = true;
            
            // Construir mensaje detallado
            $mensaje = "";
            if ($copiados > 0) {
                $mensaje = "Se copiaron $copiados proveedor(es) correctamente a la empresa actual.";
            }
            
            if ($existentes > 0) {
                if ($copiados > 0) {
                    $mensaje .= "\n\n";
                }
                $mensaje .= "$existentes proveedor(es) ya estaban registrados en la empresa actual y no se copiaron para evitar duplicados.";
                
                // Si hay pocos proveedores existentes, mostrar sus nombres
                if (count($proveedores_existentes) <= 10) {
                    $mensaje .= "\n\nProveedores que ya existían:\n• " . implode("\n• ", $proveedores_existentes);
                } else {
                    $mensaje .= "\n\n(Se omitieron " . count($proveedores_existentes) . " proveedores que ya existían)";
                }
            }
            
            if ($copiados == 0 && $existentes > 0) {
                $mensaje = "Ningún proveedor fue copiado. Todos los proveedores seleccionados ya estaban registrados en la empresa actual.";
                if (count($proveedores_existentes) <= 10) {
                    $mensaje .= "\n\nProveedores que ya existían:\n• " . implode("\n• ", $proveedores_existentes);
                }
            }
            
            $response['message'] = $mensaje;
        } else {
            $obBD_con1->rollBack($obBD_conexion->conexion);
            $mensaje = "Error al copiar proveedores.\n";
            if ($copiados > 0) {
                $mensaje .= "Se copiaron $copiados proveedor(es) antes del error.\n";
            }
            if ($existentes > 0) {
                $mensaje .= "$existentes proveedor(es) ya existían.\n";
            }
            $mensaje .= "Ocurrieron $errores error(es). La operación fue revertida.";
            $response['message'] = $mensaje;
        }
        
        $response['copiados'] = $copiados;
        $response['existentes'] = $existentes;
        $response['errores'] = $errores;
        $response['proveedores_existentes'] = $proveedores_existentes;
        
        // Cerrar conexión de origen
        if (isset($obBD_conexion_origen)) {
            $obBD_conexion_origen->cerrar();
        }
        
    } catch (Exception $e) {
        // Si hay algún error no capturado
        $obBD_con1->rollBack($obBD_conexion->conexion);
        $response['success'] = false;
        $response['message'] = 'Error inesperado: ' . $e->getMessage();
        $response['errores'] = $errores;
        error_log("Error general al copiar proveedores: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
        
        // Cerrar conexión de origen en caso de error
        if (isset($obBD_conexion_origen)) {
            $obBD_conexion_origen->cerrar();
        }
    }
    
    // Limpiar cualquier output inesperado y enviar JSON
    ob_end_clean();
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE HTML>
<HTML>

<HEAD>
    <TITLE><?Php echo "Copiar Proveedores [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</HEAD>

<BODY>

    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Copiar Proveedores de Otra Empresa</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="lista" class="row">
                <div class="col-md-12">
                    <form id="frm_bus" name="frm_bus" class="form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Seleccionar Empresa Origen</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Base de Datos:</label>
                                <div class="col-sm-4">
                                    <select id="selBaseDatos" name="selBaseDatos" class="form-control input-xs">
                                        <option value="">-- Seleccione una base de datos --</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="control-label label-xs">Empresa Destino (Actual):</label>
                                    <div class="form-control-static"><?php echo htmlspecialchars($_SESSION['Ses_Emp_Nom']); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Empresa Origen:</label>
                                <div class="col-sm-6">
                                    <select id="empresa_origen" name="empresa_origen" class="form-control input-xs" disabled>
                                        <option value="">-- Primero seleccione una base de datos --</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="exa-fieldset" id="fieldset_proveedores" style="display:none;">
                            <legend class="Titulos2">Búsqueda de Proveedores</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                <div class="col-sm-4 radioset">
                                    <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" /><label for="rad_ba1">&nbsp;&nbsp;Cédula/R.U.C.&nbsp;&nbsp;</label>
                                    <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" /><label for="rad_ba2">&nbsp;&nbsp;Proveedor&nbsp;&nbsp;</label>
                                </div>
                                <div class="col-sm-4 radioset">
                                    <input id="rad_bb1" name="est_opciones" type="radio" value="a" checked="" onclick="setfocus(this.form.search)" /><label for="rad_bb1">Activo</label>
                                    <input id="rad_bb2" name="est_opciones" type="radio" value="i" onclick="setfocus(this.form.search)" /><label for="rad_bb2">Inactivo</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Búsqueda:</label>
                                <div class="col-sm-5">
                                    <div class="input-group">
                                        <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13) cargarProveedores()" class="form-control input-xs" placeholder="Ingrese índice de búsqueda" autofocus="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Proveedor" onclick="cargarProveedores()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    
                    <div style="min-height:300px; margin-top: 20px;" id="div_grid">
                        <table id="Lis_Prv_Origen"></table>
                        <div id="Pag_Prv_Origen"></div>
                        <div style="padding-top: 10px; padding-bottom: 0px;">
                            <button type="button" onclick="copiarProveedores()" class="btn btn-primary btn-sm" title="Copiar proveedores seleccionados" id="btn_copiar" disabled>
                                <i class="glyphicon glyphicon-copy"></i> <span>Copiar Proveedores Seleccionados</span>
                            </button>
                            <button type="button" onclick="seleccionarTodos()" class="btn btn-info btn-sm" title="Seleccionar todos">
                                <i class="glyphicon glyphicon-check"></i> <span>Seleccionar Todos</span>
                            </button>
                            <button type="button" onclick="deseleccionarTodos()" class="btn btn-info btn-sm" title="Deseleccionar todos">
                                <i class="glyphicon glyphicon-unchecked"></i> <span>Deseleccionar Todos</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var baseDatosSeleccionada = null;
        var empresaOrigenSeleccionada = null;
        var proveedoresSeleccionados = [];

        // Cuando se selecciona una empresa (usando Select2)
        $('#empresa_origen').on('select2:select', function (e) {
            var empresa_origen = $(this).val();
            if (empresa_origen) {
                empresaOrigenSeleccionada = empresa_origen;
                cargarProveedores();
            } else {
                $('#fieldset_proveedores').hide();
                $('#btn_copiar').prop('disabled', true);
            }
        });

        // Cuando se deselecciona la empresa
        $('#empresa_origen').on('select2:clear', function (e) {
            empresaOrigenSeleccionada = null;
            $('#fieldset_proveedores').hide();
            $('#btn_copiar').prop('disabled', true);
        });

        function cargarProveedores() {
            var empresa_origen = $('#empresa_origen').val();
            if (!empresa_origen) {
                alert('Debe seleccionar una empresa origen');
                return;
            }
            
            if (!baseDatosSeleccionada) {
                alert('Debe seleccionar una base de datos');
                return;
            }
            
            empresaOrigenSeleccionada = empresa_origen;
            $('#fieldset_proveedores').show();
            $('#btn_copiar').prop('disabled', false);
            
            var postData = $("#frm_bus").getData("proveedoresOrigenAjax");
            postData['Emp_Cod_Origen'] = empresa_origen;
            postData['Dat_Dis'] = baseDatosSeleccionada;
            
            // Recargar el grid con los nuevos datos, cambiando a tipo JSON
            $("#Lis_Prv_Origen").jqGrid('setGridParam', {
                datatype: 'json',
                postData: postData
            }).trigger('reloadGrid');
        }

        function seleccionarTodos() {
            var ids = $("#Lis_Prv_Origen").jqGrid('getDataIDs');
            for (var i = 0; i < ids.length; i++) {
                $("#Lis_Prv_Origen").jqGrid('setSelection', ids[i], true);
            }
        }

        function deseleccionarTodos() {
            $("#Lis_Prv_Origen").jqGrid('resetSelection');
        }

        function copiarProveedores() {
            var empresa_origen = $('#empresa_origen').val();
            if (!empresa_origen) {
                alert('Debe seleccionar una empresa origen');
                return;
            }
            
            var ids = $("#Lis_Prv_Origen").jqGrid('getGridParam', 'selarrrow');
            if (!ids || ids.length == 0) {
                alert('Debe seleccionar al menos un proveedor para copiar');
                return;
            }
            
            // Asegurar que ids es un array válido
            if (!Array.isArray(ids)) {
                ids = [ids];
            }
            
            // Convertir todos los IDs a números para asegurar que sean válidos
            var ids_validos = [];
            for (var i = 0; i < ids.length; i++) {
                var id = parseInt(ids[i]);
                if (!isNaN(id) && id > 0) {
                    ids_validos.push(id);
                }
            }
            
            if (ids_validos.length == 0) {
                alert('Error: No se pudieron obtener IDs válidos de los proveedores seleccionados');
                return;
            }
            
            if (!confirm('¿Está seguro de copiar ' + ids_validos.length + ' proveedor(es) a la empresa actual?')) {
                return;
            }
            
            $('#btn_copiar').prop('disabled', true).html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Copiando...');
            
            // Convertir a JSON de forma segura
            var proveedoresJson;
            try {
                proveedoresJson = JSON.stringify(ids_validos);
            } catch (e) {
                alert('Error al preparar los datos: ' + e.message);
                $('#btn_copiar').prop('disabled', false).html('<i class="glyphicon glyphicon-copy"></i> <span>Copiar Proveedores Seleccionados</span>');
                return;
            }
            
            $.ajax({
                url: '?copiarProveedoresAjax=1',
                type: 'POST',
                data: {
                    empresa_origen: empresa_origen,
                    Dat_Dis: baseDatosSeleccionada,
                    proveedores: proveedoresJson
                },
                dataType: 'json',
                success: function(response) {
                    $('#btn_copiar').prop('disabled', false).html('<i class="glyphicon glyphicon-copy"></i> <span>Copiar Proveedores Seleccionados</span>');
                    
                    if (response && response.message !== undefined) {
                        var mensaje = response.message;
                        if (response.success) {
                            // Mostrar mensaje con saltos de línea
                            alert(mensaje.replace(/\n/g, '\n'));
                            cargarProveedores(); // Recargar grid
                        } else {
                            alert('Error: ' + mensaje.replace(/\n/g, '\n'));
                        }
                    } else {
                        console.error('Respuesta inesperada:', response);
                        alert('Error: Respuesta inesperada del servidor. Revise la consola para más detalles.');
                    }
                },
                error: function(xhr, status, error) {
                    $('#btn_copiar').prop('disabled', false).html('<i class="glyphicon glyphicon-copy"></i> <span>Copiar Proveedores Seleccionados</span>');
                    
                    console.error('Error AJAX:', status, error);
                    console.error('Status Code:', xhr.status);
                    console.error('Response Text:', xhr.responseText);
                    
                    // Intentar parsear como JSON si es posible
                    var errorMsg = 'Error al copiar los proveedores.';
                    try {
                        var jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse.message) {
                            errorMsg = jsonResponse.message;
                        }
                    } catch (e) {
                        // Si no es JSON, mostrar el texto de respuesta
                        if (xhr.responseText && xhr.responseText.trim().length > 0) {
                            errorMsg += '\n\nDetalles del servidor:\n' + xhr.responseText.substring(0, 500);
                        }
                    }
                    
                    alert(errorMsg + '\n\nPor favor, revise la consola (F12) para más detalles.');
                }
            });
        }

        $(function() {
            // Cargar bases de datos disponibles
            $.get('?loadBasesDatos=1', function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.bases) {
                        public $select = $('#selBaseDatos');
                        $select.empty().append('<option value="">-- Seleccione una base de datos --</option>');
                        $.each(data.bases, function(i, base) {
                            $select.append('<option value="' + base.Dat_Dis + '">' + base.Emp_Nom + '</option>');
                        });
                    }
                } catch (e) {
                    console.error('Error al cargar bases de datos:', e);
                }
            }, 'json');

            // Inicializar Select2 para el selector de base de datos
            $('#selBaseDatos').select2({
                placeholder: 'Seleccione una base de datos...',
                allowClear: true
            });

            // Cuando se selecciona una base de datos
            $('#selBaseDatos').on('select2:select', function (e) {
                var Dat_Dis = $(this).val();
                baseDatosSeleccionada = Dat_Dis;
                
                if (Dat_Dis) {
                    // Cargar empresas de esta base de datos
                    $('#empresa_origen').prop('disabled', true).empty().append('<option value="">Cargando empresas...</option>');
                    
                    $.get('?loadEmpresasByDB=1', { Dat_Dis: Dat_Dis }, function(response) {
                        try {
                            var data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.success && data.empresas) {
                                public $select = $('#empresa_origen');
                                $select.empty().append('<option value="">-- Seleccione una empresa --</option>');
                                $.each(data.empresas, function(i, empresa) {
                                    var empresa_nombre = empresa.Emp_Nom || 'Empresa ' + empresa.Emp_Cod;
                                    var empresa_codigo = empresa.Emp_Cor || '';
                                    $select.append('<option value="' + empresa.Emp_Cod + '" data-empresa="' + 
                                                  empresa_nombre.replace(/"/g, '&quot;') + '">' + 
                                                  empresa_nombre + (empresa_codigo ? ' (' + empresa_codigo + ')' : '') + '</option>');
                                });
                                $select.prop('disabled', false);
                                
                                // Reinicializar Select2 con las nuevas opciones y búsqueda habilitada
                                // Primero destruir si ya existe
                                if ($('#empresa_origen').hasClass('select2-hidden-accessible')) {
                                    $('#empresa_origen').select2('destroy');
                                }
                                
                                $('#empresa_origen').select2({
                                    placeholder: 'Busque y seleccione una empresa...',
                                    allowClear: true,
                                    language: {
                                        noResults: function() {
                                            return "No se encontraron empresas";
                                        },
                                        searching: function() {
                                            return "Buscando...";
                                        }
                                    },
                                    matcher: function(params, data) {
                                        // Si no hay término de búsqueda, mostrar todas las opciones
                                        if ($.trim(params.term) === '') {
                                            return data;
                                        }
                                        
                                        // Normalizar texto para búsqueda (sin acentos, minúsculas)
                                        var term = params.term.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                        var text = data.text.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                        var empresa = $(data.element).attr('data-empresa') || '';
                                        empresa = empresa.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                        
                                        // Buscar en el texto completo (nombre - código) o solo en nombre
                                        if (text.indexOf(term) > -1 || empresa.indexOf(term) > -1) {
                                            return data;
                                        }
                                        
                                        return null;
                                    }
                                });
                            } else {
                                $('#empresa_origen').empty().append('<option value="">No hay empresas disponibles</option>').prop('disabled', true);
                                alert(data.message || 'No se encontraron empresas en esta base de datos');
                            }
                        } catch (e) {
                            console.error('Error al cargar empresas:', e);
                            alert('Error al cargar las empresas');
                            $('#empresa_origen').empty().append('<option value="">Error al cargar</option>').prop('disabled', true);
                        }
                    }, 'json');
                } else {
                    $('#empresa_origen').select2('destroy').empty().append('<option value="">-- Primero seleccione una base de datos --</option>').prop('disabled', true);
                    $('#fieldset_proveedores').hide();
                    $('#btn_copiar').prop('disabled', true);
                }
            });

            // Cuando se deselecciona la base de datos
            $('#selBaseDatos').on('select2:clear', function (e) {
                baseDatosSeleccionada = null;
                $('#empresa_origen').select2('destroy').empty().append('<option value="">-- Primero seleccione una base de datos --</option>').prop('disabled', true);
                $('#fieldset_proveedores').hide();
                $('#btn_copiar').prop('disabled', true);
            });

            // Inicio Grid para presentar los proveedores de la empresa origen
            // Inicializar con datatype 'local' para evitar carga inicial hasta que se seleccione empresa
            var initialPostData = $("#frm_bus").getData("proveedoresOrigenAjax");
            initialPostData['Emp_Cod_Origen'] = ''; // Inicializar vacío para evitar error
            initialPostData['Dat_Dis'] = '';
            
            $("#Lis_Prv_Origen").createGrid({
                url: '?proveedoresOrigenAjax=1',
                postData: initialPostData,
                datatype: 'local', // Cambiar a 'local' inicialmente para evitar carga
                height: 295,
                multiselect: true,
                colModel: [{
                        label: 'Cod.Int.',
                        name: 'Prv_Cod',
                        width: 30,
                        align: "left",
                        key: true
                    },
                    {
                        label: 'Cédula/R.U.C.',
                        name: 'Prs_Ced',
                        width: 65,
                        align: "left",
                        cellattr: function() {
                            return 'style="' + excelFormats.text + '"';
                        }
                    },
                    {
                        label: 'Nombre',
                        name: 'proveedor',
                        width: 200,
                        align: "left"
                    },
                    {
                        label: 'Direccion',
                        name: 'Prs_Dir',
                        width: 200,
                        align: "left"
                    },
                    {
                        label: 'Correo',
                        name: 'Prs_Cor',
                        width: 120,
                        align: "left"
                    },
                    {
                        label: 'Telefono',
                        name: 'Prs_Tel',
                        width: 70,
                        align: "left"
                    },
                ]
            }, false, "#Pag_Prv_Origen");
        });
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>
<?php
/**
 * Cierre de las conexiones
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

