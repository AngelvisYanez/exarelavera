<?php
/**
 * AJAX + helpers compartidos: TAB Sanciones
 * Extraído de man_adm_configuracion.php
 */

if (!function_exists('enviarNotificacionWhatsapp')) {
    require_once(__DIR__ . '/../../MODELS/send_whatsapp.php');
}
    
if (!isset($obBD_mani) || !is_object($obBD_mani)) {
    if (!class_exists('Class_Log_Datos_Mani')) {
        require_once(__DIR__ . '/../LOGICA/man_log_manifiesto.php');
    }
    $obBD_mani = new Class_Log_Datos_Mani;
}

if (!defined('MAN_CFG_TBL_TIPO_SANC_LISTA')) {
    define('MAN_CFG_TBL_TIPO_SANC_LISTA', 'manifiesto_sanciones_lista');
}

if (!function_exists('man_adm_cfg_tabla_tipo_sanc_lista_existe')) {
function man_adm_cfg_tabla_tipo_sanc_lista_existe($obBD_con1, $obBD_conexion)
{
    static $v = null;
    if ($v !== null) {
        return $v;
    }
    $tbl = MAN_CFG_TBL_TIPO_SANC_LISTA;
    $obBD_con1->setError(0, '');
    @$obBD_con1->getRowConsultaSql("SELECT 1 FROM `" . $tbl . "` LIMIT 1", $obBD_conexion);
    $v = ($obBD_con1->Error == 0);
    return $v;
}
}

if (!function_exists('man_adm_sanciones_tiene_columna_tsa_cod')) {
function man_adm_sanciones_tiene_columna_tsa_cod($obBD_con1, $obBD_conexion)
{
    static $v = null;
    if ($v !== null) {
        return $v;
    }
    $obBD_con1->setError(0, '');
    $row = @$obBD_con1->getRowConsultaSql("SHOW COLUMNS FROM manifiesto_sanciones LIKE 'Tsa_Cod'", $obBD_conexion);
    $v = !empty($row);
    return $v;
}
}

if (!function_exists('man_adm_sanciones_aplicar_tsa_cod_guardado')) {
/** Valida Tsa_Cod contra catálogo y lo agrega a $datos si aplica. */
function man_adm_sanciones_aplicar_tsa_cod_guardado($obBD_con1, $obBD_conexion, $Ses_Emp_Cod, &$datos)
{
    if (!man_adm_sanciones_tiene_columna_tsa_cod($obBD_con1, $obBD_conexion)) {
        return;
    }
    if (!man_adm_cfg_tabla_tipo_sanc_lista_existe($obBD_con1, $obBD_conexion)) {
        return;
    }
    $Tsa_Cod = isset($_POST['Tsa_Cod']) ? (int)$_POST['Tsa_Cod'] : 0;
    if ($Tsa_Cod <= 0) {
        throw new Exception('Seleccione el tipo de sanción.');
    }
    $tbl = MAN_CFG_TBL_TIPO_SANC_LISTA;
    $emp = (int)$Ses_Emp_Cod;
    $ok = $obBD_con1->getRowConsultaSql("SELECT Tsa_Cod FROM `" . $tbl . "` WHERE Tsa_Cod = " . (int)$Tsa_Cod . " AND Emp_Cod = $emp LIMIT 1", $obBD_conexion);
    if (empty($ok)) {
        throw new Exception('Tipo de sanción no válido para esta empresa.');
    }
    $datos['Tsa_Cod'] = $Tsa_Cod;
}
}

// Listar Plantas (search dialog sanciones) — usa Class_Log_Datos_Mani (consulta id=3)
if (isset($listPlantasGridAjax) || isset($_REQUEST['listPlantasGridAjax'])) {
    if (class_exists('ChromePhp')) { ChromePhp::log("listPlantasGridAjax ejecutándose"); }
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 20;
    $op_opciones = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'd';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $data = array('limits' => '', 'op_opciones' => $op_opciones, 'search' => $search);
    $contar = $obBD_mani->getRowConsulta(3, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];
    if ($contar['total'] > 0) {
        $data['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_mani->getArrayConsulta(3, $data, $obBD_conexion);
        $obBD_mani->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

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
    $rows = $obBD_mani->getArrayConsulta('manifiesto_vehiculo.selectWhere', $data, $obBD_conexion);
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

// Catálogo: tipos de sanción por empresa (tabla MAN_CFG_TBL_TIPO_SANC_LISTA)
if (isset($_REQUEST['listTipoSancionListaAjax']) || isset($listTipoSancionListaAjax)) {
    $resp = array('success' => true, 'rows' => array(), 'requiereSeleccion' => false);
    $tieneCol = man_adm_sanciones_tiene_columna_tsa_cod($obBD_con1, $obBD_conexion);
    $tablaOk = man_adm_cfg_tabla_tipo_sanc_lista_existe($obBD_con1, $obBD_conexion);
    $resp['requiereSeleccion'] = ($tieneCol && $tablaOk);
    if (!$tablaOk) {
        $obBD_con1->echoJson($resp);
        exit;
    }
    $tbl = MAN_CFG_TBL_TIPO_SANC_LISTA;
    $emp = (int)$Ses_Emp_Cod;
    $sql = "SELECT Tsa_Cod, Tsa_Des AS Tsa_Nom FROM `" . $tbl . "` WHERE Emp_Cod = $emp";
    $obBD_con1->setError(0, '');
    $estRow = @$obBD_con1->getRowConsultaSql("SHOW COLUMNS FROM `" . $tbl . "` LIKE 'Tsa_Est'", $obBD_conexion);
    if (!empty($estRow)) {
        $sql .= " AND Tsa_Est = 'A'";
    }
    $sql .= " ORDER BY Tsa_Des";
    $rows = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
    if (is_array($rows)) {
        $obBD_con1->utf8_change_param($rows);
        $resp['rows'] = $rows;
    } else {
        $resp['rows'] = array();
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Listar Sanciones (unificado: VE, CH, PL) — SQL directo. Tipo "Todos" + filtro_nombres vacío = todos los registros activos.
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

    $extraSel = '';
    $extraJoin = '';
    if (man_adm_sanciones_tiene_columna_tsa_cod($obBD_con1, $obBD_conexion) && man_adm_cfg_tabla_tipo_sanc_lista_existe($obBD_con1, $obBD_conexion)) {
        // Exponer Tsa_Cod de la sanción (la descripción sigue viniendo del JOIN a manifiesto_sanciones_lista)
        $extraSel = ", $nm.Tsa_Cod";
        $extraJoin = '';
    }

    $sel = "SELECT $nm.Msa_Cod, $nm.Msa_Tip, $nm.Veh_Cod, $nm.Cho_Cod, $nm.Pla_Cod, $nm.Msa_Fei, $nm.Msa_Fef, $nm.Msa_Obs$extraSel,IFNULL(manifiesto_sanciones_lista.Tsa_Des, '') AS Tsa_Des,IF(manifiesto_sanciones_lista.Tsa_Niv='M', 'MEDIO', IF(manifiesto_sanciones_lista.Tsa_Niv='A', 'ALTO',IF(manifiesto_sanciones_lista.Tsa_Niv='B', 'BAJO', 'MEDIO'))) AS Tsa_Niv,
        COALESCE(concat(vehiculo.Veh_Pla,' ',vehiculo.Veh_Mar), manifiesto_plantas.Pla_Nom, CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,''))) AS Identificador,
        COALESCE(persona_ch.Prs_Ced, persona_pl.Prs_Ced, vehiculo.Veh_Pla) AS Prs_Ced,
        COALESCE(CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,'')), CONCAT(IFNULL(persona_pl.Prs_Nom,''),' ',IFNULL(persona_pl.Prs_Ape,''))) AS Prs_Nom
        FROM $nm
        LEFT JOIN vehiculo ON vehiculo.Veh_Cod = $nm.Veh_Cod AND $nm.Msa_Tip = 'VE'
        LEFT JOIN chofer ON chofer.Cho_Cod = $nm.Cho_Cod AND $nm.Msa_Tip = 'CH'
        LEFT JOIN persona AS persona_ch ON persona_ch.Prs_Cod = chofer.Prs_Cod
        LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = $nm.Pla_Cod AND $nm.Msa_Tip = 'PL'
        LEFT JOIN manifiesto_sanciones_lista ON manifiesto_sanciones_lista.Tsa_Cod = $nm.Tsa_Cod
        LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
        LEFT JOIN persona AS persona_pl ON persona_pl.Prs_Cod = cliente.Prs_Cod
        $extraJoin
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
        $Msa_Cho = isset($_POST['Msa_Cho']) ? trim($_POST['Msa_Cho']) : '';
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
        man_adm_sanciones_aplicar_tsa_cod_guardado($obBD_con1, $obBD_conexion, $Ses_Emp_Cod, $datos);
        if (!empty($Msa_Cod)) {
            $datos['where'] = array('Msa_Cod' => $Msa_Cod);
            $obBD_con1->operacionobBD('manifiesto_sanciones.update', $datos, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_sanciones.insert', $datos, $obBD_conexion);



            //cREAR UNA CONULSTA QUE POR MEDIO DEL PLA_cod me traiga la tabla manifiesto_personal_planta esta tiene el Per_Cor correo y Pep_Tel telefono
            $vehiculo = $obBD_mani->getArrayConsulta(14, array('Veh_Cod' => $Veh_Cod), $obBD_conexion);
            $vehiculoRow = (is_array($vehiculo) && isset($vehiculo[0]) && is_array($vehiculo[0])) ? $vehiculo[0] : (is_array($vehiculo) ? $vehiculo : array());
            $plaCod = isset($vehiculoRow['Pla_Cod']) ? $vehiculoRow['Pla_Cod'] : '';
            $personal = $obBD_mani->getArrayConsulta(12, array('Pla_Cod' => $plaCod, 'Pep_Tip' => 'AP'), $obBD_conexion);
            //if (isset($personal['Pep_Tel']) && !empty($personal['Pep_Tel'])) {
            $Nom_Pla = $obBD_mani->getArrayConsulta(13, array('Pla_Cod' => $plaCod), $obBD_conexion);
            $Nom_Pla = isset($Nom_Pla['Pla_Nom']) ? $Nom_Pla['Pla_Nom'] : '';
            // Agrega un icono de precaución (emoji ⚠️) al inicio del mensaje
            $telsPlanta = array();
            if (is_array($personal)) {
                foreach ($personal as $p) {
                    if (is_array($p) && isset($p['Pep_Tel']) && trim((string) $p['Pep_Tel']) !== '') {
                        $telsPlanta[] = (string) $p['Pep_Tel'];
                    }
                }
            } elseif (is_string($personal) && trim($personal) !== '') {
                $telsPlanta[] = (string) $personal;
            }
            // $placa = isset($vehiculoRow['Vhe_Cod']) ? (string) $vehiculoRow['Vhe_Cod'] : (isset($vehiculoRow['Veh_Cod']) ? (string) $vehiculoRow['Veh_Cod'] : '');

            $placa = isset($vehiculoRow['Veh_Pla']) ? (string) $vehiculoRow['Veh_Pla'] : '';
            $mensaje = "⚠️ *Sancion aplicada a vehiculo con placa: " . $placa . "*\n" .
                ' - Chofer: ' . $Msa_Cho . "\n" .
                ' - Fec.Inicio: ' . $Msa_Fei . "\n" .
                ' - Fec.Fin: ' . $Msa_Fef . "\n" .
                'Observación: ' . $Msa_Obs . "\n" .
                ' - No podrá seleccionar este vehiculo en manifiestos - ';
            enviarNotificacionWhatsapp($mensaje, $telsPlanta);
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
        man_adm_sanciones_aplicar_tsa_cod_guardado($obBD_con1, $obBD_conexion, $Ses_Emp_Cod, $datos);
        if (!empty($Msa_Cod)) {
            $datos['where'] = array('Msa_Cod' => $Msa_Cod);
            $obBD_con1->operacionobBD('manifiesto_sanciones.update', $datos, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_sanciones.insert', $datos, $obBD_conexion);


            //cREAR UNA CONULSTA QUE POR MEDIO DEL PLA_cod me traiga la tabla manifiesto_personal_planta esta tiene el Per_Cor correo y Pep_Tel telefono
            $choferRows = $obBD_mani->getArrayConsulta(15, array('Cho_Cod' => $Cho_Cod), $obBD_conexion);
            $chofer = (is_array($choferRows) && isset($choferRows[0]) && is_array($choferRows[0])) ? $choferRows[0] : array();
            $choNom = isset($chofer['cho_nom']) ? (string) $chofer['cho_nom'] : '';
            $choTels = array();
            if (is_array($choferRows)) {
                foreach ($choferRows as $r) {
                    if (is_array($r) && isset($r['Cho_Tel']) && trim((string) $r['Cho_Tel']) !== '') {
                        $choTels[] = (string) $r['Cho_Tel'];
                    }
                }
            }
            $mensaje = "⚠️ *Sancion aplicada a chofer : " . $choNom . "*\n" .
                ' - Fec.Inicio: ' . $Msa_Fei . "\n" .
                ' - Fec.Fin: ' . $Msa_Fef . "\n" .
                'Observación: ' . $Msa_Obs . "\n" .
                ' - Chofer sancionado - ';
            enviarNotificacionWhatsapp($mensaje, $choTels);
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
        man_adm_sanciones_aplicar_tsa_cod_guardado($obBD_con1, $obBD_conexion, $Ses_Emp_Cod, $datos);
        if (!empty($Msa_Cod)) {
            $datos['where'] = array('Msa_Cod' => $Msa_Cod);
            $obBD_con1->operacionobBD('manifiesto_sanciones.update', $datos, $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD('manifiesto_sanciones.insert', $datos, $obBD_conexion);

            //cREAR UNA CONULSTA QUE POR MEDIO DEL PLA_cod me traiga la tabla manifiesto_personal_planta esta tiene el Per_Cor correo y Pep_Tel telefono
            $personal = $obBD_mani->getRowConsulta(12, array('Pla_Cod' => $Pla_Cod, 'Pep_Tip' => 'AP'), $obBD_conexion);
            //if (isset($personal['Pep_Tel']) && !empty($personal['Pep_Tel'])) {
            $Nom_Pla = $obBD_mani->getRowConsulta(13, array('Pla_Cod' => $Pla_Cod), $obBD_conexion);
            $Nom_Pla = isset($Nom_Pla['Pla_Nom']) ? $Nom_Pla['Pla_Nom'] : '';
            // Agrega un icono de precaución (emoji ⚠️) al inicio del mensaje
            $mensaje = "⚠️ *Sanción aplicada a planta: " . $Nom_Pla . "*\n" .
                ' - Fec.Inicio: ' . $Msa_Fei . "\n" .
                ' - Fec.Fin: ' . $Msa_Fef . "\n" .
                'Observación: ' . $Msa_Obs . "\n" .
                ' - No podrá realizar manifiestos - ';
            enviarNotificacionWhatsapp($mensaje, $personal['Pep_Tel']);
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

// Suspender sanción (Msa_Est = 'S'), mismo patrón que anular (Msa_Est = 'I')
if (isset($_REQUEST['suspenderSancionAjax']) || isset($suspenderSancionAjax)) {
    $resp = array('success' => false);
    $Msa_Cod = isset($_POST['Msa_Cod']) ? trim($_POST['Msa_Cod']) : '';
    if (empty($Msa_Cod)) {
        $resp['message'] = 'Código de sanción no válido.';
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->operacionobBD('manifiesto_sanciones.update', array('Msa_Est' => 'S', 'where' => array('Msa_Cod' => $Msa_Cod)), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}


if (isset($saveNuevoTipoSancionAjax)) {
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $resp = array('success' => false);
    try {
        $Tsa_Des = isset($_POST['Tsa_Des']) ? trim($_POST['Tsa_Des']) : '';
        $Tsa_Niv = isset($_POST['Tsa_Niv']) ? trim($_POST['Tsa_Niv']) : '';
        $Emp_Cod_Post = $Ses_Emp_Cod;

        if ($Tsa_Des === '') {
            throw new Exception('La descripción del tipo de sanción es obligatoria.');
        }
        if ($Tsa_Niv === '' || !in_array($Tsa_Niv, array('M', 'A', 'B'))) {
            throw new Exception('Nivel de riesgo inválido (use M, A o B).');
        }

        $datosNuevo = array(
            'Emp_Cod' => $Emp_Cod_Post,
            'Tsa_Des' => $Tsa_Des,
            'Tsa_Niv' => $Tsa_Niv,
            'Tsa_Est' => 'A'
        );

        $obBD_con1->operacionobBD('manifiesto_sanciones_lista.insert', $datosNuevo, $obBD_conexion);
        $Tsa_Cod_New = $obBD_con1->insercionid($obBD_conexion);
        $resp['success'] = true;
        $resp['Tsa_Cod'] = $Tsa_Cod_New;
        $resp['Tsa_Des'] = $Tsa_Des;
        $resp['Tsa_Niv'] = $Tsa_Niv;

        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
}

