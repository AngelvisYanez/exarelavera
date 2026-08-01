# Actualizaciones - Julio 2026

**Proyecto:** EXA Contable Relavera  
**Período:** 2026-07-08 → 2026-07-27 (14 commits)  
**Rama actual:** `fix/security-auth-sql-injection`

---

## Resumen

Durante el mes de julio se llevó a cabo una **migración completa del backend a PHP 8.2**, corrección de sintaxis legacy, reestructuración de la capa de datos (`DATA/`), adición de nuevos modelos, mejoras de infraestructura de despliegue (Docker, Plesk, Local), la inclusión del frontend Next.js (`frontend-next/`) y, al final del mes, una **auditoría de seguridad y calidad** con correcciones de autenticación, SQL Injection y pruebas unitarias.

---

## Commits del mes

| Fecha | Commit | Descripción |
|-------|--------|-------------|
| 08-jul | `0ce8bd8c` | `fix: Migracion completa a PHP 8.2 y correcciones legacy` |
| 08-jul | `2106a56d` | `fix: Restaurar codificacion ISO-8859-1 en default_charset de PHP para reparar renderizado de acentos` |
| 09-jul | `a536a96f` | `Migración a PHP 8: corrección de sintaxis y actualización de componentes` |
| 09-jul | `d3218dc9` | `chore: ignorar carpeta db para evitar subir bases de datos` |
| 09-jul | `7484aa80` | `docs: mejorar README con instrucciones de despliegue para Docker, Plesk y Local` |
| 09-jul | `bdd78235` | `chore: eliminar carpeta vercel` |
| 13-jul | `379ede26` | `docs: consolidar documentación de migración PHP en docs/MIGRACION.md` |
| 13-jul | `44973006` | `fix: resolver errores de interpolación de arrays para PHP 8.2` |
| 13-jul | `2968921f` | `Fix syntax error in Abstract.php for PHP 8 compatibility` |
| 13-jul | `048bbc2d` | `Track frontend-next folder` |
| 16-jul | `4cf6f4df` | `feat: migracion PHP 8 - actualizacion dependencias, frontend y backend` |
| 20-jul | `60c4c50d` | `actualizacion general: modulos, api, DATA incluido, excluye control-tributario-ec y frontend-next` |
| 27-jul | `e15f6b8a` | `fix: security & quality fixes from code audit` |
| 27-jul | `abf4d5cd` | `fix: resolve empresa name to Emp_Cod in auth login + XSS fix in Swagger URL` |

> Los 12 primeros commits pertenecen a `main` (ancestros de la rama); los 2 últimos (`e15f6b8a`, `abf4d5cd`) son los aportes propios de la rama.

---

## 1. Migración a PHP 8.2

### 1.1 Corrección de sintaxis legacy (`0ce8bd8c`, `a536a96f`)
- Eliminación de short tags y etiquetas de cierre incompatibles (`fix_short_tags.php`).
- Corrección de sintaxis en múltiples módulos: `administrador`, `facturacion`, `tesoreria`, `contabilidad`, `componentes`, `rrhh`, `relavera`, `activosfijos`, entre otros.
- Actualización de librerías a versión compatible con PHP 8:
  - **Facturación Electrónica** (`Librerias/FactElect/`): `FirmaElectronica.php`, `XmlSecurity/*` (DSig, Key, Pem, RSA). Se eliminó la copia duplicada de `nuSoap`.
  - **MPDF57** (`mpdf.php` + classes de barcode, css, gif, svg, ttf, etc.).
  - **PHPMail / PHPMailer** (`PHPMail.php`, `class.phpmailer.php`, `PHPMailer/` y `PHPMailer_2023/`).
- Nuevo `Dockerfile` y eliminación de procedimientos almacenados obsoletos.

### 1.2 Codificación de caracteres (`2106a56d`)
- Se restauró `default_charset = ISO-8859-1` en PHP para reparar el renderizado de acentos en el sistema legacy.

### 1.3 Capa de datos (`DATA/`) — interpolación de arrays PHP 8.2 (`44973006`, `2968921f`)
- Corrección de errores de interpolación de arrays `${var[...]}` → `{$var[...]}` en `MysqlConexion.php` y `MysqlDatos.php`.
- Nuevo `DATA/libs/AbstractModel.php` (209 líneas).
- Nuevo `DATA/libs/Adapter/Abstract.php` (1287 líneas) con corrección de error de sintaxis para compatibilidad PHP 8.

### 1.4 Dependencias, modelos y backend (`4cf6f4df`)
- **Nuevos MODELS**: `adq_solicitudes.php`, `adq_solicitudes_compras.php`, `adq_solicitudes_cotizaciones.php`, `adq_solicitudes_det.php`, `adq_tipos_requerimientos.php`, `manifiesto_contratos.php`, `manifiesto_contratos_docu.php`, `send_whatsapp.php`, `wf_conexiones.php`, `wf_flujos_modelos.php`, `wf_instancias.php`, `wf_instancias_nodos.php`, `wf_nodos.php`, `wf_reglas.php`.
- **Librería `nuSoap`** agregada en `WS/libs/nuSoap/` (webservices SOAP).
- **Scripts de migración y utilidades** en `QUARANTINE/`: `migrar_utf8.php`, `convert_encoding.php`, `fix_unquoted_keys_v2/v3.php`, `fix_free_result.php`, `lint.php`, `check_lineendings.php`, `test_utf8.php`, etc.
- Actualización del `Dockerfile` y de `administrador/FRONT/adm_param_inicial.php`.

---

## 2. Actualización general de módulos y API (`60c4c50d`)

Commit masivo que actualiza módulos, API y la capa de datos. **Excluye** `control-tributario-ec` y `frontend-next`.

### 2.1 Nueva capa de datos (`DATA/`)
- **`DATA/DAC.php`** (623 líneas) — Data Access Controller.
- **`DATA/GestorErrores.php`** (41 líneas) — gestión centralizada de errores.
- **`DATA/MysqlConexionServer.php`** (137 líneas) — conexión a servidor.
- **`DATA/libs/Db.php`**, **`DATA/libs/Env.php`**, **`DATA/libs/Expr.php`**, **`DATA/libs/Select.php`** (1399 líneas) — nuevo builder de consultas.
- **`DATA/index.php`** — punto de entrada de la capa de datos.

### 2.2 API y webservices
- Actualización de endpoints en `api/` (16 archivos) y de `WS/libs/RideGlobal.php`.
- Nuevos archivos: `config_db.php`, `java.php`, `pdf.php`, `ruc.php`, `desc_compras.php`, `desc_ventas.php`.

### 2.3 Módulos actualizados
- **facturacion** (78 archivos), **tesoreria** (68), **administrador** (32), **activosfijos** (30), **contabilidad** (27), **inventario** (19), **rrhh** (16), **api** (16), **bananero** (10), **auditoria** (9), **relavera** (9), **camaronera** (9), **adquisiciones** (8), **compras** (7), **caja_chica** (6), **transportecarga** (3), **bodega** (2), **flujo** (2), entre otros.
- **Vendor** actualizado (69 archivos) y ajustes en `.gitignore`.

---

## 3. Frontend Next.js

- **`048bbc2d`**: se incluye (`track`) la carpeta `frontend-next/` con sus skills de agentes (accessibility, composition-patterns, frontend-design, next-best-practices, next-cache-components, nodejs, react-best-practices).
- **`bdd78235`**: se elimina la carpeta `vercel/` (copia anterior del frontend) para evitar duplicación.

---

## 4. Infraestructura y despliegue

- **`d3218dc9`**: `.gitignore` ignora la carpeta `db/` para evitar subir bases de datos al repositorio.
- **`7484aa80`**: README ampliado con instrucciones de despliegue para **Docker**, **Plesk** y **Local**.

---

## 5. Documentación de migración (`379ede26`)

- Se consolidó toda la documentación de la migración a PHP en **`docs/MIGRACION.md`** (600 líneas), eliminando archivos dispersos:
  - `MIGRACION_PHP8_RESUMEN.md`, `RESUMEN_MIGRACION_PHP8.md`, `docs/MIGRACION_PHP_8.md`, `docs/MIGRACION_PHP_EXA.md`, `docs/PLAN_MIGRACION_PHP82.md`, `docs/DESPLIEGUE_Y_MEJORAS.md`, `docs/Migración de Compatibilidad a PHP 7.1.33.md`.

---

## 6. Seguridad y auditoría de código (aportes de la rama)

### 6.1 Autenticación (`api/v1/auth/auth.php`)
- **Tokens firmados con HMAC-SHA256** reemplazando el `base64_encode(user:empresa:timestamp)` original (falsificable). Secreto vía variable de entorno `AUTH_TOKEN_SECRET`.
- **Prevención de SQL Injection** en las consultas de login mediante `real_escape_string()` y casteo a `(int)` de `Emp_Cod`.
- **Login por nombre de empresa**: se resuelve `Emp_Nom` → `Emp_Cod` en lugar de confiar en un código enviado por el cliente.
- **Ocultamiento de errores internos**: los mensajes de excepción se registran en `error_log` y se responde `"Error interno del servidor"`.

### 6.2 API (`api/index.php`)
- **Debug mode de Slim deshabilitado** (`debug => false`).
- **CORS restringido** a allowlist: `localhost:3000`, `localhost:3001`, `https://exa-contable.vercel.app`.
- **Filtrado de errores fatales** (ya no se exponen mensaje/archivo/línea).
- **Fix de XSS en Swagger UI** usando `json_encode()` para la URL del `openapi.json`.

### 6.3 Otras correcciones
- **`phpinfo.php`**: acceso restringido solo a localhost (403 para otras IPs).
- **`api/v1/admin/soporte.php`**: contraseña por defecto aleatoria (`bin2hex(random_bytes(16))`) en lugar de `123456`.
- **`classes/SriScraperManager.php`**: fix de path traversal (sanitización de clave + validación de ruta con `realpath()`).
- **`classes/Cliente.php`**: typo `$responce` → `$response`.
- **`classes/Proveedor.php`**: variable `$data` no usada eliminada.
- **`classes/Categoria.php`, `Marca.php`, `Producto.php`**: código comentado eliminado.

### 6.4 Pruebas unitarias (PHPUnit 10)
- `composer.json`: `phpunit/phpunit: ^10.0` en `require-dev`.
- Nuevo `phpunit.xml` con suite de tests en `tests/`.
- **62 tests, 124 assertions**: `ApiResponseTest` (15), `FacturacionElectronicaTest` (14), `DataAPITest` (22), `TareaClassTest` (16).

### 6.5 Reporte
- **`docs/AUDITORIA_CODIGO.md`**: reporte con hallazgos de seguridad (5 críticos, 8 altos, 6 medios, 2 bajos), hallazgos de calidad y recomendaciones priorizadas.

---

## Archivos creados/principales del mes

| Archivo | Propósito |
|---------|-----------|
| `docs/MIGRACION.md` | Documentación consolidada de la migración a PHP 8 |
| `docs/AUDITORIA_CODIGO.md` | Reporte de auditoría de seguridad y calidad |
| `DATA/DAC.php`, `DATA/GestorErrores.php`, `DATA/MysqlConexionServer.php` | Nueva capa de acceso a datos |
| `DATA/libs/Db.php`, `Env.php`, `Expr.php`, `Select.php` | Builder de consultas y utilidades |
| `DATA/libs/AbstractModel.php`, `DATA/libs/Adapter/Abstract.php` | Modelos base compatibles PHP 8 |
| `MODELS/*` (14 archivos) | Modelos de adquisiciones, manifiesto, workflow y WhatsApp |
| `WS/libs/nuSoap/` | Librería SOAP para webservices |
| `Dockerfile` | Contenedor de despliegue |
| `phpunit.xml` | Configuración de PHPUnit |
| `tests/classes/*.php` (4) | Pruebas unitarias |
