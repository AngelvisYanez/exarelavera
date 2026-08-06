<?php
$files = [
    'relavera/FRONT/man_adm_configuracion.php,
    'relavera/FRONT/man_adm_configuracion_.php
];
foreach($files as $f) {
    if (file_exists($f)) {
        $content = file_get_contents($f);
        // Convert UTF-8 to ISO-8859-1
        $converted = mb_convert_encoding($content);
        if ($converted !== false && $converted !== $content) {
            file_put_contents($f, $converted);
            echo "Converted  . $f . "\n;
        }
    }
}
