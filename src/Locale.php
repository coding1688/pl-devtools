<?php

namespace Coding1688\DevTools;

use Illuminate\Http\Request;

/**
 * Resolves the devtools UI locale: explicit config, then the browser
 * Accept-Language header, then the application/default locale.
 */
final class Locale
{
    /** @var list<string> */
    public const SUPPORTED = ['en', 'zh_CN', 'zh_TW', 'ja', 'ko'];

    public static function resolve(?Request $request = null, ?string $configured = null): string
    {
        $configured ??= config('devtools.locale');
        if (is_string($configured) && in_array($configured, self::SUPPORTED, true)) {
            return $configured;
        }
        $request ??= request();
        foreach ($request->getLanguages() as $language) {
            $mapped = self::map((string) $language);
            if ($mapped !== null) {
                return $mapped;
            }
        }
        $app = (string) app()->getLocale();
        if (in_array($app, self::SUPPORTED, true)) {
            return $app;
        }
        $fallback = (string) config('app.fallback_locale', 'en');

        return in_array($fallback, self::SUPPORTED, true) ? $fallback : 'en';
    }

    public static function map(string $language): ?string
    {
        $tag = strtolower(str_replace('_', '-', $language));

        return match (true) {
            $tag === 'zh-tw', $tag === 'zh-hk', $tag === 'zh-mo', str_starts_with($tag, 'zh-hant') => 'zh_TW',
            $tag === 'zh-cn', $tag === 'zh-sg', $tag === 'zh', str_starts_with($tag, 'zh-hans') => 'zh_CN',
            str_starts_with($tag, 'ja') => 'ja',
            str_starts_with($tag, 'ko') => 'ko',
            str_starts_with($tag, 'en') => 'en',
            default => null,
        };
    }
}
