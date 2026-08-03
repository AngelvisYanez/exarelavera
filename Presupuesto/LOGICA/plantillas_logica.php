<?php
/**
 * plantillas_logica.php
 * Controlador y l�gica de datos CRUD para Plantillas Presupuestarias (EXA PPTO).
 * Implementa las funciones CRUD, validaci�n de integridad y endpoints AJAX.
 */

// Desactivar visualizaci�n de errores HTML directa para garantizar respuestas JSON limpias en AJAX
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
    CREATE TABLE IF NOT EXISTS `exa_ppto_plantillas` (
      `plt_id` INT AUTO_INCREMENT PRIMARY KEY,
      `emp_id` INT NOT NULL,
      `plt_nombre` VARCHAR(150) NOT NULL,
      `plt_descripcion` TEXT NULL,
      `plt_estado` CHAR(1) NOT NULL DEFAULT 'A',
      `plt_fecha_registro` DATETIME NOT NULL,
      `usu_id` INT NOT NULL,
      INDEX `idx_plt_empresa` (`emp_id`),
      INDEX `idx_plt_estado` (`plt_estado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla de plantillas presupuestarias por empresa';
");

// Agregar de forma segura el campo plt_id en exa_ppto_proyectos para ligar proyectos a plantillas
$check_col = $mysqli_conn->query("SHOW COLUMNS FROM `exa_ppto_proyectos` LIKE 'plt_id'");
if ($check_col && $check_col->num_rows === 0) {
    $mysqli_conn->query("ALTER TABLE `exa_ppto_proyectos` ADD COLUMN `plt_id` INT NULL AFTER `usu_id`, ADD INDEX `idx_proy_plantilla` (`plt_id`)");
}

$mysqli_conn->query("
    CREATE TABLE IF NOT EXISTS `exa_ppto_plantilla_partidas` (
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
    if ($plt_id <= 0 || !ppto_plt_tabla_existe('exa_ppto_plantilla_partidas')) {
        return 0;
    }
    $res = $mysqli_conn->query("SELECT COUNT(*) AS total FROM exa_ppto_plantilla_partidas WHERE plt_id = $plt_id");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

/**
 * Cuenta proyectos activos vinculados a una plantilla.
 */
function ppto_plt_contar_proyectos_activos($plt_id, $emp_id) {
    global $mysqli_conn;
    $plt_id = (int)$plt_id;
    $emp_id = (int)$emp_id;

    if (!ppto_plt_tabla_existe('exa_ppto_proyectos') || !ppto_plt_columna_existe('exa_ppto_proyectos', 'plt_id')) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total FROM exa_ppto_proyectos WHERE plt_id = $plt_id AND emp_id = $emp_id";
    if (ppto_plt_columna_existe('exa_ppto_proyectos', 'proy_estado')) {
        $sql .= " AND proy_estado = 'A'";
    }

    $res = $mysqli_conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

function ppto_plt_resolver_usuario_nombre($usu_id) {
    global $mysqli_conn;
    $usu_id = (int)$usu_id;
    if ($usu_id <= 0) {
        return 'Sistema';
    }

    if (ppto_plt_tabla_existe('usuarios') && ppto_plt_tabla_existe('persona')) {
        $res = $mysqli_conn->query(
            "SELECT TRIM(CONCAT(IFNULL(persona.Prs_Ape,''), ' ', IFNULL(persona.Prs_Nom,''))) AS nom
             FROM usuarios
             INNER JOIN persona ON persona.Prs_Cod = usuarios.Prs_Cod
             WHERE usuarios.Usu_Cod = $usu_id LIMIT 1"
        );
        if ($res && $row = $res->fetch_assoc()) {
            $nom = trim($row['nom']);
            if ($nom !== '') {
                return $nom;
            }
        }
    }

    if (ppto_plt_tabla_existe('usuarios') && ppto_plt_columna_existe('usuarios', 'Usu_Nom')) {
        $res = $mysqli_conn->query("SELECT Usu_Nom FROM usuarios WHERE Usu_Cod = $usu_id LIMIT 1");
        if ($res && ($row = $res->fetch_assoc()) && trim($row['Usu_Nom']) !== '') {
            return trim($row['Usu_Nom']);
        }
    }

    return 'Sistema';
}

function ppto_plantilla_nombre_existe($emp_id, $nombre, $exclude_id = 0) {
    global $mysqli_conn;
    $emp_id = (int)$emp_id;
    $exclude_id = (int)$exclude_id;
    $nombre = $mysqli_conn->real_escape_string(trim($nombre));

    if ($nombre === '') {
        return false;
    }

    $sql = "SELECT plt_id FROM exa_ppto_plantillas WHERE emp_id = $emp_id AND plt_nombre = '$nombre'";
    if ($exclude_id > 0) {
        $sql .= " AND plt_id != $exclude_id";
    }
    $sql .= ' LIMIT 1';

    $res = $mysqli_conn->query($sql);
    return $res && $res->num_rows > 0;
}

function ppto_plantilla_copiar_partidas($orig_id, $new_id) {
    global $mysqli_conn;
    $orig_id = (int)$orig_id;
    $new_id = (int)$new_id;

    if ($orig_id <= 0 || $new_id <= 0 || !ppto_plt_tabla_existe('exa_ppto_plantilla_partidas')) {
        return 0;
    }

    $sql = "INSERT IGNORE INTO exa_ppto_plantilla_partidas (plt_id, ppa_id)
            SELECT $new_id, ppa_id FROM exa_ppto_plantilla_partidas WHERE plt_id = $orig_id";
    $mysqli_conn->query($sql);
    return (int)$mysqli_conn->affected_rows;
}

/**
 * Lista las plantillas presupuestarias de una empresa.
 *
 * @param int $id_empresa ID de la empresa de la sesi�n.
 * @return array Arreglo con la lista de plantillas encontradas.
 */
function ppto_plantilla_listar($id_empresa) {
    global $mysqli_conn;
    $id_empresa = (int)$id_empresa;

    $sql = "SELECT p.*
            FROM exa_ppto_plantillas p
            WHERE p.emp_id = $id_empresa
            ORDER BY p.plt_nombre ASC";

    $res = $mysqli_conn->query($sql);
    $plantillas = array();
    if (!$res) {
        return $plantillas;
    }

    while ($row = $res->fetch_assoc()) {
        $plt_id = (int)$row['plt_id'];
        $proyectos = ppto_plt_contar_proyectos_activos($plt_id, $id_empresa);
        $partidas = ppto_plt_contar_partidas($plt_id);
        $usuario_nombre = ppto_plt_resolver_usuario_nombre($row['usu_id']);

        $plantillas[] = array(
            'plt_id' => $plt_id,
            'emp_id' => (int)$row['emp_id'],
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
 * @param array $datos Arreglo asociativo con los datos (plt_nombre, plt_descripcion, emp_id, usu_id).
 * @return int|bool Retorna el ID de la plantilla creada o false en caso de fallo.
 */
function ppto_plantilla_crear($datos) {
    global $mysqli_conn;
    
    $emp_id = (int)$datos['emp_id'];
    $usu_id = (int)$datos['usu_id'];
    $nombre = $mysqli_conn->real_escape_string(trim($datos['plt_nombre']));
    $descripcion = isset($datos['plt_descripcion']) ? "'" . $mysqli_conn->real_escape_string(trim($datos['plt_descripcion'])) . "'" : "NULL";
    $estado = isset($datos['plt_estado']) ? $mysqli_conn->real_escape_string(strtoupper(trim($datos['plt_estado']))) : 'A';

    $sql = "INSERT INTO exa_ppto_plantillas (emp_id, plt_nombre, plt_descripcion, plt_estado, plt_fecha_registro, usu_id)
            VALUES ($emp_id, '$nombre', $descripcion, '$estado', NOW(), $usu_id)";
            
    $res = $mysqli_conn->query($sql);
    return $res ? $mysqli_conn->insert_id : false;
}

/**
 * Modifica una plantilla presupuestaria existente.
 *
 * @param int $id ID de la plantilla.
 * @param array $datos Arreglo asociativo con los nuevos datos (plt_nombre, plt_descripcion, plt_estado).
 * @return bool Retorna true si fue modificada correctamente, false de lo contrario.
 */
function ppto_plantilla_editar($id, $datos) {
    global $mysqli_conn, $Ses_Emp_Cod;

    $id = (int)$id;
    $emp_id = (int)$Ses_Emp_Cod;
    $nombre = $mysqli_conn->real_escape_string(trim($datos['plt_nombre']));
    $descripcion = isset($datos['plt_descripcion']) ? "'" . $mysqli_conn->real_escape_string(trim($datos['plt_descripcion'])) . "'" : "NULL";
    $estado = isset($datos['plt_estado']) ? $mysqli_conn->real_escape_string(strtoupper(trim($datos['plt_estado']))) : 'A';

    $sql = "UPDATE exa_ppto_plantillas SET 
                plt_nombre = '$nombre', 
                plt_descripcion = $descripcion, 
                plt_estado = '$estado'
            WHERE plt_id = $id AND emp_id = $emp_id";

    return $mysqli_conn->query($sql) ? true : false;
}

/**
 * Elimina de manera l�gica/f�sica una plantilla si no est� asociada a proyectos activos.
 *
 * @param int $id ID de la plantilla a eliminar.
 * @return array Retorna un arreglo asociativo con 'status' (bool) y 'message' (string).
 */
function ppto_plantilla_eliminar($id) {
    global $mysqli_conn, $Ses_Emp_Cod;
    $id = (int)$id;
    $emp_id = (int)$Ses_Emp_Cod;

    $total_activos = ppto_plt_contar_proyectos_activos($id, $emp_id);
    if ($total_activos > 0) {
        return array(
            'status' => false,
            'message' => "La plantilla no puede ser eliminada porque esta asociada a $total_activos proyecto(s) activo(s)."
        );
    }

    // Proceder con la eliminacion fisica en la tabla
    $sql_del = "DELETE FROM exa_ppto_plantillas WHERE plt_id = $id AND emp_id = $emp_id";
    if ($mysqli_conn->query($sql_del)) {
        return array(
            'status' => true,
            'message' => "Plantilla eliminada correctamente."
        );
    }

    return array(
        'status' => false,
        'message' => "Error al ejecutar la sentencia de eliminaci�n en la base de datos."
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
    $emp_id = (int)$Ses_Emp_Cod;
    $usu_id = (int)$Ses_Usu_Cod;
    $nombre = $mysqli_conn->real_escape_string(trim($nuevo_nombre));

    // 1. Consultar la plantilla origen para duplicar sus atributos
    $sql_orig = "SELECT * FROM exa_ppto_plantillas WHERE plt_id = $id AND emp_id = $emp_id LIMIT 1";
    $res_orig = $mysqli_conn->query($sql_orig);
    if ($res_orig && $row_orig = $res_orig->fetch_assoc()) {
        $descripcion = $row_orig['plt_descripcion'] !== null ? "'" . $mysqli_conn->real_escape_string($row_orig['plt_descripcion']) . "'" : "NULL";
        $estado = $mysqli_conn->real_escape_string($row_orig['plt_estado']);

        $sql_ins = "INSERT INTO exa_ppto_plantillas (emp_id, plt_nombre, plt_descripcion, plt_estado, plt_fecha_registro, usu_id)
                    VALUES ($emp_id, '$nombre', $descripcion, '$estado', NOW(), $usu_id)";

        if ($mysqli_conn->query($sql_ins)) {
            $new_id = (int)$mysqli_conn->insert_id;
            ppto_plantilla_copiar_partidas($id, $new_id);
            return $new_id;
        }
    }
    return false;
}


// ROUTING DE ENDPOINTS AJAX: Procesa y responde peticiones as�ncronas en formato JSON
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
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
                echo json_encode(array('status' => false, 'message' => 'M�todo HTTP no soportado (debe ser POST).'));
                exit();
            }
            
            $input_data = array(
                'plt_nombre' => isset($_POST['plt_nombre']) ? $_POST['plt_nombre'] : '',
                'plt_descripcion' => isset($_POST['plt_descripcion']) ? $_POST['plt_descripcion'] : '',
                'plt_estado' => isset($_POST['plt_estado']) ? $_POST['plt_estado'] : 'A',
                'emp_id' => $Ses_Emp_Cod,
                'usu_id' => $Ses_Usu_Cod
            );

            // Validar reglas de negocio
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
                echo json_encode(array('status' => false, 'message' => 'M�todo HTTP no soportado (debe ser POST).'));
                exit();
            }

            $plt_id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : 0;
            $input_data = array(
                'plt_id' => $plt_id,
                'plt_nombre' => isset($_POST['plt_nombre']) ? $_POST['plt_nombre'] : '',
                'plt_descripcion' => isset($_POST['plt_descripcion']) ? $_POST['plt_descripcion'] : '',
                'plt_estado' => isset($_POST['plt_estado']) ? $_POST['plt_estado'] : 'A',
                'emp_id' => $Ses_Emp_Cod
            );

            // Validar reglas de negocio
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
                echo json_encode(array('status' => false, 'message' => 'M�todo HTTP no soportado (debe ser POST).'));
                exit();
            }

            $plt_id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : 0;
            if ($plt_id <= 0) {
                echo json_encode(array('status' => false, 'message' => 'ID de plantilla no v�lido para eliminaci�n.'));
                exit();
            }

            $del_res = ppto_plantilla_eliminar($plt_id);
            echo json_encode($del_res);
            exit();

        case 'duplicar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('status' => false, 'message' => 'M�todo HTTP no soportado (debe ser POST).'));
                exit();
            }

            $plt_id = isset($_POST['plt_id']) ? (int)$_POST['plt_id'] : 0;
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
                if (ppto_plt_tabla_existe('exa_ppto_plantilla_partidas')) {
                    $res_cnt = $mysqli_conn->query("SELECT COUNT(*) AS c FROM exa_ppto_plantilla_partidas WHERE plt_id = " . (int)$dup_id);
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
            echo json_encode(array('status' => false, 'message' => 'Acci�n AJAX solicitada no v�lida.'));
            exit();
    }
}
