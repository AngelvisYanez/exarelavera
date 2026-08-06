<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class VentasModelTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\ventas::class)) {
            @require_once __DIR__ . '/../../MODELS/ventas.php';
        }
        $_SESSION['Ses_Emp_Cod'] = 999;
        $_SESSION['Ses_Suc_Cod'] = 1;
        $_SESSION['Ses_Prs_Cod'] = 1;
    }

    public function testSetTotalesAddsOptimizedJoins(): void
    {
        $model = new \ventas();
        $sql = (string)$model->sqlByNombre('setTotales', $model->select(), []);

        $this->assertStringContainsString('INNER JOIN ventas_det ON ventas_det.Vet_Cod=ventas.Vet_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN iva ON iva.Iva_Cod=ventas_det.Iva_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN ventas_compr ON ventas_compr.Vet_Cod = ventas.Vet_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN comprobantes ON comprobantes.Com_Cod = ventas_compr.Com_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod', $sql);
        $this->assertStringContainsString('AS pagos_resumen ON pagos_resumen.Vet_Cod = ventas.Vet_Cod', $sql);
        $this->assertStringContainsString('FROM ccpp_cobrar', $sql);
        $this->assertStringContainsString('AS tipos_pago_agr ON tipos_pago_agr.Vet_Cod = ventas.Vet_Cod', $sql);
        $this->assertStringContainsString('GROUP_CONCAT(DISTINCT tipos_pago.Pag_Des', $sql);
        $this->assertStringContainsString('LEFT JOIN renta_iva AS renta_imp ON renta_imp.Ren_Cod = ventas_det.Ren_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN renta_iva AS iva_imp ON iva_imp.Ren_Cod = ventas_det.Ren_Iva', $sql);
        $this->assertStringContainsString('AS Com_Codigo', $sql);
        $this->assertStringContainsString('AS Com_Exi', $sql);
        $this->assertStringContainsString('AS Tot_Renta', $sql);
        $this->assertStringContainsString('AS Tot_Iva', $sql);
        $this->assertStringContainsString('GROUP BY ventas.Vet_Cod', $sql);
        $this->assertStringContainsString('ORDER BY caja_aper.Caj_Fec ASC', $sql);

        $this->assertSame(1, substr_count($sql, 'LEFT JOIN ventas_compr'));
        $this->assertSame(1, substr_count($sql, 'LEFT JOIN comprobantes'));
    }

    public function testSetTotalesSpecialEmpUsesDecimal4(): void
    {
        $_SESSION['Ses_Emp_Cod'] = 534;

        $model = new \ventas();
        $sql = (string)$model->sqlByNombre('setTotales', $model->select(), []);

        $this->assertStringContainsString('AS DECIMAL(20, 4)', $sql);
    }

    public function testSetTotalesHonorsCustomOrderAndGroup(): void
    {
        $model = new \ventas();
        $sql = (string)$model->sqlByNombre('setTotales', $model->select(), [
            'CustomOrderBy' => 'Vet_Num ASC',
            'CustomGroupBy' => 'Agr_Cli',
        ]);

        $this->assertStringContainsString('ORDER BY ventas.Vet_Num ASC', $sql);
        $this->assertStringContainsString('GROUP BY ventas.Cli_Cod', $sql);
    }

    public function testSqlByNumeroCase0BuildsVentasSinCosto(): void
    {
        $sql = (string)(new \ventas())->sqlByNumero(0, [
            'Suc_Cod' => 1,
            'Emp_Cod' => 2,
            'Fec_Ini' => '2024-01-01',
            'Fec_Fin' => '2024-01-31',
        ]);

        $this->assertStringContainsString('FROM ventas', $sql);
        $this->assertStringContainsString('INNER JOIN kardex_ie ON kardex_ie.Vet_Cod = ventas.Vet_Cod', $sql);
        $this->assertStringContainsString('puntos_imp.Suc_Cod=1', $sql);
        $this->assertStringContainsString('cliente.Emp_Cod=2', $sql);
        $this->assertStringContainsString('GROUP BY ventas.Vet_Cod', $sql);
    }

    public function testSqlByNumeroCase1BuildsDetalleProducto(): void
    {
        $sql = (string)(new \ventas())->sqlByNumero(1, [5]);

        $this->assertStringContainsString('FROM producto', $sql);
        $this->assertStringContainsString('INNER JOIN ventas_det ON ventas_det.Pro_Cod = producto.Pro_Cod', $sql);
        $this->assertStringContainsString('where Vet_Cod=5 order by Vet_Int', $sql);
    }

    public function testSqlByNumeroCase2CountsCuentasPorCobrar(): void
    {
        $sql = (string)(new \ventas())->sqlByNumero(2, [5]);

        $this->assertSame("SELECT COUNT(*) as total FROM ccpp_cobrar WHERE Vet_Cod = '5';", $sql);
    }

    public function testSqlByNumeroCase7BuildsGridVentas(): void
    {
        $sql = (string)(new \ventas())->sqlByNumero(7, [
            'limits' => '',
            'op_opciones' => 'c',
            'search' => '123',
            'Emp_Cod' => 2,
            'Tic_Cod' => 1,
            'Cmb_Mes' => '',
            'Pec_Cod' => '',
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'order' => 'ORDER BY ventas.Vet_Cod',
        ]);

        $this->assertStringContainsString("Tic_Sri='0'", $sql);
        $this->assertStringContainsString('puntos_imp.Suc_Cod=1', $sql);
        $this->assertStringContainsString('cliente.Emp_Cod=2', $sql);
        $this->assertStringContainsString("cliente_ven.Prs_Ced LIKE '123%'", $sql);
    }

    public function testGetRetencionVetBuildsSql(): void
    {
        $sql = (string)(new \ventas())->getRetencionVet(5);

        $this->assertStringContainsString('FROM ventas_det', $sql);
        $this->assertStringContainsString('INNER JOIN ventas ON ventas.Vet_Cod = ventas_det.Vet_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN renta_iva AS renta_imp ON renta_imp.Ren_Cod= ventas_det.Ren_Cod', $sql);
        $this->assertStringContainsString('AS Tot_Renta', $sql);
        $this->assertStringContainsString('AS Tot_Iva', $sql);
        $this->assertStringContainsString('WHERE (ventas.Vet_Cod=5)', $sql);
        $this->assertStringContainsString('GROUP BY ventas.Vet_Cod', $sql);
    }

    public function testNextNumBuildsSql(): void
    {
        $sql = (string)(new \ventas())->nextNum([
            'Aut_Sri' => '001',
            'Tic_Cod' => 1,
            'Aut_Ini' => 1,
            'Aut_Fin' => 100,
        ]);

        $this->assertStringContainsString('NOT EXISTS', $sql);
        $this->assertStringContainsString('MIN(t.ventas.Vet_Num)+1', $sql);
        $this->assertStringContainsString("Aut_Sri='001'", $sql);
        $this->assertStringContainsString("AS 'next'", $sql);
    }

    public function testTipoDocPagoBuildsSql(): void
    {
        $sql = (string)(new \ventas())->tipo_doc_pago(5);

        $this->assertStringContainsString('FROM pago_venta', $sql);
        $this->assertStringContainsString('LEFT JOIN tipos_pago ON tipos_pago.Pag_Cod = pago_venta.Pag_Cod', $sql);
        $this->assertStringContainsString('WHERE (pago_venta.Vet_Cod = 5)', $sql);
        $this->assertStringContainsString('GROUP BY pago_venta.Vet_Cod', $sql);
        $this->assertStringContainsString('AS FormasPago', $sql);
    }

    public function testSelectBasicBuildsStandardJoins(): void
    {
        $sql = (string)(new \ventas())->_selectBasic();

        $this->assertStringContainsString('FROM ventas', $sql);
        $this->assertStringContainsString('LEFT JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod', $sql);
        $this->assertStringContainsString('AS Secuencia', $sql);
        $this->assertStringContainsString('AS Autorizacion', $sql);
    }

    public function testIsSummaryWrapsTotalsInDerivedTable(): void
    {
        $model = new \ventas();
        $sel = $model->select();
        $model->sqlByNombre('isSummary', $sel, []);
        $sql = (string)$sel;

        $this->assertStringContainsString('AS tbl', $sql);
        $this->assertStringContainsString("tbl.Tic_Sri = '04'", $sql);
    }

    public function testSqlByNumeroThrowsOnUnknownId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la sql numero 99!');

        (new \ventas())->sqlByNumero(99, []);
    }

    public function testSqlByNombreThrowsOnUnknownId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se ha declarado la funcion foo!');

        (new \ventas())->sqlByNombre('foo', (new \ventas())->select(), []);
    }
}
