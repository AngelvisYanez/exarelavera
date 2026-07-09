<?php

/**
 * @abstract Administración de Plantas, Choferes, Vehículos y Celdas
 * @author Sistema EXA
 * @version 1.0
 * Fecha de creación: <?php echo date('d/m/Y'); ?>
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);

/* ==================== AJAX HANDLERS ==================== */
if (isset($cliAjax)) {
    $obBD_con1->getPageGridJson('cliente.selectWhere', $_GET, $obBD_conexion);
}


// Listar Plantas
if (isset($listPlantasGridAjax)) {
    // require_once('../../Librerias/procedimientos/almacenados_standar.php');
    ChromePhp::log("listPlantasGridAjax ejecutándose");
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 20;
    // Obtener parámetros de filtro
    $op_opciones = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'd';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    ChromePhp::log("page: " . $page . ", rows: " . $rows . ", op_opciones: " . $op_opciones . ", search: " . $search);
    // Preparar datos para la consulta
    $data = array('limits' => '', 'op_opciones' => $op_opciones, 'search' => $search);
    ChromePhp::log("Llamando getRowConsulta con id=3, data: ", $data);
    // Contar total de registros (sin limits)
    $contar = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);
    ChromePhp::log("Resultado contar: ", $contar);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];
    if ($contar['total'] > 0) {
        // Obtener registros con paginación (con limits)
        $data['limits'] = $pagination['limits'];
        ChromePhp::log("Llamando getArrayConsulta con id=3, data: ", $data);
        $response['rows'] = $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);
        ChromePhp::log("Registros obtenidos: " . count($response['rows']));
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
}




// Función auxiliar para obtener o crear persona
function obtenerOCrearPersona($obBD_con1, $obBD_conexion, $Prs_Ced, $datosPersona)
{
    // Si es RUC de 13 dígitos, extraer la cédula (primeros 10)
    $prsAux = $Prs_Ced;
    $longitud = strlen($prsAux);
    if ($longitud == 13) {
        $prsAux = substr($prsAux, 0, 10);
    }

    // Buscar si existe la persona
    $persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Ced' => $prsAux)), $obBD_conexion);

    if (!empty($persona)) {
        // Si existe, retornar su código
        return $persona['Prs_Cod'];
    } else {
        // Si no existe, crear nueva persona
        $datosPersona['Prs_Ced'] = $prsAux;
        $obBD_con1->operacionobBD('persona.insert', $datosPersona, $obBD_conexion);
        return $obBD_con1->insercionid($obBD_conexion);
    }
}

// Función auxiliar para guardar personal de planta
function guardarPersonalPlanta($obBD_con1, $obBD_conexion, $Pla_Cod, $Prs_Ced, $datosPersonal, $Pep_Tip, $datosPersona = array())
{
    if (empty($Prs_Ced)) {
        return; // Si no hay cédula, no se guarda
    }
    // Obtener o crear la persona
    $Prs_Cod = obtenerOCrearPersona($obBD_con1, $obBD_conexion, $Prs_Ced, $datosPersona);
    // Verificar si ya existe el registro
    $existePersonal = $obBD_con1->getRowConsulta('manifiesto_personal_planta.selectWhere',  array('where' => array('Pla_Cod' => $Pla_Cod,  'Prs_Cod' => $Prs_Cod,  'Pep_Tip' => $Pep_Tip)),  $obBD_conexion);
    // Datos para manifiesto_personal_planta
    $datosManifiestoPersonal = array(
        'Prs_Cod' => $Prs_Cod,
        'Pla_Cod' => $Pla_Cod,
        'Pep_Tip' => $Pep_Tip,
        'Pep_Est' => 'A'
    );
    // Agregar campos adicionales si existen
    if (isset($datosPersonal['Ciu_Cod_Tra']) && !empty($datosPersonal['Ciu_Cod_Tra'])) {
        $datosManifiestoPersonal['Ciu_Cod_Tra'] = $datosPersonal['Ciu_Cod_Tra'];
    }
    if (isset($datosPersonal['Ciu_Cod_Nac']) && !empty($datosPersonal['Ciu_Cod_Nac'])) {
        $datosManifiestoPersonal['Ciu_Cod_Nac'] = $datosPersonal['Ciu_Cod_Nac'];
    }
    if (isset($datosPersonal['Pep_Esc']) && !empty($datosPersonal['Pep_Esc'])) {
        $datosManifiestoPersonal['Pep_Esc'] = $datosPersonal['Pep_Esc'];
    }
    if (isset($datosPersonal['Pep_Cor']) && !empty($datosPersonal['Pep_Cor'])) {
        $datosManifiestoPersonal['Pep_Cor'] = $datosPersonal['Pep_Cor'];
    }
    if (isset($datosPersonal['Pep_Tel'])) {
        $datosManifiestoPersonal['Pep_Tel'] = $datosPersonal['Pep_Tel'];
    }
    if (isset($datosPersonal['Dir_Tra']) && !empty($datosPersonal['Dir_Tra'])) {
        $datosManifiestoPersonal['Dir_Tra'] = $datosPersonal['Dir_Tra'];
    }

    // Si ya existe, actualizar; si no, insertar
    if (!empty($existePersonal)) {
        $datosManifiestoPersonal['where'] = array('Pep_Cod' => $existePersonal['Pep_Cod']);
        $obBD_con1->operacionobBD('manifiesto_personal_planta.update', $datosManifiestoPersonal, $obBD_conexion);
    } else {
        $obBD_con1->operacionobBD('manifiesto_personal_planta.insert', $datosManifiestoPersonal, $obBD_conexion);
    }
}

// Guardar Planta
if (isset($savePlantaAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        // Guardar datos de la planta
        $datosPlanta = array('Cli_Cod' => $Cli_Cod, 'Ciu_Cod' => $Ciu_Cod, 'Pla_Nom' => $Pla_Nom, 'Pla_Lic' => $Pla_Lic, 'Pla_Dir' => $Pla_Dir, 'Pla_Est' => 'A');

        // Agregar campos adicionales de la planta
        if (isset($Pla_Car) && !empty($Pla_Car)) $datosPlanta['Pla_Car'] = $Pla_Car;
        if (isset($Pla_Dis) && !empty($Pla_Dis)) $datosPlanta['Pla_Dis'] = $Pla_Dis;
        if (isset($Pla_Geo) && !empty($Pla_Geo)) $datosPlanta['Pla_Geo'] = $Pla_Geo;
        if (isset($Pla_Cap) && !empty($Pla_Cap)) $datosPlanta['Pla_Cap'] = $Pla_Cap;
        if (isset($Pla_Crd) && !empty($Pla_Crd)) $datosPlanta['Pla_Crd'] = $Pla_Crd;
        if (isset($Pla_Cau) && !empty($Pla_Cau)) $datosPlanta['Pla_Cau'] = $Pla_Cau;
        if (isset($Pla_Rut) && !empty($Pla_Rut)) $datosPlanta['Pla_Rut'] = $Pla_Rut;
        if (isset($Pla_Fem) && !empty($Pla_Fem)) $datosPlanta['Pla_Fem'] = $Pla_Fem;
        if (isset($Pla_Fve) && !empty($Pla_Fve)) $datosPlanta['Pla_Fve'] = $Pla_Fve;
        if (isset($Pla_Pfa)) $datosPlanta['Pla_Pfa'] = $Pla_Pfa;
        if (isset($_POST['Pla_Wat'])) $datosPlanta['Pla_Wat'] = $_POST['Pla_Wat'];

        if (!empty($Pla_Cod)) {
            $datosPlanta['where'] = array('Pla_Cod' => $Pla_Cod);
            $obBD_con1->operacionobBD('manifiesto_plantas.update', $datosPlanta, $obBD_conexion);
            $Pla_Cod_New = $Pla_Cod;
        } else {
            $obBD_con1->operacionobBD('manifiesto_plantas.insert', $datosPlanta, $obBD_conexion);
            $Pla_Cod_New = $obBD_con1->insercionid($obBD_conexion);
            $resp['Pla_Cod_New'] = $Pla_Cod_New;
        }

        // Guardar personal tanto para nuevas plantas como para edición
        if (!empty($Pla_Cod_New)) {
            // Guardar Administrador de Planta (AP)
            if (isset($Prs_Ced) && !empty($Prs_Ced)) {
                $datosPersonaAdmin = array(
                    'Prs_Nom' => isset($Prs_Nom) ? $Prs_Nom : '',
                    'Prs_Ape' => isset($Prs_Ape) ? $Prs_Ape : '',
                    'Prs_Sex' => isset($Prs_Sex) ? $Prs_Sex : null,
                    'Prs_Esc' => isset($Pep_Esc) ? $Pep_Esc : null,
                    'Prs_Fec' => isset($Prs_Fec) && !empty($Prs_Fec) ? $Prs_Fec : null,
                    'Prs_Tel' => isset($Prs_Tel) ? $Prs_Tel : null,
                    'Ciu_Cod' => isset($Cod_Ciu_Nac) && !empty($Cod_Ciu_Nac) ? $Cod_Ciu_Nac : (isset($Cod_Ciu_Tra) && !empty($Cod_Ciu_Tra) ? $Cod_Ciu_Tra : 217),
                    'Prs_Dir' => ''
                );
                $datosPersonalAdmin = array(
                    'Ciu_Cod_Tra' => isset($Cod_Ciu_Tra) && !empty($Cod_Ciu_Tra) ? $Cod_Ciu_Tra : null,
                    'Ciu_Cod_Nac' => isset($Cod_Ciu_Nac) && !empty($Cod_Ciu_Nac) ? $Cod_Ciu_Nac : null,
                    'Pep_Esc' => isset($Pep_Esc) ? $Pep_Esc : null,
                    'Pep_Cor' => isset($Pep_Cor) ? $Pep_Cor : null,
                    'Pep_Tel' => isset($_POST['Pep_Tel']) ? trim($_POST['Pep_Tel']) : null
                );
                guardarPersonalPlanta($obBD_con1, $obBD_conexion, $Pla_Cod_New, $Prs_Ced, $datosPersonalAdmin, 'AP', $datosPersonaAdmin);
            }

            // Guardar Tributario/Contador (AC)
            if (isset($Trb_Prs_Ced) && !empty($Trb_Prs_Ced)) {
                $datosPersonaTrib = array(
                    'Prs_Nom' => isset($Trb_Prs_Nom) ? $Trb_Prs_Nom : '',
                    'Prs_Ape' => isset($Trb_Prs_Ape) ? $Trb_Prs_Ape : '',
                    'Prs_Sex' => isset($Trb_Prs_Sex) ? $Trb_Prs_Sex : null,
                    'Prs_Esc' => isset($Trb_Prs_Esc) ? $Trb_Prs_Esc : null,
                    'Prs_Fec' => isset($Trb_Prs_Fec) && !empty($Trb_Prs_Fec) ? $Trb_Prs_Fec : null,
                    'Prs_Tel' => isset($Trb_Prs_Tel) ? $Trb_Prs_Tel : null,
                    'Ciu_Cod' => isset($Trb_Cod_Ciu_Nac) && !empty($Trb_Cod_Ciu_Nac) ? $Trb_Cod_Ciu_Nac : (isset($Trb_Cod_Ciu_Tra) && !empty($Trb_Cod_Ciu_Tra) ? $Trb_Cod_Ciu_Tra : 217),
                    'Prs_Dir' => ''
                );
                $datosPersonalTrib = array(
                    'Ciu_Cod_Tra' => isset($Trb_Cod_Ciu_Tra) && !empty($Trb_Cod_Ciu_Tra) ? $Trb_Cod_Ciu_Tra : null,
                    'Ciu_Cod_Nac' => isset($Trb_Cod_Ciu_Nac) && !empty($Trb_Cod_Ciu_Nac) ? $Trb_Cod_Ciu_Nac : null,
                    'Pep_Esc' => isset($Trb_Prs_Esc) ? $Trb_Prs_Esc : null,
                    'Pep_Cor' => isset($Trb_Pep_Cor) ? $Trb_Pep_Cor : null,
                    'Pep_Tel' => isset($_POST['Trb_Pep_Tel']) ? trim($_POST['Trb_Pep_Tel']) : null
                );
                guardarPersonalPlanta($obBD_con1, $obBD_conexion, $Pla_Cod_New, $Trb_Prs_Ced, $datosPersonalTrib, 'AC', $datosPersonaTrib);
            }

            // Guardar Ambiental (AM)
            if (isset($Amb_Prs_Ced) && !empty($Amb_Prs_Ced)) {
                $datosPersonaAmb = array(
                    'Prs_Nom' => isset($Amb_Prs_Nom) ? $Amb_Prs_Nom : '',
                    'Prs_Ape' => isset($Amb_Prs_Ape) ? $Amb_Prs_Ape : '',
                    'Prs_Sex' => isset($Amb_Prs_Sex) ? $Amb_Prs_Sex : null,
                    'Prs_Esc' => isset($Amb_Prs_Esc) ? $Amb_Prs_Esc : null,
                    'Prs_Fec' => isset($Amb_Prs_Fec) && !empty($Amb_Prs_Fec) ? $Amb_Prs_Fec : null,
                    'Prs_Tel' => isset($Amb_Prs_Tel) ? $Amb_Prs_Tel : null,
                    'Ciu_Cod' => isset($Amb_Cod_Ciu_Nac) && !empty($Amb_Cod_Ciu_Nac) ? $Amb_Cod_Ciu_Nac : (isset($Amb_Cod_Ciu_Tra) && !empty($Amb_Cod_Ciu_Tra) ? $Amb_Cod_Ciu_Tra : 217),
                    'Prs_Dir' => ''
                );
                $datosPersonalAmb = array(
                    'Ciu_Cod_Tra' => isset($Amb_Cod_Ciu_Tra) && !empty($Amb_Cod_Ciu_Tra) ? $Amb_Cod_Ciu_Tra : null,
                    'Ciu_Cod_Nac' => isset($Amb_Cod_Ciu_Nac) && !empty($Amb_Cod_Ciu_Nac) ? $Amb_Cod_Ciu_Nac : null,
                    'Pep_Esc' => isset($Amb_Prs_Esc) ? $Amb_Prs_Esc : null,
                    'Pep_Cor' => isset($Amb_Pep_Cor) ? $Amb_Pep_Cor : null,
                    'Pep_Tel' => isset($_POST['Amb_Pep_Tel']) ? trim($_POST['Amb_Pep_Tel']) : null
                );
                guardarPersonalPlanta($obBD_con1, $obBD_conexion, $Pla_Cod_New, $Amb_Prs_Ced, $datosPersonalAmb, 'AM', $datosPersonaAmb);
            }
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Obtener datos completos de la planta (incluyendo personal)
if (isset($getPlantaCompletaAjax)) {
    $resp = array('success' => false);
    try {
        // Obtener datos de la planta
        $planta = $obBD_con1->getRowConsulta('manifiesto_plantas.selectWhere', array('where' => array('Pla_Cod' => $Pla_Cod)), $obBD_conexion);

        if (!empty($planta)) {
            $resp['success'] = true;
            $resp['planta'] = $planta;

            // Obtener datos del cliente si existe
            if (!empty($planta['Cli_Cod'])) {
                $cliente = $obBD_con1->getRowConsulta('cliente.selectWhere', array('where' => array('Cli_Cod' => $planta['Cli_Cod'])), $obBD_conexion);
                if (!empty($cliente)) {
                    $personaCliente = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Cod' => $cliente['Prs_Cod'])), $obBD_conexion);
                    if (!empty($personaCliente)) {
                        $resp['cliente'] = array(
                            'Cli_Cod' => $cliente['Cli_Cod'],
                            'Cliente' => $personaCliente['Prs_Nom'] . ' ' . $personaCliente['Prs_Ape'],
                            'Prs_Ced' => $personaCliente['Prs_Ced']
                        );
                    }
                }
            }

            // Obtener personal Administrador (AP)
            $personalAdmin = $obBD_con1->getRowConsulta(
                'manifiesto_personal_planta.selectWhere',
                array('where' => array('Pla_Cod' => $Pla_Cod, 'Pep_Tip' => 'AP')),
                $obBD_conexion
            );
            if (!empty($personalAdmin)) {
                $personaAdmin = $obBD_con1->getRowConsulta(
                    'persona.selectWhere',
                    array('where' => array('Prs_Cod' => $personalAdmin['Prs_Cod'])),
                    $obBD_conexion
                );
                if (!empty($personaAdmin)) {
                    $resp['personalAdmin'] = array_merge($personaAdmin, $personalAdmin);
                }
            }

            // Obtener personal Tributario (AC)
            $personalTrib = $obBD_con1->getRowConsulta(
                'manifiesto_personal_planta.selectWhere',
                array('where' => array('Pla_Cod' => $Pla_Cod, 'Pep_Tip' => 'AC')),
                $obBD_conexion
            );
            if (!empty($personalTrib)) {
                $personaTrib = $obBD_con1->getRowConsulta(
                    'persona.selectWhere',
                    array('where' => array('Prs_Cod' => $personalTrib['Prs_Cod'])),
                    $obBD_conexion
                );
                if (!empty($personaTrib)) {
                    $resp['personalTrib'] = array_merge($personaTrib, $personalTrib);
                }
            }

            // Obtener personal Ambiental (AM)
            $personalAmb = $obBD_con1->getRowConsulta(
                'manifiesto_personal_planta.selectWhere',
                array('where' => array('Pla_Cod' => $Pla_Cod, 'Pep_Tip' => 'AM')),
                $obBD_conexion
            );
            if (!empty($personalAmb)) {
                $personaAmb = $obBD_con1->getRowConsulta(
                    'persona.selectWhere',
                    array('where' => array('Prs_Cod' => $personalAmb['Prs_Cod'])),
                    $obBD_conexion
                );
                if (!empty($personaAmb)) {
                    $resp['personalAmb'] = array_merge($personaAmb, $personalAmb);
                }
            }
        } else {
            $resp['message'] = 'Planta no encontrada';
        }
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    $obBD_con1->utf8_change_param($resp);
    $obBD_con1->echoJson($resp);
}

// Anular Planta
if (isset($anularPlantaAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_plantas.update', array('Pla_Est' => 'I', 'where' => array('Pla_Cod' => $Pla_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Empresas de Transporte
if (isset($listEmpresasTransporteGridAjax)) {
    //require_once('../../Librerias/procedimientos/almacenados_standar.php');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 20;
    // Obtener parámetros de filtro
    $op_opciones = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'n';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $data = array();
    $data[0] = $Ses_Emp_Cod;
    if (!empty($op_opciones) && !empty($search)) {
        $data['op_opciones'] = $op_opciones;
        $data['search'] = $search;
    }

    // Contar total de registros (sin limits)
    $dataCount = $data;
    $contar = $obBD_con1->getRowConsulta(4, $dataCount, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];

    if ($contar['total'] > 0) {
        // Obtener registros con paginación (con limits)
        $data['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(4, $data, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
}

// Guardar Empresa de Transporte
if (isset($saveEmpresaTransporteAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $datosEmpresa = array(
            'Emp_Cod' => $Ses_Emp_Cod,
            'Mat_Des' => $Mat_Des,
            'Mat_Mae' => isset($Mat_Mae) ? $Mat_Mae : '',
            'Mat_Tel' => isset($Mat_Tel) && !empty($Mat_Tel) ? $Mat_Tel : null,
            'Mat_Pco' => isset($Mat_Pco) && !empty($Mat_Pco) ? $Mat_Pco : null,
            'Mat_Dir' => isset($Mat_Dir) && !empty($Mat_Dir) ? $Mat_Dir : null,
            'Mat_Est' => 'A'
        );

        if (!empty($Mat_Cod)) {
            $datosEmpresa['where'] = array('Mat_Cod' => $Mat_Cod);
            $obBD_con1->operacionobBD('manifiesto_transporte.update', $datosEmpresa, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_transporte.insert', $datosEmpresa, $obBD_conexion);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Anular Empresa de Transporte
if (isset($anularEmpresaTransporteAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_transporte.update', array('Mat_Est' => 'I', 'where' => array('Mat_Cod' => $Mat_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Choferes
if (isset($listChoferesGridAjax)) {
    //require_once('../../Librerias/procedimientos/almacenados_standar.php');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 20;
    // Obtener parámetros de filtro
    $op_opciones = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'd';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $params = array();
    $params[0] = $Ses_Emp_Cod;
    if (!empty($op_opciones) && !empty($search)) {
        $params['op_opciones'] = $op_opciones;
        $params['search'] = $search;
    }

    // Contar total de registros (sin limits)
    $paramsCount = $params;
    $contar = $obBD_con1->getRowConsulta(5, $paramsCount, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];

    if ($contar['total'] > 0) {
        // Obtener registros con paginación (con limits)
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(5, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
}

// Buscar persona por cédula
if (isset($buscarPersonaCedulaAjax)) {
    $resp = array('success' => true, 'existe' => false, 'choferExiste' => false);
    $prsAux = $Prs_Ced;
    $longitud = strlen($prsAux);
    if ($longitud * 1 === 13) {
        $prsAux = substr($prsAux, 0, -3);
    }
    $persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Ced' => $prsAux)), $obBD_conexion, true);
    if (!empty($persona)) {
        $resp['existe'] = true;
        $resp['persona'] = $persona;
        $obBD_con1->utf8_change_param($resp['persona']);
        // Verificar si el chofer ya existe en la empresa seleccionada        
        $choferExiste = $obBD_con1->getRowConsulta('chofer.selectWhere', array('where' => array(
            'chofer.Emp_Cod' => $Ses_Emp_Cod,
            'persona.Prs_Ced' => $prsAux,
            'chofer.Cho_Est' => 'A'
        )), $obBD_conexion, true);
        // Verificar si el chofer ya existe en la planta seleccionada
        if (!empty($Pla_Cod)) {
            $choferExiste = $obBD_con1->getRowConsulta(
                'manifiesto_chofer.selectWhere',
                array('where' => array(
                    'manifiesto_chofer.Pla_Cod' => $Pla_Cod,
                    'persona.Prs_Ced' => $prsAux,
                    'chofer.Cho_Est' => 'A'
                )),
                $obBD_conexion,
                true
            );

            if (!empty($choferExiste)) {
                $resp['choferExiste'] = true;
                $resp['chofer'] = $choferExiste;
                // Agregar datos de persona que no vienen en el join
                $resp['chofer']['Prs_Nom'] = isset($persona['Prs_Nom']) ? $persona['Prs_Nom'] : '';
                $resp['chofer']['Prs_Ape'] = isset($persona['Prs_Ape']) ? $persona['Prs_Ape'] : '';
                $resp['chofer']['Prs_Cod'] = isset($persona['Prs_Cod']) ? $persona['Prs_Cod'] : '';
                // Asegurar que Prs_Tel esté disponible
                if (isset($persona['Prs_Tel'])) {
                    $resp['chofer']['Prs_Tel'] = $persona['Prs_Tel'];
                }
                // Aplicar utf8_change_param después de agregar todos los campos
                $obBD_con1->utf8_change_param($resp['chofer']);
            }
        }
    }
    $obBD_con1->echoJson($resp);
}


// Guardar Chofer
if (isset($saveChoferAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        // Validar si el chofer ya existe en la planta (solo para nuevos registros)
        if (empty($Cho_Cod) && !empty($Pla_Cod)) {
            $prsAux = $Cho_Ced;
            $longitud = strlen($prsAux);
            if ($longitud * 1 === 13) {
                $prsAux = substr($prsAux, 0, -3);
            }
            $choferExiste = $obBD_con1->getRowConsulta(
                'manifiesto_chofer.selectWhere',
                array('where' => array(
                    'manifiesto_chofer.Pla_Cod' => $Pla_Cod,
                    'persona.Prs_Ced' => $prsAux,
                    'chofer.Cho_Est' => 'A'
                )),
                $obBD_conexion,
                true
            );

            if (!empty($choferExiste)) {
                $planta = $obBD_con1->getRowConsulta(
                    'manifiesto_plantas.selectWhere',
                    array('where' => array('Pla_Cod' => $Pla_Cod)),
                    $obBD_conexion,
                    true
                );
                $plantaNombre = !empty($planta['Pla_Nom']) ? $planta['Pla_Nom'] : 'la planta seleccionada';
                throw new Exception("El chofer ya existe en " . $plantaNombre . ". No se puede registrar nuevamente.");
            }
        }
        // Si está editando, permitir continuar sin validación de duplicados

        if (!empty($Prs_Cod)) {
            $Prs_Cod_New = $Prs_Cod;
        } else {
            $prsAux = $Cho_Ced;
            $longitud = strlen($prsAux);
            if ($longitud * 1 === 13) {
                $prsAux = substr($prsAux, 0, -3);
            }
            $persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Ced' => $prsAux)), $obBD_conexion);
            if (empty($persona)) {
                $datosPersona = array('Prs_Ced' => $prsAux, 'Prs_Nom' => $Prs_Nom, 'Prs_Ape' => $Prs_Ape, 'Prs_Tel' => $Cho_Tel);
                $obBD_con1->operacionobBD('persona.insert', $datosPersona, $obBD_conexion);
                $Prs_Cod_New = $obBD_con1->insercionid($obBD_conexion);
            } else {
                $Prs_Cod_New = $persona['Prs_Cod'];
            }
        }
        $datosChofer = array(
            'Prs_Cod' => $Prs_Cod_New,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Cho_Tli' => $Cho_Tli,
            'Cho_Cli' => $Cho_Cli,
            'Cho_Tel' => $Cho_Tel,
            'Cho_Tsa' => $Cho_Tsa,
            'Cho_Mae' => '' // Campo oculto, siempre se guarda vacío
        );

        if (!empty($Cho_Cod)) {
            // Editar chofer existente
            $datosChofer['where'] = array('Cho_Cod' => $Cho_Cod);
            $obBD_con1->operacionobBD('chofer.update', $datosChofer, $obBD_conexion, true);
            // Actualizar la relación con la planta (manifiesto_chofer tiene clave primaria compuesta Cho_Cod, Pla_Cod)
            $existeRelacion = $obBD_con1->getRowConsulta('manifiesto_chofer.selectWhere', array('where' => array('manifiesto_chofer.Cho_Cod' => $Cho_Cod, 'manifiesto_chofer.Pla_Cod' => $Pla_Cod)), $obBD_conexion, true);


            if (empty($existeRelacion)) {
                // Si no existe la relación, crearla
                $obBD_con1->operacionobBD('manifiesto_chofer.insert', array('Cho_Cod' => $Cho_Cod, 'Pla_Cod' => $Pla_Cod), $obBD_conexion);
            }
            // Si ya existe la relación, no hacer nada (ya está asociado a esa planta)
        } else {
            $obBD_con1->operacionobBD('chofer.insert', $datosChofer, $obBD_conexion, true);
            $resp['Cho_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
            $obBD_con1->operacionobBD('manifiesto_chofer.insert', array('Cho_Cod' => $resp['Cho_Cod_New'], 'Pla_Cod' => $Pla_Cod), $obBD_conexion);
        }

        $resp['nombre'] = $Prs_Nom . ' ' . $Prs_Ape;
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}



// Anular Chofer
if (isset($anularChoferAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('chofer.update', array('Cho_Est' => 'I', 'where' => array('Cho_Cod' => $Cho_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Vehículos
if (isset($listVehiculosGridAjax)) {
    $data = array_merge($_GET, array('where' => array('vehiculo.Veh_Est' => 'A')));
    // Agregar filtros de búsqueda si existen
    if (isset($_GET['op_opciones']) && isset($_GET['search']) && !empty($_GET['search'])) {
        $data['op_opciones'] = $_GET['op_opciones'];
        $data['search'] = $_GET['search'];
    }
    $obBD_con1->getPageGridJson('manifiesto_vehiculo.selectWhere', $data, $obBD_conexion, true);
}

// Buscar vehículo por placa (para sanciones - búsqueda directa)
if (isset($busqVehiculoPorPlacaAjax)) {
    $placa = isset($_POST['Veh_Pla']) ? trim($Veh_Pla) : '';
    $resp = array('success' => false);
    if (empty($placa)) {
        $resp['message'] = 'Ingrese el número de placa.';
        $obBD_con1->echoJson($resp);
        exit;
    }
    $data = array(
        'where' => array('vehiculo.Veh_Est' => 'A', 'Veh_Pla' => $placa),
        'op_opciones' => 'p',
        'search' => $placa,
        'rows' => 1,
        'page' => 1
    );
    $rows = $obBD_con1->getArrayConsulta('manifiesto_vehiculo.selectWhere', $data, $obBD_conexion);
    if (!empty($rows) && isset($rows[0])) {
        $v = $rows[0];
        $Veh_Cod = (int)$v['Veh_Cod'];
        // Contar sanciones del vehículo en el año actual
        $anioActual = date('Y');
        $countSan = $obBD_con1->getArrayConsultaSql(
            "SELECT COUNT(*) as total FROM manifiesto_sanciones WHERE Msa_Tip = 'VE' AND Veh_Cod = $Veh_Cod AND Msa_Est = 'A' AND YEAR(Msa_Fei) = $anioActual",
            $obBD_conexion
        );
        $cantSanciones = isset($countSan[0]['total']) ? (int)$countSan[0]['total'] : 0;
        $resp = array(
            'success' => true,
            'Veh_Cod' => $Veh_Cod,
            'Veh_Pla' => isset($v['Veh_Pla']) ? $v['Veh_Pla'] : '',
            'Veh_Mar' => isset($v['Veh_Mar']) ? $v['Veh_Mar'] : '',
            'SancionesAnio' => $cantSanciones,
            'Anio' => $anioActual
        );
    } else {
        $resp['message'] = 'Vehículo no encontrado.';
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Obtener cantidad de sanciones de un vehículo en el año actual (para mostrar al editar)
if (isset($getCountSancionesVehiculoAjax)) {
    $Veh_Cod = isset($_POST['Veh_Cod']) ? (int)$_POST['Veh_Cod'] : 0;
    $resp = array('success' => false, 'SancionesAnio' => 0, 'Anio' => date('Y'));
    if ($Veh_Cod > 0) {
        $anioActual = date('Y');
        $countSan = $obBD_con1->getArrayConsultaSql(
            "SELECT COUNT(*) as total FROM manifiesto_sanciones WHERE Msa_Tip = 'VE' AND Veh_Cod = $Veh_Cod AND Msa_Est = 'A' AND YEAR(Msa_Fei) = $anioActual",
            $obBD_conexion
        );
        $resp = array(
            'success' => true,
            'SancionesAnio' => isset($countSan[0]['total']) ? (int)$countSan[0]['total'] : 0,
            'Anio' => $anioActual
        );
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Obtener cantidad de sanciones de un chofer en el año actual (para mostrar al buscar/editar)
if (isset($getCountSancionesChoferAjax)) {
    $Cho_Cod = isset($_POST['Cho_Cod']) ? (int)$_POST['Cho_Cod'] : 0;
    $resp = array('success' => false, 'SancionesAnio' => 0, 'Anio' => date('Y'));
    if ($Cho_Cod > 0) {
        $anioActual = date('Y');
        $countSan = $obBD_con1->getArrayConsultaSql(
            "SELECT COUNT(*) as total FROM manifiesto_sanciones WHERE Msa_Tip = 'CH' AND Cho_Cod = $Cho_Cod AND Msa_Est = 'A' AND YEAR(Msa_Fei) = $anioActual",
            $obBD_conexion
        );
        $resp = array(
            'success' => true,
            'SancionesAnio' => isset($countSan[0]['total']) ? (int)$countSan[0]['total'] : 0,
            'Anio' => $anioActual
        );
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Obtener cantidad de sanciones de una planta en el año actual (para mostrar al buscar/editar)
if (isset($getCountSancionesPlantaAjax)) {
    $Pla_Cod = isset($_POST['Pla_Cod']) ? (int)$_POST['Pla_Cod'] : 0;
    $resp = array('success' => false, 'SancionesAnio' => 0, 'Anio' => date('Y'));
    if ($Pla_Cod > 0) {
        $anioActual = date('Y');
        $countSan = $obBD_con1->getArrayConsultaSql(
            "SELECT COUNT(*) as total FROM manifiesto_sanciones WHERE Msa_Tip = 'PL' AND Pla_Cod = $Pla_Cod AND Msa_Est = 'A' AND YEAR(Msa_Fei) = $anioActual",
            $obBD_conexion
        );
        $resp = array(
            'success' => true,
            'SancionesAnio' => isset($countSan[0]['total']) ? (int)$countSan[0]['total'] : 0,
            'Anio' => $anioActual
        );
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Listar Sanciones (unificado: VE, CH, PL) ? SQL directo. Tipo "Todos" + filtro_nombres vacío = todos los registros activos.
if (isset($_REQUEST['listSancionesGridAjax']) || isset($listSancionesGridAjax)) {
    $req = array_merge($_GET, $_POST);
    $page = isset($req['page']) ? (int)$req['page'] : 1;
    $rows = isset($req['rows']) ? (int)$req['rows'] : 100;
    $filtroTipo = isset($req['filtro_tipo']) ? trim((string)$req['filtro_tipo']) : '';
    $filtroVigentes = isset($req['filtro_vigentes']) && $req['filtro_vigentes'] === '1';
    $filtroId = isset($req['filtro_identificacion']) ? trim((string)$req['filtro_identificacion']) : '';
    $filtroNom = isset($req['filtro_nombres']) ? trim((string)$req['filtro_nombres']) : '';

    $nm = 'manifiesto_sanciones';
    $con = $obBD_con1->getMyCon($obBD_conexion);

    $where = array();
    $obBD_con1->setError(0, '');
    @$obBD_con1->getRowConsultaSql("SELECT $nm.Msa_Est FROM $nm LIMIT 1", $obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $where[] = "$nm.Msa_Est = 'A'";
    }
    if ($filtroTipo !== '' && in_array($filtroTipo, array('VE', 'CH', 'PL'))) {
        $escTipo = mysqli_real_escape_string($con, $filtroTipo);
        $where[] = "($nm.Msa_Tip = '$escTipo')";
    }
    if ($filtroVigentes) {
        $where[] = "NOW() >= $nm.Msa_Fei AND NOW() <= $nm.Msa_Fef";
    }
    // Filtros de búsqueda: por tipo (CH/VE/PL) se usan columnas explícitas; siempre se aplica LIKE (incluso con búsqueda vacía)
    $escId  = $filtroId !== '' ? mysqli_real_escape_string($con, $filtroId) : '';
    $escNom = $filtroNom !== '' ? mysqli_real_escape_string($con, $filtroNom) : '';
    $condSearch = array();
    if ($filtroTipo === 'CH') {
        if ($escId !== '') {
            $condSearch[] = "persona_ch.Prs_Ced LIKE '%$escId%'";
        }
        if ($escNom !== '') {
            $condSearch[] = "CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,'')) LIKE '%$escNom%'";
        }
        if (count($condSearch) === 0) {
            $condSearch[] = "COALESCE(persona_ch.Prs_Ced,'') LIKE '%'";
        }
    } elseif ($filtroTipo === 'VE') {
        if ($escId !== '') {
            $condSearch[] = "vehiculo.Veh_Pla LIKE '%$escId%'";
        }
        if ($escNom !== '') {
            $condSearch[] = "vehiculo.Veh_Pla LIKE '%$escNom%'";
        }
        if (count($condSearch) === 0) {
            $condSearch[] = "COALESCE(vehiculo.Veh_Pla,'') LIKE '%'";
        }
    } elseif ($filtroTipo === 'PL') {
        if ($escId !== '') {
            $condSearch[] = "persona_pl.Prs_Ced LIKE '%$escId%'";
        }
        if ($escNom !== '') {
            $condSearch[] = "manifiesto_plantas.Pla_Nom LIKE '%$escNom%'";
        }
        if (count($condSearch) === 0) {
            $condSearch[] = "COALESCE(manifiesto_plantas.Pla_Nom,'') LIKE '%'";
        }
    } else {
        // Tipo "Todos"
        if ($escId !== '') {
            $condSearch[] = "(COALESCE(persona_ch.Prs_Ced, persona_pl.Prs_Ced, vehiculo.Veh_Pla) LIKE '%$escId%')";
        }
        if ($escNom !== '') {
            $condSearch[] = "(COALESCE(vehiculo.Veh_Pla, manifiesto_plantas.Pla_Nom, CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,''))) LIKE '%$escNom%')";
        }
        if (count($condSearch) === 0) {
            $condSearch[] = "(COALESCE(persona_ch.Prs_Ced, persona_pl.Prs_Ced, vehiculo.Veh_Pla) LIKE '%')";
        }
    }
    if (count($condSearch) > 0) {
        $where[] = '(' . implode(' OR ', $condSearch) . ')';
    }
    $whereSql = count($where) > 0 ? implode(' AND ', $where) : '1=1';

    $sel = "SELECT $nm.Msa_Cod, $nm.Msa_Tip, $nm.Veh_Cod, $nm.Cho_Cod, $nm.Pla_Cod, $nm.Msa_Fei, $nm.Msa_Fef, $nm.Msa_Obs,
        COALESCE(concat(vehiculo.Veh_Pla,' ',vehiculo.Veh_Mar), manifiesto_plantas.Pla_Nom, CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,''))) AS Identificador,
        COALESCE(persona_ch.Prs_Ced, persona_pl.Prs_Ced, vehiculo.Veh_Pla) AS Prs_Ced,
        COALESCE(CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,'')), CONCAT(IFNULL(persona_pl.Prs_Nom,''),' ',IFNULL(persona_pl.Prs_Ape,''))) AS Prs_Nom
        FROM $nm
        LEFT JOIN vehiculo ON vehiculo.Veh_Cod = $nm.Veh_Cod AND $nm.Msa_Tip = 'VE'
        LEFT JOIN chofer ON chofer.Cho_Cod = $nm.Cho_Cod AND $nm.Msa_Tip = 'CH'
        LEFT JOIN persona AS persona_ch ON persona_ch.Prs_Cod = chofer.Prs_Cod
        LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = $nm.Pla_Cod AND $nm.Msa_Tip = 'PL'
        LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
        LEFT JOIN persona AS persona_pl ON persona_pl.Prs_Cod = cliente.Prs_Cod
        WHERE $whereSql";

    $countSql = "SELECT COUNT(*) AS total FROM ($sel) AS _cnt";
    $contar = $obBD_con1->getRowConsultaSql($countSql, $obBD_conexion);
    $total = isset($contar['total']) ? (int)$contar['total'] : 0;
    $pagination = pages($total, $page, $rows);
    $response = $pagination['data'];

    if ($total > 0) {
        $limits = $pagination['limits'];
        $sql = $sel . " ORDER BY $nm.Msa_Fei DESC " . ($limits ? $limits : '');
        $response['rows'] = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
        if (is_array($response['rows'])) {
            $obBD_con1->utf8_change_param($response['rows']);
        } else {
            $response['rows'] = array();
        }
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Guardar Sanción Vehículo
if (isset($saveSancionVehiculoAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $Msa_Cod = isset($_POST['Msa_Cod']) ? trim($_POST['Msa_Cod']) : '';
        $Veh_Cod = isset($_POST['Veh_Cod']) ? (int)$_POST['Veh_Cod'] : 0;
        $Msa_Fei = isset($_POST['Msa_Fei']) ? $_POST['Msa_Fei'] : '';
        $Msa_Fef = isset($_POST['Msa_Fef']) ? $_POST['Msa_Fef'] : '';
        $Msa_Obs = isset($_POST['Msa_Obs']) ? trim($_POST['Msa_Obs']) : '';
        if (empty($Veh_Cod)) {
            throw new Exception('Debe seleccionar un vehículo.');
        }
        if (empty($Msa_Fei) || empty($Msa_Fef)) {
            throw new Exception('Fecha inicio y fin son obligatorias.');
        }
        $datos = array(
            'Msa_Tip' => 'VE',
            'Veh_Cod' => $Veh_Cod,
            'Msa_Fei' => $Msa_Fei,
            'Msa_Fef' => $Msa_Fef,
            'Msa_Obs' => $Msa_Obs
        );
        if (!empty($Msa_Cod)) {
            $datos['where'] = array('Msa_Cod' => $Msa_Cod);
            $obBD_con1->operacionobBD('manifiesto_sanciones.update', $datos, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_sanciones.insert', $datos, $obBD_conexion);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Guardar Sanción Chofer
if (isset($saveSancionChoferAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $Msa_Cod = isset($_POST['Msa_Cod']) ? trim($_POST['Msa_Cod']) : '';
        $Cho_Cod = isset($_POST['Cho_Cod']) ? (int)$_POST['Cho_Cod'] : 0;
        $Msa_Fei = isset($_POST['Msa_Fei']) ? $_POST['Msa_Fei'] : '';
        $Msa_Fef = isset($_POST['Msa_Fef']) ? $_POST['Msa_Fef'] : '';
        $Msa_Obs = isset($_POST['Msa_Obs']) ? trim($_POST['Msa_Obs']) : '';
        if (empty($Cho_Cod)) {
            throw new Exception('Debe seleccionar un chofer.');
        }
        if (empty($Msa_Fei) || empty($Msa_Fef)) {
            throw new Exception('Fecha inicio y fin son obligatorias.');
        }
        $datos = array(
            'Msa_Tip' => 'CH',
            'Cho_Cod' => $Cho_Cod,
            'Msa_Fei' => $Msa_Fei,
            'Msa_Fef' => $Msa_Fef,
            'Msa_Obs' => $Msa_Obs
        );
        if (!empty($Msa_Cod)) {
            $datos['where'] = array('Msa_Cod' => $Msa_Cod);
            $obBD_con1->operacionobBD('manifiesto_sanciones.update', $datos, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_sanciones.insert', $datos, $obBD_conexion);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Guardar Sanción Planta
if (isset($saveSancionPlantaAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $Msa_Cod = isset($_POST['Msa_Cod']) ? trim($_POST['Msa_Cod']) : '';
        $Pla_Cod = isset($_POST['Pla_Cod']) ? (int)$_POST['Pla_Cod'] : 0;
        $Msa_Fei = isset($_POST['Msa_Fei']) ? $_POST['Msa_Fei'] : '';
        $Msa_Fef = isset($_POST['Msa_Fef']) ? $_POST['Msa_Fef'] : '';
        $Msa_Obs = isset($_POST['Msa_Obs']) ? trim($_POST['Msa_Obs']) : '';
        if (empty($Pla_Cod)) {
            throw new Exception('Debe seleccionar una planta.');
        }
        if (empty($Msa_Fei) || empty($Msa_Fef)) {
            throw new Exception('Fecha inicio y fin son obligatorias.');
        }
        $datos = array(
            'Msa_Tip' => 'PL',
            'Pla_Cod' => $Pla_Cod,
            'Msa_Fei' => $Msa_Fei,
            'Msa_Fef' => $Msa_Fef,
            'Msa_Obs' => $Msa_Obs
        );
        if (!empty($Msa_Cod)) {
            $datos['where'] = array('Msa_Cod' => $Msa_Cod);
            $obBD_con1->operacionobBD('manifiesto_sanciones.update', $datos, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_sanciones.insert', $datos, $obBD_conexion);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Anular Sanción
if (isset($anularSancionAjax)) {
    $resp = array('success' => false);
    $Msa_Cod = isset($_POST['Msa_Cod']) ? trim($_POST['Msa_Cod']) : '';
    if (empty($Msa_Cod)) {
        $resp['message'] = 'Código de sanción no válido.';
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_sanciones.update', array('Msa_Est' => 'I', 'where' => array('Msa_Cod' => $Msa_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Transportes
if (isset($listTransportesAjax)) {
    $resp = array('success' => true);
    $resp['transportes'] = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('where' => array('manifiesto_transporte.Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion, true);
    $obBD_con1->echoJson($resp);
}

// Listar Clientes para selector
if (isset($listClientesAjax)) {
    $resultado = array('success' => true);
    $resultado['rows'] = $obBD_con1->getArrayConsulta('cliente.selectWhere', array_merge($_GET, array()), $obBD_conexion, true);
    $obBD_con1->utf8_change_param($resultado['rows']);
    $obBD_con1->echoJson($resultado);
}

// Guardar Vehículo
if (isset($saveVehiculoAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $datos = array(
            'Veh_Mar' => $Veh_Mar,
            'Veh_Pla' => $Veh_Pla,
            'Veh_Col' => $Veh_Col,
            'Veh_Cap' => $Veh_Cap,
            'Veh_Tit' => $Veh_Tit,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Veh_Tip' => 'VM',
            'Mat_Cod' => $Mat_Cod
        );
        if (!empty($Veh_Cod)) {
            $datos['where'] = array('Veh_Cod' => $Veh_Cod);
            $obBD_con1->operacionobBD('vehiculo.update', $datos, $obBD_conexion, true);
        } else {
            $obBD_con1->operacionobBD('vehiculo.insert', $datos, $obBD_conexion, true);
            $resp['Veh_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
            $obBD_con1->operacionobBD('manifiesto_vehiculo.insert', array('Veh_Cod' => $resp['Veh_Cod_New'], 'Pla_Cod' => $Pla_Cod), $obBD_conexion, true);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Anular Vehículo
if (isset($anularVehiculoAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('vehiculo.update', array('Veh_Est' => 'I', 'where' => array('Veh_Cod' => $Veh_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Celdas (solo grupos para el grid principal con subGrid)
if (isset($listCeldasGridAjax)) {
    // Obtener solo los grupos (excluir eliminados, estado != 'E')
    $grupos = $obBD_con1->getArrayConsulta(
        'manifiesto_celdas.selectWhere',
        array('where' => array(
            'manifiesto_celdas.Cel_Tip' => 'G',
            'manifiesto_celdas.Emp_Cod' => $Ses_Emp_Cod
        )),
        $obBD_conexion,
        true
    );

    // Filtrar manualmente para excluir eliminados
    $grupos = array_filter($grupos, function ($g) {
        return isset($g['Cel_Est']) && $g['Cel_Est'] != 'E';
    });

    // Aplicar filtros de búsqueda si existen
    if (isset($_GET['op_opciones']) && isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = $_GET['search'];
        $op_opciones = $_GET['op_opciones'];

        // Filtrar grupos
        if ($op_opciones == 'n') {
            $grupos = array_filter($grupos, function ($g) use ($searchTerm) {
                return stripos($g['Cel_Nom'], $searchTerm) !== false;
            });
        }
    }

    // Preparar respuesta en formato jqGrid (solo grupos)
    foreach ($grupos as &$grupo) {
        $obBD_con1->utf8_change_param($grupo);
    }

    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rowsPerPage = isset($_GET['rows']) ? intval($_GET['rows']) : 10000;
    $total = count($grupos);
    $totalPages = ceil($total / $rowsPerPage);
    $start = ($page - 1) * $rowsPerPage;
    $paginatedRows = array_slice($grupos, $start, $rowsPerPage);

    $resp = array(
        'page' => $page,
        'total' => $totalPages,
        'records' => $total,
        'rows' => $paginatedRows
    );

    $obBD_con1->echoJson($resp);
}

// Listar Detalles de un Grupo (para subGrid)
if (isset($listCeldasDetalleAjax)) {
    $grupoCod = isset($_GET['grupo_cod']) ? intval($_GET['grupo_cod']) : 0;

    // Obtener detalles del grupo
    $detalles = $obBD_con1->getArrayConsulta(
        'manifiesto_celdas.selectWhere',
        array('where' => array(
            'manifiesto_celdas.Cel_Tip' => 'D',
            'manifiesto_celdas.Cel_Rec' => $grupoCod,
            'manifiesto_celdas.Emp_Cod' => $Ses_Emp_Cod
        )),
        $obBD_conexion,
        true
    );

    // Filtrar manualmente para excluir eliminados
    $detalles = array_filter($detalles, function ($d) {
        return isset($d['Cel_Est']) && $d['Cel_Est'] != 'E';
    });

    // Preparar respuesta en formato jqGrid
    foreach ($detalles as &$detalle) {
        $obBD_con1->utf8_change_param($detalle);
    }

    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rowsPerPage = isset($_GET['rows']) ? intval($_GET['rows']) : 10000;
    $total = count($detalles);
    $totalPages = ceil($total / $rowsPerPage);
    $start = ($page - 1) * $rowsPerPage;
    $paginatedRows = array_slice($detalles, $start, $rowsPerPage);

    $resp = array(
        'page' => $page,
        'total' => $totalPages,
        'records' => $total,
        'rows' => $paginatedRows
    );

    $obBD_con1->echoJson($resp);
}

// Listar Grupos (para combo box de detalles)
if (isset($listGruposCeldasAjax)) {
    $resp = array('success' => true);
    $grupos = $obBD_con1->getArrayConsulta(
        'manifiesto_celdas.selectWhere',
        array('where' => array(
            'manifiesto_celdas.Cel_Tip' => 'G',
            'manifiesto_celdas.Cel_Est' => 'A',
            'manifiesto_celdas.Emp_Cod' => $Ses_Emp_Cod
        )),
        $obBD_conexion,
        true
    );
    $obBD_con1->utf8_change_param($grupos);
    $resp['grupos'] = $grupos;
    $obBD_con1->echoJson($resp);
}

// Guardar Celda
if (isset($saveCeldaAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        // Si es Detalle, validar que tenga grupo seleccionado
        if ($Cel_Tip == 'D' && empty($Cel_Rec)) {
            throw new Exception("Debe seleccionar un grupo para el detalle.");
        }
        $datos = array(
            'Cel_Nom' => $Cel_Nom,
            'Cel_Tip' => $Cel_Tip,
            'Emp_Cod' => $Ses_Emp_Cod,
            'Cel_Est' => 'A'
        );
        // Solo agregar estos campos si es Detalle
        if ($Cel_Tip == 'D') {
            $datos['Cel_Rec'] = !empty($Cel_Rec) ? $Cel_Rec : 0;
            $datos['Cel_Num'] = !empty($Cel_Num) ? $Cel_Num : '';
            $datos['Cel_Ubi'] = !empty($Cel_Ubi) ? $Cel_Ubi : '';
        } else {
            // Si es Grupo, limpiar estos campos
            $datos['Cel_Rec'] = 0;
            $datos['Cel_Num'] = '';
            $datos['Cel_Ubi'] = '';
        }
        if (!empty($Cel_Cod)) {
            $datos['where'] = array('Cel_Cod' => $Cel_Cod);
            $obBD_con1->operacionobBD('manifiesto_celdas.update', $datos, $obBD_conexion, true);
        } else {
            $obBD_con1->operacionobBD('manifiesto_celdas.insert', $datos, $obBD_conexion, true);
            $resp['Cel_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Listar Plantas para dropdowns (Chofer y Vehículo)
if (isset($listPlantasSelectAjax)) {
    $resp = array('success' => true);
    $plantas = $obBD_con1->getArrayConsulta(
        'manifiesto_plantas.selectWhere',
        array('where' => array('Pla_Est' => 'A')),
        $obBD_conexion,
        true
    );
    $obBD_con1->utf8_change_param($plantas);
    $resp['plantas'] = $plantas;
    $obBD_con1->echoJson($resp);
}

// Anular Celda
if (isset($anularCeldaAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_celdas.update', array('Cel_Est' => 'I', 'where' => array('Cel_Cod' => $Cel_Cod)), $obBD_conexion, true);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Activar Celda
if (isset($activarCeldaAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_celdas.update', array('Cel_Est' => 'A', 'where' => array('Cel_Cod' => $Cel_Cod)), $obBD_conexion, true);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Eliminar Celda (cambiar estado a 'E')
if (isset($eliminarCeldaAjax)) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_celdas.update', array('Cel_Est' => 'E', 'where' => array('Cel_Cod' => $Cel_Cod)), $obBD_conexion, true);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
}

// Obtener ciudades
$ciudades = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('setWhere' => array('getProvincia', 'getPais')), $obBD_conexion, true);
$obBD_con1->utf8_change_param($ciudades);

// Obtener transportes de la empresa
$transportes = array();
$transportes = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('where' => array('manifiesto_transporte.Emp_Cod' => $Ses_Emp_Cod, 'manifiesto_transporte.Mat_Est' => 'A')), $obBD_conexion, true);
$obBD_con1->utf8_change_param($transportes);
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?php echo "Administración - Configuración [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script>
        var Cli_Cod = '<?php echo $cliente_manifiesto['Cli_Cod']; ?>';
    </script>
    <style>
        /* Estilos para filas de grupos y detalles en el grid de celdas */
        .fila-grupo {
            font-weight: bold !important;
        }

        .fila-detalle {
            padding-left: 15px !important;
        }

        .fila-detalle:hover {
            background-color: #d0e7ff !important;
        }

        /* Filas inactivas (anuladas) */
        .fila-inactiva {
            background-color: #eee !important;
            color: #777 !important;
            opacity: 0.9;
        }

        .fila-inactiva td {
            background-color: #eee !important;
            color: #777 !important;
        }

        /* Asegurar que los detalles tengan indentación visual */
        #gridCeldas tr.fila-detalle td {
            padding-left: 25px !important;
        }

        #gridCeldas tr.fila-detalle td:first-child {
            padding-left: 15px !important;
        }

        .nav-tabs-custom {
            margin-bottom: 20px;
        }

        .nav-tabs-custom>.nav-tabs {
            border-bottom: 3px solid #3c8dbc;
        }

        .nav-tabs-custom>.nav-tabs>li {
            margin-right: 5px;
        }

        .nav-tabs-custom>.nav-tabs>li>a {
            border-radius: 5px 5px 0 0;
            color: #444;
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-bottom: none;
            padding: 10px 20px;
            font-weight: bold;
        }

        .nav-tabs-custom>.nav-tabs>li.active>a {
            background: #3c8dbc;
            color: white;
            border-color: #3c8dbc;
        }

        .nav-tabs-custom>.nav-tabs>li>a:hover {
            background: #e9ecef;
        }

        .nav-tabs-custom>.nav-tabs>li.active>a:hover {
            background: #367fa9;
            color: white;
        }

        .tab-content {
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }

        .tab-pane {
            min-height: 200px;
        }

        .btn-toolbar {
            margin-bottom: 15px;
        }

        .panel-config {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .icon-tab {
            margin-right: 8px;
            font-size: 16px;
        }

        .info-cliente {
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            background: #86b6da;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-cliente h4 {
            margin: 0 0 10px 0;
        }

        .info-cliente p {
            margin: 0;
            opacity: 0.9;
        }
    </style>
</HEAD>

<body>
    <div class="panel panel-main panel-config">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Administración de Configuración</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">

            <!-- Información del Cliente -->
            <!--div class="info-cliente">
                <h4><i class="glyphicon glyphicon-user"></i> Cliente Asociado</h4>
                <p><strong>Código:</strong> <span id="codigoClienteHeader"><?php echo $cliente_manifiesto['Cli_Cod']; ?></span> |
                    <strong>Nombre:</strong> <span id="nombreClienteHeader"><?php echo isset($cliente_manifiesto['nombre']) ? $cliente_manifiesto['nombre'] : 'N/A'; ?></span>
                </p>
            </div-->

            <!-- Pestañas -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tabPlantas" aria-controls="tabPlantas" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-home icon-tab"></i>Plantas
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabEmpresasTransporte" aria-controls="tabEmpresasTransporte" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-truck icon-tab"></i>Empresas Transporte
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabChoferes" aria-controls="tabChoferes" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-user icon-tab"></i>Choferes
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabVehiculos" aria-controls="tabVehiculos" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-road icon-tab"></i>Vehículos
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabCeldas" aria-controls="tabCeldas" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-th-large icon-tab"></i>Celdas
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tabSanciones" aria-controls="tabSanciones" role="tab" data-toggle="tab">
                            <i class="glyphicon glyphicon-ban-circle icon-tab"></i>Sanciones
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab Plantas -->
                    <div role="tabpanel" class="tab-pane active" id="tabPlantas">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalPlanta();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Planta
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridPlantas();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroPlantasForm" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radPlanta1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radPlanta1">Nombres Cliente</label>
                                                <input id="radPlanta2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radPlanta2">Cédula/RUC</label>
                                                <input id="radPlanta3" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search)" />
                                                <label for="radPlanta3">Nombre Planta</label>
                                                <input id="radPlanta4" name="op_opciones" type="radio" value="l" onclick="setfocus(this.form.search)" />
                                                <label for="radPlanta4">Licencia</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridPlantas(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridPlantas();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <table id="gridPlantas"></table>
                        <div id="gridPlantasPager"></div>
                    </div>



                    <!-- Tab Empresas Transporte -->
                    <div role="tabpanel" class="tab-pane" id="tabEmpresasTransporte">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalEmpresaTransporte();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Empresa
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridEmpresasTransporte();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroEmpresasTransporteForm" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radTransporte1" name="op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radTransporte1">Nombre</label>
                                                <input id="radTransporte2" name="op_opciones" type="radio" value="m" onclick="setfocus(this.form.search)" />
                                                <label for="radTransporte2">Licencia MAE</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridEmpresasTransporte(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridEmpresasTransporte();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <table id="gridEmpresasTransporte"></table>
                        <div id="gridEmpresasTransportePager"></div>
                    </div>

                    <!-- Tab Choferes -->
                    <div role="tabpanel" class="tab-pane" id="tabChoferes">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalChofer();">
                                <i class="glyphicon glyphicon-plus"></i> Nuevo Chofer
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridChoferes();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroChoferesForm" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radChofer1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer1">Nombre Chofer</label>
                                                <input id="radChofer2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer2">Cédula</label>
                                                <input id="radChofer3" name="op_opciones" type="radio" value="pn" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer3">Nombre Planta</label>
                                                <input id="radChofer4" name="op_opciones" type="radio" value="pl" onclick="setfocus(this.form.search)" />
                                                <label for="radChofer4">Licencia Planta</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridChoferes(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridChoferes();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <table id="gridChoferes"></table>
                        <div id="gridChoferesPager"></div>
                    </div>

                    <!-- Tab Vehículos -->
                    <div role="tabpanel" class="tab-pane" id="tabVehiculos">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalVehiculo();">
                                <i class="glyphicon glyphicon-plus"></i> Nuevo Vehículo
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridVehiculos();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroVehiculosForm" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radVehiculo1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo1">Placa</label>
                                                <input id="radVehiculo2" name="op_opciones" type="radio" value="pn" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo2">Nombre Planta</label>
                                                <input id="radVehiculo3" name="op_opciones" type="radio" value="pl" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo3">Licencia Planta</label>
                                                <input id="radVehiculo4" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radVehiculo4">Cédula/RUC Cliente</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridVehiculos(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridVehiculos();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <table id="gridVehiculos"></table>
                        <div id="gridVehiculosPager"></div>
                    </div>

                    <!-- Tab Celdas -->
                    <div role="tabpanel" class="tab-pane" id="tabCeldas">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalCelda();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Celda
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridCeldas();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroCeldasForm" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-10 radioset opt_search">
                                                <input id="radCelda1" name="op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="radCelda1">Nombre</label>
                                                <input id="radCelda2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                                <label for="radCelda2">Código/Número</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridCeldas(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridCeldas();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <table id="gridCeldas"></table>
                        <div id="gridCeldasPager"></div>
                    </div>
                    <!-- Tab Sanciones -->
                    <div role="tabpanel" class="tab-pane" id="tabSanciones">
                        <div class="btn-toolbar">
                            <button class="btn btn-success" onclick="abrirModalSancionVehiculo();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Sanción Vehículo
                            </button>
                            <button class="btn btn-success" onclick="abrirModalSancionChofer();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Sanción Chofer
                            </button>
                            <button class="btn btn-success" onclick="abrirModalSancionPlanta();">
                                <i class="glyphicon glyphicon-plus"></i> Nueva Sanción Planta
                            </button>
                            <button class="btn btn-default" onclick="actualizarGridSanciones();">
                                <i class="glyphicon glyphicon-refresh"></i> Actualizar
                            </button>
                        </div>
                        <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroSancionesForm" class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Tipo:</label>
                                            <div class="col-xs-3">
                                                <select name="filtro_tipo" id="filtro_tipo_sanciones" class="form-control input-xs">
                                                    <option value="T">Todos</option>
                                                    <option value="VE">Vehículos</option>
                                                    <option value="CH">Choferes</option>
                                                    <option value="PL">Plantas</option>
                                                </select>
                                            </div>
                                            <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-3 radioset opt_search">
                                                <input id="radSancion1" name="op_opciones" type="radio" value="i" checked="checked" onclick="setfocus(this.form.search)" />
                                                <label for="radSancion1">Identificación</label>
                                                <input id="radSancion2" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search)" />
                                                <label for="radSancion2">Nombre/Apellido</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Búsqueda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <input name="search" type="text" id="search_sanciones" size="50" maxlength="80" placeholder="Ingrese búsqueda..." class="form-control input-xs clearable" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridSanciones(); }" />
                                                    <span class="input-group-btn">
                                                        <button type="button" onclick="actualizarGridSanciones();" class="btn btn-success btn-xs" title="Buscar">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-xs-2">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" name="filtro_vigentes" id="filtro_vigentes_sanciones" value="1" />
                                                    Solo vigentes
                                                </label>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <table id="gridSanciones"></table>
                        <div id="gridSancionesPager"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Planta -->
    <div id="plantaDialog" title="Registrar Planta" style="display: none;">
        <ul class="nav nav-tabs" role="tablist" id="plantaTabs">
            <li class="active"><a href="#tabDatosPlanta" role="tab" data-toggle="tab">Datos Planta</a></li>
            <li><a href="#tabAdminPlanta" role="tab" data-toggle="tab">Admin. Planta</a></li>
            <li><a href="#tabTributario" role="tab" data-toggle="tab">Tributario</a></li>
            <li><a href="#tabAmbiental" role="tab" data-toggle="tab">Ambiental</a></li>
        </ul>
        <div class="tab-content" style="margin-top:10px;">
            <!-- TAB DATOS PLANTA -->
            <div class="tab-pane fade in active" id="tabDatosPlanta">
                <div class="row">
                    <form id="plantaForm" class="form-horizontal normal">
                        <input type="hidden" id="Pla_Cod" name="Pla_Cod">

                        <legend class="Titulos2">Datos del Cliente</legend>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Cédula/RUC:</label>
                            <div class="col-xs-8">
                                <input name="Cli_Cod" data-name="Cli_Cod" type="text" style="display:none;" />
                                <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                <div class="input-group input-group-xs">
                                    <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#cliDialog',selectCliente); }" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                    <span class="input-group-btn">
                                        <button id="Prv_Btn" type="button" onclick="$('#cliDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Cliente:</label>
                            <div class="col-xs-8">
                                <span name="Cliente" data-name="Cliente" class="form-control input-xs databind datatitle"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Nombre Planta:</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Nom" name="Pla_Nom" class="form-control input-xs" required placeholder="Nombre de la planta" maxlength="50">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Cod.Arcon:</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Car" name="Pla_Car" class="form-control input-xs" required placeholder="Nombre de la planta" maxlength="50">
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Ciudad:</label>
                            <div class="col-xs-8">
                                <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs chosen-select" data-placeholder="Seleccione ciudad..." required>
                                    <option value=""></option>
                                    <?php foreach ($ciudades as $row) { ?>
                                        <option value="<?php echo $row['Ciu_Cod']; ?>" data-prov="<?php echo $row['Pro_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Nro. Licencia Ambiental:</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Lic" name="Pla_Lic" class="form-control input-xs" required placeholder="Número de licencia" maxlength="20">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Dirección:</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Dir" name="Pla_Dir" class="form-control input-xs" required placeholder="Dirección de la planta" maxlength="100">
                            </div>
                        </div>



                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Ubicacion Geografica:</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Geo" name="Pla_Geo" class="form-control input-xs" required placeholder="Ubicacion Geografica coordenadas(X,Y)" maxlength="50">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Tiempo en horas hasta la relavera:</label>
                            <div class="col-xs-8">
                                <input type="time" id="Pla_Dis" name="Pla_Dis" class="form-control input-xs" required placeholder="Tiempo en horas hasta la relavera" maxlength="50">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Capacidad Planta :</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Cap" name="Pla_Cap" class="form-control input-xs" required placeholder="Capacidad Planta" maxlength="50">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Nro.Gen.Desechos peligrosos :</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Crd" name="Pla_Crd" class="form-control input-xs" required placeholder="Nro.Gen.Desechos peligrosos " maxlength="50">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Nro.Aut. para la operacion de la planta :</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Cau" name="Pla_Cau" class="form-control input-xs" required placeholder="Nro.Aut. para la operacion de la planta" maxlength="50">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Ruta a la Relavera :</label>
                            <div class="col-xs-8">
                                <input type="text" id="Pla_Rut" name="Pla_Rut" class="form-control input-xs" required placeholder="Ruta desde la planta a la relavera" maxlength="50">
                            </div>
                        </div>



                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Fec.emi. regis.minero :</label>
                            <div class="col-xs-8">
                                <input type="date" id="Pla_Fem" name="Pla_Fem" value="<?php echo date('Y-m-d'); ?>" class="form-control input-xs" placeholder="Fecha emision regis.minero " maxlength="50">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Fec.ven. regis.minero :</label>
                            <div class="col-xs-8">
                                <input type="date" id="Pla_Fve" name="Pla_Fve" class="form-control input-xs" placeholder="Fecha vencimiento regis.minero" maxlength="50">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Periodo de Facturación:</label>
                            <div class="col-xs-8">
                                <select id="Pla_Pfa" name="Pla_Pfa" class="form-control input-xs">
                                    <option value="">-- Seleccione --</option>
                                    <option value="D">Diario</option>
                                    <option value="S">Semanal</option>
                                    <option value="Q">Quincenal</option>
                                    <option value="M">Mensual</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Mensaje por WhatsApp:</label>
                            <div class="col-xs-8">
                                <select id="Pla_Wat" name="Pla_Wat" class="form-control input-xs">
                                    <option value="N">No</option>
                                    <option value="S">Sí</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- TAB ADMIN PLANTA -->
            <div class="tab-pane fade" id="tabAdminPlanta">
                <form id="adminPlantaForm" class="form-horizontal normal">
                    <input type="hidden" id="Prs_Cod_Admin" name="Prs_Cod">
                    <div class="form-group">
                        <input type="hidden" id="Pep_Tip" name="Pep_Tip" value="AP">
                        <label class="col-xs-4 control-label label-xs">Identificacion Admin:</label>
                        <div class="col-xs-8">
                            <div class="input-group input-group-xs">
                                <input type="text" id="Prs_Ced" name="Prs_Ced" class="form-control input-xs" placeholder="Cédula o RUC" maxlength="13" onchange="buscarPersonaAdminPlanta(this.value)" onkeypress="return validar_numeric(event);">
                                <span class="input-group-addon validate"><i id="Prs_Ced_Est"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Nombres:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" placeholder="Nombre del administrador" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Apellidos:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" placeholder="Apellido del administrador" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Prs_Esc">Estado Civil:</label>
                        <div class="col-sm-8">
                            <select id="Pep_Esc" name="Pep_Esc" class="form-control input-xs chosen-select">
                                <option value="S">SOLTERO/A</option>
                                <option value="C">CASADO/A</option>
                                <option value="D">DIVORCIADO/A</option>
                                <option value="V">VIUDO/A</option>
                                <option value="U">UNI&Oacute;N LIBRE</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Prs_Sex">Genero:</label>
                        <div class="col-sm-8">
                            <select id="Prs_Sex" name="Prs_Sex" class="form-control input-xs chosen-select" required="">
                                <option value="M">MASCULINO</option>
                                <option value="F">FEMENINO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Prs_Fec">Fecha Nacimiento:</label>
                        <div class="col-sm-4">
                            <input id="Prs_Fec" name="Prs_Fec" class="form-control input-xs datepicker" placeholder="Elegir fecha" type="text" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Ciu_Cod">Lugar Nacimiento:</label>
                        <div class="col-sm-7">
                            <select name="Cod_Ciu_Nac" id="Cod_Ciu_Nac" data-placeholder="Seleccione una ciudad" class="form-control input-xs chosen-select">
                                <option value="" data-provincia="" data-pais=""></option>
                                <?php foreach ($ciudades as $row) { ?>
                                    <option value="<?php echo $row['Ciu_Cod']; ?>" data-provincia="<?php echo $row['Pro_Nom']; ?>" data-pais="<?php echo $row['Pas_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Prs_Tel">Tel&eacute;fono 1:</label>
                        <div class="col-sm-4">
                            <input id="Prs_Tel" name="Prs_Tel" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs" for="Pep_Tel">Tel&eacute;fono 2:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Pep_Tel" name="Pep_Tel" class="form-control input-xs" placeholder="Teléfono del administrador" maxlength="20">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Ciu_Cod">Lugar Trabajo:</label>
                        <div class="col-sm-7">
                            <select name="Cod_Ciu_Tra" id="Cod_Ciu_Tra" data-placeholder="Seleccione una ciudad" class="form-control input-xs chosen-select">
                                <option value="" data-provincia="" data-pais=""></option>
                                <?php foreach ($ciudades as $row) { ?>
                                    <option value="<?php echo $row['Ciu_Cod']; ?>" data-provincia="<?php echo $row['Pro_Nom']; ?>" data-pais="<?php echo $row['Pas_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Prs_Cor">Email:</label>
                        <div class="col-sm-7">
                            <input id="Pep_Cor" name="Pep_Cor" class="form-control input-xs" placeholder="" type="text" />
                        </div>
                    </div>
                </form>
            </div>

            <!-- TAB TRIBUTARIO -->
            <div class="tab-pane fade" id="tabTributario">
                <form id="plantaTributarioForm" class="form-horizontal normal">
                    <input type="hidden" id="Prs_Cod_Trib" name="Prs_Cod">
                    <!-- Campos replicados de Admin. Planta -->
                    <input type="hidden" id="Pep_Tip_Trib" name="Pep_Tip" value="AC">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Identificacion Contador:</label>
                        <div class="col-xs-8">
                            <div class="input-group input-group-xs">
                                <input type="text" id="Trb_Prs_Ced" name="Trb_Prs_Ced" class="form-control input-xs" placeholder="Cédula o RUC" maxlength="13" onchange="buscarPersonaTributario(this.value)" onkeypress="return validar_numeric(event);">
                                <span class="input-group-addon validate"><i id="Trb_Prs_Ced_Est"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Nombres:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Trb_Prs_Nom" name="Trb_Prs_Nom" class="form-control input-xs" placeholder="Nombre del administrador" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Apellidos:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Trb_Prs_Ape" name="Trb_Prs_Ape" class="form-control input-xs" placeholder="Apellido del administrador" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Trb_Prs_Esc">Estado Civil:</label>
                        <div class="col-sm-8">
                            <select id="Trb_Prs_Esc" name="Trb_Prs_Esc" class="form-control input-xs chosen-select">
                                <option value="S">SOLTERO/A</option>
                                <option value="C">CASADO/A</option>
                                <option value="D">DIVORCIADO/A</option>
                                <option value="V">VIUDO/A</option>
                                <option value="U">UNI&Oacute;N LIBRE</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Trb_Prs_Sex">Genero:</label>
                        <div class="col-sm-8">
                            <select id="Trb_Prs_Sex" name="Trb_Prs_Sex" class="form-control input-xs chosen-select" required="">
                                <option value="M">MASCULINO</option>
                                <option value="F">FEMENINO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Trb_Prs_Fec">Fecha Nacimiento:</label>
                        <div class="col-sm-4">
                            <input id="Trb_Prs_Fec" name="Trb_Prs_Fec" class="form-control input-xs datepicker" placeholder="Elegir fecha" type="text" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Trb_Ciu_Cod">Lugar Nacimiento:</label>
                        <div class="col-sm-7">
                            <select name="Trb_Cod_Ciu_Nac" id="Trb_Cod_Ciu_Nac" data-placeholder="Seleccione una ciudad" class="form-control input-xs chosen-select">
                                <option value="" data-provincia="" data-pais=""></option>
                                <?php foreach ($ciudades as $row) { ?>
                                    <option value="<?php echo $row['Ciu_Cod']; ?>" data-provincia="<?php echo $row['Pro_Nom']; ?>" data-pais="<?php echo $row['Pas_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Trb_Prs_Tel">Tel&eacute;fono 1:</label>
                        <div class="col-sm-4">
                            <input id="Trb_Prs_Tel" name="Trb_Prs_Tel" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Teléfono 2:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Trb_Pep_Tel" name="Trb_Pep_Tel" class="form-control input-xs" placeholder="Teléfono del administrador" maxlength="20">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Trb_Ciu_Cod">Lugar Trabajo:</label>
                        <div class="col-sm-7">
                            <select name="Trb_Cod_Ciu_Tra" id="Trb_Cod_Ciu_Tra" data-placeholder="Seleccione una ciudad" class="form-control input-xs chosen-select">
                                <option value="" data-provincia="" data-pais=""></option>
                                <?php foreach ($ciudades as $row) { ?>
                                    <option value="<?php echo $row['Ciu_Cod']; ?>" data-provincia="<?php echo $row['Pro_Nom']; ?>" data-pais="<?php echo $row['Pas_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Trb_Prs_Cor">Email:</label>
                        <div class="col-sm-7">
                            <input id="Trb_Pep_Cor" name="Trb_Pep_Cor" class="form-control input-xs" placeholder="" type="text" />
                        </div>
                    </div>
                </form>
            </div>

            <!-- TAB AMBIENTAL -->
            <div class="tab-pane fade" id="tabAmbiental">
                <form id="plantaAmbientalForm" class="form-horizontal normal">
                    <input type="hidden" id="Prs_Cod_Amb" name="Prs_Cod">
                    <!-- Campos replicados de Admin. Planta -->
                    <input type="hidden" id="Pep_Tip_Amb" name="Pep_Tip" value="AM">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Identificacion Ing.Ambiental:</label>
                        <div class="col-xs-8">
                            <div class="input-group input-group-xs">
                                <input type="text" id="Amb_Prs_Ced" name="Amb_Prs_Ced" class="form-control input-xs" placeholder="Cédula o RUC" maxlength="13" onchange="buscarPersonaAmbiental(this.value)" onkeypress="return validar_numeric(event);">
                                <span class="input-group-addon validate"><i id="Amb_Prs_Ced_Est"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Nombres:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Amb_Prs_Nom" name="Amb_Prs_Nom" class="form-control input-xs" placeholder="Nombre del administrador" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Apellidos:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Amb_Prs_Ape" name="Amb_Prs_Ape" class="form-control input-xs" placeholder="Apellido del administrador" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Amb_Prs_Esc">Estado Civil:</label>
                        <div class="col-sm-8">
                            <select id="Amb_Prs_Esc" name="Amb_Prs_Esc" class="form-control input-xs chosen-select">
                                <option value="S">SOLTERO/A</option>
                                <option value="C">CASADO/A</option>
                                <option value="D">DIVORCIADO/A</option>
                                <option value="V">VIUDO/A</option>
                                <option value="U">UNI&Oacute;N LIBRE</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Amb_Prs_Sex">Genero:</label>
                        <div class="col-sm-8">
                            <select id="Amb_Prs_Sex" name="Amb_Prs_Sex" class="form-control input-xs chosen-select" required="">
                                <option value="M">MASCULINO</option>
                                <option value="F">FEMENINO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Amb_Prs_Fec">Fecha Nacimiento:</label>
                        <div class="col-sm-4">
                            <input id="Amb_Prs_Fec" name="Amb_Prs_Fec" class="form-control input-xs datepicker" placeholder="Elegir fecha" type="text" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Amb_Ciu_Cod">Lugar Nacimiento:</label>
                        <div class="col-sm-7">
                            <select name="Amb_Cod_Ciu_Nac" id="Amb_Cod_Ciu_Nac" data-placeholder="Seleccione una ciudad" class="form-control input-xs chosen-select">
                                <option value="" data-provincia="" data-pais=""></option>
                                <?php foreach ($ciudades as $row) { ?>
                                    <option value="<?php echo $row['Ciu_Cod']; ?>" data-provincia="<?php echo $row['Pro_Nom']; ?>" data-pais="<?php echo $row['Pas_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Amb_Prs_Tel">Tel&eacute;fono 1:</label>
                        <div class="col-sm-4">
                            <input id="Amb_Prs_Tel" name="Amb_Prs_Tel" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Teléfono 2:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Amb_Pep_Tel" name="Amb_Pep_Tel" class="form-control input-xs" placeholder="Teléfono del administrador" maxlength="20">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Amb_Ciu_Cod">Lugar Trabajo:</label>
                        <div class="col-sm-7">
                            <select name="Amb_Cod_Ciu_Tra" id="Amb_Cod_Ciu_Tra" data-placeholder="Seleccione una ciudad" class="form-control input-xs chosen-select">
                                <option value="" data-provincia="" data-pais=""></option>
                                <?php foreach ($ciudades as $row) { ?>
                                    <option value="<?php echo $row['Ciu_Cod']; ?>" data-provincia="<?php echo $row['Pro_Nom']; ?>" data-pais="<?php echo $row['Pas_Nom']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Amb_Prs_Cor">Email:</label>
                        <div class="col-sm-7">
                            <input id="Amb_Pep_Cor" name="Amb_Pep_Cor" class="form-control input-xs" placeholder="" type="text" />
                        </div>
                    </div>
                </form>
            </div>




            <!-- TAB TRIBUTARIO -->
            <div class="tab-pane fade" id="tabTributario">
                <!-- Aquí puedes agregar los campos para la sección Tributario -->
                <form id="plantaTributarioForm" class="form-horizontal normal">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">RUC:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Pla_RUC" name="Pla_RUC" class="form-control input-xs" placeholder="RUC de la planta" maxlength="13">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Tipo Contribuyente:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Pla_Contribuyente" name="Pla_Contribuyente" class="form-control input-xs" placeholder="Tipo Contribuyente" maxlength="30">
                        </div>
                    </div>
                </form>
            </div>
            <!-- TAB AMBIENTAL -->
            <div class="tab-pane fade" id="tabAmbiental">
                <!-- Aquí puedes agregar los campos para la sección Ambiental -->
                <form id="plantaAmbientalForm" class="form-horizontal normal">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Licencia Ambiental:</label>
                        <div class="col-xs-8">
                            <input type="text" id="Pla_Amb_Lic" name="Pla_Amb_Lic" class="form-control input-xs" placeholder="Licencia Ambiental" maxlength="30">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Vigencia:</label>
                        <div class="col-xs-8">
                            <input type="date" id="Pla_Amb_Vig" name="Pla_Amb_Vig" class="form-control input-xs">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div id="plantaErrorMessages" style="display: none; margin-top: 15px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
            <strong><i class="glyphicon glyphicon-exclamation-sign"></i> Errores de validación:</strong>
            <ul id="plantaErrorList" style="margin-bottom: 0; padding-left: 20px;"></ul>
        </div>
        <div style="text-align: center; margin-top: 15px; padding: 10px; border-top: 1px solid #ddd;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarPlanta();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#plantaDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Cliente para Planta -->
    <div id="clientePlantaDialog" title="Buscar Cliente" style="display: none;">
        <table id="gridClientePlanta"></table>
        <div id="gridClientePlantaPager"></div>
    </div>

    <?php $plantas = $obBD_con1->getArrayConsulta('manifiesto_plantas.selectWhere', array('where' => array('Pla_Est' => 'A')), $obBD_conexion);

    // $planta = $obBD_con1->getRowConsulta('manifiesto_plantas.selectWhere', array('where' => array('Pla_Cod' => $Pla_Cod)), $obBD_conexion);


    ?>
    <!-- Modal Empresa Transporte -->
    <div id="empresaTransporteDialog" title="Registrar Empresa de Transporte" style="display: none;">
        <form id="empresaTransporteForm" class="form-horizontal normal">
            <input type="hidden" id="Mat_Cod" name="Mat_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Nombre de la Empresa:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Des" name="Mat_Des" class="form-control input-xs" required placeholder="Nombre de la Empresa" maxlength="100">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Licencia Ambiental MAE:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Mae" name="Mat_Mae" class="form-control input-xs" required placeholder="Licencia Ambiental MAE" maxlength="30">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Teléfono:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Tel" name="Mat_Tel" class="form-control input-xs" placeholder="Teléfono" maxlength="10" onkeypress="return validar_numeric(event);">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Nro. Plan de Contingencia:</label>
                <div class="col-xs-8">
                    <input type="text" id="Mat_Pco" name="Mat_Pco" class="form-control input-xs" placeholder="Número Plan de Contingencia" maxlength="30">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Dirección:</label>
                <div class="col-xs-8">
                    <textarea id="Mat_Dir" name="Mat_Dir" class="form-control input-xs" rows="3" placeholder="Dirección"></textarea>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px; padding: 10px; border-top: 1px solid #ddd;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarEmpresaTransporte();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#empresaTransporteDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Chofer -->
    <div id="choferDialog" title="Registrar Chofer" style="display: none;">
        <form id="choferForm" class="form-horizontal normal">

            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Seleccionar Planta:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <select type="text" id="Pla_Cod" name="Pla_Cod" class="form-control input-xs" required placeholder="Planta">
                            <option value="">Seleccione...</option>
                            <?php foreach ($plantas as $row) { ?>
                                <option value="<?php echo $row['Pla_Cod']; ?>"><?php echo $row['Pla_Nom']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <input type="hidden" id="Cho_Cod" name="Cho_Cod">
            <input type="hidden" id="Prs_Cod" name="Prs_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Cédula:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input type="text" id="Cho_Ced" name="Cho_Ced" class="form-control input-xs" required placeholder="Cédula o RUC" maxlength="13" onchange="buscarPersonaPorCedula(this.value)" onkeypress="return validar_numeric(event);">
                        <span class="input-group-addon validate"><i id="Cho_Ced_Est"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Nombres:</label>
                <div class="col-xs-8">
                    <input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" required placeholder="Nombre del chofer">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Apellidos:</label>
                <div class="col-xs-8">
                    <input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" required placeholder="Apellidos del chofer">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo Licencia:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <select id="Cho_Tli" name="Cho_Tli" class="form-control input-xs" required>
                            <option value="">Licencia...</option>
                            <option value="A">A</option>
                            <option value="A1">A1</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="C1">C1</option>
                            <option value="D">D</option>
                            <option value="D1">D1</option>
                            <option value="E">E</option>
                        </select>
                        <span class="input-group-addon bold alert-info">Caducidad:</span>
                        <input type="date" id="Cho_Cli" name="Cho_Cli" class="form-control input-xs datepicker" required placeholder="Fecha caducidad">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Teléfono:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cho_Tel" name="Cho_Tel" class="form-control input-xs" required placeholder="Teléfono" maxlength="20">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo de Sangre:</label>
                <div class="col-xs-8">
                    <select id="Cho_Tsa" name="Cho_Tsa" class="form-control input-xs" required>
                        <option value="">Seleccione...</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="display: none;">
                <label class="col-xs-4 control-label label-xs">Licencia AMB MAE:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cho_Mae" name="Cho_Mae" class="form-control input-xs" placeholder="Licencia ambiental MAE" maxlength="20" value="">
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarChofer();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#choferDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Vehículo -->
    <div id="vehiculoDialog" title="Registrar Vehículo" style="display: none;">
        <form id="vehiculoForm" class="form-horizontal normal">
            <input type="hidden" id="Veh_Cod" name="Veh_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Planta:</label>
                <div class="col-xs-8">
                    <select id="Pla_Cod" name="Pla_Cod" class="form-control input-xs" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($plantas as $row) { ?>
                            <option value="<?php echo $row['Pla_Cod']; ?>"><?php echo $row['Pla_Nom']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Empresa Transporte:</label>
                <div class="col-xs-8">
                    <select id="Mat_Cod" name="Mat_Cod" class="form-control input-xs" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($transportes as $row) { ?>
                            <option value="<?php echo $row['Mat_Cod']; ?>"><?php echo $row['Mat_Des']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Marca:</label>
                <div class="col-xs-8">
                    <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs" required placeholder="Ingrese marca">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Placa:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required placeholder="Ej: ABC-1234" maxlength="8" onchange="validarPlacaVehiculo(this.value)" onkeyup="this.value = this.value.toUpperCase();">
                        <span class="input-group-addon validate"><i id="Veh_Pla_Est"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Color:</label>
                <div class="col-xs-8">
                    <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs" required placeholder="Ingrese color">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Capacidad:</label>
                <div class="col-xs-5">
                    <div class="input-group input-group-xs">
                        <input name="Veh_Cap" id="Veh_Cap" type="text" class="form-control input-xs" required placeholder="Capacidad">
                        <span class="input-group-addon validate">Kg</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo Vehículo:</label>
                <div class="col-xs-8">
                    <select id="Veh_Tit" name="Veh_Tit" class="form-control input-xs" required>
                        <option value="V">VOLQUETA</option>
                        <option value="D">TIPO DUMPER</option>
                        <option value="C">CAMION</option>
                    </select>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarVehiculo();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#vehiculoDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>


        <!--INICIO DEL DIALOGO BUSCAR Cliente-->
        <div id="cliDialog" title="B&uacute;squeda de Cliente"></div>
        <!--INICIO DEL DIALOGO BUSCAR CUENTA-->



    </div>

    <!-- Modal Celda -->
    <div id="celdaDialog" title="Registrar Celda" style="display: none;">
        <form id="celdaForm" class="form-horizontal normal">
            <input type="hidden" id="Cel_Cod" name="Cel_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Tipo:</label>
                <div class="col-xs-8">
                    <select id="Cel_Tip" name="Cel_Tip" class="form-control input-xs" required onchange="cambiarTipoCelda();">
                        <option value="G">Grupo</option>
                        <option value="D">Detalle</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Nombre de la Celda:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cel_Nom" name="Cel_Nom" class="form-control input-xs" required placeholder="Nombre de la celda" maxlength="30">
                </div>
            </div>
            <div class="form-group" style="display: none;">
                <label class="col-xs-4 control-label label-xs required">Estado:</label>
                <div class="col-xs-8">
                    <select id="Cel_Est" name="Cel_Est" class="form-control input-xs" disabled>
                        <option value="A">Activo</option>
                        <option value="I">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="form-group campos-detalle" style="display: none;">
                <label class="col-xs-4 control-label label-xs required">Grupo:</label>
                <div class="col-xs-8">
                    <select id="Cel_Rec" name="Cel_Rec" class="form-control input-xs" placeholder="Seleccione un grupo">
                        <option value="">Seleccione un grupo...</option>
                    </select>
                </div>
            </div>
            <div class="form-group campos-detalle" style="display: none;">
                <label class="col-xs-4 control-label label-xs required">Número/Código:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cel_Num" name="Cel_Num" class="form-control input-xs" placeholder="Número o código de celda" maxlength="8">
                </div>
            </div>
            <div class="form-group campos-detalle" style="display: none;">
                <label class="col-xs-4 control-label label-xs required">Ubicación:</label>
                <div class="col-xs-8">
                    <input type="text" id="Cel_Ubi" name="Cel_Ubi" class="form-control input-xs" placeholder="X,Y" maxlength="20">
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarCelda();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#celdaDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Diálogos de búsqueda para Sanciones -->
    <div id="vehSancionDialog" title="Buscar Vehículo"></div>
    <div id="choferSancionDialog" title="Buscar Chofer"></div>
    <div id="plantaSancionDialog" title="Buscar Planta"></div>

    <!-- Modal Sanción Vehículo -->
    <div id="sancionVehiculoDialog" title="Sanción - Vehículo" style="display: none;">
        <form id="sancionVehiculoForm" class="form-horizontal normal">
            <input type="hidden" id="sancionVeh_Msa_Cod" name="Msa_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Placa Vehículo:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input name="Veh_Pla" id="search_veh_sancion" type="text" placeholder="Ingrese placa (ej: ABC-1234)" class="form-control input-xs" maxlength="8" onkeydown="if (event.keyCode === 13) { event.preventDefault(); buscarVehiculoPorPlacaSancion(); }" onkeyup="this.value = this.value.toUpperCase();" />
                        <span class="input-group-btn">
                            <button type="button" onclick="buscarVehiculoPorPlacaSancion();" class="btn btn-success btn-xs" title="Buscar por placa"><span class="glyphicon glyphicon-search"></span></button>
                        </span>
                    </div>
                    <input type="hidden" id="sancionVeh_Veh_Cod" name="Veh_Cod" />
                </div>
                <label class="col-xs-4 control-label label-xs required">Vehículo:</label>
                <div class="col-xs-8">
                    <span id="sancionVeh_Veh_Pla" class="form-control input-xs databind help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                    <span id="sancionVeh_sancionesAnio" class="help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Fecha/Hora Inicio:</label>
                <div class="col-xs-8">
                    <input type="datetime-local" id="sancionVeh_Msa_Fei" name="Msa_Fei" class="form-control input-xs" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Fecha/Hora Fin:</label>
                <div class="col-xs-8">
                    <input type="datetime-local" id="sancionVeh_Msa_Fef" name="Msa_Fef" class="form-control input-xs" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Observación:</label>
                <div class="col-xs-8">
                    <textarea id="sancionVeh_Msa_Obs" name="Msa_Obs" class="form-control input-xs" rows="2" maxlength="500" placeholder="Observación"></textarea>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarSancionVehiculo();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#sancionVehiculoDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Sanción Chofer -->
    <div id="sancionChoferDialog" title="Sanción - Chofer" style="display: none;">
        <form id="sancionChoferForm" class="form-horizontal normal">
            <input type="hidden" id="sancionCho_Msa_Cod" name="Msa_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Chofer:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input name="search_cho" id="search_cho_sancion" type="text" placeholder="Buscar chofer..." class="form-control input-xs" readonly />
                        <span class="input-group-btn">
                            <button type="button" onclick="abrirBusquedaChoferSancion();" class="btn btn-success btn-xs" title="Buscar Chofer"><span class="glyphicon glyphicon-search"></span></button>
                        </span>
                    </div>
                    <input type="hidden" id="sancionCho_Cho_Cod" name="Cho_Cod" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Cédula:</label>
                <div class="col-xs-8">
                    <span id="sancionCho_Prs_Ced" class="form-control input-xs databind"></span>
                    <span id="sancionCho_sancionesAnio" class="help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Fecha/Hora Inicio:</label>
                <div class="col-xs-8">
                    <input type="datetime-local" id="sancionCho_Msa_Fei" name="Msa_Fei" class="form-control input-xs" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Fecha/Hora Fin:</label>
                <div class="col-xs-8">
                    <input type="datetime-local" id="sancionCho_Msa_Fef" name="Msa_Fef" class="form-control input-xs" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Observación:</label>
                <div class="col-xs-8">
                    <textarea id="sancionCho_Msa_Obs" name="Msa_Obs" class="form-control input-xs" rows="2" maxlength="500" placeholder="Observación"></textarea>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarSancionChofer();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#sancionChoferDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <!-- Modal Sanción Planta -->
    <div id="sancionPlantaDialog" title="Sanción - Planta" style="display: none;">
        <form id="sancionPlantaForm" class="form-horizontal normal">
            <input type="hidden" id="sancionPla_Msa_Cod" name="Msa_Cod">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Planta:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs">
                        <input name="search_pla" id="search_pla_sancion" type="text" placeholder="Buscar planta..." class="form-control input-xs" readonly />
                        <span class="input-group-btn">
                            <button type="button" onclick="abrirBusquedaPlantaSancion();" class="btn btn-success btn-xs" title="Buscar Planta"><span class="glyphicon glyphicon-search"></span></button>
                        </span>
                    </div>
                    <input type="hidden" id="sancionPla_Pla_Cod" name="Pla_Cod" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Cédula cliente:</label>
                <div class="col-xs-8">
                    <span id="sancionPla_Prs_Ced" class="form-control input-xs databind"></span>
                    <span id="sancionPla_sancionesAnio" class="help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Fecha/Hora Inicio:</label>
                <div class="col-xs-8">
                    <input type="datetime-local" id="sancionPla_Msa_Fei" name="Msa_Fei" class="form-control input-xs" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Fecha/Hora Fin:</label>
                <div class="col-xs-8">
                    <input type="datetime-local" id="sancionPla_Msa_Fef" name="Msa_Fef" class="form-control input-xs" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs">Observación:</label>
                <div class="col-xs-8">
                    <textarea id="sancionPla_Msa_Obs" name="Msa_Obs" class="form-control input-xs" rows="2" maxlength="500" placeholder="Observación"></textarea>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarSancionPlanta();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#sancionPlantaDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
        </div>
    </div>

    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>

    <!-- Div oculto para imprimir plantas -->
    <div id="imprimirPlantas" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE PLANTAS', '<span class="subtitle">Listado de Plantas</span>', $obBD_conexion) ?>
            <table id="tablaReportePlantas" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>

    <!-- Div oculto para imprimir empresas de transporte -->
    <div id="imprimirEmpresasTransporte" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE EMPRESAS DE TRANSPORTE', '<span class="subtitle">Listado de Empresas de Transporte</span>', $obBD_conexion) ?>
            <table id="tablaReporteEmpresasTransporte" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>

    <!-- Div oculto para imprimir choferes -->
    <div id="imprimirChoferes" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CHOFERES', '<span class="subtitle">Listado de Choferes</span>', $obBD_conexion) ?>
            <table id="tablaReporteChoferes" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>

    <!-- Div oculto para imprimir vehículos -->
    <div id="imprimirVehiculos" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE VEHÍCULOS', '<span class="subtitle">Listado de Vehículos</span>', $obBD_conexion) ?>
            <table id="tablaReporteVehiculos" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>

    <!-- Div oculto para imprimir celdas -->
    <div id="imprimirCeldas" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CELDAS', '<span class="subtitle">Listado de Celdas</span>', $obBD_conexion) ?>
            <table id="tablaReporteCeldas" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>

</body>
<script type="text/javascript" src="../VALIDACIONES/man_adm_configuracion.js?x=26"></script>

</script>

</HTML>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>