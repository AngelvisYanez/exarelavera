<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci?n:	2015-07-22
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administracion.LOGICA
 */
function sentencias_Pec($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {   case 0:
            $sql="";
            //echo $sql.'<br/>';
            break;
        case 1://Insert perio_cont
            $sql="INSERT INTO perio_cont (Pec_Fei,Pec_Fef,Pla_Cod) VALUES ('$Par_Sql[Pec_Fei]','$Par_Sql[Pec_Fef]',$Par_Sql[Pla_Cod])";
            break; 
        case 2://Update perio_cont
            $sql="UPDATE perio_cont SET Pec_Fei='$Par_Sql[Pec_Fei]', Pec_Fef='$Par_Sql[Pec_Fef]', Pla_Cod=$Par_Sql[Pla_Cod] WHERE  Pec_Cod=$Par_Sql[Peri_Cod] ";
            break;
        case 3://baja perio_cont
            $sql="DELETE FROM perio_cont WHERE Pec_Cod='$Par_Sql[Peri_Cod]';";
            break;
        case 4://SELECCIONA LOS PERIODOS CONTABLES CON VALORES ASOCIADOS        
                if(isset($Par_Sql["limits"])){
                            $Par_Sql["limits"]="ORDER BY Pec_Fei $Par_Sql[limits]";
                            $campos="Pec_Cod AS Peri_Cod,Pec_Fei,Pec_Fef,Pec_Est,perio_cont.Pla_Cod,YEAR(Pec_Fei) AS anio,
                                    (SELECT COUNT(compras.Cop_Cod) FROM compras 
                                    INNER JOIN perio_cont ON perio_cont.Pec_Cod=compras.Pec_Cod 
                                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod 
                                    WHERE  perio_cont.Pec_Cod=Peri_Cod  ) as compras_asoc, 
                                    (SELECT COUNT(caja_aper.Caj_Cod) FROM caja_aper 
                                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=caja_aper.Pun_Cod 
                                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod 
                                    WHERE YEAR(caja_aper.Caj_Fec)=YEAR(Pec_Fei) AND sucursal.Emp_Cod=$Par_Sql[Emp_Cod] ) AS caja_aper_asoc,
                                    (SELECT COUNT(comprobantes.Com_Cod) FROM comprobantes  WHERE Pec_Cod = Peri_Cod ) 
                                    AS comprobantes_asoc ";
                            }
                    else{
                        $campos="COUNT(Pec_Cod) as total"; 
                        $Par_Sql["limits"]="";                        
                    }
                    $sql = "SELECT $campos FROM perio_cont 
                            INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod 
                            WHERE plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
                    //echo $sql;
					break;
        case 5://baja consumo
            $sql="UPDATE consumo SET Con_Est='I' WHERE Con_Cod='$Par_Sql[0]' ";    
                    break;
        
        case 6://SELECCIONA LOS AÑOS EN QUE EXISTEN PERIODOS CONTABLES POR EMPRESA
            $sql="select year(perio_cont.Pec_Fei) as using_yeards from perio_cont,plan_cuenta 
                    where perio_cont.Pla_Cod=plan_cuenta.Pla_Cod and plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod];";
			//echo $sql;		
            break;
    case 7: //seleccionar los Planes de Cuentas de la Empresa
            $sql="select * from plan_cuenta where Emp_Cod=$Par_Sql[0];";
            break;
                        }
    //echo $sql."<br/>";
    return $sql;    
}



