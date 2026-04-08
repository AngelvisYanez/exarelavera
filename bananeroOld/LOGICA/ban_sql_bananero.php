<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erick Cordova
 * @version 1.0
 * Fecha de actualizacion:	2018-01-30
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package bodega.LOGICA
 */
function sentencias_bananero($id,$Par_Sql){
	switch($id){
		case 1://Listado de clientes
      break;
    case 2://Busqueda de Proveedores
      if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
      else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
      if($Par_Sql[3]==""){$campos="COUNT(Prv_Cod) as total";}
      else{
          $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
          $campos="persona.Prs_Sex,persona.Ciu_Cod, Prv_Cod,Prv_Com, persona.Prs_Cod,Prv_Cor, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax, Prs_Dir, Prs_Cor, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est, Prv_Con, Prv_Esp ,Prs_Ape, Prs_Nom,Prv_Tic";
      }
      $sql="SELECT $campos FROM proveedore, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
      //echo $sql;
      break;
    case 3:
      $sql="SELECT Emp_Cod,persona.*,proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor FROM persona  
              LEFT JOIN proveedore ON proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]
              WHERE Prs_Ced LIKE '$Par_Sql[0]%'  LIMIT 10;";
      //echo $sql;
      break;
    case 4:
      $sql="INSERT INTO productor_banano(Prd_Mag,Prd_Nom,Prv_Cod) VALUES('$Par_Sql[Prd_Mag]','$Par_Sql[Prd_Nom]',$Par_Sql[Prv_Cod])";
      break;
    case 5:
      $sql="UPDATE proveedor SET Prv_Com='$Par_Sql[Prv_Com]',Prv_Cor='$Par_Sql[Prv_Cor]'";
      break;
    case 6:
      $sql="INSERT INTO ";
      break;
    case 7:
      $sql="SELECT ciudad.* FROM ciudad INNER JOIN pais on pais.Pas_Cod= ciudad.Pas_Cod WHERE Ciu_Est='A' AND Pas_Est='A'";
      break;
    case 8: // usado
    /* identificacion */
      $sql="SELECT * FROM identifica WHERE Ide_Prc IS NOT NULL AND Ide_Prc<>'';";
    break;
    case 9://Busqueda de Proveedores
    $sql="SELECT Emp_Cod,persona.*,proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor FROM persona  
      LEFT JOIN proveedore ON proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]
      WHERE Prs_Ced LIKE '$Par_Sql[0]%'  LIMIT 10;";
    break;
    case 10:
    $sql="INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','$Par_Sql[Prs_Sex]',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod]);";
    //echo $sql.'<br/>';
    break;
    case 11:
    $sql="INSERT INTO proveedore(Prs_Cod,Prv_Tic,Prv_Com,Prv_Con,Prv_Esp,Emp_Cod) VALUES($Par_Sql[Prs_Cod],'$Par_Sql[Prv_Tic]','$Par_Sql[Prv_Com]','$Par_Sql[Prv_Con]','$Par_Sql[Prv_Esp]',$Par_Sql[Emp_Cod]);";
    //echo $sql.'<br/>';
    break;
    case 12:
    $sql="SELECT COUNT(productor_banano.Prd_Cod) AS productor FROM productor_banano 
    INNER JOIN proveedore ON proveedore.Prv_Cod = productor_banano.Prv_Cod
    WHERE proveedore.Prv_Cod=$Par_Sql[Prv_Cod]";
    break;
    case 13:
      if(empty($Par_Sql['limits'])) 
        $campos="COUNT(prod.Prd_Cod) AS total"; 
      else 
        $campos=" prod.*,per.Prs_Ced,per.Prs_Dir, SUM(bod.Bod_Cod IS NOT NULL) AS bod, CONCAT(per.Prs_Ape,' ',per.Prs_Nom) as proveedor,prov.Prv_Con, prov.Prv_Esp ";
      $sql="SELECT $campos from productor_banano AS prod INNER JOIN proveedore AS prov
      ON prov.Prv_Cod = prod.Prv_Cod AND prov.Prv_Est='A' INNER JOIN persona AS per 
      ON per.Prs_Cod =prov.Prs_Cod AND per.Prs_Est='A' 
      LEFT JOIN bodega AS bod ON bod.Prd_Cod = prod.Prd_Cod WHERE prod.Prd_Est='A' GROUP BY prod.Prd_Cod";
      break;
    case 14:
      $sql="SELECT bodega.* FROM bodega WHERE Prd_Cod=$Par_Sql[Prd_Cod]";
      break;
    case 15:
      $campos="(SUM(IFNULL(kar.Kar_Can,0))-SUM(IFNULL(kar.Kar_Sal,0))) AS Pro_Can, marca.Mar_Des,cat.Cat_Des,adquisicio.Adq_Cor, uni.Uni_Des, item.Ite_Lar, sum(kar.Kar_Sal)AS salida, sum(kar.Kar_Can) AS entrada, sum(kar.Kar_Can) - sum(kar.Kar_Sal) stocka_act, pro.*";
      $sql="SELECT $campos FROM kardex_ie AS kar INNER JOIN producto AS pro ON pro.Pro_Cod = kar.Pro_Cod 
      INNER JOIN adquisicio ON pro.Adq_Cod = adquisicio.Adq_Cod
      INNER JOIN unidad AS uni ON uni.Uni_Cod=pro.Uni_Cod INNER JOIN marca ON marca.Mar_Cod= pro.Mar_Cod  INNER JOIN item ON item.Ite_Cod = pro.Ite_Cod INNER JOIN categorias as cat ON cat.Cat_Cod= item.Cat_Cod INNER JOIN bodega AS bod ON bod.Bod_Cod=kar.Bod_Cod WHERE pro.Pro_Est='A'  AND bod.Bod_Cod=$Par_Sql[Bod_Cod] GROUP BY pro.Pro_Cod";
      break;
    case 16://Con esta sentencia consulto producto y stock
            if(empty($Par_Sql['limits']))
              $campos=" COUNT(item.Ite_Cod) AS total ";
            else $campos=" item.Ite_Cod,item.Ite_Est,ice.Ice_Int,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,Adq_Cor,adquisicio.Adq_Des,adquisicio.Adq_Cor,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,Stk_Can,Ice_Por,producto.Pro_Tip ";
            if($Par_Sql['op_opciones']=='c') $search=" producto.Pro_Cod='$Par_Sql[Prs_Ced]' ";
            else $search=" item.Ite_Lar  LIKE '%$Par_Sql[Prs_Ced]%' ";
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
                    INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod and stock.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                    INNER JOIN precios AS prec ON prec.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND prec.Pro_Cod=producto.Pro_Cod AND prec.Pre_Est='A' 
                    LEFT JOIN ice ON producto.Ice_Int=ice.Ice_Int
                  WHERE $search AND Pro_Est='A' AND
                  categorias.Emp_Cod = $_SESSION[Ses_Emp_Cod]";
            break;
        case 17://Select para obtener el precio de los productos
            $sql="SELECT Pre_Cod,Pre_Pvp,Tpv_Des,Pre_Des,Pre_Est,precios.Tpv_Cod,Pre_Ini,Pre_Fin FROM precios INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod WHERE precios.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND Pro_Cod=$Par_Sql[Pro_Cod] AND Pre_Est='A' AND Tpv_Def='D'";
            break;
        case 18:
          $sql="SELECT (SUM(IFNULL(kar.Kar_Can,0))-SUM(IFNULL(kar.Kar_Sal,0))) AS Pro_Can, marca.Mar_Des,cat.Cat_Des, uni.Uni_Des, item.Ite_Lar, sum(kar.Kar_Sal)AS salida, sum(kar.Kar_Can) AS entrada, sum(kar.Kar_Can) - sum(kar.Kar_Sal) stocka_act,adquisicio.Adq_Cor, pro.* FROM kardex_ie AS kar INNER JOIN producto AS pro ON pro.Pro_Cod = kar.Pro_Cod INNER JOIN unidad AS uni ON uni.Uni_Cod=pro.Uni_Cod INNER JOIN marca ON marca.Mar_Cod= pro.Mar_Cod  INNER JOIN adquisicio ON pro.Adq_Cod = adquisicio.Adq_Cod INNER JOIN item ON item.Ite_Cod = pro.Ite_Cod INNER JOIN categorias as cat ON cat.Cat_Cod= item.Cat_Cod INNER JOIN bodega AS bod ON bod.Bod_Cod=kar.Bod_Cod WHERE pro.Pro_Est='A'  AND bod.Bod_Cod=$Par_Sql[Bod_Cod] GROUP BY pro.Pro_Cod";
          if(empty($Par_Sql['limits']))
                $sql="select COUNT(Pro_Cod) AS total FROM ($sql) AS Conador";
        break;
        case 19:
          $sql="SELECT (concat(Con_Ini,IF(Con_Fin=0,' en adelante', concat(' a ',Con_Fin))))AS rango, ren.Ren_Cod,ren.Ren_Sri,conf.Con_Obs,conf.Con_Ini,conf.Con_Fin,Con_Tip,Con_Por,conf.Con_Cod FROM conf_retencion AS conf INNER JOIN renta_iva AS ren ON ren.Ren_Cod = conf.Ren_Cod WHERE conf.Con_Est='A' AND ren.Ren_Est='A' AND  Con_Tip='$Par_Sql[Con_Tip]' ORDER BY Con_Tip ";
          break;
        case 20:
          $sql="SELECT Con_Tip FROM conf_retencion AS conf INNER JOIN renta_iva AS ren ON ren.Ren_Cod = conf.Ren_Cod WHERE conf.Con_Est='A' AND ren.Ren_Est='A'  GROUP BY Con_Tip ";
          break;
        case 21://verificar stock por producto en bodega inputs:Bod_Cod,Pro_Cod
        $sql="SELECT  sum(IF (kar.Kar_Can IS NULL,0,kar.Kar_Can)) - sum(IF (kar.Kar_Sal IS NULL,0,kar.Kar_Sal)) stocka_act, pro.Pro_Cod FROM kardex_ie AS kar  INNER JOIN producto AS pro ON pro.Pro_Cod = kar.Pro_Cod AND pro.Pro_Cod=$Par_Sql[Pro_Cod] INNER JOIN bodega AS bod ON bod.Bod_Cod=kar.Bod_Cod WHERE bod.Bod_Cod=$Par_Sql[Bod_Cod] GROUP BY pro.Pro_Cod";
        break;
        case 22://verificar cantidad de producto en descuentos o ingresos ->inputs:Bod_Cod,Pro_Cod,Det_Tip
        $sql="SELECT  sum(IF (det_liq.Det_Can IS NULL,0,det_liq.Det_Can)) valor,pro.Pro_Cod FROM det_liquidacion AS det_liq INNER JOIN producto AS pro ON pro.Pro_Cod = det_liq.Pro_Cod AND pro.Pro_Cod=$Par_Sql[Pro_Cod] INNER JOIN liquidacion AS liq ON liq.Liq_Cod=det_liq.Liq_Cod  INNER JOIN bodega AS bod ON bod.Bod_Cod=liq.Bod_Cod AND bod.Bod_Cod=$Par_Sql[Bod_Cod] WHERE det_liq.Det_Tip='$Par_Sql[Det_Tip]' GROUP BY pro.Pro_Cod";
        break;
        case 23:
        // crear cabecera de nueva liquidacion
        $sql="INSERT INTO liquidacion ( Liq_Fec, Liq_Sem, Liq_Tot, Bod_Cod, Liq_Hac, Lic_Hec, Liq_Mar, Liq_Num) VALUES('$Par_Sql[Liq_Fec]','$Par_Sql[Liq_Sem]', $Par_Sql[Liq_Tot], $Par_Sql[Bod_Cod],'$Par_Sql[Liq_Hac]', '$Par_Sql[Liq_Hec]', '$Par_Sql[Liq_Mar]', '$Par_Sql[Liq_Num]')";
          break;
        case 24: // vendedor parametrizado del usuario logueado
          $sql="SELECT * FROM vendedor INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod WHERE Suc_Cod=$_SESSION[Ses_Suc_Cod] AND Prs_Cod=$_SESSION[Ses_Prs_Cod]";
          break;
        case 25:
          $sql="SELECT proveedore.Prv_Cod FROM proveedore INNER JOIN compra_prov ON compra_prov.Prv_Cod = proveedore.Prv_Cod AND Emp_Cod= $_SESSION[Ses_Emp_Cod] WHERE Prv_Est='A'";
          break;
        case 26:
          $sql="SELECT Iva_Cod FROM iva WHERE Iva_Est='A' AND '$Par_Sql[Aju_Fec]' BETWEEN Iva_Ini and Iva_Fin";
          break;
        case 27:
          if($Par_Sql['Bod_Ori']=='0')
            $Par_Sql['Bod_Ori']='NULL';
          if($Par_Sql['Bod_Des']=='0')
            $Par_Sql['Bod_Des']='NULL';

          $sql="INSERT INTO movimiento_bodega(Mov_Fec,Mov_Obs,Bod_Ori,Bod_Des,Mov_Est,Mov_Ale,Liq_Cod) VALUES ('$Par_Sql[Liq_Fec]','$Par_Sql[Mov_Obs]',$Par_Sql[Bod_Ori],$Par_Sql[Bod_Des],'F',0,$Par_Sql[Liq_Cod])";
          break;
        case 28:
          if($Par_Sql['Bod_Cod']=='0')
            $Par_Sql['Bod_Cod']='NULL';
          $sql="INSERT INTO ajuste_kar (Tia_Cod,Vnd_Cod,Prv_Cod,Aju_Fec,Aju_Hor,Aju_Det,Aju_Obs,Aju_Tip,Bod_Cod) VALUES($Par_Sql[Tia_Cod],$Par_Sql[Vnd_Cod],$Par_Sql[Prv_Cod],'$Par_Sql[Liq_Fec]','$Par_Sql[Aju_Hor]','$Par_Sql[Aju_Det]','$Par_Sql[Mov_Obs]','$Par_Sql[Aju_Tip]',$Par_Sql[Bod_Cod])";
          break;
        case 29:
          $sql="INSERT INTO entrada_salida (Aju_Cod,Mov_Cod,Aju_Tip) VALUES ($Par_Sql[Aju_Cod],$Par_Sql[Mov_Cod],'$Par_Sql[Aju_Tip]')";
          break;
        case 30:
          $sql="INSERT INTO kardex_ie (Kar_Int, Vet_Cod, Iva_Cod, Aju_Cod, Vnd_Cod, Cop_Cod, Pro_Cod, Kar_Fec, Kar_Hor,  $Par_Sql[campo], Bod_Cod) VALUES($Par_Sql[Ind], 0,  $Par_Sql[Iva_Cod], $Par_Sql[Aju_Cod],$Par_Sql[Vnd_Cod], 0, $Par_Sql[Pro_Cod], '$Par_Sql[Aju_Fec]', '$Par_Sql[Aju_Hor]', $Par_Sql[Pro_Can] , $Par_Sql[Bod_Cod])";
          break;
        case 31:
        $sql="UPDATE movimiento_bodega SET Mov_Est='$Par_Sql[Mov_Est]', Mov_Ale=$Par_Sql[alert] WHERE Mov_Cod=$Par_Sql[Mov_Cod]";
        break;
        case 32:
        $sql="SELECT bod.Bod_Cod FROM bodega AS bod INNER JOIN sucursal AS suc ON bod.Suc_Cod= suc.Suc_Cod WHERE suc.Suc_Cod =$_SESSION[Ses_Suc_Cod]  AND bod.Bod_Tip='P'";
        break;
        case 33:
        if (!isset($Par_Sql['tipo'])) {
          $Par_Sql['tipo']=NULL;
        }
        $sql="INSERT INTO det_liquidacion (Det_Tot, Det_Can, Det_Pre, Liq_Cod, Det_Tip, Pro_Cod,Det_Cat) VALUES($Par_Sql[Pro_Imp], $Par_Sql[Pro_Can],$Par_Sql[Pro_Pru],$Par_Sql[Liq_Cod], '$Par_Sql[Det_Tip]',$Par_Sql[Pro_Cod],'$Par_Sql[tipo]')";
        break;
        case 34:
        $sql="INSERT INTO det_retencion (Liq_Cod, Con_Cod, Ret_Bas, Ret_Tot) VALUES($Par_Sql[Liq_Cod], $Par_Sql[Con_Cod], $Par_Sql[Ret_Bas], $Par_Sql[Pro_Imp])";
        break;
        case 35:
        $sql="SELECT Tia_Cod,Tia_Des,Tia_Est FROM tipo_ajus WHERE Tia_Est='A' AND Tia_Tra='$Par_Sql[Tia_Tra]' AND Emp_Cod=$_SESSION[Ses_Emp_Cod]  ORDER BY Tia_Ord";
        break;
        case 36:
        $sql="UPDATE productor_banano SET Prd_Mag='$Par_Sql[Prd_Mag]',Prd_Nom='$Par_Sql[Prd_Nom]',Prv_Cod=$Par_Sql[Prv_Cod] WHERE Prd_Cod=$Par_Sql[Prd_Cod]";
        break;
        case 37:
          $search='';
          if (!empty($Par_Sql['search'])) {
            if ($Par_Sql['op_opciones']=='n') {
              $search="AND prod.Prd_Nom LIKE '$Par_Sql[search]%' ";
            }else{
              $search="AND prod.Prd_Mag LIKE '$Par_Sql[search]%' ";
            }
          }
          
          if(empty($Par_Sql['limits'])) 
            $campos="COUNT(prod.Prd_Cod) AS total"; 
          else 
            $campos=" prod.*,per.Prs_Ced,per.Prs_Dir, SUM(bod.Bod_Cod IS NOT NULL) AS bod, CONCAT(per.Prs_Ape,' ',per.Prs_Nom) as proveedor,prov.Prv_Con, prov.Prv_Esp ";
          $sql="SELECT $campos from productor_banano AS prod INNER JOIN proveedore AS prov
          ON prov.Prv_Cod = prod.Prv_Cod AND prov.Prv_Est='A' INNER JOIN persona AS per 
          ON per.Prs_Cod =prov.Prs_Cod AND per.Prs_Est='A' 
          LEFT JOIN bodega AS bod ON bod.Prd_Cod = prod.Prd_Cod WHERE prod.Prd_Est='A' $search GROUP BY prod.Prd_Cod";
        break;
      }
  return $sql;
}