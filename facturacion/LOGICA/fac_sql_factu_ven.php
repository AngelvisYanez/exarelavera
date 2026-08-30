<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2016-06-28
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package inv.LOGICA
 */


function sentencias_doc($id,$Par_Sql)
{
    $sql="";
    switch($id){
        case 0:
            $sql="";
            //echo $sql.'<br/>';
            break;
        case 1: // usado
            /**
            * Con esta sentencia consulto producto y stock
            */
            $search='';
            if($Par_Sql[3]=='') $campos=" COUNT(item.Ite_Cod) AS total "; 
            else $campos=" item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,Adq_Cor,adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,stock.*";
            if($Par_Sql[2]=='c') $search=" producto.Pro_Bar='$Par_Sql[0]' ";
            else{ if(strlen($Par_Sql[0])<3&&!ctype_digit($Par_Sql[0])) $search=" item.Ite_Lar LIKE '$Par_Sql[0]%' "; else $search=" ( UPPER(item.Ite_Lar) LIKE UPPER('%$Par_Sql[0]%') OR UPPER(producto.Pro_Obs) LIKE UPPER('%$Par_Sql[0]%') ) ";  }
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
                    INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod 
                  WHERE $search AND Pro_Est='A' AND Ite_Est='A' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql;
            break; 
        case 2://Busqueda de Proveedores // usado
            if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
            if($Par_Sql[3]==""){$campos="COUNT(Cli_Cod) as total";}
            else{
                $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                $campos=" Cli_Cod, persona.Prs_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, Cli_Dir, Prs_Dir, Prs_Cor, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
            }
            $sql="SELECT $campos FROM cliente, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql;
            break;
        case 3:
            $sql="SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A' AND Tic_Sri='$Par_Sql[0]'";
            //echo $sql;
            break;
        case 4:
            $sql="SELECT sustento.Tri_Sri, sustento.Tri_Cod, sustento.Tri_Des, sustento.Tri_Est FROM sustento WHERE sustento.Tri_Est='A'";
            //echo $sql;
            break;
        case 5: // usado
            //$sql="SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
            $sql="SELECT autorizaci.*,tipo_compr.*,Suc_Sri FROM autorizaci 
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE Tic_Est='A' AND Aut_Est='A' AND Tic_Sri!='7' AND autorizaci.Pun_Cod='$Par_Sql[0]' AND '$Par_Sql[1]' BETWEEN Aut_Fci AND Aut_Cad ORDER BY tipo_compr.Tic_Sri;";
            //echo $sql;
            break;
        case 6: // usado
            if(empty($Par_Sql[0]))
                $sql="SELECT Ciu_Cod, Ciu_Des, Pro_Nom  FROM ciudad INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod WHERE Ciu_Des != ''  ORDER BY Ciu_Des ASC";
            else
                $sql="SELECT ciudad.Ciu_Cod, Ciu_Des, Pro_Nom  FROM ciudad INNER JOIN sucursal ON sucursal.Ciu_Cod=ciudad.Ciu_Cod  INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod WHERE Ciu_Des != '' AND Suc_Cod='$Par_Sql[0]'  ORDER BY Ciu_Des ASC";
            //echo $sql;
            break;
        case 7:
            $sql="SELECT compras.Cop_Cod, Cop_Sec, Prv_Cod, Tic_Cod, Cop_Num, Cop_Fec, Cop_Aut, Cpp_Cod, compr_auto.Com_Cod FROM compras LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod  LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod=compras.Cop_Cod WHERE Cop_Est='A' AND Cop_Num='$Par_Sql[2]' AND Tic_Cod='$Par_Sql[1]' AND Prv_Cod='$Par_Sql[0]' ".(!empty($Par_Sql[3])?"AND compras.Cop_Cod<>$Par_Sql[3]":'');
            //echo $sql;
            break;
        case 8: // usado
            $sql="SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 9: // usado
            $sql="SELECT perio_cont.* FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
            //echo $sql;
            break;
        case 10: // usado
            $sql="SELECT * FROM vendedor
                INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                WHERE Suc_Cod=$Par_Sql[0] AND Prs_Cod=$Par_Sql[1]";     
            //echo $sql."<br>";
            break;
        case 11: // usado
            if(empty($Par_Sql[16]))
                $sql="INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod, Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod) 
			VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]', '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]', ".(empty($Par_Sql[10])?'NULL':"'$Par_Sql[10]'").",".(empty($Par_Sql[11])?'NULL':"'$Par_Sql[11]'").",".(!empty($Par_Sql[12])?"'$Par_Sql[12]'":"NULL").",".(!empty($Par_Sql[13])?"'$Par_Sql[13]'":"NULL").",".(!empty($Par_Sql[14])?"'$Par_Sql[14]'":"NULL").",'$Par_Sql[15]')";
            //else
                //$sql="UPDATE compras SET Tic_Cod=$Par_Sql[0], Prv_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], Cop_Num='$Par_Sql[3]', Cop_Aut='$Par_Sql[4]', Cop_Fec='$Par_Sql[5]', Cop_Reg='$Par_Sql[6]', Cop_Obs='$Par_Sql[7]', Cop_Cad='$Par_Sql[8]', Cop_Imf='$Par_Sql[9]', Tri_Cod='$Par_Sql[10]', Cop_Des='$Par_Sql[11]', Pec_Cod=$Par_Sql[12], Tpc_Cod=".(!empty($Par_Sql[13])?$Par_Sql[13]:'NULL').",Cop_Ntd='$Par_Sql[14]',Cop_Nns='$Par_Sql[15]',Cop_Nna='$Par_Sql[16]',Vnd_Cod='$Par_Sql[17]',Cop_Sec='$Par_Sql[18]' WHERE Cop_Cod=$Par_Sql[19] ";     
            //echo $sql."<br>";
            break;
        case 12: // usado
            $sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[Vet_Cod], Pro_Cod=$Par_Sql[Pro_Cod], Vet_Can=$Par_Sql[Vet_Can], 
			Iva_Cod=$Par_Sql[Iva_Cod], Vet_Pru=$Par_Sql[Vet_Pru], Vet_Imp=$Par_Sql[Vet_Imp], Vet_Dec='".(empty($Par_Sql['Vet_Dec'])?0:$Par_Sql['Vet_Dec'])."', Nge_Cod = '".(empty($Par_Sql['Nge_Cod'])?0:$Par_Sql['Nge_Cod'])."',
			Asi_Int='".(empty($Par_Sql['Asi_Int'])?0:$Par_Sql['Asi_Int'])."', Vet_Rec='".(empty($Par_Sql['Vet_Rec'])?0:$Par_Sql['Vet_Rec'])."', Cnt_Cod='".(empty($Par_Sql['Cnt_Cod'])?0:$Par_Sql['Cnt_Cod'])."', Vet_Int='".(empty($Par_Sql['Vet_Int'])?0:$Par_Sql['Vet_Int'])."', Vet_Uni='".(empty($Par_Sql['Vet_Uni'])||$Par_Sql['Vet_Uni']*1<=0?1:$Par_Sql['Vet_Uni'])."', Ren_Cod=".(empty($Par_Sql['Ren_Cod'])?'NULL':"'$Par_Sql[Ren_Cod]'").", Ren_Iva=".(empty($Par_Sql['Ren_Iva'])?'NULL':"'$Par_Sql[Ren_Iva]'").",Vet_Ite='$Par_Sql[Vet_Ite]', Vet_Ice='".(empty($Par_Sql['Vet_Ice'])?0:$Par_Sql['Vet_Ice'])."'";
            //echo $sql."<br>";           
            break;
        case 13:
            $sql="SELECT tipo_asien.* FROM form_compr INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=form_compr.Tia_Cod WHERE For_Cod = $Par_Sql[0]";
            //echo $sql."<br>";           
            break;
        case 14: // usado
            if(empty($Par_Sql[9]))
                $sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'";//Antes Com_Tip
//            else
//                $sql="UPDATE comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' WHERE Com_Cod=$Par_Sql[9] ";
            //echo $sql."<br>";           
            break;
        case 15: // usado
            /* Relaciona una venta y un comprobante para saber que es automatico */
            $sql = "INSERT INTO ventas_compr(Com_Cod, Vet_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
            //echo $sql."<br>";
            break;
        case 16: // usado
            /* busca cuenta relacion producto */
            $sql = "SELECT Pro_Cod,produ_plan.Pld_Cod,Tip_Pld,Pld_Cdc,Pld_Des,Pla_Cod FROM produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pro_Cod=$Par_Sql[1] AND (Tip_Pld='$Par_Sql[2]' OR Tip_Pld='I') AND Pla_Cod=$Par_Sql[0]";
            //echo $sql."<br>";
            break;
        case 17: // usado
            /* inserta asiento */
            $sql="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5];";
            //echo $sql."<br>";
            break;
        case 18: // usado
            /* busca ivas */
            $sql="SELECT DISTINCT * FROM iva WHERE Iva_Por!=0 AND Iva_Ini > '2001-07-01' GROUP BY Iva_Por ORDER BY Iva_Por DESC,Iva_Ini DESC ";
            //echo $sql."<br>";
            break;

          //CONSULTAS PARA VALIDAR EL CLIENTE EN EL AGREGAR 
        case 177://Select para obtener los datos de una persona segun su cedula
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

        case 19: // usado
            /* selecciona ivas */
            $sql="SELECT * FROM iva WHERE Iva_Por>0 AND ('$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin OR (DATE('$Par_Sql[0]')>=Iva_Ini AND Iva_Fin IS NULL) ) ORDER BY Iva_Por DESC"; //compras.Cop_Fec,
            //echo $sql."<br>";
            break;
        case 20: // usado
            /* selecciona cuentas iva */
            $sql="SELECT iva_cobrad.Pld_Cod,CONCAT(Pld_Des,' (',Pld_Cdc,')') AS Pld_Des FROM iva_cobrad
                    INNER JOIN det_plan ON det_plan.Pld_Cod=iva_cobrad.Pld_Cod
                    WHERE Pla_Cod='$Par_Sql[0]'";
            //echo $sql."<br>";
            break;
        case 21: // usado
            /* formas de pago */
            $sql = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Est = 'A' ORDER BY For_Des ASC";
            //echo $sql."<br>";
            break;
        case 22: // usado
            /* cuentas contado */
            $sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan, pago_plan, plan_cuenta
			 WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND Pag_Est='A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND pago_plan.Pag_Cod = $Par_Sql[1] AND plan_cuenta.Pla_Cod = $Par_Sql[0] ORDER BY Pld_Cdc, Pld_Des";
            //echo $sql."<br>";
            break;
        case 23: // usado
            /* cuentas credito */
            $sql = "SELECT ccpp_cliente.Pld_Cod, det_plan.Pld_Des, ccpp_cliente.Cpc_Def, ccpp_cliente.Cpc_Cxc, Cpc_Def AS extra FROM det_plan INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
            //echo $sql."<br>";
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
        
        case 30:  // usado //Busqueda de clientes
            $sql="SELECT persona.*,cliente.Cli_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as cliente FROM persona  
                    LEFT JOIN cliente ON cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1]
                    WHERE Prs_Ced LIKE '$Par_Sql[0]%'  LIMIT 2;";
            //echo $sql;
            break;
        case 31: // usado
            $sql="INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','$Par_Sql[Prs_Sex]',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod]);";
            //echo $sql.'<br/>';
            break;
        case 32: // usado
            $sql="INSERT INTO cliente(Prs_Cod,Cli_Tic,Cli_Cup,Cli_Ruf,Cli_Fac,Cli_Dir,Cli_Con,Cli_Tip,Emp_Cod) VALUES($Par_Sql[Prs_Cod],'$Par_Sql[Cli_Tic]','".(empty($Par_Sql['Cli_Cup'])?'0':$Par_Sql['Cli_Cup'])."','".(empty($Par_Sql['Cli_Ruf'])?'':$Par_Sql['Cli_Ruf'])."','".(empty($Par_Sql['Cli_Fac'])?'':$Par_Sql['Cli_Fac'])."','".(empty($Par_Sql['Cli_Dir'])?'':$Par_Sql['Cli_Dir'])."','$Par_Sql[Cli_Con]','".(empty($Par_Sql['Cli_Tip'])?'R':$Par_Sql['Cli_Tip'])."',$Par_Sql[Emp_Cod]);";
            //echo $sql.'<br/>';
            break;
        case 33:
            $sql="SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND Pec_Est = 'A' AND plan_cuenta.Emp_Cod= $Par_Sql[0] ORDER BY Pec_Fei Desc";
            //echo $sql.'<br/>';
            break;
        case 34:  
            if(empty($Par_Sql['limits'])) $campos="COUNT(compras.Cop_Cod) AS total"; else $campos="compras.*, provee.Prs_Cod, provee.Prs_Cor, provee.Prs_Ced, provee.Prs_Ape, provee.Prs_Nom, provee.Prs_Dir, proveedore.Prv_Cod, Prv_Esp, Prv_Con, CONCAT(provee.Prs_Ape,' ',provee.Prs_Nom) as proveedor, tipo_compr.Tic_Des, comprobantes.Com_Cod, IF(comprobantes.Com_Cod IS NULL,'N','S') AS Com_Exi,Com_Fec,Tpc_Sri,Tpc_Des,Cpp_Cod,IF(Cpp_Cod IS NULL,'Contado','Credito')AS Pago,Cpp_Ven,Cpp_Obs, IF(retencion.Ret_Cod IS NULL,'N','S') AS Ret_Exi,Ret_Cod,Ret_Asu,Ret_Aut,retencion.Aut_Cod,Aut_Sri,Ret_Num,Ret_Fec,Ret_Xml,CONCAT(vended.Prs_Ape,' ',vended.Prs_Nom) as vendedor ";
            $Par_Sql['Tic_Cod']=(!empty($Par_Sql['Tic_Cod'])?"AND compras.Tic_Cod=$Par_Sql[Tic_Cod]":'');
            if($Par_Sql['op_opciones']=='d'){
                $search="AND compras.Cop_Num LIKE '$Par_Sql[search]%'"; $Par_Sql['Cmb_Mes']=$Par_Sql['Pec_Cod']='';
            }else{
                $Par_Sql['Cmb_Mes']=(!empty($Par_Sql['Pec_Cod'])&&!empty($Par_Sql['Cmb_Mes'])?"AND MONTH(compras.Cop_Fec)=$Par_Sql[Cmb_Mes]":'');
                $Par_Sql['Pec_Cod']=(!empty($Par_Sql['Pec_Cod'])?"AND compras.Pec_Cod=$Par_Sql[Pec_Cod]":'');
                if($Par_Sql['op_opciones']=='c')
                    $search="AND provee.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search="AND (UPPER(CONCAT(provee.Prs_Ape,' ',provee.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }
            $sql="SELECT $campos FROM compras
                INNER JOIN proveedore ON compras.Prv_Cod=proveedore.Prv_Cod
                INNER JOIN persona AS provee ON provee.Prs_Cod=proveedore.Prs_Cod
                INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=compras.Tic_Cod
                INNER JOIN vendedor ON vendedor.Vnd_Cod=compras.Vnd_Cod
                INNER JOIN persona AS vended ON vendedor.Prs_Cod=vended.Prs_Cod
                LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod
                LEFT JOIN comprobantes ON compr_auto.Com_Cod=comprobantes.Com_Cod                 
                LEFT JOIN tipopagocom ON tipopagocom.Tpc_Cod=compras.Tpc_Cod 
                LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod=compras.Cop_Cod 
                LEFT JOIN retencion ON (retencion.Cop_Cod=compras.Cop_Cod AND Ret_Est='A')
                LEFT JOIN autorizaci ON autorizaci.Aut_Cod=retencion.Aut_Cod
                WHERE proveedore.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $search
                $Par_Sql[limits] ;";
            //echo $sql.'<br/>';
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
            $sql="SELECT ccpp_prove.Pld_Cod FROM asientos INNER JOIN ccpp_prove ON asientos.Pld_Cod=ccpp_prove.Pld_Cod WHERE Com_Cod=$Par_Sql[0] ";
            //echo $sql.'<br/>';
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
            $sql="DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 42:
            $sql="DELETE FROM det_compra WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 43:
            $sql="SELECT * FROM kardex_ie WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 44:
            $sql="DELETE FROM kardex_ie WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 45: // usado
            $sql="SELECT Tpc_Cod,Tpc_Sri,Tpc_Des FROM tipopagocom WHERE Tpc_Est='A'";
            //echo $sql.'<br/>';
            break;
        case 46:
            $sql="DELETE FROM det_ccpp_p WHERE Com_Cod='$Par_Sql[0]' ";
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
                    INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND Suc_Cod='$Par_Sql[0]' AND tipo_compr.Tic_Cod =$Par_Sql[5]
                    AND Pun_Sri='$Par_Sql[4]' AND Vet_Num='$Par_Sql[2]'".(!empty($Par_Sql[3])?"AND ventas.Vet_Cod<>$Par_Sql[3]":'').';';
            //echo $sql.'<br/>';
            break;
        case 51: // usado
            $sql="SELECT 
                    CASE         
                        WHEN MAX(Vet_Num)IS NOT NULL AND MAX(Vet_Num)>=$Par_Sql[3] THEN ( 
                            SELECT MIN(t.Vet_Num)+1
                            FROM ventas t 
                            INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                            INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                            WHERE tp.Suc_Cod=$Par_Sql[0] AND ta.Pun_Sri='$Par_Sql[5]' AND ta.Aut_Sri='$Par_Sql[1]' AND ta.Tic_Cod=$Par_Sql[4] AND t.Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3] AND
                            NOT EXISTS (
                                SELECT NULL FROM ventas n 
                                    INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                    INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                    WHERE n.Vet_Num=t.Vet_Num+1 AND np.Suc_Cod=$Par_Sql[0] AND na.Pun_Sri='$Par_Sql[5]' AND na.Aut_Sri='$Par_Sql[1]' AND na.Tic_Cod=$Par_Sql[4] AND n.Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3]
                                )
                           )            
                        ELSE IFNULL(MAX(Vet_Num),$Par_Sql[2]-1)+1
                        END AS 'next'
                FROM ventas
                INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                WHERE Suc_Cod=$Par_Sql[0] AND autorizaci.Pun_Sri='$Par_Sql[5]' AND autorizaci.Aut_Sri='$Par_Sql[1]' AND autorizaci.Tic_Cod=$Par_Sql[4] AND Vet_Num BETWEEN $Par_Sql[2] AND $Par_Sql[3]";            
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
            if(empty($Par_Sql[4]))
                $sql = "INSERT INTO ccpp_cobrar(Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'));";
            else
                $sql = "UPDATE ccpp_cobrar SET Cpc_Ven='$Par_Sql[2]', Cpc_Obs=UPPER('$Par_Sql[3]') WHERE Cpc_Cod='$Par_Sql[4]' AND Vet_Cod='$Par_Sql[1]' AND Com_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 56:
             $sql="SELECT   
                    sum(
                          ( 
                      (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */
                          +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0)) /* ICE */
                          )	*(1+iva.Iva_Por/100)	/* IVA */
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
            $sql = "SELECT ".(!empty($Par_Sql[1])?"SUM(det_ccpp_p.Pag_Val)":"COUNT(det_ccpp_p.Cpp_Cod)")."AS total FROM det_ccpp_p INNER JOIN comprobantes ON det_ccpp_p.Com_Cod=comprobantes.Com_Cod WHERE Cpp_Cod='$Par_Sql[0]' ".(!empty($Par_Sql[1])?"AND Pag_Est='$Par_Sql[1]' AND Com_Est='A'":'').";";
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
            $sql = "DELETE FROM det_retenc WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 63:
            $sql = "DELETE FROM retencion WHERE Ret_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 64:
            $sql = "DELETE FROM ccpp_pagar WHERE Cpp_Cod='$Par_Sql[2]' AND Cop_Cod='$Par_Sql[1]' AND Com_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 65:
            $sql = "SELECT COUNT(Cop_Cod)AS total FROM compr_auto WHERE Com_Cod='$Par_Sql[0]';";
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
            $sql="SELECT * FROM bancos;";
            //echo $sql.'<br/>';
            break;
        case 71: // usado
            $sql="SELECT banco.*,Pld_Des FROM banco 
                    INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    WHERE Ban_Cue!=0 AND Ban_Cue!='' AND Ban_Est='A' AND Emp_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        case 72: // usado
            $sql="INSERT INTO pago_venta (Vet_Cod, Bak_Cod, Ban_Cod, Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num) 
		  VALUES ($Par_Sql[Vet_Cod],".(empty($Par_Sql['Bak_Cod'])?'1':$Par_Sql['Bak_Cod']).",".(empty($Par_Sql['Ban_Cod'])?'NULL':$Par_Sql['Ban_Cod']).", $Par_Sql[Pag_Cod], ".(empty($Par_Sql['Vet_Cue'])?'NULL':"'$Par_Sql[Vet_Cue]'").", ".(empty($Par_Sql['Vet_Che'])?'NULL':"'$Par_Sql[Vet_Che]'").", $Par_Sql[Vet_Tot], '$Par_Sql[Vet_Num]')";
            //echo $sql.'<br/>';
            break;
        case 73: // usado
            $sql="SELECT Pre_Cod,Pre_Pvp,Pre_Des,Pre_Est,precios.Tpv_Cod,Pre_Ini,Pre_Fin FROM precios INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod WHERE precios.Suc_Cod='$Par_Sql[0]' AND Pro_Cod='$Par_Sql[1]' AND Pre_Est='$Par_Sql[2]' ".(empty($Par_Sql[3])?'':"AND Tpv_Def='D'")." ".(empty($Par_Sql[4])?'':"(('$Par_Sql[4]' BETWEEN Pre_Ini AND Pre_Fin) OR (Pre_Ini IS NULL AND Pre_Fin IS NULL) OR (Pre_Ini='0000-00-00' AND Pre_Fin='0000-00-00'))").";";
            //echo $sql.'<br/>';
            break; 
        case 733: // usado
            $sql="SELECT Pre_Cod,Pre_Pvp,Pre_Des,Pre_Est,precios.Tpv_Cod,Pre_Ini,Pre_Fin FROM precios INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod WHERE precios.Suc_Cod='$Par_Sql[0]' AND Pro_Cod='$Par_Sql[1]' AND Pre_Est='$Par_Sql[2]' ".(empty($Par_Sql[3])?'':"AND tipo_preci.Tpv_Cod=$Par_Sql[3]")." ".(empty($Par_Sql[4])?'':"(('$Par_Sql[4]' BETWEEN Pre_Ini AND Pre_Fin) OR (Pre_Ini IS NULL AND Pre_Fin IS NULL) OR (Pre_Ini='0000-00-00' AND Pre_Fin='0000-00-00'))").";";
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

        case 80: // ingreso a la tabla cheques_ext para que aparezca en el control de cheques de ccxcc
            $sql="INSERT INTO cheques_ext (Bak_Cod, Cli_Cod, Che_Cta, Che_Num, Che_Fec, Che_Val, Che_Cli) 
                  VALUES ($Par_Sql[Bak_Cod], '$Par_Sql[Cli_Cod]', '$Par_Sql[Vet_Cue]', '$Par_Sql[Vet_Che]', '$Par_Sql[Fec_che]', '$Par_Sql[Vet_Tot]', '$Par_Sql[Cliente]')";
                    //echo $sql.'<br/>';
            break;
            
         case 146: // Relaciona las ventas con los cheques entregados como pagos
            $sql="INSERT INTO cheq_det_ventas
                  VALUES ($Par_Sql[Che_Cod], '$Par_Sql[Vet_Cod]')";
            break;
    }
    //echo $sql."<br/>";
    return $sql;  
}
