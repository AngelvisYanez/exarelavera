<?php
/*
 * $datoXml contiene el array xml
 */
$documento = "F A C T U R A";
include dirname(__file__) . '/../COMPONENTES/tesPdfGlobal.php';

$infoFact = $datoXml['infoFactura'];
$pdf->setField(128, 25, $documento, 'B', 12);
$pdf->setField(38, 75, issetText($infoFact, 'dirEstablecimiento'), '', 8, null, 81);
$pdf->setField(70, 80, issetText($infoFact, 'contribuyenteEspecial', 'NO'), '', 8);
$pdf->setField(70, 85, issetText($infoFact, 'obligadoContabilidad', 'NO'), '', 8);

/* RECTANGULO INFO CLIENTE */
$pdf->RoundedRect(10, 98, 194, 13, 0, '');
$pdf->setField(13, 103, "RAZÓN SOCIAL:", 'B', 8);
$pdf->setField(40, 103, $infoFact['razonSocialComprador'], null, null, null, 160);
$pdf->setField(13, 108, "RUC / CI:", 'B', 8);
$pdf->setField(30, 108, $infoFact['identificacionComprador']);
$pdf->setField(75, 108, "FECHA DE EMISIÓN:", 'B', 8);
$pdf->setField(108, 108, $infoFact['fechaEmision']);
$pdf->setField(142, 108, "GUÍA DE REMISIÓN:", 'B', 8);
$pdf->setField(172, 108, issetText($infoFact, 'guiaRemision'));

/*T A B L A */
$pdf->SetY(113);
$pdf->SetCHeight(7);
$pdf->SetCAligns('C');
$pdf->SetFont('Arial', 'B', 8);
//$pdf->SetCWidths(array(8, 15, 106, 20, 23,22 ,22));
$pdf->SetCWidths(array(8, 15, 15, 66, 20, 20, 15, 15, 20)); 
$pdf->Row(array('No.', 'COD.AUX','CÓDIGO', 'DESCRIPCIÓN', 'CANTIDAD', 'PRECIO U.', 'TARIFA', 'DESC.', 'TOTAL'), true, false);
$pdf->SetFont('Arial', '', 8); 
$pdf->SetCAligns(array('L', 'C', 'C', 'L', 'C', 'R', 'C', 'C', 'R')); 
$pdf->SetCHeight(5);

//Acumular subtotal 5 
$subtotal_5 = 0;
$subtotal_iva = 0;
$valor_iva_5 = 0;
$valor_iva = 0;
foreach ($pdf->formatArray($datoXml['detalles']['detalle']) as $i => $deta) {
    $iva = '';
    $porcentaje_iva = array(); //NUEVO CODIGO

    foreach ($pdf->formatArray($deta['impuestos']['impuesto']) as $j => $vimp) {
        if ($vimp['codigo'] * 1 == 2 && $vimp['tarifa'] * 1 != 0) {
            $iva = ' *';
            if (!in_array($vimp['tarifa'], $porcentaje_iva)) { //Insertar el porcentaje del iva
                $porcentaje_iva[] = $vimp['tarifa'];
            }
            break;
        }
    }

    //Acumularo subtotal 15, 5 y demas.
    if ($porcentaje_iva[0] == 5) {
        $subtotal_5 +=  (float) $deta['cantidad'] * (float) $deta['precioUnitario'];
        $valor_iva_5 += (((float) $deta['cantidad'] * (float) $deta['precioUnitario'])  - (float)$deta['descuento']) * 0.05;
    } else if ($porcentaje_iva[0] != 5 &&  $porcentaje_iva[0] != 0) {
        $subtotal_iva += (float) $deta['cantidad'] * (float) $deta['precioUnitario']  - (float)$deta['descuento'];
        $valor_iva += (((float) $deta['cantidad'] * (float) $deta['precioUnitario'])  - (float)$deta['descuento']) * ($porcentaje_iva[0] / 100);
    }

    $pdf->Row(array(($i + 1),
        issetText($deta, 'codigoAuxiliar'), 
        issetText($deta, 'codigoPrincipal'),
        $deta['descripcion'] . $iva . detallesAdi($pdf, $deta),
        number_format($deta['cantidad'], 6),
        number_format((float)$deta['precioUnitario'], 6),
        number_format((float) $porcentaje_iva[0]) . "%",
        number_format((float)$deta['descuento'], 6),
        number_format(((float)$deta['cantidad'] * (float)$deta['precioUnitario']) - (float)$deta['descuento'], 2)
    ), true, true);
}

//var_dump( $porcentaje_iva);  //array(1) { [0]=> string(2) "12" } 

/* iniciamos en cero las variables */
$iva = array(
    '0' => array('base' => 0, 'valor' => 0),  // 0%
    '1' => array('base' => 0, 'valor' => 0),  // 10%
    '2' => array('base' => 0, 'valor' => 0),  // 12%
    '3' => array('base' => 0, 'valor' => 0),  // 14%
    '6' => array('base' => 0, 'valor' => 0),  // No Objeto Iva
    '7' => array('base' => 0, 'valor' => 0),  // Exento
    'total' => array('base' => 0, 'valor' => 0), //total acumulado
    'baseIva' => 0 // Base grava IVA
);

$ice =  array('total' => array('base' => 0, 'valor' => 0));
$ibnrp = array('total' => array('base' => 0, 'valor' => 0));
$totImp = $pdf->formatArray($infoFact['totalConImpuestos']['totalImpuesto']);

//var_dump($totImp);

foreach ($totImp as $imp) {
    //print($imp['codigo']);
    if ($imp['codigo'] == "2") { // Acumulo IVA
        $iva[$imp['codigoPorcentaje']]['base'] += ((float)$imp['baseImponible']);
        $iva[$imp['codigoPorcentaje']]['valor'] += ((float)$imp['valor']);
        $iva['total']['base'] += ((float)$imp['baseImponible']);
        $iva['total']['valor'] += ((float)$imp['valor']);
        if (((float)$imp['valor']) > 0) $iva['baseIva'] += ((float)$imp['baseImponible']);
    } else if ($imp['codigo'] == "3") { // Acumulo ICE
        $ice['total']['base'] += ((float)$imp['baseImponible']);
        $ice['total']['valor'] += ((float)$imp['valor']);
    } else if ($imp['codigo'] == "5") { // Acumulo impuesto botellas no retornables
        $ibnrp['total']['base'] += ((float)$imp['baseImponible']);
        $ibnrp['total']['valor'] += ((float)$imp['valor']);
    }
}


//$total = $iva['total']['base'] + $iva['total']['valor'] + $ibnrp['total']['valor'];
$total =  $iva['total']['base']   +     $iva['total']['valor']  +      $ibnrp['total']['valor'];

//VALIDAR NUEVOS PORCENTAJES DEL IVA
$porc_iva = (isset($porcentaje_iva[0]) && $porcentaje_iva[0] != 5) ? $porcentaje_iva[0] : "15";

/*
if ($porcentaje_iva[0] == 5) {
    $valor_iva_5 =  $iva['total']['valor'];
} else {
    $valor_iva = $iva['total']['valor'];
}*/

//fin nuevos campos
//totales
$alto_totales = 40;
$pdf->Ln(3);
$xAuxTota = $pdf->GetY();
$xPageTota = $pdf->page;
$pdf->setX(139);
$pdf->SetCAligns(array('L', 'R'));
$pdf->SetCWidths(array(40, 25));
$pdf->SetCFonts(array(array('Arial', 'B', 8), array('Arial', '', 8)));
/*
$pdf->Row(array("SUBTOTAL IVA", number_format($iva['baseIva'], 2)), true, false);
$pdf->Row(array("SUBTOTAL 0%", number_format($iva['0']['base'], 2)), true, false);
$pdf->Row(array("SUBTOTAL NO SUJETO IVA", number_format($iva['6']['base'] + (float)$infoFact['totalDescuento'], 2)), true, false);
$pdf->Row(array("SUBTOTAL SIN IMPUESTO", number_format((float)$infoFact['totalSinImpuestos'] + (float)$infoFact['totalDescuento'], 2)), true, false);
$pdf->Row(array("DESCUENTO", number_format((float)$infoFact['totalDescuento'], 2)), true, false);
$pdf->Row(array("ICE", number_format($ice['total']['valor'], 2)), true, false);
//Nuevos campos
$pdf->Row(array("IVA 5%", number_format($valor_iva_5, 2)), true, false); //TOTAL IVA 5%
$pdf->Row(array("IVA " . $porc_iva . "%", number_format($valor_iva, 2)), true, false); //TOTAL IVA cualquiera
//$pdf->Row(array("IVA ".$porc_iva."%"  ,number_format($iva['total']['valor'],2)),true,false);//TOTAL IVA
$pdf->Row(array("IRBPNR", number_format($ibnrp['total']['valor'], 2)), true, false);
$pdf->Row(array("TOTAL", number_format($total, 2)), true, false);*/


//$pdf->Row(array("SUBTOTAL SIN IMPUESTO", number_format((float)$infoFact['totalSinImpuestos'] + (float)$infoFact['totalDescuento'], 2)), true, false);
$pdf->Row(array("SUBTOTAL SIN IMPUESTO", number_format((float)$infoFact['totalSinImpuestos'], 2)), true, false);

//$pdf->Row(array("SUBTOTAL SIN IMPUESTOS", number_format($iva['baseIva'], 2)), true, false);

$pdf->Row(array("SUBTOTAL 5%", number_format($subtotal_5, 2)), true, false); //TOTAL IVA 5%

$pdf->Row(array("SUBTOTAL " . $porc_iva . "%", number_format($subtotal_iva, 2)), true, false); //TOTAL IVA cualquiera

$pdf->Row(array("SUBTOTAL 0%", number_format($iva['0']['base'], 2)), true, false);
$pdf->Row(array("SUBTOTAL NO OBJETO IVA", number_format($iva['6']['base']   /* + (float)$infoFact['totalDescuento']*/, 2)), true, false);

$pdf->Row(array("DESCUENTO", number_format((float)$infoFact['totalDescuento'], 2)), true, false);
$pdf->Row(array("ICE", number_format($ice['total']['valor'], 2)), true, false);
//Nuevos campos
$pdf->Row(array("IVA 5%", number_format($valor_iva_5, 2)), true, false); //TOTAL IVA 5%
$pdf->Row(array("IVA " . $porc_iva . "%", number_format($valor_iva, 2)), true, false); //TOTAL IVA cualquiera

//$pdf->Row(array("IVA ".$porc_iva."%"  ,number_format($iva['total']['valor'],2)),true,false);//TOTAL IVA
$pdf->Row(array("IRBPNR", number_format($ibnrp['total']['valor'], 2)), true, false);
$pdf->Row(array("TOTAL", "$ " . number_format($total, 2)), true, false);


/*Subtotal sin impuestos:	130.00
Subtotal 15.00%:	72.50
Subtotal 5%:	57.50
Subtotal tarifa especial:	0.00
Subtotal 0%:	0.00
Subtotal no objeto de IVA:	0.00
Subtotal exento de IVA:	0.00
Total descuento:	0.00
Valor ICE:	0.00
IVA 15.00% :10.88
IVA 5% :2.88
IVA tarifa especial:	0.00
Valor a pagar:	143.76
 */


$xAuxFin1 = $pdf->GetY() + 2;
$xAuxPageFin1 = $pdf->page;
/* Info Adicional */
$pdf->page = $xPageTota;
$pdf->setY($xAuxTota);
$pdf->Ln(2);

if (isset($datoXml['infoAdicional'])) {
    infoAddi($pdf, $pdf->GetY(), $datoXml['infoAdicional']['campoAdicional']);
}
$pdf->Ln(2);
if (isset($infoFact['pagos'])) {
    pagosDoc($pdf, $pdf->GetY(), $infoFact['pagos']['pago']);
}

$xAuxFin2 = $pdf->GetY() + 2;
$xAuxPageFin2 = $pdf->page;
$pdf->page = ($xAuxPageFin2 > $xAuxPageFin1 ? $xAuxPageFin2 : $xAuxPageFin1);
$pdf->SetY($xAuxPageFin2 > $xAuxPageFin1 ? $xAuxFin2 : ($xAuxFin1 < $xAuxFin2 ? $xAuxFin2 : $xAuxFin1));

$pdf->Ln(2);
if (isset($datoXml['reembolsos'])) {
    $pos = $pdf->GetY();
    if ($pos < 12) $pos = 12;
    $pdf->SetY($pos);
    $pdf->SetCFonts(null);
    $pdf->SetFont("Arial", "B", 8);
    $pdf->SetCAligns(array("C", "C", "C", "C", "C", "C", "C", "C"));
    $pdf->SetCWidths(array(194));
    $pdf->Row(array("DETALLE DE COMPROBANTES REEMBOLSO"), true, false);
    $pdf->SetCHeight(4);
    $pdf->SetCWidths(array(15, 21, 7, 25, 70, 20, 15, 21));
    $pdf->Row(array("FECHA", "RUC / CI", "TIPO", "SERIE", "AUTORIZACION", "BASE", "IMPUESTOS", "TOTAL"), true, false);
    $pdf->SetCFonts(array(array("Arial", "", "7"), array("Arial", "", "7"), array("Arial", "", "7"), array("Arial", "", "7.2"), array("Arial", "", "7"), array("Arial", "", "7"), array("Arial", "", "7"), array("Arial", "", "7")));
    $pdf->SetCAligns(array("L", "L", "C", "L", "L", "R", "R", "R"));

    $reembolsos = $pdf->formatArray($datoXml['reembolsos']['reembolsoDetalle']);
    foreach ($reembolsos as $reem) {
        $basReem = 0;
        $impueReem = 0;
        $reemImpuest = $pdf->formatArray($reem['detalleImpuestos']['detalleImpuesto']);
        foreach ($reemImpuest as $eReem) {
            if ($eReem["codigo"] == "2")
                $basReem += (("0" . $eReem["baseImponibleReembolso"]) * 1);
            $impueReem += ("0" . $eReem["impuestoReembolso"]) * 1;
        }
        $pdf->row(array(
            $reem["fechaEmisionDocReembolso"],
            $reem["identificacionProveedorReembolso"],
            $reem["codDocReembolso"],
            $reem["estabDocReembolso"] . "-" . $reem["ptoEmiDocReembolso"] . "-" . $reem["secuencialDocReembolso"],
            $reem["numeroautorizacionDocReemb"],
            number_format($basReem, 2),
            number_format($impueReem, 2),
            number_format($basReem + $impueReem, 2)
        ), true, false);
    }
}

$pdf->Output($ruta . $code . '.pdf', $opPdf);
if ($opPdf != 'F' && $opPdf != 'S') exit();
