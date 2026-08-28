<?php

require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/DataAPI.php';

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
        $candidates = ['ecoparkmining', 'exa', 'exa_master'];
        foreach ($candidates as $candidate) {
            try {
                $api = new DataAPI($candidate);
                if ($api->tableExists('api_tokens')) {
                    $this->api = $api;
                    return;
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }
        $this->api = new DataAPI('ecoparkmining');
    }

    /**
     * Devuelve el nombre de la empresa y la base de datos distribuida a partir del Emp_Cod.
     */
    public function empresaInfo($empCod)
    {
        $emp = $this->api->queryRow(
            "SELECT Emp_Nom FROM empresas WHERE Emp_Cod=" . ((int)$empCod) . " LIMIT 1"
        );
        $bdd = $this->api->queryScalar(
            "SELECT data.Dat_Dis FROM data WHERE data.Emp_Cod=" . ((int)$empCod) . " LIMIT 1"
        );
        return [
            'Emp_Cod' => (int)$empCod,
            'Emp_Nom' => $emp ? $emp['Emp_Nom'] : null,
            'Bdd' => $bdd ?: ($this->api->bdd ?: 'ecoparkmining'),
        ];
    }

    /**
     * Conecta a la base distribuida del token y devuelve si la conexión es válida
     * (el Dat_Dis existe como base de datos).
     */
    public function validarBdd($bdd)
    {
        if (empty($bdd)) {
            return false;
        }
        try {
            $api = new DataAPI($bdd);
            return $api->conexion && $api->conexion->conexion && empty($api->conexion->Error);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Genera un nuevo token de acceso.
     *
     * @return array ['success', 'token' (en claro), 'id'] o ['success'=>false,'error']
     */
    public function generate($nombre, $empCod, $cuota = 0, $periodo = 'D', $expira = null, $creadoPor = null, array $permisos = null)
    {
        $nombre = trim($nombre);
        $empCod = (int)$empCod;
        $cuota = max(0, (int)$cuota);
        $periodo = $periodo === 'M' ? 'M' : 'D';

        if ($nombre === '') {
            return ['success' => false, 'error' => 'El nombre del token es requerido'];
        }
        if ($empCod <= 0) {
            return ['success' => false, 'error' => 'Emp_Cod inválido'];
        }

        $info = $this->empresaInfo($empCod);
        if (empty($info['Emp_Nom'])) {
            return ['success' => false, 'error' => 'Empresa no encontrada'];
        }
        $bdd = $info['Bdd'];

        for ($i = 0; $i < 5; $i++) {
            $rawToken = bin2hex(random_bytes(32)); // 64 hex chars
            $existe = $this->api->count('api_tokens', ['Tok_Hash' => hash('sha256', $rawToken)]);
            if ($existe === 0) {
                break;
            }
        }
        if ($this->api->count('api_tokens', ['Tok_Hash' => hash('sha256', $rawToken)]) > 0) {
            return ['success' => false, 'error' => 'No se pudo generar un token único, intente de nuevo'];
        }

        $data = [
            'Tok_Nombre' => $nombre,
            'Tok_Hash' => hash('sha256', $rawToken),
            'Tok_Resumen' => substr($rawToken, -6),
            'Emp_Cod' => $empCod,
            'Tok_Bdd' => $bdd,
            'Tok_Cuota' => $cuota,
            'Tok_Periodo' => $periodo,
            'Tok_Usadas' => 0,
            'Tok_Periodo_Inicio' => date('Y-m-d H:i:s'),
            'Tok_Expira' => $expira ? date('Y-m-d H:i:s', strtotime($expira)) : null,
            'Tok_Est' => 'A',
            'Tok_Creado_Por' => $creadoPor,
        ];

        $id = $this->api->insert('api_tokens', $data);
        if (!$id) {
            return ['success' => false, 'error' => 'No se pudo crear el token: ' . $this->api->getErrorMsg()];
        }

        if (is_array($permisos) && !empty($permisos)) {
            $resPerm = $this->setPermisos((int)$id, $permisos);
            if (!$resPerm['success']) {
                $this->api->delete('api_tokens', 'Tok_Id', (int)$id);
                return $resPerm;
            }
        }

        $resPermisos = $this->getPermisos((int)$id);

        return [
            'success' => true,
            'id' => (int)$id,
            'token' => $rawToken,
            'nombre' => $nombre,
            'Emp_Cod' => $empCod,
            'Bdd' => $bdd,
            'cuota' => $cuota,
            'periodo' => $periodo,
            'permisos' => $resPermisos,
        ];
    }

    /**
     * Busca un token por su valor en claro (por hash).
     */
    public function findByRaw($rawToken)
    {
        if (empty($rawToken)) {
            return null;
        }
        return $this->api->queryRow(
            "SELECT * FROM api_tokens WHERE Tok_Hash=" . $this->api->escape(hash('sha256', $rawToken)) . " LIMIT 1"
        );
    }

    /**
     * Valida un token en claro para consumo de la API.
     * Verifica: existencia, estado activo, no expirado y cuota disponible.
     *
     * @return array|false  array con info del token (Emp_Cod, Bdd, ...) o false si no es válido.
     */
    public function validate($rawToken, $usarCuota = true)
    {
        $row = $this->findByRaw($rawToken);
        if (!$row) {
            return ['valid' => false, 'reason' => 'not_found'];
        }

        if ($row['Tok_Est'] !== 'A') {
            return ['valid' => false, 'reason' => 'inactive'];
        }

        if (!empty($row['Tok_Expira']) && strtotime($row['Tok_Expira']) < time()) {
            return ['valid' => false, 'reason' => 'expired'];
        }

        $cuota = (int)$row['Tok_Cuota'];
        $periodo = $row['Tok_Periodo'];
        if ($cuota > 0 && !empty($row['Tok_Periodo_Inicio'])) {
            $inicio = strtotime($row['Tok_Periodo_Inicio']);
            $limite = $periodo === 'M'
                ? strtotime('+1 month', strtotime(date('Y-m-01', $inicio)))
                : $inicio + 86400;
            if (time() >= $limite) {
                $this->api->update('api_tokens', [
                    'Tok_Usadas' => 0,
                    'Tok_Periodo_Inicio' => date('Y-m-d H:i:s'),
                ], 'Tok_Id', $row['Tok_Id']);
                $row['Tok_Usadas'] = 0;
            }
        }

        if ($cuota > 0 && (int)$row['Tok_Usadas'] >= $cuota) {
            return ['valid' => false, 'reason' => 'quota_exceeded'];
        }

        if ($usarCuota) {
            $this->api->update('api_tokens', [
                'Tok_Usadas' => (int)$row['Tok_Usadas'] + 1,
                'Tok_Ultimo_Uso' => date('Y-m-d H:i:s'),
            ], 'Tok_Id', $row['Tok_Id']);
        }

        return [
            'valid' => true,
            'id' => (int)$row['Tok_Id'],
            'Emp_Cod' => (int)$row['Emp_Cod'],
            'Bdd' => $row['Tok_Bdd'] ?: 'ecoparkmining',
            'nombre' => $row['Tok_Nombre'],
        ];
    }

    public function list($empCod = null)
    {
        $where = [];
        if ($empCod !== null && (int)$empCod > 0) {
            $where['Emp_Cod'] = (int)$empCod;
        }
        $rows = $this->api->listAll('api_tokens', $where, 'Tok_Id DESC');
        foreach ($rows as &$r) {
            $r['permisos'] = $this->getPermisos((int)$r['Tok_Id']);
        }
        return $rows;
    }

    public function revoke($id)
    {
        return $this->updateLimits($id, null, null, null, 'R');
    }

    public function updateLimits($id, $cuota = null, $periodo = null, $expira = null, $estado = null)
    {
        $id = (int)$id;
        $tok = $this->api->findById('api_tokens', 'Tok_Id', $id);
        if (!$tok) {
            return ['success' => false, 'error' => 'Token no encontrado'];
        }

        $data = [];
        if ($cuota !== null) {
            $data['Tok_Cuota'] = max(0, (int)$cuota);
        }
        if ($periodo !== null) {
            $data['Tok_Periodo'] = $periodo === 'M' ? 'M' : 'D';
        }
        if ($expira !== null) {
            $data['Tok_Expira'] = $expira ? date('Y-m-d H:i:s', strtotime($expira)) : null;
        }
        if ($estado !== null && in_array($estado, ['A', 'I', 'R'])) {
            $data['Tok_Est'] = $estado;
        }

        if (empty($data)) {
            return ['success' => true, 'message' => 'Sin cambios'];
        }

        $ok = $this->api->update('api_tokens', $data, 'Tok_Id', $id);
        return [
            'success' => $ok,
            'error' => $ok ? null : 'No se pudo actualizar: ' . $this->api->getErrorMsg(),
        ];
    }

    public function resetUsage($id)
    {
        $id = (int)$id;
        $ok = $this->api->update('api_tokens', [
            'Tok_Usadas' => 0,
            'Tok_Periodo_Inicio' => date('Y-m-d H:i:s'),
        ], 'Tok_Id', $id);
        return [
            'success' => $ok,
            'error' => $ok ? null : 'No se pudo reiniciar el uso: ' . $this->api->getErrorMsg(),
        ];
    }

    public function delete($id)
    {
        $id = (int)$id;
        $this->api->delete('api_token_permisos', 'Tok_Id', $id);
        $ok = $this->api->delete('api_tokens', 'Tok_Id', $id);
        return [
            'success' => $ok,
            'error' => $ok ? null : 'No se pudo eliminar: ' . $this->api->getErrorMsg(),
        ];
    }

    public function getPermisos($tokId)
    {
        $tokId = (int)$tokId;
        return $this->api->query(
            "SELECT p.Per_Id, p.Tok_Id, p.Tip_Cod, p.Tip_Ruta
               FROM api_token_permisos p
              WHERE p.Tok_Id=$tokId
              ORDER BY p.Tip_Ruta ASC"
        );
    }

    public function setPermisos($tokId, array $rutas)
    {
        $tokId = (int)$tokId;
        $tok = $this->api->findById('api_tokens', 'Tok_Id', $tokId);
        if (!$tok) {
            return ['success' => false, 'error' => 'Token no encontrado'];
        }

        $this->api->delete('api_token_permisos', 'Tok_Id', $tokId);

        $rutas = array_unique(array_filter(array_map('trim', $rutas)));
        foreach ($rutas as $ruta) {
            if ($ruta === '') continue;
            $this->api->insert('api_token_permisos', [
                'Tok_Id' => $tokId,
                'Tip_Ruta' => $ruta,
            ]);
        }

        return [
            'success' => true,
            'permisos' => $this->getPermisos($tokId),
        ];
    }
}
