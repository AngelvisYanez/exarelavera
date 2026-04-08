<?php

/**
 * Factura de venta
 */


function sentencias_grupoClientes($id, $Par_Sql)
{

    switch ($id) {
        case 1: //Listado de clientes
            if ($Par_Sql[2] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[3] == "") {
                $campos = "COUNT(Cli_Cod) as total";
            } else {
                $Par_Sql[3] = "ORDER BY Prs_Ape " . $Par_Sql[3];
                $campos = " Cli_Cod, persona.Prs_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, Cli_Dir, Prs_Dir, IF(Cli_Cor IS NULL OR TRIM(Cli_Cor)='',Prs_Cor,Cli_Cor)AS Prs_Cor, IF(Cli_Est='A','Activo','Inactivo') as Cli_Est";
            }
            $sql = "SELECT $campos FROM cliente, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search 
            AND cliente.Prs_Cod=persona.Prs_Cod AND Cli_Est='A' AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            break;


        case 2:

            // //ChromePhp::log($Par_Sql['Grup_Des']);
            $sql = "INSERT INTO grupo_clientes (Grup_Nom, Grup_Est, Emp_Cod, Grup_Des, Grup_Fec) 
            VALUES ('$Par_Sql[Grup_Nom]', 'A', '$Par_Sql[Emp_Cod]', '$Par_Sql[Grup_Des]', '$Par_Sql[Grup_Fec]');";

            break;

        case 3:
            $sql = "INSERT INTO det_grup_clientes (Cli_Cod, Cod_Grup) 
            VALUES ('$Par_Sql[0]','$Par_Sql[1]');";
            break;


        case 4:
            // //ChromePhp::log("codigo empresa:" . $Par_Sql['limits']);

            if ($Par_Sql['op_opciones_c'] == "cli") {
                if ($Par_Sql['op_opciones'] == "d") {
                    $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
                } else {
                    $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
                }
                if (isset($Par_Sql["limits"])) {
                    $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                    $campos = " Cli_Cod as Cod_Grup  , Prs_Ced  as Grup_Nom   , CONCAT(Prs_Ape,' ',Prs_Nom) as Grup_Des,Prs_Dir, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
                } else {
                    $campos = "COUNT(Cli_Cod) as total";
                    $Par_Sql["limits"] = "";
                }

                $sql = "SELECT $campos FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
           
            } else if ($Par_Sql['op_opciones_c'] == "gru") {

                if ($Par_Sql['op_opciones'] == "d") {
                    $search = "(Grup_Nom LIKE '%$Par_Sql[search]%' OR Grup_Des LIKE '%$Par_Sql[search]%')";
                } else {
                    $search = "Grup_Des LIKE '$Par_Sql[search]%'";
                }
                if (isset($Par_Sql["limits"])) {
                    $Par_Sql["limits"] = " ORDER BY Grup_Nom $Par_Sql[limits]";
                    $campos = " Cod_Grup,Grup_Nom,Grup_Des,Grup_Est ";
                } else {
                    $campos = "COUNT(Cod_Grup) as total";
                    $Par_Sql["limits"] = "";
                }

                $sql = "SELECT $campos FROM grupo_clientes WHERE $search AND  Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            }


            /* if ($Par_Sql['op_opciones'] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos = " Cli_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente,Prs_Dir, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
            } else {
                $campos = "COUNT(Cli_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            */

            //NUEVA CONSULTA
            /* if ($Par_Sql['op_opciones_c'] == "cli") {
                $sql = "SELECT $campos FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            } else if ($Par_Sql['op_opciones_c'] == "gru") {
                $sql = "SELECT $campos FROM grupo_clientes WHERE $search AND  Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            }*/

            // $sql = "SELECT $campos FROM grupo_clientes WHERE $search AND  Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            // $sql = "SELECT $campos FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            //ChromePhp::log($sql);
            break;

        case 5:
            $sql = "SELECT plan_cuenta.Pla_Cod, perio_cont.Pec_Cod, perio_cont.Pec_Fei, perio_cont.Pec_Fef, perio_cont.Pec_Est, Year(perio_cont.Pec_Fei) as priodo_m
                        FROM plan_cuenta, perio_cont
                        WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
                        ORDER BY Year(perio_cont.Pec_Fei) DESC";
            return $sql;
            break;

        case 7:
            $sql = "SELECT Dcc_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Pag_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Com_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,tipos_pago.For_Cod,Pag_Des,comprobantes.*,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo  FROM det_ccpp_c 
                    INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_c.Pag_Cod
                    INNER JOIN forma_pago ON tipos_pago.For_Cod=forma_pago.For_Cod
                    INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                    WHERE Cpc_Cod='$Par_Sql[0]' AND comprobantes.Com_Est='A'";
            //echo $sql;
            //ChromePhp::log($sql);
            break;


        case 8: /*consulta de facturas con pagos segun el proveedor*/
            if ($Par_Sql[5] != '') $Par_Sql[5] = " AND tipo_compr.Tic_Cod=$Par_Sql[5] ";
            if ($Par_Sql[1] != '') $Par_Sql[1] = " AND det_grup_clientes.Cod_Grup=$Par_Sql[1] ";
            // if ($Par_Sql[1] != '') $Par_Sql[1] = "AND cliente.Cli_Cod=$Par_Sql[1]";
            if ($Par_Sql[2] != '') $Par_Sql[2] = "AND perio_cont.Pec_Cod= $Par_Sql[2]";
            else $Par_Sql[2] = " AND Caj_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
            $sql = "SELECT cliente.Cli_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, 
            persona.Prs_Nom, ventas.Vet_Cod, ventas.Vet_Obs, ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec,
            
             IFNULL(grupo_clientes.Grup_Nom, 'Sin grupo') AS Grup_Nom, 
            
            det_grup_clientes.Cod_Grup,
             CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, ccpp_cobrar.Cpc_Ven, 
             ccpp_cobrar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,
                CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
                CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado'
                ,IF(DATEDIFF(Cpc_Ven,CURDATE())>=0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento 
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono, Tic_Des
                    FROM cliente
                INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)  
            
                LEFT JOIN det_grup_clientes ON det_grup_clientes.Cli_Cod = cliente.Cli_Cod 
                LEFT JOIN grupo_clientes ON det_grup_clientes.Cod_Grup = grupo_clientes.Cod_Grup

                INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) 
                INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
                INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
                INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod) 
                INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) 
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
                LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod 
                LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_c.Com_Cod),persona
                WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod 
                AND asientos.Com_Cod= comprobantes.Com_Cod  
                AND asientos.Asi_Deh= 'D' AND (ventas.vet_Est='A' OR ventas.vet_Est='E')  
                AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')
                    $Par_Sql[1] $Par_Sql[2] AND sucursal.Emp_Cod=$Par_Sql[0] $Par_Sql[5] 
                    GROUP BY ventas.Vet_Cod    ORDER BY   det_grup_clientes.Cod_Grup ,  Vet_Num /*ccpp_cobrar.Cpc_Ven*/  "; //

            // GROUP BY    ventas.Vet_Cod  order by  det_grup_clientes.Cod_Grup ";

            //echo $sql;
            //ChromePhp::log($sql);
            break;

        case 9:
            $sql = "SELECT CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) AS Com_Num,Com_Fec,CONCAT('',CAST(TRUNCATE(Com_Val, 2) AS char)) AS Com_Val,Com_Obs,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Ced FROM comprobantes INNER JOIN cliente ON cliente.Cli_Cod=comprobantes.Cli_Cod INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod WHERE Com_Cod='$Par_Sql[0]'";
            //echo $sql;
            break;
        case 10:
            $sql = "SELECT Asi_Cod,Asi_Deh,Pld_Cdc,Pld_Des,Asi_Glo as Glosa,Asi_Val,IF(Asi_Deh='D',Asi_Val,'') AS Debe,IF(Asi_Deh='H',Asi_Val,'') AS Haber FROM asientos INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod WHERE Com_Cod='$Par_Sql[0]' ORDER BY Asi_Deh";
            //echo $sql;
            break;

        case 11:
            $sql = "SELECT cheq_det_ccpp.Dcc_Cod,cheques_ext.*,Bak_Des,CONCAT(Prs_Ape,' ',Prs_Nom) as Benefactor FROM cheques_ext
                        INNER JOIN cheq_det_ccpp ON cheques_ext.Che_Cod=cheq_det_ccpp.Che_Cod
                        INNER JOIN det_ccpp_c ON cheq_det_ccpp.Dcc_Cod=det_ccpp_c.Dcc_Cod
                        INNER JOIN bancos ON cheques_ext.Bak_Cod=bancos.Bak_Cod
                        INNER JOIN cliente ON cheques_ext.Cli_Cod=cliente.Cli_Cod
                        INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                        WHERE det_ccpp_c.Cpc_Cod='$Par_Sql[0]' AND Com_Cod='$Par_Sql[1]'";
            //echo $sql;
            break;

        case 30:
            $sql = "SELECT Cpc_Fec,TRUNCATE(Cpc_Val, 2) AS Cpc_Val,ccpp_cobrar.Cpc_Obs,Pag_Des,Cpc_Ven,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,Caj_Fec FROM det_ccpp_c INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_c.Pag_Cod INNER JOIN ccpp_cobrar ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod INNER JOIN ventas ON ccpp_cobrar.Vet_Cod=ventas.Vet_Cod INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                        INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                        INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                        INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                WHERE det_ccpp_c.Cpc_Cod='$Par_Sql[0]' AND det_ccpp_c.Com_Cod='$Par_Sql[1]'";
            // echo $sql;
            //ChromePhp::log($sql);
            break;

        case 31:
            $sql = "SELECT ventas.Vet_Cod,CONCAT(Tia_Abr,'-',
            IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,Com_Fec,Asi_Val,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
            caja_aper.Caj_Fec,Ret_Num,Ret_Fec,'RT' AS tipo,Vet_Obs, Tic_Des,            
            IFNULL(grupo_clientes.Grup_Nom, 'Sin grupo') AS Grup_Nom,
            sum( ( 
            (ventas_det.Vet_Imp-(((ventas_det.Vet_Imp*ventas.Vet_Des)/100)+((ventas_det.Vet_Imp*ventas_det.Vet_Dec)/100))) /* IMPORTE */
              /*+(ventas_det.Vet_Imp-(((ventas_det.Vet_Imp*ventas.Vet_Des)/100)+((ventas_det.Vet_Imp*ventas_det.Vet_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0))*/ /* ICE */
              )	*(1+iva.Iva_Por/100)	/* IVA */
            ) AS total, Cpc_Cod
            FROM ventas
            INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
            INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
            /* LEFT JOIN ice ON (ice.Ice_int=ventas_det.Ice_Int) */
            INNER JOIN ccpp_cobrar ON ventas.Vet_Cod=ccpp_cobrar.Vet_Cod
            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
            INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
            INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
            INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
            INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
            INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
            INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod)
            INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 


            LEFT JOIN det_grup_clientes ON det_grup_clientes.Cli_Cod = cliente.Cli_Cod 
            LEFT JOIN grupo_clientes ON grupo_clientes.Cod_Grup = det_grup_clientes.Cod_Grup


            INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
            WHERE Cpc_Cod='$Par_Sql[0]'
            GROUP BY ventas.Vet_Cod ";
            //ChromePhp::log($sql);
            break;

        case 32:
            $sql = "SELECT Ret_Num,Ret_Fec,CONCAT('R',Ren_Ret) AS tipo,SUM((Vet_imp-(Vet_imp*(Vet_Des/100)))*(Ren_Por/100)) AS retencion FROM ventas_det
                    INNER JOIN renta_iva ON renta_iva.Ren_Cod=ventas_det.Ren_Cod
                    INNER JOIN ventas ON ventas_det.Vet_Cod=ventas.Vet_Cod
                    WHERE ventas.Vet_Cod='$Par_Sql[0]'
                    GROUP BY ventas.Vet_Cod";
            //echo $sql;
            break;
        case 33:
            $sql = "SELECT Ret_Num,Ret_Fec,CONCAT('R',Ren_Ret) AS tipo,SUM((Vet_Imp*(Iva_Por/100))*(Ren_Por/100)) AS retencion FROM ventas_det
                    INNER JOIN renta_iva ON renta_iva.Ren_Cod=ventas_det.Ren_Iva
                    INNER JOIN iva ON iva.Iva_Cod=ventas_det.Iva_Cod
                    INNER JOIN ventas ON ventas_det.Vet_Cod=ventas.Vet_Cod
                    WHERE ventas.Vet_Cod='$Par_Sql[0]'
                    GROUP BY ventas.Vet_Cod";
            //echo $sql;
            break;
        case 38:
            $sql = "SELECT cheques_ext.Che_Cod,Che_Fec,Che_Num,Bak_Des AS Banco,Che_Cta FROM cheques_ext 
                        INNER JOIN cheq_det_ccpp ON cheq_det_ccpp.Che_Cod=cheques_ext.Che_Cod
                        INNER JOIN bancos ON bancos.Bak_Cod=cheques_ext.Bak_Cod
                        WHERE cheq_det_ccpp.Dcc_Cod=$Par_Sql[0] AND (Che_Est!='I' AND Che_Est!='P') 
                        ";
            //echo $sql;
            break;

        case 46:
            $sql = " SELECT ventas.Vet_Cod,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
                        Com_Fec,Asi_Val,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,caja_aper.Caj_Fec,Ret_Num,Ret_Fec,'RT' AS tipo,Cpc_Cod, Asi_Val AS total, Tic_Des
                        FROM ventas
                        INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                        INNER JOIN ccpp_cobrar ON ventas.Vet_Cod=ccpp_cobrar.Vet_Cod 
                        INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                        INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod 
                        INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                        INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                        INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
                        INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
                        INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
                        INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) 
                        INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod 
                        WHERE Cpc_Cod='$Par_Sql[0]' GROUP BY ventas.Vet_Cod";
            return $sql;

        case 53:
            $sql = "SELECT Pag_Abr FROM tipos_pago WHERE Pag_Cod='$Par_Sql[0]' ";
            break;

            /**Obtener los datos de los clientes Agrupados */

        case 54:
            $sql = "SELECT * FROM grupo_clientes WHERE Emp_Cod='$Par_Sql[0]'  ";
            break;


        case 55:
            $sql = "  SELECT Count(Cod_Grup) As cant_grup FROM det_grup_clientes WHERE Cod_Grup='$Par_Sql[0]'  ";
            break;
    }

    return $sql;
}
