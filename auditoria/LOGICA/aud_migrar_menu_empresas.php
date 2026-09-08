<?php
/**
 * Migración y verificación del menú de Auditoría en bases de datos del ERP (servicios y exa)
 * Garantiza que la empresa "Capacitación Videos" y demás empresas carguen el directorio completo.
 */

require_once dirname(__FILE__) . '/../TEST/aud_test_lib.php';

function aud_sincronizar_menu_base($con, $dbName) {
    echo "=== SINCRONIZANDO MENU DE AUDITORIA EN BD: {$dbName} ===\n";

    // 1. Verificar/Crear ruta
    $qRuta = mysqli_query($con, "SELECT Rut_Cod FROM `{$dbName}`.rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1");
    if ($qRuta && mysqli_num_rows($qRuta) > 0) {
        $rRuta = mysqli_fetch_assoc($qRuta);
        $rutCod = (int)$rRuta['Rut_Cod'];
        echo "  Ruta /auditoria/FRONT/ existente: Rut_Cod = {$rutCod}\n";
    } else {
        mysqli_query($con, "INSERT INTO `{$dbName}`.rutas (`Rut_Des`, `Rut_Est`, `Rut_De2`) VALUES ('/auditoria/FRONT/', 'A', '/auditoria/FRONT/')");
        $rutCod = mysqli_insert_id($con);
        echo "  Ruta /auditoria/FRONT/ creada: Rut_Cod = {$rutCod}\n";
    }

    // 2. Verificar/Crear Módulo Raíz (Auditoria, Org_Niv = 0)
    $qMod = mysqli_query($con, "SELECT Org_Cod FROM `{$dbName}`.organizado WHERE Org_Des = 'Auditoria' AND Org_Niv = 0 LIMIT 1");
    if ($qMod && mysqli_num_rows($qMod) > 0) {
        $rMod = mysqli_fetch_assoc($qMod);
        $modCod = (int)$rMod['Org_Cod'];
        echo "  Modulo raiz Auditoria existente: Org_Cod = {$modCod}\n";
    } else {
        $maxOrdQ = mysqli_query($con, "SELECT IFNULL(MAX(Org_Ord), 0) + 1 FROM `{$dbName}`.organizado WHERE Org_Niv = 0 AND Org_Ord < 90");
        $maxOrd = ($maxOrdQ && $r = mysqli_fetch_row($maxOrdQ)) ? (int)$r[0] : 20;
        mysqli_query($con, "INSERT INTO `{$dbName}`.organizado (`Org_Niv`, `Org_Det`, `Org_Ord`, `Org_Mod`, `Org_Des`, `Org_Img`, `Org_Ime`, `Org_Ico`) 
            VALUES (0, 'Modulo para consultar y configurar el monitoreo de actividades', {$maxOrd}, 'A', 'Auditoria', 'folder-open-off.png', 'folder-open-on.png', 'fa fa-history')");
        $modCod = mysqli_insert_id($con);
        echo "  Modulo raiz Auditoria creado: Org_Cod = {$modCod}\n";
    }

    // 3. Verificar/Crear Subdirectorio (Monitoreo, Org_Niv = $modCod)
    $qSub = mysqli_query($con, "SELECT Org_Cod FROM `{$dbName}`.organizado WHERE Org_Des = 'Monitoreo' AND Org_Niv = {$modCod} LIMIT 1");
    if ($qSub && mysqli_num_rows($qSub) > 0) {
        $rSub = mysqli_fetch_assoc($qSub);
        $subCod = (int)$rSub['Org_Cod'];
        echo "  Subdirectorio Monitoreo existente: Org_Cod = {$subCod}\n";
    } else {
        mysqli_query($con, "INSERT INTO `{$dbName}`.organizado (`Org_Niv`, `Org_Det`, `Org_Ord`, `Org_Mod`, `Org_Des`, `Org_Img`, `Org_Ime`, `Org_Ico`) 
            VALUES ({$modCod}, 'Consulta y configuracion del monitoreo de actividades de usuarios', 1, 'A', 'Monitoreo', 'folder-open-off.png', 'folder-open-on.png', 'fa fa-eye')");
        $subCod = mysqli_insert_id($con);
        echo "  Subdirectorio Monitoreo creado: Org_Cod = {$subCod}\n";
    }

    // 4. Definición de Procesos bajo el subdirectorio Monitoreo
    $procesos = array(
        array(
            'nom' => 'aud_con_monitoreo_1.0.php',
            'lin' => 'Consultar',
            'det' => 'Permite consultar las actividades y cambios realizados por los usuarios',
            'ord' => 1,
            'ico' => 'fa fa-search'
        ),
        array(
            'nom' => 'aud_adm_config_monitoreo_1.0.php',
            'lin' => 'Configurar',
            'det' => 'Configura que modulos, directorios y procesos se registran en el monitoreo de actividades',
            'ord' => 2,
            'ico' => 'fa fa-cogs'
        ),
        array(
            'nom' => 'aud_con_actividad_usuarios_1.0.php',
            'lin' => 'Actividad de Usuarios',
            'det' => 'Monitor de sesiones activas, tiempo de uso, IP, ubicacion, navegadores y control de inactividad',
            'ord' => 3,
            'ico' => 'fa fa-users'
        ),
        array(
            'nom' => 'aud_con_dashboard_comparativo_1.0.php',
            'lin' => 'Dashboard Estadístico',
            'det' => 'Dashboard estadistico con comparativa temporal, emision de reporte PDF y envio por correo y WhatsApp',
            'ord' => 4,
            'ico' => 'fa fa-line-chart'
        ),
    );

    $pcsCodigos = array();

    foreach ($procesos as $p) {
        $qPcs = mysqli_query($con, "SELECT Pcs_Cod FROM `{$dbName}`.procesos WHERE Pcs_Nom = '{$p['nom']}' LIMIT 1");
        if ($qPcs && mysqli_num_rows($qPcs) > 0) {
            $rP = mysqli_fetch_assoc($qPcs);
            $pcsCod = (int)$rP['Pcs_Cod'];
            mysqli_query($con, "UPDATE `{$dbName}`.procesos SET 
                `Org_Cod` = {$subCod},
                `Pcs_Lin` = '{$p['lin']}',
                `Pcs_Det` = '{$p['det']}',
                `Pcs_Ord` = {$p['ord']},
                `Pcs_Est` = 'A',
                `Rut_Cod` = {$rutCod},
                `Pcs_Tip` = 'P',
                `Pcs_Ico` = '{$p['ico']}',
                `Pcs_Int` = 'N'
                WHERE `Pcs_Cod` = {$pcsCod}");
            echo "  Proceso {$p['nom']} actualizado: Pcs_Cod = {$pcsCod} bajo Monitoreo (Org_Cod = {$subCod})\n";
        } else {
            mysqli_query($con, "INSERT INTO `{$dbName}`.procesos 
                (`Org_Cod`, `Pcs_Det`, `Pcs_Ord`, `Pcs_Lin`, `Pcs_Est`, `Rut_Cod`, `Pcs_Nom`, `Tpr_Cod`, `Pcs_Img`, `Pcs_Tip`, `Pcs_Ico`, `Pcs_Int`)
                VALUES 
                ({$subCod}, '{$p['det']}', {$p['ord']}, '{$p['lin']}', 'A', {$rutCod}, '{$p['nom']}', 1, 'arrow-on.png', 'P', '{$p['ico']}', 'N')");
            $pcsCod = mysqli_insert_id($con);
            echo "  Proceso {$p['nom']} insertado: Pcs_Cod = {$pcsCod} bajo Monitoreo (Org_Cod = {$subCod})\n";
        }
        $pcsCodigos[] = $pcsCod;
    }

    // 5. Asignar en perfiorgan para perfiles clave
    // Perfiles específicos y roles Administrador de Sistemas y Gerente
    $perfilesCond = "Per_Des IN ('Administrador de Sistemas', 'Gerente', 'ADMINISTRADOR CAPACITACION VIDEOS') OR Per_Cod IN (1130, 1131, 890)";
    $qPer = mysqli_query($con, "SELECT Per_Cod, Per_Des FROM `{$dbName}`.perfiles WHERE {$perfilesCond}");
    $asignados = 0;
    while ($perRow = mysqli_fetch_assoc($qPer)) {
        $perCod = (int)$perRow['Per_Cod'];
        foreach ($pcsCodigos as $pcsCod) {
            $checkQ = mysqli_query($con, "SELECT 1 FROM `{$dbName}`.perfiorgan WHERE Per_Cod = {$perCod} AND Pcs_Cod = {$pcsCod}");
            if ($checkQ && mysqli_num_rows($checkQ) == 0) {
                mysqli_query($con, "INSERT INTO `{$dbName}`.perfiorgan (Per_Cod, Pcs_Cod) VALUES ({$perCod}, {$pcsCod})");
                $asignados++;
            }
        }
    }
    echo "  Asignaciones nuevas en perfiorgan: {$asignados}\n";

    return array(
        'modCod' => $modCod,
        'subCod' => $subCod,
        'pcsCodigos' => $pcsCodigos
    );
}

// Ejecutar para servicios y para exa
$con = aud_db_connect();
if (!$con) {
    echo "Error conectando a MySQL\n";
    exit(1);
}

aud_sincronizar_menu_base($con, 'servicios');
echo "\n";
aud_sincronizar_menu_base($con, 'exa');

echo "\n================ VERIFICACIÓN DE MENÚ PARA CAPACITACIÓN VIDEOS ================\n";
// Probar para el perfil 1130 (Administrador de Sistemas de Capacitación Videos en servicios)
$perCond = "perfiorgan.Per_Cod = 1130";
$sqlRaiz = "(SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, Org_Ico,
organizado.Org_Ime FROM servicios.organizado WHERE organizado.Org_Cod IN (SELECT organizado.Org_Niv FROM servicios.organizado WHERE organizado.Org_Cod IN 
(SELECT organizado.Org_Niv FROM servicios.procesos INNER JOIN servicios.perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN servicios.organizado ON
 (procesos.Org_Cod = organizado.Org_Cod) WHERE ($perCond))) ORDER BY organizado.Org_Ord) 
 UNION DISTINCT
 (SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, Org_Ico,
organizado.Org_Ime FROM servicios.organizado WHERE organizado.Org_Cod IN  
(SELECT organizado.Org_Niv FROM servicios.procesos INNER JOIN servicios.perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN servicios.organizado ON
 (procesos.Org_Cod = organizado.Org_Cod) WHERE ($perCond)) ORDER BY organizado.Org_Ord)";

$qRaiz = mysqli_query($con, $sqlRaiz);
$auditoriaEnRaiz = false;
$audOrgCod = null;
while ($r = mysqli_fetch_assoc($qRaiz)) {
    if (stripos($r['Org_Des'], 'auditoria') !== false) {
        $auditoriaEnRaiz = true;
        $audOrgCod = $r['Org_Cod'];
        echo "  [OK] Modulo raiz cargado en menu: [{$r['Org_Cod']}] {$r['Org_Des']}\n";
    }
}

if (!$auditoriaEnRaiz) {
    echo "  [ERROR] Auditoria NO cargo en el menu raiz!\n";
} else {
    // Probar subdirectorios bajo Auditoria
    $sqlSub = "SELECT DISTINCT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, organizado.Org_Ime, organizado.Org_Ico
        FROM servicios.organizado WHERE organizado.Org_Niv = {$audOrgCod} AND Org_Mod = 'A' ORDER BY organizado.Org_Ord";
    $qSub = mysqli_query($con, $sqlSub);
    $subOrgCod = null;
    while ($r = mysqli_fetch_assoc($qSub)) {
        echo "  [OK] Subdirectorio cargado: [{$r['Org_Cod']}] {$r['Org_Des']} (Padre: {$r['Org_Niv']})\n";
        if (stripos($r['Org_Des'], 'monitoreo') !== false) {
            $subOrgCod = $r['Org_Cod'];
        }
    }

    if ($subOrgCod) {
        // Probar procesos bajo Monitoreo para perfil 1130
        $sqlProc = "SELECT DISTINCT procesos.Pcs_Cod, procesos.Org_Cod, procesos.Pcs_Ord, procesos.Pcs_Lin, rutas.Rut_Des,
            procesos.Pcs_Nom, procesos.Pcs_Img, procesos.Pcs_Det, Pcs_Ico
            FROM servicios.rutas
            INNER JOIN servicios.procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
            INNER JOIN servicios.perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
            WHERE procesos.Pcs_Est = 'A' AND procesos.Pcs_Tip = 'P' AND procesos.Org_Cod = {$subOrgCod} AND ($perCond)
            ORDER BY procesos.Pcs_Ord";
        $qProc = mysqli_query($con, $sqlProc);
        $totalProcesos = 0;
        while ($r = mysqli_fetch_assoc($qProc)) {
            $totalProcesos++;
            echo "    [OK] Proceso {$totalProcesos}: [{$r['Pcs_Cod']}] {$r['Pcs_Lin']} -> {$r['Rut_Des']}{$r['Pcs_Nom']}\n";
        }
        echo "  Total procesos cargados para Administrador de Sistemas de Capacitacion Videos: {$totalProcesos}/4\n";
    }
}
