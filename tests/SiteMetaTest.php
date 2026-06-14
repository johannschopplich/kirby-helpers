<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\SiteMeta;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SiteMetaTest extends TestCase
{
    protected function tearDown(): void
    {
        App::destroy();
    }

    private function createApp(array $options = []): App
    {
        return new App(array_merge([
            'roots' => ['index' => __DIR__],
            'urls' => ['index' => 'https://example.com'],
            'site' => [
                'content' => ['title' => 'Test Site'],
                'children' => [
                    ['slug' => 'about', 'template' => 'default', 'content' => ['title' => 'About']],
                    ['slug' => 'contact', 'template' => 'contact', 'content' => ['title' => 'Contact']],
                ],
            ],
        ], $options));
    }

    #[Test]
    public function robots_returns_a_text_response(): void
    {
        $this->createApp();

        $this->assertSame('text/plain', SiteMeta::robots()->type());
    }

    #[Test]
    public function robots_lists_the_sitemap_url(): void
    {
        $this->createApp();
        $body = SiteMeta::robots()->body();

        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Allow: /', $body);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $body);
    }

    #[Test]
    public function sitemap_renders_a_valid_urlset(): void
    {
        $this->createApp();
        $response = SiteMeta::sitemap();
        $body = $response->body();

        $this->assertSame('application/xml', $response->type());
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $body);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $body);
        $this->assertStringContainsString('</urlset>', $body);
        $this->assertStringContainsString('<loc>https://example.com/about</loc>', $body);
        $this->assertStringContainsString('<lastmod>', $body);
        $this->assertStringContainsString('<priority>', $body);
    }

    #[Test]
    public function formats_the_default_priority(): void
    {
        $this->createApp();

        $this->assertMatchesRegularExpression('/<priority>0\.\d<\/priority>/', SiteMeta::sitemap()->body());
    }

    #[Test]
    public function includes_changefreq_when_set(): void
    {
        $this->createApp([
            'site' => [
                'children' => [
                    ['slug' => 'about', 'template' => 'default', 'content' => ['title' => 'About', 'changefreq' => 'weekly']],
                ],
            ],
        ]);

        $this->assertStringContainsString('<changefreq>weekly</changefreq>', SiteMeta::sitemap()->body());
    }

    #[Test]
    public function excludes_templates(): void
    {
        $this->createApp([
            'options' => ['johannschopplich.helpers.sitemap.exclude.templates' => ['contact']],
        ]);
        $body = SiteMeta::sitemap()->body();

        $this->assertStringContainsString('/about', $body);
        $this->assertStringNotContainsString('/contact', $body);
    }

    /** @return array<string, array{0: string}> */
    public static function pageExclusionKinds(): array
    {
        return ['as array' => ['array'], 'as callable' => ['callable']];
    }

    #[Test]
    #[DataProvider('pageExclusionKinds')]
    public function excludes_pages(string $kind): void
    {
        $exclude = $kind === 'callable' ? fn () => ['hidden-page'] : ['hidden-page'];

        $this->createApp([
            'site' => [
                'children' => [
                    ['slug' => 'about', 'template' => 'default', 'content' => ['title' => 'About']],
                    ['slug' => 'hidden-page', 'template' => 'default', 'content' => ['title' => 'Hidden']],
                ],
            ],
            'options' => ['johannschopplich.helpers.sitemap.exclude.pages' => $exclude],
        ]);
        $body = SiteMeta::sitemap()->body();

        $this->assertStringContainsString('/about', $body);
        $this->assertStringNotContainsString('hidden-page', $body);
    }

    #[Test]
    public function excludes_pages_via_the_blueprint_option(): void
    {
        $this->createApp([
            'blueprints' => [
                'pages/hidden' => ['options' => ['sitemap' => false]],
            ],
            'site' => [
                'children' => [
                    ['slug' => 'about', 'template' => 'default', 'content' => ['title' => 'About']],
                    ['slug' => 'secret', 'template' => 'hidden', 'content' => ['title' => 'Secret']],
                ],
            ],
        ]);
        $body = SiteMeta::sitemap()->body();

        $this->assertStringContainsString('/about', $body);
        $this->assertStringNotContainsString('secret', $body);
    }

    #[Test]
    public function emits_hreflang_alternates_for_multilingual_sites(): void
    {
        $this->createApp([
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'default' => true, 'locale' => 'en_US.UTF-8'],
                ['code' => 'de', 'name' => 'Deutsch', 'locale' => 'de_DE.UTF-8'],
            ],
        ]);
        $body = SiteMeta::sitemap()->body();

        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $body);
        $this->assertStringContainsString('hreflang="en-us"', $body);
        $this->assertStringContainsString('hreflang="de-de"', $body);
        $this->assertStringContainsString('hreflang="x-default"', $body);
    }

    #[Test]
    public function omits_lastmod_when_the_modification_date_is_null(): void
    {
        $this->createApp([
            'pageModels' => ['no-mod' => PageWithoutModified::class],
            'site' => [
                'children' => [
                    ['slug' => 'no-mod', 'template' => 'no-mod', 'content' => ['title' => 'No Mod']],
                ],
            ],
        ]);
        $body = SiteMeta::sitemap()->body();

        $this->assertStringContainsString('<loc>https://example.com/no-mod</loc>', $body);
        $this->assertStringNotContainsString('<lastmod>', $body);
    }
}

class PageWithoutModified extends Page
{
    // Force a null modification date so the sitemap must omit `<lastmod>` entirely
    public function modified(
        string|null $format = null,
        string|null $handler = null,
        string|null $languageCode = null
    ): int|string|false|null {
        return null;
    }
}
