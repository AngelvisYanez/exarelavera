<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class FacturacionElectronicaTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\FacturacionElectronicaClass::class)) {
            require_once __DIR__ . '/../../classes/FacturacionElectronica.php';
        }
    }

    private function createMockDatos(array $overrides = []): object
    {
        $mock = new class {
            public $Error = 0;
            public $MsgError = '';
            public $calledMethods = [];
            public $mockData = [];

            public function getRowConsultaSql($sql, $con)
            {
                $this->calledMethods[] = ['getRowConsultaSql', $sql];
                return $this->mockData['row'] ?? ['total' => '0'];
            }

            public function getArrayConsultaSql($sql, $con)
            {
                $this->calledMethods[] = ['getArrayConsultaSql', $sql];
                return $this->mockData['rows'] ?? [];
            }

            public function utf8_change_param(&$data)
            {
                $this->calledMethods[] = ['utf8_change_param'];
            }

            public function echoJson($data)
            {
                $this->calledMethods[] = ['echoJson', $data];
                echo json_encode($data);
            }

            public function getMyCon($con)
            {
                return null;
            }
        };

        if (isset($overrides['mockData'])) {
            $mock->mockData = $overrides['mockData'];
        }
        return $mock;
    }

    private function createMockConexion(): object
    {
        return new class {
            public $conexion = null;
        };
    }

    public function testSortClauseWithValidFieldAndDesc(): void
    {
        $ref = new \ReflectionClass(\FacturacionElectronicaClass::class);
        $method = $ref->getMethod('sortClause');
        $method->setAccessible(true);

        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $this->createMockDatos());
        $allowed = ['Vet_Num' => 'v.Vet_Num', 'Vet_Sys' => 'v.Vet_Sys'];

        $result = $method->invoke($obj, 'Vet_Num', 'DESC', $allowed, 'v.Vet_Sys');

        $this->assertEquals('ORDER BY v.Vet_Num DESC', $result);
    }

    public function testSortClauseWithValidFieldAndAsc(): void
    {
        $ref = new \ReflectionClass(\FacturacionElectronicaClass::class);
        $method = $ref->getMethod('sortClause');
        $method->setAccessible(true);

        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $this->createMockDatos());
        $allowed = ['Vet_Num' => 'v.Vet_Num'];

        $result = $method->invoke($obj, 'Vet_Num', 'ASC', $allowed, 'v.Vet_Sys');

        $this->assertEquals('ORDER BY v.Vet_Num ASC', $result);
    }

    public function testSortClauseWithInvalidOrderDefaultsToDesc(): void
    {
        $ref = new \ReflectionClass(\FacturacionElectronicaClass::class);
        $method = $ref->getMethod('sortClause');
        $method->setAccessible(true);

        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $this->createMockDatos());
        $allowed = ['Vet_Num' => 'v.Vet_Num'];

        $result = $method->invoke($obj, 'Vet_Num', 'INVALID', $allowed, 'v.Vet_Sys');

        $this->assertEquals('ORDER BY v.Vet_Num DESC', $result);
    }

    public function testSortClauseWithUnknownFieldFallsBackToDefault(): void
    {
        $ref = new \ReflectionClass(\FacturacionElectronicaClass::class);
        $method = $ref->getMethod('sortClause');
        $method->setAccessible(true);

        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $this->createMockDatos());
        $allowed = ['Vet_Num' => 'v.Vet_Num'];

        $result = $method->invoke($obj, 'UnknownField', 'ASC', $allowed, 'v.Vet_Sys');

        $this->assertEquals('ORDER BY v.Vet_Sys DESC', $result);
    }

    public function testGetComprobantesReturnsPaginatedData(): void
    {
        $datos = $this->createMockDatos([
            'mockData' => [
                'row' => ['total' => '25'],
                'rows' => [['Vet_Cod' => 1], ['Vet_Cod' => 2]],
            ],
        ]);
        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->getComprobantes(['Emp_Cod' => 1, 'page' => 1, 'rows' => 10]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['status']);
        $this->assertEquals(25, $result['data']['records']);
        $this->assertEquals(3, $result['data']['total']); // ceil(25/10)
        $this->assertEquals(1, $result['data']['page']);
    }

    public function testGetComprobantesWithSearchFilter(): void
    {
        $datos = $this->createMockDatos([
            'mockData' => ['row' => ['total' => '0'], 'rows' => []],
        ]);
        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->getComprobantes(['Emp_Cod' => 1, 'search' => 'test search', 'page' => 1]);
        ob_end_clean();

        $sqlCall = $datos->calledMethods[1];
        $this->assertStringContainsString("LIKE '%test search%'", $sqlCall[1]);
    }

    public function testGetComprobantesWithDateRange(): void
    {
        $datos = $this->createMockDatos([
            'mockData' => ['row' => ['total' => '0'], 'rows' => []],
        ]);
        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->getComprobantes([
            'Emp_Cod' => 1,
            'fecha_desde' => '2024-01-01',
            'fecha_hasta' => '2024-12-31',
            'page' => 1,
        ]);
        ob_end_clean();

        $sqlCall = $datos->calledMethods[1];
        $this->assertStringContainsString(">= '2024-01-01 00:00:00'", $sqlCall[1]);
        $this->assertStringContainsString("<= '2024-12-31 23:59:59'", $sqlCall[1]);
    }

    public function testGetComprobantesElectronicosFilter(): void
    {
        $datos = $this->createMockDatos([
            'mockData' => ['row' => ['total' => '0'], 'rows' => []],
        ]);
        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->getComprobantes(['Emp_Cod' => 1, 'estado' => 'electronicos', 'page' => 1]);
        ob_end_clean();

        $sqlCall = $datos->calledMethods[1];
        $this->assertStringContainsString("Vet_Aut = 'S'", $sqlCall[1]);
    }

    public function testGetResumenReturnsCounts(): void
    {
        $callCount = 0;
        $datos = new class {
            public $calledMethods = [];
            public function getRowConsultaSql($sql, $con)
            {
                $this->calledMethods[] = $sql;
                $n = count($this->calledMethods);
                if ($n <= 2) {
                    return ['total' => '10', 'electronicos' => '7'];
                }
                return ['total' => '5'];
            }
            public function utf8_change_param(&$data) {}
            public function echoJson($data) { echo json_encode($data); }
            public function getMyCon($con) { return null; }
        };
        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->getResumen(['Emp_Cod' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['status']);
        $this->assertEquals(10, $result['data']['facturas']['total']);
        $this->assertEquals(7, $result['data']['facturas']['electronicos']);
        $this->assertEquals(5, $result['data']['comprobantes']['total']);
    }

    public function testGetRetencionesWithPagination(): void
    {
        $datos = $this->createMockDatos([
            'mockData' => [
                'row' => ['total' => '100'],
                'rows' => array_fill(0, 50, ['Ret_Cod' => 1]),
            ],
        ]);
        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->getRetenciones(['Emp_Cod' => 1, 'page' => 2, 'rows' => 50]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['status']);
        $this->assertEquals(100, $result['data']['records']);
        $this->assertEquals(2, $result['data']['total']); // ceil(100/50)
        $this->assertEquals(2, $result['data']['page']);
    }

    public function testGetComprobantesContablesDefaultValues(): void
    {
        $datos = $this->createMockDatos([
            'mockData' => ['row' => ['total' => '0'], 'rows' => []],
        ]);
        $obj = new \FacturacionElectronicaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->getComprobantesContables(['Emp_Cod' => 0]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['status']);
        $this->assertEquals(0, $result['data']['records']);
    }
}
