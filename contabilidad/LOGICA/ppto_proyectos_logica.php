<?php
/**
 * ppto_proyectos_logica.php
 * CRUD AJAX de proyectos presupuestarios (pre_proyectos + rubros).
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('ppto_schema_logica.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Usu_Cod) && isset($_SESSION['Ses_Usu_Cod'])) {
    $Ses_Usu_Cod = $_SESSION['Ses_Usu_Cod'];
}

$obBD = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli = $obBD->conexion;
ppto_schema_ensure($mysqli);

$Emp_Cod = ppto_resolve_emp_id();
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : 'list';

function ppto_proy_json($data) {
    echo json_encode($data);
    exit();
}

if ($action === 'list') {
    $rows = array();
    $sql = "SELECT p.Pro_Cod AS proy_id, p.Pro_Nom AS proy_nombre, p.Pro_Est AS proy_estado, p.Plt_Cod AS plt_id, p.Pro_FecReg AS proy_fecha_registro, pl.plt_nombre
            FROM pre_proyectos p
            LEFT JOIN pre_plantillas pl ON p.Plt_Cod = pl.plt_id
            WHERE p.Emp_Cod = $Emp_Cod
            ORDER BY p.Pro_Nom";
    $res = $mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    ppto_proy_json(array('status' => 'success', 'rows' => $rows));
}

if ($action === 'get') {
    $proy_id = $mysqli->real_escape_string(trim($_GET['proy_id']));
    $sql = "SELECT Pro_Cod AS proy_id, Pro_Nom AS proy_nombre, Pro_Est AS proy_estado, Plt_Cod AS plt_id FROM pre_proyectos WHERE (Pro_Cod = '$proy_id' OR Pro_Ide = '$proy_id') AND Emp_Cod = $Emp_Cod LIMIT 1";
    $res = $mysqli->query($sql);
    if (!$res || $res->num_rows === 0) {
        ppto_proy_json(array('status' => 'error', 'message' => 'Proyecto no encontrado.'));
    }
    $proy = $res->fetch_assoc();

    $rubros = array();
    $sql_r = "SELECT d.Pdp_Cod AS pdp_id, d.Ppa_Cod AS ppa_id, d.Pro_Cod AS proy_id, d.Bas_Cod AS bas_id, d.Frm_Cod AS frm_id,
                     d.Pdp_Rubro AS pdp_rubro, d.Pdp_TonBase AS pdp_toneladas_base, d.Pdp_FacAnualTon AS pdp_factor_anual_tonelada, d.Pdp_PreAnual AS pdp_presupuesto_anual,
                     p.Ppa_Cla AS ppa_codigo_clasificacion, p.Ppa_Des AS ppa_descripcion
              FROM pre_proyecto_detalles d
              INNER JOIN pre_partidas p ON d.Ppa_Cod = p.Ppa_Cod
              WHERE (d.Pro_Cod = '$proy_id' OR d.Pro_Cod = '{$proy['proy_id']}') AND d.Emp_Cod = $Emp_Cod
              ORDER BY p.Ppa_Cla, d.Pdp_Rubro";
    $res_r = $mysqli->query($sql_r);
    if ($res_r) {
        while ($r = $res_r->fetch_assoc()) {
            $rubros[] = $r;
        }
    }
    ppto_proy_json(array('status' => 'success', 'proyecto' => $proy, 'rubros' => $rubros));
}

if ($action === 'save') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $proy_nombre = $mysqli->real_escape_string(trim($_POST['proy_nombre']));
    $plt_id = isset($_POST['plt_id']) && (int)$_POST['plt_id'] > 0 ? (int)$_POST['plt_id'] : "NULL";
    $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] === '1';

    if ($proy_id === '' || $proy_nombre === '') {
        ppto_proy_json(array('status' => 'error', 'message' => 'Codigo y Nombre son obligatorios.'));
    }

    if (!$is_edit) {
        $check = $mysqli->query("SELECT Pro_Cod FROM pre_proyectos WHERE (Pro_Cod = '$proy_id' OR Pro_Ide = '$proy_id') AND Emp_Cod = $Emp_Cod LIMIT 1");
        if ($check && $check->num_rows > 0) {
            ppto_proy_json(array('status' => 'error', 'message' => 'El codigo de proyecto ya existe para esta empresa.'));
        }
        $sql = "INSERT INTO pre_proyectos (Pro_Ide, Emp_Cod, Pro_Nom, Pro_Est, Pro_FecReg, Usu_Cod, Plt_Cod)
                VALUES ('$proy_id', $Emp_Cod, '$proy_nombre', 'A', NOW(), " . (int)$Ses_Usu_Cod . ", $plt_id)";
    } else {
        $sql = "UPDATE pre_proyectos SET Pro_Nom = '$proy_nombre', Plt_Cod = $plt_id WHERE (Pro_Cod = '$proy_id' OR Pro_Ide = '$proy_id') AND Emp_Cod = $Emp_Cod";
    }

    if (!$mysqli->query($sql)) {
        ppto_proy_json(array('status' => 'error', 'message' => 'Error al guardar el proyecto: ' . $mysqli->error));
    }

    // Si se asigno plantilla en nuevo proyecto, copiar rubros iniciales
    if (!$is_edit && $plt_id !== "NULL") {
        $anio = (int)date('Y');
        $res_ppe = $mysqli->query("SELECT Ppe_Cod FROM pre_presupuesto WHERE Emp_Cod = $Emp_Cod AND Ppe_Ani = $anio AND Ppe_Est = 'A' ORDER BY Ppe_Ver DESC LIMIT 1");
        if ($res_ppe && $row_ppe = $res_ppe->fetch_assoc()) {
            $ppe_id = (int)$row_ppe['Ppe_Cod'];
            $res_p = $mysqli->query("SELECT pp.Ppa_Cod, p.Ppa_Des
                FROM pre_plantilla_partidas pp
                INNER JOIN pre_partidas p ON pp.Ppa_Cod = p.Ppa_Cod
                WHERE pp.plt_id = $plt_id");
            if ($res_p) {
                while ($rp = $res_p->fetch_assoc()) {
                    $ppa_id = (int)$rp['Ppa_Cod'];
                    $rubro = $mysqli->real_escape_string($rp['Ppa_Des']);
                    $proy_esc = $mysqli->real_escape_string($proy_id);
                    $mysqli->query("INSERT IGNORE INTO pre_proyecto_detalles
                        (Ppe_Cod, Ppa_Cod, Pro_Cod, Emp_Cod, Pdp_Rubro, Pdp_TonBase, Pdp_FacAnualTon, Pdp_PreAnual, Pdp_FecReg, Usu_Cod)
                        VALUES ($ppe_id, $ppa_id, '$proy_esc', $Emp_Cod, '$rubro', 0, 0, 0, NOW(), " . (int)$Ses_Usu_Cod . ")");

                    $r2 = $mysqli->query("SELECT Pdp_Cod FROM pre_proyecto_detalles WHERE Ppe_Cod = $ppe_id AND Ppa_Cod = $ppa_id AND Pro_Cod = '$proy_esc' AND Pdp_Rubro = '$rubro' LIMIT 1");
                    if ($r2 && $row_pdp = $r2->fetch_assoc()) {
                        $pdp_id = (int)$row_pdp['Pdp_Cod'];
                        for ($m = 1; $m <= 12; $m++) {
                            $mysqli->query("INSERT IGNORE INTO pre_proyecto_detalles_mes
                                (Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual, Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible)
                                VALUES ($pdp_id, $m, 22, 0.083333, 0, 0, 0, 0)");
                        }
                    }
                }
            }
        }
    }

    ppto_proy_json(array('status' => 'success', 'message' => 'Proyecto guardado correctamente.'));
}

if ($action === 'save_rubro') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $ppa_id = (int)$_POST['ppa_id'];
    $rubro = $mysqli->real_escape_string(trim($_POST['pdp_rubro']));
    $ton_base = (float)$_POST['pdp_toneladas_base'];
    $factor = (float)$_POST['pdp_factor_anual_tonelada'];
    $presupuesto_anual = round($ton_base * $factor, 2);

    $anio = (int)date('Y');
    $res_ppe = $mysqli->query("SELECT Ppe_Cod FROM pre_presupuesto WHERE Emp_Cod = $Emp_Cod AND Ppe_Ani = $anio AND Ppe_Est = 'A' ORDER BY Ppe_Ver DESC LIMIT 1");
    if (!$res_ppe || $res_ppe->num_rows === 0) {
        ppto_proy_json(array('status' => 'error', 'message' => 'No existe cabecera presupuestaria activa para el año ' . $anio));
    }
    $ppe_id = (int)$res_ppe->fetch_assoc()['Ppe_Cod'];

    $pdp_id = isset($_POST['pdp_id']) && (int)$_POST['pdp_id'] > 0 ? (int)$_POST['pdp_id'] : 0;

    if ($pdp_id <= 0) {
        $sql = "INSERT INTO pre_proyecto_detalles
                (Ppe_Cod, Ppa_Cod, Pro_Cod, Emp_Cod, Pdp_Rubro, Pdp_TonBase, Pdp_FacAnualTon, Pdp_PreAnual, Pdp_FecReg, Usu_Cod)
                VALUES ($ppe_id, $ppa_id, '$proy_id', $Emp_Cod, '$rubro', $ton_base, $factor, $presupuesto_anual, NOW(), " . (int)$Ses_Usu_Cod . ")";
        if ($mysqli->query($sql)) {
            $pdp_id = $mysqli->insert_id;
        } else {
            ppto_proy_json(array('status' => 'error', 'message' => 'Error al guardar rubro: ' . $mysqli->error));
        }
    } else {
        $sql = "UPDATE pre_proyecto_detalles SET
                Ppa_Cod = $ppa_id,
                Pdp_Rubro = '$rubro',
                Pdp_TonBase = $ton_base,
                Pdp_FacAnualTon = $factor,
                Pdp_PreAnual = $presupuesto_anual
                WHERE Pdp_Cod = $pdp_id AND Emp_Cod = $Emp_Cod";
        if (!$mysqli->query($sql)) {
            ppto_proy_json(array('status' => 'error', 'message' => 'Error al actualizar rubro: ' . $mysqli->error));
        }
    }

    // Prorrateo uniforme 12 meses
    $pres_mes = round($presupuesto_anual / 12, 2);
    for ($m = 1; $m <= 12; $m++) {
        $sql_m = "INSERT INTO pre_proyecto_detalles_mes
                  (Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual, Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible)
                  VALUES ($pdp_id, $m, 22, 0.083333, $pres_mes, 0, 0, $pres_mes)
                  ON DUPLICATE KEY UPDATE Pdm_PreMensual = $pres_mes, Pdm_Disponible = $pres_mes - Pdm_Ejecutado - Pdm_Comprometido";
        $mysqli->query($sql_m);
    }

    ppto_proy_json(array('status' => 'success', 'message' => 'Rubro guardado y prorrateado correctamente.', 'pdp_id' => $pdp_id));
}

if ($action === 'delete_rubro') {
    $pdp_id = (int)$_POST['pdp_id'];
    $mysqli->query("DELETE FROM pre_proyecto_detalles_mes WHERE Pdp_Cod = $pdp_id");
    $ok = $mysqli->query("DELETE FROM pre_proyecto_detalles WHERE Pdp_Cod = $pdp_id AND Emp_Cod = $Emp_Cod");
    ppto_proy_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Rubro eliminado.' : $mysqli->error));
}

if ($action === 'get_maestros') {
    $plantillas = array();
    $r1 = $mysqli->query("SELECT plt_id, plt_nombre FROM pre_plantillas WHERE Emp_Cod = $Emp_Cod AND plt_estado = 'A'");
    if ($r1) {
        while ($row = $r1->fetch_assoc()) {
            $plantillas[] = $row;
        }
    }

    $partidas = array();
    $r2 = $mysqli->query("SELECT Ppa_Cod AS ppa_id, Ppa_Cla AS ppa_codigo_clasificacion, Ppa_Des AS ppa_descripcion FROM pre_partidas WHERE Emp_Cod = $Emp_Cod AND Ppa_Est = 'A' ORDER BY Ppa_Cla");
    if ($r2) {
        while ($row = $r2->fetch_assoc()) {
            $partidas[] = $row;
        }
    }

    $cabeceras = array();
    $r3 = $mysqli->query("SELECT Ppe_Cod AS ppe_id, Ppe_Ani AS ppe_anio, Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion FROM pre_presupuesto WHERE Emp_Cod = $Emp_Cod ORDER BY Ppe_Ani DESC, Ppe_Ver DESC");
    if ($r3) {
        while ($row = $r3->fetch_assoc()) {
            $cabeceras[] = $row;
        }
    }

    ppto_proy_json(array('status' => 'success', 'plantillas' => $plantillas, 'partidas' => $partidas, 'cabeceras' => $cabeceras));
}

ppto_proy_json(array('status' => 'error', 'message' => 'Accion invalida.'));
