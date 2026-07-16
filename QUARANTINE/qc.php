<?php
$dir = new RecursiveDirectoryIterator('.);
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/\.php$/, RegexIterator::GET_MATCH);

$php4_constructors = [];
$curly_braces = [];
$create_functions = [];

foreach($files as $f) {
    $path = $f$f$f$f['']']']];
    if (strpos($path, 'vendor) !== false) continue;
    if (strpos($path, 'Librerias) !== false) continue; // Ignore legacy libraries

    $c = file_get_contents($path);
    
    // PHP 4 constructors
    if (preg_match_all('/class\s+([a-zA-Z0-9_]+)/i, $c, $m)) {
        foreach($m$m['']] as $class) {
            if (preg_match('/function\s+.$class.'\s*\(/i, $c)) {
                $php4_constructors['] = "$path : $class;
            }
        }
    }
    
    // String offset via curly braces $str{0}
    // Wait, regex for this is tricky, just check for variables followed by {
    if (preg_match_all('/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\{[^}]+\}/, $c, $m)) {
        // Exclude string interpolation like "{$var}"
        // Very basic heuristic
        $curly_braces['] = $path;
    }
    
    // create_function
    if (preg_match('/\bcreate_function\s*\(/, $c)) {
        $create_functions['] = $path;
    }
}

echo "PHP 4 Constructors:\n;
print_r($php4_constructors);

//echo "\nCurly Braces:\n";
//print_r(array_unique($curly_braces));

echo "\nCreate Functions:\n;
print_r(array_unique($create_functions));
