<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class DataAPITest extends TestCase
{
    private function createMockDataAPI(array $overrides = []): object
    {
        $mockDatos = new class {
            public $Error = 0;
            public $MsgError = '';
            public $lastSql = '';
            public $rows = [];
            public $singleRow = null;
            public $insertId = 1;

            public function consulta($sql, $con)
            {
                $this->lastSql = $sql;
            }

            public function getArrayConsultaSql($sql, $con)
            {
                $this->lastSql = $sql;
                return $this->rows;
            }

            public function getRowConsultaSql($sql, $con)
            {
                $this->lastSql = $sql;
                return $this->singleRow;
            }

            public function insercionid($con)
            {
                return $this->insertId;
            }
        };

        $mockConexion = new class {
            public $conexion = null;
            public $BaseDatos = 'test_db';
        };

        $api = new class($mockConexion, $mockDatos) {
            protected $conexion;
            protected $datos;

            public function __construct($conexion, $datos)
            {
                $this->conexion = $conexion;
                $this->datos = $datos;
            }

            public function getDatos() { return $this->datos; }
            public function getConexion() { return $this->conexion; }

            public function escape($value)
            {
                if (is_null($value)) return 'NULL';
                return "'" . addslashes((string)$value) . "'";
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

            public function softDelete($table, $idField, $idValue, $stateField, $deletedValue = 'I')
            {
                return $this->update($table, [$stateField => $deletedValue], $idField, $idValue);
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

            public function insertBatch($table, $rows)
            {
                if (empty($rows)) return true;
                $ids = [];
                foreach ($rows as $row) {
                    $id = $this->insert($table, $row);
                    if ($id === false) {
                        return false;
                    }
                    $ids[] = $id;
                }
                return $ids;
            }
        };

        foreach ($overrides as $key => $value) {
            $api->$key = $value;
        }
        return $api;
    }

    public function testEscapeReturnsQuotedString(): void
    {
        $api = $this->createMockDataAPI();
        $this->assertEquals("'hello world'", $api->escape('hello world'));
    }

    public function testEscapeReturnsNullForNullValue(): void
    {
        $api = $this->createMockDataAPI();
        $this->assertEquals('NULL', $api->escape(null));
    }

    public function testEscapeEscapesQuotes(): void
    {
        $api = $this->createMockDataAPI();
        $result = $api->escape("it's a test");
        $this->assertStringContainsString("'", $result);
        $this->assertStringNotContainsString("it's", $result);
    }

    public function testListGeneratesCorrectSql(): void
    {
        $api = $this->createMockDataAPI();
        $api->list('users', [], '', 10, 0);
        $this->assertStringContainsString('SELECT * FROM `users`', $api->getDatos()->lastSql);
        $this->assertStringContainsString('LIMIT 0, 10', $api->getDatos()->lastSql);
    }

    public function testListWithWhereClause(): void
    {
        $api = $this->createMockDataAPI();
        $api->list('users', ['name' => 'John', 'age' => 30]);
        $sql = $api->getDatos()->lastSql;
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('`name`', $sql);
        $this->assertStringContainsString('`age`', $sql);
    }

    public function testListWithOrderBy(): void
    {
        $api = $this->createMockDataAPI();
        $api->list('users', [], 'name ASC');
        $this->assertStringContainsString('ORDER BY name ASC', $api->getDatos()->lastSql);
    }

    public function testListSanitizesOrderBySpecialChars(): void
    {
        $api = $this->createMockDataAPI();
        $api->list('users', [], "name;SELECT*1");
        $sql = $api->getDatos()->lastSql;
        $this->assertStringNotContainsString("name;SELECT", $sql);
        $this->assertStringContainsString('ORDER BY nameSELECT1', $sql);
    }

    public function testGetByIdGeneratesCorrectSql(): void
    {
        $api = $this->createMockDataAPI();
        $api->getById('users', 'id', 42);
        $this->assertStringContainsString("SELECT * FROM `users` WHERE `id` = '42' LIMIT 1", $api->getDatos()->lastSql);
    }

    public function testInsertReturnsId(): void
    {
        $api = $this->createMockDataAPI();
        $result = $api->insert('users', ['name' => 'John', 'email' => 'john@test.com']);
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('INSERT INTO `users`', $api->getDatos()->lastSql);
    }

    public function testInsertReturnsFalseOnError(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->Error = 1;
        $result = $api->insert('users', ['name' => 'John']);
        $this->assertFalse($result);
    }

    public function testUpdateReturnsTrue(): void
    {
        $api = $this->createMockDataAPI();
        $result = $api->update('users', ['name' => 'Jane'], 'id', 1);
        $this->assertTrue($result);
        $this->assertStringContainsString('UPDATE `users` SET', $api->getDatos()->lastSql);
    }

    public function testUpdateReturnsFalseOnError(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->Error = 1;
        $result = $api->update('users', ['name' => 'Jane'], 'id', 1);
        $this->assertFalse($result);
    }

    public function testDeleteReturnsTrue(): void
    {
        $api = $this->createMockDataAPI();
        $result = $api->delete('users', 'id', 1);
        $this->assertTrue($result);
        $this->assertStringContainsString('DELETE FROM `users`', $api->getDatos()->lastSql);
    }

    public function testCountReturnsZeroWhenNoRow(): void
    {
        $api = $this->createMockDataAPI();
        $this->assertEquals(0, $api->count('users'));
    }

    public function testCountReturnsTotal(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->singleRow = ['total' => '42'];
        $this->assertEquals(42, $api->count('users'));
    }

    public function testExistsReturnsTrue(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->singleRow = ['total' => '1'];
        $this->assertTrue($api->exists('users', ['id' => 1]));
    }

    public function testExistsReturnsFalse(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->singleRow = ['total' => '0'];
        $this->assertFalse($api->exists('users', ['id' => 999]));
    }

    public function testSoftDeleteCallsUpdate(): void
    {
        $api = $this->createMockDataAPI();
        $result = $api->softDelete('users', 'id', 1, 'estado', 'I');
        $this->assertTrue($result);
        $this->assertStringContainsString('UPDATE `users` SET', $api->getDatos()->lastSql);
        $this->assertStringContainsString("`estado` = 'I'", $api->getDatos()->lastSql);
    }

    public function testListPagedReturnsPaginatedStructure(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->singleRow = ['total' => '100'];
        $result = $api->listPaged('users', [], '', 2, 25);

        $this->assertEquals(100, $result['total']);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(25, $result['perPage']);
        $this->assertEquals(4, $result['pages']); // ceil(100/25)
    }

    public function testListPagedClampsInvalidValues(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->singleRow = ['total' => '0'];
        $result = $api->listPaged('users', [], '', 0, 0);

        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['perPage']);
    }

    public function testInsertBatchReturnsArrayOfIds(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->insertId = 10;
        $result = $api->insertBatch('users', [
            ['name' => 'A'],
            ['name' => 'B'],
        ]);

        $this->assertEquals([10, 10], $result);
    }

    public function testInsertBatchReturnsTrueForEmptyArray(): void
    {
        $api = $this->createMockDataAPI();
        $this->assertTrue($api->insertBatch('users', []));
    }

    public function testInsertBatchReturnsFalseOnError(): void
    {
        $api = $this->createMockDataAPI();
        $api->getDatos()->Error = 1;
        $result = $api->insertBatch('users', [['name' => 'A']]);
        $this->assertFalse($result);
    }
}
