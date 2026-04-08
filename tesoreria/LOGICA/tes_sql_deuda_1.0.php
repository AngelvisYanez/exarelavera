<?php
    
    /**
    * Author : Asael Tello Barcia
    */
    function sentencias_deu($id,$Par_Sql)
    {
        $sql = "";
        switch($id)
        {
            
            case 1: // INSERT VENTA
                $sql = "INSERT INTO ventas (Aut_Cod, Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Tpc_Cod, Vet_Num, Vet_Est, Vnd_Cod, Vet_Obs) "
                    . "VALUES ($Par_Sql[Aut_Cod], 1, $Par_Sql[Cli_Cod], $Par_Sql[Ciu_Cod] , $Par_Sql[Caj_Cod],'1', '0', 'E', $Par_Sql[Vnd_Cod], '$Par_Sql[Vet_Obs]')";                
                break;
            
            case 2:// SEARCH CLIENTES
                if($Par_Sql[2]=="d")                    
                    {
                        $search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";                        
                    }
                else
                    {
                        $search="Prs_Ced LIKE '$Par_Sql[0]%'";                        
                    }
                if($Par_Sql[3]=="")
                    {
                        $campos="COUNT(Cli_Cod) as total";                        
                    }
                else                    
                    {
                        $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                        $campos=" Cli_Cod, persona.Prs_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, Cli_Dir, Prs_Dir, Prs_Cor, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
                    }
                $sql="SELECT $campos FROM cliente, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";           
                break;
                
            case 3: // INSERT COMPROBANTES
                $sql = "INSERT INTO comprobantes (Pec_Cod, Cli_Cod, Usu_Cod, Com_Num, Com_Fec, Com_Con, Com_Val, Com_Est, Tia_Cod) "
                    . " VALUES ($Par_Sql[Pec_Cod], $Par_Sql[Cli_Cod], $Par_Sql[Usu_Cod], '0', '$Par_Sql[Com_Fec]', 'Deuda Inicial', $Par_Sql[Com_Val], 'E', $Par_Sql[Tia_Cod])";
                break;
            
            case 4: // SAVE ASIENTOS
                $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Pld_Cod) "
                    . "VALUES($Par_Sql[Com_Cod],'$Par_Sql[Asi_Deh]', $Par_Sql[Asi_Val], $Par_Sql[Pld_Cod]) ";
                return $sql;
                break;
            
            case 5: // SAVE DETALLE VENTAS-COMPROBANTE
                $sql = "INSERT INTO ventas_compr (Vet_Cod, Com_Cod) VALUES ($Par_Sql[Vet_Cod], $Par_Sql[Com_Cod])";
                break;
            
            case 6: // GET AUTORIZACION CODIGO
                $sql= "SELECT a.Aut_Cod, a.Pun_Cod, v.Vnd_Cod FROM autorizaci a, puntos_imp p, vendedor v WHERE a.Pun_Cod = p.Pun_Cod AND p.Pun_Cod = v.Pun_Cod AND a.Tic_Cod = 1 AND p.Suc_Cod = $Par_Sql[0] LIMIT 1";
                break;
            
            case 7: // VALIDATE CAJA APERTURA
                $sql = "SELECT COUNT(*) as contador,caja_aper.Caj_Cod FROM caja_aper WHERE Caj_Fec = '$Par_Sql[fecha]' AND Pun_Cod = $Par_Sql[Pun_Cod]";
                break;
            
            case 8: //INSERT CAJA APERTURA
                $sql = "INSERT INTO caja_aper (Pun_Cod, Caj_Fec, Caj_Est) "
                    . " VALUES ($Par_Sql[Pun_Cod], '$Par_Sql[Caj_Fec]', 'C')";
                break;
            
            case 9: // GET PLD COD
                $sql = "SELECT 
                            ccpp_cliente.Pld_Cod,
                            CONCAT(Pld_Cdc,' - ',det_plan.Pld_Des) as cuenta
                          FROM
                            det_plan
                            INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod)
                            INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                          WHERE
                            Emp_Cod=$Par_Sql[0]";
				//echo $sql;
                break;
            
            case 10: // INSERTO CCPP_COBRAR
                $sql= " INSERT INTO ccpp_cobrar (Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[Com_Cod],$Par_Sql[Vet_Cod],$Par_Sql[Cpc_Ven],'Deuda Inicial')";
                break;
            
            case 11: // GET ALL
                $sql = "SELECT DISTINCT
                            `persona`.`Prs_Cod`,
                            Concat(`persona`.`Prs_Ape`, ' ', `persona`.`Prs_Nom`) AS `cliente`,
                            ROUND(`comprobantes`.`Com_Val`, 2) as Com_Val,
                            `comprobantes`.`Com_Fec`,
                            `comprobantes`.`Com_Cod`,
                            `comprobantes`.`Usu_Cod`,
                            `persona`.`Prs_Ced`,
                            `ventas_compr`.`Vet_Cod`,
                            `ventas`.`Vet_Obs`,
                            `ventas`.`Caj_Cod`,
                            asientos.Pld_Cod,
                            cliente.Cli_Cod,
                            comprobantes.Pec_Cod,
                            comprobantes.Tia_Cod,
                            (select count(*) contador from det_ccpp_c d, ccpp_cobrar c, comprobantes cp WHERE c.Cpc_Cod = d.Cpc_Cod AND d.Com_Cod = cp.Com_Cod AND c.Com_Cod = comprobantes.Com_Cod  AND cp.Com_Est = 'A') as cont
                          FROM
                            `comprobantes`
                            INNER JOIN `cliente` ON `cliente`.`Cli_Cod` = `comprobantes`.`Cli_Cod`
                            INNER JOIN `persona` ON `persona`.`Prs_Cod` = `cliente`.`Prs_Cod`
                            INNER JOIN `asientos` ON `comprobantes`.`Com_Cod` = `asientos`.`Com_Cod` AND
                              `comprobantes`.`Com_Est` = 'E'
                            INNER JOIN `ventas_compr` ON `comprobantes`.`Com_Cod` =
                              `ventas_compr`.`Com_Cod`
                            INNER JOIN `ventas` ON `ventas`.`Vet_Cod` = `ventas_compr`.`Vet_Cod`
						  WHERE Emp_Cod=$Par_Sql[0]";
                break;
            
            case 12: // for validate on delete deuda inicial
                $sql=" SELECT COUNT(*) as contador FROM det_ccpp_c WHERE Com_Cod = $Par_Sql[0]";
                break;
            
            case 13: //delete detalle ventas_compr
                $sql = "DELETE FROM ventas_compr WHERE Vet_Cod = $Par_Sql[Vet_Cod] AND Com_Cod = $Par_Sql[Com_Cod]";
                break;
            
            case 14: //delete comprobantes
                $sql = "DELETE FROM comprobantes WHERE Com_Cod = $Par_Sql[0]";
                break;
            
            case 15: // delete ventas
                $sql = "DELETE FROM ventas WHERE Vet_Cod = $Par_Sql[0]";
                break;
            
            case 16: // delete caja_aper
                $sql = "DELETE FROM caja_aper WHERE Caj_Cod = $Par_Sql[0]";
                break;
            
            case 17: // update comprobante
                $sql = "UPDATE comprobantes SET Cli_Cod = $Par_Sql[Cli_Cod] , Com_Fec = '$Par_Sql[Com_Fec]' , Com_Val = $Par_Sql[Com_Val], Tia_Cod = $Par_Sql[Tia_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod]";
                break;
            
            case 18: // update venta
                $sql = "UPDATE ventas SET Cli_Cod = $Par_Sql[Cli_Cod], Vet_Obs = '$Par_Sql[Vet_Obs]' WHERE Vet_Cod = $Par_Sql[Vet_Cod]";
                break;
            
            case 19: //update asiento
                $sql = "UPDATE asientos SET Asi_Val = $Par_Sql[Asi_Val], Pld_Cod = $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod]";
                break;
            
            case 20: //update caja_apertura
                $sql = "UPDATE caja_aper SET Caj_Fec = '$Par_Sql[Caj_Fec]' WHERE Caj_Cod = $Par_Sql[Caj_Cod]";
                break;
            
            case 21: // Get Tipo de Ingreso
                $sql = "SELECT * FROM tipo_asien where Tia_Ini = 'I' and Tia_Est = 'A'";
                break;
				
			case 22: // Get Tipo de Ingreso
                $sql = "SELECT perio_cont.*,YEAR(Pec_Fei)AS Periodo FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod='$_SESSION[Ses_Emp_Cod]' ORDER BY Periodo DESC ";
                break;	
        }   
        return $sql;
    }
?>