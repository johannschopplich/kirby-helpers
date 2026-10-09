<div align="center">
  <a href="https://kirby.tools/helpers"><img src="./.github/favicon.svg" alt="Kirby Helpers logo" width="120"></a>

# Kirby Helpers

Kirby Helpers is a plugin for [Kirby CMS](https://getkirby.com) with five helpers for your templates and `config.php`. Each works on its own: the sitemap, `robots.txt`, and redirects stay off until you configure them, and the rest are functions you call.

[Environment Variables](https://kirby.tools/docs/helpers/environment-variables) •
[Meta Tags](https://kirby.tools/docs/helpers/meta-tags) •
[Sitemap](https://kirby.tools/docs/helpers/sitemap) •
[Redirects](https://kirby.tools/docs/helpers/redirects) •
[Vite](https://kirby.tools/docs/helpers/vite)

</div>

## When to Use

| I want to…                                                  | Use                                     |
| ----------------------------------------------------------- | --------------------------------------- |
| Read values from a `.env` file                              | `env('KEY', $default)`                  |
| Render description, Open Graph, Twitter, and JSON-LD tags   | `$page->meta()->social()`               |
| Serve a sitemap with `hreflang` alternates                  | `sitemap.enabled` option                |
| Redirect URLs that no longer exist                          | `redirects` option                      |
| Load Vite's dev server or built assets                      | `vite()->js()` and `vite()->css()`      |

## Requirements

- Kirby 5

## Installation

### Composer (Recommended)

```bash
composer require johannschopplich/kirby-helpers
```

### Manual Installation

Download a [release](https://github.com/johannschopplich/kirby-helpers/releases) and extract it to `/site/plugins/kirby-helpers`.

## Documentation

For installation, configuration, and usage, see the [Kirby Helpers documentation](https://kirby.tools/docs/helpers).

## License

[MIT](./LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)

[MIT](./LICENSE) License © 2020-2022 [Bastian Allgeier](https://github.com/getkirby)

[MIT](./LICENSE) License © 2020-2022 [Nico Hoffmann](https://github.com/getkirby)
