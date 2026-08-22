<?php

declare(strict_types = 1);

namespace JohannSchopplich\Helpers;

use Closure;
use Kirby\Cms\Page;
use Kirby\Cms\Url;
use Kirby\Content\Field;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Html;

final class PageMeta
{
    protected array $metadata = [];

    public function __construct(
        protected readonly Page $page
    ) {
        $kirby = $page->kirby();
        $defaults = $kirby->option('johannschopplich.helpers.meta.defaults', []);
        $this->metadata = match (true) {
            $defaults instanceof Closure => $defaults($kirby, $kirby->site(), $this->page),
            is_array($defaults) => $defaults,
            default => []
        };

        if (method_exists($this->page, 'metadata')) {
            $this->metadata = A::merge($this->metadata, $this->page->metadata());
        }
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->get(strtolower($name));
    }

    public function get(string $key, bool $fallback = true): Field
    {
        $key = strtolower($key);

        if (array_key_exists($key, $this->metadata)) {
            $value = $this->metadata[$key];

            if ($value instanceof Closure) {
                $computedValue = $value($this->page);

                if ($computedValue instanceof Field) {
                    return $computedValue;
                }

                return new Field($this->page, $key, $computedValue);
            }

            return new Field($this->page, $key, $value);
        }

        $field = $this->page->content()->get($key);

        if ($field->exists() && $field->isNotEmpty()) {
            return $field;
        }

        if ($fallback) {
            $field = $this->page->site()->content()->get($key);

            if ($field->exists() && $field->isNotEmpty()) {
                return $field;
            }
        }

        return new Field($this->page, $key, null);
    }

    public function priority(): float
    {
        $priority = (float)$this->get('priority', false)->or(0.5)->value();
        return max(0.0, min(1.0, $priority));
    }

    public function jsonld(): string
    {
        $lines = [];
        $jsonldValue = $this->get('jsonld', false)->value();
        $jsonld = is_array($jsonldValue) ? $jsonldValue : [];

        foreach ($jsonld as $type => $schema) {
            if (!is_array($schema)) {
                continue;
            }

            // Pin `@context`/`@type` to the front, then spread the author's
            // schema. PHP keeps the first position but last value for duplicate
            // keys, so explicit `@context`/`@type` win and `@id`/`@graph` survive.
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => ucfirst($type),
                ...$schema,
            ];

            $flags = JSON_UNESCAPED_SLASHES;
            if ($this->page->kirby()->option('debug', false)) {
                $flags |= JSON_PRETTY_PRINT;
            }

            $lines[] = '<script type="application/ld+json">';
            $lines[] = json_encode($schema, $flags);
            $lines[] = '</script>';
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    public function robots(): string
    {
        $tags = [];
        $robots = $this->get('robots');
        $canonical = $this->get('canonical');

        if ($robots->isNotEmpty()) {
            $tags[] = Html::tag('meta', null, [
                'name' => 'robots',
                'content' => $robots->value(),
            ]);
        }

        $tags[] = Html::tag('link', null, [
            'rel' => 'canonical',
            'href' => $canonical->or($this->page->url())->value(),
        ]);

        return implode(PHP_EOL, $tags) . PHP_EOL;
    }

    public function social(): string
    {
        $tags = [];
        $metaValue = $this->get('meta', false)->value();
        $opengraphValue = $this->get('opengraph', false)->value();
        $twitterValue = $this->get('twitter', false)->value();

        $meta = is_array($metaValue) ? $metaValue : [];
        $opengraph = self::flattenNestedProperties(is_array($opengraphValue) ? $opengraphValue : []);
        $twitter = self::flattenNestedProperties(is_array($twitterValue) ? $twitterValue : []);

        $kirby = $this->page->kirby();
        $description = $this->get('description');
        $thumbnail = $this->get('thumbnail')->toFile();

        $opengraph['site_name'] ??= $this->page->site()->title()->value();
        $opengraph['url'] ??= $this->page->url();
        $opengraph['type'] ??= 'website';
        $opengraph['title'] ??= $this->page->customTitle()->or($this->page->title())->value();

        $twitter['card'] ??= 'summary_large_image';
        $twitter['title'] ??= $this->page->customTitle()->or($this->page->title())->value();

        $twitterSite = $kirby->option('johannschopplich.helpers.meta.twitter.site');
        $twitterCreator = $kirby->option('johannschopplich.helpers.meta.twitter.creator');
        if ($twitterSite) {
            $twitter['site'] ??= $twitterSite;
        }
        if ($twitterCreator) {
            $twitter['creator'] ??= $twitterCreator;
        }

        if ($description->isNotEmpty()) {
            $meta['description'] ??= $description->value();
            $opengraph['description'] ??= $description->value();
            $twitter['description'] ??= $description->value();
        }

        if ($thumbnail && !array_key_exists('image', $opengraph)) {
            // Dimensions and alt text describe the thumbnail, so they are only
            // derived while the thumbnail is also the advertised image.
            $resizedThumbnail = $thumbnail->resize(1200);

            $opengraph['image'] = $resizedThumbnail->url();
            $opengraph['image:width'] ??= $resizedThumbnail->width();
            $opengraph['image:height'] ??= $resizedThumbnail->height();

            if ($thumbnail->alt()->isNotEmpty()) {
                $opengraph['image:alt'] ??= $thumbnail->alt()->value();
            }
        }

        // Both networks point at the same asset unless Twitter is set explicitly.
        if (isset($opengraph['image'])) {
            $twitter['image'] ??= $opengraph['image'];

            if (isset($opengraph['image:alt'])) {
                $twitter['image:alt'] ??= $opengraph['image:alt'];
            }
        }

        if (!isset($twitter['image']) && $twitter['card'] === 'summary_large_image') {
            $twitter['card'] = 'summary';
        }

        // Twitter Cards are flat `name=` tags without root semantics, so only
        // the OpenGraph map is reordered.
        $opengraph = self::groupPropertiesByRoot($opengraph);

        foreach ($meta as $name => $content) {
            if ($content === null) {
                continue;
            }

            $tags[] = Html::tag('meta', null, [
                'name' => $name,
                'content' => $content,
            ]);
        }

        foreach ($opengraph as $property => $content) {
            if ($content === null) {
                continue;
            }

            if (is_array($content)) {
                // A `namespace:` prefix replaces the `og:` namespace, so
                // `namespace:article` emits `article:*` properties.
                if (str_starts_with($property, 'namespace:')) {
                    $prefix = substr($property, 10);
                } else {
                    $prefix = "og:{$property}";
                }

                foreach ($content as $subProperty => $subContent) {
                    if ($subContent === null) {
                        continue;
                    }

                    $tags[] = Html::tag('meta', null, [
                        'property' => "{$prefix}:{$subProperty}",
                        'content'  => $subContent,
                    ]);
                }
            } else {
                $tags[] = Html::tag('meta', null, [
                    'property' => "og:{$property}",
                    'content'  => $content,
                ]);
            }
        }

        foreach ($twitter as $name => $content) {
            if ($content === null) {
                continue;
            }

            $tags[] = Html::tag('meta', null, [
                'name' => "twitter:{$name}",
                'content' => $content,
            ]);
        }

        return implode(PHP_EOL, $tags) . PHP_EOL;
    }

    public function opensearch(): string
    {
        return Html::tag('link', null, [
            'rel' => 'search',
            'type' => 'application/opensearchdescription+xml',
            'title' => $this->page->site()->title(),
            'href' => Url::to('open-search.xml'),
        ]) . PHP_EOL;
    }

    /**
     * Expands `['image' => ['alt' => …]]` into `['image:alt' => …]`, so both
     * spellings resolve to the same key and cannot be emitted twice.
     *
     * `namespace:` values keep their array form, since `social()` maps them
     * to a prefix of their own.
     */
    private static function flattenNestedProperties(array $properties): array
    {
        $flatProperties = [];
        $expandedProperties = [];

        foreach ($properties as $property => $content) {
            $property = (string)$property;

            if (!is_array($content) || str_starts_with($property, 'namespace:')) {
                $flatProperties[$property] = $content;
                continue;
            }

            foreach ($content as $subProperty => $subContent) {
                $expandedProperties["{$property}:{$subProperty}"] = $subContent;
            }
        }

        // The union operator keeps the left value, so an explicitly flat key
        // wins over the nested spelling.
        return $flatProperties + $expandedProperties;
    }

    /**
     * Reorders sub-properties to follow their root tag.
     *
     * They belong to the root that precedes them, so a stray `image:alt` in
     * front of `image` would be attached to the previous root or dropped:
     * https://ogp.me/#array
     */
    private static function groupPropertiesByRoot(array $properties): array
    {
        $groupedProperties = [];

        foreach ($properties as $property => $content) {
            $property = (string)$property;

            $rootProperty = str_starts_with($property, 'namespace:') ? $property : explode(':', $property, 2)[0];
            $groupedProperties[$rootProperty][$property] = $content;
        }

        $orderedProperties = [];

        foreach ($groupedProperties as $rootProperty => $group) {
            if (array_key_exists($rootProperty, $group)) {
                $orderedProperties[$rootProperty] = $group[$rootProperty];
            }

            // The union operator keeps the root tag already pinned to the front.
            $orderedProperties += $group;
        }

        return $orderedProperties;
    }
}
