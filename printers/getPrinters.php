<?php 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, origin");
include("./printers.php");
//echo json_encode(array('success'=>false,'printers'=>array()));
echo json_encode(array('success'=>true,'printers'=>$printers));