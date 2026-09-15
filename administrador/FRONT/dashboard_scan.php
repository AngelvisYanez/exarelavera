<?php
header('Content-Type: application/json');

$allowFile = __DIR__ . DIRECTORY_SEPARATOR . 'dashboard_scan_allow.json';

function dashboard_load_scan_roots($allowFile) {
    $roots = array();
    if (!file_exists($allowFile)) {
        return $roots;
    }
    $raw = @file_get_contents($allowFile);
    if ($raw === false) {
        return $roots;
    }
    $j = json_decode($raw, true);
    if (!is_array($j) || !isset($j['roots']) || !is_array($j['roots'])) {
        return $roots;
    }
    foreach ($j['roots'] as $r) {
        $r = trim(str_replace('/', DIRECTORY_SEPARATOR, $r));
        if ($r !== '' && is_dir($r)) {
            $roots[] = $r;
        }
    }
    return $roots;
}

function dashboard_is_windows_os() {
    if (defined('PHP_WINDOWS_VERSION_BUILD')) {
        return true;
    }
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

function dashboard_realpath_norm($p) {
    $rp = realpath($p);
    if ($rp === false) {
        return false;
    }
    if (dashboard_is_windows_os()) {
        return strtolower(str_replace('/', '\\', $rp));
    }
    return $rp;
}

function dashboard_path_under_allowed($path, $roots) {
    if (empty($roots)) {
        return true;
    }
    $rp = dashboard_realpath_norm($path);
    if ($rp === false) {
        return false;
    }
    foreach ($roots as $root) {
        $rr = dashboard_realpath_norm($root);
        if ($rr === false) {
            continue;
        }
        if ($rp === $rr) {
            return true;
        }
        $sep = dashboard_is_windows_os() ? '\\' : DIRECTORY_SEPARATOR;
        if (strpos($rp, $rr . $sep) === 0) {
            return true;
        }
    }
    return false;
}

function dashboard_get_scan_bases($allowFile) {
    $bases = dashboard_load_scan_roots($allowFile);
    if (empty($bases)) {
        $guess = dirname(dirname(__DIR__));
        if (is_dir($guess)) {
            $bases[] = $guess;
        }
    }
    return $bases;
}

function dashboard_normalize_input_path($path) {
    $path = str_replace('/', DIRECTORY_SEPARATOR, trim($path));
    $ds = DIRECTORY_SEPARATOR;
    if (strpos($path, '.' . $ds) === 0) {
        $path = substr($path, 2);
    }
    return rtrim($path, $ds);
}

function dashboard_is_absolute_path($path) {
    if ($path === '') {
        return false;
    }
    $ds = DIRECTORY_SEPARATOR;
    if ($path[0] === $ds) {
        return true;
    }
    if (dashboard_is_windows_os()) {
        if (strlen($path) >= 2 && $path[1] === ':') {
            return true;
        }
        if (strlen($path) >= 2 && ($path[0] === '\\' && $path[1] === '\\')) {
            return true;
        }
    }
    return false;
}

function dashboard_resolve_scan_path($rawPath, $allowFile) {
    $path = dashboard_normalize_input_path($rawPath);
    if ($path === '') {
        return '';
    }
    if (dashboard_is_absolute_path($path)) {
        if (is_dir($path) || is_file($path)) {
            $rp = realpath($path);
            return ($rp !== false) ? $rp : $path;
        }
        return '';
    }
    $bases = dashboard_get_scan_bases($allowFile);
    foreach ($bases as $base) {
        $candidate = $base . DIRECTORY_SEPARATOR . $path;
        $rp = @realpath($candidate);
        if ($rp !== false && (is_dir($rp) || is_file($rp))) {
            return $rp;
        }
        if (is_dir($candidate) || is_file($candidate)) {
            $rp2 = @realpath($candidate);
            return ($rp2 !== false) ? $rp2 : $candidate;
        }
    }
    return '';
}

if (isset($_GET['action']) && $_GET['action'] === 'list_allowed') {
    $roots = dashboard_get_scan_bases($allowFile);
    $projects = array();
    $skipDirNames = array('node_modules', 'vendor', '.git', 'cache', 'tmp', 'assets', 'img', 'fonts', '.gemini', '.agents');
    $currentProjRoot = dirname(dirname(__DIR__));
    $seenPaths = array();

    // 1. Proyecto Completo Actual
    if (is_dir($currentProjRoot)) {
        $normRoot = str_replace('\\', '/', $currentProjRoot);
        $projects[] = array(
            'path' => $normRoot,
            'label' => '⭐ ' . basename($currentProjRoot) . ' (Proyecto Completo)',
            'group' => 'Proyecto Principal'
        );
        $seenPaths[strtolower($normRoot)] = true;

        // 2. Modulos Principales (Nivel 1)
        $items = @scandir($currentProjRoot);
        if ($items) {
            foreach ($items as $i) {
                if ($i === '.' || $i === '..' || in_array(strtolower($i), $skipDirNames)) continue;
                $full = $currentProjRoot . DIRECTORY_SEPARATOR . $i;
                if (is_dir($full)) {
                    $normFull = str_replace('\\', '/', $full);
                    if (!isset($seenPaths[strtolower($normFull)])) {
                        $projects[] = array(
                            'path' => $normFull,
                            'label' => '📁 ' . $i,
                            'group' => 'Modulos del Proyecto Actual'
                        );
                        $seenPaths[strtolower($normFull)] = true;
                    }

                    // 3. Submodulos Internos (Nivel 2)
                    $subItems = @scandir($full);
                    if ($subItems) {
                        foreach ($subItems as $sub) {
                            if ($sub === '.' || $sub === '..' || in_array(strtolower($sub), $skipDirNames)) continue;
                            $subFull = $full . DIRECTORY_SEPARATOR . $sub;
                            if (is_dir($subFull)) {
                                $normSubFull = str_replace('\\', '/', $subFull);
                                if (!isset($seenPaths[strtolower($normSubFull)])) {
                                    $projects[] = array(
                                        'path' => $normSubFull,
                                        'label' => '📂 ' . $i . '/' . $sub,
                                        'group' => 'Subcarpetas y Secciones'
                                    );
                                    $seenPaths[strtolower($normSubFull)] = true;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // 4. Otros Proyectos en el Workspace (Directorio Padre)
    $parentDir = dirname($currentProjRoot);
    if ($parentDir && is_dir($parentDir) && $parentDir !== $currentProjRoot) {
        $parentItems = @scandir($parentDir);
        if ($parentItems) {
            foreach ($parentItems as $pItem) {
                if ($pItem === '.' || $pItem === '..' || in_array(strtolower($pItem), $skipDirNames)) continue;
                $pFull = $parentDir . DIRECTORY_SEPARATOR . $pItem;
                if (is_dir($pFull)) {
                    $normPFull = str_replace('\\', '/', $pFull);
                    if (!isset($seenPaths[strtolower($normPFull)])) {
                        $projects[] = array(
                            'path' => $normPFull,
                            'label' => '🌐 ' . $pItem,
                            'group' => 'Otros Proyectos en Workspace'
                        );
                        $seenPaths[strtolower($normPFull)] = true;
                    }
                }
            }
        }
    }

    $restrict = file_exists($allowFile) && count(dashboard_load_scan_roots($allowFile)) > 0;
    echo json_encode(array(
        'success' => true,
        'roots' => $roots,
        'projects' => $projects,
        'restrictScanToAllowFile' => $restrict,
        'supportsRelativePaths' => true
    ));
    exit;
}

function dashboard_collect_files_in_dir($dir, $baseDir, $exts, $skipDirs, $max = 1500) {
    $results = array();
    $items = @scandir($dir);
    if (!$items) return $results;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            if (in_array(strtolower($item), $skipDirs)) continue;
            $sub = dashboard_collect_files_in_dir($full, $baseDir, $exts, $skipDirs, $max - count($results));
            $results = array_merge($results, $sub);
            if (count($results) >= $max) break;
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, $exts)) continue;
            $rel = str_replace($baseDir, '', $full);
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            $relFolder = dirname($rel);
            if ($relFolder === '.' || $relFolder === '') $relFolder = 'ROOT';

            $results[] = array(
                'path' => str_replace('\\', '/', $full),
                'name' => $item,
                'relative' => $rel,
                'folder' => $relFolder,
                'type' => ($ext === 'htm') ? 'html' : $ext,
                'size' => @filesize($full) ?: 0
            );
            if (count($results) >= $max) break;
        }
    }
    return $results;
}

if (isset($_GET['action']) && $_GET['action'] === 'list_folder_files') {
    $folder = isset($_GET['folder']) ? trim($_GET['folder']) : '';
    if ($folder === '') {
        echo json_encode(array('success' => false, 'error' => 'Carpeta no especificada'));
        exit;
    }
    $resolved = dashboard_resolve_scan_path($folder, $allowFile);
    if ($resolved === '' || !is_dir($resolved)) {
        echo json_encode(array('success' => false, 'error' => 'Carpeta no valida o no encontrada: ' . $folder));
        exit;
    }
    $exts = array('php', 'js', 'html', 'htm', 'css', 'sql');
    $skip = array('node_modules', 'vendor', '.git', 'cache', 'tmp', 'assets', 'img', 'fonts', '.gemini', '.agents');
    $files = dashboard_collect_files_in_dir($resolved, $resolved, $exts, $skip, 1500);
    echo json_encode(array(
        'success' => true,
        'folder' => $folder,
        'resolved' => str_replace('\\', '/', $resolved),
        'label' => basename($resolved),
        'files' => $files,
        'total' => count($files)
    ));
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'search_files') {
    $q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
    if ($q === '') {
        echo json_encode(array('success' => true, 'files' => array(), 'total' => 0));
        exit;
    }
    $currentProjRoot = dirname(dirname(__DIR__));
    $baseFolder = isset($_GET['folder']) && trim($_GET['folder']) !== '' ? trim($_GET['folder']) : $currentProjRoot;
    $resolved = dashboard_resolve_scan_path($baseFolder, $allowFile);
    if ($resolved === '' || !is_dir($resolved)) {
        $resolved = $currentProjRoot;
    }
    $exts = array('php', 'js', 'html', 'htm', 'css', 'sql');
    $skip = array('node_modules', 'vendor', '.git', 'cache', 'tmp', 'assets', 'img', 'fonts', '.gemini', '.agents');
    $all = dashboard_collect_files_in_dir($resolved, $resolved, $exts, $skip, 2500);
    $matches = array();
    foreach ($all as $f) {
        if (strpos(strtolower($f['name']), $q) !== false || strpos(strtolower($f['relative']), $q) !== false) {
            $matches[] = $f;
            if (count($matches) >= 150) break;
        }
    }
    echo json_encode(array(
        'success' => true,
        'query' => $q,
        'folder' => str_replace('\\', '/', $resolved),
        'files' => $matches,
        'total' => count($matches)
    ));
    exit;
}

function dashboard_get_git_root() {
    $cur = __DIR__;
    for ($i = 0; $i < 6; $i++) {
        if (is_dir($cur . DIRECTORY_SEPARATOR . '.git')) {
            return $cur;
        }
        $parent = dirname($cur);
        if ($parent === $cur) break;
        $cur = $parent;
    }
    return false;
}

if (isset($_GET['action']) && $_GET['action'] === 'git_commits') {
    $gitRoot = dashboard_get_git_root();
    if (!$gitRoot) {
        echo json_encode(array('success' => false, 'error' => 'No se encontro repositorio Git'));
        exit;
    }

    $branch = 'origin/desarrollo';
    exec('git -C "' . $gitRoot . '" rev-parse --verify origin/desarrollo 2>nul', $tOut, $rOut);
    if ($rOut !== 0) {
        $branch = 'desarrollo';
        exec('git -C "' . $gitRoot . '" rev-parse --verify desarrollo 2>nul', $tOut2, $rOut2);
        if ($rOut2 !== 0) {
            $branch = 'HEAD';
        }
    }

    $cmd = 'git -C "' . $gitRoot . '" log -n 40 --pretty=format:"%h|%s|%an|%ad" --date=short ' . escapeshellarg($branch) . ' 2>nul';
    $rawLines = array();
    exec($cmd, $rawLines);
    $commits = array();
    foreach ($rawLines as $l) {
        $parts = explode('|', trim($l));
        if (count($parts) >= 4) {
            $commits[] = array(
                'hash' => $parts[0],
                'subject' => $parts[1],
                'author' => $parts[2],
                'date' => $parts[3]
            );
        }
    }

    echo json_encode(array(
        'success' => true,
        'gitRoot' => str_replace('\\', '/', $gitRoot),
        'branch' => $branch,
        'commits' => $commits
    ));
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'git_compare') {
    $gitRoot = dashboard_get_git_root();
    if (!$gitRoot) {
        echo json_encode(array('success' => false, 'error' => 'No se encontro repositorio Git'));
        exit;
    }

    $rawInput = json_decode(file_get_contents('php://input'), true);
    $ref = isset($rawInput['commit']) ? trim($rawInput['commit']) : (isset($_GET['commit']) ? trim($_GET['commit']) : '');
    if ($ref === '') {
        $ref = 'origin/desarrollo';
    }

    // Sanitize commit / ref
    $refClean = escapeshellarg($ref);

    $infoLines = array();
    exec('git -C "' . $gitRoot . '" log -n 1 --pretty=format:"%h|%s|%an|%ad" --date=short ' . $refClean . ' 2>nul', $infoLines);
    $commitInfo = array('hash' => $ref, 'subject' => '', 'author' => '', 'date' => '');
    if (!empty($infoLines)) {
        $cp = explode('|', trim($infoLines[0]));
        if (count($cp) >= 4) {
            $commitInfo = array(
                'hash' => $cp[0],
                'subject' => $cp[1],
                'author' => $cp[2],
                'date' => $cp[3]
            );
        }
    }

    $diffLines = array();
    exec('git -C "' . $gitRoot . '" diff --numstat ' . $refClean . ' 2>nul', $diffLines);

    $statusLines = array();
    exec('git -C "' . $gitRoot . '" diff --name-status ' . $refClean . ' 2>nul', $statusLines);

    $diffMap = array();
    foreach ($diffLines as $line) {
        $p = preg_split('/\s+/', trim($line), 3);
        if (count($p) === 3) {
            $added = is_numeric($p[0]) ? (int)$p[0] : 0;
            $deleted = is_numeric($p[1]) ? (int)$p[1] : 0;
            $normP = str_replace('\\', '/', $p[2]);
            $diffMap[$normP] = array(
                'added' => $added,
                'deleted' => $deleted,
                'delta' => $added - $deleted
            );
        }
    }

    $statusMap = array();
    foreach ($statusLines as $line) {
        $p = preg_split('/\s+/', trim($line), 2);
        if (count($p) === 2) {
            $normP = str_replace('\\', '/', $p[1]);
            $statusMap[$normP] = $p[0];
        }
    }

    echo json_encode(array(
        'success' => true,
        'gitRoot' => str_replace('\\', '/', $gitRoot),
        'ref' => $ref,
        'commitInfo' => $commitInfo,
        'diffMap' => $diffMap,
        'statusMap' => $statusMap
    ));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = array();
}

$targets = array();
if (isset($input['targets']) && is_array($input['targets'])) {
    foreach ($input['targets'] as $t) {
        if (!is_array($t)) {
            continue;
        }
        $p = isset($t['path']) ? trim($t['path']) : '';
        if ($p === '') {
            continue;
        }
        $targets[] = array(
            'path' => $p,
            'mode' => dashboard_normalize_scan_mode(isset($t['mode']) ? $t['mode'] : 'normal')
        );
    }
} else {
    $path = isset($input['path']) ? trim($input['path']) : '';
    $mode = dashboard_normalize_scan_mode(isset($input['mode']) ? $input['mode'] : 'normal');
    if ($path !== '') {
        $targets[] = array('path' => $path, 'mode' => $mode);
    }
}

if (empty($targets)) {
    die(json_encode(array('error' => 'Ruta no especificada')));
}

set_time_limit(0);
ini_set('memory_limit', '1024M');

$exts = array('php', 'js', 'html', 'htm', 'css', 'sql');
$skip = array('node_modules', 'vendor', '.git', 'cache', 'tmp', 'assets', 'img', 'fonts');
$skipFiles = array('jquery', 'bootstrap', '.min.', 'bundle');
$rates = array('alta' => 25, 'media-alta' => 35, 'media' => 45, 'baja' => 60);

function dashboard_normalize_scan_mode($m) {
    if ($m === 'no_empty' || $m === 'no_comments') {
        return $m;
    }
    return 'normal';
}

/**
 * Cuenta lineas segun modo:
 * - normal: todas
 * - no_empty: no vacias
 * - no_comments: ignora vacias y comentarios (slash, hash, bloque, HTML)
 */
function cntLines($f, $m, $ext = '') {
    if ($m === 'no_comments') {
        return cntLinesNoComments($f, $ext);
    }
    $c = 0;
    $h = @fopen($f, 'r');
    if ($h) {
        while (($l = fgets($h)) !== false) {
            if ($m === 'no_empty') {
                if (trim($l) !== '') {
                    $c++;
                }
            } else {
                $c++;
            }
        }
        fclose($h);
    }
    return $c;
}

function cntLinesNoComments($f, $ext) {
    $ext = strtolower($ext);
    $h = @fopen($f, 'r');
    if (!$h) {
        return 0;
    }
    $count = 0;
    $inBlock = false;
    $htmlMode = ($ext === 'html' || $ext === 'htm');
    $hashComments = ($ext === 'php' || $ext === 'sql');
    $slashLine = ($ext === 'php' || $ext === 'js' || $ext === 'css' || $ext === 'sql');
    $slashBlock = $slashLine;
    $dashDash = ($ext === 'sql');

    while (($line = fgets($h)) !== false) {
        $code = '';
        $len = strlen($line);
        $i = 0;
        $inSingle = false;
        $inDouble = false;

        while ($i < $len) {
            $ch = $line[$i];
            $next = ($i + 1 < $len) ? $line[$i + 1] : '';

            if ($inBlock) {
                if ($htmlMode) {
                    if ($i + 2 < $len && $ch === '-' && $next === '-' && $line[$i + 2] === '>') {
                        $inBlock = false;
                        $i += 3;
                        continue;
                    }
                } elseif ($ch === '*' && $next === '/') {
                    $inBlock = false;
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }

            if ($inSingle) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $code .= $ch . $line[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($ch === "'") {
                    $inSingle = false;
                }
                $code .= $ch;
                $i++;
                continue;
            }

            if ($inDouble) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $code .= $ch . $line[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($ch === '"') {
                    $inDouble = false;
                }
                $code .= $ch;
                $i++;
                continue;
            }

            if ($htmlMode && $i + 3 < $len && substr($line, $i, 4) === '<!--') {
                $inBlock = true;
                $i += 4;
                continue;
            }
            if ($slashBlock && $ch === '/' && $next === '*') {
                $inBlock = true;
                $i += 2;
                continue;
            }
            if ($slashLine && $ch === '/' && $next === '/') {
                break;
            }
            if ($hashComments && $ch === '#') {
                break;
            }
            if ($dashDash && $ch === '-' && $next === '-') {
                break;
            }

            if (!$htmlMode && $ch === "'") {
                $inSingle = true;
                $code .= $ch;
                $i++;
                continue;
            }
            if (!$htmlMode && $ch === '"') {
                $inDouble = true;
                $code .= $ch;
                $i++;
                continue;
            }

            $code .= $ch;
            $i++;
        }

        if (trim($code) !== '') {
            $count++;
        }
    }

    fclose($h);
    return $count;
}

function getComp($n, $t) {
    if ($t === 'html') {
        return 'baja';
    }
    if ($n > 1000) {
        return 'alta';
    }
    if ($n > 500) {
        return 'media-alta';
    }
    if ($n > 200) {
        return 'media';
    }
    return 'baja';
}

function inList($n, $l) {
    $n = strtolower($n);
    foreach ($l as $i) {
        if (strpos($n, $i) !== false) {
            return true;
        }
    }
    return false;
}

function escanearDirectorio($d, $b, $e, $s, $sf, $r, $m) {
    $res = array();
    $items = @scandir($d);
    if (!$items) {
        return $res;
    }

    foreach ($items as $i) {
        if ($i === '.' || $i === '..') {
            continue;
        }
        $fp = $d . DIRECTORY_SEPARATOR . $i;
        $rp = str_replace($b . DIRECTORY_SEPARATOR, '', $d);
        if ($rp === $d) {
            $rp = '';
        }

        if (is_dir($fp)) {
            if (inList($i, $s)) {
                continue;
            }
            $sub = escanearDirectorio($fp, $b, $e, $s, $sf, $r, $m);
            $res = array_merge($res, $sub);
        } else {
            $ext = strtolower(pathinfo($i, PATHINFO_EXTENSION));
            if (!in_array($ext, $e)) {
                continue;
            }
            if (inList($i, $sf)) {
                continue;
            }

            $lin = cntLines($fp, $m, $ext);
            $typ = ($ext === 'htm') ? 'html' : $ext;
            $cmp = getComp($lin, $typ);
            $hrs = round($lin / $r[$cmp], 2);

            $res[] = array(
                'name' => $i,
                'folder' => ($rp !== '') ? str_replace('\\', '/', $rp) : 'ROOT',
                'type' => $typ,
                'lines' => $lin,
                'complexity' => $cmp,
                'suggestedComplexity' => $cmp,
                'hours' => $hrs
            );
        }
    }
    return $res;
}

$rootsRestrict = dashboard_load_scan_roots($allowFile);
$allFiles = array();
$resolvedTargets = array();
$multi = count($targets) > 1;

foreach ($targets as $t) {
    $resolved = dashboard_resolve_scan_path($t['path'], $allowFile);
    if ($resolved === '') {
        $bases = dashboard_get_scan_bases($allowFile);
        $hint = '';
        if (!empty($bases)) {
            $hint = ' Pruebe ruta absoluta en este servidor o relativa a: ' . implode('; ', $bases) . '.';
        }
        die(json_encode(array(
            'error' => 'Ruta no valida: ' . $t['path'] . '.' . $hint
        )));
    }

    if (!empty($rootsRestrict) && !dashboard_path_under_allowed($resolved, $rootsRestrict)) {
        die(json_encode(array(
            'error' => 'Ruta fuera de las carpetas permitidas: ' . $resolved . '. Revise dashboard_scan_allow.json.'
        )));
    }

    $mode = $t['mode'];
    if (is_file($resolved)) {
        $fileName = basename($resolved);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $lin = cntLines($resolved, $mode, $ext);
        $typ = ($ext === 'htm') ? 'html' : $ext;
        $cmp = getComp($lin, $typ);
        $hrs = round($lin / $rates[$cmp], 2);
        $dir = dirname($resolved);
        $label = basename($dir);
        $relFolder = basename($dir);
        $files = array(array(
            'name' => $fileName,
            'folder' => $relFolder ? $relFolder : 'ROOT',
            'type' => $typ,
            'lines' => $lin,
            'complexity' => $cmp,
            'suggestedComplexity' => $cmp,
            'hours' => $hrs
        ));
    } elseif (isset($t['files']) && is_array($t['files']) && !empty($t['files'])) {
        $label = basename($resolved);
        $files = array();
        foreach ($t['files'] as $fItem) {
            $fRel = is_string($fItem) ? $fItem : (isset($fItem['path']) ? $fItem['path'] : '');
            $fFull = dashboard_resolve_scan_path($fRel, $allowFile);
            if ($fFull === '' || !is_file($fFull)) {
                $candidate = $resolved . DIRECTORY_SEPARATOR . dashboard_normalize_input_path($fRel);
                if (is_file($candidate)) {
                    $fFull = realpath($candidate) ?: $candidate;
                }
            }
            if (is_file($fFull)) {
                $fileName = basename($fFull);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $lin = cntLines($fFull, $mode, $ext);
                $typ = ($ext === 'htm') ? 'html' : $ext;
                $cmp = getComp($lin, $typ);
                $hrs = round($lin / $rates[$cmp], 2);
                $rel = str_replace($resolved, '', $fFull);
                $rel = ltrim(str_replace('\\', '/', $rel), '/');
                $relFolder = dirname($rel);
                if ($relFolder === '.' || $relFolder === '') $relFolder = 'ROOT';
                $files[] = array(
                    'name' => $fileName,
                    'folder' => $relFolder,
                    'type' => $typ,
                    'lines' => $lin,
                    'complexity' => $cmp,
                    'suggestedComplexity' => $cmp,
                    'hours' => $hrs
                );
            }
        }
    } else {
        $label = basename($resolved);
        $files = escanearDirectorio($resolved, $resolved, $exts, $skip, $skipFiles, $rates, $mode);
    }

    $gitRoot = dashboard_get_git_root();
    $normGitRoot = $gitRoot ? str_replace('\\', '/', $gitRoot) : '';

    foreach ($files as &$f) {
        $f['project'] = $label;
        $f['projectPath'] = str_replace('\\', '/', $resolved);
        $f['scanMode'] = $mode;

        // Path relativo al repositorio Git para comparaciones instantaneas
        if ($normGitRoot !== '') {
            $origFolder = ($f['folder'] === 'ROOT' || $f['folder'] === '') ? '' : $f['folder'];
            $fullFilePath = $resolved . DIRECTORY_SEPARATOR . ($origFolder === '' ? '' : ($origFolder . DIRECTORY_SEPARATOR)) . $f['name'];
            $normFull = str_replace('\\', '/', $fullFilePath);
            if (strpos($normFull, $normGitRoot . '/') === 0) {
                $f['repoPath'] = substr($normFull, strlen($normGitRoot) + 1);
            }
        }

        if ($multi) {
            $rel = ($f['folder'] === 'ROOT' || $f['folder'] === '') ? '' : $f['folder'];
            $f['folder'] = $rel === '' ? $label : ($label . '/' . str_replace('\\', '/', $rel));
        }
    }
    unset($f);

    $allFiles = array_merge($allFiles, $files);
    $resolvedTargets[] = array(
        'path' => $resolved,
        'label' => $label,
        'mode' => $mode,
        'files' => count($files)
    );
}

usort($allFiles, function ($a, $b) {
    return $b['lines'] - $a['lines'];
});

$primaryPath = $resolvedTargets[0]['path'];
echo json_encode(array(
    'success' => true,
    'files' => $allFiles,
    'total' => count($allFiles),
    'resolvedPath' => $primaryPath,
    'resolvedPaths' => array_map(function ($rt) {
        return $rt['path'];
    }, $resolvedTargets),
    'targets' => $resolvedTargets,
    'multi' => $multi,
    'gitAvailable' => ($gitRoot !== false)
));
