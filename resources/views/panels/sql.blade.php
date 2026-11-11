<p class="sql-debug-hint">{{ $t('sql.non_sql', ['ms' => $profile['non_sql_ms'] ?? '—']) }} · {{ $t('sql.collection', ['ms' => $data['collection_ms'] ?? '—']) }} · {{ $t('sql.peak', ['mb' => $profile['peak_memory_mb'] ?? '—']) }}</p>
@php($stageLabels = array_merge(['product_data' => '产品业务数据准备', 'catalog_cards' => '卡片整理（业务数据准备的子阶段）', 'blade' => 'Blade 模板编译 / 渲染'], $data['stage_labels'] ?? []))
@if(!empty($data['stages']))
    <h3 class="sql-debug-subtitle">{{ $t('sql.stages') }}</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('sql.stage') }}</th><th>{{ $t('sql.total') }}</th><th>{{ $t('sql.sql_ms') }}</th><th>{{ $t('sql.non_sql_ms') }}</th><th>{{ $t('sql.calls') }}</th></tr></thead><tbody>
        @foreach($data['stages'] as $stage)
            <tr><td>{{ $stageLabels[$stage['name']] ?? $stage['name'] }}</td><td>{{ $stage['duration_ms'] }}</td><td>{{ $stage['sql_ms'] }}</td><td>{{ $stage['non_sql_ms'] }}</td><td>{{ $stage['calls'] }}</td></tr>
        @endforeach
    </tbody></table></div>
@endif
@if(!empty($data['hotspots']))
    <h3 class="sql-debug-subtitle">{{ $t('sql.hotspots') }}</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('sql.calls') }}</th><th>{{ $t('sql.total') }}</th><th>{{ $t('sql.callsite') }}</th></tr></thead><tbody>
        @foreach($data['hotspots'] as $hotspot)
            <tr><td>{{ $hotspot['count'] }}</td><td @class(['sql-debug-slow' => $hotspot['ms'] >= $data['slow_ms']])>{{ $hotspot['ms'] }} ms</td><td><small>{{ $hotspot['source'] }}</small><pre>{{ $hotspot['sql'] }}</pre></td></tr>
        @endforeach
    </tbody></table></div>
@endif
@if(!empty($data['queries']))
    <h3 class="sql-debug-subtitle">{{ $t('sql.timeline') }}</h3>
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
    <p class="sql-debug-hint">{{ $t('sql.timeline_hint') }}</p>
@endif
@if(!empty($data['duplicates']))
    <h3 class="sql-debug-subtitle">{{ $t('sql.duplicates') }}</h3>
    <div class="sql-debug-table-wrap"><table class="sql-debug-table"><thead><tr><th>{{ $t('sql.calls') }}</th><th>{{ $t('sql.total') }}</th><th>{{ $t('sql.details') }}</th></tr></thead><tbody>
        @foreach($data['duplicates'] as $group)
            <tr><td>{{ $group['count'] }}</td><td @class(['sql-debug-slow' => $group['ms'] >= $data['slow_ms']])>{{ $group['ms'] }} ms</td><td><pre>{{ $group['sql'] }}</pre></td></tr>
        @endforeach
    </tbody></table></div>
@endif
<h3 class="sql-debug-subtitle">{{ $t('sql.details') }}</h3>
<div class="sql-debug-table-wrap"><table class="sql-debug-table sql-debug-table-queries"><thead><tr><th>{{ $t('sql.order') }}</th><th>{{ $t('sql.time') }}</th><th>{{ $t('sql.repeat') }}</th><th>{{ $t('sql.query_source') }}</th></tr></thead><tbody>
    @foreach($data['queries'] as $query)
        <tr><td>{{ $query['number'] }}</td><td @class(['sql-debug-slow' => $query['ms'] >= $data['slow_ms']])>{{ $query['ms'] }} ms</td><td>{{ $query['repetitions'] }}</td><td><pre>{{ $query['sql'] }}</pre><small>{{ $query['source'] ?? '—' }} · {{ $query['connection'] }} · {{ $query['parameter_count'] }} {{ $t('sql.parameters') }}</small></td></tr>
    @endforeach
</tbody></table></div>
@if($data['query_count'] > count($data['queries']))<p class="sql-debug-hint">{{ $t('sql.only_first', ['shown' => count($data['queries'])]) }}</p>@endif
