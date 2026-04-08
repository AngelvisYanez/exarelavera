<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2015-07-22
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_pais($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {
        case 1://paises
            $sql="SELECT Pas_Cod,Pas_Nom,Pas_Nac,Pas_Est,CAST(CONCAT('P_',Pas_Cod) AS CHAR) AS id,'#' as parent, Pas_Nom AS text ,'fa fa-hand-o-right red bold' AS icon,'P' AS tipo FROM pais WHERE Pas_Est='A' AND Pas_Nom<>'(Ninguno)' ORDER BY Pas_Nom";
            break; 
        case 2://regiones
            $sql="SELECT Reg_Cod,Pas_Cod,Reg_Nom,Reg_Est,CAST(CONCAT('R_',Reg_Cod) AS CHAR) AS id,CAST(CONCAT('P_',Pas_Cod) AS CHAR) as parent,Reg_Nom as text,'glyphicon glyphicon-folder-open blue' AS icon,'R' as tipo FROM regiones WHERE Reg_Est='A' AND Reg_Nom<>'(Ninguno)' ORDER BY Reg_Nom";
            break;
        case 3://provincias
            $sql="SELECT Pro_Cod,provincia.Reg_Cod,Pas_Cod,Pro_Nom,Pro_Est,CAST(CONCAT('V_',Pro_Cod) AS CHAR) AS id,CAST(CONCAT('R_',provincia.Reg_Cod) AS CHAR) as parent,Pro_Nom as text,'glyphicon glyphicon-folder-open purple' AS icon,'V' as tipo FROM provincia INNER JOIN regiones ON regiones.Reg_Cod=provincia.Reg_Cod WHERE Pro_Est='A' AND Pro_Nom<>'(Ninguno)' ORDER BY Pro_Nom";
            break;
        case 4://ciudades
            $sql="SELECT Ciu_Cod,Pro_Cod,Pas_Cod,Ciu_Des,Ciu_Est,CAST(CONCAT('C_',Ciu_Cod) AS CHAR) AS id,CAST(CONCAT('V_',Pro_Cod) AS CHAR) as parent,Ciu_Des as text,'fa fa-file-text green' AS icon,'C' as tipo FROM ciudad WHERE Ciu_Est='A' AND Ciu_Des<>'(Ninguno)' AND Pro_Cod IS NOT NULL ORDER BY Ciu_Des";
            break;
        
        case 5://insert paises
            $sql="INSERT INTO pais (Pas_Nom,Pas_Nac,Pas_Est) VALUES ('$Par_Sql[Pas_Nom]','$Par_Sql[Pas_Nac]','A')";
            break; 
        case 6://insert regiones
            $sql="INSERT INTO regiones (Reg_Nom,Pas_Cod,Reg_Est) VALUES ('$Par_Sql[Reg_Nom]','$Par_Sql[Pas_Cod]','A')";
            break;
        case 7://insert provincias
            $sql="INSERT INTO provincia (Pro_Nom,Reg_Cod,Pro_Est) VALUES ('$Par_Sql[Pro_Nom]','$Par_Sql[Reg_Cod]','A')";
            break;
        case 8://insert ciudades
            $sql="INSERT INTO ciudad (Ciu_Des,Pro_Cod,Pas_Cod,Ciu_Est) VALUES ('$Par_Sql[Ciu_Des]','$Par_Sql[Pro_Cod]','$Par_Sql[Pas_Cod]','A')";
            break;
        
        case 9://update paises
            $sql="UPDATE pais SET Pas_Nom='$Par_Sql[Pas_Nom]',Pas_Nac='$Par_Sql[Pas_Nac]' WHERE Pas_Cod='$Par_Sql[Pas_Cod]'";
            break; 
        case 10://update regiones
            $sql="UPDATE regiones SET Reg_Nom='$Par_Sql[Reg_Nom]' WHERE Reg_Cod='$Par_Sql[Reg_Cod]'";
            break;
        case 11://update provincias
            $sql="UPDATE provincia SET Pro_Nom='$Par_Sql[Pro_Nom]' WHERE Pro_Cod='$Par_Sql[Pro_Cod]'";
            break;
        case 12://update ciudades
            $sql="UPDATE ciudad SET Ciu_Des='$Par_Sql[Ciu_Des]' WHERE Ciu_Cod='$Par_Sql[Ciu_Cod]'";
            break;
        
        case 13://regiones
            $sql="SELECT Reg_Cod as id,Reg_Nom as nombre FROM regiones WHERE Reg_Est='A' AND Pas_Cod='$Par_Sql[0]' ORDER BY Reg_Nom";
            break;
        case 14://provincias
            $sql="SELECT Pro_Cod as id,Pro_Nom as nombre FROM provincia WHERE Pro_Est='A' AND Reg_Cod='$Par_Sql[0]' ORDER BY Pro_Nom";
            break;
        case 15://ciudades
            $sql="SELECT Ciu_Cod as id,Ciu_Des as nombre FROM ciudad WHERE Ciu_Est='A' AND Pro_Cod='$Par_Sql[0]' ORDER BY Ciu_Des";
            break;
        
        case 16://baja ciudades
            $sql="UPDATE ciudad SET Ciu_Est='I' WHERE Ciu_Cod='$Par_Sql[0]'";
            break;
    }
    //echo $sql."<br/>";
    return $sql;    
}



