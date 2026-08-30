<?php
$perfilesDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'conexion-produccion' . DIRECTORY_SEPARATOR . 'perfiles';
$dbConfigFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'conexion-produccion' . DIRECTORY_SEPARATOR . 'database.php';
function sanitizeNombre($nombre) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $nombre));
}
function listarPerfilesJson($dir) {
    $perfiles = [];
    if (!is_dir($dir)) return $perfiles;
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
    sort($files);
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && isset($data['nombre'])) {
            $perfiles[] = [
                'nombre' => $data['nombre'],
                'host' => $data['host'] ?? '',
                'port' => $data['port'] ?? 3306,
                'database' => $data['database'] ?? '',
            ];
        }
    }
    return $perfiles;
}
function leerPerfil($dir, $nombre) {
    $file = $dir . DIRECTORY_SEPARATOR . sanitizeNombre($nombre) . '.json';
    if (!file_exists($file)) return null;
    return json_decode(file_get_contents($file), true);
}
function escribirDatabasePhp($archivo, $perfil) {
    $php = "<?php\n/**\n * Configuracion de base de datos generada automaticamente\n */\nreturn array(\n";
    $php .= "    'host' => '" . addslashes($perfil['host']) . "',\n";
    $php .= "    'port' => " . intval($perfil['port']) . ",\n";
    $php .= "    'user' => '" . addslashes($perfil['user']) . "',\n";
    $php .= "    'pass' => '" . addslashes($perfil['pass']) . "',\n";
    $php .= ");\n";
    file_put_contents($archivo, $php);
}
function escribirActivo($dir, $nombre) {
    file_put_contents($dir . DIRECTORY_SEPARATOR . '_activo.txt', $nombre);
}
function leerActivo($dir) {
    $file = $dir . DIRECTORY_SEPARATOR . '_activo.txt';
    if (!file_exists($file)) return null;
    return trim(file_get_contents($file));
}
function getPerfilesDir() {
    global $perfilesDir;
    if (!is_dir($perfilesDir)) {
        mkdir($perfilesDir, 0755, true);
    }
    return $perfilesDir;
}
$app->get('/v1/admin/conexion/estado', function () use ($app, $dbConfigFile, $perfilesDir) {
    try {
        $activo = null;
        $conectado = false;
        $serverInfo = null;
        $error = null;
        $cfg = [];
        if (file_exists($dbConfigFile)) {
            $cfg = require $dbConfigFile;
        } else {
            $cfg = ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''];
        }
        $nombreActivo = leerActivo(dirname($dbConfigFile));
        $activo = [
            'nombre' => $nombreActivo ?: 'Personalizado',
            'host' => $cfg['host'],
            'port' => intval($cfg['port'] ?? 3306),
        ];
        [$conn, $err] = conectarConTimeout($cfg['host'], $cfg['user'], $cfg['pass'], intval($cfg['port'] ?? 3306));
        if ($conn) {
            $conectado = true;
            $serverInfo = mysqli_get_server_info($conn);
            mysqli_close($conn);
        } else {
            $error = $err;
        }
        echo json_encode([
            'success' => true,
            'activo' => $activo,
            'conectado' => $conectado,
            'server_info' => $serverInfo,
            'error' => $error,
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
$app->get('/v1/admin/conexion/perfiles', function () use ($app, $perfilesDir) {
    try {
        $perfiles = listarPerfilesJson(getPerfilesDir());
        echo json_encode(['success' => true, 'perfiles' => $perfiles]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
$app->post('/v1/admin/conexion/guardar', function () use ($app, $perfilesDir) {
    try {
        $body = getBody();
        $nombre = isset($body['nombre']) ? trim($body['nombre']) : '';
        if (empty($nombre)) {
            $app->response->setStatus(400);
            echo json_encode(['success' => false, 'error' => 'Nombre del perfil requerido']);
            return;
        }
        $perfil = [
            'nombre' => $nombre,
            'host' => $body['host'] ?? 'localhost',
            'port' => intval($body['port'] ?? 3306),
            'user' => $body['user'] ?? 'root',
            'pass' => $body['pass'] ?? '',
            'database' => $body['database'] ?? '',
        ];
        $file = getPerfilesDir() . DIRECTORY_SEPARATOR . sanitizeNombre($nombre) . '.json';
        file_put_contents($file, json_encode($perfil, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => "Perfil '$nombre' guardado correctamente"]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
$app->post('/v1/admin/conexion/activar', function () use ($app, $perfilesDir, $dbConfigFile) {
    try {
        $body = getBody();
        $nombre = isset($body['nombre']) ? trim($body['nombre']) : '';
        if (empty($nombre)) {
            $app->response->setStatus(400);
            echo json_encode(['success' => false, 'error' => 'Nombre del perfil requerido']);
            return;
        }
        $perfil = leerPerfil(getPerfilesDir(), $nombre);
        if (!$perfil) {
            $app->response->setStatus(404);
            echo json_encode(['success' => false, 'error' => "Perfil '$nombre' no encontrado"]);
            return;
        }
        escribirDatabasePhp($dbConfigFile, $perfil);
        escribirActivo(dirname($dbConfigFile), $perfil['nombre']);
        // Verificar que la nueva conexión funciona
        [$conn, $connErr] = conectarConTimeout($perfil['host'], $perfil['user'], $perfil['pass'], intval($perfil['port'] ?? 3306));
        $nuevaConexionOk = $conn !== null;
        if ($conn) {
            mysqli_close($conn);
        }
        echo json_encode([
            'success' => true,
            'message' => "Perfil '$nombre' activado correctamente",
            'conexion_ok' => $nuevaConexionOk,
            'conexion_error' => $connErr,
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
$app->post('/v1/admin/conexion/eliminar', function () use ($app, $perfilesDir) {
    try {
        $body = getBody();
        $nombre = isset($body['nombre']) ? trim($body['nombre']) : '';
        if (empty($nombre)) {
            $app->response->setStatus(400);
            echo json_encode(['success' => false, 'error' => 'Nombre del perfil requerido']);
            return;
        }
        $file = getPerfilesDir() . DIRECTORY_SEPARATOR . sanitizeNombre($nombre) . '.json';
        if ($nombre === 'Local') {
            echo json_encode(['success' => false, 'error' => 'El perfil Local no se puede eliminar']);
            return;
        }
        if (!file_exists($file)) {
            $app->response->setStatus(404);
            echo json_encode(['success' => false, 'error' => "Perfil '$nombre' no encontrado"]);
            return;
        }
        unlink($file);
        echo json_encode(['success' => true, 'message' => "Perfil '$nombre' eliminado"]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
function conectarConTimeout($host, $user, $pass, $port, $database = null, $timeout = 5) {
    $conn = mysqli_init();
    if (!$conn) return [null, 'No se pudo inicializar mysqli'];
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
    mysqli_options($conn, MYSQLI_OPT_READ_TIMEOUT, $timeout);
    $connected = @mysqli_real_connect($conn, $host, $user, $pass, $database, $port);
    if (!$connected) {
        $err = mysqli_connect_error() ?: 'Conexión fallida (timeout o servidor no accesible)';
        mysqli_close($conn);
        return [null, $err];
    }
    return [$conn, null];
}
$app->post('/v1/admin/conexion/test', function () use ($app) {
    try {
        $body = getBody();
        $host = $body['host'] ?? 'localhost';
        $port = intval($body['port'] ?? 3306);
        $user = $body['user'] ?? 'root';
        $pass = $body['pass'] ?? '';
        $database = $body['database'] ?? '';
        [$conn, $err] = conectarConTimeout($host, $user, $pass, $port, $database ?: null);
        if (!$conn) {
            echo json_encode([
                'success' => false,
                'error' => $err,
            ]);
            return;
        }
        $serverInfo = mysqli_get_server_info($conn);
        mysqli_close($conn);
        echo json_encode([
            'success' => true,
            'message' => 'Conexión exitosa',
            'server_info' => $serverInfo,
            'version' => $serverInfo,
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});
