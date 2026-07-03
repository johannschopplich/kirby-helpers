<?php

declare(strict_types = 1);

namespace JohannSchopplich\Helpers;

use Kirby\Cms\Language;
use Kirby\Toolkit\Str;

final class Util
{
    /**
     * Normalizes a Kirby language to a hreflang-compatible code.
     * Drops any charset or modifier suffix and converts to lowercase with hyphens.
     *
     * @example `de_DE.utf8` → `de-de`
     * @example `de_DE.ISO-8859-1` → `de-de`
     * @example `de_DE@euro` → `de-de`
     * @example `en_US` → `en-us`
     */
    public static function languageToHreflang(Language $language): string
    {
        $locale = $language->locale(LC_ALL) ?? $language->code();
        $normalized = preg_replace('/[.@].*$/', '', $locale);

        return Str::slug($normalized);
    }
}
