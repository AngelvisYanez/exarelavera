<?php

require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';

class DataAPI
{
    public $conexion;
    public $datos;
    public $bdd;

    function __construct($bdd)
    {
        $this->bdd = $bdd;
        $this->conexion = new MysqlConexion($bdd);
        $this->datos = new MysqlDatos();
    }

    public function query($sql)
    {
        $res = $this->datos->getArrayConsultaSql($sql, $this->conexion);
        return is_array($res) ? $res : [];
    }

    public function queryRow($sql)
    {
        $rows = $this->query($sql);
        return !empty($rows) ? $rows[0] : null;
    }

    public function queryScalar($sql)
    {
        $row = $this->queryRow($sql);
        if ($row && is_array($row)) {
            $vals = array_values($row);
            return $vals[0];
        }
        return null;
    }

    public function listAll($table, $where = [], $orderBy = null, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM `$table`";
        $sql .= $this->buildWhere($where);
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        if ($limit !== null) {
            $sql .= " LIMIT " . intval($limit);
            if ($offset !== null) {
                $sql .= " OFFSET " . intval($offset);
            }
        }
        return $this->query($sql);
    }

    public function listPaged($table, $where = [], $orderBy = null, $page = 1, $perPage = 50)
    {
        $page = max(1, intval($page));
        $perPage = max(1, min(500, intval($perPage)));
        $offset = ($page - 1) * $perPage;

        $total = $this->count($table, $where);
        $data = $this->listAll($table, $where, $orderBy, $perPage, $offset);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => ceil($total / $perPage)
        ];
    }

    public function findById($table, $idField, $idValue)
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

    public function tableExists($table)
    {
        $sql = "SHOW TABLES LIKE " . $this->escape($table);
        $rows = $this->query($sql);
        return !empty($rows);
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
        foreach ($rows as $r) {
            $tables[] = reset($r);
        }
        return $tables;
    }

    public function count($table, $where = [])
    {
        $sql = "SELECT COUNT(*) as total FROM `$table`" . $this->buildWhere($where);
        return intval($this->queryScalar($sql));
    }

    public function exists($table, $where = [])
    {
        return $this->count($table, $where) > 0;
    }

    public function escape($value)
    {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string)$value;
        $escaped = mysqli_real_escape_string($this->conexion->conexion, (string)$value);
        return "'$escaped'";
    }

    public function getError()
    {
        return $this->datos->Error;
    }

    public function getErrorMsg()
    {
        return $this->conexion->Error;
    }

    private function buildWhere($where)
    {
        if (empty($where)) return "";
        $clauses = [];
        foreach ($where as $k => $v) {
            if ($v === null) {
                $clauses[] = "`$k` IS NULL";
            } else {
                $clauses[] = "`$k` = " . $this->escape($v);
            }
        }
        return " WHERE " . implode(" AND ", $clauses);
    }
}
