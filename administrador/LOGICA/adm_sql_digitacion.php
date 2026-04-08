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
 * @package tesoreria.LOGICA
 */
function sentencias_ven($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {
        case 0:
            $sql="";
            //echo $sql.'<br/>';
            break;
        case 1:
            /* Con esta sentencia compras, ventas y asientos(conteos) */
            $fechas=" BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1] '"; 
            $sql="SELECT Prs.Prs_Cod,CONCAT(Prs_Ape,' ',Prs_Nom)AS Usuario, 
                    IFNULL((SELECT COUNT(Com_Cod) FROM comprobantes INNER JOIN usuarios ON usuarios.Usu_Cod=comprobantes.Usu_Cod WHERE Com_Gen='M' AND usuarios.Prs_Cod=Prs.Prs_Cod AND Com_Sys $fechas GROUP BY Prs_cod),0) AS TotalCom,
                    IFNULL((SELECT COUNT(Cop_Cod) FROM compras INNER JOIN vendedor ON vendedor.Vnd_Cod=compras.Vnd_Cod WHERE vendedor.Prs_Cod=Prs.Prs_Cod AND Cop_Sys $fechas GROUP BY Prs_cod),0) AS TotalCop,
                    IFNULL((SELECT COUNT(Vet_Cod) FROM ventas INNER JOIN vendedor ON vendedor.Vnd_Cod=ventas.Vnd_Cod WHERE vendedor.Prs_Cod=Prs.Prs_Cod AND Vet_Sys $fechas GROUP BY Prs_cod),0) AS TotalVet
                 FROM usuarios
                 INNER JOIN persona AS Prs ON usuarios.Prs_Cod=Prs.Prs_Cod
                 WHERE Usu_Tip != 'C' AND Prs_Int='S'
                 GROUP BY Prs.Prs_Cod
                 ORDER BY Usuario DESC";
            //echo $sql.'<br/>';
            break;
        case 2:
            /* Con esta bases master */
            $sql="SELECT DISTINCT Dat_Dis FROM data ".(isset($Par_Sql[0])&&trim($Par_Sql[0])!=''?"WHERE Emp_Cod=$Par_Sql[0]":'');
            //echo $sql.'<br/>';
            break;
        case 3:
            /* Con esta usuarios */
            $sql="SELECT Prs_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom)AS Usuario, 0 AS TotalCom, 0 AS TotalCop, 0 AS TotalVet
                 FROM persona 
                 WHERE Prs_Int='S'                 
                 ORDER BY Usuario DESC";
            //echo $sql.'<br/>';
            break;
        case 4:
            /* Con esta ventas */
            $fechas=" BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'"; 
            $sql="SELECT COUNT(Vet_Cod) AS Conteo
                FROM ventas 
                INNER JOIN vendedor ON vendedor.Vnd_Cod=ventas.Vnd_Cod
                INNER JOIN persona ON persona.Prs_Cod=vendedor.Prs_Cod
                 WHERE Vet_Sys $fechas 
                 AND (Prs_Ced='$Par_Sql[2]') /* AND Vet_Est='A' */
                 GROUP BY persona.Prs_Cod";
            //echo $sql.'<br/>';
            break;
        case 5:
            /* Con esta compras */
            $fechas=" BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'"; 
            $sql="SELECT COUNT(Cop_Cod) AS Conteo
                    FROM compras 
                    INNER JOIN vendedor ON vendedor.Vnd_Cod=compras.Vnd_Cod 
                    INNER JOIN persona ON persona.Prs_Cod=vendedor.Prs_Cod
                    WHERE Cop_Sys $fechas
                     AND (Prs_Ced='$Par_Sql[2]') /* AND Cop_Est='A' */
                    GROUP BY  persona.Prs_cod";
            //echo $sql.'<br/>';
            break;
        case 6:
            /* Con esta comprobantes */
            $fechas=" BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'"; 
            $sql="SELECT COUNT(Com_Cod) AS Conteo FROM comprobantes 
                INNER JOIN usuarios ON usuarios.Usu_Cod=comprobantes.Usu_Cod 
                INNER JOIN persona ON persona.Prs_Cod=usuarios.Prs_Cod
                    WHERE Com_Gen='M' AND Com_Sys $fechas 
                    AND (Prs_Ced='$Par_Sql[2]') /* AND Com_Est='A' */
                    GROUP BY persona.Prs_cod";
            //echo $sql.'<br/>';
            break;
        case 7:
            /* Con esta comprobantes */          
            $sql="SELECT * FROM empresas WHERE Emp_Est='A';";
            //echo $sql.'<br/>';
            break;
            
        // nuevo reporte gerencial
        case 8:
            /* Con esta compras */          
            $sql="SELECT COUNT(compras.Cop_Cod)AS total,MAX(Cop_Sys)AS last FROM compras INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod WHERE Emp_Cod=$Par_Sql[0] AND Cop_Est='$Par_Sql[1]' AND Cop_Sys BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]';";
            //echo $sql.'<br/>';
            break;  
        case 9:
            /* Con esta ventas */          
            $sql="SELECT COUNT(ventas.Vet_Cod)AS total,MAX(Vet_Sys)AS last FROM ventas INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod WHERE Emp_Cod=$Par_Sql[0] AND Vet_Est='$Par_Sql[1]' AND Vet_Sys BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]';";
            //echo $sql.'<br/>';
            break;  
        case 10:
            /* Con esta comprobantes */          
            $sql="SELECT COUNT(comprobantes.Com_Cod)AS total,MAX(Com_Sys)AS last FROM comprobantes 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod 
                INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod 
                WHERE Emp_Cod=$Par_Sql[0] ".($Par_Sql[1]!=''?"AND Com_Est='$Par_Sql[1]'":'')." AND Com_Sys BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]' ".(isset($Par_Sql[4])?$Par_Sql[4]:'').";";
            //echo $sql.'<br/>';
            break;
        case 11:
            /* Con esta cheques */          
            $sql="SELECT COUNT(comprobantes.Com_Cod)AS total,MAX(Com_Sys)AS last FROM cheques 
                INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod 
                INNER JOIN comprobantes ON asientos.Com_Cod=comprobantes.Com_Cod 
                INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod 
                INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod 
                WHERE Emp_Cod=$Par_Sql[0] ".($Par_Sql[1]!=''?"AND Com_Est='$Par_Sql[1]'":'')." AND Com_Sys BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]' ".(isset($Par_Sql[4])?$Par_Sql[4]:'').";";
            //echo $sql.'<br/>';
            break;
            
    }
    //echo $sql."<br/>";
    return $sql;  
}
