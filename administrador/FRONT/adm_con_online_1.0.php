<?Php
/* 
Alias:	-
Descripción: Consulta los usuarios online
Fecha de actualización:	2011-05-23
Desarrollador:	Lewis Chimarro 
*/

require_once('../LOGICA/logica.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);

/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm; 	  

	/* Actualiza la fecha de conexion del usuario */
	$obBD_con1->grabarv_registros(sentencias_adm(38, $obBD_con1->parametros($time_online.'*'.$Ses_Usu_Cod)),$obBD_conexion->conexion);	

	// Seteo el tiempo en segundos de hace 5 minutos 
	$time_pasado=time()-50; 

	/* Establezco que la consulta me seleccione a aquellos usuarios cuyo  
	"tiempo de ultimo clic" sea mayor o igual al $tiempo_pasado (5 minutos) */ 
	$rs_online = $obBD_con1->consulta(sentencias_adm(39, $obBD_con1->parametros($time_pasado.'*'.$_SESSION['Ses_Usu_Cod'].'*'.$_SESSION['Ses_Emp_Cod'])), $obBD_conexion->conexion);
	//print_r($row_rs_online);
	$row_rs_online = $obBD_con1->registros();
	$total_rs_online = $obBD_con1->numregistros(); ?>
    
    <table width="100%" border="0" cellspacing="0" cellpadding="0">      
        <tr class="letra_index">
            <td class="banner_logeo" align="center" style="padding-top: 5px;padding-bottom: 5px;"><!--Chat - --> <b><?php echo $total_rs_online; ?></b>&nbsp; Usuario(s) Online</td>
      </tr> 
    </table>
    <div >
    <table width="100%" border="0" cellpadding="0" cellspacing="0">     
<?php	
if ($total_rs_online > 0)
{
	do{
		// Mostramos los nombres de esos usuarios (los activos) 
		$apellido = explode(' ', $row_rs_online['Prs_Ape']);
		$nombre = explode(' ', $row_rs_online['Prs_Nom']);
		
		$chat_username = mb_convert_encoding(ucfirst($nombre[0])."-".ucfirst($apellido[0]), 'UTF-8', 'ISO-8859-1'); 
		$chat_username2 = mb_convert_encoding(ucfirst($nombre[0])." ".ucfirst($apellido[0]), 'UTF-8', 'ISO-8859-1');		
		?>  
		  <tr><td>
				<a href="javascript:void(0)" style="color:#000;font-size:12px;padding-left:5px;" onClick="javascript:chatWith('<?Php echo $chat_username; ?>')"><img src="../../mascaras/model1/imagenes/32x32/user_green.png" width="14" height="14" style="border:none;"><?php echo " ".$chat_username2; ?></a>
                  </td> </tr>
        <?php
		unset($nombre);
		unset($apellido);
	}while($row_rs_online=$obBD_con1->fetch_assoc($rs_online)); 
}//if ($total_rs_online > 0)	
	?>
	</table>   
    </div>             
	<?php

/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>