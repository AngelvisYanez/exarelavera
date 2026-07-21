<?php
/**
 * EXA Workflow Manager - Prueba de Integraci�n de Enrutamiento Din�mico por Usuario/Departamento
 * 
 * Este script valida que las solicitudes se enruten correctamente seg�n las asignaciones
 * personalizadas de departamentos y usuarios, y que la bandeja de entrada filtre
 * adecuadamente para cada usuario.
 * 
 * Ejecuci�n desde CLI: php flujo/TEST/wf_user_routing_test.php
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
$_SESSION['Ses_Emp_Cod'] = 620;
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
    protected $duplicateWfmCod = 0;

    public function __construct() {
        $this->wf_mgr = new wf_manager_log('exa');
        $this->obBD_conexion = $this->wf_mgr->obBD_conexion;
        mysqli_select_db($this->obBD_conexion->conexion, 'ecoparkmining');
        $this->obBD_datos = $this->wf_mgr->obBD_datos;
        $this->adq_log = new adq_adquisiciones_log($this->obBD_conexion);
    }

    public function run() {
        echo "========================================================================\n";
        echo " EXA WORKFLOW - PRUEBA DE ENRUTAMIENTO DIN�MICO POR USUARIO/DEPARTAMENTO\n";
        echo "========================================================================\n\n";

        try {
            $this->setupTestData();
            $this->testRoutingAndBandejaFiltering();
            $this->testCalculoSlaPorNodo();
            $this->testActionValidationSecurity();
            $this->testNotificacionFinalEsquema();
            $this->testDuplicarEsquema();
            $this->cleanupTestData();

            echo "\n========================================================================\n";
            echo " ? TODAS LAS PRUEBAS DE ENRUTAMIENTO DIN�MICO PASARON CON �XITO!\n";
            echo "========================================================================\n";
        } catch (Exception $e) {
            echo "\n? ERROR DURANTE LA EJECUCI�N DE LAS PRUEBAS:\n";
            echo "   Mensaje: " . $e->getMessage() . "\n";
            echo "   L�nea:   " . $e->getLine() . "\n";
            echo "========================================================================\n";
            $this->cleanupTestData();
            exit(1);
        }
    }

    protected function setupTestData() {
        echo ">> Preparando datos de prueba...\n";

        // 1. Limpiar datos previos de prueba
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = 999 OR Wde_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_departamentos WHERE Wde_Cod = 999 OR Dep_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias_nodos WHERE Ins_Cod IN (SELECT Ins_Cod FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing');");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing';");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_conexiones WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_nodos WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_flujos_modelos WHERE Wfm_Cod = 999;");

        // 2. Crear departamento de prueba si no existe
        $dep_check = $this->obBD_datos->getRowConsultaSql("SELECT * FROM departamen WHERE Dep_Cod = 999;", $this->obBD_conexion);
        if (empty($dep_check)) {
            mysqli_query($this->obBD_conexion->conexion, "INSERT INTO departamen (Dep_Cod, Emp_Cod, Dep_Des, Dep_Est) VALUES (999, 620, 'DEP_TEST_ROUTING', 'A');");
        }
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_departamentos (Wde_Cod, Emp_Cod, Wde_Des, Wde_Est, Dep_Cod) VALUES (999, 620, 'DEP_TEST_ROUTING', 'A', 999);");

        // 3. Crear mapeo de departamento-usuario para el Workflow
        // Asignamos Usu_Cod = 3 (Francisco) y Usu_Cod = 4 (Kleber) al departamento 999
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod, Wde_Cod) VALUES (999, 3, 999);");
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod, Wde_Cod) VALUES (999, 4, 999);");

        // 4. Crear un flujo modelo de prueba (ID 999)
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_flujos_modelos (Wfm_Cod, Emp_Cod, Wfm_Nom, Wfm_Des, Wfm_Est) VALUES (999, 620, 'Flujo de Prueba de Enrutamiento', 'Para validar asignaci�n por usuario', 'A');");

        // 5. Crear nodos del flujo
        // Nodo 1: Inicio
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_nodos (Nod_Cod, Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Nod_Est) VALUES (991, 999, 'INICIO', 'Inicio', 'Inicio del flujo', 'A');");
        // Nodo 2: Aprobaci�n por Usuario 3 �nicamente
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_nodos (Nod_Cod, Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Dep_Cod, Nod_Sla, Nod_Usu_Asig, Nod_Est) VALUES (992, 999, 'APROBACION', 'Aprobaci�n Especial', 'Solo aprobable por usuario 3', 999, 1, '3', 'A');");
        // Nodo 3: Fin
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_nodos (Nod_Cod, Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Nod_Usu_Asig, Nod_Not_Wa, Nod_Not_Em, Nod_Est) VALUES (993, 999, 'FIN', 'Fin', 'Fin del flujo', '4', 1, 0, 'A');");

        // 6. Crear conexiones
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_conexiones (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc) VALUES (999, 991, 992, 'APROBAR');");
        mysqli_query($this->obBD_conexion->conexion, "INSERT INTO wf_conexiones (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc) VALUES (999, 992, 993, 'APROBAR');");

        echo "   ? Datos de prueba creados con �xito.\n";
    }

    protected function testRoutingAndBandejaFiltering() {
        echo ">> Ejecutando Test de Filtrado de Bandeja de Entrada...\n";

        // 1. Iniciar una instancia del workflow en el nodo 992
        $res_init = $this->wf_mgr->iniciarInstancia(999, 'test_routing', 1001);
        $this->assert($res_init['success'], "No se pudo iniciar la instancia del workflow: " . (isset($res_init['message']) ? $res_init['message'] : ''));
        $ins_cod = $res_init['Ins_Cod'];

        // Verificar que la instancia est� en el nodo activo 992
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $this->obBD_conexion);
        $this->assert($instancia['Nod_Act'] == 992, "La instancia no est� en el nodo activo esperado (992). Est� en: " . $instancia['Nod_Act']);

        // 2. Simular consulta de bandeja para Usuario 3 (Asignado espec�ficamente al nodo)
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
        $this->assert(count($res_u3) == 1, "El Usuario 3 DEBER�A ver la tarea pendiente.");
        echo "   ? Usuario 3 (asignado espec�ficamente) ve la tarea correctamente.\n";

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
        $this->assert(count($res_u4) == 0, "El Usuario 4 NO deber�a ver la tarea ya que est� asignada exclusivamente al Usuario 3.");
        echo "   ? Usuario 4 (no asignado) NO ve la tarea correctamente.\n";

        // 4. Cambiar la asignaci�n del nodo a 'TODOS'
        mysqli_query($this->obBD_conexion->conexion, "UPDATE wf_nodos SET Nod_Usu_Asig = 'TODOS' WHERE Nod_Cod = 992;");

        // Ahora ambos usuarios deber�an ver la tarea
        $res_u4_todos = $this->obBD_datos->getArrayConsultaSql($sql_pendientes_u4, $this->obBD_conexion);
        $this->assert(count($res_u4_todos) == 1, "El Usuario 4 DEBER�A ver la tarea ahora que est� asignada a 'TODOS'.");
        echo "   ? Usuario 4 ve la tarea cuando la asignaci�n cambia a 'TODOS'.\n";
    }

    protected function testCalculoSlaPorNodo() {
        echo ">> Ejecutando Test de SLA por Nodo...\n";
        $instancia = $this->obBD_datos->getRowConsultaSql(
            "SELECT Ins_Cod FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing' AND Ins_Est = 'P' LIMIT 1;",
            $this->obBD_conexion
        );
        $ins_cod = intval($instancia['Ins_Cod']);
        mysqli_query(
            $this->obBD_conexion->conexion,
            "UPDATE wf_instancias_nodos
             SET Isn_Fec = CASE
                 WHEN Nod_Cod = 991 THEN DATE_SUB(NOW(), INTERVAL 3 DAY)
                 WHEN Nod_Cod = 992 THEN DATE_SUB(NOW(), INTERVAL 2 DAY)
                 ELSE Isn_Fec
             END
             WHERE Ins_Cod = $ins_cod AND Nod_Cod IN (991, 992);"
        );

        $flujo = $this->wf_mgr->getVisualFlowData($ins_cod);
        $por_nodo = array();
        foreach ($flujo['nodos'] as $nodo) {
            $por_nodo[intval($nodo['id'])] = $nodo;
        }
        $this->assert(isset($por_nodo[992]), "No se encontro el nodo con SLA en el flujo visual.");
        $this->assert($por_nodo[992]['sla_estado'] === 'retrasado', "El nodo activo con dos dias y SLA de un dia debe aparecer retrasado.");
        $this->assert(floatval($por_nodo[992]['sla_dias_retraso']) >= 1, "El retraso calculado debe ser de al menos un dia.");
        $this->assert(isset($por_nodo[993]) && $por_nodo[993]['sla_estado'] === 'sin_tiempo', "El nodo sin SLA debe mostrar sin tiempo determinado.");
        echo "   ? Retraso por nodo y estado sin tiempo determinado validados.\n";
    }

    protected function testActionValidationSecurity() {
        echo ">> Ejecutando Test de Validaci�n de Seguridad en Acciones...\n";

        // Obtener la instancia activa
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing' AND Ins_Est = 'P' LIMIT 1;", $this->obBD_conexion);
        $ins_cod = $instancia['Ins_Cod'];

        // Volver a poner la asignaci�n exclusiva al Usuario 3
        mysqli_query($this->obBD_conexion->conexion, "UPDATE wf_nodos SET Nod_Usu_Asig = '3' WHERE Nod_Cod = 992;");

        // Simular que el Usuario 4 intenta procesar la acci�n
        $usu_cod_u4 = 4;
        $dep_cod_u4 = 999;
        $perfiles_ids_u4 = "2";

        // Ejecutar la misma consulta de validaci�n de adq_bandeja.php
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

        $this->assert(empty($check_perm_u4), "La validaci�n de seguridad fall�: permiti� el acceso al Usuario 4 no asignado.");
        echo "   ? Validaci�n de seguridad bloquea correctamente al Usuario 4.\n";

        // Simular que el Usuario 3 intenta procesar la acci�n
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

        $this->assert(!empty($check_perm_u3), "La validaci�n de seguridad fall�: bloque� al Usuario 3 asignado.");
        echo "   ? Validaci�n de seguridad autoriza correctamente al Usuario 3.\n";
    }

    protected function testNotificacionFinalEsquema() {
        echo ">> Ejecutando Test de Notificacion Final del Esquema...\n";

        $destinatarios = $this->wf_mgr->listarDestinatariosNotificacionEsquema(999, 620);
        $usuarios = array();
        $correos = array();
        foreach ($destinatarios as $dest) {
            $usu_cod = intval($dest['Usu_Cod']);
            $correo = strtolower(trim(isset($dest['Correo']) ? $dest['Correo'] : ''));
            $this->assert(!isset($usuarios[$usu_cod]), "El usuario $usu_cod aparece duplicado en la notificacion final.");
            $usuarios[$usu_cod] = true;
            if ($correo !== '') {
                $this->assert(!isset($correos[$correo]), "El correo $correo aparece duplicado en la notificacion final.");
                $correos[$correo] = true;
            }
        }
        $this->assert(isset($usuarios[3]), "El usuario 3 asignado al nodo de aprobacion no fue incluido.");
        $this->assert(isset($usuarios[4]), "El usuario 4 asignado al nodo FIN no fue incluido.");

        $this->assert($this->wf_mgr->debeNotificarCierreNodo(array('Nod_Tip' => 'FIN', 'Nod_Not_Wa' => 1, 'Nod_Not_Em' => 0)), "La primera opcion debe activar el correo final.");
        $this->assert($this->wf_mgr->debeNotificarCierreNodo(array('Nod_Tip' => 'FIN', 'Nod_Not_Wa' => 0, 'Nod_Not_Em' => 1)), "La segunda opcion debe activar el correo final.");
        $this->assert(!$this->wf_mgr->debeNotificarCierreNodo(array('Nod_Tip' => 'FIN', 'Nod_Not_Wa' => 0, 'Nod_Not_Em' => 0)), "Sin opciones marcadas no debe enviarse correo final.");

        $dir = dirname(__FILE__) . '/../../DATA/wf_test_notificacion';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $cargado = $dir . '/expediente_cargado.pdf';
        $firmado = $dir . '/expediente_firmado.pdf';
        file_put_contents($cargado, '%PDF-1.4 cargado');
        file_put_contents($firmado, '%PDF-1.4 firmado');
        $ruta = $this->wf_mgr->resolverRutaExpedienteFinal(
            'wf_test_notificacion/expediente_firmado.pdf',
            'wf_test_notificacion/expediente_cargado.pdf'
        );
        $this->assert(realpath($ruta) === realpath($firmado), "Debe priorizarse el expediente firmado.");
        @unlink($firmado);
        @unlink($cargado);
        @rmdir($dir);

        echo "   ? Destinatarios, activacion y expediente final validados correctamente.\n";
    }

    protected function testDuplicarEsquema() {
        echo ">> Ejecutando Test de Duplicacion de Esquema...\n";
        $this->obBD_datos->inicio_transaccion($this->obBD_conexion);
        try {
            $res = $this->wf_mgr->duplicarFlujoDisenador(999, 620, 'Copia Flujo Test');
            $this->obBD_datos->commit_nomsn($this->obBD_conexion);
        } catch (Exception $e) {
            $this->obBD_datos->rollBack_nomsn($this->obBD_conexion);
            throw $e;
        }
        $this->duplicateWfmCod = intval($res['id']);
        $this->assert($this->duplicateWfmCod > 0 && $this->duplicateWfmCod !== 999, "La copia debe crear un esquema independiente.");
        $this->assert(intval($res['familia_cod']) === $this->duplicateWfmCod, "La copia debe tener una familia independiente.");

        $cab = $this->obBD_datos->getRowConsultaSql(
            "SELECT Wfm_Nom, Wfm_Est, Wfm_Version, Wfm_Fam_Cod FROM wf_flujos_modelos WHERE Wfm_Cod = {$this->duplicateWfmCod};",
            $this->obBD_conexion
        );
        $nodos = $this->obBD_datos->getRowConsultaSql(
            "SELECT COUNT(*) AS cnt, MAX(CASE WHEN Nod_Tip = 'APROBACION' THEN Nod_Sla ELSE NULL END) AS sla
             FROM wf_nodos WHERE Wfm_Cod = {$this->duplicateWfmCod} AND Nod_Est = 'A';",
            $this->obBD_conexion
        );
        $conexiones = $this->obBD_datos->getRowConsultaSql(
            "SELECT COUNT(*) AS cnt FROM wf_conexiones WHERE Wfm_Cod = {$this->duplicateWfmCod};",
            $this->obBD_conexion
        );
        $this->assert($cab['Wfm_Nom'] === 'Copia Flujo Test' && $cab['Wfm_Est'] === 'B', "La copia debe crearse como borrador con el nuevo nombre.");
        $this->assert(intval($nodos['cnt']) === 3 && intval($nodos['sla']) === 1, "La copia debe conservar todos los nodos y su SLA.");
        $this->assert(intval($conexiones['cnt']) === 2, "La copia debe conservar todas las conexiones.");
        echo "   ? Esquema duplicado con nodos, SLA y conexiones correctamente.\n";
    }

    protected function cleanupTestData() {
        echo ">> Limpiando datos de prueba...\n";
        if ($this->duplicateWfmCod > 0) {
            mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_conexiones WHERE Wfm_Cod = {$this->duplicateWfmCod};");
            mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_nodos WHERE Wfm_Cod = {$this->duplicateWfmCod};");
            mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_flujos_modelos WHERE Wfm_Cod = {$this->duplicateWfmCod};");
            $this->duplicateWfmCod = 0;
        }
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = 999 OR Wde_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias_nodos WHERE Ins_Cod IN (SELECT Ins_Cod FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing');");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_instancias WHERE Ins_Ent_Typ = 'test_routing';");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_conexiones WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_nodos WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_flujos_modelos WHERE Wfm_Cod = 999;");
        mysqli_query($this->obBD_conexion->conexion, "DELETE FROM wf_departamentos WHERE Wde_Cod = 999 OR Dep_Cod = 999;");
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
