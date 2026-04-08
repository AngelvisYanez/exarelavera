<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2016-06-28
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
            if ($Par_Sql[3] == '') $campos = " COUNT(item.Ite_Cod) AS total ";
            else $campos = " item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,Adq_Cor,adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,iva.Iva_Sri,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,ice.Ice_Int,Ice_Por";
            if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
            else $search = " ( UPPER(item.Ite_Lar) LIKE UPPER('%$Par_Sql[0]%') OR UPPER(producto.Pro_Obs) LIKE UPPER('%$Par_Sql[0]%')  ) ";
            $sql = "SELECT 
                    $campos
                    FROM categorias
                        INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                        INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                        INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                        INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                        INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                        INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
                        INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod) 
                        LEFT JOIN ice ON ice.Ice_Int=producto.Ice_Int
                        INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod and stock.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                        INNER JOIN precios AS prec ON prec.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND prec.Pro_Cod=producto.Pro_Cod AND prec.Pre_Est='A'
                    WHERE $search AND Pro_Est='A' AND
                        categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql;
            break;
        case 2: //Busqueda de Proveedores
            if ($Par_Sql[2] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[3] == "") {
                $campos = "COUNT(Prv_Cod) as total";
            } else {
                $Par_Sql[3] = "ORDER BY Prs_Ape " . $Par_Sql[3];
                $campos = " Prv_Cod, persona.Prs_Cod, Prs_Ced, Ide_Prc as op_ide,persona.Ide_Cod, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax, Prs_Dir, IF(Prv_Cor IS NULL, Prs_Cor, Prv_Cor)AS Prs_Cor, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est, Prv_Con, Prv_Esp,Prv_Reg,Prv_Ris, Prv_Gct,Prv_Rim_Emp,Prv_Rim_Np,Prv_Ag_Ret ";
            }
            $sql = "SELECT $campos FROM persona 
            INNER JOIN proveedore ON (persona.Prs_Cod = proveedore.Prs_Cod)
            INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
            WHERE Prs_Ced!='0' AND $search AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql;
            break;
        case 3:
            $sql = "SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A' AND Tic_Sri='$Par_Sql[0]'";
            //echo $sql;
            break;
        case 4:
            $sql = "SELECT sustento.Tri_Sri, sustento.Tri_Cod, sustento.Tri_Des, sustento.Tri_Est FROM sustento WHERE sustento.Tri_Est='A'";
            //echo $sql;
            break;
        case 5:
            $sql = "SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est
                                    FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
            //echo $sql;
            break;
        case 6:
            $sql = "SELECT Ciu_Cod, Ciu_Des, Pro_Nom  FROM ciudad INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod WHERE Ciu_Des != ''  ORDER BY Ciu_Des ASC";
            //echo $sql;
            break;
        case 7:
            $sql = "SELECT compras.Cop_Cod, Cop_Sec, Prv_Cod, Tic_Cod, Cop_Num, Cop_Fec, Cop_Aut, Cpp_Cod, compr_auto.Com_Cod FROM compras LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod  LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod=compras.Cop_Cod WHERE Cop_Est='A' AND Cop_Num='$Par_Sql[2]' AND Tic_Cod='$Par_Sql[1]' AND Prv_Cod='$Par_Sql[0]' " . (!empty($Par_Sql[3]) ? "AND compras.Cop_Cod<>$Par_Sql[3]" : '');
            //echo $sql;
            break;
        case 8:
            $sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 9:
            $sql = "SELECT perio_cont.* FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
            //echo $sql;
            break;
        case 10:
            $sql = "SELECT vendedor.*,puntos_imp.*,CONCAT(Prs_Ape,' ',Prs_Nom)as nombre FROM vendedor
                INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                INNER JOIN persona ON (vendedor.Prs_Cod=persona.Prs_Cod)
                " . (isset($Par_Sql[2]) && !empty($Par_Sql[2]) ? " LEFT JOIN autorizaci ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod AND Aut_Est='A' AND Tic_Cod=$Par_Sql[2]" : '') . "
                WHERE Suc_Cod=$Par_Sql[0] AND vendedor.Prs_Cod=$Par_Sql[1] ";
            //echo $sql."<br>";
            break;
        case 11:
            if (empty($Par_Sql[26])) {
                $sql = "INSERT INTO compras (Tic_Cod, Prv_Cod, Ciu_Cod, Cop_Num, Cop_Aut, Cop_Fec, Cop_Reg, 
                Cop_Obs, Cop_Cad, Cop_Imf, Tri_Cod, Cop_Des, Pec_Cod,Tpc_Cod,Cop_Ntd,Cop_Nns,Cop_Nna,Vnd_Cod,
                Cop_Sec,Con_Cod,Cop_Irb,Aut_Cod,Cop_iva_pres,Cop_imp_comb, Cop_Prop, Cop_Adic) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], 
                '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]',
                '$Par_Sql[9]','$Par_Sql[10]','$Par_Sql[11]', $Par_Sql[12]," . (!empty($Par_Sql[13]) ? $Par_Sql[13] : 'NULL') . ",
                '$Par_Sql[14]', '$Par_Sql[15]', '$Par_Sql[16]','$Par_Sql[17]','$Par_Sql[18]',
                " . (empty($Par_Sql[19]) ? 'NULL' : $Par_Sql[19]) . "," . (empty($Par_Sql[20]) ? 'NULL' : $Par_Sql[20]) . ",
                " . (empty($Par_Sql[21]) ? 'NULL' : $Par_Sql[21]) . "," . (empty($Par_Sql[22]) ? 'NULL' : $Par_Sql[22]) . ",
                " . (empty($Par_Sql[23]) ? 'NULL' : $Par_Sql[23]) . "," . (empty($Par_Sql[24]) ? 'NULL' : $Par_Sql[24]) . ",
                " . (empty($Par_Sql[25]) ? 'NULL' : $Par_Sql[25]) . ")"; // Cop_Prop, Cop_Adic
            } else {
                $Par_Sql[21] = ($Par_Sql[21] == 'E') ? 'NULL' : $Par_Sql[21];
                $sql = "UPDATE compras SET Tic_Cod=$Par_Sql[0], Prv_Cod=$Par_Sql[1], 
                Ciu_Cod=$Par_Sql[2], Cop_Num='$Par_Sql[3]', Cop_Aut='$Par_Sql[4]', 
                Cop_Fec='$Par_Sql[5]', Cop_Reg='$Par_Sql[6]', Cop_Obs='$Par_Sql[7]', 
                Cop_Cad='$Par_Sql[8]', Cop_Imf='$Par_Sql[9]', Tri_Cod='$Par_Sql[10]', 
                Cop_Des='$Par_Sql[11]', Pec_Cod=$Par_Sql[12], 
                Tpc_Cod=" . (!empty($Par_Sql[13]) ? $Par_Sql[13] : 'NULL') . ",
                Cop_Ntd='$Par_Sql[14]',Cop_Nns='$Par_Sql[15]',
                Cop_Nna='$Par_Sql[16]',Vnd_Cod='$Par_Sql[17]',
                Cop_Sec='$Par_Sql[18]', 
                Con_Cod=" . (empty($Par_Sql[19]) ? 'NULL' : $Par_Sql[19]) . ",
                Cop_Irb=" . (empty($Par_Sql[20]) ? 'NULL' : $Par_Sql[20]) . ", 
                Aut_Cod=" . (!empty($Par_Sql[21]) ? $Par_Sql[21]  : 'NULL') . ",
                Cop_iva_pres=" .  (empty($Par_Sql[22]) ? 'NULL' : $Par_Sql[22]) . ",  
                Cop_imp_comb=" .  (empty($Par_Sql[23]) ? 'NULL' : $Par_Sql[23])  . ",
                Cop_Prop=" . (empty($Par_Sql[24]) ? 'NULL' : $Par_Sql[24]) . ",
                Cop_Adic=" . (empty($Par_Sql[25]) ? 'NULL' : $Par_Sql[25]) . "
                WHERE Cop_Cod=" . $Par_Sql[26];
            }
            //echo $sql."<br>";
            // //ChromePhp::log($sql);
            break;
        case 12:
            $asi_cod_sql = (isset($Par_Sql['Asi_Cod']) && $Par_Sql['Asi_Cod'] !== '' && $Par_Sql['Asi_Cod'] !== null) ? $Par_Sql['Asi_Cod'] : 'NULL';
            $sql = "INSERT INTO det_compra (Cop_Cod, Cop_Can, Iva_Cod, Cop_Pro, Cop_Pru, Cop_Imp, Cop_Dec, Adq_Cod, Ice_Int, Cop_Int, Pld_Cod,Pro_Cod,Iva_Cos,Cop_Ice,Ret_Ren_Sri,Asi_Cod) 
            VALUES($Par_Sql[Cop_Cod],$Par_Sql[Cop_Can],$Par_Sql[Iva_Cod],UPPER('" . (empty($Par_Sql['Cop_Pro']) ? (empty($Par_Sql['Ite_Lar']) ? '' : $Par_Sql['Ite_Lar']) : $Par_Sql['Cop_Pro']) . "'),$Par_Sql[Cop_Pru],$Par_Sql[Cop_Imp]," . (!empty($Par_Sql['Cop_Dec']) ? $Par_Sql['Cop_Dec'] : 0) . ",$Par_Sql[Adq_Cod]," . (!empty($Par_Sql['Ice_Int']) ? $Par_Sql['Ice_Int'] : 'NULL') . ", '$Par_Sql[Cop_Int]'," . (!empty($Par_Sql['Pld_Cod']) ? $Par_Sql['Pld_Cod'] : 'NULL') . ",$Par_Sql[Pro_Cod],'" . (isset($Par_Sql['Iva_Cos']) && $Par_Sql['Iva_Cos'] == 'S' ? 'S' : 'N') . "'," . (empty($Par_Sql['Cop_Ice']) ? 'NULL' : $Par_Sql['Cop_Ice']) . ",'$Par_Sql[Ret_Ren_Sri]',$asi_cod_sql)";
            // //ChromePhp::log("CASE12: ",$sql);
            //echo $sql."<br>";           
            break;
        case 13:
            $sql = "SELECT tipo_asien.* FROM form_compr INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=form_compr.Tia_Cod WHERE For_Cod = $Par_Sql[0]";
            //echo $sql."<br>";
            break;
        case 133:
            $sql = "SELECT * FROM tipo_asien where Tia_Cod=$Par_Sql[0]";
            break;
        case 14:
            if (empty($Par_Sql[9]))
                $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'"; //Antes Com_Tip
            else
                $sql = "UPDATE comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' WHERE Com_Cod=$Par_Sql[9] ";
            break;
        case 15:
            /* Relaciona una compra y un comprobante para saber que es automatico */
            $sql = "INSERT INTO compr_auto (Com_Cod, Cop_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
            //echo $sql."<br>";
            break;
        case 16:
            /* busca cuenta relacion producto */
            $sql = "SELECT Pro_Cod,produ_plan.Pld_Cod,Tip_Pld,Pld_Cdc,Pld_Des,Pla_Cod FROM produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pro_Cod=$Par_Sql[1] AND (Tip_Pld='$Par_Sql[2]' OR Tip_Pld='I') AND Pla_Cod=$Par_Sql[0]";
            //echo $sql."<br>";
            break;
        case 17:
            /* inserta asiento */
            $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5];";


            break;
        case 18:
            /* busca ivas */
            $sql = "SELECT DISTINCT * FROM iva WHERE Iva_Por!=0 AND Iva_Ini > '2001-07-01' GROUP BY Iva_Por ORDER BY Iva_Por DESC,Iva_Ini DESC ";
            //echo $sql."<br>";
            break;
        case 19:
            /* selecciona ivas */
            $sql = "SELECT * FROM iva WHERE Iva_Por>0 AND ('$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin OR (DATE('$Par_Sql[0]')>=Iva_Ini AND Iva_Fin IS NULL) ) ORDER BY Iva_Por DESC"; //compras.Cop_Fec,
            //echo $sql."<br>";
            break;
        case 20:
            /* selecciona cuentas iva */
            $sql = "SELECT iva_pagado.Pld_Cod,CONCAT(Pld_Des,' (',Pld_Cdc,')') AS Pld_Des FROM iva_pagado 
                    INNER JOIN det_plan ON det_plan.Pld_Cod=iva_pagado.Pld_Cod
                    WHERE Pla_Cod='$Par_Sql[0]'";
            //echo $sql."<br>";
            break;
        case 21:
            /* formas de pago */
            $sql = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Est = 'A' ORDER BY For_Des ASC";
            //echo $sql."<br>";
            break;
        case 22:
            /* cuentas contado */
            $sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des 
                    FROM banco, det_plan, pago_plan, plan_cuenta
			        WHERE banco.Pld_Cod = det_plan.Pld_Cod AND
                            Ban_Est = 'A' AND 
                            banco.Ban_Cod = pago_plan.Ban_Cod AND 
                            det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND 
                            pago_plan.Pag_Cod = $Par_Sql[1] AND 
                            (Ban_Tip='C' OR Ban_Tip='O') AND 
                            plan_cuenta.Pla_Cod = $Par_Sql[0] 
                    ORDER BY Pld_Cdc, Pld_Des";
            //echo $sql."<br>";
            break;
        case 23:
            /* cuentas credito */
            $sql = "SELECT ccpp_prove.Pld_Cod, det_plan.Pld_Des, ccpp_prove.Ccp_Def, ccpp_prove.Ccp_Cxp, Ccp_Def AS extra FROM det_plan INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
            //echo $sql."<br>";
            break;
        case 24:
            /* cuentas credito */
            $sql = "SELECT DISTINCT det_plan.* FROM ccpp_prove
                    INNER JOIN asientos ON asientos.Pld_Cod=ccpp_prove.Pld_Cod
                    INNER JOIN det_plan ON det_plan.Pld_Cod=ccpp_prove.Pld_Cod
                    WHERE Com_Cod=$Par_Sql[0] AND Asi_Deh='H' ";
            //echo $sql."<br>";
            break;
        case 25:
            /* cuentas credito */
            if (empty($Par_Sql[7]))
                $sql = "INSERT INTO det_ccpp_p(Cpp_Cod,Pag_Cod,Com_Cod,Pag_Fec,Pag_Val,Pag_Est,Pag_Obs,Asi_Cod) VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]','$Par_Sql[4]','A','$Par_Sql[5]','$Par_Sql[6]')";
            else
                $sql = "UPDATE det_ccpp_p SET Pag_Cod=$Par_Sql[1],Com_Cod=$Par_Sql[2],Pag_Fec='$Par_Sql[3]',Pag_Val='$Par_Sql[4]',Pag_Est='A',Pag_Obs='$Par_Sql[5]' WHERE Com_Cod=$Par_Sql[6] AND Cpp_Cod=$Par_Sql[0]";
            //echo $sql."<br>";
            break;

        case 255:
            $sql = "INSERT INTO det_ccpp_p (Com_Cod,Pag_Cod,Pag_Fec,Pag_Val,Pag_Obs,Cpp_Cod,Asi_Cod)
                values ($Par_Sql[Com_Cod],$Par_Sql[Pag_Cod],'$Par_Sql[Pag_Fec]',$Par_Sql[Pag_Val],'$Par_Sql[Pag_Obs]',$Par_Sql[Cpp_Cod],$Par_Sql[Asi_Cod])";
            break;

        case 26:
            /* detalle compra */
            $sql = "SELECT det_compra.*,Ite_Lar FROM det_compra
                    INNER JOIN producto ON producto.Pro_Cod=det_compra.Pro_Cod
                    INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                    WHERE Cop_Cod=$Par_Sql[0] ORDER BY Cop_Int";
            //echo $sql."<br>";
            break;
        case 27:
            /* detalle asiento */
            $sql = "SELECT Asi_Cod,Asi_Deh,Pld_Cdc,Pld_Des,Asi_Glo as Glosa,Asi_Val,IF(Asi_Deh='D',Asi_Val,'') AS Debe,IF(Asi_Deh='H',Asi_Val,'') AS Haber FROM asientos INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod WHERE Com_Cod='$Par_Sql[0]' ORDER BY Asi_Deh";
            //echo $sql."<br>";
            break;
        case 28:
            /* cuenta descuentos */
            $sql = "SELECT plan_param.Pld_Cod,Pld_Cdc,Pld_Des,Pla_Cod FROM plan_param
                    INNER JOIN det_plan ON det_plan.Pld_Cod=plan_param.Pld_Cod
                    INNER JOIN tipo_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    WHERE Tpa_Abr='$Par_Sql[1]' AND Pla_Cod=$Par_Sql[0];";
            //echo $sql."<br>";
            break;
        case 29:
            /* identificacion */
            $sql = "SELECT * FROM identifica WHERE Ide_Prc IS NOT NULL AND Ide_Prc<>'';";
            //echo $sql."<br>";
            break;

        case 30: //Busqueda de Proveedores
            $sql = "SELECT Emp_Cod,IF(Prv_Cor IS NULL, Prs_Cor, Prv_Cor)AS Prs_Cor,persona.Prs_Cod,Prs_Ape,Prs_Dir,Prs_Tel,Prs_Nom,Prs_Ced,proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,persona.Ide_Cod,persona.Ciu_Cod FROM persona  
                    LEFT JOIN proveedore ON proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]
                    WHERE Prs_Ced LIKE '$Par_Sql[0]%'  LIMIT 10;";
            //echo $sql;
            break;
        case 31:
            $sql = "INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','" . (isset($Par_Sql['Prs_Sex']) ? $Par_Sql['Prs_Sex'] : '') . "',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod]);";
            //echo $sql.'<br/>';
            break;
        case 32:
            // $sql = "INSERT INTO proveedore(Prs_Cod,Prv_Tic,Prv_Com,Prv_Con,Prv_Esp,Emp_Cod,Prv_Cor,Prv_Tel,Prv_Reg,Prv_Ris) VALUES($Par_Sql[Prs_Cod],'$Par_Sql[Prv_Tic]','$Par_Sql[Prv_Com]','$Par_Sql[Prv_Con]','$Par_Sql[Prv_Esp]',$Par_Sql[Emp_Cod],'$Par_Sql[Prv_Cor]','$Par_Sql[Prv_Tel]','$Par_Sql[Prv_Reg]','$Par_Sql[Prv_Ris]');";
            $sql = "INSERT INTO proveedore(Prs_Cod,Prv_Tic,Prv_Com,Prv_Con,Prv_Esp,Emp_Cod,Prv_Cor,Prv_Tel,Prv_Reg,Prv_Ris,Prv_Gct,Prv_Rim_Emp,Prv_Rim_Np,Prv_Ag_Ret) 
                    VALUES($Par_Sql[Prs_Cod],'$Par_Sql[Prv_Tic]'
                    ,'$Par_Sql[Prv_Com]','$Par_Sql[Prv_Con]'
                    ,'$Par_Sql[Prv_Esp]',$Par_Sql[Emp_Cod]
                    ,'$Par_Sql[Prv_Cor]'
                    ,'$Par_Sql[Prv_Tel]'
                    ,'$Par_Sql[Prv_Reg]'
                    ,'$Par_Sql[Prv_Ris]'
                    ,'$Par_Sql[Prv_Gct]'
                    ,'$Par_Sql[Prv_Rim_Emp]'
                    ,'$Par_Sql[Prv_Rim_Np]'
                    ,'$Par_Sql[Prv_Ag_Ret]'
                    );";
            //ChromePhp::log($sql);
            break;

        case 33:
            $sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND Pec_Est = 'A' AND plan_cuenta.Emp_Cod= $Par_Sql[0] ORDER BY Pec_Fei Desc";
            //echo $sql.'<br/>';
            break;
        case 34:
            if (empty($Par_Sql['limits'])) $campos = "COUNT(compras.Cop_Cod) AS total";
            else $campos = "compras.*,  compras.Aut_Cod AS aut_cod_sri,provee.Prs_Cod, IF(Prv_Cor IS NULL,provee.Prs_Cor,Prv_Cor)AS Prs_Cor, Tri_Sri,
                if(Cop_Ide='1',
                    if(LENGTH(provee.Prs_Ced)<13,concat(provee.Prs_Ced,'001'),provee.Prs_Ced),
                    if(Cop_Ide='2',
                        if(LENGTH(provee.Prs_Ced)>10,SUBSTRING(provee.Prs_Ced,1,10),provee.Prs_Ced),
                        provee.Prs_Ced  
                    )
                )as Prs_Ced,
                provee.Prs_Ape, provee.Prs_Nom, provee.Prs_Dir,Emp_Cod,proveedore.Prv_Cod, Prv_Esp, 
                Prv_Con, CONCAT(provee.Prs_Ape,' ',provee.Prs_Nom) as proveedor, tipo_compr.Tic_Des, comprobantes.Com_Cod, 
                IF(comprobantes.Com_Cod IS NULL,'N','S') AS Com_Exi,Com_Fec,Tpc_Sri,Tpc_Des,Cpp_Cod,
                IF(ccpp_pagar.Cpp_Cod IS NULL,'Contado','Credito') AS Pago,Cpp_Ven,Cpp_Obs, retencion.Ret_Cod, 
                IF(retencion.Ret_Cod IS NULL,'N','S') AS Ret_Exi,Ret_Cod,Ret_Asu,Ret_Aut,retencion.Aut_Cod,
                IF(Aut_Tem='E','Electronica',Aut_Sri)AS Aut_Sri, Aut_Sri AS Aut_Sri_Num, 
                Pun_Sri,Ret_Num,Aut_Tem,Aut_Fci,Aut_Cad,Ret_Fec,Ret_Xml,CONCAT(vended.Prs_Ape,' ',vended.Prs_Nom) as vendedor,
                IF(Con_Cod IS NULL,'',Con_Cod)AS Con_Cod,Com_Est,Ret_Est,Com_Est, Cod_Neg,
                IF(Com_Fec IS NOT NULL AND MONTH(Com_Fec)=MONTH(Cop_Fec),'S','N')AS Com_Mes,'N' AS Alerta, 
                IF(comprobantes.Com_Cod IS NULL,'',CAST(CONCAT(Tia_Abr,'-',LPAD(MONTH(Com_Fec),2,'0'),'-',Com_Num)AS CHAR))AS Com_Codigo, Cop_Irb ";
            $search_compra = "";
            if (!empty($Par_Sql['search_compras'])) { //En compras no se puede ver notas de credito
                if ($Par_Sql['search_compras'] == "C") {
                    $search_compra = " AND  tipo_compr.Tic_Cod!=4 ";
                }
            }
            $Par_Sql['Tic_Cod'] = (!empty($Par_Sql['Tic_Cod']) ? "AND compras.Tic_Cod=$Par_Sql[Tic_Cod]" : '');
            if ($Par_Sql['op_opciones'] == 'd') {
                $search = "AND compras.Cop_Num LIKE '$Par_Sql[search]%'";
                $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
            } else if ($Par_Sql['op_opciones'] == 'r') {
                $search = "AND retencion.Ret_Num='$Par_Sql[search]'";
                $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
            } else {
                $Par_Sql['Cmb_Mes'] = (!empty($Par_Sql['Pec_Cod']) && !empty($Par_Sql['Cmb_Mes']) ? "AND MONTH(compras.Cop_Fec)=$Par_Sql[Cmb_Mes]" : '');
                $Par_Sql['Pec_Cod'] = (!empty($Par_Sql['Pec_Cod']) ? "AND compras.Pec_Cod=$Par_Sql[Pec_Cod]" : '');
                if ($Par_Sql['op_opciones'] == 'c')
                    $search = "AND provee.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search = "AND (UPPER(CONCAT(provee.Prs_Ape,' ',provee.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }

            if ($Par_Sql['mis_ingresos'] == 'S') {
                $filtroUsuario = "AND vendedor.Prs_cod = $_SESSION[Ses_Prs_Cod]";
            } else {
                $filtroUsuario = '';
            }

            $sql = "SELECT $campos FROM compras
                        INNER JOIN proveedore ON compras.Prv_Cod=proveedore.Prv_Cod
                        INNER JOIN persona AS provee ON provee.Prs_Cod=proveedore.Prs_Cod
                        INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=compras.Tic_Cod
                        INNER JOIN vendedor ON vendedor.Vnd_Cod=compras.Vnd_Cod
                        INNER JOIN sustento ON compras.Tri_Cod=sustento.Tri_Cod
                        INNER JOIN persona AS vended ON vendedor.Prs_Cod=vended.Prs_Cod
                        LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod
                        LEFT JOIN comprobantes ON compr_auto.Com_Cod=comprobantes.Com_Cod
                        LEFT JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod                 
                        LEFT JOIN tipopagocom ON tipopagocom.Tpc_Cod=compras.Tpc_Cod 
                        LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod=compras.Cop_Cod 
                        LEFT JOIN retencion ON (retencion.Cop_Cod=compras.Cop_Cod AND Ret_Est='A')
                        LEFT JOIN autorizaci ON autorizaci.Aut_Cod=retencion.Aut_Cod
                        LEFT JOIN nego_documentos ON compras.Cop_Cod = nego_documentos.Cod_Doc
                        INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = vendedor.Pun_Cod AND puntos_imp.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                    WHERE  proveedore.Emp_Cod=$_SESSION[Ses_Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $search_compra $filtroUsuario $search
                $Par_Sql[limits] ;";
            //echo $sql.'<br/>';
            // //ChromePhp::log($sql);
            break;
        case 35:
            $sql = "SELECT Cop_Int,Cop_Int AS 'index',det_compra.Ret_Ren_Sri ,det_compra.Pro_Cod,det_compra.Ice_Int,det_compra.Cop_Ice,
            det_compra.Iva_Cod,Iva_Por,Iva_Sri,Cop_Pro AS Ite_Lar,Cop_Can,Cop_Pru,/*Cop_Imp,*/Cop_Dec,det_compra.Adq_Cod,Adq_Des,Adq_Cor,
            det_plan.Pld_Cod, Pld_Cdc,Pld_Des,Uni_Des,Iva_Cos,Pro_Bar, ROUND(IFNULL(Cop_Imp,0) - (Cop_Dec/100 * (Cop_Can*Cop_Pru)),2) AS Cop_Imp
            FROM det_compra 
                INNER JOIN producto ON producto.Pro_Cod=det_compra.Pro_Cod
                INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
                INNER JOIN iva ON iva.Iva_Cod=det_compra.Iva_Cod
                INNER JOIN adquisicio ON adquisicio.Adq_Cod=det_compra.Adq_Cod
                LEFT JOIN det_plan ON det_plan.Pld_Cod=det_compra.Pld_Cod 
                WHERE Cop_Cod=$Par_Sql[0] ORDER BY Cop_Int;";
            //echo $sql.'<br/>';
            break;
        case 36:
            $sql = "SELECT iva_pagado.Pld_Cod FROM asientos INNER JOIN iva_pagado ON asientos.Pld_Cod=iva_pagado.Pld_Cod WHERE Com_Cod= $Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 37:
            $sql = "SELECT ccpp_prove.Pld_Cod FROM asientos INNER JOIN ccpp_prove ON asientos.Pld_Cod=ccpp_prove.Pld_Cod WHERE Com_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 38:
            $sql = "SELECT COUNT(Cop_Cod)AS total  FROM kardex_ie WHERE Cop_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 39:
            $sql = "SELECT asientos.Pld_Cod FROM asientos INNER JOIN banco ON banco.Pld_Cod=asientos.Pld_Cod WHERE Com_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 40:
            $sql = "SELECT Cop_Fec,Cop_Sec, Com_Fec, Com_Num FROM compras LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod LEFT JOIN comprobantes ON comprobantes.Com_Cod=compr_auto.Com_Cod WHERE compras.Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 41:
            $sql = "DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 42:
            $sql = "DELETE FROM det_compra WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 43:
            $sql = "SELECT * FROM kardex_ie WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 44:
            $sql = "DELETE FROM kardex_ie WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 45:
            $sql = "SELECT Tpc_Cod,Tpc_Sri,Tpc_Des FROM tipopagocom WHERE Tpc_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 46:
            $sql = "DELETE FROM det_ccpp_p WHERE Com_Cod='$Par_Sql[0]' ";
            //echo $sql.'<br/>';
            break;
        case 47:
            if (empty($Par_Sql['limits'])) $campos = "COUNT(renta_iva.Ren_Cod) AS total";
            else $campos = "Adq_Cod,renta_iva.Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,renta_iva.Ren_Tip,if(renta_iva.Ren_Tip='B','BIENES','SERVICIO')as Ren_Tipo,Ren_Ret,if(Ren_Ret='R','RENTA','IVA')as Ren_Rete,Ren_Est,if(Ren_Est='A','Activo','Anulado')as Ren_Esta";
            if ($Par_Sql['op_opciones'] == 'd') $where = "(Ren_Con LIKE '$Par_Sql[search]%' OR Ren_Con LIKE '%$Par_Sql[search]%')";
            else if ($Par_Sql['op_opciones'] == 'c') $where = "Ren_Sri LIKE '$Par_Sql[search]%'";
            else {
                if (!empty($Par_Sql['search'])) $where = "Ren_Por = '$Par_Sql[search]'";
                else $where = "";
            }
            $sql = "SELECT $campos FROM renta_iva WHERE Ren_Est='A' AND Ren_Ret='$Par_Sql[tipo]'" . (!empty($where) ? "AND $where " : '') . (!empty($Par_Sql['limits']) ? " ORDER BY Ren_Sri ASC $Par_Sql[limits];" : ';');
            //echo $sql.'<br/>';
            break;
        case 48:
            $sql = "SELECT autorizaci.* FROM autorizaci 
			        WHERE autorizaci.Tic_Cod=$Par_Sql[1] " . (isset($Par_Sql[0]) && !empty($Par_Sql[0]) ? " AND autorizaci.Pun_Cod=$Par_Sql[0] " : '') . "" . (isset($Par_Sql[2]) && !empty($Par_Sql[2]) ? " AND autorizaci.Aut_Cod=$Par_Sql[2] " : " AND autorizaci.Aut_Est = 'A' ");
            //echo $sql.'<br/>';
            break;
        case 49:
            $sql = "SELECT confi_fact.Cof_NegCam, empresas.Ret_Scom,empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
                    sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
		    FROM empresas
                    INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                    INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                    WHERE sucursal.Suc_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 50:
            $sql = "SELECT COUNT(Ret_Cod)AS total FROM retencion 
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = retencion.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod 
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND Suc_Cod='$Par_Sql[0]' AND Pun_Sri='$Par_Sql[3]' AND Ret_Num='$Par_Sql[2]'" . (!empty($Par_Sql[4]) ? "AND retencion.Ret_Cod<>$Par_Sql[4]" : '') . ';';
            //echo $sql.'<br/>';
            //ChromePhp::log($sql);
            break;
        case 51:
            $sql = "SELECT 
                    CASE         
                        WHEN MAX(Ret_Num)IS NOT NULL AND MAX(Ret_Num)>=$Par_Sql[3] THEN ( 
                            SELECT MIN(t.Ret_Num)+1
                            FROM retencion t 
                            INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                            INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                            WHERE tp.Suc_Cod=$Par_Sql[0] AND ta.Aut_Sri='$Par_Sql[1]' AND ta.Tic_Cod=$Par_Sql[4] AND t.Ret_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3] AND
                            NOT EXISTS (
                                SELECT NULL FROM retencion n 
                                    INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                    INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                    WHERE n.Ret_Num=t.Ret_Num+1 AND np.Suc_Cod=$Par_Sql[0] AND na.Aut_Sri='$Par_Sql[1]' AND na.Tic_Cod=$Par_Sql[4] AND n.Ret_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3]
                                )
                            )            
                        ELSE IFNULL(MAX(Ret_Num),$Par_Sql[2]-1)+1
                        END AS 'next'
                FROM retencion
                INNER JOIN autorizaci ON retencion.Aut_Cod=autorizaci.Aut_Cod
                INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                WHERE Suc_Cod=$Par_Sql[0] AND autorizaci.Aut_Sri='$Par_Sql[1]' AND autorizaci.Tic_Cod=$Par_Sql[4] AND Ret_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3] AND autorizaci.Pun_Sri='$Par_Sql[5]'";
            //echo $sql.'<br/>';
            //ChromePhp::log($sql);
            break;
        case 52:
            $sql = "SELECT det_plan.Pld_Des, det_plan.Pld_Cdc, reniva_pla.Pld_Cod, reniva_pla.Ren_Cod FROM det_plan 
                INNER JOIN reniva_pla ON (det_plan.Pld_Cod = reniva_pla.Pld_Cod) 
                INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                WHERE reniva_pla.Ren_Cod='$Par_Sql[1]' AND reniva_pla.Ren_Tip='$Par_Sql[2]' AND plan_cuenta.Pla_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 53:
            if (empty($Par_Sql[11]))
                $sql = "INSERT INTO retencion(Cop_Cod, Ret_Num, Ret_Fec, Ret_Con, Tic_Cod, Vnd_Cod, Aut_Cod, Ret_Xml, Ret_Asu, Ret_Uca, Ret_Pca) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'), $Par_Sql[4], $Par_Sql[5], $Par_Sql[6],'$Par_Sql[7]','$Par_Sql[8]'," . (empty($Par_Sql[9]) ? 'NULL' : $Par_Sql[9]) . "," . (empty($Par_Sql[10]) ? 'NULL' : $Par_Sql[10]) . ")";
            else
                $sql = "UPDATE retencion SET Ret_Num='$Par_Sql[1]', Ret_Fec='$Par_Sql[2]', Ret_Con=UPPER('$Par_Sql[3]'), Tic_Cod=$Par_Sql[4], Vnd_Cod=$Par_Sql[5], Aut_Cod=$Par_Sql[6], Ret_Xml='$Par_Sql[7]', Ret_Asu='$Par_Sql[8]', Ret_Uca=" . (empty($Par_Sql[9]) ? 'NULL' : $Par_Sql[9]) . ", Ret_Pca=" . (empty($Par_Sql[10]) ? 'NULL' : $Par_Sql[10]) . " WHERE Cop_Cod=$Par_Sql[0] AND Ret_Cod=$Par_Sql[11];";
            //echo $sql.'<br/>';
            break;
        case 54:
            $sql = "INSERT INTO det_retenc(Ret_Cod,Ret_Bas, Ren_Cod, Ret_Imp, Ret_Int, Adq_Cod)
                    VALUES($Par_Sql[0],'$Par_Sql[1]',$Par_Sql[2],UPPER('$Par_Sql[3]'),'$Par_Sql[4]', $Par_Sql[5])";
            //echo $sql.'<br/>';
            break;
        case 55:
            if (empty($Par_Sql[4]))
                $sql = "INSERT INTO ccpp_pagar(Com_Cod, Cop_Cod, Cpp_Ven, Cpp_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'));";
            else
                $sql = "UPDATE ccpp_pagar SET Cpp_Ven='$Par_Sql[2]', Cpp_Obs=UPPER('$Par_Sql[3]') WHERE Cpp_Cod='$Par_Sql[4]' AND Cop_Cod='$Par_Sql[1]' AND Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 56:
            $sql = "SELECT   
                    sum(
                          ( 
                      (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */
                          +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0)) /* ICE */
                          )	*(1+iva.Iva_Por/100)	/* IVA */
                    ) AS total
                  FROM compras
                    INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                    INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
                    LEFT JOIN ice ON (ice.Ice_int=det_compra.Ice_Int)
                    INNER JOIN tipo_compr ON (tipo_compr.Tic_Cod = compras.Tic_Cod)
                  WHERE
                     compras.Prv_Cod = '$Par_Sql[0]' AND Tic_Sri='$Par_Sql[1]' 
                     AND Cop_Fec BETWEEN '$Par_Sql[2] 00:00:00' AND '$Par_Sql[3] 23:59:59'
                  GROUP BY compras.Prv_Cod";
            //echo $sql.'<br/>';
            break;
        case 57:
            $sql = "SELECT " . (!empty($Par_Sql[1]) ? "SUM(det_ccpp_p.Pag_Val)" : "COUNT(det_ccpp_p.Cpp_Cod)") . "AS total FROM det_ccpp_p INNER JOIN comprobantes ON det_ccpp_p.Com_Cod=comprobantes.Com_Cod WHERE Cpp_Cod='$Par_Sql[0]' " . (!empty($Par_Sql[1]) ? "AND Pag_Est='$Par_Sql[1]' AND Com_Est='A'" : "AND Com_Est='A'") . ";";
            //echo $sql.'<br/>';
            break;

        case 577:
            $sql = "SELECT SUM(det_ccpp_p.Pag_Val)AS total 
                    FROM det_ccpp_p 
                    INNER JOIN comprobantes ON det_ccpp_p.Com_Cod=comprobantes.Com_Cod
                    INNER JOIN tipos_pago ON det_ccpp_p.Pag_Cod = tipos_pago.Pag_Cod
                    WHERE Cpp_Cod='$Par_Sql[0]'
                    AND det_ccpp_p.Pag_Est='$Par_Sql[1]' AND Com_Est='A' and tipos_pago.Pag_Abr = 'RET'";
            //echo $sql.'<br/>';
            break;

        case 58:
            $sql = "SELECT COUNT(Cop_Cod)AS total FROM det_reposicion WHERE Cop_Cod='$Par_Sql[0]' AND " . (empty($Par_Sql[1]) ? "Dre_Tip!='P'" : "Dre_Tip='$Par_Sql[1]'");
            //echo $sql.'<br/>';
            break;
        case 59:
            $sql = "SELECT Ret_Int,det_retenc.Ren_Cod,Ren_Por,Ren_Con,Ren_Sri,Ret_Imp AS Ren_Ret FROM det_retenc INNER JOIN renta_iva ON renta_iva.Ren_Cod=det_retenc.Ren_Cod WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 60:
            $sql = "SELECT reniva_pla.Pld_Cod, Pld_Cdc, Pld_Des FROM reniva_pla INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod WHERE Ren_Cod='$Par_Sql[1]' AND det_plan.Pla_Cod='$Par_Sql[0]' AND Ren_Tip='$Par_Sql[2]'";
            //echo $sql.'<br/>';
            break;
        case 61:
            $sql = "SELECT autorizaci.Aut_Sri, autorizaci.Pun_Sri, sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin FROM puntos_imp INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod) INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod) WHERE autorizaci.Aut_Cod ='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 62:
            $sql = "DELETE FROM det_retenc WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 63:
            $sql = "DELETE FROM retencion WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 64:
            $sql = "DELETE FROM ccpp_pagar WHERE Cpp_Cod='$Par_Sql[2]' AND Cop_Cod='$Par_Sql[1]' AND Com_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 65:
            $sql = "SELECT COUNT(Cop_Cod)AS total FROM compr_auto WHERE Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 66:
            $sql = "SELECT modi.Tic_Cod AS Mod_Tic_Cod, modi.Tic_Des AS Mod_Tic_Des,modicomp.Cop_Fec AS Mod_Cop_Fec FROM compras AS modicomp 
                    INNER JOIN tipo_compr AS modi ON (modi.Tic_Cod=modicomp.Tic_Cod)
                    WHERE modicomp.Cop_Num='$Par_Sql[1]' AND modicomp.Prv_Cod='$Par_Sql[0]' AND modi.Tic_Sri='$Par_Sql[2]' AND modicomp.Cop_Est='A';";
            //echo $sql.'<br/>';
            break;
        case 67:
            $sql = "SELECT plan_param.Pld_Cod,Pld_Des,Pld_Est FROM plan_param 
                    INNER JOIN det_plan ON plan_param.Pld_Cod=det_plan.Pld_Cod 
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod 
                    INNER JOIN tipo_param ON plan_param.Tpa_Cod=tipo_param.Tpa_Cod 
                    WHERE Tpa_Abr='$Par_Sql[1]' AND Emp_Cod='$Par_Sql[0]' AND Pld_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 68:
            $sql = "SELECT * FROM iva WHERE Iva_Por='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 69:
            $sql = "INSERT INTO det_reposicion(Cop_Cod,Rep_Cod,Dre_Tip)VALUES($Par_Sql[0]," . (empty($Par_Sql[1]) ? '0' : $Par_Sql[1]) . ",'$Par_Sql[2]');";
            //echo $sql.'<br/>';
            break;
        case 70:
            $sql = "SELECT asientos.Pld_Cod FROM asientos 
                    INNER JOIN plan_param ON plan_param.Pld_Cod=asientos.Pld_Cod 
                    INNER JOIN tipo_param ON plan_param.Tpa_Cod=tipo_param.Tpa_Cod
                    WHERE Com_Cod=$Par_Sql[0] AND Tpa_Abr='$Par_Sql[1]'";
            //echo $sql.'<br/>';
            break;
        case 71:
            $sql = "DELETE FROM det_reposicion WHERE Cop_Cod=$Par_Sql[0] AND Rep_Cod=$Par_Sql[1] AND Dre_Tip='$Par_Sql[2]';";
            //echo $sql.'<br/>';
            break;
        case 72:
            $sql = "UPDATE compras SET Cop_Est='$Par_Sql[1]' WHERE Cop_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 73:
            $sql = "UPDATE retencion SET Ret_Est='$Par_Sql[1]' WHERE Ret_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 74:
            $sql = "UPDATE comprobantes SET Com_Est='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 75:
            $sql = "UPDATE cheques,asientos SET Che_Est='$Par_Sql[1]' WHERE cheques.Asi_Cod=asientos.Asi_Cod AND Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 76:
            $sql = "SELECT * FROM retencion 
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod=retencion.Aut_Cod					
					INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod
					INNER JOIN proveedore ON compras.Prv_Cod=proveedore.Prv_Cod
                    WHERE Ret_Num='$Par_Sql[0]' AND Aut_Sri='$Par_Sql[1]' AND Ret_Cod!='$Par_Sql[2]' AND Emp_Cod='$Par_Sql[3]' AND Pun_Sri='$Par_Sql[4]' ;";
            //echo $sql.'<br/>';
            //ChromePhp::log($sql);
            break;
        case 77:
            $sql = "SELECT * FROM consumo WHERE Emp_Cod='$Par_Sql[0]' AND Con_Est='$Par_Sql[1]';";
            //echo $sql.'<br/>';
            break;
        case 78:
            $sql = "SELECT consumo.Con_Cod,Con_Des,det_compra.*,Cop_Num,Cop_Fec,Cop_Des,Ite_Lar,Ite_Cor,Pro_Obs,Uni_Des,Adq_Des,Adq_Cor,Iva_Por FROM det_compra
                INNER JOIN iva ON iva.Iva_Cod=det_compra.Iva_Cod
                INNER JOIN producto ON producto.Pro_Cod=det_compra.Pro_Cod
                INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
                INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                INNER JOIN compras ON compras.Cop_Cod=det_compra.Cop_Cod
                INNER JOIN consumo ON consumo.Con_Cod=compras.Con_Cod
                WHERE Cop_Est='A' AND Con_Est='A' AND Emp_Cod='$Par_Sql[0]' " . (empty($Par_Sql[1]) ? '' : " AND consumo.Con_Cod='$Par_Sql[1]'") . " AND Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]';";
            //echo $sql.'<br/>';
            break;
        case 79:
            if ($Par_Sql[3] == "") $campos = "COUNT(autorizaci.Aut_Cod) as total";
            else {
                $campos = "autorizaci.* , IF(autorizaci.Aut_Est='A','S','N') as Aut_Estado,tipo_compr.*,Suc_Sri, '$Par_Sql[2]' AS Ret_Fec,IF('$Par_Sql[2]' BETWEEN Aut_Fci AND Aut_Cad,'C','')AS Cad";
            }
            $sql = "SELECT $campos FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE Tic_Est='A' AND autorizaci.Pun_Cod=$Par_Sql[0] and tipo_compr.Tic_Cod=$Par_Sql[1] $Par_Sql[3]";
            break;
        case 80:
            $sql = "UPDATE retencion SET Ret_Aut='S', Ret_Xml='$Par_Sql[1]', Ret_Sri='$Par_Sql[1]' WHERE Ret_Cod='$Par_Sql[0]';";
            break;


        case 81:
            $sql = "select bod_cod from bodega, sucursal where bodega.Suc_Cod=sucursal.Suc_Cod AND Emp_Cod = $_SESSION[Ses_Emp_Cod] and bod_tip='P'";
            return $sql;
            break;
        //CONSULTAS PARA GENERAR EL ANTICIPO CUANDO EL VALOR DE LA NOTA DE CREDITO SOBREPASA EL SALDO DEL DOCUMENTO

        case 97:
            $sql = "SELECT  
                  Round(IFNULL((select comprobantes.Com_Val from ccpp_pagar, comprobantes where ccpp_pagar.Cpp_Cod = $Par_Sql[0] and ccpp_pagar.Com_Cod = comprobantes.Com_Cod and comprobantes.Com_Est = 'A'),'0.00') - IFNULL(sum(det_ccpp_p.Pag_Val),'0.00'),2)
                  as Cop_Saldo
                  FROM ccpp_pagar, det_ccpp_p, comprobantes  
                  where ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod
                  and det_ccpp_p.Cpp_Cod = $Par_Sql[0]
                  and comprobantes.Com_Cod = det_ccpp_p.Com_Cod 
                  and comprobantes.Com_Est = 'A'";
            break;
        case 102:
            $sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
              FROM det_plan, tipo_param, plan_param
              WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
                        AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
                        AND tipo_param.Tpa_Abr='CDV'
                        AND det_plan.Pld_Est='A' AND
              det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
            break;

        case 103:
            $sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
              FROM det_plan, tipo_param, plan_param
              WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
                        AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
                        AND tipo_param.Tpa_Abr='ANP'
                        AND det_plan.Pld_Est='A' AND
              det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
            break;

        case 104:
            $sql = "SELECT * from tipo_asien where Tia_Abr= 'EG'";
            break;
        //sentencia para obtener el periodo contable de la fecha en la que se realiza la consulta 
        case 105:
            $sql = "SELECT Pla_Cod, Pec_Cod, Pec_Fei from perio_cont
                        where ('$Par_Sql[0]' BETWEEN Pec_Fei AND Pec_Fef) and
                        perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
            break;
        //insertar un registro en la tabla comprobantes
        case 106:
            $sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Est,Usu_Cod,Com_Gen)
                            VALUES($Par_Sql[Pec_Cod], $Par_Sql[Prv_Cod], null, '$Par_Sql[Com_Num]', '$Par_Sql[Com_Fec]', '$Par_Sql[Com_Con]', 'E', '$Par_Sql[Com_Val]',
                         'SIN OBSERVACIONES', null, $Par_Sql[Tia_Cod], 'A', '$_SESSION[Ses_Usu_Cod]','A');";
            break;
        //insertar un registro en la tabla anticipos_proveedores
        case 107:
            $sql = "INSERT INTO anticipos_proveedores (Atp_Fec, Atp_Val, Atp_Est, Atp_Obs, Com_Cod, Prv_Cod, Suc_Cod)
                            VALUES('$Par_Sql[Atp_Fec]', $Par_Sql[Atp_Val], 'A', '$Par_Sql[Atp_Obs]', $Par_Sql[Com_Cod], $Par_Sql[Prv_Cod], $_SESSION[Ses_Suc_Cod]);";
            break;
        //insertar un registro en la tabla pagos_anticipo_proveedores
        case 108:
            $sql = "INSERT INTO pago_anticipo_proveedores (Pap_Cto, Pap_Ctd, Pap_Val, Atp_Cod, Pag_Cod, Asi_Cod, Pap_Est)
                            VALUES('$Par_Sql[Pap_Cto]', '$Par_Sql[Pap_Ctd]', '$Par_Sql[Pap_Val]', $Par_Sql[Atp_Cod], $Par_Sql[Pag_Cod], '$Par_Sql[Asi_Cod]','$Par_Sql[Pap_Est]');";
            // //ChromePhp::log($sql);
            break;
        //insertar un registro en la tabla asientos
        case 109:
            $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
                            VALUES($Par_Sql[Com_Cod], '$Par_Sql[Asi_Deh]', '$Par_Sql[Asi_Val]', 'ANTICIPO A PROVEEDORES', $Par_Sql[Pld_Cod], '$Par_Sql[Asi_Glo]');";
            // //ChromePhp::log($sql);
            break;
        case 110:
            $sql = "SELECT Atp_Cod, Atp_Est from anticipos_proveedores where Com_Cod= $Par_Sql[0]";
            break;

        case 111:
            $sql = "DELETE FROM anticipos_proveedores where Com_Cod= $Par_Sql[Com_Cod]";
            break;

        case 112:
            $sql = "SELECT Pag_Val from det_ccpp_p where Com_Cod= $Par_Sql[0] and Pag_Est='A'";
            break;

        case 113:
            $sql = "SELECT SUM(det_ant_ccpp.Dac_Val) as Valor from anticipos_proveedores, det_ant_ccpp where anticipos_proveedores.Com_Cod= $Par_Sql[0] and anticipos_proveedores.Atp_Cod = det_ant_ccpp.Atp_Cod GROUP BY det_ant_ccpp.Atp_Cod";
            break;
        case 114:
            $sql = "UPDATE anticipos_proveedores SET Atp_Val='$Par_Sql[Atp_Val]', Atp_Est='$Par_Sql[Atp_Est]' WHERE Com_Cod='$Par_Sql[Com_Cod]';";
            //echo $sql.'<br/>';
            break;

        case 115:
            $sql = "UPDATE pago_anticipo_proveedores SET Pap_Est = '$Par_Sql[Pap_Est]', Pap_Val = '$Par_Sql[Atp_Val]' WHERE Atp_Cod = '$Par_Sql[Atp_Cod]';";
            break;

        //CONSULTA DE CARGA MASIVA 
        case 201:
            $sql = "UPDATE carga_masiva SET Carm_Est='C' WHERE Carm_Id='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;

        case 966:
            $sql = "UPDATE det_ccpp_p
                    INNER JOIN tipos_pago ON det_ccpp_p.Pag_Cod = tipos_pago.Pag_Cod
                    INNER JOIN comprobantes ON det_ccpp_p.Com_Cod = comprobantes.Com_Cod
                    SET det_ccpp_p.Pag_Est = 'I', 
                    comprobantes.Com_Est = 'I'
                    WHERE 
                    det_ccpp_p.Cpp_Cod = $Par_Sql[0]
                    AND tipos_pago.Pag_Des = 'Retencion'
                    AND tipos_pago.Pag_Abr = 'RET'";
            break;
        case 967:
            $sql = "UPDATE kardex_ie SET Kar_Est ='I'
                        WHERE Kar_Int=$Par_Sql[Kar_Int] AND Iva_Cod=$Par_Sql[Iva_Cod] AND Pro_Cod=$Par_Sql[Pro_Cod] 
                        AND Cop_Cod = $Par_Sql[Cop_Cod] AND Kar_Est='A';";
            break;

        case 968:
            $sql = "SELECT * FROM provee_aut WHERE Prv_Cod=$Par_Sql[Prv_Cod]";
            break;

        case 969: //consulta la bodega que se utilizo 
            $sql = "SELECT Bod_Cod FROM kardex_ie WHERE Cop_Cod = $Par_Sql[0] limit 1";
            break;

        case 970:
            $sql = "UPDATE compras Set Cop_Obs = '$Par_Sql[Cop_Observacion]' WHERE Cop_Cod = $Par_Sql[Cop_Codigo]";
            break;

        case 971:
            $sql = "UPDATE comprobantes 
            INNER JOIN compr_auto ON compr_auto.Com_Cod = comprobantes.Com_Cod
            INNER JOIN compras ON compras.Cop_Cod = compr_auto.Cop_Cod
            SET comprobantes.Com_Obs = '$Par_Sql[Cop_Observacion]'
            WHERE compras.Cop_Cod = $Par_Sql[Cop_Codigo]";
            break;

        case 990: //obtener comprobante y valor de la retencion  
            $sql = "SELECT Com_Cod, Pag_Val FROM det_ccpp_p WHERE Cpp_Cod = $Par_Sql[0] ";
            break;

        case 991: //elimina el debe del comprobante de retencion
            $sql = "DELETE FROM asientos WHERE Com_Cod = $Par_Sql[Com_Ret] AND Asi_Deh = 'D'";
            break;

        case 9911: //cambiar el valor del haber del comprobante 
            $sql = "UPDATE asientos SET Asi_Val = (Asi_Val - $Par_Sql[Pag_Val]), Pld_Cod = $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = 'H' ";
            break;

        case 992: //cambiar los asientos del comprobante de la retencion al comprobante de la compra
            $sql = "UPDATE asientos SET Com_Cod = $Par_Sql[Com_Cod]  WHERE Com_Cod = $Par_Sql[Com_Ret] ";
            break;

        case 993: //Eliminar el detalle de cuentas por pagar   
            $sql = "DELETE FROM det_ccpp_p WHERE Cpp_Cod = $Par_Sql[Cpp_Cod] ";
            break;

        case 994: //Eliminar el comprobante de la retencion   
            $sql = "DELETE FROM comprobantes WHERE Com_Cod = $Par_Sql[Com_Ret] ";
            break;

        //elimina cuentas por pagar 

        case 995: //Eliminar el comprobante de la retencion   
            $sql = "SELECT det_plan.Pld_Cod, retencion.Ret_Fec FROM retencion
                    INNER JOIN det_retenc ON retencion.Ret_Cod = det_retenc.Ret_Cod
                    INNER JOIN reniva_pla ON reniva_pla.Ren_Cod = det_retenc.Ren_Cod
                    INNER JOIN det_plan ON reniva_pla.Pld_Cod = det_plan.Pld_Cod
                    INNER JOIN plan_cuenta ON det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    WHERE retencion.Cop_Cod = $Par_Sql[Cop_Cod] AND plan_cuenta.Emp_Cod = $Par_Sql[Emp_Cod] and reniva_pla.Ren_Tip = 'C' and det_plan.Pld_Est = 'A'";
            break;

        case 996: //Consulto el comprobante de compra.
            $sql = "SELECT * FROM comprobantes WHERE Com_Cod = $Par_Sql[Com_Cod] ";
            break;

        case 9966: //Consulto el comprobante de compra.
            $sql = "SELECT Pld_Cod FROM asientos WHERE Com_Cod = $Par_Sql[Com_Cod] and Pld_Cod = $Par_Sql[Pld_Cod]";
            break;

        case 997: //cambiar los asientos del comprobante de la cinora al comprobante de la retencion
            $sql = "UPDATE asientos SET Com_Cod = $Par_Sql[Com_Ret]  WHERE Com_Cod = $Par_Sql[Com_Cod] and Pld_Cod = $Par_Sql[Pld_Cod] and Asi_Deh = 'H'";
            break;

        case 998: //Consulto el total de la retencion 
            $sql = "SELECT sum(Asi_Val) as totalRetencion FROM asientos WHERE Com_Cod = $Par_Sql[Com_Cod] and Asi_Deh = 'H' ";
            break;

        case 999: //inserta asiento debe en el comprobante de retencion
            $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
                            VALUES($Par_Sql[Com_Cod], 'D', '$Par_Sql[Asi_Val]', '$Par_Sql[Cop_Num]', $Par_Sql[Pld_Cod], '$Par_Sql[Cop_Num]')";
            break;

        case 1000: //update el valor del comprobante de compra
            $sql = "UPDATE asientos SET Asi_Val = Asi_Val +  $Par_Sql[Asi_Val], Pld_Cod = $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = 'H'";
            break;
        case 10000: //update el valor del comprobante de compra
            $sql = "UPDATE asientos SET Pld_Cod = $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = 'H'";
            break;

        case 1001: //update el valor del comprobante de compra
            $sql = "UPDATE comprobantes SET Com_Val = $Par_Sql[Com_Val] WHERE Com_Cod = $Par_Sql[Com_Cod]";
            break;
        case 1002:
            $sql = "INSERT INTO ccpp_pagar(Com_Cod, Cop_Cod, Cpp_Ven, Cpp_Obs) VALUES ($Par_Sql[Com_Cod], $Par_Sql[Cop_Cod], '$Par_Sql[Cpp_Ven]', UPPER('$Par_Sql[Cpp_Obs]'));";
            break;

        case 1003:
            $sql = " SELECT autorizaci.*,tipo_compr.*,Suc_Sri
            FROM autorizaci
                INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
            WHERE ( Tic_Sri='3') AND autorizaci.Pun_Cod=$Par_Sql[0] AND Tic_Est='A' and autorizaci.Aut_Est='A'";
            break;


        case 1004: //Select para obtener los datos de la autorizacion segun los datos del vendedor
            $sql = "SELECT vendedor.Vnd_Cod,puntos_imp.Pun_Cod,vendedor.Prs_Cod,autorizaci.Aut_Cod,Aut_Sri,Aut_Fci,Aut_Cad,Aut_Ini,Aut_Fin,Cop_Cod,Pun_Sri,CURDATE() AS Fec_Sys
                        FROM vendedor
                        INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                        INNER JOIN autorizaci ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                        LEFT JOIN compras ON autorizaci.Aut_Cod=compras.Aut_Cod
                        WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND autorizaci.Tic_Cod='$Par_Sql[2]'  AND autorizaci.Aut_Cod='$Par_Sql[3]'";
            break;


        case 1005:
            $sql = "SELECT
                        CASE
                            WHEN MAX(CAST(SUBSTRING_INDEX(Cop_Num, '-', -1) AS UNSIGNED))IS NOT NULL AND MAX(CAST(SUBSTRING_INDEX(Cop_Num, '-', -1) AS UNSIGNED))>=$Par_Sql[1] THEN (
                                SELECT MIN(CAST(SUBSTRING_INDEX(t.Cop_Num, '-', -1) AS UNSIGNED))+1
                                FROM compras t
                                INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                                INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                                WHERE tp.Suc_Cod=$Par_Sql[4] AND ta.Aut_Sri='$Par_Sql[2]' AND ta.Tic_Cod=$Par_Sql[3] AND ta.Pun_Sri='$Par_Sql[5]' AND CAST(SUBSTRING_INDEX(t.Cop_Num, '-', -1) AS UNSIGNED) BETWEEN $Par_Sql[0] AND $Par_Sql[1] AND
                                NOT EXISTS (
                                    SELECT NULL FROM compras n
                                        INNER JOIN autorizaci AS na ON n.Aut_Cod = na.Aut_Cod
                                        INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                        WHERE CAST(SUBSTRING_INDEX(n.Cop_Num, '-', -1) AS UNSIGNED) = CAST(SUBSTRING_INDEX(t.Cop_Num, '-', -1) AS UNSIGNED) +1 AND np.Suc_Cod=$Par_Sql[4] AND na.Aut_Sri='$Par_Sql[2]' AND na.Pun_Sri='$Par_Sql[5]' AND na.Tic_Cod=$Par_Sql[3] AND  CAST(SUBSTRING_INDEX(n.Cop_Num, '-', -1) AS UNSIGNED) BETWEEN $Par_Sql[0] AND $Par_Sql[1]
                                    )
                               )
                        ELSE IFNULL(MAX(CAST(SUBSTRING_INDEX(Cop_Num, '-', -1) AS UNSIGNED)),$Par_Sql[0]-1)+1
                        END AS siguiente,count(CAST(SUBSTRING_INDEX(Cop_Num, '-', -1) AS UNSIGNED)) as contador, autorizaci.Aut_Tem 
                    FROM compras
                    INNER JOIN autorizaci ON compras.Aut_Cod=autorizaci.Aut_Cod
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE Suc_Cod=$Par_Sql[4] AND autorizaci.Aut_Sri='$Par_Sql[2]' AND autorizaci.Pun_Sri='$Par_Sql[5]' AND autorizaci.Tic_Cod=$Par_Sql[3] AND CAST(SUBSTRING_INDEX(Cop_Num, '-', -1) AS UNSIGNED) BETWEEN $Par_Sql[0] AND $Par_Sql[1]";
            break;
        case 1006:
            //$sql = "SELECT * FROM nego_camaron WHERE Emp_Cod = $Par_Sql[0] AND Est_Neg = 'A' OR Est_Neg='P'";
            if (!empty($Par_Sql[1])) $Par_Sql[1] = " AND Num_Neg=$Par_Sql[1]";
            $sql = "SELECT * FROM nego_camaron WHERE Emp_Cod IN ($Par_Sql[0]) AND Est_Neg = 'A' OR Est_Neg='P'  $Par_Sql[1] ";

            break;

        case 1007:
            if (empty($Par_Sql[3])) {
                $sql = "INSERT INTO nego_documentos(Cod_Neg, Cod_Doc, Abr_Doc) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]');";
            } else {
                $sql = "UPDATE nego_documentos SET Cod_Neg = $Par_Sql[0], Cod_Doc = $Par_Sql[1], Abr_Doc = 'CMP' WHERE Cod_Nd = $Par_Sql[3]";
            }
            break;
        case  1008:
            $sql = "SELECT nego_documentos.*, Num_Neg FROM nego_documentos INNER JOIN nego_camaron  ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg WHERE /* Emp_Cod =  $Par_Sql[0] AND*/ Cod_Doc = $Par_Sql[1] AND Abr_Doc = 'CMP'";
            break;
        case 1009: //update el valor del Cop_Ide de compra
            $sql = "UPDATE compras SET Cop_Ide = $Par_Sql[Cop_Ide] WHERE Cop_Cod = $Par_Sql[Cop_Cod]";
            break;
        case  1010:
            $sql = "DELETE FROM nego_documentos WHERE  Cod_Nd =  $Par_Sql[0] AND  Abr_Doc = 'CMP'";
            break;
        case 1011:
            $sql = "SELECT
                        autorizaci.Aut_Cod, autorizaci.Pun_Cod,
                        autorizaci.Tic_Cod, autorizaci.Pun_Sri,
                        autorizaci.Aut_Sri, puntos_imp.Pun_Des,
                        IF (autorizaci.Aut_Est = 'A', 'Activo', 'Inactivo') AS Aut_Est
                    FROM autorizaci
                        INNER JOIN puntos_imp ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
                        INNER JOIN vendedor ON puntos_imp.Pun_Cod = vendedor.Pun_Cod
                    WHERE 
                        puntos_imp.Suc_Cod = $Par_Sql[0]
                        AND Aut_Est = 'A'
                    GROUP BY
                        autorizaci.Pun_Sri
                    ORDER BY
                        Pun_Sri ASC;";
            break;
        case 1012:
            $sql = "UPDATE  det_compra SET  Ret_Ren_Sri = ''  WHERE Cop_Cod='$Par_Sql[0]'";
            break;
        case 1013:
            $sql = "SELECT grupo_clientes.* FROM det_grup_empresas 
            INNER JOIN grupo_clientes ON grupo_clientes.Cod_Grup = det_grup_empresas.Cod_Group
            WHERE det_grup_empresas.Emp_Cod = $_SESSION[Ses_Emp_Cod] ";
            break;
        case 1014:
            $sql = "SELECT 
                        pr.Prv_Cod,
                        CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) AS proveedor,
                        d.Pld_Cdc,
                        d.Pld_Des,
                        c.Cop_Cod,
                        cp.Cpp_Cod,
                        c.Cop_Fec,
                        c.Cop_Num,
                        c.Cop_Obs,
                        cp.Cpp_Ven,
                        comp.Com_Cod,
                        CONCAT(
                            ta.Tia_Abr, '-',
                            LPAD(MONTH(comp.Com_Fec), 2, '0'), '-',
                            comp.Com_Num
                        ) AS Com_Codigo,
                        a.Asi_Cod,
                        a.Asi_Val,
                        ROUND(COALESCE(pay.Abono, 0), 2) AS Abono,
                        ROUND(a.Asi_Val - COALESCE(pay.Abono, 0), 2) AS Saldo,
                        CASE
                            WHEN ROUND(COALESCE(pay.Abono,0),2) = a.Asi_Val THEN 'Pagado'
                            WHEN DATEDIFF(cp.Cpp_Ven, CURRENT_DATE()) >= 0
                            THEN CONCAT(DATEDIFF(cp.Cpp_Ven, CURRENT_DATE()), ' dias')
                            ELSE 'Vencido'
                        END AS vencimiento,
                        nd.*,
                        nc.Num_Neg
                    FROM compras c
                        INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
                        INNER JOIN persona p ON pr.Prs_Cod = p.Prs_Cod
                        INNER JOIN ccpp_pagar cp ON c.Cop_Cod = cp.Cop_Cod
                        INNER JOIN comprobantes comp ON cp.Com_Cod = comp.Com_Cod
                        INNER JOIN tipo_asien ta ON ta.Tia_Cod = comp.Tia_Cod
                        INNER JOIN asientos a ON comp.Com_Cod = a.Com_Cod AND a.Asi_Deh = 'H'
                        INNER JOIN det_plan d ON a.Pld_Cod = d.Pld_Cod
                        INNER JOIN perio_cont pc ON c.Pec_Cod = pc.Pec_Cod
                        LEFT JOIN nego_documentos nd ON nd.Cod_Doc = c.Cop_Cod
                        LEFT JOIN nego_camaron nc ON nd.Cod_Neg = nc.Cod_Neg
                        LEFT JOIN (
                            -- Subconsulta que suma los pagos por Cpp_Cod (solo comprobantes activos)
                            SELECT det.Cpp_Cod,
                                SUM(IF(comp2.Com_Est = 'A', ROUND(det.Pag_Val,2), 0)) AS Abono
                            FROM det_ccpp_p det
                            LEFT JOIN comprobantes comp2 ON comp2.Com_Cod = det.Com_Cod
                            GROUP BY det.Cpp_Cod
                        ) pay ON pay.Cpp_Cod = cp.Cpp_Cod
                    WHERE
                        pr.Prv_Cod = $Par_Sql[0]
                        AND c.Cop_Est IN ('A','E')
                        AND comp.Com_Est IN ('A','E')
                        AND pc.Pec_Cod = $Par_Sql[1]
                        AND pr.Emp_Cod = $Par_Sql[2]
                        ORDER BY cp.Cpp_Ven;";
            break;
        case 1015:
            $sql = "UPDATE det_compra SET Asi_Cod = NULL WHERE Cop_Cod = $Par_Sql[0]";
            break;
    }
    return $sql;
}
