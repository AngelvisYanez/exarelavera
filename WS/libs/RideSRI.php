<?php 
/** 
* Copyright (c)2018 - EN Systems Apps
* @abstract Lee los xml y envia a crear los pdfs
* @author Erik Niebla
* @mail ep_niebla@hotmail.com, ep.niebla@gmail.com
* @version 1.0
* Fecha de creación  2018-02-27
* http://ensystems.ddns.net
*/
require_once dirname(__FILE__)."/RideGlobal.php"; // Libreria para el pdf
require_once dirname(__FILE__)."/xml/XML2Array.php"; // convierte el xml a un array

class RideSRI
{ 
	public function createRide($file_xml, $logo_path=null, $option='I', $online=true)
	{
		$doc=$this->getXmlArray($file_xml, $online);
		if($doc['success']==false) throw new Exception($doc['message']);
		if($doc['autorized']==false) $doc['online']=$online;		
		$pdf = new RideGlobal();		
		return $pdf->createPdf($doc, $logo_path, $option, $online);		
	}
	public static function getXmlArray($file_xml,$online=true)
    {	
		$result=array('success'=>true, 'autorized'=>false, 'online'=>true, 'comprobante'=>null, 'claveAcceso'=>null, 'numeroAutorizacion'=>'PENDIENTE', 'fechaAutorizacion'=>'PENDIENTE');
		try{			
			if(!empty($file_xml)&&!is_readable($file_xml)){
				libxml_use_internal_errors(true);
				$sxe = simplexml_load_string($file_xml);
				if (!$sxe) {
					$str="";
					foreach(libxml_get_errors() as $error) {
						$str.=( "\t".$error->message);
					}
					return array('success'=>false,'message'=>'No se pudo interpretar el xml!.\n'.$str,'type'=>'setter');
				}
			}else
			if( empty($file_xml)||!is_readable($file_xml) ) return array('success'=>false,'message'=>'No se especifico la ruta del archivo xmlss!','type'=>'setter');
			
			$xml=(is_readable($file_xml))?file_get_contents($file_xml):$file_xml;		
			$sri = XML2Array::createArray(((!mb_detect_encoding($xml, 'UTF-8', true))?utf8_encode($xml):$xml));	
			
			if(isset($sri['autorizacion'])){
				$result['autorized'] = true;
				$comp = XML2Array::createArray($sri['autorizacion']['comprobante']["@cdata"]);
				$result['comprobante']=$comp[key($comp)];				
				$result['numeroAutorizacion']= $sri['autorizacion']['numeroAutorizacion'];
				if(!empty($sri['autorizacion']['fechaAutorizacion']["@value"])) 
					$result['fechaAutorizacion'] = substr($sri['autorizacion']['fechaAutorizacion']["@value"],0,10).'  '.substr($sri['autorizacion']['fechaAutorizacion']["@value"],11,8);
				else
					$result['fechaAutorizacion'] = substr($sri['autorizacion']['fechaAutorizacion'],0,10).'  '.substr($sri['autorizacion']['fechaAutorizacion'],11,8);
				$result['online'] = !($result['comprobante']['infoTributaria']['claveAcceso']==$result['numeroAutorizacion']);
			}else{
				$result['comprobante']=$sri[key($sri)];
				$result['online']=$online;
				
				if($online==false){
					
					$result['numeroAutorizacion']=$result['comprobante']['infoTributaria']['claveAcceso'];
					$result['fechaAutorizacion']=null;
				}
			}			
			$result['claveAcceso']=$result['comprobante']['infoTributaria']['claveAcceso'];
			$result['documento']=substr($result['claveAcceso'],8,2);
			$result['version']=$result['comprobante']['@attributes']['version'];			
		}catch (Exception $e) { 
			return array('success'=>false,'message'=>$e->getMessage(),'type'=>'application');			
		} 
		return $result;
    }	
}