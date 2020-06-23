# Env Adapter

> Adapted from [kirby-env](https://github.com/beebmx/kirby-env) by Fernando Gutiérrez

**Kirby Env** use the `vlucas/phpdotenv` package and enables its features for Kirby.

This package helps if you want to store project credentials or variables outside of your code or if you want to have development and production access in different locations.

## Usage

You can use the `$page` method to retrieve an env variable from anywhere:

```php
$page->env('VARIABLE');
```

****

If you want to use variables in your `config.php` file, you have to load the object first.

Two optional arguments `path` and `filename` may be used to load an environment file from a custom location and with a name other than `.env`. 

```php
\KirbyExtended\EnvAdapter::load('path/to/env', '.env.other');
```

Head over to [Options](#options) for more information about how to set defaults.

****

With an `.env` file in `path/to/env`in place, you can access securely stored credentials and variables. Here it is a example `.env`:

```ssh
KIRBY_DEBUG=false

SECRET_KEY=my_secret_key
PUBLIC_KEY=my_public_key

FOO=BAR
BAZ=${FOO}
```

> It is important to hide your `.env` from the public. Make sure to add it to your `.gitignore` file.

## Options

| Option | Default | Description |
| --- | --- | --- |
| `kirby-extended.env.path` | `kirby()->roots()->base()` | Path to where your default environment file is located.
| `kirby-extended.env.filename` | `.env` | Default environment filename to load.

## Example

Here's an example of a configuration in the `config.php` file:

```php
<?php

\KirbyExtended\EnvAdapter::load();

return [
    'debug' => env('KIRBY_DEBUG', false),
    'SECRET' => env('SECRET_KEY'),
    'PUBLIC' => env('PUBLIC_KEY'),
];
```
