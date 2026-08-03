<?php
/**
 * Puente de integracion presupuestaria para modulos EXA.
 * Incluir con: require_once ruta/Presupuesto/LOGICA/ppto_hooks_bridge.php
 */

if (!function_exists('ppto_bridge_cargar')) {

    function ppto_bridge_cargar() {
        static $ok = false;
        if ($ok) {
            return;
        }
        $loader = __DIR__ . '/ppto_hooks_loader.php';
        if (file_exists($loader)) {
            require_once $loader;
        }
        $ok = true;
    }

    /**
     * Obtiene mysqli desde objetos de conexion EXA.
     *
     * @param mixed $obj
     * @return mysqli|null
     */
    function ppto_bridge_conn_desde_exa($obj) {
        if ($obj instanceof mysqli) {
            return $obj;
        }
        if (is_object($obj) && isset($obj->conexion) && $obj->conexion instanceof mysqli) {
            return $obj->conexion;
        }
        return null;
    }

    /**
     * Ejecuta hook sin interrumpir el flujo del modulo origen.
     *
     * @param callable $fn
     * @return mixed|null
     */
    function ppto_bridge_ejecutar_seguro($fn) {
        try {
            return call_user_func($fn);
        } catch (Exception $e) {
            return null;
        }
    }

    function ppto_bridge_ensure_compras_staging($mysqli) {
        if (!$mysqli) {
            return;
        }
        $mysqli->query("CREATE TABLE IF NOT EXISTS `exa_compras_ordenes` (
            `id_orden` VARCHAR(50) NOT NULL PRIMARY KEY,
            `id_empresa` INT NOT NULL,
            `id_proyecto` VARCHAR(50) NULL,
            `id_rubro` VARCHAR(100) NULL,
            `monto_total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            `estado` VARCHAR(20) NOT NULL DEFAULT 'creada',
            `id_usuario` INT NOT NULL DEFAULT 1,
            `fecha` DATE NOT NULL,
            INDEX `idx_exa_oc_emp` (`id_empresa`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    /**
     * Sincroniza orden_compra real hacia tabla staging del hook.
     *
     * @param mysqli $mysqli
     * @param int $ord_cod
     * @param int $emp_id
     * @param int $usu_id
     * @return string|null
     */
    function ppto_bridge_sync_orden_compra($mysqli, $ord_cod, $emp_id, $usu_id) {
        if (!$mysqli || $ord_cod <= 0) {
            return null;
        }
        ppto_bridge_ensure_compras_staging($mysqli);
        $ord_cod = (int)$ord_cod;
        $res = $mysqli->query("SELECT oc.Ord_Fec, COALESCE(SUM(d.Ord_Imp), 0) AS monto
            FROM orden_compra oc
            LEFT JOIN orden_comp_det d ON oc.Ord_Cod = d.Ord_Cod
            WHERE oc.Ord_Cod = $ord_cod
            GROUP BY oc.Ord_Cod, oc.Ord_Fec
            LIMIT 1");
        if (!$res || !($row = $res->fetch_assoc())) {
            return null;
        }
        $id = 'OC-' . $ord_cod;
        $monto = (float)$row['monto'];
        $fecha = $mysqli->real_escape_string($row['Ord_Fec']);
        $emp_id = (int)$emp_id;
        $usu_id = (int)$usu_id;
        $mysqli->query("INSERT INTO exa_compras_ordenes
            (id_orden, id_empresa, id_proyecto, id_rubro, monto_total, estado, id_usuario, fecha)
            VALUES ('$id', $emp_id, NULL, NULL, $monto, 'creada', $usu_id, '$fecha')
            ON DUPLICATE KEY UPDATE
                monto_total = $monto,
                fecha = '$fecha',
                id_empresa = $emp_id,
                estado = 'creada'");
        return $id;
    }

    function ppto_bridge_compras($mysqli, $id_orden, $evento) {
        ppto_bridge_cargar();
        return ppto_hook_ejecutar('ppto_hook_compras', $mysqli, $id_orden, $evento);
    }

    function ppto_bridge_compras_orden($mysqli, $ord_cod, $emp_id, $usu_id, $evento = 'crear') {
        return ppto_bridge_ejecutar_seguro(function () use ($mysqli, $ord_cod, $emp_id, $usu_id, $evento) {
            $conn = ppto_bridge_conn_desde_exa($mysqli);
            if (!$conn) {
                return null;
            }
            $id = ppto_bridge_sync_orden_compra($conn, (int)$ord_cod, (int)$emp_id, (int)$usu_id);
            if (!$id) {
                return null;
            }
            return ppto_bridge_compras($conn, $id, $evento);
        });
    }

    function ppto_bridge_tesoreria($mysqli, $id_pago, $evento, $params = array()) {
        ppto_bridge_cargar();
        return ppto_hook_ejecutar('ppto_hook_tesoreria', ppto_bridge_conn_desde_exa($mysqli), $id_pago, $evento, $params);
    }

    function ppto_bridge_rrhh($mysqli, $id_nomina, $evento, $params = array()) {
        ppto_bridge_cargar();
        return ppto_hook_ejecutar('ppto_hook_rrhh', ppto_bridge_conn_desde_exa($mysqli), $id_nomina, $evento, $params);
    }

    function ppto_bridge_inventario($mysqli, $id_movimiento, $evento, $params = array()) {
        ppto_bridge_cargar();
        return ppto_hook_ejecutar('ppto_hook_inventario', ppto_bridge_conn_desde_exa($mysqli), $id_movimiento, $evento, $params);
    }

    function ppto_bridge_activos($mysqli, $id_adquisicion, $evento, $params = array()) {
        ppto_bridge_cargar();
        return ppto_hook_ejecutar('ppto_hook_activos', ppto_bridge_conn_desde_exa($mysqli), $id_adquisicion, $evento, $params);
    }

    function ppto_bridge_contabilidad($mysqli, $id_asiento, $evento, $params = array()) {
        ppto_bridge_cargar();
        return ppto_hook_ejecutar('ppto_hook_contabilidad', ppto_bridge_conn_desde_exa($mysqli), $id_asiento, $evento, $params);
    }

    function ppto_bridge_relavera_manifiesto($mysqli, $man_cod, $proy_id = 'RCET-01') {
        return ppto_bridge_ejecutar_seguro(function () use ($mysqli, $man_cod, $proy_id) {
            ppto_bridge_cargar();
            $conn = ppto_bridge_conn_desde_exa($mysqli);
            if (!$conn || empty($man_cod) || !function_exists('ppto_sync_relavera_produccion')) {
                return null;
            }
            $emp_id = function_exists('ppto_resolve_emp_id_proyecto')
                ? ppto_resolve_emp_id_proyecto($conn, $proy_id, null)
                : null;
            ppto_sync_relavera_produccion($conn, $proy_id, $emp_id, (int)date('Y'));
            return array('status' => 'ok', 'man_cod' => $man_cod);
        });
    }
}
