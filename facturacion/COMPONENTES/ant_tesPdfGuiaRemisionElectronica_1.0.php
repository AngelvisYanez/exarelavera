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
$pdf->Text(128,25,utf8_decode("GUIA  DE  REMISIÓN"));

$pdf->SetFont('Arial','B',9);
$pdf->Text(128,31,"No:");
$pdf->SetTextColor(194,8,8);
$pdf->SetFont('Arial','',9);
$pdf->Text(148,31,$datoXml->infoTributaria[0]->estab."-".$datoXml->infoTributaria[0]->ptoEmi."-".$datoXml->infoTributaria[0]->secuencial);

$pdf->SetTextColor(0,0,0);
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
$pdf->Text(70,88,$datoXml->infoGuiaRemision[0]->contribuyenteEspecial);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,93,utf8_decode("OBLIGADO A LLEVAR CONTABILIDAD:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(70,93,$datoXml->infoGuiaRemision[0]->obligadoContabilidad);

/*RECTANGULO INFO TRANSPORTISTA*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 98, 194, 27, 0, '');

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,103,utf8_decode("IDENTIFICACIÓN (TRANSPORTÍSTA):"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,103,utf8_decode($datoXml->infoGuiaRemision[0]->rucTransportista));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,108,utf8_decode("RAZÓN SOCIAL / NOMBRES Y APELLIDOS:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,108,$datoXml->infoGuiaRemision[0]->razonSocialTransportista);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,113,utf8_decode("PLACA:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,113,$datoXml->infoGuiaRemision[0]->placa);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,118,utf8_decode("PUNTO DE PARTIDA:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,118,$datoXml->infoGuiaRemision[0]->dirPartida);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,123,utf8_decode("FECHA SALIDA TRANSPORTE:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,123,$datoXml->infoGuiaRemision[0]->fechaIniTransporte);

$pdf->SetFont('Arial','B',8);
$pdf->Text(115,123,utf8_decode("FECHA LLEGADA TRANSPORTE:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(166,123,$datoXml->infoGuiaRemision[0]->fechaFinTransporte);

/*RECTANGULO INFO DESTINATARIO Y  PRODUCTOS*/
$pdf->SetLineWidth(0.1);
$pdf->SetFillColor(300);
$pdf->RoundedRect(10, 127, 194, 50, 0, '');

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,132,utf8_decode("COMPROBANTE DE VENTA:"));
$pdf->SetFont('Arial','',8);
if($datoXml->destinatarios[0]->destinatario[0]->numDocSustento!='')
{
   $TipDoc='FACTURA  ';
}
$pdf->Text(75,132,$TipDoc.$datoXml->destinatarios[0]->destinatario[0]->numDocSustento);

$pdf->SetFont('Arial','B',8);
$pdf->Text(132,132,utf8_decode("FECHA DE EMISIÓN:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(166,132,$datoXml->destinatarios[0]->destinatario[0]->fechaEmisionDocSustento);

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,137,utf8_decode("NÚMERO DE AUTORIZACIÓN:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,137,$datoXml->destinatarios[0]->destinatario[0]->numAutDocSustento); 

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,145,utf8_decode("MOTIVO DE TRASLADO:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,145,strtoupper($datoXml->destinatarios[0]->destinatario[0]->motivoTraslado));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,150,utf8_decode("DESTINO (PUNTO DE LLEGADA):"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,150,strtoupper($datoXml->destinatarios[0]->destinatario[0]->dirDestinatario));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,155,utf8_decode("IDENTIFICACIÓN (DESTINATARIO):"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,155,strtoupper($datoXml->destinatarios[0]->destinatario[0]->identificacionDestinatario));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,160,utf8_decode("RAZÓN SOCIAL / NOMBRES Y APELLIDOS:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,160,strtoupper($datoXml->destinatarios[0]->destinatario[0]->razonSocialDestinatario));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,165,utf8_decode("DOCUMENTO ADUANERO:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,165,strtoupper($datoXml->destinatarios[0]->destinatario[0]->docAduaneroUnico));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,170,utf8_decode("CÓDIGO ESTABLECIMIENTO DESTINO:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,170,strtoupper($datoXml->destinatarios[0]->destinatario[0]->codEstabDestino));

$pdf->SetFont('Arial','B',8);
$pdf->Text(13,175,utf8_decode("RUTA:"));
$pdf->SetFont('Arial','',8);
$pdf->Text(75,175,strtoupper($datoXml->destinatarios[0]->destinatario[0]->ruta));




/*T A B L A */
//Títulos de las columnas

$pdf->AliasNbPages();
$pdf->SetY(179);
//Cabecera    	
$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,7,utf8_decode('CÓDIGO PRINCIPAL'),1,0,'C');
$pdf->Cell(28,7,'CANTIDAD',1,0,'C');
$pdf->Cell(136,7,utf8_decode('DESCRIPCIÓN'),1,0,'C');
$pdf->Ln();
$pdf->SetFont('Arial','',8);

$totDet= count($datoXml->destinatarios[0]->destinatario[0]->detalles[0]->detalle);

//Detalle
for($x=0;$x<=$totDet-1;$x++)
{		
	$pdf->Cell(30,5,$datoXml->destinatarios[0]->destinatario[0]->detalles[0]->detalle[$x]->codigoInterno,1,0,'L');
	$pdf->Cell(28,5,$datoXml->destinatarios[0]->destinatario[0]->detalles[0]->detalle[$x]->cantidad,1,0,'C');	
	$pdf->Cell(136,5,$datoXml->destinatarios[0]->destinatario[0]->detalles[0]->detalle[$x]->descripcion,1,0,'L');		
	$pdf->Ln();
}

$pdf->Ln();	
$pdf->SetFont('Arial','I',8);
$pdf->Text(100,290,utf8_decode('Página ').$pdf->PageNo().' de {nb}',0,0,'C');

//$pdf->TablaBasica($datos_xml);


$pdf->Output($code.'.pdf',$opPdf);
?>