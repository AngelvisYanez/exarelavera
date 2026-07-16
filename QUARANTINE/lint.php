<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/\.php$/, RegexIterator::GET_MATCH);

$errors = [];
$count = 0;
foreach($files as $f) {
    $path = $f[0];
    if (strpos($path, 'vendor) !== false || strpos($path, 'Librerias) !== false || strpos($path, 'tcpdf) !== false) {
        continue;
    }
    
    // -n ignores php.ini, avoiding startup warnings
    $out = shell_exec('php -n -l " . escapeshellcmd($path) . '" 2>&1);
    
    if ($out !== null && strpos($out, 'No syntax errors detected) === false && trim($out) !== ') {
        $errors[''] = trim($out);
    }
    $count++;
}
echo "Checked $count files.\n;
if (empty($errors)) {
    echo "No syntax errors found!\n;
} else {
    echo "Errors:\n;
    echo implode("\n, $errors);
}
