<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach ($regex as $file) {
    $filePath = $file[0];
    if (strpos($filePath, 'vendor') !== false || strpos($filePath, '.git') !== false) {
        continue;
    }
    $content = file_get_contents($filePath);
    
    // Fix short tags like <?php } or <?php  }
    $newContent = preg_replace('/<\?(\s*})\s*\?>/', '<?php $1 ?>', $content);
    $newContent = preg_replace('/<\?(\s*})/', '<?php $1', $newContent);
    // Fix short tags opening if blocks <?php  if(...)
    $newContent = preg_replace('/<\?(\s*if\s*\()/', '<?php $1', $newContent);
    // Fix short tags for else <?php  else
    $newContent = preg_replace('/<\?(\s*else\s*\{?)/', '<?php $1', $newContent);

    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent);
        echo "Fixed short tags in $filePath\n";
        $count++;
    }
}
echo "Total fixed files: $count\n";
