<?php
/**
 * Diagnóstico del menú de administración
 * Ejecutar en el navegador: http://localhost/exa/administrador/FRONT/diagnostico_menu.php
 * IMPORTANTE: Debes estar logueado como Administrador
 */
session_start();

// Verificar que hay sesión activa
if (!isset($_SESSION['Ses_Lis_Per'])) {
    die('<h2>Error: No hay sesión activa. Debes estar logueado.</h2>');
}

if (false) {
echo "<h2>Diagnóstico del Menú de Administración</h2>";
echo "<pre>";

echo "=== 1. DATOS DE SESIÓN ===\n";
echo "Ses_Lis_Per: " . print_r($_SESSION['Ses_Lis_Per'], true) . "\n";
echo "Ses_Usu_Men: " . ($_SESSION['Ses_Usu_Men'] ?? 'NO DEFINIDO') . "\n";
echo "Ses_Dat_Dis: " . ($_SESSION['Ses_Dat_Dis'] ?? 'NO DEFINIDO') . "\n";
echo "Ses_Usu_Cod: " . ($_SESSION['Ses_Usu_Cod'] ?? 'NO DEFINIDO') . "\n";

echo "\n=== 2. CARGANDO CLASES ===\n";
$APP_REAL_PATH = realpath(__DIR__ . '/../../');
require_once($APP_REAL_PATH . '/DATA/MysqlConexion.php');
require_once($APP_REAL_PATH . '/DATA/MysqlDatos.php');
require_once($APP_REAL_PATH . '/administrador/LOGICA/logica.php');
require_once($APP_REAL_PATH . '/administrador/LOGICA/adm_log_menu_tree.php');
require_once($APP_REAL_PATH . '/Librerias/procedimientos/almacenados_standar.php');

echo "Clases cargadas correctamente.\n";

echo "\n=== 3. CONEXIÓN A BASE DE DATOS ===\n";
$obBD_conexion = new Class_Log_Conexion_Global($_SESSION['Ses_Dat_Dis']);
echo "Conexión: " . ($obBD_conexion->conexion ? 'OK' : 'FALLÓ') . "\n";

echo "\n=== 4. CONSTRUYENDO FILTRO DE PERFILES ===\n";
$obMenu = new Class_Sys_Menu;
$mperf = $obMenu->buildProfileFilter($_SESSION['Ses_Lis_Per']);
echo "Filtro: " . $mperf . "\n";

echo "\n=== 5. CONSULTANDO DIRECTORIOS (Case 2) ===\n";
$directorios = $obMenu->getArrayConsulta(2, '*' . $mperf, $obBD_conexion);
echo "Total directorios: " . count($directorios) . "\n";

// Buscar los nuevos
$nuevosDir = array_filter($directorios, function($d) {
    return in_array($d['Org_Des'], ['Scraper SRI', 'Flujo de Adquisiciones']);
});
echo "Directorios nuevos: " . count($nuevosDir) . "\n";
foreach ($nuevosDir as $d) {
    echo "  - {$d['Org_Cod']}: {$d['Org_Des']} (Niv: {$d['Org_Niv']}, Ord: {$d['Org_Ord']}, Mod: {$d['Org_Mod']})\n";
}

echo "\n=== 6. CONSULTANDO PROCESOS (Case 3) ===\n";
$procesos = $obMenu->getArrayConsulta(3, '*' . $mperf . '*P', $obBD_conexion);
echo "Total procesos: " . count($procesos) . "\n";

$nuevosProcs = array_filter($procesos, function($p) {
    return in_array($p['Pcs_Nom'], [
        'scrapers.php', 'adq_bandeja.php', 'adq_solicitud.php',
        'adq_lista_solicitud.php', 'adq_dashboard.php', 'adq_seguimiento.php',
        'adq_configuracion.php', 'adq_departamentos.php'
    ]);
});
echo "Procesos nuevos: " . count($nuevosProcs) . "\n";
foreach ($nuevosProcs as $p) {
    echo "  - {$p['Pcs_Cod']}: {$p['Pcs_Lin']} (Org: {$p['Org_Cod']}, Ruta: {$p['Rut_Des']})\n";
}

echo "\n=== 7. AGRUPACIÓN POR DIRECTORIO ===\n";
$procsGrouped = groupBy($procesos, 'Org_Cod');
$dirGrouped = groupBy($directorios, 'Org_Niv');

foreach ($nuevosDir as $d) {
    $orgCod = $d['Org_Cod'];
    $hijos = isset($procsGrouped[$orgCod]) ? $procsGrouped[$orgCod] : [];
    echo "  {$d['Org_Des']} (Org_Cod: {$orgCod}): " . count($hijos) . " procesos\n";
    foreach ($hijos as $h) {
        echo "    → {$h['Pcs_Lin']} ({$h['Pcs_Nom']})\n";
    }
}

echo "\n=== 8. VERIFICANDO CONSTRUCCIÓN DEL ÁRBOL ===\n";
$menu = $obMenu->getMenuContainer2($_SESSION['Ses_Lis_Per'], $obBD_conexion);
$paginas = $menu->getPages();
echo "Total páginas raíz: " . count($paginas) . "\n";

// Buscar los nuevos módulos en el árbol
foreach ($paginas as $page) {
    $label = $page->getLabel();
    if (in_array($label, ['Scraper SRI', 'Flujo de Adquisiciones'])) {
        echo "\n  ENCONTRADO: {$label}\n";
        echo "    ItemType: " . $page->get('itemType') . "\n";
        echo "    Visible: " . ($page->isVisible() ? 'SÍ' : 'NO') . "\n";
        echo "    Href: " . ($page->getHref() ?? 'NULL') . "\n";
        echo "    Tiene hijos: " . ($page->hasPages() ? 'SÍ' : 'NO') . "\n";
        echo "    Tiene procesos (hasProccess): " . ($page->hasProccess() ? 'SÍ' : 'NO') . "\n";
        
        if ($page->hasPages()) {
            echo "    Hijos:\n";
            foreach ($page->getPages() as $child) {
                echo "      - {$child->getLabel()} (Type: {$child->get('itemType')}, Href: " . ($child->getHref() ?? 'NULL') . ")\n";
            }
        }
    }
}

echo "\n=== 9. HTML GENERADO (primeros 2000 chars) ===\n";
$html = $obMenu->menuToHtml(1, $menu, 'nav nav-list', 'hover');
echo substr($html, 0, 2000) . "\n";

// Buscar si los nuevos módulos aparecen en el HTML
echo "\n=== 10. VERIFICACIÓN FINAL ===\n";
$busqueda = ['Scraper SRI', 'Flujo de Adquisiciones', 'scrapers.php', 'adq_bandeja.php'];
foreach ($busqueda as $term) {
    $pos = strpos($html, $term);
    echo "  '{$term}': " . ($pos !== false ? "ENCONTRADO en posición {$pos}" : "NO ENCONTRADO") . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
echo "</pre>";
} // END DEBUG TOOL - DISABLED
