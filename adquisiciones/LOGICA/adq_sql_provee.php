<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2021-09-17
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package inv.LOGICA
 */

function sentencias_provee($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            //echo $sql.'<br/>';
            break;
        case 1:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos = "proveedore.Prv_Cod,proveedore.Prs_Cod,Prv_fax,Prv_est,Tco_cod,Prv_Esp,Prv_Con,Prv_Reg,Prv_Ris,Prv_Gct,Prv_Rim_Emp,Prv_Rim_Np,Prv_Ag_Ret,Prv_Tic,Prv_Tac,if(Prv_Tic='N','Natural','Jurídico')as PrvTic,Emp_cod,proveedore.Prv_Com, Prs_Ced,Prs_Ced as Prs_Ced_Ant ,Prs_Ape, persona.Ide_Cod,Ide_Sri,Prs_Ape,Prs_Nom, CONCAT(Prs_Ape,' ',Prs_Nom) AS proveedor,Prs_Sex,persona.Ciu_Cod,Prs_Dir,Prv_Tel,Prs_Te2,Prs_Cel,Prv_Cor, provee_aut.Tri_Cod,provee_aut.Tic_Cod,provee_aut.Prd_Aut,provee_aut.Prd_Imp,provee_aut.Prd_Cad, provee_aut.Ciu_Cod as Ciu_Cod_Aut, provee_aut.Ren_Cod_Ren, provee_aut.Ren_Cod_Iva";
            } else {
                $campos = "COUNT(proveedore.Prv_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM proveedore
                            LEFT JOIN provee_aut ON proveedore.Prv_Cod = provee_aut.Prv_Cod
                            INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod
                            INNER JOIN identifica ON persona.Ide_Cod=identifica.Ide_Cod
                            WHERE $search AND Prv_Est='A' AND proveedore.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits] ";
            break;
        case 2:
            $sql = "SELECT Ciu_Cod, Ciu_Des, Pro_Nom, Pas_Nom  FROM ciudad "
                . "INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod "
                . "INNER JOIN pais ON pais.pas_cod=ciudad.pas_cod "
                . "WHERE Ciu_Des != ''  ORDER BY Ciu_Des ASC";
            //echo $sql;
            break;
        case 3:
            /* identificacion */
            $sql = "SELECT * FROM identifica WHERE Ide_Prc IS NOT NULL AND Ide_Prc<>'';";
            //echo $sql."<br>";
            break;
        case 4:
            //actualiza tabla Proveedore
            $sql = "UPDATE proveedore SET Prv_Com='$Par_Sql[Prv_Com]',"
                . " Prv_Tic='$Par_Sql[Prv_Tic]',"
                . " Prv_Tel='$Par_Sql[Prv_Tel]',"
                . " Prv_Cor='$Par_Sql[Prv_Cor]',"
                . " Prv_Esp='$Par_Sql[Prv_Esp]',"
                . " Prv_Con='$Par_Sql[Prv_Con]',"
                . " Prv_Reg='$Par_Sql[Prv_Reg]',"
                . " Prv_Ris='$Par_Sql[Prv_Ris]',"
                . " Prv_Gct='$Par_Sql[Prv_Gct]',"
                . " Prv_Rim_Emp='$Par_Sql[Prv_Rim_Emp]',"
                . " Prv_Rim_Np='$Par_Sql[Prv_Rim_Np]',"
                . " Prv_Ag_Ret='$Par_Sql[Prv_Ag_Ret]',"
                . " Prv_Tac='$Par_Sql[Prv_Tac]' "
                . "WHERE Prv_Cod='$Par_Sql[Prv_Cod]'; ";
            break;
        case 5:
            $sql = "SELECT persona.* FROM persona WHERE Prs_Ced LIKE '$Par_Sql[0]%'";
            break;
        case 6:
            //Actualiza Tabla Persona
            $sql = "UPDATE persona SET Prs_Ced='$Par_Sql[Prs_Ced]',"
                . " Prs_Sex='$Par_Sql[Prs_Sex]',"
                . " Prs_Ape='$Par_Sql[Prs_Ape]',"
                . " Prs_Nom='$Par_Sql[Prs_Nom]',"
                . " Ciu_Cod='$Par_Sql[Ciu_Cod]',"
                . " Ide_Cod='$Par_Sql[Ide_Cod]',"
                . " Prs_Dir='$Par_Sql[Prs_Dir]' "
                . "WHERE Prs_Cod='$Par_Sql[Prs_Cod]';";
            //echo $sql."<br>";
            break;
        case 7:
            //inserta datos en tabla Proveedor
            $sql = "INSERT INTO persona (Prs_Ced,Prs_Sex,Prs_Ape,Prs_Nom,Ciu_Cod,Prs_Dir,Ide_Cod) VALUES ("
                . "'$Par_Sql[Prs_Ced]',"
                . "'$Par_Sql[Prs_Sex]',"
                . "'$Par_Sql[Prs_Ape]',"
                . "'$Par_Sql[Prs_Nom]',"
                . "'$Par_Sql[Ciu_Cod]',"
                . "'$Par_Sql[Prs_Dir]',"
                . "'$Par_Sql[Ide_Cod]');";
            break;
        case 8:
            $sql = "INSERT INTO proveedore (Emp_Cod,Prs_Cod,Prv_Com,Prv_Tic,Prv_Tel,Prv_Cor,Prv_Esp,Prv_Con,Prv_Reg,Prv_Ris,
            Prv_Gct,Prv_Rim_Emp, Prv_Rim_Np ,Prv_Ag_Ret, Prv_Tac, Prv_Est) VALUES ("
                . "'$Par_Sql[Emp_Cod]',"
                . "'$Par_Sql[Prs_Cod]',"
                . "'$Par_Sql[Prv_Com]',"
                . "'$Par_Sql[Prv_Tic]',"
                . "'$Par_Sql[Prv_Tel]',"
                . "'$Par_Sql[Prv_Cor]',"
                . "'$Par_Sql[Prv_Esp]',"
                . "'$Par_Sql[Prv_Con]',"
                . "'$Par_Sql[Prv_Reg]',"
                . "'$Par_Sql[Prv_Ris]',"
                . "'$Par_Sql[Prv_Gct]',"
                . "'$Par_Sql[Prv_Rim_Emp]',"
                . "'$Par_Sql[Prv_Rim_Np]',"
                . "'$Par_Sql[Prv_Ag_Ret]',"
                . "'$Par_Sql[Prv_Tac]',"
                . "'A');";
            break;
        case 9:
            $sql = "SELECT Prv_Cod, Emp_Cod FROM proveedore WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";
            break;
        case 10: //Busqueda de Proveedores
            $sql = "SELECT Emp_Cod,persona.*,proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor FROM persona  
                    LEFT JOIN proveedore ON proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]
                    WHERE Prs_Ced LIKE '$Par_Sql[0]%'  LIMIT 10;";
            //echo $sql;
            break;

        case 11:
            //elimina datos del proveedor en la tabla provee_aut
            $sql = "DELETE FROM provee_aut WHERE Prv_Cod = '$Par_Sql[Prv_Cod]';";
            break;

        case 12:
            //inserta datos en tabla default Proveedor
            $Tri_Cod = $Par_Sql['Tri_Cod'];
            $Tic_Cod = $Par_Sql['Tic_Cod'];
            $Ciu_Cod_Aut = $Par_Sql['Ciu_Cod_Aut'];
            $Prd_Aut = $Par_Sql['Prd_Aut'];
            $Prd_Imp = $Par_Sql['Prd_Imp'];
            $Prd_Cad = $Par_Sql['Prd_Cad'];
            $Ren_Cod_Ren = $Par_Sql['Ren_Cod_Ren'];
            $Ren_Cod_Iva = $Par_Sql['Ren_Cod_Iva'];

            if ($Tri_Cod == '') {
                $Tri_Cod = "NULL";
            }
            if ($Tic_Cod == '') {
                $Tic_Cod = "NULL";
            }
            if ($Ciu_Cod_Aut == '') {
                $Ciu_Cod_Aut = "NULL";
            }
            if ($Prd_Aut == '') {
                $Prd_Aut = "NULL";
            }

            if ($Prd_Imp == '') {
                $Prd_Imp = "NULL";
            } else {
                $Prd_Imp = "'" . $Par_Sql['Prd_Imp'] . "'";
            }

            if ($Prd_Cad == '') {
                $Prd_Cad = "NULL";
            } else {
                $Prd_Cad = "'" . $Par_Sql['Prd_Cad'] . "'";
            }

            if ($Ren_Cod_Ren == '') {
                $Ren_Cod_Ren = "NULL";
            }
            if ($Ren_Cod_Iva == '') {
                $Ren_Cod_Iva = "NULL";
            }

            $sql = "INSERT INTO provee_aut (Prv_Cod,Tri_Cod,Tic_Cod,Ciu_Cod,Prd_Aut,Prd_Imp,Prd_Cad,Ren_Cod_Ren,Ren_Cod_Iva) VALUES ("
                . "'$Par_Sql[Prv_Cod]',"
                . $Tri_Cod . ","
                . $Tic_Cod . ","
                . $Ciu_Cod_Aut . ","
                . $Prd_Aut . ","
                . $Prd_Imp . ","
                . $Prd_Cad . ","
                . $Ren_Cod_Ren . ","
                . $Ren_Cod_Iva . ");";
            break;

        case 18:
            if ($Par_Sql[3] == "d") {
                $search = "det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";
            } else {
                $search = "det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[4] == "") {
                $campos = "COUNT(det_plan.Pld_Cod) as total";
            } else {
                $Par_Sql[4] = "ORDER BY det_plan.Pld_Cod " . $Par_Sql[4];
                $campos = "det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, /*empresas.Emp_Nom,*/ Pla_Obs,
                                IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                                IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
            }
            $sql = "SELECT $campos
                                FROM det_plan 
                                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                                LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                                LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                                WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
                                AND $search AND plan_cuenta.Pla_Cod =$Par_Sql[2] 
                                AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
            break;

        case 99:
            $sql = "SELECT perio_cont.* FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
            //echo $sql;
            break;

        case 47:
            if (empty($Par_Sql['limits'])) $campos = "COUNT(renta_iva.Ren_Cod) AS total";
            else $campos = "Adq_Cod,renta_iva.Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,renta_iva.Ren_Tip,if(renta_iva.Ren_Tip='B','BIENES','SERVICIO')as Ren_Tipo,Ren_Ret,if(Ren_Ret='R','RENTA','IVA')as Ren_Rete,Ren_Est,if(Ren_Est='A','Activo','Anulado')as Ren_Esta";
            if ($Par_Sql['op_opciones'] == 'd') $where = "(Ren_Con LIKE '$Par_Sql[search]%' OR Ren_Con LIKE '%$Par_Sql[search]%')";
            else if ($Par_Sql['op_opciones'] == 'c') $where = "Ren_Sri LIKE '$Par_Sql[search]%'";
            else {
                if (!empty($Par_Sql['search'])) $where = "Ren_Por = '$Par_Sql[search]'";
                else $where = "";
            }
            $sql = "SELECT $campos FROM renta_iva WHERE Ren_Est='A' AND Ren_Ret='$Par_Sql[tipo]'" . (!empty($where) ? "AND $where " : '') . (!empty($Par_Sql['limits']) ? " ORDER BY Ren_Sri ASC $Par_Sql[limits];" : ';');
            //echo $sql.'<br/>';
            break;

        case 60:
            $sql = "SELECT reniva_pla.Pld_Cod, Pld_Cdc, Pld_Des FROM reniva_pla INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod WHERE Ren_Cod='$Par_Sql[1]' AND det_plan.Pla_Cod='$Par_Sql[0]' AND Ren_Tip='$Par_Sql[2]'";
            //echo $sql.'<br/>';
            break;

        case 88:
            $sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            //echo $sql;
            break;

        case 89: // Obtener proveedores de otra empresa para copiar
            $search_clause = "";
            if (!empty($Par_Sql['search'])) {
                if ($Par_Sql['op_opciones'] == "d") {
                    $search_clause = "(persona.Prs_Ape LIKE '%$Par_Sql[search]%' OR persona.Prs_Nom LIKE '%$Par_Sql[search]%')";
                } else {
                    $search_clause = "persona.Prs_Ced LIKE '$Par_Sql[search]%'";
                }
            }
            
            $estado_clause = "";
            if ($Par_Sql['est_opciones'] == "a") {
                $estado_clause = "proveedore.Prv_Est = 'A'";
            } else {
                $estado_clause = "proveedore.Prv_Est = 'I'";
            }
            
            $where_clauses = array();
            if (!empty($search_clause)) {
                $where_clauses[] = $search_clause;
            }
            if (!empty($estado_clause)) {
                $where_clauses[] = $estado_clause;
            }
            $where_sql = implode(" AND ", $where_clauses);
            if (!empty($where_sql)) {
                $where_sql = "AND " . $where_sql;
            }

            $campos = empty($Par_Sql['limits']) ? " COUNT(proveedore.Prv_Cod) AS total" : "proveedore.Prv_Cod, proveedore.Prs_Cod, proveedore.Emp_Cod, persona.Prs_Ced, persona.Prs_Tel, persona.Prs_Cor, persona.Prs_Dir, persona.Prs_Nom, persona.Prs_Ape, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, proveedore.Prv_Tic, proveedore.Prv_Con, proveedore.Prv_Com, proveedore.Prv_Esp, proveedore.Prv_Reg, proveedore.Prv_Ris, proveedore.Prv_Gct, proveedore.Prv_Rim_Emp, proveedore.Prv_Rim_Np, proveedore.Prv_Ag_Ret, proveedore.Prv_Tel, proveedore.Prv_Cor as Prv_Cor";
            $sql = "SELECT $campos
                    FROM proveedore
                    INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
                    WHERE (proveedore.Emp_Cod = '$Par_Sql[Emp_Cod_Origen]') $where_sql $Par_Sql[limits];";
            return $sql;
            break;
    
        case 90: // Verificar si un proveedor ya existe en la empresa destino
            $sql = "SELECT Prv_Cod FROM proveedore WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";
            return $sql;
            break;
    }
    //echo $sql."<br/>";
    return $sql;
}
