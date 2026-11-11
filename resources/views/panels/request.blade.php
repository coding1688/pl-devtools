<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>{{ $t('request.request') }}</th><td><code>{{ $data['method'] }} {{ $data['path'] }}</code></td></tr>
    <tr><th>{{ $t('request.status') }}</th><td>{{ $data['status'] }} · {{ strtoupper((string) $data['type']) }}</td></tr>
    <tr><th>{{ $t('request.route') }}</th><td>{{ $data['route'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('request.controller') }}</th><td>{{ $data['controller'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('request.user') }}</th><td>{{ $data['user'] ? $data['user']['guard'].'#'.$data['user']['id'] : '—' }}</td></tr>
    <tr><th>{{ $t('request.host') }}</th><td>{{ $data['host'] }} · {{ $data['ip'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('request.duration') }}</th><td>{{ $t('request.duration_value', ['request' => $data['duration_ms'], 'sql' => $data['sql_ms'], 'count' => $data['query_count']]) }}</td></tr>
</tbody></table></div>
@if(!empty($data['headers']))
    <h3 class="sql-debug-subtitle">{{ $t('request.headers') }}</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
        @foreach($data['headers'] as $name => $value)
            <tr><th>{{ $name }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </tbody></table></div>
@endif
@if(!empty($data['middleware']))
    <h3 class="sql-debug-subtitle">{{ $t('request.middleware') }}</h3>
    <p class="sql-debug-hint">{{ implode(' → ', $data['middleware']) }}</p>
@endif
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>{{ $t('request.query') }}</th><td>{{ $data['query_keys'] === [] ? '—' : implode(', ', $data['query_keys']) }}</td></tr>
    <tr><th>{{ $t('request.input') }}</th><td>{{ $data['input_keys'] === [] ? '—' : implode(', ', $data['input_keys']) }}</td></tr>
</tbody></table></div>
<p class="sql-debug-hint">{{ $t('request.privacy') }}</p>
