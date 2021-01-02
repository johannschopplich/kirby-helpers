# Kirby Extended

This package extends Kirby's base capabilities. It's built mostly upon existing packages, but unifies them under one namespace and further updates their original functionalities including fixing open issues.

## Requirements

- Kirby 3
- PHP 7.4+

## Installation

### Download

Download and copy this repository to `/site/plugins/kirby-extended`.

### Git submodule

```
git submodule add https://github.com/johannschopplich/kirby-extended.git site/plugins/kirby-extended
```

### Composer

```
composer require johannschopplich/kirby-extended
```

## Env

Loads environment variables from `.env` automatically. This helps to store project credentials or variables outside of your code or if you want to have development and production access in different locations.

### Usage

Create a `.env` file in Kirby's base directory. You can change the default filename to look for with the `kirby-extended.env.filename` option key.

> It is important to hide your `.env` from the public. Make sure to add it to your `.gitignore` file.

#### … in Templates, Snippets etc.

You can use the `$page` method to retrieve an environment variable from anywhere:

```php
$page->env('VARIABLE');
```

#### … within `config.php`

If you want to use variables in your `config.php` file, you have to call the `Env` class manually to load the environment object before Kirby finishes initializing.

Two optional arguments `path` and `filename` may be used to load an environment file from a custom location and with a name other than `.env`. 

```php
\KirbyExtended\Env::load('path/to/env', '.env.development');
```

### Options

| Option | Default | Values | Description |
| --- | --- | --- | --- |
| `kirby-extended.env.filename` | `.env` | string | Default environment filename to load.

### Example

```ssh
# .env
KIRBY_DEBUG=false

SECRET_KEY=my_secret_key
PUBLIC_KEY=my_public_key

FOO=BAR
BAZ=${FOO}
```

With a `.env` file inside the `$base` directory in place, you can access your securely stored credentials and variables:

```php
// config.php
$base = dirname(__DIR__, 2);
\KirbyExtended\Env::load($base);

return [
    'debug' => env('KIRBY_DEBUG', false),
    'SECRET' => env('SECRET_KEY'),
    'PUBLIC' => env('PUBLIC_KEY')
];
```

## Metadata

> Forked from [getkirby.com meta plugin](https://github.com/getkirby/getkirby.com/tree/master/site/plugins/meta) by Bastian Allgeier
> Licence: MIT

This plugins handles the generation of meta tags for search engines, social networks, browsers and beyond.

### How it works

1. The plugin looks for metadata defaults, set in Kirby's global configuration.
2. If the defaults don't contain the specific key, it looks in the pagel model if it provides a `metadata()` method that returns an array or metadata fields.
3. If the page model doesn't contain the specific key, it will look for a field from a pages content file (e.g. `article.txt`) by the corrsponding key. 
4. If that also fails, it will fall back to default metadata, as stored in the `site.txt` file at the top-level of the content directory.

That way, every page will always be able to serve default values, even if the specific page or its model does not contain information like e.g. a thumbnail or a dedicated description.

It's recommended to render the metadata in your `header.php` snippet. You can define the order they will be echoed.

```php
<?php $meta = $page->meta() ?>
// Canonical link and robots meta tag if configured
<?= $meta->robots() ?>
// Schema markup
<?= $meta->jsonld() ?>
// Meta description, OpenGraph and Twitter tags
<?= $meta->social() ?>
```

### Default tags

The `kirby-extended.meta.defaults` option key may be populated by default metadata. It will be used as the base by the plugin. You can overwrite defaults with the `metadata()` method of page models per template.

| Option | Default | Values | Description |
| --- | --- | --- | --- |
| `kirby-extended.meta.defaults` | `[]` | array or function | You can use `$kirby`, `$site` and `$page` (fixed order) within the closure arguments to refer to the given object.

```php
// config.php
return [
    'kirby-extended.meta' => [
        'defaults' => return function ($kirby, $page, $site) {
            $description = $page->description()->or($site->description())->value();
            return [
                // Available keys
                'robots' => 'nofollow',
                'description' => $description,
                'opengraph' => [],
                'twitter' => [],
                'jsonld' => [
                    'WebSite' => [
                        'url' => $site->url(),
                        'name' => $site->title()->value(),
                        'description' => $description
                    ]
                ]
            ];
        };
    ]
];
```

### Using page models to automatically generate meta data

You might not want to adapt meta data for specific templates.

The following example adds a `metadata()` method to all article templates, that takes care of generating useful metadata, if an article issue is shared in a social network and also provides an automatically generated description for search engines. All keys returned by the `metadata()` method must be lowercase. Any array item can be a value of a closure, that will be called on the `$page` object, so you can use `$this` within the closure to refer to the current page.

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

### Available field keys

**Customtitle:** By default, the metadata plugin will use the page's `title` field. You can override this by defining an `customtitle` field for a specific page. The `customtitle` will then be used for OpenGraph and Twitter metadata instead of the page title.

**Description:** The description field is used for search engines as a plain meta tag and additionally added as an OpenGraph meta tag, which is used by social media networks like e.g. Facebook or Twitter.

**Thumbnail:** The thumbnail for sharing the page in a social network. If defining a custom thumbnail for a page, you should make sure to also add a text file containing an `alt` text for the corresponding image, because it is also used by social networks.

**Robots:** Generates the "robots" meta tag, that gives specifix instructions to crawlers. By default, this tag is not preset, unless a default value is defined in `site.txt`. Use a value, that you would also use if you wrote the markup directly (e.g. `noindex, nofollow`).

**Priority:** The priority for telling search engines about the importance of pages of your site. Must be a float value between 0.0 and 1.0. This value will not fall back to `site.txt`, but rather use 0.5 as default, if not explicit priority was found in the page's content or returned by its model.

**Changefreq:** Optional parameter, telling search engines how often a page changes. Possible values can be found in the [sitemaps protocol specification](https://www.sitemaps.org/protocol.html).

## Redirects

> Forked from [redirects plugin](https://github.com/getkirby/getkirby.com/pull/1131) by Nico Hoffmann
> Licence: MIT

Create redirect routes easily that only take over if no actual page/route has been matched. It uses the `go()` helper under the hood.

Redirects have to be defined in the `kirby-extended.redirects` option key in an array with the old pattern as key and the target page/URL as value. Placeholders can be used in the key and referenced via `$1`, `$2` and so on in the target string.
Instead of a target string, a callback function returning that string can also be used.

| Option | Default | Values | Description |
| --- | --- | --- | --- |
| `kirby-extended.redirects` | `false` | array | List of redirects.

```php
// config.php
return [
    'kirby-extended.redirects' => [
        // Simple redirects
        'from/foo'                    => 'to/bar',
        'blog/article-(:any)'         => 'blog/articles/$1',
        'old/reference/(:all?)'       => 'new/reference/$1',

        // Redirects with logic
        'photography/(:any)/(:all)' => function ($category, $uid) {
            if ($page = page('photography')->grandChildren()->listed()->findBy('uid', $uid)) {
                return $page->url();
            }

            return 'error';
        }
    ]
];
```

## HTML Compressor and Minifier

> Forked from [kirby-minify-html](https://github.com/afbora/kirby-minify-html) by Ahmet Bora
> Licence: MIT

Kirby HTML templates can be minified by removing extra whitespaces, comments and other unneeded characters without breaking the content structure. As a result pages become smaller in size and load faster. It will also prepare the HTML for better gzip results, by re-ranging (sort alphabetical) attributes and css-class-names.

## Usage

Minifying HTML templates is disabled by default. To enable it, set `kirby-extended.html-minify.enable` to `true`.

### Options

| Option | Default | Values | Description |
| --- | --- | --- | --- |
| `kirby-extended.html-minify.enable` | `false` | boolean | Enable or disable HTML minification.
| `kirby-extended.html-minify.options` | `[]` | array | Options to pass to HtmlMin.

Head over to HtmlMin for a [list of all available options](https://github.com/voku/HtmlMin#options).

### Example

```php
// `config.php`
return [
    'kirby-extended.minify-html' => [
        'enable' => true,
        'options' => [
            'doOptimizeViaHtmlDomParser' => true, // optimize html via `HtmlDomParser()`
            'doRemoveComments' => false // remove default HTML comments (depends on `doOptimizeViaHtmlDomParser(true)`)
        ]
    ]
];
```

## License

[MIT](https://opensource.org/licenses/MIT)
