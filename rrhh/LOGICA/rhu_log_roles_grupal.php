<?php

/**
 * Logica de las paginas para roles
 *
 * @author Alejandro Camacho
 * @version 1.0
 * Fecha de creacion: 26/05/2024
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("rhu_sql_roles_grupal.php");

class Class_Log_Conexion_Rol extends MysqlConexion {}

class Class_Log_Datos_Rol extends MysqlDatosContab
{
    function __construct()
    {
        $this->setSentencias('sentencias_rol');
    }

    function getGridRol($Map_Cod = NULL, $obBD_conexion = NULL, $formulas = true)
    {
        try {
            $response['success'] = true;
            $response['Map_Cod'] = $Map_Cod;
            // defaults PERSONAL
            $campos = array();
            array_push($campos, array('label' => 'Prs.Cod.', 'name' => 'Prs_Cod', 'hidden' => true, 'width' => 0));
            array_push($campos, array('label' => 'Per.Cod.', 'name' => 'Per_Cod', 'hidden' => true, 'width' => 0));
            array_push($campos, array('label' => 'Con.Cod.', 'name' => 'Con_Cod', 'key' => true, 'hidden' => true, 'width' => 0));
            array_push($campos, array('label' => 'C&Eacute;DULA', 'name' => 'Prs_Ced', 'width' => 90, 'align' => 'center', 'classes' => 'bgNoRight bgNoColor'));
            array_push($campos, array('label' => 'APELLIDOS', 'name' => 'Prs_Ape', 'width' => 80, 'classes' => 'bgNoRight bgNoColor'));
            array_push($campos, array('label' => 'NOMBRES', 'name' => 'Prs_Nom', 'width' => 80, 'classes' => 'bgNoRight bgNoColor'));
            array_push($campos, array('label' => 'CARGO', 'name' => 'Tic_Des', 'width' => 75, 'classes' => 'bgNoRight bgNoColor'));

            $grupos = array(
                'defa' => array('fields' => array()),
                'ingr' => array('head' => array('numberOfColumns' => 0, 'titleText' => 'INGRESOS', 'startColumnName' => NULL), 'fields' => array()),
                'egr' => array('head' => array('numberOfColumns' => 0, 'titleText' => 'EGRESOS', 'startColumnName' => NULL), 'fields' => array())
            );

            $fields = array();
            $map_rol = array();
            if (!empty($Map_Cod)) {
                $fields = $this->getArrayConsulta(7, array('Map_Cod' => $Map_Cod), $obBD_conexion);
                $map_rol = $this->getRowConsulta(8, array('Map_Cod' => $Map_Cod), $obBD_conexion);
            }
            foreach ($fields as $f) {
                if (!empty($f['Cam_Tip']) && ($f['Cam_Tip'] == 'I' || $f['Cam_Tip'] == 'E')) {
                    array_push($grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['fields'], $this->createGridField($f));
                    $grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['head']['numberOfColumns']++;
                    if (empty($grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['head']['startColumnName'])) $grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['head']['startColumnName'] = $f['Cam_Var'];
                }
            }
            foreach ($fields as $f) {
                if ($f['Cam_Tip'] == 'T' || $f['Cam_Tip'] == 'P') array_push($grupos[($f['Cam_Ord'] * 1 === 1 && $f['Cam_Tip'] == 'T' ? 'ingr' : 'egr')]['fields'], $this->createGridField($f));
                if ($f['Cam_Tip'] == 'D') array_push($grupos['defa']['fields'], $this->createGridField($f));
            }
            $campos0 = array_merge($grupos['ingr']['fields'], $grupos['egr']['fields']);
            $campos1 = array_merge($grupos['defa']['fields'], $campos0);
            $rol = array_merge($campos, $campos1);
            array_push($rol, array('label' => '<i class="glyphicon glyphicon-info-sign"></i>', 'labelLong' => 'Anticipos/Descuentos/Prestamos', 'name' => 'anticipos_ant', 'width' => 25, 'formatter' => 'gridButtonInfos', 'align' => 'center', 'classes' => 'bgNoColor', 'viewable' => false));
            if ($formulas) {
                foreach ($campos1 as &$v) {
                    if ($v['Cam_Cal'] == 'S') $v['Cam_For'] = $this->getFormula($v['Cam_Cod'], NULL, NULL, $obBD_conexion);
                }
                unset($v);
            }
            $response['rol_config'] = $map_rol;
            $response['rol'] = $campos1;
            $response['grid'] = array('sortname' => 'Prs_Ape', 'caption' => (!empty($Map_Cod) ? $map_rol['Map_Des'] : null), 'headertitles' => true, 'colModel' => $rol, 'footerrow' => true, 'bindKeys' => false);
            $response['header'] = array('useColSpanStyle' => true, 'groupHeaders' => array());
            array_push($response['header']['groupHeaders'], array('numberOfColumns' => 2, 'titleText' => 'PERSONAL', 'startColumnName' => 'Prs_Ape'));
            array_push($response['header']['groupHeaders'], $grupos['ingr']['head']);
            array_push($response['header']['groupHeaders'], $grupos['egr']['head']);
            utf8_encode_deep($response);
            return $response;
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'No se pudo obtener la plantilla del rol de pagos!', 'error' => $e);
        }
    }
    function createGridField($f)
    {
        return array_merge(array(
            'label' => $f['Cam_Dec'],
            'labelLong' => $f['Cam_Des'],
            'name' => $f['Cam_Var'],
            'width' => ($f['Cam_Vis'] == 'N' ? 0 : 50),
            'hidden' => ($f['Cam_Vis'] == 'N' ? true : false),
            'editable' => ($f['Cam_Req'] == 'S' ? true : false),
            'title' => ($f['Cam_Req'] == 'S' ? false : true),
            'editoptions' => ($f['Cam_Req'] == 'S' ? array('dataInit' => ($f['Cam_Var'] != 'dias') ? 'styleInput' : 'diasInput') : NULL),
            'formatter' => $f['Cam_Tip'] != 'T' ? $f['Cam_Var'] != 'dias' && !endsWith($f['Cam_Var'], '_acum') && !endsWith($f['Cam_Var'], '_anio') ? ($f['Cam_Req'] == 'S' ? 'number' : 'numeric') : 'interger' : 'currency',
            'align' => 'right',
            'classes' => $f['Cam_Tip'] == 'D' ? 'bgNoColor' : ($f['Cam_Sum'] == 'N' && $f['Cam_Tip'] != 'T' ? 'bgNoRight bgNoColor' : $f['Cam_Tip'] == 'T' ? ($f['Cam_Var'] == 'total_rol' ? 'columnHighlight3' : 'columnHighlight1') : ''),
            'summaryRound' => 2,
            'summaryRoundType' => 'round',
            'viewable' => ($f['Cam_Vis'] == 'S' || $f['Cam_Tip'] == 'P'),
            'editrules' => array('edithidden' => true)
        ), $f);
    }

    function getFormula($Cam_Cod, $Ogr_Cod, $ord, $obBD_conexion)
    {
        $data = array('Cam_Cod' => $Cam_Cod, 'Ogr_Cod' => $Ogr_Cod, 'Ogr_Ord' => $ord);
        $group = $this->getRowConsulta(5, $data, $obBD_conexion);
        if (empty($group)) return null;
        $formula = array('operator' => $group['Ogr_Opr']);
        $formula['operand1'] = $this->getItem($group['Ogr_Cod'], '1', $obBD_conexion);
        $formula['operand2'] = $this->getItem($group['Ogr_Cod'], '2', $obBD_conexion);
        return $formula;
    }
    function getItem($Ogr_Cod, $ord, $obBD_conexion)
    {
        $item = $this->getRowConsulta(6, array('Ogr_Cod' => $Ogr_Cod, 'Oit_Ord' => $ord), $obBD_conexion);
        if (!empty($item)) return array(
            'type' => ($item['Oit_Tip'] == 'i' ? 'item' : 'unit'),
            'value' => ($item['Oit_Tip'] == 'i' ? empty($item['Oit_Val']) ? 0 : $item['Oit_Val'] : $item['Oit_Val']),
            'text' => ($item['Oit_Tip'] == 'i' ? "\{$item[Oit_Var]\}" : null),
            'variable' => ($item['Oit_Tip'] == 'i' ? $item['Oit_Var'] : null)
        );
        else return $this->getFormula(null, $Ogr_Cod, $ord, $obBD_conexion);
    }

    function getListRoles($datos = null, $obBD_conexion = null, $print = true, $total = false)
    {
        try {
            if ($print) {
                $in = "{";
                $fn = "}";
            } else {
                $in = '';
                $fn = '';
            }
            $resp = array();
           // //ChromePhp::log("TOTALES DE CMPO $ total:*********** ".$total);
            if ($total) $datos['totales'] = true;
            //ChromePhp::log( "Rol_Cod: ".$datos['Rol_Cod']. "    Are_Cod: ".$datos['Are_Cod']."  Totales: ".$datos['totales']." Map_Cod :".$datos['Map_Cod']." Rol_F: ".$datos['Rol_F']." Rol_I :".$datos['Rol_I']."  ");
            $campos = $this->getArrayConsulta(171, $datos, $obBD_conexion);
            //ChromePhp::log("Cam_Cod: ".$campos["Rol_Val"]);
            foreach ($campos as $c) {
                $add = true;
                foreach ($resp as &$r) {
                    if ($r['Con_Cod'] == $c['Con_Cod']) {
                        $r[$in . $c['Cam_Var'] . $fn] = $c['Cam_Var'] != 'dias' ? formato_numero($c['Rol_Val'], 2, 1) : formato_numero($c['Rol_Val'], 0, 1);
                        $add = false;
                        break;
                    }
                }
                unset($r);
                if ($add == true) array_push($resp, array('Rol_Cod' => $c['Rol_Cod'], 'Con_Cod' => $c['Con_Cod'], $in . $c['Cam_Var'] . $fn => $c['Cam_Var'] != 'dias' ? formato_numero($c['Rol_Val'], 2, 1) : formato_numero($c['Rol_Val'], 0, 1)));
            }

            foreach ($resp as $k => &$r) {
                $contrato = $this->getRowConsulta(9, array('Con_Cod' => $r['Con_Cod']), $obBD_conexion);
                $Prs_Abr = explode(' ', $contrato['Prs_Ape']);
                $contrato['Prs_Abr'] = $Prs_Abr[0] . ' ' . $contrato['Prs_Nom'][0] . '.';
                $Prs_Ape[$k] = $contrato['Prs_Ape'];
                $Prs_Nom[$k] = $contrato['Prs_Nom'];
                foreach ($contrato as $k => $v) if (!isset($r[$in . $k . $fn])) $r[$in . $k . $fn] = $v;
                if (empty($r[$in . 'aporte_extras_rol_p' . $fn])) $r[$in . 'aporte_extras_rol_p' . $fn] = 0;
            }
            unset($r);
            $ident = (count($resp) > 1);
            if ($ident) array_multisort($Prs_Ape, SORT_ASC, $Prs_Nom, SORT_ASC, $resp);
            foreach ($resp as $k => &$r) {
                $r[$in . 'Rol_i' . $fn] = $k + 1;
                if ($ident) $r[$in . 'Prs_Abr' . $fn] = ($k + 1) . '.- ' . $r[$in . 'Prs_Abr' . $fn];
            }
            unset($r);
            return (isset($datos['Row']) && isset($resp[0]) ? $resp[0] : $resp);
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'No se pudo obtener los roles de pagos!', 'error' => $e);
        }
    }
}
?>