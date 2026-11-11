<?php

return [
    /*
    | The profiler is opt-in. Nothing is recorded, no middleware is prepended
    | and no route is registered unless this is explicitly true.
    */
    'enabled' => env('PL_DEBUG_ENABLED', false),

    /* Environments allowed even when enabled. Empty means any environment. */
    'environments' => ['local', 'testing'],

    /* Hosts allowed even when enabled. Empty means any host. */
    'hosts' => [],

    /* Route that renders the history page. */
    'path' => '__debug/sql',
    'route_name' => 'debug.sql',

    /* Extra middleware for the history route; add auth in shared environments. */
    'middleware' => [],

    /* Show which controller handled the request. */
    /* Collectors rendered as tabs on the history page. */
    'collectors' => [\Coding1688\DevTools\Collectors\RequestCollector::class, \Coding1688\DevTools\Collectors\SqlCollector::class],

    /* Record request headers/lists for the Request panel (values stay masked). */
    'collect_request' => true,

    'show_controller' => true,

    /* Record the authenticated user; set user_guards to [] to disable. */
    'track_user' => true,
    'user_guards' => ['backend', 'web', 'partner'],

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
];
