# Control Tributario Ecuador (PHP) — EXA

Herramienta standalone en PHP para control tributario ecuatoriano. Sin base de datos; usa `$_SESSION`.

## Acceso

- **URL:** http://localhost/control-tributario-ec/
- **Volver a EXA:** botón en la barra superior → `administrador/FRONT/home.php`

## Requisitos

- XAMPP con PHP 7.4+ (recomendado 8.x)
- Extensión `zip` habilitada (generación Excel)
- FPDF en `libs/fpdf/` (se copia desde `Librerias/fpdf`)
- Opcional: `pdftotext` en PATH para mejor lectura de PDFs SRI/IESS

## Flujo (6 pasos)

1. Datos del contribuyente (RUC con dígito verificador, régimen, SBU, tasas IESS)
2. Subir declaraciones SRI (PDF Form. 104 / comprobantes)
3. Subir planilla consolidada IESS
4. Datos manuales complementarios e IR anual
5. Dashboard (KPIs, semáforo, resumen mensual, borrador IR)
6. Descargar Excel (5 hojas) o PDF

## Estructura

Ver carpetas `parsers/`, `calculos/`, `generadores/`, `assets/`.

## Nota sobre PDFs

Los parsers extraen texto del PDF con regex. Si un comprobante SRI no se lee bien, edite los campos en la pantalla de confirmación del paso 2.

## Integración menú EXA

Agregue un enlace en el módulo **Tributación** o **Contabilidad → Herramientas**:

```html
<a href="/control-tributario-ec/" target="_blank">Control Tributario EC</a>
```
