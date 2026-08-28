# Integración ERP Locator — Credenciales y Ejemplos de Consumo API

Documentación técnica con credenciales emitidas y ejemplos de código listos para su uso en la sincronización diaria del **directorio de contactos autorizados** entre **EXA** y **ERP Locator**.

---

## 1. Credenciales de Producción

| Parámetro | Valor |
|---|---|
| **Base URL** | `https://exa.exacontable.com/api` |
| **Endpoint** | `GET /v1/contactos` |
| **URL Completa** | `https://exa.exacontable.com/api/v1/contactos` |
| **Método** | `GET` |
| **Tipo de Autenticación** | `Bearer Token` (Header `Authorization`) |
| **Token de Acceso** | `8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8` |
| **Empresa Asociada** | `ECOPARKMINING S.A.` (Emp_Cod: `620`, Base: `ecoparkmining`) |
| **Permiso Asignado** | `["/v1/contactos"]` (Solo lectura de contactos autorizados) |
| **Cuota Asignada** | `100` consultas por día |
| **Vigencia** | Hasta `2027-08-28` (1 año renovable) |
| **Documentación Interactiva (Swagger)** | `https://exa.exacontable.com/api/v1/docs` |

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
- **`activo`** (`boolean`): `true` indica contacto vigente y autorizado para notificaciones; `false` indica dado de baja.
- **`cargo`** (`string`): Rol del contacto (Administrador(a) de planta, Responsable ambiental, etc.).
- **`area`** (`string`): Planta de beneficio o área operativa asignada.
- **`empresa`** (`string`): Identificador del cliente/empresa (`ecoparkmining`).

---

## 4. Ejemplos de Implementación en Código

### 4.1. cURL (Bash / Terminal)

```bash
curl -X GET "https://exa.exacontable.com/api/v1/contactos?cliente=ecoparkmining" \
  -H "Authorization: Bearer 8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8" \
  -H "Accept: application/json"
```

---

### 4.2. JavaScript / TypeScript (Fetch API / Node.js)

```javascript
const API_URL = 'https://exa.exacontable.com/api/v1/contactos?cliente=ecoparkmining';
const API_TOKEN = '8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8';

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
import requests

url = "https://exa.exacontable.com/api/v1/contactos"
params = {
    "cliente": "ecoparkmining",
    "perPage": 500
}
headers = {
    "Authorization": "Bearer 8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8",
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
$token = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";

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
        string token = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";

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
