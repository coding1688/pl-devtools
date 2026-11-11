@php($totals = $data['totals'] ?? [])
<p class="sql-debug-hint">基于最近 {{ $totals['requests'] ?? 0 }} 个请求聚合 · 慢查询 {{ $totals['slow_queries'] ?? 0 }} 条 · 生成于 {{ $data['generated_at'] ?? '—' }}</p>
<h3 class="sql-debug-subtitle">最慢请求</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>耗时</th><th>请求</th><th>SQL</th><th>条数</th><th>时间</th></tr></thead><tbody>
    @foreach(($data['requests'] ?? []) as $row)
        <tr><td @class(['sql-debug-slow' => $row['duration_ms'] >= config('pl.devtools.slow_ms')])>{{ $row['duration_ms'] }} ms</td><td>{{ $row['method'] }} {{ $row['path'] }}</td><td>{{ $row['sql_ms'] }} ms</td><td>{{ $row['query_count'] }}</td><td><small>{{ $row['created_at'] }}</small></td></tr>
    @endforeach
</tbody></table></div>
<h3 class="sql-debug-subtitle">最慢查询</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>耗时</th><th>SQL / 来源</th></tr></thead><tbody>
    @foreach(($data['queries'] ?? []) as $row)
        <tr><td @class(['sql-debug-slow' => $row['ms'] >= config('pl.devtools.slow_ms')])>{{ $row['ms'] }} ms</td><td><pre>{{ $row['sql'] }}</pre><small>{{ $row['source'] ?? '—' }} · {{ $row['path'] }}</small></td></tr>
    @endforeach
</tbody></table></div>
<h3 class="sql-debug-subtitle">重复最多的语句</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>次数</th><th>总耗时</th><th>请求数</th><th>SQL</th></tr></thead><tbody>
    @foreach(($data['repeated'] ?? []) as $row)
        <tr><td>{{ $row['count'] }}</td><td>{{ $row['ms'] }} ms</td><td>{{ $row['requests'] }}</td><td><pre>{{ $row['sql'] }}</pre></td></tr>
    @endforeach
</tbody></table></div>
<p class="sql-debug-hint">聚合基于缓存中的历史画像；清空历史或等待 TTL 后重新统计。</p>
