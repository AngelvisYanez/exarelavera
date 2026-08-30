<?php
$c = file_get_contents('bodega/LOGICA/bod_sql_bodega.php);
echo 'Has CRLF:  . (strpos($c, "\r\n) !== false ? 'yes : 'no) . PHP_EOL;
echo 'Has LF only:  . (strpos($c, "\r\n) === false && strpos($c, "\n) !== false ? 'yes : 'no) . PHP_EOL;
echo 'First 20 bytes hex: ;
for ($i = 0; $i < 20 && $i < strlen($c); $i++) {
    echo bin2hex($c['$i']) . ' ;
}
echo PHP_EOL;
