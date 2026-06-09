<?php

/**
 * @abstract Permite
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2016-11-24
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_elect.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

ini_set('max_execution_time', 900);
set_time_limit(0);
$send_mail = true;

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_FacEle($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_FacEle;

$hoy = date("Y-m-d");
$mes = date("m");
$ruta_xmls = $APP_REAL_PATH . "/facturacion/FRONT/$Ses_Emp_Cod/";

if (isset($docsAjax)) {
    $resp = array('success' => true);
    $all = (!isset($type) || empty($type) || $type == 'TODOS');
    $ventas = $all || $type == 'VENTAS' ? $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod . '*Tic_Sri!=4 AND Tic_Sri!=5  AND Tic_Sri!=0'      , $obBD_conexion) : array();
    $notasc = $all || $type == 'NOTASC' ? $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod . '*Tic_Sri=4', $obBD_conexion) : array();
    $retenc = $all || $type == 'RETENC' ? $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion) : array();
    $guiasr = $all || $type == 'GUIAS' ? $obBD_con1->getArrayConsulta(8, $Ses_Emp_Cod, $obBD_conexion) : array();
    $notasd = $all || $type == 'NOTASD' ? $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod . '*Tic_Sri=5', $obBD_conexion) : array();
    $liquidCompra = $all || $type == 'LIQUIDC' ? $obBD_con1->getArrayConsulta(12, $Ses_Emp_Cod . '*Tic_Cod=3', $obBD_conexion) : array();

    $resp['rows'] = array_merge($retenc, array_merge($ventas, array_merge($notasc, array_merge($notasd, $guiasr, $liquidCompra ))));

    foreach ($resp['rows'] as &$r) {
        $xml = $ruta_xmls . $r['Doc_Xml'];
        if (is_readable($xml . "_F.xml"))
            $r['Doc_Fir'] = 'S';
        if ($r['Doc_Aut'] == 'S' || is_readable($xml . "_A.xml")) {
            $r['Doc_Fir'] = 'S';
            $r['Doc_Env'] = 'S';
            $r['Doc_Aut'] = 'S';
        }
        if ($r['Doc_Aut'] == 'S') {
            if (is_readable($xml . ".xml")) unlink($xml . ".xml");
            if (is_readable($xml . "_F.xml")) unlink($xml . "_F.xml");
            //if(is_readable($xml."_A.xml")) unlink($xml."_A.xml");            
        }
    }
    unset($r);
    $obBD_con1->echoJson($resp);
}
$llave = $obBD_con1->getRowConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
$config = $obBD_con1->getRowConsulta(7, $Ses_Emp_Cod, $obBD_conexion);
//$obBD_con1->echoLog($config);
if (isset($autorizaDocs)) {
    $resp = array('success' => true, 'data' => $data);
    require_once('../../Librerias/FactElect/FirmaElectronica.php');
    $DocElect = new FirmaElectronica();
    $DocElect->setProduction(($config['Cof_Fac'] * 1 == 2));
    foreach ($resp['data'] as &$d) {
        $xml = $ruta_xmls . $d['Doc_Xml'];
        $DocElect->setFileSignedPath($xml . '_F.xml');
        //if($d['Doc_Fir']!='S'){
        $d['Doc_Fir'] = 'N';
        if (is_readable($xml . ".xml")) {


            //ChromePhp::log($xml . ".xml", $ruta_xmls . $llave['Lla_Rut'], $llave['Lla_Cla']);

            $doc = $DocElect->sendToSign($xml . ".xml", $ruta_xmls . $llave['Lla_Rut'], $llave['Lla_Cla']);
            //ChromePhp::log($DocElect);
           
            if ($doc['success'] == true && !empty($doc['xml'])) {
                $d['Doc_Fir'] = 'S';
            } else $d['Error'] = 'Error al Firmar el documento!. ' . $doc['message'];


        } else $d['Error'] = "Error no se encontro el <u>XML</u> de $d[Doc_Xml]!";
        //}
        if ($d['Doc_Fir'] == 'S' && $d['Doc_Env'] != 'S') {
            /*$validate=$DocElect->validaXml();
            if($validate['success']==true){*/
            $result = $DocElect->sendToSri();
            //$obBD_con1->echoLog($result);	
            if ($result['success'] == true) {
                $d['Doc_Env'] = 'S';
            } else {
                $d['Error'] = "<span>Error al enviar el documento!<br/>[<i style='color:red;'>$result[message]</i>]" . (!empty($result['informacionAdicional']) ? "<br/>$result[informacionAdicional]</span>" : '');
                if (esErrorSecuencialRegistradoSri($result['message'] . ' ' . (isset($result['informacionAdicional']) ? $result['informacionAdicional'] : ''))) {
                    $d['Doc_VerSri'] = 'S';
                    $d['Doc_Env'] = 'S';
                }
            }
            //}else $d['Error']="<span>Error al enviar el documento!<br/>[<i style='color:red;'>$result[message]</i>]";
        }
        if ($d['Doc_Fir'] == 'S' && $d['Doc_Env'] == 'S' && $d['Doc_Aut'] != 'S') {
            $DocElect->setFileAutorized($xml . '_A.xml');
            $result = $DocElect->autorizarSri($d['Doc_Xml']);
            //$obBD_con1->echoLog($result);
            if ($result['success'] == true) {
                $d['Doc_Aut'] = 'S';
                $d['Selection'] = 'N';
                $d['Error'] = 'Se Autorizó Correctamente!<br/><u class="green">' . $result['numeroAutorizacion'] . '</u>';
                $d['numeroAutorizacion'] = $result['numeroAutorizacion'];
                $obBD_con1->operacionobBD(6, $d, $obBD_conexion);

              //  $sql="UPDATE $Par_Sql[tabla] SET $Par_Sql[campo1]='$Par_Sql[numeroAutorizacion]',$Par_Sql[campo2]='S' WHERE $Par_Sql[cod]=$Par_Sql[Doc_Cod] ;";


                if (is_readable($xml . ".xml")) unlink($xml . ".xml");
                if (is_readable($xml . "_F.xml")) unlink($xml . "_F.xml");

                if ($send_mail == true && $d['tabla'] != 'guias_remis') {
                    $d['Doc_Mail'] = 'N';
                    if (!empty($d['Email']) && trim($d['Email']) != '' && trim($d['Email']) != '-' && trim($d['Email']) != '0') {
                        require_once('../LOGICA/fac_log_electronica.php');
                        //$obBD_elect=($d['tabla']=="retencion"?new Class_Log_Datos_Retencion_Elect:new Class_Log_Datos_Factura_Elect);
                        $obBD_elect = getClassElect($d['Type']);
                        $d['Doc_Mail'] = $obBD_elect->sendMailDoc($d['Doc_Cod'], $d['Email'], NULL, $obBD_conexion, true) == true ? 'S' : 'N';
                        if ($d['Doc_Mail'] == 'N') $d['error'] = "<span>Error al enviar el email!<br/>[<i style='color:blue;'>Se autorizo corectamente pero no se pudo enviar el mail</i>]</span>";
                    } else $d['error'] = "<span>Error al enviar el email!<br/>[<i style='color:blue;'>Se autorizo corectamente pero no se registro ningun email para enviar el documento</i>]</span>";
                }
            } else {
                $d['Error'] = "<span>Error al autorizar el documento!<br/>[<i style='color:red;'>$result[message]</i>]" . (!empty($result['informacionAdicional']) ? "<br/>$result[informacionAdicional]</span>" : '');
                if (esErrorSecuencialRegistradoSri($result['message'] . ' ' . (isset($result['informacionAdicional']) ? $result['informacionAdicional'] : ''))) {
                    $d['Doc_VerSri'] = 'S';
                    $d['Doc_Env'] = 'S';
                }
            }
        }
    }
    unset($d);
    $obBD_con1->echoJson($resp);
}
function esErrorSecuencialRegistradoSri($texto) {
    $texto = strtoupper(strip_tags($texto));
    return (preg_match('/\b45\b\s*:\s*SECUENCIAL\s+REGISTRADO/i', $texto) === 1)
        || (strpos($texto, '45') !== false && strpos($texto, 'SECUENCIAL REGISTRADO') !== false);
}
if (isset($autorizaSriDocs)) {
    require_once('../../Librerias/Xml/XML.php');
    require_once('../../Librerias/FactElect/FirmaElectronica.php');
    $xml_roots = array(
        'VENTAS' => 'factura',
        'NOTASC' => 'notaCredito',
        'NOTASD' => 'notaDebito',
        'RETENC' => 'comprobanteRetencion',
        'GUIAS' => 'guiaRemision',
        'LIQUIDC' => 'liquidacionCompra'
    );
    $resp = array('success' => true, 'data' => $data);
    $DocElect = new FirmaElectronica();
    $DocElect->setProduction(($config['Cof_Fac'] * 1 == 2));
    foreach ($resp['data'] as &$d) {
        try {
            $clave = trim($d['Doc_Xml']);
            $xml_path = $ruta_xmls . $clave;
            $xml_aut_file = $xml_path . '_A.xml';
            if ($d['Doc_Aut'] == 'S' && is_readable($xml_aut_file)) {
                $d['Doc_Fir'] = 'S';
                $d['Doc_Env'] = 'S';
                $d['Error'] = 'El documento ya está autorizado.';
                continue;
            }
            if (empty($clave)) throw new Exception('No se encontró la <u>Clave de Acceso</u> del documento!');

            $xml_aut = null;
            if (is_readable($xml_aut_file)) {
                $xml_aut = XmlDoc::createFromFile($xml_aut_file);
            } else {
                $DocElect->setFileAutorized($xml_aut_file);
                $res = $DocElect->autorizarSri($clave);
                if ($res['success'] != true || empty($res['xml'])) {
                    throw new Exception($res['message'] . (!empty($res['informacionAdicional']) ? '<br/>' . $res['informacionAdicional'] : ''));
                }
                $xml_aut = new XmlDoc((!mb_detect_encoding($res['xml'], 'UTF-8', true)) ? utf8_encode($res['xml']) : $res['xml']);
            }

            if (isset($xml_aut->estado) && trim((string) $xml_aut->estado->text()) != 'AUTORIZADO') {
                throw new Exception('El documento no se encuentra <u>Autorizado</u> en el SRI!');
            }

            $data_xml = (string) $xml_aut->comprobante;
            $xml_doc = new XmlDoc((!mb_detect_encoding($data_xml, 'UTF-8', true)) ? utf8_encode($data_xml) : $data_xml);
            $rootEsperado = isset($d['Type']) && isset($xml_roots[$d['Type']]) ? $xml_roots[$d['Type']] : '';
            if (empty($rootEsperado)) throw new Exception('Tipo de documento no soportado para verificación!');
            if ($xml_doc->getName() != $rootEsperado) {
                throw new Exception('El documento electrónico no es un <u>' . (isset($d['Tipo']) ? $d['Tipo'] : $d['Type']) . '</u>!');
            }

            $numeroAutorizacion = $xml_aut->numeroAutorizacion->text();
            if (empty($numeroAutorizacion)) throw new Exception('No se obtuvo el número de autorización del SRI!');

            $claveXml = $xml_doc->infoTributaria->claveAcceso->text();
            if (trim($claveXml) !== $clave) {
                throw new Exception('La clave de acceso del XML no coincide con el documento seleccionado!');
            }

            if (!is_readable($xml_aut_file) && !empty($res['xml'])) {
                file_put_contents($xml_aut_file, $res['xml']);
            }

            $d['Doc_Aut'] = 'S';
            $d['Doc_Fir'] = 'S';
            $d['Doc_Env'] = 'S';
            $d['Selection'] = 'N';
            $d['Error'] = 'Se Autorizó Correctamente!<br/><u class="green">' . $numeroAutorizacion . '</u>';
            $d['numeroAutorizacion'] = $numeroAutorizacion;
            $obBD_con1->operacionobBD(6, $d, $obBD_conexion);
            if (is_readable($xml_path . '.xml')) unlink($xml_path . '.xml');
            if (is_readable($xml_path . '_F.xml')) unlink($xml_path . '_F.xml');
        } catch (Exception $e) {
            $d['Error'] = "<span>Error al autorizar el documento!<br/>[<i style='color:red;'>" . $e->getMessage() . "</i>]</span>";
        }
    }
    unset($d);
    $obBD_con1->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Autorizaciones Sri [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style>
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Autorizar Documentos Electronicos - <?php echo ($config['Cof_Fac'] * 1 == 2) ? 'PRODUCCIÓN' : 'PRUEBAS'; ?></h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <form id="formDocsSearch">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset ">
                            <legend class="Titulos2">Filtros</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-sm required">Tipo Documento:</label>
                                    <div class="col-xs-9">
                                        <select id="type" name="type" class="form-control input-sm" onchange="setDocs();" required="">
                                            <option value="TODOS">Todos</option>
                                            <option value="VENTAS">Ventas</option>
                                            <option value="NOTASC">Notas de Crédito</option>
                                            <option value="RETENC">Retenciones</option>
                                            <option value="GUIAS">Guias de Remisión</option>
                                            <option value="NOTASD">Notas de Débito</option>
                                            <option value="LIQUIDC">LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset ">
                            <legend class="Titulos2">Llave</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-1 control-label label-sm">Llave:</label>
                                    <div class="col-xs-6">
                                        <span class="form-control input-sm"><?php echo $llave['Lla_Rut']; ?></span>
                                    </div>
                                    <label class="col-xs-1 control-label label-sm">Cad.:</label>
                                    <div class="col-xs-4">
                                        <span class="form-control input-sm"><?php echo $llave['Lla_Cad']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </form>
                <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;">
                    <table id="documentos"></table>
                    <div id="documentosPager"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <!--<button id="btnGuardar" type="button" onclick="autorizarTodo()" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Autorizar Seleccionados</button>-->
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var docs, conteoMsg = 0,
            msg = 0;
        $(function() {
            docs = $('#documentos');
            docs.createGrid({
                caption: 'Documentos Pendientes',
                height: 390,
                grouping: true,
                groupingView: {
                    groupField: ['Tipo'],
                    groupOrder: ['desc']
                },
                colModel: [{
                        label: '<i class="glyphicon glyphicon-check"></i>',
                        name: 'Selection',
                        width: 20,
                        align: "center",
                        formatter: 'checkboxExa',
                        formatoptions: {
                            nullifField: 'Doc_Aut',
                            nullifValue: 'N'
                        },
                        viewable: false
                    },
                    {
                        label: 'Cód. Int.',
                        name: 'Doc_Cod',
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'id',
                        name: 'id',
                        width: 30,
                        align: "center",
                        key: true,
                        hidden: true
                    },
                    {
                        label: 'Tipo',
                        name: 'Tipo',
                        width: 30,
                        align: "center"
                    },
                    {
                        label: 'Fecha',
                        name: 'Doc_Fec',
                        width: 30,
                        align: "center"
                    },
                    {
                        label: 'Numero',
                        name: 'Doc_Num',
                        width: 30,
                        align: "center"
                    },
                    {
                        label: '&nbsp;',
                        name: 'actAutSri',
                        width: 25,
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: consultarAutSriUno,
                            icon: 'refresh',
                            type: 'primary',
                            title: 'Verificacion de Autorizacion en SRI',
                            conditional: function(o) {
                                return o.Doc_Aut !== 'S' && esErrorSecuencialRegistrado(o);
                            }
                        },
                        title: false
                    },
                    {
                        label: 'Archivo',
                        name: 'Doc_Xml',
                        width: 150,
                        formatter: 'docXml',
                        title: false
                    },
                    {
                        label: 'Email',
                        name: 'Email',
                        width: 40
                    },
                    {
                        label: 'Obs.',
                        name: 'Info_Adi',
                        width: 50
                    },
                    //{ label: 'Obs.', name: 'Info_Adi', width: 10, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Info_Adi; }, noMsg:' ', yesIcon:'info-sign purple', noIcon:' ', yesColor:'blue', noText:true }, title:false },                    
                    {
                        label: 'Firmado',
                        name: 'Doc_Fir',
                        width: 20,
                        align: "center",
                        formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: 'Esta Firmado',
                            noMsg: 'Sin Firmar'
                        },
                        title: false
                    },
                    {
                        label: 'Enviado',
                        name: 'Doc_Env',
                        width: 20,
                        align: "center",
                        formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: 'Enviado Al SRI',
                            noMsg: 'No Enviado'
                        },
                        title: false
                    },
                    {
                        label: 'Autorizado',
                        name: 'Doc_Aut',
                        width: 20,
                        align: "center",
                        formatter: 'truefalse',
                        formatoptions: {
                            yesMsg: 'Autorizado',
                            noMsg: 'No Autorizado'
                        },
                        title: false
                    },
                    <?php if ($send_mail) { ?> {
                            label: 'Mail Notificacion',
                            name: 'Doc_Mail',
                            width: 20,
                            align: "center",
                            formatter: 'truefalse',
                            formatoptions: {
                                yesMsg: 'Mail Enviado',
                                noMsg: 'No se Envio Mail'
                            },
                            title: false
                        }
                    <?php } ?>,


                    //{ label: 'Estado', name: 'ErrorMsg', width: 20,  align:"center", formatter:'title', formatoptions:{title:'Error'}, title:false  }, 
                    {
                        label: 'Estado',
                        name: 'ErrorMsg',
                        width: 30,
                        align: "center",
                        formatter: 'title',
                        formatoptions: {
                            title: 'Error'
                        },
                        title: false,
                        autoResizable: true 
                    },

                    {
                        label: 'Tabla',
                        name: 'tabla',
                        hidden: true
                    },
                    {
                        label: 'Campo Sri',
                        name: 'campo1',
                        hidden: true
                    },
                    {
                        label: 'Campo Aut',
                        name: 'campo2',
                        hidden: true
                    },
                    {
                        label: 'Campo Id',
                        name: 'cod',
                        hidden: true
                    },
                    {
                        label: '&nbsp;',
                        name: 'act1',
                        width: 20,
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: autorizarUno,
                            conditional: function(o) {
                                return o.Doc_Aut !== 'S';
                            }
                        },
                        title: false
                    }
                ]
            }, true, '#documentosPager').gridButtonsAdd([{
                    id: "select",
                    caption: 'Seleccionar Todo',
                    buttonicon: 'check',
                    onClickButton: function() {
                        docs.selectAllByComlumn('Selection', 'S', true);
                    }
                },
                {
                    id: "desele",
                    caption: 'Quitar Selección',
                    buttonicon: 'unchecked',
                    onClickButton: function() {
                        docs.selectAllByComlumn('Selection', 'N', true);
                    }
                }
            ]);
            setDocs();
            //$('#select').addClass('ui-state-disabled');
            //$('#desele').addClass('ui-state-disabled');
        });
        $.fn.fmatter.docXml = function(cv, opts, cObjt) {
            return '<div class="other-title" title="' + cv + '" data-originaldata="' + cv + '">&nbsp;<i class="fa fa-file-code-o blue" style="font-size:14px;"></i>&nbsp;&nbsp;&nbsp;' + cv + '.xml</div>';
        };
        $.fn.fmatter.docXml.unformat = function(cv, opts, el) {
            return $(el).find('div').data('originaldata');
        };

        function setDocs() {
            $.getDataJson('', $('#formDocsSearch').getData('docsAjax'), function(r) {
                docs.setRowsByIndex(r['rows'], 'id');
            });
        }

        function limpiarMsgs() {
            $.each(docs.getSeletedByComlumn('Selection', 'S'), function(i, v) {
                docs.changeRow(v[docs[0].p.jsonReader.id], {
                    Error: '',
                    ErrorMsg: ''
                });
            });
        }

        function autorizarTodo() {
            var data = docs.getSeletedByComlumn('Selection', 'S');
            if (data.length === 0) return $.alert('Debe seleccionar al menos un documento!');
            conteoMsg = 0, msg = data.length;
            confirmaAutorizacion(data);
        }

        function autorizarUno(data) {
            docs.changeRow(data[docs[0].p.keyName], {
                Selection: 'S'
            });
            conteoMsg = 0, msg = 1;
            confirmaAutorizacion([$.extend({
                Selection: 'S'
            }, data)]);
        }

        function esErrorSecuencialRegistrado(o) {
            if (o.Doc_VerSri === 'S') return true;
            var errorTxt = o.Error || '';
            if (errorTxt.indexOf('<') !== -1) {
                errorTxt = $('<div>').html(errorTxt).text();
            }
            var msgTxt = o.ErrorMsg || '';
            if (msgTxt.indexOf('<') !== -1) {
                msgTxt = $('<div>').html(msgTxt).text();
            }
            var txt = (errorTxt + ' ' + msgTxt).toUpperCase();
            return (txt.indexOf('45') !== -1 && txt.indexOf('SECUENCIAL REGISTRADO') !== -1);
        }

        function consultarAutSriUno(data) {
            $.createDialogConfirm('¿Desea consultar la autorización en el SRI para este documento?', data, ejecutarAutSriUno);
        }

        function ejecutarAutSriUno(data) {
            docs.changeRow(data[docs[0].p.keyName], {
                ErrorMsg: '<i class="fa fa-spin fa-pulse fa-spinner grey" style="font-size: 15px;">&nbsp;</i>'
            });
            $.saveDataJson('', {
                autorizaSriDocs: true,
                data: [data]
            }, function(re) {
                $.each(re['data'], function(i, v) {
                    v['ErrorMsg'] = '<span style="padding:1px"><i class="glyphicon glyphicon-' + ((v['Doc_Aut'] === 'S') ? 'ok green' : 'remove red') + '" style="font-size: 14px;"></i>' + (v['Error'] || '') + '</span>';
                    docs.changeRow(v[docs[0].p.keyName], v);
                    docs.changeRow(v[docs[0].p.keyName], {
                        actAutSri: ''
                    });
                });
                return false;
            }, function() {
                docs.changeRow(data[docs[0].p.keyName], {
                    ErrorMsg: ''
                });
            }, function() {
                docs.changeRow(data[docs[0].p.keyName], {
                    ErrorMsg: ''
                });
            });
        }

        function confirmaAutorizacion(data) {
            $.createDialogConfirm('¿Está seguro que desea autorizar el(los) documento(s)?', data, autorizarSri);
        }

        function autorizarSri(data) {
            $.each(data, function(i, v) {
                docs.changeRow(v[docs[0].p.jsonReader.id], {
                    ErrorMsg: '<i class="fa fa-spin fa-pulse fa-spinner grey" style="font-size: 15px;">&nbsp;</i>'
                });
                $.saveDataJson('', {
                    autorizaDocs: true,
                    data: [v]
                }, function(re) {
                    $.each(re['data'], function(i, v) {
                        v['ErrorMsg'] = '<span style="padding:1px"><i class="glyphicon glyphicon-' + ((v['Doc_Aut'] === 'S') ? 'ok ' + ($.vv(v['Doc_Mail']) && v['Doc_Mail'] === 'N' ? 'orange' : 'green') : 'remove red') + '" style="font-size: 14px;"></i>'+  ''+ v['Error']+'</span>';
                        if (v['Doc_Aut'] !== 'S' && esErrorSecuencialRegistrado(v)) {
                            v['Doc_VerSri'] = 'S';
                        }
                        docs.changeRow(v[docs[0].p.keyName], v);
                        docs.changeRow(v[docs[0].p.keyName], {
                            act1: '',
                            actAutSri: ''
                        });
                    });
                    return false;
                }, limpiarMsgs, limpiarMsgs, msgFinal);
            });
        }

        function msgFinal() {
            conteoMsg++;
            if (conteoMsg === (msg))
                $.alert('Los Documentos se enviaron para su autorización! <br><span style="color:blue">Recuerda que solo puedes tener tres intentos fallidos despues el SRI, bloqueara la opción durante 24horas </span> <br><br><u style="color:red">NOTA:</u><i> Revise el estado.</i>');
        }
    </script>
</BODY>

</HTML>