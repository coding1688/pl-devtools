@php($collectors = $collectors ?? [])
@php($visibleCollectors = $standalone ? $collectors : array_values(array_filter($collectors, fn ($item) => in_array($item->key(), (array) config('pl.devtools.toolbar_collectors', ['request', 'sql']), true))))
@if($standalone)
<!doctype html>
<html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>开发工具</title>
@if(($refresh ?? 0) > 0 && request()->query('request') === null)<meta http-equiv="refresh" content="{{ $refresh }}">@endif
@endif
@if(! ($fragment ?? false))
<style>{!! is_string(config('pl.devtools.css')) && is_file(config('pl.devtools.css')) ? file_get_contents(config('pl.devtools.css')) : '' !!}</style>
@endif
@if($standalone)
</head><body class="sql-debug-body"><main class="sql-debug-page">
    <h1>开发工具</h1>
    <p>最近 {{ config('pl.devtools.history_limit') }} 个请求，包含 AJAX 接口。
        <a href="?type=all">全部 {{ $counts['all'] ?? 0 }}</a> ·
        <a href="?type=html">页面 {{ $counts['html'] ?? 0 }}</a> ·
        <a href="?type=ajax">AJAX {{ $counts['ajax'] ?? 0 }}</a>
    </p>
    <p>绑定参数不展开。慢查询阈值 {{ config('pl.devtools.slow_ms') }} ms。<a href="{{ route((string) config('pl.devtools.route_name', 'devtools.index')) }}?fresh=1">刷新全部请求</a> · <a href="{{ request()->fullUrlWithQuery(['export' => 'json']) }}">导出 JSON</a></p>
    <p class="sql-debug-controls">自动刷新：
        @foreach([0 => '关闭', 2 => '2 秒', 5 => '5 秒'] as $seconds => $label)
            <a href="{{ request()->fullUrlWithQuery(['refresh' => $seconds]) }}">{{ $label }}</a>@if(! $loop->last) · @endif
        @endforeach
        @if(($refresh ?? 0) > 0) · 每 {{ $refresh }} 秒自动刷新 @endif
        <form method="POST" action="{{ route((string) config('pl.devtools.route_name', 'devtools.index').'.clear') }}" class="sql-debug-inline-form">@csrf<button type="submit" class="sql-debug-link-button">清空历史</button></form>
    </p>
@else
<details class="sql-debug-widget" data-sql-debug data-signature="{{ $profiles[0]['id'] ?? '' }}" data-refresh-url="{{ route((string) config('pl.devtools.route_name', 'devtools.index')) }}?toolbar=1">
    <summary>SQL {{ $profiles[0]['query_count'] }} 条 · {{ $profiles[0]['sql_ms'] }} ms <span>请求 {{ $profiles[0]['duration_ms'] }} ms</span>
        <span>非 SQL {{ $profiles[0]['non_sql_ms'] ?? '—' }} ms</span>
        <span>峰值 {{ $profiles[0]['peak_memory_mb'] ?? '—' }} MB</span> <span class="sql-debug-slow">慢 {{ $profiles[0]['slow_count'] }}</span> <span>重复 {{ $profiles[0]['repeated_count'] }}</span></summary>
    <div class="sql-debug-panel"><p class="sql-debug-controls">仅本地开发环境 ·
        @if(!empty($recent))
            <label>查看：<select data-devtools-select>
                <option value="">自动（最新）</option>
                @foreach($recent as $option)
                    <option value="{{ $option['id'] }}" @selected(($selected ?? null) === $option['id'])>{{ substr((string) $option['created_at'], 11, 8) }} {{ $option['method'] }} {{ $option['path'] }} · {{ $option['status'] }} · {{ $option['duration_ms'] }} ms</option>
                @endforeach
            </select></label> ·
        @endif
        <a href="{{ route((string) config('pl.devtools.route_name', 'devtools.index')) }}" target="_blank" rel="noopener">全部面板与历史请求（Server / Security / Performance / Logs）</a></p>
@endif
@forelse($profiles as $profile)
    <details class="sql-debug-request" @if(count($profiles) === 1) open @endif>
        <summary><span class="sql-debug-kind" @class(['sql-debug-kind-ajax' => ($profile['type'] ?? 'html') === 'ajax'])>{{ ($profile['type'] ?? 'html') === 'ajax' ? 'AJAX' : 'HTML' }}</span> <b>{{ $profile['method'] }} {{ $profile['path'] }}</b> · {{ $profile['status'] }} · 请求 {{ $profile['duration_ms'] }} ms · SQL {{ $profile['sql_ms'] }} ms / {{ $profile['query_count'] }} 条 · <span class="sql-debug-slow">慢 {{ $profile['slow_count'] }}</span> · 重复 {{ $profile['repeated_count'] }}<small>{{ $profile['host'] }} · {{ $profile['created_at'] }}</small></summary>
        <p class="sql-debug-hint"><a href="{{ request()->fullUrlWithQuery(['request' => $profile['id'], 'export' => 'json']) }}">导出该请求 JSON</a></p>
        @if($collectors === [])
            <p>没有可用的面板，请检查 <code>pl.devtools.collectors</code> 配置。</p>
        @else
            <div class="devtools" data-devtools-tabs>
                <nav class="devtools-tabs">
                    @foreach($visibleCollectors as $collector)
                        <button type="button" class="devtools-tab @if($loop->first) is-active @endif" data-devtools-tab="{{ $collector->key() }}">{{ $collector->label() }}</button>
                    @endforeach
                </nav>
                @foreach($visibleCollectors as $collector)
                    <section class="devtools-panel" data-devtools-panel="{{ $collector->key() }}" @if(! $loop->first) hidden @endif>
                        @include($collector->view(), ['data' => $collector->collect($profile), 'profile' => $profile])
                    </section>
                @endforeach
            </div>
        @endif
    </details>
@empty
    <p>暂无记录，请先访问一个页面或操作后台接口，再刷新这里。</p>
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
