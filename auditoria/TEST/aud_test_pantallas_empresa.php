<?php
/**
 * Test de pantallas y procesos simulando sesión de la empresa "Capacitación Videos"
 */

if (session_id() === '') {
    @session_start();
}

require_once dirname(__FILE__) . '/aud_test_lib.php';

// Configurar entorno de sesión como la empresa 503 "capacitacion videos"
$_SESSION['Ses_Emp_Cod'] = 503;
$_SESSION['Ses_Emp_Nom'] = 'capacitacion videos';
$_SESSION['Ses_Usu_Cod'] = 2430;
$_SESSION['Ses_Per_Cod'] = 1130;
$_SESSION['Ses_Per_Des'] = 'Administrador de Sistemas';
$_SESSION['Ses_Dat_Dis'] = 'servicios';
$_SESSION['Ses_Prs_Nom'] = 'VICTOR LEWIS';
$_SESSION['Ses_Prs_Ape'] = 'CHIMARRO CHIPANTIZA';
$_SESSION['Ses_User'] = 'admin_videos';

echo "=== VERIFICANDO PANTALLAS Y PROCESOS CON EMPRESA 'capacitacion videos' (503) ===\n\n";

// 1. Verificar aud_con_monitoreo_1.0.php
echo "1. aud_con_monitoreo_1.0.php:\n";
$file1 = dirname(__FILE__) . '/../FRONT/aud_con_monitoreo_1.0.php';
$out1 = shell_exec('php -l "' . $file1 . '" 2>&1');
aud_assert(stripos($out1, 'No syntax errors') !== false, "Sintaxis valida en aud_con_monitoreo_1.0.php");

// 2. Verificar aud_adm_config_monitoreo_1.0.php
echo "2. aud_adm_config_monitoreo_1.0.php:\n";
$file2 = dirname(__FILE__) . '/../FRONT/aud_adm_config_monitoreo_1.0.php';
$out2 = shell_exec('php -l "' . $file2 . '" 2>&1');
aud_assert(stripos($out2, 'No syntax errors') !== false, "Sintaxis valida en aud_adm_config_monitoreo_1.0.php");

// 3. Verificar aud_con_actividad_usuarios_1.0.php
echo "3. aud_con_actividad_usuarios_1.0.php:\n";
$file3 = dirname(__FILE__) . '/../FRONT/aud_con_actividad_usuarios_1.0.php';
$out3 = shell_exec('php -l "' . $file3 . '" 2>&1');
aud_assert(stripos($out3, 'No syntax errors') !== false, "Sintaxis valida en aud_con_actividad_usuarios_1.0.php");

// 4. Verificar aud_con_dashboard_comparativo_1.0.php
echo "4. aud_con_dashboard_comparativo_1.0.php:\n";
$file4 = dirname(__FILE__) . '/../FRONT/aud_con_dashboard_comparativo_1.0.php';
$out4 = shell_exec('php -l "' . $file4 . '" 2>&1');
aud_assert(stripos($out4, 'No syntax errors') !== false, "Sintaxis valida en aud_con_dashboard_comparativo_1.0.php");

// 5. Backend aud_log_actividad_sesion.php con empresa 503
echo "\n5. Backend aud_log_actividad_sesion.php con empresa 503:\n";
require_once dirname(__FILE__) . '/../LOGICA/aud_log_actividad_sesion.php';
$con = aud_db_connect();
$listado = aud_ses_listar_actividad(503, '', 0, 100, $con);
echo "   Sesiones encontradas: " . count($listado['items']) . "\n";
echo "   KPI En Línea: " . (isset($listado['kpis']['En_Linea']) ? $listado['kpis']['En_Linea'] : 0) . "\n";
echo "   KPI Sesiones Hoy: " . (isset($listado['kpis']['Sesiones_Hoy']) ? $listado['kpis']['Sesiones_Hoy'] : 0) . "\n";
aud_assert(isset($listado['kpis']) && is_array($listado['kpis']), "KPIs de actividad disponibles para empresa 503");

// 6. Backend aud_log_dashboard.php con empresa 503
echo "\n6. Backend aud_log_dashboard.php con empresa 503:\n";
require_once dirname(__FILE__) . '/../LOGICA/aud_log_dashboard.php';
$hoy = date('Y-m-d 23:59:59');
$hace7 = date('Y-m-d 00:00:00', strtotime('-7 days'));
$hace14 = date('Y-m-d 00:00:00', strtotime('-14 days'));
$hace8 = date('Y-m-d 23:59:59', strtotime('-8 days'));

$comp = aud_dash_calcular_comparativa(503, $hace7, $hoy, $hace14, $hace8, $con);
echo "   KPIs comparativos calculados: " . count($comp['kpis_comparativa']) . " métricas\n";
echo "   Módulos comparados: " . count($comp['modulos_comparativa']) . "\n";
aud_assert(isset($comp['kpis_comparativa']) && count($comp['kpis_comparativa']) >= 6, "KPIs comparativos calculados para empresa 503");

// 7. Permisos de administrador de sistemas para perfil 1130
echo "\n7. Permisos de administrador de sistemas para perfil 1130 (Capacitación Videos):\n";
require_once dirname(__FILE__) . '/../LOGICA/aud_log_config_monitoreo.php';
$esAdmin = aud_cfg_es_admin_sistemas(2430);
echo "   Es Admin de Sistemas perfil 1130: " . ($esAdmin ? "SI" : "NO") . "\n";
aud_assert($esAdmin === true, "Perfil 1130 de Capacitacion Videos es detectado como Administrador de Sistemas");

// 8. Carga de árbol filtrado por rol para perfil 1130
echo "\n8. Carga de árbol filtrado por rol para perfil 1130:\n";
require_once dirname(__FILE__) . '/../LOGICA/aud_sql_config_monitoreo.php';
$sqlTree = sentencias_cfg_monitoreo(14, array(0, 1130));
$resTree = mysqli_query($con, $sqlTree);
if (!$resTree) {
    echo "   Error en query 14: " . mysqli_error($con) . "\n";
    echo "   SQL: " . $sqlTree . "\n";
}
$modulosRol = 0;
while ($resTree && $r = mysqli_fetch_assoc($resTree)) {
    $modulosRol++;
}
echo "   Elementos de árbol asignados a rol 1130: {$modulosRol}\n";
aud_assert($modulosRol > 0, "Arbol de monitoreo se filtra y carga exitosamente para perfil 1130");

echo "\n==============================================\n";
echo "TODAS LAS VERIFICACIONES DE PANTALLAS Y PROCESOS OK\n";
echo "==============================================\n";
