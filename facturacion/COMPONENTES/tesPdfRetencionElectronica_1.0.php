<?php
/*
 * $datoXml contiene el array xml
 */
$documento="COMPROBANTE DE RETENCIÓN";
include dirname(__file__).'/../COMPONENTES/tesPdfGlobal.php';

$infoCompRet=$datoXml['infoCompRetencion'];
$pdf->setField(128,25,$documento,'B',12);
$pdf->setField(38,75, issetText($infoCompRet,'dirEstablecimiento'),'',8,null,81);
$pdf->setField(70,80, issetText($infoCompRet,'contribuyenteEspecial','NO') ,'',8);
$pdf->setField(70,85, issetText($infoCompRet,'obligadoContabilidad','NO') ,'',8);

/*RECTANGULO INFO CLIENTE*/
$pdf->SetFont('Arial','B',8);
$pdf->RoundedRect(10, 98, 194, 13, 0, '');
$pdf->setField(13,103,"RAZÓN SOCIAL:",'B',8);
$pdf->setField(40,103,$infoCompRet['razonSocialSujetoRetenido'],null,null,null,160);
$pdf->setField(13,108,"RUC / CI:",'B',8);
$pdf->setField(30,108,$infoCompRet['identificacionSujetoRetenido']);
$pdf->setField(75,108,"FECHA DE EMISIÓN:",'B',8);
$pdf->setField(110,108,$infoCompRet['fechaEmision']);

/*T A B L A */
$pdf->SetY(113);
$pdf->SetCHeight(7);
$pdf->SetCAligns('C');
$pdf->SetFont('Arial','B',8);
$pdf->SetCWidths(array(30,28,22,23,22,22,22,25));
$pdf->Row(array('Comprobante','Número','Fecha Emisión','Ejercicio Fiscal','Base Imponible','Impuesto','Porcentaje %','Valor Retenido'),true,false);
$pdf->SetFont('Arial','',8);
$pdf->SetCAligns(array('C','C','C','C','R','L','C','R'));
$pdf->SetCHeight(5);
$sumaTotal=0;
foreach ($pdf->formatArray($datoXml['impuestos']['impuesto'])AS $i=>$impu){
    $sumaTotal+=((float)$impu['valorRetenido']);
    $pdf->Row(array(
        selectDoc($impu['codDocSustento']),isset($impu['numDocSustento'])?$impu['numDocSustento']:'-',isset($impu['fechaEmisionDocSustento'])?$impu['fechaEmisionDocSustento']:'-',$infoCompRet['periodoFiscal'],
        number_format((float)$impu['baseImponible'],2),
        ($impu['codigo']=='1'?'RENTA':'IVA').'  '.$impu['codigoRetencion'],
        $impu['porcentajeRetener'],
        number_format((float)$impu['valorRetenido'],2)
    ) ,true,false);
}
$pdf->SetFont('Arial','B',8);
$pdf->Cell(144);
$pdf->Cell(25,5,'TOTAL:',0,0,'R');
$pdf->Cell(25,5,number_format((float)$sumaTotal,2),1,0,'R');

/* Info Adicional */
$pdf->Ln(10);
if(isset($datoXml['infoAdicional'])){
    infoAddi($pdf,$pdf->GetY(),$datoXml['infoAdicional']['campoAdicional']);
}

$pdf->Output($ruta.$code.'.pdf',$opPdf);
if($opPdf!='F'&&$opPdf!='S') exit();