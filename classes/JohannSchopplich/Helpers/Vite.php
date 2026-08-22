<?php

declare(strict_types = 1);

namespace JohannSchopplich\Helpers;

use Kirby\Cms\App;
use Kirby\Cms\Html;
use Kirby\Data\Data;
use Kirby\Http\Uri;
use Kirby\Toolkit\A;

final class Vite
{
    protected static Vite|null $instance = null;
    protected readonly App $kirby;
    protected readonly bool $isDev;
    protected bool $hasInjectedClient = false;

    public const MANIFEST_FILE_NAME = 'manifest.json';
    public array|null $manifest = null;

    public static function instance(): Vite
    {
        return self::$instance ??= new self();
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
            // A missing manifest means Vite is serving the assets in development mode.
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

    public function getEntryFile(string $entry): string|null
    {
        return $this->manifest[$entry]['file'] ?? null;
    }

    /**
     * Returns `<link>` tags for CSS files of an entry point,
     * including CSS from imported modules.
     */
    public function css(string $entry): string|null
    {
        // In dev mode, CSS is injected by Vite through JS.
        if ($this->isDev) {
            return null;
        }

        $cssFiles = $this->collectCss($entry);

        if ($cssFiles === []) {
            return null;
        }

        return Html::css(array_map($this->prodUrl(...), $cssFiles));
    }

    /**
     * Returns a `<script>` tag for an entry point,
     * including the Vite client in development mode.
     */
    public function js(string $entry): string
    {
        $tags = [];

        // The Vite client drives HMR and must not be injected twice per response.
        if ($this->isDev && !$this->hasInjectedClient) {
            $tags[] = Html::js($this->devUrl('@vite/client'), ['type' => 'module']);
            $this->hasInjectedClient = true;
        }

        $url = $this->file($entry);
        if ($url !== null) {
            $tags[] = Html::js($url, ['type' => 'module']);
        }

        return implode("\n", $tags);
    }

    /**
     * Returns the processed asset URL for an entry point.
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
     * Returns the asset paths for Kirby's `panel.js` option,
     * including the Vite client in development mode.
     */
    public function panelJs(string|array $entries): array|null
    {
        $urls = [];

        if ($this->isDev) {
            $urls[] = $this->devUrl('@vite/client');
        }

        foreach (A::wrap($entries) as $entry) {
            $urls[] = $this->file($entry);
        }

        return $urls !== [] ? $urls : null;
    }

    /**
     * Returns the asset paths for Kirby's `panel.css` option,
     * including CSS from imported modules.
     */
    public function panelCss(string|array $entries): array|null
    {
        // In dev mode, CSS is injected by Vite through JS.
        if ($this->isDev) {
            return null;
        }

        $urls = [];

        foreach (A::wrap($entries) as $entry) {
            foreach ($this->collectCss($entry) as $cssFile) {
                $urls[] = $this->prodUrl($cssFile);
            }
        }

        return $urls !== [] ? $urls : null;
    }

    /**
     * Collects all CSS files for an entry, including CSS from imported modules.
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

        $cssFiles = $manifestEntry['css'] ?? [];

        foreach ($manifestEntry['imports'] ?? [] as $import) {
            $cssFiles = array_merge($cssFiles, $this->collectCss($import));
        }

        return array_unique($cssFiles);
    }
}
