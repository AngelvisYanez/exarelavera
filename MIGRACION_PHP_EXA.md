# Migración PHP — Sistema EXA (ERP)

**Documento generado:** 22 de mayo de 2026  
**Ruta del proyecto:** `c:\xampp\htdocs`  
**PHP actual (XAMPP):** 5.3.8  
**Total archivos `.php` (sin `ARCHIVOS DE XAMPP`):** ~2.815  

Este documento es la referencia principal para planificar la actualización de PHP. Los conteos de patrones se obtuvieron con búsqueda en el código fuente (mayo 2026).

---

## Índice

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura del sistema](#2-arquitectura-del-sistema)
3. [Inventario por módulo](#3-inventario-por-módulo)
4. [Patrones críticos y conteos](#4-patrones-críticos-y-conteos)
5. [Librerías de terceros](#5-librerías-de-terceros)
6. [Riesgos por versión destino](#6-riesgos-por-versión-destino)
7. [Plan de migración por fases](#7-plan-de-migración-por-fases)
8. [Orden recomendado de módulos](#8-orden-recomendado-de-módulos)
9. [Checklist de pruebas](#9-checklist-de-pruebas)
10. [Herramientas y documentos relacionados](#10-herramientas-y-documentos-relacionados)
11. [Chats de Cursor (historial)](#11-chats-de-cursor-historial)

---

## 1. Resumen ejecutivo

EXA es un ERP monolítico en PHP procedural + clases legacy, organizado por módulos (`FRONT` / `LOGICA` / `VALIDACIONES`). La base de datos ya usa **mysqli** (no la extensión `mysql_*` eliminada en PHP 7).

| Aspecto | Estado actual | Impacto al migrar |
|--------|---------------|-------------------|
| Versión PHP | **5.3.8** | Salto grande; conviene ir por etapas (5.3 ? 7.4 ? 8.x) |
| Conexión BD | `DATA/MysqlConexion.php` (mysqli) | Bajo en el núcleo; revisar drivers en servidor |
| Variables implícitas | `register_globals.php` vía `seguridad.php` | **Muy alto** — casi toda pantalla depende de `$ajax_*`, `$Emp_Cod`, etc. |
| Estilo OOP | `var $`, constructores `__construct1/2/3` | Medio — warnings en 7+, refactor gradual |
| `each()` | ~95 archivos de aplicación + librerías | **Alto en PHP 8** (eliminado) |
| APIs modernas ya usadas puntualmente | `??`, `JSON_PRETTY_PRINT` en pocos archivos | Ya hay código “mixto”; unificar criterio |
| Plantillas cheques tesorería | ~200+ PHP casi duplicados | Mucho ruido en conteos; no bloquean migración global |

**Conclusión:** La migración es viable, pero el cuello de botella no es solo “subir PHP en XAMPP”, sino **validar pantalla por pantalla** con errores visibles y un entorno de prueba clonado.

---

## 2. Arquitectura del sistema

### 2.1 Estructura estándar por módulo

Ver también: `ESTRUCTURA_PROYECTO.md` en la raíz del repo.

```
[modulo]/
??? FRONT/          ? PHP + HTML + includes (interfaz y endpoints AJAX en el mismo archivo)
??? LOGICA/         ? *_log_*.php (clases datos) + *_sql_*.php (consultas por case/switch)
??? VALIDACIONES/   ? JavaScript (jqGrid, formularios)
```

### 2.2 Núcleo transversal

| Ruta | Rol |
|------|-----|
| `index.php` | Login, selección de empresa, redirección |
| `administrador/LOGICA/seguridad.php` | Sesión, permisos, menú; **incluye `register_globals.php`** |
| `administrador/FRONT/home.php` | Shell principal post-login |
| `DATA/MysqlConexion.php` | Conexión mysqli master / corporativa |
| `DATA/GestorErrores.php` | Manejo de errores |
| `Librerias/config.php/register_globals.php` | Emula `register_globals` (GET/POST/SESSION ? variables PHP) |
| `Librerias/procedimientos/almacenados_standar.php` | Procedimientos comunes |
| `MODELS/` | 107 modelos PHP reutilizables (ventas, compras, manifiesto, etc.) |
| `mascaras/model1`, `mascaras/model3` | UI, jqGrid, CSS moderno |

### 2.3 Flujo típico de una pantalla

```
FRONT/xxx.php
  ? require seguridad.php
      ? register_globals.php  (crea $Parametro desde $_GET/$_POST/$_SESSION)
      ? logica.php / permisos
  ? require ../LOGICA/xxx_log.php
  ? HTML + <script src="../VALIDACIONES/xxx.js">
  ? if (isset($ajaxAlgo)) { consulta; echo json; exit; }
```

### 2.4 Bases de datos

- **Master:** `exa_master` (empresas, acceso, planes, usuarios globales en desarrollos recientes).
- **Por empresa:** `Dat_Dis` resuelto en login; cada empresa tiene su BD.

---

## 3. Inventario por módulo

Conteo de archivos `.php` por carpeta de primer nivel (excluye `ARCHIVOS DE XAMPP`, `framework`, `Librerias`):

| Módulo | Archivos PHP | Notas |
|--------|-------------:|-------|
| **tesoreria** | 590 | Mayor volumen; muchas plantillas `cheques/{id}/` |
| **facturacion** | 569 | Ventas, compras, electrónica, cruces |
| **contabilidad** | 148 | Balances, plan de cuentas, asientos |
| **administrador** | 137 | Login, permisos, planes, dashboard proyecto |
| **MODELS** | 107 | Impacto transversal |
| **activosfijos** | 91 | |
| **componentes** | 87 | |
| **relavera** | 71 | Manifiestos, anticipos, dashboards |
| **rrhh** | 65 | Roles, nómina |
| **bananero** | 50 | Labores, liquidación |
| **WS** | 41 | Servicios web |
| **inventario** | 35 | |
| **mascaras** | 22 | UI compartida |
| **auditoria** | 22 | Despacho, tareas |
| **adquisiciones** | 22 | |
| **transportecarga** | 21 | |
| **compras** | 20 | |
| **camaronera** | 18 | |
| **DATA** | 17 | Conexión, helpers |
| **caja_chica** | 14 | |
| **bananeroOld** | 12 | Legado — baja prioridad |
| **bodega** | 12 | |
| **exa** | 7 | |
| **printers** | 7 | |
| **api** | 6 | |
| **skins** | 5 | Dashboard empresarial |
| **classes** | 5 | |
| **ccxpp** | 2 | |
| **TOTAL** | **~2.815** | |

---

## 4. Patrones críticos y conteos

### 4.1 `register_globals.php` (prioridad máxima)

**Archivo:** `Librerias/config.php/register_globals.php`

Convierte automáticamente:

- Cada clave de `$_GET`, `$_POST`, `$_SESSION` en una variable PHP (`$nombre_clave`).
- JSON body (`php://input`) en variables dinámicas.

**Cadena principal:**

```
seguridad.php ? register_globals.php
```

Casi todo módulo operativo incluye `seguridad.php`. Código típico en FRONT:

```php
if (isset($ajax_empresas2)) { ... }  // $ajax_empresas2 nunca se declara explícitamente
```

**Archivos con `require` directo a `register_globals`:** ~270 (incluye cientos de impresiones de cheques en `tesoreria/FRONT/cheques/`).

**Archivos núcleo (sin contar plantillas cheques):**

- `index.php`
- `administrador/LOGICA/seguridad.php`
- `administrador/FRONT/home.php`
- `administrador/FRONT/adm_con_control_1.2.php`
- `facturacion/COMPONENTES/tesPdf*.php`
- APIs: `administrador/api/*.php`, `compras/api/*.php`

**Estrategia de migración:**

1. **Fase corta:** Mantener `register_globals.php` en PHP 7.4 (sigue funcionando).
2. **Fase media:** Pantallas nuevas y APIs con `$_GET['x']` / `$_POST` explícitos.
3. **Fase larga:** Ir módulo a módulo eliminando dependencia (empezar por APIs JSON pequeñas).

---

### 4.2 `each()` — eliminado en PHP 8.0

| Ámbito | Archivos afectados (aprox.) |
|--------|----------------------------|
| Aplicación (FRONT/LOGICA) | ~90 |
| Librerías (TCPDF, mPDF, Smarty, calendar) | ~10 |

**Mayor densidad en aplicación:**

- `auditoria/FRONT/aud_mod_despacho_contratos_1.0.php` (29 usos)
- `facturacion/FRONT/fac_con_fac_ven_3.0.php` (14)
- `relavera/FRONT/man_adm_turnos.php` (11)
- `rrhh/FRONT/rhu_alt_abono_rol.php` (9)

**Reemplazo estándar:** `foreach ($arr as $k => $v)`

---

### 4.3 `var $propiedad` (estilo PHP 4)

Presente en clases legacy, especialmente:

- `DATA/MysqlConexion.php`
- Clases `Class_Log_*` en cada módulo
- `relavera/FRONT/man_alt_fac.php` y similares

En PHP 7.4+ genera **Deprecated**; en 8.x sigue funcionando con warning. Sustituir por `public` / `protected` cuando se toque la clase.

---

### 4.4 Constructores múltiples (`__construct1`, `__construct2`, `__construct3`)

Solo definidos explícitamente en variantes antiguas de `MysqlConexion` en `DATA/arhivos viejos/`. La clase activa `DATA/MysqlConexion.php` usa un único `__construct()` con `switch` interno — compatible, pero confuso de mantener.

---

### 4.5 Extensión `mysql_*`

**En código de aplicación:** no usada (solo en `ARCHIVOS DE XAMPP/xampp/` de referencia).

**Conclusión:** No hay bloqueo por `mysql_connect` en EXA productivo.

---

### 4.6 Funciones y APIs por versión

| Función / API | Introducida | Uso en EXA | Notas |
|---------------|-------------|-----------|-------|
| `http_response_code()` | PHP 5.4 | Pocos archivos; ya corregido en `adm_global_user.php` con `header()` | Patrón a replicar |
| `mysqli_begin_transaction()` | PHP 5.5 | Eliminado/reemplazado en fixes recientes por `START TRANSACTION` | Ver chat adm_global_user |
| `JSON_PRETTY_PRINT` | PHP 5.4 | Dashboard proyecto; helper `dashboard_json_helper.php` para 5.3 | OK en 7.4+ |
| Operador `??` | PHP 7.0 | Pocos archivos | Evitar en código que deba correr en 5.3 hasta cortar |
| `utf8_encode` / `utf8_decode` | Deprecated 8.2, removidos 9.0 | Uso disperso (login, APIs) | Migrar a `mb_convert_encoding` |
| `create_function()` | Removido 8.0 | 1 archivo (`administrador/LOGICA/TreeMenu.php`) | Reemplazar por función anónima |
| `split()` / `ereg()` | Removidos hace años | Muy pocos o ninguno en app | Revisar con grep antes de PHP 8 |

---

### 4.7 Archivos duplicados / deuda

Patrones que inflan el repo y confunden pruebas:

- `*-old.php`, `* copy.php`, `*-OLD-*.php`
- `facturacion/FRONT/fac_alt_fac_ven_3.2-old.php`
- Múltiples versiones `3.0`, `3.1`, `3.2` coexistiendo

**Recomendación:** No migrar copias; archivar fuera de `htdocs` o marcar como no desplegables.

---

## 5. Librerías de terceros

Carpeta `Librerias/` — **actualizar o reemplazar antes de PHP 8**:

| Librería | Ruta aproximada | Riesgo PHP 8 |
|----------|-----------------|--------------|
| **TCPDF** | `Librerias/TCPDF/` | Alto — `each()` interno |
| **mPDF 5.7** | `Librerias/MPDF57/`, `Librerias/slider/MPDF57/` | Alto — muy antigua |
| **Smarty (antiguo)** | `Librerias/Smarty/` | Alto |
| **PHPMailer (antiguo)** | `Librerias/PHPMail/` | Medio |
| **jqGrid / jQuery** | `framework/`, `mascaras/` | No es PHP; OK |
| **PHPDebugBar / Whoops** | `Librerias/debugbar/`, `whoops/` | Revisar versiones para PHP 7.4/8 |

**Acción:** Inventariar qué pantallas llaman a PDF (facturación, tesorería, cheques) y probar un PDF de cada tipo en entorno PHP 7.4.

---

## 6. Riesgos por versión destino

### 6.1 Objetivo intermedio: PHP 7.4

| Ventaja | Riesgo |
|---------|--------|
| Fin de vida ya pasado pero muy usado en migraciones | Muchos **Deprecated** visibles si `E_ALL` |
| Compatible con mayoría de sintaxis actual | `each()` aún existe (deprecated) |
| mysqli estable | Extensiones deben habilitarse en `php.ini` |

### 6.2 Objetivo final: PHP 8.1 / 8.2

| Cambio | Impacto EXA |
|--------|-------------|
| `each()` removido | ~90 archivos app + librerías PDF |
| Tipado estricto en funciones internas | Pasar `null` a parámetros string/int |
| Warnings ? Error en muchos casos | Código con `@` silenciador fallará “en seco” |
| `utf8_encode` removido (9.0) | Login y exportaciones |

---

## 7. Plan de migración por fases

### Fase 0 — Preparación (1–2 semanas)

- [ ] Clonar BD + código en servidor/VM de prueba.
- [ ] Instalar XAMPP o stack con **PHP 7.4** en paralelo (no tocar producción).
- [ ] `php.ini`: `display_errors=On`, `error_reporting=E_ALL`, log a archivo.
- [ ] Documentar URL de prueba y credenciales.
- [ ] Backup completo antes de cualquier cambio.

### Fase 1 — PHP 7.4 en entorno de prueba

- [ ] Subir solo versión PHP; **no** cambiar código masivamente.
- [ ] Probar **smoke test** (sección 9).
- [ ] Corregir **fatal** primero (`http_response_code`, transacciones, etc.).
- [ ] Sustituir `each()` en archivos que fallen en rutas críticas.

### Fase 2 — Núcleo estable

- [ ] `DATA/MysqlConexion.php` — propiedades `public`, quitar `@` donde oculte fallos reales.
- [ ] `register_globals.php` — mantener; auditar variables más usadas (`$ajax*`, `$search`, `$Op`).
- [ ] `MODELS/` — ejecutar pantallas que usan modelos nuevos (ventas, anticipos, manifiesto).
- [ ] Actualizar **mPDF/TCPDF** en rama de prueba para facturación/tesorería.

### Fase 3 — Módulos operativos (iterativo)

Por cada módulo: listar pantallas FRONT activas (menú), probar ABM + reportes + AJAX.

Orden sugerido en [sección 8](#8-orden-recomendado-de-módulos).

### Fase 4 — PHP 8.1+

- [ ] Eliminar todo `each()` en aplicación.
- [ ] Actualizar librerías PDF/mail.
- [ ] Reemplazar `utf8_encode` / `utf8_decode`.
- [ ] Segunda ronda smoke test completa.

---

## 8. Orden recomendado de módulos

| Orden | Módulo | Motivo |
|------:|--------|--------|
| 1 | **administrador** | Login, sesión, permisos — sin esto no hay pruebas |
| 2 | **DATA** + **MODELS** | Transversal a todos |
| 3 | **facturacion** | Core negocio; muchos AJAX |
| 4 | **tesoreria** | Cobros/pagos; cuidado con plantillas cheques |
| 5 | **contabilidad** | Reportes pesados; consultas SQL |
| 6 | **inventario** | Stock ligado a facturación |
| 7 | **relavera** | Módulo activo con APIs y dashboards |
| 8 | **bananero** | Operación agrícola |
| 9 | **rrhh**, **compras**, **adquisiciones** | |
| 10 | **auditoria**, **transportecarga**, resto | Menor criticidad o menor uso |

---

## 9. Checklist de pruebas

### Smoke test mínimo (cada cambio de versión PHP)

| # | Prueba | Ruta / acción |
|---|--------|----------------|
| 1 | Login | `index.php` ? empresa ? ingreso |
| 2 | Home y menú | `administrador/FRONT/home.php` |
| 3 | Cambio de empresa/sucursal | Si aplica en sesión |
| 4 | Factura venta | Alta o consulta `fac_alt_fac_ven_3.2.php` / `fac_con_fac_ven_3.0.php` |
| 5 | Cobro cliente | `tes_alt_cccc_lotes_2.0.php` o modificar |
| 6 | Reporte PDF | Una factura o cheque |
| 7 | API JSON | Ej. `facturacion/FRONT/api_cruce_com_ven_2.0.php` con sesión |
| 8 | Módulo Relavera | `dashboard_relavera.php` si se usa |

### Señales de problemas típicos

- Pantalla en blanco ? fatal PHP; revisar `php_error.log`.
- `500` en AJAX ? respuesta HTML de error en lugar de JSON.
- Variables vacías ? `register_globals` no ejecutado o nombre de parámetro cambiado.
- PDF corrupto ? librería incompatible con PHP nuevo.

---

## 10. Herramientas y documentos relacionados

| Recurso | Ubicación | Para qué sirve |
|---------|-----------|----------------|
| **Este documento** | `docs/MIGRACION_PHP_EXA.md` | Plan migración PHP |
| Estructura de código | `ESTRUCTURA_PROYECTO.md` | Convenciones FRONT/LOGICA/VALIDACIONES |
| Dashboard escaneo (líneas/horas) | `administrador/FRONT/dashboard_proyecto.php` | Estimación de esfuerzo por archivo, **no** compatibilidad PHP |
| Escaneo backend | `administrador/FRONT/dashboard_scan.php` | API de conteo de líneas |
| Config escaneos guardados | `administrador/FRONT/dashboard_config.json` | Complejidades por archivo (ej. relavera) |
| Compatibilidad PHP 5.x (dashboard skins) | `skins/php/BITACORA_DASHBOARD_EMPRESARIAL.md` | Notas `??` vs ternario |
| Bitácora cambios PHP 5.3 | `BITACORA_CAMBIOS_REPORTES_ANTIGUEDAD.md` | “Compatible con PHP 5.3.8” |

### Comandos útiles (PowerShell en raíz del proyecto)

```powershell
# Versión PHP
c:\xampp\php\php.exe -v

# Buscar each() en aplicación (excluir Librerias)
rg "\beach\s*\(" --glob "*.php" --glob "!Librerias/**"

# Archivos que incluyen register_globals directamente
rg "register_globals" --glob "*.php" -l

# Conteo PHP por módulo
Get-ChildItem c:\xampp\htdocs\facturacion -Recurse -Filter *.php | Measure-Object
```

### Analizadores externos (opcional)

- [PHPCompatibility](https://github.com/PHPCompatibility/PHPCompatibility) con PHP_CodeSniffer — reglas 7.4 / 8.0.
- [Rector](https://github.com/rectorphp/rector) — solo en rama Git; revisar diffs manualmente (ERP sensible).

---

## 11. Chats de Cursor (historial)

No existe un chat anterior **solo** de “auditoría PHP completa” guardado como archivo. Lo siguiente es lo más relacionado en el historial de este workspace (`htdocs`).

### Cómo abrir un chat en Cursor

1. Abre el panel **Chat** / historial de conversaciones.
2. Busca por título o por fragmentos del mensaje (ej. “migrar PHP”, “dashboard proyecto”, “PHP 5.3”).
3. Los IDs abajo identifican la conversación en  
   `C:\Users\servidor2RSP\.cursor\projects\c-xampp-htdocs\agent-transcripts\{id}\`

### Conversaciones relevantes

| Título (referencia) | ID | Contenido |
|-------------------|-----|-----------|
| **Migración PHP EXA (este hilo)** | `dd292990-7112-4e6b-959b-656afda1bbb2` | Pregunta sobre escaneo + plan migración; generación de este `.md` |
| **Dashboard proyecto / escaneo líneas** | `6086fe64-3b8a-496a-b4b1-cc030e4d662d` | `dashboard_proyecto.php`, complejidad, horas, PDF — **no es migración PHP** pero es el “escaneo” detallado por archivos |
| **Compatibilidad PHP 5.3 adm_global_user** | `2cee0a76-adc6-4aa9-aec7-ad5d2ad6d7ec` | `http_response_code`, `mysqli_begin_transaction`, PHP 5.3 |
| **Dashboard Relavera PHP 5.3.8** | `af8d2405-09c9-491f-add0-e41d8e578408` | Explícito: stack PHP 5.3.8, sin funciones > 5.3 |
| **Dashboard planes / PHP 5.3 timeouts** | `48e50fc4-b0a3-4e8f-ae7b-0f7a1fed57cc` | `set_time_limit`, optimización consultas |
| **Cruces compra-venta** | `7dcd9606-4a3c-4340-adfe-19b0bb9e192c` | Facturación reciente (no migración) |

Si buscabas un análisis **muy largo solo de PHP** de una sesión anterior en **otro PC o cuenta**, puede que no esté en este equipo; este documento lo reemplaza como referencia fija en el repo.

---

## Registro de cambios del documento

| Fecha | Cambio |
|-------|--------|
| 2026-05-22 | Creación inicial con inventario y plan por fases |

---

*Mantenimiento:* actualizar la tabla de conteos si se eliminan módulos grandes o se actualizan librerías PDF.
