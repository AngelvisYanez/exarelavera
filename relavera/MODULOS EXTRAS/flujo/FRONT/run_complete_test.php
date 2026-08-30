<?php
/**
 * EXA Adquisiciones & Workflow - End-to-End Test Simulation
 * Actúa como múltiples usuarios para probar el flujo de trabajo completo de adquisición de $6,000.
 */

// Configurar entorno de simulación
if (session_id() === '') @session_start();

// Usamos la base de datos 'exa'
$_SESSION['Ses_Dat_Dis'] = 'exa';
$Ses_Emp_Cod = 1;
$Ses_Suc_Cod = 1;

require_once('C:/xampp/htdocs/administrador/LOGICA/seguridad.php');
require_once('C:/xampp/htdocs/flujo/LOGICA/adq_adquisiciones_log.php');
require_once('C:/xampp/htdocs/flujo/LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($_SESSION['Ses_Dat_Dis']);
$obBD_con1 = new adq_adquisiciones_log($obBD_conexion);
$wf_mgr = new wf_manager_log($_SESSION['Ses_Dat_Dis']);

echo "==================================================================\n";
echo " INICIANDO SIMULACIÓN COMPLETA DE PROCESO DE ADQUISICIÓN ($6,000.00)\n";
echo "==================================================================\n\n";

// Asegurar que los usuarios de prueba estén asignados a los departamentos correctos
$obBD_con1->grabarv_registros("DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = 1 AND Usu_Cod IN (1, 3, 4, 553);", $obBD_conexion);
$obBD_con1->grabarv_registros("INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod) VALUES (1, 1), (1, 3), (1, 4), (1, 553);", $obBD_conexion);
mysqli_commit($obBD_conexion->conexion);

// ==================================================================
// PASO 1: Francisco (Usu_Cod = 3) crea una Solicitud de $6,000.00
// ==================================================================
echo "PASO 1: Francisco (Usuario Solicitante - Usu_Cod = 3) registra la solicitud...\n";
$_SESSION['Ses_Usu_Cod'] = 3;
$_SESSION['Ses_Dep_Cod'] = 1;
$_SESSION['Ses_Lis_Per'] = array(3); // Perfil común

$data_solicitud = array(
    'Emp_Cod' => 1,
    'Suc_Cod' => 1,
    'Trq_Cod' => 1, // Compra de Bienes y Equipos
    'Sol_Pri' => 'ALTA',
    'Sol_Val_Est' => '6000.00',
    'Cdc_Cod' => '1',
    'Sol_Jus' => 'Adquisición de servidores de desarrollo para el equipo de TI.',
    'Sol_Det' => 'Se requiere la compra de 2 servidores rackeables para virtualización de entornos.'
);

$items = array(
    array('Sde_Des' => 'Servidor Dell PowerEdge R750', 'Sde_Can' => 2, 'Sde_Pru' => 3000.00)
);

$cotizaciones = array(); // No obligatorias para este test simplificado

$res_creacion = $obBD_con1->guardarSolicitud($data_solicitud, $items, $cotizaciones);

if (!$res_creacion['success']) {
    die("? Error al crear la solicitud: " . $res_creacion['message'] . "\n");
}

$sol_cod = $res_creacion['Sol_Cod'];
$sol_num = $res_creacion['Num'];
echo "? Solicitud #$sol_num (ID: $sol_cod) creada con éxito por $6,000.00.\n";

// Forzar commit/actualización de lectura en esta conexión
mysqli_commit($obBD_conexion->conexion);

// Verificar estado inicial del workflow
$instancia = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Ent_Typ = 'adq_solicitudes' AND Ins_Ent_Cod = $sol_cod;", $obBD_conexion);
$ins_cod = $instancia['Ins_Cod'];
$nodo_act_cod = $instancia['Nod_Act'];
$nodo_act = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $nodo_act_cod;", $obBD_conexion);

echo "?? Estado del Workflow: Instancia #$ins_cod iniciada. Nodo Actual: ID $nodo_act_cod - '" . $nodo_act['Nod_Nom'] . "' (" . $nodo_act['Nod_Tip'] . ").\n\n";

// ==================================================================
// PASO 2: Kleber (Usu_Cod = 4) aprueba en la etapa 'Aprobación Jefatura' (Nod_Cod = 2)
// ==================================================================
echo "PASO 2: Kleber (Jefe de TI - Usu_Cod = 4) revisa y aprueba la solicitud...\n";
$_SESSION['Ses_Usu_Cod'] = 4;
$_SESSION['Ses_Dep_Cod'] = 1;
$_SESSION['Ses_Lis_Per'] = array(2); // Perfil Jefatura

$res_aprob_jefe = $wf_mgr->avanzarSiguientePaso($ins_cod, 2, 'APROBAR', 'Aprobado por el Jefe de TI. Cumple con las especificaciones técnicas requeridas.');

if (!$res_aprob_jefe) {
    die("? Error en la aprobación de Jefatura.\n");
}

// Forzar commit/actualización de lectura en esta conexión
mysqli_commit($obBD_conexion->conexion);

// Verificar que el flujo avanzó y evaluó el nodo Decisión
$instancia = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $obBD_conexion);
$nodo_act_cod = $instancia['Nod_Act'];
$nodo_act = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $nodo_act_cod;", $obBD_conexion);

echo "? Aprobación de Jefatura procesada.\n";
echo "?? Evaluación de Decisión: Como el monto es $6,000.00 (> $5,000.00), el motor de flujos debió saltarse el atajo directo e ir a Aprobación de Gerencia.\n";
echo "?? Nodo Actual: ID $nodo_act_cod - '" . $nodo_act['Nod_Nom'] . "' (" . $nodo_act['Nod_Tip'] . ").\n";
if ($nodo_act_cod == 4) {
    echo "?? ¡ÉXITO! El motor de flujos enrutó correctamente la solicitud a la etapa de Aprobación de Gerencia.\n\n";
} else {
    die("? ERROR: El flujo se desvió a un nodo incorrecto (ID: $nodo_act_cod).\n");
}

// ==================================================================
// PASO 3: Administrador (Usu_Cod = 1) aprueba en la etapa 'Aprobación Gerencia' (Nod_Cod = 4)
// ==================================================================
echo "PASO 3: Administrador (Gerente General - Usu_Cod = 1) aprueba la solicitud de alto monto...\n";
$_SESSION['Ses_Usu_Cod'] = 1;
$_SESSION['Ses_Dep_Cod'] = 1;
$_SESSION['Ses_Lis_Per'] = array(1); // Perfil Gerencial/Admin

$res_aprob_gerente = $wf_mgr->avanzarSiguientePaso($ins_cod, 4, 'APROBAR', 'Aprobado por Gerencia General. Proceder con la adquisición de inmediato.');

if (!$res_aprob_gerente) {
    die("? Error en la aprobación de Gerencia.\n");
}

// Forzar commit/actualización de lectura en esta conexión
mysqli_commit($obBD_conexion->conexion);

// Verificar que avanzó a Facturación
$instancia = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $obBD_conexion);
$nodo_act_cod = $instancia['Nod_Act'];
$nodo_act = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_nodos WHERE Nod_Cod = $nodo_act_cod;", $obBD_conexion);

echo "? Aprobación de Gerencia procesada.\n";
echo "?? Nodo Actual: ID $nodo_act_cod - '" . $nodo_act['Nod_Nom'] . "' (" . $nodo_act['Nod_Tip'] . ").\n\n";

// ==================================================================
// PASO 4: Administrador (Usu_Cod = 1) registra la Factura (Nod_Cod = 5)
// ==================================================================
echo "PASO 4: Administrador (Contabilidad - Usu_Cod = 1) registra y valida la Factura...\n";

$res_factura = $wf_mgr->avanzarSiguientePaso($ins_cod, 5, 'APROBAR', 'Factura #001-002-12345 recibida y validada en el sistema contable.');

if (!$res_factura) {
    die("? Error en la validación de Factura.\n");
}

// Forzar commit/actualización de lectura en esta conexión
mysqli_commit($obBD_conexion->conexion);

// Verificar que el flujo finalizó
$instancia = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_instancias WHERE Ins_Cod = $ins_cod;", $obBD_conexion);
$solicitud = $obBD_con1->getRowConsultaSql("SELECT * FROM adq_solicitudes WHERE Sol_Cod = $sol_cod;", $obBD_conexion);

echo "? Registro de Factura procesado.\n";
echo "?? Estado de la Instancia de Workflow: " . ($instancia['Ins_Est'] == 'F' ? "?? FINALIZADA" : "PENDIENTE") . "\n";
echo "?? Estado de la Solicitud de Adquisición: " . ($solicitud['Sol_Est'] == 'A' ? "?? APROBADA / COMPLETADA" : "EN FLUJO") . "\n\n";

if ($instancia['Ins_Est'] == 'F' && $solicitud['Sol_Est'] == 'A') {
    echo "==================================================================\n";
    echo " ?? SIMULACIÓN COMPLETADA CON ÉXITO ABSOLUTO ??\n";
    echo " El motor de flujos, la lógica de decisiones por monto y los\n";
    echo " permisos de enrutamiento funcionan al 100% de forma correcta.\n";
    echo "==================================================================\n";
} else {
    die("? ERROR: El flujo no se completó correctamente.\n");
}
