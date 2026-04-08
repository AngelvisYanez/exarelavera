<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creacion:	2017-11-07
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
        case 1:
            $search_fechas = "and Che.Che_Fec between '$Par_Sql[txt_fec_ini]' and '$Par_Sql[txt_fec_fin]'";

            $search_banco = '';
            if(  !empty($Par_Sql['Pld_Cod']) ){
                $search_banco = "  AND  Ban.Pld_Cod=$Par_Sql[Pld_Cod] ";
            }

            $search='';
             if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(Che.Che_Cod) AS total";
            } else {
                $campos = "comprobantes.Com_Est,Ban.*,Che.*,Per.*,IF(Che_Ben IS NULL OR Che_Ben='',CONCAT(Per.Prs_Ape,' ',Per.Prs_Nom),Che_Ben) as Prov,Det.Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),
                    CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Num";
            }
            
            if (isset($Par_Sql['op_opciones'])&& !empty($Par_Sql['op_opciones'])){
                switch ($Par_Sql['op_opciones']){
                    case 'p':
                        $search = "and (Per.Prs_Ape Like '%$Par_Sql[search]%' OR Per.Prs_Nom Like '%$Par_Sql[search]%')";
                    break;
                    case 'c':
                        $search = "and Per.Prs_Ced Like '%$Par_Sql[search]%'";
                    break;
                    case 'd':
                        if ($Par_Sql['search'] !== "") {
                            $search = "and Che.Che_Num = $Par_Sql[search]";
                        }
                    break;
                }
            }            
           
            $sql = "select $campos from cheques as Che
                    inner join asientos as Asi on Asi.Asi_Cod = Che.Asi_Cod
                    inner join banco as Ban on Ban.Ban_Cod = Che.Ban_Cod
                    inner join det_plan as Det on Det.Pld_Cod = Ban.Pld_Cod
                    inner join plan_cuenta as Pla on Pla.Pla_Cod = Det.Pla_Cod
                    inner join proveedore as Pro on Che.Prv_Cod = Pro.Prv_Cod
                    inner join persona as Per on Per.Prs_Cod = Pro.Prs_Cod
                    left JOIN comprobantes ON comprobantes.Com_Cod=Asi.Com_Cod
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                    where Pla.Emp_Cod=$Par_Sql[Emp_Cod] $search_fechas $search $search_banco
                 $Par_Sql[Order_By] $Par_Sql[limits]";
            //echo  $sql;
			break;
        case 2:
            $sql = "SELECT * FROM banco
                INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE Pec_Cod=$Par_Sql[Pec_Cod] AND Ban_Cue!='' AND Ban_Cue!='0'";
            break;
        case 3:
            $sql = "SELECT DISTINCT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef FROM perio_cont
            INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
            WHERE plan_cuenta.Emp_Cod=$Par_Sql[0] ORDER BY Periodo DESC;";
            //echo $sql;
            break;
        case 4:
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
        case 5:
            $sql="select * from tipo_asien where Tia_Ini='E' and Tia_Abr='EG' Limit 1";
            break;
        case 6:
            $sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[Pec_Cod], Prv_Cod=$Par_Sql[Prv_Cod], Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con=UPPER('$Par_Sql[Com_Con]'), Tia_Cod='$Par_Sql[Tia_Cod]', Com_Val=0, Com_Obs=UPPER('$Par_Sql[Com_Obs]'),Usu_Cod='$_SESSION[Ses_Usu_Cod]',Com_Est='I'";
            break;
        case 7://verificar existencia de N cheque
            $sql="SELECT Che_Num from cheques where Ban_Cod=$Par_Sql[Ban_Cod] AND Che_Num=$Par_Sql[Che_Num]";
            break;
        case 8:
            $sql="insert into asientos (Com_Cod,Asi_Deh,Asi_Val,Asi_Con,Pld_Cod,Asi_Glo) values($Par_Sql[Com_Cod],'H',0,'cheque',$Par_Sql[Pld_Cod],'$Par_Sql[Glosa]')";
            break;
        case 9:
            $sql="insert into cheques (Che_Cod,Prv_Cod,Ban_Cod,Asi_Cod,Che_Num,Che_Val,Che_Fec,Che_Est,Che_Obs) values(
                  $Par_Sql[Che_Cod],$Par_Sql[Prv_Cod],$Par_Sql[Ban_Cod],$Par_Sql[Asi_Cod],$Par_Sql[Che_Num],0,'$Par_Sql[Che_Fec]','I',UPPER('$Par_Sql[Che_Obs]'))";
            break;      
    }
    //echo $sql."<br/>";
    return $sql;
}
