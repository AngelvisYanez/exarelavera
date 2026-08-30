# Migración PHP — Sistema EXA (ERP)

**Proyecto:** Exa Ofsercont — Sistema de Contabilidad y Gestión Empresarial
**Rama:** `migracionphp`
**PHP destino:** 8.2.32 (ruta crítica: 5.3.8 → 7.1.14 → 8.2)
**Archivos PHP totales:** ~2,815
**Archivos modificados:** ~464 (+9,463 / -2,530 líneas, exclude vendor/)
**Última actualización:** Agosto 2026

---

## Índice

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual y Progreso](#2-estado-actual-y-progreso)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Correcciones de Compatibilidad Realizadas](#4-correcciones-de-compatibilidad-realizadas)
5. [Nueva API REST y Clases de Servicio](#5-nueva-api-rest-y-clases-de-servicio)
6. [Despliegue Local y Docker](#6-despliegue-local-y-docker)
7. [Frontend Next.js (Vercel)](#7-frontend-nextjs-vercel)
8. [Envío de Correo Electrónico](#8-envío-de-correo-electrónico)
9. [Optimizaciones Realizadas](#9-optimizaciones-realizadas)
10. [Módulos y Relaciones](#10-módulos-y-relaciones)
11. [Pendientes](#11-pendientes)
12. [Estadísticas](#12-estadísticas)

---

## 1. Resumen Ejecutivo

EXA es un ERP monolítico en PHP procedural + clases legacy, organizado por módulos (`FRONT` / `LOGICA` / `VALIDACIONES`). La base de datos ya usa **mysqli** (no la extensión `mysql_*` eliminada en PHP 7). Se migró desde **PHP 5.3.8** hasta lograr compatibilidad completa con **PHP 8.2**, aplicando correcciones progresivas para cada versión intermedia.

| Aspecto | Antes | Después |
|---------|-------|---------|
| Versión PHP | 5.3.8 | 8.2.32 (soporte desde 7.1.14+) |
| Conexión BD | mysqli | mysqli (sin cambios) |
| Dependencias | Sin Composer | Composer 2.2.x LTS |
| mPDF | 5.7 (legacy con `/e`) | Parcheado in situ (pendiente migrar a 7.0+) |
| TCPDF | Manual | vía Composer |
| PHPMailer | Legacy (Librerias/PHPMail) | vía Composer |
| Frontend | Solo PHP | PHP + Next.js (Vercel) |
| API | Inexistente | REST API con Slim Framework |
| Contenedores | No | Docker + docker-compose |

### Línea de tiempo de la migración

| Fase | Versión | Estado | Descripción |
|------|---------|--------|-------------|
| 0 | 5.3.8 | ✅ Original | Línea base del proyecto |
| 1 | 7.1.33 | ✅ Completa | Correcciones fatales: `session_register`, `&new`, superglobales como params, `/e`, `eregi`, `split`, short open tags |
| 2 | 7.2+ | ✅ Completa | `each()` deprecado, trailing commas |
| 3 | 8.0 | ✅ Completa | `each()` eliminado, `create_function()` → anónima, `${var}` → `{$var}`, constructores PHP 4 → `__construct` |
| 4 | 8.1 | ✅ Completa | `mysqli_report` silenciado, `FILTER_SANITIZE_STRING` → `FULL_SPECIAL_CHARS` |
| 5 | 8.2 | ✅ Completa | `utf8_encode/decode` → `mb_convert_encoding`, `var $prop` → `public $`, propiedades dinámicas → `#[AllowDynamicProperties]`, `default_charset` → `iso-8859-1` |

---

## 2. Estado Actual y Progreso

| Fase | Estado | Descripción |
|------|--------|-------------|
| 1a `$var{index}` → `$var[index]` | ✅ Completo | MPDF57 (×2), TCPDF, Smarty |
| 1b `each()` → `foreach` (app) | ✅ Completo | 95+ archivos de aplicación |
| 1c `each()` → `foreach` (librerías) | ✅ Completo | MPDF57, TCPDF, Smarty, NuSOAP |
| 2 Librerías legacy (lint) | ✅ Completo | Todos los archivos clave pasan `php -l` en PHP 8.2 |
| 3a `utf8_encode/decode` | ✅ Completo | 0 llamadas reales restantes en app (excl. `QUARANTINE/`); solo quedan menciones en comentarios |
| 3b `${var}` en strings | ✅ N/A | No existe en código (solo JS template literals) |
| 3c `var $prop` → visibilidad explícita | ✅ Completo | 411 ocurrencias convertidas en 13 archivos (NuSOAP ×12 + `framework/php/ventanasSocket/phpwebsocket.php`); solo resta `control-tributario-ec/` (excluido) |
| 3d Propiedades dinámicas | ✅ Completo | `#[AllowDynamicProperties]` en bases: `AbstractModel`, `Zend_Db_Adapter_Abstract`, `base_mysql` (además de los ya presentes en `MysqlConexion/Datos`, `DAC`, `TreeMenu`) |
| 4 Pruebas módulo por módulo | ⏳ Pendiente | 21 módulos por probar (suite PHPUnit parcial: 598 tests, 885 assertions) |

---

## 3. Arquitectura del Sistema

### 3.1 Estructura estándar por módulo

```
[modulo]/
├── FRONT/          — PHP + HTML + includes (interfaz y endpoints AJAX)
├── LOGICA/         — *_log_*.php (clases datos) + *_sql_*.php (consultas)
├── VALIDACIONES/   — JavaScript (jqGrid, formularios)
```

### 3.2 Núcleo transversal

| Ruta | Rol |
|------|-----|
| `index.php` | Login, selección de empresa, redirección |
| `administrador/LOGICA/seguridad.php` | Sesión, permisos, menú; incluye `register_globals.php` |
| `administrador/FRONT/home.php` | Shell principal post-login |
| `DATA/MysqlConexion.php` | Conexión mysqli master / corporativa |
| `DATA/GestorErrores.php` | Manejo de errores |
| `Librerias/config.php/register_globals.php` | Emula `register_globals` (GET/POST/SESSION → variables PHP) |
| `Librerias/procedimientos/almacenados_standar.php` | Procedimientos comunes |
| `MODELS/` | 107 modelos PHP reutilizables (ventas, compras, manifiesto, etc.) |
| `mascaras/model1`, `mascaras/model3` | UI, jqGrid, CSS moderno |

### 3.3 Flujo típico de una pantalla

```
FRONT/xxx.php
  → require seguridad.php
      → register_globals.php (crea $Parametro desde $_GET/$_POST/$_SESSION)
      → logica.php / permisos
  → require ../LOGICA/xxx_log.php
  → HTML + <script src="../VALIDACIONES/xxx.js">
  → if (isset($ajaxAlgo)) { consulta; echo json; exit; }
```

### 3.4 Bases de datos

- **Master:** `exa_master` (empresas, acceso, planes, usuarios globales)
- **Por empresa:** `Dat_Dis` resuelto en login; cada empresa tiene su BD

### 3.5 Inventario por módulo

| Módulo | Archivos PHP | Notas |
|--------|-------------:|-------|
| tesoreria | 590 | Mayor volumen; muchas plantillas cheques/{id}/ |
| facturacion | 569 | Ventas, compras, electrónica, cruces |
| contabilidad | 148 | Balances, plan de cuentas, asientos |
| administrador | 137 | Login, permisos, planes, dashboard proyecto |
| MODELS | 107 | Impacto transversal |
| activosfijos | 91 | |
| componentes | 87 | |
| relavera | 71 | Manifiestos, anticipos, dashboards |
| rrhh | 65 | Roles, nómina |
| bananero | 50 | Labores, liquidación |
| WS | 41 | Servicios web |
| inventario | 35 | |
| mascaras | 22 | UI compartida |
| auditoria | 22 | Despacho, tareas |
| adquisiciones | 22 | |
| transportecarga | 21 | |
| compras | 20 | |
| camaronera | 18 | |
| DATA | 17 | Conexión, helpers |
| caja_chica | 14 | |
| bananeroOld | 12 | Legado — baja prioridad |
| bodega | 12 | |
| exa | 7 | |
| printers | 7 | |
| api | 6 | |
| skins | 5 | Dashboard empresarial |
| classes | 5 | |
| ccxpp | 2 | |

---

## 4. Correcciones de Compatibilidad Realizadas

### 4.1 PHP 5.4 → 7.x

#### session_register() (eliminado en PHP 5.4)
Reemplazo de `session_register()` por asignación directa a `$_SESSION`:
- `administrador/FRONT/adm_con_control_1.0.php`
- `administrador/FRONT/adm_con_control_1.1.php`
- `auditoria/LOGICA/adm_con_control_1.1.php`

#### Asignación por Referencia `&new` (eliminado en PHP 7)
Corrección de `$var = &new Clase()` a `$var = new Class()`:
- `administrador/FRONT/adm_con_treemenu.php`, `_p.php`, `_adm_1.0_p.php`
- `Librerias/config.php/adm_con_treemenu.php`
- `mascaras/model1/img/adm_con_treemenu.php`

#### Superglobales como Parámetros (eliminado en PHP 7)
Renombrado de parámetros `$_POST` en firmas de funciones:
- `administrador/LOGICA/adm_log_soporte.php`
- `compras/LOGICA/requisiciones/index.php`

#### Modificador `/e` en preg_replace (eliminado en PHP 7)
Reemplazo por `preg_replace_callback()`:
- `Librerias/MPDF57/mpdf.php`, `compress.php`
- `Librerias/slider/MPDF57/mpdf.php`, `compress.php`

#### eregi_replace() (eliminado en PHP 7)
Cambio a `preg_replace()` con flag `/i`:
- `Librerias/FactElect/XmlSecurity/Key/P12.php`

#### split() (eliminado en PHP 7)
Reemplazo por `preg_split()`:
- 12 archivos en `contabilidad/FRONT/` y `LOGICA/`
- `contabilidad/FRONT/con_alt_compr_1.0.php`, `1.1.php`, `con_alt_comprdup.php`, etc.

#### Short Open Tags (`<?`)
Se habilitó `short_open_tag = On` en `php.ini`. Se corrigieron etiquetas sueltas en:
- `index.php`, `facturacion/FRONT/con_pri_compr.php`, `tes_alt_bancos.php`
- Facturas personalizadas por cliente (Orquídea, Pablo Aguirre, etc.)

### 4.2 PHP 7.2 → 8.x

#### each() (deprecado en 7.2, eliminado en 8.0)
Reemplazo de `each()` por `foreach()` en más de 95 archivos de aplicación, incluyendo:

**Contabilidad:**
- `con_con_may_ban_1.0-30-03-26.php`, `con_con_mayorizacion_1.0.php`, `1.1.php`, `1.2.php`
- `con_con_reporte_cuenta.php`, `con_pri_mayorizacion_1.0.php`, `1.1.php`
- `con_pri_reporte_cuenta.php`

**Inventario:**
- `inv_con_kardex_resumen.php`, `inv_con_kardex_resumido.php`, `inv_con_toma_fisica.php`

**RRHH:**
- `rhu_pri_anticipo.php`, `rhu_pri_personal_1.0.php`

**Facturación:**
- `fac_con_kardex_1.0.php`, `fac_con_kardex_xx_1.0.php`, `fac_con_kar_res_1.0.php`
- `fac_pri_kardex_1.0.php`, `fac_pri_fac_ven_1.0.php`, `fac_pri_fac_eletronica_1.0.php`
- `fac_pri_fac_martha_1.0.php`, `fac_pri_fac_olger_1.0.php`
- `fac_pri_liq_cpatino_1.0.php`, `fac_pri_comprb_1.0.php`
- `tes_pri_factura_1.0.php`, `tes_pri_kardex_1.0.php`
- `fac_log_categoria.php`

**Bananero:**
- `ban_alt_materiales_1.0.php` (bananero + bananeroOld)

**Activos Fijos:**
- `act_pri_activo_1.0.php`, `act_pri_custodio_2.0.php`

**Auditoría:**
- `aud_mod_dashboard_tareas_1.0.php`

**Tesorería (cheques):**
- `tes_alt_cheque_1.0.php`, `tes_baj_che_lib.php`, `tes_baj_cheque_1.0.php`
- `tes_con_cheque_1.0.php`, `tes_mod_cheque_1.0.php`
- `tes_pri_cheque_*.php` (múltiples variantes)
- `tes_pri_recibocobro_1.0.php`, `1.1.php`, `1.1_empresa.php`
- Todas las plantillas de cheques por empresa (1, 9, 10, 14, 19, 27, 31, 35, 36, 38, 50, 56, 96, 120, 123, 154, 155, 159—165, 171, 236—240, 246, 247, 253—255, 258, 273, 277, 279, 284, 303, 314, 559, 569)
- Facturas personalizadas por cliente (~200+ archivos en `facturacion/`)

#### create_function() (eliminado en PHP 8.0)
Reemplazo por función anónima:
- `administrador/LOGICA/TreeMenu.php`

#### Trailing Commas en firmas/llamadas
Corrección de comas de arrastre (PHP 8.0 en firmas, 7.3 en llamadas):
- `framework/Slim/Http/Util.php`
- `classes/Manifiesto.php`
- `api/index.php`

### 4.3 PHP 8.0 → 8.2

#### Constructores PHP 4 → `__construct`
PHP 8.0 eliminó los constructores con nombre de clase. Reemplazo en:
- `skins/php/TreeMenu.php`: `function TreeMenu()` → `public function __construct()`, `function TreeMenuItem()` → `public function __construct()`

#### Propiedades Dinámicas — `#[AllowDynamicProperties]`
PHP 8.2 depreca propiedades dinámicas no declaradas. Se añadió el atributo en:
- `skins/php/TreeMenu.php`: Clases `TreeMenu` y `TreeMenuItem`

#### MySQLi — Silenciar Excepciones
PHP 8.1 cambió el reporte de MySQLi a excepciones por defecto. Se añadió:
- `DATA/MysqlConexion.php`, `DATA/MysqlDatos.php`: `mysqli_report(MYSQLI_REPORT_OFF);`

#### `FILTER_SANITIZE_STRING` (deprecado PHP 8.1)
- `administrador/FRONT/home.php`: `FILTER_SANITIZE_STRING` → `FILTER_SANITIZE_FULL_SPECIAL_CHARS`

#### `default_charset` (acentos rotos)
PHP 5.6+ cambió `default_charset` a UTF-8. Para respetar ISO-8859-1 del frontend:
- `DATA/MysqlConexion.php`: `ini_set('default_charset', 'iso-8859-1');`

#### `utf8_encode/decode` (deprecado PHP 8.2)
Reemplazo masivo por `mb_convert_encoding()` en 14+ archivos, incluyendo:
- `Librerias/procedimientos/almacenados_standar.php`
- `administrador/LOGICA/adm_log_menu_tree.php`
- Wrappers en `vendor/symfony/polyfill-php72` (reparado tras reemplazo masivo)
- Últimos 2 usos de `utf8_decode` en NuSOAP (agos 2026): `WS/libs/nuSoap/nusoap.php` y `WS/libs/nuSoap/class.soap_parser.php` → `mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8')`

Quedan menciones de `utf8_encode/decode` solo en comentarios (`refactor_utf8.php`, NuSOAP) y en `QUARANTINE/` (excluido).

#### `$var{index}` → `$var[index]`
- `Librerias/MPDF57/mpdf.php` (×2, 128 ocurrencias c/u)
- `Librerias/TCPDF/tcpdf.php` (3 ocurrencias)
- `Librerias/Smarty/Smarty.class.php` (4 ocurrencias)

### 4.4 Dependencias y Librerías

#### Gestión de Dependencias
- **`composer.json`**: Dependencias modernas (monolog, php-debugbar, whoops, nusoap, tcpdf, mPDF, phpmailer, spreadsheet-reader)
- **`composer.lock`**: Bloqueo de versiones compatibles con PHP 7.1+
- **`composer.phar`**: Composer 2.2.x LTS (compatible con PHP 7.1.33)
- **`php.ini`**: Extensiones mysqli, pdo_mysql, openssl, curl, mbstring

#### Archivos de Librerías Legacy Corregidos
- `Librerias/MPDF57/mpdf.php` — Fix `/e` modifier
- `Librerias/PHPMail/class.phpmailer.php` — Compatibilidad PHP 7
- `Librerias/Smarty/plugins/function.fetch.php` — Compatibilidad PHP 7
- `Librerias/barcode/encode_bars.php`, `php-barcode.php` — Compatibilidad PHP 7
- `Librerias/fpdf/fpdf.php`, `WS/libs/fpdf/fpdf.php` — Compatibilidad PHP 7
- `Librerias/procedimientos/almacenados_standar.php` — Compatibilidad PHP 7
- `Librerias/FactElect/XmlSecurity/Key/P12.php` — Fix eregi_replace

---

## 5. Nueva API REST y Clases de Servicio

### 5.1 API REST con Slim

- **`api/index.php`**: Entry point con Slim Framework ($APP_REAL_PATH dinámico, chdir emulation)
- **`api/v1/auth/auth.php`**: Autenticación JWT
- **`api/v1/inventario/categorias.php`**, `marcas.php`, `productos.php`: CRUD inventario
- **`api/v1/relavera/manifiestos.php`**: Manifiestos de relavera
- **`api/v1/tesoreria/clientes.php`**: Clientes
- **`api/v1/adquisiciones/proveedores.php`**: Proveedores
- **`api/v1/test.php`**, `api/v1/test_auth2.php`: Endpoints de prueba

### 5.2 Clases de Servicio

- **`classes/Categoria.php`**: Modelo de categorías
- **`classes/Manifiesto.php`**: Modelo de manifiestos
- **`classes/Cliente.php`**: Modelo de clientes
- **`classes/DataAPI.php`**: Clase utilitaria para API (workdir emulation, path resolution)
- **`classes/FacturacionElectronica.php`**: Facturación electrónica
- **`classes/Tarea.php`**: Modelo de tareas

### 5.3 Resolución de Rutas Absolutas

Se inyectó dinámicamente `$APP_REAL_PATH` y `$GLOBALS['APP_REAL_PATH']` al inicio de `api/index.php` apuntando al directorio raíz del proyecto (`realpath(__DIR__ . "/../")`), permitiendo que las inclusiones legacy funcionen desde la API.

### 5.4 Emulación de Directorio de Trabajo

Técnica de `chdir()` temporal al FRONT equivalente antes de incluir lógica legacy, restaurándolo después. Permite que rutas relativas como `require_once('../../auditoria/LOGICA/...')` funcionen desde la API sin refactorizar cientos de archivos.

---

## 6. Despliegue Local y Docker

### 6.1 Backend (API)

```bash
php -S localhost:8000 router.php
```

Disponible en `http://localhost:8000`. Verificar con `http://localhost:8000/api/v1/test`.

### 6.2 Docker

Se crearon `Dockerfile` y `docker-compose.yml` para entorno aislado:

```bash
docker-compose up -d --build
docker exec -it exa-contable-relavera-php bash
php composer.phar install
```

### 6.3 Configuración Local

- `php.ini`: `short_open_tag = On`, extensiones mysqli, pdo_mysql, openssl, curl, mbstring
- Credenciales BD por defecto: root@localhost, sin contraseña, base `exa_master`
- Conexión núcleo: `DATA/MysqlConexion.php`

---

## 7. Frontend Next.js (Vercel)

- **`vercel/`**: Aplicación Next.js completa con dashboard, login y módulos de gestión
- Autenticación contra API backend
- CRUD de inventario (categorías, marcas, productos)
- Manifiestos y detalles
- Despliegue configurado para Vercel

### Correcciones en Frontend

- **Claves duplicadas en login**: Combinación `Emp_Cod-Suc_Des` para keys únicas de React
- **Panel de Actores**: Modales de creación/edición para Clientes y Proveedores
- **Inventario**: Modales para Categorías, Marcas y Productos con carga dinámica vía `useQuery()`
- **Manifiestos**: Modal de detalles con consumo de API `obtenerDetalle(item.Man_Cod)`

### TypeScript Validation

```bash
npx tsc --noEmit  # ✅ Compilación exitosa al 100%
```

---

## 8. Envío de Correo Electrónico

Ubicación: `enviar_correo/`

| Archivo | Propósito |
|---------|-----------|
| `config_correo.php` | Configuración SMTP (exacontable.com, TLS, puerto 587) |
| `ClaseEnviarCorreo.php` | Clase PHPMailer con configuración SMTP |
| `enviar_comprobante.php` | Punto de entrada y función `enviar_correo_comprobante()` |

Uso desde facturación:

```php
require_once __DIR__ . '/../../enviar_correo/enviar_comprobante.php';
$enviado = enviar_correo_comprobante($correo, $destinatario, $body, $remitente, $adjuntos);
```

---

## 9. Optimizaciones Realizadas

### 9.1 Eliminación de N+1 en Grid de Ventas

**Problema:** El jqGrid `ReportResumen` en `fac_con_fac_ven_3.0.php` ejecutaba 4 consultas individuales por fila (comprobante, contado/crédito, formas de pago, retenciones), generando ~4002 consultas por búsqueda de 1000 filas.

**Solución:** Se agregaron 4 LEFT JOINs y subconsultas directamente en la query paginada en `MODELS/ventas.php` (caso `setTotales`), eliminando el loop PHP. Además se redujo `rowNum` de 1000 a 100.

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Consultas por búsqueda (100 filas) | ~402 | ~2 | 99.5% |
| Consultas por búsqueda (1000 filas) | ~4002 | ~2 | 99.95% |
| Tiempo estimado (1000 filas) | 5-30 s | 0.2-1 s | ~95% |

**Archivos modificados:**
- `MODELS/ventas.php:165-216` — LEFT JOINs en setTotales
- `facturacion/FRONT/fac_con_fac_ven_3.0.php:166-171` — Loop simplificado
- `facturacion/FRONT/fac_con_fac_ven_3.0.php:2626-2627` — `rowNum: 100`

### 9.2 Más Rendimiento (Pendiente)

- Cachear `confi_fact` y `llave_elect` con APCu (candidatos ideales, cambian rara vez)
- Revisar patrón N+1 en compras (`fac_con_fac_com_3.0.php:130`, mismo patrón con `getComprobanteByCopCod`)
- Monitorear en producción con DebugBar (threshold 200ms)

---

## 10. Módulos y Relaciones

### 10.1 Arquitectura Hub-and-Spoke

```
VENTAS ──→ ventas_compr ──→ CONTABILIDAD
   │                          ↑
   ├──→ kardex_ie ────────────┤
   ├──→ ccpp_cobrar → TESORERIA
   ├──→ pago_venta → TESORERIA
   ├──→ stock (updateStock())
   └──→ producto (Pro_Stk, Pro_Prp)

COMPRAS ──→ compr_auto ──→ CONTABILIDAD
   │
   ├──→ kardex_ie
   ├──→ ccpp_pagar → TESORERIA
   ├──→ stock (mismo updateStock())
   ├──→ producto
   └──→ requisiciones
```

### 10.2 Tablas Puente Críticas

| Tabla | Conecta | Dirección |
|-------|---------|-----------|
| `ventas_compr` | Ventas ↔ Comprobantes | Bidireccional |
| `compr_auto` | Compras ↔ Comprobantes | Bidireccional |
| `ventas_costo` | Ventas → Contab. | Unidireccional |
| `kardex_ie` | Ventas / Compras / Ajustes | Origen-agnóstico |
| `ccpp_cobrar` | Ventas → Tesorería (AR) | Unidireccional |
| `ccpp_pagar` | Compras → Tesorería (AP) | Unidireccional |
| `comprobantes` | Hub central contable | Todos → Contab. |

### 10.3 Reglas de Optimización

| Regla | Alcance |
|-------|---------|
| Cambios en `updateStock()` deben probar ventas Y compras | Ventas ↔ Compras |
| Índices en tablas puente benefician múltiples módulos | Todos |
| Optimizaciones N+1 en ventas → buscar patrón similar en compras y tesorería | Ventas ↔ Compras ↔ Tesorería |
| Refactor de endpoints debe aplicar a comprobantes Y retenciones | API |
| `confi_fact` y `llave_elect` son cacheables sin riesgo | API Ventas + Retenciones |
| Aislamiento por `Emp_Cod` vía sesión permite optimizaciones horizontales | Todos |
| No particionar `kardex_ie` por origen sin considerar los otros dos | Inventario |

---

## 11. Pendientes

### 11.1 PHP 8.x — Correcciones Restantes

**Resueltas (agos 2026, rama `fix/security-auth-sql-injection`):**

- [x] Reemplazar `utf8_encode`/`utf8_decode` por `mb_convert_encoding` — ✅ 0 llamadas reales restantes; los 2 usos de `utf8_decode` de NuSOAP (`WS/libs/nuSoap/nusoap.php`, `class.soap_parser.php`) → `mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8')`. Quedan menciones solo en comentarios (`refactor_utf8.php`, NuSOAP) y en `QUARANTINE/`.
- [x] Revisar uso de `${var}` (deprecado en PHP 8.2) — ✅ N/A: los matches restantes son template literals JS dentro de archivos PHP (no código PHP). Los `${var}` PHP se eliminaron en la fase 1.
- [x] `var $prop` → visibilidad explícita — ✅ 411 ocurrencias convertidas a `public` vía tokenizer (`T_VAR`) en 12 archivos NuSOAP + `framework/php/ventanasSocket/phpwebsocket.php`. Restan solo `control-tributario-ec/libs/fpdf/*` (excluido de la migración).
- [x] Propiedades dinámicas → `#[AllowDynamicProperties]` en clases base — ✅ escaneo con php-parser: 30 accesos reales, todos cubiertos. Se añadió el atributo a `AbstractModel`, `Zend_Db_Adapter_Abstract` (Abstract.php) y `base_mysql` (DAC.php). Ya lo tenían `MysqlConexion`, `MysqlDatos` (heredado por `MysqlDatosContab` y todas las clases `Class_*_Log_*`), clases de `TreeMenu` y DAC. `SriScraperJob` está protegido por `property_exists`.
- [x] `continue` targeting switch warning — ✅ verificado con php-parser en TCPDF, MPDF57 (×2), PHPMailer_2023 y toda la app: 0 casos reales. Las líneas citadas (`TCPDF:17778`, `PHPMailer:4891`) usan `continue 2` en switch+loop, válido en PHP 8.2 (solo `continue;` sin nivel dentro de switch genera warning).
- [x] Eliminar dependencia de `register_globals.php` (emulación insegura) — ✅ el archivo es ahora un bootstrap de seguridad: solo inyecta variables de sesión `Ses_` + helpers (`Req`, `esc`, CSRF). Los ~270 includes restantes son el mecanismo compartido de arranque; ya no hay inyección global de variables de petición.

**Pendientes reales:**

- [ ] Reemplazar operador `@` silenciador por manejo explícito
- [ ] Migrar librerías legacy a versiones Composer (mPDF 8.x, Smarty 5.x, TCPDF 6.x, NuSOAP → soapclient nativo)
- [ ] Type hinting y declaraciones de tipo estrictas
- [ ] Deprecations restantes de NuSOAP/SOAP (por ejemplo, `wsdl`/`soap` no soportados por ext-soap en funciones de cliente)

### 11.2 Pruebas por Módulo

Orden recomendado de validación funcional:

1. administrador (login, sesión, permisos)
2. DATA + MODELS (transversal)
3. facturacion (ventas, compras, electrónica)
4. tesoreria (cobros/pagos, plantillas cheques)
5. contabilidad (reportes, balances)
6. relavera (manifiestos, anticipos, dashboards)
7. rrhh (roles, nómina)
8. bananero (labores, liquidación)
9. activosfijos
10. inventario
11. auditoria
12. transportecarga
13. compras
14. adquisiciones
15. camaronera
16. bodega
17. caja_chica
18. api (REST endpoints)
19. WS (servicios web)
20. index.php (login)
21. Módulos menores

### 11.3 Optimizaciones Pendientes

- [ ] Ejecutar prueba real contra BD con mayor volumen de ventas
- [ ] Cachear `isSummary` con APCu para empresas con millones de registros
- [ ] Monitorear en producción con DebugBar (threshold 200ms)
- [ ] Aplicar mismo patrón N+1 en compras (`fac_con_fac_com_3.0.php:130`)
- [ ] Cachear `confi_fact` y `llave_elect` con APCu
- [ ] Considerar particionamiento horizontal por `Emp_Cod`

### 11.4 Deuda Técnica

- [ ] Archivos duplicados: `*-old.php`, `* copy.php`, `*-OLD-*.php`
- [ ] Múltiples versiones coexistiendo (`3.0`, `3.1`, `3.2`)
- [ ] Archivar fuera de `htdocs` o marcar como no desplegables

---

## 12. Estadísticas

### Generales

| Métrica | Valor |
|---------|-------|
| Archivos PHP en el proyecto | ~2,815 |
| Archivos modificados | ~464 (excluye vendor/) |
| Líneas añadidas | 9,463 |
| Líneas eliminadas | 2,530 |
| Módulos afectados | 12 (contabilidad, facturación, tesorería, administrador, activos fijos, inventario, RRHH, bananero, relavera, auditoría, compras, adquisiciones) |
| Librerías actualizadas | mPDF, TCPDF, PHPMailer, NuSoap, FPDF, Smarty |
| Archivos con `each()` reemplazado | 95+ (app) + ~10 (librerías) |
| Archivos con `split()` reemplazado | 12 |
| Archivos con `utf8_encode/decode` reemplazado | 14+ (0 llamadas reales restantes en app) |
| `var $prop` → `public` convertidos | 411 (NuSOAP ×12 + websocket) |

### Volumen por Módulo (archivos PHP)

| Módulo | Archivos |
|--------|---------:|
| Tesorería | 590 |
| Facturación | 569 |
| Contabilidad | 148 |
| Administrador | 137 |
| MODELS | 107 |
| Activos Fijos | 91 |
| Relavera | 71 |
| RRHH | 65 |
| Bananero | 50 |
| Resto | ~223 |

---

## Comandos Útiles

```powershell
# Lint PHP 8.2
php -l archivo.php

# Lint PHP 7.1
& "C:\php71\php.exe" -c "C:\php71\php.ini" -l archivo.php

# Buscar each() PHP (no jQuery)
rg "\beach\s*\(" --glob "*.php" --glob "!Librerias/**"

# Buscar utf8_encode
rg "utf8_encode" --glob "*.php"

# Buscar var $prop
rg "^\s*var\s+\$" --glob "*.php"

# Archivos que incluyen register_globals
rg "register_globals" --glob "*.php" -l

# Versiones PHP disponibles
php -v
& "C:\php71\php.exe" -v
```

---

## Referencias

| Documento | Ubicación |
|-----------|-----------|
| **Este documento** | `docs/MIGRACION.md` |
| Optimización Grid Ventas N+1 | `docs/optimizacion-grid-ventas-N+1.md` |
| Relaciones entre Módulos | `docs/relaciones-modulos-optimizacion.md` |
| Envío de Correo | `enviar_correo/README.md` |
| Estructura del proyecto | `ESTRUCTURA_PROYECTO.md` |
| Dashboard escaneo | `administrador/FRONT/dashboard_proyecto.php` |

---

*Mantenimiento: actualizar este documento al completar cada fase de la migración hacia PHP 8.x.*
