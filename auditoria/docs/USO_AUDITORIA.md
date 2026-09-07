# Auditoría y Monitoreo en EXA (ofsercont)

## Objetivo

El módulo de auditoría registra la actividad de los usuarios sobre operaciones de base de datos (`INSERT`, `UPDATE`, `DELETE`) y sobre sesiones (inicio, cierre, error de login), y permite consultarla desde el monitor con filtros, detalle interpretable y exportación.

La captura se hace de forma centralizada desde `DATA/MysqlDatos.php` y la persistencia se escribe en la base `auditoria`, tabla `logs`.

---

## 1) Componentes

| Capa | Archivo | Función |
|---|---|---|
| Bootstrap | `Librerias/config.php/register_globals.php` | Carga `AuditQueue` en cada request |
| Captura | `DATA/MysqlDatos.php`, `DATA/GestorErrores.php` | Hooks `captureBefore()` / `capture()` / `flush()` |
| Cola diferida | `auditoria/LOGICA/aud_log_queue.php` | Encola en memoria y persiste tras responder |
| Sesiones | `auditoria/LOGICA/aud_log_auditoria.php` | Login, logout, error de login (`auditoria.sesion`) |
| Monitor | `auditoria/FRONT/aud_con_monitoreo_1.0.php` + `LOGICA/aud_log_monitoreo.php` + `LOGICA/aud_sql_monitoreo.php` | Consulta de actividad |
| Configuración | `auditoria/FRONT/aud_adm_config_monitoreo_1.0.php` + `LOGICA/aud_log_config_monitoreo.php` + `LOGICA/aud_sql_config_monitoreo.php` | Reglas por empresa (`auditoria.cfg_monitoreo`) |
| Interpretación | `auditoria/LOGICA/aud_log_interpretar.php` | Lenguaje natural del detalle, resolución de nombres |
| Validaciones JS | `auditoria/VALIDACIONES/aud_par_monitoreo.js`, `aud_par_config_monitoreo.js` | Grids y formularios |
| BD | `db/auditoria.sql`, `db/auditoria_config_monitoreo.sql`, `db/auditoria_menu.sql`, `db/auditoria_local.sql` | Esquema, seed de menú |

---

## 2) Requisitos para que funcione

- Tener accesible la base `auditoria`.
- Tener accesible la **base maestra** definida en `DB_DATABASE` (por defecto `exa_master`): es donde viven los catálogos `usuarios`, `persona`, `empresas`, `sucursal`, `procesos` y `organizado`. El login ya consulta estos catálogos ahí.
- Incluir `Librerias/config.php/register_globals.php` en el flujo normal del sistema (carga `AuditQueue`).

### Resolución de catálogos: `aud_master_db()`

El módulo NO asume un nombre fijo para la base de catálogos. Todos los JOINs y lookups usan el helper `aud_master_db()` (definido con guard `function_exists` en `aud_sql_monitoreo.php`, `aud_sql_config_monitoreo.php`, `aud_log_queue.php` y `aud_log_auditoria.php`):

```php
function aud_master_db()
{
    if (class_exists('Env')) {
        $db = \Env::get('DB_DATABASE', 'exa_master');
        if (is_string($db) && $db !== '') {
            return preg_replace('/[^a-zA-Z0-9_]/', '', $db);
        }
    }
    return 'exa_master';
}
```

- El nombre se toma de `.env` (`DB_DATABASE`) y se sanitiza; fallback `exa_master`.
- Así el mismo código funciona en instalaciones donde el catálogo está en otra base (p. ej. entornos heredados que usan `exa`).

---

## 3) Activación por variables de entorno

Configurar en `.env` (valores de referencia en `.env.example`):

```env
AUDIT_ENABLED=true
AUDIT_TABLES=comprobantes,asientos,manifiesto,manifiesto_turnos_cab,manifiesto_turnos_det,manifiesto_visitante,manifiesto_evento,ventas,ventas_det
AUDIT_MAX_QUEUE=150
AUDIT_RETENTION_DAYS=180
```

### Significado

- `AUDIT_ENABLED`
  Activa/desactiva la captura global. Si está en `false`, no se registran eventos nuevos.

- `AUDIT_TABLES`
  Fallback por tablas cuando la empresa **no** tiene reglas en `cfg_monitoreo`.
  - Puede usar `*` o `all` para permitir todas.

- `AUDIT_MAX_QUEUE`
  Tope de eventos en memoria por request (rango efectivo 20..500).

- `AUDIT_RETENTION_DAYS`
  Días de retención en `auditoria.logs` (mínimo 30).

---

## 4) Flujo técnico resumido

1. El sistema ejecuta SQL por `MysqlDatos::consulta()` o `MysqlDatos::grabarv_registros()`.
2. `AuditQueue::captureBefore()` guarda contexto previo para `UPDATE/DELETE` (valor anterior).
3. `AuditQueue::capture()` encola el evento con usuario, empresa, sucursal, proceso y datos (captura < 1 ms, no necesita MySQL).
4. `AuditQueue::flush(false)` persiste en `auditoria.logs` después de responder; en requests AJAX no toca buffers ni rompe el JSON.
5. En shutdown existe persistencia de respaldo.
6. Si MySQL no está disponible o la extensión mysqli no está cargada, el flush falla en silencio (sin excepciones hacia el usuario).

Archivo clave de cola: `auditoria/LOGICA/aud_log_queue.php`.

### Filtro al persistir

- Si la empresa tiene reglas activas en `cfg_monitoreo`, se registra solo:
  - módulo completo (`Pcs_Cod=0` sobre `Org_Cod` raíz),
  - directorio completo (`Pcs_Cod=0` sobre `Org_Cod` de directorio),
  - o proceso puntual (`Pcs_Cod>0`),
  - incluyendo cualquier tabla que esos procesos toquen.
- Si no hay reglas para la empresa, aplica el fallback `AUDIT_TABLES`.
- Nunca se audita la propia tabla `auditoria.logs` ni tablas internas/temporales.

---

## 5) Configuración funcional (por empresa)

Pantalla: `Auditoría → Configuración de monitoreo` (`aud_adm_config_monitoreo_1.0.php`).

- Árbol de módulos/directorios/procesos leído de `procesos`+`organizado` de la base maestra.
- Marcar un módulo lo compacta todo (regla de módulo); se puede bajar a directorio o proceso puntual.
- "Marcar todos" genera reglas de módulo para todo el árbol.
- Las reglas se guardan por empresa en `auditoria.cfg_monitoreo`; el banner del monitor muestra si la captura está activa y cuántas reglas hay.

---

## 6) Uso del monitor de actividades

Pantalla: `auditoria/FRONT/aud_con_monitoreo_1.0.php`.

Qué permite:

- filtrar por fechas, evento, módulo, directorio, proceso, usuario y sucursal;
- ver usuario/empresa/sucursal por nombre (JOINs contra la base maestra; si no hay coincidencia muestra "Usuario N" / "Empresa N");
- abrir el detalle de cada registro con descripción en lenguaje natural (`aud_log_interpretar.php`);
- exportar CSV/Excel con los filtros actuales;
- simular actividad a demanda (útil para verificar la captura).

### Auditoría de sesiones

`aud_log_auditoria.php` registra en `auditoria.sesion`:

- inicio de sesión (con proceso `*index.php` de la base maestra),
- cierre de sesión,
- intentos fallidos de login (búsqueda de usuario por cédula/empresa contra la base maestra).

---

## 7) Instalación de base de datos

1. Ejecutar `db/auditoria.sql` (esquema `auditoria`: `logs`, `sesion`, `eventos`, `tablas`, `campos`, `cfg_monitoreo`).
2. Ejecutar `db/auditoria_menu.sql` para registrar las opciones de menú (ajustar códigos de perfil según instalación).
3. Opcional en desarrollo: `db/auditoria_local.sql` y `db/auditoria_config_monitoreo.sql`.

---

## 8) Pruebas

Suite completa (unitarias de cola, monitor, configuración y usuarios simultáneos):

```bash
php auditoria/TEST/aud_run_tests.php
```

Salida esperada: `TODAS LAS PRUEBAS OK`.

Otras herramientas:

```bash
php auditoria/TEST/aud_verify_captura.php     # coherencia captura vs configuracion
php auditoria/TEST/aud_unit_monitoreo.php     # solo monitor
```

Notas:

- Los tests de BD usan datos reservados (`Usu_Cod >= 900001`, `Emp_Cod >= 999001`) y se limpian solos.
- Si el catálogo local no está en `DB_DATABASE` sino en otra base (p. ej. `exa`), el runner lo detecta y apunta `DB_DATABASE` a esa base solo para el proceso de pruebas.
- En Windows con PHP CLI sin extensiones cargadas, apuntar `PHPRC` a un ini temporal con `extension_dir` + `extension=php_mysqli.dll` antes de correr la suite.

---

## 9) Solución de problemas comunes

### No se registra nada

- Confirmar `AUDIT_ENABLED=true`.
- Confirmar que el usuario tiene `Ses_Emp_Cod` válido.
- Verificar conexión a la base `auditoria`.

### Se registra, pero no aparece en el monitor

- Revisar filtros de fecha/empresa/sucursal en pantalla.
- Verificar que `Emp_Cod` del log sea el de la sesión.
- Revisar reglas en `cfg_monitoreo` de esa empresa.

### Se registra fuera de lo esperado

- Si hay reglas en `cfg_monitoreo`, revisar selección de módulo/directorio/proceso.
- Si no hay reglas, revisar `AUDIT_TABLES`.

### El monitor muestra "Usuario 123" / "Empresa 123"

- Ese es el fallback cuando el JOIN contra la base maestra no encuentra el registro.
- Causa típica: el catálogo de esa empresa/sucursal no existe (o está desactualizado) en la base `DB_DATABASE`.
- Verificar que `usuarios`, `persona`, `empresas`, `sucursal` y `procesos` existan y estén poblados en la base maestra; los JOINs están en `aud_logs_joins()` / `aud_logs_select()` de `aud_sql_monitoreo.php` y en `aud_valor_codigo_lookup()` de `aud_log_interpretar.php`.

---

## 10) Recomendación operativa

- Mantener reglas por módulo/directorio para reducir ruido.
- Usar proceso puntual solo cuando se necesite alta precisión.
- Revisar periódicamente el volumen de `auditoria.logs` y ajustar `AUDIT_RETENTION_DAYS`.
