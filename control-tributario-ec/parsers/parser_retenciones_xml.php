<?php
if (!function_exists('parsearRetencionXML')) {
    function parsearRetencionXML($xmlContent, &$resultados, &$analisis) {
        $xmlNode = @simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xmlNode) return;
        
        $compNode = null;
        
        if (isset($xmlNode->comprobante)) {
            if (isset($xmlNode->comprobante->comprobanteRetencion)) {
                $compNode = $xmlNode->comprobante->comprobanteRetencion;
            } else {
                $compStr = (string) $xmlNode->comprobante;
                if (trim($compStr) !== '') {
                    $compNode = @simplexml_load_string(trim($compStr), 'SimpleXMLElement', LIBXML_NOCDATA);
                }
            }
        } else {
            $compNode = $xmlNode;
        }
        
        if ($compNode && isset($compNode->infoCompRetencion)) {
            if (isset($compNode->infoCompRetencion->periodoFiscal)) {
                $periodo = (string)$compNode->infoCompRetencion->periodoFiscal;
            } elseif (isset($compNode->infoCompRetencion->fechaEmision)) {
                $fecha = (string)$compNode->infoCompRetencion->fechaEmision;
                $partesFecha = explode('/', $fecha);
                if (count($partesFecha) === 3) {
                    $periodo = $partesFecha[1] . '/' . $partesFecha[2];
                } else {
                    $periodo = $fecha;
                }
            } else {
                return;
            }
            
            $partesPeriodo = explode('/', $periodo);
            $mes = (int)$partesPeriodo[0];
            
            if (!isset($resultados[$mes])) $resultados[$mes] = array();
            if (!isset($analisis['docs_por_mes'][$mes])) $analisis['docs_por_mes'][$mes] = 0;
            
            $analisis['docs_por_mes'][$mes]++;
            $analisis['total_docs']++;
            
            $rucAgente = '';
            $razonAgente = '';
            if (isset($compNode->infoTributaria)) {
                $rucAgente = (string)$compNode->infoTributaria->ruc;
                $razonAgente = (string)$compNode->infoTributaria->razonSocial;
            }
            
            $docRetencionTotal = 0;
            
            // Buscar retenciones usando XPath para soportar múltiples versiones del SRI
            $nodosRetencion = $compNode->xpath('.//retencion | .//impuesto');
            if ($nodosRetencion) {
                foreach ($nodosRetencion as $ret) {
                    $codigo = (string)$ret->codigo;
                    $codRet = (string)$ret->codigoRetencion;
                    $porcentaje = (float)$ret->porcentajeRetener;
                    $valor = (float)$ret->valorRetenido;
                    $base = isset($ret->baseImponible) ? (float)$ret->baseImponible : 0;
                    
                    if ($codigo === '' || $codRet === '') continue;
                    
                    $tipo = 'OTRO';
                    $clave = "{$codRet} ({$porcentaje}%)";

                    if ($codigo == '1') {
                        $tipo = 'RENTA';
                        $clave = "Renta {$codRet} (" . floatval($porcentaje) . "%)";
                    } elseif ($codigo == '2') {
                        $tipo = 'IVA';
                        $clave = "IVA " . floatval($porcentaje) . "%";
                    } elseif ($codigo == '3' || $codigo == '6') {
                        $tipo = 'ISD';
                        $clave = "ISD " . floatval($porcentaje) . "%";
                    } else {
                        $tipo = 'OTRO';
                        $clave = "Otro {$codRet} (" . floatval($porcentaje) . "%)";
                    }
                    
                    if (!isset($resultados[$mes][$clave])) {
                        $resultados[$mes][$clave] = array(
                            'valorRetenido' => 0,
                            'tipo' => $tipo
                        );
                    }
                    $resultados[$mes][$clave]['valorRetenido'] += $valor;
                    
                    if (!isset($analisis['codigos'][$clave])) {
                        $analisis['codigos'][$clave] = array(
                            'codigo' => $clave,
                            'tipo' => $tipo,
                            'veces' => 0,
                            'base' => 0,
                            'retenido' => 0
                        );
                    }
                    $analisis['codigos'][$clave]['veces']++;
                    $analisis['codigos'][$clave]['base'] += $base;
                    $analisis['codigos'][$clave]['retenido'] += $valor;
                    
                    $docRetencionTotal += $valor;
                }
            }
            
            if ($rucAgente !== '') {
                if (!isset($analisis['agentes'][$rucAgente])) {
                    $analisis['agentes'][$rucAgente] = array(
                        'ruc' => $rucAgente,
                        'nombre' => $razonAgente,
                        'docs' => 0,
                        'total' => 0
                    );
                }
                $analisis['agentes'][$rucAgente]['docs']++;
                $analisis['agentes'][$rucAgente]['total'] += $docRetencionTotal;
            }
        }
    }
}
