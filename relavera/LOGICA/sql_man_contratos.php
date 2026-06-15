<?php

/**
 * SQL para contratos de plantas (manifiesto_contratos)
 *
 * @author Exa-Contable
 * @version 1.0
 * @package relavera.LOGICA
 */

function sentencias_contratos_planta($id, $Par_Sql) {
    switch ($id) {
        case 1:
            // Listar contratos con planta y usuario
            $where = " WHERE 1=1 ";
            $emp = isset($_SESSION['Ses_Emp_Cod']) ? addslashes($_SESSION['Ses_Emp_Cod']) : '';
            if ($emp !== '') {
                $where .= " AND (cliente.Emp_Cod = '$emp' OR manifiesto_plantas.Cli_Cod IS NULL) ";
            }
            if (isset($Par_Sql['Mco_Est']) && $Par_Sql['Mco_Est'] !== '') {
                $est = addslashes($Par_Sql['Mco_Est']);
                $where .= " AND mc.Mco_Est = '$est' ";
            }
            if (isset($Par_Sql['Mco_Vig']) && $Par_Sql['Mco_Vig'] !== '') {
                $vig = addslashes($Par_Sql['Mco_Vig']);
                if ($vig === 'V') {
                    $where .= " AND mc.Mco_Fca >= CURDATE() ";
                } elseif ($vig === 'C') {
                    $where .= " AND mc.Mco_Fca < CURDATE() ";
                }
            }
            if (isset($Par_Sql['search']) && trim($Par_Sql['search']) !== '') {
                $val = addslashes(trim($Par_Sql['search']));
                $filtro = isset($Par_Sql['filtro']) ? $Par_Sql['filtro'] : 'p';
                switch ($filtro) {
                    case 'n':
                        $where .= " AND mc.Mco_Num LIKE '%$val%' ";
                        break;
                    case 't':
                        $where .= " AND mc.Mco_Not LIKE '%$val%' ";
                        break;
                    case 'p':
                    default:
                        $where .= " AND manifiesto_plantas.Pla_Nom LIKE '%$val%' ";
                        break;
                }
            }
            $sql = "SELECT mc.Mco_Cod, mc.Pla_Cod, mc.Usu_Cod, mc.Mco_Num, mc.Mco_Not,
                        DATE_FORMAT(mc.Mco_Fap, '%Y-%m-%d') AS Mco_Fap,
                        DATE_FORMAT(mc.Mco_Fca, '%Y-%m-%d') AS Mco_Fca,
                        mc.Mco_Obs, mc.Mco_Est,
                        DATE_FORMAT(mc.Mco_Sys, '%Y-%m-%d %H:%i') AS Mco_Sys,
                        manifiesto_plantas.Pla_Nom,
                        CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) AS Usuario,
                        IF(mc.Mco_Est = 'A', 'Activo', 'Inactivo') AS Mco_Est_Des,
                        DATEDIFF(mc.Mco_Fca, CURDATE()) AS Mco_Dias_Cad,
                        CASE
                            WHEN mc.Mco_Fca < CURDATE() THEN 'C'
                            WHEN mc.Mco_Fca <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'P'
                            ELSE 'V'
                        END AS Mco_Vig_Cod,
                        CASE
                            WHEN mc.Mco_Fca < CURDATE() THEN 'Caducado'
                            WHEN mc.Mco_Fca <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Por caducar'
                            ELSE 'Vigente'
                        END AS Mco_Vig_Des
                    FROM manifiesto_contratos mc
                        INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = mc.Pla_Cod
                        LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
                        LEFT JOIN usuarios ON usuarios.Usu_Cod = mc.Usu_Cod
                        LEFT JOIN persona ON persona.Prs_Cod = usuarios.Prs_Cod
                    $where
                    ORDER BY mc.Mco_Fca DESC, mc.Mco_Cod DESC";
            return $sql;

        case 2:
            // Obtener un contrato por c�digo
            $Mco_Cod = isset($Par_Sql['Mco_Cod']) ? (int)$Par_Sql['Mco_Cod'] : 0;
            $sql = "SELECT mc.*,
                        DATE_FORMAT(mc.Mco_Fap, '%Y-%m-%d') AS Mco_Fap,
                        DATE_FORMAT(mc.Mco_Fca, '%Y-%m-%d') AS Mco_Fca,
                        manifiesto_plantas.Pla_Nom,
                        CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) AS Usuario
                    FROM manifiesto_contratos mc
                        INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = mc.Pla_Cod
                        LEFT JOIN usuarios ON usuarios.Usu_Cod = mc.Usu_Cod
                        LEFT JOIN persona ON persona.Prs_Cod = usuarios.Prs_Cod
                    WHERE mc.Mco_Cod = $Mco_Cod
                    LIMIT 1";
            return $sql;

        case 3:
            // Insertar contrato
            $Pla_Cod = (int)$Par_Sql['Pla_Cod'];
            $Usu_Cod = (int)$Par_Sql['Usu_Cod'];
            $Mco_Num = isset($Par_Sql['Mco_Num']) ? addslashes(trim($Par_Sql['Mco_Num'])) : '';
            $Mco_Not = addslashes(trim($Par_Sql['Mco_Not']));
            $Mco_Fap = addslashes($Par_Sql['Mco_Fap']);
            $Mco_Fca = addslashes($Par_Sql['Mco_Fca']);
            $Mco_Obs = isset($Par_Sql['Mco_Obs']) ? addslashes(trim($Par_Sql['Mco_Obs'])) : '';
            $Mco_Est = addslashes($Par_Sql['Mco_Est']);
            $numSql = ($Mco_Num !== '') ? "'$Mco_Num'" : "NULL";
            $obsSql = ($Mco_Obs !== '') ? "'$Mco_Obs'" : "NULL";
            $sql = "INSERT INTO manifiesto_contratos
                        (Pla_Cod, Usu_Cod, Mco_Num, Mco_Not, Mco_Fap, Mco_Fca, Mco_Obs, Mco_Est, Mco_Sys)
                    VALUES
                        ($Pla_Cod, $Usu_Cod, $numSql, '$Mco_Not', '$Mco_Fap', '$Mco_Fca', $obsSql, '$Mco_Est', NOW())";
            return $sql;

        case 4:
            // Actualizar contrato
            $Mco_Cod = (int)$Par_Sql['Mco_Cod'];
            $Pla_Cod = (int)$Par_Sql['Pla_Cod'];
            $Mco_Num = isset($Par_Sql['Mco_Num']) ? addslashes(trim($Par_Sql['Mco_Num'])) : '';
            $Mco_Not = addslashes(trim($Par_Sql['Mco_Not']));
            $Mco_Fap = addslashes($Par_Sql['Mco_Fap']);
            $Mco_Fca = addslashes($Par_Sql['Mco_Fca']);
            $Mco_Obs = isset($Par_Sql['Mco_Obs']) ? addslashes(trim($Par_Sql['Mco_Obs'])) : '';
            $Mco_Est = addslashes($Par_Sql['Mco_Est']);
            $numSql = ($Mco_Num !== '') ? "'$Mco_Num'" : "NULL";
            $obsSql = ($Mco_Obs !== '') ? "'$Mco_Obs'" : "NULL";
            $sql = "UPDATE manifiesto_contratos SET
                        Pla_Cod = $Pla_Cod,
                        Mco_Num = $numSql,
                        Mco_Not = '$Mco_Not',
                        Mco_Fap = '$Mco_Fap',
                        Mco_Fca = '$Mco_Fca',
                        Mco_Obs = $obsSql,
                        Mco_Est = '$Mco_Est'
                    WHERE Mco_Cod = $Mco_Cod";
            return $sql;

        case 5:
            // Inactivar contrato
            $Mco_Cod = (int)$Par_Sql['Mco_Cod'];
            $sql = "UPDATE manifiesto_contratos SET Mco_Est = 'I' WHERE Mco_Cod = $Mco_Cod";
            return $sql;

        case 6:
            // Plantas activas con b�squeda (modal)
            $emp = isset($_SESSION['Ses_Emp_Cod']) ? addslashes($_SESSION['Ses_Emp_Cod']) : '';
            $where = " WHERE manifiesto_plantas.Pla_Est = 'A'
                        AND (cliente.Emp_Cod = '$emp' OR manifiesto_plantas.Cli_Cod IS NULL) ";
            if (isset($Par_Sql['search']) && trim($Par_Sql['search']) !== '') {
                $val = addslashes(trim($Par_Sql['search']));
                $filtro = isset($Par_Sql['filtro']) ? $Par_Sql['filtro'] : 'p';
                if ($filtro === 'c') {
                    $where .= " AND (persona_cli.Prs_Nom LIKE '%$val%' OR persona_cli.Prs_Ape LIKE '%$val%' OR persona_cli.Prs_Ced LIKE '%$val%') ";
                } else {
                    $where .= " AND (manifiesto_plantas.Pla_Nom LIKE '%$val%' OR ciudad.Ciu_Des LIKE '%$val%' OR manifiesto_plantas.Pla_Lic LIKE '%$val%') ";
                }
            }
            $limits = isset($Par_Sql['limits']) ? $Par_Sql['limits'] : '';
            $sql = "SELECT manifiesto_plantas.Pla_Cod, manifiesto_plantas.Pla_Nom,
                        manifiesto_plantas.Pla_Lic,
                        ciudad.Ciu_Des,
                        cliente.Cli_Cod,
                        CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) AS Cliente,
                        persona_cli.Prs_Ced AS Cli_Ced
                    FROM manifiesto_plantas
                        LEFT JOIN ciudad ON ciudad.Ciu_Cod = manifiesto_plantas.Ciu_Cod
                        LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
                        LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                    $where
                    ORDER BY manifiesto_plantas.Pla_Nom
                    $limits";
            return $sql;

        case 7:
            // Contar plantas para paginaci�n del modal
            $emp = isset($_SESSION['Ses_Emp_Cod']) ? addslashes($_SESSION['Ses_Emp_Cod']) : '';
            $where = " WHERE manifiesto_plantas.Pla_Est = 'A'
                        AND (cliente.Emp_Cod = '$emp' OR manifiesto_plantas.Cli_Cod IS NULL) ";
            if (isset($Par_Sql['search']) && trim($Par_Sql['search']) !== '') {
                $val = addslashes(trim($Par_Sql['search']));
                $filtro = isset($Par_Sql['filtro']) ? $Par_Sql['filtro'] : 'p';
                if ($filtro === 'c') {
                    $where .= " AND (persona_cli.Prs_Nom LIKE '%$val%' OR persona_cli.Prs_Ape LIKE '%$val%' OR persona_cli.Prs_Ced LIKE '%$val%') ";
                } else {
                    $where .= " AND (manifiesto_plantas.Pla_Nom LIKE '%$val%' OR ciudad.Ciu_Des LIKE '%$val%' OR manifiesto_plantas.Pla_Lic LIKE '%$val%') ";
                }
            }
            $sql = "SELECT COUNT(*) AS total
                    FROM manifiesto_plantas
                        LEFT JOIN ciudad ON ciudad.Ciu_Cod = manifiesto_plantas.Ciu_Cod
                        LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
                        LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                    $where";
            return $sql;

        case 8:
            // Listar respaldos PDF del contrato
            $Mco_Cod = isset($Par_Sql['Mco_Cod']) ? (int)$Par_Sql['Mco_Cod'] : 0;
            $sql = "SELECT Mcd_Cod, Mco_Cod, Mcd_Tip, Mcd_Nom, Mcd_Url, Mcd_Est,
                        DATE_FORMAT(Mcd_Sys, '%Y-%m-%d %H:%i') AS Mcd_Sys
                    FROM manifiesto_contratos_docu
                    WHERE Mco_Cod = $Mco_Cod AND Mcd_Est = 'A'
                    ORDER BY Mcd_Cod DESC";
            return $sql;

        case 9:
            // Insertar respaldo
            $Mco_Cod = (int)$Par_Sql['Mco_Cod'];
            $Mcd_Tip = addslashes(trim($Par_Sql['Mcd_Tip']));
            $Mcd_Nom = addslashes(trim($Par_Sql['Mcd_Nom']));
            $Mcd_Url = addslashes(trim($Par_Sql['Mcd_Url']));
            $sql = "INSERT INTO manifiesto_contratos_docu (Mco_Cod, Mcd_Tip, Mcd_Nom, Mcd_Url, Mcd_Est, Mcd_Sys)
                    VALUES ($Mco_Cod, '$Mcd_Tip', '$Mcd_Nom', '$Mcd_Url', 'A', NOW())";
            return $sql;

        case 10:
            // Inactivar respaldo
            $Mcd_Cod = (int)$Par_Sql['Mcd_Cod'];
            $sql = "UPDATE manifiesto_contratos_docu SET Mcd_Est = 'I' WHERE Mcd_Cod = $Mcd_Cod";
            return $sql;

        case 11:
            // Obtener respaldo por codigo
            $Mcd_Cod = (int)$Par_Sql['Mcd_Cod'];
            $sql = "SELECT * FROM manifiesto_contratos_docu WHERE Mcd_Cod = $Mcd_Cod AND Mcd_Est = 'A' LIMIT 1";
            return $sql;
    }
}
