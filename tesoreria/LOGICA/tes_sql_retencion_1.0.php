<?php
    
    /**
    * Author : Asael Tello Barcia
    */
    function sentencias_ret($id,$Par_Sql)
    {
        $sql = "";
        switch($id)
        {
            case 1: // GET ALL                
                if ($Par_Sql[filtro] == "0"){$filtro ="";}
                if ($Par_Sql[filtro] != "0" && $Par_Sql[filtro] != "N"){ $filtro = "AND retencion.Ret_Est = '$Par_Sql[filtro]'";}
                if ($Par_Sql[filtro] == "N"){$filtro =" AND retencion.Ret_Num = $Par_Sql[numero]";}
                $sql = "SELECT
                        `persona`.`Prs_Cod`,
                        `persona`.`Prs_Ced`,
                        `proveedore`.`Prv_Cod`,
                        `compras`.`Cop_Cod`,
                        `compras`.`Ciu_Cod`,
                        `retencion`.`Ret_Cod`,
                        `retencion`.`Vnd_Cod`,
                        `retencion`.`Tic_Cod`,
                        LPAD(`retencion`.`Ret_Num`,9,0) as Ret_Num,
                        `retencion`.`Ret_Fec`,
                        `retencion`.`Ret_Con`,
                        IF(`retencion`.`Ret_Est` = 'A', 'Activo', 'Inactivo') as Ret_Est,
                        `proveedore`.`Emp_Cod`,
                        IF(`compras`.`Cop_Est` = 'E','-',concat(Prs_Ape,' ',Prs_Nom)) as Prv_Com,
                        (select CONCAT(Prs_Ape,' ',Prs_Nom) FROM persona WHERE Prs_Cod = (select Prs_Cod FROM vendedor WHERE Vnd_Cod = retencion.Vnd_Cod)) as vendedor
                      FROM
                        `retencion`
                        LEFT JOIN `compras` ON `retencion`.`Cop_Cod` = `compras`.`Cop_Cod`
                        LEFT JOIN `proveedore` ON `proveedore`.`Prv_Cod` = `compras`.`Prv_Cod`
                        LEFT JOIN `persona` ON `persona`.`Prs_Cod` = `proveedore`.`Prs_Cod`
                      WHERE
                        `proveedore`.`Emp_Cod` = $Par_Sql[Emp_Cod] $filtro";
                break;
        }   
		
        return $sql;
    }
?>