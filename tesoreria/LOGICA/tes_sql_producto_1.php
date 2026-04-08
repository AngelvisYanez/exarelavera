<?php
/* Retorna consulta sql a ejecutarse * */
function sentencias_tes($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {
        case 0:
            $sql="";
            //echo $sql;
            break;
        case 1:
            $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0] ".(empty($Par_Sql[1])?"AND cat.Cat_Tip='D'":"AND cat.Cat_Tip='$Par_Sql[1]'");
            //echo $sql;
            break;
        case 2:
            $sql="SELECT Mar_Cod, Mar_Des FROM marca WHERE Emp_Cod = $Par_Sql[0] ORDER BY Mar_Des";
            //echo $sql;
            break;
        case 3:
            $sql="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Cor , adquisicio.Adq_Des, adquisicio.Adq_Est FROM adquisicio WHERE adquisicio.Adq_Est='A'";
            //echo $sql;
            break;
        case 4:
            $sql="SELECT Iva_Por, Iva_Cod, Iva_Sri FROM iva WHERE Iva_Est='A' ORDER BY Iva_Por";
            //echo $sql;
            break;
        case 5:
            $sql="SELECT Ubi_Cod,Ubi_Des FROM ubicacion WHERE Ubi_Est='A' AND Emp_Cod = $Par_Sql[0] ORDER BY Ubi_Cod ASC";
            //echo $sql;
            break;
        case 6:
            $sql="SELECT Uni_Cod,Uni_Des FROM unidad WHERE Uni_Est='A'";
            //echo $sql;
            break;
        case 7:
            $sql="SELECT Pre_Cod,Pre_Des FROM presentaci WHERE Pre_Est='A'";
            //echo $sql;
            break;
        case 8:
            $sql="SELECT Cat_Cod,Cat_Des,Cat_Cdc FROM categorias WHERE Cat_Cod='$Par_Sql[0]' AND categorias.Emp_Cod = $Par_Sql[1]";
            //echo $sql;
            break;
        case 9:
            $sql="SELECT Tpv_Cod,Tpv_Des,Tpv_Est,Tpv_Def FROM tipo_preci WHERE Tpv_Def='$Par_Sql[0]' AND Suc_Cod = $Par_Sql[1]";
            //echo $sql;
            break;
        case 10:
            $sql="INSERT INTO `item`(`Cat_Cod`,`Ite_Cor`,`Ite_Lar`,`Ite_Est`)VALUES($Par_Sql[Cat_Cod],'$Par_Sql[Ite_Cor]','$Par_Sql[Ite_Lar]','A')";
            //echo $sql;
            break;
        case 11:
            if(isset($Par_Sql['Pro_Tip'])) $Par_Sql['Pro_Tip']="'$Par_Sql[Pro_Tip]'"; else $Par_Sql['Pro_Tip']='NULL';
            $sql="INSERT INTO `producto`(`Ite_Cod`,`Mar_Cod`,`Iva_Cod`,`Pro_Ide`,`Pre_Cod`,`Pro_Obs`,`Pro_Tip`,`Pro_Est`,`Pro_Por`,`Adq_Cod`,`Ubi_Cod`,`Uni_Cod`,`Pro_Bar`,
            `Pro_Gen`,`Pro_Sec`,`Pro_Cdc`,`Pro_Uni`,`Pro_Dsc`,`Pro_Fec`,`Lin_Cod`, `Cod_Const`)
            VALUES($Par_Sql[Ite_Cod],$Par_Sql[Mar_Cod],$Par_Sql[Iva_Cod],$Par_Sql[Pro_Ide],$Par_Sql[Pre_Cod],'$Par_Sql[Pro_Obs]',$Par_Sql[Pro_Tip],'A',NULL,$Par_Sql[Adq_Cod],$Par_Sql[Ubi_Cod],$Par_Sql[Uni_Cod],'',
            'G',$Par_Sql[Pro_Sec],'$Par_Sql[Pro_Cdc]',$Par_Sql[Pro_Uni],$Par_Sql[Pro_Dsc],NULL,$Par_Sql[Lin_Cod],'$Par_Sql[Cod_Const]');";
            //echo $sql;
            break;
        case 12:
            $sql="UPDATE producto SET Pro_Bar='$Par_Sql[1]',Pro_Gen='$Par_Sql[2]' WHERE Pro_Cod=$Par_Sql[0]";	;
            //echo $sql;
            break;
        case 122:
            $sql="UPDATE producto SET Pro_Cod_Emp='$Par_Sql[1]',Pro_Gen_Emp='$Par_Sql[2]' WHERE Pro_Cod=$Par_Sql[0]";   ;
            //echo $sql;
            break;

        case 13:
            $sql="INSERT INTO stock(Stk_Can,Suc_Cod,Pro_Cod,Stk_Prp,Stk_Min,Stk_Max)VALUE($Par_Sql[Stk_Can],$Par_Sql[Suc_Cod],$Par_Sql[Pro_Cod],$Par_Sql[Stk_Prp],$Par_Sql[Stk_Min],$Par_Sql[Stk_Max])";
            //echo $sql;
            break;
        case 14:
            $sql="INSERT INTO precios(Pro_Cod,Pre_Pvp,Pre_Des,Suc_Cod,Tpv_Cod) VALUES ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]',$Par_Sql[3],'$Par_Sql[4]')";
            //echo $sql;
            break;
        case 15:
            $sql="SELECT Lin_Cod,Lin_Des,Lin_Abr FROM lineas WHERE Lin_Est='A' AND Emp_Cod = $Par_Sql[0] ";
            //echo $sql;
            break;
        case 16:
            $sql="SELECT (MAX(CAST(Pro_Ide AS DECIMAl))+1)AS siguiente FROM producto WHERE Lin_Cod = $Par_Sql[0]  /*AND Emp_Cod = $Par_Sql[1] */";
            //echo $sql;
            break;
        case 17:
            $sql="SELECT Suc_Cod FROM sucursal WHERE Emp_Cod = $Par_Sql[0]";
            //echo $sql;
            break;
        case 18://Busqueda de Proveedores con array
//            if(isset($Par_Sql["limits"])){
//                $Par_Sql["limits"]="ORDER BY Ite_Lar $Par_Sql[limits]";
//                $campos=" ";
//            }
//            else{$campos="COUNT(Pro_Cod) as total";$Par_Sql["limits"]="";}
            $sql="SELECT producto.Pro_Cod,/*CONCAT(Lin_Abr,LPAD(Pro_Ide,5,'0'))*/ CONCAT(Lin_Abr,SUBSTRING_INDEX(Cat_Cdc,'.',-1),LPAD(Pro_Ide,5,'0')) AS Cha_Cod,producto.Ite_Cod,Ite_Lar,Ite_Cor,Pro_Obs,Pro_Est,Cat_Des,Mar_Des,Uni_Des,IF(presentaci.Pre_Des IS NULL ,'NINGUNA',presentaci.Pre_Des) AS Pre_Des,Pro_Uni, Stk_Can,Stk_Prp,Ubi_Des,IF(Lin_Des IS NULL ,'NINGUNA',Lin_Des) AS Lin_Des,Pre_Pvp,ROUND(Pre_Pvp,2) AS RoundedPrice,Pro_Bar,
                IF(Iva_Por=0,'N','S')AS hasIva,CAST(Pre_Pvp+(IF(Iva_Por=0,0,Pre_Pvp*$Par_Sql[Iva_Por]/100))AS DECIMAL(10,2) )AS Pvp, Pro_Cod_Emp FROM producto
                INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                INNER JOIN marca ON producto.Mar_Cod=marca.Mar_Cod
                LEFT JOIN lineas ON producto.Lin_Cod=lineas.Lin_Cod
                INNER JOIN ubicacion ON producto.Ubi_Cod=ubicacion.Ubi_Cod
                LEFT JOIN presentaci ON presentaci.Pre_Cod=producto.Pre_Cod
                INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
                LEFT JOIN stock ON (stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod=$Par_Sql[Suc_Cod])
		LEFT JOIN precios ON (producto.Pro_Cod=precios.Pro_Cod AND precios.Suc_Cod=$Par_Sql[Suc_Cod])
                LEFT JOIN tipo_preci ON (tipo_preci.Tpv_Cod=precios.Tpv_Cod AND Tpv_Des='Standar')
                INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                WHERE Pro_Est='$Par_Sql[op_opciones]' AND precios.Suc_Cod=$Par_Sql[Suc_Cod] AND categorias.Emp_Cod=$Par_Sql[Emp_Cod] AND $Par_Sql[Filtros] GROUP BY producto.Pro_Cod $Par_Sql[Order] ";
            //echo $sql;
            break;
        case 19:
                $sql="SELECT (MAX(CAST(Pro_Ide AS DECIMAl))+1)AS siguiente FROM producto
                        INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                        WHERE item.Cat_Cod = $Par_Sql[0]  /*AND Emp_Cod = $Par_Sql[1] */ GROUP BY Cat_Cod ";
            //echo $sql;
            break;
        case 20:
                $sql="INSERT INTO lineas(Emp_Cod,Lin_Des,Lin_Abr) VALUES($Par_Sql[Emp_Cod],'$Par_Sql[Lin_Des]','$Par_Sql[Lin_Abr]');";
            //echo $sql;
            break;
        case 21:
                $sql="INSERT INTO marca(Emp_Cod,Mar_Des) VALUES($Par_Sql[Emp_Cod],'$Par_Sql[Mar_Des]');";
            //echo $sql;
            break;
        case 200: // Obtener productos de otra empresa para copiar
            $search_clause = "";
            if (!empty($Par_Sql['search'])) {
                $search_clause = "(item.Ite_Lar LIKE '%$Par_Sql[search]%' OR item.Ite_Cor LIKE '%$Par_Sql[search]%' OR producto.Pro_Obs LIKE '%$Par_Sql[search]%')";
            }
            
            $estado_clause = "";
            if ($Par_Sql['est_opciones'] == "a") {
                $estado_clause = "producto.Pro_Est = 'A'";
            } else {
                $estado_clause = "producto.Pro_Est = 'I'";
            }
            
            $categoria_origen_clause = "";
            if (!empty($Par_Sql['Cat_Cod_Origen'])) {
                $categoria_origen_clause = "item.Cat_Cod = '$Par_Sql[Cat_Cod_Origen]'";
            }
            
            $where_clauses = array();
            if (!empty($search_clause)) {
                $where_clauses[] = $search_clause;
            }
            if (!empty($estado_clause)) {
                $where_clauses[] = $estado_clause;
            }
            if (!empty($categoria_origen_clause)) {
                $where_clauses[] = $categoria_origen_clause;
            }
            $where_sql = implode(" AND ", $where_clauses);
            if (!empty($where_sql)) {
                $where_sql = "AND " . $where_sql;
            }

            $campos = empty($Par_Sql['limits']) ? " COUNT(producto.Pro_Cod) AS total" : "producto.Pro_Cod, producto.Ite_Cod, item.Ite_Lar, item.Ite_Cor, item.Cat_Cod, categorias.Cat_Des, producto.Pro_Obs, producto.Pro_Est, producto.Mar_Cod, marca.Mar_Des, producto.Adq_Cod, adquisicio.Adq_Des, producto.Iva_Cod, iva.Iva_Por, producto.Ubi_Cod, ubicacion.Ubi_Des, producto.Uni_Cod, unidad.Uni_Des, producto.Pre_Cod, presentaci.Pre_Des, producto.Pro_Uni, producto.Pro_Bar, producto.Pro_Sec, producto.Pro_Cdc, producto.Pro_Dsc, producto.Lin_Cod, lineas.Lin_Des, producto.Pro_Ide";
            $sql = "SELECT $campos
                    FROM producto
                    INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                    INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
                    LEFT JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                    LEFT JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                    LEFT JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
                    LEFT JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
                    LEFT JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                    LEFT JOIN presentaci ON (producto.Pre_Cod = presentaci.Pre_Cod)
                    LEFT JOIN lineas ON (producto.Lin_Cod = lineas.Lin_Cod)
                    WHERE (categorias.Emp_Cod = '$Par_Sql[Emp_Cod_Origen]') $where_sql $Par_Sql[limits];";
            return $sql;
            break;
        case 201: // Verificar si un producto ya existe en la empresa destino (por Ite_Lar y Mar_Cod)
            $sql = "SELECT producto.Pro_Cod FROM producto 
                    INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                    WHERE item.Ite_Lar = '$Par_Sql[Ite_Lar]' 
                    AND producto.Mar_Cod = $Par_Sql[Mar_Cod]
                    AND item.Cat_Cod = $Par_Sql[Cat_Cod_Destino]";
            return $sql;
            break;
        case 22:
                $sql="INSERT INTO ubicacion(Emp_Cod,Ubi_Des,Ubi_Obs,Ubi_Rec) VALUES($Par_Sql[Emp_Cod],'$Par_Sql[Ubi_Des]','$Par_Sql[Ubi_Obs]','".(empty($Par_Sql['Ubi_Rec'])?0:$Par_Sql['Ubi_Rec'])."');";
            //echo $sql;
            break;
        case 23:
                $sql="INSERT INTO categorias(Emp_Cod,Cat_Des,Cat_Tip,Cat_Rec,Cat_Cdc) VALUES($Par_Sql[Emp_Cod],'$Par_Sql[Cat_Des]','$Par_Sql[Cat_Tip]','".(empty($Par_Sql['Cat_Rec'])?0:$Par_Sql['Cat_Rec'])."','$Par_Sql[Cat_Cdc]');";
            //echo $sql;
            break;
        case 24:
                $sql="SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(Cat_Cdc, '.', -1)AS UNSIGNED)+1),1) AS next FROM categorias WHERE Cat_Rec=$Par_Sql[0]";
            //echo $sql;
            break;
        case 25:
                $sql="SELECT * FROM ice";
            //echo $sql;
            break;
        case 26:
                $sql="SELECT COUNT(Ite_Cod)AS total FROM item INNER JOIN categorias ON categorias.Cat_Cod=item.Cat_Cod WHERE Emp_Cod=$Par_Sql[0] AND UPPER(Ite_Lar)=UPPER('$Par_Sql[1]')";
            //echo $sql;
            break;
        case 266:
                $sql="SELECT COUNT(Pro_Cod) AS total FROM producto 
                INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                INNER JOIN categorias ON categorias.Cat_Cod=item.Cat_Cod
                WHERE Emp_Cod=$Par_Sql[0] AND UPPER(Pro_Cod_Emp)=UPPER('$Par_Sql[1]')";
            //echo $sql;
            break;
         case 27:
                $sql="UPDATE producto
                SET Pro_Est='$Par_Sql[Pro_Est]'
                WHERE Pro_Cod=$Par_Sql[Pro_Cod]";
                //echo $sql;
            break;
        case 28:
            $sql="SELECT * FROM producto
            INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
            WHERE Pro_Cod=$Par_Sql[0]";
            break;
        case 29:
            /* selecciona ivas */
            $sql="SELECT * FROM iva WHERE Iva_Por>0 AND ('$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin OR (DATE('$Par_Sql[0]')>=Iva_Ini AND Iva_Fin IS NULL) ) ORDER BY Iva_Por DESC"; //compras.Cop_Fec,
            //echo $sql."<br>";
            break;
        case 30:
            $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0]  AND cat.Cat_Rec='$Par_Sql[1]' ".(empty($Par_Sql[2])?"AND cat.Cat_Tip='D'":"AND cat.Cat_Tip='$Par_Sql[2]'");
                //ChromePhp::log($sql);
            //echo $sql;
            break;
        case 31:
                $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                    LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                    WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0] ".(empty($Par_Sql[1])?"AND cat.Cat_Tip='G'":"AND cat.Cat_Tip='$Par_Sql[1]'");
                //echo $sql;
                break;
        case 32:
            $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0] AND cat.Cat_Tip='D'";
                //ChromePhp::log($sql);
                break;
        case 33:
                $sql="SELECT * FROM mater_const WHERE Est_Const = 'A' ORDER BY Cod_Const ASC";
                //echo $sql;
                break;
        case 34:
                $sql="INSERT INTO mater_const(Cod_Const,Desc_Const,Est_Const) VALUES('$Par_Sql[Cod_Const]','$Par_Sql[Desc_Const]','A');";
            //echo $sql;
            break;  
    }
    //echo $sql."<br/>";
    return $sql;
}
?>