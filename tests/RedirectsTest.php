<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\Redirects;
use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class RedirectsTest extends TestCase
{
    protected function tearDown(): void
    {
        App::destroy();
    }

    private function createApp(array $redirects = []): App
    {
        return new App([
            'roots' => ['index' => __DIR__],
            'urls' => ['index' => 'https://example.com'],
            'options' => $redirects === [] ? [] : [
                'johannschopplich.helpers.redirects' => $redirects,
            ],
        ]);
    }

    #[Test]
    public function returns_null_when_no_redirects_are_configured(): void
    {
        $this->createApp();

        $this->assertNull(Redirects::go('some/path'));
    }

    #[Test]
    public function returns_null_when_no_pattern_matches(): void
    {
        $this->createApp(['old-page' => 'new-page']);

        $this->assertNull(Redirects::go('unrelated/path'));
    }

    /** @return array<string, array{0: string, 1: array<int, string>, 2: string}> */
    public static function placeholderCases(): array
    {
        return [
            'single segment' => ['news/$1', ['hello'], 'news/hello'],
            'reordered segments' => ['$2/$1', ['a', 'b'], 'b/a'],
            'multi-digit token' => ['p/$10/$1', ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'X'], 'p/X/1'],
            'replacement containing a token' => ['p/$1/$2', ['foo$2', 'bar'], 'p/foo$2/bar'],
            'unmatched token kept verbatim' => ['p/$3', ['only-one'], 'p/$3'],
        ];
    }

    #[Test]
    #[DataProvider('placeholderCases')]
    public function fills_numbered_placeholders_in_a_single_pass(string $target, array $parameters, string $expected): void
    {
        $this->assertSame($expected, Redirects::fillPlaceholders($target, $parameters));
    }
}
