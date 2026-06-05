<?php
require_once __DIR__ . "/../../../administrador/LOGICA/adm_log_login.php";
require_once __DIR__ . "/../../../DATA/MysqlConexion.php";
require_once __DIR__ . "/../../../DATA/MysqlDatos.php";

// Clases locales para evitar requerir adm_log_control.php que tiene rutas relativas rotas (../../)
class Class_Log_Conexion_Auth extends MysqlConexion {}
class Class_Log_Datos_Auth extends MysqlDatos
{
    function consultasobBD($sen_sql, $param, $obBD = null)
    {
        $Par_Sql = $this->parametros($param);
        $sql = "";
        if ($sen_sql == 2) {
            // Consulta la base de datos
            $sql = "SELECT `data`.Dat_Dis, `data`.Dat_Aut, `data`.Dat_Stg FROM
                  access INNER JOIN `data` ON (access.Dat_Cod = `data`.Dat_Cod) WHERE data.`Emp_Cod`=$Par_Sql[0] AND `access`.`Acc_Usr`='$Par_Sql[1]'";
        }
        if ($sen_sql == 14) {
            // Consulta para validar login
            // El original (consulta 16) usa Usu_Pal = '$Par_Sql[1]', asumiendo que ya viene encriptado en MD5 desde el front.
            // Si el front pasa el password en crudo, en la API lo convertimos a MD5 antes de pasarlo, o usamos MD5() en SQL.
            // Par_Sql[1] ya trae el md5 desde nuestra API (ver más abajo)
            $sql = "SELECT usuarios.Usu_Ced, persona.Prs_Nom, persona.Prs_Ape
                        FROM usuarios
                        INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
                        INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
                        INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
                        WHERE Usu_Ced = '$Par_Sql[0]' AND Usu_Pal = '$Par_Sql[1]' AND empresas.Emp_Cod = $Par_Sql[2]
                        AND usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
        }
        return $this->consulta($sql, $obBD->conexion);
    }
}

$app->post("/v1/auth/empresas", function () use ($app) {
    $body = getBody();
    $username = isset($body["username"]) ? trim($body["username"]) : "";

    if (empty($username)) {
        $app->response->setStatus(400);
        echo json_encode(["error" => "Usuario requerido"]);
        return;
    }

    try {
        // Reutilizamos la misma logica del index.php original
        $obBD_conexion = new Class_Log_Conexion_Log();
        $obBD_con1 = new Class_Log_Datos_Log();

        // Consulta 1 (sentencias_log) en master DB
        $rs_empresas = $obBD_con1->getArrayConsulta(
            1,
            $username,
            $obBD_conexion
        );

        $response = [
            "success" => !empty($rs_empresas),
            "conteo" => $rs_empresas ? count($rs_empresas) : 0,
            "empresas" => $rs_empresas ?: [],
        ];
        utf8_encode_deep($response);
        echo json_encode($response);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
});

$app->post("/v1/auth/login", function () use ($app) {
    $body = getBody();
    $username = isset($body["username"]) ? trim($body["username"]) : "";
    $password = isset($body["password"]) ? trim($body["password"]) : "";
    $empresa = isset($body["empresa"]) ? trim($body["empresa"]) : ""; // Emp_Cod
    $sucursal = isset($body["sucursal"]) ? trim($body["sucursal"]) : ""; // Suc_Cod opcional o Data Base

    if (empty($username) || empty($password) || empty($empresa)) {
        $app->response->setStatus(400);
        echo json_encode([
            "success" => false,
            "error" => "Usuario, contraseña y empresa son requeridos",
        ]);
        return;
    }

    try {
        $obBD_conexion = new Class_Log_Conexion_Auth();
        $obBD_con1 = new Class_Log_Datos_Auth();

        // Busca la BD de la empresa (Dat_Dis) en access/sucursal
        $row_data = $obBD_con1->getRowConsulta(
            2,
            $empresa . "*" . $username,
            $obBD_conexion
        );

        if (!empty($row_data)) {
            $bdd_distribuida = $row_data["Dat_Dis"];

            // Ahora nos conectamos a la BD distribuida para verificar credenciales
            $obBD_conexion_dist = new Class_Log_Conexion_Auth($bdd_distribuida);

            // Verificamos las credenciales en la base de datos distribuida
            // legacy enviaba el MD5 desde el JS. Como React manda el texto plano, lo hasheamos aquí
            $encryptor = md5($password);

            $user_data = $obBD_con1->getRowConsulta(
                14,
                $username . "*" . $encryptor . "*" . $empresa,
                $obBD_conexion_dist
            );

            if (!empty($user_data)) {
                $response = [
                    "success" => true,
                    "message" => "Login exitoso",
                    "token" => base64_encode(
                        $username . ":" . $empresa . ":" . time()
                    ),
                    "Bdd" => $bdd_distribuida,
                    "usuario" =>
                        $user_data["Prs_Nom"] . " " . $user_data["Prs_Ape"],
                    "empresa_id" => $empresa,
                ];
                utf8_encode_deep($response);
                echo json_encode($response);
            } else {
                echo json_encode([
                    "success" => false,
                    "error" => "Contraseña incorrecta o usuario inactivo",
                ]);
            }
        } else {
            echo json_encode([
                "success" => false,
                "error" => "Usuario o empresa no encontrados",
            ]);
        }
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
});
?>
