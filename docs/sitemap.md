# XML Sitemaps

Auto-generate XML sitemaps that help search engines discover and index your website pages. Features full multilingual support with automatic hreflang attributes and flexible page filtering options.

## Why Use XML Sitemaps?

XML sitemaps help search engines understand your site structure and find all your important pages. They're especially valuable for large sites, new sites, or sites with complex navigation structures.

## Setup

Enable the sitemap and robots.txt in your configuration:

```php
// config.php
return [
    'johannschopplich.helpers' => [
        'sitemap' => ['enabled' => true],
        'robots' => ['enabled' => true]
    ]
];
```

Your sitemap will be available at `/sitemap.xml` and `robots.txt` at `/robots.txt`. Results are cached using Kirby's page cache for better performance.

## Page Control

### Exclude by Template

Exclude entire templates from the sitemap:

```php
// config.php
return [
    'johannschopplich.helpers' => [
        'sitemap' => [
            'exclude' => [
                'templates' => ['error', 'admin', 'internal']
            ]
        ]
    ]
];
```

### Exclude by Page ID

Exclude specific pages using their page IDs:

```php
// config.php
return [
    'johannschopplich.helpers' => [
        'sitemap' => [
            'exclude' => [
                'pages' => [
                    'legal/privacy',
                    'admin',
                    'temp-.*'  // Regex patterns supported
                ]
            ]
        ]
    ]
];
```

### Blueprint-Level Control

Disable sitemap inclusion directly in page blueprints:

```yaml
# site/blueprints/pages/private.yml
title: Private Page
options:
  sitemap: false
```

## Multilingual Support

For multilingual sites, the plugin automatically generates:

- Separate URLs for each language version
- `hreflang` attributes for each language
- `x-default` hreflang pointing to the default language

Example multilingual sitemap output:

```xml
<url>
  <loc>https://example.com/en/about</loc>
  <xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/about" />
  <xhtml:link rel="alternate" hreflang="de" href="https://example.com/de/uber-uns" />
  <xhtml:link rel="alternate" hreflang="x-default" href="https://example.com/about" />
</url>
```

## SEO Fields

Control sitemap properties using page fields:

```yaml
# site/blueprints/pages/default.yml
fields:
  priority:
    label: Search Priority
    type: range
    min: 0
    max: 1
    step: 0.1
    default: 0.5
  changefreq:
    label: Change Frequency
    type: select
    options:
      always: Always
      hourly: Hourly
      daily: Daily
      weekly: Weekly
      monthly: Monthly
      yearly: Yearly
      never: Never
```

## Configuration Options

| Option                                               | Default | Description                                            |
| ---------------------------------------------------- | ------- | ------------------------------------------------------ |
| `johannschopplich.helpers.sitemap.enabled`           | `false` | Enable the sitemap route                               |
| `johannschopplich.helpers.robots.enabled`            | `false` | Enable the robots.txt route                            |
| `johannschopplich.helpers.sitemap.exclude.templates` | `[]`    | Array of template names to exclude                     |
| `johannschopplich.helpers.sitemap.exclude.pages`     | `[]`    | Array or callback returning page IDs (regex supported) |

## Example Configuration

```php
// config.php
return [
    'johannschopplich.helpers' => [
        'sitemap' => [
            'enabled' => true,
            'exclude' => [
                'templates' => ['error', 'search', 'contact-form'],
                'pages' => function () {
                    // Dynamic exclusion - exclude all unlisted pages
                    return site()->index()->filter(fn ($p) => !$p->isListed())->pluck('id');
                }
            ]
        ],
        'robots' => ['enabled' => true]
    ]
];
```

## License

[MIT](../LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)

[MIT](../LICENSE) License © 2020-2022 [Bastian Allgeier](https://github.com/getkirby)
