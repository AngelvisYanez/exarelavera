<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2016-06-28
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package inv.LOGICA
 */


function sentencias_doc($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            //echo $sql.'<br/>';
            break;
        case 1:
            /**
             * Con esta sentencia consulto producto y stock
             */
            if (empty($Par_Sql['limits'])) $campos = " COUNT(comprobantes.Com_Cod) AS total ";
            else /*$campos = " DISTINCT Tia_Ini,IF(Tia_Ini='D','Diario',IF(Tia_Ini='I','Ingreso','Egreso'))AS Tipo,CAST(CONCAT(Tia_Abr,'-',LPAD(MONTH(Com_Fec),2,'0'),'-',Com_Num)AS char)AS Codigo,
                        IF(ventas_compr.Vet_Cod IS NOT NULL,'Ventas',IF(compr_auto.Cop_Cod IS NOT NULL,'Compras',''))AS Doc ,
                        IF(ventas_compr.Vet_Cod IS NOT NULL,Caj_Fec,IF(compr_auto.Cop_Cod IS NOT NULL,Cop_Fec,''))AS Doc_Fec ,
                        IF(ventas_compr.Com_Cod IS NOT NULL,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)), 
                        IF(compr_auto.Cop_Cod IS NOT NULL,Cop_Num,''))AS Doc_Num,
                        compras.Cop_Cod,ventas.Vet_Cod,comprobantes.*,Tia_Des, 
                        IF(comprobantes.Prv_Cod IS NOT NULL,prs_prv.Prs_Ced,prs_cli.Prs_Ced)AS Prs_Ced,
                           COALESCE(
                            CONCAT(prs_prv.Prs_Nom, ' ', prs_prv.Prs_Ape),
                            CONCAT(prs_cli.Prs_Nom, ' ', prs_cli.Prs_Ape)
                        ) AS Nom_ClientProvee,
                        IF(comprobantes.Prv_Cod IS NOT NULL,CONCAT(prs_prv.Prs_Ape,' ',prs_prv.Prs_Nom),CONCAT(prs_cli.Prs_Ape,' ',prs_cli.Prs_Nom))AS Persona, CONCAT(resp.Prs_Ape,' ',resp.Prs_Nom) as responsable";
                */

                $campos = " DISTINCT Tia_Ini,IF(Tia_Ini='D','Diario',IF(Tia_Ini='I','Ingreso','Egreso'))AS Tipo,CAST(CONCAT(Tia_Abr,'-',LPAD(MONTH(Com_Fec),2,'0'),'-',Com_Num)AS char)AS Codigo,
                        IF(ventas_compr.Vet_Cod IS NOT NULL,'Ventas',IF(compr_auto.Cop_Cod IS NOT NULL,'Compras',''))AS Doc ,
                        IF(ventas_compr.Vet_Cod IS NOT NULL,Caj_Fec,IF(compr_auto.Cop_Cod IS NOT NULL,Cop_Fec,''))AS Doc_Fec ,
                        IF(ventas_compr.Com_Cod IS NOT NULL,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)), 
                        IF(compr_auto.Cop_Cod IS NOT NULL,Cop_Num,''))AS Doc_Num,
                        compras.Cop_Cod,ventas.Vet_Cod,comprobantes.*,Tia_Des,
                        COALESCE(
                            prs_user.Prs_Ced,
                            IF(comprobantes.Prv_Cod IS NOT NULL, prs_prv.Prs_Ced, prs_cli.Prs_Ced)
                        ) AS Prs_Ced,
                        COALESCE(
                            CASE 
                                WHEN prs_user.Prs_Cod IS NOT NULL THEN CONCAT(prs_user.Prs_Nom, ' ', prs_user.Prs_Ape) 
                                ELSE NULL 
                            END,
                            CONCAT(prs_prv.Prs_Nom, ' ', prs_prv.Prs_Ape),
                            CONCAT(prs_cli.Prs_Nom, ' ', prs_cli.Prs_Ape)
                        ) AS Nom_ClientProvee,
                        IF(comprobantes.Prv_Cod IS NOT NULL,CONCAT(prs_prv.Prs_Ape,' ',prs_prv.Prs_Nom), CONCAT(prs_cli.Prs_Ape,' ',prs_cli.Prs_Nom))AS Persona,
                        CONCAT(resp.Prs_Ape,' ',resp.Prs_Nom) as responsable";

            $osql = '';
            $estado = '';
            //Para buscar por cuenta
            $search_pldc = "";
            if ($Par_Sql['Com_Est'] == '') {
                $estado = "Com_Est<>'E'";
            } else {
                $estado = "Com_Est='B'";
            }
            if (!empty($Par_Sql['comp'])) {
                if (!empty($Par_Sql['Prs_Cod'])) {
                    $osql = $osql . " AND ( ";
                    if (!empty($Par_Sql['Prv_Cod'])) $osql = $osql . " comprobantes.Prv_Cod=$Par_Sql[Prv_Cod] ";
                    if (!empty($Par_Sql['Prv_Cod']) && !empty($Par_Sql['Cli_Cod'])) $osql = $osql . " OR ";
                    if (!empty($Par_Sql['Cli_Cod'])) $osql = $osql . " comprobantes.Cli_Cod=$Par_Sql[Cli_Cod] ";
                    $osql = $osql . " ) ";
                }
                if (!empty($Par_Sql['Tia_Ini']))
                    $osql = $osql . " AND Tia_Ini='$Par_Sql[Tia_Ini]' ";
                if (!empty($Par_Sql['Tia_Cod']))
                    $osql = $osql . " AND tipo_asien.Tia_Cod='$Par_Sql[Tia_Cod]' ";
                if ($Par_Sql['op_comp'] == 'a') {
                    if (!empty($Par_Sql['Month'])) {
                        list($ann, $mes) = explode('-', $Par_Sql['Month']);
                        $ini = $Par_Sql['Month'] . '-' . '01 00:00:00';
                        $fin = $Par_Sql['Month'] . '-' . ultimoDia($mes, $ann) . ' 23:59:59';
                        $osql = $osql . " AND Com_Fec BETWEEN '$ini' AND '$fin' ";
                    }
                    if (!empty($Par_Sql['Com_Num']))
                        $osql = $osql . " AND Com_Num='$Par_Sql[Com_Num]' ";
                }
                /* if ($Par_Sql['op_comp'] == 'r') {
                    $ini = $Par_Sql['Asi_Ini'] . ' 00:00:00';
                    $fin = $Par_Sql['Asi_Fin'] . ' 23:59:59';
                    $osql = $osql . " AND Com_Fec BETWEEN '$ini' AND '$fin' ";
                } */
                //Ver los comprobantes
                if ($Par_Sql['op_comp'] == 'r' || $Par_Sql['op_comp'] == 'n' || $Par_Sql['op_comp'] == 'c') {
                    $ini = $Par_Sql['Asi_Ini'] . ' 00:00:00';
                    $fin = $Par_Sql['Asi_Fin'] . ' 23:59:59';
                    $osql = $osql . " AND Com_Fec BETWEEN '$ini' AND '$fin' ";
                    if ($Par_Sql['op_comp'] == 'n') {
                        $osql = $osql . " AND Com_Est='I' ";
                    }
                }
                // nuevo 29-30/05/25 - filtro de cuenta contable
                if (!empty($Par_Sql['Pld_Cod_Compr'])) {
                    $search_pldc = " AND asientos.Pld_Cod=" . $Par_Sql['Pld_Cod_Compr'];
                }
            }

            if (!empty($Par_Sql['cops'])) {
                if (!empty($Par_Sql['Prv_Cod'])) {
                    $osql = $osql . "AND compras.Prv_Cod='$Par_Sql[Prv_Cod]'";
                }
                if (!empty($Par_Sql['Tic_Cod'])) {
                    $osql = $osql . "AND tipo_compr.Tic_Cod='$Par_Sql[Tic_Cod]'";
                }
                // if (!empty($Par_Sql['Tic_Cod'])) {
                //     $osql = $osql . "AND tipo_compr.Tic_Cod='$Par_Sql[Tic_Cod]'";
                // }
                // cambio a alias de tipo de comprobante
                if (!empty($Par_Sql['Tic_Cod'])) {
                    $osql = $osql . "AND tip_com_cop.Tic_Cod='$Par_Sql[Tic_Cod]'";
                }
                if (!empty($Par_Sql['Cop_Num'])) {
                    $osql = $osql . "AND Cop_Num='$Par_Sql[Cop_Num]'";
                }
                if (!empty($Par_Sql['chk_sr'])) {
                    $osql = $osql . "AND Cop_Fec BETWEEN '$Par_Sql[Cop_Ini]' AND '$Par_Sql[Cop_Fin]'";
                }

                if (!empty($Par_Sql['Pld_Cod_Com'])) {
                    $search_pldc = " AND asientos.Pld_Cod=" . $Par_Sql['Pld_Cod_Com'];
                }

                $osql = $osql . " AND compras.Cop_Cod IS NOT NULL ";
            }

            if (!empty($Par_Sql['vets'])) {
                if (!empty($Par_Sql['Cli_Cod'])) {
                    $osql = $osql . "AND ventas.Cli_Cod='$Par_Sql[Cli_Cod]'";
                }
                if (!empty($Par_Sql['Tic_Cod'])) {
                    $osql = $osql . "AND ventas.Tic_Cod='$Par_Sql[Tic_Cod]'";
                }
                if (!empty($Par_Sql['Vet_Num'])) {
                    $vet_num = $Par_Sql['Vet_Num'];
                    if (strlen($Par_Sql['Vet_Num']) > 9) {
                        $numero_venta = explode("-", $Par_Sql['Vet_Num']);
                        $vet_num = $numero_venta[2];
                    }
                    $osql = $osql . "AND Vet_Num='" . ltrim($vet_num, "0") . "'";
                }
                if (!empty($Par_Sql['chk_sr1'])) {
                    $osql = $osql . "AND Caj_Fec BETWEEN '$Par_Sql[Ven_Ini]' AND '$Par_Sql[Ven_Fin]'";
                }

                //Estos datos son nuevos
                if (!empty($Par_Sql['Pld_Cod'])) {
                    $search_pldc = " AND asientos.Pld_Cod=" . $Par_Sql['Pld_Cod'];
                }
                $osql = $osql . " AND ventas.Vet_Cod IS NOT NULL ";
            }

            /*  $sql = "SELECT $campos
                        FROM comprobantes
                        INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                        INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod
                        INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                        LEFT JOIN ventas_compr ON comprobantes.Com_Cod=ventas_compr.Com_Cod
                        LEFT JOIN ventas ON ventas.Vet_Cod=ventas_compr.Vet_Cod
                        LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                        LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                        LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                        LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                        LEFT JOIN compr_auto ON comprobantes.Com_Cod=compr_auto.Com_Cod
                        LEFT JOIN compras ON compras.Cop_Cod=compr_auto.Cop_Cod
                        LEFT JOIN tipo_compr AS tip_com_cop ON tip_com_cop.Tic_Cod=compras.Tic_Cod
                        LEFT JOIN tipo_compr AS tip_com_vet ON tip_com_vet.Tic_Cod=ventas.Tic_Cod
                        LEFT JOIN proveedore ON comprobantes.Prv_Cod=proveedore.Prv_Cod   
                        LEFT JOIN persona AS prs_prv ON prs_prv.Prs_Cod=proveedore.Prs_Cod 
                        LEFT JOIN cliente ON comprobantes.Cli_Cod=cliente.Cli_Cod
                        LEFT JOIN persona AS prs_cli ON prs_cli.Prs_Cod=cliente.Prs_Cod
                        RIGHT JOIN usuarios ON comprobantes.Usu_Cod = usuarios.Usu_Cod
                        RIGHT JOIN persona as resp ON resp.Prs_Cod = usuarios.Prs_Cod
                        -- Nuevos campos para filtrar los comprobantes que poseen una cuenta en especifico
                        LEFT JOIN asientos ON  asientos.Com_Cod = comprobantes.Com_Cod
                        LEFT JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod  
                        WHERE $estado  $search_pldc  AND plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod] $osql " . (empty($Par_Sql['order']) ? '' : " ORDER BY $Par_Sql[order] ") . " $Par_Sql[limits] ";
            */

            $sql = "SELECT $campos
            FROM comprobantes
            INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
            INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod
            INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
            LEFT JOIN ventas_compr ON comprobantes.Com_Cod=ventas_compr.Com_Cod
            LEFT JOIN ventas ON ventas.Vet_Cod=ventas_compr.Vet_Cod
            LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
            LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
            LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
            LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
            
            LEFT JOIN compr_auto ON comprobantes.Com_Cod=compr_auto.Com_Cod
            LEFT JOIN compras ON compras.Cop_Cod=compr_auto.Cop_Cod
            LEFT JOIN tipo_compr AS tip_com_cop ON tip_com_cop.Tic_Cod=compras.Tic_Cod
            LEFT JOIN tipo_compr AS tip_com_vet ON tip_com_vet.Tic_Cod=ventas.Tic_Cod
            LEFT JOIN proveedore ON comprobantes.Prv_Cod=proveedore.Prv_Cod   
            LEFT JOIN persona AS prs_prv ON prs_prv.Prs_Cod=proveedore.Prs_Cod 
            LEFT JOIN cliente ON comprobantes.Cli_Cod=cliente.Cli_Cod
            LEFT JOIN persona AS prs_cli ON prs_cli.Prs_Cod=cliente.Prs_Cod
            RIGHT JOIN usuarios ON comprobantes.Usu_Cod = usuarios.Usu_Cod
            RIGHT JOIN persona as resp ON resp.Prs_Cod = usuarios.Prs_Cod

            LEFT JOIN compr_arol ON compr_arol.Com_Cod = comprobantes.Com_Cod

            LEFT JOIN antici_rol ON antici_rol.Ant_Cod = compr_arol.Ant_Cod
            LEFT JOIN contratos_lab ON contratos_lab.Con_Cod = antici_rol.Con_Cod
            LEFT JOIN personal ON personal.Per_Cod = contratos_lab.Per_Cod
            LEFT JOIN persona AS prs_user ON prs_user.Prs_Cod = personal.Prs_Cod

            -- Nuevos campos para filtrar los comprobantes que poseen una cuenta en especifico
            LEFT JOIN asientos ON  asientos.Com_Cod = comprobantes.Com_Cod
            LEFT JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod  
            WHERE $estado  $search_pldc  AND plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod] $osql " . (empty($Par_Sql['order']) ? '' : " ORDER BY $Par_Sql[order] ") . " $Par_Sql[limits] ";
            //ChromePhp::log("case1",$sql);
            //echo $sql.'<br/>';
            break;
        case 2:
            $sql = "SELECT * FROM tipo_asien";
            //echo $sql.'<br/>';
            break;
        case 3:
            $sql = "SELECT comprobantes.*,YEAR(Pec_Fei)AS Periodo, Pec_Fei, Pec_Fef, CONCAT(Prs_Nom,' ',Prs_Ape) AS persona
            FROM comprobantes 
            INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod
            INNER JOIN usuarios ON comprobantes.Usu_Cod=usuarios.Usu_Cod
            INNER JOIN persona ON usuarios.Prs_Cod=persona.Prs_Cod
            WHERE Com_Cod='$Par_Sql[Com_Cod]'";
            //ChromePhp::log($sql);
            //echo $sql.'<br/>';
            break;
        case 4:
            $sql = "SELECT asientos.*,Asi_Deh AS Det_Tip,Pld_Cdc,Pld_Des,Asi_Glo AS Glosa,IF(Asi_Deh='D',Asi_Val,NULL)AS Debe,IF(Asi_Deh='H',Asi_Val,NULL)AS Haber FROM asientos
                 INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                 WHERE Com_Cod='$Par_Sql[Com_Cod]' ORDER BY Asi_Deh";
            //echo $sql.'<br/>';
            break;
        case 5:
            $sql = "SELECT Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom)AS Persona,CONCAT(CAST(Tic_Sri AS char),' - ',Tic_Des)AS Tic_Des,CONCAT(Tri_Sri,' - ',Tri_Des)AS Tri_Des,CONCAT(Tpc_Sri,' - ',Tpc_Des)AS Tpc_Des,compras.* FROM compras  
                INNER JOIN tipo_compr ON compras.Tic_Cod=tipo_compr.Tic_Cod
                INNER JOIN sustento ON compras.Tri_Cod=sustento.Tri_Cod
                LEFT JOIN tipopagocom ON compras.Tpc_Cod=tipopagocom.Tpc_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod
                INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod
                WHERE Cop_Cod='$Par_Sql[Cop_Cod]'";
            //echo $sql.'<br/>';
            break;
        case 6:
            $sql = "SELECT det_compra.Pro_Cod,Ite_Lar,Ite_Cor,Cop_Int AS Doc_Int,Cop_Can AS Doc_Can,Cop_Pru AS Doc_Pru,Cop_Imp AS Doc_Imp FROM det_compra 
                    INNER JOIN producto ON det_compra.Pro_Cod=producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    WHERE Cop_Cod='$Par_Sql[Cop_Cod]'";
            //echo $sql.'<br/>';
            break;
        case 7:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "det_plan.Pld_Des LIKE '%$Par_Sql[search]%'";
            } else {
                $search = "det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";
            }
            if ($Par_Sql['limits'] == "") {
                $campos = "COUNT(det_plan.Pld_Cod) as total";
            } else {
                $Par_Sql['limits'] = "ORDER BY det_plan.Pld_Cod " . $Par_Sql['limits'];
                $campos = "det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                        IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                        IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
            }
            $sql = "SELECT $campos
                        FROM det_plan 
                        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                        INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                        INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
                        LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                        LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                        WHERE plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod] AND plan_cuenta.Pla_Est='A'  AND det_plan.Pld_Est='A'
                        AND $search AND Pec_Cod =$Par_Sql[Pec_Cod] 
                        AND det_plan.Pld_Tip = 'D' $Par_Sql[limits]";
            //echo $sql.'<br/>';
            break;
        case 8:
            $sql = "UPDATE comprobantes SET Com_Val='$Par_Sql[Com_Val]',Com_Con='$Par_Sql[Com_Con]',Com_Obs='$Par_Sql[Com_Obs]',Tia_Cod='$Par_Sql[Tia_Cod]',Com_Num='$Par_Sql[Com_Num]',Com_Fec='$Par_Sql[Com_Fec]' WHERE Com_Cod=$Par_Sql[Com_Cod] ";
            //echo $sql.'<br/>';
            //ChromePhp::log($sql);
            break;
        case 9:
            $sql = "UPDATE asientos SET Pld_Cod='$Par_Sql[Pld_Cod]' WHERE Asi_Cod=$Par_Sql[Asi_Cod] AND Com_Cod=$Par_Sql[Com_Cod] ";
            //echo $sql.'<br/>';
            //ChromePhp::log($sql);
            break;
        case 10:
            $sql = "UPDATE compras SET Cop_Obs='$Par_Sql[1]' WHERE Cop_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            //ChromePhp::log($sql);
            break;
        case 11:
            $sql = "SELECT Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom)AS Persona,Caj_Fec,CONCAT(CAST(Tic_Sri AS char),' - ',Tic_Des)AS Tic_Des,ventas.* FROM ventas  
                INNER JOIN tipo_compr ON ventas.Tic_Cod=tipo_compr.Tic_Cod  
                INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                /*LEFT JOIN tipopagocom ON compras.Tpc_Cod=tipopagocom.Tpc_Cod*/
                INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                WHERE Vet_Cod='$Par_Sql[Vet_Cod]'";
            //echo $sql.'<br/>';
            break;
        case 12:
            $sql = "SELECT ventas_det.Pro_Cod,Ite_Lar,Ite_Cor,Vet_Int AS Doc_Int,Vet_Can AS Doc_Can,Vet_Pru AS Doc_Pru,Vet_Imp AS Doc_Imp FROM ventas_det 
                    INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    WHERE Vet_Cod='$Par_Sql[Vet_Cod]'";
            //echo $sql.'<br/>';
            break;
        case 13:
            $sql = "UPDATE ventas SET Vet_Obs='$Par_Sql[1]' WHERE Vet_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 14:
            $sql = "SELECT Tic_Cod,Tic_Sri,Tic_Des 
                  FROM tipo_compr 
                  WHERE Tic_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 15:
            $sql = "SELECT Tri_Cod,CONCAT(Tri_Sri,' - ',Tri_Des) AS Tri_Des 
                  FROM sustento 
                  WHERE Tri_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 16:
            $sql = "SELECT Ciu_Cod,Ciu_Des 
                  FROM ciudad
                  WHERE Ciu_Est='A'";
            //echo $sql.'<br/>';
            break;
        /**
             * Lista todos los proveedores registrados en una empresa
             */
        case 17:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos = "Prv_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,Ciu_Des, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
            } else {
                $campos = "COUNT(Prv_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM proveedore,persona,ciudad WHERE $search $Par_Sql[extra] AND persona.Ciu_Cod=ciudad.Ciu_Cod AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            //echo $sql;
            break;
        /**
             * Lista todos los clientes registrados en la tabla ventas y que pertenescan a la empresa
             * OJO: puede que en el listado el nombre de los clientes se repita pero esto se debe a que los clientes han comprado m�s de una vez
             */
        case 18:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos = "cliente.Cli_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
            } else {
                $campos = "COUNT(cliente.Cli_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM cliente                  
                  INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                  WHERE $search AND Cli_Est='A' AND Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            //echo $sql;
            break;
        /**
             * Lista todos las personas registradas en la empresa         
             */
        case 19:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos = "persona.Prs_Cod,Prs_Ced,Cli_Cod,Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom)AS Persona,Prs_Dir,cliente.Emp_Cod,proveedore.Emp_Cod";
            } else {
                $campos = "COUNT(persona.Prs_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM persona
                    LEFT JOIN cliente ON (cliente.Prs_Cod=persona.Prs_Cod AND Cli_Est='A' AND cliente.Emp_Cod=$Par_Sql[Emp_Cod])
                    LEFT JOIN proveedore ON (proveedore.Prs_Cod=persona.Prs_Cod AND Prv_Est='A' AND proveedore.Emp_Cod=$Par_Sql[Emp_Cod])
                    WHERE $search AND (cliente.Cli_Cod IS NOT NULL OR proveedore.Prv_Cod IS NOT NULL) $Par_Sql[limits]";
            //echo $sql;
            break;
        case 20:
            $sql = "SELECT MIN(Pec_Fef)AS menor, MAX(Pec_Fei)AS mayor FROM perio_cont
                INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE Emp_Cod=$Par_Sql[0];";
            //echo $sql;
            break;
        case 21:
            $sql = "SELECT Che_Cod,Che_Num,Che_Fec,Che_Val,CONCAT(Pld_Des,' (',Ban_Cue,')')AS Pld_Des, CONCAT(Prs_Ape,' ',Prs_Nom)AS Beneficiario FROM cheques 
                INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod
                INNER JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
                INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
                INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod
                WHERE Com_Cod=$Par_Sql[0];";
            //echo $sql;
            break;
        case 22:
            $sql = "SELECT perio_cont.Pec_Cod, perio_cont.Pec_Fei, perio_cont.Pec_Fef, perio_cont.Pec_Est, Year(Pec_Fei) AS Periodo, perio_cont.Pla_Cod FROM plan_cuenta
                  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                  WHERE Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0] ORDER BY Pec_Fei DESC";
            //echo $sql;
            break;
        case 23:
            if (empty($Par_Sql['Com_Cod']))
                $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[Pec_Cod], Prv_Cod=" . (empty($Par_Sql['Prv_Cod']) ? 'NULL' : $Par_Sql['Prv_Cod']) . ", Cli_Cod=" . (empty($Par_Sql['Cli_Cod']) ? 'NULL' : $Par_Sql['Cli_Cod']) . ", Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con=UPPER('$Par_Sql[Com_Con]'), Tia_Cod='$Par_Sql[Tia_Cod]', Com_Val=$Par_Sql[Com_Val], Com_Obs=UPPER('$Par_Sql[Com_Obs]'), Com_Gen='$Par_Sql[Com_Gen]', Com_Tip='$Par_Sql[Com_Tip]', Usu_Cod='$_SESSION[Ses_Usu_Cod]'"; //Antes Com_Tip
            else
                $sql = "UPDATE comprobantes SET Pec_Cod=$Par_Sql[Pec_Cod], Prv_Cod=" . (empty($Par_Sql['Prv_Cod']) ? 'NULL' : $Par_Sql['Prv_Cod']) . ", Cli_Cod=" . (empty($Par_Sql['Cli_Cod']) ? 'NULL' : $Par_Sql['Cli_Cod']) . ", Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con=UPPER('$Par_Sql[Com_Con]'), Tia_Cod='$Par_Sql[Tia_Cod]', Com_Val=$Par_Sql[Com_Val], Com_Obs=UPPER('$Par_Sql[Com_Obs]'), Com_Gen='$Par_Sql[Com_Gen]', Com_Tip='$Par_Sql[Com_Tip]', Usu_Cod='$_SESSION[Ses_Usu_Cod]' WHERE Com_Cod=$Par_Sql[Com_Cod] ";
            //ChromePhp::log($sql);
            //echo $sql."<br>";           
            break;
        case 2333:
            if (empty($Par_Sql['Com_Cod']))
                $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[Pec_Cod], Prv_Cod=" . (empty($Par_Sql['Prv_Cod']) ? 'NULL' : $Par_Sql['Prv_Cod']) . ", Cli_Cod=" . (empty($Par_Sql['Cli_Cod']) ? 'NULL' : $Par_Sql['Cli_Cod']) . ", Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con=UPPER('$Par_Sql[Com_Con]'), Tia_Cod='$Par_Sql[Tia_Cod]', Com_Val=$Par_Sql[Com_Val], Com_Obs=UPPER('$Par_Sql[Com_Obs]'), Com_Gen='$Par_Sql[Com_Gen]', Com_Tip='$Par_Sql[Com_Tip]',Com_Est='$Par_Sql[Com_Est]', Usu_Cod='$_SESSION[Ses_Usu_Cod]'"; //Antes Com_Tip
            else
                $sql = "UPDATE comprobantes SET Pec_Cod=$Par_Sql[Pec_Cod], Prv_Cod=" . (empty($Par_Sql['Prv_Cod']) ? 'NULL' : $Par_Sql['Prv_Cod']) . ", Cli_Cod=" . (empty($Par_Sql['Cli_Cod']) ? 'NULL' : $Par_Sql['Cli_Cod']) . ", Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con=UPPER('$Par_Sql[Com_Con]'), Tia_Cod='$Par_Sql[Tia_Cod]', Com_Val=$Par_Sql[Com_Val], Com_Obs=UPPER('$Par_Sql[Com_Obs]'), Com_Gen='$Par_Sql[Com_Gen]', Com_Tip='$Par_Sql[Com_Tip]', Usu_Cod='$_SESSION[Ses_Usu_Cod]' WHERE Com_Cod=$Par_Sql[Com_Cod] ";
            //ChromePhp::log($sql);
            //echo $sql."<br>";           
            break;
        case 24:
            /* inserta asiento */
            $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[Com_Cod], Asi_Deh='$Par_Sql[Asi_Deh]', Asi_Val=$Par_Sql[Asi_Val], Asi_Con=UPPER('$Par_Sql[Asi_Con]'), Asi_Glo=UPPER('$Par_Sql[Asi_Glo]'), Pld_Cod=$Par_Sql[Pld_Cod];";
            //ChromePhp::log($sql);
            break;
        case 25:
            /* inserta asiento */
            $sql = "DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
            //ChromePhp::log($sql);
            //echo $sql."<br>";
            break;
        case 26:
            /*Desactivar los Comprobantes*/
            $sql = "UPDATE comprobantes SET Com_Est='I' WHERE Com_Cod=$Par_Sql[Com_Cod];";
            break;
        case 27:
            $sql = "SELECT perio_cont.Pec_Cod, perio_cont.Pla_Cod as codigo2 from  perio_cont 
                  INNER JOIN plan_cuenta ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod) and plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod]
                  WHERE YEAR(perio_cont.Pec_Fei)=$Par_Sql[anio] AND perio_cont.Pla_Cod in (Select Pla_Cod from plan_cuenta where Pla_Cod=perio_cont.Pla_Cod);";
            //echo $sql."<br>";
            break;
        case 28:
            /*Desactivar los Comprobantes*/
            $sql = "UPDATE comprobantes SET Com_Est='A' WHERE Com_Cod=$Par_Sql[Com_Cod];";
            break;

        case 29:
            /*Desactivar los Comprobantes*/
            $sql = "SELECT Pld_Des FROM det_plan WHERE Pld_Cod=$Par_Sql[Pld_Cod];";
            break;


        case 30:
            $sql = "SELECT det_plan.* from det_plan
                        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod= det_plan.Pla_Cod
                        WHERE Pld_Est='A' AND Emp_Cod= $Par_Sql[0]" . " $Par_Sql[1]";

            return $sql;
        case 33:
            $sql = "UPDATE det_compra SET Pld_Cod='$Par_Sql[Pld_Cod]' WHERE Asi_Cod='$Par_Sql[Asi_Cod]' AND Cop_Cod=$Par_Sql[Cop_Cod] ";
            break;


         case 38:

            $sql = "SELECT  retencion.Aut_Cod, renta_iva.Ren_Sri,  Pld_Des, 
                SUM(det_retenc.Ret_Bas) AS Ret_Bas, 
                ROUND(( (renta_iva.Ren_Por/100) * SUM(det_retenc.Ret_Bas) ), 4) AS Debe,  NULL AS Haber,  'D' AS Det_Tip,
                 renta_iva.Ren_Cod,  renta_iva.Ren_Ret  , det_plan.Pld_Cdc  , det_plan.Pld_Cod  
                FROM retencion
                    INNER JOIN compras ON compras.Cop_Cod = retencion.Cop_Cod
                    INNER JOIN proveedore ON proveedore.Prv_Cod = compras.Prv_Cod
                    INNER JOIN det_retenc ON retencion.Ret_Cod = det_retenc.Ret_Cod
                    left JOIN renta_iva  ON det_retenc.Ren_Cod = renta_iva.Ren_Cod
                    INNER JOIN reniva_pla ON reniva_pla.Ren_Cod = renta_iva.Ren_Cod
                    INNER JOIN det_plan ON det_plan.Pld_Cod = reniva_pla.Pld_Cod                      
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_Cod
                WHERE 
               -- retencion.Ret_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
               compras.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
                AND proveedore.Emp_Cod = '$Par_Sql[0]' AND renta_iva.Ren_Est = 'A' AND retencion.Ret_Est='A'
                AND plan_cuenta.Emp_Cod = '$Par_Sql[0]' AND reniva_pla.Ren_Tip='C'
                AND renta_iva.Ren_Ret = 'R'
                GROUP BY  renta_iva.Ren_Sri, renta_iva.Ren_Cod, renta_iva.Ren_Ret
            UNION ALL

            SELECT  
                renta_iva.Ren_Ret, NULL,NULL, SUM(det_retenc.Ret_Bas) AS Ret_Bas,  NULL AS Haber, 
                ROUND(SUM( (renta_iva.Ren_Por/100) * det_retenc.Ret_Bas), 4) AS Debe, 'H' AS Det_Tip , NULL , NULL, NULL, NULL
            FROM retencion
                INNER JOIN compras ON compras.Cop_Cod = retencion.Cop_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod = compras.Prv_Cod
                INNER JOIN det_retenc ON retencion.Ret_Cod = det_retenc.Ret_Cod
                INNER JOIN renta_iva  ON det_retenc.Ren_Cod = renta_iva.Ren_Cod
                INNER JOIN reniva_pla ON reniva_pla.Ren_Cod = renta_iva.Ren_Cod
                INNER JOIN det_plan ON det_plan.Pld_Cod = reniva_pla.Pld_Cod                      
                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_Cod
            WHERE 
                -- retencion.Ret_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
                compras.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
                AND proveedore.Emp_Cod = '$Par_Sql[0]'  
                AND renta_iva.Ren_Est = 'A' 
                AND retencion.Ret_Est = 'A'
                AND plan_cuenta.Emp_Cod = '$Par_Sql[0]' 
                AND reniva_pla.Ren_Tip='C'
                AND renta_iva.Ren_Ret = 'R'
            GROUP BY renta_iva.Ren_Ret;";
            ChromePhp::log($sql);
            break;
        case 39:
            $sql = " SELECT det_plan.* from det_plan
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_Cod
                    INNER JOIN plan_param ON det_plan.Pld_Cod = plan_param.Pld_Cod
                    INNER JOIN tipo_param ON plan_param.Tpa_Cod = tipo_param.Tpa_Cod 
                    WHERE Tpa_Abr = 'LQR' AND plan_cuenta.Emp_Cod = '$Par_Sql[0]'";
            break;

        case 40:
            $sql = " SELECT det_plan.*,Tpa_Abr from det_plan
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_Cod
                    INNER JOIN plan_param ON det_plan.Pld_Cod = plan_param.Pld_Cod
                    INNER JOIN tipo_param ON plan_param.Tpa_Cod = tipo_param.Tpa_Cod 
                    WHERE Tpa_Abr in ($Par_Sql[Tpa_Abr]) AND plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";            
            //echo $sql;
            break;
        case 41:
            $sql = " SELECT det_plan.* from det_plan
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_Cod
                    INNER JOIN iva_cobrad ON det_plan.Pld_Cod = iva_cobrad.Pld_Cod                    
                    WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            break;
          /*Retencion de IVA en ventas */  
        case 42:
            $sql = "SELECT comprobantes.Com_Cod,det_plan.Pld_Cod, Pld_Cdc,Pld_Des, SUM(Asi_Val)as Haber, null as Debe,'H' as Det_Tip
                    FROM comprobantes 
                        INNER JOIN asientos ON comprobantes.Com_Cod = asientos.Com_Cod
                        INNER JOIN cliente ON comprobantes.Cli_Cod = cliente.Cli_Cod
                        INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod                        
                    WHERE cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' and Com_Fec between '$Par_Sql[ini]' and '$Par_Sql[fin]' and Com_Est='A' and det_plan.Pld_Cod in ($Par_Sql[Pld_Cods])
                    group by det_plan.Pld_Cod";
             //echo $sql;
             break;    
        /** Retencion de IVA en compras */
        case 43:
            $sql = "SELECT comprobantes.Com_Cod,det_plan.Pld_Cod, Pld_Cdc, Pld_Des, SUM(Asi_Val)as Debe, null as Haber, 'D' as Det_Tip
                from  comprobantes
                INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                INNER JOIN reniva_pla ON (det_plan.Pld_Cod = reniva_pla.Pld_Cod)
                INNER JOIN renta_iva ON (reniva_pla.Ren_Cod = renta_iva.Ren_Cod)
                INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                where plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' and Ren_Ret = 'I' and Ren_Est='A' and Com_Est='A' and reniva_pla.Ren_Tip = 'C' and 
                Com_Fec between '$Par_Sql[ini]' and '$Par_Sql[fin]' group by det_plan.Pld_Cod";
            break;
            //echo $sql;
    }
    //echo $sql."<br/>";
    return $sql;
}
