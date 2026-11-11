# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

Laravel developer diagnostics: request profiler, SQL timeline, server information, security checks, HTTP/cache telemetry, performance rankings and log summary — all on one page.

- PHP 8.2+, Laravel 11 / 12 / 13
- Disabled by default; requires `APP_DEBUG=true` and an explicit opt-in
- Frontend-agnostic: Blade, Layui, Vue SPA
- The same security checks run in CI: `php artisan devtools:audit`

## Features

| Panel | Content |
| --- | --- |
| Request | Method/path/status, route, `controller@action`, authenticated user, host/IP, whitelisted headers, middleware, query and input field names |
| SQL | Count/time, slow queries, stage timings, N+1 call-site hotspots, timeline, duplicate groups, query details |
| HTTP / Cache | Outgoing HTTP calls (method/URL/status/time/error) and cache hit/miss/write/forget counters |
| Server | PHP/SAPI/OS/load, Laravel version and environment, DB connection/version, cache/queue/session/fs drivers, disks, extensions, OPcache |
| Security | 12 read-only checks with severity and remediation hints |
| Performance | Cross-request rankings: slowest requests, slowest queries, most repeated statements |
| Logs | Log tail: counts by level and the latest entries (first line only, truncated) |

Also included:

- Inline bottom toolbar (switch panels, pin a historical request, follow SPA/Layui navigation)
- Standalone history page (filter, auto refresh, clear, export JSON)
- `devtools:audit` command (CI gate)

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13

## Installation

### 1. Install via Composer

The package is published on GitHub (VCS repository). Add it to the host `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/coding1688/pl-devtools.git" }
]
```

Then install it as a dev dependency:

```bash
composer require --dev coding1688/pl-devtools
```

Laravel discovers `Coding1688\DevTools\DevToolsServiceProvider` automatically. If package discovery is disabled, add it to `bootstrap/providers.php`:

```php
Coding1688\DevTools\DevToolsServiceProvider::class,
```

> With `--dev`, `composer install --no-dev` in production does not ship it.

### 2. Enable

`.env`:

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

The legacy variable `PL_DEBUG_ENABLED` is still accepted.

### 3. Open

- The toolbar appears at the bottom of any HTML page.
- Standalone page: `http://<your-host>/__debug/sql`.

The first visit is empty by design: open a page or call an endpoint, then refresh.

### 4. Configure (optional)

Package defaults are merged into `pl.devtools`. Override them in the host `config/pl.php`:

```php
return [
    'devtools' => [
        'enabled' => env('PL_DEVTOOLS_ENABLED', false),
        'hosts' => ['b.techpeek.local'],   // empty = any host
        'middleware' => ['auth'],          // extra protection for the debug route
        'slow_ms' => 100,
    ],
];
```

Publish views or assets if you need to customize the UI:

```bash
php artisan vendor:publish --tag=pl-devtools-views
php artisan vendor:publish --tag=pl-devtools-assets
```

## Quick start

1. Open any page; the bottom toolbar shows SQL count, duration and peak memory for the request.
2. Expand it and switch between `Request / SQL / HTTP / Cache`.
3. Click "全部面板与历史请求" to open the standalone page with Server / Security / Performance / Logs and the last 50 requests.
4. Run `php artisan devtools:audit` for a security check.

## Toolbar

Fixed at the bottom and showing a summary of the current request. When expanded:

- **Panel switch**: `toolbar_collectors` controls which panels appear (Request / SQL / HTTP-Cache by default).
- **View selector**:
  - `自动（最新）` (auto/latest): refreshes to the newest request every `toolbar_refresh` seconds.
  - Pick a historical request to pin it and stop following; switch back to auto at any time.
- **State**: the expanded/collapsed state and the selected entry survive refreshes.
- **Entry point**: "全部面板与历史请求" opens the standalone page.

Compatibility:

- Blade / Layui full-page rendering: the toolbar is re-rendered with each page.
- Vue SPA and Layui iframe tabs: the toolbar polls `?toolbar=1`; polling only runs in the top-level window.
- `toolbar_in_iframes = false` hides the toolbar inside iframes.
- `toolbar_refresh = 0` disables polling entirely (useful for classic Blade sites).

## Standalone page

Default path `/__debug/sql`. Query parameters:

| Parameter | Purpose |
| --- | --- |
| `?type=all\|html\|ajax` | Filter by request type |
| `?request=<uuid>` | Show a single profile |
| `?refresh=2\|5` | Auto-refresh interval in seconds |
| `?fresh=1` | Clear the cached Server / Security / Performance / Logs snapshots |
| `?export=json` | Download the current selection as JSON |
| `?toolbar=1` | Fragment endpoint used by toolbar polling (rarely needed manually) |

Page actions: clear history, export everything, export a single request.

## Panels

### Request

Request line, status, route, controller, user, host/IP and duration; whitelisted headers (`accept`, `content-type`, `x-requested-with`, `referer`, `user-agent`); middleware chain; field names of query parameters and submitted input.

Only field names and whitelisted headers are stored — never values, cookies or session content. Disable with `collect_request = false`.

### SQL

- `slow_ms` threshold, slow queries highlighted.
- Stage timings: instrument with `$profiler->measure('name', fn () => ...)`; labels via `stage_labels`.
- N+1 hotspots grouped by call site (`file:line`).
- Timeline ordered by execution with start offset and duration.
- Duplicate queries grouped by statement structure.
- Query details sorted by duration with source, connection and binding count.

### HTTP / Cache

Laravel HTTP Client and Cache events:

- Outgoing calls: method, URL (query string stripped), status, duration, failure reason.
- Cache: hit / miss / write / forget counters.

Only requests made through `Http::` are counted; raw `curl` / Guzzle calls are not. `telemetry` toggles HTTP and cache capture separately.

### Server

PHP version/SAPI/bits, OS/hostname/web server/load, INI values, Laravel version and environment, `APP_URL`, DB connection and version, cache/queue/session/filesystem/broadcast/mail drivers, disk usage and writability, 17 common extensions, OPcache hit rate and memory.

Cached for `diagnostics_ttl`; not part of per-request profiles. `?fresh=1` forces a refresh.

### Security

12 read-only checks with severity and remediation:

| Severity | Checks |
| --- | --- |
| high | `APP_DEBUG` outside local, missing/short `APP_KEY`, non-HTTPS `APP_URL`, devtools enabled outside local, non-writable `storage`/`bootstrap/cache`, `.env`/`.git` inside `public/`, executable extensions in the upload allow-list, vulnerable dependencies (optional) |
| medium | Session cookie `secure`/`http_only`/`same_site`, DB user is root/postgres |
| low | `QUEUE_CONNECTION=sync` / `CACHE_STORE=file` in production, `BCRYPT_ROUNDS<10` |

### Performance

Cross-request aggregation from cached profiles: slowest requests, slowest queries, most repeated statements. `performance.top` controls the number of rows.

### Logs

Reads only the tail of the log (`logs.max_bytes`), filters by `logs.levels`, keeps the first line only (max 300 characters) and never renders stack traces.

## Security audit

```bash
php artisan devtools:audit                    # table output, non-zero on high findings
php artisan devtools:audit --fail-on=medium  # non-zero on medium or higher
php artisan devtools:audit --fail-on=none    # report only
php artisan devtools:audit --json --fresh    # JSON output, ignore cache
```

Exit code: `1` when a finding reaches the threshold, otherwise `0`.

CI example (GitHub Actions):

```yaml
name: security-audit
on: [push, pull_request]
jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install --no-interaction --prefer-dist
      - run: php artisan devtools:audit --fail-on=high
```

Dependency auditing is disabled by default (it runs `composer audit`); enable it with `security.composer_audit = true`.

## Configuration

All keys live under `pl.devtools`.

### Basics

| Key | Default | Description |
| --- | --- | --- |
| `enabled` | `env('PL_DEVTOOLS_ENABLED', env('PL_DEBUG_ENABLED', false))` | Master switch |
| `environments` | `['local','testing']` | Allowed environments |
| `hosts` | `[]` | Allowed hosts, empty = any |
| `path` | `__debug/sql` | Standalone page path |
| `route_name` | `debug.sql` | Route name |
| `middleware` | `[]` | Extra middleware for the debug route (e.g. `auth`) |
| `cache_store` | `file` | Cache store used for profiles |
| `ttl_seconds` | `1800` | Profile retention |
| `history_limit` | `50` | History entries |
| `store_cache` | `true` | Persist profiles |
| `log` | `false` | Write the query log |
| `log_channel` | `single` | Log channel |
| `css` | `null` | Custom CSS path |

### Collection

| Key | Default | Description |
| --- | --- | --- |
| `slow_ms` | `100` | Slow query threshold |
| `max_queries` | `500` | Max queries per request |
| `max_sql_length` | `16384` | SQL truncation length |
| `exclude_paths` | `[]` | App paths ignored when attributing source |
| `collect_request` | `true` | Capture Request panel data |
| `show_controller` | `true` | Show controller/action |
| `track_user` / `user_guards` | `true` / `['backend','web','partner']` | Record the authenticated user and which guards to check |
| `duplicate_groups` | `5` | Duplicate groups per request |
| `hotspots` | `5` | Call-site hotspots per request |
| `ignore_impossible_queries` | `false` | Drop always-false queries such as `where 0 = 1` |
| `stage_labels` | `[]` | Display labels for `measure()` stages |
| `ignore_routes` | `[]` | Route names to skip, supports `admin.*` |
| `ignore_paths` | `[]` | Path patterns to skip, supports `api/*` |

### Toolbar

| Key | Default | Description |
| --- | --- | --- |
| `toolbar_collectors` | `['request','sql','telemetry']` | Panels shown in the toolbar |
| `toolbar_refresh` | `2` | Polling interval in seconds, 0 disables |
| `toolbar_history` | `20` | Recent requests offered by the selector |
| `toolbar_in_iframes` | `true` | Show the toolbar inside iframes |

### Diagnostics

| Key | Default | Description |
| --- | --- | --- |
| `diagnostics_ttl` | `300` | Cache TTL for Server / Performance / Logs |
| `security_ttl` | `300` | Cache TTL for security results |
| `telemetry.enabled` / `.http` / `.cache` / `.max_http` | `true` / `true` / `true` / `20` | HTTP and cache telemetry |
| `performance.top` | `10` | Rows per ranking |
| `logs.path` | `null` (`storage/logs/laravel.log`) | Log file |
| `logs.levels` | `ERROR/CRITICAL/ALERT/EMERGENCY/WARNING` | Captured levels |
| `logs.limit` | `50` | Displayed entries |
| `logs.max_bytes` | `524288` | Tail bytes read |
| `security.checks` | 12 check classes | Enabled checks |
| `security.composer_audit` | `false` | Run `composer audit` |

## Extending

### Custom panel

Implement `Coding1688\DevTools\Collectors\Collector`:

```php
final class QueueCollector implements Collector
{
    public function key(): string { return 'queue'; }
    public function label(): string { return 'Queue'; }
    public function collect(array $profile): array { return [/* ... */]; }
    public function view(): string { return 'pl-devtools::panels.queue'; }
}
```

Append it to `pl.devtools.collectors` and add a view under `resources/views/vendor/pl-devtools/panels/`.

### Custom security check

Implement `Coding1688\DevTools\Diagnostics\Security\Check` and register it in `pl.devtools.security.checks`; it then appears both in the panel and in `devtools:audit`.

### Stage timing

```php
use Coding1688\DevTools\SqlQueryProfiler;

$profiler = app(SqlQueryProfiler::class);
$data = $profiler->measure('product_data', fn () => $service->prepare($id));
```

## Commands

| Command | Description |
| --- | --- |
| `php artisan devtools:audit` | Run security checks; `--json`, `--fresh`, `--fail-on=high\|medium\|low\|info\|none` |

## Troubleshooting

| Symptom | What to check |
| --- | --- |
| Toolbar missing | `APP_DEBUG=true`, `PL_DEVTOOLS_ENABLED=true`, environment in `environments`, host in `hosts`; response must be HTML containing `</body>`; check `ignore_routes` / `ignore_paths` |
| No toolbar on AJAX | By design (JSON cannot host HTML); use `?type=ajax` on the standalone page or the `X-Debug-SQL-Url` response header |
| SPA / Layui iframe does not refresh | Polling is controlled by `toolbar_refresh` (2s default) and runs in the top window only; look for `__debug/sql?toolbar=1` in the network panel |
| Empty data | Trigger a request first; check `cache_store` writability, `history_limit`, `store_cache` |
| Page got slower | Increase `diagnostics_ttl`, disable `telemetry`, exclude busy routes with `ignore_*` |
| Production | Do not enable; use `devtools:audit` instead |

## Compatibility

- Laravel 11 / 12 / 13, PHP 8.2+
- Semantic versioning: minor releases add features, patches fix bugs
- Version history: see [Tags](https://github.com/coding1688/pl-devtools/tags)

## Related packages

| Package | Description |
| --- | --- |
| `coding1688/pl-files` | Uploads, file references, multi-disk and multi-cloud replication |
| `coding1688/pl-fields` | Field types, validation and option sources |
| `coding1688/pl-devtools` | This package |

## License

MIT
