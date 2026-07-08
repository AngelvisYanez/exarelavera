<?php

/**
 * @abstract Reporte del libro diario
 * @version 1.0
 * Fecha 2018-05-30
 * @author Erik Niebla
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_liquidacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

if (!isset($Lib_Cod)) {
    echo '<br/><br/><br/>' . error_alerta("No se ingreso ningun codigo de liquidacion", 3);
    exit();
}
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Liquidacion;

$hoy = date("Y-m-d");

$detalle = array('ingresos' => array(), 'descuentos' => array());
$empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('empresas.Emp_Cod' => $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
$liquidacion = $obBD_con1->getRowConsulta('liquidacion_bana.selectWhere', array('liquidacion_bana.Lib_Cod' => $Lib_Cod), $obBD_conexion);
$productor = $obBD_con1->getRowConsulta('productor_bana.selectWhere', array('productor_bana.Prd_Cod' => $liquidacion['Prd_Cod']), $obBD_conexion);
$ingresos = $obBD_con1->getArrayConsulta('liquidacion_bana_det.selectWhere', array('liquidacion_bana_det.Lib_Cod' => $Lib_Cod, 'Lid_Tip' => 'I'), $obBD_conexion, true);
$descuentos = $obBD_con1->getArrayConsulta('liquidacion_bana_det.selectWhere', array('liquidacion_bana_det.Lib_Cod' => $Lib_Cod, 'Lid_Tip' => 'D'), $obBD_conexion, true);

$gruposIng = $obBD_con1->getGrpIngresos();
$gruposDes = $obBD_con1->getGrpDescuentos();
$tipo = $liquidacion['Lib_Mag'];
//$detallado=true;
if (isset($gerencial)) {
    $detalle['ingresos'] = $ingresos;
    $detalle['descuentos'] = $descuentos;
    if ($tipo == 'N') {
        foreach ($detalle['ingresos'] as &$ing) {
            if ($ing['Lid_Grp'] * 1 == -1) {
                $ing['Lid_Des'] = "RACIMOS BANANO";
            }
        }
        unset($ing);
    }
} else if (isset($detallado)) {
    foreach ($ingresos as $ing) {
        $add = true;
        if ($ing['Lid_Grp'] * 1 == 2) {
            array_push($detalle['ingresos'], $ing);
        }
    }
    //var_dump($descuentos);
    foreach ($descuentos as $desc) {
        if ($desc['Lid_Grp'] * 1 == 2) {
            array_push($detalle['descuentos'], $desc);
        }
    }
} else {
    foreach ($ingresos as $ing) {
        $add = true;
        if ($ing['Lid_Grp'] * 1 != 2) {
            /*if($ing['Lid_Grp']*1!=-1){
                $ing['Lid_Des']=$ing['Lid_Can']=$ing['Lid_Pru']='';
                foreach ($detalle['ingresos'] as &$val){
                    if($ing['Lid_Grp']==$val['Lid_Grp']){
                        $val['Lid_Imp']+=($ing['Lid_Imp']*1);
                        $add=false;
                        break;
                    } unset($val);
                }
            }*/
            if ($add) {
                array_push($detalle['ingresos'], $ing);
            }
        }
    }
    if ($tipo == 'N') {
        $index = array();
        $aux = array(
            'Lid_Can' => 0,
            'Lid_Des' => "RACIMOS BANANO",
            'Lid_Grp' => -1,
            'Lid_Imp' => 0,
            'Lid_Int' => 1,
            'Lid_Pru' => $liquidacion['Lib_Pra'] * 1,
            'Lid_Tip' => "I"
        );
        $first = -1;
        foreach ($detalle['ingresos'] as $i => $ing) {
            if ($ing['Lid_Grp'] * 1 == -1) {
                if ($first == -1) {
                    $first = '' . $i;

                    $aux['Lid_Imp'] += ($ing['Lid_Imp'] * 1);
                    $detalle['ingresos'][$first] = $aux;
                } else {
                    array_push($index, $i);
                    $detalle['ingresos'][$first]['Lid_Imp'] += ($ing['Lid_Imp'] * 1);
                }
            }
        }
        foreach ($index as $v) {
            unset($detalle['ingresos'][$v]);
        }
        $detalle['ingresos'][$first]['Lid_Can'] = round($detalle['ingresos'][$first]['Lid_Imp'] / $detalle['ingresos'][$first]['Lid_Pru'], 4);
    }
    //var_dump($descuentos);
    /*foreach ($descuentos as $desc){
        $add=true;

        if($desc['Lid_Grp']*1!=2){
        //if($desc['Lid_Grp']!=-1){
            $desc['Lid_Des']=$desc['Lid_Can']=$desc['Lid_Pru']='';
            foreach ($detalle['descuentos'] as &$val){
                if($desc['Lid_Grp']==$val['Lid_Grp']){
                    $val['Lid_Imp']+=($desc['Lid_Imp']*1);
                    $add=false;
                    break;
                } unset($val);
            }

        //}else{
        //    $desc['Lid_Des']= formato_numero($desc['Lid_Pru'],2,1)."%&nbsp;&nbsp;&nbsp;".$desc['Lid_Des']; $desc['Lid_Pru']='';
        //}
        if($add) array_push ($detalle['descuentos'], $desc);
        }

    }*/
    foreach ($descuentos as $desc) {
        $add = true;

        if ($desc['Lid_Grp'] * 1 != 2) {
            if ($desc['Lid_Grp'] != -1) {
            } else {
                $desc['Lid_Des'] = formato_numero($desc['Lid_Pru'], 2, 1) . "%&nbsp;&nbsp;&nbsp;" . $desc['Lid_Des'];
                $desc['Lid_Pru'] = '';
            }
            if ($add) array_push($detalle['descuentos'], $desc);
        }
    }
    //var_dump($detalle['descuentos']);
}

?>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
</HEAD>

<BODY>
    <style type="text/css">
        .linea {
            border-bottom: 1px solid black;
            border-top: 1px solid black;
            border-collapse: collapse;
        }

        .linea2 {
            border-top: 1px solid black;
            border-collapse: collapse;
        }

        .titulo {
            /*font-family: Verdana, Geneva, sans-serif;*/
            font-family: Tahoma, Verdana, Segoe, sans-serif;
            font-size: 14px;

        }

        .contenido {
            /*font-family:Verdana, Geneva, sans-serif;*/
            font-family: Tahoma, Verdana, Segoe, sans-serif;
            font-size: 11px;
        }

        table {
            font-family: Monospace !important;
            font-size: 13px !important;
        }
    </style>
    <style>
        .bold {
            font-weight: normal;
        }

        .bold2 {
            font-weight: bold;
        }

        .rep {
            font-size: 12px;
        }

        .font11 {
            font-size: 11px;
        }

        .noBorder {
            border: 0;
        }

        .grid {
            width: 31%;
            float: left;
            padding: 5px;
            border: 1px solid;
            border-radius: 5px;
        }

        .grid:not(:last-child) {
            width: 31.5%;
            margin-right: 1%;
        }

        .rep td {
            padding: 1px 6px;
        }
    </style>
    <div style="width: 700px;margin-top: -5px;" class="contenido">
        <?php /*if(!isset($detallado))
                echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'LIQUIDACION DE COMPRA DE FRUTA', (isset($detallado)?"DETALLADO ":'').'SEMANA No.'.$liquidacion['Lib_Sem'], $obBD_conexion);
              else */
        if ($Ses_Emp_Cod == 56) {
            echo '<div class="titulo" style="text-align: center;">LIQUIDACION DE COMPRA DE FRUTA</div><div class="titulo" style="text-align: center;">SEMANA No.' . $liquidacion['Lib_Sem'] . '</div>';
        } else {
            echo '<div class="bold1 titulo" style="text-align: center;">' . $empresa['Emp_Nom'] . '</div><div class="titulo" style="text-align: center;">LIQUIDACION DE COMPRA DE FRUTA</div><div class="titulo" style="text-align: center;">SEMANA No.' . $liquidacion['Lib_Sem'] . '</div>';
        } ?>
        <table style="width: 100%;font-size: 12px !important;" class="rep">
            <tr style="height: 0;">
                <td style="width: 7%;"></td>
                <td style="width: 23%;"></td>
                <td style="width: 10%;"></td>
                <td style="width: 60%;"></td>
            </tr>
            <tr>
                <td class='bold'>NUM.:</td>
                <td><?php echo ($liquidacion['Lib_Mag'] == 'N' ? 'F' : '') . $liquidacion['Lib_Num']; ?></td>
                <td class='bold'>FECHA:</td>
                <td><?php echo $liquidacion['Lib_Fec']; ?></td>
            </tr>
            <tr>
                <td class='bold'>RUC:</td>
                <td><?php echo $productor['Prs_Ced']; ?></td>
                <td class='bold'>PRODUCTOR:</td>
                <td><?php echo $productor['Productor']; ?></td>
            </tr>
            <?php if ($tipo == 'S') { ?> <tr>
                    <td class='bold'>MARCA:</td>
                    <td colspan="3"><?php echo $liquidacion['Bam_Nom']; ?></td>
                </tr> <?php } ?>
        </table>
        <table style="width: 100%;border-collapse: collapse;" cellpadding="5" border="1" class="rep">
            <thead>
                <tr>
                    <th align="center" colspan="5">INGRESOS</th>
                </tr>
                <tr>
                    <th style="width:20%;">Concepto</th>
                    <th style="width:40%;">Descrip</th>
                    <th style="width:13%;">Cantidad</th>
                    <th style="width:12%;">Unitario</th>
                    <th style="width:15%;">Total</th>
                </tr>
            </thead>
            <tbody class='noBorder'>
                <?php $totIng = 0;
                if ($tipo == 'N') $gruposIng[-1] = 'Racimo Fruta';
                foreach ($detalle['ingresos'] as $ing) {
                    $totIng += ($ing['Lid_Imp'] * 1);
                    //if(isset($detallado))
                    if (($ing['Lid_Imp'] * 1) > 0)
                        echo "<tr><td class='font11'>{$gruposIng[$ing['Lid_Grp']]}</td><td>{$ing['Lid_Des']}</td><td align='right'>{$ing['Lid_Can']}</td><td align='right'>{$ing['Lid_Pru']}</td><td align='right'>" . formato_numero($ing['Lid_Imp'], 2, 1) . "</td></tr>";
                    //else
                    //echo "<tr><td>{$gruposIng[$ing['Lid_Grp']]}</td><td>{$ing['Lid_Des']}</td><td align='right'>{$ing['Lid_Can']}</td><td align='right'>{$ing['Lid_Pru']}</td><td align='right'>{$ing['Lid_Imp']}</td></tr>";
                } ?>
            </tbody>
            <tbody class='noBorder'>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2" align="right" class="" style=" border-top: 1px solid;">TOTAL INGRESOS:</td>
                    <td align='right' style=" border-top: 1px solid;"><?php echo formato_numero($totIng, 2, 1); ?></td>
                </tr>
            </tbody>
        </table>
        <div style="height: 10px;"></div>
        <table style="width: 100%;border-collapse: collapse;" border="1" class="rep">
            <thead>
                <tr>
                    <th align="center" colspan="5">DESCUENTOS</th>
                </tr>
                <tr>
                    <th style="width:22%;">Concepto</th>
                    <th style="width:40%;">Descrip</th>
                    <th style="width:13%;">Cantidad</th>
                    <th style="width:12%;">Unitario</th>
                    <th style="width:15%;">Total</th>
                </tr>
            </thead>
            <tbody class='noBorder'>
                <?php $totDes = 0;
                foreach ($detalle['descuentos'] as $desc) {
                    $totDes += ($desc['Lid_Imp'] * 1);
                    if (($desc['Lid_Imp'] * 1) > 0)
                        echo "<tr><td class='font11' style=''>{$gruposDes[$desc['Lid_Grp']]}</td><td>" . (isset($detallado) ? ($desc['Lid_Grp'] == -1 ? formato_numero($desc['Lid_Pru'], 2, 1) . "%&nbsp;&nbsp;&nbsp;{$desc['Lid_Des']}" : "{$desc['Lid_Des']}") : $desc['Lid_Des']) . "</td><td align='right'>{$desc['Lid_Can']}</td><td align='right'>" . (/*isset($deatallado)*/true ? ($desc['Lid_Grp'] != -1 ? $desc['Lid_Pru'] : '') : '') . "</td><td align='right'>{$desc['Lid_Imp']}</td></tr>";
                } ?>
            </tbody>
            <tbody class='noBorder'>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2" align="right" class="" style=" border-top: 1px solid;">TOTAL DESCUENTOS:</td>
                    <td align='right' style=" border-top: 1px solid;"><?php echo formato_numero($totDes, 2, 1); ?></td>
                </tr>
                <!--<?php if (!isset($detallado)) { ?><tr><td colspan="5" style="font-size: 10px; border: 1px solid;">Descuentos permitidos por Reglamento a la Ley del Banano seg&uacute;n decreto ejecutivo Nro. 374 el 7 de Mayo de 2003</td></tr><?php } ?>-->
            </tbody>
        </table>
        <table style="width: 100%;border-collapse: collapse;" class="rep">
            <tbody class='noBorder'>
                <tr>
                    <td style="width:60%;">&nbsp;</td>
                    <td style="width:15%;" align="right" class=''>NETO A PAGAR:</td>
                    <td style="width:15%; border-bottom: 4px double;" align="right" class=''><?php echo formato_numero($totIng - $totDes, 2, 1); ?></td>
                </tr>
            </tbody>
        </table>
        <?php if (!isset($detallado)) { ?>
            <?php
            $tarjas_sum = $obBD_con1->getRowConsulta('productor_tarja.sql.getSumatoriasByLib', array('Lib_Cod' => $Lib_Cod), $obBD_conexion);
            $haciendas = $obBD_con1->getArrayConsulta('productor_tarja.sql.getHaciendasByLib', array('Lib_Cod' => $Lib_Cod), $obBD_conexion);
            $contenedores = $obBD_con1->getArrayConsulta('productor_tarja.sql.getContenedoresByLib2', array('Lib_Cod' => $Lib_Cod), $obBD_conexion);
            if (empty($contenedores)) $contenedores = $obBD_con1->getArrayConsulta('productor_tarja.sql.getContenedoresByLib', array('Lib_Cod' => $Lib_Cod), $obBD_conexion);
            ?>
            <div style="height: 5px;"></div>
            <div style="font-size: 11px !important;;">
                <div class="grid" style="width: 20%;">
                    <table style="width: 100%;border-collapse: collapse;font-size: 11px !important;">
                        <tr>
                            <td style="width: 70%"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="bold">C. Declaradas:</td>
                            <td align='right'><?php echo formato_numero($tarjas_sum['Prt_Cad'], 0, 1) ?></td>
                        </tr>
                        <tr>
                            <td class="bold">C. Recibidas:</td>
                            <td align='right'><?php echo formato_numero($tarjas_sum['Prt_Car'], 0, 1) ?></td>
                        </tr>
                        <tr>
                            <td class="bold">C. Rechazadas:</td>
                            <td align='right'><?php echo formato_numero($tarjas_sum['Prt_Cah'], 0, 1) ?></td>
                        </tr>
                        <tr>
                            <td class="bold">C. Faltantes:</td>
                            <td align='right'><?php echo formato_numero($tarjas_sum['Prt_Caf'], 0, 1) ?></td>
                        </tr>
                        <tr>
                            <td class="bold">C. Caidas:</td>
                            <td align='right'><?php echo formato_numero($tarjas_sum['Prt_Caj'], 0, 1) ?></td>
                        </tr>
                        </tr>
                    </table>
                </div>
                <div class="grid" style="width: 42%; <?php if (count($haciendas) == 0 && count($contenedores) == 0) echo 'border:0;'; ?>">
                    <table style="width: 100%;border-collapse: collapse;font-size: 11px !important;">
                        <?php
                        //if(count($haciendas)>0||count($contenedores)>0)
                        //    echo '<tr><td class="bold" style="width: 55%">NOMBRE</td><td class="bold" style="width: 25%">COD.</td><td class="bold" align="right" style="width: 20%">CANT</td></tr>';
                        if (count($haciendas) > 0) {
                            //    echo "<tr><td class='bold' colspan='3' align='center'>HACIENDAS</td></tr>";
                            echo '<tr><td class="bold2" style="width: 55%">HACIENDA</td><td class="bold2" style="width: 25%">COD.</td><td class="bold2" align="right" style="width: 20%">CANT</td></tr>';
                            foreach ($haciendas as $hac) {
                                echo "<tr><td>{$hac['Prh_Nom']}</td><td>{$hac['Prh_Mag']}</td><td align='right'>{$hac['Prt_Car']}</td></tr>";
                            }
                        }
                        if (count($contenedores) > 0) {
                            //    echo "<tr><td class='bold' colspan='3' align='center'>CONTENEDORES</td></tr>";
                            echo '<tr><td class="bold2" style="width: 55%">NAVE</td><td class="bold2" style="width: 25%">CONTENEDOR</td><td class="bold2" align="right" style="width: 20%">CANT</td></tr>';
                            foreach ($contenedores as $cont) {
                                echo "<tr><td>{$cont['Nave']}</td><td>{$cont['Contenedor']}</td><td align='right'>{$cont['Prt_Car']}</td></tr>";
                            }
                        } ?>
                    </table>
                </div>
                <div class="grid" style="width: 30%; font-size: 10px !important;; text-align: justify;"><span class="bold">CONSTANCIA:</span> Declaro bajo juramento que los valores arriba indicados en la presente liquidaci&oacute;n son correctos, y me han sido cancelados oportunamente por la empresa, y en cuanto a los descuentos, estos han sido autorizados por mi persona por originarse en la negociaci&oacute;n.</div>
            </div>
        <?php } ?>
        <div style="height: 5px; clear: both;"></div>
        <table style="width: 100%; border: 1px solid;" class="rep">
            <tr>
                <td style="width: 12%;"></td>
                <td></td>
            </tr>
            <?php if (!isset($detallado) && !isset($gerencial)) { ?>
                <tr>
                    <td class="bold">SON:</td>
                    <td><?php echo ucwords(num2letras(formato_numero($totIng - $totDes, 2, 1))) ?></td>
                </tr>
            <?php } else { ?>
                <tr>
                    <td class="bold">OBSERVACION:</td>
                    <td><?php echo $liquidacion['Lib_Obs']; ?></td>
                </tr>
            <?php } ?>
        </table>
        <?php /*if(!isset($detallado))*/ { ?>
            <table style="width: 100%;font-size: 12px;border-collapse: collapse; border-top: 0;" border="1">
                <tr>
                    <td align='center' style="width: 25%;">ELABORADO</td>
                    <td align='center' style="width: 25%;">REVISADO</td>
                    <td align='center' style="width: 25%;">APROBADO</td>
                    <td align='center' style="width: 25%;">RECIB&Iacute; CONFORME</td>
                </tr>
                <tr style="height: 40px;">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>
        <?php } ?>
        <div style="font-size:9px;">
            <?php echo mb_convert_encoding($obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion), 'UTF-8', 'ISO-8859-1'); ?>
        </div>
    </div>
</BODY>

</HTML>
<?Php
/* Cierra las conexiones */
$obBD_conexion->cerrar();
