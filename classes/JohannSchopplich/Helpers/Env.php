<?php

declare(strict_types = 1);

namespace JohannSchopplich\Helpers;

use Closure;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;

final class Env
{
    protected static bool $loaded = false;
    protected static RepositoryInterface|null $repository = null;

    public static function getRepository(): RepositoryInterface
    {
        return self::$repository ??= RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    public static function load(string $path, string $filename = '.env'): array
    {
        $variables = Dotenv::create(
            self::getRepository(),
            $path,
            $filename
        )->load();

        self::$loaded = true;

        return $variables;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::getRepository()->get($key);

        if ($value === null) {
            return $default instanceof Closure ? $default() : $default;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => preg_match('/\A([\'"])(.*)\1\z/', $value, $matches) ? $matches[2] : $value,
        };
    }
}
