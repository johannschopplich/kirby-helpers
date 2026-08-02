<?php

@include_once __DIR__ . '/vendor/autoload.php';

use JohannSchopplich\Helpers\Env;
use JohannSchopplich\Helpers\PageMeta;
use JohannSchopplich\Helpers\Redirects;
use JohannSchopplich\Helpers\SiteMeta;
use Kirby\Cms\App;
use Kirby\Http\Route;

App::plugin('johannschopplich/helpers', [
    'hooks' => [
        'route:after' => function (string $path, string $method, mixed $result, bool $final) {
            // Kirby's `App::io()` turns any empty result into the error page, so the redirect map has to answer for exactly that set
            if ($final && ($result === null || $result === false || $result === '' || $result === [])) {
                Redirects::go($path, $method);
            }
        }
    ],
    'routes' => [
        [
            'pattern' => 'robots.txt',
            'action' => function () {
                if (option('johannschopplich.helpers.robots.enabled', false)) {
                    return SiteMeta::robots();
                }

                Route::next();
            }
        ],
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                if (option('johannschopplich.helpers.sitemap.enabled', false)) {
                    return SiteMeta::sitemap();
                }

                Route::next();
            }
        ]
    ],
    'siteMethods' => [
        'env' => function (string $key, mixed $default = null) {
            if (!Env::isLoaded()) {
                $kirby = App::instance();
                $path = $kirby->option('johannschopplich.helpers.env.path', $kirby->root('base'));
                $file = $kirby->option('johannschopplich.helpers.env.filename', '.env');
                Env::load($path, $file);
            }

            return Env::get($key, $default);
        }
    ],
    'pageMethods' => [
        'meta' => function () {
            return new PageMeta($this);
        }
    ]
]);
