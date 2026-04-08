<?php
/**
 * Recursos Humanos (rrhh)
 */
function sentencias_rrhh($id,$Par_Sql)
{
    switch ($id){
        //Select que permite extraer todas las áreas para formar el árbol
        case 1:
            $sql = "SELECT Are_Cod,Are_Des,CAST(CONCAT('A_',Are_Cod) AS CHAR) AS id,'#' AS parent,Are_Des AS text,'A' AS type
                    FROM areas_rrhh
                    WHERE Emp_Cod='$Par_Sql[0]' AND Are_Est='A'";
        break;
        //Select que extrae todos los departamentos incluyen información que indica a que área pertenece
        case 2:
            $sql = "SELECT areas_rrhh.Are_Cod,Are_Des,Dep_Cod,Dep_Des,Dep_Cod AS id,CAST(IF(Dep_Rec=0,CONCAT('A_',areas_rrhh.Are_Cod),Dep_Rec) AS CHAR) AS parent,CONCAT(Dep_Cdc,' - ',Dep_Des) AS text,IF(Dep_Rec=0,'D','SD') AS type
                    FROM departamen,areas_rrhh 
                    WHERE departamen.Emp_Cod='$Par_Sql[0]' AND departamen.Are_Cod=areas_rrhh.Are_Cod AND Dep_Est='A'";
        break;
        //Insert para registrar un área
        case 3:
            $sql = "INSERT INTO areas_rrhh(Are_Des,Emp_Cod) VALUES('$Par_Sql[0]','$Par_Sql[1]')";
            break;
        //Permite seleccionar el número mayor de secuencia
        case 4:
            $sql = "SELECT MAX(Dep_Cdc) AS max FROM departamen WHERE Are_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND Dep_Rec=0 AND Dep_Est='A'";
            break;
        //Insert para registrar un departamento
        case 5:
            $sql = "INSERT INTO departamen(Are_Cod,Dep_Des,Dep_Rec,Dep_Cdc,Emp_Cod) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]')";
            break;
        //Permite seleccionar el número mayor de secuencia en el caso de un subdepartamento
        case 6:
            $sql = "SELECT IF(ISNULL(MAX(CAST((SUBSTRING_INDEX(Dep_Cdc, '.', -1) + 0)AS DECIMAL))),'0',MAX(CAST((SUBSTRING_INDEX(Dep_Cdc, '.', -1) + 0)AS DECIMAL))) AS max FROM departamen WHERE Dep_Rec='$Par_Sql[0]' AND departamen.Emp_Cod='$Par_Sql[1]'";
            break;
        //Permite editar los datos de un área
        case 7:
            $sql = "UPDATE areas_rrhh SET Are_Des='$Par_Sql[1]' WHERE Are_Cod='$Par_Sql[0]'";
            break;
        //Permite editar los datos de un departamento y subdepartamento
        case 8:
            $sql = "UPDATE departamen SET Dep_Des='$Par_Sql[1]' WHERE Dep_Cod='$Par_Sql[0]'";
            break;
        //Permite extraer el nombre de un departamento
        case 9:
            $sql = "SELECT Dep_Des FROM departamen WHERE Dep_Cod='$Par_Sql[0]'";
            break;
        //Update mediante el cual se da de baja a un subdedpartamento
        case 10:
            //Esta condicionante es exclusiva para cuando se va a eliminar un àrea y con ella sus departamentos
            if($Par_Sql[1]=='A'){
                $sql="UPDATE areas_rrhh,departamen SET areas_rrhh.Are_Est='I',departamen.Dep_Est='I' WHERE areas_rrhh.Are_Cod='$Par_Sql[0]' AND departamen.Are_Cod='$Par_Sql[0]'";
            }else{
                if($Par_Sql[1]=='C'){
                    $sql="UPDATE tiposcargo SET Tic_Est='I' WHERE Tic_Cod='$Par_Sql[0]'";
                }else{
                    $sql="UPDATE departamen SET Dep_Est='I' WHERE Dep_Cod='$Par_Sql[0]' OR Dep_Rec='$Par_Sql[0]'";
                }
            }
            break;
        //Insert para registrar un tipo de cargo en la tabla tiposcargo
        case 11:
            $sql = "INSERT INTO tiposcargo(Dep_Cod,Tic_Des,Tic_Per) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
			//echo $sql;
        break;
        //Permite extraer la información de un cargo según el código del departamento
        case 12:
            $sql = "SELECT * FROM tiposcargo WHERE Tic_Cod='$Par_Sql[0]'";
            break;
        //Permite extraer los cargos que le pertenecen a un subdepartamento e incluir en el árbol
        case 13:
            $sql = "SELECT Tic_Cod,CONCAT('C_',Tic_Cod) AS id,CAST(departamen.Dep_Cod AS CHAR) AS parent,CONCAT(Dep_Cdc,' - ',Dep_Des) AS Dep_Des,Tic_Des AS text,'C' AS type
                    FROM departamen,tiposcargo
                    WHERE departamen.Emp_Cod='$Par_Sql[0]' AND departamen.Dep_Cod=tiposcargo.Dep_Cod AND Tic_Est='A'";
            break;
        //Permite editar los datos de un tipo de cargo Tic_Des y Tic_Per
        case 14:
            $sql="UPDATE tiposcargo SET Tic_Des='$Par_Sql[1]',Tic_Per='$Par_Sql[2]' WHERE Tic_Cod='$Par_Sql[0]'";
            break;
        case 15:
            $sql="SELECT COUNT(*)AS conteo FROM contratos_lab WHERE Tic_Cod='$Par_Sql[0]'";
            break;
        case 16:
            $sql="UPDATE tiposcargo SET Tic_Est='I' WHERE Tic_Cod='$Par_Sql[0]'";
            break;
        
        case 17:
            $sql="SELECT COUNT(contratos_lab.Con_Cod) AS conteo,tiposcargo.Tic_Cod,Tic_Des FROM tiposcargo INNER JOIN departamen ON tiposcargo.Dep_Cod=departamen.Dep_Cod INNER JOIN contratos_lab ON contratos_lab.Tic_Cod=tiposcargo.Tic_Cod WHERE departamen.Dep_Cod='$Par_Sql[0]' OR departamen.Dep_Rec='$Par_Sql[0]' GROUP BY tiposcargo.Tic_Cod;";
            break;
        case 18:
            $sql="UPDATE departamen SET Dep_Est='I' WHERE Dep_Cod='$Par_Sql[0]' OR Dep_Rec='$Par_Sql[0]'";
            break;
        case 19:
            $sql="UPDATE tiposcargo,departamen SET Tic_Est='I' WHERE departamen.Dep_Cod=tiposcargo.Dep_Cod AND (departamen.Dep_Cod='$Par_Sql[0]' OR departamen.Dep_Rec='$Par_Sql[0]')";
            break;
        
        case 20:
            $sql="SELECT COUNT(contratos_lab.Con_Cod) AS conteo,tiposcargo.Tic_Cod,Tic_Des FROM tiposcargo INNER JOIN departamen ON tiposcargo.Dep_Cod=departamen.Dep_Cod INNER JOIN contratos_lab ON contratos_lab.Tic_Cod=tiposcargo.Tic_Cod WHERE departamen.Are_Cod='$Par_Sql[0]' GROUP BY tiposcargo.Tic_Cod;";
            break;
        case 21:
            $sql="UPDATE areas_rrhh SET areas_rrhh.Are_Est='I' WHERE areas_rrhh.Are_Cod='$Par_Sql[0]';";
            break;
        case 22:
            $sql="UPDATE departamen SET Dep_Est='I' WHERE Are_Cod='$Par_Sql[0]'";
            break;
        case 23:
            $sql="UPDATE tiposcargo,departamen SET Tic_Est='I' WHERE departamen.Dep_Cod=tiposcargo.Dep_Cod AND departamen.Are_Cod='$Par_Sql[0]'";
            break;
    }
    return $sql;
}

