<?php

/**
 * Retorna consulta sql a ejecutarse
 *
 * @author Exa-Contable
 * @version 1.0
 * @package manifiesto.LOGICA
 */

function sentencias_manifiesto($id, $Par_Sql)
{
    switch ($id) {
        case 1:
            $sql = "SELECT persona.Prs_Cod, Prs_Ced, CONCAT(Prs_Nom, '', Prs_Ape) as Cliente, Ide_Cod, Prs_Est,
                        cliente.Cli_Cod, cliente.Prs_Cod, cliente.Emp_Cod, manifiesto_usuario.Cli_Cod, 
                        manifiesto_usuario.Usu_Cod, manifiesto_usuario.Pla_Cod, 
                        manifiesto_plantas.Pla_Nom, manifiesto_plantas.Pla_Lic
                    FROM persona
                        INNER JOIN cliente ON persona.Prs_Cod = cliente.Prs_Cod
                        INNER JOIN manifiesto_usuario ON cliente.Cli_Cod = manifiesto_usuario.Cli_Cod
                        LEFT JOIN manifiesto_plantas ON manifiesto_usuario.Pla_Cod = manifiesto_plantas.Pla_Cod
                    WHERE
                        Prs_Est = 'A' AND
                        manifiesto_usuario.Usu_Cod = $Par_Sql[0];";
            // ChromePhp::log($sql);
            return $sql;
            // break;
        case 2:
            $sql = "SELECT Pag_Cod, Pag_Des, Pag_Abr FROM tipos_pago WHERE Pag_Abr='TRF' OR (Pag_Abr='DEP' AND Pag_Des='Deposito') OR Pag_Abr='RET';";
            return $sql;
            // break;
        case 3:
            $sql = "SELECT * from tipo_asien where Tia_Ini = 'I' order by Tia_Abr;";
            return $sql;
            // break;
            //obtener el ultimo anticipo de clientes
        case 4:
            $sql = "SELECT if(max(Ant_Cod) IS NULL,0,max(Ant_Cod)) AS sig
                    FROM anticipos_clientes, cliente
                        -- INNER JOIN 
                    WHERE anticipos_clientes.Cli_Cod = cliente.Cli_Cod AND
                            cliente.Emp_Cod='$_SESSION[Ses_Emp_Cod]';";
            return $sql;
            // break;
            //obtener el ultimo valor de ant_doc
        case 5:
            $sql = "SELECT Ant_Doc FROM anticipos_clientes WHERE Ant_Cod=$Par_Sql[0];";
            return $sql;
            // break;
            //para seleccionar el plande cuentas correspondiente a los pagos de anticipos a clientes
        case 6:
            $sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
                    FROM det_plan, tipo_param, plan_param
                    WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
                                AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
                                AND tipo_param.Tpa_Abr='ANC'
                                AND det_plan.Pld_Est='A' AND
                                det_plan.Pla_Cod = (SELECT max(plan_cuenta.Pla_Cod)
                                                    FROM plan_cuenta 
                                                    WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]')";
            // echo $sql;
            return $sql;
            //seleccionar plan de cuentas y No. de cuenta del banco para anticipos con cheque de la empresa
        case 7:
            $sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, banco.Ban_Cue
							FROM det_plan, banco
							WHERE
								det_plan.Pla_Cod = (SELECT max(plan_cuenta.Pla_Cod) 
													FROM plan_cuenta 
													WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]') AND
								banco.Ban_Tip = '$Par_Sql[Ban_Tip]' AND
								banco.Pld_Cod=det_plan.Pld_Cod AND
                                banco.Ban_Est = 'A';";
            // ChromePhp::log($sql);
            return $sql;
            // break;
            //sentencia para obtener el periodo contable de la fecha en la que se realiza la consulta
        case 8:
            $sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des,bancos.Bak_Cod, bancos.Bak_Des
						FROM det_plan, tipo_param, plan_param, bancos
						WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
							AND bancos.Bak_Est = 'A'
							AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
							AND tipo_param.Tpa_Abr='CCH'
							AND det_plan.Pld_Est='A' AND
							det_plan.Pla_Cod = (SELECT max(plan_cuenta.Pla_Cod) FROM plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]');";
            // echo $sql;
            return $sql;
            // break;
        case 9:
            $sql = "SELECT Bak_Cod, Bak_Des, Bak_Est FROM bancos WHERE Bak_Est = 'A';";
            return $sql;
            // break;
        case 10:
            // Insertar nuevo manifiesto_anticipo (insert)
            // Asegúrate de recibir todos los campos requeridos en $Par_Sql (como array asociativo)
            // Validar que Cli_Cod no esté vacío (es clave foránea obligatoria)
            $Cli_Cod = isset($Par_Sql['Cli_Cod']) ? trim($Par_Sql['Cli_Cod']) : '';

            // Si Cli_Cod está vacío, retornar false para indicar error
            // La validación debe hacerse antes de llegar aquí
            if (empty($Cli_Cod)) {
                return false; // Retornar false para indicar error de validación
            }

            // Sanitizar todos los valores antes de insertar
            $Ban_Cod = addslashes($Par_Sql['Ban_Cod']);
            $Bak_Cod = addslashes($Par_Sql['Bak_Cod']);
            $Usu_Cod = addslashes($Par_Sql['Usu_Cod']);
            $Cli_Cod = addslashes($Cli_Cod);
            $Pla_Cod = addslashes($Par_Sql['Pla_Cod']);
            $Ama_Val = addslashes($Par_Sql['Ama_Val']);
            $Pag_Cod = addslashes($Par_Sql['Pag_Cod']);
            $Ama_Doc = addslashes($Par_Sql['Ama_Doc']);
            $Ama_Fec = addslashes($Par_Sql['Ama_Fec']);
            $Ama_Obs = empty($Par_Sql['Ama_Obs']) ? '' : addslashes($Par_Sql['Ama_Obs']);

            $Ama_Img = isset($Par_Sql['Ama_Img']) && $Par_Sql['Ama_Img'] != '' ? "'" . addslashes($Par_Sql['Ama_Img']) . "'" : 'NULL';

            $sql = "INSERT INTO manifiesto_anticipo (
                        Ban_Cod, Bak_Cod, Usu_Cod, Cli_Cod, Pla_Cod, Ama_Val, Pag_Cod, Ama_Tip, Ama_Doc, Ama_Fec, Ama_Obs, Ama_Est, Ama_Img
                    ) VALUES (
                        '$Ban_Cod', '$Bak_Cod',
                        '$Usu_Cod', '$Cli_Cod',
                        '$Pla_Cod', '$Ama_Val',
                        '$Pag_Cod', 'P',
                        '$Ama_Doc', '$Ama_Fec', 
                        '$Ama_Obs', 'A', $Ama_Img
                    );";
            return $sql;
            // break;
        case 11:
            // Parámetros esperados: filtro, estado, search, filtroAnt
            $wherefiltro = '';
            $whereestado = '';
            $wherefecha = '';
            $wherecliente = '';
            $wheretipopago = '';

            // Asumimos que los parámetros llegan en el array $Par_Sql (revisa cómo lo envías desde el frontend)
            if (isset($Par_Sql['filtro']) && isset($Par_Sql['search']) && $Par_Sql['search'] !== '') {
                $val = addslashes($Par_Sql['search']);
                switch ($Par_Sql['filtro']) {
                    case 'cl': // Por Cliente
                        $wherefiltro = " AND (CONCAT(IFNULL(persona_cli.Prs_Nom,''), ' ', IFNULL(persona_cli.Prs_Ape,'')) LIKE '%$val%')";
                        break;
                    case 'c': // Por Cedula/RUC
                        $wherefiltro = " AND persona_cli.Prs_Ced LIKE '%$val%'";
                        break;
                    case 'm': // Por Manifiesto (Ant_Cod)
                        $wherefiltro = " AND manifiesto_anticipo.Ama_Cod LIKE '%$val%'";
                        break;
                    case 'p': // Por Planta
                        $wherefiltro = " AND (IFNULL(manifiesto_plantas.Pla_Nom,'') LIKE '%$val%' OR IFNULL(manifiesto_plantas.Pla_Lic,'') LIKE '%$val%' OR TRIM(CONCAT(IFNULL(manifiesto_plantas.Pla_Nom,''), IF(IFNULL(manifiesto_plantas.Pla_Lic,'')<>'', CONCAT(' (', manifiesto_plantas.Pla_Lic, ')'), ''))) LIKE '%$val%')";
                        break;
                }
            }

            if (isset($Par_Sql['estado']) && $Par_Sql['estado'] !== '' && $Par_Sql['estado'] !== 'T') {
                $whereestado = " AND manifiesto_anticipo.Ama_Est = '" . addslashes($Par_Sql['estado']) . "'";
            } else {
                $whereestado = " ";
            }

            $wherePlaCod = "";
            if (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] != '') {
                $wherePlaCod = " AND manifiesto_anticipo.Pla_Cod = " . addslashes($Par_Sql['Pla_Cod']);
            }

            $wherefecha = " manifiesto_anticipo.Ama_Fec BETWEEN '" . addslashes($Par_Sql['Fec_IniM']) . "' AND '" . addslashes($Par_Sql['Fec_FinM']) . "'" . $wherePlaCod;

            if (isset($Par_Sql['Cli_Cod']) && $Par_Sql['Cli_Cod'] !== '') {
                $wherecliente = " AND manifiesto_anticipo.Cli_Cod = '" . addslashes($Par_Sql['Cli_Cod']) . "'";
            } else {
                $wherecliente = " ";
            }

            // Filtro por Ama_Tip (Tipo de pago: P=Pendiente, GE=Garita Entrada, GS=Garita Salida, F=Facturado, R=Rechazado, A=Acreditado)
            if (isset($Par_Sql['filtroAnt']) && $Par_Sql['filtroAnt'] !== '') {
                $wheretipopago = " AND manifiesto_anticipo.Ama_Tip = '" . addslashes($Par_Sql['filtroAnt']) . "'";
            } else {
                $wheretipopago = " ";
            }

            // $sql = "SELECT manifiesto_anticipo.*, banco.Ban_Cod, banco.Pld_Cod,
            // 			banco.Ban_Cue, banco.Ban_Obs, bancos.Bak_Cod,
            //             bancos.Bak_Des, det_plan.Pld_Des,
            // 			tipos_pago.Pag_Cod, tipos_pago.Pag_Des, usuarios.Usu_Cod,
            // 			usuarios.Usu_Ced, persona_usr.Prs_Ced as Usu_Ced,
            // 			CONCAT(persona_usr.Prs_Nom, ' ', persona_usr.Prs_Ape) as usuario,
            // 			CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as cliente,
            // 			anticipos_clientes.Com_Cod,
            //             anticipos_clientes.Ant_Val,
            //             COALESCE((SELECT SUM(dacc.Ddc_Val) 
            // 			          FROM det_ant_cccc dacc 
            // 			          INNER JOIN anticipos_clientes ac ON dacc.Ant_Cod = ac.Ant_Cod 
            // 			          INNER JOIN comprobantes com ON dacc.Com_Cod = com.Com_Cod
            // 			          WHERE ac.Ama_Cod = manifiesto_anticipo.Ama_Cod 
            // 			            AND com.Com_Est = 'A'), 0) as Abono

            // FROM manifiesto_anticipo
            //     LEFT JOIN banco ON manifiesto_anticipo.Ban_Cod = banco.Ban_Cod
            //     LEFT JOIN bancos ON manifiesto_anticipo.Bak_Cod = bancos.Bak_Cod
            //     LEFT JOIN det_plan ON banco.Pld_Cod = det_plan.Pld_Cod
            //     INNER JOIN usuarios ON manifiesto_anticipo.Usu_Cod = usuarios.Usu_Cod
            //     INNER JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
            //     INNER JOIN cliente ON manifiesto_anticipo.Cli_Cod = cliente.Cli_Cod
            //     INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
            //     INNER JOIN tipos_pago ON manifiesto_anticipo.Pag_Cod = tipos_pago.Pag_Cod
            //     LEFT JOIN anticipos_clientes ON anticipos_clientes.Ama_Cod = manifiesto_anticipo.Ama_Cod AND Ant_Est='A'
            // WHERE
            // 	$wherefecha $whereestado $wherefiltro $wherecliente $wheretipopago
            // ORDER BY manifiesto_anticipo.Ama_Cod DESC;";
            $sql = "SELECT 
                            manifiesto_anticipo.Ama_Cod, manifiesto_anticipo.Ama_Fec,
                            manifiesto_anticipo.Ama_Val, manifiesto_anticipo.Ama_Doc,
                            manifiesto_anticipo.Ama_Est,
                            manifiesto_anticipo.Ama_Tip, manifiesto_anticipo.Cli_Cod,
                            manifiesto_anticipo.Ban_Cod, manifiesto_anticipo.Bak_Cod,
                            manifiesto_anticipo.Pag_Cod, manifiesto_anticipo.Usu_Cod,
                            banco.Pld_Cod, banco.Ban_Cue, banco.Ban_Obs, bancos.Bak_Des,
                            det_plan.Pld_Des,
                            tipos_pago.Pag_Des,
                            usuarios.Usu_Ced AS UsuarioSistema,
                            persona_usr.Prs_Ced AS CedulaPersona,
                            CONCAT(persona_usr.Prs_Nom, ' ', persona_usr.Prs_Ape) AS usuario,
                            CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) AS cliente,
                            anticipos_clientes.Com_Cod,
                            anticipos_clientes.Ant_Val,

                            COALESCE((
                                SELECT SUM(dacc.Ddc_Val)
                                FROM det_ant_cccc dacc
                                INNER JOIN anticipos_clientes ac ON dacc.Ant_Cod = ac.Ant_Cod
                                INNER JOIN comprobantes com ON dacc.Com_Cod = com.Com_Cod
                                WHERE ac.Ama_Cod = manifiesto_anticipo.Ama_Cod
                                AND ac.Ant_Est IN ('A','U','C')
                                AND IFNULL(com.Com_Est,'A') <> 'I'
                                AND dacc.Com_Cod <> ac.Com_Cod
                            ), 0) AS Abono

                        FROM manifiesto_anticipo
                            LEFT JOIN banco ON manifiesto_anticipo.Ban_Cod = banco.Ban_Cod
                            LEFT JOIN bancos ON manifiesto_anticipo.Bak_Cod = bancos.Bak_Cod
                            LEFT JOIN det_plan ON banco.Pld_Cod = det_plan.Pld_Cod
                            INNER JOIN usuarios ON manifiesto_anticipo.Usu_Cod = usuarios.Usu_Cod
                            INNER JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
                            INNER JOIN cliente ON manifiesto_anticipo.Cli_Cod = cliente.Cli_Cod
                            INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                            INNER JOIN tipos_pago ON manifiesto_anticipo.Pag_Cod = tipos_pago.Pag_Cod
                            LEFT JOIN manifiesto_plantas ON manifiesto_anticipo.Pla_Cod = manifiesto_plantas.Pla_Cod
                            LEFT JOIN anticipos_clientes 
                                ON anticipos_clientes.Ama_Cod = manifiesto_anticipo.Ama_Cod 
                                AND anticipos_clientes.Ant_Est IN ('A','U','C')
                        WHERE
                            $wherefecha $whereestado $wherefiltro $wherecliente $wheretipopago
                        ORDER BY manifiesto_anticipo.Ama_Fec ASC, manifiesto_anticipo.Ama_Cod ASC;";
            return $sql;
            // break;

        case 12:
            $sql = "UPDATE manifiesto_anticipo SET Ama_Est = 'I', Ama_Tip = 'I' WHERE Ama_Cod = '" . $Par_Sql['Ama_Cod'] . "';";
            return $sql;
            // break;
        case 13:
            // Obtener datos completos de un anticipo por Ama_Cod para editar
            $sql = "SELECT manifiesto_anticipo.*, 
                        banco.Ban_Cod, banco.Pld_Cod, banco.Ban_Cue, banco.Ban_Obs,
                        bancos.Bak_Cod, bancos.Bak_Des,
                        tipos_pago.Pag_Cod, tipos_pago.Pag_Des, tipos_pago.Pag_Abr,
                        usuarios.Usu_Cod, usuarios.Usu_Ced,
                        persona_usr.Prs_Ced as Usu_Ced,
                        CONCAT(persona_usr.Prs_Nom, ' ', persona_usr.Prs_Ape) as usuario,
                        cliente.Cli_Cod,
                        persona_cli.Prs_Cod, persona_cli.Prs_Ced,
                        CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as cliente,
                        manifiesto_plantas.Pla_Cod, manifiesto_plantas.Pla_Nom, manifiesto_plantas.Pla_Lic
                    FROM manifiesto_anticipo
                        LEFT JOIN banco ON manifiesto_anticipo.Ban_Cod = banco.Ban_Cod
                        LEFT JOIN bancos ON manifiesto_anticipo.Bak_Cod = bancos.Bak_Cod
                        INNER JOIN usuarios ON manifiesto_anticipo.Usu_Cod = usuarios.Usu_Cod
                        INNER JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
                        INNER JOIN cliente ON manifiesto_anticipo.Cli_Cod = cliente.Cli_Cod
                        INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                        INNER JOIN tipos_pago ON manifiesto_anticipo.Pag_Cod = tipos_pago.Pag_Cod
                        LEFT JOIN manifiesto_plantas ON manifiesto_anticipo.Pla_Cod = manifiesto_plantas.Pla_Cod
                    WHERE manifiesto_anticipo.Ama_Cod = '" . addslashes($Par_Sql['Ama_Cod']) . "';";
            return $sql;
            // break;
        case 14:
            // Actualizar un manifiesto_anticipo existente (UPDATE)
            $Ama_Img = isset($Par_Sql['Ama_Img']) && $Par_Sql['Ama_Img'] != '' ? "'" . addslashes($Par_Sql['Ama_Img']) . "'" : 'NULL';
            $sql = "UPDATE manifiesto_anticipo SET
                Ban_Cod = '$Par_Sql[Ban_Cod]',
                Bak_Cod = '$Par_Sql[Bak_Cod]',
                Cli_Cod = '$Par_Sql[Cli_Cod]',
                Pla_Cod = '$Par_Sql[Pla_Cod]',
                Ama_Val = '$Par_Sql[Ama_Val]',
                Pag_Cod = '$Par_Sql[Pag_Cod]',
                Ama_Doc = '$Par_Sql[Ama_Doc]',
                Ama_Fec = '$Par_Sql[Ama_Fec]',
                Ama_Obs = '" . (empty($Par_Sql['Ama_Obs']) ? '' : addslashes($Par_Sql['Ama_Obs'])) . "',
                Ama_Img = $Ama_Img
            WHERE Ama_Cod = '$Par_Sql[Ama_Cod]';";
            return $sql;
            // break;
        case 15:
            $sql = "UPDATE manifiesto_anticipo SET Ama_Est = 'I', Ama_Tip = 'R' WHERE Ama_Cod = '" . $Par_Sql['Ama_Cod'] . "';";
            return $sql;
            // break;
        case 19:
            // Actualizar el estado del anticipo a Acreditado cuando se genera el comprobante
            $sql = "UPDATE manifiesto_anticipo SET Ama_Tip = 'A', Ama_IgV = '$Par_Sql[Ama_IgV]' WHERE Ama_Cod = '" . $Par_Sql['Ama_Cod'] . "';";
            return $sql;
            // break;
        case 16:
            $sql = "INSERT INTO comprobantes (Pec_Cod, Cli_Cod, Usu_Cod, Com_Num, Com_Fec, Com_Tip, Com_Con, Com_Val, Com_Obs, Com_Est, Tia_Cod, Com_Gen)
                    VALUES ('{$Par_Sql['Pec_Cod']}', '{$Par_Sql['Cli_Cod']}', '{$Par_Sql['Usu_Cod']}', '{$Par_Sql['Com_Num']}', 
                            '{$Par_Sql['Com_Fec']}', 'I', '{$Par_Sql['Com_Con']}', '{$Par_Sql['Com_Val']}', '{$Par_Sql['Com_Obs']}',
                            'A', '{$Par_Sql['Tia_Cod']}', 'A');";
            return $sql;
            // break;
        case 17:
            // Obtener el periodo contable por fecha
            $sql = "SELECT perio_cont.Pec_Cod, perio_cont.Pec_Fei, perio_cont.Pec_Fef, perio_cont.Pec_Est, YEAR(perio_cont.Pec_Fei) AS Periodo, perio_cont.Pla_Cod
                    FROM plan_cuenta
                        INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                    WHERE Pec_Est = 'A' AND 
                        ('{$Par_Sql['Com_Fec']}' BETWEEN perio_cont.Pec_Fei AND perio_cont.Pec_Fef) AND
                        plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
                    ORDER BY perio_cont.Pec_Fei DESC
                    LIMIT 1;";
            return $sql;
            // break;
        case 18:
            // Obtener el siguiente número de comprobante para el periodo según el tipo
            $sql = "SELECT IFNULL(MAX(Com_Num), 0) + 1 AS Com_Num 
                    FROM comprobantes 
                    WHERE Tia_Cod = '$Par_Sql[Tia_Cod]' AND
                        Pec_Cod = '$Par_Sql[Pec_Cod]';";
            return $sql;
            // break;
        case 20:
            // Obtener el Pld_Cod de la cuenta parametrizada ANC (Anticipos de Clientes)
            $sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Des, det_plan.Pld_Cdc
                    FROM plan_cuenta
                        INNER JOIN det_plan ON det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                        INNER JOIN plan_param ON det_plan.Pld_Cod = plan_param.Pld_Cod
                        INNER JOIN tipo_param ON plan_param.Tpa_Cod = tipo_param.Tpa_Cod
                        INNER JOIN perio_cont ON perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
                    WHERE perio_cont.Pec_Cod = '{$Par_Sql['Pec_Cod']}'
                        AND tipo_param.Tpa_Abr = 'ANC'
                        AND plan_cuenta.Emp_Cod = '{$Par_Sql['Emp_Cod']}'
                        AND plan_cuenta.Pla_Est = 'A'
                        AND det_plan.Pld_Est = 'A'
                    LIMIT 1;";
            return $sql;
            // break;
        case 21:
            // Insertar un registro en la tabla asientos
            $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Pld_Cod, Asi_Con, Asi_Glo)
                    VALUES ('{$Par_Sql['Com_Cod']}', '{$Par_Sql['Asi_Deh']}', '{$Par_Sql['Asi_Val']}', 
                            '{$Par_Sql['Pld_Cod']}', 
                            '" . (isset($Par_Sql['Asi_Con']) ? addslashes($Par_Sql['Asi_Con']) : '') . "',
                            '" . (isset($Par_Sql['Asi_Glo']) ? addslashes($Par_Sql['Asi_Glo']) : '') . "');";
            return $sql;
            // break;
        case 22:
            // Determinar el tipo de búsqueda
            $op_opciones = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : '';
            $searchCli = isset($Par_Sql['searchCli']) ? addslashes($Par_Sql['searchCli']) : '';

            // Construir la condición de búsqueda según el tipo
            if ($op_opciones == "c") {
                // Búsqueda por cédula/RUC
                $search = $searchCli != '' ? "persona.Prs_Ced LIKE '%$searchCli%'" : "1=1";
            } else {
                // Búsqueda por nombre del cliente
                $search = $searchCli != '' ? "(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$searchCli%' OR persona.Prs_Nom LIKE '%$searchCli%' OR persona.Prs_Ape LIKE '%$searchCli%')" : "1=1";
            }

            // Si no hay limits, es para contar registros
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(cliente.Cli_Cod) AS total";
                $limits = "";
            } else {
                // Si hay limits, es para obtener los datos
                $campos = "cliente.Cli_Cod, persona.Prs_Cod, persona.Prs_Ced, 
                            IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS nombre, 
                            persona.Prs_Dir, manifiesto_usuario.Pla_Cod, manifiesto_plantas.Pla_Nom, manifiesto_plantas.Pla_Lic";
                $limits = $Par_Sql['limits'];
            }

            $sql = "SELECT $campos
                    FROM persona
                        INNER JOIN cliente ON cliente.Prs_Cod = persona.Prs_Cod
                        INNER JOIN manifiesto_usuario ON cliente.Cli_Cod = manifiesto_usuario.Cli_Cod
                        LEFT JOIN manifiesto_plantas ON manifiesto_usuario.Pla_Cod = manifiesto_plantas.Pla_Cod
                    WHERE $search 
                        AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
                        AND cliente.Cli_Est = 'A'
                    $limits";
            return $sql;
            // break;
        case 23:
            $sql = "SELECT Asi_Cod, Asi_Deh, Pld_Cdc, Pld_Des,Asi_Glo as Glosa, Asi_Val,
                        IF(Asi_Deh='D',Asi_Val,'') AS Debe, IF(Asi_Deh='H',Asi_Val,'') AS Haber
                    FROM asientos
                        INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod
                    WHERE Com_Cod='$Par_Sql[0]'
                    ORDER BY Asi_Deh";
            return $sql;
            // break;
        case 29:
            // Obtener el siguiente número de documento (Ant_Doc) por cliente
            $sql = "SELECT IFNULL(MAX(CAST(Ant_Doc AS UNSIGNED)), 0) + 1 AS siguiente_doc
                    FROM anticipos_clientes
                    WHERE Cli_Cod = '{$Par_Sql['Cli_Cod']}';";
            return $sql;
            // break;
        case 30:
            // Insertar un registro en la tabla anticipos_clientes
            $sql = "INSERT INTO anticipos_clientes (Ant_Fec, Ant_Val, Ant_Est, Ant_Doc, Ant_Obs, Cli_Cod, Com_Cod, Ant_Tip, Ama_Cod)
                    VALUES (
                        '{$Par_Sql['Ant_Fec']}',
                        '{$Par_Sql['Ant_Val']}',
                        'A',
                        '{$Par_Sql['Ant_Doc']}',
                        '" . (isset($Par_Sql['Ant_Obs']) && !empty($Par_Sql['Ant_Obs']) ? addslashes($Par_Sql['Ant_Obs']) : '') . "',
                        '{$Par_Sql['Cli_Cod']}',
                        '{$Par_Sql['Com_Cod']}',
                        'A',
                        '{$Par_Sql['Ama_Cod']}'
                    );";
            return $sql;
            // break;
        case 31:
            // Obtener el tipo de asiento INGRESO (Tia_Abr='IN' y Tia_Est='A')
            $sql = "SELECT * FROM tipo_asien WHERE Tia_Abr = 'IN' AND Tia_Est = 'A' LIMIT 1;";
            return $sql;
            // break;
        case 32:
            $sql = "INSERT INTO pag_anticipo_cli (Pac_Cto, Pac_Ctd, Pac_Val, Ant_Cod, Pac_Obs, Pac_Num, Pag_Cod, Asi_Cod)
							VALUES('$Par_Sql[Pac_Cto]', '$Par_Sql[Pac_Ctd]', '$Par_Sql[Pac_Val]', $Par_Sql[Ant_Cod], '$Par_Sql[Pac_Obs]', '$Par_Sql[Pac_Num]', $Par_Sql[Pag_Cod], $Par_Sql[Asi_Cod] );";
            return $sql;
            // break;
        case 33:
            $sql = "SELECT Pla_Cod, Pla_Nom, Pla_Lic FROM manifiesto_plantas WHERE Pla_Est = 'A' AND Cli_Cod = '$Par_Sql[Cli_Cod]';";
            return $sql;
            // break;
        case 34:
            // Validar si Ama_Doc ya existe (excluyendo el registro actual si es edición)
            $Ama_Doc = addslashes($Par_Sql['Ama_Doc']);
            $Ama_Cod = isset($Par_Sql['Ama_Cod']) && $Par_Sql['Ama_Cod'] != '' ? addslashes($Par_Sql['Ama_Cod']) : '';

            $where_exclude = '';
            if ($Ama_Cod != '') {
                $where_exclude = " AND Ama_Cod != '$Ama_Cod'";
            }

            $sql = "SELECT COUNT(*) AS total 
                    FROM manifiesto_anticipo 
                    WHERE Ama_Doc = '$Ama_Doc' AND Bak_Cod = '$Par_Sql[Bak_Cod]' AND
                        Pag_Cod = '$Par_Sql[Pag_Cod]' AND Ama_Est != 'I'
                        $where_exclude;";
            return $sql;
            // break;

        case 35:
            // Usuarios de la empresa logueada con notificaciones activas (Usu_Ntf = 'S') y teléfono en persona
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $sql = "SELECT u.Usu_Cod,
                               COALESCE(NULLIF(TRIM(p.Prs_Tel),''), NULLIF(TRIM(p.Prs_Te2),'')) AS Telefono,
                               TRIM(CONCAT(IFNULL(p.Prs_Nom,''),' ',IFNULL(p.Prs_Ape,''))) AS Usuario
                        FROM usuarios u
                        INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                        INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod AND s.Emp_Cod = $Emp_Cod
                        WHERE u.Usu_Est = 'A'
                          AND s.Suc_Est = 'A'
                          AND u.Usu_Ntf = 'S'
                        ORDER BY p.Prs_Ape, p.Prs_Nom, u.Usu_Cod";
            return $sql;
            // break;

        case 36:
            // Teléfonos para notificar al administrador de una planta (misma prioridad que notificaciones masivas)
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? intval($Par_Sql['Pla_Cod']) : 0;
            $sql = "SELECT mp.Pla_Cod, mp.Pla_Nom, mp.Pla_Wat,
                           mpp.Pep_Tel AS Pep_Tel_Admin,
                           adm.Prs_Tel AS Prs_Tel_Admin,
                           adm.Prs_Te2 AS Prs_Te2_Admin
                    FROM manifiesto_plantas mp
                    LEFT JOIN manifiesto_personal_planta AS mpp
                        ON mpp.Pla_Cod = mp.Pla_Cod AND mpp.Pep_Tip = 'AP' AND mpp.Pep_Est = 'A'
                    LEFT JOIN persona AS adm ON adm.Prs_Cod = mpp.Prs_Cod
                    WHERE mp.Pla_Cod = $Pla_Cod AND mp.Pla_Est = 'A'
                    LIMIT 1";
            return $sql;
            // break;

        case 37:
            /* Consumos agrupados por comprobante (misma lógica que estado cuenta anticipo clientes: Ant A/U/C, Com <> I, excluye comprobante de ingreso del anticipo) */
            $ama = isset($Par_Sql['Ama_Cod']) ? addslashes((string) $Par_Sql['Ama_Cod']) : '';
            $whereFechaCons = '';
            if (!empty($Par_Sql['Fec_IniM']) && !empty($Par_Sql['Fec_FinM'])) {
                $fi = addslashes((string) $Par_Sql['Fec_IniM']);
                $ff = addslashes((string) $Par_Sql['Fec_FinM']);
                $whereFechaCons = " AND DATE(pago.Com_Fec) BETWEEN DATE('$fi') AND DATE('$ff') ";
            }
            /* Agrupar por comprobante del PAGO (dacc.Com_Cod): un mismo pago puede tener varias líneas det_ant (varios anticipos);
               el modal muestra esas líneas; el manifiesto debe mostrar la SUMA por ese comprobante de pago. */
            $sql = "SELECT pago.Com_Cod AS Com_Cod_Consumo,
                           MAX(pago.Com_Fec) AS Com_Fec_Consumo,
                           MAX(pago.Com_Num) AS Com_Num_Consumo,
                           MAX(CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(pago.Com_Fec))=1,CONCAT('0',CAST(MONTH(pago.Com_Fec) AS CHAR)),CAST(MONTH(pago.Com_Fec) AS CHAR)),'-',CAST(pago.Com_Num AS CHAR))) AS Codigo_Compr_Consumo,
                           MAX(pago.Com_Con) AS Com_Con_Consumo,
                           SUM(dacc.Ddc_Val) AS total_consumo
                    FROM det_ant_cccc dacc
                    INNER JOIN anticipos_clientes ac ON dacc.Ant_Cod = ac.Ant_Cod
                    INNER JOIN comprobantes pago ON pago.Com_Cod = dacc.Com_Cod
                    INNER JOIN tipo_asien tp ON tp.Tia_Cod = pago.Tia_Cod
                    WHERE ac.Ama_Cod = '$ama'
                      AND ac.Ant_Est IN ('A','U','C')
                      AND IFNULL(pago.Com_Est,'A') <> 'I'
                      AND dacc.Com_Cod <> ac.Com_Cod
                      $whereFechaCons
                    GROUP BY pago.Com_Cod
                    ORDER BY MAX(pago.Com_Fec) ASC, pago.Com_Cod ASC";
            return $sql;
            // break;

        case 38:
            /* Cabecera de un comprobante (consumo u otro) con cliente y usuario creador */
            $comCod = isset($Par_Sql['Com_Cod']) ? intval($Par_Sql['Com_Cod']) : 0;
            if ($comCod <= 0) {
                return "SELECT NULL AS Com_Cod, NULL AS Com_Fec, '' AS codigoCompra, '' AS nombre, '' AS Prs_Ced, '' AS Com_Con, '' AS Com_Obs, '' AS usuario, '' AS Com_Sys LIMIT 0";
            }
            $sql = "SELECT
                        c.Com_Cod,
                        DATE(c.Com_Fec) AS Com_Fec,
                        CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(c.Com_Fec))=1,CONCAT('0',CAST(MONTH(c.Com_Fec) AS CHAR)),CAST(MONTH(c.Com_Fec) AS CHAR)),'-',CAST(c.Com_Num AS CHAR)) AS codigoCompra,
                        TRIM(CONCAT(IFNULL(prs.Prs_Nom,''),' ',IFNULL(prs.Prs_Ape,''))) AS nombre,
                        IFNULL(prs.Prs_Ced,'') AS Prs_Ced,
                        IFNULL(c.Com_Con,'') AS Com_Con,
                        IFNULL(c.Com_Obs,'') AS Com_Obs,
                        IFNULL(TRIM(CONCAT(IFNULL(up.Prs_Nom,''),' ',IFNULL(up.Prs_Ape,''))),'') AS usuario,
                        IFNULL(c.Com_Sys,'') AS Com_Sys
                    FROM comprobantes c
                    LEFT JOIN cliente cli ON cli.Cli_Cod = c.Cli_Cod
                    LEFT JOIN persona prs ON prs.Prs_Cod = cli.Prs_Cod
                    LEFT JOIN tipo_asien tp ON tp.Tia_Cod = c.Tia_Cod
                    LEFT JOIN usuarios u ON u.Usu_Cod = c.Usu_Cod
                    LEFT JOIN persona up ON up.Prs_Cod = u.Prs_Cod
                    WHERE c.Com_Cod = " . $comCod . "
                    LIMIT 1";
            return $sql;

        case 39:
            /* Líneas det_ant_cccc de un comprobante de consumo; saldo_momento = saldo del anticipo tras consumos previos (otros comprobantes y líneas anteriores del mismo Com_Cod por Ddc_Cod) */
            $comCod = isset($Par_Sql['Com_Cod']) ? intval($Par_Sql['Com_Cod']) : 0;
            if ($comCod <= 0) {
                return "SELECT '' AS codigo_consumo, '' AS fecha_consumo, '' AS glosa_consumo, 0 AS valor_anticipo, 0 AS valor_consumo, 0 AS saldo_anticipo, 0 AS saldo_momento LIMIT 0";
            }
            $sql = "SELECT
                        CONCAT(tp_ant.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(cant.Com_Fec))=1,CONCAT('0',CAST(MONTH(cant.Com_Fec) AS CHAR)),CAST(MONTH(cant.Com_Fec) AS CHAR)),'-',CAST(cant.Com_Num AS CHAR)) AS codigo_consumo,
                        DATE(c.Com_Fec) AS fecha_consumo,
                        IFNULL(c.Com_Con,'') AS glosa_consumo,
                        CAST(ant.Ant_Val AS DECIMAL(14,4)) AS valor_anticipo,
                        CAST(ddc.Ddc_Val AS DECIMAL(14,4)) AS valor_consumo,
                        CAST((ant.Ant_Val - IFNULL((SELECT SUM(d2.Ddc_Val) FROM det_ant_cccc d2 INNER JOIN comprobantes cx ON cx.Com_Cod = d2.Com_Cod WHERE d2.Ant_Cod = ant.Ant_Cod AND IFNULL(cx.Com_Est,'A') <> 'I'),0)) AS DECIMAL(14,4)) AS saldo_anticipo,
                        CAST((ant.Ant_Val - IFNULL((
                            SELECT SUM(d2.Ddc_Val)
                            FROM det_ant_cccc d2
                            INNER JOIN comprobantes c2 ON c2.Com_Cod = d2.Com_Cod
                            WHERE d2.Ant_Cod = ant.Ant_Cod
                              AND IFNULL(c2.Com_Est,'A') <> 'I'
                              AND (
                                  c2.Com_Fec < c.Com_Fec
                                  OR (c2.Com_Fec = c.Com_Fec AND CAST(c2.Com_Cod AS UNSIGNED) < CAST(c.Com_Cod AS UNSIGNED))
                                  OR (c2.Com_Cod = c.Com_Cod AND d2.Ddc_Cod <= ddc.Ddc_Cod)
                              )
                        ), 0)) AS DECIMAL(14,4)) AS saldo_momento
                    FROM det_ant_cccc AS ddc
                    INNER JOIN anticipos_clientes AS ant ON ant.Ant_Cod = ddc.Ant_Cod
                    INNER JOIN comprobantes AS c ON c.Com_Cod = ddc.Com_Cod
                    INNER JOIN comprobantes AS cant ON cant.Com_Cod = ant.Com_Cod
                    INNER JOIN tipo_asien AS tp_ant ON tp_ant.Tia_Cod = cant.Tia_Cod
                    WHERE ddc.Com_Cod = " . $comCod . "
                        AND IFNULL(c.Com_Est,'A') <> 'I'
                    ORDER BY ddc.Ddc_Cod ASC";
            return $sql;

        case 40:
            /* Saldo inicial manifiesto antes de Fec_IniM: anticipos acreditados (Ama_Tip=A) - consumos det_ant con fecha comprobante anterior */
            $fecIni = isset($Par_Sql['Fec_IniM']) ? addslashes(trim((string) $Par_Sql['Fec_IniM'])) : '1970-01-01';
            $wherefiltro = '';
            if (isset($Par_Sql['filtro']) && isset($Par_Sql['search']) && $Par_Sql['search'] !== '') {
                $val = addslashes($Par_Sql['search']);
                switch ($Par_Sql['filtro']) {
                    case 'cl':
                        $wherefiltro = " AND (CONCAT(IFNULL(persona_cli.Prs_Nom,''), ' ', IFNULL(persona_cli.Prs_Ape,'')) LIKE '%$val%')";
                        break;
                    case 'c':
                        $wherefiltro = " AND persona_cli.Prs_Ced LIKE '%$val%'";
                        break;
                    case 'm':
                        $wherefiltro = " AND ma.Ama_Cod LIKE '%$val%'";
                        break;
                    case 'p':
                        $wherefiltro = " AND ma.Pla_Cod IN (SELECT mp2.Pla_Cod FROM manifiesto_plantas mp2 WHERE IFNULL(mp2.Pla_Nom,'') LIKE '%$val%' OR IFNULL(mp2.Pla_Lic,'') LIKE '%$val%' OR TRIM(CONCAT(IFNULL(mp2.Pla_Nom,''), IF(IFNULL(mp2.Pla_Lic,'')<>'', CONCAT(' (', mp2.Pla_Lic, ')'), ''))) LIKE '%$val%')";
                        break;
                }
            }
            $whereestado = '';
            if (isset($Par_Sql['estado']) && $Par_Sql['estado'] !== '' && $Par_Sql['estado'] !== 'T') {
                $whereestado = " AND ma.Ama_Est = '" . addslashes($Par_Sql['estado']) . "'";
            }
            $wherePlaCod = '';
            if (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] != '') {
                $wherePlaCod = ' AND ma.Pla_Cod = ' . addslashes($Par_Sql['Pla_Cod']);
            }
            $wherecliente = '';
            if (isset($Par_Sql['Cli_Cod']) && $Par_Sql['Cli_Cod'] !== '') {
                $wherecliente = " AND ma.Cli_Cod = '" . addslashes($Par_Sql['Cli_Cod']) . "'";
            }
            $sql = "SELECT (
                COALESCE((
                    SELECT SUM(ma.Ama_Val)
                    FROM manifiesto_anticipo ma
                    INNER JOIN cliente ON ma.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                    INNER JOIN tipos_pago ON ma.Pag_Cod = tipos_pago.Pag_Cod
                    WHERE ma.Ama_Fec < '" . $fecIni . "'
                      AND ma.Ama_Tip = 'A'
                      $whereestado
                      $wherecliente
                      $wherePlaCod
                      $wherefiltro
                ), 0) - COALESCE((
                    SELECT SUM(dacc.Ddc_Val)
                    FROM det_ant_cccc dacc
                    INNER JOIN anticipos_clientes ac ON dacc.Ant_Cod = ac.Ant_Cod
                    INNER JOIN comprobantes com ON dacc.Com_Cod = com.Com_Cod
                    INNER JOIN manifiesto_anticipo ma ON ma.Ama_Cod = ac.Ama_Cod
                    INNER JOIN cliente ON ma.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                    INNER JOIN tipos_pago ON ma.Pag_Cod = tipos_pago.Pag_Cod
                    WHERE DATE(com.Com_Fec) < DATE('" . $fecIni . "')
                      AND ac.Ant_Est IN ('A','U','C')
                      AND IFNULL(com.Com_Est,'A') <> 'I'
                      AND dacc.Com_Cod <> ac.Com_Cod
                      AND ma.Ama_Tip = 'A'
                      $whereestado
                      $wherecliente
                      $wherePlaCod
                      $wherefiltro
                ), 0)
            ) AS saldo_ini";
            return $sql;

        case 41:
            $term = isset($Par_Sql['term']) ? addslashes(trim((string) $Par_Sql['term'])) : '';
            if ($term === '') return "SELECT '' AS value, '' AS label LIMIT 0";
            $sql = "SELECT DISTINCT
                        ma.Pla_Cod AS value,
                        TRIM(CONCAT(IFNULL(mp.Pla_Nom,''), IF(IFNULL(mp.Pla_Lic,'')<>'', CONCAT(' (', mp.Pla_Lic, ')'), ''))) AS label
                    FROM manifiesto_anticipo ma
                    INNER JOIN manifiesto_plantas mp ON mp.Pla_Cod = ma.Pla_Cod
                    WHERE (IFNULL(mp.Pla_Nom,'') LIKE '%$term%' OR IFNULL(mp.Pla_Lic,'') LIKE '%$term%')
                    ORDER BY mp.Pla_Nom DESC
                    LIMIT 15";
            return $sql;

        case 42:
            $term = isset($Par_Sql['term']) ? addslashes(trim((string) $Par_Sql['term'])) : '';
            if ($term === '') return "SELECT '' AS value, '' AS label LIMIT 0";
            $sql = "SELECT DISTINCT
                        c.Cli_Cod AS value,
                        IFNULL(p.Prs_Ced,'') AS ruc,
                        TRIM(CONCAT(IFNULL(p.Prs_Nom,''), ' ', IFNULL(p.Prs_Ape,''))) AS label
                    FROM cliente c
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    INNER JOIN manifiesto_anticipo ma ON ma.Cli_Cod = c.Cli_Cod
                    WHERE c.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
                      AND (TRIM(CONCAT(IFNULL(p.Prs_Nom,''), ' ', IFNULL(p.Prs_Ape,'')) ) LIKE '%$term%'
                           OR IFNULL(p.Prs_Ced,'') LIKE '%$term%')
                    ORDER BY p.Prs_Ced DESC, p.Prs_Nom DESC, p.Prs_Ape DESC
                    LIMIT 15";
            return $sql;

        case 43:
            /* Consumos del rango por fecha de comprobante, aunque el anticipo sea de meses anteriores */
            $fi = isset($Par_Sql['Fec_IniM']) ? addslashes(trim((string) $Par_Sql['Fec_IniM'])) : '';
            $ff = isset($Par_Sql['Fec_FinM']) ? addslashes(trim((string) $Par_Sql['Fec_FinM'])) : '';
            if ($fi === '' || $ff === '') {
                return "SELECT '' AS Com_Cod_Consumo, '' AS Com_Fec_Consumo, '' AS Com_Num_Consumo, '' AS Codigo_Compr_Consumo, '' AS Com_Con_Consumo, 0 AS total_consumo, '' AS Ama_Cod, '' AS cliente LIMIT 0";
            }

            $wherecliente = '';
            if (isset($Par_Sql['Cli_Cod']) && $Par_Sql['Cli_Cod'] !== '') {
                $wherecliente = " AND ma.Cli_Cod = '" . addslashes($Par_Sql['Cli_Cod']) . "'";
            }
            $wherePlaCod = '';
            if (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] !== '') {
                $wherePlaCod = " AND ma.Pla_Cod = " . addslashes($Par_Sql['Pla_Cod']);
            }
            $whereestado = '';
            if (isset($Par_Sql['estado']) && $Par_Sql['estado'] !== '' && $Par_Sql['estado'] !== 'T') {
                $whereestado = " AND ma.Ama_Est = '" . addslashes($Par_Sql['estado']) . "'";
            }
            $wherefiltro = '';
            if (isset($Par_Sql['filtro']) && isset($Par_Sql['search']) && $Par_Sql['search'] !== '') {
                $val = addslashes($Par_Sql['search']);
                switch ($Par_Sql['filtro']) {
                    case 'cl': // Por Cliente
                        $wherefiltro = " AND (CONCAT(IFNULL(persona_cli.Prs_Nom,''), ' ', IFNULL(persona_cli.Prs_Ape,'')) LIKE '%$val%')";
                        break;
                    case 'c': // Por Cédula/RUC
                        $wherefiltro = " AND persona_cli.Prs_Ced LIKE '%$val%'";
                        break;
                    case 'm': // Por manifiesto/anticipo interno
                        $wherefiltro = " AND ma.Ama_Cod LIKE '%$val%'";
                        break;
                    case 'p': // Por Planta
                        $wherefiltro = " AND ma.Pla_Cod IN (SELECT mp2.Pla_Cod FROM manifiesto_plantas mp2 WHERE IFNULL(mp2.Pla_Nom,'') LIKE '%$val%' OR IFNULL(mp2.Pla_Lic,'') LIKE '%$val%' OR TRIM(CONCAT(IFNULL(mp2.Pla_Nom,''), IF(IFNULL(mp2.Pla_Lic,'')<>'', CONCAT(' (', mp2.Pla_Lic, ')'), ''))) LIKE '%$val%')";
                        break;
                }
            }
            $wheretipopago = '';
            if (isset($Par_Sql['filtroAnt']) && $Par_Sql['filtroAnt'] !== '') {
                $wheretipopago = " AND ma.Ama_Tip = '" . addslashes($Par_Sql['filtroAnt']) . "'";
            }

            $sql = "SELECT
                        pago.Com_Cod AS Com_Cod_Consumo,
                        MAX(pago.Com_Fec) AS Com_Fec_Consumo,
                        MAX(pago.Com_Num) AS Com_Num_Consumo,
                        MAX(CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(pago.Com_Fec))=1,CONCAT('0',CAST(MONTH(pago.Com_Fec) AS CHAR)),CAST(MONTH(pago.Com_Fec) AS CHAR)),'-',CAST(pago.Com_Num AS CHAR))) AS Codigo_Compr_Consumo,
                        MAX(pago.Com_Con) AS Com_Con_Consumo,
                        SUM(dacc.Ddc_Val) AS total_consumo,
                        MIN(ac.Ama_Cod) AS Ama_Cod,
                        MAX(CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape)) AS cliente
                    FROM det_ant_cccc dacc
                    INNER JOIN anticipos_clientes ac ON dacc.Ant_Cod = ac.Ant_Cod
                    INNER JOIN comprobantes pago ON pago.Com_Cod = dacc.Com_Cod
                    INNER JOIN tipo_asien tp ON tp.Tia_Cod = pago.Tia_Cod
                    INNER JOIN manifiesto_anticipo ma ON ma.Ama_Cod = ac.Ama_Cod
                    INNER JOIN cliente cli ON ma.Cli_Cod = cli.Cli_Cod
                    INNER JOIN persona AS persona_cli ON cli.Prs_Cod = persona_cli.Prs_Cod
                    WHERE DATE(pago.Com_Fec) BETWEEN DATE('$fi') AND DATE('$ff')
                      AND ac.Ant_Est IN ('A','U','C')
                      AND IFNULL(pago.Com_Est,'A') <> 'I'
                      AND dacc.Com_Cod <> ac.Com_Cod
                      $wherecliente
                      $wherePlaCod
                      $whereestado
                      $wherefiltro
                      $wheretipopago
                    GROUP BY pago.Com_Cod
                    ORDER BY MAX(pago.Com_Fec) ASC, pago.Com_Cod ASC";
            return $sql;

        case 44:
            /* Todos los comprobantes de CONSUMO/PAGO del cliente (filtros), una fila por comprobante con total aplicado.
               No filtrar por un solo Ama_Cod: un mismo pago reparte varias líneas det_ant a varios anticipos. */
            $wherecliente = '';
            if (isset($Par_Sql['Cli_Cod']) && $Par_Sql['Cli_Cod'] !== '') {
                $wherecliente = " AND ma.Cli_Cod = '" . addslashes($Par_Sql['Cli_Cod']) . "'";
            }
            $wherePlaCod = '';
            if (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] !== '') {
                $wherePlaCod = " AND ma.Pla_Cod = " . addslashes($Par_Sql['Pla_Cod']);
            }
            $whereestado = '';
            if (isset($Par_Sql['estado']) && $Par_Sql['estado'] !== '' && $Par_Sql['estado'] !== 'T') {
                $whereestado = " AND ma.Ama_Est = '" . addslashes($Par_Sql['estado']) . "'";
            }
            $wherefiltro = '';
            if (isset($Par_Sql['filtro']) && isset($Par_Sql['search']) && $Par_Sql['search'] !== '') {
                $val = addslashes($Par_Sql['search']);
                switch ($Par_Sql['filtro']) {
                    case 'cl':
                        $wherefiltro = " AND (CONCAT(IFNULL(persona_cli.Prs_Nom,''), ' ', IFNULL(persona_cli.Prs_Ape,'')) LIKE '%$val%')";
                        break;
                    case 'c':
                        $wherefiltro = " AND persona_cli.Prs_Ced LIKE '%$val%'";
                        break;
                    case 'm':
                        $wherefiltro = " AND ma.Ama_Cod LIKE '%$val%'";
                        break;
                    case 'p':
                        $wherefiltro = " AND ma.Pla_Cod IN (SELECT mp2.Pla_Cod FROM manifiesto_plantas mp2 WHERE IFNULL(mp2.Pla_Nom,'') LIKE '%$val%' OR IFNULL(mp2.Pla_Lic,'') LIKE '%$val%' OR TRIM(CONCAT(IFNULL(mp2.Pla_Nom,''), IF(IFNULL(mp2.Pla_Lic,'')<>'', CONCAT(' (', mp2.Pla_Lic, ')'), ''))) LIKE '%$val%')";
                        break;
                }
            }
            $wheretipopago = '';
            if (isset($Par_Sql['filtroAnt']) && $Par_Sql['filtroAnt'] !== '') {
                $wheretipopago = " AND ma.Ama_Tip = '" . addslashes($Par_Sql['filtroAnt']) . "'";
            }
            $whereFechaPago = '';
            if (!empty($Par_Sql['Fec_IniM']) && !empty($Par_Sql['Fec_FinM'])) {
                $fi = addslashes(trim((string) $Par_Sql['Fec_IniM']));
                $ff = addslashes(trim((string) $Par_Sql['Fec_FinM']));
                $whereFechaPago = " AND DATE(pago.Com_Fec) BETWEEN DATE('$fi') AND DATE('$ff') ";
            }
            $sql = "SELECT
                        pago.Com_Cod AS Com_Cod_Consumo,
                        MAX(pago.Com_Fec) AS Com_Fec_Consumo,
                        MAX(pago.Com_Num) AS Com_Num_Consumo,
                        MAX(CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(pago.Com_Fec))=1,CONCAT('0',CAST(MONTH(pago.Com_Fec) AS CHAR)),CAST(MONTH(pago.Com_Fec) AS CHAR)),'-',CAST(pago.Com_Num AS CHAR))) AS Codigo_Compr_Consumo,
                        MAX(pago.Com_Con) AS Com_Con_Consumo,
                        SUM(dacc.Ddc_Val) AS total_consumo,
                        MAX(CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape)) AS cliente
                    FROM det_ant_cccc dacc
                    INNER JOIN anticipos_clientes ac ON dacc.Ant_Cod = ac.Ant_Cod
                    INNER JOIN comprobantes pago ON pago.Com_Cod = dacc.Com_Cod
                    INNER JOIN tipo_asien tp ON tp.Tia_Cod = pago.Tia_Cod
                    INNER JOIN manifiesto_anticipo ma ON ma.Ama_Cod = ac.Ama_Cod
                    INNER JOIN cliente cli ON ma.Cli_Cod = cli.Cli_Cod
                    INNER JOIN persona AS persona_cli ON cli.Prs_Cod = persona_cli.Prs_Cod
                    WHERE ac.Ant_Est IN ('A','U','C')
                      AND IFNULL(pago.Com_Est,'A') <> 'I'
                      AND dacc.Com_Cod <> ac.Com_Cod
                      $whereFechaPago
                      $wherecliente
                      $wherePlaCod
                      $whereestado
                      $wherefiltro
                      $wheretipopago
                    GROUP BY pago.Com_Cod
                    ORDER BY MAX(pago.Com_Fec) ASC, pago.Com_Cod ASC";
            return $sql;


        case 45:
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : NULL;
            $Man_Cod =  'NULL';
            $Veh_Cod =  'NULL';
            $Cho_Cod =  'NULL';
            $Msj_Id = isset($Par_Sql['Msj_Id']) ? addslashes($Par_Sql['Msj_Id']) : '';
            $Msj_Tip = isset($Par_Sql['Msj_Tip']) ? addslashes($Par_Sql['Msj_Tip']) : '';
            $Msj_Tex = isset($Par_Sql['Msj_Tex']) ? addslashes($Par_Sql['Msj_Tex']) : '';
            $Msj_Img = isset($Par_Sql['Msj_Img']) ? addslashes($Par_Sql['Msj_Img']) : '';
            $Msj_Fec = isset($Par_Sql['Msj_Fec']) ? date('Y-m-d', strtotime($Par_Sql['Msj_Fec'])) : '';
            $Msj_Est = isset($Par_Sql['Msj_Est']) ? addslashes($Par_Sql['Msj_Est']) : '';
            $sql = "INSERT INTO manifiesto_mensajes (Pla_Cod,Man_Cod,Veh_Cod,Cho_Cod,Msj_Id,Msj_Tip,Msj_Tex,Msj_Img,Msj_Fec,Msj_Est) 
                VALUES ('$Pla_Cod',$Man_Cod,$Veh_Cod,$Cho_Cod,'$Msj_Id','$Msj_Tip','$Msj_Tex','$Msj_Img','$Msj_Fec','$Msj_Est');";
            return $sql;
        case 46:
            $sql = "SELECT ac.Ant_Cod, ac.Com_Cod, ac.Ant_Doc, ac.Ant_Fec
                    FROM anticipos_clientes ac
                    WHERE ac.Ama_Cod = '" . addslashes($Par_Sql['Ama_Cod']) . "'
                      AND ac.Ant_Est IN ('A','U','C')
                    LIMIT 1;";
            return $sql;
    }
}
