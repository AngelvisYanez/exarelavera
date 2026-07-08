# Migración PHP 8 - Sistema EXA (ERP)

**Documento generado:** 07 de julio de 2026
**Rama:** `migracionphp`
**Versión objetivo:** PHP 7.1+ (con preparación para PHP 8.x)
**Total archivos modificados:** ~464 (excluyendo vendor/)
**+9,463 líneas añadidas / -2,530 eliminadas**

---

## 1. Resumen de la Migración

Se migró el sistema EXA (ERP monolítico en PHP procedural + clases legacy) desde PHP 5.3.8 a PHP 7.1+, aplicando correcciones de compatibilidad para las versiones 7.x y sentando las bases para PHP 8.x.

| Aspecto | Antes | Después |
|---------|-------|---------|
| Versión PHP | 5.3.8 | 7.1.14+ |
| Conexión BD | mysqli | mysqli (sin cambios) |
| Dependencias | Sin Composer | Composer 2.2.x LTS |
| Librerías PDF | mPDF 5.7 (legacy) | mPDF 7.0 (Composer) |
| librerías TCPDF | Manual | vía Composer |
| Frontend | Solo PHP | PHP + Next.js (Vercel) |
| API | Inexistente | REST API con Slim |

---

## 2. Cambios Realizados

### 2.1 Infraestructura y Configuración

- **`composer.json`**: Nuevo archivo con dependencias modernas (PHP 7.1.14, monolog, php-debugbar, whoops, nusoap, tcpdf, mPDF, phpmailer, spreadsheet-reader)
- **`composer.lock`**: Bloqueo de versiones compatibles con PHP 7.1
- **`composer.phar`**: Composer 2.2.x LTS integrado localmente
- **`php.ini`**: Configuración para entorno local (extensiones mysqli, pdo_mysql, openssl, curl, mbstring)
- **`.gitignore`**: Exclusión de `/servicios/`, `/exa_master/`, `/exa/`, `/frontend-next/`, `/vercel/`
- **`router.php`**: Router para servidor de desarrollo integrado PHP
- **`Dockerfile`**, **`docker-compose.yml`**: Contenedores para despliegue

### 2.2 Correcciones de Compatibilidad (PHP 5.4 → 7.x)

#### session_register() (eliminado en PHP 5.4)
Reemplazo de `session_register()` por asignación directa a `$_SESSION`:
- `administrador/FRONT/adm_con_control_1.0.php`
- `administrador/FRONT/adm_con_control_1.1.php`
- `auditoria/LOGICA/adm_con_control_1.1.php`

#### Asignación por Referencia `&new` (eliminado en PHP 7)
Corrección de `$var = &new Clase()` a `$var = new Class()`:
- `administrador/FRONT/adm_con_treemenu.php`
- `administrador/FRONT/adm_con_treemenu_p.php`
- `administrador/FRONT/adm_con_treemenu_adm_1.0_p.php`
- `Librerias/config.php/adm_con_treemenu.php`
- `mascaras/model1/img/adm_con_treemenu.php`

#### Superglobales como Parámetros (eliminado en PHP 7)
Renombrado de parámetros `$_POST` en firmas de funciones:
- `administrador/LOGICA/adm_log_soporte.php`
- `compras/LOGICA/requisiciones/index.php`

#### Modificador `/e` en preg_replace (eliminado en PHP 7)
Reemplazo por `preg_replace_callback()`:
- `Librerias/MPDF57/mpdf.php`
- `Librerias/MPDF57/compress.php`
- `Librerias/slider/MPDF57/mpdf.php`
- `Librerias/slider/MPDF57/compress.php`

#### eregi_replace() (eliminado en PHP 7)
Cambio a `preg_replace()` con flag `/i`:
- `Librerias/FactElect/XmlSecurity/Key/P12.php`

#### split() (eliminado en PHP 7)
Reemplazo por `preg_split()`:
- `contabilidad/FRONT/con_alt_compr_1.0.php`
- `contabilidad/FRONT/con_alt_compr_1.1.php`
- `contabilidad/FRONT/con_alt_comprdup.php`
- `contabilidad/FRONT/con_act_compr_1.1.php`
- `contabilidad/FRONT/con_baj_compr_1.0.php`
- `contabilidad/FRONT/con_baj_compr_1.1.php`
- `contabilidad/FRONT/con_con_compr_1.0.php`
- `contabilidad/FRONT/con_mod_compr_1.0.php`
- `contabilidad/FRONT/con_mod_compr_1.1.php`
- `contabilidad/FRONT/con_pri_compr_1.0.php`
- `contabilidad/FRONT/con_pri_compr_1.2.php`
- `contabilidad/LOGICA/con_pri_compr_1.1.php`

#### Short Open Tags (`<?`)

Se habilitó `short_open_tag = On` en php.ini; además se corrigieron etiquetas en:
- `index.php`
- `facturacion/FRONT/con_pri_compr.php`
- `facturacion/FRONT/tes_alt_bancos.php`
- Varias decenas de archivos de impresión de facturas por cliente

### 2.3 Correcciones para PHP 8.x

#### each() (deprecado en 7.2, eliminado en 8.0)
Reemplazo de `each()` por `foreach()` en más de 95 archivos, incluyendo:
- `contabilidad/FRONT/con_con_may_ban_1.0-30-03-26.php`
- `contabilidad/FRONT/con_con_mayorizacion_1.0.php`
- `contabilidad/FRONT/con_con_mayorizacion_1.1.php`
- `contabilidad/FRONT/con_con_mayorizacion_1.2.php`
- `contabilidad/FRONT/con_con_reporte_cuenta.php`
- `contabilidad/FRONT/con_pri_mayorizacion_1.0.php`
- `contabilidad/FRONT/con_pri_mayorizacion_1.1.php`
- `contabilidad/FRONT/con_pri_reporte_cuenta.php`
- `inventario/FRONT/inv_con_kardex_resumen.php`
- `inventario/FRONT/inv_con_kardex_resumido.php`
- `inventario/FRONT/inv_con_toma_fisica.php`
- `rrhh/FRONT/rhu_pri_anticipo.php`
- `rrhh/FRONT/rhu_pri_personal_1.0.php`
- `facturacion/FRONT/fac_con_kardex_1.0.php`
- `facturacion/FRONT/fac_con_kardex_xx_1.0.php`
- `facturacion/FRONT/fac_con_kar_res_1.0.php`
- `facturacion/FRONT/fac_pri_kardex_1.0.php`
- `facturacion/FRONT/fac_pri_fac_ven_1.0.php`
- `facturacion/FRONT/fac_pri_fac_eletronica_1.0.php`
- `facturacion/FRONT/fac_pri_fac_martha_1.0.php`
- `facturacion/FRONT/fac_pri_fac_olger_1.0.php`
- `facturacion/FRONT/fac_pri_liq_cpatino_1.0.php`
- `facturacion/FRONT/fac_pri_comprb_1.0.php`
- `facturacion/FRONT/tes_pri_factura_1.0.php`
- `facturacion/FRONT/tes_pri_kardex_1.0.php`
- `facturacion/LOGICA/fac_log_categoria.php`
- `bananero/FRONT/ban_alt_materiales_1.0.php`
- `bananeroOld/FRONT/ban_alt_materiales_1.0.php`
- `activosfijos/FRONT/act_pri_activo_1.0.php`
- `activosfijos/FRONT/act_pri_custodio_2.0.php`
- `auditoria/FRONT/aud_mod_dashboard_tareas_1.0.php`
- `tesoreria/FRONT/tes_alt_cheque_1.0.php`
- `tesoreria/FRONT/tes_baj_che_lib.php`
- `tesoreria/FRONT/tes_baj_cheque_1.0.php`
- `tesoreria/FRONT/tes_con_cheque_1.0.php`
- `tesoreria/FRONT/tes_mod_cheque_1.0.php`
- `tesoreria/FRONT/tes_pri_cheque_*.php` (en todas las carpetas de cheques)
- `tesoreria/FRONT/tes_pri_recibocobro_1.0.php`
- `tesoreria/FRONT/tes_pri_recibocobro_1.1.php`
- `tesoreria/FRONT/tes_pri_recibocobro_1.1_empresa.php`
- Todas las plantillas de cheques por empresa (1, 9, 10, 14, 19, 27, 31, 35, 36, 38, 50, 56, 96, 120, 123, 154, 155, 159, 160, 161, 162, 163, 164, 165, 171, 236, 237, 238, 240, 246, 247, 253, 254, 255, 258, 273, 277, 279, 284, 303, 314, 559, 569)
- Facturas personalizadas por cliente (~200+ archivos en `facturacion/`)

#### create_function() (eliminado en PHP 8.0)
Reemplazo por función anónima:
- `administrador/LOGICA/TreeMenu.php`

#### Trailing Commas en firmas/llamadas (PHP 8)
Corrección de comas de arrastre en:
- `framework/Slim/Http/Util.php`
- `classes/Manifiesto.php`
- `api/index.php`

### 2.4 Dependencias y Librerías

#### Gestión de Dependencias
- Integración de Composer 2.2.x LTS (compatible con PHP 7.1.33)
- Instalación de dependencias vía Composer: monolog, php-debugbar, whoops, spreadsheet-reader, phpmailer, nusoap, tcpdf, mPDF

#### Actualización de Librerías
- **mPDF**: Migración de MPDF57 (legacy con `/e`) a mPDF 7.0 vía Composer
- **TCPDF**: Actualización a versión 6.7 vía Composer
- **PHPMailer**: Integración vía Composer (reemplazo de clase legacy)
- **NuSoap**: Integración vía `econea/nusoap`

#### Archivos de Librerías Legacy Corregidos
- `Librerias/MPDF57/mpdf.php` - Fix `/e` modifier
- `Librerias/PHPMail/class.phpmailer.php` - Compatibilidad PHP 7
- `Librerias/Smarty/plugins/function.fetch.php` - Compatibilidad PHP 7
- `Librerias/barcode/encode_bars.php`, `php-barcode.php` - Compatibilidad PHP 7
- `Librerias/fpdf/fpdf.php`, `WS/libs/fpdf/fpdf.php` - Compatibilidad PHP 7
- `Librerias/procedimientos/almacenados_standar.php`, `almacenados_academico.php` - Compatibilidad PHP 7
- `Librerias/FactElect/XmlSecurity/Key/P12.php` - Fix eregi_replace

### 2.5 Nueva API REST

- **`api/index.php`**: Entry point con Slim Framework
- **`api/v1/auth/auth.php`**: Autenticación JWT
- **`api/v1/inventario/categorias.php`**, `marcas.php`, `productos.php`: CRUD inventario
- **`api/v1/relavera/manifiestos.php`**: Manifiestos de relavera
- **`api/v1/tesoreria/clientes.php`**: Clientes
- **`api/v1/adquisiciones/proveedores.php`**: Proveedores
- **`api/v1/test.php`**, **`api/v1/test_auth2.php`**: Endpoints de prueba

### 2.6 Clases de Servicio

- **`classes/Categoria.php`**: Modelo de categorías
- **`classes/Manifiesto.php`**: Modelo de manifiestos
- **`classes/Cliente.php`**: Modelo de clientes
- **`classes/DataAPI.php`**: Clase utilitaria para API (workdir emulation, path resolution)
- **`classes/FacturacionElectronica.php`**: Facturación electrónica
- **`classes/Tarea.php`**: Modelo de tareas

### 2.7 Router para Desarrollo Local

- **`router.php`**: Router que emula el comportamiento del servidor web Apache para el servidor integrado PHP

### 2.8 Frontend Next.js (Vercel)

- **`vercel/`**: Aplicación Next.js completa con dashboard, login, y módulos de gestión
- Autenticación contra API backend
- CRUD de inventario (categorías, marcas, productos)
- Manifiestos y detalles
- Despliegue configurado para Vercel

### 2.9 Documentación

| Documento | Descripción |
|-----------|-------------|
| `docs/MIGRACION_PHP_EXA.md` | Plan de migración original y análisis de impacto |
| `docs/MIGRACION_PHP_8.md` | **Este documento** - registro detallado de cambios |
| `docs/DESPLIEGUE_Y_MEJORAS.md` | Guía de despliegue local y optimizaciones |
| `docs/Implementación de Compatibilidad a PHP 7.1.33.md` | Correcciones específicas para PHP 7.1.33 |
| `docs/optimizacion-grid-ventas-N+1.md` | Optimización de consultas N+1 |
| `docs/relaciones-modulos-optimizacion.md` | Relaciones entre módulos |

---

## 3. Archivos de Prueba

Se crearon diversos scripts de prueba para validar la migración:

- `test_db.php`, `test_db_connection.php` - Conexión a base de datos
- `test_api.php` - Prueba de endpoints API
- `test_auth.php`, `test_auth_logic.php`, `test_auth_route.php` - Autenticación
- `test_login.php`, `test_login2.php` - Login
- `test_dashboard_smoke.php` - Smoke test dashboard
- `test_dependencies.php` - Dependencias de Composer
- `test_require.php` - Inclusiones de archivos
- `test_man_turnos2.php` - Módulo turnos
- `test_relavera_smoke.php` - Smoke test relavera
- `relavera/FRONT/test_*.php` - Pruebas del módulo relavera

---

## 4. Pendientes para PHP 8.x Completo

- [ ] Reemplazar `utf8_encode`/`utf8_decode` por `mb_convert_encoding`
- [ ] Revisar uso de `${var}` (deprecado en PHP 8.2)
- [ ] Eliminar dependencia de `register_globals.php` (emulación)
- [ ] Reemplazar operador `@` silenciador por manejo explícito
- [ ] Migrar todas las librerías legacy a versiones Composer
- [ ] Pruebas de regresión en módulos de auditoría y transporte
- [ ] Type hinting y declaraciones de tipo estrictas

---

## 5. Estadísticas

- **Archivos PHP en el proyecto:** ~2,815
- **Archivos modificados:** ~464 (no incluye vendor/)
- **Líneas añadidas:** 9,463
- **Líneas eliminadas:** 2,530
- **Módulos afectados:** contabilidad, facturación, tesorería, administrador, activos fijos, inventario, RRHH, bananero, relavera, auditoría
- **Librerías actualizadas:** mPDF, TCPDF, PHPMailer, NuSoap, FPDF
- **Archivos con `each()` reemplazado:** 95+

---

*Mantenimiento: actualizar este documento al completar cada fase de la migración hacia PHP 8.x.*
