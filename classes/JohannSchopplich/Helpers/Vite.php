<?php

namespace JohannSchopplich\Helpers;

use Kirby\Cms\App;
use Kirby\Data\Data;
use Kirby\Http\Uri;

class Vite
{
    protected static Vite|null $instance = null;
    protected App $kirby;

    public const MANIFEST_FILE_NAME = 'manifest.json';
    public array|null $manifest = null;

    public static function instance(): Vite
    {
        return static::$instance ??= new static();
    }

    public function __construct()
    {
        $this->kirby = App::instance();

        $path = implode(DIRECTORY_SEPARATOR, array_filter([
            $this->kirby->root(),
            $this->kirby->option('johannschopplich.helpers.vite.build.outDir', 'dist'),
            self::MANIFEST_FILE_NAME
        ], 'strlen'));

        try {
            $this->manifest = Data::read($path);
        } catch (\Throwable $t) {
            // Vite is running in development mode
        }
    }

    public function isDev(): bool
    {
        return $this->manifest === null;
    }

    public function devUrl(string $path): string
    {
        $uri = new Uri([
            'scheme' => $this->kirby->option('johannschopplich.helpers.vite.server.https', false) ? 'https' : 'http',
            'host'   => $this->kirby->option('johannschopplich.helpers.vite.server.host', 'localhost'),
            'port'   => $this->kirby->option('johannschopplich.helpers.vite.server.port', 5173),
            'path'   => $path
        ]);

        return $uri->toString();
    }

    public function prodUrl(string $path): string
    {
        return implode('/', array_filter([
            $this->kirby->url(),
            $this->kirby->option('johannschopplich.helpers.vite.build.outDir', 'dist'),
            $path
        ], 'strlen'));
    }

    /**
     * Returns `<link>` tags for each CSS file of an entry point
     */
    public function css(string $entry)
    {
        if (is_array($this->manifest)) {
            foreach ($this->manifest[$entry]['css'] as $file) {
                return css($this->prodUrl($file));
            }
        }
    }

    /**
     * Returns a `<script>` tag for an entry point
     */
    public function js(string $entry): string
    {
        if (is_array($this->manifest)) {
            $url = $this->prodUrl($this->manifest[$entry]['file']);
        } else {
            $url = $this->devUrl($entry);
        }

        return js($url, ['type' => 'module']);
    }

    /**
     * Returns the processed asset URL for an entry point
     */
    public function file(string $entry): string|null
    {
        if (is_array($this->manifest)) {
            return $this->prodUrl($this->manifest[$entry]['file']);
        }
    }
}
