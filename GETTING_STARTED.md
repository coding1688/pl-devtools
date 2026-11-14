# Getting started

Maintained by [coding1688 / Moonsea](https://moonsea.dev). MIT licensed.

Install with Composer. If the package is not available from your configured registry, add its GitHub VCS repository to your application's composer.json first. The existing package name and PHP namespace are unchanged. Laravel discovers the service provider automatically.

Configuration precedence is: package defaults < legacy `pl.devtools` < `devtools`. New applications should use only `config/devtools.php`. Old `pl.*` values are accepted at provider registration time; runtime overrides must use the canonical key. Maps merge and lists replace (including an empty list). After changing cached configuration, rebuild your application's configuration cache.

## Local development

```bash
composer require --dev coding1688/pl-devtools
php artisan vendor:publish --tag=devtools-config
```

Enable `PL_DEVTOOLS_ENABLED=true` and `APP_DEBUG=true` in a permitted local environment. The default history path is `/__debug/sql`. The package is disabled by default and needs no business models or database migrations.

## Independent switches

In `config/devtools.php`:

```php
'collect' => true,       // request/SQL collection
'store_cache' => true,   // persist history
'log' => false,          // optional profile logging
 'toolbar' => false,     // API-only applications need no HTML injection
```

`enabled` and `app.debug` still gate HTTP instrumentation. Turn off both `store_cache` and `log` to avoid persistent profiles. Turning off `toolbar` retains response headers and, when enabled, the history endpoint.

## Shared development environment

Explicitly configure allowed environments and hosts, and set `devtools.authorization_gate` to a host-defined Laravel Gate. The gate applies to profiling and the toolbar as well as history access. Use your own authentication middleware for the history route when appropriate. Do not expose diagnostics publicly.

URL queries/credentials, common secret keys and bearer values are redacted before storage/display. Extend `devtools.redact_keys` for your own secret key names. Avoid placing secrets in URL paths or free-form log messages.

## CI diagnostics

```bash
php artisan devtools:audit
```

The console audit command does not require enabling HTTP profiling or the HTML toolbar. Install development dependencies in the CI job that runs it.

UI locales: English, Simplified Chinese, Traditional Chinese, Japanese and Korean. Set `devtools.locale` explicitly or use browser/application language detection.
