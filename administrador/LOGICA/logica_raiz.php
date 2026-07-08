<?php
require_once ("DATA/DAC.php");
require_once("sql.php");

/*********************MYSQL****************************/
/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_Adm extends Class_Mysql{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_Adm extends Class_Datos{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/


/* Funcion que devuelve un arreglo de los reportes del proceso */
function reportes($pagina, $obBD_con1, $obBD_conexion)
{
	$pag = explode("/", $pagina);
	$rs_proceso = $obBD_con1->consulta(sentencias_adm(12, $obBD_con1->parametros($pag[count($pag)-1])), 
							$obBD_conexion->conexion);
	$row_rs_proceso = $obBD_con1->registros();
	$total_rs_proceso = $obBD_con1->numregistros();
		
	$rs_reporte = $obBD_con1->consulta(sentencias_adm(13, $obBD_con1->parametros($row_rs_proceso['Pcs_Cod'])), 
							$obBD_conexion->conexion);
	$row_rs_reporte = $obBD_con1->registros();
	$total_rs_reporte = $obBD_con1->numregistros();
	
	$i=0;
	do{
		$i++;
		$reporte[$i] = $row_rs_reporte['Pcs_Nom'];		
	}while($row_rs_reporte = $obBD_con1->registros());
	
	return $reporte;
}//Fin del function reportes($pagina, $obBD_con1, $obBD_conexion)

?>