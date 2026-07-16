<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/\.php$/);
$count = 0;
foreach($files as $f) {
    $path = $f->getPathname();
    if (strpos($path, 'vendor) !== false || strpos($path, 'tcpdf) !== false) {
        continue;
    }
    $content = file_get_contents($path);
    if ($content === false) continue;
    // We want to replace:
    // 
    // 
    // with:
    // if (isset($var) && $var instanceof mysqli_result) {  }
    $new_content = preg_replace(
        '/@?mysqli_free_result\s*\(\s*(\$[^)]+)\s*\)\s*;/i,
        'if (isset(\$1) && \$1 instanceof mysqli_result) { @mysqli_free_result(\$1); },
        $content
    );
    // Also fix @mysqli_close($var)
    $new_content = preg_replace(
        '/@?mysqli_close\s*\(\s*(\$[^)]+)\s*\)\s*;/i,
        'if (isset(\$1) && \$1 instanceof mysqli) { @mysqli_close(\$1); },
        $new_content
    );
    if ($content !== $new_content) {
        file_put_contents($path, $new_content);
        echo "Fixed $path\n;
        $count++;
    }
}
echo "Fixed $count files.\n;
