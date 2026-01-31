<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\PageMeta;
use JohannSchopplich\Helpers\SiteMeta;
use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SiteMetaTest extends TestCase
{
    protected function tearDown(): void
    {
        App::destroy();
    }

    private function createApp(array $options = []): App
    {
        $app = new App(array_merge([
            'roots' => [
                'index' => __DIR__
            ],
            'urls' => [
                'index' => 'https://example.com'
            ],
            'site' => [
                'content' => [
                    'title' => 'Test Site'
                ],
                'children' => [
                    [
                        'slug' => 'about',
                        'template' => 'default',
                        'content' => ['title' => 'About']
                    ],
                    [
                        'slug' => 'contact',
                        'template' => 'contact',
                        'content' => ['title' => 'Contact']
                    ]
                ]
            ]
        ], $options));

        // Register page meta method (normally done by plugin)
        $app->extend([
            'pageMethods' => [
                'meta' => fn () => new PageMeta($this)
            ]
        ]);

        return $app;
    }

    // --- robots() method ---

    #[Test]
    public function robotsReturnsTextResponse(): void
    {
        $this->createApp();
        $response = SiteMeta::robots();

        $this->assertEquals('text/plain', $response->type());
    }

    #[Test]
    public function robotsContainsUserAgentAndSitemapUrl(): void
    {
        $this->createApp();
        $response = SiteMeta::robots();
        $body = $response->body();

        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Allow: /', $body);
        $this->assertStringContainsString('Sitemap:', $body);
        $this->assertStringContainsString('sitemap.xml', $body);
    }

    // --- sitemap() method ---

    #[Test]
    public function sitemapReturnsXmlResponse(): void
    {
        $this->createApp();
        $response = SiteMeta::sitemap();

        $this->assertEquals('application/xml', $response->type());
    }

    #[Test]
    public function sitemapGeneratesValidUrlsetStructure(): void
    {
        $this->createApp();
        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $body);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $body);
        $this->assertStringContainsString('</urlset>', $body);
    }

    #[Test]
    public function sitemapIncludesUrlElements(): void
    {
        $this->createApp();
        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertStringContainsString('<url>', $body);
        $this->assertStringContainsString('<loc>', $body);
        $this->assertStringContainsString('<lastmod>', $body);
        $this->assertStringContainsString('<priority>', $body);
    }

    #[Test]
    public function sitemapExcludesTemplates(): void
    {
        $this->createApp([
            'options' => [
                'johannschopplich.helpers.sitemap.exclude.templates' => ['contact']
            ]
        ]);

        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertStringContainsString('about', $body);
        $this->assertStringNotContainsString('/contact', $body);
    }

    #[Test]
    public function sitemapExcludesPages(): void
    {
        $this->createApp([
            'site' => [
                'children' => [
                    ['slug' => 'about', 'template' => 'default', 'content' => ['title' => 'About']],
                    ['slug' => 'hidden-page', 'template' => 'default', 'content' => ['title' => 'Hidden']]
                ]
            ],
            'options' => [
                'johannschopplich.helpers.sitemap.exclude.pages' => ['hidden-page']
            ]
        ]);

        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertStringContainsString('about', $body);
        $this->assertStringNotContainsString('hidden-page', $body);
    }

    #[Test]
    public function sitemapExcludesPagesWithCallable(): void
    {
        $this->createApp([
            'site' => [
                'children' => [
                    ['slug' => 'about', 'template' => 'default', 'content' => ['title' => 'About']],
                    ['slug' => 'hidden-page', 'template' => 'default', 'content' => ['title' => 'Hidden']]
                ]
            ],
            'options' => [
                'johannschopplich.helpers.sitemap.exclude.pages' => fn () => ['hidden-page']
            ]
        ]);

        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertStringContainsString('about', $body);
        $this->assertStringNotContainsString('hidden-page', $body);
    }

    #[Test]
    public function sitemapIncludesChangefreqWhenSet(): void
    {
        $this->createApp([
            'site' => [
                'children' => [
                    ['slug' => 'about', 'template' => 'default', 'content' => ['title' => 'About', 'changefreq' => 'weekly']]
                ]
            ]
        ]);

        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertStringContainsString('<changefreq>weekly</changefreq>', $body);
    }

    #[Test]
    public function sitemapFormatsDefaultPriority(): void
    {
        $this->createApp();
        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertMatchesRegularExpression('/<priority>0\.\d<\/priority>/', $body);
    }
}
