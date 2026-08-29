    // Rutas administrativas del panel: además del Bearer, se acepta la sesión
    // activa del panel de administración (misma cookie de sesión en producción).
    $esAdminSession = false;
    if (preg_match("#^/v1/admin/#", $resourceUri)) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $esAdminSession = !empty($_SESSION['Ses_Usu_Cod']);
        if ($esAdminSession) {
            return;
        }
    }

    // Leer header Authorization con múltiples fallbacks para compatibilidad con servidores web
    $authHeader = $app->request->headers->get("Authorization");
    if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $reqHeaders = apache_request_headers();
        $authHeader = $reqHeaders['Authorization'] ?? ($reqHeaders['authorization'] ?? null);
    }
    if (empty($authHeader)) {
        $authHeader = $_GET['token'] ?? ($_GET['api_token'] ?? ($_GET['access_token'] ?? ($_POST['token'] ?? null)));
    }

    $rawAuth = trim((string)$authHeader);
    if ($rawAuth === '') {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Token de autenticación requerido"
            ])
        );
        $app->stop();
    }

    // Limpiar prefijo Bearer si viene incluido
    $token = $rawAuth;
    while (stripos($token, 'Bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    $tokenData = validateAuthToken($token);
    $managedData = null;

    // Si el token HMAC del login no es válido, intentar con un token gestionado
    // (creado desde el panel de administración con límite de consultas).
    if ($tokenData === false) {
        if (!class_exists('APITokenManager')) {
            require_once __DIR__ . "/../classes/APITokenManager.php";
        }
        try {
            $mgr = new APITokenManager();
            $managed = $mgr->validate($token, true);
            if ($managed && !empty($managed['valid'])) {
                $managedData = $managed;
            }
        } catch (\Throwable $e) {
            error_log("Validación token gestionado error: " . $e->getMessage());
        }
    }

    if ($tokenData === false && $managedData === null) {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Token inválido"
            ])
        );
        $app->stop();
    }
