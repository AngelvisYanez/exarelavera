<?php

/* DIRECTORIOS REQUERIDOS */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_tec_camp_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Manifiesto_Tecnico($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Manifiesto_Tecnico;

/* formato para fechas */
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* para pruebas */
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 9600);

/* DECLARACION DE AJAX */

// Cargar niveles de humedad para el select
if (isset($loadHumedadAjax)) {
    $resp = $obBD_con1->getArrayConsulta(1, "", $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar manifiestos para el select
if (isset($loadManifiestoAjax)) {
    $resp = $obBD_con1->getArrayConsulta(8, "", $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Buscar manifiestos para el modal de búsqueda
if (isset($manifiestosAjax)) {
    // Obtener parámetros de búsqueda, verificando múltiples fuentes
    $filtro = '';
    $search = '';
    $estado = 'A';

    // Intentar obtener desde $_REQUEST primero, luego desde variables globales
    if (isset($_REQUEST['op_opciones']) && $_REQUEST['op_opciones'] !== '') {
        $filtro = $_REQUEST['op_opciones'];
    } elseif (isset($op_opciones) && $op_opciones !== '') {
        $filtro = $op_opciones;
    }

    // El campo de búsqueda puede llamarse 'search' o 'searchMan' dependiendo de la configuración
    if (isset($_REQUEST['search']) && trim($_REQUEST['search']) !== '') {
        $search = trim($_REQUEST['search']);
    } elseif (isset($_REQUEST['searchMan']) && trim($_REQUEST['searchMan']) !== '') {
        $search = trim($_REQUEST['searchMan']);
    } elseif (isset($search) && trim($search) !== '') {
        $search = trim($search);
    } elseif (isset($searchMan) && trim($searchMan) !== '') {
        $search = trim($searchMan);
    }

    if (isset($_REQUEST['op_opciones2']) && $_REQUEST['op_opciones2'] !== '') {
        $estado = $_REQUEST['op_opciones2'];
    } elseif (isset($op_opciones2) && $op_opciones2 !== '') {
        $estado = $op_opciones2;
    }

    // Obtener Man_Tip del formulario
    $man_tip = 'P'; // Valor por defecto
    if (isset($_REQUEST['Man_Tip']) && trim($_REQUEST['Man_Tip']) !== '') {
        $man_tip = trim($_REQUEST['Man_Tip']);
    } elseif (isset($Man_Tip) && trim($Man_Tip) !== '') {
        $man_tip = trim($Man_Tip);
    }

    $parms = array(
        'filtro' => $filtro,
        'search' => $search,
        'Man_Tip' => $man_tip
        //'estado' => $estado
    );
    $resp = $obBD_con1->getArrayConsulta(10, $parms, $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

// Obtener el siguiente código de manifiesto técnico
if (isset($getLastMatAjax)) {
    $resp['success'] = false;
    $last_Mat = $obBD_con1->getRowConsulta(2, "", $obBD_conexion);
    if ($last_Mat['sig'] == 0) {
        $resp['data'] = "1";
    } else {
        $resp['data'] = $last_Mat['sig'];
    }
    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['message'] = "Transaccion exitosa!";
    }
    $obBD_con1->echoJson($resp);
    exit();
}

// Obtener el siguiente código de manifiesto
if (isset($getLastManAjax)) {
    $resp['success'] = false;
    $last_Man = $obBD_con1->getRowConsulta(9, "", $obBD_conexion);
    if ($last_Man['sig'] == 0) {
        $resp['data'] = "1";
    } else {
        $resp['data'] = $last_Man['sig'];
    }
    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['message'] = "Transaccion exitosa!";
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/* Inicia la insercion o actualizacion de un manifiesto técnico */
// if(isset($saveManiTecAjax)){
//     try{
//     // Verificar si es una edición (UPDATE) o un nuevo registro (INSERT)
//     $esEdicion = isset($_POST['Mat_Cod']) && !empty($_POST['Mat_Cod']);

//     // Recoger datos POST necesarios
//     $valores = array(
//         'Man_Cod' => isset($_POST['Man_Cod']) ? $_POST['Man_Cod'] : '',
//         'Usu_Cod' => $Ses_Usu_Cod,
//         'Hum_Cod' => isset($_POST['Hum_Cod']) ? $_POST['Hum_Cod'] : '',
//         // 'Mat_Rso' => isset($_POST['Mat_Rso']) ? $_POST['Mat_Rso'] : '',
//         'Mat_Dna' => isset($_POST['Mat_Dna']) ? $_POST['Mat_Dna'] : '',
//         'Mat_Fde' => isset($_POST['Mat_Fde']) ? $_POST['Mat_Fde'] : '',
//         'Mat_Eae' => isset($_POST['Mat_Eae']) ? $_POST['Mat_Eae'] : '',
//         'Mat_Ear' => isset($_POST['Mat_Ear']) ? $_POST['Mat_Ear'] : '',
//         'Mat_Oce' => isset($_POST['Mat_Oce']) ? $_POST['Mat_Oce'] : '',
//         'Mat_Tra' => isset($_POST['Mat_Tra']) ? $_POST['Mat_Tra'] : ''
//     );

//     $resp['success'] = false;

//     if ($esEdicion) {
//         // Es una actualización (UPDATE) - mantener el usuario original
//         $valores['Mat_Cod'] = $_POST['Mat_Cod'];
//         // Obtener el registro actual para mantener el Usu_Cod original
//         $valores_consulta = array('Mat_Cod' => $_POST['Mat_Cod']);
//         $registro_actual = $obBD_con1->getRowConsulta(5, $valores_consulta, $obBD_conexion);
//         if ($registro_actual && isset($registro_actual['Usu_Cod'])) {
//             $valores['Usu_Cod'] = $registro_actual['Usu_Cod'];
//         }
//         $operacion = $obBD_con1->operacionobBD(6, $valores, $obBD_conexion);        

//         if ($obBD_con1->Error == 0) {
//             // Actualizar el estado del manifiesto: Man_Tip a 'GS' y añadir 'A' y 'GS' a Man_Tes
//             $manifiesto_actual = $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('Man_Cod' => $_POST['Man_Cod'])), $obBD_conexion, true);
//             $man_tes_actual = isset($manifiesto_actual['Man_Tes']) ? $manifiesto_actual['Man_Tes'] : '';
//             // Dividir por guiones
//             $partes = explode('-', $man_tes_actual);
//             $partes_filtradas = array();
//             $tiene_A = false;
//             $tiene_GS = false;
//             foreach($partes as $parte){
//                 $parte = trim($parte);
//                 if(!empty($parte)){
//                     $partes_filtradas[] = $parte;
//                     // Verificar si ya tiene 'A' o 'GS'
//                     if($parte === 'A'){
//                         $tiene_A = true;
//                     }
//                     if($parte === 'GS'){
//                         $tiene_GS = true;
//                     }
//                 }
//             }
//             // Añadir 'A' si no existe
//             if(!$tiene_A){
//                 $partes_filtradas[] = 'A';
//             }
//             // Añadir 'GS' si no existe
//             if(!$tiene_GS){
//                 $partes_filtradas[] = 'GS';
//             }
//             $man_tes_nuevo = implode('-', $partes_filtradas);
//             // Limpiar guiones dobles o al inicio/final
//             $man_tes_nuevo = trim($man_tes_nuevo, '-');
//             $man_tes_nuevo = preg_replace('/-+/', '-', $man_tes_nuevo);
//             // Actualizar Man_Tip a 'GS' y Man_Tes con 'A' y 'GS'
//             $obBD_con1->operacionobBD('manifiesto.update', array('Man_Tip'=>'GS', 'Man_Tes'=>$man_tes_nuevo, 'where'=>array('Man_Cod'=>$_POST['Man_Cod'])), $obBD_conexion);
//             $resp['success'] = true;
//             $resp['message'] = 'Manifiesto técnico actualizado correctamente';
//         } else {
//             $resp['message'] = 'Error al actualizar: ' . $obBD_con1->MsgError;
//         }
//     } else {
//         // Obtener el siguiente código
//         $last_Mat = $obBD_con1->getRowConsulta(2, "", $obBD_conexion);
//         $valores['Mat_Cod'] = $last_Mat['sig'];
//         /* CAMBIO DE TIPO DE MANIFIESTO A APROBADO */
//         // $obBD_con1->operacionobBD('manifiesto.update', array('Man_Tip'=>'A', 'where'=>array('Man_Cod'=>$_POST['Man_Cod'])), $obBD_conexion);
//         $operacion = $obBD_con1->operacionobBD(3, $valores, $obBD_conexion);

//         if ($obBD_con1->Error == 0) {
//             // Actualizar el estado del manifiesto: Man_Tip a 'GS' y añadir 'A' y 'GS' a Man_Tes
//             $man_cod = isset($_POST['Man_Cod']) ? addslashes($_POST['Man_Cod']) : '';
//             if(!empty($man_cod)){
//                 $manifiesto_actual = $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('Man_Cod' => $_POST['Man_Cod'])), $obBD_conexion, true);
//                 $man_tes_actual = isset($manifiesto_actual['Man_Tes']) ? $manifiesto_actual['Man_Tes'] : '';
//                 // Dividir por guiones
//                 $partes = explode('-', $man_tes_actual);
//                 $partes_filtradas = array();
//                 $tiene_A = false;
//                 $tiene_GS = false;
//                 foreach($partes as $parte){
//                     $parte = trim($parte);
//                     if(!empty($parte)){
//                         $partes_filtradas[] = $parte;
//                         // Verificar si ya tiene 'A' o 'GS'
//                         if($parte === 'A'){
//                             $tiene_A = true;
//                         }
//                         if($parte === 'GS'){
//                             $tiene_GS = true;
//                         }
//                     }
//                 }
//                 // Añadir 'A' si no existe
//                 if(!$tiene_A){
//                     $partes_filtradas[] = 'A';
//                 }
//                 // Añadir 'GS' si no existe
//                 if(!$tiene_GS){
//                     $partes_filtradas[] = 'GS';
//                 }
//                 $man_tes_nuevo = implode('-', $partes_filtradas);
//                 // Limpiar guiones dobles o al inicio/final
//                 $man_tes_nuevo = trim($man_tes_nuevo, '-');
//                 $man_tes_nuevo = preg_replace('/-+/', '-', $man_tes_nuevo);
//                 // Actualizar Man_Tip a 'GS' y Man_Tes con 'A' y 'GS'
//                 $obBD_con1->operacionobBD('manifiesto.update', array('Man_Tip'=>'GS', 'Man_Tes'=>$man_tes_nuevo, 'where'=>array('Man_Cod'=>$_POST['Man_Cod'])), $obBD_conexion);
//             }
//             $resp['success'] = true;
//             $resp['message'] = 'Manifiesto técnico registrado correctamente';
//             $resp['Mat_Cod'] = $valores['Mat_Cod'];
//         } else {
//             $resp['message'] = 'Error al registrar: ' . $obBD_con1->MsgError;
//         }
//     }

//     } catch (Exception $e) {
//         $obBD_con1->rollBack_nomsn($obBD_conexion);
//         $resp['success'] = false;
//         $resp['message'] = 'Excepción al registrar: ' . $e->getMessage();
//     }
//     $obBD_con1->echoJson($resp);
//     exit();
// }

if (isset($saveManiTecAjax)) {
    try {
        // Verificar si es una edición (UPDATE) o un nuevo registro (INSERT)
        $esEdicion = isset($_POST['Mat_Cod']) && !empty($_POST['Mat_Cod']);

        // Antes de guardar, inactivar registros previos del mismo Man_Cod para mantener historial
        if (isset($_POST['Man_Cod']) && !empty($_POST['Man_Cod'])) {
            $obBD_con1->operacionobBD(14, array('Man_Cod' => $_POST['Man_Cod']), $obBD_conexion);
        }

        // Recoger datos POST necesarios
        $valores = array(
            'Man_Cod' => isset($_POST['Man_Cod']) ? $_POST['Man_Cod'] : '',
            'Usu_Cod' => $Ses_Usu_Cod,
            'Hum_Cod' => isset($_POST['Hum_Cod']) ? $_POST['Hum_Cod'] : '',
            // 'Mat_Rso' => isset($_POST['Mat_Rso']) ? $_POST['Mat_Rso'] : '',
            'Mat_Dna' => isset($_POST['Mat_Dna']) ? $_POST['Mat_Dna'] : '',
            'Mat_Fde' => isset($_POST['Mat_Fde']) ? $_POST['Mat_Fde'] : '',
            'Mat_Eae' => isset($_POST['Mat_Eae']) ? $_POST['Mat_Eae'] : '',
            'Mat_Ear' => isset($_POST['Mat_Ear']) ? $_POST['Mat_Ear'] : '',
            'Mat_Oce' => isset($_POST['Mat_Oce']) ? $_POST['Mat_Oce'] : '',
            'Mat_Tra' => isset($_POST['Mat_Tra']) ? $_POST['Mat_Tra'] : ''
        );

        $resp['success'] = false;

        if ($esEdicion) {
            // Es una actualización (UPDATE) - mantener el usuario original
            $valores['Mat_Cod'] = $_POST['Mat_Cod'];
            // Obtener el registro actual para mantener el Usu_Cod original
            $valores_consulta = array('Mat_Cod' => $_POST['Mat_Cod']);
            $registro_actual = $obBD_con1->getRowConsulta(5, $valores_consulta, $obBD_conexion);
            if ($registro_actual && isset($registro_actual['Usu_Cod'])) {
                $valores['Usu_Cod'] = $registro_actual['Usu_Cod'];
            }
            // $operacion = $obBD_con1->operacionobBD(6, $valores, $obBD_conexion);

            // Generar un nuevo Mat_Cod para la edición (manteniendo el historial)
            $last_Mat = $obBD_con1->getRowConsulta(2, "", $obBD_conexion);
            $valores['Mat_Cod'] = $last_Mat['sig'];

            $operacion = $obBD_con1->operacionobBD(3, $valores, $obBD_conexion);

            if ($obBD_con1->Error == 0) {
                // Actualizar el estado del manifiesto: Man_Tip a 'GS' o 'R'
                $manifiesto_actual = $obBD_con1->getRowConsulta(12, array('where' => array('Man_Cod' => $_POST['Man_Cod'])), $obBD_conexion);
                $man_tes_actual = isset($manifiesto_actual['Man_Tes']) ? $manifiesto_actual['Man_Tes'] : '';

                // Determinar estado objetivo
                $target_man_tip = (isset($_POST['Man_Tip']) && $_POST['Man_Tip'] === 'R') ? 'R' : 'GS';
                $man_tes_nuevo = '';

                if ($target_man_tip === 'R') {
                    // Lógica para RECHAZADO
                    // Añadir 'R' si no existe
                    $partes = explode('-', $man_tes_actual);
                    $tiene_R = false;
                    foreach ($partes as $parte) {
                        if (trim($parte) === 'R') $tiene_R = true;
                    }
                    if (!$tiene_R) {
                        $man_tes_nuevo = $man_tes_actual . '-R';
                    } else {
                        $man_tes_nuevo = $man_tes_actual;
                    }
                } else {
                    // Lógica existente para GS
                    // Dividir por guiones
                    $partes = explode('-', $man_tes_actual);
                    $partes_filtradas = array();
                    $tiene_A = false;
                    $tiene_GS = false;
                    foreach ($partes as $parte) {
                        $parte = trim($parte);
                        if (!empty($parte)) {
                            $partes_filtradas[] = $parte;
                            // Verificar si ya tiene 'A' o 'GS'
                            if ($parte === 'A') {
                                $tiene_A = true;
                            }
                            if ($parte === 'GS') {
                                $tiene_GS = true;
                            }
                        }
                    }
                    // Añadir 'A' si no existe
                    if (!$tiene_A) {
                        $partes_filtradas[] = 'A';
                    }
                    // Añadir 'GS' si no existe
                    if (!$tiene_GS) {
                        $partes_filtradas[] = 'GS';
                    }
                    $man_tes_nuevo = implode('-', $partes_filtradas);
                }

                // Limpiar guiones
                $man_tes_nuevo = trim($man_tes_nuevo, '-');
                $man_tes_nuevo = preg_replace('/-+/', '-', $man_tes_nuevo);

                // Lógica para actualizar Man_Usu (Append JSON)
                $man_usu_actual = isset($manifiesto_actual['Man_Usu']) ? $manifiesto_actual['Man_Usu'] : '';
                $man_usu_arr = array();
                if (!empty($man_usu_actual)) {
                    $decoded = json_decode($man_usu_actual, true);
                    if (is_array($decoded)) {
                        // Si es un array indexado (lista de eventos) o asociativo (un solo evento)
                        if (isset($decoded[0])) $man_usu_arr = $decoded;
                        else $man_usu_arr[] = $decoded;
                    }
                }
                // Agregar nuevo evento
                $man_usu_arr[] = array(
                    'Usu_Cod' => $Ses_Usu_Cod,
                    'Man_Tip' => $target_man_tip,
                    'Fecha' => date('Y-m-d H:i:s')
                );
                $man_usu_json = addslashes(json_encode($man_usu_arr));

                $update_params = array('Man_Tip' => $target_man_tip, 'Man_Tes' => $man_tes_nuevo, 'Man_Usu' => $man_usu_json, 'where' => array('Man_Cod' => $_POST['Man_Cod']));

                if ($target_man_tip === 'R') {
                    $update_params['Man_Est'] = 'I';
                }

                $obBD_con1->operacionobBD(13, $update_params, $obBD_conexion);
                $resp['success'] = true;
                $resp['message'] = 'Manifiesto técnico actualizado correctamente';
            } else {
                $resp['message'] = 'Error al actualizar: ' . $obBD_con1->MsgError;
            }
        } else {
            // Obtener el siguiente código
            $last_Mat = $obBD_con1->getRowConsulta(2, "", $obBD_conexion);
            $valores['Mat_Cod'] = $last_Mat['sig'];
            /* CAMBIO DE TIPO DE MANIFIESTO A APROBADO */
            // $obBD_con1->operacionobBD('manifiesto.update', array('Man_Tip'=>'A', 'where'=>array('Man_Cod'=>$_POST['Man_Cod'])), $obBD_conexion);
            $operacion = $obBD_con1->operacionobBD(3, $valores, $obBD_conexion);

            if ($obBD_con1->Error == 0) {
                // Actualizar el estado del manifiesto: Man_Tip a 'GS' o 'R'
                $man_cod = isset($_POST['Man_Cod']) ? addslashes($_POST['Man_Cod']) : '';
                if (!empty($man_cod)) {
                    $manifiesto_actual = $obBD_con1->getRowConsulta(12, array('where' => array('Man_Cod' => $_POST['Man_Cod'])), $obBD_conexion);
                    $man_tes_actual = isset($manifiesto_actual['Man_Tes']) ? $manifiesto_actual['Man_Tes'] : '';

                    // Determinar estado objetivo
                    $mat_eae = isset($_POST['Mat_Eae']) ? $_POST['Mat_Eae'] : '';
                    $target_man_tip = ((isset($_POST['Man_Tip']) && $_POST['Man_Tip'] === 'R') || $mat_eae === 'R') ? 'R' : 'GS';
                    $man_tes_nuevo = '';

                    if ($target_man_tip === 'R') {
                        // Lógica para RECHAZADO
                        $partes = explode('-', $man_tes_actual);
                        $tiene_R = false;
                        foreach ($partes as $parte) {
                            if (trim($parte) === 'R') $tiene_R = true;
                        }
                        if (!$tiene_R) {
                            $man_tes_nuevo = $man_tes_actual . '-R';
                        } else {
                            $man_tes_nuevo = $man_tes_actual;
                        }
                    } else {
                        // Lógica existente para GS
                        $partes = explode('-', $man_tes_actual);
                        $partes_filtradas = array();
                        $tiene_A = false;
                        $tiene_GS = false;
                        foreach ($partes as $parte) {
                            $parte = trim($parte);
                            if (!empty($parte)) {
                                $partes_filtradas[] = $parte;
                                if ($parte === 'A') $tiene_A = true;
                                if ($parte === 'GS') $tiene_GS = true;
                            }
                        }
                        if (!$tiene_A) $partes_filtradas[] = 'A';
                        if (!$tiene_GS) $partes_filtradas[] = 'GS';
                        $man_tes_nuevo = implode('-', $partes_filtradas);
                    }

                    // Limpiar guiones
                    $man_tes_nuevo = trim($man_tes_nuevo, '-');
                    $man_tes_nuevo = preg_replace('/-+/', '-', $man_tes_nuevo);

                    // Lógica para actualizar Man_Usu (Append JSON)
                    $man_usu_actual = isset($manifiesto_actual['Man_Usu']) ? $manifiesto_actual['Man_Usu'] : '';
                    $man_usu_arr = array();
                    if (!empty($man_usu_actual)) {
                        $decoded = json_decode($man_usu_actual, true);
                        if (is_array($decoded)) {
                            // Si es un array indexado (lista de eventos) o asociativo (un solo evento)
                            if (isset($decoded[0])) $man_usu_arr = $decoded;
                            else $man_usu_arr[] = $decoded;
                        }
                    }
                    // Agregar nuevo evento
                    $man_usu_arr[] = array(
                        'Usu_Cod' => $Ses_Usu_Cod,
                        'Man_Tip' => $target_man_tip,
                        'Fecha' => date('Y-m-d H:i:s')
                    );
                    $man_usu_json = addslashes(json_encode($man_usu_arr));

                    $update_params = array('Man_Tip' => $target_man_tip, 'Man_Tes' => $man_tes_nuevo, 'Man_Usu' => $man_usu_json, 'where' => array('Man_Cod' => $_POST['Man_Cod']));

                    if ($target_man_tip === 'R') {
                        $update_params['Man_Est'] = 'I';
                    }

                    $obBD_con1->operacionobBD(13, $update_params, $obBD_conexion);
                }
                $resp['success'] = true;
                $resp['message'] = 'Manifiesto técnico registrado correctamente';
                $resp['Mat_Cod'] = $valores['Mat_Cod'];
            } else {
                $resp['message'] = 'Error al registrar: ' . $obBD_con1->MsgError;
            }
        }

        //Enviar mensaje mediante whatsapp a los choferes y plantas
        $datos_ge = $obBD_con1->getRowConsulta(15, array('Man_Cod' => $_POST['Man_Cod']), $obBD_conexion);
        $pla_wat = isset($datos_ge['Pla_Wat']) ? $datos_ge['Pla_Wat'] : '';
        if ($pla_wat == 'S' /*&& $esEdicion == 1*/) {
            $fecha_actual = date('Y-m-d H:i:s'); //Fecha y hora actaul
            $fecha_entrada = isset($datos_ge['fecha_ge']) ? $datos_ge['fecha_ge'] : '';
            $chofer_nombre = isset($datos_ge['chofer_nombre']) ? $datos_ge['chofer_nombre'] : '';
            $tel_chofer = ltrim(isset($datos_ge['tel_chofer']) ? $datos_ge['tel_chofer'] : '', "0");
            $pla_nom = isset($datos_ge['Pla_Nom']) ? $datos_ge['Pla_Nom'] : '';
            $tel_planta = ltrim(isset($datos_ge['tel_admin_planta']) ? $datos_ge['tel_admin_planta'] : '', "0");
            $tiempo_dentro_relavera =  date_diff(new DateTime($fecha_entrada), new DateTime($fecha_actual))->format('%H:%I:%S');
            $icono_notificacion = html_entity_decode('&#128227;', ENT_QUOTES, 'UTF-8');
            $icono_tiempo = html_entity_decode('&#9200;', ENT_QUOTES, 'UTF-8');
            $mensaje = $icono_notificacion . ' Sr. *' . $chofer_nombre . '* su tiempo dentro de la RELAVERA es el siguiente:\n'.
                  '- Entrada: *' . $fecha_entrada . '*\n'.
                  '- Salida: *' . $fecha_actual . '*\n'.
                   '- Planta: *' . $pla_nom . '*\n'.
                $icono_tiempo . ' Tiempo total *' .  $tiempo_dentro_relavera . '*';
            $tel_chofer = '+593' . $tel_chofer;
            $tel_planta = '';// '+593' . $tel_planta;
            enviarMensajeWhatsapp($mensaje, $tel_chofer, $tel_planta);
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al registrar: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

function enviarMensajeWhatsapp($mensaje, $tel_cliente, $tel_planta)
{
    $resultados = array();
    $numeros = array();
    if (!empty($tel_cliente)) $numeros[] = $tel_cliente;
    if (!empty($tel_planta) && $tel_planta != $tel_cliente) $numeros[] = $tel_planta;
    foreach ($numeros as $numero) {
        $params = array('token' => 'ao5aoi2f77trfaxc', 'to' => $numero, 'body' => $mensaje);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ultramsg.com/instance164295/messages/chat",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array("Content-Type: application/json"),

            /*CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => array("content-type: application/x-www-form-urlencoded"),*/
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $resultados[$numero] = !$err;
    }
    return !in_array(false, $resultados, true);
}


// Búsqueda por código QR en el modal
if (isset($_GET['buscarPorQRAjax']) || isset($buscarPorQRAjax)) {
    $resp = array('success' => false, 'message' => '', 'data' => null);

    if (!isset($_GET['codigo_qr'])) {
        $resp['message'] = 'Debe ingresar un código QR';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $codigo_qr = trim($_GET['codigo_qr']);
    if (empty($codigo_qr)) {
        $resp['message'] = 'Debe ingresar un código QR';
        $obBD_con1->echoJson($resp);
        exit();
    }

    // El código QR puede venir en formato JSON: {"cliente":"...","Man_Cod":"...","Man_Num":"..."}
    // O directamente como Man_Cod
    $man_cod = '';

    // Intentar decodificar como JSON primero
    $qr_data = json_decode($codigo_qr, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($qr_data) && isset($qr_data['Man_Cod'])) {
        // Es JSON, extraer Man_Cod
        $man_cod = trim($qr_data['Man_Cod']);
    } else {
        // No es JSON, intentar extraer de texto plano con formato "CODIGO: X"
        // Buscar el patrón "CODIGO:" seguido de un número
        if (preg_match('/CODIGO:\s*(\d+)/i', $codigo_qr, $matches)) {
            $man_cod = trim($matches[1]);
        } else {
            // Si no encuentra el patrón, asumir que es directamente el Man_Cod
            $man_cod = trim($codigo_qr);
        }
    }

    // Validar que se haya obtenido Man_Cod
    if (empty($man_cod)) {
        $resp['success'] = false;
        $resp['message'] = 'El código QR no contiene un Man_Cod válido.';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $valores = array(
        'man_cod' => $man_cod
    );
    $data = $obBD_con1->getRowConsulta(11, $valores, $obBD_conexion);

    if ($obBD_con1->Error == 0) {
        if ($data && !empty($data)) {
            $resp['success'] = true;
            $resp['data'] = $data;
            $resp['message'] = 'Manifiesto encontrado';
        } else {
            $resp['success'] = false;
            $resp['message'] = 'No existe el manifiesto buscado';
        }
    } else {
        $resp['message'] = 'Error en la consulta: ' . $obBD_con1->MsgError;
    }

    $obBD_con1->echoJson($resp);
    exit();
}

/* Carga de datos para el grid principal */
if (isset($LoadManifTecAjax)) {
    $filtro = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : '';
    $man_tip = isset($_GET['Man_Tip']) ? $_GET['Man_Tip'] : '';

    // Si el filtro es por Manifiesto ('m'), forzar Man_Tip a 'T' (Todos) para ignorar el estado
    if ($filtro === 'm') {
        $man_tip = 'T';
    }

    // Establecer fechas de hoy si no están especificadas
    $fec_ini = (isset($_GET['Fec_IniM']) && $_GET['Fec_IniM'] !== '') ? $_GET['Fec_IniM'] : $hoy;
    $fec_fin = (isset($_GET['Fec_FinM']) && $_GET['Fec_FinM'] !== '') ? $_GET['Fec_FinM'] : $hoy;

    $parms = array(
        // 'Fec_IniM' => isset($_GET['Fec_IniM']) ? $_GET['Fec_IniM'] : '',
        // 'Fec_FinM' => isset($_GET['Fec_FinM']) ? $_GET['Fec_FinM'] : '',
        'Fec_IniM' => $fec_ini,
        'Fec_FinM' => $fec_fin,
        'filtro' => $filtro,
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'Pla_Cod' => isset($_GET['Pla_Cod']) ? $_GET['Pla_Cod'] : '',
        'Man_Num' => isset($_GET['Man_Num']) ? $_GET['Man_Num'] : '',
        'Man_Tip' => $man_tip,
        'ordenar' => isset($_GET['ordenar']) ? $_GET['ordenar'] : 'codigo_desc'
    );
    $resp = $obBD_con1->getArrayConsulta(4, $parms, $obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit();
}

/* Eliminar el registro del manifiesto técnico */
if (isset($deleteManiTecAjax)) {
    $resp = array();
    $resp['success'] = false;
    try {
        $valores = array('Mat_Cod' => $_POST['Mat_Cod']);
        $obBD_con1->operacionobBD(7, $valores, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Manifiesto técnico eliminado correctamente';
        } else {
            $resp['success'] = false;
            $resp['message'] = 'Error al eliminar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['success'] = false;
        $resp['message'] = 'Excepción al eliminar: ' . $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit();
}

/* Obtener datos completos de un manifiesto técnico para editar */
if (isset($getManifTecAjax)) {
    $resp = array();
    $resp['success'] = false;

    if (!isset($_GET['Mat_Cod']) || empty($_GET['Mat_Cod'])) {
        $resp['message'] = 'No se proporcionó el código del manifiesto técnico';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $valores = array('Mat_Cod' => $_GET['Mat_Cod']);
    $data = $obBD_con1->getRowConsulta(5, $valores, $obBD_conexion);

    if ($obBD_con1->Error == 0 && $data) {
        $resp['success'] = true;
        $resp['data'] = $data;
        $resp['message'] = 'Datos cargados correctamente';
    } else {
        $resp['message'] = 'No se encontraron datos del manifiesto técnico: ' . $obBD_con1->MsgError;
    }

    $obBD_con1->echoJson($resp);
    exit();
}

/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);

?>

<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?php echo "Manifiesto Técnico"; ?></TITLE>
    <meta charset="UTF-8">




    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        #qr-reader-container-modal {
            text-align: center;
            position: relative;
        }

        #qr-reader-modal {
            min-height: 250px;
        }

        #qr-reader-modal video {
            width: 100%;
            max-width: 400px;
            border: 2px solid #333;
            border-radius: 5px;
        }

        .btn-rescan-qr-modal {
            margin-top: 10px;
            display: none;
        }

        .badge-activo {
            background-color: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
        }

        .badge-inactivo {
            background-color: rgb(247, 57, 57);
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
        }

        .badge-facturado {
            background-color: rgb(171, 111, 228);
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
        }

        .badge-garita-in {
            background-color: rgb(255, 193, 7);
            color: #333;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-garita-out {
            background-color: rgb(23, 162, 184);
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .turno-estado.libre {
            background-color: #28a745;
            color: white;
        }

        /* Estilos para el modal de búsqueda de manifiestos - Habilitar scroll */
        #manifiestosDialog {
            overflow: hidden !important;
        }

        #manifiestosDialog .ui-dialog-content {
            overflow-y: auto !important;
            overflow-x: auto !important;
            max-height: 80vh !important;
            padding: 10px !important;
        }

        #manifiestosDialog .condensed {
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        #manifiestosDialog form {
            overflow: visible !important;
        }

        /* Asegurar que el grid tenga scroll si es necesario */
        #manifiestosGrid {
            overflow: visible !important;
        }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo;Manifiesto Técnico</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <!-- AMBIENTE PRINCIPAL -->
            <div id="documentoSearch">
                <style>
                    #documentoSearch .control-label,
                    #documentoSearch label,
                    #documentoSearch input,
                    #documentoSearch select,
                    #documentoSearch .btn {
                        font-size: 16px !important;
                    }
                </style>
                <div class="row">
                    <form name="searchManifestoTec" id="searchManifestoTec" class="form-horizontal normal" action="javascript:loadManifiestosTecnicosHTML();">
                        <div class="col-sm-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda</legend>
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <div class="row">
                                            <!-- Contenedor Filtros -->
                                            <div class="col-xs-6 col-sm-6 col-md-5" style="padding-left: 0; padding-right: 0;">
                                                <div class="row" style="margin: 0;">
                                                    <div class="col-xs-5 col-sm-12 col-md-5" style="display: flex; align-items: center; justify-content: flex-end; padding-right: 5px; margin-top: 6px;">
                                                        <label class="control-label label-xs">Filtrar Por:</label>
                                                    </div>
                                                    <div class="col-xs-12 col-sm-12 col-md-7 radioset opt_search" style="padding-top: 5px;">
                                                        <input id="radsf1" name="op_opciones" type="radio" value="p" checked onclick="setfocus(this.form.search)" />
                                                        <label for="radsf1">Nº.Placa</label>
                                                        <input id="radsf2" name="op_opciones" type="radio" value="m" onclick="setfocus(this.form.search)" />
                                                        <label for="radsf2">N°.Manifiest</label>
                                                        <input id="radsf3" name="op_opciones" type="radio" value="q" onclick="setfocus(this.form.search)" />
                                                        <label for="radsf3">Codigo QR</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Contenedor Estado -->
                                            <div class="col-xs-6 col-sm-6 col-md-4" style="padding-left: 0; padding-right: 0;">
                                                <div class="row" style="margin: 0;">
                                                    <div class="col-xs-6 col-sm-4 col-md-4" style="display: flex; align-items: center; justify-content: flex-end; padding-right: 5px;">
                                                        <label class="control-label label-xs">Estado:</label>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-8 col-md-6">
                                                        <select name="Man_Tip" id="Man_Tip" class="form-control input-xs" style="text-align:center; height: auto;">
                                                            <!-- <option value="T">&lt;&lt; Todos &gt;&gt;</option>
                                                                <option value="P">PENDIENTE</option>
                                                                <option value="A">APROBADO</option>
                                                                <option value="F">FACTURADO</option> -->
                                                            <option value="GE" selected>GARITA IN</option>
                                                            <!-- <option value="GS">GARITA OUT</option>
                                                                <option value="R">RECHAZADO</option> -->
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-xs-12 col-sm-2 control-label">B&uacute;squeda:</label>
                                    <div class="col-xs-12 col-sm-10">
                                        <div id="divSearchNormal" class="input-group" style="width: 100%; max-width: 800px;">
                                            <input name="search" id="searchInput" onkeydown="if (event.keyCode === 13)
                                                    this.form.submit()" type="text" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" style="height: auto" />
                                            <span class="input-group-btn">
                                                <button type="button" id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" tabindex="-1">
                                                    <span class="glyphicon glyphicon-search"></span>
                                                    <span>Buscar</span>
                                                </button>
                                            </span>
                                        </div>

                                        <div id="divSearchManifiesto" style="display: none; width: 100%; max-width: 800px;">
                                            <div style="display: flex;">
                                                <div class="input-group" style="flex: 1; margin-right: 5px;">
                                                    <span class="input-group-addon" style="font-size: 16px; font-weight: bold; background-color: #d9edf7; color: #31708f;">M</span>
                                                    <input type="text" id="searchPlaCod" name="Pla_Cod" class="form-control input-xs" placeholder="Cód. Planta" style="text-align: center; font-size: 16px; height: auto">
                                                </div>
                                                <div class="input-group" style="flex: 1;">
                                                    <span class="input-group-addon" style="font-size: 16px; font-weight: bold; background-color: #d9edf7; color: #31708f;">-</span>
                                                    <input type="text" id="searchManNum" name="Man_Num" class="form-control input-xs" placeholder="Número" style="text-align: center; font-size: 16px; height: auto;">
                                                    <span class="input-group-btn">
                                                        <button type="button" id="btnSearchMan" onclick="loadManifiestosTecnicosHTML()" class="btn btn-success btn-xs">
                                                            <span class="glyphicon glyphicon-search"></span>
                                                            <span>Buscar</span>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="divSearchQR" style="display: none; width: 100%; max-width: 800px; text-align: center;">
                                            <div id="qr-reader-container-main" style="padding: 15px; margin-top: 10px;">
                                                <div id="qr-reader-main" style="width: 100%; max-width: 500px; margin: 0 auto; padding: 10px; background-color: #f9f9f9; border: 2px solid #ddd; border-radius: 5px; min-height: 250px;"></div>
                                                <button type="button" id="btnRescanQRMain" class="btn btn-info btn-xs" style="margin-top: 10px; display: none;">
                                                    <span class="glyphicon glyphicon-qrcode"></span> Escanear Nuevamente
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" id="searchQR" name="searchQR" value="">
                                    </div>
                                    <input type="text" tabindex="-1" style="display:none;" />
                                </div>
                            </fieldset>
                        </div>

                        <!-- Grid Principal de Manifiesto Técnico -->
                        <div class="col-sm-12" style="min-height: 350px; padding-bottom: 1px;">
                            <div id="man_tec_container" class="row" style="margin-top: 10px;"></div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- </div> -->

            <!-- Inicio del diálogo para buscar Manifiestos -->
            <div id="manifiestosDialog" title="B&uacute;squeda de Manifiestos">
                <form id="manifiestosForm" class="form-horizontal normal"></form>
            </div>

            <!-- dialogo de registro de manifiesto técnico -->
            <div id="manifTecDialog" style="display: none;">
                <form id="manifTecForm" class="form-horizontal normal">
                    <input name="Mat_Cod" id="Mat_Cod" type="hidden" value="" />
                    <input name="Usu_Cod" id="Usu_Cod" type="hidden" value="<?php echo $Ses_Usu_Cod; ?>" />

                    <style>
                        @media (min-width: 768px) and (max-width: 1024px) {
                            #Man_Cod {
                                font-size: 16px !important;
                            }
                        }
                    </style>
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos Generales</legend>

                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">C&oacute;digo Manifiesto:</label>
                            <div class="col-xs-3">
                                <input name="Man_Cod" id="Man_Cod" type="text" class="form-control input-xs" style="text-align: center;" readonly />
                            </div>

                            <label class="col-xs-2 col-xs-offset-1 control-label label-xs" style="text-align: right;">Fecha:</label>
                            <div class="col-xs-3">
                                <input id="Mat_Fde" name="Mat_Fde" type="text" class="form-control input-xs datepicker" style="text-align: center" readonly />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Usuario:</label>
                            <div class="col-xs-10">
                                <input name="Usu_Nom" id="Usu_Nom" type="text" class="form-control input-xs" value="<?php echo isset($_SESSION['Ses_Prs_Nom']) && isset($_SESSION['Ses_Prs_Ape']) ? $_SESSION['Ses_Prs_Nom'] . ' ' . $_SESSION['Ses_Prs_Ape'] : ''; ?>" readonly />
                            </div>
                        </div>


                        <!-- </fieldset>

                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos de la Celda de Residuos</legend> -->

                        <div class="form-group">
                            <div class="col-xs-4">
                                <label class="control-label label-xs" style="text-align: left; display: block;">No. Celda:</label>
                                <input name="Mat_Nce" id="Mat_Nce" type="text" maxlength="20" class="form-control input-xs" readonly />
                            </div>
                            <div class="col-xs-4">
                                <label class="control-label label-xs" style="text-align: left; display: block;">Código Celda:</label>
                                <input name="Mat_Cce" id="Mat_Cce" type="text" maxlength="20" class="form-control input-xs" readonly />
                            </div>
                            <div class="col-xs-4">
                                <label class="control-label label-xs" style="text-align: left; display: block;">Nombre de Plataforma:</label>
                                <input name="Mat_Dce" id="Mat_Dce" type="text" maxlength="50" class="form-control input-xs" readonly />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Estado y Acción</legend>

                        <div class="form-group">
                            <div class="col-xs-6">
                                <label class="control-label label-xs required" style="text-align: left; display: block;">Nivel de Humedad:</label>
                                <select id="Hum_Cod" name="Hum_Cod" class="form-control input-xs" required="">
                                    <option value="">-- Seleccione --</option>
                                    <option value="1">Mayor de 14 - ALTO</option>
                                    <option value="2">Menor a 14 - BAJO</option>
                                </select>
                            </div>

                            <div class="col-xs-6">
                                <label class="control-label label-xs" style="text-align: left; display: block;">Tipo de Desecho No Aprobado:</label>
                                <input name="Mat_Dna" id="Mat_Dna" type="text" maxlength="20" class="form-control input-xs" />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-xs-4">
                                <label class="control-label label-xs required" style="text-align: left; display: block;">Estado Ambiental:</label>
                                <select id="Mat_Eae" name="Mat_Eae" class="form-control input-xs" required="">
                                    <option value="">-- Seleccione --</option>
                                    <option value="A" selected="">A - Aceptado</option>
                                    <option value="R">R - Rechazado</option>
                                    <option value="AC">AC - Aceptado con Condición</option>
                                </select>
                            </div>

                            <div class="col-xs-4">
                                <label class="control-label label-xs required" style="text-align: left; display: block;">Estado de Acción:</label>
                                <select id="Mat_Ear" name="Mat_Ear" class="form-control input-xs" required="">
                                    <option value="">-- Seleccione --</option>
                                    <option value="TR">TR - Transporte</option>
                                    <option value="AT" selected="selected">AT - Almacenamiento Temporal</option>
                                    <option value="EL">EL - Eliminación</option>
                                    <option value="DF">DF - Disposición Final</option>
                                    <option value="CT">CT - Cierre Técnico</option>
                                </select>
                            </div>

                            <div class="col-xs-4">
                                <label class="control-label label-xs" style="text-align: left; display: block;">Tratamiento:</label>
                                <select id="Mat_Tra" name="Mat_Tra" class="form-control input-xs">
                                    <option value="">-- Seleccione --</option>
                                    <option value="AT">AT - Almacenamiento Temporal</option>
                                    <option value="DF" selected="selected">DF - Disposición Final</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-xs-12">
                                <label class="control-label label-xs" style="text-align: left; display: block;">Observación:</label>
                                <textarea name="Mat_Oce" id="Mat_Oce" class="form-control input-xs" maxlength="50"></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <div class="form-group center">
                        </br>
                        <a class="btn btn-sm btn-primary" onclick="GuardarManifTec()">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                        </a>
                        <a class="btn btn-sm btn-default" onclick="$('#manifTecDialog').dialog('close');">
                            <i class="glyphicon glyphicon-remove"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script src="../VALIDACIONES/man_tec_camp_1.0.js?e=3"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // Ejecutar la consulta automáticamente al cargar la página para mostrar registros de GARITA IN (GE)
            loadManifiestosTecnicosHTML();
        });
    </script>

    <?php
    // Cerrado y liberacion de las conexiones
    $obBD_con1->liberar();
    $obBD_conexion->cerrar();
    ?>
</BODY>

</HTML>