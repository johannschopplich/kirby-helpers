<?php

namespace KirbyExtended;

use Kirby\Cms\Responder;
use Kirby\Http\Response;
use Kirby\Http\Router;
use Kirby\Toolkit\Xml;

class SiteMeta
{
    public static function robots(): Responder
    {
        $robots = 'User-agent: *' . PHP_EOL;
        $robots .= 'Allow: /' . PHP_EOL;
        $robots .= 'Sitemap: ' . url('sitemap.xml');

        return kirby()
            ->response()
            ->type('text')
            ->body($robots);
    }

    public static function sitemap(): Response
    {
        $sitemap = [];
        $cache   = kirby()->cache('pages');
        $cacheId = 'sitemap.xml';

        if (!$sitemap = $cache->get($cacheId)) {
            $sitemap[] = '<?xml version="1.0" encoding="UTF-8"?>';
            $sitemap[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            $allowTemplates = option('kirby-extended.sitemap.templatesInclude', []);
            $allowPages     = option('kirby-extended.sitemap.pagesInclude', []);
            $ignorePages    = option('kirby-extended.sitemap.pagesExclude', []);
            $ignorePattern  = '/^(?:' . implode('|', $ignorePages) . ')$/i';

            foreach (site()->index() as $item) {
                if (
                    !in_array($item->intendedTemplate()->name(), $allowTemplates) &&
                    !in_array($item->id(), $allowPages)
                ) {
                    continue;
                }

                if (preg_match($ignorePattern, $item->id())) continue;
                if ($item->sitemapMode() === 'hide') continue;

                $ignoreItem = false;
                foreach ($item->parents() as $parent) {
                    if ($parent->sitemapMode() === 'hide') {
                        $ignoreItem = true;
                    }
                }
                if ($ignoreItem) continue;

                $meta = $item->meta();

                $sitemap[] = '<url>';
                $sitemap[] = '  <loc>' . Xml::encode($item->url()) . '</loc>';
                $sitemap[] = '  <priority>' . number_format($meta->priority(), 1, '.', '') . '</priority>';

                $changefreq = $meta->changefreq();
                if ($changefreq->isNotEmpty()) {
                    $sitemap[] = '  <changefreq>' . $changefreq . '</changefreq>';
                }

                $sitemap[] = '</url>';
            }

            $sitemap[] = '</urlset>';
            $sitemap = implode(PHP_EOL, $sitemap);

            $cache->set($cacheId, $sitemap);
        }

        return new Response($sitemap, 'application/xml');
    }

    public static function redirects($route, $path, $method, $result, $final) {
        // Redirect only if route didn't match anything and is final route
        if (!$final) return;
        if (!empty($result)) return;

        // Load redirects definitions
        $redirects = option('kirby-extended.redirects', []);
        if (empty($redirects)) return;

        // Make sure current path shall be redirected
        if (!in_array($path, array_keys($redirects))) return;

        // Turn into routes array
        $routes = array_map(function($from, $to) {
            return [
                'pattern' => $from,
                'action'  => function (...$parameters) use ($to) {
                    // Resolve callback
                    if (is_callable($to)) {
                        $to = $to(...$parameters);
                    }

                    // Fill placeholders
                    foreach ($parameters as $i => $parameter) {
                        $to = str_replace('$' . ($i + 1), $parameter, $to);
                    }

                    return go($to);
                }
            ];
        }, array_keys($redirects), $redirects);

        // Run router on redirects routes
        $router = new Router($routes);
        return $router->call($path, $method);
    }
}
