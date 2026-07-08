# Plan de Migración: PHP 5.3.8 → PHP 8.2.32

**Proyecto:** ERP EXA Contable Relavera
**Entorno local:** PHP 8.2.32 (scoop) + PHP 7.1.14 (C:\php71)
**Producción:** Plesk (PHP 7.1.14 → planificado 8.2)

## Progreso General

| Fase | Estado | Descripción |
|------|--------|-------------|
| 1a | ✅ Completo | `$var{index}` → `$var[index]` en MPDF57 (×2), TCPDF, Smarty |
| 1b | ✅ Completo | `each()` → `foreach` — solo 1 real en slider/MPDF57 (fixeado) |
| 1c | ✅ Completo | NuSOAP dirs eliminados — no aplica |
| 2 | ✅ Completo | Librerías: TCPDF continue fix, todos los archivos clave pasan lint en PHP 8.2 |
| 3a | ✅ Completo | `utf8_encode/decode` → `mb_convert_encoding()` — 14 archivos fixeados (wrappers + calls directos) |
| 3b | ✅ N/A | `${var}` en strings — no existe en el código (solo JS template literals, inofensivos) |
| 3c | ⏳ Pendiente | `var $prop` → visibilidad explícita (~hundreds en librerías + DATA/) |
| 3d | ⏳ Pendiente | Propiedades dinámicas → `#[AllowDynamicProperties]` |
| 4 | ⏳ Pendiente | Pruebas módulo por módulo (21 módulos) |

## Fase 1a — $var{index} → $var[index] ✅

### Archivos modificados
- `Librerias/MPDF57/mpdf.php` — 128 ocurrencias reemplazadas
- `Librerias/slider/MPDF57/mpdf.php` — 128 ocurrencias reemplazadas
- `Librerias/TCPDF/tcpdf.php` — 3 ocurrencias reemplazadas
- `Librerias/Smarty/Smarty.class.php` — 4 ocurrencias reemplazadas

### Regex usado
```
(?<=\$[\w\[\]''""\->]*)(?<!->)(?<!::)\{([^};\n]+)\}
```
Reemplazar con: `[$1]`

### Validación
- ✅ `php -l` pasa en PHP 8.2.32 para los 4 archivos
- ✅ `$this->{$v[1]}` (variable property) intacto
- ✅ Código `if(){` no se rompió

## Fase 1b — each() → foreach (App)

### Por hacer
- Identificar archivos con PHP `each()` (no jQuery `$.each()`)
- Reemplazar patrón `while (list($k, $v) = each($arr))` → `foreach ($arr as $k => $v)`
- Reemplazar `list($v) = each($arr)` → `foreach ($arr as $v)`
- Manejar casos complejos (each fuera de while, each con manejo de pointer)

## Fase 1c — each() → foreach (NuSOAP)

### Por hacer
- `Librerias/nusoap/nusoap.php` (≈18 calls)
- `Librerias/FactElect/nuSoap/nusoap.php` (≈18 calls)
- `WS/libs/nuSoap/nusoap.php` (≈18 calls)

## Fase 2 — Librerías legacy (híbrido)

### MPDF57
- ✅ `$var{index}` parcheado (Fase 1a)
- ⏳ `each()` pendiente
- ⏳ `continue` targeting switch warning (TCPDF también)
- Si falla: migrar a mPDF 8.x

### TCPDF
- ✅ `$var{index}` parcheado (Fase 1a)
- ⏳ `each()` pendiente
- ⏳ `continue` targeting switch warning (línea 17778)
- Si falla: actualizar a TCPDF 6.x

### Smarty
- ✅ `$var{index}` parcheado (Fase 1a)
- ⏳ `each()` pendiente
- Si falla: migrar a Smarty 5.x

### PHPMailer
- ✅ Ya pasa lint en PHP 8.2
- Solo probar

### NuSOAP
- ⏳ `each()` pendiente (Fase 1c)
- Si falla: reemplazar con soapclient nativo de PHP

## Fase 3 — Deprecados (no bloqueantes, orden recomendado)

### 3a. utf8_encode/decode → mb_convert_encoding
- ~455 calls en toda la app
- `utf8_encode($s)` → `mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1')`
- `utf8_decode($s)` → `mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8')`

### 3b. ${var} → {$var} en strings
- ~86 matches
- `"${var}"` → `"{$var}"`

### 3c. var $prop → public/protected/private
- ~150+ archivos con declaraciones `var $prop;`
- Reemplazar `var $` → `public $`

### 3d. Propiedades dinámicas → #[AllowDynamicProperties]
- ~81 accesos a propiedades dinámicas
- Agregar atributo en clases base

## Fase 4 — Pruebas por módulo

Orden recomendado:
1. administrador
2. facturacion
3. tesoreria
4. contabilidad
5. relavera
6. rrhh
7. bananero
8. transportecarga
9. camaronera
10. bodega
11. caja_chica
12. compras
13. activosfijos
14. auditoria
15. WS
16. api
17. classes
18. MODELS
19. DATA
20. index.php (login)
21. Módulos menores

## Comandos útiles

```bash
# Lint PHP 8.2
php -l archivo.php

# Lint PHP 7.1
& "C:\php71\php.exe" -c "C:\php71\php.ini" -l archivo.php

# Buscar each() PHP (no jQuery)
rg "while\s*\([^)]*each\(" --include="*.php"

# Buscar utf8_encode
rg "utf8_encode" --include="*.php"

# Buscar var $prop
rg "^\s*var\s+\$" --include="*.php"
```

## Notas
- Las librerías se parchán in situ (no se actualizan a versiones modernas) para minimizar riesgo
- vendor_modern/ tiene paquetes que requieren PHP ≥8.4.1, incompatible con 8.2.32
- api/index.php tiene file_exists guard para saltar vendor_modern si no existe
- La app usa ~2.758 archivos PHP (monolítico + procedural + clases legacy)
