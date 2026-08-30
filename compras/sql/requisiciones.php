<?php
/**
 * Requisiciones
 */

function sentencias_requisiciones($id,$Par_Sql)
{
    //ChromePhp::log('SENTENCIAS SQL');
    switch ($id){
        case 1:
            if($Par_Sql["op_opciones"] == "c"){
                $filtro = " AND Prs_Ced LIKE '%{$Par_Sql['search']}%'";
            }else{
                $filtro = " AND (Prs_Nom LIKE '%{$Par_Sql['search']}%' OR Prs_Ape LIKE '%{$Par_Sql['search']}%')";
            }
            $sql = "SELECT Per_Cod, persona.Prs_Cod, Per_Car, Prs_Ced, Prs_Dir, Prs_Cor,
                    CONCAT(Prs_Nom,' ',Prs_Ape) AS Req_Nom
                    FROM personal
                    INNER JOIN persona ON (persona.Prs_Cod = personal.Prs_Cod) 
                    WHERE Emp_Cod = {$Par_Sql['Emp_Cod']} AND Per_Req = 1 AND Per_Est = 'A' $filtro";
            //ChromePhp::log('SQL',$sql);
        break;
        case 2:
            $sql = "SELECT Req_Cod, Emp_Cod, Usu_Cod, Req_Fec_Cre, Req_Fec_Ent, Per_Cod, Req_Num, 
                CONCAT(Prs_Nom,' ',Prs_Ape) AS Requisitor
                FROM requisiciones
                INNER JOIN persona ON (requisiciones.Per_Cod = persona.Per_Cod)
                WHERE Emp_Cod = {$Par_Sql['Emp_Cod']}";
            //ChromePhp::log('SQL',$sql);
        break;
    }
    return $sql;
}

