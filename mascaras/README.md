# Unified Mask System Documentation

## Overview

The Unified Mask System provides a centralized way to manage CSS styles across all ERP modules. It ensures consistent styling and makes it easy to update the entire system by modifying a single file.

## Quick Start

### Basic Usage

```php
// Simple include (uses model3 by default)
require_once('../../mascaras/unified-loader.php');
```

### With Options

```php
// Custom configuration
$mask_options = [
    'model' => 'model3',           // 'model1', 'model2', or 'model3'
    'core_only' => false,          // Use core CSS only (no master-detail)
    'no_grid_fit' => false,        // Disable grid fitting JS
    'include_legacy' => true       // Include legacy model1 styles
];
require_once('../../mascaras/unified-loader.php');
```

## Configuration Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `$mask_model` | `'model3'` | Which mask model to use |
| `$mask_core_only` | `false` | Use core CSS only (no master-detail layout) |
| `$mask_no_grid_fit` | `false` | Disable grid fitting JavaScript |
| `$mask_include_legacy` | `true` | Include legacy model1 styles |

## Available Models

### Model1 (Legacy)
- **Location:** `mascaras/model1/`
- **Style:** Traditional ERP styling with gradients and IE filters
- **Use case:** Existing modules that haven't been migrated

### Model2 (Transitional)
- **Location:** `mascaras/model2/`
- **Style:** Bootstrap-based admin theme
- **Use case:** Login pages and admin interfaces

### Model3 (Modern) - **Recommended**
- **Location:** `mascaras/model3/`
- **Style:** Modern CSS with custom properties, flexbox, and clean design
- **Use case:** New modules and existing modules being migrated

## CSS Architecture

### Default Theme Variables
The system uses CSS custom properties defined in `modules/00-default-theme.css`:

```css
:root {
  /* Primary Colors */
  --v2-brand-primary: #4a88b5;
  --v2-brand-primary-dark: #2f6f96;
  
  /* Semantic Colors */
  --v2-color-success: #87B87F;
  --v2-color-danger: #D15B47;
  --v2-color-warning: #FFB752;
  --v2-color-info: #6FB3E0;
  
  /* Background Colors */
  --v2-bg-page: #dbe5f1;
  --v2-bg-panel: #eef3f8;
  --v2-bg-grid: #f8fafc;
  
  /* Text Colors */
  --v2-text-primary: #1e293b;
  --v2-text-muted: #64748b;
  
  /* And many more... */
}
```

### Panel Structure
Every module should use this HTML structure:

```html
<div class="panel panel-main exa-ui-panel">
  <div class="panel-heading exa-header">
    <h3 class="panel-title">Module Title</h3>
  </div>
  <div class="panel-body exa-body">
    <!-- Module content goes here -->
  </div>
</div>
```

## Helper Functions

### getMaskClass()
Returns CSS classes for common UI patterns:

```php
// Button classes
echo getMaskClass('button', 'primary');  // Returns: "btn btn-primary"
echo getMaskClass('button', 'success');  // Returns: "btn btn-success"

// Alert classes
echo getMaskClass('alert', 'danger');    // Returns: "alert alert-danger"

// Panel classes
echo getMaskClass('panel');              // Returns: "panel panel-main exa-ui-panel"

// Grid cell classes
echo getMaskClass('cell', 'success');    // Returns: "cellGreen1"
echo getMaskClass('cell', 'danger');     // Returns: "cellRed1"
```

### getStatusRowClass()
Returns CSS classes for grid row status:

```php
echo getStatusRowClass('pagado');   // Returns: "exa-ui-row-pagado"
echo getStatusRowClass('vencido');  // Returns: "exa-ui-row-vencido"
echo getStatusRowClass('dirty');    // Returns: "exa-ui-row-dirty"
```

## Migration Guide

### From Model1 to Model3

1. **Update the include statement:**
   ```php
   // Old
   require_once("../../mascaras/model1/estilos/jqgrid5.php");
   
   // New
   require_once('../../mascaras/unified-loader.php');
   ```

2. **Update HTML structure:**
   ```html
   <!-- Old -->
   <div class="panel panel-default">
     <div class="panel-heading">...</div>
     <div class="panel-body">...</div>
   </div>
   
   <!-- New -->
   <div class="panel panel-main exa-ui-panel">
     <div class="panel-heading exa-header">
       <h3 class="panel-title">...</h3>
     </div>
     <div class="panel-body exa-body">...</div>
   </div>
   ```

3. **Replace inline styles with CSS classes:**
   ```html
   <!-- Old -->
   <div style="background-color: #4a88b5; color: white;">...</div>
   
   <!-- New -->
   <div class="exa-ui-brand-bg exa-ui-text-white">...</div>
   ```

### Customizing Colors

To customize colors for a specific module, override the CSS variables:

```css
/* In your module's CSS file */
.my-module .panel.panel-main.exa-ui-panel {
  --v2-brand-primary: #custom-blue;
  --v2-bg-page: #custom-bg;
}
```

## Best Practices

1. **Always use the unified loader** instead of including individual style files
2. **Use CSS variables** instead of hardcoded colors
3. **Follow the panel structure** for consistent layout
4. **Use helper functions** for common UI patterns
5. **Test across modules** to ensure consistency

## File Structure

```
mascaras/
├── unified-loader.php          # Main loader file
├── model1/                     # Legacy styles
│   └── estilos/
│       ├── jqgrid5.php         # Main style aggregator
│       ├── interfaz.css        # Legacy CSS classes
│       └── ...
├── model2/                     # Transitional styles
│   └── estilos/
│       └── ...
└── model3/                     # Modern styles
    └── estilos/
        ├── estilos.php         # Model3 loader
        ├── exa-ui.css          # Full bundle
        ├── exa-ui-core.css     # Core bundle (no master-detail)
        └── modules/
            ├── 00-default-theme.css  # Default theme variables
            ├── 01-variables.css      # Panel-specific variables
            ├── 02-shell.css          # Main panel layout
            └── ...
```

## Troubleshooting

### Styles not loading
- Check that the path to `unified-loader.php` is correct
- Verify that the `mascaras` directory exists in the correct location
- Ensure PHP has read permissions to the style files

### Inconsistent styling
- Make sure all modules use the unified loader
- Check for conflicting inline styles
- Verify that CSS variables are being used correctly

### Performance issues
- Use `$mask_core_only = true` for pages without master-detail layout
- Consider combining multiple module styles into a single bundle
- Enable browser caching for CSS files

## Support

For issues or questions about the unified mask system, please refer to this documentation or contact the development team.