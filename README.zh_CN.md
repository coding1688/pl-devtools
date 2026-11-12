# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

Laravel 本地开发诊断工具：请求画像、SQL 时间线、服务器信息、安全隐患、HTTP / 缓存遥测、性能排行和日志摘要，集中在同一个页面里。

- PHP 8.2+，Laravel 11 / 12 / 13
- 默认关闭，只在 `APP_DEBUG=true` 且显式开启时工作
- 与前端框架无关：Blade、Layui、Vue SPA
- 界面支持英语、简体中文、繁体中文、日语、韩语 都能用
- 同一套安全检查可以跑 CI：`php artisan devtools:audit`

## 目录

- [特性](#特性)
- [安装](#安装)
- [快速开始](#快速开始)
- [工具栏](#工具栏)
- [独立页面](#独立页面)
- [面板说明](#面板说明)
- [安全审计](#安全审计)
- [配置](#配置)
- [扩展](#扩展)
- [命令](#命令)
- [故障排查](#故障排查)
- [版本与兼容](#版本与兼容)
- [相关包](#相关包)
- [许可](#许可)

## 特性

| 面板 | 内容 |
| --- | --- |
| Request | 方法/路径/状态、路由、`controller@action`、登录用户、Host/IP、白名单请求头、中间件、查询与提交字段名 |
| SQL | 条数/耗时、慢查询、阶段耗时、N+1 调用位置热点、时间线、重复查询分组、查询明细 |
| HTTP / Cache | 外发 HTTP 调用的方法/地址/状态/耗时/失败原因，缓存 hit/miss/write/forget 计数 |
| Server | PHP/SAPI/OS/负载、Laravel 版本与环境、DB 连接与版本、cache/queue/session/fs 驱动、磁盘、扩展、OPcache |
| Security | 12 项只读安全检查，带级别与修复建议 |
| Performance | 跨请求排行：最慢请求、最慢查询、重复最多的语句 |
| Logs | 日志尾部：按级别计数 + 最近条目（只保留首行并截断） |

另外提供：

- 底部内联工具栏（可切换面板、可锁定历史请求、跟随 SPA/Layui iframe 导航刷新）
- 独立历史页（过滤、自动刷新、清空、导出 JSON）
- `devtools:audit` 命令（CI 可用的安全门禁）

本包不依赖宿主应用的模型、数据表、Service 或配置文件；只使用自己的 `pl.devtools` 默认值（启动时合并）、配置的 cache store、日志文件与数据库版本查询，因此可以放进任意 Laravel 11–13 项目。

## 安装

### 1. 通过 Composer 安装

包发布在 GitHub（VCS 仓库），先在宿主 `composer.json` 添加：

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/coding1688/pl-devtools.git" }
]
```

然后安装为开发依赖：

```bash
composer require --dev coding1688/pl-devtools
```

Laravel 会自动发现 `Coding1688\DevTools\DevToolsServiceProvider`。如果宿主关闭了自动发现，在 `bootstrap/providers.php` 手动加入：

```php
Coding1688\DevTools\DevToolsServiceProvider::class,
```

> 作为 `--dev` 依赖安装时，生产环境执行 `composer install --no-dev` 不会带上它，这是推荐做法。

### 2. 开启

`.env`：

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```


### 3. 访问

- 任意 HTML 页面底部会出现工具栏；
- 独立页面：`http://<你的域名>/__debug/sql`。

首次打开没有数据是正常的：先访问一个页面或操作一次接口，再刷新。

### 4. 配置（可选）

包的默认配置合并到 `pl.devtools`。推荐在宿主 `config/pl.php` 里覆盖：

```php
return [
    'devtools' => [
        'enabled' => env('PL_DEVTOOLS_ENABLED', false),
        'hosts' => ['b.techpeek.local'],   // 留空表示任意 host
        'middleware' => ['auth'],          // 额外保护调试路由
        'slow_ms' => 100,
    ],
];
```

需要改视图或样式时发布：

```bash
php artisan vendor:publish --tag=pl-devtools-views
php artisan vendor:publish --tag=pl-devtools-assets
```

### 4. 发布配置模板（可选）

```bash
php artisan vendor:publish --tag=pl-devtools-config
```

会生成 `config/devtools.php`（顶层 `devtools` 键），启动时合并进 `pl.devtools`。优先级：

```
包默认值 < config/devtools.php < config/pl.php（宿主覆盖）
```

## 快速开始

1. 打开任意页面，底部工具栏显示本次请求的 SQL 条数、耗时、峰值内存；
2. 展开后可在 `Request / SQL / HTTP / Cache` 之间切换；
3. 点「全部面板与历史请求」进入独立页，查看 Server / Security / Performance / Logs 和最近 50 条请求；
4. 运行 `php artisan devtools:audit` 做一次安全检查。

## 工具栏

固定在页面底部，默认展示当前请求的摘要。展开后：

- **面板切换**：`toolbar_collectors` 控制显示哪些面板，默认 Request / SQL / HTTP-Cache；
- **查看下拉框**：
  - `自动（最新）`：每 `toolbar_refresh` 秒刷新到最新请求；
  - 选择某条历史请求：锁定该条，停止自动跟随，可随时切回自动；
- **状态保留**：刷新/切换后，展开状态和当前选中项都会保留；
- **入口**：`全部面板与历史请求` 打开独立页。

兼容性：

- Blade / Layui 整页渲染：工具栏随页面刷新；
- Vue SPA、Layui iframe tab：工具栏通过 `?toolbar=1` 轮询更新，轮询只在**顶层窗口**运行；
- `toolbar_in_iframes = false` 可让 iframe 内不显示工具栏；
- `toolbar_refresh = 0` 可完全关闭轮询（纯 Blade 站点可这样设置）。

## 独立页面

默认地址 `/__debug/sql`，支持以下参数：

| 参数 | 作用 |
| --- | --- |
| `?type=all\|html\|ajax` | 按请求类型过滤 |
| `?request=<uuid>` | 只显示某一条画像 |
| `?refresh=2\|5` | 自动刷新秒数 |
| `?fresh=1` | 清空 Server / Security / Performance / Logs 的缓存快照 |
| `?export=json` | 导出当前筛选结果（附件下载） |
| `?toolbar=1` | 工具栏轮询使用的片段接口，一般无需手动访问 |

页面操作：清空历史、导出全部、单条导出。

## 面板说明

### Request

请求、状态、路由、控制器、用户、Host/IP、耗时；白名单请求头（`accept`、`content-type`、`x-requested-with`、`referer`、`user-agent`）；中间件链；查询参数与提交字段的**字段名**。

只记录字段名与白名单请求头，不记录参数值、Cookie 或会话内容；`collect_request = false` 可关闭。

### SQL

- 慢查询阈值 `slow_ms`，慢查询标红；
- 阶段耗时：宿主用 `$profiler->measure('name', fn () => ...)` 埋点，`stage_labels` 可配显示名；
- N+1 热点：按调用位置（`file:line`）聚合重复查询；
- 时间线：按执行顺序显示开始偏移与持续时间；
- 重复查询：按语句结构聚合次数与总耗时；
- 查询明细：按耗时排序，附调用位置、连接、参数个数。

### HTTP / Cache

监听 Laravel HTTP Client 与 Cache 事件：

- 外呼：方法、地址（去掉查询串）、状态码、耗时、失败原因；
- 缓存：hit / miss / write / forget 计数。

只统计通过 `Http::` 发出的请求；直接 `curl` / Guzzle 不会计入。`telemetry` 下可分别关闭 HTTP 与缓存采集。

### Server

PHP 版本/SAPI/位数、OS/hostname/Web Server/负载、`memory_limit` 等 INI、Laravel 版本与环境、`APP_URL`、DB 连接与版本、cache/queue/session/filesystem/broadcast/mail 驱动、磁盘可用与可写、17 个常用扩展、OPcache 命中率与内存。

按 `diagnostics_ttl` 缓存，不会进入每请求画像；`?fresh=1` 强制重算。

### Security

12 项只读检查，失败项显示级别与修复建议：

| 级别 | 检查 |
| --- | --- |
| high | `APP_DEBUG` 在非 local 开启、`APP_KEY` 缺失/过短、`APP_URL` 非 HTTPS、调试工具在非 local 开启、`storage`/`bootstrap/cache` 不可写、`.env`/`.git` 在 `public/`、上传白名单含可执行扩展名、依赖漏洞（可选） |
| medium | 会话 Cookie 的 `secure`/`http_only`/`same_site`、DB 使用 root/postgres 账号 |
| low | 生产 `QUEUE_CONNECTION=sync` / `CACHE_STORE=file`、`BCRYPT_ROUNDS<10` |

### Performance

基于缓存中的历史画像做跨请求聚合：最慢请求、最慢查询、重复最多的语句。条数由 `performance.top` 控制。

### Logs

只读取日志尾部（`logs.max_bytes`），按 `logs.levels` 过滤级别，只保留首行摘要（最长 300 字符），不展示堆栈。

## 安全审计

```bash
php artisan devtools:audit                    # 表格输出，高危返回非 0
php artisan devtools:audit --fail-on=medium  # 中危及以上返回非 0
php artisan devtools:audit --fail-on=none    # 只报告
php artisan devtools:audit --json --fresh    # JSON 输出，忽略缓存
```

退出码：命中阈值返回 `1`，否则 `0`。

CI 示例（GitHub Actions）：

```yaml
name: security-audit
on: [push, pull_request]
jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install --no-interaction --prefer-dist
      - run: php artisan devtools:audit --fail-on=high
```

依赖漏洞检查默认关闭（会执行 `composer audit`），需要时设置 `security.composer_audit = true`。

## 配置

全部键位于 `pl.devtools`。

### 基础

| 键 | 默认值 | 说明 |
| --- | --- | --- |
| `enabled` | `env('PL_DEVTOOLS_ENABLED', false)` | 总开关 |
| `environments` | `['local','testing']` | 允许的环境 |
| `hosts` | `[]` | 允许的 host，空表示任意 |
| `path` | `__debug/sql` | 独立页路径 |
| `route_name` | `debug.sql` | 路由名 |
| `middleware` | `[]` | 调试路由的额外中间件（如 `auth`） |
| `cache_store` | `file` | 画像存储的 cache store |
| `ttl_seconds` | `1800` | 画像保留秒数 |
| `history_limit` | `50` | 历史请求条数 |
| `store_cache` | `true` | 是否持久化画像 |
| `log` | `false` | 是否写入查询日志 |
| `log_channel` | `single` | 日志通道 |
| `css` | `null` | 自定义 CSS 文件路径 |
| `locale` | `null` | 界面语言：`en`、`zh_CN`、`zh_TW`、`ja`、`ko`；`null` 依次取浏览器 Accept-Language、应用 locale、英文 |

### 采集

| 键 | 默认值 | 说明 |
| --- | --- | --- |
| `slow_ms` | `100` | 慢查询阈值 |
| `max_queries` | `500` | 单个请求最多记录条数 |
| `max_sql_length` | `16384` | 单条 SQL 截断长度 |
| `exclude_paths` | `[]` | 归因时忽略的应用路径 |
| `collect_request` | `true` | 记录 Request 面板数据 |
| `show_controller` | `true` | 显示控制器/方法 |
| `track_user` / `user_guards` | `true` / `['backend','web','partner']` | 是否记录登录用户及检查的 guard |
| `duplicate_groups` | `5` | 每个请求展示的重复查询分组数 |
| `hotspots` | `5` | 按调用位置聚合的热点数量 |
| `ignore_impossible_queries` | `false` | 忽略 `where 0 = 1` 这类恒假查询 |
| `stage_labels` | `[]` | `measure()` 阶段名的显示标签 |
| `ignore_routes` | `[]` | 不采集的路由名，支持 `admin.*` |
| `ignore_paths` | `[]` | 不采集的路径，支持 `api/*` |

### 工具栏

| 键 | 默认值 | 说明 |
| --- | --- | --- |
| `toolbar_collectors` | `['request','sql','telemetry']` | 工具栏展示的面板 |
| `toolbar_refresh` | `2` | 轮询秒数，0 关闭 |
| `toolbar_history` | `20` | 下拉框可选的最近请求数 |
| `toolbar_in_iframes` | `true` | iframe 内是否显示 |

### 诊断

| 键 | 默认值 | 说明 |
| --- | --- | --- |
| `diagnostics_ttl` | `300` | Server / Performance / Logs 快照缓存秒数 |
| `security_ttl` | `300` | 安全检查结果缓存秒数 |
| `telemetry.enabled` / `.http` / `.cache` / `.max_http` | `true` / `true` / `true` / `20` | HTTP 与缓存遥测 |
| `performance.top` | `10` | 每类排行条数 |
| `logs.path` | `null`（`storage/logs/laravel.log`） | 日志文件 |
| `logs.levels` | `ERROR/CRITICAL/ALERT/EMERGENCY/WARNING` | 采集级别 |
| `logs.limit` | `50` | 展示条数 |
| `logs.max_bytes` | `524288` | 只读尾部字节数 |
| `security.checks` | 12 个检查类 | 启用的检查 |
| `security.composer_audit` | `false` | 是否运行 `composer audit` |
| `security.upload_extensions_config` | `files.uploads.allowed_extensions` | 上传检查读取的配置键；不存在时跳过 |
| `locales` | `null` | Server 面板展示的语言列表（可选） |

## 发布与合并

- 三个 `pl-*` 包都不会发布 `config/pl.php`；该文件由宿主维护，`vendor:publish` 不会覆盖它。
- `pl.devtools` 采用深合并：映射合并、列表替换。只写 `['security' => ['composer_audit' => true]]` 这类局部覆盖时会保留内置检查项。
- 各包自己的发布项：`pl-files` → `config/files.php`（`files-config`），`pl-devtools` → 视图/资源；`pl-fields` 不发布配置。

## 扩展

### 自定义面板

实现 `Coding1688\DevTools\Collectors\Collector`：

```php
final class QueueCollector implements Collector
{
    public function key(): string { return 'queue'; }
    public function label(): string { return 'Queue'; }
    public function collect(array $profile): array { return [/* ... */]; }
    public function view(): string { return 'pl-devtools::panels.queue'; }
}
```

在 `pl.devtools.collectors` 里追加，并在 `resources/views/vendor/pl-devtools/panels/` 增加视图。

### 自定义安全检查

实现 `Coding1688\DevTools\Diagnostics\Security\Check`，在 `pl.devtools.security.checks` 里注册即可同时出现在面板和 `devtools:audit` 中。

### 阶段计时

```php
use Coding1688\DevTools\SqlQueryProfiler;

$profiler = app(SqlQueryProfiler::class);
$data = $profiler->measure('product_data', fn () => $service->prepare($id));
```

## 命令

| 命令 | 说明 |
| --- | --- |
| `php artisan devtools:audit` | 运行安全检查；`--json`、`--fresh`、`--fail-on=high|medium|low|info|none` |

## 故障排查

| 现象 | 排查 |
| --- | --- |
| 工具栏不出现 | 确认 `APP_DEBUG=true`、`PL_DEVTOOLS_ENABLED=true`、环境在 `environments` 内、host 在 `hosts` 内；响应必须是 HTML 且含 `</body>`；检查 `ignore_routes` / `ignore_paths` |
| AJAX 响应没有工具栏 | 设计如此（JSON 不能注入 HTML）；用独立页 `?type=ajax`，或看响应头 `X-Debug-SQL-Url` |
| SPA / Layui iframe 不刷新 | 轮询由 `toolbar_refresh` 控制（默认 2 秒），只在顶层窗口运行；观察 Network 里的 `__debug/sql?toolbar=1` |
| 数据为空 | 先访问页面产生请求；检查 `cache_store` 写权限、`history_limit`、`store_cache` |
| 页面变慢 | 调大 `diagnostics_ttl`，关闭 `telemetry`，用 `ignore_*` 排除高频路由 |
| 生产环境 | 不要开启；用 `devtools:audit` 代替 |

## 版本与兼容

- Laravel 11 / 12 / 13，PHP 8.2+
- 语义化版本：`0.x` 的 minor 增加功能，patch 修复问题
- 版本记录见 [Tags](https://github.com/coding1688/pl-devtools/tags)

## 相关包

| 包 | 说明 |
| --- | --- |
| `coding1688/pl-files` | 上传 / 文件引用 / 多磁盘 / 多云复制 |
| `coding1688/pl-fields` | 字段类型 / 校验 / 选项来源 |
| `coding1688/pl-devtools` | 本包 |

## 许可

MIT
