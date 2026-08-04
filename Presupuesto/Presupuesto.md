# Módulo Presupuesto (EXA PPTO)

Documento de contexto completo del módulo ubicado en `c:\xampp\htdocs\Presupuesto`.

Fecha de actualización: agosto 2026.

---

## 1. ¿Para qué sirve?

Control presupuestario del ERP EXA. Permite:

- Definir **versiones de presupuesto** por empresa/año (`Emp_Cod`, `Ppe_Ani`, `Ppe_Ver`).
- Mantener el **catálogo jerárquico de partidas** (grupos -> subgrupos -> detalle) vinculado a `Emp_Cod`.
- Cargar presupuesto **estándar mensual** por partida (`pre_detalle`).
- Gestionar **proyectos presupuestarios** (p. ej. Relaves / RCET), donde gastos e ingresos dependen de **toneladas** y **precio $/ton**.
- Comparar **aprobado vs forecast vs ejecutado vs comprometido vs disponible**.
- Registrar **reajustes** (incremento, reducción, transferencia).
- Parametrizar **cuentas contables** -> partidas presupuestarias (`pre_partida_cuenta`) usando `Emp_Cod`, `Pla_Cod` y `Pld_Cod`, y sincronizar ejecución desde mayores contables.
- Simular/aplicar **ajuste financiero**: costo de capital + recuperación GAD (sin borrar la partida base).
- Consultar dashboards, alertas (D2/D8), exportación Excel/PDF.

Contexto de uso frecuente reciente: empresa ECOPARKMINING (`Emp_Cod` típico 620), proyecto Relaves `RCET-01`, versión ~2026 V1.

---

## 2. Diseño / arquitectura

```
Presupuesto/
??? FRONT/          -> pantallas PHP + endpoints export/AJAX ligeros
??? LOGICA/         -> controladores AJAX, motores, schema, integraciones
??? VALIDACIONES/   -> validaciones PHP/JS
??? SQL/            -> migraciones y DDL completo refactorizado (exa_ppto_schema_completo_Emp_Cod.sql)
??? LOGICA/vendor/  -> lectores spreadsheet legacy
```

### Patrón técnico y convención de nombres EXA ERP

1. Cada pantalla requiere `administrador/LOGICA/seguridad.php`.
2. Conexión MySQL vía `contabilidad/LOGICA/con_log_balances2.php`.
3. **Bloque unificado de 27 tablas físicas con prefijo `pre_`**:
   - **Llaves primarias simples autoincrementales**: Todas las tablas tienen una única PK entera autoincremental que termina en `_Cod` (ej. `Ppe_Cod`, `Ppa_Cod`, `Pro_Cod`, `Pdp_Cod`, `Ajc_Cod`, etc.). No existen llaves primarias compuestas ni llaves primarias con sufijo `_id`.
   - **Nombres de campo únicos**: Ningún campo que no sea una Foreign Key repite su nombre en otra tabla. Cada campo no-FK lleva un prefijo acorde a su tabla (ej. `Pro_Nom`, `Plt_Nom`, `Ppe_Des`, `Ppa_Des`, `Ppe_FecReg`, `Pro_FecReg`).
   - **Llaves foráneas compartidas**: Mantienen idéntico nombre y tipo en todas las tablas donde se referencian:
     - `Emp_Cod BIGINT(20)` (empresas)
     - `Usu_Cod BIGINT(20)` (usuarios)
     - `Suc_Cod BIGINT(20)` (sucursal)
     - `Dep_Cod BIGINT(20)` (departamen)
     - `Pla_Cod BIGINT(20)` (plan_cuenta)
     - `Pld_Cod BIGINT(20)` (det_plan)
     - `Ppe_Cod INT(11)` (pre_presupuesto)
     - `Ppa_Cod INT(11)` (pre_partidas)
     - `Pro_Cod INT(11)` (pre_proyectos)
     - `Plt_Cod INT(11)` (pre_plantillas)
     - `Pdp_Cod INT(11)` (pre_proyecto_detalles)
     - `Ajc_Cod INT(11)` (pre_ajuste_fin_cab)
     - `Prg_Cod INT(11)` (pre_reglas)

### Capas clave

| Capa | Archivo | Rol |
|------|---------|-----|
| Persistencia | `ppto_persistencia_logica.php` | Consultas centralizadas por casos |
| Motor cálculo | `ppto_motor_calculo.php` | Fórmulas seguras rubro/partida/versión |
| Forecast | `ppto_forecast_logica.php` | Toneladas x factor = PF por escenario |
| Cuadro proyectos | `ppto_proyectos_cuadro_logica.php` | Carga rubros + escenarios + precio año |
| Ajuste financiero | `ppto_ajuste_financiero_logica.php` | Capital % + GAD + historial |
| Schema | `ppto_schema_logica.php` | Migración y verificaciones de esquema |
| Dashboard | `dashboard_logica.php` | KPIs, partidas, evolución mensual |
| Integración | `ppto_movimiento_integracion.php`, `ppto_hooks_*` | Documentos ERP -> ejecuciones |

---

## 3. Pantallas (uso)

| Pantalla | Ruta | Función |
|----------|------|---------|
| Administración | `FRONT/ppto_admin_front.php` | Partidas, mensual, reglas, carga presupuesto, param. contable |
| Proyectos | `FRONT/ppto_proyectos_front.php` | Proyectos, versiones/ton, rubros, cuadro, escenarios, ajuste financiero, import PDF/Excel |
| Dashboard | `FRONT/dashboard_front.php` | KPIs, producción, forecast, alertas, export |
| Producción | `FRONT/ppto_produccion_front.php` | Origen producción, plan ton/mes, cierre/reapertura, aprobación |
| Reajustes | `FRONT/ppto_reajustes_front.php` | Incremento / reducción / transferencia (`?embed=1` desde alertas) |
| Plantillas | `FRONT/plantillas_front.php` | CRUD plantillas -> proyectos |
| Consulta | `FRONT/ppto_consulta_front.php` | Semáforo partidas, consolidado, detalle ejecución |

---

## 4. Funcionalidad por flujo

### 4.1 Proyectos y versiones

1. Abrir `ppto_proyectos_front.php`.
2. API: `LOGICA/ppto_proyectos_logica.php`.
3. `action=catalogos` -> plantillas, partidas, cabeceras.
4. `action=save` -> `pre_proyectos` (`Pro_Cod`, `Emp_Cod`, `Pro_Ide`, `Pro_Nom`, `Usu_Cod`, `Plt_Cod`).
5. `action=save_version_ton` -> `pre_proyecto_version`:
   - `Ppv_TonBaseMes` (ingresos / PDF)
   - `Ppv_TonCostoMes` (driver egresos, típ. 77.000)
   - `Ppv_TarifaTonIva`
   - `Ppv_IvaDivisor` (típicamente 1.15)

### 4.2 Rubros del proyecto

- Tabla: `pre_proyecto_detalles` (`Pdp_Cod`, `Ppe_Cod`, `Ppa_Cod`, `Pro_Cod`, `Emp_Cod`, `Pdp_Rubro`, `Pdp_TonBase`, `Pdp_FacAnualTon`, `Pdp_PreAnual`, `Usu_Cod`) (+ meses en `pre_proyecto_detalles_mes`).
- Campos clave: `Pdp_FacAnualTon`, `Pdp_PreAnual`, `Pdp_TonBase`.
- Actions: `save_rubro`, `list_rubros`, etc.

### 4.3 Ajuste financiero (capital + GAD)

Bloque en el Cuadro de proyectos.

| Parámetro | Columna / campo | Default típico |
|-----------|-----------------|----------------|
| Costo capital % | `Ppv_CostCapPct` | 11% |
| Factor GAD $/t | `Ppv_GadFacTon` | 0.1984 |
| Objetivo GAD | `Ppv_GadObjetivo` | 2.000.000 |
| GAD recuperado acum. | `Ppv_GadRecAcum` | 0 |
| Usar partida final | `Ppv_AjuActivo` | off |

Al aplicar: historial en `pre_ajuste_fin_cab` (`Ajc_Cod`) + `pre_ajuste_fin_det` (`Ajd_Cod`).

---

## 5. Bloque Unificado de las 27 Tablas MySQL (`pre_*`)

A continuación se detalla cada una de las **27 tablas físicas** del módulo de presupuesto con su PK autoincremental única `_Cod` y llaves foráneas:

| # | Tabla Físicamente Creada | PK única | Foreign Keys (FKs) | Descripción |
|---|--------------------------|----------|--------------------|-------------|
| 1 | `pre_presupuesto` | `Ppe_Cod` | `Emp_Cod`, `Suc_Cod`, `Dep_Cod`, `Usu_Cod`, `Usu_Apr` | Cabeceras y versiones de presupuesto |
| 2 | `pre_partidas` | `Ppa_Cod` | `Emp_Cod`, `Ppa_Pad`, `Usu_Cod` | Catálogo jerárquico de partidas presupuestarias |
| 3 | `pre_detalle` | `Pde_Cod` | `Ppe_Cod`, `Ppa_Cod` | Presupuesto estándar mensual por versión/partida |
| 4 | `pre_reglas` | `Prg_Cod` | `Emp_Cod`, `Ppa_Cod`, `Usu_Cod` | Reglas de imputación automática de documentos ERP |
| 5 | `pre_ejecucion` | `Pej_Cod` | `Ppe_Cod`, `Ppa_Cod`, `Emp_Cod`, `Suc_Cod`, `Dep_Cod`, `Pro_Cod`, `Usu_Cod`, `Prg_Cod` | Ledger de ejecución y comprometido presupuestario |
| 6 | `pre_proyectos` | `Pro_Cod` | `Emp_Cod`, `Plt_Cod`, `Usu_Cod` | Maestro de proyectos presupuestarios |
| 7 | `pre_proyecto_detalles` | `Pdp_Cod` | `Ppe_Cod`, `Ppa_Cod`, `Pro_Cod`, `Emp_Cod`, `Usu_Cod` | Rubros analíticos del proyecto |
| 8 | `pre_proyecto_detalles_mes` | `Pdm_Cod` | `Pdp_Cod` | Distribución mensual del rubro analítico |
| 9 | `pre_proyecto_version` | `Ppv_Cod` | `Pro_Cod`, `Emp_Cod`, `Ppe_Cod`, `Usu_Cod` | Configuración de toneladas, tarifas y ajuste por proyecto-versión |
| 10 | `pre_proyecto_precio_anio` | `Ppr_Cod` | `Pro_Cod`, `Emp_Cod`, `Ppe_Cod`, `Usu_Cod` | Proyección de precios $/Ton c/IVA por año |
| 11 | `pre_proyecto_publicacion` | `Pub_Cod` | `Pro_Cod`, `Emp_Cod`, `Ppe_Cod`, `Usu_Cod` | Auditoría de publicación y aprobación de proyectos |
| 12 | `pre_ajuste_fin_cab` | `Ajc_Cod` | `Pro_Cod`, `Emp_Cod`, `Ppe_Cod`, `Usu_Cod` | Historial cabecera de ajuste financiero capital+GAD |
| 13 | `pre_ajuste_fin_det` | `Ajd_Cod` | `Ajc_Cod` | Historial detalle por grupo de ajuste financiero |
| 14 | `pre_prod_config` | `Pco_Cod` | `Pro_Cod`, `Emp_Cod`, `Usu_Cod` | Configuración de orígenes de producción física |
| 15 | `pre_prod_periodos` | `Prd_Cod` | `Pro_Cod`, `Emp_Cod`, `Usu_Cod` | Producción física esperada, real y proyectada por mes |
| 16 | `pre_prod_variaciones` | `Var_Cod` | `Pro_Cod`, `Emp_Cod` | Variaciones de producción física real vs esperada |
| 17 | `pre_prod_evento_log` | `Pel_Cod` | `Pro_Cod`, `Emp_Cod`, `Usu_Cod` | Bitácora de eventos de cierre/reapertura de producción |
| 18 | `pre_plantillas` | `Plt_Cod` | `Emp_Cod`, `Usu_Cod` | Plantillas presupuestarias por empresa |
| 19 | `pre_plantilla_partidas` | `Plp_Cod` | `Plt_Cod`, `Ppa_Cod` | Partidas asociadas a cada plantilla |
| 20 | `pre_reajustes` | `Rea_Cod` | `Emp_Cod`, `Ppe_Cod`, `Ppa_Cod_Origen`, `Pro_Cod_Origen`, `Ppa_Cod_Destino`, `Pro_Cod_Destino`, `Usu_Cod` | Bitácora de reajustes (incremento, reducción, transferencia) |
| 21 | `pre_movimientos` | `Mov_Cod` | `Emp_Cod`, `Ppe_Cod`, `Pro_Cod`, `Ppa_Cod`, `Usu_Cod` | Bitácora de afectaciones externas de otros módulos ERP |
| 22 | `pre_partida_cuenta` | `Ppc_Cod` | `Emp_Cod`, `Pla_Cod`, `Ppa_Cod`, `Pld_Cod`, `Usu_Cod` | Puente partida presupuestaria -> cuenta contable |
| 23 | `pre_umbral_pf` | `Ubp_Cod` | `Emp_Cod`, `Ppa_Cod`, `Usu_Cod` | Umbrales de alerta D8 (PF vs VA) |
| 24 | `pre_bases` | `Bas_Cod` | `Emp_Cod`, `Usu_Cod` | Bases de cálculo del motor presupuestario |
| 25 | `pre_formulas` | `Frm_Cod` | `Emp_Cod`, `Usu_Cod` | Fórmulas presupuestarias parametrizables |
| 26 | `pre_divergencias` | `Dvg_Cod` | `Emp_Cod`, `Ppe_Cod`, `Ppa_Cod`, `Pro_Cod`, `Usu_Cod` | Divergencias D2 y auditoría de alertas |
| 27 | `pre_perfiles` | `Prf_Cod` | `Emp_Cod`, `Pro_Cod`, `Usu_Cod` | Perfiles y parametrización avanzada de proyectos |

Sin necesidad de vistas almacenadas en base de datos (`CREATE VIEW`), la consulta unificada de presupuesto, ejecuciones y disponibles se genera dinámicamente mediante la función PHP `ppto_sql_resumen_subquery()`.

---

## 6. SQL que debes ejecutar

### 6.1 Script DDL completo de las 27 Tablas (`exa_ppto_schema_completo_Emp_Cod.sql`)

Archivos disponibles en disco:

- `c:\xampp\htdocs\Presupuesto\SQL\exa_ppto_schema_completo_Emp_Cod.sql`

Este script contiene las sentencias `CREATE TABLE IF NOT EXISTS` para generar el bloque completo de 27 tablas físicas con llaves foráneas declaradas hacia las maestras del ERP EXA (`empresas`, `usuarios`, `sucursal`, `departamen`, `plan_cuenta`, `det_plan`), sin depender de vistas en base de datos.

---

## 7. Checklist rápido de puesta en marcha

1. Ejecutar `SQL/exa_ppto_schema_completo_Emp_Cod.sql` en la base de datos del tenant.
2. Dar permiso de menú a las 7 pantallas `*_front.php` en **Administrador -> Organizadores / Procesos**.
3. En Proyectos: seleccionar empresa (`Emp_Cod`) -> proyecto -> versión.
4. Guardar ton base / ton costo / tarifa.
5. Cargar o importar rubros.
6. En Cuadro: escenarios de toneladas -> (opcional) precios por año -> Simular ajuste -> Activar partida final -> Aplicar si corresponde.

---

*Generado como documentación operativa actualizada del módulo Presupuesto (27 tablas pre_*). Código fuente canónico: `c:\xampp\htdocs\Presupuesto`.*
