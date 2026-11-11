@php($counts = $data['counts'] ?? [])
<p class="sql-debug-hint">{{ $data['path'] ?? '—' }} · {{ $data['size'] ?? '—' }} · 只读取尾部，只保留级别与首行</p>
@if(!empty($counts))
    <p class="sql-debug-extensions">
        @foreach($counts as $level => $count)
            <span class="sql-debug-extension is-loaded">{{ $level }} {{ $count }}</span>
        @endforeach
    </p>
@endif
@if(($data['exists'] ?? false) === false)
    <p>日志文件不存在或不可读。</p>
@else
    <div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-logs"><thead><tr><th>时间</th><th>级别</th><th>消息</th></tr></thead><tbody>
        @foreach(($data['entries'] ?? []) as $entry)
            <tr><td><small>{{ $entry['time'] }}</small></td><td><span class="sql-debug-severity sql-debug-severity-{{ $entry['level'] === 'WARNING' ? 'medium' : 'high' }}">{{ $entry['level'] }}</span></td><td>{{ $entry['message'] }}</td></tr>
        @endforeach
    </tbody></table></div>
@endif
