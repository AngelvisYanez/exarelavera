<?php 
/** 
* Copyright (c)2018 - EN Systems Apps
* @abstract Usa las clases de phpmailer
* @author Erik Niebla
* @mail ep_niebla@hotmail.com, ep.niebla@gmail.com
* @version 1.0
* Fecha de creación  2018-02-27
* http://ensystems.ddns.net
*/
use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__FILE__)."/PHPMailer/PHPMailer.php"; // Libreria para envio de mails
require_once dirname(__FILE__)."/PHPMailer/SMTP.php"; // Libreria para envio de mails
require_once dirname(__FILE__)."/PHPMailer/Exception.php"; // Libreria para envio de mails
require_once dirname(__FILE__)."/RideSRI.php";

class MailNotification
{ 	
	private $data=array(
		'port'=> 465,
		'host'=> "smtp.gmail.com",
        'user'=> "exa.facturacion@gmail.com",
        'pass'=> "p.123456**",
        'from'=> "exa.facturacion@gmail.com",
		'from_name'     => "OFSERCONT",
		'template_mail' => "mail.html",
		'file_autorized'=> null
    );	
	private $array_meses =array ("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
	private $mail_templates = array(
		'01' => null,
		'04' => null,
		'05' => null,
		'06' => null,
		'07' => null
	);
	private $type = array(
		'01' => 'Factura',
		'04' => 'Nota de Credito',
		'05' => 'Nota de Debito',
		'06' => 'Guia de Remision',
		'07' => 'Comprobante de Retencion'
	);	
	
	public function __construct(array $config = array()){
		if(isset($config['user'])&&!isset($config['from'])) $config['from']=$config['user'];
		$this->setConfig($config);        
    }
	public function setConfig($config=array()){
		 $this->data=array_merge($this->data,$config);
	}	
	public function sendMailDoc($file_autorized,$destinatarios,$copia=null,$logo=null,$online=false){  
		if(empty($file_autorized)||!is_readable($file_autorized)) return array('success'=>false,'message'=>'No se especifico la ruta del archivo autorizado!','type'=>'setter');
		if(empty($destinatarios)) return array('success'=>false,'message'=>'Debe especificar al menos un destinatario!','type'=>'setter');		
		
		$comprobante = $this->setXmlAut($file_autorized, $online);
		if($comprobante['success']==false)  return array('success'=>false,'message'=>'Error al leex el XML! '.$comprobante['message'],'type'=>'setter');
		
		$temp_path=dirname(__FILE__).'/../temp/';
		$file_ride="{$temp_path}{$comprobante['claveAcceso']}.pdf";
		$file_xml_aux=null;
		
        try{          
			$ride=new RideSRI();
			$pdf = $ride->createRide($file_autorized, $logo, false, $online);
			$pdf->Output($file_ride, 'F');			
			
            $mail = new PHPMailer(true); // Crear una nueva  instancia de PHPMailer habilitando el tratamiento de excepciones
			$mail->SMTPDebug = 2;
			// Configuración del servidor SMTP			
            $mail->Port = $this->data['port'];
            $mail->Host = $this->data['host'];
            $mail->Username = $this->data['user'];
            $mail->Password = $this->data['pass'];
            // Configuramos el protocolo SMTP con autenticación
			$mail->IsSMTP();
			$mail->SMTPAuth = true;
            $mail->SMTPSecure = 'ssl';
			//$mail->SMTPAutoTLS = false; 
            
            // Configuración cabeceras del mensaje
			$mail->IsHTML(true);
			$mail->Subject = "Comprobante Electrónico";
			$this->utf8_change_param($mail->Subject,true);
            $mail->From = $this->data['from'];
            $mail->FromName = $this->data['from_name'];			
			$mail->AddAddress(trim($destinatarios['to']),strtoupper($destinatarios['to_name']));	
			if(!empty($copia)) $mail->AddCC(trim($copia['to']),strtoupper($copia['to_name']));                        
            // Creamos en una variable el cuerpo, contenido HMTL, del correo //$body  = "Proebando los correos con un tutorial<br>";
			$body = $this->reporteHtml(array_merge($comprobante['mail'],$destinatarios),dirname(__FILE__).'/templates/'.(empty($comprobante['mail_template'])?$this->data['template_mail']:$comprobante['mail_template']));            
            $mail->Body = $body;
			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            // Ficheros adjuntos
			$mail->AddAttachment($file_ride, "$comprobante[claveAcceso].pdf");
			if($comprobante['autorized'])
				$mail->AddAttachment($file_autorized, "$comprobante[claveAcceso].xml");
			else if($online==false){
				// esta parte esta comentada xq no creo que se deba enviar el xml si no esta autorizado en realidad, o talvez si, no le he preguntado al sri
				/*$file_xml_aux="{$temp_path}{$comprobante['claveAcceso']}.xml";
				$this->createXmlAutorizedTemporal($comprobante['claveAcceso'], $file_autorized, $file_xml_aux);
				$mail->AddAttachment($file_xml_aux, "$comprobante[claveAcceso]_temp.xml");*/
			}
            $mail->Send(); // Enviar el correo
			unset( $mail );
			unlink( $file_ride ); // elimino el pdf xq no lo necesito y ocupa espacio en disco
			if(!empty( $file_xml_aux )) unlink( $file_xml_aux );  // elimino el xml auxiliar del documento no autorizado, cuando lo autorice obtengo el real
        }catch(Exception $e) { 
			return array('success'=>false,'message'=>$e->getMessage(),'type'=>'error'); 
		}
        return array('success'=>true,'message'=>'Se envio el mail correctamente!','type'=>'application'); ;
    }
	private function setXmlAut($file_autorized){
        if(!empty($file_autorized)){
			$this->data['file_autorized']=$file_autorized;
			$aux=RideSRI::getXmlArray($this->data['file_autorized']);
			if($aux['success']==true){
				$clave= $aux['claveAcceso'];
				$doc  = $aux['comprobante'];
				$this->data['from_name']= $doc['infoTributaria']['razonSocial'];
				$aux['mail']=array_merge($doc['infoTributaria'],array(
					'Documento'=>$this->type[substr($clave,8,2)],
					'Fecha'=>substr($clave,0,2).' de '.$this->array_meses[(substr($clave,2,2)*1)-1].' de '.substr($clave,4,4),
					'destinatario_doc'=>(isset($doc['infoFactura'])?$doc['infoFactura']['identificacionComprador']:
						(isset($doc['infoCompRetencion'])?$doc['infoCompRetencion']['identificacionSujetoRetenido']:
						(isset($doc['infoGuiaRemision'])?$doc['infoGuiaRemision']['rucTransportista']:
						(isset($doc['infoNotaCredito'])?$doc['infoNotaCredito']['identificacionComprador']:
						(isset($doc['infoNotaDebito'])?$doc['infoNotaDebito']['identificacionComprador']:''
						)))))
				));
				$aux['mail_template']=$this->mail_templates[$aux['documento']];
			}
			return $aux;
		}
    }
	private function reporteHtml($params,$templatePath){
		if (!is_file($templatePath)){ throw new Exception('No se ha encontrado la plantilla!'); }
		$templateTxt = file_get_contents($templatePath);
			$this->utf8_change_param($templateTxt,true);
		foreach ($params as $key => $value) $templateTxt = str_replace('{'.$key.'}', $value, $templateTxt); 
			$buffer=preg_replace('/{(.+)}/', '', $templateTxt);
			return $buffer;
    }
	private function utf8_change_param(&$input,$type=false){ /* agregado por erik para limpieza de caracteres especiales */
        if (is_string($input)) { 
            $inputs = trim($input); if((!!mb_detect_encoding($input, 'UTF-8', true))==$type) $input=call_user_func($type?'utf8_decode':'utf8_encode',$input);
        } else if (is_array($input)) {
            foreach ($input as &$value) { $this->utf8_change_param($value, $type); } unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var) { $this->utf8_change_param($input->$var, $type); }
        }
    }
	private  function createXmlAutorizedTemporal($claveAcceso, $file_xml, $file_xml_aux=null){
		$estado='PENDIENTE';
		$xml= file_get_contents($file_xml);
		
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;   
		$autorizacion=$doc->createElement('autorizacion'); 
		$estado=$doc->createElement('estado', $estado); 
		$numeroAutorizacion=$doc->createElement('numeroAutorizacion', $claveAcceso); 
		$fechaAutorizacion=$doc->createElement('fechaAutorizacion', '0001-01-01T00:00:01-00:01');        
		$cdata = $doc->createCDATASection((!mb_detect_encoding($xml, 'UTF-8', true))?utf8_encode($xml):$xml);            
		$comprobante=$doc->createElement('comprobante');
		$comprobante->appendChild($cdata);

		$fechaAutorizacion->setAttribute('class','fechaAutorizacion');
		$autorizacion->appendChild($estado);
		$autorizacion->appendChild($numeroAutorizacion);
		$autorizacion->appendChild($fechaAutorizacion);
		$autorizacion->appendChild($comprobante);
		$doc->appendChild($autorizacion);		
		if(!empty($file_xml_aux)) $doc->save($file_xml_aux);
		return $doc->saveXML();
	}
}