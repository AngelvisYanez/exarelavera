<?php 
/* 
Alias:	-
Descripción: Cerrar la sesión del sistema
Fecha de actualización:	2010-10-29
Desarrollador:	Lewis Chimarro 
*/

session_start();
/*if(isset($_SESSION['Ses_Prs_Cod']))
{*/
	session_unset();
	session_destroy();
	header('Location: ../../index.php');
//}//Fin del if(isset($_SESSION['Ses_Prs_Cod']))
?>
 
 
 

