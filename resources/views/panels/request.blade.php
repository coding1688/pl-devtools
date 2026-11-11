<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>请求</th><td><code>{{ $data['method'] }} {{ $data['path'] }}</code></td></tr>
    <tr><th>状态</th><td>{{ $data['status'] }} · {{ strtoupper((string) $data['type']) }}</td></tr>
    <tr><th>路由</th><td>{{ $data['route'] ?? '—' }}</td></tr>
    <tr><th>控制器</th><td>{{ $data['controller'] ?? '—' }}</td></tr>
    <tr><th>用户</th><td>{{ $data['user'] ? $data['user']['guard'].'#'.$data['user']['id'] : '—' }}</td></tr>
    <tr><th>Host / IP</th><td>{{ $data['host'] }} · {{ $data['ip'] ?? '—' }}</td></tr>
    <tr><th>耗时</th><td>请求 {{ $data['duration_ms'] }} ms · SQL {{ $data['sql_ms'] }} ms / {{ $data['query_count'] }} 条</td></tr>
</tbody></table></div>
@if(!empty($data['headers']))
    <h3 class="sql-debug-subtitle">请求头</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
        @foreach($data['headers'] as $name => $value)
            <tr><th>{{ $name }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </tbody></table></div>
@endif
@if(!empty($data['middleware']))
    <h3 class="sql-debug-subtitle">中间件</h3>
    <p class="sql-debug-hint">{{ implode(' → ', $data['middleware']) }}</p>
@endif
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>查询参数</th><td>{{ $data['query_keys'] === [] ? '—' : implode(', ', $data['query_keys']) }}</td></tr>
    <tr><th>提交字段</th><td>{{ $data['input_keys'] === [] ? '—' : implode(', ', $data['input_keys']) }}</td></tr>
</tbody></table></div>
<p class="sql-debug-hint">只记录字段名与白名单请求头，不记录参数值、Cookie 或会话内容。</p>
