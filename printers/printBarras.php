<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, origin");
header('Content-Type: application/json; charset=utf-8');
try{
    include("./printerTemplate.php"); 
    include("./printers.php");
    $datos=$_POST;
    if(!isset($datos["printer"])){
        throw new Exception("No se especifico la Impresora!");
    }
    if(!isset($datos["codigo"]) || empty($datos["codigo"]) ){
        throw new Exception("No se especifico el codigo!");
    }
    if(!isset($datos["cantidad"]) || $datos["cantidad"]*1<=0){
        throw new Exception("La cantidad no es correcta!");
    }
    $content ="";
    $printer = ($datos["printer"]);
    $producto=($datos['label']=='larga'?$datos['producto']:$datos['corta']);
    for($i=0;$i<$datos['cantidad'];$i++){
        $content.=pageOpen();  
        $content.= template1($datos['codigo'], $producto);
        
        $i++;
        if($i<$datos['cantidad']){
            $content.= template2($datos['codigo'], $producto);
        }
        $content.=pageClose();   
    }   
    
    if($printersTCP[$printer]==null)
        require("./print1.php");
    else
        require("./print2.php");
    echo json_encode(array('success'=>true, "message"=>''));
} catch (Exception $ex) {
    echo json_encode(array('success'=>false, "message"=>$ex->getMessage()));
} 