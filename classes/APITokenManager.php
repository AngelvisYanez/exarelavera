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
 * Estructura de tablas en base de datos:
 * - api_tokens: Tok_Id, Tok_Nombre, Tok_Hash, Tok_Resumen, Emp_Cod, Tok_Bdd, Tok_Cuota, Tok_Periodo, Tok_Usadas, Tok_Periodo_Inicio, Tok_Ultimo_Uso, Tok_Expira, Tok_Est ('A','I','R'), Tok_Creado_Por, Tok_Fec_Crea
 * - api_token_permisos: Per_Id, Tok_Id, Tip_Cod, Tip_Ruta, Per_Fec_Crea
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
    public function create($empCod, $nombre, $limiteTipo = 'D', $limiteCantidad = 0, $expiraEn = null, $permisos = null, $creadoPor = 1)
    {
        $emp = $this->empresaInfo($empCod);
        if (!$emp) {
            throw new \InvalidArgumentException("Empresa no encontrada: $empCod");
        }

        $rawToken = bin2hex(random_bytes(32)); // 64 caracteres hex
        $hash     = hash('sha256', $rawToken);
        $resumen  = substr($rawToken, -6);

        $limiteTipo = in_array(strtoupper($limiteTipo), ['D', 'M', 'NONE']) ? strtoupper($limiteTipo) : 'D';
        $limiteCantidad = max(0, (int) $limiteCantidad);

        $data = [
            'Emp_Cod'            => (int) $empCod,
            'Tok_Nombre'         => trim($nombre),
            'Tok_Hash'           => $hash,
            'Tok_Resumen'        => $resumen,
            'Tok_Bdd'            => $emp['Bdd'],
            'Tok_Cuota'          => $limiteCantidad,
            'Tok_Periodo'        => ($limiteTipo === 'NONE') ? 'D' : $limiteTipo,
            'Tok_Usadas'         => 0,
            'Tok_Periodo_Inicio' => date('Y-m-d H:i:s'),
            'Tok_Est'            => 'A',
            'Tok_Creado_Por'     => (int) $creadoPor,
        ];
        if (!empty($expiraEn)) {
            $data['Tok_Expira'] = date('Y-m-d H:i:s', strtotime($expiraEn));
        }

        $id = $this->api->insert('api_tokens', $data);
        if (!$id) {
            throw new \RuntimeException("No se pudo insertar el token: " . $this->api->getErrorMsg());
        }

        // Insertar permisos si se proporcionaron
        if (!empty($permisos) && is_array($permisos)) {
            foreach ($permisos as $ruta) {
                $ruta = trim((string)$ruta);
                if ($ruta !== '') {
                    $this->api->insert('api_token_permisos', [
                        'Tok_Id'   => (int)$id,
                        'Tip_Ruta' => $ruta
                    ]);
                }
            }
        }

        return [
            'id'           => $id,
            'Tok_Id'       => $id,
            'raw_token'    => $rawToken,
            'token'        => $rawToken,
            'token_prefix' => substr($rawToken, 0, 8),
            'Tok_Resumen'  => $resumen,
            'Emp_Cod'      => (int) $empCod,
            'Emp_Nom'      => $emp['Emp_Nom'],
            'Bdd'          => $emp['Bdd'],
            'Tok_Bdd'      => $emp['Bdd'],
            'nombre'       => $data['Tok_Nombre'],
        ];
    }

    public function generate($nombre, $empCod, $cuota = 0, $periodo = 'D', $expira = null, $creadoPor = null, $permisos = null)
    {
        try {
            $res = $this->create($empCod, $nombre, $periodo, $cuota, $expira, $permisos, $creadoPor ?: 1);
            return [
                'success'      => true,
                'token'        => $res['raw_token'],
                'token_prefix' => $res['token_prefix'],
                'id'           => $res['Tok_Id'],
                'Tok_Id'       => $res['Tok_Id'],
                'nombre'       => $res['nombre'],
                'Emp_Cod'      => $res['Emp_Cod'],
                'Emp_Nom'      => $res['Emp_Nom'],
                'Bdd'          => $res['Bdd']
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lista tokens (sin exponer el hash completo).
     */
    public function listAll($empCod = null)
    {
        $sql = "
            SELECT t.Tok_Id, t.Tok_Id as id, t.Emp_Cod, e.Emp_Nom, COALESCE(t.Tok_Bdd, d.Dat_Dis, 'ecoparkmining') as Bdd,
                   t.Tok_Nombre, t.Tok_Nombre as nombre, t.Tok_Resumen, t.Tok_Cuota, t.Tok_Cuota as limite_cantidad,
                   t.Tok_Periodo, t.Tok_Periodo as limite_tipo, t.Tok_Usadas, t.Tok_Usadas as usos_hoy,
                   t.Tok_Ultimo_Uso, t.Tok_Ultimo_Uso as ultimo_uso, t.Tok_Expira, t.Tok_Expira as expira_en,
                   t.Tok_Est, (t.Tok_Est = 'A') as activo, t.Tok_Fec_Crea, t.Tok_Fec_Crea as creado_el
              FROM api_tokens t
         LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
         LEFT JOIN data d ON d.Emp_Cod = t.Emp_Cod
        ";
        if ($empCod) {
            $sql .= " WHERE t.Emp_Cod = " . (int) $empCod;
        }
        $sql .= " ORDER BY t.Tok_Id DESC";

        $rows = $this->api->query($sql);
        foreach ($rows as &$r) {
            $r['id']              = (int) $r['Tok_Id'];
            $r['Tok_Id']          = (int) $r['Tok_Id'];
            $r['Emp_Cod']         = (int) $r['Emp_Cod'];
            $r['limite_cantidad'] = (int) $r['Tok_Cuota'];
            $r['usos_hoy']        = (int) $r['Tok_Usadas'];
            $r['activo']          = ($r['Tok_Est'] === 'A');
            $r['permisos']        = $this->getPermisos($r['Tok_Id']);
        }
        return $rows;
    }

    public function listTokens($empCod = null)
    {
        return $this->listAll($empCod);
    }

    /**
     * Retorna lista de permisos asignados a un token.
     */
    public function getPermisos($tokenId)
    {
        $tokenId = (int)$tokenId;
        if ($this->api->tableExists('api_token_permisos')) {
            $sql = "SELECT p.* FROM api_token_permisos p WHERE p.Tok_Id = $tokenId";
            return $this->api->query($sql);
        }
        return [];
    }

    /**
     * Asigna o actualiza permisos de un token.
     */
    public function setPermisos($tokenId, array $rutas)
    {
        $tokenId = (int)$tokenId;
        $this->api->query("DELETE FROM api_token_permisos WHERE Tok_Id = $tokenId");
        foreach ($rutas as $ruta) {
            $ruta = trim((string)$ruta);
            if ($ruta !== '') {
                $this->api->insert('api_token_permisos', [
                    'Tok_Id'   => $tokenId,
                    'Tip_Ruta' => $ruta
                ]);
            }
        }
        return true;
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
            SELECT t.*, e.Emp_Nom, COALESCE(t.Tok_Bdd, d.Dat_Dis, 'ecoparkmining') as Bdd
              FROM api_tokens t
         LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
         LEFT JOIN data d ON d.Emp_Cod = t.Emp_Cod
             WHERE t.Tok_Hash = " . $this->api->escape($hash) . "
             LIMIT 1
        ";
        $row = $this->api->queryRow($sql);
        if (!$row) {
            return null;
        }
        $row['id']              = (int) $row['Tok_Id'];
        $row['Tok_Id']          = (int) $row['Tok_Id'];
        $row['Emp_Cod']         = (int) $row['Emp_Cod'];
        $row['limite_cantidad'] = (int) $row['Tok_Cuota'];
        $row['usos_hoy']        = (int) $row['Tok_Usadas'];
        $row['activo']          = ($row['Tok_Est'] === 'A');
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

        if ($token['Tok_Est'] !== 'A') {
            return ['valid' => false, 'error' => 'Token desactivado'];
        }

        if (!empty($token['Tok_Expira']) && strtotime($token['Tok_Expira']) < time()) {
            return ['valid' => false, 'error' => 'Token expirado'];
        }

        if ($this->hasExceededLimit($token)) {
            return ['valid' => false, 'error' => 'Límite de peticiones superado (' . $token['Tok_Periodo'] . ')'];
        }

        if ($incrementUse) {
            $this->incrementUse($token['Tok_Id']);
        }

        return [
            'valid'   => true,
            'id'      => $token['Tok_Id'],
            'Tok_Id'  => $token['Tok_Id'],
            'token'   => $token,
            'Emp_Cod' => $token['Emp_Cod'],
            'Emp_Nom' => $token['Emp_Nom'],
            'Bdd'     => $token['Bdd'],
        ];
    }

    protected function hasExceededLimit($token)
    {
        $max = (int)$token['Tok_Cuota'];
        if ($max <= 0) {
            return false;
        }
        $usadas = (int)$token['Tok_Usadas'];
        if ($usadas >= $max) {
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
               SET Tok_Usadas = Tok_Usadas + 1,
                   Tok_Ultimo_Uso = '$now'
             WHERE Tok_Id = $tokenId
        ");
    }

    public function toggle($id, $activo)
    {
        $id = (int) $id;
        $val = $activo ? 'A' : 'I';
        return $this->api->update('api_tokens', 'Tok_Id', $id, ['Tok_Est' => $val]);
    }

    public function delete($id)
    {
        $id = (int) $id;
        return $this->api->delete('api_tokens', 'Tok_Id', $id);
    }
}
