<?php

function sentencias_datos_choferes_vehiculos($Nro_Sql, $Par_Sql)
{
    $sql = "";
    switch ($Nro_Sql) {

        case 1:
            // Empresas de transporte activas por empresa
            $sql = "SELECT Mat_Cod, Mat_Des 
                    FROM manifiesto_transporte 
                    WHERE Emp_Cod = '$Par_Sql[0]' AND Mat_Est = 'A' 
                    ORDER BY Mat_Des ASC";
            break;

        case 2:
            // Plantas de beneficio activas
            $sql = "SELECT Pla_Cod, Pla_Nom 
                    FROM manifiesto_plantas 
                    WHERE Pla_Est = 'A' 
                    ORDER BY Pla_Nom ASC";
            break;

        case 3:
            // Listar Choferes por Emp_Cod
            $searchChofer = "";

            if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                if ($Par_Sql['op_opciones'] == 'c') {
                    $searchChofer .= " AND persona.Prs_Ced LIKE '%$searchTerm%'";
                } else if ($Par_Sql['op_opciones'] == 'd') {
                    $searchChofer .= " AND (CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) LIKE '%$searchTerm%' OR persona.Prs_Nom LIKE '%$searchTerm%' OR persona.Prs_Ape LIKE '%$searchTerm%')";
                }
            }

            // Filtros opcionales múltiples por datos de Cédula, Licencia e Incompletud
            $mostrarDatos = array();
            if (!empty($Par_Sql['mostrar_datos'])) {
                if (is_array($Par_Sql['mostrar_datos'])) {
                    $mostrarDatos = $Par_Sql['mostrar_datos'];
                } else if (is_string($Par_Sql['mostrar_datos'])) {
                    $mostrarDatos = array_filter(array_map('trim', explode(',', $Par_Sql['mostrar_datos'])));
                }
            }

            if (!empty($Par_Sql['foto_cedula']) && $Par_Sql['foto_cedula'] == '1' && !in_array('con_foto_cedula', $mostrarDatos) && !in_array('foto_cedula', $mostrarDatos)) {
                $mostrarDatos[] = 'con_foto_cedula';
            }
            if (!empty($Par_Sql['foto_licencia']) && $Par_Sql['foto_licencia'] == '1' && !in_array('con_foto_licencia', $mostrarDatos) && !in_array('foto_licencia', $mostrarDatos)) {
                $mostrarDatos[] = 'con_foto_licencia';
            }

            foreach ($mostrarDatos as $filtro) {
                switch ($filtro) {
                    case 'incompletos':
                        $searchChofer .= " AND (IFNULL(chofer.Cho_Doc_Ced,'') = '' OR IFNULL(chofer.Cho_Doc_Ced_Rev,'') = '' OR (IFNULL(chofer.Cho_Tli,'') != 'NP' AND (IFNULL(chofer.Cho_Img_Lic_Anv,'') = '' OR IFNULL(chofer.Cho_Img_Lic_Rev,'') = '')) OR IFNULL(chofer.Cho_Tel,'') = '' OR IFNULL(persona.Prs_Ced,'') = '')";
                        break;
                    case 'sin_foto_cedula':
                        $searchChofer .= " AND (IFNULL(chofer.Cho_Doc_Ced,'') = '' OR IFNULL(chofer.Cho_Doc_Ced_Rev,'') = '')";
                        break;
                    case 'con_foto_cedula':
                    case 'foto_cedula':
                        $searchChofer .= " AND (IFNULL(chofer.Cho_Doc_Ced,'') != '' AND IFNULL(chofer.Cho_Doc_Ced_Rev,'') != '')";
                        break;
                    case 'sin_foto_licencia':
                        $searchChofer .= " AND (IFNULL(chofer.Cho_Img_Lic_Anv,'') = '' OR IFNULL(chofer.Cho_Img_Lic_Rev,'') = '')";
                        break;
                    case 'con_foto_licencia':
                    case 'foto_licencia':
                        $searchChofer .= " AND (IFNULL(chofer.Cho_Img_Lic_Anv,'') != '' AND IFNULL(chofer.Cho_Img_Lic_Rev,'') != '')";
                        break;
                    case 'licencia_vencida':
                        $searchChofer .= " AND (chofer.Cho_Cli IS NOT NULL AND chofer.Cho_Cli < CURDATE() AND IFNULL(chofer.Cho_Tli,'') != 'NP')";
                        break;
                    case 'sin_cedula':
                        $searchChofer .= " AND (IFNULL(persona.Prs_Ced,'') = '')";
                        break;
                }
            }

            $baseUnion = "
                SELECT 
                    'CHOFER' as tipo_registro,
                    CONCAT('C_', chofer.Cho_Cod) as grid_id,
                    chofer.Cho_Cod,
                    NULL as MVis_Cod,
                    persona.Prs_Cod, 
                    persona.Prs_Nom, 
                    persona.Prs_Ape, 
                    persona.Prs_Ced, 
                    persona.Prs_Fec,
                    CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre,
                    chofer.Cho_Tli,
                    chofer.Cho_Cli,
                    chofer.Cho_Tsa,
                    IFNULL(chofer.Cho_Tel, persona.Prs_Tel) as Cho_Tel,
                    chofer.Cho_Est,
                    chofer.Cho_Doc_Ced,
                    chofer.Cho_Doc_Ced_Rev,
                    chofer.Cho_Doc_Vot,
                    chofer.Cho_Doc_Fot,
                    chofer.Cho_Img_Lic_Anv,
                    chofer.Cho_Img_Lic_Rev,
                    chofer.Cho_Doc_Ldi,
                    chofer.Cho_Doc_Ant,
                    chofer.Cho_Doc_San,
                    capaci.Cap_Bas_Adj,
                    capaci.Cap_Mat_Adj,
                    capaci.Cap_Otr_Adj
                FROM chofer
                INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                LEFT JOIN manifiesto_chofer_capaci capaci ON capaci.Cho_Cod = chofer.Cho_Cod
                INNER JOIN (
                    SELECT MIN(c.Cho_Cod) as min_cho_cod
                    FROM chofer c
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    WHERE c.Emp_Cod = '$Par_Sql[0]' AND c.Cho_Est != 'I'
                    GROUP BY p.Prs_Ced
                ) min_c ON min_c.min_cho_cod = chofer.Cho_Cod
                WHERE chofer.Emp_Cod = '$Par_Sql[0]' AND chofer.Cho_Est != 'I' $searchChofer

                /* 
                -- TABLA DE VISITANTES COMENTADA (SOLO CHOFERES)
                UNION ALL

                SELECT 
                    'VISITANTE' as tipo_registro,
                    CONCAT('V_', mv.MVis_Cod) as grid_id,
                    NULL as Cho_Cod,
                    mv.MVis_Cod,
                    persona.Prs_Cod, 
                    persona.Prs_Nom, 
                    persona.Prs_Ape, 
                    persona.Prs_Ced, 
                    persona.Prs_Fec,
                    CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre,
                    'VISITANTE' as Cho_Tli,
                    NULL as Cho_Cli,
                    mv.MVis_Tsa as Cho_Tsa,
                    IFNULL(persona.Prs_Tel, mv.MVis_Tem) as Cho_Tel,
                    mv.MVis_Est as Cho_Est,
                    mv.MVis_Doc_Ced as Cho_Doc_Ced,
                    mv.MVis_Doc_Ced_Rev as Cho_Doc_Ced_Rev,
                    NULL as Cho_Img_Lic_Anv,
                    NULL as Cho_Img_Lic_Rev
                FROM manifiesto_visitante mv
                INNER JOIN persona ON persona.Prs_Cod = mv.Prs_Cod
                WHERE mv.Emp_Cod = '$Par_Sql[0]' AND mv.MVis_Est != 'I'
                */
            ";

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total FROM ($baseUnion) u";
            } else {
                $sql = "SELECT u.* FROM ($baseUnion) u ORDER BY u.nombre ASC " . $Par_Sql['limits'];
            }
            break;

        case 4:
            // Listar Vehículos con información de planta y empresa de transporte
            $search = "";
            if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                if ($Par_Sql['op_opciones'] == 'p') {
                    $search = " AND vehiculo.Veh_Pla LIKE '%$searchTerm%'";
                } else if ($Par_Sql['op_opciones'] == 'pn') {
                    $search = " AND manifiesto_plantas.Pla_Nom LIKE '%$searchTerm%'";
                } else if ($Par_Sql['op_opciones'] == 'pl') {
                    $search = " AND manifiesto_plantas.Pla_Lic LIKE '%$searchTerm%'";
                } else if ($Par_Sql['op_opciones'] == 'c') {
                    $search = " AND (manifiesto_transporte.Mat_Mae LIKE '%$searchTerm%' OR manifiesto_transporte.Mat_Des LIKE '%$searchTerm%')";
                }
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(DISTINCT vehiculo.Veh_Cod) as total 
                        FROM vehiculo
                        LEFT JOIN manifiesto_vehiculo ON manifiesto_vehiculo.Veh_Cod = vehiculo.Veh_Cod
                        LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_vehiculo.Pla_Cod
                        LEFT JOIN manifiesto_transporte ON manifiesto_transporte.Mat_Cod = vehiculo.Mat_Cod
                        WHERE vehiculo.Emp_Cod = '$Par_Sql[0]' 
                          AND vehiculo.Veh_Est = 'A' $search";
            } else {
                $sql = "SELECT vehiculo.*, 
                               manifiesto_plantas.Pla_Nom, manifiesto_plantas.Pla_Cod,
                               manifiesto_transporte.Mat_Des
                        FROM vehiculo
                        LEFT JOIN manifiesto_vehiculo ON manifiesto_vehiculo.Veh_Cod = vehiculo.Veh_Cod
                        LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_vehiculo.Pla_Cod
                        LEFT JOIN manifiesto_transporte ON manifiesto_transporte.Mat_Cod = vehiculo.Mat_Cod
                        WHERE vehiculo.Emp_Cod = '$Par_Sql[0]' 
                          AND vehiculo.Veh_Est = 'A' $search
                        ORDER BY vehiculo.Veh_Pla ASC " . $Par_Sql['limits'];
            }
            break;

        case 5:
            // Listar Empresas de Transporte
            $search = "";
            if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                if ($Par_Sql['op_opciones'] == 'n') {
                    $search = " AND manifiesto_transporte.Mat_Des LIKE '%$searchTerm%'";
                } else if ($Par_Sql['op_opciones'] == 'm') {
                    $search = " AND manifiesto_transporte.Mat_Mae LIKE '%$searchTerm%'";
                }
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total 
                        FROM manifiesto_transporte 
                        WHERE Emp_Cod = '$Par_Sql[0]' 
                          AND Mat_Est = 'A' $search";
            } else {
                $sql = "SELECT * 
                        FROM manifiesto_transporte 
                        WHERE Emp_Cod = '$Par_Sql[0]' 
                          AND Mat_Est = 'A' $search
                        ORDER BY Mat_Des ASC " . $Par_Sql['limits'];
            }
            break;

        case 6:
            // Buscar persona por cédula
            $sql = "SELECT * FROM persona WHERE Prs_Ced = '$Par_Sql[0]' LIMIT 1";
            break;

        case 7:
            // Buscar vehículos por placa para validar duplicidad
            $sql = "SELECT * FROM vehiculo WHERE Veh_Pla = '$Par_Sql[0]' AND Veh_Est = 'A' LIMIT 1";
            break;

        case 8:
            // Obtener Chofer Completo por ID para Edición en Modal (Con capacitaciones y planta)
            $sql = "SELECT chofer.*, 
                           persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Fec, persona.Prs_Cor, persona.Prs_Tel as Prs_Tel_Base, persona.Prs_Dir as Prs_Dir_Base,
                           manifiesto_chofer.Pla_Cod,
                           manifiesto_chofer_capaci.Cap_Cod, manifiesto_chofer_capaci.Cap_Bas_Obli, 
                           manifiesto_chofer_capaci.Cap_Bas_Fec, manifiesto_chofer_capaci.Cap_Bas_Vig, 
                           manifiesto_chofer_capaci.Cap_Bas_Adj, manifiesto_chofer_capaci.Cap_Mat_Peli, 
                           manifiesto_chofer_capaci.Cap_Mat_Fec, manifiesto_chofer_capaci.Cap_Mat_Vig, 
                           manifiesto_chofer_capaci.Cap_Mat_Adj, manifiesto_chofer_capaci.Cap_Otr_Adj,
                           CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre
                    FROM chofer
                    INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                    LEFT JOIN manifiesto_chofer ON manifiesto_chofer.Cho_Cod = chofer.Cho_Cod
                    LEFT JOIN manifiesto_chofer_capaci ON manifiesto_chofer_capaci.Cho_Cod = chofer.Cho_Cod
                    WHERE chofer.Cho_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 9:
            // Obtener relación manifiesto_chofer por Cho_Cod
            $sql = "SELECT * FROM manifiesto_chofer WHERE Cho_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 10:
            // Obtener relación manifiesto_chofer_capaci por Cho_Cod
            $sql = "SELECT * FROM manifiesto_chofer_capaci WHERE Cho_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 11:
            // Obtener relación manifiesto_vehiculo por Veh_Cod
            $sql = "SELECT * FROM manifiesto_vehiculo WHERE Veh_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 12:
            // Obtener Vehículo Completo por ID con datos de matrícula y planta
            $sql = "SELECT vehiculo.*, 
                           manifiesto_plantas.Pla_Nom, manifiesto_plantas.Pla_Cod,
                           manifiesto_transporte.Mat_Des,
                           manifiesto_matricula_vehiculo.Mat_Cod as Mat_Mat_Cod,
                           manifiesto_matricula_vehiculo.Mat_Pro_Nom, manifiesto_matricula_vehiculo.Mat_Pro_Id,
                           manifiesto_matricula_vehiculo.Mat_Pro_Prv, manifiesto_matricula_vehiculo.Mat_Pro_Can,
                           manifiesto_matricula_vehiculo.Mat_Pro_Dir, manifiesto_matricula_vehiculo.Mat_Pro_Tel,
                           manifiesto_matricula_vehiculo.Mat_Ctr, manifiesto_matricula_vehiculo.Mat_Ttr,
                           manifiesto_matricula_vehiculo.Mat_Aop, manifiesto_matricula_vehiculo.Mat_Otr,
                           manifiesto_matricula_vehiculo.Mat_Dis, manifiesto_matricula_vehiculo.Mat_Ava,
                           manifiesto_matricula_vehiculo.Mat_Vma, manifiesto_matricula_vehiculo.Mat_Fco,
                           manifiesto_matricula_vehiculo.Mat_Dig, manifiesto_matricula_vehiculo.Mat_Nma,
                           manifiesto_matricula_vehiculo.Mat_Fem, manifiesto_matricula_vehiculo.Mat_Fve,
                           manifiesto_matricula_vehiculo.Mat_Lem, manifiesto_matricula_vehiculo.Mat_Pla,
                           manifiesto_matricula_vehiculo.Mat_Pan, manifiesto_matricula_vehiculo.Mat_Ano,
                           manifiesto_matricula_vehiculo.Mat_Nmo, manifiesto_matricula_vehiculo.Mat_Cha,
                           manifiesto_matricula_vehiculo.Mat_Ram, manifiesto_matricula_vehiculo.Mat_Mar,
                           manifiesto_matricula_vehiculo.Mat_Mde, manifiesto_matricula_vehiculo.Mat_Cil,
                           manifiesto_matricula_vehiculo.Mat_Amo, manifiesto_matricula_vehiculo.Mat_Cve,
                           manifiesto_matricula_vehiculo.Mat_Tip as Mat_Mat_Tip, manifiesto_matricula_vehiculo.Mat_Npa,
                           manifiesto_matricula_vehiculo.Mat_Ton, manifiesto_matricula_vehiculo.Mat_Ori,
                           manifiesto_matricula_vehiculo.Mat_Tco, manifiesto_matricula_vehiculo.Mat_Car,
                           manifiesto_matricula_vehiculo.Mat_Tpe, manifiesto_matricula_vehiculo.Mat_Co1,
                           manifiesto_matricula_vehiculo.Mat_Co2, manifiesto_matricula_vehiculo.Mat_Ort,
                           manifiesto_matricula_vehiculo.Mat_Rem, manifiesto_matricula_vehiculo.Mat_Obs
                    FROM vehiculo
                    LEFT JOIN manifiesto_vehiculo ON manifiesto_vehiculo.Veh_Cod = vehiculo.Veh_Cod
                    LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_vehiculo.Pla_Cod
                    LEFT JOIN manifiesto_transporte ON manifiesto_transporte.Mat_Cod = vehiculo.Mat_Cod
                    LEFT JOIN manifiesto_matricula_vehiculo ON manifiesto_matricula_vehiculo.Veh_Cod = vehiculo.Veh_Cod
                    WHERE vehiculo.Veh_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 13:
            // Obtener relación manifiesto_matricula_vehiculo por Veh_Cod
            $sql = "SELECT * FROM manifiesto_matricula_vehiculo WHERE Veh_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 14:
            // Obtener nombre de planta por Pla_Cod
            $sql = "SELECT Pla_Cod, Pla_Nom FROM manifiesto_plantas WHERE Pla_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 15:
            // Buscar si existe Chofer activo por cédula y empresa
            $sql = "SELECT chofer.*, 
                           persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Fec, persona.Prs_Cor, persona.Prs_Tel as Prs_Tel_Base, persona.Prs_Dir as Prs_Dir_Base,
                           manifiesto_chofer.Pla_Cod,
                           manifiesto_chofer_capaci.Cap_Cod, manifiesto_chofer_capaci.Cap_Bas_Obli, 
                           manifiesto_chofer_capaci.Cap_Bas_Fec, manifiesto_chofer_capaci.Cap_Bas_Vig, 
                           manifiesto_chofer_capaci.Cap_Bas_Adj, manifiesto_chofer_capaci.Cap_Mat_Peli, 
                           manifiesto_chofer_capaci.Cap_Mat_Fec, manifiesto_chofer_capaci.Cap_Mat_Vig, 
                           manifiesto_chofer_capaci.Cap_Mat_Adj, manifiesto_chofer_capaci.Cap_Otr_Adj,
                           CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre
                    FROM chofer
                    INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                    LEFT JOIN manifiesto_chofer ON manifiesto_chofer.Cho_Cod = chofer.Cho_Cod
                    LEFT JOIN manifiesto_chofer_capaci ON manifiesto_chofer_capaci.Cho_Cod = chofer.Cho_Cod
                    WHERE chofer.Emp_Cod = '$Par_Sql[0]' 
                      AND persona.Prs_Ced = '$Par_Sql[1]'
                      AND chofer.Cho_Est != 'I' 
                    ORDER BY chofer.Cho_Cod ASC LIMIT 1";
            break;

        case 16:
            // Buscar si existe Visitante activo por cédula y empresa (último registro para herencia de datos)
            $sql = "SELECT visitante.*, 
                           persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Fec, persona.Prs_Cor, persona.Prs_Tel as Prs_Tel_Base, persona.Prs_Dir as Prs_Dir_Base,
                           CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre
                    FROM manifiesto_visitante visitante
                    INNER JOIN persona ON persona.Prs_Cod = visitante.Prs_Cod
                    WHERE visitante.Emp_Cod = '$Par_Sql[0]' 
                      AND persona.Prs_Ced = '$Par_Sql[1]'
                      AND visitante.MVis_Est != 'I' 
                    ORDER BY visitante.MVis_Cod DESC LIMIT 1";
            break;

        case 17:
            // Obtener Visitante Completo por ID
            $sql = "SELECT visitante.*, 
                           persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Fec, persona.Prs_Cor, persona.Prs_Tel as Prs_Tel_Base, persona.Prs_Dir as Prs_Dir_Base,
                           CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre
                    FROM manifiesto_visitante visitante
                    INNER JOIN persona ON persona.Prs_Cod = visitante.Prs_Cod
                    WHERE visitante.MVis_Cod = '$Par_Sql[0]' LIMIT 1";
            break;

        case 18:
            // Listar eventos de manifiesto
            $searchEv = "";
            if (!empty($Par_Sql['search'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                $searchEv = " AND (Man_ENom LIKE '%$searchTerm%')";
            }
            if (!empty($Par_Sql['Man_Eve'])) {
                $searchEv .= " AND Man_Eve = '" . addslashes($Par_Sql['Man_Eve']) . "'";
            }
            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total
                        FROM manifiesto_evento
                        WHERE IFNULL(Man_EEst, 'A') != 'I' $searchEv";
            } else {
                $sql = "SELECT Man_Eve,
                               Man_ENom,
                               Man_EFei,
                               Man_EFef,
                               Man_Ehor,
                               Man_Vig,
                               IFNULL(Man_EEst, 'A') AS Man_EEst,
                               (SELECT COUNT(*) FROM manifiesto_visitante mv
                                 WHERE mv.Man_Eve = manifiesto_evento.Man_Eve
                                   AND mv.Emp_Cod = '" . addslashes(isset($Par_Sql[0]) ? $Par_Sql[0] : '') . "'
                                   AND IFNULL(mv.MVis_Est, 'A') != 'I') AS total_visitantes
                        FROM manifiesto_evento
                        WHERE IFNULL(Man_EEst, 'A') != 'I' $searchEv
                        ORDER BY Man_Vig DESC, Man_EFei DESC, Man_ENom ASC " . $Par_Sql['limits'];
            }
            break;

        case 19:
            // Listar visitantes/personas por evento
            $searchVis = "";
            $manEve = !empty($Par_Sql['Man_Eve']) ? addslashes($Par_Sql['Man_Eve']) : '';
            if ($manEve === '') {
                $sql = empty($Par_Sql['limits'])
                    ? "SELECT 0 as total"
                    : "SELECT * FROM manifiesto_visitante WHERE 1=0";
                break;
            }
            if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                if ($Par_Sql['op_opciones'] == 'c') {
                    $searchVis .= " AND persona.Prs_Ced LIKE '%$searchTerm%'";
                } else {
                    $searchVis .= " AND (CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) LIKE '%$searchTerm%' OR persona.Prs_Nom LIKE '%$searchTerm%' OR persona.Prs_Ape LIKE '%$searchTerm%')";
                }
            } elseif (!empty($Par_Sql['search'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                $searchVis .= " AND (persona.Prs_Ced LIKE '%$searchTerm%' OR CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) LIKE '%$searchTerm%')";
            }

            $baseVis = "
                SELECT mv.MVis_Cod,
                       mv.Man_Eve,
                       mv.MVis_Nac,
                       mv.MVis_Eci,
                       mv.MVis_Tsa,
                       mv.MVis_Nem,
                       mv.MVis_Tem,
                       mv.MVis_Obs,
                       mv.MVis_Est,
                       persona.Prs_Cod,
                       persona.Prs_Ced,
                       persona.Prs_Nom,
                       persona.Prs_Ape,
                       IFNULL(persona.Prs_Tel, mv.MVis_Tem) as Prs_Tel,
                       persona.Prs_Cor,
                       persona.Prs_Dir,
                       persona.Prs_Fec,
                       CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre,
                       me.Man_ENom
                FROM manifiesto_visitante mv
                INNER JOIN persona ON persona.Prs_Cod = mv.Prs_Cod
                LEFT JOIN manifiesto_evento me ON me.Man_Eve = mv.Man_Eve
                WHERE mv.Emp_Cod = '$Par_Sql[0]'
                  AND mv.Man_Eve = '$manEve'
                  AND IFNULL(mv.MVis_Est, 'A') != 'I'
                  $searchVis
            ";

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total FROM ($baseVis) u";
            } else {
                $sql = "SELECT u.* FROM ($baseVis) u ORDER BY u.nombre ASC " . $Par_Sql['limits'];
            }
            break;

        case 20:
            // Buscar si existe Visitante activo por cédula, empresa y EVENTO específico
            $manEve = !empty($Par_Sql[2]) ? addslashes($Par_Sql[2]) : '';
            $sql = "SELECT visitante.*, 
                           persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Fec, persona.Prs_Cor, persona.Prs_Tel as Prs_Tel_Base, persona.Prs_Dir as Prs_Dir_Base,
                           CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) as nombre
                    FROM manifiesto_visitante visitante
                    INNER JOIN persona ON persona.Prs_Cod = visitante.Prs_Cod
                    WHERE visitante.Emp_Cod = '$Par_Sql[0]' 
                      AND persona.Prs_Ced = '$Par_Sql[1]'
                      AND visitante.Man_Eve = '$manEve'
                      AND visitante.MVis_Est != 'I' 
                    ORDER BY visitante.MVis_Cod DESC LIMIT 1";
            break;
    }
    return $sql;
}
