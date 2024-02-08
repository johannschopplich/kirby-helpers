# Sitemap

Auto-generates a XML sitemap to find and catalogue pages of your site. The plugin maps pages correctly in single-language as well as in multi-language sites.

## Configuration

Enable the sitemap route for your Kirby instance by setting `johannschopplich.helpers.sitemap.enabled` to `true`. The generated sitemap will be available visiting `example.com/sitemap.xml`. It will be cached if you have enabled Kirby's pages cache.

### Add Templates or Pages

All templates (and thus pages) are included in the final sitemap by default. You can opt-out templates and pages – head over to the [options](#options) to find out how.

### Control Template Visibility With Blueprint Options

You can disable templates on a blueprint-level as well. The `sitemap` option defines a template's visibility in the sitemap. It yields to `true` by default.

```yaml
title: Article
options:
  sitemap: false
```

## Options

| Option                                               | Default | Values | Description                                                   |
| ---------------------------------------------------- | ------- | ------ | ------------------------------------------------------------- |
| `johannschopplich.helpers.sitemap.enabled`           | `[]`    | array  | List of template names to include in the generated sitemap.   |
| `johannschopplich.helpers.sitemap.exclude.templates` | `[]`    | array  | List of template names to exclude from the generated sitemap. |
| `johannschopplich.helpers.sitemap.exclude.pages`     | `[]`    | array  | List of page ids to exclude.                                  |

## Example

```php
// config.php
return [
    'johannschopplich.helpers.sitemap' => [
        'enabled' => true,
        'exclude' => [
            'templates' => [
                'archive',
                'internal'
            ]
        ]
    ]
];
```

## Credits

Forked from [getkirby.com `meta` plugin](https://github.com/getkirby/getkirby.com/tree/master/site/plugins/meta).

## License

[MIT](../LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)

[MIT](../LICENSE) License © 2020-2022 [Bastian Allgeier](https://github.com/getkirby)
