<?php
/**
 * Dedicación laboral (dedica)
 */
function sentencias_dedica($id,$Par_Sql)
{
    switch ($id){
        case 1:
            $sql="INSERT INTO dedica_lab(Suc_Cod,Ded_Des,Ded_Hrs) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
        break;
        case 2:
            $sql="SELECT Ded_Cod,Ded_Des,Ded_Hrs FROM dedica_lab WHERE Ded_Est='A' AND Suc_Cod='$Par_Sql[0]'";
        break;
        case 3:
            $sql="UPDATE dedica_lab SET Ded_Des='$Par_Sql[1]',Ded_Hrs='$Par_Sql[2]' WHERE Ded_Cod='$Par_Sql[0]'";
        break;
        case 4:
            $sql="UPDATE dedica_lab SET Ded_Est='I' WHERE Ded_Cod='$Par_Sql[0]'";
        break;
        case 5:
            $sql="SELECT Ded_Cod FROM dedica_lab WHERE Ded_Cod='$Par_Sql[0]' AND Ded_Est='A'";
        break;
    }
    return $sql;
}


