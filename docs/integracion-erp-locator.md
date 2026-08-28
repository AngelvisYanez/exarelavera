# Integración ERP Locator — Directorio de contactos autorizados

Documentación de la integración que permite a **ERP Locator** sincronizar cada noche la lista de **contactos autorizados para recibir notificaciones**, y a sus operadores elegir destinatarios al registrar eventos.

- **Proveedor / contacto técnico:** SFYLA — Soft For You de Latinoamérica
  Complejo de Bodegas La Carlota, Av. Felipe Pezo Campuzano y 3er Pasaje 32, Local 4, Guayaquil
  Tel: **0989664404** · Correo: **info@sfyla.com**
- **Sistema:** EXA (ERP) · API REST

---

## 1. Endpoint (solo lectura)

| Atributo | Valor |
|---|---|
| **URL** | `GET /api/v1/contactos` |
| **Método** | `GET` |
| **Autenticación** | `Authorization: Bearer <token_integracion>` (obligatoria, ver §2) |
| **Formato** | JSON (UTF-8) |
| **Frecuencia** | Una consulta diaria en la madrugada; se admite también re-sincronización manual puntual |

### 1.1 Parámetros (query string, todos opcionales)

| Parámetro | Tipo | Descripción |
|---|---|---|
| `cliente` | string | Filtra por cliente/proyecto. Ej. `cliente=ecoparkmining`. Si se omite, devuelve toda la lista autorizada (volumen manejable; ver §6). |
| `page` | int | Página (por defecto `1`). |
| `perPage` | int | Registros por página (por defecto `500`, máx. `500`). |

### 1.2 Ejemplo de petición

```http
GET /api/v1/contactos?cliente=ecoparkmining
Authorization: Bearer <token_integracion>
```

### 1.3 Ejemplo de respuesta (200)

```json
{
  "success": true,
  "data": [
    {
      "id": "C-0001",
      "nombres": "María",
      "apellidos": "Paz",
      "correo": "mpaz@empresa.com",
      "celular": "+593991234567",
      "activo": true,
      "cargo": "Jefa ambiental",
      "area": "Planta de beneficio",
      "empresa": "ecoparkmining"
    }
  ],
  "total": 1,
  "page": 1,
  "perPage": 500,
  "pages": 1
}
```

### 1.4 Campos del contacto

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | string | Identificador **único y estable** del contacto (no cambia entre consultas). Formato `C-####`. |
| `nombres` / `apellidos` | string | Nombre y apellidos (para personalizar mensajes). |
| `correo` | string | Correo electrónico válido. |
| `celular` | string | Móvil **WhatsApp en formato internacional** `+5939XXXXXXXX`. Si se registra local `09XXXXXXXX`, EXA lo entrega ya normalizado a `+5939XXXXXXXX`. |
| `activo` | boolean | `true` = notificar; `false` = no notificar (baja controlada por EXA). |
| `cargo` | string | Cargo del contacto (para segmentar al elegir destinatarios). |
| `area` | string | Área / planta del contacto. |
| `empresa` | string | Cliente/proyecto al que pertenece (ej. `ecoparkmining`). |

### 1.5 Códigos HTTP

| Código | Significado |
|---|---|
| `200` | Éxito |
| `400` | Petición inválida / no se determinó la base de datos del token |
| `401` | Token ausente, inválido, vencido o cuota agotada |
| `403` | El token no tiene permiso para este recurso |
| `500` | Error interno del servidor |

---

## 2. Autenticación — token exclusivo de la integración

El token de integración **no usa credenciales personales**; es un token técnico emitido por EXA con **permiso únicamente de lectura del directorio de contactos** (`GET /v1/contactos`). Cualquier otro recurso de la API devuelve `403`.

### 2.1 Emisión

EXA genera el token asociado a la empresa de Ecoparkmining con:

- **Permisos:** únicamente `["/v1/contactos"]`.
- **Cuota:** 100 consultas por día (margen amplio para la nocturna + reintentos/re-sincronizaciones manuales).
- **Vigencia inicial:** 365 días a partir de la emisión.

El valor en claro se entrega **una sola vez** a ERP Locator por canal seguro; EXA solo conserva su hash.

Comando de emisión (equipo EXA):

```bash
php scripts/generar_token_erp_locator.php          # dev: Emp_Cod=281 (Bdd servicios)
php scripts/generar_token_erp_locator.php 620 100  # producción: Emp_Cod=620 (Bdd ecoparkmining)
```

También es posible emitirlo desde el panel de administración (Herramientas → Tokens de Acceso a la API).

### 2.2 Uso

```http
GET /api/v1/contactos
Authorization: Bearer <token_integracion>
```

### 2.3 Renovación / caducidad

- **Caducidad:** el token vence en la fecha de expiración configurada (12 meses). Tras vencer, las consultas devuelven `401`.
- **Renovación:** antes del vencimiento, EXA extiende la vigencia (misma empresa, cuota y permiso) o emite uno nuevo y **revoca el anterior**; el nuevo valor se entrega a ERP Locator por canal seguro.
- **Revocación:** ante cualquier incidencia o fuga, EXA revoca el token de inmediato (queda inactivo y deja de funcionar).
- **Pérdida del valor en claro:** el token no es recuperable; se emite uno nuevo y se revoca el anterior.

> Buenas prácticas: no loguear el token, no compartirlo, y rotarlo al menos una vez al año.

---

## 3. Soporte de frecuencia y acuerdos operativos

- **Consultas admitidas:** el servicio soporta sin problema **una consulta diaria** en madrugada y re-sincronizaciones manuales puntuales en horario de oficina. La cuota de 100/día cubre holgadamente ese patrón.
- **Bajas:** los contactos que EXA marque como inactivos salen con `"activo": false` o dejan de aparecer en la respuesta. ERP Locator **desactiva** (no elimina) los destinatarios ausentes para conservar la trazabilidad de notificaciones ya enviadas.
- **Volumen:** la lista inicial del directorio autorizado es del orden de decenas de contactos (≈ 1 responsable por planta activa; ~12 plantas actuales). Es un volumen manejable para sincronización completa diaria; si crece se conserva la paginación (`total`, `pages`).

---

## 4. Datos de referencia

| Elemento | Valor |
|---|---|
| Configuración SMTP de notificaciones | `ecoparkmining.relavera@gmail.com` (para el envío desde EXA) |
| Empresa (prod) | ECOPARKMINING S.A. — Emp_Cod 620, Bdd `ecoparkmining` |
| Empresa (dev/validación) | ECOPARKMINING SA — Emp_Cod 281, Bdd `servicios` |
| Tabla del directorio | `contacto_notif` (por empresa) |