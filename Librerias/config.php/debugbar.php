<?php

require_once(__DIR__ . '/../../DATA/libs/Env.php');
if(!isset($_SESSION)) @session_start();

// if(isset($_SESSION['Ses_Prs_Cod']) && $_SESSION['Ses_Prs_Cod']==1 && \Env::get('DEBUGBAR_ENABLED', false)) {
if(false && isset($_SESSION['Ses_Prs_Cod']) && $_SESSION['Ses_Prs_Cod']==1 && \Env::get('DEBUGBAR_ENABLED', false)) {
    include __DIR__ . '/../debugbar/vendor/autoload.php';
    include __DIR__ . '/../whoops/vendor/autoload.php';
    class DebugBar extends \DebugBar\DebugHelper{}
    class ChromePhp extends \DebugBar\DebugHelper{}
    $prefix='';if(isset($_SERVER['REQUEST_URI'])&&preg_match('/^\/exa\//',(string)$_SERVER['REQUEST_URI']))$prefix='/exa'; 
    DebugBar::initialize(__DIR__.'/../../../profiles', $prefix.'/Librerias/debugbar/open.php', $prefix.'/Librerias/debugbar/src/DebugBar/Resources', false, \Env::get('DEBUGBAR_OPEN_STORAGE', false));
    DebugBar::setEditor(\Env::get('DEBUGBAR_EDITOR', 'vscode'), \Env::get('DEBUGBAR_LOCAL_SITE_PATH', 'C:\xampp\htdocs\exa'));
    if(!\Env::get('DEBUGBAR_ENABLE_TRACES', true))
        DebugBar::enableFileTraces(false); // evita buscar el archivo origen, mas rapido
    if(\Env::get('DEBUGBAR_ENABLE_GLOBAL_TIMELINE', true))
        DebugBar::enableGlobalTimeline(true); // habilita el timeline para todos los collector
    if(\Env::get('DEBUGBAR_ENABLE_COLLECT_WARNINGS', true))
        DebugBar::collectWarnings(false); // false evita el error_handler default de php, true lo incluye
    //DebugBar::log(array('test' => 'test')); //envia mensajes igual que ChromePhp(info, debug, error, warning)
    //DebugBar::addException(new \Exception('Agregar Exception')); // Agrega errores silenciosamente
    //DebugBar::setDebugBar(null); // desactiva el debugbar
    DebugBar::blacklistGlobal('_COOKIE', array('PHPSESSID'));
    DebugBar::blacklistGlobal('_SESSION', array('PHPDEBUGBAR_STACK_DATA'));
    DebugBar::blacklistGlobal('_GET', array('PASSWORD'));
    DebugBar::blacklistGlobal('_POST', array('PASSWORD'));
    DebugBar::blacklistGlobal('_SERVER', array('COOKIE'));
    DebugBar::blacklistGlobal('_ENV_APP', array('DB_USERNAME', 'DB_PASSWORD', 'MAIL_USERNAME', 'MAIL_PASSWORD')); // Oculta datos sensibles como contaseñas
    //DebugBar::addRequestData('_ENV_APP', \Env::all()); // Agrega env al request
}else{
    class DebugBar { public static function __callStatic($name, $arguments) { if($name == 'measure' && isset($arguments[1]) && is_callable($arguments[1])) $arguments[1](); return null; } }
    class ChromePhp extends DebugBar{}
}
