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
        if ($order) {
            $order = preg_replace('/[^a-zA-Z0-9_.,\s]/', '', $order);
            $sql .= " ORDER BY $order";
        }
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

    public function exists($table, $where)
    {
        return $this->count($table, $where) > 0;
    }

    public function listPaged($table, $where = [], $order = '', $page = 1, $perPage = 50)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(500, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        $total = $this->count($table, $where);
        $data = $this->list($table, $where, $order, $perPage, $offset);
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'pages' => (int)ceil($total / $perPage)];
    }

    public function listPagedSql($sql, $countSql, $page = 1, $perPage = 50)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(500, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        $countRow = $this->queryRow($countSql);
        $total = $countRow ? (int)$countRow['total'] : 0;
        $sql .= " LIMIT $offset, $perPage";
        $data = $this->query($sql);
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'pages' => (int)ceil($total / $perPage)];
    }

    public function queryScalar($sql)
    {
        $row = $this->queryRow($sql);
        if (!$row) return null;
        return reset($row);
    }

    public function beginTransaction()
    {
        mysqli_autocommit($this->conexion->conexion, false);
    }

    public function commit()
    {
        mysqli_commit($this->conexion->conexion);
        mysqli_autocommit($this->conexion->conexion, true);
    }

    public function rollback()
    {
        mysqli_rollback($this->conexion->conexion);
        mysqli_autocommit($this->conexion->conexion, true);
    }

    public function getError()
    {
        return $this->datos->Error;
    }

    public function getErrorMsg()
    {
        return $this->datos->MsgError;
    }

    public function softDelete($table, $idField, $idValue, $stateField, $deletedValue = 'I')
    {
        return $this->update($table, [$stateField => $deletedValue], $idField, $idValue);
    }

    public function insertBatch($table, $rows)
    {
        if (empty($rows)) return true;
        $this->beginTransaction();
        $ids = [];
        foreach ($rows as $row) {
            $id = $this->insert($table, $row);
            if ($id === false) {
                $this->rollback();
                return false;
            }
            $ids[] = $id;
        }
        $this->commit();
        return $ids;
    }
}
