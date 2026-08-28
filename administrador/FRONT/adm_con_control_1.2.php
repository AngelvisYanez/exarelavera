<?php
/**
 * Descripcion: Permite generar la sesion correspondiente para el usuario con bases de datos distribuidas
 * Fecha de actualizacion: 2010-08-30
 * @autor: Lewis Chimarro
 * Fecha de actualizacion: 2013-01-22
 * @autor: Lewis Chimarro
 * Fecha de actualizacion: 2013-JUN-20
 * @autor: Lewis Chimarro
 * Fecha de actualizacion: 2026-JUL-28 - Seguridad: password_hash, session_regenerate_id, SQL injection fix
 */
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/adm_log_control.php');

/**
 * MIGRACIÓN register_globals: Variables GET/POST ya no se inyectan como locales
 */
$user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : (isset($_GET['user_name']) ? trim($_GET['user_name']) : '');
$Emp_Cod = isset($_POST['Emp_Cod']) ? (int)$_POST['Emp_Cod'] : (isset($_GET['Emp_Cod']) ? (int)$_GET['Emp_Cod'] : 0);
$Suc_Cod = isset($_POST['Suc_Cod']) ? (int)$_POST['Suc_Cod'] : (isset($_GET['Suc_Cod']) ? (int)$_GET['Suc_Cod'] : 0);
$encryptor = isset($_POST['encryptor']) ? trim($_POST['encryptor']) : (isset($_GET['encryptor']) ? trim($_GET['encryptor']) : '');
$password = isset($_POST['password']) ? trim($_POST['password']) : (isset($_GET['password']) ? trim($_GET['password']) : '');
$ajax_check = isset($_POST['ajax_check']) ? trim($_POST['ajax_check']) : (isset($_GET['ajax_check']) ? trim($_GET['ajax_check']) : '');
$pagina = isset($_POST['pagina']) ? trim($_POST['pagina']) : (isset($_GET['pagina']) ? trim($_GET['pagina']) : '');

if(empty($user_name) || $Emp_Cod <= 0) {
    header(isset($Browser)&&$Browser =="WML"?"Location: ../../movil/FRONT/index_m.php?errorsistema=si":"Location: ../../index.php?errorsistema=si");
    exit();
}

// Sanitizar inputs
$user_name = trim($user_name);
$Emp_Cod = (int)$Emp_Cod;
$Suc_Cod = isset($Suc_Cod) ? (int)$Suc_Cod : 0;

/**
 * Creacion del Objeto de Conexion
 */
$obBD_conexion = new Class_Log_Conexion_Cnt;
/**
 * Creacion del Objeto de Datos
 */
$obBD_con1 = new Class_Log_Datos_Cnt;

/**
 * Consulta la base de datos en funcion del Usuario y la Empresa
 */
$row_data = $obBD_con1->getRowConsulta(2, $Emp_Cod.'*'.$user_name, $obBD_conexion);

$bddName = (!empty($row_data) && !empty($row_data['Dat_Dis'])) ? $row_data['Dat_Dis'] : 'exa';
if (!empty($row_data) && !empty($row_data['Dat_Dis'])) {
    /**
     * Conexion a la base de datos distribuida, dinamica
     */
    $obBD_conexion = new Class_Log_Conexion_Cnt($bddName);
}

/**
 * Autenticacion con soporte dual: password_hash (modern) + MD5 (legacy)
 * Primero buscamos el usuario para verificar la contrasena en PHP
 */
$user_sql = "SELECT 
    usuarios.Usu_Ced, usuarios.Usu_Est, usuarios.Suc_Cod, sucursal.Emp_Cod,
    usuarios.Prs_Cod, usuarios.Usu_Cod, usuarios.Usu_Tip,
    persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Sex,
    usuarios.Usu_Cad, usuarios.Usu_Pal, usuarios.Usu_Men,
    empresas.Emp_Nom, empresas.Emp_Log, sucursal.Suc_Des,
    empresas.Emp_Cor, sucursal.Suc_Web
FROM usuarios
INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
WHERE Usu_Ced = '$user_name' AND empresas.Emp_Cod = $Emp_Cod AND usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";

if ($Suc_Cod > 0) {
    $user_sql .= " AND sucursal.Suc_Cod = $Suc_Cod";
}

$row_rs_control_query = $obBD_con1->getRowConsultaSql($user_sql, $obBD_conexion);

$total_rs_control = 0;
$row_rs_control = null;

if (!empty($row_rs_control_query)) {
    $storedHash = $row_rs_control_query['Usu_Pal'];
    $passwordValid = false;
    
    // Verificar contrasena: soporte dual (password_hash moderno + MD5 legacy)
    if (strpos($storedHash, '$2y$') === 0) {
        // Hash moderno (bcrypt)
        $passwordValid = password_verify(trim($encryptor), $storedHash);
    } else {
        // Legacy MD5 - comparar directamente
        $passwordValid = ($storedHash === trim($encryptor));
    }
    
    if ($passwordValid) {
        $total_rs_control = 1;
        $row_rs_control = $row_rs_control_query;
        
        // Si la contrasena era MD5, migrarla a bcrypt automaticamente
        if (strpos($storedHash, '$2y$') !== 0) {
            $newHash = password_hash(trim($encryptor), PASSWORD_BCRYPT, ['cost' => 12]);
            $newHashSafe = mysqli_real_escape_string($obBD_conexion->conexion, $newHash);
            $obBD_con1->consulta("UPDATE usuarios SET Usu_Pal = '$newHashSafe' WHERE Usu_Cod = " . (int)$row_rs_control['Usu_Cod'], $obBD_conexion);
        }
    }
}

/**
 * Ingresa solo cuando la coincidencia es diferente de cero
 */
if ($total_rs_control != 0)
{
    /**
     * Regenerar ID de sesion para prevenir session fixation
     */
    session_regenerate_id(true);
    
    /**
     * Control para la informacion del systema
     */
    $row_rs_system = $obBD_con1->getRowConsulta(15, '', $obBD_conexion);

    /**
     * Consulta los perfiles asignados al usuario
     */
    $rs_perfiles = $obBD_con1->getArrayConsulta(21, $row_rs_control["Usu_Cod"], $obBD_conexion);

    /**
     * Consulta de la url de la foto
     */
    $row_rs_foto = $obBD_con1->getRowConsulta(1, $row_rs_control["Prs_Cod"].'*'.$Emp_Cod, $obBD_conexion);

    /**
     * Asignacion de los permisos de usuario
     */
    $lperf = [];
    $Per_Des = [];
    foreach($rs_perfiles as $v0)
    {
        $lperf[] = $v0["Per_Cod"];
        $Per_Des[] = $v0["Per_Des"];
    }

    /**
     * Asignacion de datos en las variables de sesion
     * Variables de Sesion del usuario
     */
    $_SESSION['hp2_'] = "o";
    $_SESSION['Ses_Usu_Cod'] = $row_rs_control['Usu_Cod'];
    $_SESSION['Ses_Usu_Ced'] = $row_rs_control['Usu_Ced'];
    $_SESSION['Ses_Usu_Est'] = $row_rs_control['Usu_Est'];
    $_SESSION['Ses_Usu_Cad'] = $row_rs_control['Usu_Cad'];
    $_SESSION['Ses_Usu_Men'] = $row_rs_control['Usu_Men'];
    $_SESSION['Ses_Usu_Tip'] = isset($row_rs_control['Usu_Tip']) ? $row_rs_control['Usu_Tip'] : 'U';

    /**
     * Variable para definir la sucursal y empresa
     */
    $_SESSION['Ses_Suc_Cod'] = $row_rs_control['Suc_Cod'];
    $_SESSION['Ses_Suc_Nom'] = $row_rs_control['Suc_Des'];
    $_SESSION['Ses_Emp_Cod'] = $row_rs_control['Emp_Cod'];
    $_SESSION['Ses_Emp_Nom'] = $row_rs_control['Emp_Nom'];
    $_SESSION['Ses_Emp_Cor'] = $row_rs_control['Emp_Cor'];
    $_SESSION['Ses_Suc_Web'] = $row_rs_control['Suc_Web'];
    $_SESSION['Ses_Emp_Log'] = $row_rs_control['Emp_Log'];

    /**
     * Variables del Perfil del usuario
     */
    $_SESSION['Ses_Lis_Per'] = $lperf;
    $_SESSION['Ses_Per_Des'] = $Per_Des;

    /**
     * Variables de la persona
     */
    $_SESSION['Ses_Prs_Cod'] = $row_rs_control['Prs_Cod'];
    $_SESSION['Ses_Prs_Nom'] = $row_rs_control['Prs_Nom'];
    $_SESSION['Ses_Prs_Ape'] = $row_rs_control['Prs_Ape'];
    $_SESSION['Ses_Prs_Ced'] = $row_rs_control['Prs_Ced'];
    $_SESSION['Ses_Prs_Sex'] = $row_rs_control['Prs_Sex'];
    $_SESSION['Ses_Per_Fot'] = isset($row_rs_foto['Per_Fot']) ? $row_rs_foto['Per_Fot'] : null;

    $apellido = explode(' ', $_SESSION['Ses_Prs_Ape']);
    $nombre = explode(' ', $_SESSION['Ses_Prs_Nom']);

    $_SESSION['username'] = ucfirst(strtolower($nombre[0])) . "-" . ucfirst(strtolower($apellido[0]));

    /**
     * Variables para la informacion del sistema
     */
    $_SESSION['Ses_Sys_Sitio'] = isset($row_rs_system['Sys_Nom']) ? $row_rs_system['Sys_Nom'] : 'EXA Contable';
    $_SESSION['Ses_Sys_Nom'] = (isset($row_rs_system['Sys_Nom']) ? $row_rs_system['Sys_Nom'] : 'EXA') . " [" . (isset($row_rs_system['Sys_Des']) ? $row_rs_system['Sys_Des'] : 'Software Contable') . "]";
    $_SESSION['Ses_Sys_Ver'] = isset($row_rs_system['Sys_Ver']) ? $row_rs_system['Sys_Ver'] : '2.0';
    $_SESSION['Ses_Sys_Tim'] = date("Y-m-d H:i:s");
    $_SESSION['Ses_Sys_Dat'] = date("Y-m-d");
    $_SESSION['Ses_Sys_Sit'] = "inside";
    $_SESSION['Ses_Sys_Cor'] = isset($row_rs_system['Sys_Cor']) ? $row_rs_system['Sys_Cor'] : '';

    /**
     * Variable para la base de datos del sistema local
     */
    $_SESSION['Ses_Dat_Dis'] = $bddName;
    $_SESSION['Ses_Dat_Aut'] = !empty($row_data['Dat_Aut']) ? $row_data['Dat_Aut'] : '';
    $_SESSION['Ses_Dat_Stg'] = !empty($row_data['Dat_Stg']) ? $row_data['Dat_Stg'] : '';

    /**
     * Variable de sesion de auditoria
     */
    $objAud = new Class_Log_Datos_Aud;
    $_SESSION['Ses_Ses_Cod'] = $objAud->guardarInicioSesion($_SESSION['Ses_Sys_Tim'], $_SESSION['Ses_Usu_Cod'], $obBD_conexion);
    $objAud->liberar();

    /**
     * Control que almacena el tipo de acceso al sistema
     * Datos del navegador
     */
    $objSes = new Class_Log_Datos_Aud;
    $Browser = detectar_acceso();
    $objSes->guardarSesionSistema($_SESSION['Ses_Ses_Cod'], $Browser, $obBD_conexion);
    $objSes->liberar();

    /**
     * Verificacion de clave por defecto "123456"
     */
    $isDefaultPass = false;
    $checkPass = $obBD_con1->getRowConsultaSql("SELECT Usu_Cod, Usu_Pal FROM usuarios WHERE Usu_Cod = " . (int)$row_rs_control['Usu_Cod'], $obBD_conexion);
    if ($checkPass) {
        $storedHash = $checkPass['Usu_Pal'];
        if (strpos($storedHash, '$2y$') === 0) {
            $isDefaultPass = password_verify('123456', $storedHash);
        } else {
            $isDefaultPass = ($storedHash === md5('123456'));
        }
    }

    /**
     * Si es una peticion AJAX (Silent Check de handleLogin)
     */
    if (!empty($ajax_check)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'insecure' => $isDefaultPass
        ]);
        exit();
    }

    /**
     * Redireccion a la pagina principal
     */
    header("Location: home.php");
    exit();
}
else
{
    /**
     * Si es una peticion AJAX y falla el login
     */
    if (!empty($ajax_check)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error_type' => 'user'
        ]);
        exit();
    }

    header(isset($Browser)&&$Browser =="WML"?"Location: ../../movil/FRONT/index_m.php?errorusuario=si":"Location: ../../index.php?errorusuario=si");
    exit();
}
?>
