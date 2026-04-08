<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$path = isset($input['path']) ? trim($input['path']) : '';
$mode = isset($input['mode']) ? $input['mode'] : 'normal';

if (empty($path)) {
    die(json_encode(array('error' => 'Ruta no especificada')));
}

// Normalizar separadores según el SO del servidor
if (DIRECTORY_SEPARATOR === '/') {
    // Linux/Mac: convertir backslashes a slashes
    $path = str_replace('\\', '/', $path);
} else {
    // Windows: convertir slashes a backslashes
    $path = str_replace('/', '\\', $path);
}
$path = rtrim($path, DIRECTORY_SEPARATOR);

if (!is_dir($path)) {
    die(json_encode(array('error' => 'Ruta no valida: ' . $path)));
}

// Configurar limites para proyectos grandes
set_time_limit(0);
ini_set('memory_limit', '1024M');

$exts = array('php', 'js', 'html', 'htm', 'css', 'sql');
$skip = array('node_modules', 'vendor', '.git', 'cache', 'tmp', 'assets', 'img', 'fonts');
$skipFiles = array('jquery', 'bootstrap', '.min.', 'bundle');
$rates = array('alta' => 25, 'media-alta' => 35, 'media' => 45, 'baja' => 60);

function cntLines($f, $m) {
    $c = 0;
    $h = @fopen($f, 'r');
    if ($h) {
        while (($l = fgets($h)) !== false) {
            if ($m === 'no_empty') {
                if (trim($l) !== '') $c++;
            } else {
                $c++;
            }
        }
        fclose($h);
    }
    return $c;
}

function getComp($n, $t) {
    if ($t === 'html') return 'baja';
    if ($n > 1000) return 'alta';
    if ($n > 500) return 'media-alta';
    if ($n > 200) return 'media';
    return 'baja';
}

function inList($n, $l) {
    $n = strtolower($n);
    foreach ($l as $i) {
        if (strpos($n, $i) !== false) return true;
    }
    return false;
}

function escanearDirectorio($d, $b, $e, $s, $sf, $r, $m) {
    $res = array();
    $items = @scandir($d);
    if (!$items) return $res;
    
    foreach ($items as $i) {
        if ($i === '.' || $i === '..') continue;
        $fp = $d . DIRECTORY_SEPARATOR . $i;
        $rp = str_replace($b . DIRECTORY_SEPARATOR, '', $d);
        if ($rp === $d) $rp = '';
        
        if (is_dir($fp)) {
            if (inList($i, $s)) continue;
            $sub = escanearDirectorio($fp, $b, $e, $s, $sf, $r, $m);
            $res = array_merge($res, $sub);
        } else {
            $ext = strtolower(pathinfo($i, PATHINFO_EXTENSION));
            if (!in_array($ext, $e)) continue;
            if (inList($i, $sf)) continue;
            
            $lin = cntLines($fp, $m);
            $typ = ($ext === 'htm') ? 'html' : $ext;
            $cmp = getComp($lin, $typ);
            $hrs = round($lin / $r[$cmp], 2);
            
            $res[] = array(
                'name' => $i,
                'folder' => $rp ? $rp : 'ROOT',
                'type' => $typ,
                'lines' => $lin,
                'complexity' => $cmp,
                'hours' => $hrs
            );
        }
    }
    return $res;
}

$files = escanearDirectorio($path, $path, $exts, $skip, $skipFiles, $rates, $mode);

usort($files, function($a, $b) {
    return $b['lines'] - $a['lines'];
});

echo json_encode(array('success' => true, 'files' => $files, 'total' => count($files)));
