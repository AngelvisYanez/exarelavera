<?php


function sentencias_rrhh($id,$Par_Sql)
{
    switch ($id){
    	case 1:
            $sql="SELECT * FROM areas_rrhh WHERE Are_Est='A' AND Emp_Cod=$Par_Sql[0]";
            break;

        case 2:
            $sql="SELECT * FROM map_system WHERE Map_Est='A' AND Emp_Cod='$Par_Sql[0]';";
            break;

        case 3:
            $sql="SELECT perio_cont.*,YEAR(Pec_Fei)AS Periodo FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Pec_Est='A' AND Emp_Cod='$Par_Sql[0]' ORDER BY Periodo DESC";
            break;

        case 4:
            $Pec='';
            if(isset($Par_Sql['Pec_Cod'])){
                if(!empty($Par_Sql['Pec_Cod'])&&$Par_Sql['Pec_Cod']!='ALL')  $Pec="AND rol_pagos.Pec_Cod=$Par_Sql[Pec_Cod] ";   
            }
            $condition = '';
            if (isset($Par_Sql['Month']) && !empty($Par_Sql['Month']) && $Par_Sql['Rol_Tip'] != 'S') {
                $fecha = explode("-", $Par_Sql['Month']);
                $mes = $fecha[1];
                if($mes != '00'){
                    $condition = " AND rol_pagos.Rol_Fei LIKE '" . $Par_Sql['Month'] . "%' ";
                }    
            }

            $sql="SELECT DISTINCT rol_pagos.*, areas_rrhh.Are_Des FROM rol_pagos
                    INNER JOIN det_rpagos ON rol_pagos.Rol_Cod=det_rpagos.Rol_Cod
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    INNER JOIN map_system ON campo_rol.Map_Cod=map_system.Map_Cod
                    INNER JOIN areas_rrhh ON rol_pagos.Are_Cod=areas_rrhh.Are_Cod
                    LEFT JOIN compr_rol ON rol_pagos.Rol_Cod=compr_rol.Rol_Cod
                    WHERE map_system.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND Rol_Est = 'A'
                          ".(isset($Par_Sql['Are_Cod'])&&!empty($Par_Sql['Are_Cod'])?" AND areas_rrhh.Are_Cod=$Par_Sql[Are_Cod] ":'').(isset($Par_Sql['Map_Cod'])&&!empty($Par_Sql['Map_Cod'])?" AND map_system.Map_Cod=$Par_Sql[Map_Cod] ":'').(isset($Par_Sql['Rol_Tip'])&&!empty($Par_Sql['Rol_Tip'])?" AND rol_pagos.Rol_Tip='$Par_Sql[Rol_Tip]' ":'').(isset($Par_Sql['Rol_S'])&&!empty($Par_Sql['Rol_S'])?" AND rol_pagos.Rol_Num=$Par_Sql[Rol_S] ":'').$Pec . $condition . 
                          "GROUP BY rol_pagos.Rol_Cod ORDER BY Are_Des,Rol_Num DESC,Rol_Est";
            break;

        case 5:
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, banco.Ban_Cue
					from det_plan, banco
					where
						det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' AND plan_cuenta.Pla_Est='A')
						and banco.Ban_Tip = '$Par_Sql[Ban_Tip]'
						and banco.Pld_Cod=det_plan.Pld_Cod;";
			break;
        
        case 6:
            if($Par_Sql[op_opciones] == 't'){
			$campos = " 'C' as tipo_documento, per.Prs_Ced as identificacion, UPPER(CONCAT(per.Prs_Ape, ' ' ,per.Prs_Nom)) as beneficiario,'ROLES' as concepto, 'CU' as forma_pago, '25' as banco, '00' as tipo_cuenta, paco.Pag_Con_Cue as numero_cuenta,SUM(deanro.Ant_Val) as valor,'RPA' as submotivo";
                $Pag_Con_For = 'T';
                $Pag_Des = 'Transferencia';
            }
            if($Par_Sql[op_opciones] == 'c'){
                $campos = " 'C' as tipo_documento, per.Prs_Ced as identificacion, UPPER(CONCAT(per.Prs_Ape, ' ' ,per.Prs_Nom)) as beneficiario,'ROLES' as concepto, 'Cheque' as forma_pago, '25' as banco, ch.Che_Num as tipo_cuenta, '".$Par_Sql[Ban_Cue]."' as numero_cuenta,SUM(deanro.Ant_Val) as valor,'RPA' as submotivo";
                $Pag_Con_For = 'C';
                $Pag_Des = 'Cheque';
            }
            if($Par_Sql[op_opciones] == 'e'){
                $campos = " 'C' as tipo_documento, per.Prs_Ced as identificacion, UPPER(CONCAT(per.Prs_Ape, ' ' ,per.Prs_Nom)) as beneficiario,'ROLES' as concepto, 'Efectivo' as forma_pago, '00' as banco, '00' as tipo_cuenta, '-' as numero_cuenta,SUM(deanro.Ant_Val) as valor,'RPA' as submotivo";
                $Pag_Con_For = 'E';
                $Pag_Des = 'Efectivo';
            }

			$where = " anro.Ant_Est = 'A' ";
			$Pec='';
            if(isset($Par_Sql['Pec_Cod'])){
                if(!empty($Par_Sql['Pec_Cod'])&&$Par_Sql['Pec_Cod']!='ALL')  $Pec=" AND ropa.Pec_Cod=$Par_Sql[Pec_Cod] ";   
            }
			$groupBy = ' GROUP BY tipo_documento,identificacion,beneficiario,concepto,forma_pago,banco,tipo_cuenta,numero_cuenta,submotivo';
			$orderBy = " ORDER BY beneficiario";

			if($Par_Sql['Roles_Where']=='()'){
				$sql = "SELECT * FROM rol_pagos where Rol_Cod = 0 AND Are_Cod=0 AND Pec_Cod =0 AND Usu_Cod = 0";
			}
			else{
				$sql = "SELECT $campos
					FROM antici_rol as anro 
					INNER JOIN det_an_rol  as deanro ON anro.Ant_Cod = deanro.Ant_Cod AND anro.Ant_Tip = 'B'
					INNER JOIN rol_pagos as ropa ON ropa.Rol_Cod = deanro.Rol_Cod AND ropa.Rol_Est = 'A'
					INNER JOIN contratos_lab as cola ON cola.Con_Cod = anro.Con_Cod AND cola.Con_Est = 'A'
                    INNER JOIN pago_contrato paco ON paco.Con_Cod = cola.Con_Cod AND paco.Pag_Con_For = '" . $Pag_Con_For."'
					INNER JOIN personal as pe ON pe.Per_Cod = cola.Per_Cod
					INNER JOIN persona as per ON pe.Prs_Cod = per.Prs_Cod
					INNER JOIN asientos asi ON deanro.Asi_Cod = asi.Asi_Cod AND asi.Asi_Deh = 'H'
					INNER JOIN tipos_pago as tipa ON tipa.Pag_Cod = deanro.Pag_Cod AND tipa.Pag_Des =  '" . $Pag_Des."'
					INNER JOIN perio_cont as peco ON peco.Pec_Cod = ropa.Pec_Cod AND peco.Pec_Est = 'A'
					INNER JOIN plan_cuenta as plcu ON plcu.Pla_Cod = peco.Pla_Cod
                    LEFT JOIN cheques as ch ON ch.Asi_Cod = asi.Asi_Cod
					WHERE " . $where . " AND asi.Pld_Cod = $Par_Sql[Pld_Cod] AND plcu.Emp_Cod = $Par_Sql[Emp_Cod]
					".(isset($Par_Sql['Are_Cod'])&&!empty($Par_Sql['Are_Cod'])?" AND ropa.Are_Cod=$Par_Sql[Are_Cod] ":'') . (isset($Par_Sql['Rol_Cod'])&&!empty($Par_Sql['Rol_Cod'])&&$Par_Sql['Rol_Cod']!='ALL'?" AND ropa.Rol_Cod=$Par_Sql[Rol_Cod] ":'') . (isset($Par_Sql['Roles_Where'])&&!empty($Par_Sql['Roles_Where']) &&($Par_Sql['Rol_Cod']=='ALL') &&$Par_Sql['Roles_Where']!='()'?" AND ropa.Rol_Cod in $Par_Sql[Roles_Where] ":'') . $Pec
					. $groupBy . $orderBy;
			}
			return $sql;
        break;
     }

     return $sql;
 }