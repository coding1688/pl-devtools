@php($http = $data['http'] ?? [])
@php($cache = $data['cache'] ?? [])
<h3 class="sql-debug-subtitle">缓存</h3>
<p class="sql-debug-extensions">
    <span class="sql-debug-extension is-loaded">hit {{ $cache['hit'] ?? 0 }}</span>
    <span class="sql-debug-extension @if(($cache['miss'] ?? 0) > 0) is-loaded @endif">miss {{ $cache['miss'] ?? 0 }}</span>
    <span class="sql-debug-extension is-loaded">write {{ $cache['written'] ?? 0 }}</span>
    <span class="sql-debug-extension is-loaded">forget {{ $cache['forgotten'] ?? 0 }}</span>
</p>
<h3 class="sql-debug-subtitle">HTTP 客户端（{{ count($http) }} 次）</h3>
@if($http === [])
    <p class="sql-debug-hint">本请求没有外发 HTTP 调用。</p>
@else
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>耗时</th><th>状态</th><th>请求</th></tr></thead><tbody>
        @foreach($http as $call)
            <tr>
                <td @class(['sql-debug-slow' => ($call['ms'] ?? 0) >= config('pl.devtools.slow_ms')])>{{ $call['ms'] ?? '—' }} ms</td>
                <td>{{ $call['status'] ?? 'FAILED' }}</td>
                <td><code>{{ $call['method'] }} {{ $call['url'] }}</code>@if(!empty($call['error']))<br><small class="sql-debug-slow">{{ $call['error'] }}</small>@endif</td>
            </tr>
        @endforeach
    </tbody></table></div>
@endif
<p class="sql-debug-hint">只记录外呼地址（去掉查询串）、状态与耗时，以及缓存命中计数；不记录响应体与缓存键。</p>
