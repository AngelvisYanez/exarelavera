<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2016-06-28
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package inv.LOGICA
 */

function guias_remis_email_sql()
{
    return "IFNULL(NULLIF(TRIM((SELECT GROUP_CONCAT(DISTINCT TRIM(p.Prs_Cor) ORDER BY gd.Gui_Int SEPARATOR ',')
            FROM guia_destino gd
            INNER JOIN guia_persona gp ON gd.Gpe_Cod=gp.Gpe_Cod
            INNER JOIN persona p ON gp.Prs_Cod=p.Prs_Cod
            WHERE gd.Gui_Cod=guias_remis.Gui_Cod
            AND p.Prs_Cor IS NOT NULL AND TRIM(p.Prs_Cor)<>'' AND TRIM(p.Prs_Cor)<>'-' AND TRIM(p.Prs_Cor)<>'0')), ''),
        IF(persona.Prs_Cor IS NULL OR TRIM(persona.Prs_Cor)='' OR TRIM(persona.Prs_Cor)='-' OR TRIM(persona.Prs_Cor)='0','',TRIM(persona.Prs_Cor))
    )";
}


function sentencias_facele($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            //echo $sql.'<br/>';
            break;
        case 1:
            $sql = "SELECT IF(Tic_Sri=4,'NOTAS DE CREDITO',IF(Tic_Sri=5,'NOTAS DE DEBITO','VENTAS')) AS Tipo, IF(Tic_Sri=4,'NOTASC',
            IF(Tic_Sri=5,'NOTASD','VENTAS')) AS Type, 'ventas' AS tabla, 'Vet_Sri' AS campo1, 
            'Vet_Aut' AS campo2,  'Vet_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, Caj_Fec AS Doc_Fec, 
            Vet_Num AS Doc_Num, Vet_Cod AS Doc_Cod, Vet_Aut AS Doc_Aut, Vet_Xml AS Doc_Xml, 
            Vet_Sri AS Doc_Sri, '' AS Info_Adi, IF(Cli_Cor IS NULL OR TRIM(Cli_Cor)='' OR TRIM(Cli_Cor)='-',
            IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Cli_Cor)AS Email 
            FROM ventas 
                INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod
                INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=ventas.Tic_Cod
            WHERE Vet_Est='A' AND Vet_Aut='N' AND TRIM(coalesce(Vet_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]' AND $Par_Sql[1]
            ORDER BY Caj_Fec ASC, Doc_Num ASC;";
            //echo $sql.'<br/>';
            break;
        case 2:
            $sql = "SELECT 'RETENCIONES' AS Tipo, 'RETENC' AS Type, 'retencion' AS tabla, 'Ret_Sri' AS campo1, 'Ret_Aut' AS campo2, 'Ret_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, Ret_Fec AS Doc_Fec, Ret_Num AS Doc_Num, Ret_Cod AS Doc_Cod, Ret_Aut AS Doc_Aut, Ret_Xml AS Doc_Xml, Ret_Sri AS Doc_Sri, CONCAT('Doc.# ',compras.Cop_Num) AS Info_Adi, IF(Prv_Cor IS NULL OR TRIM(Prv_Cor)='' OR TRIM(Prv_Cor)='-',IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Prv_Cor)AS Email FROM retencion 
                INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod  
                INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
            WHERE Ret_Est='A' AND Ret_Aut='N' AND TRIM(coalesce(Ret_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]'
            ORDER BY Ret_Fec ASC, Doc_Num ASC;";
            //echo $sql.'<br/>';
            break;
        case 3:
            $sql = "UPDATE llave_elect SET Lla_Est='$Par_Sql[1]' WHERE Emp_Cod='$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 4:
            $sql = "INSERT INTO llave_elect(Emp_Cod,Lla_Rut,Lla_Cla,Lla_Cad) VALUES($Par_Sql[Emp_Cod],'$Par_Sql[Lla_Rut]','$Par_Sql[Lla_Cla]','$Par_Sql[Lla_Cad]');";
            //echo $sql.'<br/>';
            break;
        case 5:
            $sql = "SElECT * FROM llave_elect WHERE Lla_Est='A' AND Emp_Cod=$Par_Sql[0];";
            //echo $sql.'<br/>';
            break;
        case 6:
            $sql = "UPDATE $Par_Sql[tabla] SET $Par_Sql[campo1]='$Par_Sql[numeroAutorizacion]',$Par_Sql[campo2]='S' WHERE $Par_Sql[cod]=$Par_Sql[Doc_Cod] ;";
            //echo $sql.'<br/>';
            break;
        case 7:
            $sql = "SElECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0];";
            //echo $sql.'<br/>';
            break;
        /*case 8:
            $sql = "SELECT 'GUIAS' AS Tipo, 'GUIAS' AS Type, 'guias_remis' AS tabla, 'Gui_Sri' AS campo1, 'Gui_Aut' AS campo2, 'Gui_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, Gui_Fec AS Doc_Fec, Gui_Num AS Doc_Num, Gui_Cod AS Doc_Cod, Gui_Aut AS Doc_Aut, Gui_Xml AS Doc_Xml, Gui_Sri AS Doc_Sri, '' AS Info_Adi FROM guias_remis                 
                        INNER JOIN guia_persona ON guia_persona.Gpe_Cod=guias_remis.Gpe_Cod  
                    WHERE Gui_Est='A' AND Gui_Aut='N' AND TRIM(coalesce(Gui_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]'
                    ORDER BY Gui_Fei ASC, Doc_Num ASC;";
            //echo $sql.'<br/>';
            break;*/

        case 8:
            $sql = "SELECT 'GUIAS' AS Tipo, 'GUIAS' AS Type, 'guias_remis' AS tabla, 'Gui_Sri' AS campo1, 'Gui_Aut' AS campo2, 'Gui_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, Gui_Fec AS Doc_Fec, Gui_Num AS Doc_Num, Gui_Cod AS Doc_Cod, Gui_Aut AS Doc_Aut, Gui_Xml AS Doc_Xml, Gui_Sri AS Doc_Sri, '' AS Info_Adi,
                    " . guias_remis_email_sql() . " AS Email
                    FROM guias_remis
                        INNER JOIN guia_persona ON guia_persona.Gpe_Cod=guias_remis.Gpe_Cod
                        INNER JOIN persona ON persona.Prs_Cod=guia_persona.Prs_Cod
                    WHERE Gui_Est='A' AND Gui_Aut='N' AND TRIM(coalesce(Gui_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]'
                    ORDER BY Gui_Fei ASC, Doc_Num ASC;";
            //echo $sql.'<br/>';
            break;


        case 9:
            $sql = "SELECT IF(Tic_Sri=4,'NOTAS DE CREDITO',IF(Tic_Sri=5,'NOTAS DE DEBITO','VENTAS')) AS Tipo, IF(Tic_Sri=4,'NOTASC',IF(Tic_Sri=5,'NOTASD','VENTAS')) AS Type, 'ventas' AS tabla, 'Vet_Sri' AS campo1, 'Vet_Aut' AS campo2,  'Vet_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, Caj_Fec AS Doc_Fec, Vet_Num AS Doc_Num, Vet_Cod AS Doc_Cod, Vet_Aut AS Doc_Aut, Vet_Xml AS Doc_Xml, Vet_Sri AS Doc_Sri, '' AS Info_Adi, IF(Cli_Cor IS NULL OR TRIM(Cli_Cor)='' OR TRIM(Cli_Cor)='-',IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Cli_Cor)AS Email FROM ventas 
                        INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                        INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                        INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod
                        INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=ventas.Tic_Cod
                    WHERE Vet_Est='A' AND Vet_Aut='S' AND TRIM(coalesce(Vet_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]' AND  Vet_Num='$Par_Sql[2]' AND $Par_Sql[1]
                    ORDER BY Caj_Fec ASC, Doc_Num ASC;";
            //echo $sql.'<br/>';
            break;
        case 10:
            $sql = "SELECT 'RETENCIONES' AS Tipo, 'RETENC' AS Type, 'retencion' AS tabla, 'Ret_Sri' AS campo1, 'Ret_Aut' AS campo2, 'Ret_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, Ret_Fec AS Doc_Fec, Ret_Num AS Doc_Num, Ret_Cod AS Doc_Cod, Ret_Aut AS Doc_Aut, Ret_Xml AS Doc_Xml, Ret_Sri AS Doc_Sri, CONCAT('Doc.# ',compras.Cop_Num) AS Info_Adi, IF(Prv_Cor IS NULL OR TRIM(Prv_Cor)='' OR TRIM(Prv_Cor)='-',IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Prv_Cor)AS Email FROM retencion 
                INNER JOIN compras ON retencion.Cop_Cod=compras.Cop_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=compras.Prv_Cod  
                INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                WHERE Ret_Est='A' AND Ret_Aut='S' AND TRIM(coalesce(Ret_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]' AND Ret_Num='$Par_Sql[1]' ORDER BY Ret_Fec;";
            //echo $sql.'<br/>';
            break;
        /*case 11:
            $sql = "SELECT 'GUIAS' AS Tipo, 'guias_remis' AS tabla, 'Gui_Sri' AS campo1, 'Gui_Aut' AS campo2, 'Gui_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, Gui_Fec AS Doc_Fec, Gui_Num AS Doc_Num, Gui_Cod AS Doc_Cod, Gui_Aut AS Doc_Aut, Gui_Xml AS Doc_Xml, Gui_Sri AS Doc_Sri, '' AS Info_Adi FROM guias_remis                 
                        INNER JOIN guia_persona ON guia_persona.Gpe_Cod=guias_remis.Gpe_Cod  
                    WHERE Gui_Est='A' AND Gui_Aut='S' AND TRIM(coalesce(Gui_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]' AND Gui_Num='$Par_Sql[1]'
                    ORDER BY Gui_Fei ASC, Doc_Num ASC;";
            //echo $sql.'<br/>';
            break;*/
  
        case 11:
            $sql = "SELECT 'GUIAS' AS Tipo, 'GUIAS' AS Type, 'guias_remis' AS tabla, 'Gui_Sri' AS campo1, 'Gui_Aut' AS campo2, 'Gui_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, Gui_Fec AS Doc_Fec, Gui_Num AS Doc_Num, Gui_Cod AS Doc_Cod, Gui_Aut AS Doc_Aut, Gui_Xml AS Doc_Xml, Gui_Sri AS Doc_Sri, '' AS Info_Adi,
                    " . guias_remis_email_sql() . " AS Email
                    FROM guias_remis
                        INNER JOIN guia_persona ON guia_persona.Gpe_Cod=guias_remis.Gpe_Cod
                        INNER JOIN persona ON persona.Prs_Cod=guia_persona.Prs_Cod
                    WHERE Gui_Est='A' AND Gui_Aut='S' AND TRIM(coalesce(Gui_Xml, ''))<>'' AND Emp_Cod='$Par_Sql[0]' AND Gui_Num='$Par_Sql[1]'
                    ORDER BY Gui_Fei ASC, Doc_Num ASC;";
            //echo $sql.'<br/>';
        break;


        case 12:
            $sql = "SELECT 'LIQUIDACION' AS Tipo, 'LIQUIDC' AS Type, 'compras' AS tabla, 
                'Cop_Aut' AS campo1, 'Aut_Cop' AS campo2, 
                'Cop_Cod' AS cod, 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail,
                 Cop_Fec AS Doc_Fec, Cop_Num AS Doc_Num, Cop_Cod AS Doc_Cod,
                Aut_Cop AS Doc_Aut, Cop_Aut AS Doc_Xml, 
                  Tic_Cod AS Doc_Sri, '' AS Info_Adi ,
                   IF(Prv_Cor IS NULL OR TRIM(Prv_Cor)='' OR TRIM(Prv_Cor)='-',
                   IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Prv_Cor) AS Email 
                  FROM compras               
                        INNER JOIN proveedore ON compras.Prv_Cod=proveedore.Prv_Cod
                        INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
                        WHERE Cop_Est='A'  AND  Aut_Cop='N' 
                        AND TRIM(coalesce(Cop_Aut, ''))<>'' 
                        AND Emp_Cod='$Par_Sql[0]' AND Aut_Cod IS NOT NULL
                        AND $Par_Sql[1] 
                        ORDER BY Cop_Num ASC;";
            //echo $sql.'<br/>';
            break;
    }
    //echo $sql."<br/>";
    return $sql;
}
