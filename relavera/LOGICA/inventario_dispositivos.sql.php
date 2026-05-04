<?php
/**
 * Sentencias SQL para el módulo de Inventario de Dispositivos (MAC)
 * 
 * @author Antigravity
 * @version 1.2
 * @package relavera.LOGICA
 */

function sentencias_inventario_dispositivos($id, $Par_Sql) {
    switch ($id) {
        case 1: // Listado para jqGrid
            $where = "1=1";
            if (isset($Par_Sql['mac']) && !empty($Par_Sql['mac'])) {
                $val = addslashes($Par_Sql['mac']);
                $where .= " AND mac_address LIKE '%$val%'";
            }
            if (isset($Par_Sql['nombre']) && !empty($Par_Sql['nombre'])) {
                $val = addslashes($Par_Sql['nombre']);
                $where .= " AND InvDis_Nom LIKE '%$val%'";
            }
            if (isset($Par_Sql['search']) && !empty($Par_Sql['search'])) {
                $val = addslashes($Par_Sql['search']);
                $where .= " AND (mac_address LIKE '%$val%' OR InvDis_Nom LIKE '%$val%')";
            }
            if (isset($Par_Sql['estado']) && !empty($Par_Sql['estado'])) {
                $where .= " AND InvDis_Est = '" . addslashes($Par_Sql['estado']) . "'";
            }
            
            $sql = "SELECT InvDis_Cod, mac_address, InvDis_Nom, InvDis_Des, InvDis_Tipo, InvDis_Est, InvDis_Fec 
                    FROM inventario_dispositivos 
                    WHERE $where";
            
            // Si hay límites de paginación
            if (isset($Par_Sql['limits'])) {
                $sql .= " " . $Par_Sql['limits'];
            }
            
            return $sql;

        case 2: // Validar MAC duplicada
            $mac = strtoupper(addslashes($Par_Sql['mac_address']));
            $id_exclude = isset($Par_Sql['id']) ? intval($Par_Sql['id']) : 0;
            $sql = "SELECT COUNT(*) as total 
                    FROM inventario_dispositivos 
                    WHERE mac_address = '$mac'";
            if ($id_exclude > 0) {
                $sql .= " AND InvDis_Cod <> $id_exclude";
            }
            return $sql;

        case 3: // Insertar dispositivo
            $mac = strtoupper(addslashes($Par_Sql['mac_address']));
            $nombre = addslashes($Par_Sql['nombre_equipo']);
            $desc = addslashes($Par_Sql['descripcion']);
            $tipo = addslashes($Par_Sql['tipo_equipo']);
            $estado = addslashes($Par_Sql['estado']);
            $sql = "INSERT INTO inventario_dispositivos (mac_address, InvDis_Nom, InvDis_Des, InvDis_Tipo, InvDis_Est, InvDis_Fec) 
                    VALUES ('$mac', '$nombre', '$desc', '$tipo', '$estado', NOW())";
            return $sql;

        case 4: // Actualizar dispositivo
            $id = intval($Par_Sql['id']);
            $mac = strtoupper(addslashes($Par_Sql['mac_address']));
            $nombre = addslashes($Par_Sql['nombre_equipo']);
            $desc = addslashes($Par_Sql['descripcion']);
            $tipo = addslashes($Par_Sql['tipo_equipo']);
            $estado = addslashes($Par_Sql['estado']);
            $sql = "UPDATE inventario_dispositivos SET 
                    mac_address = '$mac', 
                    InvDis_Nom = '$nombre', 
                    InvDis_Des = '$desc', 
                    InvDis_Tipo = '$tipo',
                    InvDis_Est = '$estado' 
                    WHERE InvDis_Cod = $id";
            return $sql;

        case 5: // Cambiar estado (Inactivar/Activar)
            $id = intval($Par_Sql['id']);
            $estado = addslashes($Par_Sql['estado']);
            $sql = "UPDATE inventario_dispositivos SET InvDis_Est = '$estado' WHERE InvDis_Cod = $id";
            return $sql;
            
        case 6: // Contar registros totales para paginación
            $where = "1=1";
            if (isset($Par_Sql['mac']) && !empty($Par_Sql['mac'])) {
                $val = addslashes($Par_Sql['mac']);
                $where .= " AND mac_address LIKE '%$val%'";
            }
            if (isset($Par_Sql['nombre']) && !empty($Par_Sql['nombre'])) {
                $val = addslashes($Par_Sql['nombre']);
                $where .= " AND InvDis_Nom LIKE '%$val%'";
            }
            if (isset($Par_Sql['search']) && !empty($Par_Sql['search'])) {
                $val = addslashes($Par_Sql['search']);
                $where .= " AND (mac_address LIKE '%$val%' OR InvDis_Nom LIKE '%$val%')";
            }
            if (isset($Par_Sql['estado']) && !empty($Par_Sql['estado'])) {
                $where .= " AND InvDis_Est = '" . addslashes($Par_Sql['estado']) . "'";
            }
            $sql = "SELECT COUNT(*) as total FROM inventario_dispositivos WHERE $where";
            return $sql;

        case 7: // Verificación masiva de MACs
            $lista_macs = $Par_Sql['lista_macs']; // Array de strings
            $macs_str = "'" . implode("','", $lista_macs) . "'";
            $sql = "SELECT mac_address FROM inventario_dispositivos WHERE mac_address IN ($macs_str)";
            return $sql;

        case 8: // Inserción masiva agrupada
            $filas = $Par_Sql['filas']; // Array de strings formateados
            $valores = implode(",", $filas);
            $sql = "INSERT INTO inventario_dispositivos (mac_address, InvDis_Nom, InvDis_Des, InvDis_Tipo, InvDis_Est, InvDis_Fec) 
                    VALUES $valores";
            return $sql;
    }
}
?>
