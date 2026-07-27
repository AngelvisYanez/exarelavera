<?php
require_once __DIR__ . "/../../../administrador/LOGICA/adm_log_login.php";
require_once __DIR__ . "/../../../DATA/MysqlConexion.php";
require_once __DIR__ . "/../../../DATA/MysqlDatos.php";

define('AUTH_TOKEN_SECRET', getenv('AUTH_TOKEN_SECRET') ?: 'CHANGE_THIS_TO_A_RANDOM_SECRET_KEY_IN_PRODUCTION');

// Clases locales para evitar requerir adm_log_control.php que tiene rutas relativas rotas (../../)
class Class_Log_Conexion_Auth extends MysqlConexion {}
class Class_Log_Datos_Auth extends MysqlDatos
{
    function consultasobBD($sen_sql, $param, $obBD = null)
    {
        $Par_Sql = $this->parametros($param);
        $sql = "";
        if ($sen_sql == 2) {
            $empCod = (int)$Par_Sql[0];
            $accUsr = $this->conexion ? $this->conexion->real_escape_string($Par_Sql[1]) : addslashes($Par_Sql[1]);
            $sql = "SELECT `data`.Dat_Dis, `data`.Dat_Aut, `data`.Dat_Stg FROM
                  access INNER JOIN `data` ON (access.Dat_Cod = `data`.Dat_Cod) WHERE data.`Emp_Cod`=$empCod AND `access`.`Acc_Usr`='$accUsr'";
        }
        if ($sen_sql == 14) {
            $usuCed = $this->conexion ? $this->conexion->real_escape_string($Par_Sql[0]) : addslashes($Par_Sql[0]);
            $usuPal = $this->conexion ? $this->conexion->real_escape_string($Par_Sql[1]) : addslashes($Par_Sql[1]);
            $empCod = (int)$Par_Sql[2];
            $sql = "SELECT usuarios.Usu_Ced, persona.Prs_Nom, persona.Prs_Ape
                        FROM usuarios
                        INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
                        INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
                        INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
                        WHERE Usu_Ced = '$usuCed' AND Usu_Pal = '$usuPal' AND empresas.Emp_Cod = $empCod
                        AND usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
        }
        return $this->consulta($sql, $obBD->conexion);
    }
}

function generateAuthToken($username, $empresa) {
    $payload = $username . ":" . $empresa . ":" . time();
    $signature = hash_hmac('sha256', $payload, AUTH_TOKEN_SECRET);
    return base64_encode($payload . ":" . $signature);
}

function validateAuthToken($token) {
    $decoded = base64_decode($token, true);
    if ($decoded === false) return false;

    $parts = explode(":", $decoded, 4);
    if (count($parts) !== 4) return false;

    [$username, $empresa, $time, $signature] = $parts;
    $expectedSignature = hash_hmac('sha256', $username . ":" . $empresa . ":" . $time, AUTH_TOKEN_SECRET);

    if (!hash_equals($expectedSignature, $signature)) return false;

    return [
        'username' => $username,
        'empresa' => $empresa,
        'time' => (int)$time,
    ];
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
        $obBD_conexion = new Class_Log_Conexion_Log();
        $obBD_con1 = new Class_Log_Datos_Log();

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
        error_log("Auth empresas error: " . $e->getMessage());
        $app->response->setStatus(500);
        echo json_encode(["error" => "Error interno del servidor"]);
    }
});

$app->post("/v1/auth/login", function () use ($app) {
    $body = getBody();
    $username = isset($body["username"]) ? trim($body["username"]) : "";
    $password = isset($body["password"]) ? trim($body["password"]) : "";
    $empresa = isset($body["empresa"]) ? trim($body["empresa"]) : "";

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

        $row_data = $obBD_con1->getRowConsulta(
            2,
            $empresa . "*" . $username,
            $obBD_conexion
        );

        if (!empty($row_data)) {
            $bdd_distribuida = $row_data["Dat_Dis"];
            $obBD_conexion_dist = new Class_Log_Conexion_Auth($bdd_distribuida);

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
                    "token" => generateAuthToken($username, $empresa),
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
        error_log("Auth login error: " . $e->getMessage());
        $app->response->setStatus(500);
        echo json_encode(["error" => "Error interno del servidor"]);
    }
});
?>
