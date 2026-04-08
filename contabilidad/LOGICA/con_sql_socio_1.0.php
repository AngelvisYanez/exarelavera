<?php
/**
 * sentencias de socio
 */
function sentencias_socio($id,$Par_Sql)
{
    switch ($id){
        //Select que permite extraer todas las ciudades
        case 1:
            $sql = "SELECT Ciu_Cod,Ciu_Des,Pro_Nom,Pas_Nom
                    FROM ciudad
                    INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod
                    INNER JOIN pais ON pais.Pas_Cod=ciudad.Pas_Cod
                    WHERE Ciu_Est='A' AND Ciu_Des IS NOT NULL
                    ORDER BY Ciu_Des";
        return $sql;
        //Select que extrae el Ide_Cod e Ide_Des de la tabla identifica para saber si el registro es una cédula o un R.U.C.
        case 2:
            $sql = "SELECT Ide_Cod,Ide_Des
                    FROM identifica
                    WHERE Ide_Max=$Par_Sql[0]";
        return $sql;
        //Select para extraer la información de una persona de la tabla del mismo nombre
        case 3:
            $sql = "SELECT Prs_Cod,Prs_Ced,Prs_Nom,Prs_Ape,ciudad.Ciu_Cod,Prs_Sex,Prs_Dir,Prs_Tel, Prs_Cor
                    FROM persona,ciudad
                    WHERE Prs_Ced='$Par_Sql[0]' AND persona.Ciu_Cod=ciudad.Ciu_Cod";
        return $sql;
        //Select para extraer la información de un socio de la tabla socio
        case 4:
            $sql = "SELECT socio.Prs_Cod, socio.Soc_Fec
                    FROM persona,socio
                    WHERE Prs_Ced='$Par_Sql[0]' AND socio.Suc_Cod='$_SESSION[Ses_Suc_Cod]' AND persona.Prs_Cod=socio.Prs_Cod";
        return $sql;
        //sentencia sql para agregar una nueva persona
        case 5:
            $sql = "INSERT INTO persona (Ciu_Cod,Ide_Cod,Prs_Ced,Prs_Nom,Prs_Ape,Prs_Sex,Prs_Tel,Prs_Cor,Prs_Dir)
            VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]')";
            //echo $sql;
        return $sql;
        //Update en la tabla persona
        case 6:
            //$Ciu_Cod . '*' . $Ide_Cod . '*' . $Prs_Ced . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Tel . '*' . $Prs_Cor . '*' . $Prs_Dir
            $sql = "UPDATE persona SET Prs_Nom='$Par_Sql[3]',Prs_Ape='$Par_Sql[4]',Prs_Sex='$Par_Sql[5]',Ciu_Cod='$Par_Sql[0]',Prs_Tel='$Par_Sql[6]',Prs_Cor='$Par_Sql[7]',Prs_Dir='$Par_Sql[8]'
            WHERE persona.Prs_Cod='$Par_Sql[2]'";
            //echo $sql;
        return $sql;
        //sentencia sql para insertar un nuevo socio
        case 7:
            $sql = "INSERT INTO socio (Prs_Cod,Suc_Cod,Soc_Est,Soc_Fec)
            VALUES('$Par_Sql[0]','$Par_Sql[1]','A','$Par_Sql[2]')";
            //echo $sql;
        return $sql;
        // sentencia sql para extraer todos los socios
        case 8:
          $totapo="(
                    SELECT SUM(IF(aporte.Apo_Est='A',aporte.Apo_Val,0))
                    FROM aporte, socio
                    WHERE aporte.Soc_Cod=socio.Soc_Cod AND socio.Soc_cod=soci
                    GROUP BY socio.Soc_Cod
                  ) AS totapo";
          if($Par_Sql['op_opciones']=="c") {$search="(persona.Prs_Ced LIKE '%$Par_Sql[search]%')";}
          else {$search="(CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape)) LIKE '%$Par_Sql[search]%'";}
          $campos=empty($Par_Sql['limits'])?" COUNT(socio.Soc_Cod) AS total":" socio.Soc_Cod, socio.Soc_Cod as soci, persona.Prs_Ced, CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape) as nombre, socio.Soc_Fec, IF(persona.Prs_Sex='M','MASCULINO','FEMENINO') Prs_Sex, persona.Prs_Tel, persona.Prs_Cor, persona.Prs_Dir, IF(socio.Soc_Est='A','Activo','Inactivo') AS Soc_Est, persona.Prs_Cod AS persona_cod, ".$totapo."";
          $sql = "SELECT $campos
                  FROM persona,socio
                  WHERE  $search AND socio.Suc_Cod='$_SESSION[Ses_Suc_Cod]' AND persona.Prs_Cod=socio.Prs_Cod $Par_Sql[limits];";;
            // echo $sql;
        return $sql;
        //Select que extrae el datos especificos del socio
        case 9:
            $sql = "SELECT socio.Soc_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, socio.Soc_Fec, Prs_Sex, persona.Prs_Tel, persona.Prs_Cor, persona.Prs_Dir, socio.Soc_Est , ciudad.Ciu_Cod
                    FROM persona, socio, ciudad
                    WHERE socio.Soc_Cod=$Par_Sql[0] AND persona.Prs_Cod=socio.Prs_Cod AND persona.Ciu_Cod=ciudad.Ciu_Cod";
            //echo $sql;
        return $sql;
        //Update en la tabla persona y socio
        case 10:
            $sql = "UPDATE persona,socio SET persona.Prs_Nom='$Par_Sql[1]',persona.Prs_Ape='$Par_Sql[2]', socio.Soc_Fec='$Par_Sql[3]',persona.Prs_Sex='$Par_Sql[4]',
            persona.Prs_Tel='$Par_Sql[5]',persona.Prs_Cor='$Par_Sql[6]',persona.Prs_Dir='$Par_Sql[7]',persona.Ciu_Cod=$Par_Sql[8]
            WHERE socio.Soc_Cod=$Par_Sql[0] AND persona.Prs_Cod=socio.Prs_Cod";
            //echo $sql;
        return $sql;
        //sentencia para extraer todos los tipos de pago
        case 11:
            $sql="SELECT * FROM tipos_pago WHERE For_Cod='1'";
            //echo $sql;
        return $sql;
        //sentencia para obtener el tipo de asiento
        case 12:
            $sql = "SELECT Tia_Cod, Tia_Des, Tia_Abr FROM tipo_asien WHERE Tia_Ini='$Par_Sql[0]' ";
        return $sql;
        //centencia para obtener la cuenta para pagos en efectivo
        case 13:
      		$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
      		FROM det_plan, banco
      		WHERE  det_plan.Pld_Cod=banco.Pld_Cod AND banco.Ban_Tip='C' AND det_plan.Pld_Est='A' AND
      		det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
      		//echo $sql;
    		return $sql;
        //sentencia para obtener la cuenta para los socios
        case 14:
          $sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
          FROM det_plan, tipo_param, plan_param
          WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod AND tipo_param.Tpa_Abr='ACS' AND det_plan.Pld_Est='A' AND
          det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
        return $sql;
        // sentecia para obtener la cuenta para pagos con cheque
        case 15:
      		$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
      		FROM det_plan, banco
      		WHERE  det_plan.Pld_Cod=banco.Pld_Cod AND banco.Ban_Tip='B' AND det_plan.Pld_Est='A' AND
      		det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
      		//echo $sql;
    		return $sql;
        //sentencia para obtener los planes de cuenta de tipo de talle
        case 16:
          if($Par_Sql['op_opciones']=="d") {$search="(det_plan.Pld_Des LIKE '%$Par_Sql[search]%')";}
          else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";}
          $campos=empty($Par_Sql['limits'])?" COUNT(det_plan.Pld_Cod) AS total":" * ";
          $sql = "SELECT $campos
          FROM det_plan
          WHERE  $search AND
          det_plan.Pld_Tip = 'D' AND
          det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
          ORDER BY SUBSTRING_INDEX(Pld_Cdc, '.', -20) $Par_Sql[limits];";
        break;
        // sentencia que retorna los periodos contables del plan de cuenta s de la empresa logueada
        case 17:
          $sql="SELECT plan_cuenta.Pla_Cod, perio_cont.Pec_Cod, perio_cont.Pec_Fei, perio_cont.Pec_Fef, perio_cont.Pec_Est, Year(perio_cont.Pec_Fei) as priodo_m
                FROM plan_cuenta, perio_cont
                WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
                ORDER BY Year(perio_cont.Pec_Fei) DESC";
        return $sql;
        //sentencia para obtener los bancos registrados en la base de datos
        case 19:
          $sql ="SELECT * FROM bancos;";
        return $sql;
        // sentencia sql para insertar cliente
        case 20:
          $sql="INSERT INTO cliente(Prs_Cod, Cli_Est, Emp_Cod)
                VALUES($Par_Sql[0], 'A', '$_SESSION[Ses_Emp_Cod]');";
          // echo $sql;
        return $sql;
        //sentencia sql para insertar un nuevo proveedor
        case 21:
          $sql = "INSERT INTO proveedore(Prs_Cod, Prv_Est, Emp_Cod)
                  VALUES($Par_Sql[0], 'A', '$_SESSION[Ses_Emp_Cod]');";
          // echo $sql;
        return $sql;
        //consulta para saber si una persona ya fue registrada como clientes
        case 22:
            $sql = "SELECT cliente.Prs_Cod, cliente.Cli_Cod
                   FROM persona,cliente
                   WHERE Prs_Ced='$Par_Sql[0]' AND persona.Prs_Cod=cliente.Prs_Cod;";
        return $sql;
        //consulta para saber si una persona ya fue registrada como proveedor
        case 23:
            $sql = "SELECT proveedore.Prs_Cod, proveedore.Prv_Cod
                   FROM persona,proveedore
                   WHERE Prs_Ced='$Par_Sql[0]' AND persona.Prs_Cod=proveedore.Prs_Cod;";
        return $sql;
        //consulta para insertar un comprobante
        case 24:
        //
            $sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Est,Usu_Cod,Com_Gen,Com_Mod)
                    VALUES($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]'
                    , '$Par_Sql[9]', $Par_Sql[10], 'A', '$_SESSION[Ses_Usu_Cod]','A','AS');";
            // echo $sql;
        return $sql;
        //consulta para insertar un asiento
        case 25:
            $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5];";
            // echo $sql;
        return $sql;
        //consulta para insertar una aportacion
        case 26:
            $sql = "INSERT INTO aporte(Soc_Cod,Com_Cod, Che_Cod,Apo_Fec,Apo_Val,Apo_Con)
                    VALUES($Par_Sql[0], '$Par_Sql[1]',$Par_Sql[5], '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]');";
            // echo $sql;
        return $sql;
        //consultar cliente o proveedor por codigo de persona
        case 27:
            $sql = "SELECT $Par_Sql[0] FROM $Par_Sql[1]
                    WHERE $Par_Sql[1].Prs_cod='$Par_Sql[2]';";
            // echo $sql;
        return $sql;
        //sentencia opara insertar un nuevo cheque (cheques_ext)
        case 28:
            $sql="INSERT INTO cheques_ext SET Bak_Cod=$Par_Sql[0], Cli_Cod=$Par_Sql[1], Che_Cta='$Par_Sql[2]', Che_Num='$Par_Sql[3]',".
                        " Che_Fec='$Par_Sql[4]', Che_Cob=null, Che_Val='$Par_Sql[5]', Che_Obs=UPPER('$Par_Sql[6]'), Che_Est='A';";
            // echo $sql;
        return $sql;

        //sentencia que retorna todos los numero de cheques reguistrados de las aportaciones de este socio
        case 29:
          $sql = "SELECT cheques_ext.Che_num FROM cheques_ext
                  WHERE cheques_ext.Cli_Cod = '$Par_Sql[1]' AND
                  cheques_ext.Bak_Cod = '$Par_Sql[0]';";
          // echo $sql;
        return $sql;
        //sentencia que retorna los detalles de la aportacion del socio (aportacion, comprobante, tipo de pago)
        case 30:
          $sql ="SELECT CONCAT(tipo_asien.Tia_Abr, '-', MONTH(comprobantes.Com_Fec), '-', comprobantes.Com_Num) as codigo_compro,
                  aporte.Che_Cod, aporte.Apo_Cod, aporte.Apo_Fec, aporte.Apo_Val, aporte.Apo_Con,
                  comprobantes.Com_Tipo, comprobantes.Com_Cod,comprobantes.Cli_Cod,comprobantes.Prv_Cod,
                  tipos_pago.Pag_Des, tipos_pago.Pag_Cod,
                  socio.Soc_Cod, socio.Prs_cod, tipo_asien.Tia_Cod, comprobantes.Pec_Cod
                 FROM socio, comprobantes, aporte, tipos_pago, tipo_asien
                 WHERE
                 socio.Soc_Cod='$Par_Sql[0]' AND
                 comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND
                 aporte.Com_Cod = comprobantes.Com_Cod AND
                 tipos_pago.Pag_Cod = comprobantes.Com_Tipo AND
                 socio.Soc_Cod = aporte.Soc_Cod AND
                 aporte.Apo_Est = 'A' AND
                 comprobantes.Com_Est = 'A';";
          // echo $sql;
        return $sql;
        //sentencia que retorna todos los asientos contables de una aportacion realizada por un socio
        case 31:
          $sql = "SELECT asientos.Asi_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, asientos.Asi_Deh, asientos.Asi_Val, IF(Asi_Deh='D',Asi_Val,'') AS Debe,IF(Asi_Deh='H',Asi_Val,'') AS Haber
                 FROM comprobantes, asientos, det_plan
                 WHERE
                 comprobantes.Com_Cod = asientos.Com_Cod AND
                 asientos.Pld_Cod = det_plan.Pld_Cod AND
                 comprobantes.Com_Cod = '$Par_Sql[0]';";
          // echo $sql;
        return $sql;
        //dar de baja una aportacion
        case 32:
            $sql = "UPDATE aporte SET Apo_Est='I'
            WHERE aporte.Com_Cod='$Par_Sql[0]'";
            //echo $sql;
        return $sql;
        //dar de baja un comprobante
        case 33:
            $sql = "UPDATE comprobantes SET Com_Est='I'
            WHERE comprobantes.Com_Cod='$Par_Sql[0]'";
            //echo $sql;
        return $sql;
        //dar de baja un cheque (cheques_ext)
        case 34:
            $sql = "UPDATE cheques_ext SET Che_Est='I'
            WHERE cheques_ext.Che_Cod='$Par_Sql[0]'";
            // echo $sql;
        return $sql;
        //sentencia para obtener los socios junto con su total de aportaciones
        case 35:
            $sql = "SELECT  socio.Soc_Cod, socio.Soc_Cod as soci, socio.Soc_Cod as soci, persona.Prs_Ced, CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape) as nombre,
                    socio.Soc_Fec, IF(persona.Prs_Sex='M','MASCULINO','FEMENINO') Prs_Sex, persona.Prs_Tel, persona.Prs_Cor, persona.Prs_Dir,
                    IF(socio.Soc_Est='A','Activo','Inactivo') AS Soc_Est, persona.Prs_Cod AS persona_cod,
                    (
                      SELECT SUM(IF(aporte.Apo_Est='A',aporte.Apo_Val,0))
                      FROM aporte, socio
                      WHERE aporte.Soc_Cod=socio.Soc_Cod AND socio.Soc_cod=soci
                      GROUP BY socio.Soc_Cod
                    ) AS totapo
                    FROM persona,socio
                    WHERE  (persona.Prs_Ced LIKE '%$Par_Sql[0]%') AND socio.Suc_Cod='$_SESSION[Ses_Suc_Cod]' AND persona.Prs_Cod=socio.Prs_Cod ;";
            //echo $sql;
        return $sql;
        //obtener el cheque por el codigo
        case 36:
          $sql="SELECT cheques_ext.Che_Num, bancos.Bak_Des, bancos.Bak_Cod, cheques_ext.Che_Cta, cheques_ext.Che_Fec
               FROM cheques_ext, bancos
               WHERE cheques_ext.Bak_Cod = bancos.Bak_Cod AND
                     cheques_ext.Che_cod = '$Par_Sql[0]';";
        return $sql;
        //para imprimir comprobantes
        case 37:
          $sql= "SELECT Com_Cod, Com_Num, comprobantes.Prv_Cod, comprobantes.Cli_Cod,
                          IF(comprobantes.Prv_Cod IS NULL, prs_cliente.Prs_Ape,prs_provee.Prs_Ape) AS Prs_Ape,
                          IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Nom,prs_provee.Prs_Nom) AS Prs_Nom,
                          IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Dir,prs_provee.Prs_Dir) AS Prs_Dir,
                          IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Tel,prs_provee.Prs_Tel) AS Prs_Tel,
                          IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Ced,prs_provee.Prs_Tel) AS Prs_Ced,
                          Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val,Com_Est, Tia_Ini, Tia_Abr, Tia_Des,Usu_Cod
                      FROM comprobantes
                      INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod
                      LEFT JOIN cliente ON cliente.Cli_Cod=comprobantes.Cli_Cod
                      LEFT JOIN persona AS prs_cliente ON cliente.Prs_Cod=prs_cliente.Prs_Cod
                      LEFT JOIN proveedore ON proveedore.Prv_Cod=comprobantes.Prv_Cod
                      LEFT JOIN persona AS prs_provee ON proveedore.Prs_Cod=prs_provee.Prs_Cod
                      WHERE Com_Cod='$Par_Sql[0]'";
         return $sql;
         //-------------
         case 38:
           $sql="SELECT usuarios.Usu_Cod, usuarios.Usu_Ced, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Est FROM persona, usuarios WHERE persona.Prs_Cod=usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
                      //echo $sql;
         return $sql;
         //sentecia para obtener todas las aportaciones activas de un socio (usada para aportes iniciales)
         case 39:
          $sql="SELECT * FROM aporte WHERE aporte.Com_Cod IS NULL AND aporte.Apo_Est='A' AND Soc_Cod='$Par_Sql[0]'";
         return $sql;
         //sentenica para insertar un aporte inicial
         case 40:
          $sql="INSERT INTO aporte SET Soc_Cod=$Par_Sql[0], Apo_Fec='$Par_Sql[1]', Apo_Val='$Par_Sql[2]', Apo_Con='$Par_Sql[3]';";
         return $sql;
         //sentenica para modificar una aportacion
         case 41:
          $sql="UPDATE aporte SET Che_Cod=$Par_Sql[0], Apo_Fec='$Par_Sql[1]', Apo_Val='$Par_Sql[2]', Apo_Con='$Par_Sql[3]'
                WHERE aporte.Apo_Cod='$Par_Sql[4]';";
          // echo $sql;
         return $sql;
         //sentenica para modificar un comprobante
         case 42:
          $sql="UPDATE comprobantes
                SET Pec_Cod='$Par_Sql[0]', Cli_Cod=$Par_Sql[1], Prv_Cod=$Par_Sql[2], Com_Num='$Par_Sql[3]', Com_Fec='$Par_Sql[4]',
                 Com_Con='$Par_Sql[5]', Com_Tip='$Par_Sql[6]', Com_Val='$Par_Sql[7]', Com_Tipo='$Par_Sql[8]', Tia_Cod='$Par_Sql[9]'
                WHERE comprobantes.Com_Cod='$Par_Sql[10]';";
          // echo $sql;
         return $sql;
         //sentenica para modificar un cheque
         case 43:
          $sql="UPDATE cheques_ext SET Bak_Cod='$Par_Sql[0]', Che_Cta='$Par_Sql[1]', Che_Num='$Par_Sql[2]', Che_Fec='$Par_Sql[3]', Che_Val='$Par_Sql[4]'
                WHERE cheques_ext.Che_Cod='$Par_Sql[5]';";
          // echo $sql;
         return $sql;
         //sentenica para eliminar asientos de acuerdo al codigo de comprobante
         case 44:
          $sql="DELETE FROM asientos WHERE asientos.Com_Cod = '$Par_Sql[0]';";
          // echo $sql;
         return $sql;
         //sentenica para OBTENER codigo del banco
         case 45:
          $sql="SELECT bancos.Bak_Cod, bancos.Bak_Des, cheques_ext.Che_Num, cheques_ext.Che_Cta
                 FROM cheques_ext, bancos
                 WHERE bancos.Bak_Cod = cheques_ext.Bak_Cod AND
                 cheques_ext.Che_Cod='$Par_Sql[0]';";
          // echo $sql;
         return $sql;
         //sentenica para obtener un tipos de pago para aportaciones
         case 46:
          $sql="SELECT * FROM tipos_pago WHERE Pag_Abr = 'OTR' OR Pag_Abr = 'EFE' OR Pag_Abr = 'CHE';";
          // echo $sql;
         return $sql;
         //sentenica para obtener un tipo de pago de acuerdo  su codigo
         case 47:
          $sql="SELECT * FROM tipos_pago WHERE Pag_Cod='$Par_Sql[0]';";
          // echo $sql;
         return $sql;
         //sentenica para SABER SI EXISTE UNA CUENTA PARAMETRIZADA PARA LOS SOCIOS
         case 48:
          $sql="SELECT tipo_param.Tpa_Abr, plan_param.Tpa_Cod, plan_param.Pld_Cod, plan_param.Ppc_Est, det_plan.Pld_Cdc
                 FROM tipo_param, plan_param, det_plan, plan_cuenta
                 WHERE tipo_param.Tpa_Abr ='ACS' AND
                 plan_param.Tpa_Cod = tipo_param.Tpa_Cod AND
                 plan_param.Pld_Cod = det_plan.Pld_Cod AND
                 plan_param.Ppc_Est = 'A' AND
                 det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND
                 plan_cuenta.Emp_Cod='$_SESSION[Ses_Emp_Cod]';";
          // echo $sql;
         return $sql;
         case 49:
          $sql="UPDATE aporte SET Apo_Val='$Par_Sql[0]', Apo_Fec='$Par_Sql[1]'
                WHERE aporte.Apo_Cod='$Par_Sql[2]'";
          // echo $sql;
         return $sql;
    }
    return $sql;
}
