# Auditoría y Monitoreo en EXA

## Objetivo

El módulo de auditoría registra actividad de usuarios sobre operaciones de base de datos (`INSERT`, `UPDATE`, `DELETE`) y permite consultarla desde el monitor.

La captura se hace de forma centralizada desde `DATA/MysqlDatos.php` y la persistencia se escribe en la base `auditoria`, tabla `logs`.

---

## 1) Requisitos para que funcione

- Tener accesible la base `auditoria`.
- Tener accesible la base distribuida de la empresa (`Ses_Dat_Dis`) y `exa` (catálogo de procesos/organización).
- Incluir `Librerias/config.php/register_globals.php` en el flujo normal del sistema (carga `AuditQueue`).

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
  Fallback por tablas cuando la empresa **no** tiene reglas en `cfg_monitoreo`.
  - Puede usar `*` o `all` para permitir todas.

- `AUDIT_MAX_QUEUE`  
  Tope de eventos en memoria por request (rango efectivo 20..500).

- `AUDIT_RETENTION_DAYS`  
  Días de retención en `auditoria.logs` (mínimo 30).

---

## 3) Configuración funcional (por empresa)

Pantalla:

- `auditoria/FRONT/aud_adm_config_monitoreo_1.0.php`

Lógica:

- `auditoria/LOGICA/aud_log_config_monitoreo.php`
- `auditoria/LOGICA/aud_sql_config_monitoreo.php`

Tabla:

- `auditoria.cfg_monitoreo`

### Cómo opera el filtro

- Si la empresa tiene reglas activas en `cfg_monitoreo`, se registra solo:
  - módulo completo (`Pcs_Cod=0` sobre `Org_Cod` de módulo),
  - directorio completo (`Pcs_Cod=0` sobre `Org_Cod` de directorio),
  - o proceso puntual (`Pcs_Cod>0`).
- Si no hay reglas para la empresa, aplica fallback de `AUDIT_TABLES`.

---

## 4) Flujo técnico resumido

1. El sistema ejecuta SQL por `MysqlDatos::consulta()` o `MysqlDatos::grabarv_registros()`.
2. `AuditQueue::captureBefore()` guarda contexto previo para `UPDATE/DELETE` (valor anterior).
3. `AuditQueue::capture()` encola evento con usuario, empresa, sucursal, proceso y datos.
4. `AuditQueue::flush(false)` persiste en `auditoria.logs` en requests AJAX (sin romper JSON).
5. En shutdown también existe persistencia de respaldo.

Archivo clave de cola:

- `auditoria/LOGICA/aud_log_queue.php`

---

## 5) Uso del monitor de actividades

Pantalla:

- `auditoria/FRONT/aud_con_monitoreo_1.0.php`

Qué permite:

- filtrar por fechas, evento, módulo, directorio, proceso, usuario y sucursal;
- abrir detalle por registro;
- exportar CSV.

Interpretación de lenguaje natural (detalle/modal):

- `auditoria/LOGICA/aud_log_interpretar.php`

---

## 6) Validación rápida (recomendada)

### 6.1 Verificar coherencia de captura con configuración

```bash
php auditoria/TEST/aud_verify_captura.php
```

Resultado esperado:

- `RESULTADO: CAPTURA COHERENTE CON LA CONFIGURACION`

### 6.2 Pruebas unitarias del monitor

```bash
php auditoria/TEST/aud_unit_monitoreo.php
```

---

## 7) Solución de problemas comunes

## No se registra nada

- Confirmar `AUDIT_ENABLED=true`.
- Confirmar que el usuario tiene `Ses_Emp_Cod` válido.
- Verificar conexión a base `auditoria`.

## Se registra, pero no aparece en el monitor

- Revisar filtros de fecha/empresa/sucursal en pantalla.
- Verificar que `Emp_Cod` del log sea el de la sesión.
- Revisar reglas en `cfg_monitoreo` de esa empresa.

## Se registra fuera de lo esperado

- Si hay reglas en `cfg_monitoreo`, revisar selección de módulo/directorio/proceso.
- Si no hay reglas, revisar `AUDIT_TABLES`.

## Modal muestra códigos en vez de nombres

- Validar joins de empresa/usuario/proceso en `aud_sql_monitoreo.php`.
- Validar funciones de interpretación en `aud_log_interpretar.php`.

---

## 8) Recomendación operativa

- Mantener reglas por módulo/directorio para reducir ruido.
- Usar proceso puntual solo cuando se necesite alta precisión.
- Revisar periódicamente volumen de `auditoria.logs` y ajustar `AUDIT_RETENTION_DAYS`.

