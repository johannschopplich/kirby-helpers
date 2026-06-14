<div align="center">
  <img src="./.github/favicon.svg" alt="Kirby Helpers logo" width="120">

# Kirby Helpers

Environment variables, SEO meta, XML sitemaps, URL redirects, and Vite integration for Kirby – the utilities most projects re-implement, in one plugin.

[Environment](./docs/env.md) •
[SEO Meta](./docs/meta.md) •
[Sitemaps](./docs/sitemap.md) •
[Redirects](./docs/redirects.md) •
[Vite](./docs/vite.md)

</div>

## When to Use

| I want to…                                             | Use                              |
| ------------------------------------------------------ | -------------------------------- |
| Read typed values from a `.env` file                   | `env('KEY', $fallback)`          |
| Emit meta description, OpenGraph, Twitter, and JSON-LD  | `$page->meta()->social()`        |
| Auto-generate an XML sitemap with `hreflang`           | `sitemap.enabled` option         |
| Redirect dead URLs without touching existing content   | `redirects` option               |
| Serve Vite dev or built assets automatically           | `vite()->js()` / `vite()->css()` |

## Features

### 🔑 Environment Variables

Load `.env` files and read variables through a global `env()` helper, with type coercion and fallbacks.

```php
// .env file support with fallbacks
$apiKey = env('STRIPE_SECRET_KEY', 'fallback-key');
```

**[Read more →](./docs/env.md)**

### 🏷️ SEO Meta Tags

Generate meta description, OpenGraph, Twitter Card, JSON-LD, and canonical tags from page fields, page models, and global defaults.

```php
// Complete meta tag generation
<?= $page->meta()->social() ?>
<?= $page->meta()->robots() ?>
```

**[Read more →](./docs/meta.md)**

### 🧭 XML Sitemaps

Auto-generate XML sitemaps with multilingual `hreflang`, template and page exclusion, and per-page control via blueprints.

```php
// Automatically available at /sitemap.xml
// Supports hreflang for multilingual sites
```

**[Read more →](./docs/sitemap.md)**

### 🔀 Smart Redirects

Pattern-based redirect rules that only fire when no existing page or route matches the URL, with placeholders and callback targets.

```php
// Pattern-based redirects with placeholders
'old/blog/(:any)' => 'news/$1'
```

**[Read more →](./docs/redirects.md)**

### ⚡️ Vite Integration

Switch between the Vite dev server (with HMR) and built `manifest.json` assets automatically, including Panel asset integration.

```php
// Load Vite assets with automatic dev/production switching
<?= vite()->js('src/main.js') ?>
<?= vite()->css('src/main.js') ?>
```

**[Read more →](./docs/vite.md)**

## Requirements

- Kirby 5
- PHP 8.3+

## Installation

### Composer (Recommended)

```bash
composer require johannschopplich/kirby-helpers
```

### Manual Installation

Download and copy this repository to `/site/plugins/kirby-helpers`.

## License

[MIT](./LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)

[MIT](./LICENSE) License © 2020-2022 [Bastian Allgeier](https://github.com/getkirby)

[MIT](./LICENSE) License © 2020-2022 [Nico Hoffmann](https://github.com/getkirby)
