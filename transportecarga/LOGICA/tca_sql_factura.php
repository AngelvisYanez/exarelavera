<?php

/**
 * ACTIVOS FIJOS
 */
function sentencias_viajeFactura($id, $Par_Sql)
{
        switch ($id) {
                //Select para cargar los clientes que se encuentren relacionados con la tabla viajes
                case 1:
                        if ($Par_Sql['op_opciones'] == "d") {
                                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
                        } else {
                                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
                        }
                        if (isset($Par_Sql["limits"])) {
                                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                                $campos = "COUNT(Via_Cod) AS Via_Con,cliente.Cli_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente,Prs_Ced AS Prs_Ced1,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente1";
                        } else {
                                $campos = "COUNT(cliente.Cli_Cod) as total";
                                $Par_Sql["limits"] = "";
                        }
                        $sql = "SELECT $campos FROM cliente
                        INNER JOIN viaje ON cliente.Cli_Cod=viaje.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                        INNER JOIN ciudad ON persona.Ciu_Cod=ciudad.Ciu_Cod
                        WHERE $search AND Prs_Ced!='0' AND persona.Ide_Cod IS NOT NULL AND Via_Est='A' AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] AND viaje.Vet_Cod IS NULL GROUP BY cliente.Cli_Cod $Par_Sql[limits]";
                        break;
                //Select para listar los viajes sin facturar que tiene un cliente
                case 2:
                        $sql = "SELECT Via_Cod,Via_Fec,CONCAT(Prs_Ape,' ',Prs_Nom) AS Cho_Fer,Car_Des,Via_Can AS Vet_Can,Via_Pru AS Vet_Pru,(Via_Can*Via_Pru) AS Vet_Imp,cargamento.Pro_Cod,Ite_Lar,iva.Iva_Cod,Iva_Por,IF(ISNULL(producto.Ice_Int),'0',Ice_Por) AS Vet_Ice,Veh_Pla,Mot_Des 
                        FROM viaje
                        INNER JOIN chofer ON viaje.Cho_Cod=chofer.Cho_Cod
                        INNER JOIN persona ON chofer.Prs_Cod=persona.Prs_Cod
                        INNER JOIN cargamento ON viaje.Car_Cod=cargamento.Car_Cod
                        INNER JOIN vehiculo ON viaje.Veh_Cod=vehiculo.Veh_Cod
                        INNER JOIN producto ON cargamento.Pro_Cod=producto.Pro_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                        LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
                        INNER JOIN modo_trabajo ON viaje.Mot_Cod=modo_trabajo.Mot_Cod
                        WHERE viaje.Cli_Cod='$Par_Sql[0]' AND Via_Est='A' AND viaje.Vet_Cod IS NULL";
                        break;
                //Select para listar los tipos de comprobantes de Tic_Sr1=0,1,2
                case 3:
                        $sql = "SELECT Tic_Cod,Tic_Sri,Tic_Des 
                        FROM tipo_compr
                        WHERE (Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2') AND Tic_Est='A'";
                        break;
                //Select para obtener el n�mero m�ximo de la secuencia de factura
                case 4:
                        $sql = "SELECT vendedor.Vnd_Cod,puntos_imp.Pun_Cod,vendedor.Prs_Cod,autorizaci.Aut_Cod,Aut_Sri,Aut_Fci,Aut_Cad,Aut_Ini,Aut_Fin,Vet_Cod,CURDATE() AS Fec_Sys
                        FROM vendedor
                        INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                        INNER JOIN autorizaci ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                        LEFT JOIN ventas ON autorizaci.Aut_Cod=ventas.Aut_Cod
                        WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND autorizaci.Tic_Cod='$Par_Sql[2]' AND autorizaci.Aut_Est='A' GROUP BY autorizaci.Aut_Cod";
                        break;
                //Consulta la ciudad en base al usuario
                case 5:
                        $sql = "SELECT sucursal.Ciu_Cod,ciudad.Ciu_Des 
                        FROM usuarios, sucursal, ciudad 
                        WHERE usuarios.Suc_Cod = sucursal.Suc_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod AND usuarios.Usu_Cod = '$Par_Sql[0]'";
                        break;
                //Select para cargar los ivas de la tabla del mismo nombre
                case 6:
                        $sql = "SELECT Iva_Cod,Iva_Por
                        FROM iva
                        WHERE Iva_Est='A' AND Iva_Por>0 ORDER BY Iva_Fin DESC";
                        break;
                //Select para obtener el Pun_Cod y Vnd_Cod del usuario que ha iniciado la sesi�n
                case 7:
                        $sql = "SELECT Vnd_Cod,vendedor.Pun_Cod 
                        FROM vendedor
                        INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                        WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND puntos_imp.Pun_Est='A'";
                        break;
                //INSERT en la tabla caja_aper, con el prop�sito de aperturar la caja este proceso es invisible para el usuario
                case 8:
                        $sql = "INSERT INTO caja_aper(Pun_Cod,Caj_Fec,Caj_Hoi,Caj_Est,Caj_Gen) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]',CURTIME(),'C','S')";
                        break;
                //INSERT en la tabla ventas
                case 9:
                        $sql = "INSERT INTO ventas(Aut_Cod,Tic_Cod,Cli_Cod,Ciu_Cod,Caj_Cod,Vnd_Cod,Vet_Num,Vet_Des,Vet_Obs,Vet_Hor,Tpc_Cod) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]',UPPER('$Par_Sql[8]'),CURTIME(),'$Par_Sql[9]')";
                        break;
                //INSERT en la tabla ventas_det
                case 10:
                        $sql = "INSERT INTO ventas_det(Vet_Ite,Vet_Cod,Pro_Cod,Iva_Cod,Vet_Can,Vet_Pru,Vet_Imp,Vet_Dec,Vet_Ice) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','0','$Par_Sql[7]')";
                        break;
                //Select para obtener las formas de pago disponibles
                case 11:
                        $sql = "SELECT For_Cod,For_Des
                        FROM forma_pago
                        WHERE For_Est='A'";
                        break;
                //Select para obtener los tipos de pago
                case 12:
                        $sql = "SELECT tipos_pago.Pag_Cod,Pag_Des
                        FROM tipos_pago,pago_plan
                        WHERE For_Cod='$Par_Sql[0]' AND tipos_pago.Pag_Est='A' AND tipos_pago.Pag_Cod=pago_plan.Pag_Cod GROUP BY tipos_pago.Pag_Cod";
                        break;
                //Select para cargar los bancos del plan de cuentas
                case 13:
                        $sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des 
                        FROM banco, det_plan, pago_plan, plan_cuenta
                        WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod 
                        AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND pago_plan.Pag_Cod = '$Par_Sql[0]' 
                        AND plan_cuenta.Emp_Cod = '$Par_Sql[1]' AND pago_plan.Pag_Est='A' ORDER BY Pld_Cdc, Pld_Des";
                        break;
                //Select para cargar los datos de la tabla tipopagocom
                case 14:
                        $sql = "SELECT Tpc_Cod,CONCAT(Tpc_Sri,' - ',Tpc_Des) AS Tpc_Des FROM tipopagocom WHERE Tpc_Est='A'";
                        break;
                //INSERT en la tabla pago_venta
                case 15:
                        $sql = "INSERT INTO pago_venta(Vet_Cod,Bak_Cod,Ban_Cod,Pag_Cod,Vet_Cue,Vet_Che,Vet_Tot,Vet_Num,Mon_Cod) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]'," . (empty($Par_Sql[2]) ? 'NULL' : $Par_Sql[2]) . ",'$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','1','1')";
                        break;
                //Select para cargar los datos de la tabla perio_cont
                case 16:
                        $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio 
                        FROM perio_cont
                        INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                        WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND Pec_Est='A' ORDER BY Pec_Fei DESC";
                        break;
                //Select para cargar la configuracion de factura para saber si esta obligado o no a llevar contabilidad
                case 17:
                        $sql = "SELECT Cof_Cod,Cof_Con 
                        FROM confi_fact
                        WHERE Emp_Cod='$Par_Sql[0]'";
                        break;
                //INSERT en la tabla comprobantes
                case 18:
                        $sql = "INSERT INTO comprobantes(Pec_Cod,Cli_Cod,Usu_Cod,Com_Num,Com_Fec,Com_Con,Com_Val,Com_Obs,Tia_Cod,Com_Gen) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]',UPPER('$Par_Sql[5]'),'$Par_Sql[6]',UPPER('$Par_Sql[7]'),'$Par_Sql[8]','A')";
                        break;
                //SELECT para obtener el Pld_Cod de un producto sobre la tabla produ_plan
                case 19:
                        $sql = "SELECT Pld_Cod FROM produ_plan WHERE Pro_Cod=$Par_Sql[0] AND (Tip_Pld='V' OR Tip_Pld='I')";
                        break;
                //Insert en la tabla asientos
                case 20:
                        $sql = "INSERT INTO asientos(Com_Cod,Asi_Deh,Asi_Val,Asi_Con,Pld_Cod) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]')";
                        //echo $sql;
                        break;
                //Select para obtener el Pld_Cod de la tabla banco
                case 21:
                        $sql = "SELECT Pld_Cod FROM banco WHERE Ban_Cod=$Par_Sql[0]";
                        break;
                //Select para obtener el Pld_Cod del iva cobrado
                case 22:
                        $sql = "SELECT iva_cobrad.Pld_Cod FROM iva_cobrad 
                        INNER JOIN det_plan ON iva_cobrad.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN perio_cont ON perio_cont.Pla_Cod=det_plan.Pla_Cod
                        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                        WHERE perio_cont.Pec_Cod='$Par_Sql[0]' AND Pla_Est='A' AND Pld_Est='A'";
                        break;
                //Insert en la tabla ccpp_cobrar
                case 23:
                        $sql = "INSERT INTO ccpp_cobrar(Com_Cod,Vet_Cod,Cpc_Ven,Cpc_Obs) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]',UPPER('$Par_Sql[3]'))";
                        break;
                //Select para obtener las cuentas deudoras
                case 24:
                        $sql = "SELECT ccpp_cliente.Pld_Cod, det_plan.Pld_Des, ccpp_cliente.Cpc_Def, ccpp_cliente.Cpc_Cxc FROM det_plan 
                        INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod)
                        INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                        INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                        WHERE perio_cont.Pec_Cod ='$Par_Sql[0]' AND plan_cuenta.Emp_Cod='$Par_Sql[1]' AND det_plan.Pld_Est='A'";
                        break;
                //Select para obtener todos los n�meros de secuencia correspondientes a un Aut_Cod c�digo de autorizaci�n
                case 25:
                        $sql = "SELECT 
                            CASE         
                                WHEN MAX(Vet_Num)IS NOT NULL AND MAX(Vet_Num)>=$Par_Sql[1] THEN ( 
                                    SELECT MIN(t.Vet_Num)+1
                                    FROM ventas t 
                                    INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                                    INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                                    WHERE tp.Suc_Cod=$Par_Sql[4] AND ta.Aut_Sri='$Par_Sql[2]' AND ta.Tic_Cod=$Par_Sql[3] AND t.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1] AND
                                    NOT EXISTS (
                                        SELECT NULL FROM ventas n 
                                            INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                            INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                            WHERE n.Vet_Num=t.Vet_Num+1 AND np.Suc_Cod=$Par_Sql[4] AND na.Aut_Sri='$Par_Sql[2]' AND na.Tic_Cod=$Par_Sql[3] AND n.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]
                                        )
                                   )            
                            ELSE IFNULL(MAX(Vet_Num),$Par_Sql[0]-1)+1
                            END AS siguiente
                        FROM ventas
                        INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                        INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                        WHERE Suc_Cod=$Par_Sql[4] AND autorizaci.Aut_Sri='$Par_Sql[2]' AND autorizaci.Tic_Cod=$Par_Sql[3] AND Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]";
                        break;
                //SELECT para obtener los registros de la tabla bancos
                case 26:
                        $sql = "SELECT * FROM bancos WHERE Bak_Est='A'";
                        break;
                //UPDATE sobre la tabla viajes para ubicar el Vet_Cod
                case 27:
                        $sql = "UPDATE viaje SET Vet_Cod=" . (empty($Par_Sql[0]) ? 'NULL' : $Par_Sql[0]) . ",Iva_Cod=" . (empty($Par_Sql[1]) ? 'NULL' : $Par_Sql[1]) . " WHERE Via_Cod='$Par_Sql[2]'";
                        break;
                //SELECT para verificar si la caja ya fue creada dentro de la tabla caja_aper
                case 28:
                        $sql = "SELECT Caj_Cod,Pun_Cod,Caj_Fec
                        FROM caja_aper
                        WHERE Pun_Cod='$Par_Sql[0]' AND Caj_Fec='$Par_Sql[1]'";
                        break;
                //Insert en la tabla ventas_compr
                case 29:
                        $sql = "INSERT INTO ventas_compr(Vet_Cod,Com_Cod) 
                        VALUES('$Par_Sql[0]','$Par_Sql[1]')";
                        break;

                /*SENTENCIAS PARA EL MANEJO DEL ARCHIVO tca_mod_factura_1.0.php*/
                //Select para cargar las facturas relacionadas con la tabla viaje
                case 30:
                        if ($Par_Sql['op_opciones'] == "cli") {
                                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
                        } else {
                                if ($Par_Sql['op_opciones'] == "ced") {
                                        $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
                                } else {
                                        $search = "Vet_Num LIKE '$Par_Sql[search]%'";
                                }
                        }
                        if (isset($Par_Sql["limits"])) {
                                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                                $campos = "ventas.Vet_Cod,Vet_Num,Vet_Est,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS Cli_Nte,Caj_Fec,Cpc_Cod";
                        } else {
                                $campos = "COUNT(ventas.Vet_Cod) as total";
                                $Par_Sql["limits"] = "";
                        }

                        $whereEstado = "";
                        if ($Par_Sql['op_est'] == "A") {
                                $whereEstado = " AND ventas.Vet_Est='A'";
                        } else if ($Par_Sql['op_est'] == "I") {
                                $whereEstado = " AND ventas.Vet_Est='I'";
                        } else {
                                $whereEstado;
                        }

                        // Filtro de fechas
                        $whereFechas = "";
                        if (!empty($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Fin'])) {
                                $whereFechas = " AND caja_aper.Caj_Fec BETWEEN '$Par_Sql[Fec_Ini]' AND '$Par_Sql[Fec_Fin]'";
                        } else if (!empty($Par_Sql['Fec_Ini'])) {
                                $whereFechas = " AND caja_aper.Caj_Fec >= '$Par_Sql[Fec_Ini]'";
                        } else if (!empty($Par_Sql['Fec_Fin'])) {
                                $whereFechas = " AND caja_aper.Caj_Fec <= '$Par_Sql[Fec_Fin]'";
                        }

                        $sql = "SELECT $campos FROM viaje
                                INNER JOIN ventas ON viaje.Vet_Cod=ventas.Vet_Cod
                                INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                                INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                                INNER JOIN caja_aper ON ventas.Caj_Cod=caja_aper.Caj_Cod
                                LEFT JOIN ccpp_cobrar ON ventas.Vet_Cod=ccpp_cobrar.Vet_Cod
                        WHERE $search $whereEstado $whereFechas AND
                                cliente.Emp_Cod='$Par_Sql[Emp_Cod]' AND
                                viaje.Vet_Cod IS NOT NULL
                        GROUP BY ventas.Vet_Cod $Par_Sql[limits]";
                        break;
                //Select para cargar los datos de la factura seleccionada
                case 31:
                        $sql = "SELECT ventas.Vet_Cod,ventas.Vet_Num,Prs_Ced,Prs_Ced AS Prs_Ced1,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente1,Caj_Fec,c1.Ciu_Des,Prs_Dir,ventas.Tic_Cod,tipo_compr.Tic_Des,c2.Ciu_Des AS Ciu_De1,Aut_Sri,Aut_Ini,Aut_Fin,Vet_Obs,pago_venta.Pag_Cod,forma_pago.For_Cod,forma_pago.For_Des,
                        pago_venta.Bak_Cod,perio_cont.Pec_Cod,perio_cont.Pec_Fei,perio_cont.Pec_Fef,CAST(SUBSTRING_INDEX(perio_cont.Pec_Fei,'-',1) AS char) AS Anio,Cpc_Ven,ccpp_cliente.Pld_Cod,pago_venta.Ban_Cod,pago_venta.Vet_Cue,pago_venta.Vet_Che,
                        Vet_Des,cliente.Cli_Cod,ccpp_cobrar.Cpc_Cod,comprobantes.Com_Cod,ventas.Tpc_Cod,tipos_pago.Pag_Des,Com_Val
                        FROM viaje
                        INNER JOIN ventas ON viaje.Vet_Cod=ventas.Vet_Cod
                        INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                        INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                        INNER JOIN caja_aper ON ventas.Caj_Cod=caja_aper.Caj_Cod
                        INNER JOIN ciudad as c1 ON c1.Ciu_Cod=persona.Ciu_Cod
                        INNER JOIN tipo_compr ON ventas.Tic_Cod=tipo_compr.Tic_Cod
                        INNER JOIN ciudad as c2 ON c2.Ciu_Cod=ventas.Ciu_Cod
                        INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                        INNER JOIN pago_venta ON ventas.Vet_Cod=pago_venta.Vet_Cod
                        INNER JOIN tipos_pago ON pago_venta.Pag_Cod=tipos_pago.Pag_Cod
                        INNER JOIN forma_pago ON tipos_pago.For_Cod=forma_pago.For_Cod
                        INNER JOIN bancos ON pago_venta.Bak_Cod=bancos.Bak_Cod
                        LEFT JOIN ventas_compr ON ventas.Vet_Cod=ventas_compr.Vet_Cod
                        LEFT JOIN comprobantes ON ventas_compr.Com_Cod=comprobantes.Com_Cod
                        LEFT JOIN perio_cont ON comprobantes.Pec_Cod=perio_cont.Pec_Cod
                        LEFT JOIN ccpp_cobrar ON comprobantes.Com_Cod=ccpp_cobrar.Com_Cod
                        LEFT JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                        LEFT JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod
                        LEFT JOIN ccpp_cliente ON det_plan.Pld_Cod=ccpp_cliente.Pld_Cod
                        LEFT JOIN iva ON viaje.Iva_Cod=iva.Iva_Cod
                        WHERE ventas.Vet_est='A' AND cliente.Emp_Cod='$Par_Sql[0]' AND ventas.Vet_Cod='$Par_Sql[1]' GROUP BY Vet_Cod";
                        break;
                //Select para cargar el detalle de la factura seleccionada
                case 32:
                        $sql = "SELECT Via_Cod,cargamento.Pro_Cod,viaje.Iva_Cod,Via_Fec,Via_Can AS Vet_Can,Car_Des,Via_Pru AS Vet_Pru,(Via_Can*Via_Pru) AS Vet_Imp,iva.Iva_Por,IF(ISNULL(producto.Ice_Int),'0',Ice_Por) AS Vet_Ice,Ite_Lar,Mot_Des
                        FROM viaje 
                        INNER JOIN cargamento ON viaje.Car_Cod=cargamento.Car_Cod
                        INNER JOIN producto ON cargamento.Pro_Cod=producto.Pro_Cod
                        INNER JOIN iva ON viaje.Iva_Cod=iva.Iva_Cod
                        LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN modo_trabajo ON viaje.Mot_Cod=modo_trabajo.Mot_Cod
                        WHERE Vet_Cod='$Par_Sql[0]' AND Via_Est='A'";
                        break;
                //Select para cargar los clientes
                case 33:
                        if ($Par_Sql['op_opciones'] == "d") {
                                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
                        } else {
                                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
                        }
                        if (isset($Par_Sql["limits"])) {
                                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                                $campos = "cliente.Prs_Cod,Cli_Cod,Prs_Ced AS Prs_Ced1,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente1,Prs_Dir,Ciu_Des";
                        } else {
                                $campos = "COUNT(cliente.Prs_Cod) as total";
                                $Par_Sql["limits"] = "";
                        }
                        $sql = "SELECT $campos FROM cliente,persona,ciudad
                        WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND Prs_Est='A' AND persona.Ciu_Cod=ciudad.Ciu_Cod AND cliente.Cli_Est='A' AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
                        break;
                //UPDATE para cambiar el cliente de la factura sobre las tablas viaje,ventas
                case 34:
                        $sql = "UPDATE viaje
                        SET viaje.Cli_Cod='$Par_Sql[0]'
                        WHERE viaje.Vet_Cod='$Par_Sql[1]'";
                        //echo $sql;
                        break;
                //UPDATE para cambiar el cliente de la factura sobre la tabla comprobantes
                case 35:
                        $sql = "UPDATE comprobantes,ventas_compr 
                        SET comprobantes.Cli_Cod='$Par_Sql[0]',Com_Fec='$Par_Sql[1]',Com_Con=UPPER('$Par_Sql[2]'),Com_Val='$Par_Sql[3]'
                        WHERE ventas_compr.Vet_Cod='$Par_Sql[4]' AND ventas_compr.Com_Cod=comprobantes.Com_Cod";
                        //echo $sql;
                        break;
                //UPDATE sobre la tabla ventas
                case 36:
                        $sql = "UPDATE ventas
                        SET ventas.Tic_Cod='$Par_Sql[0]',ventas.Vet_Num='$Par_Sql[1]',ventas.Caj_Cod='$Par_Sql[2]',ventas.Vet_Des='$Par_Sql[3]',ventas.Vet_Obs=UPPER('$Par_Sql[4]')
                        WHERE ventas.Vet_Cod='$Par_Sql[5]'";
                        break;
                //SELECT para comprobar si el n�mero de secuencia existe
                case 37:
                        $sql = "SELECT Vet_Num
                        FROM vendedor
                        INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                        INNER JOIN autorizaci ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                        LEFT JOIN ventas ON autorizaci.Aut_Cod=ventas.Aut_Cod
                        WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND autorizaci.Tic_Cod='$Par_Sql[2]' AND autorizaci.Aut_Est='A' AND Vet_Num='$Par_Sql[3]'";
                        break;
                //UPDATE para cambiar el tipo de pago de la factura 
                case 38:
                        $sql = "UPDATE pago_venta
                        SET Bak_Cod='$Par_Sql[0]',Ban_Cod=" . (empty($Par_Sql[1]) ? 'NULL' : $Par_Sql[1]) . ",Pag_Cod='$Par_Sql[2]',Vet_Cue='$Par_Sql[3]',Vet_Che='$Par_Sql[4]',Vet_Tot='$Par_Sql[5]'
                        WHERE Vet_Cod='$Par_Sql[6]'";
                        break;
                //UPDATE sobre la tabla ccpp_cobrar 
                case 39:
                        $sql = "UPDATE ccpp_cobrar
                        SET Cpc_Ven='$Par_Sql[0]'
                        WHERE Cpc_Cod='$Par_Sql[1]'";
                        break;
                //DELETE sobre la tabla ccpp_cobrar
                case 40:
                        $sql = "DELETE FROM ccpp_cobrar
                        WHERE Cpc_Cod='$Par_Sql[0]'";
                        break;
                //Select para conocer si existe al menos un pago registrado en la tabla det_ccpp_c
                case 41:
                        $sql = "SELECT COUNT(Cpc_Cod) AS Num_Pag FROM det_ccpp_c WHERE Cpc_Est='A' AND Cpc_Cod='$Par_Sql[0]'";
                        break;
                //UPDATE sobre la tabla asientos con el prop�sito de modificar el Pld_Cod 
                case 42:
                        $sql = "UPDATE asientos
                        SET Pld_Cod='$Par_Sql[0]'
                        WHERE Com_Cod='$Par_Sql[1]' AND Asi_Deh='D'";
                        break;
                //Select para obtener el valor total de los pagos efectuados
                case 43:
                        $sql = "SELECT ccpp_cobrar.Cpc_Cod,SUM(Cpc_Val) AS Tot_Pag
                        FROM ccpp_cobrar 
                        INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod=ccpp_cobrar.Cpc_Cod
                        WHERE Vet_Cod='$Par_Sql[0]' AND det_ccpp_c.Cpc_Est='A' GROUP BY Cpc_Cod";
                        break;
                //Borrado del codigo del detalle de la Venta 
                case 44:
                        $sql = "DELETE FROM ventas_det WHERE Vet_Cod='$Par_Sql[0]'";
                        break;
                //Borrado del registro de la tabla pago_venta concerniente al vet_cod
                case 45:
                        $sql = "DELETE FROM pago_venta WHERE Vet_Cod='$Par_Sql[0]'";
                        break;
                //DELETE sobre la tabla asientos concerniente al Com_Cod(comprobantes) respectivo
                case 46:
                        $sql = "DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
                        break;
                //Eliminaci�n l�gica sobre la tabla viaje
                case 47:
                        $sql = "UPDATE viaje SET viaje.Vet_Cod=" . (empty($Par_Sql[0]) ? 'NULL' : $Par_Sql[0]) . ",viaje.Iva_Cod=" . (empty($Par_Sql[1]) ? 'NULL' : $Par_Sql[1]) . " where viaje.Vet_Cod='$Par_Sql[2]'";
                        break;
                //Select para obtener el Pld_Cod del ice
                case 48:
                        $sql = "SELECT Pld_Cod FROM tipo_param 
                        INNER JOIN plan_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                        WHERE Tpa_Abr='$Par_Sql[0]' AND Ppc_Est='A'";
                        break;
                //Select para obtener los datos de la tabla asientos
                case 49:
                        $sql = "SELECT asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Con, Asi_Deh,
                        IF(Asi_Deh='D',ROUND(Asi_Val,2),'') AS Asi_Deb,IF(Asi_Deh='H',ROUND(Asi_Val,2),'') AS Asi_Hab
                        FROM asientos, det_plan 
                        WHERE asientos.Com_Cod='$Par_Sql[0]' AND asientos.Pld_Cod=det_plan.Pld_Cod ORDER BY Asi_Deh, Asi_Cod ASC";
                        //echo $sql;
                        break;
                //Select para cargar los datos de la cabecera del comprobante
                case 50:
                        $sql = "SELECT Tia_Des,CONCAT(Tia_Abr,'-',SUBSTRING_INDEX(SUBSTRING_INDEX(Com_Fec,'-',2),'-',-1),'-',Com_Num) AS Nro_Com,ROUND(Com_Val,2) AS Com_Val,Com_Fec,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente,Com_Con,Com_Obs
                        FROM comprobantes 
                        INNER JOIN tipo_asien ON comprobantes.Tia_Cod=tipo_asien.Tia_Cod
                        INNER JOIN cliente ON comprobantes.Cli_Cod=cliente.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                        WHERE Com_Cod='$Par_Sql[0]' AND Com_Est='A'";
                        //echo $sql;
                        break;
                //Consulta el codigo del proceso 
                case 51:
                        $sql = "SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom LIKE '$Par_Sql[0]%' ORDER BY Pcs_Cod DESC LIMIT 1";
                        break;
                //Consulta el reporte recursivo 
                case 52:

                        $sql = "SELECT 
			  reportes.Rep_Cod,
			  procesos.Pcs_Nom,
			  reportes.Rep_Ord,
			  rutas.Rut_Des
			FROM
			  procesos
			  INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
			  INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) 
			WHERE 
			  reportes.Pcs_Cod = $Par_Sql[0] AND reportes.Emp_Cod = $Par_Sql[1] ORDER BY reportes.Rep_Ord";
                        break;
                //UPDATE para cambiar el cliente de la factura sobre la tabla ventas
                case 53:
                        $sql = "UPDATE ventas
                        SET ventas.Cli_Cod='$Par_Sql[0]'
                        WHERE ventas.Vet_Cod='$Par_Sql[1]'";
                        //echo $sql;
                        break;
                //Select para obtener los datos de la empresa
                case 54:
                        $sql = "SELECT empresas.Emp_Nom,Emp_Ruc,ciudad.Ciu_Des,sucursal.Ciu_Cod,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Te2,sucursal.Suc_Fax,
                        sucursal.Suc_Cor,sucursal.Suc_Web,sucursal.Suc_Des,empresas.Emp_Log,CONCAT(ciudad.Ciu_Des,' - ',provincia.Pro_Nom,' - ',pais.Pas_Nom) AS provincia
                        FROM sucursal
                        INNER JOIN empresas ON empresas.Emp_Cod=sucursal.Emp_Cod
                        INNER JOIN ciudad ON ciudad.Ciu_Cod=sucursal.Ciu_Cod
                        INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod
                        INNER JOIN regiones ON regiones.Reg_Cod=provincia.Reg_Cod  
                        INNER JOIN pais ON pais.Pas_Cod=regiones.Pas_Cod  
                        WHERE sucursal.Suc_Cod = '$Par_Sql[0]' ";
                        break;
                //Select para cargar los clientes que se encuentren relacionados con la tabla viajes
                case 55:
                        if ($Par_Sql['op_opciones'] == "d") {
                                $search = "(Veh_Pla LIKE '%$Par_Sql[search]%')";
                        } else {
                                $search = "Via_Fec LIKE '$Par_Sql[search]%'";
                        }
                        if (isset($Par_Sql["limits"])) {
                                $Par_Sql["limits"] = "ORDER BY Via_Fec $Par_Sql[limits]";
                                $campos = "Via_Cod,cargamento.Pro_Cod,Via_Fec,Via_Can AS Vet_Can,Car_Des,Via_Pru AS Vet_Pru,(Via_Can*Via_Pru) AS Vet_Imp,IF(ISNULL(producto.Ice_Int),'0',Ice_Por) AS Vet_Ice,Ite_Lar,Mot_Des,CONCAT(Prs_Ape,' ',Prs_Nom) AS Cho_Fer,Veh_Pla,iva.Iva_Cod,Iva_Por";
                        } else {
                                $campos = "COUNT(cliente.Cli_Cod) as total";
                                $Par_Sql["limits"] = "";
                        }
                        $sql = "SELECT $campos FROM viaje
                        INNER JOIN cliente ON viaje.Cli_Cod=cliente.Cli_Cod 
                        INNER JOIN cargamento ON viaje.Car_Cod=cargamento.Car_Cod
                        INNER JOIN producto ON cargamento.Pro_Cod=producto.Pro_Cod
                        INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                        LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN modo_trabajo ON viaje.Mot_Cod=modo_trabajo.Mot_Cod
                        INNER JOIN chofer ON viaje.Cho_Cod=chofer.Cho_Cod
                        INNER JOIN persona ON chofer.Prs_Cod=persona.Prs_Cod
                        INNER JOIN vehiculo ON viaje.Veh_Cod=vehiculo.Veh_Cod
                        WHERE $search AND viaje.Cli_Cod='$Par_Sql[Cli_Cod]' AND viaje.Vet_Cod IS NULL AND Via_Est='A' $Par_Sql[limits]";
                        //echo $sql;
                        break;
                //Select para obtener el iva por defecto, el cual corresponde al �ltimo vigente
                case 56:
                        $sql = "SELECT Iva_Cod FROM iva WHERE '$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin";
                        break;
                //Select para identificar los viajes que ya han sido registrados
                case 57:
                        $sql = "SELECT Via_Cod,IF(ISNULL(Vet_Cod),'NO','SI') AS Existe
                        FROM viaje
                        WHERE Via_Cod='$Par_Sql[0]' AND Via_Est='A'";
                        break;
        }
        return $sql;
}
