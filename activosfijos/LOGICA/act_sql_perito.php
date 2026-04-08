<?php
/**
 * PERITO
 */
function sentencias_per($id,$Par_Sql)
{
    switch($id)
    {	
        /**
         * Comprobar la existencia de una persona
         */
        case 601:
            $sql = "SELECT Prs_Cod,Prs_Ced,Prs_Nom,Prs_Ape,ciudad.Ciu_Cod,Prs_Sex,Prs_Dir,Prs_Tel,Prs_Cel
                    FROM persona,ciudad
                    WHERE Prs_Ced='$Par_Sql[0]' AND persona.Ciu_Cod=ciudad.Ciu_Cod";
            return $sql;
        break;

        /**
         * Registrar peritaje
         */
        case 602:
                $sql = "INSERT INTO perito(Pri_Esp,Pri_Obs,Prs_Cod,Emp_Cod)
                        VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]');";
                return $sql;
        break;
        
        /**
         * Lista todas las ciudades
         */
        case 603:
                $sql="SELECT Ciu_Cod,Ciu_Des 
                      FROM ciudad
                      WHERE Ciu_Est='A'";
                return $sql;
        break;
    
        /**
         * Obtiene el código del identificador(NÚMERO DE CÉDULA, RUC, PASAPORTE,ETC.)
         */
        case 604:
                $sql = "SELECT Ide_Cod,Ide_Des
                        FROM identifica
                        WHERE Ide_Max=$Par_Sql[0]";
                return $sql;
        break;
    
        /**
         * Verifica si la persona se encuentra registrada como visitador
         */
        case 605:
                $sql = "SELECT Pri_Cod
                        FROM perito,persona
                        WHERE Prs_Ced='$Par_Sql[0]' AND persona.Prs_Cod=perito.Prs_Cod";
                return $sql;
        break;
    
        /**
         * Inserta un registro en la tabla persona en caso de que esta no se encuentre registrada
         */
        case 606:
                $sql = "INSERT INTO persona (Prs_Ced,Prs_Nom,Prs_Ape,Prs_Sex,Ciu_Cod,Prs_Dir,Prs_Tel,Prs_Cel,Ide_Cod) 
                VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]')";
                //echo $sql;
                return $sql;
        break;
    
        /**
         * Lista los peritos registrados en la misma tabla
         */
        case 607:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                        $campos="Pri_Cod,Pri_Esp,Pri_Obs,persona.Prs_Cod,Prs_Ced,Prs_Nom,Prs_Ape,CONCAT(Prs_Ape,' ',Prs_Nom) AS perito,Prs_Sex,Ciu_Cod,Prs_Dir,Prs_Tel,Prs_Cel,Ide_Des";
                }
                else{$campos="COUNT(Pri_Cod) as total";$Par_Sql["limits"]="";}
                $sql="SELECT $campos FROM persona,perito,identifica WHERE $search AND perito.Prs_Cod=persona.Prs_Cod AND persona.Ide_Cod=identifica.Ide_Cod AND perito.Pri_Est='A' AND perito.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
                return $sql;
        break;
        
        /**
         * Update sobre los datos de la persona
         */
        case 608:
                $sql="UPDATE persona SET Prs_Nom='$Par_Sql[1]',Prs_Ape='$Par_Sql[2]',Prs_Sex='$Par_Sql[3]',Ciu_Cod='$Par_Sql[4]',Prs_Dir='$Par_Sql[5]',Prs_Tel='$Par_Sql[6]',Prs_Cel='$Par_Sql[7]'
                      WHERE Prs_Cod='$Par_Sql[0]'";
                return $sql;
        break;
        
        /**
         * Update sobre los datos del perito
         */
        case 609:
                $sql="UPDATE perito SET Pri_Esp='$Par_Sql[1]',Pri_Obs='$Par_Sql[2]'
                      WHERE Pri_Cod='$Par_Sql[0]'";
                return $sql;
        break;
    }
}
?>