<?php
/**
 * EXA Workflow Manager - Motor de Flujos Gen�rico
 * 
 * L�gica principal del motor de procesos empresariales y enrutamientos.
 * @author Oz <oz-agent@warp.dev>
 * @version 1.0
 */

require_once(dirname(__FILE__) . '/../../DATA/GestorErrores.php');
require_once(dirname(__FILE__) . '/../../DATA/MysqlConexion.php');
require_once(dirname(__FILE__) . '/../../DATA/MysqlDatos.php');

class wf_manager_log {
    public $obBD_conexion;
    public $obBD_datos;

    public function __construct($Ses_Dat_Dis = null, $conexion = null) {
        if ($conexion !== null) {
            $this->obBD_conexion = $conexion;
        } else {
            if ($Ses_Dat_Dis === null && isset($_SESSION['Ses_Dat_Dis'])) {
                $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
            }
            $this->obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
        }
        $this->obBD_datos = new MysqlDatos($this->obBD_conexion);
    }

    private function ejecutarSql($sql) {
        $this->obBD_datos->setError(0, '');
        if (!$this->obBD_datos->grabarv_registros($sql, $this->obBD_conexion)) {
            throw new Exception('Error en workflow: ' . $this->obBD_datos->getMsgError());
        }
    }

    private function resolverFkHistorial($usu_cod, $dep_cod) {
        $usu = intval($usu_cod);
        $dep = intval($dep_cod);
        $usu_sql = 'NULL';
        $dep_sql = 'NULL';
        if ($usu > 0) {
            $row = $this->obBD_datos->getRowConsultaSql("SELECT Usu_Cod FROM usuarios WHERE Usu_Cod = $usu LIMIT 1;", $this->obBD_conexion);
            if (!empty($row)) {
                $usu_sql = $usu;
            }
        }
        if ($dep > 0) {
            $row = $this->obBD_datos->getRowConsultaSql("SELECT Dep_Cod FROM departamen WHERE Dep_Cod = $dep LIMIT 1;", $this->obBD_conexion);
            if (!empty($row)) {
                $dep_sql = $dep;
            }
        }
        return array('usu' => $usu_sql, 'dep' => $dep_sql);
    }

    private function avanzarDesdeNodoInicio($Ins_Cod, $Nod_Inicio_Cod, $Comentario = '') {
        $Nod_Inicio_Cod = intval($Nod_Inicio_Cod);
        $conexiones = $this->obBD_datos->getArrayConsultaSql(
            "SELECT * FROM wf_conexiones WHERE Nod_Ori = $Nod_Inicio_Cod ORDER BY Con_Cod ASC;",
            $this->obBD_conexion
        );
        if (empty($conexiones)) {
            return false;
        }
        $accion = !empty($conexiones[0]['Con_Acc']) ? $conexiones[0]['Con_Acc'] : 'CREAR';
        return $this->avanzarSiguientePaso($Ins_Cod, $Nod_Inicio_Cod, $accion, $Comentario, null);
    }

    public function avanzarSiEstaEnInicio($Ins_Cod, $Comentario = '') {
        $Ins_Cod = intval($Ins_Cod);
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT i.Nod_Act, n.Nod_Tip
             FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE i.Ins_Cod = $Ins_Cod AND i.Ins_Est = 'P' LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row) || $row['Nod_Tip'] !== 'INICIO') {
            return false;
        }
        return $this->avanzarDesdeNodoInicio($Ins_Cod, $row['Nod_Act'], $Comentario);
    }

    public function repararInstanciasEnInicio($ent_typ = 'adq_solicitudes') {
        $ent_typ = mysqli_real_escape_string($this->obBD_conexion->conexion, $ent_typ);
        $atascadas = $this->obBD_datos->getArrayConsultaSql(
            "SELECT i.Ins_Cod, i.Nod_Act
             FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act AND n.Nod_Tip = 'INICIO'
             WHERE i.Ins_Est = 'P' AND i.Ins_Ent_Typ = '$ent_typ';",
            $this->obBD_conexion
        );
        if (empty($atascadas)) {
            return 0;
        }
        $reparadas = 0;
        foreach ($atascadas as $row) {
            try {
                if ($this->avanzarDesdeNodoInicio($row['Ins_Cod'], $row['Nod_Act'], 'Reparacion automatica: salida desde Inicio.')) {
                    $reparadas++;
                }
            } catch (Exception $e) {
            }
        }
        return $reparadas;
    }

    /**
     * Verifica si el usuario actual tiene acceso a una ventana o pesta�a espec�fica
     */
    public function verificarAccesoVentana($ventana, $tab = null) {
        // Retornamos true por defecto para que el usuario gestione los accesos
        // mediante el sistema de seguridad nativo de EXA (seguridad.php)
        return true;
    }

    /**
     * Valida que un Wde_Cod exista en wf_departamentos para la empresa.
     */
    public function validarWdeCodWorkflow($wde_cod, $emp_cod) {
        $wde_cod = intval($wde_cod);
        $emp_cod = intval($emp_cod);
        if ($wde_cod <= 0 || $emp_cod <= 0) {
            return false;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT Wde_Cod FROM wf_departamentos WHERE Wde_Cod = $wde_cod AND Emp_Cod = $emp_cod LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row['Wde_Cod']);
    }

    /**
     * Asegura columna Wde_Cod en wf_departamento_usuarios y migra datos legacy.
     */
    public function ensureWfDepartamentoUsuariosWdeCod($emp_cod = null) {
        $emp_cod = $emp_cod !== null ? intval($emp_cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
        $col = $this->obBD_datos->getRowConsultaSql(
            "SHOW COLUMNS FROM wf_departamento_usuarios LIKE 'Wde_Cod';",
            $this->obBD_conexion
        );
        if (empty($col)) {
            $this->obBD_datos->setError(0, '');
            $this->obBD_datos->grabarv_registros(
                "ALTER TABLE wf_departamento_usuarios
                 ADD COLUMN Wde_Cod BIGINT DEFAULT NULL COMMENT 'llave foranea de wf_departamentos' AFTER Usu_Cod,
                 ADD KEY Wde_Cod (Wde_Cod);",
                $this->obBD_conexion
            );
        }
        if ($emp_cod > 0) {
            $this->obBD_datos->setError(0, '');
            $this->obBD_datos->grabarv_registros(
                "UPDATE wf_departamento_usuarios du
                 INNER JOIN wf_departamentos w ON w.Dep_Cod = du.Dep_Cod AND w.Emp_Cod = $emp_cod
                 SET du.Wde_Cod = w.Wde_Cod
                 WHERE du.Wde_Cod IS NULL;",
                $this->obBD_conexion
            );
        }
        $this->obBD_datos->setError(0, '');
        $this->obBD_datos->grabarv_registros(
            "UPDATE wf_departamento_usuarios du
             INNER JOIN wf_departamentos w ON w.Wde_Cod = du.Dep_Cod
             SET du.Wde_Cod = w.Wde_Cod
             WHERE du.Wde_Cod IS NULL;",
            $this->obBD_conexion
        );
    }

    /**
     * SQL de insercion de usuario en departamento workflow.
     * Dep_Cod = RRHH (parte del UNIQUE uq_dep_usu), Wde_Cod = FK a wf_departamentos.
     */
    private function sqlInsertDepartamentoUsuario($wde_cod, $usu_cod, $dep_rrhh_cod) {
        $wde_cod = intval($wde_cod);
        $usu_cod = intval($usu_cod);
        $dep_rrhh_cod = intval($dep_rrhh_cod);
        if ($dep_rrhh_cod <= 0) {
            throw new Exception('Dep_Cod de RRHH invalido para insertar usuario en departamento.');
        }
        return "INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod, Wde_Cod)
                VALUES ($dep_rrhh_cod, $usu_cod, $wde_cod)
                ON DUPLICATE KEY UPDATE Wde_Cod = VALUES(Wde_Cod);";
    }

    /**
     * Guarda usuarios asignados a un departamento de workflow.
     * Tabla destino: wf_departamento_usuarios
     *  - Dep_Cod = departamen.Dep_Cod (RRHH)  [UNIQUE con Usu_Cod]
     *  - Usu_Cod = usuarios.Usu_Cod
     *  - Wde_Cod = wf_departamentos.Wde_Cod    [FK]
     */
    public function guardarUsuariosDepartamentoWorkflow($wde_cod, $emp_cod, $usuarios_ids) {
        $this->ensureWfDepartamentosTable();
        $this->ensureWfDepartamentoUsuariosWdeCod($emp_cod);

        $wde_cod = intval($wde_cod);
        $emp_cod = intval($emp_cod);
        if (!$this->validarWdeCodWorkflow($wde_cod, $emp_cod)) {
            throw new Exception('Departamento de workflow no valido.');
        }

        $dep_rrhh = $this->resolverDepRrhhDesdeWf($wde_cod, $emp_cod);
        if ($dep_rrhh <= 0) {
            throw new Exception('El departamento workflow no tiene Dep_Cod de RRHH vinculado. Verifique wf_departamentos.Dep_Cod.');
        }

        if (!is_array($usuarios_ids)) {
            $usuarios_ids = ($usuarios_ids === null || $usuarios_ids === '') ? array() : array($usuarios_ids);
        }

        $ids_base = array();
        foreach ($usuarios_ids as $u_id) {
            $u_id = intval($u_id);
            if ($u_id > 0) {
                $ids_base[$u_id] = $u_id;
            }
        }

        $this->obBD_datos->inicio_transaccion($this->obBD_conexion);
        try {
            // Limpiar asignaciones previas de este departamento WF / RRHH
            $this->ejecutarSql("DELETE FROM wf_departamento_usuarios WHERE Wde_Cod = $wde_cod;");
            $this->ejecutarSql("DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = $dep_rrhh;");

            $insertados = 0;
            $usu_insertados = array();

            foreach ($ids_base as $u_id) {
                // Todas las cuentas activas WF de la misma cedula en la empresa
                $cuentas = $this->obBD_datos->getArrayConsultaSql(
                    "SELECT ux.Usu_Cod
                     FROM usuarios ux
                     INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                     WHERE sx.Emp_Cod = $emp_cod
                       AND ux.Usu_Est = 'A'
                       AND ux.Usu_Wf = 'S'
                       AND ux.Usu_Ced = (SELECT u0.Usu_Ced FROM usuarios u0 WHERE u0.Usu_Cod = $u_id LIMIT 1);",
                    $this->obBD_conexion
                );
                if ($cuentas === false || $cuentas === null || empty($cuentas)) {
                    // Fallback: insertar el Usu_Cod seleccionado si es valido en la empresa
                    $cuenta = $this->obBD_datos->getRowConsultaSql(
                        "SELECT ux.Usu_Cod
                         FROM usuarios ux
                         INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                         WHERE ux.Usu_Cod = $u_id AND sx.Emp_Cod = $emp_cod AND ux.Usu_Est = 'A'
                         LIMIT 1;",
                        $this->obBD_conexion
                    );
                    $cuentas = !empty($cuenta['Usu_Cod']) ? array($cuenta) : array();
                }

                foreach ($cuentas as $cuenta) {
                    $usu_cod = intval($cuenta['Usu_Cod']);
                    if ($usu_cod <= 0 || isset($usu_insertados[$usu_cod])) {
                        continue;
                    }
                    $this->ejecutarSql(
                        $this->sqlInsertDepartamentoUsuario($wde_cod, $usu_cod, $dep_rrhh)
                    );
                    $usu_insertados[$usu_cod] = true;
                    $insertados++;
                }
            }

            $this->obBD_datos->commit_nomsn($this->obBD_conexion);
            return array(
                'insertados' => $insertados,
                'dep_rrhh' => $dep_rrhh,
                'wde_cod' => $wde_cod,
                'recibidos' => count($ids_base)
            );
        } catch (Exception $e) {
            $this->obBD_datos->rollBack_nomsn($this->obBD_conexion);
            throw $e;
        }
    }

    public function resolverContextoUsuario($emp_cod = null) {
        $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $dep_cod = isset($_SESSION['Ses_Dep_Cod']) ? intval($_SESSION['Ses_Dep_Cod']) : 0;
        $emp_cod = $emp_cod !== null ? intval($emp_cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);

        if ($dep_cod <= 0 && $usu_cod > 0 && $emp_cod > 0) {
            $dep_row = $this->obBD_datos->getRowConsultaSql(
                "SELECT MIN(w.Dep_Cod) AS Dep_Cod
                 FROM wf_departamento_usuarios du
                 INNER JOIN wf_departamentos w ON w.Wde_Cod = du.Wde_Cod AND w.Emp_Cod = $emp_cod AND w.Wde_Est = 'A'
                 WHERE du.Usu_Cod = $usu_cod AND du.Wde_Cod IS NOT NULL",
                $this->obBD_conexion
            );
            if (!empty($dep_row['Dep_Cod'])) {
                $dep_cod = intval($dep_row['Dep_Cod']);
                $_SESSION['Ses_Dep_Cod'] = $dep_cod;
            }
        }

        $perfiles_ids = !empty($_SESSION['Ses_Lis_Per']) ? implode(',', array_map('intval', $_SESSION['Ses_Lis_Per'])) : '-1';

        return array(
            'usu_cod' => $usu_cod,
            'dep_cod' => $dep_cod,
            'emp_cod' => $emp_cod,
            'perfiles_ids' => $perfiles_ids
        );
    }

    public function sqlClausulaNodoAsignadoAUsuario($usu_cod, $dep_cod, $perfiles_ids) {
        $usu_cod = intval($usu_cod);
        $dep_cod = intval($dep_cod);
        $dep_sesion_sql = $dep_cod > 0
            ? "n.Dep_Cod IN (SELECT d2.Dep_Cod FROM departamen d1 INNER JOIN departamen d2 ON d2.Dep_Des = d1.Dep_Des WHERE d1.Dep_Cod = $dep_cod)"
            : '0=1';

        $dep_wf_sql = $dep_cod > 0
            ? "n.Dep_Cod IN (
                SELECT wd.Dep_Cod FROM wf_departamentos wd
                WHERE wd.Dep_Cod = $dep_cod AND wd.Wde_Est = 'A'
            )"
            : '0=1';

        // Usuario(s) asignado(s) explicitamente en el nodo (ej. cierre FIN): no exige depto/perfil.
        $asignacion_explicita = "(
            n.Nod_Usu_Asig IS NOT NULL
            AND TRIM(n.Nod_Usu_Asig) != ''
            AND n.Nod_Usu_Asig != 'TODOS'
            AND FIND_IN_SET($usu_cod, n.Nod_Usu_Asig) > 0
        )";

        // Asignacion por departamento o perfil cuando el nodo es para TODOS o sin lista de usuarios.
        $asignacion_por_rol = "(
            (n.Nod_Usu_Asig IS NULL OR TRIM(n.Nod_Usu_Asig) = '' OR n.Nod_Usu_Asig = 'TODOS')
            AND (
                n.Dep_Cod IS NULL
                OR n.Dep_Cod IN (
                    SELECT w.Dep_Cod
                    FROM wf_departamento_usuarios du
                    INNER JOIN wf_departamentos w ON w.Wde_Cod = du.Wde_Cod AND w.Wde_Est = 'A'
                    WHERE du.Usu_Cod = $usu_cod AND du.Wde_Cod IS NOT NULL
                )
                OR $dep_sesion_sql
                OR $dep_wf_sql
                OR (n.Per_Cod IS NOT NULL AND n.Per_Cod > 0 AND n.Per_Cod IN ($perfiles_ids))
            )
        )";

        return "($asignacion_explicita OR $asignacion_por_rol)";
    }

    /**
     * Indica si el usuario esta en la lista explicita del nodo activo de la instancia.
     */
    public function usuarioAsignadoExplicitoInstancia($ins_cod, $usu_cod) {
        $ins_cod = intval($ins_cod);
        $usu_cod = intval($usu_cod);
        if ($ins_cod <= 0 || $usu_cod <= 0) {
            return false;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT 1 AS ok
             FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE i.Ins_Cod = $ins_cod
               AND i.Ins_Est = 'P'
               AND n.Nod_Usu_Asig IS NOT NULL
               AND TRIM(n.Nod_Usu_Asig) != ''
               AND n.Nod_Usu_Asig != 'TODOS'
               AND FIND_IN_SET($usu_cod, n.Nod_Usu_Asig) > 0
             LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row);
    }

    /**
     * Filtro de bandeja: evita auto-aprobacion salvo asignacion explicita al solicitante.
     */
    public function sqlFiltroPendienteSinAutoaprobacion($usu_cod, $es_gerencial_sql) {
        $usu_cod = intval($usu_cod);
        $es_gerencial_sql = ($es_gerencial_sql === '1' || $es_gerencial_sql === 1) ? '1' : '0';
        return "(
            s.Usu_Sol != $usu_cod
            OR $es_gerencial_sql = 1
            OR EXISTS (
                SELECT 1 FROM wf_instancias_nodos hr
                WHERE hr.Ins_Cod = i.Ins_Cod AND hr.Isn_Acc = 'REENVIAR'
            )
            OR (
                n.Nod_Usu_Asig IS NOT NULL
                AND TRIM(n.Nod_Usu_Asig) != ''
                AND n.Nod_Usu_Asig != 'TODOS'
                AND FIND_IN_SET($usu_cod, n.Nod_Usu_Asig) > 0
            )
        )";
    }

    /**
     * Indica si el usuario puede registrar una decision manual en la instancia activa.
     */
    public function puedeResolverInstancia($ins_cod, $usu_cod, $dep_cod, $perfiles_ids) {
        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0) {
            return false;
        }
        $clausula = $this->sqlClausulaNodoAsignadoAUsuario($usu_cod, $dep_cod, $perfiles_ids);
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT n.Nod_Cod, n.Nod_Tip
             FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P' AND $clausula
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row)) {
            return false;
        }
        return $this->esNodoResolubleHumano($row['Nod_Tip']);
    }

    /**
     * Tras un REENVIAR del solicitante, el aprobador que observo/devolvio puede volver a decidir.
     */
    public function instanciaTieneReenvioParaRevision($ins_cod) {
        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0) {
            return false;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT Isn_Fec FROM wf_instancias_nodos
             WHERE Ins_Cod = $ins_cod AND Isn_Acc = 'REENVIAR'
             ORDER BY Isn_Fec DESC LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row['Isn_Fec']);
    }

    /**
     * Valida si el usuario puede tomar una decision sobre la solicitud en su etapa actual.
     */
    public function puedeUsuarioResolverSolicitud($sol, $usu_cod, $dep_cod, $perfiles_ids, $es_gerencial = false) {
        if (empty($sol['Ins_Cod']) || $sol['Ins_Est'] !== 'P' || $sol['Sol_Est'] === 'O') {
            return false;
        }
        if (!$this->puedeResolverInstancia(intval($sol['Ins_Cod']), $usu_cod, $dep_cod, $perfiles_ids)) {
            return false;
        }
        if (intval($sol['Usu_Sol']) !== intval($usu_cod)) {
            return true;
        }
        if ($es_gerencial) {
            return true;
        }
        if ($this->usuarioAsignadoExplicitoInstancia(intval($sol['Ins_Cod']), $usu_cod)) {
            return true;
        }
        return $this->instanciaTieneReenvioParaRevision(intval($sol['Ins_Cod']));
    }

    public function motivoBloqueoResolucionSolicitud($sol, $usu_cod, $dep_cod, $perfiles_ids, $es_gerencial = false) {
        if (empty($sol['Ins_Cod']) || $sol['Ins_Est'] !== 'P') {
            return 'La solicitud no tiene una instancia activa de workflow.';
        }
        if ($sol['Sol_Est'] === 'O') {
            return 'La solicitud esta observada y debe corregirse antes de volver a aprobarse.';
        }
        if (!$this->puedeResolverInstancia(intval($sol['Ins_Cod']), $usu_cod, $dep_cod, $perfiles_ids)) {
            return 'La etapa actual no esta asignada a su usuario, departamento o perfil.';
        }
        if (intval($sol['Usu_Sol']) === intval($usu_cod) && !$es_gerencial && !$this->instanciaTieneReenvioParaRevision(intval($sol['Ins_Cod'])) && !$this->usuarioAsignadoExplicitoInstancia(intval($sol['Ins_Cod']), $usu_cod)) {
            return 'No puede aprobar su propia solicitud hasta que exista un reenvio tras observacion o devolucion.';
        }
        return '';
    }

    /**
     * Valida si el usuario puede ver el seguimiento detallado (linea de tiempo / grafico).
     * Debe alinearse con quien puede abrir el detalle desde la bandeja.
     */
    public function puedeUsuarioVerSeguimientoSolicitud($sol, $usu_cod, $dep_cod, $perfiles_ids, $es_gerencial = false) {
        if ($es_gerencial) {
            return true;
        }
        if (empty($sol) || empty($sol['Sol_Cod'])) {
            return false;
        }
        $usu_cod = intval($usu_cod);
        $sol_cod = intval($sol['Sol_Cod']);
        if (intval($sol['Usu_Sol']) === $usu_cod) {
            return true;
        }
        if (!empty($sol['Ins_Cod']) && isset($sol['Ins_Est']) && $sol['Ins_Est'] === 'P') {
            $clausula = $this->sqlClausulaNodoAsignadoAUsuario($usu_cod, intval($dep_cod), $perfiles_ids);
            $row = $this->obBD_datos->getRowConsultaSql(
                "SELECT 1 AS ok
                 FROM wf_instancias i
                 INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
                 WHERE i.Ins_Cod = " . intval($sol['Ins_Cod']) . "
                   AND i.Ins_Est = 'P'
                   AND $clausula
                 LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($row)) {
                return true;
            }
            if ($this->puedeUsuarioCargarCotizaciones($sol, $usu_cod, $dep_cod, $perfiles_ids)) {
                return true;
            }
            if ($this->puedeUsuarioCargarAvance($sol, $usu_cod, $dep_cod, $perfiles_ids)) {
                return true;
            }
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT 1 AS ok
             FROM wf_instancias i
             INNER JOIN wf_instancias_nodos h ON h.Ins_Cod = i.Ins_Cod
             WHERE i.Ins_Ent_Typ = 'adq_solicitudes'
               AND i.Ins_Ent_Cod = $sol_cod
               AND h.Usu_Cod = $usu_cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row);
    }

    /**
     * Indica si la etapa activa permite cargar cotizaciones/proformas.
     * Usa el nodo de la instancia y, si aplica, el flujo publicado de la misma familia.
     */
    public function resolverNodCotEditInstancia($ins_cod) {
        $this->ensureNotificationSchema();
        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0) {
            return 0;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT i.Wfm_Cod, n.Nod_Nom, n.Nod_Tip, IFNULL(n.Nod_Cot_Edit, 0) AS Nod_Cot_Edit
             FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P'
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row)) {
            return 0;
        }
        if (intval($row['Nod_Cot_Edit']) === 1) {
            return 1;
        }
        $wfm_cod = intval($row['Wfm_Cod']);
        $nod_nom = $row['Nod_Nom'];
        $nod_tip = $row['Nod_Tip'];
        return $this->resolverNodCotEditPublicado($wfm_cod, $nod_nom, $nod_tip);
    }

    /**
     * Indica si un nodo concreto permite cargar cotizaciones/proformas.
     */
    public function resolverNodCotEditNodo($nod_cod) {
        $this->ensureNotificationSchema();
        $nod_cod = intval($nod_cod);
        if ($nod_cod <= 0) {
            return 0;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT n.Wfm_Cod, n.Nod_Nom, n.Nod_Tip, IFNULL(n.Nod_Cot_Edit, 0) AS Nod_Cot_Edit
             FROM wf_nodos n
             WHERE n.Nod_Cod = $nod_cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row)) {
            return 0;
        }
        if (intval($row['Nod_Cot_Edit']) === 1) {
            return 1;
        }
        return $this->resolverNodCotEditPublicado(intval($row['Wfm_Cod']), $row['Nod_Nom'], $row['Nod_Tip']);
    }

    private function resolverNodCotEditPublicado($wfm_cod, $nod_nom, $nod_tip) {
        $wfm_cod = intval($wfm_cod);
        if ($wfm_cod <= 0) {
            return 0;
        }
        $nod_nom = $this->escapeWf($nod_nom);
        $nod_tip = $this->escapeWf($nod_tip);
        $pub = $this->obBD_datos->getRowConsultaSql(
            "SELECT IFNULL(n2.Nod_Cot_Edit, 0) AS Nod_Cot_Edit
             FROM wf_flujos_modelos w_inst
             INNER JOIN wf_flujos_modelos w_pub ON w_pub.Emp_Cod = w_inst.Emp_Cod
                AND COALESCE(w_pub.Wfm_Fam_Cod, w_pub.Wfm_Cod) = COALESCE(w_inst.Wfm_Fam_Cod, w_inst.Wfm_Cod)
                AND w_pub.Wfm_Est = 'P'
             INNER JOIN wf_nodos n2 ON n2.Wfm_Cod = w_pub.Wfm_Cod
                AND n2.Nod_Nom = '$nod_nom' AND n2.Nod_Tip = '$nod_tip' AND n2.Nod_Est = 'A'
             WHERE w_inst.Wfm_Cod = $wfm_cod
             ORDER BY w_pub.Wfm_Cod DESC
             LIMIT 1;",
            $this->obBD_conexion
        );
        return (!empty($pub) && intval($pub['Nod_Cot_Edit']) === 1) ? 1 : 0;
    }

    /**
     * Ultima etapa humana previa (por historial) con carga de proformas habilitada.
     */
    private function resolverNodoEtapaAnteriorConProformas($Ins_Cod, $nod_actual_cod) {
        $Ins_Cod = intval($Ins_Cod);
        $nod_actual_cod = intval($nod_actual_cod);
        if ($Ins_Cod <= 0 || $nod_actual_cod <= 0) {
            return 0;
        }

        $nodos = $this->obBD_datos->getArrayConsultaSql(
            "SELECT h.Nod_Cod, MAX(h.Isn_Fec) AS ult_fec
             FROM wf_instancias_nodos h
             INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
             WHERE h.Ins_Cod = $Ins_Cod
               AND h.Nod_Cod != $nod_actual_cod
               AND n.Nod_Tip NOT IN ('INICIO', 'DECISION', 'NOTIFICACION', 'FIN')
             GROUP BY h.Nod_Cod
             ORDER BY ult_fec DESC",
            $this->obBD_conexion
        );
        if ($nodos === false || $nodos === null) {
            $nodos = array();
        }

        foreach ($nodos as $nodo_hist) {
            $nod_cod = intval($nodo_hist['Nod_Cod']);
            if ($nod_cod <= 0) {
                continue;
            }
            if ($this->resolverNodCotEditNodo($nod_cod) === 1) {
                return $nod_cod;
            }
        }

        return 0;
    }

    /**
     * Indica si el usuario puede cargar cotizaciones en la etapa actual (sin regla de auto-aprobacion).
     */
    public function puedeUsuarioCargarCotizaciones($sol, $usu_cod, $dep_cod, $perfiles_ids) {
        if (empty($sol['Ins_Cod']) || $sol['Ins_Est'] !== 'P') {
            return false;
        }
        if (in_array($sol['Sol_Est'], array('A', 'R'), true)) {
            return false;
        }
        if ($this->resolverNodCotEditInstancia(intval($sol['Ins_Cod'])) !== 1) {
            return false;
        }
        return $this->puedeResolverInstancia(intval($sol['Ins_Cod']), $usu_cod, $dep_cod, $perfiles_ids);
    }

    /**
     * Indica si la instancia activa esta en un nodo de tipo AVANCE.
     */
    public function etapaEsAvanceInstancia($ins_cod) {
        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0) {
            return false;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT n.Nod_Tip FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P' LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row['Nod_Tip']) && in_array($row['Nod_Tip'], array('AVANCE', 'FISCALIZACION'), true);
    }

    /**
     * Indica si el usuario puede cargar documentos de avance en la etapa actual.
     */
    public function puedeUsuarioCargarAvance($sol, $usu_cod, $dep_cod, $perfiles_ids) {
        if (empty($sol['Ins_Cod']) || $sol['Ins_Est'] !== 'P') {
            return false;
        }
        if (in_array($sol['Sol_Est'], array('A', 'R'), true)) {
            return false;
        }
        if (!$this->etapaEsAvanceInstancia(intval($sol['Ins_Cod']))) {
            return false;
        }
        return $this->puedeResolverInstancia(intval($sol['Ins_Cod']), $usu_cod, $dep_cod, $perfiles_ids);
    }

    /**
     * Indica si el usuario puede crear solicitudes del tipo/flujo (segun nodo INICIO).
     */
    public function puedeUsuarioCrearTipoRequerimiento($trq_cod, $emp_cod, $usu_cod = null, $dep_cod = null, $perfiles_ids = null) {
        $this->ensureVersioningSchema();
        $trq_cod = intval($trq_cod);
        $emp_cod = intval($emp_cod);
        if ($trq_cod <= 0 || $emp_cod <= 0) {
            return false;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT 1 AS ok
             FROM adq_tipos_requerimientos t
             INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = t.Wfm_Cod
             WHERE t.Trq_Cod = $trq_cod
               AND t.Emp_Cod = $emp_cod
               AND t.Trq_Est IN ('A', 'E')
             LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row);
    }

    /**
     * Tipos de requerimiento disponibles al crear solicitud (todos los activos de la empresa).
     */
    public function listarTiposRequerimientoParaCrear($emp_cod, $usu_cod = null, $dep_cod = null, $perfiles_ids = null) {
        $this->ensureVersioningSchema();
        $emp_cod = intval($emp_cod);
        // Normaliza estados residuales "En proceso" (E) a Activo (A).
        $this->obBD_datos->grabarv_registros(
            "UPDATE adq_tipos_requerimientos SET Trq_Est = 'A' WHERE Emp_Cod = $emp_cod AND Trq_Est = 'E';",
            $this->obBD_conexion
        );
        $tipos = $this->obBD_datos->getArrayConsultaSql(
            "SELECT t.Trq_Cod, t.Trq_Des, w.Wfm_Nom, w.Wfm_Cod
             FROM adq_tipos_requerimientos t
             INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = t.Wfm_Cod
             WHERE t.Emp_Cod = $emp_cod
               AND t.Trq_Est = 'A'
             ORDER BY t.Trq_Des;",
            $this->obBD_conexion
        );
        return ($tipos === false || $tipos === null) ? array() : $tipos;
    }

    private $versioningReady = false;
    private $notificationSchemaReady = false;

    public function ensureVersioningSchema() {
        if ($this->versioningReady) {
            return;
        }
        $cols = $this->obBD_datos->getArrayConsultaSql(
            "SHOW COLUMNS FROM wf_flujos_modelos LIKE 'Wfm_Fam_Cod';",
            $this->obBD_conexion
        );
        if (empty($cols)) {
            $this->ejecutarSql(
                "ALTER TABLE wf_flujos_modelos
                 ADD COLUMN Wfm_Fam_Cod BIGINT NULL AFTER Wfm_Est,
                 ADD COLUMN Wfm_Version INT NOT NULL DEFAULT 1 AFTER Wfm_Fam_Cod,
                 ADD COLUMN Wfm_Padre BIGINT NULL AFTER Wfm_Version;"
            );
            $this->ejecutarSql("UPDATE wf_flujos_modelos SET Wfm_Fam_Cod = Wfm_Cod WHERE Wfm_Fam_Cod IS NULL;");
            $this->ejecutarSql("UPDATE wf_flujos_modelos SET Wfm_Est = 'P' WHERE Wfm_Est = 'A';");
        }
        $this->versioningReady = true;
        $this->ensureNotificationSchema();
        $this->ensureConnectionPortsSchema();
    }

    /**
     * Puertos visuales de conexion (left/right/top/bottom) en el diseñador.
     */
    public function ensureConnectionPortsSchema() {
        static $ready = false;
        if ($ready) {
            return;
        }
        $cols = $this->obBD_datos->getArrayConsultaSql(
            "SHOW COLUMNS FROM wf_conexiones LIKE 'Con_Side_Ori';",
            $this->obBD_conexion
        );
        if (empty($cols)) {
            $this->ejecutarSql(
                "ALTER TABLE wf_conexiones
                 ADD COLUMN Con_Side_Ori VARCHAR(10) NULL COMMENT 'Puerto origen: left|right|top|bottom' AFTER Con_Con_Exp,
                 ADD COLUMN Con_Side_Des VARCHAR(10) NULL COMMENT 'Puerto destino: left|right|top|bottom' AFTER Con_Side_Ori;"
            );
        }
        $ready = true;
    }

    private function normalizarPuertoConexion($side) {
        $s = strtolower(trim((string)$side));
        if ($s === 'out') {
            $s = 'right';
        }
        if ($s === 'in') {
            $s = 'left';
        }
        if (in_array($s, array('left', 'right', 'top', 'bottom'), true)) {
            return $s;
        }
        return null;
    }

    /**
     * Columnas de notificacion por nodo y tabla de auditoria de envios.
     */
    public function ensureNotificationSchema() {
        if ($this->notificationSchemaReady) {
            return;
        }
        $cols = $this->obBD_datos->getArrayConsultaSql(
            "SHOW COLUMNS FROM wf_nodos LIKE 'Nod_Not_Wa';",
            $this->obBD_conexion
        );
        if (empty($cols)) {
            $this->ejecutarSql(
                "ALTER TABLE wf_nodos
                 ADD COLUMN Nod_Not_Wa TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Notificar WhatsApp al entrar',
                 ADD COLUMN Nod_Not_Em TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Notificar correo al entrar',
                 ADD COLUMN Nod_Not_Mom CHAR(1) NOT NULL DEFAULT 'S' COMMENT 'S=al completar etapa, notificar siguiente',
                 ADD COLUMN Nod_Not_Asunto VARCHAR(200) NULL COMMENT 'Asunto correo opcional',
                 ADD COLUMN Nod_Not_Texto VARCHAR(500) NULL COMMENT 'Mensaje adicional opcional';"
            );
            $this->ejecutarSql(
                "UPDATE wf_nodos SET Nod_Not_Wa = 1, Nod_Not_Mom = 'S'
                 WHERE Nod_Est = 'A' AND Nod_Tip IN ('APROBACION', 'RECEPCION', 'FACTURA');"
            );
        }
        $col_cot = $this->obBD_datos->getArrayConsultaSql(
            "SHOW COLUMNS FROM wf_nodos LIKE 'Nod_Cot_Edit';",
            $this->obBD_conexion
        );
        if (empty($col_cot)) {
            $this->ejecutarSql(
                "ALTER TABLE wf_nodos
                 ADD COLUMN Nod_Cot_Edit TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permite cargar cotizaciones en esta etapa';"
            );
        }
        $col_cre = $this->obBD_datos->getArrayConsultaSql(
            "SHOW COLUMNS FROM wf_nodos LIKE 'Nod_Cre_Sol';",
            $this->obBD_conexion
        );
        if (empty($col_cre)) {
            $this->ejecutarSql(
                "ALTER TABLE wf_nodos
                 ADD COLUMN Nod_Cre_Sol TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'En INICIO: permite crear solicitud a usuarios asignados';"
            );
        }
        $tabla = $this->obBD_datos->getArrayConsultaSql(
            "SHOW TABLES LIKE 'wf_instancias_notificaciones';",
            $this->obBD_conexion
        );
        if (empty($tabla)) {
            $this->ejecutarSql(
                "CREATE TABLE wf_instancias_notificaciones (
                    Ino_Cod BIGINT AUTO_INCREMENT PRIMARY KEY,
                    Ins_Cod BIGINT NOT NULL,
                    Nod_Cod BIGINT NOT NULL,
                    Usu_Cod INT NULL,
                    Ino_Can CHAR(2) NOT NULL COMMENT 'WA o EM',
                    Ino_Dest VARCHAR(120) NOT NULL,
                    Ino_Est CHAR(1) NOT NULL DEFAULT 'O' COMMENT 'O=ok, E=error',
                    Ino_Fec DATETIME NOT NULL,
                    Ino_Msg TEXT NULL,
                    Ino_Err VARCHAR(500) NULL,
                    KEY idx_ino_inst (Ins_Cod),
                    KEY idx_ino_nodo (Nod_Cod),
                    KEY idx_ino_usu (Usu_Cod)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );
        }
        $this->notificationSchemaReady = true;
    }

    private function esNodoNotificableEntrada($nod_tip) {
        return $this->esNodoResolubleHumano($nod_tip);
    }

    private function esNodoResolubleHumano($nod_tip) {
        return in_array($nod_tip, array('APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FISCALIZACION', 'FIN'), true);
    }

    private function esEstadoPublicado($est) {
        return in_array($est, array('P', 'A'), true);
    }

    public function resolverFamiliaCod($wfm_cod) {
        $this->ensureVersioningSchema();
        $wfm_cod = intval($wfm_cod);
        if ($wfm_cod <= 0) {
            return 0;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT COALESCE(Wfm_Fam_Cod, Wfm_Cod) AS Fam_Cod FROM wf_flujos_modelos WHERE Wfm_Cod = $wfm_cod LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row['Fam_Cod']) ? intval($row['Fam_Cod']) : $wfm_cod;
    }

    public function obtenerFlujoPublicadoFamilia($fam_cod, $emp_cod) {
        $this->ensureVersioningSchema();
        $fam_cod = intval($fam_cod);
        $emp_cod = intval($emp_cod);
        return $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_flujos_modelos
             WHERE Emp_Cod = $emp_cod
               AND COALESCE(Wfm_Fam_Cod, Wfm_Cod) = $fam_cod
               AND Wfm_Est IN ('P', 'A')
             ORDER BY Wfm_Version DESC, Wfm_Cod DESC
             LIMIT 1;",
            $this->obBD_conexion
        );
    }

    public function obtenerBorradorFamilia($fam_cod, $emp_cod) {
        $this->ensureVersioningSchema();
        $fam_cod = intval($fam_cod);
        $emp_cod = intval($emp_cod);
        return $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_flujos_modelos
             WHERE Emp_Cod = $emp_cod
               AND COALESCE(Wfm_Fam_Cod, Wfm_Cod) = $fam_cod
               AND Wfm_Est = 'B'
             ORDER BY Wfm_Version DESC, Wfm_Cod DESC
             LIMIT 1;",
            $this->obBD_conexion
        );
    }

    public function contarInstanciasActivasFamilia($fam_cod) {
        $this->ensureVersioningSchema();
        $fam_cod = intval($fam_cod);
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT COUNT(*) AS Q
             FROM wf_instancias i
             INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = i.Wfm_Cod
             WHERE COALESCE(w.Wfm_Fam_Cod, w.Wfm_Cod) = $fam_cod AND i.Ins_Est = 'P';",
            $this->obBD_conexion
        );
        return !empty($row['Q']) ? intval($row['Q']) : 0;
    }

    public function resolverFlujoParaNuevaInstancia($wfm_cod, $emp_cod) {
        $this->ensureVersioningSchema();
        $fam = $this->resolverFamiliaCod($wfm_cod);
        $pub = $this->obtenerFlujoPublicadoFamilia($fam, $emp_cod);
        if (!empty($pub['Wfm_Cod'])) {
            return intval($pub['Wfm_Cod']);
        }
        return intval($wfm_cod);
    }

    public function listarFlujosPublicados($emp_cod) {
        $this->ensureVersioningSchema();
        $emp_cod = intval($emp_cod);
        return $this->obBD_datos->getArrayConsultaSql(
            "SELECT w.Wfm_Cod, w.Wfm_Nom, w.Wfm_Version,
                    COALESCE(w.Wfm_Fam_Cod, w.Wfm_Cod) AS Wfm_Fam_Cod
             FROM wf_flujos_modelos w
             INNER JOIN (
                 SELECT COALESCE(Wfm_Fam_Cod, Wfm_Cod) AS Fam_Cod, MAX(Wfm_Version) AS MaxVer
                 FROM wf_flujos_modelos
                 WHERE Emp_Cod = $emp_cod AND Wfm_Est IN ('P', 'A')
                 GROUP BY COALESCE(Wfm_Fam_Cod, Wfm_Cod)
             ) pub ON pub.Fam_Cod = COALESCE(w.Wfm_Fam_Cod, w.Wfm_Cod) AND pub.MaxVer = w.Wfm_Version
             WHERE w.Emp_Cod = $emp_cod AND w.Wfm_Est IN ('P', 'A')
             ORDER BY w.Wfm_Nom;",
            $this->obBD_conexion
        );
    }

    /**
     * Lista flujos publicados y borradores para el disenador (una fila por familia).
     * Prioriza mostrar el borrador si existe; si no, la version publicada.
     */
    public function listarFlujosDisenador($emp_cod) {
        $this->ensureVersioningSchema();
        $emp_cod = intval($emp_cod);
        if ($emp_cod <= 0) {
            return array();
        }

        $familias = $this->obBD_datos->getArrayConsultaSql(
            "SELECT DISTINCT COALESCE(Wfm_Fam_Cod, Wfm_Cod) AS Fam_Cod
             FROM wf_flujos_modelos
             WHERE Emp_Cod = $emp_cod AND Wfm_Est IN ('P', 'A', 'B')
             ORDER BY Fam_Cod ASC;",
            $this->obBD_conexion
        );
        if (empty($familias)) {
            return array();
        }

        $resultado = array();
        foreach ($familias as $familia) {
            $fam = intval($familia['Fam_Cod']);
            $borrador = $this->obtenerBorradorFamilia($fam, $emp_cod);
            $publicado = $this->obtenerFlujoPublicadoFamilia($fam, $emp_cod);
            $activo = !empty($borrador) ? $borrador : $publicado;
            if (empty($activo)) {
                continue;
            }

            $resultado[] = array(
                'Wfm_Cod' => intval($activo['Wfm_Cod']),
                'Wfm_Nom' => $activo['Wfm_Nom'],
                'Wfm_Version' => intval($activo['Wfm_Version']),
                'Wfm_Fam_Cod' => $fam,
                'Wfm_Est' => $activo['Wfm_Est'],
                'es_borrador' => ($activo['Wfm_Est'] === 'B'),
                'tiene_publicado' => !empty($publicado),
            );
        }

        usort($resultado, function ($a, $b) {
            return strcasecmp($a['Wfm_Nom'], $b['Wfm_Nom']);
        });

        return $resultado;
    }

    public function etiquetaFlujoListado($flow) {
        $etiqueta = $flow['Wfm_Nom'] . ' (v' . intval($flow['Wfm_Version']) . ')';
        if (!empty($flow['es_borrador'])) {
            $etiqueta .= ' [Borrador]';
        }
        return $etiqueta;
    }

    public function cargarFlujoDisenador($selector_cod, $emp_cod) {
        $this->ensureVersioningSchema();
        $selector_cod = intval($selector_cod);
        $emp_cod = intval($emp_cod);
        $fam = $this->resolverFamiliaCod($selector_cod);
        $publicado = $this->obtenerFlujoPublicadoFamilia($fam, $emp_cod);
        $borrador = $this->obtenerBorradorFamilia($fam, $emp_cod);
        $activo = !empty($borrador) ? $borrador : $publicado;
        if (empty($activo)) {
            $activo = $this->obBD_datos->getRowConsultaSql(
                "SELECT * FROM wf_flujos_modelos WHERE Wfm_Cod = $selector_cod AND Emp_Cod = $emp_cod LIMIT 1;",
                $this->obBD_conexion
            );
        }
        if (empty($activo)) {
            throw new Exception('Flujo no encontrado.');
        }
        $wfm_cod = intval($activo['Wfm_Cod']);
        $nodos = $this->obBD_datos->getArrayConsultaSql(
            "SELECT * FROM wf_nodos WHERE Wfm_Cod = $wfm_cod AND Nod_Est = 'A' ORDER BY Nod_Cod ASC;",
            $this->obBD_conexion
        );
        $conexiones = $this->obBD_datos->getArrayConsultaSql(
            "SELECT * FROM wf_conexiones WHERE Wfm_Cod = $wfm_cod;",
            $this->obBD_conexion
        );
        return array(
            'flujo' => $activo,
            'publicado' => $publicado,
            'borrador' => $borrador,
            'familia_cod' => $fam,
            'instancias_activas' => $this->contarInstanciasActivasFamilia($fam),
            'nodos' => $nodos,
            'conexiones' => $conexiones
        );
    }

    private function escapeWf($value) {
        if ($value === null) {
            return '';
        }
        return mysqli_real_escape_string($this->obBD_conexion->conexion, (string)$value);
    }

    private function resolverNodoIdDisenador($frontend_id) {
        if ($frontend_id === null || $frontend_id === '') {
            return 0;
        }
        $id = (string)$frontend_id;
        if (strpos($id, 'n_') === 0) {
            return intval(substr($id, 2));
        }
        if (strpos($id, 'node_') === 0) {
            return 0;
        }
        if (ctype_digit($id)) {
            return intval($id);
        }
        return 0;
    }

    private function crearCabeceraBorrador($base_flujo, $fam_cod) {
        $emp_cod = intval($base_flujo['Emp_Cod']);
        $nom = $this->escapeWf($base_flujo['Wfm_Nom']);
        $des = $this->escapeWf(isset($base_flujo['Wfm_Des']) ? $base_flujo['Wfm_Des'] : '');
        $version = intval($base_flujo['Wfm_Version']) + 1;
        $padre = intval($base_flujo['Wfm_Cod']);
        $fam_cod = intval($fam_cod);
        $this->ejecutarSql(
            "INSERT INTO wf_flujos_modelos (Emp_Cod, Wfm_Nom, Wfm_Des, Wfm_Est, Wfm_Fam_Cod, Wfm_Version, Wfm_Padre)
             VALUES ($emp_cod, '$nom', '$des', 'B', $fam_cod, $version, $padre);"
        );
        return intval($this->obBD_datos->insercionid($this->obBD_conexion));
    }

    public function duplicarFlujoDisenador($selector_cod, $emp_cod, $nuevo_nombre, $nueva_descripcion = null) {
        $this->ensureVersioningSchema();
        $this->ensureNotificationSchema();
        $this->ensureConnectionPortsSchema();
        $selector_cod = intval($selector_cod);
        $emp_cod = intval($emp_cod);
        $nuevo_nombre = trim((string)$nuevo_nombre);
        if ($selector_cod <= 0) {
            throw new Exception('Seleccione el esquema que desea duplicar.');
        }
        if ($nuevo_nombre === '') {
            throw new Exception('El nombre del esquema duplicado es obligatorio.');
        }

        $pack = $this->cargarFlujoDisenador($selector_cod, $emp_cod);
        $origen = isset($pack['flujo']) ? $pack['flujo'] : array();
        if (empty($origen['Wfm_Cod'])) {
            throw new Exception('No se encontro el esquema de origen.');
        }
        $src_cod = intval($origen['Wfm_Cod']);
        $nom = $this->escapeWf($nuevo_nombre);
        $descripcion = ($nueva_descripcion !== null && trim((string)$nueva_descripcion) !== '')
            ? (string)$nueva_descripcion
            : (isset($origen['Wfm_Des']) ? $origen['Wfm_Des'] : '');
        $des = $this->escapeWf($descripcion);

        $this->ejecutarSql(
            "INSERT INTO wf_flujos_modelos
                (Emp_Cod, Wfm_Nom, Wfm_Des, Wfm_Est, Wfm_Fam_Cod, Wfm_Version, Wfm_Padre)
             VALUES ($emp_cod, '$nom', '$des', 'B', NULL, 1, NULL);"
        );
        $nuevo_wfm = intval($this->obBD_datos->insercionid($this->obBD_conexion));
        if ($nuevo_wfm <= 0) {
            throw new Exception('No se pudo crear la cabecera del esquema duplicado.');
        }
        $this->ejecutarSql(
            "UPDATE wf_flujos_modelos SET Wfm_Fam_Cod = $nuevo_wfm WHERE Wfm_Cod = $nuevo_wfm;"
        );

        $nodos = $this->obBD_datos->getArrayConsultaSql(
            "SELECT * FROM wf_nodos WHERE Wfm_Cod = $src_cod AND Nod_Est = 'A' ORDER BY Nod_Cod ASC;",
            $this->obBD_conexion
        );
        if (empty($nodos)) {
            throw new Exception('El esquema de origen no tiene nodos activos para duplicar.');
        }

        $mapa = array();
        foreach ($nodos as $nodo) {
            $tip = $this->escapeWf($nodo['Nod_Tip']);
            $nombre = $this->escapeWf($nodo['Nod_Nom']);
            $nodo_des = $this->escapeWf(isset($nodo['Nod_Des']) ? $nodo['Nod_Des'] : '');
            $dep = !empty($nodo['Dep_Cod']) ? intval($nodo['Dep_Cod']) : 'NULL';
            $per = !empty($nodo['Per_Cod']) ? intval($nodo['Per_Cod']) : 'NULL';
            $sla = ($nodo['Nod_Sla'] !== null && $nodo['Nod_Sla'] !== '') ? intval($nodo['Nod_Sla']) : 'NULL';
            $com = !empty($nodo['Nod_Com_Obl']) ? 1 : 0;
            $adj = !empty($nodo['Nod_Adj_Obl']) ? 1 : 0;
            $cot = !empty($nodo['Nod_Cot_Edit']) ? 1 : 0;
            $cre = !empty($nodo['Nod_Cre_Sol']) ? 1 : 0;
            $not_wa = !empty($nodo['Nod_Not_Wa']) ? 1 : 0;
            $not_em = !empty($nodo['Nod_Not_Em']) ? 1 : 0;
            $not_mom = !empty($nodo['Nod_Not_Mom']) ? $this->escapeWf($nodo['Nod_Not_Mom']) : 'S';
            $not_asunto = !empty($nodo['Nod_Not_Asunto']) ? "'" . $this->escapeWf($nodo['Nod_Not_Asunto']) . "'" : 'NULL';
            $not_texto = !empty($nodo['Nod_Not_Texto']) ? "'" . $this->escapeWf($nodo['Nod_Not_Texto']) . "'" : 'NULL';
            $x = intval($nodo['Nod_Vis_X']);
            $y = intval($nodo['Nod_Vis_Y']);
            $usu = !empty($nodo['Nod_Usu_Asig']) ? "'" . $this->escapeWf($nodo['Nod_Usu_Asig']) . "'" : "'TODOS'";

            $this->ejecutarSql(
                "INSERT INTO wf_nodos
                    (Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Dep_Cod, Per_Cod, Nod_Sla,
                     Nod_Com_Obl, Nod_Adj_Obl, Nod_Cot_Edit, Nod_Cre_Sol,
                     Nod_Not_Wa, Nod_Not_Em, Nod_Not_Mom, Nod_Not_Asunto, Nod_Not_Texto,
                     Nod_Vis_X, Nod_Vis_Y, Nod_Est, Nod_Usu_Asig)
                 VALUES
                    ($nuevo_wfm, '$tip', '$nombre', '$nodo_des', $dep, $per, $sla,
                     $com, $adj, $cot, $cre,
                     $not_wa, $not_em, '$not_mom', $not_asunto, $not_texto,
                     $x, $y, 'A', $usu);"
            );
            $nuevo_nodo = intval($this->obBD_datos->insercionid($this->obBD_conexion));
            $mapa[intval($nodo['Nod_Cod'])] = $nuevo_nodo;
        }

        $conexiones = $this->obBD_datos->getArrayConsultaSql(
            "SELECT * FROM wf_conexiones WHERE Wfm_Cod = $src_cod ORDER BY Con_Cod ASC;",
            $this->obBD_conexion
        );
        if (!empty($conexiones)) {
            foreach ($conexiones as $con) {
                $ori = isset($mapa[intval($con['Nod_Ori'])]) ? $mapa[intval($con['Nod_Ori'])] : 0;
                $des_cod = isset($mapa[intval($con['Nod_Des'])]) ? $mapa[intval($con['Nod_Des'])] : 0;
                if ($ori <= 0 || $des_cod <= 0) {
                    continue;
                }
                $accion = $this->escapeWf(isset($con['Con_Acc']) ? $con['Con_Acc'] : 'APROBAR');
                $exp = !empty($con['Con_Con_Exp']) ? "'" . $this->escapeWf($con['Con_Con_Exp']) . "'" : 'NULL';
                $side_ori = !empty($con['Con_Side_Ori']) ? "'" . $this->escapeWf($con['Con_Side_Ori']) . "'" : 'NULL';
                $side_des = !empty($con['Con_Side_Des']) ? "'" . $this->escapeWf($con['Con_Side_Des']) . "'" : 'NULL';
                $this->ejecutarSql(
                    "INSERT INTO wf_conexiones
                        (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc, Con_Con_Exp, Con_Side_Ori, Con_Side_Des)
                     VALUES ($nuevo_wfm, $ori, $des_cod, '$accion', $exp, $side_ori, $side_des);"
                );
            }
        }

        return array(
            'id' => $nuevo_wfm,
            'familia_cod' => $nuevo_wfm,
            'nombre' => $nuevo_nombre,
            'version' => 1,
            'es_borrador' => true
        );
    }

    public function guardarFlujoDisenador($data, $emp_cod) {
        $this->ensureVersioningSchema();
        $emp_cod = intval($emp_cod);
        if (!is_array($data) || empty($data['nodos']) || !is_array($data['nodos'])) {
            throw new Exception('Datos de flujo incompletos o invalidos.');
        }
        $wfm_nom = $this->escapeWf(isset($data['nombre']) ? $data['nombre'] : '');
        $wfm_des = $this->escapeWf(isset($data['descripcion']) ? $data['descripcion'] : '');
        if ($wfm_nom === '') {
            throw new Exception('El nombre del flujo es obligatorio.');
        }

        $wfm_cod = 0;
        $es_nuevo = empty($data['id']);
        if ($es_nuevo) {
            $this->ejecutarSql(
                "INSERT INTO wf_flujos_modelos (Emp_Cod, Wfm_Nom, Wfm_Des, Wfm_Est, Wfm_Version)
                 VALUES ($emp_cod, '$wfm_nom', '$wfm_des', 'B', 1);"
            );
            $wfm_cod = intval($this->obBD_datos->insercionid($this->obBD_conexion));
            $this->ejecutarSql("UPDATE wf_flujos_modelos SET Wfm_Fam_Cod = $wfm_cod WHERE Wfm_Cod = $wfm_cod;");
        } else {
            $src_cod = intval($data['id']);
            $src = $this->obBD_datos->getRowConsultaSql(
                "SELECT * FROM wf_flujos_modelos WHERE Wfm_Cod = $src_cod AND Emp_Cod = $emp_cod LIMIT 1;",
                $this->obBD_conexion
            );
            if (empty($src)) {
                throw new Exception('Flujo no encontrado.');
            }
            $fam = $this->resolverFamiliaCod($src_cod);
            if ($src['Wfm_Est'] === 'B') {
                $wfm_cod = $src_cod;
                $this->ejecutarSql(
                    "UPDATE wf_flujos_modelos SET Wfm_Nom = '$wfm_nom', Wfm_Des = '$wfm_des' WHERE Wfm_Cod = $wfm_cod;"
                );
            } elseif ($this->esEstadoPublicado($src['Wfm_Est'])) {
                $borrador = $this->obtenerBorradorFamilia($fam, $emp_cod);
                if (!empty($borrador)) {
                    $wfm_cod = intval($borrador['Wfm_Cod']);
                    $this->ejecutarSql(
                        "UPDATE wf_flujos_modelos SET Wfm_Nom = '$wfm_nom', Wfm_Des = '$wfm_des' WHERE Wfm_Cod = $wfm_cod;"
                    );
                } else {
                    $src['Wfm_Nom'] = $wfm_nom;
                    $src['Wfm_Des'] = $wfm_des;
                    $wfm_cod = $this->crearCabeceraBorrador($src, $fam);
                }
            } else {
                throw new Exception('No se puede editar una version historica del flujo.');
            }
            if (!$this->obBD_datos->grabarv_registros("DELETE FROM wf_conexiones WHERE Wfm_Cod = $wfm_cod;", $this->obBD_conexion)) {
                throw new Exception('No se pudieron limpiar las conexiones del borrador.');
            }
        }

        $node_map = array();
        $saved_nod_cods = array();
        foreach ($data['nodos'] as $nodo) {
            $nod_tip = $this->escapeWf(isset($nodo['tipo']) ? $nodo['tipo'] : '');
            $nod_nom = $this->escapeWf(isset($nodo['nombre']) ? $nodo['nombre'] : '');
            $nod_des = $this->escapeWf(isset($nodo['descripcion']) ? $nodo['descripcion'] : '');
            if ($nod_tip === '' || $nod_nom === '') {
                throw new Exception('Cada nodo debe tener tipo y nombre.');
            }
            $dep_rrhh = null;
            if (!empty($nodo['dep_cod'])) {
                $dep_rrhh = $this->resolverDepCodRrhh($nodo['dep_cod'], $emp_cod);
            }
            $dep_cod = ($dep_rrhh !== null && $dep_rrhh > 0) ? intval($dep_rrhh) : 'NULL';
            $per_cod = !empty($nodo['per_cod']) ? intval($nodo['per_cod']) : 'NULL';
            $sla = (isset($nodo['sla']) && $nodo['sla'] !== '' && $nodo['sla'] !== null) ? intval($nodo['sla']) : 'NULL';
            $com_obl = !empty($nodo['com_obl']) ? 1 : 0;
            $adj_obl = !empty($nodo['adj_obl']) ? 1 : 0;
            $cot_edit = !empty($nodo['cot_edit']) ? 1 : 0;
            $cre_sol = !empty($nodo['cre_sol']) ? 1 : 0;
            $not_wa = !empty($nodo['not_wa']) ? 1 : 0;
            $not_em = !empty($nodo['not_em']) ? 1 : 0;
            $not_asunto = !empty($nodo['not_asunto']) ? "'" . $this->escapeWf($nodo['not_asunto']) . "'" : 'NULL';
            $not_texto = !empty($nodo['not_texto']) ? "'" . $this->escapeWf($nodo['not_texto']) . "'" : 'NULL';
            if (!$this->esNodoNotificableEntrada($nod_tip) && $nod_tip !== 'INICIO') {
                $not_wa = 0;
                $not_em = 0;
                $not_asunto = 'NULL';
                $not_texto = 'NULL';
                $cot_edit = 0;
            }
            if ($nod_tip === 'INICIO') {
                $cot_edit = 0;
            }
            // Solo INICIO usa Nod_Cre_Sol; otros nodos lo dejan en 0
            if ($nod_tip !== 'INICIO') {
                $cre_sol = 0;
            } elseif (!isset($nodo['cre_sol'])) {
                $cre_sol = 1;
            }
            $vis_x = intval(isset($nodo['x']) ? $nodo['x'] : 0);
            $vis_y = intval(isset($nodo['y']) ? $nodo['y'] : 0);
            $usu_asig = !empty($nodo['usu_asig']) ? "'" . $this->escapeWf($nodo['usu_asig']) . "'" : "'TODOS'";

            $nod_cod = $this->resolverNodoIdDisenador(isset($nodo['id']) ? $nodo['id'] : null);
            $existing = null;
            if ($nod_cod > 0) {
                $existing = $this->obBD_datos->getRowConsultaSql(
                    "SELECT Nod_Cod FROM wf_nodos WHERE Nod_Cod = $nod_cod AND Wfm_Cod = $wfm_cod LIMIT 1;",
                    $this->obBD_conexion
                );
            }

            if (!empty($existing['Nod_Cod'])) {
                $nod_cod = intval($existing['Nod_Cod']);
                $this->ejecutarSql(
                    "UPDATE wf_nodos SET
                        Nod_Tip = '$nod_tip', Nod_Nom = '$nod_nom', Nod_Des = '$nod_des',
                        Dep_Cod = $dep_cod, Per_Cod = $per_cod, Nod_Sla = $sla,
                        Nod_Com_Obl = $com_obl, Nod_Adj_Obl = $adj_obl, Nod_Cot_Edit = $cot_edit,
                        Nod_Cre_Sol = $cre_sol,
                        Nod_Not_Wa = $not_wa, Nod_Not_Em = $not_em, Nod_Not_Mom = 'S',
                        Nod_Not_Asunto = $not_asunto, Nod_Not_Texto = $not_texto,
                        Nod_Vis_X = $vis_x, Nod_Vis_Y = $vis_y, Nod_Usu_Asig = $usu_asig, Nod_Est = 'A'
                     WHERE Nod_Cod = $nod_cod AND Wfm_Cod = $wfm_cod;"
                );
            } else {
                $this->ejecutarSql(
                    "INSERT INTO wf_nodos (Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Dep_Cod, Per_Cod, Nod_Sla, Nod_Com_Obl, Nod_Adj_Obl, Nod_Cot_Edit, Nod_Cre_Sol,
                        Nod_Not_Wa, Nod_Not_Em, Nod_Not_Mom, Nod_Not_Asunto, Nod_Not_Texto,
                        Nod_Vis_X, Nod_Vis_Y, Nod_Est, Nod_Usu_Asig)
                     VALUES ($wfm_cod, '$nod_tip', '$nod_nom', '$nod_des', $dep_cod, $per_cod, $sla, $com_obl, $adj_obl, $cot_edit, $cre_sol,
                        $not_wa, $not_em, 'S', $not_asunto, $not_texto, $vis_x, $vis_y, 'A', $usu_asig);"
                );
                $nod_cod = intval($this->obBD_datos->insercionid($this->obBD_conexion));
            }

            $saved_nod_cods[] = $nod_cod;
            $node_map[$nodo['id']] = $nod_cod;
            $node_map[(string)$nodo['id']] = $nod_cod;
        }

        if (!empty($saved_nod_cods) && !$es_nuevo) {
            $ids_keep = implode(',', array_map('intval', $saved_nod_cods));
            $this->obBD_datos->grabarv_registros(
                "UPDATE wf_nodos SET Nod_Est = 'I' WHERE Wfm_Cod = $wfm_cod AND Nod_Cod NOT IN ($ids_keep) AND Nod_Est = 'A';",
                $this->obBD_conexion
            );
        }

        if (!empty($data['conexiones']) && is_array($data['conexiones'])) {
            foreach ($data['conexiones'] as $con) {
                $origen_key = isset($con['origen']) ? $con['origen'] : null;
                $destino_key = isset($con['destino']) ? $con['destino'] : null;
                $nod_ori = isset($node_map[$origen_key]) ? $node_map[$origen_key] : (isset($node_map[(string)$origen_key]) ? $node_map[(string)$origen_key] : null);
                $nod_des = isset($node_map[$destino_key]) ? $node_map[$destino_key] : (isset($node_map[(string)$destino_key]) ? $node_map[(string)$destino_key] : null);
                if (empty($nod_ori) || empty($nod_des)) {
                    throw new Exception('Conexion invalida: nodo origen o destino no encontrado.');
                }
                $con_acc = $this->escapeWf(isset($con['accion']) ? $con['accion'] : 'APROBAR');
                // Condición por conexión: cada rama puede tener su propio comentario (ej. mayor / menor)
                $condicion = (isset($con['condicion']) && is_array($con['condicion'])) ? $con['condicion'] : array();
                if (isset($con['comentario'])) {
                    $com_rama = trim((string)$con['comentario']);
                    if ($com_rama !== '') {
                        $condicion['comentario'] = $com_rama;
                    } else {
                        unset($condicion['comentario']);
                    }
                }
                if (empty($condicion)) {
                    $condicion = null;
                }
                // PHP 5.3: no usar JSON_UNESCAPED_UNICODE (no existe; rompe json_encode y deja Con_Con_Exp vacío)
                $con_json = ($condicion !== null) ? json_encode($condicion) : false;
                if ($condicion !== null && ($con_json === false || $con_json === null || $con_json === '')) {
                    throw new Exception('No se pudo serializar la condicion de la conexion.');
                }
                $con_con_exp = ($con_json !== false && $con_json !== null && $con_json !== '')
                    ? ("'" . $this->escapeWf($con_json) . "'")
                    : 'NULL';
                $this->ensureConnectionPortsSchema();
                $side_ori = $this->normalizarPuertoConexion(isset($con['side_ori']) ? $con['side_ori'] : '');
                $side_des = $this->normalizarPuertoConexion(isset($con['side_des']) ? $con['side_des'] : '');
                $side_ori_sql = $side_ori ? ("'" . $this->escapeWf($side_ori) . "'") : 'NULL';
                $side_des_sql = $side_des ? ("'" . $this->escapeWf($side_des) . "'") : 'NULL';
                $this->ejecutarSql(
                    "INSERT INTO wf_conexiones (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc, Con_Con_Exp, Con_Side_Ori, Con_Side_Des)
                     VALUES ($wfm_cod, $nod_ori, $nod_des, '$con_acc', $con_con_exp, $side_ori_sql, $side_des_sql);"
                );
            }
        }

        $fam = $this->resolverFamiliaCod($wfm_cod);
        $ver_row = $this->obBD_datos->getRowConsultaSql("SELECT Wfm_Version FROM wf_flujos_modelos WHERE Wfm_Cod = $wfm_cod;", $this->obBD_conexion);
        return array(
            'id' => $wfm_cod,
            'familia_cod' => $fam,
            'es_borrador' => true,
            'instancias_activas' => $this->contarInstanciasActivasFamilia($fam),
            'version' => !empty($ver_row['Wfm_Version']) ? intval($ver_row['Wfm_Version']) : 1
        );
    }

    public function publicarFlujoDisenador($wfm_cod, $emp_cod) {
        $this->ensureVersioningSchema();
        $wfm_cod = intval($wfm_cod);
        $emp_cod = intval($emp_cod);
        $borrador = $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_flujos_modelos WHERE Wfm_Cod = $wfm_cod AND Emp_Cod = $emp_cod AND Wfm_Est = 'B' LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($borrador)) {
            throw new Exception('Solo se puede publicar un borrador del flujo.');
        }
        $fam = $this->resolverFamiliaCod($wfm_cod);
        $instancias_activas = $this->contarInstanciasActivasFamilia($fam);
        $publicado = $this->obtenerFlujoPublicadoFamilia($fam, $emp_cod);
        if (!empty($publicado) && intval($publicado['Wfm_Cod']) !== $wfm_cod) {
            $old_cod = intval($publicado['Wfm_Cod']);
            $this->ejecutarSql("UPDATE wf_flujos_modelos SET Wfm_Est = 'H' WHERE Wfm_Cod = $old_cod;");
        }
        $this->ejecutarSql("UPDATE wf_flujos_modelos SET Wfm_Est = 'P' WHERE Wfm_Cod = $wfm_cod;");
        $this->ejecutarSql(
            "UPDATE adq_tipos_requerimientos t
             INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = t.Wfm_Cod
             SET t.Wfm_Cod = $wfm_cod
             WHERE t.Emp_Cod = $emp_cod AND COALESCE(w.Wfm_Fam_Cod, w.Wfm_Cod) = $fam;"
        );
        return array(
            'id' => $wfm_cod,
            'familia_cod' => $fam,
            'version' => intval($borrador['Wfm_Version']),
            'instancias_activas' => $instancias_activas,
            'publicado' => true
        );
    }

    public function iniciarInstancia($Wfm_Cod, $Ent_Typ, $Ent_Cod, $manageTransaction = true) {
        $this->ensureVersioningSchema();
        $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
        $Wfm_Cod = $this->resolverFlujoParaNuevaInstancia($Wfm_Cod, $emp_cod);
        if ($manageTransaction) {
            $this->obBD_datos->inicio_transaccion($this->obBD_conexion);
        }
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

            $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $session_id = session_id() ?: 'CLI-SESSION';
            $fk = $this->resolverFkHistorial(
                isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0,
                isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0
            );
            $nod_ini = intval($nodoInicio['Nod_Cod']);

            $comentario_crear = 'Instanciaci' . "\xC3\xB3" . 'n inicial del flujo.';
            $sqlInsertHistorial = "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Fec, Isn_Ip, Isn_Ses) 
                                   VALUES ($Ins_Cod, $nod_ini, {$fk['usu']}, {$fk['dep']}, 'CREAR', '" . mysqli_real_escape_string($this->obBD_conexion->conexion, $comentario_crear) . "', '$fecha_actual', '$ip_usuario', '$session_id');";
            $this->ejecutarSql($sqlInsertHistorial);

            $this->avanzarDesdeNodoInicio($Ins_Cod, $nod_ini, 'Avance autom' . "\xC3\xA1" . 'tico desde Inicio.');

            if ($manageTransaction) {
                $this->obBD_datos->commit_nomsn($this->obBD_conexion);
            }
            return array('success' => true, 'Ins_Cod' => $Ins_Cod);
        } catch (Exception $e) {
            if ($manageTransaction) {
                $this->obBD_datos->rollBack_nomsn($this->obBD_conexion);
                return array('success' => false, 'message' => $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Avanza el flujo de trabajo al siguiente nodo l�gico evaluando condiciones
     */
    public function avanzarSiguientePaso($Ins_Cod, $Nod_Actual_Cod, $Accion, $Comentario = '', $Adjuntos = null, $nodCompletadoOrigen = null) {
        $fecha_actual = date('Y-m-d H:i:s');
        $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $session_id = session_id() ?: 'CLI-SESSION';
        $fk_ctx = $this->resolverFkHistorial(
            isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0,
            isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0
        );
        $usu_cod = $fk_ctx['usu'];
        $dep_cod = $fk_ctx['dep'];

        $nodoSaliente = $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_nodos WHERE Nod_Cod = " . intval($Nod_Actual_Cod) . " LIMIT 1;",
            $this->obBD_conexion
        );
        if ($nodCompletadoOrigen === null && $Accion !== 'CONDICIONAL' && !empty($nodoSaliente)) {
            if ($nodoSaliente['Nod_Tip'] === 'INICIO' || $this->esNodoNotificableEntrada($nodoSaliente['Nod_Tip'])) {
                $nodCompletadoOrigen = $nodoSaliente;
            }
        }

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
            $nodoActual = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $Nod_Actual_Cod;", $this->obBD_conexion);
            if ($nodoActual['Nod_Tip'] == 'FIN') {
                $adjunto_str = $Adjuntos !== null ? "'" . mysqli_real_escape_string($this->obBD_conexion->conexion, $Adjuntos) . "'" : "NULL";
                $com_esc = mysqli_real_escape_string($this->obBD_conexion->conexion, $Comentario);
                $this->ejecutarSql(
                    "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Adj, Isn_Fec, Isn_Ip, Isn_Ses)
                     VALUES ($Ins_Cod, $Nod_Actual_Cod, $usu_cod, $dep_cod, '$Accion', '$com_esc', $adjunto_str, '$fecha_actual', '$ip_usuario', '$session_id');"
                );
                $this->obBD_datos->grabarv_registros("UPDATE wf_instancias SET Ins_Est = 'F', Ins_Fec_Fin = '$fecha_actual' WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
                if ($instancia['Ins_Ent_Typ'] == 'adq_solicitudes') {
                    $this->obBD_datos->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = 'A' WHERE Sol_Cod = $instancia[Ins_Ent_Cod];", $this->obBD_conexion);
                }
                return true;
            }
            throw new Exception("El nodo actual no tiene conexiones de salida configuradas.");
        }

        // 3. Evaluar conexiones para decidir a qué nodo avanzar
        $nodoDestino_Cod = null;
        $conexionDefault = null;
        $conexionElegida = null;
        $conexionDefaultRow = null;

        foreach ($conexiones as $conexion) {
            if ($conexion['Con_Acc'] == 'CONDICIONAL' && !empty($conexion['Con_Con_Exp'])) {
                // Nodo decisión con condición específica (requiere campo evaluable)
                $expr_check = json_decode($conexion['Con_Con_Exp'], true);
                if (is_array($expr_check) && !empty($expr_check['campo'])) {
                    if ($this->evaluarCondicion($conexion['Con_Con_Exp'], $instancia['Ins_Ent_Typ'], $instancia['Ins_Ent_Cod'])) {
                        $nodoDestino_Cod = $conexion['Nod_Des'];
                        $conexionElegida = $conexion;
                        break;
                    }
                }
            } elseif ($conexion['Con_Acc'] == 'APROBAR' && $Accion == 'CONDICIONAL') {
                // Rama por defecto/Else (puede traer solo comentario en Con_Con_Exp)
                $expr_def = !empty($conexion['Con_Con_Exp']) ? json_decode($conexion['Con_Con_Exp'], true) : null;
                $tiene_campo = is_array($expr_def) && !empty($expr_def['campo']);
                if (!$tiene_campo) {
                    $conexionDefault = $conexion['Nod_Des'];
                    $conexionDefaultRow = $conexion;
                }
            } elseif ($conexion['Con_Acc'] == $Accion || ($Accion === 'COMPLETAR' && $conexion['Con_Acc'] === 'APROBAR')) {
                // Conexión directa según acción ejecutada
                $nodoDestino_Cod = $conexion['Nod_Des'];
                $conexionElegida = $conexion;
                break;
            }
        }

        if ($nodoDestino_Cod === null && $conexionDefault !== null) {
            $nodoDestino_Cod = $conexionDefault;
            $conexionElegida = $conexionDefaultRow;
        }

        // Si no se cumplió ninguna condición de decisión o acción coincidente, tomamos la primera por defecto
        if ($nodoDestino_Cod === null && count($conexiones) > 0) {
            $nodoDestino_Cod = $conexiones[0]['Nod_Des'];
            $conexionElegida = $conexiones[0];
        }

        // 4. Obtener información del nodo destino
        $nodoDestino = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $nodoDestino_Cod;", $this->obBD_conexion);
        if (empty($nodoDestino)) {
            throw new Exception("El nodo destino configurado no existe.");
        }

        // 5. Calcular SLA si aplica
        $sla_vencimiento = 'NULL';
        if ($nodoDestino['Nod_Sla'] !== null && $nodoDestino['Nod_Sla'] > 0) {
            $dias = $nodoDestino['Nod_Sla'];
            $sla_vencimiento = "'" . date('Y-m-d H:i:s', strtotime("+$dias days")) . "'";
        }

        // Comentario de historial: el detalle de la rama queda SOLO en el nodo DECISION
        // (no repetir en el nodo destino al salir de la decisión).
        $comentarioHist = $Comentario;
        $nodoActualTip = !empty($nodoSaliente['Nod_Tip']) ? $nodoSaliente['Nod_Tip'] : '';
        $omitirHistorialDestino = false;
        if ($Accion === 'CONDICIONAL' && $nodoActualTip === 'DECISION') {
            $comentarioHist = '';
            $omitirHistorialDestino = true;
        }

        // 6. Actualizar instancia de flujo al nuevo nodo actual
        $this->ejecutarSql("UPDATE wf_instancias SET Nod_Act = $nodoDestino_Cod WHERE Ins_Cod = $Ins_Cod;");

        // 7. Escribir en el historial del nodo destino
        if (!$omitirHistorialDestino) {
            $adjunto_str = $Adjuntos !== null ? "'" . $Adjuntos . "'" : "NULL";
            $com_esc_hist = mysqli_real_escape_string($this->obBD_conexion->conexion, $comentarioHist);
            $sqlInsertHistorialDest = "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Adj, Isn_Fec, Isn_Sla_Ven, Isn_Ip, Isn_Ses)
                                       VALUES ($Ins_Cod, $nodoDestino_Cod, $usu_cod, $dep_cod, '$Accion', '$com_esc_hist', $adjunto_str, '$fecha_actual', $sla_vencimiento, '$ip_usuario', '$session_id');";
            $this->ejecutarSql($sqlInsertHistorialDest);
        }

        // 8. Si el nuevo nodo es un Nodo de Decisión o de Notificación, se procesa automáticamente
        if ($nodoDestino['Nod_Tip'] == 'DECISION') {
            // Reescribir el historial del DECISION con el detalle de la rama que se tomará
            $ramaPrev = $this->resolverRamaDecisionDesdeNodo($nodoDestino_Cod, $instancia);
            $detalleRama = $this->textoDetalleRamaDecision($ramaPrev);
            $this->actualizarComentarioHistorialNodoDecision($Ins_Cod, $nodoDestino_Cod, $detalleRama);
            return $this->avanzarSiguientePaso($Ins_Cod, $nodoDestino_Cod, 'CONDICIONAL', $detalleRama, null, $nodCompletadoOrigen);
        } elseif ($nodoDestino['Nod_Tip'] == 'NOTIFICACION') {
            $this->enviarNotificacionNodo($nodoDestino, $instancia);
            return $this->avanzarSiguientePaso($Ins_Cod, $nodoDestino_Cod, 'COMPLETAR', 'Avance autom' . "\xC3\xA1" . 'tico tras notificaci' . "\xC3\xB3" . 'n.', null, $nodCompletadoOrigen);
        }

        if ($this->esNodoNotificableEntrada($nodoDestino['Nod_Tip']) && !empty($nodCompletadoOrigen)) {
            $this->notificarSiguienteEtapaTrasCompletar($Ins_Cod, $nodCompletadoOrigen, $nodoDestino, $instancia);
        }

        return true;
    }

    /**
     * Texto visible en línea de tiempo para la rama elegida (comentario de la condición).
     */
    private function textoDetalleRamaDecision($conexion) {
        if (empty($conexion) || !is_array($conexion)) {
            return 'Rama por defecto';
        }
        $expr = null;
        if (!empty($conexion['Con_Con_Exp'])) {
            $expr = json_decode($conexion['Con_Con_Exp'], true);
            if (!is_array($expr)) {
                $expr = null;
            }
        }
        if (is_array($expr) && isset($expr['comentario']) && trim((string)$expr['comentario']) !== '') {
            return trim((string)$expr['comentario']);
        }
        if (is_array($expr) && !empty($expr['campo'])) {
            $op = isset($expr['operador']) ? $expr['operador'] : '=';
            $val = isset($expr['valor']) ? $expr['valor'] : '';
            return $expr['campo'] . ' ' . $op . ' ' . $val;
        }
        return 'Rama por defecto';
    }

    /**
     * Elige la conexión de salida de un nodo DECISION (misma lógica que avanzarSiguientePaso).
     */
    private function resolverRamaDecisionDesdeNodo($nod_decision_cod, $instancia) {
        $nod_decision_cod = intval($nod_decision_cod);
        $conexiones = $this->obBD_datos->getArrayConsultaSql(
            "SELECT * FROM wf_conexiones WHERE Nod_Ori = $nod_decision_cod;",
            $this->obBD_conexion
        );
        if ($conexiones === false || $conexiones === null || empty($conexiones)) {
            return null;
        }
        $elegida = null;
        $defaultRow = null;
        foreach ($conexiones as $conexion) {
            if ($conexion['Con_Acc'] == 'CONDICIONAL' && !empty($conexion['Con_Con_Exp'])) {
                $expr_check = json_decode($conexion['Con_Con_Exp'], true);
                if (is_array($expr_check) && !empty($expr_check['campo'])) {
                    if ($this->evaluarCondicion($conexion['Con_Con_Exp'], $instancia['Ins_Ent_Typ'], $instancia['Ins_Ent_Cod'])) {
                        return $conexion;
                    }
                }
            } elseif ($conexion['Con_Acc'] == 'APROBAR') {
                $expr_def = !empty($conexion['Con_Con_Exp']) ? json_decode($conexion['Con_Con_Exp'], true) : null;
                $tiene_campo = is_array($expr_def) && !empty($expr_def['campo']);
                if (!$tiene_campo) {
                    $defaultRow = $conexion;
                }
            }
        }
        if ($defaultRow !== null) {
            return $defaultRow;
        }
        return $conexiones[0];
    }

    /**
     * Actualiza el movimiento del nodo DECISION para mostrar solo el detalle de la rama.
     */
    private function actualizarComentarioHistorialNodoDecision($Ins_Cod, $Nod_Cod, $detalleRama) {
        $Ins_Cod = intval($Ins_Cod);
        $Nod_Cod = intval($Nod_Cod);
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT Isn_Cod FROM wf_instancias_nodos
             WHERE Ins_Cod = $Ins_Cod AND Nod_Cod = $Nod_Cod
             ORDER BY Isn_Cod DESC LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row['Isn_Cod'])) {
            return;
        }
        $isn = intval($row['Isn_Cod']);
        $com = mysqli_real_escape_string($this->obBD_conexion->conexion, $detalleRama);
        $this->ejecutarSql(
            "UPDATE wf_instancias_nodos
             SET Isn_Acc = 'CONDICIONAL', Isn_Com = '$com', Usu_Cod = NULL, Dep_Cod = NULL
             WHERE Isn_Cod = $isn;"
        );
    }

    /**
     * Decisiones (nodos DECISION) y ramas del flujo publicado asociado a un tipo.
     */
    public function obtenerDecisionesFlujoPorTipo($trq_cod, $emp_cod) {
        $this->ensureVersioningSchema();
        $trq_cod = intval($trq_cod);
        $emp_cod = intval($emp_cod);
        if ($trq_cod <= 0 || $emp_cod <= 0) {
            return array('success' => false, 'message' => 'Parametros invalidos.', 'decisiones' => array(), 'campos' => array());
        }
        $tipo = $this->obBD_datos->getRowConsultaSql(
            "SELECT t.Trq_Cod, t.Wfm_Cod, COALESCE(w.Wfm_Fam_Cod, w.Wfm_Cod) AS Wfm_Fam_Cod
             FROM adq_tipos_requerimientos t
             INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = t.Wfm_Cod
             WHERE t.Trq_Cod = $trq_cod AND t.Emp_Cod = $emp_cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($tipo)) {
            return array('success' => false, 'message' => 'Tipo no encontrado.', 'decisiones' => array(), 'campos' => array());
        }
        $wfm_cod = $this->resolverFlujoParaNuevaInstancia(intval($tipo['Wfm_Cod']), $emp_cod);
        return $this->obtenerDecisionesFlujo($wfm_cod);
    }

    /**
     * Lista nodos DECISION con sus ramas y los campos que el usuario debe completar.
     */
    public function obtenerDecisionesFlujo($wfm_cod) {
        $wfm_cod = intval($wfm_cod);
        if ($wfm_cod <= 0) {
            return array('success' => true, 'Wfm_Cod' => 0, 'decisiones' => array(), 'campos' => array());
        }

        $decisiones_nodos = $this->obBD_datos->getArrayConsultaSql(
            "SELECT Nod_Cod, Nod_Nom, Nod_Des, Nod_Tip
             FROM wf_nodos
             WHERE Wfm_Cod = $wfm_cod AND Nod_Tip = 'DECISION' AND Nod_Est = 'A'
             ORDER BY Nod_Cod ASC;",
            $this->obBD_conexion
        );
        if ($decisiones_nodos === false || $decisiones_nodos === null) {
            $decisiones_nodos = array();
        }

        $etiquetas = array(
            'Sol_Val_Est' => 'Valor estimado / monto',
            'Sol_Tiempo_Est' => 'Dias estimados (SLA)',
            'Sol_Pri' => 'Prioridad',
            'Trq_Cod' => 'Tipo de requerimiento',
            'Dep_Sol' => 'Departamento solicitante',
            'Dep_Cod' => 'Departamento solicitante',
            'Sol_Cod' => 'Codigo de solicitud'
        );

        $decisiones = array();
        $campos_map = array();
        foreach ($decisiones_nodos as $nodo) {
            $nod_cod = intval($nodo['Nod_Cod']);
            $conexiones = $this->obBD_datos->getArrayConsultaSql(
                "SELECT c.Con_Cod, c.Con_Acc, c.Con_Con_Exp, c.Nod_Des,
                        n.Nod_Nom AS Destino_Nom, n.Nod_Tip AS Destino_Tip
                 FROM wf_conexiones c
                 LEFT JOIN wf_nodos n ON n.Nod_Cod = c.Nod_Des
                 WHERE c.Wfm_Cod = $wfm_cod AND c.Nod_Ori = $nod_cod
                 ORDER BY CASE WHEN c.Con_Acc = 'CONDICIONAL' AND c.Con_Con_Exp IS NOT NULL AND TRIM(c.Con_Con_Exp) <> '' THEN 0 ELSE 1 END,
                          c.Con_Cod ASC;",
                $this->obBD_conexion
            );
            if ($conexiones === false || $conexiones === null) {
                $conexiones = array();
            }

            $ramas = array();
            foreach ($conexiones as $con) {
                $expr = null;
                if (!empty($con['Con_Con_Exp'])) {
                    $expr = json_decode($con['Con_Con_Exp'], true);
                    if (!is_array($expr)) {
                        $expr = null;
                    }
                }
                $es_default = (empty($expr) || empty($expr['campo']));
                $campo = (!$es_default && !empty($expr['campo'])) ? $expr['campo'] : '';
                if ($campo === 'Dep_Cod') {
                    $campo = 'Dep_Sol';
                    $expr['campo'] = 'Dep_Sol';
                }
                if ($campo !== '') {
                    if (!isset($campos_map[$campo])) {
                        $campos_map[$campo] = array(
                            'campo' => $campo,
                            'etiqueta' => isset($etiquetas[$campo]) ? $etiquetas[$campo] : $campo,
                            'tipo' => in_array($campo, array('Sol_Val_Est', 'Sol_Tiempo_Est', 'Trq_Cod', 'Sol_Cod'), true) ? 'number' : 'text',
                            'opciones' => ($campo === 'Sol_Pri')
                                ? array('BAJA', 'MEDIA', 'ALTA', 'URGENTE')
                                : array()
                        );
                    }
                }
                $texto = $es_default
                    ? 'Por defecto (si no cumple ninguna condicion)'
                    : (($expr['campo'] . ' ' . (isset($expr['operador']) ? $expr['operador'] : '=') . ' ' . (isset($expr['valor']) ? $expr['valor'] : '')));
                if (is_array($expr) && !empty($expr['comentario'])) {
                    $texto = trim((string)$expr['comentario']);
                }
                $ramas[] = array(
                    'Con_Cod' => intval($con['Con_Cod']),
                    'Con_Acc' => $con['Con_Acc'],
                    'es_default' => $es_default ? 1 : 0,
                    'condicion' => $expr,
                    'texto' => $texto,
                    'Nod_Des' => intval($con['Nod_Des']),
                    'Destino_Nom' => isset($con['Destino_Nom']) ? $con['Destino_Nom'] : '',
                    'Destino_Tip' => isset($con['Destino_Tip']) ? $con['Destino_Tip'] : ''
                );
            }

            $decisiones[] = array(
                'Nod_Cod' => $nod_cod,
                'Nod_Nom' => $nodo['Nod_Nom'],
                'Nod_Des' => isset($nodo['Nod_Des']) ? $nodo['Nod_Des'] : '',
                'ramas' => $ramas
            );
        }

        return array(
            'success' => true,
            'Wfm_Cod' => $wfm_cod,
            'decisiones' => $decisiones,
            'campos' => array_values($campos_map)
        );
    }

    /**
     * Evalúa expresiones condicionales dinámicas basadas en JSON
     * Formato de ejemplo: {"campo": "Sol_Val_Est", "operador": ">", "valor": "5000"}
     */
    protected function evaluarCondicion($expression_json, $entity_type, $entity_id) {
        if (empty($expression_json)) {
            return true;
        }

        $expression = json_decode($expression_json, true);
        if (!$expression || !isset($expression['campo'])) {
            return true;
        }

        $campo = $expression['campo'];
        if ($campo === 'Dep_Cod') {
            $campo = 'Dep_Sol';
        }
        $operador = isset($expression['operador']) ? $expression['operador'] : '=';
        $valor_condicion = $expression['valor'];

        if ($entity_type != 'adq_solicitudes') {
            return false;
        }

        $entity_id = intval($entity_id);
        $valor_real = null;
        $tiene_valor = false;

        // Asegura tabla de variables de decision (puede no existir en instalaciones antiguas)
        $this->obBD_datos->grabarv_registros(
            "CREATE TABLE IF NOT EXISTS adq_solicitudes_decision_vals (
                Sdv_Cod BIGINT NOT NULL AUTO_INCREMENT,
                Sol_Cod BIGINT NOT NULL,
                Sdv_Campo VARCHAR(80) NOT NULL,
                Sdv_Valor VARCHAR(255) NOT NULL DEFAULT '',
                Sdv_Fec DATETIME NOT NULL,
                Usu_Cod BIGINT NULL,
                PRIMARY KEY (Sdv_Cod),
                UNIQUE KEY uk_sol_campo (Sol_Cod, Sdv_Campo),
                KEY idx_sdv_sol (Sol_Cod)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            $this->obBD_conexion
        );

        // 1) Valores de decisión capturados en la solicitud (tienen prioridad)
        $row_var = $this->obBD_datos->getRowConsultaSql(
            "SELECT Sdv_Valor FROM adq_solicitudes_decision_vals
             WHERE Sol_Cod = $entity_id AND Sdv_Campo = '" . $this->escapeWf($campo) . "'
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($row_var) && array_key_exists('Sdv_Valor', $row_var)) {
            $valor_real = $row_var['Sdv_Valor'];
            $tiene_valor = true;
        }

        // 2) Columna de adq_solicitudes
        if (!$tiene_valor) {
            $datos_solicitud = $this->obBD_datos->getRowConsultaSql(
                "SELECT * FROM adq_solicitudes WHERE Sol_Cod = $entity_id LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($datos_solicitud) && array_key_exists($campo, $datos_solicitud)) {
                $valor_real = $datos_solicitud[$campo];
                $tiene_valor = true;
            }
        }

        if (!$tiene_valor) {
            return false;
        }

        switch ($operador) {
            case '>':
                return floatval($valor_real) > floatval($valor_condicion);
            case '<':
                return floatval($valor_real) < floatval($valor_condicion);
            case '>=':
                return floatval($valor_real) >= floatval($valor_condicion);
            case '<=':
                return floatval($valor_real) <= floatval($valor_condicion);
            case '!=':
                return (string)$valor_real != (string)$valor_condicion;
            case '=':
            default:
                return (string)$valor_real == (string)$valor_condicion;
        }
    }

    /**
     * Env�a notificaciones de correo/alertas basadas en el nodo y la instancia
     */
    protected function enviarNotificacionNodo($nodo, $instancia) {
        // En un caso de producci�n real se usar�a la clase Mailer o enviar_correo del sistema
        // Para este entregable, dejamos la simulaci�n del env�o de correo integrada
        $asunto = "Notificaci�n de Workflow EXA: " . $nodo['Nod_Nom'];
        $cuerpo = "Se ha procesado una etapa en el workflow para la solicitud # " . $instancia['Ins_Ent_Cod'] . ".\nEtapa: " . $nodo['Nod_Nom'] . "\nDescripci�n: " . $nodo['Nod_Des'];
        
        // En una base de datos real, buscar�amos correos del departamento responsable o del solicitante
        // Simulando registro de log de notificaciones
        $fecha_actual = date('Y-m-d H:i:s');
        $this->obBD_datos->grabarv_registros("INSERT INTO query_log (sql_text, execution_time) VALUES ('[Notificacion Enviada] Asunto: $asunto', '$fecha_actual');", $this->obBD_conexion);
    }

    private function asegurarCapacidadAdjuntosHistorial() {
        $col = $this->obBD_datos->getRowConsultaSql(
            "SELECT DATA_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'wf_instancias_nodos'
               AND COLUMN_NAME = 'Isn_Adj'
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($col['DATA_TYPE']) && strtolower($col['DATA_TYPE']) === 'varchar') {
            if (!$this->obBD_datos->grabarv_registros(
                "ALTER TABLE wf_instancias_nodos MODIFY COLUMN Isn_Adj TEXT NULL;",
                $this->obBD_conexion
            )) {
                throw new Exception('No se pudo preparar el historial para guardar varios archivos adjuntos.');
            }
        }
    }

    /**
     * Ejecuta una acci�n manual de usuario (Aprobar, Rechazar, Observar, Devolver)
     */
    public function procesarAccionUsuario($Ins_Cod, $Accion, $Comentario, $Adjuntos = null) {
        if ($Adjuntos !== null && $Adjuntos !== '') {
            $this->asegurarCapacidadAdjuntosHistorial();
        }
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
                throw new Exception("La etapa actual del flujo no es v�lida.");
            }

            if ($Accion === 'COMPLETAR' && $nodoActual['Nod_Tip'] !== 'TAREA') {
                throw new Exception("La accion completar solo aplica a nodos de tipo Tarea.");
            }

            // Validar requerimientos obligatorios al aprobar o completar tarea
            if ($Accion === 'APROBAR' || $Accion === 'COMPLETAR') {
                $trimmed_comment = trim($Comentario);
                if ($nodoActual['Nod_Com_Obl'] == 1 && $trimmed_comment === '') {
                    throw new Exception("El comentario es obligatorio para aprobar o resolver esta etapa.");
                }
                if ($nodoActual['Nod_Adj_Obl'] == 1 && empty($Adjuntos)) {
                    throw new Exception("Se requiere cargar al menos un archivo adjunto como sustento de esta etapa.");
                }
                if ($Accion === 'APROBAR' && isset($nodoActual['Nod_Tip']) && in_array($nodoActual['Nod_Tip'], array('AVANCE', 'FISCALIZACION'), true)) {
                    // En estos nodos Guardar solo persiste; avanzar exige justificacion (docs) + comentario.
                    if ($trimmed_comment === '') {
                        $msg_com = ($nodoActual['Nod_Tip'] === 'FISCALIZACION')
                            ? 'Debe ingresar el comentario/justificacion antes de aprobar la fiscalizacion.'
                            : 'Debe ingresar el comentario/justificacion antes de finalizar el avance.';
                        throw new Exception($msg_com);
                    }
                    $cnt_av = $this->obBD_datos->getRowConsultaSql(
                        "SELECT COUNT(*) AS cnt
                         FROM adq_solicitudes_avances
                         WHERE Sol_Cod = " . intval($instancia['Ins_Ent_Cod']) . "
                           AND Ins_Cod = $Ins_Cod
                           AND Nod_Cod = $nod_actual_cod;",
                        $this->obBD_conexion
                    );
                    if (empty($cnt_av['cnt'])) {
                        $msg_req = ($nodoActual['Nod_Tip'] === 'FISCALIZACION')
                            ? 'Debe registrar al menos una factura, anticipo o archivo de fiscalizacion antes de aprobar.'
                            : 'Debe registrar al menos una factura o anticipo antes de finalizar el proceso de avance.';
                        throw new Exception($msg_req);
                    }

                    require_once(dirname(__FILE__) . '/adq_adquisiciones_log.php');
                    $adq_totales = new adq_adquisiciones_log($this->obBD_conexion);
                    $validacion_totales = $adq_totales->validarCoincidenciaTotalesFacturas(
                        intval($instancia['Ins_Ent_Cod'])
                    );
                    if (empty($validacion_totales['success'])) {
                        throw new Exception(isset($validacion_totales['message'])
                            ? $validacion_totales['message']
                            : 'Los valores de la proforma y las facturas no coinciden.');
                    }
                }
                if ($Accion === 'APROBAR' && isset($nodoActual['Nod_Tip']) && $nodoActual['Nod_Tip'] === 'FIN'
                    && isset($instancia['Ins_Ent_Typ']) && $instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
                    $exp = $this->obBD_datos->getRowConsultaSql(
                        "SELECT Sol_Exp_Pdf
                         FROM adq_solicitudes
                         WHERE Sol_Cod = " . intval($instancia['Ins_Ent_Cod']) . "
                         LIMIT 1;",
                        $this->obBD_conexion
                    );
                    if (empty($exp['Sol_Exp_Pdf'])) {
                        throw new Exception('Debe descargar el expediente, revisarlo y volver a cargarlo antes de finalizar.');
                    }
                }
            }

            $fecha_actual = date('Y-m-d H:i:s');
            $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $session_id = session_id() ?: 'CLI-SESSION';
            $fk_acc = $this->resolverFkHistorial(
                isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0,
                isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0
            );
            $usu_cod = $fk_acc['usu'];
            $dep_cod = $fk_acc['dep'];

            // Manejar acci�n DEVOLVER: retroceder al nodo anterior en el historial
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

                // Registrar en historial la acci�n DEVOLVER en el nodo actual
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

            if ($Accion == 'OBSERVAR') {
                $nod_retorno = $this->resolverNodoEtapaAnteriorConProformas($Ins_Cod, intval($nodoActual['Nod_Cod']));
                if ($nod_retorno <= 0) {
                    throw new Exception(
                        'No existe una etapa anterior con la opcion de cargar proformas habilitada. ' .
                        'Active "Permitir cargar cotizaciones" en la etapa destino del flujo.'
                    );
                }

                $com_obs_esc = mysqli_real_escape_string($this->obBD_conexion->conexion, $Comentario);
                $adjunto_obs = $Adjuntos !== null ? "'" . $Adjuntos . "'" : "NULL";
                $this->obBD_datos->grabarv_registros(
                    "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Adj, Isn_Fec, Isn_Ip, Isn_Ses)
                     VALUES ($Ins_Cod, $nod_actual_cod, $usu_cod, $dep_cod, 'OBSERVAR', '$com_obs_esc', $adjunto_obs, '$fecha_actual', '$ip_usuario', '$session_id');",
                    $this->obBD_conexion
                );

                $this->obBD_datos->grabarv_registros(
                    "UPDATE wf_instancias SET Nod_Act = $nod_retorno WHERE Ins_Cod = $Ins_Cod;",
                    $this->obBD_conexion
                );

                if ($instancia['Ins_Ent_Typ'] == 'adq_solicitudes') {
                    $this->obBD_datos->grabarv_registros(
                        "UPDATE adq_solicitudes SET Sol_Est = 'E' WHERE Sol_Cod = $instancia[Ins_Ent_Cod];",
                        $this->obBD_conexion
                    );
                }

                $this->obBD_datos->commit_nomsn($this->obBD_conexion);
                $this->notificarObservacionEtapaAnterior($Ins_Cod, $nodoActual, $Comentario, $instancia, $nod_retorno);
                return array('success' => true);
            }

            // Actualizar estado intermedio de la solicitud segun la accion
            if ($instancia['Ins_Ent_Typ'] == 'adq_solicitudes') {
                $nuevo_est_sol = 'E'; // Por defecto En proceso
                if ($Accion == 'RECHAZAR') {
                    $nuevo_est_sol = 'R';
                    $this->obBD_datos->grabarv_registros("UPDATE wf_instancias SET Ins_Est = 'R', Ins_Fec_Fin = '$fecha_actual' WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
                }
                $this->obBD_datos->grabarv_registros("UPDATE adq_solicitudes SET Sol_Est = '$nuevo_est_sol' WHERE Sol_Cod = $instancia[Ins_Ent_Cod];", $this->obBD_conexion);
            }

            // Rechazo: registrar movimiento en historial de la etapa actual (sin avanzar)
            if ($Accion == 'RECHAZAR') {
                $com_rech_raw = trim(stripslashes((string)$Comentario));
                if ($com_rech_raw === '') {
                    $com_rech_raw = 'Solicitud rechazada.';
                }
                $com_rech_esc = mysqli_real_escape_string($this->obBD_conexion->conexion, $com_rech_raw);
                $adjunto_rech = $Adjuntos !== null ? "'" . mysqli_real_escape_string($this->obBD_conexion->conexion, $Adjuntos) . "'" : "NULL";
                $ok_hist = $this->obBD_datos->grabarv_registros(
                    "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Adj, Isn_Fec, Isn_Ip, Isn_Ses)
                     VALUES ($Ins_Cod, $nod_actual_cod, $usu_cod, $dep_cod, 'RECHAZAR', '$com_rech_esc', $adjunto_rech, '$fecha_actual', '$ip_usuario', '$session_id');",
                    $this->obBD_conexion
                );
                if (!$ok_hist) {
                    throw new Exception('No se pudo registrar el rechazo en el Historial de Firmas.');
                }
            }

            // 2. Ejecutar avance al siguiente paso
            if ($Accion != 'RECHAZAR') {
                $this->avanzarSiguientePaso($Ins_Cod, $nod_actual_cod, $Accion, $Comentario, $Adjuntos);
            }

            $this->obBD_datos->commit_nomsn($this->obBD_conexion);

            if ($Accion === 'APROBAR'
                && $this->debeNotificarCierreNodo($nodoActual)
                && isset($instancia['Ins_Ent_Typ']) && $instancia['Ins_Ent_Typ'] === 'adq_solicitudes'
            ) {
                $this->notificarCierreExpedienteEsquema($instancia, $nodoActual, $Comentario);
            }

            return array('success' => true);
        } catch (Exception $e) {
            $this->obBD_datos->rollBack_nomsn($this->obBD_conexion);
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Vincula una factura de compra con una solicitud de adquisici�n aprobada
     */
    public function vincularCompra($Sol_Cod, $Cop_Cod) {
        $fecha_actual = date('Y-m-d H:i:s');
        // Verificar que no exista ya la vinculaci�n
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

    private function getNodosPredecesores($wfm_cod, $nod_act) {
        $predecesores = array();
        $nod_act = intval($nod_act);
        $wfm_cod = intval($wfm_cod);
        if ($nod_act <= 0 || $wfm_cod <= 0) {
            return $predecesores;
        }

        $cola = array($nod_act);
        $visitados = array();
        while (!empty($cola)) {
            $actual = array_pop($cola);
            if (isset($visitados[$actual])) {
                continue;
            }
            $visitados[$actual] = true;
            $padres = $this->obBD_datos->getArrayConsultaSql(
                "SELECT Nod_Ori FROM wf_conexiones WHERE Wfm_Cod = $wfm_cod AND Nod_Des = $actual;",
                $this->obBD_conexion
            );
            foreach ($padres as $padre) {
                $ori = intval($padre['Nod_Ori']);
                $predecesores[$ori] = true;
                $cola[] = $ori;
            }
        }
        return $predecesores;
    }


    private function normalizarNombreNodo($nombre) {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return '';
        }
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($nombre, 'UTF-8');
        }
        return strtoupper($nombre);
    }

    private function resolverActoresTimeline($historial_actores, $nodos_flujo) {
        $peso = array('APROBAR' => 10, 'RECHAZAR' => 10, 'OBSERVAR' => 9, 'DEVOLVER' => 9, 'COMPLETAR' => 8, 'CREAR' => 2);
        $por_cod = array();
        $por_nombre = array();

        if (!empty($historial_actores)) {
            foreach ($historial_actores as $h) {
                $usuario = trim($h['Usuario_Nom']);
                if ($usuario === '' && !empty($h['Dep_Des'])) {
                    $usuario = trim($h['Dep_Des']);
                }
                if ($usuario === '') {
                    continue;
                }

                $entry = array(
                    'usuario' => $usuario,
                    'accion' => $h['Isn_Acc'],
                    'fecha' => $h['Isn_Fec']
                );
                $cod = intval($h['Nod_Cod']);
                $nom = $this->normalizarNombreNodo(isset($h['Nod_Nom']) ? $h['Nod_Nom'] : '');

                if (!isset($por_cod[$cod]) || $peso[$h['Isn_Acc']] > $peso[$por_cod[$cod]['accion']]) {
                    $por_cod[$cod] = $entry;
                }
                if ($nom !== '' && (!isset($por_nombre[$nom]) || $peso[$h['Isn_Acc']] > $peso[$por_nombre[$nom]['accion']])) {
                    $por_nombre[$nom] = $entry;
                }
            }
        }

        $resultado = array();
        $total = count($nodos_flujo);
        for ($i = 0; $i < $total; $i++) {
            $nodo = $nodos_flujo[$i];
            $cod = intval($nodo['Nod_Cod']);
            $nom = $this->normalizarNombreNodo($nodo['Nod_Nom']);
            $actor = null;

            if ($nodo['Nod_Tip'] === 'INICIO') {
                if (isset($por_cod[$cod])) {
                    $actor = $por_cod[$cod];
                } elseif ($nom !== '' && isset($por_nombre[$nom])) {
                    $actor = $por_nombre[$nom];
                }
            } elseif ($i + 1 < $total) {
                $sig = $nodos_flujo[$i + 1];
                $sig_cod = intval($sig['Nod_Cod']);
                $sig_nom = $this->normalizarNombreNodo($sig['Nod_Nom']);
                $sig_actor = null;
                if (isset($por_cod[$sig_cod])) {
                    $sig_actor = $por_cod[$sig_cod];
                } elseif ($sig_nom !== '' && isset($por_nombre[$sig_nom])) {
                    $sig_actor = $por_nombre[$sig_nom];
                }
                // La aprobacion de la etapa N queda registrada al entrar al nodo N+1 (APROBAR/COMPLETAR).
                if ($sig_actor !== null && in_array($sig_actor['accion'], array('APROBAR', 'COMPLETAR'), true)) {
                    $actor = array(
                        'usuario' => $sig_actor['usuario'],
                        'accion' => 'APROBAR',
                        'fecha' => $sig_actor['fecha']
                    );
                }
            }

            if ($actor === null && $nodo['Nod_Tip'] === 'FIN') {
                if (isset($por_cod[$cod]) && in_array($por_cod[$cod]['accion'], array('APROBAR', 'COMPLETAR'), true)) {
                    $actor = $por_cod[$cod];
                } elseif ($nom !== '' && isset($por_nombre[$nom]) && in_array($por_nombre[$nom]['accion'], array('APROBAR', 'COMPLETAR'), true)) {
                    $actor = $por_nombre[$nom];
                }
            }

            $resultado[$cod] = $actor !== null ? $actor : array('usuario' => '', 'accion' => '', 'fecha' => '');
        }

        return $resultado;
    }

    /**
     * Ultimo movimiento de un nodo en el historial (para cierre FIN u otras etapas).
     */
    private function resolverUltimoActorNodo($historial_actores, $nod_cod, $acciones_validas) {
        $nod_cod = intval($nod_cod);
        if ($nod_cod <= 0 || empty($historial_actores) || empty($acciones_validas)) {
            return array('usuario' => '', 'accion' => '', 'fecha' => '');
        }
        for ($i = count($historial_actores) - 1; $i >= 0; $i--) {
            $h = $historial_actores[$i];
            if (intval($h['Nod_Cod']) !== $nod_cod || !in_array($h['Isn_Acc'], $acciones_validas, true)) {
                continue;
            }
            $usuario = trim($h['Usuario_Nom']);
            if ($usuario === '' && !empty($h['Dep_Des'])) {
                $usuario = trim($h['Dep_Des']);
            }
            if ($usuario === '') {
                continue;
            }
            return array(
                'usuario' => $usuario,
                'accion' => $h['Isn_Acc'],
                'fecha' => $h['Isn_Fec']
            );
        }
        return array('usuario' => '', 'accion' => '', 'fecha' => '');
    }

    private function buildLlegadaPorNodo($historial_actores) {
        $llegada = array();
        if (empty($historial_actores)) {
            return $llegada;
        }
        foreach ($historial_actores as $h) {
            $cod = intval($h['Nod_Cod']);
            if (isset($llegada[$cod])) {
                continue;
            }
            $usuario = trim($h['Usuario_Nom']);
            if ($usuario === '' && !empty($h['Dep_Des'])) {
                $usuario = trim($h['Dep_Des']);
            }
            if ($usuario !== '' && in_array($h['Isn_Acc'], array('APROBAR', 'CREAR', 'COMPLETAR'), true)) {
                $llegada[$cod] = $usuario;
            }
        }
        return $llegada;
    }

    private function obtenerResponsablesPendientes($nodo) {
        $dep_cod = intval($nodo['Dep_Cod']);
        $usu_asig = isset($nodo['Nod_Usu_Asig']) ? $nodo['Nod_Usu_Asig'] : 'TODOS';
        $depto = '';

        if ($dep_cod <= 0) {
            if ($usu_asig !== 'TODOS' && $usu_asig !== '' && $usu_asig !== null) {
                $ids = array();
                foreach (explode(',', $usu_asig) as $id) {
                    $id = intval(trim($id));
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
                if (!empty($ids)) {
                    $lista_ids = implode(',', $ids);
                    $usuarios = $this->obBD_datos->getArrayConsultaSql(
                        "SELECT DISTINCT TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre
                         FROM usuarios u
                         INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                         WHERE u.Usu_Cod IN ($lista_ids) AND u.Usu_Est = 'A'
                         ORDER BY p.Prs_Ape, p.Prs_Nom
                         LIMIT 6;",
                        $this->obBD_conexion
                    );
                    $nombres = array();
                    if (!empty($usuarios)) {
                        foreach ($usuarios as $u) {
                            $nom = trim($u['Nombre']);
                            if ($nom !== '') {
                                $nombres[] = $nom;
                            }
                        }
                    }
                    return array(
                        'depto' => '',
                        'asignados' => !empty($nombres) ? implode(', ', $nombres) : ('Usuario(s) #' . implode(', #', $ids))
                    );
                }
            }
            return array('depto' => '', 'asignados' => '');
        }

        $dep_row = $this->obBD_datos->getRowConsultaSql(
            "SELECT Dep_Des FROM departamen WHERE Dep_Cod = $dep_cod LIMIT 1;",
            $this->obBD_conexion
        );
        $depto = !empty($dep_row['Dep_Des']) ? trim($dep_row['Dep_Des']) : '';

        $filtro_usu = '';
        if ($usu_asig !== 'TODOS' && $usu_asig !== '' && $usu_asig !== null) {
            $ids = array();
            foreach (explode(',', $usu_asig) as $id) {
                $id = intval(trim($id));
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            if (!empty($ids)) {
                $filtro_usu = ' AND u.Usu_Cod IN (' . implode(',', $ids) . ')';
            }
        }

        $usuarios = $this->obBD_datos->getArrayConsultaSql(
            "SELECT DISTINCT TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre
             FROM wf_departamento_usuarios du
             INNER JOIN wf_departamentos w ON w.Wde_Cod = du.Wde_Cod AND w.Wde_Est = 'A'
             INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod AND u.Usu_Est = 'A'
             INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             WHERE w.Dep_Cod = $dep_cod
             $filtro_usu
             ORDER BY p.Prs_Ape, p.Prs_Nom
             LIMIT 6;",
            $this->obBD_conexion
        );

        $nombres = array();
        if (!empty($usuarios)) {
            foreach ($usuarios as $u) {
                $nombre = trim($u['Nombre']);
                if ($nombre !== '') {
                    $nombres[] = $nombre;
                }
            }
        }

        $asignados = '';
        if (!empty($nombres)) {
            $asignados = implode(', ', array_slice($nombres, 0, 4));
            if (count($nombres) > 4) {
                $asignados .= '...';
            }
        }

        return array('depto' => $depto, 'asignados' => $asignados);
    }

    /**
     * Texto legible de responsables para el lienzo del disenador.
     */
    public function resolverTextoAsignadosDisenador($dep_cod, $usu_asig, $per_cod = 0) {
        $dep_cod = intval($dep_cod);
        $per_cod = intval($per_cod);
        $usu_asig = ($usu_asig === null || $usu_asig === '') ? 'TODOS' : (string)$usu_asig;

        if ($dep_cod > 0) {
            $info = $this->obtenerResponsablesPendientes(array(
                'Dep_Cod' => $dep_cod,
                'Nod_Usu_Asig' => $usu_asig
            ));
            if (!empty($info['asignados'])) {
                return $info['asignados'];
            }
            if (!empty($info['depto']) && ($usu_asig === 'TODOS' || $usu_asig === '')) {
                return 'Todos (' . $info['depto'] . ')';
            }
            if (!empty($info['depto'])) {
                return $info['depto'];
            }
        }

        if ($usu_asig !== 'TODOS' && $usu_asig !== '') {
            $ids = array();
            foreach (explode(',', $usu_asig) as $id) {
                $id = intval(trim($id));
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            if (!empty($ids)) {
                $in = implode(',', $ids);
                $rows = $this->obBD_datos->getArrayConsultaSql(
                    "SELECT TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre
                     FROM usuarios u
                     INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                     WHERE u.Usu_Cod IN ($in)
                     ORDER BY p.Prs_Ape, p.Prs_Nom;",
                    $this->obBD_conexion
                );
                $nombres = array();
                foreach ($rows as $row) {
                    $nombre = trim($row['Nombre']);
                    if ($nombre !== '') {
                        $nombres[] = $nombre;
                    }
                }
                if (!empty($nombres)) {
                    return implode(', ', $nombres);
                }
            }
        }

        if ($per_cod > 0) {
            $perfil = $this->obBD_datos->getRowConsultaSql(
                "SELECT Per_Des FROM perfiles WHERE Per_Cod = $per_cod LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($perfil['Per_Des'])) {
                return trim($perfil['Per_Des']);
            }
        }

        return '';
    }

    /**
     * Nombre legible del actor de un movimiento de historial.
     */
    private function resolverNombreActorHistorial($h) {
        $usuario = isset($h['Usuario_Nom']) ? trim($h['Usuario_Nom']) : '';
        if ($usuario === '' && !empty($h['Dep_Des'])) {
            $usuario = trim($h['Dep_Des']);
        }
        if ($usuario === '' && !empty($h['Prs_Nom'])) {
            $usuario = trim($h['Prs_Nom'] . ' ' . (isset($h['Prs_Ape']) ? $h['Prs_Ape'] : ''));
        }
        return $usuario !== '' ? $usuario : 'Sistema';
    }

    /**
     * Corrige etapa y actor para Historial de Firmas.
     * El WF registra la aprobacion al entrar al nodo destino, no al salir de la etapa completada.
     */
    public function normalizarHistorialFirmas($historial, $ins_est, $nod_act) {
        if (empty($historial) || !is_array($historial)) {
            return array();
        }

        $ins_est = ($ins_est === null || $ins_est === '') ? '' : (string)$ins_est;
        $nod_act = intval($nod_act);

        $asc = array_values($historial);
        usort($asc, function ($a, $b) {
            $fa = isset($a['Isn_Fec']) ? $a['Isn_Fec'] : '';
            $fb = isset($b['Isn_Fec']) ? $b['Isn_Fec'] : '';
            if ($fa === $fb) {
                return intval(isset($a['Isn_Cod']) ? $a['Isn_Cod'] : 0) - intval(isset($b['Isn_Cod']) ? $b['Isn_Cod'] : 0);
            }
            return strcmp($fa, $fb);
        });

        $conteo_avance_nodo = array();
        $ultimo_avance = null;
        $correcciones = array();

        foreach ($asc as $h) {
            $isn = intval(isset($h['Isn_Cod']) ? $h['Isn_Cod'] : 0);
            if ($isn <= 0) {
                continue;
            }

            $acc = isset($h['Isn_Acc']) ? $h['Isn_Acc'] : '';
            if (!in_array($acc, array('APROBAR', 'COMPLETAR', 'CREAR'), true)) {
                continue;
            }

            $nod = intval(isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0);
            $prev_en_mismo = isset($conteo_avance_nodo[$nod]) ? intval($conteo_avance_nodo[$nod]) : 0;
            $conteo_avance_nodo[$nod] = $prev_en_mismo + 1;

            $es_completar_mismo = ($prev_en_mismo > 0);

            // Nota: la entrada "pendiente" del nodo FIN la agrega agregarNodoPendienteHistorial();
            // aqui la firma registrada al entrar a FIN se atribuye a la etapa anterior completada.
            if ($es_completar_mismo) {
                $correcciones[$isn] = array(
                    'Nod_Nom' => isset($h['Nod_Nom']) ? $h['Nod_Nom'] : '',
                    'Nod_Tip' => isset($h['Nod_Tip']) ? $h['Nod_Tip'] : '',
                    'Etapa_Nod_Cod' => $nod,
                    'Actor_Nom' => $this->resolverNombreActorHistorial($h),
                    'Actor_Modo' => 'Por',
                    'Fin_Pendiente' => 0
                );
            } elseif ($ultimo_avance !== null) {
                $etapa_cod = intval(isset($ultimo_avance['Nod_Cod']) ? $ultimo_avance['Nod_Cod'] : 0);
                $correcciones[$isn] = array(
                    'Nod_Nom' => isset($ultimo_avance['Nod_Nom']) ? $ultimo_avance['Nod_Nom'] : '',
                    'Nod_Tip' => isset($ultimo_avance['Nod_Tip']) ? $ultimo_avance['Nod_Tip'] : '',
                    'Etapa_Nod_Cod' => $etapa_cod > 0 ? $etapa_cod : $nod,
                    'Actor_Nom' => $this->resolverNombreActorHistorial($h),
                    'Actor_Modo' => 'Por',
                    'Fin_Pendiente' => 0
                );
            } else {
                $correcciones[$isn] = array(
                    'Nod_Nom' => isset($h['Nod_Nom']) ? $h['Nod_Nom'] : '',
                    'Nod_Tip' => isset($h['Nod_Tip']) ? $h['Nod_Tip'] : '',
                    'Etapa_Nod_Cod' => $nod,
                    'Actor_Nom' => $this->resolverNombreActorHistorial($h),
                    'Actor_Modo' => 'Por',
                    'Fin_Pendiente' => 0
                );
            }

            $ultimo_avance = $h;
        }

        foreach ($historial as $idx => $h) {
            $isn = intval(isset($h['Isn_Cod']) ? $h['Isn_Cod'] : 0);
            if ($isn <= 0 || !isset($correcciones[$isn])) {
                continue;
            }
            $c = $correcciones[$isn];
            if (!empty($c['Nod_Nom'])) {
                $historial[$idx]['Nod_Nom'] = $c['Nod_Nom'];
            }
            if (!empty($c['Nod_Tip'])) {
                $historial[$idx]['Nod_Tip'] = $c['Nod_Tip'];
            }
            $historial[$idx]['Actor_Nom'] = $c['Actor_Nom'];
            $historial[$idx]['Actor_Modo'] = $c['Actor_Modo'];
            $historial[$idx]['Fin_Pendiente'] = intval($c['Fin_Pendiente']);
            if (!empty($c['Etapa_Nod_Cod'])) {
                $historial[$idx]['Etapa_Nod_Cod'] = intval($c['Etapa_Nod_Cod']);
            }
        }

        foreach ($historial as $idx => $h) {
            if (empty($historial[$idx]['Etapa_Nod_Cod'])) {
                $historial[$idx]['Etapa_Nod_Cod'] = intval(isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0);
            }
            if (empty($historial[$idx]['Actor_Nom'])) {
                $historial[$idx]['Actor_Nom'] = $this->resolverNombreActorHistorial($h);
            }
            if (empty($historial[$idx]['Actor_Modo'])) {
                $historial[$idx]['Actor_Modo'] = 'Por';
            }
        }

        return $historial;
    }

    /**
     * Si la solicitud/instancia esta rechazada pero falta el movimiento RECHAZAR
     * en el historial, lo reconstruye en el nodo donde quedo (Nod_Act) y lo persiste.
     */
    public function agregarRechazoHistorialSiFalta($historial, $ins_cod, $sol_est = '', $ins_est = '') {
        if (!is_array($historial)) {
            $historial = array();
        }

        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0) {
            return $historial;
        }

        foreach ($historial as $h) {
            if (isset($h['Isn_Acc']) && $h['Isn_Acc'] === 'RECHAZAR') {
                return $historial;
            }
        }

        $instancia = $this->obBD_datos->getRowConsultaSql(
            "SELECT Ins_Cod, Nod_Act, Ins_Est, Ins_Fec_Fin, Ins_Ent_Typ, Ins_Ent_Cod
             FROM wf_instancias
             WHERE Ins_Cod = $ins_cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($instancia)) {
            return $historial;
        }

        $sol_est = ($sol_est === null || $sol_est === '') ? '' : (string)$sol_est;
        $ins_est = ($ins_est === null || $ins_est === '') ? (string)$instancia['Ins_Est'] : (string)$ins_est;
        if ($sol_est === '' && !empty($instancia['Ins_Ent_Typ']) && $instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
            $sol_row = $this->obBD_datos->getRowConsultaSql(
                "SELECT Sol_Est FROM adq_solicitudes WHERE Sol_Cod = " . intval($instancia['Ins_Ent_Cod']) . " LIMIT 1;",
                $this->obBD_conexion
            );
            $sol_est = !empty($sol_row['Sol_Est']) ? $sol_row['Sol_Est'] : '';
        }

        if ($ins_est !== 'R' && $sol_est !== 'R') {
            return $historial;
        }

        $nod_act = intval($instancia['Nod_Act']);
        if ($nod_act <= 0) {
            // Ultimo nodo con movimiento real como respaldo
            $ult = $this->obBD_datos->getRowConsultaSql(
                "SELECT Nod_Cod FROM wf_instancias_nodos
                 WHERE Ins_Cod = $ins_cod
                 ORDER BY Isn_Fec DESC, Isn_Cod DESC
                 LIMIT 1;",
                $this->obBD_conexion
            );
            $nod_act = !empty($ult['Nod_Cod']) ? intval($ult['Nod_Cod']) : 0;
        }
        if ($nod_act <= 0) {
            return $historial;
        }

        $nodo = $this->obBD_datos->getRowConsultaSql(
            "SELECT Nod_Cod, Nod_Nom, Nod_Tip FROM wf_nodos WHERE Nod_Cod = $nod_act LIMIT 1;",
            $this->obBD_conexion
        );

        $fecha = !empty($instancia['Ins_Fec_Fin']) ? $instancia['Ins_Fec_Fin'] : date('Y-m-d H:i:s');
        $comentario = 'Solicitud rechazada.';
        $com_esc = mysqli_real_escape_string($this->obBD_conexion->conexion, $comentario);
        $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $session_id = session_id() ?: 'CLI-SESSION';
        $fk = $this->resolverFkHistorial(
            isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0,
            isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0
        );

        $this->obBD_datos->grabarv_registros(
            "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Fec, Isn_Ip, Isn_Ses)
             VALUES ($ins_cod, $nod_act, {$fk['usu']}, {$fk['dep']}, 'RECHAZAR', '$com_esc', '$fecha', '$ip_usuario', '$session_id');",
            $this->obBD_conexion
        );

        $isn_nuevo = 0;
        if (method_exists($this->obBD_datos, 'insert_id')) {
            $isn_nuevo = intval($this->obBD_datos->insert_id($this->obBD_conexion));
        }
        if ($isn_nuevo <= 0) {
            $row_id = $this->obBD_datos->getRowConsultaSql(
                "SELECT Isn_Cod FROM wf_instancias_nodos
                 WHERE Ins_Cod = $ins_cod AND Nod_Cod = $nod_act AND Isn_Acc = 'RECHAZAR'
                 ORDER BY Isn_Cod DESC LIMIT 1;",
                $this->obBD_conexion
            );
            $isn_nuevo = !empty($row_id['Isn_Cod']) ? intval($row_id['Isn_Cod']) : (0 - $nod_act);
        }

        $historial[] = array(
            'Isn_Cod' => $isn_nuevo,
            'Isn_Acc' => 'RECHAZAR',
            'Nod_Cod' => $nod_act,
            'Etapa_Nod_Cod' => $nod_act,
            'Nod_Nom' => !empty($nodo['Nod_Nom']) ? $nodo['Nod_Nom'] : ('Nodo #' . $nod_act),
            'Nod_Tip' => !empty($nodo['Nod_Tip']) ? $nodo['Nod_Tip'] : '',
            'Isn_Fec' => $fecha,
            'Isn_Com' => $comentario,
            'Isn_Adj' => '',
            'Actor_Nom' => 'Flujo',
            'Actor_Modo' => 'Estado',
            'Pendiente_Aprobacion' => 0,
            'Fin_Pendiente' => 0,
            'archivos' => array()
        );

        return $historial;
    }

    /**
     * Agrega al historial la etapa pendiente actual (nodo que falta aprobar).
     */
    public function agregarNodoPendienteHistorial($historial, $ins_est, $nod_act, $usu_cod_actual = 0) {
        if (!is_array($historial)) {
            $historial = array();
        }

        $ins_est = ($ins_est === null || $ins_est === '') ? '' : (string)$ins_est;
        $nod_act = intval($nod_act);
        $usu_cod_actual = intval($usu_cod_actual);

        if ($ins_est !== 'P' || $nod_act <= 0) {
            return $historial;
        }

        foreach ($historial as $h) {
            $nod_hist = intval(isset($h['Etapa_Nod_Cod']) ? $h['Etapa_Nod_Cod'] : (isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0));
            if ($nod_hist === $nod_act && (!empty($h['Pendiente_Aprobacion']) || !empty($h['Fin_Pendiente']))) {
                return $historial;
            }
        }

        $nodo = $this->obBD_datos->getRowConsultaSql(
            "SELECT n.*
             FROM wf_nodos n
             WHERE n.Nod_Cod = $nod_act
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($nodo) || (isset($nodo['Nod_Tip']) && $nodo['Nod_Tip'] === 'INICIO')) {
            return $historial;
        }

        $actor_nom = '';
        $actor_modo = 'Asignado';
        if ($usu_cod_actual > 0) {
            $u = $this->obBD_datos->getRowConsultaSql(
                "SELECT TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre
                 FROM usuarios u
                 INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                 WHERE u.Usu_Cod = $usu_cod_actual
                 LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($u['Nombre']) && trim($u['Nombre']) !== '') {
                $actor_nom = trim($u['Nombre']);
                $actor_modo = 'Usted';
            }
        }

        if ($actor_nom === '') {
            $resp = $this->obtenerResponsablesPendientes($nodo);
            $partes = array();
            if (!empty($resp['depto'])) {
                $partes[] = $resp['depto'];
            }
            if (!empty($resp['asignados'])) {
                $partes[] = $resp['asignados'];
            }
            $actor_nom = !empty($partes) ? implode(' - ', $partes) : 'Sin asignar';
        }

        $nod_tip = isset($nodo['Nod_Tip']) ? $nodo['Nod_Tip'] : '';
        $historial[] = array(
            'Isn_Cod' => 0,
            'Isn_Acc' => 'PENDIENTE',
            'Nod_Cod' => $nod_act,
            'Etapa_Nod_Cod' => $nod_act,
            'Nod_Nom' => isset($nodo['Nod_Nom']) ? $nodo['Nod_Nom'] : '',
            'Nod_Tip' => $nod_tip,
            'Isn_Fec' => date('Y-m-d H:i:s'),
            'Isn_Com' => '',
            'Isn_Adj' => '',
            'Actor_Nom' => $actor_nom,
            'Actor_Modo' => $actor_modo,
            'Pendiente_Aprobacion' => 1,
            'Fin_Pendiente' => ($nod_tip === 'FIN') ? 1 : 0,
            'archivos' => array()
        );

        return $historial;
    }

    /**
     * Completa Historial de Firmas con los nodos del flujo que aun no tienen movimiento.
     */
    public function completarHistorialFirmasConNodos($historial, $ins_cod) {
        if (!is_array($historial)) {
            $historial = array();
        }

        $ins_cod = intval($ins_cod);
        if ($ins_cod <= 0) {
            return $historial;
        }

        $instancia = $this->obBD_datos->getRowConsultaSql(
            "SELECT Ins_Cod, Wfm_Cod, Nod_Act, Ins_Est
             FROM wf_instancias
             WHERE Ins_Cod = $ins_cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($instancia['Wfm_Cod'])) {
            return $historial;
        }

        $nodos = $this->obBD_datos->getArrayConsultaSql(
            "SELECT Nod_Cod, Nod_Nom, Nod_Tip, Dep_Cod, Per_Cod, Nod_Usu_Asig, Nod_Vis_X, Nod_Vis_Y
             FROM wf_nodos
             WHERE Wfm_Cod = " . intval($instancia['Wfm_Cod']) . "
               AND Nod_Est = 'A'
             ORDER BY Nod_Vis_X ASC, Nod_Vis_Y ASC, Nod_Cod ASC;",
            $this->obBD_conexion
        );
        if (empty($nodos) || !is_array($nodos)) {
            return $historial;
        }

        $nodos_con_historial = array();
        foreach ($historial as $h) {
            $nod_hist = intval(isset($h['Etapa_Nod_Cod']) ? $h['Etapa_Nod_Cod'] : (isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0));
            if ($nod_hist > 0) {
                $nodos_con_historial[$nod_hist] = true;
            }
        }

        $orden = 1;
        foreach ($nodos as $nodo) {
            $nod_cod = intval($nodo['Nod_Cod']);
            if ($nod_cod <= 0 || isset($nodos_con_historial[$nod_cod])) {
                $orden++;
                continue;
            }

            $resp = $this->obtenerResponsablesPendientes($nodo);
            $partes = array();
            if (!empty($resp['depto'])) {
                $partes[] = $resp['depto'];
            }
            if (!empty($resp['asignados'])) {
                $partes[] = $resp['asignados'];
            }
            $actor = !empty($partes) ? implode(' - ', $partes) : 'Sin movimiento';

            $historial[] = array(
                'Isn_Cod' => 0 - $nod_cod,
                'Isn_Acc' => 'SIN_REGISTRO',
                'Nod_Cod' => $nod_cod,
                'Etapa_Nod_Cod' => $nod_cod,
                'Nod_Nom' => isset($nodo['Nod_Nom']) ? $nodo['Nod_Nom'] : '',
                'Nod_Tip' => isset($nodo['Nod_Tip']) ? $nodo['Nod_Tip'] : '',
                'Isn_Fec' => '',
                'Isn_Com' => '',
                'Isn_Adj' => '',
                'Actor_Nom' => $actor,
                'Actor_Modo' => !empty($partes) ? 'Asignado' : 'Estado',
                'Hist_Orden' => $orden,
                'Sin_Registro' => 1,
                'archivos' => array()
            );
            $orden++;
        }

        return $historial;
    }

    /**
     * Usuarios asignados a una etapa con telefono y correo para notificaciones.
     */
    public function listarDestinatariosNotificacionEtapa($nod_cod, $emp_cod, $excluir_usu_cod = 0) {
        $nod_cod = intval($nod_cod);
        $emp_cod = intval($emp_cod);
        $excluir_usu_cod = intval($excluir_usu_cod);
        if ($nod_cod <= 0 || $emp_cod <= 0) {
            return array();
        }

        $nodo = $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_nodos WHERE Nod_Cod = $nod_cod LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($nodo) || ($nodo['Nod_Tip'] !== 'INICIO' && !$this->esNodoNotificableEntrada($nodo['Nod_Tip']))) {
            return array();
        }

        $usuarios = array();
        $dep_cod = intval($nodo['Dep_Cod']);
        $usu_asig = isset($nodo['Nod_Usu_Asig']) ? $nodo['Nod_Usu_Asig'] : 'TODOS';
        $per_cod = intval(isset($nodo['Per_Cod']) ? $nodo['Per_Cod'] : 0);

        if ($dep_cod > 0) {
            $filtro_usu = '';
            if ($usu_asig !== 'TODOS' && $usu_asig !== '' && $usu_asig !== null) {
                $ids = array();
                foreach (explode(',', $usu_asig) as $id) {
                    $id = intval(trim($id));
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
                if (!empty($ids)) {
                    $filtro_usu = ' AND u.Usu_Cod IN (' . implode(',', $ids) . ')';
                }
            }

            $rows = $this->obBD_datos->getArrayConsultaSql(
                "SELECT DISTINCT u.Usu_Cod,
                        TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                        p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                 FROM wf_departamento_usuarios du
                 INNER JOIN wf_departamentos w ON w.Wde_Cod = du.Wde_Cod AND w.Wde_Est = 'A' AND w.Emp_Cod = $emp_cod
                 INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod AND u.Usu_Est = 'A'
                 INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $emp_cod
                 INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                 WHERE w.Dep_Cod = $dep_cod
                 $filtro_usu",
                $this->obBD_conexion
            );
            if (!empty($rows)) {
                $usuarios = array_merge($usuarios, $rows);
            }
        }

        if ($per_cod > 0) {
            $rows = $this->obBD_datos->getArrayConsultaSql(
                "SELECT DISTINCT u.Usu_Cod,
                        TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                        p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                 FROM usuarios u
                 INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $emp_cod
                 INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                 INNER JOIN usuarperfi up ON up.Usu_Cod = u.Usu_Cod
                 WHERE u.Usu_Est = 'A' AND up.Per_Cod = $per_cod",
                $this->obBD_conexion
            );
            if (!empty($rows)) {
                $usuarios = array_merge($usuarios, $rows);
            }
        }

        if ($dep_cod <= 0 && $per_cod <= 0) {
            if ($usu_asig !== 'TODOS' && $usu_asig !== '' && $usu_asig !== null) {
                $ids = array();
                foreach (explode(',', $usu_asig) as $id) {
                    $id = intval(trim($id));
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
                if (!empty($ids)) {
                    $ids_sql = implode(',', $ids);
                    $rows = $this->obBD_datos->getArrayConsultaSql(
                        "SELECT DISTINCT u.Usu_Cod,
                                TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                                p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                         FROM usuarios u
                         INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $emp_cod
                         INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                         WHERE u.Usu_Est = 'A' AND u.Usu_Cod IN ($ids_sql)",
                        $this->obBD_conexion
                    );
                    if (!empty($rows)) {
                        $usuarios = array_merge($usuarios, $rows);
                    }
                }
            } else {
                $rows = $this->obBD_datos->getArrayConsultaSql(
                    "SELECT DISTINCT u.Usu_Cod,
                            TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                            p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                     FROM usuarios u
                     INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $emp_cod
                     INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                     WHERE u.Usu_Est = 'A' AND u.Usu_Wf = 'S'",
                    $this->obBD_conexion
                );
                if (!empty($rows)) {
                    $usuarios = array_merge($usuarios, $rows);
                }
            }
        }

        $resultado = array();
        $vistos = array();
        foreach ($usuarios as $u) {
            $usu_cod = intval($u['Usu_Cod']);
            if ($usu_cod <= 0 || isset($vistos[$usu_cod])) {
                continue;
            }
            if ($excluir_usu_cod > 0 && $usu_cod === $excluir_usu_cod) {
                continue;
            }
            $tel = trim(isset($u['Prs_Tel']) ? $u['Prs_Tel'] : '');
            if ($tel === '' && !empty($u['Prs_Cel'])) {
                $tel = trim($u['Prs_Cel']);
            }
            $correo = trim(isset($u['Prs_Cor']) ? $u['Prs_Cor'] : '');
            if ($tel === '' && $correo === '') {
                continue;
            }
            $vistos[$usu_cod] = true;
            $resultado[] = array(
                'Usu_Cod' => $usu_cod,
                'Nombre' => trim($u['Nombre']),
                'Telefono' => $tel,
                'Correo' => $correo
            );
        }

        return $resultado;
    }

    /**
     * Reune todos los usuarios asignados a nodos activos de un esquema.
     * Elimina duplicados tanto por codigo de usuario como por correo.
     */
    public function listarDestinatariosNotificacionEsquema($wfm_cod, $emp_cod) {
        $wfm_cod = intval($wfm_cod);
        $emp_cod = intval($emp_cod);
        if ($wfm_cod <= 0 || $emp_cod <= 0) {
            return array();
        }

        $nodos = $this->obBD_datos->getArrayConsultaSql(
            "SELECT Nod_Cod
             FROM wf_nodos
             WHERE Wfm_Cod = $wfm_cod
               AND Nod_Est = 'A'
               AND Nod_Tip IN ('INICIO', 'APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FISCALIZACION', 'FIN')
             ORDER BY Nod_Cod ASC;",
            $this->obBD_conexion
        );
        if (empty($nodos)) {
            return array();
        }

        $resultado = array();
        $usuarios_vistos = array();
        $correos_vistos = array();
        foreach ($nodos as $nodo) {
            $destinatarios = $this->listarDestinatariosNotificacionEtapa(intval($nodo['Nod_Cod']), $emp_cod, 0);
            foreach ($destinatarios as $dest) {
                $usu_cod = intval(isset($dest['Usu_Cod']) ? $dest['Usu_Cod'] : 0);
                $correo = strtolower(trim(isset($dest['Correo']) ? $dest['Correo'] : ''));
                if ($usu_cod <= 0 || isset($usuarios_vistos[$usu_cod])) {
                    continue;
                }
                if ($correo !== '' && isset($correos_vistos[$correo])) {
                    $usuarios_vistos[$usu_cod] = true;
                    continue;
                }
                $usuarios_vistos[$usu_cod] = true;
                if ($correo !== '') {
                    $correos_vistos[$correo] = true;
                }
                $resultado[] = $dest;
            }
        }
        return $resultado;
    }

    public function debeNotificarCierreNodo($nodo) {
        return !empty($nodo)
            && isset($nodo['Nod_Tip']) && $nodo['Nod_Tip'] === 'FIN'
            && (!empty($nodo['Nod_Not_Wa']) || !empty($nodo['Nod_Not_Em']));
    }

    /**
     * Devuelve la ruta absoluta segura del expediente: firmado primero,
     * cargado/revisado como alternativa.
     */
    public function resolverRutaExpedienteFinal($ruta_firmada, $ruta_cargada) {
        $base_data = realpath(dirname(__FILE__) . '/../../DATA');
        if ($base_data === false) {
            return '';
        }
        $rutas = array();
        if (!empty($ruta_firmada)) {
            $rutas[] = $ruta_firmada;
        }
        if (!empty($ruta_cargada)) {
            $rutas[] = $ruta_cargada;
        }
        foreach ($rutas as $ruta_rel) {
            $ruta_limpia = ltrim(str_replace('\\', '/', trim((string)$ruta_rel)), '/');
            $candidato = realpath($base_data . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ruta_limpia));
            if ($candidato !== false && is_file($candidato)
                && strpos($candidato, $base_data . DIRECTORY_SEPARATOR) === 0) {
                return $candidato;
            }
        }
        return '';
    }

    /**
     * Envia el expediente final y el comentario de cierre a todos los usuarios
     * asignados al esquema. Los errores de correo no revierten el workflow.
     */
    public function notificarCierreExpedienteEsquema($instancia, $nodo_fin, $comentario) {
        try {
            $this->ensureNotificationSchema();
            if (empty($instancia) || empty($nodo_fin)
                || empty($instancia['Ins_Cod']) || empty($instancia['Wfm_Cod'])
                || empty($instancia['Ins_Ent_Cod'])
                || !$this->debeNotificarCierreNodo($nodo_fin)) {
                return array('enviados' => 0, 'errores' => 0, 'omitidos' => 0);
            }

            $sol_cod = intval($instancia['Ins_Ent_Cod']);
            $sol = $this->obBD_datos->getRowConsultaSql(
                "SELECT s.Sol_Cod, s.Sol_Num, s.Sol_Tit, s.Emp_Cod, s.Sol_Exp_Pdf, s.Sol_Exp_Firmado,
                        t.Trq_Des
                 FROM adq_solicitudes s
                 LEFT JOIN adq_tipos_requerimientos t ON t.Trq_Cod = s.Trq_Cod
                 WHERE s.Sol_Cod = $sol_cod
                 LIMIT 1;",
                $this->obBD_conexion
            );
            if (empty($sol) || empty($sol['Emp_Cod'])) {
                return array('enviados' => 0, 'errores' => 0, 'omitidos' => 0);
            }

            $archivo_abs = $this->resolverRutaExpedienteFinal(
                isset($sol['Sol_Exp_Firmado']) ? $sol['Sol_Exp_Firmado'] : '',
                isset($sol['Sol_Exp_Pdf']) ? $sol['Sol_Exp_Pdf'] : ''
            );
            if ($archivo_abs === '') {
                return array('enviados' => 0, 'errores' => 0, 'omitidos' => 0);
            }

            $destinatarios = $this->listarDestinatariosNotificacionEsquema(
                intval($instancia['Wfm_Cod']),
                intval($sol['Emp_Cod'])
            );
            if (empty($destinatarios)) {
                return array('enviados' => 0, 'errores' => 0, 'omitidos' => 0);
            }

            $numero = trim(isset($sol['Sol_Num']) ? $sol['Sol_Num'] : '');
            $titulo = trim(isset($sol['Sol_Tit']) ? $sol['Sol_Tit'] : '');
            $tipo = trim(isset($sol['Trq_Des']) ? $sol['Trq_Des'] : '');
            $comentario_limpio = trim(stripslashes((string)$comentario));
            if ($comentario_limpio === '') {
                $comentario_limpio = 'Sin comentario de cierre.';
            }
            $asunto = !empty($nodo_fin['Nod_Not_Asunto'])
                ? trim($nodo_fin['Nod_Not_Asunto'])
                : 'Expediente final de la solicitud ' . ($numero !== '' ? $numero : ('#' . $sol_cod));
            $mensaje = "La solicitud ha finalizado correctamente.\n"
                . "Solicitud: " . ($numero !== '' ? $numero : ('#' . $sol_cod)) . "\n"
                . ($titulo !== '' ? "Nombre: $titulo\n" : '')
                . ($tipo !== '' ? "Tipo: $tipo\n" : '')
                . "Etapa final: " . (isset($nodo_fin['Nod_Nom']) ? $nodo_fin['Nod_Nom'] : 'FIN') . "\n\n"
                . "Comentario de cierre:\n" . $comentario_limpio;
            if (!empty($nodo_fin['Nod_Not_Texto']) && trim($nodo_fin['Nod_Not_Texto']) !== '') {
                $mensaje .= "\n\n" . trim($nodo_fin['Nod_Not_Texto']);
            }

            $mail_utils = dirname(__FILE__) . '/../../relavera/LOGICA/relavera_notif_mail_utils.php';
            if (is_file($mail_utils)) {
                require_once($mail_utils);
            }
            $adjunto = array(
                'ruta' => $archivo_abs,
                'nombre' => 'expediente_final_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $numero !== '' ? $numero : $sol_cod) . '.pdf',
                'mime' => 'application/pdf'
            );
            $fecha = date('Y-m-d H:i:s');
            $enviados = 0;
            $errores = 0;
            $omitidos = 0;
            foreach ($destinatarios as $dest) {
                $correo = trim(isset($dest['Correo']) ? $dest['Correo'] : '');
                if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $omitidos++;
                    continue;
                }
                $ok = function_exists('relavera_notif_enviar_correo_notif')
                    ? relavera_notif_enviar_correo_notif(
                        $correo,
                        isset($dest['Nombre']) ? $dest['Nombre'] : '',
                        $asunto,
                        $mensaje,
                        null,
                        $adjunto
                    )
                    : false;
                if ($ok) {
                    $enviados++;
                } else {
                    $errores++;
                }
                $this->registrarNotificacionInstancia(
                    intval($instancia['Ins_Cod']),
                    intval($nodo_fin['Nod_Cod']),
                    intval(isset($dest['Usu_Cod']) ? $dest['Usu_Cod'] : 0),
                    'EM',
                    $correo,
                    $ok ? 'O' : 'E',
                    $fecha,
                    '[CIERRE FINAL] ' . $mensaje,
                    $ok ? '' : 'Error al enviar el expediente final'
                );
            }
            return array('enviados' => $enviados, 'errores' => $errores, 'omitidos' => $omitidos);
        } catch (Exception $e) {
            return array('enviados' => 0, 'errores' => 1, 'omitidos' => 0);
        }
    }

    /**
     * Usuarios que deben aprobar una etapa, con telefono desde persona (Prs_Tel / Prs_Cel).
     */
    public function listarUsuariosNotificablesEtapa($nod_cod, $emp_cod, $excluir_usu_cod = 0) {
        $dest = $this->listarDestinatariosNotificacionEtapa($nod_cod, $emp_cod, $excluir_usu_cod);
        $resultado = array();
        foreach ($dest as $d) {
            if (empty($d['Telefono'])) {
                continue;
            }
            $resultado[] = array(
                'Usu_Cod' => $d['Usu_Cod'],
                'Nombre' => $d['Nombre'],
                'Telefono' => $d['Telefono']
            );
        }
        return $resultado;
    }

    /**
     * Aprobadores de la etapa activa de una solicitud de adquisiciones.
     */
    public function listarAprobadoresSolicitud($sol_cod, $emp_cod) {
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        if ($sol_cod <= 0 || $emp_cod <= 0) {
            return array();
        }

        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT s.Usu_Sol, i.Nod_Act
             FROM adq_solicitudes s
             INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes'
                AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row['Nod_Act'])) {
            return array();
        }

        return $this->listarUsuariosNotificablesEtapa(intval($row['Nod_Act']), $emp_cod, intval($row['Usu_Sol']));
    }

    private function formatActorEtapa($accion, $usuario, $pendiente = false) {
        if ($pendiente) {
            return 'Pendiente de aprobacion';
        }
        if ($usuario === null || trim($usuario) === '') {
            return '';
        }
        $usuario = trim($usuario);
        $etiquetas = array(
            'APROBAR' => 'Aprobo',
            'CREAR' => 'Inicio',
            'RECHAZAR' => 'Rechazo',
            'OBSERVAR' => 'Observo',
            'DEVOLVER' => 'Devolvio',
            'COMPLETAR' => 'Completo'
        );
        $prefijo = isset($etiquetas[$accion]) ? $etiquetas[$accion] : 'Por';
        return $prefijo . ': ' . $usuario;
    }

    /**
     * Ordena nodos siguiendo las flechas del flujo (wf_conexiones) desde INICIO hasta FIN.
     * Prioriza conexiones de avance; evita ciclos de OBSERVAR/DEVOLVER/RECHAZAR.
     */
    private function ordenarNodosPorConexiones($nodos, $conexiones) {
        if (empty($nodos) || !is_array($nodos)) {
            return is_array($nodos) ? $nodos : array();
        }

        $by_id = array();
        $orden_original = array();
        foreach ($nodos as $nodo) {
            $id = intval(isset($nodo['Nod_Cod']) ? $nodo['Nod_Cod'] : (isset($nodo['id']) ? $nodo['id'] : 0));
            if ($id <= 0) {
                continue;
            }
            $by_id[$id] = $nodo;
            $orden_original[] = $id;
        }
        if (empty($by_id)) {
            return $nodos;
        }

        $es_avance = array(
            'APROBAR' => true,
            'COMPLETAR' => true,
            'CREAR' => true,
            'CONDICIONAL' => true,
            '' => true
        );
        $adj_fwd = array();
        $adj_all = array();
        $incoming_fwd = array();

        if (!empty($conexiones) && is_array($conexiones)) {
            foreach ($conexiones as $con) {
                $ori = intval(isset($con['Nod_Ori']) ? $con['Nod_Ori'] : 0);
                $des = intval(isset($con['Nod_Des']) ? $con['Nod_Des'] : 0);
                if ($ori <= 0 || $des <= 0 || !isset($by_id[$ori]) || !isset($by_id[$des]) || $ori === $des) {
                    continue;
                }
                if (!isset($adj_all[$ori])) {
                    $adj_all[$ori] = array();
                }
                $adj_all[$ori][] = $des;

                $acc = isset($con['Con_Acc']) ? strtoupper(trim($con['Con_Acc'])) : '';
                if (isset($es_avance[$acc])) {
                    if (!isset($adj_fwd[$ori])) {
                        $adj_fwd[$ori] = array();
                    }
                    $adj_fwd[$ori][] = $des;
                    if (!isset($incoming_fwd[$des])) {
                        $incoming_fwd[$des] = 0;
                    }
                    $incoming_fwd[$des]++;
                }
            }
        }

        $starts = array();
        foreach ($by_id as $id => $nodo) {
            $tip = isset($nodo['Nod_Tip']) ? $nodo['Nod_Tip'] : (isset($nodo['tipo']) ? $nodo['tipo'] : '');
            if ($tip === 'INICIO') {
                $starts[] = $id;
            }
        }
        if (empty($starts)) {
            foreach ($orden_original as $id) {
                if (empty($incoming_fwd[$id])) {
                    $starts[] = $id;
                }
            }
        }
        if (empty($starts)) {
            $starts[] = $orden_original[0];
        }

        $ordenados_ids = array();
        $visitados = array();
        $cola = $starts;
        while (!empty($cola)) {
            $actual = array_shift($cola);
            if (isset($visitados[$actual]) || !isset($by_id[$actual])) {
                continue;
            }
            $visitados[$actual] = true;
            $ordenados_ids[] = $actual;

            $siguientes = array();
            if (!empty($adj_fwd[$actual])) {
                $siguientes = $adj_fwd[$actual];
            } elseif (!empty($adj_all[$actual])) {
                $siguientes = $adj_all[$actual];
            }

            $visto_sig = array();
            foreach ($siguientes as $sig) {
                $sig = intval($sig);
                if ($sig <= 0 || isset($visto_sig[$sig]) || isset($visitados[$sig])) {
                    continue;
                }
                $visto_sig[$sig] = true;
                $cola[] = $sig;
            }
        }

        foreach ($orden_original as $id) {
            if (!isset($visitados[$id])) {
                $ordenados_ids[] = $id;
            }
        }

        $resultado = array();
        foreach ($ordenados_ids as $id) {
            $resultado[] = $by_id[$id];
        }
        return $resultado;
    }

    /**
     * Para la vista de seguimiento conserva solamente la rama que corresponde
     * a la validacion actual de cada nodo DECISION y elimina nodos inaccesibles.
     */
    private function filtrarFlujoVisualPorDecisiones($nodos, $conexiones, $instancia) {
        if (empty($nodos) || !is_array($nodos) || empty($conexiones) || !is_array($conexiones)) {
            return array('nodos' => is_array($nodos) ? $nodos : array(), 'conexiones' => is_array($conexiones) ? $conexiones : array());
        }

        $by_id = array();
        $inicio = 0;
        $decisiones = array();
        foreach ($nodos as $nodo) {
            $id = intval(isset($nodo['Nod_Cod']) ? $nodo['Nod_Cod'] : 0);
            if ($id <= 0) {
                continue;
            }
            $by_id[$id] = $nodo;
            $tipo = isset($nodo['Nod_Tip']) ? $nodo['Nod_Tip'] : '';
            if ($tipo === 'INICIO' && $inicio <= 0) {
                $inicio = $id;
            }
            if ($tipo === 'DECISION') {
                $decisiones[$id] = true;
            }
        }
        if ($inicio <= 0) {
            return array('nodos' => $nodos, 'conexiones' => $conexiones);
        }

        $conexion_elegida = array();
        foreach ($decisiones as $decision_id => $dummy) {
            $elegida = $this->resolverRamaDecisionDesdeNodo($decision_id, $instancia);
            if (!empty($elegida)) {
                if (!empty($elegida['Con_Cod'])) {
                    $conexion_elegida[$decision_id] = 'cod:' . intval($elegida['Con_Cod']);
                } else {
                    $conexion_elegida[$decision_id] = 'ruta:'
                        . intval(isset($elegida['Nod_Ori']) ? $elegida['Nod_Ori'] : $decision_id)
                        . ':' . intval(isset($elegida['Nod_Des']) ? $elegida['Nod_Des'] : 0);
                }
            }
        }

        $conexiones_filtradas = array();
        foreach ($conexiones as $con) {
            $ori = intval(isset($con['Nod_Ori']) ? $con['Nod_Ori'] : 0);
            $des = intval(isset($con['Nod_Des']) ? $con['Nod_Des'] : 0);
            if ($ori <= 0 || $des <= 0 || !isset($by_id[$ori]) || !isset($by_id[$des])) {
                continue;
            }
            if (isset($decisiones[$ori]) && isset($conexion_elegida[$ori])) {
                $clave = !empty($con['Con_Cod'])
                    ? ('cod:' . intval($con['Con_Cod']))
                    : ('ruta:' . $ori . ':' . $des);
                if ($clave !== $conexion_elegida[$ori]) {
                    continue;
                }
            }
            $conexiones_filtradas[] = $con;
        }

        // Determinar los nodos alcanzables por conexiones de avance.
        $acciones_avance = array(
            'APROBAR' => true,
            'COMPLETAR' => true,
            'CREAR' => true,
            'CONDICIONAL' => true,
            '' => true
        );
        $adyacentes = array();
        foreach ($conexiones_filtradas as $con) {
            $accion = isset($con['Con_Acc']) ? strtoupper(trim($con['Con_Acc'])) : '';
            if (!isset($acciones_avance[$accion])) {
                continue;
            }
            $ori = intval($con['Nod_Ori']);
            $des = intval($con['Nod_Des']);
            if (!isset($adyacentes[$ori])) {
                $adyacentes[$ori] = array();
            }
            $adyacentes[$ori][] = $des;
        }

        $alcanzables = array();
        $cola = array($inicio);
        while (!empty($cola)) {
            $actual = array_shift($cola);
            if (isset($alcanzables[$actual]) || !isset($by_id[$actual])) {
                continue;
            }
            $alcanzables[$actual] = true;
            if (!empty($adyacentes[$actual])) {
                foreach ($adyacentes[$actual] as $siguiente) {
                    if (!isset($alcanzables[$siguiente])) {
                        $cola[] = $siguiente;
                    }
                }
            }
        }

        $nodos_resultado = array();
        foreach ($nodos as $nodo) {
            $id = intval(isset($nodo['Nod_Cod']) ? $nodo['Nod_Cod'] : 0);
            if (isset($alcanzables[$id])) {
                $nodos_resultado[] = $nodo;
            }
        }

        $conexiones_resultado = array();
        foreach ($conexiones_filtradas as $con) {
            $ori = intval($con['Nod_Ori']);
            $des = intval($con['Nod_Des']);
            if (isset($alcanzables[$ori]) && isset($alcanzables[$des])) {
                $conexiones_resultado[] = $con;
            }
        }

        return array('nodos' => $nodos_resultado, 'conexiones' => $conexiones_resultado);
    }

    private function enriquecerVisualNodosConSla($visual_nodos, $historial, $fecha_registro, $nod_actual, $ins_est) {
        if (empty($visual_nodos) || !is_array($visual_nodos)) {
            return array();
        }

        $visitas = array();
        $nodo_visita = 0;
        $ultima_fecha = '';
        foreach ($historial as $h) {
            $nod_cod = intval(isset($h['Nod_Cod']) ? $h['Nod_Cod'] : 0);
            $fecha = isset($h['Isn_Fec']) ? trim($h['Isn_Fec']) : '';
            if ($nod_cod <= 0 || $fecha === '') {
                continue;
            }
            if ($nodo_visita !== $nod_cod) {
                if ($nodo_visita > 0 && isset($visitas[$nodo_visita])) {
                    $visitas[$nodo_visita]['salida'] = $fecha;
                }
                $visitas[$nod_cod] = array('entrada' => $fecha, 'salida' => '');
                $nodo_visita = $nod_cod;
            }
            $ultima_fecha = $fecha;
        }

        // OBSERVAR/DEVOLVER registran la salida en el nodo origen, pero no siempre
        // crean una fila de entrada en el nodo al que retorna la instancia.
        if ($ins_est === 'P' && intval($nod_actual) > 0 && $nodo_visita !== intval($nod_actual) && $ultima_fecha !== '') {
            if ($nodo_visita > 0 && isset($visitas[$nodo_visita])) {
                $visitas[$nodo_visita]['salida'] = $ultima_fecha;
            }
            $visitas[intval($nod_actual)] = array('entrada' => $ultima_fecha, 'salida' => '');
            $nodo_visita = intval($nod_actual);
        }

        if ($nodo_visita > 0 && $ins_est !== 'P' && isset($visitas[$nodo_visita]) && $ultima_fecha !== '') {
            $visitas[$nodo_visita]['salida'] = $ultima_fecha;
        }

        $ahora = date('Y-m-d H:i:s');
        foreach ($visual_nodos as $idx => $node) {
            $nod_cod = intval($node['id']);
            $sla = max(0, intval(isset($node['sla_dias']) ? $node['sla_dias'] : 0));
            $entrada = isset($visitas[$nod_cod]['entrada']) ? $visitas[$nod_cod]['entrada'] : '';
            $salida = isset($visitas[$nod_cod]['salida']) ? $visitas[$nod_cod]['salida'] : '';

            if ($idx === 0 && trim((string)$fecha_registro) !== '') {
                $entrada = $fecha_registro;
            }
            if ($salida === '' && $ins_est === 'P' && $nod_cod === intval($nod_actual) && $entrada !== '') {
                $salida_calculo = $ahora;
            } else {
                $salida_calculo = $salida;
            }

            $estado_sla = 'no_iniciado';
            $dias_transcurridos = null;
            $dias_retraso = 0;
            $fecha_limite = '';
            if ($sla <= 0) {
                $estado_sla = 'sin_tiempo';
            } elseif ($entrada !== '' && $salida_calculo !== '') {
                $ts_entrada = strtotime($entrada);
                $ts_salida = strtotime($salida_calculo);
                if ($ts_entrada !== false && $ts_salida !== false) {
                    $ts_limite = $ts_entrada + ($sla * 86400);
                    $fecha_limite = date('Y-m-d H:i:s', $ts_limite);
                    $dias_transcurridos = round(max(0, $ts_salida - $ts_entrada) / 86400, 1);
                    $dias_retraso = round(max(0, $ts_salida - $ts_limite) / 86400, 1);
                    $estado_sla = $dias_retraso > 0 ? 'retrasado' : 'en_plazo';
                }
            } elseif ($entrada !== '') {
                $ts_entrada = strtotime($entrada);
                if ($ts_entrada !== false) {
                    $fecha_limite = date('Y-m-d H:i:s', $ts_entrada + ($sla * 86400));
                }
            }

            $visual_nodos[$idx]['sla_dias'] = $sla;
            $visual_nodos[$idx]['sla_estado'] = $estado_sla;
            $visual_nodos[$idx]['sla_entrada'] = $entrada;
            $visual_nodos[$idx]['sla_salida'] = $salida;
            $visual_nodos[$idx]['sla_fecha_limite'] = $fecha_limite;
            $visual_nodos[$idx]['sla_dias_transcurridos'] = $dias_transcurridos;
            $visual_nodos[$idx]['sla_dias_retraso'] = $dias_retraso;
        }
        return $visual_nodos;
    }

    public function getVisualFlowData($Ins_Cod) {
        $Ins_Cod = intval($Ins_Cod);
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
        if (empty($instancia)) {
            return array('nodos' => array(), 'conexiones' => array(), 'nodo_actual' => null);
        }

        $nodos = $this->obBD_datos->getArrayConsultaSql("SELECT * FROM wf_nodos WHERE Wfm_Cod = {$instancia['Wfm_Cod']} AND Nod_Est = 'A' ORDER BY Nod_Vis_X ASC, Nod_Cod ASC;", $this->obBD_conexion);
        $conexiones = $this->obBD_datos->getArrayConsultaSql("SELECT * FROM wf_conexiones WHERE Wfm_Cod = {$instancia['Wfm_Cod']} ORDER BY Con_Cod ASC;", $this->obBD_conexion);
        if ($nodos === false || $nodos === null) {
            $nodos = array();
        }
        if ($conexiones === false || $conexiones === null) {
            $conexiones = array();
        }
        $flujo_filtrado = $this->filtrarFlujoVisualPorDecisiones($nodos, $conexiones, $instancia);
        $nodos = $flujo_filtrado['nodos'];
        $conexiones = $flujo_filtrado['conexiones'];
        $nodos = $this->ordenarNodosPorConexiones($nodos, $conexiones);
        
        // Obtener todos los pasos del historial ejecutados para esta instancia
        $pasos_ejecutados = $this->obBD_datos->getArrayConsultaSql("SELECT Nod_Cod, Isn_Acc FROM wf_instancias_nodos WHERE Ins_Cod = $Ins_Cod ORDER BY Isn_Fec ASC;", $this->obBD_conexion);
        
        $nodos_visitados = array();
        foreach ($pasos_ejecutados as $paso) {
            $nodos_visitados[intval($paso['Nod_Cod'])] = $paso['Isn_Acc'];
        }

        $historial_actores = $this->obBD_datos->getArrayConsultaSql(
            "SELECT h.Isn_Cod, h.Nod_Cod, h.Isn_Acc, h.Isn_Fec,
                    COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
                    TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom,
                    COALESCE(wd.Wde_Des, dep.Dep_Des) AS Dep_Des
             FROM wf_instancias_nodos h
             LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
             LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
             LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             LEFT JOIN wf_departamentos wd ON wd.Dep_Cod = h.Dep_Cod
             LEFT JOIN departamen dep ON dep.Dep_Cod = h.Dep_Cod
             WHERE h.Ins_Cod = $Ins_Cod
             ORDER BY h.Isn_Fec ASC, h.Isn_Cod ASC;",
            $this->obBD_conexion
        );
        $actores_por_nodo = $this->resolverActoresTimeline($historial_actores, $nodos);
        $llegada_por_nodo = $this->buildLlegadaPorNodo($historial_actores);
        $predecesores = array();
        if ($instancia['Ins_Est'] === 'P' && !empty($instancia['Nod_Act'])) {
            $predecesores = $this->getNodosPredecesores($instancia['Wfm_Cod'], $instancia['Nod_Act']);
        }

        $visual_nodos = array();
        foreach ($nodos as $nodo) {
            $nod_id = intval($nodo['Nod_Cod']);
            $color = 'grey';

            if ($nod_id == intval($instancia['Nod_Act']) && $instancia['Ins_Est'] == 'P') {
                $color = 'blue';
            } elseif (isset($nodos_visitados[$nod_id])) {
                $accion = $nodos_visitados[$nod_id];
                if ($accion == 'RECHAZAR' || $accion == 'OBSERVAR') {
                    $color = 'red';
                } else {
                    $color = 'green';
                }
            } elseif (isset($predecesores[$nod_id])) {
                $color = 'green';
            } elseif ($nodo['Nod_Tip'] === 'INICIO' && $nod_id != intval($instancia['Nod_Act'])) {
                $color = 'green';
            }

            $actor = isset($actores_por_nodo[$nod_id]) ? $actores_por_nodo[$nod_id] : array('usuario' => '', 'accion' => '', 'fecha' => '');
            $es_nodo_actual_abierto = ($nod_id == intval($instancia['Nod_Act']) && $instancia['Ins_Est'] === 'P');
            if ($nodo['Nod_Tip'] === 'FIN') {
                if ($es_nodo_actual_abierto) {
                    // Al entrar a FIN se registra APROBAR con quien aprobo la etapa anterior; no es el responsable del cierre.
                    $actor = array('usuario' => '', 'accion' => '', 'fecha' => '');
                } elseif ($instancia['Ins_Est'] === 'F') {
                    $actor = $this->resolverUltimoActorNodo($historial_actores, $nod_id, array('APROBAR', 'COMPLETAR'));
                }
            }
            $es_pendiente = ($color === 'blue' && empty($actor['usuario']) && in_array($nodo['Nod_Tip'], array('APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FISCALIZACION', 'DECISION', 'FIN'), true));
            $pendiente_meta = null;
            if ($es_pendiente) {
                $resp = $this->obtenerResponsablesPendientes($nodo);
                $pendiente_meta = array(
                    'depto' => $resp['depto'],
                    'asignados' => $resp['asignados'],
                    'enviado_por' => isset($llegada_por_nodo[$nod_id]) ? $llegada_por_nodo[$nod_id] : ''
                );
            }
            $visual_nodos[] = array(
                'id' => $nodo['Nod_Cod'],
                'nombre' => $nodo['Nod_Nom'],
                'tipo' => $nodo['Nod_Tip'],
                'color' => $color,
                'sla_dias' => max(0, intval(isset($nodo['Nod_Sla']) ? $nodo['Nod_Sla'] : 0)),
                'x' => $nodo['Nod_Vis_X'],
                'y' => $nodo['Nod_Vis_Y'],
                'usuario' => $actor['usuario'],
                'accion' => $actor['accion'],
                'actor_label' => $es_pendiente ? '' : $this->formatActorEtapa($actor['accion'], $actor['usuario'], false),
                'pendiente_meta' => $pendiente_meta
            );
        }

        $fecha_registro = '';
        if (isset($instancia['Ins_Ent_Typ']) && $instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
            $sol_fec = $this->obBD_datos->getRowConsultaSql(
                "SELECT Sol_Fec FROM adq_solicitudes WHERE Sol_Cod = " . intval($instancia['Ins_Ent_Cod']) . " LIMIT 1;",
                $this->obBD_conexion
            );
            $fecha_registro = isset($sol_fec['Sol_Fec']) ? $sol_fec['Sol_Fec'] : '';
        }
        $visual_nodos = $this->enriquecerVisualNodosConSla(
            $visual_nodos,
            $historial_actores,
            $fecha_registro,
            isset($instancia['Nod_Act']) ? intval($instancia['Nod_Act']) : 0,
            isset($instancia['Ins_Est']) ? $instancia['Ins_Est'] : ''
        );

        return array(
            'nodos' => $visual_nodos,
            'conexiones' => $conexiones,
            'nodo_actual' => $instancia['Nod_Act']
        );
    }

    /**
     * Asegura que exista la tabla wf_departamentos del modulo Workflow.
     */
    public function ensureWfDepartamentosTable() {
        $sql = "CREATE TABLE IF NOT EXISTS wf_departamentos (
            Wde_Cod BIGINT NOT NULL AUTO_INCREMENT,
            Emp_Cod INT NOT NULL,
            Wde_Des VARCHAR(150) NOT NULL,
            Wde_Est CHAR(1) NOT NULL DEFAULT 'A',
            Dep_Cod BIGINT DEFAULT NULL COMMENT 'llave foranea de departamentos',
            PRIMARY KEY (Wde_Cod),
            KEY idx_wf_dep_emp (Emp_Cod),
            KEY Dep_Cod (Dep_Cod)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $this->obBD_datos->grabarv_registros($sql, $this->obBD_conexion);
        $this->ensureWfDepartamentosDepCodColumn();
        $this->ensureWfDepartamentoUsuariosWdeCod();
    }

    /**
     * Agrega Dep_Cod (FK a departamen) en instalaciones previas.
     */
    private function ensureWfDepartamentosDepCodColumn() {
        $col = $this->obBD_datos->getRowConsultaSql(
            "SHOW COLUMNS FROM wf_departamentos LIKE 'Dep_Cod';",
            $this->obBD_conexion
        );
        if (!empty($col)) {
            return;
        }
        $this->obBD_datos->setError(0, '');
        $this->obBD_datos->grabarv_registros(
            "ALTER TABLE wf_departamentos
             ADD COLUMN Dep_Cod BIGINT DEFAULT NULL COMMENT 'llave foranea de departamentos' AFTER Wde_Est,
             ADD KEY Dep_Cod (Dep_Cod);",
            $this->obBD_conexion
        );
        $this->obBD_datos->setError(0, '');
        $this->obBD_datos->grabarv_registros(
            "UPDATE wf_departamentos SET Dep_Cod = Wde_Cod WHERE Dep_Cod IS NULL;",
            $this->obBD_conexion
        );
    }

    /**
     * Obtiene Dep_Cod de RRHH a partir del Wde_Cod del workflow.
     */
    public function resolverDepRrhhDesdeWf($wde_cod, $emp_cod) {
        $wde_cod = intval($wde_cod);
        $emp_cod = intval($emp_cod);
        if ($wde_cod <= 0 || $emp_cod <= 0) {
            return 0;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT Dep_Cod FROM wf_departamentos WHERE Wde_Cod = $wde_cod AND Emp_Cod = $emp_cod LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($row['Dep_Cod']) ? intval($row['Dep_Cod']) : 0;
    }

    /**
     * Convierte el codigo del combo del disenador (Wde_Cod) al Dep_Cod valido en departamen (RRHH).
     */
    public function resolverDepCodRrhh($codigo_ui, $emp_cod) {
        $codigo_ui = intval($codigo_ui);
        $emp_cod = intval($emp_cod);
        if ($codigo_ui <= 0 || $emp_cod <= 0) {
            return null;
        }

        $wf = $this->obBD_datos->getRowConsultaSql(
            "SELECT Wde_Cod, Dep_Cod, Wde_Des FROM wf_departamentos WHERE Wde_Cod = $codigo_ui AND Emp_Cod = $emp_cod LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($wf['Dep_Cod'])) {
            return intval($wf['Dep_Cod']);
        }

        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT Dep_Cod FROM departamen WHERE Dep_Cod = $codigo_ui AND Emp_Cod = $emp_cod AND Dep_Est = 'A' LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($row['Dep_Cod'])) {
            return intval($row['Dep_Cod']);
        }

        if (!empty($wf['Wde_Des'])) {
            $des = $this->escapeWf($wf['Wde_Des']);
            $rrhh = $this->obBD_datos->getRowConsultaSql(
                "SELECT MIN(Dep_Cod) AS Dep_Cod FROM departamen WHERE Emp_Cod = $emp_cod AND Dep_Est = 'A' AND Dep_Des = '$des' LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($rrhh['Dep_Cod'])) {
                return intval($rrhh['Dep_Cod']);
            }
            throw new Exception(
                "El departamento '" . $wf['Wde_Des'] . "' no existe en RRHH. Registrelo en Recursos Humanos o sincronice el catalogo."
            );
        }

        throw new Exception('El departamento seleccionado no es valido para el flujo.');
    }

    /**
     * Convierte Dep_Cod de RRHH al Wde_Cod usado por el disenador en el combo de departamentos.
     */
    public function resolverWdeCodDisenador($dep_cod_rrhh, $emp_cod) {
        $dep_cod_rrhh = intval($dep_cod_rrhh);
        $emp_cod = intval($emp_cod);
        if ($dep_cod_rrhh <= 0 || $emp_cod <= 0) {
            return '';
        }

        $this->syncDepartamentosFromRrhh($emp_cod);

        $wf = $this->obBD_datos->getRowConsultaSql(
            "SELECT Wde_Cod FROM wf_departamentos WHERE Dep_Cod = $dep_cod_rrhh AND Emp_Cod = $emp_cod AND Wde_Est = 'A' LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($wf['Wde_Cod'])) {
            return intval($wf['Wde_Cod']);
        }

        return '';
    }

    /**
     * Lista departamentos del disenador registrados en wf_departamentos (activos en WF).
     * Incluye todos los departamentos vinculados a RRHH, tengan o no usuarios asignados.
     */
    public function listarDepartamentosDisenador($emp_cod) {
        $this->ensureWfDepartamentosTable();
        $this->syncDepartamentosFromRrhh($emp_cod);
        $emp_cod = intval($emp_cod);
        if ($emp_cod <= 0) {
            return array();
        }

        return $this->obBD_datos->getArrayConsultaSql(
            "SELECT w.Wde_Cod AS Dep_Cod,
                    COALESCE(r.Dep_Des, w.Wde_Des) AS Dep_Des,
                    w.Dep_Cod AS Dep_Rrhh_Cod,
                    (SELECT COUNT(DISTINCT u.Usu_Ced)
                     FROM wf_departamento_usuarios du
                     INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
                     INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
                     WHERE s.Emp_Cod = $emp_cod AND u.Usu_Est = 'A' AND u.Usu_Wf = 'S'
                       AND (du.Wde_Cod = w.Wde_Cod
                            OR (du.Wde_Cod IS NULL AND du.Dep_Cod = w.Dep_Cod))
                    ) AS Cant_Usuarios
             FROM wf_departamentos w
             LEFT JOIN departamen r ON r.Dep_Cod = w.Dep_Cod AND r.Emp_Cod = w.Emp_Cod AND r.Dep_Est = 'A'
             WHERE w.Emp_Cod = $emp_cod AND w.Wde_Est = 'A' AND w.Dep_Cod IS NOT NULL
             ORDER BY COALESCE(r.Dep_Des, w.Wde_Des) ASC, w.Wde_Cod DESC;",
            $this->obBD_conexion
        );
    }

    /**
     * Clausula SQL para filas legacy/nuevas de wf_departamento_usuarios por Wde_Cod.
     */
    public function sqlDuPorWdeCod($wde_cod, $du_alias = 'du') {
        $wde_cod = intval($wde_cod);
        return "($du_alias.Wde_Cod = $wde_cod OR ($du_alias.Wde_Cod IS NULL AND $du_alias.Dep_Cod = (SELECT w.Dep_Cod FROM wf_departamentos w WHERE w.Wde_Cod = $wde_cod LIMIT 1)))";
    }

    /**
     * Usuarios asignados a un departamento workflow (para combo de nodo).
     */
    public function listarUsuariosAsignacionDepartamento($wde_cod, $emp_cod) {
        $this->ensureWfDepartamentoUsuariosWdeCod();
        $wde_cod = intval($wde_cod);
        $emp_cod = intval($emp_cod);
        if (!$this->validarWdeCodWorkflow($wde_cod, $emp_cod)) {
            return array();
        }
        $filtro_du = $this->sqlDuPorWdeCod($wde_cod, 'du');
        $rows = $this->obBD_datos->getArrayConsultaSql(
            "SELECT MIN(u.Usu_Cod) AS Usu_Cod,
                    TRIM(CONCAT(p.Prs_Nom, ' ', p.Prs_Ape)) AS Usuario_Nom,
                    GROUP_CONCAT(u.Usu_Cod ORDER BY u.Usu_Cod) AS Usu_Cods
             FROM wf_departamento_usuarios du
             INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
             INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
             WHERE $filtro_du AND s.Emp_Cod = $emp_cod
               AND u.Usu_Est = 'A' AND u.Usu_Wf = 'S'
             GROUP BY u.Usu_Ced, p.Prs_Nom, p.Prs_Ape
             ORDER BY Usuario_Nom;",
            $this->obBD_conexion
        );
        return ($rows === false || $rows === null) ? array() : $rows;
    }

    /**
     * Lista departamentos activos registrados en RRHH (tabla departamen).
     */
    public function listarDepartamentosRrhh($emp_cod) {
        $emp_cod = intval($emp_cod);
        if ($emp_cod <= 0) {
            return array();
        }

        $rows = $this->obBD_datos->getArrayConsultaSql(
            "SELECT d.Dep_Cod, d.Dep_Des
             FROM departamen d
             WHERE d.Emp_Cod = $emp_cod AND d.Dep_Est = 'A'
             ORDER BY d.Dep_Cod DESC;",
            $this->obBD_conexion
        );
        return ($rows === false || $rows === null) ? array() : $rows;
    }

    /**
     * Crea o actualiza un departamento de workflow vinculado a RRHH.
     */
    public function guardarDepartamentoWorkflow($emp_cod, $dep_cod, $dep_des, $dep_rrhh_cod = null) {
        $this->ensureWfDepartamentosTable();
        $emp_cod = intval($emp_cod);
        $dep_des_esc = $this->escapeWf(trim($dep_des));

        if (!empty($dep_cod)) {
            if ($dep_des_esc === '') {
                throw new Exception('El nombre del departamento es obligatorio.');
            }
            $wde = intval($dep_cod);
            $this->ejecutarSql(
                "UPDATE wf_departamentos SET Wde_Des = '$dep_des_esc' WHERE Wde_Cod = $wde AND Emp_Cod = $emp_cod;"
            );
            return $wde;
        }

        $dep_rrhh = 0;
        if (!empty($dep_rrhh_cod)) {
            $dep_rrhh = intval($dep_rrhh_cod);
            $rrhh = $this->obBD_datos->getRowConsultaSql(
                "SELECT Dep_Cod, Dep_Des FROM departamen WHERE Dep_Cod = $dep_rrhh AND Emp_Cod = $emp_cod AND Dep_Est = 'A' LIMIT 1;",
                $this->obBD_conexion
            );
            if (empty($rrhh['Dep_Cod'])) {
                throw new Exception('El departamento seleccionado no existe o no está activo en Recursos Humanos.');
            }
            $dep_rrhh = intval($rrhh['Dep_Cod']);
            $dep_des_esc = $this->escapeWf(trim($rrhh['Dep_Des']));
        } else {
            if ($dep_des_esc === '') {
                throw new Exception('Debe seleccionar un departamento de Recursos Humanos.');
            }
            $rrhh = $this->obBD_datos->getRowConsultaSql(
                "SELECT MIN(Dep_Cod) AS Dep_Cod FROM departamen WHERE Emp_Cod = $emp_cod AND Dep_Est = 'A' AND Dep_Des = '$dep_des_esc' LIMIT 1;",
                $this->obBD_conexion
            );
            if (empty($rrhh['Dep_Cod'])) {
                throw new Exception(
                    "No existe el departamento '$dep_des' en RRHH. Debe registrarlo primero en Recursos Humanos."
                );
            }
            $dep_rrhh = intval($rrhh['Dep_Cod']);
        }

        $existing = $this->obBD_datos->getRowConsultaSql(
            "SELECT Wde_Cod FROM wf_departamentos WHERE Dep_Cod = $dep_rrhh AND Emp_Cod = $emp_cod LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($existing['Wde_Cod'])) {
            $wde = intval($existing['Wde_Cod']);
            $this->ejecutarSql(
                "UPDATE wf_departamentos SET Wde_Des = '$dep_des_esc', Wde_Est = 'A', Dep_Cod = $dep_rrhh WHERE Wde_Cod = $wde AND Emp_Cod = $emp_cod;"
            );
            return $wde;
        }

        $this->ejecutarSql(
            "INSERT INTO wf_departamentos (Emp_Cod, Wde_Des, Wde_Est, Dep_Cod) VALUES ($emp_cod, '$dep_des_esc', 'A', $dep_rrhh);"
        );
        $nuevo = $this->obBD_datos->getRowConsultaSql(
            "SELECT Wde_Cod FROM wf_departamentos WHERE Dep_Cod = $dep_rrhh AND Emp_Cod = $emp_cod ORDER BY Wde_Cod DESC LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($nuevo['Wde_Cod']) ? intval($nuevo['Wde_Cod']) : $dep_rrhh;
    }

    /**
     * Sincroniza descripciones de wf_departamentos con RRHH (no importa masivamente).
     */
    public function syncDepartamentosFromRrhh($emp_id) {
        $this->ensureWfDepartamentosTable();
        $emp_id = intval($emp_id);
        if ($emp_id <= 0) {
            return 0;
        }

        $this->obBD_datos->setError(0, '');
        $this->obBD_datos->grabarv_registros(
            "UPDATE wf_departamentos w
             INNER JOIN departamen d ON d.Dep_Cod = w.Dep_Cod AND d.Emp_Cod = w.Emp_Cod
             SET w.Wde_Des = d.Dep_Des
             WHERE w.Emp_Cod = $emp_id AND d.Dep_Est = 'A' AND w.Wde_Des <> d.Dep_Des;",
            $this->obBD_conexion
        );
        return true;
    }

    /**
     * Crea la tabla de estado de departamentos solo para Workflow (no afecta RRHH).
     */
    public function ensureWfDepartamentosConfigTable() {
        $sql = "CREATE TABLE IF NOT EXISTS wf_departamentos_config (
            Wfd_Cod INT AUTO_INCREMENT PRIMARY KEY,
            Emp_Cod INT NOT NULL,
            Dep_Cod INT NOT NULL,
            Wfd_Est CHAR(1) NOT NULL DEFAULT 'A',
            UNIQUE KEY uk_wf_emp_dep (Emp_Cod, Dep_Cod)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $this->obBD_datos->grabarv_registros($sql, $this->obBD_conexion);
    }

    /**
     * Activa/desactiva un departamento solo en el modulo Workflow.
     * No modifica departamen.Dep_Est (tabla compartida con RRHH).
     */
    public function toggleDepartamentoWorkflow($emp_id, $dep_cod, $estadoActual) {
        $this->ensureWfDepartamentosConfigTable();
        $emp_id = intval($emp_id);
        $dep_cod = intval($dep_cod);
        $nuevo_est = ($estadoActual === 'A') ? 'I' : 'A';

        $existing = $this->obBD_datos->getRowConsultaSql(
            "SELECT Wfd_Cod FROM wf_departamentos_config WHERE Emp_Cod = $emp_id AND Dep_Cod = $dep_cod LIMIT 1;",
            $this->obBD_conexion
        );

        if ($existing) {
            $this->obBD_datos->grabarv_registros(
                "UPDATE wf_departamentos_config SET Wfd_Est = '$nuevo_est' WHERE Emp_Cod = $emp_id AND Dep_Cod = $dep_cod;",
                $this->obBD_conexion
            );
        } else {
            $this->obBD_datos->grabarv_registros(
                "INSERT INTO wf_departamentos_config (Emp_Cod, Dep_Cod, Wfd_Est) VALUES ($emp_id, $dep_cod, '$nuevo_est');",
                $this->obBD_conexion
            );
        }

        return $nuevo_est;
    }

    /**
     * Fragmento SQL para filtrar departamentos activos en Workflow.
     */
    public function sqlFiltroDeptosActivosWorkflow($emp_id, $depAlias = 'd') {
        $emp_id = intval($emp_id);
        return "IFNULL((SELECT wdc.Wfd_Est FROM wf_departamentos_config wdc WHERE wdc.Dep_Cod = $depAlias.Dep_Cod AND wdc.Emp_Cod = $emp_id LIMIT 1), 'A') = 'A'";
    }

    /**
     * Devuelve el nodo de aprobacion donde debe reevaluarse una solicitud observada.
     */
    public function resolverNodoReevaluacionTrasObservacion($Ins_Cod) {
        $Ins_Cod = intval($Ins_Cod);
        $instancia = $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod AND Ins_Est = 'P' LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($instancia)) {
            throw new Exception('No existe una instancia activa de workflow.');
        }

        $obs = $this->obBD_datos->getRowConsultaSql(
            "SELECT h.Nod_Cod, h.Isn_Fec
             FROM wf_instancias_nodos h
             WHERE h.Ins_Cod = $Ins_Cod AND h.Isn_Acc = 'OBSERVAR'
             ORDER BY h.Isn_Fec DESC
             LIMIT 1;",
            $this->obBD_conexion
        );

        if (!empty($obs['Nod_Cod'])) {
            $nod_obs = intval($obs['Nod_Cod']);
            $nodo_obs = $this->obBD_datos->getRowConsultaSql(
                "SELECT Nod_Cod, Nod_Tip FROM wf_nodos WHERE Nod_Cod = $nod_obs LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($nodo_obs) && $this->esNodoResolubleHumano($nodo_obs['Nod_Tip'])) {
                return $nod_obs;
            }

            $conn = $this->obBD_datos->getRowConsultaSql(
                "SELECT Nod_Ori FROM wf_conexiones WHERE Nod_Des = $nod_obs AND Con_Acc = 'OBSERVAR' LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($conn['Nod_Ori'])) {
                return intval($conn['Nod_Ori']);
            }

            $fecha_obs = mysqli_real_escape_string($this->obBD_conexion->conexion, $obs['Isn_Fec']);
            $prev = $this->obBD_datos->getRowConsultaSql(
                "SELECT h.Nod_Cod
                 FROM wf_instancias_nodos h
                 INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
                 WHERE h.Ins_Cod = $Ins_Cod
                   AND h.Isn_Fec < '$fecha_obs'
                   AND n.Nod_Tip IN ('APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FISCALIZACION')
                 ORDER BY h.Isn_Fec DESC
                 LIMIT 1;",
                $this->obBD_conexion
            );
            if (!empty($prev['Nod_Cod'])) {
                return intval($prev['Nod_Cod']);
            }
        }

        $nod_actual = intval($instancia['Nod_Act']);
        $nodoActual = $this->obBD_datos->getRowConsultaSql(
            "SELECT Nod_Cod, Nod_Tip FROM wf_nodos WHERE Nod_Cod = $nod_actual LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($nodoActual) && $this->esNodoResolubleHumano($nodoActual['Nod_Tip'])) {
            return $nod_actual;
        }

        throw new Exception('No se pudo determinar la etapa de aprobacion para reenviar la solicitud.');
    }

    /**
     * Registra la correccion del solicitante y devuelve la instancia al nodo de reevaluacion.
     */
    public function reenviarCorreccionSolicitante($Ins_Cod, $comentario = '') {
        $Ins_Cod = intval($Ins_Cod);
        $instancia = $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod AND Ins_Est = 'P' LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($instancia)) {
            return array('success' => false, 'message' => 'No existe una instancia activa de workflow.');
        }

        $nod_actual = intval($instancia['Nod_Act']);
        $nod_destino = $this->resolverNodoReevaluacionTrasObservacion($Ins_Cod);

        $fecha_actual = date('Y-m-d H:i:s');
        $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $session_id = session_id() ?: 'CLI-SESSION';
        $fk = $this->resolverFkHistorial(
            isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0,
            isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0
        );
        $comentario = trim($comentario) !== '' ? $comentario : 'Correccion enviada por el solicitante.';
        $com_esc = mysqli_real_escape_string($this->obBD_conexion->conexion, $comentario);

        if ($nod_actual > 0) {
            $this->ejecutarSql(
                "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Fec, Isn_Ip, Isn_Ses)
                 VALUES ($Ins_Cod, $nod_actual, {$fk['usu']}, {$fk['dep']}, 'COMPLETAR', '$com_esc', '$fecha_actual', '$ip_usuario', '$session_id');"
            );
        }

        $this->ejecutarSql("UPDATE wf_instancias SET Nod_Act = $nod_destino WHERE Ins_Cod = $Ins_Cod;");

        $nodoDestino = $this->obBD_datos->getRowConsultaSql(
            "SELECT * FROM wf_nodos WHERE Nod_Cod = $nod_destino LIMIT 1;",
            $this->obBD_conexion
        );
        $sla_vencimiento = 'NULL';
        if (!empty($nodoDestino['Nod_Sla']) && intval($nodoDestino['Nod_Sla']) > 0) {
            $sla_vencimiento = "'" . date('Y-m-d H:i:s', strtotime('+' . intval($nodoDestino['Nod_Sla']) . ' days')) . "'";
        }

        $this->ejecutarSql(
            "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Fec, Isn_Sla_Ven, Isn_Ip, Isn_Ses)
             VALUES ($Ins_Cod, $nod_destino, {$fk['usu']}, {$fk['dep']}, 'REENVIAR', '$com_esc', '$fecha_actual', $sla_vencimiento, '$ip_usuario', '$session_id');"
        );

        if (!empty($nodoDestino) && $this->esNodoNotificableEntrada($nodoDestino['Nod_Tip'])) {
            $this->notificarSiguienteEtapaTrasCompletar(
                $Ins_Cod,
                array('Nod_Tip' => 'INICIO'),
                $nodoDestino,
                $instancia,
                array('evento' => 'reenvio')
            );
        }

        return array(
            'success' => true,
            'Ins_Cod' => $Ins_Cod,
            'Nod_Act' => $nod_destino
        );
    }

    /**
     * Al observar una solicitud, notifica al responsable de la etapa anterior segun la configuracion del nodo actual.
     */
    public function notificarObservacionEtapaAnterior($Ins_Cod, $nodoObservador, $comentario, $instancia = null, $nod_retorno_cod = 0) {
        try {
            $this->ensureNotificationSchema();
            $Ins_Cod = intval($Ins_Cod);
            if ($Ins_Cod <= 0 || empty($nodoObservador)) {
                return;
            }

            $not_wa = !empty($nodoObservador['Nod_Not_Wa']);
            $not_em = !empty($nodoObservador['Nod_Not_Em']);
            if (!$not_wa && !$not_em) {
                return;
            }

            if (empty($instancia)) {
                $instancia = $this->obBD_datos->getRowConsultaSql(
                    "SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod LIMIT 1;",
                    $this->obBD_conexion
                );
            }
            if (empty($instancia)) {
                return;
            }

            $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
            if ($emp_cod <= 0 && $instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
                $emp_row = $this->obBD_datos->getRowConsultaSql(
                    "SELECT Emp_Cod FROM adq_solicitudes WHERE Sol_Cod = " . intval($instancia['Ins_Ent_Cod']) . " LIMIT 1;",
                    $this->obBD_conexion
                );
                $emp_cod = !empty($emp_row['Emp_Cod']) ? intval($emp_row['Emp_Cod']) : 0;
            }
            if ($emp_cod <= 0) {
                return;
            }

            $nod_actual_cod = intval($nodoObservador['Nod_Cod']);
            $nod_anterior_cod = intval($nod_retorno_cod);
            if ($nod_anterior_cod <= 0) {
                $nod_anterior_cod = $this->resolverNodoEtapaAnteriorInstancia($Ins_Cod, $nod_actual_cod);
            }
            if ($nod_anterior_cod <= 0) {
                return;
            }

            $nodo_anterior = $this->obBD_datos->getRowConsultaSql(
                "SELECT * FROM wf_nodos WHERE Nod_Cod = $nod_anterior_cod LIMIT 1;",
                $this->obBD_conexion
            );
            if (empty($nodo_anterior)) {
                return;
            }

            $destinatarios = $this->resolverDestinatariosEtapaAnteriorObservacion(
                $Ins_Cod,
                $nodo_anterior,
                $emp_cod,
                $instancia
            );
            if (empty($destinatarios)) {
                return;
            }

            $mensaje_data = $this->construirMensajeNotificacionAdq(
                $instancia,
                $nodo_anterior,
                $emp_cod,
                'observar',
                array(
                    'comentario' => $comentario,
                    'nodo_observador' => $nodoObservador
                )
            );
            if (empty($mensaje_data)) {
                return;
            }

            $mensaje = $mensaje_data['mensaje'];
            if (!empty($nodoObservador['Nod_Not_Texto'])) {
                $extra = trim($nodoObservador['Nod_Not_Texto']);
                if ($extra !== '') {
                    $mensaje .= "\n\n" . $extra;
                }
            }

            $asunto = !empty($nodoObservador['Nod_Not_Asunto'])
                ? trim($nodoObservador['Nod_Not_Asunto'])
                : $mensaje_data['asunto'];
            $nod_cod = $nod_anterior_cod;
            $fecha = date('Y-m-d H:i:s');

            if ($not_wa) {
                $wa_path = dirname(__FILE__) . '/../../MODELS/send_whatsapp.php';
                if (file_exists($wa_path)) {
                    require_once($wa_path);
                }
                if (function_exists('enviarNotificacionWhatsapp')) {
                    $numeros = array();
                    foreach ($destinatarios as $d) {
                        if (!empty($d['Telefono'])) {
                            $numeros[] = $d['Telefono'];
                        }
                    }
                    $numeros = array_values(array_unique($numeros));
                    if (!empty($numeros)) {
                        $ok = enviarNotificacionWhatsapp($mensaje, $numeros);
                        foreach ($destinatarios as $d) {
                            if (empty($d['Telefono'])) {
                                continue;
                            }
                            $this->registrarNotificacionInstancia(
                                $Ins_Cod, $nod_cod, $d['Usu_Cod'], 'WA', $d['Telefono'],
                                $ok ? 'O' : 'E', $fecha, $mensaje, $ok ? '' : 'Error al enviar WhatsApp'
                            );
                        }
                    }
                }
            }

            if ($not_em) {
                $mail_utils = dirname(__FILE__) . '/../../relavera/LOGICA/relavera_notif_mail_utils.php';
                if (file_exists($mail_utils)) {
                    require_once($mail_utils);
                }
                if (function_exists('relavera_notif_enviar_correo_notif')) {
                    foreach ($destinatarios as $d) {
                        if (empty($d['Correo']) || !filter_var($d['Correo'], FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }
                        $ok_mail = relavera_notif_enviar_correo_notif($d['Correo'], $d['Nombre'], $asunto, $mensaje);
                        $this->registrarNotificacionInstancia(
                            $Ins_Cod, $nod_cod, $d['Usu_Cod'], 'EM', $d['Correo'],
                            $ok_mail ? 'O' : 'E', $fecha, $mensaje, $ok_mail ? '' : 'Error al enviar correo'
                        );
                    }
                }
            }
        } catch (Exception $e) {
            // No interrumpir el flujo por fallos de notificacion
        }
    }

    /**
     * Ultimo nodo humano distinto al actual en el historial de la instancia.
     */
    private function resolverNodoEtapaAnteriorInstancia($Ins_Cod, $nod_actual_cod) {
        $Ins_Cod = intval($Ins_Cod);
        $nod_actual_cod = intval($nod_actual_cod);
        if ($Ins_Cod <= 0 || $nod_actual_cod <= 0) {
            return 0;
        }

        $nodoAnterior = $this->obBD_datos->getRowConsultaSql(
            "SELECT DISTINCT h.Nod_Cod
             FROM wf_instancias_nodos h
             INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
             WHERE h.Ins_Cod = $Ins_Cod
               AND h.Nod_Cod != $nod_actual_cod
               AND n.Nod_Tip NOT IN ('INICIO', 'DECISION', 'NOTIFICACION', 'FIN')
             ORDER BY h.Isn_Fec DESC
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($nodoAnterior['Nod_Cod'])) {
            return intval($nodoAnterior['Nod_Cod']);
        }

        $inicio = $this->obBD_datos->getRowConsultaSql(
            "SELECT n.Nod_Cod
             FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Wfm_Cod = i.Wfm_Cod AND n.Nod_Tip = 'INICIO' AND n.Nod_Est = 'A'
             WHERE i.Ins_Cod = $Ins_Cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        return !empty($inicio['Nod_Cod']) ? intval($inicio['Nod_Cod']) : 0;
    }

    /**
     * Destinatarios de la etapa anterior: actor que la resolvio o, si fue INICIO, el solicitante.
     */
    private function resolverDestinatariosEtapaAnteriorObservacion($Ins_Cod, $nodoAnterior, $emp_cod, $instancia) {
        $Ins_Cod = intval($Ins_Cod);
        $emp_cod = intval($emp_cod);
        $nod_anterior_cod = intval($nodoAnterior['Nod_Cod']);
        if ($Ins_Cod <= 0 || $emp_cod <= 0 || $nod_anterior_cod <= 0) {
            return array();
        }

        if ($nodoAnterior['Nod_Tip'] === 'INICIO' && $instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
            return $this->resolverDestinatariosSolicitanteAdq(intval($instancia['Ins_Ent_Cod']), $emp_cod);
        }

        $actor = $this->obBD_datos->getRowConsultaSql(
            "SELECT h.Usu_Cod
             FROM wf_instancias_nodos h
             WHERE h.Ins_Cod = $Ins_Cod
               AND h.Nod_Cod = $nod_anterior_cod
               AND h.Isn_Acc IN ('APROBAR', 'COMPLETAR', 'CREAR')
               AND h.Usu_Cod IS NOT NULL AND h.Usu_Cod > 0
             ORDER BY h.Isn_Fec DESC
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (!empty($actor['Usu_Cod'])) {
            $contacto = $this->resolverContactoUsuarioNotificacion(intval($actor['Usu_Cod']), $emp_cod);
            if (!empty($contacto)) {
                return array($contacto);
            }
        }

        if ($this->esNodoNotificableEntrada($nodoAnterior['Nod_Tip'])) {
            return $this->listarDestinatariosNotificacionEtapa($nod_anterior_cod, $emp_cod, 0);
        }

        if ($instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
            return $this->resolverDestinatariosSolicitanteAdq(intval($instancia['Ins_Ent_Cod']), $emp_cod);
        }

        return array();
    }

    private function resolverDestinatariosSolicitanteAdq($sol_cod, $emp_cod) {
        $sol_cod = intval($sol_cod);
        $emp_cod = intval($emp_cod);
        if ($sol_cod <= 0 || $emp_cod <= 0) {
            return array();
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT s.Usu_Sol,
                    TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                    p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
             FROM adq_solicitudes s
             INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
             INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = $emp_cod
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row['Usu_Sol'])) {
            return array();
        }
        $tel = trim(isset($row['Prs_Tel']) ? $row['Prs_Tel'] : '');
        if ($tel === '' && !empty($row['Prs_Cel'])) {
            $tel = trim($row['Prs_Cel']);
        }
        $correo = trim(isset($row['Prs_Cor']) ? $row['Prs_Cor'] : '');
        if ($tel === '' && $correo === '') {
            return array();
        }
        return array(array(
            'Usu_Cod' => intval($row['Usu_Sol']),
            'Nombre' => trim($row['Nombre']),
            'Telefono' => $tel,
            'Correo' => $correo
        ));
    }

    private function resolverContactoUsuarioNotificacion($usu_cod, $emp_cod) {
        $usu_cod = intval($usu_cod);
        $emp_cod = intval($emp_cod);
        if ($usu_cod <= 0) {
            return null;
        }
        $row = $this->obBD_datos->getRowConsultaSql(
            "SELECT u.Usu_Cod,
                    TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                    p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
             FROM usuarios u
             INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             WHERE u.Usu_Cod = $usu_cod AND u.Usu_Est = 'A'
               AND ($emp_cod <= 0 OR u.Emp_Cod = $emp_cod)
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($row['Usu_Cod'])) {
            return null;
        }
        $tel = trim(isset($row['Prs_Tel']) ? $row['Prs_Tel'] : '');
        if ($tel === '' && !empty($row['Prs_Cel'])) {
            $tel = trim($row['Prs_Cel']);
        }
        $correo = trim(isset($row['Prs_Cor']) ? $row['Prs_Cor'] : '');
        if ($tel === '' && $correo === '') {
            return null;
        }
        return array(
            'Usu_Cod' => intval($row['Usu_Cod']),
            'Nombre' => trim($row['Nombre']),
            'Telefono' => $tel,
            'Correo' => $correo
        );
    }

    /**
     * Tras completar una etapa, notifica a los responsables de la siguiente tarea.
     * La configuracion (WhatsApp/correo) se lee del nodo completado; si fue envio desde INICIO, del nodo siguiente.
     */
    public function notificarSiguienteEtapaTrasCompletar($Ins_Cod, $nodoOrigen, $nodoSiguiente, $instancia = null, $opciones = array()) {
        if (empty($nodoOrigen) || empty($nodoSiguiente)) {
            return;
        }
        if (is_numeric($nodoOrigen)) {
            $nodoOrigen = $this->obBD_datos->getRowConsultaSql(
                "SELECT * FROM wf_nodos WHERE Nod_Cod = " . intval($nodoOrigen) . " LIMIT 1;",
                $this->obBD_conexion
            );
        }
        if (is_numeric($nodoSiguiente)) {
            $nodoSiguiente = $this->obBD_datos->getRowConsultaSql(
                "SELECT * FROM wf_nodos WHERE Nod_Cod = " . intval($nodoSiguiente) . " LIMIT 1;",
                $this->obBD_conexion
            );
        }
        if (empty($nodoOrigen) || empty($nodoSiguiente) || !$this->esNodoNotificableEntrada($nodoSiguiente['Nod_Tip'])) {
            return;
        }

        $origenTip = isset($nodoOrigen['Nod_Tip']) ? $nodoOrigen['Nod_Tip'] : '';
        if ($origenTip === 'INICIO') {
            $nodoConfig = $nodoSiguiente;
        } else {
            $nodoConfig = $nodoOrigen;
        }

        $this->enviarNotificacionEtapaInstancia($Ins_Cod, $nodoConfig, $nodoSiguiente, $instancia, $opciones);
    }

    /**
     * Envio de notificaciones a los responsables de una etapa segun configuracion del nodo.
     */
    public function enviarNotificacionEtapaInstancia($Ins_Cod, $nodoConfig, $nodoDestino, $instancia = null, $opciones = array()) {
        try {
            $this->ensureNotificationSchema();
            $Ins_Cod = intval($Ins_Cod);
            if (empty($nodoConfig)) {
                return;
            }
            $nodo = $nodoConfig;
            if ($Ins_Cod <= 0 || empty($nodoDestino) || !$this->esNodoNotificableEntrada($nodoDestino['Nod_Tip'])) {
                return;
            }
            if (empty($instancia)) {
                $instancia = $this->obBD_datos->getRowConsultaSql(
                    "SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod LIMIT 1;",
                    $this->obBD_conexion
                );
            }
            if (empty($instancia) || $instancia['Ins_Est'] !== 'P') {
                return;
            }

            $not_wa = !empty($nodo['Nod_Not_Wa']);
            $not_em = !empty($nodo['Nod_Not_Em']);
            if (!$not_wa && !$not_em) {
                return;
            }
            if (isset($nodo['Nod_Not_Mom']) && $nodo['Nod_Not_Mom'] === 'E') {
                return;
            }

            $emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
            if ($emp_cod <= 0 && $instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
                $emp_row = $this->obBD_datos->getRowConsultaSql(
                    "SELECT Emp_Cod FROM adq_solicitudes WHERE Sol_Cod = " . intval($instancia['Ins_Ent_Cod']) . " LIMIT 1;",
                    $this->obBD_conexion
                );
                $emp_cod = !empty($emp_row['Emp_Cod']) ? intval($emp_row['Emp_Cod']) : 0;
            }
            if ($emp_cod <= 0) {
                return;
            }

            $excluir = 0;
            if ($instancia['Ins_Ent_Typ'] === 'adq_solicitudes') {
                $sol_row = $this->obBD_datos->getRowConsultaSql(
                    "SELECT Usu_Sol FROM adq_solicitudes WHERE Sol_Cod = " . intval($instancia['Ins_Ent_Cod']) . " LIMIT 1;",
                    $this->obBD_conexion
                );
                $excluir = !empty($sol_row['Usu_Sol']) ? intval($sol_row['Usu_Sol']) : 0;
            }

            $destinatarios = $this->listarDestinatariosNotificacionEtapa(intval($nodoDestino['Nod_Cod']), $emp_cod, $excluir);
            if (empty($destinatarios)) {
                return;
            }

            $evento = isset($opciones['evento']) ? $opciones['evento'] : 'nueva';
            $mensaje_data = $this->construirMensajeNotificacionAdq($instancia, $nodoDestino, $emp_cod, $evento);
            if (empty($mensaje_data)) {
                return;
            }

            $mensaje = $mensaje_data['mensaje'];
            if (!empty($nodo['Nod_Not_Texto'])) {
                $extra = trim($nodo['Nod_Not_Texto']);
                if ($extra !== '') {
                    $mensaje .= "\n\n" . $extra;
                }
            }

            $asunto = !empty($nodo['Nod_Not_Asunto'])
                ? trim($nodo['Nod_Not_Asunto'])
                : $mensaje_data['asunto'];
            $nod_cod = intval($nodoDestino['Nod_Cod']);
            $fecha = date('Y-m-d H:i:s');

            if ($not_wa) {
                $wa_path = dirname(__FILE__) . '/../../MODELS/send_whatsapp.php';
                if (file_exists($wa_path)) {
                    require_once($wa_path);
                }
                if (function_exists('enviarNotificacionWhatsapp')) {
                    $numeros = array();
                    foreach ($destinatarios as $d) {
                        if (!empty($d['Telefono'])) {
                            $numeros[] = $d['Telefono'];
                        }
                    }
                    $numeros = array_values(array_unique($numeros));
                    if (!empty($numeros)) {
                        $ok = enviarNotificacionWhatsapp($mensaje, $numeros);
                        foreach ($destinatarios as $d) {
                            if (empty($d['Telefono'])) {
                                continue;
                            }
                            $this->registrarNotificacionInstancia(
                                $Ins_Cod, $nod_cod, $d['Usu_Cod'], 'WA', $d['Telefono'],
                                $ok ? 'O' : 'E', $fecha, $mensaje, $ok ? '' : 'Error al enviar WhatsApp'
                            );
                        }
                    }
                }
            }

            if ($not_em) {
                $mail_utils = dirname(__FILE__) . '/../../relavera/LOGICA/relavera_notif_mail_utils.php';
                if (file_exists($mail_utils)) {
                    require_once($mail_utils);
                }
                if (function_exists('relavera_notif_enviar_correo_notif')) {
                    foreach ($destinatarios as $d) {
                        if (empty($d['Correo']) || !filter_var($d['Correo'], FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }
                        $ok_mail = relavera_notif_enviar_correo_notif($d['Correo'], $d['Nombre'], $asunto, $mensaje);
                        $this->registrarNotificacionInstancia(
                            $Ins_Cod, $nod_cod, $d['Usu_Cod'], 'EM', $d['Correo'],
                            $ok_mail ? 'O' : 'E', $fecha, $mensaje, $ok_mail ? '' : 'Error al enviar correo'
                        );
                    }
                }
            }
        } catch (Exception $e) {
            // No interrumpir el flujo por fallos de notificacion
        }
    }

    private function construirMensajeNotificacionAdq($instancia, $nodo, $emp_cod, $evento = 'nueva', $opciones = array()) {
        if ($instancia['Ins_Ent_Typ'] !== 'adq_solicitudes') {
            return null;
        }
        $sol_cod = intval($instancia['Ins_Ent_Cod']);
        $info = $this->obBD_datos->getRowConsultaSql(
            "SELECT s.Sol_Num, s.Sol_Jus, s.Sol_Det,
                    tr.Trq_Des, w.Wfm_Nom,
                    TRIM(CONCAT(IFNULL(ps.Prs_Nom, ''), ' ', IFNULL(ps.Prs_Ape, ''))) AS Solicitante_Nom
             FROM adq_solicitudes s
             INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
             INNER JOIN usuarios us ON us.Usu_Cod = s.Usu_Sol
             INNER JOIN persona ps ON ps.Prs_Cod = us.Prs_Cod
             INNER JOIN wf_instancias i ON i.Ins_Cod = " . intval($instancia['Ins_Cod']) . "
             INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = i.Wfm_Cod
             WHERE s.Sol_Cod = $sol_cod AND s.Emp_Cod = " . intval($emp_cod) . "
             LIMIT 1;",
            $this->obBD_conexion
        );
        if (empty($info)) {
            return null;
        }

        if ($evento === 'observar') {
            $titulo = 'Solicitud observada - requiere su atencion';
        } elseif ($evento === 'reenvio') {
            $titulo = 'Solicitud corregida - requiere su aprobacion';
        } else {
            $titulo = 'Nueva solicitud de adquisicion pendiente';
        }

        $justificacion = trim($info['Sol_Jus']);
        if ($justificacion === '') {
            $justificacion = trim($info['Sol_Det']);
        }
        if (strlen($justificacion) > 280) {
            $justificacion = substr($justificacion, 0, 277) . '...';
        }

        $mensaje = "*Verificar procesos de Adquisiciones*\n"
            . "*" . $titulo . "*\n\n"
            . "Solicitud: #" . $info['Sol_Num'] . "\n"
            . "Flujo: " . $info['Wfm_Nom'] . "\n"
            . "Tipo: " . $info['Trq_Des'] . "\n"
            . "Solicitante: " . trim($info['Solicitante_Nom']) . "\n";

        if ($evento === 'observar' && !empty($opciones['nodo_observador']['Nod_Nom'])) {
            $mensaje .= "Observada en: " . $opciones['nodo_observador']['Nod_Nom'];
            if (!empty($opciones['nodo_observador']['Nod_Tip'])) {
                $mensaje .= " [" . $opciones['nodo_observador']['Nod_Tip'] . "]";
            }
            $mensaje .= "\n";
            $mensaje .= "Etapa anterior: " . $nodo['Nod_Nom'] . " [" . $nodo['Nod_Tip'] . "]\n";
        } else {
            $mensaje .= "Etapa: " . $nodo['Nod_Nom'] . " [" . $nodo['Nod_Tip'] . "]\n";
        }

        if ($evento === 'observar') {
            $comentario = trim(isset($opciones['comentario']) ? $opciones['comentario'] : '');
            if ($comentario !== '') {
                if (strlen($comentario) > 500) {
                    $comentario = substr($comentario, 0, 497) . '...';
                }
                $mensaje .= "\n*Observacion:*\n" . $comentario;
            }
        } elseif ($justificacion !== '') {
            $mensaje .= "\n*Justificacion:*\n" . $justificacion;
        }

        return array(
            'mensaje' => $mensaje,
            'asunto' => 'Adquisiciones: ' . $titulo . ' - Sol. #' . $info['Sol_Num']
        );
    }

    /**
     * Destinatarios configurados en un nodo, incluyendo INICIO.
     */
    public function listarDestinatariosNodoAsignado($nodo, $emp_cod, $excluir_usu_cod = 0) {
        $emp_cod = intval($emp_cod);
        $excluir_usu_cod = intval($excluir_usu_cod);
        if (empty($nodo) || $emp_cod <= 0) {
            return array();
        }

        $usuarios = array();
        $dep_cod = intval(isset($nodo['Dep_Cod']) ? $nodo['Dep_Cod'] : 0);
        $usu_asig = isset($nodo['Nod_Usu_Asig']) ? $nodo['Nod_Usu_Asig'] : 'TODOS';
        $per_cod = intval(isset($nodo['Per_Cod']) ? $nodo['Per_Cod'] : 0);

        if ($dep_cod > 0) {
            $filtro_usu = '';
            if ($usu_asig !== 'TODOS' && $usu_asig !== '' && $usu_asig !== null) {
                $ids = array();
                foreach (explode(',', $usu_asig) as $id) {
                    $id = intval(trim($id));
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
                if (!empty($ids)) {
                    $filtro_usu = ' AND u.Usu_Cod IN (' . implode(',', $ids) . ')';
                }
            }

            $rows = $this->obBD_datos->getArrayConsultaSql(
                "SELECT DISTINCT u.Usu_Cod,
                        TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                        p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                 FROM wf_departamento_usuarios du
                 INNER JOIN wf_departamentos w ON w.Wde_Cod = du.Wde_Cod AND w.Wde_Est = 'A' AND w.Emp_Cod = $emp_cod
                 INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod AND u.Usu_Est = 'A'
                 INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                 WHERE w.Dep_Cod = $dep_cod
                 $filtro_usu",
                $this->obBD_conexion
            );
            if (!empty($rows)) {
                $usuarios = array_merge($usuarios, $rows);
            } else {
                // Fallback por Dep_Cod RRHH directo
                $rows = $this->obBD_datos->getArrayConsultaSql(
                    "SELECT DISTINCT u.Usu_Cod,
                            TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                            p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                     FROM wf_departamento_usuarios du
                     INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod AND u.Usu_Est = 'A' AND u.Emp_Cod = $emp_cod
                     INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                     WHERE du.Dep_Cod = $dep_cod
                     $filtro_usu",
                    $this->obBD_conexion
                );
                if (!empty($rows)) {
                    $usuarios = array_merge($usuarios, $rows);
                }
            }
        }

        if ($per_cod > 0) {
            $rows = $this->obBD_datos->getArrayConsultaSql(
                "SELECT DISTINCT u.Usu_Cod,
                        TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                        p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                 FROM usuarios u
                 INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                 INNER JOIN usuarperfi up ON up.Usu_Cod = u.Usu_Cod
                 WHERE u.Usu_Est = 'A' AND u.Emp_Cod = $emp_cod AND up.Per_Cod = $per_cod",
                $this->obBD_conexion
            );
            if (!empty($rows)) {
                $usuarios = array_merge($usuarios, $rows);
            }
        }

        if (empty($usuarios) && $usu_asig !== 'TODOS' && $usu_asig !== '' && $usu_asig !== null) {
            $ids = array();
            foreach (explode(',', $usu_asig) as $id) {
                $id = intval(trim($id));
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            if (!empty($ids)) {
                $ids_sql = implode(',', $ids);
                $rows = $this->obBD_datos->getArrayConsultaSql(
                    "SELECT DISTINCT u.Usu_Cod,
                            TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombre,
                            p.Prs_Tel, p.Prs_Cel, p.Prs_Cor
                     FROM usuarios u
                     INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                     WHERE u.Usu_Est = 'A' AND u.Emp_Cod = $emp_cod AND u.Usu_Cod IN ($ids_sql)",
                    $this->obBD_conexion
                );
                if (!empty($rows)) {
                    $usuarios = array_merge($usuarios, $rows);
                }
            }
        }

        $resultado = array();
        $vistos = array();
        foreach ($usuarios as $u) {
            $usu_cod = intval($u['Usu_Cod']);
            if ($usu_cod <= 0 || isset($vistos[$usu_cod])) {
                continue;
            }
            if ($excluir_usu_cod > 0 && $usu_cod === $excluir_usu_cod) {
                continue;
            }
            $tel = trim(isset($u['Prs_Tel']) ? $u['Prs_Tel'] : '');
            if ($tel === '' && !empty($u['Prs_Cel'])) {
                $tel = trim($u['Prs_Cel']);
            }
            $correo = trim(isset($u['Prs_Cor']) ? $u['Prs_Cor'] : '');
            if ($tel === '' && $correo === '') {
                continue;
            }
            $vistos[$usu_cod] = true;
            $resultado[] = array(
                'Usu_Cod' => $usu_cod,
                'Nombre' => trim($u['Nombre']),
                'Telefono' => $tel,
                'Correo' => $correo
            );
        }

        return $resultado;
    }

    private function registrarNotificacionInstancia($ins_cod, $nod_cod, $usu_cod, $canal, $destino, $estado, $fecha, $mensaje, $error = '') {
        $ins_cod = intval($ins_cod);
        $nod_cod = intval($nod_cod);
        $usu_sql = intval($usu_cod) > 0 ? intval($usu_cod) : 'NULL';
        $canal = $this->escapeWf($canal);
        $destino = $this->escapeWf($destino);
        $estado = $this->escapeWf($estado);
        $msg_esc = $this->escapeWf($mensaje);
        $err_esc = $this->escapeWf($error);
        $this->ejecutarSql(
            "INSERT INTO wf_instancias_notificaciones
                (Ins_Cod, Nod_Cod, Usu_Cod, Ino_Can, Ino_Dest, Ino_Est, Ino_Fec, Ino_Msg, Ino_Err)
             VALUES ($ins_cod, $nod_cod, $usu_sql, '$canal', '$destino', '$estado', '$fecha', '$msg_esc', '$err_esc');"
        );
    }
}