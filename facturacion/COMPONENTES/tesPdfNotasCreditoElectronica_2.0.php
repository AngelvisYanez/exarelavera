<?php
/*
 * $datoXml contiene el array xml
 */
$documento="N O T A       DE       C R E D I T O";
include dirname(__file__).'/../COMPONENTES/tesPdfGlobal.php';

$infoNCred=$datoXml['infoNotaCredito'];
$pdf->setField(128,25,$documento,'B',12);
$pdf->setField(38,75, issetText($infoNCred,'dirEstablecimiento'),'',8,null,81);
$pdf->setField(70,80, issetText($infoNCred,'contribuyenteEspecial','NO') ,'',8);
$pdf->setField(70,85, issetText($infoNCred,'obligadoContabilidad','NO') ,'',8);

/* RECTANGULO INFO CLIENTE */
$pdf->RoundedRect(10, 98, 194, 30, 0, '');
$pdf->setField(13,103,"RAZÓN SOCIAL:",'B',8);
$pdf->setField(40,103,$infoNCred['razonSocialComprador'],null,null,null,160);
$pdf->setField(13,108,"RUC / CI:",'B',8);
$pdf->setField(30,108,$infoNCred['identificacionComprador']);
$pdf->setField(75,108,"FECHA DE EMISIÓN:",'B',8);
$pdf->setField(110,108,$infoNCred['fechaEmision']);

//(inicio X, inicio Y,fin X, fin Y)
$pdf->Line(13,110,200,110);

$pdf->setField(13,115,"COMPROBANTE QUE SE MODIFICA:",'B');
$pdf->setField(95,115, selectDoc($infoNCred['codDocModificado']).' - '.$infoNCred['numDocModificado']);
$pdf->setField(13,120,"FECHA DE EMISIÓN (Comprobante a modificar):",'B');
$pdf->setField(95,120,$infoNCred['fechaEmisionDocSustento']);
$pdf->setField(13,125,"RAZON DE MODIFCACIÓN:",'B');
$pdf->setField(58,125,$infoNCred['motivo'],null,null,null,140);

/*T A B L A */
$pdf->SetY(131);
$pdf->SetCHeight(7);
$pdf->SetCAligns('C');
$pdf->SetFont('Arial','B',8);
$pdf->SetCWidths(array(8,15,106,20,23,22));
$pdf->Row(array('No.','CÓDIGO','DESCRIPCIÓN','CANTIDAD','PRECIO U.','TOTAL'),true,false);
$pdf->SetFont('Arial','',8);
$pdf->SetCAligns(array('L','C','L','C','R','R'));
$pdf->SetCHeight(5);
foreach ($pdf->formatArray($datoXml['detalles']['detalle'])AS $i=>$deta){
    $pdf->Row(array(($i+1),issetText($deta,'codigoAdicional'),$deta['descripcion'].detallesAdi($pdf, $deta),$deta['cantidad'],$deta['precioUnitario'],number_format((float)$deta['cantidad']*(float)$deta['precioUnitario'],2)  ) ,true,false);
}

/* iniciamos en cero las variables */
$iva=array(
    '0'=>array('base'=>0,'valor'=>0),  // 0%
    '1'=>array('base'=>0,'valor'=>0),  // 10%
    '2'=>array('base'=>0,'valor'=>0),  // 12%
    '3'=>array('base'=>0,'valor'=>0),  // 14%
    '6'=>array('base'=>0,'valor'=>0),  // No Objeto Iva
    '7'=>array('base'=>0,'valor'=>0),  // Exento
    'total'=>array('base'=>0,'valor'=>0), //total acumulado
    'baseIva'=>0 // Base grava IVA
);
$ice=  array('total'=>array('base'=>0,'valor'=>0));
$ibnrp=array('total'=>array('base'=>0,'valor'=>0));

$totImp=$pdf->formatArray($infoNCred['totalConImpuestos']['totalImpuesto']);
foreach($totImp as $imp){
    if($imp['codigo']=="2"){ // Acumulo IVA
        $iva[$imp['codigoPorcentaje']]['base'] +=((float)$imp['baseImponible']);
        $iva[$imp['codigoPorcentaje']]['valor']+=((float)$imp['valor']);
        $iva['total']['base'] +=((float)$imp['baseImponible']);
        $iva['total']['valor']+=((float)$imp['valor']);
        if(((float)$imp['valor'])>0) $iva['baseIva']+=((float)$imp['baseImponible']);
    }else if($imp['codigo']=="3"){ // Acumulo ICE
        $ice['total']['base'] +=((float)$imp['baseImponible']);
        $ice['total']['valor']+=((float)$imp['valor']);
    }else if($imp['codigo']=="5"){ // Acumulo impuesto botellas no retornables
        $ibnrp['total']['base'] +=((float)$imp['baseImponible']);
        $ibnrp['total']['valor']+=((float)$imp['valor']);
    }
}
$total= $iva['total']['base'] + $iva['total']['valor'];

//totales
$alto_totales=40;
$pdf->Ln(3);
$pdf->setX(139);
$pdf->SetCAligns(array('L','R'));
$pdf->SetCWidths(array(40,25));
$pdf->SetCFonts(array(array('Arial','B',8),array('Arial','',8)) );

$pdf->Row(array("SUBTOTAL IVA"          ,number_format($iva['baseIva'],2)),true,false);
$pdf->Row(array("SUBTOTAL 0%"           ,number_format($iva['0']['base'] + (float)$infoNCred['totalDescuento'],2)),true,false);
$pdf->Row(array("SUBTOTAL NO SUJETO IVA",number_format($iva['6']['base'],2)),true,false);
$pdf->Row(array("SUBTOTAL SIN IMPUESTO" ,number_format((float)$infoNCred['totalSinImpuestos'] + (float)$infoNCred['totalDescuento'],2)),true,false);
$pdf->Row(array("DESCUENTO"             ,number_format((float)$infoNCred['totalDescuento'],2)),true,false);
$pdf->Row(array("ICE"                   ,number_format($ice['total']['valor'],2)),true,false);
$pdf->Row(array("IVA"                   ,number_format($iva['total']['valor'],2)),true,false);
$pdf->Row(array("TOTAL"                 ,number_format($total,2)),true,false);

/* Info Adicional */
$pdf->Ln(2);
if(isset($datoXml['infoAdicional'])){
    infoAddi($pdf,$pdf->GetY()-$alto_totales,$datoXml['infoAdicional']['campoAdicional']);
}

$pdf->Output($ruta.$code.'.pdf',$opPdf);
if($opPdf!='F'&&$opPdf!='S') exit();