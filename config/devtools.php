<?php

use Coding1688\DevTools\Collectors\LogsCollector;
use Coding1688\DevTools\Collectors\PerformanceCollector;
use Coding1688\DevTools\Collectors\RequestCollector;
use Coding1688\DevTools\Collectors\SecurityCollector;
use Coding1688\DevTools\Collectors\ServerCollector;
use Coding1688\DevTools\Collectors\SqlCollector;
use Coding1688\DevTools\Collectors\TelemetryCollector;
use Coding1688\DevTools\Diagnostics\Security\Checks\AppDebugCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\AppKeyCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\BcryptRoundsCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\ComposerAuditCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\DatabaseUserCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\DebugToolsExposureCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\HttpsUrlCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\ProductionDriversCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\PublicExposureCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\SessionCookieCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\StorageWritableCheck;
use Coding1688\DevTools\Diagnostics\Security\Checks\UploadExtensionCheck;

return [
    /*
    | The profiler is opt-in. Nothing is recorded, no middleware is prepended
    | and no route is registered unless this is explicitly true.
    */
    'enabled' => env('PL_DEVTOOLS_ENABLED', false),

    // Independent collection, history persistence and HTML toolbar switches.
    'collect' => true,
    'toolbar' => true,

    /* Environments allowed even when enabled. Empty means any environment. */
    'environments' => ['local', 'testing'],

    /* Hosts allowed even when enabled. Empty means any host. */
    'hosts' => [],

    /* Route that renders the history page. */
    'path' => '__debug/sql',
    'route_name' => 'debug.sql',

    /* Extra middleware for the history route; add auth in shared environments. */
    'middleware' => [],

    /* Optional Laravel gate required for profiling, toolbar and history access. */
    'authorization_gate' => null,

    /* Show which controller handled the request. */
    /* Collectors rendered as tabs on the history page. */
    'collectors' => [
        RequestCollector::class,
        SqlCollector::class,
        ServerCollector::class,
        SecurityCollector::class,
        TelemetryCollector::class,
        PerformanceCollector::class,
        LogsCollector::class,
    ],

    /* Per-request HTTP client and cache telemetry. */
    'telemetry' => [
        'enabled' => true,
        'http' => true,
        'cache' => true,
        'max_http' => 20,
    ],

    /* Cross-request aggregation shown in the Performance panel. */
    'performance' => ['top' => 10],

    /* Application log tail shown in the Logs panel. */
    'logs' => [
        'path' => null,
        'levels' => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING'],
        'limit' => 50,
        'max_bytes' => 524288,
    ],

    /* Security diagnostics: read-only checks, reused by devtools:audit. */
    'security' => [
        'checks' => [
            AppDebugCheck::class,
            AppKeyCheck::class,
            SessionCookieCheck::class,
            HttpsUrlCheck::class,
            DebugToolsExposureCheck::class,
            StorageWritableCheck::class,
            PublicExposureCheck::class,
            DatabaseUserCheck::class,
            UploadExtensionCheck::class,
            ProductionDriversCheck::class,
            BcryptRoundsCheck::class,
            ComposerAuditCheck::class,
        ],
        'composer_audit' => false,
        'upload_extensions_config' => 'files.uploads.allowed_extensions',
    ],
    'security_ttl' => 300,

    /* Route names or path patterns excluded from profiling (no toolbar, no profile). */
    'ignore_routes' => [],
    'ignore_paths' => [],

    /* Panels shown inside the inline toolbar; the history page shows all. */
    'toolbar_collectors' => ['request', 'sql', 'telemetry'],

    /* Show the toolbar inside iframes (Layui/layer tabs); polling only runs in the top window. */
    'toolbar_in_iframes' => true,

    /* Number of recent requests offered in the toolbar selector. */
    'toolbar_history' => 20,

    /* Seconds between toolbar auto-refresh polls; 0 disables polling. */
    'toolbar_refresh' => 2,

    /* Seconds to cache the server diagnostics snapshot. */
    'diagnostics_ttl' => 300,

    /* Record request headers/lists for the Request panel (values stay masked). */
    'collect_request' => true,

    'show_controller' => true,

    /* Record the authenticated user; set user_guards to [] to disable. */
    'track_user' => true,
    'user_guards' => ['web'],

    /* How many duplicate-query groups to surface per request. */
    'duplicate_groups' => 5,

    /* How many call-site hotspots (same file:line repeating) to surface. */
    'hotspots' => 5,

    /* Drop queries whose WHERE is always false, e.g. scope short-circuits. */
    'ignore_impossible_queries' => false,

    /* Auto refresh interval for the history page; 0 keeps it manual. */
    'refresh_seconds' => 0,

    /* Optional labels for custom measure() stages. */
    'stage_labels' => [],

    'slow_ms' => 100,
    'max_queries' => 500,
    'max_sql_length' => 16384,
    'history_limit' => 50,

    'cache_store' => 'file',
    'ttl_seconds' => 1800,

    /* Persist profiles to cache / log. Turn both off for the smallest footprint. */
    'store_cache' => true,
    'log' => false,
    'log_channel' => 'single',

    /* Absolute file paths ignored when attributing a query to application code. */
    'exclude_paths' => [],

    /* Filled by the service provider with the published or package CSS path. */
    'css' => null,

    /* Optional locale list shown on the Server panel (host specific). */
    'locales' => null,

    /* UI locale (en, zh_CN, zh_TW, ja, ko). Null follows the browser language, then the app locale. */
    'locale' => null,
];
