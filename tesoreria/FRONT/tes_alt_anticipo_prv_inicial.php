<?php
/**
 * Anticipos a Proveedores Iniciales (lote)
 * Comprobante ficticio Com_Est=E + asientos ficticios.
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anticipo_prv.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Ant_Prv($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Ant_Prv;

if (isset($proveedoresAjax)) {
    $obBD_con1->getPageGridJson(1, $_GET, $obBD_conexion, false);
}

if (isset($cuentaAnticipoAjax)) {
    $data = $obBD_con1->getRowConsulta(3, '', $obBD_conexion);
    $obBD_con1->echoJson(array(
        'success' => !empty($data['Pld_Cod']),
        'data' => $data,
        'message' => empty($data['Pld_Cod'])
            ? 'No existe cuenta parametrizada para ANTICIPOS A PROVEEDORES (Tpa_Abr=ANP).'
            : ''
    ));
}

if (isset($obtenerPeriodoMinMax)) {
    $resp = array('success' => true, 'data' => $obBD_con1->getRowConsulta(11, '', $obBD_conexion));
    $obBD_con1->echoJson($resp);
}

/* Tipo de pago Inicial (Pag_Cod) */
$tipoPagoInicial = $obBD_con1->getRowConsulta(43, '', $obBD_conexion);
if (!is_array($tipoPagoInicial)) {
    $tipoPagoInicial = array();
}

/**
 * Descarga plantilla XLSX (columna RUC/CI como texto).
 */
if (isset($descargarPlantillaAnticiposIni)) {
    $bin = aini_generar_plantilla_xlsx();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="plantilla_anticipos_iniciales.xlsx"');
    header('Content-Length: ' . strlen($bin));
    header('Cache-Control: max-age=0');
    echo $bin;
    exit;
}

/**
 * Procesa archivo Excel/CSV y devuelve filas para el grid.
 */
if (isset($cargarPlantillaAnticiposIni)) {
    $response = array('success' => false, 'message' => 'No se pudo procesar la plantilla.', 'rows' => array());

    if (empty($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
        $response['message'] = 'Debe seleccionar un archivo Excel o CSV.';
        $obBD_con1->echoJson($response);
    }

    $Pag_Cod = isset($Pag_Cod) ? (int)$Pag_Cod : 0;
    $Pag_Des = isset($Pag_Des) ? trim($Pag_Des) : 'Inicial';
    if ($Pag_Cod <= 0 && !empty($tipoPagoInicial['Pag_Cod'])) {
        $Pag_Cod = (int)$tipoPagoInicial['Pag_Cod'];
        $Pag_Des = $tipoPagoInicial['Pag_Abr'] . ' - ' . $tipoPagoInicial['Pag_Des'];
    }

    $vendor = realpath(__DIR__ . '/vendor/SpreadsheetReader.php');
    if (!$vendor || !is_readable($vendor)) {
        $response['message'] = 'No se encontro el lector de planillas (SpreadsheetReader).';
        $obBD_con1->echoJson($response);
    }
    require_once($vendor);

    $tmp = $_FILES['archivo']['tmp_name'];
    $orig = $_FILES['archivo']['name'];
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $readPath = $tmp;
    $tempCopy = false;
    if ($ext === 'xlsx' || $ext === 'xls' || $ext === 'csv') {
        $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aini_' . uniqid('', true) . '.' . $ext;
        if (@copy($tmp, $dest)) {
            $readPath = $dest;
            $tempCopy = true;
        }
    }

    try {
        $reader = new SpreadsheetReader($readPath, $orig);
        $map = null;
        $rowsOut = array();
        $stats = array('total' => 0, 'validos' => 0, 'alertas' => 0);
        $prvUsados = array();

        foreach ($reader as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cells = array();
            foreach ($row as $c) {
                $cells[] = is_scalar($c) ? trim((string)$c) : '';
            }
            while (count($cells) > 0 && $cells[count($cells) - 1] === '') {
                array_pop($cells);
            }
            if (count($cells) === 0) {
                continue;
            }

            if ($map === null) {
                $tryMap = aini_map_cols_plantilla($cells);
                if ($tryMap !== null) {
                    $map = $tryMap;
                    continue;
                }
                if (aini_es_fila_encabezado_plantilla($cells)) {
                    continue;
                }
                $fechaTmp = aini_parse_fecha_plantilla(isset($cells[0]) ? $cells[0] : '');
                $cedTmp = aini_norm_ced_plantilla(isset($cells[1]) ? $cells[1] : '');
                $valorTmp = (float)str_replace(array(',', ' '), array('', ''), isset($cells[3]) ? $cells[3] : '');
                if (aini_fila_sin_datos_utiles($fechaTmp, $cedTmp, $valorTmp)) {
                    continue;
                }
                $map = array('fecha' => 0, 'ced' => 1, 'nombre' => 2, 'valor' => 3);
            }

            $fecha = aini_parse_fecha_plantilla(isset($cells[$map['fecha']]) ? $cells[$map['fecha']] : '');
            $ced = aini_norm_ced_plantilla(isset($cells[$map['ced']]) ? $cells[$map['ced']] : '');
            $nombre = isset($cells[$map['nombre']]) ? trim($cells[$map['nombre']]) : '';
            $valorRaw = isset($cells[$map['valor']]) ? $cells[$map['valor']] : '';
            $valor = (float)str_replace(array(',', ' '), array('', ''), $valorRaw);

            if (aini_fila_sin_datos_utiles($fecha, $ced, $valor)) {
                continue;
            }

            if (aini_es_fila_encabezado_plantilla($cells)) {
                continue;
            }

            $stats['total']++;
            $alerta = '';
            $prv = null;

            if ($fecha === '' || $ced === '' || $nombre === '' || $valor <= 0) {
                $alerta = 'Datos incompletos (fecha, RUC/CI, nombre y valor obligatorios).';
            } else {
                $prv = $obBD_con1->getRowConsulta(44, array('Prs_Ced' => $ced), $obBD_conexion);
                if (empty($prv['Prv_Cod'])) {
                    $alerta = 'Proveedor no registrado con RUC/CI ' . $ced . '.';
                } elseif (isset($prvUsados[$prv['Prv_Cod']])) {
                    $alerta = 'Proveedor duplicado en la plantilla.';
                }
            }

            if ($alerta === '' && !empty($prv['Prv_Cod'])) {
                $prvUsados[$prv['Prv_Cod']] = true;
                $stats['validos']++;
                $rowsOut[] = array(
                    'Prv_Cod' => (int)$prv['Prv_Cod'],
                    'Prs_Ced' => $prv['Prs_Ced'],
                    'nombre' => $prv['nombre'],
                    'Atp_Fec' => $fecha,
                    'Pag_Cod' => $Pag_Cod,
                    'Pag_Des' => $Pag_Des,
                    'Valor' => round($valor, 2),
                    'Alerta' => ''
                );
            } else {
                $stats['alertas']++;
                $rowsOut[] = array(
                    'Prv_Cod' => '',
                    'Prs_Ced' => $ced,
                    'nombre' => $nombre,
                    'Atp_Fec' => $fecha,
                    'Pag_Cod' => $Pag_Cod,
                    'Pag_Des' => $Pag_Des,
                    'Valor' => $valor > 0 ? round($valor, 2) : '',
                    'Alerta' => $alerta
                );
            }
        }

        if ($tempCopy && is_file($readPath)) {
            @unlink($readPath);
        }

        if (count($rowsOut) < 1) {
            $response['message'] = 'La plantilla no contiene filas de datos.';
            $obBD_con1->echoJson($response);
        }

        $response['success'] = true;
        $response['rows'] = $rowsOut;
        $response['stats'] = $stats;
        $response['message'] = 'Se procesaron ' . $stats['total'] . ' fila(s): '
            . $stats['validos'] . ' valida(s), ' . $stats['alertas'] . ' con alerta.';
        $obBD_con1->echoJson($response);
    } catch (Exception $ex) {
        if ($tempCopy && is_file($readPath)) {
            @unlink($readPath);
        }
        $response['message'] = 'Error al leer la plantilla: ' . $ex->getMessage();
        $obBD_con1->echoJson($response);
    }
}

function aini_norm_ced_plantilla($ced) {
    $ced = trim((string)$ced);
    if (preg_match('/^[\d\.]+E[\+\-]?\d+$/i', $ced)) {
        $ced = sprintf('%.0f', (float)$ced);
    }
    return preg_replace('/[^0-9]/', '', $ced);
}

/**
 * Genera plantilla XLSX minima; columna B (RUC/CI) con formato texto (@).
 */
function aini_generar_plantilla_xlsx() {
    if (!class_exists('ZipArchive')) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plantilla_anticipos_iniciales.csv"');
        echo "\xEF\xBB\xBF";
        echo "FECHA,RUC O CEDULA,NOMBRE O PROVEEDOR,VALOR\r\n";
        echo "2026-01-15,\"=\"\"1234567890001\"\"\",EJEMPLO PROVEEDOR SA,1500.00\r\n";
        exit;
    }

    $esc = function ($s) {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };
    $cellStr = function ($ref, $val, $style = null) use ($esc) {
        $sAttr = $style !== null ? ' s="' . (int)$style . '"' : '';
        return '<c r="' . $ref . '"' . $sAttr . ' t="inlineStr"><is><t>' . $esc($val) . '</t></is></c>';
    };
    $cellNum = function ($ref, $val) {
        return '<c r="' . $ref . '"><v>' . (float)$val . '</v></c>';
    };

    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<cols><col min="2" max="2" width="22" customWidth="1" style="1"/></cols>'
        . '<sheetData>'
        . '<row r="1">'
        . $cellStr('A1', 'FECHA')
        . $cellStr('B1', 'RUC O CEDULA', 1)
        . $cellStr('C1', 'NOMBRE O PROVEEDOR')
        . $cellStr('D1', 'VALOR')
        . '</row>'
        . '<row r="2">'
        . $cellStr('A2', '15/01/2026')
        . $cellStr('B2', '1234567890001', 1)
        . $cellStr('C2', 'EJEMPLO PROVEEDOR SA')
        . $cellNum('D2', 1500)
        . '</row>'
        . '</sheetData></worksheet>';

    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode="@"/></numFmts>'
        . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '</cellXfs></styleSheet>';

    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Plantilla" sheetId="1" r:id="rId1"/></sheets></workbook>';

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';

    $tmp = tempnam(sys_get_temp_dir(), 'aini_xlsx_');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->addFromString('xl/styles.xml', $styles);
    $zip->close();

    $bin = file_get_contents($tmp);
    @unlink($tmp);
    return $bin;
}

function aini_parse_fecha_plantilla($val) {
    $val = trim((string)$val);
    if ($val === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
        return $val;
    }
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
    }
    if (is_numeric($val) && (float)$val > 30000) {
        $ts = ((float)$val - 25569) * 86400;
        return gmdate('Y-m-d', (int)$ts);
    }
    $ts = strtotime(str_replace('/', '-', $val));
    return $ts ? date('Y-m-d', $ts) : '';
}

function aini_fila_sin_datos_utiles($fecha, $ced, $valor) {
    return ($fecha === '' && $ced === '' && $valor <= 0);
}

function aini_es_fila_encabezado_plantilla($cells) {
    if (aini_map_cols_plantilla($cells) !== null) {
        return true;
    }
    $hits = 0;
    $keysHeader = array(
        'FECHA', 'FEC', 'DATE',
        'RUCOCEDULA', 'RUCCEDULA', 'RUC', 'CEDULA', 'CI',
        'NOMBREOPROVEEDOR', 'NOMBREPROVEEDOR', 'NOMBRE', 'PROVEEDOR', 'RAZONSOCIAL',
        'VALOR', 'MONTO', 'IMPORTE', 'ANTICIPO'
    );
    foreach ($cells as $c) {
        $k = strtoupper(preg_replace('/[^A-Z0-9]/', '', aini_unaccent_plantilla($c)));
        if ($k !== '' && in_array($k, $keysHeader, true)) {
            $hits++;
        }
    }
    return ($hits >= 2);
}

function aini_map_cols_plantilla($cells) {
    $norm = array();
    foreach ($cells as $i => $c) {
        $k = strtoupper(preg_replace('/[^A-Z0-9]/', '', aini_unaccent_plantilla($c)));
        if ($k !== '') {
            $norm[$k] = $i;
        }
    }
    $fecha = null;
    foreach (array('FECHA', 'FEC', 'DATE') as $k) {
        if (isset($norm[$k])) { $fecha = $norm[$k]; break; }
    }
    $ced = null;
    foreach (array('RUCOCEDULA', 'RUCCEDULA', 'RUC', 'CEDULA', 'CI', 'PRSCED') as $k) {
        if (isset($norm[$k])) { $ced = $norm[$k]; break; }
    }
    $nombre = null;
    foreach (array('NOMBREOPROVEEDOR', 'NOMBREPROVEEDOR', 'PROVEEDOR', 'NOMBRE', 'RAZONSOCIAL') as $k) {
        if (isset($norm[$k])) { $nombre = $norm[$k]; break; }
    }
    $valor = null;
    foreach (array('VALOR', 'MONTO', 'IMPORTE', 'ANTICIPO') as $k) {
        if (isset($norm[$k])) { $valor = $norm[$k]; break; }
    }
    if ($fecha === null || $ced === null || $nombre === null || $valor === null) {
        return null;
    }
    return array('fecha' => $fecha, 'ced' => $ced, 'nombre' => $nombre, 'valor' => $valor);
}

function aini_unaccent_plantilla($s) {
    $s = (string)$s;
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) {
            return $t;
        }
    }
    return $s;
}

/**
 * Guarda lote: comprobante ficticio (Com_Est=E) + asientos ficticios por proveedor.
 */
if (isset($saveAnticiposIniciales)) {
    $response = array('success' => false, 'message' => 'No se ha logrado realizar la Transaccion');

    $Atp_Obs = isset($_POST['Atp_Obs']) ? trim($_POST['Atp_Obs']) : (isset($Atp_Obs) ? trim($Atp_Obs) : '');
    $items = array();
    if (isset($_POST['items'])) {
        if (is_array($_POST['items'])) {
            $items = $_POST['items'];
        } elseif (is_string($_POST['items'])) {
            $raw = stripslashes($_POST['items']);
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                $decoded = json_decode($_POST['items'], true);
            }
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
    }

    if (count($items) < 1) {
        $response['message'] = 'Agregue al menos un proveedor con anticipo en el grid.';
        $obBD_con1->echoJson($response);
    }

    $ctaAnp = $obBD_con1->getRowConsulta(3, '', $obBD_conexion);
    if (empty($ctaAnp['Pld_Cod'])) {
        $response['message'] = 'No existe cuenta parametrizada para ANTICIPOS A PROVEEDORES (ANP).';
        $obBD_con1->echoJson($response);
    }

    $tipos = $obBD_con1->getArrayConsulta(10, '', $obBD_conexion);
    $Tia_Cod = 0;
    if (is_array($tipos) && count($tipos) > 0) {
        $Tia_Cod = (int)$tipos[0]['Tia_Cod'];
    }
    if ($Tia_Cod <= 0) {
        $response['message'] = 'No hay tipo de asiento de egreso parametrizado.';
        $obBD_con1->echoJson($response);
    }

    $tipoIni = $obBD_con1->getRowConsulta(43, '', $obBD_conexion);
    $Pag_Cod_Default = !empty($tipoIni['Pag_Cod']) ? (int)$tipoIni['Pag_Cod'] : 0;
    if ($Pag_Cod_Default <= 0) {
        $response['message'] = 'No existe tipo de pago Inicial (Pag_Abr=INI) en tipos_pago.';
        $obBD_con1->echoJson($response);
    }

    $filas = array();
    $totalLote = 0;
    foreach ($items as $idx => $d) {
        $Prv_Cod = isset($d['Prv_Cod']) ? (int)$d['Prv_Cod'] : 0;
        $valor = isset($d['Valor']) ? (float)str_replace(',', '', $d['Valor']) : 0;
        $Atp_Fec = isset($d['Atp_Fec']) ? trim($d['Atp_Fec']) : '';
        $Pag_Cod = isset($d['Pag_Cod']) ? (int)$d['Pag_Cod'] : $Pag_Cod_Default;
        if ($Pag_Cod <= 0) {
            $Pag_Cod = $Pag_Cod_Default;
        }
        $nombre = isset($d['nombre']) ? trim($d['nombre']) : '';
        if ($Prv_Cod <= 0 || $valor <= 0 || $Atp_Fec === '') {
            $response['message'] = 'Fila ' . ($idx + 1) . ': proveedor, fecha y valor > 0 son obligatorios.';
            $obBD_con1->echoJson($response);
        }
        $filas[] = array(
            'Prv_Cod' => $Prv_Cod,
            'nombre' => $nombre,
            'Atp_Fec' => $Atp_Fec,
            'Pag_Cod' => $Pag_Cod,
            'Valor' => round($valor, 2)
        );
        $totalLote += round($valor, 2);
    }

    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

    $creados = array();
    $lastCom = 0;
    $lastTia = $Tia_Cod;
    $lastPec = 0;
    $conceptoFijo = 'ANTICIPO INICIAL';

    foreach ($filas as $f) {
        $Pec = $obBD_con1->getRowConsulta(5, $f['Atp_Fec'], $obBD_conexion);
        if (empty($Pec['Pec_Cod'])) {
            $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
            $response['message'] = 'No hay periodo contable para la fecha ' . $f['Atp_Fec'] . '.';
            $obBD_con1->echoJson($response);
        }

        $obs = $Atp_Obs !== '' ? $Atp_Obs : $conceptoFijo;
        if ($f['nombre'] !== '') {
            $obs .= ' - ' . $f['nombre'];
        }

        $obBD_con1->operacionobBD(41, array(
            'Pec_Cod' => $Pec['Pec_Cod'],
            'Prv_Cod' => $f['Prv_Cod'],
            'Com_Num' => 0,
            'Com_Fec' => $f['Atp_Fec'],
            'Com_Con' => $obs,
            'Com_Val' => $f['Valor'],
            'Tia_Cod' => $Tia_Cod
        ), $obBD_conexion);
        $Com_Cod = $obBD_con1->insercionid($obBD_conexion);
        $lastCom = $Com_Cod;
        $lastPec = $Pec['Pec_Cod'];

        $obBD_con1->operacionobBD(7, array(
            'Atp_Fec' => $f['Atp_Fec'],
            'Atp_Val' => $f['Valor'],
            'Atp_Obs' => $obs,
            'Com_Cod' => $Com_Cod,
            'Prv_Cod' => $f['Prv_Cod']
        ), $obBD_conexion);
        $Atp_Cod = $obBD_con1->insercionid($obBD_conexion);

        $obBD_con1->operacionobBD(42, array(
            'Com_Cod' => $Com_Cod,
            'Asi_Deh' => 'D',
            'Asi_Glo' => $conceptoFijo,
            'Asi_Val' => $f['Valor'],
            'Pld_Cod' => $ctaAnp['Pld_Cod']
        ), $obBD_conexion);

        $obBD_con1->operacionobBD(42, array(
            'Com_Cod' => $Com_Cod,
            'Asi_Deh' => 'H',
            'Asi_Glo' => $obs,
            'Asi_Val' => $f['Valor'],
            'Pld_Cod' => $ctaAnp['Pld_Cod']
        ), $obBD_conexion);
        $Asi_Hab = $obBD_con1->insercionid($obBD_conexion);

        $obBD_con1->operacionobBD(8, array(
            'Pap_Cto' => $conceptoFijo,
            'Pap_Ctd' => '',
            'Pap_Val' => $f['Valor'],
            'Atp_Cod' => $Atp_Cod,
            'Pag_Cod' => $f['Pag_Cod'],
            'Asi_Cod' => $Asi_Hab
        ), $obBD_conexion);

        $creados[] = array('Atp_Cod' => $Atp_Cod, 'Com_Cod' => $Com_Cod, 'Prv_Cod' => $f['Prv_Cod']);
    }

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
        $response['message'] = 'Se registraron ' . count($creados) . ' anticipo(s) inicial(es) ficticios. Total: ' . number_format($totalLote, 2, '.', '');
        $response['creados'] = $creados;
        $response['total'] = $totalLote;
        $response['link'] = '../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=' . $lastCom
            . '&tabla=proveedore&campo=Prv_Cod&tipo=' . $lastTia . '&Pec_Cod=' . $lastPec;
    } else {
        $response['message'] = 'Error al guardar el lote de anticipos iniciales.';
    }
    $obBD_con1->echoJson($response);
}
?>
<!DOCTYPE html>
<html>
<head>
    <TITLE><?Php echo "Ant.Proveedor Iniciales [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/tes_val_anticipo_prv_inicial.js?e=21"></script>
    <style>
        .aini-wrap { max-width: 1100px; margin: 0 auto; }
        .aini-note {
            margin: 0 0 6px;
            padding: 5px 8px;
            border-left: 3px solid #2f6a9b;
            background: #f5f8fb;
            color: #4a6273;
            font-size: 11px;
            line-height: 1.3;
        }
        .aini-addon {
            min-width: 90px;
            max-width: 120px;
            padding: 3px 6px !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            background: #e8f1f8 !important;
            color: #1f5f8b;
            font-weight: 700;
            font-size: 10px;
            border-color: #c5d8e8 !important;
        }
        .aini-addon.empty { color: #9db1bf; font-weight: 500; background: #f7fafc !important; }
        .aini-actions { margin-top: 8px; text-align: center; }
        #AnticipoIniForm .exa-fieldset { padding: 6px 8px 4px; }
        #AnticipoIniForm .exa-fieldset > legend { margin-bottom: 4px; font-size: 12px; }
        #AnticipoIniForm .exa-fieldset .form-group {
            margin-left: 0;
            margin-right: 0;
            margin-bottom: 4px;
        }
        #AnticipoIniForm .exa-fieldset .control-label {
            padding-top: 4px;
            padding-bottom: 0;
            font-size: 11px;
        }
        #AnticipoIniForm .exa-fieldset .form-control,
        #AnticipoIniForm .exa-fieldset .input-group-addon,
        #AnticipoIniForm .exa-fieldset .btn {
            height: 26px;
            padding: 2px 6px;
            font-size: 11px;
            line-height: 1.3;
        }
        #AnticipoIniForm .exa-fieldset .input-group-btn .btn { height: 26px; }
        #AnticipoIniForm .aini-btn-add { padding-left: 4px; }
        #gridAnticipos_pager { margin-bottom: 4px; }
        .aini-alerta-icon {
            text-align: center;
            line-height: 1;
        }
        .aini-alerta-icon .glyphicon {
            color: #c0392b;
            font-size: 14px;
            cursor: help;
        }
        .ui-jqgrid tr.jqgrow.aini-fila-alerta td {
            color: #c0392b !important;
        }
        .ui-jqgrid tr.jqgrow.aini-fila-alerta input {
            color: #c0392b !important;
        }
        #AnticipoIniForm .exa-fieldset #btnLimpiarGridIni.btn,
        #AnticipoIniForm .exa-fieldset #btnPlantillaExcelIni.btn,
        #AnticipoIniForm .exa-fieldset #btnCargarExcelIni.btn {
            height: 20px !important;
            min-height: 20px !important;
            line-height: 18px !important;
            padding: 0 6px !important;
            font-size: 11px !important;
            margin-left: 4px;
            vertical-align: middle;
        }
        #btnLimpiarGridIni .ui-pg-div,
        #btnPlantillaExcelIni .ui-pg-div,
        #btnCargarExcelIni .ui-pg-div {
            padding: 0;
            margin: 0;
            height: 18px;
            line-height: 18px;
            color: #fff;
            font-size: 11px;
        }
        #btnLimpiarGridIni .ui-pg-div .glyphicon,
        #btnPlantillaExcelIni .ui-pg-div .glyphicon,
        #btnCargarExcelIni .ui-pg-div .glyphicon {
            color: #fff;
            font-size: 11px;
            top: 1px;
        }
    </style>
</head>
<body>
<div class="panel panel-main">
    <div class="panel-heading exa-header">
        <h3 class="panel-title">&raquo; Anticipos a Proveedores Iniciales</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="aini-wrap">
            <p class="aini-note">Los campos con asterisco (<span class="required"></span>) son obligatorios.</p>
            <form class="form-horizontal normal" id="AnticipoIniForm" method="post" action="javascript:;">
                <input type="hidden" name="Prs_Cod" id="Prs_Cod" value=""/>
                <input type="hidden" name="Prv_Cod" id="Prv_Cod" value=""/>
                <input type="hidden" name="op_opciones" value="c"/>
                <input type="hidden" id="Pld_Cod_Deb" name="Pld_Cod_Deb" value=""/>
                <input type="hidden" id="Pag_Cod" name="Pag_Cod"
                       value="<?php echo !empty($tipoPagoInicial['Pag_Cod']) ? (int)$tipoPagoInicial['Pag_Cod'] : ''; ?>"/>

                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Datos del Anticipo Inicial</legend>
                    <div class="form-group">
                        <label class="col-sm-1 control-label label-xs required">Fecha:</label>
                        <div class="col-sm-2">
                            <div class="input-group input-group-sm">
                                <input name="Atp_Fec" id="Atp_Fec" type="text" class="form-control input-sm datepicker" required/>
                                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                            </div>
                        </div>
                        <label class="col-sm-1 control-label label-xs required">Tipo:</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control input-sm" id="Pag_Des" name="Pag_Des" readonly
                                   value="<?php
                                    if (!empty($tipoPagoInicial['Pag_Des'])) {
                                        echo htmlspecialchars($tipoPagoInicial['Pag_Abr'] . ' - ' . $tipoPagoInicial['Pag_Des']);
                                    } else {
                                        echo 'Inicial (no parametrizado)';
                                    }
                                   ?>"/>
                        </div>
                        <label class="col-sm-1 control-label label-xs required">Valor:</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control input-sm" id="ValorCap" name="ValorCap" placeholder="0.00"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1 control-label label-xs required">Proveedor:</label>
                        <div class="col-sm-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-addon aini-addon empty" id="Prs_Ced_Addon" title="RUC / CI">RUC/CI</span>
                                <input name="nombre" id="nombre" type="text" class="form-control input-sm"
                                       placeholder="Seleccione un proveedor..." readonly/>
                                <input type="hidden" name="Prs_Ced" id="Prs_Ced" value=""/>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-success btn-sm" title="Buscar proveedor" onclick="$('#proveedoresDialog').dialog('open');">
                                        <span class="glyphicon glyphicon-search"></span>
                                    </button>
                                    <button type="button" class="btn btn-default btn-sm" title="Limpiar proveedor" onclick="limpiarProveedor();">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </span>
                            </div>
                        </div>
                        
                    </div>

                    <div class="form-group">                        
                        <label class="col-sm-1 control-label label-xs">Observacion:</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control input-sm" id="Atp_Obs" name="Atp_Obs"
                                   maxlength="250" placeholder="Observaci&oacute;n del lote..."/>
                        </div>
                        <div class="col-sm-1 aini-btn-add">
                            <button type="button" class="btn btn-sm btn-primary btn-block" onclick="agregarProveedorGrid();"><i class="glyphicon glyphicon-plus"></i> Agregar</button>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Proveedores / Anticipos del lote</legend>
                    <table id="gridAnticipos"></table>
                    <div id="gridAnticiposPager"></div>
                    <input type="file" id="archivoPlantilla" accept=".xlsx,.xls,.csv" style="display:none;"
                           onchange="cargarPlantillaExcel(this);"/>
                </fieldset>

                <div class="aini-actions">
                    <button type="button" class="btn btn-sm btn-primary" onclick="guardarAnticiposLote();">
                        <i class="glyphicon glyphicon-floppy-disk"></i> Guardar todos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="proveedoresDialog" title="B&uacute;squeda de Proveedores">
    <form class="form-horizontal normal"></form>
</div>
<div id="successDialog" title="Mensaje del Sistema">
    <center><h3>El lote de Anticipos Iniciales se registr&oacute; con &eacute;xito</h3></center>
    <center id="successMsg" style="margin-top:6px;color:#4a6273;"></center>
    <center style="margin-top:12px;">
        <button type="button" class="btn btn-danger btn-sm" onclick="$('#successDialog').dialog('close');">
            <i class="glyphicon glyphicon-remove"></i> Cerrar
        </button>
        <a id="impCompr" target="_blank" href="#" class="btn btn-success btn-sm">
            <i class="glyphicon glyphicon-print"></i> Imprimir &uacute;ltimo
        </a>
    </center>
</div>
</body>
</html>
