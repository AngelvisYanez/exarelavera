# Auditoría y Monitoreo en EXA

## Objetivo

El módulo de auditoría registra actividad de usuarios sobre operaciones de base de
datos (`INSERT`, `UPDATE`, `DELETE`) y permite consultarla desde el monitor, controlar
sesiones en tiempo real y generar reportes de gestión con envío por PDF, correo y WhatsApp.

La captura se hace de forma centralizada desde `DATA/MysqlDatos.php` y la persistencia se
escribe en la base `auditoria` (tablas `logs`, `cfg_monitoreo`, `sesion`).

---

## 1) Requisitos para que funcione

- Tener accesible la base `auditoria`.
- Tener accesible la base distribuida de la empresa (`Ses_Dat_Dis`) y `exa`
  (catálogo de procesos/organización).
- Incluir `Librerias/config.php/register_globals.php` en el flujo normal del sistema
  (carga `AuditQueue`).
- Haber registrado el menú del módulo una sola vez por entorno:
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
auditoria/
├── FRONT/           Pantallas ejecutables (configuración, monitoreo, sesiones, dashboards)
├── VALIDACIONES/    JavaScript de cada pantalla
├── RECURSOS/        Estilos visuales compartidos (aud_monitoreo_ui_1.0.css)
├── LOGICA/          Lógica de negocio, SQL, cola de captura y servicios AJAX
├── TEST/            Suite de pruebas y scripts de verificación
├── docs/            Esta documentación
└── LOGICA/reedme.txt  Nota legacy del esquema de captura anterior (grabarAuditoria)
```

### Pantallas disponibles

| Pantalla | Archivo | Descripción |
| --- | --- | --- |
| Configuración de monitoreo | `FRONT/aud_adm_config_monitoreo_1.0.php` | Reglas por módulo/directorio/proceso por empresa |
| Monitor de actividades | `FRONT/aud_con_monitoreo_1.0.php` | Consulta del historial de actividades y exportar |
| Actividad de usuarios y sesiones | `FRONT/aud_con_actividad_usuarios_1.0.php` | Sesiones en vivo, KPIs y cierre forzado |
| Dashboard de monitoreo | `FRONT/aud_con_dashboard_monitoreo_1.0.php` | Tablero ejecutivo con PDF/WhatsApp/correo |
| Dashboard comparativo | `FRONT/aud_con_dashboard_comparativo_1.0.php` | Comparativa entre dos períodos con PDF/WhatsApp/correo |

---

## 4) Configuración funcional (por empresa)

Pantalla:

- `auditoria/FRONT/aud_adm_config_monitoreo_1.0.php`

Lógica:

- `auditoria/LOGICA/aud_log_config_monitoreo.php`
- `auditoria/LOGICA/aud_sql_config_monitoreo.php`

Tabla:

- `auditoria.cfg_monitoreo` (única por `Emp_Cod`, `Org_Cod`, `Pcs_Cod`)

### Cómo opera el filtro

- Si la empresa tiene reglas activas en `cfg_monitoreo`, se registra solo:
  - módulo completo (`Pcs_Cod=0` sobre `Org_Cod` de módulo),
  - directorio completo (`Pcs_Cod=0` sobre `Org_Cod` de directorio),
  - o proceso puntual (`Pcs_Cod>0`).
- Si no hay reglas para la empresa, **no** se registra actividad: la cobertura total se
  logra marcando los módulos/directorios/procesos en Configuración de monitoreo.

### Experiencia de la pantalla

- **Árbol jerárquico** módulo → directorio → proceso, con interruptores (switch) que
  reflejan estados completos, parciales (ámbar) o apagados.
- **Vista Lista y Grid**: el conmutador `Lista`/`Grid` alterna entre el árbol colapsable
  y una tabla plana por módulo. Al cambiar de vista se conserva la selección sin guardar.
- **Contadores por módulo** `auditados/total` (verde si completo, ámbar si parcial) y un
  resumen global en la barra de estado (`N regla(s), X/Y procesos auditados`).
- **Filtros de validación**:
  - *Filtrar por Rol* y *Filtrar por Usuario* (los usuarios se agrupan por persona; el
    combo muestra `(N cuentas)` y sus roles). Los procesos autorizados del rol/usuario
    quedan resaltados con el distintivo **Asignado**, sin ocultar el resto.
  - *Estado de Auditoría* (todos / solo marcados / solo sin marcar).
  - *Modo Estricto*: oculta lo que no pertenece al rol/usuario seleccionado.
  - Botones **Marcar asignados** / **Desmarcar asignados**: marcan solo los procesos
    autorizados del filtro actual.
  - Búsqueda libre por módulo, directorio o proceso.
- **Cambios sin guardar**: al modificar la selección aparece el aviso
  *Cambios sin guardar* y el botón **Guardar** pasa a ámbar; también hay confirmación al
  salir de la página. El aviso se limpia al guardar o recargar.
- **Guardado**: envía reglas compactadas (módulo o directorio completo como una sola
  regla `Pcs_Cod=0`) y deja traza en `auditoria.logs` (campo `Cfg_Reglas`).
- **Permisos**: solo el *Administrador de Sistemas* edita. Un rol delegado ve la pantalla
  en **modo solo lectura** con el árbol filtrado por sus permisos (`perfiorgan`).

---

## 5) Flujo técnico resumido

1. El sistema ejecuta SQL por `MysqlDatos::consulta()` o `MysqlDatos::grabarv_registros()`.
2. `AuditQueue::captureBefore()` guarda contexto previo para `UPDATE/DELETE` (valor anterior).
3. `AuditQueue::capture()` encola evento con usuario, empresa, sucursal, proceso y datos.
4. `AuditQueue::flush(false)` persiste en `auditoria.logs` en requests AJAX (sin romper JSON).
5. En shutdown también existe persistencia de respaldo.

La solicitud consulta las reglas activas solo al persistir: sin configuración la cola
descarta los eventos (`hasCfgRules()`). Archivo clave:

- `auditoria/LOGICA/aud_log_queue.php`

---

## 6) Uso del monitor de actividades

Pantalla:

- `auditoria/FRONT/aud_con_monitoreo_1.0.php`

Qué permite:

- filtrar por fechas, evento, módulo, directorio, proceso, usuario y sucursal;
- abrir detalle por registro (con valor anterior en `UPDATE`);
- exportar CSV y generar Excel con los filtros actuales.

Interpretación de lenguaje natural (detalle/modal):

- `auditoria/LOGICA/aud_log_interpretar.php`

---

## 7) Actividad de usuarios y sesiones (tiempo real)

Pantalla:

- `auditoria/FRONT/aud_con_actividad_usuarios_1.0.php`

Servicio AJAX:

- `auditoria/LOGICA/aud_log_actividad_sesion.php` (acciones `consultar_actividad`,
  `ping`, `cerrar_forzada`, `cerrar_por_inactividad`, `registrar_inicio`)
- `auditoria/LOGICA/aud_sql_actividad_sesion.php`

Tabla:

- `auditoria.sesion` (incluye `ses_ip`, `ses_ubi`, `ses_nav`, `ses_ult_act`,
  `ses_min_uso`, `ses_est`, `ses_token`)

Qué permite:

- ver usuarios **en línea** en vivo (indicador *En Vivo*, auto-refresco 15/30/60 s o
  desactivado, por defecto 30 s);
- KPIs: usuarios En Línea, Ausentes, Sesiones Hoy y Tiempo Promedio de Uso;
- semáforo de estado: `en_linea` (< 5 min), `ausente` (5–15 min), `inactiva`/cerrada,
  más `forzada` al ser expulsado;
- alerta en tiempo real de **acceso múltiple** (usuario con más de una sesión activa);
- panel lateral *Mayor Tiempo de Uso*;
- filtros por estado, rol, texto (usuario/IP) y período (presets Hoy/Ayer/1 semana/1 mes/3 meses);
- **cierre forzado** de sesión (solo Administrador de Sistemas; no puede desconectar su
  propia sesión ni cerrar sesiones de otra empresa).

Notas de seguridad:

- El cierre automático por inactividad y la geolocalización por IP están
  **desactivados por defecto en producción**; las sesiones inactivas se conservan.
- Se registra `Ses_Token` para el control heartbeat de cada sesión.

---

## 8) Dashboard comparativo

Pantalla:

- `auditoria/FRONT/aud_con_dashboard_comparativo_1.0.php`

Lógica y servicio:

- `auditoria/LOGICA/aud_log_dashboard.php` (acciones `consultar`, `exportar_pdf`,
  `enviar_correo`, `enviar_whatsapp`)
- `auditoria/LOGICA/aud_rep_comparativa_pdf.php`

Qué permite:

- comparar dos períodos (base vs comparado) con KPIs de variación;
- gráficos por módulo, por horas (24 franjas) y por usuarios;
- observaciones automáticas sobre el comportamiento del período;
- exportar **PDF** del comparativo;
- enviar por **correo** y por **WhatsApp** (vía API ERP y apertura directa en WhatsApp Web).

---

## 9) Dashboard de monitoreo

Pantalla:

- `auditoria/FRONT/aud_con_dashboard_monitoreo_1.0.php`

Lógica y servicio:

- `auditoria/LOGICA/aud_log_dashboard_monitoreo.php` (acciones `consultar`,
  `exportar_pdf`, `enviar_correo`, `enviar_whatsapp`)
- `auditoria/LOGICA/aud_rep_monitoreo_pdf.php`

Qué permite:

- resumen ejecutivo del período (total de movimientos);
- desglose por módulo, plantas y usuarios más activos;
- distribución horaria (24 franjas) y tendencia diaria;
- exportar **PDF** del reporte;
- enviar por **correo** (PHPMailer) y por **WhatsApp** (API + Web).

---

## 10) Validación y pruebas

### 10.1 Verificar coherencia de captura con configuración

```bash
php auditoria/TEST/aud_verify_captura.php
```

Resultado esperado:

- `RESULTADO: CAPTURA COHERENTE CON LA CONFIGURACION`

### 10.2 Suite completa de pruebas

```bash
php -d date.timezone=America/Guayaquil auditoria/TEST/aud_run_tests.php
```

Cubre: cola de captura, monitor (proceso–módulo), configuración, actividad de usuarios,
dashboards (comparativo y monitoreo con PDF/WhatsApp) y **concurrencia** (8 usuarios en
paralelo con procesos reales registrados). Resultado esperado:

- `TODAS LAS PRUEBAS OK`

### 10.3 Registro del menú

```bash
php auditoria/TEST/aud_register_menu.php [base_de_datos]
```

---

## 11) Solución de problemas comunes

### No se registra nada

- Confirmar `AUDIT_ENABLED=true`.
- Confirmar que el usuario tiene `Ses_Emp_Cod` válido.
- Verificar conexión a base `auditoria`.
- Verificar que la empresa tenga reglas en `cfg_monitoreo` (sin reglas no se registra).

### Se registra, pero no aparece en el monitor

- Revisar filtros de fecha/empresa/sucursal en pantalla.
- Verificar que `Emp_Cod` del log sea el de la sesión.
- Revisar reglas en `cfg_monitoreo` de esa empresa.

### Se registra fuera de lo esperado

- Si hay reglas en `cfg_monitoreo`, revisar selección de módulo/directorio/proceso.
- Si no hay reglas, no se registra actividad: marcar los módulos en Configuración de monitoreo.

### No aparecen sesiones en Actividad de Usuarios

- Confirmar que la sesión se registró al iniciar (`aud_ses_registrar_inicio`) o que el
  heartbeat `ping` está activo.
- Verificar la empresa/sucursal del filtro y que `auditoria.sesion` tenga columnas nuevas.
- Revisar que el cierre por inactividad siga desactivado si no se desea expulsar usuarios.

### El PDF/WhatsApp/correo del dashboard no se genera

- Validar permisos de escritura en el directorio temporal de FPDF.
- Revisar la configuración SMTP/PHPMailer y el número de teléfono (normalización WA).

### Modal muestra códigos en vez de nombres

- Validar joins de empresa/usuario/proceso en `aud_sql_monitoreo.php`.
- Validar funciones de interpretación en `aud_log_interpretar.php`.

---

## 12) Recomendación operativa

- Mantener reglas por módulo/directorio para reducir ruido.
- Usar proceso puntual solo cuando se necesite alta precisión.
- Revisar periódicamente el volumen de `auditoria.logs` y ajustar `AUDIT_RETENTION_DAYS`.
- Supervisar con Actividad de Usuarios los accesos múltiples (posibles credenciales
  compartidas) y aplicar cierre forzado solo en casos confirmados.