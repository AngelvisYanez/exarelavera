<?php

require_once(__DIR__ . '/../../DATA/libs/Env.php');

if(\Env::get('LOG_ENABLED', false) && strtoupper(\Env::get('LOG_LEVEL', 'ERROR'))!=='NONE') {
    require_once(__DIR__ . '/../monolog/vendor/autoload.php'); 
    class Logger extends \Monolog\LoggerHelper {}
    Logger::init(\Env::get('APP_ENV', 'local'), __DIR__.'/../..', \Env::get('LOG_FILE', '../profiles/exa.log'), \Env::get('LOG_LEVEL', 'ERROR'));
    Logger::sessionColects(array('Ses_Emp_Cod', 'Ses_Usu_Cod', 'Ses_Prs_Cod', 'Ses_Dat_Dis', 'Ses_Ses_Cod'));
    // debug, info, notice, warning, error, critical, alert, emergency, exception
    //Logger::info('Este es un mensaje informativo');
    //Logger::warning('Este es un mensaje de advertencia');
    //Logger::error('Este es un mensaje de error');
    //Logger::critical('Este es un mensaje de error muy importante');
    //Logger::exception(new \Exception('Una Exception', 256));
}else{
    class Logger { public static function __callStatic($name, $arguments) { return null; } }
}
