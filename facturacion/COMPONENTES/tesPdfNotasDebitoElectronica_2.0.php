<?php
/*
 * $datoXml contiene el array xml
 */
$documento="N O T A        DE        D É B I T O";
include dirname(__file__).'/../COMPONENTES/tesPdfGlobal.php';

$infoNDeb=$datoXml['infoNotaDebito'];
$pdf->setField(128,25,$documento,'B',12);
$pdf->setField(38,75, issetText($infoNDeb,'dirEstablecimiento'),'',8,null,81);
$pdf->setField(70,80, issetText($infoNDeb,'contribuyenteEspecial','NO') ,'',8);
$pdf->setField(70,85, issetText($infoNDeb,'obligadoContabilidad','NO') ,'',8);

/* RECTANGULO INFO CLIENTE */
$pdf->RoundedRect(10, 98, 194, 25, 0, '');
$pdf->setField(13,103,"RAZÓN SOCIAL:",'B',8);
$pdf->setField(40,103,$infoNDeb['razonSocialComprador'],null,null,null,160);
$pdf->setField(13,108,"RUC / CI:",'B',8);
$pdf->setField(30,108,$infoNDeb['identificacionComprador']);
$pdf->setField(75,108,"FECHA DE EMISIÓN:",'B',8);
$pdf->setField(110,108,$infoNDeb['fechaEmision']);

//(inicio X, inicio Y,fin X, fin Y)
$pdf->Line(13,110,200,110);

$pdf->setField(13,115,"COMPROBANTE QUE SE MODIFICA:",'B');
$pdf->setField(95,115,selectDoc($infoNDeb['codDocModificado']).' - '.$infoNDeb['numDocModificado']);
$pdf->setField(13,120,"FECHA DE EMISIÓN (Comprobante a modificar):",'B');
$pdf->setField(95,120,$infoNDeb['fechaEmisionDocSustento']);


/*T A B L A */
$pdf->SetY(126);
$pdf->SetCHeight(7);
$pdf->SetCAligns('C');
$pdf->SetFont('Arial','B',9);
$pdf->SetCWidths(array(8,136,50));
$pdf->Row(array('No.','RAZON DE LA MODIFICACION','VALOR DE LA MODIFICACIÓN'),true,false);
$pdf->SetFont('Arial','',8);
$pdf->SetCAligns(array('L','L','R'));
$pdf->SetCHeight(5);
foreach ($pdf->formatArray($datoXml['motivos']['motivo'])AS $i=>$deta){
    $pdf->Row(array(($i+1),$deta['razon'],$deta['valor']  ) ,true,false);
}

/* iniciamos en cero las variables */
$baseImp0=0; $valorImp0=0;
$baseImp12=0; $valorImp12=0;
$baseNoObjeto=0; $valorNoObjeto=0;
$baseExento=0; $valorExento=0;
$valorIce=0;

$totImp=$pdf->formatArray($infoNDeb['impuestos']['impuesto']);
for($x=0;$x<count($totImp);$x++){
    if ($totImp[$x]['codigo']=="2" || $totImp[$x]['codigo']=="3"){ /* IVA */
        if ($totImp[$x]['codigoPorcentaje']=="0"){  /* IVA 0%*/
            $baseImp0=(float)$totImp[$x]['baseImponible'];
            $valorImp0=(float)$totImp[$x]['valor'];
        }
        if ($totImp[$x]['codigoPorcentaje']=="2"){ /* IVA 12%*/
            $baseImp12=(float)$totImp[$x]['baseImponible'];
            $valorImp12=(float)$totImp[$x]['valor'];
        }
        if ($totImp[$x]['codigoPorcentaje']=="3"){ /* IVA 14%*/
            $baseImp12=(float)$totImp[$x]['baseImponible'];
            $valorImp12=(float)$totImp[$x]['valor'];
        }
        if ($totImp[$x]['codigoPorcentaje']=="6"){ /* No Objeto de Impuesto */
            $baseNoObjeto=(float)$totImp[$x]['baseImponible'];
            $valorNoObjeto=(float)$totImp[$x]['valor'];
        }
        if ($totImp[$x]['codigoPorcentaje']=="7"){ /* Exento de IVA */
            $baseExento=(float)$totImp[$x]['baseImponible'];
            $valorExento=(float)$totImp[$x]['valor'];
        }
    }
    if ($totImp[$x]['codigo']=="3"){ /* ICE */
        $valorIce= $valorIce + (float)$totImp[$x]['valor']; /* SUMA TODOS LOS ICE */
    }
}
$total=((float)$infoNDeb['totalSinImpuestos'] )+ (float)$valorIce + (float)$valorImp12;

//totales
$alto_totales=40;
$pdf->Ln(3);
$pdf->setX(139);
$pdf->SetCAligns(array('L','R'));
$pdf->SetCWidths(array(40,25));
$pdf->SetCFonts(array(array('Arial','B',8),array('Arial','',8)) );

$pdf->Row(array("SUBTOTAL IVA"          ,number_format($baseImp12,2)),true,false);
$pdf->Row(array("SUBTOTAL 0%"           ,number_format($baseImp0,2)),true,false);
$pdf->Row(array("SUBTOTAL NO SUJETO IVA",number_format($baseNoObjeto,2)),true,false);
$pdf->Row(array("SUBTOTAL SIN IMPUESTO" ,number_format((float)$infoNDeb['totalSinImpuestos'],2)),true,false);
$pdf->Row(array("DESCUENTO"             ,number_format(/*(float)$infoNDeb['totalDescuento']*/0,2)),true,false);
$pdf->Row(array("ICE"                   ,number_format($valorIce,2)),true,false);
$pdf->Row(array("IVA"                   ,number_format($valorImp12,2)),true,false);
$pdf->Row(array("TOTAL"                 ,number_format($total,2)),true,false);

/* Info Adicional */
$pdf->Ln(2);
if(isset($datoXml['infoAdicional'])){
    infoAddi($pdf,$pdf->GetY()-$alto_totales,$datoXml['infoAdicional']['campoAdicional']);
}

$pdf->Ln(4);
if(isset($infoNDeb['pagos'])){
    pagosDoc($pdf,$pdf->GetY(),$infoNDeb['pagos']['pago']);
}

$pdf->Output($ruta.$code.'.pdf',$opPdf);
if($opPdf!='F'&&$opPdf!='S') exit();