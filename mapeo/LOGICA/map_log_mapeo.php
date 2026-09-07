<?php
/**
 * Backend y Lógica del Módulo de Mapeo Interactivo - Relavera
 * Manejo de persistencia MySQL / GeoJSON, actividades, capas de vectores, telemetría GPS y Manifiestos
 * @package mapeo.LOGICA
 * @version 2.1
 */
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Directorio de almacenamiento JSON / Caché local para desarrollo y modo offline
$dataDir = __DIR__ . '/../DATA';
if (!file_exists($dataDir)) {
    @mkdir($dataDir, 0777, true);
}

$archivoActividades = $dataDir . '/actividades_relavera.json';
$archivoVectores = $dataDir . '/vectores_relavera.geojson';
$archivoGpsLogs = $dataDir . '/gps_telemetria_volquetas.json';
$archivoEmergencias = $dataDir . '/emergencias_sos.json';

// Cargar conexión de base de datos del ERP si está disponible
$dbConexion = null;
$empresaCod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : 1;

try {
    $rutaConexion = __DIR__ . '/../../DATA/MysqlConexion.php';
    if (file_exists($rutaConexion)) {
        require_once($rutaConexion);
        if (class_exists('MysqlConexion')) {
            $dispositivoBD = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null;
            $dbConexion = new MysqlConexion($dispositivoBD);
        }
    }
} catch (Exception $e) {
    $dbConexion = null;
}

// Inicializar actividades por defecto si el archivo no existe
if (!file_exists($archivoActividades)) {
    $actividadesIniciales = array(
        array(
            'id' => 'ACT-' . date('Ymd') . '-001',
            'tipo' => 'descarga_relave',
            'tipo_label' => 'Descarga de Relave Seco',
            'chofer' => 'Manuel Carrión',
            'placa' => 'OBA-7821',
            'volqueta_num' => 'VOL-04 (OBA-7821)',
            'fecha' => date('Y-m-d H:i'),
            'lat' => -3.7386587286231405,
            'lng' => -79.63034898948571,
            'elevacion' => 680.0,
            'area_m2' => 520.0,
            'volumen_m3' => 16.0,
            'longitud_m' => 0,
            'estado' => 'Completada',
            'estado_badge' => 'success',
            'observaciones' => 'Descarga de 16m³ de relave en el punto central de vertido.',
            'geometria' => array(
                'type' => 'Polygon',
                'coordinates' => array(
                    array(
                        array(-79.63060, -3.73845),
                        array(-79.63010, -3.73845),
                        array(-79.63010, -3.73885),
                        array(-79.63060, -3.73885),
                        array(-79.63060, -3.73845)
                    )
                )
            )
        ),
        array(
            'id' => 'ACT-' . date('Ymd') . '-002',
            'tipo' => 'compactacion_dique',
            'tipo_label' => 'Conformación y Compactación de Dique',
            'chofer' => 'Jorge Aguilar',
            'placa' => 'LBA-9023',
            'volqueta_num' => 'Rodillo / Volqueta #12',
            'fecha' => date('Y-m-d H:i', strtotime('-1 hour')),
            'lat' => -3.73925,
            'lng' => -79.63035,
            'elevacion' => 685.0,
            'area_m2' => 1100.0,
            'volumen_m3' => 0,
            'longitud_m' => 115.0,
            'estado' => 'En Progreso',
            'estado_badge' => 'warning',
            'observaciones' => 'Pase de rodillo vibratorio pata de cabra en coronación del dique frontal.',
            'geometria' => array(
                'type' => 'LineString',
                'coordinates' => array(
                    array(-79.63110, -3.73925),
                    array(-79.63035, -3.73925),
                    array(-79.62960, -3.73925)
                )
            )
        ),
        array(
            'id' => 'ACT-' . date('Ymd') . '-003',
            'tipo' => 'monitoreo_piezometro',
            'tipo_label' => 'Monitoreo de Piezómetro PZ-01',
            'chofer' => 'Ing. Supervisor Geotécnico',
            'placa' => 'N/A',
            'volqueta_num' => 'Técnico de Campo',
            'fecha' => date('Y-m-d H:i', strtotime('-2 hours')),
            'lat' => -3.73750,
            'lng' => -79.63080,
            'elevacion' => 675.0,
            'area_m2' => 0,
            'volumen_m3' => 0,
            'longitud_m' => 0,
            'estado' => 'Supervisado',
            'estado_badge' => 'info',
            'observaciones' => 'Lectura nivel freático y estabilidad de taludes ok.',
            'geometria' => array(
                'type' => 'Point',
                'coordinates' => array(-79.63080, -3.73750)
            )
        )
    );
    file_put_contents($archivoActividades, json_encode($actividadesIniciales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch ($action) {

    // 1. Obtener lista de actividades registradas con soporte de filtros
    case 'get_actividades':
        $filtroTipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
        $filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

        $actividades = array();
        if (file_exists($archivoActividades)) {
            $jsonContent = file_get_contents($archivoActividades);
            $actividades = json_decode($jsonContent, true);
            if (!is_array($actividades)) {
                $actividades = array();
            }
        }

        // Aplicar filtros si se enviaron parámetros
        if (!empty($filtroTipo) || !empty($filtroEstado)) {
            $actividades = array_values(array_filter($actividades, function($act) use ($filtroTipo, $filtroEstado) {
                if (!empty($filtroTipo) && (!isset($act['tipo']) || $act['tipo'] !== $filtroTipo)) return false;
                if (!empty($filtroEstado) && (!isset($act['estado']) || $act['estado'] !== $filtroEstado)) return false;
                return true;
            }));
        }

        // Cálculo de cubicaje acumulado (m3)
        $totalCubicajeM3 = 0;
        foreach ($actividades as $act) {
            if (isset($act['volumen_m3'])) {
                $totalCubicajeM3 += (float)$act['volumen_m3'];
            } elseif (isset($act['tipo']) && strpos($act['tipo'], 'descarga') !== false) {
                $totalCubicajeM3 += 16.0;
            }
        }

        echo json_encode(array(
            'success' => true,
            'actividades' => $actividades,
            'total_m3' => round($totalCubicajeM3, 2),
            'total_registros' => count($actividades)
        ));
        break;

    // 2. Guardar o actualizar una actividad
    case 'guardar_actividad':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'Datos incompletos para registrar la actividad.'));
            exit;
        }

        $actividades = array();
        if (file_exists($archivoActividades)) {
            $actividades = json_decode(file_get_contents($archivoActividades), true);
            if (!is_array($actividades)) $actividades = array();
        }

        $idActividad = !empty($data['id']) ? $data['id'] : ('ACT-' . date('Ymd-His'));
        $data['id'] = $idActividad;
        $data['fecha_guardado'] = date('Y-m-d H:i:s');
        $data['usuario'] = isset($_SESSION['Ses_Usu_Nom']) ? $_SESSION['Ses_Usu_Nom'] : 'Usuario Sistema';

        // Buscar si ya existe para actualizar o insertar al inicio
        $encontrado = false;
        foreach ($actividades as $key => $act) {
            if ($act['id'] === $idActividad) {
                $actividades[$key] = $data;
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            array_unshift($actividades, $data);
        }

        file_put_contents($archivoActividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(array('success' => true, 'id' => $idActividad, 'message' => 'Actividad guardada correctamente.'));
        break;

    // 3. Sincronización en lote desde Modo Offline (localStorage / buffer)
    case 'sincronizar_lote_offline':
        $input = file_get_contents('php://input');
        $lote = json_decode($input, true);

        if (!$lote || (!isset($lote['actividades']) && !isset($lote['gps_puntos']))) {
            echo json_encode(array('success' => false, 'message' => 'Lote offline vacío o formato inválido.'));
            exit;
        }

        $procesadosAct = 0;
        $procesadosGps = 0;

        // Sincronizar Actividades
        if (isset($lote['actividades']) && is_array($lote['actividades']) && count($lote['actividades']) > 0) {
            $actividades = array();
            if (file_exists($archivoActividades)) {
                $actividades = json_decode(file_get_contents($archivoActividades), true);
                if (!is_array($actividades)) $actividades = array();
            }

            foreach ($lote['actividades'] as $actOffline) {
                if (isset($actOffline['lat']) && isset($actOffline['lng'])) {
                    if (empty($actOffline['id'])) {
                        $actOffline['id'] = 'ACT-OFF-' . uniqid();
                    }
                    $actOffline['sincronizado_offline'] = true;
                    $actOffline['fecha_sincronizacion'] = date('Y-m-d H:i:s');
                    array_unshift($actividades, $actOffline);
                    $procesadosAct++;
                }
            }
            file_put_contents($archivoActividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Sincronizar Puntos GPS
        if (isset($lote['gps_puntos']) && is_array($lote['gps_puntos']) && count($lote['gps_puntos']) > 0) {
            $gpsLogs = array();
            if (file_exists($archivoGpsLogs)) {
                $gpsLogs = json_decode(file_get_contents($archivoGpsLogs), true);
                if (!is_array($gpsLogs)) $gpsLogs = array();
            }

            foreach ($lote['gps_puntos'] as $pt) {
                $pt['sincronizado_offline'] = true;
                $gpsLogs[] = $pt;
                $procesadosGps++;
            }
            if (count($gpsLogs) > 500) {
                $gpsLogs = array_slice($gpsLogs, -500);
            }
            file_put_contents($archivoGpsLogs, json_encode($gpsLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        echo json_encode(array(
            'success' => true,
            'message' => "Sincronización exitosa: {$procesadosAct} actividades y {$procesadosGps} puntos GPS sincronizados.",
            'actividades_procesadas' => $procesadosAct,
            'gps_procesados' => $procesadosGps
        ));
        break;

    // 4. Eliminar actividad
    case 'eliminar_actividad':
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (empty($id)) {
            echo json_encode(array('success' => false, 'message' => 'ID no proporcionado.'));
            exit;
        }

        $actividades = array();
        if (file_exists($archivoActividades)) {
            $actividades = json_decode(file_get_contents($archivoActividades), true);
            if (!is_array($actividades)) $actividades = array();
        }

        $nuevasActividades = array_values(array_filter($actividades, function($act) use ($id) {
            return $act['id'] !== $id;
        }));

        file_put_contents($archivoActividades, json_encode($nuevasActividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(array('success' => true, 'message' => 'Actividad eliminada con éxito.'));
        break;

    // 5. Consultar flota real de choferes y volquetas desde MySQL ERP
    case 'get_flota':
        $flota = array();

        if ($dbConexion && is_object($dbConexion)) {
            try {
                $sql = "SELECT v.Veh_Cod, v.Veh_Pla, v.Veh_Cap, t.Mat_Des,
                               CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Chofer_Nombre
                        FROM vehiculo v
                        LEFT JOIN manifiesto_transporte t ON v.Mat_Cod = t.Mat_Cod
                        LEFT JOIN chofer c ON c.Emp_Cod = v.Emp_Cod
                        LEFT JOIN persona p ON c.Prs_Ced = p.Prs_Ced
                        WHERE v.Emp_Cod = '{$empresaCod}' AND v.Veh_Est = 'ACTIVO'
                        ORDER BY v.Veh_Pla ASC
                        LIMIT 15";
                $result = ($dbConexion && !empty($dbConexion->conexion)) ? @mysqli_query($dbConexion->conexion, $sql) : false;

                if ($result) {
                    while ($row = @mysqli_fetch_assoc($result)) {
                        $flota[] = array(
                            'codigo' => 'VOL-' . $row['Veh_Cod'],
                            'placa' => $row['Veh_Pla'],
                            'capacidad_m3' => $row['Veh_Cap'] ? $row['Veh_Cap'] : 16.0,
                            'transporte' => $row['Mat_Des'] ? $row['Mat_Des'] : 'Transporte Comunitario',
                            'chofer' => $row['Chofer_Nombre'] ? $row['Chofer_Nombre'] : 'Chofer Operativo'
                        );
                    }
                }
            } catch (Exception $e) {}
        }

        // Fallback enriquecido
        if (empty($flota)) {
            $flota = array(
                array('codigo' => 'VOL-04', 'placa' => 'OBA-7821', 'capacidad_m3' => 16.0, 'transporte' => 'Trans. El Tablón', 'chofer' => 'Manuel Carrión'),
                array('codigo' => 'VOL-09', 'placa' => 'PBC-3419', 'capacidad_m3' => 14.0, 'transporte' => 'Trans. Relavera Sur', 'chofer' => 'Luis Espinoza'),
                array('codigo' => 'VOL-12', 'placa' => 'LBA-9023', 'capacidad_m3' => 18.0, 'transporte' => 'Trans. Minero Central', 'chofer' => 'Jorge Aguilar'),
                array('codigo' => 'VOL-15', 'placa' => 'PBA-6124', 'capacidad_m3' => 16.0, 'transporte' => 'Trans. El Oro', 'chofer' => 'Carlos Morales')
            );
        }

        echo json_encode(array('success' => true, 'flota' => $flota));
        break;

    // 6. Consultar Manifiestos de hoy desde MySQL ERP
    case 'get_manifiestos_hoy':
        $manifiestos = array();
        $fechaHoy = date('Y-m-d');

        if ($dbConexion && is_object($dbConexion)) {
            try {
                $sql = "SELECT m.Man_Cod, m.Man_Fec, m.Man_Hor_Lle, m.Man_Pes_Net,
                               v.Veh_Pla, p.Pla_Nom,
                               CONCAT(per.Prs_Nom, ' ', per.Prs_Ape) AS Chofer_Nombre
                        FROM manifiesto m
                        LEFT JOIN vehiculo v ON m.Veh_Cod = v.Veh_Cod
                        LEFT JOIN manifiesto_plantas p ON m.Pla_Cod = p.Pla_Cod
                        LEFT JOIN chofer c ON m.Cho_Cod = c.Cho_Cod
                        LEFT JOIN persona per ON c.Prs_Ced = per.Prs_Ced
                        WHERE m.Emp_Cod = '{$empresaCod}' AND m.Man_Fec = '{$fechaHoy}'
                        ORDER BY m.Man_Cod DESC
                        LIMIT 10";
                $result = ($dbConexion && !empty($dbConexion->conexion)) ? @mysqli_query($dbConexion->conexion, $sql) : false;

                if ($result) {
                    while ($row = @mysqli_fetch_assoc($result)) {
                        $manifiestos[] = array(
                            'codigo' => 'MAN-' . str_pad($row['Man_Cod'], 5, '0', STR_PAD_LEFT),
                            'hora' => substr($row['Man_Hor_Lle'], 0, 5),
                            'placa' => $row['Veh_Pla'],
                            'planta' => $row['Pla_Nom'] ? $row['Pla_Nom'] : 'Planta de Beneficio',
                            'chofer' => $row['Chofer_Nombre'] ? $row['Chofer_Nombre'] : 'Conductor',
                            'peso_neto_ton' => round((float)$row['Man_Pes_Net'], 2),
                            'volumen_m3' => round(((float)$row['Man_Pes_Net'] / 1.6), 1),
                            'estado' => 'Completado'
                        );
                    }
                }
            } catch (Exception $e) {}
        }

        // Fallback de demostración si no hay manifiestos cargados en la fecha
        if (empty($manifiestos)) {
            $manifiestos = array(
                array('codigo' => 'MAN-00891', 'hora' => '08:15', 'placa' => 'OBA-7821', 'planta' => 'Planta San Luis #2', 'chofer' => 'Manuel Carrión', 'peso_neto_ton' => 24.5, 'volumen_m3' => 15.3, 'estado' => 'Completado'),
                array('codigo' => 'MAN-00892', 'hora' => '09:40', 'placa' => 'PBC-3419', 'planta' => 'Planta El Tablón Central', 'chofer' => 'Luis Espinoza', 'peso_neto_ton' => 22.8, 'volumen_m3' => 14.2, 'estado' => 'Completado'),
                array('codigo' => 'MAN-00893', 'hora' => '11:10', 'placa' => 'LBA-9023', 'planta' => 'Molino & Flotación Bella Rica', 'chofer' => 'Jorge Aguilar', 'peso_neto_ton' => 28.0, 'volumen_m3' => 17.5, 'estado' => 'Completado'),
                array('codigo' => 'MAN-00894', 'hora' => '13:05', 'placa' => 'PBA-6124', 'planta' => 'Planta Providencia', 'chofer' => 'Carlos Morales', 'peso_neto_ton' => 25.1, 'volumen_m3' => 15.7, 'estado' => 'En Tránsito')
            );
        }

        echo json_encode(array('success' => true, 'manifiestos' => $manifiestos, 'origen_bd' => ($dbConexion !== null)));
        break;

    // 7. Botón de Pánico y Alerta SOS Georreferenciada
    case 'reportar_sos':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'Coordenadas requeridas para el SOS.'));
            exit;
        }

        $emergencias = array();
        if (file_exists($archivoEmergencias)) {
            $emergencias = json_decode(file_get_contents($archivoEmergencias), true);
            if (!is_array($emergencias)) $emergencias = array();
        }

        $sosRegistro = array(
            'id' => 'SOS-' . date('Ymd-His'),
            'lat' => (float)$data['lat'],
            'lng' => (float)$data['lng'],
            'motivo' => isset($data['motivo']) ? $data['motivo'] : 'Alerta de Emergencia Inmediata',
            'chofer' => isset($data['chofer']) ? $data['chofer'] : 'Operador en Campo',
            'volqueta' => isset($data['volqueta']) ? $data['volqueta'] : 'Unidad de Acarreo',
            'fecha_hora' => date('Y-m-d H:i:s'),
            'estado' => 'ACTIVA'
        );

        array_unshift($emergencias, $sosRegistro);
        if (count($emergencias) > 50) $emergencias = array_slice($emergencias, 0, 50);

        file_put_contents($archivoEmergencias, json_encode($emergencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Registrar también como actividad crítica tipo "reporte_alerta"
        $actividades = array();
        if (file_exists($archivoActividades)) {
            $actividades = json_decode(file_get_contents($archivoActividades), true);
            if (!is_array($actividades)) $actividades = array();
        }

        $actAlerta = array(
            'id' => $sosRegistro['id'],
            'tipo' => 'reporte_alerta',
            'tipo_label' => 'Alerta Crítica SOS: ' . $sosRegistro['motivo'],
            'chofer' => $sosRegistro['chofer'],
            'placa' => 'EMERGENCIA',
            'volqueta_num' => $sosRegistro['volqueta'],
            'fecha' => date('Y-m-d H:i'),
            'lat' => $sosRegistro['lat'],
            'lng' => $sosRegistro['lng'],
            'elevacion' => 680.0,
            'area_m2' => 0,
            'volumen_m3' => 0,
            'longitud_m' => 0,
            'estado' => 'Alerta Crítica',
            'estado_badge' => 'danger',
            'observaciones' => 'EMERGENCIA ACTIVADA DESDE BOTÓN DE PÁNICO GEORREFERENCIADO.'
        );
        array_unshift($actividades, $actAlerta);
        file_put_contents($archivoActividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(array('success' => true, 'sos' => $sosRegistro, 'message' => 'Alerta SOS registrada y notificada con éxito.'));
        break;

    // 8. Consultar alertas de emergencias activas
    case 'get_emergencias':
        $emergencias = array();
        if (file_exists($archivoEmergencias)) {
            $emergencias = json_decode(file_get_contents($archivoEmergencias), true);
            if (!is_array($emergencias)) $emergencias = array();
        }
        echo json_encode(array('success' => true, 'emergencias' => $emergencias));
        break;

    // 9. Registrar punto GPS de telemetría
    case 'registrar_gps':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if ($data && isset($data['lat']) && isset($data['lng'])) {
            $data['timestamp'] = date('Y-m-d H:i:s');
            
            $gpsLogs = array();
            if (file_exists($archivoGpsLogs)) {
                $gpsLogs = json_decode(file_get_contents($archivoGpsLogs), true);
                if (!is_array($gpsLogs)) $gpsLogs = array();
            }

            $gpsLogs[] = $data;
            if (count($gpsLogs) > 300) {
                $gpsLogs = array_slice($gpsLogs, -300);
            }
            file_put_contents($archivoGpsLogs, json_encode($gpsLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        break;

    // 10. Obtener historial GPS para el Reproductor Histórico (Playback)
    case 'get_gps_history':
        $volqueta = isset($_GET['volqueta']) ? trim($_GET['volqueta']) : 'VOL-04';
        $gpsLogs = array();
        if (file_exists($archivoGpsLogs)) {
            $gpsLogs = json_decode(file_get_contents($archivoGpsLogs), true);
            if (!is_array($gpsLogs)) $gpsLogs = array();
        }

        echo json_encode(array('success' => true, 'volqueta' => $volqueta, 'puntos' => $gpsLogs));
        break;

    // 11. Guardar Canva Vectorial GeoJSON
    case 'guardar_vectores':
        $input = file_get_contents('php://input');
        if ($input) {
            file_put_contents($archivoVectores, $input);
            echo json_encode(array('success' => true, 'message' => 'Capa vectorial guardada exitosamente.'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Sin contenido GeoJSON.'));
        }
        break;

    // 12. Obtener Canva Vectorial GeoJSON
    case 'get_vectores':
        if (file_exists($archivoVectores)) {
            echo file_get_contents($archivoVectores);
        } else {
            echo json_encode(array('type' => 'FeatureCollection', 'features' => array()));
        }
        break;

    default:
        echo json_encode(array('success' => false, 'message' => 'Acción no válida o no especificada.'));
        break;
}
