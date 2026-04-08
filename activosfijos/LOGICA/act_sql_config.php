<?php
/**
 * CONFIGURACIÓN DE ACTIVOS FIJOS
 */
function sentencias_config($id,$Par_Sql)
{
    switch($id)
    {	
        //Insert en la tabla activo_porcent
        case 1:
            $sql = "INSERT INTO activo_porcent (Suc_Cod, Apr_Des, Apr_Por) VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
        break;
        //Insert en la tabla config_activo
        case 2:
            $sql = "INSERT INTO config_activo (Suc_Cod, Cfg_Ddp, Cfg_Por) VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
        break;
        //Select de la tabla config_activo
        case 3:
            $sql = "SELECT Suc_Cod,Cfg_Por FROM config_activo WHERE Suc_Cod='$Par_Sql[0]'";
        break;
        //Update de la tabla activo_porcent
        case 4:
            $sql = "UPDATE activo_porcent SET Apr_Des='$Par_Sql[1]',Apr_Por='$Par_Sql[2]' WHERE Apr_Cod='$Par_Sql[0]'";
        break;
        //Select de la tabla activo_porcent
        case 5:
            $sql = "SELECT Apr_Cod,Apr_Des,Apr_Por FROM activo_porcent WHERE Suc_Cod='$Par_Sql[0]' AND Apr_Est='A'";
        break;
        //Update para eliminación lógica de la tabla activo_porcent
        case 6:
            $sql = "UPDATE activo_porcent SET Apr_Est='I' WHERE Apr_Cod='$Par_Sql[0]'";
        break;
        //Update de la tabla config_activo
        case 7:
            $sql = "UPDATE config_activo SET Cfg_Ddp='$Par_Sql[1]',Cfg_Por='$Par_Sql[2]' WHERE Cfg_Cod='$Par_Sql[0]'";
        break;
        //Select de la tabla config_activo
        case 8:
            $sql = "SELECT Cfg_Cod,Cfg_Ddp,Cfg_Por FROM config_activo WHERE Suc_Cod='$Par_Sql[0]' AND Cfg_Est='A'";
        break;
        //Select de la tabla activo_deprecia, con el propósito de corroborar si ya se dio inicio al proceso de depreciación
        case 9:
            $sql = "SELECT activo_deprecia.Act_Cod 
                    FROM activo_deprecia,activo
                    WHERE activo.Suc_Cod='$Par_Sql[0]'";
        break;
    }
    return $sql;
}
