<?php
/**
 * Gestión Operativa del Despacho - Generador de Tareas por Período
 * @author Sistema EXA | @version 1.0
 */
if (!empty($_GET['debug'])) { ini_set('display_errors', 1); error_reporting(E_ALL); }
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_despacho_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;

// Mapeo estándar SRI Ecuador: 9no dígito -> día declaración (fallback si aud_despacho_ruc_fechas no existe)
$GLOBALS['_aud_ruc_dias_mensual'] = array(0 => 28, 1 => 10, 2 => 12, 3 => 14, 4 => 16, 5 => 18, 6 => 20, 7 => 22, 8 => 24, 9 => 26);

// Función: calcular fecha límite según 9no dígito RUC (opcional por actividad)
function calcularFechaLimiteRuc($periodo, $tipo, $digito, $obBD_con1, $obBD_conexion) {
    if ($digito === null || $digito === '') return null;
    $dig = intval($digito);
    if ($dig < 0 || $dig > 9) $dig = 0;
    $ruc = $obBD_con1->getRowConsulta(45, array('Ruc_Digito' => $dig), $obBD_conexion);
    $dia = 10;
    if (!empty($ruc) && isset($ruc['Ruc_Dia_Mensual'])) {
        $dia = intval($ruc['Ruc_Dia_Mensual']);
    } elseif (isset($GLOBALS['_aud_ruc_dias_mensual'][$dig])) {
        $dia = $GLOBALS['_aud_ruc_dias_mensual'][$dig];
    }
    $dia_sem1 = (!empty($ruc) && isset($ruc['Ruc_Dia_Sem1'])) ? intval($ruc['Ruc_Dia_Sem1']) : 10;
    $dia_sem2 = (!empty($ruc) && isset($ruc['Ruc_Dia_Sem2'])) ? intval($ruc['Ruc_Dia_Sem2']) : 10;
    $parts = explode('-', $periodo);
    $y = isset($parts[0]) ? intval($parts[0]) : date('Y');
    $m = isset($parts[1]) ? intval($parts[1]) : (int)date('m');
    // Período mensual (YYYY-MM): aplicar día según 9no dígito tanto para MENSUAL como para ANUAL
    if (($tipo === 'MENSUAL' || $tipo === 'ANUAL') && $m >= 1 && $m <= 12) {
        $ultimoDia = (int)date('t', mktime(0, 0, 0, $m, 1, $y));
        $diaOk = min($dia, $ultimoDia);
        if (checkdate($m, $diaOk, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $diaOk);
        }
        return sprintf('%04d-%02d-%02d', $y, $m, min($dia, 28));
    }
    if ($tipo === 'ANUAL' && strlen(trim($periodo)) === 4) {
        return $y . '-12-31';
    }
    if ($m >= 1 && $m <= 12) {
        if ($m == 1) return sprintf('%04d-01-%02d', $y, min($dia_sem2, 31));
        if ($m == 7) return sprintf('%04d-07-%02d', $y, min($dia_sem1, 31));
    }
    return null;
}

// Ajax: Listar personal (empleados) para asignación
if (!empty($_REQUEST['listarPersonalAsig'])) {
    $arr = $obBD_con1->getArrayConsulta(35, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Listar actividades para asignación
if (!empty($_REQUEST['listarActividadesAsig'])) {
    $arr = $obBD_con1->getArrayConsulta(2, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Listar tareas para asignación (Tar_Periodo o Fecha_Ini/Fecha_Fin para rango; Cli_Cod, Act_Cod, Cli_Cod_In)
if (!empty($_REQUEST['listarTareasAsignacion'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $arr = array();
    if (!empty($Ses_Emp_Cod)) {
        $fec_ini = trim(isset($_REQUEST['Fecha_Ini']) ? $_REQUEST['Fecha_Ini'] : '');
        $fec_fin = trim(isset($_REQUEST['Fecha_Fin']) ? $_REQUEST['Fecha_Fin'] : '');
        $periodo = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
        $meses = array();
        if ($fec_ini !== '' && $fec_fin !== '') {
            $meses = aud_meses_en_rango($fec_ini, $fec_fin);
        } elseif ($periodo !== '') {
            $meses = array($periodo);
        }
        $seen = array();
        foreach ($meses as $per) {
            $par = array('Emp_Cod' => $Ses_Emp_Cod, 'Tar_Periodo' => $per);
            if (!empty($_REQUEST['Cli_Cod'])) $par['Cli_Cod'] = intval($_REQUEST['Cli_Cod']);
            if (!empty($_REQUEST['Act_Cod'])) $par['Act_Cod'] = intval($_REQUEST['Act_Cod']);
            if (!empty($_REQUEST['Cli_Cod_In'])) $par['Cli_Cod_In'] = $_REQUEST['Cli_Cod_In'];
            $rows = $obBD_con1->getArrayConsulta(70, $par, $obBD_conexion);
            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $tid = isset($r['Tar_Cod']) ? $r['Tar_Cod'] : 0;
                    if ($tid && empty($seen[$tid])) {
                        $seen[$tid] = true;
                        $arr[] = $r;
                    }
                }
            }
        }
    }
    $out = array('rows' => $arr);
    if (!empty($_REQUEST['debug']) && $obBD_con1->getError() != 0) {
        $out['debug_error'] = array('code' => $obBD_con1->getError(), 'msg' => $obBD_con1->getMsgError());
    }
    echo json_encode($out);
    exit;
}

// Ajax: Listar clientes con tareas en período
if (!empty($_REQUEST['listarClientesConTareas'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $arr = array();
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    if ($per !== '' && !empty($Ses_Emp_Cod)) {
        $arr = $obBD_con1->getArrayConsulta(71, array('Emp_Cod' => $Ses_Emp_Cod, 'Tar_Periodo' => $per), $obBD_conexion);
        if (!is_array($arr)) $arr = array();
    }
    $out = array('rows' => $arr);
    if (!empty($_REQUEST['debug']) && $obBD_con1->getError() != 0) {
        $out['debug_error'] = array('code' => $obBD_con1->getError(), 'msg' => $obBD_con1->getMsgError());
    }
    echo json_encode($out);
    exit;
}

// Ajax: Listar clientes con contratos vigentes en el período (para combo Generar tareas)
if (!empty($_REQUEST['listarClientesContratosPeriodo'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $arr = array();
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    if ($per !== '' && !empty($Ses_Emp_Cod)) {
        $arr = $obBD_con1->getArrayConsulta(83, array('Emp_Cod' => $Ses_Emp_Cod, 'Tar_Periodo' => $per), $obBD_conexion);
        if (!is_array($arr)) $arr = array();
    }
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Listar actividades EVENTUALES de un cliente en un período (para generar tarea individual)
if (!empty($_REQUEST['listarActividadesEventuales'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $rowsOut = array();
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $cliCod = isset($_REQUEST['Cli_Cod']) ? intval($_REQUEST['Cli_Cod']) : 0;
    if ($per !== '' && $cliCod > 0 && !empty($Ses_Emp_Cod)) {
        // Buscar contratos vigentes para la empresa en el período y filtrar por cliente
        $parContr = array('Emp_Cod' => $Ses_Emp_Cod, 'Tar_Periodo' => $per);
        $contratos = $obBD_con1->getArrayConsulta(30, $parContr, $obBD_conexion);
        if (is_array($contratos)) {
            $vistas = array(); // Act_Cod ya agregado
            foreach ($contratos as $con) {
                if (!isset($con['Cli_Cod']) || intval($con['Cli_Cod']) !== $cliCod) continue;
                $conCod = isset($con['Con_Cod']) ? intval($con['Con_Cod']) : 0;
                if ($conCod <= 0) continue;
                $actividades = $obBD_con1->getArrayConsulta(43, array('Con_Cod' => $conCod), $obBD_conexion);
                $actPorDefecto = $obBD_con1->getArrayConsulta(44, array('Con_Cod' => $conCod), $obBD_conexion);
                $todas = array_merge($actividades ?: array(), $actPorDefecto ?: array());
                foreach ($todas as $a) {
                    $tipo = isset($a['Act_Tipo']) ? $a['Act_Tipo'] : 'MENSUAL';
                    if (strtoupper($tipo) !== 'EVENTUAL') continue;
                    $actCod = isset($a['Act_Cod']) ? intval($a['Act_Cod']) : 0;
                    if ($actCod <= 0 || isset($vistas[$actCod])) continue;
                    $vistas[$actCod] = true;
                    $rowsOut[] = array(
                        'Act_Cod' => $actCod,
                        'Act_Nombre' => isset($a['Act_Nombre']) ? $a['Act_Nombre'] : '',
                        'Ser_Nombre' => isset($a['Ser_Nombre']) ? $a['Ser_Nombre'] : '',
                        'Con_Cod' => $conCod
                    );
                }
            }
        }
    }
    echo json_encode(array('rows' => $rowsOut));
    exit;
}

// Ajax: Generar una tarea EVENTUAL individual (cliente + actividad + período)
if (!empty($_REQUEST['generarTareaEventual'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'message' => '', 'Tar_Cod' => 0);
    $per = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $cliCod = isset($_REQUEST['Cli_Cod']) ? intval($_REQUEST['Cli_Cod']) : 0;
    $actCod = isset($_REQUEST['Act_Cod']) ? intval($_REQUEST['Act_Cod']) : 0;
    $fecLim = isset($_REQUEST['Tar_Fecha_Limite']) ? trim($_REQUEST['Tar_Fecha_Limite']) : '';
    if ($per === '' || $cliCod <= 0 || $actCod <= 0) {
        $resp['message'] = 'Período, cliente y actividad son obligatorios.';
        echo json_encode($resp);
        exit;
    }
    // Evitar duplicados por cliente + actividad + período
    $existe = $obBD_con1->getRowConsulta(32, array('Cli_Cod' => $cliCod, 'Act_Cod' => $actCod, 'Tar_Periodo' => $per), $obBD_conexion);
    if (!empty($existe) && isset($existe['Tar_Cod'])) {
        $resp['message'] = 'Ya existe una tarea para este cliente, actividad y período.';
        $resp['Tar_Cod'] = intval($existe['Tar_Cod']);
        echo json_encode($resp);
        exit;
    }
    // Buscar contrato vigente del cliente para enlazar la tarea
    $conCodSel = 0;
    if (!empty($Ses_Emp_Cod)) {
        $parContr = array('Emp_Cod' => $Ses_Emp_Cod, 'Tar_Periodo' => $per);
        $contratos = $obBD_con1->getArrayConsulta(30, $parContr, $obBD_conexion);
        if (is_array($contratos)) {
            foreach ($contratos as $con) {
                if (isset($con['Cli_Cod']) && intval($con['Cli_Cod']) === $cliCod) {
                    $conCodSel = isset($con['Con_Cod']) ? intval($con['Con_Cod']) : 0;
                    if ($conCodSel > 0) break;
                }
            }
        }
    }
    if ($conCodSel <= 0) {
        $resp['message'] = 'No se encontró un contrato vigente para este cliente en el período indicado.';
        echo json_encode($resp);
        exit;
    }
    // Si no se indicó fecha límite, usar último día del mes del período
    if ($fecLim === '' && strlen($per) >= 7) {
        $parts = explode('-', $per);
        $y = isset($parts[0]) ? intval($parts[0]) : intval(date('Y'));
        $m = isset($parts[1]) ? intval($parts[1]) : intval(date('m'));
        $fec_ini_m = sprintf('%04d-%02d-01', $y, $m);
        $fecLim = date('Y-m-t', strtotime($fec_ini_m));
    }
    $obs = 'Tarea EVENTUAL generada manualmente desde el generador.';
    $obBD_con1->setError(0, '');
    $ok = $obBD_con1->operacionobBD(31, array(
        'Cli_Cod' => $cliCod,
        'Act_Cod' => $actCod,
        'Tar_Periodo' => $per,
        'Tar_Fecha_Limite' => $fecLim,
        'Con_Cod' => $conCodSel,
        'Emp_Cod' => $Ses_Emp_Cod,
        'Tar_Observaciones' => $obs
    ), $obBD_conexion);
    if ($ok && $obBD_con1->Error == 0) {
        $tarCod = $obBD_con1->insercionid($obBD_conexion);
        $resp['success'] = true;
        $resp['Tar_Cod'] = $tarCod ? intval($tarCod) : 0;
        $resp['message'] = 'Tarea eventual generada correctamente.';
    } else {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al generar la tarea eventual.';
    }
    echo json_encode($resp);
    exit;
}

// Helper: devolver meses (YYYY-MM) entre dos fechas
function aud_meses_en_rango($fec_ini, $fec_fin) {
    $meses = array();
    $ini = strtotime($fec_ini);
    $fin = strtotime($fec_fin);
    if ($ini === false || $fin === false || $ini > $fin) return $meses;
    $y = (int)date('Y', $ini);
    $m = (int)date('n', $ini);
    $fin_y = (int)date('Y', $fin);
    $fin_m = (int)date('n', $fin);
    while ($y < $fin_y || ($y == $fin_y && $m <= $fin_m)) {
        $meses[] = sprintf('%04d-%02d', $y, $m);
        $m++;
        if ($m > 12) { $m = 1; $y++; }
    }
    return $meses;
}

// Ajax: Vista previa de tareas a generar (Fecha_Ini/Fecha_Fin = rango libre; o Tar_Periodo = un mes; Cli_Cod = filtrar)
if (!empty($_REQUEST['previewTareas'])) {
    $fec_ini_req = trim(isset($_REQUEST['Fecha_Ini']) ? $_REQUEST['Fecha_Ini'] : '');
    $fec_fin_req = trim(isset($_REQUEST['Fecha_Fin']) ? $_REQUEST['Fecha_Fin'] : '');
    $periodo = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $filtroCli = !empty($_REQUEST['Cli_Cod']) ? intval($_REQUEST['Cli_Cod']) : 0;
    $preview = array('rows' => array(), 'total_nuevas' => 0, 'total_omitidas' => 0, 'contratos' => 0, 'fec_ini' => '', 'fec_fin' => '');
    $meses_a_procesar = array();
    if ($fec_ini_req !== '' && $fec_fin_req !== '') {
        $preview['fec_ini'] = $fec_ini_req;
        $preview['fec_fin'] = $fec_fin_req;
        $meses_a_procesar = aud_meses_en_rango($fec_ini_req, $fec_fin_req);
    } elseif ($periodo !== '') {
        $es_anual = (strlen($periodo) === 4);
        if ($es_anual) {
            $preview['fec_ini'] = $periodo . '-01-01';
            $preview['fec_fin'] = $periodo . '-12-31';
            $meses_a_procesar = aud_meses_en_rango($preview['fec_ini'], $preview['fec_fin']);
        } else {
            $parts = explode('-', $periodo);
            $y = isset($parts[0]) ? $parts[0] : date('Y');
            $m = isset($parts[1]) ? $parts[1] : date('m');
            $preview['fec_ini'] = $y . '-' . $m . '-01';
            $preview['fec_fin'] = date('Y-m-t', strtotime($preview['fec_ini']));
            $meses_a_procesar = array($periodo);
        }
    }
    if (!empty($meses_a_procesar)) {
        $par_contratos = array('Emp_Cod' => $Ses_Emp_Cod, 'Fecha_Ini' => $preview['fec_ini'], 'Fecha_Fin' => $preview['fec_fin']);
        $contratos = $obBD_con1->getArrayConsulta(30, $par_contratos, $obBD_conexion);
        $preview['contratos'] = is_array($contratos) ? count($contratos) : 0;
        if (is_array($contratos)) {
            foreach ($meses_a_procesar as $periodo) {
                $es_anual = (strlen($periodo) === 4);
                if ($es_anual) {
                    $fec_ini = $periodo . '-01-01';
                    $fec_fin = $periodo . '-12-31';
                } else {
                    $parts = explode('-', $periodo);
                    $y = isset($parts[0]) ? $parts[0] : date('Y');
                    $m = isset($parts[1]) ? $parts[1] : date('m');
                    $fec_ini = $y . '-' . $m . '-01';
                    $fec_fin = date('Y-m-t', strtotime($fec_ini));
                }
                foreach ($contratos as $con) {
                    $conCod = $con['Con_Cod'];
                    $cliCod = $con['Cli_Cod'];
                    if ($filtroCli > 0 && $cliCod != $filtroCli) continue;
                    $cliente = $obBD_con1->getRowConsulta(46, array('Cli_Cod' => $cliCod), $obBD_conexion);
                    $clienteNom = isset($cliente['Cliente_Nombre']) ? $cliente['Cliente_Nombre'] : (isset($cliente['Prs_Nom']) ? trim($cliente['Prs_Ape'] . ' ' . $cliente['Prs_Nom']) : '');
                    $ruc_ced = isset($cliente['Ruc_Cedula']) && $cliente['Ruc_Cedula'] !== null && $cliente['Ruc_Cedula'] !== ''
                        ? trim((string)$cliente['Ruc_Cedula'])
                        : trim((string)(isset($cliente['Cli_Ruf']) ? $cliente['Cli_Ruf'] : ''));
                    if ($ruc_ced === '') $ruc_ced = trim((string)(isset($cliente['Prs_Ced']) ? $cliente['Prs_Ced'] : ''));
                    $soloDigitos = preg_replace('/\D/', '', $ruc_ced);
                    $digito9 = (strlen($soloDigitos) >= 9) ? substr($soloDigitos, 8, 1) : null;
                    $actividades = $obBD_con1->getArrayConsulta(43, array('Con_Cod' => $conCod), $obBD_conexion);
                    $actPorDefecto = $obBD_con1->getArrayConsulta(44, array('Con_Cod' => $conCod), $obBD_conexion);
                    $todas = array_merge($actividades ?: array(), $actPorDefecto ?: array());
                    $vistas = array();
                    foreach ($todas as $a) {
                        $tipo = isset($a['Act_Tipo']) ? $a['Act_Tipo'] : 'MENSUAL';
                        $mesActual = !$es_anual && strlen($periodo) >= 7 ? substr($periodo, 5, 2) : null;
                        if ($tipo === 'ANUAL' && !$es_anual) {
                            $meses = array();
                            if (!empty($a['Act_Meses_Anual'])) {
                                foreach (explode(',', $a['Act_Meses_Anual']) as $m) {
                                    $m = trim($m);
                                    if ($m !== '') $meses[$m] = true;
                                }
                            }
                            if ($mesActual === null || empty($meses) || !isset($meses[$mesActual])) continue;
                        } elseif ($tipo !== 'MENSUAL' && $tipo !== 'ANUAL') {
                            continue;
                        }
                        $actCod = $a['Act_Cod'];
                        if (isset($vistas[$actCod])) continue;
                        $vistas[$actCod] = true;
                        $existe = $obBD_con1->getRowConsulta(32, array('Cli_Cod' => $cliCod, 'Act_Cod' => $actCod, 'Tar_Periodo' => $periodo), $obBD_conexion);
                        $usa_ruc = isset($a['Act_Usa_Ruc']) && $a['Act_Usa_Ruc'] === 'S';
                        $fec_lim = null;
                        if ($usa_ruc && $digito9 !== null) {
                            $fec_lim = calcularFechaLimiteRuc($periodo, $tipo, $digito9, $obBD_con1, $obBD_conexion);
                        }
                        if ($fec_lim === null) {
                            if ($tipo === 'MENSUAL' && !$es_anual) $fec_lim = $fec_fin;
                            elseif ($tipo === 'ANUAL') $fec_lim = $fec_fin;
                            elseif ($tipo === 'EVENTUAL') $fec_lim = $fec_fin;
                        }
                        $tarEst = !empty($existe) && isset($existe['Tar_Est']) ? $existe['Tar_Est'] : null;
                        $row = array(
                            'Cli_Cod' => $cliCod,
                            'Con_Cod' => $conCod,
                            'Act_Cod' => $actCod,
                            'Cliente_Nombre' => $clienteNom,
                            'Act_Nombre' => isset($a['Act_Nombre']) ? $a['Act_Nombre'] : '',
                            'Ser_Nombre' => isset($a['Ser_Nombre']) ? $a['Ser_Nombre'] : '',
                            'Tar_Periodo' => $periodo,
                            'Tar_Fecha_Limite' => $fec_lim,
                            'Tar_Est' => $tarEst,
                            'Ya_Existe' => !empty($existe),
                            'Tar_Cod' => !empty($existe) && isset($existe['Tar_Cod']) ? intval($existe['Tar_Cod']) : null
                        );
                        $preview['rows'][] = $row;
                        if (!empty($existe)) $preview['total_omitidas']++; else $preview['total_nuevas']++;
                    }
                }
            }
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($preview);
    exit;
}

// Ajax: Asignar tarea(s) a personal
if (!empty($_REQUEST['asignarTareas'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'asignadas' => 0, 'message' => '');
    $perCod = isset($_REQUEST['Per_Cod']) ? intval($_REQUEST['Per_Cod']) : 0;
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    $tarCodIn = isset($_REQUEST['Tar_Cod_In']) ? trim($_REQUEST['Tar_Cod_In']) : '';
    if ($perCod <= 0) {
        $resp['message'] = 'Seleccione el personal a asignar.';
        echo json_encode($resp);
        exit;
    }
    $tareas = array();
    if ($tarCod > 0) $tareas[] = $tarCod;
    if ($tarCodIn !== '') {
        $arr = array_map('intval', array_filter(explode(',', $tarCodIn)));
        $tareas = array_merge($tareas, $arr);
    }
    $tareas = array_unique(array_filter($tareas));
    if (empty($tareas)) {
        $resp['message'] = 'Seleccione al menos una tarea.';
        echo json_encode($resp);
        exit;
    }
    $tareasPermitidas = array();
    foreach ($tareas as $tar) {
        $rowEst = $obBD_con1->getRowConsulta(77, array('Tar_Cod' => $tar, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        $est = isset($rowEst['Tar_Est']) ? strtoupper(trim($rowEst['Tar_Est'])) : '';
        if ($est === 'FINALIZADA') continue;
        $tareasPermitidas[] = $tar;
    }
    if (empty($tareasPermitidas)) {
        $resp['message'] = 'No puede asignar usuarios a tareas finalizadas.';
        echo json_encode($resp);
        exit;
    }
    $asignadas = 0;
    foreach ($tareasPermitidas as $tar) {
        $obBD_con1->setError(0, '');
        $ok = $obBD_con1->operacionobBD(33, array('Tar_Cod' => $tar, 'Per_Cod' => $perCod), $obBD_conexion);
        if ($ok && $obBD_con1->Error == 0) $asignadas++;
    }
    $resp['success'] = ($asignadas > 0);
    $resp['asignadas'] = $asignadas;
    $resp['message'] = "Se asignaron $asignadas tarea(s).";
    echo json_encode($resp);
    exit;
}

// Ajax: Listar usuarios asignados a una tarea
if (!empty($_REQUEST['listarUsuariosAsignadosTarea'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    $arr = array();
    $tarObservaciones = '';
    $adjuntos = array();
    if ($tarCod > 0) {
        $arr = $obBD_con1->getArrayConsulta(72, array('Tar_Cod' => $tarCod), $obBD_conexion);
        if ($obBD_con1->Error != 0) {
            $obBD_con1->setError(0, '');
            $arr = $obBD_con1->getArrayConsulta(82, array('Tar_Cod' => $tarCod), $obBD_conexion);
        }
        if (!is_array($arr)) $arr = array();
        $rowTar = $obBD_con1->getRowConsulta(78, array('Tar_Cod' => $tarCod), $obBD_conexion);
        $tarObservaciones = isset($rowTar['Tar_Comentario_Supervisor']) ? $rowTar['Tar_Comentario_Supervisor'] : (isset($rowTar['Tar_Observaciones']) ? $rowTar['Tar_Observaciones'] : '');
        $adjuntos = $obBD_con1->getArrayConsulta(40, array('Tar_Cod' => $tarCod), $obBD_conexion);
        if (!is_array($adjuntos)) $adjuntos = array();
    }
    echo json_encode(array('rows' => $arr, 'Tar_Observaciones' => $tarObservaciones, 'adjuntos' => $adjuntos));
    exit;
}

// Ajax: Guardar comentario de tarea (supervisor - visible para el responsable)
if (!empty($_REQUEST['actualizarComentarioTarea'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'message' => '');
    $tarCod = isset($_POST['Tar_Cod']) ? intval($_POST['Tar_Cod']) : 0;
    $obs = isset($_POST['Tar_Observaciones']) ? trim($_POST['Tar_Observaciones']) : '';
    if ($tarCod <= 0) {
        $resp['message'] = 'Tarea inválida.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(79, array('Tar_Cod' => $tarCod, 'Tar_Observaciones' => $obs), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    $resp['message'] = $resp['success'] ? 'Comentario guardado. El responsable verá este mensaje en Mis tareas.' : ($obBD_con1->getMsgError() ?: 'Error al guardar.');
    echo json_encode($resp);
    exit;
}

// Ajax: Eliminar tarea despacho
if (!empty($_REQUEST['eliminarTareaDespacho'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'message' => '');
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    if ($tarCod <= 0) {
        $resp['message'] = 'Tarea inválida.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->setError(0, '');
    $obBD_con1->operacionobBD(75, array('Tar_Cod' => $tarCod), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    $resp['message'] = $resp['success'] ? 'Tarea eliminada.' : ($obBD_con1->getMsgError() ?: 'Error al eliminar.');
    echo json_encode($resp);
    exit;
}

// Ajax: Actualizar fecha límite de tarea
if (!empty($_REQUEST['actualizarFechaLimiteTarea'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'message' => '');
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    $fecLim = isset($_REQUEST['Tar_Fecha_Limite']) ? trim($_REQUEST['Tar_Fecha_Limite']) : '';
    if ($tarCod <= 0) {
        $resp['message'] = 'Tarea inválida.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->setError(0, '');
    $obBD_con1->operacionobBD(74, array('Tar_Cod' => $tarCod, 'Tar_Fecha_Limite' => $fecLim), $obBD_conexion);
    $resp['success'] = ($obBD_con1->Error == 0);
    $resp['message'] = $resp['success'] ? 'Fecha actualizada.' : ($obBD_con1->getMsgError() ?: 'Error al actualizar.');
    echo json_encode($resp);
    exit;
}

// Ajax: Actualizar % de avance (administrador - permite cualquier valor 0-100)
if (!empty($_REQUEST['actualizarPorcentajeAsig'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'message' => '');
    $tarCod = isset($_POST['Tar_Cod']) ? intval($_POST['Tar_Cod']) : 0;
    $perCod = isset($_POST['Per_Cod']) ? intval($_POST['Per_Cod']) : 0;
    $porc = min(100, max(0, intval(isset($_POST['TarUsu_Porcentaje']) ? $_POST['TarUsu_Porcentaje'] : 0)));
    if ($tarCod <= 0 || $perCod <= 0) {
        $resp['message'] = 'Datos inválidos.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->setError(0, '');
    $obBD_con1->operacionobBD(38, array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod, 'TarUsu_Porcentaje' => $porc, 'TarUsu_Observacion' => ''), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $obBD_con1->setError(0, '');
        $obBD_con1->operacionobBD(80, array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod, 'TarUsu_Porcentaje' => $porc), $obBD_conexion);
    }
    $resp['success'] = ($obBD_con1->Error == 0);
    $resp['message'] = $resp['success'] ? 'Porcentaje actualizado.' : ($obBD_con1->getMsgError() ?: 'Error al actualizar.');
    echo json_encode($resp);
    exit;
}

// Ajax: Eliminar asignación tarea-usuario
if (!empty($_REQUEST['eliminarAsignacionTarea'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'message' => '');
    $tarCod = isset($_REQUEST['Tar_Cod']) ? intval($_REQUEST['Tar_Cod']) : 0;
    $perCod = isset($_REQUEST['Per_Cod']) ? intval($_REQUEST['Per_Cod']) : 0;
    if ($tarCod <= 0 || $perCod <= 0) {
        $resp['message'] = 'Datos inválidos.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->setError(0, '');
    $ok = $obBD_con1->operacionobBD(73, array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod), $obBD_conexion);
    $resp['success'] = ($ok && $obBD_con1->Error == 0);
    $resp['message'] = $resp['success'] ? 'Asignación eliminada.' : ($obBD_con1->getMsgError() ?: 'Error al eliminar.');
    echo json_encode($resp);
    exit;
}

// Ajax: Generar tareas (Fecha_Ini/Fecha_Fin = rango libre; o Tar_Periodo; fechasOverride opcional)
if (!empty($_REQUEST['generarTareas'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false, 'generadas' => 0, 'omitidas' => 0, 'asignadas' => 0, 'message' => '');
    $fec_ini_req = trim(isset($_REQUEST['Fecha_Ini']) ? $_REQUEST['Fecha_Ini'] : '');
    $fec_fin_req = trim(isset($_REQUEST['Fecha_Fin']) ? $_REQUEST['Fecha_Fin'] : '');
    $periodo = trim(isset($_REQUEST['Tar_Periodo']) ? $_REQUEST['Tar_Periodo'] : '');
    $perCodAuto = isset($_REQUEST['Per_Cod_Auto']) ? intval($_REQUEST['Per_Cod_Auto']) : 0;
    $fechasOverride = array();
    if (!empty($_REQUEST['fechasOverride']) && is_string($_REQUEST['fechasOverride'])) {
        $dec = json_decode($_REQUEST['fechasOverride'], true);
        if (is_array($dec)) {
            foreach ($dec as $item) {
                if (!empty($item['Cli_Cod']) && !empty($item['Act_Cod']) && !empty($item['Tar_Periodo']) && !empty($item['Tar_Fecha_Limite'])) {
                    $key = (int)$item['Cli_Cod'] . '|' . (int)$item['Act_Cod'] . '|' . trim($item['Tar_Periodo']);
                    $fechasOverride[$key] = trim($item['Tar_Fecha_Limite']);
                }
            }
        }
    }
    $meses_a_procesar = array();
    if ($fec_ini_req !== '' && $fec_fin_req !== '') {
        $fec_ini = $fec_ini_req;
        $fec_fin = $fec_fin_req;
        $meses_a_procesar = aud_meses_en_rango($fec_ini_req, $fec_fin_req);
    } elseif ($periodo !== '') {
        $es_anual = (strlen($periodo) === 4);
        if ($es_anual) {
            $fec_ini = $periodo . '-01-01';
            $fec_fin = $periodo . '-12-31';
            $meses_a_procesar = aud_meses_en_rango($fec_ini, $fec_fin);
        } else {
            $parts = explode('-', $periodo);
            $y = isset($parts[0]) ? $parts[0] : date('Y');
            $m = isset($parts[1]) ? $parts[1] : date('m');
            $fec_ini = $y . '-' . $m . '-01';
            $fec_fin = date('Y-m-t', strtotime($fec_ini));
            $meses_a_procesar = array($periodo);
        }
    }
    if (empty($meses_a_procesar)) {
        $resp['message'] = 'Indique el rango (Desde/Hasta) o el período (ej: 2026-01).';
        echo json_encode($resp);
        exit;
    }
    $par_contratos = array('Emp_Cod' => $Ses_Emp_Cod, 'Fecha_Ini' => $fec_ini, 'Fecha_Fin' => $fec_fin);
    $contratos = $obBD_con1->getArrayConsulta(30, $par_contratos, $obBD_conexion);
    if (!is_array($contratos)) $contratos = array();
    $generadas = 0;
    $omitidas = 0;
    $tareasNuevas = array();
    $primerError = '';
    foreach ($meses_a_procesar as $periodo) {
        $es_anual = (strlen($periodo) === 4);
        if ($es_anual) {
            $fec_ini_m = $periodo . '-01-01';
            $fec_fin_m = $periodo . '-12-31';
        } else {
            $parts = explode('-', $periodo);
            $y = isset($parts[0]) ? $parts[0] : date('Y');
            $m = isset($parts[1]) ? $parts[1] : date('m');
            $fec_ini_m = $y . '-' . $m . '-01';
            $fec_fin_m = date('Y-m-t', strtotime($fec_ini_m));
        }
        foreach ($contratos as $con) {
            $conCod = $con['Con_Cod'];
            $cliCod = $con['Cli_Cod'];
            $cliente = $obBD_con1->getRowConsulta(46, array('Cli_Cod' => $cliCod), $obBD_conexion);
            $ruc_ced = isset($cliente['Ruc_Cedula']) && $cliente['Ruc_Cedula'] !== null && $cliente['Ruc_Cedula'] !== ''
                ? trim((string)$cliente['Ruc_Cedula'])
                : trim((string)(isset($cliente['Cli_Ruf']) ? $cliente['Cli_Ruf'] : ''));
            if ($ruc_ced === '') $ruc_ced = trim((string)(isset($cliente['Prs_Ced']) ? $cliente['Prs_Ced'] : ''));
            $soloDigitos = preg_replace('/\D/', '', $ruc_ced);
            $digito9 = (strlen($soloDigitos) >= 9) ? substr($soloDigitos, 8, 1) : null;
            $actividades = $obBD_con1->getArrayConsulta(43, array('Con_Cod' => $conCod), $obBD_conexion);
            $actPorDefecto = $obBD_con1->getArrayConsulta(44, array('Con_Cod' => $conCod), $obBD_conexion);
            $todas = array_merge($actividades ?: array(), $actPorDefecto ?: array());
            $vistas = array();
            foreach ($todas as $a) {
                $tipo = isset($a['Act_Tipo']) ? $a['Act_Tipo'] : 'MENSUAL';
                $mesActual = !$es_anual && strlen($periodo) >= 7 ? substr($periodo, 5, 2) : null;
                if ($tipo === 'ANUAL' && !$es_anual) {
                    $meses = array();
                    if (!empty($a['Act_Meses_Anual'])) {
                        foreach (explode(',', $a['Act_Meses_Anual']) as $m) {
                            $m = trim($m);
                            if ($m !== '') $meses[$m] = true;
                        }
                    }
                    if ($mesActual === null || empty($meses) || !isset($meses[$mesActual])) continue;
                } elseif ($tipo !== 'MENSUAL' && $tipo !== 'ANUAL') {
                    continue;
                }
                $actCod = $a['Act_Cod'];
                if (isset($vistas[$actCod])) continue;
                $vistas[$actCod] = true;
                $existe = $obBD_con1->getRowConsulta(32, array('Cli_Cod' => $cliCod, 'Act_Cod' => $actCod, 'Tar_Periodo' => $periodo), $obBD_conexion);
                if (!empty($existe)) {
                    $omitidas++;
                    continue;
                }
                $usa_ruc = isset($a['Act_Usa_Ruc']) && $a['Act_Usa_Ruc'] === 'S';
                $fec_lim = null;
                if ($usa_ruc && $digito9 !== null) {
                    $fec_lim = calcularFechaLimiteRuc($periodo, $tipo, $digito9, $obBD_con1, $obBD_conexion);
                }
                if ($fec_lim === null) {
                    if ($tipo === 'MENSUAL' && !$es_anual) $fec_lim = $fec_fin_m;
                    elseif ($tipo === 'ANUAL') $fec_lim = $fec_fin_m;
                    elseif ($tipo === 'EVENTUAL') $fec_lim = $fec_fin_m;
                }
                $keyOverride = $cliCod . '|' . $actCod . '|' . $periodo;
                if (isset($fechasOverride[$keyOverride])) {
                    $fec_lim = $fechasOverride[$keyOverride];
                }
                $obBD_con1->setError(0, '');
                $ok = $obBD_con1->operacionobBD(31, array(
                    'Cli_Cod' => $cliCod,
                    'Act_Cod' => $actCod,
                    'Tar_Periodo' => $periodo,
                    'Tar_Fecha_Limite' => $fec_lim,
                    'Con_Cod' => $conCod,
                    'Emp_Cod' => $Ses_Emp_Cod
                ), $obBD_conexion);
                if ($ok && $obBD_con1->Error == 0) {
                    $generadas++;
                    $tarCod = $obBD_con1->insercionid($obBD_conexion);
                    if ($tarCod > 0) $tareasNuevas[] = $tarCod;
                } elseif ($primerError === '' && $obBD_con1->Error != 0) {
                    $primerError = $obBD_con1->getMsgError();
                }
            }
        }
    }
    $asignadas = 0;
    if ($perCodAuto > 0 && !empty($tareasNuevas)) {
        foreach ($tareasNuevas as $tar) {
            $obBD_con1->setError(0, '');
            $ok = $obBD_con1->operacionobBD(33, array('Tar_Cod' => $tar, 'Per_Cod' => $perCodAuto), $obBD_conexion);
            if ($ok && $obBD_con1->Error == 0) $asignadas++;
        }
    }
    $resp['success'] = ($generadas > 0 || $omitidas > 0);
    $resp['generadas'] = $generadas;
    $resp['omitidas'] = $omitidas;
    $resp['asignadas'] = $asignadas;
    $resp['contratos_encontrados'] = count($contratos);
    $resp['message'] = "Se generaron $generadas tareas. Se omitieron $omitidas por duplicado.";
    if ($asignadas > 0) $resp['message'] .= " Se auto-asignaron $asignadas al personal seleccionado.";
    if ($primerError !== '') {
        $resp['message'] .= " Error BD: " . $primerError;
        $resp['success'] = false;
    } elseif ($generadas === 0 && $omitidas === 0) {
        $resp['message'] .= " Revise la configuración (ver recuadro abajo).";
    }
    echo json_encode($resp);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Generador de Tareas</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <link href="aud_zoom.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <?php require_once('../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <style>
        .exa-header { background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 10px 20px; border-radius: 10px; box-shadow: 0 4px 14px rgba(44,93,148,0.25); }
        .exa-header h3 { margin: 0; font-size: 18px; font-weight: 600; }
        .config-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .config-header { background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%); color: white; padding: 6px 14px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; font-weight: 500; font-size: 14px; }
        .config-header h4 { margin: 0; font-size: 14px; font-weight: 600; }
        .aud-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
        .aud-tabla thead th { background: linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%); color: white; padding: 8px 10px; text-align: left; }
        .aud-tabla tbody td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; }
        .aud-tabla tbody tr.ya-existe { background: #fef3c7; }
        .aud-tabla tbody tr.nueva { background: #ecfdf5; }
        .aud-tabla tbody tr.asig-sel { background: #dbeafe !important; }
        .asig-filtros { margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; position: relative; }
        .asig-filtros .form-group { margin-bottom: 0; position: relative; overflow: visible; }
        /* Forzar que el desplegable Select2 se abra siempre hacia abajo */
        .asig-filtros .select2-dropdown--above { bottom: auto !important; top: 100% !important; }
        /* Tabs estilo Admin (Individual, Por empresa, Por actividad) */
        .asig-tabs .nav-tabs { border-bottom: 2px solid #e2e8f0; margin: 0 0 12px 0; padding: 10px 16px 0 16px; background: #f8fafc; border-radius: 12px 12px 0 0; }
        .asig-tabs .nav-tabs > li { margin-bottom: -2px; margin-right: 4px; }
        .asig-tabs .nav-tabs > li > a { color: #475569; font-weight: 600; font-size: 13px; padding: 8px 16px; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 8px 8px 0 0; background: #e2e8f0; transition: all 0.2s ease; text-decoration: none; }
        .asig-tabs .nav-tabs > li > a:hover { background: #DEE7EF; color: #2C5D94; border-color: #cbd5e1; }
        .asig-tabs .nav-tabs > li.active > a, .asig-tabs .nav-tabs > li.active > a:hover, .asig-tabs .nav-tabs > li.active > a:focus { background: #3d7bb8; color: white; border-color: #2C5D94; }
        .asig-tabs .tab-content { padding: 20px 0 0 0; }
        .asig-tabs.tabs-principal { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04); overflow: hidden; }
        .asig-tabs.tabs-principal .nav-tabs { margin: 0; }
        /* Badges estado tarea */
        .estado-tarea-badge { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .estado-tarea-badge.tarea-pendiente { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #78350f; }
        .estado-tarea-badge.tarea-asignada { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e3a8a; }
        .estado-tarea-badge.tarea-en-proceso { background: linear-gradient(135deg, #93c5fd 0%, #60a5fa 100%); color: #172554; }
        .estado-tarea-badge.tarea-finalizada { background: linear-gradient(135deg, #86efac 0%, #4ade80 100%); color: #14532d; }
        .estado-tarea-badge.tarea-vencida { background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%); color: #7f1d1d; }
        .estado-tarea-badge.tarea-observada { background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%); color: #7c2d12; }
        .aud-tabla td.col-asignados { line-height: 1.5; white-space: normal; }
        /* Botón Modificar en asignación - mismo estilo que otros módulos (verde, icono lápiz) */
        .aud-tabla .btnModificarAsig.btn-editar-modificar { background: linear-gradient(180deg, #5cb85c 0%, #6fc76f 50%, #4cae4c 100%) !important; color: white !important; border: none !important; border-radius: 6px !important; padding: 0 !important; width: 28px !important; height: 28px !important; min-width: 28px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3); }
        .aud-tabla .btnModificarAsig .glyphicon-pencil { display: inline-block !important; width: 14px !important; height: 14px !important; background: url("../../mascaras/model1/imagenes/32x32/glyphicons-halflings-white.png") 0 -72px no-repeat !important; font-size: 0 !important; color: transparent !important; }
        .aud-tabla td.col-fecha-limite .input-fecha-limite { max-width: 150px; }
        /* Generar tareas: alineación a la izquierda; Mes + Desde + Hasta unidos */
        #tab-generar .config-card .form-horizontal { text-align: left; }
        #tab-generar .generar-fila-fechas { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: flex-start; gap: 10px; margin-bottom: 12px; }
        #tab-generar .generar-bloque-fecha { display: flex; flex-direction: column; gap: 4px; text-align: left; }
        #tab-generar .generar-bloque-fecha label { margin: 0; font-weight: 600; color: #333; font-size: 12px; text-align: left; }
        #tab-generar .generar-fecha-mes { width: 150px; max-width: 150px; }
        #tab-generar .generar-fecha-desde-hasta { width: 140px; max-width: 140px; }
        #tab-generar .generar-fila-fechas .generar-botones-fila { margin-left: 8px; }
        /* Cliente y auto-asignar alineados a la izquierda */
        #tab-generar .form-group .control-label { text-align: left; }
        #tab-generar .generar-zona-cliente .control-label { width: auto; padding-right: 10px; }
        /* Modal flotante Tarea eventual */
        .modal-eventual-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            z-index: 1050;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-eventual-backdrop.modal-eventual-abierto {
            display: flex;
        }
        .modal-eventual-contenido {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-width: 520px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            z-index: 1051;
        }
        .modal-eventual-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            background: #fff;
        }
        .modal-eventual-header h5 { margin: 0; color: #2C5D94; font-weight: 600; font-size: 16px; }
        .modal-eventual-cerrar {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 24px;
            line-height: 1;
            padding: 0 4px;
            cursor: pointer;
            border-radius: 6px;
            transition: color 0.2s, background 0.2s;
        }
        .modal-eventual-cerrar:hover { color: #2C5D94; background: rgba(44,93,148,0.15); }
        .modal-eventual-body { padding: 24px; overflow-y: auto; flex: 1; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="exa-header"><h3>&raquo; Generador de Tareas por Período</h3></div>

    <div class="asig-tabs tabs-principal" style="margin-top: 20px;">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#tab-generar" data-toggle="tab"><span class="glyphicon glyphicon-plus"></span> Generar tareas</a></li>
        <li><a href="#tab-asignacion" data-toggle="tab"><span class="glyphicon glyphicon-user"></span> Asignación</a></li>
    </ul>
    <div class="tab-content" style="padding: 20px; background: #E8F0F7; border-radius: 0 0 12px 12px;">
        <div role="tabpanel" class="tab-pane active" id="tab-generar">
    <div class="config-card">
        <div class="config-header"><h4>Generar tareas</h4></div>
        <p class="text-muted">Genera tareas de tipo <strong>mensual</strong> para todos los clientes activos del despacho con contrato vigente en el período indicado. Solo se incluyen actividades de tipo MENSUAL. No se generan duplicados (mismo cliente + actividad + período).</p>
        <div class="form-horizontal">
            <div class="form-group generar-fila-fechas">
                <div class="generar-bloque-fecha">
                    <label for="periodoGenerar" class="control-label">Mes de referencia</label>
                    <input type="month" id="periodoGenerar" class="form-control input-sm generar-fecha-mes" placeholder="2026-01" title="Opcional: elija un mes para rellenar Desde/Hasta autom\u00e1ticamente" />
                </div>
                <div class="generar-bloque-fecha">
                    <label for="fechaDesdeGenerar" class="control-label">Desde <span class="text-danger">*</span></label>
                    <input type="date" id="fechaDesdeGenerar" class="form-control input-sm generar-fecha-desde-hasta" title="Fecha inicio del rango" />
                </div>
                <div class="generar-bloque-fecha">
                    <label for="fechaHastaGenerar" class="control-label">Hasta <span class="text-danger">*</span></label>
                    <input type="date" id="fechaHastaGenerar" class="form-control input-sm generar-fecha-desde-hasta" title="Fecha fin del rango (puede ser varios meses)" />
                </div>
                <div class="generar-botones-fila">
                    <button type="button" class="btn btn-info btn-sm" id="btnVistaPrevia"><i class="glyphicon glyphicon-eye-open"></i> Vista previa</button>
                    <button type="button" class="btn btn-success btn-sm" id="btnGenerarTareas"><i class="glyphicon glyphicon-plus"></i> Generar tareas</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModalEventual" style="margin-left:6px;"><i class="glyphicon glyphicon-flash"></i> Tarea eventual</button>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-12">
                    <span class="text-muted small">Rango libre. Use &quot;Mes de referencia&quot; para rellenar un solo mes; luego puede ampliar el rango.</span>
                </div>
            </div>
            <div class="form-group generar-zona-cliente">
                <label class="col-sm-1 control-label">Cliente</label>
                <div class="col-sm-4">
                    <select id="filtroClienteGenerar" class="form-control input-sm" title="Filtrar o generar solo para este cliente"><option value="">-- Todos los clientes --</option></select>
                </div>
                <div class="col-sm-5"><span class="text-muted small">Elija un cliente o &quot;Todos&quot;. Pulse Vista previa para cargar la lista.</span></div>
            </div>
            <div class="form-group generar-zona-cliente">
                <div class="col-sm-offset-1 col-sm-10">
                    <label class="checkbox-inline">
                        <input type="checkbox" id="chkAutoAsignar" /> Auto-asignar al generar
                    </label>
                    <span id="wrapComboAutoAsig" style="margin-left: 12px; display: none;">
                        <select id="comboPerAutoAsig" class="form-control input-sm" style="width: 200px; display: inline-block;"><option value="">-- Seleccione --</option></select>
                    </span>
                </div>
            </div>
        </div>
        <div id="vistaPreviaTareas" style="margin-top: 20px; display: none;">
            <h5 style="margin-bottom: 10px;"><span class="glyphicon glyphicon-list"></span> Tareas a generar (vista previa)</h5>
            <div id="vistaPreviaResumen" class="text-muted" style="margin-bottom: 8px; font-size: 12px;"></div>
            <div style="max-height: 320px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px;">
                <table id="tablaVistaPrevia" class="aud-tabla"><thead><tr><th>Cliente</th><th>Servicio</th><th>Actividad</th><th>Período</th><th>Fecha límite</th><th>Estado</th><th>Estado tarea</th><th style="width:40px;"></th></tr></thead><tbody></tbody></table>
            </div>
        </div>
        <div id="resultadoGeneracion" style="margin-top: 15px; display: none;"></div>
        <div id="ayudaNoGenera" class="config-card" style="margin-top: 20px; display: none;">
            <div class="config-header"><h4><span class="glyphicon glyphicon-info-sign"></span> ¿Por qu&eacute; no se generan tareas?</h4></div>
            <p class="text-muted">Para que se generen tareas, debe cumplir <strong>todos</strong> estos pasos:</p>
            <ol style="line-height: 1.8;">
                <li><strong>Administraci&oacute;n</strong> (<a href="aud_mod_despacho_admin_1.0.php">Admin</a>): Tener clientes del despacho con estado <strong>ACTIVO</strong>, asignar régimen y tipo de empresa.</li>
                <li><strong>Contratos</strong> (<a href="aud_mod_despacho_contratos_1.0.php">Contratos</a>): Crear contratos con estado <strong>VIGENTE</strong>.</li>
                <li><strong>Precios</strong> (<a href="aud_mod_despacho_precios_1.0.php">Precios por actividad</a>): Definir precios Pequeño/Mediano/Grande por actividad.</li>
                <li><strong>Fechas del contrato</strong>: La fecha inicio debe ser <strong>antes o igual</strong> al final del per&iacute;odo, y la fecha fin <strong>despu&eacute;s o igual</strong> al inicio. Ej: para 2026-01 el contrato debe cubrir entre 2026-01-01 y 2026-01-31.</li>
                <li><strong>Configurar servicios</strong>: En Contratos, pesta&ntilde;a &quot;Configurar servicios&quot;, seleccionar el contrato y marcar las actividades que aplican (o agregar servicios con sus actividades).</li>
            </ol>
            <p class="text-muted"><strong>Ejemplo:</strong> Si su contrato va del 2026-02-01 al 2030-12-31, use per&iacute;odo <strong>2026-02</strong> (no 2026-01).</p>
        </div>

        <!-- Modal Generar tarea eventual (individual) -->
        <div id="modalEventual" class="modal-eventual-backdrop">
            <div class="modal-eventual-contenido">
                <div class="modal-eventual-header">
                    <h5><i class="glyphicon glyphicon-flash"></i> Generar tarea eventual (individual)</h5>
                    <button type="button" class="modal-eventual-cerrar" id="btnCerrarModalEventual" title="Cerrar">&times;</button>
                </div>
                <div class="modal-eventual-body">
                    <p class="text-muted small" style="margin-top:0;">Use este formulario para crear tareas de tipo <strong>EVENTUAL</strong> para un solo cliente y período. No afecta al generador masivo mensual/anual.</p>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Período <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="month" id="periodoEventual" class="form-control input-sm" placeholder="2026-03" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Cliente <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <select id="clienteEventual" class="form-control input-sm"><option value="">-- Seleccione --</option></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Actividad eventual <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <select id="actividadEventual" class="form-control input-sm"><option value="">-- Seleccione cliente primero --</option></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Fecha límite</label>
                            <div class="col-sm-8">
                                <input type="date" id="fechaLimiteEventual" class="form-control input-sm" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <span class="text-muted small">Si deja la fecha límite en blanco se usará el último día del mes del período.</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12" style="margin-top:12px;">
                                <button type="button" class="btn btn-primary btn-sm" id="btnGenerarTareaEventual"><i class="glyphicon glyphicon-plus"></i> Generar tarea eventual</button>
                                <button type="button" class="btn btn-default btn-sm" id="btnCancelarEventual" style="margin-left:8px;">Cancelar</button>
                            </div>
                        </div>
                    </div>
                    <div id="resultadoTareaEventual" style="margin-top: 12px;"></div>
                </div>
            </div>
        </div>
    </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="tab-asignacion">
    <div class="config-card">
        <div class="config-header"><h4><span class="glyphicon glyphicon-user"></span> Asignación de tareas</h4></div>
        <p class="text-muted">Asigne tareas al personal: individual, por empresa (todas las tareas de un cliente) o por actividad (todas las empresas con esa actividad).</p>
        <div class="asig-filtros">
            <div class="form-group">
                <label>Mes de referencia</label>
                <input type="month" id="periodoAsig" class="form-control input-sm" placeholder="2026-02" style="width: 130px;" title="Opcional: rellena Desde/Hasta" />
            </div>
            <div class="form-group">
                <label>Desde <span class="text-danger">*</span></label>
                <input type="date" id="fechaDesdeAsig" class="form-control input-sm" style="width: 140px;" title="Fecha inicio del rango" />
            </div>
            <div class="form-group">
                <label>Hasta <span class="text-danger">*</span></label>
                <input type="date" id="fechaHastaAsig" class="form-control input-sm" style="width: 140px;" title="Fecha fin del rango (puede ser varios meses)" />
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary btn-sm" id="btnCargarAsig"><i class="glyphicon glyphicon-refresh"></i> Cargar</button>
            </div>
        </div>
        <p class="text-muted small" style="margin: -8px 0 12px 0;">Use el mes de referencia para rellenar Desde/Hasta autom\u00e1ticamente; luego puede ampliar el rango.</p>
        <div class="asig-tabs">
        <ul class="nav nav-tabs" role="tablist">
            <li class="active"><a href="#tab-asig-individual" data-toggle="tab"><span class="glyphicon glyphicon-user"></span> Individual</a></li>
            <li><a href="#tab-asig-empresa" data-toggle="tab"><span class="glyphicon glyphicon-briefcase"></span> Por empresa</a></li>
            <li><a href="#tab-asig-actividad" data-toggle="tab"><span class="glyphicon glyphicon-tasks"></span> Por actividad</a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="tab-asig-individual">
                <div class="asig-filtros">
                    <div class="form-group">
                        <label>Cliente</label>
                        <select id="comboCliIndividual" class="form-control input-sm" style="min-width: 220px;"><option value="">-- Todos --</option></select>
                    </div>
                    <div class="form-group">
                        <label>Actividad</label>
                        <select id="comboActIndividual" class="form-control input-sm" style="min-width: 200px;"><option value="">-- Todas --</option></select>
                    </div>
                    <div class="form-group">
                        <label>Asignar a</label>
                        <select id="comboPerIndividual" class="form-control input-sm" style="min-width: 180px;"><option value="">-- Seleccione --</option></select>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-success btn-sm" id="btnAsigIndividual"><i class="glyphicon glyphicon-ok"></i> Asignar</button>
                    </div>
                </div>
                <div style="max-height: 280px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; margin-top: 10px;">
                    <table id="tablaTareasIndividual" class="aud-tabla"><thead><tr><th></th><th>Cliente</th><th>Actividad</th><th>Servicio</th><th>Fecha límite</th><th>Estado tarea</th><th>Asignados</th><th></th></tr></thead><tbody></tbody></table>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab-asig-empresa">
                <div class="asig-filtros">
                    <div class="form-group">
                        <label>Cliente <span class="text-danger">*</span></label>
                        <select id="comboCliEmpresa" class="form-control input-sm" style="min-width: 260px;"><option value="">-- Seleccione cliente --</option></select>
                    </div>
                    <div class="form-group">
                        <label>Asignar todas las tareas a</label>
                        <select id="comboPerEmpresa" class="form-control input-sm" style="min-width: 180px;"><option value="">-- Seleccione --</option></select>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-success btn-sm" id="btnAsigEmpresa"><i class="glyphicon glyphicon-ok"></i> Asignar todas</button>
                    </div>
                </div>
                <div style="max-height: 280px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; margin-top: 10px;">
                    <table id="tablaTareasEmpresa" class="aud-tabla"><thead><tr><th></th><th>Cliente</th><th>Actividad</th><th>Servicio</th><th>Fecha límite</th><th>Estado tarea</th><th>Asignados</th><th></th></tr></thead><tbody></tbody></table>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab-asig-actividad">
                <div class="asig-filtros">
                    <div class="form-group">
                        <label>Actividad <span class="text-danger">*</span></label>
                        <select id="comboActActividad" class="form-control input-sm" style="min-width: 240px;"><option value="">-- Seleccione actividad --</option></select>
                    </div>
                    <div class="form-group">
                        <label>Clientes</label>
                        <select id="comboCliActividad" class="form-control input-sm" style="min-width: 220px;"><option value="">-- Todos --</option></select>
                    </div>
                    <div class="form-group">
                        <label>Asignar a</label>
                        <select id="comboPerActividad" class="form-control input-sm" style="min-width: 180px;"><option value="">-- Seleccione --</option></select>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-success btn-sm" id="btnAsigActividad"><i class="glyphicon glyphicon-ok"></i> Asignar</button>
                    </div>
                </div>
                <div style="max-height: 280px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; margin-top: 10px;">
                    <table id="tablaTareasActividad" class="aud-tabla"><thead><tr><th></th><th>Cliente</th><th>Actividad</th><th>Servicio</th><th>Fecha límite</th><th>Estado tarea</th><th>Asignados</th><th></th></tr></thead><tbody></tbody></table>
                </div>
            </div>
        </div>
        </div>
        <div id="modalAsignados" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><span class="glyphicon glyphicon-user"></span> Usuarios asignados a la tarea</h4>
                    </div>
                    <div class="modal-body">
                        <p id="modalAsigTareaInfo" class="text-muted"></p>
                        <ul id="listaUsuariosAsignados" class="list-group"></ul>
                        <p id="modalAsigEmpty" class="text-muted" style="display:none;">No hay usuarios asignados.</p>
                        <div id="modalAsigObsImagenes" style="display:none; margin-top: 20px; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <h5 class="text-primary" style="margin-top: 0; margin-bottom: 12px;"><span class="glyphicon glyphicon-picture"></span> Imágenes adjuntas (subidas por el responsable)</h5>
                            <div id="modalAsigAdjuntosLista" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                            <p id="modalAsigSinAdjuntos" class="text-muted small" style="display:none; margin: 8px 0 0;">El responsable no ha subido imágenes aún.</p>
                        </div>
                        <div class="form-group" style="margin-top: 20px; margin-bottom: 0;">
                            <label class="control-label"><span class="glyphicon glyphicon-comment"></span> Comentario para el responsable</label>
                            <p class="text-muted small">Este mensaje será visible para el usuario asignado en su pestaña &#8220;Mis tareas&#8221;. Escriba aquí su retroalimentación en base a lo que subió.</p>
                            <textarea id="modalAsigComentario" class="form-control input-sm" rows="3" placeholder="Escriba instrucciones, observaciones o comentarios para el responsable de la tarea..." style="width:100%;"></textarea>
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardarComentarioTarea" style="margin-top: 8px;"><i class="glyphicon glyphicon-ok"></i> Guardar comentario</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script type="text/javascript">
$(function () {
    var urlBase = '<?php echo str_replace("'", "\\'", $_SERVER['PHP_SELF']); ?>';
    var listaPersonal = [];
    var listaActividades = [];
    var listaTareasAsig = [];
    var listaClientesAsig = [];
    var select2Opts = { language: { noResults: function() { return 'No se encontraron resultados'; }, searching: function() { return 'Buscando...'; } }, allowClear: true };
    function initSelect2Buscable($el, extra) {
        if (!$el.length || typeof $el.select2 !== 'function') return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2(extra ? $.extend(true, {}, select2Opts, extra) : select2Opts);
        $el.off('select2:open.s2nc select2:selecting.s2nc').on('select2:open.s2nc', function () { $el.data('select2OpenAt', Date.now()); }).on('select2:selecting.s2nc', function (ev) {
            var t = $el.data('select2OpenAt'); if (t && (Date.now() - t) < 300) ev.preventDefault();
        });
    }

    function llenarComboPersonal($sel) {
        var opts = '<option value="">-- Seleccione --</option>';
        $.each(listaPersonal, function (i, p) {
            var nom = (p.Nombre || p.Personal_Nombre || p.Prs_Nom || '').trim();
            if (!nom && p.Prs_Ape) nom = (p.Prs_Ape + ' ' + (p.Prs_Nom || '')).trim();
            opts += '<option value="' + (p.Per_Cod || p.Usu_Cod || '') + '">' + (nom || 'Sin nombre') + '</option>';
        });
        $sel.html(opts);
    }

    function llenarComboActividades($sel, optTodos) {
        var opts = optTodos ? '<option value="">-- Todas --</option>' : '<option value="">-- Seleccione actividad --</option>';
        $.each(listaActividades, function (i, a) {
            opts += '<option value="' + (a.Act_Cod || '') + '">' + (a.Act_Nombre || '') + ' (' + (a.Ser_Nombre || '') + ')</option>';
        });
        $sel.html(opts);
    }

    // Paso 2: Cargar personal al iniciar (para asignación)
    $.get(urlBase, { listarPersonalAsig: 1 }, function (r) {
        listaPersonal = r.rows || [];
        llenarComboPersonal($('#comboPerIndividual'));
        llenarComboPersonal($('#comboPerEmpresa'));
        llenarComboPersonal($('#comboPerActividad'));
        llenarComboPersonal($('#comboPerAutoAsig'));
        initSelect2Buscable($('#comboPerIndividual'), { width: '280px', dropdownParent: $('#comboPerIndividual').closest('.form-group') });
        initSelect2Buscable($('#comboPerEmpresa'), { width: '180px', dropdownParent: $('#comboPerEmpresa').closest('.form-group') });
        initSelect2Buscable($('#comboPerActividad'), { width: '180px', dropdownParent: $('#comboPerActividad').closest('.form-group') });
        initSelect2Buscable($('#comboPerAutoAsig'), { width: '200px' });
    }, 'json').fail(function () {
        if (typeof console !== 'undefined') console.warn('No se pudo cargar personal (listarPersonalAsig)');
    });

    // Paso 3: Cargar actividades al iniciar (para asignación)
    $.get(urlBase, { listarActividadesAsig: 1 }, function (r) {
        listaActividades = r.rows || [];
        llenarComboActividades($('#comboActIndividual'), true);
        llenarComboActividades($('#comboActActividad'), false);
        initSelect2Buscable($('#comboActIndividual'), { width: '280px', dropdownParent: $('#comboActIndividual').closest('.form-group') });
        initSelect2Buscable($('#comboActActividad'), { width: '240px', dropdownParent: $('#comboActActividad').closest('.form-group') });
    }, 'json').fail(function () {
        if (typeof console !== 'undefined') console.warn('No se pudo cargar actividades (listarActividadesAsig)');
    });

    function actualizarFechasAsigDesdeMes() {
        var per = $('#periodoAsig').val().trim();
        if (!per || per.length < 6) return;
        var p = per.split('-'), y = parseInt(p[0], 10), m = parseInt(p[1], 10);
        if (!m || m < 1 || m > 12) return;
        var fecIni = y + '-' + (m < 10 ? '0' + m : m) + '-01';
        var ultimoDia = new Date(y, m, 0).getDate();
        var fecFin = y + '-' + (m < 10 ? '0' + m : m) + '-' + (ultimoDia < 10 ? '0' + ultimoDia : ultimoDia);
        $('#fechaDesdeAsig').val(fecIni);
        $('#fechaHastaAsig').val(fecFin);
    }
    $('#periodoAsig').on('change input', actualizarFechasAsigDesdeMes);
    // Valor por defecto: mes actual (Asignación)
    (function () {
        var h = new Date();
        var m = h.getMonth() + 1;
        var mes = h.getFullYear() + '-' + (m < 10 ? '0' + m : '' + m);
        $('#periodoAsig').val(mes);
        actualizarFechasAsigDesdeMes();
    })();

    // Paso 5-6: Cargar tareas para asignación (rango Desde/Hasta o período)
    $('#btnCargarAsig').on('click', function () {
        var desde = $('#fechaDesdeAsig').val().trim();
        var hasta = $('#fechaHastaAsig').val().trim();
        var per = $('#periodoAsig').val().trim();
        if (!desde || !hasta) {
            if (per && per.length >= 6) {
                actualizarFechasAsigDesdeMes();
                desde = $('#fechaDesdeAsig').val();
                hasta = $('#fechaHastaAsig').val();
            }
            if (!desde || !hasta) {
                alert('Indique el rango (Desde y Hasta) o elija un mes de referencia para rellenarlas.');
                return;
            }
        }
        if (desde > hasta) { alert('La fecha Desde no puede ser mayor que Hasta.'); return; }
        var $btn = $(this);
        $btn.prop('disabled', true);
        var params = { listarTareasAsignacion: 1, Fecha_Ini: desde, Fecha_Fin: hasta };
        $.get(urlBase, params, function (r) {
            listaTareasAsig = r.rows || [];
            var clientesUnicos = {};
            $.each(listaTareasAsig, function (i, row) {
                if (row.Cli_Cod != null && row.Cliente_Nombre != null) clientesUnicos[row.Cli_Cod] = row.Cliente_Nombre;
            });
            var opts = '<option value="">-- Todos --</option>';
            $.each(clientesUnicos, function (cod, nom) {
                opts += '<option value="' + cod + '">' + (nom || '') + '</option>';
            });
            $('#comboCliIndividual').html(opts);
            var opts2 = '<option value="">-- Seleccione cliente --</option>';
            $.each(clientesUnicos, function (cod, nom) {
                opts2 += '<option value="' + cod + '">' + (nom || '') + '</option>';
            });
            $('#comboCliEmpresa').html(opts2);
            $('#comboCliActividad').html(opts);
            initSelect2Buscable($('#comboCliIndividual'), { width: '300px', dropdownParent: $('#comboCliIndividual').closest('.form-group') });
            initSelect2Buscable($('#comboCliEmpresa'), { width: '260px', dropdownParent: $('#comboCliEmpresa').closest('.form-group') });
            initSelect2Buscable($('#comboCliActividad'), { width: '220px', dropdownParent: $('#comboCliActividad').closest('.form-group') });
            renderTablaIndividual(listaTareasAsig);
            var cliEmp = $('#comboCliEmpresa').val();
            if (cliEmp) {
                var rowsEmp = listaTareasAsig.filter(function (r) { return String(r.Cli_Cod) === String(cliEmp); });
                renderTablaEmpresa(rowsEmp, true);
            } else {
                renderTablaEmpresa([], false);
            }
            $btn.prop('disabled', false);
        }, 'json').fail(function () {
            $btn.prop('disabled', false);
        });
    });

    function estadoTareaBadgeAsig(tarEst, cntUsuarios) {
        var map = { 'PENDIENTE': ['tarea-pendiente', 'Pendiente'], 'ASIGNADA': ['tarea-asignada', 'Asignada'], 'EN_PROCESO': ['tarea-en-proceso', 'En proceso'], 'FINALIZADA': ['tarea-finalizada', 'Finalizada'], 'VENCIDA': ['tarea-vencida', 'Vencida'], 'OBSERVADA': ['tarea-observada', 'Observada'] };
        var est = (tarEst || '').toUpperCase();
        var cnt = parseInt(cntUsuarios || 0, 10);
        if (est === 'PENDIENTE' && cnt > 0) { est = 'ASIGNADA'; }
        var m = map[est] || ['tarea-pendiente', tarEst || 'Pendiente'];
        return '<span class="estado-tarea-badge ' + m[0] + '">' + (m[1] || '-') + '</span>';
    }

    function renderTablaIndividual(rows) {
        var html = '';
        if (!rows || rows.length === 0) {
            html = '<tr><td colspan="8" class="text-muted">No hay tareas. Genere tareas primero o seleccione otro período.</td></tr>';
        } else {
            $.each(rows, function (i, row) {
                var cnt = parseInt(row.Cnt_Usuarios || 0, 10);
                var nombresRaw = (row.Usuarios_Asignados || '').trim() || '-';
                var nombres = nombresRaw === '-' ? '-' : nombresRaw.split(/\s*,\s*/).join('<br />');
                var estFinalizada = (row.Tar_Est || '').toUpperCase() === 'FINALIZADA';
                var btnMod = (cnt > 0 && !estFinalizada) ? '<button type="button" class="btn btn-xs btn-editar-modificar btnModificarAsig" title="Modificar" data-tar="' + (row.Tar_Cod || '') + '" data-cli="' + (row.Cliente_Nombre || '') + '" data-act="' + (row.Act_Nombre || '') + '"><span class="glyphicon glyphicon-pencil"></span></button>' : '-';
                var fecLim = (row.Tar_Fecha_Limite || '').trim();
                if (fecLim === '0000-00-00') fecLim = '';
                var inputFec = '<input type="date" class="form-control input-sm input-fecha-limite" data-tar="' + (row.Tar_Cod || '') + '" value="' + (fecLim || '') + '" style="width:120px; padding:2px 6px; font-size:11px;" />';
                var estadoBadge = estadoTareaBadgeAsig(row.Tar_Est, row.Cnt_Usuarios);
                var radioDisabled = estFinalizada ? ' disabled' : '';
                html += '<tr data-tar="' + (row.Tar_Cod || '') + '" data-finalizada="' + (estFinalizada ? '1' : '0') + '"><td><input type="radio" name="tarInd" value="' + (row.Tar_Cod || '') + '"' + radioDisabled + ' /></td>';
                html += '<td>' + (row.Cliente_Nombre || '') + '</td><td>' + (row.Act_Nombre || '') + '</td><td>' + (row.Ser_Nombre || '') + '</td>';
                html += '<td>' + inputFec + '</td><td>' + estadoBadge + '</td><td class="col-asignados" title="' + (nombresRaw !== '-' ? nombresRaw.replace(/"/g, '&quot;') : '') + '">' + nombres + '</td><td class="col-accion">' + btnMod + '</td></tr>';
            });
        }
        $('#tablaTareasIndividual tbody').html(html);
    }

    $('#comboCliIndividual, #comboActIndividual').on('change', function () {
        var cli = $('#comboCliIndividual').val();
        var act = $('#comboActIndividual').val();
        var rows = listaTareasAsig;
        if (cli) rows = rows.filter(function (r) { return String(r.Cli_Cod) === String(cli); });
        if (act) rows = rows.filter(function (r) { return String(r.Act_Cod) === String(act); });
        renderTablaIndividual(rows);
    });

    function renderTablaEmpresa(rows, hayClienteSel) {
        var html = '';
        if (!rows || rows.length === 0) {
            html = '<tr><td colspan="8" class="text-muted">' + (hayClienteSel ? 'No hay tareas para este cliente en el período.' : 'Seleccione un cliente para ver sus tareas.') + '</td></tr>';
        } else {
            $.each(rows, function (i, row) {
                var cnt = parseInt(row.Cnt_Usuarios || 0, 10);
                var nombresRaw = (row.Usuarios_Asignados || '').trim() || '-';
                var nombres = nombresRaw === '-' ? '-' : nombresRaw.split(/\s*,\s*/).join('<br />');
                var estFinalizada = (row.Tar_Est || '').toUpperCase() === 'FINALIZADA';
                var btnMod = (cnt > 0 && !estFinalizada) ? '<button type="button" class="btn btn-xs btn-editar-modificar btnModificarAsig" title="Modificar" data-tar="' + (row.Tar_Cod || '') + '" data-cli="' + (row.Cliente_Nombre || '') + '" data-act="' + (row.Act_Nombre || '') + '"><span class="glyphicon glyphicon-pencil"></span></button>' : '-';
                var fecLim = (row.Tar_Fecha_Limite || '').trim();
                if (fecLim === '0000-00-00') fecLim = '';
                var inputFec = '<input type="date" class="form-control input-sm input-fecha-limite" data-tar="' + (row.Tar_Cod || '') + '" value="' + (fecLim || '') + '" style="width:120px; padding:2px 6px; font-size:11px;" />';
                var estadoBadge = estadoTareaBadgeAsig(row.Tar_Est, row.Cnt_Usuarios);
                var cbDisabled = estFinalizada ? ' disabled' : '';
                html += '<tr data-tar="' + (row.Tar_Cod || '') + '"><td><input type="checkbox" class="tar-emp-cb" value="' + (row.Tar_Cod || '') + '"' + cbDisabled + ' /></td>';
                html += '<td>' + (row.Cliente_Nombre || '') + '</td><td>' + (row.Act_Nombre || '') + '</td><td>' + (row.Ser_Nombre || '') + '</td>';
                html += '<td>' + inputFec + '</td><td>' + estadoBadge + '</td><td class="col-asignados" title="' + (nombresRaw !== '-' ? nombresRaw.replace(/"/g, '&quot;') : '') + '">' + nombres + '</td><td class="col-accion">' + btnMod + '</td></tr>';
            });
        }
        $('#tablaTareasEmpresa tbody').html(html);
    }

    $('#comboCliEmpresa').on('change', function () {
        var cli = $(this).val();
        if (!cli) {
            renderTablaEmpresa([], false);
            return;
        }
        var rows = listaTareasAsig.filter(function (r) { return String(r.Cli_Cod) === String(cli); });
        renderTablaEmpresa(rows, true);
    });

    $(document).on('change', '.input-fecha-limite', function () {
        var $inp = $(this);
        var tar = $inp.data('tar');
        var fec = $inp.val();
        if (!tar) return;
        $.post(urlBase, { actualizarFechaLimiteTarea: 1, Tar_Cod: tar, Tar_Fecha_Limite: fec || '' }, function (r) {
            if (r && r.success) {
                var idx = listaTareasAsig.findIndex(function (r) { return String(r.Tar_Cod) === String(tar); });
                if (idx >= 0) listaTareasAsig[idx].Tar_Fecha_Limite = fec || null;
            } else {
                alert(r && r.message ? r.message : 'Error al actualizar fecha.');
            }
        }, 'json').fail(function () { alert('Error de conexión.'); });
    });

    $('#comboActActividad').on('change', function () {
        var act = $(this).val();
        if (!act) {
            $('#tablaTareasActividad tbody').html('<tr><td colspan="8" class="text-muted">Seleccione una actividad.</td></tr>');
            $('#comboCliActividad').html('<option value="">-- Todos --</option>');
            return;
        }
        var rows = listaTareasAsig.filter(function (r) { return String(r.Act_Cod) === String(act); });
        var html = '';
        var clientes = {};
        if (rows.length === 0) {
            html = '<tr><td colspan="8" class="text-muted">No hay tareas para esta actividad en el período.</td></tr>';
        } else {
            $.each(rows, function (i, row) {
                clientes[row.Cli_Cod] = row.Cliente_Nombre;
                var cnt = parseInt(row.Cnt_Usuarios || 0, 10);
                var nombresRaw = (row.Usuarios_Asignados || '').trim() || '-';
                var nombres = nombresRaw === '-' ? '-' : nombresRaw.split(/\s*,\s*/).join('<br />');
                var estFinalizada = (row.Tar_Est || '').toUpperCase() === 'FINALIZADA';
                var btnMod = (cnt > 0 && !estFinalizada) ? '<button type="button" class="btn btn-xs btn-editar-modificar btnModificarAsig" title="Modificar" data-tar="' + (row.Tar_Cod || '') + '" data-cli="' + (row.Cliente_Nombre || '') + '" data-act="' + (row.Act_Nombre || '') + '"><span class="glyphicon glyphicon-pencil"></span></button>' : '-';
                var fecLim = (row.Tar_Fecha_Limite || '').trim();
                if (fecLim === '0000-00-00') fecLim = '';
                var inputFec = '<input type="date" class="form-control input-sm input-fecha-limite" data-tar="' + (row.Tar_Cod || '') + '" value="' + (fecLim || '') + '" style="width:120px; padding:2px 6px; font-size:11px;" />';
                var estadoBadge = estadoTareaBadgeAsig(row.Tar_Est, row.Cnt_Usuarios);
                var cbDisabled = estFinalizada ? ' disabled' : '';
                html += '<tr data-tar="' + (row.Tar_Cod || '') + '"><td><input type="checkbox" class="tar-act-cb" value="' + (row.Tar_Cod || '') + '"' + cbDisabled + ' /></td>';
                html += '<td>' + (row.Cliente_Nombre || '') + '</td><td>' + (row.Act_Nombre || '') + '</td><td>' + (row.Ser_Nombre || '') + '</td>';
                html += '<td>' + inputFec + '</td><td>' + estadoBadge + '</td><td class="col-asignados" title="' + (nombresRaw !== '-' ? nombresRaw.replace(/"/g, '&quot;') : '') + '">' + nombres + '</td><td class="col-accion">' + btnMod + '</td></tr>';
            });
        }
        $('#tablaTareasActividad tbody').html(html);
        var opts = '<option value="">-- Todos --</option>';
        $.each(clientes, function (cod, nom) { opts += '<option value="' + cod + '">' + nom + '</option>'; });
        $('#comboCliActividad').html(opts);
    });

    function ejecutarAsignacion(perCod, tarCod, tarCodIn) {
        if (!perCod) { alert('Seleccione el personal a asignar.'); return; }
        var data = { asignarTareas: 1, Per_Cod: perCod };
        if (tarCod) data.Tar_Cod = tarCod;
        if (tarCodIn) data.Tar_Cod_In = tarCodIn;
        $.post(urlBase, data, function (r) {
            if (r && r.success) {
                alert(r.message || 'Asignación realizada.');
                $('#btnCargarAsig').click();
            } else {
                alert(r && r.message ? r.message : 'Error al asignar.');
            }
        }, 'json').fail(function () { alert('Error de conexión.'); });
    }

    $('#btnAsigIndividual').on('click', function () {
        var per = $('#comboPerIndividual').val();
        var tar = $('input[name="tarInd"]:checked').val();
        if (!tar) { alert('Seleccione una tarea en la tabla.'); return; }
        var row = listaTareasAsig.find(function (r) { return String(r.Tar_Cod) === String(tar); });
        if (row && (row.Tar_Est || '').toUpperCase() === 'FINALIZADA') {
            alert('No puede asignar usuarios a una tarea finalizada.');
            return;
        }
        ejecutarAsignacion(per, tar, null);
    });

    $('#btnAsigEmpresa').on('click', function () {
        var per = $('#comboPerEmpresa').val();
        var cli = $('#comboCliEmpresa').val();
        if (!cli) { alert('Seleccione un cliente.'); return; }
        var tareas = listaTareasAsig.filter(function (r) {
            return String(r.Cli_Cod) === String(cli) && (r.Tar_Est || '').toUpperCase() !== 'FINALIZADA';
        }).map(function (r) { return r.Tar_Cod; });
        if (tareas.length === 0) { alert('No hay tareas para asignar. Todas las tareas de este cliente están finalizadas o no hay tareas. Cargue primero.'); return; }
        ejecutarAsignacion(per, null, tareas.join(','));
    });

    $(document).on('click', '.btnModificarAsig', function () {
        var tar = $(this).data('tar');
        var cli = $(this).data('cli');
        var act = $(this).data('act');
        if (!tar) return;
        var urlAdjuntos = '../adjuntos/despacho/';
        $('#modalAsigTareaInfo').text('Tarea: ' + (cli || '') + ' - ' + (act || ''));
        $('#listaUsuariosAsignados').html('<li class="list-group-item text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</li>');
        $('#modalAsigEmpty').hide();
        $('#modalAsigComentario').val('');
        $('#modalAsigObsImagenes').hide();
        $('#modalAsignados').data('tar-cod', tar).modal('show');
        $.get(urlBase, { listarUsuariosAsignadosTarea: 1, Tar_Cod: tar }, function (r) {
            var rows = r.rows || [];
            var adjuntos = r.adjuntos || [];
            var html = '';
            if (rows.length === 0) {
                $('#modalAsigEmpty').show();
            } else {
                $.each(rows, function (i, u) {
                    var nom = (u.Personal_Nombre || 'Sin nombre');
                    var pct = u.TarUsu_Porcentaje != null ? parseInt(u.TarUsu_Porcentaje, 10) : 0;
                    var perCod = u.Per_Cod || '';
                    var obs = (u.TarUsu_Observacion || '').trim();
                    var obsHtml = obs ? '<div class="text-muted small" style="margin-top: 6px; padding: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; white-space: pre-wrap;"><strong>Observación del responsable:</strong><br>' + $('<div>').text(obs).html() + '</div>' : '';
                    html += '<li class="list-group-item" data-tar="' + tar + '" data-per="' + perCod + '"><div><strong>' + $('<div>').text(nom).html() + '</strong> ';
                    html += '<span class="input-group" style="display:inline-flex; margin-left: 8px; vertical-align: middle;"><input type="number" class="form-control input-sm input-avance-asig" min="0" max="100" value="' + pct + '" style="width: 56px; text-align: center;" /> <span style="margin-left: 2px; line-height: 28px;">%</span> <button type="button" class="btn btn-xs btn-primary btnGuardarAvanceAsig" style="margin-left: 6px;" title="Guardar %"><span class="glyphicon glyphicon-ok"></span></button></span> ';
                    html += '<button type="button" class="btn btn-xs btn-danger pull-right btnEliminarAsig" data-tar="' + tar + '" data-per="' + perCod + '"><span class="glyphicon glyphicon-remove"></span> Eliminar</button></div>' + obsHtml + '</li>';
                });
            }
            $('#listaUsuariosAsignados').html(html);
            $('#modalAsigComentario').val(r.Tar_Observaciones || '');
            var $imgSec = $('#modalAsigObsImagenes');
            var $imgLista = $('#modalAsigAdjuntosLista');
            var $sinAdj = $('#modalAsigSinAdjuntos');
            if (adjuntos.length > 0) {
                $imgSec.show();
                $sinAdj.hide();
                var imgHtml = '';
                for (var a = 0; a < adjuntos.length; a++) {
                    var ruta = (adjuntos[a].Adj_Ruta || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var nombre = (adjuntos[a].Adj_Nombre || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    imgHtml += '<a href="' + urlAdjuntos + ruta + '" target="_blank" title="' + nombre + '"><img src="' + urlAdjuntos + ruta + '" alt="" style="max-height: 80px; max-width: 120px; object-fit: contain; border: 1px solid #ccc; border-radius: 4px;" /></a>';
                }
                $imgLista.html(imgHtml).show();
            } else {
                $imgSec.show();
                $imgLista.html('').hide();
                $sinAdj.show();
            }
        }, 'json').fail(function () {
            $('#listaUsuariosAsignados').html('<li class="list-group-item text-danger">Error al cargar.</li>');
        });
    });

    $('#btnGuardarComentarioTarea').on('click', function () {
        var tar = $('#modalAsignados').data('tar-cod');
        var obs = $('#modalAsigComentario').val() || '';
        if (!tar) return;
        $.post(urlBase, { actualizarComentarioTarea: 1, Tar_Cod: tar, Tar_Observaciones: obs }, function (r) {
            if (r && r.success) {
                alert(r.message || 'Comentario guardado.');
            } else {
                alert(r && r.message ? r.message : 'Error al guardar.');
            }
        }, 'json').fail(function () { alert('Error de conexión.'); });
    });

    $(document).on('click', '.btnGuardarAvanceAsig', function () {
        var $li = $(this).closest('li');
        var tar = $li.data('tar');
        var per = $li.data('per');
        var $input = $li.find('.input-avance-asig');
        var porc = parseInt($input.val(), 10);
        if (isNaN(porc) || porc < 0) porc = 0;
        if (porc > 100) porc = 100;
        if (!tar || !per) return;
        $input.prop('disabled', true);
        $.post(urlBase, { actualizarPorcentajeAsig: 1, Tar_Cod: tar, Per_Cod: per, TarUsu_Porcentaje: porc }, function (r) {
            $input.prop('disabled', false);
            if (r && r.success) {
                $input.val(porc);
            } else {
                alert(r && r.message ? r.message : 'Error al guardar.');
            }
        }, 'json').fail(function () {
            $input.prop('disabled', false);
            alert('Error de conexión.');
        });
    });

    $(document).on('click', '.btnEliminarAsig', function () {
        var tar = $(this).data('tar');
        var per = $(this).data('per');
        var $li = $(this).closest('li');
        if (!tar || !per) return;
        if (!confirm('¿Eliminar esta asignación?')) return;
        $.post(urlBase, { eliminarAsignacionTarea: 1, Tar_Cod: tar, Per_Cod: per }, function (r) {
            if (r && r.success) {
                $li.remove();
                if ($('#listaUsuariosAsignados li').length === 0) {
                    $('#modalAsigEmpty').show();
                }
                $('#btnCargarAsig').click();
            } else {
                alert(r && r.message ? r.message : 'Error al eliminar.');
            }
        }, 'json').fail(function () { alert('Error de conexión.'); });
    });

    $('#btnAsigActividad').on('click', function () {
        var per = $('#comboPerActividad').val();
        var act = $('#comboActActividad').val();
        if (!act) { alert('Seleccione una actividad.'); return; }
        var cbs = $('#tablaTareasActividad .tar-act-cb:checked');
        var tareas = [];
        if (cbs.length > 0) {
            cbs.each(function () { tareas.push($(this).val()); });
        } else {
            var rows = listaTareasAsig.filter(function (r) {
                return String(r.Act_Cod) === String(act) && (r.Tar_Est || '').toUpperCase() !== 'FINALIZADA';
            });
            var cli = $('#comboCliActividad').val();
            if (cli) rows = rows.filter(function (r) { return String(r.Cli_Cod) === String(cli); });
            tareas = rows.map(function (r) { return r.Tar_Cod; });
        }
        if (tareas.length === 0) { alert('No hay tareas para asignar. Todas están finalizadas o no hay tareas. Seleccione actividad y cargue.'); return; }
        ejecutarAsignacion(per, null, tareas.join(','));
    });

    $('#chkAutoAsignar').on('change', function () {
        $('#wrapComboAutoAsig').toggle($(this).is(':checked'));
    });

    function actualizarFechasDesdeMes() {
        var per = $('#periodoGenerar').val().trim();
        if (!per || per.length < 6) {
            $('#filtroClienteGenerar').find('option:gt(0)').remove();
            return;
        }
        var p = per.split('-'), y = parseInt(p[0], 10), m = parseInt(p[1], 10);
        if (!m || m < 1 || m > 12) return;
        var fecIni = y + '-' + (m < 10 ? '0' + m : m) + '-01';
        var ultimoDia = new Date(y, m, 0).getDate();
        var fecFin = y + '-' + (m < 10 ? '0' + m : m) + '-' + (ultimoDia < 10 ? '0' + ultimoDia : ultimoDia);
        $('#fechaDesdeGenerar').val(fecIni);
        $('#fechaHastaGenerar').val(fecFin);
        $.get(urlBase, { listarClientesContratosPeriodo: 1, Tar_Periodo: per }, function (r) {
            var $combo = $('#filtroClienteGenerar');
            if ($combo.hasClass('select2-hidden-accessible')) $combo.select2('destroy');
            $combo.find('option:gt(0)').remove();
            (r.rows || []).forEach(function (row) {
                $combo.append($('<option></option>').val(row.Cli_Cod).text(row.Cliente_Nombre || ''));
            });
            initSelect2Buscable($combo, { placeholder: '-- Todos los clientes --' });
        }, 'json');
    }
    $('#periodoGenerar').on('change input', actualizarFechasDesdeMes);
    // Valor por defecto: mes actual (Generar tareas)
    (function () {
        var h = new Date();
        var m = h.getMonth() + 1;
        var mes = h.getFullYear() + '-' + (m < 10 ? '0' + m : '' + m);
        $('#periodoGenerar').val(mes);
        actualizarFechasDesdeMes();
    })();
    $('#fechaDesdeGenerar, #fechaHastaGenerar').on('change', function () {
        var desde = $('#fechaDesdeGenerar').val();
        if (desde && desde.length >= 7) {
            var per = desde.substring(0, 7);
            $.get(urlBase, { listarClientesContratosPeriodo: 1, Tar_Periodo: per }, function (r) {
                var $combo = $('#filtroClienteGenerar');
                if ($combo.find('option').length <= 1) {
                    if ($combo.hasClass('select2-hidden-accessible')) $combo.select2('destroy');
                    $combo.find('option:gt(0)').remove();
                    (r.rows || []).forEach(function (row) {
                        $combo.append($('<option></option>').val(row.Cli_Cod).text(row.Cliente_Nombre || ''));
                    });
                    initSelect2Buscable($combo, { placeholder: '-- Todos los clientes --' });
                }
            }, 'json');
        }
    });

    // -------- Tarea EVENTUAL individual (modal) --------
    function cargarClientesEventual() {
        var per = $('#periodoEventual').val().trim();
        var $cli = $('#clienteEventual');
        var $act = $('#actividadEventual');
        if ($cli.hasClass('select2-hidden-accessible')) $cli.select2('destroy');
        if ($act.hasClass('select2-hidden-accessible')) $act.select2('destroy');
        $cli.find('option:gt(0)').remove();
        $act.find('option:gt(0)').remove();
        $act.append($('<option value="">-- Seleccione cliente primero --</option>'));
        if (!per || per.length < 7) return;
        $.get(urlBase, { listarClientesContratosPeriodo: 1, Tar_Periodo: per }, function (r) {
            (r.rows || []).forEach(function (row) {
                $cli.append($('<option></option>').val(row.Cli_Cod).text(row.Cliente_Nombre || ''));
            });
            initSelect2Buscable($cli, { placeholder: '-- Seleccione --' });
        }, 'json');
    }
    function cargarActividadesEventuales() {
        var per = $('#periodoEventual').val().trim();
        var cli = $('#clienteEventual').val();
        var $act = $('#actividadEventual');
        if ($act.hasClass('select2-hidden-accessible')) $act.select2('destroy');
        $act.find('option:gt(0)').remove();
        if (!per || !cli) {
            $act.append($('<option value="">-- Seleccione cliente primero --</option>'));
            return;
        }
        $.get(urlBase, { listarActividadesEventuales: 1, Tar_Periodo: per, Cli_Cod: cli }, function (r) {
            var rows = r.rows || [];
            if (!rows.length) {
                $act.append($('<option></option>').val('').text('-- Sin actividades eventuales configuradas --'));
                return;
            }
            rows.forEach(function (row) {
                var txt = (row.Ser_Nombre ? (row.Ser_Nombre + ' - ') : '') + (row.Act_Nombre || '');
                $act.append($('<option></option>').val(row.Act_Cod).text(txt));
            });
            initSelect2Buscable($act, { placeholder: '-- Seleccione actividad --' });
        }, 'json');
    }
    function abrirModalEventual() {
        var hoy = new Date();
        var y = hoy.getFullYear();
        var m = (hoy.getMonth() + 1) < 10 ? '0' + (hoy.getMonth() + 1) : (hoy.getMonth() + 1);
        $('#periodoEventual').val(y + '-' + m);
        $('#clienteEventual').val('');
        $('#actividadEventual').find('option:gt(0)').remove();
        $('#actividadEventual').append($('<option value="">-- Seleccione cliente primero --</option>'));
        $('#fechaLimiteEventual').val('');
        $('#resultadoTareaEventual').html('');
        cargarClientesEventual();
        $('#modalEventual').addClass('modal-eventual-abierto');
    }
    function cerrarModalEventual() {
        $('#modalEventual').removeClass('modal-eventual-abierto');
    }
    $('#btnAbrirModalEventual').on('click', abrirModalEventual);
    $('#btnCerrarModalEventual, #btnCancelarEventual').on('click', cerrarModalEventual);
    $('#modalEventual').on('click', function (e) {
        if (e.target === this) cerrarModalEventual();
    });
    $(document).on('keydown.modalEventual', function (e) {
        if (e.key === 'Escape' && $('#modalEventual').hasClass('modal-eventual-abierto')) {
            cerrarModalEventual();
        }
    });
    $('#periodoEventual').on('change input', function () {
        cargarClientesEventual();
    });
    $('#clienteEventual').on('change', function () {
        cargarActividadesEventuales();
    });
    $('#btnGenerarTareaEventual').on('click', function () {
        var per = $('#periodoEventual').val().trim();
        var cli = $('#clienteEventual').val();
        var act = $('#actividadEventual').val();
        var fec = $('#fechaLimiteEventual').val().trim();
        if (!per || per.length < 7) { alert('Indique el período (mes) para la tarea eventual.'); return; }
        if (!cli) { alert('Seleccione el cliente.'); return; }
        if (!act) { alert('Seleccione la actividad eventual.'); return; }
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#resultadoTareaEventual').html('<div class=\"alert alert-info\"><span class=\"glyphicon glyphicon-refresh glyphicon-spin\"></span> Generando tarea...</div>');
        $.post(urlBase, { generarTareaEventual: 1, Tar_Periodo: per, Cli_Cod: cli, Act_Cod: act, Tar_Fecha_Limite: fec }, function (r) {
            $btn.prop('disabled', false);
            var ok = r && r.success;
            var msg = (r && r.message) ? r.message : (ok ? 'Tarea generada.' : 'No se pudo generar la tarea.');
            $('#resultadoTareaEventual').html('<div class="alert alert-' + (ok ? 'success' : 'warning') + '">' + msg + '</div>');
            if (ok) {
                setTimeout(function () { cerrarModalEventual(); }, 1500);
            }
        }, 'json').fail(function () {
            $btn.prop('disabled', false);
            $('#resultadoTareaEventual').html('<div class="alert alert-danger">Error de conexión al generar la tarea.</div>');
        });
    });

    $('#btnVistaPrevia').on('click', function () {
        var desde = $('#fechaDesdeGenerar').val().trim();
        var hasta = $('#fechaHastaGenerar').val().trim();
        var per = $('#periodoGenerar').val().trim();
        if (!desde || !hasta) {
            if (per && per.length >= 6) {
                actualizarFechasDesdeMes();
                desde = $('#fechaDesdeGenerar').val();
                hasta = $('#fechaHastaGenerar').val();
            }
            if (!desde || !hasta) {
                alert('Indique el rango con las fechas Desde y Hasta, o elija un mes de referencia para rellenarlas.');
                return;
            }
        }
        if (desde > hasta) { alert('La fecha Desde no puede ser mayor que Hasta.'); return; }
        var filtroCli = $('#filtroClienteGenerar').val() || '';
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#vistaPreviaTareas').show();
        $('#tablaVistaPrevia tbody').html('<tr><td colspan="8" class="text-muted"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando...</td></tr>');
        var params = { previewTareas: 1, Fecha_Ini: desde, Fecha_Fin: hasta };
        if (filtroCli) params.Cli_Cod = filtroCli;
        $.get(urlBase, params, function (r) {
            $btn.prop('disabled', false);
            var rows = r.rows || [];
            if (r.fec_ini && r.fec_fin) {
                $('#fechaDesdeGenerar').val(r.fec_ini);
                $('#fechaHastaGenerar').val(r.fec_fin);
            }
            var clientesUnicos = {};
            var html = '';
            if (rows.length === 0) {
                html = '<tr><td colspan="8" class="text-muted">No hay tareas para generar. Revise contratos vigentes y actividades configuradas.</td></tr>';
            } else {
                function estadoTareaBadge(tarEst) {
                    var map = { 'PENDIENTE': ['tarea-pendiente', 'Pendiente'], 'ASIGNADA': ['tarea-asignada', 'Asignada'], 'EN_PROCESO': ['tarea-en-proceso', 'En proceso'], 'FINALIZADA': ['tarea-finalizada', 'Finalizada'], 'VENCIDA': ['tarea-vencida', 'Vencida'], 'OBSERVADA': ['tarea-observada', 'Observada'] };
                    var upper = (tarEst || '').toUpperCase();
                    var m = map[upper] || ['tarea-pendiente', tarEst || '-'];
                    return '<span class="estado-tarea-badge ' + m[0] + '">' + (m[1] || '-') + '</span>';
                }
                $.each(rows, function (i, row) {
                    if (row.Cli_Cod && row.Cliente_Nombre) clientesUnicos[row.Cli_Cod] = row.Cliente_Nombre;
                    var trClass = row.Ya_Existe ? 'ya-existe' : 'nueva';
                    var estado = row.Ya_Existe ? '<span class="text-warning">Ya existe</span>' : '<span class="text-success">Nueva</span>';
                    var estadoTarea = row.Ya_Existe && row.Tar_Est ? estadoTareaBadge(row.Tar_Est) : '<span class="text-muted">-</span>';
                    var est = (row.Tar_Est || '').toUpperCase();
                    var puedeEliminar = (row.Tar_Cod && row.Ya_Existe) && (est === 'PENDIENTE' || est === 'ASIGNADA');
                    var btnElim = puedeEliminar ? '<button type="button" class="btn btn-xs btn-danger btn-eliminar-tarea-prev" data-tar="' + row.Tar_Cod + '" title="Eliminar tarea"><span style="font-size:14px;">&times;</span></button>' : '';
                    var fecVal = (row.Tar_Fecha_Limite || '').substring(0, 10);
                    var inputFecha = row.Ya_Existe ? (row.Tar_Fecha_Limite || '-') : '<input type="date" class="form-control input-sm input-fecha-limite" value="' + fecVal + '" style="width:140px; min-width:120px;" title="Puede modificar la fecha l\u00edmite" />';
                    html += '<tr class="' + trClass + '" data-cli-cod="' + (row.Cli_Cod || '') + '" data-act-cod="' + (row.Act_Cod || '') + '" data-tar-periodo="' + (row.Tar_Periodo || '') + '"><td>' + (row.Cliente_Nombre || '') + '</td><td>' + (row.Ser_Nombre || '') + '</td><td>' + (row.Act_Nombre || '') + '</td><td>' + (row.Tar_Periodo || '') + '</td><td class="col-fecha-limite">' + inputFecha + '</td><td>' + estado + '</td><td>' + estadoTarea + '</td><td>' + btnElim + '</td></tr>';
                });
            }
            $('#tablaVistaPrevia tbody').html(html);
            var msg = 'Contratos vigentes: ' + (r.contratos || 0) + ' &bull; Nuevas: ' + (r.total_nuevas || 0) + ' &bull; Omitidas (duplicado): ' + (r.total_omitidas || 0);
            $('#vistaPreviaResumen').html(msg);
            var sinTareas = (rows.length === 0) || ((r.total_nuevas || 0) === 0 && (r.total_omitidas || 0) === 0);
            $('#ayudaNoGenera').toggle(sinTareas);
        }, 'json').fail(function () {
            $btn.prop('disabled', false);
            $('#tablaVistaPrevia tbody').html('<tr><td colspan="8" class="text-danger">Error al cargar la vista previa.</td></tr>');
        });
    });
    $('#filtroClienteGenerar').on('change', function () { $('#btnVistaPrevia').click(); });

    $(document).on('click', '.btn-eliminar-tarea-prev', function () {
        var tar = $(this).data('tar');
        if (!tar || !confirm('¿Eliminar esta tarea? Se borrará permanentemente junto con sus asignaciones.')) return;
        var $tr = $(this).closest('tr');
        $.post(urlBase, { eliminarTareaDespacho: 1, Tar_Cod: tar }, function (r) {
            if (r && r.success) {
                $tr.fadeOut(200, function () { $(this).remove(); });
                $('#btnVistaPrevia').click();
            } else {
                alert(r && r.message ? r.message : 'Error al eliminar.');
            }
        }, 'json').fail(function () { alert('Error de conexión.'); });
    });

    $('#btnGenerarTareas').on('click', function () {
        var desde = $('#fechaDesdeGenerar').val().trim();
        var hasta = $('#fechaHastaGenerar').val().trim();
        var per = $('#periodoGenerar').val().trim();
        if (!desde || !hasta) {
            if (per && per.length >= 6) {
                actualizarFechasDesdeMes();
                desde = $('#fechaDesdeGenerar').val();
                hasta = $('#fechaHastaGenerar').val();
            }
            if (!desde || !hasta) {
                alert('Indique el rango con las fechas Desde y Hasta, o elija un mes de referencia.');
                return;
            }
        }
        if (desde > hasta) { alert('La fecha Desde no puede ser mayor que Hasta.'); return; }
        var fechasOverride = [];
        $('#tablaVistaPrevia tbody tr.nueva').each(function () {
            var $tr = $(this), cli = $tr.data('cli-cod'), act = $tr.data('act-cod'), periodo = $tr.data('tar-periodo');
            var $input = $tr.find('.input-fecha-limite');
            if (cli && act && periodo && $input.length && $input.val()) {
                fechasOverride.push({ Cli_Cod: cli, Act_Cod: act, Tar_Periodo: periodo, Tar_Fecha_Limite: $input.val() });
            }
        });
        var $btn = $(this);
        $btn.prop('disabled', true);
        var data = { generarTareas: 1, Fecha_Ini: desde, Fecha_Fin: hasta };
        if (fechasOverride.length) data.fechasOverride = JSON.stringify(fechasOverride);
        if ($('#chkAutoAsignar').is(':checked')) {
            var perAuto = $('#comboPerAutoAsig').val();
            if (perAuto) data.Per_Cod_Auto = perAuto;
        }
        $.ajax({
            url: urlBase,
            type: 'POST',
            data: data,
            dataType: 'json',
            timeout: 30000,
            cache: false
        }).done(function (r) {
            $btn.prop('disabled', false);
            var extra = (r && r.contratos_encontrados !== undefined) ? '<br/><small>Contratos vigentes en el per&iacute;odo: ' + r.contratos_encontrados + '</small>' : '';
            var msg = (r && r.message) ? r.message : 'Operación completada.';
            var gen = (r && r.generadas !== undefined) ? r.generadas : 0;
            var omit = (r && r.omitidas !== undefined) ? r.omitidas : 0;
            var asig = (r && r.asignadas !== undefined) ? r.asignadas : 0;
            var detalle = 'Generadas: ' + gen + ', Omitidas: ' + omit;
            if (asig > 0) detalle += ', Auto-asignadas: ' + asig;
            $('#resultadoGeneracion').show().html('<div class="alert alert-' + (r && r.success ? 'success' : 'warning') + '">' + msg + '<br/>' + detalle + extra + '</div>');
            $('#ayudaNoGenera').toggle(gen === 0 && omit === 0);
            $('#btnVistaPrevia').click();
        }).fail(function (xhr, status, err) {
            $btn.prop('disabled', false);
            var errMsg = 'Error al generar tareas. ';
            if (status === 'timeout') errMsg += 'Tiempo de espera agotado.';
            else if (xhr.responseText && xhr.responseText.indexOf('<!') === 0) errMsg += 'El servidor devolvió HTML (posible redirección de sesión).';
            else if (err) errMsg += err;
            $('#resultadoGeneracion').show().html('<div class="alert alert-danger">' + errMsg + '</div>');
            $('#ayudaNoGenera').show();
        });
    });
});
</script>
</body>
</html>
