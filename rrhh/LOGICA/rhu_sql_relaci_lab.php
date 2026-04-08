<?php
/**
 * Relación laboral (relaci)
 */
function sentencias_relaci($id,$Par_Sql)
{
    switch ($id){
        case 1:
            $sql="INSERT INTO relaci_lab(Suc_Cod,Reb_Des) VALUES('$Par_Sql[0]','$Par_Sql[1]')";
        break;
        case 2:
            $sql="SELECT Reb_Cod,Reb_Des FROM relaci_lab WHERE Reb_Est='A' AND Suc_Cod='$Par_Sql[0]'";
        break;
        case 3:
            $sql="UPDATE relaci_lab SET Reb_Des='$Par_Sql[1]' WHERE Reb_Cod='$Par_Sql[0]'";
        break;
        case 4:
            $sql="UPDATE relaci_lab SET Reb_Est='I' WHERE Reb_Cod='$Par_Sql[0]'";
        break;
        case 5:
            $sql="SELECT Reb_Cod FROM relaci_lab WHERE Reb_Cod='$Par_Sql[0]' AND Reb_Est='A'";
        break;
    }
    return $sql;
}

