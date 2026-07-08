# Optimización: Eliminación del N+1 en Grid de Ventas (ReportResumen)

## Problema

El jqGrid `ReportResumen` del módulo **fac_con_fac_ven_3.0.php** ejecutaba por cada fila devuelta **4 consultas individuales** a la base de datos:

| Consulta | Propósito | Archivo Origen |
|----------|-----------|----------------|
| `comprobantes.getComprobanteByVetCod` | Obtener código de comprobante | `MODELS/comprobantes.php` |
| `ventas.2` | Determinar si es Contado/Crédito | `MODELS/ventas.php` |
| `ventas.tipo_doc_pago` | Obtener formas de pago | `MODELS/ventas.php` |
| `ventas.getRetencionVet` | Obtener retenciones (Renta/IVA) | `MODELS/ventas.php` |

Con el valor por defecto `rowNum: 1000`, una sola búsqueda generaba:

```
2 consultas principales + (1000 filas × 4 consultas) = ~4002 consultas por búsqueda
```

Esto saturaba el servidor al filtrar por rangos grandes (varios meses), especialmente en empresas con alto volumen de ventas.

---

## Solución Aplicada

### 1. `MODELS/ventas.php` — Caso `setTotales` (líneas 167-216)

Se agregaron **4 LEFT JOINs y subconsultas** directamente en la query paginada para que todos los datos por fila se obtengan en una sola consulta SQL.

#### a) Comprobante (líneas 169-173)

```php
// LEFT JOIN triple: ventas_compr → comprobantes → tipo_asien
$sql->joinLeft('ventas_compr', "ventas_compr.Vet_Cod = $this->_name.Vet_Cod", array())
    ->joinLeft('comprobantes', "comprobantes.Com_Cod = ventas_compr.Com_Cod", array())
    ->joinLeft('tipo_asien', "comprobantes.Tia_Cod = tipo_asien.Tia_Cod", array());
```

Columnas agregadas:
- `Com_Codigo` — Código formateado (`TIA_ABR-MM-NUM`)
- `Com_Exi` — 'S' si existe comprobante
- `Com_Cod` — Código del comprobante
- `Com_Est` — Estado del comprobante

#### b) Forma de Pago (Contado/Crédito) — líneas 175-185

```php
// Subconsulta COUNT agrupada por Vet_Cod
$subPagos = $this->select(false)
    ->from('ccpp_cobrar', array(
        'Vet_Cod',
        'total_pagos' => new Zend_Db_Expr('COUNT(*)')
    ))
    ->group('Vet_Cod');
$sql->joinLeft(
    array('pagos_resumen' => $subPagos),
    "pagos_resumen.Vet_Cod = $this->_name.Vet_Cod",
    array('Forma_Pago' => new Zend_Db_Expr("IF(pagos_resumen.total_pagos > 0, 'Credito', 'Contado')"))
);
```

#### c) Formas de Pago (GROUP_CONCAT) — líneas 187-198

```php
$subTiposPago = $this->select(false)
    ->from('pago_venta', array(
        'Vet_Cod',
        'FormasPago' => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT tipos_pago.Pag_Des ORDER BY tipos_pago.Pag_Des SEPARATOR ', ')")
    ))
    ->joinLeft('tipos_pago', 'tipos_pago.Pag_Cod = pago_venta.Pag_Cod', array())
    ->group('Vet_Cod');
$sql->joinLeft(
    array('tipos_pago_agr' => $subTiposPago),
    "tipos_pago_agr.Vet_Cod = $this->_name.Vet_Cod",
    array('FormasPago')
);
```

#### d) Retenciones (Tot_Renta, Tot_Iva) — líneas 200-216

```php
// Dos LEFT JOINs a renta_iva (renta_imp e iva_imp)
$sql->joinLeft(
    array('renta_imp' => 'renta_iva'),
    'renta_imp.Ren_Cod = ventas_det.Ren_Cod',
    array()
)->joinLeft(
    array('iva_imp' => 'renta_iva'),
    'iva_imp.Ren_Cod = ventas_det.Ren_Iva',
    array()
);
$sql->addCols(null, array(
    'Tot_Renta' => new Zend_Db_Expr($this->castDecimal(
        "SUM(IF(ventas_det.Ren_Cod IS NOT NULL, IF(renta_imp.Ren_Por > 0, ($this->Importe_Descu * renta_imp.Ren_Por / 100), 0), 0))"
    )),
    'Tot_Iva'   => new Zend_Db_Expr($this->castDecimal(
        "SUM(IF(ventas_det.Ren_Iva IS NOT NULL, IF(iva_imp.Ren_Por > 0 AND Iva_Por != 0, ($this->IVA * iva_imp.Ren_Por / 100), 0), 0))"
    ))
));
```

La guarda `if (!$sql->hasTable("ventas_compr"))` evita duplicar JOINs si ya existen en la consulta base.

---

### 2. `fac_con_fac_ven_3.0.php` — Loop PHP simplificado (líneas 166-171)

**Antes** (archivo `fac_con_fac_ven_3.0-old_13-01-25.php:30-44`):

```php
foreach ($response['rows'] as &$row) {
    $comprobante = $obBD_con1->getRowConsulta('comprobantes.getComprobanteByVetCod', $row['Vet_Cod']);
    if (!is_null($comprobante)) {
        $row['Com_Codigo'] = $comprobante['Com_Codigo'];
    }
    $pagos = $obBD_con1->getRowConsulta("ventas.2", $row['Vet_Cod']);
    $row['Forma_Pago'] = ($pagos['total'] > 0) ? 'Credito' : 'Contado';
    if (!empty($row['Ret_Num'])) {
        $ret_data = $obBD_con1->getRowConsulta('ventas.getRetencionVet', $row['Vet_Cod']);
        $row = array_merge(array('Tot_Iva' => $ret_data['Tot_Iva'], 'Tot_Renta' => $ret_data['Tot_Renta']), $row);
        $response['userdata']['Tot_Renta'] += ($ret_data['Tot_Renta'] * 1);
        $response['userdata']['Tot_Iva'] += ($ret_data['Tot_Iva'] * 1);
    }
}
```

**Después** (archivo actual `fac_con_fac_ven_3.0.php:166-171`):

```php
foreach ($response['rows'] as &$row) {
    $factorNC = (isset($row['Tic_Sri']) && $row['Tic_Sri'] === '04') ? -1 : 1;
    $response['userdata']['Tot_Renta'] += ($row['Tot_Renta'] * 1 * $factorNC);
    $response['userdata']['Tot_Iva'] += ($row['Tot_Iva'] * 1 * $factorNC);
}
```

Se eliminaron las 4 consultas por fila y se reemplazaron con simples sumas aritméticas de campos que ya vienen desde SQL.

---

### 3. `fac_con_fac_ven_3.0.php` — Reducción de página (líneas 2626-2627)

**Antes:**

```javascript
rowNum: 1000,
rowList: [1000, 5000, 10000, 15000, 20000],
```

**Después:**

```javascript
rowNum: 100,
rowList: [100, 250, 500, 1000, 5000],
```

Se redujo el tamaño de página por defecto de 1000 a 100 filas, y se ajustaron las opciones del selector de páginas.

---

## Impacto

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Consultas SQL por búsqueda (100 filas) | ~402 | ~2 | **99.5%** |
| Consultas SQL por búsqueda (1000 filas) | ~4002 | ~2 | **99.95%** |
| Tiempo estimado (1000 filas, servidor típico) | 5-30 s | 0.2-1 s | ~95% |
| Ancho de banda BD / consultas | 4002 consultas | 2 consultas | Eliminación del overhead TCP |

---

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `MODELS/ventas.php:165-216` | Agregados LEFT JOINs y subconsultas en caso `setTotales` |
| `facturacion/FRONT/fac_con_fac_ven_3.0.php:166-171` | Loop PHP simplificado, eliminadas llamadas N+1 |
| `facturacion/FRONT/fac_con_fac_ven_3.0.php:2626-2627` | `rowNum: 100` y `rowList` reducido |

---

## Verificación de sintaxis

```bash
php -l MODELS/ventas.php
php -l facturacion/FRONT/fac_con_fac_ven_3.0.php
```

Ambos archivos pasaron sin errores.

---

## Pendiente

- **Ejecutar prueba real** contra la base de datos del cliente con mayor volumen de ventas para validar tiempos y resultados.
- **Considerar cachear `isSummary`** con APCu para empresas con millones de registros (la consulta de totales globales se ejecuta en cada request).
- **Monitorear en producción** con DebugBar, bajando el threshold a 200ms en `Librerias/config.php/debugbar.php`.

---

## Patrón similar detectado

El módulo de **compras** (`facturacion/FRONT/fac_con_fac_com_3.0.php:130`) tiene el mismo patrón N+1 con `getComprobanteByCopCod`. Pendiente de aplicar optimización similar si es necesario.
