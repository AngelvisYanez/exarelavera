<?Php 
/* 
Alias:	-
Descripción: Permite dar la respectiva seguridad al ginus
Fecha de actualización:	2010-10-27
Desarrollador:	Lewis Chimarro 
*/
require_once(__DIR__.'/../../DATA/GestorErrores.php');
require_once(__DIR__.'/../../Librerias/config.php/register_globals.php'); 
require_once($APP_REAL_PATH.'/administrador/LOGICA/logica.php');
require_once($APP_REAL_PATH.'/Librerias/procedimientos/almacenados_standar.php');

//session_start();
$URI_SETER=(isset($REQUEST_URI)?$REQUEST_URI:(isset($HTTP_SERVER_VARS['REQUEST_URI'])?$HTTP_SERVER_VARS['REQUEST_URI']:''));
$URL = explode( "/", $URI_SETER);

if (isset($URL[3])){
	$fcon=$URL[3];
	if (isset($URL[4])) $fcon=$URL[4];
	$ar_url=str_split($fcon);
	if (in_array("?",$ar_url)){
        $URL2 = explode("?",$fcon);
        $fcon=$URL2[0];
    }	
    
    if(isset($_SESSION)&&isset($_SESSION['Ses_Lis_Per'])){
        ///Recorrido de la variable de sesion de los perfiles de usuario
        $mperf='';
        foreach($_SESSION['Ses_Lis_Per'] as $item){
            $mperf=$mperf." "."perfiorgan.Per_Cod=".$item." OR";
        }
        /* Creacion del Objeto de conexion */
        $obBD_conexion = new Class_Log_Conexion_Adm($Ses_Dat_Dis);
        /* Cracion del objeto mysql para las consultas */
        $obBD_con1 =  new Class_Log_Datos_Adm; 	  
         
        /* Consulta que realiza la autenticacion del usuario */
        $rs_procesos = $obBD_con1->consulta(sentencias_adm(19, $obBD_con1->parametros(trim(substr($mperf,1,strlen($mperf)-3)).'*'.trim($fcon))), $obBD_conexion->conexion);
        $total_rs_procesos = $obBD_con1->num_rows($rs_procesos);
        $obBD_con1->liberar(); $obBD_conexion->cerrar(); //unset($obBD_conexion); unset($obBD_con1);
    }else $total_rs_procesos=0;
	/* Si es igual a cero, entonces no accede a la pagina */			
    if ($total_rs_procesos==0){         
        $arc=$APP_REAL_PATH.'/administrador/FRONT/forbidden.html';
        if(file_exists($arc)){
            header('HTTP/1.0 403 Forbidden');
            $fp = fopen($arc,'r');
            echo fread($fp, filesize($arc));
            fclose($fp);            
        }else header("Location: ../../administrador/FRONT/forbidden.html");
        exit();
    }
}//Fin del if ($hp2 != "o")