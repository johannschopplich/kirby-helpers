# Kirby Extended

This package extends Kirby's base capabilities. It's built mostly upon existing packages, but unifies them under one namespace and further updates their original functionalities including fixing open issues.

## Env

Loads environment variables from `.env` automatically. This helps to store project credentials or variables outside of your code or if you want to have development and production access in different locations.

The `.env` file is generally kept out of version control since it can contain sensitive information. A separate `.env.example` file is created with all the required environment variables defined except for the sensitive ones, which are either user-supplied for their own development environments or are communicated elsewhere to project collaborators.

### Usage

> It is important to hide your `.env` from the public. Make sure to add it to your `.gitignore` file.

#### … in Templates, Snippets etc.

You can use the `$page` method to retrieve an environment variable from anywhere:

```php
$page->env('VARIABLE');
```

The `Env` class doesn't have to be initialized by yourself. It uses configurable defaults. Head over to [Options](#options) for more information about how to set them.

#### … within `config.php`

If you want to use variables in your `config.php` file, you have to call the `EnvAdapter` manually to load the environment object before Kirby's finishes initializing.

Two optional arguments `path` and `filename` may be used to load an environment file from a custom location and with a name other than `.env`. 

```php
\KirbyExtended\Env::load('path/to/env', '.env.other');
```

Head over to the [Example](#example) for usage in `config.php`. 

### Options

| Option | Default | Description |
| --- | --- | --- |
| `kirby-extended.env.path` | `kirby()->root('base')` | Path to where your default environment file is located.
| `kirby-extended.env.filename` | `.env` | Default environment filename to load.

### Example

```php
<?php

$base = dirname(__DIR__, 2);
\KirbyExtended\Env::load($base);

return [
    'debug' => env('KIRBY_DEBUG', false),
    'SECRET' => env('SECRET_KEY'),
    'PUBLIC' => env('PUBLIC_KEY'),
];
```

With an `.env` file inside the `$base` directory in place, you can access securely stored credentials and variables. Here it is an example `.env`:

```ssh
KIRBY_DEBUG=false

SECRET_KEY=my_secret_key
PUBLIC_KEY=my_public_key

FOO=BAR
BAZ=${FOO}
```

[👉 Full documentation](docs/env-adapter.md)

## Meta Tags

> Forked from [kirby-meta-tags](https://github.com/pedroborges/kirby-meta-tags) by Pedro Borges

A HTML meta tags for Kirby. Supports [Open Graph](http://ogp.me), [Twitter Cards](https://dev.twitter.com/cards/overview), and [JSON Linked Data](https://json-ld.org) out of the box.

[👉 Full documentation](docs/meta-tags-adapter.md)

## HTML Minifier

> Forked from [kirby-minify-html](https://github.com/afbora/kirby-minify-html) by Ahmet Bora

Minifies rendered HTML templates when enabled in Kirby's configuration.

[👉 Full documentation](docs/html-minify.md)

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

## License

[MIT](https://opensource.org/licenses/MIT)
