<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\PageMeta;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageMetaTest extends TestCase
{
    protected function tearDown(): void
    {
        App::destroy();
    }

    private function createApp(array $options = []): App
    {
        return new App(array_merge([
            'roots' => [
                'index' => __DIR__
            ],
            'site' => [
                'content' => [
                    'title' => 'Test Site',
                    'description' => 'Site description'
                ],
                'children' => [
                    [
                        'slug' => 'test',
                        'content' => [
                            'title' => 'Test Page',
                            'description' => 'Page description',
                            'customTitle' => 'Custom Title'
                        ]
                    ],
                    [
                        'slug' => 'empty',
                        'content' => [
                            'title' => 'Empty Page'
                        ]
                    ]
                ]
            ]
        ], $options));
    }

    // --- Constructor ---

    #[Test]
    public function constructorMergesPageMetadataWithDefaults(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'author' => 'Default Author',
                    'keywords' => 'default'
                ]
            ],
            'pageModels' => [
                'with-metadata' => PageWithMetadata::class
            ],
            'site' => [
                'children' => [
                    [
                        'slug' => 'with-metadata',
                        'template' => 'with-metadata',
                        'content' => ['title' => 'Page With Metadata']
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('with-metadata'));

        // Page metadata overrides defaults
        $this->assertEquals('Page Author', $meta->get('author')->value());
        // Defaults are preserved when not overridden
        $this->assertEquals('default', $meta->get('keywords')->value());
        // Page-specific metadata is included
        $this->assertEquals('page-specific', $meta->get('custom')->value());
    }

    // --- get() method ---

    #[Test]
    public function getReturnsMetadataFromConfig(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'author' => 'Johann'
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals('Johann', $meta->get('author')->value());
    }

    #[Test]
    public function getExecutesCallableConfig(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => fn ($kirby, $site, $page) => [
                    'computed' => fn ($p) => 'Computed: ' . $p->title()->value()
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals('Computed: Test Page', $meta->get('computed')->value());
    }

    #[Test]
    public function getFallsBackToPageContent(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals('Page description', $meta->get('description')->value());
    }

    #[Test]
    public function getFallsBackToSiteContent(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('empty'));

        $this->assertEquals('Site description', $meta->get('description')->value());
    }

    #[Test]
    public function getReturnsEmptyFieldWhenNoMatch(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));

        $this->assertTrue($meta->get('nonexistent')->isEmpty());
    }

    #[Test]
    public function getSkipsSiteContentWhenFallbackDisabled(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('empty'));

        $this->assertTrue($meta->get('description', false)->isEmpty());
    }

    // --- Magic __call ---

    #[Test]
    public function magicCallProxiesToGet(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'author' => 'Johann'
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $field = $meta->author();

        $this->assertInstanceOf(\Kirby\Content\Field::class, $field);
        $this->assertEquals('Johann', $field->value());
    }

    // --- title() method ---

    #[Test]
    public function titleReturnsMetadataTitle(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'title' => 'Meta Title'
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals('Meta Title', $meta->title());
    }

    #[Test]
    public function titlePrefersCustomTitleOverPageTitle(): void
    {
        $kirby = $this->createApp([
            'site' => [
                'children' => [
                    [
                        'slug' => 'with-custom-title',
                        'content' => [
                            // No `title` in metadata config, page `title()` will be used
                            // but customTitle should be preferred over `page->title()`
                            'customTitle' => 'Custom Title'
                        ]
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('with-custom-title'));

        // customTitle is preferred over page->title() fallback
        $this->assertEquals('Custom Title', $meta->title());
    }

    #[Test]
    public function titleFallsBackToPageTitle(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('empty'));

        $this->assertEquals('Empty Page', $meta->title());
    }

    // --- description() method ---

    #[Test]
    public function descriptionReturnsValue(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals('Page description', $meta->description());
    }

    #[Test]
    public function descriptionReturnsNullWhenEmpty(): void
    {
        $kirby = $this->createApp([
            'site' => [
                'children' => [
                    ['slug' => 'nodesc', 'content' => ['title' => 'No Desc']]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('nodesc'));

        $this->assertNull($meta->description());
    }

    // --- jsonld() method ---

    #[Test]
    public function jsonldGeneratesScriptTag(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'jsonld' => [
                        'article' => [
                            'headline' => 'Test Article'
                        ]
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->jsonld();

        $this->assertStringContainsString('<script type="application/ld+json">', $html);
        $this->assertStringContainsString('</script>', $html);
        $this->assertStringContainsString('"headline":"Test Article"', $html);
    }

    #[Test]
    public function jsonldUsesSchemaOrgContextByDefault(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'jsonld' => [
                        'article' => ['headline' => 'Test']
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->jsonld();

        $this->assertStringContainsString('"@context":"https://schema.org"', $html);
    }

    #[Test]
    public function jsonldInfersTypeFromKey(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'jsonld' => [
                        'article' => ['headline' => 'Test']
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->jsonld();

        $this->assertStringContainsString('"@type":"Article"', $html);
    }

    #[Test]
    public function jsonldUsesCustomContext(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'jsonld' => [
                        'product' => [
                            '@context' => 'https://example.org/custom',
                            'name' => 'Test Product'
                        ]
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->jsonld();

        $this->assertStringContainsString('"@context":"https://example.org/custom"', $html);
        $this->assertStringNotContainsString('"@context":"https://schema.org"', $html);
    }

    #[Test]
    public function jsonldUsesCustomType(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'jsonld' => [
                        'article' => [
                            '@type' => 'BlogPosting',
                            'headline' => 'Test Blog Post'
                        ]
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->jsonld();

        $this->assertStringContainsString('"@type":"BlogPosting"', $html);
        $this->assertStringNotContainsString('"@type":"Article"', $html);
    }

    // --- robots() method ---

    #[Test]
    public function robotsGeneratesMetaTag(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'robots' => 'noindex, nofollow'
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->robots();

        $this->assertStringContainsString('<meta', $html);
        $this->assertStringContainsString('name="robots"', $html);
        $this->assertStringContainsString('content="noindex, nofollow"', $html);
    }

    #[Test]
    public function robotsGeneratesCanonicalLink(): void
    {
        $kirby = $this->createApp([
            'urls' => ['index' => 'https://example.com']
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->robots();

        $this->assertStringContainsString('<link', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('https://example.com/test', $html);
    }

    #[Test]
    public function robotsUsesCustomCanonical(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'canonical' => 'https://example.com/custom'
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->robots();

        $this->assertStringContainsString('href="https://example.com/custom"', $html);
    }

    // --- social() method ---

    #[Test]
    public function socialGeneratesOpenGraphTags(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->social();

        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('property="og:type"', $html);
        $this->assertStringContainsString('property="og:url"', $html);
        $this->assertStringContainsString('property="og:site_name"', $html);
    }

    #[Test]
    public function socialGeneratesTwitterTags(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->social();

        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringContainsString('name="twitter:title"', $html);
    }

    #[Test]
    public function socialIncludesDescription(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->social();

        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('property="og:description"', $html);
        $this->assertStringContainsString('name="twitter:description"', $html);
    }

    #[Test]
    public function socialUsesTwitterConfigOptions(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.twitter.site' => '@testsite',
                'johannschopplich.helpers.meta.twitter.creator' => '@testcreator'
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->social();

        $this->assertStringContainsString('name="twitter:site"', $html);
        $this->assertStringContainsString('content="@testsite"', $html);
        $this->assertStringContainsString('name="twitter:creator"', $html);
        $this->assertStringContainsString('content="@testcreator"', $html);
    }

    #[Test]
    public function socialFallsBackToSummaryCardWithoutImage(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->social();

        $this->assertStringContainsString('content="summary"', $html);
        $this->assertStringNotContainsString('content="summary_large_image"', $html);
    }

    #[Test]
    public function socialHandlesNestedOpenGraphProperties(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'opengraph' => [
                        'namespace:article' => [
                            'published_time' => '2024-01-01',
                            'author' => 'Johann'
                        ]
                    ]
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->social();

        $this->assertStringContainsString('property="article:published_time"', $html);
        $this->assertStringContainsString('property="article:author"', $html);
    }

    // --- opensearch() method ---

    #[Test]
    public function opensearchGeneratesLinkTag(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));
        $html = $meta->opensearch();

        $this->assertStringContainsString('<link', $html);
        $this->assertStringContainsString('rel="search"', $html);
        $this->assertStringContainsString('type="application/opensearchdescription+xml"', $html);
        $this->assertStringContainsString('open-search.xml', $html);
    }

    // --- priority() method ---

    #[Test]
    public function priorityReturnsDefaultValue(): void
    {
        $kirby = $this->createApp();
        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals(0.5, $meta->priority());
    }

    #[Test]
    public function priorityReturnsConfiguredValue(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'priority' => 0.8
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals(0.8, $meta->priority());
    }

    #[Test]
    public function priorityClampsValueAboveOne(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'priority' => 1.5
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals(1.0, $meta->priority());
    }

    #[Test]
    public function priorityClampsNegativeValueToZero(): void
    {
        $kirby = $this->createApp([
            'options' => [
                'johannschopplich.helpers.meta.defaults' => [
                    'priority' => -0.5
                ]
            ]
        ]);

        $meta = new PageMeta($kirby->page('test'));

        $this->assertEquals(0.0, $meta->priority());
    }
}

class PageWithMetadata extends Page
{
    public function metadata(): array
    {
        return [
            'author' => 'Page Author',
            'custom' => 'page-specific'
        ];
    }
}
