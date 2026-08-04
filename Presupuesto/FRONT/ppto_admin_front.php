<?php
/**
 * ppto_admin_front.php
 * Interfaz de Administraci&oacute;n de Presupuestos y Reglas de Asignaci&oacute;n (EXA PPTO).
 * Permite gestionar partidas, reglas, distribuciones mensuales y versiones.
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('../LOGICA/ppto_schema_logica.php');
require_once('../LOGICA/ppto_format_helpers.php');
require_once('../LOGICA/ppto_partidas_logica.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Emp_Cod) && isset($_SESSION['Ses_Emp_Cod'])) {
    $Ses_Emp_Cod = $_SESSION['Ses_Emp_Cod'];
}
if (!isset($Ses_Usu_Cod) && isset($_SESSION['Ses_Usu_Cod'])) {
    $Ses_Usu_Cod = $_SESSION['Ses_Usu_Cod'];
}

$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli_conn = $obBD_conexion->conexion;
if ($mysqli_conn) {
    $mysqli_conn->set_charset('utf8mb4');
}

$ppto_es_admin = ppto_usuario_es_admin();
if ($mysqli_conn && !$ppto_es_admin && !empty($Ses_Usu_Cod)) {
    $emp_admin_chk = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
    $ppto_es_admin = ppto_usuario_es_admin_db($mysqli_conn, (int)$Ses_Usu_Cod, $emp_admin_chk);
}
$ppto_regla_catalogo_json = ppto_json_encode_safe(ppto_regla_catalogo_ui());

if ($mysqli_conn) {
    ppto_schema_ensure($mysqli_conn);
    require_once __DIR__ . '/../LOGICA/ppto_partidas_logica.php';
    ppto_schema_ensure_partida_porcentaje($mysqli_conn);
}

include_once(__DIR__ . '/../LOGICA/ppto_persistencia_logica.php');

// Auto-creacion de tabla de reglas (legacy fallback)
if ($mysqli_conn) {
    @$mysqli_conn->query("CREATE TABLE IF NOT EXISTS `pre_reglas` (
      `Prg_Cod` INT AUTO_INCREMENT PRIMARY KEY,
      `Emp_Cod` INT NOT NULL,
      `Ppa_Cod` INT NOT NULL,
      `Prg_TipDoc` VARCHAR(50) NOT NULL,
      `Prg_CamEva` VARCHAR(100) NULL,
      `Prg_ValEsp` VARCHAR(100) NULL,
      `Prg_Sig` CHAR(1) NOT NULL DEFAULT '+',
      `Prg_CamMon` VARCHAR(100) NOT NULL,
      `Prg_Pri` INT NOT NULL DEFAULT 1,
      `Prg_Est` CHAR(1) NOT NULL DEFAULT 'A',
      `Prg_Des` VARCHAR(255) NULL,
      `Usu_Cod` INT NULL,
      `Prg_FecReg` DATE NULL,
      INDEX `idx_regla_emp_tipo` (`Emp_Cod`, `Prg_TipDoc`, `Prg_Pri`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}

// RESOLUCI&Oacute;N DE ISS-01: Endpoint AJAX para verificar duplicaci&oacute;n de c&oacute;digo de partida
if (isset($_GET['ajax_check_partida'])) {
    $cla = $mysqli_conn->real_escape_string($_GET['cla']);
    $ppa_id = isset($_GET['ppa_cod']) && $_GET['ppa_cod'] !== '' ? (int)$_GET['ppa_cod'] : 0;
    $emp_chk = isset($_GET['emp_cod']) ? (int)$_GET['emp_cod'] : (int)$Ses_Emp_Cod;
    $cond_exc = $ppa_id ? " AND ppa_id != $ppa_id " : "";
    
    $res_chk = $mysqli_conn->query("SELECT Ppa_Cod AS ppa_id FROM pre_partidas WHERE Emp_Cod = $emp_chk AND Ppa_Cla = '$cla' $cond_exc LIMIT 1");
    header('Content-Type: application/json; charset=utf-8');
    if ($res_chk && $res_chk->num_rows > 0) {
        echo json_encode(array('existe' => true));
    } else {
        echo json_encode(array('existe' => false));
    }
    exit();
}

// Procesar: Guardar o actualizar partida presupuestaria
if (isset($_POST['guardar_partida'])) {
    ppto_schema_ensure_partida_clase($mysqli_conn);
    ppto_schema_ensure_partida_porcentaje($mysqli_conn);

    $ppa_cod = isset($_POST['Ppa_Cod']) && $_POST['Ppa_Cod'] !== '' ? (int)$_POST['Ppa_Cod'] : null;
    $emp_cod = isset($_POST['emp_cod']) ? (int)$_POST['emp_cod'] : ppto_resolve_emp_id();
    $ppa_cla = $mysqli_conn->real_escape_string($_POST['Ppa_Cla']);
    $ppa_des = $mysqli_conn->real_escape_string($_POST['Ppa_Des']);
    $ppa_tip = $mysqli_conn->real_escape_string($_POST['Ppa_Tip']);
    $ppa_nat = $mysqli_conn->real_escape_string($_POST['Ppa_Nat']);
    $ppa_pad = isset($_POST['Ppa_Pad']) && $_POST['Ppa_Pad'] !== '' ? (int)$_POST['Ppa_Pad'] : "NULL";
    $ppa_niv = ppto_partida_nivel_desde_codigo($_POST['Ppa_Cla']);
    if (isset($_POST['Ppa_Niv']) && (int)$_POST['Ppa_Niv'] > 0) {
        $ppa_niv_post = (int)$_POST['Ppa_Niv'];
        if ($ppa_niv_post !== $ppa_niv) {
            $ppa_niv = $ppa_niv;
        }
    }
    if ($ppa_niv > 1 && $ppa_pad === "NULL") {
        header('Location: ppto_admin_front.php?tab=2&emp_cod=' . $emp_cod . '&err=partida_sin_padre');
        exit();
    }
    if ($ppa_pad !== "NULL") {
        $res_pad = $mysqli_conn->query(
            "SELECT COALESCE(NULLIF(Ppa_Clase, ''), 'D') AS ppa_clase
             FROM pre_partidas
             WHERE Ppa_Cod = $ppa_pad AND Emp_Cod = $emp_cod AND Ppa_Est = 'A'"
        );
        $pad_row = $res_pad ? $res_pad->fetch_assoc() : null;
        if (!$pad_row || $pad_row['ppa_clase'] !== 'G') {
            header('Location: ppto_admin_front.php?tab=2&emp_cod=' . $emp_cod . '&err=partida_padre_no_grupo');
            exit();
        }
    }
    if ($ppa_niv === 1) {
        $ppa_pad = "NULL";
    }
    $ppa_est = $mysqli_conn->real_escape_string($_POST['Ppa_Est']);
    $ppa_clase_raw = isset($_POST['Ppa_Clase']) ? $_POST['Ppa_Clase'] : 'D';
    $ppa_clase = ($ppa_clase_raw === 'G') ? 'G' : 'D';

    if ($ppa_cod && $ppa_clase === 'G') {
        $cnt_reg = ppto_partida_contar_reglas_activas($mysqli_conn, $ppa_cod, $emp_cod);
        if ($cnt_reg > 0) {
            header('Location: ppto_admin_front.php?tab=2&emp_cod=' . $emp_cod . '&err=partida_grupo_reglas');
            exit();
        }
    }

    $ppa_clase_sql = $mysqli_conn->real_escape_string($ppa_clase);
    $ppa_pct_sql = 'NULL';
    if ($ppa_clase === 'G' && isset($_POST['Ppa_Pct']) && trim($_POST['Ppa_Pct']) !== '') {
        $ppa_pct_val = (float)str_replace(',', '.', $_POST['Ppa_Pct']);
        if ($ppa_pct_val >= 0 && $ppa_pct_val <= 100) {
            $ppa_pct_sql = number_format(round($ppa_pct_val, 4), 4, '.', '');
        }
    }

    if ($ppa_cod) {
        $sql = "UPDATE pre_partidas SET 
                    Ppa_Cla = '$ppa_cla', Ppa_Des = '$ppa_des', Ppa_Tip = '$ppa_tip', 
                    Ppa_Nat = '$ppa_nat', Ppa_Pad = $ppa_pad, Ppa_Niv = $ppa_niv, Ppa_Clase = '$ppa_clase_sql', Ppa_Est = '$ppa_est',
                    Ppa_Pct = " . ($ppa_clase === 'G' ? $ppa_pct_sql : 'NULL') . "
                WHERE Ppa_Cod = $ppa_cod AND Emp_Cod = $emp_cod";
    } else {
        $sql = "INSERT INTO pre_partidas (Emp_Cod, Ppa_Cla, Ppa_Des, Ppa_Tip, Ppa_Nat, Ppa_Pad, Ppa_Niv, Ppa_Clase, Ppa_Pct, Ppa_Est, Ppa_Fec, Usu_Cod)
                VALUES ($emp_cod, '$ppa_cla', '$ppa_des', '$ppa_tip', '$ppa_nat', $ppa_pad, $ppa_niv, '$ppa_clase_sql', " . ($ppa_clase === 'G' ? $ppa_pct_sql : 'NULL') . ", '$ppa_est', CURDATE(), $Ses_Usu_Cod)";
    }

    $qs_guardar = 'tab=2&emp_cod=' . $emp_cod;
    if (isset($_POST['ani_filtro']) && $_POST['ani_filtro'] !== '') {
        $qs_guardar .= '&ani=' . (int)$_POST['ani_filtro'];
    }
    if (isset($_POST['ver_filtro']) && $_POST['ver_filtro'] !== '') {
        $qs_guardar .= '&ver=' . (int)$_POST['ver_filtro'];
    }
    if (isset($_POST['mes_filtro']) && $_POST['mes_filtro'] !== '') {
        $qs_guardar .= '&mes=' . (int)$_POST['mes_filtro'];
    }
    if (!empty($_POST['ver_inactivos'])) {
        $qs_guardar .= '&ver_inactivos=1';
    }

    if (!$mysqli_conn->query($sql)) {
        header('Location: ppto_admin_front.php?' . $qs_guardar . '&err=partida_guardar');
        exit();
    }

    if ($ppa_cod && $mysqli_conn->affected_rows === 0) {
        $chk = $mysqli_conn->query("SELECT Ppa_Cod AS ppa_id FROM pre_partidas WHERE Ppa_Cod = $ppa_cod AND Emp_Cod = $emp_cod LIMIT 1");
        if (!$chk || $chk->num_rows === 0) {
            header('Location: ppto_admin_front.php?' . $qs_guardar . '&err=partida_empresa');
            exit();
        }
    }

    header('Location: ppto_admin_front.php?' . $qs_guardar . '&msg=partida_guardada');
    exit();
}

// Anular o reactivar partida presupuestaria
if (isset($_GET['estado_partida'])) {
    $ppa_est_id = (int)$_GET['estado_partida'];
    $nuevo_est = (isset($_GET['nuevo_est']) && $_GET['nuevo_est'] === 'A') ? 'A' : 'I';
    $emp_est = isset($_GET['emp_cod']) ? (int)$_GET['emp_cod'] : (int)$Ses_Emp_Cod;
    if ($ppa_est_id > 0 && $mysqli_conn) {
        $resultado_est = ppto_partida_cambiar_estado($mysqli_conn, $ppa_est_id, $emp_est, $nuevo_est);
    }
    $qs_est = 'tab=2&emp_cod=' . $emp_est;
    if (isset($resultado_est) && $nuevo_est === 'I' && $resultado_est['reglas_inactivadas'] > 0) {
        $qs_est .= '&msg=partida_reglas_inactivadas&cnt=' . (int)$resultado_est['reglas_inactivadas'];
    }
    if (isset($_GET['ani'])) {
        $qs_est .= '&ani=' . (int)$_GET['ani'];
    }
    if (isset($_GET['ver'])) {
        $qs_est .= '&ver=' . (int)$_GET['ver'];
    }
    if (isset($_GET['mes'])) {
        $qs_est .= '&mes=' . (int)$_GET['mes'];
    }
    if (isset($_GET['ver_inactivos']) && $_GET['ver_inactivos'] === '1') {
        $qs_est .= '&ver_inactivos=1';
    }
    header('Location: ppto_admin_front.php?' . $qs_est);
    exit();
}

// Procesar: Guardar o actualizar regla de asignaci&oacute;n autom&aacute;tica
if (isset($_POST['guardar_regla'])) {
    $prg_cod = isset($_POST['Prg_Cod']) && $_POST['Prg_Cod'] !== '' ? (int)$_POST['Prg_Cod'] : null;
    $emp_cod = isset($_POST['emp_cod']) ? (int)$_POST['emp_cod'] : (isset($_REQUEST['emp_cod']) ? (int)$_REQUEST['emp_cod'] : (int)$Ses_Emp_Cod);
    $ppa_cod = (int)$_POST['Ppa_Cod'];
    $prg_tip_doc = $mysqli_conn->real_escape_string($_POST['Prg_TipDoc']);
    $prg_campo = isset($_POST['Prg_Campo']) && $_POST['Prg_Campo'] !== '' ? "'" . $mysqli_conn->real_escape_string($_POST['Prg_Campo']) . "'" : "NULL";
    $prg_valor = isset($_POST['Prg_Valor']) && $_POST['Prg_Valor'] !== '' ? "'" . $mysqli_conn->real_escape_string($_POST['Prg_Valor']) . "'" : "NULL";
    $prg_signo = $mysqli_conn->real_escape_string($_POST['Prg_Signo']);
    $prg_cam_mon = $mysqli_conn->real_escape_string($_POST['Prg_CamMon']);
    $prg_pri = (int)$_POST['Prg_Pri'];
    $prg_des = $mysqli_conn->real_escape_string($_POST['Prg_Des']);
    $prg_est = $mysqli_conn->real_escape_string($_POST['Prg_Est']);
    $campo_raw = isset($_POST['Prg_Campo']) ? trim($_POST['Prg_Campo']) : '';
    $valor_raw = isset($_POST['Prg_Valor']) ? trim($_POST['Prg_Valor']) : '';
    $cam_mon_raw = trim($_POST['Prg_CamMon']);

    if (!$ppto_es_admin && !ppto_regla_catalogo_validar($prg_tip_doc, $campo_raw, $valor_raw, $cam_mon_raw)) {
        $qs = 'tab=5&emp_cod=' . $emp_cod . '&err=regla_no_admin';
        if (isset($_REQUEST['ani'])) {
            $qs .= '&ani=' . (int)$_REQUEST['ani'];
        }
        if (isset($_REQUEST['ver'])) {
            $qs .= '&ver=' . (int)$_REQUEST['ver'];
        }
        if (isset($_REQUEST['mes'])) {
            $qs .= '&mes=' . (int)$_REQUEST['mes'];
        }
        header('Location: ppto_admin_front.php?' . $qs);
        exit();
    }

    if (!ppto_partida_es_destino_regla($mysqli_conn, $ppa_cod, $emp_cod)) {
        $qs = 'tab=5&emp_cod=' . $emp_cod . '&err=regla_partida_invalida';
        if (isset($_REQUEST['ani'])) {
            $qs .= '&ani=' . (int)$_REQUEST['ani'];
        }
        if (isset($_REQUEST['ver'])) {
            $qs .= '&ver=' . (int)$_REQUEST['ver'];
        }
        if (isset($_REQUEST['mes'])) {
            $qs .= '&mes=' . (int)$_REQUEST['mes'];
        }
        header('Location: ppto_admin_front.php?' . $qs);
        exit();
    }

    if ($prg_cod) {
        $sql = "UPDATE pre_reglas SET 
                    Ppa_Cod = $ppa_cod, Prg_TipDoc = '$prg_tip_doc', Prg_CamEva = $prg_campo, 
                    Prg_ValEsp = $prg_valor, Prg_Sig = '$prg_signo', Prg_CamMon = '$prg_cam_mon', 
                    Prg_Pri = $prg_pri, Prg_Des = '$prg_des', Prg_Est = '$prg_est'
                WHERE Prg_Cod = $prg_cod AND Emp_Cod = $emp_cod";
    } else {
        $sql = "INSERT INTO pre_reglas (Emp_Cod, Ppa_Cod, Prg_TipDoc, Prg_CamEva, Prg_ValEsp, Prg_Sig, Prg_CamMon, Prg_Pri, Prg_Est, Prg_Des, Usu_Cod, Prg_FecReg)
                VALUES ($emp_cod, $ppa_cod, '$prg_tip_doc', $prg_campo, $prg_valor, '$prg_signo', '$prg_cam_mon', $prg_pri, '$prg_est', '$prg_des', $Ses_Usu_Cod, CURDATE())";
    }
    $mysqli_conn->query($sql);
    $qs = 'tab=5&emp_cod=' . $emp_cod;
    if (isset($_REQUEST['ani'])) {
        $qs .= '&ani=' . (int)$_REQUEST['ani'];
    }
    if (isset($_REQUEST['ver'])) {
        $qs .= '&ver=' . (int)$_REQUEST['ver'];
    }
    if (isset($_REQUEST['mes'])) {
        $qs .= '&mes=' . (int)$_REQUEST['mes'];
    }
    header('Location: ppto_admin_front.php?' . $qs);
    exit();
}

// Procesar: Subir o bajar la prioridad de evaluaci&oacute;n de una regla
if (isset($_GET['prioridad_regla'])) {
    $prg_cod = (int)$_GET['prioridad_regla'];
    $dir = $_GET['dir'];
    $res = $mysqli_conn->query("SELECT Prg_Pri AS prg_prioridad, Prg_TipDoc AS prg_tipo_documento FROM pre_reglas WHERE Prg_Cod = $prg_cod AND Emp_Cod = $Ses_Emp_Cod LIMIT 1");
    if ($res && $regla_actual = $res->fetch_assoc()) {
        $pri_actual = (int)$regla_actual['prg_prioridad'];
        $tip_doc = $regla_actual['prg_tipo_documento'];
        if ($dir === 'up') {
            $res_swap = $mysqli_conn->query("SELECT Prg_Cod AS prg_id, Prg_Pri AS prg_prioridad FROM pre_reglas WHERE Emp_Cod = $Ses_Emp_Cod AND Prg_TipDoc = '$tip_doc' AND Prg_Pri < $pri_actual ORDER BY Prg_Pri DESC LIMIT 1");
        } else {
            $res_swap = $mysqli_conn->query("SELECT Prg_Cod AS prg_id, Prg_Pri AS prg_prioridad FROM pre_reglas WHERE Emp_Cod = $Ses_Emp_Cod AND Prg_TipDoc = '$tip_doc' AND Prg_Pri > $pri_actual ORDER BY Prg_Pri ASC LIMIT 1");
        }
        if ($res_swap && $regla_swap = $res_swap->fetch_assoc()) {
            $pri_swap = (int)$regla_swap['prg_prioridad'];
            $cod_swap = (int)$regla_swap['prg_id'];
            $mysqli_conn->query("UPDATE pre_reglas SET Prg_Pri = $pri_swap WHERE Prg_Cod = $prg_cod");
            $mysqli_conn->query("UPDATE pre_reglas SET Prg_Pri = $pri_actual WHERE Prg_Cod = $cod_swap");
        }
    }
    header("Location: ppto_admin_front.php?tab=5");
    exit();
}

// Anular o reactivar regla de asignacion
if (isset($_GET['estado_regla'])) {
    $prg_est_id = (int)$_GET['estado_regla'];
    $nuevo_est = (isset($_GET['nuevo_est']) && $_GET['nuevo_est'] === 'A') ? 'A' : 'I';
    $emp_est = isset($_GET['emp_cod']) ? (int)$_GET['emp_cod'] : ppto_resolve_emp_id();
    $actualizado = false;
    if ($prg_est_id > 0 && $mysqli_conn) {
        $est_esc = $mysqli_conn->real_escape_string($nuevo_est);
        $mysqli_conn->query("UPDATE pre_reglas SET Prg_Est = '$est_esc' WHERE Prg_Cod = $prg_est_id AND Emp_Cod = $emp_est");
        $actualizado = ($mysqli_conn->affected_rows > 0);
    }
    $qs_est = 'tab=5&emp_cod=' . $emp_est;
    if ($actualizado) {
        $qs_est .= ($nuevo_est === 'A') ? '&msg=regla_activada' : '&msg=regla_inactivada';
    } else {
        $qs_est .= '&err=regla_estado';
    }
    if (isset($_GET['ani'])) {
        $qs_est .= '&ani=' . (int)$_GET['ani'];
    }
    if (isset($_GET['ver'])) {
        $qs_est .= '&ver=' . (int)$_GET['ver'];
    }
    if (isset($_GET['mes'])) {
        $qs_est .= '&mes=' . (int)$_GET['mes'];
    }
    if (isset($_GET['ver_reglas_inactivas']) && $_GET['ver_reglas_inactivas'] === '1') {
        $qs_est .= '&ver_reglas_inactivas=1';
    } elseif ($actualizado && $nuevo_est === 'I') {
        $qs_est .= '&ver_reglas_inactivas=1';
    }
    header('Location: ppto_admin_front.php?' . $qs_est);
    exit();
}

// Procesar AJAX: Consultar listado de documentos de ejecuci&oacute;n
if (isset($_GET['ajax_partida_detalle'])) {
    $ppe_cod = (int)$_GET['ppe_cod'];
    $ppa_cod = (int)$_GET['ppa_cod'];
    $docs = ppto_persistencia_consultar($mysqli_conn, 12, array('ppe_id' => $ppe_cod, 'ppa_id' => $ppa_cod));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($docs);
    exit();
}

// Procesar: Marcar alerta presupuestaria como le&iacute;da
if (isset($_GET['marcar_leida'])) {
    $pal_cod = (int)$_GET['marcar_leida'];
    $mysqli_conn->query("UPDATE pre_alertas SET Pal_Lei = 'L' WHERE Pal_Cod = $pal_cod");
    header("Location: ppto_admin_front.php?tab=4");
    exit();
}

// Procesar: Guardar cambios manuales en la distribuci&oacute;n mensual
if (isset($_POST['guardar_mensual'])) {
    $ppe_cod = (int)$_POST['ppe_cod'];
    $emp_guardar = isset($_POST['emp_cod']) ? (int)$_POST['emp_cod'] : ppto_resolve_emp_id();
    $ani_guardar = isset($_POST['ani_filtro']) ? (int)$_POST['ani_filtro'] : (int)date('Y');
    $ver_guardar = isset($_POST['ver_req']) ? (int)$_POST['ver_req'] : 1;
    $mes_guardar = isset($_POST['mes_filtro']) ? (int)$_POST['mes_filtro'] : (int)date('n');
    $valores = isset($_POST['valores']) ? $_POST['valores'] : array();
    $map_rubro_bloqueo = ppto_partidas_map_rubro_proyecto($mysqli_conn, $emp_guardar, $ppe_cod);
    $intentos_bloqueados = 0;

    foreach ($valores as $ppa_cod => $meses) {
        $ppa_cod = (int)$ppa_cod;
        if (isset($map_rubro_bloqueo[$ppa_cod])) {
            $intentos_bloqueados++;
            continue;
        }
        foreach ($meses as $mes => $monto) {
            $mes = (int)$mes;
            $monto = (float)$monto;
            $mysqli_conn->query("INSERT INTO pre_detalle (Ppe_Cod, Ppa_Cod, Pde_Mes, Pde_Mon) 
                                VALUES ($ppe_cod, $ppa_cod, $mes, $monto) 
                                ON DUPLICATE KEY UPDATE Pde_Mon = $monto");
        }
    }

    $qs_mensual = 'tab=3&emp_cod=' . $emp_guardar . '&ani=' . $ani_guardar . '&ver=' . $ver_guardar . '&mes=' . $mes_guardar;
    if ($intentos_bloqueados > 0) {
        $qs_mensual .= '&err=partida_en_proyecto';
    } else {
        $qs_mensual .= '&msg=mensual_guardado';
    }
    header('Location: ppto_admin_front.php?' . $qs_mensual);
    exit();
}

// Procesar: Guardar y parametrizar carga de nueva versi&oacute;n presupuestaria
if (isset($_POST['guardar_cargar'])) {
    $emp_cod = isset($_POST['emp_cod']) ? (int)$_POST['emp_cod'] : ppto_resolve_emp_id();
    $ani = (int)$_POST['Ppe_Ani'];
    $ver = (int)$_POST['Ppe_Ver'];
    $des = $mysqli_conn->real_escape_string($_POST['Ppe_Des']);
    $est = $mysqli_conn->real_escape_string($_POST['Ppe_Est']);
    $cargar_modo = isset($_POST['cargar_modo']) ? $_POST['cargar_modo'] : 'manual';

    $mysqli_conn->query("INSERT INTO pre_presupuesto (Emp_Cod, Ppe_Ani, Ppe_Ver, Ppe_Des, Ppe_Est, Ppe_FecReg, Usu_Cod)
                         VALUES ($emp_cod, $ani, $ver, '$des', '$est', CURDATE(), $Ses_Usu_Cod)
                         ON DUPLICATE KEY UPDATE Ppe_Des = '$des', Ppe_Est = '$est'");
    
    $res_id = $mysqli_conn->query("SELECT Ppe_Cod AS ppe_id FROM pre_presupuesto WHERE Emp_Cod = $emp_cod AND Ppe_Ani = $ani AND Ppe_Ver = $ver LIMIT 1");
    $intentos_bloqueados = 0;
    if ($res_id && $row_id = $res_id->fetch_assoc()) {
        $ppe_cod = $row_id['ppe_id'];
        $map_rubro_bloqueo = ppto_partidas_map_rubro_proyecto($mysqli_conn, $emp_cod, null);

        if ($cargar_modo === 'copiar') {
            $anio_origen = (int)$_POST['copiar_anio'];
            $incremento = (float)$_POST['copiar_incremento'];
            $factor = 1.00 + ($incremento / 100.00);

            $res_origen = $mysqli_conn->query("SELECT pd.Ppa_Cod AS ppa_id, pd.Pde_Mes AS pde_mes, pd.Pde_Mon AS pde_monto 
                                               FROM pre_detalle pd 
                                               INNER JOIN pre_presupuesto pp ON pd.Ppe_Cod = pp.Ppe_Cod 
                                               WHERE pp.Emp_Cod = $emp_cod AND pp.Ppe_Ani = $anio_origen AND pp.Ppe_Est = 'A'");
            if ($res_origen) {
                while ($row_o = $res_origen->fetch_assoc()) {
                    $ppa_cod = (int)$row_o['ppa_id'];
                    if (isset($map_rubro_bloqueo[$ppa_cod])) {
                        $intentos_bloqueados++;
                        continue;
                    }
                    $mes = (int)$row_o['pde_mes'];
                    $monto_nuevo = (float)$row_o['pde_monto'] * $factor;
                    $mysqli_conn->query("INSERT INTO pre_detalle (Ppe_Cod, Ppa_Cod, Pde_Mes, Pde_Mon) 
                                        VALUES ($ppe_cod, $ppa_cod, $mes, $monto_nuevo) 
                                        ON DUPLICATE KEY UPDATE Pde_Mon = $monto_nuevo");
                }
            }
        } elseif ($cargar_modo === 'anual') {
            $valores_anual = isset($_POST['valores_anual']) ? $_POST['valores_anual'] : array();
            foreach ($valores_anual as $ppa_cod => $monto_anual) {
                $ppa_cod = (int)$ppa_cod;
                if (isset($map_rubro_bloqueo[$ppa_cod])) {
                    $intentos_bloqueados++;
                    continue;
                }
                $monto_mensual = (float)$monto_anual / 12.00;
                for ($mes = 1; $mes <= 12; $mes++) {
                    $mysqli_conn->query("INSERT INTO pre_detalle (Ppe_Cod, Ppa_Cod, Pde_Mes, Pde_Mon) 
                                        VALUES ($ppe_cod, $ppa_cod, $mes, $monto_mensual) 
                                        ON DUPLICATE KEY UPDATE Pde_Mon = $monto_mensual");
                }
            }
        } else {
            $valores = isset($_POST['valores_cargar']) ? $_POST['valores_cargar'] : array();
            foreach ($valores as $ppa_cod => $meses) {
                $ppa_cod = (int)$ppa_cod;
                if (isset($map_rubro_bloqueo[$ppa_cod])) {
                    $intentos_bloqueados++;
                    continue;
                }
                foreach ($meses as $mes => $monto) {
                    $mes = (int)$mes;
                    $monto = (float)$monto;
                    $mysqli_conn->query("INSERT INTO pre_detalle (Ppe_Cod, Ppa_Cod, Pde_Mes, Pde_Mon) 
                                        VALUES ($ppe_cod, $ppa_cod, $mes, $monto) 
                                        ON DUPLICATE KEY UPDATE Pde_Mon = $monto");
                }
            }
        }
    }
    $qs_cargar = 'tab=6&emp_cod=' . $emp_cod;
    if (!empty($intentos_bloqueados)) {
        $qs_cargar .= '&err=partida_en_proyecto';
    } else {
        $qs_cargar .= '&msg=cargar_guardado';
    }
    header('Location: ppto_admin_front.php?' . $qs_cargar);
    exit();
}

$emp_filtro = isset($_REQUEST['emp_cod']) ? (int)$_REQUEST['emp_cod'] : (int)$Ses_Emp_Cod;
$ani_filtro = isset($_REQUEST['ani']) ? (int)$_REQUEST['ani'] : (int)date('Y');
$mes_acumulado = isset($_REQUEST['mes']) ? (int)$_REQUEST['mes'] : (int)date('n');
$active_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;

$res_empresas = $mysqli_conn->query("SELECT Emp_Cod, Emp_Des FROM empresas WHERE Emp_Est = 'A' OR Emp_Est = 'S' ORDER BY Emp_Des");
$empresas = array();
if ($res_empresas) {
    while ($row = $res_empresas->fetch_assoc()) {
        $empresas[] = $row;
    }
}
if (empty($empresas)) {
    $empresas[] = array('Emp_Cod' => $Ses_Emp_Cod, 'Emp_Des' => isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'Empresa Actual');
}

$res_anios = $mysqli_conn->query("SELECT DISTINCT Ppe_Ani AS ppe_anio FROM pre_presupuesto WHERE Emp_Cod = $emp_filtro ORDER BY Ppe_Ani DESC");
$anios = array();
if ($res_anios) {
    while ($row = $res_anios->fetch_assoc()) {
        $anios[] = $row['ppe_anio'];
    }
}
if (empty($anios)) {
    $anios[] = date('Y');
}
$anio_actual = (int)date('Y');
$anios_filtro = array();
for ($y = $anio_actual - 3; $y <= $anio_actual + 5; $y++) {
    $anios_filtro[$y] = $y;
}
foreach ($anios as $a_db) {
    $anios_filtro[(int)$a_db] = (int)$a_db;
}
krsort($anios_filtro, SORT_NUMERIC);
$anios_filtro = array_values($anios_filtro);
$anios_cargar_opts = array();
for ($y = $anio_actual; $y <= $anio_actual + 5; $y++) {
    $anios_cargar_opts[] = $y;
}

$res_vers = $mysqli_conn->query("SELECT Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion, Ppe_Est AS ppe_estado, Ppe_Cod AS ppe_id FROM pre_presupuesto WHERE Emp_Cod = $emp_filtro AND Ppe_Ani = $ani_filtro ORDER BY Ppe_Ver DESC");
$versiones = array();
if ($res_vers) {
    while ($row = $res_vers->fetch_assoc()) {
        $versiones[] = $row;
    }
}

$ver_filtro = isset($_REQUEST['ver']) ? (int)$_REQUEST['ver'] : (isset($versiones[0]['ppe_version']) ? (int)$versiones[0]['ppe_version'] : 1);

$ppe_cod_filtro = 0;
foreach ($versiones as $v) {
    if ((int)$v['ppe_version'] === $ver_filtro) {
        $ppe_cod_filtro = (int)$v['ppe_id'];
        break;
    }
}
if (!$ppe_cod_filtro && !empty($versiones)) {
    $ppe_cod_filtro = (int)$versiones[0]['ppe_id'];
}

$reporte_datos = ppto_persistencia_consultar($mysqli_conn, 8, array(
    'Emp_Cod' => $emp_filtro,
    'ppe_anio' => $ani_filtro,
    'ppe_id' => $ppe_cod_filtro,
    'pej_mes' => $mes_acumulado
));
$ppa_proyecto_map = ppto_consulta_ppa_ids_proyecto($mysqli_conn, $emp_filtro, $ppe_cod_filtro);
$reporte_plan_estandar = ppto_consulta_filtrar_plan_estandar($reporte_datos, $ppa_proyecto_map);

$totales_hoja = ppto_consulta_sumar_hojas($reporte_plan_estandar);
$tot_presupuesto = $totales_hoja['presupuestado'];
$tot_ejecutado = $totales_hoja['ejecutado'];
$tot_disponible = $totales_hoja['disponible'];

$metricas_consolidado = ppto_admin_metricas_consolidado($mysqli_conn, $emp_filtro, $ppe_cod_filtro, $mes_acumulado);
$ver_descripcion = '';
$ver_estado_lbl = '';
foreach ($versiones as $v) {
    if ((int)$v['ppe_version'] === $ver_filtro) {
        $ver_descripcion = $v['ppe_descripcion'];
        $ver_estado_lbl = $v['ppe_estado'];
        break;
    }
}
$dash_url_base = 'dashboard_front.php?emp_cod=' . (int)$emp_filtro
    . '&ani=' . (int)$ani_filtro
    . '&ver=' . (int)$ppe_cod_filtro
    . '&mes=' . (int)$mes_acumulado;
$metricas_hay_proyectos = !empty($metricas_consolidado['proyectos']);
$metricas_gap_proyecto = round($metricas_consolidado['total_vigente'] - $tot_presupuesto, 2);

$alertas_activas = array();
if ($ppe_cod_filtro) {
    $alertas_activas = ppto_persistencia_consultar($mysqli_conn, 9, array(
        'Emp_Cod' => $emp_filtro,
        'ppe_id' => $ppe_cod_filtro
    ));
}
$cant_alertas = count($alertas_activas);

$ver_inactivos = isset($_REQUEST['ver_inactivos']) && $_REQUEST['ver_inactivos'] === '1';
$ver_reglas_inactivas = isset($_REQUEST['ver_reglas_inactivas']) && $_REQUEST['ver_reglas_inactivas'] === '1';
$partidas_todas = ppto_partidas_listar($mysqli_conn, array(
    'Emp_Cod' => $emp_filtro,
    'incluir_inactivas' => $ver_inactivos
));
$partidas_activas = ppto_partidas_listar($mysqli_conn, array('Emp_Cod' => $emp_filtro, 'solo_activas' => true));
$partidas_grupo = ppto_partidas_listar($mysqli_conn, array('Emp_Cod' => $emp_filtro, 'solo_activas' => true, 'clase' => 'G'));
$partidas_detalle = ppto_partidas_listar($mysqli_conn, array('Emp_Cod' => $emp_filtro, 'solo_activas' => true, 'clase' => 'D'));
$partidas_map_rubro_proyecto = ppto_partidas_map_rubro_proyecto($mysqli_conn, $emp_filtro, $ppe_cod_filtro);
$partidas_detalle_mensual = ppto_partidas_filtrar_mensual_libres($partidas_detalle, $partidas_map_rubro_proyecto);
$mensual_filtra_proyecto = !empty($partidas_map_rubro_proyecto);
$mensual_partidas_ocultas = count($partidas_detalle) - count($partidas_detalle_mensual);
$mensual_proyectos_activos = array();
foreach ($partidas_map_rubro_proyecto as $rubros) {
    foreach ($rubros as $rb) {
        $mensual_proyectos_activos[$rb['proy_id']] = $rb['proy_nombre'];
    }
}
$partidas_map_rubro_empresa = ppto_partidas_map_rubro_proyecto($mysqli_conn, $emp_filtro, null);
$partidas_detalle_cargar = ppto_partidas_filtrar_mensual_libres($partidas_detalle, $partidas_map_rubro_empresa);
$cargar_filtra_proyecto = !empty($partidas_map_rubro_empresa);
$cargar_partidas_ocultas = count($partidas_detalle) - count($partidas_detalle_cargar);
$cargar_proyectos_activos = array();
foreach ($partidas_map_rubro_empresa as $rubros) {
    foreach ($rubros as $rb) {
        $cargar_proyectos_activos[$rb['proy_id']] = $rb['proy_nombre'];
    }
}
$partidas_padre_pool = ppto_partidas_pool_padre($mysqli_conn, $emp_filtro);
$partidas_catalogo_js = array();
foreach ($partidas_todas as $p_cat) {
    if ($p_cat['ppa_estado'] !== 'A') {
        continue;
    }
    $partidas_catalogo_js[] = array(
        'ppa_id' => (int)$p_cat['ppa_id'],
        'ppa_codigo_clasificacion' => $p_cat['ppa_codigo_clasificacion'],
        'ppa_descripcion' => $p_cat['ppa_descripcion'],
        'ppa_nivel' => ppto_partida_nivel_visual($p_cat),
        'ppa_clase' => isset($p_cat['ppa_clase']) ? $p_cat['ppa_clase'] : 'D',
        'ppa_padre_id' => !empty($p_cat['ppa_padre_id']) ? (int)$p_cat['ppa_padre_id'] : null,
        'ppa_tipo' => $p_cat['ppa_tipo'],
        'ppa_naturaleza' => $p_cat['ppa_naturaleza'],
    );
}

$res_reglas = $mysqli_conn->query("SELECT r.*, p.Ppa_Cla AS ppa_codigo_clasificacion, p.Ppa_Des AS ppa_descripcion, p.Ppa_Est AS ppa_estado, COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS ppa_clase
                                   FROM pre_reglas r 
                                   INNER JOIN pre_partidas p ON r.Ppa_Cod = p.Ppa_Cod 
                                   WHERE r.Emp_Cod = $emp_filtro "
                                   . ($ver_reglas_inactivas ? '' : " AND r.Prg_Est = 'A' ")
                                   . "ORDER BY r.Prg_TipDoc, r.Prg_Pri ASC");
$reglas_todas = array();
if ($res_reglas) {
    while ($row = $res_reglas->fetch_assoc()) {
        $reglas_todas[] = $row;
    }
}

$emp_nom_display = 'Empresa Actual';
foreach ($empresas as $e) {
    if ((int)$e['Emp_Cod'] === $emp_filtro) {
        $emp_nom_display = $e['Emp_Des'];
        break;
    }
}

$tab_qs = 'emp_cod=' . $emp_filtro . '&amp;ani=' . $ani_filtro . '&amp;ver=' . $ver_filtro . '&amp;mes=' . $mes_acumulado;
if ($ver_inactivos) {
    $tab_qs .= '&amp;ver_inactivos=1';
}
if ($ver_reglas_inactivas) {
    $tab_qs .= '&amp;ver_reglas_inactivas=1';
}
$ppto_admin_qs_raw = 'emp_cod=' . $emp_filtro . '&ani=' . $ani_filtro . '&ver=' . $ver_filtro . '&mes=' . $mes_acumulado
    . ($ver_inactivos ? '&ver_inactivos=1' : '')
    . ($ver_reglas_inactivas ? '&ver_reglas_inactivas=1' : '');
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Administraci&oacute;n de Presupuesto - EXA</title>
    <?php require_once(__DIR__ . '/../../contabilidad/FRONT/con_model3_assets.php'); ?>
    <!-- Carga unificada de validaciones y JS de presupuesto -->
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <script>
        var PPTO_USUARIO_ES_ADMIN = <?php echo $ppto_es_admin ? 'true' : 'false'; ?>;
        var PPTO_REGLAS_CATALOGO = <?php echo $ppto_regla_catalogo_json; ?>;
        var PPTO_ADMIN_QS = <?php echo ppto_json_encode_safe($ppto_admin_qs_raw); ?>;
        var PPTO_PARTIDAS_PADRE_POOL = <?php echo ppto_json_encode_safe($partidas_padre_pool); ?>;
        var PPTO_PARTIDAS_CATALOGO = <?php echo ppto_json_encode_safe($partidas_catalogo_js); ?>;
    </script>
    <script src="../VALIDACIONES/ppto_validaciones_js.js"></script>
    <style>
        #modal_partida.exa-pre-modal-overlay { z-index: 10050; }
        #modal_regla.exa-pre-modal-overlay { z-index: 10050; }
        #modal_pc_agregar.exa-pre-modal-overlay,
        #modal_pc_copiar.exa-pre-modal-overlay,
        #modal_pc_sugerir.exa-pre-modal-overlay { z-index: 10050; }
        .ppto-metricas-callout {
            padding: 14px 16px;
            margin-bottom: 18px;
            border-radius: 6px;
            border: 1px solid #bee3f8;
            background: #ebf8ff;
            font-size: 12px;
            color: #2c5282;
            line-height: 1.5;
        }
        .ppto-metricas-callout strong { color: #1a365d; }
        .ppto-metricas-seccion {
            margin-bottom: 22px;
        }
        .ppto-metricas-seccion-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .ppto-metricas-seccion-title {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #2d3748;
        }
        .ppto-metricas-seccion-desc {
            margin: 4px 0 0;
            font-size: 11px;
            color: #718096;
            max-width: 720px;
        }
        .ppto-metricas-nota-proy {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #fbd38d;
            background: #fffaf0;
            font-size: 11px;
            color: #744210;
        }
        .ppto-metricas-kpi-sub {
            display: block;
            font-size: 10px;
            font-weight: 500;
            color: #718096;
            margin-top: 2px;
        }
        .ppto-mensual-callout {
            padding: 14px 16px;
            margin-bottom: 14px;
            border-radius: 6px;
            border: 1px solid #c6f6d5;
            background: #f0fff4;
            font-size: 12px;
            color: #276749;
            line-height: 1.5;
        }
        .ppto-mensual-callout a { font-weight: 600; }
        .exa-pre-row-inactiva td {
            background-color: #fff5f5 !important;
            color: #4a5568 !important;
        }
        .exa-pre-row-inactiva td strong {
            color: #2d3748 !important;
        }
        .exa-pre-row-inactiva td code {
            color: #2d3748 !important;
        }
        tr.exa-pre-row-inactiva-first td {
            border-top: 2px solid #fc8181 !important;
        }
        .exa-pre-section-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .exa-pre-toggle-inactivas {
            font-size: 12px;
            font-weight: normal;
            margin: 0;
            color: #4a5568;
            cursor: pointer;
        }
        .exa-pre-estado-badge {
            font-size: 11px;
        }
        .exa-pre-actions-cell .btn-xs:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .exa-pre-actions-cell .btn-xs {
            margin: 0 1px;
            min-width: 26px;
        }
        .exa-pre-actions-cell .btn-xs i {
            pointer-events: none;
        }
        .ppto-partida-tree-indent {
            display: inline-block;
            color: #cbd5e0;
            font-weight: 400;
            user-select: none;
        }
        .ppto-tree-gutter {
            display: inline-block;
            width: 28px;
            height: 1px;
            vertical-align: middle;
            border-left: 1px solid #e2e8f0;
            margin-right: 2px;
        }
        .ppto-tree-branch {
            display: inline-block;
            color: #a0aec0;
            font-size: 11px;
            font-weight: 400;
            margin-right: 6px;
            vertical-align: middle;
        }
        #tabla_partidas_catalogo td.ppto-tree-cell-codigo,
        #tabla_partidas_catalogo td.ppto-tree-cell-desc {
            vertical-align: middle;
        }
        #tabla_partidas_catalogo tr.ppto-tree-depth-1 td.ppto-tree-cell-codigo {
            background: #f8fafc;
        }
        #tabla_partidas_catalogo tr.ppto-tree-grupo td.ppto-tree-cell-codigo {
            background: #ebf8ff;
        }
        .ppto-partida-tree-codigo {
            font-weight: 700;
        }
        .ppto-partida-ubicacion {
            grid-column: 1 / -1;
            padding: 12px 14px;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 4px;
        }
        .ppto-partida-seccion-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 10px;
        }
        .ppto-partida-ubicacion-modo {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .ppto-partida-radio-opt {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #2d3748;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            margin: 0;
        }
        .ppto-partida-radio-opt:has(input:checked) {
            border-color: #3182ce;
            background: #ebf8ff;
            color: #2c5282;
        }
        .ppto-partida-ub-resumen {
            font-size: 11px;
            color: #4a5568;
            margin-top: 8px;
        }
        .ppto-partida-ub-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #2d3748;
            margin-right: 8px;
        }
        .ppto-partida-edit-ub {
            grid-column: 1 / -1;
            font-size: 12px;
            color: #4a5568;
            padding: 10px 12px;
            background: #edf2f7;
            border-radius: 6px;
            margin-bottom: 4px;
        }
        #form_partida_grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }

        /* --- Admin: estilo alineado al Dashboard (sin cambiar paleta) --- */
        .ppto-admin-body {
            padding: 20px !important;
        }
        .ppto-admin-filter-section {
            background: var(--v2-bg-subtle, #f7fafc);
            border: var(--v2-elev-border, 1px solid #e2e8f0);
            border-radius: var(--v2-radius, 8px);
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .ppto-admin-filter-form { margin: 0; }
        .ppto-admin-filter-grid {
            display: grid;
            grid-template-columns: 88px minmax(0, 1.35fr) minmax(0, 1.15fr) auto;
            gap: 10px 12px;
            align-items: end;
        }
        .ppto-admin-filter-field.field-ani { max-width: 96px; }
        .ppto-admin-filter-field.field-actions { width: auto; justify-self: start; }
        .ppto-admin-filter-field label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            margin-bottom: 4px;
            white-space: nowrap;
        }
        .ppto-admin-filter-field .form-control {
            width: 100%;
            height: 32px;
            font-size: 12px;
        }
        .ppto-admin-filter-actions {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            flex-wrap: nowrap;
            height: 32px;
            padding-top: 0;
        }
        .ppto-admin-filter-actions .btn {
            margin: 0;
            padding: 5px 10px;
            width: auto;
            min-width: 0;
            flex: 0 0 auto;
            white-space: nowrap;
        }
        @media (max-width: 991px) {
            .ppto-admin-filter-grid {
                grid-template-columns: 88px minmax(0, 1fr) minmax(0, 1fr);
            }
            .ppto-admin-filter-field.field-actions {
                grid-column: 1 / -1;
                justify-self: end;
                height: auto;
            }
        }
        @media (max-width: 575px) {
            .ppto-admin-filter-grid { grid-template-columns: 1fr 1fr; }
            .ppto-admin-filter-field.field-ani { max-width: none; }
        }

        .ppto-admin-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: stretch;
            margin-bottom: 4px;
        }
        .ppto-admin-kpi-row.cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        @media (max-width: 991px) {
            .ppto-admin-kpi-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575px) {
            .ppto-admin-kpi-row { grid-template-columns: 1fr; }
        }
        .ppto-admin-kpi-card {
            background: var(--v2-bg-panel, #ffffff);
            border: var(--v2-elev-border, 1px solid #e2e8f0);
            border-radius: var(--v2-radius, 8px);
            padding: 14px 16px 12px;
            box-shadow: var(--v2-elev-shadow, 0 4px 6px rgba(0,0,0,0.05));
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
            min-height: 96px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .ppto-admin-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        }
        .ppto-admin-kpi-card .kpi-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
            color: #718096;
            margin-bottom: 4px;
            padding-right: 30px;
            line-height: 1.35;
            min-height: 2.7em;
        }
        .ppto-admin-kpi-card .kpi-value {
            font-size: clamp(16px, 1.6vw, 20px);
            font-weight: 700;
            color: #2d3748;
            line-height: 1.25;
            word-break: break-word;
        }
        .ppto-admin-kpi-card .kpi-indicator {
            position: absolute;
            top: 12px;
            right: 14px;
            font-size: 18px;
            color: #cbd5e0;
            line-height: 1;
        }
        .ppto-admin-kpi-sub {
            display: block;
            font-size: 10px;
            font-weight: 500;
            color: #718096;
            margin-top: 4px;
            line-height: 1.3;
        }

        .ppto-admin-bloque {
            margin-bottom: 20px;
            border-color: #cbd5e0 !important;
        }
        .ppto-admin-bloque > .panel-heading {
            background-color: #ebf8ff;
            border-bottom: 1px solid #cbd5e0;
            padding: 10px 15px;
        }
        .ppto-admin-bloque > .panel-heading h5 {
            margin: 0;
            font-weight: 700;
            color: #2c5282;
            font-size: 13px;
        }
        .ppto-admin-bloque > .panel-body {
            padding: 14px 15px;
        }
        .ppto-admin-bloque-desc {
            margin: 0 0 12px;
            font-size: 11px;
            color: #718096;
            line-height: 1.45;
        }

        .ppto-admin-front input[type="number"]::-webkit-outer-spin-button,
        .ppto-admin-front input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .ppto-admin-front input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body class="exa-ui-fill-root ppto-admin-front">

<div class="panel panel-main exa-ui-panel exa-ui-fill-page">
    <div class="panel-heading exa-header exa-header-flex">
        <h3 class="panel-title"><i class="bi bi-pie-chart-fill"></i> Administraci&oacute;n de Presupuestos &amp; Reglas</h3>
        <div class="exa-header-actions">
            <span class="text-muted" style="font-size:12px;">Empresa: <?php echo htmlspecialchars($emp_nom_display); ?></span>
        </div>
    </div>
    <div class="panel-body exa-body ppto-admin-body">
        <div class="exa-ui-page-view">

    <div class="ppto-admin-filter-section">
    <form method="GET" action="" class="ppto-admin-filter-form" id="main_filters">
        <input type="hidden" name="tab" value="<?php echo $active_tab; ?>" />
        <input type="hidden" name="emp_cod" value="<?php echo (int)$emp_filtro; ?>" />

        <div class="ppto-admin-filter-grid">
        <div class="ppto-admin-filter-field field-ani">
            <label for="ani">A&ntilde;o fiscal</label>
            <select name="ani" id="ani" class="form-control input-sm">
                <?php foreach ($anios_filtro as $a): ?>
                    <option value="<?php echo (int)$a; ?>" <?php echo (int)$a === (int)$ani_filtro ? 'selected' : ''; ?>>
                        <?php echo (int)$a; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ppto-admin-filter-field">
            <label for="ver">Versi&oacute;n</label>
            <select name="ver" id="ver" class="form-control input-sm">
                <?php foreach ($versiones as $v): ?>
                    <option value="<?php echo $v['ppe_version']; ?>" <?php echo $v['ppe_version'] == $ver_filtro ? 'selected' : ''; ?>>
                        V<?php echo $v['ppe_version']; ?> &middot; <?php echo htmlspecialchars($v['ppe_descripcion']); ?><?php echo $v['ppe_estado'] === 'A' ? ' [Activa]' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ppto-admin-filter-field">
            <label for="mes">Periodo acumulado</label>
            <select name="mes" id="mes" class="form-control input-sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $mes_acumulado ? 'selected' : ''; ?>>
                        Hasta <?php echo ppto_nombre_mes($m); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="ppto-admin-filter-field field-actions">
            <label>&nbsp;</label>
            <div class="ppto-admin-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm" title="Aplicar filtros"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="ppto_admin_front.php" class="btn btn-default btn-sm">Limpiar</a>
            </div>
        </div>
        </div>
    </form>
    </div>

    <ul class="nav nav-tabs exa-ui-nav-tabs" role="tablist">
        <?php
        $tabs = array(
            1 => array('icon' => 'bi-bar-chart-line', 'label' => 'M&eacute;tricas'),
            2 => array('icon' => 'bi-list-ul', 'label' => 'Todas las partidas'),
            3 => array('icon' => 'bi-calendar3', 'label' => 'Mensual'),
            4 => array('icon' => 'bi-bell', 'label' => 'Alertas'),
            5 => array('icon' => 'bi-diagram-3', 'label' => 'Reglas de asignaci&oacute;n'),
            6 => array('icon' => 'bi-upload', 'label' => 'Cargar Presupuesto'),
            7 => array('icon' => 'bi-share', 'label' => 'Parametrizaci&oacute;n Contable'),
        );
        foreach ($tabs as $num => $tab):
        ?>
        <li role="presentation" class="<?php echo $active_tab === $num ? 'active' : ''; ?>">
            <a class="exa-pre-tab-link" href="?tab=<?php echo $num; ?>&amp;<?php echo $tab_qs; ?>">
                <i class="bi <?php echo $tab['icon']; ?>"></i> <?php echo $tab['label']; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="exa-ui-tab-content panels-area">
        <?php if ($active_tab === 1): ?>
            <div class="ppto-metricas-callout">
                <strong><i class="bi bi-info-circle"></i> Dos vistas complementarias</strong><br/>
                <strong>Plan est&aacute;ndar</strong> = montos de la pesta&ntilde;a <em>Mensual / Cargar presupuesto</em> (versi&oacute;n V<?php echo (int)$ver_filtro; ?><?php echo $ver_descripcion !== '' ? ' &mdash; ' . htmlspecialchars($ver_descripcion) : ''; ?>, acumulado a <?php echo htmlspecialchars(ppto_nombre_mes($mes_acumulado)); ?>).<br/>
                <strong>Consolidado</strong> = misma base que el <em>Dashboard de control</em> (incluye rubros por proyecto y reajustes). La <strong>proyecci&oacute;n anual por toneladas</strong> solo se calcula en el Dashboard.
            </div>

            <div class="panel panel-default exa-ui-panel ppto-admin-bloque">
                <div class="panel-heading">
                    <h5><i class="bi bi-table text-primary"></i> Plan est&aacute;ndar (carga mensual)</h5>
                </div>
                <div class="panel-body">
                    <p class="ppto-admin-bloque-desc">Montos ingresados manualmente por partida y mes. Ed&iacute;telos en la pesta&ntilde;a <a href="?tab=3&amp;<?php echo $tab_qs; ?>">Mensual</a>.</p>
                    <div class="ppto-admin-kpi-row">
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-cash-stack kpi-indicator"></i>
                            <div class="kpi-title">Plan acumulado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($tot_presupuesto); ?></div>
                            <span class="ppto-admin-kpi-sub">V<?php echo (int)$ver_filtro; ?> &middot; hasta <?php echo htmlspecialchars(ppto_nombre_mes($mes_acumulado)); ?></span>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-cart-check kpi-indicator"></i>
                            <div class="kpi-title">Ejecutado acumulado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($tot_ejecutado); ?></div>
                            <span class="ppto-admin-kpi-sub">Misma versi&oacute;n</span>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-piggy-bank kpi-indicator"></i>
                            <div class="kpi-title">Disponible plan</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($tot_disponible); ?></div>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-bell kpi-indicator"></i>
                            <div class="kpi-title">Alertas activas</div>
                            <div class="kpi-value"><?php echo $cant_alertas; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default exa-ui-panel ppto-admin-bloque">
                <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <h5 style="margin:0;"><i class="bi bi-speedometer2 text-primary"></i> Control consolidado (como Dashboard)</h5>
                    <a href="<?php echo htmlspecialchars($dash_url_base); ?>" class="btn btn-primary btn-sm" title="Abrir Dashboard con los mismos filtros">
                        <i class="bi bi-box-arrow-up-right"></i> Ir al Dashboard
                    </a>
                </div>
                <div class="panel-body">
                    <p class="ppto-admin-bloque-desc">Incluye presupuesto por proyecto, reajustes y ejecuci&oacute;n real. Para ver <strong>proyecci&oacute;n / forecast</strong> por producci&oacute;n, use el Dashboard.</p>
                    <div class="ppto-admin-kpi-row cols-3">
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-wallet2 kpi-indicator"></i>
                            <div class="kpi-title">Ppto. vigente consolidado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($metricas_consolidado['total_vigente']); ?></div>
                            <span class="ppto-admin-kpi-sub">Empresa + proyectos</span>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-cart-check kpi-indicator"></i>
                            <div class="kpi-title">Ejecutado consolidado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($metricas_consolidado['total_ejecutado']); ?></div>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-piggy-bank kpi-indicator"></i>
                            <div class="kpi-title">Disponible consolidado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($metricas_consolidado['total_disponible']); ?></div>
                        </div>
                    </div>

                <?php if ($metricas_hay_proyectos): ?>
                <div class="exa-adq-table-wrap" style="margin-top:14px;">
                    <table class="table table-bordered exa-adq-table table-condensed">
                        <thead>
                            <tr>
                                <th>Proyecto</th>
                                <th class="text-right">Ppto. vigente</th>
                                <th class="text-right">Ejecutado</th>
                                <th class="text-right">Disponible</th>
                                <th class="text-center" style="width:120px;">Dashboard</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metricas_consolidado['proyectos'] as $mp): 
                                $url_proy = $dash_url_base . '&proy_id=' . rawurlencode($mp['proy_id']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($mp['proy_id']); ?></strong><br/>
                                    <small class="text-muted"><?php echo htmlspecialchars($mp['proy_nombre']); ?></small>
                                </td>
                                <td class="text-right"><?php echo ppto_fmt_money($mp['vigente']); ?></td>
                                <td class="text-right"><?php echo ppto_fmt_money($mp['ejecutado']); ?></td>
                                <td class="text-right"><?php echo ppto_fmt_money($mp['disponible']); ?></td>
                                <td class="text-center">
                                    <a href="<?php echo htmlspecialchars($url_proy); ?>" class="btn btn-default btn-xs" title="Ver en Dashboard (incluye proyecci&oacute;n)">
                                        <i class="bi bi-graph-up"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ($metricas_gap_proyecto > 0.01): ?>
                <div class="ppto-metricas-nota-proy">
                    <i class="bi bi-exclamation-triangle"></i>
                    El presupuesto consolidado (<strong><?php echo ppto_fmt_money($metricas_consolidado['total_vigente']); ?></strong>)
                    es mayor que el plan est&aacute;ndar cargado en Mensual (<strong><?php echo ppto_fmt_money($tot_presupuesto); ?></strong>).
                    La diferencia proviene principalmente de <strong>rubros por proyecto</strong> (pantalla Proyectos / plantilla), no de la pesta&ntilde;a Mensual.
                </div>
                <?php elseif ($tot_presupuesto < 0.01 && $metricas_consolidado['total_vigente'] < 0.01): ?>
                <div class="ppto-metricas-nota-proy">
                    <i class="bi bi-lightbulb"></i>
                    No hay montos cargados para esta versi&oacute;n. Use <a href="?tab=3&amp;<?php echo $tab_qs; ?>">Mensual</a>,
                    <a href="?tab=6&amp;<?php echo $tab_qs; ?>">Cargar presupuesto</a> o configure un proyecto en <em>Proyectos presupuestarios</em>.
                </div>
                <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="exa-adq-section">
                        <h5 class="exa-adq-section-title"><i class="bi bi-pie-chart text-primary"></i> Resumen por tipo <span class="text-muted" style="font-size:11px;font-weight:normal;">(plan est&aacute;ndar)</span></h5>
                        <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Presupuestado</th>
                                    <th>Ejecutado</th>
                                    <th>% Ejecuci&oacute;n</th>
                                    <th>Progreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $resumen_tipo = array(
                                    'I' => array('Presup' => 0.00, 'Ejec' => 0.00),
                                    'G' => array('Presup' => 0.00, 'Ejec' => 0.00),
                                    'V' => array('Presup' => 0.00, 'Ejec' => 0.00)
                                );
                                $tipos_nombres = array(
                                    'I' => 'Ingresos',
                                    'G' => 'Gastos',
                                    'V' => 'Inversi&oacute;n'
                                );
                                if (!empty($reporte_plan_estandar)) {
                                    foreach ($reporte_plan_estandar as $row) {
                                        $clase_row = isset($row['ppa_clase']) ? $row['ppa_clase'] : 'D';
                                        if ($clase_row !== 'D') {
                                            continue;
                                        }
                                        $tip = $row['ppa_tipo'];
                                        if (isset($resumen_tipo[$tip])) {
                                            $resumen_tipo[$tip]['Presup'] += (float)$row['Presupuestado'];
                                            $resumen_tipo[$tip]['Ejec'] += (float)$row['Ejecutado'];
                                        }
                                    }
                                }
                                foreach ($resumen_tipo as $key => $values):
                                    $pct_tipo = $values['Presup'] > 0 ? ($values['Ejec'] / $values['Presup']) * 100 : 0.00;
                                    $color_prog = '#3182ce';
                                    if ($pct_tipo >= 80 && $pct_tipo < 100) $color_prog = '#dd6b20';
                                    elseif ($pct_tipo >= 100) $color_prog = '#e53e3e';
                                ?>
                                    <tr>
                                        <td><strong><?php echo $tipos_nombres[$key]; ?></strong></td>
                                        <td><?php echo ppto_fmt_money($values['Presup']); ?></td>
                                        <td><?php echo ppto_fmt_money($values['Ejec']); ?></td>
                                        <td><strong><?php echo ppto_fmt_pct($pct_tipo); ?></strong></td>
                                        <td>
                                            <div class="exa-pre-progress-wrap">
                                                <div class="exa-pre-progress-bar" style="background-color: <?php echo $color_prog; ?>; width: <?php echo min(100, $pct_tipo); ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="exa-adq-section">
                        <h5 class="exa-adq-section-title"><i class="bi bi-calendar-event text-primary"></i> Volumen &uacute;ltimos 6 meses <span class="text-muted" style="font-size:11px;font-weight:normal;">(plan est&aacute;ndar)</span></h5>
                        <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Presup acumulado</th>
                                    <th>Ejecutado</th>
                                    <th>Disponible</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ultimos_meses = array();
                                for ($m = max(1, $mes_acumulado - 5); $m <= $mes_acumulado; $m++) {
                                    $res_m = ppto_persistencia_consultar($mysqli_conn, 8, array(
                                        'Emp_Cod' => $emp_filtro,
                                        'ppe_anio' => $ani_filtro,
                                        'ppe_id' => $ppe_cod_filtro,
                                        'pej_mes' => $m
                                    ));
                                    $res_m = ppto_consulta_filtrar_plan_estandar($res_m, $ppa_proyecto_map);
                                    $sum_m = ppto_consulta_sumar_hojas($res_m);
                                    $ultimos_meses[] = array(
                                        'mes' => $m,
                                        'presupuestado' => $sum_m['presupuestado'],
                                        'ejecutado' => $sum_m['ejecutado'],
                                        'disponible' => $sum_m['disponible']
                                    );
                                }
                                foreach ($ultimos_meses as $um):
                                ?>
                                    <tr>
                                        <td><strong><?php echo ppto_nombre_mes($um['mes']); ?></strong></td>
                                        <td><?php echo ppto_fmt_money($um['presupuestado']); ?></td>
                                        <td><?php echo ppto_fmt_money($um['ejecutado']); ?></td>
                                        <td><?php echo ppto_fmt_money($um['disponible']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab === 2): ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'partida_guardada'): ?>
            <div class="alert alert-success" style="margin-bottom:12px;font-size:12px;">
                Partida guardada correctamente.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'partida_guardar'): ?>
            <div class="alert alert-danger" style="margin-bottom:12px;font-size:12px;">
                No se pudo guardar la partida. Verifique que la base de datos tenga la columna <code>ppa_clase</code> (recargue la pagina una vez).
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'partida_empresa'): ?>
            <div class="alert alert-danger" style="margin-bottom:12px;font-size:12px;">
                La partida no pertenece a la empresa seleccionada. Use el filtro de empresa correcto.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'partida_grupo_reglas'): ?>
            <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;">
                No puede cambiar a <strong>Grupo</strong> una partida con reglas de asignaci&oacute;n activas. Inactive las reglas o elija otra partida destino.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'partida_sin_padre'): ?>
            <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;">
                Las subpartidas (c&oacute;digo con punto, ej. <code>03.01</code>) requieren seleccionar el contenedor padre en el formulario.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'partida_padre_no_grupo'): ?>
            <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;">
                El contenedor padre debe ser una partida con clase <strong>Grupo</strong>. Marque el capitulo como Grupo o elija otro contenedor.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'partida_reglas_inactivadas'): ?>
            <div class="alert alert-info" style="margin-bottom:12px;font-size:12px;">
                Partida anulada. Se inactivaron <?php echo (int)$_GET['cnt']; ?> regla(s) de asignaci&oacute;n vinculada(s).
            </div>
            <?php endif; ?>
            <div class="exa-pre-section-head">
                <h5 class="exa-adq-section-title" style="margin:0;border:0;padding:0;"><i class="bi bi-list-ul text-primary"></i> Cat&aacute;logo de Partidas Presupuestarias</h5>
                <div class="exa-pre-section-toolbar">
                    <label class="exa-pre-toggle-inactivas">
                        <input type="checkbox" id="chk_ver_inactivos" value="1" <?php echo $ver_inactivos ? 'checked="checked"' : ''; ?> onchange="pptoToggleVerInactivos(this)" />
                        Mostrar inactivas
                    </label>
                    <button type="button" class="btn btn-success btn-sm" onclick="nuevaPartida()"><i class="bi bi-plus-lg"></i> Nueva Partida</button>
                </div>
            </div>
            <div class="exa-adq-table-wrap">
                <table class="table table-bordered exa-adq-table" id="tabla_partidas_catalogo">
                    <thead>
                        <tr>
                            <th>C&oacute;digo</th>
                            <th>Descripci&oacute;n</th>
                            <th>Tipo</th>
                            <th>Naturaleza</th>
                            <th>Clase</th>
                            <th class="text-right">% tope</th>
                            <th>Nivel</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($partidas_todas)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding:24px;">No hay partidas para mostrar.</td>
                            </tr>
                        <?php else:
                            $primera_inactiva = true;
                        ?>
                        <?php foreach ($partidas_todas as $part):
                            $part_inactiva = ($part['ppa_estado'] !== 'A');
                            $part_cod_js = htmlspecialchars($part['ppa_codigo_clasificacion'], ENT_QUOTES, 'UTF-8');
                            $row_cls = $part_inactiva ? 'exa-pre-row-inactiva' : '';
                            if ($part_inactiva && $primera_inactiva) {
                                $row_cls .= ' exa-pre-row-inactiva-first';
                                $primera_inactiva = false;
                            }
                            $part_nivel = ppto_partida_nivel_visual($part);
                            $part_indent = ppto_partida_indent_px($part_nivel);
                            $part_es_grupo = (isset($part['ppa_clase']) && $part['ppa_clase'] === 'G');
                            $row_tree_cls = 'ppto-tree-depth-' . $part_nivel . ($part_es_grupo ? ' ppto-tree-grupo' : '');
                            $part_padre_js = array(
                                'ppa_id' => (int)$part['ppa_id'],
                                'ppa_codigo_clasificacion' => $part['ppa_codigo_clasificacion'],
                                'ppa_descripcion' => $part['ppa_descripcion'],
                                'ppa_nivel' => $part_nivel,
                                'ppa_tipo' => $part['ppa_tipo'],
                                'ppa_naturaleza' => $part['ppa_naturaleza'],
                                'ppa_clase' => isset($part['ppa_clase']) ? $part['ppa_clase'] : 'G',
                                'ppa_porcentaje_tope' => isset($part['ppa_porcentaje_tope']) ? $part['ppa_porcentaje_tope'] : '',
                            );
                        ?>
                            <tr class="<?php echo trim($row_cls . ' ' . $row_tree_cls); ?>" data-ppa-estado="<?php echo htmlspecialchars($part['ppa_estado']); ?>" data-ppa-nivel="<?php echo $part_nivel; ?>">
                                <td class="ppto-tree-cell-codigo" style="padding-left:<?php echo $part_indent; ?>px;">
                                    <?php echo ppto_partida_tree_prefix_html($part_nivel); ?>
                                    <strong class="ppto-partida-tree-codigo"><?php echo htmlspecialchars($part['ppa_codigo_clasificacion']); ?></strong>
                                </td>
                                <td class="ppto-tree-cell-desc" style="padding-left:<?php echo $part_indent; ?>px;"><?php echo htmlspecialchars($part['ppa_descripcion']); ?></td>
                                <td><?php echo $part['ppa_tipo'] === 'I' ? 'Ingreso' : ($part['ppa_tipo'] === 'G' ? 'Gasto' : 'Inversi&oacute;n'); ?></td>
                                <td><?php echo htmlspecialchars($part['ppa_naturaleza']); ?></td>
                                <td>
                                    <?php if (isset($part['ppa_clase']) && $part['ppa_clase'] === 'G'): ?>
                                        <span class="label label-primary" style="font-size:11px;">Grupo</span>
                                    <?php else: ?>
                                        <span class="label label-default" style="font-size:11px;">Detalle</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($part_es_grupo && isset($part['ppa_porcentaje_tope']) && $part['ppa_porcentaje_tope'] !== '' && $part['ppa_porcentaje_tope'] !== null): ?>
                                        <?php echo rtrim(rtrim(number_format((float)$part['ppa_porcentaje_tope'], 4, '.', ''), '0'), '.'); ?>%
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>Nivel <?php echo $part_nivel; ?></td>
                                <td class="text-center">
                                    <?php if ($part_inactiva): ?>
                                        <span class="label label-danger exa-pre-estado-badge">Inactivo</span>
                                    <?php else: ?>
                                        <span class="label label-success exa-pre-estado-badge">Activo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center exa-pre-actions-cell">
                                    <?php if ($part_es_grupo && !$part_inactiva): ?>
                                    <button type="button" class="btn btn-xs btn-success js-ppto-nueva-subpartida" title="Agregar subpartida" data-ppa-id="<?php echo (int)$part['ppa_id']; ?>">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($part_inactiva): ?>
                                    <button type="button" class="btn btn-xs btn-default" title="No editable: partida inactiva" disabled="disabled">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-info" title="Editar" onclick='editarPartida(<?php echo json_encode($part); ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (!$part_inactiva): ?>
                                    <button type="button" class="btn btn-xs btn-danger" title="Anular" onclick="anularPartida(<?php echo (int)$part['ppa_id']; ?>, '<?php echo $part_cod_js; ?>')">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-success" title="Activar" onclick="activarPartida(<?php echo (int)$part['ppa_id']; ?>, '<?php echo $part_cod_js; ?>')">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 3): ?>
            <div class="exa-adq-section">
                <div class="exa-pre-section-head">
                    <h5 class="exa-adq-section-title" style="margin:0;border:0;padding:0;"><i class="bi bi-calendar3 text-primary"></i> Distribuci&oacute;n mensual del presupuesto (V<?php echo $ver_filtro; ?>)</h5>
                </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'mensual_guardado'): ?>
            <div class="alert alert-success" style="margin-bottom:12px;font-size:12px;">
                Distribuci&oacute;n mensual guardada correctamente.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'partida_en_proyecto'): ?>
            <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;">
                No se guardaron partidas asignadas a un proyecto. Esas l&iacute;neas se gestionan en <strong>Proyectos presupuestarios</strong>.
            </div>
            <?php endif; ?>

            <?php if ($mensual_filtra_proyecto): ?>
            <div class="ppto-mensual-callout">
                <strong><i class="bi bi-funnel"></i> Solo partidas libres de proyecto</strong><br/>
                Se ocultan <strong><?php echo (int)$mensual_partidas_ocultas; ?></strong> partida(s) con rubro en
                <?php
                $nombres_proy_mensual = array();
                foreach ($mensual_proyectos_activos as $pid => $pnom) {
                    $nombres_proy_mensual[] = htmlspecialchars($pid) . ' (' . htmlspecialchars($pnom) . ')';
                }
                echo implode(', ', $nombres_proy_mensual);
                ?>.
                Su plan (toneladas, factores, meses) est&aacute; en
                <a href="ppto_proyectos_front.php">Proyectos presupuestarios</a>
                y el control en <a href="<?php echo htmlspecialchars($dash_url_base); ?>">Dashboard</a>.
                Aqu&iacute; solo cargue gastos <em>sin proyecto</em>.
            </div>
            <?php endif; ?>

            <form method="POST" action="ppto_admin_front.php?tab=3" id="form_mensual">
                <input type="hidden" name="guardar_mensual" value="1" />
                <input type="hidden" name="ppe_cod" value="<?php echo $ppe_cod_filtro; ?>" />
                <input type="hidden" name="ver_req" value="<?php echo $ver_filtro; ?>" />
                <input type="hidden" name="emp_cod" value="<?php echo (int)$emp_filtro; ?>" />
                <input type="hidden" name="ani_filtro" value="<?php echo (int)$ani_filtro; ?>" />
                <input type="hidden" name="mes_filtro" value="<?php echo (int)$mes_acumulado; ?>" />

                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table exa-pre-table-wide">
                        <thead>
                            <tr>
                                <th style="width:150px;">Partida</th>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <th class="text-right" style="width:90px;"><?php echo ppto_nombre_mes($m); ?></th>
                                <?php endfor; ?>
                                <th class="text-right" style="width:100px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($partidas_detalle_mensual)): ?>
                            <tr>
                                <td colspan="14" class="text-center text-muted" style="padding:24px;font-size:12px;">
                                    <?php if ($mensual_filtra_proyecto): ?>
                                        Todas las partidas detalle est&aacute;n en proyectos activos.
                                        Use <a href="ppto_proyectos_front.php">Proyectos presupuestarios</a>.
                                    <?php else: ?>
                                        No hay partidas detalle activas en el cat&aacute;logo.
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($partidas_detalle_mensual as $part):
                                $meses_data = ppto_persistencia_consultar($mysqli_conn, 11, array('ppe_id' => $ppe_cod_filtro, 'ppa_id' => $part['ppa_id']));
                                $total_partida = 0.00;
                            ?>
                                <tr data-partida="<?php echo $part['ppa_id']; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($part['ppa_codigo_clasificacion']); ?></strong><br/>
                                        <small style="color:#718096;"><?php echo htmlspecialchars($part['ppa_descripcion']); ?></small>
                                    </td>
                                    <?php foreach ($meses_data as $m_row):
                                        $val = (float)$m_row['pde_monto'];
                                        $total_partida += $val;
                                    ?>
                                        <td>
                                            <div contenteditable="true"
                                                 class="exa-pre-editable-cell"
                                                 data-mes="<?php echo $m_row['pde_mes']; ?>"
                                                 onblur="calcularTotalFila(this)"
                                                 onkeypress="return soloNumeros(event)">
                                                <?php echo ppto_fmt_num($val, 2); ?>
                                            </div>
                                            <input type="hidden"
                                                   name="valores[<?php echo $part['ppa_id']; ?>][<?php echo $m_row['pde_mes']; ?>]"
                                                   value="<?php echo $val; ?>" />
                                        </td>
                                    <?php endforeach; ?>
                                    <td style="text-align:right; font-weight:700; color:#1a2e4a;" class="total-fila">
                                        <?php echo ppto_fmt_money($total_partida); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="exa-pre-form-actions">
                    <button type="submit" class="btn btn-success btn-sm"<?php echo empty($partidas_detalle_mensual) ? ' disabled="disabled"' : ''; ?>><i class="bi bi-save"></i> Guardar cambios mensuales</button>
                </div>
            </form>
            </div>
        <?php elseif ($active_tab === 4): ?>
            <div class="exa-adq-section">
                <div class="exa-pre-section-head">
                    <h5 class="exa-adq-section-title" style="margin:0;border:0;padding:0;"><i class="bi bi-bell text-primary"></i> Alertas del Sistema &amp; Umbrales</h5>
                    <button type="button" class="btn btn-primary btn-sm" onclick="alert('Configuraci&oacute;n de umbrales guardada correctamente')"><i class="bi bi-sliders"></i> Guardar Umbrales</button>
                </div>
            <div class="exa-pre-umbrales-bar">
                <span style="font-weight:600; font-size:13px; color:#4a5568;">Umbrales Activos:</span>
                <label style="font-size:13px;"><input type="checkbox" checked disabled /> Preventiva (80%)</label>
                <label style="font-size:13px;"><input type="checkbox" checked disabled /> Cr&iacute;tica (90%)</label>
                <label style="font-size:13px;"><input type="checkbox" checked disabled /> L&iacute;mite (100%)</label>
            </div>

            <?php if (!empty($alertas_activas)): ?>
                <?php foreach ($alertas_activas as $al): 
                    $color_card = '#38a169'; $color_bg = '#f0fff4';
                    if ((int)$al['pal_umbral'] === 90) {
                        $color_card = '#dd6b20'; $color_bg = '#fffaf0';
                    } elseif ((int)$al['pal_umbral'] === 100) {
                        $color_card = '#e53e3e'; $color_bg = '#fff5f5';
                    }
                ?>
                    <div class="exa-pre-alert-card" style="border-left: 4px solid <?php echo $color_card; ?>; background-color: <?php echo $color_bg; ?>;">
                        <div>
                            <div style="font-size:14px; font-weight:700; color:#2d3748; margin-bottom:4px;">
                                Partida: <?php echo htmlspecialchars($al['ppa_codigo_clasificacion']); ?> - <?php echo htmlspecialchars($al['ppa_descripcion']); ?>
                            </div>
                            <div style="font-size:12px; color:#4a5568;">
                                Umbral de alerta: <strong style="color: <?php echo $color_card; ?>;"><?php echo $al['pal_umbral']; ?>%</strong> 
                                | Ejecuci&oacute;n actual: <strong><?php echo ppto_fmt_pct($al['pal_porcentaje_actual']); ?></strong> 
                                | Fecha de alerta: <strong><?php echo $al['pal_fecha_registro']; ?></strong>
                            </div>
                        </div>
                        <a href="ppto_admin_front.php?marcar_leida=<?php echo $al['pal_id']; ?>&amp;emp_cod=<?php echo $emp_filtro; ?>&amp;ani=<?php echo $ani_filtro; ?>&amp;ver=<?php echo $ver_filtro; ?>&amp;mes=<?php echo $mes_acumulado; ?>" class="btn btn-default btn-xs">
                            Marcar como le&iacute;da
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="exa-pre-empty-state">
                    <h4>&iexcl;Todo al d&iacute;a!</h4>
                    <p>No existen alertas sin leer para la versi&oacute;n de presupuesto seleccionada.</p>
                </div>
            <?php endif; ?>
            </div>
        <?php elseif ($active_tab === 5): ?>
            <div class="exa-adq-section">
                <?php if (isset($_GET['err']) && $_GET['err'] === 'regla_partida_invalida'): ?>
                <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;">
                    La partida destino debe estar <strong>activa</strong> y ser de tipo <strong>Detalle</strong>.
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['err']) && $_GET['err'] === 'regla_no_admin'): ?>
                <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;">
                    Solo un perfil <strong>Administrador</strong> puede guardar reglas con campos personalizados.
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'regla_inactivada'): ?>
                <div class="alert alert-success" style="margin-bottom:12px;font-size:12px;">
                    Regla anulada correctamente. Ya no se aplicar&aacute; en la asignaci&oacute;n autom&aacute;tica.
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'regla_activada'): ?>
                <div class="alert alert-success" style="margin-bottom:12px;font-size:12px;">
                    Regla reactivada correctamente.
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['err']) && $_GET['err'] === 'regla_estado'): ?>
                <div class="alert alert-danger" style="margin-bottom:12px;font-size:12px;">
                    No se pudo cambiar el estado de la regla. Verifique que pertenezca a la empresa seleccionada.
                </div>
                <?php endif; ?>
                <div class="exa-pre-section-head">
                    <h5 class="exa-adq-section-title" style="margin:0;border:0;padding:0;"><i class="bi bi-diagram-3 text-primary"></i> Reglas de Asignaci&oacute;n Autom&aacute;tica de Ejecuci&oacute;n</h5>
                    <div class="exa-pre-section-toolbar">
                        <label class="exa-pre-toggle-inactivas">
                            <input type="checkbox" id="chk_ver_reglas_inactivas" value="1" <?php echo $ver_reglas_inactivas ? 'checked="checked"' : ''; ?> onchange="pptoToggleVerReglasInactivas(this)" />
                            Mostrar inactivas
                        </label>
                        <button type="button" class="btn btn-success btn-sm" onclick="nuevaRegla()"><i class="bi bi-plus-lg"></i> Nueva Regla</button>
                    </div>
                </div>

            <div class="exa-adq-table-wrap">
                <table class="table table-bordered exa-adq-table">
                    <thead>
                        <tr>
                            <th>Doc Origen</th>
                            <th>Prioridad</th>
                            <th>Descripci&oacute;n Regla</th>
                            <th>Condici&oacute;n Especial</th>
                            <th>Partida Destino</th>
                            <th>Signo</th>
                            <th>Campo Monto</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reglas_todas)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding:24px;">
                                    <?php echo $ver_reglas_inactivas ? 'No hay reglas registradas.' : 'No hay reglas activas. Marque &laquo;Mostrar inactivas&raquo; para ver las anuladas.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php
                        $primera_regla_inactiva = true;
                        foreach ($reglas_todas as $reg):
                            $reg_inactiva = ($reg['prg_estado'] !== 'A');
                            $row_cls = $reg_inactiva ? 'exa-pre-row-inactiva' : '';
                            if ($reg_inactiva && $primera_regla_inactiva) {
                                $row_cls .= ' exa-pre-row-inactiva-first';
                                $primera_regla_inactiva = false;
                            }
                        ?>
                            <tr class="<?php echo trim($row_cls); ?>">
                                <td style="text-transform:uppercase;"><strong><?php echo htmlspecialchars($reg['prg_tipo_documento']); ?></strong></td>
                                <td>
                                    <span style="font-weight:700; margin-right:10px;">#<?php echo $reg['prg_prioridad']; ?></span>
                                    <a href="ppto_admin_front.php?prioridad_regla=<?php echo $reg['prg_id']; ?>&amp;dir=up" class="exa-pre-priority-link" title="Subir"><i class="bi bi-chevron-up"></i></a>
                                    <a href="ppto_admin_front.php?prioridad_regla=<?php echo $reg['prg_id']; ?>&amp;dir=down" class="exa-pre-priority-link" title="Bajar"><i class="bi bi-chevron-down"></i></a>
                                </td>
                                <td><?php echo htmlspecialchars($reg['prg_descripcion']); ?></td>
                                <td>
                                    <?php if ($reg['prg_campo_evaluacion']): ?>
                                        <code><?php echo htmlspecialchars($reg['prg_campo_evaluacion']); ?> = '<?php echo htmlspecialchars($reg['prg_valor_esperado']); ?>'</code>
                                    <?php else: ?>
                                        <span style="color:#a0aec0;">Ninguna (Aplica directo)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reg['ppa_codigo_clasificacion']); ?></strong> - <?php echo htmlspecialchars($reg['ppa_descripcion']); ?>
                                    <?php if ($reg['ppa_estado'] !== 'A'): ?>
                                        <span class="label label-danger" style="font-size:10px;margin-left:4px;" title="Partida destino inactiva">Partida inactiva</span>
                                    <?php endif; ?>
                                    <?php if (isset($reg['ppa_clase']) && $reg['ppa_clase'] === 'G'): ?>
                                        <span class="label label-warning" style="font-size:10px;margin-left:4px;" title="Las reglas deben apuntar a partidas Detalle">Grupo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight:bold; color:<?php echo $reg['prg_signo'] === '+' ? '#38a169' : '#e53e3e'; ?>;">
                                        [ <?php echo $reg['prg_signo']; ?> ]
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($reg['prg_campo_monto']); ?></code></td>
                                <td class="text-center">
                                    <?php if ($reg_inactiva): ?>
                                        <span class="label label-danger exa-pre-estado-badge">Inactivo</span>
                                    <?php else: ?>
                                        <span class="label label-success exa-pre-estado-badge">Activo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center exa-pre-actions-cell">
                                    <?php if ($reg_inactiva): ?>
                                    <button type="button" class="btn btn-xs btn-default" title="No editable: regla inactiva" disabled="disabled">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-info" title="Editar" onclick='editarRegla(<?php echo json_encode($reg); ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($reg['prg_estado'] === 'A'): ?>
                                    <button type="button" class="btn btn-xs btn-danger btn-anular-regla" title="Anular"
                                        data-prg-id="<?php echo (int)$reg['prg_id']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($reg['prg_descripcion'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-success btn-activar-regla" title="Activar"
                                        data-prg-id="<?php echo (int)$reg['prg_id']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($reg['prg_descripcion'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>


            </div>
        <?php elseif ($active_tab === 6): ?>
            <div class="exa-adq-section">
                <h5 class="exa-adq-section-title"><i class="bi bi-upload text-primary"></i> Configuraci&oacute;n de Nueva Versi&oacute;n Presupuestaria</h5>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cargar_guardado'): ?>
            <div class="alert alert-success" style="margin-bottom:12px;font-size:12px;">
                Nueva versi&oacute;n presupuestaria guardada correctamente.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'partida_en_proyecto' && $active_tab === 6): ?>
            <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;">
                No se guardaron partidas asignadas a un proyecto. Esas l&iacute;neas se gestionan en <strong>Proyectos presupuestarios</strong>.
            </div>
            <?php endif; ?>

            <?php if ($cargar_filtra_proyecto): ?>
            <div class="ppto-mensual-callout">
                <strong><i class="bi bi-funnel"></i> Solo partidas libres de proyecto</strong><br/>
                Se ocultan <strong><?php echo (int)$cargar_partidas_ocultas; ?></strong> partida(s) con rubro en
                <?php
                $nombres_proy_cargar = array();
                foreach ($cargar_proyectos_activos as $pid => $pnom) {
                    $nombres_proy_cargar[] = htmlspecialchars($pid) . ' (' . htmlspecialchars($pnom) . ')';
                }
                echo implode(', ', $nombres_proy_cargar);
                ?>.
                Al crear una versi&oacute;n nueva, los rubros de proyecto no se cargan aqu&iacute;;
                use <a href="ppto_proyectos_front.php">Proyectos presupuestarios</a>.
            </div>
            <?php endif; ?>
            <div class="exa-pre-form-panel">
                
                <form method="POST" action="ppto_admin_front.php?tab=6" id="form_cargar_completo" enctype="multipart/form-data" onsubmit="return validarCargarPresupuesto(this)">
                    <input type="hidden" name="guardar_cargar" value="1" />
                    <input type="hidden" name="emp_cod" value="<?php echo (int)$emp_filtro; ?>" />
                    <input type="hidden" name="cargar_modo" id="cargar_modo_input" value="manual" />
                    
                    <div class="exa-pre-form-grid-4">
                        <div class="filter-group">
                            <label>A&ntilde;o Presupuesto</label>
                            <select name="Ppe_Ani" class="form-control input-sm" required>
                                <?php foreach ($anios_cargar_opts as $y_cargar): ?>
                                    <option value="<?php echo (int)$y_cargar; ?>" <?php echo (int)$y_cargar === ((int)date('Y') + 1) ? 'selected' : ''; ?>><?php echo (int)$y_cargar; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Versi&oacute;n Nro.</label>
                            <input type="number" name="Ppe_Ver" value="<?php echo count($versiones) + 1; ?>" class="form-control input-sm" required />
                        </div>
                        <div class="filter-group">
                            <label>Descripci&oacute;n de Versi&oacute;n</label>
                            <input type="text" name="Ppe_Des" placeholder="Ej. Presupuesto Base 2027" class="form-control input-sm" required />
                        </div>
                        <div class="filter-group">
                            <label>Estado Inicial</label>
                            <select name="Ppe_Est" class="form-control input-sm">
                                <option value="B">Borrador</option>
                                <option value="R">En Revisi&oacute;n</option>
                                <option value="A">Aprobado y Activar</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin: 20px 0; display: flex; gap: 10px;">
                        <button type="button" class="btn btn-primary btn-sm btn-mode-cargar active" id="btn_modo_manual" onclick="setModoCargar('manual')">Mensual manual</button>
                        <button type="button" class="btn btn-default btn-sm btn-mode-cargar" id="btn_modo_anual" onclick="setModoCargar('anual')">Anual &divide; 12</button>
                        <button type="button" class="btn btn-default btn-sm btn-mode-cargar" id="btn_modo_copiar" onclick="setModoCargar('copiar')">Copiar a&ntilde;o ant.</button>
                    </div>

                    <!-- MODO COPIAR -->
                    <div id="modo_copiar_panel" style="display: none; background-color: #f7fafc; border: 1px solid #cbd5e0; padding: 16px; border-radius: 6px; margin-bottom: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="filter-group">
                                <label>A&ntilde;o Origen</label>
                                <select name="copiar_anio" class="form-control input-sm">
                                    <?php foreach ($anios as $a_copiar): ?>
                                        <option value="<?php echo $a_copiar; ?>"><?php echo $a_copiar; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Porcentaje Incremento (%)</label>
                                <input type="number" step="0.01" name="copiar_incremento" value="0.00" class="form-control input-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- MODO MANUAL -->
                    <div id="modo_manual_panel">
                        <div class="exa-pre-import-box">
                            <div>
                                <strong style="font-size:14px; color:#2d3748;">Importar desde Excel (.xlsx / .csv)</strong>
                                <p style="margin:4px 0 0 0; font-size:12px; color:#718096;">Sube un archivo con columnas: C&oacute;digo Partida, Ene, Feb, Mar...</p>
                            </div>
                            <input type="file" id="excel_file" class="form-control input-sm" style="width:250px;" onchange="simularImportacionExcel()" />
                        </div>

                        <div class="exa-adq-table-wrap">
                            <table class="table table-bordered exa-adq-table exa-pre-table-wide">
                                <thead>
                                    <tr>
                                        <th style="width:150px;">Partida</th>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <th style="text-align:right; width:90px;"><?php echo ppto_nombre_mes($m); ?></th>
                                        <?php endfor; ?>
                                        <th style="text-align:right; width:100px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($partidas_detalle_cargar)): ?>
                                    <tr>
                                        <td colspan="14" class="text-center text-muted" style="padding:24px;font-size:12px;">
                                            <?php if ($cargar_filtra_proyecto): ?>
                                                Todas las partidas detalle est&aacute;n en proyectos activos.
                                                Use <a href="ppto_proyectos_front.php">Proyectos presupuestarios</a>.
                                            <?php else: ?>
                                                No hay partidas detalle activas en el cat&aacute;logo.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($partidas_detalle_cargar as $part): ?>
                                        <tr data-partida-cargar="<?php echo $part['ppa_id']; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($part['ppa_codigo_clasificacion']); ?></strong><br/>
                                                <small style="color:#718096;"><?php echo htmlspecialchars($part['ppa_descripcion']); ?></small>
                                            </td>
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <td>
                                                    <div contenteditable="true" 
                                                         class="content-editable-cell cell-cargar" 
                                                         data-mes="<?php echo $m; ?>"
                                                         onblur="calcularTotalFilaCargar(this)"
                                                         onkeypress="return soloNumeros(event)">0.00</div>
                                                    <input type="hidden" 
                                                           name="valores_cargar[<?php echo $part['ppa_id']; ?>][<?php echo $m; ?>]" 
                                                           value="0" />
                                                </td>
                                            <?php endfor; ?>
                                            <td style="text-align:right; font-weight:700; color:#1a2e4a;" class="total-fila-cargar">$0.00</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- MODO ANUAL -->
                    <div id="modo_anual_panel" style="display: none;">
                        <div class="exa-adq-table-wrap">
                            <table class="table table-bordered exa-adq-table">
                                <thead>
                                    <tr>
                                        <th>Partida</th>
                                        <th style="text-align:right; width:200px;">Monto Anual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($partidas_detalle_cargar)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted" style="padding:24px;font-size:12px;">Sin partidas libres para carga est&aacute;ndar.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($partidas_detalle_cargar as $part): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($part['ppa_codigo_clasificacion']); ?></strong> - <?php echo htmlspecialchars($part['ppa_descripcion']); ?>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="valores_anual[<?php echo $part['ppa_id']; ?>]" value="0.00" class="form-control input-sm text-right" style="font-weight: 600;" />
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="exa-pre-form-actions" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success btn-sm"<?php echo empty($partidas_detalle_cargar) ? ' disabled="disabled"' : ''; ?>>Guardar y Procesar Versi&oacute;n</button>
                    </div>
                </form>
            </div>
            </div>
        <?php elseif ($active_tab === 7): ?>
            <?php require __DIR__ . '/ppto_param_contable_tab.php'; ?>
        <?php endif; ?>
    </div><!-- exa-ui-tab-content -->
        </div><!-- exa-ui-page-view -->
    </div><!-- panel-body -->
</div><!-- panel -->

<div id="modal_partida" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box">
        <span class="exa-pre-modal-close" onclick="cerrarModalPartida()">&times;</span>
        <h3 id="modal_partida_titulo" class="exa-adq-section-title">Formulario de Partida</h3>
        
        <form method="POST" action="ppto_admin_front.php?tab=2" id="form_partida" onsubmit="return validarNuevaPartida(this)">
            <input type="hidden" name="guardar_partida" value="1" />
            <input type="hidden" name="emp_cod" id="form_ppa_emp_cod" value="<?php echo (int)$emp_filtro; ?>" />
            <input type="hidden" name="ani_filtro" value="<?php echo (int)$ani_filtro; ?>" />
            <input type="hidden" name="ver_filtro" value="<?php echo (int)$ver_filtro; ?>" />
            <input type="hidden" name="mes_filtro" value="<?php echo (int)$mes_acumulado; ?>" />
            <?php if ($ver_inactivos): ?>
            <input type="hidden" name="ver_inactivos" value="1" />
            <?php endif; ?>
            <input type="hidden" name="Ppa_Cod" id="form_ppa_cod" value="" />

            <div id="form_partida_grid">
                <div class="ppto-partida-ubicacion" id="ppto_partida_ubicacion_nueva">
                    <span class="ppto-partida-seccion-label">Ubicaci&oacute;n en el cat&aacute;logo</span>
                    <div class="ppto-partida-ubicacion-modo" id="ppto_partida_modo_ub">
                        <label class="ppto-partida-radio-opt">
                            <input type="radio" name="partida_modo_ub" value="raiz" checked="checked" onchange="pptoPartidaCambioModoUbicacion()" />
                            Cap&iacute;tulo principal (ra&iacute;z)
                        </label>
                        <label class="ppto-partida-radio-opt">
                            <input type="radio" name="partida_modo_ub" value="hijo" onchange="pptoPartidaCambioModoUbicacion()" />
                            Subpartida bajo un grupo
                        </label>
                    </div>
                    <div id="ppto_partida_padre_wrap" style="display:none;">
                        <label style="font-size:11px;font-weight:600;color:#4a5568;">Contenedor (partida padre)</label>
                        <select name="Ppa_Pad" id="form_ppa_pad" class="form-control input-sm" onchange="pptoPartidaSyncDesdePadre()">
                            <option value="">— Seleccione contenedor —</option>
                        </select>
                        <p id="form_ppa_pad_ayuda" style="font-size:11px;color:#718096;margin:6px 0 0;"></p>
                    </div>
                    <div class="ppto-partida-ub-resumen">
                        <span class="ppto-partida-ub-badge" id="ppto_partida_ub_nivel_txt">Nivel 1</span>
                        <span id="ppto_partida_ub_codigo_sug" class="text-muted"></span>
                    </div>
                </div>
                <div id="ppto_partida_edit_ub" class="ppto-partida-edit-ub" style="display:none;"></div>

                <input type="hidden" name="Ppa_Niv" id="form_ppa_niv" value="1" />

                <div class="filter-group">
                    <label>C&oacute;digo visible</label>
                    <input type="text" name="Ppa_Cla" id="form_ppa_cla" placeholder="Ej. 03 o 03.01.01" class="form-control input-sm" required onblur="pptoPartidaSyncDesdeCodigo()" onkeyup="pptoPartidaSyncDesdeCodigoDebounced()" />
                    <p style="font-size:10px;color:#718096;margin:6px 0 0;">Use puntos para subniveles. El nivel se calcula autom&aacute;ticamente.</p>
                </div>
                <div class="filter-group">
                    <label>Nombre / Descripci&oacute;n</label>
                    <input type="text" name="Ppa_Des" id="form_ppa_des" placeholder="Ej. Insumos t&eacute;cnicos ambientales" class="form-control input-sm" required />
                </div>
                <div class="filter-group">
                    <label>Tipo Partida</label>
                    <select name="Ppa_Tip" id="form_ppa_tip" class="form-control input-sm">
                        <option value="I">Ingreso</option>
                        <option value="G">Gasto</option>
                        <option value="V">Inversi&oacute;n</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Naturaleza</label>
                    <select name="Ppa_Nat" id="form_ppa_nat" class="form-control input-sm">
                        <option value="OPE">OPE (Operativo)</option>
                        <option value="ADM">ADM (Administrativo)</option>
                        <option value="COM">COM (Comercial)</option>
                        <option value="FIN">FIN (Financiero)</option>
                        <option value="RRH">RRH (Recursos Humanos)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Clase</label>
                    <select name="Ppa_Clase" id="form_ppa_clase" class="form-control input-sm" required onchange="pptoPartidaCambioClase()">
                        <option value="D">Detalle (imputable)</option>
                        <option value="G">Grupo (agrupador)</option>
                    </select>
                    <p id="form_ppa_clase_ayuda" style="font-size:10px;color:#718096;margin:6px 0 0;">Detalle = recibe gastos. Grupo = solo agrupa hijos.</p>
                </div>
                <div class="filter-group" id="form_ppa_pct_wrap" style="display:none;">
                    <label>% tope (sobre ingreso Ton)</label>
                    <input type="number" name="Ppa_Pct" id="form_ppa_pct" min="0" max="100" step="0.0001" placeholder="Ej. 38.6656" class="form-control input-sm" />
                    <p style="font-size:10px;color:#718096;margin:6px 0 0;">Tope anual = % &times; ($/Ton con IVA &divide; IVA) &times; Ton anuales del proyecto.</p>
                </div>
                <div class="filter-group">
                    <label>Estado</label>
                    <select name="Ppa_Est" id="form_ppa_est" class="form-control input-sm">
                        <option value="A">Activo</option>
                        <option value="I">Inactivo</option>
                    </select>
                </div>
            </div>

            <div style="text-align:right; margin-top:24px;">
                <button type="submit" class="btn btn-success btn-sm">Guardar Partida</button>
            </div>
        </form>
    </div>
</div>

<div id="modal_regla" class="exa-pre-modal-overlay">
    <div class="exa-pre-modal-box">
        <span class="exa-pre-modal-close" onclick="cerrarModalRegla()">&times;</span>
        <h3 id="modal_regla_titulo" class="exa-adq-section-title">Configuraci&oacute;n de Regla de Asignaci&oacute;n</h3>
        <span id="regla_admin_badge" style="display:none;font-size:11px;color:#276749;background:#c6f6d5;padding:3px 8px;border-radius:4px;margin-left:8px;">Modo administrador</span>
        <div class="exa-pre-wizard-nav">
            <span id="step_indicator_1" class="active-step">Paso 1: General</span>
            <span class="inactive-step">|</span>
            <span id="step_indicator_2" class="inactive-step">Paso 2: Condiciones</span>
            <span class="inactive-step">|</span>
            <span id="step_indicator_3" class="inactive-step">Paso 3: Destino</span>
            <span class="inactive-step">|</span>
            <span id="step_indicator_4" class="inactive-step">Paso 4: Confirmar</span>
        </div>

        <form method="POST" action="ppto_admin_front.php?tab=5" id="form_wizard_regla" onsubmit="return validarNuevaRegla(this)">
            <input type="hidden" name="guardar_regla" value="1" />
            <input type="hidden" name="emp_cod" value="<?php echo (int)$emp_filtro; ?>" />
            <input type="hidden" name="Prg_Cod" id="form_prg_cod" value="" />

            <div id="step_1" class="exa-pre-wizard-step active">
                <div style="display:grid; grid-template-columns: 1fr; gap:16px;">
                    <div class="filter-group">
                        <label>Nombre / Descripci&oacute;n de la Regla</label>
                        <input type="text" name="Prg_Des" id="form_prg_des" placeholder="Ej. Ventas Locales Guayaquil" class="form-control input-sm" required />
                    </div>
                    <div class="filter-group">
                        <label>Tipo Documento Origen</label>
                        <select name="Prg_TipDoc" id="form_prg_tipdoc" class="form-control input-sm" onchange="pptoReglaActualizarAyudas(false)">
                            <option value="liquidacion_nomina">Nomina / Rol de pagos</option>
                            <option value="orden_compra">Orden de compra</option>
                            <option value="pago_tesoreria">Pago de tesoreria</option>
                            <option value="ventas">Ventas / Factura</option>
                            <option value="egreso_inventario">Egreso de inventario</option>
                            <option value="adquisicion_activo">Adquisicion activo fijo</option>
                            <option value="compras">Compras (documento)</option>
                            <option value="comprobantes">Comprobante de egreso</option>
                            <option value="movimiento_cheques">Movimiento de cheques</option>
                            <option value="asientos">Asiento contable</option>
                            <option value="rol_pagos">Rol de pagos (legacy)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Prioridad de Evaluaci&oacute;n</label>
                        <input type="number" name="Prg_Pri" id="form_prg_pri" value="1" class="form-control input-sm" required />
                    </div>
                </div>
                <div style="text-align:right; margin-top:20px;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="goStep(2)">Siguiente &rsaquo;</button>
                </div>
            </div>

            <div id="step_2" class="exa-pre-wizard-step">
                <div id="regla_avanzada_bloqueada" style="display:none;font-size:12px;color:#744210;margin:0 0 12px;padding:10px;background:#fefcbf;border-radius:4px;border-left:3px solid #d69e2e;">
                    Esta regla tiene configuracion avanzada. Solo un <strong>administrador</strong> puede modificar la condicion y el monto.
                </div>
                <p id="regla_paso2_ayuda" style="font-size:12px;color:#4a5568;margin:0 0 12px;padding:10px;background:#ebf8ff;border-radius:4px;border-left:3px solid #3182ce;">
                    <strong>Para la mayoria de reglas deje esto vacio.</strong> Solo use una condicion si necesita separar el mismo documento en distintas partidas (ej. ventas de servicios vs productos).
                </p>
                <div style="display:grid; grid-template-columns: 1fr; gap:16px;">
                    <div class="filter-group">
                        <label>Cuando aplica esta regla</label>
                        <select id="form_prg_condicion_sel" class="form-control input-sm" onchange="pptoReglaAplicarCondicion()"></select>
                    </div>
                </div>
                <div id="regla_condicion_avanzada" style="display:none; margin-top:12px;">
                    <p id="regla_condicion_avanzada_titulo" style="font-size:11px;color:#718096;margin-bottom:8px;">Campos tecnicos (solo administrador):</p>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                        <div class="filter-group">
                            <label>Campo tecnico</label>
                            <input type="text" name="Prg_Campo" id="form_prg_campo" placeholder="Ej. Vet_Tip" class="form-control input-sm" />
                        </div>
                        <div class="filter-group">
                            <label>Valor exacto</label>
                            <input type="text" name="Prg_Valor" id="form_prg_valor" placeholder="Ej. S" class="form-control input-sm" />
                        </div>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="button" class="btn-clear" onclick="goStep(1)">&lsaquo; Anterior</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="goStep(3)">Siguiente &rsaquo;</button>
                </div>
            </div>

            <div id="step_3" class="exa-pre-wizard-step">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="filter-group">
                        <label>Partida Destino</label>
                        <select name="Ppa_Cod" id="form_prg_ppa_cod" class="form-control input-sm" required>
                            <?php foreach ($partidas_detalle as $part): ?>
                                <option value="<?php echo $part['ppa_id']; ?>"><?php echo htmlspecialchars($part['ppa_codigo_clasificacion']); ?> - <?php echo htmlspecialchars($part['ppa_descripcion']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Signo de Operaci&oacute;n</label>
                        <select name="Prg_Signo" id="form_prg_signo" class="form-control input-sm">
                            <option value="+">Suma (+)</option>
                            <option value="-">Resta (-)</option>
                        </select>
                    </div>
                    <div class="filter-group" style="grid-column: span 2;">
                        <label>Que monto se imputa al presupuesto</label>
                        <select id="form_prg_cammon_sel" class="form-control input-sm" onchange="pptoReglaAplicarMonto()"></select>
                        <input type="text" name="Prg_CamMon" id="form_prg_cammon" placeholder="Campo personalizado" class="form-control input-sm" style="margin-top:8px;display:none;" />
                        <p id="regla_monto_ayuda" style="font-size:11px;color:#718096;margin:6px 0 0;"></p>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="button" class="btn-clear" onclick="goStep(2)">&lsaquo; Anterior</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="goStep(4)">Siguiente &rsaquo;</button>
                </div>
            </div>

            <div id="step_4" class="exa-pre-wizard-step">
                <div class="exa-pre-confirm-box">
                    <strong>Confirmaci&oacute;n de Regla:</strong><br/>
                    &bull; Al procesar un documento de <strong id="confirm_tipdoc">ventas</strong>, se evaluar&aacute; la prioridad <strong id="confirm_pri">1</strong>.<br/>
                    &bull; Condici&oacute;n: <strong id="confirm_condicion">Sin condici&oacute;n especial (aplica directo)</strong>.<br/>
                    &bull; Si cumple, se enviar&aacute; el monto de <code id="confirm_monto">Vet_Sub</code> con signo <strong id="confirm_signo">+</strong> a la partida <strong id="confirm_partida">1.01</strong>.<br/>
                </div>
                <div class="filter-group" style="margin-bottom:20px;">
                    <label>Estado de Regla</label>
                    <select name="Prg_Est" id="form_prg_est" class="form-control input-sm">
                        <option value="A">Activo</option>
                        <option value="I">Inactivo</option>
                    </select>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="button" class="btn-clear" onclick="goStep(3)">&lsaquo; Anterior</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirmar y Guardar Regla</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Inicializar la pesta&ntilde;a activa de manera inmediata en la carga de la p&aacute;gina
    window.addEventListener('DOMContentLoaded', () => {
        switchTab(<?php echo $active_tab; ?>);
        if (typeof pptoPartidaInitCatalogoAcciones === 'function') {
            pptoPartidaInitCatalogoAcciones();
        }
        if (typeof pptoReglaInitAcciones === 'function') {
            pptoReglaInitAcciones();
        }
    });
</script>

</body>
</html>
<?php
$obBD_conexion->cerrar();
?>
