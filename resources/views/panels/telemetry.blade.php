@php($http = $data['http'] ?? [])
@php($cache = $data['cache'] ?? [])
<h3 class="sql-debug-subtitle">{{ $t('telemetry.cache') }}</h3>
<p class="sql-debug-extensions">
    <span class="sql-debug-extension is-loaded">hit {{ $cache['hit'] ?? 0 }}</span>
    <span class="sql-debug-extension @if(($cache['miss'] ?? 0) > 0) is-loaded @endif">miss {{ $cache['miss'] ?? 0 }}</span>
    <span class="sql-debug-extension is-loaded">write {{ $cache['written'] ?? 0 }}</span>
    <span class="sql-debug-extension is-loaded">forget {{ $cache['forgotten'] ?? 0 }}</span>
</p>
<h3 class="sql-debug-subtitle">{{ $t('telemetry.calls', ['n' => count($http)]) }}</h3>
@if($http === [])
    <p class="sql-debug-hint">{{ $t('telemetry.none') }}</p>
@else
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('sql.time') }}</th><th>{{ $t('telemetry.status') }}</th><th>{{ $t('telemetry.request') }}</th></tr></thead><tbody>
        @foreach($http as $call)
            <tr>
                <td @class(['sql-debug-slow' => ($call['ms'] ?? 0) >= config('devtools.slow_ms')])>{{ $call['ms'] ?? '—' }} ms</td>
                <td>{{ $call['status'] ?? 'FAILED' }}</td>
                <td><code>{{ $call['method'] }} {{ $call['url'] }}</code>@if(!empty($call['error']))<br><small class="sql-debug-slow">{{ $call['error'] }}</small>@endif</td>
            </tr>
        @endforeach
    </tbody></table></div>
@endif
<p class="sql-debug-hint">{{ $t('telemetry.hint') }}</p>
