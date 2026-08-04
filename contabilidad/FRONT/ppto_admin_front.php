<?php
/**
 * ppto_admin_front.php
 * Interfaz de Administración de Presupuestos y Reglas de Asignación (EXA PPTO).
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

// RESOLUCIÓN DE ISS-01: Endpoint AJAX para verificar duplicación de código de partida
if (isset($_GET['ajax_check_partida'])) {
    $cla = $mysqli_conn->real_escape_string($_GET['cla']);
    $ppa_id = isset($_GET['ppa_cod']) && $_GET['ppa_cod'] !== '' ? (int)$_GET['ppa_cod'] : 0;
    $emp_chk = isset($_GET['emp_cod']) ? (int)$_GET['emp_cod'] : (int)$Ses_Emp_Cod;
    $cond_exc = $ppa_id ? " AND Ppa_Cod != $ppa_id " : "";
    
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
    $ppa_est = isset($_POST['Ppa_Est']) ? $mysqli_conn->real_escape_string($_POST['Ppa_Est']) : 'A';

    $ppa_clase = ppto_partida_clase_por_defecto($ppa_niv);
    if (isset($_POST['Ppa_Clase']) && ($_POST['Ppa_Clase'] === 'G' || $_POST['Ppa_Clase'] === 'D')) {
        $ppa_clase = $_POST['Ppa_Clase'];
    }
    $ppa_pct = isset($_POST['Ppa_Pct']) && $_POST['Ppa_Pct'] !== '' ? (float)$_POST['Ppa_Pct'] : 0.00;
    $ppa_meses = isset($_POST['Ppa_Meses']) && is_array($_POST['Ppa_Meses'])
        ? implode(',', array_map('intval', $_POST['Ppa_Meses']))
        : (isset($_POST['Ppa_Meses']) ? $mysqli_conn->real_escape_string((string)$_POST['Ppa_Meses']) : '');

    if ($ppa_cod) {
        $sql = "UPDATE pre_partidas SET 
                Ppa_Cla = '$ppa_cla', Ppa_Des = '$ppa_des', Ppa_Tip = '$ppa_tip',
                Ppa_Nat = '$ppa_nat', Ppa_Pad = $ppa_pad, Ppa_Niv = $ppa_niv, Ppa_Clase = '$ppa_clase',
                Ppa_Pct = $ppa_pct, Ppa_Meses = '$ppa_meses', Ppa_Est = '$ppa_est'
                WHERE Ppa_Cod = $ppa_cod AND Emp_Cod = $emp_cod";
        $mysqli_conn->query($sql);
    } else {
        $sql = "INSERT INTO pre_partidas (Emp_Cod, Ppa_Cla, Ppa_Des, Ppa_Tip, Ppa_Nat, Ppa_Pad, Ppa_Niv, Ppa_Clase, Ppa_Pct, Ppa_Meses, Ppa_Est, Ppa_FecReg, Usu_Cod)
                VALUES ($emp_cod, '$ppa_cla', '$ppa_des', '$ppa_tip', '$ppa_nat', $ppa_pad, $ppa_niv, '$ppa_clase', $ppa_pct, '$ppa_meses', '$ppa_est', NOW(), " . (int)$Ses_Usu_Cod . ")";
        $mysqli_conn->query($sql);
    }
    header("Location: ppto_admin_front.php?tab=1&emp_cod=$emp_cod");
    exit();
}

// Procesar: Guardar o actualizar regla de asignacion
if (isset($_POST['guardar_regla'])) {
    $prg_cod = isset($_POST['Prg_Cod']) && $_POST['Prg_Cod'] !== '' ? (int)$_POST['Prg_Cod'] : null;
    $emp_cod = isset($_POST['emp_cod']) ? (int)$_POST['emp_cod'] : ppto_resolve_emp_id();
    $ppa_cod = (int)$_POST['Ppa_Cod'];
    $prg_tip_doc = $mysqli_conn->real_escape_string($_POST['Prg_TipDoc']);
    $prg_cam_eva = $mysqli_conn->real_escape_string($_POST['Prg_CamEva']);
    $prg_val_esp = $mysqli_conn->real_escape_string($_POST['Prg_ValEsp']);
    $prg_sig = $mysqli_conn->real_escape_string($_POST['Prg_Sig']);
    $prg_cam_mon = $mysqli_conn->real_escape_string($_POST['Prg_CamMon']);
    $prg_pri = (int)$_POST['Prg_Pri'];
    $prg_est = isset($_POST['Prg_Est']) ? $mysqli_conn->real_escape_string($_POST['Prg_Est']) : 'A';
    $prg_des = $mysqli_conn->real_escape_string($_POST['Prg_Des']);

    if ($prg_cod) {
        $sql = "UPDATE pre_reglas SET 
                Ppa_Cod = $ppa_cod, Prg_TipDoc = '$prg_tip_doc', Prg_CamEva = '$prg_cam_eva',
                Prg_ValEsp = '$prg_val_esp', Prg_Sig = '$prg_sig', Prg_CamMon = '$prg_cam_mon',
                Prg_Pri = $prg_pri, Prg_Est = '$prg_est', Prg_Des = '$prg_des'
                WHERE Prg_Cod = $prg_cod AND Emp_Cod = $emp_cod";
        $mysqli_conn->query($sql);
    } else {
        $sql = "INSERT INTO pre_reglas (Emp_Cod, Ppa_Cod, Prg_TipDoc, Prg_CamEva, Prg_ValEsp, Prg_Sig, Prg_CamMon, Prg_Pri, Prg_Est, Prg_Des, Usu_Cod, Prg_FecReg)
                VALUES ($emp_cod, $ppa_cod, '$prg_tip_doc', '$prg_cam_eva', '$prg_val_esp', '$prg_sig', '$prg_cam_mon', $prg_pri, '$prg_est', '$prg_des', " . (int)$Ses_Usu_Cod . ", NOW())";
        $mysqli_conn->query($sql);
    }
    header("Location: ppto_admin_front.php?tab=2&emp_cod=$emp_cod");
    exit();
}

// Procesar: Reordenar prioridad de reglas (Mover Arriba / Abajo)
if (isset($_GET['mover_regla'])) {
    $prg_cod = (int)$_GET['mover_regla'];
    $dir = $_GET['dir']; // 'up' | 'down'
    
    $res = $mysqli_conn->query("SELECT Prg_Pri, Prg_TipDoc FROM pre_reglas WHERE Prg_Cod = $prg_cod AND Emp_Cod = $Ses_Emp_Cod LIMIT 1");
    if ($res && $r = $res->fetch_assoc()) {
        $pri_actual = (int)$r['Prg_Pri'];
        $tip_doc = $r['Prg_TipDoc'];
        
        if ($dir === 'up') {
            $res_swap = $mysqli_conn->query("SELECT Prg_Cod, Prg_Pri FROM pre_reglas WHERE Emp_Cod = $Ses_Emp_Cod AND Prg_TipDoc = '$tip_doc' AND Prg_Pri < $pri_actual ORDER BY Prg_Pri DESC LIMIT 1");
        } else {
            $res_swap = $mysqli_conn->query("SELECT Prg_Cod, Prg_Pri FROM pre_reglas WHERE Emp_Cod = $Ses_Emp_Cod AND Prg_TipDoc = '$tip_doc' AND Prg_Pri > $pri_actual ORDER BY Prg_Pri ASC LIMIT 1");
        }
        
        if ($res_swap && $swap = $res_swap->fetch_assoc()) {
            $cod_swap = (int)$swap['Prg_Cod'];
            $pri_swap = (int)$swap['Prg_Pri'];
            
            $mysqli_conn->query("UPDATE pre_reglas SET Prg_Pri = $pri_swap WHERE Prg_Cod = $prg_cod");
            $mysqli_conn->query("UPDATE pre_reglas SET Prg_Pri = $pri_actual WHERE Prg_Cod = $cod_swap");
        }
    }
    header("Location: ppto_admin_front.php?tab=2&emp_cod=$Ses_Emp_Cod");
    exit();
}

// Procesar: Marcar alerta como leida
if (isset($_GET['marcar_leida'])) {
    $pal_cod = (int)$_GET['marcar_leida'];
    $mysqli_conn->query("UPDATE pre_alertas SET Pal_Lei = 'L' WHERE Pal_Cod = $pal_cod OR Pal_Cod = $pal_cod");
    header("Location: ppto_admin_front.php?tab=5");
    exit();
}

// Procesar: Cargar distribucion mensual nominal de una partida
if (isset($_POST['guardar_distribucion_mensual'])) {
    $ppe_id = (int)$_POST['ppe_id'];
    $ppa_id = (int)$_POST['ppa_id'];
    $meses = isset($_POST['monto_mes']) && is_array($_POST['monto_mes']) ? $_POST['monto_mes'] : array();
    
    if ($ppe_id > 0 && $ppa_id > 0) {
        foreach ($meses as $mes => $monto) {
            $mes = (int)$mes;
            $monto = (float)$monto;
            if ($mes >= 1 && $mes <= 12) {
                $mysqli_conn->query("INSERT INTO pre_detalle (Ppe_Cod, Ppa_Cod, Pde_Mes, Pde_Mon) 
                                     VALUES ($ppe_id, $ppa_id, $mes, $monto)
                                     ON DUPLICATE KEY UPDATE Pde_Mon = $monto");
            }
        }
    }
    header("Location: ppto_admin_front.php?tab=3&ppe_id=$ppe_id&ppa_id=$ppa_id");
    exit();
}

// Procesar: Nueva version de presupuesto
if (isset($_POST['crear_version_presupuesto'])) {
    $emp_cod = ppto_resolve_emp_id();
    $ani = (int)$_POST['ppe_anio'];
    $ver = (int)$_POST['ppe_version'];
    $des = $mysqli_conn->real_escape_string($_POST['ppe_descripcion']);
    
    $mysqli_conn->query("INSERT INTO pre_presupuesto (Emp_Cod, Ppe_Ani, Ppe_Ver, Ppe_Des, Ppe_Est, Ppe_FecReg, Usu_Cod)
                         VALUES ($emp_cod, $ani, $ver, '$des', 'A', NOW(), " . (int)$Ses_Usu_Cod . ")
                         ON DUPLICATE KEY UPDATE Ppe_Des = '$des', Ppe_Est = 'A'");
    
    $res_id = $mysqli_conn->query("SELECT Ppe_Cod AS ppe_id FROM pre_presupuesto WHERE Emp_Cod = $emp_cod AND Ppe_Ani = $ani AND Ppe_Ver = $ver LIMIT 1");
    if ($res_id && $r = $res_id->fetch_assoc()) {
        $new_ppe_id = (int)$r['ppe_id'];
        
        // Copiar montos de distribucion de la version anterior si se solicita
        if (isset($_POST['copiar_version_anterior']) && $_POST['copiar_version_anterior'] === '1' && isset($_POST['ppe_id_origen'])) {
            $old_ppe_id = (int)$_POST['ppe_id_origen'];
            if ($old_ppe_id > 0) {
                $res_old = $mysqli_conn->query("SELECT pd.Ppa_Cod, pd.Pde_Mes, pd.Pde_Mon 
                                               FROM pre_detalle pd 
                                               INNER JOIN pre_presupuesto pp ON pd.Ppe_Cod = pp.Ppe_Cod 
                                               WHERE pp.Ppe_Cod = $old_ppe_id");
                if ($res_old) {
                    while ($ro = $res_old->fetch_assoc()) {
                        $ppa = (int)$ro['Ppa_Cod'];
                        $m = (int)$ro['Pde_Mes'];
                        $mon = (float)$ro['Pde_Mon'];
                        $mysqli_conn->query("INSERT INTO pre_detalle (Ppe_Cod, Ppa_Cod, Pde_Mes, Pde_Mon) 
                                             VALUES ($new_ppe_id, $ppa, $m, $mon)
                                             ON DUPLICATE KEY UPDATE Pde_Mon = $mon");
                    }
                }
            }
        } elseif (isset($_POST['prorrateo_masivo']) && $_POST['prorrateo_masivo'] === '1') {
            $partidas_imputables = ppto_partidas_listar($mysqli_conn, array('Emp_Cod' => $emp_cod, 'solo_activas' => true, 'clase' => 'D'));
            foreach ($partidas_imputables as $p_imp) {
                $pct_m = (float)$p_imp['Ppa_Pct'];
                $m_str = (string)$p_imp['Ppa_Meses'];
                if ($pct_m > 0 && !empty($m_str)) {
                    $m_arr = explode(',', $m_str);
                    $n_meses = count($m_arr);
                    if ($n_meses > 0) {
                        $monto_m = round($pct_m / $n_meses, 2);
                        foreach ($m_arr as $m_num) {
                            $m_num = (int)$m_num;
                            if ($m_num >= 1 && $m_num <= 12) {
                                $mysqli_conn->query("INSERT INTO pre_detalle (Ppe_Cod, Ppa_Cod, Pde_Mes, Pde_Mon) 
                                                     VALUES ($new_ppe_id, " . (int)$p_imp['ppa_id'] . ", $m_num, $monto_m)
                                                     ON DUPLICATE KEY UPDATE Pde_Mon = $monto_m");
                            }
                        }
                    }
                }
            }
        }
    }
    header("Location: ppto_admin_front.php?tab=4&emp_cod=$emp_cod");
    exit();
}

$emp_filtro = isset($_REQUEST['emp_cod']) ? (int)$_REQUEST['emp_cod'] : (int)$Ses_Emp_Cod;
$ani_filtro = isset($_REQUEST['ani']) ? (int)$_REQUEST['ani'] : (int)date('Y');
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

$res_vers = $mysqli_conn->query("SELECT Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion, Ppe_Est AS ppe_estado, Ppe_Cod AS ppe_id FROM pre_presupuesto WHERE Emp_Cod = $emp_filtro AND Ppe_Ani = $ani_filtro ORDER BY Ppe_Ver DESC");
$versiones = array();
if ($res_vers) {
    while ($row = $res_vers->fetch_assoc()) {
        $versiones[] = $row;
    }
}

$partidas = ppto_partidas_listar($mysqli_conn, array('Emp_Cod' => $emp_filtro, 'incluir_inactivas' => true));

$partidas_padre = array();
foreach ($partidas as $p_item) {
    if ($p_item['ppa_clase'] === 'G') {
        $partidas_padre[] = $p_item;
    }
}

$partidas_imputables = array();
foreach ($partidas as $p_item) {
    if ($p_item['ppa_clase'] === 'D' && $p_item['ppa_estado'] === 'A') {
        $partidas_imputables[] = $p_item;
    }
}

$ppe_cod_activa = null;
if (!empty($versiones)) {
    foreach ($versiones as $v) {
        if ($v['ppe_estado'] === 'A') {
            $ppe_cod_activa = (int)$v['ppe_id'];
            break;
        }
    }
    if (!$ppe_cod_activa) {
        $ppe_cod_activa = (int)$versiones[0]['ppe_id'];
    }
}

$ppe_id_dist = isset($_REQUEST['ppe_id']) ? (int)$_REQUEST['ppe_id'] : $ppe_cod_activa;
$ppa_id_dist = isset($_REQUEST['ppa_id']) ? (int)$_REQUEST['ppa_id'] : (count($partidas_imputables) > 0 ? (int)$partidas_imputables[0]['ppa_id'] : null);

$distribucion_actual = array();
if ($ppe_id_dist && $ppa_id_dist) {
    $distribucion_actual = ppto_persistencia_consultar($mysqli_conn, 11, array('ppe_id' => $ppe_id_dist, 'ppa_id' => $ppa_id_dist));
}

$reglas_actuales = array();
if ($mysqli_conn) {
    $sql_reglas = "SELECT r.*, p.Ppa_Cla AS ppa_codigo_clasificacion, p.Ppa_Des AS ppa_descripcion 
                   FROM pre_reglas r 
                   INNER JOIN pre_partidas p ON r.Ppa_Cod = p.Ppa_Cod 
                   WHERE r.Emp_Cod = $emp_filtro 
                   ORDER BY r.Prg_TipDoc ASC, r.Prg_Pri ASC";
    $res_reglas = $mysqli_conn->query($sql_reglas);
    if ($res_reglas) {
        while ($row = $res_reglas->fetch_assoc()) {
            $reglas_actuales[] = $row;
        }
    }
}

$alertas = array();
if ($ppe_cod_activa) {
    $alertas = ppto_persistencia_consultar($mysqli_conn, 9, array('Emp_Cod' => $emp_filtro, 'ppe_id' => $ppe_cod_activa));
}

$meses_nombres = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Administración de Presupuestos - EXA</title>
    <?php require_once(__DIR__ . '/../../contabilidad/FRONT/con_model3_assets.php'); ?>
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <script src="../VALIDACIONES/ppto_admin.js"></script>
    <style>
        .badge-partida-G { background-color: #e2e8f0; color: #2d3748; font-weight: 700; }
        .badge-partida-D { background-color: #ebf8ff; color: #2b6cb0; font-weight: 600; }
        .row-partida-G { background-color: #f7fafc; font-weight: 700; }
        .nav-tabs .nav-link.active { font-weight: 700; border-bottom: 3px solid #3182ce; }
        .form-control-xs { padding: 0.15rem 0.4rem; font-size: 0.78rem; border-radius: 0.2rem; }
    </style>
</head>
<body class="exa-ui-body bg-light">

    <div class="container-fluid p-3">
        <!-- TITULO Y CABECERA -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="m-0 text-primary font-weight-bold">
                    <i class="bi bi-gear-fill me-2"></i>
                    Administración de Presupuestos y Reglas
                </h4>
                <small class="text-muted">Configuración del catálogo de partidas, reglas de integración automática y distribuciones mensuales</small>
            </div>
            <div>
                <a href="dashboard_front.php" class="btn btn-outline-primary btn-sm me-2">
                    <i class="bi bi-speedometer2 me-1"></i> Ir al Dashboard
                </a>
                <a href="ppto_consulta_front.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search me-1"></i> Consulta General
                </a>
            </div>
        </div>

        <!-- FILTRO GLOBAL DE EMPRESA Y AÑO -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3 bg-white rounded">
                <form method="get" action="ppto_admin_front.php" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                    
                    <div class="col-md-4">
                        <label class="form-label form-label-sm fw-bold mb-1">Empresa en Gestión</label>
                        <select name="emp_cod" class="form-select form-select-sm" onchange="this.form.submit();">
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?php echo $emp['Emp_Cod']; ?>" <?php echo ($emp['Emp_Cod'] == $emp_filtro) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['Emp_Des']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-bold mb-1">Año Fiscal</label>
                        <select name="ani" class="form-select form-select-sm" onchange="this.form.submit();">
                            <?php foreach ($anios as $a): ?>
                                <option value="<?php echo $a; ?>" <?php echo ($a == $ani_filtro) ? 'selected' : ''; ?>>
                                    <?php echo $a; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                            <i class="bi bi-filter me-1"></i> Aplicar Contexto
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABS DE CONFIGURACION -->
        <ul class="nav nav-tabs mb-3 border-bottom-0" id="tabs_admin" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 1) ? 'active' : ''; ?>" href="ppto_admin_front.php?tab=1&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>">
                    <i class="bi bi-list-nested me-1"></i> Catálogo de Partidas (<?php echo count($partidas); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 2) ? 'active' : ''; ?>" href="ppto_admin_front.php?tab=2&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>">
                    <i class="bi bi-diagram-3 me-1"></i> Reglas de Imputación (<?php echo count($reglas_actuales); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 3) ? 'active' : ''; ?>" href="ppto_admin_front.php?tab=3&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>">
                    <i class="bi bi-calendar3 me-1"></i> Distribución Mensual Nominal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 4) ? 'active' : ''; ?>" href="ppto_admin_front.php?tab=4&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>">
                    <i class="bi bi-layers me-1"></i> Versiones de Presupuesto (<?php echo count($versiones); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 5) ? 'active' : ''; ?>" href="ppto_admin_front.php?tab=5&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>">
                    <i class="bi bi-bell me-1"></i> Control de Alertas (<?php echo count($alertas); ?>)
                </a>
            </li>
        </ul>

        <!-- TAB 1: CATALOGO DE PARTIDAS -->
        <?php if ($active_tab === 1): ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-secondary">
                                Catálogo de Partidas Presupuestarias
                            </h6>
                            <div>
                                <button class="btn btn-sm btn-success" onclick="ppto_admin_nueva_partida();">
                                    <i class="bi bi-plus-circle me-1"></i> Nueva Partida
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código Clasificación</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Clase</th>
                                        <th class="text-center">Tipo / Nat</th>
                                        <th class="text-center">Nivel</th>
                                        <th class="text-center">% / Prorrateo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($partidas)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                No hay partidas presupuestarias registradas para esta empresa.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($partidas as $p): 
                                            $is_padre = ($p['ppa_clase'] === 'G');
                                            $bg_class = $is_padre ? 'row-partida-G' : '';
                                        ?>
                                            <tr class="<?php echo $bg_class; ?>">
                                                <td><code><?php echo htmlspecialchars($p['ppa_codigo_clasificacion']); ?></code></td>
                                                <td>
                                                    <span style="padding-left: <?php echo ($p['ppa_nivel'] - 1) * 12; ?>px;">
                                                        <?php echo htmlspecialchars($p['ppa_descripcion']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo $is_padre ? 'badge-partida-G' : 'badge-partida-D'; ?>">
                                                        <?php echo $is_padre ? 'Grupo' : 'Detalle'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><small><?php echo htmlspecialchars($p['ppa_tipo'] . ' / ' . $p['ppa_naturaleza']); ?></small></td>
                                                <td class="text-center"><span class="badge bg-secondary"><?php echo $p['ppa_nivel']; ?></span></td>
                                                <td class="text-center">
                                                    <?php if (!$is_padre && (float)$p['ppa_porcentaje'] > 0): ?>
                                                        <small class="text-primary fw-bold"><?php echo ppto_fmt_num($p['ppa_porcentaje'], 1); ?>%</small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo ($p['ppa_estado'] === 'A') ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ($p['ppa_estado'] === 'A') ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-xs btn-outline-primary p-1 py-0" onclick="ppto_admin_editar_partida(<?php echo htmlspecialchars(ppto_json_encode_safe($p)); ?>);">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- FORMULARIO GUARDAR/EDITAR PARTIDA -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-primary" id="title_form_partida">
                                <i class="bi bi-plus-circle me-1"></i> Gestión de Partida
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <form method="post" action="ppto_admin_front.php" id="form_partida">
                                <input type="hidden" name="emp_cod" value="<?php echo $emp_filtro; ?>">
                                <input type="hidden" name="Ppa_Cod" id="form_Ppa_Cod" value="">

                                <div class="mb-2">
                                    <label class="form-label form-label-sm fw-bold">Código Clasificación *</label>
                                    <input type="text" name="Ppa_Cla" id="form_Ppa_Cla" class="form-control form-control-sm" required placeholder="Ej: 1.1.01" onblur="ppto_admin_validar_codigo_partida();">
                                    <div class="invalid-feedback small" id="err_Ppa_Cla">Código duplicado o inválido.</div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label form-label-sm fw-bold">Descripción de Partida *</label>
                                    <input type="text" name="Ppa_Des" id="form_Ppa_Des" class="form-control form-control-sm" required placeholder="Ej: Sueldos y Salarios">
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Tipo *</label>
                                        <select name="Ppa_Tip" id="form_Ppa_Tip" class="form-select form-select-sm">
                                            <option value="egreso">Egreso / Gasto</option>
                                            <option value="ingreso">Ingreso</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Naturaleza *</label>
                                        <select name="Ppa_Nat" id="form_Ppa_Nat" class="form-select form-select-sm">
                                            <option value="debito">Débito</option>
                                            <option value="credito">Crédito</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Clase *</label>
                                        <select name="Ppa_Clase" id="form_Ppa_Clase" class="form-select form-select-sm" onchange="ppto_admin_toggle_clase_partida();">
                                            <option value="D">Detalle (Imputable)</option>
                                            <option value="G">Grupo (Capítulo/Padre)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Nivel *</label>
                                        <input type="number" name="Ppa_Niv" id="form_Ppa_Niv" class="form-control form-control-sm" min="1" max="5" value="1" required>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label form-label-sm fw-bold">Partida Padre (Grupo)</label>
                                    <select name="Ppa_Pad" id="form_Ppa_Pad" class="form-select form-select-sm">
                                        <option value="">-- Sin Padre (Raíz) --</option>
                                        <?php foreach ($partidas_padre as $pad): ?>
                                            <option value="<?php echo $pad['ppa_id']; ?>">
                                                <?php echo htmlspecialchars($pad['ppa_codigo_clasificacion'] . ' - ' . $pad['ppa_descripcion']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- SECCION PRORRATEO / PORCENTAJE (ISS-02) -->
                                <div id="wrap_prorrateo_partida" class="p-2 bg-light rounded border mb-2">
                                    <div class="mb-2">
                                        <label class="form-label form-label-sm fw-bold text-primary mb-1">% Asignación Nominal (ISS-02)</label>
                                        <input type="number" step="0.01" min="0" max="100" name="Ppa_Pct" id="form_Ppa_Pct" class="form-control form-control-sm" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="form-label form-label-sm fw-bold text-primary mb-1">Meses de Prorrateo</label>
                                        <div class="row g-1">
                                            <?php foreach ($meses_nombres as $m_n => $m_nom): ?>
                                                <div class="col-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input chk-mes-prorrateo" type="checkbox" name="Ppa_Meses[]" value="<?php echo $m_n; ?>" id="chk_mes_<?php echo $m_n; ?>">
                                                        <label class="form-check-label small" for="chk_mes_<?php echo $m_n; ?>"><?php echo substr($m_nom, 0, 3); ?></label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label form-label-sm fw-bold">Estado</label>
                                    <select name="Ppa_Est" id="form_Ppa_Est" class="form-select form-select-sm">
                                        <option value="A">Activo</option>
                                        <option value="I">Inactivo</option>
                                    </select>
                                </div>

                                <button type="submit" name="guardar_partida" id="btn_guardar_partida" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="bi bi-save me-1"></i> Guardar Partida
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 2: REGLAS DE IMPUTACION Y ASIGNACION AUTOMATICA -->
        <?php if ($active_tab === 2): ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-secondary">
                                Reglas de Asignación e Imputación Automática
                            </h6>
                            <button class="btn btn-sm btn-success" onclick="ppto_admin_nueva_regla();">
                                <i class="bi bi-plus-circle me-1"></i> Nueva Regla
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Pri</th>
                                        <th>Doc / Origen</th>
                                        <th>Partida Destino</th>
                                        <th>Evaluación Especial</th>
                                        <th class="text-center">Signo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reglas_actuales)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                No hay reglas de asignación configuradas para esta empresa.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reglas_actuales as $rg): ?>
                                            <tr>
                                                <td class="text-center fw-bold">
                                                    <span class="badge bg-secondary"><?php echo $rg['Prg_Pri']; ?></span>
                                                    <div class="btn-group-vertical btn-group-xs ms-1">
                                                        <a href="ppto_admin_front.php?tab=2&mover_regla=<?php echo $rg['Prg_Cod']; ?>&dir=up" class="btn btn-xs btn-light p-0 py-0" title="Subir Prioridad"><i class="bi bi-chevron-up"></i></a>
                                                        <a href="ppto_admin_front.php?tab=2&mover_regla=<?php echo $rg['Prg_Cod']; ?>&dir=down" class="btn btn-xs btn-light p-0 py-0" title="Bajar Prioridad"><i class="bi bi-chevron-down"></i></a>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-dark"><?php echo htmlspecialchars($rg['Prg_TipDoc']); ?></span></td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($rg['ppa_codigo_clasificacion']); ?></code> - 
                                                    <small><?php echo htmlspecialchars($rg['ppa_descripcion']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($rg['Prg_CamEva'])): ?>
                                                        <small><code><?php echo htmlspecialchars($rg['Prg_CamEva']); ?></code> == '<strong><?php echo htmlspecialchars($rg['Prg_ValEsp']); ?></strong>'</small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Aplica a todo documento</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center fw-bold"><?php echo $rg['Prg_Sig']; ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo ($rg['Prg_Est'] === 'A') ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ($rg['Prg_Est'] === 'A') ? 'Activa' : 'Inactiva'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-xs btn-outline-primary p-1 py-0" onclick="ppto_admin_editar_regla(<?php echo htmlspecialchars(ppto_json_encode_safe($rg)); ?>);">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- FORMULARIO GUARDAR/EDITAR REGLA -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-primary" id="title_form_regla">
                                <i class="bi bi-plus-circle me-1"></i> Configuración de Regla
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <form method="post" action="ppto_admin_front.php" id="form_regla">
                                <input type="hidden" name="emp_cod" value="<?php echo $emp_filtro; ?>">
                                <input type="hidden" name="Prg_Cod" id="form_Prg_Cod" value="">

                                <div class="mb-2">
                                    <label class="form-label form-label-sm fw-bold">Tipo / Módulo Documento *</label>
                                    <select name="Prg_TipDoc" id="form_Prg_TipDoc" class="form-select form-select-sm" required onchange="ppto_admin_on_change_tipdoc();">
                                        <option value="ventas">Ventas / Facturación</option>
                                        <option value="compras">Compras / Proveedores</option>
                                        <option value="rol_pagos">Rol de Pagos / Nómina</option>
                                        <option value="comprobantes">Comprobantes Contables</option>
                                        <option value="movimiento_cheques">Tesorería / Cheques</option>
                                        <option value="asientos">Asientos Diarios Directos</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label form-label-sm fw-bold">Partida Presupuestaria Destino *</label>
                                    <select name="Ppa_Cod" id="form_Prg_Ppa_Cod" class="form-select form-select-sm" required>
                                        <option value="">-- Seleccione partida imputable --</option>
                                        <?php foreach ($partidas_imputables as $p_imp): ?>
                                            <option value="<?php echo $p_imp['ppa_id']; ?>">
                                                <?php echo htmlspecialchars($p_imp['ppa_codigo_clasificacion'] . ' - ' . $p_imp['ppa_descripcion']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Campo Evaluación</label>
                                        <input type="text" name="Prg_CamEva" id="form_Prg_CamEva" class="form-control form-control-sm" placeholder="Ej: estado">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Valor Esperado</label>
                                        <input type="text" name="Prg_ValEsp" id="form_Prg_ValEsp" class="form-control form-control-sm" placeholder="Ej: Aprobado">
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Signo Transacción *</label>
                                        <select name="Prg_Sig" id="form_Prg_Sig" class="form-select form-select-sm">
                                            <option value="+">Suma (+ Positivo)</option>
                                            <option value="-">Resta (- Reverso/Reducción)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Campo Monto *</label>
                                        <input type="text" name="Prg_CamMon" id="form_Prg_CamMon" class="form-control form-control-sm" value="monto" required>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Prioridad Regla *</label>
                                        <input type="number" name="Prg_Pri" id="form_Prg_Pri" class="form-control form-control-sm" value="1" min="1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm fw-bold">Estado</label>
                                        <select name="Prg_Est" id="form_Prg_Est" class="form-select form-select-sm">
                                            <option value="A">Activa</option>
                                            <option value="I">Inactiva</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label form-label-sm fw-bold">Descripción / Notas de Regla</label>
                                    <textarea name="Prg_Des" id="form_Prg_Des" class="form-control form-control-sm" rows="2" placeholder="Motivo o uso de esta regla..."></textarea>
                                </div>

                                <button type="submit" name="guardar_regla" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="bi bi-save me-1"></i> Guardar Regla
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 3: DISTRIBUCION MENSUAL NOMINAL -->
        <?php if ($active_tab === 3): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-secondary">
                        Carga y Distribución Mensual Nominal del Presupuesto (USD)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <form method="get" action="ppto_admin_front.php" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="tab" value="3">
                        <input type="hidden" name="emp_cod" value="<?php echo $emp_filtro; ?>">
                        <input type="hidden" name="ani" value="<?php echo $ani_filtro; ?>">

                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-bold">Versión Presupuestaria</label>
                            <select name="ppe_id" class="form-select form-select-sm" onchange="this.form.submit();">
                                <?php foreach ($versiones as $v): ?>
                                    <option value="<?php echo $v['ppe_id']; ?>" <?php echo ($v['ppe_id'] == $ppe_id_dist) ? 'selected' : ''; ?>>
                                        V<?php echo $v['ppe_version']; ?> - <?php echo htmlspecialchars($v['ppe_descripcion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-bold">Partida Presupuestaria Imputable</label>
                            <select name="ppa_id" class="form-select form-select-sm" onchange="this.form.submit();">
                                <?php foreach ($partidas_imputables as $p_imp): ?>
                                    <option value="<?php echo $p_imp['ppa_id']; ?>" <?php echo ($p_imp['ppa_id'] == $ppa_id_dist) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p_imp['ppa_codigo_clasificacion'] . ' - ' . $p_imp['ppa_descripcion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                                <i class="bi bi-search me-1"></i> Cargar Meses
                            </button>
                        </div>
                    </form>

                    <?php if ($ppe_id_dist && $ppa_id_dist): ?>
                        <form method="post" action="ppto_admin_front.php?tab=3">
                            <input type="hidden" name="ppe_id" value="<?php echo $ppe_id_dist; ?>">
                            <input type="hidden" name="ppa_id" value="<?php echo $ppa_id_dist; ?>">

                            <div class="table-responsive mb-3">
                                <table class="table table-bordered table-sm align-middle text-center" style="font-size: 0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <th><?php echo substr($meses_nombres[$m], 0, 3); ?></th>
                                            <?php endfor; ?>
                                            <th class="table-primary">TOTAL ANUAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php 
                                            $total_dist = 0;
                                            $meses_dict = array();
                                            foreach ($distribucion_actual as $row_m) {
                                                $meses_dict[(int)$row_m['pde_mes']] = (float)$row_m['pde_monto'];
                                            }
                                            for ($m = 1; $m <= 12; $m++):
                                                $val = isset($meses_dict[$m]) ? $meses_dict[$m] : 0.00;
                                                $total_dist += $val;
                                            ?>
                                                <td>
                                                    <input type="number" step="0.01" min="0" name="monto_mes[<?php echo $m; ?>]" class="form-control form-control-xs text-end input-monto-mes" value="<?php echo number_format($val, 2, '.', ''); ?>" onkeyup="ppto_admin_recalcular_total_distribucion();">
                                                </td>
                                            <?php endfor; ?>
                                            <td class="fw-bold text-primary fs-6" id="total_distribucion_anual">
                                                $ <?php echo number_format($total_dist, 2); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="ppto_admin_prorratear_unif();">
                                        <i class="bi bi-arrows-expand me-1"></i> Prorratear Uniforme (1/12)
                                    </button>
                                </div>
                                <div>
                                    <button type="submit" name="guardar_distribucion_mensual" class="btn btn-sm btn-success fw-bold">
                                        <i class="bi bi-save me-1"></i> Guardar Distribución Mensual
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 4: VERSIONES DE PRESUPUESTO -->
        <?php if ($active_tab === 4): ?>
            <div class="row g-3">
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-secondary">
                                Historial de Versiones del Presupuesto (Año <?php echo $ani_filtro; ?>)
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Versión</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">ID Técnico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($versiones)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                No hay versiones registradas para este año fiscal.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($versiones as $v): ?>
                                            <tr>
                                                <td class="fw-bold text-primary">Version <?php echo $v['ppe_version']; ?></td>
                                                <td><?php echo htmlspecialchars($v['ppe_descripcion']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo ($v['ppe_estado'] === 'A') ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo ($v['ppe_estado'] === 'A') ? 'Aprobada / Activa' : 'Borrador / Histórica'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><code>#<?php echo $v['ppe_id']; ?></code></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="bi bi-plus-circle me-1"></i> Crear Nueva Versión
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <form method="post" action="ppto_admin_front.php?tab=4">
                                <input type="hidden" name="emp_cod" value="<?php echo $emp_filtro; ?>">

                                <div class="mb-2">
                                    <label class="form-label form-label-sm fw-bold">Año Fiscal *</label>
                                    <input type="number" name="ppe_anio" class="form-control form-control-sm" value="<?php echo $ani_filtro; ?>" required>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label form-label-sm fw-bold">Número de Versión *</label>
                                    <input type="number" name="ppe_version" class="form-control form-control-sm" value="<?php echo count($versiones) + 1; ?>" required min="1">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label form-label-sm fw-bold">Descripción de la Versión *</label>
                                    <input type="text" name="ppe_descripcion" class="form-control form-control-sm" required placeholder="Ej: Presupuesto Inicial Aprobado 2026">
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="copiar_version_anterior" value="1" id="chk_copiar">
                                    <label class="form-check-label small" for="chk_copiar">
                                        Copiar distribuciones de versión anterior
                                    </label>
                                </div>

                                <?php if (!empty($versiones)): ?>
                                    <div class="mb-3 ms-4" id="wrap_ver_origen" style="display:none;">
                                        <label class="form-label form-label-sm fw-bold">Versión Origen</label>
                                        <select name="ppe_id_origen" class="form-select form-select-sm">
                                            <?php foreach ($versiones as $vo): ?>
                                                <option value="<?php echo $vo['ppe_id']; ?>">V<?php echo $vo['ppe_version']; ?> - <?php echo htmlspecialchars($vo['ppe_descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="prorrateo_masivo" value="1" id="chk_prorrateo_masivo">
                                    <label class="form-check-label small text-primary fw-bold" for="chk_prorrateo_masivo">
                                        Aplicar Prorrateo Automático Inicial (ISS-02)
                                    </label>
                                </div>

                                <button type="submit" name="crear_version_presupuesto" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="bi bi-save me-1"></i> Generar Versión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 5: CONTROL DE ALERTAS Y UMBRALES -->
        <?php if ($active_tab === 5): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-secondary">
                        Panel de Control de Alertas Presupuestarias Activas
                    </h6>
                    <small class="text-muted">Desvíos detectados automáticamente por el motor</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Registro</th>
                                <th>Partida Presupuestaria</th>
                                <th class="text-center">Umbral Rebasado</th>
                                <th class="text-center">% Consumo Actual</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alertas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No hay alertas pendientes de atención para la versión activa.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($alertas as $al): 
                                    $umb = (int)$al['Pal_Umb'];
                                    $badge = ($umb >= 100) ? 'bg-danger' : 'bg-warning text-dark';
                                ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($al['Pal_FecReg'])); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($al['ppa_codigo_clasificacion']); ?></code> - 
                                            <?php echo htmlspecialchars($al['ppa_descripcion']); ?>
                                        </td>
                                        <td class="text-center"><span class="badge <?php echo $badge; ?>"><?php echo $umb; ?>% Umbral</span></td>
                                        <td class="text-center fw-bold"><?php echo ppto_fmt_num($al['Pal_PorAct'], 1); ?>%</td>
                                        <td class="text-center">
                                            <span class="badge <?php echo ($al['Pal_Lei'] === 'N') ? 'bg-primary' : 'bg-secondary'; ?>">
                                                <?php echo ($al['Pal_Lei'] === 'N') ? 'Pendiente' : 'Atendida'; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($al['Pal_Lei'] === 'N'): ?>
                                                <a href="ppto_admin_front.php?tab=5&marcar_leida=<?php echo $al['Pal_Cod']; ?>" class="btn btn-xs btn-outline-success p-1 py-0" title="Atender Alerta">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div> <!-- /container-fluid -->

    <script>
        $(document).ready(function() {
            $('#chk_copiar').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#wrap_ver_origen').show();
                } else {
                    $('#wrap_ver_origen').hide();
                }
            });
        });

        function ppto_admin_recalcular_total_distribucion() {
            var tot = 0;
            $('.input-monto-mes').each(function() {
                var v = parseFloat($(this).val() || 0);
                tot += v;
            });
            $('#total_distribucion_anual').text('$ ' + ppto_fmt_money(tot));
        }

        function ppto_admin_prorratear_unif() {
            var val = prompt("Ingrese el presupuesto anual total a prorratear uniformemente (12 meses):", "12000");
            if (val !== null && !isNaN(val)) {
                var m_tot = parseFloat(val);
                var m_mes = (m_tot / 12).toFixed(2);
                $('.input-monto-mes').val(m_mes);
                ppto_admin_recalcular_total_distribucion();
            }
        }
    </script>
</body>
</html>
