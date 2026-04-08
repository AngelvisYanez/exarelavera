<?php

namespace Monolog;

class LoggerHelper {
    private static $basePath;
    private static $logger;
    private static $env;
    private static $file;
    private static $level;
    private static $session;
    private static $exclude;
    private static $existingLogs;

    public static function init($env, $basePath, $file, $level) {
        self::$env = $env;
        $sep = DIRECTORY_SEPARATOR;
        self::$basePath = realpath($basePath).$sep;
        self::$file = realpath(rtrim(self::$basePath, $sep).$sep.ltrim($file, $sep)) ?: $file;
        $levelValues = \Monolog\Logger::getLevels();
        self::$level = $levelValues['ERROR'];
        if(isset($levelValues[strtoupper($level)]))
            self::$level = $levelValues[strtoupper($level)];
        self::$existingLogs = array();

        $addError = function($errno, $errstr, $errfile, $errline=null) {
            $type = \Monolog\LoggerHelper::getExceptionType($errno);
            $level = 'NOTICE';
            if (preg_match('/[^_]+$/', $type, $matches)) $level = $matches[0];
            if (strpos($errstr,'mysqli_free_result()') === false)
            \Monolog\LoggerHelper::__callStatic(
                strtolower($level),
                array(
                    '"'.$errstr.'"',
                    is_a($errfile, \Exception::class)
                        ? array('type'=>$type, 'exception'=>$errfile)
                        : array('code'=>$errno, 'type'=>$type, 'file'=>\Monolog\LoggerHelper::normalizeFilePath($errfile), 'line'=>$errline)
                )
            );
        };
        $anteriorEx = set_exception_handler(function ($e) use ($addError, &$anteriorEx) {
            $addError($e->getCode(), $e->getMessage(), $e);

            if ($anteriorEx)
                call_user_func($anteriorEx, $e);
            else if(!!ini_get('display_errors')) {
                echo '<br/><b>Error: </b>Uncaught exception \'<b>'.get_class($e).'('.\Monolog\LoggerHelper::getExceptionType($e->getCode()).'</b>)\': with message \''.$e->getMessage().'\' ';
                echo 'thrown in <b>'.\Monolog\LoggerHelper::normalizeFilePath($e->getFile()).'</b> on line <b>'.$e->getLine().'</b> ';
                echo '<br/>Stack trace: <pre>\n'.$e->getTraceAsString().'</pre>';
            }

            exit(1);
        });
        $anteriorEr = set_error_handler(function ($errno, $errstr, $errfile, $errline){ return false; });
        restore_error_handler();
        if (class_exists('\DebugBar\DataCollector\ExceptionsCollector'))
            restore_error_handler();
        if (class_exists('\Whoops\Run'))
            restore_error_handler();
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($addError, &$anteriorEr) {
            if (strpos(\Monolog\LoggerHelper::normalizeFilePath($errfile), 'Librerias/') === false)
                $addError($errno, $errstr, $errfile, $errline);

            // Llamar al anterior si existe
            if (is_callable($anteriorEr)) {
                return call_user_func($anteriorEr, $errno, $errstr, $errfile, $errline);
            }

            return false; // Deja que PHP lo maneje si no hay otro
        });
        register_shutdown_function(function () use ($addError) {
            $error = error_get_last();
            if(is_null($error)||!in_array($error['type'],array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE))) return;
            $addError($error["type"], $error["message"], $error["file"], $error["line"]);
        });
    }
    public static function load() {
        if(self::$logger) return;
        $formatter = new \Monolog\Formatter\LineFormatter(null, 'Y-m-d H:i:s', true, true, true);
        $formatter->setBasePath(self::$basePath);
        $formatter->indentStacktraces('  ');
        $formatter->includeStacktraces(true, function($trace){
            return \Monolog\LoggerHelper::formatTraceExceptionForLog($trace);
        });
        $handler = new \Monolog\Handler\StreamHandler(self::$file, self::$level);
        $handler->setFormatter($formatter);
        self::$logger = new \Monolog\Logger(self::$env ?: 'app');
        self::$logger->pushHandler($handler);
    }
    public static function getExceptionType($errno) {
        $errorTypes = array(
            0    => 'CUSTOM_ERROR',
            1    => 'E_ERROR',
            2    => 'E_WARNING',
            4    => 'E_PARSE',
            8    => 'E_NOTICE',
            16   => 'E_CORE_ERROR',
            32   => 'E_CORE_WARNING',
            64   => 'E_COMPILE_ERROR',
            128  => 'E_COMPILE_WARNING',
            256  => 'E_USER_ERROR',
            512  => 'E_USER_WARNING',
            1024 => 'E_USER_NOTICE',
            2048 => 'E_STRICT',
            4096 => 'E_RECOVERABLE_ERROR',
            8192 => 'E_DEPRECATED',
            16384 => 'E_USER_DEPRECATED'
        );

        return isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'UNKNOWN';
    }
    public static function normalizeFilePath($file) {
        if (empty($file)) {
            return '';
        }

        if (@file_exists($file)) {
            $file = realpath($file);
        }

        if (strpos($file, self::$basePath) === 0) {
            $file = substr($file, strlen(self::$basePath));
        }

        return ltrim(str_replace('\\', '/', $file), '/');
    }
    public static function getFilePath() {
        return self::$file;
    }
    public static function sessionColects($array){
        self::$session = $array;
    }
    public static function getContext(){
        $data = array();
        if(!isset($_SESSION)) @session_start();
        foreach(self::$session ?: array() as $key){
            if(!isset($_SESSION[$key])) continue;
            $data[preg_replace('/^Ses_/', '', $key)] = is_numeric($_SESSION[$key]) ? $_SESSION[$key]+0 : $_SESSION[$key];
        }
        if(isset($_SERVER['REQUEST_URI']))
            $data['url'] = ltrim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
        return $data;
    }
    public static function formatTraceExceptionForLog($line){
        try{
            $traceArray = explode(' ',$line);
            if(in_array($traceArray[1],array('{main}')))return '';

            $traceArray[0] = '';
            if(DIRECTORY_SEPARATOR == '\\') $traceArray[1] = str_replace('\\', '/', $traceArray[1]);
            return trim(implode(' ', $traceArray));
        }catch(\Exception $e){unset($e);return $line;}
    }
    public static function __callStatic($name, $arguments) {
        self::load();
        $limit = array('DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY');

        if (strtoupper($name) === 'EXCEPTION'){
            $name = 'critical';
            $arguments = array(
                '"'.$arguments[0]->getMessage().'"',
                array(
                    'type' => \Monolog\LoggerHelper::getExceptionType($arguments[0]->getCode()), 'exception' => $arguments[0]
                ) + (isset($arguments[1]) ? (is_array($arguments[1]) ? $arguments[1] : array()) : array())
            );
        } elseif (strtoupper($name) === 'PARSE')
            $name = 'error';
        elseif (in_array(strtoupper($name), array('STRICT', 'DEPRECATED')))
            $name = 'notice';
        elseif (!in_array(strtoupper($name), $limit))
            $name = 'alert';
        if (in_array(strtoupper($name), $limit)) {
            $name = strtolower($name);
            $arguments[1] = (isset($arguments[1]) ? (is_array($arguments[1]) ? $arguments[1] : array()) : array()) + self::getContext();
            $hash = md5("{$name}-{$arguments[0]}-" . json_encode($arguments[1]));
            if (isset(self::$existingLogs[$hash])) {
                return;
            }

            self::$existingLogs[$hash] = true;
        }

        return call_user_func_array(array(self::$logger, $name), $arguments);
    }
}
