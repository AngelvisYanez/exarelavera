<?Php
/* 
Alias:	-
Descripción: Consulta los usuarios online
Fecha de actualización:	2015-11-27
Desarrollador:	Erik Niebla
*/

require_once('../LOGICA/logica.php');
if(!isset($_SESSION)||!isset($_SESSION['Ses_Dat_Dis'])){ echo json_encode(array()); exit();  }
if(!isset($soloUsers)) $soloUsers=false;
// Seteo el tiempo en segundos de hace 5 minutos
$time_pasado6=time()-60;  $time_pasado3=time()-30;
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm; 	  

/* Actualiza la fecha de conexion del usuario */
$obBD_con1->operacionobBD(38,'*'.$Ses_Usu_Cod, $obBD_conexion);

/* Establezco que la consulta me seleccione a aquellos usuarios cuyo  
"tiempo de ultimo clic" sea mayor o igual al $tiempo_pasado (5 minutos) */        
$Usuarios=array();
$Mensajes=array();
$Senales=array();
$OnlineCount=0;
$rs_online =  $obBD_con1->getArrayConsulta(39,$time_pasado6.'*'.$_SESSION['Ses_Prs_Cod'].'*'.$_SESSION['Ses_Emp_Cod'], $obBD_conexion);
        
if (count($rs_online) > 0)
{
    foreach ($rs_online as $row){
                 // Mostramos los nombres de esos usuarios (los activos) 
		$apellido = explode(' ', $row['Prs_Ape']);
		$nombre = explode(' ', $row['Prs_Nom']);
                
                $user=array(
                    'Id'=>$row['Prs_Cod'],//$row['Usu_Cod'],
                    'RoomId'=>'1',//$Ses_Emp_Cod,
                    'Name'=> mb_convert_encoding(ucfirst($nombre[0])." ".ucfirst($apellido[0]), 'UTF-8', 'ISO-8859-1'),
                    'Email'=> $row['Prs_Cor'],
                    'ProfilePictureUrl'=> ($row['Usu_Cox']*1>=$time_pasado3?'../../framework/jquery/ChatJs/images/userGreen.png':'../../framework/jquery/ChatJs/images/userGrey.png'),
                    'Status'=>($row['Usu_Cox']*1>=$time_pasado3?'1':'0')
                );
                array_push($Usuarios,$user);
                if($row['Usu_Cox']*1>=$time_pasado3) $OnlineCount++;
		unset($nombre);unset($apellido);unset($user);
    }
}//if ($total_rs_online > 0)	
if(!$soloUsers){
    $mes_online =  $obBD_con1->getArrayConsulta(217,$_SESSION['Ses_Prs_Cod'].'*'.$_SESSION['Ses_Emp_Cod'], $obBD_conexion);


    if (count($mes_online) > 0)
    {   
         foreach ($mes_online as $row){
                array_push($Mensajes,$row);
                $obBD_con1->operacionobBD(218,$row, $obBD_conexion);
         }

    }

    $sen_online = $obBD_con1->getArrayConsulta(221,$_SESSION['Ses_Prs_Cod'].'*'.$_SESSION['Ses_Emp_Cod'], $obBD_conexion);


    //die(var_dump($row_sen_online));
    if (count($sen_online) > 0)
    {
        foreach ($sen_online as $row){
            array_push($Senales,$row);
            $obBD_con1->operacionobBD(220,$row, $obBD_conexion);
        }    
    }
}

$response['room']=array('Id'=>$Ses_Emp_Cod,'Name'=>$Ses_Emp_Nom,'UsersOnline'=>$OnlineCount);
$response['success']=true;
$response['users']=$Usuarios;
$response['messages']=$Mensajes;
$response['signals']=$Senales;
//unset($response);
//echo json_encode($response);

/* cierro las conexiones */
//$obBD_con1->liberar();
//$obBD_conexion->cerrar();
/* fin cierre las conexiones */
