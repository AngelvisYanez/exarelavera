<?php
function replaceEach($file) {
    if (!file_exists($file)) return;
    $content = file_get_contents($file);
    $content = preg_replace('/while\s*\(\s*list\s*\(\s*\$([a-zA-Z0-9_]+)\s*,\s*\$([a-zA-Z0-9_]+)\s*\)\s*=\s*each\s*\(\s*\$([a-zA-Z0-9_>\-]+)\s*\)\s*\)/', 'foreach($$$3 as $$$1 => $$$2)', $content);
    // for mpdf objects specifically (e.g. $this->images)
    $content = str_replace('while(list($file,$info)=each($this->images))', 'foreach($this->images as $file => $info)', $content);
    $content = str_replace('while(list($file,$info)=each($this->formobjects))', 'foreach($this->formobjects as $file => $info)', $content);
    file_put_contents($file, $content);
}

replaceEach(__DIR__ . '/Librerias/jscalendar/calendar.php');
replaceEach(__DIR__ . '/Librerias/slider/MPDF57/mpdf.php');
replaceEach(__DIR__ . '/vendor/setasign/fpdi/fpdi.php');

echo "Remaining each() fixed.\n";
