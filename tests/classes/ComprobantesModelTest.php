<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class ComprobantesModelTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\comprobantes::class)) {
            @require_once __DIR__ . '/../../MODELS/comprobantes.php';
        }
        $_SESSION['Ses_Usu_Cod'] = 42;
    }

    public function testGetComprobanteByCopCodBuildsSql(): void
    {
        $sql = (string)(new \comprobantes())->getComprobanteByCopCod(100);

        $this->assertStringContainsString('FROM comprobantes', $sql);
        $this->assertStringContainsString('INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN compr_auto ON comprobantes.Com_Cod = compr_auto.Com_Cod', $sql);
        $this->assertStringContainsString('WHERE (compr_auto.Cop_Cod=100)', $sql);
        $this->assertStringContainsString('AS Com_Codigo', $sql);
    }

    public function testGetComprobanteByVetCodBuildsSql(): void
    {
        $sql = (string)(new \comprobantes())->getComprobanteByVetCod(7);

        $this->assertStringContainsString('FROM comprobantes', $sql);
        $this->assertStringContainsString('comprobantes.Com_Est', $sql);
        $this->assertStringContainsString('INNER JOIN ventas_compr ON comprobantes.Com_Cod = ventas_compr.Com_Cod', $sql);
        $this->assertStringContainsString('WHERE (Vet_Cod=7)', $sql);
    }

    public function testGetMayorBuildsLedgerSql(): void
    {
        $sql = (string)(new \comprobantes())->getMayor(['Pec_Cod' => 5]);

        $this->assertStringContainsString('FROM det_plan', $sql);
        $this->assertStringContainsString('AS tabla ON det_plan.Pld_Cod=tabla.Pld_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod', $sql);
        $this->assertStringContainsString('SUM(Debe) AS Debe', $sql);
        $this->assertStringContainsString('SUM(Haber) AS Haber', $sql);
        $this->assertStringContainsString('WHERE (perio_cont.Pec_Cod=5)', $sql);
        $this->assertStringContainsString('GROUP BY Pld_Cod', $sql);
    }

    public function testSqlByNumeroCase4BuildsVentasDiarioQuery(): void
    {
        $sql = (string)(new \comprobantes())->sqlByNumero(4, [
            'Emp_Cod' => 1,
            'Suc_Cod' => 2,
            'Fec_Ini' => '2024-01-01',
            'Fec_Fin' => '2024-12-31',
        ]);

        $this->assertStringContainsString('FROM comprobantes', $sql);
        $this->assertStringContainsString('INNER JOIN asientos ON comprobantes.Com_Cod = asientos.Com_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN ventas_costo ON comprobantes.Com_Cod=ventas_costo.Com_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN ventas ON ventas.Vet_Cod=ventas_costo.Vet_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN compras ON compras.Cop_Cod=compr_auto.Cop_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN persona AS prs_prv ON prs_prv.Prs_Cod=proveedore.Prs_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN persona AS prs_cli ON prs_cli.Prs_Cod=cliente.Prs_Cod', $sql);
        $this->assertStringContainsString('AS Diferencia', $sql);
        $this->assertStringContainsString('AS Doc_Num', $sql);
        $this->assertStringContainsString('AS Persona', $sql);
        $this->assertStringContainsString('comprobantes.*', $sql);
        $this->assertStringContainsString('plan_cuenta.Emp_Cod=1', $sql);
        $this->assertStringContainsString('sucursal.Suc_Cod = 2', $sql);
        $this->assertStringContainsString('Com_Fec BETWEEN \'2024-01-01 00:00:00\' AND \'2024-12-31 23:59:59\'', $sql);
        $this->assertStringContainsString("tipo_asien.Tia_Abr='CV'", $sql);
        $this->assertStringContainsString('GROUP BY comprobantes.Com_Cod', $sql);
        $this->assertStringContainsString('ORDER BY Com_Fec', $sql);
        $this->assertStringNotContainsString('ventas_compr', $sql);
        $this->assertStringNotContainsString('tip_com_cop', $sql);
        $this->assertStringNotContainsString('tip_com_vet', $sql);
    }

    public function testSqlByNumeroCase6BuildsAsientoDetalle(): void
    {
        $sql = (string)(new \comprobantes())->sqlByNumero(6, [7]);

        $this->assertStringContainsString('FROM comprobantes', $sql);
        $this->assertStringContainsString('INNER JOIN asientos ON asientos.Com_Cod = comprobantes.Com_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN det_plan ON det_plan.Pld_Cod = asientos.Pld_Cod', $sql);
        $this->assertStringContainsString('WHERE comprobantes.Com_Cod = 7', $sql);
        $this->assertStringContainsString('ORDER BY Asi_Deh', $sql);
    }

    public function testSqlByNumeroCase5DeletesComprobante(): void
    {
        $sql = (string)(new \comprobantes())->sqlByNumero(5, [7]);

        $this->assertSame('DELETE FROM comprobantes WHERE Com_Cod = 7;', $sql);
    }

    public function testSqlByNumeroCase1AnulaAnticipoProveedor(): void
    {
        $sql = (string)(new \comprobantes())->sqlByNumero(1, ['Com_Cod' => 7]);

        $this->assertStringContainsString('UPDATE comprobantes', $sql);
        $this->assertStringContainsString('INNER JOIN asientos ON (comprobantes.Com_Cod=asientos.Com_Cod)', $sql);
        $this->assertStringContainsString("LEFT JOIN cheques ON (asientos.Asi_Cod=cheques.Asi_Cod AND Che_Est<>'P')", $sql);
        $this->assertStringContainsString("Com_Est='I', Che_Est='I'", $sql);
        $this->assertStringContainsString('WHERE comprobantes.Com_Cod=7;', $sql);
    }

    public function testSqlByNombreDataJoinsClienteProveedorYUsuario(): void
    {
        $sel = (new \comprobantes())->select();
        $sql = (string)(new \comprobantes())->sqlByNombre('data', $sel, null);

        $this->assertStringContainsString('INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN cliente ON cliente.Cli_Cod=comprobantes.Cli_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN persona AS prs_cliente ON prs_cliente.Prs_Cod=cliente.Prs_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN proveedore ON proveedore.Prv_Cod=comprobantes.Prv_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN persona AS prs_proveedore ON prs_proveedore.Prs_Cod=proveedore.Prs_Cod', $sql);
        $this->assertStringContainsString('LEFT JOIN usuarios ON usuarios.Usu_Cod=comprobantes.Usu_Cod', $sql);
        $this->assertStringContainsString('AS Usu_Nom', $sql);
        $this->assertStringContainsString('AS Inv_Nom', $sql);
        $this->assertStringContainsString('AS Inv_Ced', $sql);
    }

    public function testFormatDataInsertSetsSessionUserAndDropsComCod(): void
    {
        $data = (new \comprobantes())->formatData(['Com_Cod' => 5, 'Com_Gen' => 'X', 'Pec_Cod' => 1], 'I');

        $this->assertSame(42, $data['Usu_Cod']);
        $this->assertArrayNotHasKey('Com_Cod', $data);
        $this->assertSame('X', $data['Com_Gen']);
    }

    public function testFormatDataUpdateKeepsComCodAndDropsComGen(): void
    {
        $data = (new \comprobantes())->formatData(['Com_Cod' => 5, 'Com_Gen' => 'X', 'Pec_Cod' => 1], 'U');

        $this->assertSame(42, $data['Usu_Cod']);
        $this->assertArrayNotHasKey('Com_Gen', $data);
        $this->assertSame(5, $data['Com_Cod']);
    }

    public function testSqlByNumeroThrowsOnUnknownId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la sql denominada 99!');

        (new \comprobantes())->sqlByNumero(99, []);
    }

    public function testSqlByNombreThrowsOnUnknownId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la sql denominada foo!');

        (new \comprobantes())->sqlByNombre('foo', (new \comprobantes())->select(), null);
    }
}
