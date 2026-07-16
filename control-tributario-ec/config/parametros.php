<?php
// config/parametros.php
$json = file_get_contents(__DIR__ . '/parametros.json');
return json_decode($json, true);
