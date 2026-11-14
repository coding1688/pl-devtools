@php($php = $data['php'] ?? [])
@php($app = $data['laravel'] ?? [])
@php($db = $data['database'] ?? [])
@php($services = $data['services'] ?? [])
<h3 class="sql-debug-subtitle">{{ $t('server.runtime') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>PHP</th><td>{{ $php['version'] ?? '—' }} · {{ $php['sapi'] ?? '—' }} · {{ $php['bits'] ?? '—' }} bit</td></tr>
    <tr><th>{{ $t('server.os') }}</th><td>{{ $php['os'] ?? '—' }} · {{ $php['hostname'] ?? '—' }} · {{ $php['server'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.load') }}</th><td>{{ $php['load'] ? "{$php['load']['1']} / {$php['load']['5']} / {$php['load']['15']}" : '—' }}</td></tr>
    <tr><th>{{ $t('server.memory_limit') }}</th><td>{{ $php['memory_limit'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.max_execution') }}</th><td>{{ $php['max_execution_time'] ?? '—' }} s</td></tr>
    <tr><th>{{ $t('server.upload') }}</th><td>{{ $php['upload_max_filesize'] ?? '—' }} / {{ $php['post_max_size'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.max_input') }}</th><td>{{ $php['max_input_vars'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.timezone') }}</th><td>{{ $php['timezone'] ?? '—' }}</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">{{ $t('server.app') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>{{ $t('server.laravel') }}</th><td>{{ $app['version'] ?? '—' }} · {{ $app['environment'] ?? '—' }}@if($app['debug'] ?? false) · <span class="sql-debug-slow">APP_DEBUG=true</span>@endif</td></tr>
    <tr><th>{{ $t('server.url') }}</th><td>{{ $app['url'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.locale') }}</th><td>{{ $app['locale'] ?? '—' }} · {{ $app['timezone'] ?? '—' }}@if(($app['locales'] ?? 0) > 0) · {{ $app['locales'] }}@endif</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">{{ $t('server.database') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>{{ $t('server.connection') }}</th><td>{{ $db['connection'] ?? '—' }} · {{ $db['driver'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.version') }}</th><td>{{ $db['version'] ?? '—' }}</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">{{ $t('server.services') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
    <tr><th>{{ $t('server.cache') }}</th><td>{{ $services['cache'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.queue') }}</th><td>{{ $services['queue'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.session') }}</th><td>{{ $services['session'] ?? '—' }} · secure={{ ($services['session_secure'] ?? false) ? $t('yes') : $t('no') }}</td></tr>
    <tr><th>{{ $t('server.filesystem') }}</th><td>{{ $services['filesystem'] ?? '—' }}</td></tr>
    <tr><th>{{ $t('server.broadcast_mail') }}</th><td>{{ $services['broadcast'] ?? '—' }} / {{ $services['mail'] ?? '—' }}</td></tr>
</tbody></table></div>

<h3 class="sql-debug-subtitle">{{ $t('server.disks') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('server.path') }}</th><th>{{ $t('server.free') }}</th><th>{{ $t('server.total') }}</th><th>{{ $t('server.used') }}</th><th>{{ $t('server.writable') }}</th></tr></thead><tbody>
    @foreach(($data['storage'] ?? []) as $row)
        <tr><td>{{ $row['path'] }}</td><td>{{ $row['free'] }}</td><td>{{ $row['total'] }}</td><td>{{ $row['used'] }}</td><td>@if($row['writable']){{ $t('yes') }}@else<span class="sql-debug-slow">{{ $t('no') }}</span>@endif</td></tr>
    @endforeach
</tbody></table></div>

<h3 class="sql-debug-subtitle">{{ $t('server.extensions') }}</h3>
<p class="sql-debug-extensions">
    @foreach(($data['extensions'] ?? []) as $name => $loaded)
        <span class="sql-debug-extension @if($loaded) is-loaded @endif">{{ $name }}</span>
    @endforeach
</p>

@php($opcache = $data['opcache'] ?? ['enabled' => false])
<h3 class="sql-debug-subtitle">{{ $t('server.opcache') }}</h3>
@if($opcache['enabled'] ?? false)
    <div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-kv"><tbody>
        <tr><th>{{ $t('server.hit_rate') }}</th><td>{{ $opcache['hit_rate'] ?? '—' }}</td></tr>
        <tr><th>{{ $t('server.scripts') }}</th><td>{{ $opcache['cached_scripts'] ?? 0 }}</td></tr>
        <tr><th>{{ $t('server.memory') }}</th><td>{{ $opcache['memory_used'] ?? '—' }} / {{ $opcache['memory_total'] ?? '—' }} ({{ $opcache['memory'] ?? '—' }})</td></tr>
        <tr><th>{{ $t('server.restarts') }}</th><td>{{ $opcache['restarts'] ?? 0 }}</td></tr>
    </tbody></table></div>
@else
    <p class="sql-debug-hint">{{ $t('server.opcache_off') }}</p>
@endif
<p class="sql-debug-hint">{{ $t('server.note', ['ttl' => config('devtools.diagnostics_ttl', 300)]) }}</p>
