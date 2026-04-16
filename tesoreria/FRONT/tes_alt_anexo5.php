<?php
/**
 * Descripci�n: Permite generar archivo XML del Anexo Tranaccional Simplificado 2013 previos requisitos del SRI
 * Fecha de actualizaci�n:    2010-03-02
 * Desarrollador:    Jose Cumbicos -  Ing. Angelica Galvez
 * Fecha de actualizaci�n:    2010-03-02
 * Desarrollador:    Lewis Chimarro
 * Desarrollador:    Jose Cumbicos     2014-11-25
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
// Incrementa la capacidad de espacio reservado en la memoria ram para este script
// ini_set('memory_limit', '128M');
// ini_set('max_execution_time', 600);
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 9600);
// ini_set('memory_limit', '8192M');
//ini_set('max_execution_time', 9600);
session_write_close();

function cleanEspecialChar(&$input) {
    if (is_string($input)) {
        $input = str_ireplace(array('&', '\'', '"', '<', '>', '~', '^', '�', '?'), array('y', '&apos;', '&quot;', '&lt;', '&gt;', '', '', '', ''), trim($input));
    } else if (is_array($input)) {
        foreach ($input as &$value) {
            cleanEspecialChar($value);
        }
        unset($value);
    } else if (is_object($input)) {
        $vars = array_keys(get_object_vars($input));
        foreach ($vars as $var) {
            cleanEspecialChar($input->$var);
        }
    }
}
// Creacion del Objeto de conexion
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
// Creacion del objeto mysql para las consultas
$obBD_con1 =  new Class_Log_Datos_Anx;

$valAuxCom = 0;
$valAuxVen = 0;
$valAuxExp = 0;
$valAuxAnu = 0;


if (isset($bt_save)) {
    if (isset($chk_fechas)&&!empty($chk_fechas)) {
        $ini = $Ats_Fec_Ini;
        $fin = $Ats_Fec_Fin;
        $anio = substr($fin, 0, 4);
        $mes = substr($fin, 5, 2);
    } else {
        $ini = $anio . '-' . $mes . '-' . '01';
        $fin = $anio . '-' . $mes . '-' . ultimoDia($mes, $anio);
    }

    // Esta consulta nos permite obtener la informacion de la Empresa(Ruc, Nombre, etc) para llenar el encabezado del XML
    $row_rs_identifica = $obBD_con1->getRowConsulta(226, $Ses_Emp_Cod, $obBD_conexion);
    cleanEspecialChar($row_rs_identifica);
    // Consulta el total de puntos de impresion - Esto calcula en funcion de las ventas realizadas
    $row_puntos = $obBD_con1->getArrayConsulta(226, $Ses_Emp_Cod, $obBD_conexion);

    //*******************************************************************************
    //         E N C A B E Z A D O   P R I N C I P A L   D E L    X M L
    //*******************************************************************************
    $row_tipCompr = $obBD_con1->getArrayConsulta(862, '01*04*', $obBD_conexion);
    // Consulta el total de ventas tipo Factura
    $row_ventas = $obBD_con1->getRowConsulta(391, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*' . $row_tipCompr[0]['Tic_Sri'], $obBD_conexion);
    // Consulta el total de ventas tipo Ventas por tipo Reembolso
    $row_ventasReembolso = $obBD_con1->getRowConsulta(391, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*41', $obBD_conexion);
    // Consulta el total de ventas tipo Notas de Credito
    $row_ventasNotCredito = $obBD_con1->getRowConsulta(391, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*4', $obBD_conexion);
    // Consultamos si la empresa genera comprobantes electronico
    $row_rs_configEmpresa = $obBD_con1->getRowConsulta(863, $Ses_Emp_Cod, $obBD_conexion);
    // Consultando las ventas por establecimiento (sucursal)
    $rs_establecimiento = $obBD_con1->getArrayConsulta('393_new', $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion);

    $sinVentas = '0';
    if ($row_ventas['Total'] == 0 && count($row_ventasReembolso)) {
        $tot_ventas = '00';
        $sinVentas = '1';
    } else {
        $tot_ventas = 0;
        foreach ($rs_establecimiento as $row_rs_establecimiento)
            $tot_ventas += $row_rs_establecimiento['Total'];
        $tot_ventas = formato_numero($tot_ventas, 2, 1);
    }
    $xml_identifica = '<TipoIDInformante>R</TipoIDInformante>' .
        '<IdInformante>' . $row_rs_identifica['Emp_Ruc'] . '</IdInformante>' .
        '<razonSocial>' . strtoupper($row_rs_identifica['Emp_Nom']) . '</razonSocial>' .
        '<Anio>' . utf8_encode($anio) . '</Anio>' .
        '<Mes>' . utf8_encode($mes) . '</Mes>';

    if (isset($chk_fechas)&&!empty($chk_fechas)) {
        $xml_identifica .= '<regimenMicroempresa>SI</regimenMicroempresa>';
    }

    $xml_identifica .= '<numEstabRuc>' . str_pad(count($rs_establecimiento), 3, '0', STR_PAD_LEFT) . '</numEstabRuc>' .
        '<totalVentas>' . formato_numero($tot_ventas, 2, 1) . '</totalVentas>' . //total ventas
        '<codigoOperativo>IVA</codigoOperativo>';
    // Fin de la Etiqueta del Encabezado

    //=========================================
    //    C O M P R A S   D E L    X M L
    //=========================================
    $xml_compras = '';
    if (isset($_POST['chk_Compras'])) {
        //----------------------------------------
        //    C O M P R A S   D E L    X M L
        //----------------------------------------
        $valAuxCom = 1;
        // Consulta los porcentaje de iva
        $row_rs_iva = $obBD_con1->getRowConsulta(876, $ini, $obBD_conexion);
        // Cargado de los datos de las C O M P R A S detallados del anexo (Cabecera)
        $rs_compras = $obBD_con1->getArrayConsulta(260, $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion); //$ini.'*'.$fin.
        $Cop_Cods = pluck($rs_compras, 'Cop_Cod', true);
        // Cargado de los datos detallados del anexo (Detalle)
        $rs_compras_det_group = keyBy($obBD_con1->getArrayConsulta('230_group', $Cop_Cods, $obBD_conexion), 'Cop_Cod');
        // Consulta de facturas que no tienen retencion codigo 332
        $row_rs_compras_sin_retenc = groupBy($obBD_con1->getArrayConsulta('855_group', $Cop_Cods, $obBD_conexion), 'Cop_Cod');
        // Consulta se posee reembolsos
        $row_rs_reemb_group = groupBy($obBD_con1->getArrayConsulta('890_group', $Cop_Cods, $obBD_conexion), 'Cop_Cod');
        // Cargado para saber si el monto iva es BIEN o SERVICIO
        $rs_bien_serv_group = groupBy($obBD_con1->getArrayConsulta('232_group', $Cop_Cods . '*' . $Ses_Emp_Cod, $obBD_conexion), 'Cop_Cod');
        // Cargado para obtener si el porcentaje de retencion del iva es 100%
        $row_rs_iva_total = keyBy($obBD_con1->getArrayConsulta('854_group', $Cop_Cods, $obBD_conexion), 'Cop_Cod');
        // Cargado de los datos detallados del anexo (ICE)
        $row_rs_compras_ice = keyBy($obBD_con1->getRowConsulta('231_group', $Cop_Cods, $obBD_conexion), 'Cop_Cod');
        // Consulta de los valores AIR (solo renta)
        $row_rs_datos_air_group = groupBy($obBD_con1->getArrayConsulta('233_group', $Cop_Cods . '*' . 'R', $obBD_conexion), 'Cop_Cod');
        // pago sri
        $rs_ForPagoComSri = keyBy($obBD_con1->getArrayConsulta('856_group', '', $obBD_conexion), 'Tpc_Cod');

        // Apertura del cuerpo
        $aux = 0;
        $xml_compras = array();
        \DebugBar::startMeasure('Compras_XML', 'Maquetado Compras XML');
        $retenciones_Sin_Autorizacion = $obBD_con1->getArrayConsulta('896', array('ini'=>$ini,'fin'=>$fin,'Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion);
        $arr_retencion = array();
        foreach ($retenciones_Sin_Autorizacion as $item) {
            $arr_retencion[] = $item['Ret_Num'];
        }
        $retenciones_Sin_Autorizacion = implode('<br>', $arr_retencion);
        foreach ($rs_compras as $row_rs_compras) {
            $xml_compras[] =  '<detalleCompras>';
            $autorizacion = $row_rs_compras['Cop_Aut'];
            $estab = $obBD_con1->establecimiento($row_rs_compras['Cop_Num']);
            $cont = 0;

            if(isset($row_rs_compras_sin_retenc[$row_rs_compras['Cop_Cod']]))
            foreach ($row_rs_compras_sin_retenc[$row_rs_compras['Cop_Cod']] as $item_rs_compras_sin_retenc) {
                if ($item_rs_compras_sin_retenc['Iva_Por'] != '' && $row_rs_compras['Tic_Sri'] != '41') {
                    $cont++;
                    $codRetAir[$cont] = !empty($item_rs_compras_sin_retenc['Ret_Ren_Sri']) ? $item_rs_compras_sin_retenc['Ret_Ren_Sri'] : '332';
                    $baseImpAir[$cont] = $item_rs_compras_sin_retenc['Sub0'] + $item_rs_compras_sin_retenc['Sub12'];
                    $porcentajeAir[$cont] = 0;
                    $valRetAir[$cont] = 0;
                }
            }
            // Inicio de variables            
            $rs_compras_det = $rs_compras_det_group[$row_rs_compras['Cop_Cod']];
            $nobIva = formato_numero($rs_compras_det['nobIva'], 2, 1);
            $baseImponible = formato_numero($rs_compras_det['Sub0'], 2, 1);
            $baseImpGrav = formato_numero($rs_compras_det['Sub12'], 2, 1);
            $montoIva = formato_numero($rs_compras_det['IvaTot'], 2, 1);

            $valorRetBienes10 = '0.00';
            $valorRetServicios20 = '0.00';
            $valorRetBienes = '0.00';
            $valorRetServicios = '0.00';
            $valRetServ50 = '0.00';
            $valorRetServicios100 = '0.00';

            $rs_bien_serv = isset($rs_bien_serv_group[$row_rs_compras['Cop_Cod']])?$rs_bien_serv_group[$row_rs_compras['Cop_Cod']]:array();
            $total_rs_bien_serv = count($rs_bien_serv);
            if ($total_rs_bien_serv == 0) {
                $valorRetBienes10 = '0.00';
                $valorRetServicios20 = '0.00';
                $valorRetBienes = '0.00';
                $valorRetServicios = '0.00';
                $valRetServ50 = '0.00';
            } else {
                foreach ($rs_bien_serv as $row_rs_bien_serv) {
                    // iva 10%        bienes                       activos fijos                      Gastos
                    if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 2 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '10') {
                        $valorRetBienes10 = $valorRetBienes10 + round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
                    }
                    // iva 20%       servicio                            Gastos/
                    if (($row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13) && $row_rs_bien_serv['Ren_Por'] == '20') {
                        $valorRetServicios20 = round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
                    }
                    // iva 30%          bienes                 activos fijos                          servicio                           Gastos
                    if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 2 || $row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '30') {
                        $valorRetBienes = $valorRetBienes + round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
                    }
                    // iva 50%          bienes                 activos fijos                          servicio                           Gastos
                    if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 2 || $row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '50') {
                        $valRetServ50 = $valRetServ50 + round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
                    }
                    // iva 70%          bienes                 activos fijos                          servicio                              Gastos                        suministros al parecer
                    if (($row_rs_bien_serv['Adq_Cod'] == 1 || $row_rs_bien_serv['Adq_Cod'] == 2 || $row_rs_bien_serv['Adq_Cod'] == 3 || $row_rs_bien_serv['Adq_Cod'] == 13 || $row_rs_bien_serv['Adq_Cod'] == 14) && $row_rs_bien_serv['Ren_Por'] == '70') {
                        $valorRetServicios = round(($row_rs_bien_serv['Ret_Bas'] * $row_rs_bien_serv['Ren_Por']) / 100, 2);
                    }
                }  //Fin del $row_rs_bien_serv
            }  //Fin del if($total_rs_bien_serv==0)

            $total_rs_iva_total = isset($row_rs_iva_total[$row_rs_compras['Cop_Cod']])?$row_rs_iva_total[$row_rs_compras['Cop_Cod']]:0;
            if (!empty($total_rs_iva_total)) {
                $valorRetServicios100 = round(($total_rs_iva_total['Ret_Bas'] * $total_rs_iva_total['Ren_Por']) / 100, 2);
            } else {
                $valorRetServicios100 = '0.00';
            }
            $aux++;

            $row_rs_reemb = isset($row_rs_reemb_group[$row_rs_compras['Cop_Cod']])?$row_rs_reemb_group[$row_rs_compras['Cop_Cod']]:array();
            $row_rs_datos_air = isset($row_rs_datos_air_group[$row_rs_compras['Cop_Cod']])?$row_rs_datos_air_group[$row_rs_compras['Cop_Cod']]:array();
            if($row_rs_compras['Cop_Cod']==347338) print_r($row_rs_datos_air);
            $total_rs_datos_air = count($row_rs_datos_air);
            if ($total_rs_datos_air == 0) {
                $CanCajas = 0;
                $ValCajas = 0;
            }

            if ($total_rs_datos_air > 0) {
                $Fecha_Reg = $row_rs_compras['Cop_Fec']; // Fecha de registro compra
                $CanCajas = $row_rs_datos_air[0]['Ret_Uca']; //Unidad de caja de banano para codigo 338
                $ValCajas = $row_rs_datos_air[0]['Ret_Pca']; //Precio de caja de banano para codigo 338
                $AutTem = $row_rs_datos_air[0]['Aut_Tem'];   // Tipo de Emision de la Autorizacion N=normal  E=electronica
                $FecRetEmi = date('d/m/Y', strtotime(str_replace('/', '-', $row_rs_datos_air[0]['Ret_Fec']))); // fecha de Retencion
                if ($row_rs_configEmpresa['Cof_Gce'] == 'N') {
                    $AutEmiRet = $row_rs_datos_air[0]['Aut_Sri'];
                } else {
                    if ($AutTem == 'N') {
                        $AutEmiRet = $row_rs_datos_air[0]['Aut_Sri'];
                    } else {
                        $AutEmiRet = $row_rs_datos_air[0]['Ret_Sri'];
                    }
                }
            } else {
                $Fecha_Reg = $row_rs_compras['Cop_Fec']; //Fecha de la factura
            } //Fin del else if ($total_rs_datos_air > 0)

            //CONDICIONALES
            $xml_condicional_tipoProv = '';
            $xml_condicional_denoProv = '';
            if ($row_rs_compras['Ide_Prc'] == '03') { //Solo cuando es pasaporte o documento del exterior
                $tipo_contribuyente =  $row_rs_compras['Prv_Tic'] == 'N' ? '01' :  '02'; //N = 01  - J=02
                $denominacion_social = !empty($row_rs_compras['Prv_Com']) ? $row_rs_compras['Prv_Com'] : $row_rs_compras['Prv_Tic'];
                $xml_condicional_tipoProv = '<tipoProv>' . $tipo_contribuyente . '</tipoProv>';
                $xml_condicional_denoProv = '<denoProv>' . $denominacion_social . '</denoProv>';
            }
            
            if(!empty($row_rs_compras['Ide_Prc']) or $row_rs_compras['Ide_Prc']!==''){
                if($row_rs_compras['Ide_Prc']==1)
                    $row_rs_compras['Prs_Ced']=strlen($row_rs_compras['Prs_Ced'])<13?$row_rs_compras['Prs_Ced'].'001':$row_rs_compras['Prs_Ced'];
                else
                    if($row_rs_compras['Ide_Prc']==2)
                        $row_rs_compras['Prs_Ced']=strlen($row_rs_compras['Prs_Ced'])==13?substr($row_rs_compras['Prs_Ced'],0, -3):$row_rs_compras['Prs_Ced'];                
            }
            $iceVal = isset($row_rs_compras_ice[$row_rs_compras['Cop_Cod']]['Mon_Ice']) ? $row_rs_compras_ice[$row_rs_compras['Cop_Cod']]['Mon_Ice'] : 0;
            $xml_compras[] = // ETIQUETAS COMPRAS DEL XML
                '<codSustento>' . $row_rs_compras['Tri_Sri'] . '</codSustento>' .
                '<tpIdProv>' . str_pad($row_rs_compras['Ide_Prc'], 2, '0', STR_PAD_LEFT). '</tpIdProv>' .
                '<idProv>' . $row_rs_compras['Prs_Ced'] . '</idProv>' .
                '<tipoComprobante>' . str_pad($row_rs_compras['Tic_Sri'], 2, '0', STR_PAD_LEFT) . '</tipoComprobante>' .
                $xml_condicional_tipoProv . //tipoPrv  ::condicional
                $xml_condicional_denoProv . //denoProv ::condicional
                '<parteRel>'.'NO'.'</parteRel>' .
                '<fechaRegistro>' . date('d/m/Y', strtotime($Fecha_Reg)) . '</fechaRegistro>' .
                '<establecimiento>' . $estab[0] . '</establecimiento>' .
                '<puntoEmision>' . $estab[1] . '</puntoEmision>' .
                '<secuencial>' . str_pad($estab[2], 9, '0', STR_PAD_LEFT) . '</secuencial>' .
                '<fechaEmision>' . date('d/m/Y', strtotime($row_rs_compras['Cop_Fec'])) . '</fechaEmision>' .
                '<autorizacion>' . $autorizacion . '</autorizacion>' .
                '<baseNoGraIva>' .  $nobIva . '</baseNoGraIva>' .
                '<baseImponible>' . $baseImponible . '</baseImponible>' .
                '<baseImpGrav>' . $baseImpGrav . '</baseImpGrav>' .
                '<baseImpExe>'.'0.00'.'</baseImpExe>' .
                '<montoIce>' . formato_numero($iceVal, 2, 1) . '</montoIce>' .
                '<montoIva>' . formato_numero($montoIva, 2, 1) . '</montoIva>' .
                '<valRetBien10>' . formato_numero($valorRetBienes10, 2, 1) . '</valRetBien10>' .   // iva 10%
                '<valRetServ20>' . formato_numero($valorRetServicios20, 2, 1) . '</valRetServ20>' .// iva 20%
                '<valorRetBienes>' . formato_numero($valorRetBienes, 2, 1) . '</valorRetBienes>';  // iva 30%
            if ($ini >= '2016-01') {
                $xml_compras[] =  '<valRetServ50>' . formato_numero($valRetServ50, 2, 1) . '</valRetServ50>'; // iva 50%
            }
            $xml_compras[] =  '<valorRetServicios>' . formato_numero($valorRetServicios, 2, 1) . '</valorRetServicios>' . // iva 70%
                '<valRetServ100>' . formato_numero($valorRetServicios100, 2, 1) . '</valRetServ100>' .                    // iva 100%
                '<totbasesImpReemb>' . formato_numero(isset($row_rs_reemb[0]['tot'])?$row_rs_reemb[0]['tot']:0, 2, 1) . '</totbasesImpReemb>' .
                '<pagoExterior>';

            $xml_compras[] =
                '<pagoLocExt>01</pagoLocExt>' .
                '<paisEfecPago>NA</paisEfecPago>' .
                '<aplicConvDobTrib>NA</aplicConvDobTrib>' .
                '<pagExtSujRetNorLeg>NA</pagExtSujRetNorLeg>' .
                '<pagoRegFis>NA</pagoRegFis>';
            $xml_compras[] =  '</pagoExterior>';

            if (($baseImponible + $baseImpGrav + $montoIva) >= 500 && $row_rs_compras['Tic_Sri'] != '4') {
                $xml_compras[] =  '<formasDePago>';
                $xml_compras[] =  '<formaPago>' . $rs_ForPagoComSri[$row_rs_compras['Tpc_Cod']]['Tpc_Sri'] . '</formaPago>';
                $xml_compras[] =  '</formasDePago>';
            }
            if ($row_rs_compras['Tic_Sri'] != '4') { //control para evitar las etiquetas d retencion a las notas de credito
                $xml_compras[] =  '<air>'; // <air>
                // Control para factura que sustentan credito tributario
                $flag_control338 = 0;
                if (/*$row_rs_compras['Tri_Sri'] != '00' &&*/$total_rs_datos_air > 0) {
                    foreach ($row_rs_datos_air as $row) {
                        if ($row['Ren_Sri'] == '338') {
                            if ($flag_control338 == '0') {
                                $cont++;
                                $rs_ret338 = $obBD_con1->getRowConsulta(881, $row['Ret_Cod'] . '*' . $row['Ren_Sri'], $obBD_conexion);
                                $codRetAir[$cont] = $rs_ret338['Ren_Sri'];
                                $baseImpAir[$cont] = $rs_ret338['Ret_Bas'];
                                $porcentajeAir[$cont] = $rs_ret338['Ren_Por'];
                                $valRetAir[$cont] = $rs_ret338['Val_Air'];
                                $flag_control338 = 1;
                            }
                        } else {
                            $cont++;
                            $codRetAir[$cont] = $row['Ren_Sri'];
                            if ($row['Ren_Sri'] == '322' && $row['Ren_Por'] * 1 == 0.1) {
                                $baseImpAir[$cont] = ($row['Ret_Bas'] * 10) / 100;
                                $porcentajeAir[$cont] = 1; //$row['Ren_Por'];
                            } else {
                                $baseImpAir[$cont] = $row['Ret_Bas'];
                                $porcentajeAir[$cont] = $row['Ren_Por'];
                            }
                            $valRetAir[$cont] = $row['Val_Air'];
                        }
                    } //Fin del $row_rs_datos_air
                }
                // Detalle air
                for ($i = 1; $i <= $cont; $i++) {
                    $xml_compras[] = '<detalleAir>' .
                        '<codRetAir>' . $codRetAir[$i] . '</codRetAir>' .
                        '<baseImpAir>' . formato_numero($baseImpAir[$i], 2, 1) . '</baseImpAir>' .
                        '<porcentajeAir>' . formato_numero($porcentajeAir[$i], 2, 1) . '</porcentajeAir>' .
                        '<valRetAir>' . formato_numero($valRetAir[$i], 2, 1) . '</valRetAir>';
                    if ($codRetAir[$i] == '338') { //solo para retenciones con codigo 338
                        $xml_compras[] = '<numCajBan>' . $CanCajas . '</numCajBan><precCajBan>' . formato_numero($ValCajas, 2, 1) . '</precCajBan>';
                    }
                    $xml_compras[] = '</detalleAir>';
                } // fin del for
                $xml_compras[] = '</air>'; //</air>
            }
            // Control para factura que sustentan credito tributario
            if ($row_rs_compras['Tri_Sri'] != '00' && $total_rs_datos_air > 0) {
                unset($estabRet);
                unset($ptoEmiRet);
                unset($secRet);
                unset($autRet);
                unset($fechaEmiRet);
                foreach ($row_rs_datos_air as $row) {
                    // Controla que haya valores en las fechas
                    $fecha = '';
                    if ($row['Ret_Fec'] != '') {
                        $fecha = date('d/m/Y', strtotime($row['Ret_Fec']));
                    } //Fin del if ($row_rs_datos_air['Ret_Fec'] != '')
                    // Asignacion del establecimiento
                    $estab = $obBD_con1->establecimiento($row['Ret_Num']);
                    $estabRet = $row['Suc_Sri'];
                    $ptoEmiRet = $row['Pun_Sri'];
                    $secRet[] = isset($estab[2]) ? $estab[2] : '0';
                } //Fin del $row_rs_datos_air
            } else { // Control para factura que NO sustentan credito tributario
                unset($estabRet);
                unset($ptoEmiRet);
                unset($secRet);
                unset($autRet);
                unset($fechaEmiRet);
                if (count($row_rs_datos_air) != 0)
                foreach ($row_rs_datos_air as $row) {
                    // asignacion del establecimiento
                    $estabRet[] = '000';
                    $ptoEmiRet[] = '000';
                    $secRet[] = '0';
                    $autRet[] = '000';
                    $fechaEmiRet[] = '00/00/0000';
                } //Fin del $row_rs_datos_air
            } //Fin del if ($row_rs_compras['Tri_Sri'] != 1)

            if (count($row_rs_datos_air) != 0) {
                if (floatval($secRet[0]) > 0) { //Si la secuencia es diferente de cero ingresa
                    $xml_compras[] =
                        '<estabRetencion1>' . $estabRet . '</estabRetencion1>' .
                        '<ptoEmiRetencion1>' . $ptoEmiRet . '</ptoEmiRetencion1>' .
                        '<secRetencion1>' . str_pad($secRet[0], 9, '0', STR_PAD_LEFT) . '</secRetencion1>';
                    
                        $xml_compras[] = '<autRetencion1>' . $AutEmiRet . '</autRetencion1>';
                    
                    $xml_compras[] = '<fechaEmiRet1>' . $FecRetEmi . '</fechaEmiRet1>';
                }
            }
            if ($row_rs_compras['Tic_Sri'] == '41') { // solo para Reembolsos
                //ChromePhp::log("tic_sri ".$row_rs_compras['Tic_Sri']);
                $xml_compras[] = '<reembolsos>';
                foreach ($row_rs_reemb as $row) {
                    $arrdato = explode('-', $row['Rem_Num']);
                    //echo date_format($row['Rem_Fec'], 'd/m/Y');
                    $xml_compras[] = '<reembolso>'
                        . '<tipoComprobanteReemb>' . $row['Rem_Tic'] . '</tipoComprobanteReemb>'
                        . '<tpIdProvReemb>' . $row['Rem_Ide'] . '</tpIdProvReemb>'
                        . '<idProvReemb>' . $row['Rem_Ced'] . '</idProvReemb>'
                        . '<establecimientoReemb>' . $arrdato[0] . '</establecimientoReemb>'
                        . '<puntoEmisionReemb>' . $arrdato[1] . '</puntoEmisionReemb>'
                        . '<secuencialReemb>' . $arrdato[2] . '</secuencialReemb>'
                        . '<fechaEmisionReemb>' . date('d/m/Y', strtotime($row['Rem_Fec'])) . '</fechaEmisionReemb>'
                        . '<autorizacionReemb>' . $row['Rem_Aut'] . '</autorizacionReemb>'
                        . '<baseImponibleReemb>' . $row['Rem_Niv'] . '</baseImponibleReemb>'
                        . '<baseImpGravReemb>' . $row['Rem_Siv'] . '</baseImpGravReemb>'
                        . '<baseNoGraIvaReemb>' . $row['Rem_Oiv'] . '</baseNoGraIvaReemb>'
                        . '<baseImpExeReemb>' . $row['Rem_Eiv'] . '</baseImpExeReemb>'
                        . '<montoIceRemb>' . $row['Rem_Ice'] . '</montoIceRemb>'
                        . '<montoIvaRemb>' . $row['Rem_Iva'] . '</montoIvaRemb>'
                        . '</reembolso>';
                }
                $xml_compras[] = '</reembolsos>';
            }
            if ($row_rs_compras['Tic_Sri'] == '4' || $row_rs_compras['Tic_Sri'] == '5') { // solo para notas de credito y notas de debito
                $tipDocMod = $row_rs_compras['Cop_Ntd'];
                $arrDatNum = explode('-', $row_rs_compras['Cop_Nns']);
                $xml_compras[] =  '<docModificado>' . str_pad($tipDocMod, 2, '0', STR_PAD_LEFT) . '</docModificado>' .
                    '<estabModificado>' . $arrDatNum[0] . '</estabModificado>' .
                    '<ptoEmiModificado>' . $arrDatNum[1] . '</ptoEmiModificado>' .
                    '<secModificado>' . str_pad($arrDatNum[2], 9, '0', STR_PAD_LEFT) . '</secModificado>' .
                    '<autModificado>' . trim($row_rs_compras['Cop_Nna']) . '</autModificado>';
            }
            $xml_compras[] = '</detalleCompras>';
        } //Fin de compras
        $xml_compras = '<compras>' . implode('', $xml_compras) . '</compras>';
        \DebugBar::stopMeasure('Compras_XML');
    } // FIN IF if(isset($chk_Compras))
    unset($rs_compras_det_group);
    unset($rs_compras);
    unset($row_rs_compras_sin_retenc);
    unset($row_rs_reemb_group);
    unset($rs_bien_serv_group);
    unset($row_rs_compras_ice);
    unset($row_rs_iva_total);
    unset($row_rs_datos_air_group);
    unset($rs_ForPagoComSri);


    /*========================================*/
    /*    V E N T A S    D E L     X M L      */
    /*========================================*/
    $xml_ventas = '';
    if (isset($_POST['chk_Ventas']) > 0 && $sinVentas == '0') {
        $valAuxVen = 1;
        // Consultando los CLIENTES q se emitio Ventas en un determinado MES para el Anexo Transaccional
        $rs_ventas = $obBD_con1->getArrayConsulta('387_new', 'A' . '*' . $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion);

        $CliCods = pluck($rs_ventas, 'Cli_Cod', true);
        // Suma de ventas por cliente
        $rs_detalle_group = keyBy($obBD_con1->getArrayConsulta('389_group', $ini . '*' . $fin . '*' . $CliCods, $obBD_conexion),'Aux_Key');
        // agrupado de retenciones venta por cliente
        $rs_retenci_group = keyBy($obBD_con1->getArrayConsulta('858_group_final', $ini . '*' . $fin . '*' . $CliCods, $obBD_conexion), 'Aux_Key');
        // tipos de pago por cliente
        $rs_TipoPagoVentas_group = groupBy($obBD_con1->getArrayConsulta('879_group', $ini . '*' . $fin . '*' . $Ses_Emp_Cod . '*' . $CliCods , $obBD_conexion), 'Aux_Key');

        // Cabecera de la venta
        $xml_ventas = array();
        \DebugBar::startMeasure('Ventas_XML', 'Maquetado Ventas XML');
        \DebugBar::startMeasure('Ventas_Normal_XML', 'Maquetado Ventas Normales XML');       
        $ventas_Sin_Autorizacion = $obBD_con1->getArrayConsulta('895', array('ini'=>$ini,'fin'=>$fin,'Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion);
        if (isset($ventas_Sin_Autorizacion)){
            $arr_sa = array();
            foreach ($ventas_Sin_Autorizacion as $item) {
                $arr_sa[] = $item['Vet_Num'];
            }
            $ventas_Sin_Autorizacion = implode('<br>', $arr_sa);
        }
       
        
        foreach ($rs_ventas as $row_rs_ventas) {
            cleanEspecialChar($row_rs_ventas);
            $cabDatos = $row_rs_ventas;
            $keyAux = $row_rs_ventas['Cli_Cod'] . '_' . $cabDatos['Aut_Tem'] . '_' . $cabDatos['TicSri'];
            $rs_detalle = $rs_detalle_group[$keyAux];
            $rs_retencion = $rs_retenci_group[$keyAux];
            // Variable q contendran las sumas de los facturas de ventas
            $valor_baseImpGrav = round($rs_detalle['BaseIva'], 2);
            $valor_montoIva = round($rs_detalle['Iva'], 2);
            $valor_noObjetoIva=round($rs_detalle['BaseNObjIva'],2);
            $valor_baseImponible = round($rs_detalle['BaseCero'], 2);
            $valorRenta = $rs_retencion['Tot_Imp'];
            $valorIva = $rs_retencion['Tot_Iva'];
            // Consultamos los diferentes tipos de pagos SRI
            $auxTpcSri = 0;
            $DatostipPagoVent = '';

            $CliCodTicCod = $row_rs_ventas['Cli_Cod'] . '_' . $cabDatos['Tic_Cod'];

            $rs_TipoPagoVentas = isset($rs_TipoPagoVentas_group[$CliCodTicCod]) ? $rs_TipoPagoVentas_group[$CliCodTicCod] : array();
            foreach ($rs_TipoPagoVentas as $rowTipPagVet) {
                if ($ini >= '2016-06-01' && $rowTipPagVet['Tpc_Sri'] == '01' && $auxTpcSri == 0) {
                    $DatostipPagoVent = $DatostipPagoVent . '<formaPago>' . $rowTipPagVet['Tpc_Sri'] . '</formaPago>';
                    $auxTpcSri = 1;
                } else {
                    if ($ini >= '2016-06-01' && $rowTipPagVet['Tpc_Sri'] == '' && $auxTpcSri == 0) {
                        $DatostipPagoVent = $DatostipPagoVent . '<formaPago>01</formaPago>';
                        $auxTpcSri = 1;
                    } elseif ($ini >= '2016-06-01' && $rowTipPagVet['Tpc_Sri'] != '01') {
                        $DatostipPagoVent = $DatostipPagoVent . '<formaPago>' . $rowTipPagVet['Tpc_Sri'] . '</formaPago>';
                    }
                }
            }

            unset($rs_DetalleVentas);
            unset($rs_TodasVentas);

            // control para poner tipo de comprobante en 18 si la venta es d tipo Factura
            if ($cabDatos['TicSri'] == '01') {
                $tipDocVen = '18';
            } else {
                $tipDocVen = $cabDatos['TicSri'];
            }

            // Control para asignar 9999999999, cuando la cedula sea cero
            if ($row_rs_ventas['Prs_Ced'] == '0') {
                $cedula = '9999999999999';
            } else {
                $cedula = $row_rs_ventas['Prs_Ced'];
            }

            // asignamos el tipo de identificacion del cliente
            if ($row_rs_ventas['Ide_Prv'] != '4' && $row_rs_ventas['Ide_Prv'] != '5' && $row_rs_ventas['Ide_Prv'] != '7') {
                $tipIdeCliente = '6';
                // Cuando el cliente es extranjero la cedula debe contener minimo 3 digitos con la fucion STR_PAD rellenamos con ceros
                $cedula = str_pad($cedula, 3, '0', STR_PAD_LEFT);
            } else {
                $tipIdeCliente = $row_rs_ventas['Ide_Prv'];
            }

            $xml_ventas[] = '<detalleVentas>';
            $xml_ventas[] = // ETIQUETAS VENTAS DEL XML
                '<tpIdCliente>' . str_pad($tipIdeCliente, 2, '0', STR_PAD_LEFT) . '</tpIdCliente>' .
                '<idCliente>' . $cedula . '</idCliente>';
            if ($tipIdeCliente != '7') { //esta etiqueta no debe ir a consumidor final
                $xml_ventas[] = '<parteRelVtas>NO</parteRelVtas>';
            }
            if ($tipIdeCliente == '6' && $ini >= '2016-05-01') { //etiquetas solo para clientes con pasaporte
                $xml_ventas[] = '<tipoCliente>' . $row_rs_ventas['CliTic'] . '</tipoCliente>';
                $xml_ventas[] = '<denoCli>' . trim(trim($row_rs_ventas['Prs_Ape']) . ' ' . trim($row_rs_ventas['Prs_Nom'])) . '</denoCli>';
            }
           
            $xml_ventas[] = '<tipoComprobante>' . str_pad($tipDocVen, 2, '0', STR_PAD_LEFT) . '</tipoComprobante>' .
                '<tipoEmision>' . ($cabDatos['Aut_Tem'] == 'E' ? 'E' : 'F') . '</tipoEmision>' .
                '<numeroComprobantes>' . $cabDatos['total'] . '</numeroComprobantes>' .
                '<baseNoGraIva>' . $valor_noObjetoIva . '</baseNoGraIva>' .
                '<baseImponible>' . formato_numero($valor_baseImponible, 2, 1) . '</baseImponible>' .
                '<baseImpGrav>' . formato_numero($valor_baseImpGrav, 2, 1) . '</baseImpGrav>' .
                '<montoIva>' . formato_numero($valor_montoIva, 2, 1) . '</montoIva>';

            if ($ini >= '2015-03-01') {
                $xml_ventas[] = '<montoIce>0.00</montoIce>';
            }   // montoICE
            $xml_ventas[] =
                '<valorRetIva>' . formato_numero($valorIva, 2, 1) . '</valorRetIva>' .
                '<valorRetRenta>' . formato_numero($valorRenta, 2, 1) . '</valorRetRenta>';
            if ($ini >= '2016-06-01' && $cabDatos['TicSri'] != '04') {
                $xml_ventas[] = '<formasDePago>' . $DatostipPagoVent . '</formasDePago>';
            }
            // Cierre del detalle de la venta
            $xml_ventas[] = '</detalleVentas>';
        } //Fin del $row_rs_ventas
        \DebugBar::stopMeasure('Ventas_Normal_XML');

        // RETENCIONES BANCARIAS EN VENTAS
        $rs_retenBancarias = $obBD_con1->getArrayConsulta(886, $Ses_Emp_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
        $rs_retenBancPagos = groupBy($obBD_con1->getArrayConsulta('887_group', $Ses_Emp_Cod . '*' . $ini . '*' . $fin . '*' . pluck($rs_retenBancarias, 'Cli_Cod', true), $obBD_conexion), 'Cli_Cod');
        foreach ($rs_retenBancarias as $row_retDatos) {
            //$rs_retenBancPagos = $obBD_con1->getArrayConsulta(887, $Ses_Emp_Cod . '*' . $ini . '*' . $fin . '*' . $row_retDatos['Cli_Cod'], $obBD_conexion);
            $xml_ventas[] = '<detalleVentas>' .
                '<tpIdCliente>' . $row_retDatos['Ide_Prv'] . '</tpIdCliente>' .
                '<idCliente>' . $row_retDatos['Prs_Ced'] . '</idCliente>' .
                '<parteRelVtas>NO</parteRelVtas>' .
                '<tipoComprobante>18</tipoComprobante>' .
                '<tipoEmision>' . $row_retDatos['Rvt_Tem'] . '</tipoEmision>' .
                '<numeroComprobantes>' . $row_retDatos['regtot'] . '</numeroComprobantes>' .
                '<baseNoGraIva>0.00</baseNoGraIva>' .
                '<baseImponible>0.00</baseImponible>' .
                '<baseImpGrav>0.00</baseImpGrav>' .
                '<montoIva>0.00</montoIva>' .
                '<montoIce>0.00</montoIce>' .
                '<valorRetIva>' . formato_numero($row_retDatos['ivaTotal'], 2, 1) . '</valorRetIva>' .
                '<valorRetRenta>' . formato_numero($row_retDatos['renTotal'], 2, 1) . '</valorRetRenta>' .
                '<formasDePago>';
            foreach ($rs_retenBancPagos[$row_retDatos['Cli_Cod']] as $row_retDatosPag) {
                $xml_ventas[] = '<formaPago>' . $row_retDatosPag['Tpc_Sri'] . '</formaPago>';
            }
            $xml_ventas[] = '</formasDePago></detalleVentas>';
        }
        \DebugBar::stopMeasure('Ventas_XML');
        unset($rs_retenBancPagos);
        unset($rs_detalle_group);
        unset($rs_retenci_group);
        unset($rs_retenBancarias);
        unset($rs_ventas);
        unset($row_rs_cabecera_group);
        unset($rs_TipoPagoVentas_group);
        // Cierre de la cabecera de la venta
        $xml_ventas =  '<ventas>' . implode('', $xml_ventas) . '</ventas>';
        //=========================================================================
        //    V E N T A S    E S T A B L E C I M I E N T O    D E L     X M L
        //=========================================================================
        $xml_ventasEst = (isset($xml_ventasEst)?$xml_ventasEst:'') . '<ventasEstablecimiento>';
        foreach ($rs_establecimiento as $row_rs_establecimiento) {
            $xml_ventasEst = $xml_ventasEst . '<ventaEst>';
            $xml_ventasEst = $xml_ventasEst .
                '<codEstab>' . $row_rs_establecimiento['Suc_Sri'] . '</codEstab>' .       // codEstab
                '<ventasEstab>' . formato_numero($row_rs_establecimiento['Total'], 2, 1)/*formato_numero($tot_ventas,2,1)*/ . '</ventasEstab>';         // ventasEstab
            $xml_ventasEst = $xml_ventasEst . '</ventaEst>'; // Cierre del detalle de la venta
        } //Fin del $row_rs_ventas
        $xml_ventasEst = $xml_ventasEst . '</ventasEstablecimiento>'; // Cierre de la cabecera de la venta
    } //FIN IF if(isset($chk_Ventas))
    unset($rs_establecimiento);

    //==================================================
    //   E X P O R T A C I O N E S   D E L    X M L
    //==================================================
    // Consultamos todas las Exportaciones dentro del MES
    $rs_exportacion = $obBD_con1->getArrayConsulta(885, $Ses_Emp_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
    $xmlExportacion = '';
    if (isset($_POST['chk_Export']) && isset($rs_exportacion[0]['Vet_Cod']) && $rs_exportacion[0]['Vet_Cod'] <> 0) {
        $valAuxExp = 1;
        $xmlExportacion = array();
        foreach ($rs_exportacion as $datos) {
            $xmlExportacion[] = '<detalleExportaciones>' .
                '<tpIdClienteEx>' . $datos['Ide_Pre'] . '</tpIdClienteEx>' .
                '<idClienteEx>' . $datos['Prs_Ced'] . '</idClienteEx>' .
                '<parteRelExp>' . $datos['EveRel'] . '</parteRelExp>';
            if ($datos['Ide_Pre'] == 21) {
                $xmlExportacion[] = '<tipoCli>' . $datos['Cli_Tic'] . '</tipoCli><denoExpCli>' . $datos['Prs_Ape'] . ' ' . $datos['Prs_Nom'] . '</denoExpCli>';
            }
            $xmlExportacion[] = '<tipoRegi>' . $datos['Reg_Cod'] . '</tipoRegi>';
            if ($datos['Reg_Cod'] == '01') {
                $xmlExportacion[] = '<paisEfecPagoGen>' . $datos['Pas_Sri'] . '</paisEfecPagoGen>';
            } else {
                if ($datos['Reg_Cod'] == '02') {
                    $xmlExportacion[] = '<paisEfecPagoParFis>' . $datos['Paf_Sri'] . '</paisEfecPagoParFis>';
                } else {
                    $xmlExportacion[] = '<denopagoRegFis>' . $datos['RegDen'] . '</denopagoRegFis>';
                }
            }
            $ingextgravotropas = ($datos['Eve_Ren'] > 0) ? 'SI' : 'NO';
            $xmlExportacion[] = '<paisEfecExp>' . $datos['Pas_Sri'] . '</paisEfecExp>' .
                '<exportacionDe>' . $datos['Ref_Cod'] . '</exportacionDe>';
            if ($datos['Ref_Cod'] == '03') {
                    $xmlExportacion[] ='<tipIngExt>' . $datos['Ein_Sri'] . '</tipIngExt>' .
                    '<ingExtGravOtroPais>' . $ingextgravotropas . '</ingExtGravOtroPais>' ;
            }        
                    $xmlExportacion[] ='<tipoComprobante>' . $datos['Tic_Sri'] . '</tipoComprobante>';
            
            if ($datos['Ref_Cod'] == '01') {
                $xmlExportacion[] = '<distAduanero>' . $datos['Edi_Sri'] . '</distAduanero>' .
                    '<anio>' . $datos['Eve_Ano'] . '</anio>' .
                    '<regimen>' . $datos['Ere_Sri'] . '</regimen>' .
                    '<correlativo>' . $datos['Eve_Cor'] . '</correlativo>' .
                    '<docTransp>' . $datos['Eve_Dot'] . '</docTransp>';
            }
            $xmlExportacion[] = '<fechaEmbarque>' . $datos['Eve_Fec'] . '</fechaEmbarque>' .
                '<valorFOB>' . $datos['Eve_Fob'] . '</valorFOB>' .
                '<valorFOBComprobante>' . $datos['total'] . '</valorFOBComprobante>' .
                '<establecimiento>' . $datos['Suc_Sri'] . '</establecimiento>' .
                '<puntoEmision>' . $datos['Pun_Sri'] . '</puntoEmision>' .
                '<secuencial>' . $datos['Vet_Num'] . '</secuencial>' .
                '<autorizacion>' . $datos['Aut_Num'] . '</autorizacion>' .
                '<fechaEmision>' . $datos['Caj_Fec'] . '</fechaEmision>';
            $xmlExportacion[] = '</detalleExportaciones>';
        }
        $xmlExportacion = '<exportaciones>' . implode('', $xmlExportacion) . '</exportaciones>';
    }
    unset($rs_exportacion);

    //========================================
    //   A N U L A D O S   D E L    X M L
    //========================================
    $xml_anulados = '';
    if (isset($_POST['chk_Anulados'])) {
        $valAuxAnu = 1;
        //***********************************
        //    A N U L A D O S    V E N T A S
        //***********************************
        // Consultando los CLIENTES q se anularon una factura de Ventas en un determinado MES para el Anexo Transaccional
        $rs_anulados = $obBD_con1->getArrayConsulta(390, $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion);

        $xml_anulados = array();
        foreach ($rs_anulados as $row_rs_anulados) {
            $xml_anulados[] = '<detalleAnulados>';
            $xml_anulados[] = // ETIQUETAS ANULADOS DEL XML
                '<tipoComprobante>' . '18' . '</tipoComprobante>' .
                '<establecimiento>' . $row_rs_anulados['Suc_Sri'] . '</establecimiento>' .
                '<puntoEmision>' . $row_rs_anulados['Pun_Sri'] . '</puntoEmision>' .
                '<secuencialInicio>' . str_pad($row_rs_anulados['Vet_Num'], 9, '0', STR_PAD_LEFT) . '</secuencialInicio>' .
                '<secuencialFin>' . str_pad($row_rs_anulados['Vet_Num'], 9, '0', STR_PAD_LEFT) . '</secuencialFin>' .
                '<autorizacion>' . $row_rs_anulados['Aut_Sri'] . '</autorizacion>';
            $xml_anulados[] = '</detalleAnulados>';
        }
        unset($rs_anulados);

        //****************************************************
        //    A N U L A D O S     R E T E N C I O N E S
        //****************************************************

        // Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)
        $rs_anulados = $obBD_con1->getArrayConsulta(237, $ini . '*' . $fin . '*' . 'I' . '*' . $Ses_Emp_Cod, $obBD_conexion);
        if (count($rs_anulados) > 0) {
            foreach ($rs_anulados as $row_rs_anulados) {
                if (intval($row_rs_anulados['Ret_Num']) > 0) { //si numero de retencion es menor es cero o menor no ingresa
                    $xml_anulados[] = '<detalleAnulados>';
                    $xml_anulados[] = // ETIQUETAS ANULADOS DEL XML
                        '<tipoComprobante>' . str_pad($row_rs_anulados['Tic_Sri'], 2, '0', STR_PAD_LEFT) . '</tipoComprobante>' .
                        '<establecimiento>' . $row_rs_anulados['Suc_Sri'] . '</establecimiento>' .
                        '<puntoEmision>' . $row_rs_anulados['Pun_Sri'] . '</puntoEmision>' .
                        '<secuencialInicio>' . $row_rs_anulados['Ret_Num'] . '</secuencialInicio>' .
                        '<secuencialFin>' . $row_rs_anulados['Ret_Num'] . '</secuencialFin>' .
                        '<autorizacion>' . $row_rs_anulados['Aut_Sri'] . '</autorizacion>';
                    $xml_anulados[] = '</detalleAnulados>';
                }
            } //Fin del $row_rs_anulados
        } //Fin del if ($total_rs_anulados > 0) de retenciones
        unset($rs_anulados);

        //***************************************************************************
        //   A N U L A D O S    L I Q U I D A C I O N E S    D E    C O M P R A
        //***************************************************************************
        // Cargado de los datos de las VENTAS ANULADAS detallados del anexo (Cabecera)
        $rs_anulados = $obBD_con1->getArrayConsulta(238, $ini . '*' . $fin . '*' . 'I' . '*' . '8' . '*' . $Ses_Emp_Cod, $obBD_conexion);
        if (count($rs_anulados) > 0) {
            foreach ($rs_anulados as $row_rs_anulados) { //Inicio del $row_rs_anulados
                $estab = explode('-', $row_rs_anulados['Cop_Num']);
                $xml_anulados[] = '<detalleAnulados>';
                $xml_anulados[] = // ETIQUETAS ANULADOS DEL XML
                    '<tipoComprobante>' . $row_rs_anulados['Tic_Sri'] . '</tipoComprobante>' .
                    '<establecimiento>' . $estab[0] . '</establecimiento>' .
                    '<puntoEmision>' . $estab[1] . '</puntoEmision>' .
                    '<secuencialInicio>' . $estab[2] . '</secuencialInicio>' .
                    '<secuencialFin>' . $estab[2] . '</secuencialFin>' .
                    '<autorizacion>' . $row_rs_anulados['Cop_Aut'] . '</autorizacion>';
                $xml_anulados[] = '</detalleAnulados>';
            } //FIn del $row_rs_anulados
        } //Fin del if ($total_rs_anulados > 0) de Liquidaciones de compra
        unset($rs_anulados);

        // Cierre de la cabecera de la anulados </anulados>
        $xml_anulados = '<anulados>' . implode('', $xml_anulados) . '</anulados>';
    } //FIN IF if(isset($chk_Anulados))
}
?>
<HTML>
<HEAD>
    <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?php echo "ATS [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/estilos.php") ?>
    <style>
    /* ATS - Estilos modernos y paleta profesional */
    :root {
        --ats-primary: #1e3a5f;
        --ats-primary-light: #2c5282;
        --ats-accent: #3182ce;
        --ats-accent-hover: #2b6cb0;
        --ats-bg: #f7fafc;
        --ats-card-bg: #ffffff;
        --ats-border: #e2e8f0;
        --ats-text: #2d3748;
        --ats-text-muted: #718096;
        --ats-success: #276749;
        --ats-shadow: 0 1px 3px rgba(30, 58, 95, 0.08);
        --ats-shadow-lg: 0 10px 25px -5px rgba(30, 58, 95, 0.12);
        --ats-radius: 8px;
        --ats-radius-lg: 12px;
    }
    #set1 {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #f0f4f8 0%, #e8eef4 100%);
        min-height: 100vh;
        padding: 1.5rem;
        color: var(--ats-text);
    }
    #set1 .ats-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background: var(--ats-card-bg);
        border-radius: var(--ats-radius-lg);
        box-shadow: var(--ats-shadow-lg);
        overflow: hidden;
    }
    #set1 .table {
        border: none !important;
        margin: 0 !important;
        background: transparent !important;
    }
    #set1 .table tr.BarraTitulo td {
        background: linear-gradient(135deg, var(--ats-primary) 0%, var(--ats-primary-light) 100%) !important;
        color: #fff !important;
        font-weight: 600;
        font-size: 1rem;
        padding: 0.6rem 1rem !important;
        border: none;
        letter-spacing: 0.02em;
        height: auto !important;
    }
    #set1 .BarraTitulo-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    #set1 .BarraTitulo-left {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #set1 .BarraTitulo-right {
        font-size: 0.8rem;
        font-weight: 500;
        opacity: 0.9;
        text-align: right;
        white-space: nowrap;
    }
    #set1 .table > tbody > tr:not(.BarraTitulo) > td {
        padding: 1.25rem !important;
        vertical-align: top !important;
        background: var(--ats-card-bg) !important;
        border: none !important;
    }
    #set1 FIELDSET {
        border: 1px solid var(--ats-border);
        border-radius: var(--ats-radius);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
        background: var(--ats-bg);
        box-shadow: var(--ats-shadow);
    }
    #set1 FIELDSET LEGEND {
        background: transparent;
        padding: 0 0.5rem;
        margin-left: 0.25rem;
        font-weight: 600;
        color: var(--ats-primary);
        font-size: 0.95rem;
    }
    #set1 .Titulos2, #set1 LEGEND .Titulos2 {
        color: var(--ats-primary) !important;
        font-weight: 600 !important;
    }
    #set1 .Etiqueta1 {
        color: var(--ats-text) !important;
        font-weight: 500;
        padding: 0.35rem 0 !important;
    }
    #set1 .Asterisco {
        color: #c53030;
    }
    #set1 select, #set1 input[type="date"], #set1 input[type="text"] {
        border: 1px solid var(--ats-border);
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        min-width: 140px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    #set1 select:focus, #set1 input:focus {
        outline: none;
        border-color: var(--ats-accent);
        box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.2);
    }
    #set1 select:disabled, #set1 input:disabled {
        background: #edf2f7;
        color: var(--ats-text-muted);
        cursor: not-allowed;
    }
    #set1 input[type="checkbox"] {
        width: 1.1rem;
        height: 1.1rem;
        accent-color: var(--ats-accent);
        cursor: pointer;
    }
    #set1 .btn-primary, #set1 button.btn-primary {
        background: linear-gradient(135deg, var(--ats-accent) 0%, var(--ats-accent-hover) 100%) !important;
        border: none !important;
        border-radius: 8px;
        padding: 0.6rem 1.25rem;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(49, 130, 206, 0.3);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    #set1 .btn-primary:hover, #set1 button.btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(49, 130, 206, 0.35);
    }
    #set1 .LetraNegra, #set1 .LetraNegra td {
        color: var(--ats-text) !important;
    }
    #set1 a[href*=".xml"] img, #set1 a[href*="tes_pri_ats"] img {
        vertical-align: middle;
        margin-left: 0.25rem;
    }
    /* Rubros a Generar: lista vertical de tarjetas con franja superior y badge de alerta */
    #set1 .ats-rubros {
        margin-top: 0.35rem;
    }
    #set1 .ats-rubros--grid {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        padding: 0;
        background: transparent;
        border: none;
    }
    #set1 .ats-rubro-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 2.25rem 0.75rem 0.85rem;
        background: #fff;
        border: 1px solid var(--ats-border);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
        transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
        cursor: pointer;
        margin-left: 0;
        overflow: hidden;
    }
    #set1 .ats-rubro-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 5px;
        border-radius: 5px 0 0 5px;
    }
    /* Franja vertical izquierda */
    #set1 .ats-rubro-item.ats-rubro-compras::before { background: linear-gradient(180deg, #3182ce, #63b3ed); }
    #set1 .ats-rubro-item.ats-rubro-ventas::before { background: linear-gradient(180deg, #276749, #48bb78); }
    #set1 .ats-rubro-item.ats-rubro-export::before { background: linear-gradient(180deg, #c05621, #ed8936); }
    #set1 .ats-rubro-item.ats-rubro-anulados::before { background: linear-gradient(180deg, #c53030, #fc8181); }
    #set1 .ats-rubro-item:hover {
        background: #f8fafc;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.1);
        transform: translateY(-1px);
    }
    #set1 .ats-rubro-item:has(input:checked) {
        background: #f7fafc;
        box-shadow: 0 0 0 2px rgba(49, 130, 206, 0.2);
    }
    #set1 .ats-rubro-item input[type="checkbox"] {
        margin: 0;
        flex-shrink: 0;
        width: 0.85rem;
        height: 0.85rem;
        accent-color: var(--ats-accent);
    }
    #set1 .ats-rubro-item .ats-rubro-icon {
        width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        flex-shrink: 0;
        vertical-align: middle;
    }
    #set1 .ats-rubro-item .ats-rubro-icon.ats-ico-compras { background-color: rgba(49, 130, 206, 0.12); }
    #set1 .ats-rubro-item .ats-rubro-icon.ats-ico-ventas { background-color: rgba(56, 161, 105, 0.12); }
    #set1 .ats-rubro-item .ats-rubro-icon.ats-ico-export { background-color: rgba(237, 137, 54, 0.12); }
    #set1 .ats-rubro-item .ats-rubro-icon.ats-ico-anulados { background-color: rgba(229, 62, 62, 0.1); }
    #set1 .ats-rubro-item .ats-rubro-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--ats-text);
        flex: 1 1 auto;
        min-width: 0;
        line-height: 1.3;
    }
    #set1 .ats-rubro-item .ats-rubro-notif {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
        border-radius: 50%;
        background-color: rgba(49, 130, 206, 0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        vertical-align: middle;
    }
    #set1 .ats-rubro-item .ats-rubro-notif[title] {
        cursor: help;
    }
    /* Badge de notificación (retenciones / ventas sin autorizar) */
    #set1 .ats-rubro-alert {
        position: absolute;
        top: 0.45rem;
        right: 0.45rem;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(145deg, #f56565 0%, #dd6b20 100%);
        color: #fff !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        line-height: 1;
        box-shadow: 0 2px 8px rgba(221, 107, 32, 0.45);
        z-index: 1;
        animation: ats-rubro-alert-pulse 2.2s ease-in-out infinite;
    }
    #set1 .ats-rubro-alert[title] {
        cursor: help;
    }
    /* Ventas: triángulo de advertencia más visible (24px) */
    #set1 .ats-rubro-alert.ats-rubro-alert--ventas {
        width: 24px;
        height: 24px;
        border-radius: 8px;
    }
    #set1 .ats-rubro-alert.ats-rubro-alert--ventas .fa-exclamation-triangle {
        font-size: 24px;
        line-height: 1;
        vertical-align: middle;
    }
    @keyframes ats-rubro-alert-pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 2px 8px rgba(221, 107, 32, 0.45); }
        50% { transform: scale(1.06); box-shadow: 0 3px 12px rgba(245, 101, 101, 0.55); }
    }
    #set1 .ats-actions {
        margin-top: 0.5rem;
    }
    #set1 .ats-main-layout {
        display: flex;
        gap: 1.5rem;
        align-items: flex-start;
    }
    #set1 .ats-main-left {
        flex: 1 1 40%;
        min-width: 0;
    }
    #set1 .ats-main-right {
        flex: 1 1 60%;
        min-width: 0;
        height: 70vh;
        border-left: 1px solid var(--ats-border);
        padding-left: 1rem;
    }
    #set1 .ats-main-right iframe {
        width: 100%;
        height: 100%;
        border: none;
        border-radius: var(--ats-radius);
        box-shadow: var(--ats-shadow);
        background: #fff;
    }
    /* Loader interno SOLO del iframe */
    #set1 .ats-iframe-wrap {
        position: relative;
        width: 100%;
        height: 100%;
    }
    #set1 .ats-iframe-loader {
        display: none;
        position: absolute;
        inset: 0;
        z-index: 10;
        background: rgba(255, 255, 255, 0.75);
        border-radius: var(--ats-radius);
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
    }
    #set1 .ats-iframe-loader.ats-iframe-loader-visible {
        display: flex !important;
    }
    #set1 .ats-iframe-loader .ats-iframe-loader-spinner {
        width: 56px;
        height: 56px;
        border: 4px solid rgba(49, 130, 206, 0.25);
        border-top-color: var(--ats-accent);
        border-radius: 50%;
        animation: ats-spin 0.9s linear infinite;
    }
    #set1 .ats-iframe-loader .ats-iframe-loader-text {
        margin-top: 1rem;
        color: var(--ats-text);
        font-weight: 600;
        font-size: 1.05rem;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    #set1 .ats-download-list {
        line-height: 1.5;
        color: var(--ats-text);
    }
    #set1 .ats-download-btns {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }
    #set1 .ats-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.9rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none !important;
        transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        white-space: nowrap;
    }
    #set1 .ats-download-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    /* Iconos: usa .icon-* del bootstrap (sprite); solo ajuste de alineación */
    #set1 .ats-download-btn .ats-download-icon {
        flex-shrink: 0;
        vertical-align: middle;
    }
    #set1 .ats-download-btn .ats-download-meta {
        font-weight: 500;
        font-size: 0.75rem;
        opacity: 0.9;
    }
    #set1 .ats-download-btn-xml {
        background: linear-gradient(135deg, var(--ats-primary) 0%, var(--ats-primary-light) 100%);
        color: #fff !important;
    }
    #set1 .ats-download-btn-xml:hover {
        background: linear-gradient(135deg, var(--ats-primary-light) 0%, #3a6ba8 100%);
        color: #fff !important;
    }
    #set1 .ats-download-btn-resumen {
        background: linear-gradient(135deg, var(--ats-success) 0%, #2f855a 100%);
        color: #fff !important;
    }
    #set1 .ats-download-btn-resumen:hover {
        background: linear-gradient(135deg, #2f855a 0%, #276749 100%);
        color: #fff !important;
    }
    #set1 .ats-download-btn-contable {
        background: linear-gradient(135deg, #6b46c1 0%, #553c9a 100%);
        color: #fff !important;
    }
    #set1 .ats-download-btn-contable:hover {
        background: linear-gradient(135deg, #553c9a 0%, #44337a 100%);
        color: #fff !important;
    }
    #set1 .ats-download-list a:not(.ats-download-btn) {
        color: var(--ats-accent);
        text-decoration: none;
        font-weight: 500;
    }
    #set1 .ats-download-list a:not(.ats-download-btn):hover {
        text-decoration: underline;
    }
    /* Loader durante generación del Anexo */
    #ats-loader {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        background: rgba(30, 58, 95, 0.75);
        align-items: center;
        justify-content: center;
        flex-direction: column;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }
    #ats-loader.ats-loader-visible {
        display: flex !important;
    }
    #ats-loader .ats-loader-spinner {
        width: 56px;
        height: 56px;
        border: 4px solid rgba(255, 255, 255, 0.25);
        border-top-color: #fff;
        border-radius: 50%;
        animation: ats-spin 0.9s linear infinite;
    }
    #ats-loader .ats-loader-text {
        color: #fff;
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 1.25rem;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    #ats-loader .ats-loader-sub {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        margin-top: 0.35rem;
    }
    @keyframes ats-spin {
        to { transform: rotate(360deg); }
    }
    </style>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <!--Librerias para interfaz -->
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
    <script type="text/javascript">
    $(() => {$('#set1 *').tooltip({showURL:false});});
    </script>
    <meta http-equiv="Content-Type" content="text/html;">
</HEAD>
<BODY>
<div id="ats-loader" aria-hidden="true">
    <div class="ats-loader-spinner"></div>
    <div class="ats-loader-text">Generando Anexo Transaccional</div>
    <div class="ats-loader-sub">Espere un momento...</div>
</div>
<div id="set1">
<div class="ats-container">
<table width="100%" border='0' cellpadding='0' cellspacing='0' class="table">
<tr class="BarraTitulo">
        <td>
            <div class="BarraTitulo-inner">
                <div class="BarraTitulo-left">&raquo; ANEXO TRANSACCIONAL SIMPLIFICADO </div>
                <!-- <div class="BarraTitulo-right">tes_pri_ats_resumen_2.0.php</div> -->
            </div>
        </td>
    </tr>
    <tr>
        <td align="left" valign="top">
        <div class="ats-main-layout">
            <div class="ats-main-left">
            <form action='<?php echo $_SERVER['PHP_SELF'] ?>' method="post" name="form1" id="form1">
                <?php echo mensaje_requerido(); ?>
                <FIELDSET>
                    <LEGEND><label class="Titulos2">Generar archivo Xml:</label></LEGEND>
                    <table width="100%" border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td width="20%" class="Etiqueta1"><span class="Asterisco">* </span>A&ntilde;o:&nbsp; </td>
                            <td width="30%">
                                <?php $rs_periodo = $obBD_con1->getArrayConsulta(860, $Ses_Emp_Cod, $obBD_conexion); ?>
                                <select name="anio" id="anio">
                                    <?php foreach ($rs_periodo as $dato) { ?>
                                        <option <?php if ((isset($anio) && (string)$dato['Pec_Fei'] == (string)$anio) || (!isset($anio) && $dato['Pec_Fei'] == date('Y'))) echo "selected"; ?> value='<?php echo $dato['Pec_Fei']; ?>'><?php echo $dato['Pec_Fei']; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td width="20%" class="Etiqueta1"><span class="Asterisco">* </span>Mes:&nbsp;</td>
                            <td width="30%">
                                <select name="mes" id="mes">
                                    <option <?php if (isset($mes) && $mes == "01") echo "selected"; ?> value="01">Enero</option>
                                    <option <?php if (isset($mes) && $mes == "02") echo "selected"; ?> value="02">Febrero</option>
                                    <option <?php if (isset($mes) && $mes == "03") echo "selected"; ?> value="03">Marzo</option>
                                    <option <?php if (isset($mes) && $mes == "04") echo "selected"; ?> value="04">Abril</option>
                                    <option <?php if (isset($mes) && $mes == "05") echo "selected"; ?> value="05">Mayo</option>
                                    <option <?php if (isset($mes) && $mes == "06") echo "selected"; ?> value="06">Junio</option>
                                    <option <?php if (isset($mes) && $mes == "07") echo "selected"; ?> value="07">Julio</option>
                                    <option <?php if (isset($mes) && $mes == "08") echo "selected"; ?> value="08">Agosto</option>
                                    <option <?php if (isset($mes) && $mes == "09") echo "selected"; ?> value="09">Septiembre</option>
                                    <option <?php if (isset($mes) && $mes == "10") echo "selected"; ?> value="10">Octubre</option>
                                    <option <?php if (isset($mes) && $mes == "11") echo "selected"; ?> value="11">Noviembre</option>
                                    <option <?php if (isset($mes) && $mes == "12") echo "selected"; ?> value="12">Diciembre</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="Etiqueta1"><span class="Asterisco">* </span><input type="checkbox" id="chk_fechas" name="chk_fechas" title="Fechas Manuales" value="first_checkbox" onclick="filtros();"> Inicio:&nbsp;</td>
                            <td width="27%">
                                <input id="Ats_Fec_Ini" name="Ats_Fec_Ini" type="date" data-date='' data-date-format="YYYY-MM-DD" class="form-control input-xs datepickers" tabindex="8" required='' disabled="true" />
                                <span class="input-group-addon input-xs" title="Fecha de inicio"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                            </td>
                            <td class="Etiqueta1"><span class="Asterisco">* </span>Fin:&nbsp;</td>
                            <td>
                                <input id="Ats_Fec_Fin" name="Ats_Fec_Fin" type="date" data-date='' data-date-format="YYYY-MM-DD" class="form-control input-xs datepickers" tabindex="8" required='' disabled="true" />
                                <span class="input-group-addon input-xs" title="Fecha fin"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="Etiqueta1" colspan="4"> 
                                <button type="button" class="btn btn-primary start" title="Guardar" onClick="atsGenerar(this)">
                                <i class="icon-book icon-white"></i><span>Generar</span>
                            </button> 
                            </td>
                        </tr>
                    </table>
                </FIELDSET>

                <?php
                $ats_title_ret = '';
                if (!empty($retenciones_Sin_Autorizacion)) {
                    $ats_title_ret = 'Retenciones sin Autorizar: ' . str_ireplace(array('<br>', '<br/>', '<br />'), ', ', strip_tags($retenciones_Sin_Autorizacion));
                }
                
                ?>
                <FIELDSET>
                    <LEGEND><label class="Titulos2">Rubros a Generar:</label></LEGEND>
                    <div class="ats-rubros ats-rubros--grid">
                        <label for="chk_Compras" class="ats-rubro-item ats-rubro-compras">
                            <input name="chk_Compras" type="checkbox" id="chk_Compras" checked>
                            <span class="ats-rubro-label">Compras</span>
                            <?php if (!empty($retenciones_Sin_Autorizacion)) { ?>
                                <span class="ats-rubro-alert ats-rubro-alert--ventas" title="Hay retenciones sin autorizar: <br><?php echo $retenciones_Sin_Autorizacion; ?>" aria-label="Hay retenciones sin autorizar"><i class="fa fa-exclamation-triangle"></i></span>
                            <?php } else { ?>
                                <i class="ats-rubro-notif icon-info-sign" title="Incluir comprobantes de compra en el ATS" aria-hidden="true"></i>
                            <?php } ?>
                        </label>
                        <label for="chk_Ventas" class="ats-rubro-item ats-rubro-ventas">
                            <input type="checkbox" id="chk_Ventas" name="chk_Ventas" checked="checked">                           
                            <span class="ats-rubro-label">Ventas</span>
                            <?php if (!empty( $ventas_Sin_Autorizacion)) { ?>
                                    <span class="ats-rubro-alert ats-rubro-alert--ventas" title="Hay ventas sin autorizar: <br><?php echo $ventas_Sin_Autorizacion; ?>" aria-label="Hay ventas sin autorizar"><i class="fa fa-exclamation-triangle"></i></span>
                                <?php } else { ?>
                                <i class="ats-rubro-notif icon-info-sign" title="Incluir comprobantes de venta en el ATS" aria-hidden="true"></i>
                            <?php } ?>
                        </label>
                        <label for="chk_Export" class="ats-rubro-item ats-rubro-export">
                            <input type="checkbox" id="chk_Export" name="chk_Export" checked="checked">
                            <span class="ats-rubro-label">Exportaciones</span>
                            <i class="ats-rubro-notif icon-info-sign" title="Incluir exportaciones en el ATS" aria-hidden="true"></i>
                        </label>
                        <label for="chk_Anulados" class="ats-rubro-item ats-rubro-anulados">
                            <input name="chk_Anulados" type="checkbox" id="chk_Anulados" checked>
                            <span class="ats-rubro-label">Anulados</span>
                            <i class="ats-rubro-notif icon-info-sign" title="Incluir comprobantes anulados en el ATS" aria-hidden="true"></i>
                        </label>
                    </div>
                </FIELDSET>
                <?php if (isset($bt_save)) /*  ------  Ojo se modifico el IF ------ */ { ?>
                <FIELDSET>
                    <LEGEND><label class="Titulos2">Descargar Documentos:</label></LEGEND>
                    <table width="100%" border='0' cellpadding='0' cellspacing='0' class="LetraNegra">
                        <tr><td class="ats-download-list"><?php
                            $buffer = '<?xml version="1.0" encoding="UTF-8"?><!--Archivo XML generado por Exa (http://www.exa.ofsercont.com)--><iva>';
                            $buffer = utf8_encode($buffer . $xml_identifica . $xml_compras . $xml_ventas . $xml_ventasEst . $xmlExportacion . $xml_anulados . '</iva>');

                            $path = "SRI/" . $Ses_Emp_Cod;
                            if (!is_dir($path))
                                mkdir($path, 0777, true);// Crea el directorio de forma recursiva

                            $archivo = $path . "/ATS" . $mes . $anio . ".xml";
                            if (file_exists($archivo))
                                unlink("SRI/" . $Ses_Emp_Cod . "/ATS" . $mes . $anio . ".xml");// eliminamos el file para crear uno nuevo

                            $file = fopen($archivo, "w+");
                            fwrite($file, $buffer);
                            fclose($file);
                            $urlXml = $archivo . '?X=' . rand(1, 100);
                            $urlAts     = 'tes_pri_ats_resumen_2.0.php?ini=' . urlencode($ini) . '&fin=' . urlencode($fin) . '&aCom=' . urlencode($valAuxCom) . '&bVen=' . urlencode($valAuxVen) . '&Exp=' . urlencode($valAuxExp) . '&cNul=' . urlencode($valAuxAnu) . '&url=' . urlencode($archivo);
                            $urlContable = 'tes_pri_ats_resumen_contable.php?ini=' . urlencode($ini) . '&fin=' . urlencode($fin) . '&aCom=' . urlencode($valAuxCom) . '&bVen=' . urlencode($valAuxVen) . '&Exp=' . urlencode($valAuxExp) . '&cNul=' . urlencode($valAuxAnu) . '&url=' . urlencode($archivo);
                            $mesLabel   = mes($mes, 1);
                            $nombreXml  = 'ATS' . $mes . $anio . '.xml';
                            echo '<div class="ats-download-btns">';
                            // Botón 1: Descargar XML
                            echo '<a href="' . htmlspecialchars($urlXml) . '" class="ats-download-btn ats-download-btn-xml" download="' . htmlspecialchars($nombreXml) . '" title="Descargar XML · ' . htmlspecialchars($mesLabel) . ' ' . $anio . '">';
                            echo '<i class="icon-download-alt icon-white ats-download-icon"></i>';
                            echo '<span class="ats-download-label">Archivo XML</span>';
                            echo '</a>';
                            // Botón 2: Ver ATS (Talón Resumen original)
                            echo '<a href="' . htmlspecialchars($urlAts) . '" class="ats-download-btn ats-download-btn-resumen" target="_blank" title="Talón de Resumen · ' . htmlspecialchars($mesLabel) . ' ' . $anio . '">';
                            echo '<i class="icon-eye-open icon-white ats-download-icon"></i>';
                            echo '<span class="ats-download-label">Tal&oacute;n de Resumen</span>';
                            echo '</a>';
                            // Botón 3: Resumen Contable por cuenta
                            echo '<a href="' . htmlspecialchars($urlContable) . '" class="ats-download-btn ats-download-btn-contable" target="_blank" title="Resumen Contable por Cuenta · ' . htmlspecialchars($mesLabel) . ' ' . $anio . '">';
                            echo '<i class="icon-list-alt icon-white ats-download-icon"></i>';
                            echo '<span class="ats-download-label">Resumen Contable</span>';
                            echo '</a>';
                            echo '</div>';
                            // echo "&raquo;&nbsp;&nbsp;IVA 5%, 15% (desde Abril 2024)  Tal&oacute;n de Resumen correspondiente a <strong>' . mes($mes, 1) . '</strong> del <strong>' . $anio . '</strong> <a href='tes_pri_ats_resumen_2.1.php?ini=" . $ini . "&fin=" . $fin . "&aCom=" . $valAuxCom . "&bVen=" . $valAuxVen . "&Exp=" . $valAuxExp . "&cNul=" . $valAuxAnu . "&url=" . $archivo . "' target='_blank'><img src='../../mascaras/model1/imagenes/32x32/download.gif' title='Imprimir Tal&oacute;n de Resumen'></a>';
                        ?></td></tr>
                    </table>
                </FIELDSET>
                <?php } else { /*Fin del iif (isset($bt_save)) */
                    if (isset($bt_save)) echo error_alerta("No hay resultados que mostrar", 1);
                } ?>
                <br>
                <table width="176" border='0' cellpadding='0' cellspacing='0' class="ats-actions">
                    <tr>
                        <td width="181"><input id="bt_save" name="bt_save" type="hidden" value="Grabar">
                           
                        </td>
                    </tr>
                </table>
            </form>
            </div>
            <div class="ats-main-right">
                <?php if (isset($bt_save)) { ?>
                <div class="ats-iframe-wrap">
                    <div id="ats-iframe-loader" class="ats-iframe-loader ats-iframe-loader-visible" aria-hidden="true">
                        <div class="ats-iframe-loader-spinner"></div>
                        <div class="ats-iframe-loader-text">Cargando resumen...</div>
                    </div>
                    <iframe id="ats-iframe" src="<?php echo 'tes_pri_ats_resumen_2.0.php?ini=' . urlencode($ini) . '&fin=' . urlencode($fin) . '&aCom=' . urlencode($valAuxCom) . '&bVen=' . urlencode($valAuxVen) . '&Exp=' . urlencode($valAuxExp) . '&cNul=' . urlencode($valAuxAnu) . '&url=' . urlencode($archivo); ?>"></iframe>
                </div>
                <?php } ?>
            </div>
        </div>
        </td>
    </tr>
</table>
</div><!-- .ats-container -->
</div>

<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
<script>$.clearValidate();</script>
<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
<script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
<script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
<link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />

<script type="text/javascript">
(function() {
    var form = document.getElementById('form1');
    if (form) {
        var nativeSubmit = form.submit.bind(form);
        form.submit = function() {
            window._atsFormSubmitting = true;
            nativeSubmit();
        };
    }
})();
function atsGenerar(btn) {
    var loader = document.getElementById('ats-loader');
    if (loader) loader.classList.add('ats-loader-visible');
    window._atsFormSubmitting = false;
    validar_requeridos(btn.form, 'anio*mes', 1);
    window.setTimeout(function() {
        if (!window._atsFormSubmitting) {
            var L = document.getElementById('ats-loader');
            if (L) L.classList.remove('ats-loader-visible');
        }
    }, 0);
}

// Talón Resumen: carga en iframe oculto y abre solo el cuadro de impresión (sin nueva ventana).
function atsImprimirResumen(btn) {
    var url = btn.getAttribute('data-url');
    if (!url) return;
    var f = document.getElementById('ats-print-frame');
    if (!f) {
        f = document.createElement('iframe');
        f.id = 'ats-print-frame';
        f.setAttribute('aria-hidden', 'true');
        f.style.cssText = 'position:absolute;left:-9999px;width:0;height:0;border:0;visibility:hidden';
        document.body.appendChild(f);
    }
    f.onload = function() {
        try { f.contentWindow.print(); } catch (e) {}
    };
    f.src = url;
}

// Loader interno del iframe: se oculta cuando "tes_pri_ats_resumen_2.0.php" termina de cargar.
(function() {
    var iframe = document.getElementById('ats-iframe');
    var loader = document.getElementById('ats-iframe-loader');
    if (!iframe || !loader) return;

    var hide = function() {
        loader.classList.remove('ats-iframe-loader-visible');
        loader.style.display = 'none';
    };

    // En caché el evento load puede llegar antes; por eso verificamos readyState.
    try {
        if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
            hide();
            return;
        }
    } catch (e) { /* ignorar por seguridad */ }

    iframe.addEventListener('load', function() {
        hide();
    });
})();
function filtros() {
    if (document.getElementById("chk_fechas").checked) {
        document.getElementById("Ats_Fec_Fin").disabled = false;
        document.getElementById("Ats_Fec_Ini").disabled = false;
        document.getElementById("mes").disabled = true;
        document.getElementById("anio").disabled = true;
    } else {
        document.getElementById("mes").removeAttribute("disabled");
        document.getElementById("anio").removeAttribute("disabled");
        document.getElementById("Ats_Fec_Fin").disabled = true;
        document.getElementById("Ats_Fec_Ini").disabled = true;
    }
}
$("document").ready(function() {
    // Inicializa el estado correcto de controles al cargar (para que anio/mes se envíen en el POST).
    try { filtros(); } catch (e) {}
});
$("input").on("change", function() {
    this.setAttribute(
        "data-date",
        moment(this.value, "YYYY-MM-DD")
        .format(this.getAttribute("data-date-format"))
    );
}).trigger("change");
</script>
<?php
// Cerrado de las conexiones
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
</BODY>
</HTML>