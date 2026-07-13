# Design System — EXA Relavera

## Context and Goals

EXA Relavera es un sistema de gestión y trazabilidad contable construido con Next.js 16, React 19, Tailwind CSS v4 y shadcn/ui. Este design system documenta los tokens, componentes, patrones de interacción y estándares de accesibilidad que rigen la interfaz.

**Audiencia**: desarrolladores frontend, diseñadores UI/CTO.

**Superficie**: dashboard administrativo, login, módulos contables (compras, facturación, tesorería, inventario, RRHH, etc.).

---

## Design Tokens and Foundations

### Color Palette — Yale Blue + Slate Grey

| Token | Valor | Uso |
|---|---|---|
| `--color-primary` | `#14a7eb` | Acciones principales, links, estados activos |
| `--color-secondary` | `#9a657d` | Acentos secundarios, badges |
| `--color-info` | `#43b8ef` | Información, tooltips |
| `--color-success` | `#14a7eb` | Estados exitosos (mismo que primary) |
| `--color-warning` | `#e0731f` | Advertencias, alertas moderadas |
| `--color-error` | `#d52a38` | Errores, validación, destructive |
| `--color-dark` | `#1e2a35` | Texto principal, headings |
| `--color-muted` | `#5a6b7a` | Texto secundario, placeholders |
| `--color-border` | `#b8c9d8` | Bordes generales, separadores |
| `--color-bg-light` | `#eef2f7` | Fondos alternos, hover states |
| `--color-light` | `#e8f1fa` | Fondos de acento, sidebar accent |
| `--background` | `#f3f6fa` | Fondo de página |
| `--card` | `#ffffff` | Fondo de tarjetas |
| `--muted` | `#eef2f7` | Fondos atenuados |
| `--accent` | `#f0e8ec` | Acentos suaves |

### Tailwind Semantic Tokens

| Clase | Variable CSS | Valor |
|---|---|---|
| `text-dark` | `--color-dark` | `#1e2a35` |
| `text-muted-foreground` | `--muted-foreground` | `#5a6b7a` |
| `text-primary` | `--primary` | `#14a7eb` |
| `text-link` | `--color-link` | `#1e2a35` |
| `bg-background` | `--background` | `#f3f6fa` |
| `bg-card` | `--card` | `#ffffff` |
| `bg-muted` | `--muted` | `#eef2f7` |
| `bg-lightprimary` | `--color-lightprimary` | primary 10% opacity |
| `bg-lighterror` | `--color-lighterror` | error 10% opacity |
| `border-border` | `--border` | `#b8c9d8` |
| `ring-ring` | `--ring` | `#14a7eb` |

### Charts

| Token | Valor | Uso |
|---|---|---|
| `--chart-1` | `#14a7eb` | Yale Blue |
| `--chart-2` | `#9a657d` | Lavender Blush |
| `--chart-3` | `#e0731f` | Peach Glow |
| `--chart-4` | `#d52a38` | Tomato Jam |
| `--chart-5` | `#43b8ef` | Yale Blue Light |

### Typography

| Fuente | Variable CSS | Uso | Peso |
|---|---|---|---|
| **Orbitron** | `--font-orbitron` | Headings display, títulos de marca | 400-900 |
| **Space Grotesk** | `--font-space-grotesk` | Body text, UI, labels | 400-700 |
| **Share Tech Mono** | `--font-share-tech-mono` | Datos monospace, telemetría, códigos | 400 |

**Tailwind mapping:**
- `font-sans` → Space Grotesk
- `font-heading` → Orbitron
- `font-mono` → Share Tech Mono

**Escala de tamaños (Tailwind defaults):**
- `text-xs` → 12px
- `text-sm` → 14px (base del sistema)
- `text-base` → 16px
- `text-lg` → 18px
- `text-xl` → 20px
- `text-2xl` → 24px
- `text-3xl` → 30px

### Spacing

| Token Tailwind | Valor |
|---|---|
| `gap-30` | 30px |
| `padding-15` | 15px |
| `padding-30` | 30px |
| `margin-30` | 30px |

### Border Radius

| Token | Cálculo | Valor aprox. |
|---|---|---|
| `--radius` | base | 0.625rem (10px) |
| `radius-sm` | base × 0.6 | 6px |
| `radius-md` | base × 0.8 | 8px |
| `radius-lg` | base | 10px |
| `radius-xl` | base × 1.4 | 14px |
| `radius-2xl` | base × 1.8 | 18px |

### Shadows

| Token | Valor |
|---|---|
| `shadow-boxShadow` | `7px 7px 10px rgba(0,0,0,.03)` |
| `shadow-btn-shadow` | `rgba(0,0,0,0.05) 0 9px 17.5px` |

---

## Component Rules

### Button (`components/ui/button.tsx`)

**Anatomy**: label + optional leading/trailing icon + optional badge.

**Variants:**

| Variante | Estilos | Uso |
|---|---|---|
| `default` | `bg-primary text-white` | Acción principal (submit, guardar) |
| `outline` | `border-border bg-background` | Acciones secundarias, cancelar |
| `secondary` | `bg-secondary text-white` | Acciones alternativas |
| `ghost` | `hover:bg-muted` | Navegación, toggles |
| `destructive` | `bg-destructive/10 text-destructive` | Eliminar, confirmar acción peligrosa |
| `link` | `text-primary underline` | Navegación inline |

**Sizes:**

| Tamaño | Altura | Uso |
|---|---|---|
| `xs` | 24px | Badges, inline actions |
| `sm` | 28px | Actions en tablas |
| `default` | 32px | Uso general |
| `lg` | 36px | CTAs principales |
| `icon` | 32px | Solo icono |
| `icon-sm` | 28px | Icono pequeño |
| `icon-lg` | 36px | Icono grande |

**States (required):**

| Estado | Comportamiento |
|---|---|
| **default** | Color sólido, sombra sutil |
| **hover** | Oscurece 10%, sombra aumenta (`hover:bg-primary/90`) |
| **focus-visible** | Ring azul 3px con 50% opacidad (`focus-visible:ring-3 focus-visible:ring-ring/50`) |
| **active** | Oscurece 20%, sombra reduce (`active:bg-primary/80 active:translate-y-px`) |
| **disabled** | `opacity-50 pointer-events-none` |
| **loading** | Spinner `Loader2 animate-spin` + texto "Cargando..." |
| **invalid** | `aria-invalid:border-destructive aria-invalid:ring-destructive/20` |

**Keyboard:**
- `Tab` → focus visible
- `Enter` / `Space` → trigger
- `Escape` → close dropdown si aplica

**Responsive:** se adapta al contenedor. En mobile, `lg` se reduce a `default`.

---

### Input (`components/ui/input.tsx`)

**Anatomy**: container + border + optional leading icon + optional trailing action.

**States:**

| Estado | Comportamiento |
|---|---|
| **default** | `border-input bg-transparent`, texto `text-foreground` |
| **hover** | Sin cambio visible (solo focus) |
| **focus-visible** | `border-ring ring-3 ring-ring/50` |
| **disabled** | `bg-input/50 opacity-50 cursor-not-allowed` |
| **invalid** | `border-destructive ring-destructive/20` |
| **placeholder** | `text-muted-foreground` |

**Keyboard:**
- `Tab` → focus
- `Escape` → blur (si en dialog)
- `Enter` → submit form

**Long content:** `overflow-hidden text-ellipsis` (default por HTML).

---

### Select (`components/ui/select.tsx`)

**Anatomy:** trigger (button) + popup (list of items).

**States:**

| Estado | Comportamiento |
|---|---|
| **default** | `border-input bg-transparent` |
| **focus-visible** | `border-ring ring-3 ring-ring/50` |
| **placeholder** | `text-muted-foreground` |
| **item hover** | `bg-accent text-accent-foreground` |
| **item selected** | Check icon visible |

**Keyboard:**
- `Tab` → focus trigger
- `Enter` / `Space` → open popup
- `Arrow Up/Down` → navigate items
- `Enter` → select item
- `Escape` → close popup

---

### Checkbox (`components/ui/checkbox.tsx`)

**Anatomy:** checkbox input + indicator (check icon).

**States:**

| Estado | Comportamiento |
|---|---|
| **unchecked** | `border-primary shadow-sm` |
| **checked** | `bg-primary text-primary-foreground` |
| **focus-visible** | `ring-1 ring-ring` |
| **disabled** | `opacity-50 cursor-not-allowed` |

**Keyboard:**
- `Tab` → focus
- `Space` → toggle

---

### Label (`components/ui/label.tsx`)

**Anatomy:** text label associated with form control.

**Estilos:** `text-sm font-medium leading-none`.

**Peer relationship:** `peer-disabled:cursor-not-allowed peer-disabled:opacity-70`.

---

### Card

**Patrón de uso en dashboard:**

```tsx
<Card className="shadow-boxShadow">
  <CardHeader>
    <CardTitle className="card-title">Título</CardTitle>
  </CardHeader>
  <CardContent>...</CardContent>
</Card>
```

**Tokens:**
- Background: `bg-card` (#ffffff)
- Border: `border border-border`
- Shadow: `shadow-boxShadow`
- Title: `card-title` (font-size: 1.125rem, font-weight: 600, color: var(--color-dark))

---

### Sidebar (`components/layout/sidebar/Sidebar.tsx`)

**Layout:** columna fija, ancho 256px (w-64), fondo blanco.

**Estructura:**
1. Logo container (centrado, padding, border-bottom)
2. Navigation (SimpleBar scrollable)
3. Footer badge (gradiente primary→blue-600)

**Item states:**

| Estado | Clases | Visual |
|---|---|---|
| **default** | `text-link` | Texto oscuro |
| **hover** | `hover:bg-lightprimary hover:text-primary` | Fondo azul claro, texto azul |
| **active** | `bg-primary text-white shadow-md shadow-primary/30` | Fondo azul sólido, texto blanco, sombra |
| **child active** | `bg-lightprimary text-primary` | Fondo azul claro |

**Subheadings:** `text-xs font-bold uppercase tracking-wider text-sidebar-muted`.

**Sub-items:** indentados con `pl-4`, punto indicator `h-1.5 w-1.5 rounded-full bg-current`.

---

### Header (`components/layout/header/Header.tsx`)

**Layout:** horizontal, fondo `bg-white`, border-bottom, padding `px-6 py-4`.

**Elementos:**
- Page title: `text-dark font-semibold text-lg`
- Subtitle: `text-muted-foreground text-sm`
- Search input: `bg-lightgray` (background #f3f6fa)
- Action icons: `text-link`

---

## Login Page (`app/login/page.tsx`)

**Layout:** pantalla completa centrada, fondo `bg-background`.

**Panel:**
- Background: `bg-card` (white)
- Border: `border border-border`
- Shadow: `shadow-boxShadow`
- Radius: `rounded-2xl`
- Max-width: 448px (`max-w-md`)
- Padding: 32px (`p-8`)

**Logo:**
- Container: `w-16 h-16 rounded-full bg-lightprimary`
- Text: `text-primary text-2xl font-bold`

**Inputs:**
- Background: `bg-transparent` (hereda del Input component)
- Icons: `text-muted-foreground` (User, Lock, Building2)
- Focus: `border-ring ring-ring/50`

**Error banner:**
- Background: `bg-lighterror`
- Text: `text-error`
- Border: `border-error/20`
- Icon: `AlertCircle`

**Button:** variante `default`, full-width, h-11.

---

## Accessibility Requirements

### WCAG 2.2 AA Compliance

| Criterio | Requisito | Test |
|---|---|---|
| **Contraste texto** | Mínimo 4.5:1 para texto normal | Chrome DevTools contrast checker |
| **Contraste texto grande** | Mínimo 3:1 para texto ≥18px bold o ≥24px | Chrome DevTools |
| **Contraste UI** | Mínimo 3:1 para componentes no-texto (bordes, iconos) | Manual inspection |
| **Focus visible** | Indicador de foco visible en todos los interactive elements | `Tab` navigation |
| **Keyboard navigation** | Todos los interactive elements accesibles via teclado | `Tab`, `Enter`, `Space`, `Arrow keys` |
| **Touch targets** | Mínimo 44×44px para elementos táctiles | Inspección manual |
| **Color no-sole** | La información no debe comunicarse solo por color | Test con escala de grises |

### Focus Rules

- Todos los inputs: `focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50`
- Todos los buttons: `focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50`
- Sidebar items: focus visible via `focus-visible:ring-2 focus-visible:ring-ring`
- Skip link: "Ir al contenido principal" (implementar)

### ARIA Labels

| Componente | Atributo requerido |
|---|---|
| Input | `aria-label` o `Label` con `htmlFor` |
| Button | `aria-label` si solo tiene icono |
| Select | `aria-label` en trigger |
| Checkbox | `aria-label` o `Label` asociado |
| Dialog | `aria-modal="true"`, `aria-labelledby` |
| Sidebar | `nav` con `aria-label="Navegación principal"` |

### Screen Reader

- Todos los headings deben tener jerarquía correcta (h1 → h2 → h3)
- Imágenes deben tener `alt` descriptivo
- Loading states deben anunciar `aria-busy="true"`
- Error messages deben estar asociados via `aria-describedby`

---

## Content and Tone Standards

### Voice

- **Conciso**: mensajes cortos y directos
- **Profesional**: tono corporativo, sin informalidad
- **Acción-oriented**: verbos en infinitivo o imperativo

### Labels

| Tipo | Formato | Ejemplo |
|---|---|---|
| Botones | Verbo + sustantivo (imperativo) | "Guardar Cliente", "Eliminar registro" |
| Errores | "Error de [descripción]" | "Error de conexión al servidor" |
| Placeholders | "Ingrese su [campo]" | "Ingrese su usuario" |
| Empty states | "[Objeto] no encontrado(s)" | "No se encontraron empresas" |

### Number Formatting

- Moneda: `$1,234.56`
- Fechas: `DD/MM/YYYY`
- Horas: `HH:MM`

---

## Anti-Patterns and Prohibited

| Prohibido | Razón |
|---|---|
| Usar colores hex hardcodeados en TSX en vez de tokens | Rompe la consistencia del tema |
| Usar clases `dark:` | Dark mode deshabilitado |
| Texto sin contraste mínimo 4.5:1 | Viola WCAG AA |
| Inputs sin `Label` asociado | Inaccesible para screen readers |
| Buttons sin `aria-label` cuando solo tienen icono | Inaccesible |
| Focus solo con `outline: none` sin reemplazo | Elimina indicador de foco |
| Usar `text-white` sobre fondos claros | Contraste insuficiente |
| Animaciones sin `prefers-reduced-motion` | Experiencia negativa para usuarios con vértigo |

---

## QA Checklist

- [ ] Todos los botones tienen estados hover, focus-visible, active, disabled
- [ ] Todos los inputs tienen Label asociado con `htmlFor`
- [ ] Focus ring visible en todos los interactive elements
- [ ] Contraste texto ≥ 4.5:1 verificado con DevTools
- [ ] Contraste UI elements ≥ 3:1
- [ ] Navegación completa por teclado (Tab, Enter, Space, Escape)
- [ ] Touch targets ≥ 44×44px
- [ ] Heading hierarchy correcta (sin saltos h1→h3)
- [ ] Loading states anuncian `aria-busy`
- [ ] Error messages asociados via `aria-describedby`
- [ ] No hay clases `dark:` en componentes TSX
- [ ] No hay colores hex hardcodeados (usar tokens del tema)
- [ ] Sidebar items con hover/active states correctos
- [ ] Login con validación inline y error banners accesibles
- [ ] Responsive behavior en todos los breakpoints
- [ ] Empty states manejados con mensajes descriptivos
- [ ] Long content truncado correctamente en tables/cards
