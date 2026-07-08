<?php

class TareaClass {
    protected $conexion = null;
    protected $datos = null;

    function __construct($conexion, $datos) {
        $this->conexion = $conexion;
        $this->datos = $datos;
    }

    public function obtener($body) {
        $consulta = $this->datos->getArrayConsulta(9, array('Emp_Cod' => $body['Emp_Cod']), $this->conexion);
        $response = array('status' => true, 'data' => is_array($consulta) ? $consulta : array());
        if ($this->datos->Error != 0) {
            $response['status'] = false;
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function obtenerTodas($body) {
        $consulta = $this->datos->getArrayConsulta(37, array('Emp_Cod' => $body['Emp_Cod']), $this->conexion);
        $response = array('status' => true, 'data' => is_array($consulta) ? $consulta : array());
        if ($this->datos->Error != 0) {
            $response['status'] = false;
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function crear($body) {
        $titulo = isset($body['Tar_Titulo']) ? trim($body['Tar_Titulo']) : '';
        $desc = isset($body['Tar_Descripcion']) ? trim($body['Tar_Descripcion']) : '';
        $prioridad = isset($body['Tar_Prioridad']) && in_array($body['Tar_Prioridad'], array('Alta', 'Media', 'Baja')) ? $body['Tar_Prioridad'] : 'Media';
        $fecIni = isset($body['Tar_Fecha_Inicio']) ? trim($body['Tar_Fecha_Inicio']) : '';
        $fecFin = isset($body['Tar_Fecha_Fin']) ? trim($body['Tar_Fecha_Fin']) : '';
        $estado = isset($body['Tar_Estado']) && trim($body['Tar_Estado']) !== '' ? trim($body['Tar_Estado']) : 'Pendiente';
        $usuCreador = isset($body['Usu_Creador']) ? intval($body['Usu_Creador']) : 0;
        $empCod = isset($body['Emp_Cod']) ? intval($body['Emp_Cod']) : 0;

        $par = array(
            'Tar_Titulo_safe' => $titulo,
            'Tar_Descripcion_safe' => $desc,
            'Tar_Prioridad' => $prioridad,
            'Tar_Fecha_Inicio' => $fecIni,
            'Tar_Fecha_Fin' => $fecFin,
            'Tar_Estado' => $estado,
            'Usu_Creador' => $usuCreador,
            'Emp_Cod' => $empCod
        );
        $this->datos->operacionobBD(1, $par, $this->conexion);
        $response = array('success' => ($this->datos->Error == 0));
        if ($this->datos->Error == 0) {
            $response['Tar_Cod'] = $this->datos->insercionid($this->conexion->conexion);
        } else {
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function modificar($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $titulo = isset($body['Tar_Titulo']) ? trim($body['Tar_Titulo']) : '';
        $desc = isset($body['Tar_Descripcion']) ? trim($body['Tar_Descripcion']) : '';
        $prioridad = isset($body['Tar_Prioridad']) && in_array($body['Tar_Prioridad'], array('Alta', 'Media', 'Baja')) ? $body['Tar_Prioridad'] : 'Media';
        $fecIni = isset($body['Tar_Fecha_Inicio']) ? trim($body['Tar_Fecha_Inicio']) : '';
        $fecFin = isset($body['Tar_Fecha_Fin']) ? trim($body['Tar_Fecha_Fin']) : '';
        $estado = isset($body['Tar_Estado']) && trim($body['Tar_Estado']) !== '' ? trim($body['Tar_Estado']) : 'Pendiente';

        $par = array(
            'Tar_Cod' => $tarCod,
            'Tar_Titulo_safe' => $titulo,
            'Tar_Descripcion_safe' => $desc,
            'Tar_Prioridad' => $prioridad,
            'Tar_Fecha_Inicio' => $fecIni,
            'Tar_Fecha_Fin' => $fecFin,
            'Tar_Estado' => $estado
        );
        $this->datos->operacionobBD(39, $par, $this->conexion);
        $response = array('success' => ($this->datos->Error == 0));
        if ($this->datos->Error != 0) {
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function eliminar($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $this->datos->operacionobBD(36, array('Tar_Cod' => $tarCod), $this->conexion);
        $response = array('success' => ($this->datos->Error == 0));
        if ($this->datos->Error != 0) {
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function obtenerPorId($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $row = $this->datos->getRowConsulta(38, array('Tar_Cod' => $tarCod), $this->conexion);
        $response = array('status' => true, 'row' => is_array($row) ? $row : null);
        $this->datos->echoJson($response);
    }

    public function obtenerEmpleados($body) {
        $consulta = $this->datos->getArrayConsulta(10, array('Emp_Cod' => $body['Emp_Cod']), $this->conexion);
        $response = array('status' => true, 'data' => is_array($consulta) ? $consulta : array());
        if ($this->datos->Error != 0) {
            $response['status'] = false;
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function asignar($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $perCod = isset($body['Per_Cod']) ? intval($body['Per_Cod']) : 0;

        $existe = $this->datos->getRowConsulta(12, array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod), $this->conexion);
        if (!empty($existe)) {
            $response = array('success' => false, 'message' => 'Esta tarea ya está asignada a este empleado.');
            $this->datos->echoJson($response);
            return;
        }
        $par = array('Tar_Cod' => $tarCod, 'Per_Cod' => $perCod, 'Tas_Fecha_Asignacion' => date('Y-m-d H:i:s'));
        $this->datos->operacionobBD(2, $par, $this->conexion);
        if ($this->datos->Error == 0) {
            $this->datos->operacionobBD(25, array('Tar_Cod' => $tarCod), $this->conexion);
        }
        $response = array('success' => ($this->datos->Error == 0));
        if ($this->datos->Error != 0) {
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function eliminarAsignacion($body) {
        $tasCod = isset($body['Tas_Cod']) ? intval($body['Tas_Cod']) : 0;
        $this->datos->operacionobBD(18, array('Tas_Cod' => $tasCod), $this->conexion);
        $response = array('success' => ($this->datos->Error == 0));
        if ($this->datos->Error != 0) {
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function listarAsignaciones($body) {
        $empCod = isset($body['Emp_Cod']) ? intval($body['Emp_Cod']) : 0;
        $perCod = isset($body['Per_Cod']) ? intval($body['Per_Cod']) : 0;
        $par = array('Emp_Cod' => $empCod);
        if ($perCod > 0) $par['Per_Cod'] = $perCod;
        $consulta = $this->datos->getArrayConsulta(11, $par, $this->conexion);
        if (!is_array($consulta)) {
            $consulta = $this->datos->getArrayConsulta(30, $par, $this->conexion);
        }
        if (!is_array($consulta)) $consulta = array();

        $arrAv = $this->datos->getArrayConsulta(15, array('Emp_Cod' => $empCod), $this->conexion);
        $avances = array();
        if (is_array($arrAv)) {
            foreach ($arrAv as $r) {
                $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
                if ($tc > 0 && !isset($avances[$tc])) $avances[$tc] = $r;
            }
        }
        foreach ($consulta as &$r) {
            $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
            $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
        }
        unset($r);
        $response = array('status' => true, 'data' => $consulta);
        $this->datos->echoJson($response);
    }

    public function guardarAvance($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $desc = isset($body['Ava_Descripcion']) ? trim($body['Ava_Descripcion']) : '';
        $porc = isset($body['Ava_Porcentaje']) ? min(100, max(0, intval($body['Ava_Porcentaje']))) : 0;
        $usuCod = isset($body['Usu_Cod']) ? intval($body['Usu_Cod']) : 0;
        $fecCulminacion = isset($body['Tar_Fecha_Culminacion']) ? trim($body['Tar_Fecha_Culminacion']) : '';

        if ($tarCod <= 0) {
            $response = array('success' => false, 'message' => 'Debe indicar la tarea.');
            $this->datos->echoJson($response);
            return;
        }
        $fec = date('Y-m-d H:i:s');
        $existe = $this->datos->getRowConsulta(13, array('Tar_Cod' => $tarCod), $this->conexion);
        if (!empty($existe) && isset($existe['Ava_Cod'])) {
            $par = array('Ava_Cod' => $existe['Ava_Cod'], 'Ava_Porcentaje' => $porc, 'Ava_Descripcion' => $desc, 'Ava_Fecha' => $fec);
            $this->datos->operacionobBD(14, $par, $this->conexion);
        } else {
            $par = array('Tar_Cod' => $tarCod, 'Usu_Cod' => $usuCod, 'Ava_Descripcion' => $desc, 'Ava_Porcentaje' => $porc, 'Ava_Fecha' => $fec);
            $this->datos->operacionobBD(3, $par, $this->conexion);
        }
        if ($this->datos->Error == 0) {
            if ($porc == 100) {
                $this->datos->operacionobBD(22, array('Tar_Cod' => $tarCod, 'Tar_Fecha_Culminacion' => $fecCulminacion), $this->conexion);
                if ($this->datos->Error != 0) {
                    $this->datos->Error = 0;
                    $this->datos->operacionobBD(28, array('Tar_Cod' => $tarCod), $this->conexion);
                }
            } else {
                $this->datos->operacionobBD(34, array('Tar_Cod' => $tarCod), $this->conexion);
                if ($this->datos->Error != 0) {
                    $this->datos->Error = 0;
                    $this->datos->operacionobBD(35, array('Tar_Cod' => $tarCod), $this->conexion);
                }
                if ($porc > 0 && $porc < 100) {
                    $this->datos->operacionobBD(26, array('Tar_Cod' => $tarCod), $this->conexion);
                }
            }
        }
        $response = array('success' => ($this->datos->Error == 0));
        if ($this->datos->Error != 0) {
            $response['message'] = $this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function obtenerAvances($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $consulta = array();
        if ($tarCod > 0) {
            $consulta = $this->datos->getArrayConsulta(6, array('Tar_Cod' => $tarCod), $this->conexion);
        }
        $response = array('status' => true, 'data' => is_array($consulta) ? $consulta : array());
        $this->datos->echoJson($response);
    }

    public function obtenerMiAvance($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $row = $tarCod > 0 ? $this->datos->getRowConsulta(13, array('Tar_Cod' => $tarCod), $this->conexion) : null;
        $response = array('status' => true, 'row' => is_array($row) ? $row : null);
        $this->datos->echoJson($response);
    }

    public function indicadores($body) {
        $fecIni = isset($body['Fecha_Ini']) ? trim($body['Fecha_Ini']) : '';
        $fecFin = isset($body['Fecha_Fin']) ? trim($body['Fecha_Fin']) : '';
        $par = array('Emp_Cod' => $body['Emp_Cod']);
        if ($fecIni !== '' || $fecFin !== '') {
            $par['Fecha_Ini'] = $fecIni;
            $par['Fecha_Fin'] = $fecFin;
            $row = $this->datos->getRowConsulta(33, $par, $this->conexion);
        } else {
            $row = $this->datos->getRowConsulta(8, $par, $this->conexion);
        }
        $total = isset($row['Total_Tareas']) ? intval($row['Total_Tareas']) : 0;
        $completadas = isset($row['Completadas']) ? intval($row['Completadas']) : 0;
        $atrasadas = isset($row['Atrasadas']) ? intval($row['Atrasadas']) : 0;
        $pctCompletadas = $total > 0 ? round(100 * $completadas / $total, 1) : 0;
        $pctAtrasadas = $total > 0 ? round(100 * $atrasadas / $total, 1) : 0;
        $rendimiento = $total > 0 ? round(100 * $completadas / $total, 1) : 0;
        $response = array(
            'status' => true,
            'Total_Tareas' => $total,
            'Completadas' => $completadas,
            'Atrasadas' => $atrasadas,
            'Pct_Completadas' => $pctCompletadas,
            'Pct_Atrasadas' => $pctAtrasadas,
            'Rendimiento_Promedio' => $rendimiento
        );
        $this->datos->echoJson($response);
    }

    public function tareasAtencion($body) {
        $consulta = $this->datos->getArrayConsulta(32, array('Emp_Cod' => $body['Emp_Cod']), $this->conexion);
        if (!is_array($consulta)) $consulta = array();
        $arrAv = $this->datos->getArrayConsulta(15, array('Emp_Cod' => $body['Emp_Cod']), $this->conexion);
        $avances = array();
        if (is_array($arrAv)) {
            foreach ($arrAv as $r) {
                $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
                if ($tc > 0 && !isset($avances[$tc])) $avances[$tc] = $r;
            }
        }
        foreach ($consulta as &$r) {
            $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
            $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
        }
        unset($r);
        $response = array('status' => true, 'data' => $consulta);
        $this->datos->echoJson($response);
    }

    public function metricasRendimiento($body) {
        $empCod = isset($body['Emp_Cod']) ? intval($body['Emp_Cod']) : 0;
        $consulta = $this->datos->getRowConsulta(7, array('Emp_Cod' => $empCod), $this->conexion);
        $response = array('status' => true, 'data' => is_array($consulta) ? $consulta : array());
        $this->datos->echoJson($response);
    }

    public function misTareas($body) {
        $empCod = isset($body['Emp_Cod']) ? intval($body['Emp_Cod']) : 0;
        $usuCod = isset($body['Usu_Cod']) ? intval($body['Usu_Cod']) : 0;
        $rowPer = $this->datos->getRowConsulta(16, array('Usu_Cod' => $usuCod, 'Emp_Cod' => $empCod), $this->conexion);
        $perCod = isset($rowPer['Per_Cod']) ? intval($rowPer['Per_Cod']) : 0;
        $response = array('status' => true, 'data' => array(), 'sin_vinculo' => false);
        if ($perCod <= 0) {
            $response['sin_vinculo'] = true;
            $this->datos->echoJson($response);
            return;
        }
        $asig = $this->datos->getArrayConsulta(17, array('Per_Cod' => $perCod, 'Emp_Cod' => $empCod), $this->conexion);
        if (!is_array($asig)) $asig = array();
        $arrAv = $this->datos->getArrayConsulta(15, array('Emp_Cod' => $empCod), $this->conexion);
        $avances = array();
        if (is_array($arrAv)) {
            foreach ($arrAv as $r) {
                $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
                if ($tc > 0 && !isset($avances[$tc])) $avances[$tc] = $r;
            }
        }
        foreach ($asig as &$r) {
            $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
            $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
            $r['Ava_Cod'] = isset($avances[$tc]['Ava_Cod']) ? $avances[$tc]['Ava_Cod'] : null;
        }
        unset($r);
        $response['data'] = $asig;
        $this->datos->echoJson($response);
    }

    public function listarAdjuntos($body) {
        $tarCod = isset($body['Tar_Cod']) ? intval($body['Tar_Cod']) : 0;
        $consulta = array();
        if ($tarCod > 0) {
            try {
                $consulta = $this->datos->getArrayConsulta(43, array('Tar_Cod' => $tarCod), $this->conexion);
            } catch (Throwable $e) {
                $consulta = array();
            }
        }
        if (!is_array($consulta)) $consulta = array();
        $response = array('status' => true, 'data' => $consulta);
        $this->datos->echoJson($response);
    }

    public function reporteTareas($body) {
        $empCod = isset($body['Emp_Cod']) ? intval($body['Emp_Cod']) : 0;
        $perCod = isset($body['Per_Cod']) ? intval($body['Per_Cod']) : 0;
        $fecIni = isset($body['Fecha_Ini']) ? trim($body['Fecha_Ini']) : '';
        $fecFin = isset($body['Fecha_Fin']) ? trim($body['Fecha_Fin']) : '';

        $par = array('Emp_Cod' => $empCod);
        if ($perCod > 0) $par['Per_Cod'] = $perCod;
        if ($fecIni !== '') $par['Fecha_Ini'] = $fecIni;
        if ($fecFin !== '') $par['Fecha_Fin'] = $fecFin;

        $consulta = $this->datos->getArrayConsulta(11, $par, $this->conexion);
        if (!is_array($consulta)) {
            $consulta = $this->datos->getArrayConsulta(30, $par, $this->conexion);
        }
        if (!is_array($consulta)) $consulta = array();

        if ($fecIni !== '' || $fecFin !== '') {
            $consulta = array_values(array_filter($consulta, function ($r) use ($fecIni, $fecFin) {
                $fec = isset($r['Tar_Fecha_Inicio']) ? trim($r['Tar_Fecha_Inicio']) : '';
                if ($fec === '' || $fec === '0000-00-00') return false;
                if ($fecIni !== '' && $fec < $fecIni) return false;
                if ($fecFin !== '' && $fec > $fecFin) return false;
                return true;
            }));
        }

        $arrAv = $this->datos->getArrayConsulta(15, array('Emp_Cod' => $empCod), $this->conexion);
        $avances = array();
        if (is_array($arrAv)) {
            foreach ($arrAv as $r) {
                $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
                if ($tc > 0 && !isset($avances[$tc])) $avances[$tc] = $r;
            }
        }
        foreach ($consulta as &$r) {
            $tc = isset($r['Tar_Cod']) ? intval($r['Tar_Cod']) : 0;
            $r['Ava_Porcentaje'] = isset($avances[$tc]['Ava_Porcentaje']) ? $avances[$tc]['Ava_Porcentaje'] : null;
        }
        unset($r);
        $response = array('status' => true, 'data' => $consulta);
        $this->datos->echoJson($response);
    }
}
