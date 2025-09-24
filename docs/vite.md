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

```php
<!-- In your template -->
<?= vite()->js('src/main.js') ?>
```

### Load CSS

```php
<!-- CSS imported by your main JS file -->
<?= vite()->css('src/main.js') ?>
```

> **Note**: In development mode, `css()` does nothing since Vite handles CSS through the JavaScript import. In production, it outputs the built CSS files.

### Asset Files

Get URLs to processed assets:

```php
<?php $assetUrl = vite()->file('src/images/logo.svg') ?>
<img src="<?= $assetUrl ?>" alt="Logo">
```

## How It Works

### Development Mode

- Plugin detects missing `manifest.json` file
- Assets loaded directly from Vite's development server
- Hot Module Replacement works automatically
- CSS is handled by Vite through JS imports

### Production Mode

- Plugin reads `manifest.json` for asset mappings
- Assets served as static files with cache-friendly filenames
- CSS files extracted and linked separately
- Optimal loading performance

## Example Template Integration

```php
<!-- site/snippets/header.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $page->title() ?></title>

    <?php if (vite()->isDev()): ?>
        <!-- Development: Vite handles everything -->
        <?= vite()->js('src/main.js') ?>
    <?php else: ?>
        <!-- Production: Separate CSS and JS -->
        <?= vite()->css('src/main.js') ?>
        <?= vite()->js('src/main.js') ?>
    <?php endif ?>
</head>
```

## Configuration Options

| Option                                       | Default     | Description                      |
| -------------------------------------------- | ----------- | -------------------------------- |
| `johannschopplich.helpers.vite.server.host`  | `localhost` | Vite development server host     |
| `johannschopplich.helpers.vite.server.port`  | `5173`      | Vite development server port     |
| `johannschopplich.helpers.vite.server.https` | `false`     | Use HTTPS for development server |
| `johannschopplich.helpers.vite.build.outDir` | `dist`      | Build output directory           |

## Development Workflow

1. **Start Development**: `npm run dev` - Vite server starts, assets load from localhost
2. **Build for Production**: `npm run build` - Generates optimized assets and manifest
3. **Deploy**: Upload built assets, Kirby automatically uses production files

The plugin automatically detects which mode you're in based on the presence of the manifest file, so no manual switching is required.

## License

[MIT](../LICENSE) License © 2021-2022 [Oblik Studio](https://github.com/OblikStudio)

[MIT](../LICENSE) License © 2022-PRESENT [Johann Schopplich](https://github.com/johannschopplich)
