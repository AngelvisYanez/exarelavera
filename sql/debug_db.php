<?php
/**
 * Debug: verificar exa_master.data y la consulta de loadBasesDatosDirectorio
 */
echo "=== 1. Verificar tabla exa_master.data ===" . PHP_EOL;
$conn = new mysqli('localhost', 'root', '', 'exa_master', 3306);
$conn->set_charset('utf8');

$r = $conn->query("SHOW TABLES LIKE 'data'");
if ($r && $r->num_rows > 0) {
    echo "  Tabla 'data' existe en exa_master" . PHP_EOL;
    
    $r = $conn->query("SELECT * FROM data LIMIT 10");
    if ($r && $r->num_rows > 0) {
        echo "  Contenido:" . PHP_EOL;
        while ($row = $r->fetch_assoc()) {
            echo "    " . json_encode($row) . PHP_EOL;
        }
    } else {
        echo "  Tabla 'data' esta VACIA" . PHP_EOL;
    }
    
    // Verificar columnas
    $r = $conn->query("DESCRIBE data");
    echo "  Columnas:" . PHP_EOL;
    while ($row = $r->fetch_assoc()) {
        echo "    {$row['Field']} ({$row['Type']})" . PHP_EOL;
    }
} else {
    echo "  Tabla 'data' NO existe en exa_master" . PHP_EOL;
    
    // Buscar en todas las bases
    echo PHP_EOL . "=== Buscar tabla 'data' en todas las bases ===" . PHP_EOL;
    $r = $conn->query("SELECT table_schema, table_name FROM information_schema.tables WHERE table_name = 'data' AND table_schema NOT IN ('mysql','information_schema','performance_schema','sys')");
    while ($row = $r->fetch_assoc()) {
        echo "  {$row['table_schema']}.{$row['table_name']}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== 2. Probar la consulta exacta del PHP ===" . PHP_EOL;
$sql = "SELECT DISTINCT Dat_Dis 
        FROM exa_master.data 
        WHERE Dat_Dis IS NOT NULL AND Dat_Dis != '' AND Dat_Est = 'A'
        ORDER BY Dat_Dis ASC";
echo "  SQL: $sql" . PHP_EOL;
$r = $conn->query($sql);
if ($r) {
    echo "  Resultado: {$r->num_rows} filas" . PHP_EOL;
    while ($row = $r->fetch_assoc()) {
        echo "    Dat_Dis = {$row['Dat_Dis']}" . PHP_EOL;
    }
} else {
    echo "  ERROR: ($conn->errno) $conn->error" . PHP_EOL;
}

echo PHP_EOL . "=== 3. Verificar base exa (donde estan los datos) ===" . PHP_EOL;
$conn2 = new mysqli('localhost', 'root', '', 'exa', 3306);
$conn2->set_charset('utf8');

$r = $conn2->query("SHOW TABLES LIKE 'data'");
if ($r && $r->num_rows > 0) {
    echo "  Tabla 'data' existe en exa" . PHP_EOL;
    $r = $conn2->query("SELECT * FROM data LIMIT 10");
    if ($r && $r->num_rows > 0) {
        while ($row = $r->fetch_assoc()) {
            echo "    " . json_encode($row) . PHP_EOL;
        }
    }
} else {
    echo "  Tabla 'data' NO existe en exa" . PHP_EOL;
}

// Probar consulta sobre exa_master con data de exa
echo PHP_EOL . "=== 4. Que tablas tiene exa_master? ===" . PHP_EOL;
$r = $conn->query("SHOW TABLES");
while ($row = $r->fetch_assoc()) {
    $table = $row['Tables_in_exa_master'];
    echo "  $table" . PHP_EOL;
}

$conn->close();
$conn2->close();
