<?php

/**
 * Recursos Humanos (rrhh)
 */
function sentencias_rrhh($id, $Par_Sql)
{
    switch ($id) {
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
            $sql = "INSERT INTO personal (Prs_Cod,Emp_Cod,Per_Car,Per_Tit,Per_Obs,Per_Cfi,Per_Req,Per_Rso,Per_Mov,Per_Tcf) 
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]',$Par_Sql[6],'$Par_Sql[7]','$Par_Sql[8]','$Par_Sql[9]')";
            //echo $sql;
            break;
            //Update en la tabla personal para actualizar la foto de perfil (Per_Fot)
        case 6:
            $sql = "UPDATE personal SET Per_Fot='$Par_Sql[1]' WHERE Per_Cod='$Par_Sql[0]'";
            break;
            //Select para listar todas las personas registradas en la tabla personal
        case 7:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos = "Per_Cod,Ide_Des,ciudad.Ciu_Cod,Ciu_Des,Prs_Ced,Prs_Ape,Prs_Nom,IF(personal.Per_Req=1,'Si','No') AS Per_Req,IF(personal.Per_Req=1,1,0) AS requisitor,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,
                Prs_Sex,IF(Prs_Sex='M','Masculino','Femenino') AS Prs_Gen,Prs_Esc,Per_Rso,Per_Mov,Per_Tcf,Per_Cfi,Prs_Fec,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,Prs_San,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Car,Per_Tit,TIMESTAMPDIFF(YEAR, Prs_fec, CURDATE()) AS Prs_Eda,
                CASE Per_Tit
                    WHEN 'Np' THEN 'NO POSEE'
                    WHEN 'Abg' THEN 'ABOGADO/A'
                    WHEN 'Bac' THEN 'BACHILLER'
                    WHEN 'Dr' THEN 'DOCTOR/A'
                    WHEN 'Sec' THEN 'SECUNDARIA'
                    WHEN 'Unv' THEN 'UNIVERSITARIO'
                    WHEN 'Eco' THEN 'ECONOMISTA'
                    WHEN 'Ing' THEN 'INGENIERO/A'
                    WHEN 'Lcd' THEN 'LICENCIADO/A'
                    WHEN 'Mst' THEN 'MAESTRIA'
                    WHEN 'Phd' THEN 'PHD'
                    WHEN '' THEN '(Sin definir)'
                    ELSE COALESCE(NULLIF(TRIM(personal.Per_Tit),''), '(Sin definir)')
                END AS Per_Ti1";
            } else {
                $campos = "COUNT(Per_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM persona
                    INNER JOIN personal ON personal.Prs_Cod=persona.Prs_Cod
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
                    LEFT JOIN ciudad ON ciudad.Ciu_Cod=persona.Ciu_Cod
                    WHERE $search AND Per_Est='A' AND personal.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            //echo $sql;
            break;

        //Update en la tabla persona y personal
        case 8:
            if (empty($Par_Sql[16])) {
                $Par_Sql[16] = "NULL";
            } else {
                $Par_Sql[16] = "'$Par_Sql[16]'";
            }
            $sql = "UPDATE persona,personal SET Prs_Nom='$Par_Sql[1]',Prs_Ape='$Par_Sql[2]',Prs_Sex='$Par_Sql[3]',Prs_Esc='$Par_Sql[4]',Prs_Fec='$Par_Sql[5]',Ciu_Cod='$Par_Sql[6]',Prs_Tel='$Par_Sql[7]',Prs_Te2='$Par_Sql[8]',Prs_Cel='$Par_Sql[9]',Prs_Cor='$Par_Sql[10]',Prs_Dir='$Par_Sql[11]',Prs_San='$Par_Sql[12]',Per_Car='$Par_Sql[13]',Per_Tit='$Par_Sql[14]',Per_Obs='$Par_Sql[15]',Per_Fot=$Par_Sql[16],Per_Cfi='$Par_Sql[17]'";
            if (isset($Par_Sql[18])) {
                $sql .= ",Per_Req='$Par_Sql[18]'";
            }
            if (isset($Par_Sql[19])) {
                $sql .= ",Per_Rso='$Par_Sql[19]'";
            }
            if (isset($Par_Sql[20])) {
                $sql .= ",Per_Mov='$Par_Sql[20]'";
            }
            if (isset($Par_Sql[21])) {
                $sql .= ",Per_Tcf='$Par_Sql[21]'";
            }
            $sql .= " WHERE Per_Cod='$Par_Sql[0]' AND personal.Prs_Cod=persona.Prs_Cod";
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
            $sql = "SELECT Per_Cod,Ide_Des,ciudad.Ciu_Cod,Ciu_Des,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,IF(Prs_Sex='M','Masculino','Femenino') AS Prs_Gen,IF(Prs_Esc='S','SOLTERO/A',IF(Prs_Esc='C','CASADO/A',IF(Prs_Esc='D','DIVORCIADO/A',IF(Prs_Esc='V','VIUDO/A',IF(Prs_Esc='U','UNION LIBRE',''))))) AS Prs_Esc,Prs_Fec,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor,Prs_Dir,Prs_San,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Car,
            CASE Per_Tit
                    WHEN 'Np' THEN 'NO POSEE'
                    WHEN 'Abg' THEN 'ABOGADO/A'
                    WHEN 'Bac' THEN 'BACHILLER'
                    WHEN 'Dr' THEN 'DOCTOR/A'
                    WHEN 'Sec' THEN 'SECUNDARIA'
                    WHEN 'Unv' THEN 'UNIVERSITARIO'
                    WHEN 'Eco' THEN 'ECONOMISTA'
                    WHEN 'Ing' THEN 'INGENIERO/A'
                    WHEN 'Lcd' THEN 'LICENCIADO/A'
                    WHEN 'Mst' THEN 'MAESTRIA'
                    WHEN 'Phd' THEN 'PHD'
                    WHEN '' THEN '(Sin definir)'
                    ELSE COALESCE(NULLIF(TRIM(personal.Per_Tit),''), '(Sin definir)')            
                END AS Per_Tit,
                CASE Per_Tcf
                    WHEN 'AP' THEN 'APTO'
                    WHEN 'AO' THEN 'APTO CON OBSERVACION'
                    WHEN 'AL' THEN 'APTO CON LIMITACIONES'
                    WHEN 'NA' THEN 'NO APTO'                    
                    WHEN '' THEN '(Sin definir)'
                    ELSE COALESCE(NULLIF(TRIM(personal.Per_Tcf),''), '(Sin definir)')
                END AS Per_Tcf,
                CASE Per_Rso
                    WHEN 'A' THEN 'ALTO'
                    WHEN 'M' THEN 'MEDIO'
                    WHEN 'B' THEN 'BAJO'
                    WHEN '' THEN '(Sin definir)'
                    ELSE COALESCE(NULLIF(TRIM(personal.Per_Rso),''), '(Sin definir)')
                END AS Per_Rso,
                CASE Per_Mov
                    WHEN 'BU' THEN 'BUS'
                    WHEN 'MO' THEN 'MOTO'
                    WHEN 'VP' THEN 'VEHICULO PARTICULAR'
                    WHEN 'CA' THEN 'CAMINANDO'
                    WHEN 'BI' THEN 'BICICLETA'
                    WHEN 'VI' THEN 'VEHICULO INSTITUCIONAL'
                    WHEN '' THEN '(Sin definir)'
                    ELSE COALESCE(NULLIF(TRIM(personal.Per_Mov),''), '(Sin definir)')
                END AS Per_Mov,
                Per_Obs,IF(ISNULL(Per_Fot),'no',Per_Fot) AS Per_Fot,Per_Cfi, IF (Per_Est='A','Activo','Inactivo') as Per_Est,CURDATE() AS Fec_Sys
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
            // Obtener informacion de los contratos
        case 17:
            $sql = "SELECT * from contratos_lab 
            INNER JOIN dedica_lab ON contratos_lab.Ded_Cod = dedica_lab.Ded_Cod
            INNER JOIN relaci_lab ON contratos_lab.Reb_Cod = relaci_lab.Reb_Cod
            INNER JOIN sueldos ON contratos_lab.Con_Cod = sueldos.Con_Cod
            INNER JOIN afiliacion ON contratos_lab.Con_Cod = afiliacion.Con_Cod
            INNER JOIN pago_contrato ON contratos_lab.Con_Cod = pago_contrato.Con_Cod
            WHERE contratos_lab.Per_Cod ='$Par_Sql[0]'";
            break;
            // Dashboard: personal activo por género (persona.Prs_Sex)
        case 18:
            $emp = intval($Par_Sql[0]);
            $sexGrp = "COALESCE(NULLIF(TRIM(persona.Prs_Sex),''),'?')";
            $sql = "SELECT $sexGrp AS Prs_Sex, COUNT(*) AS total
                    FROM personal
                    INNER JOIN persona ON persona.Prs_Cod = personal.Prs_Cod
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY $sexGrp";
            break;
            // Dashboard: personal activo por nivel de estudio (personal.Per_Tit)
        case 19:
            $emp = intval($Par_Sql[0]);
            $titGrp = "COALESCE(NULLIF(TRIM(personal.Per_Tit),''),'')";
            $sql = "SELECT $titGrp AS Per_Tit_Cod,
                    CASE $titGrp
                        WHEN 'Np' THEN 'NO POSEE'
                        WHEN 'Abg' THEN 'ABOGADO/A'
                        WHEN 'Bac' THEN 'BACHILLER'
                        WHEN 'Dr' THEN 'DOCTOR/A'
                        WHEN 'Sec' THEN 'SECUNDARIA'
                        WHEN 'Unv' THEN 'UNIVERSITARIO'
                        WHEN 'Eco' THEN 'ECONOMISTA'
                        WHEN 'Ing' THEN 'INGENIERO/A'
                        WHEN 'Lcd' THEN 'LICENCIADO/A'
                        WHEN 'Mst' THEN 'MAESTRIA'
                        WHEN 'Phd' THEN 'PHD'
                        WHEN '' THEN '(Sin definir)'
                        ELSE COALESCE(NULLIF(TRIM(personal.Per_Tit),''), '(Sin definir)')
                    END AS titulo_des,
                    COUNT(*) AS total
                    FROM personal
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY $titGrp
                    ORDER BY total DESC";
            break;
            // Dashboard: total personal activo (sin depender de persona)
        case 25:
            $emp = intval($Par_Sql[0]);
            $sql = "SELECT COUNT(*) AS total
                    FROM personal
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'";
            break;
            // Dashboard: personal activo por ciudad
        case 20:
            $emp = intval($Par_Sql[0]);
            $sql = "SELECT COALESCE(ciudad.Ciu_Des,'(Sin ciudad)') AS Ciu_Des, COUNT(*) AS total
                    FROM personal
                    INNER JOIN persona ON persona.Prs_Cod = personal.Prs_Cod
                    LEFT JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY ciudad.Ciu_Des
                    ORDER BY total DESC";
            break;
            // Dashboard: personal activo por tipo de movilización (Per_Mov)
        case 21:
            $emp = intval($Par_Sql[0]);
            $movGrp = "COALESCE(NULLIF(TRIM(personal.Per_Mov),''),'(Sin definir)')";
            $sql = "SELECT $movGrp AS Per_Mov_Cod,
                    CASE TRIM(personal.Per_Mov)
                        WHEN 'BU' THEN 'BUS'
                        WHEN 'MO' THEN 'MOTO'
                        WHEN 'VP' THEN 'VEHICULO PARTICULAR'
                        WHEN 'CA' THEN 'CAMINANDO'
                        WHEN 'BI' THEN 'BICICLETA'
                        WHEN 'VI' THEN 'VEHICULO INSTITUCIONAL'
                        ELSE COALESCE(NULLIF(TRIM(personal.Per_Mov),''),'(Sin definir)')
                    END AS mov_des,
                    COUNT(*) AS total
                    FROM personal
                    INNER JOIN persona ON persona.Prs_Cod = personal.Prs_Cod
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY $movGrp
                    ORDER BY total DESC";
            break;
            // Dashboard: proveedores activos por tipo de actividad (Prv_Tac)
        case 22:
            $emp = intval($Par_Sql[0]);
            $sql = "SELECT COALESCE(NULLIF(TRIM(proveedore.Prv_Tac),''),'(Sin actividad)') AS actividad,
                    COUNT(*) AS total
                    FROM proveedore
                    WHERE proveedore.Emp_Cod = $emp AND proveedore.Prv_Est = 'A'
                    GROUP BY COALESCE(NULLIF(TRIM(proveedore.Prv_Tac),''),'(Sin actividad)')
                    ORDER BY total DESC";
            break;
            // Dashboard: personal activo por riesgo social (Per_Rso)
        case 23:
            $emp = intval($Par_Sql[0]);
            $rsoGrp = "COALESCE(NULLIF(TRIM(personal.Per_Rso),''),'(Sin definir)')";
            $sql = "SELECT $rsoGrp AS Per_Rso_Cod,
                    CASE TRIM(personal.Per_Rso)
                        WHEN 'A' THEN 'ALTO'
                        WHEN 'M' THEN 'MEDIO'
                        WHEN 'B' THEN 'BAJO'
                        ELSE COALESCE(NULLIF(TRIM(personal.Per_Rso),''),'(Sin definir)')
                    END AS rso_des,
                    COUNT(*) AS total
                    FROM personal
                    INNER JOIN persona ON persona.Prs_Cod = personal.Prs_Cod
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY $rsoGrp
                    ORDER BY total DESC";
            break;
            // Dashboard: ingreso mensual por rango (ultimo mes por area en ultimo periodo)
        case 24:
            $emp = intval($Par_Sql[0]);
            $pecUltSql = "(SELECT pec.Pec_Cod
                FROM perio_cont pec
                INNER JOIN plan_cuenta pla ON pla.Pla_Cod = pec.Pla_Cod AND pla.Emp_Cod = $emp AND pla.Pla_Est = 'A'
                WHERE pec.Pec_Est = 'A'
                ORDER BY pec.Pec_Fei DESC, pec.Pec_Cod DESC
                LIMIT 1)";
            $mesAreaSql = "(SELECT DATE_FORMAT(MAX(IFNULL(rpM.Rol_Fef, rpM.Rol_Fei)), '%Y-%m')
                FROM rol_pagos rpM
                INNER JOIN det_rpagos drM ON drM.Rol_Cod = rpM.Rol_Cod
                INNER JOIN campo_rol crM ON crM.Cam_Cod = drM.Cam_Cod
                    AND crM.Cam_Var IN ('total_ingr', 'total_ing')
                WHERE rpM.Are_Cod = rp1.Are_Cod AND rpM.Rol_Est = 'A' AND rpM.Pec_Cod = $pecUltSql
                    AND TRIM(drM.Rol_Val) <> '' AND TRIM(drM.Rol_Val) <> '0')";
            $sql = "SELECT datos.rango_ord, datos.rango_des, COUNT(DISTINCT datos.Per_Cod) AS total
                    FROM (
                        SELECT personal.Per_Cod,
                        CASE
                            WHEN ing.ing_val < 450 THEN 1
                            WHEN ing.ing_val <= 600 THEN 2
                            WHEN ing.ing_val <= 800 THEN 3
                            ELSE 4
                        END AS rango_ord,
                        CASE
                            WHEN ing.ing_val < 450 THEN '< \$450'
                            WHEN ing.ing_val <= 600 THEN '\$450 - \$600'
                            WHEN ing.ing_val <= 800 THEN '\$601 - \$800'
                            ELSE '> \$800'
                        END AS rango_des
                        FROM personal
                        INNER JOIN contratos_lab cl ON cl.Per_Cod = personal.Per_Cod AND cl.Con_Est = 'A'
                        INNER JOIN (
                            SELECT dr.Con_Cod,
                            CAST(
                                REPLACE(
                                    REPLACE(REPLACE(TRIM(dr.Rol_Val), '\$', ''), ' ', ''),
                                    ',', '.'
                                ) AS DECIMAL(14,2)
                            ) AS ing_val
                            FROM det_rpagos dr
                            INNER JOIN campo_rol cr ON cr.Cam_Cod = dr.Cam_Cod
                                AND cr.Cam_Var IN ('total_ingr', 'total_ing')
                            INNER JOIN rol_pagos rp ON rp.Rol_Cod = dr.Rol_Cod AND rp.Rol_Est = 'A'
                                AND rp.Pec_Cod = $pecUltSql
                            INNER JOIN areas_rrhh ar ON ar.Are_Cod = rp.Are_Cod AND ar.Emp_Cod = $emp AND ar.Are_Est = 'A'
                            INNER JOIN (
                                SELECT rp1.Are_Cod,
                                SUBSTRING_INDEX(
                                    GROUP_CONCAT(
                                        rp1.Rol_Cod
                                        ORDER BY IFNULL(rp1.Rol_Fef, rp1.Rol_Fei) DESC, rp1.Rol_Num DESC, rp1.Rol_Cod DESC
                                    ),
                                    ',', 1
                                ) AS Rol_Cod
                                FROM rol_pagos rp1
                                INNER JOIN areas_rrhh ar1 ON ar1.Are_Cod = rp1.Are_Cod
                                    AND ar1.Emp_Cod = $emp AND ar1.Are_Est = 'A'
                                INNER JOIN det_rpagos drx ON drx.Rol_Cod = rp1.Rol_Cod
                                INNER JOIN campo_rol crx ON crx.Cam_Cod = drx.Cam_Cod
                                    AND crx.Cam_Var IN ('total_ingr', 'total_ing')
                                WHERE rp1.Rol_Est = 'A' AND rp1.Pec_Cod = $pecUltSql
                                    AND TRIM(drx.Rol_Val) <> '' AND TRIM(drx.Rol_Val) <> '0'
                                    AND DATE_FORMAT(IFNULL(rp1.Rol_Fef, rp1.Rol_Fei), '%Y-%m') = $mesAreaSql
                                GROUP BY rp1.Are_Cod
                            ) ult_rol ON ult_rol.Rol_Cod = rp.Rol_Cod
                            WHERE TRIM(dr.Rol_Val) <> '' AND TRIM(dr.Rol_Val) <> '0'
                        ) ing ON ing.Con_Cod = cl.Con_Cod
                        WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                            AND ing.ing_val IS NOT NULL AND ing.ing_val > 0
                    ) datos
                    GROUP BY datos.rango_ord, datos.rango_des
                    ORDER BY datos.rango_ord";
            break;
            // Dashboard: personal activo por area de trabajo (contrato activo)
        case 27:
            $emp = intval($Par_Sql[0]);
            $sql = "SELECT COALESCE(areas_rrhh.Are_Des, '(Sin area)') AS are_des,
                    COUNT(DISTINCT personal.Per_Cod) AS total
                    FROM personal
                    LEFT JOIN contratos_lab cl ON cl.Per_Cod = personal.Per_Cod AND cl.Con_Est = 'A'
                    LEFT JOIN tiposcargo ON tiposcargo.Tic_Cod = cl.Tic_Cod
                    LEFT JOIN departamen ON departamen.Dep_Cod = tiposcargo.Dep_Cod
                    LEFT JOIN areas_rrhh ON areas_rrhh.Are_Cod = departamen.Are_Cod AND areas_rrhh.Emp_Cod = $emp
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY COALESCE(areas_rrhh.Are_Des, '(Sin area)')
                    ORDER BY total DESC";
            break;
            // Dashboard: personal activo por carga familiar (Per_Car)
        case 28:
            $emp = intval($Par_Sql[0]);
            $carGrp = "COALESCE(NULLIF(TRIM(personal.Per_Car),''),'?')";
            $sql = "SELECT $carGrp AS Per_Car_Cod,
                    CASE $carGrp
                        WHEN '?' THEN '(Sin definir)'
                        ELSE CONCAT(TRIM(personal.Per_Car), ' dependiente(s)')
                    END AS car_des,
                    COUNT(*) AS total
                    FROM personal
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY $carGrp
                    ORDER BY CASE $carGrp WHEN '?' THEN 999 ELSE CAST($carGrp AS UNSIGNED) END";
            break;
            // Dashboard: personal activo por condicion medica (Per_Tcf)
        case 29:
            $emp = intval($Par_Sql[0]);
            $tcfGrp = "COALESCE(NULLIF(TRIM(personal.Per_Tcf),''),'')";
            $sql = "SELECT $tcfGrp AS Per_Tcf_Cod,
                    CASE $tcfGrp
                        WHEN 'AP' THEN 'APTO'
                        WHEN 'AO' THEN 'APTO CON OBSERVACION'
                        WHEN 'AL' THEN 'APTO CON LIMITACIONES'
                        WHEN 'NA' THEN 'NO APTO'
                        WHEN '' THEN '(Sin definir)'
                        ELSE COALESCE(NULLIF(TRIM(personal.Per_Tcf),''), '(Sin definir)')
                    END AS tcf_des,
                    COUNT(*) AS total
                    FROM personal
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY $tcfGrp
                    ORDER BY FIELD($tcfGrp, 'AP', 'AO', 'AL', 'NA', '', '?')";
            break;
            // Dashboard: contratos indefinidos, en aprobacion y culminados
        case 30:
            $emp = intval($Par_Sql[0]);
            $sql = "SELECT datos.con_tipo, datos.con_des, COUNT(*) AS total
                    FROM (
                        SELECT cl.Con_Cod,
                        CASE
                            WHEN cl.Con_Fin IS NOT NULL AND cl.Con_Fin >= '9999-12-31' THEN 'indefinido'
                            WHEN cl.Con_Fin IS NOT NULL AND TRIM(cl.Con_Fin) <> '' AND cl.Con_Fin <> '0000-00-00'
                                AND cl.Con_Fin < CURDATE() THEN 'culminado'
                            WHEN cl.Con_Ini IS NOT NULL AND TRIM(cl.Con_Ini) <> '' AND cl.Con_Ini <> '0000-00-00'
                                AND cl.Con_Ini <= CURDATE()
                                AND NOT EXISTS (
                                    SELECT 1 FROM afiliacion af
                                    WHERE af.Con_Cod = cl.Con_Cod AND af.Afi_Est = 'A'
                                ) THEN 'aprobacion'
                            ELSE NULL
                        END AS con_tipo,
                        CASE
                            WHEN cl.Con_Fin IS NOT NULL AND cl.Con_Fin >= '9999-12-31' THEN 'Contratos indefinidos'
                            WHEN cl.Con_Fin IS NOT NULL AND TRIM(cl.Con_Fin) <> '' AND cl.Con_Fin <> '0000-00-00'
                                AND cl.Con_Fin < CURDATE() THEN 'Contratos culminados'
                            WHEN cl.Con_Ini IS NOT NULL AND TRIM(cl.Con_Ini) <> '' AND cl.Con_Ini <> '0000-00-00'
                                AND cl.Con_Ini <= CURDATE()
                                AND NOT EXISTS (
                                    SELECT 1 FROM afiliacion af
                                    WHERE af.Con_Cod = cl.Con_Cod AND af.Afi_Est = 'A'
                                ) THEN 'En aprobacion'
                            ELSE NULL
                        END AS con_des
                        FROM contratos_lab cl
                        INNER JOIN personal p ON p.Per_Cod = cl.Per_Cod
                        WHERE p.Emp_Cod = $emp AND p.Per_Est = 'A'
                            AND (
                                cl.Con_Est = 'A'
                                OR (
                                    cl.Con_Fin IS NOT NULL AND TRIM(cl.Con_Fin) <> '' AND cl.Con_Fin <> '0000-00-00'
                                    AND cl.Con_Fin < CURDATE() AND cl.Con_Fin < '9999-12-31'
                                )
                            )
                    ) datos
                    WHERE datos.con_tipo IS NOT NULL
                    GROUP BY datos.con_tipo, datos.con_des
                    ORDER BY FIELD(datos.con_tipo, 'indefinido', 'aprobacion', 'culminado')";
            break;
            // Dashboard: personal activo por tipo de sangre (persona.Prs_San)
        case 31:
            $emp = intval($Par_Sql[0]);
            $sanGrp = "COALESCE(NULLIF(TRIM(persona.Prs_San),''),'(Sin definir)')";
            $sql = "SELECT $sanGrp AS san_des, COUNT(*) AS total
                    FROM personal
                    INNER JOIN persona ON persona.Prs_Cod = personal.Prs_Cod
                    WHERE personal.Emp_Cod = $emp AND personal.Per_Est = 'A'
                    GROUP BY $sanGrp
                    ORDER BY FIELD($sanGrp, 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', '(Sin definir)'), $sanGrp";
            break;
        //Update en la tabla persona y personal
        case 26:
            $sql = "UPDATE persona
                INNER JOIN personal ON persona.Prs_Cod=personal.Prs_Cod
                SET Prs_Nom='$Par_Sql[Prs_Nom]',Prs_Ape='$Par_Sql[Prs_Ape]',Prs_Sex='$Par_Sql[Prs_Sex]',Prs_Esc='$Par_Sql[Prs_Esc]',Prs_Fec='$Par_Sql[Prs_Fec]',
                    Ciu_Cod='$Par_Sql[Ciu_Cod]',Prs_Tel='$Par_Sql[Prs_Tel]',Prs_Te2='$Par_Sql[Prs_Te2]',Prs_Cel='$Par_Sql[Prs_Cel]',
                    Prs_Cor='$Par_Sql[Prs_Cor]',Prs_Dir='$Par_Sql[Prs_Dir]',Prs_San='$Par_Sql[Prs_San]',Per_Car='$Par_Sql[Per_Car]',
                    Per_Tit='$Par_Sql[Per_Tit]',Per_Obs='$Par_Sql[Per_Obs]',Per_Fot='$Par_Sql[Per_Fot]',Per_Cfi='$Par_Sql[Per_Cfi]',
                    Per_Tcf='$Par_Sql[Per_Tcf]',Per_Rso='$Par_Sql[Per_Rso]',Per_Mov='$Par_Sql[Per_Mov]'
                WHERE personal.Per_Cod='$Par_Sql[Per_Cod]'";
                //echo $sql;
            break;
            
    }

    return $sql;
}
