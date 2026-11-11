<p class="sql-debug-hint">非 SQL：{{ $profile['non_sql_ms'] ?? '—' }} ms · SQL 调试采集：{{ $data['collection_ms'] ?? '—' }} ms · PHP 进程峰值内存：{{ $profile['peak_memory_mb'] ?? '—' }} MB</p>
@php($stageLabels = array_merge(['product_data' => '产品业务数据准备', 'catalog_cards' => '卡片整理（业务数据准备的子阶段）', 'blade' => 'Blade 模板编译 / 渲染'], $data['stage_labels'] ?? []))
@if(!empty($data['stages']))
    <h3 class="sql-debug-subtitle">阶段耗时</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>阶段</th><th>总耗时 ms</th><th>其中 SQL ms</th><th>非 SQL ms</th><th>次数</th></tr></thead><tbody>
        @foreach($data['stages'] as $stage)
            <tr><td>{{ $stageLabels[$stage['name']] ?? $stage['name'] }}</td><td>{{ $stage['duration_ms'] }}</td><td>{{ $stage['sql_ms'] }}</td><td>{{ $stage['non_sql_ms'] }}</td><td>{{ $stage['calls'] }}</td></tr>
        @endforeach
    </tbody></table></div>
@endif
@if(!empty($data['hotspots']))
    <h3 class="sql-debug-subtitle">N+1 热点（按调用位置）</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>次数</th><th>总耗时</th><th>调用位置 / SQL</th></tr></thead><tbody>
        @foreach($data['hotspots'] as $hotspot)
            <tr><td>{{ $hotspot['count'] }}</td><td @class(['sql-debug-slow' => $hotspot['ms'] >= $data['slow_ms']])>{{ $hotspot['ms'] }} ms</td><td><small>{{ $hotspot['source'] }}</small><pre>{{ $hotspot['sql'] }}</pre></td></tr>
        @endforeach
    </tbody></table></div>
@endif
@if(!empty($data['queries']))
    <h3 class="sql-debug-subtitle">时间线</h3>
    <div class="sql-debug-timeline">
        @foreach(collect($data['queries'])->sortBy('start_ms') as $query)
            @php($left = min(100, ($query['start_ms'] ?? 0) / max(1, $data['duration_ms']) * 100))
            @php($width = max(0.4, min(100, $query['ms'] / max(1, $data['duration_ms']) * 100)))
            <div class="sql-debug-timeline-row">
                <span class="sql-debug-timeline-label">#{{ $query['number'] }}</span>
                <span class="sql-debug-timeline-track"><span class="sql-debug-timeline-bar" style="left: {{ $left }}%; width: {{ $width }}%"></span></span>
                <span class="sql-debug-timeline-ms">{{ $query['ms'] }} ms</span>
            </div>
        @endforeach
    </div>
@endif
@if(!empty($data['duplicates']))
    <h3 class="sql-debug-subtitle">重复查询（按语句结构）</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>次数</th><th>总耗时</th><th>SQL</th></tr></thead><tbody>
        @foreach($data['duplicates'] as $group)
            <tr><td>{{ $group['count'] }}</td><td @class(['sql-debug-slow' => $group['ms'] >= $data['slow_ms']])>{{ $group['ms'] }} ms</td><td><pre>{{ $group['sql'] }}</pre></td></tr>
        @endforeach
    </tbody></table></div>
@endif
<h3 class="sql-debug-subtitle">查询明细</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-queries"><thead><tr><th>执行顺序</th><th>耗时</th><th>重复</th><th>SQL / 调用位置</th></tr></thead><tbody>
    @foreach($data['queries'] as $query)
        <tr><td>{{ $query['number'] }}</td><td @class(['sql-debug-slow' => $query['ms'] >= $data['slow_ms']])>{{ $query['ms'] }} ms</td><td>{{ $query['repetitions'] }}</td><td><pre>{{ $query['sql'] }}</pre><small>{{ $query['source'] ?? '—' }} · {{ $query['connection'] }} · {{ $query['parameter_count'] }} 个参数</small></td></tr>
    @endforeach
</tbody></table></div>
@if($data['query_count'] > count($data['queries']))<p class="sql-debug-hint">仅展示前 {{ count($data['queries']) }} 条明细，总耗时包含全部查询。</p>@endif
