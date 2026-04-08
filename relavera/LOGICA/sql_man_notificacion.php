<?php

/**
 * Notificaciones masivas WhatsApp — man_adm_notificacion.php, man_user_notificacion.php
 * Casos: 1 plantas, 2 choferes, 3 usuarios Relavera activos sin planta (Usu_Adm='N'); 4 Prs_Cod; 5 Usu_Cod; 6 Usu_Cod+Prs_Cod (modal)
 * Filtros vía Par_Sql: filtro_nombre, filtro_cedula (1, 2, 3); filtro_planta (solo 2)
 */

function sentencias_man_notificacion($id, $Par_Sql)
{
    $sql = '';
    switch ($id) {
        case 1:
            // Plantas activas; filtro nombre (planta o cliente) y cédula/RUC del cliente
            $ids = isset($Par_Sql['ids']) ? trim($Par_Sql['ids']) : '';
            $filtro_pla = ($ids !== '') ? " AND mp.Pla_Cod IN ($ids)" : '';

            $f_nombre = isset($Par_Sql['filtro_nombre']) ? trim($Par_Sql['filtro_nombre']) : '';
            $f_cedula = isset($Par_Sql['filtro_cedula']) ? trim($Par_Sql['filtro_cedula']) : '';
            $extra = '';
            if ($f_nombre !== '') {
                $fn = addslashes($f_nombre);
                $extra .= " AND (mp.Pla_Nom LIKE '%$fn%' OR CONCAT(IFNULL(persona_cli.Prs_Nom,''),' ',IFNULL(persona_cli.Prs_Ape,'')) LIKE '%$fn%')";
            }
            if ($f_cedula !== '') {
                $fc = addslashes($f_cedula);
                $extra .= " AND (persona_cli.Prs_Ced LIKE '%$fc%')";
            }

            $sql = "SELECT mp.Pla_Cod,
                           mp.Pla_Nom,
                           mp.Pla_Wat,
                           mpp.Pep_Tel AS Pep_Tel_Admin,
                           adm.Prs_Tel AS Prs_Tel_Admin,
                           adm.Prs_Te2 AS Prs_Te2_Admin,
                           TRIM(CONCAT(IFNULL(persona_cli.Prs_Nom,''),' ',IFNULL(persona_cli.Prs_Ape,''))) AS Cliente
                    FROM manifiesto_plantas mp
                    LEFT JOIN cliente ON cliente.Cli_Cod = mp.Cli_Cod
                    LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                    LEFT JOIN manifiesto_personal_planta AS mpp
                        ON mpp.Pla_Cod = mp.Pla_Cod AND mpp.Pep_Tip = 'AP' AND mpp.Pep_Est = 'A'
                    LEFT JOIN persona AS adm ON adm.Prs_Cod = mpp.Prs_Cod
                    WHERE mp.Pla_Est = 'A'
                      $filtro_pla
                      $extra
                    ORDER BY mp.Pla_Nom";
            break;

        case 2:
            // Choferes; filtro planta (nombre), nombre y cédula del chofer
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $ids = isset($Par_Sql['ids']) ? trim($Par_Sql['ids']) : '';
            $filtro_cho = ($ids !== '') ? " AND chofer.Cho_Cod IN ($ids)" : '';

            $f_planta = isset($Par_Sql['filtro_planta']) ? trim($Par_Sql['filtro_planta']) : '';
            $f_nombre = isset($Par_Sql['filtro_nombre']) ? trim($Par_Sql['filtro_nombre']) : '';
            $f_cedula = isset($Par_Sql['filtro_cedula']) ? trim($Par_Sql['filtro_cedula']) : '';
            $extra = '';
            if ($f_planta !== '') {
                $fp = addslashes($f_planta);
                $extra .= " AND EXISTS (SELECT 1 FROM manifiesto_chofer mc_f
                            INNER JOIN manifiesto_plantas mpl ON mpl.Pla_Cod = mc_f.Pla_Cod AND mpl.Pla_Est = 'A'
                            WHERE mc_f.Cho_Cod = chofer.Cho_Cod AND mpl.Pla_Nom LIKE '%$fp%')";
            }
            if ($f_nombre !== '') {
                $fn = addslashes($f_nombre);
                $extra .= " AND (CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) LIKE '%$fn%' OR persona.Prs_Nom LIKE '%$fn%' OR persona.Prs_Ape LIKE '%$fn%')";
            }
            if ($f_cedula !== '') {
                $fc = addslashes($f_cedula);
                $extra .= " AND (persona.Prs_Ced LIKE '%$fc%')";
            }

            $sql = "SELECT chofer.Cho_Cod,
                           CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) AS Chofer,
                           persona.Prs_Ced AS Cho_Ced,
                           COALESCE(NULLIF(TRIM(chofer.Cho_Tel),''), NULLIF(TRIM(persona.Prs_Tel),''), persona.Prs_Tel) AS Telefono,
                           (SELECT GROUP_CONCAT(DISTINCT mp2.Pla_Nom ORDER BY mp2.Pla_Nom SEPARATOR ', ')
                            FROM manifiesto_chofer mc2
                            INNER JOIN manifiesto_plantas mp2 ON mp2.Pla_Cod = mc2.Pla_Cod AND mp2.Pla_Est = 'A'
                            WHERE mc2.Cho_Cod = chofer.Cho_Cod
                           ) AS Pla_Nom
                    FROM chofer
                    INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                    WHERE chofer.Emp_Cod = $Emp_Cod
                      AND chofer.Cho_Est = 'A'
                      $filtro_cho
                      $extra
                    ORDER BY persona.Prs_Ape, persona.Prs_Nom";
            break;

        case 3:
            // Usuarios Relavera: activos, Usu_Adm='N', sucursal empresa, sin asignación a planta (manifiesto_usuario + planta activa)
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $ids = isset($Par_Sql['ids']) ? trim($Par_Sql['ids']) : '';
            $filtro_usu = ($ids !== '') ? " AND u.Usu_Cod IN ($ids)" : '';

            $f_nombre = isset($Par_Sql['filtro_nombre']) ? trim($Par_Sql['filtro_nombre']) : '';
            $f_cedula = isset($Par_Sql['filtro_cedula']) ? trim($Par_Sql['filtro_cedula']) : '';
            $extra = '';
            if ($f_nombre !== '') {
                $fn = addslashes($f_nombre);
                $extra .= " AND (CONCAT(p.Prs_Nom,' ',p.Prs_Ape) LIKE '%$fn%' OR p.Prs_Nom LIKE '%$fn%' OR p.Prs_Ape LIKE '%$fn%')";
            }
            if ($f_cedula !== '') {
                $fc = addslashes($f_cedula);
                $extra .= " AND (p.Prs_Ced LIKE '%$fc%' OR u.Usu_Ced LIKE '%$fc%')";
            }

            $sql = "SELECT u.Usu_Cod,
                           p.Prs_Cod,
                           p.Prs_Ced AS Prs_Ced,
                           CONCAT(p.Prs_Nom,' ',p.Prs_Ape) AS Usuario,
                           u.Usu_Ntf AS Usu_Ntf,
                           TRIM(IFNULL(p.Prs_Tel,'')) AS Prs_Tel,
                           COALESCE(NULLIF(TRIM(p.Prs_Tel),''), NULLIF(TRIM(p.Prs_Te2),'')) AS Telefono,
                           s.Suc_Des AS Sucursal
                    FROM usuarios u
                    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                    INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $Emp_Cod
                    WHERE u.Usu_Est = 'A'
                      AND s.Suc_Est = 'A'
                      AND u.Usu_Adm = 'N'
                      AND NOT EXISTS (
                          SELECT 1 FROM manifiesto_usuario mu
                          INNER JOIN cliente c_mu ON c_mu.Cli_Cod = mu.Cli_Cod AND c_mu.Emp_Cod = $Emp_Cod
                          INNER JOIN manifiesto_plantas mpl ON mpl.Pla_Cod = mu.Pla_Cod AND mpl.Pla_Est = 'A'
                          WHERE mu.Usu_Cod = u.Usu_Cod
                            AND mu.Pla_Cod IS NOT NULL AND mu.Pla_Cod > 0
                      )
                      $filtro_usu
                      $extra
                    ORDER BY p.Prs_Ape, p.Prs_Nom, u.Usu_Cod";
            break;

        case 4:
            // Prs_Cod: usuario activo empresa y sin vínculo con planta (misma regla que caso 3)
            $Prs_Cod = isset($Par_Sql['Prs_Cod']) ? intval($Par_Sql['Prs_Cod']) : 0;
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $sql = "SELECT u.Usu_Cod
                    FROM usuarios u
                    INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $Emp_Cod
                    WHERE u.Prs_Cod = $Prs_Cod AND u.Usu_Est = 'A' AND s.Suc_Est = 'A'
                      AND u.Usu_Adm = 'N'
                      AND NOT EXISTS (
                          SELECT 1 FROM manifiesto_usuario mu
                          INNER JOIN cliente c_mu ON c_mu.Cli_Cod = mu.Cli_Cod AND c_mu.Emp_Cod = $Emp_Cod
                          INNER JOIN manifiesto_plantas mpl ON mpl.Pla_Cod = mu.Pla_Cod AND mpl.Pla_Est = 'A'
                          WHERE mu.Usu_Cod = u.Usu_Cod
                            AND mu.Pla_Cod IS NOT NULL AND mu.Pla_Cod > 0
                      )
                    LIMIT 1";
            break;

        case 5:
            // Usu_Cod: usuario activo empresa y sin vínculo con planta
            $Usu_Cod = isset($Par_Sql['Usu_Cod']) ? intval($Par_Sql['Usu_Cod']) : 0;
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $sql = "SELECT u.Usu_Cod
                    FROM usuarios u
                    INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $Emp_Cod
                    WHERE u.Usu_Cod = $Usu_Cod
                      AND u.Usu_Est = 'A'
                      AND s.Suc_Est = 'A'
                      AND u.Usu_Adm = 'N'
                      AND NOT EXISTS (
                          SELECT 1 FROM manifiesto_usuario mu
                          INNER JOIN cliente c_mu ON c_mu.Cli_Cod = mu.Cli_Cod AND c_mu.Emp_Cod = $Emp_Cod
                          INNER JOIN manifiesto_plantas mpl ON mpl.Pla_Cod = mu.Pla_Cod AND mpl.Pla_Est = 'A'
                          WHERE mu.Usu_Cod = u.Usu_Cod
                            AND mu.Pla_Cod IS NOT NULL AND mu.Pla_Cod > 0
                      )
                    LIMIT 1";
            break;

        case 6:
            // Usu_Cod + Prs_Cod coherentes: usuario activo empresa, sin planta, persona coincide
            $Usu_Cod = isset($Par_Sql['Usu_Cod']) ? intval($Par_Sql['Usu_Cod']) : 0;
            $Prs_Cod = isset($Par_Sql['Prs_Cod']) ? intval($Par_Sql['Prs_Cod']) : 0;
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $sql = "SELECT u.Usu_Cod
                    FROM usuarios u
                    INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $Emp_Cod
                    WHERE u.Usu_Cod = $Usu_Cod
                      AND u.Prs_Cod = $Prs_Cod
                      AND u.Usu_Est = 'A'
                      AND s.Suc_Est = 'A'
                      AND u.Usu_Adm = 'N'
                      AND NOT EXISTS (
                          SELECT 1 FROM manifiesto_usuario mu
                          INNER JOIN cliente c_mu ON c_mu.Cli_Cod = mu.Cli_Cod AND c_mu.Emp_Cod = $Emp_Cod
                          INNER JOIN manifiesto_plantas mpl ON mpl.Pla_Cod = mu.Pla_Cod AND mpl.Pla_Est = 'A'
                          WHERE mu.Usu_Cod = u.Usu_Cod
                            AND mu.Pla_Cod IS NOT NULL AND mu.Pla_Cod > 0
                      )
                    LIMIT 1";
            break;
    }

    return $sql;
}
