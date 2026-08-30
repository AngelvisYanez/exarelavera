<?php
// Enrutador de contingencia para /api/v1/*
$_SERVER['SCRIPT_NAME'] = '/api/index.php';
require_once __DIR__ . '/../index.php';
