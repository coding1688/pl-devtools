@php($counts = $data['counts'] ?? [])
<p class="sql-debug-hint">{{ $data['path'] ?? '—' }} · {{ $data['size'] ?? '—' }} · {{ $t('logs.note') }}</p>
@if(!empty($counts))
    <p class="sql-debug-extensions">
        @foreach($counts as $level => $count)
            <span class="sql-debug-extension is-loaded">{{ $level }} {{ $count }}</span>
        @endforeach
    </p>
@endif
@if(($data['exists'] ?? false) === false)
    <p>{{ $t('logs.missing') }}</p>
@else
    <div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-logs"><thead><tr><th>{{ $t('sql.time') }}</th><th>{{ $t('logs.level') }}</th><th>{{ $t('logs.message') }}</th></tr></thead><tbody>
        @foreach(($data['entries'] ?? []) as $entry)
            <tr><td><small>{{ $entry['time'] }}</small></td><td><span class="sql-debug-severity sql-debug-severity-{{ $entry['level'] === 'WARNING' ? 'medium' : 'high' }}">{{ $entry['level'] }}</span></td><td>{{ $entry['message'] }}</td></tr>
        @endforeach
    </tbody></table></div>
@endif
