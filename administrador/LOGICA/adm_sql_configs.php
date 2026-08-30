<?php

/**
 * Created by PhpStorm.
 * User: jorge
 * Date: 4/13/2018
 * Time: 3:13 PM
 */
function sentencias_configs($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            //echo $sql.'<br/>';
            break;
        case 1:
            $sql = "SELECT *, COUNT(DISTINCT Suc_Cod)AS total FROM empresas INNER JOIN confi_fact ON confi_fact.Emp_Cod = empresas.Emp_Cod INNER JOIN sucursal ON sucursal.Emp_Cod = empresas.Emp_Cod WHERE empresas.Emp_Cod = '$Par_Sql[0]' GROUP BY empresas.Emp_Cod";
            //echo $sql.'<br/>';
            break;
        case 2:
            $sql = "UPDATE empresas SET Emp_Nom = '$Par_Sql[Emp_Nom]', Emp_Rco = '$Par_Sql[Emp_Rco]', Emp_Rep = '$Par_Sql[Emp_Rep]', Emp_Ren = '$Par_Sql[Emp_Ren]', Emp_Con = '$Par_Sql[Emp_Con]',   Emp_Rre='$Par_Sql[Emp_Rre]', Emp_Reg = '$Par_Sql[Emp_Reg]', Emp_Cnt = '$Par_Sql[Emp_Cnt]',Art_Calif = '$Par_Sql[Art_Calif]',Ret_Scom = '$Par_Sql[Ret_Scom]' WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
           // $sql = "UPDATE empresas SET Emp_Nom = '$Par_Sql[Emp_Nom]', Emp_Rco = '$Par_Sql[Emp_Rco]', Emp_Rep = '$Par_Sql[Emp_Rep]', Emp_Ren = '$Par_Sql[Emp_Rep]', Emp_Con = '$Par_Sql[Emp_Con]',   Emp_Rre='$Par_Sql[Emp_Rre]', Emp_Reg = '$Par_Sql[Emp_Reg]', Emp_Cnt = '$Par_Sql[Emp_Cnt]' WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            //echo $sql.'<br/>';
            break;
        case 3:
            $sql = "UPDATE confi_fact SET Cof_Con = '$Par_Sql[Cof_Con]',Cof_Fac = '$Par_Sql[Cof_Fac]',Cof_Ret = '$Par_Sql[Cof_Ret]', Cof_Gce = '$Par_Sql[Cof_Gce]', Cof_Stk='$Par_Sql[Cof_Stk]', Cof_Gcr='$Par_Sql[Cof_Gcr]', Cof_Stk_Neg='$Par_Sql[Cof_Stk_Neg]', Cof_Micro='$Par_Sql[Cof_Micro]',Cof_Rim='$Par_Sql[Cof_Rim]',Cof_Age='$Par_Sql[Cof_Age]', Cof_NegCam='$Par_Sql[Cof_NegCam]', Cof_Sld='$Par_Sql[Cof_Sld]' WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            //echo $sql.'<br/>';
            break;
        case 4:
            $sql = "SELECT * FROM sucursal INNER JOIN ciudad ON ciudad.Ciu_Cod = sucursal.Ciu_Cod WHERE sucursal.Emp_Cod = '$Par_Sql[0]'";
            break;
        case 5:
            $sql = "UPDATE sucursal SET Suc_Sri= '$Par_Sql[Suc_Sri]', Suc_Des= '$Par_Sql[Suc_Des]', Suc_Dir= '$Par_Sql[Suc_Dir]', Suc_Cor= '$Par_Sql[Suc_Cor]', Suc_Te1= '$Par_Sql[Suc_Te1]', Suc_Te2= '$Par_Sql[Suc_Te2]', Ciu_Cod = '$Par_Sql[Ciu_Cod]'  WHERE Suc_Cod = '$Par_Sql[Suc_Cod]'";
            break;

        case 6:
            $sql = "SELECT * FROM ciudad";
            break;
        case 7:
            $sql = "UPDATE empresas SET Emp_Log = '$Par_Sql[Emp_Log]' WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]' ";
            break;
            // Registrar el logo de las sucursales
        case 8:
            $sql = "UPDATE sucursal SET Suc_Log = '$Par_Sql[Suc_Log]' WHERE Suc_Cod = '$Par_Sql[Suc_Cod]'";
            break;
    }
    return $sql;
}
