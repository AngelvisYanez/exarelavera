<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class ComprasModelTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\compras::class)) {
            @require_once __DIR__ . '/../../MODELS/compras.php';
        }
        $_SESSION['Ses_Emp_Cod'] = 999;
        $_SESSION['Ses_Suc_Cod'] = 1;
    }

    public function testSetTotalesAddsOptimizedJoins(): void
    {
        $model = new \compras();
        $sql = (string)$model->sqlByNombre('setTotales', $model->select(), []);

        $this->assertStringContainsString('INNER JOIN det_compra ON det_compra.Cop_Cod=compras.Cop_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN iva ON iva.Iva_Cod=det_compra.Iva_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN compr_auto ON compr_auto.Cop_Cod = compras.Cop_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN comprobantes ON comprobantes.Com_Cod = compr_auto.Com_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod', $sql);
        $this->assertStringContainsString('AS pagos_resumen ON pagos_resumen.Cop_Cod = compras.Cop_Cod', $sql);
        $this->assertStringContainsString('FROM ccpp_pagar', $sql);
        $this->assertStringContainsString('AS ret_totales ON ret_totales.Ret_Cod = retencion.Ret_Cod', $sql);
        $this->assertStringContainsString('FROM det_retenc', $sql);
        $this->assertStringContainsString('INNER JOIN renta_iva ON renta_iva.Ren_Cod = det_retenc.Ren_Cod', $sql);
        $this->assertStringContainsString('ret_totales.Tot_Renta', $sql);
        $this->assertStringContainsString('ret_totales.Tot_Iva', $sql);
        $this->assertStringContainsString('AS Com_Codigo', $sql);
        $this->assertStringContainsString('AS Ret_Fec', $sql);
        $this->assertStringContainsString('AS Autorizacion', $sql);
        $this->assertStringContainsString('GROUP BY compras.Cop_Cod', $sql);
        $this->assertStringContainsString('ORDER BY Iva_Por ASC', $sql);

        $this->assertSame(1, substr_count($sql, 'LEFT JOIN compr_auto'));
        $this->assertSame(1, substr_count($sql, 'LEFT JOIN comprobantes'));
    }

    public function testSetTotalesAgrPrvGroupsByProveedor(): void
    {
        $model = new \compras();
        $sql = (string)$model->sqlByNombre('setTotales', $model->select(), ['CustomGroupBy' => 'Agr_Prv']);

        $this->assertStringContainsString('GROUP BY compras.Prv_Cod', $sql);
    }

    public function testSetTotalesGlobalesWrapsInDerivedTable(): void
    {
        $model = new \compras();
        $sql = (string)$model->sqlByNombre('setTotalesGlobales', $model->select(), []);

        $this->assertStringContainsString('AS tbl', $sql);
        $this->assertStringContainsString('IF(Tic_Sri=4,-1,1)', $sql);
        $this->assertStringContainsString('GROUP BY compras.Cop_Cod', $sql);
    }

    public function testSelectBasicBuildsStandardJoins(): void
    {
        $sql = (string)(new \compras())->_selectBasic();

        $this->assertStringContainsString('FROM compras', $sql);
        $this->assertStringContainsString('INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=compras.Tic_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN sustento ON sustento.Tri_Cod=compras.Tri_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN tipopagocom ON tipopagocom.Tpc_Cod=compras.Tpc_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod', $sql);
        $this->assertStringContainsString('AS Proveedor', $sql);
    }

    public function testSqlByNumeroCase1CountsCuentasPorPagar(): void
    {
        $sql = (string)(new \compras())->sqlByNumero(1, [7]);

        $this->assertSame("SELECT COUNT(*) as total FROM ccpp_pagar WHERE Cop_Cod = '7';", $sql);
    }

    public function testSqlByNumeroCase2CountsAdquisicionActiva(): void
    {
        $sql = (string)(new \compras())->sqlByNumero(2, [7]);

        $this->assertStringContainsString('count(compras.Cop_Cod) as Activo', $sql);
        $this->assertStringContainsString('INNER JOIN adquisicio ON det_compra.Adq_Cod = adquisicio.Adq_Cod', $sql);
        $this->assertStringContainsString('compras.Cop_Cod = 7', $sql);
    }

    public function testSqlByNumeroThrowsOnUnknownId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la sql numero 99!');

        (new \compras())->sqlByNumero(99, []);
    }
}
