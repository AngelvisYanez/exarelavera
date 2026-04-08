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
 * @package tesoreria.LOGICA
 */
function sentencias_ven($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {
        case 0:
            $sql="";
            //echo $sql."<br/>";
            break;
        case 1:
            /**
            * Con esta sentencia consulto producto y stock
            */
            if($Par_Sql[3]=='') $campos=" COUNT(item.Ite_Cod) AS total "; 
            else $campos=" item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,		  adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec ";
            if($Par_Sql[2]=='c') $search=" producto.Pro_Bar='$Par_Sql[0]' ";
            else $search=" item.Ite_Lar  LIKE '%$Par_Sql[0]%' ";    
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
                  WHERE 
                  $search AND Pro_Est='A' AND
                  categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql."<br/>";
            break;
        case 2:
            $sql= "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Pro_Uni,Pro_Cdc,Pro_Dsc,Pre_Pvp,Stk_Can,Lin_Des,CONCAT(Lin_Abr,SUBSTRING_INDEX(Cat_Cdc,'.',-1),LPAD(Pro_Ide,5,'0')) AS Cha_Cod
                        FROM producto
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN marca ON producto.Mar_Cod=marca.Mar_Cod
                        INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        INNER JOIN ubicacion ON ubicacion.Ubi_Cod= producto.Ubi_Cod   
                        INNER JOIN unidad ON unidad.Uni_Cod= producto.Uni_Cod  
                        INNER JOIN adquisicio ON adquisicio.Adq_Cod= producto.Adq_Cod  
                        INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
                        INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod 
                        LEFT JOIN lineas ON producto.Lin_Cod=lineas.Lin_Cod
                        WHERE precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0] AND stock.Suc_Cod=$Par_Sql[1]";
            //echo $sql."<br/>";
            break;    

       // Busqueda de facturas por producto
        case 3:
            $sql="SELECT CAST(CONCAT(ventas.Caj_Cod,'_',ventas_det.Vet_Cod,'_',ventas_det.Pro_Cod)AS CHAR) AS Vet_Key,
       cliente.Cli_Cod,Prs_Ced,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Cliente, ventas.Vet_Cod, caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
       producto.Pro_Cod,Ite_Lar,Uni_Des,
       Vet_Can,Vet_Pru,Vet_Imp,IF((Vet_Des+Vet_Dec)=0,NULL,(Vet_Imp*(Vet_Des+Vet_Dec)/100)) AS Descuento,(Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100)) AS SubTotal,IF(Iva_Por=0,NULL,(Vet_Imp*(Iva_Por)/100)) AS Iva,
      ((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100))+(Vet_Imp*Iva_Por/100)) AS Total,
      (((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100))+(Vet_Imp*Iva_Por/100))/Vet_Can) AS Unitario
FROM ventas_det
INNER JOIN iva ON ventas_det.Iva_Cod=iva.Iva_Cod
INNER JOIN producto ON producto.Pro_Cod=ventas_det.Pro_Cod
INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
WHERE cliente.Emp_Cod=$Par_Sql[0] AND sucursal.Suc_Cod=$Par_Sql[1] AND producto.Pro_Cod=$Par_Sql[2]
GROUP BY ventas.Vet_Cod
ORDER BY Prs_Ced,Caj_Fec";
            //echo $sql."<br/>";
            break;    
        case 4://Busqueda de Clientes con array
            if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
            if(isset($Par_Sql["limits"])){
                $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos=" Cli_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as Cliente,Prs_Dir, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
            }
            else{$campos="COUNT(Cli_Cod) as total";$Par_Sql["limits"]="";}
            $sql="SELECT $campos FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            //echo $sql."<br/>";
            break;
         // Busqueda de facturas por cliente
        case 5:
            $sql="SELECT CAST(CONCAT(ventas.Caj_Cod,'_',ventas_det.Vet_Cod,'_',ventas_det.Pro_Cod)AS CHAR) AS Vet_Key,
       cliente.Cli_Cod,Prs_Ced,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Cliente, ventas.Vet_Cod, caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
       producto.Pro_Cod,Ite_Lar,Uni_Des,
       Vet_Can,Vet_Pru,Vet_Imp,IF((Vet_Des+Vet_Dec)=0,NULL,(Vet_Imp*(Vet_Des+Vet_Dec)/100)) AS Descuento,(Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100)) AS SubTotal,IF(Iva_Por=0,NULL,(Vet_Imp*(Iva_Por)/100)) AS Iva,
      ((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100))+(Vet_Imp*Iva_Por/100)) AS Total,
      (((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100))+(Vet_Imp*Iva_Por/100))/Vet_Can) AS Unitario
FROM ventas_det
INNER JOIN iva ON ventas_det.Iva_Cod=iva.Iva_Cod
INNER JOIN producto ON producto.Pro_Cod=ventas_det.Pro_Cod
INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
WHERE cliente.Emp_Cod=$Par_Sql[0] AND sucursal.Suc_Cod=$Par_Sql[1] AND cliente.Cli_Cod=$Par_Sql[2]
GROUP BY ventas.Vet_Cod
ORDER BY Pro_Cod,Caj_Fec";
            //echo $sql."<br/>";
            break;  
        case 6://Busqueda de Vendedores con array
            if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
            if(isset($Par_Sql["limits"])){
                $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos=" Vnd_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as Vendedor,Prs_Dir, IF (Vnd_Est='A','Activo','Inactivo') as Vnd_Est";
            }
            else{$campos="COUNT(Vnd_Cod) as total";$Par_Sql["limits"]="";}
            $sql="SELECT $campos FROM vendedor, persona,puntos_imp,sucursal WHERE $search AND vendedor.Pun_Cod=puntos_imp.Pun_Cod AND sucursal.Suc_Cod=puntos_imp.Suc_Cod AND vendedor.Prs_Cod=persona.Prs_Cod AND sucursal.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            //echo $sql."<br/>";
            break;
        case 7:
            $sql="SELECT CAST(CONCAT(ventas.Caj_Cod,'_',ventas_det.Vet_Cod,'_',ventas_det.Pro_Cod)AS CHAR) AS Vet_Key,
       cliente.Cli_Cod,Prs_Ced,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Cliente, ventas.Vet_Cod, caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
       producto.Pro_Cod,Ite_Lar,Uni_Des,
       Vet_Can,Vet_Pru,Vet_Imp,IF((Vet_Des+Vet_Dec)=0,NULL,(Vet_Imp*(Vet_Des+Vet_Dec)/100)) AS Descuento,(Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100)) AS SubTotal,IF(Iva_Por=0,NULL,(Vet_Imp*(Iva_Por)/100)) AS Iva,
      ((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100))+(Vet_Imp*Iva_Por/100)) AS Total,
      (((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100))+(Vet_Imp*Iva_Por/100))/Vet_Can) AS Unitario
FROM ventas_det
INNER JOIN iva ON ventas_det.Iva_Cod=iva.Iva_Cod
INNER JOIN producto ON producto.Pro_Cod=ventas_det.Pro_Cod
INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
WHERE cliente.Emp_Cod=$Par_Sql[0] AND sucursal.Suc_Cod=$Par_Sql[1] AND ventas.Vnd_Cod=$Par_Sql[2]
GROUP BY ventas.Vet_Cod
ORDER BY Pro_Cod,Caj_Fec";
            //echo $sql."<br/>";
            break;      
    }
    //echo $sql."<br/>";
    return $sql;  
}
