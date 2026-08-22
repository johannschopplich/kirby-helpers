<?php

declare(strict_types = 1);

namespace JohannSchopplich\Helpers;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Responder;
use Kirby\Cms\Url;
use Kirby\Http\Response;
use Kirby\Toolkit\Xml;

final class SiteMeta
{
    public static function robots(): Responder
    {
        $kirby = App::instance();
        $robots = 'User-agent: *' . PHP_EOL;
        $robots .= 'Allow: /' . PHP_EOL;
        $robots .= 'Sitemap: ' . Url::to('sitemap.xml');

        return $kirby
            ->response()
            ->type('text')
            ->body($robots);
    }

    public static function sitemap(): Response
    {
        $kirby = App::instance();
        $sitemap = $kirby->cache('pages')->getOrSet(
            'sitemap.xml',
            function () use ($kirby) {
                $xhtmlAttributes = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xhtml="http://www.w3.org/1999/xhtml" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.w3.org/1999/xhtml http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd"';
                $lines = [];
                $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
                $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . ($kirby->multilang() ? " {$xhtmlAttributes}" : '') . '>';

                $excludeTemplates = $kirby->option('johannschopplich.helpers.sitemap.exclude.templates', []);
                $excludePages = $kirby->option('johannschopplich.helpers.sitemap.exclude.pages', []);

                if ($excludePages instanceof Closure) {
                    $excludePages = $excludePages();
                }

                foreach ($kirby->site()->index() as $page) {
                    if (in_array($page->intendedTemplate()->name(), $excludeTemplates, true)) {
                        continue;
                    }

                    if ($excludePages !== [] && preg_match('!^(?:' . implode('|', $excludePages) . ')$!i', $page->id())) {
                        continue;
                    }

                    $options = $page->blueprint()->options();
                    if (isset($options['sitemap']) && $options['sitemap'] === false) {
                        continue;
                    }

                    $meta = $page->meta();

                    $lines[] = '<url>';
                    $lines[] = '  <loc>' . Xml::encode($page->url()) . '</loc>';

                    $lastmod = $page->modified('Y-m-d', 'date');
                    if ($lastmod !== null) {
                        $lines[] = '  <lastmod>' . $lastmod . '</lastmod>';
                    }

                    $lines[] = '  <priority>' . number_format($meta->priority(), 1, '.', '') . '</priority>';

                    $changefreq = $meta->changefreq();
                    if ($changefreq->isNotEmpty()) {
                        $lines[] = '  <changefreq>' . Xml::encode($changefreq->value()) . '</changefreq>';
                    }

                    if ($kirby->multilang()) {
                        foreach ($kirby->languages() as $language) {
                            $hreflang = Util::languageToHreflang($language);
                            $lines[] = '  <xhtml:link rel="alternate" hreflang="' . $hreflang . '" href="' . $page->url($language->code()) . '" />';
                        }
                        $lines[] = '  <xhtml:link rel="alternate" hreflang="x-default" href="' . $page->url() . '" />';
                    }

                    $lines[] = '</url>';
                }

                $lines[] = '</urlset>';

                return implode(PHP_EOL, $lines);
            }
        );

        return new Response($sitemap, 'application/xml');
    }
}
