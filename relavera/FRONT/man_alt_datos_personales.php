<?php
/**
 * @abstract Permite realizar la actualización de datos personales
 * @author José Ambuludí / Antigravity
 * @version 1.1
 * Fecha de creación  2016-10-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_datos_personales.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Actualizacion($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Actualizacion;

// AJAX: Listado de Ciudades
if (isset($listCiudadesAjax)) {
    $rows_data = $obBD_con1->getArrayConsulta(1, array(), $obBD_conexion);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// 1. Intentar obtener planta de la sesión
$Pla_Cod_Log = isset($_SESSION['Ses_Pla_Cod']) ? $_SESSION['Ses_Pla_Cod'] : 0;

// 2. Si no está en sesión, buscar en manifiesto_usuario por el usuario logeado
if ($Pla_Cod_Log == 0) {
    $row_mu = $obBD_con1->getArrayConsultaSql("SELECT Pla_Cod FROM manifiesto_usuario WHERE Usu_Cod = '".$_SESSION['Ses_Usu_Cod']."' LIMIT 1", $obBD_conexion);
    if (isset($row_mu[0]['Pla_Cod'])) {
        $Pla_Cod_Log = $row_mu[0]['Pla_Cod'];
    }
}

// 3. Si sigue siendo 0, obtener la primera planta activa de la empresa
if ($Pla_Cod_Log == 0) {
    $row_pla_def = $obBD_con1->getRowConsulta(21, array($_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $Pla_Cod_Log = isset($row_pla_def['Pla_Cod']) ? $row_pla_def['Pla_Cod'] : 0;
}

// Verificar si el usuario logeado tiene perfil de "Plantas"
$row_perfil = $obBD_con1->getRowConsulta(26, array($_SESSION['Ses_Usu_Cod']), $obBD_conexion);
$es_perfil_plantas = (isset($row_perfil['count']) && $row_perfil['count'] > 0);

// Verificar si la campaña de actualización está activa para la empresa
$row_campana = $obBD_con1->getRowConsulta(18, array($_SESSION['Ses_Emp_Cod']), $obBD_conexion);
$campana_activa = (isset($row_campana['activa']) && $row_campana['activa'] > 0);

// Lógica de visualización
$mostrar_bloqueo_perfil = !$es_perfil_plantas;
$mostrar_bloqueo_campana = $es_perfil_plantas && !$campana_activa;

// Obtener datos del usuario logeado para auto-llenado
$row_user = $obBD_con1->getRowConsulta(23, array($_SESSION['Ses_Usu_Cod']), $obBD_conexion);
$Prs_Ced_Log = isset($row_user['Prs_Ced']) ? $row_user['Prs_Ced'] : '';

// AJAX: Listado de Personal para el Grid
if (isset($listPersonalGridAjax)) {
    $page = isset($page) ? $page : 1;
    $limit = isset($rows) ? $rows : 50;
    $sidx = (isset($sidx) && !empty($sidx)) ? $sidx : 'p.Prs_Ape';
    $sord = (isset($sord) && !empty($sord)) ? $sord : 'asc';
    $start = ($page * $limit) - $limit;

    $searchQuery = "";
    if (isset($search) && $search != '') {
        $op_opciones = isset($op_opciones) ? $op_opciones : 'n';
        if ($op_opciones == 'n') {
            $searchQuery = " AND (p.Prs_Nom LIKE '%$search%' OR p.Prs_Ape LIKE '%$search%')";
        } else if ($op_opciones == 'c') {
            $searchQuery = " AND p.Prs_Ced LIKE '$search%'";
        } else if ($op_opciones == 'p') {
            $searchQuery = " AND mp.Pla_Nom LIKE '%$search%'";
        }
    }

    $params_grid = array(
        'Pla_Cod' => $Pla_Cod_Log,
        'searchQuery' => $searchQuery,
        'sidx' => $sidx,
        'sord' => $sord,
        'start' => $start,
        'limit' => $limit
    );

    $rows_data = $obBD_con1->getArrayConsulta(19, $params_grid, $obBD_conexion);
    $row_count = $obBD_con1->getRowConsulta(20, $params_grid, $obBD_conexion);
    $count = $row_count['count'];

    $response = array(
        'page' => $page,
        'total' => ceil($count / $limit),
        'records' => $count,
        'rows' => $rows_data
    );
    $obBD_con1->echoJson($response);
    exit;
}

// Cargamos los datos completos de la persona logeada
$persona_log = array();
if ($Prs_Ced_Log != '') {
    $persona_log = $obBD_con1->getRowConsulta(3, array($Prs_Ced_Log), $obBD_conexion);
    $row_per = $obBD_con1->getRowConsulta(24, array((isset($persona_log['Prs_Cod']) ? $persona_log['Prs_Cod'] : 0), $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    if (isset($row_per['Per_Cod'])) {
        $persona_log['Per_Cod'] = $row_per['Per_Cod'];
    }
}

// Lógica de guardado (AJAX)
if (isset($_POST['savePersonal'])) {
    $response = array('success' => false);
    
    // Capturar variables explícitamente para asegurar que no lleguen vacías
    $Per_Cod = isset($_POST['Per_Cod']) ? $_POST['Per_Cod'] : 0;
    $Prs_Nom = isset($_POST['Prs_Nom']) ? $_POST['Prs_Nom'] : '';
    $Prs_Ape = isset($_POST['Prs_Ape']) ? $_POST['Prs_Ape'] : '';
    $Prs_Ced = isset($_POST['Prs_Ced']) ? $_POST['Prs_Ced'] : '';
    $Prs_Fec = isset($_POST['Prs_Fec']) ? $_POST['Prs_Fec'] : '';
    $Ciu_Cod = isset($_POST['Ciu_Cod']) ? $_POST['Ciu_Cod'] : '';
    $Prs_Sex = isset($_POST['Prs_Sex']) ? $_POST['Prs_Sex'] : 'M';
    $Prs_Esc = isset($_POST['Prs_Esc']) ? $_POST['Prs_Esc'] : 'S';
    $Prs_Tel = isset($_POST['Prs_Tel']) ? $_POST['Prs_Tel'] : '';
    $Prs_Te2 = isset($_POST['Prs_Te2']) ? $_POST['Prs_Te2'] : '';
    $Prs_Cel = isset($_POST['Prs_Cel']) ? $_POST['Prs_Cel'] : '';
    $Prs_Cor = isset($_POST['Prs_Cor']) ? $_POST['Prs_Cor'] : '';
    $Prs_Dir = isset($_POST['Prs_Dir']) ? $_POST['Prs_Dir'] : '';
    $Prs_San = isset($_POST['Prs_San']) ? $_POST['Prs_San'] : '';
    $Per_Car = isset($_POST['Per_Car']) ? $_POST['Per_Car'] : '0';
    $Per_Tit = isset($_POST['Per_Tit']) ? $_POST['Per_Tit'] : 'Np';
    $Per_Obs = isset($_POST['Per_Obs']) ? $_POST['Per_Obs'] : '';
    $Per_Cfi = isset($_POST['Per_Cfi']) ? $_POST['Per_Cfi'] : '';
    $Cho_Tli = isset($_POST['Cho_Tli']) ? $_POST['Cho_Tli'] : '';
    $Cho_Cli = isset($_POST['Cho_Cli']) ? $_POST['Cho_Cli'] : '';
    $Per_Fot_Actual = isset($_POST['Per_Fot_Actual']) ? $_POST['Per_Fot_Actual'] : '';

    $per_fot = $Per_Fot_Actual;
    // Procesar carga de foto si existe
    if (isset($_FILES['avatar-1']) && $_FILES['avatar-1']['error'] == 0) {
        $ext = pathinfo($_FILES['avatar-1']['name'], PATHINFO_EXTENSION);
        
        // Función para limpiar nombres (quitar tildes, espacios y caracteres especiales)
        function limpiarNombre($string) {
            $string = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'), array('a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N'), $string);
            return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $string));
        }

        $nom_limpio = limpiarNombre($Prs_Nom);
        $ape_limpio = limpiarNombre($Prs_Ape);
        
        // Usar el nuevo formato: nombre_apellido_planta
        $per_fot = $nom_limpio . "_" . $ape_limpio . "_" . $Pla_Cod_Log . "." . $ext;
        
        $directorio = "../../mascaras/model1/imagenes/personal/";
        $ruta_destino = $directorio . $per_fot;
        
        // Crear directorio si no existe
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['avatar-1']['tmp_name'], $ruta_destino)) {
            // Foto subida con éxito
        } else {
            // Si falla la subida, mantenemos la anterior
            $per_fot = $Per_Fot_Actual;
            $response['msg_foto'] = "Error: No se pudo mover el archivo. Verifique permisos.";
        }
    } else if (isset($_FILES['avatar-1']) && $_FILES['avatar-1']['error'] != 0) {
        // Reportar error de PHP si existe
        $error_codes = array(
            1 => 'El archivo excede el límite de upload_max_filesize en php.ini',
            2 => 'El archivo excede el límite de MAX_FILE_SIZE en el formulario',
            3 => 'El archivo se subió parcialmente',
            4 => 'No se subió ningún archivo',
            6 => 'Falta la carpeta temporal',
            7 => 'Error al escribir el archivo en el disco',
            8 => 'Una extensión de PHP detuvo la subida'
        );
        $err_num = $_FILES['avatar-1']['error'];
        $response['msg_foto'] = "Error de subida (" . $err_num . "): " . (isset($error_codes[$err_num]) ? $error_codes[$err_num] : 'Error desconocido');
    }

    // Asegurar formato de fechas para MySQL
    if (!empty($Prs_Fec)) {
        // Si viene en formato dd/mm/aaaa, convertirlo
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Prs_Fec)) {
            $parts = explode('/', $Prs_Fec);
            $Prs_Fec = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
    
    if (!empty($Cho_Cli)) {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Cho_Cli)) {
            $parts = explode('/', $Cho_Cli);
            $Cho_Cli = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }

    // Parámetros para la actualización (Caso 8 en SQL)
    $params = array(
        $Per_Cod,
        $Prs_Nom,
        $Prs_Ape,
        isset($Prs_Sex) ? $Prs_Sex : 'M',
        isset($Prs_Esc) ? $Prs_Esc : 'S',
        $Prs_Fec,
        $Ciu_Cod,
        $Prs_Tel,
        isset($Prs_Te2) ? $Prs_Te2 : '',
        $Prs_Cel,
        $Prs_Cor,
        $Prs_Dir,
        $Prs_San,
        isset($Per_Car) ? $Per_Car : '0',
        isset($Per_Tit) ? $Per_Tit : 'Np',
        isset($Per_Obs) ? $Per_Obs : '',
        $per_fot, // Nombre del archivo de la foto
        isset($Per_Cfi) ? $Per_Cfi : '',
        '0',
        $Prs_Cod // 19: Para el WHERE
    );

    // 1. Actualizar Datos Generales (Tabla persona)
    $obBD_con1->operacionobBD(8, $params, $obBD_conexion);
    
    // 2. Actualizar Datos de Personal (Tabla personal) - Solo si Per_Cod existe
    if ($Per_Cod > 0) {
        $obBD_con1->operacionobBD(25, $params, $obBD_conexion);
    }
    
    if ($obBD_con1->Error == 0) {
        // 3. Actualizar o Insertar datos de Chofer si aplica
        if (!empty($Cho_Tli)) {
            if ($Prs_Cod > 0) {
                // Verificar si ya existe en la tabla chofer
                $row_c = $obBD_con1->getArrayConsultaSql("SELECT COUNT(*) as existe FROM chofer WHERE Prs_Cod = '$Prs_Cod'", $obBD_conexion);
                
                if (isset($row_c[0]['existe']) && $row_c[0]['existe'] > 0) {
                    // Existe -> UPDATE
                    $params_chofer = array(
                        $Cho_Tli,   // 0
                        $Cho_Cli,   // 1
                        $Prs_Cod    // 2
                    );
                    $obBD_con1->operacionobBD(22, $params_chofer, $obBD_conexion);
                } else {
                    // No existe -> INSERT
                    $sql_ins = "INSERT INTO chofer (Prs_Cod, Emp_Cod, Cho_Tli, Cho_Cli, Cho_Est) 
                                VALUES ('$Prs_Cod', '".$_SESSION['Ses_Emp_Cod']."', '$Cho_Tli', '$Cho_Cli', 'A')";
                    $obBD_con1->consulta($sql_ins, $obBD_conexion->conexion);
                }
            }
        }
        $response['success'] = true;
    } else {
        $response['error'] = "Error Persona: " . $obBD_con1->MsgError;
    }
    $obBD_con1->echoJson($response);
    exit;
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE>Actualizaci&oacute;n de Datos Personales [EXA]</TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script language="javascript" src="../../framework/plugins/cedulaRuc.js"></script>
        <style>
            .photo-container { border: 1px solid #ddd; border-radius: 8px; padding: 10px; display: inline-block; margin-bottom: 10px; }
            .photo-preview { width: 160px; height: 160px; object-fit: cover; border-radius: 4px; }
            .photo-buttons { display: flex; justify-content: center; gap: 5px; }
            .btn-select { font-weight: bold; background-color: #4cae4c; border-color: #4cae4c; color: white; }
            .btn-remove { background-color: #d9534f; border-color: #d43f3a; color: white; }
            .panel-main { background: #fdfdfd !important; border: 1px solid #ddd !important; }
            .exa-body { padding: 10px !important; }
            .exa-fieldset { margin-bottom: 15px; border: 1px solid rgba(0,0,0,0.1); padding: 15px; border-radius: 4px; background: transparent; }
            .exa-fieldset legend { width: auto; border-bottom: none; font-weight: bold; color: #555; font-size: 14px; padding: 0 10px; margin-bottom: 0; background: transparent; }
            .label-sm { font-weight: bold; color: #555; font-size: 11px; padding-right: 0; }
            .form-control { border-radius: 2px; }
            .button-center { text-align: center; margin-top: 10px; padding-bottom: 10px; }
            .btn-save { font-weight: bold; padding: 6px 20px; }
            .input-group-sangre { display: flex; }
            .input-group-sangre select { border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1; }
            #Prs_San_Otro { border-top-left-radius: 0; border-bottom-left-radius: 0; border-left: 0; flex: 1; }
            
            /* Estilo Premium para Mensajes de Bloqueo - Rediseño Vibrante */
            .restricted-container {
                max-width: 650px;
                margin: 60px auto;
                padding: 0;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                text-align: center;
                border: none;
                overflow: hidden;
                border-top: 5px solid #d9534f;
            }
            .restricted-header {
                background: linear-gradient(to bottom, #fff5f5, #fff);
                padding: 40px 30px 20px;
            }
            .restricted-icon-wrapper {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #ff6b6b, #d9534f);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                box-shadow: 0 8px 15px rgba(217, 83, 79, 0.3);
            }
            .restricted-icon {
                font-size: 32px;
                color: #fff;
                margin-bottom: 0 !important;
            }
            .restricted-title {
                font-size: 24px;
                font-weight: 800;
                color: #2c3e50;
                margin-bottom: 10px;
                letter-spacing: -0.5px;
            }
            .restricted-body {
                padding: 0 40px 40px;
            }
            .restricted-text {
                font-size: 16px;
                color: #5a6c7d;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .restricted-footer {
                font-size: 13px;
                color: #8898aa;
                background: #f8f9fe;
                padding: 20px 40px;
                border-top: 1px solid #edf2f7;
            }
            .restricted-footer b { color: #d9534f; }

            /* Versión Azul/Info para Campaña Inactiva */
            .restricted-container.info-theme {
                border-top: 5px solid #3498db;
            }
            .restricted-container.info-theme .restricted-icon-wrapper {
                background: linear-gradient(135deg, #5dade2, #3498db);
                box-shadow: 0 8px 15px rgba(52, 152, 219, 0.3);
            }
            .restricted-container.info-theme .restricted-header {
                background: linear-gradient(to bottom, #ebf5fb, #fff);
            }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-default panel-main">
            <div class="panel-heading" style="background: #334a5f; color: white; font-weight: bold;">
                » Actualizaci&oacute;n de Datos Personales
            </div>
            <div class="panel-body exa-body">
                
                <?php if($mostrar_bloqueo_perfil) { ?>
                    <div class="restricted-container">
                        <div class="restricted-header">
                            <div class="restricted-icon-wrapper">
                                <span class="glyphicon glyphicon-lock restricted-icon"></span>
                            </div>
                            <div class="restricted-title">Acceso Restringido</div>
                        </div>
                        <div class="restricted-body">
                            <div class="restricted-text">
                                Este m&oacute;dulo de actualizaci&oacute;n de datos est&aacute; habilitado exclusivamente para el personal operativo de las <b>Plantas asignadas</b>.
                            </div>
                        </div>
                        <div class="restricted-footer">
                            Para mayor informaci&oacute;n o gestiones administrativas, por favor contacte con la <b>Administraci&oacute;n del Sistema</b>.
                        </div>
                    </div>
                <?php } elseif($mostrar_bloqueo_campana) { ?>
                    <div class="restricted-container info-theme">
                        <div class="restricted-header">
                            <div class="restricted-icon-wrapper">
                                <span class="glyphicon glyphicon-info-sign restricted-icon"></span>
                            </div>
                            <div class="restricted-title">Campa&ntilde;a Inactiva</div>
                        </div>
                        <div class="restricted-body">
                            <div class="restricted-text">
                                Lo sentimos, la campa&ntilde;a de actualizaci&oacute;n de datos <b>no se encuentra activa</b> para su planta en este momento.
                            </div>
                        </div>
                        <div class="restricted-footer">
                            Le informaremos oportunamente cuando se habilite un nuevo periodo de actualizaci&oacute;n.
                        </div>
                    </div>
                <?php } else { ?>

                <!-- Ambiente 1: Listado/Grid -->
                <div id="divListado">
                    <fieldset class="exa-fieldset">
                        <legend>Filtro de B&uacute;squeda</legend>
                        <form id="filtroPersonalForm" onsubmit="return false;">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group" style="margin-bottom: 5px;">
                                        <label class="col-sm-2 control-label label-sm" style="text-align: right; padding-top: 5px;">Filtrar Por:</label>
                                        <div class="col-sm-10">
                                            <div id="opcionesFiltro" class="radioset">
                                                <input type="radio" id="radNombre" name="op_opciones" value="n" checked><label for="radNombre">Nombre Chofer</label>
                                                <input type="radio" id="radCedula" name="op_opciones" value="c"><label for="radCedula">C&eacute;dula</label>
                                                <input type="radio" id="radPlanta" name="op_opciones" value="p"><label for="radPlanta">Nombre Planta</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-sm" style="text-align: right; padding-top: 5px;">B&uacute;squeda:</label>
                                        <div class="col-sm-6">
                                            <input type="text" id="txtBuscarPersonal" name="search" class="form-control input-sm" placeholder="Ingrese b&uacute;squeda..." onkeyup="if(event.keyCode==13) actualizarGridPersonal();">
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="button" class="btn btn-success btn-sm" onclick="actualizarGridPersonal();"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                            <button type="button" class="btn btn-default btn-sm" onclick="limpiarFiltroPersonal();"><span class="glyphicon glyphicon-refresh"></span> Actualizar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </fieldset>
                    <div style="margin-top: 10px;">
                        <table id="gridPersonal"></table>
                        <div id="gridPersonalPager"></div>
                    </div>
                </div>

                <!-- Ambiente 2: Formulario -->
                <div id="divFormulario" style="display:none; padding: 15px; border-radius: 4px; border: 1px solid #eee;">
                <form id="formPersonal" class="form-horizontal" onsubmit="saveForm(); return false;">
                    <input type="hidden" id="Prs_Cod" name="Prs_Cod" value="0" />
                    <input type="hidden" id="Per_Cod" name="Per_Cod" value="0" />
                    <input type="hidden" id="Per_Fot_Hidden" name="Per_Fot_Actual" value="" />
                    
                    <div class="row">
                        <!-- Columna Izquierda: Foto -->
                        <div class="col-sm-3 text-center" style="border-right: 1px solid #eee;">
                            <div class="photo-container">
                                <img id="imgPreview" src="../../mascaras/model1/imagenes/128x128/perfil.png" class="photo-preview" alt="Foto">
                            </div>
                            <div class="photo-buttons">
                                <button type="button" class="btn btn-remove btn-sm" onclick="resetImage();" title="Quitar imagen">
                                    <span class="glyphicon glyphicon-remove"></span>
                                </button>
                                <button type="button" class="btn btn-select btn-sm" onclick="$('#fileInput').click();">
                                    <span class="glyphicon glyphicon-folder-open"></span> Seleccionar imagen
                                </button>
                                <input type="file" id="fileInput" name="avatar-1" style="display: none;" onchange="previewImage(this);" accept="image/*">
                            </div>
                            <div id="msgFotoError" style="margin-top: 5px; color: red; font-size: 10px; display: none;"></div>
                        </div>

                        <!-- Columna Derecha: Datos -->
                        <div class="col-sm-9">
                            <fieldset class="exa-fieldset">
                                <legend>Datos de Identidad</legend>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">C&eacute;dula:</label>
                                            <div class="col-sm-8"><input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-sm" readonly style="background-color: #eee;" /></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Fecha Nac.:</label>
                                            <div class="col-sm-8"><input id="Prs_Fec" name="Prs_Fec" type="text" class="form-control input-sm datepicker" placeholder="dd/mm/aaaa" /></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Apellidos:</label>
                                            <div class="col-sm-8"><input id="Prs_Ape" name="Prs_Ape" type="text" class="form-control input-sm" /></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Nombres:</label>
                                            <div class="col-sm-8"><input id="Prs_Nom" name="Prs_Nom" type="text" class="form-control input-sm" /></div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="exa-fieldset">
                                <legend>Contacto y Ubicaci&oacute;n</legend>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Ciudad:</label>
                                            <div class="col-sm-8"><select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-sm"></select></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Correo:</label>
                                            <div class="col-sm-8"><input id="Prs_Cor" name="Prs_Cor" type="email" class="form-control input-sm" /></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tel&eacute;fono:</label>
                                            <div class="col-sm-8"><input id="Prs_Tel" name="Prs_Tel" type="text" class="form-control input-sm" /></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Celular:</label>
                                            <div class="col-sm-8"><input id="Prs_Cel" name="Prs_Cel" type="text" class="form-control input-sm" /></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-sm">Direcci&oacute;n:</label>
                                            <div class="col-sm-10"><input id="Prs_Dir" name="Prs_Dir" type="text" class="form-control input-sm" /></div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="exa-fieldset">
                                <legend>Otros Datos</legend>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Estado Civil:</label>
                                            <div class="col-sm-8">
                                                <select id="Prs_Esc" name="Prs_Esc" class="form-control input-sm">
                                                    <option value="S">SOLTERO/A</option>
                                                    <option value="C">CASADO/A</option>
                                                    <option value="D">DIVORCIADO/A</option>
                                                    <option value="V">VIUDO/A</option>
                                                    <option value="U">UNION LIBRE</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Sexo:</label>
                                            <div class="col-sm-8">
                                                <select id="Prs_Sex" name="Prs_Sex" class="form-control input-sm">
                                                    <option value="M">MASCULINO</option>
                                                    <option value="F">FEMENINO</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Carga Familiar:</label>
                                            <div class="col-sm-8">
                                                <input id="Per_Car" name="Per_Car" type="text" class="form-control input-sm" onkeypress="return validar_numeric(event);" value="0" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tipo Sangre:</label>
                                            <div class="col-sm-8">
                                                <div class="input-group-sangre">
                                                    <select id="Prs_San_Sel" class="form-control input-sm" onchange="cambioTipoSangre(this.value);">
                                                        <option value="">Seleccione...</option>
                                                        <option value="O+">O+</option>
                                                        <option value="O-">O-</option>
                                                        <option value="A+">A+</option>
                                                        <option value="A-">A-</option>
                                                        <option value="B+">B+</option>
                                                        <option value="B-">B-</option>
                                                        <option value="AB+">AB+</option>
                                                        <option value="AB-">AB-</option>
                                                        <option value="OTROS">OTROS</option>
                                                    </select>
                                                    <input id="Prs_San_Otro" type="text" class="form-control input-sm" style="display:none;" placeholder="Especifique..." onkeyup="$('#Prs_San').val(this.value);" />
                                                    <input type="hidden" id="Prs_San" name="Prs_San" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm required">Tipo Licencia:</label>
                                            <div class="col-sm-8">
                                                <select id="Cho_Tli" name="Cho_Tli" class="form-control input-sm">
                                                    <option value="Np">NO POSEE</option>
                                                    <option value="A">TIPO A</option>
                                                    <option value="B">TIPO B</option>
                                                    <option value="C">TIPO C</option>
                                                    <option value="C1">TIPO C1</option>
                                                    <option value="D">TIPO D</option>
                                                    <option value="D1">TIPO D1</option>
                                                    <option value="E">TIPO E</option>
                                                    <option value="E1">TIPO E1</option>
                                                    <option value="F">TIPO F</option>
                                                    <option value="G">TIPO G</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm required">Caducidad:</label>
                                            <div class="col-sm-8">
                                                <input id="Cho_Cli" name="Cho_Cli" type="text" class="form-control input-sm datepicker" placeholder="dd/mm/aaaa" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="button-center">
                                <button type="button" class="btn btn-primary btn-save" onclick="saveForm();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar Datos</button>
                                <button type="button" class="btn btn-default btn-save" onclick="mostrarListado();"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                            </div>
                        </div>
                    </div>
                </form>
                </div>
                <?php } ?>
            </div>
        </div>
        <script type="text/javascript">
            var dataLog = <?php echo json_encode($persona_log); ?>;
            var mostrarDirecto = false; // Deshabilitado para mostrar siempre el Grid primero
        </script>
        <!-- Loader -->
        <div id="loader" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; text-align: center; padding-top: 20%;">
            <div style="display: inline-block; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #3c8dbc;"></i>
                <div style="margin-top: 10px; font-weight: bold; color: #444;">Procesando...</div>
            </div>
        </div>
        <script src="../VALIDACIONES/man_val_datos_personales.js?x=9"></script>
    </BODY>
</HTML>
