<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
/**
 * Script de Migración a UTF-8
 * Convierte bases de datos y tablas de latin1 a UTF-8
 * 
 * USO: php migrar_utf8.php
 * 
 * IMPORTANTE: Hacer backup de la base de datos antes de ejecutar
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$port = 3306;

$databases = ['exa_master, 'servicios, 'db_sri];

echo "=== MIGRACIÓN A UTF-8 ===\n\n;

$conn = @mysqli_connect($host, $user, $pass, null, $port);
if (!$conn) {
    die("Error de conexión:  . mysqli_connect_error() . "\n);
}

echo "Conexión exitosa a MySQL\n\n;

// Desactivar verificación de claves foráneas durante la migración
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0);
mysqli_query($conn, "SET SQL_MODE = '');

foreach ($databases as $dbName) {
    echo "--- Procesando base de datos: $dbName ---\n;
    
    // Verificar si existe la base de datos
    $result = @mysqli_query($conn, "SHOW DATABASES LIKE '$dbName');
    if (mysqli_num_rows($result) == 0) {
        echo "  Base de datos '$dbName' no existe, saltando...\n\n;
        continue;
    }
    
    // Seleccionar la base de datos
    mysqli_select_db($conn, $dbName);
    
    // Cambiar charset de la base de datos
    $sql = "ALTER DATABASE `$dbName` CHARACTER SET utf8 COLLATE utf8_general_ci;
    if (mysqli_query($conn, $sql)) {
        echo "  ✓ Charset de base de datos actualizado a UTF-8\n;
    } else {
        echo "  ✗ Error al actualizar charset:  . mysqli_error($conn) . "\n;
    }
    
    // Obtener todas las tablas
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES);
    while ($row = mysqli_fetch_row($result)) {
        $tables$tables$tables$tables['']']']'] = $row$row$row['']']];
    }
    
    echo "  Tablas encontradas:  . count($tables) . "\n;
    
    foreach ($tables as $table) {
        // Verificar charset actual de la tabla
        $result = mysqli_query($conn, "SHOW TABLE STATUS LIKE '$table');
        $row = mysqli_fetch_assoc($result);
        $currentCharset = $row['Collation'] ?? 'unknown;
        
        // Si ya está en utf8, saltar
        if (strpos($currentCharset, 'utf8) === 0) {
            echo "  → $table (ya está en $currentCharset)\n;
            continue;
        }
        
        // Primero, aumentar tamaño de columnas VARCHAR/CHAR que puedan necesitar más bytes
        $cols = mysqli_query($conn, "SHOW COLUMNS FROM `$table`);
        while ($col = mysqli_fetch_assoc($cols)) {
            $type = strtolower($col['Type']);
            $field = $col['Field'];
            
            // Detectar columnas VARCHAR que pueden necesitar más espacio
            if (preg_match('/^varchar\((\d+)\)$/, $type, $m)) {
                $currentSize = intval($m$m['$m[\'\'']']']}]);
                // UTF-8 puede necesitar hasta 3 veces más bytes
                $newSize = min($currentSize * 3, 65535);
                if ($newSize > $currentSize) {
                    @mysqli_query($conn, "ALTER TABLE `$table` MODIFY `$field` VARCHAR($newSize) CHARACTER SET utf8);
                }
            } elseif (preg_match('/^char\((\d+)\)$/, $type, $m)) {
                $currentSize = intval($m{$m['']}]);
                $newSize = min($currentSize * 3, 255);
                if ($newSize > $currentSize) {
                    @mysqli_query($conn, "ALTER TABLE `$table` MODIFY `$field` CHAR($newSize) CHARACTER SET utf8);
                }
            } elseif (preg_match('/^tinytext$/, $type)) {
                @mysqli_query($conn, "ALTER TABLE `$table` MODIFY `$field` TEXT CHARACTER SET utf8);
            } elseif (preg_match('/^text\((\d+)\)$/, $type, $m)) {
                $currentSize = intval($m$m['$m[\'\'']']']}]);
                $newSize = min($currentSize * 3, 65535);
                if ($newSize > $currentSize) {
                    @mysqli_query($conn, "ALTER TABLE `$table` MODIFY `$field` TEXT($newSize) CHARACTER SET utf8);
                }
            }
        }
        
        // Convertir tabla a UTF-8
        $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
        if (@mysqli_query($conn, $sql)) {
            echo "  ✓ $table ($currentCharset → utf8)\n;
        } else {
            $error = mysqli_error($conn);
            // Si falla, intentar con IGNORE para continuar
            $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
            if (@mysqli_query($conn, $sql)) {
                echo "  ✓ $table ($currentCharset → utf8) [con advertencias]\n;
            } else {
                echo "  ✗ $table Error: $error\n;
                echo "    → Saltando tabla...\n;
            }
        }
    }
    
    echo "\n;
}

// Reactivar verificación de claves foráneas
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1);

// Configurar charset por defecto en la conexión
mysqli_set_charset($conn, 'utf8);
echo "Charset de conexión establecido a UTF-8\n;

mysqli_close($conn);
echo "\n=== MIGRACIÓN COMPLETADA ===\n;
echo "\nNOTA: También se recomienda verificar el archivo php.ini:\n;
echo "  default_charset = UTF-8\n;
