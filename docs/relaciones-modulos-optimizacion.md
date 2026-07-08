# Relaciones entre Módulos: Impacto en Optimización

> Documento de referencia para entender cómo las dependencias entre módulos
> afectan cualquier esfuerzo de optimización en ventas (ventas) y compras (compras).

---

## Arquitectura General: Hub-and-Spoke

```
VENTAS ──→ ventas_compr ──→ CONTABILIDAD (comprobantes → asientos)
   │                          ↑
   ├──→ kardex_ie ────────────┤  (via ventas_costo)
   ├──→ ccpp_cobrar → TESORERIA (cuentas por cobrar)
   ├──→ pago_venta → TESORERIA
   ├──→ stock (updateStock())
   └──→ producto (Pro_Stk, Pro_Prp)

COMPRAS ──→ compr_auto ──→ CONTABILIDAD (comprobantes → asientos)
   │
   ├──→ kardex_ie
   ├──→ ccpp_pagar → TESORERIA (cuentas por pagar)
   ├──→ stock (mismo updateStock())
   ├──→ producto
   └──→ requisiciones (Req_Cod opcional)
```

---

## Tablas Puente Críticas

| Tabla | Conecta | Dirección | Archivos Clave |
|-------|---------|-----------|----------------|
| `ventas_compr` | `Ventas.Vet_Cod ↔ Comprobantes.Com_Cod` | Bidireccional | `MODELS/ventas.php:169-171`, `contabilidad/LOGICA/con_sql_compr.php:285+` |
| `compr_auto` | `Compras.Cop_Cod ↔ Comprobantes.Com_Cod` | Bidireccional | `adquisiciones/LOGICA/tes_sql_ccpp.php:2277` |
| `ventas_costo` | `Ventas.Vet_Cod ↔ Comprobantes.Com_Cod` | Ventas → Contab. | `MODELS/comprobantes.php:101`, `MODELS/ventas.php:413` |
| `kardex_ie` | `Ventas.Vet_Cod \| Compras.Cop_Cod \| Aju_Cod` | Origen-agnóstico | `inventario/LOGICA/inv_sql_inventario.php:150-160`, `MODELS/kardex_ie.php:4-6` |
| `ccpp_cobrar` | `Ventas.Vet_Cod → Tesorería (AR)` | Ventas → Tesorería | `MODELS/ventas.php:174-176`, `tesoreria/LOGICA/ccxcc_sql_estado_cuenta.php:47-135` |
| `ccpp_pagar` | `Compras.Cop_Cod → Tesorería (AP)` | Compras → Tesorería | `MODELS/compras.php:162-169` |
| `comprobantes` | Hub central contable | Todos → Contab. | `MODELS/comprobantes.php:110-124` (UNION query) |

---

## Puntos de Acoplamiento que Afectan la Optimización

### 1. `updateStock()` — Hook compartido Ventas ↔ Compras

**Archivo:** `facturacion/LOGICA/fac_log_factu_prueba.php:201-227`

Una sola función maneja el inventario para ambos módulos:

| Parámetro | Venta (Egreso) | Compra (Ingreso) |
|-----------|---------------|-------------------|
| `IoE` | `'E'` | `'I'` |
| Stock | Resta (`Stk_Can -= Kar_Sal`) | Suma (`Stk_Can += Kar_Can`) |
| Precio prom. | Actualiza `Pro_Prp` | Actualiza `Pro_Prp` |
| Kardex | Inserta con `Vet_Cod` | Inserta con `Cop_Cod` |

**Regla de optimización:** Cualquier cambio en `updateStock()` debe probarse en ambos flujos.

---

### 2. Grid contable con UNION de Ventas + Compras

**Archivo:** `MODELS/comprobantes.php:110-124`

```sql
SELECT ... FROM comprobantes
LEFT JOIN ventas_compr / ventas
UNION
SELECT ... FROM comprobantes
LEFT JOIN compr_auto / compras
```

**Regla de optimización:** Índices en `ventas.Vet_Cod` y `compras.Cop_Cod` benefician este UNION. No se puede optimizar un lado sin considerar el otro.

---

### 3. Cálculo de `Com_Exi` (¿Contabilizado?) usado transversalmente

**Definido en:** `MODELS/ventas.php:61` y `MODELS/ventas.php:491`

```sql
IF(ventas_compr.Com_Cod IS NULL, 'N', 'S') AS Com_Exi
```

**Usado en:** Ventas grid, tesorería (`tes_sql_ord_trab.php:285`), reports contables.

**Regla de optimización:** Si se cambia la lógica de `Com_Exi` (ej: agregar caché), actualizar todos los consumidores. Es un campo calculado en SQL, no almacenado.

---

### 4. Determinación Contado/Crédito vía `ccpp_cobrar` / `ccpp_pagar`

**Definido en:** `MODELS/ventas.php:174-176`

```sql
IF(ccpp_cobrar.Cpc_Cod IS NULL, 'Contado', 'Credito') AS Pago
```

**Problema:** `ccpp_cobrar` se consulta en:
- `MODELS/ventas.php:174-176` (grid ventas)
- `MODELS/cliente.php:62-67` (estado cliente)
- `tesoreria/LOGICA/ccxcc_sql_estado_cuenta.php` (reportes)

**Regla de optimización:** Cualquier optimización de N+1 en ventas que involucre `ccpp_cobrar` debe replicarse en los otros consumidores.

---

### 5. Kardex — Tabla polimórfica con origen en 3 módulos

**Archivo:** `MODELS/kardex_ie.php:4-6`
**PK:** `(Kar_Int, Vet_Cod, Iva_Cod, Aju_cod, Vnd_Cod, Cop_Cod, Pro_Cod)`

**Clasificación de origen:** `inventario/LOGICA/inv_sql_inventario.php:150-160`

```sql
IF(kardex_ie.Vet_Cod!=0, 'Venta',
   IF(kardex_ie.Cop_Cod!=0, 'Compra',
   IF(kardex_ie.Aju_Cod!=0, 'Ajuste', 'Ninguno'))) AS Doc
```

**Regla de optimización:** Índices compuestos sugeridos:
- `(Pro_Cod, Vet_Cod)` — para consultas de ventas
- `(Pro_Cod, Cop_Cod)` — para consultas de compras
- `(Vet_Cod, Kar_Int)` — para detalle de una venta específica

No particionar por `Vet_Cod` sin considerar que `Cop_Cod` y `Aju_Cod` también están en la misma tabla.

---

### 6. Endpoints duplicados Comprobantes/Retenciones

**Archivo:** `api/v1/facturacion/comprobantes.php`

| Endpoint | Líneas | Contraparte Retención |
|----------|--------|----------------------|
| `comprobantes/:Vet_Cod/autorizar` | 258-412 | `retenciones/:Ret_Cod/autorizar` (613-756) |
| `comprobantes/:Vet_Cod/re-autorizar` | 201-254 | `retenciones/:Ret_Cod/re-autorizar` (559-609) |
| `comprobantes/:Vet_Cod/estado-sri` | 157-197 | `retenciones/:Ret_Cod/estado-sri` (517-555) |
| `comprobantes/:Vet_Cod/xml` | 122-134 | `retenciones/:Ret_Cod/xml` (468-486) |
| `comprobantes/:Vet_Cod/ride` | 136-153 | `retenciones/:Ret_Cod/ride` (490-513) |

**Regla de optimización:** Cualquier refactor debe aplicar el cambio en ambos bloques simultáneamente, o extraer a una clase genérica `DocumentoElectronico`.

---

### 7. Filtro por sesión (`Emp_Cod`, `Suc_Cod`) en todos los módulos

**Presente en:** `MODELS/ventas.php:136`, `MODELS/compras.php`, `MODELS/kardex_ie.php:32`, `MODELS/cliente.php`

```php
$sql->where("cliente.Emp_Cod=?", $_SESSION['Ses_Emp_Cod']);
```

**Regla de optimización:** Cualquier propuesta de particionamiento/shrading por empresa debe considerar que la sesión es el mecanismo de aislamiento. Tablas temporales por `Emp_Cod` beneficiarían a todos los módulos.

---

### 8. `confi_fact` y `llave_elect` — Configuración compartida

| Tabla | Propósito | Dónde se consulta |
|-------|-----------|-------------------|
| `confi_fact` | Config. facturación (prod/test) | `emitir.php:439`, `comprobantes.php:342` |
| `llave_elect` | Firma electrónica (.p12) | `emitir.php:430`, `comprobantes.php:333` |

**Regla de optimización:** Son candidatos ideales para APCu. Cambian rara vez. Cachearlos elimina 2 consultas por cada autorización, y aplica a ventas y retenciones por igual.

---

## Resumen: Reglas de Optimización

| # | Regla | Alcance |
|---|-------|---------|
| 1 | Cambios en `updateStock()` deben probar ventas Y compras | Ventas ↔ Compras |
| 2 | Índices en tablas puente benefician múltiples módulos simultáneamente | Todos |
| 3 | Optimizaciones de N+1 en ventas → buscar patrón similar en compras y tesorería | Ventas ↔ Compras ↔ Tesorería |
| 4 | Refactor de endpoints debe aplicar a comprobantes Y retenciones a la vez | API |
| 5 | `confi_fact` y `llave_elect` son cacheables sin riesgo de incoherencia | API Ventas + Retenciones |
| 6 | `comprobantes` tabla central: cambios en su estructura afectan reports contables | Contabilidad |
| 7 | El aislamiento por `Emp_Cod` vía sesión permite optimizaciones horizontales | Todos |
| 8 | No particionar `kardex_ie` por origen sin considerar los otros dos orígenes | Inventario |

---

## Checklist: Antes de Optimizar

- [ ] ¿El cambio afecta `updateStock()`? → Probar ventas + compras
- [ ] ¿El cambio agrega/quita columnas en `ventas`? → Verificar `compras` (patrón simétrico)
- [ ] ¿El cambio modifica la query de grid? → Revisar `comprobantes` UNION
- [ ] ¿El cambio involucra `ccpp_cobrar`? → Revisar `MODELS/cliente.php` y tesorería
- [ ] ¿El cambio es en un endpoint REST? → Aplicar a comprobantes y retenciones
- [ ] ¿El cambio introduce caché? → Asegurar invalidación cuando el otro modulo escribe
