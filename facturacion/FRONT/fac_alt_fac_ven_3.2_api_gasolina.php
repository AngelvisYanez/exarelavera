<?php

/**
 * @abstract Consumir xml de factuaras de estacion gasolinera
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de creación  2024-12/18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */

//$Ses_Dat_Dis = "gsl_chavez";
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_facturaVenta;

$data_facturas = array(
    "request" => array(
        "codeEncript" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJydWMiOiIwNzkxNzE3NjcxMDAxIiwiZXN0YWNpb24iOiJWSUNUT1JJQSIsImFtYmllbnRlIjoiMSIsImlkX2VzdGFjaW9uIjoiNTYifQ.pk8C_F7-Wa12ebwDxnUBFIPbJ5MFqXaYNqvIlKLobgc",
        "fechaInicial" => "2025-01-01",
        "fechaFinal" => "2025-01-01"
    )
);

$url = "http://www.facturacioncombustibles.com:8085/invoice/checkInvoiceClient?page=0&size=1";
$jsonData = json_encode($data_facturas);
$curl = curl_init();

curl_setopt_array($curl,  array(
    CURLOPT_URL => $url,  //CURLOPT_URL => "http://www.facturacioncombustibles.com:8085/invoice/checkInvoiceClient?page=1&size=1", // URL del servicio
    CURLOPT_RETURNTRANSFER => true, // Devuelve el resultado como una cadena del tipo curl_exec
    CURLOPT_FOLLOWLOCATION => true, // Sigue las redirecciones
    CURLOPT_ENCODING => "", // Decodifica la respuesta
    CURLOPT_MAXREDIRS => 10, // Máximo número de redirecciones
    CURLOPT_TIMEOUT => 6000, // Tiempo máximo de ejecución
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Versión HTTP
    CURLOPT_CUSTOMREQUEST => "POST", // Tipo de solicitud
    CURLOPT_POSTFIELDS => $jsonData, // Cuerpo de la solicitud
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json", // Tipo de contenido
        "Accept: application/json",
        "Content-Length: " . strlen($jsonData), // Longitud del contenido
    ),
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);
curl_close($curl);


if ($err) {
    echo "cURL Error: " . $err;
} elseif ($httpCode >= 400) {
    echo "HTTP Error: Código " . $httpCode;
    echo "\nRespuesta: " . $response;
} else {
    /* $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;*/

    header('Content-Type: text/json; charset=utf-8');
    $responseData = json_decode($response, true);
    print_r($responseData);
}













/*

if (isset($saveFactura_)) {
    set_time_limit(9500);
    ini_set('memory_limit', '1024M');
    $Ses_Dat_Dis = "gsl_chavez";
    $Ses_Emp_Cod = 570;
    $Ses_Suc_Cod = 692;

    //Variables iniciales
    $page = 0;
    $size = 1;
    $cont = 0;
    $warnings = array();
    $obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_con1 =  new Class_Log_Datos_facturaVenta;
    //borrar debug completo
    //$obBD_con1->echoLog($obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion));
    $hoy = date("Y-m-d");
    $hora = date("H:i:s");
    $mes = date("m");
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1); // Activa el registro de errores
    // ini_set('error_log', 'C:/Users/TRANSMISIONES/Downloads/Gasolinera_/log_gasolinera.log');
    // ini_set('error_log', '"C:\Users\USER\Downloads\erores\archivo_de_logs.log"');

    $configs = $obBD_con1->getRowConsulta(12, $Ses_Emp_Cod, $obBD_conexion);
    //Cargar datos de la API
    if (empty($date_ini)  &&  empty($date_fin)) {
        // $fechaayer = date('Y-m-d', strtotime('-1 day'));
        $fechaincio = date('Y-m-d');
        $fechafin = date('Y-m-d');
    } else {
        $fechaincio = $date_ini;
        $fechafin = $date_fin;
        //Las fechas solo pueden ser de tres dias de rango
        $diff = abs(strtotime($date_ini) - strtotime($date_fin)) / (60 * 60 * 24);
        //ChromePhp::log("Diferencia de fechas: " . $diff);
        if ($diff > 3) {
            $message['message'] = "Las fechas deben tener máximo un rango de tres dias.";
            echo json_encode($message);
            exit();
        }
    }

    if (empty($fechaincio)  &&  empty($fechafin)) {
        $message['message'] = "Las fechas estan vacias.";
        echo json_encode($message);
        exit();
    }
    // Carga de los dos ultimos dia
    //ChromePhp::log("Fecha inicio: " . $date_ini . "   Fecha fin: " . $date_fin . "  ---    " . $fechaincio . "   *** " . $fechafin);
    $data_facturas = array(
        "request" => array(
            "codeEncript" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJydWMiOiIwNzkxNzE3NjcxMDAxIiwiZXN0YWNpb24iOiJWSUNUT1JJQSIsImFtYmllbnRlIjoiMSIsImlkX2VzdGFjaW9uIjoiNTYifQ.pk8C_F7-Wa12ebwDxnUBFIPbJ5MFqXaYNqvIlKLobgc",
            "fechaInicial" => "$fechaincio",
            "fechaFinal" => "$fechafin"
        )
    );

    do {
        $url = "http://www.facturacioncombustibles.com:8085/invoice/checkInvoiceClient?page=$page&size=$size";
        $jsonData = json_encode($data_facturas);
        $curl = curl_init();
        curl_setopt_array($curl,  array(
            CURLOPT_URL => $url,  //CURLOPT_URL => "http://www.facturacioncombustibles.com:8085/invoice/checkInvoiceClient?page=1&size=1", // URL del servicio
            CURLOPT_RETURNTRANSFER => true, // Devuelve el resultado como una cadena del tipo curl_exec
            CURLOPT_FOLLOWLOCATION => true, // Sigue las redirecciones
            CURLOPT_ENCODING => "", // Decodifica la respuesta
            CURLOPT_MAXREDIRS => 10, // Máximo número de redirecciones
            CURLOPT_TIMEOUT => 6000, // Tiempo máximo de ejecución
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Versión HTTP
            CURLOPT_CUSTOMREQUEST => "POST", // Tipo de solicitud
            CURLOPT_POSTFIELDS => $jsonData, // Cuerpo de la solicitud
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json", // Tipo de contenido
                "Accept: application/json",
                "Content-Length: " . strlen($jsonData), // Longitud del contenido
            ),
        ));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error: " . $err;
        } elseif ($httpCode >= 400) {
            echo "HTTP Error: Código " . $httpCode;
            echo "\nRespuesta: " . $response;
        } else {
            $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
            $obBD_conIns = new Class_Log_Datos_facturaVenta;
            //  header('Content-Type: text/json; charset=utf-8');
            $responseData = json_decode($response, true);
            //$startTime = microtime(true); //PARA MEDIR EL TIEMPO DE EJECUCION

            if ($responseData['status'] == "OK") {
                if (isset($responseData['result']) && is_array($responseData['result']) && count($responseData['result']) > 0) {
                    foreach ($responseData['result'] as $index => $item) {
                        $cont++;
                        if (isset($item['xmlString'])) {
                            $xml = simplexml_load_string($item['xmlString']);
                            if ($xml !== false) {
                                //$totalFacturas++;
                                $fechaEmision = (string)$xml->infoFactura->fechaEmision;
                                $fecha = DateTime::createFromFormat('d/m/Y', $fechaEmision);
                                $Caj_Fec = $fecha->format('Y-m-d');
                                //Informacion adicional
                                $ced_vendedor = null;
                                if (isset($xml->infoAdicional)) {
                                    foreach ($xml->infoAdicional->campoAdicional as $infoAdi) {
                                        $nombre = (string)$infoAdi['nombre'];
                                        $valor = (string)$infoAdi;   // Valor del nodo
                                        $nombreLimpio = preg_replace('/[^a-zA-Z0-9_]/', '_', $nombre);
                                        $variables[$nombreLimpio] = $valor;
                                    }
                                    $ced_vendedor =  $variables["Identificacion_despachador"];
                                }
                                //ChromePhp::log($ced_vendedor);
                                //OBTENER EL CODIGO DEL VENDEDOR MEDIANTE SUCURSAL Y CEDULA DEL VENDEDOR 
                                $Prs_Cod_vendedor = $obBD_con1->getRowConsulta(1009, $Ses_Suc_Cod . '*' . $ced_vendedor, $obBD_conexion);
                                if (empty($Prs_Cod_vendedor["Prs_Cod"])) {
                                    //$obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . "El vendedor con cédula $ced_vendedor no esta registrado.", $obBD_conexionIns);
                                    registrarLogRegistros($Ses_Emp_Cod, "El vendedor con cédula $ced_vendedor no esta registrado.", $Ses_Dat_Dis);
                                    //ChromePhp::log("Vendedor no encontrado");
                                    continue;
                                }
                                //VERIFICAR SI EL VENDEDOR TIENE PERMISO DE REALIZAR LA VENTA
                                $vendedor = $obBD_con1->getRowConsulta(855, $Ses_Suc_Cod . '*' . $Prs_Cod_vendedor["Prs_Cod"], $obBD_conexion);
                                $cod_Prs_Cod = $vendedor['Prs_Cod'];
                                if (empty($cod_Prs_Cod)) {
                                    // $obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . "El vendedor con cédula $ced_vendedor no tiene permisos.", $obBD_conexionIns); 
                                    registrarLogRegistros($Ses_Emp_Cod, "El vendedor con cédula $ced_vendedor no tiene permisos.", $Ses_Dat_Dis);
                                    //ChromePhp::log("Vendedor no encontrado");
                                    continue;
                                }
                                //EDITAR EL EL PUNTO SRI Pun_Sri
                                $Pun_Sri = (string)$xml->infoTributaria->ptoEmi;
                                if (!empty($vendedor["Aut_Cod"])) {
                                    $editar_pun_sri = $obBD_con1->getRowConsulta(164, $vendedor["Aut_Cod"] . '*' . $Pun_Sri, $obBD_conexion);
                                }
                                $usuario = $obBD_con1->getRowConsulta(162, $cod_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
                                $Usu_Cod = $usuario["Usu_Cod"];
                                if (empty($Usu_Cod)) {
                                    // $obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . "No se puedo obtener el codigo del usuario, con cedula:" . $ced_vendedor, $obBD_conexionIns);
                                    registrarLogRegistros($Ses_Emp_Cod, "No se puedo obtener el codigo del usuario, con cedula:" . $ced_vendedor, $Ses_Dat_Dis);
                                    continue;
                                }
                                // Obtener el id del vendedor
                                $Vnd_Cod = $vendedor['Vnd_Cod'];
                                $Vnd_Cod_aux = null;
                                //Seccion para verificar si la caja ya fue aperturada
                                $rs_Punto = $obBD_con1->getRowConsulta(7, $cod_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
                                $rs_Caja = $obBD_con1->getRowConsulta(76, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexion);
                                if (empty($rs_Caja['Caj_Cod'])) {
                                    $obBD_conIns->operacionobBD(77, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexionIns);
                                    $Caj_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                                    if ($obBD_conIns->Error != 0) {
                                        // $obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . "No se pudo registrar la caja ", $obBD_conexionIns);  
                                        registrarLogRegistros($Ses_Emp_Cod,  "No se pudo registrar la caja ", $Ses_Dat_Dis);
                                        continue;
                                    }
                                } else {
                                    $Caj_Cod = $rs_Caja['Caj_Cod'];
                                }

                                $Aut_Sri = "";
                                $Vet_Num = ltrim(((string)$xml->infoTributaria->secuencial), 0);   //OBTENER EL ULTIMO CODIGO DE UNA VENTA
                                $Vet_Cod = "";
                                $Pun_Sri =  (string)$xml->infoTributaria->ptoEmi; //Punto SRI
                                $Vet_Xml = (string)$xml->infoTributaria->claveAcceso;
                                //ChromePhp::log("CLAVE DE ACCESO : " . $Vet_Xml);
                                $num_existe_gencod = $obBD_con1->getRowConsulta(1010, $Vet_Xml . '*' . $Vnd_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
                                if ($num_existe_gencod['total'] * 1 > 0) {
                                    $message_existe = "El documento No." . $Vet_Num . " ya existe!";
                                    //$obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . $message_existe, $obBD_conexionIns);
                                    registrarLogRegistros($Ses_Emp_Cod, $message_existe, $Ses_Dat_Dis);
                                    continue;
                                }
                                $claveAcceso = $Vet_Xml;
                                if (empty($claveAcceso)) { //Si existe un error llega aqui
                                    // $obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . "Error al generar Clave de Acceso del Comprobante Electrónico!" . $Vnd_Cod, $obBD_conexionIns);
                                    registrarLogRegistros($Ses_Emp_Cod,  "Error al generar Clave de Acceso del Comprobante Electrónico!", $Ses_Dat_Dis);

                                    continue;
                                }
                                $Vet_Aut = 'S';
                                $Tic_Sri = 1; //FACTURA
                                $rise = ($Tic_Sri * 1 == 2 || $Tic_Sri * 1 == 9); // rise, nota de venta
                                if ($rise) {
                                    $iva_cero = $obBD_con1->getRowConsulta(68, '0', $obBD_conexion);
                                }
                                //OBTENER INFORMACION DEL CLIENTE
                                $identificacionComprador = (string)$xml->infoFactura->identificacionComprador;
                                $data_cli = $obBD_con1->getRowConsulta(1011, $identificacionComprador . '*' . $Ses_Emp_Cod, $obBD_conexion);

                                if (empty($data_cli)) { //REGISTRA EL CLIENTE
                                    try {
                                        $dirEstablecimiento = isset($xml->infoFactura->dirEstablecimiento) ? (string)$xml->infoFactura->dirEstablecimiento : '';
                                        $contribuyenteEspecial = isset($xml->infoFactura->contribuyenteEspecial) ? (string)$xml->infoFactura->contribuyenteEspecial : '';
                                        $obligadoContabilidad = isset($xml->infoFactura->obligadoContabilidad) ? (string)$xml->infoFactura->obligadoContabilidad : '';
                                        $tipoIdentificacionComprado = isset($xml->infoFactura->tipoIdentificacionComprado) ? (string)$xml->infoFactura->tipoIdentificacionComprado : '';
                                        $razonSocialComprador = isset($xml->infoFactura->razonSocialComprador) ? (string)$xml->infoFactura->razonSocialComprador : '';
                                        $direccion = isset($xml->infoFactura->direccionComprador) ? (string)$xml->infoFactura->direccionComprador : '';
                                        $identificacionComprador = isset($xml->infoFactura->identificacionComprador) ? (string)$xml->infoFactura->identificacionComprador : '';
                                        if (strlen($identificacionComprador) === 10) {
                                            $Ide_Cod = 2;
                                        } elseif (strlen($identificacionComprador) === 13) {
                                            $Ide_Cod = 1;
                                        } else {
                                            $Ide_Cod = 7;
                                        }
                                        $data['Prs_Ced'] = $identificacionComprador;
                                        $data['Prs_Ape'] = ''; //(string)$xml->infoFactura->razonSocialComprador;
                                        $data['Prs_Nom'] = (string)$xml->infoFactura->razonSocialComprador;
                                        $data['Prs_Dir'] = $direccion;
                                        $data['Prs_Cor'] = $variables["Correo"]; //CORREO
                                        $data['Prs_Sex'] = 'M';
                                        $cod_ciu = $obBD_con1->getRowConsulta(157, $direccion, $obBD_conexion);
                                        $data['Ciu_Cod'] =  !empty($cod_ciu['Ciu_Cod']) ? $cod_ciu['Ciu_Cod'] : 3;
                                        $data['Ide_Cod'] =  $Ide_Cod;
                                        $data['Prs_Tel'] = 'NULL'; //Telf
                                        $data_persona = $obBD_con1->getRowConsulta(165, $identificacionComprador, $obBD_conexion);

                                        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
                                        if (empty($data_persona["Prs_Cod"])) {
                                            $obBD_con1->operacionobBD(3, $data, $obBD_conexion);
                                            $cod_pers = $obBD_conIns->insercionid($obBD_conexionIns);
                                            if ($obBD_con1->Error != 0) {
                                                //$obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . "Error al registrar persona", $obBD_conexionIns);
                                                registrarLogRegistros($Ses_Emp_Cod,  " Error al registrar persona ", $Ses_Dat_Dis);
                                                continue;
                                            }
                                        }
                                        $codigo_persona = !empty($cod_pers) ? $cod_pers : $data_persona["Prs_Cod"];
                                        if (!empty($codigo_persona)) {
                                            $data['Prs_Cod'] = $codigo_persona;
                                            $data['Cli_Tic'] = 'N'; //Telf
                                            $data['Cli_Cup'] = '0';
                                            $data['Cli_Ruf'] =  $identificacionComprador;
                                            $data['Cli_Fac'] = (string)$xml->infoFactura->razonSocialComprador;
                                            $data['Cli_Dir'] = $direccion;
                                            $data['Cli_Con'] = '';
                                            $data['Cli_Tip'] = 'R';
                                            $data['Cli_Cor'] =  $variables["Correo"];;
                                            $data['Emp_Cod'] = $Ses_Emp_Cod;
                                            $obBD_con1->operacionobBD(4, $data, $obBD_conexion);
                                            if ($obBD_con1->Error != 0) {
                                                // $obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . "Error al registrar cliente", $obBD_conexionIns);
                                                registrarLogRegistros($Ses_Emp_Cod,  " Error al registrar cliente ", $Ses_Dat_Dis);
                                                continue;
                                            }
                                        }
                                        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
                                        $data_cli = $obBD_con1->getRowConsulta(1011, $identificacionComprador . '*' . $Ses_Emp_Cod, $obBD_conexion);
                                    } catch (Exception $ex) {
                                        $obBD_con1->rollBack_nomsn($obBD_conexion);
                                        //$obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" . " Error: Existe un error " . $ex->getMessage(), $obBD_conexionIns);
                                        registrarLogRegistros($Ses_Emp_Cod,  " Error: Existe un error " . $ex->getMessage(), $Ses_Dat_Dis);
                                        //ChromePhp::log($ex->getMessage());
                                        continue;
                                    }
                                }

                                //REGISTRAR LA FACTURA
                                try {
                                    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
                                    $Cli_Cod = $data_cli['Cli_Cod'];
                                    $Ciu_Cod = $data_cli['Ciu_Cod'];
                                    $Vet_Obs = "";
                                    $Autorizaci = $obBD_con1->getRowConsulta(48, $vendedor['Pun_Cod'] . '*' . $Tic_Sri, $obBD_conexion);
                                    $Vet_Des = (string)$xml->infoFactura->totalDescuento; //DESCUENTO
                                    $hora = date('H:i:s');
                                    $Ret_Num = NULL;
                                    $Ret_Fec = NULL;
                                    $Ret_Aut_Sri = NULL;
                                    $Vnd_Cod_aux = NULL;
                                    $Prf_Cod = NULL;
                                    if (isset($xml->infoFactura->pagos->pago)) {
                                        foreach ($xml->infoFactura->pagos->pago as $pago) {
                                            $formaPago = (string)$pago->formaPago;
                                            $total = (string)$pago->total;
                                            $plazo = (string)$pago->plazo;
                                            $unidadTiempo = (string)$pago->unidadTiempo;
                                        }
                                    }
                                    $Tpc_Cod = $formaPago;
                                    $encabezado_venta = array(
                                        'Tic_Cod' => (string) $xml->infoTributaria->codDoc,
                                        'Cli_Cod' => $Cli_Cod,
                                        'Ciu_Cod' => $Ciu_Cod,
                                        'Caj_Cod' => $Caj_Cod,
                                        'Vnd_Cod' => $Vnd_Cod,
                                        'Vet_Num' => $Vet_Num,
                                        'Vet_Obs' => $Vet_Obs,
                                        'Aut_Cod' => $Autorizaci["Aut_Cod"],
                                        'Vet_Des' => $Vet_Des, //Descuento
                                        'Vet_Hor' => $hora,
                                        'Vet_Xml' => (isset($claveAcceso) ? $claveAcceso : ''),
                                        'Vet_Aut' => (isset($Vet_Aut) ? $Vet_Aut : ''),
                                        'Ret_Num' =>  $Ret_Num,
                                        'Ret_Fec' =>  $Ret_Fec,
                                        'Ret_Aut' =>  $Ret_Aut_Sri,
                                        'Tpc_Cod' =>  $Tpc_Cod, //TIPO PAGO COMPROBANTE
                                        'Vet_Sri' => (isset($claveAcceso) ? $claveAcceso : ''),
                                        'Prf_Cod' => $Prf_Cod,
                                        'Vnd_Cod_Aux' => $Vnd_Cod_aux
                                    );
                                    $obBD_conIns->operacionobBD(140, $encabezado_venta, $obBD_conexionIns);
                                    $Vet_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
                                    //Array para el kardex
                                    $kardex = array('IoE' => 'E', 'Kar_Fec' => $Caj_Fec, 'Kar_Hor' => date("H:i:s"), 'Vet_Cod' => $Vet_Cod, 'Vnd_Cod' => $Vnd_Cod);
                                    $array_kardex = array();
                                    // echo "CODIGO DE VENTA :" . $Vet_Cod . "<br>";
                                    $s_add = true;
                                    if (isset($xml->detalles->detalle)) {
                                        foreach ($xml->detalles->detalle as $detalle) {

                                            $codigo_producto = (string)$detalle->codigoPrincipal;
                                            $data_producto = $obBD_con1->getRowConsulta(159, $codigo_producto . '*' . $Ses_Emp_Cod, $obBD_conexion);
                                            if (isset($detalle->impuestos->impuesto)) {
                                                foreach ($detalle->impuestos->impuesto as $impuesto) {
                                                    $codigo = (string)$impuesto->codigo;
                                                    $codigoPorcentaje = (string)$impuesto->codigoPorcentaje;
                                                    $tarifa = (float)$impuesto->tarifa;
                                                    $baseImponible = (float)$impuesto->baseImponible;
                                                    $valor = (float)$impuesto->valor;
                                                }
                                            }
                                            $iva_cod = $obBD_con1->getRowConsulta(156, $codigoPorcentaje . '*' . $tarifa, $obBD_conexion);




                                            //CONTROL DE INVENTARIO   $Tic_Sri = 1 es factura
                                            if (($Tic_Sri * 1 != 0 || (isset($configs['Cof_Stk']) && $configs['Cof_Stk'] == 'S')) && $data_producto['Adq_Cor'] == 'B') {
                                                if ($Tic_Sri * 1 != 1 || (isset($configs['Cof_Stk2']) && $configs['Cof_Stk2'] == 'S')) {
                                                    $s_add = true;
                                                    foreach ($array_kardex as &$k) {
                                                        if ($k['Pro_Cod'] == $codigo_producto) {
                                                            $s_add = false;
                                                            $k['Kar_Sal'] += (1) * (float)$detalle->cantidad;
                                                            $k['Kar_Ime'] += (1) * (float)$detalle->precioTotalSinImpuesto;
                                                            $k['Kar_Pre'] = $k['Kar_Ime'] / $k['Kar_Sal'];
                                                            break;
                                                        }
                                                    }
                                                    unset($k);
                                                    if ($s_add == true) {
                                                        $kardexIE = array_merge($kardex, array(
                                                            'Kar_Int' => $i + 1,
                                                            'Iva_Cod' => $item['Iva_Cod'],
                                                            'Pro_Cod' => $item['Pro_Cod'],
                                                            'Kar_Sal' => (1) * (float)$detalle->cantidad, //$obBD_conIns->CantidadStock($item['Pro_Cod'],$items),
                                                            'Kar_Pre' => (float)$detalle->precioUnitario * 1,
                                                            'Kar_Ime' => (1) * (float)$detalle->precioTotalSinImpuesto,
                                                        ));
                                                        array_push($array_kardex, $kardexIE);
                                                    }
                                                }
                                            }
                                            //ACTUALIZAR INVENTARIO
                                            foreach ($array_kardex as $k) {
                                                $obBD_conIns->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion, $obBD_conexionIns);
                                            }
                                            $Pro_Cod = $data_producto["Pro_Cod"];
                                            $item['Vet_Cod'] = $Vet_Cod;
                                            $item['Vet_Ite'] = $i + 1;
                                            $item['Pro_Cod'] = $Pro_Cod;
                                            $item['Vet_Can'] = (float)$detalle->cantidad;
                                            $item['Iva_Cod'] = $iva_cod['Iva_Cod']; //debe ser el de 15%  
                                            $item['Vet_Pru'] =  (float)$detalle->precioUnitario;
                                            $item['Vet_Imp'] = (float)$detalle->precioTotalSinImpuesto;
                                            $item['Vet_Dec'] = 0; //porcentaje de descuento
                                            $item['Nge_Cod'] = 0;
                                            $item['Asi_Int'] = 0;
                                            $item['Vet_Rec'] = 0;
                                            $item['Cnt_Cod'] = 0;
                                            $item['Vet_Int'] = 0;
                                            $item['Vet_Uni'] = 1;
                                            $item['Ren_Cod'] = null;
                                            $item['Des_Adi'] = null; //Descripcion adicional
                                            $item['Ren_Iva'] = null;
                                            $item['Vet_Ice'] = 0;
                                            $obBD_conIns->operacionobBD(86, $item, $obBD_conexionIns);
                                        }
                                    }
                                    //Registro venta
                                    if (isset($xml->infoFactura->pagos->pago)) {
                                        foreach ($xml->infoFactura->pagos->pago as $pago) {
                                            $pag['Vet_Num'] = $Vet_Num; //   $i++;
                                            $pag['Vet_Cod'] = $Vet_Cod;
                                            $pag['Bak_Cod'] = 1; // (empty($Par_Sql['Bak_Cod']) ? '1' : $Par_Sql['Bak_Cod']);
                                            $pag['Ban_Cod'] = null;
                                            $pag['Tipo_Cod'] = 1; // (string)$pago->formaPago; //Tipos pago //contado, credito etc.
                                            $pag['Vet_Cue'] = null;
                                            $pag['Vet_Che'] = null;
                                            $pag['Vet_Tot'] = (string)$pago->total;
                                            $pag['Pag_Pld'] = null; //Pld_Cod  = plan de cuentas
                                            $obBD_conIns->operacionobBD(72, $pag, $obBD_conexionIns);
                                            if ($pag['Tipo_Cod'] == '3') {
                                                $pag['Cliente'] = (string)$xml->infoFactura->razonSocialComprador;;
                                                $pag['Cli_Cod'] = $Cli_Cod;
                                                $pag['Fec_che'] = $Caj_Fec;
                                                $obBD_conIns->operacionobBD(145, $pag, $obBD_conexionIns);
                                                $Che_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
                                                $obBD_conIns->operacionobBD(146, array('Vet_Cod' => $Vet_Cod, 'Che_Cod' => $Che_Cod), $obBD_conexionIns);
                                            }
                                        }
                                    }
                                    $Check_Comprobante = 1;
                                    //OBTENER EL CODIGO DEL PERIODO ACTIVO
                                    $Pec_Cod = $obBD_con1->getRowConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                    $Pec_Cod = $Pec_Cod["Pec_Cod"];
                                    $t_rubros = (string)$xml->infoFactura->importeTotal;
                                    if ($configs['Cof_Con'] == 'S' && (($Tic_Sri * 1 != 0) || $Check_Comprobante * 1 === 1)) {
                                        $Com_Con = 'REG. VENTA ' . $Vet_Num;
                                        $Com_Fec = $Caj_Fec;
                                        $Tia_Asi = $obBD_con1->getRowConsulta(80, 7, $obBD_conexion);
                                        $meseCom = explode('-', $Com_Fec);
                                        $Com_Num = $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion); // Secuencia de comprobante por mes y por tipo
                                        $campo = 'Cli_Cod';
                                        $obBD_conIns->operacionobBD(163, $Pec_Cod . '*' . $Cli_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Com_Con) . '*' . $campo  . '*' . $Usu_Cod, $obBD_conexionIns);
                                        $Com_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
                                        //AQUI FALTA CODIGO DE LA SUCURSAL
                                        $obBD_conIns->operacionobBD(83, $Com_Cod . '*' . $Vet_Cod, $obBD_conexionIns); // relacion venta comprobante
                                        $Plan_Cod = $obBD_con1->getRowConsulta(161, $Ses_Emp_Cod, $obBD_conexion);
                                        $error = false;
                                        if (isset($xml->detalles->detalle)) {
                                            foreach ($xml->detalles->detalle as $detalle) {
                                                $Pro_Cod = (string)$detalle->codigoPrincipal;
                                                $Ite_Lar = (string)$detalle->descripcion;
                                                $Vet_Imp = (float)$detalle->precioUnitario;
                                                $Cod_Prod = $obBD_con1->getRowConsulta(159, $Pro_Cod . '*' . $Ses_Emp_Cod, $obBD_conexion);

                                                if (!empty($Cod_Prod["Pro_Cod"])) {
                                                    $cuenta = $obBD_con1->getRowConsulta(84, $Plan_Cod["Pla_Cod"] . '*' . $Cod_Prod["Pro_Cod"] . '*' . 'V', $obBD_conexion);
                                                    $item['Pld_Cod'] = $cuenta['Pld_Cod'];
                                                    $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . 'H' . '*' . $Vet_Imp . '*' . $cuenta['Pld_Des'] . '*' .  $Ite_Lar . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item    
                                                } else {
                                                    $mensaje = "Error: El producto :  $Ite_Lar ! no se encuentra o no existe la parametrización contable";
                                                    registrarLogRegistros($Ses_Emp_Cod, $mensaje, $Ses_Dat_Dis);
                                                    $error = true;
                                                    continue;
                                                }
                                            }
                                          
                                        }

                                        $t_iva = (float)$detalle->precioTotalSinImpuesto;
                                        if ($t_iva * 1 > 0) {
                                            $cuenta = $obBD_con1->getRowConsulta(88, $Plan_Cod["Pla_Cod"], $obBD_conexion);
                                            if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) {
                                                registrarLogRegistros($Ses_Emp_Cod, "Error: Revisar la parametrizacion contable de: Iva Cobrado!", $Ses_Dat_Dis);
                                                continue;
                                            }
                                            $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('H') . '*' . $t_iva . '*' . 'IVA' . '*' . 'IVA' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Iva
                                        }
                                        $Vet_Des = (float)$detalle->descuento;
                                        if ($Vet_Des > 0) {
                                            $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod["Pla_Cod"] . '*' . 'DV', $obBD_conexion);
                                            if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) {
                                                registrarLogRegistros($Ses_Emp_Cod, "Error:Revisar la parametrizacion contable de: Descuentos en Ventas!", $Ses_Dat_Dis);
                                                continue;
                                            }
                                            $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $t_descuento . '*' . 'DESCUENTO' . '*' . 'DESCUENTO' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
                                        }
                                        if (isset($xml->infoFactura->pagos->pago)) {
                                            foreach ($xml->infoFactura->pagos->pago as $pag) {
                                                $pag['Vet_Num'] = $Vet_Num; //   $i++;
                                                $pag['Vet_Tot'] = (string)$pago->total;
                                                $pag['Pag_Pld'] = null; //Pld_Cod  = plan de cuentas
                                                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $pag['Vet_Tot'] . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Iva
                                                $pag['Forma_Cod'] = 1;
                                                if ($pag['Forma_Cod'] * 1 == 2) {
                                                    $obBD_conIns->operacionobBD(55, $Com_Cod . '*' . $Vet_Cod . '*' . $pag['Cpc_Ven'] . '*' . (isset($pag['Cpc_Obs']) ? trim($pag['Cpc_Obs']) : ''), $obBD_conexionIns);
                                                    $Cpc_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                                                }
                                            }
                                        }
                                    }
                                } catch (Exception $ex) {
                                    $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
                                    registrarLogRegistros($Ses_Emp_Cod, "Error: Existe un error" . $ex->getMessage(), $Ses_Dat_Dis);
                                    continue;
                                }
                                $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
                                if ($obBD_conIns->Error == 0) {
                                    $Aut_Tem = 'E';
                                    $input_autorizacion = '';
                                    if ($Aut_Tem == 'E' && $input_autorizacion == '') {
                                        require_once('../LOGICA/fac_log_electronica.php');
                                        $obBD_elect =  new Class_Log_Datos_Factura_Elect();
                                        $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
                                        $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Autorizaci["Aut_Cod"], $obBD_conexion);
                                        $xml = $obBD_elect->createXmlFactura($Vet_Cod, $Autorizaci["Aut_Cod"], $claveAcceso, $obBD_conexion);
                                        $response['Vet_Xmls'] = baseUrl("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '.xml');
                                        $response['xml'] = base64_encode($xml);
                                    }
                                } else {
                                    registrarLogRegistros($Ses_Emp_Cod, 'No se pudo crear el xml de la factura: ' . $Vet_Num, $Ses_Dat_Dis);
                                    continue;
                                }
                                unset($xml);
                                unset($variables);
                                unset($ced_vendedor);
                                unset($fechaEmision, $fecha);
                                unset($nombre, $valor, $Caj_Cod);
                            }
                        }
                        //ChromePhp::log("LA LLAVE xmlString No esta definida");
                    }

                    $page++;
                    //ChromePhp::log("Verificando si $page es igual a 1.");
                    if ($page == 1) {
                        $message['message'] = "Proceso finalizado. Verifique si existen errores, luego de la importación!";
                        //ChromePhp::log("EL XML ESTA VACIO " . $cont);
                        echo json_encode($message);
                        exit();
                    }
                } else {
                    $message['message'] = "Proceso finalizado. Verifique si existen errores, luego de la importación!";
                    //ChromePhp::log("EL XML ESTA VACIO  PORQUE SE TERMINO " . $cont);
                    echo json_encode($message);
                    exit();
                }
            } else {
                $message['message'] = "No existen facturas.";
                //ChromePhp::log("No existen facturas");
                echo json_encode($message);
                exit();
            }
        }
        sleep(2);
    } while (true);
    $message['message'] = "Fallo el registro, vuelva a intentar mas tarde.";
    //ChromePhp::log("Es el final del final");
    exit();
}*/

function registrarLogRegistros($Ses_Emp_Cod, $mensaje, $Ses_Dat_Dis)
{
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
    $obBD_conIns->operacionobBD(166, $Ses_Emp_Cod . "*" .  $mensaje, $obBD_conexionIns);
}

//Cargar los errores
//$row_errores = $obBD_con1->getArrayConsulta(167, "", $obBD_conexion);      //Seccion para obtener los ivas de la tabla iva

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro gasolinera</title>
    <TITLE><?Php echo "Ventas Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
</head>

<body style="background-color: #f3f3f3;">
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Agregar fechas para cargar facturas</h3>
            <p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;"></p>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div title="Verificar errores">
                <form class="form-horizontal normal" id="send_form_save" action="javascript: addFactura();">
                    <input name="Ext_Cod" id="Ext_Cod" type="text" class="hidden" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2"> Seleccione fechas para cargar facturas </legend>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Fecha Inicio:</label>
                            <div class="col-xs-7">
                                <input name="date_ini" id="date_ini" type="date" class="form-control input-xs" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Fecha Final:</label>
                            <div class="col-xs-7">
                                <input name="date_fin" id="date_fin" type="date" class="form-control input-xs" />
                            </div>
                        </div>
                        <div class="col-xs-12 text-center">
                            <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
        <div id="resultados"></div>
        <hr>
        <br>
        <div class="col-md-12">
            <?php if (!empty($row_errores)) { ?>
                <h2>Advertencias:</h2>
                <ul>
                    <?php
                    $cont = 0;
                    foreach ($row_errores as $warning) {
                        $cont++; ?>
                        <li><?php echo $warning["Cod_Log"] . " " .  $warning["Des_Error"]; ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</body>
<script>
    function addFactura() {
        $.saveDataJson('', $('#send_form_save').getData('saveFactura'),
            function(resp) {
                console.log("finalizado con existo");
                // load_datos_extras();
                //return false;
            });
    }
</script>
<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>

</html>