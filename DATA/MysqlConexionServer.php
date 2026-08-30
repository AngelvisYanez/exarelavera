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
    /*  Parametros de conexion Master */
    public $BaseDatos;
    public $Servidor;
    public $Puerto;
    public $Usuario;
    public $Clave;
    private static $loaded = false;
    /* Constantes de conexion bases de datos */
    private static $bd;
    private static $bdc;
    /*  Constantes de conexion */
    private static $host;
    private static $port;
    private static $user;
    private static $pass;
    public $conexion = 0;
    /* Numero de error y texto error */
    public $Errno = 0;
    public $Error = "";
    private static function load(){
        if (self::$loaded) return;
        self::$bd  = \Env::get('DB_DATABASE', 'exa_master');
        self::$bdc = \Env::get('DB_DATABASE_CORP', '');
        self::$host = \Env::get('DB_HOST', '127.0.0.1');
        self::$port = \Env::get('DB_PORT', 3306);
        self::$user = \Env::get('DB_USERNAME', 'root');
        self::$pass = \Env::get('DB_PASSWORD');
        self::$loaded = true;
    }
    /* M E T O D O S */
    /*****************/
    /* Metodo Constructor */
    function __construct()
    {
        self::load();
        $args = func_get_args();
        $nargs = func_num_args();
        if ($nargs > 0 && $args[0] == "master") {
            $nargs = -1;
        }
        switch ($nargs) {
            case 0:
                self::__construct1();
                break;
            case 1:
                self::__construct2($args[0]);
                break;
            default:
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
    /* Constructor comun (personalizado) */
    function __construct2($bd)
    {
        $this->BaseDatos = $bd;
        $this->Servidor = self::$host;
        $this->Puerto = self::$port;
        $this->Usuario = self::$user;
        $this->Clave = self::$pass;
        $this->conectar();
    }
    /* Constructor por defecto (corporativo) */
    function __construct3()
    {
        $this->BaseDatos = self::$bdc;
        $this->Servidor = self::$host;
        $this->Puerto = self::$port;
        $this->Usuario = self::$user;
        $this->Clave = self::$pass;
        $this->conectar();
    }
    /* Conexion a la base de datos y seleccion de la base de datos */
    function conectar()
    {
        if (!!$this->link()) $this->selectDb();
        return $this->conexion;
    }
    /* Conexion UNICAMENTE a la base de datos */
    function link()
    {
        @$this->conexion = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave, $this->BaseDatos ?: null, $this->Puerto);
        if (!$this->conexion) {
            $this->Error = "Ha fallado la conexion. " . mysqli_connect_error();
            DebugBar::addTransactionEvent('Open Connection', array('is_success'=>false, 'error_message'=>$this->Error) + $this->getDB());
        } else {
            @mysqli_set_charset($this->conexion, 'utf8');
        }
        return $this->conexion;
    }
    /* Selecciona la base de datos */
    function selectDb($db = null)
    {
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
}
class Class_Log_Conexion_Global extends MysqlConexion {}
class MyGlobalConexion extends MysqlConexion {}
