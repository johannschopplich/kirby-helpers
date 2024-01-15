<?php

namespace JohannSchopplich\Helpers;

use Closure;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;

class Env
{
    protected static bool $loaded = false;
    protected static RepositoryInterface|null $repository = null;

    public static function getRepository(): RepositoryInterface
    {
        return static::$repository ??= RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();
    }

    public static function isLoaded(): bool
    {
        return static::$loaded;
    }

    public static function load(string $path, string $filename = '.env'): array
    {
        static::$loaded = true;

        return Dotenv::create(
            static::getRepository(),
            $path,
            $filename
        )->load();
    }

    public static function get(string $key, $default = null): mixed
    {
        $value = static::getRepository()->get($key);

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
