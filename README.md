# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-devtools) · [Issues](https://github.com/coding1688/pl-devtools/issues)

## Version 0.5.0 baseline

The three Moonsea packages restart at `v0.5.0`. Earlier tags and GitHub Releases have been removed; commit history remains. Update Composer version constraints and lock files explicitly. This is a new version baseline, not an automatic upgrade from the removed releases.

## Laravel request profiling and diagnostics

SQL timelines, request details, outgoing HTTP/cache telemetry, server information, performance summaries, log excerpts and configurable security checks in one package. No business models or migrations are required. Blade applications can use the injected toolbar; API applications can use response headers and the history endpoint.

## Requirements and installation

Declared support: PHP `^8.2` and Laravel components `^11 | ^12 | ^13`. The chosen Laravel version may require newer PHP. This README describes current source; use a matching release. Laravel discovers the provider automatically. Install development dependencies in environments that need the tool.

```bash
composer require --dev coding1688/pl-devtools
php artisan vendor:publish --tag=devtools-config
```

If your Composer registry cannot resolve the package, add the source repository before installing:

```bash
composer config repositories.pl-devtools vcs https://github.com/coding1688/pl-devtools.git
```

## Enable locally

The profiler is disabled by default. Enable both settings in a permitted development environment. The default history endpoint is `/__debug/sql`.

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

## Configuration

Use `config/devtools.php` and `devtools.*`. Precedence: package defaults → legacy `pl.devtools` → `devtools`. Maps merge; lists replace, including empty lists. Legacy configuration is imported at provider registration. New runtime changes use canonical keys. The old `pl-devtools-config` publish tag remains an alias.

```php
// config/devtools.php
'enabled' => env('PL_DEVTOOLS_ENABLED', false),
'environments' => ['local', 'testing'],
'hosts' => [],
'collect' => true,
'store_cache' => true,
'log' => false,
'toolbar' => true,
'authorization_gate' => null,
'locale' => null,
```

`collect` controls HTTP instrumentation. `store_cache` stores request history; `log` independently logs profile summaries. Disable both to avoid persistent profiles. `toolbar=false` disables HTML injection while retaining collection/headers. `enabled`, debug, environment, host and authorization checks still apply. Disabling collection also prevents access to the profiling history controller. Empty `hosts` allows any host within the allowed environment.

## Shared environments and authorization

Do not expose diagnostics as a public endpoint. Set allowed environments/hosts and configure a host-defined Gate. The example delegates to an application permission you must implement:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-devtools', fn ($user) => $user->can('inspect-diagnostics'));

// config/devtools.php
'authorization_gate' => 'view-devtools',
```

The Gate applies to profiling, the injected toolbar and history access. Add your authentication middleware through `devtools.middleware` when needed. A Gate name alone does not establish a login session or validate a bearer token. The history routes use the `web` middleware group; the clear operation is POST. Rebuild route/config caches after changing deployment settings.

## Languages

UI catalogs include `en`, `zh_CN`, `zh_TW`, `ja` and `ko`. An explicit `devtools.locale` wins; otherwise the resolver considers browser language, application locale and fallback locale. English console commands and technical identifiers are not all localized.

## Data handling and failure isolation

Request input values and SQL bindings are not included in their respective detail records. Referer URLs, HTTP failure messages and log summaries use redaction: URL credentials/query strings/fragments and common secret assignments are removed. Extend sensitive key names with `devtools.redact_keys`. Secrets in URL paths, raw SQL literals or arbitrary business text cannot be reliably identified. Authorization is still required; redaction is not a confidentiality guarantee.

Collection and toolbar failures do not replace successful application responses or original business exceptions. Diagnostic failures are not recursively reported through the instrumented logger. Limit history/TTL and review cache/log access in shared environments.

## CLI diagnostics

The read-only audit command works without enabling HTTP profiling. `--fresh` bypasses its cached report; `--json` outputs JSON. `--fail-on` accepts `none`, `info`, `low`, `medium`, `high`; the default threshold is `high`. A configured failing finding can produce a nonzero exit code. These checks are configuration diagnostics, not a complete security assessment.

```bash
php artisan devtools:audit
php artisan devtools:audit --json --fresh --fail-on=high
```

## Extending the package

Implement `Collectors\Collector` and configure collector classes in `devtools.collectors`. Implement `Diagnostics\Security\Check` for host-specific checks and register them in `devtools.security.checks`. Follow the contracts in this version; there is no dependency on a particular admin framework. Published views can be customized with `pl-devtools-views`, and assets with `pl-devtools-assets`.

## Development and upgrades

Tests cover configuration compatibility, default registration, access controls, failure isolation, redaction and five-language key consistency. CI defines PHP/Laravel compatibility jobs; consult release results. When upgrading, migrate runtime `pl.devtools.*` writes to `devtools.*`, review access controls, and rebuild the host caches.

```bash
composer install
composer test
```

## License

[MIT License](LICENSE). Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
