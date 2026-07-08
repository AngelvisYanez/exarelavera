<?php
$dir = new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($dir);

$count = 0;
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    if (strpos($path, 'fix_short_tags.php') !== false) continue;

    $content = file_get_contents($path);
    $orig = $content;
    
    // Pattern: <? followed by whitespace, BUT NOT <?php or <?=
    // The negative lookahead (?![pP][hH][pP]|=) ensures we don't match <?php or <?=
    $content = preg_replace('/<\?(?![pP][hH][pP]|=)(\s+)/', '<?php$1', $content);
    
    // Also handle <? at the very end of the file
    $content = preg_replace('/<\?(?![pP][hH][pP]|=)$/', '<?php', $content);

    if ($orig !== $content) {
        file_put_contents($path, $content);
        $count++;
    }
}
echo "Modificados $count archivos arreglando etiquetas cortas.\n";
