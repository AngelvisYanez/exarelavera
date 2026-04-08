<?php
    
    /**
    * Author : Asael Tello Barcia
    */
    function sentencias_rep($id,$Par_Sql)
    {
        $sql = "";
        switch($id)
        {
            case 1: // GET ALL                
//                if ($Par_Sql[filtro] == "0"){$filtro ="";}
//                if ($Par_Sql[filtro] != "0" && $Par_Sql[filtro] != "N"){ $filtro = "AND retencion.Ret_Est = '$Par_Sql[filtro]'";}
//                if ($Par_Sql[filtro] == "N"){$filtro =" AND retencion.Ret_Num = $Par_Sql[numero]";}
                $sql = "SELECT 
                            det_compra.Pro_Cod,
                            Ite_Lar,
                            Sum(Cop_Can)as Cant,
                            Pro_Prp,
                            AVG(Cop_Pru-((Cop_Pru * Cop_Can)*(compras.Cop_Des/100)))as prom,
                            CAST( SUM((Cop_Pru-((Cop_Pru * Cop_Can)*(compras.Cop_Des/100)))*Cop_Can) as decimal(20,2))as tot,
                            CAST( IF(Iva_Por = 0, AVG(IF(Tic_Sri=4,-1,1)*(Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100)), '0') AS decimal(20,2))AS Sub0, 
                            CAST( SUM(IF(Iva_Por != 0, IF(Tic_Sri=4,-1,1)*(Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100), '0')) AS decimal(20,2))AS Sub12, 
                            CAST( SUM( IF(Tic_Sri=4,-1,1)*( (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */ +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0)) /* ICE */ )*(1+iva.Iva_Por/100)/* IVA */ ) AS decimal(20,2)) AS total, 
                            stock.Stk_Can,
                            Sum(Cop_Can)*Pro_Prp as mult
                        FROM compras 
                            INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod) 
                            INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                            INNER JOIN producto ON (producto.Pro_Cod = det_compra.Pro_Cod)
                            INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                            INNER JOIN stock ON (producto.Pro_Cod = stock.Pro_Cod) 
                            INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod) 
                            INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) 
                            INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod) 
                        WHERE proveedore.Emp_Cod = $Par_Sql[Emp_Cod]  AND Cop_Fec BETWEEN '$Par_Sql[desde]' AND '$Par_Sql[hasta]' AND Cop_Est='A' GROUP BY det_compra.Pro_Cod ";
                break;
        }   
        return $sql;
    }
?>