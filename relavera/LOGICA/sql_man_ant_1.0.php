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
                        $wherefiltro = " AND CONCAT(persona_cli.Prs_Nom LIKE '%$val%' OR persona_cli.Prs_Ape LIKE '%$val%')";
                        break;
                    case 'c': // Por Cedula/RUC
                        $wherefiltro = " AND persona_cli.Prs_Ced LIKE '%$val%'";
                        break;
                    case 'm': // Por Manifiesto (Ant_Cod)
                        $wherefiltro = " AND manifiesto_anticipo.Ant_Cod LIKE '%$val%'";
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
                                AND com.Com_Est = 'A'
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
                            LEFT JOIN anticipos_clientes 
                                ON anticipos_clientes.Ama_Cod = manifiesto_anticipo.Ama_Cod 
                                AND anticipos_clientes.Ant_Est = 'A'
                        WHERE
                            $wherefecha $whereestado $wherefiltro $wherecliente $wheretipopago
                        ORDER BY manifiesto_anticipo.Ama_Cod DESC;";
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
            $sql = "UPDATE manifiesto_anticipo SET Ama_Tip = 'A' WHERE Ama_Cod = '" . $Par_Sql['Ama_Cod'] . "';";
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
    }
}
