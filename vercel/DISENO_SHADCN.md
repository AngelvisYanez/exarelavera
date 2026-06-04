# Guía de Diseño de Componentes Shadcn/ui
## Estética: Futurista Cyber-Industrial de Alta Precisión (EXA Relavera)

Este documento establece las directrices de diseño y parámetros de implementación para los componentes de **Shadcn/ui** (utilizando **Tailwind CSS v4** y **Base UI**) dentro de **EXA Relavera**. El objetivo es lograr una interfaz cohesiva, altamente técnica, legible y memorable que evoque un panel de telemetría industrial moderno y futurista.

---

## 1. Sistema de Tipografía Futurista

La tipografía es el núcleo de la identidad visual de la aplicación. Se han configurado tres familias de fuentes de Google Fonts en `app/layout.tsx` y mapeado en `app/globals.css`:

| Tipo de Fuente | Familia Font | Variable CSS | Propósito | Estética |
| :--- | :--- | :--- | :--- | :--- |
| **Display / Headings** | **Orbitron** | `--font-orbitron` | Títulos (`h1` a `h6`), títulos de Cards, KPIs principales y logotipos. | Geométrica, angular, inspirada en paneles de control de ciencia ficción. |
| **Sans / UI / Body** | **Space Grotesk** | `--font-space-grotesk` | Texto de lectura, descripciones, etiquetas de formularios y UI general. | Moderna, ultra-limpia con rasgos de diseño técnico no convencionales. |
| **Monospace / Datos** | **Share Tech Mono** | `--font-share-tech-mono`| Números de trazabilidad, códigos ID, coordenadas, tablas densas y telemetría. | Estructura de rejilla fija, excelente legibilidad para datos tabulares y códigos. |

### Clases de Utilidad de Fuentes en Tailwind v4:
* `font-heading` (`Orbitron`) -> Se aplica automáticamente a etiquetas `h1` - `h6` y `CardTitle`.
* `font-sans` (`Space Grotesk`) -> Fuente por defecto en todo el documento `html` y `body`.
* `font-mono` (`Share Tech Mono`) -> Aplicar manualmente usando la clase `font-mono` para valores numéricos, IDs, códigos de barras o telemetría.

---

## 2. Paleta de Colores y Variables de Estilo

La interfaz se basa en un esquema de **alto contraste controlado** y **acentos cibernéticos**. Se configuran tonos de base neutrales oscuros (o claros de laboratorio) con toques de luz que simulan el fósforo de una pantalla de terminal.

### Definición de Variables CSS de Color (en `app/globals.css`):
```css
:root {
  /* Fondo limpio de laboratorio técnico con ligeros toques gélidos */
  --background: oklch(0.98 0.01 220);
  --foreground: oklch(0.15 0.02 240);
  --primary: oklch(0.45 0.20 250); /* Azul cibernético eléctrico */
  --primary-foreground: oklch(0.99 0 0);
  --secondary: oklch(0.93 0.02 240);
  --muted-foreground: oklch(0.45 0.02 240);
  --border: oklch(0.90 0.02 240);
  --ring: oklch(0.50 0.15 250);
}

.dark {
  /* Fondo ultra-oscuro de cabina de control */
  --background: oklch(0.12 0.015 240);
  --foreground: oklch(0.95 0.01 220);
  --primary: oklch(0.68 0.18 190); /* Cian Neón de alta visibilidad */
  --primary-foreground: oklch(0.10 0.02 240);
  --secondary: oklch(0.18 0.02 240);
  --muted-foreground: oklch(0.65 0.015 240);
  --border: oklch(0.22 0.02 240);
  --ring: oklch(0.68 0.18 190 / 40%);
}
```

---

## 3. Directrices para Componentes Específicos de Shadcn/ui

### A. Botones (`Button` en `components/ui/button.tsx`)
Los botones representan comandos directos. Deben lucir como interruptores físicos de una consola o paneles capacitivos táctiles.

* **Geometría**: Mantener bordes afilados o con esquinas sutilmente suavizadas (`rounded-md` o `rounded-lg`). **No usar botones completamente redondos (`rounded-full`)**, ya que rompen la estética industrial.
* **Tipografía**: Usar `font-heading` o `font-mono` en botones que representen acciones de sistema directas, o forzar `uppercase tracking-wider text-xs` para un estilo militarizado.
* **Efecto de Estado (Hover/Active)**:
  * El botón principal (`default`) debe tener una transición de brillo suave.
  * El botón `outline` debe usar un borde semitransparente que se ilumine al hacer hover:
    `hover:border-primary/60 hover:shadow-[0_0_12px_rgba(var(--primary-rgb),0.15)]`.
* **Micro-Interacciones**: Añadir un ligero empuje táctil en el eje Y cuando está presionado: `active:translate-y-[1px]`.

*Ejemplo de clases recomendadas para un botón personalizado futurista:*
```tsx
<Button className="font-heading uppercase tracking-widest text-xs border border-primary/40 bg-transparent text-primary hover:bg-primary/15 transition-all duration-300">
  Iniciar Escaneo
</Button>
```

---

### B. Tarjetas (`Card` en `components/ui/card.tsx`)
Las tarjetas albergan los widgets y datos del panel de control de la relavera. No deben parecer contenedores web ordinarios; deben simular placas de módulos de hardware o ventanas de un osciloscopio.

* **Bordes y Sombras**: Usar bordes delgados de bajo contraste con el fondo (`ring-1 ring-foreground/10` o `border-border/60`). En modo oscuro, evitar sombras difusas tradicionales; en su lugar, usar resplandores internos sutiles (`inset shadow`) o ningún sombreado para dar un aspecto plano y técnico de pantalla.
* **Cabecera (`CardHeader`)**:
  * El título (`CardTitle`) ya hereda `font-heading` (`Orbitron`). Mantenerlo en `font-semibold` o `font-medium`.
  * Añadir un detalle visual como una pequeña barra vertical de acento de color en el lado izquierdo de la cabecera:
    `before:absolute before:left-0 before:top-4 before:bottom-4 before:w-[3px] before:bg-primary before:rounded-r`.
* **Fondo**: Usar transparencias sutiles para dar profundidad de capas: `bg-card/90 backdrop-blur-md`.

*Estructura recomendada de Card:*
```tsx
<Card className="relative overflow-hidden border-t-2 border-t-primary/80 bg-background/60 backdrop-blur-md">
  <CardHeader className="pl-6">
    <div className="absolute left-0 top-6 bottom-6 w-1 bg-primary" />
    <CardTitle className="uppercase tracking-wider">Estado de Tubería</CardTitle>
    <CardDescription>Sensor de caudal en tiempo real</CardDescription>
  </CardHeader>
  <CardContent className="font-mono text-xl text-primary font-bold">
    243.8 m³/h
  </CardContent>
</Card>
```

---

### C. Campos de Entrada (`Input` en `components/ui/input.tsx`)
El input simula la línea de comando de una terminal donde el operador ingresa parámetros críticos.

* **Tipografía**: Si el input es de tipo numérico o código ID, forzar la clase `font-mono`.
* **Estado de Focus**: El borde de focus debe ser reactivo y de color acento brillante instantáneo. En lugar de una transición lenta, usar transiciones rápidas de 100ms con un leve resplandor exterior (`focus-visible:ring-primary/30`).
* **Placeholder**: Mantener en bajo contraste pero legible (`placeholder:text-muted-foreground/50`).

*Ejemplo:*
```tsx
<Input 
  type="text" 
  placeholder="ID_RELAX_009" 
  className="font-mono border-muted-foreground/30 focus-visible:border-primary focus-visible:ring-primary/20"
/>
```

---

### D. Tablas de Datos (`Table` en `components/ui/table.tsx`)
Las tablas en EXA Relavera muestran telemetría pesada, listas de camiones, toneladas de relave y datos de muestreo. Deben comportarse como pantallas de telemetría de alta densidad.

* **Alineación y Fuentes**:
  * Los números deben estar en `font-mono` para asegurar que cada dígito se alinee perfectamente en columnas verticales, facilitando su lectura instantánea.
  * Usar números con espaciado uniforme (tabular figures) provisto de manera nativa por `Share Tech Mono`.
* **Estilo de Filas**:
  * Usar un sombreado alternado de filas (zebra striping) muy sutil para mejorar la navegación visual horizontal: `even:bg-muted/30`.
  * Efecto de hover en la fila completo (`hover:bg-primary/5 transition-colors`).
* **Bordes**: Separadores horizontales muy finos de color atenuado.

---

### E. Pestañas (`Tabs` en `components/ui/tabs.tsx`)
El controlador de pestañas funciona como los interruptores de sección de un software militar o científico de simulación.

* **Diseño del Trigger (`TabsTrigger`)**:
  * Usar tipografía `font-heading` con `uppercase tracking-wider text-xs`.
  * La pestaña activa debe resaltarse con una línea brillante inferior o un fondo sólido que contraste fuertemente, mientras que las inactivas se funden con el fondo (`text-muted-foreground hover:text-foreground`).
  * Sin bordes curvos excesivos; preferir transiciones rectas.

---

## 4. Clases de Utilidad y Efectos Especiales "Cyber" (para añadir en `globals.css`)

Para dar toques memorables en ciertas secciones críticas (como alarmas, KPIs importantes o logotipos), podemos usar estos estilos utilitarios:

### 1. Cuadrícula de Fondo Cibernética (`cyber-grid`)
Para la pantalla de login o el fondo general del dashboard:
```css
.cyber-grid {
  background-size: 40px 40px;
  background-image: 
    linear-gradient(to right, rgba(var(--primary-rgb), 0.05) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(var(--primary-rgb), 0.05) 1px, transparent 1px);
}
```

### 2. Texto con Brillo de Neón (`text-glow`)
Para resaltar métricas críticas o advertencias:
```css
.text-glow-primary {
  text-shadow: 0 0 10px var(--primary), 0 0 20px var(--primary-foreground);
}
```

### 3. Esquinas Recortadas o Biseladas (`clip-cyber`)
Para dar un aspecto de interfaz de armadura o hardware de alta tecnología:
```css
.clip-cyber {
  clip-path: polygon(0 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%);
}
```

---

## 5. Regla de Oro para el Desarrollo de UI en EXA Relavera

> **"La precisión visual equivale a la precisión de datos."**
> 
> Evitar decoraciones superfluas o redondeos tiernos que resten seriedad al sistema. Cada línea, borde brillante o etiqueta tipográfica debe comunicar estado, jerarquía y seguridad. La legibilidad de los datos en situaciones de monitoreo industrial de relave es la prioridad número uno.
