<?php
/**
 * Diagnóstico del menú - Abrir desde el navegador mientras estás logueado
 * URL: http://localhost/exa/administrador/FRONT/diag_menu_nav.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: text/html; charset=iso-8859-1');

if (!isset($_SESSION['Ses_Lis_Per'])) {
    die('<h2>ERROR: No hay sesion activa. Debe estar logueado en el admin.</h2>');
}

require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/logica.php');
require_once('../LOGICA/adm_log_menu_tree.php');

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="iso-8859-1">
<title>Diagnostico Menu</title>
<style>
body { font-family: monospace; font-size: 13px; background: #1e1e1e; color: #d4d4d4; padding: 15px; }
.ok { color: #4ec9b0; font-weight: bold; }
.err { color: #f44747; font-weight: bold; }
.warn { color: #ce9178; }
.info { color: #569cd6; }
h2 { color: #fff; border-bottom: 2px solid #569cd6; padding-bottom: 6px; }
h3 { color: #c586c0; }
pre { background: #2d2d2d; padding: 10px; border: 1px solid #555; overflow-x: auto; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto; }
.found { background: #1a3a1a; border-color: #4ec9b0; }
.notfound { background: #3a1a1a; border-color: #f44747; }
</style>
</head>
<body>

<h2>1. Variables de Sesion</h2>
<pre>
Ses_Usu_Men: <b><?php echo htmlspecialchars(var_export($_SESSION['Ses_Usu_Men']??'UNDEF', true)); ?></b>
  -> Menu mode: <b><?php echo (($_SESSION['Ses_Usu_Men']??'') == 'B') ? '<span class="warn">VIEJO (jstree/adm_con_treemenu)</span>' : '<span class="ok">NUEVO (menuToHtml1)</span>'; ?></b>

Ses_Lis_Per: <?php echo htmlspecialchars(var_export($_SESSION['Ses_Lis_Per']??'UNDEF', true)); ?>
Ses_Dat_Dis: <?php echo htmlspecialchars(var_export($_SESSION['Ses_Dat_Dis']??'UNDEF', true)); ?>
</pre>

<h2>2. Conexion a BD</h2>
<?php
try {
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
    $ok = $obBD_conexion->conexion ? true : false;
    echo '<pre class="' . ($ok ? 'found' : 'notfound') . '">';
    echo "Conexion: " . ($ok ? '<span class="ok">OK</span>' : '<span class="err">FAIL</span>');
    if ($ok) {
        $r = $obBD_conexion->conexion->query("SELECT DATABASE() as db");
        $d = $r->fetch_assoc();
        echo "\nBD activa: " . $d['db'];
    }
    echo '</pre>';
} catch (Exception $e) {
    echo '<pre class="notfound">ERROR: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>

<h2>3. Test NUEVO menu (menuToHtml1)</h2>
<?php
$menu = new Class_Sys_Menu;
$mperf1 = $menu->buildProfileFilter($_SESSION['Ses_Lis_Per']);
echo '<pre>';
echo "Profile filter: " . htmlspecialchars($mperf1) . "\n";

$treeMenu = $menu->getMenuContainer2($_SESSION['Ses_Lis_Per'], $obBD_conexion);
$html = $menu->menuToHtml(1, $treeMenu, 'nav nav-list', 'hover');

$hasFlujo = stripos($html, 'Flujo') !== false;
$hasScraper = stripos($html, 'Scraper') !== false;
$hasAdq = stripos($html, 'adq_bandeja') !== false;
$hasScrap = stripos($html, 'scrapers.php') !== false;

echo "HTML length: " . strlen($html) . " bytes\n";
echo "Contiene 'Flujo de Adquisiciones': " . ($hasFlujo ? '<span class="ok">SI</span>' : '<span class="err">NO</span>') . "\n";
echo "Contiene 'Scraper SRI': " . ($hasScraper ? '<span class="ok">SI</span>' : '<span class="err">NO</span>') . "\n";
echo "Contiene 'adq_bandeja': " . ($hasAdq ? '<span class="ok">SI</span>' : '<span class="err">NO</span>') . "\n";
echo "Contiene 'scrapers.php': " . ($hasScrap ? '<span class="ok">SI</span>' : '<span class="err">NO</span>') . "\n";
echo '</pre>';
?>

<h2>4. Test VIEJO menu (adm_con_treemenu / sentencias_adm)</h2>
<?php
$obDT = new Class_Log_Datos_Adm;
$mperf = '';
foreach($_SESSION['Ses_Lis_Per'] as $item) {
    $mperf .= " perfiorgan.Per_Cod=" . $item . " OR";
}
$mperf = trim(substr($mperf, 1, strlen($mperf) - 3));

$rs = $obDT->consulta(sentencias_adm(16, $obDT->parametros($mperf)), $obBD_conexion->conexion);
$total = $obDT->numregistros();
echo '<pre>';
echo "Case 16 (root dirs): $total\n";
$foundOld = false;
while ($row = $obDT->fetch_assoc($rs)) {
    if (in_array($row['Org_Cod'], [224, 225])) {
        $foundOld = true;
        echo "  <span class='ok'>FOUND: Org_Cod={$row['Org_Cod']} - {$row['Org_Des']}</span>\n";
    }
}
$obDT->free_result($rs);
if (!$foundOld) {
    echo "  <span class='err'>NOT FOUND: Flujo(225) y Scraper(224) NO aparecen</span>\n";
}
echo '</pre>';

echo '<h2>5. HTML generado por el menú ACTUAL</h2>';
?>
<pre class="<?php echo ($hasFlujo && $hasScraper) ? 'found' : 'notfound'; ?>">
<?php
if ($hasFlujo || $hasScraper) {
    echo "Los modulos SÍ están en el HTML del menú.\n";
    echo "Si no se ven en la pantalla:\n";
    echo "  1. Limpiar caché del navegador (Ctrl+Shift+Del)\n";
    echo "  2. Hard refresh (Ctrl+F5)\n";
    echo "  3. Verificar si hay reglas CSS que ocultan los elementos\n";
    echo "  4. Verificar en DevTools (F12) si los elementos están ocultos\n";
} else {
    echo "Los modulos NO están en el HTML.\n";
}
?>
</pre>

<h2>6. Verificar caché PHP</h2>
<pre>
<?php
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status && isset($status['opcache_enabled'])) {
        echo "OPcache: " . ($status['opcache_enabled'] ? '<span class="warn">ACTIVADO (puede causar caché de código PHP)</span>' : '<span class="ok">Desactivado</span>') . "\n";
        if ($status['opcache_enabled']) {
            echo "  Para limpiar: reinicie Apache o ejecute opcache_reset()\n";
        }
    }
} else {
    echo "OPcache: No disponible\n";
}
echo "Fecha servidor: " . date('Y-m-d H:i:s') . "\n";
echo "PHP version: " . phpversion() . "\n";
?>

<h2>7. Verificar archivos modificados</h2>
<pre>
<?php
$files = [
    'administrador/LOGICA/sql.php' => 'Case 16 fix',
    'administrador/FRONT/adm_con_treemenu_dos_1.0.php' => 'Else branch fix',
];
foreach ($files as $f => $desc) {
    $full = dirname(__DIR__) . '/' . $f;
    if (file_exists($full)) {
        $mtime = date('Y-m-d H:i:s', filemtime($full));
        echo "$desc ($f):\n";
        echo "  Modificado: $mtime\n";
        echo "  Contiene '225': " . (strpos(file_get_contents($full), '225') !== false ? '<span class="ok">SI</span>' : '<span class="err">NO</span>') . "\n";
    } else {
        echo "$desc: <span class='err'>ARCHIVO NO ENCONTRADO</span>\n";
    }
}
?>
</pre>

<hr>
<p><a href="home.php" style="color:#569cd6">Volver a home.php</a></p>
</body>
</html>
