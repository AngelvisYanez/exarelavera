<?php
/**
 * plantillas_logica.php
 * Controlador y lï¿½gica de datos CRUD para Plantillas Presupuestarias (EXA PPTO).
 * Implementa las funciones CRUD, validaciï¿½n de integridad y endpoints AJAX.
 */

// Desactivar visualizaciï¿½n de errores HTML directa para garantizar respuestas JSON limpias en AJAX
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

// VERIFICAR TABLAS pre_* (no crear/alterar vistas exa_ppto_*)
// Las tablas ya existen por migracion; solo asegurar columna Plt_Cod en pre_proyectos.
if (ppto_plt_es_tabla_base('pre_proyectos') && !ppto_plt_columna_existe('pre_proyectos', 'Plt_Cod')) {
    $mysqli_conn->query("ALTER TABLE `pre_proyectos` ADD COLUMN `Plt_Cod` BIGINT(20) NULL COMMENT 'FK pre_plantillas' AFTER `Usu_Cod`, ADD INDEX `idx_proy_plantilla` (`Plt_Cod`)");
}
if (!ppto_plt_es_tabla_base('pre_plantillas')) {
    $mysqli_conn->query("
    CREATE TABLE IF NOT EXISTS `pre_plantillas` (
      `Plt_Cod` BIGINT(20) NOT NULL AUTO_INCREMENT,
      `Emp_Cod` BIGINT(20) NOT NULL,
      `Plt_Nom` VARCHAR(150) NOT NULL,
      `Plt_Des` TEXT NULL,
      `Plt_Est` CHAR(1) NOT NULL DEFAULT 'A',
      `Plt_FecReg` DATETIME NOT NULL,
      `Usu_Cod` BIGINT(20) DEFAULT NULL,
      PRIMARY KEY (`Plt_Cod`),
      INDEX `idx_plt_empresa` (`Emp_Cod`),
      INDEX `idx_plt_estado` (`Plt_Est`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");
}
if (!ppto_plt_es_tabla_base('pre_plantilla_partidas')) {
    $mysqli_conn->query("
    CREATE TABLE IF NOT EXISTS `pre_plantilla_partidas` (
      `Plp_Cod` BIGINT(20) NOT NULL AUTO_INCREMENT,
      `Plt_Cod` BIGINT(20) NOT NULL,
      `Ppa_Cod` BIGINT(20) NOT NULL,
      PRIMARY KEY (`Plp_Cod`),
      UNIQUE KEY `idx_plp_unico` (`Plt_Cod`, `Ppa_Cod`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");
}

/**
 * True si es tabla base (no vista).
 */
function ppto_plt_es_tabla_base($tabla) {
    global $mysqli_conn;
    $t = $mysqli_conn->real_escape_string($tabla);
    $res = $mysqli_conn->query(
        "SELECT TABLE_TYPE FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' LIMIT 1"
    );
    if ($res && ($row = $res->fetch_assoc())) {
        return strtoupper($row['TABLE_TYPE']) === 'BASE TABLE';
    }
    return false;
}


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
function ppto_plt_contar_partidas($Plt_Cod) {
    global $mysqli_conn;
    $Plt_Cod = (int)$Plt_Cod;
    if ($Plt_Cod <= 0 || !ppto_plt_tabla_existe('pre_plantilla_partidas')) {
        return 0;
    }
    $res = $mysqli_conn->query("SELECT COUNT(*) AS total FROM pre_plantilla_partidas WHERE Plt_Cod = $Plt_Cod");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

/**
 * Cuenta proyectos activos vinculados a una plantilla.
 */
function ppto_plt_contar_proyectos_activos($Plt_Cod, $Emp_Cod) {
    global $mysqli_conn;
    $Plt_Cod = (int)$Plt_Cod;
    $Emp_Cod = (int)$Emp_Cod;

    $sql = "SELECT COUNT(*) AS total FROM pre_proyectos WHERE Plt_Cod = $Plt_Cod AND Emp_Cod = $Emp_Cod AND Pro_Est = 'A'";

    $res = $mysqli_conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

function ppto_plt_resolver_usuario_nombre($Usu_Cod) {
    global $mysqli_conn;
    $Usu_Cod = (int)$Usu_Cod;
    if ($Usu_Cod <= 0) {
        return 'Sistema';
    }

    if (ppto_plt_tabla_existe('usuarios') && ppto_plt_tabla_existe('persona')) {
        $res = $mysqli_conn->query(
            "SELECT TRIM(CONCAT(IFNULL(persona.Prs_Ape,''), ' ', IFNULL(persona.Prs_Nom,''))) AS nom
             FROM usuarios
             INNER JOIN persona ON persona.Prs_Cod = usuarios.Prs_Cod
             WHERE usuarios.Usu_Cod = $Usu_Cod LIMIT 1"
        );
        if ($res && $row = $res->fetch_assoc()) {
            $nom = trim($row['nom']);
            if ($nom !== '') {
                return $nom;
            }
        }
    }

    if (ppto_plt_tabla_existe('usuarios') && ppto_plt_columna_existe('usuarios', 'Usu_Nom')) {
        $res = $mysqli_conn->query("SELECT Usu_Nom FROM usuarios WHERE Usu_Cod = $Usu_Cod LIMIT 1");
        if ($res && ($row = $res->fetch_assoc()) && trim($row['Usu_Nom']) !== '') {
            return trim($row['Usu_Nom']);
        }
    }

    return 'Sistema';
}

function ppto_plantilla_nombre_existe($Emp_Cod, $nombre, $exclude_id = 0) {
    global $mysqli_conn;
    $Emp_Cod = (int)$Emp_Cod;
    $exclude_id = (int)$exclude_id;
    $nombre = $mysqli_conn->real_escape_string(trim($nombre));

    if ($nombre === '') {
        return false;
    }

    $sql = "SELECT Plt_Cod FROM pre_plantillas WHERE Emp_Cod = $Emp_Cod AND Plt_Nom = '$nombre'";
    if ($exclude_id > 0) {
        $sql .= " AND Plt_Cod != $exclude_id";
    }
    $sql .= ' LIMIT 1';

    $res = $mysqli_conn->query($sql);
    return $res && $res->num_rows > 0;
}

function ppto_plantilla_copiar_partidas($orig_id, $new_id) {
    global $mysqli_conn;
    $orig_id = (int)$orig_id;
    $new_id = (int)$new_id;

    if ($orig_id <= 0 || $new_id <= 0 || !ppto_plt_tabla_existe('pre_plantilla_partidas')) {
        return 0;
    }

    $sql = "INSERT IGNORE INTO pre_plantilla_partidas (Plt_Cod, Ppa_Cod)
            SELECT $new_id, Ppa_Cod FROM pre_plantilla_partidas WHERE Plt_Cod = $orig_id";
    $mysqli_conn->query($sql);
    return (int)$mysqli_conn->affected_rows;
}

/**
 * Lista las plantillas presupuestarias de una empresa.
 *
 * @param int $id_empresa ID de la empresa de la sesiï¿½n.
 * @return array Arreglo con la lista de plantillas encontradas.
 */
function ppto_plantilla_listar($id_empresa) {
    global $mysqli_conn;
    $id_empresa = (int)$id_empresa;

    $sql = "SELECT p.Plt_Cod AS plt_id, p.Emp_Cod, p.Plt_Nom AS plt_nombre, p.Plt_Des AS plt_descripcion,
                   p.Plt_Est AS plt_estado, p.Plt_FecReg AS plt_fecha_registro, p.Usu_Cod
            FROM pre_plantillas p
            WHERE p.Emp_Cod = $id_empresa
            ORDER BY p.Plt_Nom ASC";

    $res = $mysqli_conn->query($sql);
    $plantillas = array();
    if (!$res) {
        return $plantillas;
    }

    while ($row = $res->fetch_assoc()) {
        $plt_id = (int)$row['plt_id'];
        $proyectos = ppto_plt_contar_proyectos_activos($plt_id, $id_empresa);
        $partidas = ppto_plt_contar_partidas($plt_id);
        $usuario_nombre = ppto_plt_resolver_usuario_nombre($row['Usu_Cod']);

        $plantillas[] = array(
            'plt_id' => $plt_id,
            'Emp_Cod' => (int)$row['Emp_Cod'],
            'plt_nombre' => $row['plt_nombre'],
            'plt_descripcion' => $row['plt_descripcion'],
            'plt_estado' => $row['plt_estado'],
            'plt_fecha_registro' => $row['plt_fecha_registro'],
            'usuario_nombre' => $usuario_nombre,
            'proyectos_activos' => $proyectos,
            'partidas_total' => $partidas
        );
    }
    return $plantillas;
}

/**
 * Crea una nueva plantilla presupuestaria en la BD.
 *
 * @param array $datos Arreglo asociativo con los datos (Plt_Nom, Plt_Des, Emp_Cod, Usu_Cod).
 * @return int|bool Retorna el ID de la plantilla creada o false en caso de fallo.
 */
function ppto_plantilla_crear($datos) {
    global $mysqli_conn;
    $datos = ppto_plantilla_normalizar_datos($datos);

    $Emp_Cod = (int)$datos['Emp_Cod'];
    $Usu_Cod = (int)$datos['Usu_Cod'];
    $nombre = $mysqli_conn->real_escape_string(trim($datos['plt_nombre']));
    $descripcion = (isset($datos['plt_descripcion']) && $datos['plt_descripcion'] !== '')
        ? "'" . $mysqli_conn->real_escape_string(trim($datos['plt_descripcion'])) . "'"
        : "NULL";
    $estado = isset($datos['plt_estado']) ? $mysqli_conn->real_escape_string(strtoupper(trim($datos['plt_estado']))) : 'A';

    $sql = "INSERT INTO pre_plantillas (Emp_Cod, Plt_Nom, Plt_Des, Plt_Est, Plt_FecReg, Usu_Cod)
            VALUES ($Emp_Cod, '$nombre', $descripcion, '$estado', NOW(), $Usu_Cod)";

    $res = $mysqli_conn->query($sql);
    return $res ? $mysqli_conn->insert_id : false;
}

/**
 * Modifica una plantilla presupuestaria existente.
 *
 * @param int $id ID de la plantilla.
 * @param array $datos Arreglo asociativo con los nuevos datos (Plt_Nom, Plt_Des, Plt_Est).
 * @return bool Retorna true si fue modificada correctamente, false de lo contrario.
 */
function ppto_plantilla_editar($id, $datos) {
    global $mysqli_conn, $Ses_Emp_Cod;
    $datos = ppto_plantilla_normalizar_datos($datos);

    $id = (int)$id;
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $nombre = $mysqli_conn->real_escape_string(trim($datos['plt_nombre']));
    $descripcion = (isset($datos['plt_descripcion']) && $datos['plt_descripcion'] !== '')
        ? "'" . $mysqli_conn->real_escape_string(trim($datos['plt_descripcion'])) . "'"
        : "NULL";
    $estado = isset($datos['plt_estado']) ? $mysqli_conn->real_escape_string(strtoupper(trim($datos['plt_estado']))) : 'A';

    $sql = "UPDATE pre_plantillas SET 
                Plt_Nom = '$nombre', 
                Plt_Des = $descripcion, 
                Plt_Est = '$estado'
            WHERE Plt_Cod = $id AND Emp_Cod = $Emp_Cod";

    return $mysqli_conn->query($sql) ? true : false;
}

/**
 * Elimina de manera lï¿½gica/fï¿½sica una plantilla si no estï¿½ asociada a proyectos activos.
 *
 * @param int $id ID de la plantilla a eliminar.
 * @return array Retorna un arreglo asociativo con 'status' (bool) y 'message' (string).
 */
function ppto_plantilla_eliminar($id) {
    global $mysqli_conn, $Ses_Emp_Cod;
    $id = (int)$id;
    $Emp_Cod = (int)$Ses_Emp_Cod;

    $total_activos = ppto_plt_contar_proyectos_activos($id, $Emp_Cod);
    if ($total_activos > 0) {
        return array(
            'status' => false,
            'message' => "La plantilla no puede ser eliminada porque esta asociada a $total_activos proyecto(s) activo(s)."
        );
    }

    // Proceder con la eliminacion fisica en la tabla
    $sql_del = "DELETE FROM pre_plantillas WHERE Plt_Cod = $id AND Emp_Cod = $Emp_Cod";
    if ($mysqli_conn->query($sql_del)) {
        return array(
            'status' => true,
            'message' => "Plantilla eliminada correctamente."
        );
    }

    return array(
        'status' => false,
        'message' => "Error al ejecutar la sentencia de eliminaciï¿½n en la base de datos."
    );
}

/**
 * Clona una plantilla existente creando un duplicado exacto con un nuevo nombre.
 *
 * @param int $id ID de la plantilla origen.
 * @param string $nuevo_nombre Nuevo nombre para la plantilla clonada.
 * @return int|bool Retorna el ID de la nueva plantilla duplicada o false en caso de error.
 */
function ppto_plantilla_duplicar($id, $nuevo_nombre) {
    global $mysqli_conn, $Ses_Emp_Cod, $Ses_Usu_Cod;
    $id = (int)$id;
    $Emp_Cod = (int)$Ses_Emp_Cod;
    $Usu_Cod = (int)$Ses_Usu_Cod;
    $nombre = $mysqli_conn->real_escape_string(trim($nuevo_nombre));

    // 1. Consultar la plantilla origen para duplicar sus atributos
    $sql_orig = "SELECT Plt_Cod AS plt_id, Emp_Cod, Plt_Nom AS plt_nombre, Plt_Des AS plt_descripcion,
            Plt_Est AS plt_estado, Plt_FecReg AS plt_fecha_registro, Usu_Cod
            FROM pre_plantillas WHERE Plt_Cod = $id AND Emp_Cod = $Emp_Cod LIMIT 1";
    $res_orig = $mysqli_conn->query($sql_orig);
    if ($res_orig && $row_orig = $res_orig->fetch_assoc()) {
        $descripcion = $row_orig['plt_descripcion'] !== null ? "'" . $mysqli_conn->real_escape_string($row_orig['plt_descripcion']) . "'" : "NULL";
        $estado = $mysqli_conn->real_escape_string($row_orig['plt_estado']);

        $sql_ins = "INSERT INTO pre_plantillas (Emp_Cod, Plt_Nom, Plt_Des, Plt_Est, Plt_FecReg, Usu_Cod)
                    VALUES ($Emp_Cod, '$nombre', $descripcion, '$estado', NOW(), $Usu_Cod)";

        if ($mysqli_conn->query($sql_ins)) {
            $new_id = (int)$mysqli_conn->insert_id;
            ppto_plantilla_copiar_partidas($id, $new_id);
            return $new_id;
        }
    }
    return false;
}


// ROUTING DE ENDPOINTS AJAX: Procesa y responde peticiones asincronas en formato JSON
if (isset($_GET['action'])) {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    $action = trim($_GET['action']);

    switch ($action) {
        case 'listar':
            if (empty($Ses_Emp_Cod)) {
                echo json_encode(array(
                    'status' => false,
                    'message' => 'Sesion de empresa no valida.',
                    'data' => array()
                ));
                exit();
            }
            $resultado = ppto_plantilla_listar($Ses_Emp_Cod);
            echo json_encode(array(
                'status' => true,
                'message' => 'Listado de plantillas obtenido con exito.',
                'data' => $resultado
            ));
            exit();

        case 'crear':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('status' => false, 'message' => 'Metodo HTTP no soportado (debe ser POST).'));
                exit();
            }

            $input_data = ppto_plantilla_normalizar_datos(array(
                'plt_nombre' => isset($_POST['plt_nombre']) ? $_POST['plt_nombre'] : (isset($_POST['Plt_Nom']) ? $_POST['Plt_Nom'] : ''),
                'plt_descripcion' => isset($_POST['plt_descripcion']) ? $_POST['plt_descripcion'] : (isset($_POST['Plt_Des']) ? $_POST['Plt_Des'] : ''),
                'plt_estado' => isset($_POST['plt_estado']) ? $_POST['plt_estado'] : (isset($_POST['Plt_Est']) ? $_POST['Plt_Est'] : 'A'),
                'Emp_Cod' => $Ses_Emp_Cod,
                'Usu_Cod' => $Ses_Usu_Cod
            ));

            $val = ppto_plantilla_validar_datos($input_data, false);
            if (!$val['valido']) {
                echo json_encode(array(
                    'status' => false,
                    'message' => implode(' | ', $val['errores'])
                ));
                exit();
            }

            if (ppto_plantilla_nombre_existe($Ses_Emp_Cod, $input_data['plt_nombre'])) {
                echo json_encode(array(
                    'status' => false,
                    'message' => 'Ya existe una plantilla con ese nombre en la empresa.'
                ));
                exit();
            }

            $nuevo_id = ppto_plantilla_crear($input_data);
            if ($nuevo_id) {
                echo json_encode(array(
                    'status' => true,
                    'message' => "Plantilla creada correctamente.",
                    'data' => array('plt_id' => $nuevo_id)
                ));
            } else {
                echo json_encode(array('status' => false, 'message' => 'Error al guardar la plantilla en la base de datos.'));
            }
            exit();

        case 'editar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('status' => false, 'message' => 'Metodo HTTP no soportado (debe ser POST).'));
                exit();
            }

            $plt_id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : (isset($_POST['Plt_Cod']) ? (int)$_POST['Plt_Cod'] : 0);
            $input_data = ppto_plantilla_normalizar_datos(array(
                'plt_id' => $plt_id,
                'plt_nombre' => isset($_POST['plt_nombre']) ? $_POST['plt_nombre'] : (isset($_POST['Plt_Nom']) ? $_POST['Plt_Nom'] : ''),
                'plt_descripcion' => isset($_POST['plt_descripcion']) ? $_POST['plt_descripcion'] : (isset($_POST['Plt_Des']) ? $_POST['Plt_Des'] : ''),
                'plt_estado' => isset($_POST['plt_estado']) ? $_POST['plt_estado'] : (isset($_POST['Plt_Est']) ? $_POST['Plt_Est'] : 'A'),
                'Emp_Cod' => $Ses_Emp_Cod
            ));

            $val = ppto_plantilla_validar_datos($input_data, true);
            if (!$val['valido']) {
                echo json_encode(array(
                    'status' => false,
                    'message' => implode(' | ', $val['errores'])
                ));
                exit();
            }

            if (ppto_plantilla_nombre_existe($Ses_Emp_Cod, $input_data['plt_nombre'], $plt_id)) {
                echo json_encode(array(
                    'status' => false,
                    'message' => 'Ya existe otra plantilla con ese nombre en la empresa.'
                ));
                exit();
            }

            $edit_ok = ppto_plantilla_editar($plt_id, $input_data);
            if ($edit_ok) {
                echo json_encode(array(
                    'status' => true,
                    'message' => "Plantilla actualizada correctamente.",
                    'data' => array('plt_id' => $plt_id)
                ));
            } else {
                echo json_encode(array('status' => false, 'message' => 'Error al actualizar los datos en el servidor.'));
            }
            exit();

        case 'eliminar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('status' => false, 'message' => 'Metodo HTTP no soportado (debe ser POST).'));
                exit();
            }

            $plt_id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : (isset($_POST['Plt_Cod']) ? (int)$_POST['Plt_Cod'] : 0);
            if ($plt_id <= 0) {
                echo json_encode(array('status' => false, 'message' => 'ID de plantilla no valido para eliminacion.'));
                exit();
            }

            $del_res = ppto_plantilla_eliminar($plt_id);
            echo json_encode($del_res);
            exit();

        case 'duplicar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('status' => false, 'message' => 'Metodo HTTP no soportado (debe ser POST).'));
                exit();
            }

            $plt_id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : (isset($_POST['Plt_Cod']) ? (int)$_POST['Plt_Cod'] : 0);
            $nuevo_nombre = isset($_POST['plt_nombre_nuevo']) ? $_POST['plt_nombre_nuevo'] : '';

            $val = ppto_plantilla_validar_duplicacion($plt_id, $nuevo_nombre);
            if (!$val['valido']) {
                echo json_encode(array(
                    'status' => false,
                    'message' => implode(' | ', $val['errores'])
                ));
                exit();
            }

            if (ppto_plantilla_nombre_existe($Ses_Emp_Cod, $nuevo_nombre)) {
                echo json_encode(array(
                    'status' => false,
                    'message' => 'Ya existe una plantilla con ese nombre en la empresa.'
                ));
                exit();
            }

            $dup_id = ppto_plantilla_duplicar($plt_id, $nuevo_nombre);
            if ($dup_id) {
                $partidas_copiadas = 0;
                if (ppto_plt_tabla_existe('pre_plantilla_partidas')) {
                    $res_cnt = $mysqli_conn->query("SELECT COUNT(*) AS c FROM pre_plantilla_partidas WHERE Plt_Cod = " . (int)$dup_id);
                    if ($res_cnt && $rc = $res_cnt->fetch_assoc()) {
                        $partidas_copiadas = (int)$rc['c'];
                    }
                }
                echo json_encode(array(
                    'status' => true,
                    'message' => 'Plantilla duplicada correctamente (' . $partidas_copiadas . ' partida(s) copiada(s)).',
                    'data' => array('plt_id' => $dup_id, 'partidas_copiadas' => $partidas_copiadas)
                ));
            } else {
                echo json_encode(array('status' => false, 'message' => 'Error al duplicar la plantilla seleccionada.'));
            }
            exit();

        default:
            echo json_encode(array('status' => false, 'message' => 'Acciï¿½n AJAX solicitada no vï¿½lida.'));
            exit();
    }
}
