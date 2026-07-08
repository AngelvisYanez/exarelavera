<?php

class FacturacionElectronicaClass {
    protected $conexion = null;
    protected $datos = null;

    function __construct($conexion, $datos) {
        $this->conexion = $conexion;
        $this->datos = $datos;
    }

    private function getMysqli() {
        $con = $this->conexion;
        if (is_object($con) && ($con instanceof MysqlConexion)) {
            return $con->conexion;
        }
        return $this->datos->getMyCon($con);
    }

    private function escape($str) {
        $mysqli = $this->getMysqli();
        return $mysqli ? $mysqli->real_escape_string($str) : addslashes($str);
    }

    private function sortClause($field, $order, $allowed, $default) {
        $order = strtoupper($order);
        if ($order !== 'ASC' && $order !== 'DESC') $order = 'DESC';
        if (isset($allowed[$field])) {
            return 'ORDER BY ' . $allowed[$field] . ' ' . $order;
        }
        return 'ORDER BY ' . $default . ' DESC';
    }

    public function getComprobantes($body) {
        $Emp_Cod = isset($body['Emp_Cod']) ? (int)$body['Emp_Cod'] : 0;
        $page = isset($body['page']) ? (int)$body['page'] : 1;
        $rows = isset($body['rows']) ? (int)$body['rows'] : 50;
        $search = isset($body['search']) ? $body['search'] : '';
        $fecha_desde = isset($body['fecha_desde']) ? $body['fecha_desde'] : '';
        $fecha_hasta = isset($body['fecha_hasta']) ? $body['fecha_hasta'] : '';
        $estado = isset($body['estado']) ? $body['estado'] : '';
        $sort_field = isset($body['sort_field']) ? $body['sort_field'] : '';
        $sort_order = isset($body['sort_order']) ? $body['sort_order'] : 'DESC';

        $where = "WHERE v.Vet_Est = 'A'";
        if ($Emp_Cod > 0) $where .= " AND s.Emp_Cod = $Emp_Cod";
        if (!empty($search)) { $s = $this->escape($search); $where .= " AND (pn.Prs_Nom LIKE '%$s%' OR pn.Prs_Ced LIKE '%$s%' OR v.Vet_Num LIKE '%$s%')"; }
        if (!empty($fecha_desde)) $where .= " AND v.Vet_Sys >= '$fecha_desde 00:00:00'";
        if (!empty($fecha_hasta)) $where .= " AND v.Vet_Sys <= '$fecha_hasta 23:59:59'";
        if ($estado === 'electronicos') $where .= " AND v.Vet_Aut = 'S'";
        elseif ($estado === 'no_electronicos') $where .= " AND (v.Vet_Aut IS NULL OR v.Vet_Aut = 'N')";

        $countSql = "SELECT COUNT(*) AS total FROM ventas v
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            LEFT JOIN cliente c ON v.Cli_Cod = c.Cli_Cod
            LEFT JOIN persona pn ON c.Prs_Cod = pn.Prs_Cod
            $where";
        $countRow = $this->datos->getRowConsultaSql($countSql, $this->conexion);
        $total = $countRow ? (int)$countRow['total'] : 0;

        $sortAllowed = array(
            'Vet_Num' => 'v.Vet_Num',
            'Prs_Nom' => 'pn.Prs_Nom',
            'Prs_Ced' => 'pn.Prs_Ced',
            'Vet_Sys' => 'v.Vet_Sys',
            'Vet_Aut' => 'v.Vet_Aut',
            'Aut_Sri' => 'a.Aut_Sri',
        );
        $sort = $this->sortClause($sort_field, $sort_order, $sortAllowed, 'v.Vet_Sys');

        $start = ($page - 1) * $rows;
        $sql = "SELECT v.Vet_Cod, v.Vet_Num, v.Vet_Aut, v.Vet_Xml, v.Vet_Sri,
                v.Vet_Obs, v.Vet_Sys, v.Vet_Hor, v.Vet_Est,
                pn.Prs_Nom, pn.Prs_Ced,
                a.Aut_Cod, a.Pun_Sri, a.Aut_Sri AS Aut_Sri_Num, a.Aut_Tem,
                t.Tic_Des, t.Tic_Sri
            FROM ventas v
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            LEFT JOIN cliente c ON v.Cli_Cod = c.Cli_Cod
            LEFT JOIN persona pn ON c.Prs_Cod = pn.Prs_Cod
            LEFT JOIN tipo_compr t ON v.Tic_Cod = t.Tic_Cod
            $where
            $sort
            LIMIT $start, $rows";
        $rows_data = $this->datos->getArrayConsultaSql($sql, $this->conexion);
        $this->datos->utf8_change_param($rows_data);

        $response = array(
            'status' => true,
            'data' => array(
                'rows' => $rows_data,
                'page' => $page,
                'total' => ceil($total / $rows),
                'records' => $total,
                'success' => true
            )
        );
        $this->datos->echoJson($response);
    }

    public function getRetenciones($body) {
        $Emp_Cod = isset($body['Emp_Cod']) ? (int)$body['Emp_Cod'] : 0;
        $page = isset($body['page']) ? (int)$body['page'] : 1;
        $rows = isset($body['rows']) ? (int)$body['rows'] : 50;
        $search = isset($body['search']) ? $body['search'] : '';
        $fecha_desde = isset($body['fecha_desde']) ? $body['fecha_desde'] : '';
        $fecha_hasta = isset($body['fecha_hasta']) ? $body['fecha_hasta'] : '';
        $estado = isset($body['estado']) ? $body['estado'] : '';
        $sort_field = isset($body['sort_field']) ? $body['sort_field'] : '';
        $sort_order = isset($body['sort_order']) ? $body['sort_order'] : 'DESC';

        $where = "WHERE r.Ret_Est = 'A'";
        if ($Emp_Cod > 0) $where .= " AND s.Emp_Cod = $Emp_Cod";
        if (!empty($search)) { $s = $this->escape($search); $where .= " AND (pn.Prs_Nom LIKE '%$s%' OR r.Ret_Num LIKE '%$s%')"; }
        if (!empty($fecha_desde)) $where .= " AND r.Ret_Fec >= '$fecha_desde'";
        if (!empty($fecha_hasta)) $where .= " AND r.Ret_Fec <= '$fecha_hasta'";
        if ($estado === 'electronicos') $where .= " AND r.Ret_Aut = 'S'";
        elseif ($estado === 'no_electronicos') $where .= " AND (r.Ret_Aut IS NULL OR r.Ret_Aut = 'N')";

        $countSql = "SELECT COUNT(*) AS total FROM retencion r
            LEFT JOIN autorizaci a ON r.Aut_Cod = a.Aut_Cod
            LEFT JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            LEFT JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            LEFT JOIN compras cp ON r.Cop_Cod = cp.Cop_Cod
            LEFT JOIN proveedore pv ON cp.Prv_Cod = pv.Prv_Cod
            LEFT JOIN persona pn ON pv.Prs_Cod = pn.Prs_Cod
            $where";
        $countRow = $this->datos->getRowConsultaSql($countSql, $this->conexion);
        $total = $countRow ? (int)$countRow['total'] : 0;

        $sortAllowed = array(
            'Ret_Num' => 'r.Ret_Num',
            'Ret_Fec' => 'r.Ret_Fec',
            'Prs_Nom' => 'pn.Prs_Nom',
            'Ret_Con' => 'r.Ret_Con',
            'Ret_Aut' => 'r.Ret_Aut',
            'Ret_Sri' => 'r.Ret_Sri',
            'Ret_Sys' => 'r.Ret_Sys',
        );
        $sort = $this->sortClause($sort_field, $sort_order, $sortAllowed, 'r.Ret_Sys');

        $start = ($page - 1) * $rows;
        $sql = "SELECT r.Ret_Cod, r.Ret_Num, r.Ret_Fec, r.Ret_Con, r.Ret_Est,
                r.Ret_Aut, r.Ret_Xml, r.Ret_Sri, r.Ret_Sys,
                a.Aut_Cod, a.Pun_Sri, a.Aut_Sri AS Aut_Sri_Num,
                t.Tic_Des, t.Tic_Sri,
                pn.Prs_Nom, pn.Prs_Ced
            FROM retencion r
            LEFT JOIN autorizaci a ON r.Aut_Cod = a.Aut_Cod
            LEFT JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
            LEFT JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod
            LEFT JOIN tipo_compr t ON r.Tic_Cod = t.Tic_Cod
            LEFT JOIN compras cp ON r.Cop_Cod = cp.Cop_Cod
            LEFT JOIN proveedore pv ON cp.Prv_Cod = pv.Prv_Cod
            LEFT JOIN persona pn ON pv.Prs_Cod = pn.Prs_Cod
            $where
            $sort
            LIMIT $start, $rows";
        $rows_data = $this->datos->getArrayConsultaSql($sql, $this->conexion);
        $this->datos->utf8_change_param($rows_data);

        $response = array(
            'status' => true,
            'data' => array(
                'rows' => $rows_data,
                'page' => $page,
                'total' => ceil($total / $rows),
                'records' => $total,
                'success' => true
            )
        );
        $this->datos->echoJson($response);
    }

    public function getComprobantesContables($body) {
        $Emp_Cod = isset($body['Emp_Cod']) ? (int)$body['Emp_Cod'] : 0;
        $page = isset($body['page']) ? (int)$body['page'] : 1;
        $rows = isset($body['rows']) ? (int)$body['rows'] : 50;
        $search = isset($body['search']) ? $body['search'] : '';
        $fecha_desde = isset($body['fecha_desde']) ? $body['fecha_desde'] : '';
        $fecha_hasta = isset($body['fecha_hasta']) ? $body['fecha_hasta'] : '';
        $sort_field = isset($body['sort_field']) ? $body['sort_field'] : '';
        $sort_order = isset($body['sort_order']) ? $body['sort_order'] : 'DESC';

        $where = "WHERE co.Com_Est = 'A'";
        if ($Emp_Cod > 0) $where .= " AND pe.Emp_Cod = $Emp_Cod";
        if (!empty($search)) { $s = $this->escape($search); $where .= " AND (co.Com_Con LIKE '%$s%' OR co.Com_Num LIKE '%$s%')"; }
        if (!empty($fecha_desde)) $where .= " AND co.Com_Fec >= '$fecha_desde'";
        if (!empty($fecha_hasta)) $where .= " AND co.Com_Fec <= '$fecha_hasta'";

        $countSql = "SELECT COUNT(*) AS total FROM comprobantes co
            INNER JOIN perio_cont pe ON co.Pec_Cod = pe.Pec_Cod
            $where";
        $countRow = $this->datos->getRowConsultaSql($countSql, $this->conexion);
        $total = $countRow ? (int)$countRow['total'] : 0;

        $sortAllowed = array(
            'Com_Num' => 'co.Com_Num',
            'Com_Fec' => 'co.Com_Fec',
            'Com_Tip' => 'co.Com_Tip',
            'Com_Con' => 'co.Com_Con',
            'Com_Val' => 'co.Com_Val',
            'Com_Mod' => 'co.Com_Mod',
            'Com_Sys' => 'co.Com_Sys',
        );
        $sort = $this->sortClause($sort_field, $sort_order, $sortAllowed, 'co.Com_Sys');

        $start = ($page - 1) * $rows;
        $sql = "SELECT co.Com_Cod, co.Com_Num, co.Com_Fec, co.Com_Con, co.Com_Tip,
                co.Com_Val, co.Com_Obs, co.Com_Est, co.Com_Gen, co.Com_Mod, co.Com_Doc,
                co.Num_Doc, co.Com_Sys,
                pe.Pec_Cod, pe.Pec_Fei, pe.Pec_Fef,
                ta.Tia_Des, ta.Tia_Abr,
                pnc.Prs_Nom AS Cli_Nom,
                pnp.Prs_Nom AS Prv_Nom
            FROM comprobantes co
            INNER JOIN perio_cont pe ON co.Pec_Cod = pe.Pec_Cod
            LEFT JOIN tipo_asien ta ON co.Tia_Cod = ta.Tia_Cod
            LEFT JOIN cliente c ON co.Cli_Cod = c.Cli_Cod
            LEFT JOIN persona pnc ON c.Prs_Cod = pnc.Prs_Cod
            LEFT JOIN proveedore p ON co.Prv_Cod = p.Prv_Cod
            LEFT JOIN persona pnp ON p.Prs_Cod = pnp.Prs_Cod
            $where
            $sort
            LIMIT $start, $rows";
        $rows_data = $this->datos->getArrayConsultaSql($sql, $this->conexion);
        $this->datos->utf8_change_param($rows_data);

        $response = array(
            'status' => true,
            'data' => array(
                'rows' => $rows_data,
                'page' => $page,
                'total' => ceil($total / $rows),
                'records' => $total,
                'success' => true
            )
        );
        $this->datos->echoJson($response);
    }

    public function getResumen($body) {
        $Emp_Cod = isset($body['Emp_Cod']) ? (int)$body['Emp_Cod'] : 0;

        $joinEmp = "";
        $joinEmpRet = "";
        if ($Emp_Cod > 0) {
            $joinEmp = " INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod AND s.Emp_Cod = $Emp_Cod";
            $joinEmpRet = " INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod INNER JOIN sucursal s ON p.Suc_Cod = s.Suc_Cod AND s.Emp_Cod = $Emp_Cod";
        }

        $sql1 = "SELECT COUNT(*) AS total, SUM(CASE WHEN v.Vet_Aut = 'S' THEN 1 ELSE 0 END) AS electronicos
            FROM ventas v INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            $joinEmp
            WHERE v.Vet_Est = 'A'";

        $facturas = $this->datos->getRowConsultaSql($sql1, $this->conexion);

        $sql2 = "SELECT COUNT(*) AS total, SUM(CASE WHEN r.Ret_Aut = 'S' THEN 1 ELSE 0 END) AS electronicos
            FROM retencion r
            INNER JOIN autorizaci a ON r.Aut_Cod = a.Aut_Cod
            $joinEmpRet
            WHERE r.Ret_Est = 'A'";

        $retenciones = $this->datos->getRowConsultaSql($sql2, $this->conexion);

        $comprobantes = $this->datos->getRowConsultaSql(
            "SELECT COUNT(*) AS total FROM comprobantes co
            INNER JOIN perio_cont pe ON co.Pec_Cod = pe.Pec_Cod
            WHERE co.Com_Est = 'A'", $this->conexion);

        $response = array(
            'status' => true,
            'data' => array(
                'facturas' => $facturas ?: array('total' => 0, 'electronicos' => 0),
                'retenciones' => $retenciones ?: array('total' => 0, 'electronicos' => 0),
                'comprobantes' => $comprobantes ?: array('total' => 0)
            )
        );
        $this->datos->echoJson($response);
    }
}
