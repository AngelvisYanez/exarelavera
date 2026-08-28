<?php
// Enrutador directo para /api/v1/contactos
$_SERVER['SCRIPT_NAME'] = '/api/index.php';
$_SERVER['PATH_INFO'] = '/v1/contactos';
require_once __DIR__ . '/../../index.php';
