# URL Redirects

Create flexible redirect rules that automatically handle URL changes, site restructures, and legacy URL support. Redirects only activate when no actual page or route matches the requested URL, ensuring they never interfere with existing content.

## Why Use Redirects?

Redirects maintain SEO value when restructuring your site, provide backward compatibility for old URLs, and help visitors find the content they're looking for even when URLs change.

## Setup

Define redirect rules in your configuration:

```php
// config.php
return [
    'johannschopplich.helpers.redirects' => [
        // Simple redirects
        'old-page' => 'new-page',
        'blog/article' => 'news/article',

        // Pattern matching with placeholders
        'old/blog/(:any)' => 'news/$1',
        'products/(:any)/details' => 'shop/$1',
        'category/(:any)/page/(:num)' => 'archive/$1?page=$2',

        // Advanced redirects with custom logic
        'legacy/(:any)' => function ($slug) {
            // Find page by custom field
            if ($page = site()->index()->filterBy('oldSlug', $slug)->first()) {
                return $page->url();
            }
            return 'not-found';
        }
    ]
];
```

## Pattern Matching

Use Kirby's route patterns to create flexible redirect rules:

- `(:any)` - Matches any string (letters, numbers, hyphens, underscores)
- `(:num)` - Matches numbers only
- `(:all)` - Matches everything including slashes
- `(:alpha)` - Matches letters only

### Pattern Examples

```php
'johannschopplich.helpers.redirects' => [
    // Redirect with single parameter
    'user/(:any)' => 'profile/$1',

    // Multiple parameters
    'archive/(:num)/(:any)' => 'blog/$2?year=$1',

    // Optional parameters
    'docs/(:all?)' => 'documentation/$1',

    // Exact matches (no patterns)
    'about-us' => 'about',
    'contact-form' => 'contact'
]
```

## Advanced Redirects

Use callback functions for complex redirect logic:

```php
'johannschopplich.helpers.redirects' => [
    // Redirect based on page lookup
    'project/(:any)' => function ($uid) {
        if ($project = page('portfolio')->children()->findBy('uid', $uid)) {
            return $project->url();
        }
        return 'portfolio'; // Fallback to portfolio overview
    },

    // Conditional redirects
    'temp/(:any)' => function ($path) {
        $kirby = kirby();
        if ($kirby->environment()->isLocal()) {
            return 'dev/' . $path;
        }
        return 'maintenance';
    },

    // Complex URL transformations
    'api/v1/(:all)' => function ($endpoint) {
        // Redirect old API calls to new structure
        return 'api/v2/' . str_replace('-', '_', $endpoint);
    }
]
```

## How It Works

1. **Non-Interfering**: Redirects only trigger when no existing page or route matches
2. **Pattern Matching**: Uses Kirby's powerful route pattern system
3. **Placeholder Support**: Reference matched segments with `$1`, `$2`, etc.
4. **Callback Support**: Use functions for complex redirect logic
5. **Error Handling**: Failed redirects fall back to the error page

## Configuration Options

| Option                               | Default | Description                            |
| ------------------------------------ | ------- | -------------------------------------- |
| `johannschopplich.helpers.redirects` | `[]`    | Array of redirect patterns and targets |

## Migration Example

Here's how you might handle a site restructure:

```php
// config.php - Migrating from old site structure
return [
    'johannschopplich.helpers.redirects' => [
        // Blog moved to news section
        'blog' => 'news',
        'blog/(:all)' => 'news/$1',

        // Products became shop
        'products' => 'shop',
        'products/(:any)' => 'shop/$1',

        // Services merged into about
        'services' => 'about/services',
        'services/(:any)' => 'about/services#$1',

        // Handle old pagination
        'archive/page/(:num)' => function ($page) {
            return url('archive', ['params' => ['page' => $page]]);
        }
    ]
];
```

## License

[MIT](../LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)

[MIT](../LICENSE) License © 2020-2022 [Nico Hoffmann](https://github.com/getkirby)
