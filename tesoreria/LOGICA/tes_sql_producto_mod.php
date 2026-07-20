<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 */


function sentencias_pro($id,$Par_Sql)
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
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0] ".(empty($Par_Sql[1])?"AND cat.Cat_Tip='G'":"AND cat.Cat_Tip='$Par_Sql[1]'");
            //ChromePhp::log($sql);
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
            $sql="SELECT Iva_Por, Iva_Cod FROM iva WHERE Iva_Est='A' ORDER BY Iva_Por";
            //echo $sql;
            break;
        case 5:
            $sql="SELECT Ubi_Cod, Ubi_Des FROM ubicacion WHERE Ubi_Est='A' AND Emp_Cod = $Par_Sql[0] ORDER BY Ubi_Cod ASC";
            //echo $sql;
            break;
        case 6:
            $sql="SELECT Uni_Cod, Uni_Des FROM unidad WHERE Uni_Est='A'";
            //echo $sql;
            break;
        case 7:
            $sql="SELECT Pre_Cod, Pre_Des FROM presentaci WHERE Pre_Est='A'";
            //echo $sql;
            break;
        case 8:
            $sql="SELECT Cat_Cod, Cat_Des, Cat_Cdc FROM categorias WHERE Cat_Cod='$Par_Sql[0]' AND categorias.Emp_Cod = $Par_Sql[1]";
            //echo $sql;
            break;
        case 9:
            $sql="SELECT Tpv_Cod, Tpv_Des, Tpv_Est, Tpv_Def FROM tipo_preci WHERE Tpv_Def='$Par_Sql[0]' AND Suc_Cod = $Par_Sql[1]";
            //echo $sql;
            break;
        case 10:
            $sql="INSERT INTO `item`(`Cat_Cod`,`Ite_Cor`,`Ite_Lar`,`Ite_Est`)VALUES($Par_Sql[Cat_Cod],'$Par_Sql[Ite_Cor]','$Par_Sql[Ite_Lar]','A')";
            //echo $sql;
            break;
        case 11:
            if(isset($Par_Sql['Pro_Tip'])) $Par_Sql['Pro_Tip']="'$Par_Sql[Pro_Tip]'"; else $Par_Sql['Pro_Tip']='NULL';
            $sql="INSERT INTO `producto`(`Ite_Cod`,`Mar_Cod`,`Iva_Cod`,`Pro_Ide`,`Pre_Cod`,`Pro_Obs`,`Pro_Tip`,`Pro_Est`,`Pro_Por`,`Adq_Cod`,`Ubi_Cod`,`Uni_Cod`,`Pro_Bar`,
            `Pro_Gen`,`Pro_Sec`,`Pro_Cdc`,`Pro_Uni`,`Pro_Dsc`,`Pro_Fec`,`Lin_Cod`)
            VALUES($Par_Sql[Ite_Cod],$Par_Sql[Mar_Cod],$Par_Sql[Iva_Cod],$Par_Sql[Pro_Ide],$Par_Sql[Pre_Cod],'$Par_Sql[Pro_Obs]',$Par_Sql[Pro_Tip],'A',NULL,$Par_Sql[Adq_Cod],$Par_Sql[Ubi_Cod],$Par_Sql[Uni_Cod],'',
            'G',$Par_Sql[Pro_Sec],'$Par_Sql[Pro_Cdc]',$Par_Sql[Pro_Uni],$Par_Sql[Pro_Dsc],NULL,$Par_Sql[Lin_Cod] );";
            //echo $sql;
            break;
        case 12:
            $sql="UPDATE producto SET Pro_Bar='$Par_Sql[1]',Pro_Gen='$Par_Sql[2]' WHERE Pro_Cod=$Par_Sql[0]";   ;
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
        case 18://Busqueda de Productos            
            $sql="SELECT precios.Pre_Cod as precio_cod, precios.Pre_Pvp,Pro_Dsc, producto.Pro_Cod,producto.Pro_Bar,producto.Pro_Gen,iva.Iva_Por,producto.Iva_Cod,producto.Pre_Cod,producto.Pro_Obs,producto.Uni_Cod,producto.Ubi_Cod,IF(producto.Lin_Cod IS NULL,'NULL',producto.Lin_Cod) AS Lin_Cod,categorias.Cat_Cod,adquisicio.Adq_Cod,marca.Mar_Cod,CONCAT(Lin_Abr,SUBSTRING_INDEX(Cat_Cdc,'.',-1),LPAD(Pro_Ide,5,'0')) AS Cha_Cod,producto.Ite_Cod,Ite_Lar,Ite_Cor,Pro_Obs,Cat_Des,Mar_Des,Uni_Des,IF(presentaci.Pre_Des IS NULL ,'NINGUNA',presentaci.Pre_Des) AS Pre_Des,Pro_Uni,Stk_Can,Stk_Prp,Ubi_Des,IF(Lin_Des IS NULL ,'NINGUNA',Lin_Des) AS Lin_Des, Pre_Pvp as Precio1 FROM producto
                INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                INNER JOIN marca ON producto.Mar_Cod=marca.Mar_Cod
                INNER JOIN precios ON precios.Pro_Cod = producto.Pro_Cod AND precios.Pre_Est='A'
                INNER JOIN sucursal ON sucursal.Suc_Cod = precios.Suc_Cod
                INNER JOIN iva ON iva.Iva_Cod = producto.Iva_Cod
                LEFT JOIN lineas ON producto.Lin_Cod=lineas.Lin_Cod
                INNER JOIN ubicacion ON producto.Ubi_Cod=ubicacion.Ubi_Cod
                LEFT JOIN presentaci ON presentaci.Pre_Cod=producto.Pre_Cod
                INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
                LEFT JOIN stock ON (stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod=$Par_Sql[Suc_Cod])
                WHERE Pro_Est='A' AND categorias.Emp_Cod=$Par_Sql[Emp_Cod] AND $Par_Sql[Filtros] GROUP by producto.Pro_Cod $Par_Sql[Order] LIMIT 0, 250";
            //ChromePhp::log($sql);
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
                $sql="SELECT COUNT(item.Ite_Cod)AS total FROM item INNER JOIN categorias ON categorias.Cat_Cod=item.Cat_Cod 
                INNER JOIN producto ON producto.Ite_Cod = item.Ite_Cod WHERE Emp_Cod=$Par_Sql[0] AND producto.Pro_Cod <> $Par_Sql[2] AND UPPER(Ite_Lar)=UPPER('$Par_Sql[1]')";
            break;
        case 27:
                $sql="UPDATE producto
                SET Mar_Cod=$Par_Sql[Mar_Cod], Iva_Cod=$Par_Sql[Iva_Cod], Ice_Int=$Par_Sql[Ice_Int], Pro_Obs='$Par_Sql[Pro_Obs]', Adq_Cod=$Par_Sql[Adq_Cod], Ubi_Cod=$Par_Sql[Ubi_Cod], 
                Uni_Cod=$Par_Sql[Uni_Cod], Pro_Bar='$Par_Sql[Pro_Bar]',Pro_Dsc='$Par_Sql[Pro_Dsc]',Pro_Gen='$Par_Sql[Pro_Gen]',Pre_Cod=$Par_Sql[Pre_Cod], Pro_Uni=$Par_Sql[Pro_Uni], Lin_Cod=$Par_Sql[Lin_Cod]
                WHERE Pro_Cod=$Par_Sql[Pro_Cod]";
            break;
        case 28:
                $sql="UPDATE item
                        SET Ite_Lar='$Par_Sql[Ite_Lar]',Ite_Cor='$Par_Sql[Ite_Cor]',Cat_Cod='$Par_Sql[Cat_Cod]' 
                        WHERE Ite_Cod=$Par_Sql[Ite_Cod]";
            break;
        case 29:
            $sql="UPDATE precios SET Pre_Pvp=$Par_Sql[Pre_Pvp] WHERE Pre_Cod=$Par_Sql[precio_cod] ";
            break;
        case 30: 
                $sql= "SELECT Tpv_Cod, Tpv_Des FROM tipo_preci WHERE Suc_Cod = '$Par_Sql[0]';";
            break;
        case 467:
            $sql= "SELECT
                        precios.Pre_Pvp, precios.Pre_Cod, tipo_preci.Tpv_Des, precios.Pre_Fec, Pre_Est, Pre_Ini, Pre_Fin, tipo_preci.Tpv_Cod, 
                        precios.Pre_Por, precios.Pre_Com, precios.Pro_Uti
                   FROM item, marca, producto, iva,precios, tipo_preci 
                   WHERE precios.Tpv_Cod=tipo_preci.Tpv_Cod
                   AND marca.Mar_Cod= producto.Mar_Cod 
                   AND producto.Ite_Cod = item.Ite_Cod 
                   AND iva.Iva_Cod = producto.Iva_Cod 
                   AND producto.Pro_Cod = precios.Pro_Cod 
                   AND item.Ite_Cod=producto.Ite_Cod 
                   AND producto.Pro_Cod = '$Par_Sql[Pro_Cod]'
                   AND precios.Suc_Cod =  '$Par_Sql[sucursal]'
                   and precios.Pre_Est = 'A'
                   ORDER BY precios.Pre_Cod DESC;";
            return $sql;
            break;
        case 31: 
            $sql= "INSERT INTO tipo_preci(Suc_Cod, Tpv_Des, Tpv_Def) VALUES($Par_Sql[Suc_Cod],'$Par_Sql[Tpv_Des]','N');";
        break;

        case 32:
            $sql= "INSERT INTO precios(Pro_Cod,Pre_Com,Pre_Fec,Pre_Fin,Pre_Ini,Pre_Por,Pre_Pvp,Pro_Uti,Suc_Cod,Tpv_Cod,Pre_Des)
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]','$Par_Sql[9]','$Par_Sql[10]')";
            //ChromePhp::log($sql);
            return $sql;
        break;

        case 322:
            $sql= "UPDATE precios SET Pre_Com='$Par_Sql[1]', Pre_Fec='$Par_Sql[2]', Pre_Fin='$Par_Sql[3]', Pre_Ini='$Par_Sql[4]', Pre_Por='$Par_Sql[5]', Pre_Pvp='$Par_Sql[6]', Pro_Uti='$Par_Sql[7]', Suc_Cod='$Par_Sql[8]', Tpv_Cod='$Par_Sql[9]', Pre_Des='$Par_Sql[10]'
                 WHERE Pre_Cod = '$Par_Sql[11]'";
            //ChromePhp::log($sql);
            return $sql;
        break;

        case 33:
            $sql= "UPDATE precios SET Pre_Est='I' WHERE Pre_Cod='$Par_Sql[0]'";
            return $sql;
        break;
        case 34:
            $sql = "SELECT Tpv_Cod, Tpv_Des FROM tipo_preci 
            WHERE Suc_Cod='$Par_Sql[0]'";
        break;
        case 35:
            $sql = "UPDATE tipo_preci set Tpv_Des='$Par_Sql[0]' where Tpv_Cod = '$Par_Sql[1]'";
            return $sql;
        break;
        case 36:
            $sql = "SELECT Pro_Cod, Pre_Des, Pre_Pvp, Tpv_Cod from precios where Pre_Est='A' AND Pro_Cod = $Par_Sql[0]";
        break;
        case 37:
            $sql = "SELECT Tpv_Des, Tpv_Cod from tipo_preci where Tpv_Est='A' AND Suc_Cod = $Par_Sql[0]";
        break;
        case 38:
            $sql="INSERT INTO precios(Suc_Cod, Pro_Cod, Pre_Pvp, Pre_Des, Pre_Est, Tpv_Cod) VALUES($Par_Sql[Suc_Cod],$Par_Sql[Pro_Cod],$Par_Sql[Pre_Pvp],'$Par_Sql[Pre_Des]','A',$Par_Sql[Tpv_Cod])";
            //ChromePhp::log($sql);
        break;
        case 39:
            $sql="UPDATE precios SET Pre_Pvp='$Par_Sql[3]' WHERE Suc_Cod = '$Par_Sql[0]' AND Pro_Cod = '$Par_Sql[1]' AND Tpv_Cod = '$Par_Sql[2]'";
            //ChromePhp::log($sql);
        break;
        case 40: 
            $sql="SELECT precios.Tpv_Cod, precios.Pre_Des FROM precios, tipo_preci
                WHERE precios.Pro_Cod = '$Par_Sql[0]' AND precios.Tpv_Cod = '$Par_Sql[1]' AND precios.Suc_Cod = '$Par_Sql[2]' group by '$Par_Sql[0]'";
                //ChromePhp::log($sql);
            return $sql;
        break;
        case 41:
            $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0]  AND cat.Cat_Rec='$Par_Sql[1]' ".(empty($Par_Sql[2])?"AND cat.Cat_Tip='D'":"AND cat.Cat_Tip='$Par_Sql[2]'");
                //echo $sql;
        break;
        case 42:
            $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0] ".(empty($Par_Sql[1])?"AND cat.Cat_Tip='D'":"AND cat.Cat_Tip='$Par_Sql[1]'");
            //ChromePhp::log($sql);
            break;
        case 43:
        $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
            LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
            WHERE cat.Cat_Est='A' AND cat.Emp_Cod=$Par_Sql[0] ".(empty($Par_Sql[1])?"AND cat.Cat_Tip='D'":"AND cat.Cat_Tip='$Par_Sql[1]'");
            //ChromePhp::log($sql);
            break;
    }
    return $sql;
}
?>