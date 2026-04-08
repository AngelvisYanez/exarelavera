<?php

/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
require_once('../../Librerias/config.php/register_globals.php');
if (!isset($clave) && (!isset($type) && !isset($Doc_Cod)) && !isset($_FILES)) {
    echo 'NO SE ENCONTRARON DATOS DE INGRESO';
    exit();
}
require_once('../LOGICA/fac_log_electronica.php');

$lista = array(
    '01' => 'VENTAS',
    '04' => 'NOTASC',
    '05' => 'NOTASD',
    '06' => 'GUIAS',
    '07' => 'RETENC',
    '08' => 'LIQUIDC'
);
function getTypeDoc($type)
{
    $obBD_elect = null;
    switch ($type) {
        case 'VENTAS';
            $obBD_elect =  new Class_Log_Datos_Factura_Elect;
            break;
        case 'NOTASC';
            $obBD_elect =  new Class_Log_Datos_NCredito_Elect;
            break;
        case 'NOTASD';
            $obBD_elect =  new Class_Log_Datos_NDebito_Elect;
            break;
        case 'GUIAS';
            $obBD_elect =  new Class_Log_Datos_Guia_Elect;
            break;
        case 'RETENC';
            $obBD_elect =  new Class_Log_Datos_Retencion_Elect;
            break;
        case 'LIQUIDC';
            $obBD_elect =  new Class_Log_Datos_LiquidacionCompras_Elect;
            break;
        default:
            exit();
    }
    return $obBD_elect;
}
if (isset($_FILES) && !empty($_FILES)) {
    try {
        if ($_FILES['archivoXML']['size'] > 0) {
            $explode_name = explode('.', $_FILES['archivoXML']['name']);
            if (strtoupper($explode_name[1]) == 'XML') {
                $docu = file_get_contents($_FILES["archivoXML"]["tmp_name"]);
                $sri = simplexml_load_string($docu);
                if (empty($sri->comprobante)) {
                    echo 'ERROR ESTRUCTURA XML';
                    exit();
                }
                $datoXml = simplexml_load_string($sri->comprobante);
                $ca = '' . $datoXml->infoTributaria[0]->claveAcceso;
                $obBD_elect = getTypeDoc($lista[substr($ca, 8, 2)]);
            } else throw new Exception("La extencion no es XML!");
        } else {
            if (strlen($claveXML) != 49) throw new Exception("El archivo y la clave estan vacios!");
            else {
                include_once '../../Librerias/FactElect/FirmaElectronica.php';
                $firmaClass = new FirmaElectronica();
                $firmaClass->setProduction(false);
                $resp = $firmaClass->autorizarSri($claveXML);
                if ($resp['success'] == true) {
                    $obBD_elect = getTypeDoc($lista[substr($claveXML, 8, 2)]);
                    $docu = $resp['xml'];
                } else throw new Exception($resp['message']);
            }
        }
        $logo = '';
        if (!empty($_FILES['imagenLogo']['name'])) $logo = $_FILES["imagenLogo"];
        //var_dump($logo); exit();
        $obBD_elect->createPdfByString($docu, $logo);
    } catch (Exception $e) {
        echo 'NO SE PUDO CARGAR XML: ' . $e->getMessage();
        exit();
    }
}


if (isset($clave)) {
    $Exa_Dat_Dis = null;
    $obBD_elect = getTypeDoc($lista[substr($clave, 8, 2)]);
    $obBD_conexion = new Class_Log_Conexion_Elect();
    $databases = $obBD_elect->getArrayConsultaSql("SELECT DISTINCT Dat_Dis FROM exa_master.data", $obBD_conexion);

    $sql = "";
    foreach ($databases as $i => $v) {
        if (!$obBD_conexion->selectDb($v['Dat_Dis'])) {
            unset($databases[$i]);
        } else {
            $empresa = $obBD_elect->getRowConsultaSql("SELECT emp_master.Emp_Cod FROM exa_master.empresas AS emp_master
                INNER JOIN $v[Dat_Dis].empresas AS emp_1 ON emp_master.Emp_Cod=emp_1.Emp_Cod WHERE emp_1.Emp_Ruc LIKE '" . substr($clave, 10, 10) . "%';", $obBD_conexion);
            if (!empty($empresa)) {
                $Exa_Dat_Dis = $v['Dat_Dis'];
                break;
            }
        }
    }
    $obBD_conexion->cerrar();
    if (!isset($Exa_Dat_Dis) || empty($Exa_Dat_Dis)) {
        echo 'ERROR AL BUSCAR BASE  DE DATOS';
        exit();
    }
    $obBD_conexion = new Class_Log_Conexion_Elect($Exa_Dat_Dis);
    $obBD_elect->createPdfByClave($clave, $obBD_conexion);
} else {
    if (isset($Doc_Cod)) {
        if (!isset($Ses_Cli_Dat_Dis) && !isset($Ses_Dat_Dis)) {
            echo 'NO SE ENCUENTRA LOGUEADO EN EL SISTEMA';
            exit();
        }
        
        $obBD_elect = getTypeDoc($type);
        $obBD_conexion = new Class_Log_Conexion_Elect(isset($Ses_Cli_Dat_Dis) ? $Ses_Cli_Dat_Dis : $Ses_Dat_Dis);
        $obBD_elect->createPdf($Doc_Cod, $obBD_conexion);
    }
}
