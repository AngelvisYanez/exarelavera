<?php

/**
* Permite tomar las variables GET y POST
*/

function getBody(){
    $content = file_get_contents('php://input');
    if (!empty($content)) {
        return json_decode($content, true);
    }
    return null;
}

$bodyPostData = getBody();
if(is_array($bodyPostData)){
    foreach ($bodyPostData as $key => $value) {
        $GLOBALS[$key] = $value;
        $$key = $value;
    }
}

/**
* Variables GET
*/
foreach ($_GET as $key => $value) {
    $GLOBALS[$key] = $value;
    $$key = $value;
}

/**
* Variables POST
*/
foreach ($_POST as $key => $value) {
    $GLOBALS[$key] = $value;
    $$key = $value;
}

/**
* Variables SESSION
*/
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}
if (isset($_SESSION) && is_array($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        $GLOBALS[$key] = $value;
        $$key = $value;
    }
}

$DirSep = DIRECTORY_SEPARATOR;
$APP_REAL_PATH = realpath(str_replace(basename(__FILE__), '', __FILE__) . '..' . $DirSep . '..' . $DirSep);

include_once(__DIR__ . '/debugbar.php');
include_once(__DIR__ . '/../../auditoria/LOGICA/aud_log_queue.php');
