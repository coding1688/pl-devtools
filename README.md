# coding1688/pl-devtools

Laravel 请求级 SQL 性能分析：记录每个请求的 SQL 条数、耗时、慢查询、重复查询与阶段耗时，并在页面底部注入工具栏，另提供历史页。

- PHP `^8.2`，Laravel `^11 | ^12 | ^13`
- **默认关闭**，必须显式开启；关闭时不注册中间件、不监听查询、不注册路由
- 记录内容可只留在内存，也可写入缓存/日志（两个独立开关）
- 与业务代码无耦合

## 安装

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/coding1688/pl-devtools.git" }
    ],
    "require": {
        "coding1688/pl-devtools": "^0.1"
    }
}
```

```bash
composer update coding1688/pl-devtools
```

包会自动注册 `Coding1688\Debug\DebugServiceProvider`。

## 开启

```dotenv
PL_DEBUG_ENABLED=true
APP_DEBUG=true
```

同时满足以下条件才会生效：

1. `pl.debug.enabled`（对应 `PL_DEBUG_ENABLED`）为真；
2. `app.debug` 为真；
3. 当前环境在 `pl.debug.environments` 内（默认 `local`、`testing`；设为空数组表示不限制）；
4. 请求 host 在 `pl.debug.hosts` 内（默认为空，表示不限制）。

> 生产/预发环境不要开。若要开，至少同时设置 `hosts` 和 `middleware`（例如 `['backend.auth']`）。

## 面板

历史页的每个请求按面板展示（`pl.devtools.collectors` 控制顺序）：

| 面板 | 内容 |
| --- | --- |
| `Request` | 方法/路径/状态/路由/控制器/用户、请求头白名单、中间件、查询与提交字段名、IP |
| `SQL` | 条数/耗时/慢查询、阶段耗时、N+1 调用位置热点、时间线、重复查询分组、查询明细 |
| `Server` | PHP/SAPI/OS/负载、Laravel 版本与环境、DB 连接与版本、cache/queue/session/fs 驱动、磁盘可用与可写、PHP 扩展、OPcache 命中率 |

Request 面板只记录**字段名与白名单请求头**，不记录参数值、Cookie 或会话内容。

## 使用

开启后：

- **HTML 与 AJAX 请求都会记录**：AJAX 响应没有工具栏，但会出现在历史页，并带上 `X-Debug-SQL-Url` 响应头指向该次画像；
- 历史页支持 `?type=all|html|ajax` 过滤，并显示三类计数；
- **时间线**：按执行顺序展示每条 SQL 的开始偏移与持续时间（占请求总耗时比例）；
- **N+1 热点**：按调用位置（`file:line`）聚合重复查询；
- **自动刷新**（`?refresh=2|5`）与**清空历史**按钮；
- 每个画像显示 `controller@action`、登录用户（可配置关闭）、重复查询分组（按语句结构聚合次数与总耗时）；
- 每个 HTML 响应底部会注入一个可折叠工具栏（SQL 条数、SQL 耗时、请求耗时、非 SQL 耗时、Blade 耗时、峰值内存、慢查询、重复查询）；
- 响应头带上 `X-Debug-SQL-Request`、`X-Debug-SQL-Count`、`X-Debug-SQL-Time` 和 `Server-Timing`；
- 打开 `/{path}`（默认 `/__debug/sql`）查看最近请求的历史，`?request={id}` 查看单条；
- 支持 `Accept: application/json` 返回原始画像。

手动给代码分段打点：

```php
use Coding1688\Debug\SqlQueryProfiler;

app(SqlQueryProfiler::class)->measure('catalog_cards', fn () => $this->decorate($products));
```

阶段会出现在画像的 `stages` 里，递归同名调用只计一次。

## 配置（放在宿主的 `config/pl.php`）

```php
return [
    'debug' => [
        'enabled' => env('PL_DEBUG_ENABLED', false),
        'environments' => ['local', 'testing'],
        'hosts' => [],
        'middleware' => [],
        'cache_store' => 'file',
        'ttl_seconds' => 1800,
        'store_cache' => true,
        'log' => false,
        'log_channel' => 'single',
        'exclude_paths' => [app_path('Providers/AppServiceProvider.php')],
    ],
];
```

| 键 | 默认 | 说明 |
| --- | --- | --- |
| `enabled` | `false` | 总开关 |
| `environments` | `['local','testing']` | 允许的环境；空数组表示不限制 |
| `hosts` | `[]` | 允许的 host；空数组表示不限制 |
| `path` / `route_name` | `__debug/sql` / `debug.sql` | 历史页路由 |
| `middleware` | `[]` | 历史页额外中间件（建议加鉴权） |
| `slow_ms` | `100` | 慢查询阈值 |
| `max_queries` | `500` | 单请求最多记录条数 |
| `max_sql_length` | `16384` | SQL 文本截断长度 |
| `history_limit` | `50` | 历史保留请求数 |
| `cache_store` | `file` | 画像缓存盘 |
| `ttl_seconds` | `1800` | 画像保留时间 |
| `store_cache` | `true` | 是否写入缓存 |
| `log` / `log_channel` | `false` / `single` | 是否写日志及通道 |
| `show_controller` | `true` | 是否显示控制器/方法 |
| `track_user` / `user_guards` | `true` / `['backend','web','partner']` | 是否记录登录用户及检查的 guard |
| `duplicate_groups` | `5` | 每个请求展示的重复查询分组数 |
| `hotspots` | `5` | 按调用位置聚合的热点数量 |
| `ignore_impossible_queries` | `false` | 忽略 `where 0 = 1` 这类恒假查询 |
| `refresh_seconds` | `0` | 历史页自动刷新间隔（0 关闭） |
| `stage_labels` | `[]` | `measure()` 阶段名的显示标签 |
| `collectors` | Request / SQL | 历史页面板顺序 |
| `collect_request` | `true` | 是否记录 Request 面板数据 |
| `toolbar_collectors` | `['request','sql']` | 内联工具栏展示的面板（历史页始终展示全部） |
| `diagnostics_ttl` | `300` | Server 诊断快照缓存秒数 |
| `exclude_paths` | `[]` | 归因时忽略的应用文件 |

## 视图与样式

```bash
php artisan vendor:publish --tag=pl-debug-views   # resource/views/vendor/pl-debug
php artisan vendor:publish --tag=pl-debug-assets  # resource/css/sql-debug.css
```

不发布也能用：包内自带视图与 CSS，发布后会优先使用宿主的版本。

## pl-* 系列

- [pl-files](https://github.com/coding1688/pl-files)：上传、引用追踪、多磁盘存储、多云复制
- [pl-fields](https://github.com/coding1688/pl-fields)：字段类型注册与选项来源
- pl-devtools（本包）
