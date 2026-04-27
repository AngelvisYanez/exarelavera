<?php
/**
 * Sentencias SQL para Estado de Cuenta de Proveedores (CCxPP)
 * Unifica facturas y pagos en una sola vista tipo kardex, ordenada por fecha.
 * Preparado para saldo acumulado en PHP.
 *
 * @package ccxpp.LOGICA
 */

if (!function_exists('sentencias_estado_cuenta_proveedor')) {

    /**
     * Retorna la consulta SQL según el id.
     * @param int $id Número de consulta
     * @param array $Par_Sql Parámetros (Prv_Cod, txt_fec_ini, txt_fec_fin, etc.)
     * @return string SQL
     */
    function sentencias_estado_cuenta_proveedor($id, $Par_Sql)
    {
        $Prv_Cod = isset($Par_Sql['Prv_Cod']) ? intval($Par_Sql['Prv_Cod']) : 0;
        $fec_ini = isset($Par_Sql['txt_fec_ini']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $Par_Sql['txt_fec_ini']) ? $Par_Sql['txt_fec_ini'] : date('Y-m-d', strtotime('-1 year'));
        $fec_fin = isset($Par_Sql['txt_fec_fin']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $Par_Sql['txt_fec_fin']) ? $Par_Sql['txt_fec_fin'] : date('Y-m-d');
        $Emp_Cod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '';

        $filter_prv = ($Prv_Cod > 0) ? " AND proveedore.Prv_Cod = " . $Prv_Cod : "";
        $nom_prv_sql = "IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape))";

        switch ($id) {
            /* Listado de proveedores para el selector */
            case 1:
                $searchPrv = isset($Par_Sql['searchPrv']) ? str_replace(array("\\", "'", "%", "_"), array("\\\\", "''", "\\%", "\\_"), $Par_Sql['searchPrv']) : '';
                $search = $searchPrv === '' ? '' : " AND (persona.Prs_Ced LIKE '%" . $searchPrv . "%' OR CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%" . $searchPrv . "%') ";
                return "SELECT proveedore.Prv_Cod, persona.Prs_Cod, persona.Prs_Ced, " .
                    // "IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS nombre, persona.Prs_Dir " .
                    $nom_prv_sql . " AS nombre, persona.Prs_Dir " .
                    "FROM persona, proveedore " .
                    "WHERE proveedore.Emp_Cod='" . $Emp_Cod . "' AND proveedore.Prs_Cod = persona.Prs_Cod AND proveedore.Prv_Est = 'A' " . $search . " " .
                    "ORDER BY nombre";

            /*
             * Estado de cuenta: UNION de facturas (y N.C.) + pagos.
             * Columnas: Com_Codigo, Fecha_Emision, Fecha_Venc, Tipo, Documento, Cuenta_Bancaria, Fecha_Cheque, TOTAL, ABONO.
             * Saldo acumulado se calcula en PHP.
             */
            case 2:
                $codigo_compro = "CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',MONTH(comprobantes.Com_Fec)),MONTH(comprobantes.Com_Fec)),'-',comprobantes.Com_Num)";

                // Parte 1: Facturas (una fila por factura de compra) + días venc. + abono aplicado a esta factura
                $sql_facturas = "SELECT " .
                    $codigo_compro . " AS Com_Codigo, " .
                    "compras.Cop_Fec AS Fecha_Emision, " .
                    "ccpp_pagar.Cpp_Ven AS Fecha_Venc, " .
                    "IF(tipo_asien.Tia_Abr IN ('NC','NDC','NCR'), 'Nota Crédito', 'Factura') AS Tipo, " .
                    "CONCAT(IFNULL(compras.Cop_Num,''), IF(IFNULL(compras.Cop_Obs,'')='','', ' - '), IFNULL(compras.Cop_Obs,'')) AS Documento, " .
                    "'' AS Cuenta_Bancaria, " .
                    "NULL AS Fecha_Cheque, " .
                    $nom_prv_sql . " AS Proveedor, " .
                    "asientos.Asi_Val AS TOTAL, " .
                    "0 AS ABONO, " .
                    "DATEDIFF(CURDATE(), ccpp_pagar.Cpp_Ven) AS Dias_Vencimiento, " .
                    "(SELECT COALESCE(SUM(det_ccpp_p.Pag_Val), 0) FROM det_ccpp_p WHERE det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod) AS Abono_Factura, " .
                    "1 AS Orden_Tipo " .
                    "FROM proveedore " .
                    "INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) " .
                    "INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) " .
                    "INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) " .
                    "INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    "INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Deh = 'H') " .
                    // "WHERE proveedore.Prv_Cod = " . $Prv_Cod . " AND proveedore.Emp_Cod = '" . $Emp_Cod . "' " .
                    "WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "' " . $filter_prv . " " .
                    "AND (compras.Cop_Est = 'A' OR compras.Cop_Est = 'E') " .
                    "AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    "AND compras.Cop_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'";

                // Parte 2: Pagos agrupados por comprobante (un solo pago de 4410 con descripción de todas las facturas que cubre)
                $codigo_compro_pago = "CONCAT(tipo_asien.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',MONTH(comprobantes.Com_Fec)),MONTH(comprobantes.Com_Fec)),'-',comprobantes.Com_Num)";
                $sql_pagos = "SELECT " .
                    $codigo_compro_pago . " AS Com_Codigo, " .
                    "comprobantes.Com_Fec AS Fecha_Emision, " .
                    "NULL AS Fecha_Venc, " .
                    "'Pago' AS Tipo, " .
                    "IFNULL(comprobantes.Com_Obs, '') AS Documento, " .
                    "(SELECT banco.Ban_Cue FROM det_ccpp_p d2 INNER JOIN cheques ON cheques.Asi_Cod = d2.Asi_Cod INNER JOIN banco ON cheques.Ban_Cod = banco.Ban_Cod WHERE d2.Com_Cod = comprobantes.Com_Cod LIMIT 1) AS Cuenta_Bancaria, " .
                    "(SELECT cheques.Che_Fec FROM det_ccpp_p d2 INNER JOIN cheques ON cheques.Asi_Cod = d2.Asi_Cod WHERE d2.Com_Cod = comprobantes.Com_Cod LIMIT 1) AS Fecha_Cheque, " .
                    $nom_prv_sql . " AS Proveedor, " .
                    "0 AS TOTAL, " .
                    "SUM(det_ccpp_p.Pag_Val) AS ABONO, " .
                    "NULL AS Dias_Vencimiento, " .
                    "NULL AS Abono_Factura, " .
                    "2 AS Orden_Tipo " .
                    "FROM det_ccpp_p " .
                    "INNER JOIN comprobantes ON (det_ccpp_p.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    "INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod) " .
                    "INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod) " .
                    // "INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod) " .
                    // "WHERE proveedore.Prv_Cod = " . $Prv_Cod . " AND proveedore.Emp_Cod = '" . $Emp_Cod . "' " .
                    "INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) " .
                    "WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "' " . $filter_prv . " " .
                    "AND comprobantes.Com_Est = 'A' " .
                    "AND comprobantes.Com_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "' " .
                    // "GROUP BY comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Num, comprobantes.Com_Obs, tipo_asien.Tia_Abr";
                    "GROUP BY comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Num, comprobantes.Com_Obs, tipo_asien.Tia_Abr, proveedore.Prv_Cod, persona.Prs_Nom, persona.Prs_Ape";

                return "($sql_facturas) UNION ALL ($sql_pagos) ORDER BY Fecha_Emision ASC, Orden_Tipo ASC";

            /* Resumen: saldo vencido (suma de saldos pendientes de facturas con fecha vencimiento < hoy) */
            case 3:
                return "SELECT COALESCE(SUM(GREATEST(0, asientos.Asi_Val - IFNULL(abonos.tot, 0))), 0) AS Saldo_Vencido " .
                    "FROM proveedore " .
                    "INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) " .
                    "INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) " .
                    "INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Deh = 'H') " .
                    "LEFT JOIN (SELECT Cpp_Cod, SUM(Pag_Val) AS tot FROM det_ccpp_p GROUP BY Cpp_Cod) abonos ON ccpp_pagar.Cpp_Cod = abonos.Cpp_Cod " .
                    // "WHERE proveedore.Prv_Cod = " . $Prv_Cod . " AND proveedore.Emp_Cod = '" . $Emp_Cod . "' " .
                    "WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "' " . $filter_prv . " " .
                    "AND ccpp_pagar.Cpp_Ven < CURDATE() " .
                    "AND (compras.Cop_Est = 'A' OR compras.Cop_Est = 'E') " .
                    "AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    "AND compras.Cop_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'";

            /* Saldo inicial: Suma de facturas - Suma de pagos antes de $fec_ini */
            case 4:
                return "SELECT ( " .
                    "(SELECT COALESCE(SUM(IF(tipo_asien.Tia_Abr IN ('NC','NDC','NCR'), -asientos.Asi_Val, asientos.Asi_Val)), 0) " .
                    " FROM proveedore " .
                    " INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) " .
                    " INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) " .
                    " INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) " .
                    " INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    " INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Deh = 'H') " .
                    // " WHERE proveedore.Prv_Cod = " . $Prv_Cod . " AND proveedore.Emp_Cod = '" . $Emp_Cod . "' " .
                    " WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "' " . $filter_prv . " " .
                    " AND (compras.Cop_Est = 'A' OR compras.Cop_Est = 'E') " .
                    " AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    " AND compras.Cop_Fec < '" . $fec_ini . "') " .
                    " - " .
                    "(SELECT COALESCE(SUM(det_ccpp_p.Pag_Val), 0) " .
                    " FROM det_ccpp_p " .
                    " INNER JOIN comprobantes ON (det_ccpp_p.Com_Cod = comprobantes.Com_Cod) " .
                    " INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod) " .
                    " INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod) " .
                    " INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod) " .
                    // " WHERE proveedore.Prv_Cod = " . $Prv_Cod . " AND proveedore.Emp_Cod = '" . $Emp_Cod . "' " .
                    " WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "' " . $filter_prv . " " .
                    " AND comprobantes.Com_Est = 'A' AND comprobantes.Com_Fec < '" . $fec_ini . "') " .
                    ") AS Saldo_Inicial";
        }
        return '';
    }
}
