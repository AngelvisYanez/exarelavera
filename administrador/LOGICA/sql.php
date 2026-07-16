<?php

function sentencias_adm($id, $Par_Sql)
{
  switch ($id) {
      /* Busqueda los ususrios por la cedula */
    case 1:
      $bucar_persona = "SELECT 
  usuarios.Usu_Cod,
  persona.Prs_Cod,
  persona.Prs_Ced,
  persona.Prs_Nom,
  persona.Prs_Ape,
  persona.Prs_Est,
  usuarios.Usu_Est
FROM
  persona
  INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)
  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
WHERE
  sucursal.Emp_Cod = $Par_Sql[0] AND 
  Prs_Ape LIKE  '%$Par_Sql[1]%' ORDER BY Prs_Ape, Prs_Nom ASC";
      //echo $bucar_persona;  
      return $bucar_persona;
      break;

      /*Busqueda de los usuarios por la cedula*/
    case 2:
      $bucar_persona_2 = "SELECT 
  usuarios.Usu_Cod,
  persona.Prs_Cod,
  persona.Prs_Ced,
  persona.Prs_Nom,
  persona.Prs_Ape,
  persona.Prs_Est,
  usuarios.Usu_Est
FROM
  persona
  INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)
  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
WHERE
  sucursal.Emp_Cod = $Par_Sql[0] AND 
  Prs_Ced = '$Par_Sql[1]' ORDER BY Prs_Ape, Prs_Nom ASC";
      //echo $bucar_persona_2;
      return $bucar_persona_2;
      break;

      /* Busqueda de usuarios dependiendo del codigo */
    case 3:
      $consulta = "SELECT usuarios.Usu_Cod, persona.Prs_Ced as Usu_Ced, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Est, usuarios.Suc_Cod, usuarios.Usu_Est, sucursal.Suc_Des, usuarios.Usu_Cad FROM persona, usuarios, sucursal WHERE persona.Prs_Cod=usuarios.Prs_Cod AND usuarios.Suc_Cod = sucursal.Suc_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
      //echo $onsulta;
      return $consulta;
      break;

      /* Busqueda los perifles del usuario */
    case 4:
      $consulta_2 = "SELECT perfiles.Per_Cod, perfiles.Per_Des, perfiles.Per_Est FROM perfiles WHERE perfiles.Emp_Cod = $Par_Sql[0] ORDER BY perfiles.Per_Des";
      return   $consulta_2;
      break;

      /* Busqueda de los usuarios depiendo del codigo de la persona */
    case 5:
      $consulta_5 = "SELECT usuarperfi.Per_Cod, concat(usuarperfi.Per_Cod,perfiles.Per_Est) as Per_Est2, perfiles.Per_Est, perfiles.Per_Des FROM usuarperfi, perfiles WHERE usuarperfi.Per_Cod = perfiles.Per_Cod AND usuarperfi.Usu_Cod=(SELECT Usu_Cod FROM usuarios WHERE Usu_Cod=$Par_Sql[0])";
      //echo $consulta_5;
      return $consulta_5;
      break;

    case 6:
      $rs_codigo = "SELECT Usu_Cod FROM usuarios WHERE Prs_Cod='$Par_Sql[0]'";
      return $rs_codigo;
      break;

      //BORRADO
    case 7:
      $rs_borrado = "DELETE FROM usuarperfi WHERE Usu_Cod='$Par_Sql[0]'";
      return $rs_borrado;
      break;

      ///INSERTAR DATOS
    case 8:
      $rs_guardar = "INSERT INTO usuarperfi (Usu_Cod) VALUES ($Par_Sql[0])";
      return $rs_guardar;
      break;

      ///actualizar
    case 9:
      $rs_usuarios = "UPDATE usuarios SET Usu_Tip = '$Par_Sql[0]' WHERE Usu_Ced = '$Par_Sql[1]'";
      return $rs_usuarios;
      break;

      ///actualiza la clave del ususario

    case 10:
      $rs_clave = "UPDATE usuarios SET Usu_Pal='" . md5($Par_Sql[0]) . "', Usu_Tip = '$Par_Sql[1]' WHERE Usu_Ced = '$Par_Sql[2]'";
      return $rs_clave;
      break;
      ///Consultar sucursales////

    case 11:
      /* Modifica o actualiza los cambios realizados en la tabla de autorozaciones */
      $cons_suc_11 = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Emp_Cod = '$Par_Sql[0]' ORDER BY Suc_Des";
      return $cons_suc_11;
      break;

    case 12:
      /* Consulta el codigo del proceso */
      $consulta_proceso_12 = "SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = '$Par_Sql[0]'";
      //echo $consulta_proceso_12;
      return $consulta_proceso_12;
      break;

    case 13:
      /* Consulta el reporte recursivo */
      $consulta_proceso_13 = "SELECT reportes.Rep_Cod, procesos.Pcs_Nom, reportes.Rep_Ord FROM procesos INNER JOIN reportes ON 
							(procesos.Pcs_Cod = reportes.Rep_Req) WHERE reportes.Pcs_Cod = $Par_Sql[0] ORDER BY
							reportes.Rep_Ord ";
      //echo $consulta_proceso_13;
      return $consulta_proceso_13;
      break;

    case 14:
      /* Consulta realiza la autenticacion de los usuarios */
      $valida_usuario_14 = "SELECT 
  usuarios.Usu_Ced,
  usuarios.Usu_Est,
  usuarios.Suc_Cod,
  sucursal.Emp_Cod,
  usuarios.Prs_Cod,
  usuarios.Usu_Cod,
  persona.Prs_Nom,
  persona.Prs_Ape,
  persona.Prs_Ced,
  persona.Prs_Sex,
  usuarios.Usu_Cad,
  empresas.Emp_Nom,
  empresas.Emp_Log,
  sucursal.Suc_Des,
  empresas.Emp_Cor,
  sucursal.Suc_Web,
  empresas.Emp_Log, 
  usuarios.Usu_Men
FROM
  usuarios
  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
  INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
WHERE
  Usu_Ced = '$Par_Sql[0]' AND 
  Usu_Pal = '" . md5($Par_Sql[1]) . "' AND empresas.Emp_Cod = $Par_Sql[2] AND 
  usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
      //echo $valida_usuario_14;
      return $valida_usuario_14;
      break;

    case 15:
      /* Consulta informacion del sistema */
      $sistema_15 = "SELECT Sys_Nom, Sys_Ver, Sys_Des, Sys_Cor FROM system";
      //echo $sistema_15;
      return $sistema_15;
      break;

    case 16:
      /* Consulta los organizado del nivel 0 */
      $perfiles_16 = /*"SELECT organizado.Org_Det,
  organizado.Org_Ord,
  organizado.Org_Des,
  organizado.Org_Niv,
  organizado.Org_Cod,
  organizado.Org_Img,
  organizado.Org_Ime FROM organizado
WHERE organizado.Org_Cod IN

(SELECT organizado.Org_Niv FROM organizado
WHERE organizado.Org_Cod IN
(SELECT 
  organizado.Org_Niv
FROM
  procesos
  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
  INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod)
WHERE
  (".$Par_Sql[0]."))) ORDER BY organizado.Org_Ord";	*/
        "(SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN (SELECT organizado.Org_Niv FROM organizado WHERE organizado.Org_Cod IN 
(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
 (procesos.Org_Cod = organizado.Org_Cod) WHERE (" . $Par_Sql[0] . "))) ORDER BY organizado.Org_Ord) 
 UNION DISTINCT
 (SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN  
(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
 (procesos.Org_Cod = organizado.Org_Cod) WHERE (" . $Par_Sql[0] . ")) AND organizado.Org_Niv = 0 ORDER BY organizado.Org_Ord)
 UNION DISTINCT
 (SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN  
(SELECT procesos.Org_Cod FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) WHERE (" . $Par_Sql[0] . ")) AND organizado.Org_Niv = 0 ORDER BY organizado.Org_Ord)";
      //echo $perfiles_16; 
      return $perfiles_16;
      break;

    case 17:
      /* Consulta los organizados del arbol del siguiente nivel 1 */
      $organizado_17 =
        "SELECT DISTINCT organizado.Org_Det,
  organizado.Org_Ord,
  organizado.Org_Des,
  organizado.Org_Niv,
  organizado.Org_Cod,
  organizado.Org_Img,
  organizado.Org_Ime  FROM organizado
WHERE organizado.Org_Cod IN
(SELECT 
  organizado.Org_Niv
FROM
  procesos
  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
  INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod)
WHERE
  (" . $Par_Sql[0] . ")) AND organizado.Org_Niv = $Par_Sql[0]";
      //echo $organizado_17."<br>";
      return $organizado_17;
      break;

    case 18:
      /* Consulta los procesos del arbol */
      $procesos_18 = "SELECT DISTINCT procesos.Pcs_Lin, rutas.Rut_Des,
procesos.Pcs_Nom, procesos.Pcs_Img, procesos.Pcs_Det
FROM
  rutas
  INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
WHERE
procesos.Pcs_Est='A' AND procesos.Pcs_Tip = '$Par_Sql[2]'
AND procesos.Org_Cod=$Par_Sql[0] AND (" . $Par_Sql[1] . ")
ORDER BY procesos.Pcs_Ord";
      //echo $procesos_18;
      return $procesos_18;
      break;
    case 19:
      /* Consulta las letras asignadas al perfil */
      $procesos_19 = "SELECT 
  perfiorgan.Pcs_Cod
FROM
  procesos
  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
WHERE
  (" . $Par_Sql[0] . ") AND 
  procesos.Pcs_Nom = '$Par_Sql[1]'";
      //echo $procesos_19;
      return $procesos_19;
      break;

    case 20:
      /* Consulta la cantidad de usuarios por empresa que existen */
      $empresas_20 = "SELECT 
  sucursal.Emp_Cod,
  usuarios.Usu_Cod,
  empresas.Emp_Nom, empresas.Emp_Cor
FROM
  usuarios
  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
WHERE
  Usu_Ced = '$Par_Sql[0]'";
      // echo $empresas_20;
      return $empresas_20;
      break;

    case 21:
      /* Consulta la cantidad de usuarios por empresa que existen */
      $usuarios_21 = "SELECT 
  usuarperfi.Per_Cod, perfiles.Per_Des 
FROM
  perfiles
  INNER JOIN usuarperfi ON (perfiles.Per_Cod = usuarperfi.Per_Cod)
WHERE
  usuarperfi.Usu_Cod = $Par_Sql[0] AND perfiles.Per_Est = 'A'";
      //echo $usuarios_21;
      return $usuarios_21;
      break;

    case 22:
      /* Consulta los perfiles por empresa */
      $perfiles_22 = "SELECT perfiles.Per_Cod, perfiles.Per_Des, perfiles.Per_Est FROM perfiles WHERE perfiles.Emp_Cod = $Par_Sql[0] ORDER BY perfiles.Per_Des";
      //echo $perfiles_22;
      return $perfiles_22;
      break;

    case 23:
      /* Consulta los perfiles de manera individual */
      $perfiles_23 = "SELECT perfiles.Per_Des FROM perfiles WHERE perfiles.Per_Cod = $Par_Sql[0]";
      //echo $perfiles_23;
      return $perfiles_23;
      break;

    case 24:
      /* Consulta los perfiles del usuario para administrarlo */
      $perfiles_24 = "SELECT 
  organizado.Org_Det,
  organizado.Org_Des,
  organizado.Org_Niv,
  organizado.Org_Cod,
  organizado.Org_Img,
  organizado.Org_Ime
FROM
  organizado
WHERE
  organizado.Org_Niv=$Par_Sql[0]
ORDER BY
  organizado.Org_Ord";
      //echo $perfiles_24;
      return $perfiles_24;
      break;

    case 25:
      /* Consulta los procesos de cada organizador para administracion */
      $organizado_25 = "SELECT Pcs_Cod,Pcs_Lin,Rut_Des,Pcs_Nom,Pcs_Tip,Pcs_Det, IF (Pcs_Tip = 'P', 'PROCESO', IF (Pcs_Tip = 'R', 'REPORTE', '')) as Tipo, Pcs_Ord, Rut_Des, Pcs_Det FROM procesos, rutas WHERE procesos.Rut_Cod=rutas.Rut_Cod AND Pcs_Est='A' AND Org_Cod=" . $Par_Sql[0] . " ORDER BY Pcs_Ord";
      //echo $organizado_25."<br>";
      return $organizado_25;
      break;

    case 26:
      /* Consulta los procesos asignados o chequeados a cada perfil */
      $procesos_26 = "SELECT 
  Pcs_Cod
FROM 
  perfiorgan
WHERE perfiorgan.Per_Cod = $Par_Sql[0] AND perfiorgan.Pcs_Cod = $Par_Sql[1]";
      //echo $procesos_26."<br>";
      return $procesos_26;
      break;

    case 27:
      /* Elimina los procesos de un perfil */
      $procesos_27 = "DELETE FROM perfiorgan WHERE Per_Cod = $Par_Sql[0]";
      //echo $procesos_27."<br>";
      return $procesos_27;
      break;

    case 28:
      /* Inserta los procesos de un perfil */
      $procesos_28 = "INSERT INTO perfiorgan(Per_Cod,Pcs_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
      //echo $procesos_28."<br>";
      return $procesos_28;
      break;

    case 29:
      /* Actualiza es estado del usuario */
      $usuarios_29 = "UPDATE usuarios SET Usu_Est='$Par_Sql[0]' WHERE Usu_Cod = $Par_Sql[1]";
      //echo $usuarios_29."<br>";
      return $usuarios_29;
      break;

    case 30:
      /* Inserta nuevos perfiles */
      $perfil_30 = "INSERT INTO perfiles(Per_Des,Emp_Cod) VALUES ('$Par_Sql[0]',$Par_Sql[1])";
      //echo $perfil_30."<br>";
      return $perfil_30;
      break;

    case 31:
      /* Consulta los organizados del arbol del siguiente nivel 2 */
      $organizado_31 =
        "SELECT DISTINCT
organizado.Org_Det,
  organizado.Org_Ord,
  organizado.Org_Des,
  organizado.Org_Niv,
  organizado.Org_Cod,
  organizado.Org_Img,
  organizado.Org_Ime
FROM
  procesos
  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
  INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod)
WHERE
  organizado.Org_Niv = $Par_Sql[0]"; // (".$Par_Sql[0].") AND

      //echo $organizado_31."<br>";
      return $organizado_31;
      break;

    case 32:
      /* Consulta los organizados */
      $organizado_32 = "SELECT Org_Cod, Org_Des, Org_Mod, Org_Img, Org_Ime FROM organizado WHERE Org_Niv=$Par_Sql[0]";

      //echo $organizado_32."<br>";
      return $organizado_32;
      break;

    case 33:
      /* Consulta los organizados en base al codigo */
      $organizado_33 = "SELECT Org_Cod, Org_Des, Org_Mod, Org_Niv FROM organizado WHERE Org_Cod=$Par_Sql[0]";

      //echo $organizado_33."<br>";
      return $organizado_33;
      break;

      /*  Insercion de los directorios */
    case 34:
      $direc_34 = "INSERT INTO organizado (Org_Niv, Org_Det, Org_Des, Org_Img, Org_Ime)  
			VALUE ($Par_Sql[0], '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]')";
      //echo $direc_34;
      return $direc_34;
      break;

      /*  Consulta las rutas activas */
    case 35:
      $rutas_35 = "SELECT Rut_Cod, Rut_Des, Rut_De2 FROM rutas WHERE Rut_Est = 'A'";
      //echo $rutas_35;
      return $rutas_35;
      break;

      /*  Insercion de los directorios */
    case 36:
      $direc_36 = "INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord)  
			VALUE ($Par_Sql[0], '$Par_Sql[1]', '$Par_Sql[2]', $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', $Par_Sql[6], $Par_Sql[7])";
      //echo $direc_36;
      return $direc_36;
      break;

      /*  Consulta los tipos de acceso al sistema */
    case 37:
      $direc_37 = "SELECT tipo_proce.Tpr_Cod, tipo_proce.Tpr_Des FROM tipo_proce WHERE tipo_proce.Tpr_Est = 'A'";
      //echo $direc_37;
      return $direc_37;
      break;

      /*  Actualiza la fecha de conexion del usuario */
    case 38:
      $time_online = time();
      $online_38 = "UPDATE usuarios SET usuarios.Usu_Cox = '$time_online' WHERE usuarios.Usu_Cod = '$Par_Sql[1]'";
      //echo $online_38;
      return $online_38;
      break;

      /*  Usuarios en linea */
    case 39:
      $online_39 = "SELECT persona.Prs_Cod,
                    persona.Prs_Ced, LOWER(persona.Prs_Nom) AS Prs_Nom, LOWER(persona.Prs_Ape) AS Prs_Ape, Prs_Cor,MAX(usuarios.Usu_Cox) AS Usu_Cox FROM persona 
                    INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)
                    WHERE /*usuarios.Usu_Cox >='$Par_Sql[0]' AND sucursal.Emp_Cod = $Par_Sql[2] AND*/ persona.Prs_Cod != $Par_Sql[1] AND Usu_Tip != 'C' AND Usu_Est='A' AND Usu_Cox IS NOT NULL GROUP BY persona.Prs_Cod ORDER BY MAX(usuarios.Usu_Cox) DESC";
      //echo $online_39;
      return $online_39;
      break;



    case 101:
      $rs_clave = "UPDATE usuarios SET Usu_Pal='" . md5($Par_Sql[0]) . "' WHERE Usu_Cod ='$Par_Sql[1]'";
      return $rs_clave;
      break;

      /* Sql de Servicios */
    case 102:
      /* Consulta todos los registros de persona para el web service que necesita la aplicacion auxiliar de KOHA (via WebService) */
      $buscar_personas = "SELECT usuarios.Prs_Cod AS codigo,Prs_Ced AS cedula, Prs_Nom AS nombre,Prs_Ape AS apellido,ciudad.Ciu_Des AS ciudad, 
		Prs_Dir AS direccion,Prs_Tel AS telefono,Prs_Cor AS correo, usuarios.Usu_Tip AS categoria,Prs_Sex AS sexo FROM persona,usuarios,ciudad WHERE persona.Prs_Cod = usuarios.Prs_Cod AND persona.Ciu_Cod = ciudad.Ciu_Cod";
      return $buscar_personas;
      break;

    case 103:
      /* Consulta un solo registro de persona para el web serice que necesita la aplicacion auxiliar de KOHA (via WebService) */
      $buscar_persona = "SELECT usuarios.Prs_Cod AS codigo,Prs_Ced AS cedula, Prs_Nom AS nombre,Prs_Ape AS apellido,ciudad.Ciu_Des AS ciudad, Prs_Dir AS direccion,Prs_Tel AS telefono,Prs_Cor AS correo,usuarios.Usu_Tip AS categoria,Prs_Sex AS sexo FROM persona,usuarios,ciudad WHERE persona.Prs_Cod = usuarios.Prs_Cod AND persona.Ciu_Cod = ciudad.Ciu_Cod AND persona.Prs_Ced='$Par_Sql[0]';";
      return $buscar_persona;
      break;

    case 104:
      /* Consulta un solo registro de persona para el web serice que necesita la aplicacion auxiliar de KOHA (via WebService) */
      $buscar_persona = "SELECT usuarios.Prs_Cod AS codigo,Prs_Ced AS cedula, Prs_Nom AS nombre,Prs_Ape AS apellido,ciudad.Ciu_Des AS ciudad, Prs_Dir AS direccion,Prs_Tel AS telefono,Prs_Cor AS correo,usuarios.Usu_Tip AS categoria,Prs_Sex AS sexo FROM persona,usuarios,ciudad WHERE persona.Prs_Cod = usuarios.Prs_Cod AND persona.Ciu_Cod = ciudad.Ciu_Cod AND persona.Prs_Ape='$Par_Sql[0]';";
      return $buscar_persona;
      break;


    case 201: //borrar
      $Sql_201 = "SELECT Usu_Cod FROM usuarios WHERE Prs_Cod=$Par_Sql[0]";
      return $Sql_201;
      break;

    case 202:
      $Sql_202 = "DELETE FROM usuarperfi WHERE Usu_Cod=$Par_Sql[0]";
      return $Sql_202;
      break;

    case 203:
      $Sql_203 = "INSERT INTO usuarperfi VALUES ($Par_Sql[0], $Par_Sql[1])";
      return $Sql_203;
      break;

    case 204:
      $Sql_204 = "UPDATE usuarios SET Usu_Ced = '$Par_Sql[0]', Usu_Cad = '$Par_Sql[2]'  WHERE Usu_Cod =$Par_Sql[1]";
      return $Sql_204;
      break;

    case 205:
      $Sql_205 = "UPDATE usuarios SET Usu_Pal='" . md5($Par_Sql[0]) . "', Usu_Ced = '$Par_Sql[1]', Usu_Cad = '$Par_Sql[3]' WHERE Usu_Cod = $Par_Sql[2]";
      return $Sql_205;
      break;

    case 206:
      $Sql_206 = "UPDATE usuarios SET Usu_Pal='" . md5($Par_Sql[0]) . "' WHERE Usu_Cod =$Par_Sql[1]";
      return $Sql_206;
      break;

    case 207:
      $Sql_207 = "SELECT Usu_Cod, usuarios.Usu_Ced, Prs_Ape, Prs_Nom, Prs_Est, usuarios.Usu_Tip, usuarios.Usu_Est FROM persona, usuarios WHERE persona.Prs_Ced=usuarios.Usu_Ced AND persona.Prs_Cod = $Par_Sql[0]";
      return $Sql_207;
      break;

    case 208:
      $Sql_208 = "SELECT Nge_Cod, Asi_Int, Nge_Cre FROM notasgedet LIMIT $Par_Sql[0],$Par_Sql[1]";
      //echo $Sql_208."<br>";
      return $Sql_208;
      break;

    case 209:
      $Sql_209 = "SELECT Asi_Int, Asi_Des, Asi_Cre FROM asignatura WHERE Asi_Int=$Par_Sql[0]";
      // echo $Sql_209."<br>";
      return $Sql_209;
      break;

    case 210:
      $Sql_210 = "UPDATE notasgedet SET Nge_Cre = $Par_Sql[0] WHERE Nge_Cod=$Par_Sql[1] AND Asi_Int =$Par_Sql[2]";
      echo $Sql_210 . "<br>";
      return $Sql_210;
      break;

    case 211:
      $Sql_211 = "SELECT Per_Cod, Per_Fot FROM personal WHERE Prs_Cod=$Par_Sql[0] AND Emp_Cod = $Par_Sql[1]";
      //echo $Sql_211;
      return $Sql_211;
      break;

    case 212:
      /**
       * Consulta de la base master 
       */
      $sql = "SELECT 
  empresas.Emp_Nom,
  sucursal.Suc_Des,
  empresas.Emp_Cod
FROM
  sucursal
  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
WHERE
  sucursal.Emp_Cod = $Par_Sql[0] AND sucursal.Suc_Est='A'";
      return $sql;
      break;

    case 213:
      /**
       * Consulta de la base master 
       */
      $sql = "SELECT DISTINCT empresas.Emp_Cod,Emp_Nom,Emp_Cor FROM sucursal 
INNER JOIN empresas ON sucursal.Emp_Cod=empresas.Emp_Cod 
INNER JOIN access ON access.Suc_Cod=sucursal.Suc_Cod WHERE empresas.Emp_Est='A' AND access.Acc_Usr='$Par_Sql[0]' AND Acc_Est='A' Order By Emp_Nom  Asc";
      //echo $sql;
      return $sql;

    case 214:
      /**
       * Consulta de la base master 
       */
      $sql = "SELECT DISTINCT sucursal.Suc_Cod,Emp_Cor,Suc_Des FROM sucursal INNER JOIN empresas ON empresas.Emp_Cod=sucursal.Emp_Cod
INNER JOIN data ON data.Emp_Cod=empresas.Emp_Cod 
INNER JOIN access ON access.Suc_Cod=sucursal.Suc_Cod  
WHERE sucursal.Emp_Cod='$Par_Sql[0]' AND access.Acc_Usr='$Par_Sql[1]' AND Acc_Est='A'";
      //echo $sql.'<br/>';
      return $sql;

      /** AQUI COMIENZA EL CHAT **/
    case 215:
      /**
       * ingresar mensaje de chat
       */        //var_dump($Par_Sql);
      $sql = "INSERT INTO chats (UserFromId,UserToId,Message,Sended) VALUES ($Par_Sql[UserFromId],$Par_Sql[UserToId],'$Par_Sql[Message]',NOW());";
      //echo $sql.'<br/>';
      return $sql;
    case 216:
      /**
       * Leer historial de chat
       */        //var_dump($Par_Sql);
      $sql = "SELECT * FROM chats WHERE ((UserFromId=$Par_Sql[UserFromId] AND UserToId=$Par_Sql[UserToId]) OR (UserToId=$Par_Sql[UserFromId] AND UserFromId=$Par_Sql[UserToId]) AND State=1) ORDER BY Sended; ";
      //echo $sql.'<br/>';
      return $sql;
    case 217:
      /**
       * Leer mensajes nuevos para mi
       */        //var_dump($Par_Sql);
      $sql = "SELECT * FROM chats WHERE (UserToId=$Par_Sql[0]) AND State=0  ORDER BY Sended; ";
      //echo $sql.'<br/>';
      return $sql;
    case 218:
      /**
       * actualizar visto
       */        //var_dump($Par_Sql);
      $sql = "UPDATE chats SET State=1,Viewed=NOW() WHERE (ChatId=$Par_Sql[ChatId]);  ";
      //*echo $sql.'<br/>';
      return $sql;
    case 219:
      /**
       * envio de señal scribiendo
       */        //var_dump($Par_Sql);
      $sql = "INSERT INTO chat_signals (UserFromId,UserToId) VALUES ($Par_Sql[UserFromId],$Par_Sql[UserToId]); ";
      //echo $sql.'<br/>';

      return $sql;
    case 220:
      /**
       * eliminacion de señal escribiendo
       */        //var_dump($Par_Sql);
      $sql = "DELETE FROM chat_signals WHERE UserFromId=$Par_Sql[UserFromId] AND UserToId=$Par_Sql[UserToId];";
      //echo $sql.'<br/>';
      return $sql;
    case 221:
      /**
       * Leer mensajes nuevos para mi
       */        //var_dump($Par_Sql);
      $sql = "SELECT * FROM chat_signals WHERE (UserToId=$Par_Sql[0]);  ";
      //echo $sql.'<br/>';

      return $sql;
      /* AQUI TERMINA EL CHAT */
    case 222:
      /**
       * Consulta la cantidad de usuarios por empresa en la base de datos MASTER
       */
      $sql = "SELECT 
      sucursal.Suc_Cod,
      sucursal.Suc_Des,		
      sucursal.Emp_Cod,
      access.Dat_Cod,
      empresas.Emp_Nom,
      empresas.Emp_Cor
    FROM
      access
      INNER JOIN sucursal ON (access.Suc_Cod = sucursal.Suc_Cod)
      INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
    WHERE
      Acc_Usr = '$Par_Sql[0]' AND 
      empresas.Emp_Est = 'A' AND access.Acc_Est='A' order by empresas.Emp_Cor Asc";
      return $sql;


    case 223:
      /**
       * Leer mensajes nuevos para mi
       */        //var_dump($Par_Sql);
      $sql = "SELECT Dat_Dis FROM exa_master.data WHERE Emp_Cod = $Par_Sql[0]; ";
      //echo $sql.'<br/>';
      return $sql;
      break;

      //Cargar todas las notificaciones
    case 224:
      $sql = "SELECT * FROM (
                SELECT * FROM servicios.tickets WHERE Tic_Est = '0' LIMIT 10
                -- UNION ALL
                -- SELECT * FROM test_prueba.tickets WHERE Tic_Est = '0' LIMIT 10
                -- UNION ALL
                -- SELECT * FROM agrofertil.tickets WHERE Tic_Est = '0' LIMIT 10
                -- UNION ALL
                -- SELECT * FROM agronuevo.tickets WHERE Tic_Est = '0' LIMIT 10
                -- UNION ALL
                -- SELECT * FROM exa.tickets WHERE Tic_Est = '0' LIMIT 10
                -- UNION ALL
                -- SELECT * FROM orquideas.tickets WHERE Tic_Est = '0' LIMIT 10
            ) AS all_tickets 
            ORDER BY Tic_Cod DESC";
      return $sql;
      break;

      // Contar notificaciones
    case 225:
      $sql = "SELECT SUM(TOTAL) AS TOTAL FROM (
                SELECT COUNT(Tic_Cod) AS TOTAL FROM servicios.tickets WHERE Tic_Est = '0'
                -- UNION ALL
                -- SELECT COUNT(Tic_Cod) AS TOTAL FROM test_prueba.tickets WHERE Tic_Est = '0'
                -- UNION ALL
                -- SELECT COUNT(Tic_Cod) AS TOTAL FROM agrofertil.tickets WHERE Tic_Est = '0'
                -- UNION ALL
                -- SELECT COUNT(Tic_Cod) AS TOTAL FROM agronuevo.tickets WHERE Tic_Est = '0'
                -- UNION ALL
                -- SELECT COUNT(Tic_Cod) AS TOTAL FROM exa.tickets WHERE Tic_Est = '0'
                -- UNION ALL
                -- SELECT COUNT(Tic_Cod) AS TOTAL FROM orquideas.tickets WHERE Tic_Est = '0'
              ) AS all_counts;";
      return $sql;
      break;

  // Carga todos los documentos por autorizar
  case 226:
    $sql = "SELECT * FROM (
                                            /*BASE DE DATOS SERVICIOS*/
                    SELECT 
                        IF(Tic_Sri=4, 'NOTAS DE CREDITO', IF(Tic_Sri=5, 'NOTAS DE DEBITO', 'VENTAS')) AS Tipo,
                        IF(Tic_Sri=4, 'NOTASC', IF(Tic_Sri=5, 'NOTASD', 'VENTAS')) AS Type,
                        'ventas' AS tabla, 
                        'Vet_Sri' AS campo1, 
                        'Vet_Aut' AS campo2,  
                        'Vet_Cod' AS cod, 
                        'N' AS Doc_Fir, 
                        'N' AS Doc_Env, 
                        'N' AS Doc_Mail, 
                        Caj_Fec AS Doc_Fec, 
                        Vet_Num AS Doc_Num, 
                        Vet_Cod AS Doc_Cod, 
                        Vet_Aut AS Doc_Aut, 
                        Vet_Xml AS Doc_Xml, 
                        Vet_Sri AS Doc_Sri, 
                        '' AS Info_Adi, 
                        IF(Cli_Cor IS NULL OR TRIM(Cli_Cor) = '' OR TRIM(Cli_Cor) = '-',
                            IF(Prs_Cor IS NULL OR TRIM(Prs_Cor) = '-', '', Prs_Cor),
                            Cli_Cor) AS Email 
                    FROM servicios.ventas 
                        INNER JOIN servicios.cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                        INNER JOIN servicios.persona ON persona.Prs_Cod = cliente.Prs_Cod
                        INNER JOIN servicios.caja_aper ON caja_aper.Caj_Cod = ventas.Caj_Cod
                        INNER JOIN servicios.tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                    WHERE Vet_Est = 'A' AND Tic_Sri!=0 AND Vet_Aut = 'N' AND Vet_Xml is not null AND Emp_Cod = '$Par_Sql[0]'
            UNION ALL
                    SELECT 
                        'RETENCIONES' AS Tipo, 
                        'RETENC' AS Type, 
                        'retencion' AS tabla, 
                        'Ret_Sri' AS campo1, 
                        'Ret_Aut' AS campo2, 
                        'Ret_Cod' AS cod, 
                        'N' AS Doc_Fir, 
                        'N' AS Doc_Env, 
                        'N' AS Doc_Mail, 
                        Ret_Fec AS Doc_Fec, 
                        Ret_Num AS Doc_Num, 
                        Ret_Cod AS Doc_Cod, 
                        Ret_Aut AS Doc_Aut, 
                        Ret_Xml AS Doc_Xml, 
                        Ret_Sri AS Doc_Sri, 
                        CONCAT('Doc.# ', compras.Cop_Num) AS Info_Adi, 
                        IF(Prv_Cor IS NULL OR TRIM(Prv_Cor) = '' OR TRIM(Prv_Cor) = '-',
                            IF(Prs_Cor IS NULL OR TRIM(Prs_Cor) = '-', '', Prs_Cor),
                            Prv_Cor) AS Email 
                    FROM servicios.retencion 
                        INNER JOIN servicios.compras ON retencion.Cop_Cod = compras.Cop_Cod
                        INNER JOIN servicios.proveedore ON proveedore.Prv_Cod = compras.Prv_Cod  
                        INNER JOIN servicios.persona ON persona.Prs_Cod = proveedore.Prs_Cod
                    WHERE Ret_Est = 'A' AND Ret_Aut = 'N' AND TRIM(coalesce(Ret_Xml, ''))<>'' AND Emp_Cod = '$Par_Sql[0]'
            UNION ALL
                    SELECT 
                        'GUIAS' AS Tipo, 
                        'GUIAS' AS Type, 
                        'guias_remis' AS tabla, 
                        'Gui_Sri' AS campo1, 
                        'Gui_Aut' AS campo2, 
                        'Gui_Cod' AS cod, 
                        'N' AS Doc_Fir, 
                        'N' AS Doc_Env, 
                        'N' AS Doc_Mail, 
                        Gui_Fec AS Doc_Fec, 
                        Gui_Num AS Doc_Num, 
                        Gui_Cod AS Doc_Cod, 
                        Gui_Aut AS Doc_Aut, 
                        Gui_Xml AS Doc_Xml, 
                        Gui_Sri AS Doc_Sri, 
                        '' AS Info_Adi, 
                        '' AS Email 
                    FROM servicios.guias_remis                 
                        INNER JOIN servicios.guia_persona ON guia_persona.Gpe_Cod = guias_remis.Gpe_Cod  
                    WHERE Gui_Est = 'A' AND Gui_Aut = 'N' AND Emp_Cod = '$Par_Sql[0]'
            UNION ALL
                    SELECT 
                    'LIQUIDACION' AS Tipo, 
                    'LIQUID' AS Type, 
                    'compras' AS tabla, 
                    'Cop_Aut' AS campo1, 
                    'Aut_Cop' AS campo2, 
                    'Cop_Cod' AS cod, 
                    'N' AS Doc_Fir, 
                    'N' AS Doc_Env, 
                    'N' AS Doc_Mail, 
                    Cop_Fec AS Doc_Fec, 
                    Cop_Num AS Doc_Num, 
                    Cop_Num AS Doc_Cod, 
                    Aut_Cop AS Doc_Aut, 
                    Cop_Aut AS Doc_Xml, 
                    Tic_Cod AS Doc_Sri, 
                    '' AS Info_Adi, 
                      IF(Prv_Cor IS NULL OR TRIM(Prv_Cor)='' OR TRIM(Prv_Cor)='-', 
                      IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Prv_Cor) AS Email
                    FROM servicios.compras               
                        INNER JOIN servicios.proveedore ON compras.Prv_Cod=proveedore.Prv_Cod
                        INNER JOIN servicios.persona ON proveedore.Prs_Cod = persona.Prs_Cod
                    WHERE Cop_Est='A'  AND  Aut_Cop='N' AND TRIM(coalesce(Cop_Aut, ''))<>'' AND 
                          Emp_Cod='$Par_Sql[0]' AND Aut_Cod IS NOT NULL

            UNION ALL
                                    /*BASE DE DATOS EXA*/
                    SELECT 
                        IF(Tic_Sri=4, 'NOTAS DE CREDITO', IF(Tic_Sri=5, 'NOTAS DE DEBITO', 'VENTAS')) AS Tipo,
                        IF(Tic_Sri=4, 'NOTASC', IF(Tic_Sri=5, 'NOTASD', 'VENTAS')) AS Type,
                        'ventas' AS tabla, 
                        'Vet_Sri' AS campo1, 
                        'Vet_Aut' AS campo2,  
                        'Vet_Cod' AS cod, 
                        'N' AS Doc_Fir, 
                        'N' AS Doc_Env, 
                        'N' AS Doc_Mail, 
                        Caj_Fec AS Doc_Fec, 
                        Vet_Num AS Doc_Num, 
                        Vet_Cod AS Doc_Cod, 
                        Vet_Aut AS Doc_Aut, 
                        Vet_Xml AS Doc_Xml, 
                        Vet_Sri AS Doc_Sri, 
                        '' AS Info_Adi, 
                        IF(Cli_Cor IS NULL OR TRIM(Cli_Cor) = '' OR TRIM(Cli_Cor) = '-',
                            IF(Prs_Cor IS NULL OR TRIM(Prs_Cor) = '-', '', Prs_Cor),
                            Cli_Cor) AS Email 
                    FROM exa.ventas 
                        INNER JOIN exa.cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                        INNER JOIN exa.persona ON persona.Prs_Cod = cliente.Prs_Cod
                        INNER JOIN exa.caja_aper ON caja_aper.Caj_Cod = ventas.Caj_Cod
                        INNER JOIN exa.tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                    WHERE Vet_Est = 'A' AND Tic_Sri!=0 AND Vet_Aut = 'N' AND Vet_Xml is not null AND Emp_Cod = '$Par_Sql[0]'

            UNION ALL

                    SELECT 
                        'RETENCIONES' AS Tipo, 
                        'RETENC' AS Type, 
                        'retencion' AS tabla, 
                        'Ret_Sri' AS campo1, 
                        'Ret_Aut' AS campo2, 
                        'Ret_Cod' AS cod, 
                        'N' AS Doc_Fir, 
                        'N' AS Doc_Env, 
                        'N' AS Doc_Mail, 
                        Ret_Fec AS Doc_Fec, 
                        Ret_Num AS Doc_Num, 
                        Ret_Cod AS Doc_Cod, 
                        Ret_Aut AS Doc_Aut, 
                        Ret_Xml AS Doc_Xml, 
                        Ret_Sri AS Doc_Sri, 
                        CONCAT('Doc.# ', compras.Cop_Num) AS Info_Adi, 
                        IF(Prv_Cor IS NULL OR TRIM(Prv_Cor) = '' OR TRIM(Prv_Cor) = '-',
                            IF(Prs_Cor IS NULL OR TRIM(Prs_Cor) = '-', '', Prs_Cor),
                            Prv_Cor) AS Email 
                    FROM exa.retencion 
                        INNER JOIN exa.compras ON retencion.Cop_Cod = compras.Cop_Cod
                        INNER JOIN exa.proveedore ON proveedore.Prv_Cod = compras.Prv_Cod  
                        INNER JOIN exa.persona ON persona.Prs_Cod = proveedore.Prs_Cod
                    WHERE Ret_Est = 'A' AND Ret_Aut = 'N' AND TRIM(coalesce(Ret_Xml, ''))<>'' AND Emp_Cod = '$Par_Sql[0]'

            UNION ALL

                    SELECT 
                        'GUIAS' AS Tipo, 
                        'GUIAS' AS Type, 
                        'guias_remis' AS tabla, 
                        'Gui_Sri' AS campo1, 
                        'Gui_Aut' AS campo2, 
                        'Gui_Cod' AS cod, 
                        'N' AS Doc_Fir, 
                        'N' AS Doc_Env, 
                        'N' AS Doc_Mail, 
                        Gui_Fec AS Doc_Fec, 
                        Gui_Num AS Doc_Num, 
                        Gui_Cod AS Doc_Cod, 
                        Gui_Aut AS Doc_Aut, 
                        Gui_Xml AS Doc_Xml, 
                        Gui_Sri AS Doc_Sri, 
                        '' AS Info_Adi, 
                        '' AS Email 
                    FROM exa.guias_remis                 
                        INNER JOIN exa.guia_persona ON guia_persona.Gpe_Cod = guias_remis.Gpe_Cod  
                    WHERE Gui_Est = 'A' AND Gui_Aut = 'N' AND Emp_Cod = '$Par_Sql[0]'

                            UNION ALL
                                        /*BASE DE DATOS GASOLNERA*/
                        SELECT 
                            IF(Tic_Sri=4, 'NOTAS DE CREDITO', IF(Tic_Sri=5, 'NOTAS DE DEBITO', 'VENTAS')) AS Tipo,
                            IF(Tic_Sri=4, 'NOTASC', IF(Tic_Sri=5, 'NOTASD', 'VENTAS')) AS Type,
                            'ventas' AS tabla, 
                            'Vet_Sri' AS campo1, 
                            'Vet_Aut' AS campo2,  
                            'Vet_Cod' AS cod, 
                            'N' AS Doc_Fir, 
                            'N' AS Doc_Env, 
                            'N' AS Doc_Mail, 
                            Caj_Fec AS Doc_Fec, 
                            Vet_Num AS Doc_Num, 
                            Vet_Cod AS Doc_Cod, 
                            Vet_Aut AS Doc_Aut, 
                            Vet_Xml AS Doc_Xml, 
                            Vet_Sri AS Doc_Sri, 
                            '' AS Info_Adi, 
                            IF(Cli_Cor IS NULL OR TRIM(Cli_Cor) = '' OR TRIM(Cli_Cor) = '-',
                                IF(Prs_Cor IS NULL OR TRIM(Prs_Cor) = '-', '', Prs_Cor),
                                Cli_Cor) AS Email 
                        FROM gsl_chavez.ventas 
                            INNER JOIN gsl_chavez.cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                            INNER JOIN gsl_chavez.persona ON persona.Prs_Cod = cliente.Prs_Cod
                            INNER JOIN gsl_chavez.caja_aper ON caja_aper.Caj_Cod = ventas.Caj_Cod
                            INNER JOIN gsl_chavez.tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                        WHERE Vet_Est = 'A' AND Tic_Sri!=0 AND Vet_Aut = 'N' AND Emp_Cod = '$Par_Sql[0]'

                UNION ALL

                        SELECT 
                            'RETENCIONES' AS Tipo, 
                            'RETENC' AS Type, 
                            'retencion' AS tabla, 
                            'Ret_Sri' AS campo1, 
                            'Ret_Aut' AS campo2, 
                            'Ret_Cod' AS cod, 
                            'N' AS Doc_Fir, 
                            'N' AS Doc_Env, 
                            'N' AS Doc_Mail, 
                            Ret_Fec AS Doc_Fec, 
                            Ret_Num AS Doc_Num, 
                            Ret_Cod AS Doc_Cod, 
                            Ret_Aut AS Doc_Aut, 
                            Ret_Xml AS Doc_Xml, 
                            Ret_Sri AS Doc_Sri, 
                            CONCAT('Doc.# ', compras.Cop_Num) AS Info_Adi, 
                            IF(Prv_Cor IS NULL OR TRIM(Prv_Cor) = '' OR TRIM(Prv_Cor) = '-',
                                IF(Prs_Cor IS NULL OR TRIM(Prs_Cor) = '-', '', Prs_Cor),
                                Prv_Cor) AS Email 
                        FROM gsl_chavez.retencion 
                            INNER JOIN gsl_chavez.compras ON retencion.Cop_Cod = compras.Cop_Cod
                            INNER JOIN gsl_chavez.proveedore ON proveedore.Prv_Cod = compras.Prv_Cod  
                            INNER JOIN gsl_chavez.persona ON persona.Prs_Cod = proveedore.Prs_Cod
                        WHERE Ret_Est = 'A' AND Ret_Aut = 'N' AND Emp_Cod = '$Par_Sql[0]'

                UNION ALL

                        SELECT 
                            'GUIAS' AS Tipo, 
                            'GUIAS' AS Type, 
                            'guias_remis' AS tabla, 
                            'Gui_Sri' AS campo1, 
                            'Gui_Aut' AS campo2, 
                            'Gui_Cod' AS cod, 
                            'N' AS Doc_Fir, 
                            'N' AS Doc_Env, 
                            'N' AS Doc_Mail, 
                            Gui_Fec AS Doc_Fec, 
                            Gui_Num AS Doc_Num, 
                            Gui_Cod AS Doc_Cod, 
                            Gui_Aut AS Doc_Aut, 
                            Gui_Xml AS Doc_Xml, 
                            Gui_Sri AS Doc_Sri, 
                            '' AS Info_Adi, 
                            '' AS Email 
                        FROM gsl_chavez.guias_remis                 
                            INNER JOIN gsl_chavez.guia_persona ON guia_persona.Gpe_Cod = guias_remis.Gpe_Cod  
                        WHERE Gui_Est = 'A' AND Gui_Aut = 'N' AND Emp_Cod = '$Par_Sql[0]'
            -- UNION ALL
            --         SELECT 
            --         'LIQUIDACION' AS Tipo, 
            --         'LIQUID' AS Type, 
            --         'compras' AS tabla, 
            --         'Cop_Aut' AS campo1, 
            --         'Aut_Cop' AS campo2, 
            --         'Cop_Cod' AS cod, 
            --         'N' AS Doc_Fir, 
            --         'N' AS Doc_Env, 
            --         'N' AS Doc_Mail, 
            --         Cop_Fec AS Doc_Fec, 
            --         Cop_Num AS Doc_Num, 
            --         Cop_Num AS Doc_Cod, 
            --         Aut_Cop AS Doc_Aut, 
            --         Cop_Aut AS Doc_Xml, 
            --         Tic_Cod AS Doc_Sri, 
            --         '' AS Info_Adi, 
            --           IF(Prv_Cor IS NULL OR TRIM(Prv_Cor)='' OR TRIM(Prv_Cor)='-', 
            --           IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Prv_Cor) AS Email
            --         FROM exa.compras               
            --             INNER JOIN exa.proveedore ON compras.Prv_Cod=proveedore.Prv_Cod
            --             INNER JOIN exa.persona ON proveedore.Prs_Cod = persona.Prs_Cod
            --         WHERE Cop_Est='A'  AND  Aut_Cop='N' AND TRIM(coalesce(Cop_Aut, ''))<>'' AND 
            --               Emp_Cod='$Par_Sql[0]' AND Aut_Cod IS NOT NULL
            ) AS all_documents  ORDER BY Tipo, Doc_Fec";
    //echo $sql.'<br/>';
    return $sql;
    break;

    /* CONSULTAS DE INDEXHOME PARA ACCESOS DIRECTOS - PROCESOS Y LLAVE ELECRONICA */
    /* Consulta los procesos para validacion de visualizacion en el indexHome.php */
    case 118:
      $sql = "SELECT DISTINCT
                  procesos.Pcs_Lin, rutas.Rut_Des,
                  procesos.Pcs_Nom, procesos.Pcs_Img,
                  procesos.Pcs_Det
              FROM rutas
                  INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
                  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
              WHERE
                  procesos.Pcs_Est='A' AND 
                  procesos.Pcs_Tip = 'P' AND ($Par_Sql[0]) AND ($Par_Sql[1])
                  AND Rut_Des = '/facturacion/FRONT/'
                  AND (Pcs_Nom = 'fac_alt_fac_ven_3.1.php' OR Pcs_Nom = 'fac_alt_fac_ven_3.2.php');";
      //echo $sql;
      return $sql;
      break;
    /* consulta de caducidad de llave electronica */
    case 119:
      $sql = "SELECT Lla_Rut, Lla_Cla, Lla_Cad FROM llave_elect WHERE Lla_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
      //echo $sql;
      return $sql;
      break;
    /* Consulta los procesos del arbol para accesos directos */
    case 120:
      $sql="SELECT DISTINCT
                  procesos.Pcs_Cod, procesos.Org_Cod,
                  organizado.Org_Des, procesos.Pcs_Lin, 
                  rutas.Rut_Des, procesos.Pcs_Nom, 
                  Pcs_Ico
              FROM rutas
                  INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
                  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
                  INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod)
              WHERE
                  procesos.Pcs_Est='A' AND 
                  procesos.Pcs_Tip = 'P' AND ($Par_Sql[0]) AND ($Par_Sql[1])
              ORDER BY
                  organizado.Org_Des ASC;";
      return $sql;
      break;

      /* Consulta para optener el registro especifico de los accesos directos*/
      case 121:
        $sql = "SELECT * FROM shortcut WHERE Emp_Cod = $Par_Sql[0] AND Prs_Cod = $Par_Sql[1]";

      /* inserta los accesos directos del usuario a la base de datos */
      case 122:
        $sql = "INSERT INTO shortcut (Emp_Cod, Prs_Cod, Acc_1, Acc_2, Acc_3, Acc_4) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]');";
        return $sql;
        break;
      /* Get Tipo de Documento by Punto */
      case 123:
        $SearchSucCod = "";
        // Usar claves asociativas si $Par_Sql es un array asociativo
        if ((isset($Par_Sql[0]) && !empty($Par_Sql[0]))) {
          $SearchSucCod = "AND puntos_imp.Suc_Cod = " . intval($Par_Sql[0]);
        }
        // Obtenemos el Prs_Cod del vendedor desde $Par_Sql['Prs_Cod']  
        $Prs_Cod = isset($Par_Sql[1]) ? intval($Par_Sql[1]) : (isset($Par_Sql['Prs_Cod']) ? intval($Par_Sql['Prs_Cod']) : 0);
        
        $sql = "SELECT DISTINCT
                    tipo_compr.Tic_Cod,
                    tipo_compr.Tic_Sri,
                    tipo_compr.Tic_Des,
                    vendedor.Prs_Cod,
                    Aut_Tem
                FROM puntos_imp
                  INNER JOIN autorizaci ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                  INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
                  INNER JOIN vendedor ON puntos_imp.Pun_Cod = vendedor.Pun_Cod
                WHERE vendedor.Prs_Cod = $Prs_Cod
                    AND vendedor.Vnd_Est = 'A'
                    $SearchSucCod
                GROUP BY Tic_Sri, tipo_compr.Tic_Des";
        // echo $Prs_Cod;
        return $sql;
        break;
            
      /* Get Autorizacion by Punto and Tipo de Documento */
      case 124:
				$SearchTicCod = "";
        $SearchPunCod = "";
				if (!empty($Par_Sql[0]) && is_array($Par_Sql[0])) {
          $Tic_Cods = implode(",", array_map('intval', $Par_Sql[0]));
          $SearchTicCod = "AND autorizaci.Tic_Cod IN ($Tic_Cods)";
        }

        if (!empty($Par_Sql[1]) && is_array($Par_Sql[1])) {
          $Pun_Cods = implode(",", array_map('intval', $Par_Sql[1]));
          $SearchPunCod = "AND autorizaci.Pun_Cod IN ($Pun_Cods)";
        }

        $sql = "SELECT 
                  autorizaci.Aut_Cod, autorizaci.Pun_Cod,
                  autorizaci.Tic_Cod, autorizaci.Pun_Sri,
                  autorizaci.Aut_Sri, tipo_compr.Tic_Des,
                  autorizaci.Aut_Cad, autorizaci.Aut_Fin,
                  IF (autorizaci.Aut_Tem = 'E', 'ELECTRONICA', 'NORMAL') AS AutTem,
                  IF (autorizaci.Aut_Est = 'A', 'Activo', 'Inactivo') AS Aut_Est
                FROM autorizaci
                  INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
                  INNER JOIN puntos_imp ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
                  INNER JOIN vendedor ON puntos_imp.Pun_Cod = vendedor.Pun_Cod
                WHERE 1=1 AND
                  vendedor.Prs_Cod = $Par_Sql[2]
                  $SearchPunCod
                  $SearchTicCod
                  AND puntos_imp.Suc_Cod = $Par_Sql[3]
                  AND Aut_Est = 'A'
                ORDER BY tipo_compr.Tic_Des ASC";
          return $sql;
          break;

      /* CONSULTAS PARA DASHBOARD DE INDEXHOME */
      /* Consulta de ventas con filtro de empresa y fechas */
      case 125: 
        $sql = "SELECT 
            COUNT(DISTINCT v.Vet_Cod) AS cantidad,
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * (
                    (vd.Vet_Pru * vd.Vet_Can) 
                    - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)
                    - (((vd.Vet_Pru * vd.Vet_Can) - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)) * v.Vet_Des / 100)
                )
            ), 0) AS subtotal,
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * IF(i.Iva_Por != 0,
                    (
                        (vd.Vet_Pru * vd.Vet_Can) 
                        - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)
                        - (((vd.Vet_Pru * vd.Vet_Can) - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)) * v.Vet_Des / 100)
                        + IFNULL(vd.Vet_Ice, 0)
                    ) * i.Iva_Por / 100
                , 0)
            ), 0) AS iva,
            COALESCE(SUM(IF(tc.Tic_Sri = 4, -1, 1) * IFNULL(vd.Vet_Ice, 0)), 0) AS ice
        FROM ventas v
          INNER JOIN ventas_det vd ON v.Vet_Cod = vd.Vet_Cod
          INNER JOIN iva i ON vd.Iva_Cod = i.Iva_Cod
          INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
          INNER JOIN autorizaci au ON v.Aut_Cod = au.Aut_Cod
          INNER JOIN tipo_compr tc ON au.Tic_Cod = tc.Tic_Cod
          INNER JOIN puntos_imp pi ON au.Pun_Cod = pi.Pun_Cod
          INNER JOIN sucursal su ON pi.Suc_Cod = su.Suc_Cod
        WHERE v.Vet_Est = 'A' 
          AND ca.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
          AND su.Emp_Cod = $Par_Sql[0]";
        return $sql;
        break;

      /* Consulta de compras con filtro de empresa y fechas */
      case 126: 
        $sql = "SELECT 
            COUNT(DISTINCT c.Cop_Cod) AS cantidad,
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * (
                    (dc.Cop_Pru * dc.Cop_Can) 
                    - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)
                    - (((dc.Cop_Pru * dc.Cop_Can) - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)) * c.Cop_Des / 100)
                )
            ), 0) AS subtotal,
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * IF(i.Iva_Por != 0,
                    (
                        (dc.Cop_Pru * dc.Cop_Can) 
                        - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)
                        - (((dc.Cop_Pru * dc.Cop_Can) - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)) * c.Cop_Des / 100)
                        + IFNULL((dc.Cop_Pru * dc.Cop_Can) * IFNULL(dc.Cop_Ice, 0) / 100, 0)
                    ) * i.Iva_Por / 100
                , 0)
            ), 0) AS iva,
            COALESCE(SUM(IF(tc.Tic_Sri = 4, -1, 1) * IFNULL((dc.Cop_Pru * dc.Cop_Can) * IFNULL(dc.Cop_Ice, 0) / 100, 0)), 0) AS ice
        FROM compras c
          INNER JOIN det_compra dc ON c.Cop_Cod = dc.Cop_Cod
          INNER JOIN iva i ON dc.Iva_Cod = i.Iva_Cod
          INNER JOIN tipo_compr tc ON c.Tic_Cod = tc.Tic_Cod
          INNER JOIN proveedore p ON c.Prv_Cod = p.Prv_Cod
        WHERE c.Cop_Est = 'A'
          AND c.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
          AND p.Emp_Cod = $Par_Sql[0]";
        return $sql; 
        break; 

      /* Consulta de clientes nuevos con filtro de empresa y fechas */
      case 127: 
        $sql = "SELECT COUNT(DISTINCT Cli_Cod) AS nuevos FROM cliente WHERE Emp_Cod = $Par_Sql[0] AND Cli_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'";
        return $sql;
        break; 

      /* Consulta de ventas por día para gráficos */
      case 128: 
        $sql = "SELECT DATE(ca.Caj_Fec) AS fecha, 
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * (
                    (vd.Vet_Pru * vd.Vet_Can) 
                    - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)
                    - (((vd.Vet_Pru * vd.Vet_Can) - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)) * v.Vet_Des / 100)
                ) * (1 + i.Iva_Por / 100)
            ), 0) AS total
            FROM ventas v
              INNER JOIN ventas_det vd ON v.Vet_Cod = vd.Vet_Cod
              INNER JOIN iva i ON vd.Iva_Cod = i.Iva_Cod
              INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
              INNER JOIN autorizaci au ON v.Aut_Cod = au.Aut_Cod
              INNER JOIN tipo_compr tc ON au.Tic_Cod = tc.Tic_Cod
              INNER JOIN puntos_imp pi ON au.Pun_Cod = pi.Pun_Cod
              INNER JOIN sucursal su ON pi.Suc_Cod = su.Suc_Cod
            WHERE v.Vet_Est = 'A' AND 
                  ca.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59' AND
                  su.Emp_Cod = $Par_Sql[0]
            GROUP BY DATE(ca.Caj_Fec)
            ORDER BY fecha";
        return $sql; 
        break; 

      /* Consulta de compras por día para gráficos */
      case 129: 
        $sql = "SELECT DATE(c.Cop_Fec) AS fecha, 
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * (
                    (dc.Cop_Pru * dc.Cop_Can) 
                    - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)
                    - (((dc.Cop_Pru * dc.Cop_Can) - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)) * c.Cop_Des / 100)
                ) * (1 + i.Iva_Por / 100)
            ), 0) AS total
            FROM compras c
              INNER JOIN det_compra dc ON c.Cop_Cod = dc.Cop_Cod
              INNER JOIN iva i ON dc.Iva_Cod = i.Iva_Cod
              INNER JOIN tipo_compr tc ON c.Tic_Cod = tc.Tic_Cod
              INNER JOIN proveedore p ON c.Prv_Cod = p.Prv_Cod
            WHERE c.Cop_Est = 'A' AND
                  c.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59' AND
                  p.Emp_Cod = $Par_Sql[0]
            GROUP BY DATE(c.Cop_Fec)
            ORDER BY fecha";
        return $sql; 
        break; 

      /* Consulta de ventas por mes para gráficos */
      case 130: 
        $sql = "SELECT DATE_FORMAT(ca.Caj_Fec, '%Y-%m') AS mes, 
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * (
                    (vd.Vet_Pru * vd.Vet_Can) 
                    - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)
                    - (((vd.Vet_Pru * vd.Vet_Can) - ((vd.Vet_Pru * vd.Vet_Can) * vd.Vet_Dec / 100)) * v.Vet_Des / 100)
                ) * (1 + i.Iva_Por / 100)
            ), 0) AS total
            FROM ventas v
              INNER JOIN ventas_det vd ON v.Vet_Cod = vd.Vet_Cod
              INNER JOIN iva i ON vd.Iva_Cod = i.Iva_Cod
              INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
              INNER JOIN autorizaci au ON v.Aut_Cod = au.Aut_Cod
              INNER JOIN tipo_compr tc ON au.Tic_Cod = tc.Tic_Cod
              INNER JOIN puntos_imp pi ON au.Pun_Cod = pi.Pun_Cod
              INNER JOIN sucursal su ON pi.Suc_Cod = su.Suc_Cod
            WHERE v.Vet_Est = 'A' AND
                  ca.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59' AND
                  su.Emp_Cod = $Par_Sql[0]
            GROUP BY DATE_FORMAT(ca.Caj_Fec, '%Y-%m')
            ORDER BY mes";
        return $sql;
        break; 

      /* Consulta de compras por mes para gráficos */
      case 131: 
        $sql = "SELECT DATE_FORMAT(c.Cop_Fec, '%Y-%m') AS mes, 
            COALESCE(SUM(
                IF(tc.Tic_Sri = 4, -1, 1) * (
                    (dc.Cop_Pru * dc.Cop_Can) 
                    - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)
                    - (((dc.Cop_Pru * dc.Cop_Can) - ((dc.Cop_Pru * dc.Cop_Can) * dc.Cop_Dec / 100)) * c.Cop_Des / 100)
                ) * (1 + i.Iva_Por / 100)
            ), 0) AS total
            FROM compras c
              INNER JOIN det_compra dc ON c.Cop_Cod = dc.Cop_Cod
              INNER JOIN iva i ON dc.Iva_Cod = i.Iva_Cod
              INNER JOIN tipo_compr tc ON c.Tic_Cod = tc.Tic_Cod
              INNER JOIN proveedore p ON c.Prv_Cod = p.Prv_Cod
            WHERE c.Cop_Est = 'A' AND
                c.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59' AND
                p.Emp_Cod = $Par_Sql[0]
            GROUP BY DATE_FORMAT(c.Cop_Fec, '%Y-%m') ORDER BY mes";
        return $sql;
        break; 

      /* Consulta Top 5 Productos */
      case 132:
        $sql = "SELECT 
            IFNULL(NULLIF(i.Ite_Cor,''), SUBSTRING(i.Ite_Lar,1,25)) AS nombre,
            IFNULL(i.Ite_Lar, p.Pro_Obs) AS nombre_largo,
            ROUND(SUM(vd.Vet_Can), 2) AS cantidad, 
            ROUND(SUM(vd.Vet_Imp), 2) AS total
        FROM ventas v
          INNER JOIN ventas_det vd ON v.Vet_Cod = vd.Vet_Cod
          INNER JOIN producto p ON vd.Pro_Cod = p.Pro_Cod
          INNER JOIN item i ON p.Ite_Cod = i.Ite_Cod
          INNER JOIN categorias cat ON i.Cat_Cod = cat.Cat_Cod
          INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
        WHERE v.Vet_Est = 'A' AND
              ca.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59' AND
              cat.Emp_Cod = $Par_Sql[0]
        GROUP BY p.Pro_Cod 
        ORDER BY total DESC LIMIT 5";
        return $sql;
        break; 

      /* Consulta Top 5 Clientes */
      case 133:
        $sql = "SELECT CONCAT(IFNULL(pe.Prs_Nom,''), ' ', IFNULL(pe.Prs_Ape,'')) AS nombre,
            COUNT(DISTINCT v.Vet_Cod) AS facturas, ROUND(SUM(vd.Vet_Imp), 2) AS total
        FROM ventas v
          INNER JOIN ventas_det vd ON v.Vet_Cod = vd.Vet_Cod
          INNER JOIN cliente cl ON v.Cli_Cod = cl.Cli_Cod
          INNER JOIN persona pe ON cl.Prs_Cod = pe.Prs_Cod
          INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
          INNER JOIN autorizaci au ON v.Aut_Cod = au.Aut_Cod
          INNER JOIN puntos_imp pi ON au.Pun_Cod = pi.Pun_Cod
          INNER JOIN sucursal su ON pi.Suc_Cod = su.Suc_Cod
        WHERE v.Vet_Est = 'A' AND
              ca.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59' AND
              su.Emp_Cod = $Par_Sql[0]
        GROUP BY cl.Cli_Cod
        ORDER BY total DESC LIMIT 5";
        return $sql;
        break; 

      /* Consulta Total Clientes */
      case 134:
        $sql = "SELECT COUNT(DISTINCT Cli_Cod) AS total FROM cliente WHERE Emp_Cod = $Par_Sql[0]";
        return $sql;
        break; 

      /* Consulta Facturas Autorizadas */
      case 135:
        $sql = "SELECT COUNT(DISTINCT v.Vet_Cod) AS total
            FROM ventas v
              INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
              INNER JOIN autorizaci au ON v.Aut_Cod = au.Aut_Cod
              INNER JOIN tipo_compr tc ON au.Tic_Cod = tc.Tic_Cod
              INNER JOIN puntos_imp pi ON au.Pun_Cod = pi.Pun_Cod
              INNER JOIN sucursal su ON pi.Suc_Cod = su.Suc_Cod
            WHERE v.Vet_Est = 'A' 
                  AND tc.Tic_Sri = 1
                  AND v.Vet_Sri IS NOT NULL AND TRIM(v.Vet_Sri) != ''
                  AND ca.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
                  AND su.Emp_Cod = $Par_Sql[0]";
        return $sql;
        break; 

      /* Consulta Notas de Crédito Autorizadas */
      case 136:
        $sql = "SELECT COUNT(DISTINCT v.Vet_Cod) AS total
            FROM ventas v
              INNER JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
              INNER JOIN autorizaci au ON v.Aut_Cod = au.Aut_Cod
              INNER JOIN tipo_compr tc ON au.Tic_Cod = tc.Tic_Cod
              INNER JOIN puntos_imp pi ON au.Pun_Cod = pi.Pun_Cod
              INNER JOIN sucursal su ON pi.Suc_Cod = su.Suc_Cod
            WHERE v.Vet_Est = 'A' 
              AND tc.Tic_Sri = 4
              AND v.Vet_Sri IS NOT NULL AND TRIM(v.Vet_Sri) != ''
              AND ca.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
              AND su.Emp_Cod = $Par_Sql[0]";
        return $sql;
        break; 

      /* Consulta Retenciones Autorizadas */
      case 137:
        $sql = "SELECT COUNT(DISTINCT r.Ret_Cod) AS total
            FROM retencion r
              INNER JOIN compras c ON r.Cop_Cod = c.Cop_Cod
              INNER JOIN proveedore p ON c.Prv_Cod = p.Prv_Cod
            WHERE r.Ret_Est = 'A'
              AND r.Ret_Sri IS NOT NULL AND TRIM(r.Ret_Sri) != ''
              AND r.Ret_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
              AND p.Emp_Cod = $Par_Sql[0]";
        return $sql;
        break; 

      /* Consulta Liquidaciones de Compras */
      case 138:
        // $sql = "SELECT COUNT(DISTINCT c.Cop_Cod) AS total
        //     FROM compras c
        //       INNER JOIN tipo_compr tc ON c.Tic_Cod = tc.Tic_Cod
        //       INNER JOIN proveedore p ON c.Prv_Cod = p.Prv_Cod
        //     WHERE c.Cop_Est = 'A'
        //       AND tc.Tic_Sri = 3
        //       AND c.Cop_Sri IS NOT NULL AND TRIM(c.Cop_Sri) != ''
        //       AND c.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
        //       AND p.Emp_Cod = $Par_Sql[0]";
        // return $sql;
        $sql = "SELECT COUNT(DISTINCT c.Cop_Cod) AS total
            FROM compras c
            INNER JOIN tipo_compr tc ON c.Tic_Cod = tc.Tic_Cod
            INNER JOIN proveedore p ON c.Prv_Cod = p.Prv_Cod
            WHERE c.Cop_Est = 'A'
            AND tc.Tic_Sri = 3
            AND c.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
            AND p.Emp_Cod = $Par_Sql[0]";
        return $sql;
        break; 

      /* Consulta Guías de Remisión */
      case 139:
        // $sql = "SELECT COUNT(DISTINCT g.Gui_Cod) AS total
        //     FROM guia_remi g
        //       INNER JOIN sucursal su ON g.Suc_Cod = su.Suc_Cod
        //     WHERE g.Gui_Est = 'A'
        //       AND g.Gui_Sri IS NOT NULL AND TRIM(g.Gui_Sri) != ''
        //       AND g.Gui_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
        //       AND su.Emp_Cod = $Par_Sql[0]";
        // return $sql;
        $sql = "SELECT COUNT(DISTINCT g.Gui_Cod) AS total
            FROM guias_remis g
            INNER JOIN guia_persona gp ON g.Gpe_Cod = gp.Gpe_Cod
            WHERE g.Gui_Est = 'A'
            AND g.Gui_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2] 23:59:59'
            AND gp.Emp_Cod = $Par_Sql[0]";
        return $sql;
        break;
  }
}
