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
    if (strpos($content, 'isset($1)) !== false) {
        echo "Found in $filePath\n;
        // Let's replace the lines:
        // 
        // with empty string, since they are after 'return' and are invalid anyway.
        // Or actually, wait. If they are AFTER return, they will never execute anyway!
        // So we can just remove them entirely.
        $newContent = str_replace(', ', $content);
        // Clean up empty lines if any
        $newContent = preg_replace('/^\h*\v+/m, ', $newContent);
        if ($newContent !== $content) {
            file_put_contents($filePath, $newContent);
            echo "Fixed $filePath\n;
            $count++;
        }
    }
}
echo "Total fixed files: $count\n;
