<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizacion:  2012-06-08
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */


function sentencias_tes($id, $Par_Sql)
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
            else $campos = " item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec ";
            if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
            else {
                //$search=" item.Ite_Lar  LIKE '%$Par_Sql[0]%' ";    
                $search = "";
                $array = explode(" ", strtoupper($Par_Sql[0]));
                foreach ($array as $ar) {
                    if (!empty($ar) && $ar != '') $search .= (($search != '' ? " AND " : "") . "CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE '%$ar%'");
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
                  WHERE 
                  $search AND Pro_Est='A' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql.'<br/>';
            break;
        case 2:
            $sql = "SELECT producto.Pro_Cod,Stk_Can,Pro_Prp,Stk_Prp,Pre_Pvp FROM producto 
                   LEFT JOIN stock ON(producto.Pro_Cod=stock.Pro_Cod AND stock.Suc_Cod=$Par_Sql[0])
                   LEFT JOIN precios ON(producto.Pro_Cod=precios.Pro_Cod AND precios.Suc_Cod=$Par_Sql[0] AND Pre_Des='Precio 1')
                   WHERE producto.Pro_Cod=$Par_Sql[1]";
            //echo $sql.'<br/>';
            break;

        case 222:
            $sql = "SELECT SUM(kardex_ie.Kar_Ims-kardex_ie.Kar_Ime)/SUM(kardex_ie.Kar_Can-kardex_ie.Kar_Sal) AS Pre_Pro,producto.Pro_Cod,Stk_Can,Pro_Prp,Stk_Prp,Pre_Pvp FROM producto 
                   LEFT JOIN stock ON (producto.Pro_Cod=stock.Pro_Cod AND stock.Suc_Cod=$Par_Sql[0])
                   LEFT JOIN precios ON (producto.Pro_Cod=precios.Pro_Cod AND precios.Suc_Cod=$Par_Sql[0] AND Pre_Des='Precio 1')
                   LEFT JOIN kardex_ie ON (producto.Pro_cod = kardex_ie.Pro_Cod)
                   WHERE producto.Pro_Cod=$Par_Sql[1]";
            //ChromePhp::log($sql);
            //echo $sql.'<br/>';
            break;

        case 200:
            $sql = "SELECT producto.Pro_Cod,(sum(kardex_ie.kar_can) - sum(kardex_ie.kar_sal)) as Stk_Can,Pro_Prp,Stk_Prp,Pre_Pvp FROM producto 
                       LEFT JOIN stock ON(producto.Pro_Cod=stock.Pro_Cod AND stock.Suc_Cod=$Par_Sql[0])
                       LEFT JOIN precios ON(producto.Pro_Cod=precios.Pro_Cod AND precios.Suc_Cod=$Par_Sql[0] AND Pre_Des='Precio 1')
                       INNER JOIN kardex_ie ON (producto.Pro_Cod=kardex_ie.Pro_Cod)
                       WHERE producto.Pro_Cod=$Par_Sql[1] AND $Par_Sql[2] AND kardex_ie.Kar_Est='A'
                       GROUP BY pro_cod";
            //echo $sql.'<br/>';
            break;
        case 3:
            $sql = "SELECT * FROM tipo_ajus
                WHERE Emp_Cod=$Par_Sql[0] AND Tia_Tra='$Par_Sql[1]' AND Tia_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 4: // busca las configuraciones
            $sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 5: // Consulta del vendedor en base al codigo de la persona
            $sql = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND 
                                vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
            //echo $sql.'<br/>';
            break;
        case 50:
            $sql = "SELECT Ubi_Cod,Ubi_Des FROM ubicacion WHERE Ubi_Est='A' AND Emp_Cod = $Par_Sql[0] ORDER BY Ubi_Cod ASC";
            //echo $sql;
            break;
        case 6: /* Consulta para generar un codigo */
            $sql = "SELECT COALESCE(MAX(Aju_Sec),0)+1 as Aju_Sec FROM ajuste_kar 
                    INNER JOIN proveedore ON proveedore.Prv_Cod=ajuste_kar.Prv_Cod
                    WHERE Tia_Cod=$Par_Sql[0] AND Emp_Cod=$Par_Sql[1]";
            //echo $sql.'<br/>';
            break;
        case 7: // Insercion del ajuste 
            $sql = "INSERT INTO ajuste_kar(Aju_Fec,Aju_Hor,Aju_Det,Aju_Obs,Aju_Num,Aju_Sec,Tia_Cod,Prv_Cod,Vnd_Cod,Aju_Tip) 
                VALUES ('$Par_Sql[Aju_Fec]','$Par_Sql[Aju_Hor]','$Par_Sql[Aju_Det]','$Par_Sql[Aju_Obs]','$Par_Sql[Aju_Num]',$Par_Sql[Aju_Sec],$Par_Sql[Tia_Cod]," . (empty($Par_Sql['Prv_Cod']) ? 'NULL' : $Par_Sql['Prv_Cod']) . "," . (empty($Par_Sql['Vnd_Cod']) ? 'NULL' : $Par_Sql['Vnd_Cod']) . ",'" . (empty($Par_Sql['Aju_Tip']) ? 'A' : $Par_Sql['Aju_Tip']) . "')";
            //echo $sql.'<br/>';
            break;
        case 8: // Inserta los datos en la tabla de detalle de ajuste
            $sql = "INSERT INTO det_ajustek(Aju_Cod,Pro_Cod,Aju_Can,Aju_Pru,Aju_Imp,Aju_Int,Con_Cod) VALUES
                 ($Par_Sql[Aju_Cod],$Par_Sql[Pro_Cod],$Par_Sql[Aju_Can],$Par_Sql[Aju_Pru],$Par_Sql[Aju_Imp]," . (empty($Par_Sql['Aju_Int']) ? '1' : $Par_Sql['Aju_Int']) . "," . (empty($Par_Sql['Con_Cod']) ? 'NULL' : $Par_Sql['Con_Cod']) . ");";
            //echo $sql.'<br/>';
            break;
        case 9: // buscar los tipos de asiento
            $sql = "SELECT * FROM tipo_asien WHERE (Tia_Tip='A' OR Tia_Ini='D') ORDER BY Tia_Tip";
            //echo $sql.'<br/>';
            break;
        case 10: // Instar un comprobante contable            
            $sql = "INSERT INTO comprobantes 
                SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' "; //Antes Com_Tip
            //echo $sql.'<br/>';
            break;
        case 11: // Inserta datos del asiento contable aqui
            $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[Com_Cod], Asi_Deh='$Par_Sql[Asi_Deh]', Asi_Val=$Par_Sql[Asi_Val], Asi_Con=UPPER('$Par_Sql[Asi_Con]'), Asi_Glo=UPPER('$Par_Sql[Asi_Glo]'), Pld_Cod=$Par_Sql[Pld_Cod]";
            //echo $sql.'<br/>';
            break;
        case 12: // consultar cuentas contable            
            $sql = "SELECT * FROM produ_plan WHERE Pro_Cod=$Par_Sql[0] AND Tip_Pld='$Par_Sql[1]'";
            //echo $sql.'<br/>';
            break;
        case 13:
            /**
             * Con esta sentencia consulto el movimiento del kardex con fechas 
             */
            $sql = "SELECT 
                        CONCAT(kardex_ie.Vet_Cod,'_',kardex_ie.Cop_Cod,'_',kardex_ie.Aju_Cod) AS Kar_Key,
                        /*IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste','Ninguno'))) AS Tipo,*/
                        IF( kardex_ie.Vet_Cod!=0,TIC2.Tic_Des,IF(kardex_ie.Cop_Cod!=0,TIC1.Tic_Des,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc,
                        IF( kardex_ie.Vet_Cod!=0,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(kardex_ie.Cop_Cod!=0,Cop_Num,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc_Num,
                        CONCAT(IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),' - ',IF( kardex_ie.Vet_Cod!=0,Vet_Obs,IF(kardex_ie.Cop_Cod!=0,Cop_Obs,IF(kardex_ie.Aju_Cod!=0,Aju_Obs,'')))) AS Kar_Det,
                        kardex_ie.Vet_Cod,kardex_ie.Aju_Cod,kardex_ie.Cop_Cod,kardex_ie.Vnd_Cod,Gia_Cod,Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock , (Kar_Ims-Kar_Ime) AS Saldo , (Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_ent , (Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_sal 
                        FROM kardex_ie
                        INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
                        LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
                        LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
                        LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
                        LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
                        LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
                        LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                        LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                        LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                        LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                    WHERE 
                            Kar_Est='A' AND 
                            Pro_Cod=$Par_Sql[2]  
                    ORDER BY Kar_Fec";
            //echo $sql.'<br/>';
            break;
        case 14:
            $sql = "SELECT Emp_Cod,item.Ite_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Pro_Obs,IF(Pro_Stk IS NULL,0,Pro_Stk)AS Pro_Stk,IF(Pro_Prp IS NULL,0,Pro_Prp)AS Pro_Prp, Stk_Can, Stk_Prp FROM producto
                    LEFT JOIN stock ON  producto.Pro_Cod=stock.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod 
                    INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
                    WHERE Emp_Cod=$Par_Sql[0] /*AND producto.Pro_Cod=1602*/";
            //echo $sql.'<br/>';
            break;
        case 15:
            $sql = "UPDATE producto SET Pro_Stk=$Par_Sql[Pro_Stk],Pro_Prp=$Par_Sql[Pro_Prp] WHERE Pro_Cod=$Par_Sql[Pro_Cod] ";
            //echo $sql.'<br/>';
            break;
        case 16:
            $sql = "INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Pre,Kar_Ime,Kar_Sal,Kar_Prs,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int)
                    VALUES(" . (empty($Par_Sql['Vet_Cod']) ? 0 : $Par_Sql['Vet_Cod']) . "," . (empty($Par_Sql['Aju_Cod']) ? 0 : $Par_Sql['Aju_Cod']) . ",$Par_Sql[Vnd_Cod]," . (empty($Par_Sql['Cop_Cod']) ? 0 : $Par_Sql['Cop_Cod']) . ",$Par_Sql[Pro_Cod],
                    '$Par_Sql[Kar_Fec]','$Par_Sql[Kar_Hor]',
                    " . (empty($Par_Sql['Kar_Can']) ? 0 : $Par_Sql['Kar_Can']) . "," . (empty($Par_Sql['Kar_Pre']) ? 0 : $Par_Sql['Kar_Pre']) . "," . (empty($Par_Sql['Kar_Ime']) ? 0 : $Par_Sql['Kar_Ime']) . ",
                    " . (empty($Par_Sql['Kar_Sal']) ? 0 : $Par_Sql['Kar_Sal']) . "," . (empty($Par_Sql['Kar_Prs']) ? 0 : $Par_Sql['Kar_Prs']) . "," . (empty($Par_Sql['Kar_Ims']) ? 0 : $Par_Sql['Kar_Ims']) . ",
                    " . (empty($Par_Sql['Kar_Des']) ? 0 : $Par_Sql['Kar_Des']) . ",$Par_Sql[Iva_Cod]," . (empty($Par_Sql['Gia_Cod']) ? 0 : $Par_Sql['Gia_Cod']) . "," . (empty($Par_Sql['Kar_Int']) ? 1 : $Par_Sql['Kar_Int']) . ")";
            //echo $sql.'<br/>';
            break;
        case 17:
            $sql = "INSERT INTO ajuste_comprob(Com_Cod,Aju_Cod) VALUES($Par_Sql[0],$Par_Sql[1]);";
            //echo $sql.'<br/>';
            break;
        case 18: //listado consumos
            $sql = "SELECT * FROM consumo WHERE Con_Est='A' AND Emp_Cod=$Par_Sql[0]";
            //echo $sql.'<br>';
            break;
        case 19: // consultar cuentas contable            
            $sql = "SELECT * FROM produ_plan WHERE Pro_Cod=$Par_Sql[0] AND Tip_Pld='$Par_Sql[1]' AND Con_Cod='$Par_Sql[2]'";
            //echo $sql.'<br/>';
            break;
        case 20: /* Numeroo de Ajuste */
            $sql = "SELECT COALESCE(MAX(CAST(Aju_Num as DECIMAL)),0)+1 as Aju_Num FROM ajuste_kar 
                    INNER JOIN proveedore ON proveedore.Prv_Cod=ajuste_kar.Prv_Cod
                    WHERE Emp_Cod=$Par_Sql[1] AND Aju_Tip='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 21:
            $sql = "UPDATE stock SET Stk_Can=$Par_Sql[Stk_Can],Stk_Prp=$Par_Sql[Stk_Prp] WHERE Pro_Cod=$Par_Sql[Pro_Cod] AND Suc_Cod='$Par_Sql[Suc_Cod]'; ";
            //echo $sql.'<br/>';
            break;
        case 22:
            $sql = "SELECT Emp_Cod, categorias.Cat_Des,item.Ite_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Pro_Obs,IF(Pro_Stk IS NULL,0,Pro_Stk)AS Pro_Stk,IF(Pro_Prp IS NULL,0,Pro_Prp)AS Pro_Prp, Stk_Can, Stk_Prp FROM producto
                    LEFT JOIN stock ON  producto.Pro_Cod=stock.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod 
            INNER JOIN adquisicio ON adquisicio.Adq_Cod=producto.Adq_Cod 
                    INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
                    WHERE $Par_Sql[0] AND adquisicio.Adq_Cor='B' $Par_Sql[1] $Par_Sql[2] ORDER BY Ite_Lar /*AND producto.Pro_Cod=1602*/";
            //echo $sql.'<br/>';
            break;
        case 23:
            /**
             * Consulta la cantidad del producto por fecha 0000-00-00 
             */
            $sql = " SELECT SUM(Kar_Can-Kar_Sal) as Stock,SUM(Kar_Ims-Kar_Ime) AS Saldo, "
                . "SUM((Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_ent
                            , SUM((Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_sal "
                . "FROM kardex_ie,iva  WHERE Kar_Est='A' AND Kar_Fec<'$Par_Sql[0] 00:00:00' AND Pro_Cod=$Par_Sql[1] AND iva.Iva_Cod= kardex_ie.Iva_Cod";
            //echo $sql.'<br>';
            return $sql;
        case 24:
            /**
             * Con esta sentencia consulto el movimiento del kardex con fechas 
             */
            $sql = "SELECT IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2','')))AS orden,
                                CONCAT(kardex_ie.Vet_Cod,'_',kardex_ie.Cop_Cod,'_',kardex_ie.Aju_Cod) AS Kar_Key,
                                /*IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',NULL))) AS Tipo,*/
                                IF( kardex_ie.Vet_Cod!=0,TIC2.Tic_Des,IF(kardex_ie.Cop_Cod!=0,TIC1.Tic_Des,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc,
                                IF( kardex_ie.Vet_Cod!=0,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(kardex_ie.Cop_Cod!=0,Cop_Num,IF(kardex_ie.Aju_Cod!=0,Aju_Sec,NULL))) AS Doc_Num,
                                CONCAT(IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),' - ',IF( kardex_ie.Vet_Cod!=0,IF(Vet_Obs IS NULL,'',Vet_Obs),IF(kardex_ie.Cop_Cod!=0,IF(Cop_Obs IS NULL, '',Cop_Obs),IF(kardex_ie.Aju_Cod!=0,IF(Aju_Det IS NULL,'',Aju_Det),'')))) AS Kar_Det,
                                kardex_ie.Vet_Cod,kardex_ie.Aju_Cod,kardex_ie.Cop_Cod,kardex_ie.Vnd_Cod,Gia_Cod,Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock , (Kar_Ims-Kar_Ime) AS Saldo , (Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_ent , (Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_sal 
                                FROM kardex_ie
                                INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
                                LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
                                LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
                                LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
                                LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
                                LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
                                LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                                LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                                LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                                LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                WHERE 
                    Kar_Est='A' AND 
                    Pro_Cod=$Par_Sql[2]  AND 
                    Kar_Fec BETWEEN '$Par_Sql[0] 00:00:00' AND '$Par_Sql[1] 23:59:59'
                ORDER BY Kar_Fec,orden,Kar_Hor";
            //echo $sql;
            return $sql;

        case 25:
            /**
             * Consulta de sucursales 
             */
            $sql = " SELECT * FROM sucursal WHERE Emp_Cod=$Par_Sql[0]";

            return $sql;
        case 26:

            $sql = "select bodega.Bod_Cod, bodega.Bod_Tip, bodega.Bod_Nom from bodega, sucursal, bodega_usuario 
                    where bodega.Suc_Cod=sucursal.Suc_Cod and bodega_usuario.bod_cod=bodega.bod_cod
                    and usu_cod=$_SESSION[Ses_Usu_Cod] and Emp_Cod = $_SESSION[Ses_Emp_Cod] and Bod_Est='A'";
            return $sql;
            break;

        case 27:
            $sql = "select bod_cod from bodega, sucursal where bodega.Suc_Cod=sucursal.Suc_Cod AND Emp_Cod = $_SESSION[Ses_Emp_Cod] and bod_tip='P'";
            return $sql;
            break;

        case 28:
            $sql = "select tia_des from tipo_ajus where tia_cod=$Par_Sql[0]";
            return $sql;
            break;

        case 29:
            $sql = "select bod_tip from bodega where bod_cod=$Par_Sql[0]";
            return $sql;
            break;
        case 30:
            $sql = "SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0] " . (empty($Par_Sql[1]) ? "AND cat.Cat_Tip='G'" : "AND cat.Cat_Tip='$Par_Sql[1]'");
            //echo $sql;
            break;

        case 31:
            $sql = "SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0]  AND cat.Cat_Rec='$Par_Sql[1]' " . (empty($Par_Sql[2]) ? "AND cat.Cat_Tip='D'" : "AND cat.Cat_Tip='$Par_Sql[2]'");

            //echo $sql;
            break;

        case 32:
            /**
             * Consulta la cantidad del producto por fecha 0000-00-00 
             */
            $sql = " SELECT SUM(Kar_Can-Kar_Sal) as Stock,SUM(Kar_Ims-Kar_Ime) AS Saldo, "
                . "SUM((Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_ent
                            , SUM((Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_sal "
                . "FROM kardex_ie,iva  WHERE Kar_Est='A' AND Pro_Cod=$Par_Sql[0] AND iva.Iva_Cod= kardex_ie.Iva_Cod";
            //echo $sql.'<br>';
            return $sql;
        case 33:
            /**
             * Con esta sentencia consulto el movimiento del kardex con fechas 
             */
            $sql = "SELECT IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2','')))AS orden,
                                CONCAT(kardex_ie.Vet_Cod,'_',kardex_ie.Cop_Cod,'_',kardex_ie.Aju_Cod) AS Kar_Key,
                                /*IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',NULL))) AS Tipo,*/
                                IF( kardex_ie.Vet_Cod!=0,TIC2.Tic_Des,IF(kardex_ie.Cop_Cod!=0,TIC1.Tic_Des,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc,
                                IF( kardex_ie.Vet_Cod!=0,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(kardex_ie.Cop_Cod!=0,Cop_Num,IF(kardex_ie.Aju_Cod!=0,Aju_Sec,NULL))) AS Doc_Num,
                                CONCAT(IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),' - ',IF( kardex_ie.Vet_Cod!=0,IF(Vet_Obs IS NULL,'',Vet_Obs),IF(kardex_ie.Cop_Cod!=0,IF(Cop_Obs IS NULL, '',Cop_Obs),IF(kardex_ie.Aju_Cod!=0,IF(Aju_Det IS NULL,'',Aju_Det),'')))) AS Kar_Det,
                                kardex_ie.Vet_Cod,kardex_ie.Aju_Cod,kardex_ie.Cop_Cod,kardex_ie.Vnd_Cod,Gia_Cod,Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock , (Kar_Ims-Kar_Ime) AS Saldo , (Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_ent , (Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_sal 
                                FROM kardex_ie
                                INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
                                LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
                                LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
                                LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
                                LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
                                LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
                                LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                                LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                                LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                                LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                WHERE 
                    Kar_Est='A' AND 
                    Pro_Cod=$Par_Sql[2]  AND 
                ORDER BY orden";
            //echo $sql;
            return $sql;
        case 34:
            $sql = "SELECT empresas.Emp_Cod, categorias.Cat_Des, if(marca.Mar_Des='NINGUNA', '', marca.Mar_Des) as Mar_Des,item.Ite_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Pro_Obs,IF(Pro_Stk IS NULL,0,Pro_Stk)AS Pro_Stk,IF(Pro_Prp IS NULL,0,Pro_Prp)AS Pro_Prp, Stk_Can, Stk_Prp FROM producto
                    LEFT JOIN stock ON  producto.Pro_Cod=stock.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod 
            INNER JOIN adquisicio ON adquisicio.Adq_Cod=producto.Adq_Cod 
                    INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
            INNER JOIN empresas ON categorias.Emp_Cod=empresas.Emp_Cod
            INNER JOIN sucursal ON empresas.Emp_Cod=sucursal.Emp_Cod
            INNER JOIN marca ON marca.Mar_Cod=producto.Mar_Cod
                    WHERE $Par_Sql[0] AND adquisicio.Adq_Cor='B' $Par_Sql[1] $Par_Sql[2] ORDER BY Ite_Lar/*AND producto.Pro_Cod=1602*/";
            //echo $sql.'<br/>';
            break;

        case 35:
            $sql = " SELECT * FROM consumo WHERE Emp_Cod=$Par_Sql[0]";
            return $sql;

        case 36:
            $sql = "SELECT consumo.con_des,producto.pro_cod,ajuste_kar.aju_cod, ajuste_kar.aju_num, item.ite_cor as nombre, det_ajustek.aju_can as cantidad, det_ajustek.aju_pru as precio, det_ajustek.aju_imp as total
                from ajuste_kar
                inner join det_ajustek on (ajuste_kar.aju_cod=det_ajustek.aju_cod)
                inner join consumo on (det_ajustek.con_cod = consumo.con_cod) 
                inner join producto on (det_ajustek.pro_cod=producto.pro_cod)
                inner join item on (producto.ite_cod=item.ite_cod)
                inner join empresas on (consumo.emp_cod=empresas.emp_cod)
                where empresas.emp_cod = '$Par_Sql[0]'  and ajuste_kar.Aju_Est = 'A' and det_ajustek.con_cod in(select con_cod from consumo where emp_cod='$Par_Sql[0]') and aju_fec between '$Par_Sql[2]' and '$Par_Sql[3]'
               group by  consumo.con_cod,producto.pro_cod order by consumo.Con_Des";
            return $sql;

        case 37:
            $sql = "SELECT consumo.con_des,producto.pro_cod,ajuste_kar.aju_cod, ajuste_kar.aju_num, item.ite_cor as nombre, det_ajustek.aju_can as cantidad, det_ajustek.aju_pru as precio, det_ajustek.aju_imp as total
                from ajuste_kar
                inner join det_ajustek on (ajuste_kar.aju_cod = det_ajustek.aju_cod)
                inner join consumo on (det_ajustek.con_cod = consumo.con_cod) 
                inner join producto on (det_ajustek.pro_cod = producto.pro_cod)
                inner join item on (producto.ite_cod = item.ite_cod)
                inner join empresas on (consumo.emp_cod = empresas.emp_cod)
                where empresas.emp_cod = '$Par_Sql[0]'  and ajuste_kar.Aju_Est = 'A' and det_ajustek.con_cod in(select con_cod from consumo where emp_cod ='$Par_Sql[0]') and aju_fec between '$Par_Sql[2]' and '$Par_Sql[3]'
               group by  producto.pro_cod,consumo.con_cod";
            return $sql;
        case 38:
            $sql = "SELECT producto.Pro_Cod, item.Ite_Lar as Ite_Lar,
                     producto.Pro_Obs, marca.Mar_Des as Mar_Des,
                     Count(*) as total, Sum(ventas_det.Vet_Can) as Veces,
                     Sum(ventas_det.Vet_Imp) as Cantidad 
                     FROM producto
                        inner join ventas_det on (producto.Pro_Cod = ventas_det.Pro_Cod)
                        inner join ventas on (ventas_det.Vet_Cod = ventas.Vet_Cod)
                        inner join item on (producto.Ite_Cod = item.Ite_Cod)
                        inner join marca on (producto.Mar_Cod = marca.Mar_Cod)
                        inner join categorias on (item.Cat_Cod = categorias.Cat_Cod)
                        inner join caja_aper on (ventas.Caj_Cod = caja_aper.Caj_Cod)
                        where categorias.Emp_Cod= $Par_Sql[0] AND ventas.Vet_Est='A'" .
                " $Par_Sql[1]" .
                " $Par_Sql[2]" .
                " $Par_Sql[5]" .
                " $Par_Sql[4]" .
                " group by ventas_det.Pro_Cod " .
                " $Par_Sql[3]";
            return $sql;
        case 39:
            $sql = "SELECT producto.Pro_Cod, producto.Pro_Bar as Pro_Bar, item.Ite_Lar as Ite_Lar, categorias.Cat_Des as Cat_Des, iva.Iva_Por as Iva_Por, adquisicio.Adq_Des as Adq_Des, stock.Stk_Can as Stock
                FROM producto
                    inner join iva on (producto.Iva_Cod = iva.Iva_Cod)
                    inner join item on (producto.Ite_Cod = item.Ite_Cod)
                    inner join categorias on (item.Cat_Cod = categorias.Cat_Cod)
                    inner join empresas on (categorias.Emp_Cod = empresas.Emp_Cod)
                    inner join adquisicio on (producto.Adq_Cod = adquisicio.Adq_Cod)
                    inner join stock on (producto.Pro_Cod = stock.Pro_Cod)
                        where empresas.Emp_Cod=$Par_Sql[0] " .
                " $Par_Sql[1] " .
                " $Par_Sql[2] " .
                " $Par_Sql[3] " .
                " $Par_Sql[4] " .
                " and stock.Stk_Can<=0 ";
            return $sql;
        case 40:
            $sql = "SELECT producto.Pro_Cod, producto.Pro_Bar as Pro_Bar, item.Ite_Lar as Ite_Lar, categorias.Cat_Des as Cat_Des, iva.Iva_Por as Iva_Por, adquisicio.Adq_Des as Adq_Des, precios.Pre_Pvp as Pre_Pvp
                FROM producto
                    inner join iva on (producto.Iva_Cod = iva.Iva_Cod)
                    inner join item on (producto.Ite_Cod = item.Ite_Cod)
                    inner join categorias on (item.Cat_Cod = categorias.Cat_Cod)
                    inner join empresas on (categorias.Emp_Cod = empresas.Emp_Cod)
                    inner join adquisicio on (producto.Adq_Cod = adquisicio.Adq_Cod)
                    inner join precios on (producto.Pro_Cod = precios.Pro_Cod)
                        where empresas.Emp_Cod=$Par_Sql[0] " .
                " $Par_Sql[1] " .
                " $Par_Sql[2] " .
                " $Par_Sql[3] " .
                " $Par_Sql[4] " .
                "and precios.Pre_Pvp<=0.0000";
            return $sql;
        case 41:
            $sql = "SELECT cliente.Cli_Cod as Cli_Cod, persona.Prs_Ced as Prs_Ced, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as Cliente, COUNT(ventas.Vet_Cod) as total, SUM(ventas_det.Vet_Imp) as Cantidad
                FROM cliente
                inner join persona on (cliente.Prs_Cod = persona.Prs_Cod)
                inner join ventas on (cliente.Cli_Cod = ventas.Cli_Cod)
                inner join ventas_det on (ventas.Vet_Cod = ventas_det.Vet_Cod)
                inner join caja_aper on (ventas.Caj_Cod = caja_aper.Caj_Cod)
                        where cliente.Emp_Cod=$Par_Sql[0] " .
                " $Par_Sql[2] " .
                " $Par_Sql[3] " .
                " $Par_Sql[4] " .
                " and ventas.Vet_Est = 'A' " .
                " group by ventas.Cli_Cod " .
                " $Par_Sql[1] ";
            return $sql;

            //Nuevas consultas

        case 42:
            $empCod = $Par_Sql[0];
            $filters =  $Par_Sql[5] . ' ' . $Par_Sql[4] . ' ' . $Par_Sql[6] . ' ' . $Par_Sql[7] . ' ' . $Par_Sql[8] . ' ' . $Par_Sql[9] . ' ' . $Par_Sql[10]  . ' ' . $Par_Sql[11];
            
            $orderPrincipal = "det_plan.Pld_Cdc ASC";
            $orderAdicional = trim(str_ireplace("ORDER BY", "", $Par_Sql[3]));
            $ORDER_BY = "ORDER BY " . $orderPrincipal . (!empty($orderAdicional) ? ", " . $orderAdicional : "");

            $groupBy = 'group by det_compra.Cop_Cod';
            $additionalSql = $Par_Sql[2];

            $sql = "SELECT com.Cop_Cod,com.Cop_Num ,com.Cop_Fec, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape)  as proveedor,  persona.Prs_Ced,
                com.Cop_Obs,consumo.Con_Des, tpc.Tic_Des, det_plan.Pld_Cdc, det_plan.Pld_Des,
                SUM(det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por/100) ) AS total,
                IF(ccpp_pagar.Cpp_Cod IS NULL,'Contado','Credito') AS Pago , CONCAT(iva.Iva_Por,' (%)') AS Iva_Por, 
                SUBSTRING(com.Cop_Num, 2, LOCATE( '-', com.Cop_Num, LOCATE( '-', com.Cop_Num) + 1) - 2) AS serie,
                /* se reemplaza estas dos lineas por las de abajo para acomodas el calculo de valores
                (det_compra.Cop_Imp* iva.Iva_Por/100) AS total_iva,
                det_compra.Cop_Imp AS subtotal,*/
                SUM( det_compra.Cop_Imp * iva.Iva_Por / 100) AS total_iva, 
                SUM( det_compra.Cop_Imp) AS subtotal,
                SUM(ROUND( IF(Tic_Sri=4,-1,1) * IF(Iva_Por = 0, det_compra.Cop_Imp, 0), 2)) AS Sub_0,
                SUM(ROUND( IF(Tic_Sri=4,-1,1) * IF(Iva_Por = 5, det_compra.Cop_Imp, 0), 2)) AS Sub_5,
                SUM(ROUND( IF(Tic_Sri=4,-1,1) * IF(Iva_Por = 8, det_compra.Cop_Imp, 0), 2)) AS Sub_8,
                SUM(ROUND( IF(Tic_Sri=4,-1,1) * IF(Iva_Por = 12, det_compra.Cop_Imp, 0), 2)) AS Sub_12,
                SUM(ROUND( IF(Tic_Sri=4,-1,1) * IF(Iva_Por != 0 AND Iva_Por != 5 AND Iva_Por != 8 AND Iva_Por != 12, det_compra.Cop_Imp, 0), 2)) AS Sub_15,
                sucursal.Suc_Cod, sucursal.Suc_Des

                FROM compras as com
                    INNER JOIN proveedore ON proveedore.Prv_Cod = com.Prv_Cod
                    INNER JOIN empresas ON empresas.Emp_Cod = proveedore.Emp_Cod
                    INNER JOIN tipo_compr as tpc ON tpc.Tic_Cod = com.Tic_Cod
                    LEFT JOIN consumo ON consumo.Con_Cod = com.Con_Cod
                    INNER JOIN det_compra ON det_compra.Cop_Cod = com.Cop_Cod
                    LEFT JOIN ccpp_pagar  ON ccpp_pagar.Cop_Cod = com.Cop_Cod
                    INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
                    LEFT JOIN det_plan ON det_compra.Pld_Cod = det_plan.Pld_Cod
                    INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
                    -- nueva union de la consulta
                    LEFT JOIN vendedor ON com.Vnd_Cod = vendedor.Vnd_Cod
                    LEFT JOIN puntos_imp ON vendedor.Pun_Cod = puntos_imp.Pun_Cod
                    LEFT JOIN sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod
                
                WHERE empresas.Emp_Cod = $empCod AND com.Cop_Est='A'
                
                $filters 
                $groupBy
                $additionalSql
                $ORDER_BY";
                //echo $sql;exit;
            return $sql;
        case 43:

            $sql = "SELECT 
                        producto.Pro_Cod,
                        det_compra.Cop_Cod, 
                        producto.Pro_Obs, 
                        det_compra.Cop_Imp, 
                        det_plan.Pld_Cdc, 
                        det_plan.Pld_Cod,
                        det_plan.Pld_Des,
                        SUM(det_compra.Cop_Imp * iva.Iva_Por / 100) AS iva
                    FROM det_compra
                        INNER JOIN det_plan ON det_compra.Pld_Cod = det_plan.Pld_Cod
                        INNER JOIN producto ON producto.Pro_Cod = det_compra.Pro_Cod
                        INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
                    WHERE 
                        det_compra.Cop_Cod = $Par_Sql[0]  GROUP BY  
                        det_plan.Pld_Cdc, 
                        det_plan.Pld_Des";
                    return $sql;

        case 44:
            $sql = "SELECT * FROM consumo WHERE Emp_Cod = $Par_Sql[0]";
            return $sql;

        case 45:
            $sql = "SELECT * FROM tipo_compr";
            return $sql;

        //Consulta para el reporte resumido de consumos
        case 46:

            $year =  date('Y', strtotime($Par_Sql[7]));
            $sql = "SELECT 
                det_plan.Pld_Cod,
                det_plan.Pld_Des,
                sucursal.Suc_Cod,
                sucursal.Suc_Des,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-01' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_enero,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-02' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_febrero,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-03' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_marzo,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-04' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_abril,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-05' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_mayo,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-06' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_junio,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-07' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_julio,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-08' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_agosto,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-09' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_septiembre,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-10' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_octubre,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-11' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_noviembre,
                SUM(CASE WHEN DATE_FORMAT(com.Cop_Fec, '%Y-%m') = '$year-12' THEN det_compra.Cop_Imp + (det_compra.Cop_Imp * iva.Iva_Por / 100) ELSE 0 END) AS total_diciembre
            
            FROM 
                compras AS com
                INNER JOIN proveedore ON proveedore.Prv_Cod = com.Prv_Cod
                INNER JOIN empresas ON empresas.Emp_Cod = proveedore.Emp_Cod
                INNER JOIN det_compra ON det_compra.Cop_Cod = com.Cop_Cod
                LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod = com.Cop_Cod
                INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
                LEFT JOIN det_plan ON det_compra.Pld_Cod = det_plan.Pld_Cod
                INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
                -- nueva union de consulta
                LEFT JOIN vendedor ON com.Vnd_Cod = vendedor.Vnd_Cod
                LEFT JOIN puntos_imp ON vendedor.Pun_Cod = puntos_imp.Pun_Cod
                LEFT JOIN sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod

            WHERE 
                empresas.Emp_Cod = $Par_Sql[0]
                AND com.Cop_Est = 'A'" .
                "$Par_Sql[1] " .
                "$Par_Sql[2] " .
                "$Par_Sql[3] " .
                "$Par_Sql[4] " .
                "$Par_Sql[5] " .
                "$Par_Sql[6] " .
                "$Par_Sql[8] " . "

            GROUP BY 
                det_plan.Pld_Cod

            HAVING 
                total_enero != 0 OR
                total_febrero != 0 OR
                total_marzo != 0 OR
                total_abril != 0 OR
                total_mayo != 0 OR
                total_junio != 0 OR
                total_julio != 0 OR
                total_agosto != 0 OR
                total_septiembre != 0 OR
                total_octubre != 0 OR
                total_noviembre != 0 OR
                total_diciembre != 0
            ORDER BY 
                det_plan.Pld_Cod;
            ";
            return $sql;

            //Cargar plan de cuentas

        case 47:
            $sql = "SELECT det_plan.* from det_plan
            INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod= det_plan.Pla_Cod
            WHERE Pld_Est='A' AND Emp_Cod= $Par_Sql[0]" .
                " $Par_Sql[1]";

            return $sql;
            case 48:

                $sql = "SELECT det_plan.Pld_Cod, 
                        Pld_Cdc, 
                        Pld_Des, 
                        Pld_Est,
                        -- Generar los saldos mensuales dinámicamente
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 1 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Enero,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 2 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Febrero,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 3 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Marzo,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 4 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Abril,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 5 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Mayo,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 6 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Junio,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 7 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Julio,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 8 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Agosto,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 9 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Septiembre,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 10 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Octubre,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 11 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Noviembre,
                        SUM(CASE WHEN MONTH(comprobantes.Com_Fec) = 12 THEN IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3)) ELSE 0 END) AS Diciembre,
                        -- Cálculo del saldo total
                        SUM(IF(Asi_Deh = 'D', ROUND(Asi_Val, 3), -ROUND(Asi_Val, 3))) AS total_general
                    FROM 
                        asientos  
                    INNER JOIN 
                        det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod                    
                    LEFT JOIN 
                        comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
                    WHERE  
                        comprobantes.Com_Est = 'A'	 		  
                        AND Pec_Cod = $Par_Sql[0]
                    GROUP BY 
                        det_plan.Pld_Cod
                    ORDER BY 
                        CAST(LEFT(Pld_Cdc, LENGTH(Pld_Cdc) - LENGTH(SUBSTRING_INDEX(Pld_Cdc, '.', -1))) AS CHAR) ASC,
                        CAST((SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0) AS DECIMAL);";
    
                return $sql;
                case 219:
                    $sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo, perio_cont.Pla_Cod FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod = $Par_Sql[0] ORDER BY Pec_Fei Desc";
                    return $sql;
                    break;
    }
    //echo $sql."<br/>";
    return $sql;
}
