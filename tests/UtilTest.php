<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\Util;
use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class UtilTest extends TestCase
{
    protected function tearDown(): void
    {
        App::destroy();
    }

    private function languageWith(string|array $locale): \Kirby\Cms\Language
    {
        $app = new App([
            'roots' => ['index' => __DIR__],
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'default' => true, 'locale' => $locale],
            ],
        ]);

        return $app->language('en');
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function localeCases(): array
    {
        return [
            'no suffix' => ['en_US', 'en-us'],
            'utf8 suffix' => ['de_DE.utf8', 'de-de'],
            'UTF-8 suffix' => ['fr_FR.UTF-8', 'fr-fr'],
            'legacy charset suffix' => ['de_DE.ISO-8859-1', 'de-de'],
            'modifier suffix' => ['de_DE@euro', 'de-de'],
        ];
    }

    #[Test]
    #[DataProvider('localeCases')]
    public function normalizes_locale_to_hreflang(string $locale, string $expected): void
    {
        $this->assertSame($expected, Util::languageToHreflang($this->languageWith($locale)));
    }

    #[Test]
    public function falls_back_to_the_language_code_without_an_lc_all_locale(): void
    {
        // A partial array locale leaves LC_ALL unset, so the code is used
        $language = $this->languageWith(['LC_TIME' => 'en_US.UTF-8']);

        $this->assertSame('en', Util::languageToHreflang($language));
    }
}
