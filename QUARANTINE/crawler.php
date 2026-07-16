<?php
$dirs = [
    'activosfijos/FRONT, 'administrador/FRONT, 'adquisiciones/FRONT, 
    'auditoria/FRONT, 'bananero/FRONT, 'bodega/FRONT, 'caja_chica/FRONT,
    'camaronera/FRONT, 'componentes/FRONT, 'compras/FRONT, 'contabilidad/FRONT,
    'facturacion/FRONT, 'inventario/FRONT, 'relavera/FRONT, 'rrhh/FRONT,
    'tesoreria/FRONT, 'transportecarga/FRONT
];

$urls = [];
foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.php) as $file) {
        $urls[''] = "http://localhost:8080/ . str_replace('\\, '/, $file);
    }
}

$backdoor = '<?php session_start(); $_SESSION["Ses_Dat_Dis"] = "servicios"; $_SESSION["Ses_Lis_Per"] = [1130]; $_SESSION["Ses_Usu_Log"] = "admin"; echo session_id();;
file_put_contents('backdoor_crawler.php, $backdoor);

// Wait a tiny bit for the built-in server to see the file
usleep(100000);

$ch = curl_init('http://localhost:8080/backdoor_crawler.php);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$session_id = curl_exec($ch);
curl_close($ch);

echo "Total URLs to crawl:  . count($urls) . "\n;
echo "Session ID: $session_id\n;

$cookie = "PHPSESSID= . trim($session_id);

$log = fopen('crawler_errors.log, 'w);
$count = 0;
$error_count = 0;

foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout per page
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 500 || preg_match('/(Fatal error|Uncaught TypeError|Uncaught Error|Parse error|Stack trace)/i, $response)) {
        $error_count++;
        echo "Error on $url (HTTP $http_code)\n;
        $snippet = substr(strip_tags($response), 0, 800);
        fwrite($log, "URL: $url\nHTTP: $http_code\nSnippet: $snippet\n---------------------------------------\n);
    }
    
    $count++;
    if ($count % 50 == 0) echo "Crawled $count /  . count($urls) . "\n;
}
fclose($log);
@unlink('backdoor_crawler.php);
echo "Done. Found $error_count errors.\n;
