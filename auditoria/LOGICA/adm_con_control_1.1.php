<?php
/**
* Descripción: Permite generar la sesion correspondiente para el usuario con bases de datos distribuidas
* Fecha de actualización:	2010-08-30
* @autor:	Lewis Chimarro 
* Fecha de actualización:	2013-01-22
* @autor: 	Lewis Chimarro 
* Fecha de actualización:	2013-JUN-20
* @autor: 	Lewis Chimarro 
*/

require_once('../../Librerias/procedimientos/almacenados_standar.php');
session_start();
require_once('../LOGICA/adm_log_control.php');

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

/**
* Conexion a la base de datos distribuida, dinamica
*/
$obBD_conexion = new Class_Log_Conexion_Cnt($row_data['Dat_Dis']);
/**
* Consulta que realiza la autenticacion del usuario 
*/
$row_rs_control = $obBD_con1->getRowConsulta(16, trim($user_name).'*'.trim($encryptor).'*'.$Emp_Cod, $obBD_conexion);
$total_rs_control = count($row_rs_control);

/**
* Ingresa solo cuando la coincidencia es diferente de cero 
*/
if ($total_rs_control !=0)
{
	
	/**
	* Control para la informacion del systema 
	*/
	$row_rs_system =  $obBD_con1->getRowConsulta(15, '', $obBD_conexion);		
	/**
	* Destruye una sesión anterior 
	*/
    session_destroy();
	/**
	* Inicia la sessión 
	*/
	session_start();
	/**
	* Variables de Sesion del usuario 
	*/
	/**
	* Variable para definir la sucursal y empresa
	*/
	/**
	* Variables del Perfil del usuario 
	*/	
	/**
	* Variables de la persona 
	*/
	/**
	* Variables para la informacion del sistema 
	*/
	/**
	 * Variable de sesion de auditoria
	 */
	/**
	* Variable para la base de datos del sistema local
	*/
	
	/**
	* Consulta los perfiles asignados al usuario 
	*/
	$rs_perfiles = $obBD_con1->getArrayConsulta(21, $row_rs_control["Usu_Cod"], $obBD_conexion); 	
	/**
	* Consulta de la url de la foto
	*/
	$row_rs_foto = $obBD_con1->getRowConsulta(1, $row_rs_control["Prs_Cod"].'*'.$Emp_Cod, $obBD_conexion);
	
	/**
	* Asignación de los permisos de usuario 
	*/
	foreach($rs_perfiles as $v0)
	{
		$lperf[]=$v0["Per_Cod"];
		$Per_Des[]=$v0["Per_Des"];
	}	
	/**
	* Asignacion de datos en las variables de sessión 
	*/
	$hp2_="o";
	$_SESSION['Ses_Usu_Cod']=$row_rs_control['Usu_Cod']; 	
	$_SESSION['Ses_Usu_Ced']=$row_rs_control['Usu_Ced'];
	$_SESSION['Ses_Usu_Est']=$row_rs_control['Usu_Est'];
	$_SESSION['Ses_Usu_Cad']=$row_rs_control['Usu_Cad'];
	$_SESSION['Ses_Usu_Men']=$row_rs_control['Usu_Men'];	
	
	$_SESSION['Ses_Suc_Cod']=$row_rs_control['Suc_Cod'];
	$_SESSION['Ses_Suc_Nom']=$row_rs_control['Suc_Des'];	
	$_SESSION['Ses_Emp_Cod']=$row_rs_control['Emp_Cod'];
	$_SESSION['Ses_Emp_Nom']=$row_rs_control['Emp_Nom'];
	$_SESSION['Ses_Emp_Cor']=$row_rs_control['Emp_Cor'];	
	$_SESSION['Ses_Suc_Web']=$row_rs_control['Suc_Web'];	
	$_SESSION['Ses_Emp_Log']=$row_rs_control['Emp_Log'];		

	$_SESSION['Ses_Lis_Per']=$lperf;
	$_SESSION['Ses_Per_Des']=$Per_Des; //Descripción del perfil
	
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
	* Datos del sistema 
	*/
	$_SESSION['Ses_Sys_Sitio']=$row_rs_system['Sys_Nom'];//Nombre del sitio 
	$_SESSION['Ses_Sys_Nom']=$row_rs_system['Sys_Nom']." [".$row_rs_system['Sys_Des']."]";//Nombre del sistema
	$_SESSION['Ses_Sys_Ver']=$row_rs_system['Sys_Ver'];//Version del sistema
	$_SESSION['Ses_Sys_Tim']=date("Y-m-d H:i:s");
	$_SESSION['Ses_Sys_Dat']=date("Y-m-d");
	$_SESSION['Ses_Sys_Sit']="inside"; //Indica si esta dentro o fuera del sitio
	$_SESSION['Ses_Sys_Cor']=$row_rs_system['Sys_Cor'];	//Correo del sistema
	/**
	* Datos de las base de datos
	*/
	$_SESSION['Ses_Dat_Dis'] = $row_data['Dat_Dis']; //Base de datos distribuida local
	$_SESSION['Ses_Dat_Aut'] = $row_data['Dat_Aut']; //Base de datos auditoria
	$_SESSION['Ses_Dat_Stg'] = $row_data['Dat_Stg']; //Base de datos storage
	/**
	* Auditoria
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
	* Verificación para saber si el usuario cambio de clave 
	*/
	if (trim($password)==trim($_SESSION['Ses_Usu_Ced']))
	{
		$_SESSION['Ses_Sys_Sit']="outside"; //Indica si esta dentro o fuera del sitio
		header ("Location: ../FRONT/adm_pas_usuarios_1.0.php");		
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
		if (trim($pagina) != "")
		{
			header ("Location: ".$pagina);	
		}
		else
		{
			echo "ok";
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
	
	if($Browser =="WML"){
		header("Location: ../../movil/FRONT/index_m.php?errorusuario=si");
	}
	else{
		header("Location: ../../index.php?errorusuario=si");
	}
}//Fin del if ($total_rs_control !=0 ){
?>