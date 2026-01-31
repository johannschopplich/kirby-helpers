<?php

namespace JohannSchopplich\Helpers;

use Closure;
use Kirby\Cms\Page;
use Kirby\Cms\Url;
use Kirby\Content\Field;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Html;

class PageMeta
{
    public function __construct(
        protected readonly Page $page,
        protected array $metadata = []
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
                $result = $value($this->page);

                if ($result instanceof Field) {
                    return $result;
                }

                return new Field($this->page, $key, $result);
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
        $priority = $this->get('priority', false)->or(0.5)->value();
        return (float)min(1, max(0, $priority));
    }

    public function jsonld(): string
    {
        $html = [];
        $jsonldValue = $this->get('jsonld', false)->value();
        $jsonld = is_array($jsonldValue) ? $jsonldValue : [];

        foreach ($jsonld as $type => $schema) {
            if (!is_array($schema)) {
                continue;
            }

            $schema = [
                '@context' => $schema['@context'] ?? 'https://schema.org',
                '@type' => $schema['@type'] ?? ucfirst($type),
                ...array_filter($schema, fn ($key) => !str_starts_with($key, '@'), ARRAY_FILTER_USE_KEY)
            ];

            $html[] = '<script type="application/ld+json">';
            $html[] = $this->page->kirby()->option('debug', false)
                ? json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                : json_encode($schema, JSON_UNESCAPED_SLASHES);
            $html[] = '</script>';
        }

        return implode(PHP_EOL, $html) . PHP_EOL;
    }

    public function robots(): string
    {
        $html = [];
        $robots = $this->get('robots');
        $canonical = $this->get('canonical');

        if ($robots->isNotEmpty()) {
            $html[] = Html::tag('meta', null, [
                'name' => 'robots',
                'content' => $robots->value(),
            ]);
        }

        $html[] = Html::tag('link', null, [
            'rel' => 'canonical',
            'href' => $canonical->or($this->page->url())->value(),
        ]);

        return implode(PHP_EOL, $html) . PHP_EOL;
    }

    public function social(): string
    {
        $html = [];
        $metaValue = $this->get('meta', false)->value();
        $ogValue = $this->get('opengraph', false)->value();
        $twitterValue = $this->get('twitter', false)->value();

        $meta = is_array($metaValue) ? $metaValue : [];
        $opengraph = is_array($ogValue) ? $ogValue : [];
        $twitter = is_array($twitterValue) ? $twitterValue : [];

        $kirby = $this->page->kirby();
        $description = $this->get('description');
        $thumbnail = $this->get('thumbnail')->toFile();

        // Basic OpenGraph tags
        $opengraph['site_name'] ??= $this->page->site()->title()->value();
        $opengraph['url'] ??= $this->page->url();
        $opengraph['type'] ??= 'website';
        $opengraph['title'] ??= $this->page->customTitle()->or($this->page->title())->value();

        // Basic Twitter tags
        $twitter['card'] ??= 'summary_large_image';
        $twitter['title'] ??= $this->page->customTitle()->or($this->page->title())->value();

        // Twitter site/creator from config
        $twitterSite = $kirby->option('johannschopplich.helpers.meta.twitter.site');
        $twitterCreator = $kirby->option('johannschopplich.helpers.meta.twitter.creator');
        if ($twitterSite) {
            $twitter['site'] ??= $twitterSite;
        }
        if ($twitterCreator) {
            $twitter['creator'] ??= $twitterCreator;
        }

        // Meta, OpenGraph and Twitter description
        if ($description->isNotEmpty()) {
            $meta['description'] ??= $description->value();
            $opengraph['description'] ??= $description->value();
            $twitter['description'] ??= $description->value();
        }

        // OpenGraph and Twitter image with dimensions
        if ($thumbnail) {
            $resized = $thumbnail->resize(1200);
            $imageUrl = $resized->url();

            $opengraph['image'] ??= $imageUrl;
            $opengraph['image:width'] ??= $resized->width();
            $opengraph['image:height'] ??= $resized->height();

            $twitter['image'] ??= $imageUrl;

            if ($thumbnail->alt()->isNotEmpty()) {
                $opengraph['image:alt'] ??= $thumbnail->alt()->value();
                $twitter['image:alt'] ??= $thumbnail->alt()->value();
            }
        } elseif (!isset($twitter['image']) && $twitter['card'] === 'summary_large_image') {
            $twitter['card'] = 'summary';
        }

        // OpenGraph locale for multilang
        if ($kirby->multilang()) {
            if ($locale = $kirby->language()?->locale(LC_ALL) ?? $kirby->language()?->code()) {
                $opengraph['locale'] ??= str_replace('-', '_', $locale);
            }

            $alternateLocales = [];
            foreach ($kirby->languages() as $lang) {
                if ($lang->code() !== $kirby->languageCode()) {
                    $alternateLocales[] = str_replace('-', '_', $lang->locale(LC_ALL) ?? $lang->code());
                }
            }
            if ($alternateLocales) {
                $opengraph['locale:alternate'] ??= $alternateLocales;
            }
        }

        // Generate meta tags
        foreach ($meta as $name => $content) {
            if ($content === null) {
                continue;
            }

            $html[] = Html::tag('meta', null, [
                'name' => $name,
                'content' => $content,
            ]);
        }

        // Generate OpenGraph tags
        foreach ($opengraph as $prop => $content) {
            if ($content === null) {
                continue;
            }

            if (is_array($content)) {
                // Handle namespace prefix (e.g., "namespace:article" → "article:")
                if (str_starts_with($prop, 'namespace:')) {
                    $prefix = substr($prop, 10);
                } else {
                    $prefix = "og:{$prop}";
                }

                foreach ($content as $subProp => $subContent) {
                    if ($subContent === null) {
                        continue;
                    }

                    $html[] = Html::tag('meta', null, [
                        'property' => "{$prefix}:{$subProp}",
                        'content'  => $subContent,
                    ]);
                }
            } else {
                $html[] = Html::tag('meta', null, [
                    'property' => "og:{$prop}",
                    'content'  => $content,
                ]);
            }
        }

        // Generate Twitter tags
        foreach ($twitter as $name => $content) {
            if ($content === null) {
                continue;
            }

            $html[] = Html::tag('meta', null, [
                'name' => "twitter:{$name}",
                'content' => $content,
            ]);
        }

        return implode(PHP_EOL, $html) . PHP_EOL;
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
}
