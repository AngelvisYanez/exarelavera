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
        $fec_ini = isset($Par_Sql['txt_fec_ini']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $Par_Sql['txt_fec_ini']) ? $Par_Sql['txt_fec_ini'] : date('Y-01-01');
        $fec_fin = isset($Par_Sql['txt_fec_fin']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $Par_Sql['txt_fec_fin']) ? $Par_Sql['txt_fec_fin'] : date('Y-m-d');
        $Emp_Cod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '';
        // Filtro de proveedor: si Prv_Cod=0 se consultan todos los proveedores de la empresa
        $filter_prov = ($Prv_Cod > 0) ? " AND proveedore.Prv_Cod = " . $Prv_Cod : "";
        // Solo facturas cuyo comprobante tenga al menos un asiento Haber en cuentas CxP proveedores (ccpp_prove)
        $sql_existe_ccpp_prove = " AND EXISTS ( SELECT 1 FROM asientos a_cpp INNER JOIN ccpp_prove cp ON (a_cpp.Pld_Cod = cp.Pld_Cod) WHERE a_cpp.Com_Cod = comprobantes.Com_Cod AND a_cpp.Asi_Deh = 'H' ) ";

        switch ($id) {
            /* Listado de proveedores para el selector */
            case 1:
                $searchPrv = isset($Par_Sql['searchPrv']) ? str_replace(array("\\", "'", "%", "_"), array("\\\\", "''", "\\%", "\\_"), $Par_Sql['searchPrv']) : '';
                $search = $searchPrv === '' ? '' : " AND (persona.Prs_Ced LIKE '%" . $searchPrv . "%' OR CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%" . $searchPrv . "%') ";
                return "SELECT proveedore.Prv_Cod, persona.Prs_Cod, persona.Prs_Ced, " .
                    "IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS nombre, persona.Prs_Dir " .
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
                    "'' AS Num_Ref, " .
                    "'' AS Num_Che_raw, " .
                    "'' AS Num_Doc_raw, " .
                    "'' AS Tipo_Pago, " .
                    "asientos.Asi_Val AS TOTAL, " .
                    "0 AS ABONO, " .
                    "DATEDIFF(CURDATE(), ccpp_pagar.Cpp_Ven) AS Dias_Vencimiento, " .
                    "(SELECT COALESCE(SUM(det_ccpp_p.Pag_Val), 0) FROM det_ccpp_p WHERE det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod) AS Abono_Factura, " .
                    "1 AS Orden_Tipo, " .
                    "IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS Proveedor, " .
                    "comprobantes.Com_Cod AS Com_Cod_Pago, " .
                    "ccpp_pagar.Cpp_Cod AS Cpp_Cod_Row, " .
                    "IFNULL(carga_masiva.Carm_Cla, '') AS Carm_Cla " .
                    "FROM proveedore " .
                    "INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) " .
                    "INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) " .
                    "INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) " .
                    "INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    "INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Deh = 'H') " .
                    "LEFT JOIN carga_masiva ON (persona.Prs_Ced = carga_masiva.Carm_Ruc AND compras.Cop_Num = carga_masiva.Carm_Num AND proveedore.Emp_Cod = carga_masiva.Emp_Cod) " .
                    "WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "'" . $filter_prov . " " .
                    "AND (compras.Cop_Est = 'A' OR compras.Cop_Est = 'E') " .
                    "AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    "AND compras.Cop_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'" .
                    $sql_existe_ccpp_prove;

                // Parte 2: Pagos agrupados por comprobante
                $codigo_compro_pago = "CONCAT(tipo_asien.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',MONTH(comprobantes.Com_Fec)),MONTH(comprobantes.Com_Fec)),'-',comprobantes.Com_Num)";
                $sql_pagos = "SELECT " .
                    $codigo_compro_pago . " AS Com_Codigo, " .
                    "comprobantes.Com_Fec AS Fecha_Emision, " .
                    "NULL AS Fecha_Venc, " .
                    "'Pago' AS Tipo, " .
                    "IFNULL(comprobantes.Com_Obs, '') AS Documento, " .
                    "(SELECT banco.Ban_Cue FROM det_ccpp_p d2 INNER JOIN cheques ON cheques.Asi_Cod = d2.Asi_Cod INNER JOIN banco ON cheques.Ban_Cod = banco.Ban_Cod WHERE d2.Com_Cod = comprobantes.Com_Cod LIMIT 1) AS Cuenta_Bancaria, " .
                    "(SELECT cheques.Che_Fec FROM det_ccpp_p d2 INNER JOIN cheques ON cheques.Asi_Cod = d2.Asi_Cod WHERE d2.Com_Cod = comprobantes.Com_Cod LIMIT 1) AS Fecha_Cheque, " .
                    "'' AS Num_Ref, " .
                    "IFNULL((SELECT cheques.Che_Num FROM det_ccpp_p d2 INNER JOIN cheques ON cheques.Asi_Cod = d2.Asi_Cod WHERE d2.Com_Cod = comprobantes.Com_Cod LIMIT 1), '') AS Num_Che_raw, " .
                    "IFNULL(comprobantes.Num_Doc, '') AS Num_Doc_raw, " .
                    "IFNULL(GROUP_CONCAT(DISTINCT tipos_pago.Pag_Des ORDER BY tipos_pago.Pag_Des SEPARATOR ' / '), '') AS Tipo_Pago, " .
                    "0 AS TOTAL, " .
                    "SUM(det_ccpp_p.Pag_Val) AS ABONO, " .
                    "NULL AS Dias_Vencimiento, " .
                    "NULL AS Abono_Factura, " .
                    "2 AS Orden_Tipo, " .
                    "IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS Proveedor, " .
                    "comprobantes.Com_Cod AS Com_Cod_Pago, " .
                    "0 AS Cpp_Cod_Row, " .
                    "'' AS Carm_Cla " .
                    "FROM det_ccpp_p " .
                    "INNER JOIN comprobantes ON (det_ccpp_p.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    "LEFT JOIN tipos_pago ON (det_ccpp_p.Pag_Cod = tipos_pago.Pag_Cod) " .
                    "INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod) " .
                    "INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod) " .
                    "INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod) " .
                    "INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) " .
                    "WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "'" . $filter_prov . " " .
                    "AND comprobantes.Com_Est = 'A' " .
                    "AND comprobantes.Com_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "' " .
                    "GROUP BY comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Num, comprobantes.Com_Obs, comprobantes.Num_Doc, tipo_asien.Tia_Abr, persona.Prs_Nom, persona.Prs_Ape";

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
                    "WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "'" . $filter_prov . " " .
                    "AND ccpp_pagar.Cpp_Ven < CURDATE() " .
                    "AND (compras.Cop_Est = 'A' OR compras.Cop_Est = 'E') " .
                    "AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    "AND compras.Cop_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'" .
                    $sql_existe_ccpp_prove;

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
                    " WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "'" . $filter_prov . " " .
                    " AND (compras.Cop_Est = 'A' OR compras.Cop_Est = 'E') " .
                    " AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    " AND compras.Cop_Fec < '" . $fec_ini . "'" .
                    $sql_existe_ccpp_prove . ") " .
                    " - " .
                    "(SELECT COALESCE(SUM(det_ccpp_p.Pag_Val), 0) " .
                    " FROM det_ccpp_p " .
                    " INNER JOIN comprobantes ON (det_ccpp_p.Com_Cod = comprobantes.Com_Cod) " .
                    " INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod) " .
                    " INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod) " .
                    " INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod) " .
                    " WHERE proveedore.Emp_Cod = '" . $Emp_Cod . "'" . $filter_prov . " " .
                    " AND comprobantes.Com_Est = 'A' AND comprobantes.Com_Fec < '" . $fec_ini . "') " .
                    ") AS Saldo_Inicial";

            /* Facturas a crédito sin ningún Haber en ccpp_prove (excluidas del kardex); aviso en pantalla */
            case 10:
                return "SELECT " .
                    "compras.Cop_Cod, " .
                    "MAX(compras.Cop_Num) AS Cop_Num, " .
                    "MAX(compras.Cop_Fec) AS Cop_Fec, " .
                    "MAX(CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char))) AS Com_Codigo, " .
                    "MAX(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) AS proveedor, " .
                    "GROUP_CONCAT(DISTINCT CONCAT(det_plan.Pld_Cdc,' - ',det_plan.Pld_Des) ORDER BY asientos.Asi_Cod SEPARATOR ' | ') AS cuenta_haber " .
                    "FROM proveedore " .
                    "INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) " .
                    "INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) " .
                    "INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) " .
                    "INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (tipo_asien.Tia_Cod = comprobantes.Tia_Cod) " .
                    "INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Deh = 'H') " .
                    "INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) " .
                    "INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) " .
                    "WHERE comprobantes.Com_Cod = ccpp_pagar.Com_Cod " .
                    "AND asientos.Com_Cod = comprobantes.Com_Cod " .
                    $filter_prov . " " .
                    "AND (compras.Cop_Est = 'A' OR compras.Cop_Est = 'E') " .
                    "AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    "AND compras.Cop_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "' " .
                    "AND proveedore.Emp_Cod = '" . $Emp_Cod . "' " .
                    "AND NOT EXISTS ( " .
                    "SELECT 1 FROM asientos a2 INNER JOIN ccpp_prove cp ON (a2.Pld_Cod = cp.Pld_Cod) " .
                    "WHERE a2.Com_Cod = comprobantes.Com_Cod AND a2.Asi_Deh = 'H' " .
                    ") " .
                    "GROUP BY compras.Cop_Cod " .
                    "ORDER BY MAX(compras.Cop_Fec), compras.Cop_Cod";

            /* Pagos aplicados a una factura específica (por Cpp_Cod) */
            case 9:
                $Cpp_Cod = isset($Par_Sql['Cpp_Cod']) ? intval($Par_Sql['Cpp_Cod']) : 0;
                $codigo_compro_pag = "CONCAT(tipo_asien.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',MONTH(comprobantes.Com_Fec)),MONTH(comprobantes.Com_Fec)),'-',comprobantes.Com_Num)";
                return "SELECT " . $codigo_compro_pag . " AS codigo_compro, " .
                    "comprobantes.Com_Fec, det_ccpp_p.Pag_Val, det_ccpp_p.Pag_Fec, " .
                    "det_ccpp_p.Pag_Obs, tipos_pago.Pag_Des AS T_Pago " .
                    "FROM det_ccpp_p " .
                    "INNER JOIN tipos_pago ON (det_ccpp_p.Pag_Cod = tipos_pago.Pag_Cod) " .
                    "INNER JOIN comprobantes ON (det_ccpp_p.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    "WHERE det_ccpp_p.Cpp_Cod = " . $Cpp_Cod . " ORDER BY comprobantes.Com_Fec";

            /* Lista de tipos de pago activos */
            case 8:
                return "SELECT Pag_Cod, Pag_Des FROM tipos_pago WHERE Pag_Est = 'A' ORDER BY Pag_Des";

            /* Asientos de un comprobante de pago */
            case 5:
                $Com_Cod = isset($Par_Sql['Com_Cod']) ? intval($Par_Sql['Com_Cod']) : 0;
                return "SELECT det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Deh, asientos.Asi_Val, asientos.Asi_Con, asientos.Asi_Glo " .
                    "FROM asientos, det_plan " .
                    "WHERE asientos.Com_Cod = " . $Com_Cod . " AND det_plan.Pld_Cod = asientos.Pld_Cod";

            /* Cheques de un comprobante de pago */
            case 6:
                $Com_Cod = isset($Par_Sql['Com_Cod']) ? intval($Par_Sql['Com_Cod']) : 0;
                return "SELECT * FROM asientos, cheques " .
                    "WHERE asientos.Com_Cod = " . $Com_Cod . " AND cheques.Asi_Cod = asientos.Asi_Cod";

            /* Facturas incluidas en un comprobante de pago */
            case 7:
                $Com_Cod = isset($Par_Sql['Com_Cod']) ? intval($Par_Sql['Com_Cod']) : 0;
                return "SELECT det_ccpp_p.Cpp_Cod, compras.Cop_Num, compras.Cop_Fec, ccpp_pagar.Cpp_Ven, " .
                    "det_ccpp_p.Pag_Val AS monto_pagado, asientos.Asi_Val AS total_factura, " .
                    "CONCAT(tipo_asien.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comp_fact.Com_Fec))=1,CONCAT('0',CAST(MONTH(comp_fact.Com_Fec) AS char)),CAST(MONTH(comp_fact.Com_Fec) AS char)),'-',CAST(comp_fact.Com_Num AS char)) AS num_compro_factura " .
                    "FROM det_ccpp_p " .
                    "INNER JOIN ccpp_pagar ON ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod " .
                    "INNER JOIN compras ON compras.Cop_Cod = ccpp_pagar.Cop_Cod " .
                    "INNER JOIN comprobantes AS comp_fact ON comp_fact.Com_Cod = ccpp_pagar.Com_Cod " .
                    "INNER JOIN tipo_asien ON tipo_asien.Tia_Cod = comp_fact.Tia_Cod " .
                    "INNER JOIN asientos ON asientos.Com_Cod = ccpp_pagar.Com_Cod AND asientos.Asi_Deh = 'H' " .
                    "WHERE det_ccpp_p.Com_Cod = " . $Com_Cod . " ORDER BY compras.Cop_Num";
        }
        return '';
    }
}
