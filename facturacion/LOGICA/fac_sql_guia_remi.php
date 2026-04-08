<?php

/* Sentencias Guias de Remision */

function sentencias_g_remi($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            //echo $sql.'<br/>';
            break;
            /* consulta de vendedor y punto de impresion */
        case 1:
            $sql = "SELECT Vnd_Cod,vendedor.Pun_Cod,puntos_imp.Pun_Des FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND puntos_imp.Pun_Est='A'";
            //echo $sql.'<br/>';
            break;
            /* consultamos la autorizacion para la Guia de Remision */
        case 2:
            $sql = "SELECT 
                        autorizaci.*,                    
                        sucursal.Suc_Sri,
                        puntos_imp.Pun_Des,                    
                        tipo_compr.Tic_Cod,Tic_Des
                    FROM
                        puntos_imp
                        INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
                        INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod)
                        INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
                        INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
                    WHERE
                        tipo_compr.Tic_Sri = '6' AND
                        autorizaci.Aut_Est='A' AND 
                        vendedor.Vnd_Est='A' AND
                        vendedor.Vnd_Cod = '$Par_Sql[0]'";
            //echo $sql.'<br/>';
            break;
        case 3: //Busqueda de transportista
            if ($Par_Sql[2] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%' OR Gpe_Ras LIKE '%$Par_Sql[0]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[4] == "") {
                $campos = "COUNT(Gpe_Cod) as total";
            } else {
                $Par_Sql[4] = "ORDER BY Prs_Ape " . $Par_Sql[4];
                $campos = "  guia_persona.*, persona.Prs_Cod, Prs_Ced, IF(Gpe_Ras IS NULL,CONCAT(Prs_Ape,' ',Prs_Nom),Gpe_Ras) as " . ($Par_Sql[3] == 'T' ? 'transportista' : 'destinatario') . ", Prs_Dir, Prs_Cor";
            }
            $sql = "SELECT $campos FROM guia_persona, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND guia_persona.Prs_Cod=persona.Prs_Cod AND Gpe_Tip='$Par_Sql[3]' AND guia_persona.Emp_Cod = $Par_Sql[1] $Par_Sql[4]";
            //echo $sql.'<br/>';
            break;
        case 4: // usado
            /**
             * Con esta sentencia consulto producto y stock
             */
            if ($Par_Sql[4] == '') $campos = " COUNT(item.Ite_Cod) AS total ";
            else $campos = " item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,Adq_Cor,adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec,stock.*, CONCAT(Ite_Lar,' - ',Pro_Obs)AS product";
            if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
            else $search = " (item.Ite_Lar  LIKE '%$Par_Sql[0]%' OR Pro_Obs  LIKE '%$Par_Sql[0]%' ) ";
            $sql = "SELECT 
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
                        INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod='$Par_Sql[3]'
                    WHERE $search AND Pro_Est='A' AND
                    categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[4]";
            //echo $sql;
            break;
        case 5: // usado
            $Par_Sql['Aut_Sri'] = trim($Par_Sql['Aut_Sri']);
            $sql = "SELECT 
                    CASE         
                        WHEN MAX(Gui_Num)IS NOT NULL AND MAX(Gui_Num)>=$Par_Sql[Aut_Fin] THEN ( 
                            SELECT MIN(t.Gui_Num)+1
                            FROM guias_remis t 
                            INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                            INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                            INNER JOIN sucursal AS ts ON ts.Suc_Cod = tp.Suc_Cod
                            WHERE ts.Emp_Cod=$_SESSION[Ses_Emp_Cod] AND ts.Suc_Sri=$Par_Sql[Suc_Sri] AND ta.Pun_Sri='$Par_Sql[Pun_Sri]' AND ta.Aut_Sri='$Par_Sql[Aut_Sri]' AND ta.Tic_Cod=$Par_Sql[Tic_Cod] AND t.Gui_Num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin] AND
                            NOT EXISTS (
                                SELECT NULL FROM guias_remis n 
                                    INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                    INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                    INNER JOIN sucursal AS ns ON ns.Suc_Cod = np.Suc_Cod
                                    WHERE n.Gui_Num=t.Gui_Num+1 AND ns.Emp_Cod=$_SESSION[Ses_Emp_Cod] AND ns.Suc_Sri=$Par_Sql[Suc_Sri] AND na.Pun_Sri='$Par_Sql[Pun_Sri]' AND na.Aut_Sri='$Par_Sql[Aut_Sri]' AND na.Tic_Cod=$Par_Sql[Tic_Cod] AND n.Gui_Num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]
                                )
                            )            
                        ELSE IFNULL(MAX(Gui_Num),$Par_Sql[Aut_Ini]-1)+1
                        END AS 'next'
                FROM guias_remis
                INNER JOIN autorizaci ON guias_remis.Aut_Cod=autorizaci.Aut_Cod
                INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                INNER JOIN sucursal ON sucursal.Suc_Cod = puntos_imp.Suc_Cod
                WHERE sucursal.Emp_Cod=$_SESSION[Ses_Emp_Cod] AND Suc_Sri=$Par_Sql[Suc_Sri] AND autorizaci.Pun_Sri='$Par_Sql[Pun_Sri]' AND autorizaci.Aut_Sri='$Par_Sql[Aut_Sri]' AND autorizaci.Tic_Cod=$Par_Sql[Tic_Cod] AND Gui_Num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]";
            //echo $sql.'<br/>';
            break;
        case 6: // usado
            $sql = "SELECT COUNT(Gui_Cod)AS total FROM guias_remis 
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = guias_remis.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod INNER JOIN sucursal ON sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    WHERE Emp_Cod=$_SESSION[Ses_Emp_Cod] AND autorizaci.Aut_Sri='$Par_Sql[1]' AND Suc_Sri='$Par_Sql[0]' AND Pun_Sri='$Par_Sql[4]' AND Gui_Num='$Par_Sql[2]'" . (!empty($Par_Sql[3]) ? "AND guias_remis.Gui_Cod<>$Par_Sql[3]" : '') . ';';
            //echo $sql.'<br/>';
            break;
        case 7:
            if ($Par_Sql[2] == "d") {
                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
            } else {
                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
            }
            if ($Par_Sql[3] == "") {
                $campos = "COUNT(Vet_Cod) as total";
            } else {
                $Par_Sql[3] = "ORDER BY Prs_Ape,Vet_Num Desc " . $Par_Sql[3];
                $campos = "ventas.Vet_Cod,Caj_Fec,Prs_Ced,autorizaci.Tic_Cod, tipo_compr.Tic_Des, LPAD(CAST(Tic_Sri AS CHAR),2,'0')AS Tic_Sri,CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Vet_Num AS CHAR),9,'0'))AS Secuencia,CONCAT(Suc_Sri,'-',Pun_Sri,'-')AS Vet_Prefix,Vet_Num,IF(Vet_Xml IS NULL OR TRIM(Vet_Xml)='', Aut_Sri, IF(Vet_Sri IS NULL OR TRIM(Vet_Sri)='','PENDIENTE',Vet_Sri))AS Aut_Sri, CONCAT(Prs_Ape,' ',Prs_Nom)AS cliente";
            }
            if ($_SESSION[Ses_Emp_Cod] == 300) {
                $sql = "SELECT $campos 
                    FROM ventas
                    INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                    INNER JOIN caja_aper ON ventas.Caj_Cod=caja_aper.Caj_Cod 
                    INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod 
                    INNER JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod 
                    INNER JOIN tipo_compr ON autorizaci.Tic_Cod=tipo_compr.Tic_Cod
                    INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                    WHERE Tic_Sri in ('01', '02') AND sucursal.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND $search $Par_Sql[3] ;";
                //echo $sql.'<br/>';
            } else {
                $sql = "SELECT $campos 
                    FROM ventas
                    INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                    INNER JOIN caja_aper ON ventas.Caj_Cod=caja_aper.Caj_Cod 
                    INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod 
                    INNER JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod 
                    INNER JOIN tipo_compr ON autorizaci.Tic_Cod=tipo_compr.Tic_Cod
                    INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                    WHERE Tic_Sri in ('01') AND sucursal.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND $search $Par_Sql[3] ;";
            }
            //ChromePhp::log($sql);
            break;
        case 8:
            $sql = "INSERT INTO guia_persona(Emp_Cod, Prs_Cod, Gpe_Tip, Gpe_Ras, Gpe_Pla, Gpe_Ces, Gpe_Dad, Gpe_Est)
                    VALUES($_SESSION[Ses_Emp_Cod], $Par_Sql[Prs_Cod], '$Par_Sql[Gpe_Tip]', " . (empty($Par_Sql['Gpe_Ras']) ? 'NULL' : "'$Par_Sql[Gpe_Ras]'") . ", " . (empty($Par_Sql['Gpe_Pla']) ? 'NULL' : "'$Par_Sql[Gpe_Pla]'") . ", " . (empty($Par_Sql['Gpe_Ces']) ? 'NULL' : "'$Par_Sql[Gpe_Ces]'") . ", " . (empty($Par_Sql['Gpe_Dad']) ? 'NULL' : "'$Par_Sql[Gpe_Dad]'") . ", 'A');";
            //echo $sql.'<br/>';
            break;
        case 9:
            /* identificacion */
            $sql = "SELECT * FROM identifica WHERE Ide_Prc IS NOT NULL AND Ide_Prc<>'';";
            //echo $sql."<br>";
            break;
        case 10:
            $sql = "INSERT INTO guias_remis(Gpe_Cod, Aut_Cod, Gui_Fec, Gui_Num, Gui_Aut, Gui_Xml, Gui_Pla, Gui_Fei, Gui_Fef, Gui_Dor, Gui_Est, Gui_Obs, Der_Min_Id, Usu_Cod, Bc_Cod)
                VALUES($Par_Sql[Gpe_Cod], $Par_Sql[Aut_Cod], '$Par_Sql[Gui_Fec]', $Par_Sql[Gui_Num], " . (empty($Par_Sql['Gui_Xml']) ? 'NULL' : "'N'") . ", " . (empty($Par_Sql['Gui_Xml']) ? 'NULL' : "'$Par_Sql[Gui_Xml]'") . ",  '$Par_Sql[Gui_Pla]', '$Par_Sql[Gui_Fei]', '$Par_Sql[Gui_Fef]', '$Par_Sql[Gui_Dor]', 'A', '$Par_Sql[Gui_Obs]', " . (empty($Par_Sql['Der_Min_Id']) ? 'NULL' : $Par_Sql['Der_Min_Id']) . ", $_SESSION[Ses_Usu_Cod], " . (empty($Par_Sql['Bc_Cod']) ? 'NULL' : $Par_Sql['Bc_Cod']) . ");";
            //echo $sql.'<br/>';
            break;
        case 11:
            $sql = "INSERT INTO guia_destino(Gui_Cod, Gui_Int, Gpe_Cod, Vet_Cod, Gui_Dde, Gui_Mot, Gui_Rut, Gui_Ces, Gui_Dad)
                    VALUES($Par_Sql[Gui_Cod], $Par_Sql[Gui_Int], $Par_Sql[Gpe_Cod], " . (empty($Par_Sql['Vet_Cod']) ? 'NULL' : $Par_Sql['Vet_Cod']) . ", '$Par_Sql[Gui_Dde]', '$Par_Sql[Gui_Mot]', '$Par_Sql[Gui_Rut]', '$Par_Sql[Gui_Ces]', '$Par_Sql[Gui_Dad]');";
            //echo $sql.'<br/>';
            break;
        case 12:
            $sql = "INSERT INTO guia_det(Gui_Cod, Gui_Int, Gde_Int, Pro_Cod, Gde_Can, Gde_Des)
                    VALUES ($Par_Sql[Gui_Cod], $Par_Sql[Gui_Int], $Par_Sql[Gde_Int], $Par_Sql[Pro_Cod], $Par_Sql[Gde_Can], '$Par_Sql[product]');";
            //echo $sql.'<br/>';
            break;
        case 13:
            $sql = "SELECT Ciu_Cod, Ciu_Des, Pro_Nom  FROM ciudad INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod WHERE Ciu_Des != ''  ORDER BY Ciu_Des ASC";
            //echo $sql;
            break;
        case 14: //Busqueda de Proveedores
            $sql = "SELECT persona.*,CONCAT(Prs_Ape,' ',Prs_Nom) as " . ($Par_Sql[2] == 'T' ? 'transportista' : 'destinatario') . ",'$Par_Sql[2]' AS Gpe_Tip,Gpe_Cod,Emp_Cod,Gpe_Pla,Gpe_Dad,Gpe_Ces,Gpe_Ras FROM persona  
                    LEFT JOIN guia_persona ON guia_persona.Prs_Cod=persona.Prs_Cod AND guia_persona.Emp_Cod = $Par_Sql[1] AND Gpe_Tip='$Par_Sql[2]'
                    WHERE Prs_Ced LIKE '$Par_Sql[0]%' LIMIT 2;";
            //echo $sql;
            break;
        case 15:
            $sql = "INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','$Par_Sql[Prs_Sex]',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod]);";
            //echo $sql.'<br/>';
            break;
        case 16:
            if (empty($Par_Sql['limits'])) $campos = "COUNT(DISTINCT guias_remis.Gui_Cod) AS total";
            else $campos = "guias_remis.*,autorizaci.*,Aut_Tem,persona.Prs_Cod,Prs_Ced,CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Gui_Num AS CHAR),9,'0'))AS Secuencia,
            CONCAT(Suc_Sri,'-',Pun_Sri)AS Pun_Num,Suc_Sri,Pun_Sri,
            IF(Gpe_Ras IS NULL OR Gpe_Ras = '', CONCAT(Prs_Ape, ' ', Prs_Nom), Gpe_Ras) AS transportista,
            IF(Gui_Xml IS NULL OR TRIM(Gui_Xml)='', Aut_Sri, IF(Gui_Sri IS NULL OR TRIM(Gui_Sri)='','PENDIENTE',Gui_Sri))AS Aut_Sri,IF(Aut_Tem='E','ELECTRONICA',Aut_Sri)AS Num_Aut_Sri,Aut_Sri AS Aut_Num,
            fac_derechos_mineros.Der_Min_Codigo, fac_derechos_mineros.Der_Min_Nombre, fac_derechos_mineros.Der_Min_Tipo, fac_derechos_mineros.Der_Min_Titular_Operador, fac_derechos_mineros.Der_Min_Recurso, Tic_Des,
            MONTH(guias_remis.Gui_Fec) AS Mes_Num, YEAR(guias_remis.Gui_Fec) AS Anio_Num,
            CONCAT(guias_remis.Gui_Cod, '_0') AS Grid_Id,
            (SELECT Bc_Nom FROM bodega_clie WHERE bodega_clie.Bc_Cod = guias_remis.Bc_Cod) AS Bodega_Nom,
            (SELECT GROUP_CONCAT(gdet.Gde_Des SEPARATOR ' | ') FROM guia_det gdet WHERE gdet.Gui_Cod = guias_remis.Gui_Cod) AS Gde_Des,
            (SELECT GROUP_CONCAT(gdet.Gde_Can SEPARATOR ' | ') FROM guia_det gdet WHERE gdet.Gui_Cod = guias_remis.Gui_Cod) AS Gde_Can,
            (SELECT GROUP_CONCAT(gdes.Gui_Dde SEPARATOR ' | ') FROM guia_destino gdes WHERE gdes.Gui_Cod = guias_remis.Gui_Cod) AS Gui_Dde,
            (SELECT GROUP_CONCAT(gdes.Gui_Mot SEPARATOR ' | ') FROM guia_destino gdes WHERE gdes.Gui_Cod = guias_remis.Gui_Cod) AS Gui_Mot,
            (SELECT GROUP_CONCAT(p.Prs_Ced SEPARATOR ' | ') FROM guia_destino gdes INNER JOIN guia_persona gp ON gdes.Gpe_Cod = gp.Gpe_Cod INNER JOIN persona p ON gp.Prs_Cod = p.Prs_Cod WHERE gdes.Gui_Cod = guias_remis.Gui_Cod) AS Dest_Ced";

            if ($Par_Sql['op_opciones'] == 'd') {
                $search = "AND guias_remis.Gui_Num = '$Par_Sql[search]'";
                $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
            } else {
                $Par_Sql['Cmb_Mes'] = (!empty($Par_Sql['Pec_Cod']) && !empty($Par_Sql['Cmb_Mes']) ? "AND MONTH(guias_remis.Gui_Fec)=$Par_Sql[Cmb_Mes]" : '');
                $Par_Sql['Pec_Cod'] = (!empty($Par_Sql['Pec_Cod']) ? "AND YEAR(guias_remis.Gui_Fec)=$Par_Sql[Year]" : '');
                if ($Par_Sql['op_opciones'] == 'c')
                    $search = "AND EXISTS (SELECT 1 FROM guia_destino gd 
                                            INNER JOIN guia_persona gp ON gd.Gpe_Cod = gp.Gpe_Cod 
                                            INNER JOIN persona p ON gp.Prs_Cod = p.Prs_Cod 
                                            WHERE gd.Gui_Cod = guias_remis.Gui_Cod 
                                            AND p.Prs_Ced LIKE '$Par_Sql[search]%')";
                elseif ($Par_Sql['op_opciones'] == 'min')
                    $search = "AND (fac_derechos_mineros.Der_Min_Codigo LIKE '%$Par_Sql[search]%' OR fac_derechos_mineros.Der_Min_Nombre LIKE '%$Par_Sql[search]%')";
                else
                    $search = "AND (UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
            }

            // Filtro por sucursal
            $Par_Sql['Suc_Cod'] = (!empty($Par_Sql['Suc_Cod']) && $Par_Sql['Suc_Cod'] != 'T' ? "AND sucursal.Suc_Cod = '" . addslashes($Par_Sql['Suc_Cod']) . "'" : '');

            // Filtro por estado
            // $Par_Sql['op_est'] = (!empty($Par_Sql['op_est']) && $Par_Sql['op_est'] != 'T' ? "AND guias_remis.Gui_Est = '" . addslashes($Par_Sql['op_est']) . "'" : '');
            if (isset($Par_Sql['op_est']) && !empty($Par_Sql['op_est'])) {
                if ($Par_Sql['op_est'] == 'A') {
                    $Par_Sql['op_est'] = " AND guias_remis.Gui_Est = 'A'";
                } elseif ($Par_Sql['op_est'] == 'I') {
                    $Par_Sql['op_est'] = " AND guias_remis.Gui_Est = 'I'";
                } else {
                    $Par_Sql['op_est'] = ''; // Opción T (o cualquier otra) omite la cláusula
                }
            } else {
                $Par_Sql['op_est'] = '';
            }

            $sql = "SELECT $campos FROM guias_remis
                        INNER JOIN guia_persona ON guias_remis.Gpe_Cod=guia_persona.Gpe_Cod
                        INNER JOIN persona ON persona.Prs_Cod=guia_persona.Prs_Cod
                        INNER JOIN autorizaci ON guias_remis.Aut_Cod=autorizaci.Aut_Cod 
                        INNER JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod 
                        INNER JOIN tipo_compr ON autorizaci.Tic_Cod=tipo_compr.Tic_Cod
                        INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                        LEFT JOIN fac_derechos_mineros ON guias_remis.Der_Min_Id = fac_derechos_mineros.Der_Min_Id
                        /*INNER JOIN guia_det ON guias_remis.Gui_Cod = guia_det.Gui_Cod*/
                    WHERE guia_persona.Emp_Cod=$_SESSION[Ses_Emp_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $Par_Sql[Suc_Cod] $Par_Sql[op_est] $search $Par_Sql[limits]
                    ";
            //echo $sql.'<br/>';
            break;
        case 30: // Get Guide Header by Gui_Cod
            $sql = "SELECT guias_remis.*, autorizaci.*, Aut_Tem, persona.Prs_Cod, Prs_Ced, 
            CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Gui_Num AS CHAR),9,'0')) AS Secuencia,
            CONCAT(Suc_Sri,'-',Pun_Sri) AS Pun_Num, Suc_Sri, Pun_Sri,
            IF(Gpe_Ras IS NULL OR Gpe_Ras = '', CONCAT(Prs_Ape, ' ', Prs_Nom), Gpe_Ras) AS transportista,
            IF(Gui_Xml IS NULL OR TRIM(Gui_Xml)='', Aut_Sri, IF(Gui_Sri IS NULL OR TRIM(Gui_Sri)='','PENDIENTE',Gui_Sri)) AS Aut_Sri,
            IF(Aut_Tem='E','ELECTRONICA',Aut_Sri) AS Num_Aut_Sri, autorizaci.Aut_Sri AS Aut_Num,
            fac_derechos_mineros.Der_Min_Codigo, fac_derechos_mineros.Der_Min_Nombre
            FROM guias_remis
            INNER JOIN guia_persona ON guias_remis.Gpe_Cod=guia_persona.Gpe_Cod
            INNER JOIN persona ON persona.Prs_Cod=guia_persona.Prs_Cod
            INNER JOIN autorizaci ON guias_remis.Aut_Cod=autorizaci.Aut_Cod
            INNER JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod
            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
            LEFT JOIN fac_derechos_mineros ON guias_remis.Der_Min_Id = fac_derechos_mineros.Der_Min_Id
            WHERE guias_remis.Gui_Cod = '$Par_Sql[0]'
            LIMIT 1";
            break;
        case 27: // Consulta para reporte de totales de guías de remisión - usar misma estructura que case 16
                if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(DISTINCT guias_remis.Gui_Cod) AS total";
            } else {
                // Usar los mismos campos que case 16 pero con alias para el grid
                $campos = "guias_remis.*,autorizaci.*,Aut_Tem,persona.Prs_Cod,persona.Prs_Ced AS Trans_Ced,CONCAT(sucursal.Suc_Sri,'-',puntos_imp.Pun_Sri,'-',LPAD(CAST(guias_remis.Gui_Num AS CHAR),9,'0'))AS Secuencia,
                CONCAT(sucursal.Suc_Sri,'-',puntos_imp.Pun_Sri)AS Pun_Num,sucursal.Suc_Sri,puntos_imp.Pun_Sri,
                IF(guia_persona.Gpe_Ras IS NULL OR guia_persona.Gpe_Ras = '', CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom), guia_persona.Gpe_Ras) AS Transportista,
                IF(guias_remis.Gui_Xml IS NULL OR TRIM(guias_remis.Gui_Xml)='', autorizaci.Aut_Sri, IF(guias_remis.Gui_Sri IS NULL OR TRIM(guias_remis.Gui_Sri)='','PENDIENTE',guias_remis.Gui_Sri))AS Gui_Aut,IF(Aut_Tem='E','ELECTRONICA',autorizaci.Aut_Sri)AS Num_Aut_Sri,autorizaci.Aut_Sri AS Aut_Num,
                sucursal.Suc_Des,sucursal.Suc_Cod, Tic_Des,
                fac_derechos_mineros.Der_Min_Codigo, fac_derechos_mineros.Der_Min_Nombre, fac_derechos_mineros.Der_Min_Tipo, fac_derechos_mineros.Der_Min_Titular_Operador, fac_derechos_mineros.Der_Min_Recurso,
                MONTH(guias_remis.Gui_Fec) AS Mes_Num, YEAR(guias_remis.Gui_Fec) AS Anio_Num,
                CONCAT(guias_remis.Gui_Cod, '_0') AS Grid_Id,
                (SELECT GROUP_CONCAT(gdes.Gui_Dde SEPARATOR ' | ') FROM guia_destino gdes WHERE gdes.Gui_Cod = guias_remis.Gui_Cod) AS Gui_Dde,
                (SELECT GROUP_CONCAT(gdes.Gui_Mot SEPARATOR ' | ') FROM guia_destino gdes WHERE gdes.Gui_Cod = guias_remis.Gui_Cod) AS Gui_Mot,
                (SELECT GROUP_CONCAT(p.Prs_Ced SEPARATOR ' | ') FROM guia_destino gdes INNER JOIN guia_persona gp ON gdes.Gpe_Cod = gp.Gpe_Cod INNER JOIN persona p ON gp.Prs_Cod = p.Prs_Cod WHERE gdes.Gui_Cod = guias_remis.Gui_Cod) AS Dest_Ced";
            }
            
            // Usar EXACTAMENTE la misma lógica WHERE que case 16
            $where = "guia_persona.Emp_Cod = '" . addslashes($Par_Sql['Emp_Cod']) . "'";
            
            // Filtro por estado
            if (isset($Par_Sql['op_est']) && !empty($Par_Sql['op_est'])) {
                if ($Par_Sql['op_est'] == 'A') {
                    $where .= " AND guias_remis.Gui_Est = 'A'";
                } elseif ($Par_Sql['op_est'] == 'I') {
                    $where .= " AND guias_remis.Gui_Est = 'I'";
                }
            }
            
            // Filtro por rango de fechas
            if (isset($Par_Sql['range']) && $Par_Sql['range'] == 'S' && !empty($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Fin'])) {
                $where .= " AND guias_remis.Gui_Fec BETWEEN '" . addslashes($Par_Sql['Fec_Ini']) . "' AND '" . addslashes($Par_Sql['Fec_Fin']) . "'";
            }
            
            // Filtro por transportista
            if (isset($Par_Sql['cedul']) && $Par_Sql['cedul'] == 'S' && !empty($Par_Sql['Prs_Ced'])) {
                $where .= " AND persona.Prs_Ced LIKE '" . addslashes($Par_Sql['Prs_Ced']) . "%'";
            }
            
            // Filtro por sucursal
            if (isset($Par_Sql['Suc_Cod']) && $Par_Sql['Suc_Cod'] != 'T' && !empty($Par_Sql['Suc_Cod'])) {
                $where .= " AND sucursal.Suc_Cod = '" . addslashes($Par_Sql['Suc_Cod']) . "'";
            }

            // Filtro general (Busqueda) - op_opciones
            if (isset($Par_Sql['op_opciones']) && !empty($Par_Sql['search'])) {
                if ($Par_Sql['op_opciones'] == 'd') {
                    $where .= " AND guias_remis.Gui_Num = '" . addslashes($Par_Sql['search']) . "'";
                } elseif ($Par_Sql['op_opciones'] == 'c') {
                    $where .= " AND EXISTS (SELECT 1 FROM guia_destino gd 
                                                INNER JOIN guia_persona gp ON gd.Gpe_Cod = gp.Gpe_Cod 
                                                INNER JOIN persona p ON gp.Prs_Cod = p.Prs_Cod 
                                            WHERE gd.Gui_Cod = guias_remis.Gui_Cod 
                                            AND p.Prs_Ced LIKE '" . addslashes($Par_Sql['search']) . "%')";
                } elseif ($Par_Sql['op_opciones'] == 'min') {
                    $where .= " AND (fac_derechos_mineros.Der_Min_Codigo LIKE '%" . addslashes($Par_Sql['search']) . "%' OR fac_derechos_mineros.Der_Min_Nombre LIKE '%" . addslashes($Par_Sql['search']) . "%')";
                } else { // 'p' - Transportista
                    $where .= " AND (UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER('%" . addslashes($Par_Sql['search']) . "%'))";
                }
            }
            
            // Ordenamiento
            $orderBy = "";
            if (!empty($Par_Sql['CustomOrderBy'])) {
                $allowedOrders = array(
                    'Gui_Fec ASC', 'Gui_Fec DESC',
                    'Transportista ASC', 'Transportista DESC',
                    'Secuencia ASC', 'Secuencia DESC'
                );
                if (in_array($Par_Sql['CustomOrderBy'], $allowedOrders)) {
                    $orderBy = " ORDER BY " . $Par_Sql['CustomOrderBy'];
                } else {
                    $orderBy = " ORDER BY guias_remis.Gui_Fec DESC, guias_remis.Gui_Num DESC";
                }
            } else {
                $orderBy = " ORDER BY guias_remis.Gui_Fec DESC, guias_remis.Gui_Num DESC";
            }
            
            $limits = isset($Par_Sql['limits']) ? $Par_Sql['limits'] : '';
            
            // Usar EXACTAMENTE la misma estructura de JOINs que case 16
            $sql = "SELECT $campos FROM guias_remis
                        INNER JOIN guia_persona ON guias_remis.Gpe_Cod=guia_persona.Gpe_Cod
                        INNER JOIN persona ON persona.Prs_Cod=guia_persona.Prs_Cod
                        INNER JOIN autorizaci ON guias_remis.Aut_Cod=autorizaci.Aut_Cod 
                        INNER JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod 
                        INNER JOIN tipo_compr ON autorizaci.Tic_Cod=tipo_compr.Tic_Cod
                        INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                        LEFT JOIN fac_derechos_mineros ON guias_remis.Der_Min_Id = fac_derechos_mineros.Der_Min_Id
                        /*INNER JOIN guia_det ON guias_remis.Gui_Cod = guia_det.Gui_Cod*/
                    WHERE $where $orderBy $limits";
            
            // Debug: ver la consulta SQL generada
            error_log("Case 27 SQL: " . $sql);
            //echo $sql.'<br/>';
            break;
        case 17:
            $sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND Pec_Est = 'A' AND plan_cuenta.Emp_Cod= $Par_Sql[0] ORDER BY Pec_Fei Desc";
            //echo $sql.'<br/>';
            break;
        case 18:
            $sql = "SELECT guia_destino.Gui_Int AS Gui_Index,'OTROS' AS Mot_Aux,CONCAT(CAST(guia_destino.Gui_Cod AS CHAR),'_',CAST(guia_destino.Gui_Int AS CHAR))AS id,count(guia_det.Gui_Int)AS items,guia_destino.*, Prs_Ced,IF(Gpe_Ras IS NULL,CONCAT(Prs_Ape,' ',Prs_Nom),Gpe_Ras) AS destinatario,
                        Tic_Des AS Tic_Cod_Txt,CONCAT(Suc_Sri,'-',Pun_Sri)AS Vet_Prefix,Vet_Num,CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Vet_Num AS CHAR),9,'0'))AS Secuencia,CONCAT(Suc_Sri,'-',Pun_Sri,'-')AS Vet_Prefix,Vet_Num,IF(Vet_Xml IS NULL OR TRIM(Vet_Xml)='', Aut_Sri, IF(Vet_Sri IS NULL OR TRIM(Vet_Sri)='','PENDIENTE',Vet_Sri))AS Aut_Sri
                    FROM guia_destino
                        INNER JOIN guia_persona ON guia_destino.Gpe_Cod=guia_persona.Gpe_Cod
                        INNER JOIN guia_det ON guia_destino.Gui_Int=guia_det.Gui_Int AND guia_destino.Gui_Cod=guia_det.Gui_Cod
                        INNER JOIN persona ON persona.Prs_Cod=guia_persona.Prs_Cod
                        LEFT JOIN ventas ON ventas.Vet_Cod=guia_destino.Vet_Cod 
                        LEFT JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod 
                        LEFT JOIN puntos_imp ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod 
                        LEFT JOIN tipo_compr ON autorizaci.Tic_Cod=tipo_compr.Tic_Cod
                        LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                    WHERE guia_destino.Gui_Cod='$Par_Sql[0]' GROUP BY Gui_Cod,Gui_Int ORDER BY Gui_Int ";
            //echo $sql.'<br/>';
            break;
        case 19:
            $sql = "SELECT Gde_Int AS 'index',CONCAT(CAST(Gui_Cod AS CHAR),'_',CAST(Gui_Int AS CHAR),'_',CAST(Gde_Int AS CHAR))AS id, guia_det.*, Uni_Des, Adq_Cor, Adq_Des, Mar_Des, Gde_Des as product FROM guia_det 
                        INNER JOIN producto ON producto.Pro_Cod=guia_det.Pro_Cod
                        INNER JOIN marca ON producto.Mar_Cod=marca.Mar_Cod
                        INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
                        INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod
                    WHERE Gui_Cod='$Par_Sql[0]' AND Gui_Int='$Par_Sql[1]' ORDER BY Gde_Int ;";
            //echo $sql.'<br/>';
            break;
        case 20:
            $sql = "UPDATE guias_remis SET Gui_Est='$Par_Sql[Gui_Est]' WHERE Gui_Cod='$Par_Sql[Gui_Cod]';";
            //echo $sql.'<br/>';
            break;
        case 21:
            $sql = "UPDATE guias_remis SET Gpe_Cod=$Par_Sql[Gpe_Cod], Aut_Cod=$Par_Sql[Aut_Cod], Gui_Fec='$Par_Sql[Gui_Fec]', Gui_Num=$Par_Sql[Gui_Num], Gui_Aut=" . (empty($Par_Sql['Gui_Xml']) ? 'NULL' : "'N'") . ", Gui_Xml=" . (empty($Par_Sql['Gui_Xml']) ? 'NULL' : "'$Par_Sql[Gui_Xml]'") . ", Gui_Pla='$Par_Sql[Gui_Pla]', Gui_Fei='$Par_Sql[Gui_Fei]', Gui_Fef='$Par_Sql[Gui_Fef]', Gui_Dor='$Par_Sql[Gui_Dor]', Gui_Est='A', Gui_Obs='$Par_Sql[Gui_Obs]', Der_Min_Id = " . (empty($Par_Sql['Der_Min_Id']) ? 'NULL' : $Par_Sql['Der_Min_Id']) . ", Usu_Cod=$_SESSION[Ses_Usu_Cod], Bc_Cod=" . (empty($Par_Sql['Bc_Cod']) ? 'NULL' : $Par_Sql['Bc_Cod']) . " WHERE Gui_Cod='$Par_Sql[Gui_Cod]';";
            //echo $sql.'<br/>';
            break;
        case 22:
            $sql = "DELETE FROM guia_destino WHERE Gui_Cod='$Par_Sql[0]';";
            //echo $sql.'<br/>';
            break;
        //NUEVAS SQL PARA EDITAR PROVEEDORES
        case 23:
            $sql = "UPDATE persona SET  Prs_Ape = '$Par_Sql[Prs_Ape]', Prs_Nom = '$Par_Sql[Prs_Nom]',
                Prs_Dir = '$Par_Sql[Prs_Dir]', Prs_Tel = '$Par_Sql[Prs_Tel]', Prs_Cor = '$Par_Sql[Prs_Cor]', Prs_Sex = '$Par_Sql[Prs_Sex]',
                Ciu_Cod = $Par_Sql[Ciu_Cod], Ide_Cod = $Par_Sql[Ide_Cod] WHERE Prs_Cod = '$Par_Sql[Prs_Cod]'";
            //echo $sql.'<br/>';
            break;
            //Esto debe editar
        case 24:
            $sql = "UPDATE guia_persona SET 
                Emp_Cod = $_SESSION[Ses_Emp_Cod],
                Gpe_Tip = '$Par_Sql[Gpe_Tip]',
                Gpe_Ras = '$Par_Sql[Gpe_Ras]', 
                Gpe_Pla = '$Par_Sql[Gpe_Pla]', 
                Gpe_Ces =  " . (empty($Par_Sql['Gpe_Ces']) ? 'NULL' : "'$Par_Sql[Gpe_Ces]'") . ", 
                Gpe_Dad =  " . (empty($Par_Sql['Gpe_Dad']) ? 'NULL' : "'$Par_Sql[Gpe_Dad]'") . ", 
                Gpe_Est = 'A' 
                WHERE Gpe_Cod = $Par_Sql[Gpe_Cod]";
            //echo $sql.'<br/>';
            break;
        case 25: // Consulta para reporte de guías de remisión con todos los datos
            $FILTERS = array();
            // Filtro por estado
            if (isset($Par_Sql['op_est']) && $Par_Sql['op_est'] == 'A') {
                $FILTERS[] = "guias_remis.Gui_Est = 'A'";
            } elseif (isset($Par_Sql['op_est']) && $Par_Sql['op_est'] == 'I') {
                $FILTERS[] = "guias_remis.Gui_Est = 'I'";
            }
            
            // Filtro por rango de fechas
            if (isset($Par_Sql['range']) && $Par_Sql['range'] == 'S' && !empty($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Fin'])) {
                $FILTERS[] = "guias_remis.Gui_Fec BETWEEN '{$Par_Sql['Fec_Ini']}' AND '{$Par_Sql['Fec_Fin']}'";
            }
            
            // Filtro por transportista
            if (isset($Par_Sql['cedul']) && $Par_Sql['cedul'] == 'S' && !empty($Par_Sql['Prs_Ced'])) {
                $FILTERS[] = "persona_trans.Prs_Ced LIKE '{$Par_Sql['Prs_Ced']}%'";
            }
            
            // Filtro por sucursal
            if (isset($Par_Sql['Suc_Cod']) && $Par_Sql['Suc_Cod'] != 'T') {
                $FILTERS[] = "sucursal.Suc_Cod = '{$Par_Sql['Suc_Cod']}'";
            }
            
            $whereClause = !empty($FILTERS) ? "WHERE " . implode(" AND ", $FILTERS) : "";
            
            $sql = "SELECT 
                guias_remis.Gui_Cod,
                guia_destino.Gui_Int,
                guias_remis.Gui_Fec,
                guias_remis.Gui_Num,
                guias_remis.Gui_Pla,
                guias_remis.Gui_Fei,
                guias_remis.Gui_Fef,
                guias_remis.Gui_Dor,
                guias_remis.Gui_Est,
                guias_remis.Gui_Obs,
                CONCAT(sucursal.Suc_Sri,'-',puntos_imp.Pun_Sri,'-',LPAD(CAST(guias_remis.Gui_Num AS CHAR),9,'0')) AS Secuencia,
                IF(guias_remis.Gui_Xml IS NULL OR TRIM(guias_remis.Gui_Xml)='', autorizaci.Aut_Sri, IF(guias_remis.Gui_Sri IS NULL OR TRIM(guias_remis.Gui_Sri)='','PENDIENTE',guias_remis.Gui_Sri)) AS Gui_Aut,
                -- Transportista
                persona_trans.Prs_Ced AS Trans_Ced,
                IF(guia_persona_trans.Gpe_Ras IS NULL OR guia_persona_trans.Gpe_Ras = '', CONCAT(persona_trans.Prs_Ape, ' ', persona_trans.Prs_Nom), guia_persona_trans.Gpe_Ras) AS Transportista,
                -- Destinatario
                persona_dest.Prs_Ced AS Dest_Ced,
                IF(guia_persona_dest.Gpe_Ras IS NULL OR guia_persona_dest.Gpe_Ras = '', CONCAT(persona_dest.Prs_Ape, ' ', persona_dest.Prs_Nom), guia_persona_dest.Gpe_Ras) AS Destinatario,
                guia_destino.Gui_Dde,
                guia_destino.Gui_Mot,
                guia_destino.Gui_Rut,
                guia_destino.Gui_Ces,
                guia_destino.Gui_Dad,
                -- Comprobante de venta
                ventas.Vet_Cod,
                caja_aper.Caj_Fec AS Vet_Fec,
                CONCAT(sucursal_venta.Suc_Sri,'-',puntos_imp_venta.Pun_Sri,'-',LPAD(CAST(ventas.Vet_Num AS CHAR),9,'0')) AS Vet_Secuencia,
                autorizaci_venta.Aut_Sri AS Vet_Aut,
                sucursal.Suc_Des,
                sucursal.Suc_Cod
            FROM guias_remis
            INNER JOIN guia_persona guia_persona_trans ON guias_remis.Gpe_Cod = guia_persona_trans.Gpe_Cod AND guia_persona_trans.Gpe_Tip = 'T'
            INNER JOIN persona persona_trans ON guia_persona_trans.Prs_Cod = persona_trans.Prs_Cod
            INNER JOIN autorizaci ON guias_remis.Aut_Cod = autorizaci.Aut_Cod
            INNER JOIN puntos_imp ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
            INNER JOIN sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod
            LEFT JOIN guia_destino ON guias_remis.Gui_Cod = guia_destino.Gui_Cod
            LEFT JOIN guia_persona guia_persona_dest ON guia_destino.Gpe_Cod = guia_persona_dest.Gpe_Cod AND guia_persona_dest.Gpe_Tip = 'D'
            LEFT JOIN persona persona_dest ON guia_persona_dest.Prs_Cod = persona_dest.Prs_Cod
            LEFT JOIN ventas ON guia_destino.Vet_Cod = ventas.Vet_Cod
            LEFT JOIN caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod
            LEFT JOIN autorizaci autorizaci_venta ON ventas.Aut_Cod = autorizaci_venta.Aut_Cod
            LEFT JOIN puntos_imp puntos_imp_venta ON autorizaci_venta.Pun_Cod = puntos_imp_venta.Pun_Cod
            LEFT JOIN sucursal sucursal_venta ON puntos_imp_venta.Suc_Cod = sucursal_venta.Suc_Cod
            $whereClause
            ORDER BY guias_remis.Gui_Fec DESC, guias_remis.Gui_Num DESC";
            //echo $sql.'<br/>';
            break;
        case 26: // Consulta detalle de productos de guía de remisión
            $sql = "SELECT 
                        Gde_Int AS 'No',
                        producto.Pro_Cod AS 'COD_PRINCIP',
                        producto.Pro_Bar AS 'COD_BAR',
                        Gde_Can AS 'CANTIDAD',
                        unidad.Uni_Des AS 'UNIDAD',
                        Gde_Des AS 'DESCRIPCION',
                        producto.Pro_Obs AS 'PRO_DESCRIPCION'
                    FROM guia_det
                        INNER JOIN producto ON guia_det.Pro_Cod = producto.Pro_Cod
                        INNER JOIN unidad ON producto.Uni_Cod = unidad.Uni_Cod
                    WHERE guia_det.Gui_Cod = '{$Par_Sql[0]}' AND guia_det.Gui_Int = '{$Par_Sql[1]}'
                    ORDER BY guia_det.Gde_Int";
            //echo $sql.'<br/>';
            break;

        // =====================================================
        // DERECHOS MINEROS - Consultas SQL
        // =====================================================
        case 1277:
            /* Consulta lista de derechos mineros activos */
            $sql = "SELECT Der_Min_Id, Der_Min_Codigo, Der_Min_Nombre, Der_Min_Titular_Operador, Der_Min_Tipo, 
                    Der_Min_Ubicacion, Der_Min_Observaciones, Der_Min_Estado, Der_Min_Recurso 
                    FROM fac_derechos_mineros 
                    WHERE Der_Min_Estado = 'A' 
                    ORDER BY Der_Min_Nombre";
            break;

        case 1278:
            /* Insertar nuevo derecho minero */
            $sql = "INSERT INTO fac_derechos_mineros 
                    (Der_Min_Codigo, Der_Min_Nombre, Der_Min_Titular_Operador, Der_Min_Tipo, 
                    Der_Min_Ubicacion, Der_Min_Observaciones, Der_Min_Estado, Der_Min_Fecha_Registro, Der_Min_Recurso) 
                    VALUES 
                    ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', 
                    '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]')";
            break;

        case 1279:
            /* Actualizar derecho minero existente */
            $sql = "UPDATE fac_derechos_mineros SET 
                    Der_Min_Codigo = '$Par_Sql[0]', 
                    Der_Min_Nombre = '$Par_Sql[1]', 
                    Der_Min_Titular_Operador = '$Par_Sql[2]', 
                    Der_Min_Tipo = '$Par_Sql[3]', 
                    Der_Min_Ubicacion = '$Par_Sql[4]', 
                    Der_Min_Observaciones = '$Par_Sql[5]', 
                    Der_Min_Fecha_Modificacion = '$Par_Sql[6]',
                    Der_Min_Recurso = '$Par_Sql[8]' 
                    WHERE Der_Min_Id = $Par_Sql[7]";
            break;

        case 1280:
            /* Consulta lista única de titulares/operadores */
            $sql = "SELECT DISTINCT Der_Min_Titular_Operador 
                    FROM fac_derechos_mineros 
                    WHERE Der_Min_Titular_Operador IS NOT NULL 
                    AND Der_Min_Titular_Operador != '' 
                    ORDER BY Der_Min_Titular_Operador";
            break;

        case 1281:
            /* Consulta catálogo de tipos de derechos mineros */
            $sql = "SELECT Tip_Der_Min_Nombre FROM fac_cat_tipo_derecho_minero 
                    WHERE Tip_Der_Min_Estado = 'A' 
                    ORDER BY Tip_Der_Min_Nombre";
            break;

        case 1282:
            /* Reporte Detallado de Traslados por Derecho Minero */
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(*) AS total";
            } else {
                $campos = "guias_remis.Gui_Cod, guias_remis.Gui_Fec, guias_remis.Gui_Num, guias_remis.Gui_Pla, guias_remis.Gui_Obs,
                        guias_remis.Gui_Dor AS Lugar_Origen,
                        fac_derechos_mineros.Der_Min_Codigo, fac_derechos_mineros.Der_Min_Nombre, fac_derechos_mineros.Der_Min_Tipo, fac_derechos_mineros.Der_Min_Titular_Operador, fac_derechos_mineros.Der_Min_Recurso,
                        guia_destino.Gui_Mot, guia_destino.Gui_Dde AS Lugar_Destino,
                        guia_det.Gde_Can, producto.Pro_Bar, IF(producto.Pro_Obs IS NULL OR producto.Pro_Obs = '', item.Ite_Lar, producto.Pro_Obs) AS Mineral,
                        persona_trans.Prs_Ced AS Trans_Ced, persona_dest.Prs_Ced AS Dest_Ced,
                        CONCAT(sucursal.Suc_Sri, '-', puntos_imp.Pun_Sri, '-', LPAD(guias_remis.Gui_Num, 9, '0')) AS Secuencia,
                        MONTH(guias_remis.Gui_Fec) AS Mes, YEAR(guias_remis.Gui_Fec) AS Anio,
                        IF(ventas.Vta_Num IS NOT NULL AND ventas.Vta_Num != '', CONCAT(Vta_Suc_Sri, '-', Vta_Pun_Sri, '-', LPAD(Vta_Num, 9, '0')), 'GUIA') AS Factura";
            }

            $WHERE = "per_trans.Emp_Cod = $_SESSION[Ses_Emp_Cod] ";
            
            if (!empty($Par_Sql['Der_Min_Id']) && $Par_Sql['Der_Min_Id'] != 'T') {
                $WHERE .= " AND guias_remis.Der_Min_Id = " . $Par_Sql['Der_Min_Id'];
            }
            
            // Filtro por Periodo (Año)
            if (!empty($Par_Sql['Pec_Cod'])) {
                $WHERE .= " AND YEAR(guias_remis.Gui_Fec) = (SELECT YEAR(Pec_Fei) FROM perio_cont WHERE Pec_Cod = " . $Par_Sql['Pec_Cod'] . ")";
            }
            
            // Filtro por Mes
            if (!empty($Par_Sql['Cmb_Mes'])) {
                $WHERE .= " AND MONTH(guias_remis.Gui_Fec) = " . $Par_Sql['Cmb_Mes'];
            }
            
            // Si no se usa mes/año, usar rango de fechas
            if (empty($Par_Sql['Pec_Cod']) && empty($Par_Sql['Cmb_Mes'])) {
                if (!empty($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Fin'])) {
                    $WHERE .= " AND guias_remis.Gui_Fec BETWEEN '{$Par_Sql['Fec_Ini']}' AND '{$Par_Sql['Fec_Fin']}'";
                }
            }

            $sql = "SELECT $campos 
                    FROM guias_remis
                    INNER JOIN fac_derechos_mineros ON guias_remis.Der_Min_Id = fac_derechos_mineros.Der_Min_Id
                    INNER JOIN guia_persona per_trans ON guias_remis.Gpe_Cod = per_trans.Gpe_Cod
                    INNER JOIN persona persona_trans ON per_trans.Prs_Cod = persona_trans.Prs_Cod
                    INNER JOIN autorizaci ON guias_remis.Aut_Cod = autorizaci.Aut_Cod
                    INNER JOIN puntos_imp ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod
                    INNER JOIN guia_destino ON guias_remis.Gui_Cod = guia_destino.Gui_Cod
                    INNER JOIN guia_det ON (guia_destino.Gui_Cod = guia_det.Gui_Cod AND guia_destino.Gui_Int = guia_det.Gui_Int)
                    INNER JOIN producto ON guia_det.Pro_Cod = producto.Pro_Cod
                    INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
                    INNER JOIN guia_persona per_dest ON guia_destino.Gpe_Cod = per_dest.Gpe_Cod
                    INNER JOIN persona persona_dest ON per_dest.Prs_Cod = persona_dest.Prs_Cod
                    LEFT JOIN ventas ON guia_destino.Vet_Cod = ventas.Vta_Cod
                    WHERE $WHERE 
                    ORDER BY guias_remis.Gui_Fec DESC, guias_remis.Gui_Num DESC 
                    $Par_Sql[limits]";
            break;
            
        case 28: // Obtener bodegas históricas de un destinatario (basado en guia_destino)
            $sql = "SELECT DISTINCT gd.Bod_Nom
                    FROM guia_destino gd
                    INNER JOIN guias_remis gr ON gr.Gui_Cod = gd.Gui_Cod
                    WHERE gd.Gpe_Cod = '$Par_Sql[0]'
                    AND gd.Bod_Nom IS NOT NULL 
                    AND gd.Bod_Nom != ''
                    ORDER BY gd.Bod_Nom";
            break;
        case 29:
            $sql = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Emp_Cod = $Par_Sql[0] AND Suc_Est = 'A' ORDER BY Suc_Des ASC";
            break;
        case 31: // Consultar bodegas de cliente
            $sql = "SELECT Bc_Cod, Bc_Nom FROM bodega_clie WHERE Emp_Cod = $Par_Sql[0] ORDER BY Bc_Nom ASC";
            break;
        case 32: // Insertar nueva bodega y retornar ID
            $sql = "INSERT INTO bodega_clie (Bc_Nom, Emp_Cod) VALUES ('" . addslashes($Par_Sql[1]) . "', $Par_Sql[0])";
            break;
        case 33: // Verificar si bodega existe
            $sql = "SELECT Bc_Cod FROM bodega_clie WHERE Emp_Cod = $Par_Sql[0] AND Bc_Nom = '" . addslashes($Par_Sql[1]) . "'";
            break;
    }
    //echo $sql."<br/>";
    return $sql;
}
