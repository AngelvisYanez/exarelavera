<?php
/**
 * Clase de conexión a MySQL
 * @package DATA
 */

class MysqlConexion
{
    public $conexion;
    private $host;
    private $usuario;
    private $clave;
    private $base_datos;

    public function __construct($base_datos = null)
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->usuario = getenv('DB_USER') ?: 'root';
        $this->clave = getenv('DB_PASSWORD') ?: 'root';
        
        if ($base_datos === null) {
            $this->base_datos = getenv('DB_NAME') ?: 'exa_database';
        } else {
            $this->base_datos = $base_datos;
        }

        $this->conectar();
    }

    private function conectar()
    {
        try {
            $this->conexion = new mysqli(
                $this->host,
                $this->usuario,
                $this->clave,
                $this->base_datos
            );

            if ($this->conexion->connect_error) {
                die("Error de conexión: " . $this->conexion->connect_error);
            }

            $this->conexion->set_charset("utf8");
        } catch (Exception $e) {
            die("Excepción de conexión: " . $e->getMessage());
        }
    }

    public function cerrar()
    {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }

    public function __destruct()
    {
        $this->cerrar();
    }
}
?>
