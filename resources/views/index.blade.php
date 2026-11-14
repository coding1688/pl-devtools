@php
    $collectors = $collectors ?? [];
    $visibleCollectors = $standalone ? $collectors : array_values(array_filter($collectors, fn ($item) => in_array($item->key(), (array) config('devtools.toolbar_collectors', ['request', 'sql']), true)));
    $locale = \Coding1688\DevTools\Locale::resolve(request());
    $t = function (string $key, array $replace = [], ?string $fallback = null) use ($locale): string {
        $full = 'devtools::ui.'.$key;
        if (\Illuminate\Support\Facades\Lang::has($full, $locale)) {
            return trans($full, $replace, $locale);
        }
        if (\Illuminate\Support\Facades\Lang::has($full, 'en')) {
            return trans($full, $replace, 'en');
        }

        return $fallback ?? $key;
    };
@endphp
@if($standalone)
<!doctype html>
<html lang="{{ str_replace('_', '-', $locale) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{{ $t('title') }}</title>
@if(($refresh ?? 0) > 0 && request()->query('request') === null)<meta http-equiv="refresh" content="{{ $refresh }}">@endif
@endif
@if(! ($fragment ?? false))
<style>{!! is_string(config('devtools.css')) && is_file(config('devtools.css')) ? file_get_contents(config('devtools.css')) : '' !!}</style>
@endif
@if($standalone)
</head><body class="sql-debug-body"><main class="sql-debug-page">
    <h1>{{ $t('title') }}</h1>
    <p>{{ $t('history_intro', ['limit' => config('devtools.history_limit')]) }}
        <a href="?type=all">{{ $t('all') }} {{ $counts['all'] ?? 0 }}</a> ·
        <a href="?type=html">{{ $t('html') }} {{ $counts['html'] ?? 0 }}</a> ·
        <a href="?type=ajax">{{ $t('ajax') }} {{ $counts['ajax'] ?? 0 }}</a>
    </p>
    <p>{{ $t('binding_hint', ['ms' => config('devtools.slow_ms')]) }}
        <a href="{{ route((string) config('devtools.route_name', 'devtools.index')) }}?fresh=1">{{ $t('refresh_all') }}</a> ·
        <a href="{{ request()->fullUrlWithQuery(['export' => 'json']) }}">{{ $t('export_all') }}</a>
    </p>
    <p class="sql-debug-controls">{{ $t('auto_refresh') }}
        @foreach([0 => $t('off'), 2 => $t('seconds', ['n' => 2]), 5 => $t('seconds', ['n' => 5])] as $seconds => $label)
            <a href="{{ request()->fullUrlWithQuery(['refresh' => $seconds]) }}">{{ $label }}</a>@if(! $loop->last) · @endif
        @endforeach
        @if(($refresh ?? 0) > 0) · {{ $t('seconds', ['n' => $refresh]) }} @endif
        <form method="POST" action="{{ route((string) config('devtools.route_name', 'devtools.index').'.clear') }}" class="sql-debug-inline-form">@csrf<button type="submit" class="sql-debug-link-button">{{ $t('clear') }}</button></form>
    </p>
@else
<details class="sql-debug-widget" data-sql-debug data-signature="{{ $profiles[0]['id'] ?? '' }}" data-refresh-url="{{ route((string) config('devtools.route_name', 'devtools.index')) }}?toolbar=1">
    <summary>{{ $t('summary_sql', ['count' => $profiles[0]['query_count'], 'ms' => $profiles[0]['sql_ms']]) }} <span>{{ $t('summary_request', ['ms' => $profiles[0]['duration_ms']]) }}</span>
        <span>{{ $t('summary_non_sql', ['ms' => $profiles[0]['non_sql_ms'] ?? '—']) }}</span>
        <span>{{ $t('summary_peak', ['mb' => $profiles[0]['peak_memory_mb'] ?? '—']) }}</span> <span class="sql-debug-slow">{{ $t('summary_slow', ['n' => $profiles[0]['slow_count']]) }}</span> <span>{{ $t('summary_repeat', ['n' => $profiles[0]['repeated_count']]) }}</span></summary>
    <div class="sql-debug-panel"><p class="sql-debug-controls">
        <span>{{ $t('local_only') }} · <a href="{{ route((string) config('devtools.route_name', 'devtools.index')) }}" target="_blank" rel="noopener">{{ $t('all_panels') }}</a></span>
        @if(!empty($recent))
            <label class="sql-debug-selector">{{ $t('view') }}
                <select data-devtools-select>
                    <option value="">{{ $t('auto_latest') }}</option>
                    @foreach($recent as $option)
                        <option value="{{ $option['id'] }}" @selected(($selected ?? null) === $option['id'])>{{ substr((string) $option['created_at'], 11, 8) }} · {{ $option['method'] }} {{ $option['path'] }} · {{ $option['status'] }} · {{ $option['duration_ms'] }} ms</option>
                    @endforeach
                </select>
            </label>
        @endif
    </p>
@endif
@forelse($profiles as $profile)
    <details class="sql-debug-request" @if(count($profiles) === 1) open @endif>
        <summary><span class="sql-debug-kind" @class(['sql-debug-kind-ajax' => ($profile['type'] ?? 'html') === 'ajax'])>{{ strtoupper($profile['type'] ?? 'html') }}</span> <b>{{ $profile['method'] }} {{ $profile['path'] }}</b> · {{ $profile['status'] }} · {{ $t('summary_request', ['ms' => $profile['duration_ms']]) }} · {{ $t('summary_sql', ['count' => $profile['query_count'], 'ms' => $profile['sql_ms']]) }} · <span class="sql-debug-slow">{{ $t('summary_slow', ['n' => $profile['slow_count']]) }}</span> · {{ $t('summary_repeat', ['n' => $profile['repeated_count']]) }}<small>{{ $profile['host'] }} · {{ $profile['created_at'] }}</small></summary>
        <p class="sql-debug-hint"><a href="{{ request()->fullUrlWithQuery(['request' => $profile['id'], 'export' => 'json']) }}">{{ $t('export_one') }}</a></p>
        @if($collectors === [])
            <p>{{ $t('no_panels') }}</p>
        @else
            <div class="devtools" data-devtools-tabs>
                <nav class="devtools-tabs">
                    @foreach($visibleCollectors as $collector)
                        <button type="button" class="devtools-tab @if($loop->first) is-active @endif" data-devtools-tab="{{ $collector->key() }}">{{ $t('panel.'.$collector->key(), [], $collector->label()) }}</button>
                    @endforeach
                </nav>
                @foreach($visibleCollectors as $collector)
                    <section class="devtools-panel" data-devtools-panel="{{ $collector->key() }}" @if(! $loop->first) hidden @endif>
                        @include($collector->view(), ['data' => $collector->collect($profile), 'profile' => $profile, 't' => $t])
                    </section>
                @endforeach
            </div>
        @endif
    </details>
@empty
    <p>{{ $t('empty') }}</p>
@endforelse
@if($standalone)
    @include('pl-devtools::partials.tabs-script')
</main>
</body></html>
@else
    </div>
    @include('pl-devtools::partials.tabs-script')
    @if(! ($fragment ?? false))
        @include('pl-devtools::partials.toolbar-refresh')
    @endif
</details>
@endif
