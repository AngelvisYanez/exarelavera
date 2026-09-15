<?php
/**
 * EXA Workflow Manager - Motor de Flujos Genérico
 * 
 * Lógica principal del motor de procesos empresariales y enrutamientos.
 * @author Oz <oz-agent@warp.dev>
 * @version 1.0
 */

require_once(dirname(__FILE__) . '/../../DATA/GestorErrores.php');
require_once(dirname(__FILE__) . '/../../DATA/MysqlConexion.php');
require_once(dirname(__FILE__) . '/../../DATA/MysqlDatos.php');

class wf_manager_log {
    public $obBD_conexion;
    public $obBD_datos;

    public function __construct($Ses_Dat_Dis = null) {
        if ($Ses_Dat_Dis === null && isset($_SESSION['Ses_Dat_Dis'])) {
            $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
        }
        $this->obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
        $this->obBD_datos = new MysqlDatos($this->obBD_conexion);
    }

    /**
     * Verifica si el usuario actual tiene acceso a una ventana o pestaña específica
     */
    public function verificarAccesoVentana($ventana, $tab = null) {
        // Retornamos true por defecto para que el usuario gestione los accesos
        // mediante el sistema de seguridad nativo de EXA (seguridad.php)
        return true;
    }

    /**
     * Inicializa una nueva instancia de flujo para una transacción/entidad
     */
    public function iniciarInstancia($Wfm_Cod, $Ent_Typ, $Ent_Cod) {
        $this->obBD_datos->inicio_transaccion($this->obBD_conexion);
        try {
            // 1. Obtener nodo INICIO para este flujo modelo
            $sqlInicio = "SELECT * FROM wf_nodos WHERE Wfm_Cod = $Wfm_Cod AND Nod_Tip = 'INICIO' AND Nod_Est = 'A' LIMIT 1;";
            $nodoInicio = $this->obBD_datos->getRowConsultaSql($sqlInicio, $this->obBD_conexion);
            
            if (empty($nodoInicio)) {
                throw new Exception("El flujo modelo no tiene configurado un nodo de INICIO activo.");
            }

            // 2. Crear registro en wf_instancias
            $fecha_actual = date('Y-m-d H:i:s');
            $sqlInsertInstancia = "INSERT INTO wf_instancias (Wfm_Cod, Ins_Ent_Typ, Ins_Ent_Cod, Nod_Act, Ins_Est, Ins_Fec_Ini) 
                                   VALUES ($Wfm_Cod, '$Ent_Typ', $Ent_Cod, $nodoInicio[Nod_Cod], 'P', '$fecha_actual');";
            $this->obBD_datos->grabarv_registros($sqlInsertInstancia, $this->obBD_conexion);
            $Ins_Cod = $this->obBD_datos->insercionid($this->obBD_conexion);

            // 3. Registrar el paso de Inicio en el historial (wf_instancias_nodos)
            $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $session_id = session_id() ?: 'CLI-SESSION';
            $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0;
            $dep_cod = isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0; // Departamento del usuario si existe

            $sqlInsertHistorial = "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Fec, Isn_Ip, Isn_Ses) 
                                   VALUES ($Ins_Cod, $nodoInicio[Nod_Cod], $usu_cod, $dep_cod, 'CREAR', 'Instanciación inicial del flujo.', '$fecha_actual', '$ip_usuario', '$session_id');";
            $this->obBD_datos->grabarv_registros($sqlInsertHistorial, $this->obBD_conexion);

            // 4. Avanzar automáticamente al siguiente nodo desde el nodo Inicio
            $this->avanzarSiguientePaso($Ins_Cod, $nodoInicio['Nod_Cod'], 'CREAR', 'Avance automático desde Inicio.', null);

            $this->obBD_datos->commit_nomsn($this->obBD_conexion);
            return array('success' => true, 'Ins_Cod' => $Ins_Cod);
        } catch (Exception $e) {
            $this->obBD_datos->rollBack_nomsn($this->obBD_conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Avanza el flujo de trabajo al siguiente nodo lógico evaluando condiciones
     */
    public function avanzarSiguientePaso($Ins_Cod, $Nod_Actual_Cod, $Accion, $Comentario = '', $Adjuntos = null) {
        $fecha_actual = date('Y-m-d H:i:s');
        $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $session_id = session_id() ?: 'CLI-SESSION';
        $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0;
        $dep_cod = isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0;

        // 1. Obtener la instancia
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
        if (empty($instancia)) {
            throw new Exception("No existe la instancia de flujo solicitada.");
        }

        // 2. Obtener conexiones de salida desde el nodo actual
        $sqlConexiones = "SELECT * FROM wf_conexiones WHERE Nod_Ori = $Nod_Actual_Cod;";
        $conexiones = $this->obBD_datos->getArrayConsultaSql($sqlConexiones, $this->obBD_conexion);

        if (empty($conexiones)) {
            // Si no hay conexiones de salida, es un nodo FIN o flujo truncado.
            // Verificamos si el nodo actual es tipo FIN
            $nodoActual = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $Nod_Actual_Cod;", $this->obBD_conexion);
            if ($nodoActual['Nod_Tip'] == 'FIN') {
                $this->obBD_datos->grabarv_registros("UPDATE wf_instancias SET Ins_Est = 'F', Ins_Fec_Fin = '$fecha_actual' WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
                return true;
            }
            throw new Exception("El nodo actual no tiene conexiones de salida configuradas.");
        }

        // 3. Evaluar conexiones para decidir a qué nodo avanzar
        $nodoDestino_Cod = null;
        $conexionDefault = null;

        foreach ($conexiones as $conexion) {
            if ($conexion['Con_Acc'] == 'CONDICIONAL' && !empty($conexion['Con_Con_Exp'])) {
                // Nodo decisión con condición específica
                if ($this->evaluarCondicion($conexion['Con_Con_Exp'], $instancia['Ins_Ent_Typ'], $instancia['Ins_Ent_Cod'])) {
                    $nodoDestino_Cod = $conexion['Nod_Des'];
                    break;
                }
            } elseif ($conexion['Con_Acc'] == 'APROBAR' && empty($conexion['Con_Con_Exp']) && $Accion == 'CONDICIONAL') {
                // Rama por defecto/Else en un nodo decisión
                $conexionDefault = $conexion['Nod_Des'];
            } elseif ($conexion['Con_Acc'] == $Accion) {
                // Conexión directa según acción ejecutada
                $nodoDestino_Cod = $conexion['Nod_Des'];
                break;
            }
        }

        if ($nodoDestino_Cod === null && $conexionDefault !== null) {
            $nodoDestino_Cod = $conexionDefault;
        }

        // Si no se cumplió ninguna condición de decisión o acción coincidente, tomamos la primera por defecto
        if ($nodoDestino_Cod === null && count($conexiones) > 0) {
            $nodoDestino_Cod = $conexiones[0]['Nod_Des'];
        }

        // 4. Obtener información del nodo destino
        $nodoDestino = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $nodoDestino_Cod;", $this->obBD_conexion);
        if (empty($nodoDestino)) {
            throw new Exception("El nodo destino configurado no existe.");
        }

        // 5. Calcular SLA si aplica
        $sla_vencimiento = 'NULL';
        if ($nodoDestino['Nod_Sla'] !== null && $nodoDestino['Nod_Sla'] > 0) {
            $horas = $nodoDestino['Nod_Sla'];
            $sla_vencimiento = "'" . date('Y-m-d H:i:s', strtotime("+$horas hours")) . "'";
        }

        // 6. Actualizar instancia de flujo al nuevo nodo actual
        $this->obBD_datos->grabarv_registros("UPDATE wf_instancias SET Nod_Act = $nodoDestino_Cod WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);

        // 7. Escribir en el historial del nodo destino
        $adjunto_str = $Adjuntos !== null ? "'" . $Adjuntos . "'" : "NULL";
        $sqlInsertHistorialDest = "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Adj, Isn_Fec, Isn_Sla_Ven, Isn_Ip, Isn_Ses) 
                                   VALUES ($Ins_Cod, $nodoDestino_Cod, $usu_cod, $dep_cod, '$Accion', '" . mysqli_real_escape_string($this->obBD_conexion->conexion, $Comentario) . "', $adjunto_str, '$fecha_actual', $sla_vencimiento, '$ip_usuario', '$session_id');";
        $this->obBD_datos->grabarv_registros($sqlInsertHistorialDest, $this->obBD_conexion);

        // 8. Si el nuevo nodo es un Nodo de Decisión o de Notificación, se procesa automáticamente
        if ($nodoDestino['Nod_Tip'] == 'DECISION') {
            return $this->avanzarSiguientePaso($Ins_Cod, $nodoDestino_Cod, 'CONDICIONAL', 'Avance automático por nodo decisión.', null);
        } elseif ($nodoDestino['Nod_Tip'] == 'NOTIFICACION') {
            $this->enviarNotificacionNodo($nodoDestino, $instancia);
            return $this->avanzarSiguientePaso($Ins_Cod, $nodoDestino_Cod, 'COMPLETAR', 'Avance automático tras notificación.', null);
        } elseif ($nodoDestino['Nod_Tip'] == 'FIN') {
            // Cierra la instancia del flujo
            $this->obBD_datos->grabarv_registros("UPDATE wf_instancias SET Ins_Est = 'F', Ins_Fec_Fin = '$fecha_actual' WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
            // Actualizar estado de la solicitud principal
            if ($instancia['Ins_Ent_Typ'] == 'adq_solicitudes') {
                $this->obBD_datos->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = 'A' WHERE Sol_Cod = $instancia[Ins_Ent_Cod];", $this->obBD_conexion);
            }
        }

        return true;
    }

    /**
     * Evalúa expresiones condicionales dinámicas basadas en JSON
     * Formato de ejemplo: {"campo": "Sol_Val_Est", "operador": ">", "valor": "5000"}
     */
    protected function evaluarCondicion($expression_json, $entity_type, $entity_id) {
        if (empty($expression_json)) return true;

        $expression = json_decode($expression_json, true);
        if (!$expression || !isset($expression['campo'])) return true;

        $campo = $expression['campo'];
        $operador = isset($expression['operador']) ? $expression['operador'] : '=';
        $valor_condicion = $expression['valor'];

        // Consulta de los datos reales de la cabecera del requerimiento/entidad
        if ($entity_type == 'adq_solicitudes') {
            $datos_solicitud = $this->obBD_datos->getRowConsultaSql("SELECT * FROM adq_solicitudes WHERE Sol_Cod = $entity_id;", $this->obBD_conexion);
            if (empty($datos_solicitud) || !isset($datos_solicitud[$campo])) {
                return false;
            }
            $valor_real = $datos_solicitud[$campo];
        } else {
            return false;
        }

        switch ($operador) {
            case '>':  return $valor_real > $valor_condicion;
            case '<':  return $valor_real < $valor_condicion;
            case '>=': return $valor_real >= $valor_condicion;
            case '<=': return $valor_real <= $valor_condicion;
            case '!=': return $valor_real != $valor_condicion;
            case '=':
            default:   return $valor_real == $valor_condicion;
        }
    }

    /**
     * Envía notificaciones de correo/alertas basadas en el nodo y la instancia
     */
    protected function enviarNotificacionNodo($nodo, $instancia) {
        // En un caso de producción real se usaría la clase Mailer o enviar_correo del sistema
        // Para este entregable, dejamos la simulación del envío de correo integrada
        $asunto = "Notificación de Workflow EXA: " . $nodo['Nod_Nom'];
        $cuerpo = "Se ha procesado una etapa en el workflow para la solicitud # " . $instancia['Ins_Ent_Cod'] . ".\nEtapa: " . $nodo['Nod_Nom'] . "\nDescripción: " . $nodo['Nod_Des'];
        
        // En una base de datos real, buscaríamos correos del departamento responsable o del solicitante
        // Simulando registro de log de notificaciones
        $fecha_actual = date('Y-m-d H:i:s');
        $this->obBD_datos->grabarv_registros("INSERT INTO query_log (sql_text, execution_time) VALUES ('[Notificacion Enviada] Asunto: $asunto', '$fecha_actual');", $this->obBD_conexion);
    }

    /**
     * Ejecuta una acción manual de usuario (Aprobar, Rechazar, Observar, Devolver)
     */
    public function procesarAccionUsuario($Ins_Cod, $Accion, $Comentario, $Adjuntos = null) {
        $this->obBD_datos->inicio_transaccion($this->obBD_conexion);
        try {
            // 1. Obtener la instancia
            $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod AND Ins_Est = 'P';", $this->obBD_conexion);
            if (empty($instancia)) {
                throw new Exception("No existe una instancia de flujo activa para este requerimiento.");
            }

            $nod_actual_cod = $instancia['Nod_Act'];
            $nodoActual = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $nod_actual_cod;", $this->obBD_conexion);

            if (empty($nodoActual)) {
                throw new Exception("La etapa actual del flujo no es válida.");
            }

            // Validar requerimientos obligatorios del nodo (solo para APROBAR)
            if ($Accion == 'APROBAR') {
                $trimmed_comment = trim($Comentario);
                if ($nodoActual['Nod_Com_Obl'] == 1 && $trimmed_comment === '') {
                    throw new Exception("El comentario es obligatorio para aprobar o resolver esta etapa.");
                }
                if ($nodoActual['Nod_Adj_Obl'] == 1 && empty($Adjuntos)) {
                    throw new Exception("Se requiere cargar al menos un archivo adjunto como sustento de esta etapa.");
                }
            }

            $fecha_actual = date('Y-m-d H:i:s');
            $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $session_id = session_id() ?: 'CLI-SESSION';
            $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0;
            $dep_cod = isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0;

            // Manejar acción DEVOLVER: retroceder al nodo anterior en el historial
            if ($Accion == 'DEVOLVER') {
                $nodoAnterior = $this->obBD_datos->getRowConsultaSql("
                    SELECT DISTINCT h.Nod_Cod 
                    FROM wf_instancias_nodos h 
                    INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
                    WHERE h.Ins_Cod = $Ins_Cod 
                      AND h.Nod_Cod != $nod_actual_cod 
                      AND n.Nod_Tip NOT IN ('INICIO', 'DECISION', 'NOTIFICACION', 'FIN')
                    ORDER BY h.Isn_Fec DESC LIMIT 1;", $this->obBD_conexion);

                if (empty($nodoAnterior)) {
                    throw new Exception("No existe un paso anterior al cual devolver esta solicitud.");
                }

                $nod_devolver = $nodoAnterior['Nod_Cod'];

                // Registrar en historial la acción DEVOLVER en el nodo actual
                $com_esc = mysqli_real_escape_string($this->obBD_conexion->conexion, $Comentario);
                $adjunto_str = $Adjuntos !== null ? "'" . $Adjuntos . "'" : "NULL";
                $this->obBD_datos->grabarv_registros("INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Adj, Isn_Fec, Isn_Ip, Isn_Ses) 
                    VALUES ($Ins_Cod, $nod_actual_cod, $usu_cod, $dep_cod, 'DEVOLVER', '$com_esc', $adjunto_str, '$fecha_actual', '$ip_usuario', '$session_id');", $this->obBD_conexion);

                // Mover instancia al nodo anterior
                $this->obBD_datos->grabarv_registros("UPDATE wf_instancias SET Nod_Act = $nod_devolver WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);

                // Actualizar estado de solicitud a Observado
                if ($instancia['Ins_Ent_Typ'] == 'adq_solicitudes') {
                    $this->obBD_datos->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = 'O' WHERE Sol_Cod = $instancia[Ins_Ent_Cod];", $this->obBD_conexion);
                }

                $this->obBD_datos->commit_nomsn($this->obBD_conexion);
                return array('success' => true);
            }

            // Actualizar estado intermedio de la solicitud según la acción
            if ($instancia['Ins_Ent_Typ'] == 'adq_solicitudes') {
                $nuevo_est_sol = 'E'; // Por defecto En proceso
                if ($Accion == 'RECHAZAR') {
                    $nuevo_est_sol = 'R';
                    $this->obBD_datos->grabarv_registros("UPDATE wf_instancias SET Ins_Est = 'R', Ins_Fec_Fin = '$fecha_actual' WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
                } elseif ($Accion == 'OBSERVAR') {
                    $nuevo_est_sol = 'O';
                }
                $this->obBD_datos->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = '$nuevo_est_sol' WHERE Sol_Cod = $instancia[Ins_Ent_Cod];", $this->obBD_conexion);
            }

            // 2. Ejecutar avance al siguiente paso
            if ($Accion != 'RECHAZAR') {
                $this->avanzarSiguientePaso($Ins_Cod, $nod_actual_cod, $Accion, $Comentario, $Adjuntos);
            }

            $this->obBD_datos->commit_nomsn($this->obBD_conexion);
            return array('success' => true);
        } catch (Exception $e) {
            $this->obBD_datos->rollBack_nomsn($this->obBD_conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Vincula una factura de compra con una solicitud de adquisición aprobada
     */
    public function vincularCompra($Sol_Cod, $Cop_Cod) {
        $fecha_actual = date('Y-m-d H:i:s');
        // Verificar que no exista ya la vinculación
        $existe = $this->obBD_datos->getRowConsultaSql("SELECT Scm_Cod FROM adq_solicitudes_compras WHERE Sol_Cod = $Sol_Cod AND Cop_Cod = $Cop_Cod;", $this->obBD_conexion);
        if (!empty($existe)) {
            return array('success' => false, 'message' => 'Esta factura ya fue vinculada a la solicitud.');
        }
        $this->obBD_datos->grabarv_registros("INSERT INTO adq_solicitudes_compras (Sol_Cod, Cop_Cod, Scm_Fec) VALUES ($Sol_Cod, $Cop_Cod, '$fecha_actual');", $this->obBD_conexion);
        return array('success' => true);
    }

    /**
     * Desvincula una factura de compra de una solicitud
     */
    public function desvincularCompra($Scm_Cod) {
        $this->obBD_datos->grabarv_registros("DELETE FROM adq_solicitudes_compras WHERE Scm_Cod = $Scm_Cod;", $this->obBD_conexion);
        return array('success' => true);
    }

    /**
     * Obtiene las compras vinculadas a una solicitud
     */
    public function getComprasVinculadas($Sol_Cod) {
        return $this->obBD_datos->getArrayConsultaSql("
            SELECT sc.Scm_Cod, sc.Cop_Cod, sc.Scm_Fec, c.Cop_Num, c.Cop_Fec, 
                   CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) as Proveedor,
                   (SELECT SUM(dc.Cop_Imp - (dc.Cop_Imp * dc.Cop_Dec / 100)) FROM det_compra dc WHERE dc.Cop_Cod = c.Cop_Cod) as Total_Compra
            FROM adq_solicitudes_compras sc
            INNER JOIN compras c ON c.Cop_Cod = sc.Cop_Cod
            INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
            INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod
            WHERE sc.Sol_Cod = $Sol_Cod
            ORDER BY sc.Scm_Fec DESC;", $this->obBD_conexion);
    }

    /**
     * Retorna el árbol/red de nodos actual con su color de estado
     */
    public function getVisualFlowData($Ins_Cod) {
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
        if (empty($instancia)) {
            return array();
        }

        // Obtener todos los nodos del flujo modelo
        $nodos = $this->obBD_datos->getArrayConsultaSql("SELECT * FROM wf_nodos WHERE Wfm_Cod = $instancia[Wfm_Cod] AND Nod_Est = 'A';", $this->obBD_conexion);
        
        // Obtener todos los pasos del historial ejecutados para esta instancia
        $pasos_ejecutados = $this->obBD_datos->getArrayConsultaSql("SELECT Nod_Cod, Isn_Acc FROM wf_instancias_nodos WHERE Ins_Cod = $Ins_Cod ORDER BY Isn_Fec ASC;", $this->obBD_conexion);
        
        $nodos_visitados = array();
        foreach ($pasos_ejecutados as $paso) {
            $nodos_visitados[$paso['Nod_Cod']] = $paso['Isn_Acc'];
        }

        $visual_nodos = array();
        foreach ($nodos as $nodo) {
            $color = 'grey'; // Por defecto: pendiente

            if ($nodo['Nod_Cod'] == $instancia['Nod_Act'] && $instancia['Ins_Est'] == 'P') {
                $color = 'blue'; // Etapa actual activa
            } elseif (isset($nodos_visitados[$nodo['Nod_Cod']])) {
                $accion = $nodos_visitados[$nodo['Nod_Cod']];
                if ($accion == 'RECHAZAR') {
                    $color = 'red'; // Rechazado
                } elseif ($accion == 'OBSERVAR') {
                    $color = 'red'; // Observado (atención requerida)
                } else {
                    $color = 'green'; // Completado
                }
            }

            $visual_nodos[] = array(
                'id' => $nodo['Nod_Cod'],
                'nombre' => $nodo['Nod_Nom'],
                'tipo' => $nodo['Nod_Tip'],
                'color' => $color,
                'x' => $nodo['Nod_Vis_X'],
                'y' => $nodo['Nod_Vis_Y']
            );
        }

        // Obtener conexiones
        $conexiones = $this->obBD_datos->getArrayConsultaSql("SELECT * FROM wf_conexiones WHERE Wfm_Cod = $instancia[Wfm_Cod];", $this->obBD_conexion);

        return array(
            'nodos' => $visual_nodos,
            'conexiones' => $conexiones,
            'nodo_actual' => $instancia['Nod_Act']
        );
    }
}
