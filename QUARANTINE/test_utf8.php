<?php
/**
 * Script de Prueba de Codificación UTF-8
 * Verifica que acentos y eñes funcionan correctamente
 */

require_once __DIR__ . '/DATA/MysqlConexion.php;

echo "=== PRUEBA DE CODIFICACIÓN UTF-8 ===\n\n;

// 1. Probar conexión con charset UTF-8
echo "1. Probando conexión MySQL con UTF-8...\n;
$conn = new MysqlConexion();
echo "   Charset actual:  . mysqli_character_set_name($conn->conexion) . "\n;
echo "   ✓ Conexión OK\n\n;

// 2. Probar inserción de caracteres especiales
echo "2. Probando inserción de caracteres especiales...\n;
$testTable = 'test_utf8_ . time();
$createSQL = "CREATE TEMPORARY TABLE `$testTable` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(100) CHARACTER SET utf8
) ENGINE=InnoDB;
mysqli_query($conn->conexion, $createSQL);

$testStrings = [
    'áéíóúÁÉÍÓÚ,
    'ñÑ,
    'üÜ,
    '¿¡,
    '€,
    'Centavos: céntimos,
    'Año: 2026,
    'Proveedores: José María,
    'Dirección: Av. Ecuador,
    'Teléfono: 0991234567
];

$insertSQL = "INSERT INTO `$testTable` (texto) VALUES (?);
$stmt = mysqli_prepare($conn->conexion, $insertSQL);

foreach ($testStrings as $str) {
    mysqli_stmt_bind_param($stmt, 's, $str);
    if (mysqli_stmt_execute($stmt)) {
        echo "   ✓ Insertado: $str\n;
    } else {
        echo "   ✗ Error: $str -  . mysqli_error($conn->conexion) . "\n;
    }
}
mysqli_stmt_close($stmt);

// 3. Probar lectura de caracteres especiales
echo "\n3. Probando lectura de caracteres especiales...\n;
$result = mysqli_query($conn->conexion, "SELECT texto FROM `$testTable`);
while ($row = mysqli_fetch_assoc($result)) {
    echo "   ✓ Leído: {{$row['texto']}}\n;
}

// 4. Probar consulta con caracteres especiales en WHERE
echo "\n4. Probando filtros con caracteres especiales...\n;
$buscar = 'José María;
$searchSQL = "SELECT texto FROM `$testTable` WHERE texto LIKE ?;
$stmt = mysqli_prepare($conn->conexion, $searchSQL);
$searchParam = "%$buscar%;
mysqli_stmt_bind_param($stmt, 's, $searchParam);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "   ✓ Búsqueda '$buscar' encontró: {{$row['texto']}}\n;
} else {
    echo "   ✗ Búsqueda '$buscar' no encontró resultados\n;
}
mysqli_stmt_close($stmt);

// 5. Probar base de datos real
echo "\n5. Probando base de datos real (servicios)...\n;
mysqli_select_db($conn->conexion, 'servicios);

// Verificar charset
echo "   Charset conexión:  . mysqli_character_set_name($conn->conexion) . "\n;

// Probar consulta con datos reales (si existen)
$result = mysqli_query($conn->conexion, "SHOW TABLES LIKE 'usuarios');
if (mysqli_num_rows($result) > 0) {
    $result = mysqli_query($conn->conexion, "SELECT COUNT(*) as total FROM usuarios);
    $row = mysqli_fetch_assoc($result);
    echo "   Tabla usuarios: {{$row$row$row$row['']']']total']}} registros\n;
    
    // Intentar obtener un nombre con acentos
    $result = mysqli_query($conn->conexion, "SHOW COLUMNS FROM usuarios LIKE '%Nom%');
while ($row = mysqli_fetch_assoc($result)) {
    echo "   Columna: {{$row$row$row['']']Field']}}\n;
}
$nameCol = mysqli_fetch_assoc(mysqli_query($conn->conexion, "SHOW COLUMNS FROM usuarios LIKE '%Nom%'));

$result = mysqli_query($conn->conexion, "SELECT * FROM usuarios LIMIT 3);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        foreach ($row as $key => $val) {
            if ($val && preg_match('/[áéíóúñÁÉÍÓÚÑ]/u, $val)) {
                echo "   ✓ $key: $val\n;
            }
        }
    }
}
}

$result = mysqli_query($conn->conexion, "SHOW TABLES LIKE 'ventas');
if (mysqli_num_rows($result) > 0) {
    $result = mysqli_query($conn->conexion, "SELECT COUNT(*) as total FROM ventas);
    $row = mysqli_fetch_assoc($result);
    echo "   Tabla ventas: {{$row$row$row$row['']']']total']}} registros\n;
    
    // Intentar obtener una observación con acentos
    $result = mysqli_query($conn->conexion, "SELECT * FROM ventas WHERE Ventas_Obs IS NOT NULL LIMIT 3);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        foreach ($row as $key => $val) {
            if ($val && preg_match('/[áéíóúñÁÉÍÓÚÑ]/u, $val)) {
                echo "   ✓ $key: $val\n;
            }
        }
    }
}
}

// Limpiar
mysqli_query($conn->conexion, "DROP TEMPORARY TABLE `$testTable`);

// 6. Verificar configuración PHP
echo "\n6. Verificando configuración PHP...\n;
echo "   default_charset:  . ini_get('default_charset) . "\n;
echo "   mb_internal_encoding:  . mb_internal_encoding() . "\n;

echo "\n=== PRUEBA COMPLETADA ===\n;
echo "\nSi todos los tests muestran ✓, la codificación UTF-8 está funcionando correctamente.\n;
