<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../administrador/LOGICA/seguridad.php');
// $Ses_Dat_Dis is extracted from $_SESSION by register_globals.php
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$res = $obBD_con1->getArrayConsultaSql('DESCRIBE adq_solicitudes_det', $obBD_conexion);
echo json_encode($res);
