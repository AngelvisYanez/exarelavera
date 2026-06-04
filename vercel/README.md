# EXA Contable Relavera - Monitor de Incidencias & Disponibilidad (Vercel Ready)

Este subproyecto es una versión optimizada y totalmente preparada para su despliegue en **Vercel** de la aplicación **Next.js (Frontend & Backend integrado)** con soporte para **PostgreSQL** y **Prisma ORM**.

## Características de la Arquitectura

1. **Frontend Moderno:** Desarrollado con Next.js 16, React 19 y Tailwind CSS 4 para un rendimiento excepcional y diseño responsivo.
2. **Backend Integrado (Serverless):** Utiliza Next.js API Routes (`/api/incidents`) que se ejecutan automáticamente como funciones serverless en Vercel.
3. **Persistencia con PostgreSQL:** Conectado mediante Prisma ORM para un mapeo de datos limpio y tipado estático completo.
4. **Resiliencia Automática (Fallback Seguro):** Si la base de datos no está configurada o falla la conexión, las APIs cambian dinámicamente a un modo "simulación" utilizando datos estáticos en memoria. ¡Esto garantiza que la previsualización de Vercel nunca se rompa!
5. **Autosebrado inteligente (Auto-seeding):** La primera vez que el backend realice una petición `GET` a una base de datos PostgreSQL vacía, creará automáticamente las 4 incidencias críticas iniciales de demostración.

---

## Estructura del Proyecto

* `vercel/prisma/schema.prisma`: Esquema de datos para PostgreSQL. Define la tabla `incidents` (`Incident`).
* `vercel/lib/prisma.ts`: Inicialización segura del cliente de Prisma para entornos serverless (evita la saturación de conexiones).
* `vercel/app/api/incidents/route.ts`: API Backend que expone endpoints `GET` (listar), `POST` (crear incidencia mock) y `PATCH` (resolver incidencia).
* `vercel/app/dashboard/incidencias/page.tsx`: Vista del Monitor de Incidencias integrada con la base de datos.

---

## Requisitos Previos

* Una base de datos **PostgreSQL**. Puedes obtener una gratuita en:
  * [Supabase](https://supabase.com/)
  * [Neon](https://neon.tech/)
  * [Aiven](https://aiven.io/)
* Una cuenta en [Vercel](https://vercel.com/).

---

## Configuración y Despliegue Paso a Paso

### 1. Clonar y Configurar Localmente (Opcional)

Si deseas probarlo localmente antes de subirlo:

1. Ve a la carpeta `vercel`:
   ```bash
   cd vercel
   ```
2. Instala las dependencias:
   ```bash
   npm install
   ```
3. Crea un archivo `.env` a partir de `.env.example`:
   ```bash
   cp .env.example .env
   ```
4. Define tu cadena de conexión a PostgreSQL en `DATABASE_URL` dentro de `.env`.
5. Empuja el esquema de Prisma a tu base de datos PostgreSQL para crear la tabla:
   ```bash
   npx prisma db push
   ```
6. Inicia el servidor de desarrollo:
   ```bash
   npm run dev
   ```

---

### 2. Despliegue en Vercel

Vercel detectará automáticamente la estructura de Next.js y compilará todo el proyecto en segundos.

#### Opción A: Desde la CLI de Vercel

Si tienes la CLI de Vercel instalada:
```bash
cd vercel
vercel
```

#### Opción B: Desde el Dashboard de Vercel (Recomendada)

1. Sube tu repositorio a GitHub, GitLab o Bitbucket.
2. Inicia sesión en [Vercel](https://vercel.com/) e ingresa a **Add New Project**.
3. Selecciona tu repositorio y haz clic en **Import**.
4. En **Root Directory**, selecciona la carpeta `vercel` (o haz clic en "Edit" y selecciona la carpeta `vercel` para que no intente compilar todo el monorepo).
5. Abre la sección de **Environment Variables** (Variables de entorno) y añade:
   * `DATABASE_URL`: Tu cadena de conexión de PostgreSQL.
   * `SUPERADMIN_USER`: Nombre de usuario para el panel (Por defecto: `exacontable`).
   * `SUPERADMIN_PASS`: Contraseña de acceso (Por defecto: `Exito2026!`).
6. Haz clic en **Deploy**.

---

### 3. Sincronizar Base de Datos en Producción

Una vez que el despliegue esté completo, necesitas crear la estructura de tablas en tu PostgreSQL de producción. Ejecuta este comando en tu terminal local (asegurándote de que la cadena en tu `.env` local apunte a la base de datos de producción temporalmente, o pásala directamente):

```bash
DATABASE_URL="tu_conexion_real_de_produccion" npx prisma db push
```

¡Listo! Al abrir la ruta `/dashboard/incidencias` en tu sitio desplegado, el backend autosembrará las incidencias de demostración en tu base de datos PostgreSQL si está vacía, y todo estará interconectado en tiempo real.
