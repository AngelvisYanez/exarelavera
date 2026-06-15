<?php
/**
 * EXA Workflow Manager & Adquisiciones - Suite de Pruebas de Integración
 * 
 * Este script ejecuta de manera programática el ciclo de vida completo de un requerimiento
 * de adquisición, evaluando transiciones, bifurcaciones de decisión por monto (SLA),
 * validaciones obligatorias de nodos y auditoría en base de datos.
 * 
 * Ejecución desde CLI: php adquisiciones/TEST/wf_integration_test.php
 * @author Oz <oz-agent@warp.dev>
 */

define('TEST_MODE', true);

// Mockear clases ausentes en CLI (como DebugBar y ChromePhp) para evitar caídas en el core de EXA
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

// Mockear variables de sesión globales de EXA
if (session_id() === '') session_start();
$_SESSION['Ses_Emp_Cod'] = 1;
$_SESSION['Ses_Suc_Cod'] = 1;
$_SESSION['Ses_Usu_Cod'] = 1;
$_SESSION['Ses_Dep_Cod'] = 1;
$_SESSION['Ses_Prs_Cod'] = 1; // Habilitar depuración detallada de errores
$_SESSION['Ses_Lis_Per'] = array(1, 2); // Perfil Administrador / Jefe
$_SESSION['Ses_Dat_Dis'] = 'exa'; // Base de datos del inquilino/corporativa para el test

require_once(dirname(__FILE__) . '/../LOGICA/adq_adquisiciones_log.php');
require_once(dirname(__FILE__) . '/../LOGICA/wf_manager_log.php');

class EXAWorkflowIntegrationTestSuite {
    protected $obBD_conexion;
    protected $obBD_datos;
    protected $adq_log;
    protected $wf_mgr;

    public function __construct() {
        $this->wf_mgr = new wf_manager_log('exa');
        $this->obBD_conexion = $this->wf_mgr->obBD_conexion;
        $this->obBD_datos = $this->wf_mgr->obBD_datos;
        $this->adq_log = new adq_adquisiciones_log($this->obBD_conexion);
    }

    public function run() {
        echo "========================================================================\n";
        echo " EXA WORKFLOW MANAGER - SUITE DE PRUEBAS DE INTEGRACIÓN (INTEGRATION TESTS)\n";
        echo "========================================================================\n\n";

        try {
            $this->testRequisitosPrevios();
            $this->testCasoCompraBajoMonto();
            $this->testCasoCompraAltoMonto();
            $this->testValidacionesCamposObligatorios();

            echo "\n========================================================================\n";
            echo " ✔️ TODAS LAS PRUEBAS DE INTEGRACIÓN SE COMPLETARON CON ÉXITO (ALL PASSED)\n";
            echo "========================================================================\n";
        } catch (Exception $e) {
            echo "\n❌ ERROR CRÍTICO DURANTE LA EJECUCIÓN DE LAS PRUEBAS:\n";
            echo "   Mensaje: " . $e->getMessage() . "\n";
            echo "   Línea:   " . $e->getLine() . "\n";
            echo "========================================================================\n";
            exit(1);
        }
    }

    /**
     * Verifica que el flujo modelo por defecto exista en la base de datos
     */
    protected function testRequisitosPrevios() {
        echo ">> 1. Verificando Requisitos Previos en Base de Datos... ";
        
        $flujo = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_flujos_modelos WHERE Wfm_Cod = 1 AND Wfm_Est = 'A';", $this->obBD_conexion);
        $this->assert(!empty($flujo), "No se encontró el flujo modelo por defecto 'Compra de Bienes y Servicios' (ID 1) activo.");

        $tipo = $this->obBD_datos->getRowConsultaSql("SELECT * FROM adq_tipos_requerimientos WHERE Trq_Cod = 1 AND Trq_Est = 'A';", $this->obBD_conexion);
        $this->assert(!empty($tipo), "No se encontró el tipo de requerimiento por defecto (ID 1) activo.");

        $nodos = $this->obBD_datos->getArrayConsultaSql("SELECT * FROM wf_nodos WHERE Wfm_Cod = 1;", $this->obBD_conexion);
        $this->assert(count($nodos) == 6, "El flujo modelo 1 debe poseer exactamente 6 nodos configurados. Posee: " . count($nodos));

        echo "PASSED!\n";
    }

    /**
     * Caso de prueba: Adquisición por un total de $1,200.00
     * El flujo debe iniciar, pasar Jefatura, evaluar automáticamente el nodo de decisión (Monto <= 5000),
     * OMITIR aprobación de Gerencia General, ir directo a Facturación, y finalizar exitosamente.
     */
    protected function testCasoCompraBajoMonto() {
        echo ">> 2. Test Caso Compra Bajo Monto ($ 1,200.00)... \n";

        // Mockear post de entrada
        $data_solicitud = array(
            'Trq_Cod' => 1,
            'Sol_Pri' => 'MEDIA',
            'Sol_Val_Est' => '1200.00',
            'Cdc_Cod' => 'DEP_TI',
            'Prv_Sug' => 1,
            'Sol_Jus' => 'Adquisición de licencias de prueba para auditorías internas de software.',
            'Sol_Det' => '2 Licencias temporales de desarrollo.',
            'Emp_Cod' => 1,
            'Suc_Cod' => 1
        );

        $items = array(
            array('Pro_Cod' => null, 'Sde_Des' => 'Licencias de software corporativo', 'Sde_Can' => 2, 'Sde_Pru' => 600.00)
        );

        $cotizaciones = array(
            array('Prv_Cod' => 1, 'Cot_Val' => 1200.00, 'Cot_Adj' => 'adquisiciones_sustentos/cot_demo1.pdf', 'Cot_Sel' => 1, 'Cot_Jus' => 'Proveedor único homologado')
        );

        // A. Guardar solicitud e iniciar flujo
        $res = $this->adq_log->guardarSolicitud($data_solicitud, $items, $cotizaciones);
        $this->assert($res['success'], "No se pudo guardar la solicitud inicial.");
        $sol_cod = $res['Sol_Cod'];

        // Recuperar instancia de flujo
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Ent_Typ = 'adq_solicitudes' AND Ins_Ent_Cod = $sol_cod;", $this->obBD_conexion);
        $this->assert(!empty($instancia), "No se creó la instancia de flujo en base de datos.");
        $ins_cod = $instancia['Ins_Cod'];

        // El flujo se debió autotransicionar del nodo 1 (Inicio) al nodo 2 (Aprobación Jefatura)
        $this->assert($instancia['Nod_Act'] == 2, "La instancia debe estar activa actualmente en el Nodo 2 (Jefatura). Nodo actual: " . $instancia['Nod_Act']);
        echo "   [PASO 1] Solicitud creada exitosamente. Instanciado workflow # $ins_cod en Jefatura (Nodo 2).\n";

        // B. Aprobación de Jefatura (Nodo 2) -> Avanza a Nodo 3 (Decisión)
        // El motor evalúa automáticamente el Nodo 3 y, al ser $1200 <= $5000, salta Gerencia (Nodo 4) y se posiciona en Factura (Nodo 5)
        $action_res = $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', 'Autorizado por presupuesto de depto.');
        $this->assert($action_res['success'], "Error al procesar la aprobación de Jefatura.");

        $instancia_post_jefatura = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $this->obBD_conexion);
        // Debe estar en Nodo 5 (Factura), saltándose Nodo 4 (Gerencia)
        $this->assert($instancia_post_jefatura['Nod_Act'] == 5, "La decisión debió saltar al Nodo 5. Se encuentra en: " . $instancia_post_jefatura['Nod_Act']);
        echo "   [PASO 2] Aprobada Jefatura. Decisión evaluó Monto ($1200 <= $5000) y saltó exitosamente al Nodo 5 (Facturación).\n";

        // C. Resolver Factura (Nodo 5) -> Finalizar (Nodo 6)
        $action_res_fin = $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', 'Factura recibida y vinculada de forma correcta.', 'adquisiciones_sustentos/factura_123.pdf');
        $this->assert($action_res_fin['success'], "Error al procesar la aprobación de Facturación.");

        $instancia_final = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $this->obBD_conexion);
        $this->assert($instancia_final['Ins_Est'] == 'F', "La instancia debe estar marcada como FINALIZADA con éxito ('F').");
        
        $solicitud_final = $this->obBD_datos->getRowConsultaSql("SELECT * FROM adq_solicitudes WHERE Sol_Cod = $sol_cod;", $this->obBD_conexion);
        $this->assert($solicitud_final['Sol_Est'] == 'A', "La solicitud de adquisición original debe estar en estado APROBADO ('A').");
        echo "   [PASO 3] Facturación procesada. El flujo se cerró con éxito ('F') y la adquisición fue aprobada ('A').\n";
        echo "   -> CASO BAJO MONTO: PASSED!\n\n";
    }

    /**
     * Caso de prueba: Adquisición por un total de $8,500.00 (Supera $5000)
     * El flujo debe pasar Jefatura, evaluar el nodo de decisión, REQUERIR aprobación de Gerencia General (Nodo 4),
     * pasar a Facturación, y finalizar.
     */
    protected function testCasoCompraAltoMonto() {
        echo ">> 3. Test Caso Compra Alto Monto ($ 8,500.00)... \n";

        $data_solicitud = array(
            'Trq_Cod' => 1,
            'Sol_Pri' => 'ALTA',
            'Sol_Val_Est' => '8500.00',
            'Cdc_Cod' => 'DEP_TI',
            'Prv_Sug' => 1,
            'Sol_Jus' => 'Adquisición de equipamientos de servidores de TI para data center.',
            'Sol_Det' => 'Compra de Servidor Rack 1U.',
            'Emp_Cod' => 1,
            'Suc_Cod' => 1
        );

        $items = array(
            array('Pro_Cod' => null, 'Sde_Des' => 'Servidor Rack de Datos', 'Sde_Can' => 1, 'Sde_Pru' => 8500.00)
        );

        $cotizaciones = array(
            array('Prv_Cod' => 1, 'Cot_Val' => 8500.00, 'Cot_Adj' => 'adquisiciones_sustentos/cot_serv.pdf', 'Cot_Sel' => 1, 'Cot_Jus' => 'Mejor oferta técnica')
        );

        // A. Guardar solicitud
        $res = $this->adq_log->guardarSolicitud($data_solicitud, $items, $cotizaciones);
        $this->assert($res['success'], "No se pudo guardar la solicitud inicial.");
        $sol_cod = $res['Sol_Cod'];

        // Recuperar instancia
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Ent_Typ = 'adq_solicitudes' AND Ins_Ent_Cod = $sol_cod;", $this->obBD_conexion);
        $ins_cod = $instancia['Ins_Cod'];
        $this->assert($instancia['Nod_Act'] == 2, "La instancia debe iniciar en Jefatura (Nodo 2).");

        // B. Aprobación de Jefatura (Nodo 2) -> Avanza a Nodo 3 (Decisión)
        // El motor evalúa el Nodo 3 y, al ser $8500 > $5000, REFIERE a Gerencia General (Nodo 4)
        $action_res = $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', 'Aprobado y recomendado por depto TI.');
        $this->assert($action_res['success'], "Error en aprobación Jefatura.");

        $instancia_post_jefatura = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $this->obBD_conexion);
        // Debe estar en Nodo 4 (Gerencia General)
        $this->assert($instancia_post_jefatura['Nod_Act'] == 4, "Al superar $5000, debió enrutarse a Gerencia General (Nodo 4). Nodo: " . $instancia_post_jefatura['Nod_Act']);
        echo "   [PASO 1] Aprobada Jefatura. Decisión evaluó Monto ($8500 > $5000) y enrutó correctamente a Gerencia General (Nodo 4).\n";

        // C. Aprobación de Gerencia General (Nodo 4) -> Avanza a Factura (Nodo 5)
        $action_res_ger = $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', 'Compra autorizada estratégicamente por Gerencia.', 'adquisiciones_sustentos/firma_gerencial.pdf');
        $this->assert($action_res_ger['success'], "Error en aprobación Gerencial.");

        $instancia_post_gerencia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $this->obBD_conexion);
        $this->assert($instancia_post_gerencia['Nod_Act'] == 5, "Debió avanzar a Facturación (Nodo 5). Nodo: " . $instancia_post_gerencia['Nod_Act']);
        echo "   [PASO 2] Aprobó Gerencia General con sustento físico. Avanzó correctamente a Factura (Nodo 5).\n";

        // D. Procesar Factura (Nodo 5) -> Fin (Nodo 6)
        $action_res_fin = $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', 'Facturación completada de forma correcta.', 'adquisiciones_sustentos/fac_serv.pdf');
        $this->assert($action_res_fin['success'], "Error en aprobación Facturación.");

        $instancia_final = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $this->obBD_conexion);
        $this->assert($instancia_final['Ins_Est'] == 'F', "La instancia debe estar finalizada ('F').");
        echo "   [PASO 3] Facturación procesada. Flujo cerrado con éxito ('F').\n";
        echo "   -> CASO ALTO MONTO: PASSED!\n\n";
    }

    /**
     * Valida que el motor rechace transiciones si no se cumplen las condiciones obligatorias de los nodos
     * (Ej. Comentario obligatorio en Jefatura, Adjunto obligatorio en Gerencia)
     */
    protected function testValidacionesCamposObligatorios() {
        echo ">> 4. Test Validaciones de Campos Obligatorios... \n";

        $data_solicitud = array(
            'Trq_Cod' => 1,
            'Sol_Pri' => 'URGENTE',
            'Sol_Val_Est' => '6000.00',
            'Cdc_Cod' => 'DEP_TI',
            'Prv_Sug' => 1,
            'Sol_Jus' => 'Prueba de validación obligatoria.',
            'Sol_Det' => 'Soporte técnico.',
            'Emp_Cod' => 1,
            'Suc_Cod' => 1
        );

        $items = array(
            array('Pro_Cod' => null, 'Sde_Des' => 'Servicios de prueba', 'Sde_Can' => 1, 'Sde_Pru' => 6000.00)
        );

        $res = $this->adq_log->guardarSolicitud($data_solicitud, $items);
        echo "   [DEBUG] guardarSolicitud result: " . json_encode($res) . "\n";
        $sol_cod = $res['Sol_Cod'];
        
        $instancia = $this->obBD_datos->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Ent_Typ = 'adq_solicitudes' AND Ins_Ent_Cod = $sol_cod;", $this->obBD_conexion);
        $ins_cod = $instancia['Ins_Cod'];
        echo "   [DEBUG] Instancia creada ID: $ins_cod, Nodo Activo: " . $instancia['Nod_Act'] . ", Estado: " . $instancia['Ins_Est'] . "\n";

        // 1. Intentar aprobar Jefatura (Nodo 2) con COMENTARIO VACÍO (Debe fallar porque Nod_Com_Obl = 1)
        try {
            $action_res = $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', '  '); // Solo espacios
            $this->assert(false, "El motor debió denegar la transacción por comentario obligatorio vacío.");
        } catch (Exception $e) {
            $this->assert(strpos($e->getMessage(), "comentario es obligatorio") !== false, "Error inesperado al validar comentario obligatorio: " . $e->getMessage());
            echo "   [OK] El motor denegó correctamente la aprobación sin comentarios en Jefatura.\n";
        }

        // Aprobar de forma válida para avanzar a Gerencia
        $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', 'Comentario válido obligatorio para avanzar.');

        // 2. Intentar aprobar Gerencia (Nodo 4) sin ADJUNTO (Debe fallar porque Nod_Adj_Obl = 1)
        try {
            $action_res_ger = $this->wf_mgr->procesarAccionUsuario($ins_cod, 'APROBAR', 'Aprobado sin adjunto por Gerente.', null); // Adjunto null
            $this->assert(false, "El motor debió denegar la transacción por adjunto obligatorio ausente.");
        } catch (Exception $e) {
            $this->assert(strpos($e->getMessage(), "archivo adjunto como sustento") !== false, "Error inesperado al validar adjunto obligatorio: " . $e->getMessage());
            echo "   [OK] El motor denegó correctamente la aprobación sin adjuntos en Gerencia General.\n";
        }

        echo "   -> TEST VALIDACIONES OBLIGATORIAS: PASSED!\n";
    }

    protected function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }
}

// Ejecutar Suite
$testSuite = new EXAWorkflowIntegrationTestSuite();
$testSuite->run();
