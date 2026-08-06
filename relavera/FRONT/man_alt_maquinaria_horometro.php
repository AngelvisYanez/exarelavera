<?php

/**
 * Formulario y Gestión de Horómetro de Maquinaria - Relavera
 * Permite registrar, revisar, aprobar y controlar lecturas de horómetro y mantenimiento preventivo.
 * Desarrollado bajo arquitectura de dos ambientes de consulta y registro.
 * Compatible con PHP 5.3.8.
 * @author Sistema EXA
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_maquinaria_horometro.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Maquinaria_Horometro($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Maquinaria_Horometro;

// 1. Obtener planta asignada al usuario
// El sistema se basa puramente en la empresa seleccionada
// Determinar rol de usuario
$user_role = 'OP'; // Operador por defecto
$row_perfil = $obBD_con1->getArrayConsultaSql("SELECT p.Per_Des FROM usuarperfi up INNER JOIN perfiles p ON up.Per_Cod = p.Per_Cod WHERE up.Usu_Cod = '" . $_SESSION['Ses_Usu_Cod'] . "'", $obBD_conexion);
if (!empty($row_perfil)) {
    foreach ($row_perfil as $perf) {
        $des = strtoupper($perf['Per_Des']);
        if ($des === 'ADMINISTRADOR' || $des === 'GERENTE' || $des === 'SISTEMAS') {
            $user_role = 'ADM';
            break;
        } else if ($des === 'SUPERVISOR' || $des === 'PLANTAS') {
            $user_role = 'SUP';
        }
    }
}

/* ==================== AJAX HANDLERS ==================== */

// Cargar Métricas Dashboard
if (isset($_GET['getDashboardMetricsAjax'])) {
    $resp = array('success' => true, 'pendientes' => 0, 'horas_mes' => 0, 'mantenimientos_alerta' => 0);

    // Lecturas pendientes
    $row_p = $obBD_con1->getRowConsulta(7, array('tipo' => 'pendientes'), $obBD_conexion);
    $resp['pendientes'] = isset($row_p['total']) ? (int)$row_p['total'] : 0;

    // Horas acumuladas mes
    $row_h = $obBD_con1->getRowConsulta(7, array('tipo' => 'horas_mes'), $obBD_conexion);
    $resp['horas_mes'] = isset($row_h['total']) ? (float)$row_h['total'] : 0.00;

    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($_GET['getLastOperadorAjax'])) {
    $resp = array('success' => false, 'Cho_Cod' => null);
    $veh_cod = isset($_GET['veh_cod']) ? addslashes($_GET['veh_cod']) : '';
    if (!empty($veh_cod)) {
        $row = $obBD_con1->getRowConsulta(25, array('Veh_Cod' => $veh_cod), $obBD_conexion);
        if ($row && !empty($row['Cho_Cod'])) {
            $resp['success'] = true;
            $resp['Cho_Cod'] = $row['Cho_Cod'];
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Listar máquinas asociadas a la planta
if (isset($_GET['listMaquinasAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(1, array('Emp_Cod' => $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// Listar operadores asociados a la planta
if (isset($_GET['listOperadoresAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(2, array('Emp_Cod' => $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// Listar registros en el Grid (Ambiente 1)
if (isset($_GET['listHorometrosGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $op_opciones = isset($_GET['op_opciones']) ? trim($_GET['op_opciones']) : '';

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'search' => $search,
        'op_opciones' => $op_opciones
    );

    $row_count = $obBD_con1->getRowConsulta(3, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Obtener registros SubGrid
if (isset($_GET['getSubGridHorometrosAjax'])) {
    $resp = array('success' => true, 'rows' => array());
    $Veh_Cod = isset($_GET['Veh_Cod']) ? (int)$_GET['Veh_Cod'] : 0;
    $Cho_Cod = isset($_GET['Cho_Cod']) ? (int)$_GET['Cho_Cod'] : 0;
    $Hor_Fec = isset($_GET['Hor_Fec']) ? trim($_GET['Hor_Fec']) : '';
    $Hor_Tur = isset($_GET['Hor_Tur']) ? trim($_GET['Hor_Tur']) : '';

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Hor_Fec)) {
        $parts = explode('/', $Hor_Fec);
        $Hor_Fec = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }

    if ($Veh_Cod > 0 && $Cho_Cod > 0 && !empty($Hor_Fec)) {
        $resp['rows'] = $obBD_con1->getArrayConsulta(13, array(
            'Veh_Cod' => $Veh_Cod,
            'Cho_Cod' => $Cho_Cod,
            'Hor_Fec' => $Hor_Fec
        ), $obBD_conexion);
        $obBD_con1->utf8_change_param($resp['rows']);
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar Horómetro (AJAX) - Soporta Insert (Ini) y Update (Fin)
if (isset($_POST['saveHorometroAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Hor_Cod = isset($_POST['Hor_Cod']) ? (int)$_POST['Hor_Cod'] : 0;
        $Veh_Cod = isset($_POST['Veh_Cod']) ? (int)$_POST['Veh_Cod'] : 0;
        $Cho_Cod = isset($_POST['Cho_Cod']) ? (int)$_POST['Cho_Cod'] : 0;
        $Hor_Fec = isset($_POST['Hor_Fec']) ? trim($_POST['Hor_Fec']) : '';
        $Hor_Ini = isset($_POST['Hor_Ini']) ? trim($_POST['Hor_Ini']) : '';
        $Hor_Fin = isset($_POST['Hor_Fin']) ? trim($_POST['Hor_Fin']) : '0';
        $Hor_Set = isset($_POST['Hor_Set']) ? trim($_POST['Hor_Set']) : '';
        $Hor_Obs = isset($_POST['Hor_Obs']) ? trim($_POST['Hor_Obs']) : '';

        $Hor_Hini = date('Y-m-d H:i:s'); // Fecha y Hora inicial automática (DATETIME)
        $Hor_Hfin = date('Y-m-d H:i:s'); // Fecha y Hora final automática (DATETIME)

        if (empty($Veh_Cod) || empty($Cho_Cod) || empty($Hor_Fec) || empty($Hor_Ini) || empty($Hor_Set)) {
            throw new Exception('Todos los campos marcados con asterisco (*) son obligatorios.');
        }

        $hor_ini_val = (float)$Hor_Ini;
        $hor_fin_val = (float)$Hor_Fin;
        $horas_calculadas = 0;

        if ($Hor_Cod > 0 && $hor_fin_val > 0) {
            if ($hor_fin_val < $hor_ini_val) {
                throw new Exception('El horómetro final no puede ser menor al horómetro inicial.');
            }
            $horas_calculadas = $hor_fin_val - $hor_ini_val;
        }

        // Formatear fecha para MySQL
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Hor_Fec)) {
            $parts = explode('/', $Hor_Fec);
            $Hor_Fec = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        // Obtener el numero de registro del dia para la maquina y chofer
        $count_n = 1;
        $row_c = $obBD_con1->getRowConsulta(14, array('Veh_Cod' => $Veh_Cod, 'Cho_Cod' => $Cho_Cod, 'Hor_Fec' => $Hor_Fec, 'Hor_Cod' => $Hor_Cod), $obBD_conexion);
        if ($Hor_Cod > 0) {
            $count_n = isset($row_c['total']) ? (int)$row_c['total'] : 1;
        } else {
            $count_n = isset($row_c['total']) ? (int)$row_c['total'] + 1 : 1;
        }
        $fecha_str = date('Ymd'); // "fechaactual"

        $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '620';
        $ruta_destino = "../../imagenes/" . $emp_cod . "/horometro/";
        if (!file_exists($ruta_destino)) {
            @mkdir($ruta_destino, 0777, true);
            if (!is_dir($ruta_destino)) {
                error_log("No se pudo crear directorio: $ruta_destino");
            }
        }

        // Subida de imagen Inicial (opcional)
        $hor_img_ini_path = '';
        if (isset($_FILES['Hor_Img_Ini']) && $_FILES['Hor_Img_Ini']['error'] == 0) {
            $ext = pathinfo($_FILES['Hor_Img_Ini']['name'], PATHINFO_EXTENSION);
            $hor_img_ini_path = $fecha_str . '_ini_' . $count_n . '.' . $ext;
            move_uploaded_file($_FILES['Hor_Img_Ini']['tmp_name'], $ruta_destino . $hor_img_ini_path);
        }

        // Subida de imagen Final (opcional)
        $hor_img_fin_path = '';
        if (isset($_FILES['Hor_Img_Fin']) && $_FILES['Hor_Img_Fin']['error'] == 0) {
            $ext = pathinfo($_FILES['Hor_Img_Fin']['name'], PATHINFO_EXTENSION);
            $hor_img_fin_path = $fecha_str . '_fin_' . $count_n . '.' . $ext;
            move_uploaded_file($_FILES['Hor_Img_Fin']['tmp_name'], $ruta_destino . $hor_img_fin_path);
        }

        if ($Hor_Cod == 0) {
            // INSERTAR (Solo Hor_Ini, Hor_Fin=0)
            $params = array(
                0 => $_SESSION['Ses_Usu_Cod'],
                1 => $Veh_Cod,
                2 => $Cho_Cod,
                3 => $Hor_Fec,
                4 => $hor_ini_val,
                5 => 0, // Fin es 0 al inicio
                6 => $Hor_Set,
                7 => $Hor_Obs,
                8 => $Hor_Hini, // Hora Inicial del sistema
                9 => $hor_img_ini_path,
                10 => '', // Hora Final inicializa vacío
                11 => '' // Sin foto final al iniciar
            );
            $obBD_con1->operacionobBD(4, $params, $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Inserción): " . $obBD_con1->getMsgError());
            $Hor_Cod_New = $obBD_con1->insercionid($obBD_conexion);
            $resp['message'] = 'Horómetro inicial guardado exitosamente.';
        } else {
            // ACTUALIZAR (Añadir Hor_Fin, etc)
            $params_upd = array(
                0 => $Hor_Cod,
                1 => $hor_ini_val,
                2 => $hor_fin_val,
                3 => $Hor_Hfin, // Hora final del sistema (solo se guardará si es cierre)
                4 => $Hor_Set,
                5 => $Hor_Obs,
                6 => $hor_img_ini_path,
                7 => $hor_img_fin_path
            );
            $obBD_con1->operacionobBD(12, $params_upd, $obBD_conexion);
            if ($obBD_con1->Error != 0) throw new Exception("Error BD (Actualización): " . $obBD_con1->getMsgError());
            $resp['message'] = 'Horómetro final y registro actualizado exitosamente.';
        }

        $resp['success'] = true;
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Cambiar de Estado (Aprobación / Revisión / Rechazo / Anulación)
if (isset($_POST['changeEstadoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Hor_Cod = isset($_POST['Hor_Cod']) ? (int)$_POST['Hor_Cod'] : 0;
        $Estado_Nue = isset($_POST['Hor_Est']) ? trim($_POST['Hor_Est']) : '';
        $Hhi_Obs = isset($_POST['Hhi_Obs']) ? trim($_POST['Hhi_Obs']) : '';

        if (empty($Hor_Cod) || empty($Estado_Nue)) {
            throw new Exception('Parámetros de estado no válidos.');
        }

        // Obtener estado anterior
        $row_act = $obBD_con1->getArrayConsultaSql("SELECT Hor_Est, Veh_Cod, Hor_Fin FROM maquinaria_horometro WHERE Hor_Cod = $Hor_Cod LIMIT 1", $obBD_conexion);
        if (empty($row_act)) {
            throw new Exception('Registro de horómetro no encontrado.');
        }
        $Estado_Ant = $row_act[0]['Hor_Est'];
        $Veh_Cod = (int)$row_act[0]['Veh_Cod'];
        $Hor_Fin = (float)$row_act[0]['Hor_Fin'];

        // Si ya está aprobado y no es Administrador, bloquear
        if ($Estado_Ant == 'A' && $user_role !== 'ADM') {
            throw new Exception('No tiene permisos para modificar un registro que ya ha sido Aprobado.');
        }

        // Actualizar estado del registro
        $obBD_con1->operacionobBD(5, array($Hor_Cod, $Estado_Nue), $obBD_conexion);
        if ($obBD_con1->Error != 0) throw new Exception("Error BD (Estado): " . $obBD_con1->getMsgError());



        $resp['success'] = true;
        $resp['message'] = 'Estado actualizado exitosamente.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Obtener Datos para Reporte
if (isset($_GET['getReporteOperativoAjax'])) {
    $resp = array('success' => false, 'message' => '');
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'individual';
    $anio = isset($_GET['anio']) ? trim($_GET['anio']) : date('Y');
    $mes = isset($_GET['mes']) ? trim($_GET['mes']) : date('m');
    $maq = isset($_GET['maq']) ? trim($_GET['maq']) : 'TODAS';
    $ope = isset($_GET['ope']) ? trim($_GET['ope']) : 'TODOS';

    $meses = array(
        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre'
    );
    $periodo = (isset($meses[$mes]) ? strtoupper($meses[$mes]) : '') . ' ' . $anio;
    $anio_mes = $anio . '-' . $mes;
    $fecha_ini = $anio_mes . '-01';
    $fecha_fin = date("Y-m-t", strtotime($fecha_ini));

    $params = array(
        'anio_mes' => $anio_mes,
        'Veh_Cod' => $maq,
        'Cho_Cod' => $ope,
        'Emp_Cod' => $_SESSION['Ses_Emp_Cod'],
        'fecha_ini' => $fecha_ini,
        'fecha_fin' => $fecha_fin
    );

    if ($tipo === 'individual') {
        // Consultar Ficha de Maquinaria
        $ficha = array('id' => 'N/D', 'marca' => 'N/D', 'modelo' => 'N/D', 'serie' => 'N/D', 'propiedad' => 'N/D');
        if ($maq !== 'TODAS') {
            $row_ficha = $obBD_con1->getRowConsulta(20, $params, $obBD_conexion);
            if (!empty($row_ficha)) {
                $ficha['id'] = $row_ficha['id'];
                $ficha['marca'] = $row_ficha['marca'];
                $ficha['modelo'] = $row_ficha['modelo'];
                $ficha['serie'] = $row_ficha['serie'];
                $ficha['propiedad'] = $row_ficha['propiedad'];
            }
        }

        // Consultar Detalle Diario
        $rows_diario = $obBD_con1->getArrayConsulta(21, $params, $obBD_conexion);

        $rows_combustible = $obBD_con1->getCombustibleReporte('individual', $params, $obBD_conexion);
        $comb_por_fecha = array();
        if (!empty($rows_combustible)) {
            foreach ($rows_combustible as $rc) {
                $comb_por_fecha[$rc['fecha']] = array(
                    'cargado' => (float)$rc['combustible_cargado'],
                    'costo' => (float)$rc['costo_combustible']
                );
            }
        }

        $tot_ht = 0;
        $tot_hp = 0;
        $tot_desf = 0;
        $tot_comb = 0;
        $tot_costo = 0;
        $q1_ht = 0;
        $q1_hp = 0;
        $q1_desf = 0;
        $q1_comb = 0;
        $q1_costo = 0;
        $q1_dias = 0;
        $q2_ht = 0;
        $q2_hp = 0;
        $q2_desf = 0;
        $q2_comb = 0;
        $q2_costo = 0;
        $q2_dias = 0;

        $op_nom = 'N/D';
        $dias_trabajados = array();
        $dias_q1 = array();
        $dias_q2 = array();

        $detalle_q1 = array();
        $detalle_q2 = array();

        if (!empty($rows_diario)) {
            foreach ($rows_diario as $r) {
                $dia = (int)$r['dia'];
                $ht = (float)$r['total_hrs'];
                $hp = (float)$r['prod_hrs'];
                $desf = (float)$r['descuento'];

                $f_row = $r['fecha'];
                $comb = isset($comb_por_fecha[$f_row]) ? $comb_por_fecha[$f_row]['cargado'] : 0;
                $costo = isset($comb_por_fecha[$f_row]) ? $comb_por_fecha[$f_row]['costo'] : 0;
                $rend = ($ht > 0) ? ($comb / $ht) : 0;

                if ($op_nom === 'N/D') $op_nom = $r['operador'];

                $tot_ht += $ht;
                $tot_hp += $hp;
                $tot_desf += $desf;
                $tot_comb += $comb;
                $tot_costo += $costo;
                $dias_trabajados[$dia] = true;

                $item = array(
                    'dia' => str_pad($dia, 2, '0', STR_PAD_LEFT),
                    'fecha' => $r['fecha'],
                    'operador' => $r['operador'],
                    'hor_inicial' => number_format((float)$r['hor_inicial'], 2),
                    'hor_final' => number_format((float)$r['hor_final'], 2),
                    'total_hrs' => number_format($ht, 2),
                    'descuento' => number_format($desf, 2),
                    'prod_hrs' => number_format($hp, 2),
                    'combustible' => number_format($comb, 2),
                    'costo' => number_format($costo, 2),
                    'rendimiento' => $ht > 0 ? number_format($rend, 2) : 'N/A',
                    'observaciones' => $r['observaciones']
                );

                if ($dia <= 15) {
                    $q1_ht += $ht;
                    $q1_hp += $hp;
                    $q1_desf += $desf;
                    $q1_comb += $comb;
                    $q1_costo += $costo;
                    $dias_q1[$dia] = true;
                    $detalle_q1[] = $item;
                } else {
                    $q2_ht += $ht;
                    $q2_hp += $hp;
                    $q2_desf += $desf;
                    $q2_comb += $comb;
                    $q2_costo += $costo;
                    $dias_q2[$dia] = true;
                    $detalle_q2[] = $item;
                }
            }
        }

        $tot_dias = count($dias_trabajados);
        $q1_dias = count($dias_q1);
        $q2_dias = count($dias_q2);
        $prom_diario = $tot_dias > 0 ? ($tot_hp / $tot_dias) : 0;

        $resp['success'] = true;
        $resp['periodo'] = $periodo;
        $resp['operador_nombre'] = $op_nom;
        $resp['ficha'] = $ficha;

        $resp['resumen'] = array(
            'horas_trabajadas' => number_format($tot_ht, 2),
            'horas_productivas' => number_format($tot_hp, 2),
            'desfase' => number_format($tot_desf, 2),
            'combustible' => number_format($tot_comb, 2),
            'costo' => number_format($tot_costo, 2),
            'promedio_diario' => number_format($prom_diario, 2),
            'dias_laborados' => $tot_dias
        );

        $resp['q1'] = array(
            'horas_trabajadas' => number_format($q1_ht, 2),
            'horas_productivas' => number_format($q1_hp, 2),
            'desfase' => number_format($q1_desf, 2),
            'combustible' => number_format($q1_comb, 2),
            'costo' => number_format($q1_costo, 2),
            'dias_laborados' => $q1_dias
        );
        $resp['q2'] = array(
            'horas_trabajadas' => number_format($q2_ht, 2),
            'horas_productivas' => number_format($q2_hp, 2),
            'desfase' => number_format($q2_desf, 2),
            'combustible' => number_format($q2_comb, 2),
            'costo' => number_format($q2_costo, 2),
            'dias_laborados' => $q2_dias
        );

        $resp['detalle_q1'] = $detalle_q1;
        $resp['detalle_q2'] = $detalle_q2;
    } else {
        // Consolidado (Case 22)
        $resp['success'] = true;
        $resp['periodo'] = $periodo;

        $resumen = array('horas_trabajadas' => 0.0, 'horas_productivas' => 0.0, 'desfase' => 0.0, 'combustible' => 0.0, 'costo' => 0.0, 'total_maquinas' => 0);
        $detalle = array();

        $rows = $obBD_con1->getArrayConsulta(22, $params, $obBD_conexion);

        $rows_combustible = $obBD_con1->getCombustibleReporte('consolidado', $params, $obBD_conexion);
        $comb_por_maq = array();
        if (!empty($rows_combustible)) {
            foreach ($rows_combustible as $rc) {
                $comb_por_maq[$rc['Veh_Cod']] = array(
                    'cargado' => (float)$rc['combustible_cargado'],
                    'costo' => (float)$rc['costo_combustible']
                );
            }
        }

        $maquinas_vistas = array();

        if (!empty($rows)) {
            foreach ($rows as $r) {
                if (!isset($maquinas_vistas[$r['veh_cod']])) {
                    $maquinas_vistas[$r['veh_cod']] = true;
                    $resumen['total_maquinas']++;
                }

                $v_cod = $r['veh_cod'];
                $comb = isset($comb_por_maq[$v_cod]) ? $comb_por_maq[$v_cod]['cargado'] : 0;
                $costo = isset($comb_por_maq[$v_cod]) ? $comb_por_maq[$v_cod]['costo'] : 0;

                $ht = (float)$r['horas_trabajadas'];
                $rend = ($ht > 0) ? ($comb / $ht) : 0;

                $resumen['horas_trabajadas'] += $ht;
                $resumen['horas_productivas'] += (float)$r['horas_productivas'];
                $resumen['desfase'] += (float)$r['desfase'];
                $resumen['combustible'] += $comb;
                $resumen['costo'] += $costo;

                $detalle[] = array(
                    'veh_cod' => $r['veh_cod'],
                    'cho_cod' => $r['cho_cod'],
                    'maquina' => $r['maquina'],
                    'operador' => $r['operador'],
                    'horas_trabajadas' => number_format($ht, 2),
                    'horas_productivas' => number_format((float)$r['horas_productivas'], 2),
                    'desfase' => number_format((float)$r['desfase'], 2),
                    'combustible' => number_format($comb, 2),
                    'costo' => number_format($costo, 2),
                    'rendimiento' => $ht > 0 ? number_format($rend, 2) : 'N/A',
                    'estado' => $r['estado']
                );
            }
        }

        $resp['resumen'] = array(
            'horas_trabajadas' => number_format($resumen['horas_trabajadas'], 2),
            'horas_productivas' => number_format($resumen['horas_productivas'], 2),
            'desfase' => number_format($resumen['desfase'], 2),
            'combustible' => number_format($resumen['combustible'], 2),
            'costo' => number_format($resumen['costo'], 2),
            'total_maquinas' => $resumen['total_maquinas']
        );
        $resp['detalle'] = $detalle;
    }

    $obBD_con1->echoJson($resp);
    exit;
}


if (isset($_GET['getLastVehiculoByOperadorAjax'])) {
    $Cho_Cod = isset($_GET['Cho_Cod']) ? (int)$_GET['Cho_Cod'] : 0;
    $resp = array('success' => false, 'Veh_Cod' => 0);
    if ($Cho_Cod > 0) {
        $sql = "SELECT Veh_Cod FROM maquinaria_horometro WHERE Cho_Cod = $Cho_Cod AND Hor_Est != 'E' ORDER BY Hor_Fec DESC, Hor_Cod DESC LIMIT 1";
        $row = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
        if (!empty($row)) {
            $resp['success'] = true;
            $resp['Veh_Cod'] = (int)$row[0]['Veh_Cod'];
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}


// Cargar Evidencias de Horómetro (AJAX)
if (isset($_GET['getEvidenciasAjax'])) {
    $Hor_Cod = isset($_GET['Hor_Cod']) ? (int)$_GET['Hor_Cod'] : 0;
    $resp = array('success' => false, 'Hor_Img_Ini' => '', 'Hor_Img_Fin' => '');
    if ($Hor_Cod > 0) {
        $row = $obBD_con1->getArrayConsultaSql("SELECT Hor_Img_Ini, Hor_Img_Fin FROM maquinaria_horometro WHERE Hor_Cod = $Hor_Cod LIMIT 1", $obBD_conexion);
        if (!empty($row)) {
            $resp['Hor_Img_Ini'] = $row[0]['Hor_Img_Ini'];
            $resp['Hor_Img_Fin'] = $row[0]['Hor_Img_Fin'];
            $resp['Emp_Cod'] = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '0';
            $resp['success'] = true;
        } else {
            $resp['message'] = "Registro no encontrado.";
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}
?>
<!DOCTYPE HTML>
<HTML>

<HEAD>
    <TITLE>Gestión de Horómetro de Maquinaria [EXA]</TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <?php require_once('../../mascaras/model3/estilos/estilos.php'); ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/maquinaria_horometro.css" />
    <script>
        var user_role = <?php echo json_encode($user_role); ?>;
    </script>

</HEAD>

<BODY>
    <div class="panel panel-default panel-main">
        <div class="panel-heading">
            <span class="glyphicon glyphicon-tasks"></span> » Gestión de Horómetro de Maquinaria
        </div>
        <div class="panel-body exa-body">

            <!-- ==================== AMBIENTE 1: DASHBOARD, GRID Y CONSULTA ==================== -->
            <div id="divListado">

                <!-- Tarjetas del Dashboard -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="v2-metric-card warning">
                            <i class="glyphicon glyphicon-hourglass v2-metric-icon"></i>
                            <div class="v2-metric-content">
                                <h3 class="v2-metric-value" id="dash_pendientes">0</h3>
                                <p class="v2-metric-label">Lecturas Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="v2-metric-card success">
                            <i class="glyphicon glyphicon-time v2-metric-icon"></i>
                            <div class="v2-metric-content">
                                <h3 class="v2-metric-value" id="dash_horas_mes">0.00 h</h3>
                                <p class="v2-metric-label">Horas Trabajadas (Mes)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestañas de Consulta -->
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active">
                            <a href="#tabListHorometros" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-list-alt" style="margin-right: 8px;"></i>Lecturas de Horómetro
                            </a>
                        </li>
                        <li>
                            <a href="#tabReportes" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-stats" style="margin-right: 8px;"></i>Reportes Operativos
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content tab-content-custom">
                        <div role="tabpanel" class="tab-pane active" id="tabListHorometros">
                            <div class="form-inline" style="margin-bottom: 15px;">
                                <label class="control-label" style="font-weight: bold; margin-right:5px;">Periodo:</label>
                                <select id="tipoFiltroFecha" class="form-control input-sm" style="margin-right: 5px;" onchange="ajustarFiltroFecha(this.value);">
                                    <option value="T">Todo el tiempo</option>
                                    <option value="D">Por Día</option>
                                    <option value="S">Semanal</option>
                                    <option value="Q">Quincenal</option>
                                    <option value="M">Mensual</option>
                                </select>

                                <input type="date" id="filtroFechaDia" class="form-control input-sm" style="margin-right: 5px; display:none;">
                                <input type="week" id="filtroFechaSemana" class="form-control input-sm" style="margin-right: 5px; display:none;">
                                <input type="month" id="filtroFechaMes" class="form-control input-sm" style="margin-right: 5px; display:none;">
                                <select id="filtroQuincena" class="form-control input-sm" style="margin-right: 15px; display:none;">
                                    <option value="1">1ra Quincena (1 - 15)</option>
                                    <option value="2">2da Quincena (16 - fin)</option>
                                </select>

                                <label class="control-label" style="font-weight: bold; margin-right:5px; margin-left:10px;">Buscar:</label>
                                <select id="opHorometro" class="form-control input-sm" style="margin-right: 5px;" onchange="ajustarPlaceholderBusqueda(this.value);">
                                    <option value="p">Placa de Máquina</option>
                                    <option value="o">Operador (Nombre/Cédula)</option>
                                </select>

                                <input type="text" id="searchHorometro" class="form-control input-sm" placeholder="Buscar placa..." style="margin-right: 5px; width: 150px;">

                                <button type="button" class="btn btn-sm btn-primary" onclick="reloadGridHorometros();"><i class="fa fa-search"></i> Buscar</button>
                                <button type="button" class="btn btn-sm btn-exa-primary" onclick="exportarExcel();" title="Exportar a Excel"><i class="fa fa-file-excel-o"></i> Excel</button>

                                <button type="button" class="btn btn-sm btn-exa-success" style="float: right;" onclick="mostrarFormulario();">
                                    <i class="glyphicon glyphicon-plus"></i> Registrar Lectura Horómetro
                                </button>
                            </div>
                            <table id="gridHorometros"></table>
                            <div id="pagerHorometros"></div>
                        </div>

                        <!-- PESTAÑA REPORTES -->
                        <div role="tabpanel" class="tab-pane" id="tabReportes">
                            <div class="report-filters" style="background:#f8fafc; border:1px solid #cbd5e1; padding:15px; border-radius:8px; margin-bottom:20px;">
                                <div class="row">
                                    <div class="col-sm-1" style="width: 180px;">
                                        <label>Tipo de Reporte:</label>
                                        <select id="rep_tipo" class="form-control input-sm" onchange="toggleReportView()">
                                            <option value="individual">Reporte Individual</option>
                                            <option value="consolidado">Reporte Consolidado</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-1">
                                        <label>Año:</label>
                                        <select id="rep_anio" class="form-control input-sm"></select>
                                    </div>
                                    <div class="col-sm-1" style="width: 130px;">
                                        <label>Mes:</label>
                                        <select id="rep_mes" class="form-control input-sm">
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
                                    <div class="col-sm-3">
                                        <label>Operador:</label>
                                        <select id="rep_operador" class="form-control input-sm chosen-select">
                                            <option value="TODOS">TODOS LOS OPERADORES</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-2">
                                        <label>Maquinaria:</label>
                                        <select id="rep_maquina" class="form-control input-sm chosen-select">
                                            <option value="TODAS">TODAS LAS MÁQUINAS</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-3 text-right">
                                        <label>&nbsp;</label><br>
                                        <button class="btn btn-sm btn-primary" onclick="generarReporte()"><i class="fa fa-refresh"></i> Generar</button>
                                        <button class="btn btn-sm btn-danger" onclick="exportarReportePDF()"><i class="fa fa-file-pdf-o"></i> PDF/IMPRIMIR</button>
                                        <button class="btn btn-sm btn-success" onclick="exportarReporteExcel()"><i class="fa fa-file-excel-o"></i> Excel</button>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTENEDOR REPORTE INDIVIDUAL -->
                            <div id="contenedorReporteIndividual" style="display:none; background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:8px;">
                                <!-- ENCABEZADO -->
                                <div class="text-center" style="margin-bottom:20px; border-bottom:2px solid #3c8dbc; padding-bottom:10px;">
                                    <h4 style="font-weight:bold; color:#2c3e50; margin:0 0 5px 0;">PROYECTO AMBIENTAL ASOCIATIVO RELAVERA COMUNITARIA EL TABLÓN</h4>
                                    <h5 style="font-weight:bold; color:#3c8dbc; margin:0 0 10px 0;">REPORTE OPERATIVO DE HORÓMETROS Y MAQUINARIA</h5>
                                    <div class="row" style="font-size:12px; color:#64748b;">
                                        <div class="col-xs-3 text-left"><strong id="lbl_rep_periodo">PERÍODO: </strong></div>
                                        <div class="col-xs-3 text-left"><strong id="lbl_rep_maquina">MÁQUINA: </strong></div>
                                        <div class="col-xs-3 text-left"><strong id="lbl_rep_operador">OPERADOR: </strong></div>
                                        <div class="col-xs-3 text-right"><strong>EMISIÓN: </strong> <?php echo date('Y-m-d H:i'); ?></div>
                                    </div>
                                </div>

                                <!-- FICHA MAQUINARIA -->
                                <div style="background:#f1f5f9; border-radius:6px; padding:10px 15px; margin-bottom:20px; display:flex; justify-content:space-between; font-size:12px;">
                                    <div><strong>MÁQUINA/ID:</strong> <span id="ficha_id"></span></div>
                                    <div><strong>MARCA:</strong> <span id="ficha_marca"></span></div>
                                    <div><strong>MODELO:</strong> <span id="ficha_modelo"></span></div>
                                    <div><strong>SERIE:</strong> <span id="ficha_serie"></span></div>
                                    <div><strong>PROPIEDAD:</strong> <span id="ficha_propiedad"></span></div>
                                </div>

                                <!-- RESUMEN EJECUTIVO (CARDS) -->
                                <div class="row" style="margin-bottom:20px;">
                                    <div class="col-md-2 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:10px; text-align:center;">
                                            <div style="font-size:11px; color:#64748b; font-weight:bold;">HORAS TRAB.</div>
                                            <div style="font-size:18px; color:#0f172a; font-weight:bold;" id="res_hrs_trab">0.0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:10px; text-align:center;">
                                            <div style="font-size:11px; color:#64748b; font-weight:bold;">HORAS PROD.</div>
                                            <div style="font-size:18px; color:#16a34a; font-weight:bold;" id="res_hrs_prod">0.0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:10px; text-align:center;">
                                            <div style="font-size:11px; color:#64748b; font-weight:bold;">DESFASE</div>
                                            <div style="font-size:18px; color:#dc2626; font-weight:bold;" id="res_desfase">0.0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:10px; text-align:center;">
                                            <div style="font-size:11px; color:#64748b; font-weight:bold;">COMBUSTIBLE</div>
                                            <div style="font-size:18px; color:#ca8a04; font-weight:bold;" id="res_comb">0 Gls</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:10px; text-align:center;">
                                            <div style="font-size:11px; color:#64748b; font-weight:bold;">PROM. DIARIO</div>
                                            <div style="font-size:18px; color:#2563eb; font-weight:bold;" id="res_prom">0.0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:10px; text-align:center;">
                                            <div style="font-size:11px; color:#64748b; font-weight:bold;">DÍAS LABORADOS</div>
                                            <div style="font-size:18px; color:#475569; font-weight:bold;" id="res_dias">0</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- COMPARATIVO QUINCENAL -->
                                <h5 style="font-weight:bold; color:#334155; border-bottom:1px solid #cbd5e1; padding-bottom:5px;">COMPARATIVO QUINCENAL</h5>
                                <div class="table-responsive" style="margin-bottom:20px;">
                                    <table class="table table-bordered table-condensed table-striped" style="font-size:12px;">
                                        <thead>
                                            <tr style="background:#e2e8f0;">
                                                <th>Indicador</th>
                                                <th class="text-center">Primera Quincena (1-15)</th>
                                                <th class="text-center">Segunda Quincena (16-fin)</th>
                                                <th class="text-center">TOTAL MENSUAL</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl_comparativo">
                                            <tr>
                                                <td>Horas Trabajadas</td>
                                                <td class="text-center" id="cmp_ht_1">0.0</td>
                                                <td class="text-center" id="cmp_ht_2">0.0</td>
                                                <td class="text-center fw-bold" id="cmp_ht_t">0.0</td>
                                            </tr>
                                            <tr>
                                                <td>Horas Productivas</td>
                                                <td class="text-center" id="cmp_hp_1">0.0</td>
                                                <td class="text-center" id="cmp_hp_2">0.0</td>
                                                <td class="text-center fw-bold" id="cmp_hp_t">0.0</td>
                                            </tr>
                                            <tr>
                                                <td>Desfase / Descuento</td>
                                                <td class="text-center" id="cmp_df_1">0.0</td>
                                                <td class="text-center" id="cmp_df_2">0.0</td>
                                                <td class="text-center fw-bold" id="cmp_df_t">0.0</td>
                                            </tr>
                                            <tr>
                                                <td>Combustible (Galones)</td>
                                                <td class="text-center" id="cmp_cb_1">0</td>
                                                <td class="text-center" id="cmp_cb_2">0</td>
                                                <td class="text-center fw-bold" id="cmp_cb_t">0</td>
                                            </tr>
                                            <tr>
                                                <td>Costo Combustible ($)</td>
                                                <td class="text-center" id="cmp_cc_1">0</td>
                                                <td class="text-center" id="cmp_cc_2">0</td>
                                                <td class="text-center fw-bold" id="cmp_cc_t">0</td>
                                            </tr>
                                            <tr>
                                                <td>Días Laborados</td>
                                                <td class="text-center" id="cmp_dl_1">0</td>
                                                <td class="text-center" id="cmp_dl_2">0</td>
                                                <td class="text-center fw-bold" id="cmp_dl_t">0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- DETALLE DIARIO - 1RA QUINCENA -->
                                <h5 style="font-weight:bold; color:#334155; border-bottom:1px solid #cbd5e1; padding-bottom:5px; margin-top:30px;">DETALLE DIARIO: PRIMERA QUINCENA (1 al 15)</h5>
                                <div class="table-responsive" style="margin-bottom:20px;">
                                    <table class="table table-bordered table-condensed table-hover" style="font-size:11px;">
                                        <thead>
                                            <tr style="background:#3c8dbc; color:#fff;">
                                                <th width="40">Día</th>
                                                <th width="70">Fecha</th>
                                                <th>Operador</th>
                                                <th width="70" class="text-right">Hor. Inicial</th>
                                                <th width="70" class="text-right">Hor. Final</th>
                                                <th width="60" class="text-right">Total Hrs</th>
                                                <th width="60" class="text-right">Descuento</th>
                                                <th width="60" class="text-right">Prod. Hrs</th>
                                                <th width="70" class="text-right">Comb. Carg.</th>
                                                <th width="70" class="text-right">Costo Comb.</th>
                                                <th width="70" class="text-right">Rend. (Gal/h)</th>
                                                <th>Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl_q1">
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">No hay registros para la primera quincena</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- DETALLE DIARIO - 2DA QUINCENA -->
                                <h5 style="font-weight:bold; color:#334155; border-bottom:1px solid #cbd5e1; padding-bottom:5px; margin-top:30px;">DETALLE DIARIO: SEGUNDA QUINCENA (16 al Fin de Mes)</h5>
                                <div class="table-responsive" style="margin-bottom:20px;">
                                    <table class="table table-bordered table-condensed table-hover" style="font-size:11px;">
                                        <thead>
                                            <tr style="background:#3c8dbc; color:#fff;">
                                                <th width="40">Día</th>
                                                <th width="70">Fecha</th>
                                                <th>Operador</th>
                                                <th width="70" class="text-right">Hor. Inicial</th>
                                                <th width="70" class="text-right">Hor. Final</th>
                                                <th width="60" class="text-right">Total Hrs</th>
                                                <th width="60" class="text-right">Descuento</th>
                                                <th width="60" class="text-right">Prod. Hrs</th>
                                                <th width="70" class="text-right">Comb. Carg.</th>
                                                <th width="70" class="text-right">Costo Comb.</th>
                                                <th width="70" class="text-right">Rend. (Gal/h)</th>
                                                <th>Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl_q2">
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">No hay registros para la segunda quincena</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- RESUMEN MENSUAL -->
                                <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:15px; margin-top:30px;">
                                    <h5 style="font-weight:bold; color:#334155; margin-top:0; border-bottom:1px solid #cbd5e1; padding-bottom:5px;">RESUMEN MENSUAL Y OBSERVACIONES</h5>
                                    <div class="row">
                                        <div class="col-sm-8">
                                            <p style="font-size:12px; margin-bottom:5px;"><strong>Observación General:</strong></p>
                                            <p style="font-size:12px; color:#475569; font-style:italic;" id="res_obs_gen">El reporte consolida el total de horas de la máquina seleccionada durante el período especificado.</p>
                                        </div>
                                        <div class="col-sm-4">
                                            <table class="table table-condensed" style="font-size:12px; margin-bottom:0; background:transparent;">
                                                <tr>
                                                    <td><strong>Horas Trabajadas:</strong></td>
                                                    <td class="text-right" id="fin_ht">0.0</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Horas Productivas:</strong></td>
                                                    <td class="text-right text-success" id="fin_hp"><strong>0.0</strong></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Desfase Total:</strong></td>
                                                    <td class="text-right text-danger" id="fin_df">0.0</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Combustible Total:</strong></td>
                                                    <td class="text-right text-warning" id="fin_cb">0 Gls</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Costo Combustible Total:</strong></td>
                                                    <td class="text-right text-info" id="fin_cc">$ 0.00</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- Fin reporte individual -->

                            <!-- CONTENEDOR REPORTE CONSOLIDADO -->
                            <div id="contenedorReporteConsolidado" style="display:none; background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:8px;">
                                <!-- ENCABEZADO CONSOLIDADO -->
                                <div class="text-center" style="margin-bottom:20px; border-bottom:2px solid #3c8dbc; padding-bottom:10px;">
                                    <h4 style="font-weight:bold; color:#2c3e50; margin:0 0 5px 0;">PROYECTO AMBIENTAL ASOCIATIVO RELAVERA COMUNITARIA EL TABLÓN</h4>
                                    <h5 style="font-weight:bold; color:#3c8dbc; margin:0 0 10px 0;">REPORTE CONSOLIDADO DE MAQUINARIA</h5>
                                    <div class="row" style="font-size:12px; color:#64748b;">
                                        <div class="col-xs-6 text-left"><strong id="lbl_rep_con_periodo">PERÍODO: </strong></div>
                                        <div class="col-xs-6 text-right"><strong>EMISIÓN: </strong> <?php echo date('Y-m-d H:i'); ?></div>
                                    </div>
                                </div>

                                <!-- RESUMEN GENERAL (CARDS) -->
                                <div class="row" style="margin-bottom:20px;">
                                    <div class="col-md-3 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:15px; text-align:center;">
                                            <div style="font-size:12px; color:#64748b; font-weight:bold;">TOTAL HORAS TRAB.</div>
                                            <div style="font-size:22px; color:#0f172a; font-weight:bold;" id="con_hrs_trab">0.0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:15px; text-align:center;">
                                            <div style="font-size:12px; color:#64748b; font-weight:bold;">TOTAL HORAS PROD.</div>
                                            <div style="font-size:22px; color:#16a34a; font-weight:bold;" id="con_hrs_prod">0.0</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:15px; text-align:center;">
                                            <div style="font-size:12px; color:#64748b; font-weight:bold;">TOTAL COMBUSTIBLE</div>
                                            <div style="font-size:22px; color:#ca8a04; font-weight:bold;" id="con_comb">0 Gls</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-xs-6 mb-2">
                                        <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:15px; text-align:center;">
                                            <div style="font-size:12px; color:#64748b; font-weight:bold;">MÁQUINAS ACTIVAS</div>
                                            <div style="font-size:22px; color:#2563eb; font-weight:bold;" id="con_maquinas">0</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TABLA CONSOLIDADA -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-condensed table-hover table-striped" style="font-size:12px;">
                                        <thead>
                                            <tr style="background:#3c8dbc; color:#fff;">
                                                <th>Máquina</th>
                                                <th>Operador</th>
                                                <th class="text-right">Horas Trabajadas</th>
                                                <th class="text-right">Horas Productivas</th>
                                                <th class="text-right">Desfase</th>
                                                <th class="text-right">Comb. Carg.</th>
                                                <th class="text-right">Costo Comb.</th>
                                                <th class="text-right">Rend. (Gal/h)</th>
                                                <th class="text-center">Estado</th>
                                                <th class="text-center" width="90">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl_consolidado">
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">Genere el reporte para ver la información</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div> <!-- Fin reporte consolidado -->

                        </div>
                    </div>
                </div>
            </div>


            <!-- ==================== AMBIENTE 2: REGISTRO / FORMULARIO ==================== -->
            <div id="divFormulario" style="display:none;">

                <!-- HEADER DE CONTEXTO DEL TURNO -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; padding-bottom: 120px; margin-bottom: 20px;">
                    <form id="formContexto" class="form-horizontal" onsubmit="return false;">
                        <div class="row">
                            <div class="col-sm-3">
                                <label class="control-label" style="font-size:12px; margin-bottom:5px; color:#475569;">Máquina / Vehículo:</label>
                                <select id="Veh_Cod" name="Veh_Cod" class="form-control chosen-select" onchange="limpiarSubgrid(); buscarUltimoOperadorOriginal(this.value);">
                                    <option value="">Seleccione Máquina...</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label class="control-label" style="font-size:12px; margin-bottom:5px; color:#475569;">Operador:</label>
                                <select id="Cho_Cod" name="Cho_Cod" class="form-control chosen-select" onchange="limpiarSubgrid();">
                                    <option value="">Seleccione Operador...</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label class="control-label" style="font-size:12px; margin-bottom:5px; color:#475569;">Fecha del Turno:</label>
                                <input id="Hor_Fec" name="Hor_Fec" type="text" class="form-control datepicker" placeholder="dd/mm/aaaa" onchange="limpiarSubgrid();" />
                            </div>
                            <div class="col-sm-2" style="padding-top: 25px;">
                                <button type="button" class="btn btn-primary btn-block" onclick="cargarJornada();" style="height:30px; padding:4px; font-weight:bold;"><i class="glyphicon glyphicon-search"></i> Cargar</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- SUBGRID DE REGISTROS DE LA JORNADA -->
                <div id="panelJornada" style="display:none; border: 1px solid #e2e8f0; border-radius: 8px; overflow:hidden;">
                    <div style="background: #334a5f; color: #fff; padding: 10px 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="glyphicon glyphicon-time"></i> Bitácora de Horómetros de la Jornada</span>
                        <button type="button" class="btn btn-xs btn-success" style="font-weight:bold; border-radius:4px;" onclick="abrirModalRegistro(0);">
                            <i class="glyphicon glyphicon-plus"></i> Añadir Nuevo Registro
                        </button>
                    </div>
                    <div style="background: #fff; padding: 15px;">
                        <table class="table table-striped table-bordered table-condensed" id="tblJornada" style="font-size:12px; margin-bottom:0;">
                            <thead>
                                <tr style="background:#f1f5f9; color:#334a5f;">
                                    <th>Horómetro Inicial</th>
                                    <th>Evidencia Inicial</th>
                                    <th>Horómetro Final</th>
                                    <th>Evidencia Final</th>
                                    <th>Horas Trab.</th>
                                    <th>Ubicación / Área</th>
                                    <th>Estado</th>
                                    <th style="width:90px; text-align:center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se cargarán por AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="button-center" style="margin-top: 20px;">
                    <button type="button" class="btn btn-custom btn-default" onclick="mostrarListado();">
                        <i class="glyphicon glyphicon-arrow-left"></i> Volver a Consultas
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- ==================== MODAL: FORMULARIO DE INGRESO/EDICION HOROMETRO ==================== -->
    <div class="modal fade" id="modalRegistroHorometro" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document" style="width: 85%; max-width: 700px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header" style="background:#334a5f; color:#fff;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.8;">&times;</button>
                    <h4 class="modal-title" style="font-weight:bold; font-size:14px;" id="modalRegistroHorometroTitulo"><i class="glyphicon glyphicon-edit"></i> Editar Horómetro</h4>
                </div>
                <div class="modal-body" style="background:#f8fafc; padding:20px;">
                    <form id="formHorometroModal" class="form-horizontal" onsubmit="return false;" enctype="multipart/form-data">
                        <input type="hidden" id="Hor_Cod_Modal" name="Hor_Cod" value="0" />

                        <!-- Bloque Inicial -->
                        <fieldset class="exa-fieldset" style="background:#fff; border-color:#cbd5e1; border-top: 3px solid #3b82f6;">
                            <legend style="color:#1d4ed8;">Apertura (Horómetro Inicial)</legend>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Horómetro Inicial:<span class="text-danger">*</span></label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Ini" name="Hor_Ini" type="text" class="form-control calculo-horas" placeholder="Lectura inicial" onkeypress="return validar_decimal(event);" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Hora Inicial:</label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Hini" type="text" class="form-control" readonly style="background:#e2e8f0; font-weight:bold; color:#0f172a;" value="Automático" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="col-sm-5 control-label label-sm">Ubicación/Área:<span class="text-danger">*</span></label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Set" name="Hor_Set" type="text" class="form-control" placeholder="Área de trabajo" maxlength="50" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 text-center">
                                    <label class="label-sm" style="display:block; text-align:center; margin-bottom:5px;">Evidencia Inicial (Foto):</label>
                                    <input type="file" id="Hor_Img_Ini" name="Hor_Img_Ini" class="form-control" style="height:auto; padding:3px; margin-bottom:5px; font-size:11px;" accept="image/*" />
                                    <div id="preview_ini_container" style="border: 1px dashed #cbd5e1; border-radius:4px; height:80px; display:flex; align-items:center; justify-content:center; background:#f1f5f9;">
                                        <span style="color:#94a3b8; font-size:11px;">(Suba una foto clara)</span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bloque Final -->
                        <fieldset class="exa-fieldset" id="fsHorometroFinal" style="background:#fff; border-color:#cbd5e1; border-top: 3px solid #10b981; margin-bottom:10px;">
                            <legend style="color:#047857;">Cierre (Horómetro Final)</legend>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Horómetro Final:<span class="text-danger">*</span></label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Fin" name="Hor_Fin" type="text" class="form-control calculo-horas" placeholder="Lectura final" onkeypress="return validar_decimal(event);" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <label class="col-sm-5 control-label label-sm">Hora Final:</label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Hfin" type="text" class="form-control" readonly style="background:#e2e8f0; font-weight:bold; color:#0f172a;" value="Automático" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="col-sm-5 control-label label-sm">Horas Calculadas:</label>
                                        <div class="col-sm-7">
                                            <input id="Hor_Hrs" type="text" class="form-control" readonly style="background:#e2e8f0; font-weight:bold; color: #0f172a;" value="0.00" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 text-center">
                                    <label class="label-sm" style="display:block; text-align:center; margin-bottom:5px;">Evidencia Final (Foto):</label>
                                    <input type="file" id="Hor_Img_Fin" name="Hor_Img_Fin" class="form-control" style="height:auto; padding:3px; margin-bottom:5px; font-size:11px;" accept="image/*" />
                                    <div id="preview_fin_container" style="border: 1px dashed #cbd5e1; border-radius:4px; height:80px; display:flex; align-items:center; justify-content:center; background:#f1f5f9;">
                                        <span style="color:#94a3b8; font-size:11px;">(Suba una foto clara)</span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Observaciones Adicionales -->
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="col-sm-2 control-label label-sm" style="text-align:left; padding-left:22px;">Observaciones:</label>
                            <div class="col-sm-10" style="padding-right:22px;">
                                <textarea id="Hor_Obs" name="Hor_Obs" class="form-control" style="height: 50px; resize:none;" placeholder="Detalles o eventualidades adicionales..."></textarea>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer" style="background:#fff; border-top:1px solid #e2e8f0;">
                    <button type="button" id="btnGuardarHorometro" class="btn btn-sm btn-primary" onclick="guardarHorometroModal();" style="font-weight:bold; padding:6px 20px;"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Registro</button>
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>

    <!-- ==================== MODAL 3: AUDITORÍA / HISTORIAL DE ESTADOS ==================== -->
    <div class="modal fade" id="modalAuditoria" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="width: 85%; max-width: 650px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title modal-title-custom"><i class="glyphicon glyphicon-time"></i> Historial de Cambios y Auditoría de Estados</h4>
                </div>
                <div class="modal-body" style="background:#f8fafc; padding:20px;">
                    <div style="max-height: 250px; overflow-y:auto; border: 1px solid #cbd5e1; border-radius:6px; background:#fff;">
                        <table class="table table-striped table-condensed" id="tblAuditoria" style="margin-bottom:0; font-size:11.5px;">
                            <thead>
                                <tr style="background:#475569; color:#fff;">
                                    <th style="padding: 8px;">Fecha/Hora</th>
                                    <th>Estado Anterior</th>
                                    <th>Nuevo Estado</th>
                                    <th>Justificación / Observaciones</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted" style="padding:20px;">Cargando historial de auditoría...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cargador Visual / Loader -->
    <div id="loader" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; text-align: center; padding-top: 20%;">
        <div style="display: inline-block; padding: 25px 35px; background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #334a5f;"></i>
            <div style="margin-top: 15px; font-weight: bold; color: #334a5f; font-size: 14px;">Procesando solicitud...</div>
        </div>
    </div>

    <!-- Modal Cambio de Estado (Aprobar/Rechazar/Revisar/Anular con observaciones) -->
    <div class="modal fade" id="modalCambioEstado" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="width: 85%; max-width: 480px;">
            <div class="modal-content" style="border-radius:8px; overflow:hidden;">
                <div class="modal-header" id="modalCambioEstadoHeader">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title modal-title-custom" id="modalCambioEstadoTitulo">Cambio de Estado</h4>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <form id="formCambioEstado" onsubmit="return false;">
                        <input type="hidden" id="Hor_Cod_Est" value="0" />
                        <input type="hidden" id="Hor_Est_Nue" value="" />
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-weight:bold; color:#475569; font-size:12px; margin-bottom:6px;" id="lblJustificacion">Observaciones / Justificación:<span class="text-danger">*</span></label>
                            <textarea id="Hhi_Obs" class="form-control" style="height: 80px; resize:none;" placeholder="Escriba el motivo de la acción..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="background:#f8fafc;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="guardarCambioEstado();"><span class="glyphicon glyphicon-floppy-disk"></span> Aplicar Cambio</button>
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visor de Evidencias -->
    <div id="modalVisorEvidencia" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal-header-custom" style="background:#0f172a; color:#fff; padding:10px 15px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.8;">&times;</button>
                    <h4 class="modal-title modal-title-custom" id="modalVisorEvidenciaTitulo"><i class="glyphicon glyphicon-picture"></i> Evidencias Fotográficas</h4>
                </div>
                <div class="modal-body" style="padding:20px; text-align:center; background:#f8fafc;">
                    <div id="visor_loader" style="display:none; color:#64748b; margin-bottom:15px;"><i class="glyphicon glyphicon-refresh fast-spin"></i> Cargando imágenes desde el servidor...</div>
                    <div class="row">
                        <div class="col-sm-6">
                            <h5 style="font-weight:bold; color:#334155; margin-bottom:10px;">Evidencia Inicial</h5>
                            <div id="visor_img_ini" style="width:100%; height:200px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; border:1px solid #cbd5e1; border-radius:4px; overflow:hidden;"></div>
                        </div>
                        <div class="col-sm-6">
                            <h5 style="font-weight:bold; color:#334155; margin-bottom:10px;">Evidencia Final</h5>
                            <div id="visor_img_fin" style="width:100%; height:200px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; border:1px solid #cbd5e1; border-radius:4px; overflow:hidden;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f1f5f9; padding:10px;">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="../VALIDACIONES/man_val_alt_maquinaria_horometro.js?v=12"></script>

    <!-- Liberacion y cierre de conexiones -->
    <?php
    $obBD_con1->liberar();
    $obBD_conexion->cerrar();
    ?>
</BODY>

</HTML>