# Meta

Handles the generation of meta tags for search engines, social networks, browsers and beyond.

## Usage

Initialize the `meta()` class for the current page. Then echo the data you wish to render in a order of your choice in your `header.php` snippet.

```php
<?php $meta = $page->meta() ?>
// Canonical link (always) and robots (if configured)
<?= $meta->robots() ?>
// Schema markup
<?= $meta->jsonld() ?>
// Meta description, OpenGraph and Twitter tags
<?= $meta->social() ?>
```

## Layers

Meta data can be set in different places. On a per page basis, in the page model or in the global configuration. When meta data is set in multiple places, the plugin use the following order to determine which data to use (higher numbers override lower numbers):

1. [Page model](#page-models) providing a `metadata()` method
2. [Page content](#blueprint-field-keys) based on the blueprint field keys
3. [Global defaults](#global-defaults) in Kirby's `config.php` file

## Defaults

### Robots

The canonical link should be present on every page. Therefore it's recommended to always include at least `$meta->robots()`. A robots tag will only be generated if you have defined content for it (see [configuration](#configuration)).

> A canonical tag is a way of telling search engines that a specific URL represents the master copy of a page. Using the canonical tag prevents problems caused by identical or "duplicate" content appearing on multiple URLs like paginated pages. Practically speaking, the canonical tag tells search engines which version of a URL you want to appear in search results.

### Social

Echoing `$meta->social()` uses sensible defaults, which of course can be extended and overwritten (see [configuration](#configuration)). Without any further configuration the plugin will generate the following meta tags:

#### Generic meta

| Key           | Default                  |
| ------------- | ------------------------ |
| `description` | `description` key if set |

The `description` key will be used for the meta tag, OpenGraph description as well as Twitter description. One key to rule them all.

#### Open Graph Meta

| Key           | Default                                                       |
| ------------- | ------------------------------------------------------------- |
| `site_name`   | `$site->title()->value()`                                     |
| `url`         | `$page->url()`                                                |
| `type`        | `website`                                                     |
| `title`       | `$page->customTitle()->or($page->title())->value()`           |
| `description` | `description` key if set                                      |
| `image`       | `thumbnail` key value or thumbnail field and its image exists |
| `image:alt`   | `alt` field of thumbnail if it exists                         |

Each meta name will be prefixed with `og:` in the rendered HTML automatically.

#### Twitter Meta

| Key           | Default                                                             |
| ------------- | ------------------------------------------------------------------- |
| `url`         | `$page->url()`                                                      |
| `card`        | `summary_large_image` or `summary` if no thumbnail image is present |
| `title`       | `$page->customTitle()->or($page->title())->value()`                 |
| `description` | `description` key if set                                            |
| `image`       | `thumbnail` key value or thumbnail field and its image exists       |
| `image:alt`   | `alt` field of thumbnail if it exists                               |

Each meta name will be prefixed with `twitter:` in the rendered HTML automatically.

## Configuration

### Global Defaults

The `johannschopplich.helpers.meta.defaults` option key may be populated by default metadata. It will be used as the base. Available array keys are:

- `robots` (string)
- `description` (string)
- `opengraph` (array)
- `twitter` (array)
- `jsonld` (array)

Custom configurations like default tags will be merged with the plugin internal defaults (as listed above). Thus you can extend them to your needs and overwrite as you wish.

```php
// config.php
return [
    'johannschopplich.helpers.meta' => [
        'defaults' => function (\Kirby\Cms\App $kirby, \Kirby\Cms\Site $site, \Kirby\Cms\Page $page) {
            $description = $page->description()->or($site->description())->value();

            // Available keys
            return [
                'robots' => 'nofollow',
                 // Used for meta, OpenGraph and Twitter
                'description' => $description,
                'opengraph' => [
                    // Custom site name overwriting the internal one
                    'site_name' => $site->opengraphtitle()->value()
                ],
                'twitter' => [],
                'jsonld' => [
                    'WebSite' => [
                        'url' => $site->url(),
                        'name' => $site->title()->value(),
                        'description' => $description
                    ]
                ]
            ];
        }
    ]
];
```

### Page Models

You might want to adapt meta data for specific templates. To do so, overwrite defaults with the `metadata()` method of page models per template.

The following example adds a `metadata()` method to all article templates. It generates useful metadata if an article issue is shared on a social network and also provides a automatically generated description for search engines.

All keys returned by the `metadata()` method must be lowercase. Any array item can be a value of a closure, that will be called on the `$page` object, so you can use `$this` within the closure to refer to the current page.

```php
class ArticlePage extends \Kirby\Cms\Page
{
    public function metadata(): array
    {
        $description = $this->description()->or($this->text()->excerpt(140))->value();
        return [
            'description' => $description,
            'thumbnail' => function () {
                return $this->image();
            },
            'opengraph' => [
                'type' => 'article'
                // Open Graph object types can be defined in an array
                // with `namespace:` as prefix
                'namespace:article' => [
                    'author' => 'Kirby',
                    'published_time' => $this->published()->toDate('Y-m-d')
                ]
            ],
            'jsonld' => [
                'BlogPosting' => [
                    'headline' => $this->title()->value(),
                    'description' => $description
                ]
            ]
        ];
    }
}
```

### Blueprint Field Keys

**Customtitle:** By default, the metadata plugin will use the page's `title` field. You can override this by defining an `customtitle` field for a specific page. The `customtitle` will then be used for OpenGraph and Twitter metadata instead of the page title.

**Description:** The description field is used for search engines as a plain meta tag and additionally added as an OpenGraph meta tag, which is used by social media networks like e.g. Facebook or Twitter.

**Thumbnail:** The thumbnail for sharing the page in a social network. If defining a custom thumbnail for a page, you should make sure to also add a text file containing an `alt` text for the corresponding image, because it is also used by social networks.

**Robots:** Generates the "robots" meta tag, that gives specifix instructions to crawlers. By default, this tag is not preset, unless a default value is defined in `site.txt`. Use a value, that you would also use if you wrote the markup directly (e.g. `noindex, nofollow`).

**Priority:** The priority for telling search engines about the importance of pages of your site. Must be a float value between 0.0 and 1.0. This value will not fall back to `site.txt`, but rather use 0.5 as default, if not explicit priority was found in the page's content or returned by its model.

**Changefreq:** Optional parameter, telling search engines how often a page changes. Possible values can be found in the [sitemaps protocol specification](https://www.sitemaps.org/protocol.html).

## Options

| Option                                   | Default | Values            | Description                                                                                                        |
| ---------------------------------------- | ------- | ----------------- | ------------------------------------------------------------------------------------------------------------------ |
| `johannschopplich.helpers.meta.defaults` | `[]`    | array or function | You can use `$kirby`, `$site` and `$page` (fixed order) within the closure arguments to refer to the given object. |

## Credits

Forked from [getkirby.com `meta` plugin](https://github.com/getkirby/getkirby.com/tree/master/site/plugins/meta).

## License

[MIT](../LICENSE) License © 2020-2023 [Johann Schopplich](https://github.com/johannschopplich)

[MIT](../LICENSE) License © 2020-2022 [Bastian Allgeier](https://github.com/getkirby)
