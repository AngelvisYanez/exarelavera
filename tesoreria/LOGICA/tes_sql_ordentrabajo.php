<?php
/**
 * Factura de venta
 */



function sentencias_ordentrabajo($id,$Par_Sql)
{
    switch($id)
    {
        case 1://Listado de clientes
            if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
            if($Par_Sql[3]==""){$campos="COUNT(Cli_Cod) as total";}
            else{
                $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                $campos=" Cli_Cod, persona.Prs_Cod, Prs_Ced, persona.Prs_Sex, persona.Prs_Tel, ciudad.Ciu_Des, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, Prs_Dir, Prs_Cor, Prs_Tel";
            }
            $sql="SELECT $campos FROM cliente
                INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod 
                    WHERE Prs_Ced!='0' 
                    AND $search 
                    AND cliente.Prs_Cod=persona.Prs_Cod 
                    AND Cli_Est='A' 
                    AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            break;
        case 2:
            $sql="SELECT persona.* FROM persona WHERE Prs_Ced LIKE '$Par_Sql[0]%'";
            return $sql;
            break;
        case 3:
            $sql="SELECT Cli_Cod FROM cliente WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";
            return $sql;
            break;
        case 4:
            $sql="SELECT MAX(Ord_Num) as numero FROM ordentrabajo WHERE Emp_Cod = $Par_Sql[Emp_Cod]";
            break;
        case 5:
            $sql="INSERT INTO ordentrabajo (Ord_Num, Cli_Cod, Emp_Cod, Ord_Servicio, Ord_Motor, Ord_Fecha_Rec, Ord_Fecha_Ent, Ord_Partes, Ord_Descripcion, Ord_Repuestos, Ord_Observaciones, Ord_Total, Ord_Abono, Ord_Saldo) VALUES ($Par_Sql[Ord_Num], $Par_Sql[Cli_Cod], $Par_Sql[Emp_Cod], '$Par_Sql[Ord_Servicio]', '$Par_Sql[Ord_Motor]', '$Par_Sql[Ord_Fecha_Rec]', '$Par_Sql[Ord_Fecha_Ent]', '$Par_Sql[Ord_Partes]', '$Par_Sql[Ord_Descripcion]', '$Par_Sql[Ord_Repuestos]', '$Par_Sql[Ord_Observaciones]', $Par_Sql[Ord_Total], $Par_Sql[Ord_Abono], $Par_Sql[Ord_Saldo]);";
        	break;

        case 6:
        	$filtros = '';
        	if($Par_Sql[cliente] != ''){
				$filtros .= " AND (ps.Prs_Nom like '%$Par_Sql[cliente]%' or ps.Prs_Ape like '%$Par_Sql[cliente]%' or ps.Prs_Ced like '%$Par_Sql[cliente]%')";
			}
			if($Par_Sql[txt_fec_entrega] != '' AND $Par_Sql[f_entrega] == 'S'){
				$filtros .= " AND (ot.Ord_Fecha_Ent = '$Par_Sql[txt_fec_entrega]')";
			}
			if($Par_Sql[txt_fec_recepcion] != '' AND $Par_Sql[f_recepcion] == 'S'){
				$filtros .= " AND (ot.Ord_Fecha_Rec = '$Par_Sql[txt_fec_recepcion]')";
			}
	            $sql="SELECT CONCAT(ps.Prs_Nom, ' ', ps.Prs_Ape) as Cliente, ot.* FROM ordentrabajo AS ot 
					JOIN cliente as cl on cl.cli_cod = ot.cli_cod
					join persona as ps on ps.prs_cod = cl.prs_cod
					WHERE ot.emp_cod = $Par_Sql[Emp_Cod]" . $filtros;
            break;

         case 7:
            $sql="SELECT CONCAT(ps.Prs_Nom, ' ', ps.Prs_Ape) as cliente, ot.*, ps.* FROM ordentrabajo AS ot 
					JOIN cliente as cl on cl.cli_cod = ot.cli_cod
					join persona as ps on ps.prs_cod = cl.prs_cod
					WHERE ot.Ord_Cod = $Par_Sql[Ord_Cod]";
            return $sql;
            break;

        case 8:
        $sql="UPDATE ordentrabajo SET Cli_Cod=$Par_Sql[Cli_Cod], Ord_Servicio = '$Par_Sql[Ord_Servicio]', Ord_Motor = '$Par_Sql[Ord_Motor]', Ord_Fecha_Rec = '$Par_Sql[Ord_Fecha_Rec]', Ord_Fecha_Ent = '$Par_Sql[Ord_Fecha_Ent]', Ord_Partes = '$Par_Sql[Ord_Partes]', Ord_Descripcion = '$Par_Sql[Ord_Descripcion]', Ord_Repuestos = '$Par_Sql[Ord_Repuestos]', Ord_Observaciones = '$Par_Sql[Ord_Observaciones]', Ord_Total = $Par_Sql[Ord_Total], Ord_Abono = $Par_Sql[Ord_Abono], Ord_Saldo=$Par_Sql[Ord_Saldo] WHERE Ord_Cod = $Par_Sql[Ord_Cod];";
    	break;

    	case 9:
            $sql="SELECT CONCAT(ps.Prs_Nom, ' ', ps.Prs_Ape) as cliente, ot.*, ps.*, suc.*, emp.* FROM ordentrabajo AS ot 
					JOIN cliente as cl on cl.cli_cod = ot.cli_cod
					JOIN persona as ps on ps.prs_cod = cl.prs_cod
			        JOIN empresas as emp on ot.Emp_Cod = emp.Emp_Cod
			        JOIN sucursal as suc on suc.Emp_cod = emp.Emp_Cod
					WHERE ot.Ord_Cod = $Par_Sql[Ord_Cod]";
            return $sql;
            break;

        case 10:
            $sql="SELECT * FROM ordentrabajo_opciones WHERE Ord_Opc_Tipo = '$Par_Sql[Tipo]' AND Emp_Cod = $Par_Sql[Emp_Cod]";
            return $sql;
            break;

        case 11:
            $sql="INSERT INTO ordentrabajo_opciones(Ord_Opc_Tipo, Ord_Opc_Descripcion, Emp_Cod) VALUES ('$Par_Sql[Tipo]', '$Par_Sql[Descripcion]', $Par_Sql[Emp_Cod]);";
            break;
    }
    return $sql;
}