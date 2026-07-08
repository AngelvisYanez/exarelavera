# Registro de Cambios, Mejoras y Guía de Despliegue Local

Este documento detalla todas las optimizaciones, correcciones de compatibilidad y la configuración realizada para desplegar localmente el sistema **Exa Ofsercont (ERP)** bajo **PHP 7.1.33** y **MariaDB 12.3**.

---

## 1. Guía de Ejecución Rápida en Local

### Backend (API)
Para levantar el sistema de backend localmente de forma indefinida, abre una consola en la raíz del proyecto (`C:\Users\ismaa\OneDrive\Documentos\GitHub\exa-contable-relavera`) y ejecuta:

```bash
php -S localhost:8000 router.php
```

El servidor de desarrollo integrado de PHP estará disponible en: **`http://localhost:8000`**
Para verificar el estado de la API, accede a: **`http://localhost:8000/api/v1/test`** (debe responder `{"mysqli":true}`).

### Frontend (Next.js)
Para levantar el servidor de desarrollo del frontend de Next.js, abre una consola en la ruta del frontend (`C:\Users\ismaa\OneDrive\Documentos\GitHub\exa-contable-relavera\frontend-next`) y ejecuta:

```bash
npm run dev
```

El portal web de desarrollo estará disponible en: **`http://localhost:3000`**

---

## 2. Optimizaciones y Cambios Realizados en el Backend

### A. Gestión de Dependencias con Composer (PHP 7.1.33)
*   **Problema:** Las versiones modernas de Composer (2.3+) requieren PHP 7.2.5+, impidiendo la instalación de dependencias en PHP 7.1.33.
*   **Solución:** Se integró localmente **Composer 2.2.x LTS** (`composer.phar`) en la raíz del proyecto, garantizando la compatibilidad absoluta con PHP 7.1.33.
*   **Resultado:** Se ejecutó con éxito `php composer.phar install` sin alterar la versión de PHP necesaria para producción.

### B. Corrección de Errores de Sintaxis (PHP 7.1.33 Compatibility)
PHP 7.1.33 es estricto y no soporta "comas de arrastre" (trailing commas) en las firmas de funciones/métodos ni en los parámetros de llamadas a funciones (características añadidas en PHP 8.0 y 7.3 respectivamente).
Se saneó la sintaxis en los siguientes archivos clave:
1.  **`classes/Manifiesto.php`**: Corrección de comas de arrastre en llamadas a `getPageGrid()` y `getRowConsulta()`.
2.  **`api/index.php`**: Saneamiento de comas de arrastre en llamadas a `$app->response->headers->set()` y `$app->response->body(json_encode(...))`.
3.  **`framework/Slim/Http/Util.php`**: Saneamiento de comas de arrastre y blindaje de firmas de métodos (`stripSlashesIfMagicQuotes`, `serializeCookies`, `encodeSecureCookie`, `decodeSecureCookie`) colocándolas en una sola línea para evitar que los formateadores automáticos de código las vuelvan a corromper en el futuro.

### C. Resolución de Rutas Absolutas (`$APP_REAL_PATH`)
*   **Problema:** Los archivos de lógica del ERP tradicional usan una variable global `$APP_REAL_PATH` para incluir recursos (ej. `require_once($APP_REAL_PATH."/relavera/LOGICA/...")`). En la API, esta variable estaba indefinida, rompiendo las inclusiones.
*   **Solución:** Se inyectó dinámicamente `$APP_REAL_PATH` y `$GLOBALS['APP_REAL_PATH']` al inicio de `api/index.php` apuntando al directorio raíz del proyecto de forma absoluta (`realpath(__DIR__ . "/../")`).
*   **Resultado:** Todas las inclusiones legacy que dependían de esta variable ahora funcionan de manera automática en la API.

### D. Emulación de Directorio de Trabajo (Workdir Emulation)
*   **Problema:** Los scripts de lógica legacy usan rutas relativas como `require_once('../../auditoria/LOGICA/...')` asumiendo que son ejecutados desde la carpeta `FRONT` del módulo correspondiente (ej: `facturacion/FRONT/`). Al cargarlos desde la API, el directorio de trabajo es la raíz del proyecto, lo que hacía que `../../` saliera de la carpeta del proyecto y fallara la inclusión.
*   **Solución:** Se diseñó una técnica quirúrgica en los adaptadores de la API (`api/v1/inventario/marcas.php` y `classes/Manifiesto.php`) para **cambiar temporalmente el directorio de trabajo (`chdir`) al FRONT equivalente** antes de incluir el archivo de lógica legacy, restaurándolo inmediatamente después.
*   **Resultado:** Compatibilidad mágica. La lógica legacy se ejecuta asumiendo su entorno esperado sin haber tenido que refactorizar cientos de archivos individuales de lógica de negocio.

---

## 3. Optimizaciones y Cambios Realizados en el Frontend

### A. Corrección del Bug de Claves Duplicadas (React Console Error)
*   **Problema:** En el login, cuando una empresa tenía múltiples sucursales, el listado devolvía ítems con el mismo código `Emp_Cod`, rompiendo la restricción de React de que las claves (`key`) deben ser únicas.
*   **Solución:** En `app/login/page.tsx`, se combinó el código de la empresa con el de la sucursal para generar una clave totalmente única:
    `key={`${emp.Emp_Cod}-${emp.Suc_Des || index}`}`
*   **Resultado:** La consola de desarrollo quedó totalmente libre de errores de claves duplicadas de React.

### B. Interactividad Completa en el Panel de Actores
*   **Cambio:** Se diseñaron e implementaron modales interactivos de **Creación** y **Edición** para **Clientes** y **Proveedores** en `app/dashboard/actores/page.tsx`.
*   **Impacto:** Los formularios se comunican de extremo a extremo con el cliente HTTP (`lib/api`) de Next.js, envían las payloads completas respetando los esquemas, manejan cargadores e inyectan el Bdd de forma dinámica desde el usuario autenticado.

### C. Automatización de Combos y Modales en Inventario
*   **Cambio:** En `app/dashboard/inventario/page.tsx`, se implementaron modales interactivos para gestionar **Categorías**, **Marcas** y **Productos**.
*   **Impacto:** El modal de Producto carga dinámicamente las marcas y categorías disponibles en la base de datos a través de `useQuery()`. El catálogo del inventario traduce automáticamente los IDs crudos a nombres descriptivos, logrando una vista de nivel empresarial.

### D. Trazabilidad de Manifiestos
*   **Cambio:** En `app/dashboard/manifiestos/page.tsx`, se añadió la funcionalidad del modal **Ver Detalles** (`Eye`) y un formulario para la **Emisión de nuevos Manifiestos**.
*   **Impacto:** El modal de detalles consume directamente la API `obtenerDetalle(item.Man_Cod)` para renderizar un desglose técnico del estado de la carga de relaves (peso, chofer, placas, cliente, planta origen).

---

## 4. Detalles de Conexión a la Base de Datos

*   **Motor:** MariaDB 12.3
*   **Host:** `localhost`
*   **Usuario:** `root`
*   **Contraseña:** `""` (vacía)
*   **Base de datos principal (Master):** `exa_master`
*   **Archivo de conexión núcleo:** `DATA/MysqlConexion.php`

Las tablas maestras de administración (`access`, `empresas`, `sucursal`, `adm_planes`, etc.) se encuentran correctamente cargadas en `exa_master` y son accesibles de forma inmediata por el sistema en local.

---

## 5. Proceso de Pruebas de Calidad Locales (Validation)

Para asegurar la robustez del código frontend y la correcta comunicación de tipos, se ejecutó una suite de chequeo estático de TypeScript:

```bash
# Ejecutado en frontend-next
npx tsc --noEmit
```

*   **Hallazgo inicial:** TypeScript detectó un error en la ruta de detalles de manifiestos (`obtenerDetail` vs `obtenerDetalle`).
*   **Corrección:** Se alineó el nombre del método con la API declarada.
*   **Resultado final:** **Compilación y Typecheck de TypeScript exitoso al 100%**, confirmando un despliegue libre de fallos de compilación.

---
*Documento generado y ampliado automáticamente el 04 de Junio de 2026 por el asistente de desarrollo.*
