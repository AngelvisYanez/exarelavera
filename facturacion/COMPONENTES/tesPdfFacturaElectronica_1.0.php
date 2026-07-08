<?php
require_once('../../Librerias/config.php/register_globals.php');
require_once('../../Librerias/fpdf/barCode.php');

// Función para corregir la codificación de caracteres especiales
function fixEncoding($text) {
    if (empty($text)) return $text;
	// Convertir a string explícitamente si es un objeto SimpleXML
	$text = (string)$text;
	// Reemplazo manual para &apos; y &quot;
	$text = str_replace(array('&apos;', '&quot;'), array("'", '"'), $text);
	// Decodificar entidades HTML
	$text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    // Detectar si el texto está en UTF-8 y convertir a ISO-8859-1
    if (mb_detect_encoding($text, 'UTF-8', true) === 'UTF-8') {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }
    return $text;
}

$pdf=new PDF_Code128();

$xml =new DOMDocument();
$sri = simplexml_load_file($urlXml);
$opPdf=$op; //op=I presenta el Pdf, op=D Descarga el Pdf
$factura = $sri->comprobante;
$datoXml = simplexml_load_string($factura);

/*Margenes*/
$pdf->SetRightMargin(5);
$pdf->SetLeftMargin(10);
$pdf->SetTopMargin(3);
$pdf->AddPage();

//A,C,B sets   CODIGO DE BARRA CLAVE DE ACCESO
$pdf->SetFont('helvetica','',8);
$code= $datoXml->infoTributaria[0]->claveAcceso;
$pdf->Code128(126,80,$code,77,10);
$pdf->SetXY(125,90);
$pdf->Write(4,$code);


// $pdf->Image($logoUrl,13,13,000); // antes
$pdf->Image($logoUrl,13,13,50,42,000);

/*RECTANGULO INFO COMPROBANTE*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(125, 10, 79, 86, 3.5, '');

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,18,"R.U.C.:");
$pdf->SetFont('Arial','',9);
$pdf->Text(148,18,$datoXml->infoTributaria[0]->ruc);

$pdf->SetFont('Arial','B',12);
$pdf->Text(128,25,"F A C T U R A");

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,31,"No:");
$pdf->SetFont('Arial','',9);
$pdf->Text(148,31,$datoXml->infoTributaria[0]->estab."-".$datoXml->infoTributaria[0]->ptoEmi."-".$datoXml->infoTributaria[0]->secuencial);

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,37,mb_convert_encoding("NÚMERO DE AUTORIZACIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',9);
$pdf->Text(128,44,$sri->numeroAutorizacion);

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,51,mb_convert_encoding("FECHA Y HORA DE AUTORIZACIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',9);
$pdf->Text(128,58,substr($sri->fechaAutorizacion,0,10).' '.substr($sri->fechaAutorizacion,11,8));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,65,"AMBIENTE:");
$pdf->SetFont('Arial','',9);
if($datoXml->infoTributaria[0]->ambiente==1)
{$ProDet='PRUEBAS';}else{$ProDet='PRODUCCIÓN';}
$pdf->Text(148,65,mb_convert_encoding($ProDet, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,72,mb_convert_encoding("EMISIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',9);
if($datoXml->infoTributaria[0]->tipoEmision==1)
{$EmiDet='NORMAL';}else{$EmiDet='INDISPONIBILIDAD DEL SISTEMA';}
$pdf->Text(148,72,mb_convert_encoding($EmiDet, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,79,mb_convert_encoding("CLAVE DE ACCESO:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',9);

/*RECTANGULO INFO EMPRESA*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 60, 112, 36, 3.5, '');


$pdf->SetFont('Arial','',12);
//$pdf->Text(13,67,mb_convert_encoding($datoXml->infoTributaria[0]->razonSocial, 'ISO-8859-1', 'UTF-8'));
if (strlen($datoXml->infoTributaria[0]->razonSocial)<=42)
{
	$pdf->Text(13,67,mb_convert_encoding($datoXml->infoTributaria[0]->razonSocial, 'ISO-8859-1', 'UTF-8'));
}else{
	$pdf->Text(13,67,mb_convert_encoding($datoXml->infoTributaria[0]->nombreComercial, 'ISO-8859-1', 'UTF-8'));
}
$pdf->SetFont('Arial','',9);
$pdf->Text(13,72,mb_convert_encoding($datoXml->infoTributaria[0]->nombreComercial, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,78,mb_convert_encoding("DIRECCIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(38,78,mb_convert_encoding($datoXml->infoTributaria[0]->dirMatriz, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,83,mb_convert_encoding("DIR. SUCURSAL:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(38,83,mb_convert_encoding("", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,88,mb_convert_encoding("CONTRIBUYENTE ESPECIAL Nro.:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(70,88,$datoXml->infoFactura[0]->contribuyenteEspecial);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,93,mb_convert_encoding("OBLIGADO A LLEVAR CONTABILIDAD:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(70,93,$datoXml->infoFactura[0]->obligadoContabilidad);

/*RECTANGULO INFO CLIENTE*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 98, 194, 13, 0, '');

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,103,mb_convert_encoding("RAZÓN SOCIAL:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(40,103, fixEncoding($datoXml->infoFactura[0]->razonSocialComprador));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,108,mb_convert_encoding("RUC / CI:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(30,108,$datoXml->infoFactura[0]->identificacionComprador);

$pdf->SetFont('Arial','B',8);
$pdf->Text(75,108,mb_convert_encoding("FECHA DE EMISIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(110,108,$datoXml->infoFactura[0]->fechaEmision);

$pdf->SetFont('Arial','B',8);
$pdf->Text(145,108,mb_convert_encoding("GUÍA DE REMISIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(175,108,$datoXml->infoFactura[0]->guiaRemision);

/*T A B L A */
//Títulos de las columnas

$pdf->AliasNbPages();
$pdf->SetY(113);
//Cabecera    	
$pdf->SetFont('Arial','B',8);
$pdf->Cell(15,7,'CODIGO',1);
$pdf->Cell(119,7,mb_convert_encoding('DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'),1,0,'C');
$pdf->Cell(20,7,'CANTIDAD',1,0,'C');
$pdf->Cell(20,7,'PRECIO U.',1,0,'C');
$pdf->Cell(20,7,'TOTAL',1,0,'C');
$pdf->Ln();
$pdf->SetFont('Arial','',8);

$totDet= count($datoXml->detalles[0]->detalle);
//Detalle
$iapos=125;
$iapos2=130;
for($x=0;$x<=$totDet-1;$x++)
{
	
	$pdf->Cell(15,5,$datoXml->detalles[0]->detalle[$x]->codigoPrincipal,1,0,'C');
	$pdf->Cell(119,5,mb_convert_encoding(substr($datoXml->detalles[0]->detalle[$x]->descripcion,0,70), 'ISO-8859-1', 'UTF-8'),1);
	$pdf->Cell(20,5,$datoXml->detalles[0]->detalle[$x]->cantidad,1,0,'C');
	$pdf->Cell(20,5,$datoXml->detalles[0]->detalle[$x]->precioUnitario,1,0,'R');
	$pdf->Cell(20,5,number_format((float)$datoXml->detalles[0]->detalle[$x]->cantidad * (float)$datoXml->detalles[0]->detalle[$x]->precioUnitario,2),1,0,'R');
	$pdf->Ln();
	$iapos+=5;
	$iapos2+=5;
}

$imp= count($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto);
/* iniciamos en cero las variables */
$baseImp0=0; $valorImp0=0;
$baseImp12=0; $valorImp12=0;
$baseNoObjeto=0; $valorNoObjeto=0;
$baseExento=0; $valorExento=0;
$valorIce=0;

for($x=0;$x<=2;$x++)
{
	if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigo=="2" || $datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigo=="3") /* IVA */
	{
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje=="0")  /* IVA 0%*/
		{
			$baseImp0=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorImp0=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje=="2") /* IVA 12%*/
		{
			$baseImp12=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorImp12=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje=="3") /* IVA 14%*/
		{
			$baseImp12=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorImp12=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje=="6") /* No Objeto de Impuesto */
		{
			$baseNoObjeto=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorNoObjeto=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje=="7") /* Exento de IVA */
		{
			$baseExento=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorExento=(float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}			
	}
	if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigo=="3") /* ICE */
	{
		$valorIce= $valorIce + (float)$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor; /* SUMA TODOS LOS ICE */		
	}
	
}


//totales
$alto_totales=200;
$pdf->Ln();
$pdf->SetFont('Arial','B',8);
$pdf->Cell(129);
$pdf->Cell(40,5,"SUBTOTAL 12%",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format($baseImp12,2),1,0,'R');
$pdf->Ln();
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"SUBTOTAL 0%",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format($baseImp0,2),1,0,'R');
$pdf->Ln();
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"SUBTOTAL NO SUJETO IVA",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format($baseNoObjeto,2),1,0,'R');
$pdf->Ln();
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"SUBTOTAL SIN IMPUESTO",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format((float)$datoXml->infoFactura[0]->totalSinImpuestos,2),1,0,'R');
$pdf->Ln();
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"DESCUENTO",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format((float)$datoXml->infoFactura[0]->totalDescuento,2),1,0,'R');
$pdf->Ln();
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"ICE",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format($valorIce,2),1,0,'R');
$pdf->Ln();
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"IVA 12%",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format($valorImp12,2),1,0,'R');

$pdf->Ln();
$total=((float)$datoXml->infoFactura[0]->totalSinImpuestos - (float)$datoXml->infoFactura[0]->totalDescuento)+ (float)$valorIce + (float)$valorImp12;
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"TOTAL",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format($total,2),1,0,'R');
$pdf->Ln();	
$pdf->SetFont('Arial','I',8);
$pdf->Text(100,290,mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$pdf->PageNo().' de {nb}',0,0,'C');

/*RECTANGULO INFO ADICIONAL*/
$pdf->SetFont('Arial','B',9);
$pdf->Text(13,$iapos2,mb_convert_encoding("INFORMACIÓN ADICIONAL", 'ISO-8859-1', 'UTF-8'));

$pdf->Ln($pdf->GetY()-$alto_totales-2);
$pdf->SetCWidths(array(30,97));
$pdf->SetCFonts(array(array('Arial','B',8)));
$pdf->SetFont('Arial','',8);

foreach($datoXml->infoAdicional->campoAdicional AS $k => $v){
	$pdf->Row(array(mb_convert_encoding(strtoupper($v['nombre']), 'ISO-8859-1', 'UTF-8').':',mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8').''));
}

$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, $iapos , 125, $pdf->GetY()-$iapos+2, 0, '');
 

//$pdf->TablaBasica($datos_xml);


$pdf->Output($code.'.pdf',$opPdf);
?>