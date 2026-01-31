<?php

namespace JohannSchopplich\Helpers;

use Kirby\Cms\Language;
use Kirby\Toolkit\Str;

class Util
{
    /**
     * Normalizes a Kirby language to a hreflang-compatible code.
     * Strips `.utf8` suffix and converts to lowercase with hyphens.
     *
     * @example `de_DE.utf8` → `de-de`
     * @example `en_US` → `en-us`
     */
    public static function languageToHreflang(Language $language): string
    {
        $locale = $language->locale(LC_ALL) ?? $language->code();
        $normalized = preg_replace('/\.utf-?8$/i', '', $locale);

        return Str::slug($normalized);
    }
}
