<?php
$root = __DIR__ . DIRECTORY_SEPARATOR . '..';
$filesWithBom = 0;
$totalPhp = 0;

$directory = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directory);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    if (strpos($path, 'vendor') !== false || strpos($path, 'node_modules') !== false) continue;
    $totalPhp++;
    $handle = fopen($path, 'rb');
    $bytes = fread($handle, 3);
    fclose($handle);
    if (strlen($bytes) >= 3 && ord($bytes[0]) === 0xEF && ord($bytes[1]) === 0xBB && ord($bytes[2]) === 0xBF) {
        echo 'BOM: ' . $path . PHP_EOL;
        $filesWithBom++;
    }
}
echo PHP_EOL . 'Total PHP: ' . $totalPhp . PHP_EOL . 'BOM found: ' . $filesWithBom . PHP_EOL;
exit($filesWithBom > 0 ? 1 : 0);