<?php
    
    /**
    * Author : Asael Tello Barcia
    */
    function sentencias_cch($id,$Par_Sql)
    {
        $sql = "";
        switch($id)
        {
            
            case 1: // INSERT Caja
                $sql = "INSERT INTO caja_chica (Usu_Cod, Emp_Cod, Com_Cod, Cch_Fec, Cch_Val, Cch_Obs, Cch_Est) "
                    . "VALUES ($Par_Sql[Usu_Cod], $Par_Sql[Emp_Cod], $Par_Sql[Com_Cod], '$Par_Sql[Cch_Fec]', $Par_Sql[Cch_Val], '$Par_Sql[Cch_Obs]', 'A')";
                return $sql;
                break;
            
            case 2: //UPDATE Caja
                $sql = "UPDATE caja_chica SET Com_Cod = '$Par_Sql[Com_Cod]', Cch_Fec = $Par_Sql[Cch_Fec], Cch_Val = $Par_Sql[Cch_Val], Cch_Obs = '$Par_Sql[Cch_Obs]' WHERE Cch_Cod = $Par_Sql[Cch_Cod]  ";
                return $sql;
                break;
            
            case 3: //UPDATE Estado -> Caja Chica
                $sql = "UPDATE caja_chica SET Cch_Est= '$Par_Sql[Cch_Est]' WHERE Cch_Cod = $Par_Sql[Cch_Cod]";
                return $sql;
                break;
            
            case 4: // Get Caja Chica by Sucursal
                $sql = "SELECT c.Cch_Cod, c.Cch_Fec, IF (c.Cch_Est = 'A','Activo','Inactivo') as Cch_Est, c.Cch_Val, CONCAT(p.Prs_Nom,' ',p.Prs_Ape) as persona, c.Cch_Obs, c.Com_Cod "
                    . "FROM caja_chica c, usuarios u, persona p WHERE c.Usu_Cod = u.Usu_Cod AND u.Prs_Cod = p.Prs_Cod AND c.Emp_Cod = $Par_Sql[0] ORDER BY c.Cch_Cod DESC";
                //echo $sql;
				return $sql;
                break;
            
            case 5: // Update Estado de Caja a Inactivo by Empresa
                $sql = "UPDATE caja_chica SET Cch_Est= 'I' WHERE Cch_Cod != $Par_Sql[Cch_Cod] AND Emp_Cod = $Par_Sql[Emp_Cod] ";
                return $sql;
                break;
            
            case 6: //Get Pld_Cod para cheques 
                $sql = "SELECT det_plan.Pld_Cod, det_plan.Pla_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des "
                    . "FROM plan_cuenta INNER JOIN det_plan ON (plan_cuenta.Pla_Cod = det_plan.Pla_Cod) "
                    . "INNER JOIN plan_param ON (det_plan.Pld_Cod = plan_param.Pld_Cod) "
                    . "INNER JOIN tipo_param ON (plan_param.Tpa_Cod = tipo_param.Tpa_Cod) "
                    . "WHERE Tpa_Abr = 'CC' AND Emp_Cod = $Par_Sql[0]";
                return $sql;
                break;
            
            case 7:// Consultamos tipos de Asientos                
                $sql="SELECT Tia_Cod,Tia_Des,Tia_Ini FROM tipo_asien WHERE Tia_Ini='$Par_Sql[0]' AND Tia_Est='A'";
                return $sql;
                break;            
            
            case 8: // Consulta los bancos
                $sql="SELECT banco.Ban_Cod,det_plan.Pld_Cod,det_plan.Pld_Des
                        FROM det_plan
                                  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
                                  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                        WHERE Ban_Tip = 'B' AND Ban_Est = 'A' AND Pld_Est = 'A' AND Emp_Cod = '$Par_Sql[0]'";
                return $sql;
                break;
            
            case 9: // Guarda comprobantes
                $sql = "INSERT INTO comprobantes (Pec_Cod, Prv_Cod, Usu_Cod, Com_Num, Com_Fec, Com_Con, Com_Val, Com_Obs, Tia_Cod, Com_Gen) "
                    . "VALUES ($Par_Sql[Pec_Cod], $Par_Sql[Prv_Cod], $Par_Sql[Usu_Cod], $Par_Sql[Com_Num], '$Par_Sql[Cch_Fec]', 'Apertura de Caja Chica', $Par_Sql[Cch_Val], '$Par_Sql[Cch_Obs]', $Par_Sql[Tia_Cod], 'A') ";
                return $sql;
                break;
            
            case 10: // Selecciona codigo de proveedor y codigo de empresa del proveedor
                $sql = "SELECT compra_prov.Prv_Cod, proveedore.Emp_Cod "
                    . "FROM proveedore "
                    . "INNER JOIN compra_prov ON (proveedore.Prv_Cod = compra_prov.Prv_Cod) "
                    . "WHERE Emp_Cod = $Par_Sql[0]";
                return $sql;
                break;
            
            case 11: // Guarda Asientos
                $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Pld_Cod) "
                    . "VALUES($Par_Sql[Com_Cod],'$Par_Sql[Asi_Deh]', $Par_Sql[Cch_Val], $Par_Sql[Pld_Cod]) ";
                return $sql;
                break;
            
            case 12: // Guarda Cheques
                $sql = "INSERT INTO cheques (Che_Cod,Prv_Cod, Ban_Cod, Asi_Cod, Che_Num, Che_Fec, Che_Val, Che_Obs, Che_Ben) "
                    . "VALUES(1,$Par_Sql[Prv_Cod],$Par_Sql[Ban_Cod], $Par_Sql[Asi_Cod], $Par_Sql[Che_Num], '$Par_Sql[Cch_Fec]', $Par_Sql[Cch_Val], '$Par_Sql[Cch_Obs]', '$Par_Sql[Che_Ben]') ";
                return $sql;
                break;            
            
            case 13: //Consultamos el ultimo cheque emitido segun el banco
                $sql="SELECT MAX(Che_Num)+1 as Che_Num FROM cheques WHERE Ban_Cod = $Par_Sql[0];";                
                return $sql;
            break;
        
            case 14: // Get pld_Cod para efectivo
                $sql = "SELECT 
                        det_plan.Pld_Cod,
                        det_plan.Pld_Cdc,
                        det_plan.Pld_Des
                      FROM
                        det_plan
                        INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
                        INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                      WHERE
                        Emp_Cod = $Par_Sql[0] AND Ban_Tip='C'";
                return $sql;
                break;
            
            case 15: // Validar que no se repita el el # de cheque
                $sql = "SELECT COUNT(Che_Num) as contador FROM cheques WHERE Ban_Cod = $Par_Sql[Ban_Cod] AND Che_Num = $Par_Sql[Che_Num]";
                return $sql;
                break;
            
            case 16: // select cheque by Cod_Com 
                $sql = "SELECT ch.*, cj.*, co.Prv_Cod, co.Com_Num, co.Tia_Cod, asi.Pld_Cod, asi.Asi_Deh, CONCAT(ch.Ban_Cod,'*',asi.Pld_Cod) as banco
                        FROM caja_chica cj INNER JOIN comprobantes co ON cj.Com_Cod = co.Com_Cod 
                        INNER JOIN asientos asi ON co.Com_Cod = asi.Com_Cod left outer join cheques ch on asi.Asi_Cod = ch.Asi_Cod
                        WHERE cj.Com_Cod = $Par_Sql[0] AND Emp_Cod=$_SESSION[Ses_Emp_Cod]";
                return $sql;
                break;

            case 17: // Update Caja Chica
                $sql = "UPDATE caja_chica SET Cch_Fec = '$Par_Sql[Cch_Fec]', Cch_Val = $Par_Sql[Cch_Val] , Cch_Obs = '$Par_Sql[Cch_Obs]' WHERE Cch_Cod = $Par_Sql[Cch_Cod]";
                return $sql;
                break;

            case 18: // Update Comprobante
                $sql = "UPDATE comprobantes SET Com_Fec = '$Par_Sql[Cch_Fec]', Com_Val = $Par_Sql[Cch_Val] , Com_Obs = '$Par_Sql[Cch_Obs]', Tia_Cod = $Par_Sql[Tia_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod]";
                return $sql;
                break;
            
            case 19: // Update Asiento
                $sql = "UPDATE asientos SET Asi_Val = $Par_Sql[Cch_Val] , Pld_Cod = $Par_Sql[Pld_Cod] WHERE Asi_Cod = $Par_Sql[Asi_Cod]";
                return $sql;
                break;
            
            case 20: // Update Cheques by Asi_Cod
                $sql = "UPDATE cheques SET Ban_Cod = $Par_Sql[Ban_Cod] , Che_Num = $Par_Sql[Che_Num] , Che_Fec = '$Par_Sql[Cch_Fec]', Che_Val = $Par_Sql[Cch_Val] , Che_Obs = '$Par_Sql[Cch_Obs]', Che_Ben = '$Par_Sql[Che_Ben]' WHERE Asi_Cod = $Par_Sql[Asi_Cod]";
                return $sql;
                break;
            
            case 21: // Delete Asientos and delete from cheque in cascade
                $sql = "DELETE FROM asientos WHERE Com_Cod = $Par_Sql[0]";
                return $sql;
                break;
            
            case 22: // get Asiento id by Com_Cod and filtro
                $sql = "SELECT Asi_Cod FROM asientos WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = '$Par_Sql[filtro]'";
                return $sql;
                break;
            
        }      
    }
?>