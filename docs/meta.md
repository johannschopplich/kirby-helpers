# SEO Meta Tags

Generate meta description, OpenGraph, Twitter Card, JSON-LD, and canonical tags from page fields, page models, and global defaults.

## Basic Usage

Add meta tag generation to your header snippet:

```php
<?php $meta = $page->meta() ?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $page->title() ?></title>

    <?= $meta->robots() ?>    <!-- Canonical link + robots -->
    <?= $meta->social() ?>    <!-- Meta, OpenGraph, Twitter -->
    <?= $meta->jsonld() ?>    <!-- Structured data -->
</head>
```

## What Gets Generated

### Robots & Canonical

- Canonical URL (always included)
- Robots meta tag (if configured)

### Social Media Tags

- Meta description
- OpenGraph tags for Facebook, LinkedIn
- Twitter Card tags
- Automatic image handling from page thumbnails

### Structured Data

- JSON-LD schema markup for search engines

## Configuration

### Page-Level Meta Data

Add fields to your page blueprints to control meta tags:

```yaml
# site/blueprints/pages/article.yml
fields:
  customtitle:
    label: SEO Title
    type: text
    help: Override the page title for SEO
  description:
    label: Meta Description
    type: textarea
    maxlength: 160
  thumbnail:
    label: Social Media Image
    type: files
```

### Page Model Customization

Override meta data in page models for specific templates:

```php
// site/models/article.php
class ArticlePage extends \Kirby\Cms\Page
{
    public function metadata(): array
    {
        return [
            'description' => $this->text()->excerpt(160),
            'thumbnail' => $this->image(),
            'opengraph' => [
                'type' => 'article'
            ],
            'jsonld' => [
                'BlogPosting' => [
                    'headline' => $this->title()->value(),
                    'datePublished' => $this->published()->toDate('c')
                ]
            ]
        ];
    }
}
```

### Global Defaults

Set site-wide defaults in your configuration:

```php
// config.php
return [
    'johannschopplich.helpers.meta' => [
        'defaults' => function ($kirby, $site, $page) {
            return [
                'description' => $page->description()->or($site->description())->value(),
                'opengraph' => [
                    'site_name' => $site->title()->value()
                ],
                'jsonld' => [
                    'WebSite' => [
                        'url' => $site->url(),
                        'name' => $site->title()->value()
                    ]
                ]
            ];
        }
    ]
];
```

### Nested and Namespaced OpenGraph Tags

An array value expands into `og:<property>:<key>` tags. A `namespace:` prefix drops the `og:` part entirely, which is the only way to emit `article:*`, `product:*`, and similar namespaced tags:

```php
'opengraph' => [
    'image' => [
        'width' => 1200,
        'height' => 630
    ],
    'namespace:article' => [
        'published_time' => $page->published()->toDate('c'),
        'author' => 'Johann Schopplich'
    ]
]
```

```html
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="article:published_time" content="…">
<meta property="article:author" content="Johann Schopplich">
```

## Default Values

The plugin provides sensible defaults that work out of the box:

### OpenGraph

- `site_name`: Site title
- `url`: Current page URL
- `type`: "website"
- `title`: Page title or custom title
- `description`: From description field
- `image`: From thumbnail field (resized to 1200px)
- `image:width` / `image:height`: Automatic dimensions from thumbnail
- `image:alt`: Thumbnail alt text (if set)

### Twitter Cards

- `card`: "summary_large_image" (or "summary" if no image)
- `title`: Page title or custom title
- `description`: From description field
- `image`: From thumbnail field
- `image:alt`: Thumbnail alt text (if set)

## Configuration Priority

Meta data is merged in this order (later overrides earlier):

1. Global defaults (config.php)
2. Page content fields (blueprint fields)
3. Page model metadata() method

## Additional Methods

### OpenSearch Discovery

Add OpenSearch support so browsers can discover your site's search functionality:

```php
<?= $page->meta()->opensearch() ?>
```

This generates a `<link>` tag pointing to `/open-search.xml`.

### Sitemap Priority

Get the sitemap priority for a page (used internally by the sitemap generator):

```php
$priority = $page->meta()->priority(); // Returns float 0.0-1.0
```

The priority is read from the page's `priority` field, defaulting to `0.5`.

## API Reference

| Method                                    | Returns | Description                                     |
| ----------------------------------------- | ------- | ---------------------------------------------- |
| `robots()`                                | `string` | Canonical link + robots meta tag              |
| `social()`                                | `string` | Meta, OpenGraph, and Twitter tags             |
| `jsonld()`                                | `string` | JSON-LD structured data scripts               |
| `opensearch()`                            | `string` | OpenSearch discovery link                     |
| `priority()`                              | `float`  | Sitemap priority (0.0–1.0)                    |
| `get(string $key, bool $fallback = true)` | `Field`  | Any meta field, with optional site fallback   |

Any other method call is proxied to `get()` and returns a `Field`, so `$meta->description()` resolves the `description` field (with site fallback) and `$meta->thumbnail()->toFile()` resolves the thumbnail.

## Configuration Options

| Option                                          | Default | Description                                  |
| ----------------------------------------------- | ------- | -------------------------------------------- |
| `johannschopplich.helpers.meta.defaults`        | `[]`    | Global meta tag defaults (array or function) |
| `johannschopplich.helpers.meta.twitter.site`    | `null`  | Twitter @username for the site               |
| `johannschopplich.helpers.meta.twitter.creator` | `null`  | Twitter @username for the content creator    |

## License

[MIT](../LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)

[MIT](../LICENSE) License © 2020-2022 [Bastian Allgeier](https://github.com/getkirby)
