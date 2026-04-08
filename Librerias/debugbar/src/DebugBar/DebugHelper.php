<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

use DebugBar\DebugBar;
use DebugBar\DataCollector\FilesCollector;
use DebugBar\DataCollector\ObjectCountCollector;
use DebugBar\DataCollector\QueryCollector;
use DebugBar\DataCollector\LogsCollector;
use DebugBar\OpenHandler;
use DebugBar\Storage\TempFileStorage;
use DebugBar\Storage\FileStorage;

class DebugHelper
{
    /**
     * @method static void addException(\Exception $e) Agrega una excepción al colector.
     * @method static void addThrowable(\Throwable $t) Agrega un Throwable al colector.
     * @method static void addWarning(int $errno, string $errstr, string $errfile = '', int $errline = 0) Agrega una advertencia al colector.
     * @method static array collectWarnings(bool $preserveOriginalHandler = false) Obtiene todas las advertencias recopiladas.
     *
     * @method static void addMeasure(string $label, float $start, float $end, array $params = [], string $collector = null, string $group = null) Agrega una medición de tiempo al colector.
     * @method static mixed measure(string $label, callable $callback, string $collector = null) Ejecuta y mide el tiempo de una función.
     * @method static void startMeasure(string $name, string $label, string $collector = null, string $group = null) Inicia una medición de tiempo.
     * @method static void stopMeasure(string $name, array $params = []) Detiene una medición de tiempo.
     * @method static bool hasStartedMeasure(string $name) Verifica si una medición ha sido iniciada.
     *
     * @method static void addMessage(mixed $message, string $label = 'info', mixed $isString = true) Agrega un mensaje al colector.
     * @method static void aggregate(\DebugBar\DataCollector\MessagesAggregateInterface $messages) Agrega múltiples mensajes al colector.
     * @method static void emergency(string $message, array $context = array()) Registra un mensaje de nivel emergencia.
     * @method static void alert(mixed $message, array $context = array()) Registra un mensaje de nivel alerta.
     * @method static void critical(mixed $message, array $context = array()) Registra un mensaje de nivel crítico.
     * @method static void error(mixed $message, array $context = array()) Registra un mensaje de nivel error.
     * @method static void warning(mixed $message, array $context = array()) Registra un mensaje de nivel advertencia.
     * @method static void notice(mixed $message, array $context = array()) Registra un mensaje de nivel aviso.
     * @method static void info(mixed $message, array $context = array()) Registra un mensaje de nivel información.
     * @method static void debug(mixed $message, array $context = array()) Registra un mensaje de nivel depuración.
     *
     * @method static void countClass(mixed $class, int $count = 1) Cuenta las instancias de un objeto, string
     *
     * @method static void startQueryMeasure() Inicia la medición de una consulta.
     * @method static void addQuery(string $query, array $extraData = array()) Agrega una consulta al colector.
     * @method static void addTransactionEvent(string $event, array $extraData = array()) Registra un evento de transacción.
     * @method static void addComment(string $comment, array $extraData = array()) Agrega un comentario al colector.
     */

    protected static $debugbar = null;
    protected static $startTime = null;
    protected static $stacked = false;
    protected static $whoops = null;
    protected static $whoopsHandler = null;

    public static function loadClass(){}
    public static function initialize($profilerPath, $openHandler, $baseUrl = null, $noRender = false, $persist = false) {
        if (!is_null(self::$debugbar)) return;
        if (class_exists('\Whoops\Run')){
            self::$whoopsHandler = new \Whoops\Handler\PrettyPageHandler();
            self::$whoopsHandler->setApplicationRootPath(array(self::getMainPath()));
            self::$whoops = new \Whoops\Run();
            self::$whoops->allowQuit(false);
            self::$whoops->pushHandler(self::$whoopsHandler);
            if (self::isAjax()) {
                $jsonHandler = new \Whoops\Handler\JsonResponseHandler();
                // You can also tell JsonResponseHandler to give you a full stack trace:
                $jsonHandler->addTraceToOutput(true);

                // You can also return a result compliant to the json:api spec
                // re: http://jsonapi.org/examples/#error-objects
                // tl;dr: error[] becomes errors[[]]
                //$jsonHandler->setJsonApi(true);

                // And push it into the stack:
                self::$whoops->pushHandler($jsonHandler);
            }
            self::$whoops->register();
        }

        if(function_exists('memory_reset_peak_usage'))memory_reset_peak_usage();
        $memoryStart = memory_get_usage(false);
        $timeStart = microtime(true);
        $debugbar = new StandardDebugBar();
        $requestTime = isset(self::$startTime) ? self::$startTime : $debugbar['time']->getRequestStartTime();
        $debugbar['time']->showMemoryUsage(true);
        $debugbar['time']->addMeasure('Booting', $requestTime, microtime(true), array('memoryUsage' => $memoryStart));

        $debugbar->setStorage($persist?new FileStorage($profilerPath):new TempFileStorage($profilerPath));
        $debugbar->addCollector(new QueryCollector(null,$debugbar['exceptions'],'MySQL'));
        $debugbar->addCollector(new ObjectCountCollector());
        $debugbar->addCollector(new FilesCollector());
        $debugbar->addCollector(new LogsCollector());

        $debugbar['memory']->setPrecision(1);
        $debugbar['memory']->resetMemoryBaseline(true);
        $debugbar['queries']->setDurationBackground(true);
        $debugbar['queries']->setSlowThreshold(10000);

        $debugbarRenderer = $debugbar->getJavascriptRenderer($baseUrl)
            ->setAjaxHandlerAutoShow(true)
            ->setBaseUrl(is_null($baseUrl) ? '../src/DebugBar/Resources' : $baseUrl)
            ->setAjaxHandlerEnableTab(true)
            ->setDeferDatasets(true)
            ->setHideEmptyTabs(true)
            ->setEnableJqueryNoConflict(true)
            ->setTheme(isset($_GET['theme']) ? $_GET['theme'] : 'auto')
            ->setOpenHandlerUrl($openHandler);

        $previusExHandler = null;
        $previusExHandler = set_exception_handler(function ($exception) use (&$previusExHandler) {
            DebugHelper::addException($exception);
            if ($previusExHandler)
                call_user_func($previusExHandler, $exception);
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if(is_null($error)||!in_array($error['type'],array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE))) return;
            DebugHelper::addWarning($error["type"], $error["message"], $error["file"], $error["line"]);
        });
        if ($noRender) {
            if (self::isAjax())
                register_shutdown_function(function () {
                    if(!DebugHelper::stacked())
                        DebugHelper::sendDataInHeaders(true);
                });
        } else {
            //if (! self::isAjax()) {
                ob_start();
            //}

            register_shutdown_function(function () {

                if (DebugHelper::isAjax() || !DebugHelper::hasDebugBar()){
                    if(!DebugHelper::stacked())
                        DebugHelper::sendDataInHeaders(true);
                    ob_end_flush();
                }else {
                    $content = ob_get_contents();
                    ob_get_clean(); // Clean buffer

                    if (DebugHelper::stacked()) {
                        echo $content;
                        return;
                    }

                    // Try to put the js/css directly before the </head>
                    $pos = stripos($content, '</head>');
                    if (false !== $pos) {
                        $content = substr($content, 0, $pos) . DebugHelper::renderHead() . substr($content, $pos);

                        // Try to put the widget at the end, directly before the </body>
                        $pos = strripos($content, '</body>');
                        if (false !== $pos) {
                            $content = substr($content, 0, $pos) . DebugHelper::render() . substr($content, $pos);
                        } else {
                            DebugHelper::addWarning(E_USER_NOTICE, '`</body>` tag not found on html string.');
                            $content = $content . DebugHelper::render();
                        }
                    } else {
                        DebugHelper::addWarning(E_USER_NOTICE, '`</head>` tag not found on html string.');
                        DebugHelper::getData();
                    }

                    echo $content; // show the content
                }
            });
        }

        $debugbar['time']->addMeasure('Debugbar Load', $timeStart, microtime(true), array('memoryUsage' => memory_get_usage(false)));
        self::setDebugBar($debugbar);
        self::enableFileTraces(true);
        self::setPathReplacements(' ');
        return $debugbarRenderer;
    }

    public static function setDebugBar($debugbarInstance) {
        self::$debugbar = $debugbarInstance;
    }

    public static function hasDebugBar() {
        return !empty(self::$debugbar);
    }

    public static function stacked() {
        return self::$stacked;
    }

    private static function getMainPath() {
        return realpath(__DIR__.'/../../../../').DIRECTORY_SEPARATOR;
    }

    public static function setEditor($editor, $localPath = null) {
        if (is_null(self::$debugbar) || !$editor) return;
        if (! is_null($localPath))
            $replacements = array_fill_keys(
                array(self::getMainPath()),
                rtrim($localPath, "/\\").DIRECTORY_SEPARATOR
            );

        if (!is_null(self::$whoopsHandler)) {
            self::$whoopsHandler->setEditor($editor);
            if (! is_null($localPath)) {
                self::$whoopsHandler->setEditorPathReplacements($replacements);
            }
        }

        foreach (self::$debugbar->getCollectors() as $collector) {
            if (method_exists($collector, 'setEditorLinkTemplate'))
                $collector->setEditorLinkTemplate($editor);
            if (! is_null($localPath) && method_exists($collector, 'addXdebugReplacements'))
                $collector->addXdebugReplacements($replacements);
        }
    }

    public static function setPathReplacements($local = null, $remotePaths = array()) {
        if (is_null(self::$debugbar)) return;
        $path = self::getMainPath();
        $localPath = !is_null($local) ? rtrim($local, "/\\").DIRECTORY_SEPARATOR : $path;
        $remotePaths = count($remotePaths) ? array_filter($remotePaths) : array($path);
        $replacements = array_fill_keys($remotePaths, $localPath);

        foreach (self::$debugbar->getCollectors() as $collector)
            if (method_exists($collector, 'addXdebugReplacements'))
                $collector->addXdebugReplacements($replacements);

        if (!is_null(self::$whoopsHandler)) {
            self::$whoopsHandler->setEditorPathReplacements($replacements);
        }
    }

    public static function blacklistGlobal($superGlobalName, $keys) {
        if(! is_array($keys)) $keys = array($keys);
        if (!is_null(self::$whoopsHandler)) {
            foreach ($keys as $key) {
                self::$whoopsHandler->hideSuperglobalKey($superGlobalName, $key);
            }
        }

        if (!is_null(self::$debugbar) && isset(self::$debugbar['request'])) {
            self::$debugbar['request']->hideSuperglobalKeys($superGlobalName, $keys);
        }
    }

    public static function enableFileTraces($enable = true) {
        if (is_null(self::$debugbar)) return;
        if (isset(self::$debugbar['queries']))
            self::$debugbar['queries']->setFindSource($enable);
        if (isset(self::$debugbar['messages']))
            self::$debugbar['messages']->collectFileTrace($enable);
    }

    public static function enableGlobalTimeline($enable = true) {
        if (is_null(self::$debugbar) || ! isset(self::$debugbar['time'])) return;
        if (isset(self::$debugbar['queries']))
            self::$debugbar['queries']->setTimeline(!$enable ? null : self::$debugbar['time']);
    }

    public static function addQueryComment($comment, $other = array()) {
        if (is_null(self::$debugbar)) return;
        if (isset(self::$debugbar['queries']))
            self::$debugbar['queries']->addComment(comment, $other);
    }

    public static function getJavascriptRenderer() {
        if (is_null(self::$debugbar)) return;
        return self::$debugbar->getJavascriptRenderer();
    }

    public static function renderHead() {
        if (is_null(self::$debugbar)) return;
        return self::$debugbar->getJavascriptRenderer()->renderHead();
    }

    public static function render() {
        if (is_null(self::$debugbar)) return;
        return self::$debugbar->getJavascriptRenderer()->render();
    }

    public static function aggregateMessagesCollector(MessagesAggregateInterface $messages) {
        if (is_null(self::$debugbar)) return;
        if (isset(self::$debugbar['messages']))
            self::$debugbar['messages']->aggregate($messages);
    }

    public static function log() {
        $args = func_get_args();
        if (is_null(self::$debugbar)) return;
        if (! isset(self::$debugbar['messages'])) return;
        foreach ($args as $message) {
            self::$debugbar['messages']->log('info', $message);
        }
    }

    public static function openHandler($profilerPath){
        $debugbar = new DebugBar();
        $debugbar->setStorage(new TempFileStorage($profilerPath));
        $openHandler = new OpenHandler($debugbar);
        $openHandler->handle();
    }

    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function __callStatic($name, $arguments) {
        if (is_null(self::$debugbar)){
            if ($name == 'measure' && isset($arguments[1]) && is_callable($arguments[1])) {
                $arguments[1]();
            }

            return;
        }

        if (method_exists(__CLASS__, $name)) {
            return call_user_func_array(array(__CLASS__, $name), $arguments);
        }

        if (in_array($name, array('addMessage', 'aggregate')) && isset(self::$debugbar['messages'])) {
            return call_user_func_array(array(self::$debugbar['messages'], $name), $arguments);
        }

        if (in_array($name, array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug')) && isset(self::$debugbar['messages'])) {
            return call_user_func_array(array(self::$debugbar['messages'], $name), $arguments);
        }

        if (in_array($name, array('startQueryMeasure', 'addQuery', 'addTransactionEvent', 'addComment')) && isset(self::$debugbar['queries'])) {
            return call_user_func_array(array(self::$debugbar['queries'], $name), $arguments);
        }

        if (in_array($name, array('addMeasure', 'measure', 'startMeasure', 'stopMeasure', 'hasStartedMeasure')) && isset(self::$debugbar['time'])) {
            return call_user_func_array(array(self::$debugbar['time'], $name), $arguments);
        }

        if (in_array($name, array('countClass')) && isset(self::$debugbar['counter'])) {
            return call_user_func_array(array(self::$debugbar['counter'], $name), $arguments);
        }

        if (in_array($name, array('addException', 'addThrowable', 'addWarning', 'collectWarnings')) && isset(self::$debugbar['exceptions'])) {
            return call_user_func_array(array(self::$debugbar['exceptions'], $name), $arguments);
        }

        if (in_array($name, array('addRequestData')) && isset(self::$debugbar['request'])) {
            return call_user_func_array(array(self::$debugbar['request'], $name), $arguments);
        }

        if (method_exists(self::$debugbar, $name)) {
            if ($name === 'stackData') {
                self::$stacked = true;
            }

            return call_user_func_array(array(self::$debugbar, $name), $arguments);
        }

        return;
    }
}

// Uso seguro sin riesgo de error:
//DebugHelper::addMessage("Mensaje de prueba");
