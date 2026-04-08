<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Alejandro Camahco
 * @version 1.0
 * Fecha de actualizaci�n:	2021/04/09
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package fac.LOGICA
 */

function sentencias_doc($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            break;

        case 1:
            $sql = "UPDATE producto SET Pro_Stk=$Par_Sql[Pro_Stk] WHERE Pro_Cod=$Par_Sql[Pro_Cod];";
            break;

        case 2:
            $sql = "SELECT Kar_Pre, Kar_Ime FROM kardex_ie WHERE Vet_Cod = $Par_Sql[Vet_Cod] AND Pro_Cod = $Par_Sql[Pro_Cod]";
            break;
        case 3:
            $where_doc = "tipo_compr.Tic_Cod=='34'";

            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(ventas.Vet_Cod) AS total";
            } else {
                $campos = "ventas.*,
                vende.Prs_Ape,
                vende.Prs_Nom,
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
                if(ccpp_cobrar.Cpc_Cod is null,'Contado','Credito')as Pago,
                if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                ventas_compr.Com_Cod,
                if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
            }
            $Par_Sql['Tic_Cod'] = (!empty($Par_Sql['Tic_Cod']) ? "AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]" : '');
            if ($Par_Sql['op_opciones'] == 'd') {
                $search = "AND ventas.Vet_Num = '$Par_Sql[search]'";
                $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
            } else {
                $Par_Sql['Cmb_Mes'] = (!empty($Par_Sql['Pec_Cod']) && !empty($Par_Sql['Cmb_Mes']) ? "AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]" : '');
                $Par_Sql['Pec_Cod'] = (!empty($Par_Sql['Pec_Cod']) ? "AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'" : '');
                if ($Par_Sql['op_opciones'] == 'c')
                    $search = "AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                else
                    $search = "AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }
            //ChromePhp::log('MIS_INGRESOS',$Par_Sql['mis_ingresos']);

            if (isset($Par_Sql["mis_ingresos"])) {
                if ($Par_Sql["mis_ingresos"] == 'S') {
                    $filtroUsuario = "AND vendedor.Prs_cod = $_SESSION[Ses_Prs_Cod]";
                }
            } else {
                $filtroUsuario = '';
            }
            $sql = "SELECT $campos FROM ventas
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
                WHERE Tic_Des like '%NOTA DE ENTREGA%' AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $filtroUsuario $search ORDER BY Vet_Num DESC $Par_Sql[limits]  ;";
            //ChromePhp::log($sql);
            break;
        case 4:
            $sql = "UPDATE ventas 
                    INNER JOIN cliente ON ventas.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN empresas ON cliente.Emp_Cod = empresas.Emp_Cod
                    SET Vet_Est= '$Par_Sql[Vet_Est]'
                    WHERE ventas.Vet_Cod = $Par_Sql[Vet_Cod] AND empresas.Emp_Cod = $Par_Sql[Ses_Emp_Cod]";
            //ChromePhp::log($sql);
            return $sql;

            break;

        case 5:
            $sql = "UPDATE comprobantes 
                    LEFT JOIN ventas_compr ON ventas_compr.Com_Cod=comprobantes.Com_Cod
                    LEFT JOIN ventas ON ventas.Vet_Cod = ventas_compr.Vet_Cod
                    INNER JOIN cliente ON ventas.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN empresas ON cliente.Emp_Cod = empresas.Emp_Cod
                    SET comprobantes.Com_Est= '$Par_Sql[Com_Est]'
                    WHERE ventas.Vet_Cod = $Par_Sql[Vet_Cod] AND empresas.Emp_Cod = $Par_Sql[Ses_Emp_Cod]";
            //ChromePhp::log($sql);
            return $sql;

            break;
    }

    return $sql;
}
