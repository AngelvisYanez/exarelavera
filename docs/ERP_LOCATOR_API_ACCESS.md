# Integración ERP Locator — Guía de Integración y Ejemplos de Consumo API

Documentación técnica con ejemplos de código para la sincronización diaria del **directorio de contactos autorizados** entre **EXA** y **ERP Locator**.

---

## 1. Configuración de Conexión

| Parámetro | Valor |
|---|---|
| **Base URL** | `https://exa.exacontable.com/api` |
| **Endpoint** | `GET /v1/contactos` |
| **URL Completa** | `https://exa.exacontable.com/api/v1/contactos` |
| **Método** | `GET` |
| **Tipo de Autenticación** | `Bearer Token` (Header `Authorization`) |
| **Formato del Header** | `Authorization: Bearer <TOKEN_BEARER_ASIGNADO>` |
| **Empresa Asociada** | `ECOPARKMINING S.A.` (Emp_Cod: `620`, Base: `ecoparkmining`) |
| **Permiso Asignado** | `["/v1/contactos"]` (Solo lectura del directorio de contactos) |
| **Cuota Asignada** | `100` consultas por día |
| **Documentación Interactiva (Swagger)** | `https://exa.exacontable.com/api/v1/docs` |

> **Nota de Seguridad:** El token de acceso emitido debe almacenarse de forma segura en variables de entorno del servidor de ERP Locator (`EXA_API_TOKEN`) y nunca exponerse en repositorios públicos ni en el frontend.

---

## 2. Parámetros de Consulta (Query String)

Todos los parámetros son opcionales:

| Parámetro | Tipo | Por Defecto | Descripción |
|---|---|---|---|
| `cliente` | `string` | `ecoparkmining` | Filtro por cliente/proyecto. Ej: `?cliente=ecoparkmining` |
| `page` | `int` | `1` | Número de página. |
| `perPage` | `int` | `500` | Cantidad de registros por página (máx. `500`). |

---

## 3. Estructura de la Respuesta (JSON)

### Respuesta Exitosa (`200 OK`)

```json
{
  "success": true,
  "data": [
    {
      "id": "C-0001",
      "nombres": "JUAN DE JESUS",
      "apellidos": "CANDO PACHECO",
      "correo": "jcandop@hotmail.com",
      "celular": "+593991858616",
      "activo": true,
      "cargo": "Administrador(a) de planta",
      "area": "PLANTA DE BENEFICIO OROCONCENT",
      "empresa": "ecoparkmining"
    },
    {
      "id": "C-0002",
      "nombres": "JONATHAN ORLANDO",
      "apellidos": "TORRES MAZA",
      "correo": "ambiente.oroconcent@grupoadmg.com",
      "celular": "+593995793019",
      "activo": true,
      "cargo": "Responsable ambiental de planta",
      "area": "PLANTA DE BENEFICIO OROCONCENT",
      "empresa": "ecoparkmining"
    }
  ],
  "total": 141,
  "page": 1,
  "perPage": 500,
  "pages": 1
}
```

### Campos del Objeto Contacto

- **`id`** (`string`): Identificador único y persistente del contacto (`C-0001`).
- **`nombres`** (`string`): Nombres de la persona registrada.
- **`apellidos`** (`string`): Apellidos de la persona registrada.
- **`correo`** (`string`): Correo electrónico válido.
- **`celular`** (`string`): Número de WhatsApp en formato internacional normalizado (`+5939XXXXXXXX`).
- **`activo`** (`boolean`): `true` indica contacto vigente y autorizado para notificaciones; `false` indica inactivo.
- **`cargo`** (`string`): Rol del contacto (Administrador(a) de planta, Responsable ambiental, etc.).
- **`area`** (`string`): Planta de beneficio o área operativa asignada.
- **`empresa`** (`string`): Identificador del cliente/empresa (`ecoparkmining`).

---

## 4. Ejemplos de Implementación en Código (Método GET)

### 4.1. cURL (Bash / Terminal)

```bash
# Reemplazar <TOKEN_BEARER_ASIGNADO> con el token privado entregado
curl -X GET "https://exa.exacontable.com/api/v1/contactos?cliente=ecoparkmining" \
  -H "Authorization: Bearer <TOKEN_BEARER_ASIGNADO>" \
  -H "Accept: application/json"
```

---

### 4.2. JavaScript / TypeScript (Fetch API / Node.js)

```javascript
const API_URL = 'https://exa.exacontable.com/api/v1/contactos?cliente=ecoparkmining';
const API_TOKEN = process.env.EXA_API_TOKEN || '<TOKEN_BEARER_ASIGNADO>';

async function sincronizarContactos() {
  try {
    const response = await fetch(API_URL, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
    }

    const resultado = await response.json();
    console.log(`Total contactos obtenidos: ${resultado.total}`);
    
    resultado.data.forEach(contacto => {
      console.log(`[${contacto.id}] ${contacto.nombres} ${contacto.apellidos} - ${contacto.celular} (${contacto.cargo})`);
    });

    return resultado.data;
  } catch (error) {
    console.error('Error al sincronizar contactos con EXA:', error);
  }
}

sincronizarContactos();
```

---

### 4.3. Python (requests)

```python
import os
import requests

url = "https://exa.exacontable.com/api/v1/contactos"
api_token = os.getenv("EXA_API_TOKEN", "<TOKEN_BEARER_ASIGNADO>")

params = {
    "cliente": "ecoparkmining",
    "perPage": 500
}
headers = {
    "Authorization": f"Bearer {api_token}",
    "Accept": "application/json"
}

response = requests.get(url, headers=headers, params=params)

if response.status_code == 200:
    payload = response.json()
    contactos = payload.get("data", [])
    print(f"Sincronizados {len(contactos)} contactos de {payload.get('total')} totales.")
    for c in contactos:
        print(f"- {c['nombres']} {c['apellidos']} | Móvil: {c['celular']} | Área: {c['area']}")
else:
    print(f"Error {response.status_code}: {response.text}")
```

---

### 4.4. PHP (cURL nativo)

```php
<?php

$url = "https://exa.exacontable.com/api/v1/contactos?cliente=ecoparkmining";
$token = getenv('EXA_API_TOKEN') ?: '<TOKEN_BEARER_ASIGNADO>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Total contactos: " . $data['total'] . "\n";
    foreach ($data['data'] as $contacto) {
        echo "{$contacto['id']}: {$contacto['nombres']} {$contacto['apellidos']} - {$contacto['celular']}\n";
    }
} else {
    echo "Error HTTP $httpCode: $response\n";
}
```

---

### 4.5. C# / .NET (HttpClient)

```csharp
using System;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Threading.Tasks;

class Program
{
    private static readonly HttpClient client = new HttpClient();

    static async Task Main()
    {
        string url = "https://exa.exacontable.com/api/v1/contactos?cliente=ecoparkmining";
        string token = Environment.GetEnvironmentVariable("EXA_API_TOKEN") ?? "<TOKEN_BEARER_ASIGNADO>";

        client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", token);
        client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

        HttpResponseMessage response = await client.GetAsync(url);
        if (response.IsSuccessStatusCode)
        {
            string jsonString = await response.Content.ReadAsStringAsync();
            Console.WriteLine("Respuesta recibida:");
            Console.WriteLine(jsonString);
        }
        else
        {
            Console.WriteLine($"Error: {response.StatusCode}");
        }
    }
}
```

---

## 5. Códigos de Respuesta HTTP

| Código | Descripción | Acción requerida |
|---|---|---|
| `200 OK` | Consulta exitosa. Devuelve la lista paginada. | Procesar contactos y sincronizar en ERP Locator. |
| `401 Unauthorized` | Token inválido, expirado o faltante. | Verificar el valor del Bearer Token en el Header. |
| `403 Forbidden` | Token sin permisos para acceder al recurso. | El token solo tiene acceso permitido a `/v1/contactos`. |
| `429 Too Many Requests` | Cuota diaria agotada (límite: 100 req/día). | Reintentar en la siguiente ventana de 24 horas. |
| `500 Internal Server Error` | Error de servidor o base de datos. | Notificar al soporte técnico de EXA (`info@sfyla.com`). |
