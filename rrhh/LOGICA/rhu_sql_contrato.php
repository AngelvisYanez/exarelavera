<?php
/**
 * Recursos Humanos (rrhh)
 */

function sentencias_rrhh($id,$Par_Sql)
{
    switch ($id){
        //Select para listar todas las personas registradas en la tabla personal
        case 1:
            if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
            if(isset($Par_Sql["limits"])){
                    $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                    $campos="Per_Cod,Ide_Des,ciudad.Ciu_Cod,Ciu_Des,Prs_Ced,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,Prs_Sex,Prs_Esc,Prs_Fec,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Car,Per_Tit,Per_Obs,IF(ISNULL(Per_Fot),'no',Per_Fot) AS Per_Fot, IF (Per_Est='A','Activo','Inactivo') as Per_Est";
            }
            else{$campos="COUNT(Per_Cod) as total";$Par_Sql["limits"]="";}
            $sql = "SELECT $campos FROM persona
                    INNER JOIN personal ON personal.Prs_Cod=persona.Prs_Cod
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
                    INNER JOIN ciudad ON ciudad.Ciu_Cod=persona.Ciu_Cod
                    WHERE $search AND personal.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
		//echo $sql;
        break;
        //Select para extraer los contratos en los cuales esta involucrado un empleado
        case 2:
            $sql = "SELECT contratos_lab.Con_Cod,Con_Exc,Prs_Ced,Tic_Des,Con_Ini,Con_Fin,Sue_Val,Sue_Va1
                    FROM personal
                    INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod
                    INNER JOIN contratos_lab ON contratos_lab.Per_Cod=personal.Per_Cod
                    INNER JOIN tiposcargo ON tiposcargo.Tic_Cod=contratos_lab.Tic_Cod
                    INNER JOIN sueldos ON sueldos.Con_Cod=contratos_lab.Con_Cod
                    WHERE personal.Per_Cod='$Par_Sql[0]' AND Con_Est='A'";
        break;
        //Select para extraer los procesos de afiliaci�n de un empleado
        case 3:
            $sql = "SELECT contratos_lab.Con_Cod,Con_Exc,Prs_Ced,Afi_Fei,Afi_Fef,IF(Afi_Fnd='S','SI','NO') AS Afi_Fnd,IF(Afi_Dte='S','SI','NO') AS Afi_Dte,IF(Afi_Dcu='S','SI','NO') AS Afi_Dcu 
                    FROM afiliacion,contratos_lab,persona,personal
                    WHERE contratos_lab.Per_Cod='$Par_Sql[0]' AND contratos_lab.Con_Cod=afiliacion.Con_Cod AND contratos_lab.Per_Cod=personal.Per_Cod AND personal.Prs_Cod=persona.Prs_Cod AND Afi_Est='A'";
        break;
        //Inserta un registro en la tabla contratos_lab
        case 4:
            $sql = "INSERT INTO contratos_lab (Tic_Cod,Per_Cod,Ded_Cod,Reb_Cod,Con_Ini,Con_Fin,Con_Mot,Con_Exc) 
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]')";
            //echo $sql;
        break;
        //Inserta un registro en la tabla sueldos
        case 5:
            $sql = "INSERT INTO sueldos (Con_Cod,Sue_Fec,Sue_Val,Sue_Va1,Sue_Bas,Sut_Cod) 
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]',".(empty($Par_Sql[5])?"NULL":"$Par_Sql[5]").")";
			//echo $sql;
        break;
        //Inserta un registro en la tabla aficiliacion
        case 6:
            $sql = "INSERT INTO afiliacion (Con_Cod,Afi_Fei,Afi_Fnd,Afi_Dte,Afi_Dcu,Afi_Fef,Afi_Mot,Afi_Due) 
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]')";
			//echo $sql;
        break;
        //Select para listar subdepartammentos
        case 7:
            $sql = "SELECT Dep_Cod,Dep_Des 
                    FROM departamen
                    WHERE Dep_Rec>'0' AND Dep_Est='A' AND Emp_Cod='$Par_Sql[0]'";
                    //WHERE Dep_Rec>'0' AND Emp_Cod='$Par_Sql[0]' AND Dep_Est='A'";
        break;
        //Select para listar cargos seg�n el subdepartammentos
        case 8:
//            $sql = "SELECT CONCAT(Tic_Cod,'*',Tic_Des,'*',Tic_Per)AS Tic_Cod,Tic_Des
//                    FROM tiposcargo
//                    WHERE Dep_Cod='$Par_Sql[0]' AND Tic_Est='A'";
            $sql = "SELECT Tic_Cod,Tic_Des
                    FROM tiposcargo
                    WHERE Dep_Cod='$Par_Sql[0]' AND Tic_Est='A'";
        break;
        //Select para extraer las relaciones laborales existentes en la tabla relaci_lab
        case 9:
            $sql = "SELECT Reb_Cod,Reb_Des 
                    FROM relaci_lab
                    WHERE Suc_Cod='$Par_Sql[0]' AND Reb_Est='A'";
        break;
        //Select para extraer las relaciones laborales existentes en la tabla relaci_lab
        case 10:
            $sql = "SELECT Ded_Cod,Ded_Des 
                    FROM dedica_lab
                    WHERE Suc_Cod='$Par_Sql[0]' AND Ded_Est='A'";
        break;
        //Select para extraer las los datos de un tipo de cargo de la tabla tiposcargo
        case 11:
            $sql = "SELECT Tic_Des,Tic_Per 
                    FROM tiposcargo
                    WHERE Tic_Cod='$Par_Sql[0]' AND Tic_Est='A'";
        break;
    
        /*** INICIO DE SQL'S PARA EL ARCHIVO rhu_mod_contrato_1.0.php ***/
        //Select para listar todas las personas registradas en la tabla personal
        case 12:
            if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
            if(isset($Par_Sql["limits"])){
                    $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                    $campos="personal.Per_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,contratos_lab.Con_Cod,contratos_lab.Tic_Cod,Tic_Des,Tic_Per,Ded_Cod,Reb_Cod,Con_Ini,Con_Fin,Con_Mot,Con_Exc,Con_Est,Sue_Fec,Sue_Val,Sue_Va1,IF(ISNULL(Afi_Cod),0,Afi_Cod) AS Afi_Cod,Afi_Fei,Afi_Fef,Afi_Fnd,Afi_Dte,Afi_Dcu,Afi_Mot,Afi_Est,Afi_Due,Sue_Bas,Sut_Cod, pg.Bak_Cod, pg.Pag_Con_For, pg.Pag_Con_Tip, pg.Pag_Con_Cue";
            }
            else{$campos="COUNT(personal.Per_Cod) as total";$Par_Sql["limits"]="";}
            $sql = "SELECT $campos FROM persona
                    INNER JOIN personal ON persona.Prs_Cod=personal.Prs_Cod
                    INNER JOIN contratos_lab ON contratos_lab.Per_Cod=personal.Per_Cod
                    INNER JOIN sueldos ON contratos_lab.Con_Cod=sueldos.Con_Cod
                    INNER JOIN tiposcargo ON contratos_lab.Tic_Cod=tiposcargo.Tic_Cod
                    LEFT JOIN afiliacion ON contratos_lab.Con_Cod=afiliacion.Con_Cod AND Afi_Est='A'
                    LEFT JOIN pago_contrato pg ON contratos_lab.Con_Cod=pg.Con_Cod AND pg.Pag_Con_Est='A'
                    WHERE $search AND Per_Est='A' AND Con_Est='A' AND personal.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
			//echo $sql;
        break;
        //Update sobre la tabla contratos_lab y sueldos
        case 13:
            $sql = "UPDATE contratos_lab,sueldos SET  
                    Tic_Cod='$Par_Sql[1]',Ded_Cod='$Par_Sql[2]',Reb_Cod='$Par_Sql[3]',Con_Ini='$Par_Sql[4]',Con_Fin='$Par_Sql[5]',Con_Mot='$Par_Sql[6]',
                    Sue_Fec='$Par_Sql[7]',Sue_Val='$Par_Sql[8]',Sue_Va1='$Par_Sql[9]',Con_Exc='$Par_Sql[10]',Sue_Bas='$Par_Sql[11]',Sut_Cod=".(empty($Par_Sql[12])?"NULL":"$Par_Sql[12]")."
                    WHERE contratos_lab.Con_Cod='$Par_Sql[0]' AND contratos_lab.Con_Cod=sueldos.Con_Cod";
        break;
        //Update sobre la tabla afiliacion
        case 14:

            $sql = "UPDATE afiliacion SET  
            Afi_Fei='$Par_Sql[1]',Afi_Fnd='$Par_Sql[2]',Afi_Dte='$Par_Sql[3]',Afi_Dcu='$Par_Sql[4]',Afi_Fef='$Par_Sql[5]',
            Afi_Mot='$Par_Sql[6]',Afi_Due='$Par_Sql[7]',Afi_Est='A'
            WHERE afiliacion.Afi_Cod='$Par_Sql[8]'";
        break;
        //Eliminaci�n l�gica por medio de update sobre la tabla afiliacion
        case 15:
            $sql = "UPDATE afiliacion SET  
                    Afi_Est='I'
                    WHERE afiliacion.Con_Cod='$Par_Sql[0]'";
        break;
        case 16:
            $sql="SELECT * FROM sueldos_tipo INNER JOIN sueldos_unificados ON sueldos_tipo.Sut_Cod=sueldos_unificados.Sut_Cod WHERE Sut_Est='A' AND ( Suu_Fef IS NULL OR Suu_Fef > '".date("Y-m-d")."' );";
            //echo $sql;
            break;
	
	//VALIDAR DUPLICIDAD DEL CONTRATO POR FECHAS 
	case 17:
        $sql="SELECT * FROM contratos_lab 
                WHERE Per_Cod = $Par_Sql[0]
                AND (('$Par_Sql[1]' BETWEEN Con_Ini AND Con_Fin) OR ('$Par_Sql[2]' BETWEEN Con_Ini AND Con_Fin))
                AND Con_Est = 'A'";
        //echo $sql;
        break;
	
	//INSERTAR POR DEFECTO UN REGISTRO EN LA TABLA DE AFILIACION
         case 18:
            $sql = "INSERT INTO afiliacion (Con_Cod, Afi_Est) VALUES ('$Par_Sql[0]','I')";
            //echo $sql;
        break;

    //Obtener listado de bancos externos
         case 19:
            $sql = "SELECT * FROM bancos WHERE Bak_Est = 'A'";
            //echo $sql;
        break;
    //Inserta un registro en la talba pago_contrato
        case 20:
            $sql = "INSERT INTO pago_contrato (Con_Cod,Bak_cod,Pag_Con_Tip,Pag_Con_For,Pag_Con_Cue) 
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]')";
            //echo $sql;
        break;

        //Actualizar un registro en la talba pago_contrato
        case 21:
                $sql = "DELETE FROM pago_contrato WHERE pago_contrato.Con_Cod=$Par_Sql[0]";
         break;
    }
    return $sql;
}


