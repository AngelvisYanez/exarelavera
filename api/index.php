// Endpoint de test y diagnóstico
$app->get('/v1/test', function () use ($app) {
    if (!class_exists('APITokenManager')) {
        require_once __DIR__ . "/../classes/APITokenManager.php";
    }
    $mgr = new APITokenManager();
    $token = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $val = $mgr->validate($token, false);

    $conn = mysqli_connect("localhost", "wilsonbelduma", "Pvhn713?6", "exa");
    $users = [];
    if ($conn) {
        $q = mysqli_query($conn, "SELECT a.Acc_Usr, a.Suc_Cod, e.Emp_Cod, e.Emp_Nom, e.Emp_Cor, a.Acc_Est FROM access a JOIN sucursal s ON a.Suc_Cod = s.Suc_Cod JOIN empresas e ON s.Emp_Cod = e.Emp_Cod WHERE a.Acc_Est='A' LIMIT 25");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $users[] = $r;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'php_version' => PHP_VERSION,
        'info' => $mgr->empresaInfo(620),
        'token_validation' => $val,
        'users' => $users
    ]);
});
