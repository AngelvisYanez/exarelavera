# Auditoría de Código - EXA Contable Relavera

**Fecha:** 2026-07-27  
**Alcance:** Clases PHP, API, Modelos, Seguridad, Calidad  

---

## 1. Pruebas Unitarias Creadas

### Estructura
```
tests/
  classes/
    ApiResponseTest.php       (15 tests)
    FacturacionElectronicaTest.php (14 tests)
    DataAPITest.php            (22 tests)
    TareaClassTest.php         (16 tests)
```

### Resultado
```
PHPUnit 10.5.64
OK (62 tests, 124 assertions)
```

---

## 2. Hallazgos de Seguridad

### CRÍTICOS (5)

| # | Hallazgo | Archivo | Línea |
|---|----------|---------|-------|
| 1 | **SQL Injection en autenticación** - Interpolación directa de `$Par_Sql[]` en queries SQL sin escape | `api/v1/auth/auth.php` | 17, 29 |
| 2 | **Token inseguro (base64)** - El token JWT es solo `base64_encode(user:empresa:timestamp)`, falsificable trivialmente | `api/v1/auth/auth.php` | 118 |
| 3 | **Contraseñas MD5** - Hashing MD5 sin salt, vulnerable a fuerza bruta y colisiones | `api/v1/auth/auth.php` | 106 |
| 4 | **Credenciales en Git** - `conexion-produccion/database.php` con user root commitado al repo | `conexion-produccion/database.php` | - |
| 5 | **SQL Injection masivo en LOGICA** - Cientos de archivos `sql_*.php` con interpolación directa | `*/LOGICA/sql_*.php` | Varios |

### ALTOS (8)

| # | Hallazgo | Archivo |
|---|----------|---------|
| 6 | Sin control de roles/RBAC - cualquier usuario autenticado accede a endpoints admin | `api/index.php:81` |
| 7 | Bypass auth via parámetro `Bdd` - conexión a DB arbitraria desde cliente | `api/v1/facturacion/comprobantes.php:10` |
| 8 | phpinfo.php expone config del servidor en producción | `phpinfo.php` |
| 9 | Excepciones con mensajes SQL y paths filtrados al cliente | `api/v1/auth/auth.php:67`, `api/index.php:15` |
| 10 | Conexiones DB controladas por cliente - admin puede reescribir `database.php` | `api/v1/admin/conexion.php:101` |
| 11 | Path traversal en descarga de archivos SRI Scraper | `classes/SriScraperManager.php:150` |
| 12 | SQL Injection en motor de flujo (flujo/) | `flujo/LOGICA/wf_manager_log.php` (50+ instancias) |
| 13 | `taskkill` de todos los procesos Python como DoS potencial | `classes/SriScraperManager.php:104` |

### MEDIOS (6)

| # | Hallazgo | Archivo |
|---|----------|---------|
| 14 | CORS `*` - permite requests desde cualquier dominio | `api/index.php:41` |
| 15 | Sin protección CSRF en endpoints API | Todos los endpoints |
| 16 | XSS en Swagger UI (reflejo de URL en script) | `api/index.php:225` |
| 17 | Debug mode habilitado en Slim Framework | `api/index.php:37` |
| 18 | Password default `123456` en creación de usuarios | `api/v1/admin/soporte.php:97` |
| 19 | Sesiones manipulables desde input del cliente | `api/v1/facturacion/comprobantes.php:418` |

### BAJOS (2)

| # | Hallazgo | Archivo |
|---|----------|---------|
| 20 | `verconsulta()` output HTML sin escape | `DATA/MysqlDatos.php:691` |
| 21 | Nombre de BD hardcodeado como fallback | `api/v1/facturacion/comprobantes.php:65` |

---

## 3. Hallazgos de Calidad de Código

### CRÍTICOS

| # | Hallazgo | Archivo(s) |
|---|----------|------------|
| 1 | **Duplicación masiva** - `MarcaClass`, `ProductoClass` y `CategoriaClass` son prácticamente idénticos (copy-paste del 100%) | `classes/Marca.php`, `classes/Producto.php`, `classes/Categoria.php` |
| 2 | **Sin namespaces** - Todas las clases están en el namespace global | `classes/*.php` |
| 3 | **Sin type hints** - Ningún método tiene tipado estricto de parámetros ni retorno | Todas las clases |

### ALTOS

| # | Hallazgo | Detalle |
|---|----------|---------|
| 4 | Comparaciones sueltas `==` en lugar de `===` en toda la base de datos | `classes/*.php`, `api/**/*.php` |
| 5 | Código comentado innecesario en producción | `classes/Producto.php:38,41`, `classes/Marca.php:38` |
| 6 | Variables `$response` vs `$responce` (typo inconsistente) | `classes/Cliente.php:57,68` |
| 7 | Sin manejo de errores centralizado - echo+exit mezclado con return | `api/**/*.php` |
| 8 | Variables no usadas (`$data` en `getProveedores`) | `classes/Proveedor.php:23` |

### MEDIOS

| # | Hallazgo | Detalle |
|---|----------|---------|
| 9 | Naming inconsistente: `snake_case` y `camelCase` mezclados | En todo el proyecto |
| 10 | Archivos `.bak` y `.old` en producción | `index.php.bak`, `ventas_old-13-01-25.php` |
| 11 | Sin documentación PHPDoc en la mayoría de métodos | `classes/*.php` |
| 12 | `utf8_encode()` deprecado en PHP 8.2+ | Múltiples archivos |

---

## 4. Recomendaciones Prioritarias

### Inmediatas (Esta semana)
1. **Reemplazar token base64** por JWT con HMAC-signed
2. **Migrar de MD5** a `password_hash()/password_verify()` con force rehash
3. **Fix SQL injection en auth.php** usando prepared statements
4. **Eliminar phpinfo.php** de producción
5. **Remover `conexion-produccion/`** del Git, agregar a `.gitignore`, rotar credenciales

### Corto plazo (1-2 semanas)
6. Agregar RBAC a endpoints admin
7. Validar/restringir el parámetro `Bdd` contra allowlist
8. Configurar CORS restringido a dominios específicos
9. Fix path traversal en SRI Scraper
10. Agregar CSRF tokens a endpoints state-changing

### Mediano plazo (1 mes)
11. Refactorizar `MarcaClass`, `ProductoClass`, `CategoriaClass` en una sola clase genérica
12. Implementar namespaces PSR-4
13. Migrar SQL legacy (`operacionobBD()`) a prepared statements
14. Agregar type hints a todas las clases
15. Implementar tests de integración con base de datos de prueba

---

## 5. Archivos Creados

| Archivo | Propósito |
|---------|-----------|
| `phpunit.xml` | Configuración PHPUnit |
| `tests/classes/ApiResponseTest.php` | Tests para respuesta API |
| `tests/classes/FacturacionElectronicaTest.php` | Tests para facturación |
| `tests/classes/DataAPITest.php` | Tests para capa de datos |
| `tests/classes/TareaClassTest.php` | Tests para gestión de tareas |
| `docs/AUDITORIA_CODIGO.md` | Este reporte |
