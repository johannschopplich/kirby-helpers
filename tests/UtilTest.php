<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\Util;
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

    private function field(string $value): Field
    {
        new App(['roots' => ['index' => __DIR__]]);

        return new Field(new Page(['slug' => 'test']), 'text', $value);
    }

    private static function block(string $type, array $content, bool $isHidden = false): array
    {
        return ['id' => uniqid(), 'type' => $type, 'isHidden' => $isHidden, 'content' => $content];
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
        // A partial array locale leaves `LC_ALL` unset, so the language code is used.
        $language = $this->languageWith(['LC_TIME' => 'en_US.UTF-8']);

        $this->assertSame('en', Util::languageToHreflang($language));
    }

    #[Test]
    public function to_excerpt_separates_block_elements_and_line_breaks(): void
    {
        $field = $this->field('<p>One.</p><p>Two<br>three<br class="x">four.</p><div>Five.</div><div>Six.</div><hr>Seven.');

        $this->assertSame('One. Two three four. Five. Six. Seven.', $field->toExcerpt());
    }

    #[Test]
    public function to_excerpt_decodes_entities_only_once(): void
    {
        $this->assertSame("Tom & Jerry's &lt;b&gt;", $this->field('<p>Tom &amp; Jerry&#039;s &amp;lt;b&amp;gt;</p>')->toExcerpt());
    }

    #[Test]
    public function to_excerpt_adds_no_space_between_a_closing_strong_and_a_comma(): void
    {
        $this->assertSame('A bold, word.', $this->field('<p>A <strong>bold</strong>, word.</p>')->toExcerpt());
    }

    #[Test]
    public function to_excerpt_keeps_only_the_visible_text_blocks_of_a_blocks_field(): void
    {
        $field = $this->field(json_encode([
            self::block('heading', ['level' => 'h2', 'text' => 'Heading']),
            self::block('text', ['text' => '<p>First.</p>']),
            self::block('text', ['text' => '<p>Hidden.</p>'], isHidden: true),
            self::block('text', ['text' => '<p>Second.</p>']),
        ]));

        $this->assertSame('First. Second.', $field->toExcerpt());
    }

    #[Test]
    public function to_excerpt_keeps_the_text_blocks_of_every_layout_column(): void
    {
        $column = fn (string $text) => ['id' => uniqid(), 'width' => '1/2', 'blocks' => [self::block('text', ['text' => "<p>{$text}</p>"])]];
        $field = $this->field(json_encode([
            ['id' => uniqid(), 'attrs' => [], 'columns' => [$column('Left.'), $column('Right.')]],
        ]));

        $this->assertSame('Left. Right.', $field->toExcerpt());
    }

    #[Test]
    public function to_excerpt_cuts_to_the_given_chars_at_a_word_boundary(): void
    {
        $this->assertSame('One two …', $this->field('<p>One two three</p>')->toExcerpt(10));
    }

    /** @return array<string, array{0: string}> */
    public static function bracketedTextCases(): array
    {
        return [
            'invalid JSON' => ['[Ad] Sale today'],
            'JSON without blocks' => ['[2024]'],
            'JSON objects without blocks' => ['[{"label":"Sale"}]'],
        ];
    }

    #[Test]
    #[DataProvider('bracketedTextCases')]
    public function to_excerpt_returns_text_starting_with_a_bracket_unchanged_unless_it_is_blocks_data(string $value): void
    {
        $this->assertSame($value, $this->field($value)->toExcerpt());
    }

    /** @return array<string, array{0: string}> */
    public static function emptyCases(): array
    {
        return [
            'empty field' => [''],
            'empty blocks field' => ['[]'],
        ];
    }

    #[Test]
    #[DataProvider('emptyCases')]
    public function to_excerpt_returns_an_empty_string_for_an_empty_field(string $value): void
    {
        $this->assertSame('', $this->field($value)->toExcerpt());
    }
}
