<?php
require_once(dirname(__file__)."/libs/nuSoap/nusoap.php");

function getRide($file, $logo='',$ext='') {
    try{
        $ruta=dirname(__file__)."/"; 
		$logo_path=null;
		if(!empty($logo)&&!empty($ext)){
			$rand=mt_rand(10000000, 99999999);
			$logo_path=$ruta."log_$rand.$ext";
			$fp = fopen($logo_path, 'wb');
			fwrite($fp,base64_decode( $logo ) ); 
			fclose($fp);			
			//chmod($logo_path, 0777);
		}
        $xml=base64_decode($file);
		
        //if(empty(trim($xml))) throw new Exception('El XML recibido esta vacio!');       
        if(!mb_detect_encoding($xml, 'UTF-8', true)) throw new Exception('El XML debe estar en formato UTF-8!.');
        
		require_once(dirname(__file__)."/libs/RideSRI.php");
		$ride = new RideSRI();
	    $result = $ride->createRide($xml, $logo_path, 'S', false); // Instancio la clase, y muestro el ride
		if($logo_path!=null) unlink($logo_path);
        return array('success'=>true,'message'=>'','pdf'=>base64_encode($result));         
    }catch(Exception $e){ return array('success'=>false,'message'=>$e->getMessage(),'pdf'=>''); }
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