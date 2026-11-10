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
