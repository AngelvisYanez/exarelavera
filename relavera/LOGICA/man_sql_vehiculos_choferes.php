<?php
/**
 * Sentencias SQL para el módulo de alta de vehículos y choferes en Relavera
 * Maneja todo el CRUD de consultas, conteo, inserción y modificación.
 * @author Sistema EXA
 * @version 1.1
 */
function sentencias_vehiculos_choferes($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 1:
            // Obtener catálogo de empresas de transporte activas para la empresa del usuario
            $sql = "SELECT Mat_Cod, Mat_Des 
                    FROM manifiesto_transporte 
                    WHERE Emp_Cod = '$Par_Sql[0]' AND Mat_Est = 'A'
                    ORDER BY Mat_Des ASC";
            break;

        case 2:
            // Listar Choferes para el Grid (Ambiente 1) - Con conteo incorporado
            $search = "";
            if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                if ($Par_Sql['op_opciones'] == 'c') {
                    $search = " AND persona.Prs_Ced LIKE '%$searchTerm%'";
                } else if ($Par_Sql['op_opciones'] == 'd') {
                    $search = " AND (CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$searchTerm%' OR persona.Prs_Nom LIKE '%$searchTerm%' OR persona.Prs_Ape LIKE '%$searchTerm%')";
                }
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total 
                        FROM chofer
                        INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                        INNER JOIN manifiesto_chofer ON manifiesto_chofer.Cho_Cod = chofer.Cho_Cod
                        WHERE chofer.Emp_Cod = '$Par_Sql[0]' 
                          AND manifiesto_chofer.Pla_Cod = '$Par_Sql[Pla_Cod]' 
                          AND chofer.Cho_Est = 'A' AND IFNULL(chofer.Cho_Tip, '') != 'CM' $search";
            } else {
                $sql = "SELECT chofer.*, persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced,
                               CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as nombre,
                               manifiesto_chofer.Pla_Cod
                        FROM chofer
                        INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                        INNER JOIN manifiesto_chofer ON manifiesto_chofer.Cho_Cod = chofer.Cho_Cod
                        WHERE chofer.Emp_Cod = '$Par_Sql[0]' 
                          AND manifiesto_chofer.Pla_Cod = '$Par_Sql[Pla_Cod]' 
                          AND chofer.Cho_Est = 'A' AND IFNULL(chofer.Cho_Tip, '') != 'CM' $search
                        ORDER BY persona.Prs_Ape ASC, persona.Prs_Nom ASC " . $Par_Sql['limits'];
            }
            break;

        case 3:
            // Listar Vehículos para el Grid (Ambiente 1) - Con conteo incorporado
            $search = "";
            if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                if ($Par_Sql['op_opciones'] == 'p') {
                    $search = " AND vehiculo.Veh_Pla LIKE '%$searchTerm%'";
                }
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total 
                        FROM manifiesto_vehiculo
                        INNER JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto_vehiculo.Veh_Cod
                        WHERE vehiculo.Veh_Est = 'A' 
                          AND vehiculo.Emp_Cod = '$Par_Sql[0]' 
                          AND manifiesto_vehiculo.Pla_Cod = '$Par_Sql[Pla_Cod]' 
                          AND IFNULL(vehiculo.Veh_Tip, '') != 'VM' $search";
            } else {
                $sql = "SELECT vehiculo.Veh_Cod, vehiculo.Veh_Pla, vehiculo.Veh_Mar, vehiculo.Veh_Col, vehiculo.Veh_Cap, vehiculo.Veh_Tit, vehiculo.Prv_Cod,
                               CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as empresa_transporte
                        FROM manifiesto_vehiculo
                        INNER JOIN vehiculo ON vehiculo.Veh_Cod = manifiesto_vehiculo.Veh_Cod
                        LEFT JOIN proveedor ON proveedor.Prv_Cod = vehiculo.Prv_Cod
                        LEFT JOIN persona ON persona.Prs_Cod = proveedor.Prs_Cod
                        WHERE vehiculo.Veh_Est = 'A' 
                          AND vehiculo.Emp_Cod = '$Par_Sql[0]' 
                          AND manifiesto_vehiculo.Pla_Cod = '$Par_Sql[Pla_Cod]' 
                          AND IFNULL(vehiculo.Veh_Tip, '') != 'VM' $search
                        ORDER BY vehiculo.Veh_Pla ASC " . $Par_Sql['limits'];
            }
            break;

        case 4:
            // INSERT Persona
            $sql = "INSERT INTO persona (Ciu_Cod, Ide_Cod, Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, Prs_Esc, Prs_Tel, Prs_Cel, Prs_San, Prs_Est)
                    VALUES ('0', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', 'M', 'S', '$Par_Sql[5]', '$Par_Sql[5]', '$Par_Sql[6]', 'A')";
            break;

        case 5:
            // UPDATE Persona
            $sql = "UPDATE persona 
                    SET Prs_Nom = '$Par_Sql[1]', Prs_Ape = '$Par_Sql[2]', Prs_Tel = '$Par_Sql[3]', Prs_Cel = '$Par_Sql[3]', Prs_San = '$Par_Sql[4]'
                    WHERE Prs_Cod = '$Par_Sql[0]'";
            break;

        case 6:
            // INSERT Chofer
            $sql = "INSERT INTO chofer (Prs_Cod, Emp_Cod, Cho_Tli, Cho_Cli, Cho_Tel, Cho_Tsa, Cho_Mae, Cho_Est)
                    VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '', 'A')";
            break;

        case 7:
            // UPDATE Chofer
            $sql = "UPDATE chofer 
                    SET Cho_Tli = '$Par_Sql[1]', Cho_Cli = '$Par_Sql[2]', Cho_Tel = '$Par_Sql[3]', Cho_Tsa = '$Par_Sql[4]'
                    WHERE Cho_Cod = '$Par_Sql[0]'";
            break;

        case 8:
            // INSERT Relación manifiesto_chofer
            $sql = "INSERT IGNORE INTO manifiesto_chofer (Cho_Cod, Pla_Cod)
                    VALUES ('$Par_Sql[0]', '$Par_Sql[1]')";
            break;

        case 9:
            // INSERT Vehículo
            $sql = "INSERT INTO vehiculo (Veh_Mar, Veh_Pla, Veh_Col, Veh_Cap, Veh_Tit, Emp_Cod, Veh_Tip, Mat_Cod, Veh_Est, Prv_Cod)
                    VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '', NULL, 'A', " . (empty($Par_Sql[7]) ? 'NULL' : "'$Par_Sql[7]'") . ")";
            break;

        case 10:
            // UPDATE Vehículo
            $sql = "UPDATE vehiculo 
                    SET Veh_Mar = '$Par_Sql[1]', Veh_Col = '$Par_Sql[2]', Veh_Cap = '$Par_Sql[3]', Veh_Tit = '$Par_Sql[4]', Mat_Cod = NULL, Prv_Cod = " . (empty($Par_Sql[6]) ? 'NULL' : "'$Par_Sql[6]'") . "
                    WHERE Veh_Cod = '$Par_Sql[0]'";
            break;

        case 11:
            // INSERT Relación manifiesto_vehiculo
            $sql = "INSERT IGNORE INTO manifiesto_vehiculo (Veh_Cod, Pla_Cod)
                    VALUES ('$Par_Sql[0]', '$Par_Sql[1]')";
            break;
    }
    return $sql;
}
