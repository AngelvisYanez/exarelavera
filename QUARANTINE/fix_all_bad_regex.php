<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);
$regex = new RegexIterator($iterator, '/^.+\.php$/i, RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach ($regex as $file) {
    $filePath = $file[0];
    if (strpos($filePath, 'vendor) !== false || strpos($filePath, '.git) !== false) {
        continue;
    }
    $content = file_get_contents($filePath);
    
    // Look for any remaining isset($1)
    if (strpos($content, 'isset($1)) !== false) {
        echo "Found isset($1) in $filePath\n;
        
        // Remove: @mysqli_close($this->conexion)
        $content = str_replace('@mysqli_close($this->conexion), '@mysqli_close($this->conexion), $content);
        
        file_put_contents($filePath, $content);
        $count++;
    }
}
echo "Total fixed files: $count\n;
