<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2016-06-28
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package inv.LOGICA
 */

function sentencias_che($id, $Par_Sql) {
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            //echo $sql.'<br/>';
            break;
        case 1: /* Cargado individual de cheque en el reporte */
            $sql = "SELECT comprobantes.Com_Cod, persona.Prs_Cod, Pld_Des, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Ape,Che_Ben) AS Prs_Ape, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Nom,'') AS Prs_Nom, Che_Ben, Che_Num, ROUND(Che_Val,2) as Che_Val, Che_Cob, Che_Fec FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona WHERE comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND cheques.Che_Cod = $Par_Sql[0] AND cheques.Asi_Cod = $Par_Sql[1] AND cheques.Ban_Cod = $Par_Sql[2] AND cheques.Prv_Cod = $Par_Sql[3]";
            //echo $sql.'<br/>';
            break;
        case 2: /* Consulta la informacion la ciudad en base a la sucursal  */
            $sql = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
            //echo $sql.'<br/>';
            break;
                case 3:
            $numero_cheque = "";
            if (isset($Par_Sql[5])) {
                $numero_cheque = " AND (CAST(cheques.Che_Num as CHAR) = '$Par_Sql[5]')";
            }
            if (($Par_Sql[2] * 1) == 2)
                $cheq = "AND Che_Fec>'$Par_Sql[3]'";
            else {
                if (empty($Par_Sql[3]) || empty($Par_Sql[4]))
                    $cheq = '';
                else
                    $cheq = "AND Che_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";
            }
            if (($Par_Sql[2] * 1) == 3)
                $cheq = $cheq . " AND cheques.Che_Est='C'";
            if (($Par_Sql[2] * 1) == 4)
                $cheq = $cheq . " AND cheques.Che_Est='A'";
            if (($Par_Sql[2] * 1) == 5)
                $cheq = $cheq . " AND cheques.Che_Est='I'";
            if (($Par_Sql[2] * 1) == 6)
                $cheq = $cheq . " AND cheques.Che_Est='P'";
            $sql = "SELECT CONCAT(CAST(cheques.Ban_Cod AS CHAR),'_',CAST(Che_Num as CHAR),'_',CAST(Che_Cod as CHAR)) as Che_Cod , cheques.Che_Cod as  Cheque_Cod,comprobantes.Com_Cod,
            cheques.Asi_Cod,cheques.Prv_Cod,Com_Fec,Pld_Des,Ban_Cue,Che_Num,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,banco.Ban_Cod,
            IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',CONCAT(Prs_Ape,' ',Prs_Nom),Che_Ben) AS Beneficiario,
            comprobantes.Com_Cod as codigo_Comp,
            (Select count(Cpp_Cod) as  editVal from det_ccpp_p where det_ccpp_p.Com_Cod= codigo_Comp )as EdicionVal,
            IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,
            CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),
            CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Num,Che_Cob
            FROM cheques
            INNER JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
            INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
            INNER JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
            INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
            INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod
            INNER JOIN comprobantes ON comprobantes.Com_Cod=asientos.Com_Cod
            INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
            WHERE Emp_Cod=$Par_Sql[0] AND cheques.Ban_Cod=$Par_Sql[1] $cheq
            $numero_cheque
            ORDER BY Che_Fec";
            break;
        //echo $sql."<br>";
        case 4:
            $sql = "SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
                INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='' GROUP BY Ban_Cod;";
            //echo $sql;
            break;
        case 5:
            $id = explode("-", $Par_Sql[2]);
            $numero_cheque = "";
            if (isset($Par_Sql[5])) {
                $numero_cheque = " AND (CAST(cheques_otros.Che_Num as CHAR) = '$Par_Sql[5]')";
            }
            if (($Par_Sql[2] * 1) == 2)
                $cheq = "AND Che_Fec>'$Par_Sql[3]'";
            else {
                if (empty($Par_Sql[3]) || empty($Par_Sql[4]))
                    $cheq = '';
                else
                    $cheq = "AND Che_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";
            }
            if (($Par_Sql[2] * 1) == 3)
                $cheq = $cheq . " AND cheques_otros.Che_Est='C'";
            if (($Par_Sql[2] * 1) == 4)
                $cheq = $cheq . " AND cheques_otros.Che_Est='A'";
            if (($Par_Sql[2] * 1) == 5)
                $cheq = $cheq . " AND cheques_otros.Che_Est='I'";
            if (($Par_Sql[2] * 1) == 6)
                $cheq = $cheq . " AND cheques_otros.Che_Est='P'";
            $sql = "SELECT CONCAT('CHE-',CAST(Che_Cod as CHAR)) as Che_Cod ,cheques_otros.Che_Cod as Cheque_Cod,Pld_Des,Ban_Cue,banco.Ban_Cod,
            Che_Num,0 as EdicionVal,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,
						IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',CONCAT(Prs_Ape,' ',Prs_Nom),Che_Ben) AS Beneficiario,
            IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,
            Che_Cob,'EXT' AS t_type  FROM cheques_otros
            INNER JOIN banco ON banco.Ban_Cod=cheques_otros.Ban_Cod
            INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
            INNER JOIN proveedore ON proveedore.Prv_Cod=cheques_otros.Prv_Cod
            INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
            WHERE Emp_Cod=$Par_Sql[0] AND cheques_otros.Ban_Cod=$Par_Sql[1] $cheq
            $numero_cheque
            ORDER BY Che_Fec";
            break;      //echo $sql;

        case 6:
            $sql = "SELECT DISTINCT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef FROM perio_cont
            INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
            WHERE plan_cuenta.Emp_Cod=$Par_Sql[0] ORDER BY Periodo DESC;";
            //echo $sql;
            break;
        case 7:
            $sql = "UPDATE cheques SET Che_Num = NULL, Che_Obs='$Par_Sql[Che_Obs]-Cheque $Par_Sql[Che_Num] Liberado' WHERE Prv_Cod=$Par_Sql[Prv_Cod] AND Ban_Cod=$Par_Sql[Ban_Cod] AND Asi_Cod=$Par_Sql[Asi_Cod] AND Che_Cod = $Par_Sql[Cheque_Cod]";
            //echo $sql;
            break;
        case 8://modificar Cheque
            $sql = "UPDATE cheques SET Prv_Cod=$Par_Sql[Prv_Cod] , Ban_Cod=$Par_Sql[Ban_Cod] , Asi_Cod=$Par_Sql[Asi_Cod] , Che_Num=$Par_Sql[Che_Num],Che_Ben='$Par_Sql[Che_Ben]', Che_Fec='$Par_Sql[Che_Fec]'  , Che_Val=$Par_Sql[Che_Val], Che_Obs='$Par_Sql[Che_Obs]'  WHERE Prv_Cod=$Par_Sql[Prv_Cod_Ant] AND Ban_Cod=$Par_Sql[Ban_Cod_Ant] AND Asi_Cod=$Par_Sql[Asi_Cod] AND Che_Cod = $Par_Sql[Cheque_Cod]";
            //echo $mod_cheque;
            break;
        case 9://verificar existencia de N cheque
            $sql = "SELECT Che_Num from cheques where Ban_Cod=$Par_Sql[Ban_Cod] AND Che_Num=$Par_Sql[Che_Num]";
            //echo $sql;
            break;
        case 10://obtener Bancos para edicion Cheques
            $sql = "SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
      			INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
      			INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
      			INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
      			WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='' AND Ban_Tip='B' GROUP BY Ban_Cod;";
            //echo $sql;
            break;
        case 11://actualizar asiento en edicion de cheque
            $sql = "UPDATE asientos set Pld_Cod=$Par_Sql[Pld_Cod] WHERE Com_Cod=$Par_Sql[Com_Cod]";
            break;
        case 12://verificacion de Asiento contable en CCXPP
            $sql = "SELECT cheques.Che_Cod,cheques.Prv_Cod,cheques.Ban_Cod,cheques.Asi_Cod
      			FROM cheques
      			INNER JOIN asientos ON asientos.Asi_Cod = cheques.Asi_Cod
      			INNER JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
      			INNER JOIN det_ccpp_p ON comprobantes.Com_Cod = det_ccpp_p.Com_Cod
      			WHERE cheques.Asi_Cod =$Par_Sql[Asi_Cod]";
            //echo $sql;
            break;
        case 13://Actualiza valor de asiento con el de cheque
            $sql = "UPDATE asientos set Asi_Val=$Par_Sql[Che_Val] WHERE Com_Cod=$Par_Sql[Com_Cod]";
            //echo $sql;
            break;
        case 14://Actualizar valor de comprobantes con de Cheque
            $sql = "UPDATE comprobantes set Com_Val=$Par_Sql[Che_Val] WHERE Com_Cod=$Par_Sql[Com_Cod]";
            //echo $sql;
            break;
        case 15:  //Actualizar datos de cheques_otros
            $sql = "UPDATE cheques_otros SET  Ban_Cod=$Par_Sql[Ban_Cod] ,
        Che_Num=$Par_Sql[Che_Num],Che_Ben='$Par_Sql[Che_Ben]',
        Che_Fec='$Par_Sql[Che_Fec]'  , Che_Val=$Par_Sql[Che_Val], Che_Obs='$Par_Sql[Che_Obs]'
        WHERE Che_Cod = $Par_Sql[Cheque_Cod]";
            //echo $sql;"
            break;
        case 16:
            $sql = "SELECT Che_Num from cheques_otros where Ban_Cod=$Par_Sql[Ban_Cod] AND Che_Num=$Par_Sql[Che_Num]";
            break;
        case 17:
            $sql = "select bancos.* from bancos where Bak_Est='A'";
            break;
        case 18:
            $search = "";
            switch ($Par_Sql['op_opciones']) {
                case 'p':
                    $search = "and (Prs_Ape Like '%$Par_Sql[search]%' OR Prs_Nom Like '%$Par_Sql[search]%')";
                    break;
                case 'c':
                    $search = "and Prs_Ced Like '%$Par_Sql[search]%'";
                    break;
                case 'd':
                    if ($Par_Sql['search'] !== "") {
                        $search = "and Che_Num = $Par_Sql[search]";
                    }
                    break;
            }

            $busqueda = "";
            if (isset($Par_Sql['TipBus'])) {
                foreach ((count($Par_Sql['TipBus']) > 1 ? $Par_Sql['TipBus'] : str_split($Par_Sql['TipBus'], 1)) as $clave => $valor) {
                    if ($valor === "1") {
                        $busqueda = " and (Che_Apl is not NULL or Che_Apl <> '')" . $busqueda;
                    }
                    if ($valor === "2") {
                        $busqueda = " and Che_Est='C' " . $busqueda;
                    }
                    if ($valor === "3") {
                        $busqueda = " and Che_Est='A' " . $busqueda;
                    }
                    if ($valor === "4") {
                        $busqueda = " and Che_Est='P' " . $busqueda;
                    }
                    if ($valor === "5") {
                        $busqueda = " and Che_Est='D' " . $busqueda;
                    }
                }
            }
            $search_fechas = "and Che_Fec between '$Par_Sql[txt_fec_ini]' and '$Par_Sql[txt_fec_fin]'";
            $banco_search = "";
            if ($Par_Sql['Bak_Cod'] !== "0") {
                $banco_search = "and bancos.Bak_Cod = $Par_Sql[Bak_Cod] ";
            }



            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(cheques_ext.Che_Cod) AS total";
            } else {
                $campos = "bancos.Bak_Des,cheques_ext.*, 
                            if(cheques_ext.Che_Cli is NULL,concat(Prs_Ape ,' ',Prs_Nom),
                            cheques_ext.Che_Cli)as Cli_Nom, concat(Prs_Ape ,' ',Prs_Nom) as Cli_Ven";
            }
            $sql = "select  distinct $campos from cheques_ext 
                    inner join bancos on bancos.Bak_Cod = cheques_ext.Bak_Cod
                    left join cliente on cheques_ext.Cli_Cod = cliente.Cli_Cod
                    left join persona on persona.Prs_Cod = cliente.Prs_Cod
                    /*left join pago_venta on pago_venta.Vet_Che = cheques_ext.Che_Num and pago_venta.Bak_Cod =cheques_ext.Bak_Cod and pago_venta.Vet_Cue = cheques_ext.Che_Cta*/
                    left join cheq_det_ccpp on cheq_det_ccpp.Che_Cod = cheques_ext.Che_Cod
                    left join det_ccpp_c on det_ccpp_c.Dcc_Cod = cheq_det_ccpp.Dcc_Cod
                    left join ccpp_cobrar on ccpp_cobrar.Cpc_Cod = det_ccpp_c.Cpc_Cod
                    where cliente.Emp_Cod=$Par_Sql[Emp_Cod] and cheques_ext.Che_Est <> 'I' $search $busqueda $search_fechas $banco_search";
            break;
        case 19: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla banco 'CONTADO'
            $sql = "SELECT distinct banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des,Ban_Tip,if(banco.Ban_Tip='B','si','no') AS banco, if(banco.Ban_Tip='C',1,0) as Pag_Cod  FROM banco, det_plan, pago_plan, plan_cuenta,tipos_pago
                    WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    AND plan_cuenta.Pla_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND (banco.Ban_Tip='B' OR banco.Ban_Tip='C' ) AND pago_plan.Pag_Est='A' ORDER BY Pld_Cdc, Pld_Des";
            break; 
        case 20: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla tipo_param ' correspondiente a EFECTIVO Y CHEQUE'
            $sql = "SELECT tipo_param.Tpa_Cod,det_plan.Pld_Cod,Pld_Des,Tpa_Abr,IF(tipo_param.Tpa_Cod=16,1,3) AS Pag_Cod,'no' AS banco FROM tipo_param
                    INNER JOIN plan_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    INNER JOIN det_plan ON plan_param.Pld_Cod=det_plan.Pld_Cod
                    WHERE (Tpa_Abr='CBA') and det_plan.Pla_Cod=$Par_Sql[0] and plan_param.Ppc_Est='A'" ;
            break;
        case 21:
            $sql="select max(plan_cuenta.Pla_Cod)as Pla_Cod, perio_cont.Pec_Cod from plan_cuenta 
                inner join perio_cont on perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
                where plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod]";
            break;
        case 22:
            $sql="select * from tipo_asien where tipo_asien.Tia_Est='A' and tipo_asien.Tia_Ini='$Par_Sql[Tia_Ini]'";
            break;
        case 23://comprobante 
            $sql="insert into comprobantes (Pec_Cod,$Par_Sql[prv_or_cli],Usu_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Tia_Cod,Com_Gen,Com_Doc)
                  values ($Par_Sql[Pec_Cod],$Par_Sql[Cli_Cod],$Par_Sql[Usu_Cod],$Par_Sql[Com_Num],'$Par_Sql[Mov_Fec]','$Par_Sql[Com_Con]','$Par_Sql[Tia_Ini]',$Par_Sql[Com_Val],'$Par_Sql[Mov_Obs]',$Par_Sql[Tia_Cod],'A',".(!empty($Par_Sql['Com_Doc'])?"'".$Par_Sql['Com_Doc']."'":"NULL").")";
		//ChromePhp::log($sql);
            break;
        case 231:
            $sql="SELECT cheques_ext.Cli_Cod FROM cheques_ext 
            INNER JOIN cliente ON cheques_ext.Cli_Cod= cliente.Cli_Cod 
            WHERE Emp_Cod=$Par_Sql[Emp_Cod] AND che_cod= 3276";
            //ChromePhp::log($sql);
            break;
        case 24:
            if(!isset($Par_Sql['Det_Tip']))
                $Par_Sql['Det_Tip']=$Par_Sql['Asi_Deh'];
            if(!isset($Par_Sql['Glosa']))
                $Par_Sql['Glosa']=$Par_Sql['Asi_Glo'];
            
            $sql="insert into asientos (Com_Cod,Asi_Deh,Asi_Val,Asi_Con,Pld_Cod,Asi_Glo) values($Par_Sql[Com_Cod],'$Par_Sql[Det_Tip]',$Par_Sql[Asi_Val],'$Par_Sql[Asi_Con]',$Par_Sql[Pld_Cod],'$Par_Sql[Glosa]')";
// echo $sql."<br/>";
            break;
        case 25:
            $sql="SELECT plan_param.Pld_Cod,Pld_Cdc,Pld_Des,Pla_Cod,Tpa_Des FROM plan_param
                    INNER JOIN det_plan ON det_plan.Pld_Cod=plan_param.Pld_Cod
                    INNER JOIN tipo_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    WHERE Tpa_Abr='$Par_Sql[Tpa_Abr]' AND Pla_Cod=$Par_Sql[Pla_Cod];";
            break;  
        case 26:
            $sql="insert into movimiento_cheques (Che_Cod,Com_Cod,Mov_Fec,Mov_Usu,Mov_Obs,Mov_Tip,Mov_Doc) values ($Par_Sql[Che_Cod],$Par_Sql[Com_Cod],'$Par_Sql[Mov_Fec]',$Par_Sql[Mov_Usu],'$Par_Sql[Mov_Obs]','$Par_Sql[Mov_Tip]','$Par_Sql[Mov_Doc]')";
            break;
        case 27:
            $che_cob='';
            if (isset($Par_Sql['Che_Cob'])) {
                $che_cob = ", Che_Cob='$Par_Sql[Che_Cob]'";
            }
            $sql="UPDATE cheques_ext SET Che_Est ='$Par_Sql[Che_Est]' $che_cob where Che_Cod=$Par_Sql[Che_Cod]";
            break;
        case 28:
            $fecha_apl="'$Par_Sql[Che_Apl]'";
            if(empty($Par_Sql['Che_Apl'])){
                $fecha_apl='NULL';
            }
            $sql="UPDATE cheques_ext SET Che_Apl = $fecha_apl where Che_Cod=$Par_Sql[Che_Cod]";
            break;
        case 29: // cuenta de clientes_varios para abregar multa y valor del cheque
            $sql = "SELECT ccpp_cliente.Pld_Cod, det_plan.Pld_Des, ccpp_cliente.Cpc_Def, ccpp_cliente.Cpc_Cxc, Cpc_Def AS extra FROM det_plan INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
            //echo $sql."<br>";
            break;
        case 30://cuenta banco para agregar multa y valor del cheque
            $sql="select asientos.Pld_Cod from asientos 
                inner join comprobantes on comprobantes.Com_Cod= asientos.Com_Cod
                inner join movimiento_cheques on comprobantes.Com_Cod = movimiento_cheques.Com_Cod and Mov_Est='A' and Mov_Tip='D'
                inner join cheques_ext on movimiento_cheques.Che_Cod = cheques_ext.Che_Cod and Che_Est='D'
                inner join det_plan on asientos.Pld_Cod = det_plan.Pld_Cod
                where cheques_ext.Che_Cod=$Par_Sql[Che_Cod] and asientos.Asi_Deh='D'";
            break;
        case 31:
            $sql="select det_ccpp_c.* from cheq_det_ccpp
                inner join det_ccpp_c on det_ccpp_c.Dcc_Cod= cheq_det_ccpp.Dcc_Cod
                where cheq_det_ccpp.Che_Cod=$Par_Sql[Che_Cod]";
            break;
        case 32:
            $sql="insert into det_ccpp_c (Cpc_Cod,Com_Cod,Pag_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs) "
                . "values ($Par_Sql[Cpc_Cod],$Par_Sql[Com_Cod],'$Par_Sql[Pag_Cod]','$Par_Sql[Cpc_Fec]','$Par_Sql[Cpc_Val]','$Par_Sql[Cpc_Obs]')";
            break;
        case 33:
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(movimiento_cheques.Che_Cod) AS total";
            } else {
                $campos = " movimiento_cheques.*,comprobantes.Com_Sys,Com_Est, CONCAT(Prs_Ape,' ',Prs_Nom) as Usuario, CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),
            CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Num  ";
            }
            $sql = "select $campos from movimiento_cheques  
                   inner join comprobantes on comprobantes.Com_Cod = movimiento_cheques.Com_Cod
                   inner join usuarios on movimiento_cheques.Mov_Usu = usuarios.Usu_Cod
                   inner join persona on usuarios.Prs_Cod = persona.Prs_Cod
                   inner join tipo_asien on tipo_asien.Tia_Cod = comprobantes.Tia_Cod
                   where Che_Cod=$Par_Sql[Che_Cod]";
            break;
        case 34:
            $sql="SELECT reportes.Rep_Cod, procesos.Pcs_Nom, reportes.Rep_Ord, rutas.Rut_Des FROM procesos
                    INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
                    INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) 
                    WHERE reportes.Pcs_Cod = 137 AND reportes.Emp_Cod =$Par_Sql[Emp_Cod] ORDER BY reportes.Rep_Ord";
            break;
        case 35:
            $sql="select max(plan_cuenta.Pla_Cod)as Pla_Cod, perio_cont.Pec_Cod from plan_cuenta 
                inner join perio_cont on perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
                where YEAR(perio_cont.Pec_Fei)=$Par_Sql[fecha] AND plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod]";
            break;    
                
        /* ocupados por sqls viejas */
        case 190:
            $ins_cheques_190="INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]',".//" Che_Cob='$Par_Sql[4]',"
                    " Che_Val=$Par_Sql[5], Che_Obs=UPPER('$Par_Sql[6]'), Che_Fec='$Par_Sql[7]', Che_Cod = $Par_Sql[8], Che_Ben=UPPER('$Par_Sql[9]') ;";
            //echo "<br/>".$ins_cheques_190;
            return $ins_cheques_190;
        case 214:
            $cargar_per_214 = "SELECT perio_cont.Pec_Cod,perio_cont.Pec_Fei,perio_cont.Pec_Fef,perio_cont.Pec_Est,Year(Pec_Fei) AS Periodo,perio_cont.Pla_Cod
                FROM plan_cuenta
                  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                WHERE Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0] ORDER BY Pec_Fei DESC";
            //echo $cargar_per_214;
            return $cargar_per_214;
        
        case 339:
            $sql = "SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                        INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                        WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='';";
            //echo $sql;
            break;
        case 340:
            if ($Par_Sql[1] == "") {
                $bancoSql = "";
            } else {
                $bancoSql = " AND cheques.Ban_Cod=$Par_Sql[1]";
            }
            $sql = "SELECT CONCAT_WS('-',Asi_Cod,Che_Cod) as id,Che_Cod,Asi_Cod, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',CONCAT(Prs_Ape,' ',Prs_Nom),Che_Ben) as proveedor,CONCAT_WS(' ',Pld_Des,'- ( Cta.#',Ban_Cue,')  ') as banco,Che_Num,Che_Val,Che_Fec,Che_Cob,Che_Est FROM cheques
                        JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                        JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
                        JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                        WHERE Che_Est='A' $bancoSql AND plan_cuenta.Emp_Cod=$Par_Sql[0] $Par_Sql[2] ;";
            //echo $sql;
            break;
        case 341:
            $id = explode("-", $Par_Sql[2]);
            $ins_cheques_191 = "UPDATE cheques SET Che_Est = '$Par_Sql[0]',Che_Cob = '$Par_Sql[1]' WHERE Asi_Cod=$id[0] AND Che_Cod=$id[1]";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 342:
            $ins_cheques_191 = "SELECT asientos.Asi_Cod,Pld_Cdc, asientos.Asi_Deh, sum(asientos.Asi_Val) as Total,det_plan.Pld_Des,Ban_Cod,Ban_Cue FROM asientos  
                    INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod
                    INNER JOIN banco ON banco.Pld_Cod = det_plan.Pld_Cod
                    LEFT JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
                    WHERE  
                        comprobantes.Com_Est = 'A'				  
                        AND det_plan.Pld_Cod = '$Par_Sql[0]'  
                        AND comprobantes.Com_Fec <= '$Par_Sql[1] 23:59:59'
                        GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 343:
            if ($Par_Sql[3] == 'd') {
                $Par_Sql[3] = "(Prs_Ape like '%$Par_Sql[0]%' OR Prs_Nom like '%$Par_Sql[0]%')";
            } else {
                $Par_Sql[3] = "Com_Num='$Par_Sql[0]'";
            }
            $ins_cheques_191 = "SELECT Tia_Abr,comprobantes.Com_Cod,Pld_Cod,Asi_Deh, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est, Com_Gen 
                        FROM comprobantes, proveedore, persona, asientos,tipo_asien 
                        WHERE $Par_Sql[3]
                        AND Asi_Deh='$Par_Sql[4]' 
                        AND Pld_Cod=$Par_Sql[2]
                        AND comprobantes.Com_Cod=asientos.Com_Cod AND comprobantes.Tia_Cod=tipo_asien.Tia_Cod 
                        AND comprobantes.Prv_Cod=proveedore.Prv_Cod 
                        AND proveedore.Prs_Cod=persona.Prs_Cod 
                        AND comprobantes.Pec_Cod='$Par_Sql[1]'                         
                        AND Com_Est='A'
                        AND comprobantes.Com_Cod
                        NOT IN 
                                (	SELECT asientos.Com_Cod 
                                        FROM asientos, cheques, comprobantes 
                                WHERE asientos.Asi_Cod= cheques.Asi_Cod         
                                AND comprobantes.Com_Cod = asientos.Com_Cod 
                                AND cheques.Che_Est='A' 
                                AND comprobantes.Pec_Cod='$Par_Sql[1]')";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 344:
            $ins_cheques_191 = "SELECT CONCAT_WS('-',asientos.Asi_Cod,Che_Cod) as id,Com_Fec,cheques.Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Tia_Abr,' - ',CAST( cheques.Asi_Cod AS CHAR)) as asiento FROM cheques
                                    INNER JOIN proveedore ON cheques.Prv_Cod= proveedore.Prv_Cod
                                    INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod
                                    INNER JOIN asientos ON cheques.Asi_Cod= asientos.Asi_Cod
                                    INNER JOIN comprobantes ON asientos.Com_Cod= comprobantes.Com_Cod
                                    INNER JOIN tipo_asien ON comprobantes.Tia_Cod= tipo_asien.Tia_Cod
                                    WHERE (Che_Est='A' OR Che_Est='C')  AND Com_Est='A' AND Pld_Cod=$Par_Sql[0]
                                        AND comprobantes.Com_Fec <= '$Par_Sql[2] 23:59:59'
                                        AND (cheques.Che_Cob >= '$Par_Sql[2] 23:59:59' OR cheques.Che_Cob IS NULL)
                                    ORDER BY Com_Fec";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 345:
            if ($Par_Sql[3] == 'd') {
                $Par_Sql[3] = "(Prs_Ape like '%$Par_Sql[0]%' OR Prs_Nom like '%$Par_Sql[0]%')";
            } elseif ($Par_Sql[3] == 'c') {
                $Par_Sql[3] = "Com_Num='$Par_Sql[0]'";
            }
            if ($Par_Sql[3] == 'n') {
                $Par_Sql[3] = "Che_Num='$Par_Sql[0]'";
            }
            if ($Par_Sql[4] != "") {
                $Par_Sql[4] = "AND asientos.Pld_Cod=" . $Par_Sql[4];
            }
            $sql = "SELECT Tia_Abr,comprobantes.Com_Cod,Che_Num, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est 
                                FROM comprobantes, proveedore, persona, asientos, cheques, det_plan,plan_cuenta,tipo_asien
                                WHERE $Par_Sql[3]
                                AND comprobantes.Pec_Cod='$Par_Sql[2]'
                                $Par_Sql[4]
                                AND plan_cuenta.Emp_Cod=$Par_Sql[1]
                                AND comprobantes.Prv_Cod=proveedore.Prv_Cod AND comprobantes.Tia_Cod=tipo_asien.Tia_Cod 
                                AND proveedore.Prs_Cod=persona.Prs_Cod 
                                AND comprobantes.Com_Est='A'  
                                AND comprobantes.Com_Cod = asientos.Com_Cod 
                                AND asientos.Asi_Cod = cheques.Asi_Cod 
                                AND det_plan.Pld_Cod=asientos.Pld_Cod
                                AND det_plan.Pla_Cod=plan_cuenta.Pla_Cod                                
                                GROUP BY comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, Com_Val, Com_Est ORDER BY Che_Num ASC";
            //echo $sql;
            break;

        case 346:
            $ins_cheques_191 = "UPDATE cheques SET Che_Est = 'I' WHERE Asi_Cod='$Par_Sql[0]'";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 347:
            /* Consulta de los tipos de asientos  filtrados por el sub-tipo */
            if ($Par_Sql[0] == "")
                $Par_Sql[0] = " WHERE Tia_Tip='B'";
            else
                $Par_Sql[0] = "";
            $sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
            break;
        case 348:
            $sql = "SELECT * FROM file_banco WHERE Fil_Cod_Rec=0";
            break;
        case 349:
            $sql = "SELECT Fil_Cod,Flc_Cam FROM file_campo";
            break;
        case 350:
            $sql = "SELECT Flc_Cod,Flc_Cam,file_campo.Fil_Cod,file_banco.Fil_Cam FROM file_campo 
                        INNER JOIN file_banco ON file_banco.Fil_Cod=file_campo.Fil_Cod
                        INNER JOIN file_banco as parent ON file_banco.Fil_Cod_Rec=parent.Fil_Cod
                        WHERE parent.Fil_Cam='$Par_Sql[0]' ";
            break;
        case 351:
            if ($Par_Sql[2] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[3] == "") {
                $campos = "COUNT(Prv_Cod) as total";
            } else {
                $Par_Sql[3] = "ORDER BY Prs_Ape " . $Par_Sql[3];
                $campos = " Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,Prs_Ape,Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
            }
            $bus_xmld_331 = "SELECT $campos
                                FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $bus_xmld_331;
            return $bus_xmld_331;
        case 352:
            if ($Par_Sql[3] == "d") {
                $search = "det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";
            } else {
                $search = "det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[4] == "") {
                $campos = "COUNT(det_plan.Pld_Cod) as total";
            } else {
                $Par_Sql[4] = "ORDER BY det_plan.Pld_Cod " . $Par_Sql[4];
                $campos = "det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                                IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                                IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
            }
            $bus_xmld_331 = "SELECT $campos
                                FROM det_plan 
                                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                                INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                                INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
                                LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                                LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                                WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
                                AND $search AND Pec_Cod =$Par_Sql[2] 
                                AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
            //echo $bus_xmld_331;
            return $bus_xmld_331;
        case 353:
            $ins_cheques_191 = "SELECT cheques.Asi_Cod,cheques.Che_Est FROM cheques
                    INNER JOIN asientos ON cheques.Asi_Cod=asientos.Asi_Cod
                    INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    WHERE Emp_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' AND det_plan.Pld_Cod=$Par_Sql[2] AND (Che_Est='A' OR Che_Est='C')";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 354:
            $ins_cheques_191 = "UPDATE cheques SET Che_Est = '$Par_Sql[0]',Che_Cob = '$Par_Sql[1]' WHERE Asi_Cod=$Par_Sql[2] AND Che_Num=$Par_Sql[3]";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 355:
            /*
             * Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
             */
            $sql = "SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes, tipo_asien WHERE comprobantes.Tia_Cod=tipo_asien.Tia_Cod AND 
                tipo_asien.Tia_Ini='$Par_Sql[0]' AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
            break;
        case 356:
            $ins_compi = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[9]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Tipo='$Par_Sql[8]', Com_Doc='$Par_Sql[10]',Usu_Cod='$_SESSION[Ses_Usu_Cod]', Com_Mod='$Par_Sql[11]' "; //Antes Com_Tip
            //echo $ins_compi;
            return $ins_compi;
        /*
         * Inserci�n de cada asiento del comprobante 
         */
        case 357:
            $ins_asie = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 358:
            $ins_asie = "SELECT comprobantes.Com_Cod, Com_Doc,det_plan.Pld_cod,Pld_des FROM comprobantes
                            INNER JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE Com_Doc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND asientos.Pld_Cod=$Par_Sql[2]";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 359:
            if ($Par_Sql[2] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[3] == "") {
                $campos = "COUNT(Cli_Cod) as total";
            } else {
                $Par_Sql[3] = "ORDER BY Prs_Ape " . $Par_Sql[3];
                $campos = " Cli_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as clientes, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
            }
            $bus_xmld_331 = "SELECT $campos
                                FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $bus_xmld_331;
            return $bus_xmld_331;
        case 360:
            $sql = "SELECT MAX(Che_Num) as Che_Num FROM cheques WHERE Ban_Cod=$Par_Sql[0];";			

            break;
        case 361:
            $ins_asie = "SELECT comprobantes.Com_Cod, Com_Doc,det_plan.Pld_cod,Pld_des FROM comprobantes
                            INNER JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE Com_Doc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND asientos.Pld_Cod=$Par_Sql[2] AND asientos.Asi_Deh='$Par_Sql[3]' AND comprobantes.Com_Cod <> $Par_Sql[4] and comprobantes.Com_Est='A'";
            //echo $ins_asie."<br>";
            return $ins_asie;
            
        case 362:
            $numero_cheque="";
            $chefec=(!isset($Par_Sql[6])||$Par_Sql[6]!='B'?'Che_Fec':'Che_Cob');
            if(isset($Par_Sql[5]) && !empty($Par_Sql[5])){
                $numero_cheque=" AND (CAST(cheques.Che_Num as CHAR) = '$Par_Sql[5]')";
            }
            if(($Par_Sql[2]*1)==2){
                $cheq="AND {$chefec}>'$Par_Sql[3]' AND {$chefec}>Com_Fec /*AND Com_Fec<='$Par_Sql[3]'*/";
                if(!empty($Par_Sql[4])) $cheq.="AND {$chefec}<'$Par_Sql[4]'";
            }else{ if(empty($Par_Sql[3])||empty($Par_Sql[4])) $cheq=''; else $cheq="AND {$chefec} BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";  }
            if(($Par_Sql[2]*1)==3)$cheq=$cheq." AND cheques.Che_Est='C'";
            if(($Par_Sql[2]*1)==4)$cheq=$cheq." AND cheques.Che_Est='A'";
            if(($Par_Sql[2]*1)==5)$cheq=$cheq." AND cheques.Che_Est='I'";
            if(($Par_Sql[2]*1)==6)$cheq=$cheq." AND cheques.Che_Est='P'";
            $sql="SELECT CONCAT(CAST(cheques.Ban_Cod AS CHAR),'_',CAST(Che_Num as CHAR),'_',CAST(Che_Cod as CHAR)) as Che_Cod , cheques.Che_Cod as  Cheque_Cod,comprobantes.Com_Cod,
                cheques.Asi_Cod,cheques.Prv_Cod,Com_Fec,Pld_Des,Ban_Cue,Che_Num,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,banco.Ban_Cod,
                IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',CONCAT(Prs_Ape,' ',Prs_Nom),Che_Ben) AS Beneficiario,
                comprobantes.Com_Cod as codigo_Comp,
                (Select count(Cpp_Cod) as  editVal from det_ccpp_p where det_ccpp_p.Com_Cod= codigo_Comp )as EdicionVal,
                IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,
                CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),
                CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Num,Che_Cob
                FROM cheques
                INNER JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
                INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
                INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod
                INNER JOIN comprobantes ON comprobantes.Com_Cod=asientos.Com_Cod
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                WHERE Emp_Cod=$Par_Sql[0] AND cheques.Ban_Cod=$Par_Sql[1] $cheq
                $numero_cheque
                ORDER BY {$chefec}";
            break;
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 363:// insertar persona para beneficiario
            $ins_asie = "INSERT INTO persona SET Prs_Ced='0',Prs_Ape=UPPER('$Par_Sql[0]'),Prs_Nom=UPPER('$Par_Sql[1]'),Ciu_Cod=217";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 364:// insertar proveedor para beneficiario
            $ins_asie = "INSERT INTO proveedore SET Prs_Cod=$Par_Sql[0], Emp_Cod=$Par_Sql[1],Prv_Con='N',Prv_Esp='N'";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 365:// insertar persona para beneficiario
            $ins_asie = "SELECT Prv_Cod,UPPER(Prs_Nom) AS Prs_Nom,UPPer(Prs_Ape) AS Prs_Ape FROM proveedore 
                        INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                        WHERE Emp_Cod=$Par_Sql[0] AND (Prs_Ape LIKE '%$Par_Sql[1]%' OR Prs_Nom LIKE '%$Par_Sql[1]%' OR Prs_Ced LIKE '$Par_Sql[1]%')
                        LIMIT 7";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 366:// anular comprobante
            $ins_asie = "UPDATE comprobantes set Com_Est='I' WHERE Com_Cod=$Par_Sql[0] ";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 367:// anular comprobante
            $ins_asie = "SELECT * FROM asientos WHERE Asi_Cod=$Par_Sql[0] ";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 368:// anular comprobante
            $ins_asie = "SELECT COUNT(Che_Cod) AS conteo FROM cheques inner join banco on banco.Ban_Cod=cheques.Ban_Cod WHERE banco.Pld_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' ";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 369:// anular comprobante
            $ins_asie = "UPDATE cheques set Che_Cob='$Par_Sql[0]' WHERE Ban_Cod='$Par_Sql[1]' AND Che_Num='$Par_Sql[2]' AND Che_Cod=$Par_Sql[3] ";
            //echo $ins_asie."<br>";
            return $ins_asie;

        case 370:
            $ins_asie = "SELECT Ban_Cod,banco.Pld_Cod,Pld_Des FROM banco
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                        WHERE Emp_Cod='$Par_Sql[0]' AND Ban_Cue<>'0' AND Pld_Est='A' AND Pla_Est='A' AND Ban_Est='A'";
            //echo $ins_asie."<br>";
            return $ins_asie;
        case 371:
            $sql = "INSERT INTO cheques_otros(Prv_Cod,Ban_Cod,Che_Num,Che_Fec,Che_Cob,Che_Val,Che_Obs,Pld_Cod,Com_Fec,Che_Est)
                                VALUES ($Par_Sql[Prv_Cod],$Par_Sql[Ban_Cod],$Par_Sql[Che_Num],'$Par_Sql[Che_Fec]'," . (isset($Par_Sql['Che_Cob']) ? "$Par_Sql[Che_Cob]" : 'NULL') . ",$Par_Sql[Che_Val],'$Par_Sql[Che_Obs]',$Par_Sql[Pld_Cod],'$Par_Sql[Che_Fec]','$Par_Sql[Che_Est]');";
            //echo $sql."<br>";
            break;
        case 372:
            $sql = "SELECT CONCAT_WS('-',Asi_Cod,Che_Cod) as id,Com_Fec,Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Che_Obs AS Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Asi_Cod) as asiento FROM cheques_otros
                                    INNER JOIN proveedore ON cheques_otros.Prv_Cod= proveedore.Prv_Cod
                                    INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod                                    
                                    WHERE (Che_Est='A' OR Che_Est='C')  AND Pld_Cod=$Par_Sql[3] AND Emp_Cod='$Par_Sql[0]'
                                     AND Com_Fec <= '$Par_Sql[2] 23:59:59'
                                        AND (Che_Cob >= '$Par_Sql[2] 23:59:59' OR Che_Cob IS NULL)
                                    ORDER BY Com_Fec";
            //echo $sql;
            break;

        case 373:
            $sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien WHERE Tia_Ini='$Par_Sql[1]' " . ($Par_Sql[0] == '' ? '' : " AND Tia_Tip='$Par_Sql[0]'");
            break;
        case 374:
            /* Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
             */
            $sql = "SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
            //echo $sql;
            break;
        case 375:
            /* Selecciona los cheques entre un rango de fechas ANULADOR  */
            $cons_cheq_163 = "SELECT Tia_Abr,comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Fec, Che_Obs, comprobantes.Com_Fec, cheques.Che_Est, Com_Con "
                    . "FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona,tipo_asien "
                    . "where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND (cheques.Che_Fec BETWEEN '$Par_Sql[0] 00:00:00' AND '$Par_Sql[1] 23:59:59') AND Che_Est = '$Par_Sql[2]'
								  AND tipo_asien.Tia_Cod=comprobantes.Tia_Cod AND Emp_Cod=$Par_Sql[3]  AND Com_Est = '$Par_Sql[4]' ORDER BY banco.Ban_Cod, Che_Num, Prs_Ape, Prs_Nom";
            //echo $cons_cheq_163;
            return $cons_cheq_163;        
        case 376:
            $sql = "SELECT COUNT(Che_Cod)AS conteo FROM cheques_otros WHERE Ban_Cod=$Par_Sql[0] AND Che_Num=$Par_Sql[1] UNION ALL SELECT COUNT(Che_Cod)AS conteo FROM cheques WHERE Ban_Cod=$Par_Sql[0] AND Che_Num=$Par_Sql[1];";
            //echo $sql."<br>";
            break;
        case 377:
            $sql = "SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
            INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
            INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
            WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='' GROUP BY Ban_Cod;";
            //echo $sql;
            break;
        case 378:
            $sql = "SELECT CONCAT_WS('-','CH',Che_Cod) as id,Che_Cod,Asi_Cod, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',CONCAT(Prs_Ape,' ',Prs_Nom),Che_Ben) AS proveedor,CONCAT_WS(' ',Pld_Des,'- ( Cta.#',Ban_Cue,')  ') as banco,Che_Num,Che_Val,Che_Fec,Che_Cob,Che_Est,'EXT' AS t_type  FROM cheques_otros
                        JOIN banco ON banco.Ban_Cod=cheques_otros.Ban_Cod
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                        JOIN proveedore ON proveedore.Prv_Cod=cheques_otros.Prv_Cod
                        JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                        WHERE Che_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] $Par_Sql[2] ;";
            //echo $sql;
            break;
        case 379:
            $id = explode("-", $Par_Sql[2]);
            $sql = "UPDATE cheques_otros SET " . (empty($Par_Sql[0]) ? '' : "Che_Est='$Par_Sql[0]',") . "Che_Cob = '$Par_Sql[1]' WHERE Che_Cod=$id[1]";
            //echo $sql;
            break;
        case 380:
            $id=  explode("-", $Par_Sql[2]);
            $chefec=(!isset($Par_Sql[6])||$Par_Sql[6]!='B'?'Che_Fec':'Che_Cob');
            $numero_cheque="";
            if(isset($Par_Sql[5]) && !empty($Par_Sql[5])){
                $numero_cheque=" AND (CAST(cheques_otros.Che_Num as CHAR) = '$Par_Sql[5]')";
            }
            if(($Par_Sql[2]*1)==2){
                $cheq="AND {$chefec}>'$Par_Sql[3]' AND {$chefec}>Com_Fec /*AND Com_Fec<='$Par_Sql[3]'*/";
                if(empty($Par_Sql[4])) $cheq.="AND {$chefec}<'$Par_Sql[4]'";
            }
            else{ if(empty($Par_Sql[3])||empty($Par_Sql[4])) $cheq=''; else $cheq="AND {$chefec} BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";  }
            if(($Par_Sql[2]*1)==3)$cheq=$cheq." AND cheques_otros.Che_Est='C'";
            if(($Par_Sql[2]*1)==4)$cheq=$cheq." AND cheques_otros.Che_Est='A'";
            if(($Par_Sql[2]*1)==5)$cheq=$cheq." AND cheques_otros.Che_Est='I'";
            if(($Par_Sql[2]*1)==6)$cheq=$cheq." AND cheques_otros.Che_Est='P'";
            $sql="SELECT CONCAT('CHE-',CAST(Che_Cod as CHAR)) as Che_Cod ,cheques_otros.Che_Cod as Cheque_Cod,Pld_Des,Ban_Cue,banco.Ban_Cod,
                Che_Num,0 as EdicionVal,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,
                IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',CONCAT(Prs_Ape,' ',Prs_Nom),Che_Ben) AS Beneficiario,
                IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,
                Che_Cob,'EXT' AS t_type  FROM cheques_otros
                INNER JOIN banco ON banco.Ban_Cod=cheques_otros.Ban_Cod
                INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=cheques_otros.Prv_Cod
                INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                WHERE Emp_Cod=$Par_Sql[0] AND cheques_otros.Ban_Cod=$Par_Sql[1] $cheq
                $numero_cheque
                ORDER BY {$chefec}";
            //echo $sql;
            break;
        case 381:
            $fecha = explode('-', $Par_Sql[1]);
            $sql = "SELECT asientos.Asi_Cod,Pld_Cdc, asientos.Asi_Deh, sum(asientos.Asi_Val) as Total,det_plan.Pld_Des,Ban_Cod,Ban_Cue FROM asientos  
            INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod
            INNER JOIN banco ON banco.Pld_Cod = det_plan.Pld_Cod
            LEFT JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
            WHERE  
                comprobantes.Com_Est = 'A'				  
                AND banco.Ban_Cod = '$Par_Sql[0]'  
                 AND comprobantes.Com_Fec BETWEEN '$fecha[0]-01-01 00:00:00' AND '$Par_Sql[1] 00:00:00'
                GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";
            //echo $sql;
            break;
        case 382:
            $sql = "SELECT CONCAT_WS('-',asientos.Asi_Cod,Che_Cod) as id,Com_Fec,cheques.Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Tia_Abr,' - ',CAST( cheques.Asi_Cod AS CHAR)) as asiento FROM cheques
            INNER JOIN proveedore ON cheques.Prv_Cod= proveedore.Prv_Cod
            INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod
            INNER JOIN asientos ON cheques.Asi_Cod= asientos.Asi_Cod
            INNER JOIN comprobantes ON asientos.Com_Cod= comprobantes.Com_Cod
            INNER JOIN tipo_asien ON comprobantes.Tia_Cod= tipo_asien.Tia_Cod
            WHERE (Che_Est='A' OR Che_Est='C')  AND Com_Est='A' AND cheques.Ban_Cod=$Par_Sql[0]
                AND comprobantes.Com_Fec <= '$Par_Sql[2] 23:59:59'
                AND (cheques.Che_Cob >= '$Par_Sql[2] 23:59:59' OR cheques.Che_Cob IS NULL)
            ORDER BY Com_Fec";
            //echo $sql;
            break;
        case 383:
            $sql = "SELECT CONCAT_WS('-',Asi_Cod,Che_Cod) as id,Com_Fec,Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Che_Obs AS Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Asi_Cod) as asiento FROM cheques_otros
            INNER JOIN proveedore ON cheques_otros.Prv_Cod= proveedore.Prv_Cod
            INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod                                    
            WHERE (Che_Est='A' OR Che_Est='C')  AND Ban_Cod=$Par_Sql[3] AND Emp_Cod='$Par_Sql[0]'
             AND Com_Fec <= '$Par_Sql[2] 23:59:59'
                AND (Che_Cob >= '$Par_Sql[2] 23:59:59' OR Che_Cob IS NULL)
            ORDER BY Com_Fec";
            //echo $sql;
            break;
        case 384:
            $sql = "SELECT DISTINCT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef FROM perio_cont               
                INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod                
                WHERE plan_cuenta.Emp_Cod=$Par_Sql[0] ORDER BY Periodo DESC;";
            //echo $sql;
            break;

        /* actualizamos el COM_GEN de la tabla comprobante, se ejecuta cuando se genera un cheque */
        case 385:
            $sql = "UPDATE comprobantes SET Com_Gen='A' WHERE Com_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 386:
            $sql = "SELECT * FROM banco
                INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE Pec_Cod=$Par_Sql[0] AND Ban_Cue!='' AND Ban_Cue!='0' ";
            //echo $sql;
            break;
        case 387:
            $sql="UPDATE cheques SET Che_Num = NULL, Che_Obs='$Par_Sql[Che_Obs]-Cheque $Par_Sql[Che_Num] Liberado' WHERE Prv_Cod=$Par_Sql[Prv_Cod] AND Ban_Cod=$Par_Sql[Ban_Cod] AND Asi_Cod=$Par_Sql[Asi_Cod] AND Che_Cod = $Par_Sql[Cheque_Cod]";
            //echo $sql;
            break;
        case 389://modificar Cheque
            $sql="UPDATE cheques SET Prv_Cod=$Par_Sql[Prv_Cod] , Ban_Cod=$Par_Sql[Ban_Cod] , Asi_Cod=$Par_Sql[Asi_Cod] , Che_Num=$Par_Sql[Che_Num],Che_Ben='$Par_Sql[Che_Ben]', Che_Fec='$Par_Sql[Che_Fec]'  , Che_Val=$Par_Sql[Che_Val], Che_Obs='$Par_Sql[Che_Obs]'  WHERE Prv_Cod=$Par_Sql[Prv_Cod_Ant] AND Ban_Cod=$Par_Sql[Ban_Cod_Ant] AND Asi_Cod=$Par_Sql[Asi_Cod] AND Che_Cod = $Par_Sql[Cheque_Cod]";
            //echo $mod_cheque;
            break;
        case 390://verificar existencia de N cheque
                $sql="SELECT Che_Num from cheques where Ban_Cod=$Par_Sql[Ban_Cod] AND Che_Num=$Par_Sql[Che_Num]";
                //echo $sql;
                break;
        case 391://obtener Bancos para edicion Cheques
            $sql="SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
                INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='' AND Ban_Tip='B' GROUP BY Ban_Cod;";
                //echo $sql;
            break;
        case 392://actualizar asiento en edicion de cheque
                $sql="UPDATE asientos set Pld_Cod=$Par_Sql[Pld_Cod] WHERE Com_Cod=$Par_Sql[Com_Cod]";
            break;
        case 393://verificacion de Asiento contable en CCXPP
            $sql="SELECT cheques.Che_Cod,cheques.Prv_Cod,cheques.Ban_Cod,cheques.Asi_Cod
                FROM cheques
                INNER JOIN asientos ON asientos.Asi_Cod = cheques.Asi_Cod
                INNER JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
                INNER JOIN det_ccpp_p ON comprobantes.Com_Cod = det_ccpp_p.Com_Cod
                WHERE cheques.Asi_Cod =$Par_Sql[Asi_Cod]";
                //echo $sql;
            break;
        case 394://Actualiza valor de asiento con el de cheque
            $sql="UPDATE asientos set Asi_Val=$Par_Sql[Che_Val] WHERE Com_Cod=$Par_Sql[Com_Cod]";
                //echo $sql;
            break;
        case 395://Actualizar valor de comprobantes con de Cheque
            $sql="UPDATE comprobantes set Com_Val=$Par_Sql[Che_Val] WHERE Com_Cod=$Par_Sql[Com_Cod]";
                //echo $sql;
            break;
        case 396:  //Actualizar datos de cheques_otros
            $sql="UPDATE cheques_otros SET  Ban_Cod=$Par_Sql[Ban_Cod] ,
                Che_Num=$Par_Sql[Che_Num],Che_Ben='$Par_Sql[Che_Ben]',
                Che_Fec='$Par_Sql[Che_Fec]'  , Che_Val=$Par_Sql[Che_Val], Che_Obs='$Par_Sql[Che_Obs]'
                WHERE Che_Cod = $Par_Sql[Cheque_Cod]";
            //echo $sql;"
            break;
        case 397:
            $sql="SELECT Che_Num from cheques_otros where Ban_Cod=$Par_Sql[Ban_Cod] AND Che_Num=$Par_Sql[Che_Num]";
            break;
        
        //sql para modificacion de comprobantes en libro_banco
        case 398:
            $periodo_cod="AND Pec_Cod=$Par_Sql[0]";
            if(!isset($Par_Sql[0])||$Par_Sql[0]===0){
                $periodo_cod="";
            }
            $sql = "SELECT * FROM banco
                INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE Ban_Cue!='' $periodo_cod  AND Ban_Cue!='0' ";
            //echo $sql;
            break;
        case 399:
            $search_fechas = "and Com.Com_Fec between '$Par_Sql[txt_fec_ini]' and '$Par_Sql[txt_fec_fin]'";
            $filtro_tipo='';
            if($Par_Sql['Com_Tip']!=='0')
                $filtro_tipo="and Tip.Tia_Ini='$Par_Sql[Com_Tip]'";
            
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(Com.Com_Cod) AS total";
            } else {
                $campos = "if(Com.Com_Gen='A','si','no')as Has_Cheque,PrsP.Prs_Ape as Prov_Apellido,PrsP.Prs_Nom as Prov_Nombre,PrsP.Prs_Est as Prov_Estado,Asi.Pld_Cod,concat(PrsU.Prs_Ape,' ',PrsU.Prs_Nom) as Usuario ,CONCAT(Tip.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com.Com_Fec))=1,CONCAT('0',CAST(MONTH(Com.Com_Fec) AS char)),
            CAST(MONTH(Com.Com_Fec) AS char)),'-',CAST(Com.Com_Num AS char)) as Compr_Num,Tip.Tia_Ini,if(PrsP.Prs_Ape is null,concat(PrsC.Prs_Ape,' ',PrsC.Prs_Nom),concat(PrsP.Prs_Ape,' ',PrsP.Prs_Nom))as Prov_Cli,if(Prov.Prv_Cod is null,PrsC.Prs_Ced,PrsP.Prs_Ced)as Prov_Cli_Ced,Com.*";
            }
            $sql = "select DISTINCT $campos from comprobantes as Com
                inner join usuarios as Usu on Usu.Usu_Cod = Com.Usu_Cod
                inner join persona as PrsU on PrsU.Prs_Cod = Usu.Prs_Cod
                left join asientos as Asi on Asi.Com_Cod = Com.Com_Cod
                inner join tipo_asien as Tip on Tip.Tia_Cod = Com.Tia_Cod
                inner join perio_cont as Pcont on Pcont.Pec_Cod = Com.Pec_Cod 
                inner join plan_cuenta as Pcue on Pcue.Pla_Cod = Pcont.Pla_Cod
                left join compr_arol as caro on caro.Com_Cod= Com.Com_Cod
                left join movimiento_cheques as Mov on Mov.Com_Cod = Com.Com_Cod
                left join det_ccpp_c as Cccc on Cccc.Com_Cod= Com.Com_Cod
                left join det_ccpp_p as Ccpp on Ccpp.Com_Cod = Com.Com_Cod
                left join ventas_compr as VetC on VetC.Com_Cod = Com.Com_Cod
                left join compr_auto as ComC on ComC.Com_Cod = Com.Com_Cod
                left join proveedore as Prov on Prov.Prv_Cod = Com.Prv_Cod
                left join persona as PrsP on PrsP.Prs_Cod = Prov.Prs_Cod
                left join cliente as Cli on Cli.Cli_Cod = Com.Cli_Cod
                left join persona as PrsC on PrsC.Prs_Cod = Cli.Prs_Cod 
                where caro.Com_Cod is null and Mov.Com_Cod is null and VetC.Com_Cod is null and ComC.Com_Cod is null and Cccc.Com_Cod is null and Ccpp.Com_Cod is null
                and (Asi.Pld_Cod in ($Par_Sql[Pld_Cod]))
                and Pcue.Emp_Cod=$Par_Sql[Emp_Cod] and Com.Pec_Cod=$Par_Sql[Pec_Cod] /*and Com.Com_Gen='A'*/ $filtro_tipo $search_fechas $Par_Sql[Order_By] $Par_Sql[limits]";
            break;
        case 400:
            $sql="SELECT che.*,if(che.Che_Ben is null or che.Che_Ben = '',concat(per.Prs_Ape,' ',per.Prs_Nom),che.Che_Ben)as Che_Ben,asientos.*,Asi_Deh AS Det_Tip,Pld_Cdc,Pld_Des,Asi_Glo AS Glosa,IF(Asi_Deh='D',Asi_Val,NULL)AS Debe,IF(Asi_Deh='H',Asi_Val,NULL)AS Haber FROM asientos
                 INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                 left join cheques as che on che.Asi_Cod=asientos.Asi_Cod
                 left join proveedore as pro on pro.Prv_Cod = che.Prv_cod
                 left join persona as per on per.Prs_Cod = pro.Prs_Cod
                 WHERE Com_Cod='$Par_Sql[Com_Cod]' ORDER BY Asi_Deh";
            break;
        
        
        case 401:
            $sql="UPDATE comprobantes set Com_Gen='$Par_Sql[Com_Gen]',"
                . "Prv_Cod=$Par_Sql[Prv_Cod],"
                . "Cli_Cod=$Par_Sql[Cli_Cod],"
                . "Usu_Cod=$Par_Sql[Usu_Cod],"
                . "Com_Fec='$Par_Sql[Com_Fec]',"
                . "Com_Con='$Par_Sql[Com_Con]',"
                . "Com_Tip='$Par_Sql[Com_Tip]',"
                . "Com_Val=$Par_Sql[Com_Val],"
                . "Com_Obs='$Par_Sql[Com_Obs]',"
                . "Tia_Cod=$Par_Sql[Tia_Cod],"
                . "Com_Doc=".(isset($Par_Sql['Num_Doc'])&&!empty($Par_Sql['Num_Doc'])?"'$Par_Sql[Num_Doc]'":'NULL')." WHERE Com_Cod=$Par_Sql[Com_Cod]";
            break;
        
        case 402:
            $sql="DELETE FROM asientos WHERE Com_Cod='$Par_Sql[Com_Cod]'";
            //echo $sql."<br>";
            break;
        case 403:
            $sql="INSERT INTO cheques SET Prv_Cod=$Par_Sql[Prv_Cod], Ban_Cod=$Par_Sql[Ban_Cod], Asi_Cod=$Par_Sql[Asi_Cod], Che_Num='$Par_Sql[Che_Num]',".//" Che_Cob='$Par_Sql[4]',"
                    " Che_Val=$Par_Sql[Che_Val], Che_Obs=UPPER('$Par_Sql[Che_Obs]'), Che_Fec='$Par_Sql[Che_Fec]', Che_Cod = $Par_Sql[Che_Cod], Che_Ben=UPPER('$Par_Sql[Che_Ben]')";
            break;
        case 404:
            $sql = "SELECT MAX(Che_Num) as Che_Num FROM cheques inner join banco on banco.Ban_Cod=cheques.Ban_Cod WHERE banco.Pld_Cod=$Par_Sql[0];";
            break;
		case 405:
            $sql = "SELECT COUNT(Che_Cod) AS conteo FROM cheques WHERE Ban_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' ";
            break;	
        case 406:
            $sql = "SELECT comprobantes.Com_Cod, Com_Doc,det_plan.Pld_cod,Pld_des FROM comprobantes
                            INNER JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE Com_Doc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND asientos.Pld_Cod=$Par_Sql[2] AND asientos.Asi_Deh='$Par_Sql[3]' AND comprobantes.Com_Est='A'";
            //echo $ins_asie."<br>";
            break;
        case 407:
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(che.Che_Cod) AS total";
            } else {
                $campos = "che.*,Pld_Des";
            }
            $sql="SELECT $campos FROM cheques AS che 
            INNER JOIN asientos AS asi ON asi.Asi_Cod = che.Asi_Cod
            INNER JOIN banco AS ban ON ban.Ban_Cod= che.Ban_Cod
            INNER JOIN det_plan AS det ON det.Pld_Cod=ban.Pld_Cod
            WHERE asi.Com_Cod=$Par_Sql[Com_Cod]";
            break;
        case 408:
            $sql="SELECT che.Che_Cod FROM cheques AS che 
            INNER JOIN asientos AS asi ON asi.Asi_Cod = che.Asi_Cod
            INNER JOIN banco AS ban ON ban.Ban_Cod= che.Ban_Cod
            INNER JOIN det_plan AS det ON det.Pld_Cod=ban.Pld_Cod
            WHERE asi.Com_Cod=$Par_Sql[Com_Cod] AND che.Che_Est='I'";
            break;
        case 409:
            $sql = "SELECT * FROM bancos";
            //ChromePhp::log($sql);
            break;
        case 410:
            //INSERTAR CHEQUES POST FECHADOS
            $sql = "INSERT INTO cheques_postf (Bak_Cod, Pec_Cod, Emp_Cod, Chp_Fec, Chp_Num, Chp_Cta, Chp_Pro, Chp_Ben, Chp_Val, Chp_Por, Chp_Gan, Chp_Ent, Chp_Con, Chp_Obs)
            values ($Par_Sql[Bak_Cod],$Par_Sql[Pec_Cod],$Par_Sql[Emp_Cod],'$Par_Sql[Chp_Fec]',$Par_Sql[Chp_Num],$Par_Sql[Chp_Cta],'$Par_Sql[Chp_Pro]','$Par_Sql[Chp_Ben]',$Par_Sql[Chp_Val],$Par_Sql[Chp_Por],$Par_Sql[Chp_Gan],$Par_Sql[Chp_Ent],'$Par_Sql[Chp_Con]','$Par_Sql[Chp_Obs]')";
            //ChromePhp::log($sql);
            break;
        case 411:
            $sql = "SELECT Chp_Cod, cheques_postf.Emp_Cod, Bak_Des, Chp_Ben, Chp_Fec, Chp_Num, ROUND(Chp_Val,2) AS Valor, CONCAT(ROUND(Chp_Por,2), ' %') AS Porcentaje, ROUND(Chp_Gan,2) AS Ganancia, ROUND(Chp_Ent,2) AS Entregado, Chp_Est, Chp_Cta FROM cheques_postf 
                    INNER JOIN bancos ON cheques_postf.Bak_Cod = bancos.Bak_Cod
                    INNER JOIN empresas ON cheques_postf.Emp_Cod = empresas.Emp_Cod
                    WHERE cheques_postf.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            //ChromePhp::log($sql);
            break;
    }
    return $sql;
}