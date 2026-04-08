<?php
/**
 * Requisitores 
 */
function sentencias_requisitores($id,$Par_Sql)
{
    switch ($id){
        //Select que permite extraer todas las ciudades
        case 1:
            $sql = "SELECT Ciu_Cod,Ciu_Des,Pro_Nom,Pas_Nom
                    FROM ciudad
                    INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod
                    INNER JOIN pais ON pais.Pas_Cod=ciudad.Pas_Cod 
                    WHERE Ciu_Est='A' AND Ciu_Des IS NOT NULL";
        break;
        //Select que extrae el Ide_Cod e Ide_Des de la tabla identifica para saber si el registro es una c�dula o un R.U.C.
        case 2:
            $sql = "SELECT Ide_Cod,Ide_Des
                    FROM identifica
                    WHERE Ide_Max=$Par_Sql[0]";
        break;
        //Select para extraer la informaci�n de una persona de la tabla del mismo nombre
        case 3:
            $sql = "SELECT Prs_Cod,Prs_Ced,Prs_Nom,Prs_Ape,ciudad.Ciu_Cod,Prs_Sex,Prs_Dir,Prs_Tel,Prs_Cel,Prs_Fec,Prs_Esc,Prs_Cor,identifica.Ide_Cod,Ide_Des
                    FROM persona
					INNER JOIN ciudad ON persona.Ciu_Cod=ciudad.Ciu_Cod
					INNER JOIN identifica ON persona.Ide_Cod=identifica.Ide_Cod
                    WHERE Prs_Ced='$Par_Sql[0]'";
        break;
        //Inserta un registro en la tabla persona 
        case 4:
            $sql = "INSERT INTO persona (Ciu_Cod,Ide_Cod,Prs_Ced,Prs_Nom,Prs_Ape,Prs_Sex,Prs_Esc,Prs_Fec,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,Prs_San) 
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]','$Par_Sql[9]','$Par_Sql[10]','$Par_Sql[11]','$Par_Sql[12]','$Par_Sql[13]')";
        break;
        //Inserta un registro en la tabla personal 
        case 5:
            $sql = "INSERT INTO personal (Prs_Cod,Emp_Cod,Per_Car,Per_Tit,Per_Obs,Per_Cfi,Per_Req) 
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]',1)";
			//echo $sql;
        break;
        //Update en la tabla personal para actualizar la foto de perfil (Per_Fot)
        case 6:
            $sql = "UPDATE personal SET Per_Fot='$Par_Sql[1]' WHERE Per_Cod='$Par_Sql[0]'";
        break;
        //Select para listar todas las personas registradas en la tabla personal
        case 7:
            if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
            if(isset($Par_Sql["limits"])){
                    $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                    $campos="Per_Cod,Ide_Des,ciudad.Ciu_Cod,Ciu_Des,Prs_Ced,Prs_Ape,Prs_Nom,IF(personal.Per_Req=1,'Si','No') AS Per_Req,IF(personal.Per_Req=1,1,0) AS requisitor,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,Prs_Sex,IF(Prs_Sex='M','Masculino','Femenino') AS Prs_Gen,Prs_Esc,Prs_Fec,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,Prs_San,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Car,Per_Tit,IF(Per_Tit='Np','NO POSEE',IF(Per_Tit='Abg','ABOGADO/A',IF(Per_Tit='Bac','BACHILLER',IF(Per_Tit='Dr','DOCTOR/A',IF(Per_Tit='Eco','ECONOMISTA',IF(Per_Tit='Ing','INGENIERO/A',IF(Per_Tit='Lcd','LICENCIADO/A','')))))))AS Per_Ti1,Per_Obs,IF(ISNULL(Per_Fot),'no',Per_Fot) AS Per_Fot,Per_Cfi, IF (Per_Est='A','Activo','Inactivo') as Per_Est,CURDATE() AS Fec_Sys";
            }
            else{$campos="COUNT(Per_Cod) as total";$Par_Sql["limits"]="";}
            $sql = "SELECT $campos FROM persona
                    INNER JOIN personal ON personal.Prs_Cod=persona.Prs_Cod
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
                    LEFT JOIN ciudad ON ciudad.Ciu_Cod=persona.Ciu_Cod
                    WHERE $search AND Per_Est='A' AND personal.Emp_Cod = $Par_Sql[Emp_Cod] AND Per_Req = 1 $Par_Sql[limits] ";
			//echo $sql;
        break;
		
        //Update en la tabla persona y personal
        case 8:
			if(empty($Par_Sql[16])){$Par_Sql[16]="NULL";} else{ $Par_Sql[16]="'$Par_Sql[16]'";}
            $sql = "UPDATE persona,personal SET Prs_Nom='$Par_Sql[1]',Prs_Ape='$Par_Sql[2]',Prs_Sex='$Par_Sql[3]',Prs_Esc='$Par_Sql[4]',Prs_Fec='$Par_Sql[5]',Ciu_Cod='$Par_Sql[6]',Prs_Tel='$Par_Sql[7]',Prs_Te2='$Par_Sql[8]',Prs_Cel='$Par_Sql[9]',Prs_Cor='$Par_Sql[10]',Prs_Dir='$Par_Sql[11]',Prs_San='$Par_Sql[12]',Per_Car='$Par_Sql[13]',Per_Tit='$Par_Sql[14]',Per_Obs='$Par_Sql[15]',Per_Fot=$Par_Sql[16],Per_Cfi='$Par_Sql[17]'
                    WHERE Per_Cod='$Par_Sql[0]' AND personal.Prs_Cod=persona.Prs_Cod";
        break;
        //Select para extraer la informaci�n de un empleado de la tabla personal
        case 9:
            $sql = "SELECT personal.Prs_Cod
                    FROM persona,personal
                    WHERE Prs_Ced='$Par_Sql[0]' AND persona.Prs_Cod=personal.Prs_Cod AND Emp_Cod=$_SESSION[Ses_Emp_Cod]";
        break;
        //Select para listar subdepartammentos
        case 10:
            $sql = "SELECT Dep_Cod,Dep_Des 
                    FROM departamen
                    WHERE Dep_Rec>'0' AND Emp_Cod='$Par_Sql[0]' AND Dep_Est='A'";
        break;
        //Select para listar cargos seg�n el subdepartammentos
        case 11:
            $sql = "SELECT CONCAT(Tic_Cod,'*',Tic_Des,'*',Tic_Per)AS Tic_Cod,Tic_Des
                    FROM tiposcargo
                    WHERE Dep_Cod='$Par_Sql[0]' AND Tic_Est='A'";
        break;
        //Select para extraer las relaciones laborales existentes en la tabla relaci_lab
        case 12:
            $sql = "SELECT Reb_Cod,Reb_Des 
                    FROM relaci_lab
                    WHERE Suc_Cod='$Par_Sql[0]' AND Reb_Est='A'";
        break;
        //Select para extraer las relaciones laborales existentes en la tabla relaci_lab
        case 13:
            $sql = "SELECT Ded_Cod,Ded_Des 
                    FROM dedica_lab
                    WHERE Suc_Cod='$Par_Sql[0]' AND Ded_Est='A'";
        break;
    
        /**INICIO DE SQL's PARA PARA EL MANEJO DE DATOS EN EL ARCHIVO rhu_pri_personal_1.0.php**/
        //Select para extraer datos principales de una sucursal
        case 14:
                $sql = "SELECT empresas.Emp_Nom,Emp_Ruc,ciudad.Ciu_Des,sucursal.Ciu_Cod,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Te2,sucursal.Suc_Fax,
                        sucursal.Suc_Cor,sucursal.Suc_Web,sucursal.Suc_Des,empresas.Emp_Log,CONCAT(ciudad.Ciu_Des,' - ',provincia.Pro_Nom,' - ',pais.Pas_Nom) AS provincia
                        FROM sucursal
                        INNER JOIN empresas ON empresas.Emp_Cod=sucursal.Emp_Cod
                        INNER JOIN ciudad ON ciudad.Ciu_Cod=sucursal.Ciu_Cod
                        INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod
                        INNER JOIN regiones ON regiones.Reg_Cod=provincia.Reg_Cod  
                        INNER JOIN pais ON pais.Pas_Cod=regiones.Pas_Cod  
                        WHERE sucursal.Suc_Cod = '$Par_Sql[0]' ";
        break;
        //Select para extraer los datos de un empleado
        case 15:
            $sql = "SELECT Per_Cod,Ide_Des,ciudad.Ciu_Cod,Ciu_Des,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,IF(Prs_Sex='M','Masculino','Femenino') AS Prs_Gen,IF(Prs_Esc='S','SOLTERO/A',IF(Prs_Esc='C','CASADO/A',IF(Prs_Esc='D','DIVORCIADO/A',IF(Prs_Esc='V','VIUDO/A',IF(Prs_Esc='U','UNION LIBRE',''))))) AS Prs_Esc,Prs_Fec,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,Prs_San,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Car,IF(Per_Tit='Np','NO POSEE',IF(Per_Tit='Abg','ABOGADO/A',IF(Per_Tit='Bac','BACHILLER',IF(Per_Tit='Dr','DOCTOR/A',IF(Per_Tit='Eco','ECONOMISTA',IF(Per_Tit='Ing','INGENIERO/A',IF(Per_Tit='Lcd','LICENCIADO/A','')))))))AS Per_Tit,Per_Obs,IF(ISNULL(Per_Fot),'no',Per_Fot) AS Per_Fot,Per_Cfi, IF (Per_Est='A','Activo','Inactivo') as Per_Est,CURDATE() AS Fec_Sys
                    FROM personal
                    INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
                    INNER JOIN ciudad ON ciudad.Ciu_Cod=persona.Ciu_Cod
                    WHERE Per_Cod='$Par_Sql[0]' AND personal.Emp_Cod='$Par_Sql[1]'";
        break;
		//Update en la tabla persona
        case 16:			
            $sql = "UPDATE persona SET Prs_Nom='$Par_Sql[1]',Prs_Ape='$Par_Sql[2]',Prs_Sex='$Par_Sql[3]',Prs_Esc='$Par_Sql[4]',Prs_Fec='$Par_Sql[5]',Ciu_Cod='$Par_Sql[6]',Prs_Tel='$Par_Sql[7]',Prs_Te2='$Par_Sql[8]',Prs_Cel='$Par_Sql[9]',Prs_Cor='$Par_Sql[10]',Prs_Dir='$Par_Sql[11]',Prs_San='$Par_Sql[12]' 
            WHERE persona.Prs_Cod='$Par_Sql[0]'";
        break;
        case 17:			
            $sql = "UPDATE personal SET Per_Est='$Par_Sql[Per_Est]'
            WHERE Per_Cod=$Par_Sql[Per_Cod]";
        break;
        case 18:
            if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
            if(isset($Par_Sql["limits"])){
                    $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                    $campos="Per_Cod,Ide_Des,ciudad.Ciu_Cod,Ciu_Des,Prs_Ced,Prs_Ape,Prs_Nom,IF(personal.Per_Req=1,'Si','No') AS Per_Req,IF(personal.Per_Req=1,1,0) AS requisitor,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,Prs_Sex,IF(Prs_Sex='M','Masculino','Femenino') AS Prs_Gen,Prs_Esc,Prs_Fec,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,Prs_San,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Car,Per_Tit,IF(Per_Tit='Np','NO POSEE',IF(Per_Tit='Abg','ABOGADO/A',IF(Per_Tit='Bac','BACHILLER',IF(Per_Tit='Dr','DOCTOR/A',IF(Per_Tit='Eco','ECONOMISTA',IF(Per_Tit='Ing','INGENIERO/A',IF(Per_Tit='Lcd','LICENCIADO/A','')))))))AS Per_Ti1,Per_Obs,IF(ISNULL(Per_Fot),'no',Per_Fot) AS Per_Fot,Per_Cfi, IF (Per_Est='A','Activo','Inactivo') as Per_Est,CURDATE() AS Fec_Sys";
            }
            else{$campos="COUNT(Per_Cod) as total";$Par_Sql["limits"]="";}
            $sql = "SELECT $campos FROM persona
                    INNER JOIN personal ON personal.Prs_Cod=persona.Prs_Cod
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
                    LEFT JOIN ciudad ON ciudad.Ciu_Cod=persona.Ciu_Cod
                    WHERE $search AND personal.Emp_Cod = $Par_Sql[Emp_Cod] AND Per_Req = 1 $Par_Sql[limits] ";
			//echo $sql;
        break;
    }
    return $sql;
}

