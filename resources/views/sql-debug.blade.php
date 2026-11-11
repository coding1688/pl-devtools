@if($standalone)
<!doctype html>
<html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>本地 SQL 调试</title>
@endif
<style>{!! is_string(config('pl.debug.css')) && is_file(config('pl.debug.css')) ? file_get_contents(config('pl.debug.css')) : '' !!}</style>
@if($standalone)
</head><body class="sql-debug-body"><main class="sql-debug-page">
    <h1>本地 SQL 调试</h1>
    <p>最近 {{ config('pl.debug.history_limit') }} 个请求，包含 AJAX 接口。
        <a href="?type=all">全部 {{ $counts['all'] ?? 0 }}</a> ·
        <a href="?type=html">页面 {{ $counts['html'] ?? 0 }}</a> ·
        <a href="?type=ajax">AJAX {{ $counts['ajax'] ?? 0 }}</a>
    </p>
    <p>绑定参数不展开。慢查询阈值 {{ config('pl.debug.slow_ms') }} ms。<a href="{{ route((string) config('pl.debug.route_name', 'debug.sql')) }}">刷新全部请求</a></p>
@else
<details class="sql-debug-widget" data-sql-debug>
    <summary>SQL {{ $profiles[0]['query_count'] }} 条 · {{ $profiles[0]['sql_ms'] }} ms <span>请求 {{ $profiles[0]['duration_ms'] }} ms</span>
        <span>非 SQL {{ $profiles[0]['non_sql_ms'] ?? '—' }} ms</span>
        <span>Blade {{ collect($profiles[0]['stages'] ?? [])->firstWhere('name', 'blade')['duration_ms'] ?? '—' }} ms</span>
        <span>采集 {{ $profiles[0]['collection_ms'] ?? '—' }} ms</span>
        <span>峰值 {{ $profiles[0]['peak_memory_mb'] ?? '—' }} MB</span> <span class="sql-debug-slow">慢 {{ $profiles[0]['slow_count'] }}</span> <span>重复 {{ $profiles[0]['repeated_count'] }}</span></summary>
    <div class="sql-debug-panel"><p>仅本地开发环境 · 绑定参数不展开 · <a href="{{ route('debug.sql') }}" target="_blank" rel="noopener">全部请求 / 接口 SQL</a></p>
@endif
@forelse($profiles as $profile)
    <details class="sql-debug-request" @if(count($profiles) === 1) open @endif>
        <summary><span class="sql-debug-kind" @class(['sql-debug-kind-ajax' => ($profile['type'] ?? 'html') === 'ajax'])>{{ ($profile['type'] ?? 'html') === 'ajax' ? 'AJAX' : 'HTML' }}</span> <b>{{ $profile['method'] }} {{ $profile['path'] }}</b> · {{ $profile['status'] }} · 请求 {{ $profile['duration_ms'] }} ms · SQL {{ $profile['sql_ms'] }} ms / {{ $profile['query_count'] }} 条 · <span class="sql-debug-slow">慢 {{ $profile['slow_count'] }}</span> · 重复 {{ $profile['repeated_count'] }}<small>{{ $profile['host'] }} · {{ $profile['created_at'] }}</small></summary>
        <p>非 SQL：{{ $profile['non_sql_ms'] ?? '—' }} ms · SQL 调试采集：{{ $profile['collection_ms'] ?? '—' }} ms · PHP 进程峰值内存：{{ $profile['peak_memory_mb'] ?? '—' }} MB</p>
        @if(!empty($profile['controller']) || !empty($profile['user']))
            <p>@if(!empty($profile['controller']))控制器：<code>{{ $profile['controller'] }}</code>@endif
                @if(!empty($profile['user'])) · 用户：{{ $profile['user']['guard'] }}#{{ $profile['user']['id'] }}@endif</p>
        @endif
        <p>请求计时从调试中间件开始，到业务响应生成结束；不含此前框架启动、记录保存、调试面板渲染和浏览器耗时。非 SQL = 请求 − SQL，包含等待与采集开销，不代表 PHP CPU 时间。</p>
        @php($stageLabels = array_merge(['product_data' => '产品业务数据准备', 'catalog_cards' => '卡片整理（业务数据准备的子阶段）', 'blade' => 'Blade 模板编译 / 渲染'], (array) config('pl.debug.stage_labels', [])))
        @if(!empty($profile['stages']))
            <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>阶段</th><th>总耗时 ms</th><th>其中 SQL ms</th><th>非 SQL ms</th><th>次数</th></tr></thead><tbody>
                @foreach($profile['stages'] as $stage)
                    <tr><td>{{ $stageLabels[$stage['name']] ?? $stage['name'] }}</td><td>{{ $stage['duration_ms'] }}</td><td>{{ $stage['sql_ms'] }}</td><td>{{ $stage['non_sql_ms'] }}</td><td>{{ $stage['calls'] }}</td></tr>
                @endforeach
            </tbody></table></div>
            <p>阶段耗时包含阶段内 SQL；父子阶段不能相加。Blade 嵌套模板仅累计最外层引擎耗时，不含进入引擎前的视图数据准备。未接入的阶段不显示；“—”表示未采集。</p>
        @else
            <p>此请求未采集业务 / Blade 阶段计时。</p>
        @endif
        @if(!empty($profile['duplicates']))
            <h3 class="sql-debug-subtitle">重复查询（按语句结构，共 {{ count($profile['duplicates']) }} 组）</h3>
            <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>次数</th><th>总耗时</th><th>SQL</th></tr></thead><tbody>
                @foreach($profile['duplicates'] as $group)
                    <tr><td>{{ $group['count'] }}</td><td @class(['sql-debug-slow' => $group['ms'] >= config('pl.debug.slow_ms')])>{{ $group['ms'] }} ms</td><td><pre>{{ $group['sql'] }}</pre></td></tr>
                @endforeach
            </tbody></table></div>
        @endif
        <h3 class="sql-debug-subtitle">查询明细</h3>
        <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>执行顺序</th><th>耗时</th><th>重复</th><th>SQL / 调用位置</th></tr></thead><tbody>
            @foreach($profile['queries'] as $query)
                <tr><td>{{ $query['number'] }}</td><td @class(['sql-debug-slow' => $query['ms'] >= config('pl.debug.slow_ms')])>{{ $query['ms'] }} ms</td><td>{{ $query['repetitions'] }}</td><td><pre>{{ $query['sql'] }}</pre><small>{{ $query['source'] ?? '—' }} · {{ $query['connection'] }} · {{ $query['parameter_count'] }} 个参数</small></td></tr>
            @endforeach
        </tbody></table></div>
        @if($profile['query_count'] > count($profile['queries']))<p>仅展示前 {{ count($profile['queries']) }} 条明细，总耗时包含全部查询。</p>@endif
    </details>
@empty
    <p>暂无记录，请先访问一个页面或操作后台接口，再刷新这里。</p>
@endforelse
@if($standalone)
</main></body></html>
@else
    </div>
</details>
@endif
