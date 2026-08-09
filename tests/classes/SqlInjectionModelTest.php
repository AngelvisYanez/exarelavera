<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class SqlInjectionModelTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION['Ses_Emp_Cod'] = 999;
        $_SESSION['Ses_Suc_Cod'] = 1;
    }

    /**
     * [model, opcion de busqueda usada en _selectBasicGrid]
     */
    public static function vulnerableModels(): array
    {
        return [
            'persona'      => ['persona', 'n'],
            'cliente'      => ['cliente', 'n'],
            'proveedore'   => ['proveedore', 'n'],
            'personal'     => ['personal', 'n'],
            'productor'    => ['productor', 'n'],
            'productor_bana' => ['productor_bana', 'n'],
            'conciliacion_bancaria' => ['conciliacion_bancaria', 'n'],
            'conciliacion_banc_asientos' => ['conciliacion_banc_asientos', 'n'],
            'labores'      => ['labores', 'n'],
            'renta_iva'    => ['renta_iva', 'n'],
            'det_plan'     => ['det_plan', 'n'],
            'proformas'    => ['proformas', 'h'],
            'requisiciones' => ['requisiciones', 'h'],
            'requisitores' => ['requisitores', 'h'],
            'actividad_labor' => ['actividad_labor', 'lbr'],
            'retcre_vta'   => ['retcre_vta', 'c'],
            'usuarios'     => ['usuarios', 'd'],
            'exportacion_container' => ['exportacion_container', 'b'],
        ];
    }

    public static function injectionPayloads(): array
    {
        return [
            'string_breakout' => ["x' OR '1'='1"],
            'union_select'    => ["' UNION SELECT 1,2,3-- "],
            'backslash'       => ["\\'; DROP TABLE persona;-- "],
            'null_byte'       => ["' OR 1=1\x00"],
        ];
    }

    /**
     * @dataProvider vulnerableModels
     */
    public function testSelectBasicGridIsSqlInjectionSafe(string $model, string $opcion): void
    {
        $class = '\\' . $model;
        if (!class_exists($class)) {
            @require_once __DIR__ . '/../../MODELS/' . $model . '.php';
        }
        $this->assertTrue(class_exists($class), "Modelo $model no cargado");

        $payload = "x' OR '1'='1";
        $modelObj = new $class();
        $sql = (string)$modelObj->_selectBasicGrid([
            'op_opciones' => $opcion,
            'search'      => $payload,
            'Cod_Bus'     => $payload,
        ]);

        $this->assertStringNotContainsString($payload, $sql, "Payload crudo presente en SQL de $model");
        $this->assertStringNotContainsString("' OR '1'='1", $sql);
    }

    /**
     * @dataProvider injectionPayloads
     */
    public function testPersonaSearchEscapesAllPayloads(string $payload): void
    {
        $model = new \persona();
        $sql = (string)$model->_selectBasicGrid([
            'op_opciones' => 'n',
            'search'      => $payload,
        ]);

        $escaped = addcslashes($payload, "\000\n\r\\'\"\032");
        $this->assertStringContainsString("LIKE '%" . $escaped . "%'", $sql, 'Payload no escapado en SQL (inyeccion)');
    }

    public function testPersonaCedulaBranchStillUsesPlaceholder(): void
    {
        $model = new \persona();
        $payload = "1001'; DROP TABLE persona;--";
        $sql = (string)$model->_selectBasicGrid([
            'op_opciones' => 'c',
            'search'      => $payload,
        ]);

        $this->assertStringContainsString('Prs_Ced=', $sql);
        $this->assertStringNotContainsString($payload, $sql, 'Payload crudo presente');
    }

    public function testDetPlanBothBranchesUsePlaceholders(): void
    {
        $model = new \det_plan();
        $payload = "x' OR '1'='1";

        $sqlCdc = (string)$model->_selectBasicGrid(['op_opciones' => 'c', 'search' => $payload]);
        $sqlDes = (string)$model->_selectBasicGrid(['op_opciones' => 'n', 'search' => $payload]);

        $this->assertStringNotContainsString($payload, $sqlCdc);
        $this->assertStringNotContainsString($payload, $sqlDes);
    }

    public function testRentaIvaBothBranchesUsePlaceholders(): void
    {
        $model = new \renta_iva();
        $payload = "x' OR '1'='1";

        $sqlSri = (string)$model->_selectBasicGrid(['op_opciones' => 'c', 'search' => $payload]);
        $sqlCon = (string)$model->_selectBasicGrid(['op_opciones' => 'n', 'search' => $payload]);

        $this->assertStringNotContainsString($payload, $sqlSri);
        $this->assertStringNotContainsString($payload, $sqlCon);
    }

    public function testActividadLaborFechasUsePlaceholders(): void
    {
        $model = new \actividad_labor();
        $payload = "2024-01-01'; DROP TABLE persona;--";
        $sql = (string)$model->_selectBasicGrid([
            'op_opciones' => 'fch',
            'Fec_Ini'     => $payload,
            'Fec_Fin'     => '2024-01-31',
        ]);

        $this->assertStringContainsString('BETWEEN', $sql);
        $this->assertStringNotContainsString($payload, $sql, 'Payload crudo presente');
    }

    public function testActividadLaborFuncionAndLaborBranchesEscaped(): void
    {
        $model = new \actividad_labor();
        $payload = "x' OR '1'='1";

        $sqlFnc = (string)$model->_selectBasicGrid(['op_opciones' => 'fnc', 'Cod_Bus' => $payload]);
        $sqlLbr = (string)$model->_selectBasicGrid(['op_opciones' => 'lbr', 'Cod_Bus' => $payload]);

        $this->assertStringNotContainsString($payload, $sqlFnc);
        $this->assertStringNotContainsString($payload, $sqlLbr);
    }

    public function testFechasGridModelsUsePlaceholders(): void
    {
        foreach (['proformas', 'requisiciones', 'requisitores'] as $model) {
            $class = '\\' . $model;
            $modelObj = new $class();
            $sql = (string)$modelObj->_selectBasicGrid([
                'op_opciones' => 'fch',
                'desde'       => "2024-01-01' OR '1'='1",
                'hasta'       => '2024-01-31',
            ]);

            $this->assertStringNotContainsString("'1'='1", $sql, "Fecha cruda en SQL de $model");
        }
    }
}
