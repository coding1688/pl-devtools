@php($php = $data['php'] ?? [])
@php($app = $data['laravel'] ?? [])
@php($db = $data['database'] ?? [])
@php($services = $data['services'] ?? [])
<h3 class="sql-debug-subtitle">运行环境</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>PHP</th><td>{{ $php['version'] ?? '—' }} · {{ $php['sapi'] ?? '—' }} · {{ $php['bits'] ?? '—' }} bit</td></tr>
    <tr><th>系统</th><td>{{ $php['os'] ?? '—' }} · {{ $php['hostname'] ?? '—' }} · {{ $php['server'] ?? '—' }}</td></tr>
    <tr><th>负载</th><td>{{ $php['load'] ? "{$php['load']['1']} / {$php['load']['5']} / {$php['load']['15']}" : '—' }}</td></tr>
    <tr><th>内存上限</th><td>{{ $php['memory_limit'] ?? '—' }}</td></tr>
    <tr><th>执行时间上限</th><td>{{ $php['max_execution_time'] ?? '—' }} s</td></tr>
    <tr><th>上传 / POST 上限</th><td>{{ $php['upload_max_filesize'] ?? '—' }} / {{ $php['post_max_size'] ?? '—' }}</td></tr>
    <tr><th>max_input_vars</th><td>{{ $php['max_input_vars'] ?? '—' }}</td></tr>
    <tr><th>时区</th><td>{{ $php['timezone'] ?? '—' }}</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">应用</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>Laravel</th><td>{{ $app['version'] ?? '—' }} · {{ $app['environment'] ?? '—' }}@if($app['debug'] ?? false) · <span class="sql-debug-slow">APP_DEBUG=true</span>@endif</td></tr>
    <tr><th>APP_URL</th><td>{{ $app['url'] ?? '—' }}</td></tr>
    <tr><th>Locale / 时区</th><td>{{ $app['locale'] ?? '—' }} · {{ $app['timezone'] ?? '—' }} · {{ $app['locales'] ?? 0 }} 种语言</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">数据库</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>连接</th><td>{{ $db['connection'] ?? '—' }} · {{ $db['driver'] ?? '—' }}</td></tr>
    <tr><th>版本</th><td>{{ $db['version'] ?? '—' }}</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">服务驱动</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>Cache</th><td>{{ $services['cache'] ?? '—' }}</td></tr>
    <tr><th>Queue</th><td>{{ $services['queue'] ?? '—' }}</td></tr>
    <tr><th>Session</th><td>{{ $services['session'] ?? '—' }} · secure={{ ($services['session_secure'] ?? false) ? 'true' : 'false' }}</td></tr>
    <tr><th>Filesystem</th><td>{{ $services['filesystem'] ?? '—' }}</td></tr>
    <tr><th>Broadcast / Mail</th><td>{{ $services['broadcast'] ?? '—' }} / {{ $services['mail'] ?? '—' }}</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">磁盘</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>路径</th><th>可用</th><th>总量</th><th>已用</th><th>可写</th></tr></thead><tbody>
    @foreach(($data['storage'] ?? []) as $row)
        <tr><td>{{ $row['path'] }}</td><td>{{ $row['free'] }}</td><td>{{ $row['total'] }}</td><td>{{ $row['used'] }}</td><td>{{ $row['writable'] ? 'yes' : '<span class="sql-debug-slow">no</span>' }}</td></tr>
    @endforeach
</tbody></table></div>

<h3 class="sql-debug-subtitle">PHP 扩展</h3>
<p class="sql-debug-extensions">
    @foreach(($data['extensions'] ?? []) as $name => $loaded)
        <span class="sql-debug-extension @if($loaded) is-loaded @endif">{{ $name }}</span>
    @endforeach
</p>

@php($opcache = $data['opcache'] ?? ['enabled' => false])
<h3 class="sql-debug-subtitle">OPcache</h3>
@if($opcache['enabled'] ?? false)
    <div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
        <tr><th>命中率</th><td>{{ $opcache['hit_rate'] ?? '—' }}</td></tr>
        <tr><th>缓存脚本</th><td>{{ $opcache['cached_scripts'] ?? 0 }}</td></tr>
        <tr><th>内存使用</th><td>{{ $opcache['memory_used'] ?? '—' }} / {{ $opcache['memory_total'] ?? '—' }}（{{ $opcache['memory'] ?? '—' }}）</td></tr>
        <tr><th>重启次数</th><td>{{ $opcache['restarts'] ?? 0 }}</td></tr>
    </tbody></table></div>
@else
    <p class="sql-debug-hint">OPcache 未启用（或 CLI 下不可用）。</p>
@endif
<p class="sql-debug-hint">只读诊断，按 {{ config('pl.devtools.diagnostics_ttl', 300) }} 秒缓存；不读取数据库凭据。</p>
