<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App as Kirby;
use KirbyExtended\EnvAdapter;
use KirbyExtended\HtmlMinTemplate;

Kirby::plugin('johannschopplich/kirby-extended', [
    'pageMethods' => [
        'env' => function ($value, $default = null) {
            if (!EnvAdapter::isLoaded()) {
                EnvAdapter::load();
            }

            return env($value, $default);
        },
        'metaTags' => function ($groups = null) {
            return metaTags($this)->render($groups);
        }
    ],
    'components' => [
        'template' => function (Kirby $kirby, string $name, string $type = 'html', string $defaultType = 'html') {
            return new HtmlMinTemplate($name, $type, $defaultType);
        }
    ]
]);
