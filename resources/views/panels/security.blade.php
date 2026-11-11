@php($counts = $data['counts'] ?? [])
<p class="sql-debug-hint">只读检查 · 生成于 {{ $data['generated_at'] ?? '—' }} · 通过 {{ $counts['pass'] ?? 0 }} · 失败 {{ $counts['fail'] ?? 0 }} · 跳过 {{ $counts['skip'] ?? 0 }}</p>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>级别</th><th>结论</th><th>检查项 / 说明</th></tr></thead><tbody>
    @foreach(($data['checks'] ?? []) as $check)
        <tr>
            <td><span class="sql-debug-severity sql-debug-severity-{{ $check['severity'] }}">{{ strtoupper($check['severity']) }}</span></td>
            @php($statusLabel = $check['status'] === 'fail' ? 'FAIL' : ($check['status'] === 'skip' ? 'SKIP' : 'PASS'))
            <td><span @class(['sql-debug-slow' => $check['status'] === 'fail'])>{{ $statusLabel }}</span></td>
            <td><b>{{ $check['title'] }}</b><br><small>{{ $check['detail'] }}</small>@if(!empty($check['fix']))<br><small class="sql-debug-hint">修复：{{ $check['fix'] }}</small>@endif</td>
        </tr>
    @endforeach
</tbody></table></div>
<p class="sql-debug-hint">同一套检查可在命令行运行：<code>php artisan devtools:audit --fail-on=high</code></p>
