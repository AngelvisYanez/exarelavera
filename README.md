# Exa Ofsercont: Sistema de Contabilidad y Gestión Empresarial (ERP)

Este repositorio alberga el código fuente de **Exa Ofsercont**, un sistema integral de contabilidad y gestión empresarial (ERP). El proyecto comenzó originalmente el 07 de enero de 2019 y fue migrado de PHP 5.3.8 a PHP 7.1+, logrando recientemente compatibilidad completa con **PHP 8.2**.

## 🚀 Características Principales

Exa Ofsercont es una aplicación web construida con **PHP 8.2** y base de datos **MySQL/MariaDB**. Su arquitectura modular permite gestionar:

- **Contabilidad y Finanzas:** Gestión contable, auditoría, caja chica, tesorería, activos fijos.
- **Inventario y Logística:** Control de inventario, compras, bodega.
- **Facturación:** Facturación electrónica, retenciones, guías de remisión, notas de crédito.
- **Recursos Humanos:** Administración de personal, roles, nómina.
- **Módulos Especializados:** Industria bananera, camaronera, transporte de carga, relavera.
- **API REST:** Endpoints para integración con frontend moderno.

## 📋 Requisitos del Sistema

- **PHP:** 8.2+ (soporta desde 7.1.14+ por compatibilidad legacy)
- **Servidor Web:** Apache o Nginx
- **Base de Datos:** MySQL 5.7+ o MariaDB 10.3+
- **Composer:** 2.2.x LTS (incluido como `composer.phar`)

---

## 🛠️ Guía de Despliegue

A continuación, se presentan las instrucciones para desplegar la aplicación en diferentes entornos.

### 1. Despliegue Local (Desarrollo)

Ideal para desarrolladores que desean probar o modificar el código en sus máquinas.

**Opción A: Usando XAMPP/WAMP (Recomendado)**
1. Clona este repositorio dentro de la carpeta `htdocs` (XAMPP) o `www` (WAMP).
2. Instala las dependencias usando Composer en la terminal:
   ```bash
   cd exa-contable-relavera
   php composer.phar install
   ```
3. **Base de Datos:** Crea una base de datos llamada `exa_master` (o la que prefieras) e importa tu volcado `.sql`. *(Recuerda guardar cualquier archivo de la base de datos dentro de la carpeta `/db` que está ignorada en Git para evitar subidas accidentales).*
4. Configura las credenciales de conexión editando el archivo `DATA/MysqlConexion.php`.
5. Accede desde tu navegador: `http://localhost/exa-contable-relavera/`

**Opción B: Servidor integrado de PHP**
```bash
php -S localhost:8000 router.php
```
*El sistema estará disponible en `http://localhost:8000`*

### 2. Despliegue con Docker (Entorno Aislado)

Para levantar el proyecto rápidamente sin instalar servidores locales y evitar problemas de dependencias:

1. Asegúrate de tener instalado [Docker](https://www.docker.com/) y `docker-compose`.
2. En la raíz del proyecto, asegúrate de contar con el archivo `docker-compose.yml` y levanta los contenedores:
   ```bash
   docker-compose up -d --build
   ```
3. Ingresa al contenedor de PHP para instalar las dependencias:
   ```bash
   docker exec -it exa-contable-relavera-php bash
   php composer.phar install
   exit
   ```
4. Tu aplicación estará corriendo en el puerto configurado (ej. `http://localhost:8000`).

### 3. Despliegue en Plesk (Producción)

Para desplegar el ERP en un entorno de producción utilizando **Plesk Panel**:

1. **Crear el Subdominio/Dominio:**
   - En Plesk, ve a **Sitios web y dominios** > **Añadir subdominio** (ej. `erp.tudominio.com`).
2. **Configurar la versión de PHP:**
   - En la configuración del dominio, ve a **Configuración de PHP** y selecciona la versión **8.2.x** (FPM application servida por Apache o Nginx).
3. **Subir los Archivos:**
   - Usa la extensión **Git** de Plesk para clonar este repositorio directamente, o sube los archivos vía **Administrador de Archivos** a la carpeta raíz (`httpdocs` o el directorio de tu subdominio).
4. **Instalar Dependencias:**
   - Si no subiste la carpeta `vendor`, ve a la opción de **Composer** en Plesk para escanear e instalar las dependencias. Alternativamente, conéctate por SSH y ejecuta `php composer.phar install`.
5. **Configurar Base de Datos:**
   - Ve a **Bases de datos**, crea una nueva base de datos y su usuario correspondiente.
   - Importa tu esquema SQL usando la interfaz de phpMyAdmin.
   - Actualiza el archivo `DATA/MysqlConexion.php` con las nuevas credenciales del servidor de producción.
6. **Permisos (Carpetas sensibles):**
   - Asegúrate de que las carpetas donde el sistema genere archivos temporales, comprobantes, PDFs o logs, tengan permisos de escritura (ej. `755`) para el usuario de Apache/Nginx.
7. **Certificado SSL:**
   - Asegura la conexión instalando un certificado SSL gratuito utilizando la extensión **Let's Encrypt** dentro del panel de control.

---

## 📖 Documentación Adicional

- [`api/README.md`](api/README.md) - Guía de la API REST, autenticación y documentación Swagger (`/v1/docs`).
- [`docs/MIGRACION.md`](docs/MIGRACION.md) - Documento maestro de migración PHP (plan, cambios, mejoras, pendientes).
- [`docs/optimizacion-grid-ventas-N+1.md`](docs/optimizacion-grid-ventas-N+1.md) - Eliminación de N+1 en grid de ventas.
- [`docs/relaciones-modulos-optimizacion.md`](docs/relaciones-modulos-optimizacion.md) - Relaciones entre módulos y reglas de optimización.

## 📦 Módulos Principales (Resumen)

| Módulo | Descripción |
|--------|-------------|
| `administrador` | Administración del sistema, roles, permisos. |
| `api` | API REST para interconexión. |
| `contabilidad` | Diario, mayor, plan de cuentas, reportes financieros. |
| `facturacion` | Ventas, compras, facturación electrónica (SRI). |
| `tesoreria` | Bancos, conciliación, cobros, pagos y caja. |
| `industriales` | Bananero, camaronera, transporte de carga, relavera. |
