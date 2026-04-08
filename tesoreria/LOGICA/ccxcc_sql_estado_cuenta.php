<?php
/**
 * Sentencias SQL para Estado de Cuenta de Clientes (CCxCC)
 * Unifica facturas y cobros en una sola vista tipo kardex, ordenada por fecha.
 * Preparado para saldo acumulado en PHP.
 *
 * @package ccxcc.LOGICA
 */

if (!function_exists('sentencias_estado_cuenta_cliente')) {

    /**
     * Retorna la consulta SQL según el id.
     * @param int $id Número de consulta
     * @param array $Par_Sql Parámetros (Cli_Cod, txt_fec_ini, txt_fec_fin, etc.)
     * @return string SQL
     */
    function sentencias_estado_cuenta_cliente($id, $Par_Sql)
    {
        $Cli_Cod = isset($Par_Sql['Cli_Cod']) ? intval($Par_Sql['Cli_Cod']) : 0;
        $fec_ini = isset($Par_Sql['txt_fec_ini']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $Par_Sql['txt_fec_ini']) ? $Par_Sql['txt_fec_ini'] : date('Y-01-01');
        $fec_fin = isset($Par_Sql['txt_fec_fin']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $Par_Sql['txt_fec_fin']) ? $Par_Sql['txt_fec_fin'] : date('Y-m-d');
        $Emp_Cod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '';

        switch ($id) {
            /* Listado de clientes para el selector */
            case 1:
                $searchCli = isset($Par_Sql['searchCli']) ? str_replace(array("\\", "'", "%", "_"), array("\\\\", "''", "\\%", "\\_"), $Par_Sql['searchCli']) : '';
                $search = $searchCli === '' ? '' : " AND (persona.Prs_Ced LIKE '%" . $searchCli . "%' OR CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%" . $searchCli . "%') ";
                return "SELECT cliente.Cli_Cod, persona.Prs_Cod, persona.Prs_Ced, " .
                    "IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS nombre, persona.Prs_Dir " .
                    "FROM persona, cliente " .
                    "WHERE cliente.Emp_Cod='" . $Emp_Cod . "' AND cliente.Prs_Cod = persona.Prs_Cod AND cliente.Cli_Est = 'A' " . $search . " " .
                    "ORDER BY nombre";

            /*
             * Estado de cuenta: UNION de facturas (y N.C.) + cobros.
             * Saldo acumulado se calcula en PHP.
             */
            case 2:
                $codigo_compro = "CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',MONTH(comprobantes.Com_Fec)),MONTH(comprobantes.Com_Fec)),'-',comprobantes.Com_Num)";

                // Parte 1: Facturas (una fila por factura de venta) + días venc. + abono aplicado a esta factura
                $sql_facturas = "SELECT " .
                    $codigo_compro . " AS Com_Codigo, " .
                    "caja_aper.Caj_Fec AS Fecha_Emision, " .
                    "ccpp_cobrar.Cpc_Ven AS Fecha_Venc, " .
                    "IF(tipo_asien.Tia_Abr IN ('NC','NDC','NCR'), 'Nota Crédito', 'Factura') AS Tipo, " .
                    "CONCAT(IFNULL(ventas.Vet_Num,''), IF(IFNULL(ventas.Vet_Obs,'')='','', ' - '), IFNULL(ventas.Vet_Obs,'')) AS Documento, " .
                    "'' AS Cuenta_Bancaria, " .
                    "NULL AS Fecha_Cheque, " .
                    "IF(asientos.Asi_Deh = 'D', asientos.Asi_Val, -asientos.Asi_Val) AS TOTAL, " .
                    "0 AS ABONO, " .
                    "DATEDIFF(CURDATE(), ccpp_cobrar.Cpc_Ven) AS Dias_Vencimiento, " .
                    "(SELECT COALESCE(SUM(det_ccpp_c.Cpc_Val), 0) FROM det_ccpp_c WHERE det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod) AS Abono_Factura, " .
                    "1 AS Orden_Tipo " .
                    "FROM cliente " .
                    "INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod) " .
                    "INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) " .
                    "INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) " .
                    "INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    "INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) " .
                    "INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) " .
                    "WHERE cliente.Cli_Cod = " . $Cli_Cod . " AND cliente.Emp_Cod = '" . $Emp_Cod . "' " .
                    "AND (ventas.Vet_Est = 'A' OR ventas.Vet_Est = 'E') " .
                    "AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    "AND caja_aper.Caj_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'";

                // Parte 2: Cobros agrupados por comprobante
                $codigo_compro_cobro = "CONCAT(tipo_asien.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',MONTH(comprobantes.Com_Fec)),MONTH(comprobantes.Com_Fec)),'-',comprobantes.Com_Num)";
                $sql_cobros = "SELECT " .
                    $codigo_compro_cobro . " AS Com_Codigo, " .
                    "comprobantes.Com_Fec AS Fecha_Emision, " .
                    "NULL AS Fecha_Venc, " .
                    "'Cobro' AS Tipo, " .
                    "IFNULL(comprobantes.Com_Obs, '') AS Documento, " .
                    "(SELECT banco.Ban_Cue FROM det_ccpp_c d2 INNER JOIN cheques ON cheques.Asi_Cod = d2.Asi_Cod INNER JOIN banco ON cheques.Ban_Cod = banco.Ban_Cod WHERE d2.Com_Cod = comprobantes.Com_Cod LIMIT 1) AS Cuenta_Bancaria, " .
                    "(SELECT cheques.Che_Fec FROM det_ccpp_c d2 INNER JOIN cheques ON cheques.Asi_Cod = d2.Asi_Cod WHERE d2.Com_Cod = comprobantes.Com_Cod LIMIT 1) AS Fecha_Cheque, " .
                    "0 AS TOTAL, " .
                    "SUM(det_ccpp_c.Cpc_Val) AS ABONO, " .
                    "NULL AS Dias_Vencimiento, " .
                    "NULL AS Abono_Factura, " .
                    "2 AS Orden_Tipo " .
                    "FROM det_ccpp_c " .
                    "INNER JOIN comprobantes ON (det_ccpp_c.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod) " .
                    "INNER JOIN ccpp_cobrar ON (det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod) " .
                    "INNER JOIN ventas ON (ccpp_cobrar.Vet_Cod = ventas.Vet_Cod) " .
                    "INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod) " .
                    "WHERE cliente.Cli_Cod = " . $Cli_Cod . " AND cliente.Emp_Cod = '" . $Emp_Cod . "' " .
                    "AND comprobantes.Com_Est = 'A' " .
                    "AND comprobantes.Com_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "' " .
                    "GROUP BY comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Num, comprobantes.Com_Obs, tipo_asien.Tia_Abr";

                return "($sql_facturas) UNION ALL ($sql_cobros) ORDER BY Fecha_Emision ASC, Orden_Tipo ASC";

            /* Resumen: saldo vencido */
            case 3:
                return "SELECT COALESCE(SUM(GREATEST(0, asientos.Asi_Val - IFNULL(abonos.tot, 0))), 0) AS Saldo_Vencido " .
                    "FROM cliente " .
                    "INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod) " .
                    "INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) " .
                    "INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) " .
                    "INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) " .
                    "INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Deh = 'D') " .
                    "LEFT JOIN (SELECT Cpc_Cod, SUM(Cpc_Val) AS tot FROM det_ccpp_c GROUP BY Cpc_Cod) abonos ON ccpp_cobrar.Cpc_Cod = abonos.Cpc_Cod " .
                    "WHERE cliente.Cli_Cod = " . $Cli_Cod . " AND cliente.Emp_Cod = '" . $Emp_Cod . "' " .
                    "AND ccpp_cobrar.Cpc_Ven < CURDATE() " .
                    "AND (ventas.Vet_Est = 'A' OR ventas.Vet_Est = 'E') " .
                    "AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    "AND caja_aper.Caj_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'";

            /* Saldo inicial */
            case 4:
                return "SELECT ( " .
                    "(SELECT COALESCE(SUM(IF(asientos.Asi_Deh = 'D', asientos.Asi_Val, -asientos.Asi_Val)), 0) " .
                    " FROM cliente " .
                    " INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod) " .
                    " INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) " .
                    " INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) " .
                    " INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) " .
                    " INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) " .
                    " INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) " .
                    " WHERE cliente.Cli_Cod = " . $Cli_Cod . " AND cliente.Emp_Cod = '" . $Emp_Cod . "' " .
                    " AND (ventas.Vet_Est = 'A' OR ventas.Vet_Est = 'E') " .
                    " AND (comprobantes.Com_Est = 'A' OR comprobantes.Com_Est = 'E') " .
                    " AND caja_aper.Caj_Fec < '" . $fec_ini . "') " .
                    " - " .
                    "(SELECT COALESCE(SUM(det_ccpp_c.Cpc_Val), 0) " .
                    " FROM det_ccpp_c " .
                    " INNER JOIN comprobantes ON (det_ccpp_c.Com_Cod = comprobantes.Com_Cod) " .
                    " INNER JOIN ccpp_cobrar ON (det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod) " .
                    " INNER JOIN ventas ON (ccpp_cobrar.Vet_Cod = ventas.Vet_Cod) " .
                    " INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod) " .
                    " WHERE cliente.Cli_Cod = " . $Cli_Cod . " AND cliente.Emp_Cod = '" . $Emp_Cod . "' " .
                    " AND comprobantes.Com_Est = 'A' AND comprobantes.Com_Fec < '" . $fec_ini . "') " .
                    ") AS Saldo_Inicial";
        }
        return '';
    }
}
