# Auditoría y Monitoreo en EXA

## Objetivo

El módulo de auditoría registra actividad de usuarios sobre operaciones de base de
datos (`INSERT`, `UPDATE`, `DELETE`) y permite consultarla desde el monitor, controlar
sesiones en tiempo real y generar reportes de gestión con envío por PDF, correo y WhatsApp.

La captura se hace de forma centralizada desde `DATA/MysqlDatos.php` y la persistencia se
escribe en la base `auditoria` (tablas `logs`, `cfg_monitoreo`, `sesion`, `cfg_acceso`,
`cfg_notificaciones`, `cfg_correo_usuario`).

Documentación viva del módulo: este archivo en `docs/USO_AUDITORIA.md` (carpeta raíz del
proyecto). Los PDF de apoyo (`Documentacion_Modulo_Auditoria_Produccion.pdf`,
`flujo-auditoria.pdf`) también viven en `docs/` cuando estén disponibles en el entorno local.

---

## 1) Requisitos para que funcione

- Tener accesible la base `auditoria`.
- Tener accesible la base distribuida de la empresa (`Ses_Dat_Dis`) y `exa`
  (catálogo de procesos/organización).
- Incluir `Librerias/config.php` / `register_globals.php` en el flujo normal del sistema
  (carga `AuditQueue`).
- Haber registrado el menú del módulo una sola vez por entorno (script local, ver
  sección 12):
  `php auditoria/TEST/aud_register_menu.php [base_de_datos]`.

---

## 2) Activación por variables de entorno

Configurar en `.env` (o validar valores en `.env.example`):

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
  Lista de tablas por defecto (legacy). Ya **no** se usa como fallback: sin reglas en
  `cfg_monitoreo` no se registra actividad.

- `AUDIT_MAX_QUEUE`
  Tope de eventos en memoria por request (rango efectivo 20..500).

- `AUDIT_RETENTION_DAYS`
  Días de retención en `auditoria.logs` (mínimo 30).

---

## 3) Estructura del módulo

```
exa-relavera-desarrollo/
??? docs/
?   ??? USO_AUDITORIA.md     Documentación de uso y función (este archivo)
??? auditoria/
    ??? FRONT/               Pantallas ejecutables
    ??? VALIDACIONES/        JavaScript de cada pantalla
    ??? RECURSOS/            Estilos compartidos (aud_monitoreo_ui_1.0.css)
    ??? LOGICA/              Negocio, SQL, cola de captura y servicios AJAX
    ??? TEST/                Suite local de pruebas (ignorada por git; no se versiona)
```

### Pantallas disponibles

| Pantalla | Archivo | Descripción |
| --- | --- | --- |
| Configuración de monitoreo | `FRONT/aud_adm_config_monitoreo_1.0.php` | Tres pestañas: módulos/procesos, clave de acceso al directorio y notificaciones por correo |
| Monitor de actividades | `FRONT/aud_con_monitoreo_1.0.php` | Historial, resumen automatizado del filtro y exportar |
| Actividad de usuarios y sesiones | `FRONT/aud_con_actividad_usuarios_1.0.php` | Sesiones en vivo, KPIs, estadística por usuario y cierre forzado |
| Dashboard de monitoreo (Panel Estadístico) | `FRONT/aud_con_dashboard_monitoreo_1.0.php` | Tablero con el mismo filtro que Monitoreo y PDF/WhatsApp/correo |
| Dashboard comparativo | `FRONT/aud_con_dashboard_comparativo_1.0.php` | Comparativa entre dos períodos con PDF/WhatsApp/correo |

Todas las pantallas de `auditoria/FRONT/*` y sus servicios AJAX en `auditoria/LOGICA/*`
quedan protegidos por la **clave de acceso al directorio** (sección 5) cuando la
empresa la tiene configurada.

---

## 4) Configuración funcional (por empresa)

Pantalla:

- `auditoria/FRONT/aud_adm_config_monitoreo_1.0.php`

Lógica:

- `auditoria/LOGICA/aud_log_config_monitoreo.php`
- `auditoria/LOGICA/aud_sql_config_monitoreo.php`
- Clave: `auditoria/LOGICA/aud_log_acceso_directorio.php`
- Notificaciones: `auditoria/LOGICA/aud_log_notificaciones.php`

Tablas:

- `auditoria.cfg_monitoreo` (única por `Emp_Cod`, `Org_Cod`, `Pcs_Cod`)
- `auditoria.cfg_acceso`
- `auditoria.cfg_notificaciones` / `auditoria.cfg_correo_usuario`

### Organización de la pantalla (pestañas)

1. **Módulos y Procesos** — qué se captura.
2. **Clave de Acceso** — PIN compartido por empresa para entrar al directorio.
3. **Notificaciones por Correo** — alertas en Actualizar/Eliminar + correo por usuario.

### Cómo opera el filtro de captura

- Si la empresa tiene reglas activas en `cfg_monitoreo`, se registra solo:
  - módulo completo (`Pcs_Cod=0` sobre `Org_Cod` de módulo),
  - directorio completo (`Pcs_Cod=0` sobre `Org_Cod` de directorio),
  - o proceso puntual (`Pcs_Cod>0`).
- Si no hay reglas para la empresa, **no** se registra actividad: la cobertura total se
  logra marcando los módulos/directorios/procesos en Configuración de monitoreo.

### Experiencia — pestaña Módulos y Procesos

- **Árbol jerárquico** módulo ? directorio ? proceso, con interruptores (switch) que
  reflejan estados completos, parciales (ámbar) o apagados.
- **Vista Lista y Grid**: el conmutador `Lista`/`Grid` alterna entre el árbol colapsable
  y una vista en tarjetas/grilla. Al cambiar de vista se conserva la selección sin guardar.
- **Filtro rápido por Usuario** en la barra principal (usuarios agrupados por persona).
- **Contadores por módulo** `auditados/total` y resumen global en la barra de estado.
- **Panel Filtrar por rol / usuario**:
  - *Filtrar por Rol* y *Estado de Auditoría* (todos / solo marcados / solo sin marcar).
  - *Modo Estricto*: oculta lo que no pertenece al rol/usuario seleccionado.
  - Botones **Marcar asignados** / **Desmarcar asignados**.
  - Búsqueda libre por módulo, directorio o proceso.
- **Cambios sin guardar**: aviso y botón **Guardar** en ámbar; confirmación al salir.
- **Guardado**: compacta reglas (módulo/directorio completo como `Pcs_Cod=0`) y deja
  traza en `auditoria.logs` (`Cfg_Reglas`).
- **Permisos**: solo el *Administrador de Sistemas* edita. Un rol delegado ve la pantalla
  en **modo solo lectura** filtrado por `perfiorgan`.

---

## 5) Clave de acceso al directorio de auditoría

Pestaña **Clave de Acceso** en Configuración de monitoreo.

Lógica: `auditoria/LOGICA/aud_log_acceso_directorio.php`  
Tabla: `auditoria.cfg_acceso` (`Emp_Cod` único, `Acc_Clave_Hash` con `password_hash()`)

### Cómo opera

- Clave **compartida por empresa** (no por usuario): se pide **una vez por sesión de
  navegador** al entrar a `auditoria/FRONT/*` o a AJAX independientes de `LOGICA/*`.
- Sin clave configurada, el acceso queda abierto (no se bloquea el módulo).
- Punto de entrada: `aud_acceso_directorio_gate($empCod)`. Si falta validación, muestra
  pantalla de PIN y hace `exit()`; si ya está OK
  (`$_SESSION['aud_acceso_ok_<Emp_Cod>']`), continúa.
- Tras 3 intentos fallidos hay demora anti fuerza bruta.
- Solo el *Administrador de Sistemas* define, cambia o desactiva la clave (mínimo 4
  caracteres).

---

## 6) Notificaciones por correo (Actualizar / Eliminar)

Pestaña **Notificaciones por Correo** en Configuración de monitoreo.

Lógica: `auditoria/LOGICA/aud_log_notificaciones.php`  
Tablas: `cfg_notificaciones`, `cfg_correo_usuario`

### Experiencia de la pestaña

- Hero con alcance (modificación / eliminación).
- Formulario en **4 pasos**: alcance (módulo/directorio/proceso), eventos (chips
  Actualizar/Eliminar), usuarios destino, correos adicionales.
- Lista de reglas con badges de evento y chips de correo/usuario; edición resalta la
  fila y el formulario.
- Bloque **Correo por usuario** para registrar el email en `cfg_correo_usuario` antes
  de poder elegirlo como destinatario.
- Solo el *Administrador de Sistemas* crea/edita reglas; el resto ve la lista en
  lectura.

### Cómo opera el envío

- Cada regla define alcance, eventos (`U` y/o `D`) y destinatarios (correos libres y/o
  usuarios con correo registrado).
- En tiempo real, tras persistir un log `U`/`D`, `AuditQueue::persistOne()` llama a
  `aud_notif_procesar_evento()` (misma semántica de coincidencia que `cfg_monitoreo`)
  y envía HTML con PHPMailer + interpretación de detalle (`aud_log_interpretar.php`).
- **No se notifican Insertar (`I`)**.
- Fallos de notificación se silencian: nunca interrumpen la captura.

---

## 7) Flujo técnico resumido de captura

1. El sistema ejecuta SQL por `MysqlDatos::consulta()` o `MysqlDatos::grabarv_registros()`.
2. `AuditQueue::captureBefore()` guarda contexto previo para `UPDATE/DELETE`.
3. `AuditQueue::capture()` encola evento (usuario, empresa, sucursal, proceso, datos).
4. `AuditQueue::flush(false)` persiste en `auditoria.logs` en requests AJAX.
5. Tras persistir un evento `U`/`D`, se evalúan notificaciones (sección 6).
6. En shutdown también hay persistencia de respaldo.

Sin reglas en `cfg_monitoreo` la cola descarta eventos (`hasCfgRules()`).  
Archivo clave: `auditoria/LOGICA/aud_log_queue.php`.

---

## 8) Uso del monitor de actividades

Pantalla: `auditoria/FRONT/aud_con_monitoreo_1.0.php`

Qué permite:

- filtrar por fechas, evento, módulo, directorio, proceso, usuario, sucursal y planta;
- detalle por registro (valor anterior en `UPDATE`, historial del campo `movimiento` en
  Actualizar);
- **resumen automatizado** del filtro (totales por evento, top usuarios/procesos,
  observaciones) sobre todo el historial filtrado, no solo la página visible;
- aviso de **"datos desde"** (inicio de captura; por defecto desde **10-sep-2026** si
  aplica el banner de registro);
- exportar CSV / Excel con los filtros actuales.

Interpretación de detalle: `auditoria/LOGICA/aud_log_interpretar.php`.

---

## 9) Actividad de usuarios y sesiones (tiempo real)

Pantalla: `auditoria/FRONT/aud_con_actividad_usuarios_1.0.php`  
AJAX: `aud_log_actividad_sesion.php` / `aud_sql_actividad_sesion.php`  
Tabla: `auditoria.sesion`

Qué permite:

- usuarios **en línea** en vivo (auto-refresco 15/30/60 s; por defecto 30 s);
- KPIs: En Línea, Ausentes, Sesiones Hoy, Tiempo Promedio de Uso;
- semáforo: `en_linea` (&lt; 5 min), `ausente` (5–14 min), `inactiva`/cerrada, más
  `forzada`;
- alerta de **acceso múltiple**;
- panel *Mayor Tiempo de Uso*;
- **Estadística de sesiones por usuario** (`estadistica_usuarios`): iniciadas,
  cerradas, por inactividad, forzadas, promedio y total;
- filtros por estado, rol, texto y período;
- **cierre forzado** (solo Administrador de Sistemas; no la propia sesión ni otra
  empresa).

Notas:

- Cierre automático por inactividad y geolocalización por IP **desactivados por
  defecto** en producción.
- Se usa `Ses_Token` para el heartbeat.

---

## 10) Dashboard comparativo

Pantalla: `auditoria/FRONT/aud_con_dashboard_comparativo_1.0.php`  
Lógica: `aud_log_dashboard.php`, `aud_rep_comparativa_pdf.php`

- Compara dos períodos con KPIs de variación.
- Gráficos por módulo, horas (24 franjas) y usuarios.
- Observaciones automáticas.
- Exportar **PDF**; enviar por **correo** y **WhatsApp**.

---

## 11) Dashboard de monitoreo (Panel Estadístico)

Pantalla: `auditoria/FRONT/aud_con_dashboard_monitoreo_1.0.php`  
Lógica: `aud_log_dashboard_monitoreo.php`, `aud_sql_dashboard.php`,
`aud_rep_monitoreo_pdf.php`

- Resumen ejecutivo, desglose por módulo/plantas/usuarios, distribución horaria y
  tendencia diaria.
- **Mismo filtro que Monitoreo** (evento, módulo, directorio, proceso, usuario,
  sucursal, planta) aplicado a consulta, PDF, correo y WhatsApp.
- Helpers compartidos: `aud_dash_where_filtro()` / `aud_dash_extra_joins()` en
  `aud_sql_dashboard.php`.

---

## 12) Validación y pruebas (solo local)

La carpeta `auditoria/TEST/` **no se versiona** (está en `.gitignore`). Debe existir
en el entorno de desarrollo para correr verificaciones.

### 12.1 Coherencia de captura

```bash
php auditoria/TEST/aud_verify_captura.php
```

Esperado: `RESULTADO: CAPTURA COHERENTE CON LA CONFIGURACION`

### 12.2 Suite completa

```bash
php -d date.timezone=America/Guayaquil auditoria/TEST/aud_run_tests.php
```

Cubre: cola, monitor, configuración, actividad, dashboards, OAuth de dispositivos,
clave de acceso, notificaciones y concurrencia. Esperado: `TODAS LAS PRUEBAS OK`.

### 12.3 Registro del menú

```bash
php auditoria/TEST/aud_register_menu.php [base_de_datos]
```

---

## 13) Solución de problemas comunes

### No se registra nada

- `AUDIT_ENABLED=true`, `Ses_Emp_Cod` válido, conexión a `auditoria`.
- Empresa con reglas en `cfg_monitoreo` (sin reglas no hay captura).

### Se registra, pero no aparece en el monitor

- Revisar filtros de fecha/empresa/sucursal.
- `Emp_Cod` del log vs sesión y reglas de esa empresa.

### Se registra fuera de lo esperado

- Revisar selección en Configuración de monitoreo.
- Sin reglas no debería haber captura.

### No aparecen sesiones en Actividad de Usuarios

- Confirmar `aud_ses_registrar_inicio` / heartbeat `ping`.
- Empresa/sucursal del filtro y columnas de `auditoria.sesion`.

### El PDF/WhatsApp/correo del dashboard no se genera

- Permisos de escritura temporal de FPDF.
- SMTP/PHPMailer y normalización del teléfono WA.

### Modal muestra códigos en vez de nombres

- Joins en `aud_sql_monitoreo.php` e interpretación en `aud_log_interpretar.php`.

### Pide la clave de acceso en cada pantalla y no debería

- La validación es por sesión de navegador; entre sesiones distintas es normal.
- Para quitar el PIN: Administrador ? Clave de Acceso ? **Desactivar clave**.

### No llegan las notificaciones por correo

- Regla activa en `cfg_notificaciones` que cubra alcance + evento `U`/`D`.
- Destinatario con correo válido (libre o en `cfg_correo_usuario`).
- Los `I` no notifican.
- Revisar SMTP/PHPMailer (mismo transporte que dashboards).

---

## 14) Recomendación operativa

- Preferir reglas por módulo/directorio; proceso puntual solo si hace falta precisión.
- Revisar volumen de `auditoria.logs` y `AUDIT_RETENTION_DAYS`.
- Usar Actividad de Usuarios para detectar accesos múltiples; cierre forzado solo en
  casos confirmados.
- En empresas sensibles: clave de directorio + notificaciones en módulos críticos
  (contabilidad, tesorería, nómina).
- Mantener la suite en `auditoria/TEST/` solo en entornos de desarrollo; no subirla
  al repositorio.
