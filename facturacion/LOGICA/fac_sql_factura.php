<?php

/* Factura de venta */

function sentencias_facturaVenta($id, $Par_Sql)
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
                $campos = " Cli_Cod, persona.Prs_Cod, Prs_Ced,Ide_Prv as op_ide, Ide_Prv, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, Cli_Dir, Prs_Dir, Prs_Tel, IF(Cli_Cor IS NULL OR TRIM(Cli_Cor)='',Prs_Cor,Cli_Cor)AS Prs_Cor, IF(Cli_Est='A','Activo','Inactivo') as Cli_Est";
            }
            $sql = "SELECT $campos FROM persona
            INNER JOIN cliente ON persona.Prs_Cod=cliente.Prs_Cod
            LEFT JOIN identifica ON persona.Ide_Cod=identifica.Ide_Cod 
            WHERE Prs_Ced!='0' AND persona.Ide_Cod IS NOT NULL AND $search AND Cli_Est='A' AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            break;
        case 2: //Busqueda de clientes
            $sql = "SELECT persona.*,cliente.Cli_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as cliente FROM persona
                    LEFT JOIN cliente ON cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1]
                    WHERE Prs_Ced LIKE '$Par_Sql[0]%'  LIMIT 2;";
            break;
        case 3: //Insert en la tabla persona
            $sql = "INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod,Prs_Tel) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','$Par_Sql[Prs_Sex]',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod],$Par_Sql[Prs_Tel]);";
            break;
        case 4: //Insert en la tabla cliente
            $sql = "INSERT INTO cliente(Prs_Cod,Cli_Tic,Cli_Cup,Cli_Ruf,Cli_Fac,Cli_Dir,Cli_Con,Cli_Tip,Cli_Cor,Emp_Cod) VALUES($Par_Sql[Prs_Cod],'$Par_Sql[Cli_Tic]','" . (empty($Par_Sql['Cli_Cup']) ? '0' : $Par_Sql['Cli_Cup']) . "','" . (empty($Par_Sql['Cli_Ruf']) ? '' : $Par_Sql['Cli_Ruf']) . "','" . (empty($Par_Sql['Cli_Fac']) ? '' : $Par_Sql['Cli_Fac']) . "','" . (empty($Par_Sql['Cli_Dir']) ? '' : $Par_Sql['Cli_Dir']) . "','$Par_Sql[Cli_Con]','" . (empty($Par_Sql['Cli_Tip']) ? 'R' : $Par_Sql['Cli_Tip']) . "'," . (empty($Par_Sql['Cli_Cor']) ? 'NULL' : "'$Par_Sql[Cli_Cor]'") . ",$Par_Sql[Emp_Cod]);";
            break;
        case 5: //Select para cargar los datos de la tabla perio_cont
            $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio,perio_cont.Pla_Cod
                    FROM perio_cont
                    LEFT JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND Pec_Est='A' ORDER BY Pec_Fei DESC";
            break;
        case 6: //Consulta la ciudad en base al usuario
            $sql = "SELECT sucursal.Ciu_Cod,ciudad.Ciu_Des
                    FROM usuarios, sucursal, ciudad
                    WHERE usuarios.Suc_Cod = sucursal.Suc_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod AND usuarios.Usu_Cod = '$Par_Sql[0]'";
            break;
        case 7: //Select para obtener el Pun_Cod y Vnd_Cod del usuario que ha iniciado la sesi�n
            $sql = "SELECT Vnd_Cod,vendedor.Pun_Cod,puntos_imp.Pun_Des 
                    FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND puntos_imp.Pun_Est='A'";
            break;
        case 8: //Select para listar los tipos de comprobantes de Tic_Sr1=0,1,2,41,44,47,48,49,50,51,52
            $where_doc = "Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
            if (isset($Par_Sql[2])) {
                $where_doc = "Tic_Sri='4' OR Tic_Sri='5'";
            }
            if (isset($Par_Sql[1]) && ($Par_Sql[1]) != 0) {
                $where = "autorizaci.Aut_Cod='$Par_Sql[1]'";
            } else {
                $where = "autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A'";
            }

            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE ($where_doc) AND $where ";
            break;
        case 9: //Select para obtener los datos de la autorizacion segun los datos del vendedor
            $sql = "SELECT vendedor.Vnd_Cod,puntos_imp.Pun_Cod,vendedor.Prs_Cod,autorizaci.Aut_Cod,Aut_Sri,Aut_Fci,Aut_Cad,Aut_Ini,Aut_Fin,Vet_Cod,Pun_Sri,CURDATE() AS Fec_Sys
                    FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN autorizaci ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    LEFT JOIN ventas ON autorizaci.Aut_Cod=ventas.Aut_Cod
                    WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND autorizaci.Tic_Cod='$Par_Sql[2]'  AND autorizaci.Aut_Cod='$Par_Sql[3]'";
            break;
        case 10: //Select para obtener todos los n�meros de secuencia correspondientes a un Aut_Cod
            $sql = "SELECT
                        CASE
                            WHEN MAX(Vet_Num)IS NOT NULL AND MAX(Vet_Num)>=$Par_Sql[1] THEN (
                                SELECT MIN(t.Vet_Num)+1
                                FROM ventas t
                                INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                                INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                                WHERE tp.Suc_Cod=$Par_Sql[4] AND ta.Aut_Sri='$Par_Sql[2]' AND ta.Tic_Cod=$Par_Sql[3] AND ta.Pun_Sri='$Par_Sql[5]' AND t.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1] AND
                                NOT EXISTS (
                                    SELECT NULL FROM ventas n
                                        INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                        INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                        WHERE n.Vet_Num=t.Vet_Num+1 AND np.Suc_Cod=$Par_Sql[4] AND na.Aut_Sri='$Par_Sql[2]' AND na.Pun_Sri='$Par_Sql[5]' AND na.Tic_Cod=$Par_Sql[3] AND n.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]
                                    )
                               )
                        ELSE IFNULL(MAX(Vet_Num),$Par_Sql[0]-1)+1
                        END AS siguiente,count(Vet_Num) as contador, autorizaci.Aut_Tem 
                    FROM ventas
                    INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE Suc_Cod=$Par_Sql[4] AND autorizaci.Aut_Sri='$Par_Sql[2] ' AND autorizaci.Pun_Sri='$Par_Sql[5]' AND autorizaci.Tic_Cod=$Par_Sql[3] AND Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]";
            break;
        case 11: //Select para comprobar si el numero de secuencia ya se encuentra registrado
            $sql = "SELECT COUNT(Vet_Cod)AS total FROM ventas
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = ventas.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND autorizaci.Pun_Sri='$Par_Sql[4]' AND Suc_Cod='$Par_Sql[0]' AND Vet_Num='$Par_Sql[2]'" . (!empty($Par_Sql[3]) ? "AND ventas.Vet_Cod<>$Par_Sql[3]" : '') . ';';
            break;
        case 12:
            $sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            break;
        case 13: //Con esta sentencia consulto producto y stock
            if ($Par_Sql[3] == '') $campos = " COUNT(item.Ite_Cod) AS total ";
            else $campos = "prec.Pre_Est, prec.Pre_Fin, prec.Pre_Ini, prec.Pre_Pvp, prec.Pre_Cod, prec.Pre_Des, tipo_preci.Tpv_Cod, item.Ite_Cod,item.Ite_Est,ice.Ice_Int,categorias.Cat_Cod,categorias.Cat_Des,
            item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,Adq_Cor,adquisicio.Adq_Des,iva.Iva_Cod,
            iva.Iva_Por,iva.Iva_Sri,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,
            producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,Stk_Can,Ice_Por,tipo_preci.Tpv_Des,
            prec.Pre_Pvp as Vet_Pru";
            if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
            //else if($Par_Sql[2]=='d') $search=" ( UPPER(item.Ite_Lar) LIKE UPPER('%$Par_Sql[0]%') OR UPPER(producto.Pro_Obs) LIKE UPPER('%$Par_Sql[0]%')  ) ";    
            else {
                $search = "";
                $array = explode(" ", strtoupper($Par_Sql[0]));
                foreach ($array as $ar) {
                    if (!empty($ar) && $ar != '')
                        // $search .= (($search != '' ? " AND " : "") . "CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs,Ite_Cor)) AS CHAR)LIKE '%$ar%'");
                        $search .= (($search != '' ? " AND " : "") . "CAST(UPPER(
                                                                            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                                                                                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                                                                                    CONCAT(Ite_Lar,Pro_Obs,Ite_Cor),
                                                                                'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),
                                                                                'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U')
                                                                        )
                                                                    AS CHAR) LIKE '%$ar%'");
                }
                if ($search == '') $search = "1=1";
            }
            $sql = "SELECT
                    $campos
                  FROM
                    categorias
                    INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                    INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                    INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                    INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                    INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                    INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
                    INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                    LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
                    INNER JOIN precios AS prec ON prec.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND prec.Pro_Cod=producto.Pro_Cod AND prec.Pre_Est='A'
                    INNER JOIN tipo_preci ON prec.Tpv_Cod = tipo_preci.Tpv_Cod                  
                  WHERE $search AND Pro_Est='A' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";

            break;

        case 133: //Variante de 13 para VENTA RÁPIDA (Búsqueda expandida con códigos)
            if ($Par_Sql[3] == '') $campos = " COUNT(item.Ite_Cod) AS total ";
            else $campos = "prec.Pre_Est, prec.Pre_Fin, prec.Pre_Ini, prec.Pre_Pvp, prec.Pre_Cod, prec.Pre_Des, tipo_preci.Tpv_Cod, item.Ite_Cod,item.Ite_Est,ice.Ice_Int,categorias.Cat_Cod,categorias.Cat_Des,
            item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,Adq_Cor,adquisicio.Adq_Des,iva.Iva_Cod,
            iva.Iva_Por,iva.Iva_Sri,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,
            producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,Stk_Can,Ice_Por,tipo_preci.Tpv_Des,
            prec.Pre_Pvp as Vet_Pru";
            
            $search = "";
            $array = explode(" ", strtoupper($Par_Sql[0]));
            foreach ($array as $ar) {
                if (!empty($ar) && $ar != '')
                    $search .= (($search != '' ? " AND " : "") . "CAST(UPPER(
                                                                        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                                                                            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                                                                                CONCAT(Ite_Lar,' ',Pro_Obs,' ',Ite_Cor,' ',IFNULL(producto.Pro_Bar,''),' ',IFNULL(producto.Pro_Cod,''),' ',IFNULL(producto.Pro_Cod_Emp,'')),
                                                                            'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),
                                                                            'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U')
                                                                    )
                                                                AS CHAR) LIKE '%$ar%'");
            }
            if ($search == '') $search = "1=1";
            
            $sql = "SELECT
                    $campos
                  FROM
                    categorias
                    INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                    INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                    INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                    INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                    INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                    INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
                    INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                    LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
                    INNER JOIN precios AS prec ON prec.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND prec.Pro_Cod=producto.Pro_Cod AND prec.Pre_Est='A'
                    INNER JOIN tipo_preci ON prec.Tpv_Cod = tipo_preci.Tpv_Cod                  
                  WHERE $search AND Pro_Est='A' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";

            break;
        case 14: //Select para obtener el precio de los productos
            $sql = "SELECT Pre_Cod,Pre_Pvp,Tpv_Des,Pre_Des,Pre_Est,precios.Tpv_Cod,Pre_Ini,Pre_Fin FROM precios INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod WHERE precios.Suc_Cod='$Par_Sql[0]' AND Pro_Cod='$Par_Sql[1]' AND Pre_Est='$Par_Sql[2]' " . (empty($Par_Sql[3]) ? '' : "AND Tpv_Def='D'") . " " . (empty($Par_Sql[4]) ? '' : "(('$Par_Sql[4]' AND BETWEEN Pre_Ini AND Pre_Fin) OR (Pre_Ini IS NULL AND Pre_Fin IS NULL) OR (Pre_Ini='0000-00-00' AND Pre_Fin='0000-00-00'))") . ";";

            break;
        case 15: //busca cuenta relacion producto
            $sql = "SELECT Pro_Cod,produ_plan.Pld_Cod,Tip_Pld,Pld_Cdc,Pld_Des,Pla_Cod FROM produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pro_Cod=$Par_Sql[1] AND (Tip_Pld='$Par_Sql[2]' OR Tip_Pld='I') AND Pla_Cod=$Par_Sql[0]";
            break;
        case 16: //Select para obtener los datos de la tabla iva, cuyos porcentajes sean mayor a cero y se encuentren activos
            $sql = "SELECT * FROM iva WHERE Iva_Por>0 ORDER BY Iva_Ini DESC";
            break;
        case 17: //Select para obtener los tipos de pago de la tabla tipos_pago
            $sql = "SELECT * FROM tipos_pago WHERE Pag_Est='A'";
            break;
        case 18: //Select obtener el listado de bancos de la tabla del mismo nombre
            $sql = "SELECT * FROM bancos;";
            break;

        //CONSULTAS PARA VALIDAR EL CLIENTE EN EL AGREGAR 
        case 177: //Select para obtener los datos de una persona seg�n su c�dula
            $sql = "SELECT persona.* FROM persona WHERE Prs_Ced LIKE '$Par_Sql[0]%'";
            return $sql;
            break;
        case 188: //Select para comprobar si el cliente ya se encuentra registrado
            $sql = "SELECT Cli_Cod FROM cliente WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";
            return $sql;
            break;
        case 299: //Select para obtener la lista de identificaciones
            $sql = "SELECT *,IF(ISNULL(Ide_Pre),'Ec','Ex') AS Tipo FROM identifica WHERE Ide_Est='A'";
            return $sql;
            break;

        case 19: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla banco 'CONTADO'
            $forma_pago = "and For_Cod=1";
            if (!empty($Par_Sql[2])) {
                $forma_pago = "and For_Cod=$Par_Sql[2]";
            }


            $sql = "SELECT distinct banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des,Ban_Tip,if(banco.Ban_Tip='B','si','no') AS banco, if(banco.Ban_Tip='C',1,0) as Pag_Cod  FROM banco, det_plan, pago_plan, plan_cuenta,tipos_pago
                    WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    AND plan_cuenta.Pla_Cod='$Par_Sql[0]' $forma_pago AND Emp_Cod='$Par_Sql[1]' AND (banco.Ban_Tip='B' OR banco.Ban_Tip='C' ) AND pago_plan.Pag_Est='A' ORDER BY Pld_Cdc, Pld_Des";
            break;


        case 1111111: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla banco 'CONTADO'
            $sql = "SELECT pago_plan.Pag_Cod,banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des,Ban_Tip,'si' AS banco FROM banco, det_plan, pago_plan, plan_cuenta
                    WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    AND plan_cuenta.Pla_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND banco.Ban_Tip='B' AND pago_plan.Pag_Est='A' ORDER BY Pld_Cdc, Pld_Des";
            break;

        case 20: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla tipo_param ' correspondiente a EFECTIVO Y CHEQUE'
            $sql = "SELECT tipo_param.Tpa_Cod,det_plan.Pld_Cod,Pld_Des,Tpa_Abr,IF(tipo_param.Tpa_Cod=16,1,3) AS Pag_Cod,'no' AS banco FROM tipo_param
                    INNER JOIN plan_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    INNER JOIN det_plan ON plan_param.Pld_Cod=det_plan.Pld_Cod
                    WHERE (Tpa_Abr='CBA' OR Tpa_Abr='CCH') and det_plan.Pla_Cod=$Par_Sql[0] and plan_param.Ppc_Est='A'";
            break;
        case 21: //Select para listar los porcentajes de impuesto a la renta
            if (empty($Par_Sql['limits'])) $campos = "COUNT(renta_iva.Ren_Cod) AS total";
            else $campos = "Adq_Cod,renta_iva.Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,renta_iva.Ren_Tip,if(renta_iva.Ren_Tip='B','BIENES','SERVICIO')as Ren_Tipo,Ren_Ret,if(Ren_Ret='R','RENTA','IVA')as Ren_Rete,Ren_Est,if(Ren_Est='A','Activo','Anulado')as Ren_Esta";
            if ($Par_Sql['op_opciones'] == 'd') $where = "(Ren_Con LIKE '$Par_Sql[search]%' OR Ren_Con LIKE '%$Par_Sql[search]%')";
            else if ($Par_Sql['op_opciones'] == 'c') $where = "Ren_Sri LIKE '$Par_Sql[search]%'";
            else {
                if (!empty($Par_Sql['search'])) $where = "Ren_Por = '$Par_Sql[search]'";
                else $where = "";
            }
            $sql = "SELECT $campos FROM renta_iva WHERE Ren_Est='A' AND Ren_Ret='$Par_Sql[tipo]'" . (!empty($where) ? "AND $where " : '') . (!empty($Par_Sql['limits']) ? " ORDER BY Ren_Sri ASC $Par_Sql[limits];" : ';');
            break;
        case 22: //Select para presentar cuentas de impuesto a la renta
            $sql = "SELECT reniva_pla.Pld_Cod, Pld_Cdc, Pld_Des FROM reniva_pla INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod WHERE Ren_Cod='$Par_Sql[1]' AND det_plan.Pla_Cod='$Par_Sql[0]' AND Ren_Tip='$Par_Sql[2]'";
            break;
        case 23: //Insert sobre la tabla venta
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod)
                    VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]',
                       '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]',
                        " . (empty($Par_Sql[10]) ? 'NULL' : "'$Par_Sql[10]'") . ",
                        " . (empty($Par_Sql[11]) ? 'NULL' : "'$Par_Sql[11]'") . ",
                        " . (!empty($Par_Sql[12]) ? "'$Par_Sql[12]'" : "NULL") . ",
                        " . (!empty($Par_Sql[13]) ? "'$Par_Sql[13]'" : "NULL") . ",
                        " . (!empty($Par_Sql[14]) ? "'$Par_Sql[14]'" : "NULL") . ",
                        " . (!empty($Par_Sql[15]) ? "$Par_Sql[15]" : "NULL") . ")";
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
            if (empty($Par_Sql[6]))
                $sql = "INSERT INTO det_ccpp_p(Cpp_Cod,Pag_Cod,Com_Cod,Pag_Fec,Pag_Val,Pag_Est,Pag_Obs) VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]','$Par_Sql[4]','A','$Par_Sql[5]')";
            else
                $sql = "UPDATE det_ccpp_p SET Pag_Cod=$Par_Sql[1],Com_Cod=$Par_Sql[2],Pag_Fec='$Par_Sql[3]',Pag_Val='$Par_Sql[4]',Pag_Est='A',Pag_Obs='$Par_Sql[5]' WHERE Com_Cod=$Par_Sql[6] AND Cpp_Cod=$Par_Sql[0]";
            //echo $sql."<br>";
            break;

        case 255:
            $sql = "INSERT INTO det_ccpp_c (Com_Cod,Pag_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,Cpc_Cod,Asi_Cod)
                values ($Par_Sql[Com_Cod],$Par_Sql[Pag_Cod],'$Par_Sql[Cpc_Fec]',$Par_Sql[Cpc_Val],'$Par_Sql[Cpc_Obs]',$Par_Sql[Cpc_Cod],$Par_Sql[Asi_Cod])";
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
        case 28: // usado
            /* cuenta descuentos */
            $sql = "SELECT plan_param.Pld_Cod,Pld_Cdc,Pld_Des,Pla_Cod FROM plan_param
                    INNER JOIN det_plan ON det_plan.Pld_Cod=plan_param.Pld_Cod
                    INNER JOIN tipo_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    WHERE Tpa_Abr='$Par_Sql[1]' AND Pla_Cod=$Par_Sql[0];";
            //echo $sql."<br>";
            break;
        case 29: // usado
            /* identificacion */
            $sql = "SELECT * FROM identifica WHERE Ide_Prc IS NOT NULL AND Ide_Prc<>'';";
            //echo $sql."<br>";
            break;
        case 30:
            $sql = "SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
            break;
        case 31: // usado
            if (empty($Par_Sql[9]))
                $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'"; //Antes Com_Tip

            break;

        case 32:
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(ventas.Vet_Cod) AS total";
            } else {
                $campos = "ventas.*,
                vende.Prs_Ape,
                vende.Prs_Nom,
                ciudad.Ciu_Des,
                Tic_Des,
                ventas_compr.Com_Cod,
                tipo_compr.Tic_Sri,
                ccpp_cobrar.Cpc_Cod,
                tipopagocom.*,
                Caj_Fec as Vet_Fec,
                concat(vende.Prs_Ape,' ',vende.Prs_Nom)as vendedor_per,
                concat(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)as cliente_per,
                cliente_ven.Prs_Ced,
                comprobantes.Pec_Cod,
                if(ccpp_cobrar.Cpc_Cod is null,'Contado','Credito')as Pago,
                if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
            }
            $Par_Sql['Tic_Cod'] = (!empty($Par_Sql['Tic_Cod']) ? "AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]" : "AND (tipo_compr.Tic_Sri=4 OR tipo_compr.Tic_Sri=5 )");
            if ($Par_Sql['op_opciones'] == 'd') {
                $search = "AND ventas.Vet_Num = '$Par_Sql[search]'";
                $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
            } else {
                $Par_Sql['Cmb_Mes'] = (!empty($Par_Sql['Pec_Cod']) && !empty($Par_Sql['Cmb_Mes']) ? "AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]" : '');
                $Par_Sql['Pec_Cod'] = (!empty($Par_Sql['Pec_Cod']) ? "AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'" : '');
                if ($Par_Sql['op_opciones'] == 'c')
                    $search = "AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search = "AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }
            $sql = "SELECT $campos FROM ventas
                  INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                  INNER JOIN persona as vende ON vendedor.Prs_Cod = vende.Prs_Cod
                  left join ventas_compr on ventas_compr.Vet_Cod=ventas.Vet_Cod
                  inner join cliente on cliente.Cli_Cod= ventas.Cli_Cod
                  INNER JOIN persona as cliente_ven ON cliente_ven.Prs_Cod = cliente.Prs_Cod
                  left join ccpp_cobrar on ccpp_cobrar.Vet_Cod=ventas.Vet_Cod
                  INNER JOIN ciudad ON ciudad.Ciu_Cod = ventas.Ciu_Cod
                  left join tipopagocom on tipopagocom.Tpc_Cod = ventas.Tpc_Cod
                  left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod AND comprobantes.Com_Est='A'
                  left join autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                  INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                  inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                WHERE ventas.Vet_Est<>'E'  AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $search
                $Par_Sql[order] $Par_Sql[limits] ;";
            //echo $sql.'<br/>';
            break;
        case 33:

            $sql = "SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
            break;

        case 34:
            $where_doc = "Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(ventas.Vet_Cod) AS total";
            } else {
                $campos = "ventas.*,
                nego_documentos.*, 
                vende.Prs_Ape,
                vende.Prs_Nom,Ide_Prv,
                ciudad.Ciu_Des,
                Tic_Des,Emp_Cod,
                ventas_compr.Com_Cod,
                tipo_compr.Tic_Sri,
                ccpp_cobrar.Cpc_Cod, ccpp_cobrar.Cpc_Ven, ccpp_cobrar.Cpc_Obs,
                tipopagocom.*,
                Caj_Fec as Vet_Fec,
                if(Vet_Ide='4',
                    if(LENGTH(cliente_ven.Prs_Ced)<13,concat(cliente_ven.Prs_Ced,'001'),cliente_ven.Prs_Ced),
                    if(Vet_Ide='5',
                        if(LENGTH(cliente_ven.Prs_Ced)>10,SUBSTRING(cliente_ven.Prs_Ced,1,10),cliente_ven.Prs_Ced),
                        cliente_ven.Prs_Ced  
                    )
                )as Prs_Ced,
                concat(vende.Prs_Ape,' ',vende.Prs_Nom)as vendedor_per,
                concat(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)as cliente_per,
                cliente_ven.Prs_Cor,cliente_ven.Prs_Dir,
                comprobantes.Pec_Cod,
                comprobantes.Com_Con,
                if(ccpp_cobrar.Cpc_Cod is null,'Contado','Credito')as Pago,
                if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                ventas_compr.Com_Cod,
                if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
            }
            $Par_Sql['Tic_Cod'] = (!empty($Par_Sql['Tic_Cod']) ? "AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]" : '');
            if ($Par_Sql['op_opciones'] == 'd') {
                $search = "AND ventas.Vet_Num = '$Par_Sql[search]'";
                $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
            } else {
                $Par_Sql['Cmb_Mes'] = (!empty($Par_Sql['Pec_Cod']) && !empty($Par_Sql['Cmb_Mes']) ? "AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]" : '');
                $Par_Sql['Pec_Cod'] = (!empty($Par_Sql['Pec_Cod']) ? "AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'" : '');
                if ($Par_Sql['op_opciones'] == 'c')
                    $search = "AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search = "AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }

            if (isset($Par_Sql["mis_ingresos"])) {
                if ($Par_Sql["mis_ingresos"] == 'S') {
                    $filtroUsuario = "AND vendedor.Prs_cod = $_SESSION[Ses_Prs_Cod]";
                }
            } else {
                $filtroUsuario = '';
            }

            $sql = "SELECT $campos FROM ventas
                  INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                  INNER JOIN persona as vende ON vendedor.Prs_Cod = vende.Prs_Cod
                  left join ventas_compr on ventas_compr.Vet_Cod=ventas.Vet_Cod
                  inner join cliente on cliente.Cli_Cod= ventas.Cli_Cod
                  INNER JOIN persona as cliente_ven ON cliente_ven.Prs_Cod = cliente.Prs_Cod
                  inner join identifica on cliente_ven.Ide_Cod= identifica.Ide_Cod
                  left join ccpp_cobrar on ccpp_cobrar.Vet_Cod=ventas.Vet_Cod
                  INNER JOIN ciudad ON ciudad.Ciu_Cod = ventas.Ciu_Cod
                  left join tipopagocom on tipopagocom.Tpc_Cod = ventas.Tpc_Cod
                  left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod AND comprobantes.Com_Est='A'
                  INNER JOIN autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                  INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod AND puntos_imp.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                  INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                  inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                  LEFT JOIN nego_documentos ON nego_documentos.Cod_Doc = ventas.Vet_Cod
                -- Anteriormente evitaba que eltipo de comprobante sea el 32 NOTA DE ENTREGA
              --  WHERE ($where_doc) AND ventas.Vet_Est<>'E' AND tipo_compr.Tic_Cod!=32 AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $filtroUsuario $search
                WHERE ($where_doc) AND ventas.Vet_Est<>'E'  AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $filtroUsuario $search
               
                $Par_Sql[order] $Par_Sql[limits] ;";
            //echo $sql.'<br/>';
            break;
        case 35:
            $sql = "SELECT Cop_Int,Cop_Int AS 'index', det_compra.Pro_Cod,Ice_Int,det_compra.Iva_Cod,Iva_Por,Iva_Sri,Cop_Pro AS Ite_Lar,Cop_Can,Cop_Pru,Cop_Imp,Cop_Dec,det_compra.Adq_Cod,Adq_Des,Adq_Cor,det_plan.Pld_Cod, Pld_Cdc,Pld_Des,Uni_Des,Iva_Cos FROM det_compra
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
            if (($Par_Sql[1]) != 0) {
                $where = "autorizaci.Aut_Cod='$Par_Sql[1]'";
            } else {
                $where = "autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A'";
            }

            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='5' OR Tic_Sri='4') AND $where ";
            break;
        case 38:
            $sql = "SELECT COUNT(Cop_Cod)AS total  FROM kardex_ie WHERE Cop_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 39:
            $sql = "SELECT asientos.Pld_Cod FROM asientos INNER JOIN banco ON banco.Pld_Cod=asientos.Pld_Cod WHERE Com_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 399:
            $sql = "SELECT asientos.Pld_Cod FROM asientos INNER JOIN ccpp_cliente ON ccpp_cliente.Pld_Cod=asientos.Pld_Cod WHERE Com_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 40:
            $sql = "SELECT Cop_Fec,Cop_Sec, Com_Fec, Com_Num FROM compras LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod INNER JOIN comprobantes ON comprobantes.Com_Cod=compr_auto.Com_Cod WHERE compras.Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 41:
            $sql = "DELETE FROM asientos WHERE Com_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 42:
            $sql = "DELETE  FROM det_compra WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 43:
            $sql = "SELECT * FROM kardex_ie WHERE Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 44:
            $sql = "DELETE  FROM kardex_ie WHERE Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 45: // usado
            $sql = "SELECT Tpc_Cod,Tpc_Sri,Tpc_Des FROM tipopagocom WHERE Tpc_Sri='01' OR Tpc_Sri='15' OR Tpc_Sri='16' OR Tpc_Sri='17' OR Tpc_Sri='18' OR Tpc_Sri='19' OR Tpc_Sri='20' OR Tpc_Sri='21' AND  Tpc_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 46:
            $sql = "DELETE  FROM det_ccpp_p WHERE Com_Cod='$Par_Sql[0]' ";
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
             WHERE autorizaci.Pun_Cod=$Par_Sql[0] AND autorizaci.Tic_Cod=$Par_Sql[1] AND autorizaci.Aut_Est = 'A'";
            //echo $sql.'<br/>';
            break;
        case 49: // usado
            $sql = "SELECT empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
                    sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
            FROM empresas
                    INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                    INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                    WHERE sucursal.Suc_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 50: // usado
            $sql = "SELECT COUNT(Vet_Cod)AS total FROM ventas
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = ventas.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND Suc_Cod='$Par_Sql[0]' AND autorizaci.Pun_Sri=$Par_Sql[4]  AND Vet_Num='$Par_Sql[2]'" . (!empty($Par_Sql[3]) ? "AND ventas.Vet_Cod<>$Par_Sql[3]" : '') . ';';
            break;
        case 51: // usado
            $sql = "SELECT
                    CASE
                        WHEN MAX(Vet_Num)IS NOT NULL AND MAX(Vet_Num)>=$Par_Sql[3] THEN (
                            SELECT MIN(t.Vet_Num)+1
                            FROM ventas t
                            INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                            INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                            WHERE tp.Suc_Cod=$Par_Sql[0] AND ta.Aut_Sri='$Par_Sql[1]' AND ta.Tic_Cod=$Par_Sql[4] AND t.Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3] AND
                            NOT EXISTS (
                                SELECT NULL FROM ventas n
                                    INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                    INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                    WHERE n.Vet_Num=t.Vet_Num+1 AND np.Suc_Cod=$Par_Sql[0] AND na.Aut_Sri='$Par_Sql[1]' AND na.Tic_Cod=$Par_Sql[4] AND n.Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3]
                                )
                           )
                        ELSE IFNULL(MAX(Vet_Num),$Par_Sql[2]-1)+1
                        END AS 'next'
                FROM ventas
                INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                WHERE Suc_Cod=$Par_Sql[0] AND autorizaci.Aut_Sri='$Par_Sql[1]' AND autorizaci.Tic_Cod=$Par_Sql[4] AND Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3]";
            //echo $sql.'<br/>';
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
        case 55: // usado
            $fecha_venta = "Cpc_Ven='$Par_Sql[2]', ";
            if ($Par_Sql[2] === '') {
                $fecha_venta = '';
            }
            if (empty($Par_Sql[4]))
                $sql = "INSERT INTO ccpp_cobrar(Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'));";
            else
                $sql = "UPDATE ccpp_cobrar SET $fecha_venta Cpc_Obs=UPPER('$Par_Sql[3]') WHERE Cpc_Cod='$Par_Sql[4]' AND Vet_Cod=$Par_Sql[1] AND Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 56:
            $sql = "SELECT
                    sum(
                          (
                      (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */
                          +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0)) /* ICE */
                          ) *(1+iva.Iva_Por/100)    /* IVA */
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
            $sql = "SELECT " . (!empty($Par_Sql[1]) ? "SUM(det_ccpp_c.Cpc_Val)" : "COUNT(det_ccpp_c.Cpc_Cod)") . "AS total FROM det_ccpp_c INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod WHERE Cpc_Cod='$Par_Sql[0]' " . (!empty($Par_Sql[1]) ? "AND Cpc_Est='$Par_Sql[1]' AND Com_Est='A'" : '') . ";";
            //echo $sql.'<br/>';
            break;
        case 577:
            $sql = "SELECT SUM(det_ccpp_c.Cpc_Val)AS total 
                    FROM det_ccpp_c
                    INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod
                    INNER JOIN tipos_pago ON det_ccpp_c.Pag_Cod = tipos_pago.Pag_Cod
                    WHERE Cpc_Cod='$Par_Sql[0]'
                    AND det_ccpp_c.Cpc_Est='$Par_Sql[1]' AND Com_Est='A' and tipos_pago.Pag_Abr = 'RET'";
            //echo $sql.'<br/>';
            break;

        case 58:
            $sql = "SELECT COUNT(Cop_Cod)AS total FROM det_reposicion WHERE Cop_Cod='$Par_Sql[0]' AND Dre_Tip!='P'";
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
        case 61: // usado
            $sql = "SELECT autorizaci.Aut_Sri, autorizaci.Pun_Sri, sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin FROM puntos_imp INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod) INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod) WHERE autorizaci.Aut_Cod ='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 62:
            $sql = "DELETE  FROM det_retenc WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 63:
            $sql = "DELETE  FROM retencion WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 64:
            $sql = "DELETE  FROM ccpp_pagar WHERE Cpp_Cod='$Par_Sql[2]' AND Cop_Cod='$Par_Sql[1]' AND Com_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 65:
            $sql = "SELECT COUNT(Vet_Cod)AS total FROM ventas_compr WHERE Com_Cod='$Par_Sql[0]';";
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
        case 68: // usado
            $sql = "SELECT * FROM iva WHERE Iva_Por='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 69: // usado
            $sql = "SELECT * FROM tipos_pago ORDER BY Pag_Cod;";
            //echo $sql.'<br/>';
            break;
        case 70: // usado
            if (!isset($Par_Sql[9]) || empty($Par_Sql[9]))
                $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'"; //Antes Com_Tip
            else
                $sql = "UPDATE comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' WHERE Com_Cod=$Par_Sql[9] ";
            //echo $sql."<br>";
            break;

        case 71: // usado
            $sql = "SELECT banco.*,Pld_Des FROM banco
                    INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    WHERE Ban_Cue!=0 AND Ban_Cue!='' AND Ban_Est='A' AND Emp_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;

        case 72: // usado
            $sql = "INSERT INTO pago_venta (Vet_Cod, Bak_Cod, Ban_Cod, Pag_Cod, Vet_Cue, Vet_Che, Vet_Mon, Vet_Cam, Vet_Tot, Vet_Num,Pld_Cod,Vet_Nau,Vet_Nlt,Vet_Nts, Vet_Plz)
                    VALUES ($Par_Sql[Vet_Cod]," . (empty($Par_Sql['Bak_Cod']) ? '1' : $Par_Sql['Bak_Cod']) . "," . (empty($Par_Sql['Ban_Cod']) ? 'NULL' : $Par_Sql['Ban_Cod']) . "," . $Par_Sql['Tipo_Cod'] . "," . (empty($Par_Sql['Vet_Cue']) ? 'NULL' : "'$Par_Sql[Vet_Cue]'") . ", " . (empty($Par_Sql['Vet_Che']) ? 'NULL' : "'$Par_Sql[Vet_Che]'")  . ", " . (empty($Par_Sql['Vet_Mon']) ? 'NULL' : "'$Par_Sql[Vet_Mon]'")  . ", " . (empty($Par_Sql['Vet_Cam']) ? 'NULL' : "'$Par_Sql[Vet_Cam]'") . ", $Par_Sql[Vet_Tot], '$Par_Sql[Vet_Num]'," . (empty($Par_Sql['Pag_Pld']) ? 'NULL' : "'$Par_Sql[Pag_Pld]'") . ",'" . (empty($Par_Sql['Vet_Nau']) ? null : $Par_Sql['Vet_Nau']) . "','" . (empty($Par_Sql['Vet_Nlt']) ? null : $Par_Sql['Vet_Nlt']) . "','" . (empty($Par_Sql['Vet_Nts']) ? null : $Par_Sql['Vet_Nts']) . "', '" . (empty($Par_Sql['Vet_Plz']) ? null : $Par_Sql['Vet_Plz']) . "')";

            break;
        case 73: // usado
            $sql = "SELECT Pre_Cod,Pre_Pvp,Pre_Des,Pre_Est,precios.Tpv_Cod,Pre_Ini,Pre_Fin FROM precios INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod WHERE precios.Suc_Cod='$Par_Sql[0]' AND Pro_Cod='$Par_Sql[1]' AND Pre_Est='$Par_Sql[2]' " . (empty($Par_Sql[3]) ? '' : "AND Tpv_Def='D'") . " " . (empty($Par_Sql[4]) ? '' : "(('$Par_Sql[4]' AND BETWEEN Pre_Ini AND Pre_Fin) OR (Pre_Ini IS NULL AND Pre_Fin IS NULL) OR (Pre_Ini='0000-00-00' AND Pre_Fin='0000-00-00'))") . ";";
            //echo $sql.'<br/>';
            break;
        case 74: // usado
            $sql = "SELECT * FROM tipo_preci WHERE Suc_Cod='$Par_Sql[0]' AND Tpv_Est='A';";
            //echo $sql.'<br/>';
            break;
        case 75: // usado
            $sql = "SELECT * FROM caja_aper WHERE Pun_Cod='$Par_Sql[0]' " . (empty($Par_Sql[1]) ? '' : " AND Caj_Est='$Par_Sql[1]'") . " ;";
            //echo $sql.'<br/>';
            break;

        /********** NUEVAS **********/

        case 76: //SELECT para verificar si la caja ya fue creada dentro de la tabla caja_aper
            $sql = "SELECT Caj_Cod,Pun_Cod,Caj_Fec
                    FROM caja_aper
                    WHERE Pun_Cod='$Par_Sql[0]' AND Caj_Fec='$Par_Sql[1]'";
            break;
        case 77: //INSERT en la tabla caja_aper, con el prop�sito de aperturar la caja este proceso es invisible para el usuario
            $sql = "INSERT INTO caja_aper(Pun_Cod,Caj_Fec,Caj_Hoi,Caj_Est,Caj_Gen)
                    VALUES('$Par_Sql[0]','$Par_Sql[1]',CURTIME(),'C','S')";
            break;

        case 78:
            $sql = "SELECT perio_cont.* FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
            //echo $sql;
            break;

        case 79:
            $sql = "SELECT ventas_det.* , Ite_Lar FROM producto
            INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
            INNER JOIN ventas_det ON ventas_det.Pro_Cod = producto.Pro_Cod
            where Vet_Cod=$Par_Sql[0] order by Vet_Int";
            break;
        case 80:
            $sql = "SELECT * FROM tipo_asien where Tia_Cod=$Par_Sql[0]";
            break;
        case 81:
            $sql = "SELECT Ciu_Cod, Ciu_Des, Pro_Nom  FROM ciudad INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod WHERE Ciu_Des != ''  ORDER BY Ciu_Des ASC";
            //echo $sql;
            break;
        case 82:
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE Tic_Est='A' AND Aut_Est='A' AND Tic_Sri!='7' AND autorizaci.Pun_Cod='$Par_Sql[0]' AND '$Par_Sql[1]' BETWEEN Aut_Fci AND Aut_Cad;";
            //echo $sql;
            break;

        case 83: // usado
            /* Relaciona una venta y un comprobante para saber que es automatico */
            $sql = "INSERT INTO ventas_compr(Com_Cod, Vet_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
            //echo $sql."<br>";
            break;

        case 84: // usado
            /* busca cuenta relacion producto */
            $sql = "SELECT Pro_Cod,produ_plan.Pld_Cod,Tip_Pld,Pld_Cdc,Pld_Des,Pla_Cod FROM produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pro_Cod=$Par_Sql[1] AND (Tip_Pld='$Par_Sql[2]' OR Tip_Pld='I') AND Pla_Cod=$Par_Sql[0]";
            break;

        case 85: // usado
            $sql = "SELECT * FROM vendedor
            INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
            WHERE Suc_Cod=$Par_Sql[0] AND Prs_Cod=$Par_Sql[1]";
            //echo $sql."<br>";
            break;

        case 86: // usado
            $sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[Vet_Cod], Pro_Cod=$Par_Sql[Pro_Cod], Vet_Can=$Par_Sql[Vet_Can],
            Iva_Cod=$Par_Sql[Iva_Cod], Vet_Pru=$Par_Sql[Vet_Pru], Vet_Imp=$Par_Sql[Vet_Imp], Vet_Dec='" . (empty($Par_Sql['Vet_Dec']) ? 0 : $Par_Sql['Vet_Dec']) . "', Nge_Cod = '" . (empty($Par_Sql['Nge_Cod']) ? 0 : $Par_Sql['Nge_Cod']) . "',
            Asi_Int='" . (empty($Par_Sql['Asi_Int']) ? 0 : $Par_Sql['Asi_Int']) . "', Vet_Rec='" . (empty($Par_Sql['Vet_Rec']) ? 0 : $Par_Sql['Vet_Rec']) . "', Cnt_Cod='" . (empty($Par_Sql['Cnt_Cod']) ? 0 : $Par_Sql['Cnt_Cod']) . "', Vet_Int='" . (empty($Par_Sql['Vet_Int']) ? 0 : $Par_Sql['Vet_Int']) . "', Vet_Uni='" . (empty($Par_Sql['Vet_Uni']) || $Par_Sql['Vet_Uni'] * 1 <= 0 ? 1 : $Par_Sql['Vet_Uni']) . "', Ren_Cod=" . (empty($Par_Sql['Ret_Ren_Cod']) ? 'NULL' : "'$Par_Sql[Ret_Ren_Cod]'") . ", Des_Adi=" . (empty($Par_Sql['Des_Adi']) ? 'NULL' : "'$Par_Sql[Des_Adi]'") . ", Ren_Iva=" . (empty($Par_Sql['Iva_Ren_Cod']) ? 'NULL' : "'$Par_Sql[Iva_Ren_Cod]'") . ",Vet_Ite='$Par_Sql[Vet_Ite]', Vet_Ice='" . (empty($Par_Sql['Ice_Por']) ? 0 : $Par_Sql['Ice_Por']) . "', Vet_Cre='" .  (empty($Par_Sql['Vet_Cre']) ? 0 : $Par_Sql['Vet_Cre']) . "', Ime_Cod=" . (empty($Par_Sql['Ime_Cod']) || $Par_Sql['Ime_Cod'] == '0' || $Par_Sql['Ime_Cod'] == 'undefined' ? 'NULL' : "'$Par_Sql[Ime_Cod]'") . "";
            //echo $sql."<br>";
            break;

        case 866: // usado
            $sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[Vet_Cod], Pro_Cod=$Par_Sql[Pro_Cod], Vet_Can=$Par_Sql[Vet_Can],
            Iva_Cod=$Par_Sql[Iva_Cod], Vet_Pru=$Par_Sql[Vet_Pru], Vet_Imp=$Par_Sql[Vet_Imp], Vet_Dec='" . (empty($Par_Sql['Vet_Dec']) ? 0 : $Par_Sql['Vet_Dec']) . "', Nge_Cod = '" . (empty($Par_Sql['Nge_Cod']) ? 0 : $Par_Sql['Nge_Cod']) . "',
            Asi_Int='" . (empty($Par_Sql['Asi_Int']) ? 0 : $Par_Sql['Asi_Int']) . "', Vet_Rec='" . (empty($Par_Sql['Vet_Rec']) ? 0 : $Par_Sql['Vet_Rec']) . "', Cnt_Cod='" . (empty($Par_Sql['Cnt_Cod']) ? 0 : $Par_Sql['Cnt_Cod']) . "', Vet_Int='" . (empty($Par_Sql['Vet_Int']) ? 0 : $Par_Sql['Vet_Int']) . "', Vet_Uni='" . (empty($Par_Sql['Vet_Uni']) || $Par_Sql['Vet_Uni'] * 1 <= 0 ? 1 : $Par_Sql['Vet_Uni']) . "', Ren_Cod=" . (empty($Par_Sql['Ret_Ren_Cod']) ? 'NULL' : "'$Par_Sql[Ret_Ren_Cod]'") . ", Des_Adi=" . (empty($Par_Sql['Des_Adi']) ? 'NULL' : "'$Par_Sql[Des_Adi]'") . ", Ren_Iva=" . (empty($Par_Sql['Iva_Ren_Cod']) ? 'NULL' : "'$Par_Sql[Iva_Ren_Cod]'") . ",Vet_Ite='$Par_Sql[Vet_Ite]', Vet_Ice='" . (empty($Par_Sql['Ice_Por']) ? 0 : $Par_Sql['Ice_Por']) . "', Vet_Cre='" .  (empty($Par_Sql['Vet_Cre']) ? 0 : $Par_Sql['Vet_Cre']) . "', Ime_Cod=" . (empty($Par_Sql['Ime_Cod']) || $Par_Sql['Ime_Cod'] == '0' || $Par_Sql['Ime_Cod'] == 'undefined' ? 'NULL' : "'$Par_Sql[Ime_Cod]'") . "";
            //echo $sql."<br>";
            break;

        case 867: // usado - Actualizar estado de ticket a Facturado (F)
            $Tck_Cod = isset($Par_Sql['Tck_Cod']) ? intval($Par_Sql['Tck_Cod']) : 0;
            $Tck_Tip = isset($Par_Sql['Tck_Tip']) ? addslashes($Par_Sql['Tck_Tip']) : 'F';

            if ($Tck_Cod > 0) {
                $sql = "UPDATE ticket_cantera SET Tck_Tip='$Tck_Tip' WHERE Tck_Cod=$Tck_Cod";
            } else {
                $sql = "";
            }
            break;



        case 87: // usado
            /* inserta asiento */
            $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5];";
            break;


        case 88: // usado

            /* selecciona cuentas iva */
            $sql = "SELECT iva_cobrad.Pld_Cod,CONCAT(Pld_Des,' (',Pld_Cdc,')') AS Pld_Des FROM iva_cobrad
                    INNER JOIN det_plan ON det_plan.Pld_Cod=iva_cobrad.Pld_Cod
                    WHERE Pla_Cod='$Par_Sql[0]'";
            //echo $sql."<br>";

            break;

        case 89: // usado

            /* formas de pago */
            $sql = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Est = 'A' ORDER BY For_Des ASC";
            //echo $sql."<br>";
            break;

        case 90: // usado

            /* cuentas credito */
            $sql = "SELECT ccpp_cliente.Pld_Cod, det_plan.Pld_Des, ccpp_cliente.Cpc_Def, ccpp_cliente.Cpc_Cxc, Cpc_Def AS extra FROM det_plan INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
            //echo $sql."<br>";
            break;


        case 91: //tipo de pago efectivo--credito 
            $sql = "SELECT tipos_pago.For_Cod,Pag_Des, pago_venta.* FROM ventas 
                  inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                  inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                  where ventas.Vet_Cod=$Par_Sql[0] ";
            break;
        case 92:
            $sql = "SELECT pago_venta.Vet_Cod,pago_venta.Bak_Cod,pago_venta.Ban_Cod,pago_venta.Pag_Cod,
                            pago_venta.Vet_Cue, pago_venta.Vet_Che, pago_venta.Vet_Mon, pago_venta.Vet_Cam, pago_venta.Vet_Tot, pago_venta.Vet_Num, Mon_Cod, pago_venta.Vet_Plz,
                            tipos_pago.For_Cod, tipos_pago.Pag_Des,
                            if(pago_venta.Pld_Cod>0,pago_venta.Pld_Cod,(select as2.Pld_Cod
                                                                        from pago_venta pv2
                                                                            inner join tipos_pago tp2 on pv2.Pag_Cod = tp2.Pag_Cod 
                                                                            left join ventas_compr vc2 on pv2.Vet_Cod = vc2.Vet_Cod 
                                                                            left join asientos as2 on vc2.Com_Cod = as2.Com_Cod and as2.Asi_Val=pv2.Vet_Tot  and as2.Asi_Deh='D'
                                                                        where
                                                                        pv2.Vet_Cod=pago_venta.Vet_Cod LIMIT 1)) as Pag_Pld,
                            cheques_ext.Che_Fec as Fec_che,
                            bancos.Bak_Des as Bak_Name,
                            det_plan.Pld_Des as Ban_Name
                    FROM pago_venta
                        inner join tipos_pago on pago_venta.Pag_Cod = tipos_pago.Pag_Cod 
                        left join ventas_compr on pago_venta.Vet_Cod = ventas_compr.Vet_Cod
                        left join cheq_det_ventas on pago_venta.Vet_Cod = cheq_det_ventas.Vet_Cod
                        left join cheques_ext on cheq_det_ventas.Che_Cod = cheques_ext.Che_Cod
                        left join bancos on pago_venta.Bak_Cod = bancos.Bak_Cod
                        left join banco on pago_venta.Ban_Cod = banco.Ban_Cod
                        left join det_plan on banco.Pld_Cod = det_plan.Pld_Cod
                    WHERE 
                        pago_venta.Vet_Cod=$Par_Sql[0]";
            break;
        case 93:
            $sql = "SELECT ventas_det.* , Ite_Lar, 
                            renta.Ren_Sri as Ret_Ren_Sri, 
                            renta.Ren_Cod as Ret_Ren_Cod,
                            renta.Ren_Por as Ret_Ren_Por,
                            renta.Ren_Con as Ret_Ren_Con,
                            renta.Ren_Tip as Ret_Ren_Tip,
                            renta.Ren_Ret as Ret_Ren_Ret,
                            renta.Ren_Est as Ret_Ren_Est,
                            iva_imp.Ren_Sri as Iva_Ren_Sri, 
                            iva_imp.Ren_Cod as Iva_Ren_Cod,
                            iva_imp.Ren_Por as Iva_Ren_Por,
                            iva_imp.Ren_Con as Iva_Ren_Con,
                            iva_imp.Ren_Tip as Iva_Ren_Tip,
                            iva_imp.Ren_Ret as Iva_Ren_Ret,
                            iva_imp.Ren_Est as Iva_Ren_Est,
                            ivas.Iva_Por as Iva_Por,
                            ivas.Iva_Cod as Iva_Cod,
                            ivas.Iva_Sri as Iva_Sri,
                            det_plan.Pld_Cdc,
                            det_plan.Pld_Des,
                            ice.*,
                            unidad.*,
                            adquisicio.*,
                            imei.Ime_Cod,
                            imei.Ime_Num,
                            imei.Ime_Tip
                    FROM producto
                        left join adquisicio on adquisicio.Adq_Cod = producto.Adq_Cod
                        INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
                        INNER JOIN ventas_det ON ventas_det.Pro_Cod = producto.Pro_Cod
                        LEFT join renta_iva as renta on renta.Ren_Cod= ventas_det.Ren_Cod 
                        LEFT join renta_iva as iva_imp on iva_imp.Ren_Cod = ventas_det.Ren_Iva
                        left join iva as ivas  on ivas.Iva_Cod = ventas_det.Iva_Cod
                        left join ice on ice.Ice_Int = producto.Ice_Int
                        left join unidad on unidad.Uni_Cod = producto.Uni_Cod
                        left join produ_plan on produ_plan.Pro_Cod = producto.Pro_Cod and (Tip_Pld='V' OR Tip_Pld='I') 
                        left join det_plan on produ_plan.Pld_Cod = det_plan.Pld_Cod
                        LEFT JOIN imei on imei.Ime_Cod = ventas_det.Ime_Cod
                    WHERE ventas_det.Vet_Cod=$Par_Sql[0]
                    ORDER BY Vet_Ite, ventas_det.Ime_Cod";
            break;

        case 94: //Update sobre la tabla venta

            $sql = "update ventas set Tic_Cod=$Par_Sql[0], Cli_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], Caj_Cod=" . (empty($Par_Sql[3]) ? 'NULL' : $Par_Sql[3]) . ", Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Vet_Obs=" . (empty($Par_Sql[6]) ? 'NULL' : "'$Par_Sql[6]'") . ", Aut_Cod=" . (empty($Par_Sql[7]) ? 'NULL' : "'$Par_Sql[7]'") . ", Vet_Des=$Par_Sql[8], Vet_Hor='$Par_Sql[9]',Vet_Xml=" . (empty($Par_Sql[10]) ? 'NULL' : "'$Par_Sql[10]'") . ",Vet_Aut=" . (empty($Par_Sql[11]) ? 'NULL' : "'$Par_Sql[11]'") . ",Ret_Num=" . (empty($Par_Sql[12]) ? 'NULL' : "'$Par_Sql[12]'") . ",
                    Ret_Fec=" . (empty($Par_Sql[13]) ? 'NULL' : "'$Par_Sql[13]'") . ",Ret_Aut=" . (empty($Par_Sql[14]) ? 'NULL' : "'$Par_Sql[14]'") . ",Tpc_Cod=" . (empty($Par_Sql[15]) ? 'NULL' : "$Par_Sql[15]") . " where Vet_Cod=$Par_Sql[16]";
            break;
        case 95: // Delete pagos de la venta
            $sql = "Delete from pago_venta where Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;

        case 96:
            $sql = "DELETE  FROM ccpp_cobrar WHERE Cpc_Cod=$Par_Sql[1] AND Vet_Cod=$Par_Sql[0]";
            break;

        case 966:
            $sql = "UPDATE det_ccpp_c
                    INNER JOIN tipos_pago ON det_ccpp_c.Pag_Cod = tipos_pago.Pag_Cod
                    INNER JOIN comprobantes ON det_ccpp_c.Com_Cod = comprobantes.Com_Cod
                    SET det_ccpp_c.Cpc_Est = 'I', 
                    comprobantes.Com_Est = 'I'
                    WHERE 
                    det_ccpp_c.Cpc_Cod = $Par_Sql[0]
                    AND tipos_pago.Pag_Des = 'Retencion'
                    AND tipos_pago.Pag_Abr = 'RET'";
            break;

        case 97:
            $sql = "DELETE  FROM ventas_det WHERE Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 98:
            $sql = "DELETE  FROM comprobantes where Com_Cod=$Par_Sql[0]";
            break;
        case 99:
            $sql = "DELETE FROM ventas_compr WHERE Com_Cod=$Par_Sql[0] and Vet_Cod=$Par_Sql[1]";
            break;
        case 100:
            if ($Par_Sql[2] == "") $campos = "COUNT(autorizaci.Aut_Cod) as total";
            else {
                $campos = "IF(Aut_Tem='N',Aut_Sri,'Electronica')as AutSri,autorizaci.* , IF(autorizaci.Aut_Est='A','S','N') as Aut_Estado,tipo_compr.*,Suc_Sri,Ext_Nom";
            }
            $where = "";
            if ($Par_Sql[1] != "") {
                $where = " and tipo_compr.Tic_Cod=$Par_Sql[1]";
            }
            $sql = "SELECT $campos FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    LEFT JOIN rutas_fact_extra ON autorizaci.Ext_Cod=rutas_fact_extra.Ext_Cod
                    WHERE (Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' 
                    OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' 
                    OR Tic_Sri='51' OR Tic_Sri='52') AND Tic_Est='A' AND autorizaci.Pun_Cod=$Par_Sql[0] $where $Par_Sql[2]";
            break;
        case 101:
            $where_doc = "Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
            if (isset($Par_Sql[3])) {
                $where_doc = "Tic_Sri='4' OR Tic_Sri='5'";
            }
            $where = "";
            if (($Par_Sql[1]) != 0) {
                $where = " AND autorizaci.Aut_Cod<>'$Par_Sql[1]' and tipo_compr.Tic_Cod<>'$Par_Sql[2]'";
            }
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE ($where_doc) AND autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A' $where ";
            break;

        case 102: //Update sobre la tabla venta

            $sql = "update ventas set Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Aut_Cod=" . (empty($Par_Sql[7]) ? 'NULL' : "'$Par_Sql[7]'") . ",Ret_Num=" . (empty($Par_Sql[12]) ? 'NULL' : "'$Par_Sql[12]'") . ",
                    Ret_Fec=" . (empty($Par_Sql[13]) ? 'NULL' : "'$Par_Sql[13]'") . ",Ret_Aut=" . (empty($Par_Sql[14]) ? 'NULL' : "'$Par_Sql[14]'") . " where Vet_Cod=$Par_Sql[16]";
            break;
        case 103:
            $sql = "SELECT det_plan.Pld_Des, det_plan.Pld_Cdc, reniva_pla.Pld_Cod, reniva_pla.Ren_Cod FROM det_plan
                INNER JOIN reniva_pla ON (det_plan.Pld_Cod = reniva_pla.Pld_Cod)
                INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                INNER JOIN renta_iva ON (renta_iva.Ren_Cod = reniva_pla.Ren_Cod)
                WHERE renta_iva.Ren_Sri='$Par_Sql[1]' AND renta_iva.Ren_Por=1 AND reniva_pla.Ren_Tip='$Par_Sql[2]' AND plan_cuenta.Pla_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';

            break;
        case 104:
            $sql = "select Pro_Cod from producto
                  left join item on producto.Ite_Cod = item.Ite_Cod 
                  left join categorias on categorias.Cat_Cod = item.Cat_Cod
                  where categorias.Emp_Cod=$Par_Sql[0] and Pro_Est='A' limit 1";
            break;
        case 105: //Select para cargar perido segun fecha
            $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio,perio_cont.Pla_Cod
                    FROM perio_cont
                    LEFT JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND '$Par_Sql[0]' between Pec_Fei and Pec_Fef AND Pec_Est='A' ORDER BY Pec_Fei DESC";
            break;
        case 106: //Select consumidor final
            $sql = "SELECT Cli_Cod, Prs_Ape from cliente
                    inner join persona on cliente.Prs_Cod = persona.Prs_Cod
                    where cliente.Emp_Cod=$Par_Sql[0] and Prs_Ced=9999999999999";
            break;
        case 107: //Select para listar los tipos de comprobantes de Tic_Sr1=0,1,2,41,44,47,48,49,50,51,52
            if (isset($Par_Sql[1])) {
                $where = "autorizaci.Aut_Cod='$Par_Sql[1]'";
            } else {
                $where = "autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A'";
            }

            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='4' OR Tic_Sri='5') AND $where ";
            break;
        case 108:
            if ($Par_Sql[2] == "") $campos = "COUNT(autorizaci.Aut_Cod) as total";
            else {
                $campos = "autorizaci.* , IF(autorizaci.Aut_Est='A','S','N') as Aut_Estado,tipo_compr.*,Suc_Sri";
            }
            $where = "";
            if ($Par_Sql[1] != "") {
                $where = " and tipo_compr.Tic_Cod=$Par_Sql[1]";
            }
            $sql = "SELECT $campos FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='4' OR Tic_Sri='5') AND Tic_Est='A' AND autorizaci.Pun_Cod=$Par_Sql[0] $where $Par_Sql[2]";
            break;
        case 109:
            if ($Par_Sql[4] == "") {
                $campos = "count(ventas.Vet_Cod) as total";
            } else {
                $campos = " ventas.*, sucursal.Suc_Sri,autorizaci.Pun_Sri,ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, 
                            tipo_compr.Tic_Des , tipos_pago.Pag_Des , forma_pago.For_Cod,forma_pago.For_Des,
                            if(forma_pago.For_Cod = 2, 
                            (select det_plan.Pld_Cod from comprobantes 
                                inner join asientos on comprobantes.Com_Cod = asientos.Com_Cod 
                                inner join det_plan on asientos.Pld_Cod = det_plan.Pld_Cod 
                                inner join ccpp_cliente on det_plan.Pld_Cod = ccpp_cliente.Pld_Cod 
                            where comprobantes.Com_Cod = ccpp_cobrar.Com_Cod), COALESCE(pago_venta.Pld_Cod,banco.Pld_Cod) 
                        )as Pld_Cod";
            }
            $where = "";
            if ($Par_Sql[2] != "") {
                $where = $where . "AND forma_pago.For_Cod=$Par_Sql[2]";
            }

            if ($Par_Sql[3] != "") {
                $where = $where . " and ventas.Vet_Num=$Par_Sql[3] ";
            }
            $sql = "SELECT $campos FROM ventas
                inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                inner join puntos_imp on caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                inner join cliente on cliente.Cli_Cod = ventas.Cli_Cod 
                inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                left join banco on pago_venta.Ban_Cod = banco.Ban_Cod
                left join ventas_compr on ventas.Vet_Cod = ventas_compr.Vet_Cod
                inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                inner join forma_pago on forma_pago.For_Cod = tipos_pago.For_Cod
                inner join persona on persona.Prs_Cod= cliente.Prs_Cod
                left join ccpp_cobrar on ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
                inner join autorizaci on autorizaci.Aut_Cod = ventas.Aut_Cod
                inner join sucursal on sucursal.Suc_Cod = puntos_imp.Suc_Cod
                inner join tipo_compr on tipo_compr.Tic_Cod = ventas.Tic_Cod 
                where  
                puntos_imp.Suc_Cod=$Par_Sql[1] and ventas.Vet_Est ='A' and tipo_compr.Tic_Sri in (1, 2) $where $Par_Sql[4]";
            break;
        case 110:
            $sql = "select iva.* from iva where '2017-06-12' BETWEEN iva.Iva_Ini and Iva_Fin limit 1 ";
            break;
        case 111: //Insert sobre la tabla venta
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Est)
                    VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]',
                       '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]',
                        " . (empty($Par_Sql[10]) ? 'NULL' : "'$Par_Sql[10]'") . ",
                        " . (empty($Par_Sql[11]) ? 'NULL' : "'$Par_Sql[11]'") . ",
                        " . (!empty($Par_Sql[12]) ? "'$Par_Sql[12]'" : "NULL") . ",
                        " . (!empty($Par_Sql[13]) ? "'$Par_Sql[13]'" : "NULL") . ",
                        " . (!empty($Par_Sql[14]) ? "'$Par_Sql[14]'" : "NULL") . ",
                        " . (!empty($Par_Sql[15]) ? "'$Par_Sql[15]'" : "NULL") . ",'$Par_Sql[16]')";
            break;
        /* case 112:
            $sql = "select sum(comprobantes.Com_Val) as Vet_Total
                from ventas
                INNER JOIN ventas_compr ON ventas.Vet_Cod = ventas_compr.Vet_Cod
                INNER JOIN comprobantes ON ventas_compr.Com_Cod = comprobantes.Com_Cod
                where ventas.Vet_Cod = '$Par_Sql[0]' ";
            break;*/

        case 112: //ESTO PERMITE USAR NOTAS DE CREDITO SIN TENER PLAN DE CUENTAS
            $sql = "select sum(comprobantes.Com_Val) as Vet_Total, sum(pago_venta.Vet_Tot) as Vet_Tot_Sin_Compr
                    from ventas
                    LEFT JOIN ventas_compr ON ventas.Vet_Cod = ventas_compr.Vet_Cod
                    INNER JOIN pago_venta ON ventas.Vet_Cod = pago_venta.Vet_Cod
                    LEFT JOIN comprobantes ON ventas_compr.Com_Cod = comprobantes.Com_Cod
                    WHERE ventas.Vet_Cod ='$Par_Sql[0]' ";
            break;




        case 113:
            $sql = "insert into det_ccpp_c (Com_Cod,Pag_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,Cpc_Cod)
                values ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]',$Par_Sql[3],$Par_Sql[4],$Par_Sql[5])";
            break;

        case 1133:
            $sql = "INSERT INTO det_ccpp_c (Com_Cod,Pag_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,Cpc_Cod)
                values ($Par_Sql[Com_Cod],$Par_Sql[Pag_Cod],'$Par_Sql[Cpc_Fec]',$Par_Sql[Cpc_Val],'$Par_Sql[Cpc_Obs]',$Par_Sql[Cpc_Cod])";
            break;

        case 114:
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
                    Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,
                    Vet_Xml,Vet_Aut,Vet_Sri,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Nns,Vet_Ntd,Vet_Fdm)
                    VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]',
                        '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]',
                        " . (empty($Par_Sql[10]) ? 'NULL' : "'$Par_Sql[10]'") . ",
                        " . (empty($Par_Sql[11]) ? 'NULL' : "'$Par_Sql[11]'") . ",
                        " . (!empty($Par_Sql[12]) ? "'$Par_Sql[12]'" : "NULL") . ",
                        " . (!empty($Par_Sql[13]) ? "'$Par_Sql[13]'" : "NULL") . ",
                        " . (!empty($Par_Sql[14]) ? "'$Par_Sql[14]'" : "NULL") . ",
                        " . (!empty($Par_Sql[15]) ? "'$Par_Sql[15]'" : "NULL") . ",
                        " . (!empty($Par_Sql[16]) ? "'$Par_Sql[16]'" : "NULL") . ",
                        " . (!empty($Par_Sql[17]) ? "'$Par_Sql[17]'" : "NULL") . ", 
                        " . (!empty($Par_Sql[18]) ? "'$Par_Sql[18]'" : "NULL") . ", 
                        " . (!empty($Par_Sql[19]) ? "'$Par_Sql[19]'" : "NULL") . ")";
            break;
        /*  case 115:
            $where = (empty($Par_Sql[1])?'':'and det_ccpp_c.Com_Cod<>'.$Par_Sql[1]);
            $sql= "SELECT COALESCE(SUM(det_ccpp_c.Cpc_Val),0) AS Vet_Abonos 
                FROM ventas
                INNER join ventas_compr on ventas_compr.Vet_Cod = ventas.Vet_Cod
                INNER join ccpp_cobrar on ccpp_cobrar.Com_Cod = ventas_compr.Com_Cod
                INNER join det_ccpp_c on det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod and Cpc_Est='A'
                INNER join comprobantes on comprobantes.Com_Cod = det_ccpp_c.Com_Cod and Com_Est='A'
                where ventas.Vet_Cod = '$Par_Sql[0]' $where ";
            break;*/


        case 115: // Esto permite usar las notas de credito sin usar PLAN DE CUENTAS
            $where = (empty($Par_Sql[1]) ? '' : 'and det_ccpp_c.Com_Cod<>' . $Par_Sql[1]);
            $sql = "SELECT  COALESCE(SUM(det_ccpp_c.Cpc_Val), 0) AS Vet_Abonos
                FROM ventas
                LEFT JOIN ventas_compr ON ventas_compr.Vet_Cod = ventas.Vet_Cod
                LEFT JOIN ccpp_cobrar cc1 ON cc1.Com_Cod = ventas_compr.Com_Cod
                LEFT JOIN ccpp_cobrar cc2 ON cc2.Vet_Cod = ventas_compr.Vet_Cod
                INNER JOIN  det_ccpp_c ON det_ccpp_c.Cpc_Cod = cc2.Cpc_Cod AND det_ccpp_c.Cpc_Est = 'A'
                LEFT JOIN comprobantes ON comprobantes.Com_Cod = det_ccpp_c.Com_Cod 
                
                WHERE  ventas.Vet_Cod = '$Par_Sql[0]' $where 
                AND comprobantes.Com_Est = 'A' ";
            break;


        case 116:
            $where = "";
            if (($Par_Sql[1]) != 0) {
                $where = " AND autorizaci.Aut_Cod<>'$Par_Sql[1]' and tipo_compr.Tic_Cod<>'$Par_Sql[2]'";
            }
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='5' OR Tic_Sri='4') AND autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A' $where ";
            break;
        case 117:
            $sql  = "select ventas.*,sucursal.Suc_Sri,autorizaci.Pun_Sri,
                    ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec, 
                    CONCAT(Prs_Ape,' ',Prs_Nom) as cliente,
                    tipo_compr.Tic_Des , tipos_pago.Pag_Des ,
                    forma_pago.For_Cod,forma_pago.For_Des from ventas 
                    inner join ventas_compr on ventas.Vet_Cod = ventas_compr.Vet_Cod
                    inner join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod
                    inner join ccpp_cobrar on ccpp_cobrar.Com_Cod = comprobantes.Com_Cod
                    inner join det_ccpp_c on det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                    inner join puntos_imp on caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                    inner join cliente on cliente.Cli_Cod = ventas.Cli_Cod
                    inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                    inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                    inner join forma_pago on forma_pago.For_Cod = tipos_pago.For_Cod
                    inner join persona on persona.Prs_Cod= cliente.Prs_Cod
                    inner join autorizaci on autorizaci.Aut_Cod = ventas.Aut_Cod
                    inner join sucursal on sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    inner join tipo_compr on tipo_compr.Tic_Cod = ventas.Tic_Cod
                    where det_ccpp_c.Com_Cod='$Par_Sql[0]' and ventas.Vet_Num<>'$Par_Sql[1]'";
            break;
        case 118:
            $sql = "select ventas.* from ventas 
                    inner join ventas_compr on ventas_compr.Vet_Cod = ventas.Vet_Cod
                    inner join comprobantes on ventas_compr.Com_Cod = comprobantes.Com_Cod
                    where ventas.Vet_Cod='$Par_Sql[0]'";
            break;
        /*case 119:
            $sql =  "update ventas set Tic_Cod=$Par_Sql[0], Cli_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], "
                . "Caj_Cod=".(empty($Par_Sql[3])?'NULL':$Par_Sql[3]).", Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Vet_Obs=".(empty($Par_Sql[6])?'NULL':"'$Par_Sql[6]'").",
                    Aut_Cod=".(empty($Par_Sql[7])?'NULL':"'$Par_Sql[7]'").", Vet_Des=$Par_Sql[8],
                    Vet_Hor='$Par_Sql[9]', Vet_Nns='$Par_Sql[10]',Vet_Ntd='$Par_Sql[11]',Vet_Fdm='$Par_Sql[12]'
                    where Vet_Cod=$Par_Sql[13]";
            break;*/
        case 119:
            $sql =  "update ventas set Tic_Cod=$Par_Sql[0], Cli_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], "
                . "Caj_Cod=" . (empty($Par_Sql[3]) ? 'NULL' : $Par_Sql[3]) . ", Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Vet_Obs=" . (empty($Par_Sql[6]) ? 'NULL' : "'$Par_Sql[6]'") . ",
                    Aut_Cod=" . (empty($Par_Sql[7]) ? 'NULL' : "'$Par_Sql[7]'") . ", Vet_Des=$Par_Sql[8],
                    Vet_Hor='$Par_Sql[9]',Vet_Xml='$Par_Sql[10]', Vet_Nns='$Par_Sql[11]',Vet_Ntd='$Par_Sql[12]',Vet_Fdm='$Par_Sql[13]'
                    where Vet_Cod=$Par_Sql[14]";
            break;
        case 120:
            $sql = "DELETE FROM det_ccpp_c WHERE Com_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 121:
            $sql  = "select ventas.*,sucursal.Suc_Sri,autorizaci.Pun_Sri,
                    ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec, 
                    CONCAT(Prs_Ape,' ',Prs_Nom) as cliente,
                    tipo_compr.Tic_Des , tipos_pago.Pag_Des ,
                    forma_pago.For_Cod,forma_pago.For_Des from ventas 
                    left join ventas_compr on ventas.Vet_Cod = ventas_compr.Vet_Cod
                    left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod
                    left join ccpp_cobrar on ccpp_cobrar.Com_Cod = comprobantes.Com_Cod
                    left join det_ccpp_c on det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                    inner join puntos_imp on caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                    inner join cliente on cliente.Cli_Cod = ventas.Cli_Cod
                    inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                    inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                    inner join forma_pago on forma_pago.For_Cod = tipos_pago.For_Cod
                    inner join persona on persona.Prs_Cod= cliente.Prs_Cod
                    inner join autorizaci on autorizaci.Aut_Cod = ventas.Aut_Cod
                    inner join sucursal on sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    inner join tipo_compr on tipo_compr.Tic_Cod = ventas.Tic_Cod
                    where ventas.Vet_Num='$Par_Sql[0]' and ventas.Tic_Cod='$Par_Sql[1]' and sucursal.Suc_Sri='$Par_Sql[2]' and autorizaci.Pun_Sri='$Par_Sql[3]' AND sucursal.Emp_Cod=$_SESSION[Ses_Emp_Cod] Limit 1";
            break;
        case 122:
            $si_existe = "renta_iva.Ren_Est='A'";
            if (isset($Par_Sql['Ren_Por']))
                $si_existe = "renta_iva.Ren_Por=$Par_Sql[Ren_Por]";
            //renta de Referencia para NUEVA RENTA
            $sql = "select * from renta_iva 
                    left join reniva_pla on reniva_pla.Ren_Cod = renta_iva.Ren_Cod
                    left join det_plan on det_plan.Pld_Cod =reniva_pla.Pld_Cod AND det_plan.Pla_Cod='$Par_Sql[Pla_Cod]' 
                    where renta_iva.Ren_Sri='$Par_Sql[Ren_Sri]' and $si_existe limit 1";
            break;
        case 123:
            $sql = "INSERT INTO renta_iva(Ren_Sri,Ren_Con,Ren_Por,Ren_Ini,Ren_Fin,Ren_Ing,Ren_Tip,Ren_Ret,Ren_Est,Adq_Cod) VALUES('$Par_Sql[Ren_Sri]','$Par_Sql[Ren_Con]','$Par_Sql[Ren_Por]','$Par_Sql[Ren_Ini]','$Par_Sql[Ren_Fin]','$Par_Sql[Ren_Ing]','$Par_Sql[Ren_Tip]','$Par_Sql[Ren_Ret]','$Par_Sql[Ren_Est]','$Par_Sql[Adq_Cod]');";
            break;
        /* niebla */

        case 124:
            /* Consulta del vendedor en base al codigo de la persona */
            $sql = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
            //echo $sql;            
            break;
        case 125:
            /*Consulta informacion de la empresa */
            $sql = "SELECT 
                              empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
                              sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv,confi_fact.Cof_Con 
                            FROM
                              empresas
                          INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                              INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                       WHERE
                          sucursal.Suc_Cod=$Par_Sql[0]";
            //echo ".$sql."<br>";            
            break;
        case 126:
            $fields = (empty($Par_Sql['limits'])) ? "COUNT(ventas.Vet_Cod)AS total" : "ventas.Vet_Cod,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Fac_Num, ventas.Vet_Num, ventas.Ret_Num, caja_aper.Caj_Fec,CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est, SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Tot, SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva, SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced";
            $sql = "SELECT  $fields
                            FROM ventas "
                . (empty($Par_Sql['limits']) ? '' : "INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod) 
                            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) ") . "
                            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
                            INNER JOIN sucursal ON (sucursal.Suc_Cod=puntos_imp.Suc_Cod)
                            INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)                             
                            INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
                        INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                    WHERE (Caj_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59') 
                            AND ventas.Vet_Est = 'A' 
                        AND ventas.Tic_Cod = $Par_Sql[Tic_Cod] 
                        AND puntos_imp.Suc_Cod =  $Par_Sql[Suc_Cod] " .
                ($Par_Sql['Cli_Cod'] != '' ? ' AND cliente.Cli_Cod=' . $Par_Sql['Cli_Cod'] : '')
                . (empty($Par_Sql['limits']) ? '' : " GROUP BY ventas.Vet_Cod $Par_Sql[limits] ");
            //echo $sql.'<br>';
            break;
        case 127:
            /*  Consulta la ciudad en base al usuario */
            $sql = "SELECT sucursal.Ciu_Cod, ciudad.Ciu_Des FROM usuarios, sucursal, ciudad 
                    WHERE usuarios.Suc_Cod = sucursal.Suc_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod AND usuarios.Usu_Cod = '$Par_Sql[0]'";
            break;
        case 128:
            $sql = "SELECT det_plan.Pla_Cod,banco.Ban_Cod,banco.Pld_Cod,CONCAT(Pld_Des,' (',Ban_Cue,')') AS Ban_Des FROM banco
                        INNER JOIN pago_plan ON pago_plan.Ban_Cod=banco.Ban_Cod
                        LEFT JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        LEFT JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                        LEFT JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                        WHERE Pag_Cod=$Par_Sql[0] AND perio_cont.Pec_Cod=$Par_Sql[1] AND Emp_Cod='$Par_Sql[2]' AND Ban_Est='A'";
            //echo $sql.'<br>';
            break;
        case 129:
            $sql = "SELECT ventas_det.Pro_Cod,Pro_Obs,producto.Adq_Cod,Adq_Cor,SUM(Vet_Can) AS Vet_Can,SUM(Vet_Pru)/COUNT(Vet_Num) AS Vet_Pru,SUM(Vet_Can)*SUM(Vet_Pru)/COUNT(Vet_Num) AS Importe,Iva_Por,Ite_Lar,iva.Iva_Cod
                    FROM ventas_det
                    INNER JOIN ventas ON ventas.Vet_Cod=ventas_det.Vet_Cod
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
                    INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                    WHERE $Par_Sql[0] 
                    GROUP BY ventas_det.Pro_Cod";
            //echo $sql.'<br>';
            break;
        case 130:

            //echo $sql;            
            break;
        case 131:
            /* Buscamos numero de venta segun el numero de autorizacion */
            $sql = "SELECT 
                              ventas.Vet_Cod,autorizaci.Aut_Cod,ventas.Vet_Num
                      FROM
                              autorizaci
                              INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
                      WHERE
                              autorizaci.Aut_Sri='$Par_Sql[0]' AND ventas.Vet_Num='$Par_Sql[1]'";
            //echo $sql;
            break;
        case 132:
            $sql = "UPDATE ventas SET Vet_Est='U' WHERE $Par_Sql[0]";
            //echo $sql.'<br>';
            break;
        case 133:
            /*Consulta cargar la autorizacion activa de acuerdo a un tipo de comprobante*/
            $sql = "SELECT autorizaci.Aut_Cod, autorizaci.Tic_Cod, Tic_Sri, autorizaci.Pun_Sri, autorizaci.Pun_Cod, autorizaci.Aut_Cad,Tic_Des, autorizaci.Aut_Sri, autorizaci.Aut_Ini, autorizaci.Aut_Fin, autorizaci.Aut_Adv, autorizaci.Aut_Ads FROM autorizaci,tipo_compr WHERE tipo_compr.Tic_Cod=autorizaci.Tic_Cod AND autorizaci.Tic_Cod = $Par_Sql[0]  
                    AND autorizaci.Pun_Cod = '$Par_Sql[1]'
                    AND autorizaci.Aut_Cad >= '$Par_Sql[2]' AND autorizaci.Aut_Est = 'A'";

            break;
        case 134:
            $sql = "SELECT Tic_Cod, Tic_Des, Tic_Sri FROM tipo_compr WHERE Tic_Est='A'";
            break;


        // fecha de modificacion 02/04/2018

        case 135:
            $sql = "SELECT ventas_det.Pro_Cod,Pro_Obs,producto.Adq_Cod,Adq_Cor,SUM(Vet_Can) AS Vet_Can, SUM(Vet_Imp)/SUM(Vet_Can) AS Vet_Pru ,SUM(Vet_Imp) AS Importe,Iva_Por,Ite_Lar,iva.Iva_Cod,
                    IF(Iva_Por=0,'S','N')AS Iva,CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )AS Producto 
                    FROM ventas_det
                    INNER JOIN ventas ON ventas.Vet_Cod=ventas_det.Vet_Cod
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
                    INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                    WHERE $Par_Sql[0] 
                    GROUP BY ventas_det.Pro_Cod";
            //echo $sql.'<br>';
            break;
        case 136:
            $sql = "SELECT * FROM stock_nd WHERE Pro_Cod='$Par_Sql[0]'";
            //echo $sql.'<br>';
            break;
        case 137:
            $sql = "INSERT INTO stock_nd (Pro_Cod,Pro_Can,Pro_Imp)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2]) ";
            //echo $sql.'<br>';
            break;
        case 138:
            $sql = "UPDATE stock_nd SET Pro_Can=$Par_Sql[1], Pro_Imp=$Par_Sql[2] WHERE Pro_Cod='$Par_Sql[0]';";
            //echo $sql.'<br>';
            break;
        case 139:
            /*Consulta cargar la autorizacion activa de acuerdo a un tipo de comprobante*/
            $sql = "SELECT autorizaci.Aut_Cod, autorizaci.Tic_Cod, Tic_Sri, autorizaci.Pun_Sri, autorizaci.Pun_Cod, autorizaci.Aut_Cad,Tic_Des, autorizaci.Aut_Sri, autorizaci.Aut_Ini, autorizaci.Aut_Fin, autorizaci.Aut_Adv, autorizaci.Aut_Ads FROM autorizaci,tipo_compr WHERE tipo_compr.Tic_Cod=autorizaci.Tic_Cod AND autorizaci.Tic_Cod = $Par_Sql[0]  
                    AND autorizaci.Pun_Cod = '$Par_Sql[1]'
                    AND autorizaci.Aut_Cad >= '$Par_Sql[2]' AND autorizaci.Aut_Est = 'A'";
            //echo $sql.'<br/>';
            break;



        // fecha de modificacion 22/03/2018

        // case 140:
        case 140: //Insert sobre la tabla venta
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Sri,Vnd_Cod_Aux, Vet_Prop,Vet_Ide,Veh_Cod)
                    VALUES ($Par_Sql[Tic_Cod], $Par_Sql[Cli_Cod], $Par_Sql[Ciu_Cod], $Par_Sql[Caj_Cod], $Par_Sql[Vnd_Cod], '$Par_Sql[Vet_Num]','$Par_Sql[Vet_Obs]', $Par_Sql[Aut_Cod], '$Par_Sql[Vet_Des]', '$Par_Sql[Vet_Hor]',
                        " . (empty($Par_Sql['Vet_Xml']) ? 'NULL' : "'$Par_Sql[Vet_Xml]'") . ",
                        " . (empty($Par_Sql['Vet_Aut']) ? 'NULL' : "'$Par_Sql[Vet_Aut]'") . ",
                        " . (!empty($Par_Sql['Ret_Num']) ? "'$Par_Sql[Ret_Num]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Ret_Fec']) ? "'$Par_Sql[Ret_Fec]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Ret_Aut']) ? "'$Par_Sql[Ret_Aut]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Tpc_Cod']) ? "'$Par_Sql[Tpc_Cod]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Vet_Sri']) ? "$Par_Sql[Vet_Sri]" : "NULL") . ",
                        " . (!empty($Par_Sql['Vnd_Cod_Aux']) ? "$Par_Sql[Vnd_Cod_Aux]" : "NULL") . ",
                         " . (!empty($Par_Sql['Vet_Prop']) ? "'$Par_Sql[Vet_Prop]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Vet_Ide']) ? "'$Par_Sql[Vet_Ide]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Veh_Cod']) ? "'$Par_Sql[Veh_Cod]'" : "NULL") . "    
                        )";

            break;
        case 141: //Update sobre la tabla venta   
            $sql = "update ventas set Tic_Cod=$Par_Sql[Tic_Cod], Cli_Cod=$Par_Sql[Cli_Cod], Ciu_Cod=$Par_Sql[Ciu_Cod], Caj_Cod=" . (empty($Par_Sql['Caj_Cod']) ? 'NULL' : $Par_Sql['Caj_Cod']) . ", Vnd_Cod=$Par_Sql[Vnd_Cod],
                    Vet_Num=$Par_Sql[Vet_Num], Vet_Obs=" . (empty($Par_Sql['Vet_Obs']) ? 'NULL' : "'$Par_Sql[Vet_Obs]'") . ", 
                    Aut_Cod=" . (empty($Par_Sql['Aut_Cod']) ? 'NULL' : "'$Par_Sql[Aut_Cod]'") . ", 
                    Vet_Des=$Par_Sql[Vet_Des], 
                    Vet_Hor='$Par_Sql[Vet_Hor]',Vet_Xml=" . (empty($Par_Sql['Vet_Xml']) ? 'NULL' : "'$Par_Sql[Vet_Xml]'") . ",
                    Vet_Aut=" . (empty($Par_Sql['Vet_Aut']) ? 'NULL' : "'$Par_Sql[Vet_Aut]'") . ",
                    Ret_Num=" . (empty($Par_Sql['Ret_Num']) ? 'NULL' : "'$Par_Sql[Ret_Num]'") . ",
                    Ret_Fec=" . (empty($Par_Sql['Ret_Fec']) ? 'NULL' : "'$Par_Sql[Ret_Fec]'") . ",
                    Ret_Aut=" . (empty($Par_Sql['Ret_Aut']) ? 'NULL' : "'$Par_Sql[Ret_Aut]'") . ",
                    Tpc_Cod=" . (empty($Par_Sql['Tpc_Cod']) ? 'NULL' : "$Par_Sql[Tpc_Cod]") . ",
                    Vet_Sri=" . (empty($Par_Sql['Vet_Sri']) ? 'NULL' : "$Par_Sql[Vet_Sri]") . ", 
                    Vnd_Cod_Aux=" . (empty($Par_Sql['Vnd_Cod_Aux']) ? 'NULL' : "$Par_Sql[Vnd_Cod_Aux]") . ",
                    Vet_Prop=" . (empty($Par_Sql['Vet_Prop']) ? 'NULL' : "'$Par_Sql[Vet_Prop]'") . ",
                    Vet_Ide=" . (empty($Par_Sql['Vet_Ide']) ? 'NULL' : "'$Par_Sql[Vet_Ide]'") . ",
                    Veh_Cod=" . (empty($Par_Sql['Veh_Cod']) ? 'NULL' : "'$Par_Sql[Veh_Cod]'") . "
                    where Vet_Cod=$Par_Sql[Vet_Cod]";
            break;

        // fecha de modificacion 02/04/2018 
        case 142:
            /*Consulta cargar la autorizacion activa de acuerdo a un tipo de comprobante*/
            $sql = "SELECT stock_nd.*,(stock_nd.Pro_Imp/stock_nd.Pro_Can) AS Pro_Uni,CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) ) AS Producto,producto.Iva_Cod,Iva_Por FROM stock_nd 
                        INNER JOIN producto ON producto.Pro_Cod=stock_nd.Pro_Cod
                        INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON categorias.Cat_Cod=item.Cat_Cod
                        WHERE stock_nd.Pro_Imp>0 AND stock_nd.Pro_Can>0 AND Emp_Cod='$_SESSION[Ses_Emp_Cod]'
                        ORDER BY stock_nd.Pro_Imp DESC, stock_nd.Pro_Can DESC
                            ;";
            //echo $sql.'<br/>';
            break;

        case 143:
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(ventas.Vet_Cod) AS total";
            } else {
                $campos = "ventas.*,
                vende.Prs_Ape,
                vende.Prs_Nom,
                ciudad.Ciu_Des,
                Tic_Des,
                ventas_compr.Com_Cod,
                tipo_compr.Tic_Sri,
                ccpp_cobrar.Cpc_Cod,
                tipopagocom.*,
                Caj_Fec as Vet_Fec,
                concat(vende.Prs_Ape,' ',vende.Prs_Nom)as vendedor_per,
                concat(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)as cliente_per,
                cliente_ven.Prs_Ced,
                comprobantes.Pec_Cod,
                if(ccpp_cobrar.Cpc_Cod is null,'Contado','Credito')as Pago,
                if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
            }
            $Par_Sql['Tic_Cod'] = (!empty($Par_Sql['Tic_Cod']) ? "AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]" : "AND (tipo_compr.Tic_Sri=4 OR tipo_compr.Tic_Sri=5 )");
            if ($Par_Sql['op_opciones'] == 'd') {
                $search = "AND ventas.Vet_Num = '$Par_Sql[search]'";
                $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
            } else {
                $Par_Sql['Cmb_Mes'] = (!empty($Par_Sql['Pec_Cod']) && !empty($Par_Sql['Cmb_Mes']) ? "AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]" : '');
                $Par_Sql['Pec_Cod'] = (!empty($Par_Sql['Pec_Cod']) ? "AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'" : '');
                if ($Par_Sql['op_opciones'] == 'c')
                    $search = "AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search = "AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }
            $sql = "SELECT $campos FROM ventas
                  INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                  INNER JOIN persona as vende ON vendedor.Prs_Cod = vende.Prs_Cod
                  left join ventas_compr on ventas_compr.Vet_Cod=ventas.Vet_Cod
                  inner join cliente on cliente.Cli_Cod= ventas.Cli_Cod
                  INNER JOIN persona as cliente_ven ON cliente_ven.Prs_Cod = cliente.Prs_Cod
                  left join ccpp_cobrar on ccpp_cobrar.Cpc_Cod=(SELECT Cpc_Cod FROM det_ccpp_c INNER JOIN ventas_compr AS vet_cpr ON vet_cpr.Com_Cod=det_ccpp_c.Com_Cod WHERE vet_cpr.Vet_Cod=ventas.Vet_Cod LIMIT 1)
                  INNER JOIN ciudad ON ciudad.Ciu_Cod = ventas.Ciu_Cod
                  left join tipopagocom on tipopagocom.Tpc_Cod = ventas.Tpc_Cod
                  left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod AND comprobantes.Com_Est='A'
                  left join autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                  INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                  inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                WHERE ventas.Vet_Est<>'E'  AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $search
                $Par_Sql[order] $Par_Sql[limits] ;";
            //echo $sql.'<br/>';
            break;

        case 145: // ingreso a la tabla cheques_ext para que aparezca en el control de cheques de ccxcc
            $sql = "INSERT INTO cheques_ext (Bak_Cod, Cli_Cod, Che_Cta, Che_Num, Che_Fec, Che_Val, Che_Cli) 
                  VALUES ($Par_Sql[Bak_Cod], '$Par_Sql[Cli_Cod]', '$Par_Sql[Vet_Cue]', '$Par_Sql[Vet_Che]', '$Par_Sql[Fec_che]', '$Par_Sql[Vet_Tot]', '$Par_Sql[Cliente]')";
            break;
        case 146: // Relaciona las ventas con los cheques entregados como pagos
            $sql = "INSERT INTO cheq_det_ventas
                  VALUES ($Par_Sql[Che_Cod], '$Par_Sql[Vet_Cod]')";
            break;
        case 147: // Anular cheque pagos de la venta
            $sql = "UPDATE cheques_ext, cheq_det_ventas SET cheques_ext.Che_Est = 'I' 
                    WHERE 
                    cheques_ext.Che_Cod = cheq_det_ventas.Che_Cod
                    AND cheq_det_ventas.Vet_Cod=$Par_Sql[0]";
            break;
        case 148: // Delete pagos de la venta
            $sql = "Delete from cheq_det_ventas where Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;

        case 150: // Stock del producto para controlar negativos en la venta
            $sql = "SELECT Sum(kardex_ie.Kar_Can) - SUM(kardex_ie.Kar_Sal) as Stk_Can from kardex_ie where kardex_ie.Pro_cod = $Par_Sql[0] and kardex_ie.Kar_Est = 'A'";
            break;

        case 151: // Configuracion de la empresa para controlar stokc en la venta
            $sql = "SELECT Cof_Stk_Neg from confi_fact where Emp_Cod=$Par_Sql[0]";
            break;

        case 152: // Stock del producto para controlar negativos en la venta
            $sql = "SELECT Ite_Lar from item INNER JOIN producto ON item.Ite_Cod = producto.Ite_Cod where producto.Pro_cod = $Par_Sql[0] ";
            break;

        case 153: //tipo de pago efectivo--credito 
            $sql = "SELECT Pag_Cod, Pag_Des FROM tipos_pago WHERE For_Cod=$Par_Sql[0] AND Pag_Abr in ('CXC', 'EFE')";
            break;

        case 154: //consulta la bodega que se utilizo 
            $sql = "SELECT Bod_Cod FROM kardex_ie WHERE Vet_Cod = $Par_Sql[0] limit 1";
            break;

        case 970:
            $sql = "UPDATE ventas Set Vet_Obs = '$Par_Sql[Vet_Observacion]' WHERE Vet_Cod = $Par_Sql[Vet_Codigo]";
            break;

        case 971:
            $sql = "UPDATE comprobantes 
            INNER JOIN ventas_compr ON ventas_compr.Com_Cod = comprobantes.Com_Cod
            INNER JOIN ventas ON ventas.Vet_Cod = ventas_compr.Vet_Cod
            SET comprobantes.Com_Obs = '$Par_Sql[Vet_Observacion]'
            WHERE ventas.Vet_Cod = $Par_Sql[Vet_Codigo]";
            break;

        case 990: //obtener comprobante y valor de la retencion  
            $sql = "SELECT Com_Cod, Cpc_Val FROM det_ccpp_c WHERE Cpc_Cod = $Par_Sql[0] ";
            break;

        case 991: //elimina el haber del comprobante de retencion
            $sql = "DELETE FROM asientos WHERE Com_Cod = $Par_Sql[Com_Ret] AND Asi_Deh = 'H'";
            break;

        case 9911: //cambiar el valor del debe del comprobante 
            $sql = "UPDATE asientos SET Asi_Val = (Asi_Val - $Par_Sql[Cpc_Val]), Pld_Cod = $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = 'D' ";
            break;

        case 992: //cambiar los asientos del comprobante de la retencion al comprobante de la compra
            $sql = "UPDATE asientos SET Com_Cod = $Par_Sql[Com_Cod]  WHERE Com_Cod = $Par_Sql[Com_Ret] ";
            break;

        case 993: //Eliminar el detalle de cuentas por pagar   
            $sql = "DELETE FROM det_ccpp_c WHERE Cpc_Cod = $Par_Sql[Cpc_Cod] ";
            break;

        case 994: //Eliminar el comprobante de la retencion   
            $sql = "DELETE FROM comprobantes WHERE Com_Cod = $Par_Sql[Com_Ret] ";
            break;

        //elimina cuentas por pagar 

        case 995: //Eliminar el comprobante de la retencion   
            $sql = "SELECT det_plan.Pld_Cod, ventas.Ret_Fec FROM ventas
                    INNER JOIN ventas_det ON ventas.Vet_Cod = ventas_det.Vet_Cod
                    INNER JOIN reniva_pla ON (reniva_pla.Ren_Cod = ventas_det.Ren_Cod or reniva_pla.Ren_Cod = ventas_det.Ren_Iva)
                    INNER JOIN det_plan ON reniva_pla.Pld_Cod = det_plan.Pld_Cod
                    INNER JOIN plan_cuenta ON det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    WHERE ventas.Vet_Cod = $Par_Sql[Vet_Cod] AND plan_cuenta.Emp_Cod = $Par_Sql[Emp_Cod] and reniva_pla.Ren_Tip = 'V' and det_plan.Pld_Est = 'A'";
            break;

        case 996: //Consulto el comprobante de compra.
            $sql = "SELECT * FROM comprobantes WHERE Com_Cod = $Par_Sql[Com_Cod] ";
            break;

        case 997: //cambiar los asientos del comprobante de la venta al comprobante de la retencion
            $sql = "UPDATE asientos SET Com_Cod = $Par_Sql[Com_Ret]  WHERE Com_Cod = $Par_Sql[Com_Cod] and Pld_Cod = $Par_Sql[Pld_Cod] and Asi_Deh = 'D'";
            break;

        case 998: //Consulto el total de la retencion 
            $sql = "SELECT sum(Asi_Val) as totalRetencion FROM asientos WHERE Com_Cod = $Par_Sql[Com_Cod] and Asi_Deh = 'D' ";
            break;

        case 999: //inserta asiento debe en el comprobante de retencion
            $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
                                VALUES($Par_Sql[Com_Cod], 'H', '$Par_Sql[Asi_Val]', '$Par_Sql[Vet_Num]', $Par_Sql[Pld_Cod], '$Par_Sql[Vet_Num]')";
            break;

        case 10000: //update el valor del comprobante de compra
            $sql = "UPDATE asientos SET Asi_Val = Asi_Val +  $Par_Sql[Asi_Val], Pld_Cod =  $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = 'D'";
            break;

        case 10011: //update el valor del comprobante de compra
            $sql = "UPDATE comprobantes SET Com_Val = $Par_Sql[Com_Val] WHERE Com_Cod = $Par_Sql[Com_Cod]";
            break;
        case 10022:
            $sql = "INSERT INTO ccpp_cobrar(Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[Com_Cod], $Par_Sql[Vet_Cod], '$Par_Sql[Cpc_Ven]', UPPER('$Par_Sql[Cpc_Obs]'));";
            break;


        case 1000:
            $sql = "SELECT perio_cont.* FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
            //echo $sql;
            break;

        case 1001:
            /* cuentas contado */
            $sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan, pago_plan, plan_cuenta
             WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND pago_plan.Pag_Cod = $Par_Sql[1] AND (Ban_Tip='C' OR Ban_Tip='O') AND plan_cuenta.Pla_Cod = $Par_Sql[0] ORDER BY Pld_Cdc, Pld_Des";
            //echo $sql."<br>";
            break;

        case 1002:
            /* cuentas credito */
            $sql = "SELECT ccpp_cliente.Pld_Cod, det_plan.Pld_Des, ccpp_cliente.Cpc_Def, ccpp_cliente.Cpc_Cxc, Cpc_Def AS extra FROM det_plan INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
            //echo $sql."<br>";
            break;

        case 1003: //Verifica si tiene activada la opcion de vendedores, Registrar vendedor es la id 83
            $sql = "SELECT COUNT(*) AS total_rows
                  FROM perfiles
                  INNER JOIN perfiorgan ON perfiles.Per_Cod = perfiorgan.Per_Cod
                  INNER JOIN usuarperfi ON perfiles.Per_Cod = usuarperfi.Per_Cod
                  WHERE perfiles.Emp_Cod = $Par_Sql[0] AND usuarperfi.Usu_Cod = $Par_Sql[1] AND perfiorgan.Pcs_Cod=83  AND perfiles.Per_Est='A' ";

            break;

        case 1004: //Select los vendedores de sucursal que ha iniciado sesion. 
            $sql = "SELECT * 
                    FROM persona
                    INNER JOIN personal ON persona.Prs_Cod = personal.Prs_Cod
                    INNER JOIN vendedor ON vendedor.Prs_Cod=persona.Prs_Cod
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN empresas ON personal.Emp_Cod = empresas.Emp_Cod
                    WHERE empresas.Emp_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod =$Par_Sql[1] AND vendedor.Vnd_Est='A' AND puntos_imp.Pun_Des='Caja-Vendedores'";
            break;

        case 1005:
            $sql = "SElECT * FROM llave_elect WHERE Lla_Est='A' AND Emp_Cod=$Par_Sql[0];";
            break;

        case 1006: //Insert en la tabla RUTAS_FACT_EXTRA
            if (empty($Par_Sql['Ext_Cod'])) { //Si Ext_Cod esta vacio o nulo registra datos
                $sql = "INSERT INTO rutas_fact_extra(Ext_Nom,Ext_Ciu,Ext_Dest,Ext_Ruta,Ext_Fec,Emp_Cod,Ext_Telf) VALUES('$Par_Sql[Ext_Nom]','$Par_Sql[Ext_Ciu]','$Par_Sql[Ext_Dest]','$Par_Sql[Ext_Ruta]','$Par_Sql[Ext_Fec]','$Par_Sql[Emp_Cod]', '$Par_Sql[Ext_Telf]'  );";
            } else { //Editar datos
                $sql = "UPDATE rutas_fact_extra  SET Ext_Nom = '$Par_Sql[Ext_Nom]', Ext_Ciu = '$Par_Sql[Ext_Ciu]',  Ext_Dest = '$Par_Sql[Ext_Dest]', 
                        Ext_Ruta = '$Par_Sql[Ext_Ruta]',  Ext_Fec = '$Par_Sql[Ext_Fec]', Emp_Cod = '$Par_Sql[Emp_Cod]', Ext_Telf = '$Par_Sql[Ext_Telf]' 
                    WHERE Ext_Cod = '$Par_Sql[Ext_Cod]';";
            }
            break;

        case 2006: //Insert en la tabla RUTAS_FACT_EXTRA
            if (empty($Par_Sql['Ext_Cod'])) { //Si Ext_Cod esta vacio o nulo registra datos
                $sql = "INSERT INTO rutas_fact_extra (Ext_Nom,Ext_Ciu,Ext_Dest,
                                                        Ext_Ruta,Ext_Fec,Emp_Cod,Ext_Telf,
                                                        Ext_Placa, Ext_PediPos,
                                                        Ext_NrAcep, Vet_Cod) 
                                VALUES('','','','','','$Par_Sql[Emp_Cod]','','$Par_Sql[Ext_Placa]',
                                                        '$Par_Sql[Ext_PediPos]','$Par_Sql[Ext_NrAcep]',
                                                        '$Par_Sql[Vet_Cod]');";
            } else { //Editar datos
                $sql = "UPDATE rutas_fact_extra  
                                SET Emp_Cod = '$Par_Sql[Emp_Cod]', Ext_Placa = '$Par_Sql[Ext_Placa]', 
                                    Ext_PediPos = '$Par_Sql[Ext_PediPos]', Ext_NrAcep = '$Par_Sql[Ext_NrAcep]',
                                    Vet_Cod = '$Par_Sql[Vet_Cod]' 
                            WHERE Ext_Cod = '$Par_Sql[Ext_Cod]';";
            }
            break;

        case 1007: //Insert en la tabla RUTAS_FACT_EXTRA
            $search = " ";
            if (!empty($Par_Sql[1])) {
                $search = " AND Ext_Nom LIKE '%" . $Par_Sql[1] . "%'";
            }

            $sql = "SELECT *  FROM rutas_fact_extra WHERE Emp_Cod = $Par_Sql[0]  $search";
            break;

        case 1008: //Insert en la tabla RUTAS_FACT_EXTRA
            $sql = "DELETE  FROM rutas_fact_extra WHERE Ext_Cod = '$Par_Sql[Ext_Cod]'";
            break;

        // OBTENER LOS VENDEDORES
        case 157:
            $sql = "SELECT vendedor.Vnd_Cod, persona.Prs_Nom,persona.Prs_Ape, puntos_imp.Pun_Des  FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN persona ON vendedor.Prs_Cod = persona.Prs_Cod
                    WHERE Suc_Cod=$Par_Sql[0]  AND Vnd_Est='A'";
            break;
        case 1588:
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

        /**
         * ----------------------------------------------------------------------
         *|           FUNCIONES PARA LEER LA API DE LA GASOLINERA                |
         *  ----------------------------------------------------------------------
         */


        case 1009:
            $sql = "SELECT * FROM vendedor
                        INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                        INNER JOIN persona ON vendedor.Prs_Cod = persona.Prs_Cod
                        WHERE Suc_Cod=$Par_Sql[0] AND Prs_Ced=$Par_Sql[1] AND Vnd_Est='A'";
            break;

        //VERIFICAR SI EXISTE UNA FACTURA 
        case 1010:
            $sql = "SELECT  COUNT(Vet_Sri) AS total FROM ventas  
                    INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = vendedor.Pun_Cod
                    WHERE Vet_Sri='$Par_Sql[0]' AND ventas.Vnd_Cod=$Par_Sql[1] AND Suc_Cod=$Par_Sql[2]  AND Vet_Est='A'";
            break;
        // VERIFICAR EL CODIGO DEL CLIENTE
        case 1011:
            $sql = "SELECT Cli_Cod, Ciu_Cod FROM cliente 
                    INNER JOIN  persona ON persona.Prs_Cod = cliente.Prs_Cod
                    WHERE Prs_Ced=$Par_Sql[0] AND Emp_Cod=$Par_Sql[1] ";
            break;

        // CODIGO DE CIUDAD BUSCADO POR EL NOMBRE
        case 157:
            $sql = "SELECT Ciu_Cod FROM ciudad  WHERE Ciu_Des='$Par_Sql[0]' AND Ciu_Est='A'";
            break;

        case 156:
            $sql = "SELECT Iva_Cod FROM iva  WHERE Iva_Sri='$Par_Sql[0]' AND Iva_Por='$Par_Sql[1]' AND Iva_Est='A'";
            break;

        case 158:
            $sql = "SELECT Pla_Cod FROM plan_cuenta  WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]'";
            break;

        case 159:
            $sql = "SELECT (SELECT producto.Pro_Cod, adquisicio.Adq_Cor
                    FROM producto
                    INNER JOIN produ_plan ON produ_plan.Pro_Cod = producto.Pro_Cod
                    INNER JOIN det_plan ON det_plan.Pld_Cod = produ_plan.Pld_Cod
                    LEFT JOIN adquisicio.Adq_Cod = producto.Adq_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_Cod
                    WHERE Pro_Cod_Emp = '$Par_Sql[0]'
                    AND plan_cuenta.Emp_Cod =$Par_Sql[1]
                LIMIT 1 ) AS Pro_Cod";
            break;

        case 160:
            $sql = "SELECT Pla_Cod FROM plan_cuenta  WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]'";
            break;

        case 161:
            $sql = "SELECT Pla_Cod FROM plan_cuenta  WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]'";
            break;

        case 855:
            $sql = "SELECT * FROM vendedor
            INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
            INNER JOIN autorizaci ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
            WHERE Suc_Cod=$Par_Sql[0] AND Prs_Cod=$Par_Sql[1]  AND Tic_Cod=1";
            break;

        case 162:
            $sql = "SELECT * FROM usuarios  WHERE Prs_Cod=$Par_Sql[0] AND Suc_Cod=$Par_Sql[1]";
            break;

        case 163:
            $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$Par_Sql[9]'"; //Antes Com_Tip
            break;

        //EDITAR EL PUNT_SRI
        case 164:
            $sql = "UPDATE autorizaci SET Pun_Sri='$Par_Sql[1]' 
            WHERE Aut_Cod='$Par_Sql[0]'";
            break;
        // OBTENER LA PERSONA CON NUMERO DE CEDULA
        case 165:
            $sql = "SELECT * FROM persona  WHERE Prs_Ced='$Par_Sql[0]'";
            break;
        //REGISTRAR LOS LOG DE LA CARGA DE LAS FACTURAS
        case 166:
            $sql = "INSERT INTO log_gasolinera SET Emp_Cod=$Par_Sql[0], Des_Error='$Par_Sql[1]', fecha='" . date('Y-m-d') . "'";
            break;
        // OBTENER LOS LOG DE LA CARGA DE LAS VENTAS
        case 167:
            $sql = "SELECT * FROM log_gasolinera ";
            break;
        //Datos camaronera
        case 168:
            if (!empty($Par_Sql[1])) $Par_Sql[1] = " AND Num_Neg=$Par_Sql[1]";
            $sql = "SELECT Cod_Neg,Num_Neg FROM nego_camaron WHERE Emp_Cod IN ($Par_Sql[0])  $Par_Sql[1]   AND Est_Neg = 'A' OR Est_Neg='P'";
            break;

        case 169:
            //$sql = "INSERT INTO nego_documentos(Cod_Neg, Cod_Doc, Abr_Doc,Tip_Prod) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]','$Par_Sql[3]' );";
            if (empty($Par_Sql[4])) {
                $sql = "INSERT INTO nego_documentos(Cod_Neg, Cod_Doc, Abr_Doc,Tip_Prod) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]','$Par_Sql[3]' );";
            } else {
                $sql = "UPDATE nego_documentos SET Cod_Neg=$Par_Sql[0], Cod_Doc=$Par_Sql[1], Abr_Doc='$Par_Sql[2]', Tip_Prod='$Par_Sql[3]' WHERE Cod_Nd=$Par_Sql[4];";
            }
            break;

        // ESTE SIRVE PARA VER SI EXISTEN PAGOS ACTIVOS ANTES DE CAMBIAR DE CREDITO A CONTADO UNA FACTURA
        case 170:
            $sql = "SELECT  COUNT(Dcc_Cod) AS tot_pago FROM ccpp_cobrar
                    INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    INNER JOIN comprobantes ON det_ccpp_c.Com_Cod = comprobantes.Com_Cod
                    WHERE  Vet_Cod = $Par_Sql[0] AND comprobantes.Com_Est='A'";
            break;
        case 171:
            $sql = "DELETE FROM nego_documentos  WHERE Cod_Neg = $Par_Sql[0]  AND Cod_Doc = $Par_Sql[1]";
            break;

        case 172:
            $sql = "SELECT Num_Neg FROM nego_camaron  WHERE Cod_Neg = $Par_Sql[0] AND Est_Neg = 'A' OR Est_Neg='P'";
            break;

        //Consultas para autorizar facturas
        case 173:
            $sql = " UPDATE ventas SET Vet_Sri = '$Par_Sql[numeroAutorizacion]' , Vet_Aut = 'S' WHERE Vet_Cod = $Par_Sql[Doc_Cod] ;";
            break;

        case 174:
            $sql = "SELECT 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, 
                Vet_Num AS Doc_Num, Vet_Cod AS Doc_Cod, Vet_Aut AS Doc_Aut, Vet_Xml AS Doc_Xml, 
                Vet_Sri AS Doc_Sri, 'ventas' AS tabla , IF(Cli_Cor IS NULL OR TRIM(Cli_Cor)='' OR TRIM(Cli_Cor)='-',
                IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Cli_Cor) AS Email 
                FROM ventas 
                INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                WHERE Vet_Cod = $Par_Sql[0] AND Vet_Est='A';";
            break;
        //Fin de consultar para autorizar facturas
        case 175:
            if (!empty($Par_Sql[0])) {
                //$Par_Sql[0] = " AND Pag_Abr='RET'";
                $Par_Sql[0] = " AND Pag_Abr='$Par_Sql[0]'";
            }
            $sql = "SELECT * FROM tipos_pago  WHERE  Pag_Est='A' $Par_Sql[0];";
            break;

        case 176: //Obtener los codigos de las empresas que se encuentran dentro de un grupo
            $sql = "SELECT grupo_clientes.* FROM det_grup_empresas 
            INNER JOIN grupo_clientes ON grupo_clientes.Cod_Grup = det_grup_empresas.Cod_Group
            WHERE det_grup_empresas.Emp_Cod = $_SESSION[Ses_Emp_Cod] ";
            break;
        case  178:
            $sql = "DELETE FROM nego_documentos WHERE  Cod_Nd =  $Par_Sql[0] AND  Abr_Doc = 'VNT'";
            break;
        case 179: //Obtener vehiculos
            $sql = "SELECT Ext_Nom,vehiculo.* 
            FROM rutas_fact_extra
                INNER JOIN autorizaci ON rutas_fact_extra.Ext_Cod = autorizaci.Ext_Cod
                LEFT JOIN vehiculo ON rutas_fact_extra.Ext_Cod = vehiculo.Ext_Cod
            WHERE autorizaci.Aut_Cod = $Par_Sql[Aut_Cod] AND Veh_Est='A'";
            break;
        // obtiene valores de CCxCC en base a un solo cliente -  esto para luego presentar el valor de Saldo en Reg Ventas.
        case 180:
            $sql = "SELECT 
                        c.Cli_Cod, CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) AS Cliente,
                        v.Vet_Cod, ca.Caj_Fec,
                        co.Com_Cod, a.Asi_Cod, a.Asi_Val,
                        dp.Pld_Cdc, dp.Pld_Des,
                        CONCAT(ta.Tia_Abr, '-', LPAD(MONTH(co.Com_Fec), 2, '0'), '-', co.Com_Num) AS Com_Codigo,
                        CASE
                            WHEN SUM(IF(comp2.Com_Est = 'A', ROUND(dcc.Cpc_Val, 2), 0)) = a.Asi_Val THEN 'Pagado'
                            WHEN DATEDIFF(cp.Cpc_Ven, CURRENT_DATE()) >= 0
                            THEN CONCAT(DATEDIFF(cp.Cpc_Ven, CURRENT_DATE()), ' dias')
                            ELSE 'Vencido'
                        END AS vencimiento,
                        ROUND(SUM(IF(comp2.Com_Est = 'A', ROUND(dcc.Cpc_Val, 2), 0)), 2) AS Abono,
                        ROUND(a.Asi_Val - SUM(IF(comp2.Com_Est = 'A', ROUND(dcc.Cpc_Val, 2), 0)), 2) AS Saldo,
                        tc.Tic_Des
                    FROM ventas v
                        INNER JOIN cliente c ON c.Cli_Cod = v.Cli_Cod
                        INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                        INNER JOIN tipo_compr tc ON v.Tic_Cod = tc.Tic_Cod
                        INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
                        INNER JOIN puntos_imp pi ON ca.Pun_Cod = pi.Pun_Cod
                        INNER JOIN sucursal s ON s.Suc_Cod = pi.Suc_Cod
                        INNER JOIN autorizaci au ON au.Aut_Cod = v.Aut_Cod
                        INNER JOIN ccpp_cobrar cp ON v.Vet_Cod = cp.Vet_Cod
                        INNER JOIN comprobantes co ON cp.Com_Cod = co.Com_Cod
                        INNER JOIN tipo_asien ta ON ta.Tia_Cod = co.Tia_Cod
                        INNER JOIN asientos a ON co.Com_Cod = a.Com_Cod
                        INNER JOIN perio_cont pc ON co.Pec_Cod = pc.Pec_Cod
                        INNER JOIN ccpp_cliente cc ON a.Pld_Cod = cc.Pld_Cod
                        INNER JOIN det_plan dp ON a.Pld_Cod = dp.Pld_Cod
                        LEFT JOIN det_ccpp_c dcc ON cp.Cpc_Cod = dcc.Cpc_Cod
                        LEFT JOIN comprobantes comp2 ON comp2.Com_Cod = dcc.Com_Cod
                    WHERE
                        v.Vet_Est IN ('A', 'E')
                        AND co.Com_Est IN ('A', 'E')
                        AND a.Asi_Deh = 'D'
                        AND pc.Pec_Cod = $Par_Sql[0]
                        AND s.Emp_Cod = $Par_Sql[1]
                        AND c.Cli_Cod = $Par_Sql[2]
                    GROUP BY v.Vet_Cod
                    ORDER BY cp.Cpc_Ven;";
            break;
        case 181:
            $sql = "SELECT Cli_Cod, Prs_Cod, Emp_Cod, Cli_Ruf, Dia_Cred, Mon_Max FROM cliente WHERE Cli_Cod=$Par_Sql[0]";
            break;

        // Validar clave de acceso
        case 182:
            $sql = "SELECT * FROM claves_accesos WHERE Emp_Cod = $_SESSION[Ses_Emp_Cod] AND Cla_Est = 'A'";
            break;

        case 183: // Validar clave de acceso
            $sql = "SELECT * FROM claves_accesos  
            WHERE Emp_Cod = '$Par_Sql[Emp_Cod]' AND Cla_Cod = '$Par_Sql[Cla_Cod]' AND Cod_Psc = '$Par_Sql[Cod_Psc]'  AND Cla_Est = 'A' LIMIT 1";
            break;


        //REGISTRAR COMO ANTICIPO LA RETENCION EN CASO QUE LA VENTA SEA POR MANFIIESTO (RELAVERA)
        case 1811: //184
            $sql = "INSERT INTO anticipos_clientes (Ant_Fec, Ant_Val, Ant_Est, Ant_Doc, Ant_Obs, Cli_Cod, Com_Cod, Ant_Tip, Ama_Cod, Vet_Cod)
                    VALUES ('$Par_Sql[Ant_Fec]', $Par_Sql[Ant_Val], '$Par_Sql[Ant_Est]',
                    " . (empty($Par_Sql['Ant_Doc']) ? 'NULL' : "'$Par_Sql[Ant_Doc]'") . ",
                    " . (empty($Par_Sql['Ant_Obs']) ? 'NULL' : "'$Par_Sql[Ant_Obs]'") . ",
                    " . (empty($Par_Sql['Cli_Cod']) ? 'NULL' : "$Par_Sql[Cli_Cod]") . ",
                    " . (empty($Par_Sql['Com_Cod']) ? 'NULL' : "$Par_Sql[Com_Cod]") . ",
                    '$Par_Sql[Ant_Tip]',
                    " . (empty($Par_Sql['Ama_Cod']) ? 'NULL' : "$Par_Sql[Ama_Cod]") . ",
                    " . (empty($Par_Sql['Vet_Cod']) ? 'NULL' : (int)$Par_Sql['Vet_Cod']) . ")";
            break;
        //Detectar si la venta existe en algun manifiesto
        case 1822: //185
            $sql = " SELECT COUNT(Vet_Cod) AS total FROM manifiesto WHERE Man_Est = 'A' AND Vet_Cod = $Par_Sql[0] ";
            break;
        case 1833: //186
            $sql = "INSERT INTO manifiesto_anticipo (Ban_Cod, Bak_Cod, Usu_Cod, Cli_Cod, Pag_Cod, Pla_Cod, Ama_Val, Ama_Tde, Ama_Tip, Ama_Doc, Ama_Fec, Ama_Obs, Ama_Img, Ama_Est)
                    VALUES (
                    " . (empty($Par_Sql['Ban_Cod']) ? 'NULL' : "$Par_Sql[Ban_Cod]") . ",
                    " . (empty($Par_Sql['Bak_Cod']) ? 'NULL' : "$Par_Sql[Bak_Cod]") . ",
                    " . (empty($Par_Sql['Usu_Cod']) ? 'NULL' : "$Par_Sql[Usu_Cod]") . ",
                    " . (empty($Par_Sql['Cli_Cod']) ? 'NULL' : "$Par_Sql[Cli_Cod]") . ",
                    $Par_Sql[Pag_Cod],
                    " . (empty($Par_Sql['Pla_Cod']) ? 'NULL' : "$Par_Sql[Pla_Cod]") . ",
                    $Par_Sql[Ama_Val],
                    " . (empty($Par_Sql['Ama_Tde']) ? 'NULL' : "'$Par_Sql[Ama_Tde]'") . ",
                    '$Par_Sql[Ama_Tip]',
                    '$Par_Sql[Ama_Doc]',
                    '$Par_Sql[Ama_Fec]',
                    " . (empty($Par_Sql['Ama_Obs']) ? 'NULL' : "'$Par_Sql[Ama_Obs]'") . ",
                    " . (empty($Par_Sql['Ama_Img']) ? 'NULL' : "'$Par_Sql[Ama_Img]'") . ",
                    '$Par_Sql[Ama_Est]')";
            break;
        case 1844:
            $sql = "SELECT * FROM manifiesto WHERE Man_Est = 'A' AND  Man_Tip = 'F' AND Vet_Cod = $Par_Sql[0] LIMIT 1";
            break;

        case 1855:
            $sql = "SELECT * FROM tipo_asien WHERE  Tia_Est='A' AND Tia_Abr='IN'";
            break;

        case 1866:
            $sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Est,Usu_Cod,Com_Gen)
							VALUES(" . (int)$Par_Sql['Pec_Cod'] . ", null, " . (int)$Par_Sql['Cli_Cod'] . ", '" . $Par_Sql['Com_Num'] . "', '" . $Par_Sql['Com_Fec'] . "', '" . $Par_Sql['Com_Con'] . "', 'I', '" . $Par_Sql['Com_Val'] . "', '" . $Par_Sql['Com_Obs'] . "', null, " . (int)$Par_Sql['Tia_Cod'] . ", 'A', " . (empty($Par_Sql['Usu_Cod']) ? 'NULL' : (int)$Par_Sql['Usu_Cod']) . ", 'A')";

            break;

        /* Obtener anticipo por manifiesto (retención) existente para una venta: Ama_Cod, Ant_Cod, Com_Cod, Com_Num, Vet_Cod (referencia a la venta que generó la retención) */
        case 1877:
            $sql = "SELECT ma.Ama_Cod, ac.Ant_Cod, ac.Com_Cod, c.Com_Num, ac.Vet_Cod
                    FROM manifiesto m
                    INNER JOIN manifiesto_anticipo ma ON ma.Cli_Cod = m.Cli_Cod AND ma.Pla_Cod = m.Pla_Cod AND (ma.Ama_Tde = 'R' OR ma.Ama_Tde = '8')
                    INNER JOIN anticipos_clientes ac ON ac.Ama_Cod = ma.Ama_Cod
                    INNER JOIN comprobantes c ON c.Com_Cod = ac.Com_Cod
                    WHERE m.Vet_Cod = " . (int)$Par_Sql[0] . " AND m.Man_Est = 'A' AND m.Man_Tip = 'F' LIMIT 1";
            break;

        /* Actualizar manifiesto_anticipo (valor, doc, fecha, obs) */
        case 191:
            $sql = "UPDATE manifiesto_anticipo SET Ama_Val = " . (float)$Par_Sql['Ama_Val'] . ", Ama_Doc = '" . addslashes($Par_Sql['Ama_Doc']) . "', Ama_Fec = '" . addslashes($Par_Sql['Ama_Fec']) . "', Ama_Obs = '" . addslashes($Par_Sql['Ama_Obs']) . "' WHERE Ama_Cod = " . (int)$Par_Sql['Ama_Cod'];
            break;

        /* Actualizar anticipos_clientes (valor, obs, doc) */
        case 192:
            $sql = "UPDATE anticipos_clientes SET Ant_Val = " . (float)$Par_Sql['Ant_Val'] . ", Ant_Obs = '" . addslashes($Par_Sql['Ant_Obs']) . "', Ant_Doc = '" . addslashes($Par_Sql['Ant_Doc']) . "' WHERE Ant_Cod = " . (int)$Par_Sql['Ant_Cod'];
            break;

        /* Obtener anticipo existente por Vet_Cod (para actualizar en lugar de crear otro) */
        case 193:
            $sql = "SELECT ac.Ant_Cod, ac.Ama_Cod, ac.Com_Cod, c.Com_Num, ac.Ant_Val
                    FROM anticipos_clientes ac
                    INNER JOIN comprobantes c ON c.Com_Cod = ac.Com_Cod AND c.Com_Est = 'A'
                    WHERE ac.Vet_Cod = " . (int)$Par_Sql[0] . " AND (ac.Ant_Est = 'A' OR ac.Ant_Est = 'U') LIMIT 1";
            break;
        /* Insertar registro en pagos_anticipo_cli */
        case 194:
            $sql = "INSERT INTO pag_anticipo_cli (Pac_Cto, Pac_Ctd, Pac_Val, Ant_Cod, Che_Cod, Pac_Obs, Pac_Num, Pag_Cod, Asi_Cod)
                    VALUES('" . addslashes($Par_Sql['Pac_Cto']) . "', '" . addslashes($Par_Sql['Pac_Ctd']) . "', " . (float)$Par_Sql['Pac_Val'] . ", " . (int)$Par_Sql['Ant_Cod'] . ", " . (empty($Par_Sql['Che_Cod']) ? 'NULL' : (int)$Par_Sql['Che_Cod']) . ", '" . addslashes($Par_Sql['Pac_Obs']) . "', '" . addslashes($Par_Sql['Pac_Num']) . "', " . (int)$Par_Sql['Pag_Cod'] . ", " . (int)$Par_Sql['Asi_Cod'] . ")";
            break;
        /* Eliminar registros de pagos_anticipo_cli por Ant_Cod */
        case 195:
            $sql = "DELETE FROM pag_anticipo_cli WHERE Ant_Cod = " . (int)$Par_Sql[0];
            break;
        //obtener los pgaos que tiene una  factura a credito 
        case 196:
            $sql = "SELECT  ROUND(SUM(det_ccpp_c.Cpc_Val),2) AS tot_pago FROM ccpp_cobrar
            INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
            INNER JOIN comprobantes ON det_ccpp_c.Com_Cod = comprobantes.Com_Cod
            WHERE  Vet_Cod = $Par_Sql[0] AND comprobantes.Com_Est='A'  AND det_ccpp_c.Cpc_Est='A'";
            break;
        /* Actualizar estado del anticipo (Ant_Est: C=consumido, U=utilizado parcial) */
        case 197:
            $sql = "UPDATE anticipos_clientes SET Ant_Est = '" . $Par_Sql['Ant_Est'] . "' WHERE Ant_Cod = " . (int)$Par_Sql['Ant_Cod'];
            break;
        /* Actualizar estado de pag_anticipo_cli (Pac_Est: C=consumido, U=utilizado) */
        case 198:
            $sql = "UPDATE pag_anticipo_cli SET Pac_Est = '" . $Par_Sql['Pac_Est'] . "' WHERE Pac_Cod = " . (int)$Par_Sql['Pac_Cod'] . " AND Ant_Cod = " . (int)$Par_Sql['Ant_Cod'];
            break;
        /* Insertar det_ant_cccc (consumo de anticipo aplicado a cobro) */
        case 199:
            $sql = "INSERT INTO det_ant_cccc (Ddc_Val, Ddc_Obs, Ant_Cod, Dcc_Cod, Pac_Cod, Com_Cod) VALUES (" . (float)$Par_Sql['Ddc_Val'] . ", '" . addslashes($Par_Sql['Ddc_Obs']) . "', " . (int)$Par_Sql['Ant_Cod'] . ", " . (int)$Par_Sql['Dcc_Cod'] . ", " . (int)$Par_Sql['Pac_Cod'] . ", " . (int)$Par_Sql['Com_Cod'] . ")";
            break;
        case 200:
            $sql = "SELECT ant.Ant_Val, ant.Ant_Cod, ant.Ant_Val, ant.Ant_Fec, CONCAT(pr.Prs_Nom, ' ', pr.Prs_Ape) AS nombre,
                        ant.Com_Cod, pr.Prs_Ced, COALESCE(SUM(dacc.Ddc_Val), 0) AS Dac_Val, COALESCE(SUM(dacc.Ddc_Val), 0) AS Dac_Val_Aux,
                        (COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli AS pga WHERE pga.Ant_Cod = ant.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo,
                        (COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli AS pga WHERE pga.Ant_Cod = ant.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo_aux
                    FROM anticipos_clientes AS ant
                        INNER JOIN comprobantes AS com ON com.Com_Cod = ant.Com_Cod
                        INNER JOIN cliente AS cli ON ant.Cli_Cod = cli.Cli_Cod
                        INNER JOIN persona AS pr ON pr.Prs_Cod = cli.Prs_Cod
                        LEFT JOIN det_ant_cccc AS dacc ON dacc.Ant_Cod = ant.Ant_Cod
                    WHERE ant.Cli_Cod = $Par_Sql[Cli_Cod] AND (ant.Ant_Est = 'A' OR ant.Ant_Est = 'U') AND com.Com_Est = 'A' 
                    AND ant.Ant_Cod = $Par_Sql[Ant_Cod] And ant.Vet_Cod = $Par_Sql[Vet_Cod]
                    GROUP BY ant.Ant_Cod, ant.Ant_Fec, nombre, ant.Com_Cod, pr.Prs_Ced 
                    ORDER BY ant.Ant_Cod, ant.Ant_Fec ASC";
            break;
        case 201:
            $sql = "DELETE FROM det_ant_cccc WHERE Dcc_Cod = " . (int)$Par_Sql;
            break;
        case 256:
            $sql = "DELETE FROM det_ccpp_c WHERE Dcc_Cod = " . (int)$Par_Sql;
            break;
        /* Obtener Com_Cod de comprobantes de cobro con anticipo (pago con anticipo) por Vet_Cod */
        case 260:
            $sql = "SELECT DISTINCT d.Com_Cod FROM det_ccpp_c d
                    INNER JOIN ccpp_cobrar c ON c.Cpc_Cod = d.Cpc_Cod
                    INNER JOIN det_ant_cccc a ON a.Dcc_Cod = d.Dcc_Cod
                    INNER JOIN comprobantes comp ON comp.Com_Cod = d.Com_Cod
                    WHERE c.Vet_Cod = " . (int)$Par_Sql[0] . " AND comp.Com_Est = 'A' AND d.Cpc_Est = 'A'";
            break;
        /* Anular comprobante (Com_Est = 'I') por Com_Cod */
        case 261:
            $sql = "UPDATE comprobantes SET Com_Est = 'I' WHERE Com_Cod = " . (int)$Par_Sql[0];
            break;
        /* Anular det_ccpp_c (Cpc_Est = 'I') por Com_Cod */
        case 262:
            $sql = "UPDATE det_ccpp_c SET Cpc_Est = 'I' WHERE Com_Cod = " . (int)$Par_Sql[0];
            break;
        /* Anular anticipo cliente (Ant_Est = 'I') por Ant_Cod - anticipos generados por retención en ventas */
        case 263:
            $sql = "UPDATE anticipos_clientes SET Ant_Est = 'I' WHERE Ant_Cod = " . (int)$Par_Sql[0];
            break;
        /* Obtener todos los anticipos con Vet_Cod (generados por retención de esta venta) para anularlos: Ant_Est a 'I', su comprobante Com_Est a 'I' y, si tiene, manifiesto_anticipo Ama_Est a 'I'. Incluye Ama_Cod y Ant_Val (para comparar si la retención cambió). */
        case 264:
            $sql = "SELECT ac.Ant_Cod, ac.Com_Cod, ac.Ama_Cod, ac.Ant_Val FROM anticipos_clientes ac
                    LEFT JOIN comprobantes c ON c.Com_Cod = ac.Com_Cod
                    WHERE ac.Vet_Cod = " . (int)$Par_Sql[0] . " AND (ac.Ant_Est = 'A' OR ac.Ant_Est = 'U' OR ac.Ant_Est='C' )";
            break;
        /* Comprobantes de cobro que usaron anticipos de ESTA venta (Vet_Cod). Solo esos deben anularse al editar la retención; no otros pagos de la factura. */
        case 266:
            $sql = "SELECT DISTINCT d.Com_Cod FROM det_ccpp_c d
                    INNER JOIN det_ant_cccc a ON a.Dcc_Cod = d.Dcc_Cod
                    INNER JOIN anticipos_clientes ac ON ac.Ant_Cod = a.Ant_Cod AND ac.Vet_Cod = " . (int)$Par_Sql[0] . "
                    INNER JOIN comprobantes comp ON comp.Com_Cod = d.Com_Cod
                    WHERE comp.Com_Est = 'A' AND d.Cpc_Est = 'A'";
            break;
        /* Actualizar Ama_Cod del anticipo (vínculo con manifiesto_anticipo); solo cuando la venta está relacionada con manifiesto */
        case 267:
            $sql = "UPDATE anticipos_clientes SET Ama_Cod = " . (empty($Par_Sql['Ama_Cod']) ? 'NULL' : (int)$Par_Sql['Ama_Cod']) . " WHERE Ant_Cod = " . (int)$Par_Sql['Ant_Cod'];
            break;
        /* Inhabilitar manifiesto_anticipo (Ama_Est = 'I') por Ama_Cod; al editar la retención se inhabilita el anterior y se crea uno nuevo */
        case 268:
            $sql = "UPDATE manifiesto_anticipo SET Ama_Est = 'I' WHERE Ama_Cod = " . (int)$Par_Sql[0];
            break;
        case 269:
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(DISTINCT ventas.Vet_Cod) AS total";
            } else {
                $campos = "ventas.Vet_Cod, 
                            CONCAT(sucursal.Suc_Sri, '-', autorizaci.Pun_Sri, '-', CAST(LPAD(ventas.Vet_Num, 9, '0') AS CHAR)) AS Secuencia, 
                            caja_aper.Caj_Fec, 
                            persona.Prs_Ced, 
                            CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as Cliente,
                            ventas.Vet_Obs as Ite_Lar,
                            GROUP_CONCAT(CONCAT('* Prod#', ventas_det.Vet_Ite, ': ', CAST(ventas_det.Vet_Can AS CHAR)) SEPARATOR '\r\n') as Vet_Can,
                            GROUP_CONCAT(CONCAT('• ', item.Ite_Lar, ' [Precio U. ', CAST(ventas_det.Vet_Pru AS CHAR), ' / Importe ', CAST(ROUND(ventas_det.Vet_Can * ventas_det.Vet_Pru, 2) AS CHAR), ']') SEPARATOR '\r\n') as Des_Adi,
                            SUM(ventas_det.Vet_Imp) as Total";
            }

            $where = "ventas.Vet_Est IN ('A','I') "; // Default status filter
            if ($Par_Sql['op_est'] == 'A') $where = "ventas.Vet_Est = 'A' ";
            else if ($Par_Sql['op_est'] == 'I') $where = "ventas.Vet_Est = 'I' ";

            $where .= " AND cliente.Emp_Cod = " . $_SESSION['Ses_Emp_Cod'];

            if (!empty($Par_Sql['Suc_Cod']) && $Par_Sql['Suc_Cod'] != 'T') {
                $where .= " AND puntos_imp.Suc_Cod = " . $Par_Sql['Suc_Cod'];
            }

            if (!empty($Par_Sql['Tic_Cod']) && $Par_Sql['Tic_Cod'] != 'T') {
                $where .= " AND ventas.Tic_Cod = " . $Par_Sql['Tic_Cod'];
            }

            if (!empty($Par_Sql['range']) && $Par_Sql['range'] == 'S') {
                $where .= " AND (caja_aper.Caj_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59')";
            }

            if (!empty($Par_Sql['cedul']) && $Par_Sql['cedul'] == 'S' && !empty($Par_Sql['Cli_Cod'])) {
                $where .= " AND ventas.Cli_Cod = " . $Par_Sql['Cli_Cod'];
            }

            if (!empty($Par_Sql['Vet_Aut']) && $Par_Sql['Vet_Aut'] != 'T' && in_array($Par_Sql['Vet_Aut'], array('S', 'N'), true)) {
                $where .= " AND ventas.Vet_Aut = '" . $Par_Sql['Vet_Aut'] . "'";
            }

            $sql = "SELECT $campos FROM ventas
                    INNER JOIN caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod
                    INNER JOIN puntos_imp ON caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod
                    INNER JOIN autorizaci ON ventas.Aut_Cod = autorizaci.Aut_Cod
                    INNER JOIN cliente ON ventas.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                    INNER JOIN ventas_det ON ventas.Vet_Cod = ventas_det.Vet_Cod
                    INNER JOIN producto ON ventas_det.Pro_Cod = producto.Pro_Cod
                    INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
                    WHERE $where " .
                (empty($Par_Sql['limits']) ? "" : " GROUP BY ventas.Vet_Cod " . (!empty($Par_Sql['order']) ? $Par_Sql['order'] : "") . " " . $Par_Sql['limits']);
            break;

        /* Pagos de retención directos antiguos (sin anticipos) activos por Cpc_Cod */
        case 271:
            $sql = "SELECT DISTINCT d.Com_Cod
                    FROM det_ccpp_c d
                    INNER JOIN comprobantes c ON c.Com_Cod = d.Com_Cod
                    WHERE d.Cpc_Cod = " . (int)$Par_Sql['Cpc_Cod'] . "
                    AND d.Pag_Cod = 50 AND d.Cpc_Est = 'A'  AND c.Com_Est = 'A'";
            break;
    }
    return $sql;
}
