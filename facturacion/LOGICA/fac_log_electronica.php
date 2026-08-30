<?Php

/**
 * Logica de las paginas para comprobantes contables
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2017-06-08
 */

use PHPMailer\PHPMailer\PHPMailer;

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_electronica.php");
require_once("../../Librerias/Xml/XML.php");


$ruta_pdf = array(
    'VENTAS' => "../COMPONENTES/tesPdfFacturaElectronica_1.0.php",
    'RETENC' => "../COMPONENTES/tesPdfRetencionElectronica_1.0.php",
    'NOTASC' => "../COMPONENTES/tesPdfNotasCreditoElectronica_1.0.php",
    'GUIAS' => "../COMPONENTES/tesPdfGuiaRemisionElectronica_1.0.php",
    'NOTASD' => "../COMPONENTES/tesPdfRetencionElectronica_1.0.php",
    'LIQUIDC' => "../COMPONENTES/tesPdfLiquidacionCompraElectronica_1.0.php"
);


/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Elect extends MysqlConexion {}

/* Clase para acceder a los datos */
class Class_Log_Datos_Elect extends MysqlDatos
{
    public $doc;
    public $tag;

    public $xml_tag = array(
        'VENTAS' => array('root' => "factura",              'version' => "1.1.0", 'pdf' => "tesPdfFacturaElectronica_2.0.php",      'mail' => ''),
        'RETENC' => array('root' => "comprobanteRetencion", 'version' => "1.0.0", 'pdf' => "tesPdfRetencionElectronica_2.0.php",    'mail' => ''),
        'RETENC2' => array('root' => "comprobanteRetencion", 'version' => "2.0.0", 'pdf' => "tesPdfRetencionElectronica_2.0.php",    'mail' => ''),
        'NOTASC' => array('root' => "notaCredito",          'version' => "1.1.0", 'pdf' => "tesPdfNotasCreditoElectronica_2.0.php", 'mail' => ''),
        'GUIAS' => array('root' => "guiaRemision",         'version' => "1.1.0", 'pdf' => "tesPdfGuiaRemisionElectronica_2.0.php", 'mail' => ''),
        'NOTASD' => array('root' => "notaDebito",           'version' => "1.0.0", 'pdf' => "tesPdfNotasDebitoElectronica_2.0.php",  'mail' => ''),
        'LIQUIDC' => array('root' => "liquidacionCompra",  'version' => "1.0.0", 'pdf' => "tesPdfLiquidacionCompraElectronica_1.0.php",  'mail' => '')

    );
    function __construct()
    {
        $this->setSentencias('sentencias_elect');
    }
    function getByTag($key, $tag)
    {
        $t = isset($tag) ? $tag : $this->tag;
        return !empty($t) ? isset($this->xml_tag[$tag]) ? !empty($key) ? isset($this->xml_tag[$tag][$key]) ? $this->xml_tag[$tag][$key] : null : $this->xml_tag[$tag] : null : null;
    }

    // <editor-fold defaultstate="collapsed" desc="BLOQUES GLOBALES">
    // partir campo demasiado largo
    function utf8_change_param(&$input, $type = false)
    { /* agregado por erik para limpieza de caracteres especiales */
        if (is_string($input)) {
            if (trim($input) != '') $input = trim($input);
            if ((!!mb_detect_encoding($input, 'UTF-8', true)) == $type) $input = $type ? mb_convert_encoding($input, 'ISO-8859-1', 'UTF-8') : mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                $this->utf8_change_param($value, $type);
            }
            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var) {
                $this->utf8_change_param($input->$var, $type);
            }
        }
    }
    function splitToLongField($label, $string, $extend = true)
    {
        $result = array($label => '');
        $stringArray = explode(" ", $string);

        $i = 0;
        $lbl = $label;
        foreach ($stringArray as $str) {
            if (strlen($result[$lbl]) + strlen($str) + 1 > 300) {
                if (!$extend) return $result;
                $i++;
                $lbl = str_pad("", $i, " ");
                $result[$lbl] = "";
            }
            $result[$lbl] .= ($result[$lbl] == '' ? "" : " ") . $str;
        }
        return $result;
    }
    // Enviar E-Mail
    function getMailData($Doc_Cod, $obBD)
    {
        return array();
    }


    function sendMailDoc($Doc_Cod, $correo, $destinatario, $obBD, $adjuntos = false)
    {
        $ban = true;
        if (empty($Doc_Cod) || empty($correo) || strlen($correo) < 4) return false;
        $datos = array_merge($this->getInfoEmpresa($obBD), array_merge($this->getMailData($Doc_Cod, $obBD), array('Emp_Cod' => $_SESSION['Ses_Emp_Cod'], 'Doc_Cod' => $Doc_Cod, 'type' => $this->tag, 'Documento' => $this->doc)));
        if (!empty($destinatario) || empty($datos['Destinatario'])) $datos['Destinatario'] = $destinatario;
        if (empty($datos['Destinatario'])) $datos['Destinatario'] = 'Destinatario No Definido';
        $this->utf8_change_param($datos);
        if (isset($datos['Fecha'])) {
            $array_meses1 = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
            $fec = explode('-', $datos['Fecha']);
            $datos['Fecha'] = $fec[2] . ' de ' . $array_meses1[$fec[1] - 1] . ' de ' . $fec[0];
        }
        //var_dump($datos);exit();
        try {
            $template = $this->getByTag('mail', $this->tag);
            $body = $this->reporteHtml($datos, '../../templates/' . (empty($template) ? 'doc_elect_exa.html' : $template));
            require_once '../../Librerias/PHPMailer_compat.php';
               
            $mail = new PHPMailer(true); // Crear una nueva  instancia de PHPMailer habilitando el tratamiento de excepciones
            $mail->charSet = "UTF-8";
            $mail->IsSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = "exa.facturacion@gmail.com";
            $mail->Password =  "owdjkcjdxvftwbxg";  //habilitar
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->Timeout = 10;
            $mail->SMTPKeepAlive = true;
            $mail->From = "exa.facturacion@gmail.com"; 
            $mail->Sender = "exa.facturacion@gmail.com";
            $mail->FromName = $datos['Emp_Nom'];
            $correos = explode(",", $correo);
            foreach ($correos as  $c) {
                $mail->AddAddress(trim($c), strtoupper($datos['Destinatario']));
            }
            $mail->IsHTML(true);
            $mail->Subject = "Comprobante Electrónico";
            // Creamos en una variable el cuerpo, contenido HMTL, del correo //$body  = "Proebando los correos con un tutorial<br>";
            $mail->Body = $body;
            if ($adjuntos == true) {
                $ruta = realpath(dirname(__file__) . "/../FRONT/$_SESSION[Ses_Emp_Cod]");
                $logo = realpath(dirname(__file__) . "/$datos[Emp_Log]");
                $file_autorized = "$ruta/$datos[claveAcceso]_A.xml";
                $file_ride = "$ruta/$datos[claveAcceso].pdf";
                
                $this->createPdfByString($file_autorized, $logo, 'F', false, "$ruta/");
                $mail->AddAttachment($file_autorized, "$datos[claveAcceso].xml");
                $mail->AddAttachment($file_ride, "$datos[claveAcceso].pdf");
                //Enviar reporte de factura consu manfiiestos agrupados
            }
            $mail->Send(); // Enviar el correo
            if (isset($file_ride) && is_file($file_ride)) unlink($file_ride);
        } catch (Exception $e) {
            $ban = false; 
            //ChromePhp::log($e);
        }
        return $ban;
    }





    /*function sendMailDoc($Doc_Cod,$correo,$destinatario,$obBD){
        $ban=true;
        if(empty($Doc_Cod)||empty($correo)||strlen($correo)<4) return false;
        $datos= array_merge($this->getInfoEmpresa($obBD),array_merge($this->getMailData($Doc_Cod,$obBD),array('Emp_Cod'=>$_SESSION['Ses_Emp_Cod'],'Doc_Cod'=>$Doc_Cod,'type'=>$this->tag,'Documento'=>$this->doc)));
        if(!empty($destinatario)||empty($datos['Destinatario'])) $datos['Destinatario']=$destinatario;
        if(empty($datos['Destinatario'])) $datos['Destinatario']='Destinatario No Definido';
        $this->utf8_change_param($datos);
        if(isset($datos['Fecha'])){
            $array_meses1 =array ("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
            $fec = explode('-', $datos['Fecha']); $datos['Fecha']=$fec[2].' de '.$array_meses1[$fec[1]-1].' de '.$fec[0];

        }
        //var_dump($datos);exit();
        try{
            $template=$this->getByTag('mail',$this->tag);
            $body = $this->reporteHtml($datos, '../../templates/'.(empty($template)?'doc_elect_exa.html':$template));

            require_once '../../Librerias/PHPMailer_compat.php';
            $mail = new PHPMailer(true); // Crear una nueva  instancia de PHPMailer habilitando el tratamiento de excepciones
            // Configuramos el protocolo SMTP con autenticaci�n
            $mail->IsSMTP();
            $mail->SMTPAuth = true;
            $mail->IsHTML(true);
            // Configuraci�n del servidor SMTP
            $mail->Port = 25;
            $mail->Host = 'ofsercont.com';
            $mail->Username = "facturacion.electronica@ofsercont.com";
            $mail->Password = "p.123456";
            // Configuraci�n cabeceras del mensaje
            $mail->From = "facturacion.electronica@ofsercont.com";
            $mail->FromName = $datos['Emp_Nom'];
            $mail->AddAddress(trim($correo),strtoupper($datos['Destinatario']));
            //$mail->AddAddress("destino2@correo.com","Nombre 2");
            //$mail->AddCC("copia1@correo.com","Nombre copia 1");
            //$mail->AddBCC("copia1@correo.com","Nombre copia 1");
            $mail->Subject = "Comprobante Electr�nico";
            // Creamos en una variable el cuerpo, contenido HMTL, del correo //$body  = "Proebando los correos con un tutorial<br>";
            $mail->Body = $body;
            // Ficheros adjuntos //$mail->AddAttachment("misImagenes/foto1.jpg", "developandoFoto.jpg");
            $mail->Send(); // Enviar el correo
        }catch(Exception $e) { $ban=false; }
        return $ban;
    }*/
    function reporteHtml($params, $templatePath)
    {
        if (!is_file($templatePath)) {
            throw new Exception('No se ha encontrado la plantilla!');
        }
        $templateTxt = file_get_contents($templatePath);
        $this->utf8_change_param($templateTxt, true);
        foreach ($params as $key => $value) $templateTxt = str_replace('{' . $key . '}', $value, $templateTxt);
        $buffer = preg_replace('/{(.+)}/', '', $templateTxt);
        return $buffer;
    }
    public function createPdfByString($xml_aut, $logoUrl = null, $op = 'I', $isText = true, $ruta = '')
    {
        //var_dump($logoUrl);
        //ChromePhp::log(":: " . $this->xml_tag[$this->tag]['pdf']);
        $nueva_elect = true;
        include_once dirname(__file__) . '/../COMPONENTES/' . $this->xml_tag[$this->tag]['pdf'];
        if ($op == 'S' && isset($pdf)) return $pdf->Output('', $op);
        if ($op != 'F') exit();
    }

   /* protected function saveXml($xml, $clave_acc)
    {
        $Emp_Cod = $_SESSION['Ses_Emp_Cod'];
        if (!file_exists($Emp_Cod)) mkdir($Emp_Cod, 0777, true);
        $archivo = $Emp_Cod . "/" . $clave_acc . ".xml";

        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save($archivo);
        return $dom->saveXML();
    }*/

    //Funcion nueva
    protected function saveXml($xml, $clave_acc)
    {
        $Emp_Cod = $_SESSION['Ses_Emp_Cod'];
        // Obtener la ruta base del proyecto (desde facturacion/LOGICA/ subimos dos niveles)
        $basePath = realpath(dirname(__FILE__) . '/../../');
        $rutaXml = $basePath . DIRECTORY_SEPARATOR . 'facturacion' . DIRECTORY_SEPARATOR . 'FRONT' . DIRECTORY_SEPARATOR . $Emp_Cod . DIRECTORY_SEPARATOR;
        if (!file_exists($rutaXml)) mkdir($rutaXml, 0777, true);
        $archivo = $rutaXml . $clave_acc . ".xml";
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save($archivo);
        return $dom->saveXML();
    }


    protected function fDate($fecha)
    {
        $d = explode('-', $fecha);
        return "$d[2]/$d[1]/$d[0]";
    }
    protected function unsetOpcionales(&$input, $keys)
    {
        foreach ($keys as $k) {
            if (empty($input[$k]))  unset($input[$k]);
        }
    }
    protected function clearArray(&$input, $opcionales = array())
    {
        $this->cleanSaltosLinea($input);
        $this->cleanEspecialChar($input);
        $this->unsetOpcionales($input, $opcionales);
    }
    function cleanEspecialChar(&$input)
    {
        if (is_string($input)) {
            //$input = str_ireplace(array('&', "'", "\"", '<', '>', "~", "^", "¿", "?"), array("&amp;", "&apos;", "&quot;", "&lt;", "&gt;", "", "", "", ""), trim($input));
            $input = str_ireplace(array('&', "\"", '<', '>', "~", "^", "¿", "?"), array("&amp;", "&apos;", "&quot;", "&lt;", "&gt;", "", "", "", ""), trim($input));
        
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                $this->cleanEspecialChar($value);
            }
            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var) {
                $this->cleanEspecialChar($input->$var);
            }
        }
    }
    protected function toXML($array, $tag)
    {
        $this->utf8_change_param($array);
        $this->unsetOpcionales($array, array('infoAdicional'));
        $xml = XmlDoc::createFromArray($this->xml_tag[$tag]['root'], $array);
        $xml->setAttribute('id', 'comprobante');
        $xml->setAttribute('version', $this->xml_tag[$tag]['version']);
        return $xml;
    }
    protected function getInfoEmpresa($obBD, $Suc_Cod = null, $Emp_Cod = null)
    {
        return $this->getRowConsultaSql("SELECT Emp_Ruc,Emp_Nom,Emp_Cor,Emp_Cnt,Emp_Reg,Suc_Cod,Suc_Dir,Suc_Sri,Suc_Te1,Suc_Te2,Suc_Log,confi_fact.*,Emp_Log
            FROM empresas INNER JOIN sucursal ON empresas.Emp_Cod=sucursal.Emp_Cod INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
            WHERE " . (empty($Emp_Cod) ? " empresas.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND Suc_Cod='" . (empty($Suc_Cod) ? $_SESSION['Ses_Suc_Cod'] : $Suc_Cod) . "' " : " empresas.Emp_Cod='$Emp_Cod' ") . ";", $obBD);
    }
    function getClaveAcceso($Aut_Cod, $Doc_Fec, $Doc_Num, $obBD)
    {
        //        try{
        $rs_infoCliente = $this->getRowConsultaSql("SELECT LPAD(Tic_Sri,2,'0')AS Tic_Sri,sucursal.Suc_Cod,autorizaci.Aut_Sri,autorizaci.Pun_Sri,sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin
                FROM puntos_imp
                   INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
                   INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                   INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
                WHERE autorizaci.Aut_Cod =$Aut_Cod;", $obBD);
        $rs_infoEmpresa = $this->getInfoEmpresa($obBD, $rs_infoCliente['Suc_Cod']);
        if (empty($rs_infoEmpresa) || empty($Doc_Fec) || empty($Doc_Num)) return null;

        $Emp_Cod = $_SESSION['Ses_Emp_Cod'];
        $Secuencia = $rs_infoEmpresa['Suc_Sri'] . $rs_infoCliente['Pun_Sri'] . str_pad($Doc_Num, 9, "0", STR_PAD_LEFT);
        $TipoAmbienteCE = $rs_infoEmpresa['Cof_Fac'];
        $TipoEmisionCE = $rs_infoEmpresa['Cof_Fte'];

        /*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
        if ($rs_infoEmpresa['Cof_Fte'] == '1') { /*clave de acceso de tipo emision NORMAL*/
            $cadena = date("dmY", strtotime($Doc_Fec)) . $rs_infoCliente['Tic_Sri'] . $rs_infoEmpresa['Emp_Ruc'] . $rs_infoEmpresa['Cof_Fac'] . $Secuencia . "12345678" . $rs_infoEmpresa['Cof_Fte'];
        } else {
            if (count(file($Emp_Cod . "/" . $rs_infoEmpresa['Cof_Clv'])) != 0) { /* preguntamos si el txt aun posee numeros para usar */
                $file = file($Emp_Cod . "/" . $rs_infoEmpresa['Cof_Clv']);
                /*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
                $cadena = date("dmY", strtotime($Doc_Fec)) . $rs_infoCliente['Tic_Sri'] . $rs_infoEmpresa['Emp_Ruc'] . $rs_infoEmpresa['Cof_Fac'] . substr($file[0], 14, 23) . $rs_infoEmpresa['Cof_Fte'];
            } else {
                /*clave de acceso de tipo emision NORMAL*/
                $cadena = date("dmY", strtotime($Doc_Fec)) . $rs_infoCliente['Tic_Sri'] . $rs_infoEmpresa['Emp_Ruc'] . $rs_infoEmpresa['Cof_Fac'] . $Secuencia . "12345678" . $rs_infoEmpresa['Cof_Fte'];
                $TipoAmbienteCE = $rs_infoEmpresa['Cof_Fac'];
                $TipoEmisionCE = "1";
            }
        }
        $factor = 2;
        $suma = 0;
        for ($i = strlen($cadena) - 1; $i >= 0; $i--) {
            $suma += $factor * $cadena[$i];
            $factor = $factor % 7 == 0 ? 2 : $factor + 1;
        }
        $dv = 11 - $suma % 11;
        $ds = $dv == 11 ? 0 : ($dv == 10 ? "1" : $dv);
        return $cadena . $ds;
        //        }catch(Exception $e){ return null; }
    }
    protected function _infoTributaria($Aut_Cod, $clave_acc, $obBD)
    {
        $autorizacion = $this->getRowConsultaSql("SELECT Aut_Cod,Tic_Sri,Suc_Sri,Pun_Sri,Emp_Ruc,Emp_Nom,Emp_Cor,Suc_Dir,Suc_Com
            FROM autorizaci
            INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
            INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
            INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
            INNER JOIN empresas ON empresas.Emp_Cod=sucursal.Emp_Cod
            WHERE Aut_Cod='$Aut_Cod' AND empresas.Emp_Cod='$_SESSION[Ses_Emp_Cod]';", $obBD);
        if (empty($autorizacion)) {
            return array();
        }

        $infoTrib = array(
            'ambiente'          => substr($clave_acc, 23, 1),
            'tipoEmision'       => substr($clave_acc, 47, 1),
            'razonSocial'       => $autorizacion['Emp_Nom'],
            'nombreComercial'   => isset($autorizacion['Suc_Com']) && !empty($autorizacion['Suc_Com']) ? $autorizacion['Suc_Com'] : $autorizacion['Emp_Cor'], /*opcional*/
            'ruc'               => $autorizacion['Emp_Ruc'],
            'claveAcceso'       => $clave_acc,
            'codDoc'            => str_pad($autorizacion['Tic_Sri'], 2, "0", STR_PAD_LEFT),
            'estab'             => str_pad($autorizacion['Suc_Sri'], 3, "0", STR_PAD_LEFT),
            'ptoEmi'            => str_pad($autorizacion['Pun_Sri'], 3, "0", STR_PAD_LEFT),
            'secuencial'        => substr($clave_acc, 30, 9),
            'dirMatriz'         => empty($autorizacion['Suc_Dir']) ? ' - ' : $autorizacion['Suc_Dir'],
        );

        $confiMicroAge = $this->getRowConsultaSql("SELECT Cof_Micro, Cof_Age, Cof_Rim FROM confi_fact 
                                                WHERE Emp_Cod ='$_SESSION[Ses_Emp_Cod]';", $obBD);
        if ($confiMicroAge['Cof_Micro'] == 'S')
            $infoTrib['regimenMicroempresas'] = "CONTRIBUYENTE RÉGIMEN  MICROEMPRESAS";
        if ($confiMicroAge['Cof_Age'] == 'S')
            $infoTrib['agenteRetencion'] = "1";
        if ($confiMicroAge['Cof_Rim'] == 'S' || $confiMicroAge['Cof_Rim'] == 'EM')
            $infoTrib['contribuyenteRimpe'] = "CONTRIBUYENTE RÉGIMEN RIMPE";
        if ($confiMicroAge['Cof_Rim'] == 'NP')
            $infoTrib['contribuyenteRimpe'] = "CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE";

        $this->clearArray($infoTrib, array('nombreComercial'));
        return $infoTrib;
    }
    protected function _infoAdicional($info = array())
    {

        $infoAdicional = array('campoAdicional' => array());
        foreach ($info as $k => $v)
            if (is_string($v) && !empty($v) && trim($v) != '' && trim($v) != '-') {
                $campo = array('@attributes' => array('nombre' => $k), '@value' => $v);
                $this->cleanSaltosLinea($campo);
                if ($campo['@attributes']['nombre'] == '') $campo['@attributes']['nombre'] = ' ';
                array_push($infoAdicional['campoAdicional'],  $campo);
            }
        $this->unsetOpcionales($infoAdicional, array('campoAdicional'));
        return $infoAdicional;
    }

    protected function _detallesAdicionales($detAd = array())
    {
        $detalles = array('detAdicional' => array());
        foreach ($detAd as $k => $v)
            if (!empty($v)) {
                $campo = array('@attributes' => array('nombre' => $k, 'valor' => $v));
                $this->cleanSaltosLinea($campo);
                array_push($detalles['detAdicional'],  $campo);
            }
        $this->unsetOpcionales($detalles, array('detAdicional'));
        return $detalles;
    }
    protected function _impuesto($cod, $porc, $tarif, $base, $val)
    {
        $imp = array(
            'codigo'            => $cod,
            'codigoPorcentaje'  => $porc,
            'tarifa'            => $tarif,
            'baseImponible'     => formato_numero($base, 2, 1),
            'valor'             => formato_numero($val, 2, 1)
        );
        if ($tarif == null) unset($imp['tarifa']);
        return $imp;
    }
    protected function _pago($forma, $total, $plazo = null, $unidad = null)
    {
        $pago = array(
            'formaPago'    => str_pad($forma, 2, "0", STR_PAD_LEFT),
            'total'        => $total,
            'plazo'        => $plazo,
            'unidadTiempo' => $unidad
        );
        $this->clearArray($pago, array('plazo', 'unidadTiempo'));
        return $pago;
    }
    /* FIN BLOQUES GLOBALES */
    // </editor-fold>
}

// <editor-fold defaultstate="collapsed" desc="GUIA DE REMISION ELECTRONICA">
class Class_Log_Datos_Guia_Elect extends Class_Log_Datos_Elect
{
    public $doc = 'GUIA DE REMISION';
    public $tag = 'GUIAS';
    public $sri = '06';

    function createPdf($Doc_Cod, $obBD, $op = 'I')
    {
        $guia = $this->getRowConsultaSql("SELECT guias_remis.Gui_Cod,Gui_Aut,Gui_Xml,Gui_Sri,Emp_Cod FROM guias_remis INNER JOIN guia_persona ON guia_persona.Gpe_Cod=guias_remis.Gpe_Cod WHERE guias_remis.Gui_Cod='$Doc_Cod';", $obBD);
        if (empty($guia)) return;
        //$this->echoLog($guia);
        $emp = $this->getInfoEmpresa($obBD, null, $guia['Emp_Cod']);
        $nueva_elect = true;
        $Doc_Aut = $guia['Gui_Aut'];
        $offline = $guia['Gui_Xml'] == $guia['Gui_Sri'];
        $urlXml = "../FRONT/$guia[Emp_Cod]/$guia[Gui_Xml]" . ($Doc_Aut == 'S' ? '_A' : '') . ".xml";

        //Consulta para obtener el logo de las sucursales
        $data_suc = $this->getRowConsultaSql("SELECT s.Suc_Log
       FROM guias_remis gr
       JOIN autorizaci a ON gr.Aut_Cod = a.Aut_Cod
       JOIN puntos_imp pi ON pi.pun_cod = a.Pun_Cod
       JOIN sucursal s ON s.Suc_Cod = pi.Suc_Cod
       WHERE gr.Gui_Cod ='$Doc_Cod';", $obBD);

        //Obtener el logo de las sucursales
        $logoUrl = empty($data_suc['Suc_Log']) ? $emp['Emp_Log'] : $data_suc['Suc_Log'];

        include_once '../COMPONENTES/' . $this->xml_tag[$this->tag]['pdf'];
        exit();
    }
    function createPdfByClave($clave, $obBD)
    {
        $guia = $this->getRowConsultaSql("SELECT guias_remis.Gui_Cod FROM guias_remis WHERE guias_remis.Gui_Xml='$clave';", $obBD);
        $this->createPdf($guia['Gui_Cod'], $obBD);
    }

    //XML  GUIA DE REMISION
    function createXmlGuiaRemision($Doc_Cod, $Aut_Cod, $clave_acc, $obBD)
    {

        if ($this->sri != substr($clave_acc, 8, 2)) return null;
        $extra = $this->getRowConsultaSql("SELECT guias_remis.Gui_Obs FROM guias_remis WHERE Gui_Cod='$Doc_Cod';", $obBD);
        try {
            $guia = array(
                'infoTributaria'    => $this->_infoTributaria($Aut_Cod, $clave_acc, $obBD),
                'infoGuiaRemision'  => $this->_infoGuiaRemision($Doc_Cod, $obBD),
                'destinatarios'     => $this->_destinatarios($Doc_Cod, $obBD),
                'infoAdicional'     => $this->_infoAdicional(array('Observación' => $extra['Gui_Obs']))
            );


            /*
            'infoAdicional'=> $this->_infoAdicional(array_merge(array('Email' => $extra['Cli_Cor'] . ' ', 'Teléfono' => $extra['Prs_Tel'] . ' - ' . $extra['Prs_Te2'], 'Dirección' => $extra['Prs_Dir'], 'Pago' => $tipoPago), $VetObs))
        );
        $fact['infoFactura']['totalConImpuestos']['totalImpuesto'] = $this->acumulaTotales($fact);
        $this->unsetOpcionales($fact, array('reembolsos', 'otrosRubrosTerceros', 'infoSustitutivaGuiaRemision', 'infoAdicional'));
      */

            $this->unsetOpcionales($guia, array('infoAdicional'));
            $xml = $this->toXML($guia, $this->tag);
            return $this->saveXml($xml, $clave_acc);
        } catch (Exception $e) {
            return null;
        }
    }
    //FIN DE GUIA DE REMISION


    // cabecera guia
    private function _infoGuiaRemision($Doc_Cod, $obBD)
    {
        $emp = $this->getInfoEmpresa($obBD);
        $guia = $this->getRowConsultaSql("SELECT guias_remis.*,Ide_Prv,Prs_Ced,IF(Gpe_Ras IS NULL OR Gpe_Ras='',CONCAT(Prs_Ape,' ',Prs_Nom),Gpe_Ras)AS transportista FROM guias_remis
            INNER JOIN guia_persona ON guia_persona.Gpe_Cod=guias_remis.Gpe_Cod
            INNER JOIN persona ON guia_persona.Prs_Cod=persona.Prs_Cod
            INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
            WHERE  guias_remis.Gui_Cod='$Doc_Cod';", $obBD);
        if (empty($guia)) {
            return array();
        }

        $infoGuiaR = array(
            'dirEstablecimiento'                => $emp['Suc_Dir'], /*opcional*/
            'dirPartida'                        => $guia['Gui_Dor'],
            'razonSocialTransportista'          => $guia['transportista'],
            'tipoIdentificacionTransportista'   => $guia['Ide_Prv'],
            'rucTransportista'                  => $guia['Prs_Ced'],
            'rise'                              => NULL, /*opcional*/
            'obligadoContabilidad'              => $emp['Emp_Cnt'] == 'S' ? 'SI' : 'NO', /*opcional*/
            'contribuyenteEspecial'             => $emp['Emp_Reg'], /*opcional*/
            'fechaIniTransporte'                => $this->fDate($guia['Gui_Fei']),
            'fechaFinTransporte'                => $this->fDate($guia['Gui_Fef']),
            'placa'                             => $guia['Gui_Pla']
        );
        $this->clearArray($infoGuiaR, array('dirEstablecimiento', 'rise', 'obligadoContabilidad', 'contribuyenteEspecial'));
        return $infoGuiaR;
    }
    //destinatarios
    private function _destinatarios($Doc_Cod, $obBD)
    {
        $destinos = $this->getArrayConsultaSql("SELECT guia_destino.*,Prs_Ced,IF(Gpe_Ras IS NULL OR Gpe_Ras='',CONCAT(Prs_Ape,' ',Prs_Nom),Gpe_Ras)AS destinatario,
            Tic_Sri,Caj_Fec,IF(Vet_Xml IS NULL,Aut_Sri,Vet_Sri)AS Aut_Sri,CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(Vet_Num,9,'0'))AS Vet_Num
            FROM guia_destino
            INNER JOIN guia_persona ON guia_persona.Gpe_Cod=guia_destino.Gpe_Cod
            INNER JOIN persona ON guia_persona.Prs_Cod=persona.Prs_Cod
            LEFT JOIN ventas ON ventas.Vet_Cod=guia_destino.Vet_Cod
            LEFT JOIN caja_aper ON ventas.Caj_Cod=caja_aper.Caj_Cod
            LEFT JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
            LEFT JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
            LEFT JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
            LEFT JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
            WHERE guia_destino.Gui_Cod='$Doc_Cod' ORDER BY Gui_Int;", $obBD);
        if (empty($destinos)) {
            return array();
        }

        $destinatarios = array('destinatario' => array());
        foreach ($destinos as $d) {
            $desti = array(
                'identificacionDestinatario'    => $d['Prs_Ced'],
                'razonSocialDestinatario'       => $d['destinatario'],
                'dirDestinatario'               => $d['Gui_Dde'],
                'motivoTraslado'                => $d['Gui_Mot'],
                'docAduaneroUnico'              => $d['Gui_Dad'], /*opcional*/
                'codEstabDestino'               => $d['Gui_Ces'], /*opcional*/
                'ruta'                          => $d['Gui_Rut'], /*opcional*/
                'codDocSustento'                => empty($d['Tic_Sri']) ? NULL : str_pad($d['Tic_Sri'], 2, "0", STR_PAD_LEFT), /*opcional*/
                'numDocSustento'                => $d['Vet_Num'], /*opcional*/
                'numAutDocSustento'             => $d['Aut_Sri'], /*opcional*/
                'fechaEmisionDocSustento'       => empty($d['Caj_Fec']) ? NULL : $this->fDate($d['Caj_Fec']), /*opcional*/
                'detalles'                      => $this->_detalles_guia($Doc_Cod, $d['Gui_Int'], $obBD)
            );
            $this->clearArray($desti, array('docAduaneroUnico', 'codEstabDestino', 'ruta', 'codDocSustento', 'numDocSustento', 'numAutDocSustento', 'fechaEmisionDocSustento', 'detalles'));
            array_push($destinatarios['destinatario'], $desti);
        }
        return $destinatarios;
    }
    //detalles guia
    private function _detalles_guia($Doc_Cod, $Int, $obBD)
    {
        $dets = $this->getArrayConsultaSql("SELECT guia_det.*,Pro_Bar,Mar_Des,Uni_Des FROM guia_det
            INNER JOIN producto ON guia_det.Pro_Cod=producto.Pro_Cod
            INNER JOIN unidad ON unidad.Uni_Cod=producto.Uni_Cod
            INNER JOIN marca ON marca.Mar_Cod=producto.Mar_Cod
            WHERE Gui_Cod='$Doc_Cod' AND Gui_Int='$Int' ORDER BY Gde_Int;", $obBD);
        if (empty($dets)) {
            return array();
        }

        $detalles = array('detalle' => array());
        foreach ($dets as $d) {
            $deta = array(
                'codigoInterno'     => $d['Pro_Cod'], /*opcional*/
                'codigoAdicional'   => !empty($d['Pro_Bar']) ? $d['Pro_Bar'] : $d['Pro_Cod'], /*opcional*/
                'descripcion'       => $d['Gde_Des'],
                'cantidad'          => $d['Gde_Can'],
                'detallesAdicionales' => $this->_detallesAdicionales(array('Marca' => $d['Mar_Des'], 'Unidad' => $d['Uni_Des'])) /*opcional*/
            );
            $this->clearArray($deta, array('codigoInterno', 'codigoAdicional', 'detallesAdicionales'));
            array_push($detalles['detalle'], $deta);
        }
        $this->unsetOpcionales($detalles, array('detalle'));
        return $detalles;
    }
}
/* FIN GUIA DE REMISION ELECTRONICA */
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="FACTURA ELECTRONICA">
class Class_Log_Datos_Factura_Elect extends Class_Log_Datos_Elect
{
    public $doc = 'FACTURA';
    public $tag = 'VENTAS';
    public $sri = '01';
    public $cod1 = 'codigoPrincipal';
    public $cod2 = 'codigoAuxiliar';

    function createPdf($Doc_Cod, $obBD, $op = 'I')
    {
        $guia = $this->getRowConsultaSql("SELECT ventas.Vet_Cod,Vet_Aut,Vet_Xml,Vet_Sri,Emp_Cod FROM ventas INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod WHERE ventas.Vet_Cod='$Doc_Cod';", $obBD);

        if (empty($guia)) return;
        //$this->echoLog($guia);
        $emp = $this->getInfoEmpresa($obBD, null, $guia['Emp_Cod']);
        $nueva_elect = true;
        $Doc_Aut = $guia['Vet_Aut'];
        $offline = ($guia['Vet_Xml'] == $guia['Vet_Sri']);
        $urlXml = "../FRONT/$guia[Emp_Cod]/$guia[Vet_Xml]" . ($Doc_Aut == 'S' ? '_A' : '') . ".xml";

        //Nuevo codigo para obrener el logo de las sucursales
        $data_suc = $this->getRowConsultaSql("SELECT s.Suc_Log
        FROM ventas gr
        JOIN autorizaci a ON gr.Aut_Cod = a.Aut_Cod
        JOIN puntos_imp pi ON pi.Pun_cod = a.Pun_Cod
        JOIN sucursal s ON s.Suc_Cod = pi.Suc_Cod
        WHERE gr.Vet_Cod ='$Doc_Cod';", $obBD);
        $logoUrl = empty($data_suc['Suc_Log']) ? $emp['Emp_Log'] : $data_suc['Suc_Log'];
        //  echo $logoUrl;exit;
        include_once '../COMPONENTES/' . $this->xml_tag[$this->tag]['pdf'];
        exit();
    }
    function createPdfByClave($clave, $obBD)
    {
        $guia = $this->getRowConsultaSql("SELECT ventas.Vet_Cod FROM ventas WHERE ventas.Vet_Xml='$clave';", $obBD);
        $this->createPdf($guia['Vet_Cod'], $obBD);
    }

    function createXmlFactura($Doc_Cod, $Aut_Cod, $clave_acc, $obBD)
    {
        //if($this->sri!=substr($clave_acc, 8, 2)) return null;
        //19-02-2025 funcional
        $extra = $this->getRowConsultaSql("SELECT ventas.Vet_Obs,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir, ciudad.Ciu_Des,Cli_Cor,Cof_Rim,tipos_pago.For_Cod, empresas.Art_Calif,Ext_Nom,Ext_Ruc,Ext_Email,Veh_Pla
            FROM ventas 
            INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
            INNER JOIN empresas ON cliente.Emp_Cod = empresas.Emp_Cod
            INNER JOIN confi_fact ON empresas.Emp_Cod = confi_fact.Emp_Cod
            INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod 
            INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
            INNER JOIN pago_venta ON ventas.Vet_Cod = pago_venta.Vet_Cod
            INNER JOIN tipos_pago ON pago_venta.Pag_Cod = tipos_pago.Pag_Cod            
            LEFT JOIN vehiculo ON ventas.Veh_Cod = vehiculo.Veh_Cod
            LEFT JOIN rutas_fact_extra ON vehiculo.Ext_Cod = rutas_fact_extra.Ext_Cod
            WHERE ventas.Vet_Cod = '$Doc_Cod';", $obBD);

        // $extra = $this->getRowConsultaSql("SELECT ventas.Vet_Obs,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,
        //                                 ciudad.Ciu_Des,Cli_Cor,Cof_Rim,tipos_pago.For_Cod, empresas.Art_Calif,
        //                                 rutas_fact_extra.Ext_Placa, rutas_fact_extra.Ext_PediPos, rutas_fact_extra.Ext_NrAcep
        //                                 FROM ventas 
        //                                     INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
        //                                     INNER JOIN empresas ON cliente.Emp_Cod = empresas.Emp_Cod
        //                                     INNER JOIN confi_fact ON empresas.Emp_Cod = confi_fact.Emp_Cod
        //                                     INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod 
        //                                     INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
        //                                     INNER JOIN pago_venta ON ventas.Vet_Cod = pago_venta.Vet_Cod
        //                                     INNER JOIN tipos_pago ON pago_venta.Pag_Cod = tipos_pago.Pag_Cod
        //                                     INNER JOIN rutas_fact_extra ON ventas.Vet_Cod = rutas_fact_extra.Vet_Cod
        //                                 WHERE ventas.Vet_Cod = '$Doc_Cod';", $obBD);

        $tipoPago = "";
        if ($extra['For_Cod'] == 1) {
            $tipoPago = "Contado";
        } else {
            $tipoPago = "Credito";
        }
        /*$rimpe = "";
        if($extra['Cof_Rim']=='S'){
            $rimpe = "CONTRIBUYENTE R�GIMEN RIMPE";
        } else {
            $rimpe = "";
        }*/
        $trans = array();
        if (isset($extra['Ext_Nom']) && $extra['Ext_Placa'] !== '')
            $trans = array('RUC TRANS.' => $extra['Ext_Ruc'], 'PLACA TRANS.' => $extra['Veh_Pla'], 'TRANSPORTE' => $extra['Ext_Nom'], 'MAIL TRANS.' => $extra['Ext_Email']);
        try {
            $VetObs = $this->splitToLongField('Observación', $extra['Vet_Obs']);
            $fact = array(
                'infoTributaria'             => $this->_infoTributaria($Aut_Cod, $clave_acc, $obBD),
                'infoFactura'                => $this->_infoFactura($Doc_Cod, $obBD),
                'detalles'                   => $this->_detalles_fact($Doc_Cod, $obBD),
                'reembolsos'                 => $this->_reembolsos($Doc_Cod, $obBD),
                'otrosRubrosTerceros'        => null,
                'infoSustitutivaGuiaRemision' => null,
                'infoAdicional'              => $this->_infoAdicional(array_merge(array('Email Cli.' => $extra['Cli_Cor'] . ' ', 'Telf Cli' => $extra['Prs_Tel'] . ' - ' . $extra['Prs_Te2'], 'Dir. Cli.' => $extra['Prs_Dir'], 'Pago' => $tipoPago, 'Artesano Calificado' => $extra['Art_Calif'], 'Ciudad' => $extra['Ciu_Des']), $trans, $VetObs))
                // array('Placa' => $extra['Ext_Placa'], 'Pedido Pos' => $extra['Ext_PediPos'], 'Nro Aceptación' => $extra['Ext_NrAcep']))),
            );
            $fact['infoFactura']['totalConImpuestos']['totalImpuesto'] = $this->acumulaTotales($fact);
            $this->unsetOpcionales($fact, array('reembolsos', 'otrosRubrosTerceros', 'infoSustitutivaGuiaRemision', 'infoAdicional'));
            $xml = $this->toXML($fact, $this->tag);
            //var_dump($fact['infoAdicional']);
            return $this->saveXml($xml, $clave_acc);
        } catch (Exception $e) {
            return null;
        }
    }
    // cabecera fact
    private function _infoFactura($Doc_Cod, $obBD)
    {
        $emp = $this->getInfoEmpresa($obBD);
        $fact = $this->getRowConsultaSql($this->getSqlVentas($Doc_Cod), $obBD);
        if (empty($fact)) {
            return array();
        }
        // consulta adicional para los dias de plazo que no afecte a la consulta principal
        $vetpag = $this->getArrayConsultaSql("SELECT Tpc_Sri AS formaPago, Vet_Tot AS total, IF(Vet_Plz='' OR Vet_Plz is null,0, Vet_Plz) AS plazo , 'dias' AS unidadTiempo 
                                                    FROM ventas 
                                                        INNER JOIN pago_venta ON ventas.Vet_Cod=pago_venta.Vet_Cod 
                                                        INNER JOIN tipopagocom ON ventas.Tpc_Cod=tipopagocom.Tpc_Cod 
                                                    WHERE pago_venta.Vet_Cod=" . $Doc_Cod, $obBD);

        $infoFact = array(
            'fechaEmision'                => $this->fDate($fact['Caj_Fec']),
            'dirEstablecimiento'          => $emp['Suc_Dir'], /*opcional*/
            'contribuyenteEspecial'       => $emp['Emp_Reg'], /*opcional*/
            'obligadoContabilidad'        => $emp['Emp_Cnt'] == 'S' ? 'SI' : 'NO', /*opcional*/
            'tipoIdentificacionComprador' => str_pad($fact['Ide_Prv'], 2, '0', STR_PAD_LEFT),
            'razonSocialComprador'        => $fact['Cliente'],
            'identificacionComprador'     => $fact['Prs_Ced'],
            'direccionComprador'          => $fact['Prs_Dir'], /*opcional*/
            'totalSinImpuestos'           => $fact['Importe_Descu'],
            'totalDescuento'              => $fact['Descu'],
            'codDocReembolso'             => null,
            'totalComprobantesReembolso'  => null,
            'totalBaseImponibleReembolso' => null,
            'totalImpuestoReembolso'      => null,
            'totalConImpuestos'           => array('totalImpuesto' => array()),
            'propina'                     => '0.00',
            'importeTotal'                => $fact['Total'],
            'moneda'                      => 'DOLAR', /*opcional*/
            // 'pagos'                       => array('pago' => array(0 => $this->_pago($fact['Tpc_Sri'], $fact['Total'])) ),
            'pagos'                       => array('pago' => $vetpag),

        );
        $array_reembolsos = $this->getArrayConsulta("venta_reembolsos.selectWhere", array('where' => array('venta_reembolsos.Vet_Cod' => $Doc_Cod)), $obBD);
        if (count($array_reembolsos) > 0) {
            $reem = array('totalBaseImponibleReembolso' => 0, 'totalImpuestoReembolso'      => 0,);
            foreach ($array_reembolsos as $val) {
                $array_cop = $this->getRowConsulta('compras.selectWhere', array('where' => array('compras.Cop_Cod' => $val['Cop_Cod']), 'setWhere' => array('setTotales')), $obBD);
                $reem['totalBaseImponibleReembolso'] += $array_cop['Importe_Descu'];
                $reem['totalImpuestoReembolso'] += ($array_cop['Ice_Tot'] * 1 + $array_cop['Iva_Tot'] * 1 + $array_cop['Irbpnr']);
            }
            $reem['totalComprobantesReembolso'] = $reem['totalBaseImponibleReembolso'] + $reem['totalImpuestoReembolso'];
            foreach ($reem as $k => $v) {
                $reem[$k] = formato_numero($v, 2, 1);
            }
            $reem['codDocReembolso'] = '41';
            $infoFact = array_merge($infoFact, $reem);
        }
        $this->clearArray($infoFact, array('dirEstablecimiento', 'obligadoContabilidad', 'contribuyenteEspecial', 'direccionComprador', 'moneda', 'codDocReembolso', 'totalComprobantesReembolso', 'totalBaseImponibleReembolso', 'totalImpuestoReembolso'));
        return $infoFact;
    }
    // reembolsos

    protected function _reembolsos($Doc_Cod, $obBD)
    {
        $array_reembolsos = $this->getArrayConsulta("venta_reembolsos.selectWhere", array('where' => array('venta_reembolsos.Vet_Cod' => $Doc_Cod)), $obBD);
        if (empty($array_reembolsos)) {
            return array();
        }
        $dets = array();
        foreach ($array_reembolsos as $val) {
            array_push($dets, $this->getRowConsulta('compras.selectWhere', array('where' => array('compras.Cop_Cod' => $val['Cop_Cod']), 'setWhere' => array('setIdentifica', 'setTotales')), $obBD));
        }

        $detalles = array('reembolsoDetalle' => array());
        foreach ($dets as $d) {
            $fact = array('detalles' => $this->_detalles_compra($d['Cop_Cod'], $obBD));
            $secuen = explode("-", $d['Cop_Num']);
            $deta = array(
                'tipoIdentificacionProveedorReembolso'  => str_pad($d['Ide_Prv_Ree'], 2, '0', STR_PAD_LEFT),
                'identificacionProveedorReembolso'      => $d['Prs_Ced'],
                'codPaisPagoProveedorReembolso'         => '593',
                'tipoProveedorReembolso'                => $d['Prv_Tic'] == 'J' ? '02' : '01',
                'codDocReembolso'                       => $d['Tic_Sri'],
                'estabDocReembolso'                     => $secuen[0],
                'ptoEmiDocReembolso'                    => $secuen[1],
                'secuencialDocReembolso'                => $secuen[2],
                'fechaEmisionDocReembolso'              => $this->fDate($d['Cop_Fec']),
                'numeroautorizacionDocReemb'            => $d['Cop_Aut'],
                'detalleImpuestos' => array('detalleImpuesto' => $this->acumulaTotales($fact, true))
            );
            foreach ($deta['detalleImpuestos']['detalleImpuesto'] as &$vimp) {
                $vimp['baseImponibleReembolso'] = $vimp['baseImponible'];
                unset($vimp['baseImponible']);
                $vimp['impuestoReembolso'] = $vimp['valor'];
                unset($vimp['valor']);
            }
            unset($vimp);
            array_push($detalles['reembolsoDetalle'], $deta);
        }
        return $detalles;
    }
    protected function _detalles_compra($Doc_Cod, $obBD)
    {
        $dets = $this->getArrayConsultaSql("SELECT det_compra.*,Cop_Des,Iva_Sri,Iva_Por,Ice_Sri,Ice_Por FROM det_compra
            INNER JOIN iva ON det_compra.Iva_Cod=iva.Iva_Cod
            INNER JOIN compras ON compras.Cop_Cod=det_compra.Cop_Cod
            INNER JOIN producto ON det_compra.Pro_Cod=producto.Pro_Cod
            INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
            LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
            WHERE det_compra.Cop_Cod='$Doc_Cod' ORDER BY Cop_Int;", $obBD);
        if (empty($dets)) {
            return array();
        }

        $detalles = array('detalle' => array());
        foreach ($dets as $d) {
            $Des = $d['Cop_Des'] * 1 == 0 ? 0 : round($d['Cop_Imp'] * $d['Cop_Des'] / 100, 2);
            $Imp = $d['Cop_Imp'] - $Des;
            $Ice = $d['Cop_Ice'] * 1 == 0 ? 0 : round($Imp * $d['Cop_Ice'] / 100, 2);
            $Irbpnr = 0;
            $Iva = $d['Iva_Por'] * 1 == 0 ? 0 : round(($Imp + $Ice) * $d['Iva_Por'] / 100, 2);
            $deta = array(
                'precioTotalSinImpuesto' => formato_numero($Imp, 2, 1),
                'impuestos'             => array('impuesto' => array(0 => $this->_impuesto('2', $d['Iva_Sri'], $d['Iva_Por'], ($Imp + $Ice), $Iva))) // IVA
            );
            if ($Ice > 0) // ICE
                array_push($deta['impuestos']['impuesto'], $this->_impuesto('3', $d['Ice_Sri'], $d['Ice_Por'], $Imp, $Ice));

            if ($Irbpnr > 0) // Impuesto Botellas No Retornables
                array_push($deta['impuestos']['impuesto'], $this->_impuesto('5', $d['Irb_Sri'], $d['Irb_Por'], $Imp, $Irbpnr));

            array_push($detalles['detalle'], $deta);
        }
        $this->unsetOpcionales($detalles, array('detalle'));
        return $detalles;
    }

    protected function _detalles_fact($Doc_Cod, $obBD)
    {
        $dets = $this->getArrayConsultaSql("SELECT ventas_det.*,CAST(CONCAT(Ite_Lar,IF(Pro_Obs IS NOT NULL AND TRIM(Pro_Obs)!='' AND Pro_Obs!=Ite_Lar, CONCAT(' - ',Pro_Obs),''))AS CHAR)AS product,Pro_Bar,IF(Cod_Const IS NULL OR Cod_Const='',NULL,Cod_Const)AS Cod_Const,Mar_Des,Uni_Des,Vet_Des,Iva_Sri,Iva_Por,Ice_Sri,Ice_Por,imei.Ime_Num AS Ime_Num FROM ventas_det
            INNER JOIN iva ON ventas_det.Iva_Cod=iva.Iva_Cod
            INNER JOIN ventas ON ventas.Vet_Cod=ventas_det.Vet_Cod
            INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
            INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
            LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
            INNER JOIN unidad ON unidad.Uni_Cod=producto.Uni_Cod
            INNER JOIN marca ON marca.Mar_Cod=producto.Mar_Cod
            LEFT JOIN imei ON imei.Ime_Cod=ventas_det.Ime_Cod
            WHERE ventas_det.Vet_Cod='$Doc_Cod' ORDER BY Vet_Ite;", $obBD);
        if (empty($dets)) {
            return array();
        }

        $detalles = array('detalle' => array());
        foreach ($dets as $d) {
            $Viajes_txt = '';
            $viajes = $this->getArrayConsulta('viaje', array('unsetCols' => true, 'addCols' => array('' => array('viaje.Via_Has', 'viaje.Via_Con', 'Ori_Aco' => 'origen.Vlu_Aco', 'Des_Aco' => 'destino.Vlu_Aco')), 'Vet_Cod' => $Doc_Cod, 'Vet_Ite' => $d['Vet_Ite']), $obBD);
            if (is_array($viajes) && !empty($viajes)) {
                $book = array('empty' => array());
                $Viajes_txt = ", DESDE {$viajes[0]['Ori_Aco']} HASTA {$viajes[0]['Des_Aco']}";
                foreach ($viajes as $v) {
                    if (!empty($v['Via_Has'])) {
                        $add = true;
                        foreach ($book as $k => &$b)
                            if ($k == str_replace(" ", "_", $v['Via_Has'])) {
                                array_push($b, $v);
                                $add = false;
                                break;
                            }
                        unset($b);
                        if ($add) $book[str_replace(" ", "_", $v['Via_Has'])] = array($v);
                    } else array_push($book['empty'], $v);
                }
                foreach ($book as $k => $b) {
                    $booking = '';
                    if (!empty($b)) {
                        $conten = implode(",", array_map(function ($e) {
                            return $e['Via_Con'];
                        }, $b));
                        $booking .= "; " . (!empty($b[0]['Via_Has']) ? "Book {$b[0]['Via_Has']}:" : "No Book.:") . (!empty($conten) ? " CONT:($conten)" : '');
                    }
                    $Viajes_txt .= $booking;
                }
            }
            if (isset($d['Ime_Num']) && !empty($d['Ime_Num'])) $d['product'] = $d['product'] . ' - IMEI: ' . $d['Ime_Num'];
            if (isset($d['Des_Adi']) && !empty($d['Des_Adi'])) $d['product'] = $d['product'] . ' - ' . $d['Des_Adi'];
            $d['product'] .= $Viajes_txt;
            $Pru = $d['Vet_Pru'] - ($d['Vet_Dec'] * 1 == 0 ? 0 : $d['Vet_Pru'] * $d['Vet_Dec'] / 100);
            $Des = $d['Vet_Des'] * 1 == 0 ? 0 : round($d['Vet_Imp'] * $d['Vet_Des'] / 100, 2);
            $Imp = $d['Vet_Imp'] - $Des;
            $Ice = $d['Vet_Ice'] * 1 == 0 ? 0 : round($Imp * $d['Vet_Ice'] / 100, 2);
            $Irbpnr = 0;
            $Iva = $d['Iva_Por'] * 1 == 0 ? 0 : round(($Imp + $Ice) * $d['Iva_Por'] / 100, 2);

            //$codigo_auxiliar = !empty($d['Pro_Bar']) ?  $d['Pro_Bar'] : str_pad($d['Pro_Cod'], 8, '0', STR_PAD_RIGHT);
            $codigo_auxiliar = "";
            if ($d['Tic_Cod'] * 1 == 4) {
                $codigo_auxiliar =  !empty($d['Cod_Const']) ?  $d['Cod_Const'] : str_pad($d['Pro_Cod'], 8, '0', STR_PAD_RIGHT);
            }
            $deta = array(
                $this->cod1             => $d['Pro_Cod'],
                $this->cod2             => !empty($d['Cod_Const']) ? $d['Cod_Const'] : $codigo_auxiliar,
                'descripcion'           => strlen($d['product']) > 300 ? $d['product'] : substr($d['product'], 0, 299),
                'cantidad'              => formato_numero($d['Vet_Can'], 5, 1),
                'precioUnitario'        => round($Pru, 6),
                'descuento'             => formato_numero($Des, 2, 1),
                'precioTotalSinImpuesto' => formato_numero($Imp, 2, 1),
                'detallesAdicionales'   => $this->_detallesAdicionales(array('Marca' => $d['Mar_Des'], 'Unidad' => $d['Uni_Des'])), /*opcional*/
                'impuestos'             => array('impuesto' => array(0 => $this->_impuesto('2', $d['Iva_Sri'], $d['Iva_Por'], ($Imp + $Ice), $Iva))) // IVA
            );
            if ($Ice > 0) // ICE
                array_push($deta['impuestos']['impuesto'], $this->_impuesto('3', $d['Ice_Sri'], $d['Ice_Por'], $Imp, $Ice));

            if ($Irbpnr > 0) // Impuesto Botellas No Retornables
                array_push($deta['impuestos']['impuesto'], $this->_impuesto('5', $d['Irb_Sri'], $d['Irb_Por'], $Imp, $Irbpnr));

            $this->clearArray($deta, array('codigoAuxiliar', 'detallesAdicionales'));
            $this->unsetOpcionales($deta, array('codigoAdicional'));
            array_push($detalles['detalle'], $deta);
        }
        $this->unsetOpcionales($detalles, array('detalle'));
        return $detalles;
    }

    // acumular codigos
    protected function acumulaTotales($deta, $tarif = false)
    {
        $aux = array();
        foreach ($deta['detalles']['detalle'] as $d) {
            foreach ($d['impuestos']['impuesto'] as $i) {
                $add = true;
                $ki = $i['codigo'] . '_' . $i['codigoPorcentaje'];
                foreach ($aux as $k => &$a) {
                    if ($k == $ki) {
                        $add = false;
                        $a['baseImponible'] += $i['baseImponible'];
                        $a['valor'] += $i['valor'];
                    }
                }
                unset($a);
                if ($add) $aux[$ki] = $i;
            }
        }
        $impu = array();
        foreach ($aux as $a) {
            array_push($impu, $this->_impuesto($a['codigo'], $a['codigoPorcentaje'], ($tarif == true ? $a['tarifa'] : null), $a['baseImponible'], $a['baseImponible'] * $a['tarifa'] / 100));
        }
        return $impu;
    }


    protected function getSqlVentas_old($Doc_Cod) //Estaba activo antes
    {
        /*  return "SELECT ventas.Vet_Cod, Vet_Aut, Vet_Xml, sucursal.Emp_Cod, persona.Prs_Cod, sucursal.Suc_Cod, ventas.Aut_Cod, autorizaci.Pun_Cod, autorizaci.Tic_Cod, LPAD(CAST(Tic_Sri AS CHAR),2,'0') AS Tic_Sri, ventas.Caj_Cod, Vnd_Cod, Caj_Fec, Suc_Sri, Pun_Sri, Vet_Num, CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Vet_Num AS CHAR),9,'0')) AS Secuencia, IF(Vet_Xml IS NULL OR TRIM(Vet_Xml)='', Aut_Sri, IF(Vet_Sri IS NULL OR TRIM(Vet_Sri)='','PENDIENTE',Vet_Sri)) AS Autorizacion, Vet_Des, Vet_Obs, CAST( SUM( (Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) AS Importe, CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * (ventas.Vet_Des/100))  AS decimal(20,2)) AS Descu, CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Importe_Descu, CAST( SUM(IF(Iva_Por = 0,  CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) , '0'))  AS decimal(20,2)) AS Sub_0, CAST( SUM(IF(Iva_Por != 0, CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) , '0'))  AS decimal(20,2)) AS Sub_12, CAST( SUM(IF(Vet_Ice != 0, CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) ), 0))  AS decimal(20,2)) AS Ice_Tot, CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Iva_Tot, CAST( SUM( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) ) + ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ) ) AS decimal(20,2)) AS Total, ventas.Tpc_Cod, ventas.Cli_Cod, persona.Ide_Cod, Ide_Sri, Ide_Prv, IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',Prs_Ced,Cli_Ruf) AS Prs_Ced, IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='' OR TRIM(Cli_Fac)='-',CONCAT(Prs_Ape,' ',Prs_Nom),Cli_Fac) AS Cliente, Prs_Dir, IF(Tpc_Sri IS NULL,NULL,LPAD(Tpc_Sri,2,'0'))AS Tpc_Sri,Vet_Ntd,Vet_Nns,Vet_Fdm, Vet_Est
            FROM ventas
            INNER JOIN ventas_det ON ventas.Vet_Cod=ventas_det.Vet_Cod
            INNER JOIN iva ON iva.Iva_Cod=ventas_det.Iva_Cod
            LEFT JOIN tipopagocom ON tipopagocom.Tpc_Cod=ventas.Tpc_Cod
            INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod
            INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
            INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
            INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
            INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
            INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
            INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
            WHERE ventas.Vet_Cod='$Doc_Cod' GROUP BY ventas.Vet_Cod ORDER BY sucursal.Emp_Cod, Vet_Num;";*/


        /*CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Iva_Tot, 
        */

        return "SELECT ventas.Vet_Cod, Vet_Aut, Vet_Xml, sucursal.Emp_Cod, persona.Prs_Cod, sucursal.Suc_Cod, ventas.Aut_Cod, 
        autorizaci.Pun_Cod, autorizaci.Tic_Cod, LPAD(CAST(Tic_Sri AS CHAR),2,'0') AS Tic_Sri, 
        ventas.Caj_Cod, Vnd_Cod, Caj_Fec, Suc_Sri, Pun_Sri, ventas.Vet_Num, 
        CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(ventas.Vet_Num AS CHAR),9,'0')) AS Secuencia, 
        IF(Vet_Xml IS NULL OR TRIM(Vet_Xml)='', Aut_Sri, IF(Vet_Sri IS NULL OR TRIM(Vet_Sri)='','PENDIENTE',Vet_Sri)) AS Autorizacion, 
        Vet_Des, Vet_Obs, CAST( SUM( (Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) AS Importe, 
        CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * (ventas.Vet_Des/100))  AS decimal(20,2)) AS Descu, 
        CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Importe_Descu,
        CAST( SUM(IF(Iva_Por = 0,  CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) , '0'))  AS decimal(20,2)) AS Sub_0, CAST( SUM(IF(Iva_Por != 0, CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) , '0'))  AS decimal(20,2)) AS Sub_12, CAST( SUM(IF(Vet_Ice != 0, CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) ), 0))  AS decimal(20,2)) AS Ice_Tot, 
        
        ROUND(  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) * ventas.Vet_Des/100 )) AS decimal(20,5) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) * ventas.Vet_Des/100 )) AS decimal(20,5) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,5) )  AS decimal(20,5) )*Iva_Por/100 ), 0)) AS decimal(20,5))  , 2 ) AS Iva_Tot, 
        
        CAST( SUM( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) ) + ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ) ) AS decimal(20,2)) AS Total, 
        ventas.Tpc_Cod, ventas.Cli_Cod, persona.Ide_Cod, Ide_Sri, coalesce(Vet_Ide,Ide_Prv)as Ide_Prv, 
        if(Vet_Ide='4',
            if(LENGTH(persona.Prs_Ced)<13,concat(persona.Prs_Ced,'001'),persona.Prs_Ced),
            if(Vet_Ide='5',
                if(LENGTH(persona.Prs_Ced)>10,SUBSTRING(persona.Prs_Ced,1,10),persona.Prs_Ced),
                persona.Prs_Ced  
            )
        )as Prs_Ced,
        IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='' OR TRIM(Cli_Fac)='-'  OR TRIM(Cli_Fac)='.'    ,CONCAT(Prs_Ape,' ',Prs_Nom),Cli_Fac) AS Cliente, Prs_Dir,         
        IF(Tpc_Sri IS NULL,NULL,LPAD(Tpc_Sri,2,'0'))AS Tpc_Sri,Vet_Ntd,Vet_Nns,Vet_Fdm, Vet_Est,
        /*COALESCE((SELECT ABS(DATEDIFF(ccpp_cobrar.Cpc_Sys, ccpp_cobrar.Cpc_Ven))
                FROM ccpp_cobrar
                WHERE ventas.Vet_Cod = ccpp_cobrar.Vet_Cod), 0
                ) AS plazo,
                'dias' AS unidadTiempo*/
        COALESCE(
                (SELECT ABS(DATEDIFF(ccpp_cobrar.Cpc_Sys, ccpp_cobrar.Cpc_Ven))
                    FROM ccpp_cobrar
                    WHERE ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
                LIMIT 1),
                pago_venta.Vet_Plz,
                0
            ) AS plazo, 'dias' AS unidadTiempo
            FROM ventas
            INNER JOIN ventas_det ON ventas.Vet_Cod=ventas_det.Vet_Cod
            INNER JOIN iva ON iva.Iva_Cod=ventas_det.Iva_Cod
            LEFT JOIN tipopagocom ON tipopagocom.Tpc_Cod=ventas.Tpc_Cod
            INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod
            INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
            INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
            INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
            INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
            INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
            INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
            LEFT JOIN pago_venta ON pago_venta.Vet_Cod = ventas.Vet_Cod
            /* LEFT JOIN ccpp_cobrar ON ventas.Vet_Cod = ccpp_cobrar.Vet_Cod */
            /*LEFT JOIN (
                SELECT Vet_Cod, MIN(Cpc_Cod) as Cpc_Cod 
                FROM ccpp_cobrar 
                GROUP BY Vet_Cod
            ) AS ccpp_cobrar ON ventas.Vet_Cod = ccpp_cobrar.Vet_Cod */
            WHERE ventas.Vet_Cod='$Doc_Cod' GROUP BY ventas.Vet_Cod ORDER BY sucursal.Emp_Cod, Vet_Num;";
    }


    protected function getSqlVentas($Doc_Cod)
    {
       return "SELECT ventas.Vet_Cod, Vet_Aut, Vet_Xml, sucursal.Emp_Cod, persona.Prs_Cod, sucursal.Suc_Cod, ventas.Aut_Cod,
                autorizaci.Pun_Cod, autorizaci.Tic_Cod, LPAD(CAST(Tic_Sri AS CHAR),2,'0') AS Tic_Sri, ventas.Caj_Cod, Vnd_Cod, Caj_Fec, Suc_Sri, Pun_Sri, ventas.Vet_Num,
                CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(ventas.Vet_Num AS CHAR),9,'0')) AS Secuencia, 
                IF(Vet_Xml IS NULL OR TRIM(Vet_Xml)='', Aut_Sri, IF(Vet_Sri IS NULL OR TRIM(Vet_Sri)='','PENDIENTE',Vet_Sri)) AS Autorizacion,
                Vet_Des, Vet_Obs, CAST( SUM( (Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) AS Importe, 
                CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * (ventas.Vet_Des/100))  AS decimal(20,2)) AS Descu,
                    CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - 
                    ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Importe_Descu, 
                    CAST( SUM(IF(Iva_Por = 0,  CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) , '0'))  AS decimal(20,2)) AS Sub_0, CAST( SUM(IF(Iva_Por != 0, CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) , '0'))  AS decimal(20,2)) AS Sub_12, CAST( SUM(IF(Vet_Ice != 0, CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) ), 0))  AS decimal(20,2)) AS Ice_Tot,  ROUND(  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) * ventas.Vet_Des/100 )) AS decimal(20,5) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,5) ) * ventas.Vet_Des/100 )) AS decimal(20,5) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,5) )  AS decimal(20,5) )*Iva_Por/100 ), 0)) AS decimal(20,5))  , 2 ) AS Iva_Tot,  CAST( SUM( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) ) + ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ) ) AS decimal(20,2)) AS Total, ventas.Tpc_Cod, ventas.Cli_Cod, persona.Ide_Cod, Ide_Sri, coalesce(Vet_Ide,Ide_Prv)as Ide_Prv, if(Vet_Ide='4', if(LENGTH(persona.Prs_Ced)<13,concat(persona.Prs_Ced,'001'),persona.Prs_Ced), if(Vet_Ide='5', if(LENGTH(persona.Prs_Ced)>10,SUBSTRING(persona.Prs_Ced,1,10),persona.Prs_Ced), persona.Prs_Ced ) )as Prs_Ced, IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='' OR TRIM(Cli_Fac)='-'  OR TRIM(Cli_Fac)='.'    ,CONCAT(Prs_Ape,' ',Prs_Nom),Cli_Fac) AS Cliente, Prs_Dir, IF(Tpc_Sri IS NULL,NULL,LPAD(Tpc_Sri,2,'0'))AS Tpc_Sri,Vet_Ntd,Vet_Nns,Vet_Fdm, Vet_Est, 
                    COALESCE( (
          SELECT ABS(DATEDIFF(c.Cpc_Sys, c.Cpc_Ven))
          FROM ccpp_cobrar c
          WHERE c.Vet_Cod = ventas.Vet_Cod
          LIMIT 1 ),  0 ) AS plazo, 'dias' AS unidadTiempo

        FROM ventas INNER JOIN ventas_det ON ventas.Vet_Cod=ventas_det.Vet_Cod INNER JOIN iva ON iva.Iva_Cod=ventas_det.Iva_Cod LEFT JOIN tipopagocom ON tipopagocom.Tpc_Cod=ventas.Tpc_Cod INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod 
        LEFT JOIN (SELECT Vet_Cod,  SUM(Vet_Mon) AS Total_Pagado FROM pago_venta  GROUP BY Vet_Cod) pagos ON pagos.Vet_Cod = ventas.Vet_Cod
        WHERE ventas.Vet_Cod='$Doc_Cod' GROUP BY ventas.Vet_Cod ORDER BY sucursal.Emp_Cod, Vet_Num;";
    }

    function getMailData($Doc_Cod, $obBD)
    {
        $venta = $this->getRowConsultaSql($this->getSqlVentas($Doc_Cod), $obBD);
        $venta['claveAcceso'] = $venta['Vet_Xml'];
        $venta['Destinatario'] = $venta['Cliente'];
        $venta['Aut'] = $venta['Vet_Aut'];
        $venta['Fecha'] = $venta['Caj_Fec'];
        return $venta;
    }
}
/* FIN FACTURA ELECTRONICA */
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="NOTA DE CREDITO ELECTRONICA">
class Class_Log_Datos_NCredito_Elect extends Class_Log_Datos_Factura_Elect
{
    public $doc = 'NOTA DE CREDITO';
    public $tag = 'NOTASC';
    public $sri = '04';
    public $cod1 = 'codigoInterno';
    public $cod2 = 'codigoAdicional';

    function createXmlNCredito($Doc_Cod, $Aut_Cod, $clave_acc, $obBD)
    {
        if ($this->sri != substr($clave_acc, 8, 2)) return null;
        $extra = $this->getRowConsultaSql("SELECT ventas.Vet_Obs,Prs_Tel,Prs_Te2,Prs_Cel,Cli_Cor,Prs_Dir FROM ventas INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod WHERE Vet_Cod='$Doc_Cod';", $obBD);
        try {
            $fact = array(
                'infoTributaria'             => $this->_infoTributaria($Aut_Cod, $clave_acc, $obBD),
                'infoNotaCredito'            => $this->_infoNotaCredito($Doc_Cod, $obBD),
                'detalles'                   => $this->_detalles_fact($Doc_Cod, $obBD),
                'infoAdicional'              => $this->_infoAdicional(array('Email' => $extra['Cli_Cor'] . ' ', 'Teléfono' => $extra['Prs_Tel'] . ' - ' . $extra['Prs_Te2'], 'Dirección' => $extra['Prs_Dir'], 'Observación' => $extra['Vet_Obs']))
            );
            $fact['infoNotaCredito']['totalConImpuestos']['totalImpuesto'] = $this->acumulaTotales($fact);
            $this->unsetOpcionales($fact, array('infoAdicional'));

            $xml = $this->toXML($fact, $this->tag);
            return $this->saveXml($xml, $clave_acc);
        } catch (Exception $e) {
            return null;
        }
    }
    // cabecera fact
    private function _infoNotaCredito($Doc_Cod, $obBD)
    {
        $emp = $this->getInfoEmpresa($obBD);
        $fact = $this->getRowConsultaSql($this->getSqlVentas($Doc_Cod), $obBD);
        if (empty($fact)) {
            return array();
        }
        $infoFact = array(
            'fechaEmision'                => $this->fDate($fact['Caj_Fec']),
            'dirEstablecimiento'          => $emp['Suc_Dir'], /*opcional*/
            'tipoIdentificacionComprador' => str_pad($fact['Ide_Prv'], 2, '0', STR_PAD_LEFT),
            'razonSocialComprador'        => $fact['Cliente'],
            'identificacionComprador'     => $fact['Prs_Ced'],
            'contribuyenteEspecial'       => $emp['Emp_Reg'], /*opcional*/
            'obligadoContabilidad'        => $emp['Emp_Cnt'] == 'S' ? 'SI' : 'NO', /*opcional*/
            'rise'                        => null,
            'codDocModificado'            => str_pad($fact['Vet_Ntd'], 2, '0', STR_PAD_LEFT),
            'numDocModificado'            => $fact['Vet_Nns'],
            'fechaEmisionDocSustento'     => $this->fDate($fact['Vet_Fdm']),
            'totalSinImpuestos'           => $fact['Importe_Descu'],
            //'totalDescuento'              =>$fact['Descu'],
            'valorModificacion'           => $fact['Total'],
            'moneda'                      => 'DOLAR', /*opcional*/
            'totalConImpuestos'           => array('totalImpuesto' => array()),
            'motivo'                      => $fact['Vet_Obs']
        );
        $this->clearArray($infoFact, array('dirEstablecimiento', 'obligadoContabilidad', 'contribuyenteEspecial', 'rise', 'valorModificacion', 'moneda'));
        if (empty($infoFact['motivo'])) $infoFact['motivo'] = "NINGUNO";
        return $infoFact;
    }
}
/* FIN NOTA DE CREDITO ELECTRONICA */
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="NOTA DE DEBITO ELECTRONICA">
class Class_Log_Datos_NDebito_Elect extends Class_Log_Datos_Factura_Elect
{
    public $doc = 'NOTA DE DEBITO';
    public $tag = 'NOTASD';
    public $sri = '05';
    function createXmlNDebito($Doc_Cod, $Aut_Cod, $clave_acc, $obBD)
    {

        if ($this->sri != substr($clave_acc, 8, 2)) return null;
        $extra = $this->getRowConsultaSql("SELECT ventas.Vet_Obs,Prs_Tel,Prs_Te2,Prs_Cel,Cli_Cor,Prs_Dir FROM ventas INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod WHERE Vet_Cod='$Doc_Cod';", $obBD);
        try {
            $fact = array(
                'infoTributaria'             => $this->_infoTributaria($Aut_Cod, $clave_acc, $obBD),
                'infoNotaDebito'             => $this->_infoNotaDebito($Doc_Cod, $obBD),
                'detalles'                   => $this->_detalles_fact($Doc_Cod, $obBD), // solo esta para calcular los impuestos acumulados, se borra despues
                'motivos'                    => $this->_motivos($Doc_Cod, $obBD), //pendiente de programar
                'infoAdicional'              => $this->_infoAdicional(array('Email' => $extra['Cli_Cor'] . ' ', 'Teléfono' => $extra['Prs_Tel'] . ' - ' . $extra['Prs_Te2'], 'Dirección' => $extra['Prs_Dir'], 'Observación' => $extra['Vet_Obs']))
            );
            $fact['infoNotaDebito']['impuestos']['impuesto'] = $this->acumulaTotales($fact, true);
            $fact['motivos'] = array('motivo' => array('razon' => 'Nota Debito Factura', 'valor' => $fact['infoNotaDebito']['totalSinImpuestos']));
            $fact['detalles'] = null;
            $this->unsetOpcionales($fact, array('infoAdicional', 'detalles'));

            $xml = $this->toXML($fact, $this->tag);
            return $this->saveXml($xml, $clave_acc);
        } catch (Exception $e) {
            return null;
        }
    }
    // cabecera fact
    private function _infoNotaDebito($Doc_Cod, $obBD)
    {
        $emp = $this->getInfoEmpresa($obBD);
        $fact = $this->getRowConsultaSql($this->getSqlVentas($Doc_Cod), $obBD);
        if (empty($fact)) {
            return array();
        }
        $infoFact = array(
            'fechaEmision'                => $this->fDate($fact['Caj_Fec']),
            'dirEstablecimiento'          => $emp['Suc_Dir'], /*opcional*/
            'tipoIdentificacionComprador' => str_pad($fact['Ide_Prv'], 2, '0', STR_PAD_LEFT),
            'razonSocialComprador'        => $fact['Cliente'],
            'identificacionComprador'     => $fact['Prs_Ced'],
            'contribuyenteEspecial'       => $emp['Emp_Reg'], /*opcional*/
            'obligadoContabilidad'        => $emp['Emp_Cnt'] == 'S' ? 'SI' : 'NO', /*opcional*/
            'rise'                        => null,
            'codDocModificado'            => str_pad($fact['Vet_Ntd'], 2, '0', STR_PAD_LEFT),
            'numDocModificado'            => $fact['Vet_Nns'],
            'fechaEmisionDocSustento'     => $this->fDate($fact['Vet_Fdm']),
            'totalSinImpuestos'           => $fact['Importe_Descu'],
            'impuestos'                   => array('impuesto' => array()),
            'compensaciones'              => null,
            'valorTotal'                  => $fact['Total'],
            'pagos'                       => empty($fact['Tpc_Sri']) ? '' : array('pago' => array(0 => $this->_pago($fact['Tpc_Sri'], $fact['Total']))),
        );
        $this->clearArray($infoFact, array('dirEstablecimiento', 'obligadoContabilidad', 'contribuyenteEspecial', 'rise', 'compensaciones'));
        return $infoFact;
    }
    private function _motivos($Doc_Cod, $obBD)
    {
        return null;
    }
}
/* FIN NOTA DE DEBITO ELECTRONICA */
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="RETENCION ELECTRONICA">
class Class_Log_Datos_Retencion_Elect extends Class_Log_Datos_Elect
{
    public $doc = 'RETENCION';
    public $tag = 'RETENC';
    public $sri = '07';
    function createPdf($Doc_Cod, $obBD, $op = 'I')
    {
        $guia = $this->getRowConsultaSql("SELECT retencion.Ret_Cod,Ret_Aut,Ret_Xml,Ret_Sri,empresas.Emp_Cod,Cof_Ret 
        FROM retencion 
            INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod 
            INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod
            INNER JOIN empresas ON proveedore.Emp_Cod=empresas.Emp_Cod
            INNER JOIN confi_fact ON empresas.Emp_Cod=confi_fact.Emp_Cod
        WHERE retencion.Ret_Cod='$Doc_Cod';", $obBD);
        if (empty($guia)) return;
        //$this->echoLog($guia);
        $this->tag = $guia['Cof_Ret'] == '1.0' ? 'RETENC' : 'RETENC2';
        $emp = $this->getInfoEmpresa($obBD, null, $guia['Emp_Cod']);
        $nueva_elect = true;
        $Doc_Aut = $guia['Ret_Aut'];
        $offline = ($guia['Ret_Xml'] == $guia['Ret_Sri']);
        $urlXml = "../FRONT/$guia[Emp_Cod]/$guia[Ret_Xml]" . ($Doc_Aut == 'S' ? '_A' : '') . ".xml";
        $this->echoLog($guia);

        //Consulta para obtener el logo de las sucursales
        $data_suc = $this->getRowConsultaSql("SELECT s.Suc_Log
        FROM retencion cm
        JOIN autorizaci a ON cm.Aut_Cod = a.Aut_Cod
        JOIN puntos_imp pi ON pi.Pun_cod = a.Pun_Cod
        JOIN sucursal s ON s.Suc_Cod = pi.Suc_Cod
        WHERE cm.Ret_Cod ='$Doc_Cod';", $obBD);
        //Obtener el logo de las sucursales
        $logoUrl = empty($data_suc['Suc_Log']) ? $emp['Emp_Log'] : $data_suc['Suc_Log'];
        // $logoUrl = $emp['Emp_Log'];

        include_once '../COMPONENTES/' . $this->xml_tag[$this->tag]['pdf'];
        exit();
    }

    function createPdfByClave($clave, $obBD)
    {
        $guia = $this->getRowConsultaSql("SELECT retencion.Ret_Cod FROM retencion WHERE retencion.Ret_Xml='$clave';", $obBD);
        $this->createPdf($guia['Ret_Cod'], $obBD);
    }
    function createXmlRetencion($Doc_Cod, $Aut_Cod, $clave_acc, $obBD)
    {
        if ($this->sri != substr($clave_acc, 8, 2)) return null;

        $extra = $this->getRowConsultaSql("SELECT IF(Ret_Con IS NULL OR TRIM(Ret_Con)='',Cop_Obs,Ret_Con)AS Obs,Prs_Tel,Cof_Ret,Prs_Te2,Prs_Cel,IF(Prv_Cor IS NULL OR TRIM(Prv_Cor)='',Prs_Cor,Prv_Cor)as Prs_Cor,Prs_Dir FROM retencion
        INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod
        INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod 
        INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod 
        INNER JOIN empresas ON proveedore.Emp_Cod=empresas.Emp_Cod
        INNER JOIN confi_fact ON empresas.Emp_Cod=confi_fact.Emp_Cod
        WHERE Ret_Cod='$Doc_Cod';", $obBD);


        $this->tag = $extra['Cof_Ret'] === '1.0' ? 'RETENC' : 'RETENC2';
        try {
            $RetObs = $this->splitToLongField('Observación', $extra['Obs']);
            $fact = array(
                'infoTributaria'             => $this->_infoTributaria($Aut_Cod, $clave_acc, $obBD),
                'infoCompRetencion'          => $this->_infoCompRetencion($Doc_Cod, $obBD),
                ($this->tag == 'RETENC' ? 'impuestos' : 'docsSustento') => $this->tag == 'RETENC' ? $this->_impuestos($Doc_Cod, $obBD) : $this->_docsSustento($Doc_Cod, $obBD),
                //'impuestos'                  =>$this->_impuestos($Doc_Cod,$obBD),
                'infoAdicional'              => $this->_infoAdicional(array_merge(array('Email' => $extra['Prs_Cor'] . ' ', 'Teléfono' => $extra['Prs_Tel'] . ' - ' . $extra['Prs_Te2'], 'Dirección' => $extra['Prs_Dir']), $RetObs))
            );
            $this->unsetOpcionales($fact, array('infoAdicional'));

            $xml = $this->toXML($fact, $this->tag);
            return $this->saveXml($xml, $clave_acc);
        } catch (Exception $e) {
            return null;
        }
    }
    // cabecera fact
    private function _infoCompRetencion($Doc_Cod, $obBD)
    {
        $emp = $this->getInfoEmpresa($obBD);
        $fact = $this->getRowConsultaSql("SELECT Ret_Fec,IF(Cop_Ide=1,4,IF(Cop_Ide=2,5,6))as Ide_Prv,Cop_Fec,
            if(Cop_Ide='1',
                if(LENGTH(persona.Prs_Ced)<13,concat(persona.Prs_Ced,'001'),persona.Prs_Ced),
                if(Cop_Ide='2',
                    if(LENGTH(persona.Prs_Ced)>10,SUBSTRING(persona.Prs_Ced,1,10),persona.Prs_Ced),
                    persona.Prs_Ced  
                )
            )as Prs_Ced,
            IF(Prv_Tic='J','02','01')as prvTic,IF(Prv_Tic!='J' OR ( Prv_Tic='J' AND (Prv_Com IS NULL OR TRIM(Prv_Com)='')),CONCAT(Prs_Ape,' ',Prs_Nom),Prv_Com)AS Proveedor FROM retencion
            INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod
            INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod 
            INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod 
            INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod            
            WHERE Ret_Cod='$Doc_Cod';", $obBD);
        if (empty($fact)) {
            return array();
        }

        $fec = $this->fDate($fact['Ret_Fec']);
        $infoRet = array(
            'fechaEmision'                => $fec,
            'dirEstablecimiento'          => $emp['Suc_Dir'], /*opcional*/
            'contribuyenteEspecial'       => $emp['Emp_Reg'], /*opcional*/
            'obligadoContabilidad'        => $emp['Emp_Cnt'] == 'S' ? 'SI' : 'NO', /*opcional*/
            'tipoIdentificacionSujetoRetenido' => str_pad($fact['Ide_Prv'], 2, '0', STR_PAD_LEFT),
            'tipoSujetoRetenido'          => $fact['Ide_Prv'] == '06' || $fact['Ide_Prv'] == '08' ? $fact['prvTic'] : '',
            'parteRel'                    => $emp['Cof_Ret'] == '2.0' ? 'SI' : '',
            'razonSocialSujetoRetenido'   => $fact['Proveedor'],
            'identificacionSujetoRetenido' => $fact['Prs_Ced'],
            'periodoFiscal'               => substr($fact['Cop_Fec'],5,2).'/'.substr($fact['Cop_Fec'],0,4) // la Retencion se reporta con el mes de la compra
        );
        $this->unsetOpcionales($infoRet, array('tipoSujetoRetenido', 'parteRel'));
        $this->clearArray($infoRet, array('dirEstablecimiento', 'obligadoContabilidad', 'contribuyenteEspecial'));
        return $infoRet;
    }
    private function _docsSustento($Doc_Cod, $obBD)
    {
        $dets = $this->getArrayConsultaSql("SELECT compras.Cop_Cod,
            retencion.Ret_Cod,if(Ret_Imp = 'R','1','2')as Imp_Cod,
            if(det_retenc.Ret_Imp = 'I', CASE WHEN Ren_Por='10' THEN '9' WHEN Ren_Por='20' THEN '10' WHEN Ren_Por='30' THEN '1' WHEN Ren_Por='50' THEN '11' WHEN Ren_Por='70' THEN '2' WHEN Ren_Por='100' THEN '3' END, renta_iva.Ren_Sri ) AS Codigo,
            CAST(SUM(det_retenc.Ret_Bas) AS DECIMAL(14,2))as Ret_Bas, renta_iva.Ren_Por, (SUM(det_retenc.Ret_Bas*renta_iva.Ren_Por)/100) as Val_Ret,Tic_Sri,Cop_Num,Cop_Fec
        FROM renta_iva
            INNER JOIN det_retenc ON (renta_iva.Ren_Cod = det_retenc.Ren_Cod)
            INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
            INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod) INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
        WHERE retencion.Ret_Cod = '$Doc_Cod' AND retencion.Ret_Est = 'A' GROUP BY Ren_Sri;", $obBD);

        $com = $this->getArrayConsultaSql("SELECT Iva_Por,compras.*,tipo_compr.Tic_Sri,Tpc_Sri,Tri_Sri,Iva_Sri,Ice_Sri,Cop_Ice as Ice_Por,
		CAST( SUM(IF(Iva_Por = 0,  CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) , '0'))  AS decimal(20,6))AS sub_0,
		CAST( SUM(IF(Iva_Por != 0, CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) , '0'))  AS decimal(20,6))AS sub_12,
		CAST( SUM(  CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * (compras.Cop_Des/100))  AS decimal(20,2)) AS Descu,
		CAST( SUM(IF(Cop_Ice != 0, CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) ), 0))  AS decimal(20,6)) AS IceTot,
		CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) + CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) )  AS decimal(20,6) )*Iva_Por/100 ), 0)) AS decimal(20,6))  AS IvaTot,
		
		CAST( SUM((
				CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) /* IMPORTE */
				+ CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) ) /* ICE */
				+ ( CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) + CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) )  AS decimal(20,6) )*Iva_Por/100 ) /* IVA */ 
			)	  
		)  AS decimal(20,2)) AS total
		FROM compras
		INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)    
		INNER JOIN sustento ON (compras.Tri_Cod = sustento.Tri_Cod) 
        INNER JOIN producto ON det_compra.Pro_Cod = producto.Pro_Cod
        LEFT JOIN tipopagocom ON compras.Tpc_Cod = tipopagocom.Tpc_Cod
        LEFT JOIN ice ON producto.Ice_Int = ice.Ice_Int
		INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)    
		INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
		WHERE det_compra.Cop_Cod = " . $dets[0]['Cop_Cod'] . " GROUP BY iva.Iva_Cod,Ice_Sri", $obBD);

        if (empty($dets)) {
            return array();
        }

        $impuestoDocSustento = array();
        $arrIva = array();
        $arrIce = array();

        $arrIceTem = array();
        $sub0 = 0;
        $sub12 = 0;
        $total12 = 0;
        $codsri = '';
        $porsri = '';
        $total_pago = 0;
        $sin_impu = 0;
        foreach ($com as $d) {
            $total_pago += $d['total'];
            $sin_impu = $d['sub_0'] + $d['sub_12'];
            if (($d['sub_0'] * 1) === 0)
                $sub0 += $d['sub_0'] + $d['Desc'];
            if (($d['Iva_Por'] * 1) !== 0) {/*Impuesto Iva */
                $sub12 += number_format($d['sub_12'], 2, '.', '') + $d['IceTot'];
                $codsri = $d['Iva_Sri'];
                $porsri = $d['Iva_Por'];
                $total12 += $d['total'];
            }
            if (!!$d['Ice_Sri'] && ($d['Ice_Sri'] * 1) !== 0) {/*Impuesto Ice */
                array_push($arrIce, array(
                    'codImpuestoDocSustento'    => 3,
                    'codigoPorcentaje'            => $d['Ice_Sri'],
                    'baseImponible'                => number_format((float)$d['sub_12'], 2, '.', ''),
                    'tarifa'                    => $d['Ice_Por'],
                    'valorImpuesto'                => number_format((float)$d['IceTot'], 2, '.', ''),
                ));
            }
        }
        /*Impuestos de IVA*/
        $arrIva = array(
            'codImpuestoDocSustento'    => 2,
            'codigoPorcentaje'            => $codsri != '' ? $codsri : 2,
            'baseImponible'                => number_format((float)$sub12 != 0 ? $sub12 : '0.00', 2, '.', ''),
            'tarifa'                    => !empty($porsri) ? $porsri : 12,
            'valorImpuesto'                => number_format((float)($sub12 * $porsri) / 100, 2, '.', ''),
        );
        array_push($arrIce, $arrIva);
        $impuestoDocSustento = $arrIce;

        $impuestos = array('retencion' => array());
        foreach ($dets as $d) {
            $Ret_Bas = $d['Ret_Bas'];
            $Ren_Por = $d['Ren_Por'];
            $Val_Ret = $d['Val_Ret'];
            switch ($d['Codigo']) {
                case '322':
                    if ($Ren_Por * 1 == 0.1) {
                        $Ren_Por = 1;
                        $Ret_Bas = number_format((float)($Ret_Bas * 0.1), 2, '.', '');
                        $Val_Ret = number_format((float)($Ret_Bas * $Ren_Por / 100), 2, '.', '');
                    }
                    if ($Ren_Por * 1 == 0.175) {
                        $Ren_Por = 1.75;
                        $Ret_Bas = number_format((float)($Ret_Bas * 0.1), 2, '.', '');
                        $Val_Ret = number_format((float)($Ret_Bas * $Ren_Por / 100), 2, '.', '');
                    }
                    break;
            }
            $impu = array(
                'codigo'            => $d['Imp_Cod'],
                'codigoRetencion'   => $d['Codigo'],
                'baseImponible'     => $Ret_Bas,
                'porcentajeRetener' => $Ren_Por,
                'valorRetenido'     => number_format($Val_Ret, 2, '.', '')
            );
            array_push($impuestos['retencion'], $impu);
        }

        $docSustento = array('docSustento' => array(
            'codSustento'             => $com[0]['Tri_Sri'],
            'codDocSustento'         => str_pad($com[0]['Tic_Sri'], 2, "0", STR_PAD_LEFT),
            'numDocSustento'         => str_replace("-", "", $com[0]['Cop_Num']),
            'fechaEmisionDocSustento' => $this->fDate($com[0]['Cop_Fec']),
            'fechaRegistroContable'  => $this->fDate($com[0]['Cop_Fec']),
            'numAutDocSustento'         => $com[0]['Cop_Aut'],
            'pagoLocExt'             => '01',
            'totalSinImpuestos'         => number_format($sin_impu, 2, '.', ''),
            'importeTotal'             => number_format($total_pago, 2, '.', ''),
            'impuestosDocSustento'     => array('impuestoDocSustento' => $impuestoDocSustento),
            'retenciones'             => $impuestos,
            'pagos'                     => array('pago' => array('formaPago' => is_null($com[0]['Tpc_Sri']) || $com[0]['Tpc_Sri'] == '0' ? '01' : $com[0]['Tpc_Sri'], 'total' => number_format($total_pago, 2, '.', ''))),

        ));

        //$this->unsetOpcionales($docSustento, array('docSustento'));
        return $docSustento;
    }
    private function _impuestos($Doc_Cod, $obBD)
    {
        $dets = $this->getArrayConsultaSql("SELECT
                    retencion.Ret_Cod,if(Ret_Imp = 'R','1','2')as Imp_Cod,
                    if(det_retenc.Ret_Imp = 'I', CASE WHEN Ren_Por='10' THEN '9' WHEN Ren_Por='20' THEN '10' WHEN Ren_Por='30' THEN '1' WHEN Ren_Por='50' THEN '11' WHEN Ren_Por='70' THEN '2' WHEN Ren_Por='100' THEN '3' END, renta_iva.Ren_Sri ) AS Codigo,
                    CAST(SUM(det_retenc.Ret_Bas) AS DECIMAL(14,2))as Ret_Bas, renta_iva.Ren_Por,(SUM(det_retenc.Ret_Bas *renta_iva.Ren_Por)/100) as Val_Ret,Tic_Sri,Cop_Num,Cop_Fec
                FROM renta_iva
                    INNER JOIN det_retenc ON (renta_iva.Ren_Cod = det_retenc.Ren_Cod)
                    INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
                    INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod) INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
                WHERE retencion.Ret_Cod = '$Doc_Cod' AND retencion.Ret_Est = 'A' GROUP BY Ren_Sri, Ren_Por;", $obBD);
        if (empty($dets)) {
            return array();
        }

        $impuestos = array('impuesto' => array());
        foreach ($dets as $d) {
            $Ret_Bas = $d['Ret_Bas'];
            $Ren_Por = $d['Ren_Por'];
            $Val_Ret = $d['Val_Ret'];
            switch ($d['Codigo']) {
                case '322':
                    if ($Ren_Por * 1 == 0.1) {
                        $Ren_Por = 1;
                        $Ret_Bas = number_format((float)($Ret_Bas * 0.1), 2, '.', '');
                        $Val_Ret = number_format((float)($Ret_Bas * $Ren_Por / 100), 2, '.', '');
                    }
                    break;
            }
            $impu = array(
                'codigo'            => $d['Imp_Cod'],
                'codigoRetencion'   => $d['Codigo'],
                'baseImponible'     => $Ret_Bas,
                'porcentajeRetener' => $Ren_Por,
                'valorRetenido'     => number_format($Val_Ret, 2, '.', ''),
                'codDocSustento'    => str_pad($d['Tic_Sri'], 2, "0", STR_PAD_LEFT),
                'numDocSustento'    => str_replace("-", "", $d['Cop_Num']),
                'fechaEmisionDocSustento' => empty($d['Cop_Fec']) || $d['Cop_Fec'] == '0000-00-00' ? null : $this->fDate($d['Cop_Fec'])
            );
            $this->clearArray($impu, array('numDocSustento', 'fechaEmisionDocSustento'));
            array_push($impuestos['impuesto'], $impu);
        }
        $this->unsetOpcionales($impuestos, array('impuesto'));
        return $impuestos;
    }
    function getMailData($Doc_Cod, $obBD)
    {
        return $this->getRowConsultaSql("SELECT Ret_Cod,persona.Prs_Cod,Prs_Ced,Ret_Fec AS Fecha,Ret_Xml AS claveAcceso,CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Ret_Num AS CHAR),9,'0')) AS Secuencia,IF(Prv_Com IS NULL OR Prv_Com='',CONCAT(Prs_Ape,' ',Prs_Nom),Prv_Com)AS Destinatario,Ret_Aut AS Aut FROM retencion
            INNER JOIN autorizaci ON autorizaci.Aut_Cod=retencion.Aut_Cod
            INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
            INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
            INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod
            INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod WHERE Ret_Cod='$Doc_Cod';", $obBD);
    }
}
/* FIN RETENCION ELECTRONICA */
// </editor-fold>
function getClassElect($type)
{
    $obBD_elect = null;
    switch ($type) {
        case 'VENTAS';
            $obBD_elect =  new Class_Log_Datos_Factura_Elect;
            break;
        case 'NOTASC';
            $obBD_elect =  new Class_Log_Datos_NCredito_Elect;
            break;
        case 'NOTASD';
            $obBD_elect =  new Class_Log_Datos_NDebito_Elect;
            break;
        case 'GUIAS';
            $obBD_elect =  new Class_Log_Datos_Guia_Elect;
            break;
        case 'RETENC';
            $obBD_elect =  new Class_Log_Datos_Retencion_Elect;
            break;
        case 'LIQUIDC';
            $obBD_elect =  new Class_Log_Datos_LiquidacionCompras_Elect;
            break;
            //default : exit();
    }
    return $obBD_elect;
}


// LIQUIDACION DE COMPRAS
class Class_Log_Datos_LiquidacionCompras_Elect extends Class_Log_Datos_Elect
{
    public $doc = 'LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS';
    public $tag = 'LIQUIDC';

    //Crear pdf de la liquidacion de compra

    function createPdf($Doc_Cod, $obBD, $op = 'I')
    {

        $guia = $this->getRowConsultaSql("SELECT compras.Cop_Cod,Cop_Est, Aut_Cop,Cop_Aut as Cop_Sri,Emp_Cod 
        FROM compras INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod 
        WHERE compras.Cop_Cod='$Doc_Cod';", $obBD);

        if (empty($guia)) return;
        //$this->echoLog($guia);
        $emp = $this->getInfoEmpresa($obBD, null, $guia['Emp_Cod']);
        $nueva_elect = true;
        $Doc_Aut = $guia['Aut_Cop'];
        // $Doc_Aut = "";
        $offline = ($guia['Cop_Aut'] == $guia['Cop_Sri']);
        $urlXml = "../FRONT/$guia[Emp_Cod]/$guia[Cop_Sri]" . ($Doc_Aut == 'S' ? '_A' : '') . ".xml";
        //Nuevo codigo para obrener el logo de las sucursales
        $data_suc = $this->getRowConsultaSql("SELECT s.Suc_Log
        FROM compras gr
        JOIN autorizaci a ON gr.Aut_Cod = a.Aut_Cod
        JOIN puntos_imp pi ON pi.Pun_cod = a.Pun_Cod
        JOIN sucursal s ON s.Suc_Cod = pi.Suc_Cod
        WHERE gr.Cop_Cod ='$Doc_Cod';", $obBD);
        $logoUrl = empty($data_suc['Suc_Log']) ? $emp['Emp_Log'] : $data_suc['Suc_Log'];
        include_once '../COMPONENTES/' . $this->xml_tag[$this->tag]['pdf'];
        exit();
    }


    // número de documento - codigo de autorizacion - clave de acceso
    function createXmlLiquidacionCompra($Doc_Cod, $Aut_Cod, $clave_acc, $obBD)
    {
        $extra = $this->getRowConsultaSql(" SELECT compras.Cop_Obs AS Obs  ,persona.Prs_Tel, Cof_Ret,Prs_Te2,Prs_Cel,   
        IF(Prv_Cor IS NULL OR TRIM(Prv_Cor)='',  Prs_Cor, Prv_Cor) AS Prs_Cor, Prs_Dir ,Cop_Cod
        FROM compras 
        INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod 
        INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod 
        INNER JOIN empresas ON proveedore.Emp_Cod=empresas.Emp_Cod
        INNER JOIN confi_fact ON empresas.Emp_Cod=confi_fact.Emp_Cod
        WHERE Cop_Cod='$Doc_Cod';", $obBD);
        $tipoPago = "";
        try {
            $LiqObs = $this->splitToLongField('Observación', $extra['Obs']); //observacion
            $liquidacion = array(
                'infoTributaria'         => $this->_infoTributaria($Aut_Cod, $clave_acc, $obBD),
                'infoLiquidacionCompra'  => $this->_infoLiquidacionCompra($extra['Cop_Cod'], $obBD),
                'detalles' =>  $this->_detalles_liquidacion($extra['Cop_Cod'], $obBD),
                'infoAdicional'          => $this->_infoAdicional(array_merge(array('Email' => $extra['Prs_Cor'] . ' ', 'Teléfono' => $extra['Prs_Tel'] . ' - ' . $extra['Prs_Te2'], 'Dirección' => $extra['Prs_Dir'], 'Pago' => $tipoPago, 'Artesano Calificado' => $extra['Art_Calif']), $LiqObs)),
            );
            $xml = $this->toXML($liquidacion, $this->tag);
            return $this->saveXml($xml, $clave_acc);
        } catch (Exception $e) {
            return null;
        }
    }
    //Informacion liquidacion de compra
    function _infoLiquidacionCompra($Doc_Cod, $obBD)
    {
        try {
            /* $fact = $this->getRowConsultaSql(" SELECT COALESCE(Cop_Ide,Ide_Prv)as Ide_Prv,Cop_Fec,
            if(Cop_Ide='1',
                if(LENGTH(Prs_Ced)<13,concat(Prs_Ced,'001'),Prs_Ced),
                if(Cop_Ide='2',
                    if(LENGTH(Prs_Ced)>10,SUBSTRING(Prs_Ced,1,10),Prs_Ced),Prs_Ced  
                )
            )as Prs_Ced,
            IF(Prv_Tic='J','02','01')as prvTic,
            IF(Prv_Tic!='J' OR ( Prv_Tic='J' AND (Prv_Com IS NULL OR TRIM(Prv_Com)='')),
            CONCAT(Prs_Ape,' ',Prs_Nom),Prv_Com) AS Proveedor 
            FROM compras
                    INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod 
                    INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod 
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod            
                WHERE Cop_Cod='$Doc_Cod';", $obBD);*/


            $fact = $this->getRowConsultaSql(" SELECT Ide_Prv,Prs_Ced,Cop_Fec,
            IF(Prv_Tic='J','02','01')as prvTic,
            IF(Prv_Tic!='J' OR ( Prv_Tic='J' AND (Prv_Com IS NULL OR TRIM(Prv_Com)='')),
            CONCAT(Prs_Ape,' ',Prs_Nom),Prv_Com) AS Proveedor 
            FROM compras
                    INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod 
                    INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod 
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod            
                WHERE Cop_Cod='$Doc_Cod';", $obBD);

            //Consultar los valores
            $com = $this->getArrayConsultaSql(" SELECT Iva_Por,compras.*,tipo_compr.Tic_Sri,Tpc_Sri,Tri_Sri,Iva_Sri,Ice_Sri,Cop_Ice as Ice_Por,
            CAST( SUM(IF(Iva_Por = 0,  CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) , '0'))  AS decimal(20,6))AS sub_0,
            CAST( SUM(IF(Iva_Por != 0, CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) , '0'))  AS decimal(20,6))AS sub_12,
            CAST( SUM(  CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * (compras.Cop_Des/100))  AS decimal(20,2)) AS Descu,
            CAST( SUM(IF(Cop_Ice != 0, CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) ), 0))  AS decimal(20,6)) AS IceTot,
            CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) + CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) )  AS decimal(20,6) )*Iva_Por/100 ), 0)) AS decimal(20,6))  AS IvaTot,
            CAST( SUM((
                    CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) /* IMPORTE */
                    + CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) ) /* ICE */
                    + ( CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) + CAST( CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,6) ) * compras.Cop_Des/100 )) AS decimal(20,6) ) *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,6) )  AS decimal(20,6) )*Iva_Por/100 ) /* IVA */ 
                )	  
            )  AS decimal(20,2)) AS total , 
            COALESCE((SELECT ABS(DATEDIFF(ccpp_pagar.Cpp_Sys, ccpp_pagar.Cpp_Ven)) FROM ccpp_pagar WHERE 
            compras.Cop_Cod = ccpp_pagar.Cop_Cod), 0 ) AS plazo, 
            'dias' AS unidadTiempo
            FROM compras
            INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)    
            INNER JOIN sustento ON (compras.Tri_Cod = sustento.Tri_Cod) 
            INNER JOIN producto ON det_compra.Pro_Cod = producto.Pro_Cod
            LEFT JOIN tipopagocom ON compras.Tpc_Cod = tipopagocom.Tpc_Cod
            LEFT JOIN ice ON producto.Ice_Int = ice.Ice_Int
            INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)    
            INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
             LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod = compras.Cop_Cod
            WHERE compras.Cop_Cod = " . $Doc_Cod . " GROUP BY iva.Iva_Cod,Ice_Sri", $obBD);
            if (empty($fact)) {
                return array();
            }
            $fec = $this->fDate($fact['Cop_Fec']);
            $infoRet = array(
                'fechaEmision'                => $fec,
                'tipoIdentificacionProveedor' => $fact['Ide_Prv'], // 04  05  Ruc::   Cedula::
                // 'tipoIdentificacionProveedor' => str_pad($fact['Ide_Prv'], 2, '0', STR_PAD_LEFT), // 04  05  Ruc::   Cedula::
                'razonSocialProveedor'        => $fact['Proveedor'],
                'identificacionProveedor'     => $fact['Prs_Ced'],
                'totalSinImpuestos'           => ($com[0]['sub_12'] > 0) ? $com[0]['sub_12'] : $com[0]['sub_0'], //Subtotal
                'totalDescuento'              => $com[0]['Descu'], //Descuento
                'totalConImpuestos'           =>  $this->_totalConImpuestos($Doc_Cod, $obBD),
                'importeTotal'                => $com[0]['total'],
                'moneda'                      => 'DOLAR',
                'pagos' =>  array('pago' => array(0 => $this->_pago($com[0]['Tpc_Sri'], $com[0]['total'], $com[0]['plazo'], $com[0]['unidadTiempo'])))
            );
        } catch (Exception $e) {
            return null;
        }
        return $infoRet;
    }

    //detalle liquidacion
    private function _detalles_liquidacion($Doc_Cod, $obBD)
    {
        $dets = $this->getArrayConsultaSql("SELECT det_compra.*,Cop_Des,Iva_Sri,Iva_Por,Ice_Sri,Ice_Por FROM det_compra
            INNER JOIN iva ON det_compra.Iva_Cod=iva.Iva_Cod
            INNER JOIN compras ON compras.Cop_Cod=det_compra.Cop_Cod
            INNER JOIN producto ON det_compra.Pro_Cod=producto.Pro_Cod
            INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
            LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
            WHERE det_compra.Cop_Cod='$Doc_Cod' ORDER BY Cop_Int;", $obBD);

        if (empty($dets)) {
            return array();
        }
        $detalles = array('detalle' => array());
        foreach ($dets as $d) {

            $Des = $d['Cop_Des'] * 1 == 0 ? 0 : round($d['Cop_Imp'] * $d['Cop_Des'] / 100, 2);
            $Imp = $d['Cop_Imp'] - $Des;
            $Ice = $d['Cop_Ice'] * 1 == 0 ? 0 : round($Imp * $d['Cop_Ice'] / 100, 2);
            $Irbpnr = 0;
            $Iva = $d['Iva_Por'] * 1 == 0 ? 0 : round(($Imp + $Ice) * $d['Iva_Por'] / 100, 2);
            $deta = array(
                'codigoPrincipal'     => $d['Pro_Cod'], /*opcional*/
                'codigoAuxiliar'   => !empty($d['Pro_Bar']) ? $d['Pro_Bar'] : $d['Pro_Cod'], /*opcional*/
                'descripcion'       => $d['Cop_Pro'],
                'cantidad'          => $d['Cop_Can'],
                'precioUnitario'          => formato_numero($d['Cop_Pru'], 2, 1),
                'descuento'   =>  formato_numero($Des, 2, 1),
                'precioTotalSinImpuesto'  => formato_numero($d['Cop_Imp'], 2, 1),
                'detallesAdicionales' => $this->_detallesAdicionales(array('Marca' => $d['Mar_Des'], 'Unidad' => $d['Uni_Des'])), /*opcional*/
                'impuestos'           => array('impuesto' => array(0 => $this->_impuesto('2', $d['Iva_Sri'], $d['Iva_Por'], ($Imp + $Ice), $Iva))), // IVA
            );
            $this->clearArray($deta, array('codigoPrincipal', 'codigoAuxiliar', 'detallesAdicionales'));
            array_push($detalles['detalle'], $deta);
        }
        $this->unsetOpcionales($detalles, array('detalle'));
        return $detalles;
    }

    //TOTAL CON IMPUESTOS
    private function _totalConImpuestos($Doc_Cod, $obBD)
    {
        /*$det =  $this->getArrayConsultaSql("SELECT det_compra.*, 
        Cop_Des,Iva_Sri,Iva_Por
        FROM det_compra
        INNER JOIN compras ON compras.Cop_Cod = det_compra.Cop_Cod
        INNER JOIN iva ON det_compra.Iva_Cod  = iva.Iva_Cod    
        WHERE det_compra.Cop_Cod = '$Doc_Cod' ORDER BY Cop_Int;", $obBD);*/

        $det =  $this->getArrayConsultaSql("SELECT ROUND(SUM(Cop_Imp), 2) AS  Cop_Imp, 
        Cop_Des,Iva_Sri,Iva_Por
        FROM det_compra
        INNER JOIN compras ON compras.Cop_Cod = det_compra.Cop_Cod
        INNER JOIN iva ON det_compra.Iva_Cod  = iva.Iva_Cod    
        WHERE det_compra.Cop_Cod = '$Doc_Cod' GROUP BY iva.Iva_Cod ORDER BY Cop_Int;", $obBD);

        $detalles = array('totalImpuesto' => array());
        foreach ($det as $com) {
            $totalConImpuestos = array(
                'codigo'            => 2,   //codigo del impuesto 2 = iva, 3 = ice, 5 = IRBPNR
                'codigoPorcentaje'  => ($com['Iva_Sri']), // codigo del porcentaje del iva
                'baseImponible'     =>  $com['Cop_Imp'], //subtotal
                'tarifa' => ($com['Iva_Por']), // tarifa del iva 12- 8 - 5
                //'valor'  => ($com['Cop_Imp'] * (($com['Iva_Por']) / 100))   // Total deL iva
                'valor'  => round($com['Cop_Imp'] * (($com['Iva_Por']) / 100), 2)   // Total deL iva
            );
            array_push($detalles['totalImpuesto'], $totalConImpuestos);
        }

        $this->unsetOpcionales($detalles, array('totalImpuesto'));
        return $detalles;
    }
    function getMailData($Doc_Cod, $obBD)
    {
        return $this->getRowConsultaSql("SELECT Cop_Cod,persona.Prs_Cod,Prs_Ced,Cop_Fec AS Fecha,Cop_Aut AS claveAcceso, Cop_Num  AS Secuencia,  IF(Prv_Com IS NULL OR Prv_Com='',CONCAT(Prs_Ape,' ',Prs_Nom),Prv_Com)AS Destinatario,Aut_Cop AS Aut FROM compras
            INNER JOIN autorizaci ON autorizaci.Aut_Cod=compras.Aut_Cod
            INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
            INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
            INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod 
            INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod WHERE Cop_Cod='$Doc_Cod';", $obBD);
    }
}
