<?php
/**
 * ppto_param_contable_ajax.php
 * Endpoint JSON para Parametrizacion Contable (tab Admin).
 */

if (!ob_get_level()) {
    @ob_start();
}
@ini_set('display_errors', '0');

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once(__DIR__ . '/../LOGICA/ppto_schema_logica.php');
require_once(__DIR__ . '/../LOGICA/ppto_format_helpers.php');
require_once(__DIR__ . '/../LOGICA/ppto_param_contable_logica.php');
require_once(__DIR__ . '/../LOGICA/ppto_param_ejecutado_logica.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Emp_Cod) && isset($_SESSION['Ses_Emp_Cod'])) {
    $Ses_Emp_Cod = $_SESSION['Ses_Emp_Cod'];
}
if (!isset($Ses_Usu_Cod) && isset($_SESSION['Ses_Usu_Cod'])) {
    $Ses_Usu_Cod = $_SESSION['Ses_Usu_Cod'];
}

function ppto_param_json($data) {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    exit;
}

try {
    $obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
    $mysqli = $obBD_conexion->conexion;
    if (!$mysqli) {
        ppto_param_json(array('ok' => false, 'message' => 'Sin conexion BD.'));
    }
    $mysqli->set_charset('utf8mb4');
    ppto_param_contable_boot($mysqli);

    $action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
    $emp_id = isset($_REQUEST['emp_id']) ? (int)$_REQUEST['emp_id'] : (int)$Ses_Emp_Cod;
    $anio = isset($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : (int)date('Y');
    $usu_id = isset($Ses_Usu_Cod) ? (int)$Ses_Usu_Cod : 0;

    $plan = ppto_param_contable_plan_empresa($mysqli, $emp_id, $anio);
    $pla_cod = $plan ? (int)$plan['pla_cod'] : 0;
    $pec_cod = $plan ? (int)$plan['pec_cod'] : 0;

    if ($action === 'meta') {
        ppto_param_json(array(
            'ok' => true,
            'emp_id' => $emp_id,
            'anio' => $anio,
            'plan' => $plan,
            'kpis' => $pla_cod > 0 ? ppto_param_contable_kpis($mysqli, $emp_id, $pla_cod) : array(),
            'grupos_balance' => $pla_cod > 0 ? ppto_param_contable_grupos_balance($mysqli, $pla_cod) : array(),
        ));
    }

    if ($action === 'kpis') {
        if ($pla_cod <= 0) {
            ppto_param_json(array('ok' => false, 'message' => 'Sin plan de cuentas para la empresa/anio.'));
        }
        ppto_param_json(array('ok' => true, 'kpis' => ppto_param_contable_kpis($mysqli, $emp_id, $pla_cod), 'plan' => $plan));
    }

    if ($action === 'arbol') {
        $filtro = isset($_REQUEST['filtro']) ? $_REQUEST['filtro'] : 'todos';
        if ($pla_cod <= 0) {
            ppto_param_json(array('ok' => false, 'message' => 'Sin plan de cuentas.', 'rows' => array()));
        }
        ppto_param_json(array(
            'ok' => true,
            'rows' => ppto_param_contable_arbol_partidas($mysqli, $emp_id, $pla_cod, $filtro),
        ));
    }

    if ($action === 'rubro') {
        $ppa_id = isset($_REQUEST['ppa_id']) ? (int)$_REQUEST['ppa_id'] : 0;
        if ($pla_cod <= 0 || $ppa_id <= 0) {
            ppto_param_json(array('ok' => false, 'message' => 'Parametros invalidos.'));
        }
        $det = ppto_param_contable_rubro_detalle($mysqli, $emp_id, $pla_cod, $ppa_id);
        $det['plan'] = $plan;
        ppto_param_json($det);
    }

    if ($action === 'movimientos') {
        $raw = isset($_REQUEST['pld_cods']) ? $_REQUEST['pld_cods'] : '';
        $ids = is_array($raw) ? $raw : preg_split('/[,\s]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);
        ppto_param_json(array(
            'ok' => true,
            'movimientos' => ppto_param_contable_movimientos_cuentas($mysqli, $pec_cod, $ids),
            'pec_cod' => $pec_cod,
        ));
    }

    if ($action === 'buscar_cuentas') {
        $q = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';
        $grupo = isset($_REQUEST['grupo']) ? $_REQUEST['grupo'] : 'todas';
        $filtro = isset($_REQUEST['filtro']) ? $_REQUEST['filtro'] : 'todas';
        $limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 500;
        ppto_param_json(array(
            'ok' => true,
            'grupos_balance' => ppto_param_contable_grupos_balance($mysqli, $pla_cod),
            'rows' => ppto_param_contable_buscar_cuentas($mysqli, $emp_id, $pla_cod, $q, $limit, $grupo, $filtro),
            'hint' => (trim($q) === '' && (trim($grupo) === '' || strtolower(trim($grupo)) === 'todas'))
                ? 'Elija un grupo del plan (ej. Gastos) o escriba parte del codigo/nombre.'
                : '',
        ));
    }

    if ($action === 'cuentas_pendientes') {
        $grupo = isset($_REQUEST['grupo']) ? $_REQUEST['grupo'] : (isset($_REQUEST['clasif']) ? $_REQUEST['clasif'] : 'todas');
        $q = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';
        ppto_param_json(array(
            'ok' => true,
            'grupos_balance' => ppto_param_contable_grupos_balance($mysqli, $pla_cod),
            'rows' => ppto_param_contable_cuentas_pendientes($mysqli, $emp_id, $pla_cod, $grupo, $q),
        ));
    }

    if ($action === 'asignar') {
        $ppa_id = isset($_POST['ppa_id']) ? (int)$_POST['ppa_id'] : 0;
        $pld_cod = isset($_POST['pld_cod']) ? (int)$_POST['pld_cod'] : 0;
        $r = ppto_param_contable_asignar($mysqli, $emp_id, $pla_cod, $ppa_id, $pld_cod, $usu_id);
        if (!empty($r['ok'])) {
            $r['kpis'] = ppto_param_contable_kpis($mysqli, $emp_id, $pla_cod);
        }
        ppto_param_json($r);
    }

    if ($action === 'asignar_multi') {
        $ppa_id = isset($_POST['ppa_id']) ? (int)$_POST['ppa_id'] : 0;
        $ids = array();
        if (isset($_POST['pld_cods']) && is_array($_POST['pld_cods'])) {
            $ids = $_POST['pld_cods'];
        } elseif (isset($_POST['pld_cods'])) {
            $ids = preg_split('/[,\s]+/', (string)$_POST['pld_cods'], -1, PREG_SPLIT_NO_EMPTY);
        }
        $ok_n = 0;
        $err = array();
        foreach ($ids as $id) {
            $r = ppto_param_contable_asignar($mysqli, $emp_id, $pla_cod, $ppa_id, (int)$id, $usu_id);
            if (!empty($r['ok'])) {
                $ok_n++;
            } else {
                $err[] = $r['message'];
            }
        }
        ppto_param_json(array(
            'ok' => $ok_n > 0,
            'message' => $ok_n . ' cuenta(s) asignada(s).' . (!empty($err) ? ' ' . $err[0] : ''),
            'asignadas' => $ok_n,
            'kpis' => ppto_param_contable_kpis($mysqli, $emp_id, $pla_cod),
        ));
    }

    if ($action === 'quitar') {
        $ppc_id = isset($_POST['ppc_id']) ? (int)$_POST['ppc_id'] : 0;
        $r = ppto_param_contable_quitar($mysqli, $emp_id, $ppc_id);
        if (!empty($r['ok'])) {
            $r['kpis'] = ppto_param_contable_kpis($mysqli, $emp_id, $pla_cod);
        }
        ppto_param_json($r);
    }

    if ($action === 'auditar') {
        ppto_param_json(ppto_param_contable_auditar($mysqli, $emp_id, $pla_cod, $pec_cod));
    }

    if ($action === 'copiar') {
        $anio_origen = isset($_POST['anio_origen']) ? (int)$_POST['anio_origen'] : 0;
        $anio_destino = isset($_POST['anio_destino']) ? (int)$_POST['anio_destino'] : 0;
        $sobreescribir = !empty($_POST['sobreescribir']);
        $r = ppto_param_contable_copiar($mysqli, $emp_id, $anio_origen, $anio_destino, $usu_id, $sobreescribir);
        if (!empty($r['ok']) && $pla_cod > 0) {
            $r['kpis'] = ppto_param_contable_kpis($mysqli, $emp_id, $pla_cod);
        }
        ppto_param_json($r);
    }

    if ($action === 'sugerir') {
        $ppa_id = isset($_REQUEST['ppa_id']) ? (int)$_REQUEST['ppa_id'] : 0;
        $top = isset($_REQUEST['top']) ? (int)$_REQUEST['top'] : 12;
        if ($pla_cod <= 0 || $ppa_id <= 0) {
            ppto_param_json(array('ok' => false, 'message' => 'Parametros invalidos.', 'sugerencias' => array()));
        }
        ppto_param_json(ppto_param_contable_sugerir($mysqli, $emp_id, $pla_cod, $ppa_id, $top));
    }

    if ($action === 'mapa') {
        $filtro = isset($_REQUEST['filtro']) ? $_REQUEST['filtro'] : 'todos';
        $con_mov = !empty($_REQUEST['con_movimientos']);
        if ($pla_cod <= 0) {
            ppto_param_json(array('ok' => false, 'message' => 'Sin plan de cuentas.', 'rows' => array()));
        }
        ppto_param_json(ppto_param_contable_mapa($mysqli, $emp_id, $pla_cod, $pec_cod, $con_mov, $filtro));
    }

    if ($action === 'ejecutado_preview') {
        $mes_desde = isset($_REQUEST['mes_desde']) ? (int)$_REQUEST['mes_desde'] : 1;
        $mes_hasta = isset($_REQUEST['mes_hasta']) ? (int)$_REQUEST['mes_hasta'] : 12;
        $ppe_id = isset($_REQUEST['ppe_id']) ? (int)$_REQUEST['ppe_id'] : 0;
        ppto_param_json(ppto_param_ejecutado_preview($mysqli, $emp_id, $anio, $mes_desde, $mes_hasta, $ppe_id));
    }

    if ($action === 'ejecutado_sync') {
        $mes_desde = isset($_POST['mes_desde']) ? (int)$_POST['mes_desde'] : (isset($_REQUEST['mes_desde']) ? (int)$_REQUEST['mes_desde'] : 1);
        $mes_hasta = isset($_POST['mes_hasta']) ? (int)$_POST['mes_hasta'] : (isset($_REQUEST['mes_hasta']) ? (int)$_REQUEST['mes_hasta'] : 12);
        $ppe_id = isset($_POST['ppe_id']) ? (int)$_POST['ppe_id'] : (isset($_REQUEST['ppe_id']) ? (int)$_REQUEST['ppe_id'] : 0);
        ppto_param_json(ppto_param_ejecutado_sincronizar($mysqli, $emp_id, $anio, $mes_desde, $mes_hasta, $usu_id, $ppe_id));
    }

    ppto_param_json(array('ok' => false, 'message' => 'Accion no reconocida.'));
} catch (Exception $e) {
    ppto_param_json(array('ok' => false, 'message' => 'Error: ' . $e->getMessage()));
}
