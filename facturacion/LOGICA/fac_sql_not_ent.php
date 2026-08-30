<?php
/**
 * Factura de venta
 */



function sentencias_facturaVenta($id,$Par_Sql)
{
    switch($id)
    {
        case 1://Listado de clientes
            if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
            if($Par_Sql[3]==""){$campos="COUNT(Cli_Cod) as total";}
            else{
                $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                $campos=" Cli_Cod, persona.Prs_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, Cli_Dir, Prs_Dir, IF(Cli_Cor IS NULL OR TRIM(Cli_Cor)='',Prs_Cor,Cli_Cor)AS Prs_Cor, IF(Cli_Est='A','Activo','Inactivo') as Cli_Est";
            }
            $sql="SELECT $campos FROM cliente, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND cliente.Prs_Cod=persona.Prs_Cod AND Cli_Est='A' AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //ChromePhp::log($sql);
            break;
        case 2://Busqueda de clientes
            $sql="SELECT persona.*,cliente.Cli_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as cliente FROM persona
                    LEFT JOIN cliente ON cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1]
                    WHERE Prs_Ced LIKE '$Par_Sql[0]%'  LIMIT 2;";
            //ChromePhp::log($sql);
            break;
        case 3://Insert en la tabla persona
            $sql="INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod,Prs_Tel) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','$Par_Sql[Prs_Sex]',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod],$Par_Sql[Prs_Tel]);";
        //ChromePhp::log($sql);
            break;
        case 4://Insert en la tabla cliente
            $sql="INSERT INTO cliente(Prs_Cod,Cli_Tic,Cli_Cup,Cli_Ruf,Cli_Fac,Cli_Dir,Cli_Con,Cli_Tip,Cli_Cor,Emp_Cod) VALUES($Par_Sql[Prs_Cod],'$Par_Sql[Cli_Tic]','".(empty($Par_Sql['Cli_Cup'])?'0':$Par_Sql['Cli_Cup'])."','".(empty($Par_Sql['Cli_Ruf'])?'':$Par_Sql['Cli_Ruf'])."','".(empty($Par_Sql['Cli_Fac'])?'':$Par_Sql['Cli_Fac'])."','".(empty($Par_Sql['Cli_Dir'])?'':$Par_Sql['Cli_Dir'])."','$Par_Sql[Cli_Con]','".(empty($Par_Sql['Cli_Tip'])?'R':$Par_Sql['Cli_Tip'])."',".(empty($Par_Sql['Cli_Cor'])?'NULL':"'$Par_Sql[Cli_Cor]'").",$Par_Sql[Emp_Cod]);";
            break;
        case 5://Select para cargar los datos de la tabla perio_cont
            $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio,perio_cont.Pla_Cod
                    FROM perio_cont
                    LEFT JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND Pec_Est='A' ORDER BY Pec_Fei DESC";
                   // //ChromePhp::log($sql);
            break;
        case 6://Consulta la ciudad en base al usuario
            $sql = "SELECT sucursal.Ciu_Cod,ciudad.Ciu_Des
                    FROM usuarios, sucursal, ciudad
                    WHERE usuarios.Suc_Cod = sucursal.Suc_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod AND usuarios.Usu_Cod = '$Par_Sql[0]'";
                    //ChromePhp::log($sql);
            break;
        case 7://Select para obtener el Pun_Cod y Vnd_Cod del usuario que ha iniciado la sesiï¿½n
            $sql = "SELECT Vnd_Cod,vendedor.Pun_Cod,puntos_imp.Pun_Des 
                    FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND puntos_imp.Pun_Est='A'";
            break;
        case 8://Select para listar los tipos de comprobantes de Tic_Sr1=0,1,2,41,44,47,48,49,50,51,52
            $where_doc="Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
            if(isset($Par_Sql[2])){$where_doc="Tic_Sri='4' OR Tic_Sri='5'";}
            if(isset($Par_Sql[1])&&($Par_Sql[1])!=0){
                $where="autorizaci.Aut_Cod='$Par_Sql[1]'";
            }  else {
                $where="autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A' AND autorizaci.Tic_Cod='32'";
            }
                
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE ($where_doc) AND $where ";
            break;
        case 9://Select para obtener los datos de la autorizacion segun los datos del vendedor
            $sql = "SELECT vendedor.Vnd_Cod,puntos_imp.Pun_Cod,vendedor.Prs_Cod,autorizaci.Aut_Cod,Aut_Sri,Aut_Fci,Aut_Cad,Aut_Ini,Aut_Fin,Vet_Cod,Pun_Sri,CURDATE() AS Fec_Sys
                    FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN autorizaci ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    LEFT JOIN ventas ON autorizaci.Aut_Cod=ventas.Aut_Cod
                    WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND autorizaci.Tic_Cod='$Par_Sql[2]'  AND autorizaci.Aut_Cod='$Par_Sql[3]'";
                //ChromePhp::log($sql);   
            break;
        case 10://Select para obtener todos los nï¿½meros de secuencia correspondientes a un Aut_Cod
            $sql = "SELECT
                        CASE
                            WHEN MAX(Vet_Num)IS NOT NULL AND MAX(Vet_Num)>=$Par_Sql[1] THEN (
                                SELECT MIN(t.Vet_Num)+1
                                FROM ventas t
                                INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                                INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                                WHERE tp.Suc_Cod=$Par_Sql[4] AND ta.Aut_Sri='$Par_Sql[2]' AND ta.Tic_Cod=$Par_Sql[3] AND ta.Pun_Sri='$Par_Sql[5]' AND t.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1] AND
                                NOT EXISTS (
                                    SELECT NULL FROM ventas n
                                        INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                        INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                        WHERE n.Vet_Num=t.Vet_Num+1 AND np.Suc_Cod=$Par_Sql[4] AND na.Aut_Sri='$Par_Sql[2]' AND na.Pun_Sri='$Par_Sql[5]' AND na.Tic_Cod=$Par_Sql[3] AND n.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]
                                    )
                               )
                        ELSE IFNULL(MAX(Vet_Num),$Par_Sql[0]-1)+1
                        END AS siguiente,count(Vet_Num) as contador, autorizaci.Aut_Tem 
                    FROM ventas
                    INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE Suc_Cod=$Par_Sql[4] AND autorizaci.Aut_Sri='$Par_Sql[2] ' AND autorizaci.Pun_Sri='$Par_Sql[5]' AND autorizaci.Tic_Cod=$Par_Sql[3] AND Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]";
            break;
        case 11://Select para comprobar si el numero de secuencia ya se encuentra registrado
            $sql = "SELECT COUNT(Vet_Cod)AS total FROM ventas
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = ventas.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND autorizaci.Pun_Sri='$Par_Sql[4]' AND Suc_Cod='$Par_Sql[0]' AND Vet_Num='$Par_Sql[2]'".(!empty($Par_Sql[3])?"AND ventas.Vet_Cod<>$Par_Sql[3]":'').';';
            break;
        case 12:
            $sql="SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            break;
        case 13://Con esta sentencia consulto producto y stock
            if($Par_Sql[3]=='') $campos=" COUNT(item.Ite_Cod) AS total ";
            else $campos="prec.Pre_Est, prec.Pre_Fin, prec.Pre_Ini, prec.Pre_Pvp, prec.Pre_Cod, prec.Pre_Des, tipo_preci.Tpv_Cod, item.Ite_Cod,item.Ite_Est,ice.Ice_Int,categorias.Cat_Cod,categorias.Cat_Des,
            item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,Adq_Cor,adquisicio.Adq_Des,iva.Iva_Cod,
            iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,
            producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,Stk_Can,Ice_Por,tipo_preci.Tpv_Des,
            prec.Pre_Pvp as Vet_Pru";
            if($Par_Sql[2]=='c') $search=" producto.Pro_Bar='$Par_Sql[0]' ";
            //else if($Par_Sql[2]=='d') $search=" ( UPPER(item.Ite_Lar) LIKE UPPER('%$Par_Sql[0]%') OR UPPER(producto.Pro_Obs) LIKE UPPER('%$Par_Sql[0]%')  ) ";    
                else{
                    $search=""; 
                    $array=explode(" ",strtoupper($Par_Sql[0]));
                    foreach($array as $ar){
                        if(!empty($ar) && $ar!='') $search.=(($search!=''?" AND ":"")."CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE '%$ar%'");                    
                    }
                    if($search=='') $search="1=1";                    
                }
            $sql= "SELECT
                    $campos
                  FROM
                    categorias
                    INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                    INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                    INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                    INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                    INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                    INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
                    INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                    LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
                    INNER JOIN precios AS prec ON prec.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND prec.Pro_Cod=producto.Pro_Cod AND prec.Pre_Est='A'
                    INNER JOIN tipo_preci ON prec.Tpv_Cod = tipo_preci.Tpv_Cod                  
                  WHERE $search AND Pro_Est='A' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
                  //ChromePhp::log($sql);
            break;        
        case 14://Select para obtener el precio de los productos
            $sql="SELECT Pre_Cod,Pre_Pvp,Tpv_Des,Pre_Des,Pre_Est,precios.Tpv_Cod,Pre_Ini,Pre_Fin FROM precios INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod WHERE precios.Suc_Cod='$Par_Sql[0]' AND Pro_Cod='$Par_Sql[1]' AND Pre_Est='$Par_Sql[2]' ".(empty($Par_Sql[3])?'':"AND Tpv_Def='D'")." ".(empty($Par_Sql[4])?'':"(('$Par_Sql[4]' BETWEEN Pre_Ini AND Pre_Fin) OR (Pre_Ini IS NULL AND Pre_Fin IS NULL) OR (Pre_Ini='0000-00-00' AND Pre_Fin='0000-00-00'))").";";
            //ChromePhp::log($sql);
            break;
        case 15://busca cuenta relacion producto
            $sql = "SELECT Pro_Cod,produ_plan.Pld_Cod,Tip_Pld,Pld_Cdc,Pld_Des,Pla_Cod FROM produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pro_Cod=$Par_Sql[1] AND (Tip_Pld='$Par_Sql[2]' OR Tip_Pld='I') AND Pla_Cod=$Par_Sql[0]";
            break;
        case 16://Select para obtener los datos de la tabla iva, cuyos porcentajes sean mayor a cero y se encuentren activos
            $sql = "SELECT * FROM iva WHERE Iva_Por>0 ORDER BY Iva_Ini DESC";
            break;
        case 17://Select para obtener los tipos de pago de la tabla tipos_pago
            $sql = "SELECT * FROM tipos_pago WHERE Pag_Est='A'";
            break;
        case 18: //Select obtener el listado de bancos de la tabla del mismo nombre
            $sql="SELECT * FROM bancos;";
            break;

            //CONSULTAS PARA VALIDAR EL CLIENTE EN EL AGREGAR 
        case 177://Select para obtener los datos de una persona segï¿½n su cï¿½dula
            $sql="SELECT persona.* FROM persona WHERE Prs_Ced LIKE '$Par_Sql[0]%'";
            return $sql;
            break;
        case 188://Select para comprobar si el cliente ya se encuentra registrado
            $sql="SELECT Cli_Cod FROM cliente WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";
            return $sql;
            break;
        case 299://Select para obtener la lista de identificaciones
            $sql="SELECT *,IF(ISNULL(Ide_Pre),'Ec','Ex') AS Tipo FROM identifica WHERE Ide_Est='A'";
            return $sql;
        break;

        case 19: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla banco 'CONTADO'
            $forma_pago="and For_Cod=1";
            if(!empty($Par_Sql[2])){
                $forma_pago="and For_Cod=$Par_Sql[2]";
            }
            
            
            $sql = "SELECT distinct banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des,Ban_Tip,if(banco.Ban_Tip='B','si','no') AS banco, if(banco.Ban_Tip='C',1,0) as Pag_Cod  FROM banco, det_plan, pago_plan, plan_cuenta,tipos_pago
                    WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    AND plan_cuenta.Pla_Cod='$Par_Sql[0]' $forma_pago AND Emp_Cod='$Par_Sql[1]' AND (banco.Ban_Tip='B' OR banco.Ban_Tip='C' ) AND pago_plan.Pag_Est='A' ORDER BY Pld_Cdc, Pld_Des";
            break;


         case 1111111: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla banco 'CONTADO'
            $sql = "SELECT pago_plan.Pag_Cod,banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des,Ban_Tip,'si' AS banco FROM banco, det_plan, pago_plan, plan_cuenta
                    WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    AND plan_cuenta.Pla_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND banco.Ban_Tip='B' AND pago_plan.Pag_Est='A' ORDER BY Pld_Cdc, Pld_Des";
            break;    
         
        case 20: //Select para obtener el listado de las cuentas contables (Pld_Cod) de la tabla tipo_param ' correspondiente a EFECTIVO Y CHEQUE'
            $sql = "SELECT tipo_param.Tpa_Cod,det_plan.Pld_Cod,Pld_Des,Tpa_Abr,IF(tipo_param.Tpa_Cod=16,1,3) AS Pag_Cod,'no' AS banco FROM tipo_param
                    INNER JOIN plan_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    INNER JOIN det_plan ON plan_param.Pld_Cod=det_plan.Pld_Cod
                    WHERE (Tpa_Abr='CBA' OR Tpa_Abr='CCH') and det_plan.Pla_Cod=$Par_Sql[0] and plan_param.Ppc_Est='A'" ;
            break;
        case 21://Select para listar los porcentajes de impuesto a la renta
            if(empty($Par_Sql['limits'])) $campos="COUNT(renta_iva.Ren_Cod) AS total"; else $campos="Adq_Cod,renta_iva.Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,renta_iva.Ren_Tip,if(renta_iva.Ren_Tip='B','BIENES','SERVICIO')as Ren_Tipo,Ren_Ret,if(Ren_Ret='R','RENTA','IVA')as Ren_Rete,Ren_Est,if(Ren_Est='A','Activo','Anulado')as Ren_Esta";
            if($Par_Sql['op_opciones']=='d') $where="(Ren_Con LIKE '$Par_Sql[search]%' OR Ren_Con LIKE '%$Par_Sql[search]%')";
            else if($Par_Sql['op_opciones']=='c') $where="Ren_Sri LIKE '$Par_Sql[search]%'";
                 else{  if(!empty($Par_Sql['search'])) $where="Ren_Por = '$Par_Sql[search]'"; else $where=""; }
            $sql= "SELECT $campos FROM renta_iva WHERE Ren_Est='A' AND Ren_Ret='$Par_Sql[tipo]'".(!empty($where)?"AND $where ":'').(!empty($Par_Sql['limits'])?" ORDER BY Ren_Sri ASC $Par_Sql[limits];":';');
            break;
        case 22://Select para presentar cuentas de impuesto a la renta
            $sql = "SELECT reniva_pla.Pld_Cod, Pld_Cdc, Pld_Des FROM reniva_pla INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod WHERE Ren_Cod='$Par_Sql[1]' AND det_plan.Pla_Cod='$Par_Sql[0]' AND Ren_Tip='$Par_Sql[2]'";
            break;
        case 23://Insert sobre la tabla venta
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod)
                    VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]',
                       '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]',
                        ".(empty($Par_Sql[10])?'NULL':"'$Par_Sql[10]'").",
                        ".(empty($Par_Sql[11])?'NULL':"'$Par_Sql[11]'").",
                        ".(!empty($Par_Sql[12])?"'$Par_Sql[12]'":"NULL").",
                        ".(!empty($Par_Sql[13])?"'$Par_Sql[13]'":"NULL").",
                        ".(!empty($Par_Sql[14])?"'$Par_Sql[14]'":"NULL").",
                        ".(!empty($Par_Sql[15])?"$Par_Sql[15]":"NULL").")";
            break;

        case 24:
            /* cuentas credito */
            $sql = "SELECT DISTINCT det_plan.* FROM ccpp_prove
                    INNER JOIN asientos ON asientos.Pld_Cod=ccpp_prove.Pld_Cod
                    INNER JOIN det_plan ON det_plan.Pld_Cod=ccpp_prove.Pld_Cod
                    WHERE Com_Cod=$Par_Sql[0] AND Asi_Deh='H' ";
            //echo $sql."<br>";
            break;
        case 25:
            /* cuentas credito */
            if(empty($Par_Sql[6]))
                $sql = "INSERT INTO det_ccpp_p(Cpp_Cod,Pag_Cod,Com_Cod,Pag_Fec,Pag_Val,Pag_Est,Pag_Obs) VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]','$Par_Sql[4]','A','$Par_Sql[5]')";
            else
                $sql = "UPDATE det_ccpp_p SET Pag_Cod=$Par_Sql[1],Com_Cod=$Par_Sql[2],Pag_Fec='$Par_Sql[3]',Pag_Val='$Par_Sql[4]',Pag_Est='A',Pag_Obs='$Par_Sql[5]' WHERE Com_Cod=$Par_Sql[6] AND Cpp_Cod=$Par_Sql[0]";
            //echo $sql."<br>";
            break;
        case 26:
            /* detalle compra */
            $sql = "SELECT det_compra.*,Ite_Lar FROM det_compra
                    INNER JOIN producto ON producto.Pro_Cod=det_compra.Pro_Cod
                    INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                    WHERE Cop_Cod=$Par_Sql[0] ORDER BY Cop_Int";
            //echo $sql."<br>";
            break;
        case 27:
            /* detalle asiento */
            $sql="SELECT Asi_Cod,Asi_Deh,Pld_Cdc,Pld_Des,Asi_Glo as Glosa,Asi_Val,IF(Asi_Deh='D',Asi_Val,'') AS Debe,IF(Asi_Deh='H',Asi_Val,'') AS Haber FROM asientos INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod WHERE Com_Cod='$Par_Sql[0]' ORDER BY Asi_Deh";
            //echo $sql."<br>";
            break;
        case 28: // usado
            /* cuenta descuentos */
            $sql="SELECT plan_param.Pld_Cod,Pld_Cdc,Pld_Des,Pla_Cod FROM plan_param
                    INNER JOIN det_plan ON det_plan.Pld_Cod=plan_param.Pld_Cod
                    INNER JOIN tipo_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    WHERE Tpa_Abr='$Par_Sql[1]' AND Pla_Cod=$Par_Sql[0];";
            //echo $sql."<br>";
            break;
        case 29: // usado
            /* identificacion */
            $sql="SELECT * FROM identifica WHERE Ide_Prc IS NOT NULL AND Ide_Prc<>'';";
            //echo $sql."<br>";
            break;
        case 30:
            $sql="SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
            break;
        case 31: // usado
            if(empty($Par_Sql[9]))
                $sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'";//Antes Com_Tip
            
            break;
            
        case 32:
            if(empty($Par_Sql['limits'])){ 
                $campos="COUNT(ventas.Vet_Cod) AS total";         
            }else{
                $campos="ventas.*,
                vende.Prs_Ape,
                vende.Prs_Nom,
                ciudad.Ciu_Des,
                Tic_Des,
                ventas_compr.Com_Cod,
                tipo_compr.Tic_Sri,
                ccpp_cobrar.Cpc_Cod,
                tipopagocom.*,
                Caj_Fec as Vet_Fec,
                concat(vende.Prs_Ape,' ',vende.Prs_Nom)as vendedor_per,
                concat(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)as cliente_per,
                cliente_ven.Prs_Ced,
                comprobantes.Pec_Cod,
                if(ccpp_cobrar.Cpc_Cod is null,'Contado','Credito')as Pago,
                if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
            }
            $Par_Sql['Tic_Cod']=(!empty($Par_Sql['Tic_Cod'])?"AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]":"AND (tipo_compr.Tic_Sri=4 OR tipo_compr.Tic_Sri=5 )");
            if($Par_Sql['op_opciones']=='d'){
                $search="AND ventas.Vet_Num = '$Par_Sql[search]'"; $Par_Sql['Cmb_Mes']=$Par_Sql['Pec_Cod']='';
            }else{
                $Par_Sql['Cmb_Mes']=(!empty($Par_Sql['Pec_Cod'])&&!empty($Par_Sql['Cmb_Mes'])?"AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]":'');
                $Par_Sql['Pec_Cod']=(!empty($Par_Sql['Pec_Cod'])?"AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'":'');
                if($Par_Sql['op_opciones']=='c')
                    $search="AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search="AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }
            $sql="SELECT $campos FROM ventas
                  INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                  INNER JOIN persona as vende ON vendedor.Prs_Cod = vende.Prs_Cod
                  left join ventas_compr on ventas_compr.Vet_Cod=ventas.Vet_Cod
                  inner join cliente on cliente.Cli_Cod= ventas.Cli_Cod
                  INNER JOIN persona as cliente_ven ON cliente_ven.Prs_Cod = cliente.Prs_Cod
                  left join ccpp_cobrar on ccpp_cobrar.Vet_Cod=ventas.Vet_Cod
                  INNER JOIN ciudad ON ciudad.Ciu_Cod = ventas.Ciu_Cod
                  left join tipopagocom on tipopagocom.Tpc_Cod = ventas.Tpc_Cod
                  left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod AND comprobantes.Com_Est='A'
                  left join autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                  INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                  inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                WHERE ventas.Vet_Est<>'E'  AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $search
                $Par_Sql[order] $Par_Sql[limits] ;";
            //echo $sql.'<br/>';
            break;            
        case 33:

            $sql="SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
            break;

        case 34:
            $where_doc="Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
            
            if(empty($Par_Sql['limits'])){ 
                $campos="COUNT(ventas.Vet_Cod) AS total";         
            }else{
                $campos="ventas.*,
                vende.Prs_Ape,
                vende.Prs_Nom,
                ROUND(pago_venta.Vet_Tot,2) AS total_,
                ROUND((   ventas_det.Vet_Can * ventas_det.Vet_Pru),2) As total_,
              
                ROUND((pago_venta.Vet_Tot /((iva.Iva_Por/100)+1)),2) AS subtotal_,
                ROUND((ventas_det.Vet_Can * ventas_det.Vet_Pru),2) As subtotal,
                
                (ROUND(pago_venta.Vet_Tot,2) - ROUND((pago_venta.Vet_Tot /((iva.Iva_Por/100)+1) ),2)) AS impuesto_,
                ROUND((ventas_det.Vet_Can * ventas_det.Vet_Pru) * (iva.Iva_Por/100) ,2) AS impuesto,

                ROUND( ((ventas_det.Vet_Can * ventas_det.Vet_Pru) + (ventas_det.Vet_Can * ventas_det.Vet_Pru) * (iva.Iva_Por/100)),2) As total,

                ciudad.Ciu_Des,
                Tic_Des,Emp_Cod,
                ventas_compr.Com_Cod,
                tipo_compr.Tic_Sri,
                ccpp_cobrar.Cpc_Cod,
                tipopagocom.*,
                Caj_Fec as Vet_Fec,
                concat(vende.Prs_Ape,' ',vende.Prs_Nom)as vendedor_per,
                concat(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)as cliente_per,
                cliente_ven.Prs_Ced,
                comprobantes.Pec_Cod,
                forma_pago.For_Des AS Pago,
                if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                ventas_compr.Com_Cod, ROUND(ventas_det.Vet_Can,2) AS Vet_Can, Ite_Lar, Vet_Pru,
                if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
            }
            $Par_Sql['Tic_Cod']=(!empty($Par_Sql['Tic_Cod'])?"AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]":'');
            if($Par_Sql['op_opciones']=='d'){
                $search="AND ventas.Vet_Num = '$Par_Sql[search]'"; $Par_Sql['Cmb_Mes']=$Par_Sql['Pec_Cod']='';
            }else{
                $Par_Sql['Cmb_Mes']=(!empty($Par_Sql['Pec_Cod'])&&!empty($Par_Sql['Cmb_Mes'])?"AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]":'');
                $Par_Sql['Pec_Cod']=(!empty($Par_Sql['Pec_Cod'])?"AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'":'');
                if($Par_Sql['op_opciones']=='c')
                    $search="AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search="AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }
            //ChromePhp::log('MIS_INGRESOS',$Par_Sql['mis_ingresos']);
            
            if(isset($Par_Sql["mis_ingresos"])){
                if($Par_Sql["mis_ingresos"] == 'S'){
                    $filtroUsuario = "AND vendedor.Prs_cod = $_SESSION[Ses_Prs_Cod]";
                }    
            }else{
                $filtroUsuario = '';
            }
            if($Par_Sql['estado']!=""){
                $facturada = "AND ventas.Vet_Est='$Par_Sql[estado]'";
            }else{
                $facturada = "AND ventas.Vet_Est!='I'";
            }
            $sql="SELECT $campos FROM ventas
                  INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                  INNER JOIN persona as vende ON vendedor.Prs_Cod = vende.Prs_Cod
                  left join ventas_compr on ventas_compr.Vet_Cod=ventas.Vet_Cod
                  inner join cliente on cliente.Cli_Cod= ventas.Cli_Cod
                  INNER JOIN persona as cliente_ven ON cliente_ven.Prs_Cod = cliente.Prs_Cod
                  left join ccpp_cobrar on ccpp_cobrar.Vet_Cod=ventas.Vet_Cod
                  INNER JOIN ciudad ON ciudad.Ciu_Cod = ventas.Ciu_Cod
                  left join tipopagocom on tipopagocom.Tpc_Cod = ventas.Tpc_Cod
                  left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod AND comprobantes.Com_Est='A'
                  INNER JOIN autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                  INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod AND puntos_imp.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                  INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                  inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                  INNER JOIN pago_venta on pago_venta.Vet_cod =ventas.Vet_Cod
                  INNER JOIN tipos_pago on pago_venta.Pag_Cod = tipos_pago.Pag_Cod
                  INNER JOIN forma_pago on tipos_pago.For_Cod = forma_pago.For_Cod
                  INNER JOIN ventas_det on ventas.Vet_Cod = ventas_det.Vet_Cod
                  INNER JOIN iva ON ventas_det.Iva_Cod = iva.Iva_Cod
                  inner join producto on ventas_det.Pro_Cod = producto.Pro_Cod
                  INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
                WHERE ($where_doc) $facturada AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $filtroUsuario $search 
                $Par_Sql[order] $Par_Sql[limits] ;";
            //ChromePhp::log($sql);
            break;
        case 35:
            $sql="SELECT Cop_Int,Cop_Int AS 'index', det_compra.Pro_Cod,Ice_Int,det_compra.Iva_Cod,Iva_Por,Iva_Sri,Cop_Pro AS Ite_Lar,Cop_Can,Cop_Pru,Cop_Imp,Cop_Dec,det_compra.Adq_Cod,Adq_Des,Adq_Cor,det_plan.Pld_Cod, Pld_Cdc,Pld_Des,Uni_Des,Iva_Cos FROM det_compra
                INNER JOIN producto ON producto.Pro_Cod=det_compra.Pro_Cod
                INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
                INNER JOIN iva ON iva.Iva_Cod=det_compra.Iva_Cod
                INNER JOIN adquisicio ON adquisicio.Adq_Cod=det_compra.Adq_Cod
                LEFT JOIN det_plan ON det_plan.Pld_Cod=det_compra.Pld_Cod
                WHERE Cop_Cod=$Par_Sql[0] ORDER BY Cop_Int;";
            //echo $sql.'<br/>';
            break;
        case 36:
            $sql="SELECT iva_pagado.Pld_Cod FROM asientos INNER JOIN iva_pagado ON asientos.Pld_Cod=iva_pagado.Pld_Cod WHERE Com_Cod= $Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 37:
            if(($Par_Sql[1])!=0){
                $where="autorizaci.Aut_Cod='$Par_Sql[1]'";
            }  else {
                $where="autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A'";
            }
                
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='5' OR Tic_Sri='4') AND $where ";
            break;
        case 38:
            $sql="SELECT COUNT(Cop_Cod)AS total  FROM kardex_ie WHERE Cop_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 39:
            $sql="SELECT asientos.Pld_Cod FROM asientos INNER JOIN banco ON banco.Pld_Cod=asientos.Pld_Cod WHERE Com_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
            break;
        case 40:
            $sql="SELECT Cop_Fec,Cop_Sec, Com_Fec, Com_Num FROM compras LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod INNER JOIN comprobantes ON comprobantes.Com_Cod=compr_auto.Com_Cod WHERE compras.Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 41:
            $sql="DELETE FROM asientos WHERE Com_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 42:
            $sql="DELETE  FROM det_compra WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 43:
            $sql="SELECT * FROM kardex_ie WHERE Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 44:
            $sql="DELETE  FROM kardex_ie WHERE Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 45: // usado
            $sql="SELECT Tpc_Cod,Tpc_Sri,Tpc_Des FROM tipopagocom WHERE Tpc_Sri='01' OR Tpc_Sri='15' OR Tpc_Sri='16' OR Tpc_Sri='17' OR Tpc_Sri='18' OR Tpc_Sri='19' OR Tpc_Sri='20' OR Tpc_Sri='21' AND  Tpc_Est='A'";
            //echo $sql.'<br/>';
        //ChromePhp::log($sql);
            break;
        case 46:
            $sql="DELETE  FROM det_ccpp_p WHERE Com_Cod='$Par_Sql[0]' ";
            //echo $sql.'<br/>';
            break;
        case 47:
            if(empty($Par_Sql['limits'])) $campos="COUNT(renta_iva.Ren_Cod) AS total"; else $campos="Adq_Cod,renta_iva.Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,renta_iva.Ren_Tip,if(renta_iva.Ren_Tip='B','BIENES','SERVICIO')as Ren_Tipo,Ren_Ret,if(Ren_Ret='R','RENTA','IVA')as Ren_Rete,Ren_Est,if(Ren_Est='A','Activo','Anulado')as Ren_Esta";
            if($Par_Sql['op_opciones']=='d') $where="(Ren_Con LIKE '$Par_Sql[search]%' OR Ren_Con LIKE '%$Par_Sql[search]%')";
            else if($Par_Sql['op_opciones']=='c') $where="Ren_Sri LIKE '$Par_Sql[search]%'";
                 else{  if(!empty($Par_Sql[search])) $where="Ren_Por = '$Par_Sql[search]'"; else $where=""; }
            $sql= "SELECT $campos FROM renta_iva WHERE Ren_Est='A' AND Ren_Ret='$Par_Sql[tipo]'".(!empty($where)?"AND $where ":'').(!empty($Par_Sql['limits'])?" ORDER BY Ren_Sri ASC $Par_Sql[limits];":';');
            //echo $sql.'<br/>';
            break;
        case 48:
            $sql="SELECT autorizaci.* FROM autorizaci
             WHERE autorizaci.Pun_Cod=$Par_Sql[0] AND autorizaci.Tic_Cod=$Par_Sql[1] AND autorizaci.Aut_Est = 'A'";
            //echo $sql.'<br/>';
            break;
        case 49: // usado
            $sql= "SELECT empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
                    sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
            FROM empresas
                    INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                    INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                    WHERE sucursal.Suc_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 50: // usado
            $sql="SELECT COUNT(Vet_Cod)AS total FROM ventas
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = ventas.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND Suc_Cod='$Par_Sql[0]' AND autorizaci.Pun_Sri=$Par_Sql[4]  AND Vet_Num='$Par_Sql[2]'".(!empty($Par_Sql[3])?"AND ventas.Vet_Cod<>$Par_Sql[3]":'').';';
            break;
        case 51: // usado
            $sql="SELECT
                    CASE
                        WHEN MAX(Vet_Num)IS NOT NULL AND MAX(Vet_Num)>=$Par_Sql[3] THEN (
                            SELECT MIN(t.Vet_Num)+1
                            FROM ventas t
                            INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                            INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                            WHERE tp.Suc_Cod=$Par_Sql[0] AND ta.Aut_Sri='$Par_Sql[1]' AND ta.Tic_Cod=$Par_Sql[4] AND t.Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3] AND
                            NOT EXISTS (
                                SELECT NULL FROM ventas n
                                    INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                    INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                    WHERE n.Vet_Num=t.Vet_Num+1 AND np.Suc_Cod=$Par_Sql[0] AND na.Aut_Sri='$Par_Sql[1]' AND na.Tic_Cod=$Par_Sql[4] AND n.Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3]
                                )
                           )
                        ELSE IFNULL(MAX(Vet_Num),$Par_Sql[2]-1)+1
                        END AS 'next'
                FROM ventas
                INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                WHERE Suc_Cod=$Par_Sql[0] AND autorizaci.Aut_Sri='$Par_Sql[1]' AND autorizaci.Tic_Cod=$Par_Sql[4] AND Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3]";
            //echo $sql.'<br/>';
            break;
        case 52:
            $sql="SELECT det_plan.Pld_Des, det_plan.Pld_Cdc, reniva_pla.Pld_Cod, reniva_pla.Ren_Cod FROM det_plan
                INNER JOIN reniva_pla ON (det_plan.Pld_Cod = reniva_pla.Pld_Cod)
                INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                WHERE reniva_pla.Ren_Cod='$Par_Sql[1]' AND reniva_pla.Ren_Tip='$Par_Sql[2]' AND plan_cuenta.Pla_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';

            break;
        case 53:
            if(empty($Par_Sql[11]))
                $sql="INSERT INTO retencion(Cop_Cod, Ret_Num, Ret_Fec, Ret_Con, Tic_Cod, Vnd_Cod, Aut_Cod, Ret_Xml, Ret_Asu, Ret_Uca, Ret_Pca) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'), $Par_Sql[4], $Par_Sql[5], $Par_Sql[6],'$Par_Sql[7]','$Par_Sql[8]',".(empty($Par_Sql[9])?'NULL':$Par_Sql[9]).",".(empty($Par_Sql[10])?'NULL':$Par_Sql[10]).")";
            else
                $sql="UPDATE retencion SET Ret_Num='$Par_Sql[1]', Ret_Fec='$Par_Sql[2]', Ret_Con=UPPER('$Par_Sql[3]'), Tic_Cod=$Par_Sql[4], Vnd_Cod=$Par_Sql[5], Aut_Cod=$Par_Sql[6], Ret_Xml='$Par_Sql[7]', Ret_Asu='$Par_Sql[8]', Ret_Uca=".(empty($Par_Sql[9])?'NULL':$Par_Sql[9]).", Ret_Pca=".(empty($Par_Sql[10])?'NULL':$Par_Sql[10])." WHERE Cop_Cod=$Par_Sql[0] AND Ret_Cod=$Par_Sql[11];";
            //echo $sql.'<br/>';
            break;
         case 54:
            $sql="INSERT INTO det_retenc(Ret_Cod,Ret_Bas, Ren_Cod, Ret_Imp, Ret_Int, Adq_Cod)
                    VALUES($Par_Sql[0],'$Par_Sql[1]',$Par_Sql[2],UPPER('$Par_Sql[3]'),'$Par_Sql[4]', $Par_Sql[5])";
            //echo $sql.'<br/>';
            break;
        case 55: // usado
            $fecha_venta = "Cpc_Ven='$Par_Sql[2]', ";
            if($Par_Sql[2]===''){
                $fecha_venta='';
            }
            if(empty($Par_Sql[4]))
                $sql = "INSERT INTO ccpp_cobrar(Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'));";
            else
                $sql = "UPDATE ccpp_cobrar SET $fecha_venta Cpc_Obs=UPPER('$Par_Sql[3]') WHERE Cpc_Cod='$Par_Sql[4]' AND Vet_Cod=$Par_Sql[1] AND Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 56:
             $sql="SELECT
                    sum(
                          (
                      (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */
                          +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0)) /* ICE */
                          ) *(1+iva.Iva_Por/100)    /* IVA */
                    ) AS total
                  FROM compras
                    INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                    INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
                    LEFT JOIN ice ON (ice.Ice_int=det_compra.Ice_Int)
                    INNER JOIN tipo_compr ON (tipo_compr.Tic_Cod = compras.Tic_Cod)
                  WHERE
                     compras.Prv_Cod = '$Par_Sql[0]' AND Tic_Sri='$Par_Sql[1]'
                     AND Cop_Fec BETWEEN '$Par_Sql[2] 00:00:00' AND '$Par_Sql[3] 23:59:59'
                  GROUP BY compras.Prv_Cod";
            //echo $sql.'<br/>';
            break;
        case 57:
            $sql = "SELECT ".(!empty($Par_Sql[1])?"SUM(det_ccpp_c.Cpc_Val)":"COUNT(det_ccpp_c.Cpc_Cod)")."AS total FROM det_ccpp_c INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod WHERE Cpc_Cod='$Par_Sql[0]' ".(!empty($Par_Sql[1])?"AND Cpc_Est='$Par_Sql[1]' AND Com_Est='A'":'').";";
            //echo $sql.'<br/>';
            break;
        case 58:
            $sql = "SELECT COUNT(Cop_Cod)AS total FROM det_reposicion WHERE Cop_Cod='$Par_Sql[0]' AND Dre_Tip!='P'";
            //echo $sql.'<br/>';
            break;
        case 59:
            $sql = "SELECT Ret_Int,det_retenc.Ren_Cod,Ren_Por,Ren_Con,Ren_Sri,Ret_Imp AS Ren_Ret FROM det_retenc INNER JOIN renta_iva ON renta_iva.Ren_Cod=det_retenc.Ren_Cod WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 60:
            $sql = "SELECT reniva_pla.Pld_Cod, Pld_Cdc, Pld_Des FROM reniva_pla INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod WHERE Ren_Cod='$Par_Sql[1]' AND det_plan.Pla_Cod='$Par_Sql[0]' AND Ren_Tip='$Par_Sql[2]'";
            //echo $sql.'<br/>';
            break;
        case 61: // usado
            $sql = "SELECT autorizaci.Aut_Sri, autorizaci.Pun_Sri, sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin FROM puntos_imp INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod) INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod) WHERE autorizaci.Aut_Cod ='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 62:
            $sql = "DELETE  FROM det_retenc WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 63:
            $sql = "DELETE  FROM retencion WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 64:
            $sql = "DELETE  FROM ccpp_pagar WHERE Cpp_Cod='$Par_Sql[2]' AND Cop_Cod='$Par_Sql[1]' AND Com_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 65:
            $sql = "SELECT COUNT(Vet_Cod)AS total FROM ventas_compr WHERE Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 66:
            $sql = "SELECT modi.Tic_Cod AS Mod_Tic_Cod, modi.Tic_Des AS Mod_Tic_Des,modicomp.Cop_Fec AS Mod_Cop_Fec FROM compras AS modicomp
                    INNER JOIN tipo_compr AS modi ON (modi.Tic_Cod=modicomp.Tic_Cod)
                    WHERE modicomp.Cop_Num='$Par_Sql[1]' AND modicomp.Prv_Cod='$Par_Sql[0]' AND modi.Tic_Sri='$Par_Sql[2]' AND modicomp.Cop_Est='A';";
            //echo $sql.'<br/>';
            break;
        case 67:
            $sql="SELECT plan_param.Pld_Cod,Pld_Des,Pld_Est FROM plan_param
                    INNER JOIN det_plan ON plan_param.Pld_Cod=det_plan.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    INNER JOIN tipo_param ON plan_param.Tpa_Cod=tipo_param.Tpa_Cod
                    WHERE Tpa_Abr='$Par_Sql[1]' AND Emp_Cod='$Par_Sql[0]' AND Pld_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 68: // usado
            $sql="SELECT * FROM iva WHERE Iva_Por='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 69: // usado
            $sql="SELECT * FROM tipos_pago ORDER BY Pag_Cod;";
            //echo $sql.'<br/>';
            break;
        case 70: // usado
            if(!isset($Par_Sql[9])||empty($Par_Sql[9]))
                $sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'";//Antes Com_Tip
            else
                $sql="UPDATE comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' WHERE Com_Cod=$Par_Sql[9] ";
            //echo $sql."<br>";
            break;
        
        case 71: // usado
            $sql="SELECT banco.*,Pld_Des FROM banco
                    INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    WHERE Ban_Cue!=0 AND Ban_Cue!='' AND Ban_Est='A' AND Emp_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        
    case 72: // usado
            $sql="INSERT INTO pago_venta (Vet_Cod, Bak_Cod, Ban_Cod, Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num,Pld_Cod)
            VALUES ($Par_Sql[Vet_Cod],".(empty($Par_Sql['Bak_Cod'])?'1':$Par_Sql['Bak_Cod']).",".(empty($Par_Sql['Ban_Cod'])?'NULL':$Par_Sql['Ban_Cod']). "," . $Par_Sql['Tipo_Cod'] .",".(empty($Par_Sql['Vet_Cue'])?'NULL':"'$Par_Sql[Vet_Cue]'").", ".(empty($Par_Sql['Vet_Che'])?'NULL':"'$Par_Sql[Vet_Che]'").", $Par_Sql[Vet_Tot], '$Par_Sql[Vet_Num]',".(empty($Par_Sql['Pag_Pld'])?'NULL':"'$Par_Sql[Pag_Pld]'").")";

            break;
        case 73: // usado
            $sql="SELECT Pre_Cod,Pre_Pvp,Pre_Des,Pre_Est,precios.Tpv_Cod,Pre_Ini,Pre_Fin FROM precios INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod WHERE precios.Suc_Cod='$Par_Sql[0]' AND Pro_Cod='$Par_Sql[1]' AND Pre_Est='$Par_Sql[2]' ".(empty($Par_Sql[3])?'':"AND Tpv_Def='D'")." ".(empty($Par_Sql[4])?'':"(('$Par_Sql[4]' BETWEEN Pre_Ini AND Pre_Fin) OR (Pre_Ini IS NULL AND Pre_Fin IS NULL) OR (Pre_Ini='0000-00-00' AND Pre_Fin='0000-00-00'))").";";
            //echo $sql.'<br/>';
            break;
        case 74: // usado
            $sql="SELECT * FROM tipo_preci WHERE Suc_Cod='$Par_Sql[0]' AND Tpv_Est='A';";
            //echo $sql.'<br/>';
            break;
        case 75: // usado
            $sql="SELECT * FROM caja_aper WHERE Pun_Cod='$Par_Sql[0]' ".(empty($Par_Sql[1])?'':" AND Caj_Est='$Par_Sql[1]'")." ;";
            //echo $sql.'<br/>';
            break;

        /********** NUEVAS **********/

        case 76://SELECT para verificar si la caja ya fue creada dentro de la tabla caja_aper
            $sql = "SELECT Caj_Cod,Pun_Cod,Caj_Fec
                    FROM caja_aper
                    WHERE Pun_Cod='$Par_Sql[0]' AND Caj_Fec='$Par_Sql[1]'";
            break;
        case 77://INSERT en la tabla caja_aper, con el propï¿½sito de aperturar la caja este proceso es invisible para el usuario
            $sql = "INSERT INTO caja_aper(Pun_Cod,Caj_Fec,Caj_Hoi,Caj_Est,Caj_Gen)
                    VALUES('$Par_Sql[0]','$Par_Sql[1]',CURTIME(),'C','S')";
            break;

      case 78 :
          $sql="SELECT perio_cont.* FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
          //echo $sql;
          break;
      
      case 79:
            $sql="SELECT ventas_det.* , Ite_Lar FROM producto
            INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
            INNER JOIN ventas_det ON ventas_det.Pro_Cod = producto.Pro_Cod
            where Vet_Cod=$Par_Sql[0] order by Vet_Int";
          break;
        case 80:
            $sql="SELECT * FROM tipo_asien where Tia_Cod=$Par_Sql[0]";
            break;
        case 81:
            $sql="SELECT Ciu_Cod, Ciu_Des, Pro_Nom  FROM ciudad INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod WHERE Ciu_Des != ''  ORDER BY Ciu_Des ASC";
            //echo $sql;
            break;
        case 82:
            $sql="SELECT autorizaci.*,tipo_compr.*,Suc_Sri FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE Tic_Est='A' AND Aut_Est='A' AND Tic_Sri!='7' AND autorizaci.Pun_Cod='$Par_Sql[0]' AND '$Par_Sql[1]' BETWEEN Aut_Fci AND Aut_Cad;";
            //echo $sql;
            break;
        
        case 83: // usado
            /* Relaciona una venta y un comprobante para saber que es automatico */
            $sql = "INSERT INTO ventas_compr(Com_Cod, Vet_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
            //echo $sql."<br>";
            break;

        case 84: // usado
              /* busca cuenta relacion producto */
              $sql = "SELECT Pro_Cod,produ_plan.Pld_Cod,Tip_Pld,Pld_Cdc,Pld_Des,Pla_Cod FROM produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pro_Cod=$Par_Sql[1] AND (Tip_Pld='$Par_Sql[2]' OR Tip_Pld='I') AND Pla_Cod=$Par_Sql[0]";
              //echo $sql."<br>";
          //ChromePhp::log($sql);
              break;

        case 85: // usado
        $sql="SELECT * FROM vendedor
            INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
            WHERE Suc_Cod=$Par_Sql[0] AND Prs_Cod=$Par_Sql[1]";
        //echo $sql."<br>";
        break;

        case 86: // usado
            $sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[Vet_Cod], Pro_Cod=$Par_Sql[Pro_Cod], Vet_Can=$Par_Sql[Vet_Can],
            Iva_Cod=$Par_Sql[Iva_Cod], Vet_Pru=$Par_Sql[Vet_Pru], Vet_Imp=$Par_Sql[Vet_Imp], Vet_Dec='".(empty($Par_Sql['Vet_Dec'])?0:$Par_Sql['Vet_Dec'])."', Nge_Cod = '".(empty($Par_Sql['Nge_Cod'])?0:$Par_Sql['Nge_Cod'])."',
            Asi_Int='".(empty($Par_Sql['Asi_Int'])?0:$Par_Sql['Asi_Int'])."', Vet_Rec='".(empty($Par_Sql['Vet_Rec'])?0:$Par_Sql['Vet_Rec'])."', Cnt_Cod='".(empty($Par_Sql['Cnt_Cod'])?0:$Par_Sql['Cnt_Cod'])."', Vet_Int='".(empty($Par_Sql['Vet_Int'])?0:$Par_Sql['Vet_Int'])."', Vet_Uni='".(empty($Par_Sql['Vet_Uni'])||$Par_Sql['Vet_Uni']*1<=0?1:$Par_Sql['Vet_Uni'])."', Ren_Cod=".(empty($Par_Sql['Ret_Ren_Cod'])?'NULL':"'$Par_Sql[Ret_Ren_Cod]'").", Ren_Iva=".(empty($Par_Sql['Iva_Ren_Cod'])?'NULL':"'$Par_Sql[Iva_Ren_Cod]'").",Vet_Ite='$Par_Sql[Vet_Ite]', Vet_Ice='".(empty($Par_Sql['Ice_Por'])?0:$Par_Sql['Ice_Por'])."'";
            //echo $sql."<br>";
            break;

        case 866: // usado
            $sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[Vet_Cod], Pro_Cod=$Par_Sql[Pro_Cod], Vet_Can=$Par_Sql[Vet_Can],
            Iva_Cod=$Par_Sql[Iva_Cod], Vet_Pru=$Par_Sql[Vet_Pru], Vet_Imp=$Par_Sql[Vet_Imp], Vet_Dec='".(empty($Par_Sql['Vet_Dec'])?0:$Par_Sql['Vet_Dec'])."', Nge_Cod = '".(empty($Par_Sql['Nge_Cod'])?0:$Par_Sql['Nge_Cod'])."',
            Asi_Int='".(empty($Par_Sql['Asi_Int'])?0:$Par_Sql['Asi_Int'])."', Vet_Rec='".(empty($Par_Sql['Vet_Rec'])?0:$Par_Sql['Vet_Rec'])."', Cnt_Cod='".(empty($Par_Sql['Cnt_Cod'])?0:$Par_Sql['Cnt_Cod'])."', Vet_Int='".(empty($Par_Sql['Vet_Int'])?0:$Par_Sql['Vet_Int'])."', Vet_Uni='".(empty($Par_Sql['Vet_Uni'])||$Par_Sql['Vet_Uni']*1<=0?1:$Par_Sql['Vet_Uni'])."', Ren_Cod=".(empty($Par_Sql['Ret_Ren_Cod'])?'NULL':"'$Par_Sql[Ret_Ren_Cod]'").", Des_Adi=".(empty($Par_Sql['Des_Adi'])?'NULL':"'$Par_Sql[Des_Adi]'").", Ren_Iva=".(empty($Par_Sql['Iva_Ren_Cod'])?'NULL':"'$Par_Sql[Iva_Ren_Cod]'").",Vet_Ite='$Par_Sql[Vet_Ite]', Vet_Ice='".(empty($Par_Sql['Ice_Por'])?0:$Par_Sql['Ice_Por'])."'";
            //echo $sql."<br>";
            break;

        case 87: // usado
            /* inserta asiento */
            $sql="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5];";
            //echo $sql."<br>";
         break;


        case 88: // usado

            /* selecciona cuentas iva */
            $sql="SELECT iva_cobrad.Pld_Cod,CONCAT(Pld_Des,' (',Pld_Cdc,')') AS Pld_Des FROM iva_cobrad
                    INNER JOIN det_plan ON det_plan.Pld_Cod=iva_cobrad.Pld_Cod
                    WHERE Pla_Cod='$Par_Sql[0]'";
            //echo $sql."<br>";

            break;

        case 89: // usado

            /* formas de pago */
            $sql = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Est = 'A' ORDER BY For_Des ASC";
            //echo $sql."<br>";
            break;

        case 90: // usado

            /* cuentas credito */
            $sql = "SELECT ccpp_cliente.Pld_Cod, det_plan.Pld_Des, ccpp_cliente.Cpc_Def, ccpp_cliente.Cpc_Cxc, Cpc_Def AS extra FROM det_plan INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
            //echo $sql."<br>";
            break;

        
        case 91://tipo de pago efectivo--credito 
            $sql="select tipos_pago.For_Cod,Pag_Des
                  from 
                  ventas 
                  inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                  inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                  where ventas.Vet_Cod=$Par_Sql[0] ";
            break;
        case 92:
            $sql="select pago_venta.Vet_Cod,pago_venta.Bak_Cod,Ban_Cod,pago_venta.Pag_Cod,Vet_Cue,Vet_Che,Vet_Tot,Vet_Num,Mon_Cod,tipos_pago.For_Cod,tipos_pago.Pag_Des,if(pago_venta.Pld_Cod>0,pago_venta.Pld_Cod,(select asientos.Pld_Cod from pago_venta
                inner join tipos_pago on pago_venta.Pag_Cod = tipos_pago.Pag_Cod 
                left join ventas_compr on pago_venta.Vet_Cod = ventas_compr.Vet_Cod 
                left join asientos on ventas_compr.Com_Cod = asientos.Com_Cod and asientos.Asi_Val=pago_venta.Vet_Tot  and asientos.Asi_Deh='D'
                where
                pago_venta.Vet_Cod=$Par_Sql[0] LIMIT 1)) as Pag_Pld,
                cheques_ext.Che_Fec as Fec_che
                from pago_venta
                inner join tipos_pago on pago_venta.Pag_Cod = tipos_pago.Pag_Cod 
                left join ventas_compr on pago_venta.Vet_Cod = ventas_compr.Vet_Cod
                left join cheq_det_ventas on pago_venta.Vet_Cod = cheq_det_ventas.Vet_Cod
                left join cheques_ext on cheq_det_ventas.Che_Cod = cheques_ext.Che_Cod
                where 
                pago_venta.Vet_Cod=$Par_Sql[0]";
            break;
        case 93:
            $sql="SELECT ventas_det.* , Ite_Lar, 
            renta.Ren_Sri as Ret_Ren_Sri, 
            renta.Ren_Cod as Ret_Ren_Cod,
            renta.Ren_Por as Ret_Ren_Por,
            renta.Ren_Con as Ret_Ren_Con,
            renta.Ren_Tip as Ret_Ren_Tip,
            renta.Ren_Ret as Ret_Ren_Ret,
            renta.Ren_Est as Ret_Ren_Est,
            iva_imp.Ren_Sri as Iva_Ren_Sri, 
            iva_imp.Ren_Cod as Iva_Ren_Cod,
            iva_imp.Ren_Por as Iva_Ren_Por,
            iva_imp.Ren_Con as Iva_Ren_Con,
            iva_imp.Ren_Tip as Iva_Ren_Tip,
            iva_imp.Ren_Ret as Iva_Ren_Ret,
            iva_imp.Ren_Est as Iva_Ren_Est,
            ivas.Iva_Por as Iva_Por,
            ivas.Iva_Cod as Iva_Cod,
            det_plan.Pld_Cdc,
            ice.*,
            unidad.*,
            adquisicio.*
            FROM producto
            left join adquisicio on adquisicio.Adq_Cod = producto.Adq_Cod
            INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
            INNER JOIN ventas_det ON ventas_det.Pro_Cod = producto.Pro_Cod
            LEFT join renta_iva as renta on renta.Ren_Cod= ventas_det.Ren_Cod 
            LEFT join renta_iva as iva_imp on iva_imp.Ren_Cod = ventas_det.Ren_Iva
            left join iva as ivas  on ivas.Iva_Cod = ventas_det.Iva_Cod
            left join ice on ice.Ice_Int = producto.Ice_Int
            left join unidad on unidad.Uni_Cod = producto.Uni_Cod
            left join produ_plan on produ_plan.Pro_Cod = producto.Pro_Cod and (Tip_Pld='V' OR Tip_Pld='I') 
            left join det_plan on produ_plan.Pld_Cod = det_plan.Pld_Cod
            where Vet_Cod=$Par_Sql[0] order by Vet_Ite";
            break;
        
        case 94://Update sobre la tabla venta
                    
            $sql = "update ventas set Tic_Cod=$Par_Sql[0], Cli_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], Caj_Cod=".(empty($Par_Sql[3])?'NULL':$Par_Sql[3]).", Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Vet_Obs=".(empty($Par_Sql[6])?'NULL':"'$Par_Sql[6]'").", Aut_Cod=".(empty($Par_Sql[7])?'NULL':"'$Par_Sql[7]'").", Vet_Des=$Par_Sql[8], Vet_Hor='$Par_Sql[9]',Vet_Xml=".(empty($Par_Sql[10])?'NULL':"'$Par_Sql[10]'").",Vet_Aut=".(empty($Par_Sql[11])?'NULL':"'$Par_Sql[11]'").",Ret_Num=".(empty($Par_Sql[12])?'NULL':"'$Par_Sql[12]'").",
                    Ret_Fec=".(empty($Par_Sql[13])?'NULL':"'$Par_Sql[13]'").",Ret_Aut=".(empty($Par_Sql[14])?'NULL':"'$Par_Sql[14]'").",Tpc_Cod=".(empty($Par_Sql[15])?'NULL':"$Par_Sql[15]")." where Vet_Cod=$Par_Sql[16]";
            break;
        case 95: // Delete pagos de la venta
            $sql="Delete from pago_venta where Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;

        case 96:
            $sql = "DELETE  FROM ccpp_cobrar WHERE Cpc_Cod=$Par_Sql[1] AND Vet_Cod=$Par_Sql[0]";
            break;

        case 966:
            $sql = "UPDATE det_ccpp_c
                    INNER JOIN tipos_pago ON det_ccpp_c.Pag_Cod = tipos_pago.Pag_Cod
                    INNER JOIN comprobantes ON det_ccpp_c.Com_Cod = comprobantes.Com_Cod
                    SET det_ccpp_c.Cpc_Est = 'I', 
                    comprobantes.Com_Est = 'I'
                    WHERE 
                    det_ccpp_c.Cpc_Cod = $Par_Sql[0]
                    AND tipos_pago.Pag_Des = 'Retencion'
                    AND tipos_pago.Pag_Abr = 'RET'";
            break;

        case 97:
            $sql="DELETE  FROM ventas_det WHERE Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 98:
            $sql = "DELETE  FROM comprobantes where Com_Cod=$Par_Sql[0]";
            break;
        case 99:
            $sql="DELETE FROM ventas_compr WHERE Com_Cod=$Par_Sql[0] and Vet_Cod=$Par_Sql[1]";
            break;
        case 100:
            if($Par_Sql[2]=="")$campos="COUNT(autorizaci.Aut_Cod) as total";
            else { $campos="IF(Aut_Tem='N',Aut_Sri,'Electronica')as AutSri,autorizaci.* , IF(autorizaci.Aut_Est='A','S','N') as Aut_Estado,tipo_compr.*,Suc_Sri";}
            $where="";
            if($Par_Sql[1]!=""){
                $where=" and tipo_compr.Tic_Cod=$Par_Sql[1]";
            }
            $sql= "SELECT $campos FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' 
                    OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' 
                    OR Tic_Sri='51' OR Tic_Sri='52') AND Tic_Est='A' AND autorizaci.Pun_Cod=$Par_Sql[0] $where $Par_Sql[2]";
            break;
        case 101:
            $where_doc="Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
            if(isset($Par_Sql[3])){$where_doc="Tic_Sri='4' OR Tic_Sri='5'";}
            $where="";
            if(($Par_Sql[1])!=0){
                $where=" AND autorizaci.Aut_Cod<>'$Par_Sql[1]' and tipo_compr.Tic_Cod<>'$Par_Sql[2]'";
            }
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE ($where_doc) AND autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A' $where ";
            break;
            
        case 102://Update sobre la tabla venta
                    
            $sql = "update ventas set Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Aut_Cod=".(empty($Par_Sql[7])?'NULL':"'$Par_Sql[7]'").",Ret_Num=".(empty($Par_Sql[12])?'NULL':"'$Par_Sql[12]'").",
                    Ret_Fec=".(empty($Par_Sql[13])?'NULL':"'$Par_Sql[13]'").",Ret_Aut=".(empty($Par_Sql[14])?'NULL':"'$Par_Sql[14]'")." where Vet_Cod=$Par_Sql[16]";
            break;
        case 103:
            $sql="SELECT det_plan.Pld_Des, det_plan.Pld_Cdc, reniva_pla.Pld_Cod, reniva_pla.Ren_Cod FROM det_plan
                INNER JOIN reniva_pla ON (det_plan.Pld_Cod = reniva_pla.Pld_Cod)
                INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                INNER JOIN renta_iva ON (renta_iva.Ren_Cod = reniva_pla.Ren_Cod)
                WHERE renta_iva.Ren_Sri='$Par_Sql[1]' AND renta_iva.Ren_Por=1 AND reniva_pla.Ren_Tip='$Par_Sql[2]' AND plan_cuenta.Pla_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';

            break;
        case 104:
            $sql="select Pro_Cod from producto
                  left join item on producto.Ite_Cod = item.Ite_Cod 
                  left join categorias on categorias.Cat_Cod = item.Cat_Cod
                  where categorias.Emp_Cod=$Par_Sql[0] and Pro_Est='A' limit 1";
            break;
        case 105://Select para cargar perido segun fecha
            $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio,perio_cont.Pla_Cod
                    FROM perio_cont
                    LEFT JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND '$Par_Sql[0]' between Pec_Fei and Pec_Fef AND Pec_Est='A' ORDER BY Pec_Fei DESC";
            break;
        case 106://Select consumidor final
            $sql = "SELECT Cli_Cod, Prs_Ape from cliente
                    inner join persona on cliente.Prs_Cod = persona.Prs_Cod
                    where cliente.Emp_Cod=$Par_Sql[0] and Prs_Ced=9999999999999";
            break;
        case 107://Select para listar los tipos de comprobantes de Tic_Sr1=0,1,2,41,44,47,48,49,50,51,52
            if(isset($Par_Sql[1])){
                $where="autorizaci.Aut_Cod='$Par_Sql[1]'";
            }  else {
                $where="autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A'";
            }
                
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='4' OR Tic_Sri='5') AND $where ";
            break;
        case 108:
            if($Par_Sql[2]=="")$campos="COUNT(autorizaci.Aut_Cod) as total";
            else { $campos="autorizaci.* , IF(autorizaci.Aut_Est='A','S','N') as Aut_Estado,tipo_compr.*,Suc_Sri";}
            $where="";
            if($Par_Sql[1]!=""){
                $where=" and tipo_compr.Tic_Cod=$Par_Sql[1]";
            }
            $sql= "SELECT $campos FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='4' OR Tic_Sri='5') AND Tic_Est='A' AND autorizaci.Pun_Cod=$Par_Sql[0] $where $Par_Sql[2]";
            break;
        case 109:
            if($Par_Sql[4]==""){$campos="count(ventas.Vet_Cod) as total";}
            else { $campos=" ventas.*, sucursal.Suc_Sri,autorizaci.Pun_Sri,ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, 
                            tipo_compr.Tic_Des , tipos_pago.Pag_Des , forma_pago.For_Cod,forma_pago.For_Des";}
            $where="";
            if($Par_Sql[2]!=""){
                $where=$where."AND forma_pago.For_Cod=$Par_Sql[2]";
            }
            
            if($Par_Sql[3]!=""){
                $where=$where." and ventas.Vet_Num=$Par_Sql[3] ";
            }
            $sql= "SELECT $campos FROM ventas
                inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                inner join puntos_imp on caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                inner join cliente on cliente.Cli_Cod = ventas.Cli_Cod 
                inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                inner join forma_pago on forma_pago.For_Cod = tipos_pago.For_Cod
                inner join persona on persona.Prs_Cod= cliente.Prs_Cod
                left join ccpp_cobrar on ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
                inner join autorizaci on autorizaci.Aut_Cod = ventas.Aut_Cod
                inner join sucursal on sucursal.Suc_Cod = puntos_imp.Suc_Cod
                inner join tipo_compr on tipo_compr.Tic_Cod = ventas.Tic_Cod 
                where  
                puntos_imp.Suc_Cod=$Par_Sql[1] and ventas.Vet_Est ='A' and tipo_compr.Tic_Sri=1 $where $Par_Sql[4]";
            break;
        case 110:
            $sql="select iva.* from iva where '2017-06-12' BETWEEN iva.Iva_Ini and Iva_Fin limit 1 ";
            break;
        case 111://Insert sobre la tabla venta
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Est)
                    VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]',
                       '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]',
                        ".(empty($Par_Sql[10])?'NULL':"'$Par_Sql[10]'").",
                        ".(empty($Par_Sql[11])?'NULL':"'$Par_Sql[11]'").",
                        ".(!empty($Par_Sql[12])?"'$Par_Sql[12]'":"NULL").",
                        ".(!empty($Par_Sql[13])?"'$Par_Sql[13]'":"NULL").",
                        ".(!empty($Par_Sql[14])?"'$Par_Sql[14]'":"NULL").",
                        ".(!empty($Par_Sql[15])?"'$Par_Sql[15]'":"NULL").",'$Par_Sql[16]')";
            break;
        case 112:
            $sql= "select sum(pago_venta.Vet_Tot) as Vet_Total
                from ventas
                inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod 
                where ventas.Vet_Cod = '$Par_Sql[0]' ";
            break;
        case 113:
            $sql="insert into det_ccpp_c (Com_Cod,Pag_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,Cpc_Cod)
                values ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]',$Par_Sql[3],$Par_Sql[4],$Par_Sql[5])";
            break;

        case 1133:
            $sql="INSERT INTO det_ccpp_c (Com_Cod,Pag_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,Cpc_Cod)
                values ($Par_Sql[Com_Cod],$Par_Sql[Pag_Cod],'$Par_Sql[Cpc_Fec]',$Par_Sql[Cpc_Val],'$Par_Sql[Cpc_Obs]',$Par_Sql[Cpc_Cod])";
            break;

        case 114:
            $sql="INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Nns,Vet_Ntd,Vet_Fdm)
                    VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]',
                       '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]',
                        ".(empty($Par_Sql[10])?'NULL':"'$Par_Sql[10]'").",
                        ".(empty($Par_Sql[11])?'NULL':"'$Par_Sql[11]'").",
                        ".(!empty($Par_Sql[12])?"'$Par_Sql[12]'":"NULL").",
                        ".(!empty($Par_Sql[13])?"'$Par_Sql[13]'":"NULL").",
                        ".(!empty($Par_Sql[14])?"'$Par_Sql[14]'":"NULL").",
                        ".(!empty($Par_Sql[15])?"'$Par_Sql[15]'":"NULL").",
                        ".(!empty($Par_Sql[16])?"'$Par_Sql[16]'":"NULL").",
                        ".(!empty($Par_Sql[17])?"'$Par_Sql[17]'":"NULL").",
                        ".(!empty($Par_Sql[18])?"'$Par_Sql[18]'":"NULL").")";
            break;
        case 115:
            $where = (empty($Par_Sql[1])?'':'and det_ccpp_c.Com_Cod<>'.$Par_Sql[1]);
            $sql= "SELECT COALESCE(SUM(det_ccpp_c.Cpc_Val),0) AS Vet_Abonos 
                FROM ventas
                INNER join ventas_compr on ventas_compr.Vet_Cod = ventas.Vet_Cod
                INNER join ccpp_cobrar on ccpp_cobrar.Com_Cod = ventas_compr.Com_Cod
                INNER join det_ccpp_c on det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod and Cpc_Est='A'
                INNER join comprobantes on comprobantes.Com_Cod = det_ccpp_c.Com_Cod and Com_Est='A'
                where ventas.Vet_Cod = '$Par_Sql[0]' $where ";
            break;
        
        case 116:
            $where="";
            if(($Par_Sql[1])!=0){
                $where=" AND autorizaci.Aut_Cod<>'$Par_Sql[1]' and tipo_compr.Tic_Cod<>'$Par_Sql[2]'";
            }
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE (Tic_Sri='5' OR Tic_Sri='4') AND autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A' $where ";
            break;
        case 117:
            $sql  = "select ventas.*,sucursal.Suc_Sri,autorizaci.Pun_Sri,
                    ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec, 
                    CONCAT(Prs_Ape,' ',Prs_Nom) as cliente,
                    tipo_compr.Tic_Des , tipos_pago.Pag_Des ,
                    forma_pago.For_Cod,forma_pago.For_Des from ventas 
                    inner join ventas_compr on ventas.Vet_Cod = ventas_compr.Vet_Cod
                    inner join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod
                    inner join ccpp_cobrar on ccpp_cobrar.Com_Cod = comprobantes.Com_Cod
                    inner join det_ccpp_c on det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                    inner join puntos_imp on caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                    inner join cliente on cliente.Cli_Cod = ventas.Cli_Cod
                    inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                    inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                    inner join forma_pago on forma_pago.For_Cod = tipos_pago.For_Cod
                    inner join persona on persona.Prs_Cod= cliente.Prs_Cod
                    inner join autorizaci on autorizaci.Aut_Cod = ventas.Aut_Cod
                    inner join sucursal on sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    inner join tipo_compr on tipo_compr.Tic_Cod = ventas.Tic_Cod
                    where det_ccpp_c.Com_Cod='$Par_Sql[0]' and ventas.Vet_Num<>'$Par_Sql[1]'";
            break;
        case 118:
            $sql = "select ventas.* from ventas 
                    inner join ventas_compr on ventas_compr.Vet_Cod = ventas.Vet_Cod
                    inner join comprobantes on ventas_compr.Com_Cod = comprobantes.Com_Cod
                    where ventas.Vet_Cod='$Par_Sql[0]'";
            break;
        /*case 119:
            $sql =  "update ventas set Tic_Cod=$Par_Sql[0], Cli_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], "
                . "Caj_Cod=".(empty($Par_Sql[3])?'NULL':$Par_Sql[3]).", Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Vet_Obs=".(empty($Par_Sql[6])?'NULL':"'$Par_Sql[6]'").",
                    Aut_Cod=".(empty($Par_Sql[7])?'NULL':"'$Par_Sql[7]'").", Vet_Des=$Par_Sql[8],
                    Vet_Hor='$Par_Sql[9]', Vet_Nns='$Par_Sql[10]',Vet_Ntd='$Par_Sql[11]',Vet_Fdm='$Par_Sql[12]'
                    where Vet_Cod=$Par_Sql[13]";
            break;*/
         case 119:
            $sql =  "update ventas set Tic_Cod=$Par_Sql[0], Cli_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], "
                . "Caj_Cod=".(empty($Par_Sql[3])?'NULL':$Par_Sql[3]).", Vnd_Cod=$Par_Sql[4],
                    Vet_Num=$Par_Sql[5], Vet_Obs=".(empty($Par_Sql[6])?'NULL':"'$Par_Sql[6]'").",
                    Aut_Cod=".(empty($Par_Sql[7])?'NULL':"'$Par_Sql[7]'").", Vet_Des=$Par_Sql[8],
                    Vet_Hor='$Par_Sql[9]',Vet_Xml='$Par_Sql[10]', Vet_Nns='$Par_Sql[11]',Vet_Ntd='$Par_Sql[12]',Vet_Fdm='$Par_Sql[13]'
                    where Vet_Cod=$Par_Sql[14]";
            break;
        case 120:
            $sql="DELETE FROM det_ccpp_c WHERE Com_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 121:
            $sql  = "select ventas.*,sucursal.Suc_Sri,autorizaci.Pun_Sri,
                    ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec, 
                    CONCAT(Prs_Ape,' ',Prs_Nom) as cliente,
                    tipo_compr.Tic_Des , tipos_pago.Pag_Des ,
                    forma_pago.For_Cod,forma_pago.For_Des from ventas 
                    left join ventas_compr on ventas.Vet_Cod = ventas_compr.Vet_Cod
                    left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod
                    left join ccpp_cobrar on ccpp_cobrar.Com_Cod = comprobantes.Com_Cod
                    left join det_ccpp_c on det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                    inner join puntos_imp on caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                    inner join cliente on cliente.Cli_Cod = ventas.Cli_Cod
                    inner join pago_venta on ventas.Vet_Cod = pago_venta.Vet_Cod
                    inner join tipos_pago on tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                    inner join forma_pago on forma_pago.For_Cod = tipos_pago.For_Cod
                    inner join persona on persona.Prs_Cod= cliente.Prs_Cod
                    inner join autorizaci on autorizaci.Aut_Cod = ventas.Aut_Cod
                    inner join sucursal on sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    inner join tipo_compr on tipo_compr.Tic_Cod = ventas.Tic_Cod
                    where ventas.Vet_Num='$Par_Sql[0]' and ventas.Tic_Cod='$Par_Sql[1]' and sucursal.Suc_Sri='$Par_Sql[2]' and autorizaci.Pun_Sri='$Par_Sql[3]' AND sucursal.Emp_Cod=$_SESSION[Ses_Emp_Cod] Limit 1";
            break;
        case 122:
            $si_existe="renta_iva.Ren_Est='A'";
            if(isset($Par_Sql['Ren_Por']))
                $si_existe= "renta_iva.Ren_Por=$Par_Sql[Ren_Por]";
            //renta de Referencia para NUEVA RENTA
            $sql = "select * from renta_iva 
                    left join reniva_pla on reniva_pla.Ren_Cod = renta_iva.Ren_Cod
                    left join det_plan on det_plan.Pld_Cod =reniva_pla.Pld_Cod AND det_plan.Pla_Cod='$Par_Sql[Pla_Cod]' 
                    where renta_iva.Ren_Sri='$Par_Sql[Ren_Sri]' and $si_existe limit 1";
            break;
        case 123:
            $sql="INSERT INTO renta_iva(Ren_Sri,Ren_Con,Ren_Por,Ren_Ini,Ren_Fin,Ren_Ing,Ren_Tip,Ren_Ret,Ren_Est,Adq_Cod) VALUES('$Par_Sql[Ren_Sri]','$Par_Sql[Ren_Con]','$Par_Sql[Ren_Por]','$Par_Sql[Ren_Ini]','$Par_Sql[Ren_Fin]','$Par_Sql[Ren_Ing]','$Par_Sql[Ren_Tip]','$Par_Sql[Ren_Ret]','$Par_Sql[Ren_Est]','$Par_Sql[Adq_Cod]');";
            break;
        /* niebla */
        
        case 124:
            /* Consulta del vendedor en base al codigo de la persona */
            $sql = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
            //echo $sql;            
            break;
        case 125: 
            /*Consulta informacion de la empresa */
            $sql= "SELECT 
                              empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
                              sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv,confi_fact.Cof_Con 
                            FROM
                              empresas
                          INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                              INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                       WHERE
                          sucursal.Suc_Cod=$Par_Sql[0]";
            //echo ".$sql."<br>";            
            break;
        case 126:
            $fields=(empty($Par_Sql['limits']))?"COUNT(ventas.Vet_Cod)AS total":"ventas.Vet_Cod,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Fac_Num, ventas.Vet_Num, ventas.Ret_Num, caja_aper.Caj_Fec,CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est, SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Tot, SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva, SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced";
            $sql = "SELECT  $fields
                            FROM ventas "
                            .(empty($Par_Sql['limits'])?'':"INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod) 
                            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) ")."
                            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
                            INNER JOIN sucursal ON (sucursal.Suc_Cod=puntos_imp.Suc_Cod)
                            INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)                             
                            INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
                        INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                    WHERE (Caj_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59') 
                            AND ventas.Vet_Est = 'A' 
                        AND ventas.Tic_Cod = $Par_Sql[Tic_Cod] 
                        AND puntos_imp.Suc_Cod =  $Par_Sql[Suc_Cod] ".
                        ($Par_Sql['Cli_Cod']!=''?' AND cliente.Cli_Cod='.$Par_Sql['Cli_Cod']:'')    
                        .(empty($Par_Sql['limits'])?'':" GROUP BY ventas.Vet_Cod $Par_Sql[limits] ");
                //echo $sql.'<br>';
            break; 
        case 127:
            /*  Consulta la ciudad en base al usuario */
            $sql = "SELECT sucursal.Ciu_Cod, ciudad.Ciu_Des FROM usuarios, sucursal, ciudad 
                    WHERE usuarios.Suc_Cod = sucursal.Suc_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod AND usuarios.Usu_Cod = '$Par_Sql[0]'";
            break;
        case 128:
            $sql = "SELECT det_plan.Pla_Cod,banco.Ban_Cod,banco.Pld_Cod,CONCAT(Pld_Des,' (',Ban_Cue,')') AS Ban_Des FROM banco
                        INNER JOIN pago_plan ON pago_plan.Ban_Cod=banco.Ban_Cod
                        LEFT JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        LEFT JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                        LEFT JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                        WHERE Pag_Cod=$Par_Sql[0] AND perio_cont.Pec_Cod=$Par_Sql[1] AND Emp_Cod='$Par_Sql[2]' AND Ban_Est='A'";
            //echo $sql.'<br>';
            break;
        case 129:
            $sql = "SELECT ventas_det.Pro_Cod,Pro_Obs,producto.Adq_Cod,Adq_Cor,SUM(Vet_Can) AS Vet_Can,SUM(Vet_Pru)/COUNT(Vet_Num) AS Vet_Pru,SUM(Vet_Can)*SUM(Vet_Pru)/COUNT(Vet_Num) AS Importe,Iva_Por,Ite_Lar,iva.Iva_Cod
                    FROM ventas_det
                    INNER JOIN ventas ON ventas.Vet_Cod=ventas_det.Vet_Cod
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
                    INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                    WHERE $Par_Sql[0] 
                    GROUP BY ventas_det.Pro_Cod";
            //echo $sql.'<br>';
            break;
        case 130:
            
            //echo $sql;            
            break;
        case 131:
            /* Buscamos numero de venta segun el numero de autorizacion */
            $sql="SELECT 
                              ventas.Vet_Cod,autorizaci.Aut_Cod,ventas.Vet_Num
                      FROM
                              autorizaci
                              INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
                      WHERE
                              autorizaci.Aut_Sri='$Par_Sql[0]' AND ventas.Vet_Num='$Par_Sql[1]'";   
            //echo $sql;
            break;
        case 132:
                $sql = "UPDATE ventas SET Vet_Est='U' WHERE $Par_Sql[0]";
                //echo $sql.'<br>';
                break;
        case 133:
            /*Consulta cargar la autorizacion activa de acuerdo a un tipo de comprobante*/
            $sql = "SELECT autorizaci.Aut_Cod, autorizaci.Tic_Cod, Tic_Sri, autorizaci.Pun_Sri, autorizaci.Pun_Cod, autorizaci.Aut_Cad,Tic_Des, autorizaci.Aut_Sri, autorizaci.Aut_Ini, autorizaci.Aut_Fin, autorizaci.Aut_Adv, autorizaci.Aut_Ads FROM autorizaci,tipo_compr WHERE tipo_compr.Tic_Cod=autorizaci.Tic_Cod AND autorizaci.Tic_Cod = $Par_Sql[0]  
                    AND autorizaci.Pun_Cod = '$Par_Sql[1]'
                    AND autorizaci.Aut_Cad >= '$Par_Sql[2]' AND autorizaci.Aut_Est = 'A'";                    
                   
                    break;
        case 134:
            $sql = "SELECT Tic_Cod, Tic_Des, Tic_Sri FROM tipo_compr WHERE Tic_Est='A'";   
             break;
             
             
        // fecha de modificacion 02/04/2018
        
        case 135:
            $sql = "SELECT ventas_det.Pro_Cod,Pro_Obs,producto.Adq_Cod,Adq_Cor,SUM(Vet_Can) AS Vet_Can, SUM(Vet_Imp)/SUM(Vet_Can) AS Vet_Pru ,SUM(Vet_Imp) AS Importe,Iva_Por,Ite_Lar,iva.Iva_Cod,
                    IF(Iva_Por=0,'S','N')AS Iva,CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )AS Producto 
                    FROM ventas_det
                    INNER JOIN ventas ON ventas.Vet_Cod=ventas_det.Vet_Cod
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
                    INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                    INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                    WHERE $Par_Sql[0] 
                    GROUP BY ventas_det.Pro_Cod";
            //echo $sql.'<br>';
            break;   
        case 136:
            $sql = "SELECT * FROM stock_nd WHERE Pro_Cod='$Par_Sql[0]'";
            //echo $sql.'<br>';
            break;  
        case 137:
            $sql = "INSERT INTO stock_nd (Pro_Cod,Pro_Can,Pro_Imp)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2]) ";
            //echo $sql.'<br>';
            break; 
        case 138:
            $sql = "UPDATE stock_nd SET Pro_Can=$Par_Sql[1], Pro_Imp=$Par_Sql[2] WHERE Pro_Cod='$Par_Sql[0]';";
            //echo $sql.'<br>';
            break; 
         case 139:
            /*Consulta cargar la autorizacion activa de acuerdo a un tipo de comprobante*/
            $sql = "SELECT autorizaci.Aut_Cod, autorizaci.Tic_Cod, Tic_Sri, autorizaci.Pun_Sri, autorizaci.Pun_Cod, autorizaci.Aut_Cad,Tic_Des, autorizaci.Aut_Sri, autorizaci.Aut_Ini, autorizaci.Aut_Fin, autorizaci.Aut_Adv, autorizaci.Aut_Ads FROM autorizaci,tipo_compr WHERE tipo_compr.Tic_Cod=autorizaci.Tic_Cod AND autorizaci.Tic_Cod = $Par_Sql[0]  
                    AND autorizaci.Pun_Cod = '$Par_Sql[1]'
                    AND autorizaci.Aut_Cad >= '$Par_Sql[2]' AND autorizaci.Aut_Est = 'A'";                    
            //echo $sql.'<br/>';
                    break;
                    
                    
                    
        // fecha de modificacion 22/03/2018

        // case 140:
        case 140://Insert sobre la tabla venta
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Sri)
                    VALUES ($Par_Sql[Tic_Cod], $Par_Sql[Cli_Cod], $Par_Sql[Ciu_Cod], $Par_Sql[Caj_Cod], $Par_Sql[Vnd_Cod], '$Par_Sql[Vet_Num]','$Par_Sql[Vet_Obs]', $Par_Sql[Aut_Cod], '$Par_Sql[Vet_Des]', '$Par_Sql[Vet_Hor]',
                        ".(empty($Par_Sql['Vet_Xml'])?'NULL':"'$Par_Sql[Vet_Xml]'").",
                        ".(empty($Par_Sql['Vet_Aut'])?'NULL':"'$Par_Sql[Vet_Aut]'").",
                        ".(!empty($Par_Sql['Ret_Num'])?"'$Par_Sql[Ret_Num]'":"NULL").",
                        ".(!empty($Par_Sql['Ret_Fec'])?"'$Par_Sql[Ret_Fec]'":"NULL").",
                        ".(!empty($Par_Sql['Ret_Aut'])?"'$Par_Sql[Ret_Aut]'":"NULL").",
                        ".(!empty($Par_Sql['Tpc_Cod'])?"'$Par_Sql[Tpc_Cod]'":"NULL").",
                        ".(!empty($Par_Sql['Vet_Sri'])?"$Par_Sql[Vet_Sri]":"NULL").")";
                //ChromePhp::log($sql);
        break;
        case 141://Update sobre la tabla venta   
            $sql = "update ventas set Tic_Cod=$Par_Sql[Tic_Cod], Cli_Cod=$Par_Sql[Cli_Cod], Ciu_Cod=$Par_Sql[Ciu_Cod], Caj_Cod=".(empty($Par_Sql['Caj_Cod'])?'NULL':$Par_Sql['Caj_Cod']).", Vnd_Cod=$Par_Sql[Vnd_Cod],
                    Vet_Num=$Par_Sql[Vet_Num], Vet_Obs=".(empty($Par_Sql['Vet_Obs'])?'NULL':"'$Par_Sql[Vet_Obs]'").", Aut_Cod=".(empty($Par_Sql['Aut_Cod'])?'NULL':"'$Par_Sql[Aut_Cod]'").", Vet_Des=$Par_Sql[Vet_Des], Vet_Hor='$Par_Sql[Vet_Hor]',Vet_Xml=".(empty($Par_Sql['Vet_Xml'])?'NULL':"'$Par_Sql[Vet_Xml]'").",Vet_Aut=".(empty($Par_Sql['Vet_Aut'])?'NULL':"'$Par_Sql[Vet_Aut]'").",Ret_Num=".(empty($Par_Sql['Ret_Num'])?'NULL':"'$Par_Sql[Ret_Num]'").",Ret_Fec=".(empty($Par_Sql['Ret_Fec'])?'NULL':"'$Par_Sql[Ret_Fec]'").",Ret_Aut=".(empty($Par_Sql['Ret_Aut'])?'NULL':"'$Par_Sql[Ret_Aut]'").",Tpc_Cod=".(empty($Par_Sql['Tpc_Cod'])?'NULL':"$Par_Sql[Tpc_Cod]").",Vet_Sri=".(empty($Par_Sql['Vet_Sri'])?'NULL':"$Par_Sql[Vet_Sri]")." where Vet_Cod=$Par_Sql[Vet_Cod]";
            break;
            
        // fecha de modificacion 02/04/2018 
        case 142:
            /*Consulta cargar la autorizacion activa de acuerdo a un tipo de comprobante*/
            $sql = "SELECT stock_nd.*,(stock_nd.Pro_Imp/stock_nd.Pro_Can) AS Pro_Uni,CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) ) AS Producto,producto.Iva_Cod,Iva_Por FROM stock_nd 
                        INNER JOIN producto ON producto.Pro_Cod=stock_nd.Pro_Cod
                        INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON categorias.Cat_Cod=item.Cat_Cod
                        WHERE stock_nd.Pro_Imp>0 AND stock_nd.Pro_Can>0 AND Emp_Cod='$_SESSION[Ses_Emp_Cod]'
                        ORDER BY stock_nd.Pro_Imp DESC, stock_nd.Pro_Can DESC
                            ;";                    
            //echo $sql.'<br/>';
                    break;  

        case 143:
            if(empty($Par_Sql['limits'])){ 
                $campos="COUNT(ventas.Vet_Cod) AS total";         
            }else{
                $campos="ventas.*,
                vende.Prs_Ape,
                vende.Prs_Nom,
                ciudad.Ciu_Des,
                Tic_Des,
                ventas_compr.Com_Cod,
                tipo_compr.Tic_Sri,
                ccpp_cobrar.Cpc_Cod,
                tipopagocom.*,
                Caj_Fec as Vet_Fec,
                concat(vende.Prs_Ape,' ',vende.Prs_Nom)as vendedor_per,
                concat(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)as cliente_per,
                cliente_ven.Prs_Ced,
                comprobantes.Pec_Cod,
                if(ccpp_cobrar.Cpc_Cod is null,'Contado','Credito')as Pago,
                if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
            }
            $Par_Sql['Tic_Cod']=(!empty($Par_Sql['Tic_Cod'])?"AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]":"AND (tipo_compr.Tic_Sri=4 OR tipo_compr.Tic_Sri=5 )");
            if($Par_Sql['op_opciones']=='d'){
                $search="AND ventas.Vet_Num = '$Par_Sql[search]'"; $Par_Sql['Cmb_Mes']=$Par_Sql['Pec_Cod']='';
            }else{
                $Par_Sql['Cmb_Mes']=(!empty($Par_Sql['Pec_Cod'])&&!empty($Par_Sql['Cmb_Mes'])?"AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]":'');
                $Par_Sql['Pec_Cod']=(!empty($Par_Sql['Pec_Cod'])?"AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'":'');
                if($Par_Sql['op_opciones']=='c')
                    $search="AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search="AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }
            $sql="SELECT $campos FROM ventas
                  INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                  INNER JOIN persona as vende ON vendedor.Prs_Cod = vende.Prs_Cod
                  left join ventas_compr on ventas_compr.Vet_Cod=ventas.Vet_Cod
                  inner join cliente on cliente.Cli_Cod= ventas.Cli_Cod
                  INNER JOIN persona as cliente_ven ON cliente_ven.Prs_Cod = cliente.Prs_Cod
                  left join ccpp_cobrar on ccpp_cobrar.Cpc_Cod=(SELECT Cpc_Cod FROM det_ccpp_c INNER JOIN ventas_compr AS vet_cpr ON vet_cpr.Com_Cod=det_ccpp_c.Com_Cod WHERE vet_cpr.Vet_Cod=ventas.Vet_Cod LIMIT 1)
                  INNER JOIN ciudad ON ciudad.Ciu_Cod = ventas.Ciu_Cod
                  left join tipopagocom on tipopagocom.Tpc_Cod = ventas.Tpc_Cod
                  left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod AND comprobantes.Com_Est='A'
                  left join autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                  INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                  inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                WHERE ventas.Vet_Est<>'E'  AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $search
                $Par_Sql[order] $Par_Sql[limits] ;";
            //echo $sql.'<br/>';
            break;

        case 145: // ingreso a la tabla cheques_ext para que aparezca en el control de cheques de ccxcc
            $sql="INSERT INTO cheques_ext (Bak_Cod, Cli_Cod, Che_Cta, Che_Num, Che_Fec, Che_Val, Che_Cli) 
                  VALUES ($Par_Sql[Bak_Cod], '$Par_Sql[Cli_Cod]', '$Par_Sql[Vet_Cue]', '$Par_Sql[Vet_Che]', '$Par_Sql[Fec_che]', '$Par_Sql[Vet_Tot]', '$Par_Sql[Cliente]')";
            break;
        case 146: // Relaciona las ventas con los cheques entregados como pagos
            $sql="INSERT INTO cheq_det_ventas
                  VALUES ($Par_Sql[Che_Cod], '$Par_Sql[Vet_Cod]')";
            break;
        case 147: // Anular cheque pagos de la venta
            $sql="UPDATE cheques_ext, cheq_det_ventas SET cheques_ext.Che_Est = 'I' 
                    WHERE 
                    cheques_ext.Che_Cod = cheq_det_ventas.Che_Cod
                    AND cheq_det_ventas.Vet_Cod=$Par_Sql[0]";
            break;
        case 148: // Delete pagos de la venta
            $sql="Delete from cheq_det_ventas where Vet_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;

         case 150: // Stock del producto para controlar negativos en la venta
            $sql="SELECT Sum(kardex_ie.Kar_Can) - SUM(kardex_ie.Kar_Sal) as Stk_Can from kardex_ie where kardex_ie.Pro_cod = $Par_Sql[0] and kardex_ie.Kar_Est = 'A'";
            break;

        case 151: // Configuracion de la empresa para controlar stokc en la venta
            $sql="SELECT Cof_Stk_Neg from confi_fact where Emp_Cod=$Par_Sql[0]";
            break;

        case 152: // Stock del producto para controlar negativos en la venta
            $sql="SELECT Ite_Lar from item INNER JOIN producto ON item.Ite_Cod = producto.Ite_Cod where producto.Pro_cod = $Par_Sql[0] ";
            break;

        case 153://tipo de pago efectivo--credito 
            $sql="SELECT Pag_Cod, Pag_Des FROM tipos_pago WHERE For_Cod=$Par_Sql[0] AND Pag_Abr in ('CXC', 'EFE')";
        break;
    }
    return $sql;
}



