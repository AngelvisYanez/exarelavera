<?php

class DataAPI
{
    protected $conexion;
    protected $datos;

    function __construct($bdd)
    {
        $this->conexion = new MysqlConexion($bdd);
        $this->datos = new MysqlDatos();
    }

    public function escape($value)
    {
        if (is_null($value)) return 'NULL';
        return "'" . mysqli_real_escape_string($this->conexion->conexion, $value) . "'";
    }

    public function query($sql)
    {
        return $this->datos->getArrayConsultaSql($sql, $this->conexion);
    }

    public function queryRow($sql)
    {
        return $this->datos->getRowConsultaSql($sql, $this->conexion);
    }

    public function list($table, $where = [], $order = '', $limit = 1000, $offset = 0)
    {
        $sql = "SELECT * FROM `$table`";
        if (!empty($where)) {
            $parts = [];
            foreach ($where as $k => $v) {
                $parts[] = "`$k` = " . $this->escape($v);
            }
            $sql .= " WHERE " . implode(" AND ", $parts);
        }
        if ($order) $sql .= " ORDER BY $order";
        $sql .= " LIMIT $offset, $limit";
        return $this->query($sql);
    }

    public function getById($table, $idField, $idValue)
    {
        $sql = "SELECT * FROM `$table` WHERE `$idField` = " . $this->escape($idValue) . " LIMIT 1";
        return $this->queryRow($sql);
    }

    public function insert($table, $data)
    {
        $fields = [];
        $values = [];
        foreach ($data as $k => $v) {
            $fields[] = "`$k`";
            $values[] = $this->escape($v);
        }
        $sql = "INSERT INTO `$table` (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
        $this->datos->consulta($sql, $this->conexion);
        if ($this->datos->Error == 0) {
            return $this->datos->insercionid($this->conexion);
        }
        return false;
    }

    public function update($table, $data, $idField, $idValue)
    {
        $sets = [];
        foreach ($data as $k => $v) {
            $sets[] = "`$k` = " . $this->escape($v);
        }
        $sql = "UPDATE `$table` SET " . implode(", ", $sets) . " WHERE `$idField` = " . $this->escape($idValue);
        $this->datos->consulta($sql, $this->conexion);
        return $this->datos->Error == 0;
    }

    public function delete($table, $idField, $idValue)
    {
        $sql = "DELETE FROM `$table` WHERE `$idField` = " . $this->escape($idValue);
        $this->datos->consulta($sql, $this->conexion);
        return $this->datos->Error == 0;
    }

    public function tableInfo($table)
    {
        $sql = "DESCRIBE `$table`";
        return $this->query($sql);
    }

    public function listTables($pattern = null)
    {
        $sql = "SHOW TABLES";
        if ($pattern) {
            $sql .= " LIKE " . $this->escape($pattern);
        }
        $rows = $this->query($sql);
        $tables = [];
        $key = 'Tables_in_' . $this->conexion->BaseDatos;
        foreach ($rows as $r) {
            $tables[] = $r[$key];
        }
        return $tables;
    }

    public function count($table, $where = [])
    {
        $sql = "SELECT COUNT(*) AS total FROM `$table`";
        if (!empty($where)) {
            $parts = [];
            foreach ($where as $k => $v) {
                $parts[] = "`$k` = " . $this->escape($v);
            }
            $sql .= " WHERE " . implode(" AND ", $parts);
        }
        $row = $this->queryRow($sql);
        return $row ? (int)$row['total'] : 0;
    }
}
