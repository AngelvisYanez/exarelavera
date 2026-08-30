<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erick Cordova
 * @version 1.0
 * Fecha de actualizacion:	2018-01-03
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package bodega.LOGICA
 */
function sentencias_bodega($id,$Par_Sql){
	switch($id){
		case 1://Listado de clientes
      break;
    case 2:
      $sql = "SELECT * FROM usuarios AS usu INNER JOIN sucursal AS suc ON suc.Suc_Cod = usu.Suc_Cod AND suc.Suc_Cod=$_SESSION[Ses_Suc_Cod] INNER JOIN persona AS per ON per.Prs_Cod = usu.Prs_Cod INNER JOIN vendedor AS ven ON ven.Prs_Cod = per.Prs_Cod INNER JOIN puntos_imp AS pun ON pun.Pun_Cod= ven.Pun_Cod AND pun.Suc_Cod = $_SESSION[Ses_Suc_Cod]";
      // echo $sql;
      break;
    case 3:
      $sql="INSERT INTO bodega (Bod_Dir,Bod_Res,Bod_Nom,Suc_Cod,Prd_Cod) VALUES ('$Par_Sql[Bod_Dir]','$Par_Sql[Bod_Res]','$Par_Sql[Bod_Nom]',$_SESSION[Ses_Suc_Cod],".
      (empty($Par_Sql['Prd_Cod'])?'NULL':$Par_Sql['Prd_Cod']).")";
      break;
    case 4:
      $sql="INSERT INTO bodega_usuario (Bod_Cod,Usu_Cod) VALUES ($Par_Sql[Bod_Cod],$Par_Sql[Usu_Cod])";
      break;
    case 5:
      if(empty($Par_Sql['limits'])) $campos="COUNT(bodega.Bod_Cod) AS total"; else $campos=" bodega.* ";
      $sql="SELECT $campos FROM bodega where  Suc_Cod = $_SESSION[Ses_Suc_Cod] $Par_Sql[where] ";
      break;
    case 6:
      $sql="SELECT * FROM bodega WHERE Bod_Cod=$Par_Sql[Bod_Cod]";
      break;
    case 7:
      $sql="SELECT Usu_Cod FROM bodega_usuario WHERE Bod_Cod=$Par_Sql[Bod_Cod]";
      break;
    case 8:
      $sql="UPDATE bodega SET Bod_Dir='$Par_Sql[Bod_Dir]',Bod_Res='$Par_Sql[Bod_Res]',Bod_Nom='$Par_Sql[Bod_Nom]', Prd_Cod=".
      (empty($Par_Sql['Prd_Cod'])?'NULL':$Par_Sql['Prd_Cod'])." WHERE Suc_Cod=$_SESSION[Ses_Suc_Cod] AND Bod_Cod=$Par_Sql[Bod_Cod]";
      break;
    case 9:
      $sql="DELETE FROM bodega_usuario WHERE Bod_Cod=$Par_Sql[Bod_Cod]";
      break;
    case 10:
      $sql="SELECT * FROM bodega AS bod INNER JOIN bodega_usuario AS bod_usu ON bod_usu.Bod_Cod=bod.Bod_Cod AND bod_usu.Usu_Cod=$_SESSION[Ses_Usu_Cod]";
      break;
    case 11:
      $Bod_Condicion="kar.Bod_Cod=$Par_Sql[Bod_Cod]";
      if($Par_Sql['Bod_Cod'] == 0){
        $Bod_Condicion="kar.Bod_Cod IS NULL";
      }

      $sql="SELECT (sum(CASE WHEN kar.Kar_Can IS NULL THEN 0 ELSE kar.Kar_Can END)-sum(CASE WHEN Kar_Sal IS NULL THEN 0 ELSE Kar_Sal END))AS Pro_Can,kar.Pro_Cod,unidad.Uni_Des,item.Ite_Lar,marca.Mar_Des,cat.Cat_Des FROM kardex_ie AS kar 
      INNER JOIN producto AS pro ON pro.Pro_Cod = kar.Pro_Cod AND pro.Pro_Est='A' 
      INNER JOIN item ON item.Ite_Cod = pro.Ite_Cod  AND item.Ite_Est='A' 
      INNER JOIN categorias AS cat ON cat.Cat_Cod = item.Cat_Cod 
      INNER JOIN marca ON marca.Mar_Cod = pro.Mar_Cod 
      INNER JOIN unidad ON unidad.Uni_Cod = pro.Uni_Cod 
      WHERE kar.Kar_Est='A' $Par_Sql[where] AND $Bod_Condicion GROUP BY kar.Pro_Cod /*HAVING Pro_Can > 0*/ ORDER BY item.Ite_Lar";

      if(empty($Par_Sql['limits']))
        $sql="SELECT count(t.Pro_Cod) AS total FROM (".$sql.") AS t"; 
      break;
    case 12:
      if($Par_Sql['Bod_Ori']=='0')
        $Par_Sql['Bod_Ori']='NULL';
      if($Par_Sql['Bod_Des']=='0')
        $Par_Sql['Bod_Des']='NULL';

      $sql="INSERT INTO movimiento_bodega(Mov_Fec,Mov_Obs,Bod_Ori,Bod_Des) VALUES ('$Par_Sql[Aju_Fec]','$Par_Sql[Mov_Obs]',$Par_Sql[Bod_Ori],$Par_Sql[Bod_Des])";
      break;
    case 13:
      if($Par_Sql['Bod_Cod']=='0')
        $Par_Sql['Bod_Cod']='NULL';
      $sql="INSERT INTO ajuste_kar (Tia_Cod,Vnd_Cod,Prv_Cod,Aju_Fec,Aju_Hor,Aju_Det,Aju_Obs,Aju_Tip,Bod_Cod) VALUES($Par_Sql[Tia_Cod],$Par_Sql[Vnd_Cod],$Par_Sql[Prv_Cod],'$Par_Sql[Aju_Fec]','$Par_Sql[Aju_Hor]','$Par_Sql[Aju_Det]','$Par_Sql[Mov_Obs]','$Par_Sql[Aju_Tip]',$Par_Sql[Bod_Cod])";
      break;
    case 14:
      $sql="SELECT Tia_Cod,Tia_Des,Tia_Est FROM tipo_ajus WHERE Tia_Est='A' AND Tia_Tra='$Par_Sql[Tia_Tra]' AND Emp_Cod=$_SESSION[Ses_Emp_Cod]  ORDER BY Tia_Ord";
      break;
    case 15: // vendedor parametrizado del usuario logueado
      $sql="SELECT * FROM vendedor INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod WHERE Suc_Cod=$_SESSION[Ses_Suc_Cod] AND Prs_Cod=$_SESSION[Ses_Prs_Cod]";
      break;
    case 16:
      $sql="SELECT proveedore.Prv_Cod FROM proveedore INNER JOIN compra_prov ON compra_prov.Prv_Cod = proveedore.Prv_Cod AND Emp_Cod= $_SESSION[Ses_Emp_Cod] WHERE Prv_Est='A'";
      break;
    case 17:
      $sql="INSERT INTO entrada_salida (Aju_Cod,Mov_Cod,Aju_Tip) VALUES ($Par_Sql[Aju_Cod],$Par_Sql[Mov_Cod],'$Par_Sql[Aju_Tip]')";
      break;
    case 18:
      $sql="SELECT * FROM bodega AS bod WHERE bod.Suc_Cod=$_SESSION[Ses_Suc_Cod]";
      break;
    case 19:
      $sql="SELECT Iva_Cod FROM iva WHERE Iva_Est='A' AND '$Par_Sql[Aju_Fec]' BETWEEN Iva_Ini and Iva_Fin";
      break;
    case 20:
      if(empty($Par_Sql['limits'])) $campos="COUNT(mov.Mov_Cod) AS total"; else $campos="bod.Bod_Tip as tipo_destino, bod_or.Bod_Tip as tipo_origen, mov.Mov_Ale,mov.Mov_Cod,mov.Mov_Fec,mov.Mov_Sys,mov.Mov_Obs,mov.Mov_Est,IF(mov.Bod_Ori IS NULL,0,mov.Bod_Ori ) AS Bod_Ori, IF(mov.Bod_Des IS NULL,0,mov.Bod_Des) AS Bod_Des ,IF (bod.Bod_Nom IS NULL ,'Principal',bod.Bod_Nom) AS destino, IF (bod_or.Bod_Nom IS NULL ,'Principal',bod_or.Bod_Nom) AS origen";
      $sql="SELECT $campos FROM movimiento_bodega AS mov LEFT JOIN bodega AS bod ON mov.Bod_Des = bod.Bod_Cod LEFT JOIN bodega AS bod_or ON mov.Bod_Ori = bod_or.Bod_Cod INNER JOIN bodega_usuario AS bo_us ON bo_us.Bod_Cod = bod.Bod_Cod AND bo_us.Usu_Cod = $_SESSION[Ses_Usu_Cod] WHERE bod.Bod_Cod IS NOT NULL AND bod_or.Bod_Cod IS NOT NULL";
      break;
    case 21:
      $sql="SELECT kar.Pro_Cod, kar.Kar_Sal AS Pro_Can, uni.Uni_Des,item.Ite_Lar FROM kardex_ie AS kar INNER JOIN ajuste_kar AS aju ON aju.Aju_Cod = kar.Aju_Cod INNER JOIN entrada_salida AS en_sa ON en_sa.Aju_Cod =aju.Aju_Cod AND en_sa.Mov_Cod=$Par_Sql[Mov_Cod] INNER JOIN producto AS pro ON pro.Pro_Cod=kar.Pro_Cod INNER JOIN unidad AS uni ON uni.Uni_Cod = pro.Uni_Cod INNER JOIN item ON item.Ite_Cod = pro.Ite_Cod";
      break;
    case 22:
      $sql="UPDATE movimiento_bodega SET Mov_Est='$Par_Sql[Mov_Est]', Mov_Ale=$Par_Sql[alert] WHERE Mov_Cod=$Par_Sql[Mov_Cod]";
      break;
    case 23:
      if($Par_Sql['Bod_Cod']=='0')
        $Par_Sql['Bod_Cod']='NULL';
      $sql="INSERT INTO kardex_ie (Kar_Int, Vet_Cod, Iva_Cod, Aju_Cod, Vnd_Cod, Cop_Cod, Pro_Cod, Kar_Fec, Kar_Hor,  $Par_Sql[campo], Bod_Cod) VALUES($Par_Sql[Ind], 0,  $Par_Sql[Iva_Cod], $Par_Sql[Aju_Cod],$Par_Sql[Vnd_Cod], 0, $Par_Sql[Pro_Cod], '$Par_Sql[Aju_Fec]', '$Par_Sql[Aju_Hor]', $Par_Sql[Pro_Can] , $Par_Sql[Bod_Cod])";
      break;
    case 24:
      if(empty($Par_Sql['limits'])) 
        $campos="COUNT(pro.Pro_Cod) AS total"; 
      else 
        $campos="pro.Pro_Cod,unidad.Uni_Des,item.Ite_Lar,marca.Mar_Des,cat.Cat_Des";
      $sql="SELECT  $campos FROM producto AS pro INNER JOIN item ON item.Ite_Cod = pro.Ite_Cod  AND item.Ite_Est='A' INNER JOIN categorias AS cat ON cat.Cat_Cod = item.Cat_Cod AND Emp_Cod=$_SESSION[Ses_Emp_Cod] INNER JOIN marca ON marca.Mar_Cod = pro.Mar_Cod INNER JOIN unidad ON unidad.Uni_Cod = pro.Uni_Cod WHERE pro.Pro_Est='A' ORDER BY item.Ite_Lar";
        break;
    case 25:
      if(empty($Par_Sql['limits'])) $campos="COUNT(mov.Mov_Cod) AS total"; else $campos="mov.Mov_Cod,mov.Mov_Fec,mov.Mov_Sys,mov.Mov_Obs,mov.Mov_Est,IF(mov.Bod_Ori IS NULL,0,mov.Bod_Ori ) AS Bod_Ori, IF(mov.Bod_Des IS NULL,0,mov.Bod_Des) AS Bod_Des ,IF (bod.Bod_Nom IS NULL ,'Principal',bod.Bod_Nom) AS destino, IF (bod_or.Bod_Nom IS NULL ,'Principal',bod_or.Bod_Nom) AS origen";
      $sql="SELECT $campos FROM movimiento_bodega AS mov 
      LEFT JOIN bodega AS bod_or ON mov.Bod_Ori = bod_or.Bod_Cod 
      LEFT JOIN bodega AS bod ON mov.Bod_Des = bod.Bod_Cod 
      INNER JOIN bodega_usuario AS bo_us ON bo_us.Bod_Cod = bod_or.Bod_Cod 
      WHERE mov.Bod_Des IS NULL AND bod_or.Suc_Cod=$_SESSION[Ses_Suc_Cod] GROUP BY mov.Mov_Cod";
      break;
    case 26:
      $search='';
      if ($Par_Sql['search']!='') {
        switch ($Par_Sql['op_opciones']) {
          case 'cat':
            $search=" AND cat.Cat_Des LIKE '%$Par_Sql[search]%' ";
            break;
          case 'des':
            $search=" AND item.Ite_Lar LIKE '%$Par_Sql[search]%' ";
            break;
          case 'cod':
            $search=" AND pro.Pro_Cod = '$Par_Sql[search]' ";
            
            break;
        }
      }

      if ($Par_Sql['Bod_Cod']==0) {
        $bodega_search="bod.Suc_Cod=$_SESSION[Ses_Suc_Cod]";
      }else{
        $bodega_search="bod.Bod_Cod=$Par_Sql[Bod_Cod]";
      }
      if (empty($Par_Sql['limits'])) {
        $campos="COUNT(pro.Pro_Cod) as total";
      }else{
        $campos="(SUM(IFNULL(kar.Kar_Can,0))-SUM(IFNULL(kar.Kar_Sal,0))) AS cantidad, marca.Mar_Des,cat.Cat_Des, uni.Uni_Des, item.Ite_Lar, sum(kar.Kar_Sal)AS salida, sum(kar.Kar_Can) AS entrada, sum(kar.Kar_Can) - sum(kar.Kar_Sal) stocka_act, pro.*";
      }
      $sql="SELECT $campos FROM kardex_ie AS kar INNER JOIN producto AS pro ON pro.Pro_Cod = kar.Pro_Cod INNER JOIN unidad AS uni ON uni.Uni_Cod=pro.Uni_Cod INNER JOIN marca ON marca.Mar_Cod= pro.Mar_Cod  INNER JOIN item ON item.Ite_Cod = pro.Ite_Cod INNER JOIN categorias as cat ON cat.Cat_Cod= item.Cat_Cod INNER JOIN bodega AS bod ON bod.Bod_Cod=kar.Bod_Cod WHERE pro.Pro_Est='A' $search AND $bodega_search GROUP BY pro.Pro_Cod";
      break;
    case 27:
      if ($Par_Sql['Bod_Cod']==0) {
        $bodega_search="bod.Suc_Cod=$_SESSION[Ses_Suc_Cod]";
      }else{
        $bodega_search="bod.Bod_Cod=$Par_Sql[Bod_Cod]";
      }
    if (empty($Par_Sql['limits'])) {
      $campos="COUNT(pro.Pro_Cod) as total";
    }else{
      $campos="aju.Aju_Fec, mov.Mov_Est,bod.Bod_Nom ,mov.Mov_Cod,aju.Aju_Cod,IF (kar.Kar_Sal IS NULL, 0,kar.Kar_Sal)AS Kar_Sal ,
              IF (kar.Kar_Can IS NULL, 0,kar.Kar_Can)AS Kar_Can,
              IF(IFNULL(kar.Kar_Sal,0) > IFNULL(kar.Kar_Can,0),'E','I') AS 
              tipo,item.Ite_Lar, pro.*";
    }
    $sql="SELECT $campos FROM kardex_ie AS kar 
          INNER JOIN producto AS pro ON pro.Pro_Cod = kar.Pro_Cod 
          INNER JOIN item ON item.Ite_Cod = pro.Ite_Cod
          INNER JOIN bodega AS bod ON bod.Bod_Cod=kar.Bod_Cod
          INNER JOIN ajuste_kar AS aju ON aju.Aju_Cod = kar.Aju_Cod
          LEFT JOIN entrada_salida AS en ON en.Aju_Cod = aju.Aju_Cod
          LEFT JOIN movimiento_bodega AS mov ON mov.Mov_Cod = en.Mov_Cod
          WHERE pro.Pro_Est='A' AND $bodega_search AND pro.Pro_Cod=$Par_Sql[ajaxSubgrid] order by aju.Aju_Sys ";
      break;
      case 28:
        $sql="SELECT produ.* FROM productor_banano AS produ INNER JOIN proveedore AS prove ON prove.Prv_Cod= produ.Prv_Cod WHERE prove.Emp_Cod=$_SESSION[Ses_Emp_Cod] AND prove.Prv_Est='A' AND produ.Prd_Est='A' ";
      break;
    }

  return $sql;
}