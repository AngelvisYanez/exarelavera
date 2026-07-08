<?php
require_once dirname(__FILE__).'/XmlSecurity/DSig.php';
require_once dirname(__FILE__).'/XmlSecurity/Key.php';

class FirmaElectronica
{
    protected $ws;
    protected $signer="http://ws.ofsercont.com/WS/firmaXadesBes.wsdl";

    protected $development=array(
        'recept'=>"https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl",
        'autori'=>"https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl"
    );
    protected $production=array(
        'recept'=>"https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl",
        'autori'=>"https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl"
    );
    protected $key=null;
    protected $data=array(
        'key_p12'=>null,
        'pass_key'=>null,
        'file_to_sign'=>null,
        'file_signed'=>null,
        'file_to_send'=>null,
        'file_autorized'=>null
    );

    protected $soapTimeout = 60;
    protected $soapResponseTimeout = 120;
    protected $maxRetries = 3;
    protected $retryDelay = 2;

    public function __construct(array $config = array()){
        $this->data=array_merge($this->data,$config);
        $this->setProduction(true);
    }

    public function setProduction($pr){
        $this->ws=($pr==true)?$this->production:$this->development;
        $this->ws['signer']=$this->signer;
    }

    public function setFileToSignPath($file_to_sign) {
        if(!empty($file_to_sign)) $this->data['file_to_sign']=$file_to_sign;
    }
    public function setFileSignedPath($file_signed){
        if(!empty($file_signed)) $this->data['file_signed']=$file_signed;
    }
    public function setFileAutorized($file_autorized){
        if(!empty($file_autorized)) $this->data['file_autorized']=$file_autorized;
    }

    public function isKeyActive(){ return empty($this->key)?false:$this->key->active; }

    public function setKey($file_key,$pass, $echo=false){
        try {
            if(!empty($file_key)&&!empty($pass)&&is_readable($file_key)){
                $this->key = Key::factory(Key::RSA_SHA1, $file_key, true, Key::TYPE_PRIVATE, $pass);
                if(!$this->key->active){ $this->key=null; return false; }
                return true;
            } return false;
        } catch (Exception $e) {
            if($echo) echo 'Excepción Capturada: ',  $e->getMessage(), "\n";
            return false;
        }
    }

	public function getKeyData($file_key="", $pass="", $echo=false){
		try {
			if(!empty($file_key)&&!empty($pass)&&is_readable($file_key)){
				$this->setKey($file_key , $pass, $echo=false);
			}
			if(!$this->key->active) return 2;

			return $this->key->getDataKey();
		} catch (Exception $e) {
            if($echo) echo 'Excepción Capturada: ',  $e->getMessage(), "\n";

        }
		return 1;
	}

    public function signXml($config = array(), $echo=false){
        try {
            if(!empty($config) && is_array($config)) $this->data = array_merge($this->data,$config);

            if( (empty($this->data['file_to_sign']) || !is_readable($this->data['file_to_sign']) ) || ( empty($this->key) && !$this->setKey($this->data['key_p12'],$this->data['pass_key']) ) )
                return false;

            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->formatOutput = false;
            $xml = file_get_contents($this->data['file_to_sign']);
            if(!mb_detect_encoding($xml, 'UTF-8', true)) $xml=mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1');
            $doc->loadXml($xml);
            $signature=DSig::createSignatureP12($this->key, DSig::C14N, $doc->documentElement);
            if(!empty($this->data['file_signed'])){
                $doc->save($this->data['file_signed']);
                $this->data['file_to_send']=$this->data['file_signed'];
            }
            return $doc;
        } catch (Exception $e) {
            if($echo) echo 'Excepción Capturada: ',  $e->getMessage(), "\n";
            return false;
        }
    }

    protected function logSri($message, $data = null) {
        $logDir = dirname(__DIR__) . '/../logs/sri';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/sri_' . date('Y-m-d') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($data !== null) {
            $line .= ' ' . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
    }

    protected function createSoapClient($wsdlUrl) {
        $client = new nusoap_client($wsdlUrl, true, false, false, false, false, $this->soapTimeout, $this->soapResponseTimeout);
        $error = $client->getError();
        if ($error) {
            $this->logSri("SOAP constructor error [$wsdlUrl]", $error);
            return array('error' => $error);
        }
        return $client;
    }

    public function sendToSign($xml_path,$key_path,$pass){
        try {
            if(empty($xml_path)||!is_readable($xml_path)) return array('success'=>false,'message'=>'Revise la ruta del xml!','type'=>'application');
            if(empty($key_path)||!is_readable($key_path)) return array('success'=>false,'message'=>'Revise la ruta de la firma!','type'=>'application');
            if(empty($pass)) return array('success'=>false,'message'=>'La password no puede estar en blanco!','type'=>'application');
            $xml=file_get_contents($xml_path);
            $xml_64=base64_encode((!mb_detect_encoding($xml, 'UTF-8', true))?mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1'):$xml);
            $key_64=base64_encode(file_get_contents($key_path));
            $pass_64=base64_encode($pass);

            $client = $this->createSoapClient($this->ws['signer']);
            if (is_array($client) && isset($client['error'])) {
                return array('success'=>false,'message'=>$client['error'],'type'=>'constructor');
            }

            $result = $client->call("getFileSigned", array("file" => $xml_64,'key'=>$key_64, 'pass'=>$pass_64));

            if ($client->fault) {
                $this->logSri("sendToSign SOAP fault", $result);
                return array('success'=>false,'message'=>'Fault en servicio de firma','type'=>'call','data'=>$result);
            }
            $error = $client->getError();
            if($error) {
                $this->logSri("sendToSign error", $error);
                return array('success'=>false,'message'=>$error,'type'=>'response');
            }

            if(!empty($this->data['file_signed'])&&!empty($result['xml'])){
                $file=$this->data['file_signed'];
                $exist=file_exists($file);
                $fp = fopen($file, 'wb');
                fwrite($fp,(!mb_detect_encoding($result['xml'], 'UTF-8', true))?mb_convert_encoding($result['xml'], 'UTF-8', 'ISO-8859-1'):$result['xml'] ); fclose($fp);
                if(!$exist) chmod($file, 0777);
            }
            if(empty($result['xml'])) {
                $result['success']=false;
                $result['message']='El servicio de firma no devolvió el XML firmado';
            } else {
                $result['success']=true;
            }

            return $result;
        } catch (Exception $e) {
            $this->logSri("sendToSign exception", $e->getMessage());
            return array('success'=>false,'message'=>$e->getMessage(),'type'=>'application');
        }
    }

    public function sendToSri($file_signed=null, $echo=false){
        try {
            if(!empty($file_signed)) $this->data['file_signed']=$file_signed;
            if(empty($this->data['file_signed'])) return array('success'=>false,'message'=>'No se especifico la ruta del archivo firmado!','type'=>'setter');
            if(!is_readable($this->data['file_signed'])) return array('success'=>false,'message'=>'No se encontro el archivo xml!','type'=>'setter');

            $client = $this->createSoapClient($this->ws['recept']);
            if (is_array($client) && isset($client['error'])) {
                return array('success'=>false,'message'=>$client['error'],'type'=>'constructor');
            }

            $base_convert=base64_encode(file_get_contents($this->data['file_signed']));
            $result = $client->call("validarComprobante", array("xml" =>$base_convert));

            if ($client->fault) {
                $this->logSri("sendToSri SOAP fault", $result);
                return array('success'=>false,'message'=>'Fault del SRI en recepción','type'=>'call','data'=>$result);
            }
            $error = $client->getError();
            if($error) {
                $this->logSri("sendToSri error", $error);
                return array('success'=>false,'message'=>$error,'type'=>'response');
            }

            $respuesta = $result['RespuestaRecepcionComprobante'];
            if (!$respuesta) {
                $this->logSri("sendToSri respuesta inesperada", $result);
                return array('success'=>false,'message'=>'El SRI devolvió una respuesta inesperada','type'=>'response','data'=>$result);
            }

            $estado = $respuesta['estado'] ?? '';

            if ($estado === 'RECIBIDA') {
                $this->logSri("sendToSri RECIBIDA", ['claveAcceso' => $respuesta['comprobantes']['comprobante']['claveAcceso'] ?? '']);
                return array(
                    'success'=>true,
                    'message'=>'Documento recibido por el SRI correctamente',
                    'claveAcceso'=>$respuesta['comprobantes']['comprobante']['claveAcceso'] ?? '',
                    'estado'=>'RECIBIDA'
                );
            }

            $comp=$respuesta['comprobantes']['comprobante'] ?? array();
            $resp = array(
                'success'=>$estado!=='DEVUELTA',
                'message'=>'',
                'informacionAdicional'=>'',
                'type'=>'response',
                'estado'=>$estado
            );
            $resp['claveAcceso']=$comp['claveAcceso'] ?? '';

            if($resp['success']==false && isset($comp['mensajes'])){
                $msgs=$comp['mensajes'];
                if (isset($msgs['mensaje'])) $msgs = array($msgs['mensaje']);
                foreach($msgs as $msg){
                    $identificador = $msg['identificador'] ?? 0;
                    // Error 43 = ya fue recibido anteriormente
                    if($resp['success']==false && $identificador*1==43){
                        $resp['success']=true;
                        $resp['message']='El documento ya fue recibido anteriormente por el SRI';
                        continue;
                    }
                    $resp['message'].=($msg['tipo'] ?? '') . ' ' . $identificador . ': ' . ($msg['mensaje'] ?? '') . "!, ";
                    if(isset($msg['informacionAdicional'])) $resp['informacionAdicional'].=$msg['informacionAdicional'] . "!, ";
                }
                $resp['message']=substr($resp['message'], 0, -2);
                if(!empty($resp['informacionAdicional']))$resp['informacionAdicional']=substr($resp['informacionAdicional'], 0, -2);
            }

            if ($resp['success']) {
                $this->logSri("sendToSri exitoso", ['claveAcceso' => $resp['claveAcceso'], 'estado' => $estado]);
            } else {
                $this->logSri("sendToSri DEVUELTA", $resp['message']);
            }

            return $resp;
        } catch (Exception $e) {
            $this->logSri("sendToSri exception", $e->getMessage());
            return array('success'=>false,'message'=>$e->getMessage(),'type'=>'application');
        }
    }

    public function autorizarSri($claveAcceso=null,$file_autorized=null, $echo=false) {
        try {
            if(!empty($file_autorized)) $this->data['file_autorized']=$file_autorized;
            if(empty($claveAcceso)) return array('success'=>false,'message'=>'No se especifico una Clave de Acceso','type'=>'setter');

            $attempts = 0;
            $lastResult = null;

            while ($attempts < $this->maxRetries) {
                $attempts++;
                if ($attempts > 1) {
                    sleep($this->retryDelay);
                }

                $client = $this->createSoapClient($this->ws['autori']);
                if (is_array($client) && isset($client['error'])) {
                    $lastResult = array('success'=>false,'message'=>$client['error'],'type'=>'constructor');
                    continue;
                }

                $result = $client->call("autorizacionComprobante", array("claveAccesoComprobante" =>$claveAcceso));

                if ($client->fault) {
                    $this->logSri("autorizarSri SOAP fault (intento $attempts)", $result);
                    $lastResult = array('success'=>false,'message'=>'Fault del SRI en autorización','type'=>'call','data'=>$result);
                    continue;
                }
                $error = $client->getError();
                if($error) {
                    $this->logSri("autorizarSri error (intento $attempts)", $error);
                    $lastResult = array('success'=>false,'message'=>$error,'type'=>'response');
                    continue;
                }

                $respuesta = $result['RespuestaAutorizacionComprobante'] ?? array();
                $numeroComprobantes = (int)($respuesta['numeroComprobantes'] ?? 0);

                if ($numeroComprobantes === 0) {
                    $lastResult = array(
                        'success'=>false,
                        'message'=>"No se encontró la clave en el SRI, o no se encuentra autorizado (Máximo 90 días atrás)!",
                        'type'=>'response',
                        'estado'=>'NO_ENCONTRADO'
                    );
                    break;
                }

                $aut = $respuesta['autorizaciones']['autorizacion'] ?? array();
                if (isset($aut['estado'])) {
                    $aut = array($aut);
                }

                $autorizacion = $aut[0] ?? array();
                $estado = trim(strval($autorizacion['estado'] ?? ''));

                if ($estado === 'AUTORIZADO') {
                    $resp = $this->buildAutorizadoResponse($autorizacion);
                    $this->logSri("autorizarSri AUTORIZADO", ['claveAcceso' => $claveAcceso, 'numeroAutorizacion' => $autorizacion['numeroAutorizacion'] ?? '']);
                    return $resp;
                }

                if ($estado === 'EN PROCESO') {
                    $this->logSri("autorizarSri EN PROCESO (intento $attempts/$this->maxRetries)", ['claveAcceso' => $claveAcceso]);
                    $lastResult = array(
                        'success'=>false,
                        'message'=>"Documento en proceso de autorización (intento $attempts de $this->maxRetries)",
                        'type'=>'response',
                        'estado'=>'EN PROCESO',
                        'reintentar'=>($attempts < $this->maxRetries)
                    );
                    continue;
                }

                if ($estado === 'NO AUTORIZADO' || $estado === 'RECHAZADA') {
                    $msgs = $autorizacion['mensajes'] ?? array();
                    if (isset($msgs['mensaje'])) $msgs = array($msgs['mensaje']);
                    $msgTexto = '';
                    $infoAdi = '';
                    foreach($msgs as $msg){
                        $msgTexto.=($msg['tipo'] ?? '') . ' ' . ($msg['identificador'] ?? '') . ': ' . ($msg['mensaje'] ?? '') . "!, ";
                        if(isset($msg['informacionAdicional'])) $infoAdi.=$msg['informacionAdicional'] . "!, ";
                    }
                    $msgTexto = substr($msgTexto, 0, -2);
                    $infoAdi = substr($infoAdi, 0, -2);

                    $this->logSri("autorizarSri $estado", ['claveAcceso' => $claveAcceso, 'mensaje' => $msgTexto]);
                    return array(
                        'success'=>false,
                        'message'=>$msgTexto ?: "Documento $estado por el SRI",
                        'informacionAdicional'=>$infoAdi,
                        'type'=>'response',
                        'estado'=>$estado
                    );
                }

                $lastResult = array(
                    'success'=>false,
                    'message'=>"Estado desconocido del SRI: $estado",
                    'type'=>'response',
                    'estado'=>$estado
                );
                break;
            }

            if ($lastResult && !empty($lastResult['estado']) && $lastResult['estado'] === 'EN PROCESO') {
                $lastResult['message'] = 'El documento está en proceso en el SRI. Reintente en unos segundos.';
            }

            return $lastResult ?: array('success'=>false,'message'=>'Error desconocido en autorización','type'=>'application');
        } catch (Exception $e) {
            $this->logSri("autorizarSri exception", $e->getMessage());
            return array('success'=>false,'message'=>$e->getMessage(),'type'=>'application','informacionAdicional'=>'');
        }
    }

    public function consultarEstadoSri($claveAcceso) {
        try {
            if(empty($claveAcceso)) return array('success'=>false,'message'=>'No se especifico una Clave de Acceso');

            $client = $this->createSoapClient($this->ws['autori']);
            if (is_array($client) && isset($client['error'])) {
                return array('success'=>false,'message'=>$client['error']);
            }

            $result = $client->call("autorizacionComprobante", array("claveAccesoComprobante" =>$claveAcceso));

            if ($client->fault) {
                return array('success'=>false,'message'=>'Fault del SRI en consulta','data'=>$result);
            }
            $error = $client->getError();
            if($error) {
                return array('success'=>false,'message'=>$error);
            }

            $respuesta = $result['RespuestaAutorizacionComprobante'] ?? array();
            $numeroComprobantes = (int)($respuesta['numeroComprobantes'] ?? 0);

            if ($numeroComprobantes === 0) {
                return array(
                    'success'=>false,
                    'message'=>"No se encontró la clave en el SRI",
                    'estado'=>'NO_ENCONTRADO'
                );
            }

            $aut = $respuesta['autorizaciones']['autorizacion'] ?? array();
            if (isset($aut['estado'])) {
                $aut = array($aut);
            }
            $autorizacion = $aut[0] ?? array();
            $estado = trim(strval($autorizacion['estado'] ?? ''));

            $resp = array(
                'success'=> ($estado === 'AUTORIZADO'),
                'estado'=>$estado,
                'claveAcceso'=>$claveAcceso,
            );

            if ($estado === 'AUTORIZADO') {
                $resp['numeroAutorizacion'] = $autorizacion['numeroAutorizacion'] ?? '';
                $resp['fechaAutorizacion'] = $autorizacion['fechaAutorizacion'] ?? '';
                $resp['ambiente'] = $autorizacion['ambiente'] ?? '';
                if (!empty($autorizacion['comprobante'])) {
                    $resp['xmlAutorizado'] = $autorizacion['comprobante'];
                }
            } elseif ($estado === 'EN PROCESO') {
                $resp['message'] = 'Documento en proceso de autorización';
            } elseif ($estado === 'NO AUTORIZADO' || $estado === 'RECHAZADA') {
                $msgs = $autorizacion['mensajes'] ?? array();
                if (isset($msgs['mensaje'])) $msgs = array($msgs['mensaje']);
                $msgTexto = '';
                foreach($msgs as $msg){
                    $msgTexto.=($msg['tipo'] ?? '') . ' ' . ($msg['identificador'] ?? '') . ': ' . ($msg['mensaje'] ?? '') . "!, ";
                }
                $resp['message'] = substr($msgTexto, 0, -2);
            }

            $this->logSri("consultarEstadoSri", ['claveAcceso' => $claveAcceso, 'estado' => $estado]);
            return $resp;
        } catch (Exception $e) {
            $this->logSri("consultarEstadoSri exception", $e->getMessage());
            return array('success'=>false,'message'=>$e->getMessage(),'estado'=>'ERROR');
        }
    }

    protected function buildAutorizadoResponse($autorizacion) {
        $resp=array(
            'success'=>true,
            'xml'=>'',
            'message'=>'',
            'informacionAdicional'=>'',
            'estado'=>'AUTORIZADO',
            'numeroAutorizacion'=>$autorizacion['numeroAutorizacion'] ?? '',
            'fechaAutorizacion'=>$autorizacion['fechaAutorizacion'] ?? '',
            'ambiente'=>$autorizacion['ambiente'] ?? ''
        );

        if(!empty($this->data['file_autorized'])){
            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->formatOutput = true;
            $autorizacionNode=$doc->createElement('autorizacion');
            $estado=$doc->createElement('estado',$autorizacion['estado']);
            $numeroAutorizacion=$doc->createElement('numeroAutorizacion',$autorizacion['numeroAutorizacion']);
            $fechaAutorizacion=$doc->createElement('fechaAutorizacion',$autorizacion['fechaAutorizacion']);
            $cdata = $doc->createCDATASection(
                (!mb_detect_encoding($autorizacion['comprobante'], 'UTF-8', true))
                    ? mb_convert_encoding($autorizacion['comprobante'], 'UTF-8', 'ISO-8859-1')
                    : $autorizacion['comprobante']
            );
            $comprobante=$doc->createElement('comprobante');
            $comprobante->appendChild($cdata);

            $fechaAutorizacion->setAttribute('class','fechaAutorizacion');
            $autorizacionNode->appendChild($estado);
            $autorizacionNode->appendChild($numeroAutorizacion);
            $autorizacionNode->appendChild($fechaAutorizacion);
            $autorizacionNode->appendChild($comprobante);
            $doc->appendChild($autorizacionNode);
            $doc->save($this->data['file_autorized']);
            $resp['xml']=$doc->saveXML();
        }

        return $resp;
    }

    public function validateXml($config = array(), $echo=false ){
        try {
            if(!empty($config) && is_array($config)) $this->data = array_merge($this->data,$config);
            if( empty($this->data['file_signed']) || !is_readable($this->data['file_signed']) ) return false;
            $options = array('id_name' => 'id', 'id_ns_prefix' => 'xml', 'id_prefix_ns' => 'http://www.w3.org/XML/1998/namespace');
            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->loadXML (file_get_contents($this->data['file_signed']));
            $signature = DSig::locateSignature($doc);
            $firma=DSig::verifyDocumentSignature($signature,$this->key);
            $referencias=DSig::verifyReferences($signature, $options);
            if($echo) echo '<br/>'.
                        '<b>FIRMA       :</b>'.($firma?'Valida':'Invalida').'<br/>'.
                        '<b>REFERENCIAS :</b>'.($referencias?'Validas':'Invalidas').'<br/>';
            return ($firma && $referencias);
        } catch (Exception $e) {
            if($echo) echo 'Excepción Capturada: ',  $e->getMessage(), "\n";
            return false;
        }
    }

    public function validaXmlWithXsd($file_signed=null, $echo=false){
		if(!empty($file_signed)) $this->data['file_signed']=$file_signed;
        if(!function_exists('libxml_display_error')){
		function libxml_display_error($error){
			$return = "<br/>\n";
			switch ($error->level) {
				case LIBXML_ERR_WARNING: $return .= "<b>Warning $error->code</b>: "; break;
				case LIBXML_ERR_ERROR: $return .= "<b>Error $error->code</b>: "; break;
				case LIBXML_ERR_FATAL: $return .= "<b>Fatal Error $error->code</b>: "; break;
			}
			$return .= trim($error->message);
			$return .= " on line <b>$error->line</b>\n";
			return $return;
		}}
        if(!function_exists('libxml_display_errors')){
		function libxml_display_errors() {
			$html="<br/>\n";
			$errors = libxml_get_errors();
			if(isset($errors)&&!empty($errors)){
				foreach ($errors as $error) {
					$html.=libxml_display_error($error);
				} libxml_clear_errors();
			} return $html;
		}}
		libxml_use_internal_errors(true);
		try {
			if(empty($this->data['file_signed'])||!is_readable($this->data['file_signed'])) return array('success'=>false,'message'=>'No se especifico la ruta del archivo firmado!','type'=>'setter');
			$xml=file_get_contents($this->data['file_signed']);

			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = true;
            $doc->loadXml(self::encode_utf8($xml));

			$doc_name   =strtoupper($doc->documentElement->nodeName);
			$doc_version=str_replace('.','_',$doc->documentElement->getAttribute('version'));

			if(isset($this->xsd_docs[$doc_name])) $doc_name=$this->xsd_docs[$doc_name];
			$xsd=dirname(__FILE__)."/xsd/{$doc_version}/{$doc_name}_V_{$doc_version}.xsd";
			if(!file_exists($xsd)) throw new \Exception("No existe Schema de Validacion para el documento $doc_name version $doc_version!");

			if(!$doc->schemaValidate($xsd)){
				throw new \Exception("El XML no paso el Schema de Validacion del SRI, <b>XSD:{$doc_name}_V_{$doc_version}.xsd</b>! ".libxml_display_errors());
			}
			return array('success'=>true,'message'=>'XSD ha validado correctamente!','type'=>'application');
		} catch (\Exception $e) {
            if($echo) echo 'Excepción Capturada: ',  $e->getMessage(), "\n";
            return array('success'=>false,'message'=>$e->getMessage(),'type'=>'application');
        }
	}
}
