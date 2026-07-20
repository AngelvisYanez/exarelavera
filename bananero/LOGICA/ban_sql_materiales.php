<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2015-07-22
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package facturacion.LOGICA
 */

function sentencias_produ($id, $Par_Sql)
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
            else $campos = " item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,		  adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec ";
            if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
            else $search = " item.Ite_Lar  LIKE '%$Par_Sql[0]%' ";
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
                    INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod 
                  WHERE 
                  $search AND Pro_Est='A' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql.'<br/>';
            break;
        case 2:
            /**
             * Con esta sentencia consulto producto y stock
             */
            if ($Par_Sql[4] == '') $campos = " COUNT(item.Ite_Cod) AS total ";
            else $campos = " item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,Stk_Can  ";
            if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
            else $search = " item.Ite_Lar  LIKE '%$Par_Sql[0]%' ";
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
                    LEFT JOIN stock ON (stock.Pro_Cod=producto.Pro_Cod" . ($Par_Sql[5] != '' ? " AND  stock.Suc_Cod=$Par_Sql[5] " : '') . ")
                  WHERE 
                  $search AND Pro_Est='A' AND producto.Pro_Cod!='$Par_Sql[3]' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[4]";
            //echo $sql.'<br/>';
            break;
        case 3:
            /**
             * Con esta sentencia consulto producto y stock
             */
            $sql = "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Pro_Uni,Pro_Cdc,Pro_Dsc,Pre_Pvp,Stk_Can
                    FROM producto
                    INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                    INNER JOIN marca ON producto.Mar_Cod=marca.Mar_Cod
                    INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                    INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                    INNER JOIN ubicacion ON ubicacion.Ubi_Cod= producto.Ubi_Cod   
                    INNER JOIN unidad ON unidad.Uni_Cod= producto.Uni_Cod  
                    INNER JOIN adquisicio ON adquisicio.Adq_Cod= producto.Adq_Cod  
                    INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
                    LEFT JOIN stock ON (stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod=$Par_Sql[1])
                    WHERE precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 4:
            /**
             * Consulta la cantidad del producto por fecha 0000-00-00 
             */
            $sql = " SELECT SUM(Kar_Can-Kar_Sal) as Stock,SUM(Kar_Ims-Kar_Ime) AS Saldo, "
                . "SUM((Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_ent
                    , SUM((Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_sal "
                . "FROM kardex_ie,iva  WHERE Kar_Est='A' AND Kar_Fec<'$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1] AND iva.Iva_Cod= kardex_ie.Iva_Cod";
            //echo $sql.'<br>';
            //echo $sql.'<br/>';
            break;
        case 5:
            $sql = "INSERT INTO mesclas(`Pro_Cod`,`Mes_Nom`,`Mes_Des`,`Mes_Res`,`Mes_Max`,`Bam_Cod`,`Mes_Tip`)
                        VALUES($Par_Sql[Pro_Cod],'$Par_Sql[Mes_Nom]','$Par_Sql[Mes_Des]',1,$Par_Sql[Mes_Max],$Par_Sql[Bam_Cod],'$Par_Sql[Mes_Tip]');";
            //echo $sql.'<br/>';
            break;
        case 6:
            $sql = "INSERT INTO `mesclas_det`(`Mes_Int`,`Pro_Cod`,`Mes_Cod`,`Mes_Can`)
                    VALUES($Par_Sql[conteo],$Par_Sql[Pro_Cod],$Par_Sql[Mes_Cod],$Par_Sql[Mes_Can]);
                ";
            //echo $sql.'<br/>';
            break;
        case 7:
            $sql = "SELECT * FROM mesclas WHERE Pro_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 8:
            $sql = "SELECT Ite_Lar,Ite_Cor,mesclas_det.*,Stk_Can,Stk_Max,Stk_Min,Stk_Prp,Uni_Des,IF(Stk_Prp IS NULL,0,(Stk_Can*Stk_Prp))AS Stk_Tot,Iva_Cod FROM mesclas_det 
                INNER JOIN mesclas ON mesclas.Mes_Cod=mesclas_det.Mes_Cod
                INNER JOIN producto ON (mesclas_det.Pro_Cod = producto.Pro_Cod)
                INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                LEFT JOIN stock ON (stock.Pro_Cod=mesclas_det.Pro_Cod AND Suc_Cod=$Par_Sql[1])
                WHERE mesclas.Pro_Cod=$Par_Sql[0]
                ORDER BY mesclas_det.Mes_Cod";
            //echo $sql.'<br/>';
            break;
        case 9:
            $sql = "INSERT INTO orden_produccion(`Mes_Cod`,`Pro_Cod`,`Ord_Res`,`Ord_Max`,Ord_Obs,Ord_Cou,Cli_Cod,Ord_Mes,Ord_Fec)
                        VALUES($Par_Sql[Mes_Cod],$Par_Sql[Pro_Cod],$Par_Sql[Ord_Res],$Par_Sql[Ord_Max],'$Par_Sql[Ord_Obs]',$Par_Sql[Ord_Cou],$Par_Sql[Cli_Cod],'$Par_Sql[Ord_Mes]','$Par_Sql[Ord_Fec]');";
            //echo $sql.'<br/>';
            break;
        case 10:
            $sql = "INSERT INTO `orden_det`(`Ord_Cod`,`Ord_Int`,`Pro_Cod`,`Ord_Can`,`Pro_Cou`)
                    VALUES($Par_Sql[Ord_Cod],$Par_Sql[conteo],$Par_Sql[Pro_Cod],$Par_Sql[Mes_Can],$Par_Sql[Stk_Prp]);
                ";
            //echo $sql.'<br/>';
            break;
        case 11:
            $sql = "SELECT orden_produccion.*,Mes_Nom,Mes_Max,Ite_Lar,Uni_Des,Ord_Mes,Ord_Fec,IF(Prs_Ape IS NULL,'Ninguno',CONCAT(Prs_Ape,' ',Prs_Nom))AS cliente FROM orden_produccion 
                INNER JOIN mesclas ON (mesclas.Mes_Cod = orden_produccion.Mes_Cod)
                INNER JOIN producto ON (orden_produccion.Pro_Cod = producto.Pro_Cod)
                INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                LEFT JOIN cliente ON cliente.Cli_Cod=orden_produccion.Cli_cod
                LEFT JOIN persona ON cliente.Prs_Cod=persona.Prs_cod
                WHERE Ord_Cod=$Par_Sql[0]
                    ";
            //echo $sql.'<br/>';
            break;
        case 12:
            $sql = "SELECT Ite_Lar,Ite_Cor,orden_det.*,Uni_Des FROM orden_det                 
                INNER JOIN producto ON (orden_det.Pro_Cod = producto.Pro_Cod)
                INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)                
                WHERE orden_det.Ord_Cod=$Par_Sql[0]
                ";
            //echo $sql.'<br/>';
            break;
        case 13:
            $sql = "SELECT * FROM mesclas_det WHERE mesclas_det.Mes_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 14: //Busqueda de Proveedores con array
            if ($Par_Sql['op_opciones'] == "d") {
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
            break;
        case 15:
            /* 
            * Consulta el proveedor reservado para la contabilización
            */
            $sql = "SELECT compra_prov.Prv_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom FROM compra_prov, proveedore, persona WHERE compra_prov.Prv_Cod = proveedore.Prv_Cod AND persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[0]";
            break;
        case 16:
            /* 
            * Consulta el cliente reservado para la caja diaria 
            */
            $sql = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND 
								vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
            //echo $caja_clien_180;
            break;
        case 17:
            /* 
            * Consulta el tipo de ajustes
            */
            $sql = "SELECT * FROM tipo_ajus WHERE Emp_Cod=$Par_Sql[0]";
            break;
        case 18:
            /* 
            * insertar ajustes dee kardex
            */
            $sql = "INSERT INTO ajuste_kar (Tia_Cod,Vnd_Cod,Prv_Cod,Aju_Fec,Aju_Hor,Aju_Det,Aju_Obs)"
                . " VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]' ) ";
            //echo '<br>'.$sql;        
            break;
        case 19:
            /* 
            * insertar ajustes dee kardex
            */
            $sql = "INSERT INTO det_ajustek (Aju_Cod,Pro_Cod,Aju_Can,Aju_Pru,Aju_Imp)"
                . " VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4]) ";
            //echo '<br>'.$sql;        
            break;
        case 20:
            /**
             * Inserta datos en el kardex
             */
            $sql = "INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],$Par_Sql[15])";
            break;
        case 21:
            /* 
            * Consulta el stock
            */
            $sql = "SELECT * FROM stock WHERE Pro_Cod=$Par_Sql[0] AND Suc_Cod=$Par_Sql[1]";
            break;
        case 22:
            /* 
            * Consulta el stock
            */
            $sql = "UPDATE stock SET Stk_Can=$Par_Sql[2],Stk_Prp=$Par_Sql[3] WHERE Pro_Cod=$Par_Sql[0] AND Suc_Cod=$Par_Sql[1]";
            break;
        case 23:
            /* 
            * Consulta las mesclas
            */
            $sql = "SELECT bnm.Bam_Nom,if(mesclas.Mes_Tip='C','Caja','Mat. Chico') as tp_mt,Mes_Cod,mesclas.Pro_Cod,producto.Ite_Cod,Mes_Nom,Mes_Des,Ite_Lar,Ite_Cor,Mes_Max FROM mesclas
                        INNER JOIN producto ON producto.Pro_Cod=mesclas.Pro_Cod
                        left join banano_marca as bnm on(bnm.Bam_Cod=mesclas.Bam_Cod)
                        INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                        WHERE 1=1 AND $Par_Sql[0] and (mesclas.Mes_Tip='C' or mesclas.Mes_Tip='M');";
            //echo $sql;
            break;
        case 24:
            /* 
            * Consulta las ordened
            */
            $sql = "SELECT * FROM orden_produccion
                    INNER JOIN mesclas ON mesclas.Mes_Cod=orden_produccion.Mes_Cod
                    INNER JOIN producto ON producto.Pro_Cod=mesclas.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    INNER JOIN cliente ON cliente.Cli_Cod=orden_produccion.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                    WHERE 1=1 AND $Par_Sql[0] ";
            break;
        case 25:
            $sql = "SELECT Ite_Lar,Ite_Cor,mesclas_det.*,Stk_Can,Stk_Max,Stk_Min,Stk_Prp,Uni_Des,IF(Stk_Prp IS NULL,0,(Stk_Can*Stk_Prp))AS Stk_Tot,Iva_Cod FROM mesclas_det 
                INNER JOIN mesclas ON mesclas.Mes_Cod=mesclas_det.Mes_Cod
                INNER JOIN producto ON (mesclas_det.Pro_Cod = producto.Pro_Cod)
                INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                LEFT JOIN stock ON (stock.Pro_Cod=mesclas_det.Pro_Cod AND Suc_Cod=$Par_Sql[1])
                WHERE mesclas.Mes_Cod=$Par_Sql[0]
                ORDER BY mesclas_det.Mes_Cod";
            //echo $sql.'<br/>';
            break;
        case 26:
            $sql = "SELECT Ite_Lar,Ite_Cor,orden_det.*,Stk_Can,Stk_Max,Stk_Min,Stk_Prp,Uni_Des,IF(Stk_Prp IS NULL,0,(Stk_Can*Stk_Prp))AS Stk_Tot,Iva_Cod,(Ord_Can*Pro_Cou) AS Pro_Tot  FROM orden_det 
                INNER JOIN orden_produccion ON orden_produccion.Ord_Cod=orden_det.Ord_Cod
                INNER JOIN producto ON (orden_produccion.Pro_Cod = producto.Pro_Cod)
                INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                LEFT JOIN stock ON (stock.Pro_Cod=orden_det.Pro_Cod AND Suc_Cod=$Par_Sql[1])
                WHERE orden_produccion.Ord_Cod=$Par_Sql[0]
                ORDER BY orden_produccion.Ord_Cod";
            //echo $sql.'<br/>';
            break;
        case 27:
            // $sql="SELECT * from banano_marca where Emp_Cod = $_SESSION[Ses_Emp_Cod] and Bam_Est = 'A';";
            $sql = "SELECT * from mesclas where Pro_Cod = $Par_Sql[0] and Mes_Est = 'A';";
            return $sql;
        case 28:
            $sql = "SELECT * from banano_marca where Emp_Cod = $_SESSION[Ses_Emp_Cod] and Bam_Est = 'A';";
            return $sql;



        case 29:
            $sql = "SELECT Ciu_Cod,Ciu_Des,Pro_Nom,Pas_Nom FROM ciudad
            INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod
            INNER JOIN pais ON pais.Pas_Cod=ciudad.Pas_Cod 
            WHERE Ciu_Est='A' AND Ciu_Des IS NOT NULL";
            return $sql;
    }
    //echo $sql."<br/>".$id."<br/>";
    return $sql;
}
