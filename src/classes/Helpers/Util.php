<?php

declare(strict_types = 1);

namespace JohannSchopplich\Helpers;

use Kirby\Cms\Language;
use Kirby\Cms\Layouts;
use Kirby\Content\Field;
use Kirby\Data\Json;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;
use Throwable;

final class Util
{
    /**
     * Drops any charset or modifier suffix from a language's locale and
     * converts it to lowercase with hyphens.
     *
     * @example `de_DE.utf8` → `de-de`
     * @example `de_DE.ISO-8859-1` → `de-de`
     * @example `de_DE@euro` → `de-de`
     * @example `en_US` → `en-us`
     */
    public static function languageToHreflang(Language $language): string
    {
        $locale = $language->locale(LC_ALL) ?? $language->code();
        $normalizedLocale = preg_replace('/[.@].*$/', '', $locale);

        return Str::slug($normalizedLocale);
    }

    /**
     * Shortens a field to plain text. A value that decodes as a JSON list of
     * blocks or layout rows contributes its visible text blocks only; any
     * other value is read as HTML. The result is not escaped: run it through
     * `esc()` before echoing it into HTML.
     */
    public static function excerpt(Field $field, int $chars): string
    {
        $value = trim($field->value() ?? '');

        if ($value === '') {
            return '';
        }

        $html = $value;

        if (str_starts_with($value, '[')) {
            try {
                $data = Json::decode($value);
            } catch (Throwable) {
                $data = null;
            }

            // Every block has a `type`, every layout row its `columns`.
            $isBlocksData = is_array($data) && A::every(
                $data,
                fn ($item) => is_array($item) && (is_string($item['type'] ?? null) || is_array($item['columns'] ?? null))
            );

            if ($isBlocksData) {
                $html = Layouts::factory($data, ['parent' => $field->parent(), 'field' => $field])
                    ->toBlocks()
                    ->filterBy('type', 'text')
                    ->toHtml();
            }
        }

        // `Str::excerpt` only spaces out a tag that touches a word character,
        // so `end.</p><p>Next` would turn into `end.Next`.
        $html = preg_replace('!</(?:address|article|aside|blockquote|caption|dd|details|div|dl|dt|figcaption|figure|footer|h[1-6]|header|li|main|nav|ol|p|pre|section|summary|table|td|th|tr|ul)>|<(?:br|hr)\b[^>]*>!i', '$0 ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return Str::excerpt($text, $chars, strip: false);
    }
}
