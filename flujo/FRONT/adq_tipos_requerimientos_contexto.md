# Contexto del Módulo de Tipos de Requerimientos

**Fecha de generación:** 17 de junio de 2026  
**Módulo principal:** EXA Adquisiciones / Workflow Manager  
**Archivo analizado:** `flujo/FRONT/adq_tipos_requerimientos.php`  

---

## 1. Descripción General

El módulo de **Tipos de Requerimientos** es una pieza fundamental del sistema de adquisiciones y gestión de flujos de trabajo (Workflow) de EXA. Su propósito principal es permitir la administración (creación, edición, activación/desactivación) de las diferentes categorías de solicitudes de compra o servicios (ej. "Compra de Bienes y Equipos", "Servicios de Mantenimiento", etc.) y definir las **reglas de negocio** y el **flujo de aprobación** que cada tipo debe seguir de manera obligatoria.

---

## 2. Arquitectura y Capas del Módulo

El módulo está estructurado bajo el patrón de capas de EXA (FRONT - LOGICA - MODELS):

```
[ Capa de Presentación / Vista ]
  ??? flujo/FRONT/adq_tipos_requerimientos.php (HTML, Bootstrap 5.3, jQuery 3.7, AJAX)

[ Capa de Lógica de Negocio ]
  ??? flujo/LOGICA/wf_manager_log.php (Motor de Workflow)
  ??? flujo/LOGICA/adq_adquisiciones_log.php (Lógica de Adquisiciones)

[ Capa de Datos / Modelos ]
  ??? MODELS/adq_tipos_requerimientos.php (Clase Modelo orientada a Objetos)
```

### 2.1 Capa de Presentación (`flujo/FRONT/adq_tipos_requerimientos.php`)
- **Interfaz Gráfica:** Diseñada con una interfaz moderna y limpia usando **Bootstrap 5.3** y **Bootstrap Icons**.
- **Listado Principal:** Muestra una tabla con todos los tipos configurados para la empresa activa (`$Ses_Emp_Cod`), detallando sus reglas de control y su estado (Activo/Inactivo).
- **Formulario Modal:** Permite crear y editar tipos de requerimientos sin recargar la página, gestionando switches dinámicos para activar/desactivar restricciones.
- **Controladores AJAX:** El mismo archivo PHP actúa como controlador para responder peticiones asíncronas de guardado, cambio de estado y consulta de datos.

### 2.2 Capa de Datos (`MODELS/adq_tipos_requerimientos.php`)
- Define la clase `adq_tipos_requerimientos` que hereda de `AbstractModel`.
- Encapsula las consultas básicas de base de datos, aplicando filtros automáticos por empresa (`Emp_Cod`) mediante el método `sqlByNombre("setEmpCod", ...)`.
- Facilita la reutilización de consultas en otras partes del sistema (como la bandeja de solicitudes o el registro de compras).

---

## 3. Estructura de Datos (`adq_tipos_requerimientos`)

La tabla de la base de datos almacena la configuración de comportamiento para cada tipo de requerimiento:

| Campo | Tipo | Descripción |
|---|---|---|
| **`Trq_Cod`** | `INT` | Clave primaria autoincremental. Identificador único del tipo de requerimiento. |
| **`Emp_Cod`** | `INT` | Código de la empresa (soporte multi-inquilino / multi-tenant). |
| **`Wfm_Cod`** | `INT` | Clave foránea que apunta a `wf_flujos_modelos.Wfm_Cod`. Define qué flujo de aprobación seguirá. |
| **`Trq_Des`** | `VARCHAR` | Descripción o nombre descriptivo del tipo de requerimiento (ej. "Suministros de Oficina"). |
| **`Trq_Req_Fac`** | `TINYINT` | `1` si el requerimiento exige registrar una factura de compra para su cierre; `0` si no. |
| **`Trq_Per_Cie`** | `TINYINT` | `1` si se permite cerrar el flujo de adquisición sin haber vinculado una factura; `0` si no. |
| **`Trq_Req_Cot`** | `TINYINT` | `1` si el requerimiento exige adjuntar cotizaciones físicas de proveedores; `0` si no. |
| **`Trq_Min_Cot`** | `INT` | Número mínimo de cotizaciones físicas obligatorias (rango de 1 a 10). |
| **`Trq_Req_Pre`** | `TINYINT` | `1` si el sistema debe verificar disponibilidad presupuestaria antes de avanzar; `0` si no. |
| **`Trq_Req_Adj`** | `TINYINT` | `1` si exige que el solicitante cargue archivos adjuntos de sustento al inicio; `0` si no. |
| **`Trq_Req_Pro`** | `TINYINT` | `1` si exige seleccionar un proveedor sugerido desde el catálogo; `0` si no. |
| **`Trq_Est`** | `CHAR(1)` | Estado del registro: `'A'` (Activo) o `'I'` (Inactivo). |

---

## 4. Integraciones con Otros Módulos

El módulo de **Tipos de Requerimientos** no trabaja de forma aislada; es el "orquestador" de comportamiento para todo el flujo de adquisiciones:

### 4.1 Integración con Registro de Solicitudes (`adq_solicitud.php` & `adq_solicitud.js`)
Cuando un usuario crea una nueva solicitud de adquisición:
1. Al seleccionar el **Tipo de Requerimiento**, se dispara una petición AJAX (`ajax_get_trq_details`) para obtener su configuración en tiempo real.
2. El script de validación frontend (`adq_solicitud.js`) reacciona dinámicamente:
   - **Proveedor Sugerido:** Si `Trq_Req_Pro == 1`, muestra el campo autocompletable de proveedores (vía Select2) y lo marca como obligatorio.
   - **Cotizaciones Múltiples:** Si `Trq_Req_Cot == 1`, muestra la sección de cotizaciones físicas y genera automáticamente la cantidad de campos de carga de archivos especificada en `Trq_Min_Cot`.
   - **Validación de Ganador:** Exige que el usuario marque cuál de las cotizaciones cargadas es la ganadora y redacte una justificación comercial de la elección.

### 4.2 Integración con el Motor de Workflow (`wf_manager_log.php`)
Cuando la solicitud es enviada:
1. La lógica de adquisiciones (`adq_adquisiciones_log.php`) consulta el flujo modelo (`Wfm_Cod`) asociado al tipo de requerimiento seleccionado.
2. Invoca al método `iniciarInstancia($Wfm_Cod, 'adq_solicitudes', $sol_cod)` del motor de workflow.
3. El motor busca el nodo `INICIO` de dicho flujo modelo, crea la instancia de seguimiento (`wf_instancias`) y avanza automáticamente al primer paso de aprobación real (ej. Aprobación de Jefatura, Compras, Gerencia).

### 4.3 Integración con Bandeja y Dashboard (`adq_bandeja.php` & `adq_dashboard.php`)
- **Bandeja de Entrada:** Realiza un `INNER JOIN` con `adq_tipos_requerimientos` para mostrar el nombre del tipo de requerimiento en las listas de tareas pendientes de los aprobadores.
- **Dashboard Gerencial:** Agrupa métricas, tiempos promedio de atención y cuellos de botella basándose en los tipos de requerimientos y sus flujos asociados.

---

## 5. Flujos de Control AJAX (Endpoints Internos)

El archivo `adq_tipos_requerimientos.php` procesa tres acciones AJAX principales mediante peticiones `POST` y `GET`:

1. **`ajax_save_tipo_req` (POST):**
   - Recibe los datos del formulario modal.
   - Realiza saneamiento básico de cadenas (`mysqli_real_escape_string`) y conversión de tipos (`intval`).
   - Si recibe un código (`Trq_Cod`), ejecuta un `UPDATE` en la tabla `adq_tipos_requerimientos`.
   - Si no recibe código, ejecuta un `INSERT` asignando el estado `'A'` por defecto y el código de empresa actual (`$Ses_Emp_Cod`).
   - Retorna una respuesta JSON: `{"success": true}` o `{"success": false, "message": "..."}`.

2. **`ajax_toggle_tipo_req` (POST):**
   - Recibe el código (`Trq_Cod`) y el estado actual (`Trq_Est`).
   - Invierte el estado (si es `'A'` cambia a `'I'`, y viceversa).
   - Ejecuta la actualización en la base de datos.
   - Retorna: `{"success": true, "nuevo_estado": "..."}`.

3. **`ajax_get_tipo_req` (GET):**
   - Recibe el código (`Trq_Cod`).
   - Realiza una consulta directa a la tabla para obtener todos los campos del registro.
   - Retorna los datos en formato JSON para que el formulario modal los cargue en modo edición.

---

## 6. Diagnóstico y Recomendaciones de Mejora

A pesar de ser un módulo limpio, moderno y bien estructurado (utiliza Bootstrap 5 y jQuery actualizados), se identifican las siguientes oportunidades de optimización técnica:

1. **Saneamiento y Seguridad (Prepared Statements):**
   - Las consultas SQL concatenan variables directamente (ej. `$wfm_cod`, `$trq_des`). Aunque se usa `mysqli_real_escape_string` y `intval`, la mejor práctica moderna para evitar inyecciones SQL es el uso de **consultas preparadas (Prepared Statements)** a través del modelo o de PDO.
2. **Separación de Responsabilidades (Capa de Controladores):**
   - El archivo mezcla la lógica de control AJAX con la vista HTML. Se recomienda separar los endpoints AJAX a un controlador independiente (ej. `flujo/LOGICA/adq_tipos_requerimientos_actions.php`) y dejar el archivo de FRONT únicamente para la renderización visual.
3. **Manejo de Errores Global:**
   - En el bloque de creación/edición se utiliza un `try-catch`, pero en el cambio de estado (`ajax_toggle_tipo_req`) no se implementa, lo que podría generar fallos silenciosos si la base de datos pierde conexión o si ocurre un error de restricción de clave foránea.
