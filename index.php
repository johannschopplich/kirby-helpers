<?php

@include_once __DIR__ . '/vendor/autoload.php';

use JohannSchopplich\Helpers\Env;
use JohannSchopplich\Helpers\PageMeta;
use JohannSchopplich\Helpers\Redirects;
use JohannSchopplich\Helpers\SiteMeta;
use Kirby\Cms\App;

App::plugin('johannschopplich/helpers', [
    'hooks' => [
        'route:after' => function (\Kirby\Http\Route $route, string $path, string $method, mixed $result, bool $final) {
            if ($final && empty($result)) {
                Redirects::go($path, $method);
            }
        }
    ],
    'routes' => [
        [
            'pattern' => 'robots.txt',
            'action' => function () {
                $kirby = App::instance();

                if ($kirby->option('johannschopplich.helpers.robots.enabled', false)) {
                    return SiteMeta::robots();
                }

                $this->next();
            }
        ],
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                $kirby = App::instance();

                if ($kirby->option('johannschopplich.helpers.sitemap.enabled', false)) {
                    return SiteMeta::sitemap();
                }

                $this->next();
            }
        ]
    ],
    'siteMethods' => [
        'env' => function (string $key, $default = null) {
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
