<?php
require_once "./nuSoap/nusoap.php";

function getRide($file, $logo='',$ext='') {
    try{
        $ruta=dirname(__file__)."/"; 
        $xml=base64_decode($file);		
        $logo=$ruta."prueba.pdf";
		if(empty($file) || empty($xml)) throw new Exception('El Pdf recibido esta vacio!');     
		
		$fp = fopen($ruta."vergas.pdf", 'w+');
		fwrite($fp,$file); 
		fclose($fp);
		chmod($ruta."vergas.pdf", 0777);
		
		$fp = fopen($ruta."vergas.txt", 'w+');
		fwrite($fp,"vergas"); 
		fclose($fp);
		chmod($ruta."vergas.txt", 0777);
		
        $exist=file_exists($logo);                   
		$fp = fopen($logo, 'w+');
		fwrite($fp,$xml); 
		fclose($fp);
		if(!$exist) chmod($logo, 0777);
		if(!file_exists($logo)) throw new Exception('No se guardo el pdf!');    
        
        return array('success'=>true,'message'=>$file,'pdf'=>$logo);         
    }catch(Exception $e){ return array('success'=>false,'message'=>$e->getMessage(),'pdf'=>""); }
    return array('success'=>false,'message'=>'Error Creando XadesBes Bridge','pdf'=>'');
}      
$server = new soap_server();
$server->configureWSDL("ridesri", "urn:ridesri");
$server->wsdl->addComplexType(
    'Result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'success' => array('name'=>'success','type'=>'xsd:boolean'),
        'message' => array('name'=>'message','type'=>'xsd:string'),
		'pdf' => array('name'=>'pdf','type'=>'xsd:string')
    )
); 
$server->register("getRide",
    array("file" => "xsd:string", "logo" => "xsd:string", "ext" => "xsd:string",),
    array("return" => "tns:Result"),
    "urn:ridesri",
    "urn:ridesri#getRide",
    "rpc",
    "encoded",
    "Nos da un pdf desde un xml.");  
    
if ( !isset( $HTTP_RAW_POST_DATA ) ) $HTTP_RAW_POST_DATA =file_get_contents( 'php://input' );
$server->service($HTTP_RAW_POST_DATA);