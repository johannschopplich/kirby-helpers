# Kirby Helpers

A comprehensive toolkit that extends Kirby CMS with essential development utilities. This plugin consolidates commonly needed functionality into a single, well-integrated package, eliminating the need for multiple separate plugins.

## Why Kirby Helpers?

Building modern Kirby websites often requires the same set of tools: environment variable management, SEO meta tags, XML sitemaps, URL redirects, and modern build tool integration. Instead of installing and configuring multiple plugins, Kirby Helpers provides these essential features in one cohesive package, designed to work seamlessly together.

## Features

### ⚡️ Environment Variables

Load `.env` files automatically and access variables through a global `env()` helper function. Perfect for managing API keys, database credentials, and environment-specific settings securely.

```php
// .env file support with fallbacks
$apiKey = env('STRIPE_SECRET_KEY', 'fallback-key');
```

**[Read more →](./docs/env.md)**

### 🗂 SEO Meta Tags

Generate comprehensive meta tags for search engines and social media automatically. Supports OpenGraph, Twitter Cards, JSON-LD structured data, and canonical URLs with smart defaults and easy customization.

```php
// Complete meta tag generation
<?= $page->meta()->social() ?>
<?= $page->meta()->robots() ?>
```

**[Read more →](./docs/meta.md)**

### 🧭 XML Sitemaps

Auto-generate XML sitemaps with full multilingual support. Automatically excludes unwanted pages and templates while providing fine-grained control over what gets included.

```php
// Automatically available at /sitemap.xml
// Supports hreflang for multilingual sites
```

**[Read more →](./docs/sitemap.md)**

### 🔀 Smart Redirects

Create flexible redirect rules that only activate when no existing page matches the URL. Supports pattern matching, placeholders, and custom logic through callbacks.

```php
// Pattern-based redirects with placeholders
'old/blog/(:any)' => 'news/$1'
```

**[Read more →](./docs/redirects.md)**

### ⚡️ Vite Integration

Seamless Vite integration for modern frontend tooling. Automatically switches between development server and production assets, with support for Hot Module Replacement during development.

```php
// Load Vite assets with automatic dev/production switching
<?= vite()->js('src/main.js') ?>
<?= vite()->css('src/main.js') ?>
```

**[Read more →](./docs/vite.md)**

## Requirements

- Kirby 4+
- PHP 8.1+

## Installation

### Composer (Recommended)

```bash
composer require johannschopplich/kirby-helpers
```

### Manual Installation

Download and copy this repository to `/site/plugins/kirby-helpers`.

## Quick Start

1. **Environment Variables**: Create a `.env` file in your project root and start using `env('VARIABLE_NAME')`

2. **Meta Tags**: Add `<?= $page->meta()->social() ?>` to your header snippet

3. **Sitemap**: Enable in your config with `'johannschopplich.helpers.sitemap.enabled' => true`

4. **Redirects**: Define redirect rules in your config under `'johannschopplich.helpers.redirects'`

5. **Vite**: Configure your Vite setup and use `vite()->js()` and `vite()->css()` helpers

## Documentation

Detailed documentation for each feature is available in the [`docs`](./docs) directory:

- [Environment Variables](./docs/env.md)
- [Meta Tags](./docs/meta.md)
- [XML Sitemaps](./docs/sitemap.md)
- [Redirects](./docs/redirects.md)
- [Vite Integration](./docs/vite.md)

## License

[MIT](./LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)

[MIT](./LICENSE) License © 2020-2022 [Bastian Allgeier](https://github.com/getkirby)

[MIT](./LICENSE) License © 2020-2022 [Nico Hoffmann](https://github.com/getkirby)
