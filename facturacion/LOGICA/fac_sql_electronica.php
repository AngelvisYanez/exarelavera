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
function sentencias_elect($id,$Par_Sql)
{
    $sql="";
    switch($id){
        case 0:
            $sql="";
            //echo $sql.'<br/>';
            break;
        case 1:
            $sql="SELECT * FROM data INNER JOIN empresas ON empresas.Emp_Cod=data.Emp_Cod WHERE empresas.Emp_Cod=$Par_Sql[0];";
            //echo $sql.'<br/>';
            break;
        case 2:
            $sql="SELECT * FROM cliente WHERE cliente.Prs_Cod=$Par_Sql[0] AND Emp_Cod=$Par_Sql[1]";
            //echo $sql.'<br/>';
            break;
        case 3:
            $sql="SELECT IF(Tic_Sri=4,'NOTASC','VENTAS') AS type, IF(Tic_Sri=4,'NOTAS DE CREDITO','FACTURAS') AS Tipo, 'ventas' AS tabla, CONCAT(Suc_Sri,'-',Pun_Sri) AS Doc_Ser, 'Vet_Sri' AS campo1, 'Vet_Aut' AS campo2,  'Vet_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, Caj_Fec AS Doc_Fec, Vet_Num AS Doc_Num, Vet_Cod AS Doc_Cod, Vet_Aut AS Doc_Aut, Vet_Xml AS Doc_Xml, Vet_Sri AS Doc_Sri FROM ventas 
                    INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                    INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod                    
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=ventas.Tic_Cod
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
                    INNER JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                    WHERE Vet_Est='A' AND TRIM(coalesce(Vet_Xml, ''))<>'' AND cliente.Cli_Cod=$Par_Sql[1] AND cliente.Emp_Cod='$Par_Sql[0]' AND $Par_Sql[2] ORDER BY Caj_Fec;";
            //echo $sql.'<br/>';
            break;
        case 4:
            $sql="SELECT 'RETENC' AS type, 'RETENCIONES' AS Tipo, 'retencion' AS tabla, 'Ret_Sri' AS campo1,CONCAT(Suc_Sri,'-',Pun_Sri) AS Doc_Ser,  'Ret_Aut' AS campo2, 'Ret_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, Ret_Fec AS Doc_Fec, Ret_Num AS Doc_Num, Ret_Cod AS Doc_Cod, Ret_Aut AS Doc_Aut, Ret_Xml AS Doc_Xml, Ret_Sri AS Doc_Sri FROM retencion 
                INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod  
                INNER JOIN autorizaci ON autorizaci.Aut_Cod=retencion.Aut_Cod
                INNER JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod
                INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                WHERE Ret_Est='A' AND TRIM(coalesce(Ret_Xml, ''))<>'' AND proveedore.Emp_Cod='$Par_Sql[0]' AND proveedore.Prv_Cod='$Par_Sql[1]' $Par_Sql[2] ORDER BY Ret_Fec;";
            //echo $sql.'<br/>';
            break;
        case 5:
            $sql="SELECT * FROM empresas WHERE Emp_Cod=$Par_Sql[0];";
            //echo $sql.'<br/>';
            break;
        case 6:
            $sql="SELECT * FROM proveedore WHERE proveedore.Prs_Cod=$Par_Sql[0] AND Emp_Cod=$Par_Sql[1]";
            //echo $sql.'<br/>';
            break;
        case 7:
            $sql="SELECT * FROM persona WHERE persona.Prs_Cod=$Par_Sql[0];";
            //echo $sql.'<br/>';
            break;
    }
    //echo $sql."<br/>";
    return $sql;  
}
