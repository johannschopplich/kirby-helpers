<?php

declare(strict_types = 1);

namespace JohannSchopplich\Helpers;

use Closure;
use Exception;
use Kirby\Cms\App;
use Kirby\Http\Router;

final class Redirects
{
    public static function go(string|null $path, string $method = 'GET'): mixed
    {
        $kirby = App::instance();
        $redirects = $kirby->option('johannschopplich.helpers.redirects', []);

        if ($redirects === []) {
            return null;
        }

        $routes = array_map(
            fn ($from, $to) => [
                'pattern' => $from,
                'action'  => function (...$parameters) use ($to) {
                    // A closure target consumes the matched segments directly;
                    // one that returns `null` leaves Kirby's response untouched.
                    if ($to instanceof Closure) {
                        $target = $to(...$parameters);
                        return $target === null ? null : go($target);
                    }

                    // Kirby binds the action to its route, where `self` names the route class.
                    return go(Redirects::fillPlaceholders($to, $parameters));
                }
            ],
            array_keys($redirects),
            $redirects
        );

        try {
            return Router::execute($path, $method, $routes);
        } catch (Exception) {
            // No redirect matched, or a target threw an exception: leave Kirby's own response untouched.
            return null;
        }
    }

    /**
     * Replaces `$1`, `$2`, … placeholders in a redirect target with their
     * matched route segments.
     *
     * Substitution runs in a single pass so that multi-digit tokens (`$10`)
     * and replacements that themselves contain a `$n` sequence are never
     * corrupted.
     */
    public static function fillPlaceholders(string $target, array $parameters): string
    {
        return preg_replace_callback(
            '/\$(\d+)/',
            fn ($matches) => $parameters[(int)$matches[1] - 1] ?? $matches[0],
            $target
        );
    }
}
