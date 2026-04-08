<?php

/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */

require_once('../../Librerias/config.php/register_globals.php');
require_once('../../Librerias/fpdf/barCode_2.0.php');
require_once('../../Librerias/Xml/XML2Array.php');

if ((!isset($urlXml) && !isset($isText)) || (isset($urlXml) && !file_exists($urlXml))) {
    echo 'NO SE ENCONTRO EL XML';
    exit();
}
$offline = true;
$autorizado = false;
if (!isset($ruta)) $ruta = '';
if (!isset($nueva_elect) || (isset($nueva_elect) && (isset($xml_aut) || (isset($Doc_Aut) && $Doc_Aut == 'S')))) {
    $file = isset($isText) ? ($isText == true ? $xml_aut : file_get_contents($xml_aut)) : file_get_contents($urlXml);
    //$xml =new DOMDocument();
    $sri = XML2Array::createArray($file);
    if (isset($sri['autorizacion'])) {
        $Xml = XML2Array::createArray($sri['autorizacion']['comprobante']["@cdata"]);
        $datoXml = $Xml[key($Xml)];
        $num_autorizacion = $sri['autorizacion']['numeroAutorizacion'];
        $fec_autorizacion = substr($sri['autorizacion']['fechaAutorizacion']["@value"], 0, 10) . '  ' . substr($sri['autorizacion']['fechaAutorizacion']["@value"], 11, 8);
        $autorizado = true;
    } else {
        $datoXml = $sri[key($sri)];
        $num_autorizacion = $datoXml['infoTributaria']['claveAcceso'];
        $fec_autorizacion = "PENDIENTE";
    }
} else {
    $Xml = XML2Array::createArray(file_get_contents($urlXml));
    $datoXml = $Xml[key($Xml)];

    if (isset($offline) && $offline == true) {
        $num_autorizacion = $datoXml['infoTributaria']['claveAcceso'];
        $fec_autorizacion = "PENDIENTE";
    } else {
        $num_autorizacion = 'PENDIENTE';
        $fec_autorizacion = 'PENDIENTE';
    }
    $autorizado = false;
}

$pdf = new PDF_Code128(); // objeto pdf
$pdf->newMargin(); // margenes

$opPdf  = $op; //op=I presenta el Pdf, op=D Descarga el Pdf
$infTrib = $datoXml['infoTributaria'];
$infFac = $datoXml['infoFactura'];
$infRet = $datoXml['infoCompRetencion'];
$code   = $infTrib['claveAcceso'];
if ($autorizado == true) $offline = ($num_autorizacion == $code);

// Logo
$no_logo = dirname(__file__) . '/../COMPONENTES/no_logo.jpg';



if (isset($isText) && $isText && !empty($logoUrl) && isset($logoUrl['name']) && !empty($logoUrl['name'])) {
    $target_path  =  '../../imagenes/'  .  $code . '_' . basename($logoUrl['name']);
    $logoUrl = (move_uploaded_file($logoUrl['tmp_name'], $target_path)) ? $target_path : '';
}

//echo $logoUrl;exit;

$logoUrl = (!isset($logoUrl) || empty($logoUrl) || (!file_exists($logoUrl) || !is_file($logoUrl)) ? $no_logo : $logoUrl);
$pdf->centerImageFile($logoUrl, 10, 7, 112, 36); // $pdf->Image($logoUrl,13,13,000); // antes
if (isset($isText) && $isText && $logoUrl != $no_logo) {
    unlink($logoUrl);
}

//Consultar la fecha de emision = fecha de firma de documento

//Nueva consulta para obtener el nombre de la sucursal
/*
require_once('../LOGICA/fac_log_factura.php');
$obBD_con1 =  new Class_Log_Datos_facturaVenta;

//$obBD_conexion = new Class_Log_Conexion_facturaVenta;
$obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);*/
//$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
//MysqlDatos

//$conexion = new MysqlDatos();
//$fecha_firma = $obBD_con1->getRowConsultaSql("SELECT ven.Vet_Sys FROM ventas as ven WHERE ven.Vet_Xml =$infTrib[claveAcceso]", $obBD_conexionIns);

//var_dump($fecha_firma);
//echo $infTrib['claveAcceso'];

//Nueva consulta para obtener el nombre de la sucursal

//require_once('../LOGICA/fac_log_factura.php');


/*
$obBD_conexion = new Class_Log_Datos_Factura_Elect();
$conexion = new MysqlDatos();
$datos_empresa = $conexion->getRowConsultaSql("SELECT emp.Art_Calif FROM empresas AS emp
WHERE emp.Emp_Cod = $_SESSION[Ses_Emp_Cod] ", $obBD_conexion,true);

*/



// A,C,B sets   CODIGO DE BARRA CLAVE DE ACCESO
$pdf->SetFont('helvetica', '', 8);
$pdf->Code128(126, 80, $code, 77, 10);
$pdf->SetXY(125, 90);
$pdf->Write(4, $code);

/* Config textos  */
$pdf->SetFont('Arial', '', 9);
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);

/*RECTANGULO INFO COMPROBANTE*/
$pdf->RoundedRect(125, 10, 79, 86, 3.5, '123', '');
/* DATOS INFO COMPROBANTE */
$pdf->SetFont('Arial', 'B', 9);
$pdf->Text(128, 18, "R.U.C.:");
$pdf->SetFont('Arial', '', 9);
$pdf->Text(148, 18, $infTrib['ruc']);
//var_dump($infTrib);
$pdf->setField(128, 31, "No:", 'B');
$pdf->SetTextColor(194, 8, 8);
$pdf->setField(148, 31, "$infTrib[estab]-$infTrib[ptoEmi]-$infTrib[secuencial]", '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->setField(128, 39, "NÚMERO DE AUTORIZACIÓN:", 'B');
if ($num_autorizacion == 'PENDIENTE') $pdf->SetTextColor(194, 8, 8);
$pdf->setField(128, 44, $num_autorizacion, '', (isset($offline) && $offline == true ? 7.7 : 9));
$pdf->SetTextColor(0, 0, 0);

//Validar que la factura esta firmada
if ($fec_autorizacion == 'PENDIENTE') {
    $pdf->setField(128, 52, "FACTURA FIRMADA:", 'B');
    $pdf->setField(128, 57, $infFac['fechaEmision']);
    $pdf->SetTextColor(0, 0, 0);
} else {

    if (!empty($fec_autorizacion)) {
        $pdf->setField(128, 52, "FECHA Y HORA DE AUTORIZACIÓN:", 'B');
        if ($fec_autorizacion == 'PENDIENTE') $pdf->SetTextColor(194, 8, 8);
        $pdf->setField(128, 57, $fec_autorizacion);
        $pdf->SetTextColor(0, 0, 0);
    }
}

$pdf->setField(128, 65, "AMBIENTE:", 'B');
$pdf->setField(148, 65, $infTrib['ambiente'] * 1 == 1 ? 'PRUEBAS' : 'PRODUCCIÓN');
$pdf->setField(128, 72, "EMISIÓN:", 'B');
$pdf->setField(148, 72, $infTrib['tipoEmision'] * 1 == 1 ? 'NORMAL' : 'INDISPONIBILIDAD DEL SISTEMA');
$pdf->setField(128, 79, "CLAVE DE ACCESO:", 'B');

/* RECTANGULO INFO EMPRESA*/
$pdf->RoundedRect(10, 46, 112, 50, 3.5, '14', 'DF');
/* DATOS INFO EMPRESA */
$pdf->setField(13, 55, $infTrib['razonSocial'], 'B', 8.3, null, 107);
$pdf->setField(13, 60, $infTrib['nombreComercial'], '', 9, null, 107);
$pdf->setField(13, 70, "DIRECCIÓN:", 'B', 8);
$pdf->setField(38, 70, $infTrib['dirMatriz'], '', 8, null, 81);

$pdf->setField(13, 75, "DIR. SUCURSAL:", 'B', 8);
$pdf->setField(13, 80, "CONTRIBUYENTE ESPECIAL Nro.:", 'B', 8);
$pdf->setField(13, 85, "OBLIGADO A LLEVAR CONTABILIDAD:", 'B', 8);
/*

//$pdf->setField(13,90,$infTrib['dirMatriz'],'',8,null,81);
/*
array(14) { ["fechaEmision"]=> string(10) "16/07/2020" ["dirEstablecimiento"]=> string(22) "TRES CERRITOS / PASAJE" ["obligadoContabilidad"]=> string(2) "SI" ["tipoIdentificacionComprador"]=> string(2) "04" ["razonSocialComprador"]=> string(11) "MIDAJA S.A." ["identificacionComprador"]=> string(13) "0992695080001" ["direccionComprador"]=> string(40) "AV. CARLOS JULIO AROSEMENA SOLAR 37 Y SN" ["totalSinImpuestos"]=> string(6) "752.00" ["totalDescuento"]=> string(4) "0.00" ["totalConImpuestos"]=> array(1) { ["totalImpuesto"]=> array(4) { ["codigo"]=> string(1) "2" ["codigoPorcentaje"]=> string(1) "2" ["baseImponible"]=> string(6) "752.00" ["valor"]=> string(5) "90.24" } } ["propina"]=> string(4) "0.00" ["importeTotal"]=> string(6) "842.24" ["moneda"]=> string(5) "DOLAR" ["pagos"]=> array(1) { ["pago"]=> array(2) { ["formaPago"]=> string(2) "20" ["total"]=> string(6) "842.24" } } } FPDF error: Some data has already been output, can't send PDF file
*/
/*if($infTrib['regimenMicroempresas']){
    $pdf->setField(13,90,$infTrib['regimenMicroempresas'],'B',8,null,81);
}*/

if ($infTrib['contribuyenteRimpe']) {
    if ($infTrib['contribuyenteRimpe'] == "CONTRIBUYENTE RÉGIMEN RIMPE") {
        $pdf->setField(13, 90, $infTrib['contribuyenteRimpe'] . " - EMPRENDEDOR", 'B', 8, null, 81);
    } else {
        $pdf->setField(13, 90, $infTrib['contribuyenteRimpe'], 'B', 8, null, 81);
    }
    // $pdf->setField(13, 90, $infTrib['contribuyenteRimpe'], 'B', 8, null, 81);
} else {
    $pdf->setField(13, 90, "RÉGIMEN GENERAL", 'B', 8, null, 81);
}

if ($infTrib['agenteRetencion']) {
    $agenteRetencion = "Agente de Retencion No. Resolucion " . $infTrib['agenteRetencion'];
    if ($infTrib['contribuyenteRimpe']) {
        $pdf->setField(13, 95, $agenteRetencion, 'B', 8, null, 81);
    } else {
        $pdf->setField(13, 95, $agenteRetencion, 'B', 8, null, 81);
    }
}

/*
$pdf->setField(13, 90, "NÚMERO DE ARTESANO:", 'B', 8);
$pdf->setField(50, 90, $datos_empresa["Art_Calif"]  , '', 8, null, 81);*/



/* FUNCIONES GLOBALES */
// campos indefinidos
function issetText($obj, $col, $def = '')
{
    return isset($obj[$col]) ? $obj[$col] : $def;
}
// Info Adicional
function infoAddi($pdf, $pos, $dato)
{
    $fec = '';
    if (!empty($infFac['fechaEmision'])) {
        $fec = $infFac['fechaEmision'];
    } else {
        $fec = $infRet['fechaEmision'];
    }
    $hoy = date("Y-m-d");
    $agentes = array(16, 6, 39, 275, 159, 271, 241, 44, 97, 227, 1, 260, 104, 258, 259, 28, 283, 303, 253, 254, 236, 231, 45, 125, 8, 211, 33, 7, 20, 10, 22, 229, 25, 295, 26, 183, 126, 69, 27, 163, 162);
    $agem = array(44, 227, 1, 158, 260, 104, 238);
    $microempresas = array(302, 173, 305, 187, 306, 14, 309, 70, 152, 171, 54, 15, 135, 210, 127, 58, 34, 82, 120);

    $ancho = '';
    foreach ($agentes as $ag) {
        if ($_SESSION['Ses_Emp_Cod'] == $ag) {
            $ancho = 4;
            break;
        }
    }
    foreach ($microempresas as $m) {
        if ($_SESSION['Ses_Emp_Cod'] == $m) {
            $ancho = 5;
            break;
        }
    }
    foreach ($agem as $a) {
        if ($_SESSION['Ses_Emp_Cod'] == $a) {
            $ancho = 7;
            break;
        }
    }

    if ($pos < 12) $pos = 12;
    $pdf->setY($pos + 2);
    $pdf->setField(14, null, "INFORMACIÓN ADICIONAL", 'B', 9, 3);
    $pdf->RoundedRect(10, $pdf->GetY() - 7, 125, 6, 0, '');

    $pdf->SetCWidths(array(25, 100));
    $pdf->SetCAligns(array());
    $pdf->SetCFonts(array(array('Arial', 'B', 8), array('Arial', '', 8)));
    $pdf->RectCof(10, $pdf->GetY() - 1, 125, 1, 'LR');
    foreach ($pdf->formatArray($dato) as $v) {
        $h = $pdf->Row(array(strtoupper($v['@attributes']['nombre']) . (trim($v['@attributes']['nombre']) == '' ? '' : ':'), $v['@value'] . ''));
        $pdf->RectCof(10, $pdf->GetY() - $h, 125, $h, 'LR');
    }
    if ($ancho != 1) {
        $ancho = $ancho;
    } else {
        $ancho = 1;
    }
    $pdf->RectCof(10, $pdf->GetY(), 125, $ancho, 'LRB');
    //foreach($agentes as $ag){
    //if($ag==$_SESSION['Ses_Emp_Cod'] ){
    //$pdf->setField(11,$pdf->GetY()+3,"Agente de Retenci�n Resoluci�n NAC-DNCRASC20-00000001",'B',9,3);
    //$pdf->setField(11,$pdf->GetY()+3,"Contribuyente R�gimen Microempresas",'B',9,3);
    //}	
    //}
    //foreach($microempresas as $m){
    //if($m==$_SESSION['Ses_Emp_Cod']  ){
    //$pdf->setField(11,$pdf->GetY()+3,"Contribuyente R�gimen Microempresas",'B',9,3);
    //}	
    //}
    //foreach($agem as $a){
    //if($a==$_SESSION['Ses_Emp_Cod'] ){
    //$pdf->setField(11,$pdf->GetY()+3,"Agente de Retenci�n Resoluci�n NAC-DNCRASC20-00000001",'B',9,3);
    //$pdf->setField(11,$pdf->GetY()+3,"Contribuyente R�gimen Microempresas",'B',9,3);
    //}	
    //}

}
// Pagos documentos
function pagosDoc($pdf, $pos, $dato)
{
    $tipos = array(
        '01' => "SIN UTILIZACION DEL SISTEMA FINANCIERO",
        '15' => "COMPENSACIÓN DE DEUDAS",
        '16' => "TARJETA DE DÉBITO",
        '17' => "DINERO ELECTRÓNICO",
        '18' => "TARJETA PREPAGO",
        '19' => "TARJETA DE CRÉDITO",
        '20' => "OTROS CON UTILIZACION DEL SISTEMA FINANCIERO",
        '21' => "ENDOSO DE TÍTULOS"
    );
    if ($pos < 12) $pos = 12;
    $pdf->setY($pos + 2);
    $pdf->SetCWidths(array(7, 75, 23, 20));
    $pdf->SetCAligns('C');
    $pdf->SetCFonts(array(array('Arial', 'B', 7), array('Arial', 'B', 8), array('Arial', 'B', 8), array('Arial', 'B', 8)));
    $pdf->Row(array('COD', 'FORMA DE PAGO', 'VALOR', 'PLAZO'), true, false);
    $pdf->SetCFonts(array(array('Arial', 'B', 8), array('Arial', '', 8), array('Arial', '', 8), array('Arial', '', 8)));
    $pdf->SetCAligns(array('C', 'L', 'R', 'C'));
    foreach ($pdf->formatArray($dato) as $v) {
        $valor_plazo = issetText($v, 'plazo') ? issetText($v, 'plazo') : 0;
        $unidad_tiempo = $valor_plazo == 1 ? "dia" : issetText($v, 'unidadTiempo');
        $pdf->Row(array($v['formaPago'], issetText($tipos, $v['formaPago'], 'NO DEFINIDO'), $v['total'], $valor_plazo /*issetText($v,'plazo')*/ . ' ' . $unidad_tiempo/*issetText($v,'unidadTiempo')*/), true, false);
    }
}
// Detalles adicionales productos
function detallesAdi($pdf, $deta, $key = 'detallesAdicionales')
{
    $str = ' ';
    if (isset($deta[$key]))
        foreach ($pdf->formatArray($deta[$key]['detAdicional']) as $v)
            //Agrega la Marca y la Unidad de venta en el item Ejemplo: zapatos[MARCA:NIKE][UNIDAD:UNIDAD]
            // $str.=" [ ".$v['@attributes']['nombre'].":".$v['@attributes']['valor']." ] ";
            return $str;
}

// Detalles adicionales guia remision
function obtenerUnidad_guia($deta, $key = 'detallesAdicionales') {
    if (isset($deta[$key]) && isset($deta[$key]['detAdicional'])) {
        $detAdicionales = is_array($deta[$key]['detAdicional']) ? $deta[$key]['detAdicional']  : array($deta[$key]['detAdicional']);
        foreach ($detAdicionales as $v) {
            if ( isset($v['@attributes']['nombre']) && $v['@attributes']['nombre'] === 'Unidad'  ) {
                return $v['@attributes']['valor'];
            }
        }
    }
    return ''; // Si no se encuentra la unidad
}

// Seleccionar tipo de documento
function selectDoc($cod)
{
    $docs = array(
        '01' => "FACTURA",
        '02' => "NOTA O BOLETA DE VENTA",
        '03' => "LIQUIDACION DE COMPRAS",
        '04' => "NOTA DE CREDITO",
        '05' => "NOTA DE DEBITO",
        '16' => "FORMULARIO UNICO DE EXPORTACION",
        '17' => "DOCUMENTO UNICO DE IMPORTACION",
        '22' => "RECAP",
        '40' => "RENDIMIENTO FINANCIERO",
        '42' => "42",
    );
    return issetText($docs, $cod, 'NO DEFINIDO');
}
function selectIvas($por)
{
    $docs = array(
        '100' => "731",
        '70' => "729",
        '50' => "727",
        '30' => "725",
        '20' => "723",
        '10' => "721"
    );
    return issetText($docs, $por, 'NO DEFINIDO');
}

// Función para corregir la codificación de caracteres especiales
// function fixEncoding($text){
//     if (empty($text)) return $text;
//     // Convertir a string explícitamente si es un objeto SimpleXML
//     $text = (string)$text;
//     // Reemplazo manual para &apos; y &quot;
//     $text = str_replace(array('&apos;', '&quot;'), array("'", '"'), $text);
//     // Decodificar otras entidades HTML/XML
//     $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
//     // Detectar si el texto está en UTF-8 y convertir a ISO-8859-1
//     if (mb_detect_encoding($text, 'UTF-8', true) === 'UTF-8') {
//         return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
//     }
//     return $text;
// }
