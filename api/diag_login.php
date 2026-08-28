<?php
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';

$con = new MysqlConexion('exa_master');
$datos = new MysqlDatos();

$response = [];

// 1. Buscar usuarios
$response['usuarios'] = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, u.Usu_Tip, u.Suc_Cod,
            p.Prs_Nom, p.Prs_Ape, p.Prs_Ced
       FROM usuarios u
       LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
      WHERE u.Usu_Ced LIKE '%22600781%'
         OR u.Usu_Ced LIKE '%1676514%'
         OR p.Prs_Nom LIKE '%TORRES%'
         OR p.Prs_Ape LIKE '%TORRES%'
         OR p.Prs_Nom LIKE '%CARRION%'
         OR p.Prs_Ape LIKE '%CARRION%'",
    $con
);

// 2. Buscar empresas
$response['empresas_torres'] = $datos->getArrayConsultaSql(
    "SELECT e.Emp_Cod, e.Emp_Nom, e.Emp_Cor, e.Emp_Ruc, e.Emp_Est, d.Dat_Dis AS Bdd
       FROM empresas e
       LEFT JOIN data d ON d.Emp_Cod = e.Emp_Cod
      WHERE e.Emp_Nom LIKE '%TORRES%'
         OR e.Emp_Nom LIKE '%CARRION%'
         OR e.Emp_Cor LIKE '%TORRES%'
         OR e.Emp_Cor LIKE '%CARRION%'",
    $con
);

// 3. Probar consulta ajax_empresas2 para cada usuario
$response['login_empresas_test'] = [];
$cedulas = ['22600781', '1676514'];
if (!empty($response['usuarios'])) {
    foreach ($response['usuarios'] as $u) {
        if (!in_array($u['Usu_Ced'], $cedulas)) {
            $cedulas[] = $u['Usu_Ced'];
        }
    }
}

foreach ($cedulas as $ced) {
    $rows = $datos->getArrayConsultaSql(
        "SELECT sucursal.Suc_Cod, sucursal.Suc_Des, sucursal.Emp_Cod,
                usuarios.Usu_Cod, usuarios.Usu_Ced, empresas.Emp_Nom, empresas.Emp_Cor,
                usuarios.Usu_Pal, usuarios.Usu_Est, empresas.Emp_Est
           FROM usuarios
          INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
          INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
          WHERE usuarios.Usu_Ced = '$ced'
            AND empresas.Emp_Est = 'A'
            AND usuarios.Usu_Est = 'A'
          ORDER BY empresas.Emp_Cor ASC",
        $con
    );
    
    // Verificar contraseñas conocidas
    $passChecks = [];
    foreach ($rows as &$r) {
        $hash = $r['Usu_Pal'];
        $passChecks['hash'] = $hash;
        $passChecks['123456_md5'] = ($hash === md5('123456'));
        $passChecks['123456_md5_hash'] = ($hash === md5(md5('123456')));
        $passChecks['123456_bcrypt_raw'] = strpos($hash, '$2y$') === 0 && password_verify('123456', $hash);
        $passChecks['123456_bcrypt_md5'] = strpos($hash, '$2y$') === 0 && password_verify(md5('123456'), $hash);
    }
    
    $response['login_empresas_test'][$ced] = [
        'conteo' => count($rows),
        'empresas' => $rows,
        'pass_checks' => $passChecks
    ];
}

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
