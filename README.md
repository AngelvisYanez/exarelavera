# Exa Ofsercont: Sistema de Contabilidad y Gestión Empresarial (ERP)

Este repositorio alberga el código fuente de **Exa Ofsercont**, un sistema integral de contabilidad y gestión empresarial (ERP). El proyecto comenzó originalmente el 07 de enero de 2019 y fue migrado de PHP 5.3.8 a PHP 7.1+, logrando recientemente compatibilidad completa con **PHP 8.2**.

## Rama de Migración PHP 8

Este repositorio contiene la rama `feature/migracion-php8` (o `migracionphp`) con todos los cambios de compatibilidad. 
Consulta los siguientes documentos para el registro detallado de los cambios realizados:
- [`MIGRACION_PHP8_RESUMEN.md`](MIGRACION_PHP8_RESUMEN.md) (Resumen de las correcciones de compatibilidad aplicadas para PHP 8.2).
- [`docs/MIGRACION_PHP_8.md`](docs/MIGRACION_PHP_8.md) (Bitácora de la migración completa).

## Descripción General

Exa Ofsercont es una aplicación web construida con **PHP** y base de datos **MySQL/MariaDB**. Su arquitectura modular permite gestionar:

- **Contabilidad y Finanzas:** Gestión contable, auditoría, caja chica, tesorería, activos fijos.
- **Inventario y Logística:** Control de inventario, compras, bodega.
- **Facturación:** Facturación electrónica, retenciones, guías de remisión, notas de crédito.
- **Recursos Humanos:** Administración de personal, roles, nómina.
- **Módulos Especializados:** Industria bananera, camaronera, transporte de carga, relavera.
- **API REST:** Endpoints para integración con frontend Next.js.

## Módulos Principales

| Módulo | Descripción |
|--------|-------------|
| `administrador` | Administración del sistema, login, permisos, menú |
| `activosfijos` | Gestión de activos fijos y depreciaciones |
| `adquisiciones` | Adquisiciones y proveedores |
| `api` | API REST (Slim Framework) |
| `auditoria` | Herramientas de auditoría y monitoreo |
| `bananero`, `camaronera`, `transportecarga` | Módulos específicos por industria |
| `bodega` | Control de inventario y almacenamiento |
| `caja_chica` | Fondos de caja chica |
| `classes` | Modelos de datos (Categoría, Cliente, Manifiesto, etc.) |
| `compras` | Procesos de compra y requisiciones |
| `contabilidad` | Núcleo contable: balances, diario, mayor, plan de cuentas |
| `facturacion` | Facturación: ventas, compras, electrónica, ATS |
| `inventario` | Kardex, rentabilidad, toma física |
| `relavera` | Manifiestos, anticipos, dashboards |
| `rrhh` | Recursos humanos, contratos, anticipos |
| `tesoreria` | Tesorería: cheques, cobros, pagos, conciliaciones |

## Requisitos del Sistema

- **PHP:** 8.2+ (recomendado para producción, soporta desde 7.1.14+)
- **Servidor Web:** Apache o Nginx
- **Base de Datos:** MySQL 5.7+ o MariaDB 10.3+
- **Composer:** 2.2.x LTS (incluido como `composer.phar`)

## Despliegue Local

### Usando el servidor integrado de PHP

```bash
php -S localhost:8000 router.php
```

El sistema estará disponible en: **http://localhost:8000**

### Usando Apache/XAMPP

Clona el proyecto en `htdocs` y accede vía:

```
http://localhost/exa-contable-relavera/
```

### Instalación de dependencias

```bash
php composer.phar install
```

### Configuración de Base de Datos

1. Crea la base de datos `exa_master`
2. Configura credenciales en `DATA/MysqlConexion.php`
3. Copia `.env.example` a `.env` y ajusta según tu entorno
