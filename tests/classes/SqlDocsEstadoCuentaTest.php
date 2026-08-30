<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class SqlDocsEstadoCuentaTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION['Ses_Emp_Cod'] = '300';
        if (!function_exists('sentencias_estado_cuenta_proveedor')) {
            @require_once __DIR__ . '/../../tesoreria/LOGICA/tes_sql_estado_cuenta.php';
        }
        if (!function_exists('sentencias_doc')) {
            @require_once __DIR__ . '/../../contabilidad/LOGICA/con_sql_docs.php';
        }
    }

    public function testEstadoCuentaFacturasUsesDerivedTableForAbono(): void
    {
        $sql = \sentencias_estado_cuenta_proveedor(2, [
            'Prv_Cod' => '7',
            'txt_fec_ini' => '2024-01-01',
            'txt_fec_fin' => '2024-12-31',
        ]);

        $this->assertStringNotContainsString('(SELECT COALESCE(SUM(det_ccpp_p.Pag_Val), 0) FROM det_ccpp_p WHERE det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)', $sql);
        $this->assertStringContainsString('LEFT JOIN (SELECT Cpp_Cod, SUM(Pag_Val) AS Pag_Sum FROM det_ccpp_p GROUP BY Cpp_Cod) abonos_f ON ccpp_pagar.Cpp_Cod = abonos_f.Cpp_Cod', $sql);
        $this->assertStringContainsString('IFNULL(abonos_f.Pag_Sum, 0) AS Abono_Factura', $sql);
        $this->assertStringContainsString('UNION ALL', $sql);
        $this->assertStringContainsString('ORDER BY Fecha_Emision ASC, Orden_Tipo ASC', $sql);
    }

    public function testEstadoCuentaPagosUsesDerivedTableForChequeInfo(): void
    {
        $sql = \sentencias_estado_cuenta_proveedor(2, [
            'Prv_Cod' => '7',
            'txt_fec_ini' => '2024-01-01',
            'txt_fec_fin' => '2024-12-31',
        ]);

        $this->assertStringNotContainsString('(SELECT banco.Ban_Cue FROM det_ccpp_p d2', $sql);
        $this->assertStringNotContainsString('(SELECT cheques.Che_Fec FROM det_ccpp_p d2', $sql);
        $this->assertStringContainsString('LEFT JOIN (SELECT d2.Com_Cod, MIN(cheques.Che_Fec) AS Che_Fec, MIN(banco.Ban_Cue) AS Ban_Cue', $sql);
        $this->assertStringContainsString('cheq_info.Ban_Cue AS Cuenta_Bancaria', $sql);
        $this->assertStringContainsString('cheq_info.Che_Fec AS Fecha_Cheque', $sql);
        $this->assertStringContainsString('GROUP BY d2.Com_Cod) cheq_info ON cheq_info.Com_Cod = comprobantes.Com_Cod', $sql);
    }

    public function testEstadoCuentaCase2PreservesParamsAndFilters(): void
    {
        $sql = \sentencias_estado_cuenta_proveedor(2, [
            'Prv_Cod' => '7',
            'txt_fec_ini' => '2024-01-01',
            'txt_fec_fin' => '2024-12-31',
        ]);

        $this->assertStringContainsString('proveedore.Prv_Cod = 7 AND proveedore.Emp_Cod = \'300\'', $sql);
        $this->assertStringContainsString("compras.Cop_Fec BETWEEN '2024-01-01' AND '2024-12-31'", $sql);
        $this->assertStringContainsString("comprobantes.Com_Fec BETWEEN '2024-01-01' AND '2024-12-31'", $sql);
        $this->assertStringContainsString('GROUP BY comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Num, comprobantes.Com_Obs, tipo_asien.Tia_Abr', $sql);
    }

    public function testCase38IsSinglePassRollup(): void
    {
        $sql = \sentencias_doc(38, ['0' => '300', '1' => '2024-01-01', '2' => '2024-12-31']);

        $this->assertStringNotContainsString('UNION ALL', $sql);
        $this->assertStringContainsString('GROUP BY renta_iva.Ren_Sri, renta_iva.Ren_Cod, renta_iva.Ren_Ret WITH ROLLUP', $sql);
        $this->assertStringContainsString(') t', $sql);
    }

    public function testCase38KeepsDetailAndTotalRows(): void
    {
        $sql = \sentencias_doc(38, ['0' => '300', '1' => '2024-01-01', '2' => '2024-12-31']);

        $this->assertStringContainsString('CASE WHEN Ren_Sri IS NULL THEN Ren_Ret ELSE Aut_Cod END AS Aut_Cod', $sql);
        $this->assertStringContainsString("CASE WHEN Ren_Sri IS NULL THEN 'H' ELSE 'D' END AS Det_Tip", $sql);
        $this->assertStringContainsString('WHERE (Ren_Sri IS NOT NULL AND Ren_Cod IS NOT NULL)', $sql);
        $this->assertStringContainsString('(Ren_Sri IS NULL AND Ren_Cod IS NULL AND Ren_Ret IS NOT NULL)', $sql);
    }

    public function testCase38HAmountsLandInHaberColumn(): void
    {
        $sql = \sentencias_doc(38, ['0' => '300', '1' => '2024-01-01', '2' => '2024-12-31']);

        $this->assertStringContainsString('CASE WHEN Ren_Sri IS NULL THEN NULL ELSE Debe END AS Debe', $sql);
        $this->assertStringContainsString('CASE WHEN Ren_Sri IS NULL THEN Haber ELSE NULL END AS Haber', $sql);
        $this->assertStringContainsString('ROUND(SUM((renta_iva.Ren_Por / 100) * det_retenc.Ret_Bas), 4) AS Haber', $sql);
    }

    public function testCase38PreservesRowOrderContract(): void
    {
        $sql = \sentencias_doc(38, ['0' => '300', '1' => '2024-01-01', '2' => '2024-12-31']);

        $this->assertStringContainsString('ORDER BY Det_Tip ASC, Ren_Ret ASC, Ren_Sri ASC, Ren_Cod ASC', $sql);
    }

    public function testCase38PreservesParamsAndJoins(): void
    {
        $sql = \sentencias_doc(38, ['0' => '300', '1' => '2024-01-01', '2' => '2024-12-31']);

        $this->assertStringContainsString("proveedore.Emp_Cod = '300'", $sql);
        $this->assertStringContainsString("plan_cuenta.Emp_Cod = '300'", $sql);
        $this->assertStringContainsString("BETWEEN '2024-01-01' AND '2024-12-31'", $sql);
        $this->assertStringContainsString('INNER JOIN reniva_pla ON reniva_pla.Ren_Cod = renta_iva.Ren_Cod', $sql);
        $this->assertStringContainsString('INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_Cod', $sql);
        $this->assertStringContainsString('reniva_pla.Ren_Tip = \'C\'', $sql);
    }
}
