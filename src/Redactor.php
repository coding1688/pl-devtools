<?php

namespace Coding1688\DevTools;

final class Redactor
{
    public static function text(string $text): string
    {
        $text = preg_replace_callback('~https?://[^\s<>"\']+~i', fn (array $match): string => self::url($match[0]), $text) ?? '';
        $text = preg_replace('~\bBearer\s+[^\s,"\']+~i', 'Bearer [REDACTED]', $text) ?? '';
        $keys = array_merge(['password', 'passwd', 'token', 'access_token', 'refresh_token', 'secret', 'secret_key', 'api_key', 'authorization', 'cookie'], (array) config('devtools.redact_keys', []));
        $keys = implode('|', array_map(fn ($key): string => preg_quote((string) $key, '~'), $keys));

        return preg_replace('~(["\']?(?:'.$keys.')["\']?\s*[:=]\s*)(?:"[^"]*"|\'[^\']*\'|[^\s,;}]+)~i', '$1[REDACTED]', $text) ?? '';
    }

    public static function url(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['host'])) {
            return '[REDACTED URL]';
        }

        return ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? '/').(isset($parts['query']) ? '?[REDACTED]' : '');
    }
}
