<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class TareaClassTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\TareaClass::class)) {
            require_once __DIR__ . '/../../classes/Tarea.php';
        }
    }

    private function createMockDatos(array $overrides = []): object
    {
        $mock = new class {
            public $Error = 0;
            public $MsgError = '';
            public $calledMethods = [];
            public $mockData = [];

            public function getArrayConsulta($id, $params, $con)
            {
                $this->calledMethods[] = ['getArrayConsulta', $id, $params];
                return $this->mockData['arrays'][$id] ?? [];
            }

            public function getRowConsulta($id, $params, $con)
            {
                $this->calledMethods[] = ['getRowConsulta', $id, $params];
                return $this->mockData['rows'][$id] ?? null;
            }

            public function operacionobBD($id, $params, $con)
            {
                $this->calledMethods[] = ['operacionobBD', $id, $params];
                if (isset($this->mockData['operacionErrors'][$id])) {
                    $this->Error = $this->mockData['operacionErrors'][$id];
                }
            }

            public function insercionid($con)
            {
                return $this->mockData['insertId'] ?? 1;
            }

            public function echoJson($data)
            {
                $this->calledMethods[] = ['echoJson', $data];
                echo json_encode($data);
            }
        };

        if (isset($overrides['mockData'])) {
            $mock->mockData = $overrides['mockData'];
        }
        if (isset($overrides['Error'])) {
            $mock->Error = $overrides['Error'];
        }
        if (isset($overrides['MsgError'])) {
            $mock->MsgError = $overrides['MsgError'];
        }
        return $mock;
    }

    private function createMockConexion(): object
    {
        return new class { public $conexion = null; };
    }

    public function testCrearReturnsSuccessOnValidData(): void
    {
        $datos = $this->createMockDatos(['mockData' => ['insertId' => 42]]);
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->crear([
            'Tar_Titulo' => 'Test Task',
            'Tar_Descripcion' => 'Description',
            'Tar_Prioridad' => 'Alta',
            'Tar_Fecha_Inicio' => '2024-01-01',
            'Tar_Fecha_Fin' => '2024-01-31',
            'Tar_Estado' => 'Pendiente',
            'Usu_Creador' => 1,
            'Emp_Cod' => 1,
        ]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['Tar_Cod']);
    }

    public function testCrearDefaultPriorityIsMedia(): void
    {
        $datos = $this->createMockDatos();
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->crear([
            'Tar_Titulo' => 'Test',
            'Emp_Cod' => 1,
        ]);
        ob_end_clean();

        $call = $datos->calledMethods[0];
        $this->assertEquals('Media', $call[2]['Tar_Prioridad']);
    }

    public function testCrearInvalidPriorityDefaultsToMedia(): void
    {
        $datos = $this->createMockDatos();
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->crear([
            'Tar_Titulo' => 'Test',
            'Tar_Prioridad' => 'InvalidPriority',
            'Emp_Cod' => 1,
        ]);
        ob_end_clean();

        $call = $datos->calledMethods[0];
        $this->assertEquals('Media', $call[2]['Tar_Prioridad']);
    }

    public function testCrearValidPrioritiesAccepted(): void
    {
        foreach (['Alta', 'Media', 'Baja'] as $priority) {
            $datos = $this->createMockDatos();
            $obj = new \TareaClass($this->createMockConexion(), $datos);

            ob_start();
            $obj->crear([
                'Tar_Titulo' => 'Test',
                'Tar_Prioridad' => $priority,
                'Emp_Cod' => 1,
            ]);
            ob_end_clean();

            $call = $datos->calledMethods[0];
            $this->assertEquals($priority, $call[2]['Tar_Prioridad']);
        }
    }

    public function testCrearReturnsErrorOnFailure(): void
    {
        $datos = $this->createMockDatos([
            'mockData' => ['operacionErrors' => [1 => 1]],
            'MsgError' => 'DB Error',
        ]);
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->crear([
            'Tar_Titulo' => 'Test',
            'Emp_Cod' => 1,
        ]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertFalse($result['success']);
        $this->assertEquals('DB Error', $result['message']);
    }

    public function testModificarReturnsSuccess(): void
    {
        $datos = $this->createMockDatos();
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->modificar([
            'Tar_Cod' => 1,
            'Tar_Titulo' => 'Updated',
            'Tar_Prioridad' => 'Baja',
            'Tar_Estado' => 'En Progreso',
        ]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['success']);
    }

    public function testEliminarReturnsSuccess(): void
    {
        $datos = $this->createMockDatos();
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->eliminar(['Tar_Cod' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['success']);
    }

    public function testObtenerReturnsDataTask(): void
    {
        $tasks = [['Tar_Cod' => 1, 'Tar_Titulo' => 'Task 1'], ['Tar_Cod' => 2, 'Tar_Titulo' => 'Task 2']];
        $datos = $this->createMockDatos(['mockData' => ['arrays' => [9 => $tasks]]]);
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->obtener(['Emp_Cod' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['status']);
        $this->assertCount(2, $result['data']);
    }

    public function testAsignarReturnsErrorIfAlreadyAssigned(): void
    {
        $datos = $this->createMockDatos(['mockData' => ['rows' => [12 => ['Tar_Cod' => 1, 'Per_Cod' => 1]]]]);
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->asignar(['Tar_Cod' => 1, 'Per_Cod' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ya está asignada', $result['message']);
    }

    public function testGuardarAvanceRejectsZeroTaskId(): void
    {
        $datos = $this->createMockDatos();
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->guardarAvance(['Tar_Cod' => 0, 'Ava_Porcentaje' => 50]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Debe indicar la tarea', $result['message']);
    }

    public function testGuardarAvanceClampsPercentageTo100(): void
    {
        $datos = $this->createMockDatos();
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->guardarAvance(['Tar_Cod' => 1, 'Ava_Porcentaje' => 150, 'Usu_Creador' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['success']);
        $operacionCall = null;
        foreach ($datos->calledMethods as $call) {
            if ($call[0] === 'operacionobBD' && $call[1] === 3) {
                $operacionCall = $call;
                break;
            }
        }
        $this->assertNotNull($operacionCall);
        $this->assertEquals(100, $operacionCall[2]['Ava_Porcentaje']);
    }

    public function testGuardarAvanceClampsPercentageToZero(): void
    {
        $datos = $this->createMockDatos();
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->guardarAvance(['Tar_Cod' => 1, 'Ava_Porcentaje' => -20, 'Usu_Creador' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['success']);
        $operacionCall = null;
        foreach ($datos->calledMethods as $call) {
            if ($call[0] === 'operacionobBD' && $call[1] === 3) {
                $operacionCall = $call;
                break;
            }
        }
        $this->assertNotNull($operacionCall);
        $this->assertEquals(0, $operacionCall[2]['Ava_Porcentaje']);
    }

    public function testIndicadoresCalculatesPercentages(): void
    {
        $datos = $this->createMockDatos(['mockData' => ['rows' => [8 => [
            'Total_Tareas' => '10',
            'Completadas' => '6',
            'Atrasadas' => '2',
        ]]]]);
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->indicadores(['Emp_Cod' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['status']);
        $this->assertEquals(10, $result['Total_Tareas']);
        $this->assertEquals(6, $result['Completadas']);
        $this->assertEquals(60.0, $result['Pct_Completadas']);
        $this->assertEquals(20.0, $result['Pct_Atrasadas']);
    }

    public function testIndicadoresZeroTotalReturnsZero(): void
    {
        $datos = $this->createMockDatos(['mockData' => ['rows' => [8 => [
            'Total_Tareas' => '0',
            'Completadas' => '0',
            'Atrasadas' => '0',
        ]]]]);
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->indicadores(['Emp_Cod' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertEquals(0.0, $result['Pct_Completadas']);
        $this->assertEquals(0.0, $result['Pct_Atrasadas']);
    }

    public function testMisTareasReturnsSinVinculoIfNoPerson(): void
    {
        $datos = $this->createMockDatos(['mockData' => ['rows' => [16 => null]]]);
        $obj = new \TareaClass($this->createMockConexion(), $datos);

        ob_start();
        $obj->misTareas(['Emp_Cod' => 1, 'Usu_Creador' => 1]);
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertTrue($result['sin_vinculo']);
        $this->assertEmpty($result['data']);
    }
}
