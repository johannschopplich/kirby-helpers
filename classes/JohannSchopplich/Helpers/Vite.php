<?php

namespace JohannSchopplich\Helpers;

use Kirby\Cms\App;
use Kirby\Cms\Html;
use Kirby\Data\Data;
use Kirby\Http\Uri;
use Kirby\Toolkit\A;

class Vite
{
    protected static Vite|null $instance = null;
    protected readonly App $kirby;
    protected readonly bool $isDev;
    protected bool $hasInjectedClient = false;

    public const MANIFEST_FILE_NAME = 'manifest.json';
    public array|null $manifest = null;

    public static function instance(): Vite
    {
        return static::$instance ??= new static();
    }

    public function __construct()
    {
        $this->kirby = App::instance();

        $path = implode('/', array_filter([
            $this->kirby->root(),
            $this->kirby->option('johannschopplich.helpers.vite.build.outDir', 'dist'),
            '.vite',
            self::MANIFEST_FILE_NAME
        ], strlen(...)));

        try {
            $this->manifest = Data::read($path);
        } catch (\Throwable) {
            // Vite is running in development mode
        }

        $this->isDev = $this->manifest === null;
    }

    public function isDev(): bool
    {
        return $this->isDev;
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
        ], strlen(...)));
    }

    /**
     * Returns the file path for an entry from the manifest
     */
    public function getEntryFile(string $entry): string|null
    {
        return $this->manifest[$entry]['file'] ?? null;
    }

    /**
     * Returns `<link>` tags for CSS files of an entry point,
     * including CSS from imported modules
     */
    public function css(string $entry): string|null
    {
        // In dev mode, CSS is injected by Vite through JS
        if ($this->isDev) {
            return null;
        }

        $files = $this->collectCss($entry);

        if ($files === []) {
            return null;
        }

        $tags = array_map(
            fn (string $file): string => Html::css($this->prodUrl($file)),
            $files
        );

        return implode("\n", $tags);
    }

    /**
     * Returns a `<script>` tag for an entry point,
     * including the Vite client in development mode
     */
    public function js(string $entry): string
    {
        $tags = [];

        // Inject Vite client for HMR in development mode (only once)
        if ($this->isDev && !$this->hasInjectedClient) {
            $tags[] = Html::js($this->devUrl('@vite/client'), ['type' => 'module']);
            $this->hasInjectedClient = true;
        }

        $url = $this->isDev
            ? $this->devUrl($entry)
            : $this->prodUrl($this->getEntryFile($entry));

        $tags[] = Html::js($url, ['type' => 'module']);

        return implode("\n", $tags);
    }

    /**
     * Returns the processed asset URL for an entry point
     */
    public function file(string $entry): string|null
    {
        if ($this->isDev) {
            return $this->devUrl($entry);
        }

        $file = $this->getEntryFile($entry);
        return $file !== null ? $this->prodUrl($file) : null;
    }

    /**
     * Returns an array of file paths for Kirby Panel JS customization
     */
    public function panelJs(string|array $entries): array|null
    {
        $files = [];

        if ($this->isDev) {
            $files[] = $this->devUrl('@vite/client');
        }

        foreach (A::wrap($entries) as $entry) {
            $files[] = $this->file($entry);
        }

        return $files !== [] ? $files : null;
    }

    /**
     * Returns an array of file paths for Kirby Panel CSS customization
     */
    public function panelCss(string|array $entries): array|null
    {
        // In dev mode, CSS is injected by Vite through JS
        if ($this->isDev) {
            return null;
        }

        $files = [];

        foreach (A::wrap($entries) as $entry) {
            foreach ($this->collectCss($entry) as $css) {
                $files[] = $this->prodUrl($css);
            }
        }

        return $files !== [] ? $files : null;
    }

    /**
     * Collects all CSS files for an entry, including CSS from imported modules
     */
    protected function collectCss(string $entry): array
    {
        if ($this->manifest === null) {
            return [];
        }

        $manifestEntry = $this->manifest[$entry] ?? null;

        if ($manifestEntry === null) {
            return [];
        }

        // Get direct CSS files
        $files = $manifestEntry['css'] ?? [];

        // Recursively collect CSS from imports
        foreach ($manifestEntry['imports'] ?? [] as $import) {
            $files = array_merge($files, $this->collectCss($import));
        }

        return array_unique($files);
    }
}
