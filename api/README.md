# API REST — EXA Contable

API REST del sistema ERP contable **EXA**. Implementada sobre **Slim 2** (PHP) en la carpeta `api/`, expone los módulos del ERP (contabilidad, facturación, tesorería, RRHH, inventario, activos fijos, bodega, caja chica, transporte, bananero, camaronera, relavera, auditoría, adquisiciones, compras y flujo de workflows).

- Contrato OpenAPI: [`api/openapi.json`](openapi.json)
- Documentación interactiva (Swagger UI): **`/v1/docs`**
- Especificación servida por la API: **`/v1/docs/openapi.json`**

---

## Cómo usar la documentación interactiva

Con el servidor levantado, abre en el navegador:

```
http://localhost:8000/v1/docs
```

Allí puedes navegar todos los endpoints, ver los parámetros de cada request y probarlos.

---

## Autenticación

La mayoría de los endpoints requieren un **token Bearer**. El flujo es de dos pasos:

### 1. Listar empresas del usuario

```
POST /v1/auth/empresas
Content-Type: application/json

{ "username": "tu_usuario" }
```

Devuelve las empresas (`Emp_Cod`) a las que tiene acceso el usuario.

### 2. Iniciar sesión

```
POST /v1/auth/login
Content-Type: application/json

{
  "username": "tu_usuario",
  "password": "tu_password",
  "empresa": "Nombre de la Empresa"
}
```

Respuesta de éxito:

```json
{
  "success": true,
  "message": "Login exitoso",
  "token": "bm9tYnJlOjQ6MTczMDAwMDAwMDphYmNkZWY=",
  "Bdd": "exa_empresa_4",
  "usuario": "Nombre Apellido",
  "empresa_id": "Nombre de la Empresa"
}
```

### 3. Usar el token

Cada petición autenticada debe incluir el header:

```
Authorization: Bearer {token}
```

- El token es `base64(usuario:empresa:timestamp:signature)` (firma **HMAC-SHA256**).
- **Expira a las 24 horas** de emitido; pasado ese tiempo devuelve `401 Session expirada`.

### Rutas exentas de autenticación

No requieren token:

- `/v1/test`
- `/v1/auth/*`
- `/v1/facturacion/*` (comprobantes electrónicos, SRI scraper, etc.)
- `/v1/docs*`

---

## Convenciones

- **Cuerpo JSON:** La mayoría de los endpoints reciben los datos en el body con `Content-Type: application/json`.
- **`Emp_Cod` automático:** El middleware de autenticación inyecta `Emp_Cod` (empresa del token) en el body si no se envía. No es obligatorio enviarlo en rutas autenticadas.
- **`Bdd`:** Muchos endpoints requieren el nombre de la base de datos (`Bdd`), que suele devolver el login.
- **Paginación:** Endpoints con listas grandes aceptan `page` y `perPage` (opcionales).
- **Codificación:** Todas las respuestas son UTF-8 JSON.

### Formato de respuesta

- Éxito: `{ "success": true, "data": ..., "message": "..." }`
- Error: `{ "success": false, "error": "descripción" }`
- Listas paginadas: `{ "success": true, "data": [...], "total": N, "page": 1, "perPage": 25, "pages": N }`

### Códigos HTTP

| Código | Significado |
|--------|-------------|
| `200`  | Éxito |
| `400`  | Petición inválida / faltan parámetros |
| `401`  | No autenticado, token inválido o expirado |
| `500`  | Error interno del servidor |

---

## Seguridad

- **`POST /v1/data/query`** permite ejecutar **SQL personalizado** con los privilegios de la conexión configurada. No debe exponerse a usuarios no confiables.
- Las rutas `/v1/facturacion/*` están **exentas de autenticación** (bypass en `api/index.php`). Tenlo en cuenta al evaluar el riesgo de esos endpoints.

---

## Estructura de código

- `api/index.php` — Bootstrap de Slim, CORS, middleware de autenticación, Swagger UI y carga de módulos.
- `api/router.php` — Router para el servidor integrado de PHP.
- `api/openapi.json` — Contrato OpenAPI 3.0 de la API.
- `api/v1/*` — Endpoints REST por módulo.
