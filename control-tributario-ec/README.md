# Control Tributario Interno y Externo — Ecuador

Herramienta profesional para contadores: IVA (Form. 104), retenciones (Form. 103), Impuesto a la Renta, planillas IESS, semáforo de obligaciones y comprobantes SRI.

## Stack

| Capa | Tecnología |
|------|------------|
| Backend | Node.js + Express + JWT |
| Base de datos | MySQL (XAMPP) |
| Frontend | React + Vite + Tailwind CSS |
| Excel | ExcelJS |
| PDF | PDFKit |
| Parseo PDF | pdf-parse |

## Instalación (XAMPP)

### 1. Base de datos

1. Inicie **Apache** y **MySQL** en el Panel XAMPP.
2. Abra phpMyAdmin: http://localhost/phpmyadmin
3. Importe en este orden:
   - `database/schema.sql`
   - `database/seed.sql`

### 2. Backend

```bash
cd c:\xampp\htdocs\control-tributario-ec\backend
copy .env.example .env
npm install
npm run seed
npm run dev
```

API: http://localhost:4000/api/health

### 3. Frontend

```bash
cd c:\xampp\htdocs\control-tributario-ec\frontend
npm install
npm run dev
```

App: http://localhost:5173

### Credenciales demo

- **Email:** admin@controltributario.ec  
- **Password:** admin123  

(Ejecute `npm run seed` en backend si el login falla.)

## Módulos (pestañas únicas)

1. **Contribuyente** — RUC con dígito verificador, régimen, 9no dígito, período fiscal  
2. **Control Tributario** — Hoja maestra enero–diciembre (104 ventas/compras, retenciones, nómina)  
3. **Resumen I.R.** — Conciliación y liquidación con tabla progresiva  
4. **Planillas IESS** — Por empleado + importación PDF  
5. **Control Externo** — Semáforo de obligaciones y vencimientos por 9no dígito  
6. **Comprobantes SRI** — Registro + parseo PDF  
7. **Documentos** — Repositorio de archivos  
8. **Dashboard** — Informe gerencial y gráficos  
9. **Parámetros** — IVA, IESS, SBU, tabla IR por año (sin hardcode)

## Exportación

- **Excel:** 4 hojas (Control Tributario, Resumen I.R., IESS, Comprobantes) — botón en pie de página  
- **PDF:** Informe gerencial resumido  

## Parámetros editables (BD)

`TARIFA_IVA`, `IESS_PERSONAL`, `IESS_PATRONAL`, `TASA_CCC`, `TASA_FONDOS_RESERVA`, `SBU`, `PARTICIPACION_TRABAJADORES`, tabla `tabla_ir_progresiva` por `anio_fiscal`.

## Reglas de negocio implementadas

- Validación RUC Ecuador (módulo 11)  
- Vencimiento Form. 104/103 según 9no dígito RUC  
- Crédito tributario IVA arrastrable mes a mes  
- Cálculos automáticos IVA causado, crédito, IR progresivo  
- Multi-contribuyente por usuario contador  
- Declaraciones sustitutivas (`reemplaza_id`)  

## Estructura del proyecto

```
control-tributario-ec/
├── database/          # schema.sql, seed.sql
├── backend/src/       # API Express
├── frontend/src/      # React + pestañas
└── uploads/           # PDFs comprobantes
```
