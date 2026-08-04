<?php
/**
 * plantillas_logica.php
 * Controlador y lógica de datos CRUD para Plantillas Presupuestarias (EXA PPTO).
 * Implementa las funciones CRUD, validación de integridad y endpoints AJAX.
 */

// Desactivar visualización de errores HTML directa para garantizar respuestas JSON limpias en AJAX
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('../VALIDACIONES/plantillas_validaciones.php');

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

// VERIFICAR Y AUTO-CREAR TABLAS DE PLANTILLAS Y INTEGRIDAD CON MAESTROS
// Ejecuta el DDL de forma silenciosa para asegurar que el sistema pueda operar de inmediato.
$mysqli_conn->query("
    CREATE TABLE IF NOT EXISTS `pre_plantillas` (
      `plt_id` INT AUTO_INCREMENT PRIMARY KEY,
      `Emp_Cod` INT NOT NULL,
      `plt_nombre` VARCHAR(150) NOT NULL,
      `plt_descripcion` TEXT NULL,
      `plt_estado` CHAR(1) NOT NULL DEFAULT 'A',
      `plt_fecha_registro` DATETIME NOT NULL,
      `Usu_Cod` INT NOT NULL,
      INDEX `idx_plt_empresa` (`Emp_Cod`),
      INDEX `idx_plt_estado` (`plt_estado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla de plantillas presupuestarias por empresa';
");

// Agregar de forma segura el campo Plt_Cod en pre_proyectos para ligar proyectos a plantillas
$check_col = $mysqli_conn->query("SHOW COLUMNS FROM `pre_proyectos` LIKE 'Plt_Cod'");
if ($check_col && $check_col->num_rows === 0) {
    $mysqli_conn->query("ALTER TABLE `pre_proyectos` ADD COLUMN `Plt_Cod` INT NULL AFTER `Usu_Cod`, ADD INDEX `idx_proy_plantilla` (`Plt_Cod`)");
}

$mysqli_conn->query("
    CREATE TABLE IF NOT EXISTS `pre_plantilla_partidas` (
      `plp_id` INT AUTO_INCREMENT PRIMARY KEY,
      `plt_id` INT NOT NULL,
      `ppa_id` INT NOT NULL,
      UNIQUE KEY `idx_plp_unico` (`plt_id`, `ppa_id`),
      INDEX `idx_plp_plt` (`plt_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Partidas asociadas a plantillas presupuestarias'
");


/**
 * Verifica si una tabla existe en la BD activa.
 */
function ppto_plt_tabla_existe($tabla) {
    global $mysqli_conn;
    $tabla = $mysqli_conn->real_escape_string($tabla);
    $res = $mysqli_conn->query("SHOW TABLES LIKE '$tabla'");
    return $res && $res->num_rows > 0;
}

/**
 * Verifica si una columna existe en una tabla.
 */
function ppto_plt_columna_existe($tabla, $columna) {
    global $mysqli_conn;
    $tabla = $mysqli_conn->real_escape_string($tabla);
    $columna = $mysqli_conn->real_escape_string($columna);
    $res = $mysqli_conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
    return $res && $res->num_rows > 0;
}

/**
 * Cuenta partidas vinculadas a una plantilla.
 */
function ppto_plt_contar_partidas($plt_id) {
    global $mysqli_conn;
    $plt_id = (int)$plt_id;
    if ($plt_id <= 0 || !ppto_plt_tabla_existe('pre_plantilla_partidas')) {
        return 0;
    }
    $res = $mysqli_conn->query("SELECT COUNT(*) AS total FROM pre_plantilla_partidas WHERE plt_id = $plt_id");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

/**
 * Cuenta proyectos activos vinculados a una plantilla.
 */
function ppto_plt_contar_proyectos_activos($plt_id) {
    global $mysqli_conn, $Ses_Emp_Cod;
    $plt_id = (int)$plt_id;
    $Emp_Cod = (int)$Ses_Emp_Cod;
    if ($plt_id <= 0 || !ppto_plt_tabla_existe('pre_proyectos') || !ppto_plt_columna_existe('pre_proyectos', 'Plt_Cod')) {
        return 0;
    }
    $res = $mysqli_conn->query("SELECT COUNT(*) AS total FROM pre_proyectos WHERE Plt_Cod = $plt_id AND Emp_Cod = $Emp_Cod AND Pro_Est = 'A'");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

/**
 * Retorna las partidas asociadas a una plantilla especifica.
 */
function ppto_plt_obtener_partidas_ids($plt_id) {
    global $mysqli_conn;
    $plt_id = (int)$plt_id;
    $ids = array();
    if ($plt_id <= 0 || !ppto_plt_tabla_existe('pre_plantilla_partidas')) {
        return $ids;
    }
    $res = $mysqli_conn->query("SELECT ppa_id FROM pre_plantilla_partidas WHERE plt_id = $plt_id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['ppa_id'];
        }
    }
    return $ids;
}

/**
 * Copia masiva de partidas de plantilla origen a nueva plantilla duplicada.
 */
function ppto_plt_clonar_partidas($orig_id, $new_id) {
    global $mysqli_conn;
    $orig_id = (int)$orig_id;
    $new_id = (int)$new_id;
    if ($orig_id <= 0 || $new_id <= 0 || !ppto_plt_tabla_existe('pre_plantilla_partidas')) {
        return false;
    }
    $sql = "INSERT IGNORE INTO pre_plantilla_partidas (plt_id, ppa_id)
            SELECT $new_id, ppa_id FROM pre_plantilla_partidas WHERE plt_id = $orig_id";
    return $mysqli_conn->query($sql);
}

// ROUTER DE ACCIONES AJAX VIA POST/GET
$action = '';
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
}

if ($action === 'list') {
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $sql = "SELECT p.*, 
                   (SELECT COUNT(*) FROM pre_plantilla_partidas pp WHERE pp.plt_id = p.plt_id) AS num_partidas,
                   (SELECT COUNT(*) FROM pre_proyectos pr WHERE pr.Plt_Cod = p.plt_id AND pr.Emp_Cod = $Emp_Cod AND pr.Pro_Est = 'A') AS num_proyectos
            FROM pre_plantillas p
            WHERE p.Emp_Cod = $Emp_Cod
            ORDER BY p.plt_id DESC";
    $res = $mysqli_conn->query($sql);
    $data = array();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'data' => $data));
    exit;
}

if ($action === 'get') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $Emp_Cod = (int)$Ses_Emp_Cod;
    
    $sql = "SELECT * FROM pre_plantillas WHERE plt_id = $id AND Emp_Cod = $Emp_Cod LIMIT 1";
    $res = $mysqli_conn->query($sql);
    
    if ($res && $plantilla = $res->fetch_assoc()) {
        $partidas_ids = ppto_plt_obtener_partidas_ids($id);
        $num_proyectos = ppto_plt_contar_proyectos_activos($id);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'ok' => true,
            'data' => $plantilla,
            'partidas' => $partidas_ids,
            'num_proyectos' => $num_proyectos
        ));
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'Plantilla no encontrada.'));
    }
    exit;
}

if ($action === 'save') {
    $id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : 0;
    $nombre = isset($_POST['plt_nombre']) ? trim($_POST['plt_nombre']) : '';
    $descripcion = isset($_POST['plt_descripcion']) ? trim($_POST['plt_descripcion']) : '';
    $estado = (isset($_POST['plt_estado']) && $_POST['plt_estado'] === 'I') ? 'I' : 'A';
    $partidas = (isset($_POST['partidas']) && is_array($_POST['partidas'])) ? $_POST['partidas'] : array();
    
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $Usu_Cod = (int)$Ses_Usu_Cod;

    // Validacion previa centralizada
    $val = ppto_plt_validar_datos($_POST, $id, $Emp_Cod, $mysqli_conn);
    if (!$val['ok']) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($val);
        exit;
    }

    $nombre_esc = $mysqli_conn->real_escape_string($nombre);
    $desc_esc = $mysqli_conn->real_escape_string($descripcion);

    if ($id <= 0) {
        // INSERTAR NUEVA PLANTILLA
        $sql = "INSERT INTO pre_plantillas (Emp_Cod, plt_nombre, plt_descripcion, plt_estado, plt_fecha_registro, Usu_Cod)
                VALUES ($Emp_Cod, '$nombre_esc', '$desc_esc', '$estado', NOW(), $Usu_Cod)";
        if ($mysqli_conn->query($sql)) {
            $id = $mysqli_conn->insert_id;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'error' => 'Error al guardar la plantilla en base de datos.'));
            exit;
        }
    } else {
        // ACTUALIZAR PLANTILLA EXISTENTE
        $sql = "UPDATE pre_plantillas SET 
                plt_nombre = '$nombre_esc',
                plt_descripcion = '$desc_esc',
                plt_estado = '$estado'
                WHERE plt_id = $id AND Emp_Cod = $Emp_Cod";
        if (!$mysqli_conn->query($sql)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'error' => 'Error al actualizar la plantilla.'));
            exit;
        }
    }

    // SINCRONIZAR PARTIDAS VINCULADAS
    $mysqli_conn->query("DELETE FROM pre_plantilla_partidas WHERE plt_id = $id");
    if (!empty($partidas)) {
        $values = array();
        foreach ($partidas as $ppa_id) {
            $ppa_id = (int)$ppa_id;
            if ($ppa_id > 0) {
                $values[] = "($id, $ppa_id)";
            }
        }
        if (!empty($values)) {
            $sql_ins = "INSERT IGNORE INTO pre_plantilla_partidas (plt_id, ppa_id) VALUES " . implode(',', $values);
            $mysqli_conn->query($sql_ins);
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'id' => $id, 'mensaje' => 'Plantilla guardada exitosamente.'));
    exit;
}

if ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $Emp_Cod = (int)$Ses_Emp_Cod;

    $num_proyectos = ppto_plt_contar_proyectos_activos($id);
    if ($num_proyectos > 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => "No se puede eliminar la plantilla porque esta vinculada a $num_proyectos proyecto(s) activo(s)."));
        exit;
    }

    $sql_del = "DELETE FROM pre_plantillas WHERE plt_id = $id AND Emp_Cod = $Emp_Cod";
    if ($mysqli_conn->query($sql_del)) {
        $mysqli_conn->query("DELETE FROM pre_plantilla_partidas WHERE plt_id = $id");
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => true, 'mensaje' => 'Plantilla eliminada correctamente.'));
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'Error al intentar eliminar la plantilla.'));
    }
    exit;
}

if ($action === 'duplicate') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $Usu_Cod = (int)$Ses_Usu_Cod;

    $sql_orig = "SELECT * FROM pre_plantillas WHERE plt_id = $id AND Emp_Cod = $Emp_Cod LIMIT 1";
    $res_orig = $mysqli_conn->query($sql_orig);
    if ($res_orig && $orig = $res_orig->fetch_assoc()) {
        $nuevo_nombre = $mysqli_conn->real_escape_string($orig['plt_nombre'] . " (Copia)");
        $desc_esc = $mysqli_conn->real_escape_string($orig['plt_descripcion']);
        
        $sql_ins = "INSERT INTO pre_plantillas (Emp_Cod, plt_nombre, plt_descripcion, plt_estado, plt_fecha_registro, Usu_Cod)
                    VALUES ($Emp_Cod, '$nuevo_nombre', '$desc_esc', 'A', NOW(), $Usu_Cod)";
        if ($mysqli_conn->query($sql_ins)) {
            $nuevo_id = $mysqli_conn->insert_id;
            ppto_plt_clonar_partidas($id, $nuevo_id);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => true, 'id' => $nuevo_id, 'mensaje' => 'Plantilla duplicada exitosamente.'));
            exit;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => false, 'error' => 'No se pudo duplicar la plantilla especificada.'));
    exit;
}

if ($action === 'export') {
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $sql = "SELECT p.plt_id, p.plt_nombre, p.plt_descripcion, p.plt_estado, p.plt_fecha_registro,
                   (SELECT COUNT(*) FROM pre_plantilla_partidas pp WHERE pp.plt_id = p.plt_id) AS total_partidas
            FROM pre_plantillas p
            WHERE p.Emp_Cod = $Emp_Cod
            ORDER BY p.plt_id DESC";
    $res = $mysqli_conn->query($sql);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Plantillas_Presupuestarias_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Bom para UTF-8 en Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, array('ID', 'Nombre de Plantilla', 'Descripcion', 'Partidas Asignadas', 'Estado', 'Fecha Registro'));
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($output, array(
                $row['plt_id'],
                $row['plt_nombre'],
                $row['plt_descripcion'],
                $row['total_partidas'],
                ($row['plt_estado'] === 'A' ? 'Activo' : 'Inactivo'),
                $row['plt_fecha_registro']
            ));
        }
    }
    fclose($output);
    exit;
}

if ($action === 'get_partidas_catalogo') {
    $Emp_Cod = (int)$Ses_Emp_Cod;
    
    // Obtener todo el catalogo de partidas activas de la empresa
    $sql = "SELECT ppa_id, ppa_codigo_clasificacion, ppa_descripcion, ppa_tipo, ppa_naturaleza, ppa_nivel
            FROM pre_partidas
            WHERE Emp_Cod = $Emp_Cod AND ppa_estado = 'A'
            ORDER BY ppa_codigo_clasificacion ASC";
    $res = $mysqli_conn->query($sql);
    
    $data = array();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'data' => $data));
    exit;
}

if ($action === 'import') {
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $Usu_Cod = (int)$Ses_Usu_Cod;

    if (!isset($_FILES['file_csv']) || $_FILES['file_csv']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'Por favor seleccione un archivo CSV valido para importar.'));
        exit;
    }

    $tmp_name = $_FILES['file_csv']['tmp_name'];
    $handle = fopen($tmp_name, 'r');
    if (!$handle) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'No se pudo abrir el archivo de importacion.'));
        exit;
    }

    $importadas = 0;
    $duplicadas = 0;
    $errores = 0;
    $linea = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $linea++;
        if ($linea === 1 && (strtolower(trim($data[0])) === 'nombre' || strtolower(trim($data[0])) === 'id')) {
            continue; // Omitir cabecera si existe
        }

        $nombre = isset($data[0]) ? trim($data[0]) : '';
        $descripcion = isset($data[1]) ? trim($data[1]) : '';
        $codigos_partidas = isset($data[2]) ? trim($data[2]) : '';

        if (empty($nombre)) {
            $errores++;
            continue;
        }

        $nombre_esc = $mysqli_conn->real_escape_string($nombre);
        $desc_esc = $mysqli_conn->real_escape_string($descripcion);

        // Verificar si ya existe por nombre en esta empresa
        $check = $mysqli_conn->query("SELECT plt_id FROM pre_plantillas WHERE Emp_Cod = $Emp_Cod AND plt_nombre = '$nombre_esc' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $duplicadas++;
            continue;
        }

        $sql_ins = "INSERT INTO pre_plantillas (Emp_Cod, plt_nombre, plt_descripcion, plt_estado, plt_fecha_registro, Usu_Cod)
                    VALUES ($Emp_Cod, '$nombre_esc', '$desc_esc', 'A', NOW(), $Usu_Cod)";
        if ($mysqli_conn->query($sql_ins)) {
            $plt_id = $mysqli_conn->insert_id;
            $importadas++;

            // Mapear partidas por codigos de clasificacion separados por coma o punto y coma
            if (!empty($codigos_partidas)) {
                $cods = preg_split('/[;,\|]+/', $codigos_partidas);
                foreach ($cods as $cod) {
                    $cod = trim($cod);
                    if (!empty($cod)) {
                        $cod_esc = $mysqli_conn->real_escape_string($cod);
                        $res_part = $mysqli_conn->query("SELECT ppa_id FROM pre_partidas WHERE Emp_Cod = $Emp_Cod AND ppa_codigo_clasificacion = '$cod_esc' LIMIT 1");
                        if ($res_part && $row_p = $res_part->fetch_assoc()) {
                            $ppa_id = (int)$row_p['ppa_id'];
                            $mysqli_conn->query("INSERT IGNORE INTO pre_plantilla_partidas (plt_id, ppa_id) VALUES ($plt_id, $ppa_id)");
                        }
                    }
                }
            }
        } else {
            $errores++;
        }
    }
    fclose($handle);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'ok' => true,
        'mensaje' => "Importacion completada: $importadas nueva(s) plantilla(s), $duplicadas duplicada(s) omitida(s), $errores error(es).",
        'importadas' => $importadas,
        'duplicadas' => $duplicadas,
        'errores' => $errores
    ));
    exit;
}

if ($action === 'duplicate_structure') {
    $dup_id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : 0;
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $Usu_Cod = (int)$Ses_Usu_Cod;

    if ($dup_id <= 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'Identificador de plantilla invalido para duplicar.'));
        exit;
    }

    $sql_orig = "SELECT * FROM pre_plantillas WHERE plt_id = $dup_id AND Emp_Cod = $Emp_Cod LIMIT 1";
    $res_orig = $mysqli_conn->query($sql_orig);
    if (!$res_orig || $res_orig->num_rows === 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'La plantilla especificada no existe o fue eliminada.'));
        exit;
    }

    $orig = $res_orig->fetch_assoc();
    $nuevo_nombre = $mysqli_conn->real_escape_string($orig['plt_nombre'] . " (Copia " . date('d/m H:i') . ")");
    $desc_esc = $mysqli_conn->real_escape_string($orig['plt_descripcion']);

    $sql_ins = "INSERT INTO pre_plantillas (Emp_Cod, plt_nombre, plt_descripcion, plt_estado, plt_fecha_registro, Usu_Cod)
                VALUES ($Emp_Cod, '$nuevo_nombre', '$desc_esc', 'A', NOW(), $Usu_Cod)";
    if ($mysqli_conn->query($sql_ins)) {
        $nuevo_id = $mysqli_conn->insert_id;
        
        $copiadas = 0;
        if (ppto_plt_tabla_existe('pre_plantilla_partidas')) {
            $res_cnt = $mysqli_conn->query("SELECT COUNT(*) AS c FROM pre_plantilla_partidas WHERE plt_id = " . (int)$dup_id);
            if ($res_cnt && $rc = $res_cnt->fetch_assoc()) {
                $copiadas = (int)$rc['c'];
            }
            ppto_plt_clonar_partidas($dup_id, $nuevo_id);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'ok' => true,
            'id' => $nuevo_id,
            'mensaje' => "Estructura duplicada con exito ($copiadas partidas copiadas).",
            'copiadas' => $copiadas
        ));
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => false, 'error' => 'Error al duplicar la estructura de la plantilla.'));
    exit;
}
