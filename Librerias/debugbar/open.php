<?php

if(!isset($_REQUEST['op'])&&!isset($_REQUEST['max'])){
    header('HTTP/1.0 403 Forbidden');
    exit();
}

if(!isset($_SESSION)) @session_start();

if(!isset($_SESSION['Ses_Prs_Cod']) || $_SESSION['Ses_Prs_Cod']!=1) {
    header('HTTP/1.0 403 Forbidden');
    exit();
}

include __DIR__ . '/vendor/autoload.php';

\DebugBar\DebugHelper::openHandler(__DIR__ . '/../../../profiles');
