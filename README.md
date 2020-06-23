# Kirby Extended

This package extends Kirby's base capabilities. It is built mostly upon existing packages, but unifies them under one namespace and further updates their original functionalities.

## Included plugins

## Env

> Adapted from [kirby-env](https://github.com/beebmx/kirby-env) by Fernando Gutiérrez

**Notable Changes:**
- Updated to PHP dotenv 5.0.0
- Up-to-date Laravel `env` helper with `putenv` support

## Meta Tags

> Adapted from [kirby-meta-tags](https://github.com/pedroborges/kirby-meta-tags/) by Pedro Borges

**Notable Changes:**
- Overhauled documentation
- Cache one instance per page
- Prevent same tags being repeated multiple times
- Type hinting

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

## Requirements

- Kirby 3
- PHP 7.2+

## License

[MIT](https://opensource.org/licenses/MIT)
