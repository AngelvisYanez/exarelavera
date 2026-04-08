<?Php
/**
 * Descripción: Permite generar archivo XML del formulario 104
 * Fecha de creación:   2015-05-21
 * Desarrollador:   Lewis Chimarro
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_104.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Anx;

/**
 * Incrementa la capacidad de espacio reservado en la memoria ram para este script 
 */
ini_set("memory_limit", "32M");

$ctas_param=$obBD_con1->getArrayConsulta('plan_param.selectWhere',array("Tpa_Abr in('IPM','FRM485','FRM615','FRM617','FRM618')",'Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion); 

if (isset($xml) || isset($html)) {    
    
    $ini_ant = $anio . '-'.($mes*1>1? str_pad(($mes*1)-1,2,0,STR_PAD_LEFT).'-01 00:00:00':'01-01 00:00:00');
    $fin_ant = $anio . '-'.($mes*1>1? str_pad(($mes*1)-1,2,0,STR_PAD_LEFT) . '-' . ultimoDia(($mes*1)-1, $anio) . ' 00:00:00':'01-31 00:00:00');
    if ($chk_fechas) {
        $ini = $Ats_Fec_Ini . ' 00:00:00';
        $fin = $Ats_Fec_Fin . ' 23:59:59';
        $anio = substr($fin, 0, 4);
        $mes = substr($fin, 5, 2);
    } else {
        $ini = $anio . '-' . $mes . '-' . '01 00:00:00';
        $fin = $anio . '-' . $mes . '-' . ultimoDia($mes, $anio) . ' 23:59:59
        ';
    }   
    $iva = 'true';
    $noiva = 'false';
    $row_ivas = $obBD_con1->getRowConsulta(37, $ini, $obBD_conexion);
    $valorIva = (int)$row_ivas['Iva_Por'];
    /**
     * Identificación 
     * Esta consulta nos permite obtener la informacion de la Empresa(Ruc, Nombre, etc) para llenar el encabezado del
     * archivo XML a generar
     */
    $row_identifica = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
    //$row_409_419 = $obBD_con1->getRowConsulta(3, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);
    //$row_509_519 = $obBD_con1->getRowConsulta(8, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion); 
    //$row_500_510_iva0 = $obBD_con1->getRowConsulta(7, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);        

    /* Iva por pagar pendiente por pagar MES ANTERIOR */
    $cta=reset(array_filter($ctas_param,function($e){if($e['Tpa_Abr']=='FRM485')return $e;}));    
    $iva_mes_ant = $obBD_con1->getRowConsulta(48, array('ini'=>$ini_ant,'fin'=>$fin_ant,'mes'=>$mes,'Emp_Cod'=>$Ses_Emp_Cod,'Pld_Cod'=>$cta['Pld_Cod']), $obBD_conexion);
    /* Credito tributario Adquisicion MES ANTERIOR 615*/
    $cta=reset(array_filter($ctas_param,function($e){if($e['Tpa_Abr']=='FRM615')return $e;}));
    $diario_615 = $obBD_con1->getRowConsulta(48, array('ini'=>$ini_ant,'fin'=>$fin_ant,'mes'=>$mes,'Emp_Cod'=>$Ses_Emp_Cod,'Pld_Cod'=>$cta['Pld_Cod']), $obBD_conexion,true);
    /* Credito tributario Retencion MES ANTERIOR 617*/
    $cta=reset(array_filter($ctas_param,function($e){if($e['Tpa_Abr']=='FRM617')return $e;}));
    $diario_617 = $obBD_con1->getRowConsulta(48, array('ini'=>$ini_ant,'fin'=>$fin_ant,'mes'=>$mes,'Emp_Cod'=>$Ses_Emp_Cod,'Pld_Cod'=>$cta['Pld_Cod']), $obBD_conexion,true);
    /* Credito tributario Retencion MES ANTERIOR 618*/
    $cta=reset(array_filter($ctas_param,function($e){if($e['Tpa_Abr']=='FRM618')return $e;}));
    $diario_618 = $obBD_con1->getRowConsulta(48, array('ini'=>$ini_ant,'fin'=>$fin_ant,'mes'=>$mes,'Emp_Cod'=>$Ses_Emp_Cod,'Pld_Cod'=>$cta['Pld_Cod']), $obBD_conexion,true);

    //VENTAS
    $vent_contado = $obBD_con1->getRowConsulta(15, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1*1", $obBD_conexion);
    $vent_credito = $obBD_con1->getRowConsulta(15, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1*2", $obBD_conexion);

    $row_402 = $obBD_con1->getRowConsulta(49, array('ini'=>$ini,'fin'=>$fin,'Emp_Cod'=>$Ses_Emp_Cod,'iva'=>$iva,'Tic_Cod'=>1,'Adq_Cor'=>"'A'",'Iva_Por'=>''), $obBD_conexion);
    $row_403 = $obBD_con1->getRowConsulta(49, array('ini'=>$ini,'fin'=>$fin,'Emp_Cod'=>$Ses_Emp_Cod,'iva'=>NULL,'Tic_Cod'=>1,'Adq_Cor'=>"'B','S','G','SM'",'Iva_Por'=>'', 'CreTri'=>"N"), $obBD_conexion);
    //$row_403 = $obBD_con1->getRowConsulta(13, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*1", $obBD_conexion);
    //$row_404 = $obBD_con1->getRowConsulta(14, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*1", $obBD_conexion);
    //$row_405 = $obBD_con1->getRowConsulta(13, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*1*S", $obBD_conexion);
    $row_404 = $obBD_con1->getRowConsulta(49, array('ini'=>$ini,'fin'=>$fin,'Emp_Cod'=>$Ses_Emp_Cod,'iva'=>NULL,'Tic_Cod'=>1,'Adq_Cor'=>"'A'",'Iva_Por'=>''), $obBD_conexion);
    $row_405 = $obBD_con1->getRowConsulta(49, array('ini'=>$ini,'fin'=>$fin,'Emp_Cod'=>$Ses_Emp_Cod,'iva'=>NULL,'Tic_Cod'=>1,'Adq_Cor'=>"'B','S','G','SM'",'Iva_Por'=>'', 'CreTri'=>"S"), $obBD_conexion);
    
    $row_407 = $obBD_con1->getRowConsulta(43, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*1", $obBD_conexion);
    $row_408 = $obBD_con1->getRowConsulta(42, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*1", $obBD_conexion);
    $row_434 = $obBD_con1->getRowConsulta(35, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "**41", $obBD_conexion);

    // IVA 5%
    $row_425_contado = $obBD_con1->getRowConsulta(15, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1*1*5", $obBD_conexion);
    $row_425_credito = $obBD_con1->getRowConsulta(15, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1*2*5", $obBD_conexion);
    //VENTAS N/C
    //$NC_contado= $obBD_con1->getRowConsulta(15, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*1", $obBD_conexion,true);
    //$NC_credito= $obBD_con1->getRowConsulta(15, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*2", $obBD_conexion,true);    
    
    $NC_Ventas = $obBD_con1->getRowConsulta(40, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*4*2", $obBD_conexion);    
    $row_412 = $obBD_con1->getRowConsulta(14, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*4", $obBD_conexion);
    $row_435 = $obBD_con1->getRowConsulta(14, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*4*5", $obBD_conexion); //5%
    $row_413 = $obBD_con1->getRowConsulta(13, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*4", $obBD_conexion);    
    $row_414 = $obBD_con1->getRowConsulta(14, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*4", $obBD_conexion);
    $row_415 = $obBD_con1->getRowConsulta(13, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*4*S", $obBD_conexion);
    // NOTA: no se puede diferenciar cuando es venta de activos fijos
    //COMPRAS        
    $row_500 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1~3*2~7", $obBD_conexion);
    $row_501 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1~3*4", $obBD_conexion);
    
    
    $row_502 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1~3*3", $obBD_conexion);
    
    
    $row_507 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*1~3", $obBD_conexion);
    $row_508 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*2", $obBD_conexion);
    $row_540 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*1~3*2~7*5", $obBD_conexion);//IVA 5%
    $row5_510 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*4*2~7*5", $obBD_conexion);

    //COMPRAS N/C       
    $row_510 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*4*2~7", $obBD_conexion);
    $row_511 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*4*4", $obBD_conexion);
    $row_512 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*4*3", $obBD_conexion);
    $row_517 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*4", $obBD_conexion);
    //$row_518 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*4", $obBD_conexion);
    // NOTA: no se puede diferencia RISE
    //IMPORTACIONES
    $row_503 = $obBD_con1->getRowConsulta(36, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*16*1~2*S", $obBD_conexion);
    $row_504 = $obBD_con1->getRowConsulta(36, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*16*1~2~6~7*B", $obBD_conexion);
    $row_505 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$iva*16*3~4", $obBD_conexion);
    $row_506 = $obBD_con1->getRowConsulta(12, $ini . '*' . $fin . '*' . $Ses_Emp_Cod . "*$noiva*16", $obBD_conexion);
    //RETENCIONES
    $row_609 = $obBD_con1->getRowConsulta(10, $ini . '*' . $fin . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $row_721 = $obBD_con1->getRowConsulta(9, $ini . '*' . $fin . '*' . '10' . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $row_723 = $obBD_con1->getRowConsulta(9, $ini . '*' . $fin . '*' . '20' . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $row_725 = $obBD_con1->getRowConsulta(9, $ini . '*' . $fin . '*' . '30' . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $row_727 = $obBD_con1->getRowConsulta(9, $ini . '*' . $fin . '*' . '50' . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $row_729 = $obBD_con1->getRowConsulta(9, $ini . '*' . $fin . '*' . '70' . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $row_731 = $obBD_con1->getRowConsulta(9, $ini . '*' . $fin . '*' . '100' . '*' . $Ses_Emp_Cod, $obBD_conexion);
    // ARREGLO CAMBIO DE IVA 423, Y 526, DIFERENTES IVAS        
    // $VENTAS_IVA= $obBD_con1->getRowConsulta(10, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);
    // $VENTAS_IVA_MES= $obBD_con1->getRowConsulta(10, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);
    // $COMPRAS_IVA_MES= $obBD_con1->getRowConsulta(10, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);
    // $COMPRAS_IVA_DIFERENTE= $obBD_con1->getRowConsulta(10, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);

    //INICIO DEL FORMULARIO 104
    $form['ruc'] = $row_identifica['Emp_Ruc'];
    $form['codigo_moneda'] = 1;
    $form['31'] = $tipo;
    $form['101'] = $mes;
    $form['102'] = $anio;
    $form['201'] = $row_identifica['Emp_Ruc'];
    $form['202'] = $row_identifica['Emp_Nom'];

    //VENTAS
    $form['401'] = formato_numero($vent_contado['Total'] + $vent_credito['Total'], 2, 1); //VENTAS GRAVADAS TARIFA 12% -- actual 15%
    $form['411'] = formato_numero($form['401'] - $NC_Ventas['Total']/*-$NC_contado['Total']-$NC_credito['Total']*/, 2, 1); //VENTAS GRAVADAS TARIFA 12% N/C
    $form['421'] = formato_numero(($form['411'] * $valorIva) / 100, 2, 1);
    

    $form['402'] = formato_numero($row_402['Total'], 2, 1); //VENTAS ACTIVOS FIJOS GRAVADAS TARIFA 12%
    $form['405'] ="0.00";
    $form['406'] ="0.00";
    $form['410'] ="0.00";
    $form['420'] ="0.00";
    $form['430'] ="0.00";
    $form['412'] = formato_numero($row_402['Total'] - $row_412['Total'], 2, 1); //VENTAS ACTIVOS FIJOS  GRAVADAS TARIFA 12% N/C
    $form['422'] = formato_numero(($form['412'] * $valorIva) / 100, 2, 1);

    /** Nuevos codigos de la operacion 5% SRI */
    $form['425'] = formato_numero($row_425_contado['Total'] + $row_425_credito['Total'], 2, 1); //Ventas locales (Excluye activos fijos) gravadas tarifa 5%
    $form['435'] = formato_numero($form['425'] - $row_435['Total'], 2, 1); //VENTAS ACTIVOS FIJOS  GRAVADAS TARIFA 5% N/C
    $form['445'] = formato_numero(($form['435'] * 5) / 100, 2, 1);
    /** Fin de las operaciones 5% */

    $form['423'] = formato_numero((($NC_contado['Total'] + $NC_credito['Total']) * $valorIva / 100) - $NC_contado['Iva'] - $NC_credito['Iva'], 2, 1);  // Diferencia del iva
    $form['424'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION

    $form['403'] = formato_numero($row_403['Total'] - $row_407['Total'] - $row_408['Total'], 2, 1); //VENTAS GRAVADAS TARIFA 0% menos exportaciones
    // $form['413']=formato_numero($row_403['Total']-$row_413['Total'],2,1);//VENTAS GRAVADAS TARIFA 0% N/C

    $form['413'] = formato_numero($row_403['Total'] - $row_413['Total'], 2, 1); //VENTAS 
    $form['404'] = formato_numero($row_404['Total'], 2, 1); //VENTAS ACTIVOS 
    $form['405'] = formato_numero($row_405['Total'], 2, 1); //VENTAS 0% CON CREDITO TRIBUTARIO

    $form['407'] = formato_numero($row_407['Total'], 2, 1); //EXPORTACION BIENES
    $form['408'] = formato_numero($row_408['Total'], 2, 1); //EXPORTACION SERVICIOS

    $form['415'] =formato_numero($row_405['Total'] - $row_415['Total'], 2, 1); //VENTAS 
    $form['416'] ="0.00";
    $form['111'] ='0.00';
    $form['113'] ='0.00';  

    $form['115'] ='0.00';
    $form['117'] ='0.00';
    $form['119'] ='0.00';


    $form['417'] = formato_numero($row_407['Total'], 2, 1); //EXPORTACION BIENES
    $form['418'] = formato_numero($row_408['Total'], 2, 1); //EXPORTACION SERVICIOS

    $form['414'] = formato_numero($row_404['Total'] - $row_414['Total'], 2, 1); //VENTAS ACTIVOS FIJOS  GRAVADAS TARIFA 12% N/C
    $form['424'] = formato_numero(($form['414'] * $valorIva) / 100, 2, 1);

    $form['431'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['434'] = formato_numero($row_434['Total'], 2, 1);
    $form['444'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['454'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION

    $form['441'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['442'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['443'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['453'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION

    //TOTALES VENTAS
    $form['409'] = ($form['401'] * 1) + ($form['402'] * 1) +  ($form['425'] * 1) + ($form['403'] * 1) + ($form['404'] * 1) + ($form['405'] * 1) + ($form['406'] * 1) + ($form['407'] * 1) + ($form['408'] * 1);
    $form['419'] = ($form['411'] * 1) + ($form['412'] * 1) +  ($form['435'] * 1) + ($form['413'] * 1) + ($form['414'] * 1) + ($form['415'] * 1) + ($form['416'] * 1) + ($form['417'] * 1) + ($form['418'] * 1);
    $form['429'] = formato_numero(($form['421'] * 1) + ($form['422'] * 1) +  ($form['445'] * 1)    + ($form['423'] * 1), 2, 1);
    $form['409'] = formato_numero($form['409'], 2, 1);
    $form['419'] = formato_numero($form['419'], 2, 1);
    $form['549'] = formato_numero($form['429'], 2, 1);

    //Liquidacion del IVA del mes
    $form['480'] = formato_numero($vent_contado['Total'] - $NC_contado['Total'], 2, 1); //VENTAS A CONTADO EN ESTE MES
    $form['481'] = formato_numero($vent_credito['Total'] - $NC_credito['Total'], 2, 1); //VENTAS A CREDITO EN ESTE MES
    // NOTA: estos valores no estan calculados xq no hay ventas a credito
    $form['482'] = $form['429'];

    $form['483'] = formato_numero($iva_mes_ant['saldo'], 2, 1); // 485 MES ANTERIOR
    $form['484'] = formato_numero($form['429'], 2, 1);
    $form['485'] = formato_numero($form['482'] - $form['484'], 2, 1);
    $form['486'] ='0.00';
    $form['487'] ='0.00';
    $form['499'] = formato_numero($form['483'] + $form['484'], 2, 1);

    //COMPRAS
    $form['500'] = formato_numero($row_500['Importe'], 2, 1); //Adquisiciones y pagos 12%
    $form['510'] = formato_numero($row_500['Importe'] - $row_510['Importe'], 2, 1); //Adquisiciones y pagos 12% N/C
    $form['520'] = formato_numero(($form['510'] * $valorIva) / 100, 2, 1);
    $form['530'] = formato_numero($row_500['subDif'], 2, 1); //Compras iva diferenciado 8%
    $form['533'] = formato_numero($row_500['subDif'] - $row_510['subDif'], 2, 1); //Compras iva diferenciado 8%  N/C
    $form['534'] = formato_numero($row_500['Iva_Dif'], 2, 1);

    //IVA 5%
    $form['540'] = formato_numero($row_540['Importe'], 2, 1); //Adquisiciones y pagos 12%
    $form['550'] = formato_numero($row_540['Importe'] - $row5_510['Importe'], 2, 1); //Adquisiciones y pagos 12% N/C
    $form['560'] = formato_numero(($form['550'] * 5) / 100, 2, 1);//iva 5%

    $form['501'] = formato_numero($row_501['Importe'], 2, 1); //Otras Adquisiciones y pagos con derecho 12%
    $form['511'] = formato_numero($row_501['Importe'] - $row_511['Importe'], 2, 1); //Otras Adquisiciones y pagos con derecho 12% N/C
    $form['521'] = formato_numero(($form['511'] * $valorIva) / 100, 2, 1);

    $form['502'] = formato_numero($row_502['Importe'], 2, 1); //Otras Adquisiciones y pagos sin derecho 12%
    $form['512'] = formato_numero($row_502['Importe'] - $row_512['Importe'], 2, 1); //Otras Adquisiciones y pagos sin derecho 12% N/C
    $form['522'] = formato_numero(($form['512'] * $valorIva) / 100, 2, 1);
    $form['523'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['524'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['525'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION

    $form['526'] = formato_numero((($row_510['Importe'] + $row_511['Importe'] + $row_512['Importe']) * $valorIva / 100) - (($row_510['Iva'] + $row_511['Iva'] + $row_512['Iva'])), 2, 1); // Diferencia del iva
    $form['527'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION

    $form['503'] = formato_numero($row_503['Importe'], 2, 1); //Importaciones Servicios 12%
    $form['504'] = formato_numero($row_504['Importe'], 2, 1); //Importaciones Bienes 12%
    $form['505'] = formato_numero($row_505['Importe'], 2, 1); //Importaciones Activos 12%
    $form['506'] = formato_numero($row_506['Importe'], 2, 1); //Importaciones Bienes incluye activos 0%
    $form['513'] = $form['503'];
    $form['514'] = $form['504'];
    $form['515'] = $form['505'];
    $form['516'] = $form['506'];

    $form['507'] = formato_numero($row_507['Importe'], 2, 1); //Adquisiciones y pagos 0%
    $form['517'] = formato_numero($row_507['Importe'] - $row_517['Importe'], 2, 1); //Adquisiciones y pagos 0% N/C

    $form['508'] = formato_numero($row_508['Importe'], 2, 1); //Adquisiciones a cont. RISE
    $form['518'] = formato_numero($row_508['Importe'], 2, 1); //Adquisiciones a cont. RISE N/C

    //TOTALES COMPRAS       
    $form['509'] = ($form['500'] * 1) + ($form['501'] * 1) + ($form['540'] * 1) +($form['502'] * 1) + ($form['503'] * 1) + ($form['504'] * 1) + ($form['505'] * 1) + ($form['506'] * 1) + ($form['507'] * 1) + ($form['508'] * 1)+ ($form['530'] * 1);
    $form['519'] = ($form['510'] * 1) + ($form['511'] * 1) + ($form['533'] * 1)+ ($form['550'] * 1) +($form['512'] * 1) + ($form['513'] * 1) + ($form['514'] * 1) + ($form['515'] * 1) + ($form['516'] * 1) + ($form['517'] * 1) + ($form['518'] * 1);
    $form['529'] = ($form['520'] * 1) + ($form['521'] * 1) + ($form['534'] * 1)+ ($form['560'] * 1) +($form['522'] * 1) + ($form['523']) + ($form['524'] * 1) + ($form['525'] * 1) + ($form['526'] * 1)+ ($form['534'] * 1);
    $form['509'] = formato_numero($form['509'], 2, 1);
    $form['519'] = formato_numero($form['519'], 2, 1);
    $form['529'] = formato_numero($form['529'], 2, 1);

    $form['531'] ='0.00';
    $form['532'] ='0.00';
    $form['535'] ='0.00';

    $form['541'] ='0.00';
    $form['542'] ='0.00';
    $form['543'] ='0.00';
    $form['544'] ='0.00';
    $form['545'] ='0.00';

    $form['554'] ='0.00';
    $form['555'] ='0.00';

    if (($form['419'] * 1) > 0){ //FACTOR PROPORCIONALIDAD
        $form['563'] = formato_numero((($form['411'] * 1) + ($form['412'] * 1) + ($form['420'] * 1) + ($form['415'] * 1) + ($form['416'] * 1) + ($form['417'] * 1) + ($form['418'] * 1)+ ($form['435'] * 1)) / $form['419'], 2, 1);
    }
    $form['564'] = (($form['520']*1)+($form['521']*1)+($form['523']*1)+($form['524']*1)+($form['525']*1)+($form['526']*1)+($form['527']*1)+($form['534']*1)+($form['560']*1)) * ($form['563']*1);
    $form['564'] = formato_numero($form['564'], 2, 1); //CREDITO TRIBUTARIO APLICABLE

    $IMPOSITIVO = ($form['499'] * 1) - ($form['564'] * 1);
    $CREDITOTRIB = ($form['564'] * 1) - ($form['499'] * 1);
    if ($IMPOSITIVO > 0) {
        $form['601'] = formato_numero($IMPOSITIVO, 2, 1);
        $form['602'] = formato_numero(0, 2, 1);
    } else {
        $form['602'] = formato_numero($CREDITOTRIB, 2, 1);
        $form['601'] = formato_numero(0, 2, 1);
    }
   
    

    //CODIGOS QUE FALTAN POR PROGRAMAR
    $form['603'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['605'] = formato_numero($diario_615['saldo_debe'], 2, 1); 
    $form['606'] = formato_numero($diario_617['saldo_debe'], 2, 1); 
    $form['607'] = formato_numero($diario_618['saldo_debe'], 2, 1); 
    $form['608'] = formato_numero($diario_619['saldo_debe'], 2, 1); 
    $form['609'] = formato_numero($row_609['Iva_Ret'], 2, 1); //Ret en la fuente del periodo
    $form['610'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['611'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['612'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['613'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['614'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['615'] = formato_numero((($form['605']+$form['602'])-$form['601']<0?0:($form['605']+$form['602'])-$form['601']), 2, 1);
    $form['617'] = formato_numero(($form['605']-$form['601']<0? ($form['606']-(($form['605']-$form['601'])*-1)+$form['609']>0?($form['606']-(($form['605']-$form['601'])*-1)+$form['609']):0):($form['606']+$form['609'])), 2, 1); 
    $form['618'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['619'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION
    $form['621'] = formato_numero(0, 2, 1); //NUEVOS CODIGOS DEL SRI FALTA PROGRAMAR LA OPERACION        
    $form['700'] ='0.00';
    $form['701'] ='0.00';
    $form['702'] ='0.00';
    $form['800']= '0.00';
    $form['802']= '0.00';
    $form['620'] = ($form['601'] * 1) - ($form['602'] * 1) - ($form['603'] * 1) - ($form['604'] * 1) - ($form['605'] * 1) - ($form['606'] * 1) - ($form['607'] * 1) - ($form['608'] * 1) - ($form['609'] * 1) + ($form['610'] * 1) + ($form['611'] * 1) + ($form['612'] * 1) + ($form['613'] * 1) + ($form['614'] * 1);

    if ($form['620'] > 0) {
        formato_numero($form['620'], 2, 1);
    } else {
        $form['620'] = '0.00';
    } //SUBTOTAL A PAGAR

    $form['699'] = formato_numero(($form['620'] * 1) + ($form['621'] * 1), 2, 1); //TOTAL IMPUESTO a pagar por Percepcion
    $form['721'] = formato_numero($row_721['Valor'], 2, 1); //Ret del 10
    $form['723'] = formato_numero($row_723['Valor'], 2, 1); //Ret del 20
    $form['725'] = formato_numero($row_725['Valor'], 2, 1); //Ret del 30
    $form['727'] = formato_numero($row_727['Valor'], 2, 1); //Ret del 50
    $form['729'] = formato_numero($row_729['Valor'], 2, 1); //Ret del 70
    $form['731'] = formato_numero($row_731['Valor'], 2, 1); //Ret del 100
    $form['799'] = formato_numero(($form['721'] * 1) + ($form['723'] * 1) + ($form['725'] * 1) + ($form['727']) + ($form['729'] * 1) + ($form['731'] * 1),2,1); //TOTAL RETENCIONES
    //$form['799']=($form['721']*1)+($form['723']*1)+($form['725']*1);    
    $form['801'] = ($form['799'] * 1) - ($form['800'] * 1)- ($form['802'] * 1);
    $form['859'] = formato_numero(($form['699'] * 1) + ($form['801'] * 1), 2, 1); //TOTAL CONSOLIDADO DE IVA
    $form['902'] = ($form['859'] * 1) - ($form['898'] * 1); //TOTAl imp a pagar
    $form['999'] = $form['902']; //TOTAL PAGADO
    $form['905'] = $form['902']; //MEDIANTE CHEQUE, DEBITO, N/C

    $form['104'] = "";
    $form['198'] = $row_identifica['Emp_Rre'];
    $form['199'] = $row_identifica['Emp_Rco'];
    $form['908'] = "";
    $form['910'] = "";
    $form['912'] = "";
    $form['916'] = "";
    $form['918'] = "";
    $form['921'] = "2";
    $form['922'] = "16";

   /* if (isset($xml)) {
        $buffer = reporteHtml($form, 'tes_pri_plantilla_104.xml');
        $responce['xml_104'] = preg_replace('/{(.+)}/', '0.00', $buffer);
        $responce['name'] = "104-" . $tipo . "-" . mes($mes, 2) . "-" . $anio . "-" . $row_identifica['Emp_Ruc'] . ".xml";
    }
    if (isset($html)) {
        $buffer = reporteHtml($form, 'tes_pri_html_104.html');
        $responce['html_104'] = preg_replace('/{(.+)}/', '', $buffer);
    }*/
    $responce['form'] = $form;    
    $responce['success'] = true;
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

?>

<html lang="en">
<head>
    
    <title><?Php echo "Formulario 104 [EXA]"; ?></title>
    <meta charset="UTF-8">    	
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />    
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/moment/moment.min.js"></script>
	<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript">
        $(() => {$('#set1 *').tooltip({showURL:false});});
    </script>
    <meta http-equiv="Content-Type" content="text/html;">
    <style type="text/css" media="screen">
    .table {
        background: #ffffff!important;
    }
     th, td {
        padding: 2px !important;
        font-size: 11px;
    }
    tr:nth-child(even) {
      background-color: #e1dfdf;
    }
    tr:hover {
      background-color: #cac7c7; /* Cambia este color como prefieras */
      cursor: pointer; /* Opcional: cambia el cursor */
    }
    .lbl {
        line-height: 1;
        padding: 0;
        margin: 0;
        font-size: 11px;
    }
    .input_edit{
        border: none; 
        background: transparent;
        outline: none; 
        color: inherit;
        text-align: right;
        font-weight: bold;
    }   
   
</style>
</head>


<body>
    <div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo; Formulario 104</h3>
		</div>
         <div class="col-xs-12 Titulos2"><br><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.<hr></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div id="documentoSearch">
				<div class="row">
                    <form action="" class="form-horizontal normal" method="post" id="form1">
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
								<legend class="Titulos2">Filtro:</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Año:</label>
                                        <div class="col-sm-5 ">
                                            <select name="anio" id="anio" class="form-control input-sm" onchange="generaHtml();">
                                                <?Php for ($i = date("Y"); $i >= date("Y") - 5; $i--) { //Presentamos los dos ultimos a�os para generar el XML  ?>
                                                    <option <?Php if ($anio == $i) { echo "selected"; } ?> value="<?Php echo $i; ?>"><?Php echo $i; ?></option>
                                                <?Php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Mes:</label>
                                        <div class="col-sm-5 ">
                                            <select name="mes" id="mes" class="form-control input-sm" onchange="generaHtml();">
                                                <option value="01">Enero</option>
                                                <option value="02">Febrero</option>
                                                <option value="03">Marzo</option>
                                                <option value="04">Abril</option>
                                                <option value="05">Mayo</option>
                                                <option value="06">Junio</option>
                                                <option value="07">Julio</option>
                                                <option value="08">Agosto</option>
                                                <option value="09">Septiembre</option>
                                                <option value="10">Octubre</option>
                                                <option value="11">Noviembre</option>
                                                <option value="12">Diciembre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">                                                                                            
                                        <label class="col-xs-2 control-label label-xs">Fechas:</label>
                                        <div class="col-sm-1">
                                            <input id="chk_fechas" name="chk_fechas" type="checkbox" value="first_checkbox" onclick="filtros();" alt="" />                                            
                                        </div>
                                        <label class="col-xs-1 control-label label-xs">Inicio:</label>
                                        <div class="col-sm-2">
                                            <input id="Ats_Fec_Ini" name="Ats_Fec_Ini" type="date" data-date=""  class="form-control input-xs datepicker" tabindex="8" required="" disabled="true" />
                                        </div>
                                        <label class="col-xs-1 control-label label-xs">Fin:</label>
                                        <div class="col-sm-2">
                                            <input id="Ats_Fec_Fin" name="Ats_Fec_Fin" type="date" data-date=""  class="form-control input-xs datepicker" tabindex="8" required="" disabled="true" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Tipo:</label>
                                        <div class="col-xs-10 radioset opt_search">
                                            <input id="radio" name="tipo" type="radio" value="O" checked="" onclick="/*$('#Html104').printElement()*/ $('#Tip_Frm').html('- ORIGINAL')" alt="" />
                                            <label for="radio">Original</label>
                                            <input id="radio2" name="tipo" type="radio" value="S" onclick="/*validar_requeridos(this.form, 'anio*mes', 1)*/ $('#Tip_Frm').html('- SUSTITUTIVA')" alt="" />
                                            <label for="radio2">Sustitutiva</label>
                                        </div>
                                    </div>
                                    <div>						
                                        <button class="btn btn-sm btn-success" type="button" onclick="generaHtml()"><i class="glyphicon glyphicon-search"></i> Generar</button>
                                    </div>
								</div>
                            </fieldset>
                        </div>
                        <fieldset class="exa-fieldset">                            
							<legend class="Titulos2">Datos Generados:</legend>
                            <div class="text-right">						
                                <button class="btn btn-sm btn-primary" type="button" onclick="imprimirDiv('Html104');"><i class="glyphicon glyphicon-print"></i> Resumen</button>
                                <button class="btn btn-sm btn-primary" type="button" onclick="generaAsiento();"><i class="fa fa-magic"></i> Generar Diario</button>
                            </div><br>
                            <div>                                                
                                <div class="LetraNegra" id="Html104">                                            
                                    <table id="tbl_104" name="tbl_104" style="width:100%;min-width:700px;font-size:10px;table-layout:fixed;border:0px solid grey;border-top:0; line-height: 1;  padding: 0; margin: 0;">
                                    <tr>
                                        <td width="55%"></td>
                                        <td width="5%"></td>
                                        <td width="10%"></td>
                                        <td width="5%"></td>
                                        <td width="10%"></td>
                                        <td width="5%"></td>
                                        <td width="10%"></td>                                                   
                                    </tr>
                                    <tr style="font-size:12px; background: #0c4597;color: #ffffff;" ;>
                                        <td colspan="7" align="center" style="color:#ffffff;"><label  class="lbl" id="201" style="font-size:14px;" ></label> - <label style="font-size:14px;" class="lbl" id="202"></label></td>
                                    </tr>
                                    <tr style="font-weight:bold;font-size:15px; color: #000000;">
                                        <td colspan="2" style="border-bottom: 0px solid black; font-size:14px;">FORMULARIO 104 <span id="Tip_Frm">- ORIGINAL</span></td>
                                        <td colspan="5" align="right" style="border-bottom: 0px solid black;"><label  class="lbl" id="101" style="font-size:14px;">Mes</label>/<label style="font-size:14px;" class="lbl" id="102">Año</label></td>
                                    </tr>    
                                    
                                    <tr style="font-weight:bold;font-size:12px;background: #0c4597;color: #ffffff;">
                                        <td style="border-bottom:1px solid black;">RESUMEN DE VENTAS Y OTRAS OPERACIONES DEL PERIODO</td>
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">V. BRUTO</td>
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">V. NETO (V.B - NC)</td>
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">IMPUESTO</td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">VENTAS LOCALES (EXCLUYE ACTIVOS FIJOS) GRAVADAS TARIFA DIFERENTE A CERO</td>
                                        <td align="right">401</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="401">0.00</label></td>
                                        <td align="right">411</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="411">0.00</label></td>
                                        <td align="right">421</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="421">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td>VENTAS DE ACTIVOS FIJOS GRAVADAS TARIFA DIFERENTE A CERO</td>
                                        <td align="right">402</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="402">0.00</label></td>
                                        <td align="right">412</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="412">0.00</label></td>
                                        <td align="right">422</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="422">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td>VENTAS (EXCLUYE ACTIVOS FIJOS) GRAVADAS TARIFA VARIABLE DIF. DE CERO</td>
                                        <td align="right">410</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="410">0.00</label></td>
                                        <td align="right">420</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="420">0.00</label></td>
                                        <td align="right">430</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="430">0.00</label></td>
                                    </tr>
                                    <!-- TARIFA 5% -->
                                    <tr>
                                        <td>VENTAS LOCALES (EXCLUYE ACTIVOS FIJOS) GRABADOS TARIFA 5%</td>
                                        <td align="right">425</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="425">0.00</label></td>
                                        <td align="right">435</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="435">0.00</label></td>
                                        <td align="right">445</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="445">0.00</label></td>
                                    </tr>
                                    <!--FIN TARIFA 5%-->
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;" colspan="5">IVA GENERADO EN LA DIFERENCIA ENTRE VENTAS Y NOTAS DE CREDITO CON DIFERENTE TARIFA (AJUSTE A PAGAR)</td>                                                    
                                        <td align="right">423</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="423">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;" colspan="5">IVA GENERADO EN LA DIFERENCIA ENTRE VENTAS Y NOTAS DE CREDITO CON DIFERENTE TARIFA (AJUSTE A FAVOR)</td>                                                    
                                        <td align="right">424</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="424">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">VENTAS LOCALES (EXCLUYE ACTIVOS FIJOS) GRAVADAS TARIFA 0% SIN DERECHO A CRED.TRIB.</td>
                                        <td align="right">403</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="403">0.00</label></td>
                                        <td align="right">413</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="413">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">VENTAS DE ACTIVOS FIJOS GRAVADAS TARIFA 0% SIN DERECHO A CRED.TRIB.</td>
                                        <td align="right">404</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="404">0.00</label></td>
                                        <td align="right">414</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="414">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">VENTAS LOCALES (EXCLUYE ACTIVOS FIJOS) GRAVADAS TARIFA 0% CON DERECHO A CRED.TRIB.</td>
                                        <td align="right">405</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="405">0.00</label></td>
                                        <td align="right">415</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="415">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>VENTAS DE ACTIVOS FIJOS GRAVADAS TARIFA 0% CON DERECHO A CRED.TRIB.</td>
                                        <td align="right">406</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="406">0.00</label></td>
                                        <td align="right">416</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="416">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>EXPORTACIONES DE BIENES</td>
                                        <td align="right">407</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="407">0.00</label></td>
                                        <td align="right">417</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="417">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>EXPORTACIONES DE SERVICIOS Y/O SERVICIOS</td>
                                        <td align="right">408</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="408">0.00</label></td>
                                        <td align="right">418</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="418">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>

                                    <tr style="background: #FFCC66;color: #000000;">
                                        <td><u>TOTAL VENTAS Y OTRAS OPERACIONES</u></td>
                                        <td align="right">409</td>
                                        <td align="right" style="font-weight:bold; border: 1px solid black;"><label  class="lbl" id="409">0.00</label></td>
                                        <td align="right">419</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="419">0.00</label></td>
                                        <td align="right">429</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="429">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td>TRANSFERENCIAS NO OBJETO O EXENTAS DE IVA</td>
                                        <td align="right">431</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="431">0.00</label></td>
                                        <td align="right">441</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="441">0.00</label></td>
                                        <td align="right"></td>
                                        <td align="right" style=""></td>                                                    
                                    </tr>
                                    <tr>
                                        <td>NOTAS DE CREDITO TARIFA 0% POR COMPENSAR PROXIMO MES</td>
                                        <td align="right"></td>
                                        <td align="right" style=""></td>
                                        <td align="right">442</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="442">0.00</label></td>
                                        <td align="right"></td>
                                        <td align="right" style=""></td>                                                   
                                    </tr>
                                    <tr>
                                        <td>NOTAS DE CREDITO TARIFA DIFERENTE DE CERO POR COMPENSAR PROX. MES</td>
                                        <td align="right"></td>
                                        <td align="right" style=""></td>
                                        <td align="right">443</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="443">0.00</label></td>
                                        <td align="right">453</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="453">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td>INGRESOS POR REEMBOLSO COMO INTERMEDIARIO (INFORMATIVO)</td>
                                        <td align="right">434</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="434">0.00</label></td>
                                        <td align="right">444</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="444">0.00</label></td>
                                        <td align="right">454</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="454">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7">&nbsp;</td>
                                    </tr>                                    
                                    <tr style="font-weight:bold;font-size:12px;background: #0c4597;color: #ffffff;">
                                        <td colspan="7" style="border-bottom:1px solid black;border-top: 0 px solid black;">LIQUIDACION DEL IVA EN EL MES</td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">TRANSFERENCIAS TARIFA DIFERENTE A CERO AL CONTADO ESTE MES</td>
                                        <td align="right">480</td>                                        
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="480">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_480" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">TRANSFERENCIAS TARIFA DIFERENTE A CERO A CREDITO ESTE MES</td>
                                        <td align="right">481</td>                                        
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="481">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_481" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">TOTAL IMPUESTO GENERADO</td>
                                        <td align="right">482</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="482">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">IMPUESTO A LIQUIDAR DEL MES ANTERIOR</td>
                                        <td align="right">483</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="483">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_483" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">IMPUESTO A LIQUIDAR ESTE MES</td>
                                        <td align="right">484</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0; float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;" class="lbl" id="484">0.00</label> <input type="text" style="display: none" class="input_edit" id="txt_484" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">IMPUESTO A LIQUIDAR EN EL PROXIMO MES</td>
                                        <td align="right">485</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="485">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">MES A PAGAR EL MONTO DE IVA DIFERENTE DE CERO POR VENTAS A CREDITO ESTE MES</td>
                                        <td align="right">486</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="486">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">TAMA&Ntilde;O COPCI</td>
                                        <td align="right">487</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="487">0.00</label></td>
                                    </tr>


                                    <tr style="background: #FFCC66;color: #000000;">
                                        <td colspan="5">TOTAL IMPUESTO A LIQUIDAR EN ESTE MES</td>
                                        <td align="right">499</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="499">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="3">TOTAL COMPROBANTES DE VENTA EMITIDOS</td>
                                        <td align="right">111</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="111">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3">TOTAL COMPROBANTES DE VENTA ANULADOS</td>
                                        <td align="right">113</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="113">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td colspan="7">&nbsp;</td>
                                    </tr>

                                    
                                    
                                    <tr style="font-weight:bold;font-size:12px;background: #0c4597;color: #ffffff;">
                                        <td style="border-bottom:1px solid black;">RESUMEN DE ADQUISICIONES Y PAGOS DEL PERIODO</td>
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">V. BRUTO</td>
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">V. NETO (V.B - NC)</td>
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">IMPUESTO</td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">ADQUISICIONES Y PAGOS(EXCLUYE ACTIVOS FIJOS) TARIFA DIFERENTE
                                        A CERO</td>
                                        <td align="right">500</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="500">0.00</label></td>
                                        <td align="right">510</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="510">0.00</label></td>
                                        <td align="right">520</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="520">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">ADQUISICIONES LOCALES DE ACT. FIJOS TARIFA DIFERENTE A CERO
                                        CON DERECHO A CRED. TRIB.</td>
                                        <td align="right">501</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="501">0.00</label></td>
                                        <td align="right">511</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="511">0.00</label></td>
                                        <td align="right">521</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="521">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">ADQUISICIONES Y PAGOS (EXCLUYE ACTIVOS FIJOS) TARIFA VAR. (CON CRED. TRIB.)</td>
                                        <td align="right">530</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="530">0.00</label></td>
                                        <td align="right">533</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="533">0.00</label></td>
                                        <td align="right">534</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="534">0.00</label></td>
                                    </tr>
                                    
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">ADQUISICIONES Y PAGOS LOCALES (EXCLUYE ACTIVOS FIJOS)
                                        GRABADOS CON TARIFA 5%(CON DERECHO A CR&Eacute;DITO TRIBUTARIO)</td>
                                        <td align="right">540</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="540">0.00</label></td>
                                        <td align="right">550</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="550">0.00</label></td>
                                        <td align="right">560</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="560">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">OTRAS ADQUISICIONES Y PAGOS TARIFA DIFERENTE A CERO SIN DERECHO A CRED. TRIB.</td>
                                        <td align="right">502</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="502">0.00</label></td>
                                        <td align="right">512</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="512">0.00</label></td>
                                        <td align="right">522</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="522">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td>IMPORTACIONES DE SERVICIOS TARIFA DIFERENTE A CERO</td>
                                        <td align="right">503</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="503">0.00</label></td>
                                        <td align="right">513</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="513">0.00</label></td>
                                        <td align="right">523</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="523">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td>IMPORTACIONES DE BIENES (EXCLUYE ACT. FIJOS) TARIFA DIFERENTE A CERO</td>
                                        <td align="right">504</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="504">0.00</label></td>
                                        <td align="right">514</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="514">0.00</label></td>
                                        <td align="right">524</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="524">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td>IMPORTACIONES DE ACT. FIJOS TARIFA DIFERENTE A CERO</td>
                                        <td align="right">505</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="505">0.00</label></td>
                                        <td align="right">515</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="515">0.00</label></td>
                                        <td align="right">525</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="525">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">IVA GENERADO EN LA DIFERENCIA ENTRE ADQUICISIONES Y NOTAS DE CREDITO CON DISTINTA TARIFA(AJUSTE EN POSITIVO)</td>
                                        <td colspan="4"></td>
                                        <td align="right">526</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="526">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_526" value="0.00"></td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">IVA GENERADO EN LA DIFERENCIA ENTRE ADQUICISIONES Y NOTAS DE CREDITO CON DISTINTA TARIFA(AJUSTE EN NEGATIVO)</td>
                                        <td colspan="4"></td>
                                        <td align="right">527</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="527">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_527" value="0.00"></td>
                                    </tr>
                                    <tr>
                                        <td>IMPORTACIONES DE BIENES (INCLUYE ACT. FIJOS) 0%</td>
                                        <td align="right">506</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="506">0.00</label></td>
                                        <td align="right">516</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="516">0.00</label></td>
                                        <td align></td>
                                        <td></td>        
                                    </tr>
                                    <tr>
                                        <td>ADQUISICIONES Y PAGOS (INCLUYE ACT. FIJOS) 0%</td>
                                        <td align="right">507</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="507">0.00</label></td>
                                        <td align="right">517</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="517">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">ADQUISICIONES REALIZADAS A CONTRIBUYENTES RISE (HASTA DICIEMBRE 2021), NEGOCIOS POPULARES (DESDE ENERO 2022)</td>
                                        <td align="right">508</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="508">0.00</label></td>
                                        <td align="right">518</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="518">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>
                                    <tr style="background: #ffcc66;color: #000000;">
                                        <td><u>TOTAL ADQUISICIONES Y PAGOS</u></td>
                                        <td align="right">509</td>
                                        <td align="right" style="font-weight:bold; border: 1px solid black;"><label  class="lbl" id="509">0.00</label></td>
                                        <td align="right">519</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="519">0.00</label></td>
                                        <td align="right">529</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="529">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td>ADQUISICIONES NO OBJETO DE IVA</td>
                                        <td align="right">531</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="531">0.00</label></td>
                                        <td align="right">541</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="541">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td>ADQUISICIONES EXENTAS DEL PAGO DE IVA</td>
                                        <td align="right">532</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="532">0.00</label></td>
                                        <td align="right">542</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="542">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td>NOTAS DE CREDITO TARIFA 0% POR COMPENSAR PROXIMO MES</td>
                                        <td align="right"></td>
                                        <td align="right"></td>
                                        <td align="right">543</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="543">0.00</label></td>
                                        <td align></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">NOTAS DE CREDITO TARIFA DIFERENTE DE CERO POR COMPENSAR PROXIMO MES</td>
                                        <td align="right"></td>
                                        <td align="right"></td>
                                        <td align="right">544</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="544">0.00</label></td>
                                        <td align="right">554</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="554">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td>PAGOS NETOS POR REEMBOLSO COMO INTERMEDIARIOO / VALORES...<!-- FACTURADOS POR SOCIOS A OPERADORAS DE
                                        TRANSPORTE/PAGOS<br>
                                        REALIZADOS POR PARTE DE LAS SOCIEDADES DE GESTION COLECTIVA COMO INTERMEDIARIOS (INFORMATIVO) --></td>
                                        <td align="right">535</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="535">0.00</label></td>
                                        <td align="right">545</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="545">0.00</label></td>
                                        <td align="right">555</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="555">0.00</label></td>
                                    </tr>

                                    
                                    <tr>
                                        <td colspan="5" style="border-top:0px solid black;">FACTOR DE PROPORCIONALIDAD DE CR&Eacute;DITO TRIBUTARIO (411+412+420+435+415+416+417+418) / 419</td>
                                        <td align="right" style="border-top:0px solid black;">563</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="563">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">CR&Eacute;DITO TRIBUTARIO APLICABLE EN ESTE PERIODO (520+521+534+560+523+524+525+526-527) x 563</td>
                                        <td align="right">564</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="564">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7">&nbsp;</td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">TOTAL COMPROBANTES DE VENTA RECIBIDOS POR ADQUISICIONES Y PAGOS (EXCEPTO NOTA DE VENTA)</td>
                                        <td align="right">115</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="115">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">TOTAL NOTA DE VENTA RECIBIDAS</td>
                                        <td align="right">117</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="117">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" style="white-space: nowrap; overflow: hidden;">TOTAL LIQUIDACIONES DE COMPRA EMITIDAS (POR PAGOS TARIFA 0% DE IVA, O POR REEMBOLSO EN REALCION DE DEPENDENCIA)</td>
                                        <td align="right">119</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="119">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="7">&nbsp;</td>
                                    </tr>

                                    <tr style="font-weight:bold;font-size:12px;background: #0c4597;color: #ffffff;">
                                        <td colspan="7" style="border-bottom:0px solid black;border-top: 0px solid black;">RESUMEN IMPOSITIVO: AGENTE DE PERCEPCI&Oacute;N DEL IMPUESTO AL VALOR AGREGADO</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">IMPUESTO CAUSADO (Si diferencia campo 499-564 es mayor que 0)</td>
                                        <td align="right">601</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="601">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">CREDITO TRIBUTARIO APLICABLE EN ESTE PERIODO (Si diferencia campo 499-564 es menor que 0)</td>
                                        <td align="right">602</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="602">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">(-)COMPENSACION DE IVA POR VENTAS EFECTUADAS CON MEDIO ELECTRONICO Y/O IVA DEVUELTO O DESCONTADO
                                        POR TRANSACCIONES REALIZADAS CON PERSONAS ADULTAS MAYORES O PERSONAS CON DISCAPACIDAD</td>
                                        <td align="right">603</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="603">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_603" value="0.00"></td>        
                                    </tr>
                                    
                                    <tr>
                                        <td colspan="5">(-) Compensación de IVA por ventas efectuadas en zonas afectadas - Ley de solidaridad</td>
                                        <td align="right">604</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="604">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_604" value="0.00"></td>        
                                    </tr>

                                    <tr>
                                        <td colspan="7">(-)SALDO CR&Eacute;DITO TRIBUTARIO DEL MES ANTERIOR</td>
                                    </tr>

                                    <tr>
                                        <td colspan="5"><span style="margin-left:30px;">POR ADQUISICIONES E IMPORTACIONES (CODIGO 615 DECLARACION PERIODO ANTERIOR)</span></td>
                                        <td align="right">605</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="605">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_605" value="0.00"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5"><span style="margin-left:30px;">POR RETENCIONES FUENTE QUE LE HAN SIDO EFECTUADAS  (CODIGO 617 DECLARACION PERIODO ANTERIOR)</span></td>
                                        <td align="right">606</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="606">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_606" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5"><span style="margin-left:30px;">POR COMPENSACIONES DE IVA POR VENTAS EFECTUADAS CON MEDIO ELECTRONICO  (CODIGO 618 DECLARACION PERIODO ANTERIOR)</span></td>
                                        <td align="right">607</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="607">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_607" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5" style="white-space: nowrap; overflow: hidden;">POR COMPENSACIONES DE IVA POR VENTAS ZONAS AFECTADAS - LEY DE SOLIDADRIDAD, RESTITUCION DE CREDITO TRIBUTARIO EN RESOLUCIONES ADMINISTRATIVA  (CODIGO 619 DECLARACION PERIODO ANTERIOR)</td>
                                        <td align="right">608</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="608">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_608" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">(-)RETENCIONES EN LA FUENTE DE IVA QUE LE HAN SIDO EFECTUADAS EN ESTE PERIODO</td>
                                        <td align="right">609</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="609">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">(-) IVA DEVUELTO/DESCONTADO POR TRANSACCIONES REALIZADAS CON PERSONAS ADULTAS MAYORES O PERSONAS CON DISCAP.</td>
                                        <td align="right">622</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="622">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_622" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">(+)AJUSTE DE IVA DEVUELTO O DESCONTADO POR ADQUISICIONES EFECTUADAS CON MEDIO ELECTRONICO</td>
                                        <td align="right">610</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="610">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_610" value="0.00"></td>
                                    </tr>
                                    
                                    <tr>
                                        <td colspan="5">(+) AJUSTE POR IVA DEVUELTO/DESCONTADO EN ADQUISICIONES EFECTUADAS EN ZONAS AFECTADAS - LEY DE SOLIDARIDAD</td>
                                        <td align="right">611</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="611">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_611" value="0.00"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">(+)AJUSTE POR IVA DEVUELTO E IVA RECHAZADO IMPUTABLE AL CRED. TRIB. EN EL MES (DEV. IVA)</td>
                                        <td align="right">612</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="612">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_612" value="0.00"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">(+)AJUSTE POR IVA DEVUELTO E IVA RECHAZADO IMPUTABLE AL CRED. TRIB. EN EL MES (RF. IVA)</td>
                                        <td align="right">613</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="613">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_613" value="0.00"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">(+)AJUSTE POR IVA DEVUELTO POR OTRAS INST. ESTADO IMPUTABLE CRED. TRIB. EN EL MES </td>
                                        <td align="right">614</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="614">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_614" value="0.00"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7">SALDO CR&Eacute;DITO TRIBUTARIO PARA EL PROXIMO MES</td>
                                    </tr>

                                    <tr>
                                        <td colspan="5"><span style="margin-left:30px;">POR ADQUISICIONES E IMPORTACIONES</span></td>
                                        <td align="right">615</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="615">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5"><span style="margin-left:30px;">POR RETENCIONES FUENTE QUE LE HAN SIDO EFECTUADAS</span></td>
                                        <td align="right">617</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="617">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5"><span style="margin-left:30px;">POR COMPENSACIONES DE IVA POR VENTAS EFECTUADAS CON MEDIO ELECTRONICO</span></td>
                                        <td align="right">618</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="618">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5" style="white-space: nowrap; overflow: hidden;">POR COMPENSACIONES DE IVA POR VENTAS EFECTUADAS EN ZONAS AFECTADAS - LEY DE SOLIDADRIDAD, RESTITUCION DE CREDITO TRIBUTARIO EN RESOLUCIONES ADMINISTRATIVA</td>
                                        <td align="right">619</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="619">0.00</label></td>
                                    </tr>

                                    <tr>
                                        <td colspan="5">Ajuste del crédito tributario de Impuesto al Valor Agregado pagado en adquisiciones locales e importaciones de bienes y servicios superior a cinco (5) años</td>
                                        <td align="right">625</td>
                                        <td align="right" style="border: 1px solid #000000;"><span style="color:#4F85F0;  float: left;" class="glyphicon glyphicon-pencil"></span><label style="cursor: pointer;"  class="lbl" id="625">0.00</label><input type="text" style="display: none" class="input_edit" id="txt_625" value="0.00"></td>        
                                    </tr>

                                    <tr>
                                        <td colspan="5">SUBTOTAL A PAGAR( 601-602-603-604-605-607-608-609+610+611+612+613+614) > 0</td>
                                        <td align="right">620</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="620">0.00</label></td>
                                    </tr>
                                    <tr>
                                    <td colspan="5">IVA PRESUNTIVO DE SALAS DE JUEGO (BINGO MECÁNICO) Y OTROS JUEGOS DE AZAR</td> 
                                    <td align="right">621</td>     
                                    <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="621">0.00</label></td>
                                    </tr>
                                    <tr style="background: #FFCC66;color: #000000;">
                                        <td colspan="5"><u>TOTAL IMPUESTO A PAGAR POR PERCEPCION (620+621)</u></td>
                                        <td align="right">699</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="699">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7">&nbsp;</td>
                                    </tr>

                                    <tr style="font-weight:bold;font-size:12px;background: #0c4597;color: #ffffff;">
                                        <td style="border-bottom:1px solid black;" colspan="3">IMPUESTO A LA SALIDA DE DIVISAS A EFECTOS DE DEVOLUCION A EXPORTADORES HABITUALES DE BIENES</td>
                                        <!--td style="border-bottom:1px solid black;" colspan="2" align="center">VALOR BRUTO</td-->
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">VALOR</td>
                                        <td style="border-bottom:1px solid black;" colspan="2" align="center">ISD PAGADO</td>
                                    </tr>
                                    <tr>
                                        <td style="white-space: nowrap; overflow: hidden;">IMPORT. DE MATERIAS PRIMAS, INSUMOS Y BIENES DE CAPITAL QUE SEAN INCORPORADAS EN PROCESOS PRODUCTIVOS DE BIENES QUE SE EXPORTEN</td>
                                        <td align="right"></td>
                                        <td align="right"></td>
                                        <td align="right">700</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="700">0.00</label></td>
                                        <td align="right">701</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="701">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <!--td style="border-bottom:1px solid black;" colspan="2" align="center">VALOR BRUTO</td-->
                                        <td colspan="2" align="center"></td>
                                        <td style="font-weight:bold;font-size:12px;background: #0c4597;color: #ffffff;" colspan="2" align="center">
                                        PORCENTAJE</td>
                                    </tr>

                                    <tr>
                                        <td colspan="5" style="white-space: nowrap; overflow: hidden;">PROPORCION DEL INGRESO NETO DE DIVISAS DESDE EL EXTERIOR AL ECUADOR, RESPECTO DEL TOTAL DE LAS EXPORTACIONES NETAS DE BIENES</td>
                                        <td align="right">702</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="702">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7">&nbsp;</td>
                                    </tr>

                                    <tr style="font-weight:bold;font-size:12px;background: #0c4597;color: #ffffff;">
                                        <td colspan="7" style="border-bottom:0px solid black;border-top: 0px solid black;">AGENTE DE RETENCI&Oacute;N  DEL IMPUESTO AL VALOR AGREGADO</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">RETENCI&Oacute;N DEL 10%</td>
                                        <td align="right">721</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="721">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">RETENCI&Oacute;N DEL 20%</td>
                                        <td align="right">723</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="723">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">RETENCI&Oacute;N DEL 30%</td>
                                        <td align="right">725</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="725">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">RETENCI&Oacute;N DEL 50%</td>
                                        <td align="right">727</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="727">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5">RETENCI&Oacute;N DEL 70%</td>
                                        <td align="right">729</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="729">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5"> RETENCI&Oacute;N DEL 100%</td>
                                        <td align="right">731</td>
                                        <td align="right" style="border: 1px solid #000000;"><label  class="lbl" id="731">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5"><u>TOTAL IMPUESTO A PAGAR POR RETENCI&Oacute;N (721+723+725+727+729+731)</u></td>
                                        <td align="right">799</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid #000000;"><label  class="lbl" id="799">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5"><u>DEVOLUCION PROVISIONAL DEL IVA MEDIANTE COMPENSACION CON RETENCION EFECTUADAS</u></td>
                                        <td align="right">800</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid #000000;"><label  class="lbl" id="800">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5"><u>RETENCIONES EFECTUADAS Y NO PAGADAS SECTOR PUBLICO, UNIVERSIDADES Y ESCUELAS POLITECNICAS</u></td>
                                        <td align="right">802</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid #000000;"><label  class="lbl" id="802">0.00</label></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr style="background: #FFCC66;color: #000000;">
                                        <td colspan="5"><u>TOTAL IMPUESTO A PAGAR POR RETENCION (799+800+802)</u></td>
                                        <td align="right">801</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="801">0.00</label></td>
                                    </tr>
                                    <tr style="background: #FFCC66;color: #000000;">
                                        <td colspan="5"><u>TOTAL CONSOLIDADO DE IMPUESTO AL VALOR AGREGADO (699+801)</u></td>
                                        <td align="right">859</td>
                                        <td align="right" style="font-weight:bold;border: 1px solid black;"><label  class="lbl" id="859">0.00</label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" align="right"><br /><b>Generado por EXA Software Contable</b></td>
                                    </tr>
                                </table>
                                </div>
                            <div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
        <div style=" display:none">
                
    </div>
    </div>


  
   <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <script>
        $(document).ready(function () {
            function editar_valor(o){
                const $input = $('#txt_'+o);
                const $label = $('#'+o);            

                // Al hacer clic en el label, ocultarlo y mostrar input con focus
                $label.on('click', function () {
                    $input.val($label.text()); // Prellenar input con texto del label
                    $label.hide();
                    $input.show().focus();
                });

                // Validar entrada: solo números (enteros o decimales)
                function esNumeroValido(valor) {
                    return /^-?\d+(\.\d+)?$/.test(valor);
                }

                // Al salir del input
                $input.on('blur', function () {
                    const valor = $input.val().trim();

                    if (valor === '' || !esNumeroValido(valor)) {
                        // No es válido: dejar input visible y marcar error
                        $input.addClass('error').focus().val($label.text());
                    } else {
                        let numero = parseFloat(valor);
                        let numeroFormateado = numero.toFixed(2);

                        // Actualizar label e input con el número formateado
                        $label.text(numeroFormateado).show();
                        $input.removeClass('error').hide();                        
                    }
                    calculos_generales();
                });
            }
            editar_valor('480');editar_valor('481');
            editar_valor('483');editar_valor('484');editar_valor('603');editar_valor('604');editar_valor('605');
            editar_valor('606');editar_valor('607');editar_valor('608');editar_valor('622');editar_valor('625');
            editar_valor('610');editar_valor('611');editar_valor('612');editar_valor('613');editar_valor('614');
            editar_valor('526');editar_valor('527');
          
        });
        function calculos_generales(){
            /* Restar 482 de 484 */
            $('#485').text(($('#482').text()*1-$('#484').text()*1).toFixed(2));

            /*  suma 499 */            
            var codigos_499=[483,484];
            $('#499').text(sumar(codigos_499));
            
            /*  suma 605 */
            //var codigos_605=[606,609];
            //$('#605').text(sumar(codigos_605));

            /*  suma 529 */            
            var codigos_529=[520,521,534,560,522,523,524,524,525,526,527];            
            $('#529').text(sumar(codigos_529));

            /*  suma 564 */            
            var codigos_564=[520,521,534,560,522,523,524,524,525,526,527];
            $('#564').text(sumar(codigos_564)*($('#563').text()*1));    
            //$('#564').text(($('#529').text()*1)*($('#563').text()*1));    
            
            $codigo_601 = (($('#499').text()*1)-($('#564').text()*1));
            $('#601').text($codigo_601<0?0:$codigo_601.toFixed(2));    

            $('#615').text((($('#605').text()*1+$('#602').text()*1)-$('#601').text()*1-$('#625').text()*1<0?0:($('#605').text()*1+$('#602').text()*1)-$('#601').text()*1-$('#625').text()*1).toFixed(2));
            $('#617').text(($('#605').text()*1-$('#601').text()*1<0? ($('#606').text()*1-(($('#605').text()*1-$('#601').text()*1)*-1)+$('#609').text()*1>0?($('#606').text()*1-(($('#605').text()*1-$('#601').text()*1)*-1)+$('#609').text()*1):0):($('#606').text()*1+$('#609').text()*1)).toFixed(2)); 
            var codigo_620=($('#601').text()*1-$('#602').text()*1-$('#603').text()*1-$('#604').text()*1-$('#605').text()*1-$('#606').text()*1-$('#607').text()*1-$('#608').text()*1-$('#609').text()*1+$('#610').text()*1+$('#611').text()*1+$('#612').text()*1+$('#613').text()*1+$('#614').text()*1).toFixed(2);
            $('#620').text(codigo_620<0?0:codigo_620);
            $('#699').text(($('#620').text()*1+$('#621').text()*1));
            $('#859').text(($('#699').text()*1+$('#801').text()*1));
        }
        
        function sumar(lista){
            var suma=0;
            lista.forEach(function(val) {
                let valor = parseFloat($('#'+val).text()*1);
                if (!isNaN(valor)) {
                    suma += valor;
                }
            }); 
            return suma.toFixed(2);
        }

        function generaAsiento(){
            let url="../../contabilidad/FRONT/con_alt_compr_2.0.php?";
            let anio=$('#anio').val();
            let mes=$('#mes').val();
            let ini=anio+'-'+mes+'-01';
            let fin=anio+'-'+mes+'-'+moment(`${anio}-${mes}`, "YYYY-M").daysInMonth();
			// Construimos la URL con parámetros
            let link = url + "&_mes="+mes*1+"&anio="+anio+"&ini="+ini+"&fin="+fin+"&Ren_Cod=T&optest=A&_615="+$('#615').text()+"&_617="+$('#617').text()+"&_606="+$('#606').text()+"&_605="+$('#605').text()+"&_429="+$('#429').text()+"&_529="+$('#529').text()+"&_609="+$('#609').text();
            // Abrimos en una nueva pestaña
            window.open(link, "_blank");
        }
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

        $.fn.getData = function(tipo) {
            var data = this.serializeObject();
            data[tipo] = true;
            return data;
        };
        $.fn.serializeObject = function() {
            var o = {},
                a = this.serializeArray();
            $.each(a, function() {
                if (o[this.name] !== undefined) {
                    if (!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        };

        function generaXml() {
            var data = $("#form1").getData('xml');
            $.get("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", data, function(response) {
                if (response['success'] === true) {
                    $.downloadFile(response['xml_104'], response['name']);
                } else {
                    alert("No se logro generar el Xml!");
                }
            }, 'json').fail(function(error) {
                alert("El Servidor ha fallado en responder!");
            });
        }

        function generaHtml() {
            var data = $("#form1").getData('html');
            //$('#Html104').html('');
            $('#loader').show();
            $.get("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", data, function(response) {
                if (response['success'] === true) {
                    //$('#Html104').html(response['html_104']);
                    //const filas = document.querySelectorAll("#miTabla tbody tr");
                   
                    $.each(response.form, function(id, texto) {
                        if ($('#' + id).is('input')){
                           $('#' + id).val(texto);
                           //$('#' + id).attr('value',texto);
                        }else    
                           $('#' + id).text(texto);                        
                    });

                    //var tablaCopiada = $('#tbl_104').clone();
                    //$('#Html104').html(tablaCopiada);
                   
                } else {
                    alert("No se logro generar el Xml!");
                }
                 $('#loader').hide();
            }, 'json').fail(function(error) {
                alert("El Servidor ha fallado en responder!");
            });
           
        }
        

        function imprimirDiv(idDiv) {
            var contenido = $('#' + idDiv).html();
            var ventana = window.open('','', 'height=600,width=800');
            ventana.document.write('<html><head><title>Imprimir</title></head><body>'+contenido+'</body></html>');            
            ventana.document.close();
            ventana.print();
        }
        //generaHtml();
    </script>
<?Php
   //$obBD_con1->liberar();
   //$obBD_conexion->cerrar();
?>
</body>
</html>