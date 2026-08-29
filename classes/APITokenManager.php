<?php

require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/DataAPI.php';

if (!function_exists('random_bytes')) {
    function random_bytes($length)
    {
        return openssl_random_pseudo_bytes($length);
    }
}

/**
 * Gestión de tokens de acceso a la API.
 *
 * Los tokens son cadenas aleatorias que se guardan SOLO como hash SHA-256.
 * El valor en claro solo se muestra una vez en el momento de la creación.
 *
 * Cada token está asociado a una empresa (Emp_Cod) y a su base de datos
 * distribuida (Bdd), y puede llevar un límite de consultas por día (D) o
 * por mes (M). 0 = ilimitado.
 */
class APITokenManager
{
    protected $api;

    public function __construct($bdd = null)
    {
        if ($bdd) {
            $this->api = new DataAPI($bdd);
            return;
        }
        $candidates = array('exa', 'ecoparkmining');
        foreach ($candidates as $candidate) {
            try {
                $api = new DataAPI($candidate);
                if ($api->isConnected() && $api->tableExists('api_tokens')) {
                    $this->api = $api;
                    return;
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }
        $this->api = new DataAPI('exa');
    }

    /**
     * Devuelve el nombre de la empresa y la base de datos distribuida a partir del Emp_Cod.
     */
    public function empresaInfo($empCod)
    {
        $empCod = (int) $empCod;
        $row = $this->api->queryRow("
            SELECT e.Emp_Cod, e.Emp_Nom, e.Emp_Ruc, d.Dat_Dis
              FROM empresas e
         LEFT JOIN data d ON d.Emp_Cod = e.Emp_Cod
             WHERE e.Emp_Cod = $empCod
             LIMIT 1
        ");
        if (!$row) {
            return null;
        }
        return [
            'Emp_Cod' => (int) $row['Emp_Cod'],
            'Emp_Nom' => $row['Emp_Nom'],
            'Emp_Ruc' => $row['Emp_Ruc'],
            'Bdd'     => !empty($row['Dat_Dis']) ? $row['Dat_Dis'] : 'ecoparkmining',
        ];
    }

    /**
     * Lista todas las empresas activas disponibles para asignar tokens.
     */
    public function listarEmpresas()
    {
        return $this->api->query("
            SELECT e.Emp_Cod, e.Emp_Nom, e.Emp_Ruc, COALESCE(d.Dat_Dis, 'ecoparkmining') as Bdd
              FROM empresas e
         LEFT JOIN data d ON d.Emp_Cod = e.Emp_Cod
             WHERE e.Emp_Est = 'A'
          ORDER BY e.Emp_Nom ASC
        ");
    }

    /**
     * Crea un nuevo token para una empresa y retorna el token en texto claro (una sola vez).
     */
    public function create($empCod, $nombre, $limiteTipo = 'D', $limiteCantidad = 0, $expiraEn = null, $modulos = null)
    {
        $emp = $this->empresaInfo($empCod);
        if (!$emp) {
            throw new \InvalidArgumentException("Empresa no encontrada: $empCod");
        }

        $rawToken = bin2hex(random_bytes(32)); // 64 caracteres hex
        $hash     = hash('sha256', $rawToken);
        $prefix   = substr($rawToken, 0, 8);

        if ($modulos === null || $modulos === '') {
            $modulosJson = json_encode(['*']);
        } elseif (is_array($modulos)) {
            $modulosJson = json_encode(array_values($modulos));
        } else {
            $modulosJson = json_encode([$modulos]);
        }

        $limiteTipo = in_array(strtoupper($limiteTipo), ['D', 'M', 'NONE']) ? strtoupper($limiteTipo) : 'D';
        $limiteCantidad = max(0, (int) $limiteCantidad);

        $data = [
            'Emp_Cod'         => (int) $empCod,
            'nombre'          => trim($nombre),
            'token_hash'      => $hash,
            'token_prefix'    => $prefix,
            'limite_tipo'     => $limiteTipo,
            'limite_cantidad' => $limiteCantidad,
            'modulos'         => $modulosJson,
            'activo'          => 1,
            'creado_el'       => date('Y-m-d H:i:s'),
        ];
        if (!empty($expiraEn)) {
            $data['expira_en'] = date('Y-m-d H:i:s', strtotime($expiraEn));
        }

        $id = $this->api->insert('api_tokens', $data);
        if (!$id) {
            throw new \RuntimeException("No se pudo insertar el token: " . $this->api->getErrorMsg());
        }

        return [
            'id'           => $id,
            'raw_token'    => $rawToken,
            'token_prefix' => $prefix,
            'Emp_Cod'      => (int) $empCod,
            'Emp_Nom'      => $emp['Emp_Nom'],
            'Bdd'          => $emp['Bdd'],
            'nombre'       => $data['nombre'],
            'modulos'      => json_decode($modulosJson, true),
        ];
    }

    public function generate($nombre, $empCod, $cuota = 0, $periodo = 'D', $expira = null, $creadoPor = null, $permisos = null)
    {
        try {
            $res = $this->create($empCod, $nombre, $periodo, $cuota, $expira);
            return [
                'success' => true,
                'token' => $res['raw_token'],
                'token_prefix' => $res['token_prefix'],
                'id' => $res['id'],
                'nombre' => $res['nombre'],
                'Emp_Cod' => $res['Emp_Cod'],
                'Emp_Nom' => $res['Emp_Nom'],
                'Bdd' => $res['Bdd']
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lista tokens (sin exponer el hash completo ni el texto claro).
     */
    public function listAll($empCod = null)
    {
        $sql = "
            SELECT t.id, t.Emp_Cod, e.Emp_Nom, COALESCE(d.Dat_Dis, 'ecoparkmining') as Bdd,
                   t.nombre, t.token_prefix, t.limite_tipo, t.limite_cantidad,
                   t.usos_hoy, t.usos_mes, t.ultimo_uso, t.modulos, t.activo,
                   t.creado_el, t.expira_en
              FROM api_tokens t
         LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
         LEFT JOIN data d ON d.Emp_Cod = t.Emp_Cod
        ";
        if ($empCod) {
            $sql .= " WHERE t.Emp_Cod = " . (int) $empCod;
        }
        $sql .= " ORDER BY t.id DESC";

        $rows = $this->api->query($sql);
        foreach ($rows as &$r) {
            $r['id']              = (int) $r['id'];
            $r['Emp_Cod']         = (int) $r['Emp_Cod'];
            $r['limite_cantidad'] = (int) $r['limite_cantidad'];
            $r['usos_hoy']        = (int) $r['usos_hoy'];
            $r['usos_mes']        = (int) $r['usos_mes'];
            $r['activo']          = (int) $r['activo'] === 1;
            $r['modulos']         = !empty($r['modulos']) ? json_decode($r['modulos'], true) : ['*'];
        }
        return $rows;
    }

    public function listTokens($empCod = null)
    {
        return $this->listAll($empCod);
    }

    public function getPermisos($tokenId)
    {
        $tokenId = (int)$tokenId;
        if ($this->api->tableExists('api_tokens_permisos')) {
            $sql = "SELECT p.* FROM api_tokens_permisos p WHERE p.token_id = $tokenId";
            return $this->api->query($sql);
        }
        return [];
    }

    /**
     * Busca un token por su valor en texto claro.
     */
    public function findByRaw($rawToken)
    {
        $rawToken = trim($rawToken);
        if (strlen($rawToken) < 16) {
            return null;
        }
        $hash = hash('sha256', $rawToken);
        $sql = "
            SELECT t.*, e.Emp_Nom, COALESCE(d.Dat_Dis, 'ecoparkmining') as Bdd
              FROM api_tokens t
         LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
         LEFT JOIN data d ON d.Emp_Cod = t.Emp_Cod
             WHERE t.token_hash = " . $this->api->escape($hash) . "
             LIMIT 1
        ";
        $row = $this->api->queryRow($sql);
        if (!$row) {
            return null;
        }
        $row['id']              = (int) $row['id'];
        $row['Emp_Cod']         = (int) $row['Emp_Cod'];
        $row['limite_cantidad'] = (int) $row['limite_cantidad'];
        $row['usos_hoy']        = (int) $row['usos_hoy'];
        $row['usos_mes']        = (int) $row['usos_mes'];
        $row['activo']          = (int) $row['activo'] === 1;
        $row['modulos']         = !empty($row['modulos']) ? json_decode($row['modulos'], true) : ['*'];
        return $row;
    }

    /**
     * Valida un token, verifica expiración, estado activo y límites de uso.
     * Si $incrementUse = true, incrementa el contador de consumo.
     */
    public function validate($rawToken, $incrementUse = true, $modulo = null)
    {
        $token = $this->findByRaw($rawToken);
        if (!$token) {
            return ['valid' => false, 'error' => 'Token inexistente'];
        }

        if (!$token['activo']) {
            return ['valid' => false, 'error' => 'Token desactivado'];
        }

        if (!empty($token['expira_en']) && strtotime($token['expira_en']) < time()) {
            return ['valid' => false, 'error' => 'Token expirado'];
        }

        if ($modulo !== null && !empty($token['modulos'])) {
            $allowed = $token['modulos'];
            if (!in_array('*', $allowed, true) && !in_array($modulo, $allowed, true)) {
                return ['valid' => false, 'error' => "Token sin permiso para el módulo: $modulo"];
            }
        }

        if ($this->hasExceededLimit($token)) {
            return ['valid' => false, 'error' => 'Límite de peticiones superado (' . $token['limite_tipo'] . ')'];
        }

        if ($incrementUse) {
            $this->incrementUse($token['id']);
        }

        return [
            'valid'   => true,
            'id'      => $token['id'],
            'token'   => $token,
            'Emp_Cod' => $token['Emp_Cod'],
            'Emp_Nom' => $token['Emp_Nom'],
            'Bdd'     => $token['Bdd'],
        ];
    }

    protected function hasExceededLimit($token)
    {
        $tipo = $token['limite_tipo'];
        $max  = $token['limite_cantidad'];
        if ($max <= 0 || $tipo === 'NONE') {
            return false;
        }
        if ($tipo === 'D' && $token['usos_hoy'] >= $max) {
            return true;
        }
        if ($tipo === 'M' && $token['usos_mes'] >= $max) {
            return true;
        }
        return false;
    }

    public function incrementUse($tokenId)
    {
        $tokenId = (int) $tokenId;
        $now = date('Y-m-d H:i:s');
        $this->api->query("
            UPDATE api_tokens
               SET usos_hoy = usos_hoy + 1,
                   usos_mes = usos_mes + 1,
                   ultimo_uso = '$now'
             WHERE id = $tokenId
        ");
    }

    public function toggle($id, $activo)
    {
        $id = (int) $id;
        $val = $activo ? 1 : 0;
        return $this->api->update('api_tokens', 'id', $id, ['activo' => $val]);
    }

    public function delete($id)
    {
        $id = (int) $id;
        return $this->api->delete('api_tokens', 'id', $id);
    }
}
