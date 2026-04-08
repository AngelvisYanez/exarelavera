<?php
/**
 * VIAJE
 */

function sentencias_viaje($id,$Par_Sql)
{
    switch($id)
    {
        //Select para cargar los clientes de una empresa
        case 1:
                $sql = "SELECT Cli_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente
                        FROM cliente,persona
                        WHERE cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod='$Par_Sql[0]' AND Cli_Est='A'";
        break;
        //Select para cargar los registros de la tabla cargamento
        case 2:
                if(!empty($Par_Sql[0])){$condicion="Car_Cod='$Par_Sql[0]' AND";}else{$condicion="";}
                $sql = "SELECT Car_Cod,Car_Des
                        FROM cargamento
                        INNER JOIN producto ON producto.Pro_Cod=cargamento.Pro_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        WHERE $condicion Car_Est='A' AND categorias.Emp_Cod='$Par_Sql[1]' ORDER BY Car_Des";
        break;
        //Select para cargar los registros de la tabla modo_trabajo
        case 3:
                if(!empty($Par_Sql[0])){$condicion="Mot_Cod='$Par_Sql[0]' AND";}else{$condicion="";}
                $sql = "SELECT Mot_Cod,Mot_Des
                        FROM modo_trabajo
                        WHERE $condicion Mot_Est='A' AND Emp_Cod='$Par_Sql[1]' ORDER BY Mot_Des";
        break;
        //Insert sobre la tabla cargamento
        case 4:
                $sql = "INSERT INTO cargamento(Car_Des,Pro_Cod) VALUES (UPPER('$Par_Sql[0]'),$Par_Sql[1])";
        break;
        //Insert sobre la tabla modo_trabajo
        case 5:
                $sql = "INSERT INTO modo_trabajo(Mot_Des,Emp_Cod) VALUES (UPPER('$Par_Sql[0]'),'$Par_Sql[1]')";
        break;
        //Select para cargar los productos
        case 6:
                if($Par_Sql['op_opciones']=="d") {$search="(Ite_Lar LIKE '%$Par_Sql[search]%')";}
                else {$search="Pro_Cod LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Ite_Lar $Par_Sql[limits]";
                        $campos="producto.Pro_Cod,Ite_Lar,Pld_Cod";
                }
                else{$campos="COUNT(producto.Pro_Cod) as total";$Par_Sql["limits"]="";}
                $sql = "SELECT $campos FROM producto
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        LEFT JOIN produ_plan ON producto.Pro_Cod=produ_plan.Pro_Cod AND Tip_Pld='V'
                        WHERE $search AND Pro_Est='A' AND categorias.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";

        break;
        //Insert sobre la tabla vehiculo
        case 7:
                $sql = "INSERT INTO vehiculo(Emp_Cod,Veh_Mar,Veh_Pla,Veh_Col) VALUES ('$Par_Sql[0]',UPPER('$Par_Sql[1]'),UPPER('$Par_Sql[2]'),UPPER('$Par_Sql[3]'))";
        break;
        //Select para cargar los registros de la tabla vehiculo
        case 8:
                if(!empty($Par_Sql[0])){$condicion="vehiculo.Veh_Cod='$Par_Sql[0]' AND";}else{$condicion="";}
                $sql = "SELECT Veh_Cod,Veh_Pla AS label
                        FROM vehiculo
                        WHERE $condicion Veh_Est='A' AND Emp_Cod='$Par_Sql[1]' ORDER BY Veh_Pla";
        break;
        //Select para cargar las personas
        case 9:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                        $campos="Prs_Cod,Ciu_Cod,Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,CONCAT(Prs_Ape,' ',Prs_Nom) AS persona";
                }
                else{$campos="COUNT(Prs_Cod) as total";$Par_Sql["limits"]="";}
                $sql = "SELECT $campos FROM persona
                        WHERE $search AND Prs_Est='A' $Par_Sql[limits]";
        break;
        //Insert sobre la tabla chofer
        case 10:
                $sql = "INSERT INTO chofer(Prs_Cod,Emp_Cod,Cho_Tli,Cho_Tel) VALUES ('$Par_Sql[0]','$Par_Sql[1]',UPPER('$Par_Sql[2]'),'$Par_Sql[3]')";
        break;
        //Select para verificar si la persona ya se encuentra registrada en la tabla persona, comporando el # cédula
        case 11:
                $sql = "SELECT Prs_Cod FROM persona
                        WHERE Prs_Ced='$Par_Sql[0]'";
        break;
        //Lista todas las ciudades
        case 12:
                $sql = "SELECT Ciu_Cod,Ciu_Des
                        FROM ciudad
                        WHERE Ciu_Est='A'";
        break;
        //Obtiene el código del identificador(NÚMERO DE CÉDULA, RUC, PASAPORTE,ETC.)
        case 13:
                $sql = "SELECT Ide_Cod,Ide_Des
                        FROM identifica
                        WHERE Ide_Max=$Par_Sql[0]";
        break;
        //Inserta un registro en la tabla persona
        case 14:
            $sql = "INSERT INTO persona (Ciu_Cod,Ide_Cod,Prs_Ced,Prs_Nom,Prs_Ape,Prs_Dir)
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]',UPPER('$Par_Sql[3]'),UPPER('$Par_Sql[4]'),UPPER('$Par_Sql[5]'))";
        break;
        //Select para cargar los registros de la tabla chofer
        case 15:
            if(!empty($Par_Sql[0])){$condicion="chofer.Cho_Cod='$Par_Sql[0]' AND";}else{$condicion="";}
            $sql = "SELECT CONCAT(Prs_Ape,' ',Prs_Nom) AS label,chofer.Cho_Cod,Prs_Ced,MAX(Via_Cod) AS Via_Cod,viaje.Veh_Cod,Veh_Pla,Via_Est
                    FROM chofer
                    INNER JOIN persona ON chofer.Prs_Cod=persona.Prs_Cod
                    LEFT JOIN viaje ON chofer.Cho_Cod=viaje.Cho_Cod
                    LEFT JOIN vehiculo ON viaje.Veh_Cod=vehiculo.Veh_Cod
                    WHERE $condicion chofer.Emp_Cod='$Par_Sql[1]' AND Cho_Est='A' AND Prs_Est='A' GROUP BY chofer.Cho_Cod ORDER BY Prs_Ape ";
        break;
        //Select para cargar los clientes
        case 16:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                        $campos="cliente.Prs_Cod,Cli_Cod,Prs_Ced,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente,Prs_Dir";
                }
                else{$campos="COUNT(cliente.Prs_Cod) as total";$Par_Sql["limits"]="";}
                $sql = "SELECT $campos FROM cliente,persona
                        WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND Prs_Est='A' AND cliente.Cli_Est='A' AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
        break;
        //Inserta un registro en la tabla viaje
        case 17:
            $sql = "INSERT INTO viaje (Cho_Cod,Car_Cod,Mot_Cod,Cli_Cod,Veh_Cod,Via_Ded,Via_Has,Via_Fec,Via_Can,Via_Pru,Via_Des)
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]',UPPER('$Par_Sql[5]'),UPPER('$Par_Sql[6]'),'$Par_Sql[7]','$Par_Sql[8]','$Par_Sql[9]',UPPER('$Par_Sql[10]'))";
        break;
        //Select para verificar si la persona ya esta registrada como chofer
        case 18:
            $sql = "SELECT Prs_Cod
                    FROM chofer
                    WHERE chofer.Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND Cho_Est='A'";
        break;

        /*** INICIO DE SQL'S PARA EL MANEJO DEL ARCHIVO tca_mod_viaje_1.0.php ***/

        //Select para cargar los viajes
        case 19:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                        $group="GROUP BY cliente.Prs_Cod";
                        $campos="COUNT(Via_Cod)AS viajes,viaje.Cli_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente,Prs_Dir,Via_Des";
                }
                else{$campos="COUNT(DISTINCT viaje.Cli_Cod) as total";$Par_Sql["limits"]="";$group="";}
                $sql = "SELECT $campos FROM viaje
                        INNER JOIN cliente ON viaje.Cli_Cod=cliente.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                        WHERE $search AND Vet_Cod IS NULL AND Via_Est='A' AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $group $Par_Sql[limits]";
                //echo $sql;
        break;
        //Update sobre la tabla viaje
        case 20:
            $sql = "UPDATE viaje
                    SET Cli_Cod='$Par_Sql[1]',Via_Des=UPPER('$Par_Sql[2]'),Via_Ded=UPPER('$Par_Sql[3]'),Via_Has=UPPER('$Par_Sql[4]'),Cho_Cod='$Par_Sql[5]',Car_Cod='$Par_Sql[6]',Veh_Cod='$Par_Sql[7]',Mot_Cod='$Par_Sql[8]',Via_Fec='$Par_Sql[9]',Via_Can='$Par_Sql[10]',Via_Pru='$Par_Sql[11]'
                    WHERE Via_Cod='$Par_Sql[0]'";
        break;
        //Select para obtener los datos de una persona a través de su # de cédula
        case 21:
            $sql = "SELECT Prs_Cod,Ciu_Cod,Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir
                    FROM persona
                    WHERE Prs_Ced LIKE '$Par_Sql[0]%' AND Prs_Est='A'";
        break;
        //Select para cargar los viajes de un determinado cliente
        case 22:
            $sql = "SELECT viaje.*,CONCAT(Prs_Ape,' ',Prs_Nom) AS Con_Duc,Veh_Pla AS Aut_Mot FROM viaje
                    LEFT JOIN chofer ON viaje.Cho_Cod=chofer.Cho_Cod
                    INNER JOIN persona ON chofer.Prs_Cod=persona.Prs_Cod
                    LEFT JOIN vehiculo ON viaje.Veh_Cod=vehiculo.Veh_Cod
                    WHERE Vet_Cod IS NULL AND viaje.Cli_Cod='$Par_Sql[0]' AND Via_Est='A' ".(!empty($Par_Sql[1])&&$Par_Sql[1]=='S'?"AND viaje.Via_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]'":'');
            //echo $sql;
        break;
        //Update sobre la tabla viaje
        case 23:
            $sql = "UPDATE viaje SET Cho_Cod='$Par_Sql[1]',Car_Cod='$Par_Sql[2]',Mot_Cod='$Par_Sql[3]',Cli_Cod='$Par_Sql[4]',Veh_Cod='$Par_Sql[5]',Via_Ded=UPPER('$Par_Sql[6]'),Via_Has=UPPER('$Par_Sql[7]'),Via_Fec='$Par_Sql[8]',Via_Can='$Par_Sql[9]',Via_Pru='$Par_Sql[10]',Via_Est='$Par_Sql[11]'
                    WHERE Via_Cod='$Par_Sql[0]'";
        break;
        //Select para cargar las facturas relacionadas con la tabla viaje
        case 24:
                if($Par_Sql['op_opciones']=="r2") {$search="Vet_Cod IS NOT NULL AND";}
                if($Par_Sql['op_opciones']=="r3"){$search="Vet_Cod IS NULL AND";}
                if($Par_Sql['op_opciones']=="r1"){$search="";}
                if(!empty($Par_Sql['Cho_Cod'])){$search.=" viaje.Cho_Cod='$Par_Sql[Cho_Cod]' AND";}
                if(!empty($Par_Sql['Cli_Cod'])){$search.=" viaje.Cli_Cod='$Par_Sql[Cli_Cod]' AND";}
                if(isset($Par_Sql["limits"])){
                    $Par_Sql["limits"]="ORDER BY p1.Prs_Ape,p1.Prs_Nom,Via_Fec, estado $Par_Sql[limits]";
                    $campos="Via_Cod,CONCAT(p1.Prs_Ced,' - ',p1.Prs_Ape,' ',p1.Prs_Nom) AS Prs_Ced,Car_Des,IF(ISNULL(Vet_Cod),'Sin Facturar','Facturado') AS estado,Via_Fec,Via_Can,Via_Pru,(Via_Can*Via_Pru) AS total,CONCAT(p2.Prs_Ape,' ',p2.Prs_Nom) AS chofer,Veh_Pla,Via_Ded,Via_Has";
                }
                else{$campos="COUNT(Via_Cod) as total";$Par_Sql["limits"]="";}
                $sql = "SELECT $campos FROM viaje
                        INNER JOIN cliente ON cliente.Cli_Cod=viaje.Cli_Cod
                        INNER JOIN persona AS p1 ON p1.Prs_Cod=cliente.Prs_Cod
                        INNER JOIN cargamento ON viaje.Car_Cod=cargamento.Car_Cod
                        INNER JOIN chofer ON viaje.Cho_Cod=chofer.Cho_Cod
                        INNER JOIN persona AS p2 ON chofer.Prs_Cod=p2.Prs_Cod
                        INNER JOIN vehiculo ON viaje.Veh_Cod=vehiculo.Veh_Cod
                        WHERE $search Via_Est='A' AND cliente.Emp_Cod='$Par_Sql[Emp_Cod]' AND (Via_Fec BETWEEN '$Par_Sql[Fec_Ini]' AND '$Par_Sql[Fec_Fin]') $Par_Sql[limits]";
	//ChromePhp::log($sql);
	 break;
        //Select para cargar los clientes que estan dentro de la tabla viaje
        case 25:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                        $campos="DISTINCT(viaje.Cli_Cod),cliente.Prs_Cod,Prs_Ced,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente,Prs_Dir";
                }
                else{$campos="COUNT(DISTINCT(viaje.Cli_Cod)) as total";$Par_Sql["limits"]="";}
                $sql = "SELECT $campos FROM viaje
                        INNER JOIN cliente ON viaje.Cli_Cod=cliente.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                        WHERE $search AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
	
        break;
        //Select para cargar los choferes que estan dentro de la tabla viaje
        case 26:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                        $campos="DISTINCT(viaje.Cho_Cod),chofer.Prs_Cod,Prs_Ced AS Prs_Ced1,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS chofer";
                }
                else{$campos="COUNT(DISTINCT(viaje.Cho_Cod)) as total";$Par_Sql["limits"]="";}
                $sql = "SELECT $campos FROM viaje
                        INNER JOIN chofer ON viaje.Cho_Cod=chofer.Cho_Cod
                        INNER JOIN persona ON chofer.Prs_Cod=persona.Prs_Cod
                        WHERE $search AND chofer.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";

        break;
        //Select para cargar los datos de la tabla chofer
        case 27:
            $sql = "SELECT Cho_Cod,chofer.Prs_Cod,Prs_Ced,Prs_Ape,Prs_Nom,persona.Ciu_Cod,Prs_Dir,Cho_Tli,Cho_Est,Cho_Tel
                    FROM chofer
                    INNER JOIN persona ON chofer.Prs_Cod=persona.Prs_Cod
                    INNER JOIN ciudad ON persona.Ciu_Cod=ciudad.Ciu_Cod
                    WHERE chofer.Emp_Cod=$Par_Sql[0] AND Cho_Est='A'";
			//echo $sql;
        break;
        //Update sobre la tabla chofer
        case 28:
            $sql = "UPDATE chofer SET Cho_Tli=UPPER('$Par_Sql[1]'),Cho_Est='$Par_Sql[2]',Cho_Tel='$Par_Sql[3]'
                    WHERE Cho_Cod='$Par_Sql[0]'";
        break;
        //Update sobre la tabla persona relacionada con el chofer
        case 29:
            $sql = "UPDATE persona SET Prs_Ape=UPPER('$Par_Sql[1]'),Prs_Nom=UPPER('$Par_Sql[2]'),Ciu_Cod='$Par_Sql[3]',Prs_Dir=UPPER('$Par_Sql[4]')
                    WHERE Prs_Cod='$Par_Sql[0]'";
        break;
        //Select para cargar los datos de la tabla vehiculo
        case 30:
            $sql = "SELECT *
                    FROM vehiculo
                    WHERE Veh_Est='A' AND Emp_Cod='$Par_Sql[0]'";
        break;
        //Update sobre la tabla vehiculo
        case 31:
            $sql = "UPDATE vehiculo SET Veh_Mar=UPPER('$Par_Sql[1]'),Veh_Pla=UPPER('$Par_Sql[2]'),Veh_Col=UPPER('$Par_Sql[3]'),Veh_Est='$Par_Sql[4]'
                    WHERE Veh_Cod='$Par_Sql[0]'";
        break;
        //Select para cargar los datos de la tabla vehiculo
        case 32:
            $sql = "SELECT Car_Cod,Ite_Lar,Car_Des,Car_Est,cargamento.Pro_Cod
                    FROM cargamento
                    INNER JOIN producto ON producto.Pro_Cod=cargamento.Pro_Cod
                    INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                    INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                    WHERE Car_Est='A' AND categorias.Emp_Cod='$Par_Sql[0]'";
        break;
        //Update sobre la tabla cargamento
        case 33:
            $sql = "UPDATE cargamento SET Car_Des=UPPER('$Par_Sql[1]'),Pro_Cod='$Par_Sql[2]',Car_Est='$Par_Sql[3]'
                    WHERE Car_Cod='$Par_Sql[0]'";
        break;
        //Select para cargar los datos de la tabla vehiculo
        case 34:
            $sql = "SELECT *
                    FROM modo_trabajo
                    WHERE Mot_Est='A' AND Emp_Cod='$Par_Sql[0]'";
        break;
        //Update sobre la tabla modo_trabajo
        case 35:
            $sql = "UPDATE modo_trabajo SET Mot_Des=UPPER('$Par_Sql[1]'),Mot_Est='$Par_Sql[2]'
                    WHERE Mot_Cod='$Par_Sql[0]'";
        break;
		 //Update sobre la tabla viaje
        case 36:
            $sql = "UPDATE viaje SET Cli_Cod='$Par_Sql[1]' WHERE Via_Cod='$Par_Sql[0]'";
        break;
    }
    return $sql;
}
