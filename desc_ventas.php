<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/config_db.php';
$c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$q = $c->query("DESCRIBE ventas");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
