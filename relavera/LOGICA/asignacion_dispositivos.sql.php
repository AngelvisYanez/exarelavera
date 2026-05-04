<?php
/**
 * Sentencias SQL para el módulo de Asignación de Dispositivos
 * Corregido: Eliminando referencia a Usu_Usr que no existe en la tabla usuarios
 */

function sentencias_asignacion_dispositivos($id, $Par_Sql) {
    switch ($id) {
        case 1: // Listado de dispositivos asignados con estado de vínculo de navegador
            $usuario_id = intval($Par_Sql['usuario_id']);
            $sql = "SELECT ui.UsInv_Cod as id, inv.InvDis_Cod, inv.mac_address, inv.InvDis_Nom, inv.InvDis_Est as estado,
                           du.DisUsr_Cod as vinculado_id, du.DisUsr_Fec as fecha_vinculo, du.DisUsr_IP as ip_vinculo
                    FROM usuario_inventario ui
                    INNER JOIN inventario_dispositivos inv ON ui.InvDis_Cod = inv.InvDis_Cod
                    LEFT JOIN dispositivos_usuario du ON (ui.InvDis_Cod = du.InvDis_Cod AND du.Usu_Cod = ui.UsInv_Usu AND du.DisUsr_Est = 'A')
                    WHERE ui.UsInv_Usu = $usuario_id AND ui.UsInv_Est = 'A'";
            return $sql;

        case 2: // Dispositivos disponibles (no asignados al usuario seleccionado)
            $usuario_id = intval($Par_Sql['usuario_id']);
            $sql = "SELECT InvDis_Cod, mac_address, InvDis_Nom
                    FROM inventario_dispositivos
                    WHERE InvDis_Est = 'A' 
                    AND InvDis_Cod NOT IN (
                        SELECT InvDis_Cod FROM usuario_inventario WHERE UsInv_Usu = $usuario_id AND UsInv_Est = 'A'
                    )
                    ORDER BY InvDis_Nom ASC";
            return $sql;

        case 3: // Listado de usuarios activos con perfiles específicos (Agrupado por persona para evitar duplicados)
            $sql = "SELECT MAX(u.Usu_Cod) as id, TRIM(CONCAT(IFNULL(p.Prs_Ape,''), ' ', IFNULL(p.Prs_Nom, ''))) as nombre 
                    FROM usuarios u 
                    INNER JOIN persona p ON u.Prs_Cod = p.Prs_Cod
                    INNER JOIN usuarperfi up ON u.Usu_Cod = up.Usu_Cod
                    INNER JOIN perfiles pf ON up.Per_Cod = pf.Per_Cod
                    WHERE u.Usu_Est = 'A' 
                    AND pf.Per_Des IN ('Gerente', 'Tecnico', 'Contador', 'Auxiliar', 'Guardia', 'RRHH', 'Plantas', 'Admin_Oper', 'Contralor', 'Aux_contable')
                    AND (p.Prs_Ape IS NOT NULL AND TRIM(p.Prs_Ape) <> '' OR p.Prs_Nom IS NOT NULL AND TRIM(p.Prs_Nom) <> '')
                    GROUP BY p.Prs_Cod
                    ORDER BY p.Prs_Ape ASC, p.Prs_Nom ASC";
            return $sql;

        case 4: // Asignar dispositivo
            $usuario_id = intval($Par_Sql['usuario_id']);
            $inventario_id = intval($Par_Sql['inventario_id']);
            $sql = "INSERT INTO usuario_inventario (UsInv_Usu, InvDis_Cod, UsInv_Fec, UsInv_Est) 
                    VALUES ($usuario_id, $inventario_id, NOW(), 'A')";
            return $sql;

        case 5: // Quitar asignación (Soft delete)
            $id = intval($Par_Sql['id']);
            $sql = "UPDATE usuario_inventario SET UsInv_Est = 'I' WHERE UsInv_Cod = $id";
            return $sql;
            
        case 6: // Re-activar asignación si ya existía
            $usuario_id = intval($Par_Sql['usuario_id']);
            $inventario_id = intval($Par_Sql['inventario_id']);
            $sql = "UPDATE usuario_inventario SET UsInv_Est = 'A', UsInv_Fec = NOW() 
                    WHERE UsInv_Usu = $usuario_id AND InvDis_Cod = $inventario_id";
            return $sql;
            
        case 7: // Verificar si ya existe registro de asignación
            $usuario_id = intval($Par_Sql['usuario_id']);
            $inventario_id = intval($Par_Sql['inventario_id']);
            $sql = "SELECT UsInv_Cod, UsInv_Est FROM usuario_inventario 
                    WHERE UsInv_Usu = $usuario_id AND InvDis_Cod = $inventario_id";
            return $sql;

        case 8: // Desvincular navegador de un equipo (Limpiar cupo)
            $vinculado_id = intval($Par_Sql['vinculado_id']);
            // Marcamos como inactivo el vínculo del navegador
            $sql = "UPDATE dispositivos_usuario SET DisUsr_Est = 'I' WHERE DisUsr_Cod = $vinculado_id";
            return $sql;
    }
}
?>
