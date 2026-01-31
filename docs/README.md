# Kirby Helpers Documentation

Complete documentation for all features included in the Kirby Helpers plugin. Each feature is designed to work independently or together as part of a comprehensive development toolkit.

## Features Overview

### ⚡️ [Environment Variables](./env.md)

Securely manage environment-specific configuration through `.env` files. Load variables automatically with a global `env()` helper function for API keys, database credentials, and sensitive settings.

### 🗂 [SEO Meta Tags](./meta.md)

Generate comprehensive meta tags for search engines and social media. Includes meta descriptions, OpenGraph, Twitter Cards, JSON-LD structured data, and canonical URLs with smart defaults.

### 🧭 [XML Sitemaps](./sitemap.md)

Auto-generate XML sitemaps with full multilingual support. Features automatic hreflang attributes, flexible page filtering, and integration with Kirby's cache system for optimal performance.

### 🔀 [URL Redirects](./redirects.md)

Create flexible redirect rules for URL changes and site restructures. Supports pattern matching, placeholders, custom logic through callbacks, and only activates when no existing content matches.

### ⚡️ [Vite Integration](./vite.md)

Seamlessly integrate Vite for modern frontend tooling. Automatically switches between development server (with HMR) and production assets without manual configuration changes.

## Quick Reference

### Installation

```bash
composer require johannschopplich/kirby-helpers
```

### Common Usage Patterns

```php
// Environment variables
$apiKey = env('API_SECRET_KEY', 'fallback');

// Meta tags in header
<?= $page->meta()->social() ?>
<?= $page->meta()->robots() ?>

// Vite assets
<?= vite()->js('src/main.js') ?>
<?= vite()->css('src/main.js') ?>
```

### Configuration Example

```php
// config.php
return [
    'johannschopplich.helpers' => [
        'sitemap' => ['enabled' => true],
        'redirects' => [
            'old/(:any)' => 'new/$1'
        ],
        'vite' => [
            'build' => ['outDir' => 'dist']
        ]
    ]
];
```

## Requirements

- Kirby 5+
- PHP 8.1+

## Support

Each feature includes comprehensive documentation with practical examples, configuration options, and troubleshooting tips. Features are designed to work independently, so you can use only what you need.
