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

    private function app(array $redirects = []): App
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
        $this->app();

        $this->assertNull(Redirects::go('some/path'));
    }

    #[Test]
    public function returns_null_when_no_pattern_matches(): void
    {
        $this->app(['old-page' => 'new-page']);

        $this->assertNull(Redirects::go('unrelated/path'));
    }

    #[Test]
    public function returns_null_when_a_closure_target_returns_null(): void
    {
        $this->app(['old-page' => fn () => null]);

        $this->assertNull(Redirects::go('old-page'));
    }

    #[Test]
    public function runs_the_target_for_a_HEAD_request(): void
    {
        $isTargetCalled = false;
        $this->app(['old-page' => function () use (&$isTargetCalled) {
            $isTargetCalled = true;
            return null;
        }]);

        Redirects::go('old-page', 'HEAD');

        $this->assertTrue($isTargetCalled);
    }

    /** @return array<string, array{0: class-string<Throwable>}> */
    public static function throwableClasses(): array
    {
        return ['Error' => [Error::class], 'Exception' => [Exception::class]];
    }

    #[Test]
    #[DataProvider('throwableClasses')]
    public function rethrows_a_Throwable_from_a_closure_target(string $throwableClass): void
    {
        $this->app(['old-page' => fn () => throw new $throwableClass('Broken target')]);

        $this->expectException($throwableClass);
        $this->expectExceptionMessage('Broken target');
        Redirects::go('old-page');
    }

    /** @return array<string, array{0: string, 1: array<int, string>, 2: string}> */
    public static function placeholderCases(): array
    {
        return [
            'single segment' => ['news/$1', ['hello'], 'news/hello'],
            'reordered segments' => ['$2/$1', ['a', 'b'], 'b/a'],
            'multi-digit token' => ['p/$10/$1', ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'X'], 'p/X/1'],
            'replacement containing a token' => ['p/$1/$2', ['foo$2', 'bar'], 'p/foo$2/bar'],
            'unmatched token removed' => ['p/$1/$2', ['only-one'], 'p/only-one/'],
        ];
    }

    #[Test]
    #[DataProvider('placeholderCases')]
    public function fills_numbered_placeholders_in_a_single_pass(string $target, array $parameters, string $expected): void
    {
        $this->assertSame($expected, Redirects::fillPlaceholders($target, $parameters));
    }
}
