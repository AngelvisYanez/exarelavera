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

    public function resolverContextoUsuario($emp_cod = null) {
        $usu_cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
        $dep_cod = isset($_SESSION['Ses_Dep_Cod']) ? intval($_SESSION['Ses_Dep_Cod']) : 0;
        $emp_cod = $emp_cod !== null ? intval($emp_cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);

        if ($dep_cod <= 0 && $usu_cod > 0 && $emp_cod > 0) {
            $dep_row = $this->obBD_datos->getRowConsultaSql(
                "SELECT MIN(du.Dep_Cod) AS Dep_Cod
                 FROM wf_departamento_usuarios du
                 INNER JOIN departamen d ON d.Dep_Cod = du.Dep_Cod AND d.Emp_Cod = $emp_cod
                 WHERE du.Usu_Cod = $usu_cod",
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

        $nodo_usu_visible = "(n.Nod_Usu_Asig IS NULL OR n.Nod_Usu_Asig = 'TODOS' OR n.Nod_Usu_Asig = '' OR FIND_IN_SET($usu_cod, n.Nod_Usu_Asig) > 0)";

        return "(
            $nodo_usu_visible
            AND (
                n.Dep_Cod IN (SELECT Dep_Cod FROM wf_departamento_usuarios WHERE Usu_Cod = $usu_cod)
                OR $dep_sesion_sql
                OR n.Per_Cod IN ($perfiles_ids)
            )
        )";
    }

    private $versioningReady = false;

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
            $dep_cod = !empty($nodo['dep_cod']) ? intval($nodo['dep_cod']) : 'NULL';
            $per_cod = !empty($nodo['per_cod']) ? intval($nodo['per_cod']) : 'NULL';
            $sla = (isset($nodo['sla']) && $nodo['sla'] !== '' && $nodo['sla'] !== null) ? intval($nodo['sla']) : 'NULL';
            $com_obl = !empty($nodo['com_obl']) ? 1 : 0;
            $adj_obl = !empty($nodo['adj_obl']) ? 1 : 0;
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
                        Nod_Com_Obl = $com_obl, Nod_Adj_Obl = $adj_obl,
                        Nod_Vis_X = $vis_x, Nod_Vis_Y = $vis_y, Nod_Usu_Asig = $usu_asig, Nod_Est = 'A'
                     WHERE Nod_Cod = $nod_cod AND Wfm_Cod = $wfm_cod;"
                );
            } else {
                $this->ejecutarSql(
                    "INSERT INTO wf_nodos (Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Dep_Cod, Per_Cod, Nod_Sla, Nod_Com_Obl, Nod_Adj_Obl, Nod_Vis_X, Nod_Vis_Y, Nod_Est, Nod_Usu_Asig)
                     VALUES ($wfm_cod, '$nod_tip', '$nod_nom', '$nod_des', $dep_cod, $per_cod, $sla, $com_obl, $adj_obl, $vis_x, $vis_y, 'A', $usu_asig);"
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
                $con_con_exp = !empty($con['condicion']) ? "'" . $this->escapeWf(json_encode($con['condicion'])) . "'" : 'NULL';
                $this->ejecutarSql(
                    "INSERT INTO wf_conexiones (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc, Con_Con_Exp)
                     VALUES ($wfm_cod, $nod_ori, $nod_des, '$con_acc', $con_con_exp);"
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
    public function avanzarSiguientePaso($Ins_Cod, $Nod_Actual_Cod, $Accion, $Comentario = '', $Adjuntos = null) {
        $fecha_actual = date('Y-m-d H:i:s');
        $ip_usuario = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $session_id = session_id() ?: 'CLI-SESSION';
        $fk_ctx = $this->resolverFkHistorial(
            isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : 0,
            isset($_SESSION['Ses_Dep_Cod']) ? $_SESSION['Ses_Dep_Cod'] : 0
        );
        $usu_cod = $fk_ctx['usu'];
        $dep_cod = $fk_ctx['dep'];

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

        // 3. Evaluar conexiones para decidir a qu� nodo avanzar
        $nodoDestino_Cod = null;
        $conexionDefault = null;

        foreach ($conexiones as $conexion) {
            if ($conexion['Con_Acc'] == 'CONDICIONAL' && !empty($conexion['Con_Con_Exp'])) {
                // Nodo decisi�n con condici�n espec�fica
                if ($this->evaluarCondicion($conexion['Con_Con_Exp'], $instancia['Ins_Ent_Typ'], $instancia['Ins_Ent_Cod'])) {
                    $nodoDestino_Cod = $conexion['Nod_Des'];
                    break;
                }
            } elseif ($conexion['Con_Acc'] == 'APROBAR' && empty($conexion['Con_Con_Exp']) && $Accion == 'CONDICIONAL') {
                // Rama por defecto/Else en un nodo decisi�n
                $conexionDefault = $conexion['Nod_Des'];
            } elseif ($conexion['Con_Acc'] == $Accion) {
                // Conexi�n directa seg�n acci�n ejecutada
                $nodoDestino_Cod = $conexion['Nod_Des'];
                break;
            }
        }

        if ($nodoDestino_Cod === null && $conexionDefault !== null) {
            $nodoDestino_Cod = $conexionDefault;
        }

        // Si no se cumpli� ninguna condici�n de decisi�n o acci�n coincidente, tomamos la primera por defecto
        if ($nodoDestino_Cod === null && count($conexiones) > 0) {
            $nodoDestino_Cod = $conexiones[0]['Nod_Des'];
        }

        // 4. Obtener informaci�n del nodo destino
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

        // 6. Actualizar instancia de flujo al nuevo nodo actual
        $this->ejecutarSql("UPDATE wf_instancias SET Nod_Act = $nodoDestino_Cod WHERE Ins_Cod = $Ins_Cod;");

        // 7. Escribir en el historial del nodo destino
        $adjunto_str = $Adjuntos !== null ? "'" . $Adjuntos . "'" : "NULL";
        $sqlInsertHistorialDest = "INSERT INTO wf_instancias_nodos (Ins_Cod, Nod_Cod, Usu_Cod, Dep_Cod, Isn_Acc, Isn_Com, Isn_Adj, Isn_Fec, Isn_Sla_Ven, Isn_Ip, Isn_Ses) 
                                   VALUES ($Ins_Cod, $nodoDestino_Cod, $usu_cod, $dep_cod, '$Accion', '" . mysqli_real_escape_string($this->obBD_conexion->conexion, $Comentario) . "', $adjunto_str, '$fecha_actual', $sla_vencimiento, '$ip_usuario', '$session_id');";
        $this->ejecutarSql($sqlInsertHistorialDest);

        // 8. Si el nuevo nodo es un Nodo de Decisi�n o de Notificaci�n, se procesa autom�ticamente
        if ($nodoDestino['Nod_Tip'] == 'DECISION') {
            return $this->avanzarSiguientePaso($Ins_Cod, $nodoDestino_Cod, 'CONDICIONAL', 'Avance autom' . "\xC3\xA1" . 'tico por nodo decisi' . "\xC3\xB3" . 'n.', null);
        } elseif ($nodoDestino['Nod_Tip'] == 'NOTIFICACION') {
            $this->enviarNotificacionNodo($nodoDestino, $instancia);
            return $this->avanzarSiguientePaso($Ins_Cod, $nodoDestino_Cod, 'COMPLETAR', 'Avance autom' . "\xC3\xA1" . 'tico tras notificaci' . "\xC3\xB3" . 'n.', null);
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
     * Eval�a expresiones condicionales din�micas basadas en JSON
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

    /**
     * Ejecuta una acci�n manual de usuario (Aprobar, Rechazar, Observar, Devolver)
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
                throw new Exception("La etapa actual del flujo no es v�lida.");
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

            // Actualizar estado intermedio de la solicitud seg�n la acci�n
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
             INNER JOIN departamen d ON d.Dep_Cod = du.Dep_Cod
             INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod AND u.Usu_Est = 'A'
             INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             WHERE d.Dep_Cod = $dep_cod
                OR d.Dep_Des = (SELECT Dep_Des FROM departamen WHERE Dep_Cod = $dep_cod LIMIT 1)
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

    public function getVisualFlowData($Ins_Cod) {
        $Ins_Cod = intval($Ins_Cod);
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $Ins_Cod;", $this->obBD_conexion);
        if (empty($instancia)) {
            return array('nodos' => array(), 'conexiones' => array(), 'nodo_actual' => null);
        }

        $nodos = $this->obBD_datos->getArrayConsultaSql("SELECT * FROM wf_nodos WHERE Wfm_Cod = {$instancia['Wfm_Cod']} AND Nod_Est = 'A' ORDER BY Nod_Vis_X ASC, Nod_Cod ASC;", $this->obBD_conexion);
        
        // Obtener todos los pasos del historial ejecutados para esta instancia
        $pasos_ejecutados = $this->obBD_datos->getArrayConsultaSql("SELECT Nod_Cod, Isn_Acc FROM wf_instancias_nodos WHERE Ins_Cod = $Ins_Cod ORDER BY Isn_Fec ASC;", $this->obBD_conexion);
        
        $nodos_visitados = array();
        foreach ($pasos_ejecutados as $paso) {
            $nodos_visitados[intval($paso['Nod_Cod'])] = $paso['Isn_Acc'];
        }

        $historial_actores = $this->obBD_datos->getArrayConsultaSql(
            "SELECT h.Nod_Cod, h.Isn_Acc, h.Isn_Fec,
                    COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
                    TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom,
                    COALESCE(wd.Wde_Des, dep.Dep_Des) AS Dep_Des
             FROM wf_instancias_nodos h
             LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
             LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
             LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
             LEFT JOIN wf_departamentos wd ON wd.Wde_Cod = h.Dep_Cod
             LEFT JOIN departamen dep ON dep.Dep_Cod = h.Dep_Cod
             WHERE h.Ins_Cod = $Ins_Cod
             ORDER BY h.Isn_Fec ASC;",
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
            $es_pendiente = ($color === 'blue' && empty($actor['usuario']) && in_array($nodo['Nod_Tip'], array('APROBACION', 'RECEPCION', 'FACTURA', 'DECISION'), true));
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
                'x' => $nodo['Nod_Vis_X'],
                'y' => $nodo['Nod_Vis_Y'],
                'usuario' => $actor['usuario'],
                'accion' => $actor['accion'],
                'actor_label' => $es_pendiente ? '' : $this->formatActorEtapa($actor['accion'], $actor['usuario'], false),
                'pendiente_meta' => $pendiente_meta
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

    /**
     * Asegura que exista la tabla wf_departamentos del modulo Workflow.
     */
    public function ensureWfDepartamentosTable() {
        $sql = "CREATE TABLE IF NOT EXISTS wf_departamentos (
            Wde_Cod INT NOT NULL AUTO_INCREMENT,
            Emp_Cod INT NOT NULL,
            Wde_Des VARCHAR(150) NOT NULL,
            Wde_Est CHAR(1) NOT NULL DEFAULT 'A',
            PRIMARY KEY (Wde_Cod),
            KEY idx_wf_dep_emp (Emp_Cod)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $this->obBD_datos->grabarv_registros($sql, $this->obBD_conexion);
    }

    /**
     * Importa departamentos activos de RRHH (departamen) a wf_departamentos.
     * Usa Dep_Cod de RRHH como Wde_Cod para mantener asignaciones de usuarios.
     */
    public function syncDepartamentosFromRrhh($emp_id) {
        $this->ensureWfDepartamentosTable();
        $emp_id = intval($emp_id);
        if ($emp_id <= 0) {
            return 0;
        }

        $sql = "INSERT INTO wf_departamentos (Wde_Cod, Emp_Cod, Wde_Des, Wde_Est)
                SELECT MIN(d.Dep_Cod), d.Emp_Cod, d.Dep_Des, 'A'
                FROM departamen d
                LEFT JOIN wf_departamentos w ON w.Emp_Cod = d.Emp_Cod AND w.Wde_Des = d.Dep_Des
                WHERE d.Emp_Cod = $emp_id AND d.Dep_Est = 'A' AND w.Wde_Cod IS NULL
                GROUP BY d.Emp_Cod, d.Dep_Des";

        $this->obBD_datos->grabarv_registros($sql, $this->obBD_conexion);
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
}