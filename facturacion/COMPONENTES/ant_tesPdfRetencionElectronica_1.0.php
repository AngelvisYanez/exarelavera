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
$pdf->Text(128,25,mb_convert_encoding("COMPROBANTE DE RETENCIÓN", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,31,"No:");
$pdf->SetTextColor(194,8,8);
$pdf->SetFont('Arial','',9);
$pdf->Text(148,31,$datoXml->infoTributaria[0]->estab."-".$datoXml->infoTributaria[0]->ptoEmi."-".$datoXml->infoTributaria[0]->secuencial);

$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','B',9);
$pdf->Text(128,37,mb_convert_encoding("NÚMERO DE AUTORIZACIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',7.7);
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
$pdf->Text(70,88,$datoXml->infoCompRetencion[0]->contribuyenteEspecial);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,93,mb_convert_encoding("OBLIGADO A LLEVAR CONTABILIDAD:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(70,93,$datoXml->infoCompRetencion[0]->obligadoContabilidad);

/*RECTANGULO INFO CLIENTE*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 98, 194, 13, 0, '');

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,103,mb_convert_encoding("RAZÓN SOCIAL / NOMBRES Y APELLIDOS:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$numCad=strlen((string)$datoXml->infoCompRetencion[0]->razonSocialSujetoRetenido);
if($numCad>38)//controlamos el tamaño de la razon social q no sea mayor d 38 caracteres
{
	$pdf->Text(75,103,substr(mb_convert_encoding($datoXml->infoCompRetencion[0]->razonSocialSujetoRetenido, 'ISO-8859-1', 'UTF-8'),1,$numCad));
}else{
	$pdf->Text(75,103,mb_convert_encoding($datoXml->infoCompRetencion[0]->razonSocialSujetoRetenido, 'ISO-8859-1', 'UTF-8'));
}
$pdf->SetFont('Arial','B',8);
$pdf->Text(145,108,mb_convert_encoding("RUC / CI:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(160,108,$datoXml->infoCompRetencion[0]->identificacionSujetoRetenido);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,108,mb_convert_encoding("FECHA DE EMISIÓN:", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,108,$datoXml->infoCompRetencion[0]->fechaEmision);


/*T A B L A */
//Títulos de las columnas

$pdf->AliasNbPages();
$pdf->SetY(113);
//Cabecera    	
$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,7,'Comprobante',1,0,'C');
$pdf->Cell(28,7,mb_convert_encoding('Número', 'ISO-8859-1', 'UTF-8'),1,0,'C');
$pdf->Cell(22,7,mb_convert_encoding('Fecha Emisión', 'ISO-8859-1', 'UTF-8'),1,0,'C');
$pdf->Cell(23,7,mb_convert_encoding('Ejercicio Fiscal', 'ISO-8859-1', 'UTF-8'),1,0,'C');
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
$pdf->Text(13,$iapos2,mb_convert_encoding("INFORMACIÓN ADICIONAL", 'ISO-8859-1', 'UTF-8'));

$pdf->Ln($iapos-117-($totDet-1)*5);
$pdf->SetCWidths(array(30,97));
$pdf->SetCFonts(array(array('Arial','B',8)));
$pdf->SetFont('Arial','',8);

foreach($datoXml->infoAdicional->campoAdicional AS $k => $v){
	$pdf->Row(array(mb_convert_encoding(strtoupper($v['nombre']), 'ISO-8859-1', 'UTF-8').':',mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8').''));
}

$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, $iapos , 125, $pdf->GetY()-$iapos+2, 0, '');


$pdf->Ln();	
$pdf->SetFont('Arial','I',8);
$pdf->Text(100,290,mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$pdf->PageNo().' de {nb}',0,0,'C');

//$pdf->TablaBasica($datos_xml);


$pdf->Output($code.'.pdf',$opPdf);
?>