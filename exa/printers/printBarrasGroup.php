<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, origin");
$done=array();
try{
    include("./printerTemplate.php"); 
    include("./printers.php"); 
    $datos=$_POST;
    if(empty($_POST) && !isset($_POST['imprimir']) && empty($_POST['imprimir']) ){
        throw new Exception("No se especifico la Impresora!");
    }
    $arrayImpresion=array();   
    foreach($_POST['imprimir'] AS $i=>$impresora){
        $arrayImpresion[$impresora['printer']]=array();
        foreach($impresora['listado'] AS $datos){
            $producto=($datos['label']=='larga'?$datos['producto']:$datos['corta']);
            for($i=0;$i<$datos['cantidad'];$i++){            
                array_push($arrayImpresion[$impresora['printer']], array('codigo'=>$datos['codigo'],'producto'=>$producto));
            }
        }
    }
              
    foreach($arrayImpresion AS $printer=>$listado){
        $content ="";  
        $total=count($listado);
        if($total>0){
            for($i=0;$i<$total;$i++){
                $content.=pageOpen(); 
                $content.= template1($listado[$i]['codigo'], $listado[$i]['producto']);

                $i++;
                if($i<$total){
                    $content.= template2($listado[$i]['codigo'], $listado[$i]['producto']);
                }
                $content.=pageClose();   
            }                 
            if($printersTCP[$printer]==null)
                require("./print1.php");
            else
                require("./print2.php");
        }
        array_push($done,$printer);
    }
    echo json_encode(array('success'=>true, "message"=>'','done'=>$done));
} catch (Exception $ex) {
    echo json_encode(array('success'=>false, "message"=>$ex->getMessage(),'done'=>$done));
} 