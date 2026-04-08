<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../LOGICA/tes_log_carga_masiva.php');
require_once('../../Librerias/Xml/XML.php');
require_once('../../Librerias/FactElect/FirmaElectronica.php');

$obBD_conexion_set = new Class_Log_Conexion_Carga($Ses_Dat_Dis);
$obBD_con_set =  new Class_Log_Datos_Carga;
ini_set("memory_limit", "32M");
ini_set('max_execution_time', 300);
$obBD_con1 = new MysqlDatos(true);
$hoy = date("Y-m-d");

function tipoDoc($tipo)
{
    $ar = array(
        "01" => "FACTURA",
        "02" => "NOTA DE VENTA",
        "03" => "LIQUIDACION DE COMPRA",
        "04" => "NOTA DE CR&Eacute;DITO",
        "05" => "NOTA DE D&Eacute;BITO",
        "06" => "GUIA DE REMISION",
        "07" => "RETENCION"
    );
    return isset($ar[$tipo]) ? $ar[$tipo] : 'INDEFINIDO';
}
if (isset($formResumen)) {
    require_once('../../Librerias/Xml/XML.php');
    $responce = array('success' => false);
    $tot = count($_FILES["archivoXML"]['name']);
    $rows = array();
    $resumen = array();
    try {
        if ($tot == 0) throw new Exception("No se ha encontrado ningun archivo!");
        for ($i = 0; $i < $tot; $i++) { //este for recorre el arreglo
            $explode_name = explode('.', $_FILES['archivoXML']['name'][$i]);
            if (strtoupper($explode_name[1]) != 'XML') throw new Exception("La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)");
            $xml = XmlDoc::createFromFileToArray($_FILES["archivoXML"]["tmp_name"][$i]);
            if (!isset($xml['iva'])) throw new Exception("El archivo " . $_FILES["archivoXML"]["name"][$i] . " no es un ATS válido!");
            $sri = $xml['iva']; //var_dump($sri);
            //$responce['empresa']='<b>EMPRESA(S)&raquo;</b>&nbsp; '.$sri->razonSocial.'('.$sri->IdInformante.')  ';
            $anio = $sri['Anio'];
            $mes = $sri['Mes'];
            $totBase = 0;
            $totIva = 0;
            if (isset($sri['compras']) && isset($sri['compras']['detalleCompras']))
                foreach ($sri['compras']['detalleCompras'] as $dato) {
                    $banCond = true;
                    $autRet = '';
                    if (isset($txtRuc) && trim($txtRuc) != '') {
                        $banCond = false;
                        $arrRuc = explode(';', $txtRuc);
                        foreach ($arrRuc as $ruc)
                            if ($ruc == $dato['idProv']) {
                                $banCond = true;
                                break;
                            }
                    }
                    if ($banCond) {
                        if (isset($dato['autRetencion1'])) $autRet = $dato['autRetencion1'];
                        $totBase += $dato['baseImponible'] * 1;
                        $totIva += $dato['montoIva'] * 1;
                        //die(var_dump($total));
                        $row_rs_nomProv = $obBD_con1->getRowConsulta('proveedore.selectWhere', array('clean' => true, 'unsetCols' => true, 'addCols' => array('proveedore' => 'Prv_Cod', 'persona' => '*'), 'where' => array('Prs_Ced' => $dato['idProv']), 'join' => array('persona' => array('on' => 'proveedore.Prs_Cod=persona.Prs_Cod'))));
                        $total = floatval($dato['baseImponible']) + floatval($dato['baseImpGrav']) + floatval($dato['montoIva']);
                        /*suma todos los codigos de retencion(Renta,Iva) de la compra*/
                        $totRete = 0;
                        $TotRen = 0;
                        $SriRet = array();
                        $PorRet = array();
                        if (isset($dato['air']) && isset($dato['air']['detalleAir'])) {
                            if (!isset($dato['air']['detalleAir'][0])) $dato['air']['detalleAir'] = array($dato['air']['detalleAir']);
                            foreach ($dato['air']['detalleAir'] as $datosRet) {
                                $totRete += floatval($datosRet['valRetAir']);
                                $TotRen += floatval($datosRet['valRetAir']);
                                array_push($SriRet, !isset($datosRet['codRetAir']) || empty($datosRet['codRetAir']) ? '332' : $datosRet['codRetAir']);
                                array_push($PorRet, (!isset($datosRet['porcentajeAir']) || empty($datosRet['porcentajeAir']) ? '0' : $datosRet['porcentajeAir'] * 1) . "%");
                            }
                        }
                        $totRete += floatval($dato['valRetBien10'] * 1 + $dato['valRetServ20'] * 1 + $dato['valorRetBienes'] * 1 + $dato['valRetServ50'] + $dato['valorRetServicios'] + $dato['valRetServ100']);
                        $fila = array(
                            //'id'=>($i+1),'anio'=>$anio,'mes'=>$mes,
                            'periodo' => "$anio-$mes",
                            'idEmpresa' => $sri['IdInformante'],
                            'empresa' => $sri['razonSocial'],
                            'ruc' => $dato['idProv'],
                            'prov' => $row_rs_nomProv['Prs_Ape'] . ' ' . $row_rs_nomProv['Prs_Nom'],
                            'fecha'     => $dato['fechaEmision'],
                            'sustento' => $dato['codSustento'],
                            'tipo' => $dato['tipoComprobante'],
                            'tipo_long' => tipoDoc($dato['tipoComprobante']),
                            'documento' => "$dato[establecimiento]-$dato[puntoEmision]-" . str_pad($dato['secuencial'], 9, "0", STR_PAD_LEFT),
                            'autorizacion' => $dato['autorizacion'],
                            'sub0' => $dato['baseImponible'],
                            'sub12' => $dato['baseImpGrav'],
                            'iva' => $dato['montoIva'],
                            'total' => $total,
                            'retencion' => isset($dato['estabRetencion1']) ? "$dato[estabRetencion1]-$dato[ptoEmiRetencion1]-$dato[secRetencion1]" : '',
                            'aut_retencion' => isset($dato['autRetencion1']) ? $dato['autRetencion1'] : '',
                            'codsri'    => implode(",", $SriRet),
                            'porrenta'  => implode(",", $PorRet),
                            'renta'     => $TotRen,
                            'iva10'     => $dato['valRetBien10'],
                            'iva20'     => $dato['valRetServ20'],
                            'iva30'     => $dato['valorRetBienes'],
                            'iva70'     => $dato['valorRetServicios'],
                            'iva100'    => $dato['valRetServ100'],
                            'valret' => $totRete
                        );
                        array_push($rows, $fila); //$obBD_con1->echoLog($fila);
                    }
                }  //fin for($x=0;$x<$totCom;$x++)
        } //fin for ($i = 0; $i < $tot; $i++)
        if (isset($isAgrupado) && $isAgrupado == 'S') {
            $unset = array('documento' => '', 'autorizacion' => '', 'retencion' => '', 'aut_retencion' => '', 'codsri' => '', 'porrenta' => '');
            $nc = -1;
            switch ($group) {
                case 'ruc':
                    $unset = array_merge($unset, array('fecha' => '', 'tipo' => '', 'tipo_long' => '', 'periodo' => ''));
                    break;
                case 'fecha':
                    $unset = array('ruc' => '', 'prov' => '', 'tipo' => '', 'tipo_long' => '', 'periodo' => '');
                    break;
                case 'tipo_long':
                    $nc = 1;
                    $unset = array('fecha' => '', 'ruc' => '', 'prov' => '', 'periodo' => '');
                    break;
                case 'periodo':
                    $unset = array_merge($unset, array('ruc' => '', 'prov' => '', 'fecha' => '', 'tipo' => '', 'tipo_long' => ''));
                    break;
            }
            foreach ($rows as $d) {
                $add = true;
                foreach ($resumen as &$r) {
                    if ($r['idEmpresa'] == $d['idEmpresa'] && $r[$group] == $d[$group]) {
                        $auxR = array(
                            'sub0'  => $r['sub0'] * 1 + $d['sub0'] * ($d['tipo'] == '04' ? $nc : 1),
                            'sub12' => $r['sub12'] * 1 + $d['sub12'] * ($d['tipo'] == '04' ? $nc : 1),
                            'iva'   => $r['iva'] * 1 + $d['iva'] * ($d['tipo'] == '04' ? $nc : 1),
                            'total' => $r['total'] * 1 + $d['total'] * ($d['tipo'] == '04' ? $nc : 1),
                            'renta' => $r['renta'] * 1 + $d['renta'] * ($d['tipo'] == '04' ? $nc : 1),
                            'iva10' => $r['iva10'] * 1 + $d['iva10'] * ($d['tipo'] == '04' ? $nc : 1),
                            'iva20' => $r['iva20'] * 1 + $d['iva20'] * ($d['tipo'] == '04' ? $nc : 1),
                            'iva30' => $r['iva30'] * 1 + $d['iva30'] * ($d['tipo'] == '04' ? $nc : 1),
                            'iva70' => $r['iva70'] * 1 + $d['iva70'] * ($d['tipo'] == '04' ? $nc : 1),
                            'iva100' => $r['iva100'] * 1 + $d['iva100'] * ($d['tipo'] == '04' ? $nc : 1),
                            'valret' => $r['valret'] * 1 + $d['valret'] * ($d['tipo'] == '04' ? $nc : 1)
                        ); /*$obBD_con1->echoLog($d['tipo']);*/ /*$obBD_con1->echoLog($auxR);*/
                        $r = array_merge($r, $auxR);
                        $add = false;
                        break;
                    }
                }
                unset($r);
                if ($add) array_push($resumen, array_merge(array_merge($d, $unset), array(
                    'sub0' => $d['sub0'] * ($d['tipo'] == '04' ? $nc : 1),
                    'sub12' => $d['sub12'] * ($d['tipo'] == '04' ? $nc : 1),
                    'iva' => $d['iva'] * ($d['tipo'] == '04' ? $nc : 1),
                    'total' => $d['total'] * ($d['tipo'] == '04' ? $nc : 1),
                    'renta' => $d['renta'] * ($d['tipo'] == '04' ? $nc : 1),
                    'iva10' => $d['iva10'] * ($d['tipo'] == '04' ? $nc : 1),
                    'iva20' => $d['iva20'] * ($d['tipo'] == '04' ? $nc : 1),
                    'iva30' => $d['iva30'] * ($d['tipo'] == '04' ? $nc : 1),
                    'iva70' => $d['iva70'] * ($d['tipo'] == '04' ? $nc : 1),
                    'iva100' => $d['iva100'] * ($d['tipo'] == '04' ? $nc : 1),
                    'valret' => $d['valret'] * ($d['tipo'] == '04' ? $nc : 1)
                )));
            }
            $responce['rows'] = $resumen;
        } else $responce['rows'] = $rows;
        $responce['success'] = true;
    } catch (Exception $e) {
        $responce['message'] = '<b class="red">ERROR:</b> ' . $e->getMessage();
    }
    $obBD_con1->echoJson($responce);
}


if (isset($formConvertAts)) {
    require_once('../../Librerias/Xml/XML.php');
    $responce = array('success' => false);
    $tot = count($_FILES["archivoXML"]["name"]);
    //este for recorre el arreglo
    try {
        $rows = array();
        if (!($tot > 0 && (!empty($_FILES["archivoXML"]["name"][0])))) throw new Exception("No se ha encontrado ningun archivo!");
        for ($i = 0; $i < $tot; $i++) {
            $explode_name = explode('.', $_FILES['archivoXML']['name'][$i]);
            if (strtoupper($explode_name[1]) != 'XML')  throw new Exception("La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)");
            $sri = XmlDoc::createFromFile($_FILES["archivoXML"]["tmp_name"][$i]);
            $sri->setMainComment('Actualizado por Exa (http://www.exa.ofsercont.com) ' . date("Y-m-d"), false);
            $empresa = '<b>COMPRAS&raquo;</b>&nbsp; ' . $sri->IdInformante . ' - ' . $sri->razonSocial;
            $anio = $sri->Anio->text();
            $mes = $sri->Mes->text();
            $fecha = $anio . '-' . $mes;
            if ($anio == "") $responce['message'] = "El archivo " . $_FILES["archivoXML"]["name"][$i] . " no es un ATS Valido!";
            if ($sri->compras->tot()) {
                $datosCom = $sri->compras[0]->detalleCompras;
                $totCom = count($datosCom);
                /* C O M P R A S */
                for ($x = 0; $x < $totCom; $x++) {
                    if (!$datosCom[$x]->parteRel->tot()) {
                        $target = $datosCom[$x]->tipoComprobante;
                        if ($target->tot()) $target->addAfter("<parteRel>NO</parteRel>", false);
                    }
                    if (!$datosCom[$x]->baseImpExe->tot()) {
                        $target = $datosCom[$x]->baseImpGrav;
                        if ($target->tot()) $target->addAfter("<baseImpExe>0.00</baseImpExe>", false);
                    }
                    if ($anio >= '2016' && $mes >= '01' && !$datosCom[$x]->valRetServ50->tot()) {
                        $target = $datosCom[$x]->valorRetBienes;
                        if ($target->tot()) $target->addAfter("<valRetServ50>0.00</valRetServ50>", false);
                    }
                    $target = $datosCom[$x]->montoIva;
                    if ($target->tot()) {
                        if (!$datosCom[$x]->valRetServ20->tot()) $target->addAfter("<valRetServ20>0.00</valRetServ20>", false);
                        if (!$datosCom[$x]->valRetBien10->tot()) $target->addAfter("<valRetBien10>0.00</valRetBien10>", false);
                    }
                    if ($datosCom[$x]->pagoExterior->tot()) {
                        if (!$datosCom[$x]->pagoExterior->pagoRegFis->tot()) {
                            $target = $datosCom[$x]->pagoExterior->pagExtSujRetNorLeg;
                            if ($target->tot())  $target->addAfter("<pagoRegFis>NA</pagoRegFis>", false);
                        }
                    } else {
                        //echo ",,,";
                        $target = $datosCom[$x]->valRetServ100;
                        if ($target->tot()) {
                            $target->addAfter("<pagoExterior><pagoLocExt>01</pagoLocExt><paisEfecPago>NA</paisEfecPago><aplicConvDobTrib>NA</aplicConvDobTrib><pagExtSujRetNorLeg>NA</pagExtSujRetNorLeg><pagoRegFis>NA</pagoRegFis></pagoExterior>", false);
                        }
                    }
                    //echo ".,";
                    if (!$datosCom[$x]->totbasesImpReemb->tot()) {
                        $target = $datosCom[$x]->valRetServ100;
                        if ($target->tot()) $target->addAfter("<totbasesImpReemb>0.00</totbasesImpReemb>", false);
                    }
                    if ($datosCom[$x]->air->tot() && $datosCom[$x]->air->detalleAir->tot()) {
                        if ($datosCom[$x]->air->detalleAir->codRetAir->text() == '341')
                            $datosCom[$x]->air->detalleAir->codRetAir = '344';
                        if ($datosCom[$x]->air->detalleAir->codRetAir->text() == '340')
                            $datosCom[$x]->air->detalleAir->codRetAir = '312';
                    }
                    if (('0' . $datosCom[$x]->baseImponible) * 1 >= 1000) {
                        $target = $datosCom[$x]->pagoExterior;
                        if ($target->tot()) $target->addAfter("<formasDePago><formaPago>01</formaPago></formasDePago>", false);
                    }
                }
            }
            /*  V E N T A S */
            if ($sri->ventas->tot()) {
                $datosVen = $sri->ventas[0]->detalleVentas;
                $totVen = count($datosVen);
                for ($x = 0; $x < $totVen; $x++) {
                    if ($datosVen[$x]->tpIdCliente->text() != '07') {
                        if ($fecha >= '2016-01') {
                            if (!$datosVen[$x]->parteRelVtas->tot()) {
                                if ($fecha >= '2016-05' && $datosVen[$x]->tpIdCliente->text() == '06' && !$datosVen[$x]->tipoCliente->tot()) {
                                    $datosVen[$x]->tpIdCliente->addAfter("<parteRelVtas>NO</parteRelVtas>", false);
                                    $datosVen[$x]->parteRelVtas->addAfter("<tipoCliente>01</tipoCliente>", false);
                                    $datosVen[$x]->tipoCliente->addAfter("<denoCli>NINGUNO</denoCli>", false);
                                } else {
                                    $datosVen[$x]->idCliente->addAfter("<parteRelVtas>NO</parteRelVtas>", false);
                                }
                            } else {
                                if ($fecha >= '2016-05' && $datosVen[$x]->tpIdCliente->text() == '06' && !$datosVen[$x]->tipoCliente->tot()) {
                                    $datosVen[$x]->parteRelVtas->addAfter("<tipoCliente>01</tipoCliente>", false);
                                    $datosVen[$x]->tipoCliente->addAfter("<denoCli>NINGUNO</denoCli>", false);
                                }
                            }
                        }
                    }
                    if (!$datosVen[$x]->tipoEmision->tot()) {
                        $target = $datosVen[$x]->tipoComprobante;
                        if ($target->tot()) $target->addAfter("<tipoEmision>F</tipoEmision>", false);
                    }
                    if ($fecha >= '2016-06' && !$datosVen[$x]->formasDePago->tot()) {
                        $target = $datosVen[$x]->valorRetRenta;
                        if ($target->tot() && $datosVen[$x]->tipoComprobante->text() != '04') $target->addAfter("<formasDePago><formaPago>01</formaPago></formasDePago>", false);
                    }
                    if ($fecha >= '2015-03' && !$datosVen[$x]->montoIce->tot()) {
                        $target = $datosVen[$x]->montoIva;
                        if ($target->tot()) $target->addAfter("<montoIce>0.00</montoIce>", false);
                    }
                }
            }
            array_push($rows, array(
                'empresa' => $empresa,
                'anio' => $anio,
                'mes' => $mes,
                'nombre' => $_FILES['archivoXML']['name'][$i],
                'xml' => ($anio != "" ? $sri->asXML() : $responce['message'])
            )); //echo  $sri->asXML(); die();
            $responce['message'] = '';
        }
        $responce['success'] = true;
        $responce['rows'] = $rows;
    } catch (Exception $e) {
        $responce['success'] = false;
        $responce['message'] = '<b class="red">ERROR:</b> ' . $e->getMessage();
    }
    $obBD_con1->echoJson($responce);
}

if (isset($formElectronicos)) {
    require_once('../../Librerias/Xml/XML.php');
    $responce = array('success' => false, 'empresa' => '<b>EMPRESA(S)&raquo;</b>&nbsp; ');
    $tot = count($_FILES["archivoXML"]["name"]);
    $z = 0;
    $rows = array();
    try {
        if ($tot == 0) throw new Exception("No se ha encontrado ningun archivo!");
        for ($i = 0; $i < $tot; $i++) {
            $z++; //este for recorre el arreglo
            $explode_name = explode('.', $_FILES['archivoXML']['name'][$i]);

            if (strtoupper($explode_name[1]) != 'XML') throw new Exception("La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)");
            $sri = XmlDoc::createFromFile($_FILES["archivoXML"]["tmp_name"][$i]);
            if ($sri->estado->text() == "AUTORIZADO") { // acepta xml autorizados por el sri
                $sri = XmlDoc::createFromString($sri->comprobante);
            }
            if (is_null($sri->infoTributaria->ruc)) continue; //throw new Exception("El archivo ".$_FILES["archivoXML"]["name"][$i]." no es un documento del SRI!");
            array_push($rows, array(
                'id'     => $z,
                'ruc'    => $sri->infoTributaria->ruc->text(),
                'tipo'   => tipoDoc(substr($sri->infoTributaria->claveAcceso->text(), 8, 2)),
                'numero' => $sri->infoTributaria->codDoc . '-' . $sri->infoTributaria->ptoEmi . '-' . $sri->infoTributaria->secuencial,
                'numero2' => $sri->impuestos->impuesto[0]->numDocSustento->text(),
                'clave'  => $sri->infoTributaria->claveAcceso->text(),
                'fecha'  => $sri->infoFactura->fechaEmision . $sri->infoCompRetencion->fechaEmision . $sri->infoNotaCredito->fechaEmision,
            ));
            $responce['empresa'] .= $sri->infoTributaria->ruc->text() . ' ';
        } //fin for ($i = 0; $i < $tot; $i++)
        if (count($rows) == 0) throw new Exception("No se encontro documentos electronicos en los XMLs!");
        $responce['success'] = true;
        $responce['rows'] = $rows;
    } catch (Exception $e) {
        $responce['success'] = false;
        $responce['message'] = '<b class="red">ERROR:</b> ' . $e->getMessage();
    }
    $obBD_con1->echoJson($responce);
}


if (isset($formExporta)) {

    require_once('../../Librerias/Xml/XML.php');
    $responce = array('success' => false, 'empresa' => '<b>EMPRESA(S)&raquo;</b>&nbsp; ');
    $tot = count($_FILES["archivoXML"]["name"]);
    $z = 0;
    $rows = array();
    try {
        if ($tot == 0) throw new Exception("No se ha encontrado ningun archivo!");
        for ($i = 0; $i < $tot; $i++) {
            $z++; //este for recorre el arreglo
            $explode_name = explode('.', $_FILES['archivoXML']['name'][$i]);
            if (strtoupper($explode_name[1]) != 'XML') throw new Exception("La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)");
            $sri = XmlDoc::createFromFile($_FILES["archivoXML"]["tmp_name"][$i]);
            if (is_null($sri->exportaciones[0])) continue; //throw new Exception("El archivo ".$_FILES["archivoXML"]["name"][$i]." no contiene exportaciones!");
            $totExp = count($sri->exportaciones[0]->detalleExportaciones);
            $datos = $sri->exportaciones;
            $anio = $sri->Anio->text();
            $mes = $sri->Mes->text();
            $totFac = 0;
            $totFob = 0;
            for ($x = 0; $x < $totExp; $x++) {
                $auxNum = 1;
                $totFac += $datos->detalleExportaciones[$x]->valorFOBComprobante;
                $totFob += $datos->detalleExportaciones[$x]->valorFOB;
                if ($datos->detalleExportaciones[$x]->tipoComprobante == '04' || $datos->detalleExportaciones[$x]->tipoComprobante == '4') {
                    $auxNum = -1;
                }
                array_push($rows, array(
                    'ref'        => '' . $datos->detalleExportaciones[$x]->distAduanero . '-' . $datos->detalleExportaciones[$x]->anio . '-' . $datos->detalleExportaciones[$x]->regimen . '-' . $datos->detalleExportaciones[$x]->correlativo,
                    'trans' => '' . $datos->detalleExportaciones[$x]->docTransp,
                    'fecha' => '' . $datos->detalleExportaciones[$x]->fechaEmbarque,
                    'serie' => '' . $datos->detalleExportaciones[$x]->establecimiento . '-' . $datos->detalleExportaciones[$x]->puntoEmision,
                    'num' => str_pad($datos->detalleExportaciones[$x]->secuencial, 9, "0", STR_PAD_LEFT),
                    'autorizacion' => '' . $datos->detalleExportaciones[$x]->autorizacion,
                    'val_fac' => ('' . $datos->detalleExportaciones[$x]->valorFOBComprobante) * $auxNum,
                    'val_fob' => ('' . $datos->detalleExportaciones[$x]->valorFOB) * $auxNum,
                ));
            } // fin for($x=0;$x<$totExp;$x++)
            $responce['empresa'] .= $sri->IdInformante . ' ';
        } //fin for($i = 0; $i < $tot; $i++)
        if (count($rows) == 0) throw new Exception("No se encontro Exportaciones en los XMLs!");
        $responce['success'] = true;
        $responce['rows'] = $rows;
    } catch (Exception $e) {
        $responce['success'] = false;
        $responce['message'] = '<b class="red">ERROR:</b> ' . $e->getMessage();
    }
    $obBD_con1->echoJson($responce);
}

if (isset($formDevolIva)) {
    require_once('../../Librerias/Xml/XML.php');
    $responce = array('success' => false, 'message' => "No se ha encontrado ningun archivo!");
    $tot = count($_FILES["archivoXML"]["name"]);
    $omitir02 = isset($omitir02) && $omitir02 == 'S';
    $omitirnc = isset($omitirnc) && $omitirnc == 'S';
    $bancarizacion = 5000;
    //este for recorre el arreglo
    try {
        if ($tot == 0) throw new Exception("No se ha encontrado ningun archivo!");
        $rows = array();
        $nc = array();
        for ($i = 0; $i < $tot; $i++) {
            $explode_name = explode('.', $_FILES['archivoXML']['name'][$i]);
            if (strtoupper($explode_name[1]) != 'XML') throw new Exception("La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)");

            $xml = XmlDoc::createFromFileToArray($_FILES["archivoXML"]["tmp_name"][$i]);
            if (!isset($xml['iva'])) throw new Exception("El archivo " . $_FILES["archivoXML"]["name"][$i] . " no es un ATS válido!");
            $sri = $xml['iva'];
            $datos = isset($sri['compras']) && isset($sri['compras']['detalleCompras']) ? $sri['compras']['detalleCompras'] : array();
            $responce['empresa'] = "<b>EMPRESA(S)&raquo;</b>&nbsp; $sri[razonSocial] ($sri[IdInformante])  ";

            foreach ($datos as $d)
                if ($d['tipoComprobante'] == "04") {
                    array_push($nc, array(
                        'numero'     => "$d[estabModificado]-$d[ptoEmiModificado]-$d[secModificado]",
                        'baseImpGrav' => $d['baseImpGrav'],
                        'montoIva'   => $d['montoIva'],
                    ));
                }
            $totales = array();
            foreach ($datos as $d) {
                if ($d['tipoComprobante'] == "01" || $d['tipoComprobante'] == "04") {
                    if (!$omitirnc || ($omitirnc && '' . $d['tipoComprobante'] != '04')) {
                        $add = true;
                        $val_monto = ($d['tipoComprobante'] == "04" ? -1 : 1) * ($d['baseImpGrav'] * 1) + ($d['montoIva'] * 1);
                        $totales[$d['idProv']] = $val_monto + (isset($totales[$d['idProv']]) ? $totales[$d['idProv']] : 0);
                    }
                }
            }
            foreach ($datos as $d) {
                $baseIvaCom = 0;
                $montoIvaCom = 0;
                $devIva = 0;
                if ((!$omitir02 || ($omitir02 && $d['codSustento'] != '02')) && (!$omitirnc || ($omitirnc && $d['tipoComprobante'] != '04'))) {
                    $tipo = tipoDoc('' . $d['tipoComprobante']);
                    $baseIvaCom = (float)$d['baseImpGrav'];
                    $montoIvaCom = (float)$d['montoIva'];
                    if ($omitirnc)
                        for ($y = 0; $y < count($nc); $y++) {
                            if ($nc[$y]['numero'] === "$d[establecimiento]-$d[puntoEmision]-$d[secuencial]") {
                                $devIva = $nc[$y]['montoIva'];
                                break;
                            }
                        }
                    if (($montoIvaCom - (float)$devIva) > 0) {
                        $rs_persona = $obBD_con1->getRowConsulta('proveedore.selectWhere', array('clean' => true, 'unsetCols' => true, 'addCols' => array('proveedore' => 'Prv_Cod', 'persona' => '*'), 'where' => array('Prs_Ced' => $d['idProv']), 'join' => array('persona' => array('on' => 'proveedore.Prs_Cod=persona.Prs_Cod'))));
                        $codigoFormato = $sri['IdInformante'] . ($d['tipoComprobante'] == '01' && $totales[$d['idProv']] >= $bancarizacion ? '_bancarizacion' : '') . '_' . strtolower($tipo) . '_' . intval($d['secuencial']) . '_' . $sri['Anio'] . '_' . strtolower(mes($sri['Mes'], 1));
                        array_push($rows, array(
                            'ruc'       => $d['idProv'],
                            'empresa'   => $sri['IdInformante'],
                            'anio'      => $sri['Anio'],
                            'mes'       => $sri['Mes'],
                            'tpIdProv'  => $d['tpIdProv'],
                            'tipoComprobante' => $tipo,
                            'codigo'    => $codigoFormato,
                            'tipo'      => $d['tipoComprobante'],
                            'sustento'  => $d['codSustento'],
                            'proveedor' => $rs_persona['Prs_Ape'] . ' ' . $rs_persona['Prs_Nom'],
                            'fecha'     => $d['fechaEmision'],
                            'estab'     => $d['establecimiento'],
                            'impre'     => $d['puntoEmision'],
                            'documento' => str_pad($d['secuencial'], 9, "0", STR_PAD_LEFT),
                            'autorizacion' => $d['autorizacion'],
                            'autret' => isset($d['autRetencion1']) && $d['autRetencion1'] * 1 != 0 ? $d['autRetencion1'] : '',
                            'base' => '' . $baseIvaCom,
                            'iva' => '' . $montoIvaCom,
                            'ivadev' => '' . ($montoIvaCom - (float)$devIva),
                        ));
                    }
                } //fin omitir sustento 02
            }  //fin for($x=0;$x<$totCom;$x++)
            $responce['success'] = true;
            $responce['rows'] = $rows;
        } //fin for ($i = 0; $i < $tot; $i++)
    } catch (Exception $e) {
        $responce['message'] = 'ERROR: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($responce);
}


if (isset($formBusquedaSri)) {
    $responce = array('success' => false, 'file' => $_FILES['archivoXML']['name']);
    $rows = array();
    try {
        $explode_name = explode('.', $_FILES['archivoXML']['name']);
        if (strtoupper($explode_name[1]) != 'TXT') throw new Exception("La extension del archivo debe ser <u>.TXT</u> (<i>Archivo de Texto Plano</i>)");
        //eliminar NUMERO_DOCUMENTO_MODIFICADO
        $contenido = file_get_contents($_FILES["archivoXML"]["tmp_name"]);
        //ChromePhp::log($contenido);
        $palabra_a_eliminar = 'NUMERO_DOCUMENTO_MODIFICADO';
        $contenido_modificado = preg_replace("/{$palabra_a_eliminar}\t+/", "", $contenido);
        $contenido_modificado = mb_convert_encoding($contenido_modificado, 'UTF-8', 'auto');
        $sri = explode("\n", $contenido_modificado);
        $keys = explode("\t", $sri[0]);
        array_push($keys, $sri[1]);
        $ruc = $obBD_con_set->getArrayConsulta(9, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion_set);
        foreach ($sri  as $i => $line) {
            if ($i > 1)
                if (strlen($line) > 49) {
                    $aux = array();
                    $data = explode("\t", str_replace("\t\t\t", "\t", str_replace("\t\t", "\t", $line)));
                    array_push($data, $sri[$i + 1]);
                    foreach ($keys as $j => $val) $aux[$val] = trim($data[$j]);
                    $aux['TIPO_DOC'] = substr($aux['CLAVE_ACCESO'], 8, 2);
                    if ($aux['IDENTIFICACION_RECEPTOR'] == $ruc[0]['Emp_Ruc']) {
                        array_push($rows, $aux);
                    }
                }
        }
        $responce['success'] = true;
        $responce['rows'] = $rows;
    } catch (Exception $e) {
        $responce['success'] = false;
        $responce['message'] = '<b class="red">ERROR:</b> ' . $e->getMessage();
    }
    $obBD_con1->echoJson($responce);
}

if (isset($downloadFileSri)) {
    $responce = array('success' => false, 'xml' => "", 'pdf' => "", 'name' => "$CLAVE_ACCESO.$type");
    require_once('../../Librerias/FactElect/FirmaElectronica.php');
    $DocElect = new FirmaElectronica();
    $DocElect->setProduction(true);

    if (isset($Carm_Cla)) {
        $CLAVE_ACCESO = $Carm_Cla;
    }

    $result = $DocElect->autorizarSri($CLAVE_ACCESO);
    //ChromePhp::log($result);

    if ($result['success']) {
        $responce['success'] = true;
        $responce['xml'] = base64_encode($result['xml']);
        if ($type == 'pdf' || $type == 'all') {
            require_once('../../facturacion/LOGICA/fac_log_electronica.php');
            function getTypeDoc($type)
            {
                $obBD_elect = null;
                switch ($type) {
                    case '01';
                        $obBD_elect =  new Class_Log_Datos_Factura_Elect;
                        break;
                    case '04';
                        $obBD_elect =  new Class_Log_Datos_NCredito_Elect;
                        break;
                    case '05';
                        $obBD_elect =  new Class_Log_Datos_NDebito_Elect;
                        break;
                    case '06';
                        $obBD_elect =  new Class_Log_Datos_Guia_Elect;
                        break;
                    case '07';
                        $obBD_elect =  new Class_Log_Datos_Retencion_Elect;
                        break;
                }
                return $obBD_elect;
            }
            $obBD_elect = getTypeDoc(substr($CLAVE_ACCESO, 8, 2));
            $responce['pdf'] = base64_encode($obBD_elect->createPdfByString($result['xml'], '', 'S'));
            if ($type == 'pdf') unset($responce['xml']);
        }
    } else {
        $responce['error'] = $result['message'];
    }
    $obBD_con1->echoJson($responce);
}

if (isset($preGuardar)) {
    $cantFilas = count($datosTabla);
    try {
        for ($i = 0; $i < $cantFilas; $i++) {
            $datosBd = $obBD_con_set->getArrayConsulta(7, array('Emp_Cod' => $Ses_Emp_Cod, 'Carm_Cla' => $datosTabla[$i]['CLAVE_ACCESO']), $obBD_conexion_set);
            $datosBd2 = $obBD_con_set->getArrayConsulta(8, array('Emp_Cod' => $Ses_Emp_Cod, 'Cop_Num' => $datosTabla[$i]['SERIE_COMPROBANTE'], 'Prs_Ced' => $datosTabla[$i]['RUC_EMISOR']), $obBD_conexion_set);
            $comprobar = count($datosBd);
            $comprobar2 = count($datosBd2);
            if ($comprobar == 0 && $comprobar2 == 0) {
                $obBD_con_set->operacionobBD(
                    1,
                    array(
                        'Carm_Ruc' => $datosTabla[$i]['RUC_EMISOR'],
                        'Carm_Emi' => $datosTabla[$i]['RAZON_SOCIAL_EMISOR'],
                        'Carm_Fec' => $datosTabla[$i]['FECHA_EMISION'],
                        'Carm_Rur' => $datosTabla[$i]['IDENTIFICACION_RECEPTOR'],
                        'Carm_Cod' => $datosTabla[$i]['TIPO_DOC'],
                        'Carm_Com' => $datosTabla[$i]['COMPROBANTE'],
                        'Carm_Num' => $datosTabla[$i]['SERIE_COMPROBANTE'],
                        'Carm_Cla' => $datosTabla[$i]['CLAVE_ACCESO'],
                        'Carm_Aut' => $datosTabla[$i]['NUMERO_AUTORIZACION'],
                        'Carm_Fea' => $datosTabla[$i]['FECHA_AUTORIZACION'],
                        'Carm_Tard' => $datosTabla[$i]['TARIFA12'],
                        'Carm_Tarc' => $datosTabla[$i]['TARIFA0'],
                        'Carm_Iva' => $datosTabla[$i]['IVA'],
                        'Carm_Tot' => $datosTabla[$i]['IMPORTE_TOTAL']
                    ),
                    $obBD_conexion_set
                );
            }
        }
        if ($obBD_con_set->Error == 0) {
            $response['success'] = true;
        } else {
            $response['error'] = $obBD_con_set->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
        $response['success'] = false;
    }

    $obBD_con_set->echoJson($response);
    exit();
}

if (isset($preGuardarPro)) {
    try {
        $datosBd = $obBD_con_set->getArrayConsulta(7, array('Emp_Cod' => $Ses_Emp_Cod, 'Carm_Cla' => $dataFila['CLAVE_ACCESO']), $obBD_conexion_set);
        $datosBd2 = $obBD_con_set->getArrayConsulta(8, array('Emp_Cod' => $Ses_Emp_Cod, 'Cop_Num' => $dataFila['SERIE_COMPROBANTE'], 'Prs_Ced' => $dataFila['RUC_EMISOR']), $obBD_conexion_set);
        $comprobar = count($datosBd);
        $comprobar2 = count($datosBd2);
        if ($comprobar == 0 && $comprobar2 == 0) {
            $obBD_con_set->operacionobBD(
                1,
                array(
                    'Carm_Ruc' => $dataFila['RUC_EMISOR'],
                    'Carm_Emi' => $dataFila['RAZON_SOCIAL_EMISOR'],
                    'Carm_Fec' => $dataFila['FECHA_EMISION'],
                    'Carm_Rur' => $dataFila['IDENTIFICACION_RECEPTOR'],
                    'Carm_Cod' => $dataFila['TIPO_DOC'],
                    'Carm_Com' => $dataFila['COMPROBANTE'],
                    'Carm_Num' => $dataFila['SERIE_COMPROBANTE'],
                    'Carm_Cla' => $dataFila['CLAVE_ACCESO'],
                    'Carm_Aut' => $dataFila['NUMERO_AUTORIZACION'],
                    'Carm_Fea' => $dataFila['FECHA_AUTORIZACION'],
                    'Carm_Tard' => $dataFila['TARIFA12'],
                    'Carm_Tarc' => $dataFila['TARIFA0'],
                    'Carm_Iva' => $dataFila['IVA'],
                    'Carm_Tot' => $dataFila['IMPORTE_TOTAL']
                ),
                $obBD_conexion_set
            );
            if ($obBD_con_set->Error == 0) {
                $response['success'] = true;
            } else {
                $response['error'] = $obBD_con_set->MsgError;
            }
        }
    } catch (Exception $e) {
        $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
        $response['success'] = false;
    }
    $obBD_con_set->echoJson($response);
    exit();
}

function buscarSubtotales($clave)
{
    require_once('../../Librerias/Xml/XML.php');
    require_once('../../Librerias/FactElect/FirmaElectronica.php');

    $DocElect = new FirmaElectronica();
    $res = $DocElect->autorizarSri($clave);
    if ($res['success'] == true) {
        $xml_aut = new XmlDoc((!mb_detect_encoding($res['xml'], 'UTF-8', true)) ? utf8_encode($res['xml']) : $res['xml']);
        //var_dump((string)$xml_aut->comprobante);
    } else throw new Exception($res['message']);

    $data_xml = (string)$xml_aut->comprobante;
    $xml = new XmlDoc((!mb_detect_encoding($data_xml, 'UTF-8', true)) ? utf8_encode($data_xml) : $data_xml);
    if ($xml->getName() != 'factura') throw new Exception('El documento electronico no es una <u>Factura</u>!');
    $factI = $xml->infoFactura;

    $tarifa12 = '0';
    $tarifa0 = '0';
    $iva = '0';
    $subs = $factI->totalConImpuestos->totalImpuesto;
    $total = $factI->importeTotal->text();

    for ($x1 = 0; $x1 < count($subs); $x1++) {
        if ($subs[$x1]->codigoPorcentaje->text() == '2' && $subs[$x1]->baseImponible->text() * 1 > 0) {
            $tarifa12 = $subs[$x1]->baseImponible->text();
            $iva = $subs[$x1]->valor->text();
        }
        if ($subs[$x1]->codigoPorcentaje->text() == '0') {
            $tarifa0 = $subs[$x1]->baseImponible->text();
        }
    }

    $subsArray = array($tarifa12, $tarifa0, $iva, $total);
    return $subsArray;
}



if (isset($validar)) {
    try {
        $obBD_con_set->getArrayConsulta(10, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion_set);
        if ($obBD_con_set->Error == 0) {
            $response['success'] = true;
        } else {
            $response['error'] = $obBD_con_set->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
        $response['success'] = false;
    }
}


if (isset($colores)) {
    try {
        $datosBd = $obBD_con_set->getArrayConsulta(7, array('Emp_Cod' => $Ses_Emp_Cod, 'Carm_Cla' => $clave), $obBD_conexion_set);
        $datosBd2 = $obBD_con_set->getArrayConsulta(8, array('Emp_Cod' => $Ses_Emp_Cod, 'Cop_Num' => $cop_num, 'Prs_Ced' => $prs_ced), $obBD_conexion_set);

        if ($subtotalesPeti) {
            $subtotales = buscarSubtotales($clave);
        } else {
            $subtotales = "";
        }
        if ($obBD_con_set->Error == 0) {
            $response['success'] = true;
        } else {
            $response['error'] = $obBD_con_set->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
        $response['success'] = false;
    }
    $obBD_con_set->echoJson(array(
        'rows' => $datosBd,
        'rows2' => $datosBd2,
        'total' => 1,
        'subts' => $subtotales,
        'records' => count($response),
        'success' => $response['success']
    ));
}


if (isset($omitirFactura)) {
    $data['Carm_Est'] = 'O';
    $data['Carm_Id'] = $id;

    $responce = $obBD_con_set->getArrayConsulta(3, $data, $obBD_conexion_set);

    $obBD_con_set->echoJson(array(
        'rows' => $responce,
        'total' => 1,
        'records' => count($responde),
        'success' => true
    ));
}


if (isset($preCargarFactura)) {
    $data['Carm_Est'] = 'P';
    $data['Carm_Id'] = $id;
    $responce = $obBD_con_set->getArrayConsulta(3, $data, $obBD_conexion_set);

    $obBD_con_set->echoJson(array(
        'rows' => $responce,
        'total' => 1,
        'records' => count($responde),
        'success' => true
    ));
}


if (isset($searchDocument)) {
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $data['Valor'] = $_GET['num_doc'];
    $data['Opcion'] = $_GET['opc'];

    $responce = $obBD_con_set->getArrayConsulta(2, $data, $obBD_conexion_set);
    $obBD_con_set->echoJson(array(
        'rows' => $responce,
        'total' => 1,
        'records' => count($responde),
        'success' => true
    ));
}

if (isset($searchDocumentOmi)) {
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $data['Valor'] = $_GET['num_doc'];
    $data['Opcion'] = $_GET['opc'];

    $responce = $obBD_con_set->getArrayConsulta(4, $data, $obBD_conexion_set);

    $obBD_con_set->echoJson(array(
        'rows' => $responce,
        'total' => 1,
        'records' => count($responde),
        'success' => true
    ));
}

if (isset($searchDocumentExi)) {
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $data['Valor'] = $_GET['num_doc'];
    $data['Opcion'] = $_GET['opc'];

    $responce = $obBD_con_set->getArrayConsulta(5, $data, $obBD_conexion_set);
    $obBD_con_set->echoJson(array(
        'rows' => $responce,
        'total' => 1,
        'records' => count($responde),
        'success' => true
    ));
}


if (isset($cargarFactura)) {
    $_SESSION['idCargar'] = $idCarga;
    $_SESSION['claCargar'] = $claCarga;
    $_SESSION['cargaMasiva'] = true;

    $obBD_con_set->echoJson(array(
        'success' => true
    ));
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Compras Carga Masiva [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script type="text/javascript" src="../../framework/jquery/bootstrap/jqboot.checkbox.buttons.js"></script>
    <script src="../../framework/plugins/ace-editor/ace-1.2/ace.js"></script>
    <script src="../../framework/plugins/ace-editor/vkbeautify-0.99.js"></script>
    <script src="../VALIDACIONES/tes_val_carga_masiva.js"></script>
    <style>
        #editor {
            border-radius: 0 0 4px 4px;
        }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Carga Masiva de Documentos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <div id='tabsMain' class="ui-tabs ui-tab-fix noPaddingH noBorder">
                        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                            <li><a href="#tabs-1">Descarga Masiva</a></li>
                            <li><a href="#tabs-2">Pre Cargados</a></li>
                            <li><a href="#tabs-3">Cargas exitosas</a></li>
                            <li><a href="#tabs-4">Omitidos Carga</a></li>
                        </ul>

                        <div id="tabs-1" class="ui-tabs-panel">
                            <div class="row">
                                <div class="col-xs-9">
                                    <FIELDSET class="exa-fieldset">
                                        <LEGEND class="Titulos2">Cargar Busqueda del SRI</LEGEND>
                                        <form method="post" name="formBusquedaSri" id="formBusquedaSri" enctype="multipart/form-data" action="javascript:loadBusquedaSri();" class="form-horizontal normal">
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-sm required">Seleccione:</label>
                                                <div class="col-xs-10">
                                                    <div class="input-group input-group-sm">
                                                        <input type="file" name="archivoXML" value="" accept="text/txt" class="form-control input-sm" required />
                                                        <div class="input-group-btn">
                                                            <button type="button" class="btn btn-sm btn-primary start" onclick="$(this.form).formSubmit();" title="Cargar Archivo txt"><i class="glyphicon glyphicon-upload"></i> <span>Cargar</span> </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>

                                    </FIELDSET>
                                </div>

                            </div>
                            <div style="margin-bottom: 5px">
                                <table id="gridBusSri"></table>
                                <div id="gridBusSriPager"></div>
                                <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Contabilizados | <span class="glyphicon glyphicon-stop red"></span> Omitidos | <span class="glyphicon glyphicon-stop blue"></span> Pre Cargados | <span class="glyphicon glyphicon-stop white"></span> Pendientes </div>
                            </div>

                            <button id="cargardatos" type="button" class="btn btn-sm btn-primary start" onclick="preGuardarPro();" title="Pre Cargar"><i class="glyphicon glyphicon-check"></i> <span>PreCargar</span> </button>

                            <div class="form-group" id="process" style="display:none;">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="">
                                    </div>
                                </div>
                            </div>

                        </div>


                        <div id="tabs-2" class="ui-tabs-panel">
                            <div class="row">
                                <div class="col-xs-9">
                                    <FIELDSET class="exa-fieldset">
                                        <LEGEND class="Titulos2">Filtro PreCargados</LEGEND>
                                        <form id="serachDocDorm" name="serachDocDorm" class="form-horizontal normal">

                                            <div class="form-group">
                                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                                <div class="col-sm-5 radioset">
                                                    <input id="pre_rad_ba1" name="pre_op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" /><label for="pre_rad_ba1">Nro.Doc</label>
                                                    <input id="pre_rad_ba2" name="pre_op_opciones" type="radio" value="r" onclick="setfocus(this.form.search)" /><label for="pre_rad_ba2">R.U.C./Emisor</label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                                <div class="col-xs-7">
                                                    <div class="input-group">
                                                        <input name="searchPreCargados" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" autofocus class="form-control input-sm clearable submit" />
                                                        <span class="input-group-btn">
                                                            <button id="buscarValid" type="button" onclick="fetchData();" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1">
                                                                <span class="glyphicon glyphicon-search"></span>
                                                                <span>Buscar</span>
                                                            </button>
                                                        </span>
                                                        <span class="input-group-btn">
                                                            <button id="valid" style="margin-left: 10px;" type="button" onclick="validarPro();" class="btn btn-success btn-sm" title="Actualiza Cargados" tabindex="-1" disabled="true">
                                                                <span class="glyphicon glyphicon-ok"></span>
                                                                <span>Validar</span>
                                                            </button>
                                                        </span>
                                                    </div><!-- /input-group -->

                                                    <div class="input-group">
                                                    </div>
                                                </div><input type="text" tabindex="-1" style="display:none;" />
                                            </div>
                                        </form>

                                    </FIELDSET>
                                </div>

                            </div>
                            <div style="margin-bottom: 5px">
                                <table id="gridPreCar"></table>
                                <div id="gridPreCarPager"></div>
                            </div>
                        </div>


                        <div id="tabs-3" class="ui-tabs-panel">
                            <div class="row">
                                <div class="col-xs-9">
                                    <FIELDSET class="exa-fieldset">
                                        <LEGEND class="Titulos2">Filtro Cargas Exitosas</LEGEND>
                                        <form method="post" name="serachDocDormCarExi" id="serachDocDormCarExi" enctype="multipart/form-data" action="javascript:loadBusquedaSri();" class="form-horizontal normal">

                                            <div class="form-group">
                                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                                <div class="col-sm-5 radioset">
                                                    <input id="exi_rad_ba1" name="exi_op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" /><label for="exi_rad_ba1">Nro.Doc</label>
                                                    <input id="exi_rad_ba2" name="exi_op_opciones" type="radio" value="r" onclick="setfocus(this.form.search)" /><label for="exi_rad_ba2">R.U.C./Emisor</label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                                <div class="col-xs-7">
                                                    <div class="input-group">
                                                        <input name="searchExitosas" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" autofocus class="form-control input-sm clearable submit" />
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="fetchDataExi();" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1">
                                                                <span class="glyphicon glyphicon-search"></span>
                                                                <span>Buscar</span>
                                                            </button>
                                                        </span>

                                                    </div><!-- /input-group -->
                                                </div><input type="text" tabindex="-1" style="display:none;" />
                                            </div>

                                        </form>

                                    </FIELDSET>
                                </div>

                            </div>
                            <div style="margin-bottom: 5px">
                                <table id="gridCarExi"></table>
                                <div id="gridCarExiPager"></div>
                            </div>
                        </div>


                        <div id="tabs-4" class="ui-tabs-panel">
                            <div class="row">
                                <div class="col-xs-9">
                                    <FIELDSET class="exa-fieldset">
                                        <LEGEND class="Titulos2">Filtro Omitidos Carga</LEGEND>
                                        <form method="post" name="serachDocDormOmiCar" id="serachDocDormOmiCar" enctype="multipart/form-data" action="javascript:" class="form-horizontal normal">

                                            <div class="form-group">
                                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                                <div class="col-sm-5 radioset">
                                                    <input id="omi_rad_ba1" name="omi_op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" /><label for="omi_rad_ba1">Nro.Doc</label>
                                                    <input id="omi_rad_ba2" name="omi_op_opciones" type="radio" value="r" onclick="setfocus(this.form.search)" /><label for="omi_rad_ba2">R.U.C./Emisor</label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                                <div class="col-xs-7">
                                                    <div class="input-group">
                                                        <input name="searchOmitidas" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" autofocus class="form-control input-sm clearable submit" />
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="fetchDataOmi();" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1">
                                                                <span class="glyphicon glyphicon-search"></span>
                                                                <span>Buscar</span>
                                                            </button></span>
                                                    </div><!-- /input-group -->
                                                </div><input type="text" tabindex="-1" style="display:none;" />
                                            </div>
                                        </form>

                                    </FIELDSET>
                                </div>
                            </div>
                            <div style="margin-bottom: 5px">
                                <table id="gridOmiCar">
                                </table>
                                <div id="gridOmiCarPager"></div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script src="../../framework/plugins/jszip/jszip.min.js"></script>
    <script src="../../framework/plugins/FileSaver/FileSaver.min.js"></script>
</BODY>

</HTML>