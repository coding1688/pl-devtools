@php($totals = $data['totals'] ?? [])
<p class="sql-debug-hint">{{ $t('performance.note', ['requests' => $totals['requests'] ?? 0, 'slow' => $totals['slow_queries'] ?? 0, 'at' => $data['generated_at'] ?? '—']) }}</p>
<h3 class="sql-debug-subtitle">{{ $t('performance.slow_requests') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('sql.time') }}</th><th>{{ $t('performance.request') }}</th><th>{{ $t('performance.sql') }}</th><th>{{ $t('performance.count') }}</th><th>{{ $t('sql.time') }}</th></tr></thead><tbody>
    @foreach(($data['requests'] ?? []) as $row)
        <tr><td @class(['sql-debug-slow' => $row['duration_ms'] >= config('devtools.slow_ms')])>{{ $row['duration_ms'] }} ms</td><td>{{ $row['method'] }} {{ $row['path'] }}</td><td>{{ $row['sql_ms'] }} ms</td><td>{{ $row['query_count'] }}</td><td><small>{{ $row['created_at'] }}</small></td></tr>
    @endforeach
</tbody></table></div>
<h3 class="sql-debug-subtitle">{{ $t('performance.slow_queries') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('sql.time') }}</th><th>{{ $t('performance.request') }}</th></tr></thead><tbody>
    @foreach(($data['queries'] ?? []) as $row)
        <tr><td @class(['sql-debug-slow' => $row['ms'] >= config('devtools.slow_ms')])>{{ $row['ms'] }} ms</td><td><pre>{{ $row['sql'] }}</pre><small>{{ $row['source'] ?? '—' }} · {{ $row['path'] }}</small></td></tr>
    @endforeach
</tbody></table></div>
<h3 class="sql-debug-subtitle">{{ $t('performance.repeated') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('performance.times') }}</th><th>{{ $t('performance.total_ms') }}</th><th>{{ $t('performance.requests') }}</th><th>{{ $t('performance.sql') }}</th></tr></thead><tbody>
    @foreach(($data['repeated'] ?? []) as $row)
        <tr><td>{{ $row['count'] }}</td><td>{{ $row['ms'] }} ms</td><td>{{ $row['requests'] }}</td><td><pre>{{ $row['sql'] }}</pre></td></tr>
    @endforeach
</tbody></table></div>
<p class="sql-debug-hint">{{ $t('performance.note2') }}</p>
