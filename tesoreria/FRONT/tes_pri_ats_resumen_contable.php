<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
/**
 * Reporte Agrupado por Cuenta Contable para Anexo
 *
 * @author Antigravity
 * @package tesoreria
 */

require_once('../../Librerias/config.php/register_globals.php');
require_once($APP_REAL_PATH . '/administrador/LOGICA/logica.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

ini_set("memory_limit", "1024M");
ini_set('max_execution_time', 3000);

/** 
 * objeto conexion
 */
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);

/**
 * objeto para extraer datos
 */
$obBD_con1 =  new Class_Log_Datos_Anx;

/**
 *   Variables para Encabezado
 */
$Titulo = "TALON DE RESUMEN";
$fechaAnexo = explode('-', $ini);
$Subtitulo = "ANEXO TRANSACCIONAL AGRUPADO POR CUENTA CONTABLE - " . strtoupper(mes($fechaAnexo[1], 1)) . ' ' . $fechaAnexo[0];

/* ============================================================
   OPTIMIZACIÓN: 2 consultas UNION ALL reemplazan 4 viajes a BD.
   Case 3000 = Case 1000 UNION ALL Case 1006 (con tag Tipo M/C).
   Case 3001 = Case 1001 UNION ALL Case 1007 (con tag Tipo M/C).
   PHP solo enruta por etiqueta — cero recálculo de lógica.
   ============================================================ */
$params = $Ses_Emp_Cod . '*' . $ini . '*' . $fin;

$rs_union_cmp = $obBD_con1->getArrayConsulta(3000, $params, $obBD_conexion);
$rs_union_vta = $obBD_con1->getArrayConsulta(3001, $params, $obBD_conexion);

$rs_compras    = array();
$rs_contra_cmp = array();
foreach ($rs_union_cmp as $row) {
    if ($row['Tipo'] === 'M') { $rs_compras[]    = $row; }
    else                      { $rs_contra_cmp[] = $row; }
}

$rs_ventas     = array();
$rs_contra_vta = array();
foreach ($rs_union_vta as $row) {
    if ($row['Tipo'] === 'M') { $rs_ventas[]     = $row; }
    else                      { $rs_contra_vta[] = $row; }
}

// Obtener porcentaje de IVA para el label
$row_rs_PorIva = $obBD_con1->getRowConsulta(876, $ini, $obBD_conexion);

/* Consulta de la cabecera del reporte */
$row_institucion = $obBD_con1->getRowConsulta(22, $Ses_Suc_Cod, $obBD_conexion);
/* Consulta la provincia y pais de la sucursal */
$row_provincia = $obBD_con1->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD_conexion);

// --- RETENCIONES por cuenta contable ---
$rs_ret_cmp_rta = $obBD_con1->getArrayConsulta(1002, $params, $obBD_conexion);
$rs_ret_cmp_iva = $obBD_con1->getArrayConsulta(1003, $params, $obBD_conexion);
$rs_ret_vta_rta = $obBD_con1->getArrayConsulta(1004, $params, $obBD_conexion);
$rs_ret_vta_iva = $obBD_con1->getArrayConsulta(1005, $params, $obBD_conexion);


/* ============================================================
   PARSEO DEL XML PARA RETENCIONES
   ============================================================ */
function buscaCodigoCont($auxArr, $valor) {
    $x = 0;
    foreach ($auxArr as $clave => $cod) {
        $xy = $x;
        if ((string)$cod['Cod'] == $valor) { break; }
        else { $xy = "falso"; }
        $x++;
    }
    return $xy;
}

$retVtaArr = array(
    array("id" => 1, "nom" => "Valor de IVA que le han retenido",   "val" => 0, "base" => 0, "reg" => 0),
    array("id" => 2, "nom" => "Valor de Renta que le han retenido", "val" => 0, "base" => 0, "reg" => 0)
);
$retIvaComArr = array(
    array("id" => 1, "nom" => "Retencion IVA 10%",  "val" => 0, "base" => 0, "reg" => 0, "pct" => 0.10),
    array("id" => 2, "nom" => "Retencion IVA 20%",  "val" => 0, "base" => 0, "reg" => 0, "pct" => 0.20),
    array("id" => 3, "nom" => "Retencion IVA 30%",  "val" => 0, "base" => 0, "reg" => 0, "pct" => 0.30),
    array("id" => 4, "nom" => "Retencion IVA 50%",  "val" => 0, "base" => 0, "reg" => 0, "pct" => 0.50),
    array("id" => 5, "nom" => "Retencion IVA 70%",  "val" => 0, "base" => 0, "reg" => 0, "pct" => 0.70),
    array("id" => 6, "nom" => "Retencion IVA 100%", "val" => 0, "base" => 0, "reg" => 0, "pct" => 1.00)
);
$retCmpArr = null;
$xmlOk = false;

try {
    require_once('../../Librerias/Xml/XML.php');
    $xml = simplexml_load_file($url);
    /* Retenciones de COMPRAS */
    foreach ($xml->compras->detalleCompras as $dato) {
        $vals = array(
            (float)$dato->valRetBien10,
            (float)$dato->valRetServ20,
            (float)$dato->valorRetBienes,
            (float)$dato->valRetServ50,
            (float)$dato->valorRetServicios,
            (float)$dato->valRetServ100
        );
        foreach ($vals as $i => $v) {
            if ($v > 0) {
                $retIvaComArr[$i]['val']  += $v;
                $retIvaComArr[$i]['base'] += $v / $retIvaComArr[$i]['pct'];
                $retIvaComArr[$i]['reg']++;
            }
        }
        $retnCmp = $dato->air;
        if (!empty($retnCmp->detalleAir))
            foreach ($retnCmp->detalleAir as $ret) {
                if (isset($retCmpArr)) {
                    $flag = buscaCodigoCont($retCmpArr, (string)$ret->codRetAir);
                    if ($flag === "falso") {
                        array_push($retCmpArr, array("Cod" => (string)$ret->codRetAir, "reg" => 1, "base" => (float)$ret->baseImpAir, "val" => (float)$ret->valRetAir));
                    } else {
                        $retCmpArr[$flag]["base"] += (float)$ret->baseImpAir;
                        $retCmpArr[$flag]["val"]  += (float)$ret->valRetAir;
                        $retCmpArr[$flag]["reg"]++;
                    }
                } else {
                    $retCmpArr = array(array("Cod" => (string)$ret->codRetAir, "reg" => 1, "base" => (float)$ret->baseImpAir, "val" => (float)$ret->valRetAir));
                }
            }
    }
    /* IVA y Renta en VENTAS (lo que le retuvieron) */
    foreach ($xml->ventas->detalleVentas as $dato) {
        $vRta = (float)$dato->valorRetRenta;
        $vIva = (float)$dato->valorRetIva;
        $nReg = (int)$dato->numeroComprobantes;
        $baseT = (float)$dato->baseNoGraIva + (float)$dato->baseImponible + (float)$dato->baseImpGrav;
        
        if ($vRta > 0) {
            $retVtaArr[1]['val']  += $vRta;
            $retVtaArr[1]['base'] += $baseT;
            $retVtaArr[1]['reg']  += $nReg;
        }
        if ($vIva > 0) {
            $retVtaArr[0]['val']  += $vIva;
            $retVtaArr[0]['base'] += (float)$dato->montoIva;
            $retVtaArr[0]['reg']  += $nReg;
        }
    }
    $xmlOk = true;
} catch (Exception $e) {
    // XML no disponible — se omiten las secciones de retenciones
}

function codigoRentaCont($cod) {
    $map = array(
        '303'=>'SERVICIOS HONORARIOS PROFESIONALES Y DIETAS',
        '303A'=>'SERVICIOS PROFESIONALES PRESTADOS POR SOCIEDADES RESIDENTES',
        '304'=>'SERVICIOS PREDOMINA EL INTELECTO',
        '307'=>'SERVICIOS PREDOMINA MANO DE OBRA',
        '308'=>'SERVICIOS ENTRE SOCIEDADES',
        '309'=>'SERVICIOS PUBLICIDAD Y COMUNICACION',
        '310'=>'SERVICIOS TRANSPORTE PRIVADO DE PASAJEROS O SERVICIO PUBLICO O PRIVADO DE CARGA',
        '312'=>'TRANSFERENCIA DE BIENES MUEBLES DE NATURALEZA CORPORAL',
        '312A'=>'COMPRA DE BIENES DE ORIGEN AGRICOLA,AVICOLA,ETC',
        '312V'=>'RETENCION EN LA FUENTE RENTA VENTAS',
        '319'=>'ARRENDAMIENTOS MERCANTIL',
        '320'=>'ARRENDAMIENTOS BIENES INMUEBLES',
        '322'=>'SEGUROS Y REASEGUROS (PRIMAS Y SESIONES)',
        '323'=>'RENDIMIENTOS FINANCIEROS',
        '332'=>'OTRAS COMPRAS DE BIENES Y SERVICIOS NO SUJETAS A RETENCION',
        '332G'=>'PAGOS CON TARJETA DE CREDITO',
        '332I'=>'PAGO A TRAVES DE CONVENIO DE DEBITO',
        '340'=>'OTRAS RETENCIONES APLICABLES 1%',
        '341'=>'OTRAS RETENCIONES APLICABLES EL 2%',
        '342'=>'OTRAS RENTENCIONES APLICABLES EL 8%',
        '344V'=>'OTRAS RETENCIONES APLICABLES AL 2%',
        '345'=>'OTRAS RENTENCIONES APLICABLES EL 8%',
        '505'=>'PAGOS A NO RESIDENTES - INTERESES DE OTROS CREDITOS EXTERNOS',
    );
    return isset($map[$cod]) ? $map[$cod] : $cod;
}
?>
<html>
<head>
    <title>ATS Agrupado por Cuenta</title>
    <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style type="text/css">
        .totales { font-size: 10px; font-weight: bold; font: 8pt verdana; }
        .titEmp { font-size: 14px; font-weight: bold; font: 12pt verdana; }
        .txtValor { font-size: 9px; font-weight: normal; font: 8pt verdana; }
        .CabeceraTabla { font-size: 10px; font-weight: bold; background-color: #99CCCC; }
        .FilaTotal { background-color: #cccccc; font-weight: bold; }
    </style>
</head>
<body class="Cuerpo">
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td height="58" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td width="10%" rowspan="5" valign="top"><img src="../../mascaras/model2/imagenes/32x32/sri.png" width="100" height="70" /></td>
                        <td width="80%" height="24" class="titEmp"><strong><?Php echo $row_institucion['Emp_Nom']; ?></strong></td>
                        <td width="10%" rowspan="5" valign="top" class="TITULO_REPORTE_2"><img src="<? echo $row_institucion['Emp_Log'] ?>" width="115" height="83" /></td>
                    </tr>
                    <tr align="center">
                        <td valign="top" class="Texto_Reporte">
                            <div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp; <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div>
                        </td>
                    </tr>
                    <tr align="center">
                        <td valign="top" class="Texto_Reporte">
                            <div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div>
                        </td>
                    </tr>
                    <tr align="center">
                        <td valign="top" class="Texto_Reporte">
                            <div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div>
                        </td>
                    </tr>
                    <tr align="center">
                        <td align="center" valign="top" class="Texto_Reporte">
                            <div align="center"><?Php
                                if (count($row_provincia) > 0) {
                                    $provincia = " - " . $row_provincia['Pro_Nom'] . ' - ' . $row_provincia['Pas_Nom'];
                                } else {
                                    $provincia = "";
                                }
                                echo $row_institucion['Ciu_Des'] . $provincia; ?></div>
                        </td>
                    </tr>
                    <tr align="center">
                        <td colspan="3" valign="top">
                            <hr />
                        </td>
                    </tr>
                    <tr align="center">
                        <td colspan="3" valign="top" class="TITULO_REPORTE"><? echo $Titulo; ?></td>
                    </tr>
                    <tr align="center">
                        <td colspan="3" valign="top" class="TITULO_REPORTE"><? echo $Subtitulo; ?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <br>
                <?php
                /* ---- TABLA COMPRAS POR CUENTA CONTABLE ---- */
                if (count($rs_compras) > 0 && $aCom == 1): ?>
                <table width="100%" border="1" style="border-collapse:collapse;margin-bottom:6px" cellpadding="3" cellspacing="0">
                    <thead>
                        <tr class="Texto_Listados">
                            <th colspan="7" style="color:#FFF;" bgcolor="#025ECC">
                                <div align="center">COMPRAS</div>
                            </th>
                        </tr>
                        <tr class="Texto_Listados CabeceraTabla">
                            <th width="10%">C&oacute;d.</th>
                            <th width="30%">Cta. Contable</th>
                            <th width="15%">Transacci&oacute;n</th>
                            <th width="10%">No. Reg.</th>
                            <th width="12%">BI Tarifa 0%</th>
                            <th width="12%">BI Tarifa <?php echo $row_rs_PorIva['Iva_Por']; ?>%</th>
                            <th width="11%">Valor IVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tot_reg=0; $tot_b0=0; $tot_bIva=0; $tot_vIva=0;
                        foreach ($rs_compras as $row) {
                            $tot_reg   += $row['NumReg'];
                            $tot_b0    += $row['Base0'];
                            $tot_bIva  += $row['BaseIva'];
                            $tot_vIva  += $row['ValIva'];
                        ?>
                        <tr class="txtValor">
                            <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                            <td><?php echo $row['Pld_Des']; ?></td>
                            <td><?php echo $row['Tic_Des']; ?></td>
                            <td align="center"><?php echo $row['NumReg']; ?></td>
                            <td align="right"><?php echo number_format($row['Base0'],    2); ?></td>
                            <td align="right"><?php echo number_format($row['BaseIva'],  2); ?></td>
                            <td align="right"><?php echo number_format($row['ValIva'],   2); ?></td>
                        </tr>
                        <?php } ?>
                        <tr class="txtValor FilaTotal">
                            <td colspan="3" align="right">TOTAL GENERAL:</td>
                            <td align="center"><?php echo $tot_reg; ?></td>
                            <td align="right"><?php echo number_format($tot_b0,    2); ?></td>
                            <td align="right"><?php echo number_format($tot_bIva,  2); ?></td>
                            <td align="right"><?php echo number_format($tot_vIva,  2); ?></td>
                        </tr>
                    </tbody>
                </table>
                <table width="100%" border="1" style="border-collapse:collapse;margin-bottom:6px" cellpadding="3" cellspacing="0">
                    <thead>
                        <tr class="Texto_Listados">
                            <th colspan="5" style="color:#FFF;" bgcolor="#5D4077">
                                <div align="left">&nbsp;Resumen &mdash; Contracuentas de compras (haber)</div>
                            </th>
                        </tr>
                        <tr class="Texto_Listados" bgcolor="#2D2A4B" style="color:#FFF;">
                            <th width="15%">C&oacute;d. haber</th>
                            <th width="35%">Contracuenta</th>
                            <th width="20%">Tipo de movimiento</th>
                            <th width="15%">No. registros</th>
                            <th width="15%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ctot_reg=0; $ctot_val=0;
                        foreach ($rs_contra_cmp as $row) {
                            $ctot_reg   += $row['NumReg'];
                            $ctot_val   += $row['Total'];
                            
                            $label_tipo = $row['Tic_Des'];
                            $bg_tipo    = '#e6f2ff'; 
                            $color_tipo = '#0055ff';
                            if ($row['Tic_Sri'] == '04') { $label_tipo = 'NOTA CR&Eacute;DITO'; $bg_tipo = '#fff2e6'; $color_tipo = '#b38f00'; }
                            if ($row['Tic_Sri'] == '05') { $label_tipo = 'NOTA D&Eacute;BITO';   $bg_tipo = '#f2ffe6'; $color_tipo = '#008037'; }
                        ?>
                        <tr class="txtValor">
                            <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                            <td style="padding-left:10px;"><?php echo $row['Pld_Des']; ?></td>
                            <td align="center">
                                <span style="background-color:<?php echo $bg_tipo; ?>; color:<?php echo $color_tipo; ?>; padding:2px 8px; border-radius:4px; font-weight:bold; font-size:9px; text-transform:uppercase;">
                                    <?php echo $label_tipo; ?>
                                </span>
                            </td>
                            <td align="center"><?php echo $row['NumReg']; ?></td>
                            <td align="right" style="padding-right:15px;"><?php echo number_format($row['Total'], 2); ?></td>
                        </tr>
                        <?php } ?>
                        <tr class="txtValor FilaTotal">
                            <td colspan="3" align="right">TOTAL CONTRACUENTAS:</td>
                            <td align="center"><?php echo $ctot_reg; ?></td>
                            <td align="right" style="padding-right:15px;"><?php echo number_format($ctot_val, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
                <p style="font-size:10px; font-style:italic; color:#666; margin-top:0px; margin-bottom:15px;">
                    Nota: Los valores de Caja/Bancos y Retenciones se registran en el diario general y auxiliar de bancos. Los guiones (&mdash;) indican que los montos se determinan al momento del pago de cada factura. El Cr&eacute;dito Tributario IVA corresponde al valor total de IVA en compras gravadas al <?php echo $row_rs_PorIva['Iva_Por']; ?>%, compensable en la declaraci&oacute;n mensual ante el SRI.
                </p>
                <?php endif; ?>

                <?php
                /* ---- TABLA VENTAS POR CUENTA CONTABLE ---- */
                if (count($rs_ventas) > 0 && $bVen == 1): ?>
                <table width="100%" border="1" style="border-collapse:collapse;margin-bottom:6px" cellpadding="3" cellspacing="0">
                    <thead>
                        <tr class="Texto_Listados">
                            <th colspan="7" style="color:#FFF;" bgcolor="#025ECC">
                                <div align="center">VENTAS</div>
                            </th>
                        </tr>
                        <tr class="Texto_Listados CabeceraTabla">
                            <th width="10%">C&oacute;d.</th>
                            <th width="30%">Cta. Contable</th>
                            <th width="15%">Transacci&oacute;n</th>
                            <th width="10%">No. Reg.</th>
                            <th width="12%">BI Tarifa 0%</th>
                            <th width="12%">BI Tarifa Diferente 0%</th>
                            <th width="11%">Valor IVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $vtot_reg=0; $vtot_b0=0; $vtot_bIva=0; $vtot_vIva=0;
                        foreach ($rs_ventas as $row) {
                            $vtot_reg   += $row['NumReg'];
                            $vtot_b0    += $row['Base0'];
                            $vtot_bIva  += $row['BaseIva'];
                            $vtot_vIva  += $row['ValIva'];
                        ?>
                        <tr class="txtValor">
                            <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                            <td><?php echo $row['Pld_Des']; ?></td>
                            <td><?php echo $row['Tic_Des']; ?></td>
                            <td align="center"><?php echo $row['NumReg']; ?></td>
                            <td align="right"><?php echo number_format($row['Base0'],    2); ?></td>
                            <td align="right"><?php echo number_format($row['BaseIva'],  2); ?></td>
                            <td align="right"><?php echo number_format($row['ValIva'],   2); ?></td>
                        </tr>
                        <?php } ?>
                        <tr class="txtValor FilaTotal">
                            <td colspan="3" align="right">TOTAL GENERAL:</td>
                            <td align="center"><?php echo $vtot_reg; ?></td>
                            <td align="right"><?php echo number_format($vtot_b0,    2); ?></td>
                            <td align="right"><?php echo number_format($vtot_bIva,  2); ?></td>
                            <td align="right"><?php echo number_format($vtot_vIva,  2); ?></td>
                        </tr>
                    </tbody>
                </table>
                <table width="100%" border="1" style="border-collapse:collapse;margin-bottom:6px" cellpadding="3" cellspacing="0">
                    <thead>
                        <tr class="Texto_Listados">
                            <th colspan="5" style="color:#FFF;" bgcolor="#5D4077">
                                <div align="left">&nbsp;Resumen &mdash; Contracuentas de ventas (debe)</div>
                            </th>
                        </tr>
                        <tr class="Texto_Listados" bgcolor="#2D2A4B" style="color:#FFF;">
                            <th width="15%">C&oacute;d. debe</th>
                            <th width="35%">Contracuenta</th>
                            <th width="20%">Tipo de movimiento</th>
                            <th width="15%">No. registros</th>
                            <th width="15%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $vctot_reg=0; $vctot_val=0;
                        foreach ($rs_contra_vta as $row) {
                            $vctot_reg   += $row['NumReg'];
                            $vctot_val   += $row['Total'];
                            
                            $vlabel_tipo = $row['Tic_Des'];
                            $vbg_tipo    = '#e6f2ff'; 
                            $vcolor_tipo = '#0055ff';
                            if ($row['Tic_Sri'] == '04') { $vlabel_tipo = 'NOTA CR&Eacute;DITO'; $vbg_tipo = '#fff2e6'; $vcolor_tipo = '#b38f00'; }
                            if ($row['Tic_Sri'] == '05') { $vlabel_tipo = 'NOTA D&Eacute;BITO';   $vbg_tipo = '#f2ffe6'; $vcolor_tipo = '#008037'; }
                        ?>
                        <tr class="txtValor">
                            <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                            <td style="padding-left:10px;"><?php echo $row['Pld_Des']; ?></td>
                            <td align="center">
                                <span style="background-color:<?php echo $vbg_tipo; ?>; color:<?php echo $vcolor_tipo; ?>; padding:2px 8px; border-radius:4px; font-weight:bold; font-size:9px; text-transform:uppercase;">
                                    <?php echo $vlabel_tipo; ?>
                                </span>
                            </td>
                            <td align="center"><?php echo $row['NumReg']; ?></td>
                            <td align="right" style="padding-right:15px;"><?php echo number_format($row['Total'], 2); ?></td>
                        </tr>
                        <?php } ?>
                        <tr class="txtValor FilaTotal">
                            <td colspan="3" align="right">TOTAL CONTRACUENTAS:</td>
                            <td align="center"><?php echo $vctot_reg; ?></td>
                            <td align="right" style="padding-right:15px;"><?php echo number_format($vctot_val, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <br>
    <p class="Texto_Listados"><strong>RESUMEN DE RETENCIONES</strong></p>

    <!-- RETENCION EN LA FUENTE DE IMPUESTO A LA RENTA -->
    <table width="100%" border="1" style="border-collapse:collapse;margin-bottom:8px" cellpadding="2" cellspacing="0">
        <thead>
            <tr class="Texto_Listados">
                <th colspan="6" style="color:#FFF;" bgcolor="#025ECC">
                    <div align="center">RETENCIONES EN COMPRAS - IMPUESTO A LA RENTA</div>
                </th>
            </tr>
            <tr class="Texto_Listados" bgcolor="#99CCCC">
                <th width="10%">Cta. Contable</th>
                <th width="35%">Nombre Cuenta</th>
                <th width="5%">C&oacute;d.</th>
                <th width="30%">Concepto de Retenci&oacute;n</th>
                <th width="10%">No. Reg.</th>
                <th width="10%">Valor Retenido</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sumReg = 0; $sumRta = 0;
            // Retenciones en COMPRAS
            foreach ($rs_ret_cmp_rta as $row) {
                $sumReg  += $row['NumReg'];
                $sumRta  += $row['ValRet'];
            ?>
            <tr class="txtValor">
                <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                <td align="left"><?php echo $row['Pld_Des']; ?></td>
                <td align="center"><?php echo $row['Ren_Sri']; ?></td>
                <td align="left"><?php echo $row['Ren_Con']; ?></td>
                <td align="center"><?php echo $row['NumReg']; ?></td>
                <td align="right"><?php echo number_format($row['ValRet'],  2); ?></td>
            </tr>
            <?php } ?>
            <tr class="txtValor FilaTotal">
                <td colspan="4" align="right"><strong>Total:</strong></td>
                <td align="center"><strong><?php echo $sumReg; ?></strong></td>
                <td align="right"><strong><?php echo number_format($sumRta,  2); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <!-- RETENCION EN LA FUENTE DE IVA -->
    <table width="100%" border="1" style="border-collapse:collapse;margin-bottom:8px" cellpadding="2" cellspacing="0">
        <thead>
            <tr class="Texto_Listados">
                <th colspan="5" style="color:#FFF;" bgcolor="#025ECC">
                    <div align="center">RETENCIONES EN COMPRAS - IVA</div>
                </th>
            </tr>
            <tr class="Texto_Listados" bgcolor="#99CCCC">
                <th width="10%">Cta. Contable</th>
                <th width="35%">Nombre Cuenta</th>
                <th width="35%">Concepto de Retenci&oacute;n</th>
                <th width="10%">No. Reg.</th>
                <th width="10%">Valor Retenido</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $iReg = 0; $iVal = 0;
            // Retenciones en COMPRAS
            foreach ($rs_ret_cmp_iva as $row) {
                $iReg  += $row['NumReg'];
                $iVal  += $row['ValRet'];
            ?>
            <tr class="txtValor">
                <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                <td align="left"><?php echo $row['Pld_Des']; ?></td>
                <td align="left"><?php echo $row['Ren_Con']; ?></td>
                <td align="center"><?php echo $row['NumReg']; ?></td>
                <td align="right"><?php echo number_format($row['ValRet'],  2); ?></td>
            </tr>
            <?php } ?>
            <tr class="txtValor FilaTotal">
                <td colspan="3" align="right"><strong>Total:</strong></td>
                <td align="center"><strong><?php echo $iReg; ?></strong></td>
                <td align="right"><strong><?php echo number_format($iVal,  2); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <!-- RESUMEN DE RETENCIONES QUE LE EFECTUARON EN EL PERIODO -->
    <table width="100%" border="1" style="border-collapse:collapse;margin-bottom:8px" cellpadding="2" cellspacing="0">
        <thead>
            <tr class="Texto_Listados">
                <th colspan="6" style="color:#FFF;" bgcolor="#025ECC">
                    <div align="center">RETENCIONES EN VENTAS (QUE LE EFECTUARON EN EL PERIODO)</div>
                </th>
            </tr>
            <tr class="Texto_Listados" bgcolor="#99CCCC">
                <th width="10%">Cta. Contable</th>
                <th width="25%">Nombre Cuenta</th>
                <th width="10%">Operaci&oacute;n</th>
                <th width="35%">Concepto de Retenci&oacute;n</th>
                <th width="10%">No. Reg.</th>
                <th width="10%">Valor Retenido</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totRReg = 0; $totRVal = 0;
            // Renta en Ventas
            foreach ($rs_ret_vta_rta as $row) {
                $totRReg += $row['NumReg']; $totRVal += $row['ValRet'];
            ?>
            <tr class="txtValor">
                <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                <td align="left"><?php echo $row['Pld_Des']; ?></td>
                <td align="center">VENTA</td>
                <td align="left"><?php echo $row['Ren_Con']; ?> (Renta)</td>
                <td align="center"><?php echo $row['NumReg']; ?></td>
                <td align="right"><?php echo number_format($row['ValRet'],  2); ?></td>
            </tr>
            <?php }
            // IVA en Ventas
            foreach ($rs_ret_vta_iva as $row) {
                $totRReg += $row['NumReg']; $totRVal += $row['ValRet'];
            ?>
            <tr class="txtValor">
                <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                <td align="left"><?php echo $row['Pld_Des']; ?></td>
                <td align="center">VENTA</td>
                <td align="left"><?php echo $row['Ren_Con']; ?> (IVA)</td>
                <td align="center"><?php echo $row['NumReg']; ?></td>
                <td align="right"><?php echo number_format($row['ValRet'],  2); ?></td>
            </tr>
            <?php } ?>
            <tr class="txtValor FilaTotal">
                <td colspan="4" align="right"><strong>Total:</strong></td>
                <td align="center"><strong><?php echo $totRReg; ?></strong></td>
                <td align="right"><strong><?php echo number_format($totRVal, 2); ?></strong></td>
            </tr>
        </tbody>
    </table>


    <p class="txtValor"><strong>Fecha de Generaci&oacute;n:</strong>&nbsp;&nbsp;<?php echo date("d/m/Y H:i:s"); ?></p>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
