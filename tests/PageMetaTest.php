<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\PageMeta;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Content\Field;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PageMetaTest extends TestCase
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
                'content' => ['title' => 'Test Site', 'description' => 'Site description'],
                'children' => [
                    [
                        'slug' => 'test',
                        'content' => [
                            'title' => 'Test Page',
                            'description' => 'Page description',
                            'customTitle' => 'Custom Title',
                        ],
                    ],
                    ['slug' => 'empty', 'content' => ['title' => 'Empty Page']],
                ],
            ],
        ], $options));
    }

    private function appWithMetaDefaults(array $defaults): App
    {
        return $this->createApp([
            'options' => ['johannschopplich.helpers.meta.defaults' => $defaults],
        ]);
    }

    private function metaForTestPage(array $defaults): PageMeta
    {
        return new PageMeta($this->appWithMetaDefaults($defaults)->page('test'));
    }

    #[Test]
    public function merges_page_metadata_over_defaults(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => ['author' => 'Default Author', 'keywords' => 'default'],
            ],
            'pageModels' => ['with-metadata' => PageWithMetadata::class],
            'site' => [
                'children' => [
                    ['slug' => 'with-metadata', 'template' => 'with-metadata', 'content' => ['title' => 'Page With Metadata']],
                ],
            ],
        ]);

        $meta = new PageMeta($kirby->page('with-metadata'));

        $this->assertSame('Page Author', $meta->get('author')->value());
        $this->assertSame('default', $meta->get('keywords')->value());
        $this->assertSame('page-specific', $meta->get('custom')->value());
    }

    #[Test]
    public function returns_metadata_from_the_config_defaults(): void
    {
        $meta = $this->metaForTestPage(['author' => 'Johann']);

        $this->assertSame('Johann', $meta->get('author')->value());
    }

    #[Test]
    public function executes_a_callable_default_config(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => fn ($kirby, $site, $page) => [
                    'computed' => fn ($p) => 'Computed: ' . $p->title()->value(),
                ],
            ],
        ]);

        $this->assertSame('Computed: Test Page', (new PageMeta($kirby->page('test')))->get('computed')->value());
    }

    #[Test]
    public function falls_back_to_page_content(): void
    {
        $meta = new PageMeta($this->createApp()->page('test'));

        $this->assertSame('Page description', $meta->get('description')->value());
    }

    #[Test]
    public function falls_back_to_site_content(): void
    {
        $meta = new PageMeta($this->createApp()->page('empty'));

        $this->assertSame('Site description', $meta->get('description')->value());
    }

    #[Test]
    public function returns_an_empty_field_when_nothing_matches(): void
    {
        $meta = new PageMeta($this->createApp()->page('test'));

        $this->assertTrue($meta->get('nonexistent')->isEmpty());
    }

    #[Test]
    public function skips_site_content_when_the_fallback_is_disabled(): void
    {
        $meta = new PageMeta($this->createApp()->page('empty'));

        $this->assertTrue($meta->get('description', false)->isEmpty());
    }

    #[Test]
    public function magic_call_returns_a_field(): void
    {
        $field = $this->metaForTestPage(['author' => 'Johann'])->author();

        $this->assertInstanceOf(Field::class, $field);
        $this->assertSame('Johann', $field->value());
    }

    #[Test]
    public function magic_call_lowercases_the_name_and_ignores_arguments(): void
    {
        $meta = $this->metaForTestPage(['author' => 'Johann']);

        $this->assertSame('Johann', $meta->Author('ignored-argument')->value());
    }

    #[Test]
    public function jsonld_renders_a_schema_org_article_by_default(): void
    {
        $html = $this->metaForTestPage(['jsonld' => ['article' => ['headline' => 'Test Article']]])->jsonld();

        $this->assertStringContainsString('<script type="application/ld+json">', $html);
        $this->assertStringContainsString('</script>', $html);
        $this->assertStringContainsString('"@context":"https://schema.org"', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringContainsString('"headline":"Test Article"', $html);
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string, 2: string}> */
    public static function jsonldOverrides(): array
    {
        return [
            'custom context' => [
                ['product' => ['@context' => 'https://example.org/custom', 'name' => 'Test Product']],
                '"@context":"https://example.org/custom"',
                '"@context":"https://schema.org"',
            ],
            'custom type' => [
                ['article' => ['@type' => 'BlogPosting', 'headline' => 'Test']],
                '"@type":"BlogPosting"',
                '"@type":"Article"',
            ],
        ];
    }

    #[Test]
    #[DataProvider('jsonldOverrides')]
    public function jsonld_respects_an_explicit_context_and_type(array $jsonld, string $expected, string $forbidden): void
    {
        $html = $this->metaForTestPage(['jsonld' => $jsonld])->jsonld();

        $this->assertStringContainsString($expected, $html);
        $this->assertStringNotContainsString($forbidden, $html);
    }

    #[Test]
    public function jsonld_preserves_a_top_level_id(): void
    {
        $html = $this->metaForTestPage([
            'jsonld' => ['person' => ['@id' => 'https://example.com/#person', 'name' => 'Johann']],
        ])->jsonld();

        $this->assertStringContainsString('"@id":"https://example.com/#person"', $html);
        // `@context` and `@type` stay pinned to the front, ahead of `@id`
        $this->assertStringContainsString(
            '{"@context":"https://schema.org","@type":"Person","@id":"https://example.com/#person"',
            $html
        );
    }

    #[Test]
    public function jsonld_renders_one_script_per_schema_entry(): void
    {
        $html = $this->metaForTestPage([
            'jsonld' => [
                'article' => ['headline' => 'A'],
                'person' => ['name' => 'B'],
            ],
        ])->jsonld();

        $this->assertSame(2, substr_count($html, '<script type="application/ld+json">'));
    }

    #[Test]
    public function jsonld_skips_non_array_entries(): void
    {
        $html = $this->metaForTestPage(['jsonld' => ['note' => 'just a string']])->jsonld();

        $this->assertStringNotContainsString('<script', $html);
    }

    #[Test]
    public function robots_renders_a_robots_meta_tag(): void
    {
        $html = $this->metaForTestPage(['robots' => 'noindex, nofollow'])->robots();

        $this->assertStringContainsString('name="robots"', $html);
        $this->assertStringContainsString('content="noindex, nofollow"', $html);
    }

    #[Test]
    public function robots_renders_a_canonical_link(): void
    {
        $html = (new PageMeta($this->createApp()->page('test')))->robots();

        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('href="https://example.com/test"', $html);
    }

    #[Test]
    public function robots_uses_a_custom_canonical(): void
    {
        $html = $this->metaForTestPage(['canonical' => 'https://example.com/custom'])->robots();

        $this->assertStringContainsString('href="https://example.com/custom"', $html);
    }

    #[Test]
    public function social_renders_open_graph_tags(): void
    {
        $html = (new PageMeta($this->createApp()->page('test')))->social();

        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('property="og:type"', $html);
        $this->assertStringContainsString('property="og:url"', $html);
        $this->assertStringContainsString('property="og:site_name"', $html);
    }

    #[Test]
    public function social_renders_twitter_tags(): void
    {
        $html = (new PageMeta($this->createApp()->page('test')))->social();

        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringContainsString('name="twitter:title"', $html);
    }

    #[Test]
    public function social_includes_the_description(): void
    {
        $html = (new PageMeta($this->createApp()->page('test')))->social();

        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('property="og:description"', $html);
        $this->assertStringContainsString('name="twitter:description"', $html);
    }

    #[Test]
    public function social_uses_the_twitter_config(): void
    {
        $html = (new PageMeta($this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.twitter.site' => '@testsite',
                'johannschopplich.helpers.meta.twitter.creator' => '@testcreator',
            ],
        ])->page('test')))->social();

        $this->assertStringContainsString('content="@testsite"', $html);
        $this->assertStringContainsString('content="@testcreator"', $html);
    }

    #[Test]
    public function falls_back_to_a_summary_card_without_an_image(): void
    {
        $html = (new PageMeta($this->createApp()->page('test')))->social();

        $this->assertStringContainsString('content="summary"', $html);
        $this->assertStringNotContainsString('content="summary_large_image"', $html);
    }

    #[Test]
    public function handles_nested_open_graph_properties(): void
    {
        $html = $this->metaForTestPage([
            'opengraph' => [
                'namespace:article' => ['published_time' => '2024-01-01', 'author' => 'Johann'],
            ],
        ])->social();

        $this->assertStringContainsString('property="article:published_time"', $html);
        $this->assertStringContainsString('property="article:author"', $html);
    }

    #[Test]
    public function opensearch_renders_a_link_tag(): void
    {
        $html = (new PageMeta($this->createApp()->page('test')))->opensearch();

        $this->assertStringContainsString('rel="search"', $html);
        $this->assertStringContainsString('type="application/opensearchdescription+xml"', $html);
        $this->assertStringContainsString('open-search.xml', $html);
    }

    /** @return array<string, array{0: float|null, 1: float}> */
    public static function priorities(): array
    {
        return [
            'default' => [null, 0.5],
            'configured' => [0.8, 0.8],
            'clamped above one' => [1.5, 1.0],
            'clamped below zero' => [-0.5, 0.0],
        ];
    }

    #[Test]
    #[DataProvider('priorities')]
    public function clamps_priority_to_the_unit_range(float|null $configured, float $expected): void
    {
        $defaults = $configured === null ? [] : ['priority' => $configured];

        $this->assertSame($expected, $this->metaForTestPage($defaults)->priority());
    }
}

class PageWithMetadata extends Page
{
    public function metadata(): array
    {
        return [
            'author' => 'Page Author',
            'custom' => 'page-specific',
        ];
    }
}
