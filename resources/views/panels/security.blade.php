@php($counts = $data['counts'] ?? [])
<p class="sql-debug-hint">{{ $t('security.note', ['at' => $data['generated_at'] ?? '—', 'pass' => $counts['pass'] ?? 0, 'fail' => $counts['fail'] ?? 0, 'skip' => $counts['skip'] ?? 0]) }}</p>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-checks"><thead><tr><th>{{ $t('security.level') }}</th><th>{{ $t('security.status') }}</th><th>{{ $t('security.check') }}</th></tr></thead><tbody>
    @foreach(($data['checks'] ?? []) as $check)
        @php($statusLabel = $check['status'] === 'fail' ? 'FAIL' : ($check['status'] === 'skip' ? 'SKIP' : 'PASS'))
        <tr>
            <td><span class="sql-debug-severity sql-debug-severity-{{ $check['severity'] }}">{{ strtoupper($check['severity']) }}</span></td>
            <td><span @class(['sql-debug-slow' => $check['status'] === 'fail'])>{{ $statusLabel }}</span></td>
            <td><b>{{ $check['title'] }}</b><br><small>{{ $check['detail'] }}</small>@if(!empty($check['fix']))<br><small class="sql-debug-hint">{{ $t('security.fix') }}{{ $check['fix'] }}</small>@endif</td>
        </tr>
    @endforeach
</tbody></table></div>
<p class="sql-debug-hint">{{ $t('security.cli') }}</p>
