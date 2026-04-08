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
function sentencias_cos($id,$Par_Sql)
{
    $sql="";
    switch($id){
        case 0:
            $sql="";
            //echo $sql.'<br/>';
            break;
        case 1:
            $sql = "SELECT perio_cont.Pec_Cod, perio_cont.Pec_Fei, perio_cont.Pec_Fef, perio_cont.Pec_Est, Year(Pec_Fei) AS Periodo, perio_cont.Pla_Cod FROM plan_cuenta
                  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                  WHERE Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0] ORDER BY Pec_Fei DESC";
            //echo $sql;
            break; 
        case 2:
            if($Par_Sql['op_opciones']=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[search]%'";}
            else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";}
            if($Par_Sql['limits']==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
            else{
                $Par_Sql['limits']="ORDER BY det_plan.Pld_Cod ".$Par_Sql['limits'];
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
                        WHERE plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod] AND plan_cuenta.Pla_Est='A' 
                        AND $search AND Pec_Cod =$Par_Sql[Pec_Cod] 
                        AND det_plan.Pld_Tip = 'D' $Par_Sql[limits]";
            //echo $sql.'<br/>';
            break;
        case 3:
            $fecha=  explode('-',$Par_Sql[1]);            
            $sql="SELECT asientos.Asi_Cod, asientos.Asi_Deh, sum(asientos.Asi_Val) as Total FROM asientos                           
                LEFT JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
                WHERE  
                    comprobantes.Com_Est = 'A'				  
                    $Par_Sql[0]  
                    AND comprobantes.Com_Fec BETWEEN '$fecha[0]-01-01' AND '$Par_Sql[1]'
                    GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";    
            //echo $sql;
            return $sql;  
        case 4:
            $fecha=  explode('-',$Par_Sql[2]);
            $sql="SELECT IFNULL(SUM(det_compra.Cop_Can),0) AS Can FROM det_compra
                    INNER JOIN compras ON det_compra.Cop_Cod=compras.Cop_Cod
                    INNER JOIN producto ON det_compra.Pro_Cod=producto.Pro_Cod
                    INNER JOIN produ_plan ON produ_plan.Pro_Cod=producto.Pro_Cod
                    WHERE Cop_Est='A' AND Cop_Fec BETWEEN '$fecha[0]-01-01' AND '$Par_Sql[2]' AND Tip_Pld='$Par_Sql[1]' AND produ_plan.Pld_Cod='$Par_Sql[0]'";    
            //echo $sql;
            return $sql;   
        case 5:
            $fecha=  explode('-',$Par_Sql[2]);
            $sql="SELECT IFNULL(SUM(ventas_det.Vet_Can),0) AS Can FROM ventas_det
                    INNER JOIN ventas ON ventas_det.Vet_Cod=ventas.Vet_Cod
                    INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod
                    INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                    INNER JOIN produ_plan ON produ_plan.Pro_Cod=producto.Pro_Cod
                    INNER JOIN det_plan ON produ_plan.Pld_Cod=det_plan.Pld_Cod
                    WHERE Vet_Est='A' AND Caj_Fec BETWEEN '$fecha[0]-01-01' AND '$Par_Sql[2]' AND Tip_Pld='$Par_Sql[1]' AND produ_plan.Pld_Cod='$Par_Sql[0]'";    
            //echo $sql;
            return $sql;     
        case 6:
            if($Par_Sql['op_opciones']=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[search]%'";}
            else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";}
            if($Par_Sql['limits']==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
            else{
                $Par_Sql['limits']="ORDER BY det_plan.Pld_Cod ".$Par_Sql['limits'];
                $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                        IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                        IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, COUNT(child.Pld_Cod)AS Hijos ";
            }
            $sql="SELECT $campos
                        FROM det_plan 
                        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                        INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                        INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
                        INNER JOIN det_plan AS child ON child.Pld_Rec=det_plan.Pld_Cod
                        LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                        LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                        WHERE plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod] AND plan_cuenta.Pla_Est='A' 
                        AND $search AND Pec_Cod =$Par_Sql[Pec_Cod] 
                        AND det_plan.Pld_Tip = 'G' AND child.Pld_Tip='D' GROUP BY det_plan.Pld_Cod $Par_Sql[limits]";
            //echo $sql.'<br/>';
            break;    
        case 7:
            $sql="SELECT Pld_Cod FROM det_plan WHERE Pld_Rec=$Par_Sql[0];";
            //echo $sql.'<br/>';
            break;
    }
    //echo $sql."<br/>";
    return $sql;  
}
