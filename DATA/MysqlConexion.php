<?php

require_once(__DIR__ . '/libs/Env.php');
/** Inicializa la Sesion en las paginas **/
if (session_id() === '') @session_start();

/*****************************************/
/*     Clase para conexion con MySql     */
/*****************************************/
class MysqlConexion
{
    /* P R O P I E D A D E S */
    /*************************/
    /*  Parametros de conexión Master */
    var $BaseDatos;
    var $Servidor;
    var $Puerto;
    var $Usuario;
    var $Clave;

    private static $loaded = false;
    /* Constantes de conexion bases de datos */
    private static $bd; //base de datos master
    private static $bdc;//base de datos corporativa
    /*  Constantes de conexion */
    private static $host;
    private static $port;
    private static $user;
    private static $pass;

    var $conexion = 0;
    /* Número de error y texto error */
    var $Errno = 0;
    var $Error = "";

    private static function load(){
        if (self::$loaded) return;

        /* Constantes de conexion bases de datos */
        self::$bd  = \Env::get('DB_DATABASE', 'exa_master'); //base de datos master
        self::$bdc = \Env::get('DB_DATABASE_CORP', '');     //base de datos corporativa
        /*  Constantes de conexion */
        self::$host = \Env::get('DB_HOST', '127.0.0.1');
        self::$port = \Env::get('DB_PORT', 3306);
        self::$user = \Env::get('DB_USERNAME', 'root');
        self::$pass = \Env::get('DB_PASSWORD');

        self::$loaded = true;
    }

    /* M E T O D O S */
    /*****************/
    /* Método Constructor: Cada vez que creemos una variable de esta clase, se ejecutará esta función */
    function __construct()
    {
        self::load();
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
    /* Constructor por defecto */
    function __construct1()
    {
        $this->BaseDatos = self::$bd;
        $this->Servidor = self::$host;
        $this->Puerto = self::$port;
        $this->Usuario = self::$user;
        $this->Clave = self::$pass;
        $this->conectar();
    }
    /* Constructor común (personalizado) */
    function __construct2($bd)
    {
        $this->BaseDatos = $bd;
        $this->Servidor = self::$host;
        $this->Puerto = self::$port;
        $this->Usuario = self::$user;
        $this->Clave = self::$pass;
        $this->conectar();
    }
    /* Constructor por defecto (corporativo)  @ param $master="master" */
    function __construct3()
    {
        $this->BaseDatos = self::$bdc;
        $this->Servidor = self::$host;
        $this->Puerto = self::$port;
        $this->Usuario = self::$user;
        $this->Clave = self::$pass;
        $this->conectar();
    }
    /* Conexión a la base de datos y selección de la base de datos */
    function conectar()
    {
        if (!!$this->link()) $this->selectDb(); /* Conectamos al servidor, y seleccionamos la base de datos */
        return $this->conexion; /* Si hemos tenido éxito conectando devuelve el identificador de la conexión, sino devuelve 0 */
    }
    /* Conexión UNICAMENTE a la base de datos */
    function link()
    {
        /* Conectamos al servidor */
        @$this->conexion = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave, $this->BaseDatos ?: null, $this->Puerto);
        if (!$this->conexion) {
            $this->Error = "Ha fallado la conexión. " . mysqli_connect_error();
            DebugBar::addTransactionEvent('Open Connection', array('is_success'=>false, 'error_message'=>$this->Error) + $this->getDB());
        }
        return $this->conexion; /* Si hemos tenido éxito conectando devuelve el identificador de la conexión, sino devuelve 0 */
    }
    /* Selecciona la base de datos */
    function selectDb($db = null)
    {
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
    function cerrar()
    {
        DebugBar::addTransactionEvent('Close Connection', $this->getDB());
        return (!$this->conexion) ? NULL : @mysqli_close($this->conexion);
    }
    function getDB()
    {
        return array('db' => $this->BaseDatos) +
            (!$this->conexion ? array('is_success'=>false, 'error_message'=>'La conexion presenta problemas') : array());
    }
} //Fin de clase Class_Conexion
class Class_Log_Conexion_Global extends MysqlConexion {}
class MyGlobalConexion extends MysqlConexion {}
