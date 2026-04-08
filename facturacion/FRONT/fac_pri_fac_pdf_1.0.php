<?php
require_once('../../Librerias/fpdf/barCode.php');
$pdf=new PDF_Code128();

$xml =new DOMDocument();
$sri = simplexml_load_file($Ses_Emp_Cod.'6/FCT_083009_000002244_15022015_181520.xml');
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

$pdf->Image($Ses_Emp_Log,13,13,000);

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
$pdf->Text(128,37,utf8_decode("NÚMERO DE AUTORIZACIÓN:"));
$pdf->SetFont('Arial','',9);
$pdf->Text(128,44,$sri->numeroAutorizacion);

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,51,utf8_decode("FECHA Y HORA DE AUTORIZACIÓN:"));
$pdf->SetFont('Arial','',9);
$pdf->Text(128,58,$sri->fechaAutorizacion);

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,65,"AMBIENTE:");
$pdf->SetFont('Arial','',9);
if($datoXml->infoTributaria[0]->ambiente==1)
{$ProDet='PRUEBAS';}else{$ProDet='PRODUCCIÓN';}
$pdf->Text(148,65,utf8_decode($ProDet));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,72,utf8_decode("EMISIÓN:"));
$pdf->SetFont('Arial','',9);
if($datoXml->infoTributaria[0]->tipoEmision==1)
{$EmiDet='NORMAL';}else{$EmiDet='INDISPONIBILIDAD DEL SISTEMA';}
$pdf->Text(148,72,utf8_decode($EmiDet));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,79,utf8_decode("CLAVE DE ACCESO:"));
$pdf->SetFont('Arial','',9);

/*RECTANGULO INFO EMPRESA*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 60, 112, 36, 3.5, '');

$pdf->SetFont('Arial','',12);
$pdf->Text(13,67,utf8_decode($datoXml->infoTributaria[0]->razonSocial));

$pdf->SetFont('Arial','',9);
$pdf->Text(13,72,utf8_decode($datoXml->infoTributaria[0]->nombreComercial));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,78,utf8_decode("DIRECCIÓN:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(38,78,utf8_decode($datoXml->infoTributaria[0]->dirMatriz));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,83,utf8_decode("DIR. SUCURSAL:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(38,83,utf8_decode(""));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,88,utf8_decode("CONTRIBUYENTE ESPECIAL Nro.:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(70,88,$datoXml->infoFactura[0]->contribuyenteEspecial);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,93,utf8_decode("OBLIGADO A LLEVAR CONTABILIDAD:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(70,93,$datoXml->infoFactura[0]->obligadoContabilidad);

/*RECTANGULO INFO CLIENTE*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 98, 194, 13, 0, '');

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,103,utf8_decode("RAZÓN SOCIAL / NOMBRES Y APELLIDOS:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,103,utf8_decode($datoXml->infoFactura[0]->razonSocialComprador));

$pdf->SetFont('Arial','B',8);
$pdf->Text(145,103,utf8_decode("RUC / CI:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(175,103,$datoXml->infoFactura[0]->identificacionComprador);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,108,utf8_decode("FECHA DE EMISIÓN:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,108,$datoXml->infoFactura[0]->fechaEmision);

$pdf->SetFont('Arial','B',8);
$pdf->Text(145,108,utf8_decode("GUÍA DE REMISIÓN:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(175,108,$datoXml->infoFactura[0]->guiaRemision);

/*T A B L A */
//Títulos de las columnas

$pdf->AliasNbPages();
$pdf->SetY(113);
//Cabecera    	
$pdf->SetFont('Arial','B',8);
$pdf->Cell(15,7,'CODIGO',1);
$pdf->Cell(119,7,utf8_decode('DESCRIPCIÓN'),1,0,'C');
$pdf->Cell(20,7,'CANTIDAD',1,0,'C');
$pdf->Cell(20,7,'PRECIO U.',1,0,'C');
$pdf->Cell(20,7,'TOTAL',1,0,'C');
$pdf->Ln();
$pdf->SetFont('Arial','',8);

$totDet= count($datoXml->detalles[0]->detalle);
//Detalle
for($x=0;$x<=$totDet-1;$x++)
{
	
	$pdf->Cell(15,5,$datoXml->detalles[0]->detalle[$x]->codigoPrincipal,1,0,'C');
	$pdf->Cell(119,5,$datoXml->detalles[0]->detalle[$x]->descripcion,1);
	$pdf->Cell(20,5,$datoXml->detalles[0]->detalle[$x]->cantidad,1,0,'C');
	$pdf->Cell(20,5,$datoXml->detalles[0]->detalle[$x]->precioUnitario,1,0,'R');
	$pdf->Cell(20,5,(float)$datoXml->detalles[0]->detalle[$x]->cantidad * (float)$datoXml->detalles[0]->detalle[$x]->precioUnitario,1,0,'R');
	$pdf->Ln();
}

$imp= count($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto);
for($x=0;$x<=2;$x++)
{
	if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigo==2) /* IVA */
	{
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje==0)  /* IVA 0%*/
		{
			$baseImp0=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorImp0=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje==2) /* IVA 12%*/
		{
			$baseImp12=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorImp12=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje==6) /* No Objeto de Impuesto */
		{
			$baseNoObjeto=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorNoObjeto=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}
		if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigoPorcentaje==7) /* Exento de IVA */
		{
			$baseExento=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->baseImponible;
			$valorExento=$datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor;			
		}	
	}
	if ($datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->codigo==3) /* ICE */
	{
		$valorIce= $valorIce + $datoXml->infoFactura[0]->totalConImpuestos->totalImpuesto[$x]->valor; /* SUMA TODOS LOS ICE */
	}	
}

//totales
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
$pdf->Cell(25,5,number_format($datoXml->infoFactura[0]->totalSinImpuestos,2),1,0,'R');
$pdf->Ln();
$pdf->Cell(129);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(40,5,"DESCUENTO",1);
$pdf->SetFont('Arial','',8);
$pdf->Cell(25,5,number_format($datoXml->infoFactura[0]->totalDescuento,2),1,0,'R');
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
$pdf->Cell(25,5,$total,1,0,'R');
$pdf->Ln();	
$pdf->SetFont('Arial','I',8);
$pdf->Text(100,290,utf8_decode('Página ').$pdf->PageNo().' de {nb}',0,0,'C');

//$pdf->TablaBasica($datos_xml);


$pdf->Output("jose1.pdf","I");
?>