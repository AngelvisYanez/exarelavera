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
                $sql = "SELECT COUNT(DISTINCT chofer.Prs_Cod) as total 
                        FROM chofer
                        INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                        WHERE chofer.Emp_Cod = '$Par_Sql[0]' 
                          AND chofer.Cho_Est = 'A' AND IFNULL(chofer.Cho_Tip, '') = 'OP' $search";
            } else {
                $sql = "SELECT chofer.*, persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced,
                               CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as nombre
                        FROM chofer
                        INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                        WHERE chofer.Emp_Cod = '$Par_Sql[0]' 
                          AND chofer.Cho_Est = 'A' AND IFNULL(chofer.Cho_Tip, '') = 'OP' $search
                        GROUP BY chofer.Prs_Cod
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
                        FROM vehiculo
                        WHERE vehiculo.Veh_Est = 'A' 
                          AND vehiculo.Emp_Cod = '$Par_Sql[0]' 
                          AND IFNULL(vehiculo.Veh_Tip, '') != 'VM' $search";
            } else {
                $sql = "SELECT vehiculo.Veh_Cod, vehiculo.Veh_Pla, vehiculo.Veh_Mar, vehiculo.Veh_Col, vehiculo.Veh_Cap, vehiculo.Veh_Tit, vehiculo.Prv_Cod, vehiculo.Veh_Val,
                               CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as empresa_transporte
                        FROM vehiculo
                        LEFT JOIN proveedore ON proveedore.Prv_Cod = vehiculo.Prv_Cod
                        LEFT JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
                        WHERE vehiculo.Veh_Est = 'A' 
                          AND vehiculo.Emp_Cod = '$Par_Sql[0]' 
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
            $sql = "INSERT INTO chofer (Prs_Cod, Emp_Cod, Cho_Tli, Cho_Cli, Cho_Tel, Cho_Tsa, Cho_Mae, Cho_Est, Cho_Tip)
                    VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '', 'A', 'OP')";
            break;

        case 7:
            // UPDATE Chofer
            $sql = "UPDATE chofer 
                    SET Cho_Tli = '$Par_Sql[1]', Cho_Cli = '$Par_Sql[2]', Cho_Tel = '$Par_Sql[3]', Cho_Tsa = '$Par_Sql[4]', Cho_Tip = 'OP'
                    WHERE Cho_Cod = '$Par_Sql[0]'";
            break;

        case 8:
            // No se usa manifiesto_chofer
            $sql = "SELECT 1";
            break;

        case 9:
            // INSERT Vehículo
            $sql = "INSERT INTO vehiculo (Veh_Mar, Veh_Pla, Veh_Col, Veh_Cap, Veh_Tit, Emp_Cod, Veh_Tip, Mat_Cod, Veh_Est, Prv_Cod, Veh_Val, Veh_Adi)
                    VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '', NULL, 'A', " . (empty($Par_Sql[7]) ? 'NULL' : "'$Par_Sql[7]'") . ", " . (empty($Par_Sql[8]) ? "'0.00'" : "'$Par_Sql[8]'") . ", '$Par_Sql[9]')";
            break;

        case 10:
            // UPDATE Vehículo
            $sql = "UPDATE vehiculo 
                    SET Veh_Mar = '$Par_Sql[1]', Veh_Col = '$Par_Sql[2]', Veh_Cap = '$Par_Sql[3]', Veh_Tit = '$Par_Sql[4]', Mat_Cod = NULL, Prv_Cod = " . (empty($Par_Sql[6]) ? 'NULL' : "'$Par_Sql[6]'") . ", Veh_Val = " . (empty($Par_Sql[7]) ? "'0.00'" : "'$Par_Sql[7]'") . ", Veh_Adi = '$Par_Sql[8]'
                    WHERE Veh_Cod = '$Par_Sql[0]'";
            break;

        case 11:
            // No se usa manifiesto_vehiculo
            $sql = "SELECT 1";
            break;

        case 12:
            // Buscar Vehículo por placa
            $sql = "SELECT Veh_Mar, Veh_Col, Veh_Tit, Prv_Cod, Veh_Val, Veh_Adi 
                    FROM vehiculo 
                    WHERE Veh_Pla = '$Par_Sql[0]' AND Emp_Cod = '$Par_Sql[1]' AND Veh_Est = 'A' LIMIT 1";
            break;

        case 13:
            // Buscar nombre y cédula de proveedor por ID
            $sql = "SELECT persona.Prs_Ced, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as Prv_Nom 
                    FROM proveedore 
                    INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod 
                    WHERE proveedore.Prv_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 14:
            // Buscar Proveedor por cédula
            $sql = "SELECT proveedore.Prv_Cod, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as Prv_Nom 
                    FROM proveedore 
                    INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod 
                    WHERE persona.Prs_Ced = '$Par_Sql[0]' LIMIT 1";
            break;

        case 15:
            // Insertar persona (Para registro rápido de proveedor)
            $sql = "INSERT INTO persona (Prs_Ced, Prs_Sex, Prs_Ape, Prs_Nom, Ciu_Cod, Prs_Dir, Ide_Cod, Prs_Tel, Prs_Cor, Prs_Est) VALUES ("
                . "'$Par_Sql[Prs_Ced]',"
                . "'$Par_Sql[Prs_Sex]',"
                . "'$Par_Sql[Prs_Ape]',"
                . "'$Par_Sql[Prs_Nom]',"
                . "'$Par_Sql[Ciu_Cod]',"
                . "'$Par_Sql[Prs_Dir]',"
                . "'$Par_Sql[Ide_Cod]',"
                . "'$Par_Sql[Prs_Tel]',"
                . "'$Par_Sql[Prs_Cor]',"
                . "'A');";
            break;

        case 16:
            // Insertar proveedor (Para registro rápido de proveedor)
            $sql = "INSERT INTO proveedore (Emp_Cod, Prs_Cod, Prv_Com, Prv_Tic, Prv_Esp, Prv_Con, Prv_Reg, Prv_Ris, Prv_Gct, Prv_Rim_Emp, Prv_Rim_Np, Prv_Ag_Ret, Prv_Est) VALUES ("
                . "'$Par_Sql[Emp_Cod]',"
                . "'$Par_Sql[Prs_Cod]',"
                . "'$Par_Sql[Prv_Com]',"
                . "'$Par_Sql[Prv_Tic]',"
                . "'$Par_Sql[Prv_Esp]',"
                . "'$Par_Sql[Prv_Con]',"
                . "'$Par_Sql[Prv_Reg]',"
                . "'$Par_Sql[Prv_Ris]',"
                . "'$Par_Sql[Prv_Gct]',"
                . "'$Par_Sql[Prv_Rim_Emp]',"
                . "'$Par_Sql[Prv_Rim_Np]',"
                . "'$Par_Sql[Prv_Ag_Ret]',"
                . "'A');";
            break;
        case 17:
            // Obtener marcas únicas
            $sql = "SELECT DISTINCT Veh_Mar FROM vehiculo WHERE Veh_Mar IS NOT NULL AND Veh_Mar != '' ORDER BY Veh_Mar ASC";
            break;

        case 18:
            // Obtener colores únicos
            $sql = "SELECT DISTINCT Veh_Col FROM vehiculo WHERE Veh_Col IS NOT NULL AND Veh_Col != '' ORDER BY Veh_Col ASC";
            break;

        case 19:
            // Obtener tipos/títulos únicos
            $sql = "SELECT DISTINCT Veh_Tit FROM vehiculo WHERE Veh_Tit IS NOT NULL AND Veh_Tit != '' ORDER BY Veh_Tit ASC";
            break;
        case 20:
            $sql = "SELECT Pla_Cod FROM manifiesto_usuario WHERE Usu_Cod = '$Par_Sql[0]' LIMIT 1";
            break;
        case 21:
            $sql = "SELECT Pla_Cod FROM manifiesto_plantas mp LEFT JOIN cliente c ON c.Cli_Cod = mp.Cli_Cod WHERE mp.Pla_Est = 'A' AND c.Emp_Cod = '$Par_Sql[0]' LIMIT 1";
            break;
        case 22:
            $sql = "SELECT Pla_Nom FROM manifiesto_plantas WHERE Pla_Cod = '$Par_Sql[0]' LIMIT 1";
            break;
        case 23:
            $sql = "SELECT Ciu_Cod, Ciu_Des FROM ciudad WHERE Ciu_Des != '' ORDER BY Ciu_Des";
            break;
        case 24:
            $sql = "SELECT Prs_Cod, Prs_Nom, Prs_Ape, Prs_Tel, Prs_San FROM persona WHERE Prs_Ced = '$Par_Sql[0]' LIMIT 1";
            break;
        case 25:
            $sql = "SELECT Cho_Tli, Cho_Cli FROM chofer WHERE Prs_Cod = '$Par_Sql[0]' LIMIT 1";
            break;
        case 26:
            $sql = "SELECT Prs_Cod FROM persona WHERE Prs_Ced = '$Par_Sql[0]' LIMIT 1";
            break;
        case 27:
            $sql = "SELECT Prv_Cod FROM proveedore WHERE Prs_Cod = '$Par_Sql[0]' AND Emp_Cod = '$Par_Sql[1]' LIMIT 1";
            break;
        case 28:
            $sql = "SELECT Cho_Cod, IFNULL(Cho_Tip, '') as Cho_Tip FROM chofer WHERE Prs_Cod = '$Par_Sql[0]' AND Emp_Cod = '$Par_Sql[1]' LIMIT 1";
            break;
        case 29:
            $sql = "SELECT Veh_Cod, IFNULL(Veh_Tip, '') as Veh_Tip FROM vehiculo WHERE Veh_Pla = '$Par_Sql[0]' AND Emp_Cod = '$Par_Sql[1]' AND Veh_Est = 'A' LIMIT 1";
            break;
    }
    return $sql;
}
