<?php
/**
 * Clase de datos para ejecutar operaciones en MySQL
 * @package DATA
 */

class MysqlDatos
{
    /**
     * Ejecuta una consulta en MySQL
     */
    public function consulta($sql, $conexion)
    {
        return $conexion->query($sql);
    }

    /**
     * Obtiene una fila asociativa
     */
    public function fetch_assoc($result)
    {
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Obtiene todas las filas
     */
    public function fetch_all($result)
    {
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Libera resultado
     */
    public function free_result($result)
    {
        if ($result) {
            $result->free();
        }
    }

    /**
     * Graba registros (INSERT, UPDATE, DELETE)
     */
    public function grabarv_registros($sql, $conexion)
    {
        return $conexion->query($sql);
    }

    /**
     * Obtiene el ID del último insert
     */
    public function insercionid($conexion)
    {
        return $conexion->insert_id;
    }

    /**
     * Inicia transacción
     */
    public function inicio_transaccion($conexion)
    {
        $conexion->begin_transaction();
    }

    /**
     * Finaliza transacción
     */
    public function fin_transaccion($conexion)
    {
        $conexion->commit();
    }

    /**
     * Revierte transacción
     */
    public function rollback_transaccion($conexion)
    {
        $conexion->rollback();
    }

    /**
     * Procesa parámetros para la SQL
     */
    public function parametros($param)
    {
        return $param;
    }
}
?>
