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

    public function __construct()
    {
        // La tabla vive en la base MASTER central (exa_master)
        // Nota: 'master' como nombre de BD mapea a la BD corporativa vacía en
        // MysqlConexion, por eso se usa el nombre explícito 'exa_master'.
        $this->api = new DataAPI('exa_master');
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
            'Bdd' => $bdd ?: 'exa',
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
        $con = new MysqlConexion($bdd);
        return !empty($con->conexion);
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

        // Asignar permisos por módulo/proceso (opcional). Si no se envían,
        // el token queda sin restricción (permite todo).
        if (is_array($permisos) && !empty($permisos)) {
            $resPerm = $this->setPermisos((int)$id, $permisos);
            if (!$resPerm['success']) {
                // No fallar la creación por un error de permisos, pero notificar
                $this->api->delete('api_tokens', 'Tok_Id', (int)$id);
                return $resPerm;
            }
        }

        $resPermisos = is_array($permisos) ? $this->getPermisos((int)$id) : [];

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

        // Actualizar periodo de cuota si aplica
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
            'nombre' => $row['Tok_Nombre'],
            'Emp_Cod' => (int)$row['Emp_Cod'],
            'Bdd' => $row['Tok_Bdd'],
            'cuota' => $cuota,
            'usadas' => (int)$row['Tok_Usadas'] + ($usarCuota ? 1 : 0),
            'periodo' => $periodo,
            'creado' => $row['Tok_Creado'],
        ];
    }

    /**
     * Lista los tokens, opcionalmente filtrados por empresa.
     */
    public function list($empCod = null)
    {
        $where = [];
        if (!empty($empCod)) {
            $where['Emp_Cod'] = (int)$empCod;
        }
        $rows = $this->api->list('api_tokens', $where, 'Tok_Id DESC', 500);
        foreach ($rows as &$r) {
            unset($r['Tok_Hash']); // Nunca exponer el hash
            $r['Tok_Creado'] = $r['Tok_Creado'] ?? null;
            $r['permisos'] = $this->getPermisos($r['Tok_Id']);
        }
        return $rows;
    }

    /**
     * Actualiza la cuota / periodo / expiración de un token.
     */
    public function updateLimits($tokId, $cuota = null, $periodo = null, $expira = null, $estado = null)
    {
        $tokId = (int)$tokId;
        $row = $this->api->getById('api_tokens', 'Tok_Id', $tokId);
        if (!$row) {
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
            $data['Tok_Expira'] = $expira === '' || $expira === '0'
                ? null
                : date('Y-m-d H:i:s', strtotime($expira));
        }
        if ($estado !== null) {
            $estado = strtoupper(substr($estado, 0, 1));
            $data['Tok_Est'] = ($estado === 'I' || $estado === 'A') ? $estado : 'A';
        }
        if (empty($data)) {
            return ['success' => false, 'error' => 'No hay cambios para aplicar'];
        }
        $ok = $this->api->update('api_tokens', $data, 'Tok_Id', $tokId);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'error' => 'No se pudo actualizar el token: ' . $this->api->getErrorMsg()];
    }

    /**
     * Revoca (desactiva) un token.
     */
    public function revoke($tokId)
    {
        return $this->updateLimits($tokId, null, null, null, 'I');
    }

    /**
     * Elimina definitivamente un token.
     */
    public function delete($tokId)
    {
        $ok = $this->api->delete('api_tokens', 'Tok_Id', (int)$tokId);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'error' => 'No se pudo eliminar el token: ' . $this->api->getErrorMsg()];
    }

    /**
     * Resetea el contador de consultas de un token.
     */
    public function resetUsage($tokId)
    {
        $ok = $this->api->update('api_tokens', [
            'Tok_Usadas' => 0,
            'Tok_Periodo_Inicio' => date('Y-m-d H:i:s'),
        ], 'Tok_Id', (int)$tokId);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'error' => 'No se pudo resetear el contador'];
    }

    /**
     * Construye el catálogo de módulos y procesos de la API a partir de openapi.json.
     *
     * Cada módulo (tag OpenAPI) contiene una lista de procesos (rutas) con su
     * método, descripción (summary) y la ruta real de consumo.
     *
     * @return array ['success'=>bool, 'modulos'=>[ [name, description, rutas=>[ [ruta, metodo, descripcion] ]] ]]
     */
    public function catalogo()
    {
        $openapiFile = dirname(__DIR__) . '/api/openapi.json';
        if (!is_file($openapiFile)) {
            return ['success' => false, 'error' => 'Spec OpenAPI no encontrada'];
        }
        $spec = json_decode(file_get_contents($openapiFile), true);
        if (!isset($spec['tags']) || !isset($spec['paths'])) {
            return ['success' => false, 'error' => 'Spec OpenAPI inválida'];
        }

        $modulos = [];
        foreach ($spec['tags'] as $tag) {
            $modulos[$tag['name']] = [
                'name' => $tag['name'],
                'description' => $tag['description'] ?? '',
                'rutas' => [],
            ];
        }

        foreach ($spec['paths'] as $ruta => $ops) {
            if ($ruta === '/v1/test') {
                continue;
            }
            foreach (['get' => 'GET', 'post' => 'POST', 'put' => 'PUT', 'delete' => 'DELETE'] as $met => $metodo) {
                if (!isset($ops[$met])) {
                    continue;
                }
                $op = $ops[$met];
                $tag = isset($op['tags'][0]) ? $op['tags'][0] : 'general';
                if (!isset($modulos[$tag])) {
                    $modulos[$tag] = ['name' => $tag, 'description' => '', 'rutas' => []];
                }
                $modulos[$tag]['rutas'][] = [
                    'ruta' => $ruta,
                    'metodo' => $metodo,
                    'descripcion' => $op['summary'] ?? '',
                ];
            }
        }

        // Orden alfabético por nombre, excluyendo módulos internos de admin/auth si no proceden
        ksort($modulos);
        $out = array_values($modulos);
        foreach ($out as &$m) {
            usort($m['rutas'], function ($a, $b) {
                return strcmp($a['ruta'], $b['ruta']);
            });
        }

        return ['success' => true, 'modulos' => $out];
    }

    /**
     * Devuelve las rutas permitidas (permisos) de un token gestionado.
     * Una entrada con Tip_Ruta='*' significa "todo el módulo".
     *
     * @return array  lista de ['Tip_Id', 'Tip_Mod', 'Tip_Ruta']
     */
    public function getPermisos($tokId)
    {
        $tokId = (int)$tokId;
        $rows = $this->api->query(
            "SELECT Tip_Id, Tip_Mod, Tip_Ruta, Tip_Est
               FROM api_token_permisos
              WHERE Tok_Id = $tokId AND Tip_Est = 'A'
              ORDER BY Tip_Mod, Tip_Ruta"
        );
        return is_array($rows) ? $rows : [];
    }

    /**
     * Reemplaza por completo los permisos de un token.
     *
     * @param int   $tokId
     * @param array $rutas  lista de rutas permitidas. Cada elemento puede ser:
     *                      - la ruta completa '/v1/modulo/recurso'
     *                      - '*'
     *                      El prefijo de módulo se deriva automáticamente de la ruta.
     * @return array ['success'=>bool, ...]
     */
    public function setPermisos($tokId, array $rutas)
    {
        $tokId = (int)$tokId;
        $row = $this->api->getById('api_tokens', 'Tok_Id', $tokId);
        if (!$row) {
            return ['success' => false, 'error' => 'Token no encontrado'];
        }

        // Limpiar permisos previos
        $ok = $this->api->delete('api_token_permisos', 'Tok_Id', $tokId);
        if (!$ok) {
            return ['success' => false, 'error' => 'No se pudieron actualizar los permisos: ' . $this->api->getErrorMsg()];
        }

        if (empty($rutas)) {
            return ['success' => true, 'message' => 'Permisos actualizados (acceso a todos los módulos, sin restricción)'];
        }

        $filas = [];
        foreach ($rutas as $ruta) {
            $ruta = trim((string)$ruta);
            if ($ruta === '' || $ruta === '*') {
                // Acceso a todo (representado explícitamente por la ausencia de filas);
                // se omite aquí porque 'sin filas' = permitir todo por compatibilidad.
                continue;
            }
            $norm = $ruta[0] === '/' ? $ruta : '/' . $ruta;
            $partes = explode('/', trim($norm, '/'));
            $modulo = isset($partes[1]) ? $partes[1] : 'general';
            $filas[] = [
                'Tok_Id' => $tokId,
                'Tip_Mod' => $modulo,
                'Tip_Ruta' => $norm,
                'Tip_Est' => 'A',
            ];
        }

        if (!empty($filas)) {
            $ok = $this->api->insertBatch('api_token_permisos', $filas);
            if (!$ok) {
                return ['success' => false, 'error' => 'No se pudieron guardar los permisos: ' . $this->api->getErrorMsg()];
            }
        }

        return ['success' => true, 'message' => 'Permisos actualizados correctamente'];
    }

    /**
     * Compara una ruta concreta de la petición contra un patrón de permiso.
     * Los segmentos ':param' del patrón coinciden con cualquier valor.
     */
    protected function rutaCoincide($rutaSolicitada, $patron)
    {
        $a = explode('/', trim($rutaSolicitada, '/'));
        $b = explode('/', trim($patron, '/'));
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($b as $i => $seg) {
            if ($seg === '' || $seg === '*' || strpos($seg, ':') === 0) {
                continue; // coincide con cualquier segmento
            }
            if (!isset($a[$i]) || $a[$i] !== $seg) {
                return false;
            }
        }
        return true;
    }

    /**
     * Indica si un token gestionado puede consumir la ruta dada.
     *
     * Reglas:
     *  - Si el token NO tiene permisos registrados => permite todo (comportamiento
     *    actual / retrocompatibilidad).
     *  - Si tiene permisos => se deniega por defecto; se permite solo si la ruta
     *    coincide con un permiso (con soporte de segmentos ':param' y módulo '*').
     *
     * @param int    $tokId
     * @param string $ruta  ruta real de la petición (p.ej. '/v1/contabilidad/periodos')
     * @return bool
     */
    public function hasPermission($tokId, $ruta)
    {
        $tokId = (int)$tokId;
        $row = $this->api->queryRow("SELECT COUNT(*) AS c FROM api_token_permisos WHERE Tok_Id=$tokId AND Tip_Est='A'");
        if ((int)$row['c'] === 0) {
            return true; // sin permisos = todo permitido (retrocompatibilidad)
        }

        $ruta = trim((string)$ruta);
        if ($ruta === '') {
            return false;
        }
        $normalizada = $ruta[0] === '/' ? $ruta : '/' . $ruta;
        $partes = explode('/', trim($normalizada, '/'));
        $modulo = isset($partes[1]) ? $partes[1] : 'general';

        $perms = $this->getPermisos($tokId);
        foreach ($perms as $p) {
            if ($p['Tip_Ruta'] === '*') {
                return true;
            }
            if ($p['Tip_Mod'] === $modulo && $this->rutaCoincide($normalizada, $p['Tip_Ruta'])) {
                return true;
            }
        }
        return false;
    }
}
