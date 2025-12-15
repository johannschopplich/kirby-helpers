# Vite Integration

Seamlessly integrate Vite with your Kirby project for modern frontend tooling. Automatically switches between development server (with Hot Module Replacement) and production assets, making the transition from development to production effortless.

## Why Use Vite?

Vite provides lightning-fast development with Hot Module Replacement, modern build optimizations, and excellent developer experience. This integration handles the complexity of switching between development and production modes automatically.

## Setup

### 1. Configure Vite

Enable manifest generation in your `vite.config.js`:

```js
// vite.config.js
import { defineConfig } from "vite";

export default defineConfig({
  build: {
    manifest: true,
    outDir: "dist",
  },
});
```

### 2. Configure Kirby

Match your Vite settings in Kirby's configuration:

```php
// config.php
return [
    'johannschopplich.helpers.vite' => [
        'server' => [
            'port' => 5173,
            'https' => false
        ],
        'build' => [
            'outDir' => 'dist'
        ]
    ]
];
```

### 3. Update Development Script

Remove the build directory when starting development to ensure proper detection:

```json
{
  "scripts": {
    "dev": "rm -rf dist && vite",
    "build": "vite build"
  }
}
```

## Usage

### Load JavaScript

The `js()` method automatically injects the Vite client (`@vite/client`) in development mode for HMR support:

```php
<?= vite()->js('src/main.js') ?>
```

In development, this outputs:

```html
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/src/main.js"></script>
```

In production:

```html
<script type="module" src="/dist/assets/main-BxE4l5kw.js"></script>
```

### Load CSS

The `css()` method outputs CSS files in production, including CSS from imported modules:

```php
<?= vite()->css('src/main.js') ?>
```

> **Note**: In development mode, `css()` returns `null` since Vite handles CSS injection through JavaScript. In production, it outputs `<link>` tags for all CSS files, including those from imported modules.

### Asset Files

Get URLs to processed assets:

```php
<?php $assetUrl = vite()->file('src/images/logo.svg') ?>
<img src="<?= $assetUrl ?>" alt="Logo">
```

### Kirby Panel Customization

Use `panelJs()` and `panelCss()` to integrate Vite-built assets with Kirby's Panel:

```php
// config.php
return [
    'panel' => [
        'js' => vite()->panelJs('src/panel.js'),
        'css' => vite()->panelCss('src/panel.js')
    ]
];
```

These methods return arrays of file paths (not HTML tags) as required by Kirby's Panel configuration. The `panelJs()` method automatically includes the Vite client in development mode.

## How It Works

### Development Mode

- Plugin detects missing `manifest.json` file
- Vite client (`@vite/client`) is automatically injected for HMR
- Assets loaded directly from Vite's development server
- Hot Module Replacement works automatically
- CSS is handled by Vite through JS imports

### Production Mode

- Plugin reads `manifest.json` for asset mappings
- Assets served as static files with cache-friendly filenames
- CSS files extracted and linked separately (including CSS from imported modules)
- Optimal loading performance

## Example Template Integration

```html
<!-- site/snippets/header.php -->
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title><?= $page->title() ?></title>

    <?= vite()->css('src/main.js') ?> <?= vite()->js('src/main.js') ?>
  </head>
</html>
```

The `css()` method returns `null` in development (Vite handles it), so you can safely include it in your template without conditional checks. The `js()` method automatically includes the Vite client in development mode.

## Configuration Options

| Option                                       | Default     | Description                      |
| -------------------------------------------- | ----------- | -------------------------------- |
| `johannschopplich.helpers.vite.server.host`  | `localhost` | Vite development server host     |
| `johannschopplich.helpers.vite.server.port`  | `5173`      | Vite development server port     |
| `johannschopplich.helpers.vite.server.https` | `false`     | Use HTTPS for development server |
| `johannschopplich.helpers.vite.build.outDir` | `dist`      | Build output directory           |

## API Reference

| Method                             | Returns        | Description                                                 |
| ---------------------------------- | -------------- | ----------------------------------------------------------- |
| `js(string $entry)`                | `string`       | Returns `<script>` tag(s), includes Vite client in dev mode |
| `css(string $entry)`               | `string\|null` | Returns `<link>` tag(s) for CSS, `null` in dev mode         |
| `file(string $entry)`              | `string\|null` | Returns the processed asset URL                             |
| `panelJs(string\|array $entries)`  | `array\|null`  | Returns file paths for Panel JS config                      |
| `panelCss(string\|array $entries)` | `array\|null`  | Returns file paths for Panel CSS config                     |
| `isDev()`                          | `bool`         | Returns `true` if in development mode                       |

## Development Workflow

1. **Start Development**: `npm run dev` - Vite server starts, assets load from localhost
2. **Build for Production**: `npm run build` - Generates optimized assets and manifest
3. **Deploy**: Upload built assets, Kirby automatically uses production files

The plugin automatically detects which mode you're in based on the presence of the manifest file, so no manual switching is required.

## License

[MIT](../LICENSE) License © 2022-PRESENT [Johann Schopplich](https://github.com/johannschopplich)
