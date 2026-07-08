<?php
require_once __DIR__ . '/../../../DATA/MysqlConexion.php';
require_once __DIR__ . '/../../../DATA/MysqlDatos.php';
require_once __DIR__ . '/../../../classes/FacturacionElectronica.php';

// ── Helper: get connection ──────────────────────────────────────────────────

function emitirDb($bdd) {
    $obBD_conexion = new MysqlConexion($bdd);
    $obBD_con1 = new MysqlDatos;
    $obBD_con1->setConnection($obBD_conexion);
    return [$obBD_conexion, $obBD_con1];
}

// ── Buscar productos ─────────────────────────────────────────────────────────

$app->post('/v1/facturacion/emitir/productos/buscar', function () {
    $body = getBody();
    $bdd = $body['Bdd'] ?? 'servicios';
    $empCod = (int)($body['Emp_Cod'] ?? 0);
    $search = trim($body['search'] ?? '');

    if ($empCod <= 0) jsonError(400, 'Emp_Cod requerido');

    list($obBD_conexion, $obBD_con1) = emitirDb($bdd);

    $where = "";
    if ($search !== '') {
        $esc = $obBD_conexion->conexion->real_escape_string($search);
        $where = "AND (i.Ite_Lar LIKE '%$esc%' OR p.Pro_Obs LIKE '%$esc%' OR p.Pro_Ide LIKE '%$esc%')";
    }

    $sql = "SELECT p.Pro_Cod, p.Pro_Obs, i.Ite_Lar AS Pro_Des, p.Pro_Ide,
                   p.Iva_Cod, p.Pro_Bar, p.Pro_Est
            FROM producto p
            INNER JOIN item i ON p.Ite_Cod = i.Ite_Cod
            INNER JOIN categorias c ON i.Cat_Cod = c.Cat_Cod
            WHERE p.Pro_Est = 'A' AND c.Emp_Cod = $empCod $where
            ORDER BY i.Ite_Lar LIMIT 100";

    $rows = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
    jsonOk($rows ?: []);
});

// ── Crear producto ───────────────────────────────────────────────────────────

$app->post('/v1/facturacion/emitir/productos/crear', function () {
    $body = getBody();
    $bdd = $body['Bdd'] ?? 'servicios';
    $empCod = (int)($body['Emp_Cod'] ?? 0);
    $proDes = trim($body['Pro_Des'] ?? '');
    $proObs = trim($body['Pro_Obs'] ?? '');

    if ($empCod <= 0) jsonError(400, 'Emp_Cod requerido');
    if ($proDes === '') jsonError(400, 'Pro_Des (nombre del producto) es requerido');

    list($obBD_conexion, $obBD_con1) = emitirDb($bdd);
    $mysqli = $obBD_con1->getMyCon($obBD_conexion);

    // 1. Get or create category (Cat_Cod)
    $cat = $obBD_con1->getRowConsultaSql(
        "SELECT Cat_Cod, Cat_Cdc FROM categorias WHERE Cat_Est='A' AND Emp_Cod = $empCod ORDER BY Cat_Cod LIMIT 1",
        $obBD_conexion
    );
    if (!$cat) {
        $catCdc = '001';
        $obBD_con1->grabaru(
            "INSERT INTO categorias(Cat_Cdc, Cat_Des, Cat_Tip, Cat_Rec, Emp_Cod) VALUES('$catCdc', 'GENERAL', 'S', 0, $empCod)",
            $obBD_conexion
        );
        $catCod = $obBD_con1->insercionid($obBD_conexion);
        $catCdc = '001';
    } else {
        $catCod = (int)$cat['Cat_Cod'];
        $catCdc = $cat['Cat_Cdc'];
    }

    // 2. Get or create marca (Mar_Cod)
    $mar = $obBD_con1->getRowConsultaSql(
        "SELECT Mar_Cod FROM marca WHERE Mar_Est='A' LIMIT 1",
        $obBD_conexion
    );
    if ($mar) {
        $marCod = (int)$mar['Mar_Cod'];
    } else {
        $obBD_con1->grabaru("INSERT INTO marca(Mar_Des) VALUES('GENERAL')", $obBD_conexion);
        $marCod = $obBD_con1->insercionid($obBD_conexion);
    }

    // 3. Get default Iva_Cod (first active IVA)
    $iva = $obBD_con1->getRowConsultaSql(
        "SELECT Iva_Cod FROM iva WHERE Iva_Est='A' LIMIT 1",
        $obBD_conexion
    );
    $ivaCod = $iva ? (int)$iva['Iva_Cod'] : 1;

    // 4. Get default Adq_Cod
    $adq = $obBD_con1->getRowConsultaSql(
        "SELECT Adq_Cod FROM adquisicio LIMIT 1",
        $obBD_conexion
    );
    $adqCod = $adq ? (int)$adq['Adq_Cod'] : 1;

    // 5. Get default Ubi_Cod
    $ubi = $obBD_con1->getRowConsultaSql(
        "SELECT Ubi_Cod FROM ubicacion LIMIT 1",
        $obBD_conexion
    );
    $ubiCod = $ubi ? (int)$ubi['Ubi_Cod'] : 1;

    // 6. Get default Uni_Cod
    $uni = $obBD_con1->getRowConsultaSql(
        "SELECT Uni_Cod FROM unidad LIMIT 1",
        $obBD_conexion
    );
    $uniCod = $uni ? (int)$uni['Uni_Cod'] : 1;

    // 7. Create item (Ite_Cor = short code, Ite_Lar = description)
    $iteCor = substr($proDes, 0, 20);
    $iteLarEsc = $mysqli->real_escape_string($proDes);
    $obBD_con1->grabaru(
        "INSERT INTO item(Cat_Cod, Ite_Cor, Ite_Lar) VALUES($catCod, '$iteCor', '$iteLarEsc')",
        $obBD_conexion
    );
    $iteCod = $obBD_con1->insercionid($obBD_conexion);
    if (empty($iteCod)) jsonError(500, 'Error al crear item');

    // 8. Get next Pro_Sec for this category
    $secRow = $obBD_con1->getRowConsultaSql(
        "SELECT IFNULL(MAX(Pro_Sec), 0) + 1 AS next FROM producto p
         INNER JOIN item i ON p.Ite_Cod = i.Ite_Cod
         WHERE i.Cat_Cod = $catCod",
        $obBD_conexion
    );
    $proSec = $secRow ? (int)$secRow['next'] : 1;

    // 9. Get next Pro_Ide
    $ideRow = $obBD_con1->getRowConsultaSql(
        "SELECT IFNULL(MAX(CAST(Pro_Ide AS DECIMAL)), 0) + 1 AS next FROM producto",
        $obBD_conexion
    );
    $proIde = str_pad($ideRow ? $ideRow['next'] : '1', 5, '0', STR_PAD_LEFT);

    // 10. Create producto
    $proObsEsc = $mysqli->real_escape_string($proObs);
    $proCdc = $catCdc . '.' . str_pad($proSec, 5, '0', STR_PAD_LEFT);
    $sqlProd = "INSERT INTO producto(
        Adq_Cod, Ite_Cod, Mar_Cod, Iva_Cod, Pro_Obs, Pro_Bar, Ubi_Cod, Uni_Cod,
        Pro_Sec, Pro_Cdc, Pro_Uni, Pro_Dsc, Pre_Cod, Pro_Ide, Pro_Tip, Pro_Est
    ) VALUES (
        $adqCod, $iteCod, $marCod, $ivaCod, '$proObsEsc', '', $ubiCod, $uniCod,
        $proSec, '$proCdc', 1, 0, NULL, '$proIde', 'S', 'A'
    )";
    $obBD_con1->grabaru($sqlProd, $obBD_conexion);
    $proCod = $obBD_con1->insercionid($obBD_conexion);
    if (empty($proCod)) jsonError(500, 'Error al crear producto');

    // 11. Create default price (precios table)
    $prePvp = (float)($body['Pre_Pvp'] ?? 0);
    $obBD_con1->grabaru(
        "INSERT INTO precios(Pro_Cod, Suc_Cod, Pre_Pvp, Pre_Est)
         VALUES($proCod, 1, '$prePvp', 'A')",
        $obBD_conexion
    );

    // 12. Create initial stock (stock table)
    $obBD_con1->grabaru(
        "INSERT INTO stock(Pro_Cod, Suc_Cod, Stk_Can, Stk_Prp)
         VALUES($proCod, 1, 0, 0)",
        $obBD_conexion
    );

    jsonOk([
        'Pro_Cod' => $proCod,
        'Pro_Des' => $proDes,
        'Pro_Ide' => $proIde,
        'Iva_Cod' => $ivaCod,
        'success' => true,
        'message' => 'Producto creado correctamente',
    ]);
});

$app->post('/v1/facturacion/emitir/comprobante', function () {
    try {
        $body = getBody();
        $bdd = $body['Bdd'] ?? 'servicios';
        $empCod = (int)($body['Emp_Cod'] ?? 0);
        $sucCod = (int)($body['Suc_Cod'] ?? 0);
        $punCod = (int)($body['Pun_Cod'] ?? 0);
        $cliCod = (int)($body['Cli_Cod'] ?? 0);
        $ciuCod = (int)($body['Ciu_Cod'] ?? 0);
        $vndCod = (int)($body['Vnd_Cod'] ?? 0);
        $ticCod = (int)($body['Tic_Cod'] ?? 1);
        $vetFec = $body['Vet_Fec'] ?? date('Y-m-d');
        $vetObs = $body['Vet_Obs'] ?? '';

        $items = $body['items'] ?? [];
        $pagos = $body['pagos'] ?? [];

        if (empty($empCod) || empty($sucCod) || empty($punCod) || empty($cliCod)) {
            jsonError(400, 'Emp_Cod, Suc_Cod, Pun_Cod y Cli_Cod son requeridos');
        }
        if (empty($items) || !is_array($items)) {
            jsonError(400, 'Debe especificar al menos un item');
        }

        $obBD_conexion = new MysqlConexion($bdd);
        $obBD_con1 = new MysqlDatos;
        $obBD_con1->setConnection($obBD_conexion);
        $mysqli = $obBD_con1->getMyCon($obBD_conexion);

        // ── 1. Obtener autorización electrónica activa ─────────────────────
        $aut = $obBD_con1->getRowConsultaSql(
            "SELECT a.* FROM autorizaci a
             INNER JOIN puntos_imp p ON a.Pun_Cod = p.Pun_Cod
             WHERE a.Aut_Tem = 'E' AND a.Aut_Est = 'A'
               AND a.Tic_Cod = $ticCod
               AND p.Pun_Cod = $punCod
               AND p.Suc_Cod = $sucCod
             LIMIT 1",
            $obBD_conexion
        );
        if (!$aut) {
            jsonError(400, 'No se encontró autorización electrónica activa para este punto de emisión');
        }
        $autCod = (int)$aut['Aut_Cod'];
        $autIni = (int)$aut['Aut_Ini'];
        $autFin = (int)$aut['Aut_Fin'];
        $autSri = $mysqli->real_escape_string($aut['Aut_Sri']);
        $punSri = $mysqli->real_escape_string($aut['Pun_Sri']);

        // ── 2. Obtener o crear caja apertura ──────────────────────────────
        $caj = $obBD_con1->getRowConsultaSql(
            "SELECT Caj_Cod FROM caja_aper
             WHERE Pun_Cod = $punCod AND Caj_Fec = '$vetFec' AND Caj_Est = 'A'
             LIMIT 1",
            $obBD_conexion
        );
        if ($caj) {
            $cajCod = (int)$caj['Caj_Cod'];
        } else {
            $cajInsert = "INSERT INTO caja_aper(Pun_Cod, Caj_Fec, Caj_Hoi, Caj_Est, Caj_Gen)
                          VALUES ($punCod, '$vetFec', CURTIME(), 'A', 'S')";
            $obBD_con1->grabaru($cajInsert, $obBD_conexion);
            $cajCod = $obBD_con1->insercionid($obBD_conexion);
            if (empty($cajCod)) {
                jsonError(500, 'Error al crear caja apertura');
            }
        }

        // ── 3. Generar siguiente número secuencial ────────────────────────
        $nextNumSql = "SELECT
            CASE
                WHEN MAX(v.Vet_Num) IS NOT NULL AND MAX(v.Vet_Num) >= $autFin THEN (
                    SELECT MIN(t.Vet_Num)+1
                    FROM ventas t
                    INNER JOIN autorizaci AS ta ON t.Aut_Cod = ta.Aut_Cod
                    INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                    WHERE tp.Suc_Cod = $sucCod
                      AND ta.Pun_Sri = '$punSri'
                      AND ta.Aut_Sri = '$autSri'
                      AND ta.Tic_Cod = $ticCod
                      AND t.Vet_Num BETWEEN $autIni AND $autFin
                      AND NOT EXISTS (
                          SELECT NULL FROM ventas n
                          WHERE n.Vet_Num = t.Vet_Num+1
                            AND n.Aut_Cod = ta.Aut_Cod
                            AND n.Vet_Est = 'A'
                      )
                )
                ELSE IFNULL(MAX(v.Vet_Num), $autIni-1)+1
            END AS 'next'
            FROM ventas v
            INNER JOIN autorizaci a ON v.Aut_Cod = a.Aut_Cod
            INNER JOIN puntos_imp p ON p.Pun_Cod = a.Pun_Cod
            WHERE p.Suc_Cod = $sucCod
              AND a.Pun_Sri = '$punSri'
              AND a.Aut_Sri = '$autSri'
              AND a.Tic_Cod = $ticCod
              AND v.Vet_Num BETWEEN $autIni AND $autFin";
        $nextRow = $obBD_con1->getRowConsultaSql($nextNumSql, $obBD_conexion);
        $vetNum = $nextRow ? (int)$nextRow['next'] : $autIni;
        if ($vetNum > $autFin) {
            jsonError(400, 'Se ha agotado el rango de numeración para esta autorización');
        }

        // ── 4. Validar cliente ───────────────────────────────────────────
        $cli = $obBD_con1->getRowConsultaSql(
            "SELECT Cli_Cod, Cli_Nom, Cli_Ruc, Cli_Dir, Cli_Tel FROM cliente WHERE Cli_Cod = $cliCod AND Cli_Est = 'A'",
            $obBD_conexion
        );
        if (!$cli) {
            jsonError(404, 'Cliente no encontrado');
        }

        // ── 5. Insertar ventas header ────────────────────────────────────
        $vetHor = $vetFec . ' ' . date('H:i:s');
        $vetDes = (float)($body['Vet_Des'] ?? 0);
        $tpcCod = !empty($body['Tpc_Cod']) ? (int)$body['Tpc_Cod'] : 'NULL';
        $vetProp = (float)($body['Vet_Prop'] ?? 0);
        $vndCodAux = !empty($body['Vnd_Cod_Aux']) ? (int)$body['Vnd_Cod_Aux'] : 'NULL';

        $sqlVentas = "INSERT INTO ventas SET
            Tic_Cod = $ticCod,
            Cli_Cod = $cliCod,
            Ciu_Cod = $ciuCod,
            Caj_Cod = $cajCod,
            Vnd_Cod = $vndCod,
            Vet_Num = '$vetNum',
            Vet_Obs = '" . $mysqli->real_escape_string($vetObs) . "',
            Aut_Cod = $autCod,
            Vet_Des = '$vetDes',
            Vet_Hor = '$vetHor',
            Tpc_Cod = $tpcCod,
            Vet_Prop = '$vetProp',
            Vnd_Cod_Aux = $vndCodAux";

        $obBD_con1->grabaru($sqlVentas, $obBD_conexion);
        $vetCod = $obBD_con1->insercionid($obBD_conexion);
        if (empty($vetCod)) {
            jsonError(500, 'Error al insertar el comprobante');
        }

        // ── 6. Insertar items en ventas_det ──────────────────────────────
        $itemIndex = 0;
        foreach ($items as $item) {
            $proCod = (int)($item['Pro_Cod'] ?? 0);
            $ivaCod = (int)($item['Iva_Cod'] ?? 1);
            $vetCan = (float)($item['Vet_Can'] ?? 1);
            $vetPru = (float)($item['Vet_Pru'] ?? 0);
            $vetImp = $vetCan * $vetPru;
            $vetDec = (float)($item['Vet_Dec'] ?? 0);
            $vetIce = (float)($item['Vet_Ice'] ?? 0);
            $vetCre = (float)($item['Vet_Cre'] ?? 0);
            $vetUni = (float)($item['Vet_Uni'] ?? 1);
            $vetRec = (float)($item['Vet_Rec'] ?? 0);
            $ngeCod = !empty($item['Nge_Cod']) ? (int)$item['Nge_Cod'] : 0;
            $cntCod = !empty($item['Cnt_Cod']) ? (int)$item['Cnt_Cod'] : 0;
            $renCod = isset($item['Ren_Cod']) ? (int)$item['Ren_Cod'] : 'NULL';
            $renIva = isset($item['Ren_Iva']) ? (int)$item['Ren_Iva'] : 'NULL';
            $imeCod = isset($item['Ime_Cod']) ? (int)$item['Ime_Cod'] : 'NULL';
            $desAdi = isset($item['Des_Adi']) ? "'" . $mysqli->real_escape_string($item['Des_Adi']) . "'" : 'NULL';

            $sqlDet = "INSERT INTO ventas_det SET
                Vet_Cod = $vetCod,
                Pro_Cod = $proCod,
                Vet_Can = '$vetCan',
                Iva_Cod = $ivaCod,
                Vet_Pru = '$vetPru',
                Vet_Imp = '$vetImp',
                Vet_Dec = '$vetDec',
                Vet_Ite = '$itemIndex',
                Vet_Ice = '$vetIce',
                Vet_Cre = '$vetCre',
                Vet_Uni = '$vetUni',
                Vet_Rec = '$vetRec',
                Nge_Cod = '$ngeCod',
                Asi_Int = '0',
                Cnt_Cod = '$cntCod',
                Vet_Int = '$itemIndex',
                Ren_Cod = $renCod,
                Ren_Iva = $renIva,
                Ime_Cod = $imeCod,
                Des_Adi = $desAdi";
            $obBD_con1->grabaru($sqlDet, $obBD_conexion);
            $itemIndex++;
        }

        // ── 7. Insertar pagos en pago_venta ──────────────────────────────
        if (empty($pagos) || !is_array($pagos)) {
            $totalImp = 0;
            foreach ($items as $item) {
                $totalImp += (float)($item['Vet_Can'] ?? 1) * (float)($item['Vet_Pru'] ?? 0);
            }
            $pagos = [['Pag_Cod' => 1, 'Vet_Tot' => $totalImp, 'Bak_Cod' => 1]];
        }
        foreach ($pagos as $pago) {
            $pagCod = (int)($pago['Pag_Cod'] ?? 1);
            $bakCod = !empty($pago['Bak_Cod']) ? (int)$pago['Bak_Cod'] : 1;
            $banCod = !empty($pago['Ban_Cod']) ? (int)$pago['Ban_Cod'] : 'NULL';
            $vetTot = (float)($pago['Vet_Tot'] ?? 0);
            $vetCue = isset($pago['Vet_Cue']) ? "'" . $mysqli->real_escape_string($pago['Vet_Cue']) . "'" : 'NULL';
            $vetChe = isset($pago['Vet_Che']) ? "'" . $mysqli->real_escape_string($pago['Vet_Che']) . "'" : 'NULL';
            $pldCod = isset($pago['Pld_Cod']) ? (int)$pago['Pld_Cod'] : 'NULL';

            $sqlPago = "INSERT INTO pago_venta (Vet_Cod, Bak_Cod, Ban_Cod, Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num, Pld_Cod)
                        VALUES ($vetCod, $bakCod, $banCod, $pagCod, $vetCue, $vetChe, '$vetTot', '$vetNum', $pldCod)";
            $obBD_con1->grabaru($sqlPago, $obBD_conexion);
        }

        // ── 8. Generar clave de acceso ───────────────────────────────────
        $obBD_elect = getFacturaElectClass($obBD_conexion, $empCod);
        $claveAcceso = $obBD_elect->getClaveAcceso($autCod, $vetFec, $vetNum, $obBD_conexion);
        if (empty($claveAcceso)) {
            jsonError(500, 'No se pudo generar la clave de acceso');
        }

        // ── 9. Guardar clave de acceso en DB ────────────────────────────
        $claEsc = $mysqli->real_escape_string($claveAcceso);
        $obBD_con1->grabaru(
            "UPDATE ventas SET Vet_Xml='$claEsc' WHERE Vet_Cod=$vetCod",
            $obBD_conexion
        );

        // ── 10. Generar XML factura ──────────────────────────────────────
        $xmlResult = $obBD_elect->createXmlFactura($vetCod, $autCod, $claveAcceso, $obBD_conexion);
        if (!$xmlResult) {
            jsonError(500, 'Error al generar el XML');
        }

        // ── 11. Preparar paths para firma y autorización ─────────────────
        $empDir = getEmpresaXmlDir($empCod);
        $base = __DIR__ . '/../../../facturacion/FRONT/';

        $xmlUnsignedPath   = $empDir . $claveAcceso . '.xml';
        $xmlSignedPath     = $empDir . $claveAcceso . '_F.xml';
        $xmlAuthorizedPath = $empDir . $claveAcceso . '_A.xml';

        if (!is_readable($xmlUnsignedPath)) {
            $altPath = $base . $claveAcceso . '.xml';
            if (is_readable($altPath)) {
                if (!is_dir($empDir)) mkdir($empDir, 0775, true);
                copy($altPath, $xmlUnsignedPath);
            } else {
                jsonError(500, 'El XML generado no se encontró en el servidor');
            }
        }

        // ── 12. Obtener firma electrónica ────────────────────────────────
        $llave = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM llave_elect WHERE Lla_Est='A' AND Emp_Cod=$empCod",
            $obBD_conexion
        );
        if (!$llave || empty($llave['Lla_Rut']) || empty($llave['Lla_Cla'])) {
            jsonError(400, 'No se encontró firma electrónica configurada para esta empresa');
        }

        // ── 13. Configuración SRI (producción / pruebas) ─────────────────
        $config = $obBD_con1->getRowConsultaSql(
            "SELECT * FROM confi_fact WHERE Emp_Cod=$empCod",
            $obBD_conexion
        );

        require_once __DIR__ . '/../../../Librerias/FactElect/FirmaElectronica.php';
        $DocElect = new FirmaElectronica();
        $DocElect->setProduction($config && $config['Cof_Fac'] * 1 == 2);

        $keyPath = $empDir . $llave['Lla_Rut'];
        if (!is_readable($keyPath)) {
            $keyPath = $base . $empCod . '/' . $llave['Lla_Rut'];
            if (!is_readable($keyPath)) {
                $keyPath = $base . $llave['Lla_Rut'];
            }
        }

        // ── 14. Firmar XML ───────────────────────────────────────────────
        $DocElect->setFileSignedPath($xmlSignedPath);
        $doc = $DocElect->sendToSign($xmlUnsignedPath, $keyPath, $llave['Lla_Cla']);
        if (!$doc || $doc['success'] !== true || empty($doc['xml'])) {
            jsonError(500, 'Error al firmar el documento: ' . ($doc['message'] ?? 'Error desconocido'));
        }

        // ── 15. Enviar al SRI ────────────────────────────────────────────
        $DocElect->setFileSignedPath($xmlSignedPath);
        $result = $DocElect->sendToSri($xmlSignedPath);
        if (!$result || $result['success'] !== true) {
            $msg = $result['message'] ?? 'Error al enviar al SRI';
            if (!empty($result['informacionAdicional'])) $msg .= ' - ' . $result['informacionAdicional'];
            jsonError(502, $msg);
        }

        // ── 16. Autorizar en SRI ─────────────────────────────────────────
        $DocElect->setFileAutorized($xmlAuthorizedPath);
        $autResult = $DocElect->autorizarSri($claveAcceso);
        if (!$autResult || $autResult['success'] !== true) {
            $msg = $autResult['message'] ?? 'Error al autorizar en el SRI';
            if (!empty($autResult['informacionAdicional'])) $msg .= ' - ' . $autResult['informacionAdicional'];
            jsonError(502, $msg, [
                'Vet_Cod' => $vetCod,
                'claveAcceso' => $claveAcceso,
                'estado' => $autResult['estado'] ?? 'DESCONOCIDO',
                'reintentar' => $autResult['reintentar'] ?? false,
            ]);
        }

        // ── 17. Actualizar DB con autorización ──────────────────────────
        $numeroAutorizacion = $mysqli->real_escape_string($autResult['numeroAutorizacion']);
        $obBD_con1->grabaru(
            "UPDATE ventas SET Vet_Sri='$numeroAutorizacion', Vet_Aut='S' WHERE Vet_Cod=$vetCod",
            $obBD_conexion
        );

        // ── 18. Limpiar archivos intermedios ─────────────────────────────
        if (is_readable($xmlUnsignedPath)) {
            $baseCheck = $base . $claveAcceso . '.xml';
            if ($xmlUnsignedPath !== $baseCheck) unlink($xmlUnsignedPath);
        }
        if (is_readable($xmlSignedPath)) unlink($xmlSignedPath);

        jsonOk([
            'Vet_Cod' => $vetCod,
            'Vet_Num' => $vetNum,
            'claveAcceso' => $claveAcceso,
            'success' => true,
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => $autResult['fechaAutorizacion'] ?? '',
            'estado' => 'AUTORIZADO',
            'message' => 'Comprobante emitido y autorizado correctamente',
        ]);

    } catch (Exception $e) {
        jsonError(500, 'Error al emitir comprobante: ' . $e->getMessage());
    }
});
