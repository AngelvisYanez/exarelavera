<?php
// DEBUG TOOL - DISABLED
if (false) {
/**
 * DIAGNÓSTICO COMPLETO DEL MENÚ
 * Ejecutar desde el navegador estando logueado en el admin.
 * URL: http://localhost/exa/administrador/FRONT/debug_menu.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/logica.php');

if (!isset($_SESSION['Ses_Lis_Per'])) {
    die('ERROR: No hay sesión activa. Debe estar logueado.');
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="iso-8859-1"><title>Debug Menu</title>
<style>
body { font-family: monospace; font-size: 12px; background: #1e1e1e; color: #d4d4d4; padding: 10px; }
.ok { color: #4ec9b0; }
.err { color: #f44747; }
.info { color: #569cd6; }
.warn { color: #ce9178; }
pre { background: #2d2d2d; padding: 8px; border: 1px solid #555; overflow-x: auto; white-space: pre-wrap; }
h3 { color: #fff; border-bottom: 1px solid #555; padding-bottom: 4px; }
</style>
</head>
<body>
<h3>PASO 1: Variables de Sesión</h3>
<pre>
Ses_Lis_Per: <?php echo json_encode($_SESSION['Ses_Lis_Per']); ?>
Ses_Usu_Men: <?php echo json_encode($_SESSION['Ses_Usu_Men'] ?? 'NO DEFINIDO'); ?>
Ses_Dat_Dis: <?php echo json_encode($_SESSION['Ses_Dat_Dis'] ?? 'NO DEFINIDO'); ?>
Ses_Emp_Cod: <?php echo json_encode($_SESSION['Ses_Emp_Cod'] ?? 'NO DEFINIDO'); ?>
</pre>

<?php
// --- PASO 2: Crear conexión (misma lógica que home.php línea 143) ---
echo '<h3>PASO 2: Conexión a BD</h3>';
try {
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
    $conValid = $obBD_conexion->conexion ? 'SI' : 'NO';
    echo "<pre>Conexión: <span class='".($conValid=='SI'?'ok':'err')."'>$conValid</span>";
    if ($conValid == 'SI') {
        $dbResult = $obBD_conexion->conexion->query("SELECT DATABASE() as db");
        $dbRow = $dbResult->fetch_assoc();
        echo "\nBase de datos actual: " . $dbRow['db'];
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre class='err'>ERROR conexión: " . $e->getMessage() . "</pre>";
}

// --- PASO 3: Probar Class_Sys_Menu directamente ---
echo '<h3>PASO 3: Class_Sys_Menu - Instanciación</h3>';
try {
    require_once("../LOGICA/adm_log_menu_tree.php");
    $menuObj = new Class_Sys_Menu;
    echo "<pre class='ok'>Class_Sys_Menu creada OK. Sentencias: " . $menuObj->getSentencias() . "</pre>";
} catch (Throwable $e) {
    echo "<pre class='err'>ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    die('No se puede continuar sin Class_Sys_Menu');
}

// --- PASO 4: buildProfileFilter ---
echo '<h3>PASO 4: Profile Filter</h3>';
$mperf1 = $menuObj->buildProfileFilter($_SESSION['Ses_Lis_Per']);
echo "<pre>Resultado: '<span class='info'>$mperf1</span>'\nVacío: " . (empty($mperf1) ? 'SI (ERROR - sin perfiles)' : 'NO') . "</pre>";

// --- PASO 5: Consulta caso 2 (Directorios) ---
echo '<h3>PASO 5: Case 2 - Directorios (organizado)</h3>';
$raw_org = $menuObj->getArrayConsulta(2, '*' . $mperf1, $obBD_conexion);
echo "<pre>Total directorios: <span class='info'>" . count($raw_org) . "</span>";

// Buscar los nuestros
$nuevos_org = array_filter($raw_org, function($o) {
    return in_array($o['Org_Cod'], [224, 225]);
});
foreach ($nuevos_org as $o) {
    echo "\n  -> Org_Cod:{$o['Org_Cod']} | {$o['Org_Des']} | Niv:{$o['Org_Niv']} | Ord:{$o['Org_Ord']} | Mod:{$o['Org_Mod']}";
}
echo "</pre>";

// --- PASO 6: groupBy Org_Niv ---
echo '<h3>PASO 6: groupBy Org_Niv</h3>';
$Organiza = groupBy($raw_org, 'Org_Niv');
echo "<pre>Claves de group: " . implode(', ', array_keys($Organiza)) . "";
echo "\nOrganiza[0] count: " . (isset($Organiza[0]) ? count($Organiza[0]) : 'NO EXISTE');

if (isset($Organiza[0])) {
    $encontrados_0 = array_filter($Organiza[0], function($o) {
        return in_array($o['Org_Cod'], [224, 225]);
    });
    foreach ($encontrados_0 as $o) {
        echo "\n  [0] -> Org_Cod:{$o['Org_Cod']} | {$o['Org_Des']}";
    }
}
echo "</pre>";

// --- PASO 7: Consulta caso 3 (Procesos) ---
echo '<h3>PASO 7: Case 3 - Procesos</h3>';
$raw_proc = $menuObj->getArrayConsulta(3, '*' . $mperf1 . '*P', $obBD_conexion);
echo "<pre>Total procesos: <span class='info'>" . count($raw_proc) . "</span>";

$nuevos_proc = array_filter($raw_proc, function($p) {
    return $p['Org_Cod'] == 225 || $p['Org_Cod'] == 224;
});
foreach ($nuevos_proc as $p) {
    echo "\n  -> Pcs_Cod:{$p['Pcs_Cod']} | {$p['Pcs_Lin']} | Org_Cod:{$p['Org_Cod']} | Rut:{$p['Rut_Des']}{$p['Pcs_Nom']}";
}
echo "</pre>";

// --- PASO 8: groupBy Org_Cod ---
echo '<h3>PASO 8: groupBy Org_Cod (Procesos)</h3>';
$Procesos = groupBy($raw_proc, 'Org_Cod');
echo "<pre>Claves de group: " . implode(', ', array_slice(array_keys($Procesos), 0, 20)) . "...";
foreach ([224, 225] as $cod) {
    if (isset($Procesos[$cod])) {
        echo "\n  Procesos[$cod] count: " . count($Procesos[$cod]);
    } else {
        echo "\n  Procesos[$cod]: <span class='err'>NO EXISTE</span>";
    }
}
echo "</pre>";

// --- PASO 9: Construcción del árbol TreeMenu ---
echo '<h3>PASO 9: Árbol TreeMenu (setMenuPages)</h3>';
try {
    $menu = new TreeMenu();
    $menuObj->setMenuPages($menu, 0, $Organiza, $Procesos);
    $pages = $menu->getPages();
    echo "<pre>Nodos raíz: <span class='info'>" . count($pages) . "</span>\n";

    foreach ($pages as $p) {
        $id = $p->get('id');
        $label = $p->get('label');
        $type = $p->get('itemType');
        $hasPages = $p->hasPages() ? 'SI' : 'NO';
        $hasProc = $p->hasProccess() ? 'SI' : 'NO';
        $href = $p->getHref() ?: '(null)';
        $childCount = count($p->getPages());

        $isNew = in_array($id, [224, 225]);
        $prefix = $isNew ? '>>>' : '   ';

        echo "$prefix [$type] ID:$id | $label | hasPages:$hasPages | hasProccess:$hasProc | href:$href | children:$childCount";

        if ($isNew && $p->hasPages()) {
            foreach ($p->getPages() as $child) {
                $cType = $child->get('itemType');
                $cLabel = $child->get('label');
                $cHref = $child->getHref() ?: '(null)';
                echo "\n     ↳ [$cType] $cLabel | href:$cHref";
            }
        }
        echo "\n";
    }
    echo "</pre>";
} catch (Throwable $e) {
    echo "<pre class='err'>ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}

// --- PASO 10: Renderizado HTML del menú ---
echo '<h3>PASO 10: HTML generado (fragmento de nuestros módulos)</h3>';
try {
    $html = $menuObj->menuToHtml(1, $menu, 'nav nav-list', 'hover');
    
    // Buscar fragmentos relevantes
    $busquedas = ['Flujo', 'Scraper', 'SRI', 'adq_', 'scrapers'];
    $encontrado = false;
    foreach ($busquedas as $busq) {
        if (stripos($html, $busq) !== false) {
            $encontrado = true;
        }
    }
    
    echo "<pre>Longitud HTML: <span class='info'>" . strlen($html) . "</span> bytes";
    echo "\nContiene 'Flujo': " . (stripos($html, 'Flujo') !== false ? '<span class="ok">SI</span>' : '<span class="err">NO</span>');
    echo "\nContiene 'Scraper': " . (stripos($html, 'Scraper') !== false ? '<span class="ok">SI</span>' : '<span class="err">NO</span>');
    echo "\nContiene 'adq_bandeja': " . (stripos($html, 'adq_bandeja') !== false ? '<span class="ok">SI</span>' : '<span class="err">NO</span>');
    echo "\nContiene 'scrapers.php': " . (stripos($html, 'scrapers.php') !== false ? '<span class="ok">SI</span>' : '<span class="err">NO</span>');
    
    // Extraer contexto alrededor de "Flujo"
    if (stripos($html, 'Flujo') !== false) {
        $pos = stripos($html, 'Flujo');
        $start = max(0, $pos - 200);
        $len = strlen($html) - $start;
        echo "\n\n--- Contexto alrededor de 'Flujo' ---\n";
        echo htmlspecialchars(substr($html, $start, 600));
    }
    
    // Extraer contexto alrededor de "Scraper"
    if (stripos($html, 'Scraper') !== false) {
        $pos = stripos($html, 'Scraper');
        $start = max(0, $pos - 200);
        echo "\n\n--- Contexto alrededor de 'Scraper' ---\n";
        echo htmlspecialchars(substr($html, $start, 600));
    }
    
    echo "</pre>";
} catch (Throwable $e) {
    echo "<pre class='err'>ERROR renderizando: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}

// --- PASO 11: Verificar hasItemType ---
echo '<h3>PASO 11: Verificación de hasItemType (posible bug)</h3>';
try {
    if (isset($Procesos[225])) {
        $testItem = new TreeMenuItem(array('id' => 225, 'label' => 'Test Flujo', 'itemType' => 'G'));
        foreach ($Procesos[225] as $p) {
            $child = new TreeMenuItem(array('id' => $p['Pcs_Cod'], 'label' => $p['Pcs_Lin'], 'itemType' => 'D', 'href' => $p['Rut_Des'] . $p['Pcs_Nom']));
            $testItem->addPage($child);
        }
        
        echo "<pre>hasItemType('D') en directorio Flujo: " . ($testItem->hasItemType('D') ? '<span class="ok">TRUE</span>' : '<span class="err">FALSE (BUG)</span>');
        echo "\nhasProccess() en directorio Flujo: " . ($testItem->hasProccess() ? '<span class="ok">TRUE</span>' : '<span class="err">FALSE (BUG)</span>');
        echo "\nhasPages() en directorio Flujo: " . ($testItem->hasPages() ? '<span class="ok">TRUE</span>' : '<span class="err">FALSE</span>');
        echo "\nchild count: " . count($testItem->getPages());
        echo "</pre>";
    }
} catch (Throwable $e) {
    echo "<pre class='err'>ERROR: " . $e->getMessage() . "</pre>";
}

// --- RESUMEN ---
echo '<h3>RESUMEN</h3>';
echo "<pre class='" . ($encontrado ? 'ok' : 'err') . "'>";
if ($encontrado) {
    echo "Los módulos SÍ están en el HTML generado.\nEl problema podría ser:\n";
    echo "  1. Caché del navegador (Ctrl+F5)\n";
    echo "  2. CSS ocultando los elementos\n";
    echo "  3. JavaScript manipulando el menú después de carga";
} else {
    echo "Los módulos NO están en el HTML.\nRevise los pasos anteriores para identificar dónde se pierden.";
}
echo "</pre>";
?>
<br><a href="home.php" style="color:#569cd6">← Volver a home.php</a>
</body>
</html>
<?php } ?>
