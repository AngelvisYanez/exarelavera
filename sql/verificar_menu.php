<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
/**
 * Script de verificación del menú
 * Ejecutar desde línea de comandos: php sql/verificar_menu.php
 */

require_once __DIR__ . '/../config_db.php';

echo "=== VERIFICACIÓN DEL MENÚ ===\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    
    echo "1. VERIFICANDO RUTAS:\n";
    echo str_repeat('-', 60) . "\n";
    $result = $conn->query("SELECT Rut_Cod, Rut_Des, Rut_De2, Rut_Est FROM rutas WHERE Rut_Des LIKE '%flujo%' OR Rut_Des LIKE '%administrador%' ORDER BY Rut_Cod");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  Rut_Cod: {$row['Rut_Cod']} | Rut_Des: {$row['Rut_Des']} | Rut_Est: {$row['Rut_Est']}\n";
        }
    } else {
        echo "  ⚠ No se encontraron rutas\n";
    }
    
    echo "\n2. VERIFICANDO DIRECTORIOS (organizado):\n";
    echo str_repeat('-', 60) . "\n";
    $result = $conn->query("SELECT Org_Cod, Org_Des, Org_Niv, Org_Ord, Org_Mod FROM organizado WHERE Org_Des IN ('Scraper SRI', 'Flujo de Adquisiciones') ORDER BY Org_Ord");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  Org_Cod: {$row['Org_Cod']} | Org_Des: {$row['Org_Des']} | Org_Niv: {$row['Org_Niv']} | Org_Ord: {$row['Org_Ord']} | Org_Mod: {$row['Org_Mod']}\n";
        }
    } else {
        echo "  ⚠ No se encontraron directorios\n";
    }
    
    echo "\n3. VERIFICANDO PROCESOS:\n";
    echo str_repeat('-', 60) . "\n";
    $result = $conn->query("
        SELECT p.Pcs_Cod, p.Pcs_Lin, p.Pcs_Nom, p.Pcs_Tip, p.Pcs_Est, p.Pcs_Ord, p.Rut_Cod, p.Org_Cod,
               r.Rut_Des as Ruta
        FROM procesos p
        LEFT JOIN rutas r ON p.Rut_Cod = r.Rut_Cod
        WHERE p.Pcs_Nom IN (
            'scrapers.php',
            'adq_bandeja.php', 'adq_solicitud.php', 'adq_lista_solicitud.php',
            'adq_dashboard.php', 'adq_seguimiento.php', 'adq_configuracion.php',
            'adq_departamentos.php'
        )
        ORDER BY p.Org_Cod, p.Pcs_Ord
    ");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  Pcs_Cod: {$row['Pcs_Cod']} | {$row['Pcs_Lin']} | Pcs_Nom: {$row['Pcs_Nom']} | Tip: {$row['Pcs_Tip']} | Est: {$row['Pcs_Est']} | Ord: {$row['Pcs_Ord']} | Rut_Cod: {$row['Rut_Cod']} | Org_Cod: {$row['Org_Cod']} | Ruta: {$row['Ruta']}\n";
        }
    } else {
        echo "  ⚠ No se encontraron procesos\n";
    }
    
    echo "\n4. VERIFICANDO ASIGNACIONES A PERFILES (perfiorgan):\n";
    echo str_repeat('-', 60) . "\n";
    $result = $conn->query("
        SELECT pf.Per_Cod, pf.Pcs_Cod, p.Pcs_Lin, p.Pcs_Nom
        FROM perfiorgan pf
        JOIN procesos p ON pf.Pcs_Cod = p.Pcs_Cod
        WHERE p.Pcs_Nom IN (
            'scrapers.php',
            'adq_bandeja.php', 'adq_solicitud.php', 'adq_lista_solicitud.php',
            'adq_dashboard.php', 'adq_seguimiento.php', 'adq_configuracion.php',
            'adq_departamentos.php'
        )
        ORDER BY pf.Per_Cod, pf.Pcs_Cod
    ");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  Per_Cod: {$row['Per_Cod']} | Pcs_Cod: {$row['Pcs_Cod']} | {$row['Pcs_Lin']} ({$row['Pcs_Nom']})\n";
        }
    } else {
        echo "  ⚠ No se encontraron asignaciones de perfil\n";
    }
    
    echo "\n5. SIMULANDO CONSULTA DEL MENÚ (como la hace home.php):\n";
    echo str_repeat('-', 60) . "\n";
    
    // Simular getMenuContainer2 con perfil Administrador (Per_Cod = 1)
    $perfilFilter = "perfiorgan.Per_Cod=1";
    
    echo "  Filtro de perfil: $perfilFilter\n\n";
    
    // Case 2: Directorios
    echo "  Case 2 (Directorios):\n";
    $sql2 = "SELECT DISTINCT
                organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des,
                organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img,
                organizado.Org_Ime, organizado.Org_Ico
             FROM organizado
             WHERE Org_Mod='A'
             ORDER BY organizado.Org_Niv, IF(organizado.Org_Niv=0, organizado.Org_Ord, organizado.Org_Cod)";
    
    $result2 = $conn->query($sql2);
    $directorios = [];
    while ($row = $result2->fetch_assoc()) {
        $directorios[] = $row;
    }
    
    $nuevosDir = array_filter($directorios, function($d) {
        return in_array($d['Org_Des'], ['Scraper SRI', 'Flujo de Adquisiciones']);
    });
    
    echo "    Total directorios: " . count($directorios) . "\n";
    echo "    Directorios nuevos encontrados: " . count($nuevosDir) . "\n";
    foreach ($nuevosDir as $d) {
        echo "      - {$d['Org_Des']} (Org_Cod: {$d['Org_Cod']}, Org_Niv: {$d['Org_Niv']}, Org_Ord: {$d['Org_Ord']})\n";
    }
    
    // Case 3: Procesos
    echo "\n  Case 3 (Procesos):\n";
    $sql3 = "SELECT DISTINCT procesos.Pcs_Cod, procesos.Org_Cod, procesos.Pcs_Ord,
                    procesos.Pcs_Lin, rutas.Rut_Des, procesos.Pcs_Nom,
                    procesos.Pcs_Img, procesos.Pcs_Det, procesos.Pcs_Ico
             FROM rutas
             INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
             INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
             WHERE procesos.Pcs_Est='A' AND procesos.Pcs_Tip = 'P'
             AND ($perfilFilter)
             ORDER BY procesos.Pcs_Ord";
    
    $result3 = $conn->query($sql3);
    $procesos = [];
    while ($row = $result3->fetch_assoc()) {
        $procesos[] = $row;
    }
    
    $nuevosProcs = array_filter($procesos, function($p) {
        return in_array($p['Pcs_Nom'], [
            'scrapers.php', 'adq_bandeja.php', 'adq_solicitud.php', 
            'adq_lista_solicitud.php', 'adq_dashboard.php', 'adq_seguimiento.php', 
            'adq_configuracion.php', 'adq_departamentos.php'
        ]);
    });
    
    echo "    Total procesos: " . count($procesos) . "\n";
    echo "    Procesos nuevos encontrados: " . count($nuevosProcs) . "\n";
    foreach ($nuevosProcs as $p) {
        echo "      - {$p['Pcs_Lin']} (Org_Cod: {$p['Org_Cod']}, Ruta: {$p['Rut_Des']})\n";
    }
    
    // Verificar si los procesos están agrupados bajo los directorios correctos
    echo "\n  Agrupación por directorio:\n";
    foreach ($nuevosDir as $dir) {
        $hijos = array_filter($nuevosProcs, function($p) use ($dir) {
            return $p['Org_Cod'] == $dir['Org_Cod'];
        });
        echo "    {$dir['Org_Des']} (Org_Cod: {$dir['Org_Cod']}): " . count($hijos) . " procesos\n";
        foreach ($hijos as $h) {
            echo "      → {$h['Pcs_Lin']}\n";
        }
    }
    
    $conn->close();
    
    echo "\n=== FIN DE VERIFICACIÓN ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
