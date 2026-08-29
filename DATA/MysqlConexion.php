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
    const bd = "exa"; //base de datos master
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
            case 4:  //Constructor completo
                self::__construct3($args[0], $args[1], $args[2], $args[3]);
                break;
            default:
                self::__construct1();
                break;
        }
    }
    /* Constructor por defecto */
    function __construct1() {
        $env = __DIR__ . '/../.env';
        if (file_exists($env)) {
            $vars = parse_ini_file($env);
            if (!empty($vars['DB_HOST'])) {
                self::__construct3(
                    $vars['DB_NAME'] ?? self::bd,
                    $vars['DB_HOST'],
                    $vars['DB_USER'] ?? self::user,
                    $vars['DB_PASS'] ?? self::pass
                );
                return;
            }
        }
        self::__construct3(self::bd, self::host, self::user, self::pass);
    }
    /* Constructor común */
    function __construct2($bd) {
        $env = __DIR__ . '/../.env';
        if (file_exists($env)) {
            $vars = parse_ini_file($env);
            if (!empty($vars['DB_HOST'])) {
                self::__construct3(
                    $bd,
                    $vars['DB_HOST'],
                    $vars['DB_USER'] ?? self::user,
                    $vars['DB_PASS'] ?? self::pass
                );
                return;
            }
        }
        self::__construct3($bd, self::host, self::user, self::pass);
    }
    /* Constructor completo */
    function __construct3($bd, $host, $user, $pass) {
        $this->BaseDatos = $bd;
        $this->Servidor  = $host;
        $this->Usuario   = $user;
        $this->Clave     = $pass;
        $this->conectar();
    }
    /* Conexión a la base de datos */
    function conectar() {
        $t_start = microtime(true);
        $this->conexion = @mysqli_connect($this->Servidor, $this->Usuario, $this->Clave, $this->BaseDatos);
        $duration = microtime(true) - $t_start;
        if (!$this->conexion) {
            $this->Errno = mysqli_connect_errno();
            $this->Error = "Ha fallado la conexión: " . mysqli_connect_error();
            DebugBar::addTransactionEvent('DB Connect', array(
                'is_success'    => false,
                'error_message' => $this->Error,
                'duration'      => $duration
            ) + $this->getDB());
            return 0;
        }
        $this->Errno = 0;
        $this->Error = "";
        @mysqli_set_charset($this->conexion, "latin1");
        DebugBar::addTransactionEvent('DB Connect', array(
            'is_success' => true,
            'duration'   => $duration
        ) + $this->getDB());
        return $this->conexion;
    }
    /* Retorna la conexión activa */
    function getConexion() {
        return $this->conexion;
    }
    /* Retorna el estado de la conexión */
    function estadoConexion() {
        return ($this->conexion) ? true : false;
    }
    /* Cierra la conexión */
    function close() {
        if ($this->conexion && is_object($this->conexion) && $this->conexion instanceof mysqli) {
            @mysqli_close($this->conexion);
            $this->conexion = 0;
        }
    }
    /* Compatibilidad con código antiguo que usa cerrar() */
    function cerrar() {
        $this->close();
    }
    /* Destructor de la clase */
    function __destruct() {
        $this->close();
    }
    /* Retorna array con datos de conexion para debugbar */
    function getDB() {
        return array(
            'db_name' => $this->BaseDatos,
            'host'    => $this->Servidor,
            'user'    => $this->Usuario
        );
    }
}

#[\AllowDynamicProperties]
class MysqlConexionContab extends MysqlConexion {}
?>
