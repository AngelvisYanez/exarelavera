<?php
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';

$con = new MysqlConexion(); // base 'exa'
$datos = new MysqlDatos();

$response = [];

// 1. Conteo total de usuarios y personas en 'exa'
$response['total_usuarios'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM usuarios", $con);
$response['total_personas'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM persona", $con);
$response['total_sucursales'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM sucursal", $con);
$response['total_empresas'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM empresas", $con);

// 2. Buscar 22600781 y 1676514 en 'exa'
$response['find_usuarios'] = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, u.Usu_Tip, u.Suc_Cod,
            p.Prs_Nom, p.Prs_Ape, p.Prs_Ced
       FROM usuarios u
       LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
      WHERE u.Usu_Ced LIKE '%22600781%'
         OR u.Usu_Ced LIKE '%1676514%'
         OR p.Prs_Nom LIKE '%TORRES%'
         OR p.Prs_Ape LIKE '%TORRES%'
         OR p.Prs_Ced LIKE '%22600781%'
         OR p.Prs_Ced LIKE '%1676514%'",
    $con
);

// 3. Probar la consulta exacta de ajax_empresas2 para 22600781 y 1676514
$cedulas = ['22600781', '1676514'];
if (!empty($response['find_usuarios'])) {
    foreach ($response['find_usuarios'] as $u) {
        if (!in_array($u['Usu_Ced'], $cedulas)) {
            $cedulas[] = $u['Usu_Ced'];
        }
    }
}

$response['login_empresas_test'] = [];
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
    
    // Probar hashes de contraseña
    $passTests = [];
    foreach ($rows as $r) {
        $hash = $r['Usu_Pal'];
        $passTests[$r['Usu_Cod']] = [
            'hash' => $hash,
            'is_123456_raw_md5' => ($hash === md5('123456')), // 123456 -> md5
            'is_123456_double_md5' => ($hash === md5(md5('123456'))),
            'is_123456_bcrypt_of_md5' => (strpos($hash, '$2y$') === 0 && password_verify(md5('123456'), $hash)),
            'is_123456_bcrypt_of_raw' => (strpos($hash, '$2y$') === 0 && password_verify('123456', $hash)),
            'is_pass_1676514_raw_md5' => ($hash === md5('1676514')),
            'is_pass_22600781_raw_md5' => ($hash === md5('22600781'))
        ];
    }
    
    $response['login_empresas_test'][$ced] = [
        'conteo' => count($rows),
        'empresas' => $rows,
        'pass_tests' => $passTests
    ];
}

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
