<?php
/*FACTURACION*/

function sentencias_proforma($id, $Par_Sql)
{
	$sql = "";
	switch ($id) {
		case 1: /* Update orden de compra */
			$sql = "UPDATE orden_compra SET Prv_Cod='$Par_Sql[Prv_Cod]' , Ord_Fec='$Par_Sql[Ord_Fec]',Ord_Des='$Par_Sql[t_descuento]' , Ord_Obs='$Par_Sql[Vet_Obs]',  Ord_Ord ='$Par_Sql[Ord_Num_Ext]'  WHERE  Ord_Cod='$Par_Sql[Ord_Cod]'";
			break;

		case 2: /* Eliminar detalles Orden de compra */
			$sql = " DELETE FROM orden_comp_det WHERE Ord_Cod='$Par_Sql[Ord_Cod]' AND Pfd_Int='$Par_Sql[Pfd_Int]' AND Pro_Cod='$Par_Sql[Pro_Cod]'";
			break;

		case 3:
			$sql = " SELECT orden_compra.* , Concat(Prs_Nom,' ',Prs_Ape) AS Proveedor,Prs_Ced ,Prs_Dir,Prs_Cor FROM orden_compra 
		INNER JOIN vendedor ON vendedor.Vnd_Cod = orden_compra.Vnd_Cod 
		INNER JOIN proveedore ON proveedore.Prv_Cod = orden_compra.Prv_Cod 
		INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
		WHERE orden_compra.Ord_Est = 'A' AND proveedore.Emp_Cod=$Par_Sql[Emp_Cod]";
			break;
	}
	return $sql;
}
