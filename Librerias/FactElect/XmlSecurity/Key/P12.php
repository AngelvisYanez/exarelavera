<?php
/**
 * This file is part of the XmlSecurity library. It is a library written in PHP
 * for working with XML Encryption and Signatures.
 *
 * Large portions of the library are derived from the xmlseclibs PHP library for
 * XML Security (http://code.google.com/p/xmlseclibs/) Copyright (c) 2007-2010,
 * Robert Richards <rrichards@cdatazone.org>. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Robert Richards nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author  Andreas Schamberger <mail@andreass.net>
 * @author  Robert Richards <rrichards@cdatazone.org>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

require_once 'PrivatePublic.php';
/**
 * This class holds a security key and provides the necessary encryption,
 * decryption and certificate handling routines.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class P12 extends PrivatePublic
{
    public $active=false;
    /**
     * Loads the given cryptographic key for the class.
     *
     * @param string  $keyType    \ass\XmlSecurity\Key::TYPE_PUBLIC | \ass\XmlSecurity\Key::TYPE_PRIVATE
     * @param string  $key        Key string or filename
     * @param boolean $isFile     Is parameter key a filename
     * @param string  $passphrase Passphrase for given key
     */
    public function __construct($keyType, $key, $isFile = true, $passphrase = null)
    {
        $this->digest  = OPENSSL_ALGO_SHA1;
        $this->padding = OPENSSL_PKCS1_PADDING;
        $this->type    = self::RSA_SHA1;
        $this->config = array(
            'file' => $key,
            'pass' => $passphrase,
            'data' => null,
            'wordwrap' => 76,
        );
        $this->config['data'] = file_get_contents($this->config['file']);
        
        if(openssl_pkcs12_read($this->config['data'], $this->certs, $this->config['pass'])==false) return false;
        array_push($this->certs['extracerts'],$this->certs['cert']); 
        $this->data=openssl_x509_parse($this->certs['cert']);
        //$this->setCert(0);
        $this->active=true;
        $this->keyType = self::TYPE_PRIVATE;
        $this->passphrase = $passphrase;
        $this->opensslResource=$this->certs['pkey'];
        $this->key = base64_decode($this->certs['pkey']);
        //var_dump($this->certs['cert']);
        unset($this->config['data']);
        
    }
    public function setCert($i){
        $this->certs['cert']= $this->certs['extracerts'][$i];
        $this->data = array_merge($this->data,openssl_x509_parse($this->certs['cert'])); 
    }
    /**
     * Sign the given data with this key and return signature.
     *
     * @param string $data Data to sign
     *
     * @return string
     */
    public function signData($data)
    {
        //var_dump($this->digest);
        if (openssl_sign($data, $signature, $this->certs['pkey'], $this->digest)==false) {
            throw new SignatureErrorException($this->type, $this->getOpenSslErrorString());
        }
        return $signature;
    }
    public function verifySignature($data, $signature, $pub_key = null, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        if ($pub_key === null)
            $pub_key = $this->certs['cert'];
        $pub_key = $this->normalizeCert($pub_key);
        //var_dump($pub_key);
        //var_dump(openssl_dh_compute_key($pub_key))
        return openssl_verify($data, $signature, $pub_key, $signature_alg) == 1 ? true : false;
    }
    /**
     * Método que agrega el inicio y fin de un certificado (clave pública)
     * @param cert Certificado que se desea normalizar
     * @return Certificado con el inicio y fin correspondiente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-20
     */
    private function normalizeCert($cert)
    {
        if (strpos($cert, '-----BEGIN CERTIFICATE-----')===false) {
            $body = trim($cert);
            $cert = '-----BEGIN CERTIFICATE-----'."\n";
            $cert .= wordwrap($body, $this->config['wordwrap'], "\n", true)."\n";
            $cert .= '-----END CERTIFICATE-----'."\n";
        }
        return $cert;
    }
    /**
     * Método que entrega el RUN/RUT asociado al certificado
     * @return RUN/RUT asociado al certificado en formato: 11222333-4
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-02-12
     */
    // public function getID()
    // {
        // // RUN/RUT se encuentra en la extensión del certificado, esto de acuerdo
        // // a Ley 19.799 sobre documentos electrónicos y firma electrónica
        // $x509 = new \phpseclib\File\X509();
        // $cert = $x509->loadX509($this->certs['cert']);
        // if (isset($cert['tbsCertificate']['extensions'])) {
            // foreach ($cert['tbsCertificate']['extensions'] as $e) {
                // if ($e['extnId']=='id-ce-subjectAltName') {
                    // return ltrim($e['extnValue'][0]['otherName']['value']['ia5String'], '0');
                // }
            // }
        // }
        // // se obtiene desde serialNumber (esto es sólo para que funcione la firma para tests)
        // if (isset($this->data['subject']['serialNumber'])) {
            // return ltrim($this->data['subject']['serialNumber'], '0');
        // }
        // // no se encontró el RUN
        // return $this->error('No fue posible obtener el ID de la firma');
    // }
	public function getDataKey(){
		$x509 = openssl_x509_parse($this->certs['cert']);
		return $x509;
	}
    public function getSerialNumber()
    {       
        // $key = openssl_pkey_get_details(openssl_pkey_get_private($this->certs['pkey']));
        // $x509 = openssl_x509_parse($key['key']);
        // var_dump('vergas1',$key);
    
        // $x509 = openssl_x509_parse($this->certs['cert']);
        // var_dump('vergas',$x509['serialNumber']);
        // var_dump($this->getDate($x509['validFrom_time_t']));
        // var_dump($this->getDate($x509['validTo_time_t']));
      
        if (isset($this->data['serialNumber']))
            return $this->data['serialNumber'];
        
    }
    public function getDate($val){
        return date('Y-m-d H:i:s', $val);
    }
    /**
     * Método que entrega el CN del subject
     * @return CN del subject
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-02-12
     */
    public function getName()
    {
        if (isset($this->data['subject']['CN']))
            return $this->data['subject']['CN'];
        return $this->error('No fue posible obtener el Name (subject.CN) de la firma');
    }
    /**
     * Método que entrega el emailAddress del subject
     * @return emailAddress del subject
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-02-12
     */
    public function getEmail()
    {
        if (isset($this->data['subject']['emailAddress']))
            return $this->data['subject']['emailAddress'];
        return $this->error('No fue posible obtener el Email (subject.emailAddress) de la firma');
    }
    /**
     * Método que entrega desde cuando es válida la firma
     * @return validFrom_time_t
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-22
     */
    public function getFrom()
    {
        return date('Y-m-d H:i:s', $this->data['validFrom_time_t']);
    }
    /**
     * Método que entrega hasta cuando es válida la firma
     * @return validTo_time_t
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-22
     */
    public function getTo()
    {
        return date('Y-m-d H:i:s', $this->data['validTo_time_t']);
    }
    /**
     * Método que entrega el nombre del emisor de la firma
     * @return CN del issuer
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-22
     */
    public function getIssuer()
    {
        return $this->data['issuer']['CN'];
    }
    public function getIssuerName()
    {
        $is=$this->data['issuer'];
        $name="CN=$is[CN],L=$is[L],OU=$is[OU],O=$is[O],C=$is[C]";
        return $name;
    }
    /**
     * Método que entrega los datos del certificado
     * @return Arreglo con todo los datos del certificado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-11
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * Método que obtiene el módulo de la clave privada
     * @return Módulo en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-07
     */
    public function getModulus()
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->certs['pkey']));
        //var_dump('getModulus');
        //var_dump(base64_encode($details['rsa']['n']));
        return wordwrap(base64_encode($details['rsa']['n']), $this->config['wordwrap'], "\n", true);
    }
    /**
     * Método que obtiene el exponente público de la clave privada
     * @return Exponente público en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-06
     */
    public function getExponent()
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->certs['pkey']));
        return wordwrap(base64_encode($details['rsa']['e']), $this->config['wordwrap'], "\n", true);
    }
    /**
     * Método que entrega el certificado de la firma
     * @return Contenido del certificado, clave pública del certificado digital, en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-24
     */
    public function getCertificate($clean = false, $wrap=false)
    {
        $cert=$this->certs['cert'];        
        if ($clean) {
            $cert=trim(str_replace(
                array('-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'),
                '',
                $cert
            ));
        } 
        if ($wrap){ 
            $cert=wordwrap(preg_replace('/[\n]/i', '', $cert), $this->config['wordwrap'], "\n", true); 
            //var_dump($this->config['wordwrap']);
        }
        return $cert;
    }
    /**
     * Método que entrega la clave privada de la firma
     * @return Contenido de la clave privada del certificado digital en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-24
     */
    public function getPrivateKey($clean = false)
    {
        if ($clean) {
            return trim(str_replace(
                array('-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----'),
                '',
                $this->certs['pkey']
            ));
        } else {
            return $this->certs['pkey'];
        }
    }
    /**
     * Método para realizar la firma de datos
     * @param data Datos que se desean firmar
     * @param signature_alg Algoritmo que se utilizará para firmar (por defect SHA1)
     * @return Firma digital de los datos en base64 o =false si no se pudo firmar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-08
     */
    public function sign($data, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $signature = null;
        if (openssl_sign($data, $signature, $this->certs['pkey'], $signature_alg)==false) {
            return $this->error('No fue posible firmar los datos');
        }
        return base64_encode($signature);
    }

    /**
     * Método que firma un XML utilizando RSA y SHA1
     *
     * Referencia: http://www.di-mgt.com.au/xmldsig2.html
     *
     * @param xml Datos XML que se desean firmar
     * @param reference Referencia a la que hace la firma
     * @return XML firmado o =false si no se pudo fimar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-03
     */
    // public function signXML($xml, $reference = '', $tag = null, $xmlns_xsi = false)
    // {
        // $doc = new XML();
        // $doc->loadXML($xml);
        // if (!$doc->documentElement) {
            // return $this->error('No fue posible obtener el documentElement desde el XML a firmar');
        // }
        // // crear nodo para la firma
        // $Signature = $doc->importNode((new XML())->generate([
            // 'Signature' => [
                // '@attributes' => [
                    // 'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
                // ],
                // 'SignedInfo' => [
                    // '@attributes' => [
                        // 'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
                        // 'xmlns:xsi' => $xmlns_xsi ? 'http://www.w3.org/2001/XMLSchema-instance' : false,
                    // ],
                    // 'CanonicalizationMethod' => [
                        // '@attributes' => [
                            // 'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                        // ],
                    // ],
                    // 'SignatureMethod' => [
                        // '@attributes' => [
                            // 'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                        // ],
                    // ],
                    // 'Reference' => [
                        // '@attributes' => [
                            // 'URI' => $reference,
                        // ],
                        // 'Transforms' => [
                            // 'Transform' => [
                                // '@attributes' => [
                                    // 'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                                // ],
                            // ],
                        // ],
                        // 'DigestMethod' => [
                            // '@attributes' => [
                                // 'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                            // ],
                        // ],
                        // 'DigestValue' => null,
                    // ],
                // ],
                // 'SignatureValue' => null,
                // 'KeyInfo' => [
                    // 'KeyValue' => [
                        // 'RSAKeyValue' => [
                            // 'Modulus' => null,
                            // 'Exponent' => null,
                        // ],
                    // ],
                    // 'X509Data' => [
                        // 'X509Certificate' => null,
                    // ],
                // ],
            // ],
        // ])->documentElement, true);
        // // calcular DigestValue
        // if ($tag) {
            // $item = $doc->documentElement->getElementsByTagName($tag)->item(0);
            // if (!$item) {
                // return $this->error('No fue posible obtener el nodo con el tag '.$tag);
            // }
            // $digest = base64_encode(sha1($item->C14N(), true));
        // } else {
            // $digest = base64_encode(sha1($doc->C14N(), true));
        // }
        // //var_dump(array('antes'=>$digest));
        // $Signature->getElementsByTagName('DigestValue')->item(0)->nodeValue = $digest;
        // // calcular SignatureValue
        // $SignedInfo = $doc->saveHTML($Signature->getElementsByTagName('SignedInfo')->item(0));
        // $firma = $this->sign($SignedInfo);
        // if (!$firma)
            // return false;
        // $signature = wordwrap($firma, $this->config['wordwrap'], "\n", true);
        // // reemplazar valores en la firma de
        // $Signature->getElementsByTagName('SignatureValue')->item(0)->nodeValue = $signature;
        // $Signature->getElementsByTagName('Modulus')->item(0)->nodeValue = $this->getModulus();
        // $Signature->getElementsByTagName('Exponent')->item(0)->nodeValue = $this->getExponent();
        // $Signature->getElementsByTagName('X509Certificate')->item(0)->nodeValue = $this->getCertificate(true);
        // // agregar y entregar firma
        // $doc->documentElement->appendChild($Signature);
        // $save=$doc->saveXML();
        // //$this->verifyXML($save); //die();
        // return $save;
    // }
    /**
     * Método que obtiene la clave asociada al módulo y exponente entregados
     * @param modulus Módulo de la clave
     * @param exponent Exponente de la clave
     * @return Entrega la clave asociada al módulo y exponente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-19
     */
    // public static function getFromModulusExponent($modulus, $exponent)
    // {
        // $rsa = new \phpseclib\Crypt\RSA();
        // $modulus = new \phpseclib\Math\BigInteger(base64_decode($modulus), 256);
        // $exponent = new \phpseclib\Math\BigInteger(base64_decode($exponent), 256);
        // $rsa->loadKey(['n' => $modulus, 'e' => $exponent]);
        // $rsa->setPublicKey();
        // return $rsa->getPublicKey();
    // }
    function filter_content($content)
	 {
	 $filtered_content = $content;
	 $filtered_content = preg_replace('/&/','&amp;', $filtered_content);
	 $filtered_content = preg_replace('/</','&lt;', $filtered_content);
	 $filtered_content = preg_replace('/>/','&gt;', $filtered_content);
	 $filtered_content = preg_replace('/"/','&quot;', $filtered_content);
	 $filtered_content = preg_replace("/'/","&#39;", $filtered_content);
	 return $filtered_content;
	 }

	function filter_content_white_spaces($content)
	 {
	 $filtered_content = $content;
	 $filtered_content = preg_replace('/\n/','', $filtered_content);
	 $filtered_content = preg_replace('/\s/','', $filtered_content);
	 return $filtered_content;
	 }
    function filter_content_base64($content)
	 {
	 $filtered_content = filter_content($content);
	 $filtered_content = preg_replace('/-----BEGIN CERTIFICATE-----/','', $filtered_content);
	 $filtered_content = preg_replace('/-----END CERTIFICATE-----/','', $filtered_content);
	 $filtered_content = preg_replace('/-----BEGIN X509 CERTIFICATE-----/','', $filtered_content);
	 $filtered_content = preg_replace('/-----END X509 CERTIFICATE-----/','', $filtered_content);
	 $filtered_content = preg_replace('/-----BEGIN CRL-----/','', $filtered_content);
	 $filtered_content = preg_replace('/-----END CRL-----/','', $filtered_content);
	 $filtered_content = preg_replace('/-----BEGIN X509 CRL-----/','', $filtered_content);
	 $filtered_content = preg_replace('/-----END X509 CRL-----/','', $filtered_content);
	 $filtered_content = filter_content_white_spaces($filtered_content);
	 return $filtered_content;
	 }
}
