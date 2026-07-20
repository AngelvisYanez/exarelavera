<?php

class ApiResponse
{
    public static function success($data = null, $message = 'OK', $code = 200)
    {
        http_response_code($code);
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        echo json_encode($response);
    }

    public static function error($error, $code = 400)
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $error]);
    }

    public static function paginated($data, $total, $page, $perPage, $pages)
    {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'total' => (int)$total,
            'page' => (int)$page,
            'perPage' => (int)$perPage,
            'pages' => (int)$pages
        ]);
    }

    public static function created($data = null, $message = 'Registro creado exitosamente')
    {
        self::success($data, $message, 201);
    }

    public static function noContent($message = 'Operación exitosa')
    {
        http_response_code(204);
    }

    public static function notFound($error = 'Registro no encontrado')
    {
        self::error($error, 404);
    }

    public static function badRequest($error = 'Parámetros inválidos')
    {
        self::error($error, 400);
    }

    public static function serverError($error = 'Error interno del servidor')
    {
        self::error($error, 500);
    }

    public static function validateRequired($fields, $body)
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($body[$field]) || $body[$field] === '' || $body[$field] === null) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            self::badRequest('Campos requeridos: ' . implode(', ', $missing));
            return false;
        }
        return true;
    }
}
