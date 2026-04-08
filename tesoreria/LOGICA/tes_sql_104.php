<?Php

/**
 * Anexo transaacional
 */

function is_var_true($var)
{
    if (empty($var) || is_array($var) || is_object($var)) return false;
    if (in_array($var, array('true', 'True', 'TRUE', 'yes', 'Yes', 'y', 'Y', '1', 'on', 'On', 'ON', true, 1), true)) return true;
    return false;
}
function sentencias_anx($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 1:
            /**
             * Consulta la identificación del archivo xml 
             */
            $sql = "SELECT Emp_Ruc, Emp_Nom, Suc_Dir, Suc_Sri,Suc_Te1, Suc_Fax, Suc_Cor, Emp_Rce, Emp_Rep, Emp_Rco, Emp_Ren,Emp_Rre,Emp_Rco FROM empresas, sucursal
                                                        WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND empresas.Emp_Cod = $Par_Sql[0]";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los Detalles de Ventas de Factura
             */
        case 2:
            $sql = "SELECT 
            SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
            SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
            FROM
            ventas
            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
            INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
            INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
            WHERE
            ventas.Vet_Est = 'A' AND 
            caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] AND iva.Iva_Por = 0";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los Detalles de Ventas de Factura TOTAL
             */
        case 3:
            $sql = "SELECT 
            SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
            SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
            FROM
            ventas
            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
            INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
            INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
            WHERE
            ventas.Vet_Est = 'A' AND 
            caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2]";
            //echo $sql."<br/>";
            break;


            /**
             * Consultando los Detalles de Compras de Factura TOTAL
             */
        case 4:
            $sql = "SELECT SUM(det_compra.Cop_Imp-((det_compra.Cop_Imp * compras.Cop_Des/100)+(det_compra.Cop_Imp*det_compra.Cop_Dec/100)))
            FROM det_compra, iva, adquisicio, compras, proveedore
            WHERE (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND det_compra.Iva_Cod=iva.Iva_Cod 
            AND adquisicio.Adq_Cod=det_compra.Adq_Cod  AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Prv_Cod = proveedore.Prv_Cod 	
            AND proveedore.Emp_Cod= $Par_Sql[2] AND iva.Iva_Por != 0";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los Detalles de Ventas de Factura
             */
        case 5:
            $sql = "SELECT 
            SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
            SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
            FROM
            ventas
            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
            INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
            INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
            WHERE
            ventas.Vet_Est = 'A' AND 
            caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] AND iva.Iva_Por != 0";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los Detalles de Compras de Factura TOTAL
             */
        case 6:
            $sql = "SELECT SUM(det_compra.Cop_Imp-((det_compra.Cop_Imp * compras.Cop_Des/100)+(det_compra.Cop_Imp*det_compra.Cop_Dec/100))) as Importe, iva.Iva_Cod, iva.Iva_Por
            FROM det_compra, iva, adquisicio, compras, proveedore
            WHERE (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND det_compra.Iva_Cod=iva.Iva_Cod 
            AND adquisicio.Adq_Cod=det_compra.Adq_Cod  AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Prv_Cod = proveedore.Prv_Cod 	
            AND proveedore.Emp_Cod= $Par_Sql[2] AND iva.Iva_Por = 0";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los Detalles de Compras de Factura TOTAL
             */
        case 7:
            $sql = "SELECT SUM(det_compra.Cop_Imp-((det_compra.Cop_Imp * compras.Cop_Des/100)+(det_compra.Cop_Imp*det_compra.Cop_Dec/100))) as Importe, iva.Iva_Cod, iva.Iva_Por
            FROM det_compra, iva, adquisicio, compras, proveedore
            WHERE (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND det_compra.Iva_Cod=iva.Iva_Cod 
            AND adquisicio.Adq_Cod=det_compra.Adq_Cod  AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Prv_Cod = proveedore.Prv_Cod 	
            AND proveedore.Emp_Cod= $Par_Sql[2] AND iva.Iva_Por = 0";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los Detalles de Compras de Factura TOTAL
             */
        case 8:
            $sql = "SELECT SUM(det_compra.Cop_Imp-((det_compra.Cop_Imp * compras.Cop_Des/100)+(det_compra.Cop_Imp*det_compra.Cop_Dec/100))) as Importe
            FROM det_compra, iva, adquisicio, compras, proveedore
            WHERE (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND det_compra.Iva_Cod=iva.Iva_Cod 
            AND adquisicio.Adq_Cod=det_compra.Adq_Cod  AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Prv_Cod = proveedore.Prv_Cod 	
            AND proveedore.Emp_Cod= $Par_Sql[2]";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los valores retenidos
             */
        case 9:
            $sql = "SELECT 
                SUM((ROUND(det_retenc.Ret_Bas,2) * renta_iva.Ren_Por) / 100) AS Valor
            FROM
                retencion
                INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
                INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
                INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
                INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
            WHERE Tic_Sri!='0' AND  Cop_Est='A' AND 
                compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
                Ret_Imp = 'I' AND retencion.Ret_Est = 'A' AND
                renta_iva.Ren_Por = '$Par_Sql[2]' AND proveedore.Emp_Cod = '$Par_Sql[3]'";
            //echo $sql."<br/>";
            break;

            /**
             * Consultando los valores retenidos en ventas
             */
        case 10:
            $sql = "SELECT 
            SUM((((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) * renta_iva.Ren_Por) / 100 AS Iva_Ret
            FROM
            ventas
            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
            INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
            INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
            INNER JOIN renta_iva ON (ventas_det.Ren_Iva = renta_iva.Ren_Cod)
            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
            INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
            WHERE Tic_Sri!='0' AND
            ventas.Vet_Est = 'A' AND 
            caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
            cliente.Emp_Cod = $Par_Sql[2]";
            //echo $sql."<br/>";
            break;

            //NUEVOS SQLs   
        case 11:
            /**
             * Consulta la identificación del archivo xml 
             */
            $sql = "SELECT Emp_Ruc, Emp_Nom, Suc_Dir, Suc_Sri,Suc_Te1, Suc_Fax, Suc_Cor, Emp_Rce, Emp_Rep, Emp_Rco, Emp_Ren FROM empresas, sucursal
															WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND empresas.Emp_Cod = $Par_Sql[emp_cod]";
            //echo $sql."<br/>";
            break;
        case 12;
            /*if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';
            else $Par_Sql[3] = '=0';*/
            if (is_var_true($Par_Sql[3])) {
                if ($Par_Sql[6] == 5) {
                    $Par_Sql[3] = '!=0 AND iva.Iva_Por=5';
                } else {
                    $Par_Sql[3] = '!=0 AND iva.Iva_Por!=5';
                }
            } else {
                $Par_Sql[3] = '=0';
            }
            //6 IVA 5 

            if (isset($Par_Sql[4])) {
                $tic_cod = explode('~', $Par_Sql[4]);
                $docu = "(";
                for ($i = 0; $i < count($tic_cod); $i++) {
                    $docu = $docu . "compras.Tic_Cod=$tic_cod[$i]";
                    if ($i < (count($tic_cod) - 1)) $docu = $docu . " OR ";
                }
                $docu = $docu . ")";
            }
            if (isset($Par_Sql[5])) {
                $tri_cod = explode('~', $Par_Sql[5]);
                $sustento = "AND (";
                for ($i = 0; $i < count($tri_cod); $i++) {
                    $sustento = $sustento . "Tri_Cod=$tri_cod[$i]";
                    if ($i < (count($tri_cod) - 1)) $sustento = $sustento . " OR ";
                }
                $sustento = $sustento . ")";
            } else {
                $sustento = "";
            }
            $Cop_Imp = "(det_compra.Cop_Pru * det_compra.Cop_Can)";
            $sql = "SELECT 
                    SUM(IF(Iva_Tip is null, CAST( $Cop_Imp-(($Cop_Imp * compras.Cop_Des/100)+($Cop_Imp*det_compra.Cop_Dec/100)) AS decimal(20,2)), 0)) as Importe,
                    SUM((($Cop_Imp - ((($Cop_Imp * compras.Cop_Des) / 100) + (($Cop_Imp * det_compra.Cop_Dec) / 100))) * Iva_Por) / 100) AS Iva,
                    SUM(IF(Iva_Tip='E',(($Cop_Imp - ((($Cop_Imp * compras.Cop_Des) / 100) + (($Cop_Imp * det_compra.Cop_Dec) / 100))) * Iva_Por) / 100,0)) AS Iva_Dif,
                    SUM(IF(Iva_Por=0 and Iva_Tip is null,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub0,
                    SUM(IF(Iva_Por=5 and Iva_Tip is null,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub5,
                    SUM(IF(Iva_Tip='E',cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subDif,
                    SUM(IF(Iva_Por=12 and Iva_Tip is null,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub12,
                    SUM(IF(Iva_Por=15 and Iva_Tip is null,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub15
                    FROM compras
                        INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                        INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
                        INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                        INNER JOIN adquisicio ON (det_compra.Adq_Cod = adquisicio.Adq_Cod)
                        INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
                    WHERE (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') 
                    AND $docu AND Tic_Sri!='0'                    
                    AND proveedore.Emp_Cod= $Par_Sql[2] AND Cop_Est='A'
                    AND iva.Iva_Por  $Par_Sql[3] $sustento";
            //echo $sql."<br/>";
            break;
        case 13:
            if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';            
            else $Par_Sql[3] = '=0';
            $CreTri=isset($Par_Sql[5])?" Vet_Cre='S' AND ":" (Vet_Cre IS NULL OR Vet_Cre='') AND"; 
            $sql = "SELECT /* codigo 405 */ 
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    /*Solo obtiene si son exportaciones*/
                    /* INNER JOIN exporta_vent ON (exporta_vent.Vet_Cod=ventas_det.Vet_Cod)
		            */
                    LEFT JOIN venta_reembolsos ON ventas.Vet_Cod=venta_reembolsos.Vet_Cod
                    WHERE producto.Adq_Cod<>2 AND venta_reembolsos.Cop_Cod IS NULL AND 
                    ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[4] AND $CreTri 
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] AND iva.Iva_Por  $Par_Sql[3] AND Tic_Sri!='0'
		    ";
            //echo $sql."<br/>";
            break;
        case 14: 
            /* if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';
            else $Par_Sql[3] = '=0';*/
            if (is_var_true($Par_Sql[3])) {
                if ($Par_Sql[5]*1 == 5) {
                    $Par_Sql[3] = ' AND iva.Iva_Por=5';
                } else {
                    $Par_Sql[3] = ' AND iva.Iva_Por !=0 AND iva.Iva_Por!=5';
                }
            } else {
                $Par_Sql[3] = ' AND iva.Iva_Por=0 ';
            }

            $sql = "SELECT /* codigo 404 */ 
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    LEFT JOIN venta_reembolsos ON ventas.Vet_Cod=venta_reembolsos.Vet_Cod
                    WHERE (producto.Adq_Cod=1 OR producto.Adq_Cod=3 OR producto.Adq_Cod=13 OR  producto.Adq_Cod=14) AND venta_reembolsos.Cop_Cod IS NULL AND 
                    ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[4] AND
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2]  $Par_Sql[3] AND Tic_Sri!='0'";
            //echo $sql."<br/>";

            break;
        case 15:
            /* if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0'; 
            if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0 AND iva.Iva_Por!=5 '; 
            else $Par_Sql[3] = '=0';*/

            if (is_var_true($Par_Sql[3])) {
                if ($Par_Sql[6] == 5) {
                    $Par_Sql[3] = ' AND iva.Iva_Por!=0 AND iva.Iva_Por=5';
                } else {
                    $Par_Sql[3] = ' AND iva.Iva_Por!=0 AND iva.Iva_Por!=5';
                }
            } else {
                $Par_Sql[3] = ' AND iva.Iva_Por=0';
            }

            $sql = "SELECT /* Nota Credito 480*/
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
                    INNER JOIN tipos_pago ON (tipos_pago.Pag_Cod = pago_venta.Pag_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    WHERE (producto.Adq_Cod=1 OR producto.Adq_Cod=3 OR producto.Adq_Cod=13 OR  producto.Adq_Cod=14) AND For_Cod='$Par_Sql[5]' AND 
                    ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[4] AND
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] $Par_Sql[3] AND Tic_Sri!='0'";
            //echo $sql."<br/>";
            break;
        case 16:
            $sql = "SELECT DISTINCT
				  renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Con,renta_iva.Ren_Por, 
				  if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret
				FROM
				  det_retenc
				  INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				WHERE
				  proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' AND renta_iva.Ren_Est='A'         
				UNION 
				SELECT renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Con,renta_iva.Ren_Por, 
				  if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret 
				  FROM renta_iva WHERE Ren_Sri='332' AND Ren_Est='A' 
				  AND (SELECT COUNT(compras.Cop_Cod) as total
			   FROM
				 compras
				 INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				 LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)     
			   WHERE
				 proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' AND Ret_Cod IS NULL)>0
				ORDER BY Ren_Ret Desc";
            //echo $sql."<br/>";
            break;
        case 17:
            $sql = "SELECT  
				  renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Por,retencion.Ret_Num,
				  retencion.Ret_Fec,persona.Prs_Ape,persona.Prs_Nom,tipo_compr.Tic_Des,
				  compras.Cop_Num,compras.Cop_Fec,
				  Round(SUM(Ret_Bas),5)as Ret_Bas,
				  Round(SUM(Ret_Bas * Ren_Por)/100, 5) AS Ren_Ret
				FROM
				  det_retenc
				  INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  proveedore.Emp_Cod = '$Par_Sql[0]' AND Ret_Est='A' AND Ren_Sri='$Par_Sql[1]' " . (isset($Par_Sql[5]) ? " AND Ren_Por='$Par_Sql[5]' " : '') . " AND (Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]') AND compras.Cop_Est = '$Par_Sql[4]' AND Tic_Sri!='0'
				GROUP BY
				  compras.Cop_Cod
				order by retencion.Ret_Num, retencion.Ret_Fec Asc";
            //echo $sql;
            break;
        case 18:
            $sql = "SELECT   
                        compras.Cop_Cod,'-' AS Ret_Num,'-' AS Ret_Fec,
                        compras.Prv_Cod,Prs_Ape,Prs_Nom,Cop_Num,Cop_Fec,Cop_Reg,Emp_Cod,
                        ROUND(sum(	CAST( (CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,5) ) - ( CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,5) ) * compras.Cop_Des/100 )) AS decimal(20,5) ) 
  ),5) AS Ret_Bas,0 AS Ren_Ret
                        FROM det_compra
                        INNER JOIN compras ON (compras.Cop_Cod = det_compra.Cop_Cod)
                        INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                        INNER JOIN persona ON (persona.Prs_Cod = proveedore.Prs_Cod)
                        INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
                        LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod AND Ret_Est='A') 
                        LEFT JOIN det_retenc ON (retencion.Ret_Cod=det_retenc.Ret_Cod AND det_retenc.Ret_Int=det_compra.Cop_Int AND Ret_Imp='R')
                        LEFT JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod) 
                        WHERE (retencion.Ret_Cod IS NULL OR det_retenc.Ret_Int IS NULL OR Ren_Sri='332') AND Emp_Cod='$Par_Sql[0]' AND (Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND compras.Cop_Est = '$Par_Sql[3]' AND Tic_Sri!='0' AND Tic_Sri!='4'
			GROUP BY compras.Cop_Cod;";
            //echo $sql.'<br>';
            break;
            //             case 19:
            //		  $sql="SELECT  SUM(det_compra.Cop_Imp) as Cop_Imp, det_compra.Cop_Cod, det_compra.Cop_Int
            //			 FROM det_compra WHERE  det_compra.Cop_Int NOT IN 
            //		  (SELECT det_retenc.Ret_Int FROM retencion INNER JOIN det_retenc ON
            //		   (retencion.Ret_Cod = det_retenc.Ret_Cod) WHERE retencion.Ret_Est = 'A' AND retencion.Cop_Cod =$Par_Sql[0]) AND 
            //		  det_compra.Cop_Cod = $Par_Sql[0]
            //		  GROUP by Cop_Cod"; // AND det_compra.Adq_Cod!=13
            //		  //echo $sql.'<br>';                  
            //		  break;
        case 20: //Busqueda de Proveedores con array
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos = " Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax,Prs_Dir, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
            } else {
                $campos = "COUNT(Prv_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            break;

        case 21: //Listado de tipos de gastos personales
            $sql = "SELECT * FROM agp_tipo WHERE Agp_Est='A' ORDER BY Agp_Max,Agp_Nom;";
            //echo $sql."<br/>";
            break;
        case 22: //Registrar Gasto Personal
            $sql = "INSERT INTO agp_gasto(Agp_Cod,Prv_Cod,Gas_Fec,Gas_Num,Gas_Val,Gas_Obs)
                            VALUES ($Par_Sql[Agp_Cod],$Par_Sql[Prv_Cod],'$Par_Sql[Gas_Fec]','$Par_Sql[Gas_Num]',$Par_Sql[Gas_Val],'$Par_Sql[Gas_Obs]');";
            //echo $sql."<br/>";
            break;
        case 23: //Listado de tipos de gastos personales
            $sql = "SELECT Gas_Cod,agp_gasto.Prv_Cod,agp_gasto.Agp_Cod,Agp_Nom,Gas_Fec,Gas_Num,Gas_Val,Gas_Obs,CONCAT(Prs_Ape,' ',Prs_Nom) AS Proveedor FROM agp_gasto
                            INNER JOIN agp_tipo ON agp_gasto.Agp_Cod=agp_tipo.Agp_Cod
                            INNER JOIN proveedore ON agp_gasto.Prv_Cod=proveedore.Prv_Cod
                            INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod
                            WHERE Gas_Est='A' AND Emp_Cod=$Par_Sql[Emp_Cod] AND Gas_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59' ORDER BY Agp_Max,Agp_Nom ";
            //echo $sql."<br/>";
            break;
        case 24:
            $sql = "SELECT perio_cont.Pec_Cod,perio_cont.Pla_Cod,Pec_Fei,Pec_Fef,Year(Pla_Fec)as Pla_Fec FROM perio_cont 
                    INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE Emp_Cod=$Par_Sql[0] AND Pec_Est='A' AND Pla_Est='A'";
            //echo $sql;               
            break;
        case 25: //Listado de tipos de gastos personales para xml
            $sql = "SELECT COUNT(Gas_Cod)AS docs,SUM(Gas_Val)As total,Agp_Sri,Prs_Ced AS ruc FROM agp_gasto
                        INNER JOIN agp_tipo ON agp_gasto.Agp_Cod=agp_tipo.Agp_Cod
                        INNER JOIN proveedore ON agp_gasto.Prv_Cod=proveedore.Prv_Cod
                        INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod
                        WHERE Gas_Est='A' AND Emp_Cod=$Par_Sql[Emp_Cod] AND Gas_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59'        
                        GROUP BY agp_gasto.Prv_Cod,Agp_Sri ORDER BY Agp_Sri";
            //echo $sql."<br/>";
            break;
        case 26:
            $sql = "SELECT * FROM sucursal INNER JOIN ciudad ON ciudad.Ciu_Cod=sucursal.Ciu_Cod INNER JOIN empresas ON empresas.Emp_Cod=sucursal.Emp_Cod WHERE Suc_Cod=$Par_Sql[0]";
            //echo $sql;               
            break;
        case 27:
            $sql = "SELECT * FROM pais";
            //echo $sql;               
            break;
        case 28:
            $sql = "SELECT * FROM provincia INNER JOIN regiones ON regiones.Reg_Cod=provincia.Reg_Cod WHERE Pas_Cod=$Par_Sql[0]";
            //echo $sql;               
            break;
        case 29:
            $sql = "SELECT * FROM ciudad WHERE Pro_Cod=$Par_Sql[0]";
            //echo $sql;               
            break;
        case 30:
            $sql = "DELETE FROM agp_gasto WHERE Gas_Cod=$Par_Sql[Gas_Cod]";
            //echo $sql;               
            break;
        case 31:
            $sql = "UPDATE agp_gasto SET Agp_Cod=$Par_Sql[Agp_Cod],Prv_Cod='$Par_Sql[Prv_Cod]',Gas_Fec='$Par_Sql[Gas_Fec]',Gas_Num='$Par_Sql[Gas_Num]',Gas_Val=$Par_Sql[Gas_Val],Gas_Obs='$Par_Sql[Gas_Obs]' WHERE Gas_Cod=$Par_Sql[Gas_Cod]";
            //echo $sql;               
            break;

        case 32:
            $sql = "SELECT agp_tipo.Agp_Cod,Agp_Sri,Agp_Nom,COUNT(Gas_Cod)AS docs,SUM(Gas_Val)as total FROM agp_tipo
                    INNER JOIN agp_gasto ON agp_gasto.Agp_Cod=agp_tipo.Agp_Cod
                    INNER JOIN proveedore ON proveedore.Prv_Cod=agp_gasto.Prv_Cod 
                    WHERE Agp_Est='A' AND Gas_Est='A' AND Emp_Cod=$Par_Sql[Emp_Cod] AND Gas_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59' 
                    GROUP BY Agp_Sri";
            //echo $sql;               
            break;
        case 33:
            $sql = "SELECT * FROM agp_gasto
                    WHERE Prv_Cod=$Par_Sql[Prv_Cod] AND Gas_Num='$Par_Sql[Gas_Num]' ";
            //echo $sql; 
            break;
        case 34:
            $sql = "SELECT DISTINCT
                    renta_iva.Ren_Sri, 
                    if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret
                    FROM
                    det_retenc
                    INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
                    INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
                    INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
                    INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                    WHERE
				  proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' AND Ren_Ret='R'      
				UNION 
				SELECT renta_iva.Ren_Sri,
				  if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret 
				  FROM renta_iva WHERE Ren_Sri='332' AND Ren_Est='A' 
				  AND (SELECT COUNT(compras.Cop_Cod) as total
			   FROM
				 compras
				 INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				 LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)     
			   WHERE
				 proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' AND Ret_Cod IS NULL)>0  AND Ren_Ret='R'
				ORDER BY Ren_Ret Desc";
            break;
        case 35:
            $sql = "SELECT 
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    WHERE 
                    ventas.Vet_Est = 'A' AND Tic_Sri=$Par_Sql[4] AND
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2]  AND Tic_Sri!='0'";
            //echo $sql."<br/>";
            break;
        case 36;
            if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';
            else $Par_Sql[3] = '=0';
            if (isset($Par_Sql[5])) {
                $tri_cod = explode('~', $Par_Sql[5]);
                $sustento = "AND (";
                $ban = false;
                for ($i = 0; $i < count($tri_cod); $i++) {
                    $sustento = $sustento . "Tri_Cod=$tri_cod[$i]";
                    $ban = true;
                    if ($i < (count($tri_cod) - 1)) $sustento = $sustento . " OR ";
                }
                $sustento = $sustento . ")";
            } else {
                $sustento = "";
            }
            $sql = "SELECT SUM(det_compra.Cop_Imp-((det_compra.Cop_Imp * compras.Cop_Des/100)+(det_compra.Cop_Imp*det_compra.Cop_Dec/100))) as Importe
                    FROM det_compra, iva, adquisicio, compras, proveedore ,tipo_compr
                    WHERE compras.Tic_Cod = tipo_compr.Tic_Cod AND (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') 
                    AND det_compra.Iva_Cod=iva.Iva_Cod AND compras.Tic_Cod=$Par_Sql[4] AND Tic_Sri!='0'
                    AND adquisicio.Adq_Cod=det_compra.Adq_Cod 
                    AND compras.Cop_Cod=det_compra.Cop_Cod 
                    AND compras.Prv_Cod = proveedore.Prv_Cod 
                    AND proveedore.Emp_Cod= $Par_Sql[2] AND Cop_Est='A'
                    AND iva.Iva_Por $Par_Sql[3] $sustento  
                    AND adquisicio.Adq_Cor='$Par_Sql[6]'  ";
            //echo $sql."<br/>";
            break;
        case 37:
            $sql = "SELECT * FROM iva WHERE Iva_Por>0 AND ('$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin OR (DATE('$Par_Sql[0]')>=Iva_Ini AND Iva_Fin IS NULL) ) ORDER BY Iva_Por DESC"; //compras.Cop_Fec,
            //echo "<br>".$sql;
            break;
        case 38:
            $sql = "SELECT perio_cont.*,YEAR(Pec_Fei) AS Periodo FROM perio_cont INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] ORDER BY Pec_Fei DESC";
            //echo "<br>".$sql;
            break;
            /**
             * Consultando los valores retenidos en ventas
             */
        case 39:
            $sql = "SELECT SUM((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * renta_iva.Ren_Por) / 100 AS Ret_Fue
				FROM ventas
				INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
				INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
				INNER JOIN renta_iva ON (ventas_det.Ren_Cod = renta_iva.Ren_Cod)            
				INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE Tic_Sri!='0' AND
				ventas.Vet_Est = 'A' AND 
				caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
				cliente.Emp_Cod = $Par_Sql[2]";
            //echo $sql."<br/>";
            break;
        case 40:
            if (is_var_true($Par_Sql[3])) $Par_Sql[3] = ' AND iva.Iva_Por !=0 AND iva.Iva_Por!=5 ';
            else $Par_Sql[3] = '=0';
            $sql = "SELECT /*Notas Credito*/
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    /*INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
                    INNER JOIN tipos_pago ON (tipos_pago.Pag_Cod = pago_venta.Pag_Cod)*/
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    WHERE (producto.Adq_Cod=1 OR producto.Adq_Cod=3 OR producto.Adq_Cod=14) /*AND For_Cod='$Par_Sql[5]'*/ AND 
                    ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[4] AND
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] $Par_Sql[3] AND Tic_Sri!='0'";
            //echo $sql."<br/>";
            break;

            //Nuevo caso para obtner los valores del IVA Y TOTAL 
        case 41:
            if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';
            else $Par_Sql[3] = '=0';
            $sql = "SELECT 
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    WHERE (producto.Adq_Cod=1 OR producto.Adq_Cod=3 OR producto.Adq_Cod=14) 
                    AND ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[4] AND
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] AND iva.Iva_Por $Par_Sql[3] AND Tic_Sri!='0'";
            break;


        case 42: //consulta de exportaciones servicios
            if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';
            else $Par_Sql[3] = '=0';
            $sql = "SELECT 
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)

                   

                    /*Solo obtiene si son exportaciones*/
                    INNER JOIN exporta_vent ON (exporta_vent.Vet_Cod=ventas_det.Vet_Cod)
            




                    LEFT JOIN venta_reembolsos ON ventas.Vet_Cod=venta_reembolsos.Vet_Cod
                    WHERE producto.Adq_Cod<>2 AND venta_reembolsos.Cop_Cod IS NULL AND  exporta_vent.Ref_Cod='03' AND 
                    ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[4] AND 
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] AND iva.Iva_Por  $Par_Sql[3] AND Tic_Sri!='0'
            ";
            //echo $sql."<br/>";
            break;


        case 43: //consulta de exportaciones bienes
            if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';
            else $Par_Sql[3] = '=0';
            $sql = "SELECT 
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    /*Solo obtiene si son exportaciones*/
                    INNER JOIN exporta_vent ON (exporta_vent.Vet_Cod=ventas_det.Vet_Cod)
                    LEFT JOIN venta_reembolsos ON ventas.Vet_Cod=venta_reembolsos.Vet_Cod
                    WHERE producto.Adq_Cod<>2 AND venta_reembolsos.Cop_Cod IS NULL AND  exporta_vent.Ref_Cod!='03' AND 
                    ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[4] AND 
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] AND iva.Iva_Por  $Par_Sql[3] AND Tic_Sri!='0'
            ";
            //echo $sql."<br/>";
            break;
        case 44:
           
                if (is_var_true($Par_Sql[3]))
                     $Par_Sql[3] = 'AND iva.Iva_Por!=0';
                else 
                    $Par_Sql[3] = 'AND iva.Iva_Por=0';
           
            $sql = "SELECT 
                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por<>5,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total12,
                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por=5,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total5,
                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por=0,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total0,
                    SUM(IF(ventas.Tic_Cod=5,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS TotalND,
                    SUM(IF(ventas.Tic_Cod=4,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS TotalNC,			
                    SUM(CAST((((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)))) * Iva_Por) / 100 as decimal(10,2))*IF(ventas.Tic_Cod=4,-1,1))AS TotalIva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    LEFT JOIN venta_reembolsos ON ventas.Vet_Cod=venta_reembolsos.Vet_Cod
                    WHERE venta_reembolsos.Cop_Cod  IS NULL /*AND ventas.Tic_Cod=$Par_Sql[4]*/ AND 
                    ventas.Vet_Est = 'A' AND caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] $Par_Sql[3] AND Tic_Sri!='0'";
            //echo $sql."<br/>";
            break;  
        case 45:
            $sql = "SELECT DISTINCT
                    renta_iva.Ren_Sri, 
                    if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret
                    FROM
                    det_retenc
                    INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
                    INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
                    INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
                    INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                    WHERE
				  proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' AND Ren_Ret='R' AND Ret_Est='A' AND Cop_Fec Between '$Par_Sql[1]' AND '$Par_Sql[2]'
				UNION 
				SELECT renta_iva.Ren_Sri,
				  if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret 
				  FROM renta_iva WHERE Ren_Sri='332' AND Ren_Est='A' 
				  AND (SELECT COUNT(compras.Cop_Cod) as total
			   FROM
				 compras
				 INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				 LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)     
			   WHERE
				 proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' AND Ret_Cod IS NULL)>0  AND Ren_Ret='R'
				ORDER BY Ren_Ret Desc";
            //echo $sql;
            break; 
        case 46:  /* SQL JOSE CUMBICOS 2025-09-05     Consulta las vntes segun IVAS*/
           
                if (is_var_true($Par_Sql[3]))
                     $Par_Sql[3] = "AND iva.Iva_Por!=0";
                else 
                    $Par_Sql[3] = 'AND iva.Iva_Por=0';
           
            $sql = "SELECT 
                    SUM(IF(ventas.Tic_Cod=4 and ventas.Tic_Cod<>5 and Iva_Por=12,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total12NC,
                    SUM(IF(ventas.Tic_Cod=4 and ventas.Tic_Cod<>5 and Iva_Por=15,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total15NC,
                    SUM(IF(ventas.Tic_Cod=4 and ventas.Tic_Cod<>5 and Iva_Por=8,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total8NC,
                    SUM(IF(ventas.Tic_Cod=4 and ventas.Tic_Cod<>5 and Iva_Por=5,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total5NC,
                    SUM(IF(ventas.Tic_Cod=4 and ventas.Tic_Cod<>5 and Iva_Por=0,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total0NC,

                    SUM(IF(ventas.Tic_Cod=5 and Iva_Por=12,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total12ND,
                    SUM(IF(ventas.Tic_Cod=5 and Iva_Por=15,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total15ND,
                    SUM(IF(ventas.Tic_Cod=5 and Iva_Por=8,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total8ND,
                    SUM(IF(ventas.Tic_Cod=5 and Iva_Por=5,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total5ND,
                    SUM(IF(ventas.Tic_Cod=5 and Iva_Por=0,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total0ND,

                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por=12,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total12,
                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por=15,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total15,
                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por=8,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total8,
                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por=5,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total5,
                    SUM(IF(ventas.Tic_Cod<>4 and ventas.Tic_Cod<>5 and Iva_Por=0,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS Total0,
                    SUM(IF(ventas.Tic_Cod=5,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS TotalND,
                    SUM(IF(ventas.Tic_Cod=4,CAST((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) as decimal(10,2)),0)) AS TotalNC,			
                    SUM(CAST((((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)))) * Iva_Por) / 100 as decimal(10,2))*IF(ventas.Tic_Cod=4,-1,1))AS TotalIva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    LEFT JOIN venta_reembolsos ON ventas.Vet_Cod=venta_reembolsos.Vet_Cod
                    WHERE venta_reembolsos.Cop_Cod  IS NULL /*AND ventas.Tic_Cod=$Par_Sql[4]*/ AND 
                    ventas.Vet_Est = 'A' AND caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND `cliente`.`Emp_Cod`= $Par_Sql[2] /*$Par_Sql[3]*/ AND Tic_Sri!='0'";
            //echo $sql."<br/>";
            break;  
        case 47:
            $Cop_Imp = "(det_compra.Cop_Pru * det_compra.Cop_Can)";
            $sql = "SELECT 
                    SUM( CAST( $Cop_Imp-(($Cop_Imp * compras.Cop_Des/100)+($Cop_Imp*det_compra.Cop_Dec/100)) AS decimal(20,2)) ) as Importe,
                    SUM(IF(Tic_Sri!=4,(($Cop_Imp - ((($Cop_Imp * compras.Cop_Des) / 100) + (($Cop_Imp * det_compra.Cop_Dec) / 100))) * Iva_Por) / 100,0)) AS Iva,
                    SUM(IF(Tic_Sri=4,(($Cop_Imp - ((($Cop_Imp * compras.Cop_Des) / 100) + (($Cop_Imp * det_compra.Cop_Dec) / 100))) * Iva_Por) / 100,0)) AS Ivanc,
                    SUM(IF(Iva_Por=0 and Tic_Sri=4,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnc0,
                    SUM(IF(Iva_Por=5 and Tic_Sri=4,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnc5,
                    SUM(IF(Iva_Por=8 and Tic_Sri=4,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnc8,
                    SUM(IF(Iva_Por=12 and Tic_Sri=4,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnc12,
                    SUM(IF(Iva_Por=15 and Tic_Sri=4,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnc15,

                    SUM(IF(Iva_Por=0 and Tic_Sri=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnd0,
                    SUM(IF(Iva_Por=5 and Tic_Sri=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnd5,
                    SUM(IF(Iva_Por=8 and Tic_Sri=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnd8,
                    SUM(IF(Iva_Por=12 and Tic_Sri=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnd12,
                    SUM(IF(Iva_Por=15 and Tic_Sri=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as subnd15,
                    
                    SUM(IF(Iva_Por=0 and Tic_Sri!=4 and Tic_Sri!=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub0,
                    SUM(IF(Iva_Por=5 and Tic_Sri!=4 and Tic_Sri!=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub5,
                    SUM(IF(Iva_Por=8 and Tic_Sri!=4 and Tic_Sri!=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub8,
                    SUM(IF(Iva_Por=12 and Tic_Sri!=4 and Tic_Sri!=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub12,
                    SUM(IF(Iva_Por=15 and Tic_Sri!=4 and Tic_Sri!=5,cast( (det_compra.Cop_Pru * det_compra.Cop_Can)-(((det_compra.Cop_Pru * det_compra.Cop_Can) * compras.Cop_Des / 100)+((det_compra.Cop_Pru * det_compra.Cop_Can)* det_compra.Cop_Dec / 100)) as decimal(20, 2)),0)) as sub15
                    FROM compras
                        INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                        INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
                        INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                        INNER JOIN adquisicio ON (det_compra.Adq_Cod = adquisicio.Adq_Cod)
                        INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
                    WHERE (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND Tic_Sri!='0' AND proveedore.Emp_Cod= $Par_Sql[2] AND Cop_Est='A' ";
            break;
        /* Obtenemos el saldo (Mayor) de una cuenta segun fechas */  
        case 48:
            $tipo_asiento = date("m", strtotime($Par_Sql['ini']))==1 && $Par_Sql['mes']*1==1 ? ' AND Tia_Abr="DA"' : ' AND Tia_Abr!="DA"';
            $sql= "SELECT SUM(if(Asi_Deh='D',Asi_Val,0))as saldo_debe, SUM(if(Asi_Deh='H',Asi_Val,0))as saldo_haber
                   FROM
                   asientos
                       INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                       INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)                       
                       INNER JOIN comprobantes ON (asientos.Com_Cod = comprobantes.Com_Cod)
                       INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
                   where plan_cuenta.Emp_Cod='$Par_Sql[Emp_Cod]' and Com_Est='A' AND Com_Fec BETWEEN '$Par_Sql[ini]' and '$Par_Sql[fin]' and asientos.Pld_Cod='$Par_Sql[Pld_Cod]' $tipo_asiento";
            break;
        case 49:
            /* if (is_var_true($Par_Sql[3])) $Par_Sql[3] = '!=0';
            else $Par_Sql[3] = '=0';*/
            if (is_var_true($Par_Sql['iva'])) {
                if ($Par_Sql['Iva_Por']*1 == 5) {
                    $Par_Sql['iva'] = ' AND iva.Iva_Por=5';
                } else {
                    $Par_Sql['iva'] = ' AND iva.Iva_Por !=0 AND iva.Iva_Por!=5';
                }
            } else {
                $Par_Sql['iva'] = ' AND iva.Iva_Por=0 ';
            }
            $CreTri=(!empty($Par_Sql['CreTri']) && $Par_Sql['CreTri']=='S')?" Vet_Cre='S' AND ":" (Vet_Cre IS NULL OR Vet_Cre='' OR Vet_Cre='N') AND"; 
            $sql = "SELECT /* codigo 405 */ 
                    SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
                    SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
                    FROM
                    ventas
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                    INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                    INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                    INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                    INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                    LEFT JOIN venta_reembolsos ON ventas.Vet_Cod=venta_reembolsos.Vet_Cod
                    WHERE $CreTri adquisicio.Adq_Cor in ($Par_Sql[Adq_Cor]) AND venta_reembolsos.Cop_Cod IS NULL AND 
                    ventas.Vet_Est = 'A' AND ventas.Tic_Cod=$Par_Sql[Tic_Cod] AND
                    caja_aper.Caj_Fec BETWEEN '$Par_Sql[ini]' AND '$Par_Sql[fin]' AND `cliente`.`Emp_Cod`= $Par_Sql[Emp_Cod]  $Par_Sql[iva] AND Tic_Sri!='0'";
            //echo $sql."<br/>";
            break;
            /**
             * Consultando los valores retenciones bancaria
             */
        case 59:
            $sql = "SELECT SUM(IF(Ren_Ret='R',(retcrevta_det.Rvt_Bas * renta_iva.Ren_Por) / 100,0)) AS Ret_Fue, SUM(IF(Ren_Ret='I',(retcrevta_det.Rvt_Bas * renta_iva.Ren_Por) / 100,0)) AS Ret_Iva
                FROM retcre_vta
                    INNER JOIN retcrevta_det ON (retcre_vta.Rvt_Cod = retcrevta_det.Rvt_Cod)				
                    INNER JOIN cliente ON (retcre_vta.Cli_Cod = cliente.Cli_Cod)
                    INNER JOIN renta_iva ON (retcrevta_det.Ren_Cod = renta_iva.Ren_Cod)            			        				
				WHERE retcre_vta.Rvt_Est = 'A' AND retcre_vta.Rvt_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND cliente.Emp_Cod = $Par_Sql[2]";
            //echo $sql."<br/>";
            break;
    }
    return $sql;
}
?>