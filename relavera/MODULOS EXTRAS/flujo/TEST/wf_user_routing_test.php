<?php
/**
 * EXA Workflow Manager - Prueba de Integración de Enrutamiento Dinámico por Usuario/Departamento
 * 
 * Este script valida que las solicitudes se enruten correctamente según las asignaciones
 * personalizadas de departamentos y usuarios, y que la bandeja de entrada filtre
 * adecuadamente para cada usuario.
 * 
 * Ejecución desde CLI: php flujo/TEST/wf_user_routing_test.php
 */

define('TEST_MODE', true);

if (!class_exists('DebugBar')) {
    class DebugBar {
        public static function startQueryMeasure() {}
        public static function addQuery($sql, $data) {}
        public static function addTransactionEvent($event, $data) {}
        public static function addQueryComment($comment, $data) {}
    }
}
if (!class_exists('ChromePhp')) {
    class ChromePhp {
        public static function log() {}
    }
}

if (session_id() === '') session_start();
$_SESSION['Ses_Emp_Cod'] = 1;
$_SESSION['Ses_Suc_Cod'] = 1;
$_SESSION['Ses_Prs_Cod'] = 1;
$_SESSION['Ses_Dat_Dis'] = 'exa';

require_once(dirname(__FILE__) . '/../LOGICA/wf_manager_log.php');
require_once(dirname(__FILE__) . '/../LOGICA/adq_adquisiciones_log.php');

class EXAUserRoutingTestSuite {
    protected $obBD_conexion;
    protected $obBD_datos;
    protected $wf_mgr;
    protected $adq_log;

    public function __construct() {
        $this->wf_mgr = new wf_manager_log('exa');
        $this->obBD_conexion = $this->wf_mgr->obBD_conexion;
        $this->obBD_datos = $this->wf_mgr->obBD_datos;
        $this->adq_log = new adq_adquisiciones_log($this->obBD_conexion);
    }

    public function run() {
        echo "========================================================================\n";
        echo " EXA WORKFLOW - PRUEBA DE ENRUTAMIENTO DINÁMICO POR USUARIO/DEPARTAMENTO\n";
        echo "========================================================================\n\n";

        try {
            $this->setupTestData();
            $this->testRoutingAndBandejaFiltering();
            $this->testActionValidationSecurity();
            $this->cleanupTestData();

            echo "\n========================================================================\n";
            echo " ? TODAS LAS PRUEBAS DE ENRUTAMIENTO DINÁMICO PASARON CON ÉXITO!\n";
            echo "========================================================================\n";
        } catch (Exception $e) {
            echo "\n? ERROR DURANTE LA EJECUCIÓN DE LAS PRUEBAS:\n";
            echo "   Mensaje: " . $e->getMessage() . "\n";
            echo "   Línea:   " . $e->getLine() . "\n";
            echo "========================================================================\n";
            $this->cleanupTestData();
            exit(1);
        }
    }

    protected function setupTestData() {
        echo ">> Preparando datos de prueba...\n";

        // 1. Limpiar datos previos de prueba
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias_nodos WHERE Ins_Cod IN (SELECT Ins_Cod FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing');");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing';");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_conexiones WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_nodos WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_flujos_modelos WHERE Wfm_Cod = 999;");

        // 2. Crear departamento de prueba si no existe
        $dep_check = $this->obBD_datos->getRowConsultaSql("SELECT * FROM departamen WHERE Dep_Cod = 999;", $this->obBD_conexion);
        if (empty($dep_check)) {
            mysqli_query($this->obBD_conexion->conexion, "INSERT INTO departamen (Dep_Cod, Emp_Cod, Dep_Des, Dep_Est) VALUES (999, 1, 'DEP_TEST_ROUTING', 'A');");
        }

        // 3. Crear mapeo de departamento-usuario para el Workflow
        // Asignamos Usu_Cod = 3 (Francisco) y Usu_Cod = 4 (Kleber) al departamento 999
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod) VALUES (999, 3);");
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod) VALUES (999, 4);");

        // 4. Crear un flujo modelo de prueba (ID 999)
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_flujos_modelos (Wfm_Cod, Emp_Cod, Wfm_Nom, Wfm_Des, Wfm_Est) VALUES (999, 1, 'Flujo de Prueba de Enrutamiento', 'Para validar asignación por usuario', 'A');");

        // 5. Crear nodos del flujo
        // Nodo 1: Inicio
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_nodos (Nod_Cod, Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Nod_Est) VALUES (991, 999, 'INICIO', 'Inicio', 'Inicio del flujo', 'A');");
        // Nodo 2: Aprobación por Usuario 3 únicamente
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_nodos (Nod_Cod, Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Dep_Cod, Nod_Usu_Asig, Nod_Est) VALUES (992, 999, 'APROBACION', 'Aprobación Especial', 'Solo aprobable por usuario 3', 999, '3', 'A');");
        // Nodo 3: Fin
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_nodos (Nod_Cod, Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Nod_Est) VALUES (993, 999, 'FIN', 'Fin', 'Fin del flujo', 'A');");

        // 6. Crear conexiones
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_conexiones (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc) VALUES (999, 991, 992, 'APROBAR');");
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_conexiones (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc) VALUES (999, 992, 993, 'APROBAR');");

        echo "   ? Datos de prueba creados con éxito.\n";
    }

    protected function testRoutingAndBandejaFiltering() {
        echo ">> Ejecutando Test de Filtrado de Bandeja de Entrada...\n";

        // 1. Iniciar una instancia del workflow en el nodo 992
        $res_init = $this->wf_mgr->iniciarInstancia(999, 'test_routing', 1001);
        $this->assert($res_init['success'], "No se pudo iniciar la instancia del workflow: " . (isset($res_init['message']) ? $res_init['message'] : ''));
        $ins_cod = $res_init['Ins_Cod'];

        // Verificar que la instancia esté en el nodo activo 992
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $this->obBD_conexion);
        $this->assert($instancia['Nod_Act'] == 992, "La instancia no está en el nodo activo esperado (992). Está en: " . $instancia['Nod_Act']);

        // 2. Simular consulta de bandeja para Usuario 3 (Asignado específicamente al nodo)
        $usu_cod = 3;
        $dep_cod = 999;
        $perfiles_ids = "1,2";

        $sql_pendientes = "
            SELECT i.Ins_Cod
            FROM wf_instancias i
            INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act AND (
                (
                    n.Dep_Cod IN (SELECT Dep_Cod FROM wf_departamento_usuarios WHERE Usu_Cod = $usu_cod)
                    AND (n.Nod_Usu_Asig IS NULL OR n.Nod_Usu_Asig = 'TODOS' OR n.Nod_Usu_Asig = '' OR FIND_IN_SET($usu_cod, n.Nod_Usu_Asig) > 0)
                )
                OR (n.Dep_Cod = $dep_cod)
            )
            WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P';";

        $res_u3 = $this->obBD_datos->getArrayConsultaSql($sql_pendientes, $this->obBD_conexion);
        $this->assert(count($res_u3) == 1, "El Usuario 3 DEBERÍA ver la tarea pendiente.");
        echo "   ? Usuario 3 (asignado específicamente) ve la tarea correctamente.\n";

        // 3. Simular consulta de bandeja para Usuario 4 (En el mismo depto pero NO asignado al nodo)
        $usu_cod = 4;
        $sql_pendientes_u4 = "
            SELECT i.Ins_Cod
            FROM wf_instancias i
            INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act AND (
                (
                    n.Dep_Cod IN (SELECT Dep_Cod FROM wf_departamento_usuarios WHERE Usu_Cod = $usu_cod)
                    AND (n.Nod_Usu_Asig IS NULL OR n.Nod_Usu_Asig = 'TODOS' OR n.Nod_Usu_Asig = '' OR FIND_IN_SET($usu_cod, n.Nod_Usu_Asig) > 0)
                )
            )
            WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P';";

        $res_u4 = $this->obBD_datos->getArrayConsultaSql($sql_pendientes_u4, $this->obBD_conexion);
        $this->assert(count($res_u4) == 0, "El Usuario 4 NO debería ver la tarea ya que está asignada exclusivamente al Usuario 3.");
        echo "   ? Usuario 4 (no asignado) NO ve la tarea correctamente.\n";

        // 4. Cambiar la asignación del nodo a 'TODOS'
        mysqli_query($this->obBD_conexion->conexion, "UPDATE wf_nodos SET Nod_Usu_Asig = 'TODOS' WHERE Nod_Cod = 992;");

        // Ahora ambos usuarios deberían ver la tarea
        $res_u4_todos = $this->obBD_datos->getArrayConsultaSql($sql_pendientes_u4, $this->obBD_conexion);
        $this->assert(count($res_u4_todos) == 1, "El Usuario 4 DEBERÍA ver la tarea ahora que está asignada a 'TODOS'.");
        echo "   ? Usuario 4 ve la tarea cuando la asignación cambia a 'TODOS'.\n";
    }

    protected function testActionValidationSecurity() {
        echo ">> Ejecutando Test de Validación de Seguridad en Acciones...\n";

        // Obtener la instancia activa
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing' AND Ins_Est = 'P' LIMIT 1;", $this->obBD_conexion);
        $ins_cod = $instancia['Ins_Cod'];

        // Volver a poner la asignación exclusiva al Usuario 3
        mysqli_query($this->obBD_conexion->conexion, "UPDATE wf_nodos SET Nod_Usu_Asig = '3' WHERE Nod_Cod = 992;");

        // Simular que el Usuario 4 intenta procesar la acción
        $usu_cod_u4 = 4;
        $dep_cod_u4 = 999;
        $perfiles_ids_u4 = "2";

        // Ejecutar la misma consulta de validación de adq_bandeja.php
        $check_perm_u4 = $this->obBD_datos->getRowConsultaSql("
            SELECT n.Nod_Cod 
            FROM wf_instancias i
            INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
            WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P' AND (
                (
                    n.Dep_Cod IN (SELECT Dep_Cod FROM wf_departamento_usuarios WHERE Usu_Cod = $usu_cod_u4)
                    AND (n.Nod_Usu_Asig IS NULL OR n.Nod_Usu_Asig = 'TODOS' OR n.Nod_Usu_Asig = '' OR FIND_IN_SET($usu_cod_u4, n.Nod_Usu_Asig) > 0)
                )
            );", $this->obBD_conexion);

        $this->assert(empty($check_perm_u4), "La validación de seguridad falló: permitió el acceso al Usuario 4 no asignado.");
        echo "   ? Validación de seguridad bloquea correctamente al Usuario 4.\n";

        // Simular que el Usuario 3 intenta procesar la acción
        $usu_cod_u3 = 3;
        $check_perm_u3 = $this->obBD_datos->getRowConsultaSql("
            SELECT n.Nod_Cod 
            FROM wf_instancias i
            INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
            WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P' AND (
                (
                    n.Dep_Cod IN (SELECT Dep_Cod FROM wf_departamento_usuarios WHERE Usu_Cod = $usu_cod_u3)
                    AND (n.Nod_Usu_Asig IS NULL OR n.Nod_Usu_Asig = 'TODOS' OR n.Nod_Usu_Asig = '' OR FIND_IN_SET($usu_cod_u3, n.Nod_Usu_Asig) > 0)
                )
            );", $this->obBD_conexion);

        $this->assert(!empty($check_perm_u3), "La validación de seguridad falló: bloqueó al Usuario 3 asignado.");
        echo "   ? Validación de seguridad autoriza correctamente al Usuario 3.\n";
    }

    protected function cleanupTestData() {
        echo ">> Limpiando datos de prueba...\n";
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias_nodos WHERE Ins_Cod IN (SELECT Ins_Cod FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing');");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing';");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_conexiones WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_nodos WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_flujos_modelos WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM departamen WHERE Dep_Cod = 999;");
        echo "   ? Limpieza completada.\n";
    }

    protected function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }
}

$suite = new EXAUserRoutingTestSuite();
$suite->run();
?>
