<?php mysqli_report(MYSQLI_REPORT_OFF);
if (!class_exists('DebugBar', false)) {
    $debugbarFile = __DIR__ . '/../Librerias/config.php/debugbar.php';
    if (file_exists($debugbarFile)) {
        require_once $debugbarFile;
    }
}
/** Inicializa la Sesion en las paginas **/
if (session_id() === '') @session_start();
/*****************************************/
/*     Clase para conexion con MySql     */
/*****************************************/
#[\AllowDynamicProperties]
class MysqlConexion{
    /* P R O P I E D A D E S */
    /*************************/
    /*  Parametros de conexión Master */
    public $BaseDatos;
    public $Servidor;
    public $Usuario;
    public $Clave;
    /* Constantes de conexion bases de datos */
    const bd = "exa_master"; //base de datos master
    const bdc = ""; //base de datos corporativa
    /*  Constantes de conexion */
    //"userExa";
    //"lynxsc7";
    /* credenciales LOCALES (por defecto) */
    const host = "localhost";
    const user = "root";
    const pass = "";
    public $conexion = 0;
    /* Número de error y texto error */
    public $Errno = 0;
    public $Error = "";
    /* M E T O D O S */
    /*****************/
    /* Método Constructor: Cada vez que creemos una variable de esta clase, se ejecutará esta función */
    function __construct() {
        $args = func_get_args(); // Captura los argumentos
        $nargs = func_num_args(); // Número de argumentos
        if ($nargs > 0 && $args[0] == "master") { // Determina si la base de datos es corporativa
            $nargs = -1;
        }
        switch ($nargs) {
            case 0:  //Constructor por defecto
                self::__construct1();
                break;
            case 1:  //Constructor común (personalizado)
                self::__construct2($args[0]);
                break;
            default: //Constructor por defecto corporativo
                self::__construct3();
                break;
        }
    }
    /* Carga configuracion externa si existe (produccion) */
    private function loadConfig() {
        $prodFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conexion-produccion' . DIRECTORY_SEPARATOR . 'database.php';
        if (file_exists($prodFile)) {
            $config = require $prodFile;
            if (is_array($config) && isset($config['host'], $config['user'], $config['pass'])) {
                return $config;
            }
        }
        return array('host' => self::host, 'user' => self::user, 'pass' => self::pass);
    }
    /* Constructor por defecto */
    function __construct1(){
        $cfg = $this->loadConfig();
        $this->BaseDatos = self::bd;
        $this->Servidor = $cfg['host'];
        $this->Usuario = $cfg['user'];
        $this->Clave = $cfg['pass'];
        $this->conectar();
    }
    /* Constructor común (personalizado) */
    function __construct2($bd) {
        $cfg = $this->loadConfig();
        $this->BaseDatos = $bd;
        $this->Servidor = $cfg['host'];
        $this->Usuario = $cfg['user'];
        $this->Clave = $cfg['pass'];
        $this->conectar();
    }
    /* Constructor por defecto (corporativo)  @ param $master="master" */
    function __construct3() {
        $cfg = $this->loadConfig();
        $this->BaseDatos = self::bdc;
        $this->Servidor = $cfg['host'];
        $this->Usuario = $cfg['user'];
        $this->Clave = $cfg['pass'];
        $this->conectar();
    }
    /* Conexión a la base de datos y selección de la base de datos */
    function conectar() {
        if (!!$this->link()) $this->selectDb(); /* Conectamos al servidor, y seleccionamos la base de datos */
        return $this->conexion; /* Si hemos tenido éxito conectando devuelve el identificador de la conexión, sino devuelve 0 */
    }
    /* Conexión UNICAMENTE a la base de datos */
    function link() {
        /* Conectamos al servidor */
        @$this->conexion = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave, $this->BaseDatos ?: null);
        if (!$this->conexion) {
            $this->Error = "Ha fallado la conexión. " . mysqli_connect_error();
            DebugBar::addTransactionEvent('Open Connection', array('is_success'=>false, 'error_message'=>$this->Error) + $this->getDB());
        } else {
            @mysqli_set_charset($this->conexion, 'utf8');
        }
        return $this->conexion; /* Si hemos tenido éxito conectando devuelve el identificador de la conexión, sino devuelve 0 */
    }
    /* Selecciona la base de datos */
    function selectDb($db = null) {
        /* Seleccionamos la base de datos */
        $dbs = empty($db) ? $this->BaseDatos : $db;
        if (!$this->conexion || !@mysqli_select_db($this->conexion, $dbs)) {
            $this->Error = "Imposible abrir " . $dbs;
            DebugBar::addTransactionEvent('Open Connection', array('is_success'=>false, 'error_message'=>$this->Error) + $this->getDB());
            return false;
        }
        DebugBar::addTransactionEvent('Open Connection', $this->getDB());
        return $this->conexion;
    }
    /* Cierra la conexion */
    function cerrar() {
        DebugBar::addTransactionEvent('Close Connection', $this->getDB());
        if (!($this->conexion instanceof \mysqli)) return NULL;
        try {
            return @mysqli_close($this->conexion);
        } catch (\Throwable $e) {
            return NULL;
        }
    }
    function getDB() {
        return array('db' => $this->BaseDatos) +
            (!$this->conexion ? array('is_success'=>false, 'error_message'=>'La conexion presenta problemas') : array());
    }
} //Fin de clase Class_Conexion
class Class_Log_Conexion_Global extends MysqlConexion {}
class MyGlobalConexion extends MysqlConexion {}
