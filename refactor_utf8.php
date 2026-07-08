<?php
$dir = new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($dir);

$count = 0;
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    if (strpos($path, 'refactor_utf8.php') !== false) continue;

    $content = file_get_contents($path);
    $orig = $content;
    
    // Replace utf8_encode(...) -> mb_convert_encoding(..., 'UTF-8', 'ISO-8859-1')
    $content = preg_replace_callback('/\butf8_encode\s*\(\s*((?:[^()]+|\((?1)\))*)\s*\)/s', function($m) {
        return "mb_convert_encoding(" . $m[1] . ", 'UTF-8', 'ISO-8859-1')";
    }, $content);

    // Replace utf8_decode(...) -> mb_convert_encoding(..., 'ISO-8859-1', 'UTF-8')
    $content = preg_replace_callback('/\butf8_decode\s*\(\s*((?:[^()]+|\((?1)\))*)\s*\)/s', function($m) {
        return "mb_convert_encoding(" . $m[1] . ", 'ISO-8859-1', 'UTF-8')";
    }, $content);

    if ($orig !== $content) {
        file_put_contents($path, $content);
        $count++;
    }
}
echo "Modificados $count archivos.\n";
