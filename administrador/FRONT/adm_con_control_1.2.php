<?php
/**
* Descripci�n: Permite generar la sesion correspondiente para el usuario con bases de datos distribuidas
* Fecha de actualizaci�n:	2010-08-30
* @autor:	Lewis Chimarro
* Fecha de actualizaci�n:	2013-01-22
* @autor: 	Lewis Chimarro
* Fecha de actualizaci�n:	2013-JUN-20
* @autor: 	Lewis Chimarro
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/adm_log_control.php');

if(!isset($Emp_Cod)||!isset($user_name)) {
    header(isset($Browser)&&$Browser =="WML"?"Location: ../../movil/FRONT/index_m.php?errorsistema=si":"Location: ../../index.php?errorsistema=si");
    exit();
}
/**
* Creacion del Objeto de Conexion
*/
$obBD_conexion = new Class_Log_Conexion_Cnt;
/**
* Creacion del Objeto de Datos
*/
$obBD_con1 =  new Class_Log_Datos_Cnt;

/**
* Consulta la base de datos en funcion del Usuario y la Empresa
*/
$row_data = $obBD_con1->getRowConsulta(2, $Emp_Cod.'*'.trim($user_name), $obBD_conexion);

if (!empty($row_data)) {
    /**
    * Conexion a la base de datos distribuida, dinamica
    */
    $obBD_conexion = new Class_Log_Conexion_Cnt($row_data['Dat_Dis']);
    /**
    * Consulta que realiza la autenticacion del usuario
    */
    $row_rs_control = $obBD_con1->getRowConsulta(16, trim($user_name).'*'.trim($encryptor).'*'.$Emp_Cod.'*'.$Suc_Cod, $obBD_conexion);
    $total_rs_control = count($row_rs_control);
} else $total_rs_control = 0;

/**
* Ingresa solo cuando la coincidencia es diferente de cero
*/
if ($total_rs_control !=0)
{
    /**
     * Validar acceso por dispositivo
     */
    $res_dispositivo = $obBD_con1->validarDispositivo($row_rs_control['Usu_Cod'], $obBD_conexion);
    if (!$res_dispositivo['success']) {
        if (isset($_POST['ajax_check'])) {
            echo json_encode(array('success' => false, 'message' => $res_dispositivo['message'], 'error_type' => 'device'));
            exit();
        }
        header("Location: ../../index.php?errordispositivo=si");
        exit();
    }

	/**
	* Control para la informacion del systema
	*/
	$row_rs_system =  $obBD_con1->getRowConsulta(15, '', $obBD_conexion);

	/**
	* Consulta los perfiles asignados al usuario
	*/
	$rs_perfiles = $obBD_con1->getArrayConsulta(21, $row_rs_control["Usu_Cod"], $obBD_conexion);

	/**
	* Consulta de la url de la foto
	*/
	$row_rs_foto = $obBD_con1->getRowConsulta(1, $row_rs_control["Prs_Cod"].'*'.$Emp_Cod, $obBD_conexion);

	/**
	* Asignaci�n de los permisos de usuario
	*/
	foreach($rs_perfiles as $v0)
	{
		$lperf[]=$v0["Per_Cod"];
		$Per_Des[]=$v0["Per_Des"];
	}

	/**
	* Asignacion de datos en las variables de sessi�n
	*
	* Variables de Sesion del usuario
	*/
	$_SESSION['hp2_']="o";
	$_SESSION['Ses_Usu_Cod']=$row_rs_control['Usu_Cod'];
	$_SESSION['Ses_Usu_Ced']=$row_rs_control['Usu_Ced'];
	$_SESSION['Ses_Usu_Est']=$row_rs_control['Usu_Est'];
	$_SESSION['Ses_Usu_Cad']=$row_rs_control['Usu_Cad'];
	$_SESSION['Ses_Usu_Men']=$row_rs_control['Usu_Men'];
    $_SESSION['Ses_Usu_Tip']=isset($row_rs_control['Usu_Tip'])?$row_rs_control['Usu_Tip']:'U';

	/**
	* Variable para definir la sucursal y empresa
	*/
	$_SESSION['Ses_Suc_Cod']=$row_rs_control['Suc_Cod'];
	$_SESSION['Ses_Suc_Nom']=$row_rs_control['Suc_Des'];
	$_SESSION['Ses_Emp_Cod']=$row_rs_control['Emp_Cod'];
	$_SESSION['Ses_Emp_Nom']=$row_rs_control['Emp_Nom'];
	$_SESSION['Ses_Emp_Cor']=$row_rs_control['Emp_Cor'];
	$_SESSION['Ses_Suc_Web']=$row_rs_control['Suc_Web'];
	$_SESSION['Ses_Emp_Log']=$row_rs_control['Emp_Log'];

	/**
	* Variables del Perfil del usuario
	*/
	$_SESSION['Ses_Lis_Per']=$lperf;
	$_SESSION['Ses_Per_Des']=$Per_Des; //Descripci�n del perfil

	/**
	* Variables de la persona
	*/
	$_SESSION['Ses_Prs_Cod']=$row_rs_control['Prs_Cod'];
	$_SESSION['Ses_Prs_Nom']=$row_rs_control['Prs_Nom'];
	$_SESSION['Ses_Prs_Ape']=$row_rs_control['Prs_Ape'];
	$_SESSION['Ses_Prs_Ced']=$row_rs_control['Prs_Ced'];
	$_SESSION['Ses_Prs_Sex']=$row_rs_control['Prs_Sex'];
	$_SESSION['Ses_Per_Fot']=$row_rs_foto['Per_Fot'];

	$apellido = explode(' ', $_SESSION['Ses_Prs_Ape']);
	$nombre = explode(' ', $_SESSION['Ses_Prs_Nom']);

	$_SESSION['username']=  ucfirst(strtolower($nombre[0]))."-".ucfirst(strtolower($apellido[0]));

	/**
	* Variables para la informacion del sistema
	*/
	$_SESSION['Ses_Sys_Sitio']=$row_rs_system['Sys_Nom'];//Nombre del sitio
	$_SESSION['Ses_Sys_Nom']=$row_rs_system['Sys_Nom']." [".$row_rs_system['Sys_Des']."]";//Nombre del sistema
	$_SESSION['Ses_Sys_Ver']=$row_rs_system['Sys_Ver'];//Version del sistema
	$_SESSION['Ses_Sys_Tim']=date("Y-m-d H:i:s");
	$_SESSION['Ses_Sys_Dat']=date("Y-m-d");
	$_SESSION['Ses_Sys_Sit']="inside"; //Indica si esta dentro o fuera del sitio
	$_SESSION['Ses_Sys_Cor']=$row_rs_system['Sys_Cor'];	//Correo del sistema

	/**
	* Variable para la base de datos del sistema local
	*/
	$_SESSION['Ses_Dat_Dis'] = $row_data['Dat_Dis']; //Base de datos distribuida local
	$_SESSION['Ses_Dat_Aut'] = $row_data['Dat_Aut']; //Base de datos auditoria
	$_SESSION['Ses_Dat_Stg'] = $row_data['Dat_Stg']; //Base de datos storage

	/**
	 * Variable de sesion de auditoria
	 */
	$objAud = new Class_Log_Datos_Aud;
	$_SESSION['Ses_Ses_Cod'] = $objAud->guardarInicioSesion($_SESSION['Ses_Sys_Tim'], $_SESSION['Ses_Usu_Cod'], $obBD_conexion);
	$objAud->liberar();

	/**
	* Control que almacena el tipo de acceso al sistama
	* Datos del navegador
	*/
	$Browser =  detectar_acceso();

	/**
	* Verificacion para saber si el usuario cambio de clave
	*/
        //echo trim($password)."==".trim($_SESSION['Ses_Usu_Ced']);
	if (trim($password)==trim($_SESSION['Ses_Usu_Ced']) || trim($password) == '123456')
	{
		$_SESSION['Ses_Sys_Sit']="outside"; //Indica si esta dentro o fuera del sitio
        if (isset($_POST['ajax_check'])) {
            echo json_encode(array('success' => true, 'insecure' => true));
            exit();
        }
		header ("Location: ../../index.php");
		exit();
	}

    if (isset($_POST['ajax_check'])) {
        echo json_encode(array('success' => true, 'insecure' => false));
        exit();
    }

	if($Browser=="WML")
	{
		//$Per_Pee = $row_rs_persona['Prs_Cod'];
		//header ("Location: ../../movil/FRONT/mov_menu.php?Mov_Prs_Cod=$Prs_Pee&Per_Cod=$Ses_Per_Cod&Per_Let=$Ses_Per_Let");
	}
	else
	{
		//header ("Location: ../movil/FRONT/mov_con_menu.php");
		//header ("Location: ../../home.php");
		/**
		* Verifica si existe una pagina para cargar por defecto
		*/

		if (isset($pagina) && trim($pagina) != ""){
			header ("Location: ../../index.php?errorusuario=si");
		}else{
			header ("Location: ../FRONT/home.php");
		}//Fin del if (trim($pagina) != "")
	}
	exit();
}//Fin del if (count($rs_control)!=0){
else
{
	/**
	 * Auditoria
	 */
	$objAud = new Class_Log_Datos_Aud;

	$objAud->GuardarSesionError(date("Y-m-d H:i:s"), trim($user_name), trim($password), $Emp_Cod);

	$objAud->liberar();

	if(isset($Browser)&&$Browser =="WML"){
		header("Location: ../../movil/FRONT/index_m.php?errorusuario=si");
	}
	else{
        if (isset($_POST['ajax_check'])) {
            echo json_encode(array('success' => false));
            exit();
        }
		header("Location: ../../index.php?errorusuario=si");
	}
        exit();
}//Fin del if ($total_rs_control !=0 ){
?>