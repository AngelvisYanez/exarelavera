<?php
require_once('../../Librerias/config.php/register_globals.php');
require_once('../../Librerias/fpdf/barCode.php');
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
$pdf->Text(128,25,utf8_decode("COMPROBANTE DE RETENCIÓN"));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,31,"No:");
$pdf->SetTextColor(194,8,8);
$pdf->SetFont('Arial','',9);
$pdf->Text(148,31,$datoXml->infoTributaria[0]->estab."-".$datoXml->infoTributaria[0]->ptoEmi."-".$datoXml->infoTributaria[0]->secuencial);

$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','B',9);
$pdf->Text(128,37,utf8_decode("NÚMERO DE AUTORIZACIÓN:"));
$pdf->SetFont('Arial','',7.7);
$pdf->Text(128,44,$sri->numeroAutorizacion);

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,51,utf8_decode("FECHA Y HORA DE AUTORIZACIÓN:"));
$pdf->SetFont('Arial','',9);
$pdf->Text(128,58,substr($sri->fechaAutorizacion,0,10).' '.substr($sri->fechaAutorizacion,11,8));

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
if (strlen($datoXml->infoTributaria[0]->razonSocial)<=42)
{
	$pdf->Text(13,67,utf8_decode($datoXml->infoTributaria[0]->razonSocial));
}else{
	$pdf->Text(13,67,utf8_decode($datoXml->infoTributaria[0]->nombreComercial));
}

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
$pdf->Text(70,88,$datoXml->infoCompRetencion[0]->contribuyenteEspecial);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,93,utf8_decode("OBLIGADO A LLEVAR CONTABILIDAD:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(70,93,$datoXml->infoCompRetencion[0]->obligadoContabilidad);

/*RECTANGULO INFO CLIENTE*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 98, 194, 13, 0, '');

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,103,utf8_decode("RAZÓN SOCIAL / NOMBRES Y APELLIDOS:"));
$pdf->SetFont('Arial','',8);
$numCad=strlen((string)$datoXml->infoCompRetencion[0]->razonSocialSujetoRetenido);
if($numCad>38)//controlamos el tamaño de la razon social q no sea mayor d 38 caracteres
{
	$pdf->Text(75,103,substr(utf8_decode($datoXml->infoCompRetencion[0]->razonSocialSujetoRetenido),1,$numCad));
}else{
	$pdf->Text(75,103,utf8_decode($datoXml->infoCompRetencion[0]->razonSocialSujetoRetenido));
}
$pdf->SetFont('Arial','B',8);
$pdf->Text(145,108,utf8_decode("RUC / CI:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(160,108,$datoXml->infoCompRetencion[0]->identificacionSujetoRetenido);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,108,utf8_decode("FECHA DE EMISIÓN:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,108,$datoXml->infoCompRetencion[0]->fechaEmision);


/*T A B L A */
//Títulos de las columnas

$pdf->AliasNbPages();
$pdf->SetY(113);
//Cabecera    	
$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,7,'Comprobante',1,0,'C');
$pdf->Cell(28,7,utf8_decode('Número'),1,0,'C');
$pdf->Cell(22,7,utf8_decode('Fecha Emisión'),1,0,'C');
$pdf->Cell(23,7,utf8_decode('Ejercicio Fiscal'),1,0,'C');
$pdf->Cell(22,7,'Base Imponible',1,0,'C');
$pdf->Cell(22,7,'Impuesto',1,0,'C');
$pdf->Cell(22,7,'Porcentaje %',1,0,'C');
$pdf->Cell(25,7,'Valor Retenido',1,0,'C');
$pdf->Ln();
$pdf->SetFont('Arial','',8);

$totDet= count($datoXml->impuestos[0]->impuesto);
$sumaTotal=0;
//Detalle
for($x=0;$x<=$totDet-1;$x++)
{   $sumaTotal+=((float)$datoXml->impuestos[0]->impuesto[$x]->baseImponible*(float)$datoXml->impuestos[0]->impuesto[$x]->porcentajeRetener)/100;
	if($datoXml->impuestos[0]->impuesto[$x]->codigo=='1')
	{
		$RentaIva='RENTA';
	}else{
		$RentaIva='IVA';
	}
	$pdf->Cell(30,5,'FACTURA',1,0,'C');
	$pdf->Cell(28,5,$datoXml->impuestos[0]->impuesto[$x]->numDocSustento,1);
	$pdf->Cell(22,5,$datoXml->impuestos[0]->impuesto[$x]->fechaEmisionDocSustento,1,0,'C');
	$pdf->Cell(23,5,$datoXml->infoCompRetencion[0]->periodoFiscal,1,0,'C');
	$pdf->Cell(22,5,number_format((float)$datoXml->impuestos[0]->impuesto[$x]->baseImponible,2),1,0,'C');
	$pdf->Cell(22,5,$RentaIva.'  '.$datoXml->impuestos[0]->impuesto[$x]->codigoRetencion,1,0,'');
	$pdf->Cell(22,5,$datoXml->impuestos[0]->impuesto[$x]->porcentajeRetener,1,0,'C');
	$pdf->Cell(25,5,number_format((float)$datoXml->impuestos[0]->impuesto[$x]->valorRetenido,2),1,0,'R');
	$pdf->Ln();
}
/*Total de la retencion*/
$pdf->SetFont('Arial','B',8);		
$pdf->Cell(144); 
$pdf->Cell(25,5,'TOTAL:',0,0,'R');
$pdf->Cell(25,5,number_format($sumaTotal,2),1,0,'R');

/*RECTANGULO INFO ADICIONAL*/
$iapos=$pdf->GetY()+10;
$iapos2=$iapos+5;

$pdf->SetFont('Arial','B',9);
$pdf->Text(13,$iapos2,utf8_decode("INFORMACIÓN ADICIONAL"));

$pdf->Ln($iapos-117-($totDet-1)*5);
$pdf->SetCWidths(array(30,97));
$pdf->SetCFonts(array(array('Arial','B',8)));
$pdf->SetFont('Arial','',8);

foreach($datoXml->infoAdicional->campoAdicional AS $k => $v){
	$pdf->Row(array(utf8_decode(strtoupper($v['nombre'])).':',utf8_decode($v).''));
}

$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, $iapos , 125, $pdf->GetY()-$iapos+2, 0, '');


$pdf->Ln();	
$pdf->SetFont('Arial','I',8);
$pdf->Text(100,290,utf8_decode('Página ').$pdf->PageNo().' de {nb}',0,0,'C');

//$pdf->TablaBasica($datos_xml);


$pdf->Output($code.'.pdf',$opPdf);
?>