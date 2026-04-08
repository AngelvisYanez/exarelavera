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
 * @package tesoreria.LOGICA
 */
function sentencias_cons($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {
        case 1://Insert consumo
            $sql="INSERT INTO consumo (Emp_Cod,Con_Des,Con_Est) VALUES ('$Par_Sql[0]','$Par_Sql[1]','A')";
            break; 
        case 2://Update consumo
            $sql="UPDATE consumo SET Con_Des='$Par_Sql[1]' WHERE Con_Cod='$Par_Sql[0]' ";
            break;
        case 3://baja consumo
            $sql="DELETE FROM consumo WHERE Con_Cod='$Par_Sql[0]' ";
            break;
        case 4://busqueda de consumos        
            if(isset($Par_Sql["limits"])){
                $Par_Sql["limits"]="ORDER BY Con_Des $Par_Sql[limits]";$campos=" Con_Cod,Con_Des,IF(Con_Est='A','Activo','Inactivo') AS Con_Est ";
            }else{$campos="COUNT(Con_Cod) as total";$Par_Sql["limits"]="";}
            $sql="SELECT $campos FROM consumo WHERE Con_Des LIKE '%$Par_Sql[search]%' AND Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
            break;
        case 5://baja consumo
            $sql="UPDATE consumo SET Con_Est='$Par_Sql[1]' WHERE Con_Cod='$Par_Sql[0]' ";  
            break;
        case 6://listado consumos
            $sql="SELECT * FROM consumo WHERE Con_Est='A' AND Emp_Cod=$Par_Sql[0]";  
            //echo $sql.'<br>';
            break;
        case 7:  
            if($Par_Sql[4]==""){$campos="COUNT(producto.Pro_Cod) as total";}
            else{$campos="item.Ite_Cod,Ite_Est,Ite_Cor,Ite_Lar,categorias.Cat_Cod,Cat_Des,marca.Mar_Cod,Mar_Des, producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec ";}  
            if($Par_Sql[2]=="t") $Par_Sql[2]='';
            if($Par_Sql[2]=="s") $Par_Sql[2]='AND det_plan.Pla_Cod='.$Par_Sql[3];
            if($Par_Sql[2]=="n") $Par_Sql[2]="AND producto.Pro_Cod NOT IN(SELECT produ_plan.Pro_Cod From produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pla_Cod=$Par_Sql[3])";
            $sql= "SELECT $campos
                    FROM producto
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
                        INNER JOIN marca ON producto.Mar_Cod= marca.Mar_Cod ".
                        ($Par_Sql[2]!=''?"LEFT JOIN det_plan ON det_plan.Pld_Cod =( SELECT produ_plan.Pld_Cod FROM produ_plan WHERE produ_plan.Pro_Cod=producto.Pro_Cod LIMIT 1) ":'').
                    " WHERE item.Ite_Est='A' AND Pro_Est='A' AND (item.Ite_Lar LIKE '$Par_Sql[0]%' OR item.Ite_Lar LIKE '%$Par_Sql[0]%' OR item.Ite_Cor LIKE '%$Par_Sql[0]%' ) AND categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[2] ".($Par_Sql[2]!=''?($Par_Sql[4]==""?'':"GROUP BY Pro_Cod"):'ORDER BY Pro_Cod')." $Par_Sql[4] ";
            //echo $sql.'<br>';
            break;
        case 8:
		$sql="SELECT * FROM produ_plan 
                    INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod
                    LEFT JOIN consumo ON consumo.Con_Cod=produ_plan.Con_Cod
                    WHERE Pro_Cod='$Par_Sql[0]' AND Pla_Cod='$Par_Sql[1]' ".(empty($Par_Sql[2])?'':" AND Tip_Pld='$Par_Sql[2]'").(!empty($Par_Sql[3])&&($Par_Sql[2]=='G'||$Par_Sql[2]=='O')?" AND consumo.Con_Cod='$Par_Sql[3]'":'');
            //echo $sql."<br/>";
            break;
        case 9:

            if (!empty($Par_Sql[5])  && ($Par_Sql[5] == 'V')) {
                $digCnta = 1;
                $digCnta1 = 4;
                //$Par_Sql[5] = " AND CAST(det_plan.Pld_Cdc AS CHAR) LIKE '$digCnta%'";
                $Par_Sql[5] = " AND (CAST(det_plan.Pld_Cdc AS CHAR) LIKE '$digCnta%' OR CAST(det_plan.Pld_Cdc AS CHAR) LIKE '$digCnta1%')";
            } else { $Par_Sql[5] = ""; }

            if($Par_Sql[3]=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";}
            else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";}
            if($Par_Sql[4]==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
            else{
                $Par_Sql[4]="ORDER BY det_plan.Pld_Cod ".$Par_Sql[4];
                $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                        IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                        IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
            }
            $sql="SELECT $campos
                        FROM det_plan 
                        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                        INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                        INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
                        LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                        LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                        WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
                        AND $search AND Pec_Cod =$Par_Sql[2] $Par_Sql[5]
                        AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
            //echo $sql."<br/>";
            break; 
        case 10:
            $sql_cons=($Par_Sql[2]=='G'||$Par_Sql[2]=='0')&&!empty($Par_Sql[3])?" AND Con_Cod=$Par_Sql[3]":'';
            $sql="DELETE FROM produ_plan WHERE Pro_Cod='$Par_Sql[0]' AND Pld_Cod='$Par_Sql[1]' AND Tip_Pld='$Par_Sql[2]' $sql_cons";
            //echo $sql."<br/>";
            break; 
        case 11:
            $sql="INSERT INTO produ_plan(Pro_Cod,Pld_Cod,Mod_Cod,Tip_Pld,Con_Cod,Car_Int) VALUES ('$Par_Sql[0]','$Par_Sql[1]',0,'$Par_Sql[2]',".(empty($Par_Sql[3])?'NULL':$Par_Sql[3]).",".(empty($Par_Sql[3])?'0':$Par_Sql[3]).")";
           //echo $sql."<br/>";
            break; 
        case 12:
            if($Par_Sql[4]==""){$campos="COUNT(producto.Pro_Cod) as total";}
                else{$campos="item.Ite_Cod,Ite_Est,Ite_Cor,Ite_Lar,categorias.Cat_Cod,Cat_Des,marca.Mar_Cod,Mar_Des, producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,'Yes' AS act1 ";}  
                if($Par_Sql[2]=="t") $Par_Sql[2]='';
                if($Par_Sql[2]=="s") $Par_Sql[2]='AND det_plan.Pla_Cod='.$Par_Sql[3];
                if($Par_Sql[2]=="n") $Par_Sql[2]="AND producto.Pro_Cod NOT IN(SELECT produ_plan.Pro_Cod From produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pla_Cod=$Par_Sql[3])";
                $sql= "SELECT $campos
			FROM producto
                            INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                            INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
                            INNER JOIN marca ON producto.Mar_Cod= marca.Mar_Cod ".
                            ($Par_Sql[2]!=''?"LEFT JOIN det_plan ON det_plan.Pld_Cod =( SELECT produ_plan.Pld_Cod FROM produ_plan WHERE produ_plan.Pro_Cod=producto.Pro_Cod LIMIT 1) ":'').
			" WHERE item.Ite_Est='A' AND Pro_Est='A' AND categorias.Cat_Cod=$Par_Sql[0] AND categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[2] ".($Par_Sql[2]!=''?($Par_Sql[4]==""?'':"GROUP BY Pro_Cod"):'ORDER BY Pro_Cod')." $Par_Sql[4] ";
            //echo $sql."<br/>";
            break;  
        case 13:
            $sql="SELECT Cat_Cod,produ_plan.Tip_Pld,det_plan.*,produ_plan.Con_Cod,Con_Des FROM produ_plan 
                    INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod
                    INNER JOIN producto ON produ_plan.Pro_Cod=producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    LEFT JOIN consumo ON consumo.Con_Cod=produ_plan.Con_Cod
                    WHERE Cat_Cod='$Par_Sql[0]' AND Pla_Cod='$Par_Sql[1]' ".(empty($Par_Sql[2])?'':"AND Tip_Pld='$Par_Sql[2]'").(!empty($Par_Sql[3])&&($Par_Sql[2]=='G'||$Par_Sql[2]=='O')?" AND consumo.Con_Cod='$Par_Sql[3]'":'').
                    " GROUP BY Cat_Cod,produ_plan.Pld_Cod,Tip_Pld,produ_plan.Con_Cod ORDER BY Tip_Pld ";
             //echo $sql."<br/>";
            break;    
              //Otros Categorias
        case 14:
            $sql_cons=($Par_Sql[1]=='G'||$Par_Sql[1]=='O')&&!empty($Par_Sql[2])?" AND Con_Cod=$Par_Sql[2]":'';
            $sql="DELETE FROM produ_plan WHERE Pro_Cod='$Par_Sql[0]'  AND Tip_Pld='$Par_Sql[1]' $sql_cons ";
            //echo $sql."<br/>";
            break;     
        case 15:
            $sql = "SELECT 
                    perio_cont.Pec_Cod,perio_cont.Pec_Fei,perio_cont.Pec_Fef,perio_cont.Pec_Est,Year(Pec_Fei) AS Periodo,perio_cont.Pla_Cod
                  FROM
                    plan_cuenta
                    INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                  WHERE
                    Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0]
                  ORDER BY
                    Pec_Fei DESC";
            //echo $sql."<br/>";
            break; 
        case 16:
            $sql="SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
                LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
                WHERE cat.Cat_Est='A' AND cat.Cat_Tip='D' AND cat.Emp_Cod=$Par_Sql[0]";
            //echo $sql."<br/>";
            break;   
    }
    //echo $sql."<br/>";
    return $sql;    
}



