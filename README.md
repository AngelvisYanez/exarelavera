# Exa Ofsercont: Sistema de Contabilidad y Gestión Empresarial (ERP)

Este repositorio alberga el código fuente de "Exa Ofsercont", un sistema integral de contabilidad y gestión empresarial (ERP) desarrollado originalmente el 07 de enero de 2019. El proyecto busca centralizar y automatizar diversas operaciones administrativas y contables para empresas.

## Descripción General

Exa Ofsercont es una aplicación web construida con **PHP** y, presumiblemente, utiliza una base de datos **MySQL/MariaDB** para la persistencia de datos. Su arquitectura modular permite gestionar áreas clave de una empresa, incluyendo:

*   **Contabilidad y Finanzas:** Gestión contable, auditoría, caja chica, tesorería y activos fijos.
*   **Inventario y Logística:** Control de inventario, gestión de compras y movimientos de bodega.
*   **Ventas:** Facturación de productos y servicios.
*   **Recursos Humanos:** Administración de personal.
*   **Módulos Especializados:** Soporte para industrias específicas como la bananera, camaronera y transporte de carga.

## Módulos Principales

El proyecto está organizado en varios directorios, cada uno representando un módulo funcional:

*   `administrador`: Funcionalidades de administración del sistema.
*   `activosfijos`: Gestión de activos fijos de la empresa.
*   `adquisiciones`: Módulo de adquisiciones y compras.
*   `auditoria`: Herramientas y registros para auditoría.
*   `bananero`, `camaronera`, `transportecarga`: Módulos específicos para distintas industrias.
*   `bodega`: Control de inventario y almacenamiento.
*   `caja_chica`: Gestión de fondos de caja chica.
*   `compras`: Procesos de compra.
*   `contabilidad`: Núcleo contable del sistema.
*   `facturacion`: Generación y gestión de facturas.
*   `inventario`: Manejo del inventario de productos.
*   `rrhh`: Gestión de recursos humanos.
*   `tesoreria`: Control de tesorería y flujos de efectivo.
*   `api`: Interfaz para la comunicación con otros sistemas o frontends.
*   `classes`, `componentes`, `framework`, `Librerias`: Componentes y librerías internas del sistema.

## Despliegue Local

Para poner en marcha Exa Ofsercont en tu entorno local, sigue estos pasos:

### 1. Requisitos del Sistema

Asegúrate de tener instalado un entorno de servidor web compatible con PHP, como:

*   **Servidor Web:** Apache o Nginx
*   **PHP:** Versión compatible (probablemente PHP 5.x o 7.x)
*   **Base de Datos:** MySQL o MariaDB
*   **Composer:** Herramienta de gestión de dependencias de PHP (opcional, si el proyecto lo usa).

Una opción sencilla es usar paquetes "todo en uno" como XAMPP, WAMP o Laragon.

### 2. Clonar el Repositorio

Clona el proyecto en el directorio de documentos de tu servidor web (ej. `htdocs` en XAMPP):

```bash
git clone <URL_DEL_REPOSITORIO> exa-contable-relavera
```
*(Reemplaza `<URL_DEL_REPOSITORIO>` con la URL real de este repositorio si aún no lo has hecho).*

### 3. Configuración de la Base de Datos

1.  **Crear Base de Datos:** Accede a tu gestor de base de datos (ej. phpMyAdmin) y crea una nueva base de datos (sugiero `exa_contable`).
2.  **Importar Esquema:** Deberás encontrar el archivo `.sql` con el esquema de la base de datos para importarlo. Este archivo suele contener la estructura de las tablas y, a veces, datos iniciales. Es probable que se encuentre en alguno de los directorios del proyecto.
3.  **Configurar Conexión:** Localiza el archivo de configuración de la base de datos (usualmente `config.php`, `database.php` o similar, posiblemente en `classes` o `framework`). Edita las credenciales para que apunten a tu base de datos local (nombre de la base de datos, usuario, contraseña, host).

### 4. Dependencias PHP (si aplica)

Si el proyecto utiliza Composer para la gestión de dependencias, navega al directorio del proyecto y ejecuta:

```bash
cd exa-contable-relavera
composer install
```

### 5. Acceso al Sistema

Una vez configurado, podrás acceder al sistema a través de tu navegador web. Si lo desplegaste en `localhost` bajo el directorio `exa-contable-relavera`, la URL será:

```
http://localhost/exa-contable-relavera/
```
